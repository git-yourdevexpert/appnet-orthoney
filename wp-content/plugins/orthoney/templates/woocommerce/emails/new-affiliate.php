<?php
/**
 * New Affiliate email template
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
 * @var $display_name     string
 * @var $user             WP_User
 */

if ( ! defined( 'YITH_WCAF' ) ) {
	exit;
} // Exit if accessed directly
$user = $affiliate->get_user();

$user_id = $user->ID;


$organization = get_user_meta($user_id, '_yith_wcaf_name_of_your_organization', true)?: '';
$first_name = get_user_meta($user_id, '_yith_wcaf_first_name', true)?: '';
$last_name = get_user_meta($user_id, '_yith_wcaf_last_name', true)?: '';
$city = get_user_meta($user_id, '_yith_wcaf_city', true)?: '';
$state = get_user_meta($user_id, '_yith_wcaf_state', true)?: '';
$zipcode = get_user_meta($user_id, '_yith_wcaf_zipcode', true)?: '';
$tax_id = get_user_meta($user_id, '_yith_wcaf_tyour_organizations_website', true)?: '';
$phone_number = get_user_meta($user_id, '_yith_wax_id', true)?: '';
$website = get_user_meta($user_id, '_yith_wcaf_caf_phone_number', true)?: '';
$oam_heart = get_user_meta($user_id, '_yith_wcaf_oam_heart', true)?: '';


do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>Dear, <?php echo $first_name .' '.$last_name ?></p>
<p>Thank you for your interest in bringing <strong>Honey From The Heart</strong> to <strong><?php echo esc_html($organization); ?></strong>! </p>
<p>While you await approval, here’s a little more about how this sweet program can benefit your community and cause:</p>
<p><strong>About Honey From The Heart</strong></p>
<p><a href="<?php echo site_url()?>">Honey From The Heart</a> is a non-profit initiative designed to help Jewish organizations raise meaningful funds with a gift that touches hearts and celebrates tradition. 100% of the profit goes directly toward Jewish causes.</p>
<p>Partnering with hundreds of Jewish non-profits across the country for over 30 years, we've become adept at honey fundraising. The numbers speak for themselves. Honey From The Heart volunteers served over 200 participating Jewish organizations and shipped over  50,000 jars of honey!</p>
<p><strong>What’s in the Honey Package?</strong></p>
<ul>
<li>8 oz. jar of OU Kosher honey, with a festive Rosh Hashanah label and gold bow</li>
<li>A personalized gift card from the sender</li>
<li>Blessings for apples and honey, printed on a keepsake card</li>
</ul>
<p><strong>Why Fundraise With Us?</strong></p>
<ul>
<li>Your own custom 3-digit code and organization name listed on our order dropdown</li>
<li>Your own dashboard to manage orders and track performance</li>
<li>Customer service and marketing support</li>
<li>Reorder forms</li>
<li>No upfront costs</li>
</ul>
<p><strong>Shipping Info:</strong></p>
<ul>
<li>We ship nationwide perfectly timed for Rosh Hashanah</li>
<li>Free shipping on orders placed through <strong>August 4</strong></li>
<li>$8 per jar shipping applies after <strong>August 4</strong> (covered by the sender)</li>
<li>Free bulk shipping to a single address any time</li>
</ul>

<p>If you have any questions feel free to reach out to us at <?php echo get_option('admin_email') ?>.</p>
<p>We look forward to working with you on this outstanding fundraiser!  </p>

<p>Warm regards,<br>
<strong>The Honey From The Heart Team</strong><br>
<i>The Sweet Way To Raise Money</i></p>

<p><strong>TESTIMONIALS</strong></p>
<p><i>"This is such an amazing fundraiser!!  I am happy to chair it each year! Your volunteers are greatly appreciated!!!  I send honey orders all over the county and it is such a great way to send sweet wishes around to friends and family!!!  AND so easy!!!  We of course appreciate the fund development component and use the funds for amazing opportunities." Melissa Singer, Membership VP, Hadassah Valley of the Sun (Scottsdale, AZ)</i></p>
<p><i>“This was the easiest fundraiser we`ve ever done!”  Elaine Kaplan, Temple Sholom Sisterhood (Cincinnati, OH)</i></p>
<p><i>“This is a great fundraiser. It's a win/win/win situation. ORT benefits, our sisterhood benefits and the recipients love to receive their honey gifts.” Grace Schessler, Midway Jewish Center Sisterhood, (Syosset, NY)</i></p>
<p><i>"The positive response from both senders and recipients has been overwhelming! "S. Stein, Beth Sholom Synagogue, (Memphis, TN)</i></p>
<p><i>"We`ve never worked with a more conscientious or better organized group. It's always been a pleasure working with you!" Candy Familant, Peninsula Chapter ORT (Virginia)</i></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
