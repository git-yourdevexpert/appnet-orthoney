<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;
$table_name = OAM_Helper::$oh_affiliate_customer_linker;

// Fetch records with status 1 (Approved) and 0 (Pending)
$requests = $wpdb->get_results(
    "SELECT id, customer_id, affiliate_id, token, timestamp, status 
     FROM $table_name 
     WHERE status IN (0, 1)"
);
?>
<div class="affiliate-dashboard order-process-block">
    <h3>Customers</h3>
    <!-- Search and filter options -->
    <div class="filter-container">
        <input type="text" id="customer-email-search" required placeholder="Enter Customer Email" data-error-message="Please enter a Email.">
        
            <span class="error-message"></span>
    <button id="search-button" class="w-btn us-btn-style_2">Search</button>
    <ul id="customer-email-results"></ul>
    </div>
    <div id="affiliate-results">
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
                        $status_label = ($request->status == 1) ? 'Approved' : 'Pending';
                        
                        echo '<tr>
                                <td><div class="thead-data">ID</div>' . esc_html($request->id) . '</td>
                                <td><div class="thead-data">Customer Name</div>' . $customer_name . '</td>
                                <td><div class="thead-data">Customer Email</div>' . $customer_email . '</td>
                                <td><div class="thead-data">Status</div>' . esc_html($status_label) . '</td>';
                        
                        // Show resend button if status is pending
                        if ($request->status == 0) {
                            echo '<td><div class="thead-data">Action</div><button class="resend-email-btn" data-customer-id="' . esc_attr($request->customer_id) . '">Resend Email</button></td>';
                        } else {
                            echo '<td><div class="thead-data">Action</div>-</td>';
                        }
                        
                        echo '</tr>';
                        
                    }
                }else{
                    echo '<p>No customer found.</p>';
                }
                ?>

            </tbody>
        </table>
    </div>
</div>