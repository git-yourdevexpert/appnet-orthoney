<?php
/**
 * Display detailed recipient order information.
 */

defined('ABSPATH') || exit;

$order_id = get_query_var('order-details');
$order = wc_get_order($order_id);


if (!$order) return;
$order_date = $order->get_date_created(); // May be WC_DateTime or null


// If it's a WC_DateTime object, convert to native DateTime
if (is_object($order_date) && method_exists($order_date, 'date')) {
    $order_date = new DateTime($order_date->date('Y-m-d H:i:s'));
}

// If it's still a string (fallback case), parse it manually
if (is_string($order_date)) {

    [$date_part, $time_part] = explode(' ', $order_date);
    [$year, $month, $day] = explode('-', $date_part);
    [$hour, $minute, $second] = explode(':', $time_part);

    $formatted_string = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
        $year, $month, $day, $hour, $minute, $second
    );

    $order_date = DateTime::createFromFormat('Y-m-d H:i:s', $formatted_string);
}

$editable = OAM_COMMON_Custom::check_order_editable($order_date);

$order_items = $order->get_items();
$quantity = array_sum(wp_list_pluck($order_items, 'quantity'));

foreach ($order_items as $item) {
    $quantity = (int) $item->get_quantity();
    // echo $item->get_meta('single_order', true);
    // echo $item->get_meta('process_id', true);
    // echo $item->get_meta('greeting', true);
}

$user_id = get_current_user_id();
$notes = $order->get_customer_order_notes();

// Get order process user
global $wpdb;
$wc_orders_meta_table = $wpdb->prefix . 'wc_orders_meta';
$order_process_user_id = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wc_orders_meta_table} WHERE order_id = %d AND meta_key = %s",
    $order->get_id(), 'order_process_by'
));

$order_process_by = '';
if ($order_process_user_id && ($user_info = get_userdata($order_process_user_id))) {
    $order_process_by = ' and Process by <mark class="order-status">' . esc_html($user_info->display_name) . '</mark>';
}

// Get recipient data
$sub_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
$recipient_order_table = $wpdb->prefix . 'oh_recipient_order';
$recipientResult = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$recipient_order_table} WHERE order_id = %d",
    $sub_order_id
));

// Determine organization
$organization = 'Orthoney';
$organization_data = '';
if (!empty($recipientResult[0]->affiliate_token) && $recipientResult[0]->affiliate_token !== 'Orthoney') {
    $token = $recipientResult[0]->affiliate_token;
    $meta_key = '_yith_wcaf_name_of_your_organization';

    $organization = $wpdb->get_var($wpdb->prepare(
        "SELECT um.meta_value
         FROM {$wpdb->usermeta} um
         JOIN {$wpdb->prefix}yith_wcaf_affiliates aff ON um.user_id = aff.user_id
         WHERE aff.token = %s AND um.meta_key = %s",
        $token, $meta_key
    ));

    if($organization != 'Orthoney'){
        $organization_data_query = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                aff.*,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_city' THEN um.meta_value END) AS _yith_wcaf_city,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_state' THEN um.meta_value END) AS _yith_wcaf_state,
                MAX(CASE WHEN um.meta_key = 'billing_city' THEN um.meta_value END) AS billing_city,
                MAX(CASE WHEN um.meta_key = 'billing_state' THEN um.meta_value END) AS billing_state,
                MAX(CASE WHEN um.meta_key = 'shipping_city' THEN um.meta_value END) AS shipping_city,
                MAX(CASE WHEN um.meta_key = 'shipping_state' THEN um.meta_value END) AS shipping_state
            FROM {$wpdb->prefix}yith_wcaf_affiliates AS aff
            LEFT JOIN {$wpdb->usermeta} AS um ON um.user_id = aff.user_id
            WHERE aff.token = %s
            GROUP BY aff.user_id
            ",
            $token
        ));

        // Choose the first available city from multiple sources
        $city = $organization_data_query->_yith_wcaf_city 
            ?: $organization_data_query->billing_city 
            ?: $organization_data_query->shipping_city;
        $state = $organization_data_query->_yith_wcaf_state 
            ?: $organization_data_query->billing_state 
            ?: $organization_data_query->shipping_state;

        // Combine with $organization name (assuming it's defined)
        // echo $organization_data = $organization . ' , ' . $city. ', '.$state;


        $merged_address = implode(', ', array_filter(['['.$token.']',$organization,$city, $state ]));
                  
          $organization_data = trim($merged_address);
    }


}
?>

<div class='loader multiStepForm'>
    <div>
        <h2 class='swal2-title'>Processing...</h2>
        <div class='swal2-html-container'>Please wait while we process your request.</div>
        <div class='loader-5'></div>
    </div>
</div>

