<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_FULFILLMENT_DYNAMIC_REPORT
{
    /**
     * Define class Constructor
     **/
    public function __construct() {
        add_action('wp', array($this, 'orthoney_set_fulfillment_dynamic_report_callback'));
        add_action('orthoney_fulfillment_dynamic_report_event', array($this, 'orthoney_fulfillment_dynamic_report_event_callback'), 10, 2);
    }

    public function orthoney_set_fulfillment_dynamic_report_callback() {
        $season_start_date = get_field('season_start_date', 'option');
        $season_start_ts   = strtotime($season_start_date);

        $log_file = WP_CONTENT_DIR . '/uploads/fulfillment_log.txt';

        file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] SETUP: Started batch scheduling\n", FILE_APPEND);

        $current_batches = [];
        $batch_timestamps = [];

        // 🟡 If no batches in ACF
        if (!have_rows('fulfillment_batchs', 'option')) {
            $start_ts = strtotime('+1 day', current_time('timestamp'));
            $today_start_date = date('m/d/Y 00:00:00', $start_ts);
            $today_end_date   = date('m/d/Y 24:00:00', $start_ts);

            $args = [
                'start' => $today_start_date,
                'end'   => $today_end_date,
                'offset' => 0,
            ];

            if (!as_next_scheduled_action('orthoney_fulfillment_dynamic_report_event', [$args], 'batchs_fulfillment')) {
                as_schedule_recurring_action(
                    $start_ts,
                    DAY_IN_SECONDS,
                    'orthoney_fulfillment_dynamic_report_event',
                    [$args],
                    'batchs_fulfillment'
                );
                file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] Scheduled fallback batch: " . json_encode($args) . "\n", FILE_APPEND);
            }

            update_option('orthoney_fulfillment_scheduled_batches', []);
            file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] No ACF batches found — fallback batch scheduled\n", FILE_APPEND);
            return;
        }

        // ✅ Collect and sort ACF batches
        while (have_rows('fulfillment_batchs', 'option')) {
            the_row();
            $batch_date_str = get_sub_field('batch');
            $batch_date_ts  = strtotime($batch_date_str);
            if (!$batch_date_ts) continue;

            $current_batches[] = $batch_date_str;
            $batch_timestamps[$batch_date_str] = $batch_date_ts;
        }

        uasort($batch_timestamps, fn($a, $b) => $a <=> $b);
        $ordered_batches = array_keys($batch_timestamps);

        $last_batch_date_ts = 0;
        $previous_date_ts = $season_start_ts;

        foreach ($ordered_batches as $index => $batch_date_str) {
            $batch_date_ts = $batch_timestamps[$batch_date_str];
            $schedule_time = strtotime('-1 day', $batch_date_ts);

            $start_date = date('m/d/Y 00:00:00', $previous_date_ts);
            $end_date   = date('m/d/Y 24:00:00', $batch_date_ts);

            $args = [
                'start' => $start_date,
                'end'   => $end_date,
                'offset' => 0,
            ];

            if ($schedule_time > time()) {
                if (!as_has_scheduled_action('orthoney_fulfillment_dynamic_report_event', [$args], 'batchs_fulfillment')) {
                    as_schedule_single_action(
                        $schedule_time,
                        'orthoney_fulfillment_dynamic_report_event',
                        [$args],
                        'batchs_fulfillment'
                    );
                    file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] Scheduled batch: " . json_encode($args) . " | Execution: " . date('Y-m-d H:i:s', $schedule_time) . "\n", FILE_APPEND);
                }
            } else {
                file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] Skipped past batch: " . json_encode($args) . "\n", FILE_APPEND);
            }

            $previous_date_ts = $batch_date_ts;
            $last_batch_date_ts = max($last_batch_date_ts, $batch_date_ts);
        }

        // ✅ Fallback after last batch
        $now_ts = current_time('timestamp');
        if ($last_batch_date_ts > 0 && $last_batch_date_ts < $now_ts) {
            $daily_start_ts = strtotime('+1 day', $last_batch_date_ts);
            $today_start_date = date('m/d/Y 00:00:00', $daily_start_ts);
            $today_end_date   = date('m/d/Y 24:00:00', $daily_start_ts);

            $args = [
                'start' => $today_start_date,
                'end'   => $today_end_date,
                'offset' => 0,
            ];

            if (!as_next_scheduled_action('orthoney_fulfillment_dynamic_report_event', [$args], 'batchs_fulfillment')) {
                as_schedule_recurring_action(
                    $daily_start_ts,
                    DAY_IN_SECONDS,
                    'orthoney_fulfillment_dynamic_report_event',
                    [$args],
                    'batchs_fulfillment'
                );
                file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] Scheduled recurring fallback after last batch: " . json_encode($args) . "\n", FILE_APPEND);
            }
        }

        // ✅ Unschedule removed batches
        $previous_batches = get_option('orthoney_fulfillment_scheduled_batches', []);
        $removed_batches = array_diff($previous_batches, $current_batches);

        foreach ($removed_batches as $removed_batch_str) {
            $removed_batch_ts = strtotime($removed_batch_str);
            if ($removed_batch_ts) {
                $prev_key = array_search($removed_batch_str, $previous_batches);
                $prev_ts = ($prev_key > 0) ? strtotime($previous_batches[$prev_key - 1]) : $season_start_ts;

                $start_date = date('m/d/Y 00:00:00', $prev_ts);
                $end_date   = date('m/d/Y 24:00:00', $removed_batch_ts);

                $args = [
                    'start' => $start_date,
                    'end'   => $end_date,
                    'offset' => 0,
                ];

                as_unschedule_action(
                    'orthoney_fulfillment_dynamic_report_event',
                    [$args],
                    'batchs_fulfillment'
                );
                file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] Unscheduled removed batch: " . json_encode($args) . "\n", FILE_APPEND);
            }
        }

        // ✅ Unschedule recurring if batches are empty
        if (empty($current_batches)) {
            $scheduled = as_next_scheduled_action('orthoney_fulfillment_dynamic_report_event', [], 'batchs_fulfillment');
            if ($scheduled) {
                as_unschedule_all_actions('orthoney_fulfillment_dynamic_report_event', [], 'batchs_fulfillment');
                file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] No ACF batches — removed all recurring fulfillment actions\n", FILE_APPEND);
            }
        }

        update_option('orthoney_fulfillment_scheduled_batches', $current_batches);
        file_put_contents($log_file, "[" . current_time('Y-m-d H:i:s') . "] Updated batch list: " . implode(', ', $current_batches) . "\n", FILE_APPEND);
    }


    public function orthoney_fulfillment_dynamic_report_event_callback($args) {
        global $wpdb;
        $__start = microtime(true); // <--- Add this

        $log_dir = wp_upload_dir()['basedir'] . '/fulfillment-reports';
        $log_file = $log_dir . '/orthoney-cron-trigger.log';
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            chmod($log_dir, 0755);
        }

        $start  = sanitize_text_field($args['start'] ?? '');
        $end    = sanitize_text_field($args['end'] ?? '');
        $offset = (int) ($args['offset'] ?? 0);

        error_log(date('[Y-m-d H:i:s] ') . "ARGS: " . print_r($args, true) . PHP_EOL, 3, $log_file);
        error_log(date('[Y-m-d H:i:s] ') . "START: Fulfillment Batch | Start: $start | End: $end | Offset: $offset" . PHP_EOL, 3, $log_file);

        $start_date = DateTime::createFromFormat('m/d/Y H:i:s', trim($start));
        $end_date = DateTime::createFromFormat('m/d/Y H:i:s', trim($end));

        if (!$start_date || !$end_date || empty($start) || empty($end)) {
            $msg = "ERROR: Invalid start/end date or format.";
            error_log(date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, 3, $log_file);
            return false;
        }

        //
        $fulfillment_batchs = get_field('fulfillment_batchs', 'option');

        $one_based_index = -1; // Default if not found

        if ($fulfillment_batchs && have_rows('fulfillment_batchs', 'option')) {
            // Sanitize and get the 'end' date argument
            $end = isset($args['end']) ? sanitize_text_field($args['end']) : '';

            // Try to parse date from known formats
            $date_obj = false;
            if ($end) {
                $date_obj = DateTime::createFromFormat('Y-m-d', trim($end));
                if (!$date_obj) {
                    $date_obj = DateTime::createFromFormat('m/d/Y', trim($end));
                }
                if ($date_obj) {
                    $search_date = $date_obj->format('m/d/Y');

                    // Build array of formatted batch dates
                    $dates_array = array_map(
                        fn($b) => date("m/d/Y", strtotime($b['batch'])),
                        $fulfillment_batchs
                    );

                    // Find 0-based index and convert to 1-based, or -1 if not found
                    $found_index = array_search($search_date, $dates_array);
                    $one_based_index = ($found_index !== false) ? $found_index + 1 : -1;
                }
            }
        }
        error_log(date('[Y-m-d H:i:s] ') . "Batch index: $one_based_index" . PHP_EOL, 3, $log_file);
        $found_index = '';
        if($one_based_index == -1){
            
        }else{
            $found_index = $one_based_index;
        }
        
        error_log(date('[Y-m-d H:i:s] ') . "Found index: $found_index" . PHP_EOL, 3, $log_file);
        //

        $file_name_date_format = $start_date->format('mdY') . '-' . $end_date->format('mdY');
        $base_name = "batch{$found_index}-orders-fulfillment-" . $file_name_date_format;
        $upload_dir = wp_upload_dir();
        $custom_dir = $upload_dir['basedir'] . '/fulfillment-reports';
        $custom_url = $upload_dir['baseurl'] . '/fulfillment-reports';

        $start_str = $start_date->format('Y-m-d 00:00:00');
        $end_str = $end_date->format('Y-m-d 23:59:59');

        $limit = 200;
        $is_first_batch = ($offset === 0);

        if ($is_first_batch) {
            error_log(date('[Y-m-d H:i:s] ') . "First batch: generating new CSV files..." . PHP_EOL, 3, $log_file);

            $index = 1;
            do {
                $fulfillment_filename = "{$base_name}-{$index}.csv";
                $greetings_filename = "batch{$found_index}-greetings-per-jar-{$file_name_date_format}-{$index}.csv";
                $fulfillment_path = $custom_dir . '/' . $fulfillment_filename;
                $greetings_path = $custom_dir . '/' . $greetings_filename;
                $fulfillment_url = $custom_url . '/' . $fulfillment_filename;
                $greetings_url = $custom_url . '/' . $greetings_filename;
                $index++;
            } while (file_exists($fulfillment_path) || file_exists($greetings_path));

            $fulfillment_file = fopen($fulfillment_path, 'w');
            $greetings_file = fopen($greetings_path, 'w');

            if (!$fulfillment_file || !$greetings_file) {
                error_log(date('[Y-m-d H:i:s] ') . "ERROR: Failed to create CSV files at $fulfillment_path or $greetings_path" . PHP_EOL, 3, $log_file);
                return false;
            }

            error_log(date('[Y-m-d H:i:s] ') . "Writing CSV headers..." . PHP_EOL, 3, $log_file);

            fputcsv($fulfillment_file, [
                'Status', 'WC_OID', 'ORDERID', 'RECIPIENTNO', 'JARNO', 'JARQTY',
                'ORDERTYPE', 'VOUCHER', 'RECIPNAME', 'RECIPCOMPANY', 'RECIPADDRESS',
                'RECIPADDRESS 2', 'RECIPCITY', 'RECIPState', 'RECIPZIP', 'RECIPCountry',
                'Greeting', 'Bottom of card = In celebration of',
                'Organization name (stub)', 'Code', 'Organization name (front of card)'
            ]);

            fputcsv($greetings_file, [
                'WC_OID', 'ORDERID', 'RECIPIENTNO', 'JARNO', 'Greeting'
            ]);

            fclose($fulfillment_file);
            fclose($greetings_file);

            error_log(date('[Y-m-d H:i:s] ') . "Headers written. Files created: $fulfillment_filename & $greetings_filename" . PHP_EOL, 3, $log_file);
        } else {
            $fulfillment_path = $args['fulfillment_path'];
            $greetings_path = $args['greetings_path'];
            $fulfillment_url = $args['fulfillment_url'];
            $greetings_url = $args['greetings_url'];
            $fulfillment_filename = $args['filename'];

            error_log(date('[Y-m-d H:i:s] ') . "Continuing with existing files: $fulfillment_filename" . PHP_EOL, 3, $log_file);
        }

        // Log timed: Get total orders
        $t1 = microtime(true);
        $total_orders = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(ID) FROM {$wpdb->prefix}wc_orders
            WHERE status IN ('wc-completed', 'wc-processing')
            AND date_created_gmt BETWEEN %s AND %s
        ", $start_str, $end_str));
        $t2 = microtime(true);
        error_log(date('[Y-m-d H:i:s] ') . "Total orders found: $total_orders (Query time: " . round($t2 - $t1, 4) . "s)" . PHP_EOL, 3, $log_file);

        if ($total_orders == 0) {
            error_log(date('[Y-m-d H:i:s] ') . "INFO: No orders in range." . PHP_EOL, 3, $log_file);
            return true;
        }

        // Log timed: Get batch of order IDs
        $t1 = microtime(true);
        $order_ids = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$wpdb->prefix}wc_orders
            WHERE status IN ('wc-completed', 'wc-processing')
            AND date_created_gmt BETWEEN %s AND %s
            ORDER BY date_created_gmt ASC
            LIMIT %d OFFSET %d
        ", $start_str, $end_str, $limit, $offset));
        $t2 = microtime(true);
        error_log(date('[Y-m-d H:i:s] ') . "Fetched " . count($order_ids) . " order IDs for current batch (Query time: " . round($t2 - $t1, 4) . "s)" . PHP_EOL, 3, $log_file);

        if (empty($order_ids)) {
            error_log(date('[Y-m-d H:i:s] ') . "INFO: No more orders to process." . PHP_EOL, 3, $log_file);
            return true;
        }

        // Log timed: Fetch relation data
        $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));
        $query = $wpdb->prepare("
            SELECT o.ID AS wc_order_id, rel.order_id AS custom_order_id, rel.affiliate_code, rel.quantity
            FROM {$wpdb->prefix}wc_orders o
            INNER JOIN {$wpdb->prefix}oh_wc_order_relation rel ON o.ID = rel.wc_order_id
            WHERE o.ID IN ($placeholders)
            ORDER BY o.date_created_gmt ASC
        ", ...$order_ids);
        $t1 = microtime(true);
        $results = $wpdb->get_results($query, ARRAY_A);
        $t2 = microtime(true);
        error_log(date('[Y-m-d H:i:s] ') . "Fetched " . count($results) . " order relations (Query time: " . round($t2 - $t1, 4) . "s)" . PHP_EOL, 3, $log_file);

        if (empty($results)) {
            error_log(date('[Y-m-d H:i:s] ') . "INFO: No order relations found for this batch." . PHP_EOL, 3, $log_file);
            return true;
        }

        // Continue (no changes to logic from here on)
        $fulfillment_output = fopen($fulfillment_path, 'a');
        $greetings_output = fopen($greetings_path, 'a');

        if (!$fulfillment_output || !$greetings_output) {
            error_log(date('[Y-m-d H:i:s] ') . "ERROR: Failed to open CSVs for writing." . PHP_EOL, 3, $log_file);
            return false;
        }

        //error_log(date('[Y-m-d H:i:s] ') . "Writing data rows to CSV files..." . PHP_EOL, 3, $log_file);

        $t1 = microtime(true);
        error_log(date('[Y-m-d H:i:s] ') . "Writing data rows to CSV files..." . PHP_EOL, 3, $log_file);

        $exclude_coupon = defined('EXCLUDE_COUPON') ? EXCLUDE_COUPON : array();

        foreach ($results as $row) {
        try {
            $org_activate_status = 'New';
            $wc_order_id = $row['wc_order_id'];
            $affiliate_status = (int) OAM_COMMON_Custom::get_order_meta($wc_order_id, 'affiliate_account_status');

            $coupon_codes = OAM_AFFILIATE_Helper::get_applied_coupon_codes_from_order($wc_order_id);
            
            $coupons = array_filter(array_diff(explode(',', $coupon_codes), $exclude_coupon));

            $row['affiliate_code'] = trim($row['affiliate_code']);
            if ($row['affiliate_code'] === '' || strtolower($row['affiliate_code']) === 'orthoney') {
                $org_activate_status = 'Rep';
                $row['affiliate_code'] = 'Honey from the Heart';
                $row['affiliate_name'] = 'Honey from the Heart';
                $row['affiliate_full_card'] = get_field('honey_from_the_heart_gift_card', 'option') ?: '';
                $row['affiliate_full_card_name'] = 'In celebration of the New Year, a donation has been made in your name to ' . $row['affiliate_full_card'];
            } else {
                $affiliate_user_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT user_id FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE token = %s",
                    $row['affiliate_code']
                ));
                if ($affiliate_user_id) {

                    $org_activate_status = OAM_AFFILIATE_Helper::is_user_created_this_year($affiliate_user_id) ? 'New' : 'Rep';
                    $orgName = get_user_meta($affiliate_user_id, '_yith_wcaf_name_of_your_organization', true);
                    $row['affiliate_name'] = $orgName ?: '';
                    $row['affiliate_full_card'] = get_user_meta($affiliate_user_id, 'gift_card', true) ?: $orgName;
                    $row['affiliate_full_card_name'] = 'In celebration of the New Year, a donation has been made in your name to ' . $row['affiliate_full_card'];
                }
            }

            $recipient_rows = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}oh_recipient_order
                WHERE order_id = %d AND order_id != %d
                GROUP BY recipient_order_id
            ", $row['custom_order_id'], 0), ARRAY_A);

            foreach ($recipient_rows as $recipient) {
                $recipient['zipcode'] = strlen(trim($recipient['zipcode'])) == 4 ? '0' . trim($recipient['zipcode']) : trim($recipient['zipcode']);
                $greeting_text = $recipient['greeting'] ?? '';
                
                $recipient_greeting = OAM_ADMINISTRATOR_Helper::cleanup_pure_text($greeting_text);

                $recipient_qty = (int) $recipient['quantity'];
                $jar_query = $recipient_qty > 6 ? "GROUP BY recipient_order_id" : "GROUP BY jar_order_id";

                $jar_rows = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$wpdb->prefix}oh_wc_jar_order
                    WHERE recipient_order_id = %s AND order_id != %d
                    $jar_query
                ", $recipient['recipient_order_id'], 0), ARRAY_A);

                
                    foreach ($jar_rows as $jar) {
                        $line = [
                            ucwords(strtolower(trim($jar['order_type']))),
                            trim($wc_order_id),
                            trim($row['custom_order_id']),
                            trim($recipient['recipient_order_id']),
                            ($jar['order_type'] == 'external' ? trim($jar['jar_order_id']) : ''),
                            ($jar['order_type'] == 'external' ? 1 : trim($jar['quantity'])),
                            (!empty($coupons) ? 'Wholesale' : 'Retail'),
                            (!empty($coupons) ? trim(implode(', ', $coupons)) : ''),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($recipient['full_name']))),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($recipient['company_name']))),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($recipient['address_1']))),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($recipient['address_2']))),
                            trim($recipient['city']),
                            trim($recipient['state']),
                            trim($recipient['zipcode']),
                            trim($recipient['country']),
                            ($jar['order_type'] != 'external' ? '' : trim($recipient_greeting)),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_full_card_name']))),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_name']))),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_code']))),
                            OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_full_card']))),
                        ];
                        
                        fputcsv($fulfillment_output, array_map(fn($v) => mb_convert_encoding($v ?? '', 'UTF-8', 'auto'), $line));
                    }


                    $jar_order_rows = $wpdb->get_results($wpdb->prepare("
                        SELECT * FROM {$wpdb->prefix}oh_wc_jar_order
                        WHERE recipient_order_id = %s AND order_id != %d
                    ", $recipient['recipient_order_id'], 0), ARRAY_A);

                    foreach ($jar_order_rows as $jar) {
                        if ($jar['order_type'] == 'internal') {
                            $greeting_line = [
                                trim($wc_order_id),
                                trim($row['custom_order_id']),
                                trim($recipient['recipient_order_id']),
                                trim($jar['jar_order_id']),
                                trim($recipient_greeting),
                                OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_full_card_name']))),
                                OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_name']))),
                                OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_code']))),
                                OAM_ADMINISTRATOR_Helper::cleanup_pure_text(html_entity_decode(stripslashes($row['affiliate_full_card']))),
                            ];
                            fputcsv($greetings_output, array_map(fn($v) => mb_convert_encoding($v ?? '', 'UTF-8', 'auto'), $greeting_line));
                        }
                    }
                
                }
            } catch (Exception $e) {
                error_log(date('[Y-m-d H:i:s] ') . "ERROR processing order {$row['wc_order_id']}: " . $e->getMessage() . PHP_EOL, 3, $log_path);

                    $__end = microtime(true);
                    $__duration = round($__end - $__start, 4);
                    error_log(date('[Y-m-d H:i:s] ') . "ERROR processing order: {$__duration}s" . PHP_EOL, 3, $log_file);

                continue;
            }
        }
        $t2 = microtime(true);
        
        error_log(date('[Y-m-d H:i:s] ') . "Writing data rows done CSV files.. (Query time: " . round($t2 - $t1, 4) . "s)" . PHP_EOL, 3, $log_file);

        fclose($fulfillment_output);
        fclose($greetings_output);

        $processed = $offset + count($order_ids);
        error_log(date('[Y-m-d H:i:s] ') . "Batch completed: Processed $processed / $total_orders" . PHP_EOL, 3, $log_file);

        if ($processed < $total_orders) {
            $next_args = [
                'start' => $start,
                'end' => $end,
                'offset' => $processed,
                'fulfillment_path' => $fulfillment_path,
                'greetings_path' => $greetings_path,
                'fulfillment_url' => $fulfillment_url,
                'greetings_url' => $greetings_url,
                'filename' => $fulfillment_filename,
            ];

            as_schedule_single_action(time() + 15, 'orthoney_fulfillment_dynamic_report_event', [$next_args], 'batchs_fulfillment');
            error_log(date('[Y-m-d H:i:s] ') . "Scheduled next batch | Offset: $processed / $total_orders" . PHP_EOL, 3, $log_file);
        } else {

            error_log("=== SFTP Upload Started ===");

            $uploader = new Orthoney_SFTP_Uploader();

            $localFile  = $fulfillment_path;
            $remotePath = "Test/" . basename($localFile);

            $uploader->upload($localFile, $remotePath);

            $localgreetingsFile  = $greetings_path;
            $remotePath = "Test/" . basename($localgreetingsFile);

            $uploader->upload($localgreetingsFile, $remotePath);

            error_log("=== SFTP Upload Finished ===");

            error_log(date('[Y-m-d H:i:s] ') . "All batches complete. Fulfillment file: $fulfillment_url" . PHP_EOL, 3, $log_file);
        }

        $__end = microtime(true);
        $__duration = round($__end - $__start, 4);
        error_log(date('[Y-m-d H:i:s] ') . "Total execution time: {$__duration}s" . PHP_EOL, 3, $log_file);

        return true;
    }

}

new OAM_FULFILLMENT_DYNAMIC_REPORT();