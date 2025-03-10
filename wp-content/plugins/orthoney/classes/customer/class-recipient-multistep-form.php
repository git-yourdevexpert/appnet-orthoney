<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_RECIPIENT_MULTISTEP_FORM
{
    /**
	 * Define class Constructor
	 **/
	public function __construct() {
        add_shortcode('recipient_multistep_form', array( $this, 'recipient_multistep_form_handler' ) );
    }


    public function recipient_multistep_form_handler() {
        ob_start();

        if (!is_user_logged_in()) {
            return '<div class="login-block"><div class="login-container"><span>If you want to access this page, please</span><a href="' . esc_url(wc_get_page_permalink('myaccount')) . '" class="w-btn us-btn-style_1 login-btn">Log In </a></div><div class="animate-bee moveImgup"><div class="image-block"><img src="https://orthoney.backstagedev.com/wp-content/uploads/2025/02/honey-bee.png" /></div></div></div>';
        }
        echo "<div class='order-block-wrap'>";
        echo "<div class='order-process-block'>";
         // $validate_address = OAM_Helper::validate_address('47 W 13th', 'St', 'New York', 'New York', '10011');
        // echo "<pre>";
        // print_r($validate_address);
        // echo "</pre>";
        
       

        $setData = [];
        $csv_name = '';
        $stepData_1 = [];
        $currentStep = 0;
        if (isset($_GET['pid']) && !empty($_GET['pid'])) {
            global $wpdb;
            $order_process_table = OAM_Helper::$order_process_table;
            
            $user = get_current_user_id();
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$order_process_table} WHERE user_id = %d AND id = %d",
                $user,
                intval($_GET['pid'])
            ));
           
            if (!empty($result)) {
                if(!empty(json_decode($result->data,true))){
                    $setData  = json_decode($result->data);   
                    // echo "<pre>";
                    // print_r($setData );                 
                    // echo "</pre>";
                }
                $currentStep  = $result->step;
                $csv_name  = $result->csv_name;
                if ($currentStep < 0) {
                    $currentStep = 0;
                }
            }
        }
        ?>

<form id="multiStepForm" method="POST" enctype="multipart/form-data">
    <input type="hidden" id="pid" name="pid" value="<?php echo (isset($_GET['pid']) ? $_GET['pid'] : '') ?>">
    <?php
            self::step_nav($currentStep);
            self::step_1($setData);
            self::step_2($setData);
            self::step_3($setData, $csv_name);
            self::step_4($setData, $currentStep);
            self::step_5($setData, $currentStep);
            ?>
