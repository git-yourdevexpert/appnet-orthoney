<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_SALES_REPRESENTATIVE_Shortcode
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('sales_representative_dashboard', array( $this, 'sales_representative_dashboard_handler' ) );

        add_shortcode('recent_customers', array( $this, 'display_recent_customers_shortcode_handler' ) );
        add_shortcode('recent_organizations', array( $this, 'display_recent_organization_shortcode_handler' ) );

    }

    public function sales_representative_dashboard_handler() {
        ob_start();
    
        if (!is_user_logged_in()) {
            $message = 'Please login to view your Sales Representative dashboard.';
            $url = home_url('/login');
            $btn_name = 'Login';
            return OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
        }
        
        $user_id = get_current_user_id();
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);
       
        if (in_array('sales_representative', $user_roles) || in_array('administrator', $user_roles)) {
                echo OAM_SALES_REPRESENTATIVE_Helper::sales_representative_dashboard_navbar($user_roles);
                 $endpoint = get_query_var('sales_representative_endpoint');
                 $template_path = OH_PLUGIN_DIR_PATH . '/templates/sales-representative/';
                if ($endpoint === 'my-profile' && file_exists($template_path . 'my-profile.php')) {
                    include_once $template_path . 'my-profile.php';
                } elseif ($endpoint === 'manage-customer' && file_exists($template_path . 'manage-customer.php')) {
                    include_once $template_path . 'manage-customer.php';
                } elseif ($endpoint === 'manage-organization' && file_exists($template_path . 'manage-organization.php')) {
                    include_once $template_path . 'manage-organization.php';
                } 
                else {
                    include_once $template_path . 'dashboard.php';
                }
           
        } else {
             echo "<p>You do not have access to this page.</p>";
        }
    
        return ob_get_clean();
    }

    public function display_recent_customers_shortcode_handler($atts) {
        $atts = shortcode_atts([
            'limit' => 3,
        ], $atts);
        $limit = intval($atts['limit']);
    
        // Get recent customers
        $args = [
            'orderby' => 'user_registered',
            'order'   => 'DESC',
            'number'  => $limit + 1, // Check for 'View All'
        ];
        $customers = get_users($args);
    
        // Filter only users with a single 'customer' role
        $customers = array_filter($customers, function($user) {
            $roles = (array) $user->roles;
            return count($roles) === 1 && in_array('customer', $roles);
        });
    
        if (empty($customers)) {
            return '<p>No recent customers found.</p>';
        }
    
        $output = '<div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4>Recent Customers</h4>
                <div class="see-all">';
                    if (count($customers) > $limit) { 
                        $output .= '<a class="w-btn us-btn-style_1" href="/sales-representative-dashboard/manage-customer/">See all</a>';
                    }
                $output .= '</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>';
                foreach ($customers as $index => $customer) {
                    if ($index >= $limit) break;
                    $output .= '<tr data-id="52" data-verify="0" data-group="0">';
                    $output .= '<td><div class="thead-data">Customer Name</div>' . esc_html($customer->display_name) . '</td>';
                    $output .= '<td><div class="thead-data">Email</div>' . esc_html($customer->user_email) . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>
            </table>
        </div>';
        return $output;
    }

    public function display_recent_organization_shortcode_handler($atts) {
        $atts = shortcode_atts([
            'limit' => 3,
        ], $atts);
        $limit = intval($atts['limit']);
    
        // Get recent organization
        $args = [
            'role'    => 'yith_affiliate',
            'orderby' => 'user_registered',
            'order'   => 'DESC',
            'number'  => $limit + 1, // Check for 'View All'
        ];
    
        $organizations = get_users($args);
    
        if (empty($organizations)) {
            return '<p>No recent organization found.</p>';
        }
    
        $output = '<div class="recipient-lists-block custom-table">
            <div class="row-block">
                <h4>Recent Organization</h4>
                <div class="see-all">';
                    if (count($organizations) > $limit) { 
                        $output .= '<a class="w-btn us-btn-style_1" href="/sales-representative-dashboard/manage-organization/">See all</a>';
                    }
                $output .= '</div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Organization Name</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Zip Code</th>
                    </tr>
                </thead>
                <tbody>';
                foreach ($organizations as $index => $organization) {
                    if ($index >= $limit) break;
                    $organization_name = get_user_meta($organization->ID, '_yith_wcaf_name_of_your_organization', true);
                    $city = get_user_meta($organization->ID, '_yith_wcaf_city', true);
                    $state = get_user_meta($organization->ID, '_yith_wcaf_state', true);
                    $code = get_user_meta($organization->ID, '_yith_wcaf_zipcode', true);

                    $output .= '<tr data-id="52" data-verify="0" data-group="0">';
                    $output .= '<td><div class="thead-data">Organization Name</div>' . esc_html($customer->display_name) . '</td>';
                    $output .= '<td><div class="thead-data">City</div>' . esc_html($city ?: 'N/A') . '</td>';
                    $output .= '<td><div class="thead-data">State</div>' . esc_html($state ?: 'N/A') . '</td>';
                    $output .= '<td><div class="thead-data">Zip Code</div>' . esc_html($code ?: 'N/A') . '</td>';
                    $output .= '</tr>';
                }
                $output .= '</tbody>
            </table>
        </div>';
        return $output;
    }
    
    
    
}
new OAM_SALES_REPRESENTATIVE_Shortcode();