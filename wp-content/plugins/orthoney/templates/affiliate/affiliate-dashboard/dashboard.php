<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="order-block-wrap">
    <div class="order-process-dashboard">
        <div class="dashboard-block">
        <h3 class="block-title">Welcome to the Organization Dashboard</h3>
            <?php 
            
            // echo OAM_COMMON_Custom::switch_back_user();
            
            $details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);
            echo OAM_AFFILIATE_Helper::affiliate_details($affiliate_id, $details);
            
            echo OAM_AFFILIATE_Helper::affiliate_order_list($details, 5);
            ?>
        </div>
    </div>
</div>

<?php