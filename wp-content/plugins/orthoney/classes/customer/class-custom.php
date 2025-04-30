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
        add_filter('woocommerce_account_menu_items', array($this, 'custom_my_account_menu_item_urls'), 99);
        add_filter('woocommerce_locate_template', array($this, 'custom_woocommerce_myaccount_template'), 10, 3);
        add_filter('query_vars', array($this, 'custom_query_vars')); // Register query vars
        // add_action('init', array($this, 'flush_rewrite_rules_if_needed')); // Ensure rewrite rules are flushed when needed

        // Ensure rewrite rules are flushed when activating the plugin
        register_activation_hook(__FILE__, array($this, 'flush_rewrite_rules_on_activation'));
    }

    /**
     * Register custom My Account endpoints and Load custom templates for My Account endpoints
     */
    public function custom_customer_endpoints() {
        $endpoints = [
            'order-details',
            'incomplete-order',
            'failed-recipients',
            'failed-recipients-details', // Ensure this is separate
            'order-recipients-data',
            'recipients-list',
            'groups',
            'groups-details',
            'organizations'
        ];
        
        foreach ($endpoints as $endpoint) {
            add_rewrite_endpoint($endpoint, EP_ROOT | EP_PAGES);
        }

        
        $parsedUrl = CUSTOMER_DASHBOARD_LINK;
        $newdUrl = str_replace(home_url(), '' ,$parsedUrl);
        $slug = trim($newdUrl, '/');
        
        // Updated rewrite rule to work with customer-dashboard slug
        if (!empty($slug)) {
            add_rewrite_rule(
                $slug.'/failed-recipients/details/([0-9]+)/?$', 
                'index.php?pagename='.$slug.'&failed-recipients-details=$matches[1]', 
                'top'
            );

            add_rewrite_rule(
                $slug.'/groups/details/([0-9a-z_]+)/?$', 
                'index.php?pagename='.$slug.'&groups-details=$matches[1]', 
                'top'
            );

            add_rewrite_rule(
                $slug.'/order-details/([0-9a-z_]+)/?$', 
                'index.php?pagename='.$slug.'&order-details=$matches[1]', 
                'top'
            );
            // Load custom templates for My Account endpoints
            $base_path = OH_PLUGIN_DIR_PATH . "templates/customer/customer-dashboard/";
            foreach ($endpoints as $endpoint) {
                add_action("woocommerce_account_{$endpoint}_endpoint", function () use ($base_path, $endpoint) {
                    $template = $base_path . "{$endpoint}.php";
                    if (file_exists($template)) {
                        include $template;
                    } else {
                        echo "<p>Template file not found: {$endpoint}.php</p>";
                    }
                });
            }
        }

        
    }

    /**
     * Modify My Account menu items
     */
    public function custom_my_account_menu_item_urls($items) {
        $items = [
            'dashboard'         => __('Dashboard', OH_DOMAIN),
            'incomplete-order'  => __('Incomplete Order', OH_DOMAIN),
            'failed-recipients' => __('Failed Recipients', OH_DOMAIN),
            'orders'            => __('Orders', OH_DOMAIN),
            'groups'            => __('Recipient Lists', OH_DOMAIN),
            'organizations'     => __('Organizations', OH_DOMAIN),
            'edit-account'      => __('My Profile', OH_DOMAIN),
            'edit-address'      => __('Edit Address', OH_DOMAIN),
        ];
        
        return $items;
    }

    /**
     * Override WooCommerce My Account dashboard template
     */
    public function custom_woocommerce_myaccount_template($template, $template_name, $template_path) {
        if ($template_name === 'myaccount/dashboard.php' && !is_wc_endpoint_url()) {
            $custom_template = OH_PLUGIN_DIR_PATH . '/templates/customer/customer-dashboard/dashboard.php';
            return file_exists($custom_template) ? $custom_template : $template;
        }else{
            $custom_template = OH_PLUGIN_DIR_PATH . '/templates/woocommerce/'.$template_name;
            return file_exists($custom_template) ? $custom_template : $template;
        }
    
        return $template;
    }

    /**
     * Add custom query vars
     */
    public function custom_query_vars($vars) {
        $vars[] = 'failed-recipients-details';
        return $vars;
    }

    /**
     * Flush rewrite rules on activation
     */
    public function flush_rewrite_rules_on_activation() {
        flush_rewrite_rules();
    }

    /**
     * Flush rewrite rules only when necessary
     */
    public function flush_rewrite_rules_if_needed() {
        if (get_option('oam_flush_rewrite_rules') != '1') {
            flush_rewrite_rules();
            update_option('oam_flush_rewrite_rules', '1');
        }
    }
}

// Initialize the class
new OAM_CUSTOM();