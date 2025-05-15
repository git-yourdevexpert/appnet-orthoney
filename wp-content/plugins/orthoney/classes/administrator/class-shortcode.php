<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_ADMINISTRATOR_Shortcode
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('administrator_dashboard', array( $this, 'administrator_dashboard_handler' ) );
    }

    public function administrator_dashboard_handler() {
        ob_start();

        if (!is_user_logged_in()) {
            $message = 'Please login to view your Administrator dashboard.';
            $url = home_url('/login');
            $btn_name = 'Login';
            return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
        }

        $user_id = get_current_user_id();
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);

        if (in_array('administrator', $user_roles)) {
           
            echo OAM_ADMINISTRATOR_Helper::administrator_dashboard_navbar($user_roles);

            $endpoint = get_query_var('administrator');
            $template_path = OH_PLUGIN_DIR_PATH . '/templates/administrator/administrator-dashboard/';

            switch ($endpoint) {
                case 'orders':
                    $file = $template_path . 'orders.php';
                    break;
                case 'sales-representative':
                    $file = $template_path . 'sales-representative.php';
                    break;
                case 'customer':
                    $file = $template_path . 'customer.php';
                    break;
                case 'organizations':
                    $file = $template_path . 'organizations.php';
                    break;
                default:
                    $file = $template_path . 'dashboard.php';
                    break;
            }

            if (file_exists($file)) {
                include_once $file;
            } else {
                echo '<p>Template not found: ' . esc_html($file) . '</p>';
            }

        } else {
            echo "<p>You do not have access to this page.</p>";
        }

        return ob_get_clean();
    }

   
    
    
    
}
new OAM_ADMINISTRATOR_Shortcode();