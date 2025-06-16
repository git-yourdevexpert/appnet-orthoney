<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$dashboard_link = ORGANIZATION_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';
// TODO: Verification functionality pending
global $wpdb;
$current_user_id = get_current_user_id();
$affiliate_id = $current_user_id;
$associated_id = get_user_meta($current_user_id, 'associated_affiliate_id', true);
if (!empty($associated_id)) {
    $affiliate_id = $associated_id;
}

$users_meta_table = OAM_Helper::$users_meta_table;
$users_table = OAM_Helper::$users_table;

$users = $wpdb->get_results($wpdb->prepare(
    "SELECT u.ID, u.display_name 
     FROM {$users_table} u
     JOIN {$users_meta_table} m2 ON u.ID = m2.user_id AND m2.meta_key = 'associated_affiliate_id'
     WHERE m2.meta_value = %d AND m2.user_id != %d" ,
    $affiliate_id,$affiliate_id
));
?>

<div class="affiliate-dashboard order-process-block">
   
    <div class="heading-title">
        <h3 class="block-title">Change Organizations Access</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
    <?php if (!empty($users)) : ?>
        <div class="filter-container">         
            <select id="userDropdown">
                <option value="">Select a User</option>
                <?php foreach ($users as $user) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>">
                        <?php echo esc_html($user->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button id="changeRoleBtn" class="us-btn-style_1">Change Role & Logout</button>
        </div>
        <div id="affiliate-results"></div>
    <?php else : ?>
        <p>First, create a team member, then it will be workable.</p>
    <?php endif; ?>
</div>
