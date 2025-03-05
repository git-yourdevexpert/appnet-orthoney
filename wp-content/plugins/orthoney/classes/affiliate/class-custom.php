<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_CUSTOM {
    /**
     * Constructor to hook into Affiliate template loading.
     */
    public function __construct() {
        add_action('init', array($this, 'custom_affiliate_endpoints'));
    }

    /**
     * Affiliate callback
     */
    public function custom_affiliate_endpoints() {
        $affiliate_dashboard_id = get_page_by_path('affiliate-dashboard-2');
    
        if ($affiliate_dashboard_id) {
            add_rewrite_rule('affiliate-dashboard-2/([^/]+)/?$', 'index.php?pagename=affiliate-dashboard-2&affiliate_endpoint=$matches[1]', 'top');
            add_rewrite_endpoint('affiliate_endpoint', EP_PAGES);
        }
    }
}

new OAM_AFFILIATE_CUSTOM();