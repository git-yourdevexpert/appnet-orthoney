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
         add_action('wp_ajax_orthoney_activate_affiliate_account_ajax', array($this,'orthoney_orthoney_activate_affiliate_account_ajax_handler'));
    }
    
    /**
     * administrator callback
     */
    public function orthoney_orthoney_activate_affiliate_account_ajax_handler() {
        $user_id = intval($_POST['user_id'] ?? 0);

        if (!$user_id) {
            wp_send_json_error(['message' => 'Invalid user ID.']);
        }

        // Optionally set any user meta or status updates here
        // Example:
        update_user_meta($user_id, 'activate_affiliate_account', 1);

        wp_send_json_success(['message' => 'Your account has been successfully activated.']);
    }

    public function get_affiliate_details_by_id($affiliate_id) {
    global $wpdb;

    $affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';

    $affiliates = $wpdb->get_results($wpdb->prepare(
        "SELECT user_id, token FROM {$affiliates_table} WHERE ID = %d", $affiliate_id
    ), ARRAY_A);

    if (empty($affiliates)) {
        return null; // No affiliates found
    }

    $results = [];

    foreach ($affiliates as $affiliate) {
        $user_id = $affiliate['user_id'];
        $token   = $affiliate['token'];

        $user = get_userdata($user_id);
        if (!$user) {
            continue;
        }

        // WooCommerce Billing Info
        $first_name  = get_user_meta($user_id, 'billing_first_name', true);
        $last_name   = get_user_meta($user_id, 'billing_last_name', true);
        $phone       = get_user_meta($user_id, 'billing_phone', true);
        $address_1   = get_user_meta($user_id, 'billing_address_1', true);
        $address_2   = get_user_meta($user_id, 'billing_address_2', true);
        $city        = get_user_meta($user_id, 'billing_city', true);
        $state       = get_user_meta($user_id, 'billing_state', true);
        $postcode    = get_user_meta($user_id, 'billing_postcode', true);
        $country     = get_user_meta($user_id, 'billing_country', true);

        $results[] = [
            'token'      => $token,
            'email'      => $user->user_email,
            'first_name' => $first_name ?: $user->first_name,
            'last_name'  => $last_name ?: $user->last_name,
            'phone'      => $phone,
            'address'    => trim("{$address_1} {$address_2}"),
            'city'       => $city,
            'state'      => $state,
            'postcode'   => $postcode,
            'country'    => $country,
        ];
    }

    return !empty($results) ? $results : null;
}


 // db changes on 18-6-2025 for the show details
