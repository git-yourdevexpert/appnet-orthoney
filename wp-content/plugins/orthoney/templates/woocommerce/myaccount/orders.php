<?php

$uri = $_SERVER['REQUEST_URI'];
$path = trim(parse_url($uri, PHP_URL_PATH), '/');
$segments = explode('/', $path);

$first_segment = isset($segments[0]) ? $segments[0] : '';

/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.5.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );

$user_id = get_current_user_id();
$user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);

if (!is_user_logged_in()) {
    $message = 'Please login to view your Customer dashboard.';
    $url = ur_get_login_url();
    $btn_name = 'Login';
    echo OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
    return;
}

if (!in_array('customer', $user_roles) && !in_array('administrator', $user_roles)) {
    $message = 'You do not have access to this page.';
    echo OAM_COMMON_Custom::message_design_block($message);
    return;
}

 $dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
 $dashboard_link_label = 'Return to Dashboard';
 
if ($first_segment == 'dashboard'){
    $dashboard_link = CUSTOMER_DASHBOARD_LINK;
     $dashboard_link_label = 'Return to Dashboard';
   ?>
   <style>
     .customer-select-filter{display:none}
   </style>
   <?php 
}

$affiliate_id = 0;
$affiliate_token = '';
if ($first_segment == 'organization-dashboard'){
    ?>
     <style>
     .affiliate-token-filter{display:none}
   </style> 
    <?php
}
if ($first_segment == 'organization-dashboard'){
    global $wpdb;

    $affiliate_user_id = get_current_user_id();
    $affiliat_user_roles = OAM_COMMON_Custom::get_user_role_by_id($affiliate_user_id);
    if (in_array('yith_affiliate', $affiliat_user_roles) || in_array('affiliate_team_member', $affiliat_user_roles) || in_array('administrator', $affiliat_user_roles)) {
        $affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);
        if($affiliate_id == ''){
            $affiliate_id = $affiliate_user_id;
        }
    }

     $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;

    // Correct query execution
    $affiliate_token = $wpdb->get_var($wpdb->prepare("
        SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d
    ", $affiliate_id));

    $dashboard_link = ORGANIZATION_DASHBOARD_LINK;
    $dashboard_link_label = 'Return to Dashboard';
    ?>
    <style>
        .jar-search-by-organization,
        .search-by-organization{
            display:none;
        }
    </style>
    <?php 

}

if ($first_segment == 'sales-representative-dashboard') {
     ?>
    <style>
        .jar-search-by-organization,
        .search-by-organization{
            display:none;
        }
    </style>
    <?php 
    $dashboard_link = SALES_REPRESENTATIVE_DASHBOARD_LINK;
    $dashboard_link_label = 'Return to Dashboard';

    $user_id = get_current_user_id();
    $select_organization = get_user_meta($user_id, 'select_organization', true);
    $choose_organization = get_user_meta($user_id, 'choose_organization', true);

    $choose_ids_array = [];
    $affiliate_id = '';

    // Default
    $organizations_status = 'Assign All Organizations';

    if ($select_organization === 'choose_organization' && !empty($choose_organization)) {
        // Case: User selected specific organization(s)
        $choose_ids_array = array_map('intval', (array) $choose_organization);
        $affiliate_token = implode(',', $choose_ids_array);
    } else {
        // Case: Assign all enabled and not banned organizations for this user
        global $wpdb;
        $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;

        $results = $wpdb->get_results($wpdb->prepare("
            SELECT token FROM {$yith_wcaf_affiliates_table} WHERE enabled = '1' AND banned = '0'
        "));

        // Extract token values from objects
        if (!empty($results)) {
            foreach ($results as $row) {
                $choose_ids_array[] = sanitize_text_field($row->token);
            }
        }

        $affiliate_token = implode(',', $choose_ids_array);
    }
}
?>

<div class="customer-order-block order-process-block orthoney-datatable-warraper">
    <div class="heading-title">
        <h3 class="block-title"><?php echo ($first_segment == 'organization-dashboard' ? 'All Customers Order' : 'My Orders') ?></h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
    <div class="order-filter-tab">
        <label for="main_order">
                <input type="radio" id="main_order" name="table_order_type" value="main_order" checked>
                <span>Order List by Number</span>
        </label>
            
        <label for="sub_order_order">
            <input type="radio" id="sub_order_order" name="table_order_type" value="sub_order_order">
            <span>Order list by Recipient Number </span>
        </label>
    </div>
    <table id="customer-orders-table" data-tabletype="<?php echo $first_segment; ?>" class="display" data-affiliate_id="<?php echo  $affiliate_id ?>" data-affiliate_token="<?php echo $affiliate_token ?>">
    <thead>
        <tr>
            <th>Select</th>
            <th>Recipient No</th>
            <th>Order No</th>
            <th>Date</th>
            <th><?php echo ($first_segment == 'dashboard') ? 'Billing Name': 'Billing Name'  ?></th>
            <th>Recipient Name</th>
            <th>Organization Code</th>
            <th>Qty</th>
            <th>Total Recipient</th>
            <!-- <th>Type</th>
            <th>Status</th> -->
            <th>Price</th>
            <th style="width:200px">Action</th>
        </tr>
    </thead>
</table>

<table id="customer-jar-orders-table" class="display" style="display:none" data-affiliate_id="<?php echo  $affiliate_id ?>" data-affiliate_token="<?php echo $affiliate_token ?>">
    <thead>
        <tr>
            <!-- <th>Select</th> -->
            <th>Recipient No</th>
            <th>Date</th>
            <th>Recipient Name</th>
            <th>Organization Code</th>
            <th>Qty</th>
            <th>Jar Tracking</th>
            <th>Status</th>
            <!-- <th>Price</th> -->
            <th style="width:200px">Action</th>
        </tr>
    </thead>
</table>

</div>

<div id="recipient-order-manage-popup" class="lity-popup-normal lity-hide">
    <div class="popup-show order-process-block orthoney-datatable-warraper">
        <h3 class="popup-title"><span></span> Order Details</h3>
        <?php echo OAM_Helper::get_recipient_order_form(); ?>
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

<div id="view-order-tracking-popup" class="lity-hide black-mask full-popup popup-show">
    <h3>Order Tracking Details</h3>
    <div class=" orthoney-datatable-warraper">
        <table>
            <thead>
                <tr>
                    <th>Jar Order No</th>
                    <th>Tracking No.</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>202504290004-1-1</td>
                    <td>202504290004-1-1</td>
                    <td>On the Way</td>
                </tr>
                <tr>
                    <td>202504290004-1-1</td>
                    <td>202504290004-1-1</td>
                    <td>On the Way</td>
                </tr>
                <tr>
                    <td>202504290004-1-1</td>
                    <td>202504290004-1-1</td>
                    <td>On the Way</td>
                </tr>
                <tr>
                    <td>202504290004-1-1</td>
                    <td>202504290004-1-1</td>
                    <td>On the Way</td>
                </tr>
                <tr>
                    <td>202504290004-1-1</td>
                    <td>202504290004-1-1</td>
                    <td>On the Way</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>