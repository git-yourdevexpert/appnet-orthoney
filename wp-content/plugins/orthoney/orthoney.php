<?php
/*
Plugin Name: ORT Honey
Description: This is ORT Honey plugin!
Author: ORT Honey
Requires Plugins: woocommerce
*/

require_once plugin_dir_path(__FILE__) . 'includes/multiStepForm.php';

require_once plugin_dir_path(__FILE__) . 'includes/database.php';

register_activation_hook(__FILE__, 'ort_honey_create_custom_tables');

require_once plugin_dir_path(__FILE__) . 'classes/class-custom.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-scripts.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-ajax.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-shortcode.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-wc.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-hooks.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-user-role.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-login-registration.php';

require_once plugin_dir_path(__FILE__) . 'classes/class-recipient-multistep-form.php';