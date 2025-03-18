<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;

class OAM_Helper{
	/**
	 * Define tables
	 **/
    public static $order_process_table;
    public static $order_process_recipient_table;
    public static $order_process_recipient_activate_log_table;
    public static $files_activate_log_table;
    
    public static $group_table;
    public static $group_recipient_table;
    public static $yith_wcaf_affiliates_table;
    public static $oh_affiliate_customer_relation_table;

    public static $users_table;
    
    // Define directories
    public static $all_uploaded_csv_dir;
    public static $process_recipients_csv_dir;
    public static $process_recipients_csv_url;
    public static $group_recipients_csv_dir;



    public static function init() {
        global $wpdb;

        self::$order_process_table = $wpdb->prefix . 'oh_order_process';
        self::$order_process_recipient_table = $wpdb->prefix . 'oh_order_process_recipient';
        self::$order_process_recipient_activate_log_table = $wpdb->prefix . 'oh_order_process_recipient_activate_log';
        self::$files_activate_log_table = $wpdb->prefix . 'oh_files_upload_activity_log';
        
        self::$group_table = $wpdb->prefix . 'oh_group';
        self::$group_recipient_table = $wpdb->prefix . 'oh_group_recipient';
        self::$yith_wcaf_affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';
        self::$oh_affiliate_customer_relation_table = $wpdb->prefix . 'oh_affiliate_customer_relation';

        self::$users_table = $wpdb->prefix . 'users';

        self::$all_uploaded_csv_dir = WP_CONTENT_DIR . '/all-uploaded-files/';
        self::$process_recipients_csv_dir = WP_CONTENT_DIR . '/process-recipients-files/';
        self::$process_recipients_csv_url = WP_CONTENT_URL . '/process-recipients-files/';
        self::$group_recipients_csv_dir = WP_CONTENT_DIR . '/group-recipients-files/';
    }

	public function __construct() {}

    public static function manage_affiliates_content($search = '', $filter = '') {
        global $wpdb;
        $yith_wcaf_affiliates_table = self::$yith_wcaf_affiliates_table;
        $oh_affiliate_customer_relation_table = self::$oh_affiliate_customer_relation_table;
        $users_table = self::$users_table;

        $user_id = get_current_user_id();

        // Base SQL query
        $queryParts = ["a.enabled = 1"];
        $queryParams = [];
        $blocked_affiliates = [];

        // Apply search filter if necessary
        if (!empty($search)) {
            $queryParts[] = "(a.token LIKE %s OR u.display_name LIKE %s)";
            array_push($queryParams, '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%');
        }

        if (!empty($filter) && $filter!= 'All') {

            // Get blocked affiliates for the logged-in user
            $blocked_affiliates = $wpdb->get_col($wpdb->prepare(
                "SELECT affiliate_id FROM $oh_affiliate_customer_relation_table WHERE user_id = %d",
                $user_id
            ));

            // Ensure blocked_affiliates is an array
            if (!is_array($blocked_affiliates)) {
                $blocked_affiliates = [];
            }

            // Apply filter logic correctly
            if ($filter === 'blocked') {
                if (!empty($blocked_affiliates)) {
                    $placeholders = implode(',', array_fill(0, count($blocked_affiliates), '%s'));
                    $queryParts[] = "a.ID IN ($placeholders)";
                    $queryParams = array_merge($queryParams, $blocked_affiliates);
                }
            } elseif ($filter === 'unblocked') {
                if (!empty($blocked_affiliates)) {
                    $placeholders = implode(',', array_fill(0, count($blocked_affiliates), '%s'));
                    $queryParts[] = "a.ID NOT IN ($placeholders)";
                    $queryParams = array_merge($queryParams, $blocked_affiliates);
                }
            }
        }

        // Build final SQL query
        $sql = "SELECT a.ID, a.token, u.display_name , a.user_id
        FROM $yith_wcaf_affiliates_table AS a
        JOIN $users_table AS u ON a.user_id = u.ID
        WHERE " . implode(" AND ", $queryParts) . " 
        ORDER BY u.display_name ASC";

        // Prepare and execute query
        if (!empty($queryParams)) {
            $query = $wpdb->prepare($sql, ...$queryParams);
        } else {
            $query = $sql;
        }
        $affiliates = $wpdb->get_results($query);

        if (!$affiliates) {
            return json_encode(['success' => false, 'message'=> 'No blocked affiliates found.']);
        }

        $resultData = [
            'affiliates' => $affiliates,
            'blocked_affiliates' => $blocked_affiliates,
        ];

        return json_encode(['success' => true, 'data'=> $resultData]);

    }

