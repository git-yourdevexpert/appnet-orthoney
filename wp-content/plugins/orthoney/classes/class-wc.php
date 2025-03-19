<?php 

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
class OAM_WC_Customizer {
    /**
     * Constructor to hook into WooCommerce template loading.
     */
    public function __construct() {        
        add_action('woocommerce_order_item_meta_end', array($this, 'woocommerce_order_item_meta_end_handler'), 10, 3);
        add_action('woocommerce_before_calculate_totals', array($this, 'woocommerce_before_calculate_totals_handler'));
        add_filter('woocommerce_add_cart_item_data', array($this, 'woocommerce_add_cart_item_data_handler'), 10,3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'woocommerce_get_cart_item_from_session_handler'), 10,3);
        add_filter('woocommerce_get_item_data', array($this, 'woocommerce_get_item_data_handler'), 10, 2);
        add_action('template_redirect', array($this,'handle_custom_order_form_submission'));
        add_action('woocommerce_checkout_create_order_line_item', array($this,'save_affiliate_info_on_order_item'), 10, 4);

        add_filter('woocommerce_order_again_cart_item_data' , array($this,'woocommerce_order_again_cart_item_data_handler'), 10, 3);

    }
    
    public function woocommerce_order_item_meta_end_handler($item_id, $item, $order) {
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
    }

    public function woocommerce_before_calculate_totals_handler($cart) {
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
    }

    /**
     * Save custom data in cart session
     */
    public function woocommerce_add_cart_item_data_handler($cart_item_data, $product_id, $variation_id) {
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
    }

    /**
     * Retrieve custom data from session
     */
    public function woocommerce_get_cart_item_from_session_handler($cart_item, $values, $key){
        if (isset($values['new_price'])) {
            $cart_item['new_price'] = $values['new_price'];
        }
        if (isset($values['single_order'])) {
            $cart_item['single_order'] = $values['single_order'];
        }
    
        return $cart_item;
    }

    public function woocommerce_get_item_data_handler($item_data, $cart_item) {
    
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

    public function handle_custom_order_form_submission() {
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

    public function save_affiliate_info_on_order_item($item, $cart_item_key, $values, $order) {

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
    /**
     * Reordering Functionality Hooks
     */
    public function woocommerce_order_again_cart_item_data_handler ($cart_item_data, $item, $order) {
   
        if (!empty($item->get_meta_data())) {
            foreach ($item->get_meta_data() as $meta) {         
                $cart_item_data[str_replace('_recipient_', '', $meta->key)] = $meta->value;
            }
        }
    
        return $cart_item_data;
    }
}

new OAM_WC_Customizer();




// add_action('woocommerce_thankyou', 'process_order_suborders', 10, 1);

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
            // echo "<div id='thank-you-sub-orders-creation' data-order_id='".$order_id."' data-group_id='".$group_id."'></div>";
        }
    }
}