<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo '<h2>Welcome to the User List</h2>';

$user_id = get_current_user_id();
$add_user_affiliate_form = OAM_AFFILIATE_Helper::add_user_affiliate_form();

?>

<!-- Add User Button -->
<div class="add-user w-btn us-btn-style_1">
    <a href="#add-user" data-lity>Add User</a>
</div>

<!-- Add User Modal -->
<div id="add-user" style="background:#fff" class="lity-hide">
    <div class="add-affiliate-team-user">
        <h1>Add User</h1>
        <?php echo $add_user_affiliate_form; ?>
    </div>
</div>

<!-- Edit User Modal -->
<div id="edit-user" style="background:#fff; padding: 20px; max-width: 400px; margin: auto;" class="lity-hide">
    <div class="add-affiliate-team-user">
        <h2>Edit User</h2>

        <label>Name:</label>
        <input type="text" id="edit-name" />

        <label>Email:</label>
        <input type="email" id="edit-email" />

        <label>Phone:</label>
        <input type="text" id="edit-phone" />

        <label>Type:</label>
        <select id="edit-role">
            <option value="primary-contact">Primary Contact</option>
            <option value="co-chair">Co-Chair</option>
            <option value="alternative-contact">Alternative Contact</option>
        </select>

        <button id="save-user">Update User</button>
    </div>
</div>


<?php
// Get users with the 'affiliate_team_member' role
$affiliate_users = get_users(['role' => 'affiliate_team_member']);
?>

<!-- User Table -->
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Sr. No</th>
            <th>User Name</th>
            <th>Email</th>
            <th>Phone Number</th>
            <th>User Role</th>
            <th>Edit</th>
            <th>Delete</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($affiliate_users)) :
            $sr_no = 1;
            foreach ($affiliate_users as $user) :
                $phone = get_user_meta($user->ID, 'billing_phone', true);
                $affiliate_type = get_field('field_67c830a35d448', 'user_' . $user->ID); // ACF field
        ?>
        <tr>
            <td><?php echo esc_html($sr_no++); ?></td>
            <td><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></td>
            <td><?php echo esc_html($user->user_email); ?></td>
            <td><?php echo esc_html($phone); ?></td>
            <td><?php echo esc_html($affiliate_type); ?></td>
            <td>
                <a href="#edit-user" class="edit-user" data-id="<?php echo esc_attr($user->ID); ?>"
                    data-name="<?php echo esc_attr($user->first_name . ' ' . $user->last_name); ?>"
                    data-email="<?php echo esc_attr($user->user_email); ?>" data-phone="<?php echo esc_attr($phone); ?>"
                    data-role="<?php echo esc_attr($affiliate_type); ?>" data-lity>
                    <i class="fas fa-edit"></i>
                </a>
            </td>
            <td><a href="#" class="delete-user"><span class="dashicons dashicons-trash"></span></a></td>
        </tr>
        <?php
            endforeach;
        else :
        ?>
        <tr>
            <td colspan="7">No users found.</td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<?php wp_nonce_field('update_affiliate_user_nonce', 'update-affiliate-user-nonce'); ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".edit-user").forEach(button => {
        button.addEventListener("click", function(event) {
            event.preventDefault(); // Prevent default anchor behavior

            // Get data attributes from clicked edit button
            const userId = this.getAttribute("data-id");
            const userName = this.getAttribute("data-name");
            const userEmail = this.getAttribute("data-email");
            const userPhone = this.getAttribute("data-phone");
            const userRole = this.getAttribute("data-role");

            // Populate the modal fields
            document.getElementById("edit-name").value = userName;
            document.getElementById("edit-email").value = userEmail;
            document.getElementById("edit-phone").value = userPhone;
            document.getElementById("edit-type").value = userRole;

            // Store user ID for reference if needed (hidden input can be used)
            let userIdInput = document.getElementById("edit-user-id");
            if (!userIdInput) {
                userIdInput = document.createElement("input");
                userIdInput.type = "hidden";
                userIdInput.id = "edit-user-id";
                document.querySelector(".add-affiliate-team-user").appendChild(userIdInput);
            }
            userIdInput.value = userId;
        });
    });
});
</script>