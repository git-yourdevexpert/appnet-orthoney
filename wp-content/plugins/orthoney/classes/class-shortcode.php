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
    }

    
    public function get_group_handler() {
        ob_start();

        $OAM_Helper = new OAM_Helper();
        $OAM_Helper->getGroup();
        return ob_get_clean();
    }
   

}
new OAM_Shortcode();