<?php
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


?>

<div class="customer-order-block order-process-block orthoney-datatable-warraper">
    <div class="heading-title"><h3 class="block-title">My Orders</h3></div>
    <table id="customer-orders-table" class="display">
    <thead>
        <tr>
            <th>Select</th>
            <th>Jar No</th>
            <th>Order No</th>
            <th>Date</th>
            <th>Billing Name</th>
            <th>Recipient Name</th>
            <th>Organization Code</th>
            <th>Total Honey Jar</th>
            <th>Total Recipient</th>
            <!-- <th>Type</th>
            <th>Status</th> -->
            <th>Price</th>
            <th style="width:200px">Action</th>
        </tr>
    </thead>
</table>

<table id="customer-jar-orders-table" class="display" style="display:none" >
    <thead>
        <tr>
            <!-- <th>Select</th> -->
            <th>Jar No</th>
            <th>Date</th>
            <th>Recipient Name</th>
            <th>Organization Code</th>
            <th>Total Honey Jar</th>
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