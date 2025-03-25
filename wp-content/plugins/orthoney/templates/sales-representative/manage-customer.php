<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$select_customer = get_user_meta($user_id, 'select_customer', true);
$choose_customer = get_user_meta($user_id, 'choose_customer', true);

if ($select_customer === 'all') {
    $args = [
        'role'    => 'customer',
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ];
    $customers = get_users($args);
} elseif ($select_customer === 'choose_customer' && !empty($choose_customer)) {
    $customers = get_users(['include' => $choose_customer]);
} else {
    echo '<p>No customers selected.</p>';
    return;
}

if (!empty($customers)) { ?>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Login</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer) {
                $nonce = wp_create_nonce('auto_login_' . $customer->ID);
                $login_url = home_url('/?action=auto_login&user_id=' . $customer->ID . '&nonce=' . $nonce . '&redirect_back=' . $user_id);
            ?>
            <tr>
                <td><?php echo esc_html($customer->display_name); ?></td>
                <td><?php echo esc_html($customer->user_email); ?></td>
                <td><a href="<?php echo esc_url($login_url); ?>" target="_blank">Login</a></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
<?php
} else {
    echo '<p>No matching customers found.</p>';
}