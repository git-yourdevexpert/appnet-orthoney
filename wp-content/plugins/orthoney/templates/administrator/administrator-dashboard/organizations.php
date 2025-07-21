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

    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(2) ,
    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(4) {
        width: 210px !important;
        max-width:210px !important;
        word-break: break-word;
    }
    
    #admin-organizations-results table.dataTable tbody th, table.dataTable tbody td:nth-child(1) {
        width: 60px !important;
        max-width:60px !important;
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
                    <!-- <th>Commission</th> -->
                    <th>Login</th>
                </tr>
            </thead>
            <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div id="view_org_details_popup" class="lity-popup-normal lity-hide">
    <div class="popup-show order-process-block orthoney-datatable-warraper">
        <h3 class="popup-title"><span></span> Organization details</h3>
        <div class="affiliate-dashboard  pb-40 mb-40">
            <div id="org-details-content" class="recipient-view-details-wrapper">
                <div class="recipient-view-details-wrapper">
                    <h6>Organization Profile:</h6>
                    <ul>
                        <li><strong>Website:</strong> <span id="org-website">www.abelgusikowski.com</span></li>
                        <li><strong>Address :</strong> <span id="org-full-address">123 Main St, Anytown, USA</span></li>
                        <li><strong>Phone :</strong> <span id="org-phone">123-456-7890</span></li>
                        <li><strong>Tax ID  :</strong> <span id="org-tax-id">43445345</span></li>
                    </ul>
                </div>
                <div class="recipient-view-details-wrapper">
                    <h6>Remittance:</h6>
                    <ul>
                        <li><strong>Make Check Payable to:</strong> <span id="org-check_payable">QA - AbelGusikowski's</span></li>
                        <li><strong>Address to Send Check to:</strong> <span id="org-check_address">www.abelgusikowski.com</span></li>
                        <li><strong>To the Attention of :</strong> <span id="org-check_attention">123 Main St, Anytown, USA</span></li>
                        <li><strong>Please indicate if check will be mailed to a home or your organization's office :</strong> <span id="org-check_office">43445345</span></li>
                    </ul>
                </div>
                <div class="recipient-view-details-wrapper">
                    <h6>Product Price:</h6>
                    <ul>
                        <li><strong><span id="product_price">$18.00</span></strong></li>
                    </ul>
                </div>
                <div class="recipient-view-details-wrapper">
                    <h6>Gift Card:</h6>
                    <ul>
                        <li><strong>In celebration of the New Year, a donation has been made in your name to </strong><span id="gift_card"></span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>