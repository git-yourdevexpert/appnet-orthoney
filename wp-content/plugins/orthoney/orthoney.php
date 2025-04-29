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

require_once 'libs/dompdf/autoload.inc.php';

add_filter( 'woocommerce_checkout_fields', 'custom_disable_shipping_fields_validation' );
add_filter('woocommerce_create_order_draft_enabled', '__return_false');

function custom_disable_shipping_fields_validation( $fields ) {
    if ( isset( $fields['shipping'] ) ) {
        foreach ( $fields['shipping'] as $key => $field ) {
            $fields['shipping'][$key]['required'] = false;
        }
    }
    return $fields;
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


$link_array = array(
    'customer_dashboard_link'             => 'CUSTOMER_DASHBOARD_LINK', 
    'organization_dashboard_link'         => 'ORGANIZATION_DASHBOARD_LINK',
    'sales_representative_dashboard_link' => 'SALES_REPRESENTATIVE_DASHBOARD_LINK',
    'customer_login_link'                 => 'CUSTOMER_LOGIN_LINK',
    'customer_register_link'              => 'CUSTOMER_REGISTER_LINK',
    'organization_login_link'             => 'ORGANIZATION_LOGIN_LINK',
    'organization_register_link'          => 'ORGANIZATION_REGISTER_LINK',
    'order_process_link'                  => 'ORDER_PROCESS_LINK'
);

foreach ($link_array as $key => $constant_name) {
    if ( ! defined($constant_name) ) {
        $link = get_field($key, 'options');
        if ($link) {
            define($constant_name, $link['url']);
        }else{
            define($constant_name, home_url());
        }
    }
}

// Include required files
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
// register_activation_hook(__FILE__, 'orthoney_create_custom_tables');

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

        $custom_url = OAM_COMMON_Custom::redirect_user_based_on_role($user_roles);
        if($custom_url == home_url('wp-admin')){
            $custom_url = home_url();
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


/**
 * Affiliate Verification Start
 * 
 */

// add_filter( 'woocommerce_registration_auth_new_customer', '__return_false' );

// add_action('user_register', 'set_default_user_meta_after_register', 10, 1);

// function set_default_user_meta_after_register($user_id) {
//     $user = get_userdata($user_id);
    
//     $length         = 50;
// 		$token          = '';
// 		$code_alphabet  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
// 		$code_alphabet .= 'abcdefghijklmnopqrstuvwxyz';
// 		$code_alphabet .= '0123456789';
// 		$max            = strlen( $code_alphabet );

// 		for ( $i = 0; $i < $length; $i++ ) {
// 			$token .= $code_alphabet[ random_int( 0, $max - 1 ) ];
// 		}

// 	$token .= crypt_the_string( $user_id . '_' . time(), 'e' );

//     // Set default user meta if not exists
//     if (!metadata_exists('user', $user_id, 'ur_confirm_email_token')) {
//         // $token = ur_generate_onetime_token($user_id, 'ur_passwordless_login', 32, 60);
//         update_user_meta($user_id, 'ur_confirm_email_token', $token);
//         update_user_meta($user_id, 'ur_confirm_email_token'.$user_id, $token);
//     }

//     if (!metadata_exists('user', $user_id, 'ur_confirm_email')) {
//         update_user_meta($user_id, 'ur_confirm_email', 0);
//     }

//     if (!metadata_exists('user', $user_id, 'ur_login_option')) {
//         update_user_meta($user_id, 'ur_login_option', 'email_confirmation');
//     }

//     // Get custom login URL
//     $custom_url = wp_login_url();
//     $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);

//     if (in_array('yith_affiliate', $user_roles) || in_array('affiliate_team_member', $user_roles)) {
//         $custom_url = defined('ORGANIZATION_LOGIN_LINK') ? ORGANIZATION_LOGIN_LINK : $custom_url;
//     }

//     // Email setup
//     $first_name = $user->first_name ? $user->first_name : $user->display_name;
//     $email = $user->user_email;
//     $nonce = wp_create_nonce('ur_email_verification_' . $user_id);

//     $url_params = array(
//         'uid'   => $user->ID,
//         'ur_token' => get_user_meta($user_id, 'ur_confirm_email_token', true),
//         'nonce' => $nonce,
//     );
//     $verification_link = add_query_arg($url_params, $custom_url);

//     $subject = 'Email Verification Required';
//     $message = sprintf(
//         'Hello %s,<br><br>
//         Thank you for registering. Please click the button below to verify your email:<br><br>
//         <a href="%s" style="background-color: #4CAF50; border-radius: 5px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; padding: 12px 25px; text-decoration: none; text-transform: uppercase;">Verify Email Address</a><br><br>
//         If you did not create this account, please ignore this email.',
//         esc_html($first_name),
//         esc_url($verification_link)
//     );

//     $headers = array(
//         'Content-Type: text/html; charset=UTF-8',
//         'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
//     );

//     wp_mail($email, $subject, $message, $headers);
// }

/**
 * Affiliate Verification end
 * 
 */


// /affiliate-dashboard-user-list-menu-item
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
