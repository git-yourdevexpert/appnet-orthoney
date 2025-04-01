<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="order-block-wrap">
    <div class="order-process-dashboard heading-open-sans">
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
</div>