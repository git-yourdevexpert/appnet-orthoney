<?php
defined('ABSPATH') || exit;
?>

<p>Hello,</p>

<p>The manifest report for the selected date range is now available. Please find the attached CSV file for your reference.</p>

<ul>
    <li><strong>Date Range:</strong> <?php echo esc_html($date_range); ?></li>
    <li><strong>Total Orders:</strong> <?php echo esc_html($order_count); ?></li>
</ul>

<p>This report includes all orders placed within the specified date range, along with full order details.</p>

<p>If you have any questions or need further assistance, please don't hesitate to contact our support team.</p>

<p>Thank you.</p>