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
        

        // Fetch paginated customer data with search + filter
       $sql_data = "
            SELECT DISTINCT u.ID, u.user_email, u.display_name, um_first.meta_value AS first_name, um_last.meta_value AS last_name
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um_role ON um_role.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um_first ON (um_first.user_id = u.ID AND um_first.meta_key = 'first_name')
            LEFT JOIN {$wpdb->usermeta} um_last ON (um_last.user_id = u.ID AND um_last.meta_key = 'last_name')
            WHERE um_role.meta_key = '{$wpdb->prefix}capabilities'
            AND um_role.meta_value = 'a:1:{s:8:\"customer\";b:1;}'
            AND u.user_email != ''
            {$include_clause}
            ORDER BY um_first.meta_value ASC
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql_data));
  $affiliate_customer_linker = $wpdb->prefix . 'oh_affiliate_customer_linker';
                $affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';

        $data = [];
        foreach ($results as $user) {
            $nonce = wp_create_nonce('switch_to_user_' . $user->ID);
            $name  = trim($user->first_name . ' ' . $user->last_name);

             $full_name = trim(get_user_meta($user->ID, 'first_name', true) . ' ' . get_user_meta($user->ID, 'last_name', true));
             $customer = new WC_Customer($user->ID);

            
                $affiliates_ids = $wpdb->get_results($wpdb->prepare(
                    "SELECT affiliate_id FROM {$affiliate_customer_linker} WHERE customer_id = %d",
                    $user->ID
                ));

                // echo '<pre>';
                // print_r($wpdb);
                //  echo '</pre>';

                $oname_block = '';

                if (!empty($affiliates_ids)) {
                    foreach ($affiliates_ids as $affiliate) {
                        $affiliate_id = $affiliate->affiliate_id;

                        $affiliate_data  = $wpdb->get_row($wpdb->prepare(
                            "SELECT token FROM {$affiliates_table} WHERE user_id = %d",
                            $affiliate_id
                        ));

                        $token = $affiliate_data->token ?? '';

                        $first_name  = get_user_meta($affiliate_id, '_yith_wcaf_name_of_your_organization', true);
                        //$last_name   = get_user_meta($affiliate_id, 'billing_last_name', true);

                        $associated_id = get_user_meta($affiliate_id, 'associated_affiliate_id', true);
                        if ($associated_id) {
                            
                            if (!empty($token)) {
                                $oname_block .= '<strong>[' .esc_html($token) . '] '.$first_name.'</strong><br>';
                            }
                           

                          
                                $afuser = get_userdata($affiliate_id);
                                if ($afuser && !empty($afuser->user_email)) {
                                    $oname_block .= esc_html($afuser->user_email) . '<br>';
                                }                       

                                $oname_block .=  get_user_meta($affiliate_id, '_yith_wcaf_phone_number', true) . '<br>';
                        
                            $address_parts = array_filter([
                                get_user_meta($affiliate_id, '_yith_wcaf_address', true),
                                get_user_meta($affiliate_id, '_yith_wcaf_city', true),
                                get_user_meta($affiliate_id, '_yith_wcaf_state', true),
                                get_user_meta($affiliate_id, '_yith_wcaf_zipcode', true),
                               // get_user_meta($affiliate_id, 'billing_country', true),
                            ]);

                            if (!empty($address_parts)) {
                                $oname_block .= esc_html(implode(', ', $address_parts)) . '<br>';
                            }

                            $oname_block .= '<hr>';
                        }
                    }
                }

                $billing_address = array_filter([
                    $customer->get_billing_address_1(),
                    $customer->get_billing_city(),
                    $customer->get_billing_state(),
                    $customer->get_billing_postcode(),
                    $customer->get_billing_country()
                ]);
                $full_address = implode(', ', $billing_address);

                $name_block = '';
                if (!empty($full_name)) {
                    $name_block .= '<strong>' . esc_html($full_name) . '</strong><br>';
                }

                $name_block .= esc_html($user->user_email) . '<br>';

                $billing_phone = $customer->get_billing_phone();
                if (!empty($billing_phone)) {
                    $name_block .= esc_html($billing_phone) . '<br>';
                }

                if (!empty($full_address)) {
                    $name_block .= esc_html($full_address) . '<br>';
                }



if($user->user_email != ''){
            $data[] = [
                'name'   => $name_block,
                'email'  => $oname_block,
                'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '" data-nonce="' . esc_attr($nonce) . '">
                                <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png"> Login as Customer
                            </button>',
            ];
        }
        }

        wp_send_json([
            'draw'            => $draw,
            'recordsTotal'    => intval(count($data)),
            'recordsFiltered' => intval(count($data)),
            'data'            => $data,
        ]);
    }
    public function get_affiliates_list_ajax_handler() {
    global $wpdb;

    $start  = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $draw   = intval($_POST['draw'] ?? 1);
    $search = sanitize_text_field($_POST['search']['value'] ?? '');

    $search_filter = '';
    if (!empty($search)) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $search_filter = $wpdb->prepare(" AND (
            u.user_email LIKE %s OR
            fname.meta_value LIKE %s OR
            lname.meta_value LIKE %s
        )", $like, $like, $like);
    }

    // Total records
    $sql_total = "
        SELECT COUNT(DISTINCT a.user_id)
        FROM {$wpdb->prefix}yith_wcaf_affiliates a
        INNER JOIN {$wpdb->users} u ON u.ID = a.user_id
    ";
    $records_total = $wpdb->get_var($sql_total);

    // Filtered count
    $sql_filtered = "
        SELECT COUNT(DISTINCT a.user_id)
        FROM {$wpdb->prefix}yith_wcaf_affiliates a
        INNER JOIN {$wpdb->users} u ON u.ID = a.user_id
        LEFT JOIN {$wpdb->usermeta} fname ON fname.user_id = a.user_id AND fname.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} lname ON lname.user_id = a.user_id AND lname.meta_key = 'last_name'
        WHERE 1=1 $search_filter
    ";
    $records_filtered = $wpdb->get_var($sql_filtered);

    // Data query
    $sql_data = "
        SELECT 
            a.user_id,
            u.user_email,
            fname.meta_value AS first_name,
            lname.meta_value AS last_name
        FROM {$wpdb->prefix}yith_wcaf_affiliates a
        INNER JOIN {$wpdb->users} u ON u.ID = a.user_id
        LEFT JOIN {$wpdb->usermeta} fname ON fname.user_id = a.user_id AND fname.meta_key = 'first_name'
        LEFT JOIN {$wpdb->usermeta} lname ON lname.user_id = a.user_id AND lname.meta_key = 'last_name'
        WHERE 1=1 $search_filter
        LIMIT %d OFFSET %d
    ";
    $results = $wpdb->get_results($wpdb->prepare($sql_data, $length, $start));

    $data = [];
    foreach ($results as $row) {
        $data[] = [
            'email' => esc_html($row->user_email),
            'first_name' => esc_html($row->first_name),
            'last_name' => esc_html($row->last_name),
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