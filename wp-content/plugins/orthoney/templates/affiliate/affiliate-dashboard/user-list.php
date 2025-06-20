<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

  $dashboard_link = ORGANIZATION_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';
// Get users with the 'affiliate_team_member' role
$affiliate_users = get_users([
    'role'       => 'affiliate_team_member',
    'meta_query' => [
        [
            'key'   => 'associated_affiliate_id',
            'value' => get_current_user_id(),
            'compare' => '='
        ]
    ]
]);
echo OAM_AFFILIATE_Helper::manage_user_popup();

?>

<!-- User Table -->
<div class="order-process-block">
<div class="heading-title">
    <h3 class="block-title">Team Member List</h3>
    <div>
    <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>

    <a href="#user-manage-popup" class="add-user w-btn us-btn-style_1" data-lity data-popup="#user-manage-popup">Add new user</a>
    </div>
</div>

<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>User Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>User Role</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        if (!empty($affiliate_users)) {
            foreach ($affiliate_users as $user) {
                $phone = get_user_meta($user->ID, 'billing_phone', true);
                $affiliate_type = get_field('user_field_type', 'user_' . $user->ID);

                $first_name = get_user_meta($user->ID, 'first_name', true);
                $last_name = get_user_meta($user->ID, 'last_name', true);

                // Fallback to YITH affiliate meta if empty
                if (empty($first_name)) {
                    $first_name = get_user_meta($user->ID, '_yith_wcaf_first_name', true);
                }
                if (empty($last_name)) {
                    $last_name = get_user_meta($user->ID, '_yith_wcaf_last_name', true);
                }
                $full_name = trim("$first_name $last_name");


                ?>
                <tr data-userid="<?php echo $user->ID?>">
                    <td><div class='thead-data'>User Name</div><?php echo esc_html($full_name); ?></td>
                    <td><div class='thead-data'>Email</div><?php echo esc_html($user->user_email); ?></td>
                    <td><div class='thead-data'>Phone Number</div><?php echo esc_html($phone); ?></td>
                    <td><div class='thead-data'>User Role</div><?php echo esc_html($affiliate_type); ?></td>
                    <td><div class='thead-data'>Action</div><button class="edit-user-form-btn far fa-edit" data-popup="#user-manage-popup" data-userid="<?php echo esc_attr($user->ID); ?>"></button><button class="delete-user far fa-trash" data-userid="<?php echo esc_attr($user->ID); ?>"></button></td>
                </tr>
                <?php
                }
            }else{
                ?>
                <tr><td colspan="5" class="no-available-msg">No Team member is not found!</td></tr>
                <?php
            } ?>
    </tbody>
</table>
</div>