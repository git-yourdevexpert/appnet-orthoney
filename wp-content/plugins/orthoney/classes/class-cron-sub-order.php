<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_WC_CRON_Suborder {
    /**
     * Constructor to hook into WooCommerce template loading.
     */
    public function __construct() {        
        add_action('woocommerce_thankyou', array($this, 'schedule_create_sub_order_cron_handler'), 10, 3);
        add_action('create_sub_order', array($this, 'process_sub_order_creation_handler'), 10, 4);
        
    }

    public function schedule_create_sub_order_cron_handler($order_id) {
        if (!$order_id) {
            return;
        }
        
    
        global $wpdb;
        
        $order_process_table = OAM_Helper::$order_process_table;
        $group_table = OAM_Helper::$group_table;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
    
        $main_order = wc_get_order($order_id);
        if (!$main_order) {
            return;
        }
    

        $main_order->update_meta_data('order_process_by', OAM_COMMON_Custom::old_user_id());
        $main_order->save();
    
        $order_items = $main_order->get_items();
        $order_items_count = count($order_items);
        
        $single_order = 0;
        $process_id = 0;
        $recipient_id = 0;
    
     
        foreach ($order_items as $item) {   
            $single_order_meta = $item->get_meta('single_order', true);
            
            if (!empty($single_order_meta) && $single_order_meta == 1) {
                $single_order = 1;
                $process_id = $item->get_meta('process_id', true) ?: 0;
            } else {
                if($item->get_meta('_recipient_order_type', true) != ''){
                    $single_order = $item->get_meta('_recipient_order_type', true);
                }
                if($item->get_meta('_recipient_process_id', true) != ''){
                    $process_id = $item->get_meta('_recipient_process_id', true);
                }

                if($item->get_meta('_recipient_recipient_id', true) != ''){
                    $recipient_id = $item->get_meta('_recipient_recipient_id', true);
                }
                if($process_id != ''){
                    break;
                }
            }
        }
    
        
        $order_type = ($single_order == 1) ? 'single-order' : 'multi-recipient-order';
    
        if ($process_id) {
    
                $wpdb->update(
                $order_process_table,
                ['order_type' => $order_type, 'order_id' => $order_id],
                ['id' => $process_id]
            );
        }
    
        if ($order_type !== 'multi-recipient-order') {
            return;
        }
    
        // Fetch process data only if needed
        $processData = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, name FROM {$order_process_table} WHERE id = %d",
            $process_id
        ));
    
        if (!$processData) {
            return;
        }
    
        // Check if group entry exists
        $group_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$group_table} WHERE order_id = %d AND pid = %d",
            $order_id, $process_id
        ));
    
        // Insert if group does not exist
        if (!$group_id) {
            $wpdb->insert($group_table, [
                'user_id'    => $processData->user_id,
                'pid'        => $process_id,
                'order_id'   => $order_id,
                'visibility' => 1,
                'name'       => sanitize_text_field($processData->name),
            ]);
            $group_id = $wpdb->insert_id;
        }
    
        if ($group_id) {
            $groupRecipientCount = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM $group_recipient_table WHERE group_id = %d",
                $group_id
            ));

             if ($groupRecipientCount != $order_items_count && !wp_next_scheduled('create_sub_order', [$order_id, $group_id, $process_id])) {
                wp_schedule_single_event(time() + 90, 'create_sub_order', [$order_id, $group_id, $process_id]);
                OAM_COMMON_Custom::sub_order_error_log("Scheduled cron job for Order ID: $order_id");
            }
        }
       
    }

    public function process_sub_order_creation_handler($order_id, $group_id, $process_id) {
        global $wpdb;
    
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $group_recipient_table = OAM_Helper::$group_recipient_table;

        if (!$order_id) {
            OAM_COMMON_Custom::sub_order_error_log("Invalid Order ID in process_sub_order_creation");
            return;
        }

        $main_order = wc_get_order($order_id);
        if (!$main_order) {
            OAM_COMMON_Custom::sub_order_error_log("Main order not found for Order ID: $order_id");
            return;
        }

        $customer_id = $main_order->get_customer_id();
        $billing_data = [
            'first_name' => $main_order->get_billing_first_name(),
            'last_name'  => $main_order->get_billing_last_name(),
            'company'    => $main_order->get_billing_company(),
            'address_1'  => $main_order->get_billing_address_1(),
            'address_2'  => $main_order->get_billing_address_2(),
            'city'       => $main_order->get_billing_city(),
            'state'      => $main_order->get_billing_state(),
            'postcode'   => $main_order->get_billing_postcode(),
            'country'    => $main_order->get_billing_country(),
            'email'      => $main_order->get_billing_email(),
            'phone'      => $main_order->get_billing_phone(),
        ];

        foreach ($main_order->get_items() as $item_id => $item) {
            $recipient_id = $item->get_meta('_recipient_recipient_id', true);
            $company_name = $item->get_meta('_recipient_company_name', true);
            $greeting = $item->get_meta('greeting', true);

            OAM_COMMON_Custom::sub_order_error_log("recipient_id: $recipient_id, company_name: $company_name");

            // Fetch recipient details
            $recipients = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$order_process_recipient_table} WHERE id = %d",
                $recipient_id
            ));

            if ($recipients && $recipients->order_id == 0) {
                $sub_order = wc_create_order();

                if ($customer_id) {
                    $sub_order->set_customer_id($customer_id);
                }

                $custom_full_name = $item->get_meta('full_name', true);
                $sub_order->set_address($billing_data, 'billing');
                $sub_order->set_billing_email($billing_data['email']);

                // Set order-level metadata
                $sub_order->update_meta_data('_recipient_recipient_id', $recipient_id);
                $sub_order->update_meta_data('_recipient_company_name', $company_name);
                $sub_order->update_meta_data('greeting', $greeting);

                $product_id = $item->get_product_id();
                $quantity   = $item->get_quantity();
                $product    = wc_get_product($product_id);

                if ($product) {
                    $order_item = new WC_Order_Item_Product();
                    $order_item->set_product($product);
                    $order_item->set_quantity($quantity);
                    $order_item->set_subtotal(0);
                    $order_item->set_total(0);

                    // Set item-level metadata
                    $order_item->update_meta_data('_recipient_recipient_id', $recipient_id);
                    $order_item->update_meta_data('_recipient_company_name', $company_name);
                    $order_item->update_meta_data('greeting', $greeting);

                    $sub_order->add_item($order_item);
                }

                // Set shipping address
                $shipping_data = [
                    'first_name' => $custom_full_name ?: $billing_data['first_name'],
                    'last_name'  => '',
                    'address_1'  => $item->get_meta('_recipient_address_1', true) ?? '',
                    'address_2'  => $item->get_meta('_recipient_address_2', true) ?? '',
                    'city'       => $item->get_meta('_recipient_city', true) ?? '',
                    'state'      => $item->get_meta('_recipient_state', true) ?? '',
                    'postcode'   => $item->get_meta('_recipient_zipcode', true) ?? '',
                    'country'    => 'US',
                ];

                $sub_order->set_address($shipping_data, 'shipping');
                $sub_order->set_shipping_total(0);
                $sub_order->set_parent_id($order_id);
                $sub_order->calculate_totals();
                $sub_order->set_status('processing');
                $sub_order->save();

                // Update order reference in recipient table
                $wpdb->update(
                    $order_process_recipient_table,
                    ['order_id' => $sub_order->get_id()],
                    ['id' => $recipient_id]
                );

                // Insert into group recipient table
                $wpdb->insert($group_recipient_table, [
                    'user_id'           => $recipients->user_id ?? 0,
                    'recipient_id'      => $recipient_id,
                    'group_id'          => $group_id,
                    'order_id'          => $sub_order->get_id(),
                    'full_name'         => sanitize_text_field($custom_full_name),
                    'company_name'      => sanitize_text_field($recipients->company_name),
                    'address_1'         => sanitize_text_field($recipients->address_1),
                    'address_2'         => sanitize_text_field($recipients->address_2),
                    'city'              => sanitize_text_field($recipients->city),
                    'state'             => sanitize_text_field($recipients->state),
                    'zipcode'           => sanitize_text_field($recipients->zipcode),
                    'quantity'          => sanitize_text_field($recipients->quantity),
                    'verified'          => sanitize_text_field($recipients->verified),
                    'address_verified'  => sanitize_text_field($recipients->address_verified),
                    'visibility'        => 1,
                    'new'               => 0,
                    'update_type'       => 0,
                    'reasons'           => sanitize_text_field($recipients->reasons),
                    'greeting'          => sanitize_text_field($recipients->greeting),
                ]);

                OAM_COMMON_Custom::sub_order_error_log("Sub-order created for Main Order ID: $order_id | Sub Order ID: " . $sub_order->get_id());

                // 2-second delay before next sub-order creation
                sleep(2);
            }
        }
    }
}
new OAM_WC_CRON_Suborder();