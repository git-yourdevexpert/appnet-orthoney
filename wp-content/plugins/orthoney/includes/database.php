<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// CREATE TABLE `wp_oh_wc_jar_order` (
//   `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//   `order_id` BIGINT(20) UNSIGNED NOT NULL,
//   `recipient_order_id` VARCHAR(255) NULL,
//   `jar_order_id` VARCHAR(255) NULL,
//   `tracking_no` VARCHAR(255) NULL,
//   `quantity` BIGINT(20) UNSIGNED NOT NULL,
//   `order_type` VARCHAR(255) NULL,
//   `status` VARCHAR(255) NULL,
//   `created_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
//   PRIMARY KEY (`id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



// CREATE TABLE `wp_oh_jar_order_greeting` (
//   `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
//   `order_id` BIGINT(20) UNSIGNED NOT NULL,
//   `recipient_order_id` VARCHAR(255) NULL,
//   `jar_order_id` VARCHAR(255) NULL,
//   `greeting` VARCHAR(255) NULL,
//   `created_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
//   PRIMARY KEY (`id`)
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
        'wc_jar_order_table' => $wpdb->prefix . 'oh_wc_jar_order',


        'wc_order_relation_table' => $wpdb->prefix . 'oh_wc_order_relation',
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
    if (isset($_GET['database_refresh']) && $_GET['database_refresh'] == 'new') {
        $table_names = [
            $wpdb->prefix . 'oh_order_process_recipient_activate_log',
            $wpdb->prefix . 'oh_files_upload_activity_log',
            $wpdb->prefix . 'oh_order_process',
            $wpdb->prefix . 'oh_order_process_recipient'
        ];
        
        // Loop through each table
        foreach ($table_names as $table_name) {
            // Check if the 'process_by' column exists in the table
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'process_by'");
        
            if (empty($column_exists)) {
                // If the column does not exist, add it after 'user_id'
                $wpdb->query(
                    "ALTER TABLE {$table_name} 
                    ADD COLUMN process_by BIGINT(20) UNSIGNED NOT NULL 
                    AFTER user_id"
                );
            }
        }

       $recipient_activate_log_table =  $wpdb->prefix . 'oh_order_process_recipient_activate_log';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$recipient_activate_log_table} LIKE 'method'");
        
            if (empty($column_exists)) {
                // If the column does not exist, add it after 'user_id'
                $wpdb->query(
                    "ALTER TABLE {$recipient_activate_log_table} 
                    ADD COLUMN method VARCHAR(255) NOT NULL
                    AFTER type"
                );
            }
            
    }

    // Load the upgrade script
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // SQL queries for table creation
    $sql_queries = [

        'wc_jar_order_table' => "CREATE TABLE {$tables['wc_jar_order_table']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            recipient_order_id BIGINT(20) UNSIGNED NOT NULL,
            jar_order_id BIGINT(20) UNSIGNED NOT NULL,
            tracking_no BIGINT(20) UNSIGNED NOT NULL,
            quantity BIGINT(20) UNSIGNED NOT NULL,
            greeting VARCHAR(255) NULL,
            order_type VARCHAR(255) NULL,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",


        'wc_order_relation_table' => "CREATE TABLE {$tables['wc_order_relation_table']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            wc_order_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            quantity BIGINT(20) UNSIGNED NOT NULL,
            order_type VARCHAR(255) NULL,
            affiliate_code VARCHAR(255) NULL,
            affiliate_user_id BIGINT(20) DEFAULT 0,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

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
            process_by BIGINT(20) DEFAULT 0,
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
            method VARCHAR(255) NOT NULL,
            update_log TEXT NULL,
            user_agent TEXT NULL,
            user_ip TEXT NULL,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$wpdb->get_charset_collate()};",

        'order_process_table' => "CREATE TABLE {$tables['order_process_table']} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NOT NULL,
            process_by BIGINT(20) DEFAULT 0,
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
