<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo '<h2>Welcome to the User List</h2>';

?>

<!-- Add User Button -->
<a href="#user-manage-popup" class="add-user us-btn-style_1" data-lity data-popup="#user-manage-popup">Add User</a>

<?php
// Get users with the 'affiliate_team_member' role
$affiliate_users = get_users(['role' => 'affiliate_team_member']);
echo OAM_AFFILIATE_Helper::manage_user_popup();
if(!empty($affiliate_users )){
?>

<!-- User Table -->
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>User Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>User Role</th>
            <th>Edit</th>
            <th>Delete</th>
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
                    <td><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo esc_html($phone); ?></td>
                    <td><?php echo esc_html($affiliate_type); ?></td>
                    <td><button class="edit-user-form-btn far fa-edit" data-popup="#user-manage-popup" data-userid="<?php echo esc_attr($user->ID); ?>"></button></td>
                    <td><button class="delete-user far fa-trash" data-userid="<?php echo esc_attr($user->ID); ?>"></button></td>
                </tr>
                <?php
            endforeach;
         endif; ?>
    </tbody>
</table>
<?php 
    }else{
        echo '<p>No affiliate team member found!</p>';
    }
?>