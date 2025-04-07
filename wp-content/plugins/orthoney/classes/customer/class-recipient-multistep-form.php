<?php
// Prevent direct access
//http://appnet-orthoney.local/order-process/?pid=10104

if (!defined('ABSPATH')) {
    exit;
}

class OAM_RECIPIENT_MULTISTEP_FORM
{
    /**
     * Define class Constructor
     **/
    private static $atts_process_id;
    private static $current_user_id;
    public function __construct(){
        add_shortcode('recipient_multistep_form', array($this, 'recipient_multistep_form_handler'));
    }
    public static function init() {}

    public function recipient_multistep_form_handler($atts) {
        if (!is_user_logged_in()) {
            return OAM_COMMON_Custom::message_design_block(
                'If you want to access this page, please',
                ur_get_login_url(),
                'Login'
            );
        }

        ob_start();
    
        $failed_recipients_details = get_query_var('failed-recipients-details');
        if (!empty($failed_recipients_details)) {
            self::$atts_process_id = intval($failed_recipients_details);
        } else {
            self::$atts_process_id = 0;
        }
        self::$current_user_id = get_current_user_id();
    
        echo "<div class='order-block-wrap'>
                <div class='loader multiStepForm'>
                    <div>
                        <h2 class='swal2-title'>Processing...</h2>
                        <div class='swal2-html-container'>Please wait while we process your request.</div>
                        <div class='loader-5'></div>
                    </div>
                </div>
                <div class='order-process-block heading-open-sans'>";
    
                $setData = [];
                $csv_name = '';
                $group_name = '';
                $currentStep = 0;
            
                if (!empty($_GET['pid'])) {
                    global $wpdb;
                    $order_process_table = OAM_Helper::$order_process_table;
                    $user = self::$current_user_id;
                    $result = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM {$order_process_table} WHERE user_id = %d AND id = %d",
                        $user, intval($_GET['pid'])
                    ));
                    
