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

// Register activation hook
register_activation_hook(__FILE__, 'orthoney_create_custom_tables');

// Refresh database if requested
if(isset($_GET['database_refresh']) && $_GET['database_refresh'] == 'okay' ){
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


add_filter('render_block', function ($block_content, $block) {
    if (!is_checkout()) {
        return $block_content;
    }

    // Target the WooCommerce Checkout Order Summary Cart Items Block
    if (isset($block['blockName']) && $block['blockName'] === 'woocommerce/checkout-order-summary-cart-items-block') {
        
        // Ensure WooCommerce session is available
        if (!WC()->session) {
            return $block_content;
        }

        $cart = WC()->session->get('cart', []);
        $status = false;
        $total_quantity = 0;
        $table_content = "";

        foreach ($cart as $cart_item) {
            // Ensure 'quantity' exists before adding
            if (isset($cart_item['quantity'])) {
                $total_quantity += (int) $cart_item['quantity']; // Cast to integer to avoid unexpected issues
            }

            if (isset($cart_item['order_type']) && $cart_item['order_type'] === 'multi-recipient-order') {
                $status = true;
                $table_content .= '<tr>';
                $table_content .= '<td>' . (isset($cart_item['full_name']) ? esc_html($cart_item['full_name']) : '-') . '</td>';
                $table_content .= '<td>' . (isset($cart_item['company_name']) ? esc_html($cart_item['company_name']) : '-') . '</td>';
                $table_content .= '<td>' . (isset($cart_item['address']) ? esc_html($cart_item['address']) : '-') . '</td>';
                $table_content .= '<td>' . (isset($cart_item['quantity']) ? esc_html($cart_item['quantity']) : '0') . '</td>';
                $table_content .= '</tr>';
            }
        }

        if ($status) {
            $custom_content = '<div class="viewAllRecipientsPopupCheckoutContent"><div class="item"><strong>Total Honey Jars:</strong> ' . esc_html($total_quantity) . '</div>';
            $custom_content .= '<div class="item"><strong>Total Recipients:</strong> ' . esc_html(count($cart)) . '</div>';
            $custom_content .= '<div class="item"><a href="#viewAllRecipientsPopupCheckout" class="viewAllRecipientsPopupCheckout btn-underline" data-lity>View all Recipients Details</a></div>';
            // Popup Content
            $custom_content .= '<div id="viewAllRecipientsPopupCheckout" class="lity-popup-normal lity-hide">
                <div class="popup-show order-process-block">
                    <h4>All Recipients Details</h4>
                    <table>
                        <thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th></tr></thead>
                        <tbody>' . $table_content . '</tbody>
                    </table>
                </div>
            </div></div>';

            return $block_content . $custom_content;
        }
    }

    return $block_content;
}, 10, 2);






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


// function create_sub_orders_ajax() {
//     global $wpdb;
    
//     $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
//     $group_recipient_table = OAM_Helper::$group_recipient_table;

//     // Get AJAX request parameters
//     $main_order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
//     $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
//     $current_chunk = isset($_POST['current_chunk']) ? intval($_POST['current_chunk']) : 0;
//     $pid = isset($_POST['pid']) ? sanitize_text_field($_POST['pid']) : null;

//     if (!$main_order_id) {
//         wp_send_json_error(['message' => 'Invalid Order ID']);
//     }

//     $main_order = wc_get_order($main_order_id);
//     if (!$main_order) {
//         wp_send_json_error(['message' => 'Main order not found']);
//     }

//     $customer_id = $main_order->get_customer_id();
//     $billing_data = [
//         'first_name' => $main_order->get_billing_first_name(),
//         'last_name'  => $main_order->get_billing_last_name(),
//         'company'    => $main_order->get_billing_company(),
//         'address_1'  => $main_order->get_billing_address_1(),
//         'address_2'  => $main_order->get_billing_address_2(),
//         'city'       => $main_order->get_billing_city(),
//         'state'      => $main_order->get_billing_state(),
//         'postcode'   => $main_order->get_billing_postcode(),
//         'country'    => $main_order->get_billing_country(),
//         'email'      => $main_order->get_billing_email(),
//         'phone'      => $main_order->get_billing_phone(),
//     ];

//     // Get all order items
//     $order_items = $main_order->get_items();
//     $total_rows = count($order_items);
//     $chunk_size = 2; // Process 2 items per chunk
//     $chunks = array_chunk($order_items, $chunk_size, true);

//     if (!isset($chunks[$current_chunk])) {
//         wp_send_json_success([
//             'finished' => true,
//             'progress' => 100,
//             'message' => 'All sub-orders created successfully!',
//         ]);
//     }

//     // Process the current chunk
//     foreach ($chunks[$current_chunk] as $item_id => $item) {
//         $recipient_id = $item->get_meta('_recipient_recipient_id', true);

//         $recipientQuery = $wpdb->prepare(
//             "SELECT * FROM {$order_process_recipient_table} WHERE id = %d",
//             $recipient_id
//         );

//         $recipients = $wpdb->get_row($recipientQuery);

//         if ($recipients && $recipients->order_id == 0) {
//             $sub_order = wc_create_order();

//             if ($customer_id) {
//                 $sub_order->set_customer_id($customer_id);
//             }

//             $custom_full_name = $item->get_meta('full_name', true);
//             $sub_order->set_address($billing_data, 'billing');
//             $sub_order->set_billing_email($billing_data['email']);

//             $product_id = $item->get_product_id();
//             $quantity   = $item->get_quantity();

//             $product = wc_get_product($product_id);
//             if ($product) {
//                 $order_item = new WC_Order_Item_Product();
//                 $order_item->set_product($product);
//                 $order_item->set_quantity($quantity);
//                 $order_item->set_subtotal(0);
//                 $order_item->set_total(0);
//                 $order_item->set_order_id($sub_order->get_id());
//                 $sub_order->add_item($order_item);
//             }

//             $shipping_data = [
//                 'first_name' => $custom_full_name ?: $billing_data['first_name'],
//                 'last_name'  => '',
//                 'address_1'  => $item->get_meta('_recipient_address_1', true) ?? '',
//                 'address_2'  => $item->get_meta('_recipient_address_2', true) ?? '',
//                 'city'       => $item->get_meta('_recipient_city', true) ?? '',
//                 'state'      => $item->get_meta('_recipient_state', true) ?? '',
//                 'postcode'   => $item->get_meta('_recipient_zipcode', true) ?? '',
//                 'country'    => 'US',
//             ];

//             $sub_order->set_address($shipping_data, 'shipping');
//             $sub_order->set_shipping_total(0);
//             $sub_order->set_parent_id($main_order_id);
//             $sub_order->calculate_totals();
//             $sub_order->set_status('processing');
//             $sub_order->save();

//             $wpdb->update(
//                 $order_process_recipient_table,
//                 ['order_id' => $sub_order->get_id()],
//                 ['id' => $recipient_id]
//             );

//             $wpdb->insert($group_recipient_table, [
//                 'user_id'       => $recipients->user_id ?? 0,
//                 'recipient_id'  => $recipient_id,
//                 'group_id'      => $group_id,
//                 'order_id'      => $sub_order->get_id(),
//                 'full_name'     => sanitize_text_field($custom_full_name),
//                 'company_name'  => sanitize_text_field($recipients->company_name),
//                 'address_1'     => sanitize_text_field($recipients->address_1),
//                 'address_2'     => sanitize_text_field($recipients->address_2),
//                 'city'          => sanitize_text_field($recipients->city),
//                 'state'         => sanitize_text_field($recipients->state),
//                 'zipcode'       => sanitize_text_field($recipients->zipcode),
//                 'quantity'      => sanitize_text_field($recipients->quantity),
//                 'verified'      => sanitize_text_field($recipients->address_verified),
//                 'greeting'      => sanitize_text_field($recipients->greeting),
//             ]);
//         }
//     }

//     // Calculate progress percentage
//     $progress = round((($current_chunk + 1) / count($chunks)) * 100, 2);
    
//     wp_send_json_success([
//         'finished' => false,
//         'progress' => $progress,
//         'next_chunk' => $current_chunk + 1,
//         'total_rows' => $total_rows,
//     ]);
// }

// add_action('wp_ajax_orthoney_thank-you-sub-orders-creation_ajax', 'create_sub_orders_ajax');
// add_action('wp_ajax_nopriv_orthoney_thank-you-sub-orders-creation_ajax', 'create_sub_orders_ajax');


add_filter( 'woocommerce_order_query_args', function( $query_args ) {
    $query_args['parent_order_id'] = 0;
    return $query_args;
});

// function custom_woocommerce_myaccount_template($template, $template_name, $template_path) {
//     // Check if it's the My Account dashboard template
//     if ($template_name === 'myaccount/dashboard.php') {
//         // Define the new custom template path
//         $new_template = WP_PLUGIN_DIR . '/orthoney/templates/customer/customer-dashboard/dashboard.php';
        
//         // Check if the custom template file exists
//         if (file_exists($new_template)) {
//             return $new_template;
//         }
//     }
    
//     return $template;
// }
// add_filter('woocommerce_locate_template', 'custom_woocommerce_myaccount_template', 10, 3);
