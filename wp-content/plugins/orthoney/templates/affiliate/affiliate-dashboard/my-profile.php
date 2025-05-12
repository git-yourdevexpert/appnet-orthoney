<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Fetch user meta fields
$first_name = get_user_meta($user_id, 'first_name', true);
$last_name = get_user_meta($user_id, 'last_name', true);
$user = get_userdata($user_id);
$email = $user->user_email;

//TODO
$name_of_your_organization = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true);
$your_organizations_website = get_user_meta($user_id, '_yith_wcaf_your_organizations_website', true);
$phone_number = get_user_meta($user_id, '_yith_wcaf_phone_number', true);
$address = get_user_meta($user_id, '_yith_wcaf_address', true);
$city = get_user_meta($user_id, '_yith_wcaf_city', true);
$product_price = get_user_meta($user_id, 'DJarPrice', true);
$state = get_user_meta($user_id, '_yith_wcaf_state', true);
$zipcode = get_user_meta($user_id, '_yith_wcaf_zipcode', true);
$tax_id = get_user_meta($user_id, '_yith_wcaf_tax_id', true);

//TODO
?>
<div class="order-process-block form-deisgn">
<div class="heading-title"><h3 class="block-title">My Profile</h3></div>
    <div class="affiliate-profile" id="update-affiliate-form">
    
        <form id="affiliate-profile-form">
            <div class="profile-fields site-form grid-two-col">
                <div class="form-row gfield--width-half">
                    <label for="first_name">First Name</label>
                    <input type="text" name="first_name" id="first_name" value="<?php echo esc_attr($first_name); ?>" required data-error-message="Please enter a First Name.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="last_name">Last Name</label>
                    <input type="text" name="last_name" id="last_name" value="<?php echo esc_attr($last_name); ?>" required data-error-message="Please enter a Last Name.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="billing_phone">Phone Number</label>
                    <input type="text" name="billing_phone" id="billing_phone" class="phone-input" value="<?php echo esc_attr($phone_number); ?>" required data-error-message="Please enter a Phone Number.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="email">Email ID</label>
                    <input type="email" name="email" id="email" value="<?php echo esc_attr($email); ?>" required data-error-message="Please enter a Email.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="organization_name">Organization Name</label>
                    <input type="text" name="organization_name" id="organization_name" value="<?php echo esc_attr($name_of_your_organization); ?>" data-error-message="Please enter a Organization Name.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="organization_website">Organization Website</label>
                    <input type="url" name="organization_website" id="organization_website" value="<?php echo esc_attr($your_organizations_website); ?>" data-error-message="Please enter a Organization Website.">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="address">Address</label>
                    <input type="text" name="address" id="address" value="<?php echo esc_attr($address); ?>" data-error-message="Please enter a Address">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="city">City</label>
                    <input type="text" name="city" id="city" value="<?php echo esc_attr($city); ?>" data-error-message="Please enter a City">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="state">State</label>
                    <input type="text" name="state" id="state" value="<?php echo esc_attr($state); ?>" data-error-message="Please enter a State">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="zipcode">Zipcode</label>
                    <input type="text" name="zipcode" id="zipcode" value="<?php echo esc_attr($zipcode); ?>" data-error-message="Please enter a Zip">
                    <span class="error-message"></span>
                </div>
                <div class="form-row gfield--width-half">
                    <label for="tax_id">Tax ID</label>
                    <input type="text" name="tax_id" id="tax_id" value="<?php echo esc_attr($tax_id); ?>" data-error-message="Please enter a Tax ID">
                    <span class="error-message"></span>
                </div>
                <div class="form-row text-right">
                    <button type="button" class="add-user us-btn-style_1" id="save-profile">Save</button>

                </div>
            </div>
        </form>
        <div id="profile-message"></div>
    </div>
</div>
</div>


<br>
<div class="order-process-block  form-deisgn">
    <div class="heading-title">
        <h3 class="block-title">Update Product Price</h3>
    </div>
    <div class="affiliate-profile" id="update-price-affiliate-form">
        <form id="affiliate-update-price-form">
            <div class="form-row gfield--width-half">
                <label for="city">Product Price</label>
                <input type="text" name="product_price" id="product_price" value="<?php echo esc_attr($product_price); ?>" data-error-message="Please enter a product price">
                <span class="error-message"></span>
            </div>
            <div class="form-row text-right">
                <button type="button" class="add-user us-btn-style_1" id="affiliate-product-price-profile">Update Price</button>
            </div>
        </form>
    </div>
</div>

<br>
<div class="order-process-block  form-deisgn">
<div class="heading-title"><h3 class="block-title">Change Password</h3><a href="<?php echo OAM_Helper::$customer_dashboard_link.'edit-account/'; ?>" class="add-user w-btn us-btn-style_1 active">Change Password</a></div></div>
