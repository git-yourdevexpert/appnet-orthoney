<?php
/**
 * Customer processing order email
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $wpdb;
$total_honey_jars = 0;
$taxable_donation = get_field('ort_taxable_donation', 'option');
$order_process_table = OAM_Helper::$order_process_table;
$yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
$user = get_current_user_id();
$result = $wpdb->get_row($wpdb->prepare(
	"SELECT * FROM {$order_process_table} WHERE user_id = %d AND id = %d",
	$user, intval($_GET['pid'])
));
$setData = json_decode($result->data);
$affiliate = $setData->affiliate_select != '' ? $setData->affiliate_select : 'Orthoney';
$affiliate_id = intval($affiliate);
$token = $wpdb->get_var($wpdb->prepare(
    "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE ID = %d",
    $affiliate_id
));

foreach ($order->get_items() as $item_id => $item) { 
    $total_honey_jars += $item->get_quantity();
}
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p>Thank you for your gift of $<?php echo $order->get_total(); ?> to <?php echo $order->get_billing_company(); ?>. Your Honey From The Heart gift benefits <?php echo $order->get_billing_company(); ?>, a non-profit organization, and ORT America, a 501(c)(3) organization. For federal income tax purposes, your charitable deduction is limited to the purchase price of the honey less its fair market value. For purposes of determining the value of goods provided, you should use $<?php echo $taxable_donation; ?> per jar so your charitable contribution is <?php echo "$".number_format($order->get_total() - ($total_honey_jars * $taxable_donation), 2); ?>.</p>

<p><?php _e( "Your order has been received and is now being processed. Your order details are shown below for your reference:", 'woocommerce' ); ?></p>
<p>If you have questions about your order please contact:<br />
<strong><?php echo $order->get_billing_first_name() . " " . $order->get_billing_last_name(); ?></strong><br />
<?php echo $order->get_billing_phone(); ?><br />
<?php echo $order->get_billing_email(); ?>
</p>

<?php do_action('woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text); ?>

<h2><?php printf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?></h2>
<p>Order Placed: <?php echo wc_format_datetime( $order->get_date_created() ); ?></p>
<p>Total Jars Ordered: <?php echo $total_honey_jars; ?></p>

<table cellspacing="0" cellpadding="6" style="width: 100%; border: 1px solid #eee;" border="1" bordercolor="#eee">
    <thead>
        <tr>
            <th><?php _e( 'Recipient', 'woocommerce' ); ?></th>
            <th><?php _e( 'Quantity', 'woocommerce' ); ?></th>
            <th><?php _e( 'Price', 'woocommerce' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($order->get_items() as $item_id => $item) {

            // Retrieve meta data
            $full_name    = wc_get_order_item_meta($item_id, 'full_name', true) ?: 'N/A';
            $company_name = wc_get_order_item_meta($item_id, '_recipient_company_name', true) ?: 'N/A';
            $address_1    = wc_get_order_item_meta($item_id, '_recipient_address_1', true) ?: '';
            $address_2    = wc_get_order_item_meta($item_id, '_recipient_address_2', true) ?: '';
            $city         = wc_get_order_item_meta($item_id, '_recipient_city', true) ?: '';
            $state        = wc_get_order_item_meta($item_id, '_recipient_state', true) ?: '';
            $zipcode      = wc_get_order_item_meta($item_id, '_recipient_zipcode', true) ?: '';
            $quantity     = $item->get_quantity();
            $greeting     = wc_get_order_item_meta($item_id, '_recipient_greeting', true) ?: '';
            $price        = $item->get_total();
            ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($full_name); ?></strong><br>
                    <small><?php echo esc_html($company_name); ?><br />
                    <?php echo esc_html($address_1 . ' ' . $address_2); ?><br />
                    <?php echo esc_html($city); ?>, <?php echo esc_html($state); ?> <?php echo esc_html($zipcode); ?><br />
                    <?php echo esc_html($greeting); ?><br />
                    <?php echo $order->get_billing_country(); ?>
                    </small>
                </td>
                <td><?php echo esc_html($quantity); ?></td>
                <td><span class="amount"><?php echo "$".number_format($price, 2); ?></span></td>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
		<?php
			if ( @$totals = $order->get_order_item_totals() ) {
				/*$totals['shipping']['value'] = str_replace("&nbsp;", " ", $totals['shipping']['value']);
				$totals['shipping']['value'] = explode(" ", strip_tags($totals['shipping']['value']));
				$totals['shipping']['value'] = $totals['shipping']['value'][0];*/
				$totals['shipping']['value'] = "$".number_format((int) preg_replace('/\D/', '', @$totals['shipping']['value']), 2);
				$i = 0;
				foreach ( $totals as $total ) {
					$i++;
					?><tr>
						<th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
						<td style="text-align:left; border: 1px solid #eee; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php if($i==2) { ?>$<?php echo number_format(WC()->cart->shipping_total, 2);}else { echo $total['value']; } ?></td>
					</tr><?php
				}
			}
		?>
	</tfoot>
</table>

<?php echo '<p>Distributor Code: ' . (($token != '') ? $token : $affiliate) . '</p>'; ?>


<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_footer' ); ?>