                    if ($result) {
                        $setData = json_decode($result->data) ?? [];

                        // echo "<pre>";
                        // print_r( $setData);
                        // echo "</pre>";
                        $currentStep = max(0, (int) $result->step);
                        $csv_name = $result->csv_name ?? '';
                        $group_name = $result->name ?? '';
                    }
                }else{

                }
                ?>
                <form id="multiStepForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="pid" name="pid" value="<?php echo $_GET['pid'] ?? ''; ?>">
                    <?php
                    self::step_nav($currentStep);
                    self::step_1($setData);
                    self::step_2($setData);
                    self::step_3($setData, $csv_name);
                    self::step_4($setData, $currentStep, $group_name);
                    self::step_5($setData, $currentStep, $group_name);
                    ?>
                </form>
                <?php
                self::popups();
                echo "</div>
              <div class='animate-bee moveImgup'>
                  <div class='image-block'>
                      <img decoding='async' src='" . OH_PLUGIN_DIR_URL . "assets/image/honey-bee.png'>
                  </div>
              </div>
          </div>";
        
        return ob_get_clean();
    }

    public static function step_nav($currentStep = 0){ 
        ?>
        <div id="stepNav" class="tab-selections" <?php echo ((self::$atts_process_id != 0) ? 'style="display:none"' : '' ) ?>>
            <span class="step-nav-item <?php echo ($currentStep == 0  AND self::$atts_process_id == 0) ? 'active' : '' ?>" data-step="0">Step 1: Select Organization</span>
            <span class="step-nav-item <?php echo $currentStep == 1 ? 'active' : '' ?>" data-step="1">Step 2: Delivery Preference</span>
            <span class="step-nav-item <?php echo $currentStep == 2 ? 'active' : '' ?>" data-step="2">Step 3: Upload Recipients</span>
            <span class="step-nav-item <?php echo (($currentStep == 3 OR self::$atts_process_id != 0) ? 'active' : '') ?>" data-step="3">Step 4: Verify Recipients</span>
            <span class="step-nav-item <?php echo ($currentStep == 4 OR $currentStep == 5) ? 'active' : '' ?>" data-step="4">Step 5: Verify Address</span>
            <span class="step-nav-item" data-step="5">Step 6: Checkout</span>
        </div>
        <?php
    }

    public static function step_1($data){
        $affiliate = 'Orthoney';
        if (!empty($data)) {
            $affiliate = $data->affiliate_select != '' ? $data->affiliate_select : 'Orthoney';
        }
        OAM_COMMON_Custom::set_affiliate_cookie($affiliate);
        ?>
        <div class="step" id="step1">
            <div class="g-cols wpb_row via_grid laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default step-one">
                <div class="organization-step">
                    <h3 class="block-title">Select Organization</h3>
                    <div class="wpb_column vc_column_container steps-column">
                        <p>Your Affiliate is the participating organization that you want to benefit from your honey purchase.</p>
                        <div>
                            <?php
                            
                            global $wpdb;

                            $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
                            $oh_affiliate_customer_linker = OAM_Helper::$oh_affiliate_customer_linker;
                            $users_table = OAM_Helper::$users_table;
                            $query = $wpdb->prepare(
                                "SELECT a.ID, a.token, u.display_name, a.user_id 
                                FROM {$yith_wcaf_affiliates_table} AS a 
                                JOIN {$users_table} AS u ON a.user_id = u.ID 
                                WHERE a.user_id NOT IN (
                                    SELECT affiliate_id 
                                    FROM {$oh_affiliate_customer_linker} 
                                    WHERE customer_id = %d AND status != %d
                                )",
                                self::$current_user_id, 1
                            );

                            $affiliateList = $wpdb->get_results($query);

                            // $affiliateList = OAM_Helper::manage_affiliates_content('', 'blocked');
                            // $affiliateList = json_decode($affiliateList, true);
                         
                            echo '<select name="affiliate_select" id="affiliate_select" required data-error-message="Please select an affiliate.">';
                            echo '<option ' . selected($affiliate, '0', false) . ' value="Orthoney">Unaffiliated</option>';
                            
                            if (!empty($affiliateList)) {
                                foreach ($affiliateList  as $key => $data) {
                                    if($data->token != ''){
                                        $user_id = $data->user_id;
                                        $states = WC()->countries->get_states('US');
                                        $state = get_user_meta($user_id, 'billing_state', true) ?: get_user_meta($user_id, 'shipping_state', true);
                                        $city = get_user_meta($user_id, 'billing_city', true) ?: get_user_meta($user_id, 'shipping_city', true);
                                        $state_name = isset($states[$state]) ? $states[$state] : $state;
                                        $value = '[' . $data->token . '] ' . $data->display_name;
                                        if (!empty($city)) {
                                            $value .= ', ' . $city;
                                        }
                                        if (!empty($state)) {
                                            $value .= ', ' . $state_name;
                                        }

                                        echo '<option  data-token="'.$data->token.'" ' . selected($user_id, $affiliate, false) . ' value="' . esc_attr($user_id) . '">' . esc_html($value) . '</option>';
                                    }
                                }

                            }
                                echo '</select>';
                            ?>
                            <span class="error-message"></span>
                        </div>
                    </div>
                    <h4 class="content-title">Don't Have An Organization? <a href="javascript:;" class="next-with-ortHoney-affiliates">Click Here</a></h4>
                </div>
                <div class="block-btn"><button type="button" class="next w-btn us-btn-style_1">Next</button></div>
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
        $groups = [];

        if (!empty($data)) {
            $delivery_preference = (!empty($data->delivery_preference)) ? $data->delivery_preference : '';
            $multiple_address_output = $data->multiple_address_output ? $data->multiple_address_output : '';
            $upload_type_output = !empty($data->upload_type_output) ? $data->upload_type_output : '';
            $single_address_quantity = $data->single_address_quantity ? $data->single_address_quantity : '';
            $single_address_greeting = $data->single_address_greeting ? $data->single_address_greeting : '';
            $groups = isset($data->groups) ? $data->groups : [];
        }
        ?>
        <div class="step" id="step2">
            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row via_grid laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                        <div class="wpb_column vc_column_container" style="text-align:center">
                            <div class="address-block">
                                <h3>Where would you like your order delivered?</h3>
                                <label for="single_address">
                                    <input type="radio" id="single_address" name="delivery_preference" <?php echo $delivery_preference == 'single_address' ? 'checked' : '' ?> value="single_address" required
                                        data-error-message="Please select a delivery preference.">
                                    <span><img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/address.png" alt="" class="address-icon">Ship to Single Address</span>
                                </label>
                                <label for="multiple_address">
                                    <input type="radio" id="multiple_address" name="delivery_preference" <?php echo $delivery_preference == 'multiple_address' ? 'checked' : '' ?> value="multiple_address" required
                                        data-error-message="Please select a delivery preference.">
                                    <span><img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/destination.png" alt="" class="address-icon">Ship to Multiple Addresses</span>
                                </label>
                            </div>
                            <div class="address-inner">
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
                                <div class="multiple-address-order" style="<?php echo $delivery_preference == 'multiple_address' ? '' : 'display:none' ?>">
                                    <input type="hidden" id="multiple-address-output" name="multiple_address_output"
                                        value="<?php echo $multiple_address_output ?>">
                                        <div class="multiple-address-grid">
                                        <?php 
                                    $user = self::$current_user_id;
                                    $getGroupList = OAM_Helper::getGroupList($user);
                                    if(!empty($getGroupList)){
                                    ?>
                                        <label>
                                            <input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'select-group' ? 'checked' : '' ?> value="select-group" data-error-message="Please select a delivery preference.">
                                            <span>
                                                <img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/book.png" alt="" class="address-icon">
                                                Choose from Existing Recipient List
                                            </span>
                                        </label>
                                        
                                    <div class="groups-wrapper input-wrapp" style="<?php echo $upload_type_output == 'select-group' ? '' : 'display:none' ?>">
                                        <!-- <h4>Choose from existing recipient list</h4> -->
                                        <div class="bg-card">
                                            <select name="groups[]" data-error-message="Please select a Recipient List." multiple >
                                                <?php 
                                                foreach ($getGroupList as $key => $data) {
                                                    $selected = '';
                                                    if(in_array($data->id, $groups)){
                                                        $selected =  'selected';
                                                    }
                                                    echo '<option '.$selected.' value="'.$data->id.'">'.$data->name.'</option>';
                                                }
                                                ?>
                                                
                                            </select>
                                            <span class="error-message"></span>
                                        </div>
                                    </div>
                                    <?php } ?>
                                        <label>
                                            <input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'upload-csv' ? 'checked' : '' ?> value="upload-csv" data-error-message="Please select a delivery preference.">
                                            <span>
                                                <img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/file.png" alt="" class="address-icon">
                                                Upload New Recipient List
                                            </span>
                                        </label>
                                        <label>
                                            <input type="radio" name="upload_type_output" <?php echo $upload_type_output == 'add-manually' ? 'checked' : '' ?> value="add-manually" data-error-message="Please select a delivery preference."> <span><img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/contract.png" alt="" class="address-icon">
                                                Manually Add Recipient Addresses
                                            </span></label>
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
                    class="next w-btn us-btn-style_1" style="float:right">Next</button>
            </div>
        </div>
    <?php
    }

    public static function step_3($data, $csv_name = ''){
        $groups = [];
        $csv_dir = OAM_Helper::$process_recipients_csv_url;
        $greeting = '';
        $csvName = '';
        if (!empty($data)) {
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
                                <h3>Recipient List</h3>
                                <div class="file-upload field-block">
                                    <label><span class="title-block">File Upload: <small>(Accept: .csv, .xlsx, .xls)</small></span></label>
                                    <input type="file" accept=".csv, .xlsx, .xls" id="fileInput"
                                        value="<?php echo ($csv_name != '' ? $csv_dir . '' . $csv_name : '') ?>" name="csv_file"
                                        required data-error-message="Please upload a CSV file.">
                                </div>
                                <div class="rename-file field-block">
                                    <label><span class="title-block">Recipient List Name:</span>
                                    </label>
                                    <input type="text" placeholder="Enter a recipient list name for easy future reference" name="csv_name" value="<?php echo $csvName ?>"
                                        data-error-message="Please add a name for the CSV.">
                                </div>
                                <div class="textarea-div textarea-field field-block">
                                    <label>
                                        <span class="title-block">Add a Universal Greeting for All Recipients</span>
                                        <textarea name="greeting"
                                            data-error-message="Type here..."><?php echo $greeting ?></textarea>
                                        <div class="char-counter"><span>250</span> characters remaining</div>
                                    </label>
                                    <div class="description">
                                        <p>The universal greeting you enter applies to all recipients on your list. For different greetings, include them in your list before uploading; do not enter a universal greeting</p>
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
                                    <p>Save or export your list as a .csv, .xlsx, .xls before uploading.</p>
                                </div>
                                <div class="download-file">
                                    <a href="<?php echo esc_url(OH_PLUGIN_DIR_URL . 'assets/recipient_sample.csv'); ?>" class="us-btn-style_1 submit-csv-btn " download><i class="far fa-download"></i> Download Sample File</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="button-block">
                <button type="button" class="back w-btn us-btn-style_2">Back</button>
                <button type="button" class="submit_csv_file w-btn next-step us-btn-style_1" style="float:right">Next </button>
            </div>
        </div>
    <?php
    }

    public static function step_4($data, $currentStep, $group_name){
        ?>
        <div class="step" id="step4">
        <?php
            if(($currentStep == 3 OR self::$atts_process_id != 0)){
                $data = '';
                $view_all_recipients_btn_html = '';

                if(self::$atts_process_id != 0){
                    global $wpdb;
                    $order_process_table = OAM_Helper::$order_process_table;
                    $user_id = self::$current_user_id;

                    $total_items = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$order_process_table} WHERE id = %d AND user_id = %d AND step = %d AND order_id != %d AND order_type = %s",
                        self::$atts_process_id, $user_id, 5, 0 ,'multi-recipient-order'
                    ));
                    if($total_items == 0){
                         echo  'Failed recipient is not found';
                        return;
                    }
                }


                $oam_ajax = new OAM_Ajax();
                // Fetch recipient data based on 'pid' parameter
                if (isset($_GET['pid']) && $_GET['pid'] != '') {
                    $data = $oam_ajax->orthoney_get_csv_recipient_ajax_handler(self::$current_user_id, $_GET['pid'], self::$atts_process_id);
                } else {
                    $data = $oam_ajax->orthoney_get_csv_recipient_ajax_handler(self::$current_user_id, '', self::$atts_process_id);
                }

                if(self::$atts_process_id != 0){
                    $data = $oam_ajax->orthoney_get_csv_recipient_ajax_handler(self::$current_user_id, self::$atts_process_id, self::$atts_process_id);
                }
                $result = json_decode($data, true);

                if(self::$atts_process_id == 0){
                    ?>
                    <div class="heading-title">
                        <div>
                            <div class="group-name">
                                Recipient List Name: <strong><?php echo $group_name; ?></strong><button class="editProcessName far fa-edit" data-name="<?php echo $group_name; ?>" data-tippy="Edit Recipient List Name"></button>
                            </div>
                            <?php if ($result['data']['totalCount'] != 0) : ?>
                                <p class="num-count">Number of Recipients: <span><?php echo $result['data']['totalCount']; ?></span></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <button class="editRecipient btn-underline" data-popup="#recipient-manage-popup">Add New Recipient</button><br>
                                <!-- <button class="removeRecipientsAlreadyOrder btn-underline" data-tippy="Remove recipient for the already placed order">Remove recipient</button> -->
                            </div>
                        </div>
                    </div>
                    <?php
                }
                if (!empty($result) && $result['success'] == 1) { 
                    ?>
                    <div class="recipient-group-nav">
                        <?php
                        $sections = [
                            'fail'     => 'Failed',
                            'success'  => 'Success',
                            'duplicate' => 'Duplicate',
                            // 'alreadyOrder' => 'Already Ordered',
                            'new'      => 'New'
                        ];
                        if(self::$atts_process_id != 0){
                            $sections = [
                                'fail'     => 'Failed',
                            ];
                        }
                        foreach ($sections as $key => $label) {
                            $countKey = $key . 'Count';
                            if (!empty($result['data'][$countKey])) {
                                echo '<button class="scroll-section-btn" data-section="' . $key . 'CSVData">' 
                                    . $label . ' Recipient (' . $result['data'][$countKey] . ')</button>';
                            }
                        }
                        ?>
                    </div>
                    <div class="recipient-group-section <?php echo ((self::$atts_process_id != 0) ? 'failed-recipient-show' : '' ) ?>">
                        <?php
                        foreach ($sections as $key => $label) {
                            $countKey = $key . 'Count';
                            $dataKey = $key . 'Data';
                        
                            if (!empty($result['data'][$countKey])) {
                                $viewAllBtn = '';
                                if ($key != 'duplicate' && $result['data'][$countKey] > 10) {
                                    $viewAllBtn = '<button class="view-all-recipients btn-underline" data-status="0">View All Recipients</button>';
                                }
                                echo '<div id="' . $key . 'CSVData" class="table-data">' . $result['data'][$dataKey] . $viewAllBtn . '</div>';
                            }
                        }
                        ?>
                    </div>
                    <?php
                    
                }
                if(self::$atts_process_id == 0){
                    if (!empty($_GET['pid']) && empty($result['data']['newCount'])) { 
                        ?>
                        <div class="heading-title">
                            <div><h5 class="table-title">New Recipients</h5></div>
                            <div><button class="editRecipient btn-underline" data-popup="#recipient-manage-popup">Add New Recipient</button></div>
                        </div>
                        <?php 
                    }
                  
        
                    if ($result['data']['totalCount'] != 0) {
                        ?>
                        <div class="two-cta-block">
                            <a href="<?php echo get_permalink(); ?>" class="w-btn us-btn-style_1 outline-btn" data-tippy="Your order progress has been saved under Incomplete Orders.">Save Progress</a>
                            <button class="verifyRecipientAddressButton w-btn us-btn-style_1 next-step"
                                data-totalCount="<?php echo $result['data']['totalCount']; ?>"
                                data-successCount="<?php echo $result['data']['successCount']; ?>"
                                data-newCount="<?php echo $result['data']['newCount']; ?>"
                                data-failCount="<?php echo $result['data']['failCount']; ?>"
                                data-duplicateCount="<?php echo $result['data']['duplicateCount']; ?>">
                                Next
                            </button>
                        </div>
                        <?php 
                    } 
                } 
            }
            ?>
        </div>
    
        <?php
    }
    

    public static function step_5($data, $currentStep, $group_name){

        $duplicate = $data->duplicate ?? 1;
        $recipientAddressIds = $data->recipientAddressIds ?? [];
        $singleAddressCheckoutStatus = $data->singleAddressCheckoutStatus ?? '';
        $checkoutProceedStatus = $data->checkout_proceed_with_multi_addresses_status ?? '';

        
        echo '<div class="step" id="step5">';
        if (!empty($data->delivery_preference)) {
            if($currentStep == 4 OR $data->delivery_preference == 'single_address' OR $data->delivery_preference == 'multiple_address'){
                if ($data->delivery_preference == 'single_address') {
                    self::single_address_form($currentStep);
                }

                if ($data->delivery_preference == 'multiple_address' && !empty($data->pid)) {
                    $data = OAM_Helper::get_order_process_address_verified_recipient($data->pid, $duplicate, $recipientAddressIds);
                    $result = json_decode($data, true);

                    if (!empty($result) && $result['success'] == 1) {
                        echo '<div class="heading-title"><div>';
                        echo '<div class="group-name">Recipient List Name: <strong>' . $group_name . '</strong>';
                        echo '<button class="editProcessName far fa-edit" data-tippy="Edit Recipient List Name" data-name="' . $group_name . '"></button></div>';
                        echo '<p class="num-count">Number of Final Recipients: <span>' . $result['data']['totalCount'] . '</span></p>';
                        echo '</div></div>';

                        echo '<div class="recipient-group-nav">';
                        if ($result['data']['unverifiedRecordCount'] > 0) {
                            echo '<button class="scroll-section-btn" data-section="unverified-block">Unverified Addresses (' . $result['data']['unverifiedRecordCount'] . ')</button>';
                        }
                        if ($result['data']['verifiedRecordCount'] > 0) {
                            echo '<button class="scroll-section-btn" data-section="verified-block">Verified Addresses (' . $result['data']['verifiedRecordCount'] . ')</button>';
                        }
                        echo '</div>';

                        echo '<div class="recipient-group-section">';
                        self::render_recipient_block('Unverified Addresses', 'unverified-block', 'unverifiedRecord', $result['data']['unverifiedRecordCount'], $result['data']['totalCount'], $result['data']['unverifiedData']);
                        self::render_recipient_block('Verified Addresses', 'verified-block', 'verifyRecord', $result['data']['verifiedRecordCount'], $result['data']['totalCount'], $result['data']['verifiedData']);
                        echo '</div>';

                        echo '<div class="two-cta-block">';
                        echo '<button id="checkout_proceed_with_addresses_button" class="w-btn us-btn-style_1 next-step">Proceed To CheckOut</button>';
                        echo '<input type="hidden" name="processCheckoutStatus" value="' . $currentStep . '">';
                        echo '<input type="hidden" name="checkout_proceed_with_multi_addresses_status" value="' . $checkoutProceedStatus . '">';
                        if ($result['data']['unverifiedRecordCount'] > 0) {
                            echo '<button id="checkout_proceed_with_only_unverified_addresses" style="display:none" class="w-btn us-btn-style_1 outline-btn">Continue With Unverified Addresses</button>';
                        }
                        if ($result['data']['verifiedRecordCount'] > 0) {
                            echo '<button id="checkout_proceed_with_only_verified_addresses" style="display:none" class="w-btn us-btn-style_1 outline-btn">Proceed With Only Verified Addresses</button>';
                        }
                        echo '</div>';
                    }
                }
            }
        }

        echo '</div>';
    }

    private static function render_recipient_block($title, $block_id, $record_id, $recordCount, $totalCount, $data){
        if ($recordCount > 0) {
            $viewAllBtn = $recordCount > 10 ? '<button class="view-all-recipients btn-underline" data-status="0">View All Recipients</button>' : '';
            echo '<div class="' . $block_id . '" id="' . $block_id . '" data-count="'.$recordCount.'">';
            echo '<div class="block-row">';
            echo '<div class="block-inner"><h3 class="title">' . $title . '</h3>';
            echo '<p>Out of ' . $totalCount . ' Recipients, ' . $recordCount . ' are ' . strtolower($title) . '.</p></div>';
            echo '</div>';
            echo '<div id="' . $record_id . '">' . $data . $viewAllBtn . '</div>';
            echo '</div>';
        }
    }

    public static function single_address_form($currentStep){
        $user_id = self::$current_user_id;
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
                        <?php echo OAM_Helper::get_us_states_list(isset($shipping_address['state']) ? $shipping_address['state'] : ""); ?>
                    </select>
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-third-half">
                    <label for="single_order_zipcode">Zipcode:</label>
                    <input type="text" id="single_order_zipcode" name="single_order_zipcode"
                        value="<?php echo isset($shipping_address['postcode']) ? $shipping_address['postcode'] : "" ?>" required
                        data-error-message="Please enter a zip code">
                    <span class="error-message"></span>
                </div>
                <div class="form-row text-right">
                    <div>
                        <input type="hidden" name="processCheckoutStatus" value="<?php echo $currentStep; ?>">
                        <button class="w-btn us-btn-style_1 next-step" id="singleAddressCheckout">Proceed To CheckOut</button>
                    </div>
                </div>
        <?php
    }

    public static function popups(){
        echo OAM_Helper::manage_recipient_popup();
        echo OAM_Helper::view_details_recipient_popup();

        ?>
        <div id="viewAllAlreadyOrderPopup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block">
                <h3>List of orders for <span></span> received a jar this year.</h3>
                <div class="table-wrapper">
                    <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Full Name</th>
                            <th>Company Name</th>
                            <th>Address</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}

new OAM_RECIPIENT_MULTISTEP_FORM();
