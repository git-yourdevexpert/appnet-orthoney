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
            $message = 'Please login to view your affiliate dashboard.';
            $url = home_url('/login');
            $btn_name = 'Login';
            return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
        }
    
        $user_id = get_current_user_id();
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);
    
        if (in_array('yith_affiliate', $user_roles) || in_array('affiliate_team_member', $user_roles)) {
            $affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);
            $affiliate_status_check = OAM_AFFILIATE_Helper::affiliate_status_check($affiliate_id);
            $result = json_decode($affiliate_status_check, true);
    
            // Check if the user is a team member and override the status message
            if (in_array('affiliate_team_member', $user_roles) && isset($result['message']) && $result['message'] === 'You are not registered as an affiliate.') {
                // Allow access to the dashboard for affiliate_team_member
                echo OAM_AFFILIATE_Helper::affiliate_dashboard_navbar($user_roles);
    
                $endpoint = get_query_var('affiliate_endpoint');
                $template_path = OH_PLUGIN_DIR_PATH . '/templates/affiliate/affiliate-dashboard/';
    
                if ($endpoint === 'my-profile' && file_exists($template_path . 'my-profile.php')) {
                    include_once $template_path . 'my-profile.php';
                } elseif ($endpoint === 'user-list' && file_exists($template_path . 'user-list.php')) {
                    echo '<div class="oam-access-denied-message">You do not have access to this page.</div>';
                } elseif ($endpoint === 'order-list' && file_exists($template_path . 'order-list.php')) {
                    include_once $template_path . 'order-list.php';
                } elseif ($endpoint === 'change-admin' && file_exists($template_path . 'change-admin.php')) {
                    echo '<div class="oam-access-denied-message">You do not have access to this page.</div>';
                } elseif ($endpoint === 'link-customer' && file_exists($template_path . 'link-customer.php')) {
                    echo '<div class="oam-access-denied-message">You do not have access to this page.</div>';
                } else {
                    include_once $template_path . 'dashboard.php';
                }
            } elseif (!empty($result) && isset($result['success'])) {
                // Regular flow for yith_affiliate or any other status
                if ($result['success'] == 1) {
                    echo OAM_AFFILIATE_Helper::affiliate_dashboard_navbar($user_roles);
    
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
                    }elseif ($endpoint === 'link-customer' && file_exists($template_path . 'link-customer.php')) {
                        include_once $template_path . 'link-customer.php';
                    } else {
                        include_once $template_path . 'dashboard.php';
                    }
                } else {
                    // Error messages if success is not 1
                    if (!empty($result['message'])) {
                        $message = $result['message'];
                        $url = home_url('/customer-dashboard');
                        $btn_name = 'Customer Dashboard';
                        return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
                    }
                    if (!empty($result['reason'])) {
                        $message = $result['reason'];
                        $url = home_url('/customer-dashboard');
                        $btn_name = 'Customer Dashbaord';
                        return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
                    }
                }
            }
        } else {
            echo "<p>You do not have access to this page.</p>";
        }
    
        return ob_get_clean();
    }
    
}
new OAM_AFFILIATE_Shortcode();