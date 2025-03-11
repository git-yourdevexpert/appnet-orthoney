<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo '<h2>Welcome to the Order List</h2>';

$details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);

echo OAM_AFFILIATE_Helper::affiliate_order_list($details, 5);