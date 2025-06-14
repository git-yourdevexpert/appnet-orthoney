<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_Helper
{
    public function __construct() {}
    public static function init() {}
    public static function getRandomChars($string, $length = 3)
    {
        $string = str_replace(' ', '', $string);
        $stringArray = str_split($string);

        if (strlen($string) < $length) {
            return strtoupper($string);
        }

        shuffle($stringArray);
        return strtoupper(implode('', array_slice($stringArray, 0, $length)));
    }

    public static function affiliate_status_check($user_id)
    {
        if ($user_id == '') {
            return json_encode([
                'success' => false,
                'message' => 'Something went wrong. Please contact the Honey From The Heart team.',
                'reason' => $ban_message
            ]);
        }

        global $wpdb;
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
        // Get affiliate data from the database
        $affiliate = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $user_id)
        );
        // If no record found, user is not an affiliate
        if (!$affiliate) {
            return json_encode(['success' => false, 'message' => 'You are not registered as an affiliate.']);
        }
        if (isset($affiliate->banned) && $affiliate->banned == 1) {
            $ban_message = get_user_meta($user_id, '_yith_wcaf_ban_message', true);
            return json_encode([
                'success' => false,
                'message' => 'You are Banned please contact to the admin.',
                'reason' => $ban_message
            ]);
        } elseif (isset($affiliate->enabled)) {

            if ($affiliate->enabled == 0) {
?>
                <style>
                    .l-subheader.at_middle .hidden_for_tablets,
                    .login-container>a,
                    .top-bar-menu .customer-dashboard,
                    .l-subheader.at_bottom {
                        display: none;
                    }
                </style>
        <?php
                return json_encode([
                    'success' => false,
                    'message' => 'Your application is pending approval. Thank you for reaching out!',
                    'reason' => ''
                ]);
            } elseif ($affiliate->enabled == -1) {
                $reject_message = get_user_meta($user_id, '_yith_wcaf_reject_message', true);
                return json_encode([
                    'success' => false,
                    'message' => 'You account has been Rejected.',
                    'reason' => $reject_message
                ]);
            }
        }
        return json_encode([
            'success' => true,
            'message' => '',
            'reason' => ''
        ]);
    }
    public static function manage_user_popup()
    {
        ?>

        <div id="user-manage-popup" class="lity-hide black-mask full-popup popup-show">
            <h3>Team Member Details</h3>
            <?php
            echo self::get_user_affiliate_form();
            ?>
        </div>
    <?php
    }

    public static function get_user_affiliate_form()
    {
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
    public static function affiliate_dashboard_navbar($user_roles = array())
    {
        $output = '';
        if (in_array('yith_affiliate', $user_roles) or  in_array('affiliate_team_member', $user_roles)) {
            $output = '<div class="affiliate-dashboard">';
            $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK) . '">Dashboard</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK . '/my-profile/') . '">My Profile</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK . '/orders-list/') . '">Orders List</a></div>';
            if (! in_array('affiliate_team_member', $user_roles)) {
                $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK . '/change-admin/') . '">Change Admin</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK . '/link-customer/') . '">Link Customer</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ORGANIZATION_DASHBOARD_LINK . '/users-list/') . '">Users List</a></div>';
            }
            $output .= '<div class="btn"><a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a></div>';
            $output .= '</div>';
            return '';
        }
    }

    public static function affiliate_order_list($details, $limit = 9999999)
    {
        if (empty($details['orders'])) {
            return '';
        }
       
        $total_commission = 0;
        $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate();

        $exclude_coupon = EXCLUDE_COUPON;
        $total_all_quantity = 0;
        $total_orders = 0;
        $fundraising_qty = 0;
        $wholesale_qty = 0;
        $wholesale_order = 0;
        $fundraising_orders = 0;
        $unit_price = 0;
        $unit_cost = 0;
        $dist_unit_profit = 0;
        foreach ($commission_array as $key => $data) {
            if ($data['affiliate_account_status'] == 1) {
                $unit_price = $data['par_jar'];
                $unit_cost = $data['minimum_price'];
                 $total_all_quantity += $data['total_quantity'];
                 $total_orders++;
                $common = [];
                $coupon_array = !empty($data['is_voucher_used']) ? explode(",", $data['is_voucher_used']) : [];
                if(count($coupon_array) > 0){
                    $coupon_array = array_diff($coupon_array, $exclude_coupon);
                    $coupon_array = array_values($coupon_array);
                }
                if(count($coupon_array) == 0){
                    $total_commission += $data['commission'];
                }else{
                    $wholesale_qty += $data['total_quantity'];
                     $wholesale_order++;
                }
            }
        }
        $fundraising_qty = $total_all_quantity - $wholesale_qty;
        $fundraising_orders = $total_orders - $wholesale_order;

        $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;
        if ($unit_price >= $selling_minimum_price) {
            if (OAM_AFFILIATE_Helper::is_user_created_this_year(get_current_user_id())) {
                if ($total_all_quantity < 99) {
                    $dist_unit_profit = get_field('new_minimum_price_50', 'option');
                } else {
                    $dist_unit_profit = get_field('new_minimum_price_100', 'option');
                }
            } else {
                if ($total_all_quantity < 99) {
                    $dist_unit_profit = get_field('ex_minimum_price_50', 'option');
                } else {
                    $dist_unit_profit = get_field('ex_minimum_price_100', 'option');
                }
            }
        }
        
        $html = '';
        // rsort($details['orders']);
        $orders = $details['orders'];
        $current_url = OAM_Helper::$organization_dashboard_link . '/orders-list/';
        $html .= '<div>
                    <div class="">
                        <div class="recipient-lists-block custom-table orthoney-datatable-warraper table-with-search-block" id="affiliate-orderlist-table">';

        $html .=    '
                    <table >
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Billing Name</th>
                                <th>Qty</th>
                                <th>Type</th>
                                <th>Fundrs total<br><small>(Fundraising Qty * Dist Unit Price )</small></th>
                                <th>Distributor Sales Profit<br><small>(Fundraising Qty * Dist Unit Profit )</small></th>
                                <th>Order Date</th>
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

            $exclude_coupon = EXCLUDE_COUPON;

            if ($commission_array[$custom_order_id]['affiliate_account_status'] == 1) {
                $coupon_array = !empty($commission_array[$custom_order_id]['is_voucher_used']) 
                    ? explode(",", $commission_array[$custom_order_id]['is_voucher_used']) 
                    : [];

                $coupon_array = array_diff($coupon_array, $exclude_coupon);

                if (empty($coupon_array)) {
                    $commission_price = wc_price($quantity * ($unit_price - $dist_unit_profit));
                } else {
                    $commission_price = wc_price(0) . ' <span style="color:red">(used voucher: '.implode(",", $coupon_array).')</span>';
                }
            } else {
                $commission_price = wc_price(0) . ' <span style="color:red">(Account deactivated)</span>';
            }
            
            if ($commission_array[$custom_order_id]['affiliate_account_status'] == 1) {
            $html .= '<tr>
                        <td><div class="thead-data">Order ID</div>#' . esc_html($custom_order_id) . '</td>
                        <td><div class="thead-data">Billing Name</div>' . esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '</td>
                        <td><div class="thead-data">Qty</div>' . esc_html($quantity) . '</td>
                        <td><div class="thead-data">Type</div>' . esc_html('Wholesale') . '</td>
                        <td><div class="thead-data">Fundrs total<br><small>(Fundraising Qty * Dist Unit Price )</small></div>' . wc_price($order->get_total()) . '<br><small>('.$quantity.' * '.wc_price($unit_price).')</small></td>
                        
                        <td><div class="thead-data">Distributor Sales Profit<br><small>(Fundraising Qty * Dist Unit Profit )</small></div>' . $commission_price . '<br><small>('.$quantity.' * '.wc_price(($unit_price - $dist_unit_profit)).')</small></td>
                        <td><div class="thead-data">Date</div>' . date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($order->get_date_created())) . '</td>
                    </tr>';
            }
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



    public static function affiliate_current_year_order_list($details, $limit = 9999999)
    {
        if (empty($details['current_year_orders_ids'])) {
            return '<p>Current year customer order is not found?</p>';
        }

        $total_commission = 0;
        $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate();

        $exclude_coupon = EXCLUDE_COUPON;
        $total_all_quantity = 0;
        $total_orders = 0;
        $fundraising_qty = 0;
        $wholesale_qty = 0;
        $wholesale_order = 0;
        $fundraising_orders = 0;
        $unit_price = 0;
        $unit_cost = 0;
        $dist_unit_profit  = 0;
        foreach ($commission_array as $key => $data) {
            if ($data['affiliate_account_status'] == 1) {
                $unit_price = $data['par_jar'];
                $unit_cost = $data['minimum_price'];
                 $total_all_quantity += $data['total_quantity'];
                 $total_orders++;
                $common = [];
                $coupon_array = !empty($data['is_voucher_used']) ? explode(",", $data['is_voucher_used']) : [];
                if(count($coupon_array) > 0){
                    $coupon_array = array_diff($coupon_array, $exclude_coupon);
                    $coupon_array = array_values($coupon_array);
                }
                if(count($coupon_array) == 0){
                    $total_commission += $data['commission'];
                }else{
                    $wholesale_qty += $data['total_quantity'];
                     $wholesale_order++;
                }
            }
        }
        $fundraising_qty = $total_all_quantity - $wholesale_qty;
        $fundraising_orders = $total_orders - $wholesale_order;

        $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;
        if ($unit_price >= $selling_minimum_price) {
            if (OAM_AFFILIATE_Helper::is_user_created_this_year(get_current_user_id())) {
                if ($total_all_quantity < 99) {
                    $dist_unit_profit = get_field('new_minimum_price_50', 'option');
                } else {
                    $dist_unit_profit = get_field('new_minimum_price_100', 'option');
                }
            } else {
                if ($total_all_quantity < 99) {
                    $dist_unit_profit = get_field('ex_minimum_price_50', 'option');
                } else {
                    $dist_unit_profit = get_field('ex_minimum_price_100', 'option');
                }
            }
        }
        $html = '';
        $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate();



        //rsort($details['current_year_orders_ids']);
        $orders = $details['current_year_orders_ids'];
        $current_url = OAM_Helper::$organization_dashboard_link . '/orders-list/';
        $html .= '<div><style>#yearFilter{display:none}</style>
                    <div class="">
                        <div class="recipient-lists-block custom-table orthoney-datatable-warraper table-with-search-block" id="affiliate-orderlist-table">';

        $html .=  '<table >
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Billing Name</th>
                                <th>Qty</th>
                                <th>Type</th>
                                <th>Fundrs total<br><small>(Fundraising Qty * Dist Unit Price )</small></th>
                                <th>Distributor Sales Profit<br><small>(Fundraising Qty * Dist Unit Profit )</small></th>
                                <th>Order Date</th>
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

            $exclude_coupon = EXCLUDE_COUPON;

            if ($commission_array[$custom_order_id]['affiliate_account_status'] == 1) {
                $coupon_array = !empty($commission_array[$custom_order_id]['is_voucher_used']) 
                    ? explode(",", $commission_array[$custom_order_id]['is_voucher_used']) 
                    : [];

                $coupon_array = array_diff($coupon_array, $exclude_coupon);

                if (empty($coupon_array)) {
                    $commission_price = wc_price($quantity * ($unit_price - $dist_unit_profit));
                } else {
                    $commission_price = wc_price(0) . ' <span style="color:red">(used voucher: '.implode(",", $coupon_array).')</span>';
                }
            } else {
                $commission_price = wc_price(0) . ' <span style="color:red">(Account deactivated)</span>';
            }


            // $commission_price = '';
            // if ($commission_array[$custom_order_id]['affiliate_account_status'] == 1) {
            //     $commission_price = wc_price($commission_array[$custom_order_id]['commission']);
            // } else {
            //     $commission_price = wc_price(0) . ' <span style="color:red">(Account deactivated)</span>';
            // }

            if ($commission_array[$custom_order_id]['affiliate_account_status'] == 1) {
            $html .= '<tr>
                        <td><div class="thead-data">Order ID</div>#' . esc_html($custom_order_id) . '</td>
                        <td><div class="thead-data">Billing Name</div>' . esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()) . '</td>
                        <td><div class="thead-data">Qty</div>' . esc_html($quantity) . '</td>
                        <td><div class="thead-data">Type</div>' . esc_html('Wholesale') . '</td>
                        <td><div class="thead-data">Fundrs total<br><small>(Fundraising Qty * Dist Unit Price )</small></div>' . wc_price($order->get_total()) . '<br><small>('.$quantity.' * '.wc_price($unit_price).')</small></td>
                        
                        <td><div class="thead-data">CommDistributor Sales Profit<br><small>(Fundraising Qty * Dist Unit Profit )</small></div>' . $commission_price . '<br><small>('.$quantity.' * '.wc_price(($unit_price - $dist_unit_profit)).')</small></td>
                        <td><div class="thead-data">Date</div>' . date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($order->get_date_created())) . '</td>
                    </tr>';
            }
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


    public static function affiliate_details($affiliate_id, $details)
    {
        $affiliate = get_userdata($affiliate_id);
        $html = '';
        $total_commission = 0;
        $commission_array = OAM_AFFILIATE_Helper::get_commission_affiliate();

        $exclude_coupon = EXCLUDE_COUPON;
        $total_all_quantity = 0;
        $total_orders = 0;
        $fundraising_qty = 0;
        $wholesale_qty = 0;
        $wholesale_order = 0;
        $fundraising_orders = 0;
        $unit_price = 0;
        $unit_cost = 0;
        foreach ($commission_array as $key => $data) {
            $fundraising_qty = $data['total_quantity'];
            $wholesale_qty = $data['wholesale_qty'];
            if ($data['affiliate_account_status'] == 1) {
                $unit_price = $data['par_jar'];
                $unit_cost = $data['minimum_price'];
                 $total_all_quantity = $data['total_quantity'];
                 $total_orders++;
                $common = [];
                $coupon_array = !empty($data['is_voucher_used']) ? explode(",", $data['is_voucher_used']) : [];
                if(count($coupon_array) > 0){
                    $coupon_array = array_diff($coupon_array, $exclude_coupon);
                    $coupon_array = array_values($coupon_array);
                }
                if(count($coupon_array) == 0){
                    $total_commission += $data['commission'];
                }else{
                     $wholesale_order++;
                }
            }
        }
        $total_all_quantity = $fundraising_qty + $wholesale_qty;
         $fundraising_orders = $total_orders - $wholesale_order;
         
         $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;
            if ($unit_price >= $selling_minimum_price) {
            if (OAM_AFFILIATE_Helper::is_user_created_this_year(get_current_user_id())) {
                if ($total_all_quantity < 99) {
                    $unit_cost = get_field('new_minimum_price_50', 'option');
                } else {
                    $unit_cost = get_field('new_minimum_price_100', 'option');
                }
            } else {
                if ($total_all_quantity < 99) {
                    $unit_cost = get_field('ex_minimum_price_50', 'option');
                } else {
                    $unit_cost = get_field('ex_minimum_price_100', 'option');
                }
            }
        }

        $tax_id = get_user_meta($affiliate_id, '_yith_wcaf_tax_id', true);
        $activate_affiliate_account = get_user_meta($affiliate_id, 'activate_affiliate_account', true);

        if (empty($activate_affiliate_account) and $activate_affiliate_account != 1) {
            $html = '<div class="dashboard-heading block-row">
                        <div class="item" style="background-color: rgba(255, 0, 0, 0.5);">
                            <div class="row-block">
                                <h6 class="block-title">Your account is inactive. Submit your Tax ID to activate your account and become eligible for this year`s commission.</h6>
                                <div>';

            if (!empty($tax_id)) {
                $html .= '<button data-userid="' . esc_attr($affiliate_id) . '" class="w-btn us-btn-style_1 activate_affiliate_account">Activate Account</button>';
            } else {
                $html .= '<a href="' . ORGANIZATION_DASHBOARD_LINK . 'my-profile/" class="w-btn us-btn-style_1">Update Tax ID</a>';
            }

            $html .= '</div>
                            </div>
                        </div>
                    </div>';
        }

        if ($details['token'] != '') {
            $html .= '
                

                <div class="dashboard-heading block-row">
                    <div class="item">
                        <div class="row-block">
                            <h3 class="block-title">' . (!empty($affiliate->_orgName) ? esc_html($affiliate->_orgName) : esc_html($affiliate->display_name)) . '</h3>
                            ' . ($details['token'] ? '<div> <strong>Token: ' . $details['token'] . '</strong></div> ' : '') . '
                        </div>
                    </div>
                </div>
                '. ( ($details['total_quantity'] > 50)  ? '' : '<div class="dashboard-heading block-row"><div class="item" style="padding: 10px 20px;background-color: rgba(255, 0, 0, 0.5);"><div class="row-block"><p style="color: white;">A minimum of 50 jars is required. You still need ' . (50 - $details['total_quantity']) . ' more jars.</p style="color: white;"></div></div></div>' ) . '
                
                <div class="block-row three-block-col">
                    <div class="place-order item" style="display:none">
                        <div class="row-block">
                            <h4 class="block-title">Total Customer Orders</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="' . OH_PLUGIN_DIR_URL . '/assets/image/jar-icon.png" width="30"  height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div>Total Orders : ' . (!empty($details['orders']) ? count($details['orders']) : '0') . '</div>
                            <div> Current Year Orders: ' . (!empty($details['current_year_orders_ids']) ? count($details['current_year_orders_ids']) : '0') . '</div>
                        </div>
                    </div>
                    <div class="place-order item" style="display:none">
                        <div class="row-block">
                            <h4 class="block-title">Total Sold Jar</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="' . OH_PLUGIN_DIR_URL . '/assets/image/jar-icon.png" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                        <div>Total Jars: ' . (!empty($details['total_quantity']) ? esc_html($details['total_quantity']) : '0') . '</div>
                        <div>Current Year Jars: ' . (!empty($details['current_year_total_quantity']) ? esc_html($details['current_year_total_quantity']) : '0') . '</div>
                        </div>
                    </div>
                    <div class="place-order item" style="display:none">
                        <div class="row-block">
                            <h4 class="block-title">Total Commission</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="' . OH_PLUGIN_DIR_URL . '/assets/image/commission-icon.png" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div>' . wc_price($total_commission) . ' ' . (($details['total_quantity'] <= 50) ? '<p style="line-height: 1;"><small style="font-size: 70%; color: red;line-height: 1 !important;font-weight: 900;">You are not eligible for a commission. A minimum of 50 jars is required.</small></p>' : '') . '</div>
                        </div>
                    </div>
                    <style>
                    .commission-calc{
                        list-style: none;
    margin: 0;
    font-size: 16px;
    color: #000;
                    }
    .commission-calc li{
    display: flex;
    justify-content: space-between;
        }
    .commission-calc li.total{    border-top: 2px solid #ddd;
    padding-top: 10px;}
                    </style>
                     <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Total(Fundraising + Wholesale)</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="' . OH_PLUGIN_DIR_URL . '/assets/image/commission-icon.png" width="30"  height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div></div>
                            <div>
                                <ul class="commission-calc">
                                    <li><span>Total Orders </span><span>'.$total_orders.'</span></li>
                                    <li><span>Total Qty </span><span>'.$total_all_quantity.'</span></li>
                                    <li><span>Dist Unit Price </span><span>'.wc_price($unit_price).'</span></li>
                                    <li><span>Dist Unit Cost </span><span>'.wc_price($unit_cost).'</span></li>
                                    <li><span>Dist Unit Profit <div class="tooltip" data-tippy="(Dist Unit Price - Dist Unit Cost )"></div></span><span>'.wc_price($unit_price - $unit_cost).'</span></li>
                                    <li><span>Total Cost <div class="tooltip" data-tippy="(Fundrs Total + Wholesale Total )"></div> </span><span>'.wc_price(($fundraising_qty * $unit_price) + ($wholesale_qty * $unit_price)).'</span></li>
                                   
                                    <li><span>Dist Total Cost (ORT share) <div class="tooltip" data-tippy="(Total Qty * Dist Unit Cost )"></div></span><span>'.wc_price($total_all_quantity * $unit_cost).'</span></li>
                                    <li class="total"><span>Distributor Sales Profit <div class="tooltip" data-tippy="(Fundraising Qty * Dist Unit Profit )"></div> </span><span>'.(($details['total_quantity'] > 50) ? wc_price($fundraising_qty * ($unit_price - $unit_cost)) :  wc_price(0)).'</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Fundraising</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="' . OH_PLUGIN_DIR_URL . '/assets/image/commission-icon.png" width="30"  height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div></div>
                            <div>
                                <ul class="commission-calc">
                                    <li><span>Fundraising Orders </span><span>'.$fundraising_orders.'</span></li>
                                    <li><span>Fundraising Qty </span><span>'.$fundraising_qty.'</span></li>
                                    <li><span>Dist Unit Price </span><span>'.wc_price($unit_price).'</span></li>
                                    <li><span>Dist Unit Cost </span><span>'.wc_price($unit_cost).'</span></li>
                                    <li><span>Dist Unit Profit <div class="tooltip" data-tippy="(Dist Unit Price - Dist Unit Cost )"></div></span><span>'.wc_price($unit_price - $unit_cost).'</span></li>
                                     <li><span>Dist Total Cost (ORT share) <div class="tooltip" data-tippy="(Fundraising Qty * Dist Unit Price )"></div></span><span>'.wc_price($fundraising_qty * $unit_cost).'</span></li>
                                    <li ><span>Fundrs Total <div class="tooltip" data-tippy="(Fundraising Qty * Dist Unit Price )"></div></span><span>'.wc_price($fundraising_qty * $unit_price).'</span></li>
                                    <li  class="total"><span>Fundrs Profit <div class="tooltip" data-tippy="(Fundraising Qty * Dist Unit Profit )"></div></span><span>'.wc_price($fundraising_qty * ($unit_price - $unit_cost)).'</span></li>
                                    
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="place-order item">
                        <div class="row-block">
                            <h4 class="block-title">Wholesale</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="' . OH_PLUGIN_DIR_URL . '/assets/image/commission-icon.png" width="30"  height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div></div>
                            <div>
                                <ul class="commission-calc">
                                    <li><span>Total Orders </span><span>'.$wholesale_order.'</span></li>
                                    <li><span>Wholesale Qty </span><span>'.$wholesale_qty.'</span></li>
                                    <li><span>Dist Unit Price </span><span>'.wc_price($unit_price).'</span></li>
                                    <li><span>Dist Unit Cost </span><span>'.wc_price($unit_cost).'</span></li>
                                    <li><span>Dist Unit Profit <div class="tooltip" data-tippy="(Dist Unit Price - Dist Unit Cost )"></div></span><span>'.wc_price($unit_price - $unit_cost).'</span></li>
                                     <li><span>Dist Total Cost (ORT share) <div class="tooltip" data-tippy="(Wholesale Qty * Dist Unit Price )"></div></span><span>'.wc_price($wholesale_qty * $unit_cost).'</span></li>
                                     <li><span>Wholesale Total <div class="tooltip" data-tippy="(Wholesale Qty * Dist Unit Price )"></div></span><span>'.wc_price($wholesale_qty * $unit_price).'</span></li>
                                     <li class="total"><span>Wholesale Profit <div class="tooltip" data-tippy="(Wholesale Qty * Dist Unit Profit  )"></div></span><span>'.wc_price('00.00').'</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="place-order item" style="display:none">
                        <div class="row-block">
                            <h4 class="block-title">Claim Commission</h4>
                            <div class="see-all">
                                <div class="icon-card"><img alt="speedicon" src="' . OH_PLUGIN_DIR_URL . '/assets/image/commission-icon.png" width="30" height="30" /></div>
                            </div>
                        </div>
                        <div class="sub-heading">
                            <div>
                            <p>' . (($details['total_quantity'] > 50)  ?  '<button data-orgid="' . $affiliate_id . '"  class="w-btn us-btn-style_1 org_account_statement">Account Statement</button>' : 'A minimum of 50 jars is required. You still need ' . (50 - $details['total_quantity']) . ' more jars.') . '</p>
                            
                            </div>
                        </div>
                    </div>
                </div>';
        } else {
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

    public static function is_user_created_this_year($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user || empty($user->user_registered)) {
            return false;
        }

        $registered_year = (int) date('Y', strtotime($user->user_registered));
        $current_year = (int) date('Y');

        return $registered_year === $current_year;
    }

    public static function get_commission_affiliate($affiliate_id_attr = '')
    {
        global $wpdb;
        $activate_affiliate_account = 0;

        $commission_array = [];
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;

        // Get current user ID
        $current_user_id = get_current_user_id();
        $affiliate_id = $current_user_id;

        // Determine affiliate ID based on role if not provided
        if ($affiliate_id_attr == '') {

            $activate_affiliate_account = get_user_meta($current_user_id, 'activate_affiliate_account', true);
            // echo '$affiliate_id_attr'. $affiliate_id_attr;
            $user_roles = OAM_COMMON_Custom::get_user_role_by_id($current_user_id);

            if (array_intersect($user_roles, ['yith_affiliate', 'affiliate_team_member', 'administrator'])) {
                $associated_id = get_user_meta($current_user_id, 'associated_affiliate_id', true);
                if (!empty($associated_id)) {
                    $affiliate_id = $associated_id;
                }
            }

            // Fetch affiliate record
            $affiliate = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d",
                    $affiliate_id
                )
            );
            if ($affiliate) {
                $affiliate_id = $affiliate->ID;
            }
        } else {
            // Fetch affiliate record
            $affiliate = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d",
                    $affiliate_id_attr
                )
            );
            if ($affiliate) {
                $affiliate_id = $affiliate->ID;
                $current_user_id = $affiliate_id_attr;
            }
            $activate_affiliate_account = get_user_meta($affiliate_id_attr, 'activate_affiliate_account', true);
        }

        $commission_year_results = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                c.order_id,
                SUM(CAST(qty_meta.meta_value AS UNSIGNED)) AS total_quantity,
                SUM(CAST(line_total_meta.meta_value AS DECIMAL(10,2))) AS line_total,
                SUM(CAST(line_subtotal_meta.meta_value AS DECIMAL(10,2))) AS line_subtotal
            FROM {$wpdb->prefix}yith_wcaf_commissions c
            INNER JOIN {$wpdb->prefix}wc_orders o ON c.order_id = o.id
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = o.id AND oi.order_item_type = 'line_item'
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta qty_meta ON qty_meta.order_item_id = oi.order_item_id AND qty_meta.meta_key = '_qty'
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta line_total_meta ON line_total_meta.order_item_id = oi.order_item_id AND line_total_meta.meta_key = '_line_total'
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta line_subtotal_meta ON line_subtotal_meta.order_item_id = oi.order_item_id AND line_subtotal_meta.meta_key = '_line_subtotal'
            WHERE c.affiliate_id = %d
            AND YEAR(o.date_created_gmt) = YEAR(CURDATE())
            GROUP BY c.order_id",
            $affiliate_id
        ));

        $total_quantity = $wpdb->get_var($wpdb->prepare(
            "SELECT 
                SUM(CAST(om.meta_value AS UNSIGNED))
            FROM {$wpdb->prefix}yith_wcaf_commissions c
            INNER JOIN {$wpdb->prefix}wc_orders o ON c.order_id = o.id
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.id = oi.order_id AND oi.order_item_type = 'line_item'
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om ON oi.order_item_id = om.order_item_id AND om.meta_key = '_qty'
            WHERE c.affiliate_id = %d
            AND YEAR(o.date_created_gmt) = YEAR(CURDATE())",
            $affiliate_id
        ));

         



        $total_exclude_quantity = $wpdb->get_var($wpdb->prepare(
            "SELECT 
                SUM(CAST(om.meta_value AS UNSIGNED)) AS total_qty
            FROM {$wpdb->prefix}yith_wcaf_commissions c
            INNER JOIN {$wpdb->prefix}wc_orders o ON c.order_id = o.id
            INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON o.id = oi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta om  ON oi.order_item_id = om.order_item_id
            INNER JOIN {$wpdb->prefix}wc_orders_meta wm ON wm.order_id = o.id
            WHERE c.affiliate_id = %d
                AND om.meta_key = '_qty'
                AND wm.meta_key = 'affiliate_account_status'
                AND wm.meta_value = '1'
                AND YEAR(o.date_created_gmt) = YEAR(CURDATE())",
            $affiliate_id
        ));


        if (!empty($commission_year_results)) {
            // if ($total_quantity >= 50) {
                $total_all_quantity = 0;
                $wholesale_qty = 0;
               
                $exclude_coupon = EXCLUDE_COUPON;
                foreach ($commission_year_results as $key => $value) {
                    $affiliate_account_status =  OAM_COMMON_Custom::get_order_meta($value->order_id, 'affiliate_account_status') ?: 0;
                    if($affiliate_account_status == 1){
                        $common = [];
                        $coupon_data = OAM_AFFILIATE_Helper::get_applied_coupon_codes_from_order($value->order_id);
                        $coupon_array = !empty($coupon_data) ? explode(",", $coupon_data) : [];
                        if(count($coupon_array) > 0){
                            $coupon_array = array_diff($coupon_array, $exclude_coupon);
                            $coupon_array = array_values($coupon_array);
                        }

                        if(count($coupon_array) == 0){
                           $total_all_quantity += $value->total_quantity;
                        }else{
                            $wholesale_qty += $value->total_quantity;
                        }
                    }
                }

                // echo $total_all_quantity;
                // echo "<br>";
                // echo $total_all_quantity - $wholesale_qty;
                // echo "<br>";

                foreach ($commission_year_results as $key => $commission) {
                   
                 
                    $total_exclude_quantity = $total_exclude_quantity ?: 0;
                    $total_quantity = $total_quantity;
                    $line_total = $commission->line_total;
                    $line_subtotal = $commission->line_subtotal;
                    
                    $par_jar = $commission->line_subtotal / $commission->total_quantity;
                    $minimum_price = 0;

                    $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;
                    if ($par_jar >= $selling_minimum_price) {
                        if (OAM_AFFILIATE_Helper::is_user_created_this_year($current_user_id)) {
                            if ($total_all_quantity < 99) {
                                $minimum_price = get_field('new_minimum_price_50', 'option');
                            } else {
                                $minimum_price = get_field('new_minimum_price_100', 'option');
                            }
                        } else {
                            if ($total_all_quantity < 99) {
                                $minimum_price = get_field('ex_minimum_price_50', 'option');
                            } else {
                                $minimum_price = get_field('ex_minimum_price_100', 'option');
                            }
                        }
                    }

                    $affiliate_account_status =  OAM_COMMON_Custom::get_order_meta($commission->order_id, 'affiliate_account_status') ?: 0;
                    $custom_order_id = OAM_COMMON_Custom::get_order_meta($commission->order_id, '_orthoney_OrderID');

                    $data['total_exclude_quantity'] = $total_exclude_quantity;
                    $data['total_all_quantity'] = $total_quantity;
                    $data['wholesale_qty'] = $wholesale_qty;
                    $data['order_id'] = $commission->order_id;
                    $data['custom_order_id'] = $custom_order_id;
                    $data['total_quantity'] =  $total_all_quantity;
                    $data['line_total'] = $line_total;
                    $data['line_subtotal'] = $line_subtotal;
                    $data['par_jar'] = $par_jar;
                    $data['minimum_price'] = $minimum_price;
                    $data['is_voucher_used'] = OAM_AFFILIATE_Helper::get_applied_coupon_codes_from_order($commission->order_id);
                    $data['affiliate_account_status'] = $affiliate_account_status ?: 0;
                    $data['commission'] = ($par_jar >= $selling_minimum_price ? (($par_jar - $minimum_price) * $total_all_quantity) : 0);

                    $commission_array[$custom_order_id] = $data;
                }

                //  Start
                
                //  end
            // }
        }

        if ($affiliate_id_attr == '') {
            return $commission_array;
        } else {
            return json_encode(['success' => true, 'data' => $commission_array, 'activate_affiliate_account' => $activate_affiliate_account]);
        }
    }

    public static function get_affiliate_details($affiliate_id)
    {
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

            $current_year_results = $wpdb->get_row($wpdb->prepare(
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

    public static function get_applied_coupon_codes_from_order($order_id)
    {
        // Get the order object
        $order = wc_get_order($order_id);

        if (! $order) {
            return false; // Invalid order ID
        }

        // Get applied coupon codes (returns an array)
        $coupon_codes = $order->get_coupon_codes();

        // Return as comma-separated string, or false if empty
        return ! empty($coupon_codes) ? implode(', ', $coupon_codes) : false;
    }
}
// Initialize the class properly

new OAM_AFFILIATE_Helper();
OAM_AFFILIATE_Helper::init();
