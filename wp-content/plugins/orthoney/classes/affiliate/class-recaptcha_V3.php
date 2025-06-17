<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class YITH_Affiliate_Recaptcha_V3 {

    private $site_key;
    private $secret_key;

    public function __construct() {
        $this->site_key   = get_field('recaptcha_v3_site_key', 'option');    // Your reCAPTCHA v3 Site Key
        $this->secret_key = get_field('recaptcha_v3_secret_key', 'option');  // Your reCAPTCHA v3 Secret Key

        add_action('wp_enqueue_scripts', [$this, 'enqueue_recaptcha_script']);
        add_action('woocommerce_register_form', [$this, 'add_recaptcha_input']);
        add_filter('yith_wcaf_check_affiliate_validation_errors', [$this, 'validate_recaptcha']);
    }

    /**
     * Enqueue Google reCAPTCHA v3 script and inject token handler.
     */
    public function enqueue_recaptcha_script() {
        if (is_page() && has_shortcode(get_post()->post_content, 'yith_wcaf_registration_form')) {
            wp_enqueue_script(
                'google-recaptcha-v3',
                'https://www.google.com/recaptcha/api.js?render=' . $this->site_key,
                array(),
                '3.0',
                true
            );

            wp_add_inline_script('google-recaptcha-v3', "
                function executeRecaptcha() {
                    if (typeof grecaptcha !== 'undefined') {
                        grecaptcha.ready(function() {
                            grecaptcha.execute('{$this->site_key}', {action: 'affiliate_registration'}).then(function(token) {
                                var input = document.getElementById('g-recaptcha-response');
                                if (input) {
                                    input.value = token;
                                }
                            });
                        });
                    }
                }

                document.addEventListener('DOMContentLoaded', function() {
                    executeRecaptcha();

                    var form = document.querySelector('.yith-wcaf-registration-form');
                    if (form) {
                        form.addEventListener('submit', function(e) {
                            var input = document.getElementById('g-recaptcha-response');
                            if (!input.value) {
                                e.preventDefault();
                                executeRecaptcha();
                                setTimeout(function() {
                                    form.submit();
                                }, 800);
                            }
                        });
                    }
                });
            ");
        }
    }

    /**
     * Add hidden input field for g-recaptcha-response
     */
    public function add_recaptcha_input() {
        if (is_page() && has_shortcode(get_post()->post_content, 'yith_wcaf_registration_form')) {
            echo '<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" />';
        }
    }

    /**
     * Server-side reCAPTCHA v3 validation
     */
    public function validate_recaptcha($errors) {
        if (empty($_POST['g-recaptcha-response'])) {
            $errors->add('recaptcha_missing', __('reCAPTCHA verification is required.', 'yith-woocommerce-affiliates'));
            return $errors;
        }

        $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);

        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret'   => $this->secret_key,
                'response' => $recaptcha_response,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ]
        ]);

        if (is_wp_error($response)) {
            $errors->add('recaptcha_error', __('Unable to verify reCAPTCHA. Please try again.', 'yith-woocommerce-affiliates'));
            return $errors;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($result['success'])) {
            $errors->add('recaptcha_failed', __('reCAPTCHA verification failed.', 'yith-woocommerce-affiliates'));
        } elseif (isset($result['score']) && $result['score'] < 0.5) {
            $errors->add('recaptcha_score_low', __('Security score too low. Please try again.', 'yith-woocommerce-affiliates'));
        }

        return $errors;
    }
}

new YITH_Affiliate_Recaptcha_V3();
