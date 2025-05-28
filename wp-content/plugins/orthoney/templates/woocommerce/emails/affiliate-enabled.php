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
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
	<?php
	// translators: 1. Affiliate formatted name.
	echo esc_html( sprintf( _x( 'Hello %s,', '[EMAILS] Affiliate enabled email', 'yith-woocommerce-affiliates' ), get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true) ) );
	?>
</p>

<p>We are pleased to inform you that your registration has been approved by the administrator.</p>
<p><strong>Organization Code:</strong> <?php echo $affiliate_token ?></p>
<p>You can now access your organization dashboard using this code and start managing your activities on the platform.</p>
<p>If you have any questions or need assistance, feel free to contact our <a href="mailto:<?php echo esc_attr( $admin_email ); ?>"><?php echo esc_html( $admin_email ); ?></a>.</p>

Welcome aboard!  
<?php echo get_bloginfo('name') ?><br>
Support Team <a href="mailto:<?php echo esc_attr( $admin_email ); ?>"><?php echo esc_html( $admin_email ); ?></a>
</p>


<?php do_action( 'woocommerce_email_footer', $email ); ?>
