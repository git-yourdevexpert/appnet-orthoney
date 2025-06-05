<?php
/*
Plugin Name: ORT Honey
Description: This is ORT Honey plugin!
Author: ORT Honey
Requires Plugins: woocommerce
*/
// Prevent direct access

if (!defined('ABSPATH')) {
    exit;
}

require_once 'libs/dompdf/autoload.inc.php';

add_filter( 'woocommerce_checkout_fields', 'custom_disable_shipping_fields_validation' );
add_filter('woocommerce_create_order_draft_enabled', '__return_false');

function custom_disable_shipping_fields_validation( $fields ) {
    if ( isset( $fields['shipping'] ) ) {
        foreach ( $fields['shipping'] as $key => $field ) {
            $fields['shipping'][$key]['required'] = false;
        }
    }
    return $fields;
}

function custom_rename_coupon_to_voucher( $translated, $text, $domain ) {
    $text = str_ireplace('coupon', 'Voucher', $text);
    $text = str_ireplace('Coupon', 'Voucher', $text);
    return $text;
}
add_filter( 'gettext', 'custom_rename_coupon_to_voucher', 10, 3 );
add_filter( 'ngettext', 'custom_rename_coupon_to_voucher', 10, 3 );


// add_action( 'init', 'createDB' );
// function createDB(){
//     global $wpdb;

// $tables = [
//     'wc_order_relation_table' => $wpdb->prefix . 'oh_wc_order_relation',
// ];

// $sql = "CREATE TABLE {$tables['wc_order_relation_table']} (
//     id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//             user_id BIGINT(20) UNSIGNED NOT NULL,
//             wc_order_id BIGINT(20) UNSIGNED NOT NULL,
//             order_id BIGINT(20) UNSIGNED NOT NULL,
//             quantity BIGINT(20) UNSIGNED NOT NULL,
//             order_type VARCHAR(255) NULL,
//             affiliate_code VARCHAR(255) NULL,
//             affiliate_user_id BIGINT(20) DEFAULT 0,
//             created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
//             PRIMARY KEY (id)
// ) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

// require_once ABSPATH . 'wp-admin/includes/upgrade.php';
// dbDelta($sql);
// }



// Ensure WooCommerce is active before loading the plugin
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p><strong>Warning:</strong> Your custom plugin requires WooCommerce to be activated.</p></div>';
    });
    return;
}

// Define required columns
$required_columns = ['full name','Company Name', 'Mailing Address', 'Suite/Apt#', 'city', 'state', 'zipcode', 'quantity', 'greeting'];

if ( ! defined( 'OH_REQUIRED_COLUMNS' ) ) {
    define( 'OH_REQUIRED_COLUMNS', $required_columns );
}

if ( ! defined( 'OH_PLUGIN_DIR_URL' ) ) {
	define( 'OH_PLUGIN_DIR_URL', plugin_dir_url(__FILE__) );
}
if ( ! defined( 'OH_PLUGIN_DIR_PATH' ) ) {
	define( 'OH_PLUGIN_DIR_PATH', plugin_dir_path(__FILE__)  );
}
if ( ! defined( 'OH_DOMAIN' ) ) {
	define( 'OH_DOMAIN', 'ORTHONEY'  );
}


$link_array = array(
    'customer_dashboard_link'             => 'CUSTOMER_DASHBOARD_LINK', 
    'organization_dashboard_link'         => 'ORGANIZATION_DASHBOARD_LINK',
    'administrator_dashboard_link'        => 'ADMINISTRATOR_DASHBOARD_LINK',
    'sales_representative_dashboard_link' => 'SALES_REPRESENTATIVE_DASHBOARD_LINK',
    'customer_login_link'                 => 'CUSTOMER_LOGIN_LINK',
    'customer_register_link'              => 'CUSTOMER_REGISTER_LINK',
    'organization_login_link'             => 'ORGANIZATION_LOGIN_LINK',
    'organization_register_link'          => 'ORGANIZATION_REGISTER_LINK',
    'order_process_link'                  => 'ORDER_PROCESS_LINK'
);

