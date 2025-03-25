<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$select_customer = get_user_meta($user_id, 'select_customer', true);
$choose_customer = get_user_meta($user_id, 'choose_customer', true);

if ($select_customer === 'all') {
    // Get only users with solely the 'customer' role
    $args = [
        'role'    => 'customer',
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ];

    $customers = array_filter(get_users($args), function ($user) {
        return count($user->roles) === 1 && in_array('customer', $user->roles);
    });

} elseif ($select_customer === 'choose_customer' && !empty($choose_customer)) {
    // Get specific customers with solely the 'customer' role
    $args = [
        'include' => $choose_customer,
    ];

    $customers = array_filter(get_users($args), function ($user) {
        return count($user->roles) === 1 && in_array('customer', $user->roles);
    });

} else {
    echo '<div class="no-customers">No customers selected.</div>';
    return;
}
if (!empty($customers)) { ?>
    <div class="customer-list-wrapper custom-table">
        <h2 class="heading-title">Customer List</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Login</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer) {?>
                <tr>
                    <td><?php echo esc_html($customer->display_name); ?></td>
                    <td><?php echo esc_html($customer->user_email); ?></td>
                    <td><button class="customer-login-btn w-btn us-btn-style_1"  data-user-id="<?php echo esc_attr($customer->ID); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('switch_to_user_' . $customer->ID)); ?>"> Login </button></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php
} else {
    echo '<div class="no-customers">No matching customers found.</div>';
}
?>
