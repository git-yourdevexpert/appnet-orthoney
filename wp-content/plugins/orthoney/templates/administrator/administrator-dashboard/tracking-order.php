<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$redirect_back_user_id = get_transient('redirect_back_user_' . $current_user_id);

if ($redirect_back_user_id) {
    $nonce = wp_create_nonce('auto_login_' . $redirect_back_user_id);
    $back_url = home_url('/?action=auto_login&user_id=' . $redirect_back_user_id . '&nonce=' . $nonce . '&redirect_to=' . urlencode(SALES_REPRESENTATIVE_DASHBOARD_LINK));
    echo '<a href="' . esc_url($back_url) . '" class="button">Back to Previous User</a>';
}
?>
<div class="order-block-wrap">
    <div class="order-process-dashboard">
        <div class="dashboard-block">
            <h3 class="block-title">Tracking Order Report</h3>
            <div class="block-row">
                <div class="tracking_order_form">
                    <form id="tracking-order-upload-form" enctype="multipart/form-data">
                        <div class="file-upload field-block">
                            <label>
                            <span class="title-block">
                                File Upload: <small>(Accept: .csv)</small>
                            </span>
                            </label>
                            <input type="file" accept=".csv" id="fileInput" name="csv_file" required>
                        </div>
                        <br>
                        <button type="submit" class="w-btn us-btn-style_2">Upload File</button>
                    </form>
                </div>
                <p><strong>Note:</strong> After manually uploading a file, please click the “Run” option in the table below to process it right away. <br>If you do not run it manually, the file will be processed automatically during the next scheduled update, which happens every 3 hours.</p>
            </div>
        </div>
    </div>
</div>
<br>

<?php 
global $wpdb;
 
 $table    = $wpdb->prefix . 'oh_tracking_order';
$tracking_orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY id DESC"));
$upload_dir = WP_CONTENT_URL . '/uploads/fulfillment-reports/tracking-orders/';
if(!empty($tracking_orders)) {
?>
<div class="affiliate-dashboard order-process-block">
    <div class="heading-title">
        <h3 class="block-title">Tracking Order File Status</h3>
        
    </div>
    <!-- Search and filter options -->
    <div class="orthoney-datatable-warraper">
        <div id="" class="orthoney-datatable-warraper table-with-search-block">
            <table id="" class="display " style="width:100%">
                <thead>
                <tr>
                    <th>File Name</th>
                    <th>File Time</th>
                    <th>Update Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($tracking_orders as $order) {

                    $run_button = strtolower($order->status) == 'pending' ? '<button data-fileid="' . esc_attr($order->id) . '" data-filename="' . esc_attr($order->updated_file_name) . '" class="tracking_order_import_button w-btn us-btn-style_2">Run</button>' : '';
                    $reasons = $order->reasons != '' ? '<br> ('.$order->reasons.')' : '';
                    echo '<tr>';
                    echo '<td>' . esc_html($order->file_name) . '</td>';
                    echo '<td>' . esc_html($order->file_time) . '</td>';
                    echo '<td>' . esc_html(ucwords(strtolower($order->uploaded_type))) . '</td>';
                    echo '<td>' . esc_html(ucwords(strtolower($order->status))) .''.$reasons.'</td>';
                    echo '<td>
                    '.$run_button.'
                    <a href="' . esc_url($upload_dir.$order->updated_file_name) . '" class="button-icon-underline"><img src="' . esc_url(OH_PLUGIN_DIR_URL . 'assets/image/download.png') . '" alt="Download">Download File</a>
                    </td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
            </table>
        </div>
    </div>
</div>
<?php
} 
?>