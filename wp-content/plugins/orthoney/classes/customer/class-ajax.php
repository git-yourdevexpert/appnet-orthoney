<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;


class OAM_Ajax{
    
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
    
        // Done 
        add_action( 'wp_ajax_orthoney_order_process_ajax', array( $this, 'orthoney_order_process_ajax_handler' ) );
        add_action( 'wp_ajax_orthoney_order_step_process_ajax', array( $this, 'orthoney_order_step_process_ajax_handler' ) );
        add_action( 'wp_ajax_orthoney_insert_temp_recipient_ajax', array( $this, 'orthoney_insert_temp_recipient_ajax_handler' ) );
        
        add_action( 'wp_ajax_save_group_recipient_to_order_process', array( $this, 'orthoney_save_group_recipient_to_order_process_handler' ) );

        add_action( 'wp_ajax_orthoney_get_csv_recipient_ajax', array( $this, 'orthoney_get_csv_recipient_ajax_handler') );
        add_action( 'wp_ajax_orthoney_single_address_data_save_ajax', array( $this, 'orthoney_single_address_data_save_ajax_handler') );
		add_action( 'wp_ajax_manage_recipient_form', array( $this, 'orthoney_manage_recipient_form_handler' ) );
		add_action( 'wp_ajax_download_failed_recipient', array( $this, 'orthoney_download_failed_recipient_handler'));

		add_action( 'wp_ajax_deleted_recipient', array( $this, 'orthoney_deleted_recipient_handler' ) );
		add_action( 'wp_ajax_bulk_deleted_recipient', array( $this, 'orthoney_bulk_deleted_recipient_handler' ) );
		add_action( 'wp_ajax_get_recipient_base_id', array( $this, 'orthoney_get_recipient_base_id_handler' ) );

		add_action( 'wp_ajax_reverify_address_recipient', array( $this, 'orthoney_reverify_address_recipient_handler' ) );

        add_action( 'wp_ajax_orthoney_order_step_process_completed_ajax', array( $this, 'orthoney_order_step_process_completed_ajax_handler') );
        
        add_action( 'wp_ajax_edit_process_name', array( $this, 'orthoney_edit_process_name_handler' ) );
        add_action( 'wp_ajax_keep_this_and_delete_others_recipient', array( $this, 'orthoney_keep_this_and_delete_others_recipient_handler' ) );

        add_action( 'wp_ajax_affiliate_status_toggle_block', array( $this, 'orthoney_affiliate_status_toggle_block_handler' ) );
        
        add_action('wp_ajax_search_affiliates', array( $this,'search_affiliates_handler'));

        add_action( 'wp_ajax_orthoney_incomplete_order_process_ajax', array( $this, 'orthoney_incomplete_order_process_ajax_handler' ) );
        add_action( 'wp_ajax_orthoney_groups_ajax', array( $this, 'orthoney_groups_ajax_handler' ) );
        // Done
        
		add_action( 'wp_ajax_deleted_group', array( $this, 'orthoney_deleted_group_handler' ) );
        
