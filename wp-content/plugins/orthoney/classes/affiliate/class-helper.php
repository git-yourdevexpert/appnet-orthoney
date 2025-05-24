<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_Helper {
    public function __construct() {}
    public static function init() {}
    public static function getRandomChars($string, $length = 3) {
        $string = str_replace(' ', '', $string); 
        $stringArray = str_split($string);
    
        if (strlen($string) < $length) {
            return strtoupper($string); 
        }
    
        shuffle($stringArray); 
        return strtoupper(implode('', array_slice($stringArray, 0, $length))); 
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
                    'message'=> 'You will be able to access the Organization Area once your account is approved by the admin.',
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
        if (empty($details['orders'])) {
            return '';
        }
        $html = '';
        // rsort($details['orders']);
        $orders = $details['orders'];
        $current_url = OAM_Helper::$organization_dashboard_link . '/order-list/';
        $html .= '<div>
                    <div class="">
                        <div class="recipient-lists-block custom-table orthoney-datatable-warraper table-with-search-block" id="affiliate-orderlist-table">';
                           
        $html .=    '
                    <table >
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Billing Name</th>
                                <th>Number of Jars</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>';
        $count = 0;
        foreach ($orders as $order_id) {
            if (++$count > $limit) break;
            $order = wc_get_order($order_id);
            if (!$order) continue;
            $custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
            $quantity = 0;
            foreach ($order->get_items() as $item) {
                $quantity += (int) $item->get_quantity();
            }
            $html .= '<tr>
                        <td><div class="thead-data">Order ID</div>#' . esc_html($custom_order_id) . '</td>
                        <td><div class="thead-data">Billing Name</div>' . esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '</td>
                        <td><div class="thead-data">Number of Jars</div>' . esc_html($quantity) . '</td>
                        <td><div class="thead-data">Total</div>' . wc_price($order->get_total()) . '</td>
                        <td><div class="thead-data">Date</div>' . wc_format_datetime( $order->get_date_created() ). '</td>
                    </tr>';
        }
        if ($count === 0) {
            $html .= '<tr><td colspan="4" class="no-available-msg">Order not found!</td></tr>';
        }
        $html .=    '</tbody>
                    </table>
                </div>
            </div>
        </div>';
        return $html;
    }
    public static function affiliate_current_year_order_list($details, $limit = 9999999) {
        if (empty($details['current_year_orders_ids'])) {
            return '<p>Current year customer order is not found?</p>';
        }
        $html = '';
        //rsort($details['current_year_orders_ids']);
        $orders = $details['current_year_orders_ids'];
        $current_url = OAM_Helper::$organization_dashboard_link . '/order-list/';
        $html .= '<div>
                    <div class="">
                        <div class="recipient-lists-block custom-table orthoney-datatable-warraper table-with-search-block" id="affiliate-orderlist-table">';
                           
        $html .=    '
                    <table >
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Billing Name</th>
                                <th>Number of Jars</th>
                                <th>Total</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>';
        $count = 0;
        foreach ($orders as $order_id) {
            if (++$count > $limit) break;
            $order = wc_get_order($order_id);
            if (!$order) continue;
            $custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
            $quantity = 0;
            foreach ($order->get_items() as $item) {
                $quantity += (int) $item->get_quantity();
            }
            $html .= '<tr>
                        <td><div class="thead-data">Order ID</div>#' . esc_html($custom_order_id) . '</td>
                        <td><div class="thead-data">Billing Name</div>' . esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '</td>
                        <td><div class="thead-data">Number of Jars</div>' . esc_html($quantity) . '</td>
                        <td><div class="thead-data">Total</div>' . wc_price($order->get_total()) . '</td>
                        <td><div class="thead-data">Date</div>' . wc_format_datetime( $order->get_date_created() ). '</td>
                    </tr>';
        }
        if ($count === 0) {
            $html .= '<tr><td colspan="4" class="no-available-msg">Order not found!</td></tr>';
        }
        $html .=    '</tbody>
                    </table>
                </div>
            </div>
        </div>';
        return $html;
    }
    public static function affiliate_details($affiliate_id, $details){
        $affiliate = get_userdata($affiliate_id);
        
        if($details['token'] != ''){
            $html = '<div class="dashboard-heading block-row">
                    <div class="item">
                        <div class="row-block">
                            <h3 class="block-title">'.(!empty($affiliate->_orgName) ? esc_html($affiliate->_orgName) : esc_html($affiliate->display_name)).'</h3>
                            '. ($details['token'] ? '<div> <strong>Token: '.$details['token'].'</strong></div> ' : '').'
                        </div>
                    </div>
                </div>
                <div class="block-row three-block-col">
                    
                    
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Total Customer Orders</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/jar-icon.png" width="30"  height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div>Total Orders : '.(!empty($details['orders']) ? count( $details['orders']) : '0').'</div>
                            <div> Current Year Orders: '.(!empty($details['current_year_orders_ids']) ? count( $details['current_year_orders_ids']) : '0').'</div>
                        </div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Total Sold Jar</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/jar-icon.png" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                        <div>Total Jars: '.(!empty($details['total_quantity']) ? esc_html($details['total_quantity']) : '0').'</div>
                        <div>Current Year Jars: '.(!empty($details['current_year_total_quantity']) ? esc_html($details['current_year_total_quantity']) : '0').'</div>
                        </div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Claim Commission</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="'.OH_PLUGIN_DIR_URL.'/assets/image/commission-icon.png" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div>
                            <p>'.(($details['total_quantity'] > 50)  ?  '<a href="#" class="w-btn us-btn-style_1">View all</a>': 'A minimum of 50 jars is required. You still need '.(50 - $details['total_quantity']).' more jars.').'</p>
                            
                            </div>
                        </div>
                    </div>
                </div>';
            }else{
                $html = '<div class="dashboard-heading block-row">
                    <div class="item">
                        <div class="row-block">
                        <div>
                            <h3 class="block-title" style="margin-bottom: 15px;">You do not have an organization account.</h3>
                            <p>If you need to create an organization account, please contact the Honey from the Heart team.</p>
                            </div>
                        </div>
                    </div>
                </div>';
            }
            return  $html;
    }
    public static function get_affiliate_details($affiliate_id) {
        global $wpdb;
        
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
        
        // Initialize default values
        $data = [
            'token' => '',
            'user_id' => $affiliate_id,
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
            $data['token'] = $affiliate->token;
            $data['user_id'] = $affiliate->user_id;
            $data['total_earnings'] = $affiliate->earnings;
            $data['paid'] = $affiliate->paid;
            $data['refunds'] = $affiliate->refunds;
            $data['total_quantity'] = 0;
            $affiliate_id = $affiliate->ID;
    
            // Get active balance and order IDs
            $results = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    SUM(c.amount) AS total_amount,
                    GROUP_CONCAT( DISTINCT c.order_id ORDER BY o.date_created_gmt DESC) AS order_ids,
                    SUM(q.total_qty) AS total_quantity
                FROM {$wpdb->prefix}yith_wcaf_commissions c
                INNER JOIN {$wpdb->prefix}wc_orders o ON c.order_id = o.id
                LEFT JOIN (
                    SELECT 
                        oi.order_id,
                        SUM(CAST(om.meta_value AS UNSIGNED)) AS total_qty
                    FROM {$wpdb->prefix}woocommerce_order_items oi
                    INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om 
                        ON oi.order_item_id = om.order_item_id 
                        AND om.meta_key = '_qty'
                    WHERE oi.order_item_type = 'line_item'
                    GROUP BY oi.order_id
                ) q ON q.order_id = c.order_id
                WHERE c.affiliate_id = %d 
                AND o.parent_order_id = 0
                GROUP BY c.affiliate_id",
                $affiliate_id
            ));


            $current_year_results = $wpdb->get_row( $wpdb->prepare(
                "SELECT 
                    SUM(DISTINCT c.amount) AS total_amount,
                    GROUP_CONCAT(DISTINCT c.order_id ORDER BY o.date_created_gmt DESC) AS order_ids,
                    SUM(CAST(om.meta_value AS UNSIGNED)) AS total_quantity
                FROM {$wpdb->prefix}yith_wcaf_commissions c
                INNER JOIN {$wpdb->prefix}wc_orders o ON c.order_id = o.id
                INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.id = oi.order_id AND oi.order_item_type = 'line_item'
                INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON oi.order_item_id = om.order_item_id AND om.meta_key = '_qty'
                WHERE c.affiliate_id = %d 
                AND YEAR(o.date_created_gmt) = YEAR(CURDATE())",
                $affiliate_id
            ));
                
            if ($results) {
                $data['active_balance'] = $results->total_amount ?: 0;
                $data['orders'] = $results->order_ids ? explode(',', $results->order_ids) : [];
                $data['current_year_orders_ids'] = $current_year_results->order_ids ? explode(',', $current_year_results->order_ids) : [];
                $data['total_quantity'] = isset($results->total_quantity)  ?  $results->total_quantity : 0;
                $data['current_year_total_quantity'] = isset($current_year_results->total_quantity)  ?  $current_year_results->total_quantity : 0;
            }
        }
        
        return $data;
    }
    
}
// Initialize the class properly

new OAM_AFFILIATE_Helper();
OAM_AFFILIATE_Helper::init();