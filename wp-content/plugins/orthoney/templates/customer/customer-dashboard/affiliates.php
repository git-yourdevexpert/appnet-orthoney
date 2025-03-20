<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);


if (!is_user_logged_in()) {
    $message = 'Please login to view your Customer dashboard.';
    $url = ur_get_login_url();
    $btn_name = 'Login';
    echo  OAM_COMMON_Custom::message_design_block($message, $url, $btn_name);
    return;
} 

if (!in_array('customer', $user_roles) && !in_array('administrator', $user_roles)) {
    $message = 'You do not have access to this page.';
    echo OAM_COMMON_Custom::message_design_block($message);
    return;
}


$manage_affiliates_content = OAM_Helper::manage_affiliates_content();
$result = json_decode($manage_affiliates_content, true);

if (!empty($result) && isset($result['success']) && $result['success']) {
    $affiliates = $result['data']['affiliates'];
    $blocked_affiliates = $result['data']['blocked_affiliates'];
    if(!empty($affiliates)){
    ?>
        <div class="affiliate-dashboard order-process-block">
            <h3>All Affiliates</h3>
            <!-- Search and filter options -->
            <div class="filter-container">
                <input type="text" id="search-affiliates" placeholder="Search Affiliates">
                <select id="filter-block-status">
                    <option value="all">All Affiliates</option>
                    <option value="blocked">Blocked</option>
                    <option value="unblocked">Unblocked</option>
                </select>
                 <button id="affiliate-filter-button" class="w-btn us-btn-style_2">Filter</button>
            </div>
            <div id="affiliate-results">
                <table>
                    <thead>
                        <tr>
                            <th>Affiliate Code</th>
                            <th>Affiliate Name</th>
                            <th>Block/Unblock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($affiliates as $affiliate):
                            $is_blocked = in_array($affiliate['ID'], $blocked_affiliates);
                            ?>
                            <tr>
                                <td><?php echo isset($affiliate['token']) ? esc_html($affiliate['token']) : ''; ?></td>
                                <td><?php echo esc_html($affiliate['display_name']); ?></td>
                                <td>
                                    <button class="affiliate-block-btn w-btn <?php echo $is_blocked ? 'us-btn-style_2' : 'us-btn-style_1' ?>" 
                                        data-affiliate="<?php echo esc_attr($affiliate['ID']); ?>"
                                        data-blocked="<?php echo esc_attr($is_blocked ? '1' : '0'); ?>">
                                        <?php echo $is_blocked ? 'Unblock' : 'Block'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php 
    }
}
?>