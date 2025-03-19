<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
class OAM_AFFILIATE_Scripts{
    
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        // Hook to enqueue scripts and styles on the front-end
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_affiliate_profile_script' ) );
        
    }

    public function enqueue_affiliate_profile_script() {

        wp_enqueue_script(
            'affiliate-dashboard',
            OH_PLUGIN_DIR_URL. 'assets/js/affiliate-dashboard.js',
            array( 'sweetalert2', 'lity-js' ), 
            time(),          
            true 
        );
        wp_localize_script(
            'affiliate-dashboard', 
            'oam_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'oam_nonce' ),
            )
        );
    }

}

new OAM_AFFILIATE_Scripts();