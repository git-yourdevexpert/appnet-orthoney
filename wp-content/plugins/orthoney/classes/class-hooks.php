<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_Hooks {
    /**
     * Constructor to hook into WooCommerce template loading.
     */
    public function __construct() {
        // add_filter('woocommerce_locate_template', array($this, 'custom_plugin_woocommerce_template'), 10, 3);
        add_action('init', array($this,'grant_switch_users_capability'));
        add_action('after_setup_theme', array($this,'add_dynamic_menu_items'));
    }

    /**
     * Add Dynamic Menu Item callback
     */
    public function add_dynamic_menu_items() {
        $menus = [
            'sales-rep-menu' => [ // Menu slug
                'id' => 21, // Menu ID
                'items' => [
                    'Dashboard' => SALES_REPRESENTATIVE_DASHBOARD_LINK,
                    'Manage Customer' => SALES_REPRESENTATIVE_DASHBOARD_LINK . 'manage-customer/',
                    'Manage Organization' => SALES_REPRESENTATIVE_DASHBOARD_LINK . 'manage-organization/',
                    'My Profile' => SALES_REPRESENTATIVE_DASHBOARD_LINK . 'my-profile/',
                ]
            ],
            'organization-menu' => [ // Menu slug
                'id' => 20, // Menu ID
                'items' => [
                    'Dashboard' => ORGANIZATION_DASHBOARD_LINK,
                    'Orders' => ORGANIZATION_DASHBOARD_LINK . 'order-list/',
                    'Change Admin' => ORGANIZATION_DASHBOARD_LINK . 'change-admin/',
                    'Linked Customers' => ORGANIZATION_DASHBOARD_LINK . 'link-customer/',
                    'Organization Users' => ORGANIZATION_DASHBOARD_LINK . 'user-list/',
                    'My Profile' => ORGANIZATION_DASHBOARD_LINK . 'my-profile/',
                ]
            ],
            'customer-menu' => [ // Menu slug
                'id' => 19, // Menu ID
                'items' => [
                    'Dashboard' => CUSTOMER_DASHBOARD_LINK,
                    'Orders' => CUSTOMER_DASHBOARD_LINK . 'orders/',
                    'Incomplete Order' => CUSTOMER_DASHBOARD_LINK . 'incomplete-order/',
                    'Failed Recipients from Prv. Orders' => CUSTOMER_DASHBOARD_LINK . 'failed-recipients/',
                    'Recipients List' => CUSTOMER_DASHBOARD_LINK . 'groups/',
                    'Associated Organizations' => CUSTOMER_DASHBOARD_LINK . 'organizations/',
                    'My Profile' => CUSTOMER_DASHBOARD_LINK . 'edit-account/',
                   
                ]
            ]
        ];

        foreach ($menus as $menu_slug => $menu_data) {
            $menu_id = $menu_data['id'];
            $menu_exists = wp_get_nav_menu_object($menu_id);
            if (!$menu_exists) {
                continue; // Skip if menu does not exist
            }

            // Get existing menu items for comparison
            $existing_items = wp_get_nav_menu_items($menu_id);
            $existing_titles = [];

            if (!empty($existing_items)) {
                foreach ($existing_items as $item) {
                    $existing_titles[$item->title] = [
                        'id' => $item->ID, 
                        'url' => untrailingslashit($item->url)
                    ];
                }
            }

            // Add new items or update URL if title exists
            foreach ($menu_data['items'] as $title => $url) {
                $normalized_url = untrailingslashit(esc_url($url));
                $url_slug = sanitize_title(basename($normalized_url)); // Extracts slug from URL
                $custom_class = "{$menu_slug}_{$url_slug}"; // Format: menuslug_urlslug

                if (isset($existing_titles[$title])) {
                    // Title exists, check if URL needs updating
                    if ($existing_titles[$title]['url'] !== $normalized_url) {
                        wp_update_nav_menu_item($menu_id, $existing_titles[$title]['id'], [
                            'menu-item-title' => esc_html($title),
                            'menu-item-url' => esc_url($url),
                            'menu-item-status' => 'publish',
                            'menu-item-classes' => $custom_class, // Add custom class
                        ]);
                    }
                } else {
                    // Title does not exist, add new menu item
                    wp_update_nav_menu_item($menu_id, 0, [
                        'menu-item-title' => esc_html($title),
                        'menu-item-url' => esc_url($url),
                        'menu-item-status' => 'publish',
                        'menu-item-type' => 'custom',
                        'menu-item-parent-id' => 0,
                        'menu-item-classes' => $custom_class, // Add custom class
                    ]);
                }
            }
        }
    }
    /**
     * Template override callback
     */
    public function custom_plugin_woocommerce_template($template, $template_name, $template_path) {
        // Define the custom template path
        $custom_path = OH_PLUGIN_DIR_PATH . '../templates/woocommerce/';

        // Check if the custom template exists
        if (file_exists($custom_path . $template_name)) {
            $template = $custom_path . $template_name;
        }

        return $template;
    }

    public function grant_switch_users_capability() {
        // Grant the capability to yith_affiliate role
        $yith_affiliate_role = get_role('yith_affiliate');
        if ($yith_affiliate_role) {
            $yith_affiliate_role->add_cap('switch_users');
        }
    
        // Grant the capability to affiliate_team_member role
        $affiliate_team_member_role = get_role('affiliate_team_member');
        if ($affiliate_team_member_role) {
            $affiliate_team_member_role->add_cap('switch_users');
        }
    
        // Grant the capability to sales_representative role
        $sales_representative_role = get_role('sales_representative');
        if ($sales_representative_role) {
            $sales_representative_role->add_cap('switch_users');
        }
    }
    
    
}

new OAM_Hooks();