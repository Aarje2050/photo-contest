<?php
namespace Voxel_Photo_Contests;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Database {
    /**
     * Create necessary database tables.
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for storing votes
        $table_name = $wpdb->prefix . 'voxel_photo_contest_votes';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id varchar(100) NOT NULL,
            post_id bigint(20) NOT NULL,
            contest_id bigint(20) NOT NULL,
            vote_value int(11) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id(50)),
            KEY post_id (post_id),
            KEY contest_id (contest_id),
            UNIQUE KEY unique_vote (user_id(50), post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}