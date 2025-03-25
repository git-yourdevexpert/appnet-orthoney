<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('user_switching') && method_exists('user_switching', 'get_old_user')) {
    $old_user = user_switching::get_old_user();
    if ($old_user) {
        $redirect_to = urlencode(home_url('/sales-representative-dashboard/manage-organization/'));
        $switch_back_url = user_switching::switch_back_url($old_user) . '&redirect_to=' . $redirect_to;

        echo '<a href="' . esc_url($switch_back_url) . '" class="switch-back-btn w-btn us-btn-style_1">Switch Back to ' . esc_html($old_user->display_name) . '</a>';
    }
}


$details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);


echo OAM_AFFILIATE_Helper::affiliate_details($affiliate_id, $details);
echo OAM_AFFILIATE_Helper::affiliate_order_list($details, 5);