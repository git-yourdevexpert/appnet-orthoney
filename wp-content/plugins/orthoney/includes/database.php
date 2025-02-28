<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


// Function to create custom tables
function ort_honey_create_custom_tables() {
    global $wpdb;

    // Set the table names with the WordPress prefix
    $recipient_table = $wpdb->prefix . 'recipient';
    $recipient_temp_table = $wpdb->prefix . 'recipient_temp';
    $recipient_group_table = $wpdb->prefix . 'recipient_group';
    $recipient_group_relationships_temp_table = $wpdb->prefix . 'recipient_group_relationships_temp';
    $affiliate = $wpdb->prefix . 'orm_affiliate';
    // Define the table name
    $order_process = $wpdb->prefix . 'order_process';

    // Load the upgrade script
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // SQL to create the recipient table
    $recipient_sql = "CREATE TABLE $recipient_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        address_1 TEXT NULL,
        address_2 TEXT NULL,
        city VARCHAR(255) NOT NULL,
        state VARCHAR(255) NOT NULL,
        country VARCHAR(255) NOT NULL,
        zipcode VARCHAR(50) NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        visibility TINYINT(1) DEFAULT 1,
        greeting VARCHAR(255)  NULL,
        reasons TEXT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

    // Execute the SQL for recipient table
    dbDelta($recipient_sql);

    // SQL to create the recipient table
    $recipient_temp_sql = "CREATE TABLE $recipient_temp_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        address_1 TEXT NULL,    
        address_2 TEXT NULL,
        city VARCHAR(255) NOT NULL,
        state VARCHAR(255) NOT NULL,
        country VARCHAR(255) NOT NULL,
        zipcode VARCHAR(50) NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        visibility TINYINT(1) DEFAULT 1,
        greeting VARCHAR(255)  NULL,
        reasons TEXT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

    // Execute the SQL for recipient table
    dbDelta($recipient_temp_sql);

    // SQL to create the recipient_group table
    $recipient_group_sql = "CREATE TABLE $recipient_group_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        greeting VARCHAR(255)  NULL,
        csv_path VARCHAR(255)  NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";
    // Execute the SQL for recipient_group table
    dbDelta($recipient_group_sql);

    // SQL to create the recipient_group table
    $recipient_group_relationships_temp_sql = "CREATE TABLE $recipient_group_relationships_temp_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        group_id BIGINT(20) NOT NULL,
        recipient_id BIGINT(20) NOT NULL,
        user_id BIGINT(20) NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

    // Execute the SQL for recipient_group table
    dbDelta($recipient_group_relationships_temp_sql);

     // SQL query to create the table
     $affiliate_sql = "CREATE TABLE IF NOT EXISTS $affiliate (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Execute the SQL for recipient_group table
    dbDelta($affiliate_sql);


    // SQL to create the order_process table
    $order_process_sql = "CREATE TABLE $order_process (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        name TEXT NULL,
        data LONGTEXT NULL,
        step VARCHAR(255) NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";
    // Execute the SQL for recipient_group table
    dbDelta($order_process_sql);

}