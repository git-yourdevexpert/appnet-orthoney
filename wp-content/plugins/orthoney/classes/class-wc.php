<?php 

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure WooCommerce Uses the New Price Start
function apply_custom_price_in_cart($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['custom_data']['new_price'])) {
            $cart_item['data']->set_price(floatval($cart_item['custom_data']['new_price']));
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'apply_custom_price_in_cart');


// Ensure WooCommerce Uses the New Price END





add_action('template_redirect', 'handle_custom_order_form_submission');

function handle_custom_order_form_submission() {
    if (isset($_POST['product_id']) && isset($_POST['quantity']) && isset($_POST['order_type'])) {
        $product_id = absint($_POST['product_id']);
        $quantity = absint($_POST['quantity']);
        $order_type = sanitize_text_field($_POST['order_type']);
        
        // Only process if the order type is 'single-order'
        if ($order_type === 'single-order') {
            // Add the product to the WooCommerce cart
            WC()->cart->add_to_cart($product_id, $quantity);

            // Save affiliate info to session
            if (isset($_POST['affiliate'])) {
                $affiliate = sanitize_text_field($_POST['affiliate']);
                WC()->session->set('affiliate', $affiliate);
            }

            // Optionally, you can store 'order_type' in session if needed
            WC()->session->set('order_type', $order_type);

            // Redirect to checkout page
            wp_redirect(wc_get_checkout_url());
            exit;
        }
    }
}


add_action('woocommerce_checkout_create_order_line_item', 'save_affiliate_info_on_order_item', 10, 4);

function save_affiliate_info_on_order_item($item, $cart_item_key, $values, $order) {
    // Check if affiliate information is available in the session
    $affiliate = WC()->session->get('affiliate');
    
    if ($affiliate) {
        // Retrieve the affiliate information
        $affiliates = OAM_Helper::getAffiliateList($affiliate);

        // Check if affiliate data was returned
        if (isset($affiliates[0]) && !empty($affiliates[0]->name)) {
            // Add affiliate information to the order item metadata (not shown on the frontend)
            $item->add_meta_data('Affiliate', $affiliates[0]->name, true);
        }
    }
}
add_action('woocommerce_thankyou', 'display_affiliate_on_thank_you_page', 10, 1);

function display_affiliate_on_thank_you_page($order_id) {
    $order = wc_get_order($order_id);

    // Get the affiliate info from the order meta
    $affiliate = $order->get_meta('Affiliate', true);


    if ($affiliate) {
        echo '<p><strong>Affiliate 00:</strong> ' . esc_html(OAM_Helper::getAffiliateList($affiliate)) . '</p>';
    }
}

add_action('woocommerce_admin_order_data_after_billing_address', 'display_affiliate_in_backend', 10, 1);

function display_affiliate_in_backend($order) {
    // Get the affiliate info from the order meta
    $affiliate = $order->get_meta('Affiliate', true);   
    
    if ($affiliate) {
        echo '<p><strong>' . __('Affiliate 00', 'text-domain') . ':</strong>' . esc_html($affiliate) . '</p>';
    }
}


// 

function add_items_to_cart($data, $product_id) {
    if (!function_exists('WC')) {
        die('WooCommerce is not loaded.');
    }

    // Ensure WooCommerce is active
    if (!class_exists('WC_Cart') || WC()->cart === null) {
        return;
    }

    // Empty the cart before adding new items
    WC()->cart->empty_cart();

    foreach ($data as $customer) {
        $full_name    = sanitize_text_field($customer['Full Name']);
        $company_name = sanitize_text_field($customer['Company Name']);
        $address      = sanitize_text_field($customer['Address']);
        $quantity     = (int) $customer['Quantity'];

        // Generate a unique key to prevent WooCommerce from merging items
        $unique_key = uniqid('custom_', true);

        WC()->cart->add_to_cart($product_id, $quantity, 0, [], [
            'full_name'    => $full_name,
            'company_name' => $company_name,
            'address'      => $address,
            'unique_key'   => $unique_key, // Ensures each item is treated separately
        ]);
    }
}

