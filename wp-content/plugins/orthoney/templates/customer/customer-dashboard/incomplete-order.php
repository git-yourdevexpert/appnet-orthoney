<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);

if (!is_user_logged_in()) {
    $message = 'Please login to view your Customer dashboard.';
    $url = ur_get_login_url();
    $btn_name = 'Login';
    echo OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
    return;
}

if (!in_array('customer', $user_roles) && !in_array('administrator', $user_roles)) {
    $message = 'You do not have access to this page.';
    echo OAM_COMMON_Custom::message_design_block($message);
    return;
}

$dashboard_link = CUSTOMER_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';
?>

<div class="incomplete-order-block order-process-block orthoney-datatable-warraper">
    <div class="heading-title">
        <h3 class="block-title">All Incomplete Orders</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
    
    <table id="incomplete-order-table"  data-failed="0" class="display">
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Name</th>
                <th>Ordered By</th>
                <th>Current Step</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>