<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_Helper {
    public function __construct() {}

    public static function init() {
    }

    public static function affiliate_status_check($user_id) {
        global $wpdb;
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;


        // Get affiliate data from the database
        $affiliate = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $user_id)
        );

        // If no record found, user is not an affiliate
        if (!$affiliate) {
            return json_encode(['success' => false, 'message'=> 'You are not registered as an affiliate.']);
        }


        if (isset($affiliate->banned) && $affiliate->banned == 1) {
            $ban_message = get_user_meta($user_id, '_yith_wcaf_ban_message', true);
            return json_encode([
                'success' => false, 
                'message'=> 'You are Banned please contact to the admin.',
                'reason'=> $ban_message
            ]);
            
        } elseif (isset($affiliate->enabled)) {
            if ($affiliate->enabled == 0) {
                
                return json_encode([
                    'success' => false, 
                    'message'=> 'You has been pending Approval.',
                    'reason'=> ''
                ]);

            } elseif ($affiliate->enabled == -1) {
                $reject_message = get_user_meta($user_id, '_yith_wcaf_reject_message', true);
                return json_encode([
                    'success' => false, 
                    'message'=> 'You account has been Rejected.',
                    'reason'=> $reject_message
                ]);
            }
        }
        
        return json_encode([
            'success' => true, 
            'message'=> '',
            'reason'=> ''
        ]);
       
    }

    public static function manage_user_popup(){
        ?>
        <div id="user-manage-popup" class="lity-hide black-mask full-popup" style="background: white;">
            <h2>User Details</h2>
            <?php 
            echo self::get_user_affiliate_form();
            ?>
        </div>
        <?php
    }
    
    public static function get_user_affiliate_form() {
        ob_start(); ?>
        <div id="edit-user-form" class="edit-affiliate-form">
            <form method="POST" id="addUserForm">
                <input type="hidden" id="user_id" name="user_id" required />
                <label>First Name</label>
                <input type="text" id="first_name" name="first_name" required data-error-message="Please enter a First Name." />
                <span class="error-message"></span>
                <label>Last Name</label>
                <input type="text" id="last_name" name="last_name" required data-error-message="Please enter a Last Name." />
                <span class="error-message"></span>
                <label>Email</label>
                <input type="email" id="email" name="email" required data-error-message="Please enter a valid Email." />
                <span class="error-message"></span>
                <label>Phone Number</label>
                <input type="text" id="phone" name="phone" class="phone-input" required data-error-message="Please enter a Phone Number." />
                <span class="error-message"></span>
                <label>Type</label>
                <select name="type" id="affiliate_type" required data-error-message="Please select a Type.">
                    <option value="">Select a Type</option>
                    <option value="primary-contact">Primary Contact</option>
                    <option value="co-chair">Co-Chair</option>
                    <option value="alternative-contact">Alternative Contact</option>
                </select>
                <span class="error-message"></span>
                <button class="add-user us-btn-style_1" type="submit">Save</button>
            </form>
            <div id="user-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function affiliate_dashboard_navbar($user_roles = array()){

        $output = '';
        if ( in_array( 'yith_affiliate', $user_roles) OR  in_array( 'affiliate_team_member', $user_roles)) {
            $output = '<div class="affiliate-dashboard">';
            $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard')) . '">Dashboard</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/my-profile/')) . '">My Profile</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/order-list/')) . '">Order List</a></div>';
            if ( ! in_array( 'affiliate_team_member', $user_roles)) {
                $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/change-admin/')) . '">Change Admin</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/user-list/')) . '">User List</a></div>';
            }
            $output .= '<div class="btn"><a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a></div>';
            $output .= '</div>';
        return $output;
        }
    }
       
    public static function affiliate_order_list($details, $limit = 5){
        $html = '';
        $html .= "<h4>Order List</h4>";
        $count = 0;
    
        if(!empty($details['orders'])){
            $html .= "<table><thead><tr><th>Order ID</th><th>Date</th></tr></thead><tbody>";
            foreach ($details['orders'] as $order) {
                $count = $count + 1;
                if($count <= $limit){
                    $html .= "<tr><td>#{$order->order_id}</td><td>{$order->date_created_gmt}</td></tr>";
                }
            }
            $html .= "</tbody></table>";
        }
        if($count >  $limit){
            $html .= '<div class="btn"><a href="' . esc_url(site_url('/affiliate-dashboard/order-list/')) . '">View All Orders</a></div>';
        }

        return  $html;
    }
    
    public static function affiliate_details($affiliate_id, $details){
        $affiliate = get_userdata($affiliate_id);

        $html = '';
        $html .= "<h3>Affiliate: " . (!empty($affiliate->display_name) ? esc_html($affiliate->display_name) : 'N/A') . "</h3>";
        $html .= "<p><strong>Total Earnings:</strong> $" . (!empty($details['total_earnings']) ? esc_html($details['total_earnings']) : '0.00') . "</p>";
        $html .= "<p><strong>Paid:</strong> $" . (!empty($details['paid']) ? esc_html($details['paid']) : '0.00') . "</p>";
        $html .= "<p><strong>Refunds:</strong> $" . (!empty($details['refunds']) ? esc_html($details['refunds']) : '0.00') . "</p>";
        $html .= "<p><strong>Active Balance:</strong> $" . (!empty($details['active_balance']) ? esc_html($details['active_balance']) : '0.00') . "</p>";

        
        return  $html;
    }

    public static function get_affiliate_details($affiliate_id) {
        global $wpdb;
    
        // Get total earnings, paid, refunds, and balance
        $total_earnings = get_user_meta($affiliate_id, 'yith_total_commissions', true);
        $paid = get_user_meta($affiliate_id, 'yith_paid_commissions', true);
        $refunds = get_user_meta($affiliate_id, 'yith_refunded_commissions', true);
        $active_balance = get_user_meta($affiliate_id, 'yith_commission_balance', true);
    
        // Fetch orders for the affiliate
        $orders = $wpdb->get_results(
            $wpdb->prepare("
                SELECT p.ID, p.post_date, pm.meta_value as affiliate_id
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'shop_order' 
                AND pm.meta_key = '_yith_affiliate_id'
                AND pm.meta_value = %d
            ", $affiliate_id)
        );
    
        return [
            'total_earnings'  => $total_earnings,
            'paid'            => $paid,
            'refunds'         => $refunds,
            'active_balance'  => $active_balance,
            'orders'          => $orders,
        ];
    }
    
}

// Initialize the class properly
new OAM_AFFILIATE_Helper();
OAM_AFFILIATE_Helper::init();