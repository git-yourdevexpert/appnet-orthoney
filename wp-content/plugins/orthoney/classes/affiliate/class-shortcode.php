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

        if (!is_user_logged_in()) {
            return '<p>Please <a href="' . esc_url(wp_login_url()) . '">log in</a> to view your affiliate dashboard.</p>';
        }

        $user_id = get_current_user_id();
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);

        if ( in_array( 'yith_affiliate', $user_roles) OR  in_array( 'affiliate_team_member', $user_roles)) {
            $affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);
            
            $affiliate_status_check = OAM_AFFILIATE_Helper::affiliate_status_check($affiliate_id);

            $result = json_decode($affiliate_status_check, true);

            if (!empty($result) && isset($result['success']) && $result['success']) {
                
                if(!empty($result['message'])|| !empty($result['reason'])){
                    echo '<div class="main-message">';
                    if (!empty($result['message'])) {
                        echo 'Message: ' . esc_html($result['message']) . '<br>';
                    }
                    if (!empty($result['reason'])) {
                        echo 'Reason: ' . esc_html($result['reason']);
                    }
                    echo '</div>';
                }

                // Append the profile button conditionally
                if (isset($result['success']) && $result['success'] == 1) {
                    echo OAM_AFFILIATE_Helper::affiliate_dashboard_navbar($user_roles);
                }

                $endpoint = get_query_var('affiliate_endpoint');

                $template_path = OH_PLUGIN_DIR_PATH . '/templates/affiliate/affiliate-dashboard/';

                if ($endpoint === 'my-profile' && file_exists($template_path . 'my-profile.php')) {
                    include_once $template_path . 'my-profile.php';
                } elseif ($endpoint === 'user-list' && file_exists($template_path . 'user-list.php')) {
                    include_once $template_path . 'user-list.php';
                } elseif ($endpoint === 'order-list' && file_exists($template_path . 'order-list.php')) {
                    include_once $template_path . 'order-list.php';
                } elseif ($endpoint === 'change-admin' && file_exists($template_path . 'change-admin.php')) {
                    include_once $template_path . 'change-admin.php';
                } else {
                    include_once $template_path . 'dashboard.php';
                }
            }
        }else{
            echo "<p>You have not access for this page</p>";
        }
            
        return ob_get_clean();
    }

}
new OAM_AFFILIATE_Shortcode();