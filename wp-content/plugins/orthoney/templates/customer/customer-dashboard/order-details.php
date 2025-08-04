<?php
/**
 * Display detailed recipient order information.
 */

defined('ABSPATH') || exit;

$order_id = get_query_var('order-details');
$order = wc_get_order($order_id);


if (!$order) return;
$order_date = $order->get_date_created(); // May be WC_DateTime or null
$payment_method_title = $order->get_payment_method_title() == 'Pay by Check' ? 'Check payments' : $order->get_payment_method_title();

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
 $oh_wc_jar_order = $wpdb->prefix . 'oh_wc_jar_order';
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
    "SELECT DISTINCT * FROM {$recipient_order_table} WHERE order_id = %d",
    $sub_order_id
));


$jarOrderResult = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT count(*) FROM {$oh_wc_jar_order} WHERE order_id = %d",
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

        $merged_address = implode(', ', array_filter([$organization,$city, $state ]));
        $organization_data = trim($merged_address).' (Code : '.$token.')';
    }
}


$dashboard_link_label = 'Return to Orders';
$dashboard_link = CUSTOMER_DASHBOARD_LINK.'orders/';
if(isset($_GET['return_url']) && $_GET['return_url']=='admin'){
    $dashboard_link = ADMINISTRATOR_DASHBOARD_LINK.'orders/';
    $dashboard_link_label = 'Return to Orders';
}
if(isset($_GET['return_url']) && $_GET['return_url']=='organization'){
    $dashboard_link = ORGANIZATION_DASHBOARD_LINK.'orders-list/';
     $dashboard_link_label = 'Return to Orders';
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
            <div>
                <?php 
                $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);
                if (in_array('administrator', $user_roles)) {
                ?>
                <button data-popup="#order-switch-org-popup" class="w-btn us-btn-style_1 orderchangeorg" data-organization_data="<?php echo $organization_data ?>" data-wc_order_id="<?php echo $order_id ?>" data-order_id="<?php echo $sub_order_id ?>" data-currentorg="<?php echo $token ?>">Switch ORG</button>
                <a class="w-btn us-btn-style_1" target="_blank" href="<?php echo admin_url('post.php?post=' . $order_id . '&action=edit') ?>">View WP Admin Order</a>
                <?php } ?>
                <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
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
                <?php if ( $order->get_billing_phone() ) : ?>
                    <strong>Phone Number: </strong><?php echo esc_html( $order->get_billing_phone() ); ?><br>
                <?php endif; ?>
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
                <strong>Payment Method: </strong><?php echo esc_html($payment_method_title); ?><br>
                
            </div>
        </div>
    
    <?php 
   if ($jarOrderResult !== 0) {
    ?>
    <div id="recipient-jar-order-data" class="table-data orthoney-datatable-warraper">
        
        <table>
            <thead>
                <tr>
                    <th>Recipient No</th>
                    <th>Jar No</th>
                    <th>Recipient Name</th>
                    <th>Company Name</th>
                    <th>Address</th>
                    <th>Honey Jar</th>
                    <th>Tracking status</th>
                    <th style="width:200px">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $uniqueRecipients = [];
                $subOrderIds = [];

                if (!empty($recipientResult)) {
                    foreach ($recipientResult as $recipient) {
                        if (!in_array($recipient->recipient_order_id, $subOrderIds)) {
                            $uniqueRecipients[] = $recipient;
                            $subOrderIds[] = $recipient->recipient_order_id;
                        }
                    }
                }

                $recipients = !empty($uniqueRecipients) ? $uniqueRecipients : [(object) [
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

                    if($sub_order->quantity > 6){
                    
                        $jarOrderResult = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$oh_wc_jar_order} 
                            WHERE recipient_order_id = %s AND order_id != %d
                            GROUP BY recipient_order_id",
                            $sub_order->recipient_order_id, 0
                        ));
                    }else{
                         $jarOrderResult = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM {$oh_wc_jar_order} 
                            WHERE recipient_order_id = %s AND order_id != %d
                            GROUP BY jar_order_id",
                            $sub_order->recipient_order_id, 0
                        ));
                    }
                    ?>
                    <tr class="group-header" data-count="<?php echo count($jarOrderResult) ?>" data-group="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-id="<?php echo esc_attr($sub_order->recipient_order_id); ?>">
                        <td colspan="8" style="background: #cbdac7 !important;">
                            <div class="heading-title" style="margin-bottom: 0px;">
                                <div>
                                    <strong>
                            <?php echo "#".esc_html($sub_order->recipient_order_id); ?>
                            <?php //echo esc_html($sub_order->full_name); ?>
                            <?php //echo esc_html($sub_order->company_name); ?>
                            <?php // echo esc_html($address); ?>
                            <?php echo "Qty (" .esc_html($sub_order->quantity).")"; ?>
                            </strong>
                        </div>
                        <div>
                            <?php 
                            if(!empty($recipientResult)){
                                ?>
                                <button class="far fa-eye viewRecipientOrder" data-order="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-popup="#recipient-order-edit-popup"></button>

                                <?php 
                                if($editable === true ){
                                    if(isset($_GET['return_url']) && $_GET['return_url'] !='organization'){
                                    ?>
                                    <button class="far fa-edit editRecipientOrder" data-order="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-popup="#recipient-order-manage-popup"></button>
                                    <?php
                                    }
                                }
                            }
                            ?>
                            </div>
                            </div>
                            
                            <!-- <button class="deleteRecipient far fa-times" data-order="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-recipientname="<?php echo esc_attr($sub_order->full_name); ?>"></button> -->
                        </td>
                    </tr>
                    
                    <?php 
                    if(!empty($jarOrderResult)){
                        foreach ($jarOrderResult as $jar_order) {
                            ?>
                              <tr data-id="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-group="<?php echo esc_attr($sub_order->recipient_order_id); ?>">
                                <td><?php echo esc_html($sub_order->recipient_order_id); ?></td>
                                <td><?php echo esc_html($jar_order->jar_order_id); ?></td>
                                <td><?php echo esc_html(html_entity_decode(stripslashes($sub_order->full_name))); ?></td>
                                <td><?php echo esc_html(html_entity_decode(stripslashes($sub_order->company_name))); ?></td>
                                <td><?php echo esc_html(html_entity_decode(stripslashes($address))); ?></td>
                                <td><?php echo esc_html( ($sub_order->quantity > 6 ? $sub_order->quantity : 1)); ?></td>
                                <th></th>
                                <td>Processing</td>
                            </tr>
                            <?php
                        }
                    }

                    ?>
                <?php } ?>
            </tbody>
        </table>
    </div>
    <?php 
    }else{
        ?>
  <div id="recipient-order-data" class="table-data orthoney-datatable-warraper">
      
        <table>
            <thead>
                <tr>
                    <th>Recipient No</th>
                    <th>Recipient Name</th>
                    <th>Company Name</th>
                    <th>Address</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th style="width:200px">Action</th>
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
                        <td><?php echo esc_html(html_entity_decode(stripslashes($sub_order->full_name))); ?></td>
                        <td><?php echo esc_html(html_entity_decode(stripslashes($sub_order->company_name))); ?></td>
                        <td><?php echo esc_html(html_entity_decode(stripslashes($address))); ?></td>
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
                            
                            <!-- <button class="deleteRecipient far fa-times" data-order="<?php echo esc_attr($sub_order->recipient_order_id); ?>" data-recipientname="<?php echo esc_attr($sub_order->full_name); ?>"></button> -->
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        
    </div>
        <?php
    }
    ?>
    </div>
    <!-- Popups -->
     <div id="order-switch-org-popup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block orthoney-datatable-warraper">
                <h3 class="popup-title"><span></span> Switch Organization</h3>
                <p><strong><span class="org-details-div"></span></strong> </p>
                <div class="affiliate-dashboard pb-40 mb-40">
                    <?php 
                    global $wpdb;

                    $affiliate = (!isset($token) || $token === '') ? 'Orthoney' : $token;

                    $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;

                    // Fetch all active and non-banned affiliates
                    $query = "SELECT * FROM {$yith_wcaf_affiliates_table} WHERE enabled = 1 AND banned = 0 ORDER BY token";
                    $affiliateList = $wpdb->get_results($query);

                    // Reorder list to move 'ATL' token to the top
                    $aha_item = null;
                    $reordered = [];

                    if (!empty($affiliateList)) {
                        foreach ($affiliateList as $index => $item) {
                            if ($item->token === 'ATL') {
                                $aha_item = $item;
                                unset($affiliateList[$index]);
                                break;
                            }
                        }
                    }

                    $affiliateList = array_values($affiliateList); // Reindex
                    if ($aha_item) {
                        $reordered[] = $aha_item;
                    }
                    $affiliateListReordered = array_merge($reordered, $affiliateList);
                    ?>
                    <!-- Search and filter options -->
                    <div class="filter-container orthoney-datatable-warraper">
                        <div class="customer-email-search linked-customer-search">
                            <input type="hidden" name="wc_order_id" id="wc_order_id" value="">
                            <input type="hidden" name="order_id" id="order_id" value="">
                            <select id="order-org-search" class="form-control" required data-error-message="Please select an organization">
                                <!-- <option data-token="Orthoney" value="Orthoney">Honey from the Heart</option> -->

                                <?php
                                if (!empty($affiliateListReordered)) {
                                    foreach ($affiliateListReordered as $data) {
                                        if (empty($data->token)) continue;

                                        $user_id = $data->user_id;
                                        $states = WC()->countries->get_states('US');

                                        $state = get_user_meta($user_id, '_yith_wcaf_state', true) ?: 
                                                get_user_meta($user_id, 'billing_state', true) ?: 
                                                get_user_meta($user_id, 'shipping_state', true);

                                        $city = get_user_meta($user_id, '_yith_wcaf_city', true) ?: 
                                                get_user_meta($user_id, 'billing_city', true) ?: 
                                                get_user_meta($user_id, 'shipping_city', true);

                                        $orgName = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true) ?: 
                                                get_user_meta($user_id, '_orgName', true);

                                        $state_name = $states[$state] ?? $state;

                                        $value = '[' . $data->token . '] ' . ($orgName ?: $data->display_name);
                                        if (!empty($city)) $value .= ', ' . $city;
                                        if (!empty($state_name)) $value .= ', ' . $state_name;

                                        echo '<option data-token="' . esc_attr($data->token) . '" value="' . esc_attr($user_id) . '">' . esc_html($value) . '</option>';
                                    }
                                }
                                ?>
                            </select>

                            <div id="suggestions"></div>
                            <span class="error-message"></span>
                            <button id="switch-org-button" class="w-btn us-btn-style_2">Switch Organization</button>
                            <ul id="order-org-search-results"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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