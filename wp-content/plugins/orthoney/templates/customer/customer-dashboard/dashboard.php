<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
echo '<h2>Welcome to the Customer Dashboard</h2>';
$current_user_id = get_current_user_id();
$redirect_back_user_id = get_transient('redirect_back_user_' . $current_user_id);

if ($redirect_back_user_id) {
    $nonce = wp_create_nonce('auto_login_' . $redirect_back_user_id);
    $back_url = home_url('/?action=auto_login&user_id=' . $redirect_back_user_id . '&nonce=' . $nonce . '&redirect_to=' . urlencode('/sales-representative-dashboard/'));
    echo '<a href="' . esc_url($back_url) . '" class="button">Back to Previous User</a>';
}

$title = 'Title';
$content = 'Customer dashboard.';
$icon = 'path/to/icon.png';
$url = '#';

echo OAM_COMMON_Custom::info_block($title, $content, $icon, $url);

echo do_shortcode('[recent_incomplete_orders limit="3"]');

echo do_shortcode('[recent_orders limit="2"]');