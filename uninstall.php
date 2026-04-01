<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Drop database tables
$tables = array(
    $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'church_programs',
    $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'church_services',
    $wpdb->prefix . CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX . 'church_branches',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete plugin options
delete_option('church_branches_generator_db_version');
delete_option('church_branches_generator_option');
delete_option('CHURCH_BRANCHES_GENERATOR_TABLE_PREFIX');

// Clean up transients
delete_transient('church_branches_generator_cache');

// Remove capabilities if added
$admin = get_role('administrator');
if ($admin) {
    $admin->remove_cap('manage_church_branches');
}
