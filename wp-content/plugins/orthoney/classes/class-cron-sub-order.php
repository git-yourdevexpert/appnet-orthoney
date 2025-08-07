<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_WC_CRON_Suborder
{
    public function __construct()
    {
        // Hook into Thank You to schedule the sub-order creation
        add_action('woocommerce_thankyou', array($this, 'queue_sub_order_creation'), 10, 1);
        // Hook for async processing via WP Cron
        add_action('oam_create_sub_orders_async', array($this, 'schedule_create_sub_order_cron_handler'), 10, 1);
        // Disable Processing mail temporarily
        add_filter('woocommerce_email_enabled_customer_processing_order', array($this, 'oam_disable_processing_email_temporarily'), 10, 2);
        // Order status is change then init CRON job.
        add_action('woocommerce_order_status_changed', array($this, 'oam_maybe_schedule_sub_order_on_status_change'), 10, 4);

        add_action('init', array($this, 'oam_schedule_remaining_order_update_every_3_hours'));
        add_action('remaining_order_update_for_every_3_hours', array($this, 'remaining_order_update_for_every_3_hours_callback'), 10, 1);
    }

    

    public function trigger_processing_by_order_id($order_id) {
        $order = wc_get_order($order_id);
    
        if (!$order) {
            OAM_COMMON_Custom::sub_order_error_log('Order not found for ID: ' . $order_id, 'scheduled-3-hours');
            return false;
        }
    
        // Change order status to processing if it isn't already
        if ($order->get_status() !== 'processing') {
            $order->update_status('processing');
            OAM_COMMON_Custom::sub_order_error_log('Order status changed to processing for order ID: ' . $order_id, 'scheduled-3-hours');
        }
    
        // Manually trigger the hook
        do_action('woocommerce_order_status_processing', $order_id, $order);
        OAM_COMMON_Custom::sub_order_error_log('Processing hook triggered for order ID: ' . $order_id, 'scheduled-3-hours');
    
        // Trigger email notification specifically
        WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order_id);
        OAM_COMMON_Custom::sub_order_error_log('Customer processing email triggered for order ID: ' . $order_id, 'scheduled-3-hours');
    
        OAM_COMMON_Custom::sub_order_error_log('Processing trigger completed successfully for order ID: ' . $order_id, 'scheduled-3-hours');
        return true;
    }

    public function oam_schedule_remaining_order_update_every_3_hours() {
        // Check if ActionScheduler is available
        if (!function_exists('as_next_scheduled_action')) {
            OAM_COMMON_Custom::sub_order_error_log('ActionScheduler not available', date('Y-m-d H:i:s'));
            return;
        }
        
        // Only schedule if not already scheduled
        if (!as_next_scheduled_action('remaining_order_update_for_every_3_hours')) {
            $scheduled = as_schedule_recurring_action(
                time(),                         
                3 * HOUR_IN_SECONDS,             // Interval (3 hours)
                'remaining_order_update_for_every_3_hours', // Hook name
                [],                              // No arguments
                'oam-sub-order-group'            // Optional group name
            );
            
            if ($scheduled) {
                
                OAM_COMMON_Custom::sub_order_error_log('3-hour recurring action scheduled successfully', 'scheduled-3-hours');
            } else {
                OAM_COMMON_Custom::sub_order_error_log('Failed to schedule 3-hour recurring action', 'scheduled-3-hours');
            }
        }
    }

    public function remaining_order_update_for_every_3_hours_callback() {
        // Log that the callback is being executed
        OAM_COMMON_Custom::sub_order_error_log('3-hour callback started', 'scheduled-3-hours');
        
        global $wpdb;
        
        // Improved query with better error handling
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "
                SELECT o.ID
                FROM {$wpdb->prefix}wc_orders o
                LEFT JOIN {$wpdb->prefix}oh_wc_order_relation rel ON o.ID = rel.wc_order_id
                WHERE rel.wc_order_id IS NULL
                AND o.status IN (%s, %s)
                AND o.type = %s
                ORDER BY o.ID ASC
                ",
                'wc-completed',
                'wc-processing',
                'shop_order'
            )
        );
        
        // Check for database errors
        if ($wpdb->last_error) {
            OAM_COMMON_Custom::sub_order_error_log('Database error: ' . $wpdb->last_error, 'scheduled-3-hours');
            return;
        }
        
        if (!empty($results)) {
            $order_count = count($results);
            $order_ids_string = implode(',', array_map('intval', $results));
            OAM_COMMON_Custom::sub_order_error_log("Processing {$order_count} orders: " . $order_ids_string, 'scheduled-3-hours');
            
            foreach ($results as $order_id) {
                // Add error handling for individual order processing
                try {
                //    $this->trigger_processing_by_order_id($order_id);
                    $this->schedule_create_sub_order_cron_handler($order_id);
                    OAM_COMMON_Custom::sub_order_error_log("Processed order ID: {$order_id}", 'scheduled-3-hours');
                } catch (Exception $e) {
                    OAM_COMMON_Custom::sub_order_error_log("Error processing order {$order_id}: " . $e->getMessage(), 'scheduled-3-hours');
                }
            }
        } else {
            OAM_COMMON_Custom::sub_order_error_log('No orders found to process in 3-hour callback', 'scheduled-3-hours');
        }
        
        OAM_COMMON_Custom::sub_order_error_log('3-hour callback completed', 'scheduled-3-hours');
    }

    // Optional: Method to manually trigger the callback for testing
    public function manual_trigger_3_hour_callback() {
        if (current_user_can('manage_options')) {
            $this->remaining_order_update_for_every_3_hours_callback();
            wp_die('Manual trigger completed. Check logs.');
        }
    }

    // Optional: Method to clear/reschedule the action
    public function reschedule_3_hour_action() {
        if (current_user_can('manage_options')) {
            // Clear existing scheduled actions
            as_unschedule_all_actions('remaining_order_update_for_every_3_hours', [], 'oam-sub-order-group');
            
            // Reschedule
            $this->oam_schedule_remaining_order_update_every_3_hours();
            
            wp_die('Action rescheduled successfully.');
        }
    }

    public function oam_maybe_schedule_sub_order_on_status_change($order_id, $from_status, $to_status, $order)
    {
        if (in_array($to_status, ['processing'])) {
            $hook = 'oam_create_sub_orders_async';
            $args = [$order_id];

            // Check if the action is already scheduled
            if (!as_next_scheduled_action($hook, $args)) {
                // Schedule it 30 seconds in the future
                as_schedule_single_action(time() + 30, $hook, $args, 'oam-sub-order-group');
            }
        }   
    }

    public function oam_disable_processing_email_temporarily($enabled, $order)
    {
        if (is_a($order, 'WC_Order')) {
            return (bool) $order->get_meta('_oam_suborders_ready');
        }
        return $enabled;
    }
    /**
     * Queue the sub-order creation (asynchronously)
     */
    public function queue_sub_order_creation($order_id)
    {
        if (!$order_id) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        global $wpdb;
        $wc_order_relation_table      = $wpdb->prefix . 'oh_wc_order_relation';
        $wc_order_id_exist = $wpdb->get_row($wpdb->prepare(
            "SELECT order_id FROM {$wc_order_relation_table} WHERE wc_order_id = %d",
            $order_id
        ));

        if (empty($wc_order_id_exist->order_id)){
    
            $formatted_count = OAM_COMMON_Custom::get_current_month_count();

            if (!empty($wc_order_id_exist->order_id)){
                $custom_order_id = $wc_order_id_exist->order_id;
            }

            $custom_check_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID')?: 0;
            if($custom_check_order_id != 0){
                $order->update_meta_data('_orthoney_OrderID', $custom_check_order_id);
            }else{
                $order->update_meta_data('_orthoney_OrderID', $formatted_count);
            }

            $order->update_meta_data('order_process_by', OAM_COMMON_Custom::old_user_id());
            $order->save();

            // Only schedule if order is NOT 'draft' or 'failed'
            if (in_array($order->get_status(), ['draft', 'failed'])) {
                return; // Do NOT schedule
            }

            $hook = 'oam_create_sub_orders_async';
            $args = [$order_id];

            // Check if the action is already scheduled
            if (!as_next_scheduled_action($hook, $args)) {
                // Schedule it 30 seconds in the future
                as_schedule_single_action(time() + 30, $hook, $args, 'oam-sub-order-group');
            }
        }
    }

    /**
     * The actual heavy sub-order processing
     */
   
    public function schedule_create_sub_order_cron_handler($order_id)
    {
        if (empty($order_id) || !is_numeric($order_id)) return;

        global $wpdb;

        $main_order = wc_get_order($order_id);
        if (!$main_order) return;

        // Define tables
        $order_process_table          = OAM_Helper::$order_process_table;
        $group_table                  = OAM_Helper::$group_table;
        $group_recipient_table        = OAM_Helper::$group_recipient_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $recipient_order_table        = OAM_Helper::$recipient_order_table;
        $wc_order_relation_table      = $wpdb->prefix . 'oh_wc_order_relation';
        $yith_wcaf_affiliates_table   = $wpdb->prefix . 'yith_wcaf_affiliates';
        $oh_wc_jar_order              = $wpdb->prefix . 'oh_wc_jar_order';
        $oh_jar_order_greeting        = $wpdb->prefix . 'oh_jar_order_greeting';

        OAM_COMMON_Custom::sub_order_error_log('Start create sub order: ' . $order_id, $order_id);

        // Identify order type and process_id
        $order_items    = $main_order->get_items();
        $single_order   = false;
        $process_id     = 0;
        $total_quantity = 0;

        foreach ($order_items as $item) {
            $quantity = (int) $item->get_quantity();
            $process_id = $item->get_meta('process_id', true) ?: $process_id;
            $single_order |= $item->get_meta('single_order', true) == 1;
            $total_quantity += $quantity;
        }

        if (!$process_id) return;

        $order_type = 'multi-recipient-order';
        
        $custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
        $wc_order_id_exist = $wpdb->get_row($wpdb->prepare(
            "SELECT order_id FROM {$wc_order_relation_table} WHERE wc_order_id = %d",
            $order_id
        ));

        if (!empty($wc_order_id_exist->order_id)){
            $custom_order_id = $wc_order_id_exist->order_id;
        }


        // Update order_process_table
        $wpdb->update(
            $order_process_table,
            ['order_type' => $order_type, 'order_id' => $order_id],
            ['id' => $process_id]
        );

        $wc_order_id_exist = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wc_order_relation_table} WHERE wc_order_id = %d",
            $order_id
        ));

        if (empty($wc_order_id_exist->id)){

            // Fetch process data
            $process_data = $wpdb->get_row($wpdb->prepare(
                "SELECT user_id, name, data FROM {$order_process_table} WHERE id = %d",
                $process_id
            ));

            if (!$process_data) return;

            $decoded_data     = json_decode($process_data->data ?? '', true);
            $affiliate        = $decoded_data['affiliate_select'] ?? 0;
            $affiliate_token  = $wpdb->get_var($wpdb->prepare("SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $affiliate));
            $affiliate_id     = $affiliate_token ? $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$yith_wcaf_affiliates_table} WHERE token = %s", $affiliate_token)) : 0;

            OAM_COMMON_Custom::sub_order_error_log("affiliate: {$affiliate}, token: {$affiliate_token}, 'order type: {$order_type}'", $order_id);

            if ($order_type !== 'multi-recipient-order') return;

            // Insert WC Order Relation
            $wpdb->insert($wc_order_relation_table, [
                'user_id'           => (int) $process_data->user_id,
                'wc_order_id'       => (int) $order_id,
                'order_id'          => sanitize_text_field($custom_order_id),
                'quantity'          => (int) $total_quantity,
                'order_type'        => $single_order ? 'single_address' : 'multi_address',
                'affiliate_code'    => sanitize_text_field($affiliate_token ?: 'Orthoney'),
                'affiliate_user_id' => (int) $affiliate_id,
            ]);

            // Ensure Group
            $group_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$group_table} WHERE order_id = %d AND pid = %d",
                $order_id,
                $process_id
            ));

            if (!$group_id) {
                $wpdb->insert($group_table, [
                    'user_id'    => $process_data->user_id,
                    'pid'        => $process_id,
                    'order_id'   => $order_id,
                    'visibility' => 1,
                    'name'       => sanitize_text_field($process_data->name),
                ]);
                $group_id = $wpdb->insert_id;
            }

            // Check recipient count
            $existing_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM {$group_recipient_table} WHERE group_id = %d",
                $group_id
            ));

            if ($existing_count === $total_quantity) return;

            $recipients = OAM_Helper::get_recipient_by_pid($process_id);
            if (empty($recipients)) return;

            $recipient_ids = wp_list_pluck($recipients, 'id');
            $placeholders  = implode(',', array_fill(0, count($recipient_ids), '%d'));

            // Update order_id in recipients
            $wpdb->query($wpdb->prepare(
                "UPDATE {$order_process_recipient_table} SET order_id = %s WHERE id IN ($placeholders)",
                array_merge([$custom_order_id], $recipient_ids)
            ));

            $recipient_rows = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$order_process_recipient_table} WHERE id IN ($placeholders)",
                $recipient_ids
            ));

            if (empty($recipient_rows)) return;

            $count = 0;
            foreach ($recipient_rows as $recipient) {
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$recipient_order_table} WHERE recipient_id = %d AND order_id = %s",
                    $recipient->id,
                    $custom_order_id
                ));
                if ($exists) continue;

                $count++;
                $sub_order_id = $custom_order_id . '-' . $count;

                // Insert recipient order
                $recipient_order_data = [
                    'ID'                 => $count,
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
                $wpdb->insert($recipient_order_table, $recipient_order_data);

                // Insert group recipient
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
                $wpdb->insert($group_recipient_table, $group_recipient_data);

                // Handle Jar Order Creation
                $jar_order_type = $recipient->quantity > 6 ? 'internal' : 'external';
                for ($i = 1; $i <= $recipient->quantity; $i++) {
                    $jar_id = $sub_order_id . '-' . $i;
                    $wpdb->insert($oh_wc_jar_order, [
                        'order_id'           => $custom_order_id,
                        'recipient_order_id' => $sub_order_id,
                        'jar_order_id'       => $jar_id,
                        'tracking_no'        => '',
                        'quantity'           => 1,
                        'order_type'         => $jar_order_type,
                        'status'             => ''
                    ]);
                    if ($jar_order_type === 'external' && $i === $recipient->quantity) {
                        $wpdb->insert($oh_jar_order_greeting, [
                            'order_id'           => $custom_order_id,
                            'recipient_order_id' => $sub_order_id,
                            'jar_order_id'       => $jar_id,
                            'greeting'           => $recipient->greeting,
                        ]);
                    }
                }

                if ($jar_order_type === 'internal') {
                    $wpdb->insert($oh_jar_order_greeting, [
                        'order_id'           => $custom_order_id,
                        'recipient_order_id' => $sub_order_id,
                        'jar_order_id'       => $sub_order_id . '-1',
                        'greeting'           => $recipient->greeting,
                    ]);
                }

                OAM_COMMON_Custom::sub_order_error_log("Sub-order created for Recipient ID: {$recipient->id} → Sub Order ID: {$sub_order_id}", $order_id);
            }

            // Finalize
            $main_order->update_meta_data('_oam_suborders_ready', 1);
            $main_order->save();

            WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger($order_id);

            OAM_COMMON_Custom::sub_order_error_log('End create sub order: ' . $order_id, $order_id);
        }
    }
}
new OAM_WC_CRON_Suborder();


// add_action('init', function () {
//     WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger(2459);
// }, 10, 2);