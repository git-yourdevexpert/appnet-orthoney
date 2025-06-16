<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_Ajax{


    /**
	 * Define class Constructor
	 **/
	public function __construct() {

        // Affiliate Profile function
        add_action( 'wp_ajax_update_affiliate_profile', array( $this, 'update_affiliate_profile_handler' ) );
        add_action( 'wp_ajax_update_price_affiliate_profile', array( $this, 'update_price_affiliate_profile_handler' ) );

        //Manage Affiliate Team Member 
        add_action( 'wp_ajax_manage_affiliate_team_member_users', array( $this, 'manage_affiliate_team_member_users_handler' ) );
        
        //get Affiliate user data in form
        add_action( 'wp_ajax_get_affiliate_team_member_by_base_id', array( $this, 'get_affiliate_team_member_by_base_id_handler' ) );

        // Delete Affiliate user 
        add_action( 'wp_ajax_deleted_affiliate_team_member', array( $this, 'deleted_affiliate_team_member_handler' ) );

        // Change Affiliate Admin user
        add_action('wp_ajax_change_user_role_logout', array( $this, 'change_user_role_logout_handler' ));

        add_action('wp_ajax_search_customer_by_email', array( $this, 'search_customer_by_email' ));
        add_action('wp_ajax_add_affiliate_request', array( $this, 'add_affiliate_request_handler' ));
        add_action('wp_ajax_nopriv_add_affiliate_link', array($this, 'add_affiliate_link_handler'));
        add_action('wp_ajax_add_affiliate_link', array( $this, 'add_affiliate_link_handler' ));

        add_action('wp_ajax_orthoney_org_account_statement_ajax', array( $this, 'orthoney_org_account_statement_ajax_handler' ));


    }

    public function orthoney_org_account_statement_ajax_handler() {
        check_ajax_referer('oam_nonce', 'security');

        // Load Dompdf if not already loaded
        if (!class_exists('\Dompdf\Dompdf')) {
            require_once plugin_dir_path(__FILE__) . 'libs/dompdf/vendor/autoload.php';
        }

        $org_id = isset($_POST['orgid']) ? intval($_POST['orgid']) : 0;
        if (!$org_id) {
            wp_send_json_error(['message' => 'Invalid Organization ID.']);
        }

        // Dummy HTML content for now – populate with real data
        $html = '
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
        <body>
            <h2>Account Statement for Organization ID: ' . esc_html($org_id) . '</h2>
            <div class="section">
                <div class="order-summary">
                    <p>This is where the order summary or account statement content will go.</p>
                </div>
            </div>
            <div class="section" style="color:red">
            <hr>
                <p><strong>Important Check Terms:</strong></p>
                <ul>
                <li>2-4 weeks after submitting your Remittance Form, you’ll receive an email notification that the check was mailed.</li>
                <li>A $25 processing fee is charged to replace a lost or expired check.</li>
                <li>If the check does not arrive within 2 weeks of the mailing notification, you must immediately notify support@orthoney.com to avoid a $25 replacement processing fee.</li>
                <li>Checks expire 90 days after date of issue.</li>
                <li>Under no circumstances will ORT replace a check that has been deposited.</li>
                <li>It is your responsibility to provide a complete and accurate mailing address on the Remittance Form.</li>
                </ul>
            </div>
            <div class="section" style="color:blue">
            <hr>
                <p><strong>To Request your Profit Check</strong></p>
                <ol>
                <li>Click the Remittance Form button on the Account Statement page of your Distributor Account. Your username is Juliegur@yahoo.com.</li>
                <li>Complete the Remittance Form. (Your organization code is ABR.)</li>
                <li>Click the Captcha (I’m not a robot).</li>
                <li>Click Submit.</li>
                </ol>
                <p>An Account Statement notification was emailed to all contacts in your Distributor Account. Please do not submit duplicate forms.</p>
            </div>
        </body>
        </html>';

        // Generate PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Filename and file path
        $timestamp = date('Y-m-d_H-i-s');
        $upload_dir = wp_upload_dir();
        $pdf_filename = 'organization-statement-' . $org_id . '-' . $timestamp . '.pdf';
        $pdf_path = $upload_dir['path'] . '/' . $pdf_filename;

        // Save PDF
        file_put_contents($pdf_path, $dompdf->output());

        // URL to return for download
        $pdf_url = $upload_dir['url'] . '/' . $pdf_filename;

        // Respond with success
        wp_send_json_success([
            'url' => $pdf_url,
            'filename' => $pdf_filename,
            'status' => 'success',
            'request' => 'download',
        ]);
    }


    public function add_affiliate_link_handler() {
        check_ajax_referer('oam_nonce', 'security');
    
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
    
        if (empty($token)) {
            wp_send_json(['success' => false, 'message' => 'You have failed to link with the organization.']);
        }
    
        global $wpdb;
        $table_name = OAM_Helper::$oh_affiliate_customer_linker;
    
        $existing_request = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE token = %s", $token));
    
        if ($existing_request) {
            if ($existing_request->status == 0) {
                $wpdb->update(
                    $table_name,
                    ['status' => 1],
                    ['token' => $token],
                    ['%d'],
                    ['%s']
                );
                wp_send_json(['success' => true, 'message' => 'You have successfully linked with the organization!']);
            } elseif ($existing_request->status == 1) {
                wp_send_json(['success' => true, 'message' => 'You are already linked with the organization.']);
            }else{
                wp_send_json(['success' => true, 'message' => 'You have blocked the organization.']);
            }
        } else {
            wp_send_json(['success' => false, 'message' => 'You have failed to link with the organization.']);
        }
    }

    public function add_affiliate_request_handler() {
        check_ajax_referer('oam_nonce', 'security');
    
        $customer_id = intval($_POST['customer_id']);
        $affiliate_id = get_current_user_id();
    
        if (!$customer_id || !$affiliate_id) {
            wp_send_json(['success' => false, 'message' => 'Invalid data.']);
        }
    
        global $wpdb;
        $table_name = OAM_Helper::$oh_affiliate_customer_linker;
    
        $random_string = OAM_AFFILIATE_Helper::getRandomChars('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 10);
        $token = md5($random_string . time());
    
        $existing_request = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE affiliate_id = %d AND customer_id = %d", $affiliate_id, $customer_id)
        );
    
        if ($existing_request) {
            if ($existing_request->status == 0) {
                $token = $existing_request->token;
                $this->send_affiliate_email($customer_id, $affiliate_id, 'Resend : Organization Request Notification', $token);
                wp_send_json(['success' => true, 'message' => 'Resend request successfully!']);
            }
            wp_send_json(['success' => false, 'message' => 'Customer is already linked with you.']);
        }
    
        if ($wpdb->insert($table_name, ['customer_id' => $customer_id, 'affiliate_id' => $affiliate_id, 'status' => 0, 'token' => $token], ['%d', '%d', '%d', '%s'])) {
            $this->send_affiliate_email($customer_id, $affiliate_id, 'Organization Request Notification', $token);
            wp_send_json(['success' => true, 'message' => 'Request sent successfully.', 'token' => $token]);
        }
    
        wp_send_json(['success' => false, 'message' => 'Failed to send request.']);
    }
    
     private function send_affiliate_email($customer_id, $affiliate_id, $subject, $token) {
        $customer = get_userdata($customer_id);
        $affiliate = get_userdata($affiliate_id);

        if (!$customer || !$affiliate) {
            return; // Exit if either user is not found
        }

        $first_name     = get_user_meta($customer_id, 'first_name', true);
        $last_name      = get_user_meta($customer_id, 'last_name', true);
        $customer_email = $customer->user_email;

        $full_name = (!empty($first_name) && !empty($last_name)) 
            ? $first_name
            : $customer->display_name;

        $organization   = get_user_meta($affiliate_id, '_yith_wcaf_name_of_your_organization', true);
        $affiliate_name = $affiliate->display_name;

        $link = esc_url(home_url("/?action=organization-link&token={$token}"));
        $organizations_link = esc_url(CUSTOMER_DASHBOARD_LINK.'organizations/');

        $message = "
            <p>Dear {$full_name},</p>
            <p>A honey sale administrator from <strong>{$organization}</strong> has requested access to your Honey From The Heart account.</p>
            <p>Please click the link below to approve their request: <a href=\"{$link}\" target=\"_blank\">Click here</a></p>
            <p>In the future, you can block organizations from accessing your account. Please <a href=\"{$organizations_link}\" target=\"_blank\">Click here</a>: </p>
            <p>Thank you,<br>Honey From The Heart</p>
        ";

        wp_mail($customer_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
    }

    public function search_customer_by_email() {
        check_ajax_referer('oam_nonce', 'security');
        $user_id = get_current_user_id();
        $email = sanitize_email($_POST['email']);
        if (empty($email)) {
            wp_send_json(['success' => false, 'message' => 'Invalid email.']);
        }
    
        // Perform exact email match using get_user_by instead of WP_User_Query
        $user = get_user_by('email', $email);
    
        if (!$user) {
            wp_send_json(['success' => false, 'customers' => []]);
        }
    
        // Check if the user has only the 'customer' role
        $user_roles = (array) $user->roles;
        if (count($user_roles) === 1 && in_array('customer', $user_roles)) {
            global $wpdb;
            $table_name = OAM_Helper::$oh_affiliate_customer_linker;
            $processQuery = $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE affiliate_id = %d AND customer_id = %d",
                $user_id, $user->id
            );
    
            $results = $wpdb->get_row($processQuery);
            $message = '';
            $exist_status = 2;

            
            if($results){
                $exist_status = $results->status;
                if($results->status == 0){
                    $message = 'You have already send request. Can you resend request?';
                }
                if($results->status == 1){
                    $message = 'Customer is already link with you';
                }
                if($results->status == -1){
                    $message = 'Customer has been reject to you.';
                }

            }
            $customers = [
                [
                    'id'    => $user->id,
                    'name'  => $user->display_name,
                    'email' => $user->user_email,
                    'exist_status' => $exist_status,
                    'message' => $message,
                ]
            ];
            
            wp_send_json(['success' => true, 'customers' => $customers]);
        }
    
        wp_send_json(['success' => false, 'customers' => []]);
    }
    
    // Change Affilate Admin user
    public function change_user_role_logout_handler() {
        check_ajax_referer('oam_nonce', 'security'); // Security check

        $current_user_id = get_current_user_id();
        $selected_user_id = isset($_POST['selected_user_id']) ? intval($_POST['selected_user_id']) : 0;

        if ($selected_user_id <= 0 || $current_user_id <= 0) {
            wp_send_json_error(['message' => 'Invalid user ID.']);
        }

        $selected_user = new WP_User($selected_user_id);
        $current_user = new WP_User($current_user_id);

        if ($selected_user && $current_user) {

            // Backup existing roles
            $selected_user_roles = $selected_user->roles;
            $current_user_roles = $current_user->roles;

            // Remove all existing roles from both users
            foreach ($selected_user_roles as $role) {
                $selected_user->remove_role($role);
            }
            foreach ($current_user_roles as $role) {
                $current_user->remove_role($role);
            }

            // Assign the swapped roles
            foreach ($current_user_roles as $role) {
                $selected_user->add_role($role);
            }
            foreach ($selected_user_roles as $role) {
                $current_user->add_role($role);
            }

            $afficated_id = $current_user_id; // Replace with actual value
            $args = array(
                'meta_key'   => 'associated_affiliate_id',
                'meta_value' => $afficated_id,
                'number'     => -1, // Retrieve all matching users
            );
            $users = get_users($args);
            if (!empty($users)) {
                foreach ($users as $user) {
                    //echo 'User ID: ' . $user->ID . ' - Username: ' . $user->user_login . '<br>';
                    update_user_meta($user->ID, 'associated_affiliate_id', $selected_user_id);
                }
            }

            global $wpdb;
            $yith_affiliate_table = $wpdb->prefix . 'yith_wcaf_affiliates';

            if ($wpdb->get_var("SHOW TABLES LIKE '{$yith_affiliate_table}'")) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$yith_affiliate_table} SET user_id = %d WHERE user_id = %d",
                    $selected_user_id, $current_user_id
                ));
            }

            // Email Notification
            $to = $selected_user->user_email;
            $subject = 'Your Role Has Been Changed';
            $message = "Hello " . $selected_user->display_name . ",\n\nYour user role has been updated. Please log in to check your new permissions.\n\nThank you.";
            $headers = ['Content-Type: text/plain; charset=UTF-8'];

            wp_mail($to, $subject, $message, $headers);

            wp_logout();

            wp_send_json_success(['message' => 'Role changed successfully, logging out...']);
        } else {
            wp_send_json_error(['message' => 'User not found.']);
        }
    }

    public function update_price_affiliate_profile_handler() {
        // Verify nonce for security
        check_ajax_referer('oam_nonce', 'security');


        $today = current_time('m/d/Y H:i:s');
        $season_start_date = get_field('season_start_date', 'option');
        $season_end_date = get_field('season_end_date', 'option');

        $current_timestamp      = strtotime($today);
        $season_start_timestamp = strtotime($season_start_date);
        $season_end_timestamp   = strtotime($season_end_date);

        $is_within_range = ( $current_timestamp >= $season_start_timestamp && $current_timestamp <= $season_end_timestamp );

        if ($is_within_range ) {
            wp_send_json(['success' => false, 'message' => 'Updating the selling price during the season is not allowed.']);
            wp_die();
        }
        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'message' => 'You must be logged in to update your profile.']);
            wp_die();
        }

        $user_id = get_current_user_id();
        $affiliate_id = $user_id;
        $associated_id = get_user_meta($user_id, 'associated_affiliate_id', true);

        $product_price = sanitize_text_field($_POST['product_price']);

        // Update current user's price
        update_user_meta($associated_id, 'DJarPrice', $product_price);

        // Get team members associated with this user
        $user_ids = get_users([
            'role'       => 'affiliate_team_member',
            'meta_key'   => 'associated_affiliate_id',
            'meta_value' => strval($associated_id),
            'fields'     => 'ID'
        ]);

        // Update each team member's price
        foreach ($user_ids as $id) {
            update_user_meta($id, 'DJarPrice', $product_price);
        }

        wp_send_json(['success' => true, 'message' => 'Product price updated successfully!']);
        wp_die();
    }

    // Affiliate Profile function
    public function update_affiliate_profile_handler() {

        // Verify nonce for security
        check_ajax_referer('oam_nonce', 'security');

        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'message' => 'You must be logged in to update your profile.']);
            wp_die();
        }

        $user_id = get_current_user_id();
        $affiliate_id = $user_id;
        $associated_id = get_user_meta($user_id, 'associated_affiliate_id', true);

        // Validate and sanitize inputs
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $billing_phone = sanitize_text_field($_POST['billing_phone']);
        $email = sanitize_text_field($_POST['email']);

        $organization_name = sanitize_text_field($_POST['organization_name']);
        $organization_website = esc_url_raw($_POST['organization_website']);
        $address = sanitize_text_field($_POST['address']);
        $city = sanitize_text_field($_POST['city']);
        $state = sanitize_text_field($_POST['state']);
        $zipcode = sanitize_text_field($_POST['zipcode']);
        $tax_id = sanitize_text_field($_POST['tax_id']);

         // Ensure email is valid and not already taken
        if (!is_email($email)) {
            wp_send_json(['success' => false, 'message' => 'Invalid email address.']);
            wp_die();
        }

        // if (email_exists($email) && email_exists($email) != $user_id) {
        //     wp_send_json(['success' => false, 'message' => 'This email is already taken.']);
        //     wp_die();
        // }

        // Update user profile data (including email)
        $update_data = [
            'ID'         => $associated_id,
            '_yith_wcaf_first_name' => $first_name,
            '_yith_wcaf_last_name'  => $last_name,
            'user_email' => $email, 
        ];

        // $user_update = wp_update_user($update_data);

        // if (is_wp_error($user_update)) {
        //     wp_send_json(['success' => false, 'message' => 'Error updating profile.']);
        //     wp_die();
        // }

        $user_ids = get_users([
            'meta_key'   => 'associated_affiliate_id',
            'meta_value' => strval($associated_id),
            'fields'     => 'ID'
        ]);

        // Update each team member's price
        foreach ($user_ids as $id) {
            // Update user meta
            update_user_meta($id, '_yith_wcaf_first_name', $first_name);
            update_user_meta($id, '_yith_wcaf_last_name', $last_name);
            update_user_meta($id, 'billing_phone', $billing_phone);

            //affiliate Fields data update
            // update_user_meta($id, '_yith_wcaf_first_name', $organization_name);
            // update_user_meta($id, '_yith_wcaf_last_name', '');
            update_user_meta($id, '_yith_wcaf_phone_number', $billing_phone);
            update_user_meta($id, '_yith_wcaf_name_of_your_organization', $organization_name);
            update_user_meta($id, '_yith_wcaf_your_organizations_website', $organization_website);
            update_user_meta($id, '_yith_wcaf_address', $address);
            update_user_meta($id, '_yith_wcaf_city', $city);
            update_user_meta($id, '_yith_wcaf_state', $state);
            update_user_meta($id, '_yith_wcaf_zipcode', $zipcode);
            update_user_meta($id, '_yith_wcaf_tax_id', $tax_id);
        }

        wp_send_json(['success' => true, 'message' => 'Your profile has been updated successfully.']);
        wp_die();

    }

    //Manage Affiliate Team Member 
    public function manage_affiliate_team_member_users_handler() {
        // Verify nonce for security
        check_ajax_referer('oam_nonce', 'security');

        // Validate required fields
        if (empty($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json(['success' => false, 'message' => esc_html__('Invalid email!', 'text-domain')]);
        }

        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['type'])) {
            wp_send_json(['success' => false, 'message' => esc_html__('All fields are required!', 'text-domain')]);
        }

        // Sanitize input data
        $user_id        = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $affiliate_id   = isset($_POST['affiliate_id']) ? intval($_POST['affiliate_id']) : 0;
        $first_name     = sanitize_text_field($_POST['first_name']);
        $last_name      = sanitize_text_field($_POST['last_name']);
        $email          = sanitize_email($_POST['email']);
        $phone          = sanitize_text_field($_POST['phone']);
        $affiliate_type = sanitize_text_field($_POST['type']);

       
        // If creating new user
        if (empty($user_id)) {
            // Check if the email is already in use
            if (email_exists($email)) {
                wp_send_json(['success' => false, 'message' => esc_html__('Organization member already exists.', 'text-domain')]);
            }

            // Use current user as affiliate owner
            
            // Create a new user
            $password = wp_generate_password();
            $user_id  = wp_create_user($email, $password, $email);

            if (is_wp_error($user_id)) {
                wp_send_json(['success' => false, 'message' => esc_html__('Error creating user!', 'text-domain')]);
            }

            // Set user details
            wp_update_user([
                'ID'         => $user_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
            ]);

            // Assign role
            $user = new WP_User($user_id);
            $user->set_role('affiliate_team_member');
            $user->add_role('customer'); // Optional

            $organization_name = get_user_meta($affiliate_id, '_yith_wcaf_name_of_your_organization', true);

            // Send welcome email
            $from_name  = get_bloginfo('name');
            $from_email = get_option('admin_email');
            $headers    = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            ];

            $subject = esc_html__('Welcome to Honey From The Heart – Your Account Details', 'text-domain');

            $message = sprintf(
                'Hello %s,<br><br>You have been successfully added to <strong>Honey From The Heart</strong> by <strong>%s</strong>.<br>
                Please log in to the organization portal using the credentials below:<br><br>
                <strong>Login Here:</strong> <a href="%s">%s</a><br>
                <strong>Email:</strong> %s<br>
                <strong>Password:</strong> %s<br><br>
                You can update your password anytime by visiting the <strong>My Profile</strong> section.<br><br>
                If you have any questions, feel free to contact us at <a href="mailto:%s">%s</a>.<br><br>
                Warm regards,<br><strong>The Honey From The Heart Team.</strong>',
                esc_html($first_name),
                $organization_name,
                esc_url(site_url('organization-login')),
                esc_url(site_url('organization-login')),
                esc_html($email),
                esc_html($password),
                esc_html($from_email),
                esc_html($from_email)
            );

            wp_mail($email, $subject, $message, $headers);
        }

        // Update user meta (for both new and existing users)
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'shipping_phone', $phone);
        update_user_meta($user_id, 'user_field_type', $affiliate_type);
        update_user_meta($user_id, 'associated_affiliate_id', $affiliate_id);

        // Send success response
        $message = empty($_POST['user_id']) 
            ? esc_html__('Organization member created successfully!', 'text-domain') 
            : esc_html__('Organization member updated successfully!', 'text-domain');

        wp_send_json(['success' => true, 'message' => $message]);
    }


    //get Affiliate user data in form
    public function get_affiliate_team_member_by_base_id_handler() {

        $userid  = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $array = [];
        $affiliate_users = get_users([
            'role'    => 'affiliate_team_member',
            'include' => [$userid],
        ]);

        if (!empty($affiliate_users)) :
            foreach ($affiliate_users as $user) :
                $array['userid'] =  $user->ID;
                $array['phone'] =  get_user_meta($user->ID, 'phone', true);
                $array['affiliate_type'] =  get_user_meta($user->ID, 'user_field_type', true);
                $array['first_name'] =  $user->first_name;
                $array['last_name'] =   $user->last_name;
                $array['email'] =  $user->user_email;
                
            endforeach;
        endif;

        wp_send_json(['success' => true, 'message' => 'Organization Member details update successfully!', 'data' => $array]);
    }

    // Delete Affiliate user 
    public function deleted_affiliate_team_member_handler(){
        // Verify nonce for security
        check_ajax_referer('oam_nonce', 'security');

        if (!defined('DOING_AJAX') || !DOING_AJAX) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }
    
        if (empty($_POST['id'])) {
            wp_send_json_error(['message' => 'User ID is required.']);
        }
    
        $user_id = intval($_POST['id']);
        $user = get_userdata($user_id);
    
        // Check if user exists
        if (!get_userdata($user_id)) {
            wp_send_json_error(['message' => 'User not found.']);
        }
    
        // Attempt to delete user
        delete_user_meta($user_id, 'associated_affiliate_id');
        $user->remove_role('affiliate_team_member');
    
        if ($deleted) {
            wp_send_json_success(['message' => 'User deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete user.']);
        }
    }
    

}

new OAM_AFFILIATE_Ajax();