    public static function get_user_agent() {
        return isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Unknown';
    }
    
    public static function get_user_ip() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim(reset($ip_list)); // Get the first valid IP
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
    }

    

    public static function get_table_recipient_content($dataArray, $customGreeting, $reverify = 0, $duplicate = 0) {
        $html = '';
        $reasonsHtml = '';
        if(!empty($dataArray)){
            foreach ($dataArray as $data) {
                $id = $data->id;
                $reasons = '';

                if($data->verified == 0){
                    $reasonsHtml = '<div>No';
                    if (!empty($data->reasons)) {
						
                        $reasons = implode(", ", json_decode($data->reasons, true));
                        if($reasons != ''){
							$reasonsHtml = '<div class="tooltip">Failed';
                            $reasonsHtml .= '<span class="tooltiptext">'.$reasons.'</span></div>';
                        }
                    }
                    $reasonsHtml .= '</div>';
                }
                
                $addressParts = array_filter([$data->address_1, $data->address_2, $data->city, $data->state, $data->zipcode]);
                if (!empty($addressParts)) {
                    $addressPartsHtml = '<td>' . implode(', ', $addressParts) . '</td>';
                } else {
                    $addressPartsHtml = '<td>-</td>';
                }
                
                $greetingHtml = '<div>N/A</div>'; // Default value

                if (!empty($data->greeting)) {
                    $greetingHtml = '<div>' . html_entity_decode($data->greeting) . '</div>';
                } elseif (!empty($customGreeting)) {
                    $greetingHtml = '<div>' . $customGreeting . '</div>';
                }

                
                $html .= '<tr data-id="'.$id.'">';
                $html .= '<td><input type="hidden" name="'.(($reverify == 1) ? "recipientAddressIds[]" : "recipientIds[]").'" value="'.$id.'">'.$data->full_name.'</td>';
                $html .= '<td>'.($data->company_name != "" ? $data->company_name : '') .'</td>';
                
                $html .= $addressPartsHtml;
                $html .= '<td>'.((empty($data->quantity) || $data->quantity <= 0) ? '0' : $data->quantity).'</td>';
                if($reverify != 1){
                    $html .= '<td>'.(($data->verified == 0) ? $reasonsHtml: 'Passed').'</td>';
                }
                // $html .= '<td>'.$greetingHtml.'</td>';
                
                

                if($reverify == 1){
                    $html .= '<td>';
                    if($data->address_verified == 0){
                        // $html .= '<button class="reverifyAddress w-btn us-btn-style_1" style="padding:10px"><small>Reverify Address</small></button>';
                    }
                    $html .= ($data->address_verified == 0 ? ' <button class="editRecipient far fa-edit" data-popup="#recipient-manage-popup" data-address_verified="1"></button>' : '') .'<button data-recipientname="'.$data->full_name.'" class="deleteRecipient far fa-trash"></button>';
                    $html .= '</td>';
                    
                }else{
                    $html .= '<td>';
                    if($duplicate == 1){
                        $html .= '<button class="keep_this_and_delete_others" data-recipientname="'.$data->full_name.'"  data-popup="#recipient-manage-popup">Keep this and delete others</button>';
                    }
                    $html .= '<button class="viewRecipient far fa-eye" data-popup="#recipient-view-details-popup"></button><button class="editRecipient far fa-edit" data-popup="#recipient-manage-popup"></button><button data-recipientname="'.$data->full_name.'" class="deleteRecipient far fa-trash"></button>';
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
        }
        return $html;
    }


    public static function log_and_return($status, $method,$process_id, $message, $file_path = '') {
        global $wpdb;
        
        $insert_data = [
            'user_id'     => get_current_user_id(),
            'related_id'  => $process_id,
            'name'        => sanitize_text_field($file_path ? basename($file_path) : ''),
            'status'      => $status ? 1 : 0,
            'method'      => sanitize_text_field($method),
            'update_log'  => sanitize_textarea_field($message),
            'user_agent'  => OAM_Helper::get_user_agent(),
            'user_ip'     => OAM_Helper::get_user_ip(),
            'timestamp'   => current_time('mysql'),
        ];
        
        $wpdb->insert(OAM_Helper::$files_activate_log_table, $insert_data);
        
        return ['success' => $status, 'message' => $message, 'file_path' => $file_path];
    }

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
    
        if (!in_array($file_extension, ['csv', 'xlsx', 'xls'])) {
            return self::log_and_return(false, $method, $process_id, 'Only CSV, XLSX, and XLS files are allowed. Please upload a valid file.');
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
    
        $required_columns = OH_REQUIRED_COLUMNS;
        $required_columns_lower = array_map('strtolower', $required_columns);
    
        if ($file_extension === 'csv') {
            // Validate CSV file structure
            if (($handle = fopen($file_path, 'r')) !== false) {
                $header = fgetcsv($handle);
                fclose($handle);
                $header_lower = array_map('strtolower', $header);
    
                $missing_columns = array_diff($required_columns_lower, $header_lower);
                if (!empty($missing_columns)) {
                    return self::log_and_return(false, $method, $process_id, 'Invalid CSV format. Missing required columns: ' . implode(', ', $missing_columns), $file_path);
                }
            }
        } elseif ($file_extension === 'xlsx') {
            require_once OH_PLUGIN_DIR_PATH. 'libs/SimpleXLSX/SimpleXLSX.php';
    
            if ($xlsx = SimpleXLSX::parse($file_path)) {
                $header = $xlsx->rows()[0];
                $header_lower = array_map('strtolower', $header);
    
                $missing_columns = array_diff($required_columns_lower, $header_lower);
                if (!empty($missing_columns)) {
                    return self::log_and_return(false, $method, $process_id, 'Invalid XLSX format. Missing required columns: ' . implode(', ', $missing_columns), $file_path);
                }
            } else {
                return self::log_and_return(false, $method, $process_id, 'Failed to parse XLSX file: ' . SimpleXLSX::parseError(), $file_path);
            }
        } elseif ($file_extension === 'xls') {
            require_once OH_PLUGIN_DIR_PATH. 'libs/SimpleXLS/SimpleXLS.php';
    
            if ($xls = SimpleXLS::parse($file_path)) {
                $rows = $xls->rows();
                if (!$rows || empty($rows[0])) {
                    return self::log_and_return(false, $method, $process_id, 'XLS file is empty or unreadable.', $file_path);
                }
            
                $header = $rows[0];
                $header_lower = array_map('strtolower', $header);
                $missing_columns = array_diff($required_columns_lower, $header_lower);
            
                if (!empty($missing_columns)) {
                    return self::log_and_return(false, $method, $process_id, 'Invalid XLS format. Missing required columns: ' . implode(', ', $missing_columns), $file_path);
                }
            } else {
                return self::log_and_return(false, $method, $process_id, 'Failed to parse XLS file: ' . SimpleXLS::parseError(), $file_path);
            }
            
        }
    
        return self::log_and_return(true, $method, $process_id, 'File uploaded and validated.', $file_path);
    }
    
    public static function order_process_recipient_activate_log($recipient_id, $status, $changes) {
        if($recipient_id != '' && $recipient_id != 0){
            global $wpdb;
            $order_process_recipient_activate_log_table = OAM_Helper::$order_process_recipient_activate_log_table;
            $data = [
                'user_id'         => get_current_user_id(),
                'recipient_id'    => $recipient_id,
                'type'            => sanitize_text_field($status),
                'update_log'      => sanitize_textarea_field($changes),
                'user_agent'      => OAM_Helper::get_user_agent(),
                'user_ip'         => OAM_Helper::get_user_ip(),
                'timestamp'       => current_time('mysql'),
            ];

            $result = $wpdb->insert($order_process_recipient_activate_log_table, $data);
        }
        
    }
    
    public static function get_order_process_address_verified_recipient($order_process_id, $duplicate = 1){
        global $wpdb;
        $order_process_table           = OAM_Helper::$order_process_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        
        $customGreeting = "";
        $group_name="";

        $verifyRecordHtml = '';
        $unverifiedRecordHtml = '';
        
        $unverifiedTableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Action</th></tr></thead><tbody>';

        $verifyTableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Action</th></tr></thead><tbody>';


        $tableEnd = '</tbody></table>';

        
        $getGreetingQuery = $wpdb->prepare(
            "SELECT greeting, name FROM {$order_process_table} WHERE id = %d",
            $order_process_id
        );
        
        $getGreeting = $wpdb->get_row($getGreetingQuery);
        
        
        if ($getGreeting) {
            $group_name = $getGreeting->name;
            $customGreeting = $getGreeting->greeting;
        }

        $recipientQuery = $wpdb->prepare(
            "SELECT * FROM {$order_process_recipient_table} WHERE pid = %d AND visibility = %d AND verified = %d" ,
            $order_process_id, 1 , 1
        );
        
        $recipients = $wpdb->get_results($recipientQuery);
        // Separate verified and unverified recipients in PHP
        $verifiedRecipients = [];
        $unverifiedRecipients = [];
        
        if($duplicate == 0){
            foreach ($recipients as $recipient) {
                // Create unique key for comparison
                $key = $recipient->full_name . '|' . 
                        str_replace($recipient->address_1, ',' , '' ). ' ' . str_replace($recipient->address_2 , ',' , '') . '|' . 
                    $recipient->city . '|' . 
                    $recipient->state . '|' . 
                    $recipient->zipcode;
                
                // Store record in the map
                if (!isset($recordMap[$key])) {
                    $recordMap[$key] = [];
                }
                $recordMap[$key][] = $recipient;
            }
    
            foreach ($recordMap as $key => $records) {
                if (count($records) > 1) {

                } else {
                    $finalrecipients[] = $records[0];
                }
            }
        } else{
            $finalrecipients = $recipients;
        }
        
        
        foreach ($finalrecipients as $recipient) {
            
            if ($recipient->address_verified == 1) {
                $verifiedRecipients[] = $recipient;
            } else {
                $unverifiedRecipients[] = $recipient;
            }
        }
        
        
        if (!empty($unverifiedRecipients)) {
            $unverifiedRecordHtml = self::get_table_recipient_content($unverifiedRecipients, $customGreeting, 1);
            
        }
        if (!empty($verifiedRecipients)) {
            $verifyRecordHtml = self::get_table_recipient_content($verifiedRecipients, $customGreeting, 1);

        }

        if($unverifiedRecordHtml != ''){
            $unverifiedRecordHtml = $unverifiedTableStart.$unverifiedRecordHtml.$tableEnd;
        }
        if($verifyRecordHtml != ''){
            $verifyRecordHtml = $verifyTableStart.$verifyRecordHtml.$tableEnd;
        }

        $resultData = [
            'groupName' => $group_name,
            'unverifiedRecordCount' => count($unverifiedRecipients),
            'verifiedRecordCount'   => count($verifiedRecipients),
            'verifiedData'          => $verifyRecordHtml,
            'unverifiedData'        => $unverifiedRecordHtml,
            'totalCount'            => count($unverifiedRecipients) + count($verifiedRecipients),
            'csvCount'            => count($recipients),
        ];

        return json_encode(
            ['success' => true, 'data' => $resultData]
        );

    }

    public static function get_group_name($group_id) {
        global $wpdb;
        $group_table = self::$group_table;
        $group_name = '';

        $getGroupNameQuery = $wpdb->prepare(
            "SELECT name FROM {$group_table} WHERE id = %d",
            $group_id
        );

        $getGroupName = $wpdb->get_row($getGroupNameQuery);

        if ($getGroupName) {
            $group_name = $getGroupName->name;
        }

        return $group_name;

    }

    public static function get_us_states_list($select_state) {
        $states = WC()->countries->get_states('US'); // Get states for the US
        $states_html = '';
    
        if(!empty($states)){
            foreach ($states as $shortname => $fullname) {
                $selected = '';
                if($select_state == $shortname){
                    $selected = 'selected';
                }
                if($shortname != 'AA' && $shortname != 'AE' && $shortname != 'AP'){
                    $states_html .= '<option '.$selected.' value="'.$shortname.'">'.$fullname.'('.$shortname.')</option>';
                }else{
                    $states_html .= '<option '.$selected.' value="'.$shortname.'">'.$fullname.'</option>';
                }
            }
        }
    
        return $states_html;
    }

    public static function get_recipient_form(){ ?>
        <div id="recipient-manage-form" class="site-form" >
            <form class="grid-two-col" novalidate>
                <input type="hidden" id="pid" name="pid" value="<?php echo isset($_GET['pid']) ? $_GET['pid'] : '' ?>">
                <input type="hidden" id="recipient_id" name="recipient_id" value="">

                <div class="form-row gfield--width-half">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required data-error-message="Please enter your full name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" required data-error-message="Please enter a company name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_1">Mailing Address:</label>
                    <input type="text" id="address_1" name="address_1" required data-error-message="Please enter a mailing address.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_2">Suite/Apt#:</label>
                    <input type="text" id="address_2" name="address_2">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="city">City:</label>
                    <input type="text" id="city" name="city" required data-error-message="Please enter a city.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="state">State:</label>
                    <select id="state" name="state" required data-error-message="Please select a state.">
                        <option value="" disable>Select state</option>
                        <?php echo self::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : ""); ?>
                    </select>
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode:</label>
                    <input type="text" id="zipcode" name="zipcode" required data-error-message="Please enter a valid zipcode.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="quantity">Quantity:</label>
                    <div class="quantity">
                        <button class="minus" aria-label="Decrease" type="button">&minus;</button>
                        <input type="number" class="input-box" min="1" value="1" max="10000" required id="quantity" name="quantity" data-error-message="Please enter a valid quantity.">
                        <button class="plus" aria-label="Increase" type="button">&plus;</button>
                    </div>
                    <span class="error-message"></span>
                </div>

                <div class="textarea-div form-row gfield--width-full">
                    <label for="greeting">Add a Greeting:</label>
                    <textarea id="greeting" name="greeting"></textarea>
                    <div class="char-counter"><span>250</span> characters remaining</div>
                </div>

                <div class="footer-btn gfield--width-full">
                <button type='button' class=" w-btn us-btn-style_1" data-lity-close>Cancel</button>
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
        <?php
    }

    public static function verify_recipient_address_popup(){
        ?>
        <div id="verify-recipient-address-popup" class="lity-hide black-mask full-popup">
            <p>Out of the 10 records uploaded via CSV, 8 were successfully added. However, 2 records failed to upload and 2 repeated orders.
            Please confirm if you would like to proceed with the successfully added records.</p>
           
        </div>
        <?php
    }
    
    public static function view_details_recipient_popup(){
        ?>
        <div id="recipient-view-details-popup" class="lity-hide black-mask full-popup popup-show">
            <h2>Recipient Details</h2>
            <div class="recipient-view-details-wrapper"></div>
            <button type='button' class="w-btn us-btn-style_1" data-lity-close>close</button>
        </div>
        <?php
    }
    public static function manage_recipient_popup(){
        ?>
        <div id="recipient-manage-popup" class="lity-hide black-mask full-popup popup-show">
            <h2>Recipient Details</h2>
            <?php 
            echo self::get_recipient_form();
            ?>
        </div>
        <?php
    }
    
    public static function getGroup() {
        ?>
        <div class="recipient-group-section">
            <div class="recipient-group-list">
                <?php 
                $groups = self::getGroupList();
                if(!empty($groups)){
                ?>
                <select name="groups-list" class="groups-list">
                    <option value="">Select Group</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo esc_attr($group->id); ?>">
                            <?php echo esc_html($group->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
    
                <div class="edit-group-form-wrapper" style="display: none;">
                    <div class="edit-group-form" style="display: none;">
                        <?php echo self::getCreateGroupForm('edit'); ?>
                        <div class="response-msg"></div>
                    </div>
                </div>
                <?php } else{
                    echo 'No group exists. Please create a group first!';
                    ?>
                    <div class="recipient-group-form" style="display:none">
                    <?php echo self::getCreateGroupForm(); ?>
                    <div class="response-msg"></div>
            </div>
            <button class="createGroupFormButton">Create New Group</button>
                    <?php 
                } ?>
            </div>
        </div>
        <?php
    }
    
    /**
	 * Helper function that get Affiliates lists
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return array $groups is array with user data.
	 */
    public static function getAffiliateList($user_id = ''){
        global $wpdb;
        
        // Table name (sanitize the table name)
        $orm_affiliate_table = $wpdb->prefix . 'orm_affiliate';
        
        // Prepare the query based on whether user_id is provided
        if($user_id == ''){
            $query = "SELECT * FROM $orm_affiliate_table";  // No need to prepare if there's no dynamic value
            $groups = $wpdb->get_results($query);
        } else {
            $query = $wpdb->prepare("SELECT * FROM $orm_affiliate_table WHERE id = %d", $user_id); // Prepare the user_id part
            $groups = $wpdb->get_results($query);
        }
        
        return $groups;
    }

	/**
	 * Helper function that get group lists
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return array $groups is array with user data.
	 */

	public static function getGroupList($user_id = ''){
        $groups = array();
        global $wpdb;
        if($user_id == ''){
            // Get current user ID
            $user_id = get_current_user_id();
        }
        // Table name
        $group_table = self::$group_table;

        if($user_id == ''){
            $groups = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM $group_table"));
        }else{
            $groups = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM $group_table WHERE user_id = %d
            ", $user_id));
        }
        
        return $groups;
    }
    
    
	/**
	 * Helper function that get form for the create and edit group
	 *
	 * @return string
	 */
	public static function getCreateGroupForm($edit = ''){
        $label = 'Create Group';
        $status = 'create';
        if($edit == 'edit'){
            $status = 'edit';
            $label = 'Edit Group';
        }
        echo '<form class="groupForm" data-formType="'.$status.'">
            <input type="text" name="group_name" class="group_name" placeholder="Enter group name" required />
            <input type="hidden" name="group_id" class="group_id" />
            <button type="button" name="create_group" class="createGroupButton">'.$label.'</button>
        </form>';
    }

    public static function validate_address($delivery_line_1, $delivery_line_2, $city, $state, $zipcode) {
        $auth_id = '0fdfc34a-4087-0f9d-ae9c-afb52f987e78';
        $auth_token = 'RXTN0yzOth5dFffkvvb6';

    
        $url = "https://us-street.api.smarty.com/street-address?"
             . http_build_query([
                 'auth-id'    => $auth_id,
                 'auth-token' => $auth_token,
                 'street'     => trim($delivery_line_1 . ' ' . $delivery_line_2),
                 'city'       => $city,
                 'state'      => $state,
                 'zipcode'    => $zipcode,
             ]);
    
        $response = wp_remote_get($url);
    
        if (is_wp_error($response)) {
            $response = ['success' => false, 'message' => 'Error fetching address validation.'];
        }
    
       

        $body = wp_remote_retrieve_body($response);

        $data = json_decode($body, true);
    
    
        if (empty($data)) {
            $response = ['success' => false, 'message' => 'Invalid address.'];
        }
    
        // Extract DPV match code
        $dpv_match_code = $data[0]['analysis']['dpv_match_code'] ?? '';
    
        // Check DPV match code for validity
        if ($dpv_match_code !== 'N' && !empty($dpv_match_code)) {
            $response = ['success' => true, 'message' => 'Valid and deliverable address.'];
        }else{
            $response = ['success' => false, 'message' => 'Invalid address format.'];
        }

        return json_encode($response);
    }

    public static function multi_validate_address($address = []) {
        $auth_id = '0fdfc34a-4087-0f9d-ae9c-afb52f987e78';
        $auth_token = 'RXTN0yzOth5dFffkvvb6';
        $url = "https://us-street.api.smarty.com/street-address?auth-id={$auth_id}&auth-token={$auth_token}";
    
        $body = json_encode($address);
    
        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8'
            ],
            'body' => $body,
            'method' => 'POST',
            'timeout' => 30,
            'sslverify' => false
        ]);
    
        if (is_wp_error($response)) {
            return $response->get_error_message();
        }
    
        return wp_remote_retrieve_body($response);
    }
    

}
new OAM_Helper();
OAM_Helper::init();
/*
backup code for the old code
public static function getGroup(){
        ?>
        <div class="recipient-group-section">
            <div class="recipient-group-list">
                <?php $groups = self::getGroupList();
                echo '<select name="groups-list" class="groups-list">';
                echo '<option value="">Select Group</option>';
                foreach ($groups as $group) {
                    echo '<option value="' . esc_attr($group->id) . '">' . esc_html($group->name) . '</option>';
                }
                echo '</select>';
                ?>
                <div class="edit-group-form-wrapper" style="display:none">
                <div class="edit-group-form" style="display:none">
                    <?php echo self::getCreateGroupForm('edit'); ?>
                    <div class="response-msg"></div>
                </div>
                <button class="editGroupFormButton">Edit Group</button>
                <button class="uploadRecipientButton">Add Recipient using (SCV)</button>
                <!-- <button class="deleteGroupButton">Delete Group</button> -->
                </div>
            </div>
            <div class="recipient-group-form" style="display:none">
            <?php echo self::getCreateGroupForm(); ?>
            <div class="response-msg"></div>
            </div>
            <div class="upload-recipient-form" style="display:none">
                <?php echo self::getuploadget_recipient_form(); ?>
            <div class="response-msg"></div>
            </div>
            <button class="createGroupFormButton">Create New Group</button>
        </div>
        <?php
    }
 */