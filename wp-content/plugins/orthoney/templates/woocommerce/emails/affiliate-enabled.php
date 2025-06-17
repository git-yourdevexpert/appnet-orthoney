<?php
/**
 * Affiliate enabled email template
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Affiliates\Templates
 * @version 1.0.0
 */

/**
 * Template variables:
 *
 * @var $email_heading    string
 * @var $email            WC_Email
 * @var $affiliate        YITH_WCAF_Affiliate
 * @var $new_status       string
 * @var $old_status       string
 * @var $additional_notes string
 * @var $user             WP_User
 * @var $display_name     string
 */

if ( ! defined( 'YITH_WCAF' ) ) {
	exit;
} // Exit if accessed directly

$user = $affiliate->get_user();

$user_id = $user->ID;
$affiliate_token = '';
global $wpdb;
        $table = $wpdb->prefix . 'yith_wcaf_affiliates';
        $affiliate_token = $wpdb->get_var($wpdb->prepare("
            SELECT token FROM {$table} WHERE user_id = %d
        ", $user_id));

$admin_email     = get_option('admin_email');



$organization = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true)?: '';
$first_name = get_user_meta($user_id, '_yith_wcaf_first_name', true)?: '';
$last_name = get_user_meta($user_id, '_yith_wcaf_last_name', true)?: '';
$city = get_user_meta($user_id, '_yith_wcaf_city', true)?: '';
$state = get_user_meta($user_id, '_yith_wcaf_state', true)?: '';
$zipcode = get_user_meta($user_id, '_yith_wcaf_zipcode', true)?: '';
$tax_id = get_user_meta($user_id, '_yith_wcaf_tax_id', true)?: '';
$website = get_user_meta($user_id, '_yith_wcaf_your_organizations_website', true)?: '';
$phone_number = get_user_meta($user_id, '_yith_wcaf_phone_number', true)?: '';
$oam_heart = get_user_meta($user_id, '_yith_wcaf_oam_heart', true)?: '';
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>


<p>Dear, <?php echo  $yith_first_name; echo $yith_last_name;?></p>
<p>Welcome to the Honey From The Heart family.  Your organization has been approved!</p>
<p>Your unique 3-digit organization code is: <strong><?php echo $affiliate_token ?> </strong></p>
<p>You can now log in using your email address and [<strong>initial password or instructions</strong>] (please be sure to update your password).</p>
<p>Your dashboard is now live, giving you full access to manage orders, view reports, and engage with supporters.</p>
<p><strong><i>Sell just 50 jars at $18 each and earn at least $2 per jar. Sell over 100 jars and earn $4 per jar!</i></strong></p>
<p>If you have any questions, don’t hesitate to reach out to our team at <?php echo get_option('admin_email') ?>. We’re here to help every step of the way.</p>
<p>Here’s to a sweet and successful fundraising season. Welcome to our sweet family!</p>
<p>Warm regards,<br>
<strong>The Honey From The Heart Team</strong><br>
<i>The Sweet Way To Raise Money</i></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
