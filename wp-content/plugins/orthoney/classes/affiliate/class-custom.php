<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_Custom {
    /**
     * Constructor to hook into Affiliate template loading.
     */
    public function __construct() {
        add_action('init', array($this, 'affiliate_dashboard_handler'));
    }

    /**
     * Affiliate callback
     */
    public function affiliate_dashboard_handler() {
        $affiliate_dashboard_id = get_page_by_path('affiliate-dashboard');
    
        if ($affiliate_dashboard_id) {
            add_rewrite_rule('affiliate-dashboard/([^/]+)/?$', 'index.php?pagename=affiliate-dashboard&affiliate_endpoint=$matches[1]', 'top');
            add_rewrite_endpoint('affiliate_endpoint', EP_PAGES);
        }
    }
}

new OAM_AFFILIATE_Custom();