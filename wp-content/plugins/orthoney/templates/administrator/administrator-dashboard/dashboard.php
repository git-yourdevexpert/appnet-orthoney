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
                <h3 class="block-title">Welcome to the Administrator Dashboard</h3>
                <div class="block-row">
                    <?php 

                    // echo OAM_COMMON_Custom::switch_back_user();
                    
                    $title = 'Place Order';
                    $content = '<div class="text-center"><h3 class="text-color-yellow">A Jar of Honey, A World of Meaning</h3><h4 class="block-title">Every jar helps fund meaningful programs and spread holiday joy!</h4></div>';
                   
                    echo OAM_COMMON_Custom::info_block($title, $content);
                    ?>
                    
                </div>

                
                <div class="two-col-grid">
                    <div class="cl-left">
                        <div class="recipient-lists-block custom-table">
                            <div class="row-block">
                                <h4>View All Orders</h4>
                                <div class="see-all">
                                    <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$administrator_dashboard_link.'orders/'); ?>">See All</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cl-right">
                        <div class="recipient-lists-block custom-table">
                            <div class="row-block">
                                <h4>Manage Sales Representative</h4>
                                <div class="see-all">
                                    <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$administrator_dashboard_link.'sales-representative/'); ?>">See All</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="two-col-grid">
                    <div class="cl-left">
                        <div class="recipient-lists-block custom-table">
                            <div class="row-block">
                                <h4>Manage Customer</h4>
                                <div class="see-all">
                                    <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$administrator_dashboard_link.'customer/'); ?>">See All</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cl-right">
                        <div class="recipient-lists-block custom-table">
                            <div class="row-block">
                                <h4>Manage Organizations</h4>
                                <div class="see-all">
                                    <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$administrator_dashboard_link.'organizations/'); ?>">See All</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php