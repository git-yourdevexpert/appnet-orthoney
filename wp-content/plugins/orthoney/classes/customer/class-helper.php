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
    public static $recipient_order_table;
    public static $order_process_recipient_table;
    public static $order_process_recipient_activate_log_table;
    public static $files_activate_log_table;
    
    public static $group_table;
    public static $group_recipient_table;
    public static $yith_wcaf_affiliates_table;
    public static $oh_affiliate_customer_linker; 

    public static $users_table;
    public static $users_meta_table;
    
    // Define directories
    public static $all_uploaded_csv_dir;
    public static $customer_dashboard_link;
    public static $organization_dashboard_link;
    public static $process_recipients_csv_dir;
    public static $process_recipients_csv_url;
    public static $group_recipients_csv_dir;
    public static $date_format;
    public static $time_format;

    public static function init() {
        global $wpdb;

        self::$order_process_table = $wpdb->prefix . 'oh_order_process';
        self::$recipient_order_table = $wpdb->prefix . 'oh_recipient_order';;
        self::$order_process_recipient_table = $wpdb->prefix . 'oh_order_process_recipient';
        self::$order_process_recipient_activate_log_table = $wpdb->prefix . 'oh_order_process_recipient_activate_log';
        self::$files_activate_log_table = $wpdb->prefix . 'oh_files_upload_activity_log';
        
        self::$group_table = $wpdb->prefix . 'oh_group';
        self::$group_recipient_table = $wpdb->prefix . 'oh_group_recipient';
        self::$yith_wcaf_affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';
        
        self::$oh_affiliate_customer_linker = $wpdb->prefix . 'oh_affiliate_customer_linker'; 

        self::$users_table = $wpdb->prefix . 'users';
        self::$users_meta_table = $wpdb->prefix . 'usermeta';

        self::$customer_dashboard_link = CUSTOMER_DASHBOARD_LINK;
        self::$organization_dashboard_link = ORGANIZATION_DASHBOARD_LINK;
        self::$all_uploaded_csv_dir = WP_CONTENT_DIR . '/all-uploaded-files/';
        self::$process_recipients_csv_dir = WP_CONTENT_DIR . '/process-recipients-files/';
        self::$process_recipients_csv_url = WP_CONTENT_URL . '/process-recipients-files/';
        self::$group_recipients_csv_dir = WP_CONTENT_DIR . '/group-recipients-files/';

        self::$date_format = get_option('date_format');
        self::$time_format = get_option('time_format');
    }

	public function __construct() {}
    
    
    public static function get_recipient_by_pid($pid) {
        global $wpdb;
        $process = $wpdb->get_row($wpdb->prepare(
            "SELECT data FROM " . OAM_Helper::$order_process_table . " WHERE id = %d", 
            $pid
        ));

        $setData = json_decode($process->data) ?? [];

        $address_verified = $setData->status;
        $recipientIds = isset($setData->recipientAddressIds) ? $setData->recipientAddressIds  : [];
        $placeholders = implode(',', array_fill(0, count($recipientIds), '%d'));
      

        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;

        if($address_verified == 1){
            
            $recipients = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$order_process_recipient_table} 
                    WHERE  pid = %d AND visibility = %d AND verified = %d AND address_verified = %d AND id IN ($placeholders)",
                    array_merge([ $pid, 1, 1, 1], $recipientIds)
                )
            );
        
        }else{
            $recipients = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$order_process_recipient_table} 
                    WHERE  pid = %d AND visibility = %d AND verified = %d AND id IN ($placeholders)",
                    array_merge([ $pid, 1, 1], $recipientIds)
                )
            );
        }

        return $recipients;
    }
    
    public static function get_filtered_orders_by_id($user_id, $orderid = 0) {
        global $wpdb;
    
        $orders_table = $wpdb->prefix . 'wc_orders';
        $filtered_orders = [];
    
        $main_orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE customer_id = %d AND parent_order_id = 0 AND id = %d AND status != %s ORDER BY date_updated_gmt DESC",
            $user_id, $orderid, 'wc-checkout-draft'
        ));
    
        foreach ($main_orders as $main_data) {
            $order_id = $main_data->id;
            $main_status = $main_data->status;
    
            $main_order = wc_get_order($order_id);
            if (!$main_order) continue;
    
            $order_type = 'Multi Address';
            $total_quantity = 0;
    
            foreach ($main_order->get_items() as $item) {
                if ($item->get_meta('single_order', true) == 1) {
                    $order_type = 'Single Address';
                }
                $total_quantity += $item->get_quantity();
            }
    
            $row_builder = 'build_export_order_row';
            $row_data = OAM_Helper::$row_builder($main_data, $main_order, $order_type, $total_quantity);
    
            if ($order_type === 'Single Address') {
                $filtered_orders[] = $row_data;
            }
    
            // Sub-orders
            $sub_orders = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $orders_table WHERE customer_id = %d AND parent_order_id = %d",
                $user_id, $order_id
            ));
    
            foreach ($sub_orders as $sub_data) {
                $sub_order = wc_get_order($sub_data->id);
                if (!$sub_order) continue;
    
                $sub_total_quantity = 0;
                foreach ($sub_order->get_items() as $item) {
                    $sub_total_quantity += $item->get_quantity();
                }
    
                $filtered_orders[] = OAM_Helper::$row_builder($sub_data, $sub_order, $order_type, $sub_total_quantity, $main_order);
            }
        }
    
      
        return $filtered_orders;
    }
    


    public static  function get_jars_orders($user_id, $table_order_type, $custom_order_type, $custom_order_status, $search, $is_export = false, $page, $length) {
        global $wpdb;
        $orders_table = $wpdb->prefix . 'wc_orders'; 
        $filtered_orders = [];
        $limit = $length;
        $offset = $page;
            // If no cached data, build the query
            if (current_user_can('administrator')) {
                $jarsorder = $wpdb->get_results(
                    $wpdb->prepare(
                        "
                        SELECT 
                            recipient_order_id AS `jar_no`,
                            order_id AS `order_no`,
                            created_date AS `date`,
                            full_name AS `billing_name`,
                            full_name AS `shipping_name`,
                            affiliate_token AS `affiliate_code`,
                            quantity AS `total_jar`,    
                            order_type AS `type`
                        FROM {$wpdb->prefix}oh_recipient_order
                        LIMIT %d OFFSET %d
                        ",
                        $limit, 
                        $offset
                    ),
                    ARRAY_A
                );
          } else {
            $jarsorder = $wpdb->get_results(
                $wpdb->prepare(
                    "
                    SELECT 
                        recipient_order_id AS `jar_no`,
                        order_id AS `order_no`,
                        created_date AS `date`,
                        full_name AS `billing_name`,
                        full_name AS `shipping_name`,
                        affiliate_token AS `affiliate_code`,
                        quantity AS `total_jar`,    
                        order_type AS `type`
                    FROM {$wpdb->prefix}oh_recipient_order
                    WHERE user_id = %d
                    LIMIT %d OFFSET %d
                    ",
                    $user_id, // Assuming $user_id is the variable you are comparing with
                    $limit, 
                    $offset
                ),
                ARRAY_A
            );
          }
          $jarsorder = array_map(function($order) {
            $order['status'] = 'n/a';
            $order['price'] = '$14.00'; // Set price here (change 10.00 to the actual price calculation)
            $order['action'] = 'n/a'; // Set price here (change 10.00 to the actual price calculation)

            return $order;
        }, $jarsorder);
        
        $filtered_orders = $jarsorder;
        return $filtered_orders;

    }

        
    public static  function get_filtered_orders($user_id, $table_order_type, $custom_order_type, $custom_order_status, $search, $is_export = false, $page, $length) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'wc_orders';
    
        $filtered_orders = [];
    // echo $search;

        $limit = $length;
        // $page = isset($page) ? (int)$page : 1;
         $offset = $page;

        $raw_key = current_user_can('administrator')
        ? "main_orders_admin_{$limit}_{$offset}"
        : "main_orders_user_{$user_id}_{$limit}_{$offset}";

    // Hash it to make it shorter and cache-safe
    $transient_key = 'main_orders_' . md5($raw_key);

    // Try to get cached result
    $main_orders = get_transient($transient_key);
    $main_orders = false;
        if ($main_orders === false) {
            // If no cached data, build the query
            if (current_user_can('administrator')) {
                $sql = $wpdb->prepare(
                    "SELECT * FROM $orders_table
                    WHERE status != %s
                    ORDER BY date_updated_gmt DESC
                    LIMIT %d OFFSET %d",
                    'wc-checkout-draft', $limit, $offset
                );
            } else {
                $sql = $wpdb->prepare(
                    "SELECT * FROM $orders_table
                    WHERE customer_id = %d
                    AND status != %s
                    ORDER BY date_updated_gmt DESC
                    LIMIT %d OFFSET %d",
                    $user_id, 'wc-checkout-draft', $limit, $offset
                );
            }

            // Run query and cache the results for 5 minutes (300 seconds)
            $main_orders = $wpdb->get_results($sql);
            set_transient($transient_key, $main_orders, 300); // 5 minutes
        }
    // echo $sql;
    
    

        foreach ($main_orders as $main_data) {
            $order_id = $main_data->id;
            $main_status = $main_data->status;

            if ($custom_order_status !== 'all' && $custom_order_status !== $main_status) {
                continue;
            }

            $main_order = wc_get_order($order_id);
            if (!$main_order) continue;

            $order_type = 'Multi Address';
            $total_quantity = 0;

            foreach ($main_order->get_items() as $item) {
                if ($item->get_meta('single_order', true) == 1) {
                    $order_type = 'Single Address';
                }
                $total_quantity += $item->get_quantity();
            }

            if (
                ($custom_order_type === 'single_address' && $order_type !== 'Single Address') ||
                ($custom_order_type === 'multiple_address' && $order_type !== 'Multi Address')
            ) {
                continue;
            }

            $matches_search_main = empty($search) ||
                stripos((string)$order_id, $search) !== false ||
                stripos($main_order->get_billing_first_name(), $search) !== false ||
                stripos($main_order->get_billing_last_name(), $search) !== false ||
                stripos($main_order->get_shipping_first_name(), $search) !== false||
                stripos($main_order->get_shipping_last_name(), $search) !== false;

            $row_builder = $is_export ? 'build_export_order_row' : 'build_order_row';
            $row_data = OAM_Helper::$row_builder($main_data, $main_order, $order_type, $total_quantity);

            if ($table_order_type === 'main_order') {
                if ($matches_search_main) {
                    $filtered_orders[] = $row_data;
                }
            }
        }

        return $filtered_orders;
    }



    // Helper to build row array for a main or sub order
    public static function build_order_row($order_data, $order_obj, $order_type, $total_quantity, $parent_order = null) {
        global $wpdb;
        $user_id = get_current_user_id();
        $orders_table = $wpdb->prefix . 'wc_orders';
    
        $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($order_data->date_created_gmt));
        $billing_name = $order_obj->get_billing_first_name() . ' ' . $order_obj->get_billing_last_name();
        $shipping_name = $order_obj->get_shipping_first_name() . ' ' . $order_obj->get_shipping_last_name();
        $referral_id = $order_obj->get_meta('_yith_wcaf_referral', true) ?: 'Orthoney';
        $order_total = wc_price($order_obj->get_total());
        $sub_order_id =  OAM_COMMON_Custom::get_order_meta($order_data->id, '_orthoney_OrderID');
        $jarsorder_count = 1;
        if($sub_order_id){
        $jarsorder_count = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$wpdb->prefix}oh_recipient_order
                WHERE order_id = %d
                ",
                $sub_order_id
            )
        );
    }


        
        // Status HTML
        $status_html = '';
        if ($order_type === 'Multi Address' && $order_data->parent_order_id == 0) {
            $status_counts = $wpdb->get_results($wpdb->prepare(
                "SELECT status, COUNT(*) as count FROM $orders_table WHERE customer_id = %d AND parent_order_id = %d GROUP BY status",
                $user_id, $order_data->id
            ));
            foreach ($status_counts as $status) {
                $status_html .= sprintf(
                    '<span class="%s">(%d) %s</span> ',
                    esc_attr($status->status),
                    esc_html($status->count),
                    esc_html(wc_get_order_status_name($status->status))
                );
            }
        } else {
            $status_html .= sprintf(
                '<span class="%s">%s</span> ',
                esc_attr($order_data->status),
                esc_html(wc_get_order_status_name($order_data->status))
            );
        }
    
        $resume_url = esc_url(CUSTOMER_DASHBOARD_LINK . "view-order/". ($order_data->parent_order_id == 0 ? $order_data->id : $order_data->parent_order_id));
    
        return [
            'jar_no' => esc_html($order_data->id),
            'order_no' => ($order_data->parent_order_id == 0) ? $order_data->id : $order_data->parent_order_id,
            'date' => esc_html($created_date),
            'billing_name' => esc_html($billing_name),
            'shipping_name' => esc_html($shipping_name),
            'affiliate_code' => esc_html($referral_id),
            'total_jar' => esc_html($total_quantity),
            'total_recipient' => $jarsorder_count,
            'type' => esc_html($order_type),
            'status' => !empty($status_html) ? $status_html : 'Recipient Order is Preparing.',
            'price' => $order_total,
            // 'action' =>
            //     '<a data-tippy="View Order" href="' . $resume_url . '" class="far fa-eye"></a>' .
            //     ($order_data->parent_order_id == 0 ? '<a data-tippy="Download Invoice" href="#" class="far fa-download"></a><button class="download_csv_by_order_id far fa-file-csv" data-tippy="Download CSV" data-orderid="'.(($order_data->parent_order_id == 0) ? $order_data->id : $order_data->parent_order_id).'"></button>' : '') .
            //     (empty($status_html) && ( $order_data->parent_order_id == 0) ? '<button>Suborder is created</button>' : '')
            'action' => ''
            
        ];
    }

    public static function build_export_order_row($order_data, $order_obj, $order_type, $total_quantity, $parent_order = null) {
        global $wpdb;
        $user_id = get_current_user_id();
        $orders_table = $wpdb->prefix . 'wc_orders';
    
        $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($order_data->date_created_gmt));
        $billing_name = $order_obj->get_billing_first_name() . ' ' . $order_obj->get_billing_last_name();
        $shipping_name = $order_obj->get_shipping_first_name() . ' ' . $order_obj->get_shipping_last_name();
        $referral_id = $order_obj->get_meta('_yith_wcaf_referral', true) ?: 'Orthoney';
        $order_total = $order_obj->get_total();
    
        // Status HTML
        $status_html = '';
        if ($order_type === 'Multi Address' && $order_data->parent_order_id == 0) {
            $status_counts = $wpdb->get_results($wpdb->prepare(
                "SELECT status, COUNT(*) as count FROM $orders_table WHERE customer_id = %d AND parent_order_id = %d GROUP BY status",
                $user_id, $order_data->id
            ));
            foreach ($status_counts as $status) {
                $status_html .= sprintf(
                    '(%d) %s ',
                    esc_html($status->count),
                    esc_html(wc_get_order_status_name($status->status))
                );
            }
        } else {
            $status_html .= sprintf(
                '%s ',
                esc_html(wc_get_order_status_name($order_data->status))
            );
        }
    
        $resume_url = esc_url(CUSTOMER_DASHBOARD_LINK . "view-order/". ($order_data->parent_order_id == 0 ? $order_data->id : $order_data->parent_order_id));
    
        return [
            'jar_no' => esc_html($order_data->id),
            'order_no' => ($order_data->parent_order_id == 0) ? esc_html($order_data->id) : esc_html($order_data->parent_order_id),
            'date' => esc_html($created_date),
            'billing_name' => esc_html($billing_name),
            'shipping_name' => esc_html($shipping_name),
            'affiliate_code' => esc_html($referral_id),
            'total_jar' => esc_html($total_quantity),
            'total_recipient' => count($order_obj->get_items()),
            'type' => esc_html($order_type),
            'status' => !empty($status_html) ? $status_html : 'Recipient Order is Preparing.',
            'price' => $order_total,
            
        ];
    }

    public static function manage_affiliates_content($search = '', $filter = '', $return_type = 0) {
        global $wpdb;
        $yith_wcaf_affiliates_table = self::$yith_wcaf_affiliates_table;
        $oh_affiliate_customer_linker = self::$oh_affiliate_customer_linker;
        $users_table = self::$users_table;

        $search_term = sanitize_text_field($search);
        $user_id = get_current_user_id();

        $affiliates = $wpdb->get_results($wpdb->prepare(
            "SELECT affiliate_id, status, token FROM $oh_affiliate_customer_linker WHERE customer_id = %d",
            $user_id
        ));
        
        if (!empty($affiliates)) {
            // Extract affiliate IDs
            $ids = array_column($affiliates, 'affiliate_id');
        
            if (!empty($ids)) {
                // Convert array into a comma-separated list of integers
                $affiliates_list = implode(',', array_map('intval', $ids));
        
                $query = $wpdb->prepare(
                    "SELECT a.ID, a.token, u.display_name, a.user_id 
                     FROM {$yith_wcaf_affiliates_table} AS a 
                     JOIN {$users_table} AS u ON a.user_id = u.ID 
                     WHERE u.ID IN ($affiliates_list) 
                     AND (u.display_name LIKE %s OR a.token LIKE %s) AND a.token != %s ",
                    "%{$search_term}%", "%{$search_term}%", ""
                );
        
                $user_info = $wpdb->get_results($query);
            } else {
                $user_info = [];
            }
        } else {
            $user_info = []; // No affiliates found
        }

        $resultData = [
            'user_info' => $user_info,
            'affiliates' => $affiliates,
        ];

        return json_encode(['success' => true, 'data'=> $resultData]);
        wp_die();

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

    public static function get_table_recipient_content($dataArray, $customGreeting, $reverify = 0, $duplicate = 0, $alreadyOrder = 0) {
        $html = '';
        $reasonsHtml = '';
        global $wpdb;
        
        $year = date('Y');

        $group_recipient_table = OAM_Helper::$group_recipient_table;
        $start_date = "$year-01-01 00:00:00";
        $end_date = "$year-12-31 23:59:59";

        if(!empty($dataArray)){
            foreach ($dataArray as $data) {
                $id = $data->id;
                $reasons = '';

                $addressParts = array_filter([$data->address_1, $data->address_2, $data->city, $data->state, $data->zipcode]);
                // Fetch records using `DATETIME` format for filtering
                $result = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, address_1, address_2 ,city, state, zipcode FROM {$group_recipient_table} 
                    WHERE full_name = %s 
                    
                    AND user_id = %d
                    AND `timestamp` BETWEEN %s AND %s",
                    $data->full_name, get_current_user_id(),$start_date, $end_date
                ));

                // Normalize and merge input address values
                $search_address = implode(' ', $addressParts); // Merge input address fields
                $search_address = str_replace([',', '.'], '', $search_address);
                $search_address = trim($search_address);

                // Filter results and extract only IDs
                $filtered_ids = array_map(function ($record) use ($search_address) {
                    // Merge database fields for comparison
                    $merged_address = implode(' ', array_filter([$record->address_1, $record->address_2, $record->city, $record->state, $record->zipcode]));
                    $merged_address = str_replace([',', '.'], '', $merged_address);
                    $merged_address = trim($merged_address);

                    return strcasecmp($search_address, $merged_address) === 0 ? $record->id : null;
                }, $result);

                // Remove null values
                $filtered_ids = array_filter($filtered_ids);

                // Convert to a comma-separated string
                $filtered_ids_string = implode(',', $filtered_ids);
                $AlreadyOrderHtml = '';
                if($alreadyOrder == 0){
                 $AlreadyOrderHtml = (!empty($filtered_ids) ? '<button data-recipientname="'.$data->full_name.'" data-tippy="Recipient has been verified but has already placed an order this season." style="color:red" class="alreadyOrderButton btn-underline">Already Ordered</button>' : '');
                }
                if($data->verified == 0){
                    $reasonsHtml = '<div>No';
                    if (!empty($data->reasons)) {
						
                        $reasons = implode(", ", json_decode($data->reasons, true));
                        if($reasons != ''){
							$reasonsHtml = '<div class="tooltip" data-tippy="'.$reasons.'">Failed</span></div>';
                        }
                    }
                    $reasonsHtml .= '</div>';
                }
                
                
                if (!empty($addressParts)) {
                    $addressPartsHtml = '<td data-label="Address"><div class="thead-data">Address</div>' . implode(', ', $addressParts) . '</td>';
                } else {
                    $addressPartsHtml = '<td data-label="Address"><div class="thead-data">Address</div>-</td>';
                }
                
                $greetingHtml = '<div>N/A</div>'; // Default value

                if (!empty($data->greeting)) {
                    $greetingHtml = '<div>' . html_entity_decode($data->greeting) . '</div>';
                } elseif (!empty($customGreeting)) {
                    $greetingHtml = '<div>' . $customGreeting . '</div>';
                }

                $html .= '<tr data-alreadyorder="'.(!empty($filtered_ids) ? implode(',', $filtered_ids) : '').'" data-id="'.$id.'" '.(($duplicate != 1)? 'data-verify="'.$data->verified.'" data-group="'.$duplicate.'"': '').'>';
                if($alreadyOrder != 0){
                    $html .= '<td data-label="Order Id"><div class="thead-data">Order Id</div>'.($data->order_id != "" ? $data->order_id : '') .'</td>';
                    $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->timestamp));
                    $html .= '<td data-label="Create Date"><div class="thead-data">Create Date</div>'.$created_date.'</td>';
                }
                
                    $html .= '<td data-label="Full Name"><div class="thead-data">Full Name</div><input type="hidden" name="'.(($reverify == 1) ? "recipientAddressIds[]" : "recipientIds[]").'" value="'.$id.'">'.$data->full_name.'</td>';
                
                $html .= '<td data-label="Company name"><div class="thead-data">Company name</div>'.($data->company_name != "" ? $data->company_name : '') .'</td>';
                
                $html .= $addressPartsHtml;
                $html .= '<td data-label="Quantity"><div class="thead-data">Quantity</div>'.((empty($data->quantity) || $data->quantity <= 0) ? '0' : $data->quantity).'</td>';
               
                // $html .= '<td>'.$greetingHtml.'</td>';

                if($alreadyOrder == 0){
                    if($reverify != 1){
                        $html .= '<td data-label="Status"><div class="thead-data">Status</div>'.(($data->verified == 0) ? $reasonsHtml: 'Passed').'</td>';
                    }
                    if($reverify == 1){
                        $html .= '<td data-label="Action"><div class="thead-data">Action</div>';
                        if($data->address_verified == 0){
                            // $html .= '<button class="reverifyAddress w-btn us-btn-style_1" style="padding:10px"><small>Reverify Address</small></button>';
                        }
                        $html .= ($data->address_verified == 0 ? ' <button class="editRecipient far fa-edit" data-tippy="Edit Recipient Details" data-popup="#recipient-manage-popup" data-address_verified="1"></button>' : '') .'<button data-recipientname="'.$data->full_name.'" class="deleteRecipient far fa-trash" data-tippy="Remove Recipient"></button>';
                        $html .= '</td>';
                        
                    }else{
                        $html .= '<td data-label="Action"><div class="thead-data">Action</div>';
                        if($duplicate == 1){
                            $html .= '<button class="keep_this_and_delete_others" data-recipientname="'.$data->full_name.'"  data-popup="#recipient-manage-popup" data-tippy="Keep this and delete others">Keep this and delete others</button>';
                        }
                        $html .= '<button class="viewRecipient far fa-eye" data-tippy="View Recipient Details" data-popup="#recipient-view-details-popup"></button><button class="editRecipient far fa-edit" data-tippy="Edit Recipient Details" data-popup="#recipient-manage-popup"></button><button data-recipientname="'.$data->full_name.'" data-tippy="Remove Recipient" class="deleteRecipient far fa-trash"></button>'.$AlreadyOrderHtml;
                        $html .= '</td>';
                    }
                }
                $html .= '</tr>';
            }
        }
        return $html;
    }

    public static function log_and_return($status, $method,$process_id, $message, $file_path = '') {
        global $wpdb;
        
        $insert_data = [
            'user_id'        => get_current_user_id(),
            'process_by'     => OAM_COMMON_Custom::old_user_id(),
            'related_id'     => $process_id,
            'name'           => sanitize_text_field($file_path ? basename($file_path) : ''),
            'status'         => $status ? 1 : 0,
            'method'         => sanitize_text_field($method),
            'update_log'     => sanitize_textarea_field($message),
            'user_agent'     => OAM_Helper::get_user_agent(),
            'user_ip'        => OAM_Helper::get_user_ip(),
            'timestamp'      => current_time('mysql'),
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
    
    public static function order_process_recipient_activate_log($recipient_id, $status, $changes, $method = 'process') {
        if($recipient_id != '' && $recipient_id != 0){
            
            global $wpdb;
            $order_process_recipient_activate_log_table = OAM_Helper::$order_process_recipient_activate_log_table;
            $data = [
                'user_id'         => get_current_user_id(),
                'process_by'      => intval(OAM_COMMON_Custom::old_user_id()),
                'recipient_id'    => $recipient_id,
                'type'            => sanitize_text_field($status),
                'method'          => sanitize_text_field($method),
                'update_log'      => sanitize_textarea_field($changes),
                'user_agent'      => OAM_Helper::get_user_agent(),
                'user_ip'         => OAM_Helper::get_user_ip(),
                'timestamp'       => current_time('mysql'),
            ];

            $result = $wpdb->insert($order_process_recipient_activate_log_table, $data);
        }
        
    }
    
    public static function get_order_process_address_verified_recipient($order_process_id, $duplicate = 1 , $recipientAddressIds = []){
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

        if(!empty($recipientAddressIds)){
            $placeholders = implode(',', array_fill(0, count($recipientAddressIds), '%d'));
            $recipientQuery = $wpdb->prepare(
                "SELECT * FROM {$order_process_recipient_table} WHERE pid = %d AND id IN ($placeholders)" ,
                array_merge([$order_process_id], $recipientAddressIds)
            );
        }else{

            $recipientQuery = $wpdb->prepare(
                "SELECT * FROM {$order_process_recipient_table} WHERE pid = %d AND visibility = %d AND verified = %d" ,
                $order_process_id, 1 , 1
            );
        }
        
        $recipients = $wpdb->get_results($recipientQuery);
        // Separate verified and unverified recipients in PHP
        $verifiedRecipients = [];
        $unverifiedRecipients = [];
        
        foreach ($recipients as $recipient) {
            
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
            'csvCount'              => count($recipients),
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
                    $states_html .= '<option '.$selected.' value="'.$shortname.'">'.$fullname.' ('.$shortname.')</option>';
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
                    <label for="full_name">Full Name: <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required data-error-message="Please enter your full name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" data-error-message="Please enter a company name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_1">Mailing Address: <span class="required">*</span></label>
                    <input type="text" id="address_1" name="address_1" required data-error-message="Please enter a mailing address.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_2">Suite/Apt#:</label>
                    <input type="text" id="address_2" name="address_2">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="city">City: <span class="required">*</span></label>
                    <input type="text" id="city" name="city" required data-error-message="Please enter a city.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="state">State: <span class="required">*</span></label>
                    <select id="state" name="state" required data-error-message="Please select a state.">
                        <option value="" disable>Select state</option>
                        <?php echo self::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : ""); ?>
                    </select>
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode: <span class="required">*</span></label>
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
                <button type='button' class=" w-btn us-btn-style_4" data-lity-close>Cancel</button>
                <button type="submit">Add New Recipient Details</button>
                </div>
            </form>
        </div>
        <?php
    }

    public static function get_recipient_order_form(){ ?>
        <div id="recipient-manage-order-form" class="site-form" >
            <form class="grid-two-col" novalidate>
            <input type="hidden" id="order_id" name="order_id" value="">
                <div class="form-row gfield--width-half">
                    <label for="full_name">Full Name: <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required data-error-message="Please enter your full name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" data-error-message="Please enter a company name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_1">Mailing Address:<span class="required">*</span></label>
                    <input type="text" id="address_1" name="address_1" required data-error-message="Please enter a mailing address.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_2">Suite/Apt#:</label>
                    <input type="text" id="address_2" name="address_2">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="city">City:<span class="required">*</span></label>
                    <input type="text" id="city" name="city" required data-error-message="Please enter a city.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="state">State:<span class="required">*</span></label>
                    <select id="state" name="state" required data-error-message="Please select a state.">
                        <option value="" disable>Select state</option>
                        <?php echo self::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : ""); ?>
                    </select>
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode:<span class="required">*</span></label>
                    <input type="text" id="zipcode" name="zipcode" required data-error-message="Please enter a valid zipcode.">
                    <span class="error-message"></span>
                </div>

               

                <div class="textarea-div form-row gfield--width-full">
                    <label for="greeting">Add a Greeting:</label>
                    <textarea id="greeting" name="greeting"></textarea>
                    <div class="char-counter"><span>250</span> characters remaining</div>
                </div>

                <div class="footer-btn gfield--width-full">
                <button type='button' class=" w-btn us-btn-style_4" data-lity-close>Cancel</button>
                <button type="submit">Edit Recipient Order Details</button>
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
            
        </div>
        <?php
    }
    
    public static function manage_recipient_popup(){
        ?>
        <div id="recipient-manage-popup" class="lity-hide black-mask full-popup popup-show">
            <h3>Recipient Details</h3>
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
                SELECT * FROM $group_table WHERE visibility = %d", 1));
        }else{
            $groups = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM $group_table WHERE user_id = %d AND visibility = %d
            ", $user_id, 1));
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

    public static function group_dashboard_widget($title = "", $limit = 3, $link = '') {
        global $wpdb;
        $user_id = get_current_user_id();
        $group_table = OAM_Helper::$group_table;
    
    
        // Fetch limited orders
        $query = $wpdb->prepare(
            "SELECT * FROM {$group_table} WHERE user_id = %d  ORDER BY timestamp DESC LIMIT %d",
            $user_id, $limit
        );
        $results = $wpdb->get_results($query);

    
        
        $html = '<div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4>'.esc_html( $title ).'</h4>
                <div class="see-all">
                    '.(($link) ? '<a class="w-btn us-btn-style_1" href="'.$link.'">See all</a> ': '').'
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
                if(!empty($results)){
                foreach ($results as $data) {
                    $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->timestamp));
                    
                    $resume_url = esc_url(OAM_Helper::$customer_dashboard_link.'/groups/details/'.$data->id);
                    $html .= '<tr data-id="52" data-verify="0" data-group="0">
                    <td><div class="thead-data">Id</div>'.esc_html($data->id).'</td>
                    <td><div class="thead-data">Name</div>'.esc_html($data->name).'</td>
                    <td><div class="thead-data">Date</div>'.esc_html($created_date).'</td>
                    <td><div class="thead-data">Action</div><a href="'.esc_url( $resume_url ).'" class="w-btn action-link"><img src="'.OH_PLUGIN_DIR_URL .'assets/image/resume.png" alt="resume icon">Open</a></td>
                </tr>';
                }
            }else{
                $html .= '<tr><td colspan="4" class="no-available-msg">No '.($title ? $title : 'data').' available.</td></tr>';
            }
                $html .= '</tbody>
            </table>
        </div>';


        
        return $html;
    }
   
    public static function organizations_dashboard_widget($title = "", $limit = 3, $link = '') {
        $manage_affiliates_content = OAM_Helper::manage_affiliates_content();
        $result = json_decode($manage_affiliates_content, true);
    
        if (empty($result) || !isset($result['success']) || !$result['success']) {
            return '<div class="recipient-lists-block custom-table"><p>No Organization found!</p></div>';
        }
    
        $affiliates = $result['data']['user_info'];
        $blocked_affiliates = $result['data']['affiliates'];
    
        ob_start(); ?>
        <div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4><?php echo esc_html($title); ?></h4>
                <div class="see-all">
                    <?php if (!empty($link)) : ?>
                        <a class="w-btn us-btn-style_1" href="<?php echo esc_url($link); ?>">See all</a>
                    <?php endif; ?>
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($affiliates)) :
                        foreach ($affiliates as $key => $affiliate) :
                            $is_blocked = $blocked_affiliates[$key]['status'];
                            $token = $blocked_affiliates[$key]['token'];
                            $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
                            ?>
                            <tr>
                                <td><div class="thead-data">Token</div><?php echo esc_html($affiliate['token']); ?></td>
                                <td><div class="thead-data">Name</div><?php echo esc_html($affiliate['display_name']); ?></td>
                                <td><div class="thead-data">Action</div>
                                    <?php if ($is_blocked != 0) : ?>
                                        <button class="affiliate-block-btn w-btn action-link" 
                                            data-affiliate="<?php echo esc_attr($affiliate['ID']); ?>"
                                            data-blocked="<?php echo ($is_blocked == 1) ? '1' : '0'; ?>">
                                            <?php echo ($is_blocked == 1) ? 'Block' : 'Unblock'; ?>
                                        </button>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url($current_url . '?action=organization-link&token=' . $token); ?>" 
                                           class="w-btn us-btn-style_1">Link to Organization</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach;
                    else : ?>
                        <tr><td colspan="3" class="no-available-msg">No Organization found!</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    
        return ob_get_clean();
    }
    

    public static function incomplete_orders_dashboard_widget($title = "", $limit = 3, $link = '') {
        global $wpdb;
        $user_id = get_current_user_id();
        $order_process_table = OAM_Helper::$order_process_table;
    
    
        // Fetch limited orders
        $query = $wpdb->prepare(
            "SELECT * FROM {$order_process_table} WHERE user_id = %d AND step != %d ORDER BY created DESC LIMIT %d",
            $user_id, 5, $limit
        );
        $results = $wpdb->get_results($query);

        
        $html = '<div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4>'.esc_html( $title ).'</h4>
                <div class="see-all">
                    '.(($link) ? '<a class="w-btn us-btn-style_1" href="'.$link.'">See all</a> ': '').'
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
                if(!empty($results)){
                foreach ($results as $data) {
                    $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->created));
                    
                    $resume_url = esc_url(ORDER_PROCESS_LINK."?pid=$data->id");
                    $html .= '<tr data-id="52" data-verify="0" data-group="0">
                    <td><div class="thead-data">Id</div>'.esc_html($data->id).'</td>
                    <td><div class="thead-data">Name</div>'.esc_html($data->name).'</td>
                    <td><div class="thead-data">Date</div>'.esc_html($created_date).'</td>
                    <td><div class="thead-data">Action</div><a href="'.esc_url( $resume_url ).'" class="w-btn action-link"><img src="'.OH_PLUGIN_DIR_URL .'assets/image/resume.png" alt="">Resume</a></td>
                </tr>';
                }
            }else{
                $html .= '<tr><td colspan="4"  class="no-available-msg">No incomplete orders available.</td></tr>';
            }
                $html .= '</tbody>
            </table>
        </div>';


        
        return $html;
    }
    public static function failed_recipients_dashboard_widget($title = "", $limit = 3, $link = '') {
        global $wpdb;
        $user_id = get_current_user_id();
        $order_process_table = OAM_Helper::$order_process_table;
    
    
        // Fetch limited orders
        $query = $wpdb->prepare(
            "SELECT * FROM {$order_process_table} WHERE user_id = %d AND step = %d AND order_id != %d AND order_type = %s ORDER BY created DESC LIMIT %d",
            $user_id,5,0, 'multi-recipient-order',$limit
        );
        $results = $wpdb->get_results($query);

        
        $html = '<div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4>'.esc_html( $title ).'</h4>
                <div class="see-all">
                    '.(($link) ? '<a class="w-btn us-btn-style_1" href="'.$link.'">See all</a> ': '').'
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
                if(!empty($results)){
                foreach ($results as $data) {
                    $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->created));
                    
                    $resume_url = esc_url(OAM_Helper::$customer_dashboard_link.'/failed-recipients/details/'.$data->id);
                    $html .= '<tr data-id="52" data-verify="0" data-group="0">
                    <td><div class="thead-data">Id</div>'.esc_html($data->id).'</td>
                    <td><div class="thead-data">Name</div>'.esc_html($data->name).'</td>
                    <td><div class="thead-data">Date</div>'.esc_html($created_date).'</td>
                    <td><div class="thead-data">Action</div><a href="'.esc_url( $resume_url ).'" class="w-btn action-link"><img src="'.OH_PLUGIN_DIR_URL .'assets/image/resume.png" alt="resume">Open</a></td>
                </tr>';
                }
            }else{
                $html .= '<tr><td colspan="4" class="no-available-msg">No failed recipients found!.</td></tr>';
            }
                $html .= '</tbody>
            </table>
        </div>';
        
        return $html;
    }

    public static function groups_dashboard_widget($title = "", $limit = 3, $link = '') {
        global $wpdb;
        $user_id = get_current_user_id();
        $order_process_table = OAM_Helper::$order_process_table;
    
        $group_table = OAM_Helper::$group_table;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
        $order_process_table = OAM_Helper::$order_process_table;;

      
        // Fetch limited orders
        $query = $wpdb->prepare(
            "SELECT * FROM {$group_table} WHERE user_id = %d AND visibility = %d ORDER BY timestamp DESC LIMIT %d",
            $user_id, 1, $limit
        );
        $results = $wpdb->get_results($query);

        
        $html = '<div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4>'.esc_html( $title ).'</h4>
                <div class="see-all">
                    '.(($link) ? '<a class="w-btn us-btn-style_1" href="'.$link.'">See all</a> ': '').'
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';
                if(!empty($results)){
                foreach ($results as $data) {
                    $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->timestamp));


                    $resume_url = esc_url(OAM_Helper::$customer_dashboard_link.'/groups/details/'.$data->id);
                    $html .= '<tr data-id="52" data-verify="0" data-group="0">
                    <td><div class="thead-data">ID</div>'.esc_html($data->id).'</td>
                    <td><div class="thead-data">Name</div>'.esc_html($data->name).'</td>
                    <td><div class="thead-data">Date</div>'.esc_html($created_date).'</td>
                    <td><div class="thead-data">Action</div><a href="'.esc_url( $resume_url ).'" class="w-btn action-link"><img src="'.OH_PLUGIN_DIR_URL .'assets/image/resume.png" alt="">View Recipients</a></td>
                </tr>';
                }
            }else{
                $html .= '<tr><td colspan="4"  class="no-available-msg" >No group found!.</td></tr>';
            }
                $html .= '</tbody>
            </table>
        </div>';
        
        return $html;
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