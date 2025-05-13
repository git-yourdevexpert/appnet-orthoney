<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_COMMON_Custom {
    /**
     * Constructor to hook into Affiliate template loading.
     */
    public function __construct() {       
        add_action('user_register', array($this, 'check_userrole_update_meta'));
        add_filter('acf/settings/save_json', array($this, 'oh_acf_json_save_path'));
        add_filter('acf/settings/load_json', array($this, 'oh_acf_json_load_paths'));
        
        add_action('template_redirect', array($this, 'redirect_logged_in_user_to_dashboard'));
        // add_action('user_registration_after_submit_buttons', array($this, 'add_login_user_registration_after_submit_buttons'));

        add_action( 'wp_login', array($this, 'custom_redirect_admin_if_has_admin_role'), 10, 2);
        add_action('user_registration_after_login_form', array($this, 'add_login_link_pl_login_form'));
        add_action('user_registration_before_customer_login_form', array($this, 'add_content_pl_login_form'));
        add_shortcode('customer_login_button', array($this, 'custom_login_button_shortcode'));
        add_filter('body_class', array($this, 'custom_body_class'));

        add_filter( 'user_registration_reset_password_redirect', array($this, 'reset_password_redirection'), 10, 2);
        add_filter( 'user_registration_modify_field_validation_response',  array($this, 'custom_user_registration_email_exists_message'), 10, 2 );

        add_filter( 'woocommerce_registration_auth_new_customer', '__return_false' );
        // add_action( 'woocommerce_created_customer', array($this,'custom_redirect_after_registration_based_on_role' ));
        add_shortcode( 'registration_success_msg', array($this, 'show_success_message_on_login') );


    }

    public static function init() {}
    
    public function custom_redirect_after_registration_based_on_role( $customer_id ) {
        if ( is_admin() || wp_doing_ajax() ) return;

        $user = get_user_by( 'ID', $customer_id );

        if ( isset( $_POST['register_affiliate'] ) && !empty( $_POST['register_affiliate'] ) ) {
            // Redirect to organization login page if the field is present in the POST data
            $redirect_url = home_url( '/organization-login/' );
        } else {
            // Else, redirect to the default login page
            $redirect_url = home_url( '/login/' );
        }

        // Add a query parameter to show a success message
        $redirect_url = add_query_arg( 'registration', 'success', $redirect_url );

        wp_safe_redirect( $redirect_url );
        exit;
    }


    public function show_success_message_on_login() {
        ob_start();
        if ( isset( $_GET['registration'] ) && $_GET['registration'] == 'success' ) {
            ?>
            <div class="woocommerce-message" role="alert" tabindex="-1">
            Your account was created successfully. Your login details have been sent to your email address.	</div>
            <?php
        }
        return ob_get_clean();
    }
   

    public static function check_order_editable($order_date) {
        $editable = false;
        if ($order_date instanceof WC_DateTime) {
            $order_timestamp = $order_date->getTimestamp();
        
            $start_date = get_field('free_shipping_start_date', 'option'); 
            $end_date   = get_field('free_shipping_end_date', 'option');
        
            // Validate date strings
            $start_timestamp = strtotime($start_date);
            $end_timestamp   = strtotime($end_date);
        
            if ($start_timestamp !== false && $end_timestamp !== false) {
                if ($order_timestamp >= $start_timestamp && $order_timestamp <= $end_timestamp) {
                    $editable = true;
                }
            }
        }
        return $editable;
        
    }

    public static function get_order_meta($order_id, $meta_key) {

        global $wpdb;

        // Define the query to count orders for the current month
        $query = $wpdb->prepare(
            "SELECT meta_value
             FROM {$wpdb->prefix}wc_orders_meta
             WHERE meta_key = %s AND order_id = %d",
            $meta_key,
            $order_id
        );
        
        // Execute the query and get the result
        return $wpdb->get_var($query);
    }
    

    public static function get_current_month_count() {
        global $wpdb;
    
        // Step 1: Get the count of orders for the current month
        $query = "SELECT COUNT(id) 
                  FROM {$wpdb->prefix}wc_orders
                  WHERE date_created_gmt >= DATE_FORMAT(NOW(), '%Y-%m-01')
                  AND date_created_gmt <= LAST_DAY(NOW())";
        
        $month_count = (int) $wpdb->get_var($query);
    
        // Step 2: Format the count with leading zeros (4 digits)
        $formatted_count = str_pad($month_count, 4, '0', STR_PAD_LEFT);
    
        // Step 3: Build the base prefix (like 20250428)
        $date_prefix = current_time('Ymd'); // Site's timezone, not GMT
    
        // Step 4: Combine date + formatted_count
        $full_value = $date_prefix . $formatted_count;
    
        // Step 5: Now check in _wc_orders_meta table
        $meta_key = '_orthoney_OrderID'; // Correct meta key
    
        while (true) {
            $exists = $wpdb->get_var( $wpdb->prepare(
                "SELECT meta_value 
                 FROM {$wpdb->prefix}wc_orders_meta 
                 WHERE meta_key = %s AND meta_value = %s 
                 LIMIT 1",
                $meta_key,
                $full_value
            ));
    
            if (!$exists) {
                // Not exist, safe to return
                return $full_value;
            }
    
            // Else, increment and recheck
            $month_count++;
            $formatted_count = str_pad($month_count, 4, '0', STR_PAD_LEFT);
            $full_value = $date_prefix . $formatted_count;
        }
    }
    
    public static function get_product_custom_price($product_id, $affiliate_id) {
        global $wpdb; // Add this line to access $wpdb

        $product = wc_get_product( $product_id );
        $price = 14;

        if($affiliate_id !=0 ){

        
        // Get the affiliate ID from yith_wcaf_affiliates table
        $affiliate_id_result = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id = %d",
            $affiliate_id
        ) );

        if ( $affiliate_id_result ) {
            // Use the provided affiliate ID to get user-specific price
            $custom_price = get_user_meta($affiliate_id, 'DJarPrice', true);
            if ( $custom_price !== '' ) {
                $price = $custom_price;
            }
        } else {
            $price = $product->get_price();
        }

    }
        return $price;
    }


    public static function redirect_user_based_on_role($roles) {
        $redirects = [
            'administrator'         => home_url('wp-admin'),
            'yith_affiliate'        => ORGANIZATION_DASHBOARD_LINK,
            'affiliate_team_member' => ORGANIZATION_DASHBOARD_LINK,
            'sales_representative'  => SALES_REPRESENTATIVE_DASHBOARD_LINK,
            'customer'              => CUSTOMER_DASHBOARD_LINK
        ];

        foreach ($roles as $role) {
            if (isset($redirects[$role])) {
                return $redirects[$role];
                exit;
            }
        }
    }

    public static function old_user_id() {
        $user_id = 0;
        if (class_exists('user_switching') && method_exists('user_switching', 'get_old_user')) {

            $old_user = User_Switching::get_old_user();
            if(!empty($old_user)){
                $user_id = $old_user->ID;
            }
        }
        return $user_id;
    }

    public static function switch_to_user($user_id) {
        $user = get_user_by('ID',$user_id);
        return User_Switching::switch_to_url( $user);
    }

    public static function switch_back_user() {
        $html = '';
        if (class_exists('user_switching') && method_exists('user_switching', 'get_old_user')) {

            $old_user = user_switching::get_old_user();

            if ($old_user) {
                user_switching::switch_back_url($old_user);
                $redirect_to = urlencode(self::redirect_user_based_on_role($old_user->roles));
                $switch_back_url = user_switching::switch_back_url($old_user) . '&redirect_to=' . $redirect_to;
        
                 $html .='<a href="' . esc_url($switch_back_url) . '" class="btn-with-arrow">Switch Back to ' . esc_html($old_user->display_name) . '</a>';
            }
        }
        return $html;
    }
    
    public static function set_affiliate_cookie($token, $remove = 0) {
        global $wpdb;

        if ($remove == 1) {
            // Remove the cookies
            setcookie('yith_wcaf_referral_token', '', time() - 3600, "/", "", true, true);
            setcookie('yith_wcaf_referral_history', '', time() - 3600, "/", "", true, true);
            return;
        }

        if ($token !== 'Orthoney') {
            $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;

            // Correct query execution
            $processExistResult = $wpdb->get_var($wpdb->prepare("
                SELECT token FROM {$yith_wcaf_affiliates_table} WHERE ID = %d
            ", $token));

            if (!$processExistResult) {
                return;
            }

            setcookie('yith_wcaf_referral_token', $processExistResult);
            setcookie('yith_wcaf_referral_history', $processExistResult);
        } else {
            
            setcookie('yith_wcaf_referral_token', $token);
            setcookie('yith_wcaf_referral_history', $token);
        }
    }

    public function custom_body_class($classes) {
        // Add your custom class
        $affiliate_dashboard_id = get_page_by_path('affiliate-dashboard');
        if ($affiliate_dashboard_id) {
            $classes[] = 'affiliate-dashboard';
        }
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (!empty($user->roles)) {
                foreach ($user->roles as $role) {
                    $classes[] = 'role-' . sanitize_html_class($role);
                }
            }
        } 

    
        return $classes;
    }
    public static function sub_order_error_log($message, $order_id = null) {
        // Fallback to generic file if order_id not provided
        $log_filename = $order_id ? "sub_order_{$order_id}.log" : "sub-order-error.log";
        $log_file = WP_CONTENT_DIR . '/logs/' . $log_filename;
    
        // Ensure log directory exists
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
    
        // Format the error message
        $log_message = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
    
        // Write to the log file
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
   
    public static function orthoney_get_order_data($order_id) {
        global $wpdb;
        $order = wc_get_order($order_id);
        if (!$order) return null;
   
        $order_data = [];
   
        // Basic customer info
        $order_data['order_id'] = $order_id;
        $order_data['customer_name'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $order_data['email'] = $order->get_billing_email();
        $order_data['address'] = $order->get_billing_address_1() . ', ' .
                                 $order->get_billing_city() . ', ' .
                                 $order->get_billing_state() . ' ' .
                                 $order->get_billing_postcode();
   
        // Optional: get shipping address
        $order_data['shipping_address'] = $order->get_formatted_shipping_address();
   
        $custom_sub_oid = $order->get_meta( '_orthoney_OrderID' ); // replace with actual key
       // echo $results;
 
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}oh_recipient_order WHERE order_id = %d",
                $custom_sub_oid
            ),
            ARRAY_A
        );

    
        $order_data['suborder'] = $results;
 
        $suborder = [];
        foreach ($order_data['suborder'] as $suborder_data) {
         
            $suborder_product_id = $suborder_data['pid'];
            $suborder_affiliate_token = $suborder_data['affiliate_token'];
 
            $suborder_full_name = $suborder_data['full_name'];
            $suborder_data_company_name = $suborder_data['company_name'];
            $suborder_data_city = $suborder_data['city'];
            $suborder_data_state = $suborder_data['state'];
            $suborder_data_zipcode = $suborder_data['zipcode'];
            $suborder_data_country = $suborder_data['country'];
            $suborder_data_address_1 = $suborder_data['address_1'];
            $suborder_data_address_2 = $suborder_data['address_2'];
            $suborder_data_quantity = $suborder_data['quantity'];

            $userinfo = $wpdb->get_var(
                $wpdb->prepare(
                    "
                    SELECT um.meta_value
                    FROM {$wpdb->prefix}usermeta um
                    INNER JOIN {$wpdb->prefix}yith_wcaf_affiliates af ON um.user_id = af.user_id
                    WHERE um.meta_key = %s
                    AND af.token = %s
                    ",
                    '_userinfo',
                    $suborder_affiliate_token
                )
            );


            // Define the table names with the WordPress prefix
            $affiliate_table = $wpdb->prefix . 'yith_wcaf_affiliates';
            $usermeta_table = $wpdb->prefix . 'usermeta';

            // Define the query to fetch first name and filter by token 'AAC'
            $query = "
                SELECT meta.meta_value as first_name
                FROM {$affiliate_table} affiliate
                JOIN {$usermeta_table} meta ON meta.user_id = affiliate.user_id
                WHERE meta.meta_key = '_yith_wcaf_first_name'
                AND affiliate.token = %s
                GROUP BY affiliate.user_id
            ";

            // Prepare and execute the query with the 'AAC' token
            $affiliate_org_name = $wpdb->get_var($wpdb->prepare($query, $suborder_affiliate_token));
            
         

            
   
            $sub[] = [
               
                'suborder_affiliate_org_name' => $affiliate_org_name,

                'suborder_product_id' => $suborder_product_id,
                'suborder_affiliate_token' => $suborder_affiliate_token,
                'suborder_affiliate_user_info' => $userinfo,
                'suborder_full_name' => $suborder_full_name,
                'suborder_data_company_name' => $suborder_data_company_name,
                'suborder_data_city' => $suborder_data_city,
                'suborder_data_state' => $suborder_data_state,
                'suborder_data_zipcode' => $suborder_data_zipcode,
                'suborder_data_country' => $suborder_data_country,
                'suborder_data_address_1' => $suborder_data_address_1,
                'suborder_data_address_2' => $suborder_data_address_2,
                'suborder_data_quantity' => $suborder_data_quantity,
                'suborder_data_userinfo' => $userinfo,
            ];
        }
        $order_data['suborderdata'] = $sub;
   
        // Totals
        $order_data['total'] = $order->get_total();
        $order_data['formatted_total'] = wc_price($order->get_total());
   
        return $order_data;
    }

    public static function redirect_logged_in_user_to_dashboard() {

        /**
         * Save password 
         */
        if (isset($_POST['save_password'])) {
            if (!isset($_POST['save-password-nonce']) || !wp_verify_nonce($_POST['save-password-nonce'], 'save_password')) {
                wc_add_notice(__('Security check failed. Try again.', 'woocommerce'), 'error');
                return;
            }
    
            $user_id = get_current_user_id();
            $current_password = !empty($_POST['password_current']) ? $_POST['password_current'] : '';
            $new_password = !empty($_POST['password_1']) ? $_POST['password_1'] : '';
            $confirm_password = !empty($_POST['password_2']) ? $_POST['password_2'] : '';
    
            // Validate password fields
            if (empty($new_password) || empty($confirm_password)) {
                wc_add_notice(__('Please enter a new password.', 'woocommerce'), 'error');
                return;
            }
    
            if ($new_password !== $confirm_password) {
                wc_add_notice(__('New passwords do not match.', 'woocommerce'), 'error');
                return;
            }
    
            // Verify current password
            $user = get_user_by('ID', $user_id);
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                wc_add_notice(__('Current password is incorrect.', 'woocommerce'), 'error');
                return;
            }
    
            // Update password
            wp_set_password($new_password, $user_id);
            wc_add_notice(__('Password changed successfully.', 'woocommerce'), 'success');
            wp_redirect(wc_get_page_permalink('myaccount')); // Refresh page
            exit;
        }

        
        /**
         * Modify Password Less login URL
         */

        if (isset($_GET['pl']) && $_GET['pl'] == 'true'){
            $user_id = get_current_user_id();
            $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);
            if(!empty($user_roles)){
                wp_redirect(self::redirect_user_based_on_role($user_roles));
                exit;
            }
        }
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? ''; 
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $full_page_url = $protocol . $host . $uri;
        // Check if the user is logged in and visiting the login page
        if ( is_user_logged_in() && ( 
            $full_page_url ==  CUSTOMER_LOGIN_LINK  ||  
            $full_page_url ==  CUSTOMER_REGISTER_LINK  ||  
            $full_page_url ==  ORGANIZATION_LOGIN_LINK  ||  
            $full_page_url ==  ORGANIZATION_REGISTER_LINK
            ) ) {

            $user = wp_get_current_user();
            
            wp_redirect(self::redirect_user_based_on_role($user->roles));
            exit;
            
        }

    }

    /**
     * ACF JSON Save Path.
     */
    public function oh_acf_json_save_path() {
        return OH_PLUGIN_DIR_PATH . 'acf-json';
    }

    public static function custom_redirect_admin_if_has_admin_role( $user_login, $user ) {
        // Check if the user has the 'administrator' role
        if ( in_array( 'administrator', (array) $user->roles ) ) {
            wp_safe_redirect( admin_url() ); // Redirect to WP Admin Dashboard
            exit; // Stop further execution
        }
    }

    public static function add_login_link_pl_login_form() {
        if (isset($_GET['pl']) && $_GET['pl'] == 'true'){
            echo '<div class="custom-login-btn"><a href="' .esc_url( ur_get_login_url() ) . '">Login with Password</a></div>';
        }
    }

    public static function add_content_pl_login_form() {
        if (isset($_GET['pl']) && $_GET['pl'] == 'true'){
            echo '<div class="custom-login-paragraph"><p>Please enter your email in the field below. A login link will be sent to your email, allowing you to log in automatically without a password.</p></div>';
        }
    }

    public function custom_login_button_shortcode() {
        $user = wp_get_current_user();
        $roles = $user->roles;
        $display_name = $user->display_name;
        
        $output = '<ul>';
        
        if (is_user_logged_in()) {
            $output .= '<li>Hi, ' . $display_name . '!</li>';
            if (in_array('administrator', $roles)) {
                $output .= '<li><a href="' . CUSTOMER_DASHBOARD_LINK . '">Customer Dashboard</a></li>';
                $output .= '<li><a href="' . ORGANIZATION_DASHBOARD_LINK . '">Organization Area</a></li>';
                $output .= '<li><a href="' . SALES_REPRESENTATIVE_DASHBOARD_LINK . '">Sales Representative Area</a></li>';
            } else {
                // Check for customer without affiliate roles
                if (in_array('customer', $roles) && !in_array('yith_affiliate', $roles) && !in_array('affiliate_team_member', $roles)) {
                    $output .= '<li><a href="' . CUSTOMER_DASHBOARD_LINK . '">Customer Area</a></li>';
                } else {
                    if (in_array('customer', $roles)) {
                        $output .= '<li><a href="' . CUSTOMER_DASHBOARD_LINK . '">Customer Area</a></li>';
                    }
                    if (in_array('yith_affiliate', $roles) || in_array('affiliate_team_member', $roles)) {
                        $output .= '<li><a href="' . ORGANIZATION_DASHBOARD_LINK . '">Organization Area</a></li>';
                    }
                    if (in_array('sales_representative', $roles)) {
                        $output .= '<li><a href="' . SALES_REPRESENTATIVE_DASHBOARD_LINK . '">Sales Representative Area</a></li>';
                    }
                }
            }
            // Add logout link
            $output .= '<li><a href="' . wp_logout_url(site_url()) . '">Logout</a></li>';
            $output .= self::switch_back_user();
            
        } else {
            $output .= '<li><a href="' . ur_get_login_url() . '">Customer Login</a></li>';
            $output .= '<li><a href="' . ORGANIZATION_LOGIN_LINK . '">Organization Login</a></li>';
        }
    
        $output .= '</ul>';
        return $output;
    }


    /**
     * ACF JSON Load Paths.
     */
    public function oh_acf_json_load_paths($paths) {
        $paths[] = OH_PLUGIN_DIR_PATH . 'acf-json';
        return $paths;
    }

    public static function check_process_exist($csv_name, $process_id) {
        global $wpdb;
        $order_process_table = OAM_Helper::$order_process_table;
        $processExistQuery = $wpdb->prepare("
        SELECT id
        FROM {$order_process_table}
        WHERE user_id = %d 
        AND name = %s 
        AND id != %d 
        ", get_current_user_id(), $csv_name, $process_id);
        $processExistResult = $wpdb->get_var($processExistQuery);

        return !empty($processExistResult);
    }

    /**
     * Add custom login URL in after the form button 
     * NOTE: $form_id is https://appnet-dev.com/orthoney/registration/
     */
    public static function add_login_user_registration_after_submit_buttons($form_id) {
        if($form_id == 475){
            echo '<div class="registration-form-link">Existing Customers, <a href="'.esc_url( ur_get_login_url() ).'">Log In Here</a></div>';
        }
    
    }


    public static function message_design_block($message, $url = '', $btn_name = '') {    
        if ($message != '') {
            $buttonHtml = '';
            if (!empty($url) && !empty($btn_name)) {
                $buttonHtml = '<a href="' . $url . '" class="w-btn us-btn-style_1 login-btn">'. $btn_name .'</a>';
            }
    
            return '<div class="login-block">
                <div class="login-container">
                    <span>' . $message . '</span>
                    ' . $buttonHtml . '
                </div>
                <div class="animate-bee moveImgup">
                    <div class="image-block">
                        <img src="' . OH_PLUGIN_DIR_URL . 'assets/image/honey-bee.png" />
                    </div>
                </div>
            </div>';
        }
    }

    function check_userrole_update_meta($user_id) {
        $user = get_userdata($user_id);
        $roles = $user->roles; // Get the user's roles (array)
        
        $logged_in_user = 0;
        // Check if the user was created by a logged-in user or a guest
        if (is_user_logged_in()) {
            $logged_in_user = wp_get_current_user();
        }

    }
    
    public static function get_user_role_by_id($user_id) {
        $user = get_userdata($user_id);
        return !empty($user) ? $user->roles : [];
    }

    /**
     * Get and display the ACF field 'orthoney_product_selector'.
     */
    public static function get_product_id() {
        return $product_id = get_field('orthoney_product_selector','options');
    }


     /**
     * info block
     */
    public static function info_block($title, $content) {    
        if (!empty($title)) {
            return '<div class="place-order item">
            <div class="row-block">
            <h4 class="block-title">' . esc_html($title) . '</h4>
            <div class="see-all"><a href="'.esc_url(ORDER_PROCESS_LINK).'" class="w-btn us-btn-style_1">Order Now</a></div>
            </div>
            <div class="description">' . html_entity_decode($content) . '</div>
            </div>';
        }
        
        return '';
    }
    
    public static function reset_password_redirection ($redirect, $user) {
        return self::redirect_user_based_on_role($user->roles); // Return the correct URL
    }
    public static function custom_user_registration_email_exists_message( $message, $form_data ) {
        if ( isset( $message['individual'] ) && $message['individual'] === true ) {
            foreach ( $message as $key => $value ) {
                if ( $key !== 'individual' && $value === 'Email already exists.' ) {
                    $message[$key] = 'This email is already registered. Please use a different email.';
                }
            }
        }
        return $message;
    }
 
}

// Instantiate and initialize
new OAM_COMMON_Custom();
OAM_COMMON_Custom::init();