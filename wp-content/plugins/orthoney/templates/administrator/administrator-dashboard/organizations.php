<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
 $dashboard_link_label = 'Return to Dashboard';
$commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate(26382);
$commission_array_data = json_decode($commission_array, true);


?>
<style>

    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(2) {
        width: 150px !important;
        max-width:150px !important;
        word-break: break-word;
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
            <table id="admin-organizations-table" class="display" style="width:100%">
                <thead>
                <tr>
                    <th>Code</th>  
                    <th>Email</th>
                    <th>Organization</th>
                    <th>CSR Name</th>
                    <th>New Organization</th>
                    <th>Status</th>
                    <th>Season Status</th>
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