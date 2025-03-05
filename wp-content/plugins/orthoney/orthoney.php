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

// Include necessary files
require_once OH_PLUGIN_DIR_PATH . 'includes/database.php';

require_once OH_PLUGIN_DIR_PATH . 'classes/class-wc.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-hooks.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-custom.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-user-role.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-login-registration.php';




// Affiliate
require_once OH_PLUGIN_DIR_PATH . 'classes/affiliate/class-affiliate.php';

// Customer
require_once OH_PLUGIN_DIR_PATH . 'classes/customer/class-customer.php';

// Register activation hook
register_activation_hook(__FILE__, 'orthoney_create_custom_tables');

// Refresh database if requested
if(isset($_GET['database_refresh']) && $_GET['database_refresh'] == 'okay' ){
    add_action('init', 'orthoney_create_custom_tables');
}




/**
 * affiliate team code
 */

// 1️⃣ Register the Endpoint
function add_affiliate_overview_endpoint() {
    add_rewrite_endpoint('affiliate-overview', EP_ROOT | EP_PAGES);
}
add_action('init', 'add_affiliate_overview_endpoint');

// 2️⃣ Add the Menu Item in WooCommerce My Account
function add_affiliate_overview_menu_item($items) {
    $items['affiliate-overview'] = __('Affiliate Overview', 'woocommerce');
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_affiliate_overview_menu_item');

// 3️⃣ Display Affiliate Overview Content
function display_affiliate_overview_content() {
    $user_id = get_current_user_id();
    $affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);

    echo "<h2>Affiliate Overview</h2>";

    if (!$affiliate_id) {
        echo "<p>You are not assigned to any affiliate.</p>";
        return;
    }
    
    $details = get_affiliate_details($affiliate_id);


    $affiliate = get_userdata($affiliate_id);

    echo "<h3>Affiliate: {$affiliate->display_name}</h3>";
    echo "<p><strong>Total Earnings:</strong> $" . $details['total_earnings'] . "</p>";
    
    echo "<p><strong>Paid:</strong> $" . (isset($details['paid']) ? $details['paid'] : 0)  . "</p>";
    echo "<p><strong>Refunds:</strong> $" . (isset($details['refunds']) ? $details['refunds'] : 0) . "</p>";
    echo "<p><strong>Active Balance:</strong> $" . $details['active_balance'] . "</p>";

    echo "<h4>Order List</h4>";
    echo "<table><thead><tr><th>Order ID</th><th>Date</th></tr></thead><tbody>";

    if(!empty($details['orders'])){
        foreach ($details['orders'] as $order) {
            echo "<tr><td>#{$order->order_id}</td><td>{$order->date_created_gmt}</td></tr>";
        }
    }
    echo "</tbody></table>";
}
add_action('woocommerce_account_affiliate-overview_endpoint', 'display_affiliate_overview_content');

// 4️⃣ Fetch Affiliate Data
function get_affiliate_details($affiliate_id) {
    global $wpdb;

    // Get order item IDs linked to this affiliate
    $order_items = $wpdb->get_results($wpdb->prepare("
        SELECT order_item_id 
        FROM {$wpdb->prefix}woocommerce_order_itemmeta 
        WHERE meta_key = '_yith_wcaf_commission_id' 
        AND meta_value = %d
    ", $affiliate_id));

    if (empty($order_items)) {
        return ['error' => 'No orders found for this affiliate.'];
    }

    $order_item_ids = wp_list_pluck($order_items, 'order_item_id');
    $order_item_ids_placeholder = implode(',', array_fill(0, count($order_item_ids), '%d'));

    // Get total earnings
    $total_earnings = $wpdb->get_var($wpdb->prepare("
        SELECT SUM(meta_value) 
        FROM {$wpdb->prefix}woocommerce_order_itemmeta 
        WHERE meta_key = '_yith_wcaf_commission_amount' 
        AND order_item_id IN ($order_item_ids_placeholder)
    ", ...$order_item_ids));

    // Calculate Active Balance (Total Earnings - Paid - Refunds)
    $active_balance = ($total_earnings ?: 0);

    // Get related orders from HPOS
    $orders = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT o.id AS order_id, o.date_created_gmt 
        FROM {$wpdb->prefix}wc_orders o
        JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.id = oi.order_id
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE oim.meta_key = '_yith_wcaf_commission_id'
        AND oim.meta_value = %d
    ", $affiliate_id));

    return [
        'total_earnings'  => wc_price($total_earnings ?: 0),
        'active_balance'  => wc_price($active_balance ?: 0),
        'orders'          => $orders,
    ];
}