</form>
<?php
        self::popups();
        echo "</div>";
        echo "<div class='animate-bee moveImgup'><div class='image-block'><img decoding='async' src='https://orthoney.backstagedev.com/wp-content/uploads/2025/02/honey-bee.png'></div>
        </div>";
        echo "</div>";
        return ob_get_clean();
    }

    public static function step_nav($currentStep = 0){ ?>
<div id="stepNav" class="tab-selections">
    <span class="step-nav-item <?php echo $currentStep == 0 ? 'active' : '' ?>" data-step="0">Step 1: Select
        Affiliate</span>
    <span class="step-nav-item <?php echo $currentStep == 1 ? 'active' : '' ?>" data-step="1">Step 2: Delivery
        Preference</span>
    <span class="step-nav-item <?php echo $currentStep == 2 ? 'active' : '' ?>" data-step="2">Step 3: Upload
        Recipients</span>
    <span class="step-nav-item <?php echo $currentStep == 3 ? 'active' : '' ?>" data-step="3">Step 4: Verify
        Recipients</span>
    <span class="step-nav-item <?php echo ($currentStep == 4 OR $currentStep == 5) ? 'active' : '' ?>"
        data-step="4">Step 5: Verify Address</span>
    <span class="step-nav-item" data-step="5">Step 6: Checkout</span>
</div>
<?php
    }

    public static function step_1($data){
        $affiliate = 'Orthoney';
        if(!empty($data)){
            $affiliate = $data->affiliate_select != '' ? $data->affiliate_select : 'Orthoney';
        }
        ?>
        <div class="step" id="step1">
            <div  class="g-cols wpb_row via_grid cols_2 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                <div class="wpb_column vc_column_container steps-column">
                    <h3 class="block-title">Select Organization.</h3>
                    <?php 
                    //   $affiliateList =  OAM_Helper::manage_affiliates_content('', 'unblocked');
                    //   print($affiliateList);
                    ?>
                    <select name="affiliate_select" id="affiliate_select" required data-error-message="Please select an affiliate.">
                        <option <?php echo ($affiliate == 'OrtHoney') ? 'selected' : ''; ?> value="OrtHoney">OrtHoney (unaffiliated)</option>
                        <option <?php echo ($affiliate == 'Jennifer King') ? 'selected' : ''; ?> value="Jennifer King">[JEK], Jennifer King, Alaska</option>
                        <option <?php echo ($affiliate == 'Joseph Adams') ? 'selected' : ''; ?> value="Joseph Adams">[JOA], Joseph Adams, Lowa</option>
                        <option <?php echo ($affiliate == 'Brian Harris') ? 'selected' : ''; ?> value="Brian Harris">[BRH], Brian Harris, Alberta</option>
                    </select>
                    <span class="error-message"></span>
                    <div class="block-btn"><button type="button" class="next w-btn us-btn-style_1">Next</button></div>
                </div>
                <div class="wpb_column vc_column_container steps-column">
                        <h3 class="block-title">Not affiliated with a participating organization? Proceed with Ort Honey.</h3>
                        <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the
                            industry's standard dummy text ever since the 1500s</p>
                        <div class="block-btn"><button type="button" class="next w-btn us-btn-style_1">Next</button></div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function step_2($data){
        $delivery_preference = '';
        $multiple_address_output = '';
        $single_address_quantity = '';
        $single_address_greeting = '';
        $upload_type_output = '';
        if(!empty($data)){
            $delivery_preference = (!empty($data->delivery_preference)) ? $data->delivery_preference : '';
            $multiple_address_output = $data->multiple_address_output ? $data->multiple_address_output : '';
            $upload_type_output = !empty($data->upload_type_output) ? $data->upload_type_output : '';
            $single_address_quantity = $data->single_address_quantity ? $data->single_address_quantity : '';
            $single_address_greeting = $data->single_address_greeting ? $data->single_address_greeting : '';
        }
        ?>

        <div class="step" id="step2">
            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row via_grid laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                        <div class="wpb_column vc_column_container" style="text-align:center">
                            <div>
                                <h3>Where would you like your order delivered?</h3>
                                <label for="single_address">
                                    <input type="radio" id="single_address" name="delivery_preference" <?php echo $delivery_preference == 'single_address' ? 'checked' : '' ?> value="single_address" required
                                        data-error-message="Please select a delivery preference.">
                                    <span>Deliver to My Address Only</span>
                                </label>
                                <label for="multiple_address">
                                    <input type="radio" id="multiple_address" name="delivery_preference" <?php echo $delivery_preference == 'multiple_address' ? 'checked' : '' ?> value="multiple_address" required
                                        data-error-message="Please select a delivery preference.">
                                        <span>Deliver to Multiple Addresses</span>
                                </label>
                            </div>
                            <div style="max-width:600pxwidth: 1000px;margin:25px auto;text-align:left">
                                <div class="single-address-order greeting-box"
                                    style="<?php echo  $delivery_preference == 'single_address'  ? '' : 'display:none' ?>">
                                    <div class="bg-card">
                                        <div class="flex-column quantity-block">
                                         <span>Enter the Quantity:</span>
                                            <div class="quantity">
                                                <button class="minus" aria-label="Decrease">&minus;</button>
                                                <input type="number" class="input-box" min="1" max="10000"
                                                    <?php echo  $delivery_preference == 'single_address'  ? 'required' : '' ?>
                                                    name="single_address_quantity"
                                                    value="<?php echo  $single_address_quantity == '' ? 1 : $single_address_quantity ?>"
                                                    data-error-message="Please add a quantity.">
                                                <button class="plus" aria-label="Increase">&plus;</button>
                                            </div>
                                            <span class="error-message"></span>
                                        </div>
                                        <div class="textarea-div flex-column">
                                            <span>Add Greeting/Message:</span>
                                            <textarea name="single_address_greeting"
                                                data-error-message="Please add a greeting."><?php echo htmlspecialchars($single_address_greeting); ?></textarea>
                                            <div class="char-counter"><span>250</span> characters remaining</div>
                                        </div>
                                        <div class="w-separator size_medium"></div>
                                    </div>
                                </div>

                                <div class="multiple-address-order"  style="<?php echo $delivery_preference == 'multiple_address' ? '' : 'display:none' ?>">
                                    <input type="hidden" id="multiple-address-output" name="multiple_address_output"
                                        value="<?php echo $multiple_address_output ?>">
                                    <div class="multiple-address-grid">
                                        <label><input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'select-group' ? 'checked' : '' ?> value="select-group"
                                                data-error-message="Please select a delivery preference."> <span>Choose from existing recipient list</span></label>
                                        <label><input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'upload-csv' ? 'checked' : '' ?> value="upload-csv"
                                                data-error-message="Please select a delivery preference."> <span>Do not have a recipient list/CSV & XLSX and would like to add one.</span></label>
                                        <label><input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'add-manually' ? 'checked' : '' ?> value="add-manually"
                                                data-error-message="Please select a delivery preference."> <span>Do not have a CSV and would like to manually enter recipients.</span></label>
                                        </div>
                                        <div class="groups-wrapper input-wrapp" style="display:none">
                                        <div class="bg-card">
                                            <select name="groups[]" data-error-message="Please select a group." multiple>
                                                <option value="Option 1">Option 1</option>
                                                <option value="Option 2">Option 2</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="button-block">
                <button type="button" class="back w-btn us-btn-style_2">Back</button>
                <button type="button" value="<?php echo  $delivery_preference == 'single_address'  ? 'single-address' : '' ?>" 
                class="next w-btn us-btn-style_2" style="float:right">Next</button>
            </div>
        </div>

    <?php
    }

    public static function step_3($data, $csv_name = ''){
        $groups = [];

        $csv_dir = OAM_Helper::$process_recipients_csv_url;
        $greeting = '';
        $csvName = '';
        if(!empty($data)){
            $groups = (!empty($data->groups)) ? $data->groups : '';
            $csvName = $data->csv_name != '' ? $data->csv_name : '';
            $greeting = (!empty($data->greeting)) ? $data->greeting : '';    
        }
        ?>
        <div class="step" id="step3">

            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row via_grid cols_3-2 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                        <div class="wpb_column vc_column_container steps-column">
                            <div>
                                <h3>Upload List</h3>
                                <div class="file-upload field-block">
                                    <label><span class="title-block">File Upload: <small>(Accept: .csv, .xlsx,
                                                .xls)</small></span></label>
                                    <input type="file" accept=".csv, .xlsx, .xls" id="fileInput"
                                        value="<?php echo ($csv_name != '' ? $csv_dir . '' . $csv_name : '') ?>" name="csv_file"
                                        required data-error-message="Please upload a CSV file.">
                                </div>
                                <div class="rename-file field-block">
                                    <label><span class="title-block">Rename your file here if you wish to change the name of the
                                            file you will upload.:</span>
                                    </label>
                                    <input type="text" name="csv_name" value="<?php echo $csvName ?>"
                                        data-error-message="Please add a name for the CSV.">
                                </div>

                                <div class="textarea-div textarea-field field-block">
                                    <label>
                                        <span class="title-block">Add a Greeting for All Recipients</span>
                                        <textarea name="greeting"
                                            data-error-message="Type here..."><?php echo $greeting ?></textarea>
                                        <div class="char-counter"><span>250</span> characters remaining</div>
                                    </label>
                                    <div class="description">
                                        <p>The greeting you enter here will be applied to every recipient on your list. If you
                                            want
                                            any recipient(s) on your list to get a different greeting, your list must include
                                            your
                                            desired greeting for each recipient before you upload; do not enter a greeting here.
                                        </p>
                                    </div>
                                </div>
                                <span class="error-message"></span>
                            </div>
                        </div>
                        <div class="wpb_column vc_column_container steps-column">
                            <div class="download-file-block">
                                <h3>Download a sample file to see the correct format for your list.</h3>
                                <div class="file-lists">
                                    <div class="file-title">
                                        <p>The file should have the following fields:</p>
                                    </div>
                                    <ul>
                                        <li>Full Name</li>
                                        <li>Company Name (may be left blank)</li>
                                        <li>Mailing address</li>
                                        <li>Suite/Apt#(may be left blank)</li>
                                        <li>City</li>
                                        <li>State (2 letter abbreviation)</li>
                                        <li>Zipcode</li>
                                        <li>Quantity</li>
                                        <li>Greeting (may be left blank)</li>
                                    </ul>
                                </div>
                                <div class="file-title format">
                                    <p>*Please ensure your file is in CSV format</p>
                                </div>
                                <div class="download-file">
                                    <a href="<?php echo esc_url(OH_PLUGIN_DIR_URL . 'assets/recipient_sample.csv'); ?>"
                                        class="submit_csv_file us-btn-style_1 submit-csv-btn" download>Download Sample File</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="button-block">
                <button type="button" class="back w-btn us-btn-style_2">Back</button>
                <button type="button" class="submit_csv_file us-btn-style_1" style="float:right">Submit</button>
            </div>
        </div>
        <?php
    }

    public static function step_4($data){
        $data = '';
        $oam_ajax = new OAM_Ajax();
        
        if(isset($_GET['pid']) AND $_GET['pid'] != ''){
            $data = $oam_ajax->orthoney_get_csv_recipient_ajax_handler(get_current_user_id(),$_GET['pid'] );
        }else{
            $data = $oam_ajax->orthoney_get_csv_recipient_ajax_handler(get_current_user_id() );
        }
        
        $result = json_decode($data, true);
        ?>
        <div class="step" id="step4">
            <div class="block-row">
            <h3 class="block-title">Verify Recipients</h3>
                <?php 
                if($result['data']['totalCount'] != 0){
                    echo '<p class="num-count">Number of Recipients: <span>'.$result['data']['totalCount'].'</span> </p>';
                }
                echo '</div>';
                if(!empty( $result)){
                    if($result['success'] == 1){
                        echo '<div class="recipient-group-section">';
                        echo '<div id="failCSVData" class="table-data">'.$result['data']['failData'].'</div>';
                        echo '<div id="successCSVData" class="table-data">'.$result['data']['successData'].'</div>';
                        echo '<div id="duplicateCSVData" class="table-data">'.$result['data']['duplicateData'].'</div>';
                        echo '<div id="newCSVData" class="table-data">'.$result['data']['newData'].'</div>';
                        echo '</div>';
                    
                        if(isset($_GET['pid']) AND $_GET['pid'] != ''){
                        echo '<button class="editRecipient  w-btn us-btn-style_1" data-popup="#recipient-manage-popup">Add new Recipient</button>';
                        }
                    }
                }
                if($result['data']['totalCount'] != 0){ ?>
                <div class="two-cta-block">
                    <a href="<?php echo get_permalink(); ?>" class="w-btn us-btn-style_2">Save Progress and Re Upload CSV</a>
                    <button class="verifyRecipientAddressButton w-btn us-btn-style_2"
                        data-totalCount="<?php echo $result['data']['totalCount'] ?>"
                        data-successCount="<?php echo $result['data']['successCount'] ?>"
                        data-failCount="<?php echo $result['data']['failCount'] ?>"
                        data-duplicateCount="<?php echo $result['data']['duplicateCount'] ?>">Proceed With Address  Verification</button>
                </div>
                <?php  } ?>
            </div>
        <!-- </div> -->
    <?php
    }

    public static function step_5($data, $currentStep){
        $singleAddressCheckoutStatus = '';
        if(!empty($data)){
            $singleAddressCheckoutStatus = (!empty($data->singleAddressCheckoutStatus)) ? $data->singleAddressCheckoutStatus : '';
            $checkout_proceed_with_multi_addresses_status = (!empty($data->checkout_proceed_with_multi_addresses_status)) ? $data->checkout_proceed_with_multi_addresses_status : '';
        }
        ?>
        <div class="step" id="step5">
            <?php 
            if(!empty($data)){
                if(!empty($data->delivery_preference) && $data->delivery_preference == 'single_address'){
                    self::single_address_form($currentStep);
                }
                
                if(!empty($data->action) && $data->action == 'orthoney_order_step_process_completed_ajax' && !empty($data->pid)){
                    $data = OAM_Helper::get_order_process_address_verified_recipient($data->pid);
                    
                    $result = json_decode($data, true);
                    
                    if(!empty( $result)){
                        if($result['success'] == 1){
                            $process_name = 'unknown_'.$_GET['pid'];
                            if(!empty($result['data']['groupName'])){
                                $process_name = $result['data']['groupName'];
                            }
                            
                            echo '<div class="block-row"><p class="group-name">Group Name: <strong>' . $process_name . '</strong><button class="editProcessName far fa-edit" data-name="' . $process_name . '"></button></p></div>';
                            echo '<div class="recipient-group-section">';
                            echo '<div class="unverified-block">';
                            if ($result['data']['unverifiedRecordCount'] != 0) {
                                echo '<div class="block-row">';
                                echo '<div class="block-inner"> <h3 class="title">Unverified Addresses</h3>';
                                echo '<p>Out of ' . $result['data']['totalCount'] . ' Recipients ' . $result['data']['unverifiedRecordCount'] . ' Recipients are unverified</p> </div>';
                                echo "<button id='verified-multiple-addresses' class=' w-btn us-btn-style_1'>Verified Multiple Addresses</button>";
                                echo '</div>';
                                echo '<div id="unverifiedRecord">' . $result['data']['unverifiedData'] . '</div>';
                            }
                            echo '</div>';
                            echo '<div class="verified-block">';
                            if ($result['data']['verifiedRecordCount'] != 0) {
                                echo '<div class="block-row">';
                                echo '<div class="block-inner"> <h3 class="title">Verified Addresses</h3>';
                                echo '<p>Out of ' . $result['data']['totalCount'] . ' Recipients ' . $result['data']['verifiedRecordCount'] . ' Recipients are verified</p> </div>';
                                echo '</div>';
                                echo '<div id="verifyRecord">' . $result['data']['verifiedData'] . '</div>';
                            }
                            echo '</div>';
                            
                            echo '<div class="two-cta-block">
                            <input type="hidden" name="processCheckoutStatus" value="'.$currentStep.'">
                            <input type="hidden" name="checkout_proceed_with_multi_addresses_status" value="'.$checkout_proceed_with_multi_addresses_status.'">
                            <button id="checkout_proceed_with_only_unverified_addresses"  class="w-btn us-btn-style_2">Continue With Unverified Addresses</button>
                            <button id="checkout_proceed_with_only_verified_addresses" class="w-btn us-btn-style_2">Proceed With Only Verified Addresses</button>
                            </div>';
                        
                        echo '</div>';
                        }
                    }
                }
            }
            ?>
        </div>
    <?php
    }

    public static function single_address_form($currentStep){
        $user_id = get_current_user_id();
        $shipping_address = array(
            'address_1'  => get_user_meta($user_id, 'shipping_address_1', true),
            'address_2'  => get_user_meta($user_id, 'shipping_address_2', true),
            'city'       => get_user_meta($user_id, 'shipping_city', true),
            'state'      => get_user_meta($user_id, 'shipping_state', true),
            'postcode'   => get_user_meta($user_id, 'shipping_postcode', true),
        );
        ?>
        <div class="site-form grid-two-col">
            <div class="form-row gfield--width-half">
                <label for="single_order_address_1">Address 1:</label>
                <input type="text" id="single_order_address_1" name="single_order_address_1"
                    value="<?php echo isset($shipping_address['address_1']) ? $shipping_address['address_1'] : "" ?>" required
                    data-error-message="Please enter an Address.">
                <span class="error-message"></span>
            </div>
            <div class="form-row gfield--width-half">
                <label for="single_order_address_2">Address 2:</label>
                <input type="text" id="single_order_address_2" name="single_order_address_2"
                    value="<?php echo isset($shipping_address['address_2']) ? $shipping_address['address_2'] : "" ?>">
                <span class="error-message"></span>
            </div>
            <div class="form-row gfield--width-third-half">
                <label for="single_order_city">City:</label>
                <input type="text" id="single_order_city" name="single_order_city"
                    value="<?php echo isset($shipping_address['city']) ? $shipping_address['city'] : "" ?>" required
                    data-error-message="Please enter city.">
                <span class="error-message"></span>
            </div>
            <div class="form-row gfield--width-third-half">
                <label for="single_order_state">State:</label>
                <select id="single_order_state" name="single_order_state" required data-error-message="Please select to state.">
                    <?php echo OAM_Helper::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : "");?>
                </select>
                <span class="error-message"></span>
            </div>
            <div class="form-row gfield--width-third-half">
                <label for="single_order_zipcode">Zipcode:</label>
                <input type="text" id="single_order_zipcode" name="single_order_zipcode"
                    value="<?php echo isset($shipping_address['postcode']) ? $shipping_address['postcode'] : "" ?>" required
                    data-error-message="Please select to zipcode.">
                <span class="error-message"></span>
            </div>
        <div class="form-row">
            <div style="margin-top:15px;">
                <input type="hidden" name="processCheckoutStatus" value="<?php echo $currentStep; ?>">
                <button class="w-btn us-btn-style_2" id="singleAddressCheckout">Proceed CheckOut</button>
            </div>
    </div>
    <?php
    }

    public static function popups(){
        echo OAM_Helper::manage_recipient_popup();
    }
  

}
new OAM_RECIPIENT_MULTISTEP_FORM();