foreach ($link_array as $key => $constant_name) {
    if ( ! defined($constant_name) ) {
        $link = get_field($key, 'options');
        if ($link) {
            define($constant_name, $link['url']);
        }else{
            define($constant_name, home_url());
        }
    }
}

// Include required files
require_once OH_PLUGIN_DIR_PATH . 'includes/database.php';

require_once OH_PLUGIN_DIR_PATH . 'classes/class-wc.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-cron-sub-order.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-hooks.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-common-custom.php';
require_once OH_PLUGIN_DIR_PATH . 'classes/class-user-role.php';
// require_once OH_PLUGIN_DIR_PATH . 'classes/class-login-registration.php';


// YITH Affilate Plugin Custmization
require_once OH_PLUGIN_DIR_PATH . 'classes/yith-affilate-custimization.php';

// Affiliate
require_once OH_PLUGIN_DIR_PATH . 'classes/affiliate/class-affiliate.php';

// Customer
require_once OH_PLUGIN_DIR_PATH . 'classes/customer/class-customer.php';

// Sales Representative
require_once OH_PLUGIN_DIR_PATH . 'classes/sales-representative/class-sales-representative.php';

// Administrator
require_once OH_PLUGIN_DIR_PATH . 'classes/administrator/class-administrator.php';

// Register activation hook
// register_activation_hook(__FILE__, 'orthoney_create_custom_tables');

// Refresh database if requested
if(isset($_GET['database_refresh']) && ($_GET['database_refresh'] == 'okay' OR $_GET['database_refresh'] == 'new') ){
    add_action('init', 'orthoney_create_custom_tables');
}

add_action( 'init', 'add_customer_role_to_affiliates' );

function add_customer_role_to_affiliates() {

    if( isset($_GET['add_customer_role_to_affiliates']) && $_GET['add_customer_role_to_affiliates'] == 'yes'){
        $affiliates = get_users( array(
            'role' => 'yith_affiliate'
        ) );

        foreach ( $affiliates as $user ) {
            if ( ! in_array( 'customer', (array) $user->roles ) ) {
                $user->add_role( 'customer' );
            }
        }
    }

    if( isset($_GET['season_popup']) && $_GET['season_popup'] == 'yes'){

        ?>
        <?php
    }
}

if ( ! function_exists( 'user_registration_pro_generate_magic_login_link' ) ) {
    function user_registration_pro_generate_magic_login_link( $email, $nonce, $redirect_url ) {
        $user  = get_user_by( 'email', $email );
        $token = ur_generate_onetime_token( $user->ID, 'ur_passwordless_login', 32, 60 );

        update_user_meta( $user->ID, 'ur_passwordless_login_redirect_url' . $user->ID, $redirect_url );

        $custom_url = ''; // Default login link
        $user_roles = OAM_COMMON_Custom::get_user_role_by_id($user->ID);

        $custom_url = OAM_COMMON_Custom::redirect_user_based_on_role($user_roles);
        if($custom_url == home_url('wp-admin')){
            $custom_url = home_url();
        }

        $arr_params = array( 'action', 'uid', 'token', 'nonce' );
        $url_params = array(
            'uid'   => $user->ID,
            'token' => $token,
            'nonce' => $nonce,
        );
        $url = add_query_arg( $url_params, $custom_url );

        return $url;
    }
}
 



