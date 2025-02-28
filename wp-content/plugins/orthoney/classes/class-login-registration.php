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
        add_shortcode( 'custom_registration_form', array($this,'orthoney_registration_form_handler') );

        add_action( 'init', array( $this,'orthoney_registration_handler') );

        add_action( 'woocommerce_before_customer_login_form', array( $this,'orthoney_display_registration_success_message_handler') );

        add_action( 'init', array( $this,'orthoney_handle_email_verification_handler'));

        add_filter( 'authenticate', array( $this,'orthoney_prevent_unverified_user_login_handler'), 30, 3);

        add_filter( 'manage_users_columns',  array( $this,'orthoney_add_verification_status_column_handler'));

        add_action( 'manage_users_custom_column', array( $this,'orthoney_show_verification_status_column_content_handler'), 10, 3);
    }

    /**
     *  callback Shortcode for Custom Registration Form
     */
    public function orthoney_registration_form_handler() {
        if (is_user_logged_in()) {
            return '<p>' . esc_html__('You are already logged in.', 'woocommerce') . '</p>';
        }
    
        ob_start(); ?>
        <div class="woocommerce">
            <div class="woocommerce-page">
                
                <form method="post" class="woocommerce-form woocommerce-form-register register">
                    <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                    
        
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_first_name"><?php esc_html_e('First Name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="first_name" id="reg_first_name" autocomplete="given-name" value="<?php echo esc_attr(!empty($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : ''); ?>" required />
                    </p>
        
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_last_name"><?php esc_html_e('Last Name', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="text" name="last_name" id="reg_last_name" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="family-name" value="<?php echo esc_attr(!empty($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : ''); ?>" required />
                    </p>
        
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_email"><?php esc_html_e('Email Address', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="email" name="email" id="reg_email" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="email" value="<?php echo esc_attr(!empty($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : ''); ?>" required />
                    </p>
        
                    <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                        <label for="reg_password"><?php esc_html_e('Password', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="password" name="password" id="reg_password" class="woocommerce-Input woocommerce-Input--text input-text" autocomplete="new-password" required />
                    </p>
        
                    <?php do_action('woocommerce_register_form'); ?>
        
                    <p class="woocommerce-form-row form-row">
                        <button type="submit" class="woocommerce-Button woocommerce-button button wp-element-button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e('Register', 'woocommerce'); ?>">
                            <?php esc_html_e('Register', 'woocommerce'); ?>
                        </button>
                    </p>
        
                    <?php do_action('woocommerce_register_form_end'); ?>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     *  callback Handle Registration
     */

    public function orthoney_registration_handler() {
        if (!isset($_POST['register']) || 'POST' !== $_SERVER['REQUEST_METHOD']) {
            return;
        }

        if (empty($_POST['woocommerce-register-nonce']) || 
            !wp_verify_nonce(sanitize_text_field($_POST['woocommerce-register-nonce']), 'woocommerce-register')) {
            wc_add_notice(__('Security check failed.', 'woocommerce'), 'error');
            return;
        }

        // Validate required fields
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            wc_add_notice(__('Please fill in all required fields.', 'woocommerce'), 'error');
            return;
        }

        if (email_exists($email)) {
            wc_add_notice(__('An account is already registered with this email address.', 'woocommerce'), 'error');
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
            wc_add_notice($user_id->get_error_message(), 'error');
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
            // Store the message in a transient
            $message_key = 'registration_success_' . wp_generate_password(8, false);
            set_transient($message_key, 'Please check your mail inbox for Email Verification Required', 60);
            
            // Redirect to login page with message key
            wp_redirect(add_query_arg('registration_success', $message_key, wc_get_page_permalink('myaccount')));
            exit;
        } else {
            wc_add_notice(__('Registration successful but there was an error sending the verification email. Please contact support.', 'woocommerce'), 'error');
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }

     /**
     *  callback Handle Email Verification
     */
    
    public function orthoney_handle_email_verification_handler() {
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

    /**
     *  callback Display registration success message
     */
    public function orthoney_display_registration_success_message_handler() {
        if (isset($_GET['registration_success'])) {
            $message_key = sanitize_text_field($_GET['registration_success']);
            $message = get_transient($message_key);
            
            if ($message) {
                wc_add_notice($message, 'success');
                delete_transient($message_key);
            }
        }
    }

   
     /**
     *  callback Prevent login for unverified users
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
     *  callback Add verification status column to users list in admin
     */
    public function orthoney_add_verification_status_column_handler($columns) {
        $columns['email_verified'] = 'Email Verified';
        return $columns;
    }

    /**
     *  callback Display verification status in the column
     */
    public function orthoney_show_verification_status_column_content_handler($value, $column_name, $user_id) {
        if ($column_name === 'email_verified') {
            $verified = get_user_meta($user_id, 'email_verified', true);
            return $verified === 'true' ? 'Yes' : 'No';
        }
        return $value;
    }

}

new OAM_Auth();