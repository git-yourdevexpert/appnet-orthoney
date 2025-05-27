<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_SALES_REPRESENTATIVE_Ajax{

     /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_action('wp_ajax_update_sales_representative', array($this, 'update_sales_representative_handler'));
        add_action('wp_ajax_auto_login_request_to_sales_rep', array($this, 'auto_login_request_to_sales_rep_handler'));

        add_action('wp_ajax_get_affiliates_list', array($this, 'get_affiliates_list_ajax_handler'));
        add_action('wp_ajax_get_filtered_customers', array($this, 'orthoney_get_filtered_customers'));
    }

    
    public function orthoney_get_filtered_customers() {
        // check_ajax_referer('get_customers_nonce', 'nonce');

        global $wpdb;

        $user_id = get_current_user_id();
        $select_customer = get_user_meta($user_id, 'select_customer', true);
        $choose_customer = get_user_meta($user_id, 'choose_customer', true);

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');

        // Prepare "include" filter if needed
        $include_clause = '';
        $include_ids = [];
        if ($select_customer === 'choose_customer' && !empty($choose_customer)) {
            $choose_customer_int = array_map('intval', (array)$choose_customer);
            if (!empty($choose_customer_int)) {
                $include_ids = $choose_customer_int;
                $include_clause = 'AND u.ID IN (' . implode(',', $include_ids) . ')';
            }
        }

        // Clean search for SQL LIKE
        $like_search = '%' . $wpdb->esc_like($search) . '%';

        // Prepare SQL for counting total 'customer' users with possible inclusion filter (no search)
        $sql_total = "
            SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID AND um_role.meta_key = '{$wpdb->prefix}capabilities'
            WHERE um_role.meta_value = 'a:1:{s:8:\"customer\";b:1;}'
            {$include_clause}
        ";

        // Total count without search
        $total_count = $wpdb->get_var($sql_total);

        // Prepare SQL for filtered count with search
        $sql_filtered = "
            SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID AND um_role.meta_key = '{$wpdb->prefix}capabilities'
            LEFT JOIN {$wpdb->usermeta} um_first ON (um_first.user_id = u.ID AND um_first.meta_key = 'first_name')
            LEFT JOIN {$wpdb->usermeta} um_last ON (um_last.user_id = u.ID AND um_last.meta_key = 'last_name')
            WHERE um_role.meta_value = 'a:1:{s:8:\"customer\";b:1;}'
            AND (
                u.user_email LIKE %s
                OR um_first.meta_value LIKE %s
                OR um_last.meta_value LIKE %s
            )
            {$include_clause}
        ";

        $filtered_count = $wpdb->get_var($wpdb->prepare($sql_filtered, $like_search, $like_search, $like_search));

        // Prepare SQL to get user data with limit and search
        $sql_data = "
            SELECT u.ID, u.user_email, um_first.meta_value AS first_name, um_last.meta_value AS last_name
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID AND um_role.meta_key = '{$wpdb->prefix}capabilities'
            LEFT JOIN {$wpdb->usermeta} um_first ON (um_first.user_id = u.ID AND um_first.meta_key = 'first_name')
            LEFT JOIN {$wpdb->usermeta} um_last ON (um_last.user_id = u.ID AND um_last.meta_key = 'last_name')
            WHERE um_role.meta_value = 'a:1:{s:8:\"customer\";b:1;}'
            AND (
                u.user_email LIKE %s
                OR um_first.meta_value LIKE %s
                OR um_last.meta_value LIKE %s
            )
            {$include_clause}
            ORDER BY um_first.meta_value ASC, um_last.meta_value ASC
            LIMIT %d, %d
        ";

        $results = $wpdb->get_results($wpdb->prepare(
            $sql_data,
            $like_search,
            $like_search,
            $like_search,
            $start,
            $length
        ));

        // Prepare data for DataTables
        $data = [];
        foreach ($results as $user) {
            $nonce = wp_create_nonce('switch_to_user_' . $user->ID);
            $name = trim($user->first_name . ' ' . $user->last_name);

            $data[] = [
                'name' => esc_html($name ?: '—'),
                'email' => esc_html($user->user_email),
                'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '" data-nonce="' . esc_attr($nonce) . '">
                                <img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login as Customer
                            </button>',
            ];
        }

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => intval($total_count),
            'recordsFiltered' => intval($filtered_count),
            'data' => $data,
        ]);
    }
 public function get_affiliates_list_ajax_handler() {
        global $wpdb;

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $nonce  = wp_create_nonce('customer_login_nonce');

        // Step 1: Get all affiliate users from yith_wcaf_affiliates table
        $raw_users = $wpdb->get_results(" SELECT user_id, enabled, banned , token  FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE enabled = 1 AND banned = 0");

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

            $data[] = [
                'code'         => esc_html($meta['code']),
                'organization' => esc_html($meta['organization']),
                'city'         => esc_html($meta['city']),
                'state'        => esc_html($meta['state']),
                'email'        => esc_html($meta['email']),
                'status'       => esc_html($status),
                'login'        => ($status =='Accepted and enabled' ? '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user_id) . '" data-nonce="' . esc_attr($nonce) . '"><img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login As An Organization</button>' : "" )
            ];
        }

        wp_send_json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }    


    public function update_sales_representative_handler() {

        // Verify nonce for security
        check_ajax_referer('oam_nonce', 'security');
    
        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'message' => 'You must be logged in to update your profile.']);
            wp_die();
        }
    
        $user_id = get_current_user_id();
    
        // Validate and sanitize inputs
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $billing_phone = sanitize_text_field($_POST['billing_phone']);
        $email = sanitize_email($_POST['email']);
    
        // Ensure email is valid and not already taken
        if (!is_email($email)) {
            wp_send_json(['success' => false, 'message' => 'Invalid email address.']);
            wp_die();
        }
    
        if (email_exists($email) && email_exists($email) != $user_id) {
            wp_send_json(['success' => false, 'message' => 'This email is already taken.']);
            wp_die();
        }
    
        // Update user profile data
        $update_data = [
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'user_email' => $email,
        ];
    
        $user_update = wp_update_user($update_data);
    
        if (is_wp_error($user_update)) {
            wp_send_json(['success' => false, 'message' => 'Error updating profile.']);
            wp_die();
        }
    
        // Update user meta data (phone number)
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, '_yith_wcaf_phone_number', $billing_phone);
    
        wp_send_json(['success' => true, 'message' => 'Sales Representative Profile updated successfully!']);
        wp_die();
    }

    /*
    * Auto Login Request to sales Representative to Customer or Organization
    */
    public function auto_login_request_to_sales_rep_handler() {
        check_ajax_referer('oam_nonce', 'security');

        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'message' => 'You must be logged in.']);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $current_user = wp_get_current_user();
        $target = get_userdata($user_id);

        if (!$target) {
            wp_send_json(['success' => false, 'message' => 'Could not switch users.']);
        }

        if (!$user_id || !get_user_by('ID', $user_id)) {
            wp_send_json(['success' => false, 'message' => 'Invalid user ID.']);
        }

        // Ensure the current user has permission to switch users.
        // if (!current_user_can('switch_users')) {
        //     wp_send_json(['success' => false, 'message' => 'You do not have permission to switch users.']);
        // }

        $user = get_user_by('ID', $user_id);

        if (!$user) {
            wp_send_json(['success' => false, 'message' => 'User not found.']);
        }

        $sessions = WP_Session_Tokens::get_instance($target->ID);
        $all_sessions = $sessions->get_all();

        // Filter out sessions with the [switched_from_id] key
        $filtered_sessions = array();
        foreach ($all_sessions as $token => $session) {
            if (!isset($session['switched_from_id'])) {
                $filtered_sessions[$token] = $session;
            }
        }

        // Update the sessions with the filtered list
        if (count($filtered_sessions) !== count($all_sessions)) {
            $sessions->destroy_all();
            
            // Re-add the filtered sessions
            foreach ($filtered_sessions as $token => $session_data) {
                $sessions->update($token, $session_data);
            }
        }
    
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($target);

        
        // Generate switch URL using User Switching plugin
        $login_url = User_Switching::switch_to_url($user)."&redirect_to=".OAM_COMMON_Custom::redirect_user_based_on_role($target->roles);

        wp_send_json(['success' => true, 'url' => esc_url_raw(html_entity_decode($login_url))]);
    }
    
}


new OAM_SALES_REPRESENTATIVE_Ajax();