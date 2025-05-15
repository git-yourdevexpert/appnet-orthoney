<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_Shortcode
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('get_group', array( $this, 'get_group_handler' ) );
        add_shortcode('customer_dashboard', array( $this, 'customer_dashboard_handler' ) );
	//show recent incomplate orders
        // add_shortcode('recent_incomplete_orders', array($this, 'display_recent_incomplete_orders'));

        //recent order
        add_shortcode('recent_orders', array($this, 'display_recent_orders'));
    }

    
    public function get_group_handler() {
        ob_start();

        $OAM_Helper = new OAM_Helper();
        $OAM_Helper->getGroup();
        return ob_get_clean();
    }

    public function customer_dashboard_handler() {
        ob_start();

        $endpoint = get_query_var('customer_endpoint');

        $template_path = OH_PLUGIN_DIR_PATH . '/templates/customer/customer-dashboard/';

        if ($endpoint === 'affiliate-manage' && file_exists($template_path . 'affiliate-manage.php')) {
            require_once $template_path . 'affiliate-manage.php';
        } else {
            require_once $template_path . 'dashboard.php';
        }
            
        return ob_get_clean();
    }


    /**
     * Show Recent Orders 
     */
    public function display_recent_orders($atts) {
        // Ensure WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return '<p>' . esc_html__('WooCommerce is not active.', OH_DOMAIN) . '</p>';
        }
    
        // Extract attributes with default values
        $atts = shortcode_atts([
            'limit' => 3,
        ], $atts);
    
        $limit = intval($atts['limit']);
    
        // Get the current user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<p>' . esc_html__('Please log in to view your orders.', OH_DOMAIN) . '</p>';
        }
    
        // Fetch recent orders using WooCommerce query
        $args = [
            'customer_id' => $user_id,
            'limit' => $limit,
            'status' => ['wc-processing', 'wc-pending', 'wc-completed'],
        ];
        $orders = wc_get_orders($args);
    
        if (empty($orders)) {
            return '<p>' . esc_html__('No recent orders found.', OH_DOMAIN) . '</p>';
        }
    
        // Check if there are more than the limit
        $total_orders = (new WC_Order_Query([
            'customer_id' => $user_id,
            'status' => ['wc-processing', 'wc-pending', 'wc-completed'],
            'limit' => -1, // No limit for the count
        ]))->get_orders();
    
        $show_see_all = count($total_orders) > $limit;
    
        ob_start(); 
        
        $user_id = get_current_user_id();
        $row_data = OAM_Helper::get_filtered_orders($user_id , 'main_order', 'all', 'all','',false, '', $limit, '');

        ?>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e('Order ID', OH_DOMAIN); ?></th>
                        <th><?php esc_html_e('Date', OH_DOMAIN); ?></th>
                        <th><?php esc_html_e('Billing Name', OH_DOMAIN); ?></th>
                        <th><?php esc_html_e('Organization Code', OH_DOMAIN); ?></th>
                        <th><?php esc_html_e('QTY', OH_DOMAIN); ?></th>
                        <th><?php esc_html_e('Price', OH_DOMAIN); ?></th>
                        <th><?php esc_html_e('Actions', OH_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($row_data as $order) :
                        $order_id = $order['order_no'];
                      $orderdata = wc_get_order( $order_id );
                        $order_date = $order['date'];
                        $total_price = $order['price'];
                        $items = $order['total_recipient'];
                        
                        $quantity = $order['total_jar'];

                          $billing_first_name = $orderdata->get_billing_first_name();
                            $billing_last_name  = $orderdata->get_billing_last_name();
                    ?>
                        <tr>
                            <td><?php echo esc_html($order_id); ?></td>
                            <td><?php echo esc_html($order_date); ?></td>
                            <td><?php echo esc_html($billing_first_name .' '.  $billing_last_name); ?></td>
                            <td><?php echo esc_html($order['affiliate_code']); ?></td>
                            <td><?php echo esc_html($quantity); ?></td>
                            <td><?php echo wp_kses_post($total_price); ?></td>
                            <td><?php echo $order['action'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php
        return ob_get_clean();
    }
    
   

}
new OAM_Shortcode();