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

        add_action('wp_ajax_get_affiliates_list_ajax', array($this, 'get_affiliates_list_ajax_handler'));
        add_action('wp_ajax_get_filtered_customers', array($this, 'orthoney_get_filtered_customers'));
    }

    
    public function orthoney_get_filtered_customers() {
        global $wpdb;

        $user_id         = get_current_user_id();
        $select_customer = get_user_meta($user_id, 'select_customer', true);
        $choose_customer = get_user_meta($user_id, 'choose_customer', true); // no trailing space

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');

        $include_clause = '';
        if ($select_customer === 'choose_customer' && !empty($choose_customer)) {
            // make sure $choose_customer is array of integers
            $choose_ids = array_map('intval', (array) $choose_customer);
            if (!empty($choose_ids)) {
                $include_clause = 'AND u.ID IN (' . implode(',', $choose_ids) . ')';
            }
        }
        // If $select_customer == 'value', no include clause → show all customers

        $like_search = '%' . $wpdb->esc_like($search) . '%';

        // Total customers count (with optional filter)
        $sql_total = "
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID
            WHERE um_role.meta_key = '{$wpdb->prefix}capabilities'
            AND um_role.meta_value LIKE '%customer%'
            {$include_clause}
        ";
        $total_count = $wpdb->get_var($sql_total);

        // Filtered customers count with search + filter
        $sql_filtered = "
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um_first ON (um_first.user_id = u.ID AND um_first.meta_key = 'first_name')
            LEFT JOIN {$wpdb->usermeta} um_last ON (um_last.user_id = u.ID AND um_last.meta_key = 'last_name')
            WHERE um_role.meta_key = '{$wpdb->prefix}capabilities'
            AND um_role.meta_value LIKE '%customer%'
            AND (
                u.user_email LIKE %s
                OR um_first.meta_value LIKE %s
                OR um_last.meta_value LIKE %s
            )
            {$include_clause}
        ";
        $filtered_count = $wpdb->get_var($wpdb->prepare($sql_filtered, $like_search, $like_search, $like_search));

        // Fetch paginated customer data with search + filter
        $sql_data = "
            SELECT u.ID, u.user_email, u.display_name, um_first.meta_value AS first_name, um_last.meta_value AS last_name
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um_first ON (um_first.user_id = u.ID AND um_first.meta_key = 'first_name')
            LEFT JOIN {$wpdb->usermeta} um_last ON (um_last.user_id = u.ID AND um_last.meta_key = 'last_name')
            WHERE um_role.meta_key = '{$wpdb->prefix}capabilities'
            AND um_role.meta_value LIKE '%customer%'
            AND (
                u.user_email LIKE %s
                OR um_first.meta_value LIKE %s
                OR um_last.meta_value LIKE %s
            )
            {$include_clause}
            ORDER BY um_first.meta_value ASC
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql_data, $like_search, $like_search, $like_search, $length, $start));

        $data = [];
        foreach ($results as $user) {
            $nonce = wp_create_nonce('switch_to_user_' . $user->ID);
            $name  = trim($user->first_name . ' ' . $user->last_name);

            $data[] = [
                'name'   => esc_html($name ?: $user->display_name),
                'email'  => esc_html($user->user_email),
                'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '" data-nonce="' . esc_attr($nonce) . '">
                                <img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login as Customer
                            </button>',
            ];
        }

        wp_send_json([
            'draw'            => $draw,
            'recordsTotal'    => intval($total_count),
            'recordsFiltered' => intval($filtered_count),
            'data'            => $data,
        ]);
    }

      public function get_affiliates_list_ajax_handler() {
        global $wpdb;

        $user_id = get_current_user_id();
        $select_organization = get_user_meta($user_id, 'select_organization', true);
        $choose_organization = get_user_meta($user_id, 'choose_organization', true);

        // Build include clause based on user meta
        $include_clause = '';
        if ($select_organization === 'choose_organization' && !empty($choose_organization)) {
            $choose_ids = array_map('intval', (array)$choose_organization);
            if (!empty($choose_ids)) {
                $include_clause = ' AND a.user_id IN (' . implode(',', $choose_ids) . ') ';
            }
        }

        // Pagination and search parameters
        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');

        // Handle ordering safely
        $order = $_POST['order'][0] ?? null;
        $order_column_index = 0;
        $order_dir = 'ASC';
        if (is_array($order)) {
            $order_column_index = intval($order['column'] ?? 0);
            $order_dir = strtoupper($order['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        }

        // Define columns for ordering
        $columns = ['organization_name', 'city', 'state', 'a.token'];
        $order_by = $columns[$order_column_index] ?? 'organization_name';

        // Prepare search filter SQL
        $search_filter = '';
        $search_like = '%' . $wpdb->esc_like($search) . '%';
        if (!empty($search)) {
            $search_filter = $wpdb->prepare(" AND (
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_name_of_your_organization' THEN um.meta_value END) LIKE %s
                OR MAX(CASE WHEN um.meta_key = '_yith_wcaf_city' THEN um.meta_value END) LIKE %s
                OR MAX(CASE WHEN um.meta_key = '_yith_wcaf_state' THEN um.meta_value END) LIKE %s
                OR a.token LIKE %s
            ) ", $search_like, $search_like, $search_like, $search_like);
        }

        // Total records count (without search)
        $sql_total = "
            SELECT COUNT(DISTINCT a.user_id)
            FROM {$wpdb->prefix}yith_wcaf_affiliates AS a
            WHERE a.enabled = '1' AND a.banned = '0'
            $include_clause
        ";
        $records_total = $wpdb->get_var($sql_total);

        // Filtered records count (with search)
        $sql_filtered = "
            SELECT COUNT(*) FROM (
                SELECT a.user_id
                FROM {$wpdb->prefix}yith_wcaf_affiliates AS a
                LEFT JOIN {$wpdb->usermeta} um ON a.user_id = um.user_id
                WHERE a.enabled = '1' AND a.banned = '0'
                $include_clause
                GROUP BY a.user_id, a.token
                HAVING 1=1
                $search_filter
            ) AS subquery
        ";
        $records_filtered = $wpdb->get_var($sql_filtered);

        // Data query
        $sql_data = "
            SELECT 
                a.user_id,
                a.token,
                u.user_email,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_name_of_your_organization' THEN um.meta_value END) AS organization_name,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_city' THEN um.meta_value END) AS city,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_state' THEN um.meta_value END) AS state
            FROM {$wpdb->prefix}yith_wcaf_affiliates AS a
            INNER JOIN {$wpdb->users} u ON u.ID = a.user_id
            LEFT JOIN {$wpdb->usermeta} um ON a.user_id = um.user_id
            WHERE a.enabled = '1' AND a.banned = '0'
            $include_clause
            GROUP BY a.user_id, a.token
            HAVING 1=1
            $search_filter
            ORDER BY $order_by $order_dir
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql_data, $length, $start));

        $data = [];
        foreach ($results as $row) {
            $nonce = wp_create_nonce('switch_to_user_' . $row->user_id);
            $data[] = [
                'code' => esc_html($row->token ?? ''),
                'email' => esc_html($row->user_email ?? ''),
                'organization' => esc_html($row->organization_name ?? ''),
                'city' => esc_html($row->city ?? ''),
                'state' => esc_html($row->state ?? ''),
                'status' => esc_html($row->status ?? ''),
                'login' => '<button class="affiliate-login-btn" data-user-id="' . esc_attr($row->user_id) . '" data-nonce="' . esc_attr($nonce) . '">Login</button>',
            ];
        }

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => intval($records_total),
            'recordsFiltered' => intval($records_filtered),
            'data' => $data,
        ]);
        wp_die();
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