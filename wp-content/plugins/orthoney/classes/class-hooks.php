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
        add_action('current_screen', array($this, 'add_dynamic_menu_items'));
        add_shortcode( 'confirm_link', array($this,'orthoney_confirm_link_shortcode') );

        add_filter('woocommerce_email', array($this,'register_custom_email_placeholders_with_user_id'));
        // add_filter('woocommerce_email_enabled_customer_new_account', array($this,'disable_new_account_email_for_affiliate'), 10, 2);
    }
    
    /**
     * Disable "New Account" email for users with the 'yith_affiliate' role
     */
    public function disable_new_account_email_for_affiliate($enabled, $user) {
        if (!$user || is_wp_error($user)) {
            return $enabled;
        }

        if (is_numeric($user)) {
            $user = get_user_by('id', $user);
        }

        if ($user instanceof WP_User && in_array('yith_affiliate', (array) $user->roles)) {
            return false; // Disable email
        }

        return $enabled;
    }

    /**
     * Register custom email placeholders in WooCommerce emails
     */
    public function register_custom_email_placeholders_with_user_id($email) {

        // Replace placeholders in subject and heading
        add_filter('woocommerce_email_format_string', function ($string) use ($email) {
            $replacements = $this->get_custom_placeholders($email);
            return strtr($string, $replacements);
        }, 10, 1);

        // Replace placeholders in email body content
        add_filter('woocommerce_mail_content', function ($content) use ($email) {
            $replacements = $this->get_custom_placeholders($email);
            return strtr($content, $replacements);
        }, 10, 1);

        return $email;
    }

    /**
     * Helper function to define placeholder replacements
     */
    public function get_custom_placeholders($email) {
        global $wpdb;
        $admin_email   = get_option('admin_email');
        $support_email = get_option('admin_email'); // Modify if needed
        $site_title    = get_bloginfo('name');

        $user_id = null;

        // Try to get the user ID from the email object
        if (is_object($email) && method_exists($email, 'get_order')) {
            $order = $email->get_order();
            if ($order && is_a($order, 'WC_Order')) {
                $user_id = $order->get_user_id();
            }
        }

        // Default token
        $affiliate_token = 'N/A';
        $organization_name = '';
        $yith_first_name= '';
        $yith_last_name= '';
        // Try to get affiliate token from user meta
        if ($user_id) {
            $organization_name = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true)?: '';
            $yith_first_name = get_user_meta($user_id, '_yith_wcaf_first_name', true)?: '';
            $yith_last_name = get_user_meta($user_id, '_yith_wcaf_last_name', true)?: '';

            $table_name = $wpdb->prefix . 'yith_wcaf_affiliates';
            $affiliate_token = $wpdb->get_var(
                $wpdb->prepare("SELECT token FROM $table_name WHERE user_id = %d", $user_id)
            );

            if (empty($affiliate_token)) {
                $affiliate_token = 'N/A';
            }
        }

        return [
            '{admin_email}'     => $admin_email,
            '{support_email}'   => $support_email,
            '{site_title}'      => $site_title,
            '{affiliate_token}' => $affiliate_token,
            '{organization_name}' => $organization_name,
            '{yith_first_name}' => $yith_first_name,
            '{yith_last_name}' => $yith_last_name,
        ];
    }



    public function orthoney_confirm_link_shortcode( $atts ) {
        ob_start();
        $atts = shortcode_atts(
            array(
                'title'  => __( 'Click me' ),
                'href'   => '#',
                'target' => '_self',
                'class'   => '',
            ),
            $atts,
            'confirm_link'
        );

        // Sanitize
        $title  = esc_html( $atts['title'] );
        $href   = esc_url( $atts['href'] );
        $target = esc_attr( $atts['target'] );
        $class = esc_attr( $atts['class'] );
        
        $loggedin   = is_user_logged_in() ? 1 : 0;

        // Output anchor. The JS looks for .confirmation_link
        return sprintf(
        '<a class="confirmation_link '.$class.'" href="%s" target="%s" data-loginstatus="%d">%s</a>',
            $href,
            $target,
            $loggedin,
            $title
        );
        return ob_get_clean();
    }

    /**
     * Add Dynamic Menu Item callback
     */
    public function add_dynamic_menu_items($screen) {
        // Check if we are in the admin area
        if (is_admin()) {
            $screen = get_current_screen();
    
            // Check if we're on the dashboard screen
            if (isset($screen->id) && $screen->id === 'dashboard') {
                $menus = [
                    'sales-rep-menu' => [ // Menu slug
                        'id' => 21, // Menu ID
                        'items' => [
                            'Dashboard' => SALES_REPRESENTATIVE_DASHBOARD_LINK,
                            'Assigned Customers' => SALES_REPRESENTATIVE_DASHBOARD_LINK . 'manage-customers/',
                            'Assigned Organizations' => SALES_REPRESENTATIVE_DASHBOARD_LINK . 'manage-organizations/',
                            'My Profile' => CUSTOMER_DASHBOARD_LINK . 'edit-account/',
                        ]
                    ],
                    'organization-menu' => [ // Menu slug
                        'id' => 20, // Menu ID
                        'items' => [
                            'Dashboard' => ORGANIZATION_DASHBOARD_LINK,
                            'Orders' => ORGANIZATION_DASHBOARD_LINK . 'orders-list/',
                            'Change Organizations Admin' => ORGANIZATION_DASHBOARD_LINK . 'change-admin/',
                            'Linked Customers' => ORGANIZATION_DASHBOARD_LINK . 'link-customer/',
                            'Organization Users' => ORGANIZATION_DASHBOARD_LINK . 'users-list/',
                            'Organization Profile' => ORGANIZATION_DASHBOARD_LINK . 'my-profile/',
                            'My Profile' => CUSTOMER_DASHBOARD_LINK . 'edit-account/',
                        ]
                    ],
                    'customer-menu' => [ // Menu slug
                        'id' => 19, // Menu ID
                        'items' => [
                            'Dashboard' => CUSTOMER_DASHBOARD_LINK,
                            'Orders' => CUSTOMER_DASHBOARD_LINK . 'orders/',
                            'Incomplete Orders' => CUSTOMER_DASHBOARD_LINK . 'incomplete-order/',
                            'Failed Recipients' => CUSTOMER_DASHBOARD_LINK . 'failed-recipients/',
                            'Recipient Lists' => CUSTOMER_DASHBOARD_LINK . 'groups/',
                            'Associated Organizations' => CUSTOMER_DASHBOARD_LINK . 'organizations/',
                            'My Profile' => CUSTOMER_DASHBOARD_LINK . 'edit-account/',
                        ]
                    ],
                    'admin-menu' => [ // Menu slug
                        'id' => 22, // Menu ID
                        'items' => [
                            'Dashboard' => ADMINISTRATOR_DASHBOARD_LINK,
                            'Orders' => ADMINISTRATOR_DASHBOARD_LINK . 'orders/',
                            'Manage Customers' => ADMINISTRATOR_DASHBOARD_LINK . 'customers/',
                            'Manage Organizations' => ADMINISTRATOR_DASHBOARD_LINK . 'organizations/',
                            'Manage Sales Representatives' => ADMINISTRATOR_DASHBOARD_LINK . 'sales-representatives/',
                            'My Profile' => CUSTOMER_DASHBOARD_LINK . 'edit-account/',
                            
                        ]
                    ],
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