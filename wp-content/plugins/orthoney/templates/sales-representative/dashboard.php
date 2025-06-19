<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="order-block-wrap">
        <div class="order-process-dashboard">
            <div class="dashboard-block">
                <h3 class="block-title">Welcome to the Sales Representative Dashboard</h3>
                <div class="two-col-grid">
                    <div class="cl-left">
                        <div class="recipient-lists-block custom-table">
                            <div class="row-block">
                                <h4>Assigned Customers</h4>
                                <div class="see-all">
                                    <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$sales_representative_dashboard_link.'manage-customers/'); ?>">See All</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="cl-right">
                        <div class="recipient-lists-block custom-table">
                            <div class="row-block">
                                <h4>Assigned Organizations</h4>
                                <div class="see-all">
                                    <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$sales_representative_dashboard_link.'manage-organizations/'); ?>">See All</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="two-col-grid">
                    <div class="cl-left">
                        <div class="recipient-lists-block custom-table">
                            <div class="row-block">
                                <h4>Organizations Commission</h4>
                                <div class="see-all">
                                    <a class="w-btn us-btn-style_1" href="<?php echo esc_url(OAM_Helper::$sales_representative_dashboard_link.'organization-commission/'); ?>">See All</a> 
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
<!-- 
<div class="order-block-wrap">
    <div class="order-process-dashboard heading-open-sans sales-representative-dashbaord">
        <div class="dashboard-block">
            <h3 class="block-title">Welcome to the Sales Representative Dashboard</h3>
            <div class="block-row">
                <?php 
                $title = 'Lorem ipsum';
                $content = 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Aliquid qui, assumenda adipisci tenetur nemo culpa repellendus quaerat aut magni quidem. Inventore expedita voluptas facere ipsa alias fugiat non nulla recusandae! Lorem ipsum dolor sit amet consectetur adipisicing elit. Aliquid qui, assumenda adipisci tenetur nemo culpa repellendus quaerat aut magni quidem. Inventore expedita voluptas facere ipsa alias fugiat non nulla recusandae!';
                
                echo OAM_COMMON_Custom::info_block($title, $content);
                ?>                
            </div>
            <div class="two-col-grid">
                <div class="cl-left">
                    <?php  echo do_shortcode('[recent_customers limit="3"]'); ?>
                </div>
                <div class="cl-right">
                    <?php  echo do_shortcode('[recent_organizations limit="1"]');  ?>
                </div>
            </div>
        </div>
    </div>
</div> -->