<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
class OAM_affiliate_Scripts{


    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        // Hook to enqueue scripts and styles on the front-end
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_affiliate_profile_script' ) );
        
    }

    public function enqueue_affiliate_profile_script() {

        wp_enqueue_script(
            'affiliate-dashboard.js',
            OH_PLUGIN_DIR_URL. 'assets/js/affiliate-dashboard.js',
            array( 'sweetalert2', 'lity-js' ), 
            '1.0.0',          
            true 
        );
        wp_localize_script(
            'affiliate-profile-script', 
            'affiliateProfileAjax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'oam_nonce' ),
            )
        );
    }

}