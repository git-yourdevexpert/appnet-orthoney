<?php
/**
 * Customer processing order email
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

$total_honey_jars = 0;
$taxable_donation = get_field('ort_taxable_donation', 'option');
$order_process_table = OAM_Helper::$order_process_table;
$yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
$order_process_recipient_table = OAM_Helper::$order_process_recipient_table;

$user = get_current_user_id();
$result = $wpdb->get_row($wpdb->prepare(
	"SELECT * FROM {$order_process_table} WHERE order_id = %d",
	intval($order->get_order_number())
));

$setData = json_decode($result->data);
$affiliate = $setData->affiliate_select != '' ? $setData->affiliate_select : 'Orthoney';

foreach ($order->get_items() as $item_id => $item) { 
    $total_honey_jars += $item->get_quantity();
}

$sub_order_id = OAM_COMMON_Custom::get_order_meta($order->get_order_number(), '_orthoney_OrderID');

$recipientResult = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$order_process_recipient_table} WHERE order_id = %d",
    $sub_order_id 
));

$token = $organization = 'Orthoney';
$organization_data = 'Honey From The Heart';
$organization_address_data = '3495 Piedmont Rd NE, Atlanta, GA 30305';
$affiliate_email = 'support@orthoney.com';

if (!empty($affiliate) && $affiliate !== 'Orthoney') {
    $affiliate_id = $affiliate;
    $token = $wpdb->get_var($wpdb->prepare(
    "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d",
    $affiliate_id
    ));

    
    $organization = get_user_meta($affiliate_id, '_yith_wcaf_name_of_your_organization', true);
    $affiliate_email_meta_data = get_user_meta( $affiliate_id, '_yith_wcaf_email', true );

    if ( empty( $affiliate_email_meta_data ) ) {
        $user = get_userdata( $affiliate_id );
        
        if ( $user && ! empty( $user->user_email ) ) {
            $affiliate_email = $user->user_email;
        }
    }

    if ($organization != 'Orthoney') {
        $organization_data_query = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                aff.*,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_city' THEN um.meta_value END) AS _yith_wcaf_city,
                MAX(CASE WHEN um.meta_key = '_yith_wcaf_state' THEN um.meta_value END) AS _yith_wcaf_state,
                MAX(CASE WHEN um.meta_key = 'billing_city' THEN um.meta_value END) AS billing_city,
                MAX(CASE WHEN um.meta_key = 'billing_state' THEN um.meta_value END) AS billing_state,
                MAX(CASE WHEN um.meta_key = 'shipping_city' THEN um.meta_value END) AS shipping_city,
                MAX(CASE WHEN um.meta_key = 'shipping_state' THEN um.meta_value END) AS shipping_state
            FROM {$wpdb->prefix}yith_wcaf_affiliates AS aff
            LEFT JOIN {$wpdb->usermeta} AS um ON um.user_id = aff.user_id
            WHERE aff.token = %s
            GROUP BY aff.user_id",
            $token
        ));

        $city = $organization_data_query->_yith_wcaf_city 
            ?: $organization_data_query->billing_city 
            ?: $organization_data_query->shipping_city;

        $state = $organization_data_query->_yith_wcaf_state 
            ?: $organization_data_query->billing_state 
            ?: $organization_data_query->shipping_state;

        $merged_address = implode(', ', array_filter(['[' . $token . ']', $organization, $city, $state]));
        $organization_data = trim($merged_address);
        $organization_address_data = implode(', ', array_filter([
            $organization_data_query->address_1,
            $organization_data_query->address_2,
            $city,
            $state,
            $organization_data_query->zipcode,
            $organization_data_query->country
        ]));
    }
}

$total_price_before_discount = 0;
$total_quantity = 0;

foreach ( $order->get_items() as $item ) {
    $quantity = $item->get_quantity();
    $line_subtotal = $item->get_meta('_line_subtotal', true);
    if (!$line_subtotal) {
        $line_subtotal = $item->get_subtotal();
    }

    $total_price_before_discount += floatval($line_subtotal);
    $total_quantity += $quantity;
}

$per_jar_price = $total_quantity > 0 ? round($total_price_before_discount / $total_quantity, 2) : 0;
$unit_price = ($total_quantity > 0) ? ($line_subtotal / $total_quantity) : 0;
?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p>Thank you for your gift of $<?php echo $order->get_total(); ?> to <?php echo $organization_data; ?>. Your Honey From The Heart gift benefits <?php echo $organization_data; ?>, a non-profit organization, and ORT America, a 501(c)(3) organization. For federal income tax purposes, your charitable deduction is limited to the purchase price of the honey less its fair market value. For purposes of determining the value of goods provided, you should use $<?php echo $taxable_donation; ?> per jar so your charitable contribution is <?php echo "$" . number_format($order->get_total() - ($total_honey_jars * $taxable_donation), 2); ?>.</p>

<p><?php _e( "Your order has been received and is now being processed. Your order details are shown below for your reference:", 'woocommerce' ); ?></p>

<p>If you have questions about your order please contact:<br />
<?php
if ($organization != 'Orthoney') {
    echo '<strong>' . esc_html($organization_data) . '</strong><br />';
    echo esc_html($organization_address_data) . '<br />';
    echo esc_html($affiliate_email) . '<br />';
} else {
    echo '<strong>Honey From The Heart</strong><br />';
    echo '3495 Piedmont Rd NE, Atlanta, GA 30305<br />';
    echo esc_html($affiliate_email) . '<br />';
}
?>

</p>

<?php do_action('woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text); ?>

<h2><?php printf( __( 'Order #%s', 'woocommerce' ), $sub_order_id ); ?></h2>
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
        <?php foreach ($recipientResult as $data) {
            $full_name    = $data->full_name ?: '';
            $company_name = $data->company_name ?: '';
            $addressParts = array_filter([$data->address_1, $data->address_2, $data->city, $data->state, $data->zipcode, $order->get_billing_country()]);
            $quantity     = $data->quantity;
            $greeting     = $data->greeting ?: '';
            $price        = $unit_price * $quantity;
            ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($full_name); ?></strong><br>
                    <small><?php echo esc_html($company_name); ?><br />
                    <?php echo esc_html(implode(' ', $addressParts)); ?><br />
                    <?php echo esc_html($greeting); ?><br />
                    </small>
                </td>
                <td><?php echo esc_html($quantity); ?></td>
                <td><span class="amount"><?php echo "$" . number_format($price, 2); ?></span></td>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <?php
        $totals = $order->get_order_item_totals();
        if ($totals && is_array($totals)) {
            $i = 0;
            foreach ($totals as $key => $total) {
                $i++;
                $label = isset($total['label']) ? $total['label'] : '';
                $value = isset($total['value']) ? wp_kses_post($total['value']) : '';

                if ($key === 'shipping') {
                    $value = '$' . number_format((float) $order->get_shipping_total(), 2);
                }
                ?>
                <tr>
                    <th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee; <?php if ($i == 1) echo 'border-top-width: 4px;'; ?>">
                        <?php echo $label; ?>
                    </th>
                    <td style="text-align:left; border: 1px solid #eee; <?php if ($i == 1) echo 'border-top-width: 4px;'; ?>">
                        <?php echo $value; ?>
                    </td>
                </tr>
                <?php
            }
        }

        // Extra check to force "discount" row if missing
        if ( $order->get_discount_total() > 0 && ! isset( $totals['discount'] ) ) {
            ?>
            <tr>
                <th scope="row" colspan="2" style="text-align:left; border: 1px solid #eee;">
                    Discount
                </th>
                <td style="text-align:left; border: 1px solid #eee;">
                    -<?php echo wc_price( $order->get_discount_total() ); ?>
                </td>
            </tr>
            <?php
        }
        ?>
    </tfoot>
</table>

<?php 
$code = $token ?: $affiliate;
if ($code != 'Orthoney') {
    echo '<p>Distributor Code: ' . esc_html($code) . '</p>'; 
}
?>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text ); ?>
<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>
<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text ); ?>
<?php do_action( 'woocommerce_email_footer' ); ?>
