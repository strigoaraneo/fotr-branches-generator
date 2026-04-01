<?php
class Church_Branches_Generator_Activator {
    public static function activate() {
        self::create_tables();
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        // Include dbDelta for table creation/updates
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Table: wp_church_branches
        $branches_table = $prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'branches';
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
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_branch_name (branch_name),
            KEY idx_page_id (page_id)
        ) $charset_collate;";

        // Table: wp_church_services
        $services_table = $prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'church_services';
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

        // Table: wp_church_programs
        $programs_table = $prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'church_programs';
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

        // Execute table creation
        dbDelta($sql_branches);
        dbDelta($sql_services);
        dbDelta($sql_programs);

        // Store version for future migrations
        update_option('church_branches_generator_db_version', '1.0.0');
    }
}
