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
            return '<p>If you want to access this page, please <a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">Log In </a>.</p>';
        }
        
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

            $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
            $address_list = [];
            $address_ids = [];
            $result = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$order_process_recipient_table} WHERE  user_id = %d AND pid = %d AND visibility= %d",
                $user,
                intval($_GET['pid']),
                1
            ));
        
            if (!empty($result)) {
                foreach ($result as $data) {
                    $street = trim(($data->address_1 ?? '') . ' ' . ($data->address_2 ?? ''));
                    $address_ids[] = $data->id;
                    $address_list[] = [
                        "input_id"   => $data->id,
                        "street"     => $street ?? '',
                        "city"       => $data->city ?? '',
                        "state"      => $data->state ?? '',
                        "zipcode"    => $data->zipcode ?? '',
                        "candidates" => 10,
                    ];
                }
            }
            
            $multi_validate_address = OAM_Helper::multi_validate_address($address_list);
            
            $multi_validate_address_result = json_decode($multi_validate_address, true);

            if(!empty($multi_validate_address_result)){
                foreach($multi_validate_address_result as $data){
                    $pid = $data['input_id'];

                    $dpv_match_code = $data['analysis']['dpv_match_code'] ?? '';
                    if ($dpv_match_code !== 'N' && !empty($dpv_match_code)) {
                        
                    }else{
                        
                    }
            

                    // $update_result = $wpdb->update(
                    //     $order_process_recipient_table,
                    //     ['address_verified' => intval(1)],
                    //     ['id' => $pid]
                    // );
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
        return ob_get_clean();
    }

    public static function step_nav($currentStep = 0){
        ?>
        <div id="stepNav">
            <span class="step-nav-item <?php echo $currentStep == 0 ? 'active' : '' ?>" data-step="0">Step 1: Select Affiliate</span>
            <span class="step-nav-item <?php echo $currentStep == 1 ? 'active' : '' ?>" data-step="1">Step 2: Delivery Preference</span>
            <span class="step-nav-item <?php echo $currentStep == 2 ? 'active' : '' ?>" data-step="2">Step 3: Upload Recipients</span>
            <span class="step-nav-item <?php echo $currentStep == 3 ? 'active' : '' ?>" data-step="3">Step 4: Verify Recipients</span>
            <span class="step-nav-item <?php echo ($currentStep == 4 OR $currentStep == 5) ? 'active' : '' ?>" data-step="4">Step 5: Verify Address</span>
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
            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row via_grid cols_2 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                        <div class="wpb_column vc_column_container">
                            <div>
                                <h3>Select Organization.</h3>
                                <select name="affiliate_select" id="affiliate_select" required data-error-message="Please select an affiliate.">
                                    <option <?php echo ($affiliate == 'OrtHoney') ? 'selected' : ''; ?> value="OrtHoney">OrtHoney (unaffiliated)</option>
                                    <option <?php echo ($affiliate == 'Jennifer King') ? 'selected' : ''; ?> value="Jennifer King">[JEK], Jennifer King, Alaska</option>
                                    <option <?php echo ($affiliate == 'Joseph Adams') ? 'selected' : ''; ?> value="Joseph Adams">[JOA], Joseph Adams, Lowa</option>
                                    <option <?php echo ($affiliate == 'Brian Harris') ? 'selected' : ''; ?> value="Brian Harris">[BRH], Brian Harris, Alberta</option>
                                </select>
                                
                                <span class="error-message"></span>
                                <button type="button" class="next w-btn us-btn-style_1">Next</button>
                            </div>
                        </div>
                        <div class="wpb_column vc_column_container">
                            <div>
                                <h4>Not affiliated with a participating organization? Proceed with Ort Honey.</h4>
                                <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s</p>
                                <button type="button" class="next w-btn us-btn-style_1">Next</button>
                            </div>
                        </div>
                    </div>
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
                                    <input type="radio" id="single_address" name="delivery_preference" <?php echo $delivery_preference == 'single_address' ? 'checked' : '' ?> value="single_address" required data-error-message="Please select a delivery preference.">
                                    Deliver to My Address Only
                                </label>
                                <label for="multiple_address">
                                    <input type="radio" id="multiple_address" name="delivery_preference" <?php echo $delivery_preference == 'multiple_address' ? 'checked' : '' ?> value="multiple_address" required data-error-message="Please select a delivery preference.">
                                    Deliver to Multiple Addresses
                                </label>
                       
                            </div>
                        
                            <div style="max-width:600pxwidth: 1000px;margin:25px auto;text-align:left">
                                <div class="single-address-order" style="<?php echo  $delivery_preference == 'single_address'  ? '' : 'display:none' ?>">
                                    <label>Enter the Quantity: 
                    
                                        <div class="quantity">
                                            <button class="minus" aria-label="Decrease">&minus;</button>
                                            <input type="number" class="input-box"  min="1" max="10000" <?php echo  $delivery_preference == 'single_address'  ? 'required' : '' ?>  name="single_address_quantity" value="<?php echo  $single_address_quantity == '' ? 1 : $single_address_quantity ?>" data-error-message="Please add a quantity.">
                                            <button class="plus" aria-label="Increase">&plus;</button>
                                        </div>
                                    <span class="error-message"></span>
                                    </label>
                                    
                                    <label>Add Greeting/Message:</label>
                                    <div class="textarea-div">
                                        <textarea name="single_address_greeting" data-error-message="Please add a greeting." ><?php echo htmlspecialchars($single_address_greeting); ?></textarea>
                                        <div class="char-counter"><span>250</span> characters remaining</div>
                                    </div>
                                    
                                    <div class="w-separator size_medium"></div>
                                </div>

                                <div class="multiple-address-order" style="<?php echo $delivery_preference == 'multiple_address' ? '' : 'display:none' ?>">
                                
                                    <input type="hidden" id="multiple-address-output" name="multiple_address_output" value="<?php echo $multiple_address_output ?>">

                                    <label>
                                        <input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'select-group' ? 'checked' : ''  ?> value="select-group" data-error-message="Please select a delivery preference."> Choose from existing recipient list</label>
                                    <br>
                                    <label>
                                        <input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'upload-csv' ? 'checked' : ''  ?>  value="upload-csv" data-error-message="Please select a delivery preference."> Do not have a recipient list/CSV & XLSX and would like to add one.</label>
                                    <br>
                                    <label>
                                        <input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'add-manually' ? 'checked' : ''  ?>  value="add-manually" data-error-message="Please select a delivery preference."> Do not have a CSV and would like to manually enter recipients.</label>
                                    <br>

                                    <div class="groups-wrapper" style="display:none">
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
            <button type="button" class="back w-btn us-btn-style_2">Back</button>
            <button type="button" value="<?php echo  $delivery_preference == 'single_address'  ? 'single-address' : '' ?>" class="next w-btn us-btn-style_2" style="float:right">Next</button>
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
                        <div class="wpb_column vc_column_container">
                            <div>
                                <h3>Upload List</h3>
                                <label>File Upload: <small>(Accept: .csv, .xlsx, .xls)</small><br>
                                    <input type="file" accept=".csv, .xlsx, .xls"  id="fileInput" value="<?php echo ($csv_name != '' ?  $csv_dir.''.$csv_name :'') ?>" name="csv_file" required data-error-message="Please upload a CSV file.">
                                </label>
                                
                                <label>Rename your file here if you wish to change the name of the file you will upload.: 
                                    <input type="text" name="csv_name" value="<?php echo $csvName ?>"  data-error-message="Please add a name for the CSV.">
                                </label>
                               

                                <div class="textarea-div">
                                    <label>
                                        Add a Greeting for All Recipients
                                        <textarea name="greeting" data-error-message="Type here..."><?php echo  $greeting ?></textarea>
                                        <div class="char-counter"><span>250</span> characters remaining</div>
                                    </label>
                                    <p>The greeting you enter here will be applied to every recipient on your list. If you want any recipient(s) on your list to get a different greeting, your list must include your desired greeting for each recipient before you upload; do not enter a greeting here.</p>
                                </div>
                                <span class="error-message"></span>
                            </div>
                        </div>
                        <div class="wpb_column vc_column_container">
                            <div>
                               <h3>Download a sample file to see the correct format for your list.</h3>
                               <p>The file should have the following fields:</p>
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
                               <p>*Please ensure your file is in CSV format</p>

                               <a href="<?php echo esc_url( OH_PLUGIN_DIR_URL . 'assets/recipient_sample.csv' ); ?>" class="submit_csv_file us-btn-style_2" download>Download Sample File</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="button" class="back w-btn us-btn-style_2">Back</button>
            <button type="button" class="submit_csv_file us-btn-style_1" style="float:right">Submit</button>
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
            
            <h3>Verify Recipients</h3>
            <?php 
            if($result['data']['totalCount'] != 0){
                echo '<p>Number of Recipients: '.$result['data']['totalCount'].'</p>';
            }
            
            if(!empty( $result)){
                if($result['success'] == 1){
                    echo '<div class="recipient-group-section">';
                    echo '<div id="failCSVData">'.$result['data']['failData'].'</div>';
                    echo '<div id="successCSVData">'.$result['data']['successData'].'</div>';
                    echo '<div id="duplicateCSVData">'.$result['data']['duplicateData'].'</div>';
                    echo '<div id="newCSVData">'.$result['data']['newData'].'</div>';
                    echo '</div>';
                
                    if(isset($_GET['pid']) AND $_GET['pid'] != ''){
                    echo '<button class="editRecipient  w-btn us-btn-style_1" data-popup="#recipient-manage-popup">Add new Recipient</button>';
                    }
                }
            }
            if($result['data']['totalCount'] != 0){
            ?>
            
            <div style="margin-top:15px;">
            <a href="<?php echo get_permalink(); ?>" class="w-btn us-btn-style_2">Save Progress and Re Upload CSV</a>
            <button data-popup="#verify-recipient-address-popup" class="verifyRecipientAddressButton w-btn us-btn-style_2" data-totalCount="<?php echo $result['data']['totalCount'] ?>" data-successCount="<?php echo $result['data']['successCount'] ?>" data-failCount="<?php echo $result['data']['failCount'] ?>" data-duplicateCount="<?php echo $result['data']['duplicateCount'] ?>">Proceed With Address Verification</button>
            </div>
            <?php  } ?>
            
        </div>
        <?php
    }

    public static function step_5($data, $currentStep){
        $singleAddressCheckoutStatus = '';
        if(!empty($data)){
            $singleAddressCheckoutStatus = (!empty($data->singleAddressCheckoutStatus)) ? $data->singleAddressCheckoutStatus : '';
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
                            
                            echo '<p>Group Name: <strong>'.$process_name.'</strong><button class="editProcessName far fa-edit" data-name="'.$process_name.'"></button></p>';
                            echo '<div class="recipient-group-section">';
                            if($result['data']['unverifiedRecordCount'] != 0){
                                echo '<h3>Unverified Addresses</h3>';
                                echo '<p>Out of '.$result['data']['totalCount'].' Recipients '.$result['data']['unverifiedRecordCount'].' Recipients are unverified</p>';
                                echo '<div id="unverifiedRecord">'.$result['data']['unverifiedData'].'</div>';
                            }
                            if($result['data']['verifiedRecordCount'] != 0){
                                echo '<h3>Verified Addresses</h3>';
                                echo '<p>Out of '.$result['data']['totalCount'].' Recipients '.$result['data']['verifiedRecordCount'].' Recipients are verified</p>';
                                
                                echo '<div id="verifyRecord">'.$result['data']['verifiedData'].'</div>'; 
                            }
                            
                            echo '<div style="margin-top:15px;">
                                <button class="w-btn us-btn-style_2">Continue With Unverified Addresses</button>
                                <button class="verifyRecipientAddressButton w-btn us-btn-style_2" >Proceed With Only Verified Addresses</button>
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
            <div>
                <label for="single_order_address_1">Address 1:</label>
                <input type="text" id="single_order_address_1" name="single_order_address_1" value="<?php echo isset($shipping_address['address_1']) ? $shipping_address['address_1'] : "" ?>" required data-error-message="Please enter an Address.">
                <span class="error-message"></span>
            </div>
            <div>
                <label for="single_order_address_2">Address 2:</label>
                <input type="text" id="single_order_address_2" name="single_order_address_2" value="<?php echo isset($shipping_address['address_2']) ? $shipping_address['address_2'] : "" ?>">
                <span class="error-message"></span>
            </div>
            <div>
                <label for="single_order_city">City:</label>
                <input type="text" id="single_order_city" name="single_order_city" value="<?php echo isset($shipping_address['city']) ? $shipping_address['city'] : "" ?>" required data-error-message="Please enter city.">
                <span class="error-message"></span>
            </div>
            <div>
                <label for="single_order_state">State:</label>
                <select id="single_order_state" name="single_order_state" required data-error-message="Please select to state.">
                    <?php
                    echo OAM_Helper::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : "") 
                    ?>
                </select>
                <span class="error-message"></span>
            
            </div>
            
            <div>
                <label for="single_order_zipcode">Zipcode:</label>
                <input type="text" id="single_order_zipcode" name="single_order_zipcode" value="<?php echo isset($shipping_address['postcode']) ? $shipping_address['postcode'] : "" ?>" required  data-error-message="Please select to zipcode.">
                <span class="error-message"></span>
            </div>
            <div>
            <div style="margin-top:15px;">
                <input type="hidden" name="processCheckoutStatus" value="<?php echo $currentStep; ?>">
            <button class="w-btn us-btn-style_2" id="singleAddressCheckout">Proceed CheckOut</button>
            </div>
        <?php
    }

    public static function popups(){
        echo OAM_Helper::manage_recipient_popup();
    }
  

}
new OAM_RECIPIENT_MULTISTEP_FORM();