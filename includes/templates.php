<?php
namespace Voxel_Photo_Contests;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Templates {
    /**
     * Constructor.
     */
    public function __construct() {
        // Add template filters
        add_filter('single_template', [$this, 'register_single_templates']);
        add_filter('archive_template', [$this, 'register_archive_templates']);
        
        // Add template functions
        add_action('voxel_photo_contests_before_content', [$this, 'add_contest_header']);
        add_action('voxel_photo_contests_after_content', [$this, 'add_contest_footer']);
    }

    /**
     * Register single post templates.
     */
    public function register_single_templates($template) {
        global $post;
        
        if (!$post) {
            return $template;
        }

        // For photo contest single page
        if ($post->post_type === 'photo_contest') {
            $custom_template = VOXEL_PHOTOS_PLUGIN_DIR . 'templates/single-photo-contest.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        // For photo submission single page
        if ($post->post_type === 'photo_submission') {
            $custom_template = VOXEL_PHOTOS_PLUGIN_DIR . 'templates/single-photo-submission.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    /**
     * Register archive templates.
     */
    public function register_archive_templates($template) {
        if (is_post_type_archive('photo_contest')) {
            $custom_template = VOXEL_PHOTOS_PLUGIN_DIR . 'templates/archive-photo-contest.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        if (is_post_type_archive('photo_submission')) {
            $custom_template = VOXEL_PHOTOS_PLUGIN_DIR . 'templates/archive-photo-submission.php';
            
            if (file_exists($custom_template)) {
                return $custom_template;
            }
        }
        
        return $template;
    }

    /**
     * Add contest header to single contest page.
     */
    public function add_contest_header() {
        if (is_singular('photo_contest')) {
            global $post;
            
            // Get contest data
            $start_date = get_post_meta($post->ID, '_contest_start_date', true);
            $end_date = get_post_meta($post->ID, '_contest_end_date', true);
            $voting_enabled = get_post_meta($post->ID, '_voting_enabled', true);
            $show_results = get_post_meta($post->ID, '_show_results', true);
            
            // Format dates
            $start_date_formatted = $start_date ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($start_date)) : __('Immediately', 'voxel-photo-contests');
            $end_date_formatted = $end_date ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($end_date)) : __('No end date', 'voxel-photo-contests');
            
            // Check if contest is active
            $current_time = current_time('mysql');
            $contest_status = 'upcoming';
            
            if ((!$start_date || $current_time >= $start_date) && (!$end_date || $current_time <= $end_date)) {
                $contest_status = 'active';
            } elseif ($end_date && $current_time > $end_date) {
                $contest_status = 'ended';
            }
            
            // Display header
            include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/contest-header.php';
        }
    }

    /**
     * Add contest footer to single contest page.
     */
    public function add_contest_footer() {
        if (is_singular('photo_contest')) {
            global $post;
            
            // Get rules and prizes
            $rules = get_post_meta($post->ID, '_contest_rules', true);
            $prizes = get_post_meta($post->ID, '_contest_prizes', true);
            
            // Display footer
            include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/contest-footer.php';
        }
    }

    /**
     * Get contest submission form URL.
     */
    public static function get_submission_form_url($contest_id) {
        // Check if contest exists
        if (!get_post($contest_id)) {
            return '';
        }
        
        // Try to get Voxel form page
        $post_type = \Voxel\Post_Type::get('photo_submission');
        
        if ($post_type) {
            $form_template = $post_type->get_templates()['form'] ?? null;
            
            if ($form_template) {
                return add_query_arg([
                    'contest_id' => $contest_id,
                ], get_permalink($form_template));
            }
        }
        
        // Fallback to custom submission page
        $submission_page = get_option('voxel_photo_contests_submission_page');
        
        if ($submission_page) {
            return add_query_arg([
                'contest_id' => $contest_id,
            ], get_permalink($submission_page));
        }
        
        return '';
    }

    /**
     * Get vote button HTML.
     */
    public static function get_vote_button($post_id, $contest_id) {
        // Check if user has already voted
        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        
        // For guest voting, use IP as identifier
        if ($user_id === 0) {
            $user_ip = $_SERVER['REMOTE_ADDR'];
            // Hash the IP for privacy
            $user_id = 'ip_' . md5($user_ip);
        }
        
        $has_voted = Database::has_user_voted($user_id, $post_id);
        
        // Get vote count
        $vote_count = get_post_meta($post_id, '_voxel_photo_contest_vote_count', true) ?: 0;
        
        // Get voting type
        $voting_type = get_post_meta($contest_id, '_voting_type', true) ?: 'single';
        
        ob_start();
        include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/vote-button.php';
        return ob_get_clean();
    }
}

// Initialize templates
new Templates();