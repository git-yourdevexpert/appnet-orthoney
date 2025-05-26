<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_ADMINISTRATOR_HELPER {
    /**
     * Constructor to hook into HELPERer template loading.
     */
    public function __construct() {
        
    }
    
     public static function administrator_dashboard_navbar($user_roles = array()){
        $output = '';
        if ( in_array( 'administrator', $user_roles)) {
            $output = '<div class="affiliate-dashboard">';
            $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK) . '">Dashboard</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK.'/my-profile/') . '">My Profile</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK.'/orders-list/') . '">Order List</a></div>';
            if ( ! in_array( 'affiliate_team_member', $user_roles)) {
                $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK.'/change-admin/') . '">Change Admin</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK.'/link-customer/') . '">Link Customer</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK.'/users-list/') . '">User List</a></div>';
            }
            $output .= '<div class="btn"><a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a></div>';
            $output .= '</div>';
        return '';
        }
    }
    
}

// Initialize the class
new OAM_ADMINISTRATOR_HELPER();