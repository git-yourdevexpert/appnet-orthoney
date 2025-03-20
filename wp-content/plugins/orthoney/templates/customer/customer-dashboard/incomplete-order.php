<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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
<div class="order-process-block">
    <h3>All Incomplete Orders</h3>
    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Date</th>
                <th>Name</th>
                <th style="width:300px">Action</th>
            </tr>
        </thead>
        <tbody id="order-process-data"></tbody>
    </table>
    <div class="order-process-pagination">
        <div id="io-pagination"></div>
    </div>
</div>