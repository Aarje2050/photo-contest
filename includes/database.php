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
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            contest_id bigint(20) NOT NULL,
            vote_value int(11) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY contest_id (contest_id),
            UNIQUE KEY unique_vote (user_id, post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Record a vote.
     */
    public static function record_vote($user_id, $post_id, $contest_id, $vote_value = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voxel_photo_contest_votes';

        // Check if user has already voted for this post
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND post_id = %d",
            $user_id,
            $post_id
        ));

        if ($existing_vote) {
            // User already voted, update their vote
            $result = $wpdb->update(
                $table_name,
                [
                    'vote_value' => $vote_value,
                    'created_at' => current_time('mysql')
                ],
                [
                    'user_id' => $user_id,
                    'post_id' => $post_id
                ],
                ['%d', '%s'],
                ['%d', '%d']
            );
            
            return $result !== false;
        } else {
            // New vote
            $result = $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                    'contest_id' => $contest_id,
                    'vote_value' => $vote_value,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%d', '%s']
            );
            
            return $result !== false;
        }
    }

    /**
     * Get votes for a post.
     */
    public static function get_votes_for_post($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voxel_photo_contest_votes';

        $votes = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d",
            $post_id
        ));

        return $votes;
    }

    /**
     * Get vote count for a post.
     */
    public static function get_vote_count($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voxel_photo_contest_votes';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
            $post_id
        ));

        return intval($count);
    }

    /**
     * Check if user has voted for a post.
     */
    public static function has_user_voted($user_id, $post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voxel_photo_contest_votes';

        $vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND post_id = %d",
            $user_id,
            $post_id
        ));

        return !empty($vote);
    }

    /**
     * Get top voted posts for a contest.
     */
    public static function get_top_voted_posts($contest_id, $limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'voxel_photo_contest_votes';

        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, COUNT(*) as vote_count 
            FROM $table_name 
            WHERE contest_id = %d 
            GROUP BY post_id 
            ORDER BY vote_count DESC 
            LIMIT %d",
            $contest_id,
            $limit
        ));

        return $posts;
    }
}