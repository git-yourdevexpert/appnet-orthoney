<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


class OAM_USER_Role {
    /**
     * Constructor to hook into WooCommerce template loading.
     */
    public function __construct() {
        add_action('init', array($this, 'create_user_roles'));
    }

    /**
     * Template override callback
     */

    public function create_user_roles() {
     if (!get_role('affiliate_team_member')) {
            add_role('affiliate_team_member', 'Affiliate Team Member', [
                'read'         => true,
                'edit_posts'   => false,
                'delete_posts' => false,
            ]);
        }
    }
}

new OAM_USER_Role();