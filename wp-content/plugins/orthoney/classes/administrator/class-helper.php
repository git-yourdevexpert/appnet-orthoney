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
        if ($text != '') {
            // Replace newlines with |||BR|||
            $text = str_replace(["\r\n", "\r", "\n"], '|||BR|||', $text);
            
            // 4. Replace curly quotes & special punctuation with ASCII equivalents
            $replacements = [
                '’' => "'", '‘' => "'", '“' => '"', '”' => '"',
                '…' => '...', '–' => '-', '—' => '-', '•' => '-'
            ];

            $text = strtr($text, $replacements);
            // 1. Fix double-encoded UTF-8 (e.g. â¤ -> ❤)
            $text = utf8_encode(utf8_decode($text));
            
            // 2. Strip slashes and decode HTML entities
            $text = stripslashes($text);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            
            
            // 3. Fix specific broken characters (misencoded sequences)
            $text = str_replace(
                ['â€™', 'â€œ', 'â€', 'â€"', 'â€"'],
                ["'", '"', '"', '-', '—'],
                $text
            );
            
            // 5. REMOVE ALL EMOJIS & PICTOGRAPHS - PLAIN TEXT ONLY
            // Remove ALL emoji ranges for complete plain text
            $text = preg_replace('/[\x{1F000}-\x{1FAFF}]/u', '', $text);     // All modern emojis
            $text = preg_replace('/[\x{2600}-\x{27BF}]/u', '', $text);       // Symbols & Dingbats
            $text = preg_replace('/[\x{2300}-\x{23FF}]/u', '', $text);       // Miscellaneous Technical
            $text = preg_replace('/[\x{2B00}-\x{2BFF}]/u', '', $text);       // Miscellaneous Symbols and Arrows
            $text = preg_replace('/[\x{25A0}-\x{25FF}]/u', '', $text);       // Geometric Shapes
            $text = preg_replace('/[\x{2190}-\x{21FF}]/u', '', $text);       // Arrows
            $text = preg_replace('/[\x{2000}-\x{206F}]/u', '', $text);       // General Punctuation symbols
            $text = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $text);       // Variation Selectors
            $text = preg_replace('/\x{200D}/u', '', $text);                  // Zero Width Joiner
            $text = preg_replace('/[\x{20D0}-\x{20FF}]/u', '', $text);       // Combining Diacritical Marks for Symbols
            $text = preg_replace('/[\x{1F100}-\x{1F1FF}]/u', '', $text);     // Enclosed Alphanumeric Supplement
            
            // Remove any remaining non-ASCII symbols that might be decorative
            $text = preg_replace('/[^\x{0020}-\x{007E}\x{00A0}-\x{024F}\x{1E00}-\x{1EFF}\s]/u', '', $text);
            
            // 6. Remove leftover control characters (non-printable)
            $text = preg_replace('/[^\P{C}\n]+/u', '', $text);
            
            // 7. Normalize spaces
            $text = preg_replace('/\s+/u', ' ', $text);
            
            // 8. Remove leftover replacement chars (� or "??")
            $text = preg_replace('/\x{FFFD}|\?{2,}/u', '', $text);
            
            // 9. Trim final
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
        return '';
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

      public static function update_wc_order_status_send_mail_callback($update_status_args = 0, $offset = 0) {
        global $wpdb;

        $order_table = $wpdb->prefix . 'wc_orders';
        $meta_table = "{$wpdb->prefix}wc_orders_meta";
        $chunk_size  = 50;

        // Main status aggregation query (unchanged)
        $sql = $wpdb->prepare("
            SELECT 
                order_id,
                CONCAT(
                    '{',
                    GROUP_CONCAT(CONCAT('\"', Status, '\":', cnt) SEPARATOR ','),
                    '}'
                ) AS status_counts
            FROM (
                SELECT 
                    order_id,
                    Status,
                    COUNT(*) AS cnt
                FROM {$wpdb->prefix}oh_wc_jar_order
                WHERE Status <> ''
                AND YEAR(created_date) = YEAR(CURDATE())
                GROUP BY order_id, Status
            ) AS t
            GROUP BY order_id
            LIMIT %d OFFSET %d
        ", $chunk_size, $offset);

        $results = $wpdb->get_results($sql);

        if (empty($results)) {
            return;
        }

        // Step 1: Build array of required order_ids
        $order_ids = array_map(function($row) { return $row->order_id; }, $results);

        // Step 2: Bulk fetch WooCommerce order mapping
        $placeholders = implode(',', array_fill(0, count($order_ids), '%s'));
        $meta_query = $wpdb->prepare("
            SELECT meta_value as oh_order_id, order_id as wc_order_id
            FROM {$wpdb->prefix}wc_orders_meta
            WHERE meta_key = '_orthoney_OrderID'
            AND meta_value IN ($placeholders)
        ", ...$order_ids);

        $meta_map = [];
        foreach ($wpdb->get_results($meta_query) as $meta) {
            $meta_map[$meta->oh_order_id] = $meta->wc_order_id;
        }
        
        $mailer = WC()->mailer();
        
        foreach ($results as $row) {
            $wc_order_id = isset($meta_map[$row->order_id]) ? $meta_map[$row->order_id] : null;
            $meta_key = 'send_mail_customer';
            $meta_value = 1;
            $order_id = $wc_order_id;

            if (empty($wc_order_id)) {
                continue;
            }

            $order_quantity = OAM_AFFILIATE_Helper::get_quantity_by_order_id($wc_order_id);
            $status_array = json_decode($row->status_counts, true);

            if (!is_array($status_array) || !isset($status_array['Shipped'])) {
                continue;
            }

            $shipped_count = (int) $status_array['Shipped'];
            $update_status = ($shipped_count >= $order_quantity) ? 'wc-shipped' : 'wc-partial-shipped';

            if($update_status_args == 1){
                $wpdb->update(
                    $order_table,
                    [ 'status' => $update_status ],
                    [ 'id' => $wc_order_id ],
                    [ '%s' ],
                    [ '%d' ]
                );
            }
            
            $send_mail_customer_status = OAM_COMMON_Custom::get_order_meta($wc_order_id, 'send_mail_customer')?: 0;
            if ( $send_mail_customer_status != 1 ) {
                $emails = $mailer->get_emails();
                $email_sent = false;

               

                // Trigger email if status changes to partial-shipped
                if ( $update_status === 'wc-partial-shipped' && !empty( $emails['WC_Email_Partial_Shipped'] ) ) {
                    $email = $emails['WC_Email_Partial_Shipped'];
                    if ( $email->is_enabled() ) {
                        $email->trigger( $wc_order_id );
                        $email_sent = true;
                    }
                }

                  // Trigger email if status changes to shipped
                if ( $update_status === 'wc-shipped' && !empty( $emails['WC_Email_Shipped'] ) ) {
                    $email = $emails['WC_Email_Shipped'];
                    if ( $email->is_enabled() ) {
                        $email->trigger( $wc_order_id );
                        $email_sent = true;
                    }
                

                    // ✅ Mark email as sent
                    if ( $email_sent ) {
                        // Check if meta exists
                        $existing = $wpdb->get_var( $wpdb->prepare(
                            "SELECT meta_id FROM {$meta_table} WHERE order_id = %d AND meta_key = %s",
                            $order_id,
                            $meta_key
                        ) );

                        if ( $existing ) {
                            // Update existing meta
                            $wpdb->update(
                            $meta_table,
                                [ 'meta_value' => $meta_value ],
                                [ 'meta_id' => $existing ],
                                [ '%d' ],
                                [ '%d' ]
                            );
                        }else{
                            $wpdb->insert(
                                $meta_table,
                                [
                                    'order_id'    => $order_id,
                                    'meta_key'   => $meta_key,
                                    'meta_value' => $meta_value
                                ],
                                [ '%d', '%s', '%d' ]
                            );
                        }
                    }
                }
            }else{
                $wpdb->insert(
                    $meta_table,
                    [
                        'order_id'    => $order_id,
                        'meta_key'   => $meta_key,
                        'meta_value' => $meta_value
                    ],
                    [ '%d', '%s', '%d' ]
                );
            }
            // Trigger email if status changes to shipped or partial-shipped
        }
        
        return $offset + $chunk_size;
    }

}

// Initialize the class
new OAM_ADMINISTRATOR_HELPER();
