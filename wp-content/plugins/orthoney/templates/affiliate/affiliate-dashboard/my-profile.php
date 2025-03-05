<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<p>Please log in to view your profile.</p>';
    return;
}

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Fetch user meta fields
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);

// TODO 
$billing_phone = get_user_meta($user_id, 'billing_phone', true);
$billing_email = get_user_meta($user_id, 'billing_email', true);
// TODO 

?>

<div class="affiliate-profile">
    <h2>My Profile</h2>

    <form id="affiliate-profile-form">
        <div class="profile-fields">
            <label>First Name</label>
            <input type="text" id="first_name" value="<?php echo esc_attr($first_name); ?>">

            <label>Last Name</label>
            <input type="text" id="last_name" value="<?php echo esc_attr($last_name); ?>">

            <label>Phone Number</label>
            <input type="text" id="billing_phone" value="<?php echo esc_attr($billing_phone); ?>">

            <label>Email ID</label>
            <input type="email" id="billing_email" value="<?php echo esc_attr($billing_email); ?>">

        </div>

        <button type="button" id="save-profile">Save</button>
    </form>
    <div id="profile-message"></div>
</div>