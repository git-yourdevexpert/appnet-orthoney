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
        // add_action('admin_footer', array($this, 'custom_token_validation_script'), 10, 1);
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
    
    /**
         * 
         * Start Token change
         */
        global $wpdb;

            $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;
            $wc_order_relation          = $wpdb->prefix . 'oh_wc_order_relation';
            $order_process_table        = $wpdb->prefix . 'oh_order_process';
            $recipient_order            = $wpdb->prefix . 'oh_recipient_order';

            OAM_COMMON_Custom::sub_order_error_log(print_r($_POST, true), 'change-org-token');
            $orgNewToken = $_POST['yith_wcaf_affiliate_meta']['token'] ?? '';
            $a_id = $_POST['affiliate_id'];

            // Get current (old) token from DB
            $orgOldToken = $wpdb->get_var($wpdb->prepare(
                "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE ID = %d",
                $a_id
            ));

            $orgUserID = $wpdb->get_var($wpdb->prepare(
                "SELECT user_id FROM {$yith_wcaf_affiliates_table} WHERE ID = %d",
                $a_id
            ));

            OAM_COMMON_Custom::sub_order_error_log("Start Affiliate token update: $orgOldToken => $orgNewToken (User ID: $orgUserID)", 'change-org-token');
            OAM_COMMON_Custom::sub_order_error_log("Process by {$user_id}", 'change-org-token');
            
            if ($orgNewToken !== $orgOldToken) {
                OAM_COMMON_Custom::sub_order_error_log("Affiliate Token changed from $orgOldToken to $orgNewToken", 'change-org-token');

                update_user_meta( $orgUserID, '_orgCode', $orgNewToken);
                update_user_meta( $orgUserID, '_affiliate_org_code', $orgNewToken);
                // Update all wc_order_relation records

                $order_relations = $wpdb->get_results(
                    $wpdb->prepare("SELECT DISTINCT wc_order_id FROM {$wc_order_relation} WHERE affiliate_code = %s", $orgOldToken)
                );

                if ($order_relations) {
                    error_log("wc_order_relation data update");
                    $wpdb->update(
                        $wc_order_relation,
                        ['affiliate_code' => $orgNewToken],
                        ['affiliate_code' => $orgOldToken]
                    );

                    $yith_wcaf_referral[] = $orgNewToken;

                    foreach ($order_relations as $data) {
                        
                        OAM_COMMON_Custom::sub_order_error_log("Effected oder data update ".$data->wc_order_id, 'change-org-token');
                        $order = wc_get_order($data->wc_order_id);
                        if ($order) {
                            $order_id = $order->get_id();
                            $order->update_meta_data('_yith_wcaf_referral', $orgNewToken);
                            $order->update_meta_data('_yith_wcaf_referral_history', maybe_serialize($yith_wcaf_referral));
                            $order->save();

                            // $result = $wpdb->get_row($wpdb->prepare(
                            //     "SELECT data FROM {$order_process_table} WHERE order_id = %d",
                            //     $order_id
                            // ));

                            // if ($result) {
                            //     $decoded_data = json_decode($result->data, true);
                            //     $decoded_data['affiliate_select'] = $orgUserID;

                            //     $wpdb->update(
                            //         $order_process_table,
                            //         ['data' => wp_json_encode($decoded_data)],
                            //         ['order_id' => $order_id]
                            //     );
                            // }
                        }
                    }
                }
                // Update recipient order table
                $recipient_rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$recipient_order} WHERE affiliate_token = %s",
                    $orgOldToken
                ));

                if ($recipient_rows) {
                    $wpdb->update(
                        $recipient_order,
                        ['affiliate_token' => $orgNewToken],
                        ['affiliate_token' => $orgOldToken]
                    );
                }
                OAM_COMMON_Custom::sub_order_error_log("Finish Affiliate token update", 'change-org-token');
            }
        /**
         * End Token change
         */



        $token = $_POST['yith_wcaf_affiliate_meta']['token'];
        

        // First, check if an entry already exists with this user_id and token
        $existing_token_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->prefix}yith_wcaf_affiliates WHERE user_id = %d AND token = %s",
            $user_id,
            ''
        ));

        // If exists, update the token
        if ( $existing_token_id ) {
            $wpdb->update(
                $wpdb->prefix . 'yith_wcaf_affiliates',
                ['token' => $token],
                ['ID' => $existing_token_id]
            );
        }

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
            $token_error .= 'Error: The affiliate token is already in use. Please choose a unique token.';

    
            if (strlen($token) !== 3) {
                $token_error .= 'Error: The affiliate token must be exactly 3 characters long.';
            }
            $token = $existing_user;
            global $wpdb;
            $table_name = $wpdb->prefix . 'yith_wcaf_affiliates';
           $wpdb->update(
                $table_name,
                [
                    'banned' => 0,
                    'enabled' => 0
                ],
                ['user_id' => $user_id],
                ['%d', '%d'],  
                ['%d']  
            );
            // setcookie('token_error', $token_error);
            setcookie('token_error', '', time() - 3600, '/');
            unset($_COOKIE['token_error']);

        }
    
        $_POST['yith_wcaf_affiliate_meta']['token'] = $token;
        
        if ($token_error != '') {
            ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    setTimeout(function() {
                        // Function to get and decode a cookie value by name
                        function getCookie(name) {
                            let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                            return match ? decodeURIComponent(match[2]) : null; // Decode the cookie value
                        }

                        
                            var targetElement = document.querySelector('#yith_wcaf_affiliate_meta_token-container');

                            if (targetElement) {
                                var noticeDiv = document.createElement('div');
                                noticeDiv.className = 'notice notice-error 111';
                                noticeDiv.innerHTML = '<p><?php echo $token_error ?></p>';
                                targetElement.appendChild(noticeDiv);
                            }
                        
                    }, 1000); // Delay execution by 1 second
                });
            </script>
            <?php
        }
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