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
                $this->send_affiliate_email($customer_id, $affiliate_id, 'Resend : Affiliate Request Notification', $token);
                wp_send_json(['success' => true, 'message' => 'Resend request successfully!']);
            }
            wp_send_json(['success' => false, 'message' => 'Customer is already linked with you.']);
        }
    
        if ($wpdb->insert($table_name, ['customer_id' => $customer_id, 'affiliate_id' => $affiliate_id, 'status' => 0, 'token' => $token], ['%d', '%d', '%d', '%s'])) {
            $this->send_affiliate_email($customer_id, $affiliate_id, 'Affiliate Request Notification', $token);
            wp_send_json(['success' => true, 'message' => 'Request sent successfully.', 'token' => $token]);
        }
    
        wp_send_json(['success' => false, 'message' => 'Failed to send request.']);
    }
    
    private function send_affiliate_email($customer_id, $affiliate_id, $subject, $token) {
        $customer_email = get_userdata($customer_id)->user_email;
        $affiliate_name = get_userdata($affiliate_id)->display_name;
        $message = "Hello,\n\nYou have received an affiliate request from {$affiliate_name}.\n\n";
        $message .= "Please approve or reject the request using the following link:\n";
        $message .= home_url("/?action=organization-link&token={$token}");
        wp_mail($customer_email, $subject, $message, ['Content-Type: text/plain; charset=UTF-8']);
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

        if (!is_user_logged_in()) {
            wp_send_json(['success' => false, 'message' => 'You must be logged in to update your profile.']);
            wp_die();
        }

        $user_id = get_current_user_id();
        $product_price = sanitize_text_field($_POST['product_price']);

        // Update current user's price
        update_user_meta($user_id, 'DJarPrice', $product_price);

        // Get team members associated with this user
        $user_ids = get_users([
            'role'       => 'affiliate_team_member',
            'meta_key'   => 'associated_affiliate_id',
            'meta_value' => strval($user_id),
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

        if (email_exists($email) && email_exists($email) != $user_id) {
            wp_send_json(['success' => false, 'message' => 'This email is already taken.']);
            wp_die();
        }

        // Update user profile data (including email)
        $update_data = [
            'ID'         => $user_id,
            '_yith_wcaf_first_name' => $first_name,
            '_yith_wcaf_last_name'  => $last_name,
            'user_email' => $email, 
        ];

        $user_update = wp_update_user($update_data);

        if (is_wp_error($user_update)) {
            wp_send_json(['success' => false, 'message' => 'Error updating profile.']);
            wp_die();
        }

        // Update user meta
        update_user_meta($user_id, '_yith_wcaf_first_name', $first_name);
        update_user_meta($user_id, '_yith_wcaf_last_name', $last_name);
        update_user_meta($user_id, 'billing_phone', $billing_phone);

        //affiliate Fields data update
        // update_user_meta($user_id, '_yith_wcaf_first_name', $organization_name);
        // update_user_meta($user_id, '_yith_wcaf_last_name', '');
        update_user_meta($user_id, '_yith_wcaf_phone_number', $billing_phone);
        update_user_meta($user_id, '_yith_wcaf_name_of_your_organization', $organization_name);
        update_user_meta($user_id, '_yith_wcaf_your_organizations_website', $organization_website);
        update_user_meta($user_id, '_yith_wcaf_address', $address);
        update_user_meta($user_id, '_yith_wcaf_city', $city);
        update_user_meta($user_id, '_yith_wcaf_state', $state);
        update_user_meta($user_id, '_yith_wcaf_zipcode', $zipcode);
        update_user_meta($user_id, '_yith_wcaf_tax_id', $tax_id);


        wp_send_json(['success' => true, 'message' => 'Affiliate Profile updated successfully!']);
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
        $user_id        = isset($_POST['user_id']) ? intval($_POST['user_id']) : '';
        $first_name     = sanitize_text_field($_POST['first_name']);
        $last_name      = sanitize_text_field($_POST['last_name']);
        $email          = sanitize_email($_POST['email']);
        $phone          = sanitize_text_field($_POST['phone']);
        $affiliate_type = sanitize_text_field($_POST['type']);
        $current_user   = get_current_user_id();

        if (empty($user_id)) {
            // Check if the email is already in use
            if (email_exists($email)) {
                wp_send_json(['success' => false, 'message' => esc_html__('Organization Users is Already Exist', 'text-domain')]);
            }

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
            $user->add_role('customer');

            // Email verification setup
            
            $verification_token = wp_generate_password(32, false);
            update_user_meta($user_id, 'email_verification_token', $verification_token);

            // Generate verification link
            $verification_link = add_query_arg([
                'action'  => 'verify_email',
                'token'   => $verification_token,
                'user_id' => $user_id
            ], home_url('/'));

            // Send verification email
            $from_name  = get_bloginfo('name');
            $from_email = get_option('admin_email');
            $headers    = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $from_name . ' <' . $from_email . '>'
            ];
            
            $subject = esc_html__('Email Verification Required', 'text-domain');
            $message = sprintf(
                'Hello %s,<br><br>Thank you for registering. Please verify your email by clicking the button below:<br><br><a href="%s" style="background-color:#4CAF50;color:#ffffff;padding:12px 25px;text-decoration:none;border-radius:5px;">Verify Email Address</a><br><br>If you did not create this account, please ignore this email.',
                esc_html($first_name),
                esc_url($verification_link)
            );

            wp_mail($email, $subject, $message, $headers);
        }

        // Update user meta data
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'shipping_phone', $phone);
        update_user_meta($user_id, 'user_field_type', $affiliate_type);
        update_user_meta($user_id, 'associated_affiliate_id', $current_user);

        // Send success response
        $message = empty($_POST['user_id']) ? esc_html__('Affiliate Member created successfully!', 'text-domain') : esc_html__('Affiliate Member updated successfully!', 'text-domain');
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
                $array['phone'] =  get_user_meta($user->ID, 'billing_phone', true);
                $array['affiliate_type'] =  get_user_meta($user->ID, 'user_field_type', true);
                $array['first_name'] =  $user->first_name;
                $array['last_name'] =   $user->last_name;
                $array['email'] =  $user->user_email;
                
            endforeach;
        endif;

        wp_send_json(['success' => true, 'message' => 'Affiliate Member Updated successfully!', 'data' => $array]);
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
    
        // Check if user exists
        if (!get_userdata($user_id)) {
            wp_send_json_error(['message' => 'User not found.']);
        }
    
        // Attempt to delete user
        $deleted = wp_delete_user($user_id);
    
        if ($deleted) {
            wp_send_json_success(['message' => 'User deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete user.']);
        }
    }
    

}

new OAM_AFFILIATE_Ajax();