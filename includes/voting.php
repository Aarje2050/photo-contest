<?php
namespace Voxel_Photo_Contests;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Voting {
    /**
     * Constructor.
     */
    public function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_voxel_photo_contest_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_voxel_photo_contest_vote', [$this, 'handle_vote']);
        
        // Add vote count to post meta for easier querying
        add_action('voxel_photo_contest_vote_recorded', [$this, 'update_vote_count_meta'], 10, 3);
    }

    /**
     * Handle vote submission via AJAX.
     */
    public function handle_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'voxel_photo_contests_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'voxel-photo-contests')
            ]);
            exit;
        }

        // Get parameters
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $contest_id = isset($_POST['contest_id']) ? intval($_POST['contest_id']) : 0;
        $vote_value = isset($_POST['vote_value']) ? intval($_POST['vote_value']) : 1;

        // Validate post ID
        if (!$post_id || get_post_type($post_id) !== 'photo_submission') {
            

                wp_send_json_error([
                    'message' => __('Invalid submission', 'voxel-photo-contests')
                ]);
                exit;
            }
    
            // Validate contest ID
            if (!$contest_id || get_post_type($contest_id) !== 'photo_contest') {
                wp_send_json_error([
                    'message' => __('Invalid contest', 'voxel-photo-contests')
                ]);
                exit;
            }
    
            // Check if contest is active
            $contest_start = get_post_meta($contest_id, '_contest_start_date', true);
            $contest_end = get_post_meta($contest_id, '_contest_end_date', true);
            $current_time = current_time('mysql');
    
            if (($contest_start && $current_time < $contest_start) || 
                ($contest_end && $current_time > $contest_end)) {
                wp_send_json_error([
                    'message' => __('Voting is not currently active for this contest', 'voxel-photo-contests')
                ]);
                exit;
            }
    
            // Check if voting is enabled
            $voting_enabled = get_post_meta($contest_id, '_voting_enabled', true);
            if ($voting_enabled !== 'yes') {
                wp_send_json_error([
                    'message' => __('Voting is not enabled for this contest', 'voxel-photo-contests')
                ]);
                exit;
            }
    
            // Check who can vote
            $who_can_vote = get_post_meta($contest_id, '_who_can_vote', true) ?: 'logged_in';
            
            if ($who_can_vote === 'logged_in' && !is_user_logged_in()) {
                wp_send_json_error([
                    'message' => __('You must be logged in to vote', 'voxel-photo-contests'),
                    'require_login' => true
                ]);
                exit;
            }
    
            // Get user ID (use IP for guests)
            $user_id = is_user_logged_in() ? get_current_user_id() : 0;
            
            // For guest voting, use IP as identifier
            if ($user_id === 0) {
                $user_ip = $this->get_user_ip();
                // Hash the IP for privacy
                $user_id = 'ip_' . md5($user_ip);
            }
    
            // Check if user has already voted
            if (Database::has_user_voted($user_id, $post_id)) {
                wp_send_json_error([
                    'message' => __('You have already voted for this entry', 'voxel-photo-contests')
                ]);
                exit;
            }
    
            // Record the vote
            $result = Database::record_vote($user_id, $post_id, $contest_id, $vote_value);
            
            if ($result) {
                // Update vote count in post meta
                do_action('voxel_photo_contest_vote_recorded', $post_id, $contest_id, $vote_value);
                
                // Get updated vote count
                $vote_count = Database::get_vote_count($post_id);
                
                wp_send_json_success([
                    'message' => __('Your vote has been recorded', 'voxel-photo-contests'),
                    'vote_count' => $vote_count
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('Error recording your vote', 'voxel-photo-contests')
                ]);
            }
            
            exit;
        }
    
        /**
         * Update vote count meta when a vote is recorded.
         */
        public function update_vote_count_meta($post_id, $contest_id, $vote_value) {
            // Get current vote count
            $vote_count = Database::get_vote_count($post_id);
            
            // Update post meta
            update_post_meta($post_id, '_voxel_photo_contest_vote_count', $vote_count);
            
            // Also store the vote value sum for rating type contests
            $current_sum = get_post_meta($post_id, '_voxel_photo_contest_vote_sum', true) ?: 0;
            $new_sum = $current_sum + $vote_value;
            update_post_meta($post_id, '_voxel_photo_contest_vote_sum', $new_sum);
            
            // Calculate and store average rating
            if ($vote_count > 0) {
                $average = $new_sum / $vote_count;
                update_post_meta($post_id, '_voxel_photo_contest_vote_average', $average);
            }
        }
    
        /**
         * Get user IP address.
         */
        private function get_user_ip() {
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return $ip;
        }
    }
    
    // Initialize voting
    new Voting();