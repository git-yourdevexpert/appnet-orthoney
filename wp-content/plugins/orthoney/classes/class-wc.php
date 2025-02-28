<?php 
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
        $affiliates = OAM_Custom::getAffiliateList($affiliate);

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
        echo '<p><strong>Affiliate 00:</strong> ' . esc_html(OAM_Custom::getAffiliateList($affiliate)) . '</p>';
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
