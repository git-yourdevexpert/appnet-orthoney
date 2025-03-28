<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<!-- Add User Button -->
<?php
// Get users with the 'affiliate_team_member' role
$affiliate_users = get_users(['role' => 'affiliate_team_member']);
echo OAM_AFFILIATE_Helper::manage_user_popup();
if(!empty($affiliate_users )){
?>

<!-- User Table -->
<div class="order-process-block">
<div class="heading-title">
    <h3 class="block-title">User List</h3>
    <a href="#user-manage-popup" class="add-user w-btn us-btn-style_1" data-lity data-popup="#user-manage-popup">Add new member</a>
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
        if (!empty($affiliate_users)) :
            foreach ($affiliate_users as $user) :
                $phone = get_user_meta($user->ID, 'billing_phone', true);
                $affiliate_type = get_field('field_67c830a35d448', 'user_' . $user->ID);
                ?>
                <tr data-userid="<?php echo $user->ID?>">
                    <td><div class='thead-data'>User Name</div><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></td>
                    <td><div class='thead-data'>Email</div><?php echo esc_html($user->user_email); ?></td>
                    <td><div class='thead-data'>Phone Number</div><?php echo esc_html($phone); ?></td>
                    <td><div class='thead-data'>User Role</div><?php echo esc_html($affiliate_type); ?></td>
                    <td><div class='thead-data'>Action</div><button class="edit-user-form-btn far fa-edit" data-popup="#user-manage-popup" data-userid="<?php echo esc_attr($user->ID); ?>"></button><button class="delete-user far fa-trash" data-userid="<?php echo esc_attr($user->ID); ?>"></button></td>
                </tr>
                <?php
            endforeach;
         endif; ?>
    </tbody>
</table>
</div>
<?php 
    }else{
        echo '<p>No affiliate team member found!</p>';
    }
?>