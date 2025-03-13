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



    }

    public static function init() {
        // Add any initialization logic here
    }
    /**
     * ACF JSON Save Path.
     */
    public function oh_acf_json_save_path() {
        return OH_PLUGIN_DIR_PATH . 'acf-json';
    }

    /**
     * ACF JSON Load Paths.
     */
    public function oh_acf_json_load_paths($paths) {
        $paths[] = OH_PLUGIN_DIR_PATH . 'acf-json';
        return $paths;
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