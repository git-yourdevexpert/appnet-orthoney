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

function custom_rename_coupon_to_voucher( $translated, $text, $domain ) {
    $text = str_ireplace('coupon', 'Voucher', $text);
    $text = str_ireplace('Coupon', 'Voucher', $text);
    return $text;
}
add_filter( 'gettext', 'custom_rename_coupon_to_voucher', 10, 3 );
add_filter( 'ngettext', 'custom_rename_coupon_to_voucher', 10, 3 );


// add_action( 'init', 'createDB' );
// function createDB(){
//     global $wpdb;

// $tables = [
//     'wc_order_relation_table' => $wpdb->prefix . 'oh_wc_order_relation',
// ];

// $sql = "CREATE TABLE {$tables['wc_order_relation_table']} (
//     id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//             user_id BIGINT(20) UNSIGNED NOT NULL,
//             wc_order_id BIGINT(20) UNSIGNED NOT NULL,
//             order_id BIGINT(20) UNSIGNED NOT NULL,
//             quantity BIGINT(20) UNSIGNED NOT NULL,
//             order_type VARCHAR(255) NULL,
//             affiliate_code VARCHAR(255) NULL,
//             affiliate_user_id BIGINT(20) DEFAULT 0,
//             created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
//             PRIMARY KEY (id)
// ) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

// require_once ABSPATH . 'wp-admin/includes/upgrade.php';
// dbDelta($sql);
// }



// Ensure WooCommerce is active before loading the plugin
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p><strong>Warning:</strong> Your custom plugin requires WooCommerce to be activated.</p></div>';
    });
    return;
}

// Define required columns
$required_columns = ['full name','Company Name', 'Mailing Address', 'Suite/Apt#', 'city', 'state', 'zipcode', 'quantity', 'greeting'];
$exclude_coupon = ['freeshipping'];

