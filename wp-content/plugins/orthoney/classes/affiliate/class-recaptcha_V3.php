<?php
/**
 * YITH Affiliate reCAPTCHA v3 Integration
 * 
 * Handles Google reCAPTCHA v3 validation for affiliate registration forms
 * and automatically adds approved affiliates to Mailchimp lists.
 * 
 * @package YITH_Affiliate_Recaptcha_V3
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YITH_Affiliate_Recaptcha_V3
 * 
 * Integrates Google reCAPTCHA v3 with YITH WooCommerce Affiliates plugin
 * and handles Mailchimp synchronization for approved affiliates.
 */
class YITH_Affiliate_Recaptcha_V3
{
    /**
     * Google reCAPTCHA v3 site key
     * 
     * @var string
     */
    private $site_key;

    /**
     * Google reCAPTCHA v3 secret key
     * 
     * @var string
     */
    private $secret_key;

    /**
     * Mailchimp API key
     * 
     * @var string
     */
    private $mailchimp_api_key;

    /**
     * Mailchimp list ID for approved organizations
     * 
     * @var string
     */
    private $mailchimp_list_id_for_organizations_approved_list;

    /**
     * Minimum acceptable reCAPTCHA score (0.0 to 1.0)
     * 
     * @var float
     */
    private $min_recaptcha_score = 0.5;

    /**
     * Constructor - Initialize the class and set up hooks
     */
    public function __construct()
    {
        // Initialize configuration from ACF options
        $this->init_config();
        
        // Only proceed if required configurations are available
        if (!$this->is_config_valid()) {
            error_log('YITH_Affiliate_Recaptcha_V3: Missing required configuration');
            return;
        }

        // Hook into WordPress actions and filters
        $this->init_hooks();
    }

    /**
     * Initialize configuration from ACF fields
     * 
     * @return void
     */
    private function init_config()
    {
        $this->site_key = get_field('recaptcha_v3_site_key', 'option');
        $this->secret_key = get_field('recaptcha_v3_secret_key', 'option');
        $this->mailchimp_api_key = get_field('mailchimp_api_key', 'option');
        $this->mailchimp_list_id_for_organizations_approved_list = get_field('mailchimp_list_id_for_organizations_approved_list', 'option');
    }

    /**
     * Check if required configuration is valid
     * 
     * @return bool True if configuration is valid, false otherwise
     */
    private function is_config_valid()
    {
        return !empty($this->site_key) && !empty($this->secret_key);
    }

    /**
     * Initialize WordPress hooks
     * 
     * @return void
     */
    private function init_hooks()
    {
        // Enqueue reCAPTCHA scripts on pages with affiliate registration form
        add_action('wp_enqueue_scripts', [$this, 'enqueue_recaptcha_script']);
        
        // Add hidden input field for reCAPTCHA response
        add_action('woocommerce_register_form', [$this, 'add_recaptcha_input']);
        
        // Validate reCAPTCHA on form submission
        add_filter('yith_wcaf_check_affiliate_validation_errors', [$this, 'validate_recaptcha']);
        
        // Add approved affiliates to Mailchimp (only if Mailchimp is configured)
        if ($this->is_mailchimp_configured()) {
            add_action('yith_wcaf_affiliate_approved', [$this, 'add_affiliate_to_mailchimp']);
        }
    }

    /**
     * Check if Mailchimp is properly configured
     * 
     * @return bool True if Mailchimp is configured, false otherwise
     */
    private function is_mailchimp_configured()
    {
        return !empty($this->mailchimp_api_key) && !empty($this->mailchimp_list_id_for_organizations_approved_list);
    }

    /**
     * Check if current page contains affiliate registration form
     * 
     * @return bool True if page has affiliate registration form, false otherwise
     */
    private function has_affiliate_registration_form()
    {
        return is_page() && has_shortcode(get_post()->post_content, 'yith_wcaf_registration_form');
    }

    /**
     * Enqueue Google reCAPTCHA v3 script and inject token handler
     * 
     * Only loads on pages containing the affiliate registration form shortcode.
     * 
     * @return void
     */
    public function enqueue_recaptcha_script()
    {
        // Only enqueue on pages with affiliate registration form
        if (!$this->has_affiliate_registration_form()) {
            return;
        }

        // Enqueue Google reCAPTCHA v3 API script
        wp_enqueue_script(
            'google-recaptcha-v3',
            'https://www.google.com/recaptcha/api.js?render=' . esc_attr($this->site_key),
            array(),
            '3.0',
            true
        );

        // Add inline JavaScript for token handling
        $inline_script = $this->get_recaptcha_inline_script();
        wp_add_inline_script('google-recaptcha-v3', $inline_script);
    }

