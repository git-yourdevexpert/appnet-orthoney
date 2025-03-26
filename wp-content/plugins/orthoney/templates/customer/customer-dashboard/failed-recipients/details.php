<?php
$recipient_id = get_query_var('failed-recipients-details');

if (!$recipient_id) {
    echo "<p>Invalid Recipient ID</p>";
    return;
}


?>
