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
    echo  OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
    return;
} 

if (!in_array('sales_representative', $user_roles)) {
    $message = 'You do not have access to this page.';
    echo OAM_COMMON_Custom::message_design_block($message);
    return;
}
?>
<div class="affiliate-dashboard order-process-block">
    <h3>Assigned Customers</h3>
    <!-- Search and filter options -->
    
<div class="orthoney-datatable-warraper">
    <table id="sales-representative-customer-table" class="display">
        <thead>
            <tr>
                <th>Name</th>
                <th>Organization</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
</div>

