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
<style>

    #sales-representative-affiliate-table_wrapper table.dataTable tbody th, table.dataTable tbody td:nth-child(2) {
        width: 220px !important;
        max-width:220px !important;
        word-break: break-word;
    }
    #sales-representative-affiliate-table_wrapper table.dataTable tbody th, table.dataTable tbody td:nth-child(3) {
        width: 220px !important;
        max-width:220px !important;
        word-break: break-word;
    }
    #sales-representative-affiliate-table_wrapper table.dataTable tbody th, table.dataTable tbody td:nth-child(1) {
        width: 40px !important;
        max-width: 40px !important;
        word-break: break-word;
    }
  
</style>
 <div class="affiliate-dashboard order-process-block">
     <h3>Assigned Organizations</h3>
<div class="orthoney-datatable-warraper">
    <table id="sales-representative-affiliate-table" class="display">
        <thead>
            <tr>
                <th>Code</th>  
                <th>Organization</th>
                <th>Organization Admin</th>
                <th>New Organization</th>
                <th>Status</th>
                <!-- <th>Season Status</th> -->
                <th>Price</th>
                <!-- <th>Commission</th> -->
                <th>Login</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
</div>
