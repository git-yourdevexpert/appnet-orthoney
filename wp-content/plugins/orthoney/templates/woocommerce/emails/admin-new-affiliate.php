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
?>
<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<p>
	Hello, <?php echo get_bloginfo( 'name' ); ?>
</p>
<p>
	A new organization has successfully registered on the platform and is currently awaiting your approval.
</p>

<p>
	<strong>Organization Details:</strong><br/><br/>
	
	<strong>OrganizationName: </strong> <span><?php echo get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true) ?></span>
	
	<br/>
	
	<strong>
		<?php echo esc_html_x( 'Email:', '[EMAILS] New affiliate email', 'yith-woocommerce-affiliates' ); ?>
	</strong>&nbsp;
	<span class="email">
		<a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a>
	</span>
	
	<br/>
	<strong>Contact Person: </strong> <span><?php echo get_user_meta($user_id, '_yith_wcaf_first_name', true) ?> <?php echo get_user_meta($user_id, '_yith_wcaf_last_name', true) ?></span><br/>
	
</p>
<p>
	Please review and take the appropriate action through your admin dashboard.
</p>
<p>
	Best regards,  <br/>
	<?php echo get_bloginfo( 'name' ); ?>
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
