<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Function to create custom tables
function orthoney_create_custom_tables() {
    global $wpdb;

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

    // Define table names
    $tables = [
        'recipient_table' => $wpdb->prefix . 'oh_group_recipient',
        'group_table' => $wpdb->prefix . 'oh_group',
        'order_process_recipient_table' => $wpdb->prefix . 'oh_order_process_recipient',
        'files_upload_activity_log_table' => $wpdb->prefix . 'oh_files_upload_activity_log',
        'order_process_table' => $wpdb->prefix . 'oh_order_process',
        'order_process_recipient_activate_log_table' => $wpdb->prefix . 'oh_order_process_recipient_activate_log',
        'affiliate_customer_relation' => $wpdb->prefix . 'oh_affiliate_customer_relation',
        'affiliate_customer_linker' => $wpdb->prefix . 'oh_affiliate_customer_linker'
    ];

    // Drop tables if the refresh flag is set
    if (isset($_GET['database_refresh']) && $_GET['database_refresh'] == 'okay') {
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    // Load the upgrade script
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // SQL queries for table creation
    $sql_queries = [
        'recipient_table' => "CREATE TABLE {$tables['recipient_table']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_id BIGINT(20) UNSIGNED NOT NULL,
            group_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            address_1 TEXT NULL,    
            address_2 TEXT NULL,
            city VARCHAR(255) NOT NULL,
            state VARCHAR(255) NOT NULL,
            zipcode VARCHAR(50) NOT NULL,
            quantity VARCHAR(50) NOT NULL,
            verified TINYINT(1) DEFAULT 0,
            address_verified TINYINT(1) DEFAULT 0,
            visibility TINYINT(1) DEFAULT 1,
            new TINYINT(1) DEFAULT 1,
            update_type VARCHAR(255) NULL,
            reasons TEXT NULL,
            greeting VARCHAR(255) NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

        'group_table' => "CREATE TABLE {$tables['group_table']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            pid BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            visibility TINYINT(1) DEFAULT 1,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

        'affiliate_customer_linker' => "CREATE TABLE {$tables['affiliate_customer_linker']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT(20) UNSIGNED NOT NULL,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            status VARCHAR(20) NOT NULL,
            token VARCHAR(64) NOT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

        'order_process_recipient_table' => "CREATE TABLE {$tables['order_process_recipient_table']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            pid BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
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
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

        'files_upload_activity_log_table' => "CREATE TABLE {$tables['files_upload_activity_log_table']} (
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
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

        'order_process_recipient_activate_log_table' => "CREATE TABLE {$tables['order_process_recipient_activate_log_table']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_id BIGINT(20) UNSIGNED NOT NULL,
            type VARCHAR(255) NOT NULL,
            update_log TEXT NULL,
            user_agent TEXT NULL,
            user_ip TEXT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

        'order_process_table' => "CREATE TABLE {$tables['order_process_table']} (
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
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()} AUTO_INCREMENT=10000;",

       
    ];

    // Execute the SQL queries only if the table doesn't exist
    foreach ($sql_queries as $table_key => $sql) {
        $table_name = $tables[$table_key];
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");

        if ($table_exists !== $table_name) {
            dbDelta($sql);
        }
    }
}
