<?php
class Church_Branches_Generator_Deactivator {
    public static function deactivate() {
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();

        // Clear scheduled cron jobs if any
        $timestamp = wp_next_scheduled('church_branches_generator_cron_hook');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'church_branches_generator_cron_hook');
        }
    }
}
