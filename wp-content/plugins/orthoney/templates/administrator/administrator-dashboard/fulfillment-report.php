<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_user_id = get_current_user_id();
$redirect_back_user_id = get_transient('redirect_back_user_' . $current_user_id);

if ($redirect_back_user_id) {
    $nonce = wp_create_nonce('auto_login_' . $redirect_back_user_id);
    $back_url = home_url('/?action=auto_login&user_id=' . $redirect_back_user_id . '&nonce=' . $nonce . '&redirect_to=' . urlencode(SALES_REPRESENTATIVE_DASHBOARD_LINK));
    echo '<a href="' . esc_url($back_url) . '" class="button">Back to Previous User</a>';
}

?>
<style>
    .daterangepicker .drp-calendar {
    display: none;
    max-width: 100% !important;
}
</style>


<div class="order-block-wrap">
        <div class="order-process-dashboard">
            <div class="dashboard-block">
                <h3 class="block-title">Fulfillment Report Dashboard</h3>
                <div class="block-row">
                    <div class="fulfillment-report-wrap grid-two-col">

                        <div class="form-row gfield--width-full">
                            <label for="date_range_picker">Select date range</label>
                            <input type="text" id="date_range_picker" name="date_range_picker" placeholder="Select date range" />
                        </div>

                        <div class="form-row gfield--width-full">
                            <label for="fulfillment_send_mail">Enter email address</label>
                            <input type="text" id="fulfillment_send_mail" name="fulfillment_send_mail" placeholder="Enter email address" />
                            <span>Please enter the email address where you'd like to receive the <strong>fulfillment report.</strong></span>
                        </div>

                        <br>
                        <div class="form-row gfield--width-full">
                            <label for="fulfillment_sheet_type">
                            <input type="checkbox" id="fulfillment_sheet_type" name="fulfillment_sheet_type" />
                            <span>If you want to receive the Full export report as a spreadsheet, please check this box.</span>
                            </label>
                        </div>
                        <br>

                        <div id="date_range_error" style="color: red; font-size: 13px; margin-top: 4px;"></div>
                        <div class="form-row gfield--width-full">
                            <button id="fulfillment-report-generate_report" class="w-btn us-btn-style_2">Generate Report</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php