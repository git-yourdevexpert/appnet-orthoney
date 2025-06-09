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

        add_shortcode('season_start_end_message_box', array($this,'season_start_end_message_box_shortcode'));

        add_action('wp', array($this,'season_start_end_message_box_shortcode_footer'));

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

        // add_filter( 'woocommerce_registration_auth_new_customer', '__return_false' );
        // add_action( 'woocommerce_created_customer', array($this,'custom_redirect_after_registration_based_on_role' ));
        add_shortcode( 'registration_success_msg', array($this, 'show_success_message_on_login') );
        add_action( 'wp_ajax_oam_ajax_logout',array($this, 'oam_ajax_logout') );

    }

    public static function init() {}

        
    public function season_start_end_message_box_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'popup' => '',
            'date' => '',
        ), $atts);

        if ($atts['type'] === 'order') {
            ?>
            <style>
                #page-content{
                background:#ddd;
                }
                .us_custom_76d41440 {
                    padding-top: 0rem !important;
                    padding-bottom: 1rem !important;
                }
                .l-section.wpb_row.us_custom_19737bc8 .g-cols.vc_row{
                    display:none;
                }
            </style>
            <?php
        }
        ob_start();

        $today = current_time('m/d/Y H:i:s');
        $season_start_date = get_field('season_start_date', 'option');
        $season_end_date = get_field('season_end_date', 'option');

        $current_timestamp      = strtotime($today);
        $season_start_timestamp = strtotime($season_start_date);
        $season_end_timestamp   = strtotime($season_end_date);

        $is_within_range = ( $current_timestamp >= $season_start_timestamp && $current_timestamp <= $season_end_timestamp );

        if ( ! $is_within_range ) {
        $seasonStartDate = new DateTime($season_start_date);

        // Format as MM/DD/YYYY
        $formatted_numeric = $seasonStartDate->format('m/d/Y H:i:s');

        // Format as "June 12, 2025"
        $startDay = $seasonStartDate->format('j');
        $formatted_text = $seasonStartDate->format("F {$startDay}, Y");

        if ($current_timestamp <= $season_start_timestamp){
        ?>
            <div class="season_start_end_message_box <?php echo $atts['popup'] ?>" style="<?php echo $atts['popup'] === 'withpopup' ? 'display:none' : '' ?>">
                <div class="popupbox <?php echo $atts['popup'] ?>">
                    <div class="content-wrapper">
                        <div class="bee-animation"><img decoding="async" width="72" height="72" src="/wp-content/uploads/2025/06/bee.png" class="attachment-full size-full" alt="Bee" loading="lazy"></div>
                        <div class="close-popup" onclick="this.closest('.popupbox').style.display='none'">x</div>
                        <div class="top-content">
                            <h2>The Hive’s Just Waking Up… Get Ready for the Buzz!</h2>
                            <div class="subtext">We’re almost ready to launch this season’s buzz-worthy tradition and trust us, it’s going to bee amazing!</div>
                            <p>Our honey isn’t flowing just yet, but the hive opens for gifting on <strong class="green-text"><?php echo $formatted_text ?></strong></p>
                            <div class="border-top-bottom">Please come back soon to Send Honey. Share Hope. Spread Joy.</div>
                            <div class="notifyme">
                                <span>Your friends at Honey From The Heart</span>
                                <a class="w-btn us-btn-style_2 us_custom_29a0f245" href="https://www.orthoney.com/sign-up/"><span class="w-btn-label">Notify Me</span></a>
                            </div>
                            <div id="countdown" class="countdown" data-date="<?php echo $formatted_numeric ?>" data-currentdate="<?php echo $today ?>">
                                <ul>
                                    <li><span class="days"></span> Days</li>
                                    <li><span class="hours"></span> Hours</li>
                                    <li><span class="minutes"></span> Minutes</li>
                                    <li><span class="seconds"></span> Seconds</li>
                                </ul>
                            </div>
                        </div>
                        <div class="bottom-content"></div>
                    </div>
                </div>
            </div>
        <?php
        }

        $next_year_season_start_date = get_field('next_year_season_start_date', 'option');

        $nextYearSeasonStartDate = new DateTime($next_year_season_start_date);

        // Format as MM/DD/YYYY
        $next_year_formatted_numeric = $nextYearSeasonStartDate->format('m/d/Y H:i:s');

        // Format as "June 12, 2025"
        $nextYearStartDay = $nextYearSeasonStartDate->format('j');
        $next_year_formatted_text = $nextYearSeasonStartDate->format("F {$nextYearStartDay}, Y");
        
        if ($current_timestamp >= $season_end_timestamp) {
            ?>
            <div class="season_start_end_message_box <?php echo $atts['popup'] ?>" style="<?php echo $atts['popup'] === 'withpopup' ? 'display:none' : '' ?>">
                <div class="popupbox <?php echo $atts['popup'] ?>">
                    <div class="content-wrapper">
                        <div class="bee-animation"><img decoding="async" width="72" height="72" src="/wp-content/uploads/2025/06/bee.png" class="attachment-full size-full" alt="Bee" loading="lazy"></div>
                        <div class="close-popup" onclick="this.closest('.popupbox').style.display='none'">x</div>
                        <div class="top-content">
                            <h2>The Hive Is Slowing Down… But the Sweetness Will Return!</h2>
                            <div class="subtext">As the honey season is now closed, we’re filled with gratitude for the kindness you’ve shared. </div>
                            <p>We’ll be buzzing again in <strong class="green-text"><?php echo $next_year_formatted_text ?>,</strong> and we can’t wait to share the sweetness with you once more.</strong></p>
                            <div class="border-top-bottom">Until then — thank you for helping us Send Honey. Share Hope. Spread Joy.</div>
                            <div class="message-txt">
                                With heartfelt appreciation,<br>
                                Your friends at Honey From The Heart<br><br>
                                </div>
                            <div class="notifyme">
                                <span>Be the first to know when the season opens!</span>
                                <a class="w-btn us-btn-style_2 us_custom_29a0f245" href="https://www.orthoney.com/sign-up/"><span class="w-btn-label">Notify Me</span></a>
                            </div>
                            <div id="countdown" class="countdown" data-date="<?php echo $next_year_formatted_numeric ?>" data-currentdate="<?php echo $today ?>">
                                 <ul>
                                    <li><span class="days"></span> Days</li>
                                    <li><span class="hours"></span> Hours</li>
                                    <li><span class="minutes"></span> Minutes</li>
                                    <li><span class="seconds"></span> Seconds</li>
                                </ul>
                            </div>
                        </div>
                        <div class="bottom-content"></div>
                    </div>
                </div>
            </div>
            <?php
            }
        }
    
        return ob_get_clean();
    }

    public function season_start_end_message_box_shortcode_footer($atts) {
        if ( is_front_page() ) {
            $current_date = current_time('Y-m-d H:i:s');

            $season_start_date = get_field('season_start_date', 'option');
            $season_end_date   = get_field('season_end_date', 'option');

            // Convert all dates to timestamps
            $current_timestamp      = strtotime($current_date);
            $season_start_timestamp = strtotime($season_start_date);
            $season_end_timestamp   = strtotime($season_end_date);

            // Check if current date is within the season range
            $is_within_range = ( $current_timestamp >= $season_start_timestamp && $current_timestamp <= $season_end_timestamp );

            if ( $is_within_range === false ) {
                echo do_shortcode("[season_start_end_message_box type='order' popup='withpopup']");
            }
        }
    }
    
    public function oam_ajax_logout() {
        wp_logout();            
        wp_send_json_success();
    }
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
            <div class="woocommerce-message"> Your account has been created successfully. Your application is pending approval. Thank you for reaching out!</div>
            <?php
        }
        return ob_get_clean();
    }
   

    public static function check_order_editable($order_date) {
        $editable = false;
          // Get current WordPress timestamp and current year
        $current_time  = current_time('timestamp');
        $current_year  = date('Y', $current_time);

        // Get order timestamp and year
        $order_timestamp = $order_date->getTimestamp();
        $order_year      = date('Y', $order_timestamp);

    
        // Get fulfilment window dates from ACF options
        $start_date = get_field('fulfilment_start_date', 'option'); 
        $end_date   = get_field('fulfilment_end_date', 'option');
        $start_timestamp = strtotime($start_date);
         if ($order_year != $current_year) {
            return false;
         }else{
             if ($current_time <= $start_timestamp) {
                return true;
             }else{
                // Allow editing within 4 hours of order time
                $editable_until = $order_timestamp + (4 * HOUR_IN_SECONDS);
                if ($current_time <= $editable_until) {
                    return true;
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
        global $wpdb;

        $product = wc_get_product($product_id);
        $price = get_field('selling_minimum_price', 'option') ?: 18;

        if ($affiliate_id != 0) {
            // Get the affiliate ID from yith_wcaf_affiliates table
            $affiliate_id_result = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id = %d",
                $affiliate_id
            ));

            if ($affiliate_id_result) {
                // Get the user-specific price
                $custom_price = get_user_meta($affiliate_id, 'DJarPrice', true);

                // Make sure it's a valid numeric value and not lower than the default price
                if ($custom_price !== '' && is_numeric($custom_price) && floatval($custom_price) >= floatval($price)) {
                    $price = floatval($custom_price);
                }
            }
        }

        return $price;
    }


    public static function redirect_user_based_on_role($roles) {
        $redirects = [
            'administrator'         => ADMINISTRATOR_DASHBOARD_LINK,
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
        
                if($old_user->id  != get_current_user_id() ){
                 $html .='<a href="' . esc_url($switch_back_url) . '" class="btn-with-arrow">Switch Back to ' . esc_html($old_user->display_name) . '</a>';
                }
            }
        }
        return $html;
    }
    
    public static function set_affiliate_cookie($token, $remove = 0) {
        global $wpdb;

        if ($remove == 1) {
            // Remove the cookies

            yith_wcaf_delete_cookie('yith_wcaf_referral_token');
            yith_wcaf_delete_cookie('yith_wcaf_referral_history');
           
            return;
        }

        
        if ($token !== 'Orthoney') {
         
            $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;

            // Correct query execution
            $processExistResult = $wpdb->get_var($wpdb->prepare("
                SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d
            ", $token));

            if (!$processExistResult) {
                return;
            }

            setcookie('yith_wcaf_referral_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            setcookie('yith_wcaf_referral_history', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            unset($_COOKIE['yith_wcaf_referral_token']);
            unset($_COOKIE['yith_wcaf_referral_history']);
            yith_wcaf_set_cookie( 'yith_wcaf_referral_token', $processExistResult, WEEK_IN_SECONDS );
            yith_wcaf_set_cookie( 'yith_wcaf_referral_history', $processExistResult, WEEK_IN_SECONDS );
            
        } else {
            setcookie('yith_wcaf_referral_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            setcookie('yith_wcaf_referral_history', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            unset($_COOKIE['yith_wcaf_referral_token']);
            unset($_COOKIE['yith_wcaf_referral_history']);
            
            yith_wcaf_set_cookie( 'yith_wcaf_referral_token', $token, WEEK_IN_SECONDS );
            yith_wcaf_set_cookie( 'yith_wcaf_referral_history', $token, WEEK_IN_SECONDS );
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

    // Helper inline cleaner
    $clean = function ($value) {
        return (empty($value) || strtolower($value) === 'null') ? '' : $value;
    };

    // Basic customer info
    $first_name = $clean($order->get_billing_first_name());
    $last_name  = $clean($order->get_billing_last_name());
    $order_data['order_id'] = $order_id;
    $order_data['customer_name'] = trim("{$first_name} {$last_name}");

    $order_data['email'] = $clean($order->get_billing_email());

    $address_line1 = $clean($order->get_billing_address_1());
    $city          = $clean($order->get_billing_city());
    $state         = $clean($order->get_billing_state());
    $postcode      = $clean($order->get_billing_postcode());

    $order_data['address'] = trim("{$address_line1}, {$city}, {$state} {$postcode}", ', ');

    // Shipping address
    $order_data['shipping_address'] = $clean($order->get_formatted_shipping_address());

    // Custom meta
    $custom_sub_oid = $clean($order->get_meta('_orthoney_OrderID'));
    $order_data['custom_sub_oid'] = $custom_sub_oid;

    // Suborders
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}oh_recipient_order WHERE order_id = %d",
            $custom_sub_oid
        ),
        ARRAY_A
    );

    $order_data['suborder'] = $results;
    $sub = [];

    if (!empty($results)) {
        foreach ($results as $suborder_data) {
            $token = $clean($suborder_data['affiliate_token'] ?? '');

            // Get _userinfo
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
                    $token
                )
            );
            $userinfo = $clean($userinfo);

            // Affiliate org name
            $org_query = "
                SELECT meta.meta_value as first_name
                FROM {$wpdb->prefix}yith_wcaf_affiliates affiliate
                JOIN {$wpdb->prefix}usermeta meta ON meta.user_id = affiliate.user_id
                WHERE meta.meta_key = '_yith_wcaf_first_name'
                AND affiliate.token = %s
                GROUP BY affiliate.user_id
            ";
            $affiliate_org_name = $wpdb->get_var($wpdb->prepare($org_query, $token));
            $affiliate_org_name = $clean($affiliate_org_name);

            $sub[] = [
                'suborder_affiliate_org_name'    => $affiliate_org_name,
                'suborder_product_id'            => $clean($suborder_data['pid'] ?? ''),
                'suborder_affiliate_token'       => $token,
                'suborder_affiliate_user_info'   => $userinfo,
                'suborder_full_name'             => $clean($suborder_data['full_name'] ?? ''),
                'suborder_data_company_name'     => $clean($suborder_data['company_name'] ?? ''),
                'suborder_data_city'             => $clean($suborder_data['city'] ?? ''),
                'suborder_data_state'            => $clean($suborder_data['state'] ?? ''),
                'suborder_data_zipcode'          => $clean($suborder_data['zipcode'] ?? ''),
                'suborder_data_country'          => $clean($suborder_data['country'] ?? ''),
                'suborder_data_address_1'        => $clean($suborder_data['address_1'] ?? ''),
                'suborder_data_address_2'        => $clean($suborder_data['address_2'] ?? ''),
                'suborder_data_quantity'         => $clean($suborder_data['quantity'] ?? ''),
                'suborder_data_userinfo'         => $userinfo,
            ];
        }
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
            wp_safe_redirect( ADMINISTRATOR_DASHBOARD_LINK ); // Redirect to WP Admin Dashboard
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
                $output .= '<li class="customer-dashboard"><a href="' . CUSTOMER_DASHBOARD_LINK . '">Customer Area</a></li>';
                $output .= '<li class="organization-dashboard"><a href="' . ORGANIZATION_DASHBOARD_LINK . '">Organization Area</a></li>';
                $output .= '<li><a href="' . SALES_REPRESENTATIVE_DASHBOARD_LINK . '">Sales Representative Area</a></li>';
                $output .= '<li><a href="' . ADMINISTRATOR_DASHBOARD_LINK . '">Administrator Area</a></li>';
                
            } 
            elseif(in_array('sales_representative', $roles) && in_array('customer', $roles)){
                    $output .= '<li class="customer-dashboard"><a href="' . CUSTOMER_DASHBOARD_LINK . '">Customer Area</a></li>';
                    $output .= '<li><a href="' . SALES_REPRESENTATIVE_DASHBOARD_LINK . '">Sales Representative Area</a></li>';
            }  else {
                // Check for customer without affiliate roles
                if (in_array('customer', $roles) && !in_array('yith_affiliate', $roles) && !in_array('affiliate_team_member', $roles)) {
                    $output .= '<li class="customer-dashboard"><a href="' . CUSTOMER_DASHBOARD_LINK . '">Customer Area</a></li>';
                } else {
                    if (in_array('customer', $roles)) {
                        $output .= '<li class="customer-dashboard"><a href="' . CUSTOMER_DASHBOARD_LINK . '">Customer Area</a></li>';
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
        AND visibility = %d 
        ", get_current_user_id(), $csv_name, $process_id, 1);
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
        $user = get_user_by('id', $user_id);
        // Get the role set at creation time (from $_POST)
        if (isset($_POST['role']) && $_POST['role'] === 'sales_representative') {
            // Add customer role if sales_representative
            $user->add_role('customer');
        }
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