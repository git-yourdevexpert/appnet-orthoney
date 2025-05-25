<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_Auth {
    /**
     * Constructor to hook into WooCommerce template loading.
     */
    public function __construct() {
        add_shortcode('custom_registration_form', array($this, 'orthoney_registration_form_handler'));
        
        // AJAX handlers - notice the nopriv version is required for non-logged in users
        add_action('wp_ajax_orthoney_register_user', array($this, 'orthoney_ajax_registration_handler'));
        add_action('wp_ajax_nopriv_orthoney_register_user', array($this, 'orthoney_ajax_registration_handler'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'orthoney_enqueue_scripts'));
        
        add_action('init', array($this, 'orthoney_handle_email_verification_handler'));
        add_filter('authenticate', array($this, 'orthoney_prevent_unverified_user_login_handler'), 30, 3);
        add_filter('manage_users_columns', array($this, 'orthoney_add_verification_status_column_handler'));
        add_action('manage_users_custom_column', array($this, 'orthoney_show_verification_status_column_content_handler'), 10, 3);

        // Hook when a new user is created from the backend
        add_action('user_register', array($this,'send_email_verification_to_admin_created_user'), 10, 1);

        add_action('woocommerce_register_form_end', array($this,'add_existing_customer_login_message'), 10, 1);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function orthoney_enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        // Make sure the path is correct for your plugin structure
        
        wp_enqueue_script('orthoney-registration-ajax', plugins_url( 'assets/js/registration-ajax.js', __DIR__ ), array('jquery'), '1.0.0', true);
        
        wp_localize_script('orthoney-registration-ajax', 'orthoney_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('orthoney-register-nonce'),
            'email_mismatch' => __('Email addresses do not match.', 'woocommerce'),
            'password_mismatch' => __('Passwords do not match.', 'woocommerce')
        ));

        if (is_page('registration')) { // Change this to match your form's page
            wp_enqueue_script('wc-password-strength-meter');
            wp_enqueue_script('wc-checkout');
        }
    }

    /**
     * Shortcode for Custom Registration Form
     */
    public function orthoney_registration_form_handler() {
        if (is_user_logged_in()) {
            return '<p>' . esc_html__('You are already logged in.', 'woocommerce') . '</p>';
        }
    
        ob_start(); ?>
        <div class="woocommerce">
            <div class="woocommerce-page">
                <div id="registration-response"></div>
                
                <form id="orthoney-registration-form" class="woocommerce-form woocommerce-form-register register">
                    <?php wp_nonce_field('orthoney-register-nonce', 'orthoney_register_nonce'); ?>
                    
                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_first_name"><?php esc_html_e('First Name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name" id="reg_first_name" autocomplete="given-name" required />
                    </div>
        
                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_last_name"><?php esc_html_e('Last Name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="text" name="last_name" id="reg_last_name" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="family-name" required />
                    </div>
        
                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_email"><?php esc_html_e('Email Address', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="email" name="email" id="reg_email" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="email" required />
                    </div>

                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="confirm_email"><?php esc_html_e('Confirm Email Address', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="email" name="confirm_email" id="confirm_email" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="email" required />
                        <span class="email-error" style="color: red; display: none;"><?php esc_html_e('Email addresses do not match', 'woocommerce'); ?></span>
                    </div>
        
                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_password">
                            <?php esc_html_e('Password', 'woocommerce'); ?> <span class="required">*</span>
                        </label>
                        <div style="position: relative;">
                            <i class="far fa-eye toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                            <input type="password" name="password" id="reg_password" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="new-password" required />
                            <div id="password-strength-meter"></div>
                        </div>
                    </div>

                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" >
                        <label for="confirm_password"><?php esc_html_e('Confirm Password', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <div style="position: relative;">
                            <i class="far fa-eye toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="new-password" required />
                            <span class="password-error" style="color: red; display: none;"><?php esc_html_e('Passwords do not match', 'woocommerce'); ?></span>
                        </div>
                    </div>

                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" style="display: inline-flex;">
                        <input type="checkbox" name="terms_conditions" id="terms_conditions" required>
                        <label for="terms_conditions">
                            <?php esc_html_e('By signing up, I agree with the ', 'woocommerce'); ?>
                            <a href="#" target="_blank"><?php esc_html_e('Terms of Use', 'woocommerce'); ?></a> & 
                            <a href="#" target="_blank"><?php esc_html_e('Privacy Policy', 'woocommerce'); ?></a>
                        </label>
                    </div>

                    <div class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide" style="display: inline-flex;">
                        <input type="checkbox" name="marketing_consent" id="marketing_consent">
                        <label for="marketing_consent">
                            <?php esc_html_e('I consent to receiving marketing updates and give permission for you to collect my data.', 'woocommerce'); ?>
                        </label>
                    </div>

        
                    <?php do_action('woocommerce_register_form'); ?>
        
                    <div class="woocommerce-form-row form-row">
                        <button type="submit" class="woocommerce-Button woocommerce-button button wp-element-button woocommerce-form-register__submit" id="orthoney-register-button">
                            <?php esc_html_e('Register', 'woocommerce'); ?>
                        </button>
                    </div>
        
                    <?php do_action('woocommerce_register_form_end'); ?>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX Registration Handler
     */
    public function orthoney_ajax_registration_handler() {
        // Check nonce
        check_ajax_referer('orthoney-register-nonce', 'orthoney_register_nonce');
        
        $response = array(
            'success' => false,
            'message' => ''
        );
        
        // Validate required fields
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $confirm_email = isset($_POST['confirm_email']) ? sanitize_email($_POST['confirm_email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // Check for empty fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_email) || empty($confirm_password)) {
            $response['message'] = __('Please fill in all required fields.', 'woocommerce');
            wp_send_json($response);
            return;
        }
        
        // Check if emails match
        if ($email !== $confirm_email) {
            $response['message'] = __('Email addresses do not match.', 'woocommerce');
            wp_send_json($response);
            return;
        }
        
        // Check if passwords match
        if ($password !== $confirm_password) {
            $response['message'] = __('Passwords do not match.', 'woocommerce');
            wp_send_json($response);
            return;
        }
        
        // Check if email already exists
        if (email_exists($email)) {
            $response['message'] = __('An account is already registered with this email address.', 'woocommerce');
            wp_send_json($response);
            return;
        }

        // Create new user
        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'customer'
        );

        $user_id = wp_insert_user($user_data);

        if (is_wp_error($user_id)) {
            $response['message'] = $user_id->get_error_message();
            wp_send_json($response);
            return;
        }

        // Set user as unverified
        update_user_meta($user_id, 'email_verified', 'false');

        // Generate verification token
        $verification_token = wp_generate_password(32, false);
        update_user_meta($user_id, 'email_verification_token', $verification_token);

        // Create verification link
        $verification_link = add_query_arg(
            array(
                'action' => 'verify_email',
                'token' => $verification_token,
                'user_id' => $user_id
            ),
            home_url('/')
        );

        // Get email settings
        $from_name  = get_bloginfo( 'name' );
        $from_email = get_option('admin_email');;

        // Prepare email content
        $to = $email;
        $subject = 'Email Verification Required';
        $message = sprintf(
            'Hello %s,<br><br>
            Thank you for registering. Please click the button below to verify your email:<br><br>
            <a href="%s" style="background-color: #4CAF50; border-radius: 5px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; padding: 12px 25px; text-decoration: none; text-transform: uppercase;">Verify Email Address</a><br><br>
            If you did not create this account, please ignore this email.',
            $first_name,
            esc_url($verification_link)
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send verification email
        $email_sent = wp_mail($to, $subject, $message, $headers);

        if ($email_sent) {
            $response['success'] = true;
            $response['message'] = __('Registration successful! Please check your email inbox for verification instructions.', 'woocommerce');
        } else {
            $response['message'] = __('Registration successful but there was an error sending the verification email. Please contact support.', 'woocommerce');
        }
        
        wp_send_json($response);
        die(); // Important to terminate properly
    }

    /**
     * Handle Email Verification
     */
    public function orthoney_handle_email_verification_handler() {

        // Handle verification request (for backend-created users)
        if(isset($_GET['user_id'])){
        
            if (isset($_GET['action']) && $_GET['action'] === 'verify_email' && isset($_GET['token']) && isset($_GET['user_id'])) {
                $user_id = intval($_GET['user_id']);
                $token = sanitize_text_field($_GET['token']);
        
                // Get stored token
                $saved_token = get_user_meta($user_id, 'email_verification_token', true);
        
                if ($token === $saved_token) {
                    // Mark email as verified
                    update_user_meta($user_id, 'email_verified', 'true');
                    delete_user_meta($user_id, 'email_verification_token');
        
                    wp_redirect(home_url('/?email_verified=success'));
                    exit;
                } else {
                    wp_redirect(home_url('/?email_verified=failed'));
                    exit;
                }
            }

        }else{  
    
    // Handle verification request for custom registration from
            if (!isset($_GET['action']) || $_GET['action'] !== 'verify_email') {
                return;
            }

            $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
            $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

            if (!$user_id || !$token) {
                wc_add_notice(__('Invalid verification link.', 'woocommerce'), 'error');
                wp_redirect(wc_get_page_permalink('myaccount'));
                exit;
            }

            $stored_token = get_user_meta($user_id, 'email_verification_token', true);

            if ($token === $stored_token) {
                // Update user verification status
                if (isset($_POST['marketing_consent'])) {
                    update_user_meta($user_id, 'marketing_consent', 'yes');
                } else {
                    update_user_meta($user_id, 'marketing_consent', 'no');
                }
            
                if (isset($_POST['terms_conditions'])) {
                    update_user_meta($user_id, 'terms_conditions', 'yes');
                }else{
                    update_user_meta($user_id, 'terms_conditions', 'no');
                }

                update_user_meta($user_id, 'email_verified', 'true');
                delete_user_meta($user_id, 'email_verification_token');

                // Add success message and redirect to login page
                wc_add_notice(__('Email verification successful! You can now log in.', 'woocommerce'), 'success');
                wp_redirect(wc_get_page_permalink('myaccount'));
                exit;
            } else {
                wc_add_notice(__('Invalid or expired verification link.', 'woocommerce'), 'error');
                wp_redirect(wc_get_page_permalink('myaccount'));
                exit;
            }
        }
    }

    /**
     * Prevent login for unverified users
     */
    public function orthoney_prevent_unverified_user_login_handler($user, $username, $password) {
        if (!is_wp_error($user)) {
            $email_verified = get_user_meta($user->ID, 'email_verified', true);
            
            if ($email_verified !== 'true') {
                return new WP_Error(
                    'unverified_email',
                    __('Please verify your email address before logging in. Check your email inbox for the verification link.', 'woocommerce')
                );
            }
        }
        return $user;
    }

    /**
     * Add verification status column to users list in admin
     */
    public function orthoney_add_verification_status_column_handler($columns) {
        $columns['email_verified'] = 'Email Verified';
        return $columns;
    }

    /**
     * Display verification status in the column
     */
    public function orthoney_show_verification_status_column_content_handler($value, $column_name, $user_id) {
        if ($column_name === 'email_verified') {
            $verified = get_user_meta($user_id, 'email_verified', true);
            return $verified === 'true' ? 'Yes' : 'No';
        }
        return $value;
    }

/**
     * send email verification to admin created user
     */
    public function send_email_verification_to_admin_created_user($user_id) {

        $user = get_userdata($user_id);

        // Check if the user has only the 'sales_representative' role
        if (in_array('sales_representative', $user->roles) && count($user->roles) === 1) {
            // Add 'customer' role without removing existing roles
            $user->add_role('customer');
        }
        if (!is_admin()) {
            return; // Ensure this runs only in the WordPress admin dashboard
        }

        
    
        // // Get user data
        // $user = get_userdata($user_id);
        // $email = $user->user_email;
        // $first_name = get_user_meta($user_id, 'first_name', true);
    
        // // Set user as unverified
        // update_user_meta($user_id, 'email_verified', 'false');
    
        // // Generate a verification token
        // $verification_token = wp_generate_password(32, false);
        // update_user_meta($user_id, 'email_verification_token', $verification_token);
    
        // // Create verification link
        // $verification_link = add_query_arg(
        //     array(
        //         'action'  => 'verify_email',
        //         'token'   => $verification_token,
        //         'user_id' => $user_id
        //     ),
        //     home_url('/')
        // );
    
        // // Email settings
        // $from_name = get_bloginfo('name');
        // $from_email = get_option('admin_email');
        // $headers = array(
        //     'Content-Type: text/html; charset=UTF-8',
        //     'From: ' . $from_name . ' <' . $from_email . '>'
        // );
    
        // // Email message
        // $subject = 'Email Verification Required';
        // $message = sprintf(
        //     'Hello %s,<br><br>
        //     Thank you for registering. Please click the button below to verify your email:<br><br>
        //     <a href="%s" style="background-color: #4CAF50; border-radius: 5px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; padding: 12px 25px; text-decoration: none; text-transform: uppercase;">Verify Email Address</a><br><br>
        //     If you did not create this account, please ignore this email.',
        //     esc_html($first_name),
        //     esc_url($verification_link)
        // );
    
        // // Send the email
        // wp_mail($email, $subject, $message, $headers);
    }

    public function add_existing_customer_login_message() {
        ?>
        <p class="existing-customer-login">
            Existing Customers, <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Log In Here</a>
        </p>
        <?php
    }
}

new OAM_Auth();