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

        //Manage Affiliate Team Member 
        add_action( 'wp_ajax_manage_affiliate_team_member_users', array( $this, 'manage_affiliate_team_member_users_handler' ) );
        
        //get Affiliate user data in form
        add_action( 'wp_ajax_get_affiliate_team_member_by_base_id', array( $this, 'get_affiliate_team_member_by_base_id_handler' ) );

        // Delete Affiliate user 
        add_action( 'wp_ajax_deleted_affiliate_team_member', array( $this, 'deleted_affiliate_team_member_handler' ) );

        // Change Affiliate Admin user
        add_action('wp_ajax_change_user_role_logout', array( $this, 'change_user_role_logout_handler' ));

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

            // Swap roles in a single loop
            $all_roles = array_unique(array_merge($selected_user_roles, $current_user_roles));
            
            foreach ($all_roles as $role) {
                if (in_array($role, $selected_user_roles)) {
                    $selected_user->remove_role($role);
                    $current_user->add_role($role);
                }
                if (in_array($role, $current_user_roles)) {
                    $current_user->remove_role($role);
                    $selected_user->add_role($role);
                }
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
        $billing_email = sanitize_text_field($_POST['billing_email']);
    
        // Update user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name', $last_name);
        update_user_meta($user_id, 'billing_phone', $billing_phone);
        
    
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
                wp_send_json(['success' => false, 'message' => esc_html__('Affiliate Member already exists!', 'text-domain')]);
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