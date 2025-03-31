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
    $affiliates = $result['data']['user_info'];
    $blocked_affiliates = $result['data']['affiliates'];    
    ?>
    <div class="affiliate-dashboard order-process-block">
        <h3>All Organizations</h3>
        <!-- Search and filter options -->
        <div class="filter-container">
            <input type="text" id="search-affiliates" placeholder="Search Organization">
            <select id="filter-block-status">
                <option value="all">All organization</option>
                <option value="blocked">Blocked</option>
                <option value="unblocked">Unblocked</option>
            </select>
                <button id="affiliate-filter-button" class="w-btn us-btn-style_2">Filter</button>
        </div>
        <div id="affiliate-results">
            <table>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if(!empty($affiliates)){
                foreach ($affiliates as $key => $affiliate){
                    $is_blocked = $result['data']['affiliates'][$key]['status'];
                    $token = $result['data']['affiliates'][$key]['token'];
                    $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));
                        ?>
                        <tr>
                            <td><div class="thead-data">Token</div><?php echo esc_html($affiliate['token']); ?></td>
                            <td><div class="thead-data">Name</div><?php echo esc_html($affiliate['display_name']); ?></td>
                            <td><div class="thead-data">Action</div>
                            <?php 
                            if($is_blocked != 0){
                            ?>
                            <button class="affiliate-block-btn w-btn <?php echo ($is_blocked == 1) ? 'us-btn-style_1' : 'us-btn-style_2' ?>" 
                                data-affiliate="<?php echo esc_attr($affiliate['ID']); ?>"
                                    data-blocked="<?php echo ($is_blocked == 1) ? '1' : '0'; ?>">
                                    <?php echo ($is_blocked == 1) ? 'Block' : 'Unblock'; ?>
                                </button>
                                <?php }else{ ?>
                                    <a href="<?php echo $current_url.'?action=organization-link&token='.$token; ?>" class="w-btn us-btn-style_1">Link to Organization</a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } }else{ ?>
                        <tr><td colspan="3">No Organization found! </td></tr>
                        <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php 
    
}
?>