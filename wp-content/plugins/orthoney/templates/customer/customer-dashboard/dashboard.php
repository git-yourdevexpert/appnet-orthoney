<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
echo '<h2>Welcome to the Customer Dashboard</h2>';

if (class_exists('user_switching') && method_exists('user_switching', 'get_old_user')) {
    $old_user = user_switching::get_old_user();
    if ($old_user) {
        $redirect_to = urlencode(home_url('/sales-representative-dashboard/manage-customer/'));
        $switch_back_url = user_switching::switch_back_url($old_user) . '&redirect_to=' . $redirect_to;

        echo '<a href="' . esc_url($switch_back_url) . '" class="switch-back-btn w-btn us-btn-style_1">Switch Back to ' . esc_html($old_user->display_name) . '</a>';
    }
}

$title = 'Title';
$content = 'Customer dashboard.';
$icon = 'path/to/icon.png';
$url = '#';

echo OAM_COMMON_Custom::info_block($title, $content, $icon, $url);

echo do_shortcode('[recent_incomplete_orders limit="3"]');

echo do_shortcode('[recent_orders limit="2"]');