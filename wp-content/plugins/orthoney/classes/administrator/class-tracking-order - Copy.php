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
        add_action('wp_ajax_tracking_order_number_insert', array($this, 'tracking_order_number_insert_callback'));
        add_action('init', array($this, 'oam_tracking_file_every_3_hours'));
        add_action('tracking_order_file_for_every_3_hours', array($this, 'tracking_order_file_for_every_3_hours_callback'), 10, 1);
    }

    
    public function tracking_order_number_insert_callback() {
        // check_ajax_referer('oam_ajax_nonce', 'security'); // security check

        global $wpdb;
        $table_name = $wpdb->prefix . "oh_tracking_order";

        $upload_dir = WP_CONTENT_DIR . '/uploads/fulfillment-reports/tracking-orders/';

        $filename      = sanitize_file_name($_POST['filename'] ?? '');
        $current_chunk = intval($_POST['current_chunk'] ?? 0);
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
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $rows = array_map('str_getcsv', file($file_path));
        } elseif (in_array($ext, ['xls', 'xlsx'])) {
            if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                // You need PhpSpreadsheet installed
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

        // Skip header row
        $start = ($current_chunk * $chunk_size) + 1;
        $end   = min($start + $chunk_size - 1, $total_rows - 1);

        for ($i = $start; $i <= $end; $i++) {
            $row = $rows[$i] ?? [];
            if (empty($row)) {
                continue;
            }

            // Example: first column is tracking number
            // $tracking_number = sanitize_text_field($row[0] ?? '');

            // if (!empty($tracking_number)) {
            //     $wpdb->insert(
            //         $table_name,
            //         [
            //             'updated_file_name' => $filename,
            //             'status'            => 'pending',
            //             'filetime'          => current_time('mysql'),
            //         ],
            //         ['%s','%s','%s']
            //     );
            // }
        }

        $next_chunk = $current_chunk + 1;
        $finished   = $end >= ($total_rows - 1);

        $progress = round(($end / ($total_rows - 1)) * 100);

        wp_send_json_success([
            'next_chunk' => $next_chunk,
            'finished'   => $finished,
            'progress'   => $progress,
            'total_rows' => $total_rows - 1
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

        $files = $uploader->listFiles();
        if (!$files || !is_array($files) || empty($files)) {
            OAM_COMMON_Custom::sub_order_error_log('No files found on SFTP', $log_ctx);
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
            $processed++;
            $originalName = $file['name'];
            $reportedTime = isset($file['last_modified']) ? $file['last_modified'] : '';
            OAM_COMMON_Custom::sub_order_error_log("Processing file: {$originalName} | last_modified: {$reportedTime}", $log_ctx);

            // Generate unique local file name
            $newFileName = time() . '_' . $originalName;
            $localPath   = $upload_dir . $newFileName;
            $remotePath  = 'wp-content/tracking-csv/' . $originalName;

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

            // DB upsert
            try {
                $exists = (int) $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE file_name = %s", $originalName)
                );
                OAM_COMMON_Custom::sub_order_error_log('DB check for existing file_name "' . $originalName . '": ' . $exists, $log_ctx);

                if ($exists > 0) {
                    // Update with new saved filename
                    $res = $wpdb->update(
                        $table,
                        [
                            'updated_file_name' => $newFileName,
                            'status'            => 'pending',
                            'uploaded_type'     => 'cron',
                        ],
                        ['file_name' => $originalName],
                        ['%s', '%s'],
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
                    // Insert new record
                    $res = $wpdb->insert(
                        $table,
                        [
                            'file_name'         => $originalName,
                            'updated_file_name' => $newFileName,
                            'status'            => 'pending',
                            'uploaded_type'     => 'cron',
                        ],
                        ['%s', '%s', '%s', '%s']
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

        // Summary
        OAM_COMMON_Custom::sub_order_error_log(
            'Cron completed. Summary => processed=' . $processed . ', inserted=' . $inserted . ', updated=' . $updated . ', skipped=' . $skipped . ', failed=' . $failed,
            $log_ctx
        );
    }

}

// Initialize the class
new OAM_TRACKING_ORDER_CRON();
