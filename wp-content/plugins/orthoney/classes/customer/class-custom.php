<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_CUSTOM {
    /**
     * Constructor to hook into Customer template loading.
     */
    public function __construct() {
        add_action('init', array($this, 'custom_customer_endpoints'));
    }

    /**
     * Customer callback
     */
    public function custom_customer_endpoints() {
        
        $customer_dashboard_id = get_page_by_path('customer-dashboard');
    
        if ($customer_dashboard_id) {
            add_rewrite_rule('customer-dashboard/([^/]+)/?$', 'index.php?pagename=customer-dashboard&customer_endpoint=$matches[1]', 'top');
            add_rewrite_endpoint('customer_endpoint', EP_PAGES);
        }
    }
}

new OAM_CUSTOM();
