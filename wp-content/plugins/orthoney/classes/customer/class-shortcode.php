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
         global $wpdb;
        $user_id = get_current_user_id();
          
       $query = $wpdb->prepare("
    SELECT 
        orders.id AS order_id,
        orders.date_created_gmt AS order_date,
        ba.first_name AS billing_first_name,
        ba.last_name AS billing_last_name,
        (
            SELECT rec1.affiliate_token
            FROM {$wpdb->prefix}oh_recipient_order AS rec1
            WHERE rec1.order_id = rel.order_id
            ORDER BY rec1.id ASC
            LIMIT 1
        ) AS affiliate_token,
        IFNULL(qty_table.total_quantity, 0) AS total_quantity
    FROM {$wpdb->prefix}wc_orders AS orders
    INNER JOIN {$wpdb->prefix}oh_wc_order_relation AS rel 
        ON rel.wc_order_id = orders.id
    LEFT JOIN {$wpdb->prefix}wc_order_addresses AS ba 
        ON ba.order_id = orders.id AND ba.address_type = 'billing'
    LEFT JOIN (
        SELECT 
            orders.id AS order_id,
            SUM(CAST(oim.meta_value AS UNSIGNED)) AS total_quantity
        FROM {$wpdb->prefix}wc_orders AS orders
        INNER JOIN {$wpdb->prefix}woocommerce_order_items AS oi 
            ON oi.order_id = orders.id AND oi.order_item_type = 'line_item'
        INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim 
            ON oim.order_item_id = oi.order_item_id AND oim.meta_key = '_qty'
        GROUP BY orders.id
    ) AS qty_table ON qty_table.order_id = orders.id
    WHERE orders.customer_id = %d
    AND orders.status NOT IN ('wc-cancelled', 'wc-failed', 'wc-on-hold', 'wc-refunded')
    GROUP BY orders.id
    ORDER BY orders.date_created_gmt DESC
    LIMIT %d
", $user_id, $limit);

        $row_data = $wpdb->get_results($query);
          
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
                        $order_id = $order->order_id;
                        $orderdata = wc_get_order( $order_id );
                        $order_date = $order->order_date;
                        $total_price = wc_price($orderdata->get_total());
                   
                        $quantity = $order->total_quantity ;
                        $resume_url = esc_url(CUSTOMER_DASHBOARD_LINK . "order-details/". $order_id);

                       $affiliate_code = 'Honey from the Heart';
                       
                       if($order->affiliate_code == 'Orthoney' && $order->affiliate_code == ''){
                           $affiliate_code = 'Honey from the Heart';
                        }else{
                           $affiliate_code =  $order->affiliate_code;

                       }
                       if($affiliate_code == ''){
                        $affiliate_code = 'Honey from the Heart';
                       }

                    ?>
                        <tr>
                            <td><?php echo esc_html(OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID')); ?></td>
                            <td><?php echo esc_html($order_date); ?></td>
                            <td><?php echo esc_html($order->billing_first_name . ' ' . $order->billing_last_name ); ?></td>
                            <td><?php echo esc_html($affiliate_code); ?></td>
                            <td><?php echo esc_html($quantity); ?></td>
                            <td><?php echo wp_kses_post($total_price); ?></td>
                            <td><?php echo '<a data-tippy="View Order" href="' . $resume_url . '" class="far fa-eye"></a>'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php
        return ob_get_clean();
    }

}
new OAM_Shortcode();