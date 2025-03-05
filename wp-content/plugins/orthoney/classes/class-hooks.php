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
        add_filter('woocommerce_locate_template', array($this, 'custom_plugin_woocommerce_template'), 10, 3);
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
}

new OAM_Hooks();