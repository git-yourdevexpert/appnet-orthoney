<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_Custom {
    /**
     * Constructor to hook into Affiliate template loading.
     */
    public function __construct() {
        add_action('init', array($this, 'affiliate_import_handler'));
        add_action('init', array($this, 'affiliate_dashboard_handler'));
        add_filter('yith_wcaf_registration_form_affiliate_pending_text', array($this, 'custom_affiliate_pending_message'));

        // add_action('wp_footer', [$this, 'maybe_show_loader_and_redirect']);
       
        add_action('user_register', [$this, 'schedule_user_meta'], 10, 1);
        add_action('set_default_user_meta_after_register', [$this, 'handle_user_meta_and_email']);
    }

   
    public function affiliate_import_handler() {
         if ( isset($_GET['sales_representative_data_import']) && $_GET['sales_representative_data_import'] == 'import' ) {
            $sales_reps = get_users( array(
                'role' => 'sales_representative',
            ) );

            foreach ( $sales_reps as $user ) {
                $user->add_role('customer');
            }
        }
 
        if (isset($_GET['affiliate_member_import']) && $_GET['affiliate_member_import'] === 'import') {

            global $wpdb;

            $log_file = WP_CONTENT_DIR . '/affiliate_member_import.log';

            $yith_wcaf_affiliates_table = OAM_helper::$yith_wcaf_affiliates_table;

            $query = "
                SELECT um1.user_id, um2.meta_value AS _userinfo
                FROM {$wpdb->usermeta} um1
                INNER JOIN {$wpdb->usermeta} um2 
                    ON um1.user_id = um2.user_id
                WHERE um1.meta_key = %s 
                AND um2.meta_key = %s
            ";

            $results = $wpdb->get_results($wpdb->prepare($query, 'DJarPrice', '_userinfo'));

            $contact_roles = [
                'Primary'   => 'primary-contact',
                'CoChair'   => 'co-chair',
                'Alternate' => 'alternative-contact',
            ];

            foreach ($results as $row) {
                $userinfo = maybe_unserialize($row->_userinfo);
                if (!is_array($userinfo) || empty($userinfo['Email'])) {
                    continue;
                }

                $main_email = strtolower($userinfo['Email']);

                foreach ($contact_roles as $key => $role_meta_key) {

                    $contact = $userinfo['Distributor'][$key] ?? null;

                    if (!$contact || empty($contact['Email'])) {
                        continue;
                    }

                    $contact_email = strtolower(sanitize_email($contact['Email']));
                    if ($contact_email === $main_email) {
                        continue; // Skip if same as main contact
                    }

                    if (email_exists($contact_email)) {
                        $user_id = email_exists($contact_email);
                        $user = get_user_by('id', $user_id);

                        if ($user) {
                            $roles = $user->roles;
                            if(!in_array( 'administrator' , $roles)){

                                if(!in_array( 'affiliate_team_member' , $roles)){
                                    // Assign roles
                                    $user->add_role('affiliate_team_member');
                                    $user->add_role('customer');

                                    // Update user meta
                                    $phone          = sanitize_text_field($contact['Phone'] ?? '');
                                    $affiliate_type = sanitize_text_field($role_meta_key ?? '');

                                    update_user_meta($user_id, 'phone', $phone);
                                    update_user_meta($user_id, 'user_field_type', $affiliate_type);
                                    update_user_meta($user_id, 'associated_affiliate_id', $row->user_id);

                                    // Get affiliate token
                                    $processtoken = $wpdb->get_var(
                                        $wpdb->prepare(
                                            "SELECT token FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d",
                                            $row->user_id
                                        )
                                    );

                                    // Log info to file
                                    $formatted_message  = '[' . date('Y-m-d H:i:s') . '] ' . PHP_EOL;
                                    $formatted_message .= 'Token: ' . $processtoken . PHP_EOL;
                                    $formatted_message .= 'User ID: ' . $user_id . PHP_EOL;
                                    $formatted_message .= 'Email: ' . $contact_email . PHP_EOL;
                                    $formatted_message .= 'Affiliate Type: ' . $affiliate_type . PHP_EOL;
                                    $formatted_message .= str_repeat('-', 40) . PHP_EOL;

                                    error_log($formatted_message, 3, $log_file);
                                }
                            }
                        }
                    }
                }
            }
        }

        if(isset($_GET['affiliate_data_import']) &&  $_GET['affiliate_data_import'] = 'import') {
            global $wpdb;

            $current_user = get_current_user_id();

            $query = "
                SELECT um1.user_id, um2.meta_value AS _userinfo
                FROM {$wpdb->usermeta} um1
                INNER JOIN {$wpdb->usermeta} um2 
                    ON um1.user_id = um2.user_id
                WHERE um1.meta_key = %s 
                AND um2.meta_key = %s
            ";

            $results = $wpdb->get_results($wpdb->prepare($query, 'DJarPrice', '_userinfo'));

            foreach ($results as $row) {
                $userinfo = maybe_unserialize($row->_userinfo);
                if (!is_array($userinfo) || empty($userinfo['Email'])) {
                    continue;
                }


                $main_email = strtolower($userinfo['Email']);

                // Optional meta updates for current user
                $description = $userinfo['Distributor']['description'] ?? '';
                $card_wording = $userinfo['Distributor']['card_wording'] ?? '';
                update_user_meta($row->user_id, 'mission_statement', sanitize_text_field($description));
                update_user_meta($row->user_id, 'gift_card', sanitize_text_field($card_wording));

                // Contact roles to check
                $contact_roles = [
                    'Primary'   => 'primary-contact',
                    'CoChair'   => 'co-chair',
                    'Alternate' => 'alternative-contact',
                ];

                foreach ($contact_roles as $key => $role_meta_key) {

                    $contact = $userinfo['Distributor'][$key] ?? null;
                
                    if (!$contact || empty($contact['Email'])) {
                        continue;
                    }

                    $contact_email = strtolower(sanitize_email($contact['Email']));
                    if ($contact_email === $main_email) {
                        continue; // Same as main, skip
                    }
                    

                    // Check if user already exists
                    if (email_exists($contact_email)) {
                        continue; // User already exists
                    }

                    $first_name     = sanitize_text_field($contact['FirstName'] ?? '');
                    $last_name      = sanitize_text_field($contact['LastName'] ?? '');
                    $phone          = sanitize_text_field($contact['Phone'] ?? '');
                    $affiliate_type = sanitize_text_field($role_meta_key ?? '');

                    // Create user
                    $password = wp_generate_password();
                    $user_id = wp_create_user($contact_email, $password, $contact_email);

                    if (!is_wp_error($user_id)) {
                        $user = get_user_by('id', $user_id);
                        $user->set_role('affiliate_team_member');
                        $user->add_role('customer');

                        update_user_meta($user_id, 'first_name', $first_name);
                        update_user_meta($user_id, 'last_name', $last_name);
                        update_user_meta($user_id, 'phone', $phone);
                        update_user_meta($user_id, 'user_field_type', $affiliate_type);
                        update_user_meta($user_id, 'associated_affiliate_id', $row->user_id);
                    }
                }
            }
        }
    }

     /**
     * Affiliate Verification Start
     * 
     */
    public function maybe_show_loader_and_redirect() {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            isset($_POST['register_affiliate']) &&
            isset($_POST['_wp_http_referer']) &&
            $_POST['_wp_http_referer'] === '/organization-registration/' &&
            isset($_POST['register']) &&
            $_POST['register'] === 'Register'
        ) {
            ?>
            <div class='loader multiStepForm register_affiliate_loader' style="display:block">
                <div>
                    <h2 class='swal2-title'>Processing...</h2>
                    <div class='swal2-html-container'>Please wait while we process your request.</div>
                    <div class='loader-5'></div>
                </div>
            </div>
            <script>
            jQuery(function($) {
                const message = $(".yith-wcaf.yith-wcaf-registration-form .woocommerce-message").text().trim();
                if (message.includes("Your account was created successfully. Your login details have been sent to your email address.")) {
                    setTimeout(function() {
                        window.location.href = "<?php echo site_url('/organization-login/?registration=success'); ?>";
                    }, 2000);
                } else {
                    $('.register_affiliate_loader').hide();
                }
            });
            </script>
            <?php
        }
    }

    public function schedule_user_meta($user_id) {
       $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user_id);

        if (in_array('yith_affiliate', $user_roles)) {
            // Check if 'first_name' is empty
            $first_name = get_user_meta($user_id, 'first_name', true);
            if (empty($first_name)) {
                $wcaf_first_name = get_user_meta($user_id, '_yith_wcaf_first_name', true);
                if (!empty($wcaf_first_name)) {
                    update_user_meta($user_id, 'first_name', $wcaf_first_name);
                }
            }

            // Check if 'last_name' is empty
            $last_name = get_user_meta($user_id, 'last_name', true);
            if (empty($last_name)) {
                $wcaf_last_name = get_user_meta($user_id, '_yith_wcaf_last_name', true);
                if (!empty($wcaf_last_name)) {
                    update_user_meta($user_id, 'last_name', $wcaf_last_name);
                }
            }
        }
        // if (!as_next_scheduled_action('set_default_user_meta_after_register', [$user_id])) {
        //     as_schedule_single_action(time() + 60, 'set_default_user_meta_after_register', [$user_id]);
        // }
    }

    public function handle_user_meta_and_email($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;

        $token = $this->generate_token($user_id);
        $custom_url = defined('ORGANIZATION_LOGIN_LINK') ? ORGANIZATION_LOGIN_LINK : site_url('/login/');

        if (!metadata_exists('user', $user_id, 'ur_confirm_email_token')) {
            update_user_meta($user_id, 'ur_confirm_email_token', $token);
        }

        if (!metadata_exists('user', $user_id, 'ur_confirm_email')) {
            update_user_meta($user_id, 'ur_confirm_email', 0);
        }

        if (!metadata_exists('user', $user_id, 'ur_login_option')) {
            update_user_meta($user_id, 'ur_login_option', 'email_confirmation');
        }

        $first_name = $user->first_name ? $user->first_name : $user->display_name;
        $email = $user->user_email;
        $nonce = wp_create_nonce('ur_email_verification_' . $user_id);

        $url_params = [
            'uid'      => $user->ID,
            'ur_token' => urlencode($token),
            'nonce'    => $nonce,
        ];
        $verification_link = add_query_arg($url_params, $custom_url);

        $subject = 'Email Verification Required';
        $message = sprintf(
            'Hello %s,<br><br>
            Thank you for registering. Please click the button below to verify your email:<br><br>
            <a href="%s" style="background-color: #4CAF50; border-radius: 5px; color: #ffffff; display: inline-block; font-size: 16px; font-weight: bold; padding: 12px 25px; text-decoration: none; text-transform: uppercase;">Verify Email Address</a><br><br>
            If you did not create this account, please ignore this email.',
            esc_html($first_name),
            esc_url($verification_link)
        );

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        wp_mail($email, $subject, $message, $headers);
    }

    private function generate_token($user_id) {
        $length = 50;
        $token = '';
        $code_alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $max = strlen($code_alphabet);

        for ($i = 0; $i < $length; $i++) {
            $token .= $code_alphabet[random_int(0, $max - 1)];
        }

        if (function_exists('crypt_the_string')) {
            $token .= crypt_the_string($user_id . '_' . time(), 'e');
        }

        return $token;
    }
    /**
     * Affiliate Verification end
     * 
     */

    /**
     * Affiliate callback
     */
    public function affiliate_dashboard_handler() {

        $parsedUrl = ORGANIZATION_DASHBOARD_LINK;
        $newdUrl = str_replace(home_url(), '' ,$parsedUrl);
        $slug = trim($newdUrl, '/');
        
        if (!empty($slug)) {
            $affiliate_dashboard_id = get_page_by_path($slug);
        
            if ($affiliate_dashboard_id) {
                add_rewrite_rule($slug.'/([^/]+)/?$', 'index.php?pagename='.$slug.'&affiliate_endpoint=$matches[1]', 'top');
                add_rewrite_endpoint('affiliate_endpoint', EP_PAGES);
            }
        }
    }

    /**
     * Custom message for affiliate pending approval
     */
    public function custom_affiliate_pending_message($message) {
        return '<div class="affiliate-pending-message">
            <h2>Please Note:</h2>
            <p>An affiliate account will be created only upon admin approval.</p>
            <p>Once approved, the affiliate can log in to the site and will receive email notifications regarding the approval status.</p>
        </div>';
    }
}

new OAM_AFFILIATE_Custom();