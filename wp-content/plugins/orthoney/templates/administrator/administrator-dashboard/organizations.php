<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';

$new_organization = OAM_AFFILIATE_Helper::is_user_created_this_year($user_id) ? 'New' : 'Returning';


?>
<style>

    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(2) {
        width: 150px !important;
        max-width:150px !important;
       word-wrap: break-word;
    }
    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(1) {
        width: 40px !important;
        max-width: 40px !important;
        word-break: break-word;
    }
  
</style>
<div class="affiliate-dashboard order-process-block">
    <div class="heading-title">
        <h3 class="block-title">Manage Organizations</h3>
        <div>
            <a class="w-btn us-btn-style_1" href="<?php echo esc_url( admin_url('admin.php?page=yith_wcaf_panel&tab=affiliates&sub_tab=affiliates-list&status=new')) ?>">New Requests</a>
            <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
        </div>
    </div>
    <!-- Search and filter options -->
    <div class="orthoney-datatable-warraper">
        <div id="admin-organizations-results" class="orthoney-datatable-warraper table-with-search-block">
            <table id="admin-organizations-table" class="display " style="width:100%">
                <thead>
                <tr>
                    <th>Code</th>  
                    <th>Organization</th>
                    <th>Organization Admin</th>
                    <th>CSR Name</th>
                    <th>Status</th>
                    <th>Status</th>
                    <!-- <th>Season Status</th> -->
                    <th>Price</th>
                    <th>Commission</th>
                    <th>Login</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>
</div>