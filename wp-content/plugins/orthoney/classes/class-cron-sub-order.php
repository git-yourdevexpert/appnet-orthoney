<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_WC_CRON_Suborder {
    public function __construct() {
        // Hook into Thank You to schedule the sub-order creation
        add_action('woocommerce_thankyou', array($this, 'queue_sub_order_creation'), 10, 1);
        // Hook for async processing via WP Cron
        add_action('oam_create_sub_orders_async', array($this, 'schedule_create_sub_order_cron_handler'), 10, 1);
        // Disable Processing mail temporarily
        add_filter('woocommerce_email_enabled_customer_processing_order', array($this,'oam_disable_processing_email_temporarily'), 10, 2);
        // Order status is change then init CRON job.
        add_action('woocommerce_order_status_changed', array($this,'oam_maybe_schedule_sub_order_on_status_change'), 10, 4);
    }

    public function oam_maybe_schedule_sub_order_on_status_change($order_id, $from_status, $to_status, $order) {
        if (in_array($to_status, ['processing']) && !wp_next_scheduled('oam_create_sub_orders_async', [$order_id])) {
            wp_schedule_single_event(time() + 50, 'oam_create_sub_orders_async', [$order_id]);
        }
    }

    public function oam_disable_processing_email_temporarily($enabled, $order) {
        if (is_a($order, 'WC_Order')) {
            return (bool) $order->get_meta('_oam_suborders_ready');
        }
        return $enabled;
    }
    /**
     * Queue the sub-order creation (asynchronously)
     */
    public function queue_sub_order_creation($order_id) {
        if (!$order_id) return;

       $order = wc_get_order($order_id);
        if (!$order) return;

        $formatted_count = OAM_COMMON_Custom::get_current_month_count();
        $order->update_meta_data('_orthoney_OrderID', $formatted_count);
        $order->update_meta_data('order_process_by', OAM_COMMON_Custom::old_user_id());
        $order->save();
    
        // Only schedule if order is NOT 'draft' or 'failed'
        if (in_array($order->get_status(), ['draft', 'failed'])) {
            return; // Do NOT schedule
        }
    
        if (!wp_next_scheduled('oam_create_sub_orders_async', [$order_id])) {
            wp_schedule_single_event(time() + 50, 'oam_create_sub_orders_async', [$order_id]);
        }
    }

    /**
     * The actual heavy sub-order processing
     */
    public function schedule_create_sub_order_cron_handler($order_id) {
        if (empty($order_id) || !is_numeric($order_id)) return;

        OAM_COMMON_Custom::sub_order_error_log('Start create sub order: ' . $order_id, $order_id);
        global $wpdb;
        
        $order_process_table = OAM_Helper::$order_process_table;
        $group_table = OAM_Helper::$group_table;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $recipient_order_table = OAM_Helper::$recipient_order_table;
        $wc_order_relation_table = $wpdb->prefix . 'oh_wc_order_relation';
        $yith_wcaf_affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';
        $oh_wc_jar_order = $wpdb->prefix . 'oh_wc_jar_order';
        $oh_jar_order_greeting = $wpdb->prefix . 'oh_jar_order_greeting';

        $main_order = wc_get_order($order_id);
        if (!$main_order) return;

        // Update Order Meta
        // $formatted_count = OAM_COMMON_Custom::get_current_month_count();
        // $main_order->update_meta_data('_orthoney_OrderID', $formatted_count);
        // $main_order->update_meta_data('order_process_by', OAM_COMMON_Custom::old_user_id());
        // $main_order->save();

        // Identify order type
        $order_items = $main_order->get_items();
        $single_order = false;
        $process_id = 0;
        $total_quantity = 0;

        foreach ($order_items as $item) {
            $quantity = (int) $item->get_quantity();
            $single_order_meta = $item->get_meta('single_order', true);
    
            if($item->get_meta('process_id', true)  != 0 OR $item->get_meta('process_id', true) != ''){
                $process_id = $item->get_meta('process_id', true);
            }

            if (!empty($single_order_meta) && $single_order_meta == 1) {
                $single_order = true;
            }

            $total_quantity += $quantity;
        }

        $order_type = $single_order ? 'single-order' : 'multi-recipient-order';

         // Prepare data
         $custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
        //  $affiliate_token = OAM_COMMON_Custom::get_order_meta($order_id, '_yith_wcaf_referral');

        $result = $wpdb->get_row($wpdb->prepare("SELECT data FROM {$order_process_table} WHERE order_id = %d", $order_id));
        

        $json_data = $result->data ?? '';

        $decoded_data = json_decode($json_data, true);

        $affiliate = !empty($decoded_data['affiliate_select']) ? $decoded_data['affiliate_select'] : 0;

        $affiliate_token = $wpdb->get_var($wpdb->prepare("SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $affiliate));

        OAM_COMMON_Custom::sub_order_error_log('affiliate_token from process table: ' . $affiliate_token, $order_id);

        $affiliate_id = '';
        if($affiliate_token != ''){
            $affiliate_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$yith_wcaf_affiliates_table} WHERE token = %s",
                $affiliate_token
            ));
        }
    
        // Update order_process_table
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

        $wpdb->insert(
            $wc_order_relation_table,
            [
                'user_id'            => (int) $processData->user_id,
                'wc_order_id'        => (int) $order_id,
                'order_id'           => sanitize_text_field($custom_order_id),
                'quantity'           => (int) $total_quantity,
                'order_type'         => sanitize_text_field($order_type === 'single-order' ? 'single_address' : 'multi_address'),
                'affiliate_code'     => sanitize_text_field($affiliate_token ?: 'Orthoney'),
                'affiliate_user_id'  => (int) ($affiliate_id !== '' ? $affiliate_id : 0),
            ],
            [
                '%d', // user_id
                '%d', // wc_order_id
                '%s', // order_id
                '%d', // quantity
                '%s', // order_type
                '%s', // affiliate_code
                '%d', // affiliate_user_id
            ]
        );
        

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
            return; // Already processed
        }

       

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

            $recipientExist = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$recipient_order_table} WHERE recipient_id = %d AND order_id = %s",
                $recipient->id,
                $custom_order_id
            ));

            if($recipientExist) return;

            $recipientCount++;
            $sub_order_id = $custom_order_id . '-' . $recipientCount;

            // Insert into recipient_order_table
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

            $insert_result = $wpdb->insert($recipient_order_table, $recipient_order_data);

            if (false === $insert_result) {

                OAM_COMMON_Custom::sub_order_error_log('Insert into recipient_order_table failed: ' . $wpdb->last_error, $order_id);
            } else {
                $inserted_id = $wpdb->insert_id;
                OAM_COMMON_Custom::sub_order_error_log('Insert into recipient_order_table success: ' . $inserted_id, $order_id);
            }

            // Insert into group_recipient_table
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

            $insert_result = $wpdb->insert($group_recipient_table, $group_recipient_data);

            if (false === $insert_result) {
                OAM_COMMON_Custom::sub_order_error_log('Insert into group_recipient_table failed: ' . $wpdb->last_error, $order_id);
            } else {
                $inserted_id = $wpdb->insert_id;
                OAM_COMMON_Custom::sub_order_error_log('Insert into group_recipient_table success: ' . $inserted_id, $order_id);
            }


            if($recipient->quantity > 6){
                $jar_order_type = 'internal';

                $jar_order_data = [
                    'order_id'           => $custom_order_id,
                    'recipient_order_id' => $sub_order_id,
                    'jar_order_id'       => $sub_order_id.'-1',
                    'tracking_no'        => '',
                    'quantity'           => $recipient->quantity,
                    'order_type'         => $jar_order_type,
                    'status'             => ''
                ];

                $jar_greeting_data = [
                    'order_id'           => $custom_order_id,
                    'recipient_order_id' => $sub_order_id,
                    'jar_order_id'       => $sub_order_id.'-1',
                    'greeting'           => $recipient->greeting,
                ];

                $insert_result = $wpdb->insert($oh_wc_jar_order, $jar_order_data);
               
                $insert_result = $wpdb->insert($oh_jar_order_greeting, $jar_greeting_data);

            }else{
                $jar_order_type = 'external';

                for ($i=1; $i <= $recipient->quantity; $i++) { 

                    $jar_order_data = [
                        'order_id'           => $custom_order_id,
                        'recipient_order_id' => $sub_order_id,
                        'jar_order_id'       => $sub_order_id.'-'.$i,
                        'tracking_no'        => '',
                        'quantity'           => 1,
                        'order_type'         => $jar_order_type,
                        'status'             => ''
                    ];
                    $insert_result = $wpdb->insert($oh_wc_jar_order, $jar_order_data);
                }

                $jar_greeting_data = [
                    'order_id'           => $custom_order_id,
                    'recipient_order_id' => $sub_order_id,
                    'jar_order_id'       => $sub_order_id.'-'.$i,
                    'greeting'           => $recipient->greeting,
                ];

                $insert_result = $wpdb->insert($oh_jar_order_greeting, $jar_greeting_data);

            }

            OAM_COMMON_Custom::sub_order_error_log("Sub-order created for Recipient ID: {$recipient->id} → Sub Order ID: {$sub_order_id}", $order_id);
        }

        // ✅ Now mark email allowed & trigger it
        $main_order->update_meta_data('_oam_suborders_ready', 1);
        $main_order->save();

        WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger($order_id);

        OAM_COMMON_Custom::sub_order_error_log('End create sub order: ' . $order_id, $order_id);
    }

}
new OAM_WC_CRON_Suborder();


// add_action('init', function () {
//     WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger(2459);
// }, 10, 2);