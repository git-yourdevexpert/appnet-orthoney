<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_TRACKING_ORDER_CRON
{
    /**
     * Constructor to hook into Customer template loading.
     */
    public function __construct()
    {
        add_action('wp_ajax_oam_tracking_order_manual_upload', array($this, 'oam_tracking_order_manual_upload_callback'));
        add_action('wp_ajax_tracking_order_number_insert', array($this, 'tracking_order_number_insert_callback'));
        
        add_action('init', array($this, 'oam_tracking_file_every_3_hours'));
        add_action('tracking_order_file_for_every_3_hours', array($this, 'tracking_order_file_for_every_3_hours_callback'), 10, 1);
        add_action('tracking_order_insert_mapping', array($this, 'tracking_order_insert_mapping_callback'), 10, 1);
        add_action('update_wc_order_status', array($this, 'update_wc_order_status_callback'), 10, 1);
        
    }
    public function next_tracking_order() {
        global $wpdb;
        $table    = $wpdb->prefix . 'oh_tracking_order';
        $log_ctx = 'next_tracking_order';

        $next_file = $wpdb->get_row("SELECT id FROM $table WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
        OAM_COMMON_Custom::sub_order_error_log('Next file to process: ' . json_encode($next_file), $log_ctx);

        if ($next_file && !empty($next_file->id)) {
            $next_args = [
                'id'     => (int) $next_file->id,
                'offset' => 0
            ];

            // Check if same action is already scheduled
            $existing = as_next_scheduled_action(
                'tracking_order_insert_mapping',
                [$next_args],               // must match args shape exactly
                'tracking-order-group'
            );

            if (!$existing) {
                $action_id = as_schedule_single_action(
                    time() + 60,            // run in 1 minute
                    'tracking_order_insert_mapping',
                    [$next_args],           // wrap args inside array
                    'tracking-order-group'
                );

                OAM_COMMON_Custom::sub_order_error_log(
                    "[" . current_time('Y-m-d H:i:s') . "] Scheduled next chunk: action_id=$action_id args=" . json_encode($next_args),
                    $log_ctx
                );
            } else {
                OAM_COMMON_Custom::sub_order_error_log(
                    "[" . current_time('Y-m-d H:i:s') . "] Next chunk already scheduled for args=" . json_encode($next_args),
                    $log_ctx
                );
            }
        } else {
            OAM_COMMON_Custom::sub_order_error_log(
                "[" . current_time('Y-m-d H:i:s') . "] No pending file found to schedule next chunk",
                $log_ctx
            );

            $action_id = as_schedule_single_action(
                time() + 30,
                'update_wc_order_status',
                [],
                'tracking-order-group'
            );

            OAM_COMMON_Custom::sub_order_error_log(
                "[" . current_time('Y-m-d H:i:s') . "] Started updating WC order status action_id= $action_id",
                $log_ctx
            );
        }
    }


    public function update_wc_order_status_callback($offset = 0) {
        $chunk_size = 50; // Make sure this matches the send_mail function
        $results = OAM_ADMINISTRATOR_HELPER::update_wc_order_status_send_mail_callback(1, $offset);

        // Step 3: Schedule next batch only if this batch was full
        if (!empty($results)) {
            $next_offset = $results;
            as_schedule_single_action(
                time() + 60,
                'update_wc_order_status',
                [ $next_offset ],
                'tracking-order-group'
            );
        }
    }


    public function oam_tracking_order_manual_upload_callback() {
        global $wpdb;
        // check_ajax_referer('oam_ajax_nonce', '_ajax_nonce');

        $table = $wpdb->prefix . 'oh_tracking_order';
        $upload_dir = WP_CONTENT_DIR . '/uploads/fulfillment-reports/tracking-orders/';

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        if (!isset($_FILES['csv_file'])) {
            wp_send_json_error(['message' => '❌ No file uploaded']);
        }

        $file = $_FILES['csv_file'];
        $originalName = sanitize_file_name($file['name']);

        // 🔹 Check if originalName exists in DB
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE file_name = %s",
            $originalName
        ));

        if ($exists > 0) {
            // Add suffix to avoid duplication: filename-1.csv, filename-2.csv ...
            $fileInfo = pathinfo($originalName);
            $base     = $fileInfo['filename'];
            $ext      = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
            $i = 1;

            do {
                $newOriginalName = $base . '-' . $i . $ext;
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE file_name = %s",
                    $newOriginalName
                ));
                $i++;
            } while ($exists > 0);

            $originalName = $newOriginalName; // replace with new safe name
        }

        $newFileName  = time() . '_' . $originalName;
        $localPath    = $upload_dir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $localPath)) {
            wp_send_json_error(['message' => '❌ Failed to save uploaded file']);
        }

        $wpdb->insert(
            $table,
            [
                'file_name'         => $originalName,
                'updated_file_name' => $newFileName,
                'status'            => 'pending',
                'uploaded_type'     => 'manually'
            ],
            ['%s','%s','%s','%s']
        );

        $msg = "✅ Inserted new file entry: $originalName";

        OAM_COMMON_Custom::sub_order_error_log($msg, 'tracking-order-manual-upload');
        wp_send_json_success(['message' => $msg]);
    }

    public function tracking_order_insert_mapping_callback($args) {
        global $wpdb;
        $failed     = isset($args['failed']) ? (int)$args['failed'] : 0;
        $skipped    = isset($args['skipped']) ? (int)$args['skipped'] : 0;
        $not_exists = isset($args['not_exists']) ? (int)$args['not_exists'] : 0;
        $success    = isset($args['success']) ? (int)$args['success'] : 0;

        $table        = $wpdb->prefix . 'oh_tracking_order';
        $wc_jar_order = $wpdb->prefix . "oh_wc_jar_order";
        $upload_dir   = WP_CONTENT_DIR . '/uploads/fulfillment-reports/tracking-orders/';
        $log_ctx      = 'tracking_order_insert';
        $limit        = 100;

        // --- Resolve file + offset ---
        $file_id = isset($args['id']) ? absint($args['id']) : 0;
        $offset  = isset($args['offset']) ? max(0, absint($args['offset'])) : 0;

        if (!$file_id) {
            $pending = $wpdb->get_row("SELECT id, file_name, updated_file_name, status FROM $table WHERE status = 'pending' ORDER BY id ASC LIMIT 1");
            if (!$pending) {
                return; // nothing to do
            }
            $file_id = (int) $pending->id;
            $offset  = 0;
        }

        $tracking = $wpdb->get_row($wpdb->prepare(
            "SELECT id, file_name, updated_file_name, status FROM $table WHERE id = %d",
            $file_id
        ));
        if (!$tracking) {
            return;
        }

        // lock the file
        if ($tracking->status === 'pending' && $offset === 0) {
            $wpdb->update($table, ['status' => 'processing'], ['id' => $file_id], ['%s'], ['%d']);
        }

        if (!in_array($tracking->status, ['pending', 'processing'], true)) {
            return; // already done or failed
        }

        $file_name = !empty($tracking->updated_file_name) ? $tracking->updated_file_name : $tracking->file_name;
        $path      = rtrim($upload_dir, '/\\') . DIRECTORY_SEPARATOR . $file_name;

        if (!file_exists($path)) {
            $wpdb->update($table, ['status' => 'failed'], ['id' => $file_id], ['%s'], ['%d']);
            OAM_COMMON_Custom::sub_order_error_log("[" . current_time('Y-m-d H:i:s') . "] Missing file: $path\n", $log_ctx);
            return;
        }

        $handle = fopen($path, 'r');
        if (!$handle) {
            OAM_COMMON_Custom::sub_order_error_log("[" . current_time('Y-m-d H:i:s') . "] Unable to open file: $path\n", $log_ctx);
            return;
        }

        // --- Read header ---
        $header = fgetcsv($handle);
        if ($offset === 0) {
            $required_columns = [
                'TRACKINGNUMBER',
                'SHIPPINGCARRIERNAME',
                'TRACKINGURL',
                'ORDERSTATUS',
                'RECIPIENTNO',
                'JARNO'
            ];

            $missing_columns = array_diff($required_columns, $header);
            if (!empty($missing_columns)) {
                fclose($handle);
                $wpdb->update(
                    $table,
                    [
                        'status'  => 'Failed',
                        'reasons' => 'Missing required columns: ' . implode(', ', $missing_columns)
                    ],
                    ['id' => $file_id],
                    ['%s'],
                    ['%d']
                );

                OAM_COMMON_Custom::sub_order_error_log(
                    "[" . current_time('Y-m-d H:i:s') . "] Missing required columns: " . implode(', ', $missing_columns) . " in file: $path\n",
                    $log_ctx
                );
                return;
            }
        }

        // Prepare temp file for output (core fix: only append new data – never copy old lines)
        $temp_path = $path . '.tmp';
        if ($offset === 0) {
            $temp_handle = fopen($temp_path, 'w'); // first chunk: overwrite/create new
            $header[] = 'ProcessStatus';
            fputcsv($temp_handle, $header);
        } else {
            $temp_handle = fopen($temp_path, 'a'); // subsequent chunks: append only new rows
            // DO NOT write header again, DO NOT write skipped lines again
        }

        // Fast-forward input file only (don't duplicate lines in temp file)
        $skipped_lines = 0;
        while ($skipped_lines < $offset && ($row = fgetcsv($handle)) !== false) {
            $skipped_lines++;
            // Do NOT write skipped rows in the temp file again! Only process and append new rows.
        }

        // --- Process up to $limit rows ---
        $processed = 0;
        while ($processed < $limit && ($row = fgetcsv($handle)) !== false) {
            $processed++;

            $RECIPIENTNO         = isset($row[2]) ? trim($row[2]) : '';
            $JARNO               = isset($row[3]) ? trim($row[3]) : '';
            $Trackingnumber      = isset($row[4]) ? trim($row[4]) : '';
            $TrackingCompanyName = isset($row[5]) ? trim($row[5]) : '';
            $TrackingStatus      = isset($row[6]) ? trim($row[6]) : '';
            $TrackingURL         = isset($row[7]) ? trim($row[7]) : '';

            $row_status = 'Skipped';

            if (
                !empty($RECIPIENTNO) && 
                !empty($JARNO) &&
                !empty($Trackingnumber) && 
                !empty($TrackingCompanyName) && 
                !empty($TrackingURL) && 
                !empty($TrackingStatus)
            ) {
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) 
                        FROM $wc_jar_order 
                        WHERE recipient_order_id = %s 
                        AND jar_order_id = %s",
                        $RECIPIENTNO,
                        $JARNO
                    )
                );

                if ($exists != 0) {
                    $updated = $wpdb->update(
                        $wc_jar_order,
                        [
                            'tracking_no'      => $Trackingnumber,
                            'tracking_company' => $TrackingCompanyName,
                            'tracking_url'     => $TrackingURL,
                            'status'           => $TrackingStatus,
                        ],
                        [
                            'recipient_order_id' => $RECIPIENTNO,
                            'jar_order_id'       => $JARNO,
                        ]
                    );

                    if ($updated === false) {
                        $failed++;
                        $row_status = 'Failed';
                    } else {
                        $success++;
                        $row_status = 'Success';
                    }
                } else {
                    $not_exists++;
                    $row_status = 'Order Not Exists';
                }
            } else {
                $skipped++;
                $row_status = 'Skipped';
            }

            // Append status column and write only processed rows
            $row[] = $row_status;
            fputcsv($temp_handle, $row);
        }

        // Peek for more rows
        $has_more = (fgetcsv($handle) !== false);
        fclose($handle);
        fclose($temp_handle);

        if ($has_more) {
            $next_args = [
                'id'         => $file_id, 
                'offset'     => $offset + $processed,
                'failed'     => $failed,
                'success'    => $success,
                'not_exists' => $not_exists,
                'skipped'    => $skipped
            ];

            $existing = as_next_scheduled_action('tracking_order_insert_mapping', [$next_args], 'tracking-order-group');
            if (!$existing) {
                $action_id = as_schedule_single_action(
                    time() + 20,
                    'tracking_order_insert_mapping',
                    [$next_args],
                    'tracking-order-group'
                );
                OAM_COMMON_Custom::sub_order_error_log(
                    "[" . current_time('Y-m-d H:i:s') . "] Scheduled next chunk: action_id=$action_id args=" . json_encode($next_args) . "\n",
                    $log_ctx
                );
            }
            return;
        }

        // --- No more rows: finalize file ---
        if (file_exists($temp_path)) {
            unlink($path); 
            rename($temp_path, $path);
        }

        $reasons_html = "Success: {$success}, Failed: {$failed}, Order Not Exists: {$not_exists}, Skipped: {$skipped}";

        $wpdb->update(
            $table, 
            [
                'status'  => 'success',
                'reasons' => $reasons_html
            ], 
            ['id' => $file_id], 
            ['%s','%s'], 
            ['%d']
        );

        OAM_COMMON_Custom::sub_order_error_log(
            "[" . current_time('Y-m-d H:i:s') . "] Completed file_id=$file_id Results => $reasons_html \n",
            $log_ctx
        );

        // Schedule next pending file
        $this->next_tracking_order();
    }

    
    public function tracking_order_number_insert_callback() {
        // check_ajax_referer('oam_ajax_nonce', 'security'); // security check

        global $wpdb;
        $table_name   = $wpdb->prefix . "oh_tracking_order";
        $wc_jar_order = $wpdb->prefix . "oh_wc_jar_order";
        $reasons_html = '';
        

        $upload_dir = WP_CONTENT_DIR . '/uploads/fulfillment-reports/tracking-orders/';

        $filename      = sanitize_file_name($_POST['filename'] ?? '');
        $fileid        = intval($_POST['fileid'] ?? 0);
        $current_chunk = intval($_POST['current_chunk'] ?? 0);

        $failed  = intval($_POST['failed'] ?? 0);
        $skipped = intval($_POST['skipped'] ?? 0);
        $success = intval($_POST['success'] ?? 0);
        $not_exists = intval($_POST['not_exists'] ?? 0);
        $chunk_size    = 50; // rows per chunk

        if (empty($filename)) {
            wp_send_json_error(['message' => 'Filename not provided']);
        }

        $file_path = $upload_dir . $filename;
        if (!file_exists($file_path)) {
            wp_send_json_error(['message' => 'File not found at: ' . $file_path]);
        }

        // Load file into memory
        $rows = [];
        $ext  = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $rows = array_map('str_getcsv', file($file_path));
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                wp_send_json_error(['message' => 'PhpSpreadsheet not available']);
            }
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
            $rows = $spreadsheet->getActiveSheet()->toArray();
        } else {
            wp_send_json_error(['message' => 'Invalid file format']);
        }

        $total_rows = count($rows);
        if ($total_rows <= 1) {
            wp_send_json_error(['message' => 'File is empty or only has headers']);
        }

        // --- Validate required columns ---
        $header = $rows[0];
        $required_columns = [
            'TRACKINGNUMBER',
            'SHIPPINGCARRIERNAME',
            'TRACKINGURL',
            'ORDERSTATUS',
            'RECIPIENTNO',
            'JARNO'
        ];
        $missing_columns = array_diff($required_columns, $header);
        if (!empty($missing_columns)) {

            $wpdb->update(
                $table_name,
                [
                    'status'  => 'Failed',
                    'reasons' => 'Missing required columns: ' . implode(', ', $missing_columns)
                ],
                ['id' => $fileid],
                ['%s'],
                ['%d']
            );
            
            wp_send_json_error([
                'message' => 'Missing required columns: ' . implode(', ', $missing_columns)
            ]);
        }

        // --- Ensure ProcessStatus column ---
        if (!in_array('ProcessStatus', $header, true)) {
            $header[] = 'ProcessStatus';
            $rows[0]  = $header;
        }

        // --- Process rows ---
        $start = ($current_chunk * $chunk_size) + 1;
        $end   = min($start + $chunk_size - 1, $total_rows - 1);

        if ($start == 1) {
            $wpdb->update(
                $table_name,
                ['status' => 'processing'],
                ['id' => $fileid],
                ['%s'],
                ['%d']
            );
        }

        for ($i = $start; $i <= $end; $i++) {
            $row = $rows[$i] ?? [];
            if (empty($row)) {
                continue;
            }

            // Match header to row
            $assoc_row = array_combine($header, $row + array_fill(0, count($header), ''));

            $row_status = 'Skipped';
            if (
                !empty($assoc_row['TRACKINGNUMBER']) && 
                !empty($assoc_row['SHIPPINGCARRIERNAME']) &&
                !empty($assoc_row['TRACKINGURL']) && 
                !empty($assoc_row['ORDERSTATUS']) && 
                !empty($assoc_row['RECIPIENTNO']) && 
                !empty($assoc_row['JARNO'])
            ) {
                $exists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) 
                        FROM $wc_jar_order 
                        WHERE recipient_order_id = %s 
                        AND jar_order_id = %s",
                        $assoc_row['RECIPIENTNO'],
                        $assoc_row['JARNO']
                    )
                );
                
                if ($exists != 0) {
                    $updated = $wpdb->update(
                        $wc_jar_order,
                        [
                            'tracking_no'      => $assoc_row['TRACKINGNUMBER'],
                            'tracking_company' => $assoc_row['SHIPPINGCARRIERNAME'],
                            'tracking_url'     => $assoc_row['TRACKINGURL'],
                            'status'           => $assoc_row['ORDERSTATUS'],
                        ],
                        [
                            'recipient_order_id' => $assoc_row['RECIPIENTNO'],
                            'jar_order_id'       => $assoc_row['JARNO'],
                        ]
                    );

                    if ($updated === false) {
                        $failed++;
                        $row_status = 'Failed';
                    } else {
                        $success++;
                        $row_status = 'Success';
                    }
                }else{
                    $not_exists++;
                    $row_status = 'Not Exists';
                }
            } else {
                $skipped++;
                $row_status = 'Skipped';
            }

            // Update ProcessStatus column
            $rows[$i][array_search('ProcessStatus', $header)] = $row_status;
        }

        // --- Save updated rows back to file ---
        if ($ext === 'csv') {
            $fp = fopen($file_path, 'w');
            foreach ($rows as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
        } else {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            foreach ($rows as $rIndex => $row) {
                foreach ($row as $cIndex => $cell) {
                    $sheet->setCellValueByColumnAndRow($cIndex + 1, $rIndex + 1, $cell);
                }
            }
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, ucfirst($ext));
            $writer->save($file_path);
        }

        // --- Completion handling ---
        $reasons_html = "Success: {$success}, Failed: {$failed}, Order Not Exists: {$not_exists}, Skipped: {$skipped}";

        $next_chunk = $current_chunk + 1;
        $finished   = $end >= ($total_rows - 1);
        if ($finished) {
           
            $action_id = as_schedule_single_action(
                time() + 30,
                'update_wc_order_status',
                [],
                'tracking-order-group'
            );
            
            $wpdb->update(
                $table_name,
                [
                    'status'  => 'success',
                    'reasons' => $reasons_html
                ],
                ['id' => $fileid],
                ['%s'],
                ['%d']
            );
        }

        $progress = round(($end / ($total_rows - 1)) * 100);

        
        $action_id = as_schedule_single_action(
            time() + 30,
            'update_wc_order_status',
            [],
            'tracking-order-group'
        );
        

        wp_send_json_success([
            'next_chunk' => $next_chunk,
            'finished'   => $finished,
            'progress'   => $progress,
            'total_rows' => $total_rows - 1,
            'success' => $success,
            'failed' => $failed,
            'not_exists' => $not_exists,
            'skipped' => $skipped,

        ]);
    }


            
    public function oam_tracking_file_every_3_hours() {
        // Check if ActionScheduler is available
        if (!function_exists('as_next_scheduled_action')) {
            OAM_COMMON_Custom::sub_order_error_log('ActionScheduler not available', date('Y-m-d H:i:s'));
            return;
        }
        
        // Only schedule if not already scheduled
        if (!as_next_scheduled_action('tracking_order_file_for_every_3_hours')) {
            $scheduled = as_schedule_recurring_action(
                time(),                         
                3 * HOUR_IN_SECONDS,             // Interval (3 hours)
                'tracking_order_file_for_every_3_hours', // Hook name
                [],                              // No arguments
                'tracking-order-group'            // Optional group name
            );
            
            if ($scheduled) {
                OAM_COMMON_Custom::sub_order_error_log('3-hour recurring action scheduled successfully', 'tracking-order-3-hours');
            } else {
                OAM_COMMON_Custom::sub_order_error_log('Failed to schedule 3-hour recurring action', 'tracking-order-3-hours');
            }
        }
    }

    public function tracking_order_file_for_every_3_hours_callback() {
        global $wpdb;

        $log_ctx  = 'tracking-order-3-hours';
        $table    = $wpdb->prefix . 'oh_tracking_order';
        $upload_dir = WP_CONTENT_DIR . '/uploads/fulfillment-reports/tracking-orders/';

        OAM_COMMON_Custom::sub_order_error_log('Cron started: tracking_order_file_for_every_3_hours_callback', $log_ctx);
        OAM_COMMON_Custom::sub_order_error_log('Upload directory (target): ' . $upload_dir, $log_ctx);

        // Ensure local folder exists
        if (!file_exists($upload_dir)) {
            if (wp_mkdir_p($upload_dir)) {
                OAM_COMMON_Custom::sub_order_error_log('Created upload directory: ' . $upload_dir, $log_ctx);
            } else {
                OAM_COMMON_Custom::sub_order_error_log('Failed to create upload directory: ' . $upload_dir, $log_ctx);
                return;
            }
        } else {
            OAM_COMMON_Custom::sub_order_error_log('Upload directory exists', $log_ctx);
        }

        // Writability check
        if (!is_writable($upload_dir)) {
            OAM_COMMON_Custom::sub_order_error_log('Upload directory is not writable: ' . $upload_dir, $log_ctx);
            return;
        } else {
            OAM_COMMON_Custom::sub_order_error_log('Upload directory is writable', $log_ctx);
        }

        // Get files from SFTP
        $uploader = new Orthoney_SFTP_Uploader();
        OAM_COMMON_Custom::sub_order_error_log('Initialized Orthoney_SFTP_Uploader', $log_ctx);

        $files = $uploader->listFiles('Test');
        if (!$files || !is_array($files) || empty($files)) {
            OAM_COMMON_Custom::sub_order_error_log('No files found on SFTP', $log_ctx);
            $this->next_tracking_order();
            return;
        }

        OAM_COMMON_Custom::sub_order_error_log('Files reported by SFTP: ' . json_encode($files), $log_ctx);

        // Counters
        $processed = 0;
        $inserted  = 0;
        $updated   = 0;
        $skipped   = 0;
        $failed    = 0;

        foreach ($files as $file) {
            $originalName = $file['name'];
            $reportedTime = isset($file['last_modified']) ? $file['last_modified'] : '';

            // Check if file exists with same or newer file_time in DB before processing
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE file_name = %s AND file_time >= %s",
                    $originalName,
                    $reportedTime
                )
            );

            if ($exists > 0) {
                OAM_COMMON_Custom::sub_order_error_log("Skipping file as it exists with same or newer file_time: {$originalName}", $log_ctx);
                $skipped++;
                continue;
            }

            $processed++;
            OAM_COMMON_Custom::sub_order_error_log("Processing file: {$originalName} | last_modified: {$reportedTime}", $log_ctx);

            // Generate unique local file name
            $newFileName = time() . '_' . $originalName;
            $localPath   = $upload_dir . $newFileName;
            $remotePath  = 'Test/' . $originalName;

            // Acquire Filesystem handle via reflection (since getFilesystem is private)
            try {
                $refClass  = new \ReflectionClass($uploader);
                $refMethod = $refClass->getMethod('getFilesystem');
                $refMethod->setAccessible(true);
                $filesystem = $refMethod->invoke($uploader);
                OAM_COMMON_Custom::sub_order_error_log('Obtained SFTP filesystem handle', $log_ctx);
            } catch (\Throwable $e) {
                $failed++;
                OAM_COMMON_Custom::sub_order_error_log('Failed to obtain filesystem handle: ' . $e->getMessage(), $log_ctx);
                continue;
            }

            // Read remote file (try stream first)
            $content = null;
            try {
                if (method_exists($filesystem, 'readStream')) {
                    $stream = $filesystem->readStream($remotePath);
                    if ($stream === false || $stream === null) {
                        OAM_COMMON_Custom::sub_order_error_log('readStream returned no data, attempting read: ' . $remotePath, $log_ctx);
                    } else {
                        $content = stream_get_contents($stream);
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                        OAM_COMMON_Custom::sub_order_error_log('Read remote file via stream: ' . $remotePath, $log_ctx);
                    }
                }

                if ($content === null && method_exists($filesystem, 'read')) {
                    $content = $filesystem->read($remotePath);
                    if ($content === false || $content === null) {
                        OAM_COMMON_Custom::sub_order_error_log('Failed to read remote file with read(): ' . $remotePath, $log_ctx);
                    } else {
                        OAM_COMMON_Custom::sub_order_error_log('Read remote file via read(): ' . $remotePath, $log_ctx);
                    }
                }

                if ($content === null) {
                    $failed++;
                    OAM_COMMON_Custom::sub_order_error_log('Skipping file; unable to read from SFTP: ' . $remotePath, $log_ctx);
                    continue;
                }
            } catch (\Throwable $e) {
                $failed++;
                OAM_COMMON_Custom::sub_order_error_log('Exception while reading remote file ' . $remotePath . ': ' . $e->getMessage(), $log_ctx);
                continue;
            }

            // Save locally
            try {
                $bytes = file_put_contents($localPath, $content);
                if ($bytes === false) {
                    $failed++;
                    OAM_COMMON_Custom::sub_order_error_log('Failed to save local file: ' . $localPath, $log_ctx);
                    continue;
                }
                OAM_COMMON_Custom::sub_order_error_log('Saved local file: ' . $localPath . ' | bytes: ' . $bytes, $log_ctx);
            } catch (\Throwable $e) {
                $failed++;
                OAM_COMMON_Custom::sub_order_error_log('Exception writing local file ' . $localPath . ': ' . $e->getMessage(), $log_ctx);
                continue;
            }

            // DB upsert with file_time update
            try {
                $exists = (int) $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE file_name = %s", $originalName)
                );
                OAM_COMMON_Custom::sub_order_error_log('DB check for existing file_name "' . $originalName . '": ' . $exists, $log_ctx);

                if ($exists > 0) {
                    // Update with new saved filename and file_time
                    $res = $wpdb->update(
                        $table,
                        [
                            'updated_file_name' => $newFileName,
                            'file_time'        => $reportedTime,
                            'status'           => 'pending',
                            'uploaded_type'    => 'cron',
                        ],
                        ['file_name' => $originalName],
                        ['%s', '%s', '%s', '%s'],
                        ['%s']
                    );

                    if ($wpdb->last_error) {
                        $failed++;
                        OAM_COMMON_Custom::sub_order_error_log('DB update error for ' . $originalName . ': ' . $wpdb->last_error, $log_ctx);
                    } else {
                        $updated++;
                        OAM_COMMON_Custom::sub_order_error_log('DB updated for ' . $originalName . ' -> updated_file_name=' . $newFileName . ' | rows_affected=' . (int)$res, $log_ctx);
                    }
                } else {
                    // Insert new record with file_time
                    $res = $wpdb->insert(
                        $table,
                        [
                            'file_name'         => $originalName,
                            'updated_file_name' => $newFileName,
                            'file_time'         => $reportedTime,
                            'status'            => 'pending',
                            'uploaded_type'     => 'cron',
                        ],
                        ['%s', '%s', '%s', '%s', '%s']
                    );

                    if ($wpdb->last_error) {
                        $failed++;
                        OAM_COMMON_Custom::sub_order_error_log('DB insert error for ' . $originalName . ': ' . $wpdb->last_error, $log_ctx);
                    } else {
                        $inserted++;
                        OAM_COMMON_Custom::sub_order_error_log('DB inserted for ' . $originalName . ' -> updated_file_name=' . $newFileName . ' | insert_id=' . (int)$wpdb->insert_id, $log_ctx);
                    }
                }
            } catch (\Throwable $e) {
                $failed++;
                OAM_COMMON_Custom::sub_order_error_log('Exception during DB operation for ' . $originalName . ': ' . $e->getMessage(), $log_ctx);
                continue;
            }
        }

        $this->next_tracking_order();

        // Summary
        OAM_COMMON_Custom::sub_order_error_log(
            'Cron completed. Summary => processed=' . $processed . ', inserted=' . $inserted . ', updated=' . $updated . ', skipped=' . $skipped . ', failed=' . $failed,
            $log_ctx
        );
    }

}

// Initialize the class
new OAM_TRACKING_ORDER_CRON();
