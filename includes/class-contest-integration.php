<?php
namespace VPC;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Contest Integration
 * 
 * Handles integration with Voxel's contest post type
 */
class Contest_Integration {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add custom fields to the contest post type
        add_action('voxel/post-types/field-types', [$this, 'register_field_types']);
        
        // Add contest admin columns
        add_filter('manage_contest_posts_columns', [$this, 'add_contest_columns']);
        add_action('manage_contest_posts_custom_column', [$this, 'populate_contest_columns'], 10, 2);
        
        // Add contest dashboard links
        add_filter('voxel/post/admin-actions', [$this, 'add_contest_actions'], 10, 2);
        
        // Add contest custom tabs
        add_filter('voxel/post/tabs', [$this, 'add_contest_tabs'], 10, 2);
    }
    
    /**
     * Register custom field types for Voxel post types
     */
    public function register_field_types($field_types) {
        // This function would register any custom field types if needed
        // For now, we're using standard Voxel fields, so this is just a placeholder
        return $field_types;
    }
    
    /**
     * Add custom columns to contest post type admin
     */
    public function add_contest_columns($columns) {
        $new_columns = [];
        
        // Insert our custom columns after title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['contest_status'] = __('Status', 'voxel-photo-contests');
                $new_columns['submission_count'] = __('Submissions', 'voxel-photo-contests');
                $new_columns['vote_count'] = __('Votes', 'voxel-photo-contests');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom contest columns
     */
    public function populate_contest_columns($column, $post_id) {
        switch ($column) {
            case 'contest_status':
                echo $this->get_contest_status_html($post_id);
                break;
                
            case 'submission_count':
                echo $this->get_submission_count($post_id);
                break;
                
            case 'vote_count':
                echo Voting_System::instance()->get_contest_vote_count($post_id);
                break;
        }
    }
    
    /**
     * Get HTML for contest status
     */
    private function get_contest_status_html($contest_id) {
        $dates = Voting_System::instance()->get_contest_dates($contest_id);
        $now = time();
        
        if ($dates['start'] && $now < $dates['start']) {
            return '<span class="vpc-status vpc-status-upcoming">' . __('Upcoming', 'voxel-photo-contests') . '</span>';
        } else if ($dates['end'] && $now > $dates['end']) {
            return '<span class="vpc-status vpc-status-ended">' . __('Ended', 'voxel-photo-contests') . '</span>';
        } else {
            return '<span class="vpc-status vpc-status-active">' . __('Active', 'voxel-photo-contests') . '</span>';
        }
    }
    
    /**
     * Get submission count for a contest
     */
    private function get_submission_count($contest_id) {
        global $wpdb;
        
        // If using Voxel's post relations
        if (class_exists('\Voxel\Post')) {
            // Simplified - would need to match Voxel's actual implementation
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}voxel_relations 
                WHERE related_id = %d AND object_type = 'post'",
                $contest_id
            ));
        } else {
            // Using regular post meta
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = 'vpc_contest_id' AND meta_value = %d",
                $contest_id
            ));
        }
    }
    
    /**
     * Add contest actions to post edit screen
     */
    public function add_contest_actions($actions, $post) {
        // Only add actions to contest post type
        if ($post->post_type->get_key() !== 'contest') {
            return $actions;
        }
        
        // Add link to view submissions
        $actions['view_submissions'] = [
            'label' => __('View Submissions', 'voxel-photo-contests'),
            'icon' => 'la-image',
            'link' => add_query_arg([
                'post_type' => 'submission',
                'vpc_contest_id' => $post->get_id(),
            ], admin_url('edit.php')),
        ];
        
        // Add link to contest results
        $actions['contest_results'] = [
            'label' => __('Contest Results', 'voxel-photo-contests'),
            'icon' => 'la-trophy',
            'link' => admin_url(sprintf(
                'admin.php?page=vpc-contests&action=results&contest_id=%d',
                $post->get_id()
            )),
        ];
        
        return $actions;
    }
    
    /**
     * Add contest tabs to single contest view
     */
    public function add_contest_tabs($tabs, $post) {
        // Only add tabs to contest post type
        if ($post->post_type->get_key() !== 'contest') {
            return $tabs;
        }
        
        // Add submissions tab
        $tabs['submissions'] = [
            'key' => 'submissions',
            'label' => __('Submissions', 'voxel-photo-contests'),
            'icon' => 'la-image',
            'template' => 'templates/contest-submissions.php',
        ];
        
        // Add results tab
        $tabs['results'] = [
            'key' => 'results',
            'label' => __('Results', 'voxel-photo-contests'),
            'icon' => 'la-trophy',
            'template' => 'templates/contest-results.php',
        ];
        
        return $tabs;
    }
    
    /**
     * Get contest data for use in templates
     */
    public function get_contest_data($contest_id) {
        $contest = get_post($contest_id);
        if (!$contest || $contest->post_type !== 'contest') {
            return null;
        }
        
        $voting_system = Voting_System::instance();
        $dates = $voting_system->get_contest_dates($contest_id);
        
        // Calculate contest status
        $now = time();
        $status = 'active';
        
        if ($dates['start'] && $now < $dates['start']) {
            $status = 'upcoming';
        } else if ($dates['end'] && $now > $dates['end']) {
            $status = 'ended';
        }
        
        return [
            'id' => $contest_id,
            'title' => get_the_title($contest_id),
            'content' => apply_filters('the_content', $contest->post_content),
            'permalink' => get_permalink($contest_id),
            'status' => $status,
            'start_date' => $dates['start'] ? date_i18n(get_option('date_format'), $dates['start']) : null,
            'end_date' => $dates['end'] ? date_i18n(get_option('date_format'), $dates['end']) : null,
            'submissions_count' => $this->get_submission_count($contest_id),
            'votes_count' => $voting_system->get_contest_vote_count($contest_id),
            'unique_voters_count' => $voting_system->get_unique_voters_count($contest_id),
            'max_votes_per_user' => $voting_system->get_max_votes_per_user($contest_id),
            'voting_enabled' => $voting_system->is_voting_enabled($contest_id),
        ];
    }
    
    /**
     * Get submissions for a contest
     */
    public function get_contest_submissions($contest_id, $args = []) {
        $default_args = [
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ];
        
        $args = wp_parse_args($args, $default_args);
        $args['post_type'] = 'submission';
        
        // If using Voxel's post relations
        if (class_exists('\Voxel\Post')) {
            // This is a simplified approach - actual implementation would depend on Voxel's database structure
            add_filter('posts_where', function($where) use ($contest_id) {
                global $wpdb;
                // Adjust the query based on how Voxel stores relations
                $where .= $wpdb->prepare(" AND ID IN (SELECT post_id FROM {$wpdb->prefix}voxel_relations WHERE related_id = %d)", $contest_id);
                return $where;
            });
        } else {
            // Using regular post meta
            $args['meta_query'] = [
                [
                    'key' => 'vpc_contest_id',
                    'value' => $contest_id,
                    'compare' => '=',
                ]
            ];
        }
        
        $query = new \WP_Query($args);
        
        // Remove our filter if we added it
        if (class_exists('\Voxel\Post')) {
            remove_all_filters('posts_where');
        }
        
        return $query->posts;
    }
}