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
$current_url = home_url( $_SERVER['REQUEST_URI'] );


$groups = OAM_Helper::getGroupList($user_id);
?>
<div class="groups-block order-process-block">
    <h3>All Groups</h3>
    <?php 
    if(!empty($groups)){
        ?>
    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Group Name</th>
                <th>Number of Recipients</th>
                <th>CSV Files</th>
                <th>Date</th>
                <th style="width:300px">Action</th>
            </tr>
        </thead>
        <tbody id="groups-data"></tbody>
    </table>
    <div class="groups-pagination">
        <div id="groups-pagination"></div>
    </div>
    <?php } else{ echo OAM_COMMON_Custom::message_design_block("Group is not found."); return;}?>
</div>