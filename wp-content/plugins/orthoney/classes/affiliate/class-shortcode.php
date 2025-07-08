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
        add_shortcode('org_token', array( $this, 'org_token_handler' ) );
    }

    public function org_token_handler() {
        ob_start();
        $affiliate_id = get_current_user_id();
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($affiliate_id);



        if( !in_array('yith_affiliate ', $user_roles)){
            if(in_array('affiliate_team_member', $user_roles) OR in_array('administrator', $user_roles)){
                if(get_user_meta($affiliate_id, 'associated_affiliate_id', true)){
                    $affiliate_id = get_user_meta($affiliate_id, 'associated_affiliate_id', true);

                }
            }
        }
        $details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);
        if (!empty($details['token'])) {
            echo "<h6><strong>Token :  ".$details['token']."</strong></h6>";
        }

        return ob_get_clean();
    }
    public function affiliate_dashboard_handler() {
        ob_start();

    //    $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate(get_current_user_id());
    //     $commission_array_data = json_decode($commission_array, true);
    //    echo "<pre>";
    //    print_r($commission_array_data);
    //    echo "</pre>";
           
        if (!is_user_logged_in()) {
            $message = 'Please login to view your affiliate dashboard.';
            $url = home_url('/login');
            $btn_name = 'Login';
            return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
        }
    
        $user_id = get_current_user_id();
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);
    
        if (in_array('yith_affiliate', $user_roles) || in_array('affiliate_team_member', $user_roles) || in_array('administrator', $user_roles)) {
            $affiliate_id = get_user_meta($user_id, 'associated_affiliate_id', true);

            if( $affiliate_id == '' && in_array('yith_affiliate', $user_roles) ){
                 $affiliate_id = get_current_user_id();
            }

            $affiliate_status_check = OAM_AFFILIATE_Helper::affiliate_status_check($affiliate_id);
            $result = json_decode($affiliate_status_check, true);

            if (in_array('administrator', $user_roles)) {
                echo OAM_AFFILIATE_Helper::affiliate_dashboard_navbar($user_roles);
                $endpoint = get_query_var('affiliate_endpoint');
                $template_path = OH_PLUGIN_DIR_PATH . '/templates/affiliate/affiliate-dashboard/';
    
                if (file_exists($template_path . $endpoint . '.php')) {
                    include_once $template_path . $endpoint . '.php';
                } else {
                    include_once $template_path . 'dashboard.php';
                }
            } elseif ((in_array('affiliate_team_member', $user_roles)) && isset($result['message']) && $result['message'] === 'You are not registered as an affiliate.') {
                echo OAM_AFFILIATE_Helper::affiliate_dashboard_navbar($user_roles);
                $endpoint = get_query_var('affiliate_endpoint');
                $template_path = OH_PLUGIN_DIR_PATH . '/templates/affiliate/affiliate-dashboard/';
    
                if (in_array($endpoint, ['my-profile', 'orders-list']) && file_exists($template_path . $endpoint . '.php')) {
                    include_once $template_path . $endpoint . '.php';
                } else {
                    echo '<div class="oam-access-denied-message">You do not have access to this page.</div>';
                }
            } elseif (!empty($result) && isset($result['success']) && $result['success'] == 1) {
                echo OAM_AFFILIATE_Helper::affiliate_dashboard_navbar($user_roles);
                $endpoint = get_query_var('affiliate_endpoint');
                $template_path = OH_PLUGIN_DIR_PATH . '/templates/affiliate/affiliate-dashboard/';
    
                if (file_exists($template_path . $endpoint . '.php')) {
                    include_once $template_path . $endpoint . '.php';
                } else {
                    include_once $template_path . 'dashboard.php';
                }
            } else {
                if (!empty($result['message'])) {
                    $message = $result['message'];
                    $url = CUSTOMER_DASHBOARD_LINK;
                    $btn_name = 'Customer Dashboard';
                    return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
                }
                if (!empty($result['reason'])) {
                    $message = $result['reason'];
                    $url = CUSTOMER_DASHBOARD_LINK;
                    $btn_name = 'Customer Dashboard';
                    return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
                }
            }
        } else {
            echo "<p>You do not have access to this page.</p>";
        }
    
        return ob_get_clean();
    }
    
}
new OAM_AFFILIATE_Shortcode();