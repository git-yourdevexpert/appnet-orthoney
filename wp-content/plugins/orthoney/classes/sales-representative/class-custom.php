<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_SALES_REPRESENTATIVE_Custom {

    /**
     * Constructor to hook into Affiliate template loading.
     */
    public function __construct() {
        add_action('init', array($this, 'sales_representative_dashboard_handler'));
        add_action('init', array($this, 'handle_auto_login_request_handler'));
        add_filter('acf/load_field/name=choose_customer', array($this, 'populate_choose_customer'));
        add_filter('acf/load_field/name=choose_organization', [$this, 'populate_choose_organization']);

    }

    public function handle_auto_login_request_handler() {
        if (isset($_GET['action']) && $_GET['action'] === 'auto_login' && isset($_GET['user_id']) && isset($_GET['nonce'])) {
            $user_id = intval($_GET['user_id']);
            $nonce = sanitize_text_field($_GET['nonce']);
            $redirect_to = !empty($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : home_url('/customer-dashboard/');
            $redirect_back = isset($_GET['redirect_back']) ? intval($_GET['redirect_back']) : 0;
    
            if (!wp_verify_nonce($nonce, 'auto_login_' . $user_id)) {
                wp_die('Invalid request.');
            }
    
            // Log the user in
            wp_set_auth_cookie($user_id);
    
            // Save the redirect_back user ID in a session
            if ($redirect_back) {
                set_transient('redirect_back_user_' . $user_id, $redirect_back, 3600); // 1 hour
            }
    
            // Perform the redirect
            wp_redirect($redirect_to);
            exit;
        }
    }

     /**
     * Affiliate callback
     */
    public function sales_representative_dashboard_handler() {
        $affiliate_dashboard_id = get_page_by_path('sales-representative-dashboard');
    
        if ($affiliate_dashboard_id) {
            add_rewrite_rule('sales-representative-dashboard/([^/]+)/?$', 'index.php?pagename=sales-representative-dashboard&sales_representative_endpoint=$matches[1]', 'top');
            add_rewrite_endpoint('sales_representative_endpoint', EP_PAGES);
        }
    }

    /**
    * Populate choose_customer dropdown with users having only the 'customer' role
    */
    public function populate_choose_customer($field) {
        if ($field['name'] !== 'choose_customer') {
            return $field;
        }

        // Fetch all users with 'customer' role
        $args = [
            'role'    => 'customer',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ];
        $users = get_users($args);

        // Filter users with only the 'customer' role
        $field['choices'] = [];
        foreach ($users as $user) {
            $user_roles = $user->roles;
            if (count($user_roles) === 1 && in_array('customer', $user_roles)) {
                $field['choices'][$user->ID] = $user->display_name;
            }
        }

        // Check if no customers found
        if (empty($field['choices'])) {
            $field['choices'] = ['' => 'No Customers Found'];
        }

        return $field;
    }


    /**
     * Populate choose_organization dropdown with organization name, city, state, and code
     */
    public function populate_choose_organization($field) {
        if ($field['name'] !== 'choose_organization') return $field;
    
        $args = [
            'role'    => 'yith_affiliate',
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ];
        $users = get_users($args);
    
        $field['choices'] = [];
        foreach ($users as $user) {
            $organization_name = get_user_meta($user->ID, '_yith_wcaf_name_of_your_organization', true) ?: 'Unknown Organization';
            $city = get_user_meta($user->ID, '_yith_wcaf_city', true) ?: 'Unknown City';
            $state = get_user_meta($user->ID, '_yith_wcaf_state', true) ?: 'Unknown State';
            $code = get_user_meta($user->ID, '_yith_wcaf_zipcode', true) ?: 'N/A';
    
            $field['choices'][$user->ID] = "$organization_name ($city, $state, $code)";
        }
    
        return $field;
    }
    

    
}

new OAM_SALES_REPRESENTATIVE_Custom();
