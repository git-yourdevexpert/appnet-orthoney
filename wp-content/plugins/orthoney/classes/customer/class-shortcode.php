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
    
        ob_start(); ?>
        <div class="recent-orders">
            <h3><?php esc_html_e('Recent Orders', OH_DOMAIN); ?></h3>
            <?php foreach ($orders as $order) :
                $order_id = $order->get_id();
                $status = wc_get_order_status_name($order->get_status());
                $order_date = $order->get_date_created()->date_i18n(get_option('date_format'));
                $total_price = wc_price($order->get_total());
                $items = $order->get_items();
                $first_item = reset($items);
                $product_name = $first_item ? $first_item->get_name() : esc_html__('N/A', OH_DOMAIN);
                $quantity = $first_item ? $first_item->get_quantity() : 0;
            ?>
                <div class="order-card">
                    <p><strong><?php esc_html_e('Order ID:', OH_DOMAIN); ?></strong> <?php echo esc_html($order_id); ?></p>
                    <p><span><strong><?php esc_html_e('Price:', OH_DOMAIN); ?></strong> <?php echo wp_kses_post($total_price); ?></span></p>
                    <p><strong><?php echo esc_html($product_name); ?></strong></p>
                    <p><strong><?php esc_html_e('Date:', OH_DOMAIN); ?></strong> <?php echo esc_html($order_date); ?></p>
                    <p><strong><?php esc_html_e('QTY:', OH_DOMAIN); ?></strong> <?php echo esc_html($quantity); ?></p>
                    <span class="status <?php echo esc_attr(sanitize_title($status)); ?>"><?php echo esc_html($status); ?></span>
                    
                    <a href="<?php echo esc_url($order->get_checkout_order_received_url()); ?>" class="w-btn us-btn-style_1 outline-btn"><?php esc_html_e('Reorder', OH_DOMAIN); ?></a>
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('view-order/' . $order_id)); ?>" class="w-btn us-btn-style_1 outline-btn"><?php esc_html_e('Download Invoice', OH_DOMAIN); ?></a>
                </div>
            <?php endforeach; ?>
            <?php if ($show_see_all) : ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('orders')); ?>" class="w-btn us-btn-style_1 outline-btn"><?php esc_html_e('See All', OH_DOMAIN); ?></a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
   

}
new OAM_Shortcode();