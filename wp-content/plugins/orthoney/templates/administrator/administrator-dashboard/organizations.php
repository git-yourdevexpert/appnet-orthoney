<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$dashboard_link = ADMINISTRATOR_DASHBOARD_LINK;
 $dashboard_link_label = 'Return to Dashboard';
 
        $new_organization = OAM_AFFILIATE_Helper::is_user_created_this_year($user_id) ? 'Yes' : 'No';

        $exclude_coupon = EXCLUDE_COUPON;

    // Initialize counters
        $total_all_quantity = $fundraising_qty = $wholesale_qty = 0;
        $total_orders = $wholesale_order = 0;
        $unit_price = $unit_cost = 0;
        $total_commission = 0;

        if (!empty($commission_array_data['data'])) {
            foreach ($commission_array_data['data'] as $value) {
                // Aggregate quantities
                $fundraising_qty = $value['total_quantity'];
                $wholesale_qty = $value['wholesale_qty'];

                // Process only if affiliate account is active
                if (!empty($value['affiliate_account_status'])) {
                    $unit_price = $value['par_jar'];
                    $unit_cost = $value['minimum_price'];
                    $total_all_quantity += $value['total_quantity'];
                    $total_orders++;

                    // Handle coupon logic
                    $coupon_array = !empty($value['is_voucher_used']) 
                        ? array_values(array_diff(explode(",", $value['is_voucher_used']), $exclude_coupon)) 
                        : [];

                    if (empty($coupon_array)) {
                        $total_commission += $value['commission'];
                    } else {
                        $wholesale_order++;
                    }
                }
            }
        }

        // Final quantity and order calculations
        $total_all_quantity = $fundraising_qty;
        $fundraising_orders = $total_orders - $wholesale_order;

        // Calculate total commission based on jar threshold
        echo $total_commission = ($total_all_quantity > 50)  ? wc_price($fundraising_qty * ($unit_price - $unit_cost)) : wc_price(0);

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