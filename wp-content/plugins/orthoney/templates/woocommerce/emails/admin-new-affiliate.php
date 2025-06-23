<?php
/**
 * New affiliate email template
 *
 * @author YITH <plugins@yithemes.com>
 * @package YITH\Affiliates\Templates
 * @version 1.0.0
 */

/**
 * Template variables:
 *
 * @var $email_heading          string
 * @var $email                  WC_Email
 * @var $affiliate              YITH_WCAF_Affiliate
 * @var $affiliate_referral_url string
 */

if ( ! defined( 'YITH_WCAF' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php
$user = $affiliate->get_user();

$user_id = $user->ID;

if ( ! $user ) {
	return;
}

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
<p>
	Hello, <?php echo get_bloginfo( 'name' ); ?>
</p>
<p>
	A new organization, <?php echo esc_html($organization); ?> from <?php echo esc_html($city) ?>, <?php echo esc_html($state) ?>, has registered on the Honey From The Heart platform and is awaiting your approval.
</p>

<p>
	<strong>Organization Details:</strong><br/><br/>
	
	<strong>First Name: </strong> <span><?php echo esc_html($first_name); ?></span><br/>
	<strong>Last Name: </strong> <span><?php echo esc_html($last_name); ?></span><br/>
	<strong>Organization Name: </strong> <span><?php echo esc_html($organization); ?></span><br/>
	<strong>Website: </strong> <span><?php echo esc_html($website); ?></span><br/>
	<strong>Phone Number: </strong> <span><?php echo esc_html($phone_number); ?></span><br/>
	<strong>Email: </strong>
	<span class="email">
		<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a>
	</span>
	<br/>
	<strong>Tax ID: </strong> <span><?php echo esc_html($tax_id); ?></span><br/>
	<strong>Full Address: </strong> <span><?php echo esc_html($address); ?></span><br/>
	<strong>How They Heard About Us: </strong> <span><?php echo esc_html($oam_heart); ?></span><br/>

</p>
<p>
	Please log into your admin dashboard to review and take the appropriate action.<br>
	<strong>We're always excited to welcome new partners to our hive.</strong><br>
	<strong>Let's keep things buzzing!</strong><br>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
