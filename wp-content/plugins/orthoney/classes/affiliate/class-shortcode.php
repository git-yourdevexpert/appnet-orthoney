<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_AFFILIATE_Shortcode
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('affiliate_dashboard', array( $this, 'affiliate_dashboard_handler' ) );
    }

    
    public function affiliate_dashboard_handler() {
        ob_start();

        $endpoint = get_query_var('affiliate_endpoint');

        $template_path = OH_PLUGIN_DIR_PATH . '/templates/affiliate/affiliate-dashboard/';

        if ($endpoint === 'my-profile' && file_exists($template_path . 'my-profile.php')) {
            require_once $template_path . 'my-profile.php';
        } elseif ($endpoint === 'user-manage' && file_exists($template_path . 'user-manage.php')) {
            require_once $template_path . 'user-manage.php';
        } elseif ($endpoint === 'user-list' && file_exists($template_path . 'user-list.php')) {
            require_once $template_path . 'user-list.php';
        } elseif ($endpoint === 'order-list' && file_exists($template_path . 'order-list.php')) {
            require_once $template_path . 'order-list.php';
        } else {
            require_once $template_path . 'dashboard.php';
        }
            
        return ob_get_clean();
    }
   

}
new OAM_AFFILIATE_Shortcode();