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

    #sales-representative-affiliate-commission-table_wrapper table.dataTable tbody th, table.dataTable tbody td:nth-child(1) {
        width: 220px !important;
        max-width:220px !important;
        word-break: break-word;
    }
  
</style>
 <div class="affiliate-dashboard order-process-block">
     <h3>Organizations commission</h3>
    <div class="orthoney-datatable-warraper table-with-search-block">
        <table id="sales-representative-affiliate-commission-table" class="display ">
            <thead>
                <tr>
                    <th>Organization</th>
                    <th>Total Order</th>
                    <th>Total Quantity</th>
                    <th>Your Total Sales</th>
                    <th>Your Total Cost (ORT`s Share)</th>
                    <th>Your Unit Profit <br><small>(Your Selling Price - Your Unit Cost)</small></th>
                    <th>Total Profit <br><small>(Total Quantity * Your Selling Profit)</small></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
