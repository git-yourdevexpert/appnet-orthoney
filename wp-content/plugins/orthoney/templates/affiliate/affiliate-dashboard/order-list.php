<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="order-block-wrap">
    <div class="order-process-dashboard">
        <div class="dashboard-block">
        <div class="heading-title"><h3 class="block-title">Order list</h3></div>
            <?php
            $details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);
            echo OAM_AFFILIATE_Helper::affiliate_order_list($details);
            ?>
        </div>
    </div>
</div>
<?php
