<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$dashboard_link = ORGANIZATION_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';

include_once  OH_PLUGIN_DIR_PATH.'templates/woocommerce/myaccount/orders.php';

?>
<div class="order-block-wrap" style="display:none">
    <div class="order-process-dashboard">
        <div class="dashboard-block">
        <div class="row-block">
            <h3 class="block-title">Recent Customer Orders</h3>
            <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
        </div>
            <?php
            $details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);
            if( !empty($details['orders']) ){
                echo OAM_AFFILIATE_Helper::affiliate_order_list($details);
            }else{
                echo "<p style='padding-top: 15px;'>Customer Orders not found!</p>";
            }
            ?>
        </div>
    </div>
</div>
<?php
