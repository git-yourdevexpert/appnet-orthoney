<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);
if (in_array('yith_affiliate', $user_roles) || in_array('affiliate_team_member', $user_roles) || in_array('administrator', $user_roles)) {
    $affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);
    if($affiliate_id == ''){
        $affiliate_id = $user_id;
    }
}
global $wpdb;
$table_name = OAM_Helper::$oh_affiliate_customer_linker;

// Fetch records with status 1 (Approved) and 0 (Pending)
$requests = $wpdb->get_results(
    "SELECT id, customer_id, affiliate_id, token, timestamp, status 
     FROM $table_name 
     WHERE status IN (0, 1) AND  affiliate_id = $user_id"
);
$dashboard_link = ORGANIZATION_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';
?>
<div class="affiliate-dashboard order-process-block pb-40 mb-40">
  
    <div class="heading-title">
        <h3 class="block-title">Search customer here to link</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
    <!-- Search and filter options -->
    <div class="filter-container orthoney-datatable-warraper">
        <div class="customer-email-search linked-customer-search">       
            <input type="text" id="customer-email-search" class="autocomplete-suggestions" required placeholder="Enter Customer Email" data-error-message="Please enter a Email.">
        <div id="suggestions"></div>

            <span class="error-message"></span>
    <button id="search-button" class="w-btn us-btn-style_2">Find customer</button>
    <ul id="customer-email-results"></ul></div>
 
    </div>
      </div>
<!-- Search and filter options -->
<!-- list -->
 <div class="affiliate-dashboard order-process-block">
   <h3>Already linked customer </h3>
    <div id="affiliate-results" class="orthoney-datatable-warraper table-with-search-block">
        <table>
            <thead>
                <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Customer Email</th>
                <th>Status</th>
                <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                
                if (!empty($requests)) {
                    foreach ($requests as $request) {
                        
                        // Get customer data
                        $customer = get_userdata($request->customer_id);
                        $customer_name = $customer ? esc_html($customer->display_name) : 'Unknown';
                        $customer_email = $customer ? esc_html($customer->user_email) : 'Unknown';
                        $status_label = 'Blocked';
                        if($request->status == 0){
                            $status_label = 'Pending Approval';
                        }
                        if($request->status == 1){
                            $status_label = 'Approved';
                        }
                        
                        echo '<tr>
                                <td><div class="thead-data">ID</div>' . esc_html($request->customer_id) . '</td>
                                <td><div class="thead-data">Customer Name</div>' . $customer_name . '</td>
                                <td><div class="thead-data">Customer Email</div>' . $customer_email . '</td>
                                <td><div class="thead-data">Status</div>' . esc_html($status_label) . '</td>';
                        
                        // Show resend button if status is pending
                        if ($request->status == 0) {
                            echo '<td><div class="thead-data">Action</div><button class="resend-email-btn icon-txt-btn" data-customer-id="' . esc_attr($request->customer_id) . '"><img src="'.OH_PLUGIN_DIR_URL.'/assets/image/re-order-icon.png">Resend Email</button></td>';
                        } else {
                            if ($request->status == 1) {
                                echo '<td><div class="thead-data">Action</div>';
                                echo '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($request->customer_id) . '"><img src="'.OH_PLUGIN_DIR_URL.'/assets/image/login-customer-icon.png">Login as Customer</button></td>';
                            }else{
                                echo '<td><div class="thead-data">Action</div>-</td>';
                            }
                        }
                        
                        echo '</tr>';
                        
                    }
                }
                ?>

            </tbody>
        </table>
    </div>
    <!-- list -->
</div>

<script>
document.getElementById('customer-email-search').addEventListener('input', async function(e) {
    const query = e.target.value.trim();
    if (query.length < 2) return;

    const response = await fetch(oam_ajax.ajax_url + '?action=search_customers_autosuggest&term=' + encodeURIComponent(query));
    const results = await response.json();

    const suggestionBox = document.getElementById('suggestions');
    suggestionBox.innerHTML = '';
    
    results.forEach(user => {
        const div = document.createElement('div');
        div.textContent = `${user.display_name} (${user.user_email})`;
        suggestionBox.appendChild(div);
    });
});
</script>