function season_start_end_message_box_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => '',
        'popup' => '',
    ), $atts);

    if ($atts['type'] === 'order') {
        ?>
        <style>
             #page-content{
            background:#ddd;
        }
        .us_custom_76d41440 {
            padding-top: 0rem !important;
    padding-bottom: 1rem !important;
        }
        .l-section.wpb_row.us_custom_19737bc8 .g-cols.vc_row{
            display:none;
        }
        </style>
        <?php
    }
    ob_start();
    ?>
    <style>
       
    #countdown ul {
        margin: 0; padding: 0; display: flex; align-items: flex-start;
        justify-content: center; gap: 10px; flex-wrap: wrap;
    }
    #countdown li {
        font-size: 15px; list-style-type: none; text-align: center;
        width: auto; padding: 10px; border: 1px solid var(--color-content-secondary);
        color: var(--color-content-secondary); width: 78px; margin: 0;
    }
    #countdown li span {
        display: block; font-size: 25px; line-height: 25px; font-weight: 800;
    }
    .bee-animation {
        animation: smoothMovement 8s infinite ease-in-out;
        transition: all 0.3s ease; right: 0; top: 0px;
    }
    @keyframes smoothMovement {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(30px); }
    }
    .popupbox {
        display: flex; 
        top: 0; left: 0; width: 100%; height: 100%;
        align-items: center; justify-content: center; padding: 0 20px;
        z-index: 9999;
    }
    .popupbox.withpopup{
    position: fixed;
    background-color: rgba(0,0,0,0.8);
    }
    .popupbox .content-wrapper {
        max-width: 850px; width: 100%; margin: 20px auto; position: relative;
    }
    .popupbox .content-wrapper .top-content {
        text-align: center; max-height: 70vh; overflow-y: auto;
        padding: 40px; background-color: #fff;
    }
    .popupbox .content-wrapper .bottom-content {
        width: 100%; height: 100px;
        background: url(https://orthoney.appnet-projects.com/wp-content/uploads/2025/06/honey-drop-white.png) repeat-x top center / auto 100px;
    }
    .popupbox .bee-animation {
        position: absolute; right: 0;
    }
    .popupbox .close-popup {
        display:none
    }
    .popupbox.withpopup .close-popup {
        position: absolute; left: 20px; top: 20px; font-weight: 700;
        font-size: 20px; color: #000; width: 30px; height: 30px;
        align-items: center; justify-content: center; display: flex;
        border-radius: 100%; border: 1px solid #000; cursor: pointer;
    }
    .popupbox .subtext {
        font-family: Open Sans; font-weight: 500; font-size: 18px;
        line-height: 1.5; color: var(--color-content-heading); margin-bottom: 15px;
    }
    .popupbox p {
        color: var(--color-content-primary); margin-bottom: 0; padding-bottom: 0;
    }
    .popupbox .border-top-bottom {
        font-family: Yesteryear; padding: 20px 0; margin: 20px 0;
        border-top: 1px solid #a4afb3; border-bottom: 1px solid #a4afb3;
        font-size: 27px; line-height: 1.2; color: var(--color-content-heading);
    }
    .popupbox .notifyme {
        display: flex; flex-wrap: wrap; justify-content: center; align-items: center;
        background: #f0f0f0; margin-bottom: 20px; padding: 10px 30px; gap: 20px;
    }
    .popupbox .notifyme span {
        font-size: 16px; font-family: Open Sans; font-weight: 500;
        font-style: italic; padding: 0; margin: 0;
    }
    </style>

    <div class="popupbox <?php echo $atts['popup'] ?>">
    <div class="content-wrapper">
        <div class="bee-animation"><img decoding="async" width="72" height="72" src="https://orthoney.appnet-projects.com/wp-content/uploads/2025/06/bee.png" class="attachment-full size-full" alt="Bee" loading="lazy"></div>
        <div class="close-popup" onclick="this.closest('.popupbox').style.display='none'">x</div>
        <div class="top-content">
            <h2>The Hive’s Just Waking Up… Get Ready for the Buzz!</h2>
            <div class="subtext">We’re almost ready to launch this season’s buzz-worthy tradition and trust us, it’s going to bee amazing!</div>
            <p>Our honey isn’t flowing just yet, but the hive opens for gifting on <strong>June 12th 2025</strong></p>
            <div class="border-top-bottom">Please come back soon to Send Honey. Share Hope. Spread Joy.</div>
            <div class="notifyme">
                <span>Your friends at Honey From The Heart</span>
                <a class="w-btn us-btn-style_2 us_custom_29a0f245" href="https://www.orthoney.com/sign-up/"><span class="w-btn-label">Notify Me</span></a>
            </div>
            <div id="countdown">
                <ul>
                    <li><span id="days"></span>Days</li>
                    <li><span id="hours"></span>Hours</li>
                    <li><span id="minutes"></span>Minutes</li>
                    <li><span id="seconds"></span>Seconds</li>
                </ul>
            </div>
        </div>
        <div class="bottom-content"></div>
    </div>
    </div>

    <script>
    (function () {
        const second = 1000,
              minute = second * 60,
              hour = minute * 60,
              day = hour * 24;

        let today = new Date(),
            dd = String(today.getDate()).padStart(2, "0"),
            mm = String(today.getMonth() + 1).padStart(2, "0"),
            yyyy = today.getFullYear(),
            nextYear = yyyy + 1,
            dayMonth = "06/12/",
            birthday = dayMonth + yyyy;

        today = mm + "/" + dd + "/" + yyyy;
        if (today > birthday) {
            birthday = dayMonth + nextYear;
        }

        const countDown = new Date(birthday).getTime(),
            x = setInterval(function () {
                const now = new Date().getTime(),
                      distance = countDown - now;

                document.getElementById("days").innerText = Math.floor(distance / day);
                document.getElementById("hours").innerText = Math.floor((distance % day) / hour);
                document.getElementById("minutes").innerText = Math.floor((distance % hour) / minute);
                document.getElementById("seconds").innerText = Math.floor((distance % minute) / second);

                if (distance < 0) {
                    document.getElementById("countdown").style.display = "none";
                    clearInterval(x);
                }
            }, 1000);
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('season_start_end_message_box', 'season_start_end_message_box_shortcode');



// /affiliate-dashboard-user-list-menu-item
// if (!function_exists('user_has_role')) {
//     function user_has_role($user_id, $roles_to_check = []) {
//         $user = get_userdata($user_id);
        
//         if ($user && !empty($roles_to_check)) {
//             $user_roles = (array) $user->roles;
//             return array_intersect($roles_to_check, $user_roles) ? true : false;
//         }
//         return false;
//     }
// }

// // 🔹 1. Redirect after login
// function custom_login_redirect($redirect_to, $request, $user) {
//     if (isset($user->ID)) {
//         $roles_to_check = ['yith_affiliate', 'affiliate_team_member'];
        
//         if (user_has_role($user->ID, $roles_to_check)) {
//             return site_url('/affiliate-dashboard'); // Change this to your actual affiliate dashboard page
//         }
//     }
//     return $redirect_to;
// }
// add_filter('login_redirect', 'custom_login_redirect', 10, 3);

// // 🔹 2. Prevent admin dashboard access
// function restrict_admin_access() {
//     if (is_user_logged_in()) {
//         $user_id = get_current_user_id();
//         $roles_to_check = ['yith_affiliate', 'affiliate_team_member'];
        
//         if (user_has_role($user_id, $roles_to_check) && is_admin()) {
//             wp_redirect(site_url('/affiliate-dashboard')); // Redirect to Affiliate Dashboard
//             exit;
//         }
//     }
// }
// add_action('admin_init', 'restrict_admin_access');

// // 🔹 3. Hide admin bar for specific users
// function hide_admin_bar_for_affiliates($show_admin_bar) {
//     $user_id = get_current_user_id();
//     $roles_to_check = ['yith_affiliate', 'affiliate_team_member'];
    
//     if (user_has_role($user_id, $roles_to_check)) {
//         return false;
//     }
//     return $show_admin_bar;
// }
// add_filter('show_admin_bar', 'hide_admin_bar_for_affiliates');



// add_filter( 'query', function( $query ) {
//      global $wpdb;

//     $orders_table   = $wpdb->prefix . 'wc_orders';
//     $relation_table = $wpdb->prefix . 'oh_wc_order_relation';

//     if (
//         strpos( $query, $orders_table ) !== false || 
//         strpos( $query, $relation_table ) !== false
//     ) {
//         error_log( 'Query Hooked: ' . $query );
//     }
//     return $query;
// });
