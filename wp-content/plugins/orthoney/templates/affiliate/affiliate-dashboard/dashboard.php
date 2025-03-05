<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    return '<p>Please <a href="' . esc_url(wp_login_url()) . '">log in</a> to view your affiliate dashboard.</p>';
}

$user_id = get_current_user_id();
$affiliate_status_check = OAM_AFFILIATE_Helper::affiliate_status_check($user_id);

$result = json_decode($affiliate_status_check, true);

if (!empty($result) && isset($result['success']) && $result['success']) {
    if(!empty($result['message'])|| !empty($result['reason'])){
        echo '<div class="main-message">';
        if (!empty($result['message'])) {
            echo 'Message: ' . esc_html($result['message']) . '<br>';
        }
        if (!empty($result['reason'])) {
            echo 'Reason: ' . esc_html($result['reason']);
        }
        echo '</div>';
    }
    

    // Append the profile button conditionally
    // TODO
    if (isset($result['success']) && $result['success'] == 1) {
        $output = '<div class="affiliate-dashboard">';
        $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/my-profile/')) . '">My Profile</a></div>';
        $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/user-list/')) . '">User List</a></div>';
        $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/order-list/')) . '">Order List</a></div>';
        $output .= '</div>';
        echo $output;
    }
}