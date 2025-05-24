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

add_action('wp_ajax_get_filtered_customers', 'orthoney_get_filtered_customers');

function orthoney_get_filtered_customers() {
    // check_ajax_referer('get_customers_nonce', 'nonce');

    global $wpdb;

    $user_id = get_current_user_id();
    $select_customer = get_user_meta($user_id, 'select_customer', true);
    $choose_customer = get_user_meta($user_id, 'choose_customer', true);

    $start  = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $draw   = intval($_POST['draw'] ?? 1);
    $search = sanitize_text_field($_POST['search']['value'] ?? '');

    // Prepare "include" filter if needed
    $include_clause = '';
    $include_ids = [];
    if ($select_customer === 'choose_customer' && !empty($choose_customer)) {
        $choose_customer_int = array_map('intval', (array)$choose_customer);
        if (!empty($choose_customer_int)) {
            $include_ids = $choose_customer_int;
            $include_clause = 'AND u.ID IN (' . implode(',', $include_ids) . ')';
        }
    }

    // Clean search for SQL LIKE
    $like_search = '%' . $wpdb->esc_like($search) . '%';

    /*
     * Query logic:
     * 1) Join wp_users with wp_usermeta for first_name, last_name, and role
     * 2) Filter role = 'customer' ONLY (exactly)
     * 3) Search in first_name OR last_name OR user_email
     * 4) Paginate with LIMIT $start, $length
     */

    // Prepare SQL for counting total 'customer' users with possible inclusion filter (no search)
    $sql_total = "
        SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID AND um_role.meta_key = '{$wpdb->prefix}capabilities'
        WHERE um_role.meta_value = 'a:1:{s:8:\"customer\";b:1;}'
        {$include_clause}
    ";

    // Total count without search
    $total_count = $wpdb->get_var($sql_total);

    // Prepare SQL for filtered count with search
    $sql_filtered = "
        SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID AND um_role.meta_key = '{$wpdb->prefix}capabilities'
        LEFT JOIN {$wpdb->usermeta} um_first ON (um_first.user_id = u.ID AND um_first.meta_key = 'first_name')
        LEFT JOIN {$wpdb->usermeta} um_last ON (um_last.user_id = u.ID AND um_last.meta_key = 'last_name')
        WHERE um_role.meta_value = 'a:1:{s:8:\"customer\";b:1;}'
        AND (
            u.user_email LIKE %s
            OR um_first.meta_value LIKE %s
            OR um_last.meta_value LIKE %s
        )
        {$include_clause}
    ";

    $filtered_count = $wpdb->get_var($wpdb->prepare($sql_filtered, $like_search, $like_search, $like_search));

    // Prepare SQL to get user data with limit and search
    $sql_data = "
        SELECT u.ID, u.user_email, um_first.meta_value AS first_name, um_last.meta_value AS last_name
        FROM {$wpdb->users} u
        INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID AND um_role.meta_key = '{$wpdb->prefix}capabilities'
        LEFT JOIN {$wpdb->usermeta} um_first ON (um_first.user_id = u.ID AND um_first.meta_key = 'first_name')
        LEFT JOIN {$wpdb->usermeta} um_last ON (um_last.user_id = u.ID AND um_last.meta_key = 'last_name')
        WHERE um_role.meta_value = 'a:1:{s:8:\"customer\";b:1;}'
        AND (
            u.user_email LIKE %s
            OR um_first.meta_value LIKE %s
            OR um_last.meta_value LIKE %s
        )
        {$include_clause}
        ORDER BY um_first.meta_value ASC, um_last.meta_value ASC
        LIMIT %d, %d
    ";

    $results = $wpdb->get_results($wpdb->prepare(
        $sql_data,
        $like_search,
        $like_search,
        $like_search,
        $start,
        $length
    ));

    // Prepare data for DataTables
    $data = [];
    foreach ($results as $user) {
        $nonce = wp_create_nonce('switch_to_user_' . $user->ID);
        $name = trim($user->first_name . ' ' . $user->last_name);

        $data[] = [
            'name' => esc_html($name ?: '—'),
            'email' => esc_html($user->user_email),
            'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '" data-nonce="' . esc_attr($nonce) . '">
                            <img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login as Customer
                        </button>',
        ];
    }

    wp_send_json([
        'draw' => $draw,
        'recordsTotal' => intval($total_count),
        'recordsFiltered' => intval($filtered_count),
        'data' => $data,
    ]);
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