        // TODO
        add_action( 'wp_ajax_orthoney_process_to_checkout_ajax', array( $this, 'orthoney_process_to_checkout_ajax_handler' ) );
        add_action( 'wp_ajax_create_group', array( $this, 'orthoney_create_group_handler' ) );
        // TODO

    }

    /**
	 * AJAX handler function that edit process name
	 *
	 * @return JSON 
     * 
	 */ 
    public function add_items_to_cart($data, $product_id) {
        if (!function_exists('WC')) {
            die('WooCommerce is not loaded.');
        }
    
        if (!class_exists('WC_Cart') || WC()->cart === null) {
            return;
        }
    
        WC()->cart->empty_cart();
    
        foreach ($data as $customer) {
            $recipient_id    = sanitize_text_field($customer['recipient_id']);
            $process_id    = sanitize_text_field($customer['process_id']);
            $order_type    = sanitize_text_field('multi-recipient-order');
            $full_name    = sanitize_text_field($customer['full_name']);
            $company_name = sanitize_text_field($customer['company_name']);
            $address_1    = sanitize_text_field($customer['address_1']);
            $address_2    = sanitize_text_field($customer['address_2']);
            $city         = sanitize_text_field($customer['city']);
            $state        = sanitize_text_field($customer['state']);
            $zipcode      = sanitize_text_field($customer['zipcode']);
            $quantity     = (int) $customer['quantity'];
            $custom_price = 50; // Set custom price
    
            // Unique key to ensure separate cart items
            $unique_key = uniqid('custom_', true);

            $full_address    = sanitize_text_field($customer['address_1']) . ' ' . sanitize_text_field($customer['address_2']). ' ' . sanitize_text_field($customer['city']). ' ' . sanitize_text_field($customer['state']) . ' ' . sanitize_text_field($customer['zipcode']) ;
    
            // Add product to cart with custom data
            WC()->cart->add_to_cart($product_id, $quantity, 0, [], [
                'new_price'    => $custom_price, // Custom price
                'process_id'   => $process_id,
                'order_type'   => $order_type,
                'recipient_id' => $recipient_id,
                'full_name'    => $full_name,
                'company_name' => $company_name,
                'address'      => $full_address,
                'address_1'    => $address_1,
                'address_2'    => $address_2,
                'city'         => $city,
                'state'        => $state,
                'zipcode'      => $zipcode,
                'unique_key'   => $unique_key,
            ]);
        }
    }
    
    
    public function orthoney_save_group_recipient_to_order_process_handler() {
        check_ajax_referer('oam_nonce', 'security');
    
        global $wpdb;
        $status = true;
    
        $groups = $_POST['groups'] ?? '';
        $pid = $_POST['pid'] ?? '';
    
        if (empty($groups) || empty($pid)) {
            wp_send_json_error(['status' => false, 'message' => 'Missing parameters.']);
        }
    
        $group_recipient_table = OAM_Helper::$group_recipient_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $group_ids = array_map('intval', explode(',', $groups));
    
        if (!empty($group_ids)) {
            $wpdb->delete($order_process_recipient_table, ['pid' => intval($pid)], ['%d']);
    
            $placeholders = implode(',', array_fill(0, count($group_ids), '%d'));
            $query = $wpdb->prepare(
                "SELECT * FROM {$group_recipient_table} WHERE group_id IN ($placeholders)",
                ...$group_ids
            );
    
            $results = $wpdb->get_results($query);
    
            if (!empty($results)) {
                $user_id = get_current_user_id();
    
                foreach ($results as $data) {
                    $insert_data = [
                        'user_id'          => $user_id,
                        'pid'              => intval($pid),
                        'full_name'        => sanitize_text_field($data->full_name),
                        'company_name'     => sanitize_text_field($data->company_name),
                        'address_1'        => sanitize_textarea_field($data->address_1),
                        'address_2'        => sanitize_textarea_field($data->address_2),
                        'city'             => sanitize_text_field($data->city),
                        'state'            => sanitize_text_field($data->state),
                        'zipcode'          => sanitize_text_field($data->zipcode),
                        'quantity'         => max(1, intval($data->quantity ?? 1)),
                        'greeting'         => sanitize_textarea_field($data->greeting),
                        'verified'         => 1,
                        'address_verified' => 0,
                        'new'              => 0,
                        'reasons'          => null,
                    ];
    
                    $wpdb->insert($order_process_recipient_table, $insert_data);
                }
            }
        }
    
        wp_send_json_success(['status' => $status]);
    }
    
    
    public function orthoney_process_to_checkout_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $product_id = OAM_COMMON_Custom::get_product_id();
    
        $status      = isset($_POST['status']) ? $_POST['status'] : 1;
        $pid         = isset($_POST['pid']) ? intval($_POST['pid']) : 1; 
        $currentStep = isset($_POST['currentStep']) ? intval($_POST['currentStep']) + 1 : 1;
        $recipientIds = isset($_POST['recipientAddressIds']) ? $_POST['recipientAddressIds']  : [];
        $stepData    = $_POST ?? [];
        $user_id     = get_current_user_id(); 
        $placeholders = implode(',', array_fill(0, count($recipientIds), '%d'));
    
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $order_process_table = OAM_Helper::$order_process_table;
    
        $recipients = [];
        if($status == 1){
            $recipients = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$order_process_recipient_table} 
                    WHERE user_id = %d AND pid = %d AND visibility = %d AND verified = %d AND address_verified = %d AND id IN ($placeholders)",
                    array_merge([$user_id, $pid, 1, 1, 1], $recipientIds)
                )
            );
        }else{
            $recipients = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$order_process_recipient_table} 
                    WHERE user_id = %d AND pid = %d AND visibility = %d AND verified = %d AND id IN ($placeholders)",
                    array_merge([$user_id, $pid, 1, 1], $recipientIds)
                )
            );
        }
    
        $address_list = [];
    
        if(!empty( $recipients)){
            foreach ($recipients as $recipient) {
                $address_list[] = [
                    "recipient_id"   => $recipient->id,
                    "process_id"     => trim($pid) ?? '',
                    "full_name"      => trim($recipient->full_name) ?? '',
                    "company_name"   => trim($recipient->company_name) ?? '',
                    "address_1"      => trim($recipient->address_1) ?? '',
                    "address_2"      => trim($recipient->address_2) ?? '',
                    "city"           => trim($recipient->city) ?? '',
                    "state"          => trim($recipient->state) ?? '',
                    "zipcode"        => trim($recipient->zipcode) ?? '',
                    "quantity"       => $recipient->quantity ?? 1,
                ];
            }
        
            // Add items to the cart
            self::add_items_to_cart($address_list, $product_id);
        
            $processQuery = $wpdb->prepare("
            SELECT order_id
            FROM {$order_process_table}
            WHERE user_id = %d 
            AND id = %d 
            ", $user_id, $pid);

            $processResult = $wpdb->get_row($processQuery);
            
            if(!empty($processResult)){
                if($processResult->order_id != 0){
                    $order = wc_get_order( $processResult->order_id);
                    if ($order) {
                        $order_key = $order->get_order_key();
                        $order_url = wc_get_endpoint_url('view-order', $processResult->order_id, wc_get_page_permalink('myaccount')) . '?key=' . $order_key;
                        wp_send_json_success([
                            'message' => 'Please wait, the order is in progress.',
                            'checkout_url' => $order_url
                        ]);
                    }
                }
            }

            $updateData = [
                'data' => wp_json_encode($stepData),
                'order_type'  => sanitize_text_field('multi-recipient-order'),
                'step' => sanitize_text_field($currentStep),
            ];

            $wpdb->update(
                $order_process_table,
                $updateData,
                ['id' => $pid]
            );

            // Get checkout page URL
            $checkout_url = wc_get_checkout_url();

            wp_send_json_success([
            'message' => 'Please wait, the order is in progress.',
                'checkout_url' => $checkout_url
            ]);
        }else{
            wp_send_json_error([
                'message' => 'Failed to process the order. Please try again.',
                'checkout_url' => $checkout_url
            ]);
        }

    }
    
    
    /**
	 * AJAX handler function that edit process name
	 *
	 * @return JSON 
     * 
	 */ 
    public function orthoney_keep_this_and_delete_others_recipient_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $delete_ids = isset($_POST['delete_ids']) ? $_POST['delete_ids'] : '';
    
        if (!empty($delete_ids)) {
            // Convert string to an array
            $delete_ids_array = explode(',', $delete_ids);
            $delete_ids_array = array_map('intval', $delete_ids_array); // Ensure IDs are integers
    
            $recipient_table = OAM_Helper::$order_process_recipient_table;
    
            if (!empty($delete_ids_array)) {
                // Convert array to a comma-separated list for SQL query
                $placeholders = implode(',', array_fill(0, count($delete_ids_array), '%d'));
    
                // Prepare the SQL statement securely
                $sql = $wpdb->prepare(
                    "UPDATE $recipient_table SET visibility = 0 WHERE id IN ($placeholders)",
                    ...$delete_ids_array // Spread operator for passing array values dynamically
                );
    
                $result = $wpdb->query($sql);
    
                if ($result !== false) {
                    wp_send_json_success(['message' => 'Records updated successfully.']);
                } else {
                    wp_send_json_error(['message' => 'Error updating records.']);
                }
            }
        }
    
        wp_send_json_error(['message' => 'No valid IDs provided.']);
    }
    
    /**
	 * AJAX handler function that edit process name
	 *
	 * @return JSON 
     * 
	 */ 
    public function orthoney_edit_process_name_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $order_process_table   = OAM_Helper::$order_process_table;
        $group_table   = OAM_Helper::$group_table;

        $process_id  = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
        $name    = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : 'unknown_'.$process_id;
        $method    = isset($_POST['method']) ? $_POST['method'] : 'order-process';

        $check_process_status = OAM_COMMON_Custom::check_process_exist($name, $process_id);
        if ($check_process_status) {
            wp_send_json_error(['message' => 'The group name already exists. Please enter a different name.']);
        }

        if($method == 'order-process'){
    
            $result = $wpdb->update(
                $order_process_table,
                [
                    'name'     => sanitize_text_field($name),
                ],
                ['id' => $process_id]
            );
        }
        if($method == 'group'){
            $result = $wpdb->update(
                $group_table,
                [
                    'name'     => sanitize_text_field($name),
                ],
                ['id' => $process_id]
            );
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Recipient list name is not updated, so please try again.']);
        }
        
        wp_send_json_success(['message' => 'Recipient list name has been updated successfully.']);
        wp_die();
    }

    /**
	 * AJAX handler function that completes the order process, saves the order, and moves it to the checkout.
	 *
	 * @return JSON 
     * 
	 */ 
    public function orthoney_order_step_process_completed_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $order_process_table           = OAM_Helper::$order_process_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $duplicate  = isset($_POST['duplicate']) ? intval($_POST['duplicate']) : 1;
        
        $address_list = [];
        $address_ids = [];
        $process_id  = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
        $group_id    = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        $user        = get_current_user_id();
        $stepData    = $_POST ?? [];
        $currentStep = isset($_POST['currentStep']) ? intval($_POST['currentStep']) + 1 : 1;
    
        if ($process_id === 0) {
            wp_send_json_error(['message' => 'Invalid Process ID.']);
        }
    
        // Update order process table
        $result = $wpdb->update(
            $order_process_table,
            [
                'data' => wp_json_encode($stepData),
                'step' => sanitize_text_field($currentStep),
            ],
            ['id' => $process_id]
        );
    
        if ($result === false) {
            wp_send_json_error(['message' => 'Database update failed.']);
        }
    
        // Verify recipient addresses if group_id is provided
        if ($process_id) {
            $recipients = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, address_1, address_2, city, state, zipcode FROM {$order_process_recipient_table} 
                     WHERE user_id = %d AND pid = %d AND visibility = %d AND verified = %d",
                    $user, $process_id, 1, 1
                )
            );
        
            //
            // if($duplicate == 0){
            //     foreach ($recipients as $recipient) {
            //         // Create unique key for comparison
            //         $key = $recipient->full_name . '|' . 
            //                 str_replace($recipient->address_1, ',' , '' ). ' ' . str_replace($recipient->address_2 , ',' , '') . '|' . 
            //             $recipient->city . '|' . 
            //             $recipient->state . '|' . 
            //             $recipient->zipcode;
                    
            //         // Store record in the map
            //         if (!isset($recordMap[$key])) {
            //             $recordMap[$key] = [];
            //         }
            //         $recordMap[$key][] = $recipient;
            //     }
        
            //     foreach ($recordMap as $key => $records) {
            //         if (count($records) > 1) {
            //             // This is a group of duplicates
            //             $duplicateGroups[] = $records;
            //         } else {
            //             $address_list[] = $records;
            //         }
            //     }
            // }else{
            
                
            // }    

            foreach ($recipients as $recipient) {
                $street = trim(($recipient->address_1 ?? '') . ' ' . ($recipient->address_2 ?? ''));
                $address_list[] = [
                    "input_id"   => $recipient->id,
                    "street"     => $street ?? '',
                    "city"       => $recipient->city ?? '',
                    "state"      => $recipient->state ?? '',
                    "zipcode"    => $recipient->zipcode ?? '',
                    "candidates" => 10,
                ];
            }
            // Process addresses in chunks of 80
            $chunk_size = 80;
            $address_chunks = array_chunk($address_list, $chunk_size);
            $delay_time = 2; // Adjustable delay time in seconds
    
            foreach ($address_chunks as $index => $chunk) {
                // Call API with the current chunk
                $multi_validate_address = OAM_Helper::multi_validate_address($chunk);
    
                // Check if the API response is valid
                if (empty($multi_validate_address) || !is_string($multi_validate_address)) {
                    error_log("Address validation API failed or returned an invalid response.");
                    continue; // Skip this chunk if API response is invalid
                }
    
                $multi_validate_address_result = json_decode($multi_validate_address, true);
    
                if (!empty($multi_validate_address_result)) {
                    foreach ($multi_validate_address_result as $data) {
                        $pid = $data['input_id'];
                        $dpv_match_code = $data['analysis']['dpv_match_code'] ?? '';
    
                        if ($dpv_match_code !== 'N') {
                            $update_result = $wpdb->update(
                                $order_process_recipient_table,
                                ['address_verified' => intval(1)],
                                ['id' => $pid]
                            );
    
                            if ($update_result === false) {
                                error_log("Failed to update address verification for ID: $pid");
                            }
                        }
                    }
                }
    
                // Add delay between chunks (except after the last chunk)
                if ($index < count($address_chunks) - 1) {
                    sleep($delay_time);
                }
            }
        }
    
        wp_send_json_success(['message' => 'Process completed successfully.']);
        wp_die();
    }


    

    /**
	 * Ajax handle function that insert temp recipients
	 *
	 * @return JSON 
     * 
     * USE Helper function 
     * validate_and_upload_csv(): This function validates the CSV file and saves the CSV upload activity log.
     * 
	 */
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
        $process_id = isset($_POST['pid']) ? intval($_POST['pid']) : '';
        $process_name = (isset($_POST['csv_name']) && !empty($_POST['csv_name'])) ? sanitize_text_field($_POST['csv_name']) : 'unknown_' . $process_id;
        
        $check_process_status = OAM_COMMON_Custom::check_process_exist($process_name, $process_id);
        
        if ($check_process_status) {
            wp_send_json_error(['message' => 'The recipient list name already exists. Please enter a different name.']);
        }
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

             // Upload to /upload_csv/ first
             $result = OAM_Helper::validate_and_upload_csv($_FILES['csv_file'], $current_chunk, $process_id, 'order_process');
            
             
             if (!$result['success']) {
                 wp_send_json_error(['message' => $result['message']]);
                 wp_die();
             }
          
            
            $file_ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
            $unique_file_name = 'recipient_' . $process_id . '.' . $file_ext;
            $recipient_file_path = trailingslashit($recipient_dir) . $unique_file_name;
    
            // Check if the file exists, and delete it before saving the new one
            if (file_exists($recipient_file_path)) {
                unlink($recipient_file_path);
            }

            if (!copy($result['file_path'], $recipient_file_path)) {
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
            fclose($handle);
        } elseif ($file_ext === 'xlsx') {
            require_once OH_PLUGIN_DIR_PATH. 'libs/SimpleXLSX/SimpleXLSX.php';
        
            $xlsx = SimpleXLSX::parse($file_path);
            if (!$xlsx) {
                wp_send_json_error(['message' => 'Invalid XLSX file: ' . SimpleXLSX::parseError()]);
                wp_die();
            }
            $header = $xlsx->rows()[0];
        } elseif ($file_ext === 'xls') {
            require_once OH_PLUGIN_DIR_PATH. 'libs/SimpleXLS/SimpleXLS.php';
        
            $xls = SimpleXLS::parse($file_path);
            if (!$xls) {
                wp_send_json_error(['message' => 'Invalid XLS file: ' . SimpleXLS::parseError()]);
                wp_die();
            }
            $header = $xls->rows()[0];
        } else {
            wp_send_json_error(['message' => 'Unsupported file type. Only CSV, XLSX, and XLS are allowed.']);
            wp_die();
        }
        
    
        $required_columns = OH_REQUIRED_COLUMNS;
        
        $required_columns_lower = array_map('strtolower', $required_columns);
        $header_lower = array_map('strtolower', $header);
        
        $missing_columns = array_diff($required_columns_lower, $header_lower);
    
        if (!empty($missing_columns)) {
            wp_send_json_error(['message' => 'Missing required columns: ' . implode(', ', $missing_columns)]);
            wp_die();
        }
        
        $total_rows = 0;
        if ($file_ext === 'csv') {
            $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $total_rows = $lines ? count($lines) - 1 : 0;
        } elseif ($file_ext === 'xlsx') {
            $xlsx = SimpleXLSX::parse($file_path);
            $total_rows = count($xlsx->rows()) - 1;
        } elseif ($file_ext === 'xls') {
            $xls = SimpleXLS::parse($file_path); 
            $total_rows = count($xls->rows()) - 1;
        } 
    
        $wpdb->query('START TRANSACTION');
        try {
            $processed_rows = 0;
            $error_rows = [];
            $rows = [];
            
            if($file_ext === 'csv'){
                $rows = [];
            } elseif ($file_ext === 'xlsx') {
                $rows = $xlsx->rows();
            } elseif ($file_ext === 'xls') {
                $rows = $xls->rows();
            }

            
            for ($i = 0; $i < $chunk_size; $i++) {
                $row = ($file_ext === 'csv') ? fgetcsv($handle) : ($rows[$current_chunk * $chunk_size + $i + 1] ?? false);
                if ($row === false) break;
                
                if (count($row) !== count($header_lower)) {
                    $error_rows[] = $current_chunk * $chunk_size + $processed_rows;
                    continue;
                }
                
                $data = array_combine($header_lower, $row);
                $failure_reasons = [];
                
                $required_fields = [
                    'full name' => 'Full name',
                    'Mailing Address' => 'Mailing Address',
                    'city' => 'City',
                    'state' => 'State',
                    'zipcode' => 'Zipcode',
                    'quantity' => 'quantity',
                    
                ];
                
                foreach ($required_fields as $key => $field) {
                    
                    if ($key !== 'quantity' && empty($data[strtolower($key)])) {
                        $failure_reasons[] = "Missing {$field}";
                    } elseif ($key === 'quantity' && (!is_numeric($data[strtolower($key)]) || $data[strtolower($key)] <= 0)) {
                        
                    }
                }

    
                $insert_data = [
                    'user_id'          => get_current_user_id(),
                    'pid'              => $process_id,
                    'full_name'        => sanitize_text_field($data['full name']),
                    'company_name'     => sanitize_text_field($data['company name']),
                    'address_1'        => sanitize_textarea_field($data['mailing address']),
                    'address_2'        => sanitize_textarea_field($data['suite/apt']),
                    'city'             => sanitize_text_field($data['city']),
                    'state'            => sanitize_text_field($data['state']),
                    'zipcode'          => sanitize_text_field($data['zipcode']),
                    'quantity'         => max(1, intval($data['quantity'] ?? 1)),
                    'greeting'         => sanitize_textarea_field($data['greeting']),
                    'verified'         => empty($failure_reasons) ? 1 : 0,
                    'address_verified' => 0,
                    'new'              => 0,
                    'reasons'          => empty($failure_reasons) ? null : json_encode($failure_reasons),
                ];
                $wpdb->insert($recipient_table, $insert_data);
                $processed_rows++;
            }
    
            $wpdb->query('COMMIT');
            
            $order_process_table = OAM_Helper::$order_process_table;
            
            $currentStep++;

            $data = [
                'step'    => sanitize_text_field($currentStep),
            ];
        
            $result = $wpdb->update(
                $order_process_table,
                $data,
                ['id' => $process_id]
            );

            wp_send_json_success([
                'message' => "Chunk processed successfully", 
                'processed_rows' => $processed_rows, 
                'error_rows' => $error_rows, 
                'next_chunk' => $current_chunk + 1, 
                'total_rows' => $total_rows, 
                'progress' => min(100, round((($current_chunk + 1) * $chunk_size) / $total_rows * 100)), 
                'finished' => (($current_chunk + 1) * $chunk_size) >= $total_rows, 'pid' => $process_id
            ]);

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
	 * An AJAX handler function that saves and verifies the address for a single-address order before redirecting to the checkout page.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
     * USE Helper function 
     * validate_address(): This function checks address validation using the Smarty API.
     * 
     * 
     * TODO: get dynamic product price base selected affiliate.
     * 
	 */
    
    public function orthoney_single_address_data_save_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');

        global $wpdb;
    
        $order_process_table = OAM_Helper::$order_process_table;
        $stepData = $_POST;
        $user_id = get_current_user_id();

        $process_id = !empty($stepData['pid']) ? $stepData['pid'] : '';


        $processQuery = $wpdb->prepare("
        SELECT order_id
        FROM {$order_process_table}
        WHERE user_id = %d 
        AND id = %d 
        ", $user_id, $process_id);

        $processResult = $wpdb->get_row($processQuery);

        
        if(!empty($processResult)){
            $order = wc_get_order( $processResult->order_id);
            if ($order) {
                $order_key = $order->get_order_key();
                $order_url = wc_get_endpoint_url('view-order', $processResult->order_id, wc_get_page_permalink('myaccount')) . '?key=' . $order_key;
                wp_send_json_success([
                    'message' => 'Address is Verify',
                    'checkout_url' => $order_url
                ]);
            }
        }
        

        $currentStep = !empty($stepData['currentStep']) ? $stepData['currentStep'] : '';
        $delivery_line_1 = !empty($stepData['single_order_address_1']) ? $stepData['single_order_address_1'] : '';
        $delivery_line_2 = !empty($stepData['single_order_address_2']) ? $stepData['single_order_address_2'] : '';
        $city = !empty($stepData['single_order_city']) ? $stepData['single_order_city'] : '';
        $state = !empty($stepData['single_order_state']) ? $stepData['single_order_state'] : '';
        $country = !empty($stepData['single_order_country']) ? $stepData['single_order_country'] : '';
        $zipcode = !empty($stepData['single_order_zipcode']) ? $stepData['single_order_zipcode'] : '';
        $quantity = !empty($stepData['single_address_quantity']) ? $stepData['single_address_quantity'] : '';

        $validate_address_result =  OAM_Helper::validate_address($delivery_line_1, $delivery_line_2, $city, $state, $zipcode);

        $data = json_decode($validate_address_result, true);
        if(!empty($data)){
            if($data['success'] === false){
                wp_send_json_error(['message' => $data['message']]);
            }else{
                update_user_meta($user_id, 'shipping_address_1', $delivery_line_1);
                update_user_meta($user_id, 'shipping_address_2', $delivery_line_2);
                update_user_meta($user_id, 'shipping_city', $city);
                update_user_meta($user_id, 'shipping_state', $state);
                update_user_meta($user_id, 'shipping_country', $country);
                update_user_meta($user_id, 'shipping_postcode', $zipcode);

                //clear the cart
                if (class_exists('WC_Cart')) {
                    WC()->cart->empty_cart(); // First, clear the cart
                    
                    $product_id = OAM_COMMON_Custom::get_product_id();

                    $custom_price = 50; // Set custom price per unit

                    $product = wc_get_product($product_id);
                    $custom_price = $custom_price;

                    $custom_data = array(
                        'custom_data' => array(
                            'new_price' => $custom_price,
                            'single_order' => 1,
                            'process_id' => $process_id
                        )
                    );
                    
                    WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $custom_data);

                }

                $updateData = [
                    'order_type'  => sanitize_text_field('single_order'),
                ];
    
                $wpdb->update(
                    $order_process_table,
                    $updateData,
                    ['id' => $process_id]
                );

                // Get checkout page URL
                $checkout_url = wc_get_checkout_url();

                wp_send_json_success([
                    'message' => 'Address is Verify',
                    'checkout_url' => $checkout_url
                ]);

            }
        }
    }
    
    /**
	 * An AJAX handler function that save order process activity.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
	 */
   
    public function orthoney_order_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');  

        $process_id = intval($_POST['pid']);
        
        $stepData = $_POST;
        
        $groups = 0;
        if(isset($_POST['groups']) && !empty($_POST['groups'])){
            $groups = 1;
        }

        $currentStep = intval($_POST['currentStep']);
    
        global $wpdb;
    
        $order_process_table = OAM_Helper::$order_process_table;
        
        
        // Ensure data is safely serialized as a JSON string
        $data = [
            'user_id'  => get_current_user_id(),
            'data'     => wp_json_encode($stepData),
            'step'     => sanitize_text_field($currentStep),
            'greeting' => sanitize_text_field($stepData['greeting']),
        ];

        $processExistQuery = $wpdb->prepare("
        SELECT id
        FROM {$order_process_table}
        WHERE user_id = %d 
        AND id = %d 
        ", get_current_user_id(), $process_id);

        $processExistResult = $wpdb->get_row($processExistQuery);
        if (!$processExistResult) {
            $process_id = 0;
        }

        if ($process_id == 0) {
            $data['created']    = current_time('mysql');
            $data['modified']   =  current_time('mysql');
            $data['user_agent'] = OAM_Helper::get_user_agent();
            $data['user_ip']    = OAM_Helper::get_user_ip();
           
            $result = $wpdb->insert( $order_process_table, $data);
    
            if ($result !== false) {
                $process_id = $wpdb->insert_id;
    
                $updateData = [
                    'name'        => sanitize_text_field('unknown_' . $process_id),
                    'modified'    =>  current_time('mysql'),
                ];
    
                $wpdb->update(
                    $order_process_table,
                    $updateData,
                    ['id' => $process_id]
                );
            }
        } else {
            
            $result = $wpdb->update(
                $order_process_table,
                $data,
                ['id' => $process_id]
            );
        }
    
        if ($result !== false) {
            wp_send_json_success([
                'pid'     => $process_id,
                'groups'     => $groups,
                'step'    => sanitize_text_field($currentStep),
            ]);
        } else {
            wp_send_json_error(['message' => 'Database error occurred.']);
        }
    }

    /**
	 * An AJAX handler function that save step for order process form.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
	 */
    public function orthoney_order_step_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        $process_id = intval($_POST['process_value']);
        $currentStep = intval($_POST['currentStep']);

        global $wpdb;
        $order_process_table = OAM_Helper::$order_process_table;

        $data = [
            'step'    => sanitize_text_field($currentStep),
        ];

        $result = $wpdb->update(
            $order_process_table,
            $data,
            ['id' => $process_id]
        );

        if ($result !== false) {
            wp_send_json_success([
                'pid' => $process_id,
            ]);
        } else {
            wp_send_json_error(['message' => 'Database error occurred.']);
        }

    }


     /**
	 * An AJAX handler function that get recipients
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON and array depend to the parameter
     * 
     * USE Helper function 
     * get_table_recipient_content(): This function retrieves recipient data in a table format.
     * 
	 */
   
    public function orthoney_get_csv_recipient_ajax_handler($userId = '', $processID = '', $atts_process_id = 0) {
        
        $exclude_ids = $process_id = $customGreeting = $addressPartsHtml = $successHtml = $newDataHtml = $alreadyOrderHtml  = $failHtml = $duplicateHtml = '';
        $successData = $newData = $failData = $duplicateGroups = $alreadyOrderGroups = [];
        $totalCount = 0;
        if($userId != ''){
            $user = $userId;
        }else{
            // Verify nonce for security
            check_ajax_referer('oam_nonce', 'security');    
            $user = sanitize_text_field($_POST['user']);
        }

        if(isset($_POST['pid'])){
            $process_id  = isset($_POST['pid'])? sanitize_text_field($_POST['pid']) : '';
        }else{
            $process_id = $processID;
        }
        
        if($user != 0 OR $user != ''){
            global $wpdb;
            $group_recipient_table = OAM_Helper::$group_recipient_table;
            $start_date = get_field('free_shipping_start_date', 'option');
            $end_date = get_field('free_shipping_end_date', 'option');

            $tableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Status</th><th>Action</th></tr></thead><tbody>';

            $duplicateTableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Status</th><th>Action</th></tr></thead><tbody>';

            $alreadyOrderTableStart ='<table><thead><tr><th>Order Id</th><th>Order Date</th><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th></tr></thead><tbody>';
            
            $tableEnd = '</tbody></table>';
           
            $recipient_table = OAM_Helper::$order_process_recipient_table;
            
            // Generate placeholders for each ID
            
            $query = '';
            $includeQuery = '';

            if($process_id != 0 OR $process_id != ''){
                $query = $wpdb->prepare("
                    SELECT * 
                    FROM {$recipient_table}
                    WHERE user_id = %d 
                    AND pid = %d 
                    AND visibility = 1
                ", $user, $process_id);

                $order_process_table = OAM_Helper::$order_process_table;
                
                $processQuery = $wpdb->prepare("
                SELECT * 
                FROM {$order_process_table}
                WHERE user_id = %d 
                AND id = %d 
                ", $user, $process_id);

                $processResult = $wpdb->get_row($processQuery);

                if ($processResult) {
                    $getData  = json_decode($processResult->data);
                    $customGreeting = (!empty($getData->greeting)) ? $getData->greeting : '';    
                }
                
            }
            
            $allRecords = $wpdb->get_results($query);
            
            // First pass: Group records by their unique combination
            $recordMap = [];
            
            foreach ($allRecords as $record) {
                // Create unique key for comparison
                $key = $record->full_name . '|' . 
                        str_replace($record->address_1, ',' , '' ). ' ' . str_replace($record->address_2 , ',' , '') . '|' . 
                       $record->city . '|' . 
                       $record->state . '|' . 
                       $record->zipcode;
                
                // Store record in the map
                if (!isset($recordMap[$key])) {
                    $recordMap[$key] = [];
                }
                $recordMap[$key][] = $record;


                // Fetch records using `DATETIME` format for filtering
                $result = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$group_recipient_table} 
                    WHERE full_name = %s 
                    AND city = %s 
                    AND state = %s 
                    AND zipcode = %s 
                    AND user_id = %d
                    AND `timestamp` BETWEEN %s AND %s",
                    $record->full_name, $record->city, $record->state,  $record->zipcode, $user,$start_date, $end_date
                ));

                // Normalize and merge input address values
                $search_address = $record->address_1 . $record->address_2; // Merge address_1 and address_2
                $search_address = str_replace([',', '.'], '', $search_address);
                $search_address = trim($search_address);

                // Filter results by merging address_1 and address_2 in the database
                $filtered_results = array_filter($result, function ($record) use ($search_address) {
                    // Merge database address_1 and address_2
                    $merged_address = trim($record->address_1 . ' ' . $record->address_2);
                    $merged_address = str_replace([',', '.'], '', $merged_address);

                    // Compare merged values
                    return strcasecmp($search_address, $merged_address) === 0;
                });


                $alreadyOrderGroups = $filtered_results;

            }
    
            // Second pass: Categorize records
            foreach ($recordMap as $key => $records) {
                if (count($records) > 1) {
                    // This is a group of duplicates
                    $duplicateGroups[] = $records;
                } else {
                    // Single record - add to success or fail based on verified status
                    $record = $records[0];
                    if ($record->new == 0) {
                        if ($record->verified == 1) {
                            $successData[] = $record;
                        } else {
                            $failData[] = $record;
                        }
                    }else{
                        $newData[] = $record;
                    }
                }
            }

          
            $totalDuplicates = 0;
            if($atts_process_id == 0){
          
                // Generate Duplicate HTML - now showing grouped duplicates
                //Keep 1 Entry and Delete Other Duplicate Entries
                if(!empty($duplicateGroups)){
                    foreach ($duplicateGroups as $group) {
                        $totalDuplicates += count($group);
                    }

                    $bulkMargeButtonHtml = '';
                    if($process_id != ''){
                        $bulkMargeButtonHtml = '<div class="tooltip" data-tippy="Keep 1 Entry and Delete Other Duplicate Entries"><button id="bulkMargeRecipient" class="btn-underline">Bulk Marge</button></div>';
                    }
                    
                    $duplicateHtml .= '<div class="heading-title"><div><h5 class="table-title">Duplicate Recipient</h5> </div>'.$bulkMargeButtonHtml.'</div>';
                    
                    foreach ($duplicateGroups as $groupIndex => $group) {
                        $duplicateHtml .= '<tr class="group-header" data-count="'.count($group).'" data-group="99'.($groupIndex + 1).'"><td colspan="12"><strong>'.count($group).'</strong> duplicate records for <strong>'.($group[0]->full_name).'</strong></td></tr>';
                        $duplicateHtml .= OAM_Helper::get_table_recipient_content($group , $customGreeting, 0 , '99'.$groupIndex + 1);
                        
                    }
                }

            
                // Generate new data HTML
                if(!empty($newData)){
                    $newDataHtml .= '<div class="heading-title"><h5 class="table-title">New Recipients</h5><div><button class="editRecipient btn-underline" data-popup="#recipient-manage-popup">Add New Recipient</button></div></div>';
                    $newDataHtml .= OAM_Helper::get_table_recipient_content($newData, $customGreeting);
                    
                }
        
                // Generate Success HTML
                if(!empty($successData)){
                    $successHtml .= '<div class="heading-title"><div><h5 class="table-title">Verified Recipients</h5></div></div>';
                    $successHtml .=  OAM_Helper::get_table_recipient_content($successData , $customGreeting);
                }
                
                
                if(!empty($alreadyOrderGroups)){
                    $alreadyOrderHtml .= '<div class="heading-title"><div><h5 class="table-title">Already Ordered</h5><p> Honey is already ordered for '.count($alreadyOrderGroups).' Recipients this year</p></div></div>';
                    $alreadyOrderHtml .= OAM_Helper::get_table_recipient_content($alreadyOrderGroups , $customGreeting, 0 , 0 , 1);
                }

                // Wrap tables with headers
                if($newDataHtml != ''){
                    $newDataHtml = $tableStart.$newDataHtml.$tableEnd;
                }
                if($successHtml != ''){
                    $successHtml = $tableStart.$successHtml.$tableEnd;
                }
                if($alreadyOrderHtml != ''){
                    $alreadyOrderHtml = $alreadyOrderTableStart.$alreadyOrderHtml.$tableEnd;
                }

                if($duplicateHtml != ''){
                    $duplicateHtml = $duplicateTableStart.$duplicateHtml.$tableEnd;
                }
        
                // Calculate total count of all records including duplicates
                $totalDuplicates = 0;
                foreach ($duplicateGroups as $group) {
                    $totalDuplicates += count($group);
                }
            }else{
                $successData = [];
            }
            
            // Generate Fail HTML
            if(!empty($failData)){
                // $failHtml .= '<div><h3>Failed to Add Recipeints</h3><h4>'.count($failData).' Recipient failed!</h4></div>';
                $failHtml .= OAM_Helper::get_table_recipient_content($failData , $customGreeting);
            }

            $totalCount = count($successData) + count($failData) + $totalDuplicates + count($newData);

            
            if($failHtml != ''){
                $failHtml = '<div class="download-csv"><div class="heading-title"><div><h5 class="table-title">Failed Recipeints</h5><p>To fix the failed data, edit the row and make the necessary changes OR upload a new CSV for failed recipients.</p></div><div><button data-tippy="Failed records can be downloaded" id="download-failed-recipient-csv" class="btn-underline" ><i class="far fa-download"></i> Download Failed Recipients</button></div></div> </div>'.$tableStart.$failHtml.$tableEnd;
            }
            
            

            $resultData = [
                'newData'         => $newDataHtml,
                'successData'     => $successHtml,
                'failData'        => $failHtml,
                'alreadyOrderData'=> $alreadyOrderHtml,
                'duplicateData'   => $duplicateHtml,
                'duplicateGroups' => $duplicateGroups,
                'totalCount'      => $totalCount,
                'successCount'    => count($successData),
                'newCount'        => count($newData),
                'failCount'       => count($failData),
                'alreadyOrderCount'   => count($alreadyOrderGroups),
                'duplicateCount'  => $totalDuplicates,
                'groupCount'      => count($duplicateGroups)
            ];

            
            if($userId != ''){
                return json_encode(['success' => true, 'data'=> $resultData]);
            }else{
                wp_send_json_success( $resultData);
            }
           
        }else {
            if($userId != ''){
                return json_encode(['success' => false, 'message' => 'No data found.']);
            }else{
                wp_send_json_error( ['message' => 'No data found.']);
            }
        }
    
        wp_die();
    }
    
    /**
	 * An AJAX handler function that download failed recipients data.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
     * TODO : remaining code for group
	 */
    
     public function orthoney_download_failed_recipient_handler() {
        global $wpdb;
    
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : '';
    
        // Get the logged-in user ID
        $user = get_current_user_id();
        $failData = [];
        $recordMap = [];
        $filename = 'fail-recipients-' . $id . '.csv';
        if ($type == 'process') {
            $recipient_table = OAM_Helper::$order_process_recipient_table;
            $allRecords = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$recipient_table} WHERE user_id = %d AND pid = %d  AND visibility = %d", $user, $id, 1));
           
        
            foreach ($allRecords as $record) {
                $key = $record->full_name . '|' . 
                    $record->address_1 . '|' . 
                    $record->city . '|' . 
                    $record->state . '|' . 
                    $record->country . '|' . 
                    $record->quantity . '|' . 
                    $record->zipcode;
        
                if (!isset($recordMap[$key])) {
                    $recordMap[$key] = [];
                }
                $recordMap[$key][] = $record;
            }
        
            foreach ($recordMap as $key => $records) {
                if (count($records) == 1) {
                    $record = $records[0];
                    if ($record->verified == 0) {
                        $failData[] = $record;
                    }
                }
            }
        }
        
        if ($type == 'group') {
            $filename = 'recipients-list-' . $id . '.csv';
            $group_recipient_table = OAM_Helper::$group_recipient_table;
           
            $failData = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$group_recipient_table} WHERE user_id = %d AND group_id = %d  AND visibility = %d
            ", $user, $id, 1));
        }
    
       
        if (!empty($failData)) {
            $csvData = array(
                array('Full name', 'Company name', 'Mailing address', 'Suite/Apt', 'City', 'State', 'Zipcode', 'Quantity', 'Greeting', 'Reasons'),
            );
    
            foreach ($failData as $record) {
                $reasons = (!empty($record->reasons)) ? implode(", ", json_decode($record->reasons, true)) : '';
    
                $csvData[] = array(
                    $record->full_name,
                    $record->company_name,
                    $record->address_1,
                    $record->address_2,
                    $record->city,
                    $record->state,
                    $record->zipcode,
                    $record->quantity,
                    $record->greeting,
                    $reasons,
                );
            }
    
            // Define new folder path inside wp-content
            $custom_dir = WP_CONTENT_DIR . '/download_recipient_list';
            $custom_url = content_url('/download_recipient_list');
    
            // Create the directory if it does not exist
            if (!file_exists($custom_dir)) {
                wp_mkdir_p($custom_dir);
            }
    
            // Use $id in the filename
           
            $file_path = $custom_dir . '/' . $filename;
            $file_url = $custom_url . '/' . $filename;
    
            // Remove the existing file if it exists
            if (file_exists($file_path)) {
                unlink($file_path);
            }
    
            // Open file for writing
            $output = fopen($file_path, 'w');
            if ($output === false) {
                wp_send_json_error(array('message' => 'Failed to create CSV file.'));
                exit;
            }
    
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
    
            // Check if file exists before sending the response
            if (file_exists($file_path)) {
                wp_send_json_success(array(
                    'url' => $file_url,
                    'filename' => $filename,
                ));
            } else {
                wp_send_json_error(array('message' => 'CSV file not found.'));
            }
            exit;
        } else {
            wp_send_json_error(array('message' => 'No failed records found.'));
        }
    }
    
    // Callback function for create Group
    public function orthoney_create_group_handler() {
        // Check if the group name is provided
        if (empty($_POST['group_name'])) {
            wp_send_json_error(['message' => 'Recipients list name is required.']);
        }
    
        $group_name = sanitize_text_field($_POST['group_name']);
        $group_id = sanitize_text_field($_POST['group_id']);
        $group_status = sanitize_text_field($_POST['group_status']);
    
        // Your logic to insert the group into the database
        global $wpdb;
        $table = $wpdb->prefix . 'recipient_group';
        if($group_status  == 'edit'){
            $result = $wpdb->update(
                $table,
                ['name' => $group_name], // Data to update
                ['id' => $group_id],   // WHERE condition
                ['%s'],                // Format for data to update
                ['%d']                 // Format for WHERE condition
            );
            if ($result !== false) {
                wp_send_json_success(['message' => 'Recipients list name updated successfully!']);
            } else {
                wp_send_json_error(['message' => 'Failed to update Recipients list name.']);
            }
        }
        if($group_status  == 'create'){
            $result = $wpdb->insert(
                $table,
                [
                    'user_id' => get_current_user_id(),
                    'name' => $group_name,
                ],
                ['%d', '%s']
            );
        
            if ($result) {
                wp_send_json_success(['message' => 'Recipients list created successfully!']);
            } else {
                wp_send_json_error(['message' => 'Failed to create recipients list.']);
            }
        }
        

    }

    // Callback function for deleted Group
    public function orthoney_deleted_group_handler() {
        // Check if the group name is provided
        if (empty($_POST['group_id'])) {
            wp_send_json_error(['message' => 'Group name is required.']);
        }
    
        $group_id = sanitize_text_field($_POST['group_id']);
    
        // Your logic to insert the group into the database
        global $wpdb;
        $group_table = OAM_Helper::$group_table;
    
        $result = $wpdb->update(
            $group_table,
            ['visibility' => 0],
            ['id' => $group_id]
        );
    
        if ($result) {
            wp_send_json_success(['message' => 'Group deleted successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete group or group not found.']);
        }
    }

    // Callback function for bulk deleted recipient 
    public function orthoney_bulk_deleted_recipient_handler() {
        
        if (isset($_POST['ids']) && is_array(json_decode(stripslashes($_POST['ids'])))) {
            global $wpdb;
            $table = OAM_Helper::$order_process_recipient_table;
            $ids = json_decode(stripslashes($_POST['ids']));
    
            // Prepare the IDs for the IN clause, ensuring they are integers
            $ids_placeholder = implode(',', array_map('intval', $ids));

            // Update the records based on the IDs array
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET visibility = 0 WHERE id IN ($ids_placeholder)",
                    $ids
                )
            );

            if ($result !== false) {
                foreach ($ids as $key => $id) {
                    OAM_Helper::order_process_recipient_activate_log($id, 'bulk deleted', '');
                }
                wp_send_json_success([
                    'message' => 'Recipient deleted successfully!',
                    'groupId' => $groupId,
                    'user' => get_current_user_id(),
    
                ]);
            } else {
                wp_send_json_error(['message' => 'Failed to delete recipient or recipient not found.']);
            }
        
        } else {
            wp_send_json_error(['message' => 'Failed to delete recipient or recipient not found.']);
        }

    }
    
    // Callback function for deleted recipient 
    public function orthoney_deleted_recipient_handler() {
        // Check if the recipient id is provided
        if (empty($_POST['id'])) {
            wp_send_json_error(['message' => 'Recipient is required.']);
        }
        $method = !empty($_POST['method']) ? $_POST['method'] : 'process';
    
        $id = sanitize_text_field($_POST['id']);

        // Your logic to insert the recipient into the database
        global $wpdb;
        $table = OAM_Helper::$order_process_recipient_table;
        if($method == 'group'){
            $table = OAM_Helper::$group_recipient_table;
        }

        $result = $wpdb->update(
            $table,
            ['visibility' => 0],
            ['id' => $id]
        );
    
        if ($result) {
            OAM_Helper::order_process_recipient_activate_log($id, 'deleted', '', $method);
            wp_send_json_success([
                'message' => 'A new recipient has been added successfully!',
                'user' => get_current_user_id(),

            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to delete recipient or recipient not found.']);
        }
    }

    // Callback function for get recipient base in id
    public function orthoney_get_recipient_base_id_handler($recipient = '') {
        global $wpdb;
    
        // Determine recipient ID from POST or fallback to the function parameter
        $recipientID = !empty($_POST['id']) ? intval($_POST['id']) : intval($recipient);
        $method = !empty($_POST['method']) ? $_POST['method'] : 'process';
    
        if (empty($recipientID)) {
            $response = ['success' => false, 'message' => 'Invalid recipient ID.'];
            return !empty($recipient) ? json_encode($response) : wp_send_json_error($response);
        }
    
        // Table name
        $recipient_table = OAM_Helper::$order_process_recipient_table;
        if($method == 'group'){
            $recipient_table = OAM_Helper::$group_recipient_table;
        }
    
        // Fetch recipient record as an associative array
        $record = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $recipient_table WHERE id = %d", $recipientID), ARRAY_A
        );
    
        if (!empty($record)) {
            $response = ['success' => true, 'data' => $record];
            return !empty($recipient) ? json_encode($response) : wp_send_json_success($record);
        } else {
            $response = ['success' => false, 'message' => 'No data found.'];
            return !empty($recipient) ? json_encode($response) : wp_send_json_error($response);
        }
    }

    // Callback function for manually add new recipient and edit recipient
    public function orthoney_manage_recipient_form_handler() {
        $method =  isset($_POST['method']) ? $_POST['method'] : 'process';
        
        $group_id =  isset($_POST['group_id']) ? $_POST['group_id'] : 0;
        global $wpdb;
        
        // Check for required fields
        $required_fields = [
            'full_name' => 'Full Name',
            'address_1' => 'Mailing Address',
            'city' => 'City',
            'state' => 'State',
            'quantity' => 'quantity',
            'zipcode' => 'zipcode',
        ];
        
        $missing_fields = [];
        
        foreach ($required_fields as $key => $field) {
            if (!isset($_POST[$key]) || empty($_POST[$key])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            wp_send_json_error([
                'message' => 'Missing required fields: '.implode(', ', $missing_fields)
            ]);
        }
        
        
        $address_verified = $_POST['address_verified'] ? $_POST['address_verified'] : 0 ;
        // Sanitize and prepare common data
        $data = [
            'full_name' => sanitize_text_field($_POST['full_name']),
            'company_name'  => sanitize_text_field($_POST['company_name']),
            'address_1'  => sanitize_textarea_field($_POST['address_1']),
            'address_2'  => isset($_POST['address_2']) ? sanitize_textarea_field($_POST['address_2']) : '',
            'city'       => sanitize_text_field($_POST['city']),
            'state'      => sanitize_text_field($_POST['state']),
            'zipcode'    => sanitize_text_field($_POST['zipcode']),
            'quantity'   => $_POST['quantity'],
            'greeting'   => sanitize_textarea_field($_POST['greeting']),
            'reasons'    => '',
            'verified'   => 1,
            'visibility' => 1,
            'timestamp'  => current_time('mysql'),
        ];
        
        $table = OAM_Helper::$order_process_recipient_table;
        
        
        $recipient_id = isset($_POST['recipient_id']) ? absint($_POST['recipient_id']) : null;
        $process_id = isset($_POST['pid']) ? absint($_POST['pid']) : null;
        
        // Construct the key for checking existing records
        $record_key = $data['full_name'] . '|' . 
        $data['address_1'] . '|' . 
        $data['city'] . '|' . 
        $data['state'] . '|' . 
        $data['zipcode'];
        
        $status = '';
        $result = false;
        
        if($method == 'group'){
            $table = OAM_Helper::$group_recipient_table;
            $existing_recipient = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, visibility FROM $table WHERE full_name = %s AND address_1 = %s AND city = %s AND state = %s AND zipcode = %s AND group_id = %d", 
                $data['full_name'], $data['address_1'], $data['city'], $data['state'], $data['zipcode'], $group_id
                )
            );
        }else{
        
            $existing_recipient = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, visibility FROM $table WHERE full_name = %s AND address_1 = %s AND city = %s AND state = %s AND zipcode = %s AND pid = %d", 
                $data['full_name'], $data['address_1'], $data['city'], $data['state'], $data['zipcode'], $process_id
                )
            );
        }
        
            
        if (!$recipient_id && $existing_recipient) {
            if ($existing_recipient->visibility == 1) {
                wp_send_json_error(['message' => 'A recipient with the same details already exists.']);
            } else {
                // Only update visibility to 1
                $result = $wpdb->update($table, ['visibility' => 1], ['id' => $existing_recipient->id], ['address_verified' => 0]);
                if ($result !== false) {
                    wp_send_json_success([
                        'status'       => 'update',
                        'recipient_id' => $existing_recipient->id,
                        'message'      => 'Recipient reactivated successfully!',
                    ]);
                   
                    OAM_Helper::order_process_recipient_activate_log($existing_recipient->id, 'reactivated', '', $method);
                } else {
                    wp_send_json_error(['message' => 'Failed to update visibility.']);
                }
            }
        }
            
        if ($recipient_id) {
            // Update existing recipient
            $existing_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $recipient_id), ARRAY_A);
            $changes = [];
            
            foreach ($data as $key => $value) {
                if (isset($existing_data[$key]) && $existing_data[$key] != $value) {
                    $changes[$key] = [
                        'old' => $existing_data[$key],
                        'new' => $value
                    ];
                }
            }
            if($address_verified == 1){

                $validation_result = json_decode(OAM_Helper::validate_address(
                    $data['address_1'], $data['address_2'], $data['city'], $data['state'], $data['zipcode']
                ), true);
            
                
                if ($validation_result) {
                    $verified_status = $validation_result['success'] ? 1 : 0;
                    $data['address_verified'] = $verified_status;
                    $result = $wpdb->update(
                        $table,
                        $data,
                        ['id' => $recipient_id]
                    );
                    
                    if ($verified_status == 0) {
                        wp_send_json_error(['message' => 'The address is incorrect. Please enter the correct address.']);
                    }else{
                        // wp_send_json_success(['message' => 'The address is correct.']);
                    }
                }
            }else{

                $result = $wpdb->update($table, $data, ['id' => $recipient_id]);
            }
            
            $status = 'update';
            
            OAM_Helper::order_process_recipient_activate_log($recipient_id, $status, wp_json_encode($changes), $method);
            $success_message = 'Recipient details updated successfully!';
            
        } else {
            // Insert new recipient
            $data['user_id'] = get_current_user_id();
            if($method == 'group'){
                $data['group_id'] = $group_id;
            }else{
                $data['pid'] = $_POST['pid'];
            }
            $data['new'] = 1;
            $data['address_verified'] = 0;
            $data['update_type'] = sanitize_text_field("add_manually");
            $result = $wpdb->insert($table, $data);
            $recipient_id = $wpdb->insert_id;
            $status = 'new';
            

            OAM_Helper::order_process_recipient_activate_log($wpdb->insert_id, $status, 'manually added' , $method);
            $success_message = 'Recipient details added successfully!';
        }
        
        // Handle the result
        if ($result !== false) {
            wp_send_json_success([
                'status'       => $status,
                'user'         => get_current_user_id(),
                'recipient_id' => $recipient_id,
                'message'      => $success_message,
            ]);
        } else {
            wp_send_json_error([
                'message' => $wpdb->last_error ?: 'Database operation failed.',
            ]);
        }
    }
    
    /**
	 * An AJAX handler function that reverify address 
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON and array depend to the parameter
     * 
     * USE Helper function 
     * validate_address(): This function verify address using the Smarty API
     * 
	 */
    public function orthoney_reverify_address_recipient_handler() {
        check_ajax_referer('oam_nonce', 'security');  
        global $wpdb;
    
        $order_process_table           = OAM_Helper::$order_process_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        
        $recipient_id  = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
    
        // Verify recipient addresses if group_id is provided
        
        $recipient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, address_1, address_2, city, state, zipcode FROM {$order_process_recipient_table} 
                WHERE id = %d",
                $recipient_id
            )
        );
        
        if ($recipient) {
            $validation_result = json_decode(OAM_Helper::validate_address(
                $recipient->address_1, $recipient->address_2, $recipient->city, $recipient->state, $recipient->zipcode
            ), true);
        
            if ($validation_result) {
                $verified_status = $validation_result['success'] ? 1 : 0;
        
                $update_result = $wpdb->update(
                    $order_process_recipient_table,
                    ['address_verified' => $verified_status],
                    ['id' => $recipient->id]
                );
        
                if ($update_result == 0) {
                    wp_send_json_error(['message' => 'The address is incorrect. Please enter the correct address.']);
                }else{
                    wp_send_json_success(['message' => 'The address is correct.']);
                }
            }
        }        
    
        wp_send_json_success(['message' => 'The address is correct. ']);
        
        wp_die();
    }

    public function orthoney_affiliate_status_toggle_block_handler() {
        global $wpdb;
        $user_id = get_current_user_id();
        $oh_affiliate_customer_relation_table = OAM_Helper::$oh_affiliate_customer_relation_table;


        $affiliate_id = sanitize_text_field($_POST['affiliate_id']);
        $status = sanitize_text_field($_POST['status']);

        if ($status == 'block') {
            $wpdb->insert($oh_affiliate_customer_relation_table, [
                'user_id' => $user_id,
                'affiliate_id' => $affiliate_id
            ]);
        } elseif ($status == 'unblock') {
            $wpdb->delete($oh_affiliate_customer_relation_table, [
                'user_id' => $user_id,
                'affiliate_id' => $affiliate_id
            ]);
        } else {
            wp_send_json_error(['message' => 'Invalid action']);
        }

        wp_send_json_success();
    }
    
    
    public function search_affiliates_handler() {
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';

        $affiliates_content = OAM_Helper::manage_affiliates_content($search, $filter, 1);

        $affiliates_data = json_decode($affiliates_content, true);

        $affiliates = $affiliates_data['data']['affiliates'];
        $blocked_affiliates = $affiliates_data['data']['blocked_affiliates'];
        
        ?>
        <table>
            <thead><tr><th>Affiliate Code</th><th>Affiliate Name</th><th>Block/Unblock</th></tr></thead>
            <tbody>
                <?php 
                if(!empty($affiliates)){
                foreach ($affiliates as $affiliate){
                        $is_blocked = in_array($affiliate['ID'], $blocked_affiliates);
                    ?>
                <tr>
                    <td><?php echo esc_html($affiliate['token']); ?></td>
                    <td><?php echo esc_html($affiliate['display_name']); ?></td>
                    <td>
                    <button class="affiliate-block-btn w-btn <?php echo $is_blocked ? 'us-btn-style_2' : 'us-btn-style_1' ?>" 
                        data-affiliate="<?php echo esc_attr($affiliate['token']); ?>"
                            data-blocked="<?php echo $is_blocked ? '1' : '0'; ?>">
                            <?php echo $is_blocked ? 'Unblock' : 'Block'; ?>
                        </button>
                    </td>
                </tr>
                <?php } }else{
                    echo '<tr><td colspan="3">Not Affiliate Found!</td></tr>';
                } ?>
            </tbody>
        </table>
        <?php 

        wp_die();
    }

    public function orthoney_incomplete_order_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
        $user_id = get_current_user_id();
        $current_page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $failed = isset($_POST['failed']) ? $_POST['failed'] : 0;
        $items_per_page = 10;
        $offset = ($current_page - 1) * $items_per_page;
    
        $order_process_table = OAM_Helper::$order_process_table;
        // Get total items and pages

        if($failed == 1){
            $total_items = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$order_process_table} WHERE user_id = %d AND step = %d AND order_id != %d AND order_type = %s",
                $user_id, 5, 0 ,'multi-recipient-order'
            ));

             // Fetch process data
            $processQuery = $wpdb->prepare(
                "SELECT * FROM {$order_process_table} WHERE user_id = %d AND step = %d AND order_id != %d AND order_type = %s ORDER BY created DESC LIMIT %d OFFSET %d",
                $user_id, 5 ,0 ,'multi-recipient-order' ,$items_per_page, $offset
            );
        }else{

            $total_items = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$order_process_table} WHERE user_id = %d AND step != %d",
                $user_id, 5
            ));
          
            // Fetch process data
            $processQuery = $wpdb->prepare(
                "SELECT * FROM {$order_process_table} WHERE user_id = %d AND step != %d ORDER BY created DESC LIMIT %d OFFSET %d",
                $user_id, 5 ,$items_per_page, $offset
            );
        }

        $total_pages = (int) ceil($total_items / $items_per_page);
        
        $results = $wpdb->get_results($processQuery);
        $table_content = '';
    
        if (!empty($results)) {
            foreach ($results as $data) {
                $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->created));

                if($failed != 1){
                    $resume_url = esc_url(home_url("/order-process?pid=$data->id"));
                }else{
                    $resume_url = esc_url(home_url("/customer-dashboard/failed-recipients/details/".$data->id));
                }

                $download_button = '';
    
                if (!empty($data->csv_name)) {
                    $download_url = esc_url(OAM_Helper::$process_recipients_csv_url . $data->csv_name);
                    $download_button = "<a href='".esc_url($download_url)."' class='w-btn us-btn-style_1 outline-btn round-btn' download data-tippy='Download Recipients File'><i class='far fa-download'></i></a>";
                }
    
                $table_content .= "<tr>
                <td><div class='thead-data'>Sr No</div>" . esc_html($data->id) . "</td>
                <td><div class='thead-data'>Name</div>" . esc_html($data->name) . "</td>
                <td><div class='thead-data'>Date</div>". esc_html($created_date). "</td>
                <td><div class='thead-data'>Action</div> <a href='".esc_url($resume_url)."' class='w-btn us-btn-style_1 outline-btn sm-btn'>".($failed == 1 ? 'View Recipients' : 'Resume Order' )."</a> ".($failed != 1 ? $download_button : '' )." </td>
                </tr>";
            }
        } else {
            $table_content = '<tr><td colspan="4">No data available</td></tr>';
        }
    
        $pagination = '';
        if ($total_pages > 1) {
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($current_page == $i) ? 'active' : '';
                $pagination .= "<a href='javascript:;' class='".$active_class."' data-page='".$i."'>".$i."</a> ";
            }
        }
        wp_send_json_success([
            'table_content' => $table_content,
            'pagination' => $pagination,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
        ]);
        wp_die();
    }

    //
    public function orthoney_groups_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
        $user_id = get_current_user_id();
        $current_page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        
        $items_per_page = 10;
        $offset = ($current_page - 1) * $items_per_page;
    
        $group_table = OAM_Helper::$group_table;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
        $order_process_table = OAM_Helper::$order_process_table;;

        // Get total items and pages
        $total_items = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$group_table} WHERE user_id = %d AND visibility = %d",
            $user_id, 1
        ));
       
        
        // Fetch process data
        $processQuery = $wpdb->prepare(
            "SELECT * FROM {$group_table} WHERE user_id = %d  AND visibility = %d LIMIT %d OFFSET %d",
            $user_id ,1,$items_per_page, $offset
        );

       
        

        $total_pages = (int) ceil($total_items / $items_per_page);
        
        $results = $wpdb->get_results($processQuery);
        $table_content = '';
    
        if (!empty($results)) {
            $total_unique_recipients = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$group_recipient_table} WHERE user_id = %d AND visibility = %d",
                $user_id, 1
            ));

            $table_content .= "<tr>
            <td><div class='thead-data'>Sr No</div>-</td>
            <td><div class='thead-data'>Recipient List Name</div>Unique Recipients List</td>
            <td><div class='thead-data'>Number of Recipients</div>".$total_unique_recipients."</td>
            <td><div class='thead-data'>Date</div>-</td>
            <td><div class='thead-data'>Action</div><a href='".esc_url(OAM_Helper::$customer_dashboard_link.'/groups/details/unique_recipients')."' class='far fa-eye'></td>
            </tr>";
            foreach ($results as $data) {

                $total_recipients = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$group_recipient_table} WHERE user_id = %d AND group_id = %d AND visibility = %d",
                    $user_id, $data->id, 1
                ));

                $csv_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT csv_name FROM {$order_process_table} WHERE user_id = %d AND id = %d",
                    $user_id, $data->pid
                ));

                $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->timestamp));

                $resume_url = esc_url(OAM_Helper::$customer_dashboard_link.'/groups/details/'.$data->id);
               
                $download_button = '';
    
                if (!empty($data->csv_name)) {
                    $download_url = esc_url(OAM_Helper::$process_recipients_csv_url . $data->csv_name);
                    $download_button = "<a href='".esc_url($download_url)."' class='w-btn us-btn-style_1 outline-btn round-btn' download data-tippy='Download Recipients File'><i class='far fa-download'></i></a>";
                }
    
                $table_content .= "<tr>
                <td><div class='thead-data'>ID</div>" . esc_html($data->id) . "</td>
                <td><div class='thead-data'>Recipient List Name</div>" . esc_html($data->name) . "</td>
                <td><div class='thead-data'>Number of Recipients</div>".$total_recipients."</td>
                <td><div class='thead-data'>Date</div>" . esc_html($created_date) . "</td>
                <td><div class='thead-data'>Action</div> <a href='".esc_url($resume_url)."' class='far fa-eye'></a><button data-groupid='".esc_html($data->id)."' data-groupname='".esc_html($data->name)."' data-tippy='Remove Recipients List' class='deleteGroupButton far fa-trash'></button></td>
                </tr>";
            }
        } else {
            $table_content = '<tr><td colspan="4">No data available</td></tr>';
        }
    
        $pagination = '';
        if ($total_pages > 1) {
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($current_page == $i) ? 'active' : '';
                $pagination .= "<a href='javascript:;' class='".$active_class."' data-page='".$i."'>".$i."</a> ";
            }
        }
        wp_send_json_success([
            'table_content' => $table_content,
            'pagination' => $pagination,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
        ]);
        wp_die();
    }

}
new OAM_Ajax();