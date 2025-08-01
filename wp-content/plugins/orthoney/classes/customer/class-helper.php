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
    public static $administrator_dashboard_link;
    public static $sales_representative_dashboard_link;
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
        self::$administrator_dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
        self::$sales_representative_dashboard_link = SALES_REPRESENTATIVE_DASHBOARD_LINK;
        self::$all_uploaded_csv_dir = WP_CONTENT_DIR . '/all-uploaded-files/';
        self::$process_recipients_csv_dir = WP_CONTENT_DIR . '/process-recipients-files/';
        self::$process_recipients_csv_url = WP_CONTENT_URL . '/process-recipients-files/';
        self::$group_recipients_csv_dir = WP_CONTENT_DIR . '/group-recipients-files/';

        self::$date_format = get_option('date_format');
        self::$time_format = get_option('time_format');
    }

	public function __construct() {}
    
     public static function get_affiliate_by_pid($user_id) {
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
        global $wpdb;
        
        $token = $wpdb->get_var($wpdb->prepare(
            "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d",
            $user_id
        ));

        $states = WC()->countries->get_states('US');
        $state = get_user_meta($user_id, '_yith_wcaf_state', true) ?: get_user_meta($user_id, 'billing_state', true);
        $city = get_user_meta($user_id, '_yith_wcaf_city', true) ?: get_user_meta($user_id, 'billing_city', true);
       $orgName = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
        if (empty($orgName)) {
            $orgName = get_user_meta($user_id, '_orgName', true);
        }

        $state_name = isset($states[$state]) ? $states[$state] : $state;
        $details = '[' . $token . '] ' . $orgName ?:$data->display_name;
        if (!empty($city)) {
            $details .= ', ' . $city;
        }
        if (!empty($state)) {
            $details .= ', ' . $state_name;
        }
        return $details;
     }
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
    
   public static function get_jars_orders($user_id, $table_order_type, $custom_order_type, $custom_order_status, $search, $is_export = false, $page, $length) {
        global $wpdb;
        $orders_table = $wpdb->prefix . 'wc_orders'; 
        $jar_table = $wpdb->prefix . 'oh_recipient_order';

        $filtered_orders = [];

        // ✅ Define tabletype from $_POST or default
        $tabletype = isset($_POST['tabletype']) ? sanitize_text_field($_POST['tabletype']) : 'administrator-dashboard';

        // Pagination calculation
        $offset = $page;
        $limit = $length;
        $min_qty = isset($_REQUEST['selected_min_qty']) && is_numeric($_REQUEST['selected_min_qty']) ? (int) $_REQUEST['selected_min_qty'] : 1;
        $max_qty = isset($_REQUEST['selected_max_qty']) && is_numeric($_REQUEST['selected_max_qty']) ? (int) $_REQUEST['selected_max_qty'] : 1000;
        $selected_customer_id = isset($_REQUEST['selected_customer_id']) && is_numeric($_REQUEST['selected_customer_id']) ? (int) $_REQUEST['selected_customer_id'] : '';
        $search_val = isset($_REQUEST['search']['value']) ? trim(sanitize_text_field($_REQUEST['search']['value'])) : '';
        $selected_year = isset($_REQUEST['selected_year']) && is_numeric($_REQUEST['selected_year']) ? (int) $_REQUEST['selected_year'] : '';

        $where_clauses = [];
        $params = [];

        // Quantity filter
        $where_clauses[] = "{$jar_table}.quantity BETWEEN %d AND %d";
        $params[] = $min_qty;
        $params[] = $max_qty;

        // Year filter
        if (!isset($_REQUEST['selected_year'])) {
            $where_clauses[] = "YEAR({$jar_table}.created_date) = %d";
            $params[] = date("Y");
        } else {
            if (!empty($_REQUEST['selected_year'])) {
                $where_clauses[] = "YEAR({$jar_table}.created_date) = %d";
                $params[] = $selected_year;
            }
        }

        if (!empty($_REQUEST['search_by_organization'])) {
            $search_by_organization = sanitize_text_field($_REQUEST['search_by_organization']);
            if (!empty($search_by_organization)) {
                $search_terms_raw = explode(',', $search_by_organization);
                $dsr_token = (isset($_REQUEST['jar_dsr_affiliate_token']) && $_REQUEST['jar_dsr_affiliate_token'] != '') ? sanitize_text_field($_REQUEST['jar_dsr_affiliate_token']) : null;

                if ($dsr_token != null) {
                    $search_terms_raw = [$dsr_token];
                }

                if (is_array($search_terms_raw)) {
                    $search_terms = array_filter(array_map('trim', $search_terms_raw));

                    if (!empty($search_terms)) {
                        $placeholders = implode(',', array_fill(0, count($search_terms), '%s'));
                        $where_clauses[] = "({$jar_table}.affiliate_token IN ($placeholders))";
                        $params = array_merge($params, $search_terms);
                    }
                }
            }
        }

        if (!empty($search_val)) {
            $where_clauses[] = "(o.id LIKE %s OR {$jar_table}.recipient_order_id LIKE %s OR {$jar_table}.full_name LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search_val) . '%';
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }

        if ($tabletype == 'administrator-dashboard' || $tabletype == 'organization-dashboard' || $tabletype == 'sales-representative-dashboard') {
            if ($selected_customer_id) {
                $where_clauses[] = "{$jar_table}.user_id = %d";
                $params[] = $selected_customer_id;
            }
        } else {
            $where_clauses[] = "{$jar_table}.user_id = %d";
            $params[] = get_current_user_id();
        }

        if (!empty($_REQUEST['search_by_organization'])) {
            $where_clauses[] = "{$jar_table}.affiliate_token IS NOT NULL";
        }

        $joins = "
            LEFT JOIN {$wpdb->prefix}oh_wc_order_relation oor ON {$jar_table}.order_id = oor.order_id
            LEFT JOIN {$orders_table} o ON oor.wc_order_id = o.id
        ";

        $where_clauses[] = "o.status NOT IN ('trash', 'wc-checkout-draft')";

        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

        // Add pagination
        $params[] = $limit;
        $params[] = $page;

        $sql = $wpdb->prepare(
            "
            SELECT 
                {$jar_table}.recipient_order_id AS jar_no,
                {$jar_table}.order_id AS order_no,
                {$jar_table}.created_date AS date,
                full_name AS billing_name,
                full_name AS shipping_name,
                {$jar_table}.affiliate_token AS affiliate_code,
                {$jar_table}.quantity AS total_jar,
                {$jar_table}.order_type AS type
            FROM {$jar_table}
            $joins $where_sql
            LIMIT %d OFFSET %d
            ",
            ...$params
        );

        $jarsorder = $wpdb->get_results($sql, ARRAY_A);

        // ✅ FIXED: Include $tabletype in closure
        $filtered_orders = array_map(function($order) use ($wpdb, $tabletype) {
            $order['status'] = '';
            $recipient_order_id = esc_attr($order['jar_no']);
            $order_no = esc_attr($order['order_no']);
            $recipient_name = esc_attr($order['billing_name']);

            if (isset($order['affiliate_code']) && ($order['affiliate_code'] === 'Orthoney' || $order['affiliate_code'] === '')) {
                $order['affiliate_code'] = 'Honey from the Heart';
            }

            $wc_order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT order_id
                FROM {$wpdb->prefix}wc_orders_meta
                WHERE meta_key = %s AND meta_value = %s",
                '_orthoney_OrderID',
                $order_no
            ));

            $order_created_date = $order['date'];
            if (is_string($order_created_date)) {
                [$date_part, $time_part] = explode(' ', $order_created_date);
                [$year, $month, $day] = explode('-', $date_part);
                [$hour, $minute, $second] = explode(':', $time_part);
                $formatted_string = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
                $order_created_date = DateTime::createFromFormat('Y-m-d H:i:s', $formatted_string);
            }

            $editLink = '';
            $deleteLink = '';
            $editable = OAM_COMMON_Custom::check_order_editable($order_created_date);

            if ($editable === true) {
                $editLink = '<button class="far fa-edit editRecipientOrder" data-order="' . $recipient_order_id . '" data-tippy="Edit Details" data-popup="#recipient-order-manage-popup"></button>';
            }

            $order['jar_tracking'] = '<a href="#view-order-tracking-popup" data-lity data-tippy="View Tracking">Tracking Numbers</a>';

            $return_url = '&return_url=admin';
            if ($tabletype == 'organization-dashboard') {
                $return_url = '&return_url=organization';
            } elseif ($tabletype == 'administrator-dashboard') {
                $return_url = '&return_url=admin';
            } else {
                $return_url = '&return_url=customer';
            }

            $order['action'] = '<a class="far fa-eye" href="' . esc_url(CUSTOMER_DASHBOARD_LINK . "order-details/{$wc_order_id}?recipient-order={$recipient_order_id}") . $return_url . '"></a>' . $editLink . $deleteLink;

            $order['date'] = date_i18n(
                OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format,
                strtotime($order['date'])
            );

            return $order;
        }, $jarsorder);

        return $filtered_orders;
    }



    public static function get_filtered_orders($user_id, $table_order_type, $custom_order_type, $custom_order_status, $search, $is_export = false, $page, $length, $selected_customer_id) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'wc_orders';
        $order_meta_table = $wpdb->prefix . 'wc_orders_meta';
        $recipient_ordertable = $wpdb->prefix . 'oh_recipient_order';
        $order_relation = $wpdb->prefix . 'oh_wc_order_relation';
        $order_addresses = $wpdb->prefix . 'wc_order_addresses';

        $filtered_orders = [];

        $limit = $length;
        $offset = $page;

        $search_term = isset($_REQUEST['search']['value']) ? sanitize_text_field($_REQUEST['search']['value']) : '';
        $tabletype = isset($_POST['tabletype']) ? sanitize_text_field($_POST['tabletype']) : 'administrator-dashboard';
        $where_conditions = [];
        $where_values = [];
        $join = "";

        $join .= "INNER JOIN $order_relation AS rel ON rel.wc_order_id = orders.id";
        $join .= " LEFT JOIN $recipient_ordertable AS rec ON rec.order_id = rel.order_id";

        
        if (!empty($_REQUEST['search_by_organization'])) {
            // Sanitize and convert to lowercase
            $search_by_organization = strtolower(sanitize_text_field($_REQUEST['search_by_organization']));
            
            $search_terms = array_filter(array_map('trim', explode(',', $search_by_organization)));

            // Check dsr_token
            $dsr_token = isset($_REQUEST['dsr_affiliate_token']) ? strtolower(sanitize_text_field($_REQUEST['dsr_affiliate_token'])) : null;
            
            if ($dsr_token) {
                $search_terms = [];
                $search_terms[] = $dsr_token;
            }

            if (!empty($search_terms)) {
                $id_placeholders = implode(',', array_fill(0, count($search_terms), '%s'));
                $code_placeholders = implode(',', array_fill(0, count($search_terms), '%s'));

                // Lowercase search terms for rel.affiliate_code
                $lowercased_search_terms = array_map('strtolower', $search_terms);

                $where_conditions[] = "(CAST(orders.id AS CHAR) IN ($id_placeholders) OR LOWER(rel.affiliate_code) IN ($code_placeholders))";

                // Combine normal and lowercase terms accordingly
                $where_values = array_merge($search_terms, $lowercased_search_terms);
            }
        }

        if (!empty($_REQUEST['search_by_recipient'])) {
            $search_by_recipient = sanitize_text_field($_REQUEST['search_by_recipient']);
            $where_conditions[] = "rec.full_name LIKE %s";
            $where_values[] = '%' . $wpdb->esc_like($search_by_recipient) . '%';
        }

        if (!empty($search_term)) {
           if ($tabletype == 'administrator-dashboard' || $tabletype == 'organization-dashboard' || $tabletype == 'sales-representative-dashboard') {
                $join .= " LEFT JOIN {$wpdb->users} AS u ON u.ID = orders.customer_id";
                $where_conditions[] = "(orders.id = %d OR rec.order_id = %d OR rel.wc_order_id = %d OR u.display_name LIKE %s )";
                $where_values[] = (int) $search;
                $where_values[] = (int) $search;
                $where_values[] = (int) $search;
                $where_values[] = '%' . $wpdb->esc_like($search_term) . '%';
            }else{
                
                $join .= " LEFT JOIN $order_addresses AS addr ON addr.order_id = orders.id AND addr.address_type = 'billing'";
                $where_conditions[] = "(orders.id = %d OR rec.order_id = %d OR rel.wc_order_id = %d OR CONCAT(addr.first_name, ' ', addr.last_name) LIKE %s)";
                $where_values[] = (int) $search;
                $where_values[] = (int) $search;
                $where_values[] = (int) $search;
                $where_values[] = '%' . $wpdb->esc_like($search_term) . '%';
            }
        }

        if (!empty($_REQUEST['custom_order_type'])) {
            if ($_REQUEST['custom_order_type'] === "multiple_address") {
                $where_conditions[] = "rel.order_type = %s";
                $where_values[] = 'multi_address';
            } elseif ($_REQUEST['custom_order_type'] === "single_address") {
                $where_conditions[] = "rel.order_type = %s";
                $where_values[] = 'single_address';
            }
        }

        if (!empty($_REQUEST['selected_order_status']) && $_REQUEST['selected_order_status'] !== "all") {
            $where_conditions[] = "orders.status = %s";
            $where_values[] = sanitize_text_field($_REQUEST['selected_order_status']);
        } else{
            $statuses =  ['wc-cancelled', 'wc-refunded', 'wc-failed'];
            $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
            $where_conditions[] = "orders.status NOT IN ($placeholders)";
            $where_values = array_merge($where_values, $statuses);
        }

        if (!empty($_REQUEST['custom_payment_method']) && $_REQUEST['custom_payment_method'] !== "all") {
            if($_REQUEST['custom_payment_method'] == 'Check payments'){
                $where_conditions[] = "(orders.payment_method_title = %s OR orders.payment_method_title = %s)";
                $where_values[] = 'Check payments';
                $where_values[] = 'Pay by Check';
            }else{
                $where_conditions[] = "orders.payment_method_title = %s";
                $where_values[] = sanitize_text_field($_REQUEST['custom_payment_method']);
            }
            
        }

        if (
            isset($_REQUEST['selected_min_qty'], $_REQUEST['selected_max_qty']) &&
            is_numeric($_REQUEST['selected_min_qty']) &&
            is_numeric($_REQUEST['selected_max_qty'])
        ) {
            $where_conditions[] = "rel.quantity BETWEEN %d AND %d";
            $where_values[] = intval($_REQUEST['selected_min_qty']);
            $where_values[] = intval($_REQUEST['selected_max_qty']);
        }

        $where_conditions[] = "orders.type = %s";
        $where_values[] = 'shop_order';

        if(!empty($_REQUEST['selected_year'])){
                $year = $_REQUEST['selected_year'];
                $where_conditions[] = "YEAR(orders.date_created_gmt) = %d";
                $where_values[] = $year;
        }

         if($_REQUEST['draw'] == 1){
               $year = !empty($_REQUEST['selected_year']) ? intval($_REQUEST['selected_year']) : date("Y");
                $where_conditions[] = "YEAR(orders.date_created_gmt) = %d";
                $where_values[] = $year;

         }

        if ($tabletype == 'administrator-dashboard' || $tabletype == 'organization-dashboard' || $tabletype == 'sales-representative-dashboard' ) {
            if (!empty($_REQUEST['selected_customer_id']) && is_numeric($_REQUEST['selected_customer_id'])) {
                $where_conditions[] = "orders.customer_id = %d";
                $where_values[] = intval($_REQUEST['selected_customer_id']);
            }
        } else {
            $where_conditions[] = "orders.customer_id = %d";
            $where_values[] = get_current_user_id();
        }

        $where_values[] = $limit;
        $where_values[] = $offset;

        if (!empty($_REQUEST['search_by_organization'])) {
            if($_REQUEST['search_by_organization'] != 'orthoney'){
                $where_conditions[] = "(rel.affiliate_user_id != 0 )";
            }else{
                $where_conditions[] = "(rel.affiliate_user_id = 0 )";
            }
        }


        $where_conditions[] = "orders.status NOT IN ('trash', 'wc-checkout-draft') ";
        $where_conditions[] = "(rel.order_id != 0 )";

         $sql = $wpdb->prepare(
            "SELECT orders.*, orders.customer_id, rel.order_id as rel_oid 
            FROM $orders_table AS orders
            $join
            WHERE " . implode(' AND ', $where_conditions) . "
            
            GROUP BY orders.id 
            ORDER BY orders.date_updated_gmt DESC
            LIMIT %d OFFSET %d",
            ...$where_values
        );


        $main_orders = $wpdb->get_results($sql);

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

            $row_builder = $is_export ? 'build_export_order_row' : 'build_order_row';
            $row_data = OAM_Helper::$row_builder($main_data, $main_order, $order_type, $total_quantity);

            if ($table_order_type === 'main_order') {
                $filtered_orders[] = $row_data;
            }
        }

        return $filtered_orders;
    }



    // Helper to build row array for a main or sub order
   public static function build_order_row($order_data, $order_obj, $order_type, $total_quantity, $parent_order = null) {
        global $wpdb;


        $jar_order_id = $order_data->rel_oid;
        $year = isset($_REQUEST['selected_year']) ? intval($_REQUEST['selected_year']) : date("Y");

        $user_id = get_current_user_id();


        $customer_id = $order_obj->user_id; 
        $display_name = '';

        if ( $customer_id ) {
            $user_info = get_userdata( $customer_id );
            $display_name = $user_info->display_name;
        
        }

      
        $orders_table = $wpdb->prefix . 'wc_orders';
    
        $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($order_data->date_created_gmt));
        $tabletype = isset($_POST['tabletype']) ? sanitize_text_field($_POST['tabletype']) : 'administrator-dashboard';
        
        $billing_name = $order_obj->get_billing_first_name() . ' ' . $order_obj->get_billing_last_name();
        // if ($tabletype == 'administrator-dashboard') {
        //     $billing_name = $order_obj->get_billing_first_name() . ' ' . $order_obj->get_billing_last_name();
        // }else{
        //     $billing_name = $display_name;
        // }

        $shipping_name = $order_obj->get_shipping_first_name() . ' ' . $order_obj->get_shipping_last_name();
        // $referral_id = $order_obj->get_meta('_yith_wcaf_referral', true) ?: 'Orthoney';
        $order_total = wc_price($order_obj->get_total());
        $sub_order_id =  OAM_COMMON_Custom::get_order_meta($order_data->id, '_orthoney_OrderID');
        $jarsorder_count = 1;
        if($sub_order_id){
         $jarsorder_count = $wpdb->get_var(
            $wpdb->prepare(
                "
                SELECT COUNT(*)
                FROM {$wpdb->prefix}oh_order_process_recipient
                WHERE order_id = %d
                ",
                $sub_order_id
            )
        );
        $referral_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT affiliate_token FROM {$wpdb->prefix}oh_recipient_order WHERE order_id = %d LIMIT 1",
                $sub_order_id
            )
        ) ?: 'Honey from the Heart';

       if ($referral_id === 'Orthoney') {
            $referral_id = 'Honey from the Heart';
        }
        
    }


    $reorderbutton = '';

    if($tabletype != 'organization-dashboard'){
        $gmt_created = is_object($order_data->date_created_gmt)  ? $order_data->date_created_gmt : new DateTime($order_data->date_created_gmt, new DateTimeZone('GMT')); 
        
        $order_year = (int) $gmt_created->format('Y');
        
        $current_year = (int) gmdate('Y');
    
        if ($order_year < $current_year) {
            $order = wc_get_order($order_data->id);
    
            if ($order && $order->get_user_id() == $user_id) {
                $reorderbutton = '<button data-tippy="Re Order" data-orderid="'.$order_data->id.'" data-user="' . esc_attr($user_id) . '" class="wcReOrderCustomerDashboard far fa-repeat-alt"></button>';
            }
        }
        
    }
        // // Status HTML
        //$status_html = '';
        // if ($order_type === 'Multi Address' && $order_data->parent_order_id == 0) {
        //     $status_counts = $wpdb->get_results($wpdb->prepare(
        //         "SELECT status, COUNT(*) as count FROM $orders_table WHERE customer_id = %d AND parent_order_id = %d GROUP BY status",
        //         $user_id, $order_data->id
        //     ));
        //     foreach ($status_counts as $status) {
        //         $status_html .= sprintf(
        //             '<span class="%s">(%d) %s</span> ',
        //             esc_attr($status->status),
        //             esc_html($status->count),
        //             esc_html(wc_get_order_status_name($status->status))
        //         );
        //     }
        // } 

         $status_html = sprintf(
                '<mark class="order-status status-%s tips"><span class="%s">%s</span></mark>',
                esc_attr($order_data->status),
                esc_attr($order_data->status),
                esc_html(wc_get_order_status_name($order_data->status))
            );
        
        $return_url = '?return_url=admin';
            if($tabletype == 'organization-dashboard' )  {
                $return_url = '?return_url=organization';

            }elseif($tabletype == 'administrator-dashboard' )  {
                $return_url = '?return_url=admin';
            }else{
            
                $return_url = '?return_url=customer';
            } 

        $resume_url = esc_url(CUSTOMER_DASHBOARD_LINK . "order-details/". ($order_data->parent_order_id == 0 ? $order_data->id : $order_data->parent_order_id).''.$return_url );
        $total_recipient = '';
        if($jarsorder_count != 0){
           $total_recipient = "<a id='".$jar_order_id."' onclick='jarfilter_trigger(\"$jar_order_id\", \"$year\")' class='filter-jar-order-by-wc-order ".$year."' href='javascript:;'>".$jarsorder_count."</a>";
        }else{
            $total_recipient = '-';
        }
        return [
            'jar_no' => esc_html($order_data->id ),
            'orthoney_order_id' => OAM_COMMON_Custom::get_order_meta(($order_data->parent_order_id == 0) ? $order_data->id : $order_data->parent_order_id, '_orthoney_OrderID'),
             'order_no' => OAM_COMMON_Custom::get_order_meta(($order_data->parent_order_id == 0) ? $order_data->id : $order_data->parent_order_id, '_orthoney_OrderID'). '<br> Ref. ID '. $order_data->id,
            'date' => esc_html($created_date),
            'billing_name' => esc_html($billing_name),
            'shipping_name' => esc_html($shipping_name),
            'affiliate_code' => esc_html($referral_id == 'Orthoney' ? 'Honey from the Heart' : $referral_id),
            'total_jar' => esc_html($total_quantity),
            'total_recipient' => $total_recipient,
            'payment_method' => $order_data->payment_method_title == 'Pay by Check' ? 'Check payments' : $order_data->payment_method_title,

           // 'total_recipient' => "<a id=".$jar_order_id." onclick='jarfilter_trigger(\"$jar_order_id\")' class='filter-jar-order-by-wc-order' href='javascript:;'>".$jarsorder_count."</a>",
            //'type' => esc_html($order_type),
            'status' => !empty($status_html) ? $status_html : 'Order is Preparing.',
            'price' => $order_total,
            'action' =>'<a data-tippy="View Order" href="' . $resume_url . '" class="far fa-eye"></a>'. $reorderbutton
        ];
    }

      // 'action' =>
            //     '<a data-tippy="View Order" href="' . $resume_url . '" class="far fa-eye"></a>' .
            //     ($order_data->parent_order_id == 0 ? '<a data-tippy="Download Invoice" href="#" class="far fa-download"></a><button class="download_csv_by_order_id far fa-file-csv" data-tippy="Download CSV" data-orderid="'.(($order_data->parent_order_id == 0) ? $order_data->id : $order_data->parent_order_id).'"></button>' : '') .
            //     (empty($status_html) && ( $order_data->parent_order_id == 0) ? '<button>Suborder is created</button>' : '')

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
    
        $resume_url = esc_url(CUSTOMER_DASHBOARD_LINK . "order-details/". ($order_data->parent_order_id == 0 ? $order_data->id : $order_data->parent_order_id));
    
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
                 $AlreadyOrderHtml = (!empty($filtered_ids) ? '<button data-recipientname="'.stripslashes($data->full_name).'" data-tippy="Recipient has been added, but we found another recent order with the same recipient." style="color:red" class="alreadyOrderButton btn-underline">Already Ordered</button>' : '');
                }
                if($data->verified == 0){
                    $reasonsHtml = '<div>No';
                    if (!empty($data->reasons)) {
						
                        $reasons = implode(", ", json_decode($data->reasons, true));
                        if($reasons != ''){
							$reasonsHtml = '<div class="tooltip" data-tippy="'.stripslashes($reasons).'" style="color:red">Failed to Add to Order</span></div>';
                        }
                    }
                    $reasonsHtml .= '</div>';
                }
                
                
                if (!empty($addressParts)) {
                    $addressPartsHtml = '<td data-label="Address"><div class="thead-data">Address</div>' . stripslashes(implode(', ', $addressParts)) . '</td>';
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
                
                    $html .= '<td data-label="Full Name"><div class="thead-data">Full Name</div><input type="hidden" name="'.(($reverify == 1 OR $reverify == 2) ? "recipientAddressIds[]" : "recipientIds[]").'" value="'.$id.'">'.stripslashes(stripslashes($data->full_name)).'</td>';
                
                $html .= '<td data-label="Company name"><div class="thead-data">Company name</div>'.($data->company_name != "" ? stripslashes($data->company_name) : '') .'</td>';
                
                $html .= $addressPartsHtml;
                $html .= '<td data-label="Quantity"><div class="thead-data">Quantity</div>'.((empty($data->quantity) || $data->quantity <= 0) ? '0' : $data->quantity).'</td>';
               
                // $html .= '<td>'.$greetingHtml.'</td>';

                if($alreadyOrder == 0){
                    if($reverify == 0){
                        $html .= '<td data-label="Status"><div class="thead-data">Status</div>'.(($data->verified == 0) ? stripslashes($reasonsHtml): 'Added to Order').'</td>';
                         $html .= '<td data-label="Greeting"><div class="thead-data">Greeting</div>'.((!empty($data->greeting) ? 'Yes' : 'NO')).'</td>';
                    }
                    if($reverify == 1 OR $reverify == 2){
                        if($reverify == 1){
                             $html .= '<td data-label="Status"><div class="thead-data">Reason</div><span style="color:red">'.stripslashes($data->reasons).'</span></td>';
                              $html .= '<td data-label="Greeting"><div class="thead-data">Greeting</div>'.((!empty($data->greeting) ? 'Yes' : 'NO')).'</td>';
                        }
                        if($data->address_verified == 1){
                            $html .= '<td data-label="Greeting"><div class="thead-data">Greeting</div>'.((!empty($data->greeting) ? 'Yes' : 'NO')).'</td>';
                        }
                        $html .= '<td data-label="Action"><div class="thead-data">Action</div>';
                        if($data->address_verified == 0){
                            // $html .= '<button class="reverifyAddress w-btn us-btn-style_1" style="padding:10px"><small>Reverify Address</small></button>';
                        }
                        $html .=  ' <button class="editRecipient far fa-edit" data-tippy="update Recipient Details" data-popup="#recipient-manage-popup" data-address_verified="1"></button>' .'<button data-recipientname="'.stripslashes($data->full_name).'" class="deleteRecipient far fa-trash" data-tippy="Remove Recipient"></button>';
                        $html .= '</td>';
                        
                    }else{
                        $html .= '<td data-label="Action"><div class="thead-data">Action</div>';
                        if($duplicate == 1){
                            $html .= '<button class="keep_this_and_delete_others" data-recipientname="'.stripslashes($data->full_name).'"  data-popup="#recipient-manage-popup" data-tippy="Keep this and delete others">Keep this and delete others</button>';
                        }
                        $html .= '<button class="viewRecipient far fa-eye" data-tippy="View Recipient Details" data-popup="#recipient-view-details-popup"></button><button class="editRecipient far fa-edit" data-tippy="Update Recipient Details" data-popup="#recipient-manage-popup"></button><button data-recipientname="'.stripslashes($data->full_name).'" data-tippy="Remove Recipient" class="deleteRecipient far fa-trash"></button>'.$AlreadyOrderHtml;
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

                if (!empty($header[0])) {
                    // Remove BOM if present
                    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

                    // Remove leading '#' from first column of header
                    if (strpos(trim($header[0]), '#') === 0) {
                        $header[0] = ltrim($header[0], "# \t\n\r\0\x0B");
                    }
                }
                
                $header_lower = array_map(function($val) {
                    return strtolower(trim($val));
                }, $header);
    
                $missing_columns = array_diff($required_columns_lower, $header_lower);
                if (!empty($missing_columns)) {
                    return self::log_and_return(false, $method, $process_id, 'Invalid CSV format. Missing required columns: ' . implode(', ', $missing_columns), $file_path);
                }
            }
        } elseif ($file_extension === 'xlsx') {
            require_once OH_PLUGIN_DIR_PATH. 'libs/SimpleXLSX/SimpleXLSX.php';
    
            if ($xlsx = SimpleXLSX::parse($file_path)) {
                // Use rowsEx() to avoid issues with formatted cells
                $rows = $xlsx->rowsEx();

                if (empty($rows) || !is_array($rows[0])) {
                    return self::log_and_return(false, $method, $process_id, 'Invalid or empty XLSX file.', $file_path);
                }

                // Extract plain text values from the header
                $header = array_map(function($cell) {
                    return isset($cell['value']) ? trim($cell['value']) : '';
                }, $rows[0]);

                if (!empty($header[0])) {
                    // Remove BOM if somehow present
                    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
                    // Remove leading '#' symbol from first column name
                    if (strpos(trim($header[0]), '#') === 0) {
                        $header[0] = ltrim($header[0], "# \t\n\r\0\x0B");
                    }
                }

                // Normalize headers: lowercase, trim, collapse whitespace
                $header_lower = array_map(function($val) {
                    return strtolower(trim(preg_replace('/\s+/', ' ', trim($val))));
                }, $header);


                // Compare against required columns (make sure $required_columns_lower is normalized the same way)
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

                if (!empty($header[0])) {
                    // Remove BOM (just in case)
                    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

                    // Remove leading "#" from first column name
                    if (strpos(trim($header[0]), '#') === 0) {
                        $header[0] = ltrim($header[0], "# \t\n\r\0\x0B");
                    }
                }
                            
                $header_lower = array_map(function($val) {
                    return strtolower(trim($val));
                }, $header);

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
        $total_quantity = 0;
        
        $unverifiedTableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Reason</th><th>Greeting</th><th>Action</th></tr></thead><tbody>';

        $verifyTableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Greeting</th><th>Action</th></tr></thead><tbody>';


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
                "SELECT * FROM {$order_process_recipient_table} WHERE pid = %d AND visibility = %d AND id IN ($placeholders)" ,
                array_merge([$order_process_id, 1], $recipientAddressIds)
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
            $total_quantity = $total_quantity + $recipient->quantity;
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
            $verifyRecordHtml = self::get_table_recipient_content($verifiedRecipients, $customGreeting, 2);

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
            'total_quantity'        => $total_quantity,
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
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required data-error-message="Please enter your full name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" data-error-message="Please enter a company name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_1">Mailing Address <span class="required">*</span></label>
                    <input type="text" id="address_1" name="address_1" required data-error-message="Please enter a mailing address.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_2">Suite/Apt#</label>
                    <input type="text" id="address_2" name="address_2">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="city">City <span class="required">*</span></label>
                    <input type="text" id="city" name="city" required data-error-message="Please enter a city.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="state">State <span class="required">*</span></label>
                    <select id="state" name="state" required data-error-message="Please select a state.">
                        <option value="" disable>Select state</option>
                        <?php echo self::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : ""); ?>
                    </select>
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode <span class="required">*</span></label>
                    <input type="text" id="zipcode" name="zipcode" required data-error-message="Please enter a valid zipcode.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="quantity">Quantity</label>
                    <div class="quantity">
                        <button class="minus" aria-label="Decrease" type="button">&minus;</button>
                        <input type="number" class="input-box" min="1" value="1" max="10000" required id="quantity" name="quantity" data-error-message="Please enter a valid quantity.">
                        <button class="plus" aria-label="Increase" type="button">&plus;</button>
                    </div>
                    <span class="error-message"></span>
                </div>

                <div class="textarea-div form-row gfield--width-full">
                    <label for="greeting">Add a Greeting</label>
                    <textarea id="greeting" name="greeting"></textarea>
                    <div class="char-counter"><span>100</span> characters remaining</div>
                </div>

                <div class="footer-btn gfield--width-full">
                <button type='button' class=" w-btn us-btn-style_4" data-lity-close>Cancel</button>
                <button type="submit">Add Recipient to Cart</button>
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
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" required data-error-message="Please enter your full name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" data-error-message="Please enter a company name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_1">Mailing Address<span class="required">*</span></label>
                    <input type="text" id="address_1" name="address_1" required data-error-message="Please enter a mailing address.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_2">Suite/Apt#</label>
                    <input type="text" id="address_2" name="address_2">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="city">City<span class="required">*</span></label>
                    <input type="text" id="city" name="city" required data-error-message="Please enter a city.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="state">State<span class="required">*</span></label>
                    <select id="state" name="state" required data-error-message="Please select a state.">
                        <option value="" disable>Select state</option>
                        <?php echo self::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : ""); ?>
                    </select>
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode<span class="required">*</span></label>
                    <input type="text" id="zipcode" name="zipcode" required data-error-message="Please enter a valid zipcode.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="quantity">Quantity<span class="required">* <small>Quantity cannot be changed after the order is placed.</small></span></label>
                    <input type="number" id="quantity" name="quantity" readonly>
                    <span class="error-message"></span>
                </div>
               

                <div class="textarea-div form-row gfield--width-full">
                    <label for="greeting">Add a Greeting</label>
                    <textarea id="greeting" name="greeting"></textarea>
                    <div class="char-counter"><span>100</span> characters remaining</div>
                </div>

                <div class="footer-btn gfield--width-full">
                <button type='button' class=" w-btn us-btn-style_4" data-lity-close>Cancel</button>
                <button type="submit">Edit Recipient Order Details</button>
                </div>
            </form>
        </div>
        <?php
    }
    public static function get_edit_billing_address_form(){ ?>
        <div id="edit-billing-address-form" class="site-form" >
            <form class="grid-two-col" novalidate>
            <input type="hidden" id="order_id" name="order_id" value="">
                <div class="form-row gfield--width-half">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" required data-error-message="Please enter your first name.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" required data-error-message="Please enter your last name.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_1">Address line 1<span class="required">*</span></label>
                    <input type="text" id="address_1" name="address_1" required data-error-message="Please enter a Address line 1">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="address_2">Address line 2</label>
                    <input type="text" id="address_2" name="address_2">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="city">City<span class="required">*</span></label>
                    <input type="text" id="city" name="city" required data-error-message="Please enter a city.">
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="state">State<span class="required">*</span></label>
                    <select id="state" name="state" required data-error-message="Please select a state.">
                        <option value="" disable>Select state</option>
                        <?php echo self::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : ""); ?>
                    </select>
                    <span class="error-message"></span>
                </div>

                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode<span class="required">*</span></label>
                    <input type="text" id="zipcode" name="zipcode" required data-error-message="Please enter a valid zipcode.">
                    <span class="error-message"></span>
                </div>
                
                <div class="form-row gfield--width-half">
                    <label for="phone_number">Phone Number<span class="required">*</span></label>
                    <input type="text" id="phone_number" name="phone_number" required data-error-message="Please enter a valid phone number.">
                    <span class="error-message"></span>
                </div>
                
                <div class="footer-btn gfield--width-full">
                <button type='button' class=" w-btn us-btn-style_4" data-lity-close>Cancel</button>
                <button type="submit">Edit Billing Details</button>
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
            <p class="recipient-reasons" style="color:red;font-weight: 900;"></p>
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
	 * Helper function that get all previous year Order List
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return array $groups is array with user data.
	 */

	public static function getLastYearOrderList($user_id = ''){
        global $wpdb;

        $customer_id = $user_id;

        $results = $wpdb->get_col( $wpdb->prepare(
            "
            SELECT id
            FROM {$wpdb->prefix}wc_orders
            WHERE customer_id = %d
            AND YEAR(date_created_gmt) >= 2024
            AND YEAR(date_created_gmt) < YEAR(CURDATE())
            ORDER BY date_created_gmt DESC
            ",
            $customer_id
        ) );

        return $results;
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

        $city = html_entity_decode(strip_tags($city ?? ''));
        $city = ucwords(strtolower(trim($city)));
    
        $url = "https://us-street.api.smartystreets.com/street-address?"
             . http_build_query([
                 'auth-id'    => $auth_id,
                 'auth-token' => $auth_token,
                 'street'     => trim($delivery_line_1 . ' ' . $delivery_line_2),
                 'city'       => $city,
                 'state'      => $state,
                 'zipcode'    => $zipcode,
                 'match'      => 'invalid',
                 'geocode'    => true,
                 'candidates' => 10,
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
            if(!empty($data[0]['components'])){
                $message = '';
                if ( ucwords(strtolower(trim($city)))  !== ucwords(strtolower(trim($data[0]['components']['city_name']))) ) {
                    $message .= 'Provided city is invalid. Accepted city is <span style="color: #6BBE56;">'.$data[0]['components']['city_name'].'</span>';
                    
                } 
                if ($state !== $data[0]['components']['state_abbreviation']) {
                    $message .= 'Provided state is invalid. Accepted state is <span style="color: #6BBE56;">'.$data[0]['components']['state_abbreviation'].'</span>';
                } 
                if (strpos($zipcode, '-') !== false) {
                    if (strpos($zipcode, $data[0]['components']['zipcode']) === false) {
                        $message .= 'Provided zipcode is invalid. Accepted zipcode is <span style="color: #6BBE56;">'. $data[0]['components']['zipcode'].'-'.$data[0]['components']['plus4_code'].'</span>';
                    }
                }else{
                    if ($zipcode !== $data[0]['components']['zipcode']) {
                        $message .= 'Provided zipcode is invalid. Accepted zipcode is <span style="color: #6BBE56;">'. $data[0]['components']['zipcode'].'</span>';
                    }
                }
            }

            if($message != ''){
                $response = ['success' => false, 'message' => $message];
            }else{
                $response = ['success' => true, 'message' => 'Valid and deliverable address.'];
            }

        }else{
            $message = 'Invalid address format.';
            $footnotes = $data[0]['analysis']['footnotes'] ?? '';
            $dpv_footnotes = $data[0]['analysis']['dpv_footnotes'] ?? '';
            if ($footnotes !== '' && !empty($footnotes) || $dpv_footnotes !== '' && !empty($dpv_footnotes)) {
                $message = OAM_Helper::addressCorrections($footnotes, $dpv_footnotes);
                $success = false;
            }
           
            $response = ['success' => false, 'message' =>  $message];
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

    public static function addressCorrections($code = '', $dpv_footnotes = '') {

        $dpv_footnotes_corrections = [
            'AA' => 'Street name, city, state, and ZIP are all valid.',
            'A1' => 'Address not present in USPS data.',
            'BB' => 'Entire address is valid.',
            'CC' => 'The submitted secondary information (apartment, suite, etc.) was not recognized. Secondary number is NOT REQUIRED for delivery.',
            'C1' => 'The submitted secondary information (apartment, suite, etc.) was not recognized. Secondary number IS REQUIRED for delivery.',
            'F1' => 'Military or diplomatic address',
            'G1' => 'General delivery address',
            'M1' => 'Primary number (house number) is missing.',
            'M3' => 'Primary number (house number) is invalid.',
            'N1' => 'Address is missing secondary information (apartment, suite, etc.) which IS REQUIRED for delivery.',
            'PB' => 'PO Box street style address.',
            'P1' => 'PO, RR, or HC box number is missing.',
            'P3' => 'PO, RR, or HC box number is invalid.',
            'RR' => 'Confirmed address with private mailbox (PMB) info.',
            'R1' => 'Confirmed address without private mailbox (PMB) info.',
            'R7' => 'Confirmed as a valid address that doesn\'t currently receive US Postal Service street delivery.',
            'TA' => 'Primary number was matched by dropping trailing alpha.',
            'U1' => 'Address has a "unique" ZIP Code.',
            'AABB' => 'ZIP, state, city, street name, and primary number match.',
            'AABBCC' => 'ZIP, state, city, street name, and primary number match, but secondary does not. A secondary is not required for delivery.',
            'AAC1' => 'ZIP, state, city, street name, and primary number match, but secondary does not. A secondary is required for delivery.',
            'AAM1' => 'ZIP, state, city, and street name match, but the primary number is missing.',
            'AAM3' => 'ZIP, state, city, and street name match, but the primary number is invalid.',
            'AAN1' => 'ZIP, state, city, street name, and primary number match, but there is secondary information such as apartment or suite that would be helpful.',
            'AABBR1' => 'ZIP, state, city, street name, and primary number match. Address confirmed without private mailbox (PMB) info.'
        ];

         $addressCorrections = [
                'A#'  => 'Correct ZIP Code',
                'B#'  => 'Correct city/state spelling',
                'C#'  => 'Invalid city/state/ZIP',
                'D#'  => 'No ZIP+4 assigned',
                'E#'  => 'Same ZIP for multiple',
                'F#'  => 'Address not found',
                'G#'  => 'Used addressee data',
                'H#'  => 'Missing secondary number',
                'I#'  => 'Insufficient/ incorrect address data',
                'J#'  => 'Dual address',
                'K#'  => 'Cardinal rule match',
                'L#'  => 'Changed address component',
                'LL# or LI#' => 'Flag address for LACSLink',
                'LI#' => 'Flag address for LACSLink',
                'LL#' => 'Flag address for LACSLink',
                'M#'  => 'Correct street spelling',
                'N#'  => 'Fix abbreviations',
                'O#'  => 'Multiple ZIP+4; lowest used',
                'P#'  => 'Better address exists',
                'Q#'  => 'Unique ZIP match',
                'R#'  => 'No match; EWS: Match soon',
                'S#'  => 'Unrecognized secondary address',
                'T#'  => 'Multiple response due to magnet street syndrome',
                'U#'  => 'Unofficial city name',
                'V#'  => 'Unverifiable city/state',
                'W#'  => 'Invalid delivery address',
                'X#'  => 'Default Unique ZIP Code',
                'Y#'  => 'Military match',
                'Z#'  => 'Matched with ZIPMOVE',
            ];


            $dpv_suffix = substr($dpv_footnotes, -2);
            $message = 'Invalid address format.';
            if ($dpv_footnotes !== '' && array_key_exists($dpv_suffix, $dpv_footnotes_corrections)) {
               $message = $dpv_footnotes_corrections[$dpv_suffix];
            }
            else{
                if ($dpv_footnotes !== '' && array_key_exists($dpv_footnotes, $dpv_footnotes_corrections)) {
                    $message = $dpv_footnotes_corrections[$dpv_suffix];
                }else{
                    if ($code !== '' && isset($addressCorrections[$code])) {
                        $message = $addressCorrections[$code];
                    }
                }
            }
           
            return $message;
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
                        <th>List Name</th>
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
                    <td><div class="thead-data">List Name</div>'.esc_html($data->name).'</td>
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
            "SELECT * FROM {$order_process_table} WHERE user_id = %d AND order_id = %d AND visibility = %d ORDER BY created DESC LIMIT %d",
            $user_id, 0, 1,$limit
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
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
    
        // Define order column and direction
        $order_column = 'id'; // or 'created_at', 'updated_at' etc. if it exists in your table
        $order_dir = 'DESC';
    
        // Base WHERE clause
        $where = "WHERE user_id = %d";
        $params = [$user_id];
    
        // Get failed recipient pids
        $failed_rows = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT pid FROM $order_process_recipient_table WHERE verified = %d AND user_id = %d",
                0, $user_id
            )
        );
    
        if (!empty($failed_rows)) {
            $pid_placeholders = implode(',', array_fill(0, count($failed_rows), '%d'));
            $where .= " AND step = 5 AND order_id != 0 AND id IN ($pid_placeholders)";
            $params = array_merge($params, $failed_rows);
       
            // Pagination (start from 0)
            $params[] = 0;
            $params[] = (int)$limit;
        
            // Final query
            $query = "
                SELECT * FROM $order_process_table
                $where
                ORDER BY $order_column $order_dir
                LIMIT %d, %d
            ";
        
            $results = $wpdb->get_results($wpdb->prepare($query, ...$params));
         }else{
            $results = [];
         }
        
       
        $html = '<div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4>'.esc_html( $title ).'</h4>
                <div class="see-all">
                    '.(($link && !empty($results)) ? '<a class="w-btn us-btn-style_1" href="'.$link.'">See all</a> ': '').'
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