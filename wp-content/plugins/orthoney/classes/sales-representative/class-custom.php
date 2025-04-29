<?php 
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OAM_SALES_REPRESENTATIVE_Custom {

    /**
     * Constructor to hook into sales representative template loading.
     */
    public function __construct() {
        add_action('init', array($this, 'sales_representative_dashboard_handler'));
        add_action('wp_head', array($this, 'remove_user_switching_footer_button'));

        add_filter('acf/load_field/name=choose_customer', array($this, 'populate_choose_customer_acf_handler'));
        add_filter('acf/load_field/name=choose_organization', [$this, 'populate_choose_organization_acf_handler']);

    }

    public function remove_user_switching_footer_button() {
        ob_start(function ($buffer) {
            return preg_replace('/<p id="user_switching_switch_on".*?<\/p>/s', '', $buffer);
        });
    }
     /**
     * sales representative callback
     */
    public function sales_representative_dashboard_handler() {

        $parsedUrl = SALES_REPRESENTATIVE_DASHBOARD_LINK;
        $newdUrl = str_replace(home_url(), '' ,$parsedUrl);
        $slug = trim($newdUrl, '/');
        if (!empty($slug)) {
            $sales_representative_dashboard_id = get_page_by_path($slug);
        
            if ($sales_representative_dashboard_id) {
                add_rewrite_rule($slug.'/([^/]+)/?$', 'index.php?pagename='.$slug.'&sales_representative_endpoint=$matches[1]', 'top');
                add_rewrite_endpoint('sales_representative_endpoint', EP_PAGES);
            }
        }
    }

   /**
    * Populate choose customer dropdown with users having only the 'customer' role
    */
    public function populate_choose_customer_acf_handler($field) {
        $screen = get_current_screen();
        if ( isset($screen->id) && $screen->id === 'user-edit') {
            $roles_to_check = ['sales_representative'];
            if (user_has_role($_GET['user_id'], $roles_to_check)) {

            
                if ($field['name'] !== 'choose_customer') {
                    return $field;
                }

                // Fetch all users with 'customer' role
                $args = [
                    'role'    => 'customer',
                    'orderby' => 'display_name',
                    'order'   => 'ASC',
                    
                ];
                $users = get_users($args);

                // Filter users with only the 'customer' role
                $field['choices'] = [];
                foreach ($users as $user) {
                    $user_roles = $user->roles;
                    if (count($user_roles) === 1 && in_array('customer', $user_roles)) {
                        $field['choices'][$user->ID] = $user->display_name;
                    }
                }

                // Check if no customers found
                if (empty($field['choices'])) {
                    $field['choices'] = ['' => 'No Customers Found'];
                }
            }

        }

        return $field;
    }


    /**
     * Populate choose organization dropdown with organization name, city, state, and code
     */
    public function populate_choose_organization_acf_handler($field) {
        $screen = get_current_screen();
        if ( isset($screen->id) && $screen->id === 'user-edit') {
            $roles_to_check = ['sales_representative'];
            if (user_has_role($_GET['user_id'], $roles_to_check)) {
                if ($field['name'] !== 'choose_organization') return $field;
            
                $args = [
                    'role'    => 'yith_affiliate',
                    'orderby' => 'display_name',
                    'order'   => 'ASC',
                    'fields'  => ['ID'],
                ];
                $users = get_users($args);
            
                $field['choices'] = [];
                foreach ($users as $user) {
                    $organization_name = get_user_meta($user->ID, '_yith_wcaf_name_of_your_organization', true) ?: 'Unknown Organization';
                    $city = get_user_meta($user->ID, '_yith_wcaf_city', true) ?: 'Unknown City';
                    $state = get_user_meta($user->ID, '_yith_wcaf_state', true) ?: 'Unknown State';
                    $code = get_user_meta($user->ID, '_yith_wcaf_zipcode', true) ?: 'N/A';
            
                    $field['choices'][$user->ID] = "$organization_name ($city, $state, $code)";
                }
            
                return $field;
            }
        }
        return $field;
    }

    
}

new OAM_SALES_REPRESENTATIVE_Custom();