<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_Ajax{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_action( 'wp_ajax_ort_honey_order_process_ajax', array( $this, 'ort_honey_order_process_ajax_handler' ) );
        add_action( 'wp_ajax_ort_honey_order_step_process_ajax', array( $this, 'ort_honey_order_step_process_ajax_handler' ) );

        add_action( 'wp_ajax_create_group', array( $this, 'orthoney_create_group_handler' ) );

		add_action( 'wp_ajax_deleted_group', array( $this, 'orthoney_deleted_group_handler' ) );
        
        add_action( 'wp_ajax_ort_honey_insert_recipient_ajax', array( $this, 'orthoney_insert_recipient_handler') );
        
        add_action( 'wp_ajax_ort_honey_get_recipient_ajax', array( $this, 'orthoney_get_recipient_handler') );
        
		add_action( 'wp_ajax_deleted_recipient', array( $this, 'orthoney_deleted_recipient_handler' ) );

		add_action( 'wp_ajax_bulk_deleted_recipient', array( $this, 'orthoney_bulk_deleted_recipient_handler' ) );

		add_action( 'wp_ajax_get_recipient_base_id', array( $this, 'orthoney_get_recipient_base_id_handler' ) );

		add_action( 'wp_ajax_manage_recipient_form', array( $this, 'orthoney_manage_recipient_form_handler' ) );

		add_action( 'wp_ajax_download_failed_recipient', array( $this, 'orthoney_download_failed_recipient_handler'));
    }

    public function ort_honey_order_step_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        $process_id = intval($_POST['process_value']);
        $currentStep = intval($_POST['currentStep']);

        global $wpdb;
        $table = $wpdb->prefix . 'order_process';

        $data = [
            'step'    => sanitize_text_field($currentStep),
        ];

        $result = $wpdb->update(
            $table,
            $data,
            ['id' => $process_id]
        );
        if ($result !== false) {
            wp_send_json_success([
                'process_id' => $process_id,
            ]);
        } else {
            wp_send_json_error(['message' => 'Database error occurred.']);
        }

    }
    // Callback function for download failed recipients data
    public function ort_honey_order_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');  

    
        $process_id = intval($_POST['process_value']);
        
        $stepData = $_POST;
        $currentStep = intval($_POST['currentStep']);
    
        global $wpdb;
        $table = $wpdb->prefix . 'order_process';
    
        // Ensure data is safely serialized as a JSON string
        $data = [
            'user_id' => get_current_user_id(),
            'data'    => wp_json_encode($stepData),
            'step'    => sanitize_text_field($currentStep),
        ];
    
        if ($process_id == 0) {
            $result = $wpdb->insert($table, $data);
    
            if ($result !== false) {
                $process_id = $wpdb->insert_id;
    
                $updateData = [
                    'name' => sanitize_text_field('Group ' . $process_id)
                ];
    
                $wpdb->update(
                    $table,
                    $updateData,
                    ['id' => $process_id]
                );
            }
        } else {
            $result = $wpdb->update(
                $table,
                $data,
                ['id' => $process_id]
            );
        }
    
        if ($result !== false) {
            wp_send_json_success([
                'process_id' => $process_id,
            ]);
        } else {
            wp_send_json_error(['message' => 'Database error occurred.']);
        }
    }
    

    // Callback function for download failed recipients data
    public function orthoney_download_failed_recipient_handler() {
        global $wpdb;
    
        // Assuming $user is defined somewhere, or you need to set the user_id.
        $user = get_current_user_id(); // For example, if user is not passed, use the logged-in user's ID.
        
        $recipient_table = $wpdb->prefix . 'recipient_temp';
    
        // Get all records
        $allRecords = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $recipient_table WHERE user_id = %d
        ", $user));
    
        $failData = [];
    
        // First pass: Group records by their unique combination
        $recordMap = [];
    
        foreach ($allRecords as $record) {
            // Create unique key for comparison
            $key = $record->first_name . '|' . 
                   $record->last_name . '|' . 
                   $record->state . '|' . 
                   $record->country . '|' . 
                   $record->zipcode;
            
            // Store record in the map
            if (!isset($recordMap[$key])) {
                $recordMap[$key] = [];
            }
            $recordMap[$key][] = $record;
        }
    
        // Second pass: Categorize records
        foreach ($recordMap as $key => $records) {
            if (count($records) == 1) {
                // Single record - add to success or fail based on verified status
                $record = $records[0];
                if ($record->verified == 0) {
                    // If not verified, add to failData
                    $failData[] = $record;
                }
            }
        }
    
        // Generate Fail CSV if failData is not empty
        if (!empty($failData)) {
            $csvData = array(
                array('first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'country', 'zipcode', 'reasons'),
            );
    
            // Loop through the records and add each row to the CSV data
            foreach ($failData as $record) {
                // Check if reasons field is not empty and decode it
                $reasons = '';
                if (!empty($record->reasons)) {
                    // Decode the JSON and implode it into a string
                    $reasons = implode(", ", json_decode($record->reasons, true));
                }
    
                // Add the record to the CSV data array
                $csvData[] = array(
                    $record->first_name,
                    $record->last_name,
                    $record->address_1,
                    $record->address_2,
                    $record->city,
                    $record->state,
                    $record->country,
                    $record->zipcode,
                    $reasons,
                );
            }
    
            // Create a temporary file
            $filename = 'fail-recipients.csv';
            $temp_file = tempnam(sys_get_temp_dir(), 'csv_');
    
            // Open the file for writing
            $output = fopen($temp_file, 'w');
    
            // Write data to CSV
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
    
            fclose($output);
    
            // Ensure the file is accessible for download
            $file_url = wp_upload_dir()['url'] . '/' . basename($temp_file);
    
            // Move the file to the uploads directory
            $destination = wp_upload_dir()['path'] . '/' . basename($temp_file);
            rename($temp_file, $destination);
    
            // Prepare the JSON response with file URL and filename
            wp_send_json_success(array(
                'url' => $file_url,
                'filename' => $filename,
            ));
    
            exit;
        } else {
            // Handle case when no fail data is found
            wp_send_json_error(array('message' => 'No failed records found.'));
        }
    }
    
    // Callback function for create Group
    public function orthoney_create_group_handler() {
        // Check if the group name is provided
        if (empty($_POST['group_name'])) {
            wp_send_json_error(['message' => 'Group name is required.']);
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
                wp_send_json_success(['message' => 'Group updated successfully!']);
            } else {
                wp_send_json_error(['message' => 'Failed to update group name.']);
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
                wp_send_json_success(['message' => 'Group created successfully!']);
            } else {
                wp_send_json_error(['message' => 'Failed to create group.']);
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
        $table = $wpdb->prefix . 'recipient_group';
    
        $result = $wpdb->delete(
            $table,
            ['id' => $group_id],
            ['%d']
        );
    
        if ($result) {
            wp_send_json_success(['message' => 'Group deleted successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete group or group not found.']);
        }
    }
   
    // Callback function for get recipients
    public function orthoney_get_recipient_handler($userId = '', $groupId = '') {
        
        $exclude_ids  = '';
        $group_id = '';
        if($userId != ''){
            $user = $userId;
        }else{
            // Verify nonce for security
            check_ajax_referer('oam_nonce', 'security');    
            $user = sanitize_text_field($_POST['user']);
            if(isset($_POST['newids']) AND $_POST['newids'] != ''){
                $new_ids  = sanitize_text_field($_POST['newids']);
                $exclude_ids = array_map('intval', explode(',', $new_ids));
            }
        }

        if(isset($_POST['groupId'])){
            $group_id  = isset($_POST['groupId'])? sanitize_text_field($_POST['groupId']) : '';
        }else{
            $group_id = $groupId;
        }
        
        if($user != 0 OR $user != ''){
            global $wpdb;

            $tableStart ='<table><thead><tr><th>First Name</th><th>Last Name</th><th>Address 1</th><th>Address 2</th><th>City</th><th>State</th><th>Country</th><th>Zipcode</th><th>Action</th></tr></thead><tbody>';

            $tableFailStart ='<button id="download-failed-recipient-csv">Download CSV for the Fail Recipient</button><table><thead><tr><th>First Name</th><th>Last Name</th><th>Address 1</th><th>Address 2</th><th>City</th><th>State</th><th>Country</th><th>Zipcode</th><th>Reasons</th><th>Action</th></tr></thead><tbody>';

            $tableDuplicateStart ='<table><thead><tr><th>First Name</th><th>Last Name</th><th>Address 1</th><th>Address 2</th><th>City</th><th>State</th><th>Country</th><th>Zipcode</th><th>Verified</th><th>Action</th></tr></thead><tbody>';
            
            $tableEnd = '</tbody></table>';
            $successHtml = '';
            $newDataHtml = '';
            $failHtml = '';
            $duplicateHtml = '';
            $successData = [];
            $newData = [];
            $failData = [];
            $duplicateGroups = [];

            $recipient_table = $wpdb->prefix . 'recipient_temp';
            $group_relationships_table = $wpdb->prefix . 'recipient_group_relationships_temp';
    
           
            // Get all records
            if(empty($exclude_ids) ){
                if($group_id != ''){
                    $query = $wpdb->prepare("
                        SELECT r.* 
                        FROM $recipient_table r
                        INNER JOIN $group_relationships_table rg 
                            ON r.id = rg.recipient_id
                        WHERE r.user_id = %d 
                        AND r.visibility = %d 
                        AND rg.group_id = %d
                    ", $user, 1, $group_id);

                    $allRecords = $wpdb->get_results($query);
                }else{
                    $query = $wpdb->prepare("
                        SELECT r.* 
                        FROM $recipient_table r
                        INNER JOIN $group_relationships_table rg 
                            ON r.id = rg.recipient_id
                        WHERE r.user_id = %d 
                        AND r.visibility = %d
                    ", $user, 1);
                    $allRecords = $wpdb->get_results($query);
                }
            }else{
                // Generate placeholders for each ID
                $placeholders = implode(',', array_fill(0, count($exclude_ids), '%d'));
                $query = '';
                $includeQuery = '';

                if($group_id != 0 OR $group_id != ''){
                    $query = $wpdb->prepare("
                        SELECT r.* 
                        FROM $recipient_table r
                        INNER JOIN $group_relationships_table rg 
                            ON r.id = rg.recipient_id
                        WHERE r.user_id = %d 
                        AND r.visibility = %d 
                        AND rg.group_id = %d 
                        AND r.id NOT IN ($placeholders)
                    ", $user, 1, $group_id, ...$exclude_ids);

                    $includeQuery = $wpdb->prepare("
                        SELECT r.* 
                        FROM $recipient_table r
                        INNER JOIN $group_relationships_table rg 
                            ON r.id = rg.recipient_id
                        WHERE r.user_id = %d 
                        AND rg.group_id = %d 
                        AND r.id IN ($placeholders)
                    ", array_merge([$user, $group_id], $exclude_ids));


                }else{
                    $query = $wpdb->prepare(
                        "SELECT * 
                        FROM $recipient_table 
                        WHERE user_id = %d 
                        AND visibility = %d 
                        AND id NOT IN ($placeholders)",
                        $user, 1, ...$exclude_ids
                    );
                    $includeQuery = $wpdb->prepare("
                        SELECT * 
                        FROM $recipient_table 
                        WHERE user_id = %d 
                        AND id IN ($placeholders)
                    ", array_merge([$user], $exclude_ids));
                }
                
                $allRecords = $wpdb->get_results($query);
                $newAllRecords = $wpdb->get_results($includeQuery);

                // Execute queries
                $allRecords = $wpdb->get_results($query);
                $newAllRecords = $wpdb->get_results($includeQuery);


                // Generate new data HTML
                if(!empty($newAllRecords)){
                    $newDataHtml .= '<div><h4>'.count($newAllRecords).' new Recipient(s)!</h4></div>';
                    foreach ($newAllRecords as $data) {
                        $id = $data->id;
                        $newDataHtml .= '<tr data-id="'.$id.'">';
                        $newDataHtml .= '<td>'.$data->first_name.'</td>';
                        $newDataHtml .= '<td>'.$data->last_name.'</td>';
                        $newDataHtml .= '<td>'.$data->address_1.'</td>';
                        $newDataHtml .= '<td>'.$data->address_2.'</td>';
                        $newDataHtml .= '<td>'.$data->city.'</td>';
                        $newDataHtml .= '<td>'.$data->state.'</td>';
                        $newDataHtml .= '<td>'.$data->country.'</td>';
                        $newDataHtml .= '<td>'.$data->zipcode.'</td>';
                        $newDataHtml .= '<td><button class="editRecipient" data-popup="#recipient-manage-popup">Edit</button><button class="deleteRecipient">Delete</button></td>';
                        $newDataHtml .= '</tr>';
                    }
                }
            }
            
            // First pass: Group records by their unique combination
            $recordMap = [];
            
            foreach ($allRecords as $record) {
                // Create unique key for comparison
                $key = $record->first_name . '|' . 
                       $record->last_name . '|' . 
                       $record->state . '|' . 
                       $record->country . '|' . 
                       $record->zipcode;
                
                // Store record in the map
                if (!isset($recordMap[$key])) {
                    $recordMap[$key] = [];
                }
                $recordMap[$key][] = $record;
            }
    
            // Second pass: Categorize records
            foreach ($recordMap as $key => $records) {
                if (count($records) > 1) {
                    // This is a group of duplicates
                    $duplicateGroups[] = $records;
                } else {
                    // Single record - add to success or fail based on verified status
                    $record = $records[0];
                    if ($record->verified == 1) {
                        $successData[] = $record;
                    } else {
                        $failData[] = $record;
                    }
                }
            }
    
            // Generate Success HTML
            if(!empty($successData)){
                $successHtml .= '<div><h4>'.count($successData).' Recipient Successfully!</h4></div>';
                foreach ($successData as $data) {
                    $id = $data->id;
                    $successHtml .= '<tr data-id="'.$id.'">';
                    $successHtml .= '<td>'.$data->first_name.'</td>';
                    $successHtml .= '<td>'.$data->last_name.'</td>';
                    $successHtml .= '<td>'.$data->address_1.'</td>';
                    $successHtml .= '<td>'.$data->address_2.'</td>';
                    $successHtml .= '<td>'.$data->city.'</td>';
                    $successHtml .= '<td>'.$data->state.'</td>';
                    $successHtml .= '<td>'.$data->country.'</td>';
                    $successHtml .= '<td>'.$data->zipcode.'</td>';
                    $successHtml .= '<td><button class="editRecipient" data-popup="#recipient-manage-popup">Edit</button><button class="deleteRecipient">Delete</button></td>';
                    $successHtml .= '</tr>';
                }
            }
    
            // Generate Fail HTML
            if(!empty($failData)){
                $failHtml .= '<div><h4>'.count($failData).' Recipient failed!</h4></div>';
                foreach ($failData as $data) {
                    $id = $data->id;
                    $reasons = '';
                    if (!empty($data->reasons)) {
                        $reasons = implode(", ", json_decode($data->reasons, true));
                    }
                    
                    $failHtml .= '<tr data-id="'.$id.'">';
                    $failHtml .= '<td>'.$data->first_name.'</td>';
                    $failHtml .= '<td>'.$data->last_name.'</td>';
                    $failHtml .= '<td>'.$data->address_1.'</td>';
                    $failHtml .= '<td>'.$data->address_2.'</td>';
                    $failHtml .= '<td>'.$data->city.'</td>';
                    $failHtml .= '<td>'.$data->state.'</td>';
                    $failHtml .= '<td>'.$data->country.'</td>';
                    $failHtml .= '<td>'.$data->zipcode.'</td>';
                    $failHtml .= '<td>'.$reasons.'</td>';
                    $failHtml .= '<td><button class="editRecipient" data-popup="#recipient-manage-popup">Edit</button><button class="deleteRecipient">Delete</button></td>';
                    $failHtml .= '</tr>';
                }
            }
    
            // Generate Duplicate HTML - now showing grouped duplicates
            if(!empty($duplicateGroups)){
                $totalDuplicates = 0;
                foreach ($duplicateGroups as $group) {
                    $totalDuplicates += count($group);
                }

                $bulkMargeButtonHtml = '';
                if($group_id != ''){
                    $bulkMargeButtonHtml = '<button id="bulkMargeRecipient">Bulk Marge Recipient</button>';
                }
                
                $duplicateHtml .= '<div><h4>'.$totalDuplicates.' Duplicate Entries Found in '.count($duplicateGroups).' Groups!</h4>'.$bulkMargeButtonHtml.'</div>';
                
                foreach ($duplicateGroups as $groupIndex => $group) {
                    $duplicateHtml .= '<tr class="group-header" data-count="'.count($group).'" data-group="'.($groupIndex + 1).'"><td colspan="10">Duplicate Group '.($groupIndex + 1).' ('. count($group).' records)</td></tr>';
                    foreach ($group as $data) {
                        $id = $data->id;
                        $duplicateHtml .= '<tr data-verify="'.$data->verified.'" data-id="'.$id.'" data-group="'.($groupIndex + 1).'">';
                        $duplicateHtml .= '<td>'.$data->first_name.'</td>';
                        $duplicateHtml .= '<td>'.$data->last_name.'</td>';
                        $duplicateHtml .= '<td>'.$data->address_1.'</td>';
                        $duplicateHtml .= '<td>'.$data->address_2.'</td>';
                        $duplicateHtml .= '<td>'.$data->city.'</td>';
                        $duplicateHtml .= '<td>'.$data->state.'</td>';
                        $duplicateHtml .= '<td>'.$data->country.'</td>';
                        $duplicateHtml .= '<td>'.$data->zipcode.'</td>';
                        $duplicateHtml .= '<td>'.(($data->verified == 0) ? 'NO': 'Yes').'</td>';
                        $duplicateHtml .= '<td><button class="editRecipient" data-popup="#recipient-manage-popup">Edit</button><button class="deleteRecipient">Delete</button></td>';
                        $duplicateHtml .= '</tr>';
                    }
                }
            }
    
            // Wrap tables with headers
            if($newDataHtml != ''){
                $newDataHtml = $tableStart.$newDataHtml.$tableEnd;
            }
            if($successHtml != ''){
                $successHtml = $tableStart.$successHtml.$tableEnd;
            }
            if($failHtml != ''){
                $failHtml = $tableFailStart.$failHtml.$tableEnd;
            }
            if($duplicateHtml != ''){
                $duplicateHtml = $tableDuplicateStart.$duplicateHtml.$tableEnd;
            }
    
            // Calculate total count of all records including duplicates
            $totalDuplicates = 0;
            foreach ($duplicateGroups as $group) {
                $totalDuplicates += count($group);
            }

            $resultData = [
                'newData'         => $newDataHtml,
                'successData'     => $successHtml,
                'failData'        => $failHtml,
                'duplicateData'   => $duplicateHtml,
                'duplicateGroups' => $duplicateGroups, // Raw duplicate groups data
                'totalCount'      => count($successData) + count($failData) + $totalDuplicates,
                'successCount'    => count($successData),
                'failCount'       => count($failData),
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

    // Callback function for insert recipient 
    public function orthoney_insert_recipient_handler() {
        // Verify nonce for security
        check_ajax_referer('oam_nonce', 'security');
    
        global $wpdb;
        $recipient_table = $wpdb->prefix . 'recipient_temp';
        $group_table = $wpdb->prefix . 'recipient_group';
        $group_relationships_table = $wpdb->prefix . 'recipient_group_relationships_temp';
    
        // Chunk processing parameters
        $chunk_size = 2;
        $current_chunk = isset($_POST['current_chunk']) ? intval($_POST['current_chunk']) : 0;
        $group_name = isset($_POST['group_name']) && !empty($_POST['group_name'])  ? sanitize_text_field($_POST['group_name']) : (isset($_FILES['csv_file']) ? sanitize_file_name($_FILES['csv_file']['name']) : '');
        $greeting = isset($_POST['greeting']) ? sanitize_text_field($_POST['greeting']) : '';
    
        // Ensure the file is uploaded successfully
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            
        // File upload code Start
        // File upload directory setup
        $csv_dir = WP_CONTENT_DIR . '/recipient_csv/';
        
        // Ensure directory exists
        if (!file_exists($csv_dir)) {
            wp_mkdir_p($csv_dir);
        }

        $file_name = sanitize_file_name($_FILES['csv_file']['name']);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);

        // Validate file extension
        if (strtolower($file_extension) !== 'csv') {
            wp_send_json_error(['message' => 'Invalid file type. Only CSV files are allowed.']);
            wp_die();
        }

        $unique_file_name = uniqid('recipient_', true) . '.csv';
        $file_path = $csv_dir . $unique_file_name;

        // Move uploaded file securely
        if (!move_uploaded_file($_FILES['csv_file']['tmp_name'], $file_path)) {
            wp_send_json_error(['message' => 'Error saving the file on the server.']);
            wp_die();
        }

        // File upload code End

            // $file = sanitize_file_name($_FILES['csv_file']['name']);
            if (($handle = fopen($file_path, 'r')) !== false) {
                $header = fgetcsv($handle);
    
                $required_columns = ['first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'country', 'zipcode'];
    
                foreach ($required_columns as $column) {
                    if (!in_array($column, $header)) {
                        fclose($handle);
                        wp_send_json_error(['message' => 'Invalid CSV format. Missing required columns.']);
                        wp_die();
                    }
                }
    
                if ($current_chunk == 0) {
                    // Create the new group entry
                    $wpdb->insert($group_table, [
                        'name' => $group_name,
                        'greeting' => $greeting,
                        'csv_path' => WP_CONTENT_URL . '/recipient_csv/'.$unique_file_name,
                        'user_id' => get_current_user_id(),
                    ]);
    
                    // Get the newly created group_id
                    $group_id = $wpdb->insert_id;
    
                    if (!$group_id) {
                        fclose($handle);
                        wp_send_json_error(['message' => 'Failed to create recipient group.']);
                        wp_die();
                    }
    
                } else {
                    // Retrieve group_id from previous operations
                    $group_id = intval($_POST['group_id']);
                }
    
                $total_rows = count(file($file_path)) - 1;
                
                for ($i = 0; $i < ($current_chunk * $chunk_size); $i++) {
                    if (fgetcsv($handle) === false) {
                        wp_send_json_success([
                            'message' => 'Import complete',
                            'finished' => true,
                            'total_rows' => $total_rows,
                            'progress' => 100,
                            'group_id' => $group_id,
                        ]);
                        wp_die();
                    }
                }
    
                $wpdb->query('START TRANSACTION');
    
                try {
                    $processed_rows = 0;
                    $error_rows = [];
    
                    for ($i = 0; $i < $chunk_size; $i++) {
                        $row = fgetcsv($handle);
    
                        if ($row === false) {
                            break;
                        }
    
                        $failure_reasons = [];
    
                        if (count($row) !== count($header)) {
                            $failure_reasons[] = 'Column count mismatch';
                            $error_rows[] = $current_chunk * $chunk_size + $processed_rows;
                            continue;
                        }
    
                        $data = array_combine($header, $row);
    
                        foreach ($required_columns as $field) {
                            if (empty($data[$field])) {
                                $failure_reasons[] = "Missing {$field}";
                            }
                        }
    
                        if (!preg_match("/^[A-Za-z\s-]+$/", $data['first_name'])) {
                            $failure_reasons[] = "Invalid first name format";
                        }
                        if (!preg_match("/^[A-Za-z\s-]+$/", $data['last_name'])) {
                            $failure_reasons[] = "Invalid last name format";
                        }
                        if (strlen($data['zipcode']) < 5) {
                            $failure_reasons[] = "Invalid zipcode length";
                        }
    
                        $insert_data = [
                            'user_id'    => get_current_user_id(),
                            'first_name' => sanitize_text_field($data['first_name']),
                            'last_name'  => sanitize_text_field($data['last_name']),
                            'address_1'  => sanitize_textarea_field($data['address_1']),
                            'address_2'  => sanitize_textarea_field($data['address_2']),
                            'city'       => sanitize_text_field($data['city']),
                            'state'      => sanitize_text_field($data['state']),
                            'country'    => sanitize_text_field($data['country']),
                            'zipcode'    => sanitize_text_field($data['zipcode']),
                            'greeting'   => sanitize_text_field($data['greeting']),
                            'timestamp'  => current_time('mysql'),
                            'visibility' => 1,
                            'verified'   => empty($failure_reasons) ? 1 : 0,
                            'reasons'    => empty($failure_reasons) ? null : json_encode($failure_reasons),
                        ];
    
                        $insert_result = $wpdb->insert($recipient_table, $insert_data);
    
                        if ($insert_result !== false) {
                            $processed_rows++;
                            $inserted_id = $wpdb->insert_id;
                            $relationship_data = [
                                'user_id'         => get_current_user_id(),
                                'recipient_id'    => $inserted_id,
                                'group_id'        => $group_id,
                                'timestamp'       => current_time('mysql'),
                            ];
                            $relationship_result = $wpdb->insert($group_relationships_table, $relationship_data);

                        } else {
                            $error_rows[] = $current_chunk * $chunk_size + $processed_rows;
                        }
                    }
    
                    $wpdb->query('COMMIT');
                    fclose($handle);
    
                    $next_chunk = $current_chunk + 1;
                    $progress = min(100, round(($next_chunk * $chunk_size) / $total_rows * 100));
                    $is_finished = ($next_chunk * $chunk_size) >= $total_rows;
    
                    wp_send_json_success([
                        'message' => "Chunk processed successfully",
                        'processed_rows' => $processed_rows,
                        'error_rows' => $error_rows,
                        'next_chunk' => $next_chunk,
                        'total_rows' => $total_rows,
                        'progress' => $progress,
                        'finished' => $is_finished,
                        'group_id' => $group_id,
                        'user' => get_current_user_id(),
                    ]);
    
                } catch (Exception $e) {
                    $wpdb->query('ROLLBACK');
                    fclose($handle);
                    wp_send_json_error(['message' => $e->getMessage()]);
                }
            } else {
                wp_send_json_error(['message' => 'Error opening the file.']);
            }
        } else {
            wp_send_json_error(['message' => 'No file uploaded or upload error occurred.']);
        }
    
        wp_die();
    }
    
    // Callback function for bulk deleted recipient 
    public function orthoney_bulk_deleted_recipient_handler() {
        
        if (isset($_POST['ids']) && is_array(json_decode(stripslashes($_POST['ids'])))) {
            global $wpdb;
            $table = $wpdb->prefix . 'recipient_temp';
            $ids = json_decode(stripslashes($_POST['ids']));
    
            
            global $wpdb;
            $table = $wpdb->prefix . 'recipient_temp';
            $ids = json_decode(stripslashes($_POST['ids']));
            
            // Prepare the IDs for the IN clause, ensuring they are integers
            $ids_placeholder = implode(',', array_map('intval', $ids));

            // Update the records based on the IDs array
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE $table SET visibility = 0 WHERE id IN ($ids_placeholder)",
                    $ids
                )
            );

            if ($result !== false) {
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
        $groupId = 0;
        // Check if the recipient id is provided
        if (empty($_POST['id'])) {
            wp_send_json_error(['message' => 'Recipient is required.']);
        }
    
        $id = sanitize_text_field($_POST['id']);
        if(isset($_POST['groupId']) && $_POST['groupId'] != 'null'){
            $groupId = sanitize_text_field($_POST['groupId']);
        }
    
        // Your logic to insert the recipient into the database
        global $wpdb;
        $table = $wpdb->prefix . 'recipient_temp';

        $result = $wpdb->update(
            $table,
            ['visibility' => 0],
            ['id' => $id]
        );
    
        if ($result) {
            wp_send_json_success([
                'message' => 'Recipient deleted successfully!',
                'groupId' => $groupId,
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
    
        if (empty($recipientID)) {
            $response = ['success' => false, 'message' => 'Invalid recipient ID.'];
            return !empty($recipient) ? json_encode($response) : wp_send_json_error($response);
        }
    
        // Table name
        $recipient_table = $wpdb->prefix . 'recipient_temp';
    
        // Fetch recipient record as an associative array
        $record = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $recipient_table WHERE id = %d", $recipientID),
            ARRAY_A
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
        
        // Check for required fields
        $required_fields = ['first_name', 'last_name', 'address_1', 'city', 'state', 'country', 'zipcode'];
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                wp_send_json_error([
                    'message' => 'Missing required fields.',
                ]);
            }
        }
    
        // Sanitize and prepare common data
        $data = [
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name'  => sanitize_text_field($_POST['last_name']),
            'address_1'  => sanitize_textarea_field($_POST['address_1']),
            'address_2'  => isset($_POST['address_2']) ? sanitize_textarea_field($_POST['address_2']) : '',
            'city'       => sanitize_text_field($_POST['city']),
            'state'      => sanitize_text_field($_POST['state']),
            'country'    => sanitize_text_field($_POST['country']),
            'zipcode'    => sanitize_text_field($_POST['zipcode']),
            'reasons'    => null,
            'verified'   => 1,
            'visibility'  => 1,
            'timestamp'  => current_time('mysql'),
        ];
    
        global $wpdb;
        $table = $wpdb->prefix . 'recipient_temp';
        $group_relationships_table = $wpdb->prefix . 'recipient_group_relationships_temp';
    
        $status = '';
        $recipient_id = '';
        // Determine if this is an update or insert operation
        if (isset($_POST['recipient_id']) AND $_POST['recipient_id'] != '') {
            // Update existing recipient
            $recipient_id = absint($_POST['recipient_id']);
            $result = $wpdb->update(
                $table,
                $data,
                ['id' => $recipient_id]
            );
            $status = 'update';
    
            $success_message = 'Recipient details updated successfully!';
        } else {
            // Insert new recipient
            $status = 'new';
            $data['user_id'] = get_current_user_id();
            
            $result = $wpdb->insert($table, $data);
            $recipient_id = $wpdb->insert_id;

            $relationship_data = [
                'user_id'         => get_current_user_id(),
                'recipient_id'    => $recipient_id,
                'group_id'        => sanitize_text_field($_POST['group_id']),
                'timestamp'       => current_time('mysql'),
            ];
    
            $relationship_result = $wpdb->insert($group_relationships_table, $relationship_data);

            $success_message = 'Recipient details added successfully!';
        }

        // Handle the result
        if ($result !== false) {
            wp_send_json_success([
                'status' => $status,
                'user' => get_current_user_id(),
                'recipient_id' => $recipient_id,
                'message' => $success_message,
            ]);
        } else {
            wp_send_json_error([
                'message' => $wpdb->last_error ?: 'Database operation failed.',
            ]);
        }
    }
    
}
new OAM_Ajax();