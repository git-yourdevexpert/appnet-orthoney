<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$user_id = get_current_user_id();
$user_data = get_userdata($user_id);

// Fetch user meta data
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$phone_number = get_user_meta($user_id, '_yith_wcaf_phone_number', true);
$email = $user_data->user_email;
?>

<div class="order-process-block">
    
    <div class="heading-title"><h3 class="block-title">My Profile</h3></div>
    <form id="sales-rep-profile-form" class="site-form">
        <div class="grid-two-col">
            <div class="form-row gfield--width-half">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required data-error-message="Please enter a First Name.">
                <span class="error-message"></span>
            </div>
            <div class="form-row gfield--width-half">
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required data-error-message="Please enter a Last Name.">
                <span class="error-message"></span>
            </div>
            <div class="form-row gfield--width-half">
                    <label for="billing_phone">Phone Number</label>
                    <input type="text" name="billing_phone" id="billing_phone" class="phone-input" value="<?php echo esc_attr($phone_number); ?>" required data-error-message="Please enter a Phone Number.">
                    <span class="error-message"></span>
                </div>
            <div class="form-row gfield--width-half">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" required data-error-message="Please enter a Email.">
                <span class="error-message"></span>
            </div>
        </div>
        <button type="button" class="add-user us-btn-style_1" id="sales-rep-save-profile">Update Profile</button>
    </form>
    <div id="sales-rep-profile-update"></div>
</div>