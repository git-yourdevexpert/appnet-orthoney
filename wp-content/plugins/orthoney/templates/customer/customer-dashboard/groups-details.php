<?php
if (!defined('ABSPATH')) {
    exit;
}
$group_id = get_query_var('groups-details');

global $wpdb;
$user_id = get_current_user_id();


$group_table = OAM_Helper::$group_table;
$group_recipient_table = OAM_Helper::$group_recipient_table;
$order_process_table = OAM_Helper::$order_process_table;;


$group_details = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$group_table} WHERE user_id = %d AND id = %d",
    $user_id, $group_id 
));

$recipients = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$group_recipient_table} WHERE user_id = %d AND group_id = %d",
    $user_id, $group_id 
));

$csv_name = $wpdb->get_var($wpdb->prepare(
    "SELECT csv_name FROM {$order_process_table} WHERE user_id = %d AND id = %d",
    $user_id, $group_details->pid
));


if(!empty($recipients)){

?>
<div class="order-process-block">
<div class="heading-title"><h5 class="block-title"><?php echo $group_details->name ?> Recipient List</h5></div>

<div class="heading-title">
    <div>
        <div class="group-name"> 
            Recipient List Name: <strong><?php echo $group_details->name ?></strong>
            <button class="editProcessName far fa-edit" data-name="<?php echo $group_details->name ?>" data-tippy="Edit Recipient List Name"></button>
        </div>
        <p class="num-count">Number of Recipients: <span><?php echo count($recipients) ?></span></p>
    </div>
    <div>
        <?php 
        if($csv_name != ''){
            echo '<a href="'.esc_url(OAM_Helper::$process_recipients_csv_url . $csv_name).'" data-tippy="All Recipients can be downloaded" class="btn-underline"><i class="far fa-download"></i> Download All Recipients</a>';
        }
        ?>
        
    </div>
</div>


<table><thead><tr><th>Full Name</th><th>Company Name</th><th>Address</th><th>Quantity</th><th>Address Verified</th><th>Action</th></tr></thead><tbody>
<?php
foreach ($recipients as $key => $data) {
    $addressParts = array_filter([$data->address_1, $data->address_2, $data->city, $data->state, $data->zipcode]);
                if (!empty($addressParts)) {
                    $addressPartsHtml = '<td data-label="Address"><div class="thead-data">Address</div>' . implode(', ', $addressParts) . '</td>';
                } else {
                    $addressPartsHtml = '<td data-label="Address"><div class="thead-data">Address</div>-</td>';
                }
    ?>
    <tr data-id="1" data-verify="1" data-group="0">
        <td data-label="Full Name"><div class="thead-data">Full Name</div><input type="hidden" name="recipientIds[]" value="1"><?php echo $data->full_name ?></td>
        <td data-label="Company name"><div class="thead-data">Company name</div><?php echo $data->company_name ?></td>
        <?php echo $addressPartsHtml ?>
        <td data-label="Quantity"><div class="thead-data">Quantity</div><?php echo ((empty($data->quantity) || $data->quantity <= 0) ? '0' : $data->quantity) ?></td>
        <td data-label="Status"><div class="thead-data">Status</div><?php echo (($data->address_verified == 0) ? 'Failed': 'Passed') ?></td>
        <td data-label="Action"><div class="thead-data">Action</div><button class="viewRecipient far fa-eye" data-tippy="View Recipient Details" data-popup="#recipient-view-details-popup"></button><button class="editRecipient far fa-edit" data-tippy="Edit Recipient Details" data-popup="#recipient-manage-popup"></button><button data-recipientname="Michael Johnson" data-tippy="Remove Recipient" class="deleteRecipient far fa-trash"></button></td>
    </tr>
    <?php
}

?>
</tbody></table>
</div>
<?php
}
