<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

echo do_shortcode('[recent_customers limit="3"]');
echo do_shortcode('[recent_organizations limit="1"]');