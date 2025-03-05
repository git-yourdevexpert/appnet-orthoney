<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_AFFILIATE_Helper {
    public function __construct() {}

    public static function init() {
    }

    public static function affiliate_status_check($user_id) {
        global $wpdb;
        $yith_wcaf_affiliates_table = OAM_Helper::$yith_wcaf_affiliates_table;

        // Get affiliate data from the database
        $affiliate = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$yith_wcaf_affiliates_table} WHERE user_id = %d", $user_id)
        );

        // If no record found, user is not an affiliate
        if (!$affiliate) {
            return json_encode(['success' => false, 'message'=> 'You are not registered as an affiliate.']);
        }


        if (isset($affiliate->banned) && $affiliate->banned == 1) {
            $ban_message = get_user_meta($user_id, '_yith_wcaf_ban_message', true);
            return json_encode([
                'success' => false, 
                'message'=> 'You are Banned please contact to the admin.',
                'reason'=> $ban_message
            ]);
            
        } elseif (isset($affiliate->enabled)) {
            if ($affiliate->enabled == 0) {
                
                return json_encode([
                    'success' => false, 
                    'message'=> 'You has been pending Approval.',
                    'reason'=> ''
                ]);

            } elseif ($affiliate->enabled == -1) {
                $reject_message = get_user_meta($user_id, '_yith_wcaf_reject_message', true);
                return json_encode([
                    'success' => false, 
                    'message'=> 'You account has been Rejected.',
                    'reason'=> $reject_message
                ]);
            }
        }
        
        return json_encode([
            'success' => true, 
            'message'=> '',
            'reason'=> ''
        ]);
       
    }

    public static function add_user_affiliate_form() {
        $form = '<form id="addUserForm">
                <label>First Name</label>
                <input type="text" name="first_name" required />
    
                <label>Last Name</label>
                <input type="text" name="last_name" required />
    
                <label>Email</label>
                <input type="email" name="email" required />
    
                <label>Phone Number</label>
                <input type="text" name="phone" required />
    
                <label>Type</label>
                <select name="type">
                    <option value="primary-contact">Primary Contact</option>
                    <option value="co-chair">Co-Chair</option>
                    <option value="alternative-contact">Alternative Contact</option>
                </select>
                <button type="submit">Save</button>
            </form>
            <div id="user-message"></div>';
    
        return $form;
    }    


    
}

// Initialize the class properly
new OAM_AFFILIATE_Helper();
OAM_AFFILIATE_Helper::init();