<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_ADMINISTRATOR_AJAX {
    /**
     * Constructor to hook into Customer template loading.
     */
    public function __construct() {

         add_action('wp_ajax_orthoney_admin_get_customers_data', array($this,'orthoney_admin_get_customers_data_handler'));
         add_action('wp_ajax_orthoney_admin_get_organizations_data', array($this,'orthoney_admin_get_organizations_data_handler'));
         add_action('wp_ajax_orthoney_admin_get_sales_representative_data', array($this,'orthoney_admin_get_sales_representative_data_handler'));
    }
    
    /**
     * administrator callback
     */
    public function orthoney_admin_get_customers_data_handler() {

        // Security check if needed: check_ajax_referer('your-nonce')

        $all_users = get_users();
        $data = [];

        foreach ($all_users as $user) {
            if (count($user->roles) === 1 && in_array('customer', $user->roles)) {
                $data[] = [
                    'id' => $user->ID,
                    'name' => esc_html($user->display_name),
                    'email' => esc_html($user->user_email),
                    'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '">
                                    <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png">Login as Customer
                                </button>'
                ];
            }
        }

        wp_send_json([
            'data' => $data
        ]);
    }
    public function orthoney_admin_get_sales_representative_data_handler() {

        // Security check if needed: check_ajax_referer('your-nonce')

        $all_users = get_users();
        $data = [];

        foreach ($all_users as $user) {
            if (in_array('sales_representative', $user->roles)) {
                $data[] = [
                    'id' => $user->ID,
                    'name' => esc_html($user->display_name),
                    'email' => esc_html($user->user_email),
                    'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '">
                                    <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png">Login as Sales Representative
                                </button>'
                ];
            }
        }

        wp_send_json([
            'data' => $data
        ]);
    }
    public function orthoney_admin_get_organizations_data_handler() {
        global $wpdb;
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;

        $query = "SELECT a.user_id, u.display_name, u.user_email, a.token
            FROM {$yith_wcaf_affiliates_table} AS a
            LEFT JOIN {$wpdb->users} AS u ON a.user_id = u.ID
            WHERE a.enabled = 1 AND  a.banned = 0";

        $affiliateList = $wpdb->get_results($query);
        $data = [];

        foreach ($affiliateList as $user) {
            $data[] = [
                'id' => $user->user_id,
                'name' => esc_html($user->display_name),
                'token' => esc_html($user->token), 
               
                'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->user_id) . '">
                                <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png">Login as Organizations
                            </button>'
            ];
        }

        wp_send_json([
            'data' => $data
        ]);
    }


}

// Initialize the class
new OAM_ADMINISTRATOR_AJAX();