<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Fetch user meta fields
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$user = get_userdata($user_id);
$email = $user->user_email;

//TODO
$billing_phone = get_user_meta($user_id, 'billing_phone', true);
//TODO
?>

<div class="affiliate-profile" id="update-affiliate-form">
    <h2>My Profile</h2>
    <form id="affiliate-profile-form">
        <div class="profile-fields">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($first_name); ?>" required data-error-message="Please enter a First Name.">
            <span class="error-message"></span>

            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($last_name); ?>" required data-error-message="Please enter a Last Name.">
            <span class="error-message"></span>

            <label for="billing_phone">Phone Number</label>
            <input type="text" name="billing_phone" id="billing_phone" class="phone-input" value="<?php echo esc_attr($billing_phone); ?>" required data-error-message="Please enter a Phone Number.">
            <span class="error-message"></span>

            <label for="email">Email ID</label>
            <input type="email" name="email" id="email" value="<?php echo esc_attr($email); ?>" readonly>

        </div>
        <button type="button" class="add-user us-btn-style_1" id="save-profile">Save</button>
    </form>

    <div id="profile-message"></div>
</div>
