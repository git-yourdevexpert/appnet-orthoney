<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_YITH_Affilate {
    /**
     * Constructor to hook into WooCommerce template loading.
     */
    public function __construct() {        
        add_filter('yith_wcaf_affiliate_token', array($this, 'custom_affiliate_token'), 10, 2);
        add_action('yith_wcaf_before_save_profile_fields', array($this, 'custom_token_validation'), 10, 1);
        add_action('admin_footer', array($this, 'custom_token_validation_script'), 10, 1);
    }
    
    public function custom_affiliate_token($token, $user_id) {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'yith_wcaf_affiliates';
    
        $userfirst_name = get_user_meta( $token, 'first_name', true );
        $userlast_name = get_user_meta( $token, 'last_name', true );
        
        // Get user data safely
        
        $first_name = isset($userfirst_name) ? strtoupper(substr($userfirst_name, 0, 2)) : 'XX';
        $last_name = isset($userlast_name) ? strtoupper(substr($userlast_name, 0, 1)) : 'X';
    
        $base_token = $first_name . $last_name;
        $random_part = strtoupper($userfirst_name . $userlast_name);
    
        // Ensure uniqueness with fallback mechanism
        $attempts = 0;
        do {
            $new_token = ($attempts == 0) ? $base_token : OAM_AFFILIATE_Helper::getRandomChars($random_part, 3);
    
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE token = %s",
                $new_token
            ));
            $attempts++;
    
            // Prevent infinite loops by setting a limit
            if ($attempts > 50) {
                $new_token = strtoupper(wp_generate_password(3, false, false)); // Purely random fallback
                break;
            }
    
        } while ($existing > 0);
    
        // Store the new token in the database
        $wpdb->update(
            $table_name,
            ['token' => $new_token],
            ['user_id' => $user_id],
            ['%s'],
            ['%d']
        );
    
        update_user_meta($token, 'associated_affiliate_id', $token);
        

        return $new_token;
    }

    public  function custom_token_validation($user_id) {
        global $wpdb;
        $token = $_POST['yith_wcaf_affiliate_meta']['token'];
        $token_error = '';
        $token = str_replace("-1", "", $token);
    
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE token = %s AND user_id != %d",
            $token, $user_id
        ));
    
        if ($existing != 0 || (strlen($token) !== 3)) {
            $existing_user = $wpdb->get_var($wpdb->prepare(
                "SELECT token FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id = %d",
                $user_id
            ));
            $token_error = 'Error: The affiliate token is already in use. Please choose a unique token.';

    
            if (strlen($token) !== 3) {
                $token_error = 'Error: The affiliate token must be exactly 3 characters long.';
            }
            $token = $existing_user;
            global $wpdb;
            $table_name = $wpdb->prefix . 'yith_wcaf_affiliates';
            
            $wpdb->update(
                $table_name,
                ['banned' => 1],
                ['user_id' => $user_id],
                ['%d'],
                ['%d']
            );

        }
    
        $_POST['yith_wcaf_affiliate_meta']['token'] = $token;
        
        setcookie('token_error', $token_error);
    }
    

    public function custom_token_validation_script() { ?>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                // Function to get and decode a cookie value by name
                function getCookie(name) {
                    let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                    return match ? decodeURIComponent(match[2]) : null; // Decode the cookie value
                }

                var errorMessage = getCookie("token_error"); // Get and decode the cookie value

                if (errorMessage) {
                    var targetElement = document.querySelector('#yith_wcaf_affiliate_meta_token-container');

                    if (targetElement) {
                        var noticeDiv = document.createElement('div');
                        noticeDiv.className = 'notice notice-error';
                        noticeDiv.innerHTML = '<p>' + errorMessage + '</p>';
                        targetElement.appendChild(noticeDiv);
                    }

                    // Clear the cookie after displaying the message
                    document.cookie = "token_error=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                }
            }, 1000); // Delay execution by 1 second
        });
        </script>
        <?php
    }
}

new OAM_YITH_Affilate();