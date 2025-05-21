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

        add_filter('acf/fields/select/query/key=field_67e12644ba5fe',array($this, 'populate_choose_customer_select_field'), 10, 2);
        add_filter('acf/fields/select/query/key=field_67e12685ba5ff',array($this, 'populate_choose_organization_select_field'), 10, 2);

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

   

    
public function populate_choose_customer_select_field($args, $field) {
    $args['results'] = [];

    // Get search term from request
    $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

    // Fetch users with customer role (may include those with multiple roles)
    $users = get_users([
        'role'    => 'customer',
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'number'  => 50, // increase slightly so you can filter after
        'meta_query' => [
            'relation' => 'OR',
            [
                'key'     => 'first_name',
                'value'   => $search,
                'compare' => 'LIKE',
            ],
            [
                'key'     => 'last_name',
                'value'   => $search,
                'compare' => 'LIKE',
            ],
        ],
    ]);

    foreach ($users as $user) {
        $roles = (array) $user->roles;

        // Skip users with more than one role or if role isn't exactly 'customer'
        if (count($roles) !== 1 || $roles[0] !== 'customer') {
            continue;
        }

        $first_name = get_user_meta($user->ID, 'first_name', true);
        $last_name  = get_user_meta($user->ID, 'last_name', true);
        $full_name  = trim($first_name . ' ' . $last_name);

        if (empty($full_name)) {
            $full_name = $user->display_name;
        }

        $args['results'][] = [
            'id'   => $user->ID,
            'text' => $full_name,
        ];
    }

    return $args;
}
public function populate_choose_organization_select_field($args, $field) {
    $args['results'] = [];

    // Get search string from Select2 (passed as ?s=...)
    $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';

    // Fetch users with role 'yith_affiliate'
    $users = get_users([
        'role'    => 'yith_affiliate',
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'number'  => 20,
        'meta_query' => [
            'relation' => 'OR',
            [
                'key'     => '_yith_wcaf_name_of_your_organization',
                'value'   => $search,
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_yith_wcaf_city',
                'value'   => $search,
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_yith_wcaf_state',
                'value'   => $search,
                'compare' => 'LIKE',
            ],
            [
                'key'     => '_yith_wcaf_zipcode',
                'value'   => $search,
                'compare' => 'LIKE',
            ],
        ],
    ]);

foreach ($users as $user) {
    $organization_name = trim(get_user_meta($user->ID, '_yith_wcaf_name_of_your_organization', true));
    $city              = trim(get_user_meta($user->ID, '_yith_wcaf_city', true));
    $state             = trim(get_user_meta($user->ID, '_yith_wcaf_state', true));
    $code              = trim(get_user_meta($user->ID, '_yith_wcaf_zipcode', true));

    // Build location string only if values exist
    $location_parts = array_filter([$city, $state, $code]);
    $location       = $location_parts ? ' (' . implode(', ', $location_parts) . ')' : '';

    // Fallback if org name is missing
    $label = $organization_name ?: 'Unnamed Organization';
    $label .= $location;

    $args['results'][] = [
        'id'   => $user->ID,
        'text' => $label,
    ];
}


    return $args;
}

    
}

new OAM_SALES_REPRESENTATIVE_Custom();