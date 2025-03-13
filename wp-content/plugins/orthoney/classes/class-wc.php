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
        if (isset($cart_item['custom_data']['new_price'])) {
            $cart_item['data']->set_price(floatval($cart_item['custom_data']['new_price']));
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
    
    if (isset($cart_item['custom_data']['single_order'])) {
        $cart_item_data['single_order'] = $cart_item['custom_data']['single_order'];
    }
    if (isset($cart_item['custom_data']['process_id'])) {
        $cart_item_data['process_id'] = floatval($cart_item['custom_data']['process_id']);
    }

    if (!empty($cart_item['recipient_id'])) {
        $cart_item_data['recipient_id'] = sanitize_text_field($_POST['recipient_id']);
    }
    if (!empty($cart_item['order_type'])) {
        $cart_item_data['order_type'] = sanitize_text_field($_POST['order_type']);
    }
    if (!empty($cart_item['process_id'])) {
        $cart_item_data['process_id'] = sanitize_text_field($_POST['process_id']);
    }
    
    return $cart_item_data;
}, 10, 3);


// Retrieve custom data from session
add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values, $key) {
    if (isset($values['new_price'])) {
        $cart_item['new_price'] = $values['new_price'];
    }
    if (isset($values['single_order'])) {
        $cart_item['single_order'] = $values['single_order'];
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
        if (isset($_POST['order_type'])) {
            if ($order_type !== 'single-order') {
                wp_redirect(wc_get_checkout_url());
                exit;
            }

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

    if (isset($values['custom_data']['single_order'])) {
        $item->add_meta_data('single_order', $values['custom_data']['single_order'], true);
    }

    if (isset($values['custom_data']['process_id'])) {
        $item->add_meta_data('process_id', $values['custom_data']['process_id'], true);
    }

    if (isset($values['full_name'])) {
        $item->add_meta_data('full_name', $values['full_name'], true);
    }
    if (isset($values['order_type'])) {
        $item->add_meta_data('_recipient_order_type', $values['order_type'], true);
    }
    if (isset($values['recipient_id'])) {
        $item->add_meta_data('_recipient_recipient_id', $values['recipient_id'], true);
    }
    if (isset($values['process_id'])) {
        $item->add_meta_data('_recipient_process_id', $values['process_id'], true);
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

add_action('woocommerce_thankyou', 'process_order_suborders', 10, 1);

function process_order_suborders($order_id) {
    if (!$order_id) {
        return;
    }
    $group_id = 0;

    global $wpdb;
    
    $order_process_table = OAM_Helper::$order_process_table;
    $group_table = OAM_Helper::$group_table;
    $group_recipient_table = OAM_Helper::$group_recipient_table;

   
    $main_order = wc_get_order($order_id);

    $order_items = $main_order->get_items();
    $order_items_count = count($order_items);

    $single_order  = '';
    foreach ($order_items as $item_id => $item) {
        $single_order = '';
        $process_id = '';

        if($item->get_meta('single_order', true)){
            $single_order = $item->get_meta('single_order', true);
        }
        if($item->get_meta('process_id', true)){
            $process_id = $item->get_meta('process_id', true);
        }

        if($item->get_meta('_recipient_order_type', true)){
            $single_order = $item->get_meta('_recipient_order_type', true);
        }
        if($item->get_meta('_recipient_process_id', true)){
            $process_id = $item->get_meta('_recipient_process_id', true);
        }
        if($item->get_meta('_recipient_recipient_id', true)){
           $recipient_id = $item->get_meta('_recipient_recipient_id', true);
        }
        
    }
    
    $processQuery = $wpdb->prepare(
        "SELECT * FROM {$order_process_table} WHERE id = %d" ,
        $process_id
    );
    
    $processData = $wpdb->get_row($processQuery);

    if($processData){
        $recipientQuery = $wpdb->prepare(
            "SELECT id FROM {$group_table} WHERE order_id = %d AND pid = %d" ,
            $order_id, $process_id
        );
        
        $recipients = $wpdb->get_row($recipientQuery);

        if(!$recipients){
            $insert_data = [
                'user_id'  => $processData->user_id,
                'pid'      => $processData->id,
                'order_id' => $order_id,
                'name'     => sanitize_text_field($processData->name),
            ];
            $wpdb->insert($group_table, $insert_data);
            $group_id = $wpdb->insert_id;
        }else{
            $group_id = $recipients->id;
        }
    }

    //TODO
    $groupRecipientQuery = $wpdb->prepare(
        "SELECT COUNT(id) FROM $group_recipient_table WHERE group_id = %d",
        $group_id
    );

    $groupRecipientData = $wpdb->get_var($groupRecipientQuery);

    //TODO
    
    $order_type = 'multi-recipient-order';

    if($single_order == 1){
        $order_type = 'single-order';
    }

    $updateData = [
        'order_type'  => $order_type,
        'order_id'  => $order_id,
    ];

    if($process_id != ''){
        $wpdb->update(
            $order_process_table,
            $updateData,
            ['id' => $process_id]
        );
    }
    if($single_order == 'multi-recipient-order'){
        
        if($groupRecipientData != $order_items_count){
            echo "<div id='thank-you-sub-orders-creation' data-order_id='".$order_id."' data-group_id='".$group_id."'></div>";
        }
        // create_sub_orders($order_id, $group_id);
    }
}


function create_sub_orders($main_order_id, $group_id = 0) {
    global $wpdb;
    $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
    $group_recipient_table = OAM_Helper::$group_recipient_table;

    $main_order = wc_get_order($main_order_id);

    if (!$main_order) {
        return;
    }

    $customer_id  = $main_order->get_customer_id();
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
        $recipient_id = $item->get_meta('_recipient_recipient_id', true);

        $recipientQuery = $wpdb->prepare(
            "SELECT * FROM {$order_process_recipient_table} WHERE id = %d",
            $recipient_id
        );

        $recipients = $wpdb->get_row($recipientQuery);

        if ($recipients && $recipients->order_id == 0) {
            $sub_order = wc_create_order();

            if ($customer_id) {
                $sub_order->set_customer_id($customer_id);
            }

            $custom_full_name = $item->get_meta('full_name', true);
            $sub_order->set_address($billing_data, 'billing');
            $sub_order->set_billing_email($billing_data['email']);

            $product_id = $item->get_product_id();
            $quantity   = $item->get_quantity();

            $product = wc_get_product($product_id);
            if ($product) {
                $order_item = new WC_Order_Item_Product();
                $order_item->set_product($product);
                $order_item->set_quantity($quantity);
                $order_item->set_subtotal(0);
                $order_item->set_total(0);
                $order_item->set_order_id($sub_order->get_id());
                $sub_order->add_item($order_item);
            }

            $shipping_data = [
                'first_name' => $custom_full_name ?: $billing_data['first_name'],
                'last_name'  => '',
                'address_1'  => $item->get_meta('_recipient_address_1', true) ?? '',
                'address_2'  => $item->get_meta('_recipient_address_2', true) ?? '',
                'city'       => $item->get_meta('_recipient_city', true) ?? '',
                'state'      => $item->get_meta('_recipient_state', true) ?? '',
                'postcode'   => $item->get_meta('_recipient_zipcode', true) ?? '',
                'country'    => 'US',
            ];

            $sub_order->set_address($shipping_data, 'shipping');
            $sub_order->set_shipping_total(0);
            $sub_order->set_parent_id($main_order_id);
            $sub_order->calculate_totals();
            $sub_order->set_status('processing');
            $sub_order->save();

            $wpdb->update(
                $order_process_recipient_table,
                ['order_id' => $sub_order->get_id()],
                ['id' => $recipient_id]
            );

            $wpdb->insert($group_recipient_table, [
                'user_id'       => $recipients->user_id ?? 0,
                'group_id'      => $group_id,
                'order_id'      => $sub_order->get_id(),
                'full_name'     => sanitize_text_field($custom_full_name),
                'company_name'  => sanitize_text_field($recipients->company_name),
                'address_1'     => sanitize_text_field($recipients->address_1),
                'address_2'     => sanitize_text_field($recipients->address_2),
                'city'          => sanitize_text_field($recipients->city),
                'state'         => sanitize_text_field($recipients->state),
                'zipcode'       => sanitize_text_field($recipients->zipcode),
                'quantity'      => sanitize_text_field($recipients->quantity),
                'verified'      => sanitize_text_field($recipients->address_verified),
                'greeting'      => sanitize_text_field($recipients->greeting),
            ]);
        }
    }
}

// Reordering Functionality Hooks
add_filter('woocommerce_order_again_cart_item_data', function ($cart_item_data, $item, $order) {
   
    if (!empty($item->get_meta_data())) {
        foreach ($item->get_meta_data() as $meta) {         
            $cart_item_data[str_replace('_recipient_', '', $meta->key)] = $meta->value;
        }
    }

    return $cart_item_data;
}, 10, 3);