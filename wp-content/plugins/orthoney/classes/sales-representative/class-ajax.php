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
        if (!current_user_can('switch_users')) {
            wp_send_json(['success' => false, 'message' => 'You do not have permission to switch users.']);
        }

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