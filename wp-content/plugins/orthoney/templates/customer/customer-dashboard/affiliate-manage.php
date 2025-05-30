<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure the user is logged in and has the 'customer' role
if (!is_user_logged_in() || !current_user_can('customer')) {
    return '<p>You must be logged in as a customer to view this page.</p>';
}

ob_start();
$manage_affiliates_content = OAM_Helper::manage_affiliates_content();
$result = json_decode($manage_affiliates_content, true);

$dashboard_link = CUSTOMER_DASHBOARD_LINK;
$dashboard_link_label = 'Return to Dashboard';

if (!empty($result) && isset($result['success']) && $result['success']) {
    $affiliates = $result['data']['affiliates'];
    $blocked_affiliates = $result['data']['blocked_affiliates'];
    if(!empty($affiliates)){
    ?>
        <div class="affiliate-dashboard">
           
            <div class="heading-title">
                <h3 class="block-title">My Affiliates</h3>
                <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
            </div>
            <!-- Search and filter options -->
            <!-- <div class="filter-container">
                <input type="text" id="search-affiliates" placeholder="Search Affiliates">
                <select id="filter-block-status">
                    <option value="all">All Affiliates</option>
                    <option value="blocked">Blocked</option>
                    <option value="unblocked">Unblocked</option>
                </select>
            </div> -->
            <div id="affiliate-results">
                <table>
                    <thead>
                        <tr>
                            <th>Affiliate Code</th>
                            <th>Affiliate Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($affiliates as $affiliate):
                            $is_blocked = in_array($affiliate['ID'], $blocked_affiliates);
                            ?>
                            <tr>
                                <td><div class="thead-data">Affiliate Code</div><?php echo isset($affiliate['token']) ? esc_html($affiliate['token']) : ''; ?></td>
                                <td><div class="thead-data">Affiliate Name</div><?php echo esc_html($affiliate['display_name']); ?></td>
                                <td><div class="thead-data">Action</div>
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