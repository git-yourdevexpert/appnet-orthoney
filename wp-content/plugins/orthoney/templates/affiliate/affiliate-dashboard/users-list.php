<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$dashboard_link = ORGANIZATION_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';

echo $current_user_id = get_current_user_id();
$affiliate_id = $current_user_id;
echo $associated_id = get_user_meta($current_user_id, 'associated_affiliate_id', true);
if (!empty($associated_id)) {
    $affiliate_id = $associated_id;
}


// Get users with the 'affiliate_team_member' role
$affiliate_users = get_users([
    'role'       => 'affiliate_team_member',
    'meta_query' => [
        [
            'key'   => 'associated_affiliate_id',
            'value' => $affiliate_id,
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
        <a href="#user-manage-popup" class="add-user w-btn us-btn-style_1 addnewaffiliateteammember" data-lity data-popup="#user-manage-popup">Add new user</a>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
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
        $roles_type = [
        "primary-contact"      => "Primary Contact",
        "co-chair"             => "Co-Chair",
        "alternative-contact"  => "Alternative Contact"
    ];
        if (!empty($affiliate_users)) {
            foreach ($affiliate_users as $user) {
                $phone = get_user_meta($user->ID, 'phone', true);
                $affiliate_type = get_field('user_field_type', 'user_' . $user->ID);
                ?>
                <tr data-userid="<?php echo $user->ID?>">
                    <td><div class='thead-data'>User Name</div><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></td>
                    <td><div class='thead-data'>Email</div><?php echo esc_html($user->user_email); ?></td>
                    <td><div class='thead-data'>Phone Number</div><?php echo esc_html($phone); ?></td>
                    <td><div class='thead-data'>User Role</div><?php echo esc_html($roles_type[$affiliate_type]); ?></td>
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