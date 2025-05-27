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

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $nonce  = wp_create_nonce('customer_login_nonce');

        // Step 1: Get all affiliate users from yith_wcaf_affiliates table
        $raw_users = $wpdb->get_results(" SELECT user_id, enabled, banned , token  FROM {$wpdb->prefix}yith_wcaf_affiliates");

        if (empty($raw_users)) {
            wp_send_json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        // Step 2: Prepare user meta and status map
        $user_ids = wp_list_pluck($raw_users, 'user_id');

        $user_meta_cache = [];
        $user_status_map = [];

        foreach ($raw_users as $row) {
            $user_id = intval($row->user_id);
            $enabled = intval($row->enabled);
            $banned  = intval($row->banned);

            // Determine status
            if ($banned === 1) {
                $status = 'Banned';
            } elseif ($enabled === 1) {
                $status = 'Accepted and enabled';
            } elseif ($enabled === -1) {
                $status = 'Rejected';
            } else {
                $status = 'New request';
            }

            $user_status_map[$user_id] = [
                'enabled' => $enabled,
                'banned'  => $banned,
                'label'   => $status,
            ];

            $user_obj = get_userdata($user_id);

            $city  = get_user_meta($user_id, '_yith_wcaf_city', true) ?: get_user_meta($user_id, 'billing_city', true) ?: '';
            $state = get_user_meta($user_id, '_yith_wcaf_state', true) ?: get_user_meta($user_id, 'billing_state', true) ?: '';

            $user_meta_cache[$user_id] = [
                'organization' => get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true),
                'city'         => $city,
                'state'        => $state,
                'code'         => $row->token,
                'email'        => $user_obj ? $user_obj->user_email : '',
            ];
        }

        // Step 3: Search filter
        $filtered_user_ids = array_filter($user_ids, function ($user_id) use ($search, $user_meta_cache, $user_status_map) {
            if (empty($search)) return true;

            $search_lc = strtolower($search);
            $meta   = $user_meta_cache[$user_id];
            $status = strtolower($user_status_map[$user_id]['label']);

            return (
                strpos(strtolower($meta['organization']), $search_lc) !== false ||
                strpos(strtolower($meta['city']), $search_lc)         !== false ||
                strpos(strtolower($meta['state']), $search_lc)        !== false ||
                strpos(strtolower($meta['code']), $search_lc)         !== false ||
                strpos(strtolower($meta['email']), $search_lc)        !== false ||
                strpos($status, $search_lc)                           !== false
            );
        });

        $recordsTotal    = count($user_ids);
        $recordsFiltered = count($filtered_user_ids);

        // Step 4: Paginate
        $paged_user_ids = array_slice(array_values($filtered_user_ids), $start, $length);

        // Step 5: Prepare DataTables data
        $data = [];
        foreach ($paged_user_ids as $user_id) {
            $meta   = $user_meta_cache[$user_id];
            $status = $user_status_map[$user_id]['label'];

            $admin_url = admin_url().'/admin.php?page=yith_wcaf_panel&affiliate_id='.$user->ID.'&tab=affiliates';

            $data[] = [
                'code'         => esc_html($meta['code']),
                'organization' => esc_html($meta['organization']),
                'city'         => esc_html($meta['city']),
                'state'        => esc_html($meta['state']),
                'email'        => esc_html($meta['email']),
                'status'       => esc_html($status),
                'login'        => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user_id) . '" data-nonce="' . esc_attr($nonce) . '"><img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login As An Organization</button><a href="'. $admin_url.'" class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->user_id) . '">
                                    <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit Organizations Profile
                                </a>'
            ];
        }

        wp_send_json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);


    }

}

// Initialize the class
new OAM_ADMINISTRATOR_AJAX();