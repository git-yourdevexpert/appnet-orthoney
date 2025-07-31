<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';

?>
<style>
    #admin-organizations-commission-results table.dataTable tbody th, table.dataTable tbody td:nth-child(1) {
        width: 210px !important;
        max-width:210px !important;
        word-break: break-word;
    }
</style>
<div class="affiliate-dashboard order-process-block">
    <div class="heading-title">
        <h3 class="block-title">Organizations Commission</h3>
        <div>
            <a class="w-btn us-btn-style_1" href="<?php echo esc_url( ADMINISTRATOR_DASHBOARD_LINK.'organizations/') ?>">Manage Organizations</a>
            <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
        </div>
    </div>
    <!-- Search and filter options -->
    <div class="orthoney-datatable-warraper">
        <div id="admin-organizations-commission-results" class="orthoney-datatable-warraper table-with-search-block">
            <table id="admin-organizations-commission-table" class="display " style="width:100%">
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
</div>