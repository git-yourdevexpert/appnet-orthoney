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
        
        // Hide sub order 
        // add_filter('woocommerce_order_query_args' , array($this,'only_show_main_order_handler'), 10, 2);

        add_action( 'add_meta_boxes', array($this, 'admin_sub_order_metabox') );

        add_filter( 'woocommerce_package_rates', array($this,'conditional_shipping_based_on_acf_date'), 10, 2 );

        add_filter('woocommerce_email_enabled_new_order', array($this,'disable_wc_order_mail'), 10, 2);
        add_filter('woocommerce_email_enabled_customer_processing_order', array($this,'disable_wc_order_mail'), 10, 2);
        add_filter('woocommerce_email_enabled_cancelled_order', array($this,'disable_wc_order_mail'), 10, 2);
        add_filter('woocommerce_email_enabled_failed_order', array($this,'disable_wc_order_mail'), 10, 2);
        add_filter('woocommerce_email_enabled_customer_failed_order', array($this,'disable_wc_order_mail'), 10, 2);
        add_filter('woocommerce_email_enabled_customer_on_hold_order', array($this,'disable_wc_order_mail'), 10, 2);
        add_filter('woocommerce_email_enabled_customer_completed_order', array($this,'disable_wc_order_mail'), 10, 2);
        add_filter('woocommerce_email_enabled_customer_refunded_order', array($this,'disable_wc_order_mail'), 10, 2);


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
        if (!class_exists('Dompdf\Dompdf')) {
            require_once plugin_dir_path(__DIR__) . 'vendor/autoload.php';
        }
    
        if (!class_exists('Dompdf\Dompdf')) {
            error_log('Dompdf not found.');
            return;
        }
    
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        ob_start();

        
        global $wpdb;
        $total_honey_jars = 0;
        $taxable_donation = get_field('ort_taxable_donation', 'option');
        $order_id = intval($order->get_order_number());
        $order_process_table = OAM_Helper::$order_process_table;
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
        
        // Fetch order data
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT data FROM {$order_process_table} WHERE order_id = %d",
            $order_id
        ));
        $json_data = $result->data;
        $decoded_data = json_decode($json_data, true);
        $affiliate = !empty($decoded_data['affiliate_select']) ? $decoded_data['affiliate_select'] : 'Orthoney';
        
        // Fetch the token
        $token = $wpdb->get_var($wpdb->prepare(
            "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE ID = %d",
            $affiliate
        ));

        $sub_order_id =  OAM_COMMON_Custom::get_order_meta($order->get_order_number(), '_orthoney_OrderID');
        $plugin_path = plugin_dir_path(__DIR__); // Get plugin root directory
        $file_path = $plugin_path . 'templates/woocommerce/emails/email-styles.php';
        $css = file_get_contents($file_path);

        foreach ($order->get_items() as $item_id => $item) { 
            $total_honey_jars += $item->get_quantity();
        }

        echo '<!DOCTYPE html><html>';
            echo '<head><meta charset="UTF-8">
                <style>
               
                    body { font-family: DejaVu Sans, sans-serif; background-color: #f7f7f7; color: #515151; margin: 0; padding: 0; }
                    .email-container { width: 600px; background: #ffffff; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                    h1 { color: #333; text-align: center; }
                    .order-summary { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    .order-summary th, .order-summary td { padding: 10px; border: 1px solid #ddd; text-align: left; }
                    .order-summary th { background: #eee; }
                    .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
                </style>
            </head>';
            echo '<body>';
                echo '<div class="pdf-heading" style="background-color: #491571; text-align:left; color: #ffffff;">';
                    echo '<h1 style="color:white;padding: 36px 48px;">Thank you for your order</h1>';
                echo '</div>';
                echo '<div class="email-container">';
                    echo '<p>Thank you for your gift of $'.$order->get_total().' to '.$order->get_billing_company().'. Your Honey From The Heart gift benefits '.$order->get_billing_company().', a non-profit organization, and ORT America, a 501(c)(3) organization. For federal income tax purposes, your charitable deduction is limited to the purchase price of the honey less its fair market value. For purposes of determining the value of goods provided, you should use $'.$taxable_donation.' per jar so your charitable contribution is '.'$'.number_format(($order->get_subtotal()) - ($total_honey_jars * $taxable_donation), 2).'.</p>';
                    echo '<p>Your order has been received and is now being processed. Your order details are shown below for your reference:</p>';
                    echo '<h2 style="color:#491571;">Order #'.$sub_order_id.'</h2>';
                    // Ensure the order items table has a fixed width
                    echo '<table class="order-summary" style="width:100%; border-collapse:collapse; border: 1px solid black;">';
                        echo '<thead>
                                <tr>
                                    <th style="width:50%; border: 1px solid black; color:black;">Recipient</th>
                                    <th style="width:25%; border: 1px solid black; color:black;">Quantity</th>
                                    <th style="width:25%; border: 1px solid black; color:black;">Price</th>
                                </tr>
                            </thead>';
                        echo '<tbody>';
                        echo '<tr>
                                <td style="border:1px solid black; padding: 10px;">
                                    <strong>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '</strong><br>
                                    ' . $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . '<br>
                                    ' . $order->get_billing_city() . ', ' . $order->get_billing_state() . ', ' . $order->get_billing_country() . '<br>
                                    ' . $order->get_billing_postcode() . '
                                </td>
                                <td style="border:1px solid black; text-align:center; padding: 10px;">' . $total_honey_jars . '</td>
                                <td style="border:1px solid black; padding: 10px;">' . wc_price($order->get_subtotal()) . '</td>
                            </tr>';
                        
                        echo '</tbody>';
                    echo '</table>';
                    echo '<table class="order-summary" style="width:100%; border-collapse:collapse; border: 1px solid black;">';
                        echo '<tbody>';
                            if ( @$totals = $order->get_order_item_totals() ) {
                                $totals['shipping']['value'] = "$".number_format((int) preg_replace('/\D/', '', @$totals['shipping']['value']), 2);
                                $i = 0;
                                foreach ( $totals as $total ) {
                                    $i++;
                                    ?><tr>
                                        <th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
                                        <td style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php if($i==2) { ?>$<?php echo number_format(WC()->cart->shipping_total, 2);}else { echo $total['value']; } ?></td>
                                    </tr><?php
                                }
                            }
                        
                        echo '</tbody>';
                    echo '</table>';
                    echo '<p>Distributor Code: ' . (($token != '') ? $token : $affiliate) . '</p>';
                    echo '<h3 style="color:#491571;">Billing Address</h3>';

                    echo '<p>' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() . '
                    <br>' . $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() . '
                    <br>' . $order->get_billing_city() . ' ' . $order->get_billing_state() . ', '. $order->get_billing_country() . $order->get_billing_postcode() .'
                    <br>' . $order->get_billing_phone() . '
                    <br><a href="mailto:' . $order->get_billing_email() . '">' . $order->get_billing_email() . '</a></p>';
                echo '</div>';
            echo '</body>';
        echo '</html>';
        
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
        $start_date = get_field('free_shipping_start_date', 'option');
        $end_date = get_field('free_shipping_end_date', 'option');
        $current_date = current_time('Y-m-d H:i:s');

        if ( !$start_date || !$end_date ) {
            return $rates;
        }

        $is_within_date_range = ($current_date >= $start_date && $current_date <= $end_date);

        foreach ( $rates as $rate_key => $rate ) {
            if ( $is_within_date_range ) {
                if ( $rate->method_id !== 'free_shipping' ) {
                    unset( $rates[$rate_key] );
                }
            } else {
                if ( $rate->method_id !== 'flat_rate' ) {
                    unset( $rates[$rate_key] );
                }
            }
        }

        return $rates;
    }

    public function admin_sub_order_metabox() {
        $screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id( 'shop-order' )
            : 'shop_order';
    
        add_meta_box(
            'recipient_sub_order',
            'Recipients Sub Order lists',
            array ($this, 'admin_sub_order_metabox_callback'),
            $screen,
            'normal',
            'high'
        );
    }
    
    // Metabox content
    public function admin_sub_order_metabox_callback($object, $item_id) {
        // Get the WC_Order object
        $order = is_a($object, 'WP_Post') ? wc_get_order($object->ID) : $object;
        
        // Check if order exists
        if (!$order) {
            echo '<p>Order not found.</p>';
            return;
        }
        
        // Get order items
        $items = $order->get_items();
        $order_status = wc_get_order_status_name($order->get_status()); // Get readable order status
        
        if (empty($items)) {
            echo '<p>No items found in this order.</p>';
            return;
        }

        global $wpdb;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
        ?>
    
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Full Name</th>
                    <th>Company Name</th>
                    <th>Address</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item_id => $item){

                    $recipient_id   = wc_get_order_item_meta($item_id, '_recipient_recipient_id', true);
                    $groupRecipientCount = $wpdb->get_row($wpdb->prepare(
                        "SELECT order_id FROM $group_recipient_table WHERE recipient_id = %d",
                        $recipient_id
                    ));

                    if ($groupRecipientCount && !empty($groupRecipientCount->order_id)) {
                        $sub_order = wc_get_order($groupRecipientCount->order_id);
                        // Retrieve meta data
                        $full_name    = wc_get_order_item_meta($item_id, 'full_name', true);
                        $company_name = wc_get_order_item_meta($item_id, '_recipient_company_name', true);
                        $address_1    = wc_get_order_item_meta($item_id, '_recipient_address_1', true);
                        $address_2    = wc_get_order_item_meta($item_id, '_recipient_address_2', true);
                        $city         = wc_get_order_item_meta($item_id, '_recipient_city', true);
                        $state        = wc_get_order_item_meta($item_id, '_recipient_state', true);
                        $zipcode      = wc_get_order_item_meta($item_id, '_recipient_zipcode', true);
                        $quantity     = $item->get_quantity();
        
                        // Format address
                        $address_parts = array_filter([$address_1, $address_2, $city, $state, $zipcode]);
                        $formatted_address = !empty($address_parts) ? implode(', ', array_map('esc_html', $address_parts)) : '-';
                        $status = 'completed'; // default fallback status

if ( is_a( $sub_order, 'WC_Order' ) ) {
    $status = $sub_order->get_status();
}
                        ?>
                        <tr>
                            <td><a href="<?php echo admin_url() ?>admin.php?page=wc-orders&action=edit&id=<?php echo $groupRecipientCount->order_id; ?>"><?php echo $groupRecipientCount->order_id; ?></a></td>
                            <td><?php echo !empty($full_name) ? esc_html($full_name) : '-'; ?></td>
                            <td><?php echo !empty($company_name) ? esc_html($company_name) : '-'; ?></td>
                            <td><?php echo $formatted_address; ?></td>
                            <td><?php echo esc_html($quantity); ?></td>
                            <td class="order_status column-order_status">
                            <mark class="order-status status-<?php echo esc_attr( sanitize_title( $status ) ); ?> tips">
    <span><?php echo esc_html( $status ); ?></span>
</mark>
                            </td>
                            <td><a href="<?php echo admin_url() ?>admin.php?page=wc-orders&action=edit&id=<?php echo $groupRecipientCount->order_id; ?>"><span data-tippy="View Order" class="dashicons dashicons-visibility"></span></a></td>
                        </tr>
                    <?php } 
                    } 
                ?>
            </tbody>
        </table>
        <?php
    }
    
    public function only_show_main_order_handler( $query_args ) {
        $query_args['parent_order_id'] = 0;
        return $query_args;
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
            $pid = $custom_data['process_id'] ?? 0;
    
            if ($is_single_order == 0) {
                $status = true;
             
                $recipients = OAM_Helper::get_recipient_by_pid($pid);
    
                foreach ($recipients as $recipient) {
                    $address = implode(' ', array_filter([
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
                    <div class="item"><strong>Total Honey Jars:</strong> ' . esc_html($quantity) . '</div>
                    <div class="item"><strong>Shipping Address:</strong> ' . esc_html($shipping_string) . '</div>
                </div>';
    
                return $block_content . $custom_content;
            }
        }
    
        if ($status) {
            $custom_content = '<div class="viewAllRecipientsPopupCheckoutContent">
                <div class="item"><strong>Total Honey Jars:</strong> ' . esc_html($total_quantity) . '</div>
                <div class="item"><strong>Total Recipients:</strong> ' . esc_html(count($recipients)) . '</div>
                <div class="item"><a href="#viewAllRecipientsPopupCheckout" class="viewAllRecipientsPopupCheckout btn-underline" data-lity>View All Recipients Details</a></div>
    
                <div id="viewAllRecipientsPopupCheckout" class="lity-popup-normal lity-hide">
                    <div class="popup-show order-process-block orthoney-datatable-warraper">
                        <h3>All Recipients Details</h3>
                        <div class="table-wrapper">
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
        return 'Thanks for placing the order with us. Your order has been processed.';
    }

    public function add_labels_to_order_overview($order_id) {
        if (!$order_id) return;
    
        $order = wc_get_order($order_id);
    
        if (!$order) return;
    
        ?>
        <div class="order-process-wrapp">
            <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
    
                <li class="woocommerce-order-overview__order order">
                    <label><?php esc_html_e( 'Order number:', 'woocommerce' ); ?></label>
                    <strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
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
        <?php $order_details_url = $order->get_view_order_url();
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