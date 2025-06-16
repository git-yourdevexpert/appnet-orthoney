<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
class YITH_Affiliate_Recaptcha_V3
{

    private $site_key   = '6LdxR18rAAAAAH2rovDzb6HcIT-4QU_8Wn9KxARs';   // Replace with your reCAPTCHA v3 Site Key
    private $secret_key = '6LdxR18rAAAAAHvojZ8prxD940I07CU-cGiditKg'; // Replace with your reCAPTCHA v3 Secret Key

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_recaptcha_script']);
        add_filter('yith_wcaf_check_affiliate_validation_errors', [$this, 'validate_recaptcha']);
    }

    /**
     * Inject reCAPTCHA v3 script and token into the affiliate registration form
     */
    public function enqueue_recaptcha_script()
    {
        // Only load on affiliate registration page
        if (is_page() && has_shortcode(get_post()->post_content, 'yith_wcaf_registration_form')) {
            wp_enqueue_script(
                'google-recaptcha-v3',
                'https://www.google.com/recaptcha/api.js?render=' . $this->site_key,
                array(),
                '3.0',
                true
            );

            // Add inline script for reCAPTCHA execution
            wp_add_inline_script('google-recaptcha-v3', '
            function executeRecaptcha() {
                if (typeof grecaptcha !== "undefined") {
                    grecaptcha.ready(function() {
                        grecaptcha.execute("' . $this->site_key . '", {action: "affiliate_registration"}).then(function(token) {
                            var recaptchaInput = document.getElementById("g-recaptcha-response");
                            if (recaptchaInput) {
                                recaptchaInput.value = token;
                            }
                        });
                    });
                }
            }
            
            // Execute on page load
            document.addEventListener("DOMContentLoaded", function() {
                executeRecaptcha();
            });
            
            // Re-execute before form submission
            document.addEventListener("submit", function(e) {
                if (e.target.closest(".yith-wcaf-registration-form")) {
                    e.preventDefault();
                    executeRecaptcha();
                    setTimeout(function() {
                        e.target.submit();
                    }, 500);
                }
            });
        ');
        }
    }

    /**
     * Server-side validation of reCAPTCHA token
     */
    public function validate_recaptcha($errors)
    {
        // Check if reCAPTCHA response exists
        if (empty($_POST['g-recaptcha-response'])) {
            $errors->add('recaptcha_missing', __('reCAPTCHA verification is required.', 'yith-woocommerce-affiliates'));
            return $errors;
        }

        $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);

        // Verify with Google
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
        $response = wp_remote_post($verify_url, array(
            'body' => array(
                'secret' => $this->secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            )
        ));

        if (is_wp_error($response)) {
            $errors->add('recaptcha_error', __('reCAPTCHA verification failed. Please try again.', 'yith-woocommerce-affiliates'));
            return $errors;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        // Check if verification was successful
        if (!$result['success']) {
            $errors->add('recaptcha_failed', __('reCAPTCHA verification failed. Please try again.', 'yith-woocommerce-affiliates'));
            return $errors;
        }

        // Check score (optional - adjust threshold as needed)
        if (isset($result['score']) && $result['score'] < 0.5) {
            $errors->add('recaptcha_score', __('Security verification failed. Please try again.', 'yith-woocommerce-affiliates'));
            return $errors;
        }

        return $errors;
    }
}

new YITH_Affiliate_Recaptcha_V3();