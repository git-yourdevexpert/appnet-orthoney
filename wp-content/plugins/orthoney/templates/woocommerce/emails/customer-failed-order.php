<?php
/**
 * Customer failed order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-failed-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 9.8.0
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;

$yith_wcaf_affiliates_table = $wpdb->prefix . 'yith_wcaf_affiliates';
$order_process_table = OAM_Helper::$order_process_table;

$result = $wpdb->get_row($wpdb->prepare("SELECT data FROM {$order_process_table} WHERE order_id = %d", intval($order->get_order_number())));

$json_data = $result->data ?? '';

$decoded_data = json_decode($json_data, true);

$affiliate = !empty($decoded_data['affiliate_select']) ? $decoded_data['affiliate_select'] : 0;

$yith_wcaf_referral = $wpdb->get_var($wpdb->prepare("SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $affiliate));


$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );

/**
 * Hook for the woocommerce_email_header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 * @since 3.7.0
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo $email_improvements_enabled ? '<div class="email-introduction">' : ''; ?>
<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	/* translators: %s: Customer first name */
	printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) );
} else {
	printf( esc_html__( 'Hi,', 'woocommerce' ) );
}
?>
</p>
<p><?php esc_html_e( "Unfortunately, we couldn't complete your order due to an issue with your payment method.", 'woocommerce' ); ?></p>
<?php /* translators: %s: Site title */ ?>
<p><?php printf( esc_html__( "If you'd like to continue with your purchase, please return to %s and try a different method of payment.", 'woocommerce' ), esc_html( $blogname ) ); ?></p>
<p><?php esc_html_e( 'Your order details are as follows:', 'woocommerce' ); ?></p>
<?php echo $email_improvements_enabled ? '</div>' : ''; ?>

<?php
/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );


/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 * @since 1.0.0
 */



do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

if ($affiliate != 0) {
    echo '<p><strong>Distributor Code: </strong>' . esc_html($yith_wcaf_referral) . '</p>'; 
}

$order_details_url = esc_url(CUSTOMER_DASHBOARD_LINK . "order-details/". ($order->get_order_number()));

?>
<div>
	<a target="_blank" style="display: inline-block;margin-bottom: 25px;font-size: 13px; line-height: 1.2 !important; font-weight: 600; font-style: normal; text-transform: uppercase; letter-spacing: 0.02em; border-radius: 4px; padding: 1.2em 2.4em; background: #572C7C; border-color: transparent; color: #ffffff !important;" href="<?php echo esc_url($order_details_url) ?>" > View Order Details</a>
</div>
<?php

/**
 * Hook for woocommerce_email_customer_details.
 *
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 * @since 1.0.0
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo $email_improvements_enabled ? '<table border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td class="email-additional-content">' : '';
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
	echo $email_improvements_enabled ? '</td></tr></table>' : '';
}


/**
 * Hook for the woocommerce_email_footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 * @since 3.7.0
 */
do_action( 'woocommerce_email_footer', $email );
