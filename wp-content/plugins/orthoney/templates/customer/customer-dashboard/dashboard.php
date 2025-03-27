<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('user_switching') && method_exists('user_switching', 'get_old_user')) {
    $old_user = user_switching::get_old_user();
    if ($old_user) {
        $redirect_to = urlencode(home_url('/sales-representative-dashboard/manage-customer/'));
        $switch_back_url = user_switching::switch_back_url($old_user) . '&redirect_to=' . $redirect_to;

        echo '<a href="' . esc_url($switch_back_url) . '" class="switch-back-btn w-btn us-btn-style_1">Switch Back to ' . esc_html($old_user->display_name) . '</a>';
    }
}

?>
<div class="order-block-wrap">
        <div class="order-process-dashboard heading-open-sans">
            <div class="dashboard-block">
                <h3 class="block-title">Welcome to the Customer Dashboard</h3>
                <div class="block-row">
                    <?php 
                    $title = 'Place Order';
                    $content = 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Aliquid qui, assumenda adipisci tenetur nemo culpa repellendus quaerat aut magni quidem. Inventore expedita voluptas facere ipsa alias fugiat non nulla recusandae! Lorem ipsum dolor sit amet consectetur adipisicing elit. Aliquid qui, assumenda adipisci tenetur nemo culpa repellendus quaerat aut magni quidem. Inventore expedita voluptas facere ipsa alias fugiat non nulla recusandae!';
                   
                    echo OAM_COMMON_Custom::info_block($title, $content);
                    ?>
                    
                </div>
                <div class="two-col-grid">
                    <div class="cl-left">
                        <?php 
                        echo OAM_Helper::incomplete_orders_dashboard_widget('Incomplete orders', 3, esc_url(OAM_Helper::$customer_dashboard_link.'/incomplete-order/'));
                        ?>
                    </div>
                    <div class="cl-right">
                    <?php 
                        echo OAM_Helper::group_dashboard_widget('Recipient List', 3, esc_url(OAM_Helper::$customer_dashboard_link.'/groups/'));
                        ?>
                    </div>
                </div>
                <div class="two-col-grid">
                    <div class="cl-left">
                        <?php 
                        echo OAM_Helper::failed_recipients_dashboard_widget('Failed Recipients', 3, esc_url(OAM_Helper::$customer_dashboard_link.'/failed-recipients/'));
                        ?>
                    </div>
                    <div class="cl-right">
                    <?php 
                        echo OAM_Helper::groups_dashboard_widget('Group List', 3, esc_url(OAM_Helper::$customer_dashboard_link.'/groups/'));
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php

// echo do_shortcode('[recent_incomplete_orders limit="3"]');

// echo do_shortcode('[recent_orders limit="2"]');