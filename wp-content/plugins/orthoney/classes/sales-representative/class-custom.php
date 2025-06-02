<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_SALES_REPRESENTATIVE_Custom {

    public function __construct() {
        add_action('init', array($this, 'sales_representative_dashboard_handler'));
        add_action('wp_head', array($this, 'remove_user_switching_footer_button'));

        add_action('acf/load_field/name=choose_customer', array($this, 'populate_choose_customer_acf_handler'));
        add_action('acf/load_field/name=choose_organization', array($this, 'populate_choose_organization_acf_handler'));

        add_action('orthoney_cron_cache_customers', array($this, 'orthoney_cache_customer_data'));
        add_action('orthoney_cron_cache_organization', array($this, 'orthoney_cache_organization_data'));

        // Schedule the cron only once
        if (!wp_next_scheduled('orthoney_cron_cache_customers')) {
            wp_schedule_event(time(), 'daily', 'orthoney_cron_cache_customers');
        }

        if (!wp_next_scheduled('orthoney_cron_cache_organization')) {
            wp_schedule_event(time(), 'daily', 'orthoney_cron_cache_organization');
        }
    }

    /**
     * Removes the User Switching footer button from frontend.
     */
    public function remove_user_switching_footer_button() {
        ob_start(function ($buffer) {
            return preg_replace('/<p id="user_switching_switch_on".*?<\/p>/s', '', $buffer);
        });
    }

    /**
     * Adds a custom rewrite endpoint for the sales representative dashboard.
     */
    public function sales_representative_dashboard_handler() {
        $parsed_url = defined('SALES_REPRESENTATIVE_DASHBOARD_LINK') ? SALES_REPRESENTATIVE_DASHBOARD_LINK : '';
        $relative_url = str_replace(home_url(), '', $parsed_url);
        $slug = trim($relative_url, '/');

        if (!empty($slug)) {
            $page = get_page_by_path($slug);
            if ($page) {
                add_rewrite_rule("{$slug}/([^/]+)/?$", 'index.php?pagename=' . $slug . '&sales_representative_endpoint=$matches[1]', 'top');
                add_rewrite_endpoint('sales_representative_endpoint', EP_PAGES);
            }
        }
    }

    /**
     * Populates the ACF select field with cached customer data.
     */
    public function populate_choose_customer_acf_handler($field) {
        if ($field['name'] !== 'choose_customer') {
            return $field;
        }

        $cached_customers = get_transient('orthoney_cached_customers');

        if (!empty($cached_customers)) {
            foreach ($cached_customers as $user_id => $label) {
                $field['choices'][$user_id] = $label;
            }
        }

        if (empty($field['choices'])) {
            $field['choices'] = ['' => 'No Customers Found'];
        }

        return $field;
    }

    /**
     * Caches customer data in a transient to improve ACF load performance.
     */
    public function orthoney_cache_customer_data() {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT u.ID, u.display_name, u.user_email,
                MAX(CASE WHEN um.meta_key = 'first_name' THEN um.meta_value END) AS first_name,
                MAX(CASE WHEN um.meta_key = 'last_name' THEN um.meta_value END) AS last_name,
                MAX(CASE WHEN um.meta_key = '{$wpdb->prefix}capabilities' THEN um.meta_value END) AS capabilities
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            GROUP BY u.ID, u.display_name, u.user_email
            HAVING capabilities LIKE %s
            ORDER BY display_name ASC
        ", '%"customer"%');

        $users = $wpdb->get_results($query);

        $cached = [];

        foreach ($users as $user) {
            $first_name = $user->first_name;
            $last_name  = $user->last_name;
            $full_name  = trim($first_name . ' ' . $last_name);
            if (empty($full_name)) {
                $full_name = $user->display_name;
            }

            $cached[$user->ID] = $full_name . ' [' . $user->user_email . ']';
        }

        set_transient('orthoney_cached_customers', $cached, DAY_IN_SECONDS);
    }

    /**
     * Populates the ACF select field with cached organization data.
     */
    public function populate_choose_organization_acf_handler($field) {
        if ($field['name'] !== 'choose_organization') {
            return $field;
        }

        $cached_organization = get_transient('orthoney_cached_organization');

        if (!empty($cached_organization)) {
            foreach ($cached_organization as $user_id => $label) {
                $field['choices'][$user_id] = $label;
            }
        }

        if (empty($field['choices'])) {
            $field['choices'] = ['' => 'No Organization Found'];
        }

        return $field;
    }

    /**
     * Caches organization data in a transient to improve ACF load performance.
     */
    public function orthoney_cache_organization_data() {
        global $wpdb;

        $query = "
            SELECT 
                a.user_id,
                a.token,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_name_of_your_organization' THEN um.meta_value END) AS organization_name,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_city' THEN um.meta_value END) AS city,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_state' THEN um.meta_value END) AS state
            FROM {$wpdb->prefix}yith_wcaf_affiliates AS a
            LEFT JOIN {$wpdb->usermeta} AS um ON a.user_id = um.user_id
            WHERE a.enabled = '1' AND a.banned = '0'
            GROUP BY a.user_id, a.token
        ";

        $users = $wpdb->get_results($query);

        $cached = [];

        foreach ($users as $user) {
            $org_name = $user->organization_name ?? '-';
            $city     = $user->city ?? '';
            $state    = $user->state ?? '';

            $city_state = trim(implode(', ', array_filter([$city, $state])));

            $label = "[{$user->token}] {$org_name}";
            if ($city_state) {
                $label .= ", {$city_state}";
            }

            $cached[$user->user_id] = $label;
        }

        set_transient('orthoney_cached_organization', $cached, DAY_IN_SECONDS);
    }
}

new OAM_SALES_REPRESENTATIVE_Custom();