<div class="order-process-block customer-order-details-section">

        <div class="heading-title">
        <div class="order-number">
            <h3>#<?php echo esc_html($sub_order_id); ?> Order Details </h3>
            <p>
                <?php
                printf(
                    esc_html__('Order #%1$s was placed on %2$s%3$s.', 'woocommerce'),
                    '<mark class="order-number">' . esc_html($sub_order_id) . '</mark>',
                    '<mark class="order-date">' . esc_html(wc_format_datetime($order->get_date_created())) . '</mark>',
                    $order_process_by
                );
                ?>
            </p>
            <p>
                <?php
                if($organization_data != ''):
                printf(
                    esc_html__('This order will support %1$s .', 'woocommerce'),
                    '<mark class="order-number">' . esc_html($organization_data) . '</mark>',
                    $order_process_by
                );
                
            endif;
                ?>
            </p>
            </div>
        </div>
        <div class="customer-order-details">
            <div class="woocommerce-customer-details">
                <h3 class="woocommerce-column__title">Billing address</h3>
                <?php 
                $state_code = $order->get_billing_state();
                $states = WC()->countries->get_states('US');
                $full_state = ($states[$state_code] ?? $state_code) . " ($state_code)";
                ?>
                <strong>Name: </strong><?php echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()); ?><br>
                <strong>Email: </strong><?php echo esc_html($order->get_billing_email()); ?><br>
                <strong>Address: </strong><?php echo esc_html(trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2())); ?><br>
                <strong>City: </strong><?php echo esc_html($order->get_billing_city()); ?><br>
                <strong>State: </strong><?php echo esc_html($full_state); ?><br>
                <strong>Zip Code: </strong><?php echo esc_html($order->get_billing_postcode()); ?><br>
                <?php 
                 if($editable === true){
                    echo '<button class="w-btn us-btn-style_1 editBillingAddress" data-order="'.$order_id.'" data-popup="#edit-billing-address-popup">Edit Billing address </button>';
                }
                ?>
                
            </div>
            <div class="woocommerce-customer-details">
            <h3 class="woocommerce-column__title">Jar Order Details</h3>
            <strong>Total Jars in Order: </strong><?php echo esc_html($quantity); ?><br>
                <strong>Total Price: </strong><?php echo wc_price($order->get_total()); ?><br>
                <strong>Shipping: </strong><?php echo wc_price($order->get_shipping_total()); ?><br>
                <strong>Delivered Type: </strong><?php echo (!empty($recipientResult) ? "Ship to Multi Address" : "Ship to Single Address") ?>
            </div>
        </div>
    
    
    <div id="recipient-order-data" class="table-data orthoney-datatable-warraper">
        <div class="download-csv heading-title">
            <div></div>
            <div></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Jar No</th>
                    <th>Recipient Name</th>
                    <th>Company Name</th>
                    <th>Address</th>
                    <th>Total Honey Jar</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $recipients = !empty($recipientResult) ? $recipientResult : [(object) [
                    'recipient_order_id' => $sub_order_id,
                    'full_name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                    'company_name' => '',
                    'address_1' => $order->get_shipping_address_1(),
                    'address_2' => $order->get_shipping_address_2(),
                    'city' => $order->get_shipping_city(),
                    'state' => $order->get_shipping_state(),
                    'zipcode' => $order->get_shipping_postcode(),
                    'quantity' => $quantity
                ]];

                foreach ($recipients as $sub_order) {
                    $address = implode(', ', array_filter([
                        $sub_order->address_1,
                        $sub_order->address_2,
                        $sub_order->city,
                        $sub_order->state,
                        $sub_order->zipcode
                    ]));
                    ?>
                    <tr data-id="<?php echo esc_attr($sub_order->recipient_order_id); ?>">
                        <td><?php echo esc_html($sub_order->recipient_order_id); ?></td>
                        <td><?php echo esc_html($sub_order->full_name); ?></td>
                        <td><?php echo esc_html($sub_order->company_name); ?></td>
                        <td><?php echo esc_html($address); ?></td>
                        <td><?php echo esc_html($sub_order->quantity); ?></td>
                        <td></td>
                        <td>
                            <?php 
                            if(!empty($recipientResult)){
                                ?>
                                <button class="far fa-eye viewRecipientOrder" data-order="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-popup="#recipient-order-edit-popup"></button>

                                <?php 
                                if($editable === true){
                                ?>
                                <button class="far fa-edit editRecipientOrder" data-order="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-popup="#recipient-order-manage-popup"></button>
                                <?php
                                }
                            }
                            ?>
                            
                            <button class="deleteRecipient far fa-times" data-order="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-recipientname="<?php echo esc_attr($sub_order->full_name); ?>"></button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <!-- Popups -->
        <div id="recipient-order-manage-popup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block orthoney-datatable-warraper">
                <h3 class="popup-title"><span></span> Order Details</h3>
                <?php echo OAM_Helper::get_recipient_order_form(); ?>
            </div>
        </div>
        <div id="edit-billing-address-popup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block orthoney-datatable-warraper">
                <h3 class="popup-title">Edit Billing Address</h3>
               <?php echo OAM_Helper::get_edit_billing_address_form(); ?>
            </div>
        </div>

        <div id="recipient-order-edit-popup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block orthoney-datatable-warraper">
                <h3 class="popup-title"><span></span> Order Details</h3>
                <div class="recipient-view-details-wrapper"></div>
                <div class="footer-btn gfield--width-full">
                    <button type="button" class="w-btn us-btn-style_4" data-lity-close>Cancel</button>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>