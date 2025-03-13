<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_Scripts
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        // Hook to enqueue scripts and styles on the front-end
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        
    }

    // Enqueue front-end assets
    public function enqueue_frontend_assets() {

        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11',  array( 'jquery' ), '1.0.0', true);
        wp_enqueue_style('sweetalert2-css', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', [], null);

        wp_enqueue_style('lity-css', 'https://cdn.jsdelivr.net/npm/lity@2.4.1/dist/lity.min.css', array(), '2.4.1');
        wp_enqueue_script('lity-js', 'https://cdn.jsdelivr.net/npm/lity@2.4.1/dist/lity.min.js', array('jquery'), '2.4.1', true);

        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '2.4.1');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '2.4.1', true);


        wp_enqueue_style(
            'oam-frontend-style',
            OH_PLUGIN_DIR_URL. 'assets/css/oam-frontend-style.css',
            array(),
            time()
        );
        wp_enqueue_style(
            'oam-frontend-style-1',
            OH_PLUGIN_DIR_URL. 'assets/css/oam-frontend-style-1.css',
            array(),
            time()
        );

        wp_enqueue_script(
            'oam-frontend-script',
            OH_PLUGIN_DIR_URL. 'assets/js/oam-frontend-script.js',
            array( 'sweetalert2', 'lity-js' ), 
            time(),          
            true 
        );

        wp_enqueue_script(
            'oam-recipient-form-script',
            OH_PLUGIN_DIR_URL. 'assets/js/oam-recipient-form-script.js',
            array( 'oam-frontend-script' ), 
            time(),          
            true 
        );

        wp_enqueue_script(
            'oam-dev-frontend-script',
            OH_PLUGIN_DIR_URL. 'assets/js/oam-dev-frontend-script.js',
            array( 'oam-frontend-script'), 
             time(),          
            true 
        );

       
        
        wp_localize_script(
            'oam-frontend-script', 
            'oam_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'oam_nonce' ),
            )
        );

        wp_localize_script(
            'oam-recipient-form-script', 
            'oam_ajax',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'oam_nonce' ),
            )
        );
    }

}
new OAM_Scripts();