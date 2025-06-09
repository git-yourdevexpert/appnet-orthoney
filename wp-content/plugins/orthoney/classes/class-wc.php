<?php 

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Dompdf\Dompdf;
use Dompdf\Options;


class OAM_WC_Customizer {
    /**
     * Constructor to hook into WooCommerce template loading.
     */
    public function __construct() {        
        
        add_action('woocommerce_before_calculate_totals', array($this, 'woocommerce_before_calculate_totals_handler'));
        add_filter('woocommerce_add_cart_item_data', array($this, 'woocommerce_add_cart_item_data_handler'), 10,3);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'woocommerce_get_cart_item_from_session_handler'), 10,3);
        add_filter('woocommerce_get_item_data', array($this, 'woocommerce_get_item_data_handler'), 10, 2);
        add_action('template_redirect', array($this,'handle_custom_order_form_submission'));
        add_action('woocommerce_checkout_create_order_line_item', array($this,'save_affiliate_info_on_order_item'), 10, 4);

        // add_filter('woocommerce_order_again_cart_item_data' , array($this,'woocommerce_order_again_cart_item_data_handler'), 10, 3);
        add_filter('render_block' , array($this,'checkout_order_summary_render_block_handler'), 10, 2);

        add_filter( 'woocommerce_package_rates', array($this,'conditional_shipping_based_on_acf_date'), 10, 2 );

        // add_filter('woocommerce_email_enabled_new_order', array($this,'disable_wc_order_mail'), 10, 2);
        // add_filter('woocommerce_email_enabled_customer_processing_order', array($this,'disable_wc_order_mail'), 10, 2);
        // add_filter('woocommerce_email_enabled_cancelled_order', array($this,'disable_wc_order_mail'), 10, 2);
        // add_filter('woocommerce_email_enabled_failed_order', array($this,'disable_wc_order_mail'), 10, 2);
        // add_filter('woocommerce_email_enabled_customer_failed_order', array($this,'disable_wc_order_mail'), 10, 2);
        // add_filter('woocommerce_email_enabled_customer_on_hold_order', array($this,'disable_wc_order_mail'), 10, 2);
        // add_filter('woocommerce_email_enabled_customer_completed_order', array($this,'disable_wc_order_mail'), 10, 2);
        // add_filter('woocommerce_email_enabled_customer_refunded_order', array($this,'disable_wc_order_mail'), 10, 2);


        // WC thank you page template changes hooks
        add_filter( 'woocommerce_thankyou_order_received_text', array($this, 'custom_thankyou_order_received_text'), 10, 2 );
        add_action('woocommerce_thankyou', array($this, 'add_labels_to_order_overview'), 20);
        add_filter('woocommerce_email_attachments', array($this, 'attach_pdf_order_email'), 10, 3);

    }

    public function attach_pdf_order_email($attachments, $email_id, $order) {
        if ($email_id === 'customer_processing_order') { // Target "Processing Order" email
            $upload_dir = wp_upload_dir();
            
            $pdf_path = $upload_dir['basedir'] . '/order-' . $order->get_id() . '.pdf';
                
            // Generate PDF of the order email
            if (method_exists($this, 'generate_pdf_from_order_email')) {
                $this->generate_pdf_from_order_email($order, $pdf_path);
            } else {
                error_log('Method generate_pdf_from_order_email does not exist.');
            }
    
            // Attach PDF
            if (file_exists($pdf_path)) {
                $attachments[] = $pdf_path;
            }
        }
    
        return $attachments;
    }
    
    public function generate_pdf_from_order_email($order, $pdf_path) {
        if (!class_exists('\Dompdf\Dompdf')) {
            require_once plugin_dir_path(__FILE__) . 'libs/dompdf/vendor/autoload.php';
        }

        if (!class_exists('Dompdf\Dompdf')) {
            error_log('Dompdf not found.');
            return;
        }

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        ob_start();

        global $wpdb;
        $order_id = intval($order->get_order_number());
        $sub_order_id = OAM_COMMON_Custom::get_order_meta($order->get_order_number(), '_orthoney_OrderID');
        $order_process_table = OAM_Helper::$order_process_table;
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
        $recipient_order_table = $wpdb->prefix . 'oh_recipient_order';
        $taxable_donation = get_field('ort_taxable_donation', 'option');

        $result = $wpdb->get_row($wpdb->prepare("SELECT data FROM {$order_process_table} WHERE order_id = %d", $order_id));
        $json_data = $result->data ?? '';
        $decoded_data = json_decode($json_data, true);
        $affiliate = !empty($decoded_data['affiliate_select']) ? $decoded_data['affiliate_select'] : 'Orthoney';

        $token = $wpdb->get_var($wpdb->prepare("SELECT token FROM {$yith_wcaf_affiliates_table} WHERE ID = %d", $affiliate));
        $recipientResult = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$recipient_order_table} WHERE order_id = %d", $sub_order_id));

        $total_quantity = 0;
        $total_price_before_discount = 0;
        foreach ($order->get_items() as $item) {
            $quantity = $item->get_quantity();
            $line_subtotal = $item->get_meta('_line_subtotal', true);
            if (!$line_subtotal) $line_subtotal = $item->get_subtotal();
            $total_price_before_discount += floatval($line_subtotal);
            $total_quantity += $quantity;
        }
        $per_jar_price = $total_quantity > 0 ? round($total_price_before_discount / $total_quantity, 2) : 0;

        $shipping_total = $order->get_shipping_total();
        $shipping_methods = [];
        foreach ($order->get_shipping_methods() as $shipping_item) {
            $shipping_methods[] = $shipping_item->get_name(); // e.g. "Flat rate"
        }
        $shipping_method_name = implode(', ', $shipping_methods);

        $payment_method = $order->get_payment_method_title();
        $total = $order->get_total();
        $organization = 'Orthoney';
        $organization_data = 'Honey From The Heart';

        if (!empty($recipientResult[0]->affiliate_token) && $recipientResult[0]->affiliate_token !== 'Orthoney') {
            $token = $recipientResult[0]->affiliate_token;
            $meta_key = '_yith_wcaf_name_of_your_organization';
            $organization = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->usermeta} um JOIN {$wpdb->prefix}yith_wcaf_affiliates aff ON um.user_id = aff.user_id WHERE aff.token = %s AND um.meta_key = %s", $token, $meta_key));

            if ($organization != 'Orthoney') {
                $organization_data_query = $wpdb->get_row($wpdb->prepare("SELECT aff.*, MAX(CASE WHEN um.meta_key = '_yith_wcaf_city' THEN um.meta_value END) AS _yith_wcaf_city, MAX(CASE WHEN um.meta_key = '_yith_wcaf_state' THEN um.meta_value END) AS _yith_wcaf_state, MAX(CASE WHEN um.meta_key = 'billing_city' THEN um.meta_value END) AS billing_city, MAX(CASE WHEN um.meta_key = 'billing_state' THEN um.meta_value END) AS billing_state, MAX(CASE WHEN um.meta_key = 'shipping_city' THEN um.meta_value END) AS shipping_city, MAX(CASE WHEN um.meta_key = 'shipping_state' THEN um.meta_value END) AS shipping_state FROM {$wpdb->prefix}yith_wcaf_affiliates AS aff LEFT JOIN {$wpdb->usermeta} AS um ON um.user_id = aff.user_id WHERE aff.token = %s GROUP BY aff.user_id", $token));

                $city = $organization_data_query->_yith_wcaf_city ?: $organization_data_query->billing_city ?: $organization_data_query->shipping_city;
                $state = $organization_data_query->_yith_wcaf_state ?: $organization_data_query->billing_state ?: $organization_data_query->shipping_state;
                $organization_data = implode(', ', array_filter(['[' . $token . ']', $organization, $city, $state]));
            }
        }

        // Get coupon names
        $coupon_names = [];
        foreach ($order->get_coupon_codes() as $code) {
            $coupon_names[] = $code;
        }
        $coupon_display = !empty($coupon_names) ? implode(', ', $coupon_names) : 'None';

        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>body,html{padding:0; margin:0;}body { font-family: DejaVu Sans, sans-serif; background-color: #f7f7f7; color: #515151; font-size:12px;} .email-container { width: 600px; background: #ffffff; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; } h1 { color: #333; text-align: center; } .order-summary { width: 100%; border-collapse: collapse; margin-top: 20px; } .order-summary th, .order-summary td { padding: 10px; border: 1px solid #ddd; text-align: left; } .order-summary th { background: #eee; } .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }</style></head><body>';
        echo '<div class="pdf-heading" style="background-color: #491571; text-align:left; color: #ffffff;"><h1 style="color:white;padding: 36px 48px;">Thank you for your order</h1></div>';
        echo '<div class="email-container">';
        echo '<p>Thank you for your gift of $' . $total . ' to ' . $organization_data . '. Your Honey From The Heart gift benefits ' . $organization_data . ', a non-profit organization, and ORT America, a 501(c)(3) organization. For federal income tax purposes, your charitable deduction is limited to the purchase price of the honey less its fair market value. For purposes of determining the value of goods provided, you should use $' . $taxable_donation . ' per jar so your charitable contribution is $' . number_format(($order->get_subtotal()) - (count($recipientResult) * $taxable_donation), 2) . '.</p>';
        echo '<p>Your order has been received and is now being processed. Your order details are shown below for your reference:</p>';
        echo '<h2 style="color:#491571;">Order #' . $sub_order_id . '</h2>';

        echo '<table class="order-summary" style="width:100%; border: 1px solid black;"><thead><tr><th style="width:50%; border: 1px solid black;">Recipient</th><th style="width:25%; border: 1px solid black;">Quantity</th><th style="width:25%; border: 1px solid black;">Price</th></tr></thead><tbody>';
        foreach ($recipientResult as $data) {
            $addressParts = array_filter([$data->address_1, $data->address_2, $data->city, $data->state, $data->zipcode, $order->get_billing_country()]);
            $price = $per_jar_price * $data->quantity;
            echo '<tr><td style="border:1px solid black;">' . esc_html($data->full_name) . '<br><small>' . esc_html($data->company_name) . '<br>' . esc_html(implode(' ', $addressParts)) . '<br>' . esc_html($data->greeting) . '</small></td><td style="border:1px solid black; text-align:center;">' . esc_html($data->quantity) . '</td><td style="border:1px solid black;">$' . number_format($price, 2) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<table class="order-summary" style="width:100%; border: 1px solid black;"><tbody>';
        echo '<tr><td>Subtotal:</td><td>' . wc_price($order->get_subtotal()) . '</td></tr>';
        echo ($shipping_total == 0)? "" : '<tr><td>Shipping:</td><td>' . wc_price($shipping_total) . ' (' . $shipping_method_name . ')</td></tr>';
        echo ($coupon_display == '') ? "" :'<tr><td>Coupon(s) Used:</td><td>' . esc_html($coupon_display) . '</td></tr>';
        echo '<tr><td>Payment method:</td><td>' . $payment_method . '</td></tr>';
        echo '<tr><td>Total:</td><td>' . wc_price($total) . '</td></tr>';
        echo '</tbody></table>';

        if ($token && $token !== 'Orthoney') {
            echo '<p>Distributor Code: ' . $token . '</p>';
        }

        echo '<h3 style="color:#491571;">Billing Address</h3><p>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '<br>' . $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . '<br>' . $order->get_billing_city() . ' ' . $order->get_billing_state() . ', ' . $order->get_billing_country() . ' ' . $order->get_billing_postcode() . '<br>' . $order->get_billing_phone() . '<br><a href="mailto:' . $order->get_billing_email() . '">' . $order->get_billing_email() . '</a></p>';

        echo '</div></body></html>';

        $html = ob_get_clean();
        if (empty($html)) {
            error_log('Empty PDF content.');
            return;
        }

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        try {
            $dompdf->render();
            file_put_contents($pdf_path, $dompdf->output());
            error_log('PDF generated successfully: ' . $pdf_path);
        } catch (Exception $e) {
            error_log('Dompdf Error: ' . $e->getMessage());
        }
    }
  

    /**
     * Disable Email for Sub Order 
     */

    public function disable_wc_order_mail($enabled, $order) {
        if (is_a($order, 'WC_Order')) {
            // Check if the order has a parent order
            if ($order->get_parent_id() != 0) {
                return false;
            }
        }
        return $enabled;
    }

    /**
     *  conditional based shippig option apply at cart and checkout page 
     */
    public function conditional_shipping_based_on_acf_date( $rates, $package ) {
        // Get ACF start and end dates
        $start_date    = get_field( 'free_shipping_start_date', 'option' );
        $end_date      = get_field( 'free_shipping_end_date', 'option' );
        $current_date  = current_time( 'Y-m-d H:i:s' );

        if ( ! $start_date || ! $end_date ) {
            return $rates;
        }

        $is_within_range = ( $current_date >= $start_date && $current_date <= $end_date );

        $preferred_method = '';

        foreach ( $rates as $rate_key => $rate ) {
            // Inside free shipping period: allow only free_shipping
            if ( $is_within_range ) {
                if ( $rate->method_id !== 'free_shipping' ) {
                    unset( $rates[ $rate_key ] );
                } else {
                    $preferred_method = $rate_key;
                }
            } else {
                // Outside free shipping period: allow all methods, but prefer flat_rate
                if ( $rate->method_id === 'flat_rate' && empty( $preferred_method ) ) {
                    $preferred_method = $rate_key;
                }
            }
        }

        // Set preferred/default method
        if ( $preferred_method && isset( $rates[ $preferred_method ] ) ) {
            // Move preferred method to the top of the array to auto-select it
            $preferred = $rates[ $preferred_method ];
            unset( $rates[ $preferred_method ] );
            $rates = array_merge( [ $preferred_method => $preferred ], $rates );
        }

        return $rates;
    }

    

    public function woocommerce_before_calculate_totals_handler($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
    
        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['custom_data']['new_price'])) {
                $cart_item['data']->set_price(floatval($cart_item['custom_data']['new_price']));
            }
           
        }
    }

    /**
     * Save custom data in cart session
     */
    public function woocommerce_add_cart_item_data_handler($cart_item_data, $product_id, $variation_id) {
       
        
        if (isset($cart_item['custom_data']['single_order'])) {
            $cart_item_data['single_order'] = $cart_item['custom_data']['single_order'];
        }
        if (isset($cart_item['custom_data']['process_id'])) {
            $cart_item_data['process_id'] = intval($cart_item['custom_data']['process_id']);
        }
        if (isset($cart_item['custom_data']['greeting'])) {
            $cart_item_data['greeting'] = intval($cart_item['custom_data']['greeting']);
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
        if (isset($values['process_id'])) {
            $cart_item['process_id'] = $values['process_id'];
        }
        if (isset($values['greeting'])) {
            $cart_item['greeting'] = $values['greeting'];
        }
    
        return $cart_item;
    }

    public function woocommerce_get_item_data_handler($item_data, $cart_item) {
        
        if (!empty($cart_item['custom_data']['process_id'])) {
            $item_data[] = [
                'name'  => __('Process ID', 'woocommerce'),
                'value' => sanitize_text_field($cart_item['custom_data']['process_id']),
            ];
        }
        if (!empty($cart_item['custom_data']['single_order'])) {
            $value = $cart_item['custom_data']['single_order'] === 0 ? 'Multi Address' : 'Single Address';
            $item_data[] = [
                'name'  => __('Order Type', 'woocommerce'),
                'value' => sanitize_text_field($value),
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
        if (isset($values['custom_data']['greeting'])) {
            $item->add_meta_data('greeting', $values['custom_data']['greeting'], true);
        }
    
        if (isset($values['custom_data']['process_id'])) {
            $item->add_meta_data('process_id', $values['custom_data']['process_id'], true);
        }
    
    }
    /**
     * Reordering Functionality Hooks
     */
    // public function woocommerce_order_again_cart_item_data_handler ($cart_item_data, $item, $order) {
        
    //     if (!empty($item->get_meta_data())) {
    //         foreach ($item->get_meta_data() as $meta) {         
    //             $cart_item_data[str_replace('_recipient_', '', $meta->key)] = $meta->value;
    //         }
    //     }
        
    //     return $cart_item_data;
    // }

    public function checkout_order_summary_render_block_handler($block_content, $block) {

         if ((is_checkout() || isset($block['blockName'])) && $block['blockName'] === 'woocommerce/checkout-actions-block' ) {

            $custom_content = '';
            if (isset($_COOKIE['yith_wcaf_referral_token'])) {
                $yith_wcaf_referral_token = $_COOKIE['yith_wcaf_referral_token'];
            
                if($yith_wcaf_referral_token != 'Orthoney'){
                    
                    global $wpdb;
                    $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;
                    
                    // Correct query execution
                    $user_id = $wpdb->get_var($wpdb->prepare("
                        SELECT user_id FROM {$yith_wcaf_affiliates_table} WHERE token = %s AND user_id != 0
                    ", $yith_wcaf_referral_token));
                    
                   $activate_affiliate_account = get_user_meta($user_id, 'activate_affiliate_account', true)?:0;

                    $states = WC()->countries->get_states('US');
                    $state = get_user_meta($user_id, '_yith_wcaf_state', true);

                    if (empty($state)) {
                        $state = get_user_meta($user_id, 'billing_state', true) ?: get_user_meta($user_id, 'shipping_state', true);
                    }

                    $city = get_user_meta($user_id, '_yith_wcaf_city', true);
                    if (empty($city)) {
                        $city = get_user_meta($user_id, 'billing_city', true) ?: get_user_meta($user_id, 'shipping_city', true);
                    }

                    $orgName = get_user_meta($user_id, '_orgName', true);
                    if (empty($orgName)) {
                        $orgName = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
                    }

                    $state_name = isset($states[$state]) ? $states[$state] : $state;
                    $value = '[' . $yith_wcaf_referral_token . '] ' . $orgName ?:$data->display_name;
                    if (!empty($city)) {
                        $value .= ', ' . $city;
                    }
                    if (!empty($state)) {
                        $value .= ', ' . $state_name;
                    }
                        
                    if ((int)$activate_affiliate_account !== 1) {
                        $custom_content = '<div class="organization-not-active-error-message">The <strong>'.  $value.' </strong> organization is not active at the moment. The profit commission from this order will be allocated to Honey From The Heart.</div>';
                    }
                    
                }
            }
            return $block_content . $custom_content;
         }

        if (!is_checkout() || !isset($block['blockName']) || $block['blockName'] !== 'woocommerce/checkout-order-summary-cart-items-block') {
            return $block_content;
        }
    
        if (!WC()->session) {
            return $block_content;
        }
    
        $cart = WC()->session->get('cart', []);
        $total_quantity = 0;
        $table_content = '';
        $status = false;
        $recipients = [];

    
        foreach ($cart as $cart_item) {
      
            $quantity = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;
            $total_quantity += $quantity;
    
            $custom_data = $cart_item['custom_data'] ?? [];
            $is_single_order = $custom_data['single_order'] ?? 0;
            $per_jar_cost = $custom_data['new_price'] ?? 0;
            $pid = $custom_data['process_id'] ?? 0;
    
            if ($is_single_order == 0) {
                $status = true;
             
                $recipients = OAM_Helper::get_recipient_by_pid($pid);
    
                foreach ($recipients as $recipient) {
                    $address = implode(', ', array_filter([
                        $recipient->address_1,
                        $recipient->address_2,
                        $recipient->city,
                        $recipient->state,
                        $recipient->zipcode
                    ]));
    
                    $table_content .= sprintf(
                        '<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
                        esc_html($recipient->full_name ?? '-'),
                        esc_html($recipient->company_name ?? '-'),
                        esc_html($address),
                        esc_html($recipient->quantity ?? '0')
                    );
                }
            } elseif ($is_single_order === 1) {
                $shipping_address = [
                    WC()->customer->get_shipping_address_1(),
                    WC()->customer->get_shipping_address_2(),
                    WC()->customer->get_shipping_city(),
                    WC()->customer->get_shipping_state(),
                    WC()->customer->get_shipping_postcode(),
                    WC()->customer->get_shipping_country(),
                ];
    
                $shipping_string = implode(', ', array_filter($shipping_address));
    
                $custom_content = '<div class="viewAllRecipientsPopupCheckoutContent">
                    <div class="item"><strong>Total Honey Jar(s):</strong> ' . esc_html($quantity) . '</div>
                    <div class="item"><strong>Shipping Address:</strong> ' . esc_html($shipping_string) . '</div>
                </div>';
    
                return $block_content . $custom_content;
            }
        }
    
        if ($status) {

            $custom_content = '<div class="viewAllRecipientsPopupCheckoutContent">
                <div class="item"><strong>Total Honey Jar(s):</strong> ' . esc_html($total_quantity) . '</div>
                <div class="item"><strong>Total Recipient(s):</strong> ' . esc_html(count($recipients)) . '</div>
                  <div class="item"><strong>Price per Jar:</strong> ' . (wc_price($per_jar_cost)) . '</div>
                <div class="item"><a href="#viewAllRecipientsPopupCheckout" class="viewAllRecipientsPopupCheckout btn-underline" data-lity>View All Recipients Details</a></div>
    
                <div id="viewAllRecipientsPopupCheckout" class="lity-popup-normal lity-hide">
                    <div class="popup-show order-process-block orthoney-datatable-warraper">
                        <h3>All Recipients Details</h3>
                        <div class="table-wrapper table-with-search-block">
                            <table>
                                <thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th></tr></thead>
                                <tbody>' . $table_content . '</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>';
    
            return $block_content . $custom_content;
        }
    
        return $block_content;
    }
    

    /* 
    * WC thank you page template changes hooks
    */ 
    public function custom_thankyou_order_received_text( $text, $order ) {
        // Customize the thank you text
        return "Thanks for your order! <br> You'll receive another email when each recipient's honey is shipped.";
    }

    public function add_labels_to_order_overview($order_id) {
        if (!$order_id) return;
    
        $order = wc_get_order($order_id);
    
        if (!$order) return;
        $custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
        $yith_wcaf_referral = OAM_COMMON_Custom::get_order_meta($order_id, '_yith_wcaf_referral');

        if (!empty($yith_wcaf_referral)) {
            $yith_wcaf_referral_token = $yith_wcaf_referral;
        
            if($yith_wcaf_referral_token != 'Orthoney'){
                
                global $wpdb;
                $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;
                
                // Correct query execution
                $user_id = $wpdb->get_var($wpdb->prepare("
                    SELECT user_id FROM {$yith_wcaf_affiliates_table} WHERE token = %s
                ", $yith_wcaf_referral_token));

                $activate_affiliate_account = get_user_meta($user_id, 'activate_affiliate_account', true);
    
                if (empty($activate_affiliate_account) AND $activate_affiliate_account != 1) {
                    $order->update_meta_data('affiliate_account_status', 0);
                }else{
                    $order->update_meta_data('affiliate_account_status', 1);
                }
                $order->save();
                
            }
        }

        ?>
        <div class="order-process-wrapp">
            <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
    
                <li class="woocommerce-order-overview__order order">
                    <label><?php esc_html_e( 'Order number:', 'woocommerce' ); ?></label>
                    <strong><?php echo $custom_order_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                </li>
    
                <li class="woocommerce-order-overview__date date">
                    <label><?php esc_html_e( 'Date:', 'woocommerce' ); ?></label>
                    <strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                </li>
    
                <?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
                    <li class="woocommerce-order-overview__email email">
                        <label><?php esc_html_e( 'Email:', 'woocommerce' ); ?></label>
                        <strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                    </li>
                <?php endif; ?>
    
                <li class="woocommerce-order-overview__total total">
                    <label><?php esc_html_e( 'Total:', 'woocommerce' ); ?></label>
                    <strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                </li>
    
                <?php if ( $order->get_payment_method_title() ) : ?>
                    <li class="woocommerce-order-overview__payment-method method">
                        <label><?php esc_html_e( 'Payment method:', 'woocommerce' ); ?></label>
                        <strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
                    </li>
                <?php endif; ?>
    
            </ul>
        </div>
        <?php 

        $order_details_url = esc_url(CUSTOMER_DASHBOARD_LINK . "order-details/". ($order->get_order_number()));


        echo '<div class="view-order-btn"><a href="' . esc_url($order_details_url) . '" class="button">View Order Details</a></div>';?>
        <?php
    }
}

new OAM_WC_Customizer();


// add_action('woocommerce_thankyou', 'process_order_suborders', 10, 1);

// function process_order_suborders($order_id) {
//     if (!$order_id) {
//         return;
//     }
//     $group_id = 0;

//     global $wpdb;
    
//     $order_process_table = OAM_Helper::$order_process_table;
//     $group_table = OAM_Helper::$group_table;
//     $group_recipient_table = OAM_Helper::$group_recipient_table;

   
//     $main_order = wc_get_order($order_id);

//     $order_items = $main_order->get_items();
//     $order_items_count = count($order_items);

//     $single_order  = '';
//     foreach ($order_items as $item_id => $item) {
//         $single_order = '';
//         $process_id = '';

//         if($item->get_meta('single_order', true)){
//             $single_order = $item->get_meta('single_order', true);
//         }
//         if($item->get_meta('process_id', true)){
//             $process_id = $item->get_meta('process_id', true);
//         }

//         if($item->get_meta('_recipient_order_type', true)){
//             $single_order = $item->get_meta('_recipient_order_type', true);
//         }
//         if($item->get_meta('_recipient_process_id', true)){
//             $process_id = $item->get_meta('_recipient_process_id', true);
//         }
//         if($item->get_meta('_recipient_recipient_id', true)){
//            $recipient_id = $item->get_meta('_recipient_recipient_id', true);
//         }
        
//     }
    
//     $processQuery = $wpdb->prepare(
//         "SELECT * FROM {$order_process_table} WHERE id = %d" ,
//         $process_id
//     );
    
//     $processData = $wpdb->get_row($processQuery);

//     if($processData){
//         $recipientQuery = $wpdb->prepare(
//             "SELECT id FROM {$group_table} WHERE order_id = %d AND pid = %d" ,
//             $order_id, $process_id
//         );
        
//         $recipients = $wpdb->get_row($recipientQuery);

//         if(!$recipients){
//             $insert_data = [
//                 'user_id'  => $processData->user_id,
//                 'pid'      => $processData->id,
//                 'order_id' => $order_id,
//                 'name'     => sanitize_text_field($processData->name),
//             ];
//             $wpdb->insert($group_table, $insert_data);
//             $group_id = $wpdb->insert_id;
//         }else{
//             $group_id = $recipients->id;
//         }
//     }

//     //TODO
//     $groupRecipientQuery = $wpdb->prepare(
//         "SELECT COUNT(id) FROM $group_recipient_table WHERE group_id = %d",
//         $group_id
//     );

//     $groupRecipientData = $wpdb->get_var($groupRecipientQuery);

//     //TODO
    
//     $order_type = 'multi-recipient-order';

//     if($single_order == 1){
//         $order_type = 'single-order';
//     }

//     $updateData = [
//         'order_type'  => $order_type,
//         'order_id'  => $order_id,
//     ];

//     if($process_id != ''){
//         $wpdb->update(
//             $order_process_table,
//             $updateData,
//             ['id' => $process_id]
//         );
//     }
//     if($single_order == 'multi-recipient-order'){
        
//         if($groupRecipientData != $order_items_count){
//             // echo "<div id='thank-you-sub-orders-creation' data-order_id='".$order_id."' data-group_id='".$group_id."'></div>";
//         }
//     }
// }