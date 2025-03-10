<?php 

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}



add_action('woocommerce_order_item_meta_end', function ($item_id, $item, $order) {
    // Retrieve saved order item meta
    $full_name    = wc_get_order_item_meta($item_id, 'full_name', true);
    $company_name = wc_get_order_item_meta($item_id, '_recipient_company_name', true);
    $address_1    = wc_get_order_item_meta($item_id, '_recipient_address_1', true);
    $address_2    = wc_get_order_item_meta($item_id, '_recipient_address_2', true);
    $city         = wc_get_order_item_meta($item_id, '_recipient_city', true);
    $state        = wc_get_order_item_meta($item_id, '_recipient_state', true);
    $zipcode      = wc_get_order_item_meta($item_id, '_recipient_zipcode', true);

    if (!empty($full_name)) {
        echo '<p><strong>Recipient Name:</strong> ' . esc_html($full_name) . '</p>';
    }
    if (!empty($company_name)) {
        echo '<p><strong>Company:</strong> ' . esc_html($company_name) . '</p>';
    }
    if (!empty($address_1)) {
        echo '<p><strong>Address 1:</strong> ' . esc_html($address_1) . '</p>';
    }
    if (!empty($address_2)) {
        echo '<p><strong>Address 2:</strong> ' . esc_html($address_2) . '</p>';
    }
    if (!empty($city)) {
        echo '<p><strong>City:</strong> ' . esc_html($city) . '</p>';
    }
    if (!empty($state)) {
        echo '<p><strong>State:</strong> ' . esc_html($state) . '</p>';
    }
    if (!empty($zipcode)) {
        echo '<p><strong>Zipcode:</strong> ' . esc_html($zipcode) . '</p>';
    }
}, 10, 3);



add_action('woocommerce_before_calculate_totals', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (isset($cart_item['new_price'])) {
            $cart_item['data']->set_price(floatval($cart_item['new_price']));
        }
        if (isset($cart_item['new_price'])) {
            $cart_item['data']->set_price($cart_item['new_price']);
        }
    }
});

// Save custom data in cart session
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id, $variation_id) {
    if (isset($cart_item_data['new_price'])) {
        $cart_item_data['unique_key'] = uniqid('custom_', true);
    }
    if (isset($cart_item_data['single_order'])) {
        $cart_item_data['single_order'] =  $_POST['single_order'];
    }
    return $cart_item_data;
}, 10, 3);


// Retrieve custom data from session
add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values, $key) {
    if (isset($values['new_price'])) {
        $cart_item['new_price'] = $values['new_price'];
    }
    
    if (isset($values['full_name'])) {
        $cart_item['full_name'] = $values['full_name'];
    }
    return $cart_item;
}, 10, 3);

