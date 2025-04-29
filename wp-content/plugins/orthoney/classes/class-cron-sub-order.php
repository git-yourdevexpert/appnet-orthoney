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
        // add_action('create_recipient_order', array($this, 'create_recipient_order_handler'), 10, 4);

    }

    public function schedule_create_sub_order_cron_handler($order_id) {
        if (!$order_id) return;
    
        global $wpdb;
        
        $order_process_table     = OAM_Helper::$order_process_table;
        $group_table             = OAM_Helper::$group_table;
        $group_recipient_table   = OAM_Helper::$group_recipient_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $recipient_order_table   = OAM_Helper::$recipient_order_table;
    
        $main_order = wc_get_order($order_id);
        if (!$main_order) return;
    
        // Update Order Meta
        $formatted_count = OAM_COMMON_Custom::get_current_month_count();
        $main_order->update_meta_data('_orthoney_OrderID', $formatted_count);
        $main_order->update_meta_data('order_process_by', OAM_COMMON_Custom::old_user_id());
        $main_order->save();
    
        // Identify order type
        $order_items = $main_order->get_items();
        $single_order = false;
        $process_id = 0;
        $total_quantity = 0;
    
        foreach ($order_items as $item) {
            $quantity = (int) $item->get_quantity();
            $single_order_meta = $item->get_meta('single_order', true);
            $process_id = $item->get_meta('process_id', true) ?: 0;
    
            if (!empty($single_order_meta) && $single_order_meta == 1) {
                $single_order = true;
            }
    
            $total_quantity += $quantity;
        }
    
        $order_type = $single_order ? 'single-order' : 'multi-recipient-order';
    

        // Update order process table
        if ($process_id) {
            $wpdb->update(
                $order_process_table,
                ['order_type' => $order_type, 'order_id' => $order_id],
                ['id' => $process_id]
            );
        }
    
        if ($order_type !== 'multi-recipient-order') return;
    
        // Fetch Process Data
        $processData = $wpdb->get_row($wpdb->prepare(
            "SELECT user_id, name FROM {$order_process_table} WHERE id = %d",
            $process_id
        ));
        if (!$processData) return;
    
        // Ensure Group
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

    
        // Check Recipient Count
        $groupRecipientCount = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM {$group_recipient_table} WHERE group_id = %d",
            $group_id
        ));
    
        if ($groupRecipientCount === $total_quantity) {
            return; // No need to proceed if already matched
        }
        
    
        // Prepare Data
        $custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
        $affiliate_token = OAM_COMMON_Custom::get_order_meta($order_id, '_yith_wcaf_referral');
    
        $recipients = OAM_Helper::get_recipient_by_pid($process_id);
        if (empty($recipients)) return;

        

        $targetIds = wp_list_pluck($recipients, 'id');
        $placeholders = implode(',', array_fill(0, count($targetIds), '%d'));
    
        // Update order_id in order_process_recipient_table
        $wpdb->query($wpdb->prepare(
            "UPDATE {$order_process_recipient_table} SET order_id = %d WHERE id IN ($placeholders)",
            array_merge([$custom_order_id], $targetIds)
        ));
    
        // Re-fetch updated recipients
        $recipientResult = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$order_process_recipient_table} WHERE id IN ($placeholders)",
            $targetIds
        ));
    
        if (empty($recipientResult)) return;
        
        // Insert Sub-Orders
        $recipientCount = 0;
        foreach ($recipientResult as $recipient) {
            $recipientCount++;
            $sub_order_id = $custom_order_id . '-' . $recipientCount;
            
            // Prepare data for recipient_order_table
            $recipient_order_data = [
                'ID'                 => $recipientCount,
                'pid'                => $process_id,
                'created_by'         => OAM_COMMON_Custom::old_user_id(),
                'order_id'           => $custom_order_id,
                'recipient_id'       => $recipient->id,
                'recipient_order_id' => $sub_order_id,
                'affiliate_token'    => $affiliate_token ?? 'Orthoney',
                'country'            => 'US',
                'order_type'         => $order_type,
                'user_id'            => $recipient->user_id ?? 0,
                'full_name'          => sanitize_text_field($recipient->full_name),
                'company_name'       => sanitize_text_field($recipient->company_name),
                'address_1'          => sanitize_text_field($recipient->address_1),
                'address_2'          => sanitize_text_field($recipient->address_2),
                'city'               => sanitize_text_field($recipient->city),
                'state'              => sanitize_text_field($recipient->state),
                'zipcode'            => sanitize_text_field($recipient->zipcode),
                'greeting'           => sanitize_text_field($recipient->greeting),
                'quantity'           => sanitize_text_field($recipient->quantity),
                'address_verified'   => sanitize_text_field($recipient->address_verified),
            ];

            // Insert into recipient_order_table
            $insert_result = $wpdb->insert($recipient_order_table, $recipient_order_data);

            if ( false === $insert_result ) {
                OAM_COMMON_Custom::sub_order_error_log( 'Insert into recipient_order_table failed: ' . $wpdb->last_error );
            }else{
                $inserted_id = $wpdb->insert_id;
                OAM_COMMON_Custom::sub_order_error_log( 'Insert into recipient_order_table sucess: ' . $inserted_id );
            }


            // Prepare data for group_recipient_table
            $group_recipient_data = [
                'recipient_id'      => $recipient->id,
                'group_id'          => $group_id,
                'order_id'          => $sub_order_id,
                'visibility'        => 1,
                'new'               => 0,
                'update_type'       => 0,
                'verified'          => sanitize_text_field($recipient->verified),
                'reasons'           => sanitize_text_field($recipient->reasons),
                'user_id'           => $recipient->user_id ?? 0,
                'full_name'         => sanitize_text_field($recipient->full_name),
                'company_name'      => sanitize_text_field($recipient->company_name),
                'address_1'         => sanitize_text_field($recipient->address_1),
                'address_2'         => sanitize_text_field($recipient->address_2),
                'city'              => sanitize_text_field($recipient->city),
                'state'             => sanitize_text_field($recipient->state),
                'zipcode'           => sanitize_text_field($recipient->zipcode),
                'quantity'          => sanitize_text_field($recipient->quantity),
                'address_verified'  => sanitize_text_field($recipient->address_verified),
                'greeting'          => sanitize_text_field($recipient->greeting),
            ];

            // Insert into group_recipient_table
            $insert_result = $wpdb->insert($group_recipient_table, $group_recipient_data);

            if ( false === $insert_result ) {
                OAM_COMMON_Custom::sub_order_error_log( 'Insert into group_recipient_table failed: ' . $wpdb->last_error );
            }else{
                $inserted_id = $wpdb->insert_id;
                OAM_COMMON_Custom::sub_order_error_log( 'Insert into group_recipient_table sucess: ' . $inserted_id );
            }

    
            OAM_COMMON_Custom::sub_order_error_log("Sub-order created for Recipient ID: {$recipient->id} → Sub Order ID: {$sub_order_id}");
        }
    }
    
    
    // public function create_recipient_order_handler($order_id, $group_id, $process_id, $order_type, $quantity) {
        
    // }
    
    
}
new OAM_WC_CRON_Suborder();

// add_action('init', 'run_custom_suborder_handler');

// function run_custom_suborder_handler() {
//     if ( class_exists('OAM_WC_CRON_Suborder') ) {
//         $suborder = new OAM_WC_CRON_Suborder();
//         $suborder->create_recipient_order_handler(2453, 20933, 10000, 'multi-recipient-order', 'quantity');
//     }
// }
// http://appnet-orthoney.local/checkout/order-received/2453/?key=wc_order_jrpHvMQF1NweD