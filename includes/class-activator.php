<?php
class Church_Branches_Generator_Activator {
    public static function activate() {
        self::create_tables();
        self::migrate_existing_tables();
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $branches_table = $prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'branches';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$branches_table'") != $branches_table) {
            $sql_branches = "CREATE TABLE $branches_table (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                branch_name VARCHAR(255) NOT NULL,
                address VARCHAR(500) NOT NULL DEFAULT '',
                phone VARCHAR(50) NOT NULL DEFAULT '',
                email VARCHAR(255) NOT NULL DEFAULT '',
                service_times TEXT NOT NULL,
                lead_pastor VARCHAR(255) NOT NULL DEFAULT '',
                page_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                about_us_text TEXT NOT NULL,
                directions_info TEXT NOT NULL,
                language VARCHAR(20) NOT NULL DEFAULT 'english',
                branch_description TEXT NOT NULL DEFAULT '',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_branch_name (branch_name),
                KEY idx_page_id (page_id),
                KEY idx_language (language)
            ) $charset_collate;";
            dbDelta($sql_branches);
        }

        $services_table = $prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'services';
        if ($wpdb->get_var("SHOW TABLES LIKE '$services_table'") != $services_table) {
            $sql_services = "CREATE TABLE $services_table (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                branch_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                service_name VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                day_of_week VARCHAR(20) NOT NULL DEFAULT '',
                time VARCHAR(50) NOT NULL DEFAULT '',
                service_order INT(11) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_branch_id (branch_id),
                KEY idx_service_order (service_order)
            ) $charset_collate;";
            dbDelta($sql_services);
        }

        $programs_table = $prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'programs';
        if ($wpdb->get_var("SHOW TABLES LIKE '$programs_table'") != $programs_table) {
            $sql_programs = "CREATE TABLE $programs_table (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                branch_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                program_name VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                program_type VARCHAR(50) NOT NULL DEFAULT 'weekly',
                day_of_week VARCHAR(20) NOT NULL DEFAULT '',
                time VARCHAR(50) NOT NULL DEFAULT '',
                location VARCHAR(255) NOT NULL DEFAULT '',
                program_order INT(11) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_branch_id (branch_id),
                KEY idx_program_type (program_type),
                KEY idx_program_order (program_order)
            ) $charset_collate;";
            dbDelta($sql_programs);
        }
    }

    private static function migrate_existing_tables() {
        global $wpdb;
        $branches_table = $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'branches';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$branches_table'") != $branches_table) {
            return;
        }
        
        $current_version = get_option('church_branches_generator_db_version', '1.0.0');
        
        if (version_compare($current_version, '1.1.0', '<')) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'language'",
                DB_NAME, $branches_table
            ));
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $branches_table ADD COLUMN language VARCHAR(20) NOT NULL DEFAULT 'english' AFTER directions_info");
                $wpdb->query("ALTER TABLE $branches_table ADD INDEX idx_language (language)");
            }
            
            update_option('church_branches_generator_db_version', '1.1.0');
        }
        
        if (version_compare($current_version, '1.2.1', '<')) {
            $column_exists = $wpdb->get_results($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'branch_description'",
                DB_NAME, $branches_table
            ));
            
            if (empty($column_exists)) {
                $wpdb->query("ALTER TABLE $branches_table ADD COLUMN branch_description TEXT NOT NULL DEFAULT '' AFTER language");
            }
            
            update_option('church_branches_generator_db_version', '1.2.1');
        }
    }
}
