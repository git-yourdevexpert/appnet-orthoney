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
        add_action( 'wp_ajax_save_affiliate_profile', array( $this, 'save_affiliate_profile_handler' ) );

        add_action( 'wp_ajax_create_new_user', array( $this, 'create_new_user' ) );

    }

    // Affiliate Profile fucntion
    public function save_affiliate_profile_handler() {
        $user_id = get_current_user_id();

        // Define fields manually (no foreach)
        update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
     
        // TODO
        update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
        update_user_meta($user_id, 'billing_email', sanitize_email($_POST['billing_email']));
        // TODO
     
        wp_send_json(['success' => true, 'message' => 'Profile updated successfully!']);
        
        wp_die();
    }


    //Create user
    public function create_new_user() {
        // Check if required fields are set
        if (!isset($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json(['success' => false, 'message' => 'Invalid email!']);
        }

        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['type'])) {
            wp_send_json(['success' => false, 'message' => 'All fields are required!']);
        }

        // Sanitize input data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name  = sanitize_text_field($_POST['last_name']);
        $email      = sanitize_email($_POST['email']);
        $phone      = sanitize_text_field($_POST['phone']);
        $affiliate_type = sanitize_text_field($_POST['type']);

        // Define the static role
        $role = 'affiliate_team_member';

        // Check if user already exists
        if (email_exists($email)) {
            wp_send_json(['success' => false, 'message' => 'User already exists!']);
        }

        // Generate a random password
        $password = wp_generate_password();

        // Create the user
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json(['success' => false, 'message' => 'Error creating user!']);
        }

        // Update user data
        wp_update_user([
            'ID'         => $user_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ]);

        $user = new WP_User($user_id);
        $user->set_role($role);

        //acf custom field update
        update_field('field_67c830a35d448', $affiliate_type, 'user_' . $user_id);
        // Set user meta data
        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'shipping_phone', $phone);
        update_user_meta($user_id, 'email_verified', 'true');
        update_user_meta($user_id, 'associated_affiliate_id', get_current_user_id());

        // Send success response
        wp_send_json(['success' => true, 'message' => 'User created successfully!']);
    }

}

new OAM_AFFILIATE_Ajax();