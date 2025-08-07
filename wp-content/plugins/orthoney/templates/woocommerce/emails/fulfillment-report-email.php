<?php
defined('ABSPATH') || exit;
?>

<p>Hello,</p>

<p>The fulfillment report for the selected date range is now ready. Please find the CSV file attached.</p>

<ul>
    <li><strong>Date Range:</strong> <?php echo esc_html($date_range); ?></li>
    <li><strong>Total Orders:</strong> <?php echo esc_html($order_count); ?></li>
</ul>


<p>This report includes all orders marked as Completed or Processing within the specified date range.</p>

<p>If you have any questions or need further assistance, feel free to contact support.</p>

<p>Thank you.</p>