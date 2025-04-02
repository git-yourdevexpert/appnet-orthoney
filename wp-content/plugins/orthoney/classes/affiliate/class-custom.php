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
        add_filter('yith_wcaf_registration_form_affiliate_pending_text', array($this, 'custom_affiliate_pending_message'));
    }

    /**
     * Affiliate callback
     */
    public function affiliate_dashboard_handler() {

        $parsedUrl = parse_url(ORGANIZATION_DASHBOARD_LINK, PHP_URL_PATH);
        $slug = trim($parsedUrl, '/');

        $affiliate_dashboard_id = get_page_by_path($slug);
    
        if ($affiliate_dashboard_id) {
            add_rewrite_rule($slug.'/([^/]+)/?$', 'index.php?pagename='.$slug.'&affiliate_endpoint=$matches[1]', 'top');
            add_rewrite_endpoint('affiliate_endpoint', EP_PAGES);
        }
    }

    /**
     * Custom message for affiliate pending approval
     */
    public function custom_affiliate_pending_message($message) {
        return '<div class="affiliate-pending-message">
            <h2>Please Note:</h2>
            <p>An affiliate account will be created only upon admin approval.</p>
            <p>Once approved, the affiliate can log in to the site and will receive email notifications regarding the approval status.</p>
        </div>';
    }
}

new OAM_AFFILIATE_Custom();