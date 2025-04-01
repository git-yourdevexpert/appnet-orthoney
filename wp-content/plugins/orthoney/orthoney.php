<?php
/*
Plugin Name: ORT Honey
Description: This is ORT Honey plugin!
Author: ORT Honey
Requires Plugins: woocommerce
*/
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure WooCommerce is active before loading the plugin
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p><strong>Warning:</strong> Your custom plugin requires WooCommerce to be activated.</p></div>';
    });
    return;
}

// Define required columns
$required_columns = ['full name','Company Name', 'Mailing Address', 'Suite/Apt', 'city', 'state', 'zipcode', 'quantity', 'greeting'];

if ( ! defined( 'OH_REQUIRED_COLUMNS' ) ) {
	define( 'OH_REQUIRED_COLUMNS', $required_columns );
}

if ( ! defined( 'OH_PLUGIN_DIR_URL' ) ) {
	define( 'OH_PLUGIN_DIR_URL', plugin_dir_url(__FILE__) );
}
if ( ! defined( 'OH_PLUGIN_DIR_PATH' ) ) {
	define( 'OH_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__)  );
}
if ( ! defined( 'OH_DOMAIN' ) ) {
	define( 'OH_DOMAIN', 'ORTHONEY'  );
}

// Include necessary files
require_once OH_PLUGIN_DIR_PATH . 'includes/database.php';

require_once OH_PLUGIN_DIR_PATH . 'classes/class-wc.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-cron-sub-order.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-hooks.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-common-custom.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-user-role.php';
// require_once OH_PLUGIN_DIR_PATH . 'classes/class-login-registration.php';


// YITH Affilate Plugin Custmization
require_once OH_PLUGIN_DIR_PATH . 'classes/yith-affilate-custimization.php';

// Affiliate
require_once OH_PLUGIN_DIR_PATH . 'classes/affiliate/class-affiliate.php';

// Customer
require_once OH_PLUGIN_DIR_PATH . 'classes/customer/class-customer.php';

// Sales Representative
require_once OH_PLUGIN_DIR_PATH . 'classes/sales-representative/class-sales-representative.php';

// Register activation hook
register_activation_hook(__FILE__, 'orthoney_create_custom_tables');

// Refresh database if requested
if(isset($_GET['database_refresh']) && ($_GET['database_refresh'] == 'okay' OR $_GET['database_refresh'] == 'new') ){
    add_action('init', 'orthoney_create_custom_tables');
}

if ( ! function_exists( 'user_registration_pro_generate_magic_login_link' ) ) {
    function user_registration_pro_generate_magic_login_link( $email, $nonce, $redirect_url ) {
        $user  = get_user_by( 'email', $email );
        $token = ur_generate_onetime_token( $user->ID, 'ur_passwordless_login', 32, 60 );

        update_user_meta( $user->ID, 'ur_passwordless_login_redirect_url' . $user->ID, $redirect_url );

        $custom_url = ''; // Default login link
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user->ID);

        if (in_array('customer', $user_roles)) { 
            $custom_url = home_url('/customer-dashboard/');
        }
        if (in_array('yith_affiliate', $user_roles) || in_array('affiliate_team_member', $user_roles)) { 
            $custom_url = home_url('/affiliate-dashboard/');
        } 
        if (in_array('administrator', $user_roles)) { 
            $custom_url = home_url('/wp-admin/');
        }
        $arr_params = array( 'action', 'uid', 'token', 'nonce' );
        $url_params = array(
            'uid'   => $user->ID,
            'token' => $token,
            'nonce' => $nonce,
        );
        $url = add_query_arg( $url_params, $custom_url );

        return $url;
    }
}





// if (!function_exists('user_has_role')) {
//     function user_has_role($user_id, $roles_to_check = []) {
//         $user = get_userdata($user_id);
        
//         if ($user && !empty($roles_to_check)) {
//             $user_roles = (array) $user->roles;
//             return array_intersect($roles_to_check, $user_roles) ? true : false;
//         }
//         return false;
//     }
// }

// // 🔹 1. Redirect after login
// function custom_login_redirect($redirect_to, $request, $user) {
//     if (isset($user->ID)) {
//         $roles_to_check = ['yith_affiliate', 'affiliate_team_member'];
        
//         if (user_has_role($user->ID, $roles_to_check)) {
//             return site_url('/affiliate-dashboard'); // Change this to your actual affiliate dashboard page
//         }
//     }
//     return $redirect_to;
// }
// add_filter('login_redirect', 'custom_login_redirect', 10, 3);

// // 🔹 2. Prevent admin dashboard access
// function restrict_admin_access() {
//     if (is_user_logged_in()) {
//         $user_id = get_current_user_id();
//         $roles_to_check = ['yith_affiliate', 'affiliate_team_member'];
        
//         if (user_has_role($user_id, $roles_to_check) && is_admin()) {
//             wp_redirect(site_url('/affiliate-dashboard')); // Redirect to Affiliate Dashboard
//             exit;
//         }
//     }
// }
// add_action('admin_init', 'restrict_admin_access');

// // 🔹 3. Hide admin bar for specific users
// function hide_admin_bar_for_affiliates($show_admin_bar) {
//     $user_id = get_current_user_id();
//     $roles_to_check = ['yith_affiliate', 'affiliate_team_member'];
    
//     if (user_has_role($user_id, $roles_to_check)) {
//         return false;
//     }
//     return $show_admin_bar;
// }
// add_filter('show_admin_bar', 'hide_admin_bar_for_affiliates');
