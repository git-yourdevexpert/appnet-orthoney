<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo '<h2>Change Admin</h2>';

//TODO: Verification functionality pending
global $wpdb;
$get_current_user_id = get_current_user_id();
$users = $wpdb->get_results("
    SELECT u.ID, u.display_name 
    FROM wp_users u
    JOIN wp_usermeta m2 ON u.ID = m2.user_id AND m2.meta_key = 'wp_capabilities'
    WHERE m2.meta_value LIKE '%affiliate_team_member%'
    AND u.ID != $get_current_user_id
");

echo '<select id="userDropdown">';
    echo '<option value="">Select a User</option>';
    foreach ($users as $user) {
        echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>'; 
    }
echo '</select>';

echo '<button id="changeRoleBtn" class="us-btn-style_1">Change Role & Logout</button>';

?>