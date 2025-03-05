<?php 
function validate_and_upload_csv($file, $current_chunk) {
    $csv_dir = WP_CONTENT_DIR . '/upload_csv/';

    if ($current_chunk == 0 && isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        if (!file_exists($csv_dir)) {
            wp_mkdir_p($csv_dir);
        }

        $file_name = sanitize_file_name($file['name']);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        if (strtolower($file_extension) !== 'csv') {
            return ['success' => false, 'message' => 'Invalid file type. Only CSV files are allowed.'];
        }

        // Generate a unique file name with timestamp
        $unique_file_name = 'recipient_' . time() . substr(uniqid(), -8) . '.csv';
        $file_path = $csv_dir . '/' . $unique_file_name;

        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return ['success' => false, 'message' => 'Error saving the file on the server.'];
        }
    }

    // Ensure file exists before proceeding
    if (empty($file_path) || !file_exists($file_path) || !is_readable($file_path)) {
        return ['success' => false, 'message' => 'CSV file not found.'];
    }

    if (($handle = fopen($file_path, 'r')) !== false) {
        $header = fgetcsv($handle);
        $required_columns = ['first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'zipcode', 'quantity'];

        $missing_columns = array_diff($required_columns, $header);

        fclose($handle);

        if (!empty($missing_columns)) {
            return [
                'success' => false,
                'message' => 'Invalid CSV format. Missing required columns: ' . implode(', ', $missing_columns)
            ];
        }
    } else {
        return ['success' => false, 'message' => 'Unable to read the CSV file.'];
    }

    return ['success' => true, 'file_path' => $file_path];
}

// With XLSX file.
public static function validate_and_upload_csv($file, $current_chunk, $process_id, $method) {
    global $wpdb;

    $csv_dir = self::$all_uploaded_csv_dir;

    if ($current_chunk !== 0 || !isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Invalid file upload attempt.'];
    }

    // Ensure upload directory exists
    if (!file_exists($csv_dir)) {
        if (!wp_mkdir_p($csv_dir)) {
            return self::log_and_return(false, $method, $process_id, 'Failed to create upload directory.');
        }
    }

    // Check if directory is writable
    if (!is_writable($csv_dir)) {
        return self::log_and_return(false, $method, $process_id, 'Upload directory is not writable.');
    }

    // Check if temp file exists
    if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return self::log_and_return(false, $method, $process_id, 'Temporary file not found.');
    }

    $file_name = sanitize_file_name($file['name']);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_extension, ['csv', 'xlsx'])) {
        return self::log_and_return(false, $method, $process_id, 'Only CSV and XLSX files are allowed. Please upload a valid file.');
    }

    $unique_file_name = 'recipient_' . time() . substr(uniqid(), -8) . '.' . $file_extension;
    $file_path = trailingslashit($csv_dir) . $unique_file_name;

    // Attempt to move the uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        return self::log_and_return(false, $method, $process_id, 'File upload failed!');
    }

    // Confirm file exists after upload
    if (!file_exists($file_path)) {
        return self::log_and_return(false, $method, $process_id, 'File upload failed, file does not exist.');
    }

    $required_columns = ['first_name', 'last_name', 'address_1', 'city', 'state', 'zipcode', 'quantity'];

    if ($file_extension === 'csv') {
        // Validate CSV file structure
        if (($handle = fopen($file_path, 'r')) !== false) {
            $header = fgetcsv($handle);
            fclose($handle);

            $missing_columns = array_diff($required_columns, $header);
            if (!empty($missing_columns)) {
                return self::log_and_return(false, $method, $process_id, 'Invalid CSV format. Missing required columns: ' . implode(', ', $missing_columns), $file_path);
            }
        }
    } elseif ($file_extension === 'xlsx') {
        // Validate XLSX file structure using PhpSpreadsheet
        require_once ABSPATH . 'wp-load.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once 'vendor/autoload.php';

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file_path);
        $sheet = $spreadsheet->getActiveSheet();
        $header = $sheet->toArray()[0];

        $missing_columns = array_diff($required_columns, $header);
        if (!empty($missing_columns)) {
            return self::log_and_return(false, $method, $process_id, 'Invalid XLSX format. Missing required columns: ' . implode(', ', $missing_columns), $file_path);
        }
    }

    return self::log_and_return(true, $method, $process_id, 'File uploaded and validated.', $file_path);
}

