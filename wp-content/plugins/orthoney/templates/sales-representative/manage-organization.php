<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$select_organization = get_user_meta($user_id, 'select_organization', true);
$choose_organization = get_user_meta($user_id, 'choose_organization', true);

if ($select_organization === 'all') {
    // Get all users with the 'yith_affiliate' role
    $args = [
        'role'    => 'yith_affiliate',
        'orderby' => 'display_name',
        'order'   => 'ASC',
    ];
    $organizations = get_users($args);
} elseif ($select_organization === 'choose_organization' && !empty($choose_organization)) {
    // Get specific organizations
    $organizations = get_users(['include' => $choose_organization]);
} else {
    echo '<div class="no-organizations">No organizations selected.</div>';
    return;
}

if (!empty($organizations)) { ?>
    <div class="organization-list-wrapper custom-table">
        <h2 class="heading-title">Organization List</h2>
        <table>
            <thead>
                <tr>
                    <th>Organization Name</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Code</th>
                    <th>Login</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($organizations as $organization) {
                    $organization_name = get_user_meta($organization->ID, '_yith_wcaf_name_of_your_organization', true);
                    $city = get_user_meta($organization->ID, '_yith_wcaf_city', true);
                    $state = get_user_meta($organization->ID, '_yith_wcaf_state', true);
                    $code = get_user_meta($organization->ID, '_yith_wcaf_zipcode', true);
                    ?>
                <tr>
                    <td><?php echo esc_html($organization_name ?: 'N/A'); ?></td>
                    <td><?php echo esc_html($city ?: 'N/A'); ?></td>
                    <td><?php echo esc_html($state ?: 'N/A'); ?></td>
                    <td><?php echo esc_html($code ?: 'N/A'); ?></td>
                    <td><button class="organization-login-btn w-btn us-btn-style_1" data-user-id="<?php echo esc_attr($organization->ID); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('switch_to_user_' . $organization->ID)); ?>">Login</button></td>

                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php
} else {
    echo '<div class="no-organizations">No matching organizations found.</div>';
}
?>
