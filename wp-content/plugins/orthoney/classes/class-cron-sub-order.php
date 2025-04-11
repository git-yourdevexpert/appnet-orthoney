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
        add_action('create_sub_order_chunk', array($this, 'process_sub_order_chunk_handler'), 10, 4);
        // add_action('create_sub_order_chunk', 'process_sub_order_chunk_handler', 10, 4);
    }

    public function schedule_create_sub_order_cron_handler($order_id) {
        if (!$order_id) {
            return;
        }
    
        global $wpdb;
    
        $order_process_table     = OAM_Helper::$order_process_table;
        $group_table             = OAM_Helper::$group_table;
        $group_recipient_table   = OAM_Helper::$group_recipient_table;
    
        $main_order = wc_get_order($order_id);
        if (!$main_order) {
            return;
        }
    
        $main_order->update_meta_data('order_process_by', OAM_COMMON_Custom::old_user_id());
        $main_order->save();
    
        $order_items = $main_order->get_items();
        $order_items_count = count($order_items);
    
        $single_order = 0;
        $process_id   = 0;
        $recipient_id = 0;
    
        foreach ($order_items as $item) {
            $single_order_meta = $item->get_meta('single_order', true);
    
            if (!empty($single_order_meta) && $single_order_meta == 1) {
                $single_order = 1;
                $process_id   = $item->get_meta('process_id', true) ?: 0;
            } else {
                if ($item->get_meta('_recipient_order_type', true) != '') {
                    $single_order = $item->get_meta('_recipient_order_type', true);
                }
                if ($item->get_meta('_recipient_process_id', true) != '') {
                    $process_id = $item->get_meta('_recipient_process_id', true);
                }
                if ($item->get_meta('_recipient_recipient_id', true) != '') {
                    $recipient_id = $item->get_meta('_recipient_recipient_id', true);
                }
                if ($process_id != '') {
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
    
        $processData = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, name FROM {$order_process_table} WHERE id = %d",
            $process_id
        ));
    
        if (!$processData) {
            return;
        }
    
        $group_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$group_table} WHERE order_id = %d AND pid = %d",
            $order_id, $process_id
        ));
    
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
    
            // Only schedule if the recipients haven't been fully processed
            $item_ids = array_keys($order_items); // Actual WC_Order_Item IDs
            $chunks = array_chunk($item_ids, 5);
            $delay = 5;

            foreach ($chunks as $index => $chunk) {
                $scheduled_time = time() + $delay;

                wp_schedule_single_event(
                    $scheduled_time,
                    'create_sub_order_chunk',
                    [$order_id, $group_id, $process_id, $chunk]
                );

                OAM_COMMON_Custom::sub_order_error_log("Scheduled chunk $index at " . date('Y-m-d H:i:s', $scheduled_time));

                $delay += 10;
            }
        }
    }
    
    public function process_sub_order_chunk_handler($order_id, $group_id, $process_id, $item_ids) {
        global $wpdb;
    
        $order = wc_get_order($order_id);
        if (!$order) return;
    
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
    
        $billing_data = [ /* same as before */ ];
    
        foreach ($item_ids as $item_id) {
            $item = $order->get_item($item_id);
    
            if (!$item) continue;
    
            $recipient_id = $item->get_meta('_recipient_recipient_id', true);
            $company_name = $item->get_meta('_recipient_company_name', true);
            $greeting     = $item->get_meta('greeting', true);
    
            $recipient = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$order_process_recipient_table} WHERE id = %d",
                $recipient_id
            ));
    
            if ($recipient && $recipient->order_id == 0) {
                $sub_order = wc_create_order();
    
                // Set customer and billing info
                $customer_id = $order->get_customer_id();
                if ($customer_id) $sub_order->set_customer_id($customer_id);
                $sub_order->set_address($billing_data, 'billing');
                $sub_order->set_billing_email($billing_data['email']);
    
                // Add product
                $product_id = $item->get_product_id();
                $quantity   = $item->get_quantity();
                $product    = wc_get_product($product_id);
    
                if ($product) {
                    $order_item = new WC_Order_Item_Product();
                    $order_item->set_product($product);
                    $order_item->set_quantity($quantity);
                    $order_item->set_subtotal(0);
                    $order_item->set_total(0);
    
                    // Meta
                    $order_item->update_meta_data('_recipient_recipient_id', $recipient_id);
                    $order_item->update_meta_data('_recipient_company_name', $company_name);
                    $order_item->update_meta_data('greeting', $greeting);
    
                    $sub_order->add_item($order_item);
                }
    
                // Shipping
                $shipping_data = [
                    'first_name' => $item->get_meta('full_name', true) ?: $billing_data['first_name'],
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
    
                $wpdb->update(
                    $order_process_recipient_table,
                    ['order_id' => $sub_order->get_id()],
                    ['id' => $recipient_id]
                );
    
                $wpdb->insert($group_recipient_table, [
                    'user_id'           => $recipient->user_id ?? 0,
                    'recipient_id'      => $recipient_id,
                    'group_id'          => $group_id,
                    'order_id'          => $sub_order->get_id(),
                    'full_name'         => sanitize_text_field($item->get_meta('full_name', true)),
                    'company_name'      => sanitize_text_field($recipient->company_name),
                    'address_1'         => sanitize_text_field($recipient->address_1),
                    'address_2'         => sanitize_text_field($recipient->address_2),
                    'city'              => sanitize_text_field($recipient->city),
                    'state'             => sanitize_text_field($recipient->state),
                    'zipcode'           => sanitize_text_field($recipient->zipcode),
                    'quantity'          => sanitize_text_field($recipient->quantity),
                    'verified'          => sanitize_text_field($recipient->verified),
                    'address_verified'  => sanitize_text_field($recipient->address_verified),
                    'visibility'        => 1,
                    'new'               => 0,
                    'update_type'       => 0,
                    'reasons'           => sanitize_text_field($recipient->reasons),
                    'greeting'          => sanitize_text_field($recipient->greeting),
                ]);
    
                OAM_COMMON_Custom::sub_order_error_log("Chunk handler: Sub-order created for Item ID: $item_id → Sub Order ID: " . $sub_order->get_id());
            }else{
                OAM_COMMON_Custom::sub_order_error_log("Chunk handler: Sub-order exist  Item ID:". $recipient->order_id);
            }
        }
    }
    
}
new OAM_WC_CRON_Suborder();