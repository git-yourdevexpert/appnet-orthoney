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