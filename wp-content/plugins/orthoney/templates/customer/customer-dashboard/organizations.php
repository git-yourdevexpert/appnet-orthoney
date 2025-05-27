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
    <style>
        #DataTables_Table_0_filter{display:none}
    </style>
    <div class="affiliate-dashboard order-process-block">
        <h3>All Organizations</h3>
        <!-- Search and filter options -->
        <div class="filter-container" style="display:none">
            <input type="text" id="search-affiliates" placeholder="Search Organization">
            <select id="filter-block-status">
                <option value="all">All status</option>
                <option value="1">Blocked</option>
                <option value="0">Unblocked</option>
                <option value="-1">Pending Request</option>
            </select>
                <button id="affiliate-filter-button" class="w-btn us-btn-style_2">Filter</button>
        </div>
        <div id="affiliate-organizations-results">
            <table>
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                
                if(!empty($affiliates)){
                foreach ($affiliates as $key => $affiliate){
                    $is_blocked = $result['data']['affiliates'][$key]['status'];
                    $token = $result['data']['affiliates'][$key]['token'];
                    $user_id = $affiliate['user_id'];
                    $current_url = home_url(add_query_arg([], $_SERVER['REQUEST_URI']));

                    $status_label = 'Blocked';
                        if($is_blocked == 0){
                            $status_label = 'Pending Approval';
                        }
                        if($is_blocked == 1){
                            $status_label = 'Approved';
                        }
                        
                    $orgName = get_user_meta($user_id, '_orgName', true);
                    if (empty($orgName)) {
                        $orgName = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
                    }
                        ?>
                         
                        <tr>
                            <td><div class="thead-data">Token</div><?php echo esc_html($affiliate['token']); ?></td>
                            <td><div class="thead-data">Name</div><?php echo esc_html($orgName); ?></td>
                            <td><div class="thead-data">Status</div><?php echo esc_html($status_label) ?> </td>
                            <td><div class="thead-data">Action</div>
                            <?php 
                            if($is_blocked == 0){
                                ?>
                                <a href="<?php echo $current_url.'?action=organization-link&token='.$token; ?>" class="w-btn us-btn-style_1">Accept Linking Request</a>
                                <?php
                            }else{
                            
                            ?>
                            <button class="affiliate-block-btn w-btn <?php echo ($is_blocked == 1) ? 'us-btn-style_1' : 'us-btn-style_2' ?>" 
                                data-affiliate="<?php echo esc_attr($affiliate['user_id']); ?>"
                                    data-blocked="<?php echo ($is_blocked == 1) ? '1' : '0'; ?>">
                                    <?php echo ($is_blocked == 1) ? 'Block' : 'Unblock'; ?>
                                </button>
                               <?php } ?>
                            </td>
                        </tr>
                    <?php } } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php 
    
}
?>