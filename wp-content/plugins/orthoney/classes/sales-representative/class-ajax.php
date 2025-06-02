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

        if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_affiliates_list_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }

        $user_id = get_current_user_id();
        $select_organization = get_user_meta($user_id, 'select_organization', true);
        $choose_organization = get_user_meta($user_id, 'choose_organization', true);
        $assigned_affiliate_ids = array_filter([$select_organization, $choose_organization]);

        // If no assigned affiliates, return nothing
        if (empty($assigned_affiliate_ids)) {
            wp_send_json([
                'draw' => intval($_POST['draw']),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $order  = $_POST['order'][0] ?? ['column' => 0, 'dir' => 'asc'];

        $columns = [
            'meta_code.meta_value', // code
            'u.user_email',
            'org.meta_value',
            'city.meta_value',
            'state.meta_value',
            'status.meta_value'
        ];
        $order_column_index = intval($order['column']);
        $order_dir = strtoupper($order['dir']) === 'DESC' ? 'DESC' : 'ASC';
        $order_by = $columns[$order_column_index] ?? 'meta_code.meta_value';

        $like = '%' . $wpdb->esc_like($search) . '%';
        $ids_placeholders = implode(',', array_fill(0, count($assigned_affiliate_ids), '%d'));

        // Total count
        $sql_total = "
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role 
                ON um_role.user_id = u.ID
            WHERE um_role.meta_key = '{$wpdb->prefix}capabilities'
            AND um_role.meta_value LIKE '%yith_affiliate%'
            AND u.ID IN ($ids_placeholders)
        ";
        $records_total = $wpdb->get_var($wpdb->prepare($sql_total, ...$assigned_affiliate_ids));

        // Filtered count
        $sql_filtered = "
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role 
                ON um_role.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} meta_code ON meta_code.user_id = u.ID AND meta_code.meta_key = 'code'
            LEFT JOIN {$wpdb->usermeta} org ON org.user_id = u.ID AND org.meta_key = 'organization'
            LEFT JOIN {$wpdb->usermeta} city ON city.user_id = u.ID AND city.meta_key = 'city'
            LEFT JOIN {$wpdb->usermeta} state ON state.user_id = u.ID AND state.meta_key = 'state'
            LEFT JOIN {$wpdb->usermeta} status ON status.user_id = u.ID AND status.meta_key = 'status'
            WHERE um_role.meta_key = '{$wpdb->prefix}capabilities'
            AND um_role.meta_value LIKE '%yith_affiliate%'
            AND u.ID IN ($ids_placeholders)
            AND (
                meta_code.meta_value LIKE %s
                OR u.user_email LIKE %s
                OR org.meta_value LIKE %s
                OR city.meta_value LIKE %s
                OR state.meta_value LIKE %s
                OR status.meta_value LIKE %s
            )
        ";
        $records_filtered = $wpdb->get_var($wpdb->prepare(
            $sql_filtered,
            ...$assigned_affiliate_ids, $like, $like, $like, $like, $like, $like
        ));

        // Data
        $sql = "
            SELECT u.ID, u.user_email,
                meta_code.meta_value AS code,
                org.meta_value AS organization,
                city.meta_value AS city,
                state.meta_value AS state,
                status.meta_value AS status
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} meta_code ON meta_code.user_id = u.ID AND meta_code.meta_key = 'code'
            LEFT JOIN {$wpdb->usermeta} org ON org.user_id = u.ID AND org.meta_key = 'organization'
            LEFT JOIN {$wpdb->usermeta} city ON city.user_id = u.ID AND city.meta_key = 'city'
            LEFT JOIN {$wpdb->usermeta} state ON state.user_id = u.ID AND state.meta_key = 'state'
            LEFT JOIN {$wpdb->usermeta} status ON status.user_id = u.ID AND status.meta_key = 'status'
            WHERE um_role.meta_key = '{$wpdb->prefix}capabilities'
            AND um_role.meta_value LIKE '%yith_affiliate%'
            AND u.ID IN ($ids_placeholders)
            AND (
                meta_code.meta_value LIKE %s
                OR u.user_email LIKE %s
                OR org.meta_value LIKE %s
                OR city.meta_value LIKE %s
                OR state.meta_value LIKE %s
                OR status.meta_value LIKE %s
            )
            ORDER BY {$order_by} {$order_dir}
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results($wpdb->prepare(
            $sql,
            ...$assigned_affiliate_ids, $like, $like, $like, $like, $like, $like,
            $length, $start
        ));

        $data = [];
        foreach ($results as $row) {
            $nonce = wp_create_nonce('switch_to_user_' . $row->ID);
            $data[] = [
                'code'         => esc_html($row->code),
                'email'        => esc_html($row->user_email),
                'organization' => esc_html($row->organization),
                'city'         => esc_html($row->city),
                'state'        => esc_html($row->state),
                'status'       => esc_html($row->status),
                'login'        => '<button class="affiliate-login-btn" data-user-id="' . esc_attr($row->ID) . '" data-nonce="' . esc_attr($nonce) . '">Login</button>'
            ];
        }

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $data,
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