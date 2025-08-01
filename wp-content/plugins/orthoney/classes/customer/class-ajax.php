<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Dompdf\Dompdf;
use Dompdf\Options;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;


class OAM_Ajax{
    
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
    
        // Done 
        add_action( 'wp_ajax_orthoney_order_process_ajax', array( $this, 'orthoney_order_process_ajax_handler' ) );
        add_action( 'wp_ajax_orthoney_order_step_process_ajax', array( $this, 'orthoney_order_step_process_ajax_handler' ) );
        add_action( 'wp_ajax_orthoney_insert_temp_recipient_ajax', array( $this, 'orthoney_insert_temp_recipient_ajax_handler' ) );
        add_action( 'wp_ajax_orthoney_save_csv_temp_recipient_ajax', array( $this, 'orthoney_save_csv_temp_recipient_ajax_handler' ) );
        
        add_action( 'wp_ajax_save_group_orders_recipient_to_order_process', array( $this, 'orthoney_save_group_orders_recipient_to_order_process_handler' ) );

        add_action( 'wp_ajax_orthoney_get_csv_recipient_ajax', array( $this, 'orthoney_get_csv_recipient_ajax_handler') );
        add_action( 'wp_ajax_orthoney_single_address_data_save_ajax', array( $this, 'orthoney_single_address_data_save_ajax_handler') );
		add_action( 'wp_ajax_manage_recipient_form', array( $this, 'orthoney_manage_recipient_form_handler' ) );
		add_action( 'wp_ajax_download_failed_recipient', array( $this, 'orthoney_download_failed_recipient_handler'));
        
		add_action( 'wp_ajax_deleted_recipient', array( $this, 'orthoney_deleted_recipient_handler' ) );
		add_action( 'wp_ajax_bulk_deleted_recipient', array( $this, 'orthoney_bulk_deleted_recipient_handler' ) );
		add_action( 'wp_ajax_get_recipient_base_id', array( $this, 'orthoney_get_recipient_base_id_handler' ) );
		add_action( 'wp_ajax_get_recipient_order_base_id', array( $this, 'orthoney_get_recipient_order_base_id_handler' ) );
		add_action( 'wp_ajax_manage_recipient_order_form', array( $this, 'orthoney_manage_recipient_order_form_handler' ) );

		add_action( 'wp_ajax_reverify_address_recipient', array( $this, 'orthoney_reverify_address_recipient_handler' ) );

        add_action( 'wp_ajax_orthoney_order_step_process_completed_ajax', array( $this, 'orthoney_order_step_process_completed_ajax_handler') );
        
        add_action( 'wp_ajax_edit_process_name', array( $this, 'orthoney_edit_process_name_handler' ) );
        add_action( 'wp_ajax_keep_this_and_delete_others_recipient', array( $this, 'orthoney_keep_this_and_delete_others_recipient_handler' ) );

        add_action( 'wp_ajax_affiliate_status_toggle_block', array( $this, 'orthoney_affiliate_status_toggle_block_handler' ) );
        
        add_action('wp_ajax_search_affiliates', array( $this,'search_affiliates_handler'));

        add_action( 'wp_ajax_orthoney_incomplete_order_process_ajax', array( $this, 'orthoney_incomplete_order_process_ajax_handler' ) );
        add_action('wp_ajax_orthoney_group_recipient_list_ajax', array( $this, 'orthoney_group_recipient_list_ajax_handler'));
        add_action( 'wp_ajax_orthoney_groups_ajax', array( $this, 'orthoney_groups_ajax_handler' ) );
        // Done
        
		add_action( 'wp_ajax_delete_incompleted_order_button', array( $this, 'orthoney_delete_incompleted_order_button_handler' ) );
		add_action( 'wp_ajax_deleted_group', array( $this, 'orthoney_deleted_group_handler' ) );
		add_action( 'wp_ajax_orthoney_customer_order_process_ajax', array( $this, 'orthoney_customer_order_process_ajax_handler' ) );
        add_action('wp_ajax_remove_pdf_data',  array( $this,'remove_pdf_data_handler'));
        
        add_action('wp_ajax_orthoney_customer_order_export_pdf_ajax',  array( $this,'orthoney_customer_order_export_ajax_pdf_handler'));

		add_action('wp_ajax_orthoney_customer_order_export_ajax',  array( $this,'orthoney_customer_order_export_ajax_handler'));
		add_action('wp_ajax_orthoney_customer_order_export_by_id_ajax',  array( $this,'orthoney_customer_order_export_by_id_ajax_handler'));
        add_action('wp_ajax_download_generated_csv',  array( $this,'download_generated_csv_handler'));

		add_action( 'wp_ajax_customer_sub_order_details_ajax', array( $this, 'customer_sub_order_details_ajax_handler' ) );
        
        // TODO
        add_action( 'wp_ajax_orthoney_process_to_checkout_ajax', array( $this, 'orthoney_process_to_checkout_ajax_handler' ) );
        add_action( 'wp_ajax_get_alreadyorder_popup', array( $this, 'get_alreadyorder_popup_handler' ) );
        add_action( 'wp_ajax_remove_recipients_already_order_this_year', array( $this, 'remove_recipients_already_order_this_year_handler' ) );
        add_action( 'wp_ajax_create_group', array( $this, 'orthoney_create_group_handler' ) );

        //db 
        add_action( 'wp_ajax_orthoney_get_customers_autocomplete', array( $this, 'orthoney_get_customers_autocomplete_handler' ) );

        add_action( 'wp_ajax_orthoney_get_used_affiliate_codes', array( $this, 'orthoney_get_used_affiliate_codes') );
        add_action( 'wp_ajax_wc_re_order_customer_dashboard', array( $this, 'wc_re_order_customer_dashboard_handler') );
        add_action( 'wp_ajax_get_billing_details_base_order_id', array( $this, 'get_billing_details_base_order_id_handler') );
        add_action( 'wp_ajax_update_billing_details', array( $this, 'update_billing_details_handler') );


