<?php
// Register Shortcode to Display the Multi-Step Form
add_shortcode('custom_multistep_form', 'render_custom_multistep_form');

function render_custom_multistep_form() {
    ob_start();

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
          echo "<pre>";
          print_r(json_decode($result->data, true)); // Use -> instead of []
          echo "</pre>";
      } else {
          echo "No result found.";
      }
  }
  
?>
<form id="multiStepForm" method="post" enctype="multipart/form-data">
<input type="hidden" id="process_id" name="process_id" value="<?php echo (isset($_GET['process_id']) ? $_GET['process_id'] : '') ?>">
  <div id="stepNav">
    <span class="step-nav-item active" data-step="0">Step 1: Select Affiliate</span>
    <span class="step-nav-item" data-step="1">Step 2: Delivery Preference</span>
    <span class="step-nav-item" data-step="2">Step 3: Upload CSV</span>
    <span class="step-nav-item" data-step="3">Step 4: Verify Recipients</span>
  </div>

  <div class="step" id="step1">
    <h3>Select Affiliate</h3>
    <select name="affiliate" required data-error-message="Please select an affiliate.">
      <option value="">-- Select Affiliate --</option>
      <option value="Affiliate 1">Affiliate 1</option>
      <option value="Affiliate 2">Affiliate 2</option>
    </select>
    <span class="error-message"></span>
    <button type="button" class="next">Next</button>
  </div>

  <div class="step" id="step2" style="display: none;">
    <h3>Choose Delivery Preference</h3>
    <label><input type="radio" name="delivery_preference" value="my_address" required data-error-message="Please select a delivery preference."> Deliver to My Address Only</label>
    <label><input type="radio" name="delivery_preference" value="multiple_location" required data-error-message="Please select a delivery preference."> Ship to Multiple Locations</label>

    <select name="delivery_option" required data-error-message="Please select a delivery option.">
      <option value="">-- Select Delivery Option --</option>
      <option value="Option 1">Option 1</option>
      <option value="Option 2">Option 2</option>
    </select>
    <span class="error-message"></span>
    <button type="button" class="back">Back</button>
    <button type="button" class="next">Upload CSV</button>
    <button type="button" class="next">Add Manually</button>
  </div>

  <div class="step" id="step3" style="display: none;">
    <h3>Upload CSV</h3>
    <label>File Upload: <input type="file" name="csv_file" required data-error-message="Please upload a CSV file."></label>
    <label>Add CSV Name: <input type="text" name="csv_name" required data-error-message="Please add a name for the CSV."></label>
    <label>Greeting: <textarea name="greeting" required data-error-message="Please add a greeting."></textarea></label>
    <span class="error-message"></span>
    <button type="button" class="back">Back</button>
    <button type="button" class="submit">Submit</button>
  </div>

  <div class="step" id="step4" style="display: none;">
    <h3>Verify Recipients</h3>
    <p><strong>Selected Affiliate:</strong> <span id="verifyAffiliate"></span></p>
    <p><strong>Delivery Preference:</strong> <span id="verifyDeliveryPreference"></span></p>
    <p><strong>Delivery Option:</strong> <span id="verifyDeliveryOption"></span></p>
    <p><strong>CSV File:</strong> <span id="verifyCSVFile"></span></p>
    <p><strong>CSV Name:</strong> <span id="verifyCSVName"></span></p>
    <p><strong>Greeting:</strong> <span id="verifyGreeting"></span></p>
    <button type="button" class="back">Back</button>
    <button type="button" class="finish">Finish</button>
  </div>
</form>

<div id="popupModal" style="display: none;">
  <div class="popup-content">
    <p>Are you sure you want to proceed?</p>
    <button id="cancelButton">Cancel</button>
    <button id="nextButton">Move to Next Step</button>
  </div>
</div>

<style>
  form { max-width: 500px; margin: auto; }
  .step { padding: 20px; border: 1px solid #ccc; margin-top: 10px; }
  #popupModal { background: rgba(0, 0, 0, 0.6); position: fixed; top: 0; left: 0; width: 100%; height: 100%; justify-content: center; align-items: center; }
  .popup-content { background: #fff; padding: 20px; text-align: center; }
  #stepNav { display: flex; justify-content: space-between; margin-bottom: 20px; }
  .step-nav-item { padding: 10px 15px; background: #f0f0f0; border: 1px solid #ccc; cursor: pointer; }
  .step-nav-item.active { background: #0073e6; color: white; font-weight: bold; }
  .error-message { color: red; font-size: 14px; display: block; margin-top: 5px; }
</style>

<?php
    return ob_get_clean();
}
