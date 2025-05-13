<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$redirect_back_user_id = get_transient('redirect_back_user_' . $current_user_id);

if ($redirect_back_user_id) {
    $nonce = wp_create_nonce('auto_login_' . $redirect_back_user_id);
    $back_url = home_url('/?action=auto_login&user_id=' . $redirect_back_user_id . '&nonce=' . $nonce . '&redirect_to=' . urlencode(SALES_REPRESENTATIVE_DASHBOARD_LINK));
    echo '<a href="' . esc_url($back_url) . '" class="button">Back to Previous User</a>';
}

?>
<div class="order-block-wrap">
        <div class="order-process-dashboard">
            <div class="dashboard-block">
                <h3 class="block-title">Welcome to the Customer Dashboard</h3>
                <div class="block-row">
                    <?php 

                    // echo OAM_COMMON_Custom::switch_back_user();
                    
                    $title = 'Place Order';
                    $content = '<h3 class="text-color-yellow">A Jar of Honey, A World of Meaning</h3><h4 class="text-color-green">Every jar helps fund meaningful programs and spread holiday joy!</h4>';
                   
                    echo OAM_COMMON_Custom::info_block($title, $content);
                    ?>
                    
                </div>

                <div class="one-col-grid">
                <div class="recipient-lists-block custom-table">
                        <div class="row-block">
                            <h4>Recent Orders</h4>
                            <div class="see-all">
                                <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$customer_dashboard_link.'orders/') ?>">See all</a> 
                            </div>
                        </div>
                        <?php 
                        echo do_shortcode('[recent_orders limit="5"]');
                        ?>
                    </div>
                    
                </div>
                <div class="two-col-grid">
                    <div class="cl-left">
                        <?php 
                        echo OAM_Helper::incomplete_orders_dashboard_widget('Incomplete Orders', 3, esc_url(OAM_Helper::$customer_dashboard_link.'incomplete-order/'));
                        ?>
                    </div>
                    <div class="cl-right">
                    <?php 
                        echo OAM_Helper::failed_recipients_dashboard_widget('Failed Addresses', 3, esc_url(OAM_Helper::$customer_dashboard_link.'failed-recipients/'));
                        ?>
                    
                    </div>
                </div>
                <div class="two-col-grid">
                    <div class="cl-left">
                    <?php 
                        echo OAM_Helper::group_dashboard_widget('Recipient List', 3, esc_url(OAM_Helper::$customer_dashboard_link.'groups/'));
                        ?>
                    
                    </div>
                    <div class="cl-right">
                        <?php 
                            echo OAM_Helper::organizations_dashboard_widget('Associated Organizations', 3, esc_url(OAM_Helper::$customer_dashboard_link.'organizations/'));
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php

// echo do_shortcode('[recent_incomplete_orders limit="3"]');

// echo do_shortcode('[recent_orders limit="2"]');