        // TODO

    }

    public function update_billing_details_handler() {
        check_ajax_referer('oam_nonce', 'security');

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;

        if (!$order_id) {
            wp_send_json_error(['message' => 'Invalid order ID.']);
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        // Sanitize and get the billing fields from $_POST
        $billing_data = [
            'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
            'last_name'  => sanitize_text_field($_POST['last_name'] ?? ''),
            'company'    => sanitize_text_field($_POST['company'] ?? ''),
            'address_1'  => sanitize_text_field($_POST['address_1'] ?? ''),
            'address_2'  => sanitize_text_field($_POST['address_2'] ?? ''),
            'city'       => sanitize_text_field($_POST['city'] ?? ''),
            'state'      => sanitize_text_field($_POST['state'] ?? ''),
            'postcode'   => sanitize_text_field($_POST['zipcode'] ?? ''),
            'phone_number'   => sanitize_text_field($_POST['phone_number'] ?? ''),
        ];

        // Update billing fields
        $order->set_billing_first_name($billing_data['first_name']);
        $order->set_billing_last_name($billing_data['last_name']);
        $order->set_billing_company($billing_data['company']);
        $order->set_billing_address_1($billing_data['address_1']);
        $order->set_billing_address_2($billing_data['address_2']);
        $order->set_billing_city($billing_data['city']);
        $order->set_billing_state($billing_data['state']);
        $order->set_billing_postcode($billing_data['postcode']);
        $order->set_billing_phone($billing_data['phone_number']);

        $order->save();

        wp_send_json_success(['message' => 'Billing address updated successfully.']);
    }


    public function get_billing_details_base_order_id_handler() {
        check_ajax_referer('oam_nonce', 'security');

        $order_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if (!$order_id) {
            wp_send_json_error(['message' => 'Invalid order ID.']);
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found.']);
        }

        $billing_details = [
            'order_id' =>  $order_id,
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
            'company'    => $order->get_billing_company(),
            'address_1'  => $order->get_billing_address_1(),
            'address_2'  => $order->get_billing_address_2(),
            'city'       => $order->get_billing_city(),
            'state'      => $order->get_billing_state(),
            'zipcode'   => $order->get_billing_postcode(),
            'phone_number'   => $order->get_billing_phone(),
        ];

        wp_send_json_success($billing_details);
    }

    public function wc_re_order_customer_dashboard_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;

        $recipient_order_table = OAM_Helper::$recipient_order_table;
        $order_process_table   = OAM_Helper::$order_process_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;

        $userID    = isset($_POST['userID']) ? (int) $_POST['userID'] : 0;
        $orderid   = isset($_POST['orderid']) ? (int) $_POST['orderid'] : 0;
        $security  = sanitize_text_field($_POST['security'] ?? '');
        $process_by = OAM_COMMON_Custom::old_user_id();
        $order_type = 'multi-recipient-order';

        $wc_order = wc_get_order($orderid);

        if ( ! $wc_order ) {
            wp_send_json_error(['message' => 'Order ID not found. Please provide another order.']);
        }

        if ( in_array($wc_order->get_status(), ['draft', 'failed'], true) ) {
            wp_send_json_error(['message' => 'The order is in ' . $wc_order->get_status() . ' status, so you cannot create a re-order.']);
        }

        $custom_order_id = OAM_COMMON_Custom::get_order_meta($orderid, '_orthoney_OrderID');

        $affiliate_token = 'Orthoney';
        $affiliate_id = 0;
        $affiliate_data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT r.affiliate_code, a.user_id
                FROM {$wpdb->prefix}oh_wc_order_relation r
                LEFT JOIN {$wpdb->prefix}yith_wcaf_affiliates a
                ON r.affiliate_code = a.token
                WHERE r.wc_order_id = %d",
                $orderid
            ),
            ARRAY_A
        );

        if(!empty($affiliate_data)){
            $affiliate_token = $affiliate_data['affiliate_code'] ?? 'Orthoney';
            $affiliate_id = $affiliate_data['user_id'] ?? 0;
        }

        $recipient_data = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $recipient_order_table WHERE order_id = %d", $custom_order_id)
        );

        $data = [
            'user_id'    => $userID,
            'process_by' => $process_by,
            'created'    => current_time('mysql'),
            'modified'   => current_time('mysql'),
            'user_agent' => OAM_Helper::get_user_agent(),
            'user_ip'    => OAM_Helper::get_user_ip(),
        ];

        $insert_result = $wpdb->insert($order_process_table, $data);

        if ( $insert_result === false ) {
            wp_send_json_error(['message' => 'Failed to create reorder process. Please try again.']);
        }

        $process_id = $wpdb->insert_id;
        $total_quantity = 0;
        $greeting = 0;

        foreach ($wc_order->get_items() as $item) {
            $quantity = (int) $item->get_quantity();
            $greeting = $item->get_meta('greeting', true) ?: '';
            $total_quantity += $quantity;
        }

        $updateData = [
            'name'                    => sanitize_text_field('Re Order ' . $custom_order_id),
            'modified'                => current_time('mysql'),
            'affiliate_select'        => $affiliate_id,
            'delivery_preference'     => 'multiple_address',
            'single_address_quantity' => 1,
            'single_address_greeting' => '',
            'multiple_address_output' => '',
            'upload_type_output'      => '',
            'csv_name'                => '',
            'greeting'                => $greeting,
            'action'                  => 'orthoney_order_process_ajax',
            'currentStep'             => 3,
            'security'                => $security,
        ];

        $update_result = $wpdb->update(
            $order_process_table,
            [
                'data'       => wp_json_encode($updateData),
                'name'       => sanitize_text_field('Re Order ' . $custom_order_id),
                'order_type' => $order_type,
                'step'       => sanitize_text_field(3),
            ],
            ['id' => $process_id]
        );

        if ( $update_result === false ) {
            wp_send_json_error(['message' => 'Failed to update reorder data. Please contact support.']);
        }

        // Insert recipients
        if ( empty($recipient_data) ) {
            $recipient_inserted = $wpdb->insert($order_process_recipient_table, [
                'user_id'          => $userID,
                'pid'              => $process_id,
                'full_name'        => sanitize_text_field($wc_order->get_shipping_first_name() .' '. $wc_order->get_shipping_last_name()),
                'company_name'     => sanitize_text_field($wc_order->get_shipping_company()),
                'address_1'        => sanitize_textarea_field($wc_order->get_shipping_address_1()),
                'address_2'        => sanitize_textarea_field($wc_order->get_shipping_address_2()),
                'city'             => sanitize_text_field($wc_order->get_shipping_city()),
                'state'            => sanitize_text_field($wc_order->get_shipping_state()),
                'zipcode'          => sanitize_text_field($wc_order->get_shipping_postcode()),
                'quantity'         => max(1, intval($total_quantity)),
                'greeting'         => sanitize_textarea_field($greeting),
                'verified'         => 1,
                'update_type'      => sanitize_text_field('re-order'),
                'address_verified' => 0,
                'new'              => 0,
                'reasons'          => null,
            ]);

            if ( $recipient_inserted ) {
                $recipient_id = $wpdb->insert_id;
                OAM_Helper::order_process_recipient_activate_log($recipient_id, "new by reorder $custom_order_id", 'added', 'reorder');
            }

        } else {
            foreach ($recipient_data as $recipient) {
                $insert_data = [
                    'user_id'          => $userID,
                    'pid'              => intval($process_id),
                    'full_name'        => sanitize_text_field($recipient->full_name),
                    'company_name'     => sanitize_text_field($recipient->company_name),
                    'address_1'        => sanitize_textarea_field($recipient->address_1),
                    'address_2'        => sanitize_textarea_field($recipient->address_2),
                    'city'             => sanitize_text_field($recipient->city),
                    'state'            => sanitize_text_field($recipient->state),
                    'zipcode'          => sanitize_text_field($recipient->zipcode),
                    'quantity'         => max(1, intval($recipient->quantity ?? 1)),
                    'greeting'         => sanitize_textarea_field($recipient->greeting),
                    'verified'         => 1,
                    'update_type'      => sanitize_text_field('re-order'),
                    'address_verified' => 0,
                    'new'              => 0,
                    'reasons'          => null,
                ];

                $recipient_inserted = $wpdb->insert($order_process_recipient_table, $insert_data);
                if ( $recipient_inserted ) {
                    $recipient_id = $wpdb->insert_id;
                    OAM_Helper::order_process_recipient_activate_log($recipient_id, "new by reorder $custom_order_id", 'added', 'reorder');
                }
            }
        }

        if ($userID > 0) {
            $this->update_user_meta_from_order($userID, $wc_order);
        }

        if (isset($_COOKIE['yith_wcaf_referral_token'])) {
                setcookie('yith_wcaf_referral_token', $processExistResult, time() + 3600, "/", "", true, true);
            }

            if (isset($_COOKIE['yith_wcaf_referral_history'])) {
                setcookie('yith_wcaf_referral_history', $processExistResult, time() + 3600, "/", "", true, true);
            }

        $redirect_url = esc_url(ORDER_PROCESS_LINK . "?pid=$process_id");
        wp_send_json_success([
            'message'      => 'Please wait, the re-order is in progress.',
            'redirect_url' => $redirect_url,
        ]);

        wp_die();
    }


    public function orthoney_get_customers_autocomplete_handler() {
        $customer = isset($_REQUEST['customer']) ? sanitize_text_field($_REQUEST['customer']) : '';
        $page     = isset($_REQUEST['page']) ? max(1, intval($_REQUEST['page'])) : 1;
        $per_page = 20;
        $offset   = ($page - 1) * $per_page;
        $user_ids = [];

         $args = [
            'role'    => 'customer',
            'search'  => '*' . esc_attr($customer) . '*',
            'orderby' => 'user_email',
            'order'   => 'ASC',
            'number'  => $per_page,
            'offset'  => $offset,
            'fields'  => ['ID', 'display_name', 'user_email'],
        ];

        $uri = $_SERVER['REQUEST_URI'];
        $path = trim(parse_url($uri, PHP_URL_PATH), '/');
        $segments = explode('/', $path);

        $first_segment = isset($segments[0]) ? $segments[0] : '';


        if ($first_segment == 'sales-representative-dashboard') {
            $user_id      = get_current_user_id();
            $current_user = wp_get_current_user();
            $user_roles   = (array) $current_user->roles;

            $select_customer      = get_user_meta($user_id, 'select_customer', true);
            $choose_customer      = get_user_meta($user_id, 'choose_customer', true);
            $select_organization  = get_user_meta($user_id, 'select_organization', true);
            $choose_organization  = get_user_meta($user_id, 'choose_organization', true);

            $choose_ids = array_map('intval', (array) $choose_customer);
            $customer_ids = [];

            if ($select_customer === 'choose_customer') {
                if (in_array('sales_representative', $user_roles)) {
                    if ($select_organization === 'choose_organization') {
                        if (!empty($choose_organization)) {
                            $affiliate_ids = array_filter(array_map('intval', (array) $choose_organization));

                            if (!empty($affiliate_ids)) {
                                $placeholders = implode(',', array_fill(0, count($affiliate_ids), '%d'));
                                $query = "
                                    SELECT linker.customer_id
                                    FROM {$wpdb->prefix}oh_affiliate_customer_linker AS linker
                                    INNER JOIN {$wpdb->prefix}yith_wcaf_affiliates AS aff
                                        ON aff.user_id = linker.affiliate_id
                                    WHERE linker.affiliate_id IN ($placeholders)
                                ";

                                $customer_ids = $wpdb->get_col($wpdb->prepare($query, ...$affiliate_ids));
                            }
                        }
                    } else {
                        // All affiliates, no organization filtering
                        $query = "SELECT customer_id FROM {$wpdb->prefix}oh_affiliate_customer_linker";
                        $customer_ids = $wpdb->get_col($query);
                    }
                }

                // Merge and deduplicate both chosen and linked customer IDs
                $all_customer_ids = array_unique(array_merge($choose_ids, $customer_ids));
            } else {
                $all_customer_ids = $choose_ids;
            }

            if(!empty($all_customer_ids)){
                $args['include'] = $all_customer_ids;
            }else{
                $args['include'] = array(0);
            }
        
        }


        if ($first_segment == 'organization-dashboard') { 
            global $wpdb;

            $all_customer_ids = [];

            $affiliate_user_id = get_current_user_id();
            $affiliat_user_roles = OAM_COMMON_Custom::get_user_role_by_id($affiliate_user_id);
            if (in_array('yith_affiliate', $affiliat_user_roles) || in_array('affiliate_team_member', $affiliat_user_roles) || in_array('administrator', $affiliat_user_roles)) {
                $affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);
                if($affiliate_id == ''){
                     $all_customer_ids = $affiliate_user_id;
                }
            }
            
            if(!empty($all_customer_ids)){
                $args['include'] = $all_customer_ids;
            }else{
                $args['include'] = array(0);
            }
        }

            $user_query = new WP_User_Query($args);

            $users = $user_query->get_results();

        $total_users = $user_query->get_total();

        $response = [];

        foreach ($users as $user) {
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name  = get_user_meta($user->ID, 'last_name', true);
            $full_name  = trim($first_name . ' ' . $last_name);
            $label      = $full_name.' ['.$user->user_email.']' ?: ($user->display_name.' ['.$user->user_email.']' ?: $user->user_email);

            $response[] = [
                'id'    => $user->ID,
                'label' => stripslashes(html_entity_decode($label)),
            ];
        }

        wp_send_json([
            'results'    => $response,
            'pagination' => ['more' => ($offset + $per_page) < $total_users],
        ]);
    }

  /**
 * Handles AJAX request to process order to checkout with chunking support
 */

 public function orthoney_get_used_affiliate_codes() {
    global $wpdb;

    $commission_table = $wpdb->prefix . 'yith_wcaf_commissions';

    // Get unique affiliate user IDs from commissions
    $affiliate_ids = $wpdb->get_col("
        SELECT DISTINCT affiliate_id
        FROM $commission_table
        WHERE affiliate_id > 0
    ");


    $results = [];

    foreach ($affiliate_ids as $affiliate_id) {
        $affiliate_code = get_user_meta($affiliate_id, 'yith_wcaf_affiliate_code', true);
        $affiliate_name = get_the_author_meta('display_name', $affiliate_id);

        if (!empty($affiliate_code)) {
            $results[] = [
                'id'   => $affiliate_code,
                'text' => $affiliate_name . ' (' . $affiliate_code . ')'
            ];
        }
    }

    wp_send_json($results);

}

    public function orthoney_process_to_checkout_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
        $order_process_table           = OAM_Helper::$order_process_table;

        $product_id        = OAM_COMMON_Custom::get_product_id();
        $status            = (int) ($_POST['status'] ?? 1);
        $pid               = (int) ($_POST['pid'] ?? 1);
        $recipientIds      = array_map('intval', $_POST['recipientAddressIds'] ?? []);
        $user_id           = get_current_user_id();
        $affiliate_select  = ($_POST['affiliate_select'] ?? 'Orthoney');

        $stepData    = $_POST ?? [];
        $currentStep = isset($_POST['currentStep']) ? intval($_POST['currentStep']) + 1 : 1;

        $result = $wpdb->update(
            $order_process_table,
            [
                'data' => wp_json_encode($stepData),
                'step' => sanitize_text_field($currentStep),
            ],
            ['id' => $pid]
        );

        if (empty($recipientIds)) {
            wp_send_json_error(['message' => 'No recipients selected.']);
        }

        $placeholders      = implode(',', array_fill(0, count($recipientIds), '%d'));
        $table             = OAM_Helper::$order_process_recipient_table;

        $base_conditions   = "user_id = %d AND pid = %d AND visibility = 1 AND verified = 1";
        $status_condition  = $status == 1 ? "AND address_verified = 1" : "";
        $query             = $wpdb->prepare(
            "SELECT SUM(quantity) as total_quantity FROM {$table}
            WHERE {$base_conditions} {$status_condition} AND id IN ($placeholders)",
            array_merge([$user_id, $pid], $recipientIds)
        );

        $total_quantity = (int) $wpdb->get_var($query);

        if ($total_quantity > 0) {

            $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;
            $processExistResult = 'Orthoney';

            if($affiliate_select != 'Orthoney' && $affiliate_select  != ''){
                // Correct query execution
                $processExistResult = $wpdb->get_var($wpdb->prepare("
                    SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d
                ", $affiliate_select));

                if (!$processExistResult) {
                    $processExistResult = 'Orthoney';
                }
            }

            setcookie('yith_wcaf_referral_token', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            setcookie('yith_wcaf_referral_history', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
            unset($_COOKIE['yith_wcaf_referral_token']);
            unset($_COOKIE['yith_wcaf_referral_history']);
            
            yith_wcaf_delete_cookie('yith_wcaf_referral_token');
            yith_wcaf_delete_cookie('yith_wcaf_referral_history');
            yith_wcaf_set_cookie( 'yith_wcaf_referral_token', $processExistResult, WEEK_IN_SECONDS );
            yith_wcaf_set_cookie( 'yith_wcaf_referral_history', $processExistResult, WEEK_IN_SECONDS );

            $this->add_items_to_cart_chunk($total_quantity, $pid, $product_id);
            $checkout_url = wc_get_checkout_url();

            wp_send_json_success([
            'message' => 'Please wait, the order is in progress.',
                'checkout_url' => $checkout_url
            ]);
        } else {
            wp_send_json_error(['message' => 'No valid quantity found.']);
        }
    }

    /**
     * Adds a chunk of items to WooCommerce cart
     * Modified version of add_items_to_cart that doesn't clear the cart
     * 
     * @param array $data Array of recipient data
     * @param int $product_id The product ID to add to cart
     */
    private function add_items_to_cart_chunk($total_quantity, $pid, $product_id) {
        global $wpdb;

        if (!function_exists('WC') || !class_exists('WC_Cart') || WC()->cart === null) {
            return;
        }

        $process = $wpdb->get_row($wpdb->prepare(
            "SELECT data FROM " . OAM_Helper::$order_process_table . " WHERE id = %d", 
            $pid
        ));

        $affiliate_id = 0;
        $greeting = 0;
        if ($process) {
            $data = json_decode($process->data);
            $affiliate_id = $data->affiliate_select ?? 0;
            $greeting = $data->greeting ?? '';
        }

        

        if (class_exists('WC_Cart')) {
            WC()->cart->empty_cart(); // First, clear the cart
        
            $custom_price = OAM_COMMON_Custom::get_product_custom_price((int)$product_id, (int)$affiliate_id);

            $custom_data = array(
                'custom_data' => array(
                    'new_price' => $custom_price,
                    'single_order' => 0,
                    'process_id' => $pid,
                    'greeting' => $greeting
                )
            );
            
            WC()->cart->add_to_cart($product_id, $total_quantity, 0, array(), $custom_data);
        }
       
    }

    
    public function orthoney_save_group_orders_recipient_to_order_process_handler() {
        check_ajax_referer('oam_nonce', 'security');

        global $wpdb;

        $ids     = $_POST['ids'] ?? '';
        $type    = $_POST['type'] ?? '';
        $pid     = intval($_POST['pid'] ?? 0);
        $userID  = intval($_POST['userID'] ?? get_current_user_id());
        $orderid = intval($_POST['orderid'] ?? 0);
        $security = sanitize_text_field($_POST['security'] ?? '');

        if (empty($ids) || empty($pid)) {
            wp_send_json_error(['status' => false, 'message' => 'Missing parameters.']);
        }

        if ($type === 'select-group') {
            $this->process_group_recipient_data($wpdb, $ids, $pid);
        } else {
            $this->process_reorder_from_order($wpdb, $ids, $userID, $security, $pid);
        }

        wp_send_json_success(['status' => true]);
    }

    // Handles processing from selected group IDs
    private function process_group_recipient_data($wpdb, $ids, $pid) {
        global $wpdb;
        $group_table = OAM_Helper::$group_recipient_table;
        $process_table = OAM_Helper::$order_process_recipient_table;

        $ids_array = array_map('intval', explode(',', $ids));
        if (empty($ids_array)) {
            return;
        }

        $wpdb->delete($process_table, ['pid' => $pid], ['%d']);
        $placeholders = implode(',', array_fill(0, count($ids_array), '%d'));
        $query = $wpdb->prepare("SELECT * FROM {$group_table} WHERE group_id IN ($placeholders)", ...$ids_array);
        $results = $wpdb->get_results($query);

        $user_id = get_current_user_id();
        foreach ($results as $row) {
            $wpdb->insert($process_table, [
                'user_id'          => $user_id,
                'pid'              => $pid,
                'full_name'        => sanitize_text_field($row->full_name),
                'company_name'     => sanitize_text_field($row->company_name),
                'address_1'        => sanitize_textarea_field($row->address_1),
                'address_2'        => sanitize_textarea_field($row->address_2),
                'city'             => sanitize_text_field($row->city),
                'state'            => sanitize_text_field($row->state),
                'zipcode'          => sanitize_text_field($row->zipcode),
                'quantity'         => max(1, intval($row->quantity ?? 1)),
                'greeting'         => sanitize_textarea_field($row->greeting),
                'verified'         => 1,
                'address_verified' => 0,
                'new'              => 0,
                'reasons'          => null,
            ]);
        }
    }

    // Handles re-order based on existing WooCommerce order
    private function process_reorder_from_order($wpdb, $orderid, $userID, $security, $pid) {
        $recipient_table = OAM_Helper::$recipient_order_table;
        $process_recipient_table = OAM_Helper::$order_process_recipient_table;

        $orderids = array_filter(array_map('intval', explode(',', $orderid)));
        // if (empty($orderids)) {
        //     wp_send_json_error(['message' => 'Invalid Order IDs']);
        // }

        // $wc_order = wc_get_order($orderid);
        // if (!$wc_order) {
        //     wp_send_json_error(['message' => 'Order ID not found.']);
        // }

        // if (in_array($wc_order->get_status(), ['draft', 'failed'], true)) {
        //     wp_send_json_error(['message' => 'Cannot reorder. Status: ' . $wc_order->get_status()]);
        // }

        $custom_order_id = OAM_COMMON_Custom::get_order_meta($orderid, '_orthoney_OrderID');
        $process_by = OAM_COMMON_Custom::old_user_id();

        $affiliate = null;
        if (!empty($orderids)) {
            $placeholders = implode(',', array_fill(0, count($orderids), '%d'));
            $query = $wpdb->prepare("
                SELECT a.token, a.ID 
                FROM {$wpdb->prefix}yith_wcaf_commissions c
                JOIN {$wpdb->prefix}yith_wcaf_affiliates a ON c.affiliate_id = a.ID
                WHERE c.order_id IN ($placeholders)
                LIMIT 1
            ", ...$orderids);
            $affiliate = $wpdb->get_row($query);
        }

        $affiliate_token = $affiliate->token ?? 'Orthoney';
        $affiliate_id = $affiliate->ID ?? 0;
        $process_id = $pid;

        foreach ($orderids as $orderid) {
            $custom_order_id = OAM_COMMON_Custom::get_order_meta($orderid, '_orthoney_OrderID');
            $recipient_data = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$recipient_table} WHERE order_id = %d", $custom_order_id
            ));

            $order_type = empty($recipient_data) ? 'single_order' : 'multi-recipient-order';
            $greeting = '';
            $total_quantity = 0;

            // foreach ($wc_order->get_items() as $item) {
            //     $quantity = (int)$item->get_quantity();
            //     $greeting = $item->get_meta('greeting', true) ?: '';
            //     $total_quantity += $quantity;
            //     if ($order_type === 'single_order') break;
            // }
            
            // Insert recipient records for multi-recipient orders
            
            if (!empty($recipient_data)) {
                foreach ($recipient_data as $recipient) {
                    $wpdb->insert($process_recipient_table, [
                        'user_id'          => $userID,
                        'pid'              => $process_id,
                        'full_name'        => sanitize_text_field($recipient->full_name),
                        'company_name'     => sanitize_text_field($recipient->company_name),
                        'address_1'        => sanitize_textarea_field($recipient->address_1),
                        'address_2'        => sanitize_textarea_field($recipient->address_2),
                        'city'             => sanitize_text_field($recipient->city),
                        'state'            => sanitize_text_field($recipient->state),
                        'zipcode'          => sanitize_text_field($recipient->zipcode),
                        'quantity'         => max(1, intval($recipient->quantity ?? 1)),
                        'greeting'         => sanitize_textarea_field($recipient->greeting),
                        'verified'         => 1,
                        'update_type'      => sanitize_text_field('re-order'),
                        'address_verified' => 0,
                        'new'              => 0,
                        'reasons'          => null,
                    ]);
                    $recipient_id = $wpdb->insert_id;
                    OAM_Helper::order_process_recipient_activate_log($recipient_id, "new by reorder " . $custom_order_id, 'added', 'reorder');
                }
            }else{
                $wc_order = wc_get_order($orderid);
               
                $total_quantity = 0;
                foreach ($wc_order->get_items() as $item) {
                    $quantity = (int)$item->get_quantity();
                    $greeting = $item->get_meta('greeting', true) ?: '';
                    $total_quantity += $quantity;
                    if ($order_type === 'single_order') break;
                }

                $wpdb->insert($process_recipient_table, [
                    'user_id'          => $userID,
                    'pid'              => $process_id,
                    'full_name'        => sanitize_text_field($wc_order->get_shipping_first_name() .' '. $wc_order->get_shipping_last_name()),
                    'company_name'     => sanitize_text_field($wc_order->get_shipping_company()),
                    'address_1'        => sanitize_textarea_field($wc_order->get_shipping_address_1()),
                    'address_2'        => sanitize_textarea_field($wc_order->get_shipping_address_2()),
                    'city'             => sanitize_text_field($wc_order->get_shipping_city()),
                    'state'            => sanitize_text_field($wc_order->get_shipping_state()),
                    'zipcode'          => sanitize_text_field($wc_order->get_shipping_postcode()),
                    'quantity'         => max(1, intval( $total_quantity ?? 1)),
                    'greeting'         => sanitize_textarea_field($greeting),
                    'verified'         => 1,
                    'update_type'      => sanitize_text_field('re-order'),
                    'address_verified' => 0,
                    'new'              => 0,
                    'reasons'          => null,
                ]);
                $recipient_id = $wpdb->insert_id;
                OAM_Helper::order_process_recipient_activate_log($recipient_id, "new by reorder " . $custom_order_id, 'added', 'reorder');
                
            }
        }

        if(count($orderids) == 1){
            // Update user meta from billing/shipping data
            if ($userID > 0) {
                $this->update_user_meta_from_order($userID, $wc_order);
            }
              $processExistQuery = $wpdb->prepare("
                SELECT id
                FROM {$order_process_table}
                WHERE user_id = %d 
                AND id = %d 
                ", get_current_user_id(), $process_id);

                $processExistResult = $wpdb->get_row($processExistQuery);
                if (!$processExistResult) {
                    $process_id = 0;
                }
            if (isset($_COOKIE['yith_wcaf_referral_token'])) {
                setcookie('yith_wcaf_referral_token', $processExistResult, time() + 3600, "/", "", true, true);
            }
            
            if (isset($_COOKIE['yith_wcaf_referral_history'])) {
                setcookie('yith_wcaf_referral_history', $processExistResult, time() + 3600, "/", "", true, true);
            }
        }
        
    }

    // Update user billing/shipping meta from order
      private function update_user_meta_from_order($userID, $wc_order) {
        if (!is_object($wc_order) || !method_exists($wc_order, 'get_billing_first_name')) {
            return; // or throw an exception / log error
        }

        $billing_fields = ['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country', 'email', 'phone'];
        $shipping_fields = ['first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'];

        foreach ($billing_fields as $field) {
            $method = "get_billing_{$field}";
            if (method_exists($wc_order, $method)) {
                update_user_meta($userID, "billing_{$field}", $wc_order->$method());
            }
        }

        foreach ($shipping_fields as $field) {
            $method = "get_shipping_{$field}";
            if (method_exists($wc_order, $method)) {
                update_user_meta($userID, "shipping_{$field}", $wc_order->$method());
            }
        }
    }

    
    /**
	 * AJAX handler function that remove recipients already order this year handler
	 *
	 * @return JSON 
     * 
	 */ 
    public function remove_recipients_already_order_this_year_handler() {
        check_ajax_referer('oam_nonce', 'security');
        
        global $wpdb;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $ids = isset($_POST['ids']) ? sanitize_text_field($_POST['ids']) : '';
    
        if (empty($ids)) {
            wp_send_json_error(['message' => 'No valid recipients provided. Please try again.']);
        }
    
        // Sanitize and convert to integer array
        $id_array = array_filter(array_map('intval', explode(',', $ids)));
    
        if (empty($id_array)) {
            wp_send_json_error(['message' => 'No valid recipients provided. Please try again.']);
        }
    
        // Prepare SQL placeholders
        $placeholders = implode(',', array_fill(0, count($id_array), '%d'));
    
        // Update visibility
        $sql = "
            UPDATE $order_process_recipient_table
            SET visibility = 0
            WHERE id IN ($placeholders)
        ";
    
        $result = $wpdb->query($wpdb->prepare($sql, $id_array));
    
        // Log actions for each ID
        foreach ($id_array as $id) {
            OAM_Helper::order_process_recipient_activate_log($id, 'deleted', 'remove already ordered', 'process');
        }
    
        wp_send_json_success([
            'updated' => $result,
            'message' => 'Recipients removed successfully.'
        ]);
    }
    
    
    /**
	 * AJAX handler function that edit process name
	 *
	 * @return JSON 
     * 
	 */ 
    public function get_alreadyorder_popup_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
        $id =  ($_POST['id']) ? $_POST['id'] : '';
        if (!empty($id)) {
            $ids = explode(',', $id);
            
            if (!empty($ids)) {
                
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));

                $query = $wpdb->prepare(
                    "SELECT * FROM {$group_recipient_table} WHERE id IN ($placeholders)",
                    ...$ids
                );
    
                $results = $wpdb->get_results($query);

                
                if ($result !== false) {
                    $data = OAM_Helper::get_table_recipient_content($results, '',  0,  0, 1);
                    wp_send_json_success(['message' => 'Records updated successfully.', 'data' => $data]);
                } else {
                    wp_send_json_error(['message' => 'Orders not found. Please try again.']);
                }
            }
            wp_send_json_error(['message' => 'Orders not found. Please try again.']);
        }
        wp_send_json_error(['message' => 'Orders not found. Please try again.']);
    }
    /**
	 * AJAX handler function that edit process name
	 *
	 * @return JSON 
     * 
	 */ 
    public function orthoney_keep_this_and_delete_others_recipient_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $delete_ids = isset($_POST['delete_ids']) ? $_POST['delete_ids'] : '';
    
        if (!empty($delete_ids)) {
            // Convert string to an array
            $delete_ids_array = explode(',', $delete_ids);
            $delete_ids_array = array_map('intval', $delete_ids_array); // Ensure IDs are integers
    
            $recipient_table = OAM_Helper::$order_process_recipient_table;
    
            if (!empty($delete_ids_array)) {
                // Convert array to a comma-separated list for SQL query
                $placeholders = implode(',', array_fill(0, count($delete_ids_array), '%d'));
    
                // Prepare the SQL statement securely
                $sql = $wpdb->prepare(
                    "UPDATE $recipient_table SET visibility = 0 WHERE id IN ($placeholders)",
                    ...$delete_ids_array // Spread operator for passing array values dynamically
                );
                
    
                $result = $wpdb->query($sql);
    
                if ($result !== false) {
                    wp_send_json_success(['message' => 'Records updated successfully.']);
                } else {
                    wp_send_json_error(['message' => 'Error updating records.']);
                }
            }
        }
    
        wp_send_json_error(['message' => 'No valid IDs provided.']);
    }
    
    /**
	 * AJAX handler function that edit process name
	 *
	 * @return JSON 
     * 
	 */ 
    public function orthoney_edit_process_name_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $order_process_table   = OAM_Helper::$order_process_table;
        $group_table   = OAM_Helper::$group_table;

        $process_id  = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
        $name    = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : 'Recipient List '.$process_id;
        $method    = isset($_POST['method']) ? $_POST['method'] : 'order-process';

        $check_process_status = OAM_COMMON_Custom::check_process_exist($name, $process_id);
        if ($check_process_status) {
            if($method == 'order-process'){
                wp_send_json_error(['message' => 'The recipient list name already exists. Please enter a different name.']);
            }else{
                wp_send_json_error(['message' => 'The group name already exists. Please enter a different name.']);
            }
        }

        $processDataQuery = $wpdb->prepare("SELECT data FROM {$order_process_table} WHERE user_id = %d AND id = %d",
            get_current_user_id(),
            $process_id
        );
        $processDataResult = $wpdb->get_row($processDataQuery);

        $setData = [];

        if ($processDataResult && !empty($processDataResult->data)) {
            $setData = json_decode($processDataResult->data, true) ?? [];
        }

        if (!empty($setData)) {
            $setData['upload_type_output_process_name'] = sanitize_text_field($name);
        }


        if($method == 'order-process'){
    
            $result = $wpdb->update(
                $order_process_table,
                [
                    'name'     => sanitize_text_field($name),
                    'data'     => wp_json_encode($setData)
                ],
                ['id' => $process_id]
            );
        }
        if($method == 'group'){
            $result = $wpdb->update(
                $group_table,
                [
                    'name'     => sanitize_text_field($name),
                ],
                ['id' => $process_id]
            );
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Recipient list name is not updated, so please try again.']);
        }
        
        wp_send_json_success(['message' => 'Recipient list name has been updated successfully.']);
        wp_die();
    }

    /**
	 * AJAX handler function that completes the order process, saves the order, and moves it to the checkout.
	 *
	 * @return JSON 
     * 
	 */ 
   public function orthoney_order_step_process_completed_ajax_handler() {
    check_ajax_referer('oam_nonce', 'security');
    global $wpdb;

    $order_process_table           = OAM_Helper::$order_process_table;
    $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
    $duplicate  = isset($_POST['duplicate']) ? intval($_POST['duplicate']) : 1;

    $address_list = [];
    $process_id  = isset($_POST['pid']) ? intval($_POST['pid']) : 0;
    $user        = get_current_user_id();
    $stepData    = $_POST ?? [];
    $currentStep = isset($_POST['currentStep']) ? intval($_POST['currentStep']) + 1 : 1;

    if ($process_id === 0) {
        wp_send_json_error(['message' => 'Invalid Process ID.']);
    }

    // Update order process table
    $result = $wpdb->update(
        $order_process_table,
        [
            'data' => wp_json_encode($stepData),
            'step' => sanitize_text_field($currentStep),
        ],
        ['id' => $process_id]
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Database update failed.']);
    }

    // Verify recipient addresses if process_id is valid
    $recipients = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, address_1, address_2, city, state, zipcode FROM {$order_process_recipient_table} 
             WHERE user_id = %d AND pid = %d AND visibility = %d AND verified = %d",
            $user, $process_id, 1, 1
        )
    );

    foreach ($recipients as $recipient) {
        $street = trim(($recipient->address_1 ?? '') . ' ' . ($recipient->address_2 ?? ''));

        $city = html_entity_decode(strip_tags($recipient->city ?? ''));
        $city = ucwords(strtolower(trim($city)));

        $address_list[] = [
            "input_id"   => $recipient->id,
            "street"     => $street,
            "city"       => $city,
            "state"      => $recipient->state ?? '',
            "zipcode"    => $recipient->zipcode ?? '',
            "candidates" => 10,
            'match'      => 'invalid',
            'geocode'    => true,
        ];
    }

    // Process addresses in chunks
    $chunk_size = 80;
    $address_chunks = array_chunk($address_list, $chunk_size);
    $delay_time = 2; // Delay in seconds

    foreach ($address_chunks as $index => $chunk) {
        // Call API
        $multi_validate_address = OAM_Helper::multi_validate_address($chunk);

        // Check if the API response is valid
        if (empty($multi_validate_address) || !is_string($multi_validate_address)) {
            error_log("Address validation API failed or returned an invalid response.");
            continue;
        }

        $multi_validate_address_result = json_decode($multi_validate_address, true);

        if (!empty($multi_validate_address_result)) {
            foreach ($multi_validate_address_result as $i => $data) {
                $pid = $data['input_id'];
                $success = true;
                $message = '';
                $dpv_match_code = $data['analysis']['dpv_match_code'] ?? '';
                $components = $data['components'] ?? [];

                $original = $chunk[$i] ?? [];

                if ($dpv_match_code !== 'N' && !empty($dpv_match_code)) {
                    if (!empty($components)) {
                       if ( ucwords(strtolower(trim($original['city'])))  !== ucwords(strtolower(trim($components['city_name']))) ) {
                            $message .= 'Provided city is invalid. Accepted city is <span style="color: #6BBE56;">' . esc_html($components['city_name']) . '</span>';
                            $success = false;
                        } 
                        if (($original['state'] ?? '') !== ($components['state_abbreviation'] ?? '')) {
                            $message .= 'Provided state is invalid. Accepted state is <span style="color: #6BBE56;">' . esc_html($components['state_abbreviation']) . '</span>';
                            $success = false;
                        } 
                       
                        if (strpos($zipcode, '-') !== false) {
                            if (strpos($zipcode, $data[0]['components']['zipcode']) === false) {
                                $message .= 'Provided zipcode is invalid. Accepted zipcode is <span style="color: #6BBE56;">'. $data[0]['components']['zipcode'].'-'.$data[0]['components']['plus4_code'].'</span>';
                                 $success = false;
                            }
                        }else{
                            if (!empty($data) && isset($data[0]['components']['zipcode'])) {
                                if ($zipcode !== $data[0]['components']['zipcode']) {
                                    $message .= 'Provided zipcode is invalid. Accepted zipcode is <span style="color: #6BBE56;">'. $data[0]['components']['zipcode'].'</span>';
                                    $success = false;
                                }
                            }
                        }
                        
                    }
                } else {
                    $message = 'Invalid address format.';
                    $footnotes = $data['analysis']['footnotes'] ?? '';
                    $dpv_footnotes = $data['analysis']['dpv_footnotes'] ?? '';
                    if ($footnotes !== '' && !empty($footnotes) || $dpv_footnotes !== '' && !empty($dpv_footnotes)) {
                        $message = OAM_Helper::addressCorrections($footnotes, $dpv_footnotes);
                        $success = false;
                    }
                }

                if ($success === true) {
                    $update_result = $wpdb->update(
                        $order_process_recipient_table,
                        [
                            'address_verified' => 1,
                            'reasons' => '',
                        ],
                        ['id' => $pid]
                    );

                    if ($update_result === false) {
                        error_log("Failed to update address verification for ID: $pid");
                    }
                }else{
                    $update_result = $wpdb->update(
                        $order_process_recipient_table,
                        [
                            'address_verified' => 0,
                            'reasons' => $message
                        ],
                        ['id' => $pid]
                    );
                }
               
                // You can optionally log $message or store it elsewhere
            }
        }

        // Delay between chunks except the last
        if ($index < count($address_chunks) - 1) {
            sleep($delay_time);
        }
    }


    wp_send_json_success(['message' => 'Process completed successfully.']);
}

    
    /**
	 * Ajax handle function that Upload CSV file temp recipients
	 *
	 * @return JSON 
     * 
     * USE Helper function 
     * validate_and_upload_csv(): This function validates the CSV file and saves the CSV upload activity log.
     * 
	 */
    public function orthoney_save_csv_temp_recipient_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $recipient_table = OAM_Helper::$order_process_recipient_table;
        $order_process = OAM_Helper::$order_process_table;
        $recipient_dir = OAM_Helper::$process_recipients_csv_dir;
    
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    
        
        $currentStep = isset($_POST['currentStep']) ? intval($_POST['currentStep']) : 0;
        $process_id = isset($_POST['pid']) ? intval($_POST['pid']) : '';
        $process_name = (isset($_POST['csv_name']) && !empty($_POST['csv_name'])) ? sanitize_text_field($_POST['csv_name']) : 'Recipient List ' . $process_id;
    
        $check_process_status = OAM_COMMON_Custom::check_process_exist($process_name, $process_id);
    
        if ($check_process_status) {
            wp_send_json_error(['message' => 'The recipient list name already exists. Please enter a different name.']);
        }
    
        $csv_name_query = $wpdb->get_var($wpdb->prepare("SELECT csv_name FROM {$order_process} WHERE id = %d LIMIT 1", $process_id));
        $file_path = $csv_name_query ? $recipient_dir . '/' . $csv_name_query : '';
    
        
        if ($csv_name_query && file_exists($file_path)) {
            unlink($file_path);
        }
        $wpdb->delete($recipient_table, ['pid' => $process_id], ['%d']);
        
    
        // File upload handling (first chunk only)
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            if (!file_exists($recipient_dir)) {
                wp_mkdir_p($recipient_dir);
            }
    
            $result = OAM_Helper::validate_and_upload_csv($_FILES['csv_file'], 0, $process_id, 'order_process');
    
            if (!$result['success']) {
                wp_send_json_error(['message' => $result['message']]);
                wp_die();
            }
    
            $file_ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
            $unique_file_name = 'recipient_' . $process_id . '.' . $file_ext;
            $recipient_file_path = trailingslashit($recipient_dir) . $unique_file_name;
    
            if (file_exists($recipient_file_path)) {
                unlink($recipient_file_path);
            }
    
            if (!copy($result['file_path'], $recipient_file_path)) {
                wp_send_json_error(['message' => 'Failed to move uploaded file.']);
                wp_die();
            }
    
            $wpdb->update(
                $order_process,
                ['csv_name' => $unique_file_name, 'user_id' => get_current_user_id(), 'name' => $process_name],
                ['id' => $process_id]
            );
    
            $file_path = $recipient_file_path;
        }
    
        wp_send_json_success(['message' => 'CSV uploaded']);
    }

    /**
	 * Ajax handle function that insert temp recipients
	 *
	 * @return JSON 
     * 
     * USE Helper function 
     * validate_and_upload_csv(): This function validates the CSV file and saves the CSV upload activity log.
     * 
	 */
    public function orthoney_insert_temp_recipient_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
    
        $recipient_table = OAM_Helper::$order_process_recipient_table;
        $order_process = OAM_Helper::$order_process_table;
        $recipient_dir = OAM_Helper::$process_recipients_csv_dir;
    
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    
        $chunk_size = 10;
        $current_chunk = isset($_POST['current_chunk']) ? intval($_POST['current_chunk']) : 0;
        $currentStep = isset($_POST['currentStep']) ? intval($_POST['currentStep']) : 0;
        $process_id = isset($_POST['pid']) ? intval($_POST['pid']) : '';
        $process_name = (isset($_POST['csv_name']) && !empty($_POST['csv_name'])) ? sanitize_text_field($_POST['csv_name']) : 'Recipient List ' . $process_id;
    
        $check_process_status = OAM_COMMON_Custom::check_process_exist($process_name, $process_id);
    
        if ($check_process_status) {
            wp_send_json_error(['message' => 'The recipient list name already exists. Please enter a different name.']);
        }
    
        $csv_name_query = $wpdb->get_var($wpdb->prepare("SELECT csv_name FROM {$order_process} WHERE id = %d LIMIT 1", $process_id));
        $file_path = $csv_name_query ? $recipient_dir . '/' . $csv_name_query : '';
    
        if ($current_chunk == 0) {
            if ($csv_name_query && file_exists($file_path)) {
                unlink($file_path);
            }
            $wpdb->delete($recipient_table, ['pid' => $process_id], ['%d']);
        }
    
        // File upload handling (first chunk only)
        if ($current_chunk == 0 && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            if (!file_exists($recipient_dir)) {
                wp_mkdir_p($recipient_dir);
            }
    
            $result = OAM_Helper::validate_and_upload_csv($_FILES['csv_file'], $current_chunk, $process_id, 'order_process');
    
            if (!$result['success']) {
                wp_send_json_error(['message' => $result['message']]);
                wp_die();
            }
    
            $file_ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
            $unique_file_name = 'recipient_' . $process_id . '.' . $file_ext;
            $recipient_file_path = trailingslashit($recipient_dir) . $unique_file_name;
    
            if (file_exists($recipient_file_path)) {
                unlink($recipient_file_path);
            }
    
            if (!copy($result['file_path'], $recipient_file_path)) {
                wp_send_json_error(['message' => 'Failed to move uploaded file.']);
                wp_die();
            }
    
            $wpdb->update(
                $order_process,
                ['csv_name' => $unique_file_name, 'user_id' => get_current_user_id(), 'name' => $process_name],
                ['id' => $process_id]
            );
    
            $file_path = $recipient_file_path;
        }
    
        if (empty($file_path) || !file_exists($file_path) || !is_readable($file_path)) {
            wp_send_json_error(['message' => 'File not found.']);
            wp_die();
        }
    
        $file_ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $header = [];
        $rows = [];
        $handle = null;
    
        if ($file_ext === 'csv') {
            $handle = fopen($file_path, 'r');
            if (!$handle) {
                wp_send_json_error(['message' => 'Unable to open CSV file.']);
                wp_die();
            }
    
            $header = fgetcsv($handle);
            $start_line = $current_chunk * $chunk_size;
            for ($i = 0; $i < $start_line; $i++) {
                fgetcsv($handle); // skip previous lines
            }
        } elseif ($file_ext === 'xlsx') {
            require_once OH_PLUGIN_DIR_PATH . 'libs/SimpleXLSX/SimpleXLSX.php';
            $xlsx = SimpleXLSX::parse($file_path);
            if (!$xlsx) {
                wp_send_json_error(['message' => 'Invalid XLSX file: ' . SimpleXLSX::parseError()]);
                wp_die();
            }
            $rows = $xlsx->rows();
            $header = $rows[0];
        } elseif ($file_ext === 'xls') {
            require_once OH_PLUGIN_DIR_PATH . 'libs/SimpleXLS/SimpleXLS.php';
            $xls = SimpleXLS::parse($file_path);
            if (!$xls) {
                wp_send_json_error(['message' => 'Invalid XLS file: ' . SimpleXLS::parseError()]);
                wp_die();
            }
            $rows = $xls->rows();
            $header = $rows[0];
        } else {
            wp_send_json_error(['message' => 'Unsupported file type. Only CSV, XLSX, OR XLS are allowed.']);
            wp_die();
        }
    
        $required_columns = OH_REQUIRED_COLUMNS;
        $required_columns_lower = array_map('strtolower', array_map('trim', $required_columns));
        $header_lower = array_map(function($val) {
            return strtolower(trim($val));
        }, $header);
    
       $missing_columns = array_diff($required_columns_lower, $header_lower);
        if (!empty($missing_columns)) {
            wp_send_json_error(['message' => 'Missing required columns: ' . implode(', ', $missing_columns)]);
            wp_die();
        }
    
        // Count total rows
        if ($file_ext === 'csv') {
            $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $total_rows = $lines ? count($lines) - 1 : 0;
        } elseif ($file_ext === 'xlsx') {
            $total_rows = count($rows) - 1;
        } elseif ($file_ext === 'xls') {
            $total_rows = count($rows) - 1;
        }
    
        $wpdb->query('START TRANSACTION');
        try {
            $processed_rows = 0;
            $error_rows = [];
    
            for ($i = 0; $i < $chunk_size; $i++) {
                if ($file_ext === 'csv') {
                    $row = fgetcsv($handle);
                } else {
                    $row = $rows[$current_chunk * $chunk_size + $i + 1] ?? false;
                }
    
                if ($row === false) break;
    
                if (count($row) !== count($header_lower)) {
                    $error_rows[] = $current_chunk * $chunk_size + $processed_rows;
                    continue;
                }
    
                $data = array_combine($header_lower, $row);
                $failure_reasons = [];
    
                $required_fields = [
                    'full name' => 'Full name',
                    'Mailing Address' => 'Mailing Address',
                    'city' => 'City',
                    'state' => 'State',
                    'zipcode' => 'Zipcode',
                    'quantity' => 'quantity',
                ];
    
                foreach ($required_fields as $key => $field) {
                    if ($key !== 'quantity' && empty($data[strtolower($key)])) {
                        $failure_reasons[] = "Missing {$field}";
                    } elseif ($key === 'quantity' && (!is_numeric($data[strtolower($key)]) || $data[strtolower($key)] <= 0)) {
                        $failure_reasons[] = "Invalid Quantity";
                    }
                }
    
                $quantity = 0;
                if(!isset($data['quantity'] ) OR $data['quantity'] === 0 OR $data['quantity'] === ''){
                    $quantity = 0;
                }else{
                    $quantity = $data['quantity'];
                }

                $insert_data = [
                    'user_id'          => get_current_user_id(),
                    'pid'              => $process_id,
                    'full_name'        => sanitize_text_field($data['full name']),
                    'company_name'     => sanitize_text_field($data['company name']),
                    'address_1'        => sanitize_textarea_field($data['mailing address']),
                    'address_2'        => sanitize_textarea_field($data['suite/apt#']),
                    'city'             => sanitize_text_field($data['city']),
                    'state'            => sanitize_text_field(strtoupper($data['state'])),
                    'zipcode'          => sanitize_text_field($data['zipcode']),
                    'quantity'         => intval($quantity),
                    'greeting'         => sanitize_textarea_field($data['greeting']),
                    'verified'         => empty($failure_reasons) ? 1 : 0,
                    'address_verified' => 0,
                    'new'              => 0,
                    'reasons'          => empty($failure_reasons) ? null : json_encode($failure_reasons),
                ];
    
                $wpdb->insert($recipient_table, $insert_data);
                $processed_rows++;
            }
    
            $wpdb->query('COMMIT');
    
            if ($file_ext === 'csv' && isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
    
            $wpdb->update(
                $order_process,
                ['step' => sanitize_text_field(++$currentStep)],
                ['id' => $process_id]
            );
    
            wp_send_json_success([
                'message' => "Chunk processed successfully",
                'processed_rows' => $processed_rows,
                'error_rows' => $error_rows,
                'next_chunk' => $current_chunk + 1,
                'total_rows' => $total_rows,
                'progress' => min(100, round((($current_chunk + 1) * $chunk_size) / $total_rows * 100)),
                'finished' => (($current_chunk + 1) * $chunk_size) >= $total_rows,
                'pid' => $process_id
            ]);
    
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            if ($file_ext === 'csv' && isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    
    /**
	 * An AJAX handler function that saves and verifies the address for a single-address order before redirecting to the checkout page.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
     * USE Helper function 
     * validate_address(): This function checks address validation using the Smarty API.
     * 
     * 
     * TODO: get dynamic product price base selected affiliate.
     * 
	 */
    
    public function orthoney_single_address_data_save_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');

        global $wpdb;
    
        $order_process_table = OAM_Helper::$order_process_table;
        $stepData = $_POST;
        $user_id = get_current_user_id();

        $process_id = !empty($stepData['pid']) ? $stepData['pid'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : 0;


        $processQuery = $wpdb->prepare("
        SELECT order_id, data
        FROM {$order_process_table}
        WHERE user_id = %d 
        AND id = %d 
        ", $user_id, $process_id);

        $affiliate_id = 0;
        $processResult = $wpdb->get_row($processQuery);

        if(!empty($processResult)){
            $order = wc_get_order( $processResult->order_id);
            $affiliate_id = json_decode($processResult->data)->affiliate_select;
            if ($order) {
                $order_key = $order->get_order_key();
                $order_url = wc_get_endpoint_url('view-order', $processResult->order_id, wc_get_page_permalink('myaccount')) . '?key=' . $order_key;
                wp_send_json_success([
                    'message' => 'Address is Verify',
                    'checkout_url' => $order_url
                ]);
            }
        }
        
        $currentStep = !empty($stepData['currentStep']) ? $stepData['currentStep'] : '';
        $delivery_line_1 = !empty($stepData['single_order_address_1']) ? $stepData['single_order_address_1'] : '';
        $delivery_line_2 = !empty($stepData['single_order_address_2']) ? $stepData['single_order_address_2'] : '';
        $city = !empty($stepData['single_order_city']) ? $stepData['single_order_city'] : '';
        $state = !empty($stepData['single_order_state']) ? $stepData['single_order_state'] : '';
        $country = !empty($stepData['single_order_country']) ? $stepData['single_order_country'] : 'US';
        $zipcode = !empty($stepData['single_order_zipcode']) ? $stepData['single_order_zipcode'] : '';
        $quantity = !empty($stepData['single_address_quantity']) ? $stepData['single_address_quantity'] : 1;
        $greeting = !empty($stepData['single_address_greeting']) ? $stepData['single_address_greeting'] : '';

        update_user_meta($user_id, 'shipping_address_1', $delivery_line_1);
        update_user_meta($user_id, 'shipping_address_2', $delivery_line_2);
        update_user_meta($user_id, 'shipping_city', $city);
        update_user_meta($user_id, 'shipping_state', $state);
        update_user_meta($user_id, 'shipping_country', $country);
        update_user_meta($user_id, 'shipping_postcode', $zipcode);

        if($status == 0){
            $validate_address_result =  OAM_Helper::validate_address($delivery_line_1, $delivery_line_2, $city, $state, $zipcode);

            $data = json_decode($validate_address_result, true);
            if(!empty($data)){
                if($data['success'] === false){
                    wp_send_json_error(['message' => $data['message']]);
                }
            }
        }

        //clear the cart
        if (class_exists('WC_Cart')) {
            WC()->cart->empty_cart(); // First, clear the cart
            
            $product_id = OAM_COMMON_Custom::get_product_id();

            $custom_price = OAM_COMMON_Custom::get_product_custom_price((int)$product_id, (int)$affiliate_id);

            $custom_data = array(
                'custom_data' => array(
                    'new_price' => $custom_price,
                    'single_order' => 1,
                    'process_id' => $process_id,
                    'greeting' => $greeting
                )
            );
            
            WC()->cart->add_to_cart($product_id, $quantity, 0, array(), $custom_data);

        }

        $updateData = [
            'order_type'  => sanitize_text_field('single_order'),
        ];

        $wpdb->update(
            $order_process_table,
            $updateData,
            ['id' => $process_id]
        );

        // Get checkout page URL
        $checkout_url = wc_get_checkout_url();

        wp_send_json_success([
            'message' => 'Address is Verify',
            'checkout_url' => $checkout_url
        ]);
    }
    
    /**
	 * An AJAX handler function that save order process activity.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
	 */
   
    public function orthoney_order_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');  

        $process_id = intval($_POST['pid']);
        
        $stepData = $_POST;
        
        $groups = 0;
        if(isset($_POST['groups']) && !empty($_POST['groups'])){
            $groups = 1;
        }

        $currentStep = intval($_POST['currentStep']);

        global $wpdb;
    
        $order_process_table = OAM_Helper::$order_process_table;
        
        if(isset($_POST['affiliate_select'])){
            OAM_COMMON_Custom::set_affiliate_cookie($_POST['affiliate_select']);
        }
        
        // Ensure data is safely serialized as a JSON string
        $data = [
            'user_id'  => get_current_user_id(),
            'process_by'  => OAM_COMMON_Custom::old_user_id(),
            'data'     => wp_json_encode($stepData),
            'step'     => sanitize_text_field($currentStep),
            'greeting' => sanitize_text_field($stepData['greeting']),
        ];
        
        
        if($currentStep == 3 && (isset($_POST['multiple_address_output']) && !empty($_POST['multiple_address_output']) && $_POST['multiple_address_output'] === 'add-manually') && (isset($_POST['upload_type_output_process_name']) )){
            // $data['csv_name'] = $_POST['upload_type_output_process_name'];
            $data['name'] = $_POST['upload_type_output_process_name'];
        }

        $processExistQuery = $wpdb->prepare("SELECT id FROM {$order_process_table}  WHERE user_id = %d  AND id = %d", get_current_user_id(), $process_id);

        $processExistResult = $wpdb->get_row($processExistQuery);
        if (!$processExistResult) {
            $process_id = 0;
        }

        if ($process_id == 0) {
            $data['created']    = current_time('mysql');
            $data['modified']   =  current_time('mysql');
            $data['user_agent'] = OAM_Helper::get_user_agent();
            $data['user_ip']    = OAM_Helper::get_user_ip();
           
            $result = $wpdb->insert( $order_process_table, $data);
    
            if ($result !== false) {
                $process_id = $wpdb->insert_id;
    
                $updateData = [
                    'name'        => sanitize_text_field('Recipient List ' . $process_id),
                    'modified'    =>  current_time('mysql'),
                ];
    
                $wpdb->update(
                    $order_process_table,
                    $updateData,
                    ['id' => $process_id]
                );
            }
        } else {
            
            $result = $wpdb->update(
                $order_process_table,
                $data,
                ['id' => $process_id]
            );
        }
    
        if ($result !== false) {
            wp_send_json_success([
                'pid'     => $process_id,
                'groups'     => $groups,
                'step'    => sanitize_text_field($currentStep),
            ]);
        } else {
            wp_send_json_error(['message' => 'Database error occurred.']);
        }
    }

    /**
	 * An AJAX handler function that save step for order process form.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
	 */
    public function orthoney_order_step_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        $process_id = intval($_POST['process_value']);
        $currentStep = intval($_POST['currentStep']);

        global $wpdb;
        $order_process_table = OAM_Helper::$order_process_table;

        $data = [
            'step'    => sanitize_text_field($currentStep),
        ];

        $result = $wpdb->update(
            $order_process_table,
            $data,
            ['id' => $process_id]
        );

        if ($result !== false) {
            wp_send_json_success([
                'pid' => $process_id,
            ]);
        } else {
            wp_send_json_error(['message' => 'Database error occurred.']);
        }

    }


     /**
	 * An AJAX handler function that get recipients
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON and array depend to the parameter
     * 
     * USE Helper function 
     * get_table_recipient_content(): This function retrieves recipient data in a table format.
     * 
	 */
   
     public function orthoney_get_csv_recipient_ajax_handler($userId = '', $processID = '', $atts_process_id = 0) {
        
        $exclude_ids = $process_id = $customGreeting = $addressPartsHtml = $successHtml = $newDataHtml = $alreadyOrderHtml  = $failHtml = $duplicateHtml = '';
        $successData = $newData = $failData = $duplicateGroups = $alreadyOrderGroups = [];
        $totalCount = 0;
        $duplicatePassCount = 0;
        $duplicateFailCount = 0;
        if($userId != ''){
            $user = $userId;
        }else{
            // Verify nonce for security
            check_ajax_referer('oam_nonce', 'security');    
            $user = sanitize_text_field($_POST['user']);
        }

        if(isset($_POST['pid'])){
            $process_id  = isset($_POST['pid'])? sanitize_text_field($_POST['pid']) : '';
        }else{
            $process_id = $processID;
        }
        
        if($user != 0 OR $user != ''){
            global $wpdb;
            $year = date('Y');
            $group_recipient_table = OAM_Helper::$group_recipient_table;
            $start_date = "$year-01-01 00:00:00";
            $end_date = "$year-12-31 23:59:59";
            

            $tableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Status</th><th>Greeting</th><th>Action</th></tr></thead><tbody>';

            $duplicateTableStart ='<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Status</th><th>Greeting</th><th>Action</th></tr></thead><tbody>';

            $alreadyOrderTableStart ='<table><thead><tr><th>Order Id</th><th>Order Date</th><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th></tr></thead><tbody>';
            
            $tableEnd = '</tbody></table>';
           
            $recipient_table = OAM_Helper::$order_process_recipient_table;
            
            // Generate placeholders for each ID
            
            $query = '';
            $includeQuery = '';

            if($process_id != 0 OR $process_id != ''){
                $query = $wpdb->prepare("
                    SELECT * 
                    FROM {$recipient_table}
                    WHERE user_id = %d 
                    AND pid = %d 
                    AND visibility = 1
                ", $user, $process_id);

                $order_process_table = OAM_Helper::$order_process_table;
                
                $processQuery = $wpdb->prepare("
                SELECT * 
                FROM {$order_process_table}
                WHERE user_id = %d 
                AND id = %d 
                ", $user, $process_id);

                $processResult = $wpdb->get_row($processQuery);

                if ($processResult) {
                    $getData  = json_decode($processResult->data);
                    $customGreeting = (!empty($getData->greeting)) ? $getData->greeting : '';    
                }
                
            }
            
            $allRecords = $wpdb->get_results($query);
            
            // First pass: Group records by their unique combination
            $recordMap = [];

            $total_quantity = 0;
            
            foreach ($allRecords as $record) {

                // Create unique key for comparison
                $key = $record->full_name . '|' . 
                        str_replace($record->address_1, ',' , '' ). ' ' . str_replace($record->address_2 , ',' , '') . '|' . 
                       $record->city . '|' . 
                       $record->state . '|' . 
                       $record->zipcode;
                $total_quantity = $total_quantity + $record->quantity;
                // Store record in the map
                if (!isset($recordMap[$key])) {
                    $recordMap[$key] = [];
                }
                
                $recordMap[$key][] = $record;


                // // Fetch records using `DATETIME` format for filtering
                // $result = $wpdb->get_results($wpdb->prepare(
                //     "SELECT * FROM {$group_recipient_table} 
                //     WHERE full_name = %s 
                //     AND company_name = %s 
                //     AND city = %s 
                //     AND state = %s 
                //     AND zipcode = %s 
                //     AND user_id = %d
                //     AND `timestamp` BETWEEN %s AND %s",
                //     $record->full_name, $record->company_name,$record->city, $record->state,  $record->zipcode, $user,$start_date, $end_date
                // ));

                // // Normalize and merge input address values
                // $search_address = $record->address_1 . $record->address_2; // Merge address_1 and address_2
                // $search_address = str_replace([',', '.'], '', $search_address);
                // $search_address = trim($search_address);

                // // Filter results by merging address_1 and address_2 in the database
                // $filtered_results = array_filter($result, function ($record) use ($search_address) {
                //     // Merge database address_1 and address_2
                //     $merged_address = trim($record->address_1 . ' ' . $record->address_2);
                //     $merged_address = str_replace([',', '.'], '', $merged_address);

                //     // Compare merged values
                //     return strcasecmp($search_address, $merged_address) === 0;
                // });


                // $alreadyOrderGroups = $filtered_results;

            }
    
            // Second pass: Categorize records
            foreach ($recordMap as $key => $records) {
                if (count($records) > 1) {
                    // This is a group of duplicates
                    $duplicateGroups[] = $records;
                    
                } else {
                    // Single record - add to success or fail based on verified status
                    $record = $records[0];
                    if ($record->new == 0) {
                        if ($record->verified == 1) {
                            $successData[] = $record;
                        } else {
                            $failData[] = $record;
                        }
                    }else{
                        $newData[] = $record;
                    }
                }
            }

          
            $totalDuplicates = 0;
            if($atts_process_id == 0){
          
                // Generate Duplicate HTML - now showing grouped duplicates
                //Keep 1 Entry and Delete Other Duplicate Entries
                if(!empty($duplicateGroups)){
                    foreach ($duplicateGroups as $group) {
                        $totalDuplicates += count($group);
                    }

                    $bulkMargeButtonHtml = '';
                    if($process_id != ''){
                        $bulkMargeButtonHtml = '<div class="tooltip" data-tippy="Keep 1 Entry and Delete Other Duplicate Entries"><button id="bulkMargeRecipient" class="btn-underline">Clean All Duplicates</button></div>';
                    }
                    
                    $duplicateHtml .= '<div class="heading-title"><div><h5 class="table-title">Duplicate Recipient(s)</h5> </div><div class="right-col">'.$bulkMargeButtonHtml.'<div class="search-icon"> <div class="icon"></div></div></div></div>';
                    
                    foreach ($duplicateGroups as $groupIndex => $group) {
                        foreach($group as $data){
                            if ($data->verified == 1) {
                                $duplicatePassCount = $duplicatePassCount + 1;
                            }else{
                                $duplicateFailCount = $duplicateFailCount + 1;
                            }
                        }
                        $duplicateHtml .= '<tr class="group-header" data-count="'.count($group).'" data-group="99'.($groupIndex + 1).'"><td colspan="12"><strong>'.count($group).'</strong> duplicate records for <strong>'.($group[0]->full_name).'</strong></td></tr>';
                        $duplicateHtml .= OAM_Helper::get_table_recipient_content($group , $customGreeting, 0 , '99'.$groupIndex + 1);
                        
                    }
                }

            
                // Generate new data HTML
                if(!empty($newData)){
                    $newDataHtml .= '<div class="heading-title"><h5 class="table-title">Added Recipient(s) Manually</h5><div class="right-col"><button class="editRecipient btn-underline" style="display:none" data-popup="#recipient-manage-popup">Add New Recipient</button><div class="search-icon"> <div class="icon"></div></div>  </div>';
                    $newDataHtml .= OAM_Helper::get_table_recipient_content($newData, $customGreeting);
                    
                }
        
                // Generate Success HTML
                if(!empty($successData)){
                    $successHtml .= '<div class="heading-title">
                    <div><h5 class="table-title">Added Recipient(s) From List</h5></div>
                    <div class="links-group">
                    <button class="removeRecipientsAlreadyOrder btn-underline" data-tippy="Remove all recipients that we found on another recent order.">Remove Already Ordered Recipients</button><div class="vline"></div>
                    <button class="viewSuccessRecipientsAlreadyOrder btn-underline" data-status="0" data-tippy="View only the recipients that we found on another recent order." style="display:none">View Already Ordered Recipients</button><div class="search-icon"> <div class="icon"></div></div>  
                    </div></div>';
                    $successHtml .=  OAM_Helper::get_table_recipient_content($successData , $customGreeting);
                }
                
                
                // if(!empty($alreadyOrderGroups)){
                //     $alreadyOrderHtml .= '<div class="heading-title"><div><h5 class="table-title">Already Ordered</h5><p> Honey is already ordered for '.count($alreadyOrderGroups).' Recipients this year</p></div></div>';
                //     $alreadyOrderHtml .= OAM_Helper::get_table_recipient_content($alreadyOrderGroups , $customGreeting, 0 , 0 , 1);
                // }

                // Wrap tables with headers
                if($newDataHtml != ''){
                    $newDataHtml = $tableStart.$newDataHtml.$tableEnd;
                }
                if($successHtml != ''){
                    $successHtml = $tableStart.$successHtml.$tableEnd;
                }
                // if($alreadyOrderHtml != ''){
                //     $alreadyOrderHtml = $alreadyOrderTableStart.$alreadyOrderHtml.$tableEnd;
                // }

                if($duplicateHtml != ''){
                    $duplicateHtml = $duplicateTableStart.$duplicateHtml.$tableEnd;
                }
        
                // Calculate total count of all records including duplicates
                $totalDuplicates = 0;
                foreach ($duplicateGroups as $group) {
                    $totalDuplicates += count($group);
                }
            }else{
                $successData = [];
            }
            
            // Generate Fail HTML
            if(!empty($failData)){
                // $failHtml .= '<div><h3>Failed to Add Recipeints</h3><h4>'.count($failData).' Recipient failed!</h4></div>';
                $failHtml .= OAM_Helper::get_table_recipient_content($failData , $customGreeting);
            }

            $totalCount = count($successData) + count($failData) + $totalDuplicates + count($newData);

            
            if($failHtml != ''){
                $failHtml = '<div class="download-csv"><div class="heading-title"><div><h5 class="table-title">Failed Recipient(s)</h5><p>To fix the failed data, edit the row and make the necessary changes OR upload a new CSV for failed recipients.</p></div><div><div class="right-col"><button data-tippy="Failed records can be downloaded" id="download-failed-recipient-csv" class="btn-underline" ><i class="far fa-download"></i> Download Failed Recipients</button><div class="search-icon"> <div class="icon"></div></div>  </div></div></div> </div>'.$tableStart.$failHtml.$tableEnd;
            }
            
            

            $resultData = [
                'newData'         => $newDataHtml,
                'successData'     => $successHtml,
                'failData'        => $failHtml,
                'alreadyOrderData'=> $alreadyOrderHtml,
                'duplicateData'   => $duplicateHtml,
                'duplicateGroups' => $duplicateGroups,
                'totalCount'      => $totalCount,
                'total_quantity'  => $total_quantity,
                'successCount'    => count($successData),
                'newCount'        => count($newData),
                'failCount'       => count($failData),
                'alreadyOrderCount'   => count($alreadyOrderGroups),
                'duplicateCount'  => $totalDuplicates,
                'groupCount'      => count($duplicateGroups),
                'duplicatePassCount'    => $duplicatePassCount,
                'duplicateFailCount'      => $duplicateFailCount
            ];

            
            if($userId != ''){
                return json_encode(['success' => true, 'data'=> $resultData]);
            }else{
                wp_send_json_success( $resultData);
            }
           
        }else {
            if($userId != ''){
                return json_encode(['success' => false, 'message' => 'No data found.']);
            }else{
                wp_send_json_error( ['message' => 'No data found.']);
            }
        }
    
        wp_die();
    }
    
    /**
	 * An AJAX handler function that download failed recipients data.
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON
     * 
     * TODO : remaining code for group
	 */
    
     public function orthoney_download_failed_recipient_handler() {
        global $wpdb;
    
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $id = isset($_POST['id']) ? $_POST['id'] : '';
    
        // Get the logged-in user ID
        $user = get_current_user_id();
        $failData = [];
        $recordMap = [];
        $filename = 'fail-recipients-' . $id . '.csv';
        if ($type == 'process') {
            $recipient_table = OAM_Helper::$order_process_recipient_table;
            $allRecords = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$recipient_table} WHERE user_id = %d AND pid = %d  AND visibility = %d", $user, $id, 1));
           
        
            foreach ($allRecords as $record) {
                $key = $record->full_name . '|' . 
                    $record->address_1 . '|' . 
                    $record->city . '|' . 
                    $record->state . '|' . 
                    $record->country . '|' . 
                    $record->quantity . '|' . 
                    $record->zipcode;
        
                if (!isset($recordMap[$key])) {
                    $recordMap[$key] = [];
                }
                $recordMap[$key][] = $record;
            }
        
            foreach ($recordMap as $key => $records) {
                if (count($records) == 1) {
                    $record = $records[0];
                    if ($record->verified == 0) {
                        $failData[] = $record;
                    }
                }
            }
        }
        
        if ($type == 'group') {
            $filename = 'recipients-list-' . $id . '.csv';
            $group_recipient_table = OAM_Helper::$group_recipient_table;
           
            $failData = $wpdb->get_results($wpdb->prepare("
                SELECT * FROM {$group_recipient_table} WHERE user_id = %d AND group_id = %d  AND visibility = %d
            ", $user, $id, 1));
        }
    
       
        if (!empty($failData)) {
            $csvData = array(
                array('Full name', 'Company name', 'Mailing address', 'Suite/Apt', 'City', 'State', 'Zipcode', 'Quantity', 'Greeting', 'Reasons'),
            );
    
            foreach ($failData as $record) {
                $decoded_reasons = json_decode($record->reasons, true);
                $reasons = (is_array($decoded_reasons)) ? implode(", ", $decoded_reasons) : (string) $decoded_reasons;
    
                $csvData[] = array(
                    $record->full_name,
                    $record->company_name,
                    $record->address_1,
                    $record->address_2,
                    $record->city,
                    $record->state,
                    $record->zipcode,
                    $record->quantity,
                    $record->greeting,
                    $reasons,
                );
            }
    
            // Define new folder path inside wp-content
            $custom_dir = WP_CONTENT_DIR . '/download_recipient_list';
            $custom_url = content_url('/download_recipient_list');
    
            // Create the directory if it does not exist
            if (!file_exists($custom_dir)) {
                wp_mkdir_p($custom_dir);
            }
    
            // Use $id in the filename
           
            $file_path = $custom_dir . '/' . $filename;
            $file_url = $custom_url . '/' . $filename;
    
            // Remove the existing file if it exists
            if (file_exists($file_path)) {
                unlink($file_path);
            }
    
            // Open file for writing
            $output = fopen($file_path, 'w');
            if ($output === false) {
                wp_send_json_error(array('message' => 'Failed to create CSV file.'));
                exit;
            }
    
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
    
            // Check if file exists before sending the response
            if (file_exists($file_path)) {
                wp_send_json_success(array(
                    'url' => $file_url,
                    'filename' => $filename,
                ));
            } else {
                wp_send_json_error(array('message' => 'CSV file not found.'));
            }
            exit;
        } else {
            wp_send_json_error(array('message' => 'No failed records found.'));
        }
    }
    
    // Callback function for create Group
    public function orthoney_create_group_handler() {
        // Check if the group name is provided
        if (empty($_POST['group_name'])) {
            wp_send_json_error(['message' => 'Recipients list name is required.']);
        }
    
        $group_name = sanitize_text_field($_POST['group_name']);
        $group_id = sanitize_text_field($_POST['group_id']);
        $group_status = sanitize_text_field($_POST['group_status']);
    
        // Your logic to insert the group into the database
        global $wpdb;
        $table = $wpdb->prefix . 'recipient_group';
        if($group_status  == 'edit'){
            $result = $wpdb->update(
                $table,
                ['name' => $group_name], // Data to update
                ['id' => $group_id],   // WHERE condition
                ['%s'],                // Format for data to update
                ['%d']                 // Format for WHERE condition
            );
            if ($result !== false) {
                wp_send_json_success(['message' => 'Recipients list name updated successfully!']);
            } else {
                wp_send_json_error(['message' => 'Failed to update Recipients list name.']);
            }
        }
        if($group_status  == 'create'){
            $result = $wpdb->insert(
                $table,
                [
                    'user_id' => get_current_user_id(),
                    'name' => $group_name,
                ],
                ['%d', '%s']
            );
        
            if ($result) {
                wp_send_json_success(['message' => 'Recipients list created successfully!']);
            } else {
                wp_send_json_error(['message' => 'Failed to create recipients list.']);
            }
        }
        

    }

    // Callback function for deleted Group
    public function orthoney_deleted_group_handler() {
        // Check if the group name is provided
        if (empty($_POST['group_id'])) {
            wp_send_json_error(['message' => 'Group name is required.']);
        }
    
        $group_id = sanitize_text_field($_POST['group_id']);
    
        // Your logic to insert the group into the database
        global $wpdb;
        $group_table = OAM_Helper::$group_table;
    
        $result = $wpdb->update(
            $group_table,
            ['visibility' => 0],
            ['id' => $group_id]
        );
    
        if ($result) {
            wp_send_json_success(['message' => 'Group deleted successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete group or group not found.']);
        }
    }

    // Callback function for deleted Incomplete order
    public function orthoney_delete_incompleted_order_button_handler() {
        // Check if the Incomplete order name is provided
        if (empty($_POST['id'])) {
            wp_send_json_error(['message' => 'Incomplete order is required.']);
        }
    
        $id = sanitize_text_field($_POST['id']);
    
        // Your logic to insert the Incomplete order into the database
        global $wpdb;
        $order_process_table = OAM_Helper::$order_process_table;
    
        $result = $wpdb->update(
            $order_process_table,
            ['visibility' => 0],
            ['id' => $id]
        );
    
        if ($result) {
            wp_send_json_success(['message' => 'Incomplete order deleted successfully!']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete incomplete order or incomplete order not found.']);
        }
    }

    // Callback function for bulk deleted recipient 
    public function orthoney_bulk_deleted_recipient_handler() {
        
        if (isset($_POST['ids']) && is_array(json_decode(stripslashes($_POST['ids'])))) {
            global $wpdb;
            $table = OAM_Helper::$order_process_recipient_table;
            $ids = json_decode(stripslashes($_POST['ids']));
    
            // Prepare the IDs for the IN clause, ensuring they are integers
            $ids_placeholder = implode(',', array_map('intval', $ids));

            // Update the records based on the IDs array
            $result = $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$table} SET visibility = 0 WHERE id IN ($ids_placeholder)",
                    $ids
                )
            );

            if ($result !== false) {
                foreach ($ids as $key => $id) {
                    OAM_Helper::order_process_recipient_activate_log($id, 'bulk deleted', '');
                }
                wp_send_json_success([
                    'message' => 'Recipient deleted successfully!',
                    'groupId' => $groupId,
                    'user' => get_current_user_id(),
    
                ]);
            } else {
                wp_send_json_error(['message' => 'Failed to delete recipient or recipient not found.']);
            }
        
        } else {
            wp_send_json_error(['message' => 'Failed to delete recipient or recipient not found.']);
        }

    }
    
    // Callback function for deleted recipient 
    public function orthoney_deleted_recipient_handler() {
        // Check if the recipient id is provided
        if (empty($_POST['id'])) {
            wp_send_json_error(['message' => 'Recipient is required.']);
        }
        $method = !empty($_POST['method']) ? $_POST['method'] : 'process';
    
        $id = sanitize_text_field($_POST['id']);

        // Your logic to insert the recipient into the database
        global $wpdb;
        $table = OAM_Helper::$order_process_recipient_table;
        if($method == 'group'){
            $table = OAM_Helper::$group_recipient_table;
        }

        $result = $wpdb->update(
            $table,
            ['visibility' => 0],
            ['id' => $id]
        );
    
        if ($result) {
            OAM_Helper::order_process_recipient_activate_log($id, 'deleted', '', $method);
            wp_send_json_success([
                'message' => 'A new recipient has been added successfully!',
                'user' => get_current_user_id(),

            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to delete recipient or recipient not found.']);
        }
    }
    // Callback function edit recipient order
   public function orthoney_manage_recipient_order_form_handler() {
    check_ajax_referer('oam_nonce', 'security');

    global $wpdb;

    $orderID = isset($_POST['order_id']) ? $_POST['order_id'] : 0;
    if (!$orderID) {
        wp_send_json_error(['message' => 'Invalid Order ID']);
    }

    $recipient_order_table = OAM_Helper::$recipient_order_table;

    $existing_recipient = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $recipient_order_table WHERE recipient_order_id = %d", $orderID)
    );

    if (!$existing_recipient) {
        wp_send_json_error(['message' => 'Something went wrong. Please try again.']);
    }

    $data = [
        'full_name'        => sanitize_text_field($_POST['full_name'] ?? ''),
        'company_name'     => sanitize_text_field($_POST['company_name'] ?? ''),
        'address_1'        => sanitize_textarea_field($_POST['address_1'] ?? ''),
        'address_2'        => sanitize_textarea_field($_POST['address_2'] ?? ''),
        'city'             => sanitize_text_field($_POST['city'] ?? ''),
        'state'            => sanitize_text_field($_POST['state'] ?? ''),
        'zipcode'          => sanitize_text_field($_POST['zipcode'] ?? ''),
        'greeting'         => sanitize_textarea_field($_POST['greeting'] ?? ''),
        'updated_date'     => current_time('mysql'),
    ];

    $validation_result = json_decode(OAM_Helper::validate_address(
        $data['address_1'],
        $data['address_2'],
        $data['city'],
        $data['state'],
        $data['zipcode']
    ), true);

    if (isset($validation_result['success']) && $validation_result['success'] === false && empty($_POST['invalid_address'])) {
        wp_send_json_error(['message' => $validation_result['message']]);
    }

    $result = $wpdb->update($recipient_order_table, $data, ['recipient_order_id' => $orderID]);

    if ($result !== false) {
        OAM_Helper::order_process_recipient_activate_log($orderID, 'Edit Recipient Order', '', 'edit_order');
        wp_send_json_success(['message' => 'Recipient details updated successfully']);
    }

    wp_send_json_error(['message' => 'Failed to update order']);

    wp_die(); // terminate execution properly
}

    
    // Callback function for get recipient  order details base in id
    public function orthoney_get_recipient_order_base_id_handler() {
        global $wpdb;

        $recipient_order_table = $wpdb->prefix . 'oh_recipient_order';

        $orderID = !empty($_POST['id']) ? $_POST['id'] : 0;

        if (empty($orderID)) {
            $response = ['success' => false, 'message' => 'Invalid order ID.'];
            wp_send_json_error($response);
        }

        $recipientOrderDetails = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$recipient_order_table} WHERE recipient_order_id = %s",
                $orderID
            )
        );

        $full_name      = $recipientOrderDetails->full_name;
        $company_name   = $recipientOrderDetails->company_name;
        $address_1      = $recipientOrderDetails->address_1;
        $address_2      = $recipientOrderDetails->address_2;
        $city           = $recipientOrderDetails->city;
        $state          = $recipientOrderDetails->state;
        $postcode       = $recipientOrderDetails->zipcode;
        $country        = $recipientOrderDetails->country ?? 'US';
        $total_quantity = $recipientOrderDetails->quantity;
        $greeting       = html_entity_decode(stripslashes($recipientOrderDetails->greeting)) ?? '';

        $states = WC()->countries->get_states('US');
        $full_state_name = isset($states[$state]) ? $states[$state] : $state;
        $full_state = $full_state_name . " (" . $state . ")";

        $data = [
            'success'       => true,
            'order_id'      => $orderID,
            'full_name'     => $full_name,
            'company_name'  => $company_name,
            'address_1'     => $address_1,
            'address_2'     => $address_2,
            'city'          => $city,
            'state'         => $state,
            'full_state'    => $full_state,
            'zipcode'       => $postcode,
            'country'       => $country,
            'greeting'      => $greeting,
            'quantity'      => $total_quantity,
        ];

        wp_send_json_success($data);
    }

    // Callback function for get recipient base in id
    public function orthoney_get_recipient_base_id_handler($recipient = '') {
    global $wpdb;

    // Determine recipient ID from POST or fallback to the function parameter
    $recipientID = !empty($_POST['id']) ? intval($_POST['id']) : intval($recipient);
    $method = !empty($_POST['method']) ? $_POST['method'] : 'process';

    if (empty($recipientID)) {
        $response = ['success' => false, 'message' => 'Invalid recipient ID.'];
        return !empty($recipient) ? json_encode($response) : wp_send_json_error($response);
    }

    // Table name
    $recipient_table = OAM_Helper::$order_process_recipient_table;
    if ($method == 'group') {
        $recipient_table = OAM_Helper::$group_recipient_table;
    }

    // Fetch recipient record as an associative array
    $record = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $recipient_table WHERE id = %d", $recipientID),
        ARRAY_A
    );

    if (!empty($record)) {
        // Apply stripslashes to each value
        $record = array_map(function($item) {
            return is_string($item) ? stripslashes($item) : $item;
        }, $record);

        $response = ['success' => true, 'data' => $record];
        return !empty($recipient) ? json_encode($response) : wp_send_json_success($record);
    } else {
        $response = ['success' => false, 'message' => 'No data found.'];
        return !empty($recipient) ? json_encode($response) : wp_send_json_error($response);
    }
}


    // Callback function for manually add new recipient and edit recipient
    
    public function orthoney_manage_recipient_form_handler() {
        $method =  isset($_POST['method']) ? $_POST['method'] : 'process';
        
        $group_id =  isset($_POST['group_id']) ? $_POST['group_id'] : 0;
        $invalid_address =  isset($_POST['invalid_address']) ? $_POST['invalid_address'] : 0;
        global $wpdb;
        
        // Check for required fields
        $required_fields = [
            'full_name' => 'Full Name',
            'address_1' => 'Mailing Address',
            'city' => 'City',
            'state' => 'State',
            'quantity' => 'quantity',
            'zipcode' => 'zipcode',
        ];
        
        $missing_fields = [];
        
        foreach ($required_fields as $key => $field) {
            if (!isset($_POST[$key]) || empty($_POST[$key])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            wp_send_json_error([
                'message' => 'Missing required fields: '.implode(', ', $missing_fields)
            ]);
        }
        
        
            $address_verified = $_POST['address_verified'] ? $_POST['address_verified'] : 0 ;
            // Sanitize and prepare common data
            $data = [
                'full_name' => sanitize_text_field($_POST['full_name']),
                'company_name'  => sanitize_text_field($_POST['company_name']),
                'address_1'  => sanitize_textarea_field($_POST['address_1']),
                'address_2'  => isset($_POST['address_2']) ? sanitize_textarea_field($_POST['address_2']) : '',
                'city'       => sanitize_text_field($_POST['city']),
                'state'      => sanitize_text_field(strtoupper($_POST['state'])),
                'zipcode'    => sanitize_text_field($_POST['zipcode']),
                'quantity'   => $_POST['quantity'],
                'greeting'   => sanitize_textarea_field($_POST['greeting']),
                'reasons'    => '',
                'verified'   => 1,
                'visibility' => 1,
                'timestamp'  => current_time('mysql'),
            ];
            
            $table = OAM_Helper::$order_process_recipient_table;
            
            
            $recipient_id = isset($_POST['recipient_id']) ? absint($_POST['recipient_id']) : null;
            $process_id = isset($_POST['pid']) ? absint($_POST['pid']) : null;

            if($invalid_address == 1){
                 $validation_result = json_decode(OAM_Helper::validate_address(
                    $data['address_1'], $data['address_2'], $data['city'], strtoupper($data['state']), $data['zipcode']
                ), true);

                $data['reasons'] = $validation_result['message'];
                if($data['reasons'] != '' AND $data['reasons'] != 'Valid and deliverable address.' ){
                    $data['address_verified'] = 0;
                }
                
                $result = $wpdb->update(
                            $table,
                            $data,
                            ['id' => $recipient_id]
                        );
                        $status = 'update';
            
                    OAM_Helper::order_process_recipient_activate_log($recipient_id, $status, wp_json_encode($changes), $method);
                    $success_message = 'Recipient details updated.';
                    if ($result !== false) {
                    wp_send_json_success([
                        'status'       => $status,
                        'user'         => get_current_user_id(),
                        'recipient_id' => $recipient_id,
                        'message'      => $success_message,
                    ]);
                } else {
                    wp_send_json_error([
                        'message' => $wpdb->last_error ?: 'Database operation failed.',
                    ]);
                }

            }
       
            
            // Construct the key for checking existing records
            $record_key = $data['full_name'] . '|' . 
            $data['address_1'] . '|' . 
            $data['city'] . '|' . 
            $data['state'] . '|' . 
            $data['zipcode'];
            
            $status = '';
            $result = false;
            
            if($method == 'group'){
                $table = OAM_Helper::$group_recipient_table;
                $existing_recipient = $wpdb->get_row( $wpdb->prepare(
                    "SELECT id, visibility FROM $table WHERE full_name = %s AND address_1 = %s AND city = %s AND state = %s AND zipcode = %s AND group_id = %d", 
                    $data['full_name'], $data['address_1'], $data['city'], strtoupper($data['state']), $data['zipcode'], $group_id
                    )
                );
            }else{
            
                $existing_recipient = $wpdb->get_row( $wpdb->prepare(
                    "SELECT id, visibility FROM $table WHERE full_name = %s AND address_1 = %s AND city = %s AND state = %s AND zipcode = %s AND pid = %d", 
                    $data['full_name'], $data['address_1'], $data['city'], strtoupper($data['state']), $data['zipcode'], $process_id
                    )
                );
            }
    
            
            if (!$recipient_id && $existing_recipient) {
                if ($existing_recipient->visibility == 1) {
                    wp_send_json_error(['message' => 'A recipient with the same details already exists.']);
                } else {
                    // Only update visibility to 1
                    $result = $wpdb->update($table, ['visibility' => 1], ['id' => $existing_recipient->id], ['address_verified' => 0]);
                    if ($result !== false) {
                        wp_send_json_success([
                            'status'       => 'update',
                            'recipient_id' => $existing_recipient->id,
                            'message'      => 'Recipient reactivated successfully!',
                        ]);
                    
                        OAM_Helper::order_process_recipient_activate_log($existing_recipient->id, 'reactivated', '', $method);
                    } else {
                        wp_send_json_error(['message' => 'Failed to update visibility.']);
                    }
                }
            }


        if ($recipient_id) {
            // Update existing recipient
            $existing_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $recipient_id), ARRAY_A);
            $changes = [];
            
            foreach ($data as $key => $value) {
                if (isset($existing_data[$key]) && $existing_data[$key] != $value) {
                    $changes[$key] = [
                        'old' => $existing_data[$key],
                        'new' => $value
                    ];
                }
            }
            if($address_verified == 1){

                $validation_result = json_decode(OAM_Helper::validate_address(
                    $data['address_1'], $data['address_2'], $data['city'], strtoupper($data['state']), $data['zipcode']
                ), true);
        
                if ($validation_result) {
                    $verified_status = $validation_result['success'] ? 1 : 0;
                    $data['address_verified'] = $verified_status;
                    $data['reasons'] = $validation_result['message'];
                    if($data['reasons'] != '' AND $data['reasons'] != 'Valid and deliverable address.' ){
                        $data['address_verified'] = 0;
                    }
                    
                    
                    if ($verified_status == 0) {
                        wp_send_json_error(['message' => $validation_result['message']]);
                    }else{
                        
                        // wp_send_json_success(['message' => 'The address is correct.']);
                        $result = $wpdb->update(
                            $table,
                            $data,
                            ['id' => $recipient_id]
                        );
                    }
                }

            }else{
                $validation_result = json_decode(OAM_Helper::validate_address(
                    $data['address_1'], $data['address_2'], $data['city'], strtoupper($data['state']), $data['zipcode']
                ), true);

                $data['reasons'] = $validation_result['message'];
                    if($data['reasons'] != '' AND $data['reasons'] != 'Valid and deliverable address.' ){
                       $data['address_verified'] = 0;
                    }
                
                $result = $wpdb->update($table, $data, ['id' => $recipient_id]);

            }
            

            $status = 'update';
            
            OAM_Helper::order_process_recipient_activate_log($recipient_id, $status, wp_json_encode($changes), $method);
            $success_message = 'Recipient details updated.';

           
        } else {
            // Insert new recipient
            $data['user_id'] = get_current_user_id();
            if($method == 'group'){
                $data['group_id'] = $group_id;
            }else{
                $data['pid'] = $_POST['pid'];
            }
            $data['new'] = 1;
            $data['address_verified'] = 0;
            $data['update_type'] = sanitize_text_field("add_manually");
            $result = $wpdb->insert($table, $data);
            $recipient_id = $wpdb->insert_id;
            $status = 'new';
            

            OAM_Helper::order_process_recipient_activate_log($wpdb->insert_id, $status, 'manually added' , $method);
            $success_message = 'Recipient Details Added.';
        }
        
        // Handle the result
        if ($result !== false) {
            wp_send_json_success([
                'status'       => $status,
                'user'         => get_current_user_id(),
                'recipient_id' => $recipient_id,
                'message'      => $success_message,
            ]);
        } else {
            wp_send_json_error([
                'message' => $wpdb->last_error ?: 'Database operation failed.',
            ]);
        }
    }
    
    /**
	 * An AJAX handler function that reverify address 
	 *
	 * Starts the list before the elements are added.
	 *
	 * @return JSON and array depend to the parameter
     * 
     * USE Helper function 
     * validate_address(): This function verify address using the Smarty API
     * 
	 */
    public function orthoney_reverify_address_recipient_handler() {
        check_ajax_referer('oam_nonce', 'security');  
        global $wpdb;
    
        $order_process_table           = OAM_Helper::$order_process_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        
        $recipient_id  = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
    
        // Verify recipient addresses if group_id is provided
        
        $recipient = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, address_1, address_2, city, state, zipcode FROM {$order_process_recipient_table} 
                WHERE id = %d",
                $recipient_id
            )
        );
        
        if ($recipient) {
            $validation_result = json_decode(OAM_Helper::validate_address(
                $recipient->address_1, $recipient->address_2, $recipient->city, $recipient->state, $recipient->zipcode
            ), true);
        
            if ($validation_result) {
                $verified_status = $validation_result['success'] ? 1 : 0;
        
                
        
                if ($update_result == 0) {
                    wp_send_json_error(['message' => 'The address is incorrect. Please enter the correct address.']);
                }else{
                    $update_result = $wpdb->update(
                        $order_process_recipient_table,
                        ['address_verified' => $verified_status],
                        ['id' => $recipient->id]
                    );
                    wp_send_json_success(['message' => 'The address is correct.']);
                }
            }
        }        
    
        wp_send_json_success(['message' => 'The address is correct. ']);
        
        wp_die();
    }

    public function orthoney_affiliate_status_toggle_block_handler() {

        /**
         * 1 : Unblock/Active
         * 0 : Block/Deactivate
         * -1 : Pending Request
         */ 
        global $wpdb;
        $user_id = get_current_user_id();
        $oh_affiliate_customer_linker = OAM_Helper::$oh_affiliate_customer_linker;

        $customer_id = get_current_user_id();
        $affiliate_id = intval($_POST['affiliate_id']);
        $status = intval($_POST['status']);

        $update_status = -1;
        if($status == 0){
            $update_status = 1;
        }
        
        $update_result = $wpdb->update(
            $oh_affiliate_customer_linker,
            ['status' => $update_status],
            [
                'affiliate_id' => $affiliate_id, 
                'customer_id' => $customer_id
            ],
            ['%d'], ['%d', '%d']
        );

        if ($update_status == 1) {
            wp_send_json_success(['message' => 'The organization has been blocked.']);
        }else{
            wp_send_json_success(['message' => 'The organization has been unblocked.']);
        }

        wp_send_json_success();
    }
    
       public function search_affiliates_handler() {
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';

        $affiliates_content = OAM_Helper::manage_affiliates_content($search);
        $result = json_decode($affiliates_content, true);

        $affiliates = $result['data']['user_info'];
        $blocked_affiliates = $result['data']['affiliates'];

        ob_start();
        ?>
        <div id="affiliate-results">
            <table>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($affiliates)) {
                        foreach ($affiliates as $key => $affiliate): 
                            $is_blocked = $blocked_affiliates[$key]['status'];
                            if ($is_blocked == $filter || $filter == 'all') {
                                $token = $blocked_affiliates[$key]['token'];
                                $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
                                $user_id = $affiliate['user_id'];

                                $status_label = 'Blocked';
                                if ($is_blocked == 0) $status_label = 'Pending Approval';
                                if ($is_blocked == 1) $status_label = 'Approved';

                                $orgName = get_user_meta($user_id, '_orgName', true);
                                if (empty($orgName)) {
                                    $orgName = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
                                }
                                ?>
                                <tr>
                                    <td><div class="thead-data">Token</div><?php echo esc_html($affiliate['token']); ?></td>
                                    <td><div class="thead-data">Name</div><?php echo esc_html($affiliate['display_name']); ?></td>
                                    <td><div class="thead-data">Status</div><?php echo esc_html($status_label); ?></td>
                                    <td><div class="thead-data">Action</div>
                                        <?php if ($is_blocked == -1): ?>
                                            <a href="<?php echo $current_url . '?action=organization-link&token=' . $token; ?>" class="w-btn us-btn-style_1">Accept Linking Request</a>
                                        <?php else: ?>
                                            <button class="affiliate-block-btn w-btn <?php echo ($is_blocked == 1) ? 'us-btn-style_1' : 'us-btn-style_2'; ?>" 
                                                data-affiliate="<?php echo esc_attr($user_id); ?>"
                                                data-blocked="<?php echo ($is_blocked == 1) ? '1' : '0'; ?>">
                                                <?php echo ($is_blocked == 1) ? 'Block' : 'Unblock'; ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        endforeach;
                    } else {
                        echo '<tr><td colspan="4" class="no-available-msg">No organization found!</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
        echo ob_get_clean();
        wp_die();
    }

     
    //code update by db
    // Shared helper method to avoid repeating logic
    // AJAX: Load orders for display
   
    public function orthoney_customer_order_process_ajax_handler() {
        $order_type = sanitize_text_field($_POST['table_order_type'] ?? 'main_order');

        if ($order_type === 'main_order') {
            $this->orthoney_handle_main_order_request();
        } elseif ($order_type === 'sub_order_order') {
            $this->orthoney_handle_sub_order_request();
        } else {
            wp_send_json_error(['message' => 'Invalid order type']);
        }
    }

    public function orthoney_handle_main_order_request() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;

        $user_id = get_current_user_id();
        $search = sanitize_text_field($_POST['search']['value'] ?? '');
        $draw = intval($_POST['draw']);
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        $tabletype = isset($_POST['tabletype']) ? sanitize_text_field($_POST['tabletype']) : 'administrator-dashboard';

        $custom_order_type = sanitize_text_field($_POST['custom_order_type'] ?? 'all');
        $custom_order_status = sanitize_text_field($_POST['custom_order_status'] ?? 'all');
        $selected_customer_id = sanitize_text_field($_POST['selected_customer_id'] ?? '');

        $orders_table = $wpdb->prefix . 'wc_orders';
        $order_meta_table = $wpdb->prefix . 'wc_orders_meta';
        $recipient_order_table = $wpdb->prefix . 'oh_recipient_order';
        $order_relation = $wpdb->prefix . 'oh_wc_order_relation';
        $order_addresses = $wpdb->prefix . 'wc_order_addresses';

        $filtered_orders = OAM_Helper::get_filtered_orders($user_id, 'main_order', $custom_order_type, $custom_order_status, $search, false, $start, $length, $selected_customer_id);

        $where = [];
        $values = [];
        $join = "INNER JOIN $order_relation AS rel ON rel.wc_order_id = orders.id";
        $join .= " LEFT JOIN $recipient_order_table AS rec ON rec.order_id = rel.order_id";

        if ($custom_order_type === "multiple_address") {
            $where[] = "rel.order_type = %s";
            $values[] = 'multi_address';
        } elseif ($custom_order_type === "single_address") {
            $where[] = "rel.order_type = %s";
            $values[] = 'single_address';
        }

        if (!empty($_REQUEST['search_by_organization'])) {
            $search_by_organization = sanitize_text_field($_REQUEST['search_by_organization']);
            
            $search_terms = array_filter(array_map('trim', explode(',', $search_by_organization)));

             $dsr_token = isset($_REQUEST['dsr_affiliate_token']) ? sanitize_text_field($_REQUEST['dsr_affiliate_token']) : null;
            if ($dsr_token) {
                $search_terms = [];
                $search_terms[] = $dsr_token;
            }


            if (!empty($search_terms)) {
                $organization_clauses = [];
                $count = 0;
                foreach ($search_terms as $term) {
                    $organization_clauses[] = "(CAST(orders.id AS CHAR) = %s OR rel.affiliate_code LIKE %s)";
                    $values[] = $term;
                    $values[] = '%' . $wpdb->esc_like($term) . '%';
                }
                $where[] = '(' . implode(' OR ', $organization_clauses) . ')';
            }
            
        }

        if (!empty($_REQUEST['search_by_recipient'])) {
            $search_by_recipient = sanitize_text_field($_REQUEST['search_by_recipient']);
            $where[] = "rec.full_name LIKE %s";
            $values[] = '%' . $wpdb->esc_like($search_by_recipient) . '%';
        }

         if (!empty($search)) {
            if ($tabletype == 'administrator-dashboard' || $tabletype == 'organization-dashboard' || $tabletype == 'sales-representative-dashboard') {
                 $join .= " LEFT JOIN {$wpdb->users} AS u ON u.ID = orders.customer_id";
                 $where[] = "(orders.id = %d OR rec.order_id = %d OR rel.wc_order_id = %d OR u.display_name LIKE %s )";
          
                 $values[] = (int) $search; 
                 $values[] = (int) $search; 
                 $values[] = (int) $search; 
                $values[] = '%' . $wpdb->esc_like($search) . '%';
            }else{
                $join .= " LEFT JOIN $order_addresses AS addr ON addr.order_id = orders.id AND addr.address_type = 'billing'";
                $where[] = "(orders.id = %d OR CONCAT(addr.first_name, ' ', addr.last_name) LIKE %s OR addr.order_id = %d OR rel.wc_order_id = %d)";
                $values[] = (int) $search;
                $values[] = '%' . $wpdb->esc_like($search) . '%';
                $values[] = (int) $search; 
                $values[] = (int) $search; 
            }
        }

        if (!empty($_REQUEST['selected_order_status']) && $_REQUEST['selected_order_status'] !== "all") {
            $where[] = "orders.status = %s";
            $values[] = sanitize_text_field($_REQUEST['selected_order_status']);
        }else{
            $statuses = ['wc-cancelled', 'wc-refunded', 'wc-failed'];
            $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
            $where[] = "orders.status NOT IN ($placeholders)";
            $values = array_merge($values, $statuses);
        }

         if (!empty($_REQUEST['custom_payment_method']) && $_REQUEST['custom_payment_method'] !== "all") {
            if($_REQUEST['custom_payment_method'] == 'Check payments'){
                $where[] = "(orders.payment_method_title = %s OR orders.payment_method_title = %s)";
                $values[] = 'Check payments';
                $values[] = 'Pay by Check';
            }else{
                $where[] = "orders.payment_method_title = %s";
                $values[] = sanitize_text_field($_REQUEST['custom_payment_method']);
            }
            
        }
        
        if (
            isset($_REQUEST['selected_min_qty'], $_REQUEST['selected_max_qty']) &&
            is_numeric($_REQUEST['selected_min_qty']) &&
            is_numeric($_REQUEST['selected_max_qty'])
        ) {
            $where[] = "rel.quantity BETWEEN %d AND %d";
            $values[] = (int) $_REQUEST['selected_min_qty'];
            $values[] = (int) $_REQUEST['selected_max_qty'];
        }


        if($_REQUEST['draw'] == 1){
            $year = !empty($_REQUEST['selected_year']) ? intval($_REQUEST['selected_year']) : date("Y");
            $where[] = "YEAR(orders.date_created_gmt) = %d";
            $values[] = $year;
         }


         if(!empty($_REQUEST['selected_year'])){
            $year = $_REQUEST['selected_year'];
            $where[] = "YEAR(orders.date_created_gmt) = %d";
            $values[] = $year;
        }


        if ($tabletype == 'administrator-dashboard' || $tabletype == 'organization-dashboard' || $tabletype == 'sales-representative-dashboard') {
            if (!empty($selected_customer_id) && is_numeric($selected_customer_id)) {
                $where[] = "orders.customer_id = %d";
                $values[] = (int) $selected_customer_id;
            }
        } else {
            $where[] = "orders.customer_id = %d";
            $values[] = $user_id;
        }

        if (!empty($_REQUEST['search_by_organization'])) {
            if($_REQUEST['search_by_organization'] != 'Orthoney'){
                $where[] = "(rel.affiliate_user_id != 0)";
            }
        }

        $where[] = "orders.status NOT IN ('trash', 'wc-checkout-draft')";

       $sql = $wpdb->prepare(
            "SELECT  COUNT( DISTINCT orders.id) FROM {$orders_table} AS orders
            $join
            WHERE " . implode(' AND ', $where),
            ...$values
        );

        
        $total_orders = $wpdb->get_var($sql);

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $total_orders,
            'recordsFiltered' => $total_orders,
            'data' => $filtered_orders,
        ]);
    }

    public function orthoney_handle_sub_order_request() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
        $orders_table = $wpdb->prefix . 'wc_orders';

        $user_id = get_current_user_id();
        $draw = intval($_POST['draw']);
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        $tabletype = isset($_POST['tabletype']) ? sanitize_text_field($_POST['tabletype']) : 'administrator-dashboard';

        $custom_order_type = sanitize_text_field($_POST['custom_order_type'] ?? 'all');
        $custom_order_status = sanitize_text_field($_POST['custom_order_status'] ?? 'all');
        $search_val = sanitize_text_field($_POST['search']['value'] ?? '');

        $jar_table = $wpdb->prefix . 'oh_recipient_order';
        $filtered_orders = OAM_Helper::get_jars_orders($user_id, 'sub_order_order', $custom_order_type, $custom_order_status, $search_val, false, $start, $length);

        $where = [];
        $values = [];

        $min_qty = is_numeric($_REQUEST['selected_min_qty'] ?? null) ? (int) $_REQUEST['selected_min_qty'] : 1;
        $max_qty = is_numeric($_REQUEST['selected_max_qty'] ?? null) ? (int) $_REQUEST['selected_max_qty'] : 1000;
        $year = is_numeric($_REQUEST['selected_year'] ?? null) ? (int) $_REQUEST['selected_year'] : '';
        $selected_customer_id = is_numeric($_REQUEST['selected_customer_id'] ?? null) ? (int) $_REQUEST['selected_customer_id'] : null;

        $where[] = "{$jar_table}.quantity BETWEEN %d AND %d";
        $values[] = $min_qty;
        $values[] = $max_qty;

        if (!isset($_REQUEST['selected_year'])) {
            $where[] = "YEAR({$jar_table}.created_date) = %d";
            $values[] = date("Y");
        }else{
            if (!empty($_REQUEST['selected_year'])) {
                $where[] = "YEAR({$jar_table}.created_date) = %d";
                $values[] = $year;
            }
        }

       if (!empty($_REQUEST['search_by_organization'])) {
            $search_org = sanitize_text_field($_REQUEST['search_by_organization']);
            $search_terms = array_filter(array_map('trim', explode(',', $search_org)));

            $org_clauses = [];

            $dsr_token = (isset($_REQUEST['jar_dsr_affiliate_token']) && $_REQUEST['jar_dsr_affiliate_token'] != '') ? sanitize_text_field($_REQUEST['jar_dsr_affiliate_token']) : null;
            if ($dsr_token != null) {
                $search_terms = [$dsr_token]; // overwrite
            }

            foreach ($search_terms as $term) {
                $org_clauses[] = "({$jar_table}.affiliate_token LIKE %s)";
                $values[] = '%' . $wpdb->esc_like($term) . '%';
            }

            if (!empty($org_clauses)) {
                $where[] = '(' . implode(' OR ', $org_clauses) . ')';
            }
        }


        
        if (!empty($search_val)) {
            $where[] = "(o.id LIKE %s OR {$jar_table}.recipient_order_id LIKE %s OR {$jar_table}.full_name LIKE %s)";
            $search_param = '%' . $wpdb->esc_like($search_val) . '%';
            $values[] = $search_param;
            $values[] = $search_param;
            $values[] = $search_param;
        }

        if ($tabletype == 'administrator-dashboard' || $tabletype == 'organization-dashboard' || $tabletype == 'sales-representative-dashboard') {
            if ($selected_customer_id) {
                $where[] = "{$jar_table}.user_id = %d";
                $values[] = $selected_customer_id;
            }

        } else {
            $where[] = "user_id = %d";
            $values[] = get_current_user_id();
        }

        $joins = "
            LEFT JOIN {$wpdb->prefix}oh_wc_order_relation oor ON {$jar_table}.order_id = oor.order_id
            LEFT JOIN {$orders_table} o ON oor.wc_order_id = o.id
        ";

        $where[] = "o.status NOT IN ('trash', 'wc-checkout-draft') ";

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = $wpdb->prepare("SELECT COUNT({$jar_table}.id) FROM {$jar_table} $joins $where_sql", ...$values);

        
        $total_orders = $wpdb->get_var($sql);

        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $total_orders,
            'recordsFiltered' => $total_orders,
            'data' => $filtered_orders,
        ]);
    }



    public function remove_pdf_data_handler() {
        $file_url = isset($_REQUEST['file_url']) ? esc_url_raw($_REQUEST['file_url']) : '';
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $file_url);
    
        if (file_exists($file_path)) {
            unlink($file_path);
            wp_send_json_success('PDF removed successfully');
        } else {
            wp_send_json_error('PDF not found');
        }

    }
    // AJAX: Order Export PDF generate 
   public function orthoney_customer_order_export_ajax_pdf_handler() {

        $paperDeadlineDate = date_i18n(OAM_Helper::$date_format, strtotime(get_field('paper_deadline', 'option')));
        $shipStartDate = date_i18n(OAM_Helper::$date_format, strtotime(get_field('free_shipping_start_date', 'option')));
        $shipEndDate = date_i18n(OAM_Helper::$date_format, strtotime(get_field('free_shipping_end_date', 'option')));

        $ort_shipping_cost = get_field('ort_shipping_cost', 'option')?:8;

        $order_id_array = $_REQUEST['selectedValues'];
        $custom_order_pdf_type = $_REQUEST['custom_order_pdf_type'];
        $current_user = wp_get_current_user();
        $current_user_email = $current_user->user_email;

        $ex_words = ['Honey from the Heart', 'Orthoney'];
    
        // Load Dompdf
        if (!class_exists('\Dompdf\Dompdf')) {
            require_once plugin_dir_path(__FILE__) . 'libs/dompdf/vendor/autoload.php';
        }
    
        $starthtml = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>2025 Honey Reorder Form</title>
            <style>
                body {
                    font-family: DejaVu Sans, sans-serif;
                    font-size: 12px;
                    line-height: 1.6;
                }
                h2 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .section {
                    margin-bottom: 20px;
                }
                .label {
                    font-weight: bold;
                }
                .order-summary, .addresses {
                    border: 1px solid #000;
                    padding: 10px;
                }
                .address-block {
                    margin-bottom: 10px;
                }
                .payment-section {
                    border: 1px dashed #000;
                    padding: 10px;
                }
                    .page-break {
                    page-break-before: always;
                    }
            </style>
        </head>
        <body>';

        $endhtml = '</body></html>';
    
        global $wpdb;
        foreach ($order_id_array as $order_id) {

            $custom_order_id = $order_id;

            if ($custom_order_pdf_type == "2e" || $custom_order_pdf_type == "4e") {
                $html = '';
            }
            // if (!$order) continue;
            $distName = 'Honey from the Heart';
            
            // $sub_order_id =  OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID')?: $order_id;
            $sub_order_id =  $order_id;

            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT order_id
                FROM {$wpdb->prefix}wc_orders_meta
                WHERE meta_key = %s AND meta_value = %s",
                '_orthoney_OrderID',
                $sub_order_id
            ));

            $customer_email = '';
            $order = wc_get_order($order_id);
            $orderdata = OAM_COMMON_Custom::orthoney_get_order_data($order_id);

            $user_id = $order->get_user_id();
            if ($user_id) {
                $user_info = get_userdata($user_id);
                $customer_email = $user_info->user_email;
            }

          
            $name = esc_html($orderdata['customer_name']);
            $email = esc_html($orderdata['email']);
            $address = esc_html($orderdata['address']);
            $userinfo = esc_html($orderdata['suborder_affiliate_user_info']);
            
            $distAddressParts = [];
            $shopAddressParts = [];
            $distNameParts = [];

           $distNameParts[] = get_user_meta($affiliate_user_id, '_yith_wcaf_first_name', true)?: '';
           $distNameParts[] = get_user_meta($affiliate_user_id, '_yith_wcaf_last_name', true)?: '';
           $distAddressParts[] = get_user_meta($affiliate_user_id, '_yith_wcaf_address', true)?: '';
            $cityStateZip = trim(
                rtrim( get_user_meta($affiliate_user_id, '_yith_wcaf_city', true) ?? '', ',' ) . ', ' . 
                ( get_user_meta($affiliate_user_id, '_yith_wcaf_state', true) ?? '' ) . ' ' . 
                ( get_user_meta($affiliate_user_id, '_yith_wcaf_zipcode', true) ?? '' )
            );
            $distAddressParts[] = $cityStateZip;
            

            $shop_address = get_option( 'woocommerce_store_address' );
            $shop_address_2 = get_option( 'woocommerce_store_address_2' );
            $shop_city = get_option( 'woocommerce_store_city' );
            $shop_postcode = get_option( 'woocommerce_store_postcode' );
            $shop_country = get_option( 'woocommerce_default_country' );

            $country_parts = explode( ':', $shop_country );
            $country = $country_parts[0] ?? '';
            $state = $country_parts[1] ?? '';

            if ( ! empty( $shop_address ) ) {
                $shopAddressParts[] = $shop_address;
            }
            if ( ! empty( $shop_address_2 ) ) {
                $shopAddressParts[] = $shop_address_2;
            }
            $cityStateZip = trim( "{$shop_city} {$state} {$shop_postcode}" );
            if ( ! empty( $cityStateZip ) ) {
                $shopAddressParts[] = $cityStateZip;
            }
            if ( ! empty( $country ) ) {
                $shopAddressParts[] = $country;
            }
            
            // Final full address
            if(!empty($distAddressParts)){
                $distAddress = implode(' ', $distAddressParts);

            }else{
                $distAddress = implode(' ', $shopAddressParts);
            }
            if(!empty($distAddressParts)){
                $distName = implode(' ', $distNameParts);
            }
            if(trim($distName) == ''){
               $distName = 'Honey from the Heart';
            }

            $affiliate_org_name = 'Honey from the Heart';
            $affiliate_org_email = '';

            $custom_price = 0;
            if (!empty($orderdata['suborderdata'])) {
                $affiliate_user_id = $orderdata['suborderdata'][0]['suborder_affiliate_user_id'];
                $yith_wcaf_email = get_user_meta($affiliate_user_id, '_yith_wcaf_email', true);
                $selling_minimum_price = (int) get_field('selling_minimum_price', 'option');
                $custom_price = (int) get_user_meta($affiliate_user_id, 'DJarPrice', true)?: 0;
                if($custom_price < $selling_minimum_price){
                    $custom_price = $selling_minimum_price;
                }
                
                $affiliate_org_name =  get_user_meta($affiliate_user_id, '_yith_wcaf_name_of_your_organization', true) ?:'Honey from the Heart' ;
                $affiliate_org_email = $yith_wcaf_email ?: $orderdata['suborderdata'][0]['suborder_affiliate_org_email'];
            }

        
            $suborder_affiliate_token = 'Honey from the Heart';
            $refersite = site_url();
            if (!empty($orderdata['suborderdata'])) {
                $suborder_affiliate_token = $orderdata['suborderdata'][0]['suborder_affiliate_token'];
                if( !in_array($suborder_affiliate_token, $ex_words)){
                    $refersite = site_url().'?ref='.$suborder_affiliate_token;
                }
            }
        
    
            $html .= '
            <h2>'.date('Y').' Honey Reorder Form</h2>
            <div class="section">
                <div><span class="label">Name:</span> <strong>' . $name . '</strong></div>
                '.(!empty($address) ? '<div><span class="label">Address:</span> <strong>' . $address . '</strong></div>' : '' ).'
                <div><span class="label">Email:</span> <strong>' . $email . '</strong></div>
            </div>
            <div class="section">
                <p><br>Dear ' . $name . ',</p>';
    
            // PDF content types
            if ($custom_order_pdf_type == "5p") {
                $pdftypepdfcontent = "
                    <p>Thank you for your previous support to $affiliate_org_name. It's time again to send the sweetest Rosh Hashanah greetings by supporting $affiliate_org_name with your honey purchase.</p>
                    <p>For your ordering convenience, the details of your last order are listed below. To order, simply update this form with any additions, deletions or corrections, fill out the payment section and mail it to $distName $distAddress.</p>
                    <p>Mail orders must be received by $paperDeadlineDate. Your order will be shipped to arrive in time for Rosh Hashanah.</p>";
            } elseif ($custom_order_pdf_type == "4p" ) {
                $pdftypepdfcontent = "
                    <p>Thank you for supporting $affiliate_org_name in the past by ordering honey. It's time again to send the sweetest Rosh Hashanah greetings and support $affiliate_org_name with your honey purchase.</p>

                    <p>Shipping is FREE for orders submitted online through $shipEndDate. After $shipEndDate, ".wc_price( $ort_shipping_cost)." per jar is automatically added for shipping.</p>

                    <p>Your order will be shipped to arrive in time for Rosh Hashanah. To order honey, go to <a href='".esc_url($refersite)."'>".esc_url($refersite)."</a>, click on the Order Honey link, follow the instructions and enter your Reorder #" . $sub_order_id . " when prompted.</p>";
                    
            } elseif ($custom_order_pdf_type == "2p") {
                $pdftypepdfcontent = "
                    <p>Thank you for your previous support to <strong>$affiliate_org_name</strong>. It's time again to send the sweetest Rosh Hashanah greetings by supporting <strong>$affiliate_org_name</strong> with your honey purchase.</p>

                    <p>Shipping is FREE for orders submitted online through <strong>$shipEndDate</strong>. After <strong>$shipEndDate</strong>, ".wc_price( $ort_shipping_cost)." per jar is automatically added for shipping.</p>

                    <p>Your order will be shipped to arrive in time for Rosh Hashanah. To order honey, go to <a href='".esc_url($refersite)."'>".esc_url($refersite)."</a>, click on the <a href='".esc_html(site_url('order-process'))."'>Order Now</a>.</p>
                    <p>Follow the instructions, selecting $affiliate_org_name and choose your reorder from a previous year. Select #" . $sub_order_id . " when prompted.</p>

                    <p>If you can't order online, please update this form for any additions, deletions, or corrections, complete the payment section, and mail it to <a href='mailto:".$affiliate_org_email."'>$affiliate_org_email</a> . Forms must arrive by for ".date("Y")." the date is $paperDeadlineDate, or a shipping charge will be applied.</p>";
      
            } elseif ($custom_order_pdf_type == "2e" OR $custom_order_pdf_type == "4e") {

                $pdftypepdfcontent = "
                    <p>Thank you for your previous support to <strong>$affiliate_org_name</strong>. It's time again to send the sweetest Rosh Hashanah greetings by supporting <strong>$affiliate_org_name</strong> with your honey purchase.</p>

                    <p>Shipping is FREE for orders submitted online through <strong>$shipEndDate</strong>. After <strong>$shipEndDate</strong>, ".wc_price( $ort_shipping_cost)." per jar is automatically added for shipping.</p>
                    <p><strong><u>How to Order Online:</u></strong></p>
                    <ul style='list-style-type: disc;margin-left: 24px;font-size: 14px;'>
                    <li>Go to <a href='".site_url('order-process/')."'>here</a></li>
                    </ul>
                    </p>
                    <p><strong>Log in to your existing account or create a new one <strong><br>
                    <ul style='list-style-type: disc;margin-left: 24px;font-size: 14px;'>
                       <li> Click “Order Now” </li>
                       <li> Choose your organization using either: ".($suborder_affiliate_token == 'Orthoney' ? '<strong>Honey From The Heart</strong>' : 'Your 3-digit code: <strong> '.$suborder_affiliate_token.'</strong> or <strong>'.$affiliate_org_name.'</strong>') ." .</li>
                       <li> To reorder from a previous year, select order <strong>#" . $sub_order_id . " </strong>when prompted.</li>
                       <li> Complete checkout!.</li>
                        </ul> 
                    </p>

                    <p>To order by mail update this form for any additions, deletions, or corrections, complete the payment section, and mail it to <a href='mailto:support@orthoney.com'><strong>support@orthoney.com</strong></a> or email it to  <a href='mailto:".$affiliate_org_email."'><strong>$affiliate_org_email</strong></a>. Forms must arrive by <strong>$paperDeadlineDate </strong>, or a shipping charge will be applied.</p>";

            } else {
                $pdftypepdfcontent = "
                    <p>Thank you for your previous support to $affiliate_org_name.</p>
                    <p>To order, simply update this form with any additions, deletions or corrections, fill out the payment section and mail it to $distName $distAddress.</p>
                    <p>Mail orders must be received by $shipEndDate. Your order will be shipped to arrive in time for Rosh Hashanah.</p>";
            }
    
            $html .= $pdftypepdfcontent;
    
            // Order summary and payment
            $html .= '
            </div>
            <div class="section order-summary">
                <p><strong>Order Summary:</strong></p>
                <p># jars ordered ______ x '.wc_price($custom_price).' per jar = Order total $ ______</p>
            </div>
            <div class="section payment-section">
                <p><strong>Payment:</strong></p>
                <p>[ ] Credit card (circle one): Amex Discover MC Visa</p>
                <p>Name on card _____________________________________</p>
                <p>Credit card # _____________________________________ Exp Date ___/___</p>
                <p>Billing zip code __________ Contact phone number ______________________</p>
            </div>';
            
           
            // Suborders
            $html .= '<div class="section addresses"><p><strong>Jar Orders:</strong></p>';
            if (!empty($orderdata['suborderdata'])) {
    
                foreach ($orderdata['suborderdata'] as $suborderdata) {

                    $title = $suborderdata['suborder_full_name'];
                    //if (!$title) continue;
                    $quantity = $suborderdata['suborder_data_quantity'];
                    $address_parts = [
                        $suborderdata['suborder_data_address_1'],
                        $suborderdata['suborder_data_address_2'],
                        $suborderdata['suborder_data_company_name'],
                        $suborderdata['suborder_data_city'],
                        $suborderdata['suborder_data_state'],
                        $suborderdata['suborder_data_zipcode'],
                        $suborderdata['suborder_data_country']
                    ];
                    $full_address = implode(', ', array_filter($address_parts));
    
                    $html .= '
                    <div class="address-block">
                        <p><strong>' . $title . '</strong> &times; ' . $quantity . ' jar(s)<br>' . $full_address . '</p>
                    </div>';
                }
    
            }else{
                $title = trim($order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name());
                // Full shipping address
                $address_parts = [
                    $order->get_shipping_address_1(),
                    $order->get_shipping_address_2(),
                    $order->get_shipping_city(),
                    $order->get_shipping_state(),
                    $order->get_shipping_postcode()
                ];
                $full_address = implode(' ', array_filter($address_parts));

                // Total quantity
                $quantity = 0;
                foreach ($order->get_items() as $item) {
                    $quantity += (int) $item->get_quantity();
                }

                // Output block
                $html .= sprintf(
                    '<div class="address-block"><p><strong>%s</strong> &times; %d jar(s)<br>%s</p></div>',
                    esc_html($title),
                    $quantity,
                    esc_html($full_address)
                );

            }
            $html .= '</div>';

            if( !in_array($suborder_affiliate_token, $ex_words)){
                $html .= '<p>Code : '.$suborder_affiliate_token.'</p>';
            }
            $html .= '<div style="page-break-after: always;"></div>';


            if ($custom_order_pdf_type == "2e" || $custom_order_pdf_type == "4e") {
                // Generate PDF
                $dompdf = new \Dompdf\Dompdf();

                $dompdf->loadHtml($starthtml . $html . $endhtml);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
            
                $timestamp = date('Y-m-d_h.ia');
                $upload_dir = wp_upload_dir();
                $pdf_filename = $custom_order_pdf_type . '_' . $timestamp . '.pdf';
                $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
            
                file_put_contents($pdf_path, $dompdf->output());
            
                $pdf_url = $upload_dir['url'] . '/' . $pdf_filename;

                $to = $email;
               
                $cc = implode(',', array_unique(array_filter([
                    $affiliate_org_email,
                    $customer_email
                ])));

                $subject = '#'. $custom_order_id.' Reordering Form – Honey From The Heart';
                
                // $message = '<p>Dear ' . $name . ',</p>';
                $html .= "<p>Thank you for continuing to support Honey From the Heart your participation helps us make a difference, one jar at a time.</p>
                    <p>The Honey From The Heart Team  <br><i> A sweet way to raise money!</i></p>";
                $mailer = WC()->mailer();
                $subject = '#' . $custom_order_id . ' Reordering Form – Honey From The Heart';
                $wrapped_message = $mailer->wrap_message($subject, $html);
                $headers = [
                    'Content-Type: text/html; charset=UTF-8',
                    'Cc:' . $cc
                ];
                $attachments = [$pdf_path];
                $mail_sent = $mailer->send($to, $subject, $wrapped_message, $headers, $attachments);
            }
            
        }
    
       
    
        // Email or return PDF link
        if ($custom_order_pdf_type == "2e" || $custom_order_pdf_type == "4e") {
            
            wp_send_json_success([
                'message' => $mail_sent ? 'mail has been sent.' : 'mail not sent.',
                'status' => 'success',
                'request' => 'mail',
                'url' => $pdf_url,
            ]);
        } else {
             // Generate PDF
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($starthtml . $html . $endhtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
        
            $timestamp = date('Y-m-d_h.ia');
            $upload_dir = wp_upload_dir();
            $pdf_filename = $custom_order_pdf_type . '_' . $timestamp . '.pdf';
            $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;
        
            file_put_contents($pdf_path, $dompdf->output());
        
            $pdf_url = $upload_dir['url'] . '/' . $pdf_filename;
            wp_send_json_success([
                'url' => $pdf_url,
                'filename' => $pdf_filename,
                'status' => 'success',
                'request' => 'download',
            ]);
        }
    }
    
    // AJAX: Export orders as CSV
    public function orthoney_customer_order_export_by_id_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');

        $order_id = intval($_POST['order_id'] ?? 0);
        $user_id = get_current_user_id();
       
        $filtered_orders = OAM_Helper::get_filtered_orders_by_id($user_id, $order_id);

        $csvData = [
            ['Jar No', 'Order No', 'Date', 'Billing Name', 'Shipping Name', 'Affiliate Code', 'Total Jar', 'Total Recipient', 'Type', 'Status', 'Price']
        ];

        foreach ($filtered_orders as $order) {
            $csvData[] = [
                $order['jar_no'],
                $order['order_no'],
                $order['date'],
                $order['billing_name'],
                $order['shipping_name'],
                $order['affiliate_code'],
                $order['total_jar'],
                $order['total_recipient'],
                $order['type'],
                $order['status'],
                $order['price'],
            ];
        }

        $filename = 'customer_orders_' . time() . '.csv';
        $custom_dir = WP_CONTENT_DIR . '/download_recipient_list';
        $custom_url = content_url('/download_recipient_list');

        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir);
        }

        $file_path = "$custom_dir/$filename";
        $file_url = add_query_arg(['action' => 'download_generated_csv', 'filename' => $filename], admin_url('admin-ajax.php'));

        $output = fopen($file_path, 'w');
        if (!$output) {
            wp_send_json_error(['message' => 'Failed to create CSV file.']);
            return;
        }

        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }

        fclose($output);

        if (file_exists($file_path)) {
            wp_send_json_success([
                'url' => $file_url,
                'filename' => $filename,
            ]);
        } else {
            wp_send_json_error(['message' => 'File not found after creation.']);
        }

        wp_die();
    }

    public function orthoney_customer_order_export_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');

        $user_id = get_current_user_id();
        $table_order_type = sanitize_text_field($_POST['table_order_type'] ?? 'main_order');
        $custom_order_type = sanitize_text_field($_POST['custom_order_type'] ?? 'all');
        $custom_order_status = sanitize_text_field($_POST['custom_order_status'] ?? 'all');
        $search = sanitize_text_field($_POST['search']['value'] ?? '');

        $filtered_orders = OAM_Helper::get_filtered_orders($user_id, $table_order_type, $custom_order_type, $custom_order_status, $search, true);

        $csvData = [
            ['Jar No', 'Order No', 'Date', 'Billing Name', 'Shipping Name', 'Affiliate Code', 'Total Jar', 'Total Recipient', 'Type', 'Status', 'Price']
        ];

        foreach ($filtered_orders as $order) {
            $csvData[] = [
                $order['jar_no'],
                $order['order_no'],
                $order['date'],
                $order['billing_name'],
                $order['shipping_name'],
                $order['affiliate_code'],
                $order['total_jar'],
                $order['total_recipient'],
                $order['type'],
                $order['status'],
                $order['price'],
            ];
        }

        $filename = 'customer_orders_' . time() . '.csv';
        $custom_dir = WP_CONTENT_DIR . '/download_recipient_list';
        $custom_url = content_url('/download_recipient_list');

        if (!file_exists($custom_dir)) {
            wp_mkdir_p($custom_dir);
        }

        $file_path = "$custom_dir/$filename";
        $file_url = add_query_arg(['action' => 'download_generated_csv', 'filename' => $filename], admin_url('admin-ajax.php'));

        $output = fopen($file_path, 'w');
        if (!$output) {
            wp_send_json_error(['message' => 'Failed to create CSV file.']);
            return;
        }

        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }

        fclose($output);

        if (file_exists($file_path)) {
            wp_send_json_success([
                'url' => $file_url,
                'filename' => $filename,
            ]);
        } else {
            wp_send_json_error(['message' => 'File not found after creation.']);
        }

        wp_die();
    }

    // // AJAX: remove Export orders as CSV
    public function download_generated_csv_handler() {
        if (!current_user_can('read')) {
            wp_die('Access denied');
        }

        $filename = sanitize_file_name($_GET['filename'] ?? '');
        if (empty($filename)) {
            wp_die('Invalid filename');
        }

        $custom_dir = WP_CONTENT_DIR . '/download_recipient_list';
        $file_path = $custom_dir . '/' . $filename;

        if (!file_exists($file_path)) {
            wp_die('File not found');
        }

        // Set download headers
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        flush();
        readfile($file_path);

        // ✅ Remove file after download
        register_shutdown_function(function () use ($file_path) {
            @unlink($file_path);
        });

        exit;
    }
    
    public function customer_sub_order_details_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
        $orderid = intval($_POST['orderid']);
    }
    
    public function orthoney_group_recipient_list_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
    
        global $wpdb;
    
        $draw = intval($_POST['draw']);
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        if( $start  == 0){
            $length = intval($_POST['length']) -1;

        }else{
            $start = intval($_POST['start']) - 1;
        }
        $search_value = sanitize_text_field($_POST['search']['value']);
    
        $user_id = get_current_user_id();
        $group_table = OAM_Helper::$group_table; 
        $group_recipient_table = OAM_Helper::$group_recipient_table; 
    
        // Fetch groups
        $groups = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $group_table WHERE user_id = %d AND visibility = %d", $user_id, 1)
        );
    
        // Filter by search
        $filtered = [];
        if (!empty($search_value)) {
            foreach ($groups as $group) {
                if (stripos($group->name, $search_value) !== false) {
                    $filtered[] = $group;
                }
            }
        } else {
            $filtered = $groups;
        }
    
        $total_items = count($filtered);
    
        // Sort
        $order_column_index = $_POST['order'][0]['column'];
        $order_dir = $_POST['order'][0]['dir'];
        $order_column_key = $_POST['columns'][$order_column_index]['data'];
    
        usort($filtered, function ($a, $b) use ($order_column_key, $order_dir) {
            $valA = $a->{$order_column_key} ?? '';
            $valB = $b->{$order_column_key} ?? '';
            return $order_dir === 'asc' ? strnatcasecmp($valA, $valB) : strnatcasecmp($valB, $valA);
        });
    
        $paged_data = array_slice($filtered, $start, $length);
    
        $data = [];
        $final_data = [];
        $sr = $start + 1;
    
        if ($total_items != 0) {

            $recipient_list = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT ID) FROM $group_recipient_table WHERE visibility = %d AND user_id = %d",
                    1, $user_id
                )
            );
            
            
            if($start == 0){
                $data = [[
                    'id' => '—',
                    'name' => esc_html('Unique Recipient List'),
                    'recipient_count' => $recipient_list,
                    'date' => esc_html('-'),
                    'action' => "<a href='".esc_url(OAM_Helper::$customer_dashboard_link.'/groups/details/unique_recipients')."' class='far fa-eye'>"
                ]];}

            
            foreach ($paged_data as $group) {
                // ✅ Corrected COUNT query
                $recipient_count = (int) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $group_recipient_table WHERE group_id = %d AND visibility = %d",
                        $group->id, 1
                    )
                );
    
                $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($group->created));
                $view_url = esc_url(CUSTOMER_DASHBOARD_LINK . "groups/details/" . $group->id);
    
                $data[] = [
                    'id' => $sr++,
                    'name' => esc_html($group->name),
                    'recipient_count' => $recipient_count,
                    'date' => esc_html($created_date),
                    'action' => "<a href='$view_url' class='far fa-eye'></a><button data-groupid='".esc_html($group->id)."' data-groupname='".esc_html($group->name)."' data-tippy='Remove Recipients List' class='deleteGroupButton far fa-trash'></button>"
                ];
            }

            
        }
    
        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => count($groups) > 0 ? count($groups) + 1 : count($groups) ,
            'recordsFiltered' => $total_items > 0 ? $total_items + 1 : $total_items,
            'data' => $data
        ]);
    }

   public function orthoney_incomplete_order_process_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;

        $step_labels = [
            0 => 'Step 1: Select an Organization',
            1 => 'Step 2: Order Method',
            2 => 'Step 3: Upload Recipients',
            3 => 'Step 4: Add/Review Recipients',
            4 => 'Step 5: Verify Addresses',
            5 => 'Step 6: Pending Checkout',
        ];

        $user_id = get_current_user_id();
        $failed = isset($_POST['failed']) ? intval($_POST['failed']) : 0;

        $draw = intval($_POST['draw']);
        $start = intval($_POST['start']);
        $length = intval($_POST['length']);
        $search_value = sanitize_text_field($_POST['search']['value']);

        $order_column_index = $_POST['order'][0]['column'];
        $order_column = sanitize_sql_orderby($_POST['columns'][$order_column_index]['data']);
        $order_dir = in_array(strtoupper($_POST['order'][0]['dir']), ['ASC', 'DESC']) ? $_POST['order'][0]['dir'] : 'ASC';

        $order_process_table = OAM_Helper::$order_process_table;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;

        $where = "WHERE user_id = %d";
        $params = [$user_id];

        if ($failed == 1) {
            $failed_rows = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT DISTINCT pid FROM $order_process_recipient_table WHERE verified = %d AND user_id = %d",
                    0, $user_id
                )
            );

            if (!empty($failed_rows)) {
                $pid_placeholders = implode(',', array_fill(0, count($failed_rows), '%d'));
                $where .= " AND step = 5 AND order_id != 0 AND id IN ($pid_placeholders)";
                $params = array_merge($params, $failed_rows);
            } else {
                // ✅ Always send an empty response if no failed rows
                wp_send_json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => []
                ]);
                wp_die();
            }
        } else {
            $where .= " AND order_id = 0";
        }

        // Add search filter
        if (!empty($search_value)) {
            $search_term = '%' . $wpdb->esc_like($search_value) . '%';
            $where .= " AND (name LIKE %s";
            $params[] = $search_term;

            $matching_step_keys = array_keys(array_filter($step_labels, function ($label) use ($search_value) {
                return stripos($label, $search_value) !== false;
            }));

            if (!empty($matching_step_keys)) {
                $placeholders = implode(',', array_fill(0, count($matching_step_keys), '%d'));
                $where .= " OR step IN ($placeholders)";
                foreach ($matching_step_keys as $step_key) {
                    $params[] = $step_key;
                }
            }

            $where .= ")";
        }

        $where .= " AND visibility = %d";
        $params[] = 1;

        $where_sql = $wpdb->prepare($where, ...$params);

        // Get total filtered items
        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM $order_process_table $where_sql");

        // Get paginated data
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $order_process_table $where_sql ORDER BY created DESC LIMIT %d, %d",
            $start, $length
        ));

        $data = [];

        foreach ($items as $item) {
            $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($item->created));

            $resume_url = ($failed == 1)
                ? esc_url(CUSTOMER_DASHBOARD_LINK . "failed-recipients/details/" . $item->id."?pid=". $item->id)
                : esc_url(ORDER_PROCESS_LINK . "?pid=" . $item->id);

            $download_button = '';
            $delete_action = '';
            if (!empty($item->csv_name)) {
                $download_url = esc_url(OAM_Helper::$process_recipients_csv_url . $item->csv_name);
                $download_button = '<a href="' . esc_url($download_url) . '" class="button-icon-underline" download data-tippy="Download Recipients File"> <img src="' . esc_url(OH_PLUGIN_DIR_URL . 'assets/image/download.png') . '" alt="Download"> Download Recipients File</a>';
            }

            $display_name = ($item->process_by == 0) ? 'Self' : esc_html(get_userdata($item->process_by)->display_name);

            $action = "<a href='$resume_url' class='button-icon-underline'>";
            if ($failed == 1) {
                $action .= '<img src="' . OH_PLUGIN_DIR_URL . 'assets/image/resume.png" alt="">View Recipients';
            } else {
                $delete_action = "<button data-id='" . esc_html($item->id) . "' data-name='" . esc_html($item->name) . "' data-tippy='Remove Incompleted List' class='deleteIncompletedOrderButton button-icon-underline'><img src='" . esc_url(OH_PLUGIN_DIR_URL . 'assets/image/delete-icon.png') . "' alt='Delete'>Remove</button>";
                $action .= '<img src="' . OH_PLUGIN_DIR_URL . 'assets/image/resume.png" alt="">Resume Order';
            }
            $action .= "</a>";

            $row = [
                'id' => esc_html($item->id),
                'name' => esc_html($item->name),
                'ordered_by' => esc_html($display_name),
                'date' => esc_html($created_date),
                'action' => $action . ($failed != 1 ? $download_button : '') . $delete_action,
            ];

            if ($failed == 0) {
                $step = isset($step_labels[$item->step]) ? $step_labels[$item->step] : '';
                $row['current_step'] = esc_html($step);
            }

            $data[] = $row;
        }

        // ✅ Always return proper structure
        wp_send_json([
            'draw' => $draw,
            'recordsTotal' => $total_items,
            'recordsFiltered' => $total_items,
            'data' => $data
        ]);

        wp_die();
    }
    

    //
    public function orthoney_groups_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');
        global $wpdb;
        $user_id = get_current_user_id();
        $current_page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        
        $items_per_page = 10;
        $offset = ($current_page - 1) * $items_per_page;
    
        $group_table = OAM_Helper::$group_table;
        $group_recipient_table = OAM_Helper::$group_recipient_table;
        $order_process_table = OAM_Helper::$order_process_table;;

        // Get total items and pages
        $total_items = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$group_table} WHERE user_id = %d AND visibility = %d",
            $user_id, 1
        ));
       
        
        // Fetch process data
        $processQuery = $wpdb->prepare(
            "SELECT * FROM {$group_table} WHERE user_id = %d  AND visibility = %d LIMIT %d OFFSET %d",
            $user_id ,1,$items_per_page, $offset
        );

       
        

        $total_pages = (int) ceil($total_items / $items_per_page);
        
        $results = $wpdb->get_results($processQuery);
        $table_content = '';
    
        if (!empty($results)) {
            $total_unique_recipients = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$group_recipient_table} WHERE user_id = %d AND visibility = %d",
                $user_id, 1
            ));

            $table_content .= "<tr>
            <td><div class='thead-data'>Sr No</div>-</td>
            <td><div class='thead-data'>Recipient List Name</div>Unique Recipients List</td>
            <td><div class='thead-data'>Number of Recipients</div>".$total_unique_recipients."</td>
            <td><div class='thead-data'>Date</div>-</td>
            <td><div class='thead-data'>Action</div><a href='".esc_url(OAM_Helper::$customer_dashboard_link.'/groups/details/unique_recipients')."' class='far fa-eye'></td>
            </tr>";
            foreach ($results as $data) {

                $total_recipients = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$group_recipient_table} WHERE user_id = %d AND group_id = %d AND visibility = %d",
                    $user_id, $data->id, 1
                ));

                $csv_name = $wpdb->get_var($wpdb->prepare(
                    "SELECT csv_name FROM {$order_process_table} WHERE user_id = %d AND id = %d",
                    $user_id, $data->pid
                ));

                $created_date = date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($data->timestamp));

                $resume_url = esc_url(OAM_Helper::$customer_dashboard_link.'/groups/details/'.$data->id);
               
                $download_button = '';
    
                if (!empty($data->csv_name)) {
                    $download_url = esc_url(OAM_Helper::$process_recipients_csv_url . $data->csv_name);
                    $download_button = "<a href='".esc_url($download_url)."' class='w-btn us-btn-style_1 outline-btn round-btn' download data-tippy='Download Recipients File'><i class='far fa-download'></i></a>";
                }
    
                $table_content .= "<tr>
                <td><div class='thead-data'>ID</div>" . esc_html($data->id) . "</td>
                <td><div class='thead-data'>Recipient List Name</div>" . esc_html($data->name) . "</td>
                <td><div class='thead-data'>Number of Recipients</div>".$total_recipients."</td>
                <td><div class='thead-data'>Date</div>" . esc_html($created_date) . "</td>
                <td><div class='thead-data'>Action</div> <a href='".esc_url($resume_url)."' class='far fa-eye'></a><button data-groupid='".esc_html($data->id)."' data-groupname='".esc_html($data->name)."' data-tippy='Remove Recipients List' class='deleteGroupButton far fa-trash'></button></td>
                </tr>";
            }
        } else {
            $table_content = '<tr><td colspan="4" class="no-available-msg">No data available</td></tr>';
        }
    
        $pagination = '';
        if ($total_pages > 1) {
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($current_page == $i) ? 'active' : '';
                $pagination .= "<a href='javascript:;' class='".$active_class."' data-page='".$i."'>".$i."</a> ";
            }
        }
        wp_send_json_success([
            'table_content' => $table_content,
            'pagination' => $pagination,
            'current_page' => $current_page,
            'total_pages' => $total_pages,
        ]);
        wp_die();
    }

}
new OAM_Ajax();