    /**
     * Generate inline JavaScript for reCAPTCHA token handling
     * 
     * @return string JavaScript code for token handling
     */
    private function get_recaptcha_inline_script()
    {
        $site_key = esc_js($this->site_key);
        
        return "
            /**
             * Execute reCAPTCHA and populate hidden input with token
             */
            function executeRecaptcha() {
                if (typeof grecaptcha !== 'undefined') {
                    grecaptcha.ready(function() {
                        grecaptcha.execute('{$site_key}', {action: 'affiliate_registration'}).then(function(token) {
                            var input = document.getElementById('g-recaptcha-response');
                            if (input) {
                                input.value = token;
                                console.log('reCAPTCHA token generated successfully');
                            }
                        }).catch(function(error) {
                            console.error('reCAPTCHA execution failed:', error);
                        });
                    });
                } else {
                    console.error('reCAPTCHA not loaded');
                }
            }

            // Initialize when DOM is ready
            document.addEventListener('DOMContentLoaded', function() {
                // Execute reCAPTCHA on page load
                executeRecaptcha();

                // Handle form submission
                var form = document.querySelector('.yith-wcaf-registration-form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        var input = document.getElementById('g-recaptcha-response');
                        
                        // If no token present, prevent submission and generate new token
                        if (!input || !input.value) {
                            e.preventDefault();
                            console.log('No reCAPTCHA token found, generating new one...');
                            
                            executeRecaptcha();
                            
                            // Resubmit form after token generation
                            setTimeout(function() {
                                if (input && input.value) {
                                    form.submit();
                                } else {
                                    alert('reCAPTCHA verification failed. Please refresh the page and try again.');
                                }
                            }, 1000); // Increased timeout for better reliability
                        }
                    });
                }
            });
        ";
    }

    /**
     * Add hidden input field for reCAPTCHA response token
     * 
     * This field will be populated by JavaScript with the reCAPTCHA token.
     * 
     * @return void
     */
    public function add_recaptcha_input()
    {
        // Only add input on pages with affiliate registration form
        if (!$this->has_affiliate_registration_form()) {
            return;
        }

        echo '<input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response" value="" />';
    }

    /**
     * Server-side reCAPTCHA v3 validation
     * 
     * Validates the reCAPTCHA token submitted with the form and checks
     * the score against the minimum threshold.
     * 
     * @param WP_Error $errors Existing validation errors
     * @return WP_Error Updated errors object
     */
    public function validate_recaptcha($errors)
    {
        // Check if reCAPTCHA response is present
        if (empty($_POST['g-recaptcha-response'])) {
            $errors->add('recaptcha_missing', __('reCAPTCHA verification is required.', 'yith-woocommerce-affiliates'));
            return $errors;
        }

        $recaptcha_response = sanitize_text_field($_POST['g-recaptcha-response']);

        // Verify token with Google's API
        $verification_result = $this->verify_recaptcha_token($recaptcha_response);
        
        if (is_wp_error($verification_result)) {
            $errors->add('recaptcha_error', $verification_result->get_error_message());
            return $errors;
        }

        // Check if verification was successful
        if (!$verification_result['success']) {
            $error_message = !empty($verification_result['error-codes']) 
                ? __('reCAPTCHA verification failed: ', 'yith-woocommerce-affiliates') . implode(', ', $verification_result['error-codes'])
                : __('reCAPTCHA verification failed. Please try again.', 'yith-woocommerce-affiliates');
            
            $errors->add('recaptcha_failed', $error_message);
            return $errors;
        }

        // Check score threshold (reCAPTCHA v3 specific)
        if (isset($verification_result['score']) && $verification_result['score'] < $this->min_recaptcha_score) {
            error_log("reCAPTCHA score too low: {$verification_result['score']} (minimum: {$this->min_recaptcha_score})");
            $errors->add('recaptcha_score_low', __('Security verification failed. Please try again.', 'yith-woocommerce-affiliates'));
        }

        return $errors;
    }

    /**
     * Verify reCAPTCHA token with Google's API
     * 
     * @param string $token The reCAPTCHA response token
     * @return array|WP_Error Verification result or error
     */
    private function verify_recaptcha_token($token)
    {
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->secret_key,
                'response' => $token,
                'remoteip' => $this->get_user_ip()
            ],
            'timeout' => 10,
            'sslverify' => true
        ]);

        // Handle HTTP errors
        if (is_wp_error($response)) {
            error_log('reCAPTCHA API request failed: ' . $response->get_error_message());
            return new WP_Error('recaptcha_request_failed', __('Unable to verify reCAPTCHA. Please try again.', 'yith-woocommerce-affiliates'));
        }

        // Parse response
        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('reCAPTCHA API response parsing failed: ' . json_last_error_msg());
            return new WP_Error('recaptcha_parse_failed', __('reCAPTCHA verification failed. Please try again.', 'yith-woocommerce-affiliates'));
        }

        return $result;
    }

    /**
     * Get user's IP address with proxy support
     * 
     * @return string User's IP address
     */
    private function get_user_ip()
    {
        // Check for various proxy headers
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = sanitize_text_field($_SERVER[$header]);
                // Handle comma-separated IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP address
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Fallback to REMOTE_ADDR even if it's private/reserved
        return !empty($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
    }

    /**
     * Add approved affiliate to Mailchimp list
     * 
     * Automatically subscribes approved affiliates to the configured Mailchimp list.
     * 
     * @param int $affiliate_id The affiliate post ID
     * @return void
     */
    public function add_affiliate_to_mailchimp($affiliate_id)
    {
        // Validate affiliate ID
        if (empty($affiliate_id) || !is_numeric($affiliate_id)) {
            error_log('Invalid affiliate ID provided: ' . $affiliate_id);
            return;
        }

        // Get user data from affiliate
        $user_id = get_post_field('post_author', $affiliate_id);
        if (!$user_id) {
            error_log('No user found for affiliate ID: ' . $affiliate_id);
            return;
        }

        $user = get_userdata($user_id);
        if (!$user || !is_email($user->user_email)) {
            error_log('Invalid user data for affiliate ID: ' . $affiliate_id);
            return;
        }

        // Prepare user data
        $subscriber_data = [
            'email' => $user->user_email,
            'first_name' => $user->first_name ?: '',
            'last_name' => $user->last_name ?: '',
            'display_name' => $user->display_name ?: ''
        ];

        // Add to Mailchimp
        $result = $this->subscribe_to_mailchimp($subscriber_data);
        
        if (is_wp_error($result)) {
            error_log('Mailchimp subscription failed for affiliate ' . $affiliate_id . ': ' . $result->get_error_message());
        } else {
            error_log('Successfully added affiliate ' . $affiliate_id . ' (' . $subscriber_data['email'] . ') to Mailchimp');
        }
    }

    /**
     * Subscribe user to Mailchimp list
     * 
     * @param array $subscriber_data User data including email, first_name, last_name
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function subscribe_to_mailchimp($subscriber_data)
    {
        if (!$this->is_mailchimp_configured()) {
            return new WP_Error('mailchimp_not_configured', 'Mailchimp is not properly configured');
        }

        // Extract datacenter from API key
        $dc = $this->get_mailchimp_datacenter();
        if (!$dc) {
            return new WP_Error('invalid_api_key', 'Invalid Mailchimp API key format');
        }

        // Generate subscriber hash (MD5 of lowercase email)
        $subscriber_hash = md5(strtolower($subscriber_data['email']));
        
        // Build API URL
        $url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$this->mailchimp_list_id_for_organizations_approved_list}/members/{$subscriber_hash}";

        // Prepare request body
        $request_body = [
            'email_address' => $subscriber_data['email'],
            'status_if_new' => 'subscribed',
            'status' => 'subscribed',
            'merge_fields' => [
                'FNAME' => $subscriber_data['first_name'],
                'LNAME' => $subscriber_data['last_name']
            ]
        ];

        // Make API request
        $response = wp_remote_request($url, [
            'method' => 'PUT',
            'headers' => [
                'Authorization' => 'apikey ' . $this->mailchimp_api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($request_body),
            'timeout' => 15,
            'sslverify' => true
        ]);

        // Handle response
        if (is_wp_error($response)) {
            return new WP_Error('mailchimp_request_failed', 'Mailchimp API request failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        // Success codes: 200 (updated) or 201 (created)
        if (!in_array($response_code, [200, 201])) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['detail']) ? $error_data['detail'] : 'Unknown Mailchimp API error';
            return new WP_Error('mailchimp_api_error', "Mailchimp API error ({$response_code}): {$error_message}");
        }

        return true;
    }

    /**
     * Extract datacenter from Mailchimp API key
     * 
     * @return string|false Datacenter code or false if invalid
     */
    private function get_mailchimp_datacenter()
    {
        $dash_position = strpos($this->mailchimp_api_key, '-');
        if ($dash_position === false || $dash_position === strlen($this->mailchimp_api_key) - 1) {
            return false;
        }
        
        return substr($this->mailchimp_api_key, $dash_position + 1);
    }
}

// Initialize the class only if YITH WooCommerce Affiliates is active
if (class_exists('YITH_WCAF')) {
    new YITH_Affiliate_Recaptcha_V3();
} else {
    error_log('YITH_Affiliate_Recaptcha_V3: YITH WooCommerce Affiliates plugin is not active');
}