<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}


// Function to create custom tables
function orthoney_create_custom_tables() {


    // Ensure directories exist
    $directories = [
        OAM_Helper::$all_uploaded_csv_dir,
        OAM_Helper::$process_recipients_csv_dir,
        OAM_Helper::$group_recipients_csv_dir
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
    }

    global $wpdb;

    // Set the table names with the WordPress prefix
    $recipient_table = $wpdb->prefix . 'oh_group_recipient';
    $group_table = $wpdb->prefix . 'oh_group';


    $order_process_recipient_table = $wpdb->prefix . 'oh_order_process_recipient';
    $files_upload_activity_log_table = $wpdb->prefix . 'oh_files_upload_activity_log';
    $order_process_table = $wpdb->prefix . 'oh_order_process';
    $order_process_recipient_activate_log_table = $wpdb->prefix . 'oh_order_process_recipient_activate_log';

    
    
    $affiliate_customer_relation = $wpdb->prefix . 'oh_affiliate_customer_relation';
    
    $affiliate_table = $wpdb->prefix . 'oh_orm_affiliate';

    if(isset($_GET['database_refresh']) && $_GET['database_refresh'] == 'okay' ){
        // Execute DROP TABLE queries
        $wpdb->query("DROP TABLE IF EXISTS $order_process_recipient_table");
        $wpdb->query("DROP TABLE IF EXISTS $files_upload_activity_log_table");
        $wpdb->query("DROP TABLE IF EXISTS $order_process_table");
        $wpdb->query("DROP TABLE IF EXISTS $order_process_recipient_activate_log_table");

        $wpdb->query("DROP TABLE IF EXISTS $recipient_table");
        $wpdb->query("DROP TABLE IF EXISTS $group_table");
        
        $wpdb->query("DROP TABLE IF EXISTS $affiliate_customer_relation");

        $wpdb->query("DROP TABLE IF EXISTS $affiliate_table");

    }


    // Load the upgrade script
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // SQL to create the recipient table
    $recipient_sql = "CREATE TABLE $recipient_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        group_id BIGINT(20) UNSIGNED NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        address_1 TEXT NULL,    
        address_2 TEXT NULL,
        city VARCHAR(255) NOT NULL,
        state VARCHAR(255) NOT NULL,
        zipcode VARCHAR(50) NOT NULL,
        quantity VARCHAR(50) NOT NULL,
        verified TINYINT(1) DEFAULT 0,
        greeting VARCHAR(255) NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";
    // Execute the SQL for recipient table


    // SQL to create the recipient_group table
    $recipient_group_sql = "CREATE TABLE $group_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";
    // Execute the SQL for recipient_group table

    

    // SQL to create the recipient table
    $order_process_recipient_sql = "CREATE TABLE $order_process_recipient_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        pid BIGINT(20) UNSIGNED NOT NULL,
        full_name VARCHAR(255) NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        address_1 TEXT NULL,    
        address_2 TEXT NULL,
        city VARCHAR(255) NOT NULL,
        state VARCHAR(255) NOT NULL,
        zipcode VARCHAR(50) NOT NULL,
        quantity VARCHAR(50) DEFAULT 1,
        verified TINYINT(1) DEFAULT 0,
        address_verified TINYINT(1) DEFAULT 0,
        visibility TINYINT(1) DEFAULT 1,
        new TINYINT(1) DEFAULT 1,
        greeting VARCHAR(255) NULL,
        update_type VARCHAR(255) NULL,
        reasons TEXT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

    // Execute the SQL for recipient table

    // SQL to create the CSV upload activated log table
    $files_upload_activity_log_sql = "CREATE TABLE $files_upload_activity_log_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        related_id BIGINT(20) UNSIGNED NOT NULL,
        name VARCHAR(255) NOT NULL,
        status TINYINT(1) DEFAULT 0,
        method VARCHAR(255) NOT NULL,
        update_log TEXT NULL,
        user_agent TEXT NULL,
        user_ip TEXT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";
    

    // SQL to create the recipient temp activated log table
    $order_process_recipient_activate_log_sql = "CREATE TABLE $order_process_recipient_activate_log_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        recipient_id BIGINT(20) UNSIGNED NOT NULL,
        type VARCHAR(255) NOT NULL,
        update_log TEXT NULL,
        user_agent TEXT NULL,
        user_ip TEXT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";

    // Execute the SQL for recipient table
    


    
    // SQL to create the order_process table
    $order_process_sql = "CREATE TABLE $order_process_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        group_id BIGINT(20) DEFAULT 0,
        order_id BIGINT(20) UNSIGNED DEFAULT 0,
        order_type TEXT NULL,
        name TEXT NULL,
        data LONGTEXT NULL,
        step VARCHAR(255) NULL,
        csv_name VARCHAR(255) NULL,
        greeting TEXT NULL,
        user_agent TEXT NULL,
        user_ip TEXT NULL,
        created DATETIME DEFAULT CURRENT_TIMESTAMP,
        modified DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()} AUTO_INCREMENT=10000;";
    

     // SQL to create the affiliate_customer_relation table
     $affiliate_customer_relation_sql = "CREATE TABLE $affiliate_customer_relation (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,        
        user_id BIGINT(20) NOT NULL,
        affiliate_id BIGINT(20) NOT NULL,
        blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB {$wpdb->get_charset_collate()};";
    // Execute the SQL for affiliate_customer_relation table

    // SQL query to create the table
    $affiliate_sql = "CREATE TABLE IF NOT EXISTS $affiliate_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    

    $sql_queries =[
        $recipient_sql,
        $order_process_recipient_sql,
        $files_upload_activity_log_sql,
        $order_process_recipient_activate_log_sql,
        $recipient_group_sql,
        $order_process_sql,
        $affiliate_customer_relation_sql,
        
        $affiliate_sql,
    ];

    foreach ($sql_queries as $sql) {
        dbDelta($sql);
    }
}