public function orthoney_admin_get_customers_data_handler() {

    $all_users = get_users();
    $data = [];

    foreach ($all_users as $user) {
        if (count($user->roles) === 1 && in_array('customer', $user->roles)) {
            if ($user->user_email != '') {

                // Get WooCommerce customer object
                $customer = new WC_Customer($user->ID);
               $affiliate_id = "";
$oname_block = '';
if (get_user_meta($user->ID, 'associated_affiliate_id', true)) {
    $affiliate_id = get_user_meta($user->ID, 'associated_affiliate_id', true);
$affiliate_data_list = $this->get_affiliate_details_by_id($affiliate_id);

$oname_block = '';

if (!empty($affiliate_data_list)) {
    foreach ($affiliate_data_list as $affiliate_data) {
        // Build name
        if (!empty($affiliate_data['first_name']) || !empty($affiliate_data['last_name'])) {
            $oname_block .= '<strong>' . esc_html(trim($affiliate_data['first_name'] . ' ' . $affiliate_data['last_name'])) . '</strong><br>';
        }

        // Optional token
        if (!empty($affiliate_data['token'])) {
            $oname_block .= esc_html($affiliate_data['token']) . '<br>';
        }

        // Optional email
        if (!empty($affiliate_data['email'])) {
            $oname_block .= esc_html($affiliate_data['email']) . '<br>';
        }

        // Optional phone
        if (!empty($affiliate_data['phone'])) {
            $oname_block .= esc_html($affiliate_data['phone']) . '<br>';
        }

        // Optional address
        $address_parts = array_filter([
            $affiliate_data['address'] ?? '',
            $affiliate_data['city'] ?? '',
            $affiliate_data['state'] ?? '',
            $affiliate_data['postcode'] ?? '',
            $affiliate_data['country'] ?? '',
        ]);

        if (!empty($address_parts)) {
            $oname_block .= esc_html(implode(', ', $address_parts)) . '<br>';
        }

        $oname_block .= '<hr>'; // separator for multiple affiliates
    }
}

}



                

                // Get name from user profile
                $first_name = get_user_meta($user->ID, 'first_name', true);
                $last_name  = get_user_meta($user->ID, 'last_name', true);
                $full_name  = trim("{$first_name} {$last_name}");

                // Billing details
                $billing_address  = $customer->get_billing_address_1();
                $billing_city     = $customer->get_billing_city();
                $billing_state    = $customer->get_billing_state();
                $billing_postcode = $customer->get_billing_postcode();
                $billing_country  = $customer->get_billing_country();
                $billing_phone    = $customer->get_billing_phone();

                // Build full address only with non-empty parts
                $address_parts = array_filter([
                    $billing_address,
                    $billing_city,
                    $billing_state,
                    $billing_postcode,
                    $billing_country
                ]);
                $full_address = implode(', ', $address_parts);

                // Build name block only with available info
                $name_block = '';
              
                if (!empty($full_name)) {
                    $name_block .= '<strong>' . esc_html($full_name) . '</strong><br>';
                }
                
                  if (!empty($user->user_email)) {
                    $name_block .= $user->user_email.'</br>';
                }
                   if (!empty($billing_phone)) {
                    $name_block .= esc_html($billing_phone);
                }
                if (!empty($full_address)) {
                    $name_block .= esc_html($full_address) . '<br>';
                }
             

                $admin_url = admin_url("user-edit.php?user_id={$user->ID}&wp_http_referer=%2Fwp-admin%2Fusers.php");

                $data[] = [
                    'id' => $user->ID,
                    'name' => $name_block,
                  //  'email' => esc_html($user->user_email),
                    'organizations' => $oname_block,
                    'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '">
                                    <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png">Login as Customer
                                </button><a href="' . $admin_url . '" class="icon-txt-btn"><img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit Customer Profile</a>'
                ];
            }
        }
    }

    wp_send_json([
        'data' => $data
    ]);
}

    //db end
    public function orthoney_admin_get_sales_representative_data_handler() {

        // Security check if needed: check_ajax_referer('your-nonce')

        $all_users =  get_users([
            'role' => 'sales_representative',
        ]);

        $data = [];

        foreach ($all_users as $user) {
            if (in_array('sales_representative', $user->roles)) {
                if($user->user_email != ''){
                    $admin_url = admin_url("user-edit.php?user_id={$user->ID}&wp_http_referer=%2Fwp-admin%2Fusers.php");

                    $select_organization = get_user_meta($user->ID, 'select_organization', true);
                    $choose_organization = get_user_meta($user->ID, 'choose_organization', true);

                    $include_clause = '';
                    $choose_ids_array = [];
                    $choose_ids = '';
                    $organizations_status = 'Assign All Organizations';
                    if ($select_organization === 'choose_organization' && !empty($choose_organization)) {
                        $choose_ids_array = array_map('intval', (array)$choose_organization);
                         $choose_ids = implode(',', $choose_ids_array);
                    

                        global $wpdb;

                        $token_array = [];

                        if (!empty($choose_ids)) {
                            $ids_array = array_map('intval', explode(',', $choose_ids)); // Convert string to int array
                            $placeholders = implode(',', array_fill(0, count($ids_array), '%d'));

                            // Prepare and run query
                            $query = $wpdb->prepare(
                                "SELECT token FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id IN ($placeholders)",
                                ...$ids_array
                            );

                            $results = $wpdb->get_col($query); // Fetch just the 'token' column

                            $token_array = $results;
                        }
                        $organizations_status = implode(', ', $token_array);
                    }


                    $data[] = [
                        'id' => $user->ID,
                        'name' => esc_html($user->display_name),
                        'email' => esc_html($user->user_email),
                        'organizations' => $organizations_status,
                        'action' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . esc_attr($user->ID) . '">
                                        <img src="' . OH_PLUGIN_DIR_URL . '/assets/image/login-customer-icon.png">Login as Sales Representative
                                    </button><a href="' . $admin_url . '" class="icon-txt-btn"><img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit Sales Representative Profile</a>'
                    ];
                }
            }
        }

        wp_send_json([
            'data' => $data
        ]);
    }
    
    public function orthoney_admin_get_organizations_data_handler() {
        global $wpdb;

        $sales_reps = get_users([
            'role' => 'sales_representative',
        ]);

        $data = [];
        $sales_reps_data = [];

        foreach ($sales_reps as $user) {
            if (in_array('sales_representative', $user->roles)) {
                if($user->user_email != ''){
                    

                    $select_organization = get_user_meta($user->ID, 'select_organization', true);
                    $choose_organization = get_user_meta($user->ID, 'choose_organization', true);

                    $include_clause = '';
                    $choose_ids_array = [];
                    $choose_ids = '';
                    $organizations_status = 'Assign All Organizations';
                    if ($select_organization === 'choose_organization' && !empty($choose_organization)) {
                        $choose_ids_array = array_map('intval', (array)$choose_organization);
                         $choose_ids = implode(',', $choose_ids_array);
                    

                        if (!empty($choose_ids)) {
                            $ids_array = array_map('intval', explode(',', $choose_ids)); // Convert string to int array
                            $placeholders = implode(',', array_fill(0, count($ids_array), '%d'));

                            // Prepare and run query
                            $query = $wpdb->prepare(
                                "SELECT token FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id IN ($placeholders)",
                                ...$ids_array
                            );

                            $results = $wpdb->get_col($query); // Fetch just the 'token' column

                            $token_array = $results;
                        }
                        $sales_reps_data[$user->ID] = $token_array;
                    }else{
                        $sales_reps_data[$user->ID] = 'all';
                    }
                    
                }
            }
        }
   

        $start  = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $draw   = intval($_POST['draw'] ?? 1);
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $nonce  = wp_create_nonce('customer_login_nonce');

        // Step 1: Get all affiliate users
        $raw_users = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yith_wcaf_affiliates");

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
            if (!$organization) {
                $organization = get_user_meta($user_id, 'first_name', true). ' ' .get_user_meta($user_id, 'last_name', true);
            }
            $user_meta_cache[$user_id] = [
                'organization' => $organization,
                'city'         => $city,
                'state'        => $state,
                'code'         => $row->token,
                'email'        => $user_obj ? $user_obj->user_email : '',
            ];
        }

        // Step 2: Apply search filter
        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        $organization_search = sanitize_text_field($_POST['organization_search'] ?? '');

        $filtered_user_ids = array_filter($user_ids, function ($user_id) use ($search, $user_meta_cache, $user_status_map, $status_filter, $organization_search) {
            $status = strtolower($user_status_map[$user_id]['label']);
            $organization = strtolower($user_meta_cache[$user_id]['organization']);

            // Organization filter
            if (!empty($organization_search) && strpos($organization, strtolower($organization_search)) === false) {
                return false;
            }

            // Status filter
            if (!empty($status_filter) && strtolower($status_filter) !== $status) {
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
           
            $total_commission = 0;
            $activate_affiliate_account = 0;
            $total_quantity = 0;
            
            $associated_affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);
            
            $activate_affiliate_account = get_user_meta($user_id, 'activate_affiliate_account', true) ?: 0;
            $yith_wcaf_phone_number = get_user_meta($user_id, '_yith_wcaf_phone_number', true) ?: '';
            $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;
            $product_price = get_user_meta($user_id, 'DJarPrice', true);
            $show_price = $selling_minimum_price;
            if($product_price >= $selling_minimum_price){
                $show_price = $product_price;
            }

            $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate($user_id);

            $commission_array_data = json_decode($commission_array, true);

            $new_organization = OAM_AFFILIATE_Helper::is_user_created_this_year($user_id) ? 'New' : 'Returning';

            $exclude_coupon = EXCLUDE_COUPON;

        // Initialize counters
            $total_all_quantity = $fundraising_qty = $wholesale_qty = 0;
            $total_orders = $wholesale_order = 0;
            $unit_price = $unit_cost = 0;
            $total_commission = 0;

            if (!empty($commission_array_data['data'])) {
                foreach ($commission_array_data['data'] as $value) {
                    // Aggregate quantities
                    $fundraising_qty = $value['total_quantity'];
                    $wholesale_qty = $value['wholesale_qty'];

                    // Process only if affiliate account is active
                    if (!empty($value['affiliate_account_status'])) {
                        $unit_price = $value['par_jar'];
                        $unit_cost = $value['minimum_price'];
                        $total_all_quantity += $value['total_quantity'];
                        $total_orders++;

                        // Handle coupon logic
                        $coupon_array = !empty($value['is_voucher_used']) 
                            ? array_values(array_diff(explode(",", $value['is_voucher_used']), $exclude_coupon)) 
                            : [];

                        if (empty($coupon_array)) {
                            $total_commission += $value['commission'];
                        } else {
                            $wholesale_order++;
                        }
                    }
                }
            }

            // Final quantity and order calculations
            $total_all_quantity = $fundraising_qty;
            $fundraising_orders = $total_orders - $wholesale_order;

            // Calculate total commission based on jar threshold
            $total_commission = ($total_all_quantity > 50)  ? wc_price($fundraising_qty * ($unit_price - $unit_cost)) : wc_price(0);

            $admin_url = admin_url() . '/admin.php?page=yith_wcaf_panel&affiliate_id=' . intval($row->ID) . '&tab=affiliates';
            
            $organizationdata = [
                $meta['organization'],
                esc_html($meta['city']),
                esc_html($meta['state']),
            ];
            $organizationdata = array_filter($organizationdata);

            $org_admin_user = '';
            if ($associated_affiliate_id) {
                $org_user = get_userdata($associated_affiliate_id);

                // Fetch first and last name
                $first_name = get_user_meta($associated_affiliate_id, 'first_name', true);
                $last_name  = get_user_meta($associated_affiliate_id, 'last_name', true);
                $org_user_name = trim($first_name . ' ' . $last_name);

                // Fallback to display name if first/last are empty
                if (empty($org_user_name)) {
                    $org_user_name = $org_user->display_name;
                }

                // Get email and phone
                $org_email = $org_user->user_email;
                $org_phone = $yith_wcaf_phone_number;

                // Combine all into a display string
                $org_admin_user = $org_user_name . '<br>' . $org_email . '<br>' . $org_phone;
            }

            if($meta['email'] != '' AND $meta['code'] != ''){

                $search_value = $meta['code'];
                $userid_keys = [];

                foreach ($sales_reps_data as $key => $value) {
                    if ($value === 'all' || (is_array($value) && in_array($search_value, $value))) {
                        $first_name = get_user_meta($key, 'first_name', true);
                        $last_name = get_user_meta($key, 'last_name', true);

                        $userid_keys[] = trim("$first_name $last_name");
                    }
                }

                $data[] = [
                    'code'         => esc_html($meta['code']),
                    'organization' => esc_html(implode(', ', $organizationdata)),
                    'csr_name'     =>esc_html(implode(', ', $userid_keys)),
                    'organization_admin'        => $org_admin_user,
                    'new_organization' => esc_html($new_organization),
                    'status'       => esc_html($status),
                    'season_status' => esc_html($activate_affiliate_account == 1 ? 'Activated' : 'Deactivated'),
                    'price' => wc_price($show_price),
                    'commission' => $total_commission,
                    'login'        => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . intval($user_id) . '" data-nonce="' . esc_attr($nonce) . '"><img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login As An Organization</button><a href="' . $admin_url . '" class="icon-txt-btn"><img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit Organizations Profile</a>'
                ];
            }
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