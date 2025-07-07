<?php
   // Prevent direct access
   if (!defined('ABSPATH')) {
       exit;
   }

   global $wpdb;
   $user_id = get_current_user_id();
   $affiliate_id = $user_id;
   $associated_id = $user_id;

    if (!empty(get_user_meta($user_id, 'associated_affiliate_id', true))) {
        // The user is a team member, get the associated affiliate
        $associated_id = $associated_id;
    }
   
    $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;
    $org_code = $wpdb->get_var($wpdb->prepare("SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $associated_id));

   $user_info = get_userdata($affiliate_id);
   $main_email = $user_info->user_email;

   // Fetch user meta fields
   $first_name = get_user_meta($associated_id, '_yith_wcaf_first_name', true) ?: get_user_meta($associated_id, 'first_name', true);
   $last_name = get_user_meta($associated_id, '_yith_wcaf_last_name', true) ?: get_user_meta($associated_id, 'last_name', true);
   $user = get_userdata($associated_id);
   $email = get_user_meta($associated_id, '_yith_wcaf_email', true)?:$main_email;
   
   //TODO description
   $name_of_your_organization = get_user_meta($associated_id, '_yith_wcaf_name_of_your_organization', true);
   $your_organizations_website = get_user_meta($associated_id, '_yith_wcaf_your_organizations_website', true);
   $phone_number = get_user_meta($associated_id, '_yith_wcaf_phone_number', true);
   $address = get_user_meta($associated_id, '_yith_wcaf_address', true);
   $mission_statement = get_user_meta($associated_id, 'mission_statement', true);
   $gift_card = get_user_meta($associated_id, 'gift_card', true);
   $city = get_user_meta($associated_id, '_yith_wcaf_city', true);
   $product_price = get_user_meta($associated_id, 'DJarPrice', true);
   $state = get_user_meta($associated_id, '_yith_wcaf_state', true);
   $zipcode = get_user_meta($associated_id, '_yith_wcaf_zipcode', true);
   $tax_id = get_user_meta($associated_id, '_yith_wcaf_tax_id', true);


   $check_payable = get_user_meta($associated_id, '_yith_wcaf_check_payable', true);
   $address_check = get_user_meta($associated_id, '_yith_wcaf_address_check', true);
   $attention = get_user_meta($associated_id, '_yith_wcaf_attention', true);
   $check_mailed_address = get_user_meta($associated_id, '_yith_wcaf_check_mailed_address', true);
   
   $selling_minimum_price = get_field('selling_minimum_price', 'option') ?: 18;
   //TODO
   $dashboard_link = ORGANIZATION_DASHBOARD_LINK;
   $dashboard_link_label = 'Return to Dashboard';
   $activate_affiliate_account = get_user_meta($associated_id, 'activate_affiliate_account', true);
   
   if ((empty($activate_affiliate_account) AND $activate_affiliate_account != 1 ) ) {
        echo '<div class="dashboard-block"><div class="dashboard-heading block-row">
                <div class="item error-message-box">
                    <div class="row-block">
                        <h6 class="block-title">Your account is inactive. Submit your Tax ID to activate your account and become eligible for this year`s commission.</h6>
                        <div>';
                        if (!empty($tax_id)) {
                            echo '<button data-userid="' . esc_attr($affiliate_id) . '" class="w-btn us-btn-style_1 activate_affiliate_account">Activate Account</button>';
                        } else {
                            echo '<a href="'.ORGANIZATION_DASHBOARD_LINK.'my-profile/" class="w-btn us-btn-style_1">Update Tax ID</a>';
                        }
                    echo '</div></div>
                </div>
            </div></div>';
    }
    ?>
<div class="order-process-block form-deisgn">
    <div class="heading-title">
        <h3 class="block-title">Organization Profile</h3>
        <a class="w-btn us-btn-style_1" href="<?php echo esc_url( $dashboard_link ) ?>"><?php echo esc_html( $dashboard_link_label ) ?></a>
    </div>
    
    <div class="affiliate-profile" id="update-affiliate-form">
        <form id="affiliate-profile-form">
             <input type="hidden" name="associated_id" id="associated_id" value="<?php echo esc_attr($associated_id); ?>" required data-error-message="Please enter a First Name.">
            <div class="profile-fields site-form grid-two-col">
                <div class="form-row gfield--width-half">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($first_name); ?>"
                        required data-error-message="Please enter a First Name.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($last_name); ?>"
                        required data-error-message="Please enter a Last Name.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="billing_phone">Phone Number <span class="required">*</span></label>
                    <input type="text" name="billing_phone" id="billing_phone" class="phone-input"
                        value="<?php echo esc_attr($phone_number); ?>" required
                        data-error-message="Please enter a Phone Number.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="email">Email ID <span class="required">*</span> <br><span class="error-message"><strong>This is the email address where reorder notifications will be sent.</strong></span></label>
                    <input type="email" name="email" id="email" value="<?php echo esc_attr($email); ?>" required
                        data-error-message="Please enter a Email.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="organization_name">Organization Name <span class="required">*</span></label>
                    <input type="text" name="organization_name" id="organization_name" required
                        value="<?php echo esc_attr($name_of_your_organization); ?>"
                        data-error-message="Please enter a Organization Name.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="organization_website">Organization Website <span class="required">*</span></label>
                    <input type="url" name="organization_website" id="organization_website" required 
                        value="<?php echo esc_attr($your_organizations_website); ?>"
                        data-error-message="Please enter a Organization Website.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="address">Address <span class="required">*</span></label>
                    <input type="text" name="address" id="address" value="<?php echo esc_attr($address); ?>" required
                        data-error-message="Please enter a Address">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="city">City <span class="required">*</span></label>
                    <input type="text" name="city" id="city" value="<?php echo esc_attr($city); ?>" required
                        data-error-message="Please enter a City">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="state">State <span class="required">*</span></label>
                    <input type="text" name="state" id="state" value="<?php echo esc_attr($state); ?>" required
                        data-error-message="Please enter a State">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode <span class="required">*</span></label>
                    <input type="text" name="zipcode" id="zipcode" value="<?php echo esc_attr($zipcode); ?>" required
                        data-error-message="Please enter a Zip">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="tax_id">Tax ID <span class="required">*</span></label>
                    <input type="text" name="tax_id" id="tax_id" value="<?php echo $tax_id; ?>" required
                        data-error-message="Please enter a Tax ID">
                    <span class="error-message"></span>
                </div>
                <div class="form-row text-right">
                    <button type="button" class="add-user w-btn us-btn-style_1" id="save-profile">Save</button>
                </div>
            </div>
        </form>
        <div id="profile-message"></div>
    </div>
</div>
<br>
<div class="order-process-block form-deisgn">
    <div class="heading-title">
         <div class="heading-title">
            <div>
                <h3 class="block-title">Remittance Form</h3>
            </div>
        </div>
    </div>
    
    <div class="affiliate-profile">
        <form id="affiliate-remittance-form">
            <input type="hidden" name="associated_id" id="associated_id" value="<?php echo esc_attr($associated_id); ?>" required>
            <div class="profile-fields site-form grid-two-col">
                <!-- <div class="form-row gfield--width-half">
                    <label for="org_code">ORG Code <span class="required">*</span></label>
                    <input type="text" name="org_code" id="org_code" value="<?php echo esc_attr($org_code); ?>" readonly required data-error-message="Please enter a Org Code.">
                    <span class="error-message"></span>
                </div> -->
                <div class="form-row gfield--width-half">
                    <label for="check_payable">Make Check Payable to <span class="required">*</span></label>
                    <input type="text" name="check_payable" id="check_payable" value="<?php echo esc_attr($check_payable); ?>" required data-error-message="Please enter a make check payable to.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="address_check">Address to Send Check to <span class="required">*</span></label>
                    <input type="text" name="address_check" id="address_check" value="<?php echo esc_attr($address_check); ?>" required data-error-message="Please enter a address to send check.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="attention">To the Attention of <span class="required">*</span></label>
                    <input type="text" name="attention" id="attention" value="<?php echo esc_attr($attention); ?>" required data-error-message="Please enter a attention.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="check_mailed_address">Please indicate if check will be mailed to a home or your organization's office:<span class="required">*</span></label>
                    <input type="text" name="check_mailed_address" id="check_mailed_address" value="<?php echo esc_attr($check_mailed_address); ?>" required data-error-message="Please enter an indication.">
                    <span class="error-message"></span>
                </div>
                
                <div class="form-row text-right">
                    <button type="button" class="add-user w-btn us-btn-style_1" id="save-remittance">Save Remittance Form</button>
                </div>
            </div>
        </form>
        <div id="profile-message"></div>
    </div>
</div>



<div class="two-col-grid two-column-block">
    <div class="cl-left">
        <div class="order-process-block  form-deisgn">
            <div class="heading-title">
                <h3 class="block-title">Update Product Price</h3>
            </div>
            <div class="affiliate-profile" id="update-price-affiliate-form">
                <form id="affiliate-update-price-form">
                    <div class="form-row gfield--width-half">
                        <label for="product_price">Product Price <span class="error-message"><strong>Honey jar price cannot be less than <?php echo wc_price($selling_minimum_price ) ?>.</strong></span></label>

                        <?php 
                        $show_price = $selling_minimum_price;
                        if($product_price >= $selling_minimum_price){
                            $show_price = $product_price;
                        }
                        ?>

                        <div class="product-price-box textarea-div form-row gfield--width-full update-price">
                            <input type="text" name="product_price" id="product_price" value="<?php echo esc_attr($show_price); ?>" data-error-message="Please enter a product price">
                            <span class="error-message"></span>
                            <input type="hidden" name="selling_minimum_price" value="<?php echo $selling_minimum_price ?>">
                        
                            <button type="button" class="add-user w-btn us-btn-style_1" id="affiliate-product-price-profile">Update Price</button>
                        </div>
                        
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="cl-right" >
        <div class="order-process-block">
            <div class="heading-title">
                <div>
                    <h3 class="block-title">Gift Card</h3>
                    <br>
                    <p>
                        A personalized gift card will be included inside of every box of honey we send. The bottom of
                        the gift card acknowledges a donation to your organization. <br>
                        Please indicate the exact (brief) name for your organization to be inserted into this sentence:
                    </p>
                </div>
            </div>
            <div class="affiliate-profile">
                <form id="affiliate-gift-card-form">
                    <div class="form-row gfield--width-half">
                        <div class="textarea-div form-row gfield--width-full">
                            <label for="gift_card">
                                "In celebration of the New Year, a donation has been made in your name to
                            </label>
                            <textarea name="gift_card" data-error-message="Please enter a gift card"><?php echo htmlspecialchars($gift_card) ?></textarea data-limit="100">
                            <div class="char-counter"><span>100</span> characters remaining</div>
                            <span class="error-message"></span>
                        </div>
                        <div class="form-row text-right">
                            <button type="button" class="add-user w-btn us-btn-style_1" id="affiliate-gift-card-profile">Update Gift Card</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="order-process-block" >
    <div class="heading-title">
        <h3 class="block-title">Mission Statement</h3>
    </div>
    <div class="affiliate-profile">
        <form id="affiliate-mission-statement-form">
            <div class="form-row gfield--width-half">
                <div class="textarea-div form-row gfield--width-full">
                    <textarea rows="10" name="mission_statement" id="mission_statement"
                        data-error-message="Please enter a Mission Statement" data-limit="700"
                        style="min-height: 300px;"><?php echo $mission_statement ?></textarea>
                    <span class="error-message"></span>
                    <div class="char-counter"><span>700</span> characters remaining</div>
                </div>
                <div class="form-row text-right">
                    <button type="button" class="add-user w-btn us-btn-style_1" id="affiliate-mission-statement-profile">Update Mission Statement</button>
                </div>
            </div>
        </form>
    </div>
</div>