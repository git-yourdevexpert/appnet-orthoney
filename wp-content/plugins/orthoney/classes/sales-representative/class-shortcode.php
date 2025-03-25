<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_SALES_REPRESENTATIVE_Shortcode
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('sales_representative_dashboard', array( $this, 'sales_representative_dashboard_handler' ) );
    }

    public function sales_representative_dashboard_handler() {
        ob_start();
    
        if (!is_user_logged_in()) {
            $message = 'Please login to view your Sales Representative dashboard.';
            $url = home_url('/login');
            $btn_name = 'Login';
            return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
        }
        
        $user_id = get_current_user_id();
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);
       
        if (in_array('sales_representative', $user_roles)) {
            // Check if the user is a team member and override the status message
           if (in_array('sales_representative', $user_roles)) {
                echo OAM_SALES_REPRESENTATIVE_Helper::sales_representative_dashboard_navbar($user_roles);
                 $endpoint = get_query_var('sales_representative_endpoint');
                 $template_path = OH_PLUGIN_DIR_PATH . '/templates/sales-representative/';
                if ($endpoint === 'my-profile' && file_exists($template_path . 'my-profile.php')) {
                    include_once $template_path . 'my-profile.php';
                } elseif ($endpoint === 'manage-customer' && file_exists($template_path . 'manage-customer.php')) {
                    include_once $template_path . 'manage-customer.php';
                } elseif ($endpoint === 'manage-organization' && file_exists($template_path . 'manage-organization.php')) {
                    include_once $template_path . 'manage-organization.php';
                } 
                else {
                    include_once $template_path . 'dashboard.php';
                }
            } 
        } else {
             echo "<p>You do not have access to this page.</p>";
        }
    
        return ob_get_clean();
    }
    
}
new OAM_SALES_REPRESENTATIVE_Shortcode();