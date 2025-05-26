<?php

// Prevent direct access

if (!defined('ABSPATH')) {

    exit;

}



class OAM_SALES_REPRESENTATIVE_Helper {

    public function __construct() {}



    public static function init() {

    }

    public static function sales_representative_dashboard_navbar($user_roles = array()){
        $output = '';
        if ( in_array( 'sales_representative', $user_roles) || in_array('administrator', $user_roles)) {
            $output = '<div class="sales-representative-dashboard">';

            $output .= '<div class="btn"><a href="' . esc_url(SALES_REPRESENTATIVE_DASHBOARD_LINK) . '">Dashboard</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(SALES_REPRESENTATIVE_DASHBOARD_LINK.'manage-customers/') . '">Manage Customers</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(SALES_REPRESENTATIVE_DASHBOARD_LINK.'manage-organizations/') . '">Manage Organizations</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(SALES_REPRESENTATIVE_DASHBOARD_LINK.'my-profile/') . '">My Profile</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a></div>';

            $output .= '</div>';

        return $output;
        }
    }


}



// Initialize the class properly

new OAM_SALES_REPRESENTATIVE_Helper();

OAM_SALES_REPRESENTATIVE_Helper::init();