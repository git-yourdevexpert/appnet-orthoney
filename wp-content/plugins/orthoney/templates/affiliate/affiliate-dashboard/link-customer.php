<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<input type="text" id="customer-email-search" required placeholder="Enter Customer Email" data-error-message="Please enter a Email.">
<span class="error-message"></span>
<button id="search-button">Search</button>
<ul id="customer-email-results"></ul>

<?php 
global $wpdb;
$table_name = OAM_Helper::$oh_affiliate_customer_linker;

// Fetch records with status 1 (Approved) and 0 (Pending)
$requests = $wpdb->get_results(
    "SELECT id, customer_id, affiliate_id, token, timestamp, status 
     FROM $table_name 
     WHERE status IN (0, 1)"
);

if (!empty($requests)) {
    echo '<div class="custom-table">';
    echo '<table>';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Customer Email</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
          </thead>
          <tbody>';
    
    foreach ($requests as $request) {
        // Get customer data
        $customer = get_userdata($request->customer_id);
        $customer_name = $customer ? esc_html($customer->display_name) : 'Unknown';
        $customer_email = $customer ? esc_html($customer->user_email) : 'Unknown';
        $status_label = ($request->status == 1) ? 'Approved' : 'Pending';
        
        echo '<tr>
                <td>' . esc_html($request->id) . '</td>
                <td>' . $customer_name . '</td>
                <td>' . $customer_email . '</td>
                <td>' . esc_html($status_label) . '</td>';
        
        // Show resend button if status is pending
        if ($request->status == 0) {
            echo '<td><button class="resend-email-btn" data-customer-id="' . esc_attr($request->customer_id) . '">Resend Email</button></td>';
        } else {
            echo '<td>-</td>';
        }
        
        echo '</tr>';
    }

    echo '</tbody></table></div>';
} else {
    echo '<p>No requests found.</p>';
}
