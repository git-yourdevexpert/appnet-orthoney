<?php
/**
 * View Order
 *
 * Shows the details of a particular order on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/view-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();
?>
<?php

global $wpdb;
$wc_orders_table = $wpdb->prefix . 'wc_orders';
$wc_orders_meta_table = $wpdb->prefix . 'wc_orders_meta';
$sub_order_result = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT id FROM {$wc_orders_table} WHERE parent_order_id = %d",
		$order->get_order_number()
	),	
);
$order_order_process_result = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT meta_value FROM {$wc_orders_meta_table} WHERE order_id = %d AND meta_key = %s",
        $order_id, 'order_process_by'
    )
);
?>

<div class="order-process-block customer-order-details-section">
	<div class=" ">

	<div class="customer-order-details-nav">
	<h3>#<?php echo $order->get_order_number() ?> Recipient Order</h3>
	<p> 
							<?php 
							$order_process_by = '';
							if($order_order_process_result != 0){
								$user_info = get_userdata($order_order_process_result);
								$display_name = $user_info->display_name;
								$order_process_by = ' and Process by <mark class="order-status"> '.$display_name.'</mark>';
							}
							
							printf(
									/* translators: 1: order number 2: order date 3: process by */
									esc_html__( 'Order #%1$s was placed on %2$s%3$s.', 'woocommerce' ),
									'<mark class="order-number">' . $order->get_order_number() . '</mark>',
									'<mark class="order-date">' . wc_format_datetime( $order->get_date_created() ) . '</mark>',
									$order_process_by 
								);
							?>
						
						</p>
		<label>
			
			<span><img decoding="async" src="http://appnet-orthoney.local/wp-content/plugins/orthoney/assets/image/address.png" alt="" class="address-icon">Order Details</span>
		</label>
		<label>
			
			<span><img decoding="async" src="http://appnet-orthoney.local/wp-content/plugins/orthoney/assets/image/destination.png" alt="" class="address-icon">Recipient Orders</span>
		</label>
	</div>

		<div id="recipient-order-data" class="table-data">
			<div class="download-csv">
				<div class="heading-title">
					<div>
						
						
					</div>
					<div>
						<button data-tippy="Cancel All Recipient Orders" class="btn-underline">Cancel All Recipient Orders</button></div>
				</div>
			</div>
			<table>
				<thead>
					<tr>
						<th>Order ID</th>
						<th>Full Name</th>
						<th>Company Name</th>
						<th>Address</th>
						<th>Quantity</th>
						<th>Status</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					if(!empty($sub_order_result)){
						foreach ($sub_order_result as $key => $order) {
							$order_details = wc_get_order($order->id);
							$first_name 	= $order_details->get_shipping_first_name();
							$address_1  	= $order_details->get_shipping_address_1();
							$address_2  	= $order_details->get_shipping_address_2();
							$city       	= $order_details->get_shipping_city();
							$state      	= $order_details->get_shipping_state();
							$postcode   	= $order_details->get_shipping_postcode();
							$country    	= $order_details->get_shipping_country();
							$total_quantity    = 0;
							$addressParts = array_filter([$address_1, $address_2, $city, $state, $postcode]);
							$company_name = '-';
							$status = $order_details->get_status();
							$status_label = 'wc-' . $order_details->get_status();
							
							foreach ($order_details->get_items() as $item_id => $item) {
								$company_name = $item->get_meta('_recipient_company_name', true);
								$total_quantity += $item->get_quantity();
							}
							?>
							<tr data-id="<?php echo $order->id ?>" data-verify="0" data-group="0">
								<td data-label="Order ID">
									<div class="thead-data">Order ID</div><input type="hidden" name="recipientIds[]" value="<?php echo $order->id ?>"><?php echo $order->id ?>
								</td>
								<td data-label="Full Name">
									<div class="thead-data">Full Name</div><?php echo $first_name ?>
								</td>
								<td data-label="Company name">
									<div class="thead-data">Company name</div><?php echo $company_name ?>
								</td>
								<td data-label="Address">
									<div class="thead-data">Address</div><?php echo implode(', ', $addressParts) ?>
								</td>
								<td data-label="Quantity">
									<div class="thead-data">Quantity</div><?php echo $total_quantity ?>
								</td>
								<td data-label="Status">
									<div class="thead-data">Status</div><span class="<?php echo $status_label ?>"></span><?php echo wc_get_order_status_name($status) ?>
								</td>
								<td data-label="Action">
									<div class="thead-data">Action</div>
									<button class="far fa-eye" data-tippy="View Details"></button>
									<button class="far fa-edit" data-tippy="Edit Details"></button><button data-recipientname="<?php echo $first_name ?>" data-tippy="Remove Recipient" class="deleteRecipient far fa-times"></button>
									
								</td>
							</tr>
							<?php

						}
					}
					?>
				</tbody>
			</table>
		</div> 
		<div id="main-order-details">
		<div class="order-details-wrapper"><ul><li><label>Full Name:</label><span> Jane Son</span></li><li><label>Company Name: </label><span>Vertex Industries</span></li><li><label>Mailing Address: </label><span>101, Main St</span></li><li><label>Suite/Apt#: </label><span></span></li><li><label>City: </label><span>New York</span></li><li><label>State: </label><span>NY</span></li><li><label>Quantity: </label><span>3</span></li></ul><div class="recipient-view-greeting-box"><label>Greeting: </label><span></span></div></div>
		<?php do_action( 'woocommerce_view_order', $order_id ); ?>
		</div>
	</div>
</div>


