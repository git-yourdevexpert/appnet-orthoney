<?php
/**
 * Affiliate disabled email template
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

<p>Dear, <?php echo  $first_name; echo $last_name;?></p>

<p>Thank you for your interest in Honey From The Heart. At this time, we’re unable to approve <strong><?php echo $organization ?></strong> registration.</p>
<p>If you have any questions or believe this was in error, please contact our team at <?php echo get_option('admin_email') ?>.</p>
<p>Warm regards,<br>
<strong>The Honey From The Heart Team</strong><br>
<i>The Sweet Way To Raise Money</i></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
