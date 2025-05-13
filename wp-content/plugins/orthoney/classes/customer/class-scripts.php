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
        $plugin_url = OH_PLUGIN_DIR_URL . 'assets/';
        
        // Library Scripts & Styles
        $libs = [
            ['dataTables-js', 'libs/dataTables/jquery.dataTables.min.js', ['jquery']],
            ['tippy-js', 'libs/tippy/popper.min.js', ['dataTables-js']],
            ['tippy-core', 'libs/tippy/tippy-bundle.umd.min.js', ['tippy-js']],
            ['sweetalert2', 'libs/sweetalert/sweetalert2.js', ['jquery']],
            ['lity-js', 'libs/lity/lity.min.js', ['jquery'], '2.4.1'],
            ['select2-js', 'libs/select2/select2.min.js', ['jquery'], '2.4.1'],
            ['jqueryui-js', 'libs/jqueryui/jquery-ui.js', ['jquery'], '1.14.1']
        ];
    
        $styles = [
            ['dataTables-css', 'libs/dataTables/jquery.dataTables.min.css'],
            ['tippy-css', 'libs/tippy/light.css'],
            ['sweetalert2-css', 'libs/sweetalert/sweetalert2.min.css'],
            ['lity-css', 'libs/lity/lity.min.css', [], '2.4.1'],
            ['select2-css', 'libs/select2/select2.min.css', [], '2.4.1'],
            ['jquery-ui-css', 'libs/jqueryui/jquery-ui.css', [], '1.14.1']
        ];
        
        foreach ($libs as $lib) {
            wp_enqueue_script($lib[0], $plugin_url . $lib[1], $lib[2] ?? [], time(), true);
        }
        
        foreach ($styles as $style) {
            wp_enqueue_style($style[0], $plugin_url . $style[1], $style[2] ?? [], $style[3] ?? null);
        }
    
        // Plugin-Specific Styles
        wp_enqueue_style('user-registration-general', WP_PLUGIN_URL.'/user-registration-pro/assets/css/user-registration.css');
        wp_enqueue_style('oam-frontend-style', $plugin_url . 'css/oam-frontend-style.css', [], time());
        wp_enqueue_style('oam-frontend-style-1', $plugin_url . 'css/oam-frontend-style-1.css', [], time());
    
        // Plugin-Specific Scripts
        $scripts = [
            ['oam-frontend-script', 'js/oam-frontend-script.js', ['dataTables-js','sweetalert2', 'lity-js']],
            ['oam-recipient-form-script', 'js/oam-recipient-form-script.js', ['oam-frontend-script']],
            ['oam-dev-frontend-script', 'js/oam-dev-frontend-script.js', ['oam-frontend-script']]
        ];
    
        foreach ($scripts as $script) {
            wp_enqueue_script($script[0], $plugin_url . $script[1], $script[2], time(), true);
        }
        
        // Localize Scripts
        $localize_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('oam_nonce')
        ];
        wp_localize_script('oam-frontend-script', 'oam_ajax', $localize_data);
    }
    

}
new OAM_Scripts();