// Sample data
$data = [
    [
        "Full Name"    => "John Doe",
        "Company Name" => "Nimbus Solutions",
        "Address"      => "with new group, Los Angeles, CA, 90001",
        "Quantity"     => 2
    ],
    [
        "Full Name"    => "Jane Son",
        "Company Name" => "Vertex Industries",
        "Address"      => "101, Main St, New York, NY, 10001",
        "Quantity"     => 3
    ],
    [
        "Full Name"    => "Jane Son",
        "Company Name" => "BlueHorizon Tech",
        "Address"      => "101 Main St, New York, NY, 10001",
        "Quantity"     => 4
    ],
    [
        "Full Name"    => "Jane Son",
        "Company Name" => "BlueHorizon Tech",
        "Address"      => "101 Main St, New York, NY, 10001",
        "Quantity"     => 4
    ]
];

function rest() {
    $product_id = 76; // Set your WooCommerce product ID
    add_items_to_cart($GLOBALS['data'], $product_id);
}

// Ensure WooCommerce is fully loaded before running
// add_action('wp_loaded', 'rest');

// Display custom cart item meta in the cart page
function display_cart_item_custom_meta($item_data, $cart_item) {
    if (!empty($cart_item['full_name'])) {
        $item_data[] = [
            'name'  => 'Full Name',
            'value' => sanitize_text_field($cart_item['full_name']),
        ];
    }
    if (!empty($cart_item['company_name'])) {
        $item_data[] = [
            'name'  => 'Company Name',
            'value' => sanitize_text_field($cart_item['company_name']),
        ];
    }
    if (!empty($cart_item['address'])) {
        $item_data[] = [
            'name'  => 'Address',
            'value' => sanitize_text_field($cart_item['address']),
        ];
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_cart_item_custom_meta', 10, 2);

//
function create_sub_orders($main_order_id) {
    $main_order = wc_get_order($main_order_id);

    if (!$main_order) {
        return;
    }

    $customer_id  = $main_order->get_customer_id(); // Get the main order's user ID
    $billing_data = $main_order->get_address('billing'); // Get billing details
    $customer_email = $billing_data['email']; // Extract email

    foreach ($main_order->get_items() as $item_id => $item) {
        // Create a new sub-order
        $sub_order = wc_create_order();

        // Set the same user as the main order (if applicable)
        if ($customer_id) {
            $sub_order->set_customer_id($customer_id);
        }

        // Get custom "Full Name" from the cart item
        $custom_full_name = wc_get_order_item_meta($item_id, 'Full Name', true);

        // Use custom full name or fallback to main order's billing name
        $billing_data['first_name'] = !empty($custom_full_name) ? $custom_full_name : $billing_data['first_name'];
        $billing_data['last_name']  = ''; // Clear last name since "Full Name" is a single field

        // Copy billing details from main order and set Full Name
        $sub_order->set_address($billing_data, 'billing');

        // Ensure sub-order uses the same email as the main order
        $sub_order->set_billing_email($customer_email);

        // Add product to sub-order
        $product_id = $item->get_product_id();
        $quantity   = $item->get_quantity();
        $order_item = $sub_order->add_product(wc_get_product($product_id), $quantity);

        // Get custom "Address" from cart item meta for shipping
        $custom_address = wc_get_order_item_meta($item_id, 'Address', true);

        if (!empty($custom_address)) {
            // Convert custom address into WooCommerce shipping format
            $shipping_data = [
                'first_name' => $custom_full_name, // Use Full Name in Shipping Name
                'address_1'  => $custom_address,
                'address_2'  => '',
                'city'       => '',
                'state'      => '',
                'postcode'   => '',
                'country'    => '',
            ];

            // Set custom shipping address for the sub-order
            $sub_order->set_address($shipping_data, 'shipping');
        }

        // Set sub-order as a child of the main order
        $sub_order->set_parent_id($main_order_id);

        // Set order total to zero
        $sub_order->set_total(0);

        // Save the sub-order
        $sub_order->save();
    }
}

// Hook into WooCommerce order creation process
add_action('woocommerce_thankyou', 'process_order_suborders', 10, 1);

function process_order_suborders($order_id) {
    if (!$order_id) {
        return;
    }
    
    create_sub_orders($order_id);
}
