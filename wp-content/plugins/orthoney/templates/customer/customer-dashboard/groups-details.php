<?php
if (!defined('ABSPATH')) {
    exit;
}

$group_id = get_query_var('groups-details');
global $wpdb;
$user_id = get_current_user_id();

$group_table = OAM_Helper::$group_table;
$group_recipient_table = OAM_Helper::$group_recipient_table;
$order_process_table = OAM_Helper::$order_process_table;

if($group_id == 'unique_recipients'){
    
        $query = $wpdb->prepare(
            "SELECT * FROM {$group_recipient_table} WHERE user_id = %d AND visibility = 1",
            $user_id
        );
    
        $allRecords = $wpdb->get_results($query);
    
        $recordMap = [];
        $recipients = [];
    
        // Step 1: Group records by unique key
        foreach ($allRecords as $record) {
            $key = $record->full_name . '|' . 
                   str_replace($record->address_1, ',' , '' ). ' ' . 
                   str_replace($record->address_2 , ',' , '') . '|' . 
                   $record->city . '|' . 
                   $record->state . '|' . 
                   $record->zipcode;
    
            $recordMap[$key][] = $record;
        }
    
        // Step 2: Categorize records (Outside the first loop)
        foreach ($recordMap as $key => $records) {
            // If there are duplicates, add only the first record
            $recipients[] = $records[0];
        }
    
    
}

if($group_id != 'unique_recipients'){
    $group_details = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$group_table} WHERE user_id = %d AND id = %d",
            $user_id,
            $group_id
        )
    );

    $recipients = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$group_recipient_table} WHERE user_id = %d AND group_id = %d AND visibility = %d",
            $user_id,
            $group_id,
            1
        )
    );

    $csv_name = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT csv_name FROM {$order_process_table} WHERE user_id = %d AND id = %d ",
            $user_id,
            $group_details->pid
        )
    );
}
$dashboard_link = CUSTOMER_DASHBOARD_LINK.'groups/';
$dashboard_link_label = 'Return to Dashboard';

if (!empty($recipients)) :
?>

    <div class="order-process-block" id="customer-dashboard-recipient-list" data-groupid="<?php echo $group_id; ?>">
        <div class="heading-title">
            <h3 class="block-title"><?php echo ($group_id == 'unique_recipients') ? 'Unique' : ''; ?> Recipient List</h3>
             <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
        </div>

        <div class="heading-title">
            <div>
                <?php
                if($group_id != 'unique_recipients'){ 
                ?>
                <div class="group-name">
                    Recipient List Name: <strong><?php echo $group_details->name; ?></strong>
                    <button class="editProcessName far fa-edit" data-name="<?php echo $group_details->name; ?>" data-tippy="Edit Recipient List Name"></button>
                </div>
                <?php } ?>
                <p class="num-count">Number of Recipients: <span><?php echo count($recipients); ?></span></p>
            </div>
            <div>
                <?php if (!empty($csv_name)) : ?>
                    <button data-tippy="All recipients can be downloaded." id="download-failed-recipient-csv" class="btn-underline">
                        <i class="far fa-download"></i> Download All Recipients
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Company Name</th>
                    <th>Address</th>
                    <th>Quantity</th>
                    <th>Address Verified</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipients as $data) : ?>
                    <tr data-id="<?php echo $data->id; ?>">
                        <td data-label="Full Name">
                            <div class="thead-data">Full Name</div>
                            <input type="hidden" name="recipientIds[]" value="1">
                            <?php echo $data->full_name; ?>
                        </td>
                        <td data-label="Company name">
                            <div class="thead-data">Company name</div>
                            <?php echo $data->company_name; ?>
                        </td>
                        <td data-label="Address">
                            <div class="thead-data">Address</div>
                            <?php
                            $addressParts = array_filter([$data->address_1, $data->address_2, $data->city, $data->state, $data->zipcode]);
                            echo !empty($addressParts) ? implode(', ', $addressParts) : '-';
                            ?>
                        </td>
                        <td data-label="Quantity">
                            <div class="thead-data">Quantity</div>
                            <?php echo (!empty($data->quantity) && $data->quantity > 0) ? $data->quantity : '0'; ?>
                        </td>
                        <td data-label="Status">
                            <div class="thead-data">Status</div>
                            <?php
                            if(is_string($data->reasons) ){
                                $decoded_reasons = $data->reasons;
                            }else{
                                $decoded_reasons = json_decode($data->reasons, true);
                                $reasons = (is_array($decoded_reasons)) ? implode(", ", $decoded_reasons) : (string) $decoded_reasons;
                            }
                                echo ($data->address_verified == 0) ? $decoded_reasons : 'Data Validated'; 
                            ?>
                        </td>
                        <td data-label="Action">
                            <div class="thead-data">Action</div>
                            <button class="viewRecipient far fa-eye" data-tippy="View Recipient Details" data-popup="#recipient-view-details-popup"></button>
                            <button class="editRecipient far fa-edit" data-tippy="Edit Recipient Details" data-popup="#recipient-manage-popup"></button>
                            <button data-recipientname="<?php echo $data->full_name; ?>" data-tippy="Remove Recipient" class="deleteRecipient far fa-trash"></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        if($group_id != 'unique_recipients'){ 
        ?>
        <div class="heading-title"><div><button class="editRecipient btn-underline" data-popup="#recipient-manage-popup">Add New Recipient</button></div></div>
        <?php } ?>
    </div>

<?php
echo OAM_Helper::manage_recipient_popup();
echo OAM_Helper::view_details_recipient_popup();
endif;

?>
