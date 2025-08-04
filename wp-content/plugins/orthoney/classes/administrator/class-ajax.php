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
        add_action('wp_ajax_get_org_details_base_id', array($this, 'orthoney_get_org_details_base_id_callback'));
        add_action('wp_ajax_switch_org_to_order', array($this, 'orthoney_switch_org_to_order_callback'));
        add_action('wp_ajax_generate_fulfillment_report', array($this, 'orthoney_generate_fulfillment_report_callback'));

    }

   
    public function orthoney_generate_fulfillment_report_callback() {
    check_ajax_referer('oam_nonce', 'security');
    global $wpdb;

    $exclude_coupon = EXCLUDE_COUPON;

    $date_range = sanitize_text_field($_POST['date_range'] ?? '');
    $sendmail_raw = $_POST['sendmail'] ?? '';
    $email_list = array_filter(array_map('sanitize_email', explode(',', $sendmail_raw)));
    $offset = absint($_POST['offset'] ?? 0);
    $limit = 10;

    if (empty($date_range)) {
        wp_send_json_error(['message' => 'Date range is required.']);
    }

    list($start, $end) = explode(' - ', $date_range);
    $start_date = DateTime::createFromFormat('m/d/Y', trim($start));
    $end_date = DateTime::createFromFormat('m/d/Y', trim($end));

    if (!$start_date || !$end_date) {
        wp_send_json_error(['message' => 'Invalid date format.']);
    }

    $start_str = $start_date->format('Y-m-d 00:00:00');
    $end_str = $end_date->format('Y-m-d 23:59:59');
    $file_name_date_format = $start_date->format('mdY') . '-' . $end_date->format('mdY');

    $session_key = md5($date_range . $sendmail_raw);
    $fulfillment_key = 'fulfillment_report_' . $session_key;
    $greetings_key = 'greetings_report_' . $session_key;

    $upload_dir = wp_upload_dir();
    $custom_dir = $upload_dir['basedir'] . '/fulfillment-reports';
    $custom_url = $upload_dir['baseurl'] . '/fulfillment-reports';

    if (!file_exists($custom_dir)) {
        wp_mkdir_p($custom_dir);
        chmod($custom_dir, 0755);
    }

    if (!is_writable($custom_dir)) {
        wp_send_json_error(['message' => 'Directory not writable: ' . $custom_dir]);
    }

    if ($offset === 0) {
        $base_name = 'download-order-fulfillment-' . $file_name_date_format;
        $greetings_base_name = 'download-greetings-per-jar-' . $file_name_date_format;

        $index = 1;
        do {
            $fulfillment_filename = "{$base_name}-{$index}.csv";
            $greetings_filename = "{$greetings_base_name}-{$index}.csv";
            $fulfillment_path = $custom_dir . '/' . $fulfillment_filename;
            $greetings_path = $custom_dir . '/' . $greetings_filename;
            $fulfillment_url = $custom_url . '/' . $fulfillment_filename;
            $greetings_url = $custom_url . '/' . $greetings_filename;
            $index++;
        } while (file_exists($fulfillment_path) || file_exists($greetings_path));

        $fulfillment_file = fopen($fulfillment_path, 'w');
        $greetings_file = fopen($greetings_path, 'w');

        if (!$fulfillment_file || !$greetings_file) {
            wp_send_json_error(['message' => 'Unable to create CSVs.']);
        }

        fputcsv($fulfillment_file, [
            'Status', 'ORDERID', 'RECIPIENTNO', 'JARNO', 'JARQTY',
            'ORDERTYPE', 'VOUCHER', 'RECIPNAME', 'RECIPCOMPANY', 'RECIPADDRESS',
            'RECIPADDRESS 2', 'RECIPCITY', 'RECIPState', 'RECIPZIP', 'RECIPCountry',
            'Greeting', 'Bottom of card = In celebration of',
            'Organization name (stub)', 'Code', 'Organization name (front of card)'
        ]);

        fputcsv($greetings_file, [
            'ORDERID', 'RECIPIENTNO', 'JARNO', 'Greeting'
        ]);

        fclose($fulfillment_file);
        fclose($greetings_file);

        set_transient($fulfillment_key, [
            'file_path' => $fulfillment_path,
            'file_url' => $fulfillment_url,
            'filename' => $fulfillment_filename,
        ], HOUR_IN_SECONDS);

        set_transient($greetings_key, [
            'file_path' => $greetings_path,
            'file_url' => $greetings_url,
            'filename' => $greetings_filename,
        ], HOUR_IN_SECONDS);
    } else {
        $fulfillment_data = get_transient($fulfillment_key);
        $greetings_data = get_transient($greetings_key);
        if (empty($fulfillment_data) || empty($greetings_data)) {
            wp_send_json_error(['message' => 'Session expired. Please restart the export.']);
        }

        $fulfillment_path = $fulfillment_data['file_path'];
        $greetings_path = $greetings_data['file_path'];
        $fulfillment_url = $fulfillment_data['file_url'];
        $greetings_url = $greetings_data['file_url'];
        $fulfillment_filename = $fulfillment_data['filename'];
        $greetings_filename = $greetings_data['filename'];
    }

    $order_ids = $wpdb->get_col($wpdb->prepare("
        SELECT ID FROM {$wpdb->prefix}wc_orders
        WHERE status IN ('wc-completed', 'wc-processing')
        AND date_created_gmt BETWEEN %s AND %s
        ORDER BY date_created_gmt ASC
    ", $start_str, $end_str));

    if (empty($order_ids)) {
        wp_send_json_error(['message' => 'No orders found.']);
    }

    $total_orders = count($order_ids);
    $chunk_ids = array_slice($order_ids, $offset, $limit);

    if (empty($chunk_ids)) {
        $email_sent = false;
        if (!empty($email_list) && file_exists($fulfillment_path)) {
            $subject = 'Fulfillment Report: ' . $start_date->format('M d, Y') . ' - ' . $end_date->format('M d, Y');
            ob_start();
            wc_get_template('emails/fulfillment-report-email.php', [
                'date_range' => $date_range,
                'order_count' => $total_orders,
            ]);
            $message = ob_get_clean();

            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $attachments = [$fulfillment_path, $greetings_path];
            $email_sent = wp_mail($email_list, $subject, $message, $headers, $attachments);
        }

        wp_send_json_success([
            'done' => true,
            'progress' => 100,
            'fulfillment_url' => $fulfillment_url,
            'greetings_url' => $greetings_url,
            'filenames' => [
                'fulfillment' => $fulfillment_filename,
                'greetings' => $greetings_filename,
            ],
            'email_sent' => !empty($email_sent),
            'file_exists' => file_exists($fulfillment_path),
            'file_size' => filesize($fulfillment_path),
        ]);
    }

    $placeholders = implode(',', array_fill(0, count($chunk_ids), '%d'));
    $query = $wpdb->prepare("
        SELECT 
            o.ID AS wc_order_id,
            rel.order_id AS custom_order_id,
            rel.affiliate_code,
            rel.quantity
        FROM {$wpdb->prefix}wc_orders o
        INNER JOIN {$wpdb->prefix}oh_wc_order_relation rel ON o.ID = rel.wc_order_id
        WHERE o.ID IN ($placeholders)
        ORDER BY o.date_created_gmt ASC
    ", ...$chunk_ids);

    $results = $wpdb->get_results($query, ARRAY_A);
    $fulfillment_output = fopen($fulfillment_path, 'a');
    $greetings_output = fopen($greetings_path, 'a');

    if (!$fulfillment_output || !$greetings_output) {
        wp_send_json_error(['message' => 'Unable to open CSVs for writing.']);
    }

    foreach ($results as $row) {
        $wc_order_id = $row['wc_order_id'];
        $coupon_codes = OAM_AFFILIATE_Helper::get_applied_coupon_codes_from_order($wc_order_id);
        $coupons = array_filter(array_diff(explode(',', $coupon_codes), $exclude_coupon));

        $row['affiliate_code'] = trim($row['affiliate_code']);
        if ($row['affiliate_code'] === '' || strtolower($row['affiliate_code']) === 'orthoney') {
            $row['affiliate_code'] = 'Honey from the Heart';
            $row['affiliate_name'] = 'Honey from the Heart';
            $row['affiliate_full_card'] = get_field('honey_from_the_heart_gift_card', 'option') ?: '';
            $row['affiliate_full_card_name'] = 'In celebration of the New Year, a donation has been made in your name to ' . $row['affiliate_full_card'];
        } else {
            $affiliate_user_id = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE token = %s",
                $row['affiliate_code']
            ));
            if ($affiliate_user_id) {
                $orgName = get_user_meta($affiliate_user_id, '_yith_wcaf_name_of_your_organization', true);
                $row['affiliate_name'] = $orgName ?: '';
                $row['affiliate_full_card'] = get_user_meta($affiliate_user_id, 'gift_card', true) ?: $orgName;
                $row['affiliate_full_card_name'] = 'In celebration of the New Year, a donation has been made in your name to ' . $row['affiliate_full_card'];
            }
        }

        $recipient_rows = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}oh_recipient_order
            WHERE order_id = %d AND order_id != %d
            GROUP BY recipient_order_id
        ", $row['custom_order_id'], 0), ARRAY_A);

        foreach ($recipient_rows as $recipient) {
            $recipient_qty = (int) $recipient['quantity'];
            $jar_query = $recipient_qty > 6 ? "GROUP BY recipient_order_id" : "GROUP BY jar_order_id";

            $jar_rows = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$wpdb->prefix}oh_wc_jar_order
                WHERE recipient_order_id = %s AND order_id != %d
                $jar_query
            ", $recipient['recipient_order_id'], 0), ARRAY_A);

            foreach ($jar_rows as $jar) {
                $line = [
                    ucwords(strtolower($jar['order_type'])),
                    $row['custom_order_id'],
                    $recipient['recipient_order_id'],
                    ($jar['order_type'] == 'external' ? $jar['jar_order_id'] : ''),
                    ($jar['order_type'] == 'external' ? 1 : $jar['quantity']),
                    (!empty($coupons) ? 'Wholesale' : 'Retail'),
                    (!empty($coupons) ? implode(', ', $coupons) : ''),
                    $recipient['full_name'],
                    $recipient['company_name'],
                    $recipient['address_1'],
                    $recipient['address_2'],
                    $recipient['city'],
                    $recipient['state'],
                    $recipient['zipcode'],
                    $recipient['country'],
                    ($jar['order_type'] != 'external' ? '' : $recipient['greeting']),
                    $row['affiliate_full_card_name'],
                    $row['affiliate_name'],
                    $row['affiliate_code'],
                    $row['affiliate_full_card'],
                ];
                fputcsv($fulfillment_output, array_map(fn($v) => mb_convert_encoding($v ?? '', 'UTF-8', 'auto'), $line));

                if($jar['order_type'] == 'internal') {
                    $greeting_line = [
                        $row['custom_order_id'],
                        $recipient['recipient_order_id'],
                        $jar['jar_order_id'],
                        $recipient['greeting'],
                    ];
                    fputcsv($greetings_output, array_map(fn($v) => mb_convert_encoding($v ?? '', 'UTF-8', 'auto'), $greeting_line));
                }
            }
        }
    }

    fclose($fulfillment_output);
    fclose($greetings_output);

    $processed = min($offset + $limit, $total_orders);
    $progress = round(($processed / $total_orders) * 100);

    wp_send_json_success([
        'progress' => $progress,
        'done' => false,
        'processed' => $processed,
        'total' => $total_orders,
        'file_exists' => file_exists($fulfillment_path),
        'file_size' => filesize($fulfillment_path),
    ]);
}


    public function orthoney_switch_org_to_order_callback() {
         check_ajax_referer('oam_nonce', 'security');
        global $wpdb;

        $wc_order_relation     = $wpdb->prefix . 'oh_wc_order_relation';
        $order_process_table   = $wpdb->prefix . 'oh_order_process';
        $recipient_order       = $wpdb->prefix . 'oh_recipient_order';
        $order_meta            = $wpdb->prefix . 'wc_order_meta';

        // Sanitize and validate POST values
        $org_token    = isset($_POST['org_token']) && !empty($_POST['org_token']) ? sanitize_text_field($_POST['org_token']) : 'Orthoney';
        $org_user_id  = isset($_POST['org_user_id']) ? intval($_POST['org_user_id']) : 0;
        $wc_order_id  = isset($_POST['wc_order_id']) ? intval($_POST['wc_order_id']) : 0;
        $order_id     = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        if($wc_order_id == 0 OR $order_id == 0){
             wp_send_json_error(['message' => 'The order number does not match. Please try again.']);
        }

        $wc_order_check = wc_get_order($wc_order_id);
        if (!$wc_order_check) {
            wp_send_json_error(['message' => 'The order number does not match. Please try again.']);
        }

        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT data FROM {$order_process_table} WHERE order_id = %d", $wc_order_id)
        );
        $json_data = $result->data ?? '';
        $decoded_data = json_decode($json_data, true);
        $decoded_data['affiliate_select'] = $org_user_id;

        // 1. Order Process on update affiliate select value on data

        $update_result = $wpdb->update(
            $order_process_table,
            [
                'data'       => wp_json_encode($decoded_data),
            ],
            ['order_id' => $wc_order_id]
        );

        // 2. update affiliate_code and affiliate_user_id form wc_order_relation table
        $wc_order_relation_result = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wc_order_relation} WHERE wc_order_id= %d", $wc_order_id)
        );

        if(!empty($wc_order_relation_result)){
            $update_result = $wpdb->update(
                $wc_order_relation,
                [
                     'affiliate_code' => $org_token,
                     'affiliate_user_id' => $org_user_id,
                ],
                [
                    'wc_order_id' => $wc_order_id,
                ]
            );
        }

        // 3. $recipient_order
        $recipient_orderresult = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$recipient_order} WHERE order_id = %s", $order_id)
        );

        if(!empty($recipient_orderresult)){
            $update_result = $wpdb->update(
                $recipient_order,
                [
                     'affiliate_token' => $org_token,
                ],
                [
                    'order_id' => $order_id,
                ]
            );
        }

        // 4. Order meta.
        $update_result = $wpdb->update(
            $order_meta,
            [
                    'id' => $wc_order_id,
            ],
            [
                '_orthoney_OrderID' => $order_id,
            ]
        );

        $order = wc_get_order($wc_order_id);
        if($org_token == 'Orthoney'){
              $order->update_meta_data('affiliate_account_status', 1);
        }else{
            $activate_affiliate_account = get_user_meta($org_user_id, 'activate_affiliate_account', true) ?: 0;

            if($activate_affiliate_account == 0){
                $order->update_meta_data('affiliate_account_status', 0);
            }else{
                $order->update_meta_data('affiliate_account_status', 1);
            }
        }
         $order->save();
        

        wp_send_json_success(['message' => 'You have successfully switched to the selected organization.']);


    }
    
    public function orthoney_get_org_details_base_id_callback() {
        check_ajax_referer('oam_nonce', 'security');

        global $wpdb;
         $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;

        $org_id = isset($_POST['org_id']) ? intval($_POST['org_id']) : 0;

        if (!$org_id) {
            wp_send_json_error(['message' => 'Invalid Organization ID']);
        }

        $yith_wcaf_affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';
            $affiliate_token = $wpdb->get_var($wpdb->prepare(
                "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d",
                $org_id
            ));

        $meta_fields = [
            'name_of_your_organization' => '_yith_wcaf_name_of_your_organization',
            'organizations_website'     => '_yith_wcaf_your_organizations_website',
            'phone_number'              => '_yith_wcaf_phone_number',
            'address'                   => '_yith_wcaf_address',
            'mission_statement'         => 'mission_statement',
            'gift_card'                 => 'gift_card',
            'city'                      => '_yith_wcaf_city',
            'state'                     => '_yith_wcaf_state',
            'zipcode'                   => '_yith_wcaf_zipcode',
            'tax_id'                    => '_yith_wcaf_tax_id',
            'check_payable'             => '_yith_wcaf_check_payable',
            'address_check'             => '_yith_wcaf_address_check',
            'attention'                 => '_yith_wcaf_attention',
            'check_mailed_address'      => '_yith_wcaf_check_mailed_address',
            'product_price'             => 'DJarPrice',
        ];

        $org_data = [];

        foreach ($meta_fields as $key => $meta_key) {
            $org_data[$key] = sanitize_text_field(get_user_meta($org_id, $meta_key, true));
        }

        if (empty($org_data['name_of_your_organization'])) {
            wp_send_json_error(['message' => 'Organization not found or missing name.']);
        }

        $address_parts = [];

        if (!empty($org_data['address'])) {
            $address_parts[] = $org_data['address'];
        }

        $city_state_zip = trim(
            $org_data['city'] . 
            (!empty($org_data['state']) ? ', ' . $org_data['state'] : '') . 
            (!empty($org_data['zipcode']) ? ' ' . $org_data['zipcode'] : '')
        );

        if (!empty($city_state_zip)) {
            $address_parts[] = $city_state_zip;
        }

        $full_address = implode(' ', $address_parts);

        $response = [
            'org_name'            => $org_data['name_of_your_organization'] . ($affiliate_token ? ' [' . $affiliate_token . ']' : ''),
            'website'             => $org_data['organizations_website'],
            'phone'               => $org_data['phone_number'],
            'mission'             => $org_data['mission_statement'],
            'gift_card'           => $org_data['gift_card'],
            'product_price' => (is_numeric($org_data['product_price']) && $org_data['product_price'] > 0) ? $org_data['product_price'] : $selling_minimum_price,
            'tax_id'              => $org_data['tax_id'],
            'check_payable'       => $org_data['check_payable'],
            'address_check'       => $org_data['address_check'],
            'attention'           => $org_data['attention'],
            'check_mailed_address'=> $org_data['check_mailed_address'],
            'full_address'        => $full_address,
        ];

        wp_send_json_success($response);
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

         $organization_search = strtolower(stripslashes($_POST['organization_search'] ?? ''));
        $organization_code_search = strtolower(stripslashes($_POST['organization_code_search'] ?? ''));

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
                AND LOWER(meta_value) LIKE %s
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

        $organization_search = stripslashes($_POST['organization_search'] ?? '');
        $organization_code_search = stripslashes($_POST['organization_code_search'] ?? '');

        $search_conditions = [];
        $search_params = [];

        if (!empty($organization_search)) {
            $search_conditions[] = "(
                COALESCE(org_name.meta_value, CONCAT(first_name.meta_value, ' ', last_name.meta_value)) LIKE %s
            )";
            $search_params[] = '%' . $wpdb->esc_like(strtolower($organization_search)) . '%';
        }

        if (!empty($organization_code_search)) {
            $search_conditions[] = "af.token LIKE %s";
            $search_params[] = '%' . $wpdb->esc_like(strtolower($organization_code_search)) . '%';
        }

        if (!empty($search)) {
            $search_conditions[] = "(
                COALESCE(org_name.meta_value, CONCAT(first_name.meta_value, ' ', last_name.meta_value)) LIKE %s OR
                COALESCE(CONCAT(first_name1.meta_value, ' ', last_name1.meta_value)) LIKE %s OR
                COALESCE(city1.meta_value, city2.meta_value, city3.meta_value) LIKE %s OR
                COALESCE(state1.meta_value, state2.meta_value, state3.meta_value) LIKE %s OR
                COALESCE(phone.meta_value, phone1.meta_value, phone2.meta_value) LIKE %s OR
                COALESCE(email1.meta_value) LIKE %s OR
                COALESCE(address.meta_value) LIKE %s OR
                af.token LIKE %s OR
                u.user_email LIKE %s
            )";
            $search_like = '%' . $wpdb->esc_like(strtolower($search)) . '%';
            $search_params = array_merge($search_params, array_fill(0, 8, $search_like));
        }

        $where_clause = '';
        if (!empty($search_conditions)) {
            $where_clause = 'AND (' . implode(' AND ', $search_conditions) . ')';
        }

        $total_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}yith_wcaf_affiliates af 
            WHERE af.token != '' AND af.user_id != 0
        ");

        $query = "
            SELECT 
                af.user_id,
                af.token,
                af.enabled,
                af.banned,
                u.user_email,
                COALESCE(org_name.meta_value, CONCAT(first_name.meta_value, ' ', last_name.meta_value)) as organization,
                phone.meta_value as phone
            FROM {$wpdb->prefix}yith_wcaf_affiliates af
            LEFT JOIN {$wpdb->users} u ON af.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} org_name ON af.user_id = org_name.user_id AND org_name.meta_key = '_yith_wcaf_name_of_your_organization'
            LEFT JOIN {$wpdb->usermeta} first_name ON af.user_id = first_name.user_id AND first_name.meta_key = 'first_name'
            LEFT JOIN {$wpdb->usermeta} email1 ON af.user_id = email1.user_id AND email1.meta_key = '_yith_wcaf_email'
            LEFT JOIN {$wpdb->usermeta} first_name1 ON af.user_id = first_name1.user_id AND first_name1.meta_key = '_yith_wcaf_first_name'
            LEFT JOIN {$wpdb->usermeta} last_name ON af.user_id = last_name.user_id AND last_name.meta_key = 'last_name'
            LEFT JOIN {$wpdb->usermeta} last_name1 ON af.user_id = last_name1.user_id AND last_name1.meta_key = '_yith_wcaf_last_name'
            LEFT JOIN {$wpdb->usermeta} phone ON af.user_id = phone.user_id AND phone.meta_key = '_yith_wcaf_phone_number'
            LEFT JOIN {$wpdb->usermeta} phone1 ON af.user_id = phone1.user_id AND phone1.meta_key = 'billing_phone'
            LEFT JOIN {$wpdb->usermeta} phone2 ON af.user_id = phone2.user_id AND phone2.meta_key = 'shipping_phone'
            LEFT JOIN {$wpdb->usermeta} address ON af.user_id = address.user_id AND address.meta_key = '_yith_wcaf_address'
            LEFT JOIN {$wpdb->usermeta} city1 ON af.user_id = city1.user_id AND city1.meta_key = '_yith_wcaf_city'
            LEFT JOIN {$wpdb->usermeta} city2 ON af.user_id = city2.user_id AND city2.meta_key = 'billing_city'
            LEFT JOIN {$wpdb->usermeta} city3 ON af.user_id = city3.user_id AND city3.meta_key = 'shipping_city'
            LEFT JOIN {$wpdb->usermeta} state1 ON af.user_id = state1.user_id AND state1.meta_key = '_yith_wcaf_state'
            LEFT JOIN {$wpdb->usermeta} state2 ON af.user_id = state2.user_id AND state2.meta_key = 'billing_state'
            LEFT JOIN {$wpdb->usermeta} state3 ON af.user_id = state3.user_id AND state3.meta_key = 'shipping_state'
            WHERE af.token != '' AND af.user_id != 0 {$where_clause}
        ";

        if (!empty($search_params)) {
            $query = call_user_func_array([$wpdb, 'prepare'], array_merge([$query], $search_params));
             echo $query;
        }

       
        $raw_users = $wpdb->get_results($query);

        if (empty($raw_users)) {
            wp_send_json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $recordsFiltered = count($raw_users);

        $order_column_index = $_POST['order'][0]['column'] ?? 0;
        $order_direction = $_POST['order'][0]['dir'] ?? 'asc';

        $columns = ['token', 'user_email', 'organization', 'city', 'state', 'status'];
        $orderby_key = $columns[$order_column_index] ?? 'token';

        usort($raw_users, function ($a, $b) use ($orderby_key, $order_direction) {
            if ($orderby_key === 'status') {
                $a_val = $a->banned === '1' ? 'Banned' : ($a->enabled === '1' ? 'Accepted and enabled' : ($a->enabled === '-1' ? 'Rejected' : 'New request'));
                $b_val = $b->banned === '1' ? 'Banned' : ($b->enabled === '1' ? 'Accepted and enabled' : ($b->enabled === '-1' ? 'Rejected' : 'New request'));
            } else {
                $a_val = $a->$orderby_key ?? '';
                $b_val = $b->$orderby_key ?? '';
            }
            $comparison = strnatcasecmp($a_val, $b_val);
            return $order_direction === 'asc' ? $comparison : -$comparison;
        });

        $paged_users = array_slice($raw_users, $start, $length);

        $data = [];
        foreach ($paged_users as $user) {
            $user_id = intval($user->user_id);
            $enabled = intval($user->enabled);
            $banned = intval($user->banned);

            $status = 'New request';
            if ($banned === 1) $status = 'Banned';
            elseif ($enabled === 1) $status = 'Accepted and enabled';
            elseif ($enabled === -1) $status = 'Rejected';

            $organizationdata = [];
            $organization = $user->organization ?: '';
            $city = get_user_meta($user_id, '_yith_wcaf_city', true) ?: get_user_meta($user_id, 'billing_city', true) ?: get_user_meta($user_id, 'shipping_city', true);
            $state = get_user_meta($user_id, '_yith_wcaf_state', true) ?: get_user_meta($user_id, 'billing_state', true) ?: get_user_meta($user_id, 'shipping_state', true);
            $phone = get_user_meta($user_id, '_yith_wcaf_phone_number', true) ?: get_user_meta($user_id, 'billing_phone', true) ?: get_user_meta($user_id, 'shipping_phone', true);
            $email = get_user_meta($user_id, '_yith_wcaf_email', true) ?: $user_obj->user_email ?: '';

            if (!empty($organization)) $organizationdata[] = '<strong> ['.esc_html($user->token).'] ' . esc_html($organization) . '</strong>';
            $organizationdata[] = esc_html($email);
            if (!empty($phone)) $organizationdata[] = esc_html($phone);

            $organization_display = implode('<br>', array_filter($organizationdata));
            $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate_base_token($user->token);

            $unit_profit = wc_price(0);
            if($commission_array['unit_profit'] != 0){
                $unit_profit = wc_price($commission_array['unit_profit']) . '<br><small>( '.wc_price($commission_array["product_price"]).' - '.wc_price($commission_array["unit_cost"]).' )</small>';
            }

            $cost = '';
            if($commission_array['total_all_quantity'] < 50 && $commission_array['total_order'] != 0){
                $cost = '<strong>Total: </strong>'. wc_price(0);
                $cost .= '<br><small><strong>Fundraising: </strong>'. wc_price(0);
                $cost .= '<br><strong>Wholesale: </strong>'. wc_price(0) . '</small>';
            } elseif($commission_array['total_order'] != 0) {
                $cost = '<strong>Total: </strong>'. wc_price($commission_array['ort_cost']);
                $cost .= '<br><small><strong>Fundraising: </strong>'. wc_price($commission_array['fundraising_cost']);
                $cost .= '<br><strong>Wholesale: </strong>'. wc_price($commission_array['wholesale_cost']) . '</small>';
            }

            $dist_cost = '';
            if($commission_array['total_all_quantity'] < 50 && $commission_array['total_order'] != 0){
                $dist_cost = '<strong>Total: </strong>'. wc_price(0);
                $dist_cost .= '<br><small><strong>Fundraising: </strong>'. wc_price(0);
                $dist_cost .= '<br><strong>Wholesale: </strong>'. wc_price(0) . '</small>';
            } elseif($commission_array['total_order'] != 0) {
                $dist_cost = '<strong>Total: </strong>'. wc_price($commission_array['ort_dist']);
                $dist_cost .= '<br><small><strong>Fundraising: </strong>'. wc_price($commission_array['fundraising_dist']);
                $dist_cost .= '<br><strong>Wholesale: </strong>'. wc_price($commission_array['wholesale_dist']) . '</small>';
            }

            $data[] = [
                'organization'          => $organization_display,
                'new_organization'      => 'Yes',
                'status'                => esc_html($status),
                'cost'                  => $cost,
                'dist_cost'             => $dist_cost,
                'selling_min_price'     => esc_html($commission_array['selling_min_price']),
                'total_order'           => esc_html($commission_array['total_order']),
                'total_qty'             => esc_html($commission_array['total_all_quantity']),
                'wholesale_qty'         => esc_html($commission_array['wholesale_qty']),
                'fundraising_qty'       => esc_html($commission_array['fundraising_qty']),
                'fundraising_orders'    => esc_html($commission_array['fundraising_orders']),
                'total_all_quantity'    => esc_html($commission_array['total_all_quantity']),
                'unit_cost'             => esc_html($commission_array['unit_cost']),
                'unit_profit'           => $unit_profit,
                'total_commission'      => ($commission_array['total_all_quantity'] < 50) ? wc_price(0) : wc_price($commission_array['total_commission']),
            ];
        }

        wp_send_json([
            'draw'            => $draw,
            'recordsTotal'    => $total_count,
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

       $recordsTotal    = count(array_unique($user_ids));
        $recordsFiltered = count(array_unique($filtered_user_ids));

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
        $paged_user_ids = array_unique(array_slice(array_values($filtered_user_ids), $start, $length));

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
                    'login' => '<button class="customer-login-btn icon-txt-btn" data-user-id="' . intval($user_id) . '" data-nonce="' . esc_attr($nonce) . '"><img src="' . OH_PLUGIN_DIR_URL . 'assets/image/login-customer-icon.png"> Login As Org</button><a href="' . $admin_url . '" class="icon-txt-btn"><img src="' . OH_PLUGIN_DIR_URL . '/assets/image/user-avatar.png">Edit Org Prf</a><button class="view_order_details icon-txt-btn" data-popup="#view_org_details_popup" data-org-id="' . intval($user_id) . '"><i class="far fa-eye"></i>View Org Details</button>'
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