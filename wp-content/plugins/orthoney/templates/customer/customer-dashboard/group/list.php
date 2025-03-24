<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
$groups = OAM_Helper::getGroupList($user_id);
?>
<div class="groups-block">
    <h3>All Groups</h3>
    <?php 
    if(!empty($groups)){
        ?>
        
    <table>
        <thead>
            <tr>
                <th>Sr No</th>
                <th>Group Name</th>
                <th>Date</th>
                <th>Number of Recipients</th>
                <th>CSV Files</th>
                <th style="width:300px">Action</th>
            </tr>
        </thead>
        <tbody id="groups-data">
            <?php 
            foreach ($groups as $group) {
                ?>
                <tr>
                    <td><?php echo $group->id ?></td>
                    <td><?php echo $group->name ?></td>
                    <td><?php echo date_i18n(OAM_Helper::$date_format . ' ' . OAM_Helper::$time_format, strtotime($group->timestamp)) ?></td>
                    <td>Number of Recipients</td>
                    <td>CSV Files</td>
                    <td>
                        <a href="<?php echo $current_url.'?groupid='.$group->id ?>" class="far fa-users" data-tippy="View All Recipients"></a>
                        <!-- <a href="<?php echo $current_url.'?groupid='.$group->id ?>" class="far fa-edit" data-tippy="Edit Group Details"></a> -->
                        <button data-groupname="<?php echo $group->name ?>" data-groupid="<?php echo $group->id ?>" data-tippy="Remove Group" class="deleteGroupButton far fa-trash"></button>
                    </td>
                </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <div class="groups-pagination">
        <div id="groups-pagination"></div>
    </div>
    <?php } else{ echo OAM_COMMON_Custom::message_design_block("Group is not found."); return;}?>
</div>