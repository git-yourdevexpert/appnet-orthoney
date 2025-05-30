<?php
if (!defined('ABSPATH')) {
    exit;
}
$recipient_id = get_query_var('failed-recipients-details');
$dashboard_link = CUSTOMER_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';
?>
 <div class="heading-title">
        <h3 class="block-title">All Incomplete Orders</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
<?php 

echo do_shortcode("[recipient_multistep_form]");
?>
