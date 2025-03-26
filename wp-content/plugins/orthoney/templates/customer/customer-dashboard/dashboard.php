<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$redirect_back_user_id = get_transient('redirect_back_user_' . $current_user_id);

if ($redirect_back_user_id) {
    $nonce = wp_create_nonce('auto_login_' . $redirect_back_user_id);
    $back_url = home_url('/?action=auto_login&user_id=' . $redirect_back_user_id . '&nonce=' . $nonce . '&redirect_to=' . urlencode('/sales-representative-dashboard/'));
    echo '<a href="' . esc_url($back_url) . '" class="button">Back to Previous User</a>';
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