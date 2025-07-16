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
        add_action('wp_ajax_orthoney_admin_get_organizations_commission_data', array($this,'orthoney_admin_get_organizations_commission_data_handler'));
        add_action('wp_ajax_orthoney_admin_get_sales_representative_data', array($this,'orthoney_admin_get_sales_representative_data_handler'));
        add_action('wp_ajax_orthoney_activate_affiliate_account_ajax', array($this,'orthoney_orthoney_activate_affiliate_account_ajax_handler'));
    }
    
    /**
     * administrator callback
     */
    public function orthoney_orthoney_activate_affiliate_account_ajax_handler() {
        global $wpdb;

        $user_id = intval($_POST['user_id'] ?? 0);

        if (!$user_id) {
            wp_send_json_error(['message' => 'Invalid user ID.']);
        }

        $yith_wcaf_affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';
        $affiliate_token = $wpdb->get_var($wpdb->prepare(
            "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d",
            $user_id
        ));

        $organization_name = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);

        $to = 'support@orthoney.com';
        $subject = 'Organization Account Activated for the Season';

        // Create the content of your custom message
        $custom_message = '<p>Hello,</p>';
        $custom_message .= '<p>We’d like to inform you that the following organization has activated their account for this season:</p>';
        $custom_message .= '<ul>';
        $custom_message .= '<li><strong>Organization Code: </strong>' . esc_html($affiliate_token) . '</li>';
        $custom_message .= '<li><strong>Organization Name: </strong>' . esc_html($organization_name) . '</li>';
        $custom_message .= '</ul>';
        $custom_message .= '<p>Warm regards,<br>Honey From The Heart Team</p>';

        // Get WooCommerce mailer
        $mailer = WC()->mailer();

        // Wrap message using WooCommerce email template
        $wrapped_message = $mailer->wrap_message($subject, $custom_message);

        // Get headers (with content type and from name/email if needed)
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        // Send email using WooCommerce mailer
        $mail_sent = $mailer->send($to, $subject, $wrapped_message, $headers);

        if (!$mail_sent) {
            wp_send_json_error(['message' => 'Failed to send email.']);
        }

        // Optionally update metadata
        update_user_meta($user_id, 'activate_affiliate_account', 1);

        wp_send_json_success(['message' => 'Your account has been successfully activated.']);
    }


    // DB changes on 18-6-2025 for the show details
    public function orthoney_admin_get_customers_data_handler() {
        global $wpdb;

        $start  = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $search = isset($_POST['search']['value']) ? trim($_POST['search']['value']) : '';

        $order_column_index = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
        $order_dir = isset($_POST['order'][0]['dir']) && in_array($_POST['order'][0]['dir'], ['asc', 'desc']) ? $_POST['order'][0]['dir'] : 'asc';

        $organization_search = strtolower($_POST['organization_search'] ?? '');
        $organization_code_search = sanitize_text_field($_POST['organization_code_search'] ?? '');

        $column_map = [
            0 => 'u.ID',
            1 => 'm1.meta_value',
            3 => 'aff.token'
        ];

        $order_by = isset($column_map[$order_column_index]) ? $column_map[$order_column_index] : 'u.ID';

        $capabilities_key = $wpdb->prefix . 'capabilities';
        $like_customer    = '%customer%';
        $matching_ids = [];

        $org_conditions = [];
        $org_params = [];

        if (!empty($organization_search)) {
            $org_conditions[] = "aff.user_id IN (
                SELECT user_id FROM {$wpdb->usermeta}
                WHERE meta_key = '_yith_wcaf_name_of_your_organization'
                AND meta_value LIKE %s
            )";
            $org_params[] = '%' . $wpdb->esc_like($organization_search) . '%';
        }

        if (!empty($organization_code_search)) {
            $org_conditions[] = "aff.token LIKE %s";
            $org_params[] = '%' . $wpdb->esc_like($organization_code_search) . '%';
        }

        $org_where_sql = !empty($org_conditions) ? ' AND ' . implode(' AND ', $org_conditions) : '';

        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';

            $matching_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT u.ID
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->usermeta} m1 ON u.ID = m1.user_id AND m1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->usermeta} m2 ON u.ID = m2.user_id AND m2.meta_key = 'last_name'
                WHERE u.user_email LIKE %s 
                    OR m1.meta_value LIKE %s 
                    OR m2.meta_value LIKE %s 
                    OR CONCAT_WS(' ', m1.meta_value, m2.meta_value) LIKE %s",
                $search_like, $search_like, $search_like, $search_like
            ));

            if (empty($matching_ids)) {
                wp_send_json(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0]);
            }
        }

        if (!empty($matching_ids)) {
            $placeholders = implode(',', array_fill(0, count($matching_ids), '%d'));
            $params = array_merge([$capabilities_key, $like_customer], $matching_ids, $org_params);

            $total_customers = count($matching_ids);

            $sql = "SELECT DISTINCT u.ID
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                LEFT JOIN {$wpdb->usermeta} m1 ON u.ID = m1.user_id AND m1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->prefix}oh_affiliate_customer_linker linker ON u.ID = linker.customer_id
                LEFT JOIN {$wpdb->prefix}yith_wcaf_affiliates aff ON linker.affiliate_id = aff.user_id
                WHERE um.meta_key = %s AND um.meta_value LIKE %s AND u.ID IN ($placeholders) {$org_where_sql}
                ORDER BY {$order_by} {$order_dir}
                LIMIT %d OFFSET %d";

            $params[] = $length;
            $params[] = $start;

            $query_ids = $wpdb->get_col($wpdb->prepare($sql, ...$params));
        } else {
            $total_customers = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT u.ID)
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                LEFT JOIN {$wpdb->prefix}oh_affiliate_customer_linker linker ON u.ID = linker.customer_id
                LEFT JOIN {$wpdb->prefix}yith_wcaf_affiliates aff ON linker.affiliate_id = aff.user_id
                WHERE um.meta_key = %s AND um.meta_value LIKE %s {$org_where_sql}",
                ...array_merge([$capabilities_key, $like_customer], $org_params)
            ));

            $query_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT u.ID
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
                LEFT JOIN {$wpdb->usermeta} m1 ON u.ID = m1.user_id AND m1.meta_key = 'first_name'
                LEFT JOIN {$wpdb->prefix}oh_affiliate_customer_linker linker ON u.ID = linker.customer_id
                LEFT JOIN {$wpdb->prefix}yith_wcaf_affiliates aff ON linker.affiliate_id = aff.user_id
                WHERE um.meta_key = %s AND um.meta_value LIKE %s {$org_where_sql}
                ORDER BY {$order_by} {$order_dir}
                LIMIT %d OFFSET %d",
                ...array_merge([$capabilities_key, $like_customer], $org_params, [$length, $start])
            ));
        }

        $data = [];

        foreach ($query_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user || empty($user->user_email)) continue;

            $customer = new WC_Customer($user_id);

            $name = trim(get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true));
            $address = array_filter([
                $customer->get_billing_address_1(),
                $customer->get_billing_city(),
                $customer->get_billing_state(),
                $customer->get_billing_postcode(),
                $customer->get_billing_country()
            ]);

            $name_block = (!empty($name) ? '<strong>' . esc_html($name) . '</strong><br>' : '');
            $name_block .= esc_html($user->user_email) . '<br>';

            $phone = get_user_meta($user_id, 'user_registration_customer_phone_number', true);
            if ($phone == "") {
                $phone = $customer->get_billing_phone();
            }

            if (!empty($phone)) $name_block .= esc_html($phone) . '<br>';
            if (!empty($address)) $name_block .= esc_html(implode(', ', $address)) . '<br>';

            $cache_key = 'affiliates_for_customer__new' . $user_id;
            $oname_block = get_transient($cache_key);

            // if ($oname_block === false) {
                $oname_block = '';
                $blocks = [];

                $affiliate_customer_linker = $wpdb->prefix . 'oh_affiliate_customer_linker';
                $affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';

                $affiliates_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT affiliate_id FROM {$affiliate_customer_linker} WHERE customer_id = %d",
                    $user_id
                ));

                foreach ($affiliates_ids as $affiliate_id) {
                    $associated_affiliate_id = get_user_meta($affiliate_id, 'associated_affiliate_id', true);
                    if (!empty($associated_affiliate_id)) {
                        $associated_id = $associated_affiliate_id;
                    }else{
                        $associated_id = $affiliate_id;
                    }
                    $affiliate_wp_user = new WP_User($associated_id);
                    $affiliate_wp_user_roles = $affiliate_wp_user->roles;

                    // Check if current user has 'yith_affiliate' role
                    if (in_array('yith_affiliate', $affiliate_wp_user_roles)) {
                        // Get token from affiliate table
                        $token = $wpdb->get_var($wpdb->prepare(
                            "SELECT token FROM {$affiliates_table} WHERE user_id = %d",
                            $associated_id
                        ));

                        // Get meta values
                        $org_name   = get_user_meta($associated_id, '_yith_wcaf_name_of_your_organization', true);
                        $associated = get_user_meta($associated_id, 'associated_affiliate_id', true);

                         if ($associated && $token != '') {
                            $block = '';

                            // Add token and org name
                            if (!empty($token)) {
                                $block .= '<strong>[' . esc_html($token) . '] ' . esc_html($org_name) . '</strong><br>';
                            }

                            // Add affiliate email
                            $af_user = get_userdata($associated_id);
                            if ($af_user) {
                                $block .= esc_html($af_user->user_email) . '<br>';
                            }

                            // Add phone number
                            $phone = get_user_meta($associated_id, '_yith_wcaf_phone_number', true);
                            if (!empty($phone)) {
                                $block .= esc_html($phone) . '<br>';
                            }

                            // Add address if available
                            $address_parts = array_filter([
                                get_user_meta($associated_id, '_yith_wcaf_address', true),
                                get_user_meta($associated_id, '_yith_wcaf_city', true),
                                get_user_meta($associated_id, '_yith_wcaf_state', true),
                                get_user_meta($associated_id, '_yith_wcaf_zipcode', true),
                            ]);

                            if (!empty($address_parts)) {
                                $block .= esc_html(implode(', ', $address_parts)) . '<br>';
                            }

                            // Store final block
                            if (!empty($block)) {
                                $blocks[] = $block;
                            }
                        }
                    }
                }

                if (!empty($blocks)) {
                    $oname_block = implode('<hr>', $blocks);
                }

                set_transient($cache_key, $oname_block, HOUR_IN_SECONDS);
            // }

            $admin_url = admin_url("user-edit.php?user_id={$user_id}&wp_http_referer=%2Fwp-admin%2Fusers.php");

            $data[] = [
                'id' => $user_id,
                'name' => $name_block,
                'organizations' => $oname_block,
                'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user_id) . '">
                                <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png">Login as Customer
                            </button>
                            <a href="' . $admin_url . '" class="icon-txt-btn">
                                <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit Customer Profile
                            </a>'
            ];
        }

        wp_send_json([
            'data' => $data,
            'recordsTotal' => $total_customers,
            'recordsFiltered' => $total_customers
        ]);
    }

    //db end
    public function orthoney_admin_get_sales_representative_data_handler() {
        global $wpdb;

        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 50;
        $search_value = isset($_POST['search']['value']) ? sanitize_text_field($_POST['search']['value']) : '';
        $organization_code_search = isset($_POST['organization_code_search']) ? sanitize_text_field($_POST['organization_code_search']) : '';

        // Get all sales reps
        $args = [
            'role'    => 'sales_representative',
            'number'  => -1, // get all to filter manually
        ];

        $all_users = get_users($args);
        $filtered_users = [];

        foreach ($all_users as $user) {
            if (!in_array('sales_representative', $user->roles)) continue;

            $user_email = $user->user_email;
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name  = get_user_meta($user->ID, 'last_name', true);

            // Basic search filter
           if ($search_value) {
                $match = false;

                // Combine full name
                $full_name = trim($first_name . ' ' . $last_name);

                if (
                    stripos($user_email, $search_value) !== false ||
                    stripos($first_name, $search_value) !== false ||
                    stripos($last_name, $search_value) !== false ||
                    stripos($full_name, $search_value) !== false
                ) {
                    $match = true;
                }

                if (!$match) continue;
            }

            // Organization token filter
            $select_organization = get_user_meta($user->ID, 'select_organization', true);
            $choose_organization = get_user_meta($user->ID, 'choose_organization', true);

            $organizations_status = '';
            $matched_token = false;

            if ($select_organization === 'choose_organization' && !empty($choose_organization)) {
                $choose_ids_array = array_map('intval', (array) $choose_organization);
                $placeholders = implode(',', array_fill(0, count($choose_ids_array), '%d'));

                $query = $wpdb->prepare(
                    "SELECT token FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE enabled = '1' AND banned = '0' AND user_id IN ($placeholders)",
                    ...$choose_ids_array
                );

                $token_array = $wpdb->get_col($query);
                $organizations_status = implode(', ', $token_array);

                // Token search filter
                if ($organization_code_search !== '') {
                    foreach ($token_array as $token) {
                        if (stripos($token, $organization_code_search) !== false) {
                            $matched_token = true;
                            break;
                        }
                    }
                    if (!$matched_token) continue; // token didn't match
                }
            } else {
                $organizations_status = 'Assign All Organizations';
                if ($organization_code_search !== '') continue; // no tokens but filter required
            }

            $filtered_users[] = $user;
        }

        $total_count = count($filtered_users);

        // Apply pagination
        $paged_users = array_slice($filtered_users, $start, $length);

        

        $data = [];
        foreach ($paged_users as $user) {
            $admin_url = admin_url("user-edit.php?user_id={$user->ID}&wp_http_referer=%2Fwp-admin%2Fusers.php");
            $cbr_phone_number = get_user_meta($user->ID, 'user_registration_customer_phone_number', true);
            $select_organization = get_user_meta($user->ID, 'select_organization', true);
            $choose_organization = get_user_meta($user->ID, 'choose_organization', true);

            $organizations_status = '';

            if ($select_organization === 'choose_organization' && !empty($choose_organization)) {
                $choose_ids_array = array_map('intval', (array) $choose_organization);
                $placeholders = implode(',', array_fill(0, count($choose_ids_array), '%d'));

                $query = $wpdb->prepare(
                    "SELECT token FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id IN ($placeholders)",
                    ...$choose_ids_array
                );

                $token_array = $wpdb->get_col($query);
                sort($token_array); 
                $organizations_status = implode(', ', $token_array);
            }
            if ($select_organization === 'all') {
                $organizations_status = 'Assign All Organizations';
            }


            $data[] = [
                'id' => $user->ID,
                'name' => '<strong>' . esc_html($user->display_name) . '</strong><br>' . esc_html($user->user_email) . '</br>' . esc_html($cbr_phone_number),
                'email' => esc_html($user->user_email),
                'organizations' => esc_html($organizations_status),
                'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '">
                                <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png">Login as CSR
                            </button><a href="' . $admin_url . '" class="icon-txt-btn">
                                <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit CSR Profile
                            </a>'
            ];
        }

        wp_send_json([
            'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
            'recordsTotal' => $total_count,
            'recordsFiltered' => $total_count,
            'data' => $data
        ]);
    }


    
    public function orthoney_admin_get_organizations_commission_data_handler() {
     
        global $wpdb;

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $nonce  = wp_create_nonce('customer_login_nonce');

        // Step 1: Get all affiliate users
        $raw_users = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE token != '' AND user_id != 0");

        if (empty($raw_users)) {
            wp_send_json([
                'draw'            => $draw,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
            ]);
        }

        $user_ids = wp_list_pluck($raw_users, 'user_id');

        $user_meta_cache = [];
        $user_status_map = [];

        foreach ($raw_users as $row) {
            $user_id = intval($row->user_id);
            $enabled = intval($row->enabled);
            $banned  = intval($row->banned);

            $status = 'New request';
            if ($banned === 1) {
                $status = 'Banned';
            } elseif ($enabled === 1) {
                $status = 'Accepted and enabled';
            } elseif ($enabled === -1) {
                $status = 'Rejected';
            }

            $user_status_map[$user_id] = [
                'enabled' => $enabled,
                'banned'  => $banned,
                'label'   => $status,
            ];

            $user_obj = get_userdata($user_id);

           
            $city = get_user_meta($user_id, '_yith_wcaf_city', true);
            if (!$city) {
                $city = get_user_meta($user_id, 'billing_city', true);
                if (!$city) {
                    $city = get_user_meta($user_id, 'shipping_city', true);
                }
            }
            // $activate_affiliate_account = get_user_meta($user_id, 'activate_affiliate_account', true)? 'Activated' : 'Deactivated';

            // Retrieve state with fallback: _yith_wcaf_state → billing_state → shipping_state
            $state = get_user_meta($user_id, '_yith_wcaf_state', true);
            if (!$state) {
                $state = get_user_meta($user_id, 'billing_state', true);
                if (!$state) {
                    $state = get_user_meta($user_id, 'shipping_state', true);
                }
            }

            // Ensure empty strings if no values found
            $city  = $city ?: '';
            $state = $state ?: '';

            $organization = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
            $organization_phone = get_user_meta($user_id, '_yith_wcaf_phone_number', true);

            

            if (!$organization) {
                $organization = get_user_meta($user_id, 'first_name', true). ' ' .get_user_meta($user_id, 'last_name', true);
            }
            $user_meta_cache[$user_id] = [
                'organization' => $organization,
                'city'         => $city,
                'state'        => $state,
                'code'         => $row->token,
                'email'        => $user_obj ? $user_obj->user_email : '',
                'phone' => $organization_phone,
            ];
        }

        // Step 2: Apply search filter
       
        $organization_search = sanitize_text_field($_POST['organization_search'] ?? '');
        $organization_code_search = sanitize_text_field($_POST['organization_code_search'] ?? '');

        $filtered_user_ids = array_filter($user_ids, function ($user_id) use ($search, $user_meta_cache, $user_status_map, $organization_search, $organization_code_search) {
            $status = strtolower($user_status_map[$user_id]['label']);
            $organization = strtolower($user_meta_cache[$user_id]['organization']);
            $organization_code = strtolower($user_meta_cache[$user_id]['code']);

            // Organization filter
            if (!empty($organization_search) && strpos($organization, strtolower($organization_search)) === false) {
                return false;
            }
            if (!empty($organization_code_search) && strpos($organization_code, strtolower($organization_code_search)) === false) {
                return false;
            }

            if (empty($search)) return true;

            $search_lc = strtolower($search);
            $meta      = $user_meta_cache[$user_id];

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

        // Step 3: Apply ordering
        $order_column_index = $_POST['order'][0]['column'] ?? 0;
        $order_direction    = $_POST['order'][0]['dir'] ?? 'asc';

        $columns = ['code', 'email', 'organization', 'city', 'state', 'status'];
        $orderby_key = $columns[$order_column_index] ?? 'code';

        usort($filtered_user_ids, function ($a, $b) use ($orderby_key, $order_direction, $user_meta_cache, $user_status_map) {
            $a_val = $b_val = '';

            if ($orderby_key === 'status') {
                $a_val = $user_status_map[$a]['label'];
                $b_val = $user_status_map[$b]['label'];
            } else {
                $a_val = $user_meta_cache[$a][$orderby_key] ?? '';
                $b_val = $user_meta_cache[$b][$orderby_key] ?? '';
            }

            $comparison = strnatcasecmp($a_val, $b_val);
            return $order_direction === 'asc' ? $comparison : -$comparison;
        });

        // Step 4: Apply pagination
        $paged_user_ids = array_slice(array_values($filtered_user_ids), $start, $length);

        // Step 5: Format data for DataTables
        $data = [];

        
        foreach ($paged_user_ids as $user_id) {
            $meta   = $user_meta_cache[$user_id];
            $status = $user_status_map[$user_id]['label'];
           
             $organizationdata = [];

            if (!empty($meta['organization'])) {
                $organizationdata[] = '<strong> ['.esc_html($meta['code']).'] ' . esc_html($meta['organization']) . '</strong>';
            }

            $city_state = trim(esc_html($meta['city']) . (empty($meta['city']) || empty($meta['state']) ? '' : ', ') . esc_html($meta['state']));
            if (!empty($city_state)) {
                $organizationdata[] = $city_state;
            }

            if (!empty($meta['email'])) {
                $organizationdata[] = esc_html($meta['email']);
            }

            if (!empty($meta['phone'])) {
                $organizationdata[] = esc_html($meta['phone']);
            }

            $organization = implode('<br>', $organizationdata);

            // Remove empty values and join with <br>
            $organization = implode('<br>', array_filter($organizationdata));
            $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate_base_token($meta['code']);

            $unit_profit = wc_price(0);
            if($commission_array['unit_profit'] != 0){
               $unit_profit = wc_price($commission_array['unit_profit']).'<br><small>( '.wc_price($commission_array["selling_min_price"]).' - '.wc_price($commission_array["unit_cost"]).' )</small>';
            } 
          
            $cost = '';
            if($commission_array['total_order'] != 0){
                $cost = '<strong>Total: </strong>'. wc_price(($commission_array['total_quantity'] * $commission_array['selling_min_price']));
                $cost .= '<br><small><strong>Fundraising: </strong>'. wc_price(($commission_array['fundraising_qty'] * $commission_array['selling_min_price']));
                $cost .= '<br><strong>Wholesale: </strong>'. wc_price(($commission_array['wholesale_qty'] * $commission_array['selling_min_price']));
                $cost .= '</small>';

            }
            $dist_cost = '';
            if($commission_array['total_order'] != 0){
                $dist_cost = '<strong>Total: </strong>'. wc_price(($commission_array['total_quantity'] * $commission_array['unit_cost']));
                $dist_cost .= '<br><small><strong>Fundraising: </strong>'. wc_price(($commission_array['fundraising_qty'] * $commission_array['unit_cost']));
                $dist_cost .= '<br><strong>Wholesale: </strong>'. wc_price(($commission_array['wholesale_qty'] * $commission_array['unit_cost']));
                $dist_cost .= '</small>';

            }

            $data[] = [
                'organization' => $organization,
                'new_organization' => 'Yes',
                'status'       => esc_html($status),
                'cost'       => $cost,
                'dist_cost'       => $dist_cost,
                'selling_min_price'       => esc_html($commission_array['selling_min_price']),
                'total_order'       => esc_html($commission_array['total_order']),
                'total_qty'       => esc_html($commission_array['total_quantity']),
                'wholesale_qty'       => esc_html($commission_array['wholesale_qty']),
                'fundraising_qty'       => esc_html($commission_array['fundraising_qty']),
                'fundraising_orders'       => esc_html($commission_array['fundraising_orders']),
                'total_all_quantity'       => esc_html($commission_array['total_all_quantity']),
                'unit_cost'       => esc_html($commission_array['unit_cost']),
                'unit_profit'       => $unit_profit,
                'total_commission'       => wc_price($commission_array['total_commission']),
            ];
        }
        
        wp_send_json([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }


    public function orthoney_admin_get_organizations_data_handler() {
        global $wpdb;

        $sales_reps = get_users(['role' => 'sales_representative']);
        $sales_reps_data = [];

        foreach ($sales_reps as $user) {
            if (in_array('sales_representative', $user->roles) && !empty($user->user_email)) {
                $select_organization = get_user_meta($user->ID, 'select_organization', true);
                $choose_organization = get_user_meta($user->ID, 'choose_organization', true);

                if ($select_organization === 'choose_organization') {
                    if (!empty($choose_organization)) {
                        $choose_ids_array = array_map('intval', (array) $choose_organization);
                        $choose_ids = implode(',', $choose_ids_array);
                        if (!empty($choose_ids)) {
                            $ids_array = array_map('intval', explode(',', $choose_ids));
                            $placeholders = implode(',', array_fill(0, count($ids_array), '%d'));
                            $query = $wpdb->prepare(
                                "SELECT token FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id IN ($placeholders)",
                                ...$ids_array
                            );
                            $results = $wpdb->get_col($query);
                            $sales_reps_data[$user->ID] = $results;
                        }
                    }
                } else {
                    $sales_reps_data[$user->ID] = 'all';
                }
            }
        }

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $nonce  = wp_create_nonce('customer_login_nonce');

        $raw_users = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcaf_affiliates");

        if (empty($raw_users)) {
            wp_send_json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $user_ids = wp_list_pluck($raw_users, 'user_id');
        $user_meta_cache = [];
        $user_status_map = [];
        $aff_data_array  = [];

        foreach ($raw_users as $row) {
            $user_id = intval($row->user_id);
            $enabled = intval($row->enabled);
            $banned  = intval($row->banned);
            $aff_data_array[$user_id] = intval($row->ID);

            $status = 'New request';
            if ($banned === 1) {
                $status = 'Banned';
            } elseif ($enabled === 1) {
                $status = 'Accepted and enabled';
            } elseif ($enabled === -1) {
                $status = 'Rejected';
            }

            $user_status_map[$user_id] = [
                'enabled' => $enabled,
                'banned'  => $banned,
                'label'   => $status,
            ];

            $user_obj = get_userdata($user_id);

        

            $email = get_user_meta($user_id, '_yith_wcaf_email', true)
                ?: $user_obj->user_email ?: '';


            $city = get_user_meta($user_id, '_yith_wcaf_city', true)
                ?: get_user_meta($user_id, 'billing_city', true)
                ?: get_user_meta($user_id, 'shipping_city', true) ?: '';

            $state = get_user_meta($user_id, '_yith_wcaf_state', true)
                ?: get_user_meta($user_id, 'billing_state', true)
                ?: get_user_meta($user_id, 'shipping_state', true) ?: '';

            $organization = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
            if (!$organization) {
                $organization = get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true);
            }

            $organization_phone = get_user_meta($user_id, '_yith_wcaf_phone_number', true);

            $user_meta_cache[$user_id] = [
                'organization' => $organization,
                'city'         => $city,
                'state'        => $state,
                'code'         => $row->token,
                'email'        => $email,
                'phone'        => $organization_phone,
            ];
        }

        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        $session_status_filter = sanitize_text_field($_POST['session_status_filter'] ?? '');
        $organization_search = stripslashes($_POST['organization_search'] ?? '');
        $organization_code_search = stripslashes($_POST['organization_code_search'] ?? '');
       
        $filtered_user_ids = array_filter($user_ids, function ($user_id) use (
            $search, $user_meta_cache, $user_status_map, $status_filter, $session_status_filter, $organization_search, $organization_code_search, $sales_reps_data
        ) {
            $status = strtolower($user_status_map[$user_id]['label']);
            $meta = $user_meta_cache[$user_id];
            $organization = $meta['organization'];
            $code = strtolower($meta['code']);

            $activate_affiliate_account = get_user_meta($user_id, 'activate_affiliate_account', true);

           if (!empty($organization_search)) {
                $org_search = strtolower(stripslashes($organization_search));
                if (strpos(strtolower($organization), $org_search) === false) {
                    return false;
                }
            }

            if (!empty($organization_code_search) && strpos($code, strtolower($organization_code_search)) === false) {
                return false;
            }

            if (!empty($status_filter) && strtolower($status_filter) !== $status) {
                return false;
            }

            if ($session_status_filter === 'active' && intval($activate_affiliate_account) !== 1) {
                return false;
            }

            if ($session_status_filter === 'deactivate' && intval($activate_affiliate_account) === 1) {
                return false;
            }

            if (empty($search)) return true;

            $search_lc = strtolower($search);
            $search_value = $meta['code'];

            // === Associated Affiliate Search ===
            $associated_affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true) ?: $user_id;
            $assoc_first = strtolower(get_user_meta($associated_affiliate_id, 'first_name', true));
            $assoc_last  = strtolower(get_user_meta($associated_affiliate_id, 'last_name', true));
            $assoc_full  = trim("$assoc_first $assoc_last");
            $assoc_email = strtolower(get_userdata($associated_affiliate_id)->user_email ?? '');

            // === Sales Rep Match Check ===
            $salesrep_match = false;
            foreach ($sales_reps_data as $key => $value) {
                if ($value === 'all' || (is_array($value) && in_array($search_value, $value))) {
                    $sr_first = strtolower(get_user_meta($key, 'first_name', true));
                    $sr_last  = strtolower(get_user_meta($key, 'last_name', true));
                    $sr_full  = trim("$sr_first $sr_last");
                    $sr_email = strtolower(get_userdata($key)->user_email ?? '');

                    if (
                        strpos($sr_first, $search_lc) !== false ||
                        strpos($sr_last, $search_lc) !== false ||
                        strpos($sr_full, $search_lc) !== false ||
                        strpos($sr_email, $search_lc) !== false
                    ) {
                        $salesrep_match = true;
                        break;
                    }
                }
            }

            return (
                strpos($organization, $search_lc) !== false ||
                strpos(strtolower($meta['city']), $search_lc) !== false ||
                strpos(strtolower($meta['state']), $search_lc) !== false ||
                strpos(strtolower($meta['code']), $search_lc) !== false ||
                strpos(strtolower($meta['email']), $search_lc) !== false ||
                strpos($status, $search_lc) !== false ||
                strpos($assoc_first, $search_lc) !== false ||
                strpos($assoc_last, $search_lc) !== false ||
                strpos($assoc_full, $search_lc) !== false ||
                strpos($assoc_email, $search_lc) !== false ||
                $salesrep_match
            );
        });

        $recordsTotal    = count($user_ids);
        $recordsFiltered = count($filtered_user_ids);

        // Step 5: Ordering
        $order_column_index = $_POST['order'][0]['column'] ?? 0;
        $order_direction = $_POST['order'][0]['dir'] ?? 'asc';
        $columns = ['code', 'email', 'organization', 'city', 'state', 'status'];
        $orderby_key = $columns[$order_column_index] ?? 'code';

        usort($filtered_user_ids, function ($a, $b) use ($orderby_key, $order_direction, $user_meta_cache, $user_status_map) {
            $a_val = ($orderby_key === 'status') ? $user_status_map[$a]['label'] : ($user_meta_cache[$a][$orderby_key] ?? '');
            $b_val = ($orderby_key === 'status') ? $user_status_map[$b]['label'] : ($user_meta_cache[$b][$orderby_key] ?? '');
            $comparison = strnatcasecmp($a_val, $b_val);
            return $order_direction === 'asc' ? $comparison : -$comparison;
        });

        // Step 6: Pagination
        $paged_user_ids = array_slice(array_values($filtered_user_ids), $start, $length);

        // Step 7: Format and output
        $data = [];

        foreach ($paged_user_ids as $user_id) {
            $meta = $user_meta_cache[$user_id];
            $status = $user_status_map[$user_id]['label'];

            $associated_affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true) ?: $user_id;
            $activate_affiliate_account = get_user_meta($user_id, 'activate_affiliate_account', true) ?: 0;
            $yith_wcaf_phone_number = get_user_meta($user_id, '_yith_wcaf_phone_number', true) ?: '';
            $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;
            $product_price = get_user_meta($user_id, 'DJarPrice', true);
            $new_organization = OAM_AFFILIATE_Helper::is_user_created_this_year($user_id) ? 'New' : 'Returning';

            $show_price = ($product_price >= $selling_minimum_price) ? $product_price : $selling_minimum_price;

            $organizationdata = array_filter([
                '<strong>' . esc_html($meta['organization']) . '</strong>',
                trim(esc_html($meta['city']) . (!empty($meta['city']) && !empty($meta['state']) ? ', ' : '') . esc_html($meta['state'])),
                esc_html($meta['email']),
                esc_html($meta['phone']),
            ]);

            $organization = implode('<br>', $organizationdata);

            $org_admin_user = '';
            if ($associated_affiliate_id) {
                $org_user = get_userdata($associated_affiliate_id);
                $first_name = get_user_meta($associated_affiliate_id, 'first_name', true);
                $last_name  = get_user_meta($associated_affiliate_id, 'last_name', true);
                $yith_wcaf_phone_number = get_user_meta($user_id, 'user_registration_customer_phone_number', true) ?: '';
                $org_user_name = trim($first_name . ' ' . $last_name) ?: $org_user->display_name;
                $org_email = $org_user->user_email;
                $org_admin_user = '<strong>'.$org_user_name . '</strong><br>' . $org_email . '<br>' . $yith_wcaf_phone_number;
            }

            if (!empty($meta['email']) && !empty($meta['code'])) {
                $userid_keys = [];
                $search_value = $meta['code'];

                foreach ($sales_reps_data as $key => $value) {
                    if ($value === 'all' || (is_array($value) && in_array($search_value, $value))) {
                        $first_name = get_user_meta($key, 'first_name', true);
                        $last_name = get_user_meta($key, 'last_name', true);

                        $suser_info = get_userdata($key);
                        $semail = $suser_info ? $suser_info->user_email : '';

                        $cbr_phone_number = get_user_meta($key, 'user_registration_customer_phone_number', true);
                        $parts = array_filter([
                            trim("$first_name $last_name") ? '<strong>'.trim("$first_name $last_name").'</strong>' : '',
                            trim($semail),
                            trim($cbr_phone_number),
                        ]);

                        $combined_info = implode('<br>', $parts);
                        $userid_keys[] = $combined_info;
                    }
                }

                $filtered_keys = array_filter($userid_keys);
                $last_index = count($filtered_keys) - 1;

                $csr_name = implode('', array_map(function ($val, $index) use ($filtered_keys, $last_index) {
                    // Escape the content, but allow HTML formatting (br, hr)
                    $output = nl2br($val); // escape content safely, preserve line breaks if any
                    if ($index < $last_index) {
                        $output .= '<br><hr>';
                    }
                    return $output;
                }, $filtered_keys, array_keys($filtered_keys)));

                $new_organization_block = implode('<br>', array_filter([
                    '<strong>Org Status:</strong> ' . esc_html($new_organization),
                    esc_html($status),
                    '<strong>Season Status:</strong> ' . esc_html($activate_affiliate_account == 1 ? 'Activated' : 'Deactivated'),
                ]));

                $admin_url = admin_url() . '/admin.php?page=yith_wcaf_panel&affiliate_id=' . intval($aff_data_array[$user_id]) . '&tab=affiliates';

                $data[] = [
                    'code' => esc_html($meta['code']),
                    'organization' => $organization,
                    'csr_name' => $csr_name,
                    'organization_admin' => $org_admin_user,
                    'new_organization' => $new_organization_block,
                    'status' => esc_html($status),
                    'price' => wc_price($show_price),
                    'login' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . intval($user_id) . '" data-nonce="' . esc_attr($nonce) . '"><img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login As Org</button><a href="' . $admin_url . '" class="icon-txt-btn"><img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit Org Prf</a>'
                ];

            }
        }

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }



}

// Initialize the class
new OAM_ADMINISTRATOR_AJAX();