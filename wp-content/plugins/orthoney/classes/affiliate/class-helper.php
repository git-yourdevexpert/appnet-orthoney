<?php

// Prevent direct access

if (!defined('ABSPATH')) {

    exit;

}



class OAM_AFFILIATE_Helper {

    public function __construct() {}



    public static function init() {

    }



    public static function getRandomChars($string, $length = 3) {
        $string = str_replace(' ', '', $string); // Remove spaces
        $stringArray = str_split($string);
    
        if (strlen($string) < $length) {
            return strtoupper($string); // Return as is if it's too short
        }
    
        shuffle($stringArray); // Shuffle characters randomly
        return strtoupper(implode('', array_slice($stringArray, 0, $length))); // Take first 3 shuffled chars
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
      

        <div id="user-manage-popup" class="lity-hide black-mask full-popup popup-show">
            <h3>Team Member Details</h3>
            <?php 
            echo self::get_user_affiliate_form();
            ?>
        </div>

        <?php

    }

    

    public static function get_user_affiliate_form() {

        ob_start(); ?>

        <div id="edit-user-form" class="edit-affiliate-form woocommerce">
            <form method="POST" class="grid-two-col" id="addUserForm">
                <input type="hidden" id="user_id" name="user_id" required />
                <div class="form-row gfield--width-half">
                    <label for="first_name"> First Name</label>
                    <input type="text" id="first_name" name="first_name" required data-error-message="Please enter a First Name." />
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required data-error-message="Please enter a Last Name." />
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-full">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required data-error-message="Please enter a valid Email." />
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-full">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="phone-input" required data-error-message="Please enter a Phone Number." />
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-full">
                <label for="affiliate_type">Type</label>
                    <select name="type" id="affiliate_type" required data-error-message="Please select a Type.">
                        <option value="">Select a Type</option>
                        <option value="primary-contact">Primary Contact</option>
                        <option value="co-chair">Co-Chair</option>
                        <option value="alternative-contact">Alternative Contact</option>
                    </select>
                    <span class="error-message"></span>
                </div>
                <div class="footer-btn gfield--width-full">
                    <button class="add-user us-btn-style_1" type="submit">Save</button>
                </div>
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
            $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK) . '">Dashboard</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK.'/my-profile/') . '">My Profile</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK.'/order-list/') . '">Order List</a></div>';

            if ( ! in_array( 'affiliate_team_member', $user_roles)) {

                $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK.'/change-admin/') . '">Change Admin</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK.'/link-customer/') . '">Link Customer</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK.'/user-list/') . '">User List</a></div>';

            }

            $output .= '<div class="btn"><a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a></div>';
            $output .= '</div>';

        return '';

        }

    }

       
    public static function affiliate_order_list($details, $limit = 9999999) {
        $html = '';
        $current_url = home_url($_SERVER['REQUEST_URI']) . '/order-list/';
    
        if (!empty($details['orders'])) {
            $orders = $details['orders'];
            $html .= '<div class="recent-commissions">
                        <div class="dashboard-card">
                            <div class="recipient-lists-block custom-table">
                                <div class="row-block">
                                    <h4>Recent Commissions</h4>';
            
            if ($limit != 9999999) {
                $html .= '<div class="see-all"><a href="' . esc_url($current_url) . '" class="w-btn us-btn-style_1">View all</a></div>';
            }
            
            $html .= '</div>
                      <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Status</th>
                                <th>Customer Name</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>';
    
            $count = 0;
    
            foreach ($orders as $order_id) {
                $order = wc_get_order($order_id);
                if (!$order) continue;
    
                $count++;
                if ($count <= $limit) {
                    $html .= "<tr>
                                <td><div class='thead-data'>Order ID</div>#{$order->get_id()}</td>
                                <td><div class='thead-data'>Status</div>{$order->get_status()}</td>
                                <td><div class='thead-data'>Customer Name</div>{$order->get_billing_first_name()} {$order->get_billing_last_name()}</td>
                                <td><div class='thead-data'>Total</div>" . wc_price($order->get_total()) . "</td>
                              </tr>";
                }
            }
    
            if ($count == 0) {
                $html .= "<tr><td colspan='4'>Order not found!</td></tr>";
            }
    
            $html .= '</tbody></table>
                      </div>
                    </div>
                  </div>';
        }
    
        return $html;
    }
    
    
    

    public static function affiliate_details($affiliate_id, $details){

        $affiliate = get_userdata($affiliate_id);

        $html = '<div class="dashboard-heading block-row">
                    <div class="item">
                        <div class="row-block">
                            <h3 class="block-title">'.(!empty($affiliate->display_name) ? esc_html($affiliate->display_name) : 'N/A').'</h3>
                        </div>
                    </div>
                </div>
                <div class="block-row three-block-col">
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Total Earning</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/speedicon.svg" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">$'.(!empty($details['total_earnings']) ? esc_html($details['total_earnings']) : '0.00').'</div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Paid</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/speedicon.svg" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">$'.(!empty($details['paid']) ? esc_html($details['paid']) : '0.00').'</div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Refunds</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/speedicon.svg" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">$'.(!empty($details['refunds']) ? esc_html($details['refunds']) : '0.00').'</div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Active Balance</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/speedicon.svg" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">$'.(!empty($details['refunds']) ? esc_html($details['refunds']) : '0.00').'</div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Total Orders</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/speedicon.svg" width="30"  height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">'.(!empty($details['orders']) ? count( $details['orders']) : '0').'</div>
                    </div>
                </div>';


                return  $html;

    }



    public static function get_affiliate_details($affiliate_id) {
        global $wpdb;
    
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
        
        // Initialize default values
        $data = [
            'total_earnings' => 0,
            'paid'           => 0,
            'refunds'        => 0,
            'active_balance' => 0,
            'orders'         => [],
        ];
    
        // Get affiliate data from the database
        $affiliate = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $affiliate_id)
        );
    
        if ($affiliate) {
            $data['total_earnings'] = $affiliate->earnings;
            $data['paid'] = $affiliate->paid;
            $data['refunds'] = $affiliate->refunds;
            $affiliate_id = $affiliate->ID;
    
            // Get active balance and order IDs
            $results = $wpdb->get_row($wpdb->prepare(
                "SELECT SUM(c.amount) AS total_amount, GROUP_CONCAT(c.order_id) AS order_ids 
                 FROM {$wpdb->prefix}yith_wcaf_commissions c
                 INNER JOIN {$wpdb->prefix}wc_orders o ON c.order_id = o.id
                 WHERE c.affiliate_id = %d AND o.parent_order_id = 0",
                $affiliate_id
            ));
    
            if ($results) {
                $data['active_balance'] = $results->total_amount ?: 0;
                $data['orders'] = $results->order_ids ? explode(',', $results->order_ids) : [];
            }
        }
        
        return $data;
    }
    

}



// Initialize the class properly

new OAM_AFFILIATE_Helper();

OAM_AFFILIATE_Helper::init();