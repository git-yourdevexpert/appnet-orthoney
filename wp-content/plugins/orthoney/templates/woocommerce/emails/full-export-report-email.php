<?php
defined('ABSPATH') || exit;
?>

<p>Hello,</p>

<p>The fulfillment report for the selected date range is ready. Please find the CSV attached to this email.</p>

<h3>Summary</h3>
<ul>
    <li><strong>Date Range:</strong> <?php echo esc_html($date_range); ?></li>
    <li><strong>Total Orders:</strong> <?php echo esc_html($order_count); ?></li>
</ul>

<h3>Additional Notes</h3>
<p>This report includes all completed and processing orders between the selected dates.</p>

<p>If you have any questions or need further assistance, feel free to contact support.</p>

<p>Thank you,<br>
Your Team</p>