function display_cart_item_custom_meta($item_data, $cart_item) {
    
    if (!empty($cart_item['full_name'])) {
        $item_data[] = [
            'name'  => __('Recipient Name', 'woocommerce'),
            'value' => sanitize_text_field($cart_item['full_name']),
        ];
    }
    if (!empty($cart_item['company_name'])) {
        $item_data[] = [
            'name'  => __('Company Name', 'woocommerce'),
            'value' => sanitize_text_field($cart_item['company_name']),
        ];
    }
    if (!empty($cart_item['address_1'])) {
        $item_data[] = [
            'name'  => __('Address 1', 'woocommerce'),
            'value' => sanitize_text_field($cart_item['address_1']),
        ];
    }
    if (!empty($cart_item['address_2'])) {
        $item_data[] = [
            'name'  => __('Address 2', 'woocommerce'),
            'value' => sanitize_text_field($cart_item['address_2']),
        ];
    }
    if (!empty($cart_item['city'])) {
        $item_data[] = [
            'name'  => __('City', 'woocommerce'),
            'value' => sanitize_text_field($cart_item['city']),
        ];
    }
    if (!empty($cart_item['state'])) {
        $item_data[] = [
            'name'  => __('State', 'woocommerce'),
            'value' => sanitize_text_field($cart_item['state']),
        ];
    }
    if (!empty($cart_item['zipcode'])) {
        $item_data[] = [
            'name'  => __('Zip Code', 'woocommerce'),
            'value' => sanitize_text_field($cart_item['zipcode']),
        ];
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'display_cart_item_custom_meta', 10, 2);





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
    if (isset($values['single_order'])) {
        $item->add_meta_data('Single Order', $values['single_order'], true);
    }

    if (isset($values['full_name'])) {
        $item->add_meta_data('full_name', $values['full_name'], true);
    }
    if (isset($values['company_name'])) {
        $item->add_meta_data('_recipient_company_name', $values['company_name'], true);
    }
    if (isset($values['address_1'])) {
        $item->add_meta_data('_recipient_address_1', $values['address_1'], true);
    }
    if (isset($values['address_2'])) {
        $item->add_meta_data('_recipient_address_2', $values['address_2'], true);
    }
    if (isset($values['city'])) {
        $item->add_meta_data('_recipient_city', $values['city'], true);
    }
    if (isset($values['state'])) {
        $item->add_meta_data('_recipient_state', $values['state'], true);
    }
    if (isset($values['zipcode'])) {
        $item->add_meta_data('_recipient_zipcode', $values['zipcode'], true);
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
function create_sub_orders($main_order_id) {
    $main_order = wc_get_order($main_order_id);

    if (!$main_order) {
        return;
    }

    $customer_id  = $main_order->get_customer_id(); // Get the main order's user ID
    $billing_data = $main_order->get_address('billing'); // Get billing details
    $customer_email = $billing_data['email']; // Extract email
    $billing_data = [
        'first_name' => $main_order->get_billing_first_name(),
        'last_name'  => $main_order->get_billing_last_name(),
        'company'    => $main_order->get_billing_company(),
        'address_1'  => $main_order->get_billing_address_1(),
        'address_2'  => $main_order->get_billing_address_2(),
        'city'       => $main_order->get_billing_city(),
        'state'      => $main_order->get_billing_state(),
        'postcode'   => $main_order->get_billing_postcode(),
        'country'    => $main_order->get_billing_country(),
        'email'      => $main_order->get_billing_email(),
        'phone'      => $main_order->get_billing_phone(),
    ];
    
    foreach ($main_order->get_items() as $item_id => $item) {
        // Create a new sub-order
        // $sub_order = '';
        $sub_order = wc_create_order();

        // Set the same user as the main order (if applicable)
        if ($customer_id) {
            $sub_order->set_customer_id($customer_id);
        }

        // Get custom "Full Name" from the cart item (HPOS-friendly)
        $custom_full_name = $item->get_meta('full_name', true);

    
        // Copy billing details from main order and set Full Name
        $sub_order->set_address($billing_data, 'billing');

        // Ensure sub-order uses the same email as the main order
        $sub_order->set_billing_email($customer_email);

        // Add product to sub-order with a modified price
        $product_id = $item->get_product_id();
        $quantity   = $item->get_quantity();
        
        $original_price = $item->get_total() / $quantity; // Get the price per unit

        // Custom price (Example: 10% discount)
        $custom_price = 0; // Apply a 10% discount

        // Add product with a modified price
        $product = wc_get_product($product_id);
        if ($product) {
            $order_item = new WC_Order_Item_Product();
            $order_item->set_product($product);
            $order_item->set_quantity($quantity);
            $order_item->set_subtotal($custom_price * $quantity);
            $order_item->set_total($custom_price * $quantity);
            $order_item->set_order_id($sub_order->get_id());
            $sub_order->add_item($order_item);
        }

        $recipient_address_1 = wc_get_order_item_meta($item_id, '_recipient_address_1', true);
        $recipient_address_2 = wc_get_order_item_meta($item_id, '_recipient_address_2', true);
        $city = wc_get_order_item_meta($item_id, '_recipient_city', true);
        $state = wc_get_order_item_meta($item_id, '_recipient_state', true);
        $zipcode = wc_get_order_item_meta($item_id, '_recipient_zipcode', true);
        // Get custom shipping details (HPOS-friendly)
        // $custom_address = $item->get_meta('address', true);
        // if (!empty($custom_address)) {
            $shipping_data = [
                'first_name' => !empty($custom_full_name) ? $custom_full_name : $billing_data['first_name'],
                'last_name'  => '', // Keep last name empty
                'address_1'  => $recipient_address_1 ?? '',
                'address_2'  => $recipient_address_2 ?? '',
                'city'       => $city ?? '',
                'state'      => $state ?? '',
                'postcode'   => $zipcode ?? '',
                'country'    => 'US', // Adjust dynamically if needed
            ];
        
            // Ensure minimum required fields exist before setting the shipping address
            // if (!empty($shipping_data['_recipient_address_1']) && !empty($shipping_data['_recipient_city'])) {
                $sub_order->set_address($shipping_data, 'shipping');
            // }
        // }
        
        echo "<pre>";
        print_r($sub_order->get_address('shipping'));
        echo "</pre>";


        // Ensure the sub-order has a shipping method to save the shipping data
        $sub_order->set_shipping_total(0); 

        // Set sub-order as a child of the main order
        $sub_order->set_parent_id($main_order_id);

        // Calculate total for sub-order
        $sub_order->calculate_totals();

        // Save the sub-order
        $sub_order->save();
    }
}

add_action('woocommerce_thankyou', 'process_order_suborders', 10, 1);

function process_order_suborders($order_id) {
    if (!$order_id) {
        return;
    }

    $main_order = wc_get_order($order_id);

    if (!$main_order) {
        error_log("Order not found: " . $order_id);
        return;
    }

    // Debugging: Log order ID
    error_log("Processing order ID: " . $order_id);

    $order_items = $main_order->get_items();

    foreach ($order_items as $item_id => $item) {
        $new_price = $item->get_meta('New Price', true);
        $single_order = $item->get_meta('Single Order', true);
        $unique_key = $item->get_meta('Unique Key', true);

        // Log values
        error_log("Item ID: $item_id | New Price: $new_price | Single Order: $single_order | Unique Key: $unique_key");
    }

    // Call your sub-order creation function if needed
    // create_sub_orders($order_id);
}




// add_action('init', 'get_woocommerce_cart');
// function get_woocommerce_cart() {
//     if (class_exists('WooCommerce') && WC()->cart) {
//         WC()->cart->calculate_totals(); // Ensure totals are updated
//         echo "<pre>";
//         print_r(print_r(WC()->cart->get_cart(), true)); // Debugging: Log cart contents
//         echo "</pre>";
//     }
// }
