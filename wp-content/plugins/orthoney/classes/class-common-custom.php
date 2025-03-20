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
        add_action('template_redirect', array($this, 'modify_passwordless_login_url'));

        // add_action('user_registration_after_submit_buttons', array($this, 'add_login_user_registration_after_submit_buttons'));

        add_action('user_registration_after_login_form', array($this, 'add_login_link_pl_login_form'));
        add_action('user_registration_before_customer_login_form', array($this, 'add_content_pl_login_form'));

    }

    public static function init() {
        // Add any initialization logic here
    }

    public static function sub_order_error_log($message) {
        $log_file = WP_CONTENT_DIR . '/logs/sub-order-error.log';
        
        // Ensure log directory exists
        if (!file_exists(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
    
        // Format the error message
        $log_message = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
    
        // Write to the log file
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }

    /**
     * ACF JSON Save Path.
     */
    public function oh_acf_json_save_path() {
        return OH_PLUGIN_DIR_PATH . 'acf-json';
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
     * Modify Password Less login URL
     */

    public static function modify_passwordless_login_url() {
        
        if (isset($_GET['pl']) && $_GET['pl'] == 'true'){
            $user_id = get_current_user_id();
            $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);

            if ( in_array( 'yith_affiliate', $user_roles) OR  in_array( 'affiliate_team_member', $user_roles)) { 
                wp_redirect( home_url( '/affiliate-dashboard/' ) );
                exit;
            } 
            if ( in_array( 'administrator', $user_roles)) { 
                wp_redirect( home_url( '/wp-admin/' ) );
                exit;
            } 
            if ( in_array( 'customer', $user_roles)) { 
                wp_redirect( home_url( '/customer-dashboard/' ) );
                exit;
            }            
        }
       
    }
 
}

// Instantiate and initialize
new OAM_COMMON_Custom();
OAM_COMMON_Custom::init();