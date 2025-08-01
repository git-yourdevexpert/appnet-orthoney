<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// $commission = OAM_AFFILIATE_Helper::get_commission_affiliate_base_token('TTT');

// echo "<pre>";
// print_r($commission);
// echo "</pre>";

$affiliate_id = get_current_user_id();
$user_roles = OAM_COMMON_Custom::get_user_role_by_id($affiliate_id);



if( !in_array('yith_affiliate ', $user_roles)){
    if(in_array('affiliate_team_member', $user_roles) OR in_array('administrator', $user_roles)){
        if(get_user_meta($affiliate_id, 'associated_affiliate_id', true)){
            $affiliate_id = get_user_meta($affiliate_id, 'associated_affiliate_id', true);

        }
    }
}


OAM_COMMON_Custom::switch_back_user();
$details = OAM_AFFILIATE_Helper::get_affiliate_details($affiliate_id);


// Hide the subheader if the token is empty
if (empty($details['token'])) {
    echo '<style>#page-header .l-subheader.at_bottom{display:none !important}</style>';
}
?>
<style>
    .affiliate-sales-representative-details{
        margin:20px 0;
        display: grid;
        gap: 24px;
        grid-template-columns: repeat(3, 1fr);
    }
    .affiliate-sales-representative-details .item-box{

        border :1px solid #f5f5f5;
        background: #f6f6f6;
        padding:15px;
        color:#000 !important;
        border-radius: 10px;
    }
     .affiliate-sales-representative-details .item-box:hover{
         background: #eee;
     }
</style>


<div class="order-block-wrap">
    <div class="order-process-dashboard">
        <div class="dashboard-block">
            <div class="row-block">
                <h3 class="block-title">Welcome to the Organization Dashboard</h3>
            </div>

            <?php
            // Display affiliate details
            echo OAM_AFFILIATE_Helper::affiliate_details($affiliate_id, $details);

            // Display claimable orders if token exists and there are orders
            if (!empty($details['token']) && !empty($details['orders'])) {
                $current_url = OAM_Helper::$organization_dashboard_link . 'orders-list/';
                echo '<div class="recent-commissions">
                        <div class="dashboard-card">
                            <div class="row-block">
                                <h4>Recent Customer Orders</h4>
                                <a href="'.$current_url.'" class="w-btn us-btn-style_1">View all orders</a>
                            </div>';
                echo OAM_AFFILIATE_Helper::affiliate_current_year_order_list($details);
                echo '</div></div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- <div class="see-all"><a href="' . esc_url($current_url) . '" class="w-btn us-btn-style_1">View all</a></div> -->