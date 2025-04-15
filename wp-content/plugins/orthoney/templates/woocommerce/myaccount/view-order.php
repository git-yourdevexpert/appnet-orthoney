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

defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$notes = $order->get_customer_order_notes();

global $wpdb;
$wc_orders_table      = $wpdb->prefix . 'wc_orders';
$wc_orders_meta_table = $wpdb->prefix . 'wc_orders_meta';

$sub_orders = $wpdb->get_results(
    $wpdb->prepare("SELECT id FROM {$wc_orders_table} WHERE parent_order_id = %d AND customer_id", $order->get_order_number(), $user_id)
);

$order_id = $order->get_id();
$order_process_user_id = $wpdb->get_var(
    $wpdb->prepare("SELECT meta_value FROM {$wc_orders_meta_table} WHERE order_id = %d AND meta_key = %s", $order_id, 'order_process_by')
);

$order_process_by = '';
if (!empty($order_process_user_id)) {
    $user_info = get_userdata($order_process_user_id);
    if ($user_info) {
        $order_process_by = ' and Process by <mark class="order-status">' . esc_html($user_info->display_name) . '</mark>';
    }
}
?>

<div class='loader multiStepForm'>
    <div>
        <h2 class='swal2-title'>Processing...</h2>
        <div class='swal2-html-container'>Please wait while we process your request.</div>
        <div class='loader-5'></div>
    </div>
</div>

<div class="order-process-block customer-order-details-section">
    <div class="customer-order-details-nav heading-title">
        <div>
            <h3>#<?php echo esc_html($order->get_order_number()); ?> Recipient Order</h3>
            <p>
                <?php
                printf(
                    esc_html__('Order #%1$s was placed on %2$s%3$s.', 'woocommerce'),
                    '<mark class="order-number">' . esc_html($order->get_order_number()) . '</mark>',
                    '<mark class="order-date">' . esc_html(wc_format_datetime($order->get_date_created())) . '</mark>',
                    $order_process_by
                );
                ?>
            </p>
        </div>
        <div><?php do_action('woocommerce_view_order', $order_id); ?></div>
    </div>

    <div id="recipient-order-data" class="table-data orthoney-datatable-warraper">
        <div class="download-csv heading-title">
            <div></div>
            <div>
                <button data-tippy="Cancel All Recipient Orders" class="btn-underline">Cancel All Recipient Orders</button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Full Name</th>
                    <th>Company Name</th>
                    <th>Address</th>
                    <th>Jar</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sub_orders)) : ?>
                    <?php foreach ($sub_orders as $sub_order) :
                        $sub_order_id = $sub_order->id;
                        $sub_order_obj = wc_get_order($sub_order_id);
                        if (!$sub_order_obj) continue;

                        $first_name    = $sub_order_obj->get_shipping_first_name();
                        $address_parts = array_filter([
                            $sub_order_obj->get_shipping_address_1(),
                            $sub_order_obj->get_shipping_address_2(),
                            $sub_order_obj->get_shipping_city(),
                            $sub_order_obj->get_shipping_state(),
                            $sub_order_obj->get_shipping_postcode()
                        ]);

                        $company_name = '-';
                        $total_qty = 0;
                        foreach ($sub_order_obj->get_items() as $item) {
                            $company_name = $item->get_meta('_recipient_company_name', true) ?: '-';
                            $total_qty += $item->get_quantity();
                        }

                        $status = $sub_order_obj->get_status();
                        $status_label = 'wc-' . $status;
                        ?>
                        <tr data-id="<?php echo esc_attr($sub_order_id); ?>" data-verify="0" data-group="0">
                            <td data-label="Order ID">
                                <div class="thead-data">Order ID</div>
                                <input type="hidden" name="recipientIds[]" value="<?php echo esc_attr($sub_order_id); ?>">
                                <?php echo esc_html($sub_order_id); ?>
                            </td>
                            <td data-label="Full Name"><div class="thead-data">Full Name</div><?php echo esc_html($first_name); ?></td>
                            <td data-label="Company Name"><div class="thead-data">Company Name</div><?php echo esc_html($company_name); ?></td>
                            <td data-label="Address"><div class="thead-data">Address</div><?php echo esc_html(implode(', ', $address_parts)); ?></td>
                            <td data-label="Quantity"><div class="thead-data">Quantity</div><?php echo esc_html($total_qty); ?></td>
                            <td data-label="Status">
                                <div class="thead-data">Status</div>
                                <span class="<?php echo esc_attr($status_label); ?>"></span>
                                <?php echo esc_html(wc_get_order_status_name($status)); ?>
                            </td>
                            <td data-label="Action">
                                <div class="thead-data">Action</div>
                                <button class="far fa-eye viewRecipientOrder" data-tippy="View Details" data-popup="#recipient-order-edit-popup"></button>
                                <button class="far fa-edit editRecipientOrder" data-tippy="Edit Details" data-popup="#recipient-order-manage-popup"></button>
                                <button class="deleteRecipient far fa-times" data-tippy="Cancel Recipient Order" data-recipientname="<?php echo esc_attr($first_name); ?>"></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Popups -->
        <div id="recipient-order-manage-popup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block orthoney-datatable-warraper">
                <h3>Recipients Order Details</h3>
                <?php echo OAM_Helper::get_recipient_order_form(); ?>
            </div>
        </div>

        <div id="recipient-order-edit-popup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block orthoney-datatable-warraper">
                <h3>Recipients Order Details</h3>
                <div class="recipient-view-details-wrapper"></div>
                <div class="footer-btn gfield--width-full">
                    <button type="button" class="w-btn us-btn-style_4" data-lity-close>Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
