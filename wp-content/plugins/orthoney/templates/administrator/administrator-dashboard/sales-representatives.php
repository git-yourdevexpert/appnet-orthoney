<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
 $dashboard_link_label = 'Return to Dashboard';
?>
<div class="affiliate-dashboard order-process-block">
    <div class="heading-title">
        <h3 class="block-title">Manage Sales Representatives</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
    <!-- Search and filter options -->
    <div class="orthoney-datatable-warraper">
        <div id="admin-sales-representative-results" class="orthoney-datatable-warraper table-with-search-block">
            <table id="admin-sales-representative-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>