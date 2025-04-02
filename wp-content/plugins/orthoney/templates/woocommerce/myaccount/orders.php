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

<div class="customer-order-block order-process-block">
    <div class="heading-title"><h3 class="block-title">My Orders</h3></div>
    <table>
        <thead>
            <tr>
                <th>Order No</th>
                <th>Date</th>
                <th>Billing Name</th>
                <th>Affiliate Code</th>
                <th>Total Honey Jar</th>
                <th>Total Recipient</th>
                <th>Type</th>
                <th>Status</th>
                <th>Price</th>
                <th style="width:200px">Action</th>
            </tr>
        </thead>
        <tbody id="customer-order-data"></tbody>
    </table>
    <div class="customer-order-pagination">
        <div id="customer-order-pagination" class="pagination"></div>
    </div>
</div>