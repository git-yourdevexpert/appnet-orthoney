<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_ADMINISTRATOR_CUSTOM {
    /**
     * Constructor to hook into Customer template loading.
     */
    public function __construct() {
         add_action('init', array($this, 'administrator_dashboard_handler'));
    }
    
    /**
     * administrator callback
     */
    public function administrator_dashboard_handler() {

        $parsedUrl = ADMINISTRATOR_DASHBOARD_LINK;
        $newdUrl = str_replace(home_url(), '' ,$parsedUrl);
        $slug = trim($newdUrl, '/');
        
        if (!empty($slug)) {
            $administrator_dashboard_id = get_page_by_path($slug);
        
            if ($administrator_dashboard_id) {
                add_rewrite_rule($slug.'/([^/]+)/?$', 'index.php?pagename='.$slug.'&administrator=$matches[1]', 'top');
                add_rewrite_endpoint('administrator', EP_PAGES);
            }
        }
    }
}

// Initialize the class
new OAM_ADMINISTRATOR_CUSTOM();