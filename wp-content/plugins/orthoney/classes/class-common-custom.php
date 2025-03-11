<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_COMMON_Custom {
    /**
     * Constructor to hook into Affiliate template loading.
     */
    public function __construct() {       
        add_action('user_register', array($this, 'check_userrole_update_meta'));
    }

    public static function init() {
        // Add any initialization logic here
    }

    function check_userrole_update_meta($user_id) {
        $user = get_userdata($user_id);
        $roles = $user->roles; // Get the user's roles (array)
        
        $logged_in_user = 0;
        // Check if the user was created by a logged-in user or a guest
        if (is_user_logged_in()) {
            $logged_in_user = wp_get_current_user();
        }

    }
    
    public static function get_user_role_by_id($user_id) {
        $user = get_userdata($user_id);
        return !empty($user) ? $user->roles : [];
    }
}

// Instantiate and initialize
new OAM_COMMON_Custom();
OAM_COMMON_Custom::init();
