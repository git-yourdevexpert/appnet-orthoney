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
    }

    /**
     * Register custom My Account endpoints and Load custom templates for My Account endpoints
     */
    public function custom_customer_endpoints() {
        $endpoints = [
            'order-details',
            'incomplete-order',
            'order-recipients-data',
            'recipients-list',
            'groups',
            'group-recipients-list',
            'affiliates'
        ];
        
        foreach ($endpoints as $endpoint) {
            add_rewrite_endpoint($endpoint, EP_ROOT | EP_PAGES);
        }

        // Load custom templates for My Account endpoints
        $base_path = OH_PLUGIN_DIR_PATH."templates/customer/customer-dashboard/";
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

    /**
     * Modify Dashboard menu item URL to /customer-dashboard
     */
    public function custom_my_account_menu_item_urls($items) {
        if (isset($items['dashboard'])) {
            $items['dashboard'] =  __('Dashboard', OH_DOMAIN);
        }
      
        $items = [
            'dashboard'        => __('Dashboard', OH_DOMAIN),
            'incomplete-order' => __('Incomplete Order', OH_DOMAIN),
            'orders'           => __('Orders', OH_DOMAIN),
            'groups'           => __('Groups', OH_DOMAIN),
            'affiliates'       => __('Organizations', OH_DOMAIN),
            'edit-account'     => __('My Profile', OH_DOMAIN),
            'edit-address'     => __('Edit Address', OH_DOMAIN),
        ];
        
        return $items;
    }

    /**
     * Override WooCommerce My Account dashboard template
     */
    public function custom_woocommerce_myaccount_template($template, $template_name, $template_path) {
        if ($template_name === 'myaccount/dashboard.php' && get_query_var('pagename') === 'customer-dashboard' && !is_wc_endpoint_url()) {
            $custom_template =  OH_PLUGIN_DIR_PATH.'/templates/customer/customer-dashboard/dashboard.php';
            
            return file_exists($custom_template) ? $custom_template : $template;
        }
        return $template;
    }
}

new OAM_CUSTOM();
