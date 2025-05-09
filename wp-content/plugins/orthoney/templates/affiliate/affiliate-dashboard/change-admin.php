<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// TODO: Verification functionality pending
global $wpdb;
$get_current_user_id = get_current_user_id();

$users_meta_table = OAM_Helper::$users_meta_table;
$users_table = OAM_Helper::$users_table;

$users = $wpdb->get_results($wpdb->prepare(
    "SELECT u.ID, u.display_name 
     FROM {$users_table} u
     JOIN {$users_meta_table} m2 ON u.ID = m2.user_id AND m2.meta_key = 'associated_affiliate_id'
     WHERE m2.meta_value = %d AND m2.user_id != %d" ,
    $get_current_user_id,$get_current_user_id
));
?>

<div class="affiliate-dashboard order-process-block">
    <h3>Change Organizations Access</h3>
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
