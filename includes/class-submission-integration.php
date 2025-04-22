<?php
namespace VPC;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Submission Integration
 * 
 * Handles integration with Voxel's submission post type
 */
class Submission_Integration {
    
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
        // Add custom fields to the submission post type
        add_action('voxel/post-types/after-field-creation', [$this, 'add_submission_fields'], 10, 2);
        
        // Add submission admin columns
        add_filter('manage_submission_posts_columns', [$this, 'add_submission_columns']);
        add_action('manage_submission_posts_custom_column', [$this, 'populate_submission_columns'], 10, 2);
        
        // Filter submission form values
        add_filter('voxel/post/prepare-created-post', [$this, 'prepare_submission'], 10, 2);
        
        // Handle submission moderation hooks
        add_action('voxel/post/publish', [$this, 'handle_submission_publish'], 10, 2);
        
        // Add vote count to submission in search results
        add_filter('voxel/post/prepare-card-data', [$this, 'add_vote_count_to_card'], 10, 2);
        
        // Add admin filter for contest submissions
        add_action('restrict_manage_posts', [$this, 'add_contest_filter']);
        add_filter('parse_query', [$this, 'filter_submissions_by_contest']);
    }
    
    /**
     * Add custom fields to the submission post type
     */
    public function add_submission_fields($post_type, $field_manager) {
        // Only add fields to submission post type
        if ($post_type->get_key() !== 'submission') {
            return;
        }
        
        // Check if field already exists
        $existing_field = $post_type->get_field('contest');
        if ($existing_field) {
            return;
        }
        
        // Try to add a post relation field to link to contest
        try {
            // Get class names from Voxel's post-types.config.php
            $field_details = [
                'label' => __('Contest', 'voxel-photo-contests'),
                'description' => __('Select the contest this submission is for', 'voxel-photo-contests'),
                'key' => 'contest',
                'type' => 'post-relation',
                'post_types' => ['contest'],
                'relation_type' => 'has_one',
                'required' => true,
            ];
            
            // Add field to post type
            $field_manager->create_field($field_details);
            
        } catch (\Exception $e) {
            // Log error
            error_log('Error adding contest field to submission post type: ' . $e->getMessage());
        }
    }
    
    /**
     * Add custom columns to submission post type admin
     */
    public function add_submission_columns($columns) {
        $new_columns = [];
        
        // Insert our custom columns after title
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['contest'] = __('Contest', 'voxel-photo-contests');
                $new_columns['votes'] = __('Votes', 'voxel-photo-contests');
                $new_columns['submission_date'] = __('Submitted', 'voxel-photo-contests');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate custom submission columns
     */
    public function populate_submission_columns($column, $post_id) {
        switch ($column) {
            case 'contest':
                echo $this->get_contest_link($post_id);
                break;
                
            case 'votes':
                $vote_count = get_post_meta($post_id, 'vpc_vote_count', true) ?: 0;
                echo $vote_count;
                break;
                
            case 'submission_date':
                $post = get_post($post_id);
                echo date_i18n(get_option('date_format'), strtotime($post->post_date));
                break;
        }
    }
    
    /**
     * Get contest link for a submission
     */
    private function get_contest_link($post_id) {
        $contest_id = null;
        
        // Check if we're using Voxel's post relation field
        if (class_exists('\Voxel\Post')) {
            $submission = \Voxel\Post::get($post_id);
            if ($submission) {
                $field = $submission->get_field('contest');
                if ($field && $field->get_type() === 'post-relation') {
                    $contest_posts = $field->get_value();
                    if (!empty($contest_posts)) {
                        $contest_id = (int) $contest_posts[0];
                    }
                }
            }
        }
        
        // Fallback to regular post meta
        if (!$contest_id) {
            $contest_id = get_post_meta($post_id, 'vpc_contest_id', true);
        }
        
        if (!$contest_id) {
            return '<em>' . __('None', 'voxel-photo-contests') . '</em>';
        }
        
        $title = get_the_title($contest_id);
        $edit_link = get_edit_post_link($contest_id);
        
        return sprintf(
            '<a href="%s">%s</a>',
            esc_url($edit_link),
            esc_html($title)
        );
    }
    
    /**
     * Prepare submission post data
     */
    public function prepare_submission($post_data, $request) {
        // Only process submission post type
        if ($request['post_type'] !== 'submission') {
            return $post_data;
        }
        
        // Make sure contest ID is set
        if (!empty($request['fields']['contest'])) {
            $contest_id = (int) $request['fields']['contest'];
            
            // Store contest ID in post meta for easier querying
            $post_data['meta']['vpc_contest_id'] = $contest_id;
            
            // Initialize vote count
            $post_data['meta']['vpc_vote_count'] = 0;
        }
        
        return $post_data;
    }
    
    /**
     * Handle submission publish event
     */
    public function handle_submission_publish($post, $old_status) {
        // Only process submission post type
        if ($post->post_type->get_key() !== 'submission') {
            return;
        }
        
        // Only process if status changed to publish
        if ($old_status === 'publish' || $post->get_status() !== 'publish') {
            return;
        }
        
        // Get contest ID
        $contest_id = null;
        $field = $post->get_field('contest');
        if ($field && $field->get_type() === 'post-relation') {
            $contest_posts = $field->get_value();
            if (!empty($contest_posts)) {
                $contest_id = (int) $contest_posts[0];
            }
        }
        
        // Fallback to regular post meta
        if (!$contest_id) {
            $contest_id = get_post_meta($post->get_id(), 'vpc_contest_id', true);
        }
        
        if (!$contest_id) {
            return;
        }
        
        // Trigger actions for new submission
        do_action('vpc/submission/published', $post->get_id(), $contest_id);
        
        // Example: Send notification email to admin
        $this->maybe_send_new_submission_notification($post->get_id(), $contest_id);
    }
    
    /**
     * Send notification email for new submissions
     */
    private function maybe_send_new_submission_notification($submission_id, $contest_id) {
        // Check if notifications are enabled
        $notifications_enabled = get_option('vpc_email_notifications', 'yes');
        if ($notifications_enabled !== 'yes') {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $submission_title = get_the_title($submission_id);
        $contest_title = get_the_title($contest_id);
        $submission_edit_link = get_edit_post_link($submission_id, 'raw');
        
        $subject = sprintf(
            __('[%s] New submission: %s', 'voxel-photo-contests'),
            get_bloginfo('name'),
            $submission_title
        );
        
        $message = sprintf(
            __("A new submission has been received for contest: %s\n\nSubmission: %s\n\nEdit submission: %s", 'voxel-photo-contests'),
            $contest_title,
            $submission_title,
            $submission_edit_link
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Add vote count to submission card in search results
     */
    public function add_vote_count_to_card($data, $post) {
        // Only process submission post type
        if ($post->post_type->get_key() !== 'submission') {
            return $data;
        }
        
        // Add vote count
        $vote_count = get_post_meta($post->get_id(), 'vpc_vote_count', true) ?: 0;
        $data['vote_count'] = $vote_count;
        
        return $data;
    }
    
    /**
     * Add a dropdown filter for contests in admin
     */
    public function add_contest_filter() {
        global $typenow;
        
        // Only add to submission post type
        if ($typenow !== 'submission') {
            return;
        }
        
        // Get all contests
        $contests = get_posts([
            'post_type' => 'contest',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        
        // Get current selected contest
        $current_contest = isset($_GET['vpc_contest_id']) ? (int) $_GET['vpc_contest_id'] : 0;
        
        // Start dropdown
        echo '<select name="vpc_contest_id">';
        echo '<option value="">' . __('All Contests', 'voxel-photo-contests') . '</option>';
        
        foreach ($contests as $contest) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($contest->ID),
                selected($current_contest, $contest->ID, false),
                esc_html($contest->post_title)
            );
        }
        
        echo '</select>';
    }
    
    /**
     * Filter submissions by contest in admin
     */
    public function filter_submissions_by_contest($query) {
        global $pagenow, $typenow;
        
        // Only on the submission list page
        if ($pagenow !== 'edit.php' || $typenow !== 'submission' || !is_admin()) {
            return $query;
        }
        
        // Check if our filter is set
        if (isset($_GET['vpc_contest_id']) && !empty($_GET['vpc_contest_id'])) {
            $contest_id = (int) $_GET['vpc_contest_id'];
            
            // If using Voxel's relations
            if (class_exists('\Voxel\Post')) {
                // This would need to be adjusted based on Voxel's actual implementation
                add_filter('posts_where', function($where) use ($contest_id) {
                    global $wpdb;
                    $where .= $wpdb->prepare(
                        " AND ID IN (SELECT post_id FROM {$wpdb->prefix}voxel_relations WHERE related_id = %d)",
                        $contest_id
                    );
                    return $where;
                });
            } else {
                // Using regular post meta
                $query->query_vars['meta_key'] = 'vpc_contest_id';
                $query->query_vars['meta_value'] = $contest_id;
            }
        }
        
        return $query;
    }
}