// With XLSX file code
public function orthoney_insert_temp_recipient_ajax_handler() {
    check_ajax_referer('oam_nonce', 'security');
    global $wpdb;

    $recipient_table = OAM_Helper::$order_process_recipient_table;
    $order_process = OAM_Helper::$order_process_table;
    $recipient_dir = OAM_Helper::$process_recipients_csv_dir;
    
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    
    $chunk_size = 2;
    $current_chunk = isset($_POST['current_chunk']) ? intval($_POST['current_chunk']) : 0;
    $currentStep = isset($_POST['currentStep']) ? intval($_POST['currentStep']) : 0;
    $process_id = isset($_POST['pid']) ? sanitize_text_field($_POST['pid']) : '';
    $process_name = isset($_POST['csv_name']) ? sanitize_text_field($_POST['csv_name']) : 'Group ' . $process_id;
    
    // Retrieve existing CSV/XLSX file path if process exists
    $csv_name_query = $wpdb->get_var($wpdb->prepare("SELECT csv_name FROM {$order_process} WHERE id = %d LIMIT 1", $process_id));
    $file_path = $csv_name_query ? $recipient_dir . '/' . $csv_name_query : '';

    if ($current_chunk == 0) {
        if ($csv_name_query && file_exists($file_path)) {
            unlink($file_path);
        }
        $wpdb->delete($recipient_table, ['pid' => $process_id], ['%d']);
    }
    
    // File upload processing for the first chunk
    if ($current_chunk == 0 && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        if (!file_exists($recipient_dir)) {
            wp_mkdir_p($recipient_dir);
        }
        
        $file_ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        $unique_file_name = 'recipient_' . $process_id . '.' . $file_ext;
        $recipient_file_path = trailingslashit($recipient_dir) . $unique_file_name;

        if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $recipient_file_path)) {
            wp_send_json_error(['message' => 'Failed to move uploaded file.']);
            wp_die();
        }

        // Update database with the new file path
        $wpdb->update(
            $order_process,
            ['csv_name' => $unique_file_name, 'user_id' => get_current_user_id(), 'name' => $process_name],
            ['id' => $process_id]
        );
        
        $file_path = $recipient_file_path;
    }

    if (empty($file_path) || !file_exists($file_path) || !is_readable($file_path)) {
        wp_send_json_error(['message' => 'File not found.']);
        wp_die();
    }

    $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
    
    if ($file_ext === 'csv') {
        $handle = fopen($file_path, 'r');
        $header = fgetcsv($handle);
    } elseif ($file_ext === 'xlsx') {
        if (!class_exists('SimpleXLSX')) {
            require_once OH_PLUGIN_DIR_PATH . 'libs/SimpleXLSX.php';
        }
        $xlsx = SimpleXLSX::parse($file_path);
        if (!$xlsx) {
            wp_send_json_error(['message' => 'Invalid XLSX file.']);
            wp_die();
        }
        $header = $xlsx->rows()[0];
    } else {
        wp_send_json_error(['message' => 'Unsupported file type. Only CSV and XLSX are allowed.']);
        wp_die();
    }

    $required_columns = ['first_name', 'last_name', 'address_1', 'city', 'state', 'zipcode', 'quantity'];
    $missing_columns = array_diff($required_columns, $header);

    if (!empty($missing_columns)) {
        wp_send_json_error(['message' => 'Missing required columns: ' . implode(', ', $missing_columns)]);
        wp_die();
    }
    
    $total_rows = ($file_ext === 'csv') ? count(file($file_path)) - 1 : count($xlsx->rows()) - 1;

    $wpdb->query('START TRANSACTION');
    try {
        $processed_rows = 0;
        $error_rows = [];

        $rows = ($file_ext === 'csv') ? [] : $xlsx->rows();
        
        for ($i = 0; $i < $chunk_size; $i++) {
            $row = ($file_ext === 'csv') ? fgetcsv($handle) : ($rows[$current_chunk * $chunk_size + $i + 1] ?? false);
            if ($row === false) break;
            
            if (count($row) !== count($header)) {
                $error_rows[] = $current_chunk * $chunk_size + $processed_rows;
                continue;
            }
            
            $data = array_combine($header, $row);
            $failure_reasons = [];

            foreach ($required_columns as $field) {
                if ($field !== 'quantity' && empty($data[$field])) {
                    $failure_reasons[] = "Missing {$field}";
                } elseif ($field === 'quantity' && (!is_numeric($data[$field]) || $data[$field] <= 0)) {
                    $failure_reasons[] = "Invalid quantity value";
                }
            }

            $insert_data = [
                'user_id'     => get_current_user_id(),
                'pid'         => $process_id,
                'first_name'  => sanitize_text_field($data['first_name']),
                'last_name'   => sanitize_text_field($data['last_name']),
                'address_1'   => sanitize_textarea_field($data['address_1']),
                'city'        => sanitize_text_field($data['city']),
                'state'       => sanitize_text_field($data['state']),
                'zipcode'     => sanitize_text_field($data['zipcode']),
                'quantity'    => intval($data['quantity']),
                'verified'    => empty($failure_reasons) ? 1 : 0,
                'reasons'     => empty($failure_reasons) ? null : json_encode($failure_reasons),
            ];
            $wpdb->insert($recipient_table, $insert_data);
            $processed_rows++;
        }

        $wpdb->query('COMMIT');
        wp_send_json_success(['message' => "Chunk processed successfully", 'processed_rows' => $processed_rows]);
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
