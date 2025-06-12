<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
 $dashboard_link_label = 'Return to Dashboard';


?>
<style>

    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(2) {
        width: 90px !important;
        max-width: 90px !important;
        word-break: break-word;
    }
    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(1) {
        width: 40px !important;
        max-width: 40px !important;
        word-break: break-word;
    }
    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(9) {
        width: 220px !important;
        max-width: 220px !important;
        word-break: break-word;
    }
</style>
<div class="affiliate-dashboard order-process-block">
    <div class="heading-title">
        <h3 class="block-title">Manage Organizations</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
    <!-- Search and filter options -->
    <div class="orthoney-datatable-warraper">
        <div id="admin-organizations-results" class="orthoney-datatable-warraper table-with-search-block">
            <table id="admin-organizations-table">
                <thead>
                <tr>
                    <th>Code</th>  
                    <th>Email</th>
                    <th>Organization Name</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Status</th>
                    <th>Season Status</th>
                    <th>Commission</th>
                    <th>Login</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>
</div>