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
        $setData = [];
        $stepData_1 = [];
        $currentStep = 0;
        if (isset($_GET['process_id']) && !empty($_GET['process_id'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'order_process';
            $user = get_current_user_id();
        
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE user_id = %d AND id = %d",
                $user,
                intval($_GET['process_id'])
            ));
        
           
            if (!empty($result)) {
                if(!empty(json_decode($result->data,true))){
                    $setData  = json_decode($result->data);
                    echo "<pre>";
                    print_r( $setData);
                    echo "</pre>";
                }
                $currentStep  = $result->step;
                if ($currentStep < 0) {
                    $currentStep = 0;
                }
            }
        }
        
        ?>

        <form id="multiStepForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" id="process_id" name="process_id" value="<?php echo (isset($_GET['process_id']) ? $_GET['process_id'] : '') ?>">
        <?php
         self::step_nav($currentStep);
         self::step_1($setData);
         self::step_2($setData);
         self::step_3($setData);
         self::step_4($setData);
         self::csv_popup();
        ?>
        </form>
        <?php
        return ob_get_clean();
    }

    public static function step_nav($currentStep = 0){
        echo $currentStep;
        ?>
        <div id="stepNav">
            <span class="step-nav-item <?php echo $currentStep == 0 ? 'active' : '' ?>" data-step="0">Step 1: Select Affiliate</span>
            <span class="step-nav-item <?php echo $currentStep == 1 ? 'active' : '' ?>" data-step="1">Step 2: Delivery Preference</span>
            <span class="step-nav-item <?php echo $currentStep == 2 ? 'active' : '' ?>" data-step="2">Step 3: Upload CSV</span>
            <span class="step-nav-item <?php echo $currentStep == 3 ? 'active' : '' ?>" data-step="3">Step 4: Verify Recipients</span>
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
                                <h3>Select Affiliate</h3>
                                <select name="affiliate_select" id="affiliate_select" required data-error-message="Please select an affiliate.">
                                    <option <?php echo ($affiliate == 'Orthoney') ? 'selected' : ''; ?> value="Orthoney">Orthoney</option>
                                    <option <?php echo ($affiliate == 'Affiliate 1') ? 'selected' : ''; ?> value="Affiliate 1">Affiliate 1</option>
                                    <option <?php echo ($affiliate == 'Affiliate 2') ? 'selected' : ''; ?> value="Affiliate 2">Affiliate 2</option>
                                </select>
                                
                                <span class="error-message"></span>
                                <button type="button" class="next w-btn us-btn-style_1">Next</button>
                            </div>
                        </div>
                        <div class="wpb_column vc_column_container">
                            <div>
                                <h4>Don't have an affiliate? Proceed with Ort Honey.</h4>
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
        if(!empty($data)){
            $delivery_preference = (!empty($data->delivery_preference)) ? $data->delivery_preference : '';
            $multiple_address_output = $data->multiple_address_output ? $data->multiple_address_output : '';
            $single_address_quantity = $data->single_address_quantity ? $data->single_address_quantity : '';
            $single_address_greeting = $data->single_address_greeting ? $data->single_address_greeting : '';
        }
        ?>
        <div class="step" id="step2">

            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row via_grid cols_2 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                        <div class="wpb_column vc_column_container">
                            <div>
                                <h3>Choose Delivery Preference</h3>
                                <label><input type="radio" name="delivery_preference" <?php echo $delivery_preference == 'single_address' ? 'checked' : ''  ?> value="single_address" required data-error-message="Please select a delivery preference."> Deliver to My Address Only</label>
                                <label><input type="radio" name="delivery_preference" <?php echo $delivery_preference == 'multiple_address' ? 'checked' : ''  ?> value="multiple_address" required data-error-message="Please select a delivery preference."> Ship to Multiple Locations</label>
                            </div>
                        </div>
                        <div class="wpb_column vc_column_container">
                            <div>
                                <div class="single-address-order" style="<?php echo  $delivery_preference == 'single_address'  ? '' : 'display:none' ?>">
                                    <label>Quantity: <input type="number" <?php echo  $delivery_preference == 'single_address'  ? 'required' : '' ?>  name="single_address_quantity" value="<?php echo  $single_address_quantity ?>" data-error-message="Please add a quantity." min="1">
                                    <span class="error-message"></span>
                                    </label>
                                    
                                    <label>Greeting: <textarea name="single_address_greeting" data-error-message="Please add a greeting." ><?php echo htmlspecialchars($single_address_greeting); ?></textarea></label>
                                    <button type="button" value="single-address" class="next w-btn us-btn-style_1">Next</button>
                                    <div class="w-separator size_medium"></div>
                                </div>
                                <div class="multiple-address-order" style="<?php echo $delivery_preference == 'multiple_address' ? '' : 'display:none' ?>">
                                    <select name="groups[]" data-error-message="Please select a group." multiple>
                                        <option value="Option 1">Option 1</option>
                                        <option value="Option 2">Option 2</option>
                                    </select>
                                    <span class="error-message"></span>
                                    <input type="hidden" id="multiple-address-output" name="multiple_address_output" value="<?php echo $multiple_address_output ?>">
                                    <button type="button" value="select-group" class="next w-btn us-btn-style_1">Submit with Group</button>
                                    <button type="button" value="upload-csv" class="next w-btn us-btn-style_1">Upload CSV</button>
                                    <button type="button" value="add-manually" class="next w-btn us-btn-style_1">Add Manually</button>
                                </div>
                            </div>
                        </div>
                    </div>                   
                </div>
            </div>            
            <button type="button" class="back w-btn us-btn-style_2">Back</button>
        </div>

        <?php
    }

    public static function step_3($data){
        $groups = [];
        $csv_name = '';
        $greeting = '';
        if(!empty($data)){
            $groups = (!empty($data->groups)) ? $data->groups : '';
            $csv_name = (!empty($data->csv_name)) ? $data->csv_name : '';
            $greeting = (!empty($data->greeting)) ? $data->greeting : '';
            
        }
        ?>
         <div class="step" id="step3">
            <h3>Upload CSV</h3>
            <label>File Upload: <input type="file" id="fileInput" name="csv_file" required data-error-message="Please upload a CSV file."></label>
            <label>Add CSV Name: <input type="text" name="csv_name" value="<?php echo $csv_name ?>"  data-error-message="Please add a name for the CSV."></label>
            <label>Greeting: <textarea name="greeting" data-error-message="Please add a greeting."><?php echo  $greeting ?></textarea></label>
            <span class="error-message"></span>
            <button type="button" class="back w-btn us-btn-style_2">Back</button>
            <button type="button" class="submit_csv_file us-btn-style_1">Submit</button>
        </div>
        <?php
    }

    public static function step_4($data){
        ?>
        <div class="step" id="step4">
            <h3>Verify Recipients</h3>
        </div>
        <?php
    }
    public static function csv_popup(){
        ?>
        <div id="popupModal" style="display: none;">
            <div class="popup-content">
            <div id="progress-wrapper" style="display: none;">
            <progress id="progress-bar" value="0" max="100"></progress>
            <span id="progress-percentage">0%</span>
        </div>
        <div id="message"></div>
                <p>Are you sure you want to proceed?</p>
                <button id="cancelButton">Cancel</button>
                <button id="nextButton">Move to Next Step</button>
            </div>
        </div>
        <?php
    }
  

}
new OAM_RECIPIENT_MULTISTEP_FORM();