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
$current_url = home_url( $_SERVER['REQUEST_URI'] );

$dashboard_link = CUSTOMER_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';
?>
<div class="groups-block order-process-block orthoney-datatable-warraper table-with-search-block">
    <div class="heading-title">
        <h3 class="block-title">All Recipient Lists</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>

    <table id="group-recipient-table" class="display">
        <thead>
            <tr>
                <th>Sr No</th>
                <th>List Name</th>
                <th>Number of Recipients</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>
