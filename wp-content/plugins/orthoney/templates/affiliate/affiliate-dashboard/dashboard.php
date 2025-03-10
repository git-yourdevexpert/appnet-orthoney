<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);


echo OAM_AFFILIATE_Helper::affiliate_details($affiliate_id, $details);
echo OAM_AFFILIATE_Helper::affiliate_order_list($details, 5);