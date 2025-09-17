<?php
/**
 * Customer Partial Shipped Email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/partial-shipped.php
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); 

global $wpdb;
$wc_order_relation_table      = $wpdb->prefix . 'oh_wc_order_relation';
$order_id = intval($order->get_order_number());
$custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');

?>

<p><?php printf( esc_html__( 'Hello,', 'orthoney' ) ); ?></p>

<p><?php esc_html_e( 'Sweet news! Part of your honey order has already been shipped, and the rest will be on its way shortly.', 'orthoney' ); ?></p>

<p><strong><?php esc_html_e( 'Order id: ', 'orthoney' ); ?> <?php echo esc_html( '#'.$custom_order_id.' ( Ref Id: '.$order_id.' )' ); ?></strong></p>

<p><strong><?php esc_html_e( 'You can click here to view tracking details', 'orthoney' ); ?></strong> <a href="<?php echo esc_url(CUSTOMER_DASHBOARD_LINK . "order-details/" . $order_id); ?>"><?php esc_html_e( 'Order details page', 'orthoney' ); ?></a></p>

<p>
    <?php esc_html_e( 'Or follow these steps to check your shipment:', 'orthoney' ); ?>
</p>
<ol>
    <li>Log in to your customer account: <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php echo esc_html( home_url( '/login/' ) ); ?></a></li>
    <li>Go to your Order Page: <a href="<?php echo esc_url( home_url( '/dashboard/orders/' ) ); ?>"><?php echo esc_html( home_url( '/dashboard/orders/' ) ); ?></a></li>
    <li>On the order page, you can view your order status and tap <strong>View Order</strong>.</li>
    <li>On the order details page, you will find your Tracking Number and the <strong>Track Package</strong> option.</li>
    <li>Click <strong>Click Here</strong> under Track Package to be redirected to the carrier's tracking URL.</li>
</ol>

<p>
    <?php esc_html_e( 'We’ll buzz you again as soon as the remaining items are shipped. Thank you for your patience and for letting us bring a little sweetness into your day!', 'orthoney' ); ?>
</p>

<p><?php esc_html_e( 'Sweetly yours,', 'orthoney' ); ?></p>
<p>
    <strong><?php esc_html_e( 'Honey From The Heart Team,', 'orthoney' ); ?></strong><br>
    <i>A sweet way to raise money!</i><br>
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( home_url( '/' ) ); ?></a>
</p>

<?php
do_action( 'woocommerce_email_footer', $email );
