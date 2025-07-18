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
        
        global $wpdb;
        $order_process_table = OAM_Helper::$order_process_table;
        if (!is_user_logged_in()) {
            // return OAM_COMMON_Custom::message_design_block(
            //     'If you want to access this page, please',
            //     ur_get_login_url(),
            //     'Login'
            // );
            wp_redirect(home_url('/login/'));
            exit;
        }

        
         if (isset($_GET['pid']) && !empty($_GET['pid'])) {
            $processExistQuery = $wpdb->prepare("
            SELECT visibility
            FROM {$order_process_table}
            WHERE user_id = %d 
            AND id = %d 
            AND visibility = %d 
            ", get_current_user_id(), $_GET['pid'], 0);

            $processExistResult = $wpdb->get_var($processExistQuery);

            if ($processExistResult === '0' || $processExistResult === 0) {
                return OAM_COMMON_Custom::message_design_block(
                    'This order has been deleted. Please create a new order.',
                    get_the_permalink(),
                    'Create a New Order'
                );
            }
         }

        ob_start();

       $current_date = current_time('Y-m-d H:i:s');

        $ort_bypass_user = get_field('ort_bypass_user', 'option')?:[];
        $user = wp_get_current_user();
        $roles = $user->roles;
        $status = false;

        $all_admin_bypass = get_field('all_admin_bypass', 'option')?: 0;
        $all_organization_bypass = get_field('all_organization_bypass', 'option')?: 0;
        // If administrator and admin bypass is enabled
        if ( $all_admin_bypass == 1 && in_array('administrator', $roles)) {
            $status = true;
        }

        // If organization user and org bypass is enabled
        if ($all_organization_bypass == 1 && in_array('yith_affiliate', $roles)) {
            $status = true;
        }

        // If user ID is in the bypass user list
        if (in_array(get_current_user_id(), $ort_bypass_user)) {
            $status = true;
        }

        if($status === false){
            $season_start_date = get_field('season_start_date', 'option');
            $season_end_date   = get_field('season_end_date', 'option');

            // Convert all dates to timestamps
            $current_timestamp      = strtotime($current_date);
            $season_start_timestamp = strtotime($season_start_date);
            $season_end_timestamp   = strtotime($season_end_date);

            // Check if current date is within the season range
            $is_within_range = ( $current_timestamp >= $season_start_timestamp && $current_timestamp <= $season_end_timestamp );

            if ( $is_within_range === false ) {
                echo do_shortcode("[season_start_end_message_box type='order']");
                return;
            }
        }
            
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
                if (!empty($failed_recipients_details)) {
                    $dashboard_link = CUSTOMER_DASHBOARD_LINK.'failed-recipients/';
                    $dashboard_link_label = 'Return to Dashboard';
                    ?>
                     <div class="heading-title">
                        <h3 class="block-title">#<?php echo intval($failed_recipients_details) ?> Order's Failed Recipient(s) Lists</h3>
                        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
                    </div>
                    <?php 
                }
    
                $setData = [];
                $csv_name = '';
                $group_name = '';
                $currentStep = 0;
            
                if (!empty($_GET['pid'])) {
                    
                   
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
            <span class="step-nav-item <?php echo ($currentStep == 0  AND self::$atts_process_id == 0) ? 'active' : '' ?>" data-step="0">Step 1: Select an Organization</span>
            <span class="step-nav-item <?php echo $currentStep == 1 ? 'active' : '' ?>" data-step="1">Step 2: Order Method</span>
            <span class="step-nav-item <?php echo $currentStep == 2 ? 'active' : '' ?>" data-step="2">Step 3: Upload Recipients</span>
            <span class="step-nav-item <?php echo (($currentStep == 3 OR self::$atts_process_id != 0) ? 'active' : '') ?>" data-step="3">Step 4: Add/Review Recipients</span>
            <span class="step-nav-item <?php echo ($currentStep == 4 OR $currentStep == 5) ? 'active' : '' ?>" data-step="4">Step 5: Verify Addresses</span>
            <span class="step-nav-item" data-step="5">Step 6: Checkout</span>
        </div>
        <?php
    }

    public static function step_1($data){
        $affiliate = 'Orthoney';
        if (!empty($data)) {
            $affiliate = ($data->affiliate_select == '' || $data->affiliate_select == 0 )  ?  'Orthoney' : $data->affiliate_select ;
        }
        OAM_COMMON_Custom::set_affiliate_cookie($affiliate);
        ?>
        <div class="step" id="step1">
            <div class="heading-title">
                <div>
                    
                </div>
                <div>
                    <?php echo self::badge(0,0) ?>
                </div>
            </div>
            <div class="g-cols wpb_row via_grid laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default step-one">
                <div class="organization-step">
                    <h3 class="block-title">Select an Organization</h3>
                    <div class="wpb_column vc_column_container steps-column">
                        <p>To begin your order, please select an organization you'd like to support and then you can proceed.</p>
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
                                    WHERE customer_id = %d AND (status = %d OR status = -1)
                                )
                                AND a.enabled = 1 AND a.banned = 0 
                                ORDER BY a.token",
                                self::$current_user_id, 0
                            );
                            

                            $affiliateList = $wpdb->get_results($query);

                            $aha_item = null;
                            $reordered = [];

                            // Make sure $affiliateList is indexed properly
                            $affiliateList = array_values($affiliateList);

                            // Separate the item with token 'ATL'
                            if (!empty($affiliateList)) {
                                foreach ($affiliateList as $index => $item) {
                                    if ($item->token === 'ATL') {
                                        $aha_item = $item;
                                        unset($affiliateList[$index]);
                                        break;
                                    }
                                }
                            }

                            // Re-index the array after unsetting
                            $affiliateList = array_values($affiliateList);

                            // Add the ALT item first if found
                            if ($aha_item) {
                                $reordered[] = $aha_item;
                            }

                            // Append the remaining items
                            $affiliateListreordered = array_merge($reordered, $affiliateList);

                            // $affiliateList = OAM_Helper::manage_affiliates_content('', 'blocked');
                            // $affiliateList = json_decode($affiliateList, true);
                         
                            echo '<select name="affiliate_select" id="affiliate_select" required data-error-message="Please select an Organization.">';
                           echo '<option></option><option data-token="'.$data->token.'" ' . selected($affiliate, '0', false) . ' value="Orthoney">Honey from the Heart</option>';
                            
                            if (!empty($affiliateListreordered)) {
                                foreach ($affiliateListreordered  as $key => $data) {
                                    if($data->token != ''){
                                        $user_id = $data->user_id;
                                        $states = WC()->countries->get_states('US');
                                        $state = get_user_meta($user_id, '_yith_wcaf_state', true);
                                        
                                        if (empty($state)) {
                                            $state = get_user_meta($user_id, 'billing_state', true) ?: get_user_meta($user_id, 'shipping_state', true);
                                        }

                                        $city = get_user_meta($user_id, '_yith_wcaf_city', true);
                                        if (empty($city)) {
                                            $city = get_user_meta($user_id, 'billing_city', true) ?: get_user_meta($user_id, 'shipping_city', true);
                                        }

                                        $orgName = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
                                        if (empty($orgName)) {
                                            $orgName = get_user_meta($user_id, '_orgName', true);
                                        }

                                        $state_name = isset($states[$state]) ? $states[$state] : $state;
                                        $value = '[' . $data->token . '] ' . $orgName ?:$data->display_name;
                                        if (!empty($city)) {
                                            $value .= ', ' . $city;
                                        }
                                        if (!empty($state)) {
                                            $value .= ', ' . $state_name;
                                        }
                                         $selected = '';
                                    if (empty($affiliate) && $data->token === 'ATL') {
                                        $selected = 'selected';
                                    } else {
                                        $selected = selected($user_id, $affiliate, false);
                                    }

                                    echo '<option data-token="' . esc_attr($data->token) . '" ' . $selected . ' value="' . esc_attr($user_id) . '">' . esc_html($value) . '</option>';
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
                <div class="block-btn"><button type="button" class="next w-btn us-btn-style_1">Select Order Method</button></div>
            </div>
        </div>
    <?php
    }

    public static function step_2($data){
        $delivery_preference = '';
        $delivery_preference = '';
        $multiple_address_output = '';
        $single_address_quantity = '';
        $single_address_greeting = '';
        $upload_type_output = '';
        $groups = [];
        $affiliate_select = '';
        $upload_type_output_process_name = '';

        if (!empty($data)) {
            $delivery_preference = (!empty($data->delivery_preference)) ? $data->delivery_preference : '';
            $affiliate_select = (!empty($data->affiliate_select)) ? $data->affiliate_select : 'Orthoney';
            $multiple_address_output = $data->multiple_address_output ? $data->multiple_address_output : '';
            $upload_type_output = !empty($data->upload_type_output) ? $data->upload_type_output : '';
            $upload_type_output_process_name = !empty($data->upload_type_output_process_name) ? $data->upload_type_output_process_name : '';
            $single_address_quantity = $data->single_address_quantity ? $data->single_address_quantity : '';
            $single_address_greeting = $data->single_address_greeting ? $data->single_address_greeting : '';
            $groups = isset($data->groups) ? $data->groups : [];
            $orders = isset($data->orders) ? $data->orders : [];
        }
        ?>
        <div class="step" id="step2">
            <div class="heading-title organization_data_show">
                <div>
                    <?php echo self::organization_data($affiliate_select) ?>
                </div>
                <div>
                    <?php echo self::badge(0,0) ?>
                </div>
            </div>
            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row via_grid laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                        <div class="wpb_column vc_column_container" style="text-align:center">
                            <div class="address-block">
                                <h3>How Would You Like to Enter Your Order?</h3>
                                <label for="single_address" style="display: none">
                                    <input type="radio" id="single_address" name="delivery_preference" <?php echo $delivery_preference == 'single_address' ? 'checked' : '' ?> value="single_address" required
                                        data-error-message="Please select a delivery preference.">
                                    <span><img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/address.png" alt="" class="address-icon">Ship to Single Address</span>
                                </label>
                                <label for="multiple_address" style="display: none">
                                    <input type="radio" id="multiple_address" name="delivery_preference" checked value="multiple_address" required
                                        data-error-message="Please select a delivery preference.">
                                    <span><img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/destination.png" alt="" class="address-icon">Ship to Multiple Addresses</span>
                                </label>
                                <span class="error-message" style="display:none">Please Choose Your Shipping Type.</span>
                            </div>
                            <div class="address-inner" style="margin-top:0px">
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
                                            <textarea name="single_address_greeting" data-error-message="Please add a greeting."><?php echo htmlspecialchars($single_address_greeting); ?></textarea>
                                            <div class="char-counter"><span>100</span> characters remaining</div>
                                        </div>
                                        <div class="w-separator size_medium"></div>
                                    </div>
                                </div>
                                <div class="multiple-address-order" >
                                    <input type="hidden" id="multiple-address-output" name="multiple_address_output"
                                        value="<?php echo $multiple_address_output ?>" data-error-message="Please choose an option to upload the recipient list.">
                                        <span class="error-message"></span>
                                        <div class="multiple-address-grid">
                                        <label class="upload_type_output_label">
                                            <input type="radio" name="upload_type_output" <?php echo  $delivery_preference == 'multiple_address'  ? 'required' : '' ?> <?php echo $upload_type_output == 'add-manually' ? 'checked' : '' ?> value="add-manually" data-error-message="Please select a delivery preference."> <span><img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/contract.png" alt="" class="address-icon">
                                                Enter a new order
                                            </span>
                                            <div class="tooltip" data-tippy="The name you assign to your recipient list will be used to identify it in the <strong>Incomplete Orders</strong> section and in <strong>recipient lists</strong> after successfully placing an order."></div>
                                        </label>
                                        <div>
                                            <label class="upload_type_output_process_name" style="display: <?php echo $upload_type_output == 'add-manually' ? 'block' : 'none' ?> ">
                                                <input type="text" name="upload_type_output_process_name" <?php echo $upload_type_output == 'add-manually' ? 'required' : '' ?> value="<?php echo  $upload_type_output_process_name ?>" placeholder="Name your list of recipients" data-error-message="Please add recipient list name.">
                                                <span class="error-message"></span>
                                                
                                            </label>
                                        </div>
                                        <?php 
                                    $user = self::$current_user_id;
                                    $getLastYearOrderList = OAM_Helper::getLastYearOrderList($user);
                                  
                                    if(!empty($getLastYearOrderList)){
                                        ?>
                                        <label>
                                            <input type="radio" name="upload_type_output" <?php echo  $delivery_preference == 'multiple_address'  ? 'required' : '' ?> <?php echo $upload_type_output == 'select-order' ? 'checked' : '' ?> value="select-order" data-error-message="Please select a delivery preference.">
                                            <span>
                                                <img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/book.png" alt="" class="address-icon">
                                               Reorder from a previous year
                                            </span>
                                        </label>
                                        
                                    <div class="order-wrapper input-wrapp" style="<?php echo $upload_type_output == 'select-order' ? '' : 'display:none' ?>">
                                        <!-- <h4>Choose from existing recipient list</h4> -->
                                        <div class="bg-card">
                                            <select name="orders[]" data-error-message="Please select a order." <?php echo $upload_type_output == 'select-order' ? 'required' : '' ?> multiple>
                                                <?php 
                                                 echo '<option></option>';
                                                foreach ($getLastYearOrderList as $key => $order_id) {

                                                    $order = wc_get_order($order_id); // Get the order object
                                                    if (!$order) continue;

                                                    $custom_order_id = OAM_COMMON_Custom::get_order_meta($order_id, '_orthoney_OrderID');
                                                    $billing_name = $order->get_formatted_billing_full_name();

                                                    // Calculate total quantity
                                                    $total_quantity = 0;
                                                    foreach ($order->get_items() as $item) {
                                                        $total_quantity += $item->get_quantity();
                                                    }
                                                    global $wpdb;
                    
                                                   $affiliate_token = $wpdb->get_var( $wpdb->prepare(
                                                        "SELECT affiliate_token FROM {$wpdb->prefix}oh_recipient_order WHERE order_id = %d LIMIT 1",
                                                        $custom_order_id
                                                    ) );

                                                    $token_display = !empty($affiliate_token) ? '[' . $affiliate_token . ']' : '';
                                                    $selected = '';
                                                    if (!empty($orders) && in_array($order_id, $orders)) {
                                                        $selected = 'selected';
                                                    }

                                                    echo '<option ' . $selected . ' value="' . esc_attr($order_id) . '">'
                                                    . esc_html($custom_order_id . ' - ' . $billing_name . ' ' . $token_display . ' (Jars: ' . $total_quantity . ')')
                                                    . '</option>';
                                                }
                                                ?>
                                                
                                            </select>
                                            <span class="error-message"></span>
                                        </div>
                                    </div>
                                        <?php

                                    }
                                    $getGroupList = OAM_Helper::getGroupList($user);
                                    
                                    if(!empty($getGroupList)){
                                    ?>
                                        
                                        <label style="display:none">
                                            <input type="radio" name="upload_type_output" <?php echo  $delivery_preference == 'multiple_address'  ? 'required' : '' ?> <?php echo $upload_type_output == 'select-group' ? 'checked' : '' ?> value="select-group" data-error-message="Please select a delivery preference.">
                                            <span>
                                                <img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/book.png" alt="book" class="address-icon">
                                                Choose from Existing Recipient List 
                                            </span>
                                        </label>
                                        
                                    <div class="groups-wrapper input-wrapp" style="<?php echo $upload_type_output == 'select-group' ? '' : 'display:none' ?>">
                                        <!-- <h4>Choose from existing recipient list</h4> -->
                                        <div class="bg-card">
                                            <select name="groups[]" data-error-message="Please select a Recipient List." <?php echo $upload_type_output == 'select-group' ? 'required' : '' ?> multiple >
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
                                            <input type="radio" name="upload_type_output" <?php echo  $delivery_preference == 'multiple_address'  ? 'required' : '' ?>  <?php echo $upload_type_output == 'upload-csv' ? 'checked' : '' ?> value="upload-csv" data-error-message="Please select a delivery preference.">
                                            <span>
                                                <img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/file.png" alt="" class="address-icon">
                                               Upload a new order from a list
                                            </span>
                                        </label>
                                        
                                    </div>
                                    <span class="error-message multipleaddressordererrormessage" style="display:none"> Please choose an option to upload the recipient list.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="button-block">
                <div>
                    <button type="button" class="back w-btn us-btn-style_2">Back</button>
                    <button data-href="<?php echo CUSTOMER_DASHBOARD_LINK; ?>" class="w-btn us-btn-style_1 outline-btn save_continue_later_btn" data-tippy="Click to save your order progress to your Dashboard under Incomplete Orders.">Save & Continue Later</button>
                </div>
                <button type="button" value="<?php echo  $delivery_preference == 'single_address'  ? 'single-address' : '' ?>" class="next w-btn us-btn-style_1" style="float:right">Proceed With Order</button>
            </div>
        </div>
    <?php
    }

    public static function step_3($data, $csv_name = ''){
        $groups = [];
        $csv_dir = OAM_Helper::$process_recipients_csv_url;
        $greeting = '';
        $csvName = '';
        $affiliate_select = 'Orthoney';
        if (!empty($data)) {
            $groups = (!empty($data->groups)) ? $data->groups : '';
            $csvName = $data->csv_name != '' ? $data->csv_name : '';
            $greeting = (!empty($data->greeting)) ? $data->greeting : '';
            $affiliate_select = (!empty($data->affiliate_select)) ? $data->affiliate_select : 'Orthoney';
        }
        ?>
        <div class="step" id="step3">
            <div class="heading-title organization_data_show">
                <div>
                    <?php echo self::organization_data($affiliate_select) ?>
                </div>
                <div></div>
            </div>
            <div class="wpb_column vc_column_container">
                <div class="vc_column-inner">
                    <div class="g-cols wpb_row via_grid cols_3-2 laptops-cols_inherit tablets-cols_inherit mobiles-cols_1 valign_top type_default">
                    <div class="wpb_column vc_column_container steps-column">
                            <div class="download-file-block">
                                <h3><strong>INSTRUCTIONS</strong></h3>
                                <h3 style="padding-top: 0;">Download a sample file to see the correct format for your list.</h3>
                                <div class="file-lists">
                                    <div class="file-title">
                                        <p>The file must have the following columns with these exact headings:</p>
                                    </div>
                                    <ul>
                                        <li>Full Name</li>
                                        <li>Company Name <br> <small><i>(column is required, even if it's blank)</i></small></li>
                                        <li>Mailing address</li>
                                        <li>Suite/Apt# <br> <small><i>(column is required, even if it's blank)</i></small></li>
                                        <li>City</li>
                                        <li>State <br> <small><i>(2 letter abbreviation)</i></li>
                                        <li>Zipcode</li>
                                        <li>Quantity</li>
                                        <li>Greeting <br> <small><i>(column is required, even if it's blank)</i></small></li>
                                    </ul>
                                </div>
                                <div class="file-title format">
                                    <p>Save or export your list as a .csv, .xlsx, or .xls before uploading.</p>
                                </div>
                                <div class="download-file">
                                    <a href="<?php echo esc_url(OH_PLUGIN_DIR_URL . 'assets/recipient_sample.xlsx'); ?>" class="us-btn-style_1 submit-csv-btn " download><i class="far fa-download"></i> Download Sample File</a>
                                </div>
                            </div>
                        </div>    
                    <div class="wpb_column vc_column_container steps-column">
                            <div>
                                <h3>Recipient List</h3>
                                <p><strong>Your file must include all the required columns with headings exactly matching the sample file. Please click <a href="<?php echo esc_url(OH_PLUGIN_DIR_URL . 'assets/recipient_sample.xlsx'); ?>"  download> Download Sample File</a>  and use the template.</strong></p>
                                <div class="file-upload field-block">
                                    <label><span class="title-block">File Upload: <small>(Accept: .csv, .xlsx, OR .xls)</small></span></label>
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
                                <div class="textarea-div textarea-field field-block" style="display:none">
                                    <label>
                                        <span class="title-block">Add a Universal Greeting for All Recipients</span>
                                        <textarea name="greeting"
                                            data-error-message="Type here..."><?php echo $greeting ?></textarea>
                                        <div class="char-counter"><span>100</span> characters remaining</div>
                                    </label>
                                    <!-- <div class="description">
                                        <p>To begin your order, please select an organization you'd like to support and then you can proceed.</p>
                                    </div> -->
                                </div>
                                <span class="error-message"></span>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div class="button-block">
                <div>
                    <button type="button" class="back w-btn us-btn-style_2">Back</button>
                    <button data-href="<?php echo CUSTOMER_DASHBOARD_LINK; ?>" class="w-btn us-btn-style_1 outline-btn save_continue_later_btn" data-tippy="Click to save your order progress to your Dashboard under Incomplete Orders.">Save & Continue Later</button>
                </div>
                <button type="button" class="submit_csv_file w-btn next-step us-btn-style_1" style="float:right">Add Recipients </button>
            </div>
        </div>
    <?php
    }

    public static function step_4($data, $currentStep, $group_name){
        global $wpdb;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $affiliate_select = 'Orthoney';
        if (!empty($data)) {
            $affiliate_select = (!empty($data->affiliate_select)) ? $data->affiliate_select : 'Orthoney';
        }
        ?>
        <div class="step" id="step4">
        <?php
            if(($currentStep == 3 OR self::$atts_process_id != 0)){
                $data = '';
                $view_all_recipients_btn_html = '';

                if(self::$atts_process_id != 0){
                   
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

                $pid = $_GET['pid'];
                $greeting_empty_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$order_process_recipient_table} WHERE pid = %d AND greeting = %s AND visibility = %d" ,
                    $_GET['pid'],
                    '',
                    1
                ));


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
                            <div>
                                <?php echo self::organization_data($affiliate_select) ?>
                            </div>
                            <div class="group-name">
                                Recipient List Name: <strong><?php echo $group_name; ?></strong><button class="editProcessName far fa-edit" data-name="<?php echo $group_name; ?>" data-tippy="Edit Recipient List Name"></button>
                            </div>
                            <?php if ($result['data']['totalCount'] != 0) : ?>
                                <!-- <p class="num-count">Number of Recipients: <span><?php echo $result['data']['totalCount']; ?></span></p> -->
                            <?php endif; ?>
                            
                        </div>
                        <div>
                            <!-- <button class="editRecipient btn-underline" data-popup="#recipient-manage-popup">Add New Recipient</button><br> -->
                            <!-- <button class="removeRecipientsAlreadyOrder btn-underline" data-tippy="Remove recipient for the already placed order">Remove recipient</button> -->

                            <?php echo self::badge($result['data']['total_quantity'],$result['data']['totalCount']) ?>
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
                            'success'  => 'Added',
                            'duplicate' => 'Duplicate',
                            // 'alreadyOrder' => 'Already Ordered',
                            'new'      => 'Additional'
                        ];
                        if(self::$atts_process_id != 0){
                            $sections = [
                                'fail'     => 'Failed',
                            ];
                        }
                        foreach ($sections as $key => $label) {
                            $countKey = $key . 'Count';
                            if( $label == 'Added'){
                                $label = 'Added Recipient(s) From List';
                            }elseif($label == 'Additional' ){
                                $label = 'Added Recipient(s) Manually';
                            }
                            else{
                                $label = $label . ' Recipient(s)';
                            }
                            if (!empty($result['data'][$countKey])) {
                                echo '<button class="scroll-section-btn" data-section="' . $key . 'CSVData">' 
                                    . $label . ' (' . $result['data'][$countKey] . ')</button>';
                            }
                        }
                        ?>
                    </div>
                    <div class="recipient-group-section orthoney-datatable-warraper <?php echo ((self::$atts_process_id != 0) ? 'failed-recipient-show' : '' ) ?>">
                        <?php
                        foreach ($sections as $key => $label) {
                            $countKey = $key . 'Count';
                            $dataKey = $key . 'Data';
                        
                            if (!empty($result['data'][$countKey])) {
                                $viewAllBtn = '';
                                if ($key != 'duplicate' && $result['data'][$countKey] > 10) {
                                    // $viewAllBtn = '<button class="view-all-recipients btn-underline" data-status="0">View All Recipients</button>';
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
                            <div><h5 class="table-title">Added Recipient(s) Manually</h5></div>
                            <div><button class="editRecipient btn-underline" style="display:none" data-popup="#recipient-manage-popup">Add New Recipient</button></div>
                        </div>
                        <?php 
                    }
        
                    ?>
                    <div class="button-block">
                    <button data-href="<?php echo CUSTOMER_DASHBOARD_LINK; ?>" class="w-btn us-btn-style_1 outline-btn save_continue_later_btn" data-tippy="Click to save your order progress to your Dashboard under Incomplete Orders.">Save & Continue Later</button>
                    <div>
                        <button class="editRecipient w-btn us-btn-style_5" data-popup="#recipient-manage-popup">Add New Recipient</button>
                        <?php
                        if ($result['data']['totalCount'] != 0) { 
                        ?>
                        <button class="verifyRecipientAddressButton w-btn us-btn-style_1 next-step"
                            data-totalCount="<?php echo $result['data']['totalCount']; ?>"
                            data-successCount="<?php echo $result['data']['successCount']; ?>"
                            data-newCount="<?php echo $result['data']['newCount']; ?>"
                            data-failCount="<?php echo $result['data']['failCount']; ?>"
                            data-duplicateCount="<?php echo $result['data']['duplicateCount']; ?>"
                            data-duplicatePassCount="<?php echo $result['data']['duplicatePassCount'];?>"
                            data-greeting_empty_count="<?php echo $greeting_empty_count;?>"
                            data-duplicateFailCount="<?php echo $result['data']['duplicateFailCount']; ?>">
                            Verify Recipient Addresses
                        </button>
                        <?php  } ?>
                    </div>
                    </div>
                    <?php 
                    
                } 
            }
            ?>
        </div>
    
        <?php
    }
    

    public static function step_5($data, $currentStep, $group_name){
        global $wpdb;
        $order_process_recipient_table = OAM_Helper::$order_process_recipient_table;
        $affiliate_select = 'Orthoney';
        if (!empty($data)) {
            $affiliate_select = (!empty($data->affiliate_select)) ? $data->affiliate_select : 'Orthoney';
        }
        $duplicate = $data->duplicate ?? 1;
        $recipientAddressIds = $data->recipientAddressIds ?? [];
        $singleAddressCheckoutStatus = $data->singleAddressCheckoutStatus ?? '';
        $checkoutProceedStatus = $data->checkout_proceed_with_multi_addresses_status ?? '';

     
          $pid = $_GET['pid'];
                $greeting_empty_count = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$order_process_recipient_table} WHERE pid = %d AND greeting = %s AND visibility = %d " ,
                    $_GET['pid'],
                    '',
                    1
                ));

        echo '<div class="step" id="step5">';
        if (!empty($data->delivery_preference)) {
            if(($currentStep == 4 OR $currentStep == 5 ) AND ($data->delivery_preference == 'single_address' OR $data->delivery_preference == 'multiple_address')){
                if ($data->delivery_preference == 'single_address') {
                    self::single_address_form($currentStep);
                }

                if ($data->delivery_preference == 'multiple_address' && !empty($data->pid)) {
                    $data = OAM_Helper::get_order_process_address_verified_recipient($data->pid, $duplicate, $recipientAddressIds);
                    $result = json_decode($data, true);

                    if (!empty($result) && $result['success'] == 1) {
                        if($result['data']['totalCount'] === 1){
                            echo "<style>.deleteRecipient {display:none !important}</style>";
                        }
                        ?>
                        <div class="heading-title">
                            <div>
                                <div>
                                <?php echo self::organization_data($affiliate_select) ?>
                            </div>
                                <div class="group-name">
                                    Recipient List Name: <strong><?php echo $group_name; ?></strong>
                                    <button class="editProcessName far fa-edit" 
                                            data-tippy="Edit Recipient List Name" 
                                            data-name="<?php echo esc_attr($group_name); ?>"></button>
                                </div>
                                <!-- <p class="num-count">
                                    Number of Final Recipients: <span><?php echo $result['data']['totalCount']; ?></span>
                                </p> -->
                            </div>
                            <div>
                                <?php echo self::badge($result['data']['total_quantity'], $result['data']['totalCount']); ?>
                            </div>
                        </div>
                        <?php
                        echo '<div class="recipient-group-nav">';
                        if ($result['data']['unverifiedRecordCount'] > 0) {
                            echo '<button class="scroll-section-btn" data-section="unverified-block">Rejected Address(es) (' . $result['data']['unverifiedRecordCount'] . ')</button>';
                        }
                        if ($result['data']['verifiedRecordCount'] > 0) {
                            echo '<button class="scroll-section-btn" data-section="verified-block">Verified Address(es) (' . $result['data']['verifiedRecordCount'] . ')</button>';
                        }
                        echo '</div>';

                        echo '<div class="recipient-group-section orthoney-datatable-warraper">';
                        self::render_recipient_block('Rejected Address(es)', 'unverified-block', 'unverifiedRecord', $result['data']['unverifiedRecordCount'], $result['data']['totalCount'], $result['data']['unverifiedData']);
                        self::render_recipient_block('Verified Address(es)', 'verified-block', 'verifyRecord', $result['data']['verifiedRecordCount'], $result['data']['totalCount'], $result['data']['verifiedData']);
                        echo '</div>';
                        if($result['data']['totalCount'] != 0){
                            echo '<div class="button-block">';
                            echo '<button data-href="'.CUSTOMER_DASHBOARD_LINK.'" class="w-btn us-btn-style_1 outline-btn save_continue_later_btn" data-tippy="Click to save your order progress to your Dashboard under Incomplete Orders.">Save & Continue Later</button>';
                            echo '<button id="checkout_proceed_with_addresses_button" class="w-btn us-btn-style_1 next-step" data-greeting_empty_count="'.$greeting_empty_count.'">Proceed To CheckOut</button>';
                            echo '<input type="hidden" name="processCheckoutStatus" value="' . $currentStep . '">';
                            echo '<input type="hidden" name="checkout_proceed_with_multi_addresses_status" value="' . $checkoutProceedStatus . '">';
                            if ($result['data']['unverifiedRecordCount'] > 0) {
                                echo '<button id="checkout_proceed_with_only_unverified_addresses" style="display:none" class="w-btn us-btn-style_1 outline-btn">Continue With Rejected Address(es)</button>';
                            }
                            if ($result['data']['verifiedRecordCount'] > 0) {
                                echo '<button id="checkout_proceed_with_only_verified_addresses" style="display:none" class="w-btn us-btn-style_1 outline-btn">Proceed With Verified Address(es)</button>';
                            }
                            echo '</div>';
                        }
                    }
                }
            }
        }

        echo '</div>';
    }

    private static function render_recipient_block($title, $block_id, $record_id, $recordCount, $totalCount, $data){
        if ($recordCount > 0) {
            $viewAllBtn = '';            
            // $viewAllBtn = $recordCount > 10 ? '<button class="view-all-recipients btn-underline" data-status="0">View All Recipients</button>' : '';
            echo '<div class="' . $block_id . ' table-data" id="' . $block_id . '" data-count="'.$recordCount.'">';
            echo '<div class="">';
            echo '<div class="block-inner heading-title"><div><h3 class="title">' . $title . '</h3>';
            echo '<p>Out of ' . $totalCount . ' Recipient(s), ' . $recordCount . ' are ' . $title . '.</p></div><div class="search-icon"> <div class="icon"></div></div> </div>';
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

    public static function badge($jar = 0, $recipient = 0){
        ?>
        <div class="badge-group">
            <div class="badge" style="display:none">
            <img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/cart-icon.png" alt="cart icon" class="cart-icon">
                <span class="badge-number"><?php echo $recipient; ?></span>
            </div>
            <div class="badge" data-tippy="Total Number Of Jars For This Order">
            <img src="<?php echo OH_PLUGIN_DIR_URL ?>assets/image/jar-icon.png" alt="jar icon" class="jar-icon">
                <span class="badge-number"><?php echo $jar ; ?></span>
            </div>
            
        </div>
        <?php
    }
    
    public static function organization_data($affiliate){
        $details= 'Honey from the Heart';
        if($affiliate != 'Orthoney'){
           $details = OAM_Helper::get_affiliate_by_pid($affiliate);
        }
        ?>
        <p class="organization-details">
            Organization: <strong class="organization_value"><?php echo $details;?></strong>
        </p>
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
