Shortcode: 
Registration: [custom_registration_form]
Recipient Multi Step Form : [recipient_multistep_form] : This shortcode is used for insert recipient.


Smarty subscription. 
Auth id = 0fdfc34a-4087-0f9d-ae9c-afb52f987e78 Auth token = RXTN0yzOth5dFffkvvb6

$order_process_table = OAM_Helper::$order_process_table;
$order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
$order_process_recipient_activate_log_table = OAM_Helper::$order_process_recipient_activate_log_table;
$oh_files_upload_activity_log_table = OAM_Helper::$oh_files_upload_activity_log;



all-uploaded-csv
process-recipients-csv
group-recipients-csv

 $data = [
    [
        "Full Name" => "John Doe",
        "Company Name" => "Nimbus Solutions",
        "Address" => "with new group, with new group, Los Angeles, CA, 90001",
        "Quantity" => 2
    ],
    [
        "Full Name" => "Jane Son",
        "Company Name" => "Vertex Industries",
        "Address" => "101, Main St, New York, NY, 10001",
        "Quantity" => 3
    ],
    [
        "Full Name" => "Jane Son",
        "Company Name" => "BlueHorizon Tech",
        "Address" => "101 Main, St, New York, NY, 10001",
        "Quantity" => 4
    ]
];
