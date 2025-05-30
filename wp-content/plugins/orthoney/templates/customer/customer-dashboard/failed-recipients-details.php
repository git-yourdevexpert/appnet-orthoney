<?php
if (!defined('ABSPATH')) {
    exit;
}
$recipient_id = get_query_var('failed-recipients-details');

echo do_shortcode("[recipient_multistep_form]");
?>
