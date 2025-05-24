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

    // Step 1: Get all users with role 'yith_affiliate' (no filtering yet)
    $user_query = new WP_User_Query([
        'role'   => 'yith_affiliate',
        'fields' => ['ID', 'user_email'],
        'number' => -1,  // get all for manual filtering
    ]);
    $all_users = $user_query->get_results();

    // Step 2: Manual filtering by search term on email and meta fields
    if (!empty($search)) {
        $search_lc = strtolower($search);
        $filtered_users = array_filter($all_users, function($user) use ($search_lc) {
            $organization = strtolower(get_user_meta($user->ID, '_yith_wcaf_name_of_your_organization', true) ?: '');
            $city         = strtolower(get_user_meta($user->ID, '_yith_wcaf_city', true) ?: '');
            $state        = strtolower(get_user_meta($user->ID, '_yith_wcaf_state', true) ?: '');
            $code         = strtolower(get_user_meta($user->ID, '_orgCode', true) ?: '');
            $email        = strtolower($user->user_email);

            return (
                strpos($organization, $search_lc) !== false ||
                strpos($city, $search_lc) !== false ||
                strpos($state, $search_lc) !== false ||
                strpos($code, $search_lc) !== false ||
                strpos($email, $search_lc) !== false
            );
        });
    } else {
        $filtered_users = $all_users;
    }

    $filtered_user_ids = wp_list_pluck($filtered_users, 'ID');

    // Step 3: Filter only enabled users from your yith_wcaf_affiliates table
    $enabled_user_ids = [];
    if (!empty($filtered_user_ids)) {
        $placeholders = implode(',', array_fill(0, count($filtered_user_ids), '%d'));
        $sql = "
            SELECT user_id
            FROM {$wpdb->prefix}yith_wcaf_affiliates
            WHERE enabled = 1 AND user_id IN ($placeholders)
        ";
        $enabled_user_ids = $wpdb->get_col($wpdb->prepare($sql, ...$filtered_user_ids));
    }

    // Final filtered users enabled and matching search
    $final_users = array_filter($filtered_users, function($user) use ($enabled_user_ids) {
        return in_array($user->ID, $enabled_user_ids);
    });

    // Step 4: Pagination
    $recordsFiltered = count($final_users);
    $all_user_ids = wp_list_pluck($all_users, 'ID');

$enabled_all_user_ids = [];
if (!empty($all_user_ids)) {
    $placeholders_all = implode(',', array_fill(0, count($all_user_ids), '%d'));
    $sql_all = "
        SELECT user_id
        FROM {$wpdb->prefix}yith_wcaf_affiliates
        WHERE enabled = 1 AND user_id IN ($placeholders_all)
    ";
    $enabled_all_user_ids = $wpdb->get_col($wpdb->prepare($sql_all, ...$all_user_ids));
}

$recordsTotal = count($enabled_all_user_ids);

    $paged_users = array_slice(array_values($final_users), $start, $length);

    // Step 5: Prepare data for DataTables
    $data = [];
    foreach ($paged_users as $user) {
        $user_id = $user->ID;
        $organization = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
        $city = get_user_meta($user_id, '_yith_wcaf_city', true);
        $state = get_user_meta($user_id, '_yith_wcaf_state', true);
        $org_code = get_user_meta($user_id, '_orgCode', true);
        $login_link = wp_login_url() . '?user=' . urlencode($user->user_email);

        $data[] = [
            'code'         => $org_code,
            'organization' => $organization,
            'city'         => $city,
            'state'        => $state,
            'login'        => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user_id) . '" data-nonce="' . esc_attr($nonce) . '">
                                <img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login as Organization
                            </button>'
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