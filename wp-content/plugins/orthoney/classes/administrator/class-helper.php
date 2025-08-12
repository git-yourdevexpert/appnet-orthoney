<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_ADMINISTRATOR_HELPER {
    /**
     * Constructor to hook into HELPERer template loading.
     */
    public function __construct() {
        // Initialization code can go here if needed
    }

    public static function cleanup_pure_text($text = '') {
        if($text != ''){
            // 1. Fix UTF-8 misencoding
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

            // 2. Fix specific broken characters (misencoded sequences)
            $text = str_replace(
                ['â€™', 'â€œ', 'â€', 'â€“', 'â€”'],
                ["'", '"', '"', '-', '—'],
                $text
            );

            // 3. Replace curly quotes & special punctuation with ASCII equivalents
            $replacements = [
                '’' => "'", '‘' => "'", '“' => '"', '”' => '"',
                '…' => '...', '–' => '-', '—' => '-', '•' => '-'
            ];
            $text = strtr($text, $replacements);

            // 4. Remove emojis & pictographs
            $emoji_patterns = [
                '/[\x{1F600}-\x{1F64F}]/u', // Emoticons
                '/[\x{1F300}-\x{1F5FF}]/u', // Symbols & pictographs
                '/[\x{1F680}-\x{1F6FF}]/u', // Transport & map
                '/[\x{2600}-\x{26FF}]/u',   // Misc symbols
                '/[\x{2700}-\x{27BF}]/u',   // Dingbats
                '/[\x{1F900}-\x{1F9FF}]/u', // Supplemental Symbols
                '/[\x{1FA70}-\x{1FAFF}]/u', // Symbols & Pictographs Extended-A
            ];
            foreach ($emoji_patterns as $pattern) {
                $text = preg_replace($pattern, '', $text);
            }

            // 5. Remove leftover control characters
            $text = preg_replace('/[^\P{C}\n]+/u', '', $text);

            // 6. Normalize spaces
            $text = preg_replace('/\s+/u', ' ', $text);

            // 7. Trim final
            return trim($text);
        }
        return $text;
    }
    
    public static function administrator_dashboard_navbar($user_roles = array()) {
        $output = '';
        if (in_array('administrator', $user_roles)) {
            $output = '<div class="affiliate-dashboard">';
            $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK) . '">Dashboard</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK . '/my-profile/') . '">My Profile</a></div>';
            $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK . '/orders-list/') . '">Order List</a></div>';
            if (!in_array('affiliate_team_member', $user_roles)) {
                $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK . '/change-admin/') . '">Change Admin</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK . '/link-customer/') . '">Link Customer</a></div>';
                $output .= '<div class="btn"><a href="' . esc_url(ADMINISTRATOR_DASHBOARD_LINK . '/users-list/') . '">User List</a></div>';
            }
            $output .= '<div class="btn"><a href="' . esc_url(wp_logout_url(home_url())) . '">Logout</a></div>';
            $output .= '</div>';
        }
        return $output;
    }
    
    public static function org_details_popup_callback() {
        ?>
        <div id="view_org_details_popup" class="lity-popup-normal lity-hide">
            <div class="popup-show order-process-block orthoney-datatable-warraper">
                <h3 class="popup-title"><span></span> Organization details</h3>
                <div class="affiliate-dashboard pb-40 mb-40">
                    <div id="org-details-content" class="recipient-view-details-wrapper">
                        <div class="recipient-view-details-wrapper">
                            <h6>Organization Profile:</h6>
                            <ul>
                                <li><strong>Website:</strong> <span id="org-website">www.abelgusikowski.com</span></li>
                                <li><strong>Address :</strong> <span id="org-full-address">123 Main St, Anytown, USA</span></li>
                                <li><strong>Phone :</strong> <span id="org-phone">123-456-7890</span></li>
                                <li><strong>Tax ID  :</strong> <span id="org-tax-id">43445345</span></li>
                            </ul>
                        </div>
                        <div class="recipient-view-details-wrapper">
                            <h6>Remittance:</h6>
                            <ul>
                                <li><strong>Make Check Payable to:</strong> <span id="org-check_payable">QA - AbelGusikowski's</span></li>
                                <li><strong>Address to Send Check to:</strong> <span id="org-check_address">www.abelgusikowski.com</span></li>
                                <li><strong>To the Attention of :</strong> <span id="org-check_attention">123 Main St, Anytown, USA</span></li>
                                <li><strong>Please indicate if check will be mailed to a home or your organization's office :</strong> <span id="org-check_office">43445345</span></li>
                            </ul>
                        </div>
                        <div class="recipient-view-details-wrapper">
                            <h6>Product Price:</h6>
                            <ul>
                                <li><strong><span id="product_price">$18.00</span></strong></li>
                            </ul>
                        </div>
                        <div class="recipient-view-details-wrapper">
                            <h6>Gift Card:</h6>
                            <ul>
                                <li><strong>In celebration of the New Year, a donation has been made in your name to </strong><span id="gift_card"></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the class
new OAM_ADMINISTRATOR_HELPER();
