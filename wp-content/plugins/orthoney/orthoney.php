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


add_filter('render_block', 'custom_checkout_phone_format_script', 10, 2);
function custom_checkout_phone_format_script($block_content, $block) {
    // Only inject into checkout page and only into relevant block
    if (is_checkout() && strpos($block['blockName'], 'woocommerce/checkout') !== false) {
        // Add JS after the block content
        $script = <<<EOD
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.querySelector('#billing #billing-phone');
    if (!input) return;

    input.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\\D/g, '').substring(0, 10);

        let formatted = '';
        if (value.length > 0) formatted = '(' + value.substring(0, 3);
        if (value.length >= 4) formatted += ') ' + value.substring(3, 6);
        if (value.length >= 7) formatted += '-' + value.substring(6, 10);

        input.value = formatted;
        input.setAttribute('value', formatted); // Set attribute too
    });
});
</script>
EOD;

        return $block_content . $script;
    }

    return $block_content;
}

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


add_action('pre_user_query', 'custom_user_query_search_full_name_fix');

function custom_user_query_search_full_name_fix($query) {
    global $wpdb;
    
    // Only modify search queries
    if (empty($query->query_vars['search'])) {
        return;
    }
    
    // Get the search term and clean it
    $search_term = trim($query->query_vars['search'], '*');
    
    if (empty($search_term)) {
        return;
    }
    
    // Split search terms by spaces
    $search_terms = preg_split('/\s+/', $search_term);
    
    // Build meta query conditions
    $meta_conditions = [];
    
    foreach ($search_terms as $term) {
        if (empty($term)) continue;
        
        $like = '%' . $wpdb->esc_like($term) . '%';
        
        $meta_conditions[] = "(
            EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um_first
                WHERE um_first.user_id = {$wpdb->users}.ID
                AND um_first.meta_key = 'first_name'
                AND um_first.meta_value LIKE %s
            )
            OR
            EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um_last
                WHERE um_last.user_id = {$wpdb->users}.ID
                AND um_last.meta_key = 'last_name'
                AND um_last.meta_value LIKE %s
            )
        )";
    }
    
    if (!empty($meta_conditions)) {
        // Prepare the query with proper escaping
        $prepare_args = [];
        foreach ($search_terms as $term) {
            if (empty($term)) continue;
            $like = '%' . $wpdb->esc_like($term) . '%';
            $prepare_args[] = $like; // for first_name
            $prepare_args[] = $like; // for last_name
        }
        
        $custom_where = ' AND (' . implode(' OR ', $meta_conditions) . ')';
        $prepared_where = $wpdb->prepare($custom_where, $prepare_args);
        
        $query->query_where .= $prepared_where;
    }
}

// Alternative approach using query_fields and query_from for better performance
function custom_user_query_search_full_name_fix_v2($query) {
    global $wpdb;
    
    if (empty($query->query_vars['search'])) {
        return;
    }
    
    $search_term = trim($query->query_vars['search'], '*');
    
    if (empty($search_term)) {
        return;
    }
    
    // Add joins for meta fields
    $query->query_from .= " LEFT JOIN {$wpdb->usermeta} um_first ON ({$wpdb->users}.ID = um_first.user_id AND um_first.meta_key = 'first_name')";
    $query->query_from .= " LEFT JOIN {$wpdb->usermeta} um_last ON ({$wpdb->users}.ID = um_last.user_id AND um_last.meta_key = 'last_name')";
    
    // Split search terms
    $search_terms = preg_split('/\s+/', $search_term);
    $conditions = [];
    $prepare_args = [];
    
    foreach ($search_terms as $term) {
        if (empty($term)) continue;
        
        $like = '%' . $wpdb->esc_like($term) . '%';
        $conditions[] = "(um_first.meta_value LIKE %s OR um_last.meta_value LIKE %s)";
        $prepare_args[] = $like;
        $prepare_args[] = $like;
    }
    
    if (!empty($conditions)) {
        $where_clause = ' AND (' . implode(' OR ', $conditions) . ')';
        $prepared_where = $wpdb->prepare($where_clause, $prepare_args);
        $query->query_where .= $prepared_where;
        
        // Add GROUP BY to avoid duplicates
        $query->query_fields = "DISTINCT " . $query->query_fields;
    }
}
