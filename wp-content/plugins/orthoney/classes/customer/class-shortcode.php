<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_Shortcode
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('get_group', array( $this, 'get_group_handler' ) );
        add_shortcode('customer_dashboard', array( $this, 'customer_dashboard_handler' ) );
    }

    
    public function get_group_handler() {
        ob_start();

        $OAM_Helper = new OAM_Helper();
        $OAM_Helper->getGroup();
        return ob_get_clean();
    }

    public function customer_dashboard_handler() {
        ob_start();

        $endpoint = get_query_var('customer_endpoint');

        $template_path = OH_PLUGIN_DIR_PATH . '/templates/customer/customer-dashboard/';

        if ($endpoint === 'affiliate-manage' && file_exists($template_path . 'affiliate-manage.php')) {
            require_once $template_path . 'affiliate-manage.php';
        } else {
            require_once $template_path . 'dashboard.php';
        }
            
        return ob_get_clean();
    }
   

}
new OAM_Shortcode();