if ( ! defined( 'OH_REQUIRED_COLUMNS' ) ) {
    define( 'OH_REQUIRED_COLUMNS', $required_columns );
}
if ( ! defined( 'EXCLUDE_COUPON' ) ) {
    define( 'EXCLUDE_COUPON', $exclude_coupon );
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
    'administrator_dashboard_link'        => 'ADMINISTRATOR_DASHBOARD_LINK',
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

// Administrator
require_once OH_PLUGIN_DIR_PATH . 'classes/administrator/class-administrator.php';

// Register activation hook
// register_activation_hook(__FILE__, 'orthoney_create_custom_tables');

// Refresh database if requested
if(isset($_GET['database_refresh']) && ($_GET['database_refresh'] == 'okay' OR $_GET['database_refresh'] == 'new') ){
    add_action('init', 'orthoney_create_custom_tables');
}

add_action( 'init', 'add_customer_role_to_affiliates' );

add_action('admin_head', function() {
    echo '<style>
        .ui-datepicker-current.ui-state-default.ui-priority-secondary.ui-corner-all {
            display: none !important;
        }
    </style>';
});

function add_customer_role_to_affiliates() {
    

    if( isset($_GET['add_customer_role_to_affiliates']) && $_GET['add_customer_role_to_affiliates'] == 'yes'){
        $affiliates = get_users( array(
            'role' => 'yith_affiliate'
        ) );

        foreach ( $affiliates as $user ) {
            if ( ! in_array( 'customer', (array) $user->roles ) ) {
                $user->add_role( 'customer' );
            }
        }
    }

    if( isset($_GET['season_popup']) && $_GET['season_popup'] == 'yes'){

        ?>
        <?php
    }
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



// add_filter( 'query', function( $query ) {
//      global $wpdb;

//     $orders_table   = $wpdb->prefix . 'wc_orders';
//     $relation_table = $wpdb->prefix . 'oh_wc_order_relation';

//     if (
//         strpos( $query, $orders_table ) !== false || 
//         strpos( $query, $relation_table ) !== false
//     ) {
//         error_log( 'Query Hooked: ' . $query );
//     }
//     return $query;
// });

// Configuration - Replace with your actual reCAPTCHA keys
define('RECAPTCHA_SITE_KEY', '6LdxR18rAAAAAH2rovDzb6HcIT-4QU_8Wn9KxARs');
define('RECAPTCHA_SECRET_KEY', '6LdxR18rAAAAAHvojZ8prxD940I07CU-cGiditKg');

/**
 * Enqueue reCAPTCHA v3 script
 */
function yith_affiliates_enqueue_recaptcha_script() {
    // Only load on affiliate registration page
    if (is_page() && has_shortcode(get_post()->post_content, 'yith_wcaf_registration_form')) {
        wp_enqueue_script(
            'google-recaptcha-v3',
            'https://www.google.com/recaptcha/api.js?render=' . RECAPTCHA_SITE_KEY,
            array(),
            '3.0',
            true
        );
        
        // Add inline script for reCAPTCHA execution
        wp_add_inline_script('google-recaptcha-v3', '
            function executeRecaptcha() {
                if (typeof grecaptcha !== "undefined") {
                    grecaptcha.ready(function() {
                        grecaptcha.execute("' . RECAPTCHA_SITE_KEY . '", {action: "affiliate_registration"}).then(function(token) {
                            var recaptchaInput = document.getElementById("g-recaptcha-response");
                            if (recaptchaInput) {
                                recaptchaInput.value = token;
                            }
                        });
                    });
                }
            }
            
            // Execute on page load
            document.addEventListener("DOMContentLoaded", function() {
                executeRecaptcha();
            });
            
            // Re-execute before form submission
            document.addEventListener("submit", function(e) {
                if (e.target.closest(".yith-wcaf-registration-form")) {
                    e.preventDefault();
                    executeRecaptcha();
                    setTimeout(function() {
                        e.target.submit();
                    }, 500);
                }
            });
        ');
    }
}
add_action('wp_enqueue_scripts', 'yith_affiliates_enqueue_recaptcha_script');

/**
 * Add reCAPTCHA hidden field to registration form
 */
function yith_affiliates_add_recaptcha_field() {
    echo '<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" value="">';
    echo '<div class="recaptcha-notice" style="font-size: 12px; color: #666; margin-top: 10px;">';
    echo 'This site is protected by reCAPTCHA and the Google ';
    echo '<a href="https://policies.google.com/privacy" target="_blank">Privacy Policy</a> and ';
    echo '<a href="https://policies.google.com/terms" target="_blank">Terms of Service</a> apply.';
    echo '</div>';
}
add_action('  woocommerce_register_form', 'yith_affiliates_add_recaptcha_field');

/**
 * Verify reCAPTCHA on form submission
 */
function yith_affiliates_verify_recaptcha($errors, $form_data) {
    // Check if reCAPTCHA response exists
    if (empty($_POST['g-recaptcha-response'])) {
        $errors->add('recaptcha_missing', __('reCAPTCHA verification is required.', 'yith-woocommerce-affiliates'));
        return $errors;
    }
    
    $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);
    
    // Verify with Google
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $response = wp_remote_post($verify_url, array(
        'body' => array(
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $recaptcha_response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        )
    ));
    
    if (is_wp_error($response)) {
        $errors->add('recaptcha_error', __('reCAPTCHA verification failed. Please try again.', 'yith-woocommerce-affiliates'));
        return $errors;
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Check if verification was successful
    if (!$result['success']) {
        $errors->add('recaptcha_failed', __('reCAPTCHA verification failed. Please try again.', 'yith-woocommerce-affiliates'));
        return $errors;
    }
    
    // Check score (optional - adjust threshold as needed)
    if (isset($result['score']) && $result['score'] < 0.5) {
        $errors->add('recaptcha_score', __('Security verification failed. Please try again.', 'yith-woocommerce-affiliates'));
        return $errors;
    }
    
    return $errors;
}
add_filter('yith_wcaf_check_affiliate_validation_errors', 'yith_affiliates_verify_recaptcha', 10, 2);
