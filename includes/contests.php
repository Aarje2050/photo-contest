<?php
namespace Voxel_Photo_Contests;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Contests {
    /**
     * Constructor.
     */
    public function __construct() {
        // Add settings page
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add shortcodes
        add_shortcode('voxel_photo_contest', [$this, 'contest_shortcode']);
        add_shortcode('voxel_photo_submission_form', [$this, 'submission_form_shortcode']);
        add_shortcode('voxel_photo_contest_leaderboard', [$this, 'leaderboard_shortcode']);
        
        // Add filters and actions for contest management
        add_action('save_post_photo_submission', [$this, 'handle_submission_save'], 10, 3);
        add_filter('voxel/post-types/photo_submission/form/before_create', [$this, 'modify_submission_form'], 10, 2);
    }

    /**
     * Add settings page.
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=photo_contest',
            __('Photo Contest Settings', 'voxel-photo-contests'),
            __('Settings', 'voxel-photo-contests'),
            'manage_options',
            'photo-contest-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting('voxel_photo_contests_settings', 'voxel_photo_contests_submission_page');
        register_setting('voxel_photo_contests_settings', 'voxel_photo_contests_enable_guest_voting');
        register_setting('voxel_photo_contests_settings', 'voxel_photo_contests_default_voting_type');
        
        add_settings_section(
            'voxel_photo_contests_section',
            __('General Settings', 'voxel-photo-contests'),
            [$this, 'settings_section_callback'],
            'voxel_photo_contests_settings'
        );
        
        add_settings_field(
            'voxel_photo_contests_submission_page',
            __('Submission Page', 'voxel-photo-contests'),
            [$this, 'submission_page_callback'],
            'voxel_photo_contests_settings',
            'voxel_photo_contests_section'
        );
        
        add_settings_field(
            'voxel_photo_contests_enable_guest_voting',
            __('Enable Guest Voting', 'voxel-photo-contests'),
            [$this, 'enable_guest_voting_callback'],
            'voxel_photo_contests_settings',
            'voxel_photo_contests_section'
        );
        
        add_settings_field(
            'voxel_photo_contests_default_voting_type',
            __('Default Voting Type', 'voxel-photo-contests'),
            [$this, 'default_voting_type_callback'],
            'voxel_photo_contests_settings',
            'voxel_photo_contests_section'
        );
    }

    /**
     * Settings section callback.
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure the global settings for photo contests.', 'voxel-photo-contests') . '</p>';
    }

    /**
     * Submission page callback.
     */
    public function submission_page_callback() {
        $submission_page = get_option('voxel_photo_contests_submission_page');
        
        wp_dropdown_pages([
            'name' => 'voxel_photo_contests_submission_page',
            'selected' => $submission_page,
            'show_option_none' => __('Select a page', 'voxel-photo-contests'),
        ]);
        
        echo '<p class="description">' . __('Select a page where you have placed the [voxel_photo_submission_form] shortcode.', 'voxel-photo-contests') . '</p>';
    }

    /**
     * Enable guest voting callback.
     */
    public function enable_guest_voting_callback() {
        $enable_guest_voting = get_option('voxel_photo_contests_enable_guest_voting');
        
        echo '<input type="checkbox" name="voxel_photo_contests_enable_guest_voting" value="1" ' . checked(1, $enable_guest_voting, false) . '>';
        echo '<p class="description">' . __('Allow non-logged in users to vote in contests.', 'voxel-photo-contests') . '</p>';
    }

    /**
     * Default voting type callback.
     */
    public function default_voting_type_callback() {
        $default_voting_type = get_option('voxel_photo_contests_default_voting_type', 'single');
        
        ?>
        <select name="voxel_photo_contests_default_voting_type">
            <option value="single" <?php selected($default_voting_type, 'single'); ?>><?php _e('Single Vote (Like)', 'voxel-photo-contests'); ?></option>
            <option value="multiple" <?php selected($default_voting_type, 'multiple'); ?>><?php _e('Multiple Votes', 'voxel-photo-contests'); ?></option>
            <option value="rating" <?php selected($default_voting_type, 'rating'); ?>><?php _e('Star Rating', 'voxel-photo-contests'); ?></option>
        </select>
        <p class="description"><?php _e('Select the default voting type for new contests.', 'voxel-photo-contests'); ?></p>
        <?php
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Photo Contest Settings', 'voxel-photo-contests'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('voxel_photo_contests_settings');
                do_settings_sections('voxel_photo_contests_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Contest shortcode.
     */
    public function contest_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'display' => 'submissions', // submissions, rules, leaderboard
            'columns' => 3,
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ], $atts);
        
        if (!$atts['id']) {
            return '<div class="voxel-alert voxel-alert-error">' . __('Please specify a contest ID.', 'voxel-photo-contests') . '</div>';
        }
        
        $contest = get_post($atts['id']);
        
        if (!$contest || $contest->post_type !== 'photo_contest') {
            return '<div class="voxel-alert voxel-alert-error">' . __('Invalid contest ID.', 'voxel-photo-contests') . '</div>';
        }
        
        ob_start();
        
        if ($atts['display'] === 'rules') {
            // Display contest rules
            $rules = get_post_meta($contest->ID, '_contest_rules', true);
            include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/contest-rules.php';
        } elseif ($atts['display'] === 'leaderboard') {
            // Display leaderboard
            $top_posts = Database::get_top_voted_posts($contest->ID, $atts['limit']);
            include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/contest-leaderboard.php';
        } else {
            // Display submissions
            $args = [
                'post_type' => 'photo_submission',
                'posts_per_page' => $atts['limit'],
                'orderby' => $atts['orderby'],
                'order' => $atts['order'],
                'meta_query' => [
                    [
                        'key' => 'voxel:relation:contest',
                        'value' => $contest->ID,
                        'compare' => '=',
                    ]
                ]
            ];
            
            $submissions = new \WP_Query($args);
            
            include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/contest-submissions.php';
            
            wp_reset_postdata();
        }
        
        return ob_get_clean();
    }

    /**
     * Submission form shortcode.
     */
    public function submission_form_shortcode($atts) {
        $atts = shortcode_atts([
            'contest_id' => 0,
        ], $atts);
        
        if (!$atts['contest_id'] && isset($_GET['contest_id'])) {
            $atts['contest_id'] = intval($_GET['contest_id']);
        }
        
        if (!$atts['contest_id']) {
            return '<div class="voxel-alert voxel-alert-error">' . __('Please specify a contest ID.', 'voxel-photo-contests') . '</div>';
        }
        
        $contest = get_post($atts['contest_id']);
        
        if (!$contest || $contest->post_type !== 'photo_contest') {
            return '<div class="voxel-alert voxel-alert-error">' . __('Invalid contest ID.', 'voxel-photo-contests') . '</div>';
        }
        
        // Check if contest is active
        $contest_start = get_post_meta($contest->ID, '_contest_start_date', true);
        $contest_end = get_post_meta($contest->ID, '_contest_end_date', true);
        $current_time = current_time('mysql');
        
        if (($contest_start && $current_time < $contest_start) || 
            ($contest_end && $current_time > $contest_end)) {
            return '<div class="voxel-alert voxel-alert-warning">' . __('This contest is not currently accepting submissions.', 'voxel-photo-contests') . '</div>';
        }
        
        // Check user submission limit
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $max_submissions = get_post_meta($contest->ID, '_max_submissions_per_user', true) ?: 1;
            
            $existing_submissions = get_posts([
                'post_type' => 'photo_submission',
                'author' => $user_id,
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'voxel:relation:contest',
                        'value' => $contest->ID,
                        'compare' => '=',
                    ]
                ]
            ]);
            
            if (count($existing_submissions) >= $max_submissions) {
                return '<div class="voxel-alert voxel-alert-warning">' . sprintf(__('You have reached the maximum number of submissions (%d) for this contest.', 'voxel-photo-contests'), $max_submissions) . '</div>';
            }
        } else {
            return '<div class="voxel-alert voxel-alert-info">' . __('You must be logged in to submit an entry.', 'voxel-photo-contests') . '</div>';
        }
        
        // Display submission form
        ob_start();
        
        // Check if Voxel form exists
        $post_type = \Voxel\Post_Type::get('photo_submission');
        
        if ($post_type) {
            // Use Voxel's built-in form
            echo do_shortcode('[voxel_create_post post_type="photo_submission" contest_id="' . $contest->ID . '"]');
        } else {
            // Fallback to custom form
            include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/submission-form.php';
        }
        
        return ob_get_clean();
    }

    /**
     * Leaderboard shortcode.
     */
    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts([
            'contest_id' => 0,
            'limit' => 10,
            'show_votes' => 'yes',
        ], $atts);
        
        if (!$atts['contest_id']) {
            return '<div class="voxel-alert voxel-alert-error">' . __('Please specify a contest ID.', 'voxel-photo-contests') . '</div>';
        }
        
        $contest = get_post($atts['contest_id']);
        
        if (!$contest || $contest->post_type !== 'photo_contest') {
            return '<div class="voxel-alert voxel-alert-error">' . __('Invalid contest ID.', 'voxel-photo-contests') . '</div>';
        }
        
        // Check if results should be shown
        $show_results = get_post_meta($contest->ID, '_show_results', true);
        $contest_end = get_post_meta($contest->ID, '_contest_end_date', true);
        $current_time = current_time('mysql');
        
        if ($show_results === 'never') {
            return '<div class="voxel-alert voxel-alert-info">' . __('Results are not available for this contest.', 'voxel-photo-contests') . '</div>';
        }
        
        if ($show_results === 'after_contest' && $contest_end && $current_time < $contest_end) {
            return '<div class="voxel-alert voxel-alert-info">' . __('Results will be available after the contest ends.', 'voxel-photo-contests') . '</div>';
        }
        
        // Display leaderboard
        $top_posts = Database::get_top_voted_posts($contest->ID, $atts['limit']);
        
        ob_start();
        include VOXEL_PHOTOS_PLUGIN_DIR . 'templates/parts/contest-leaderboard.php';
        return ob_get_clean();
    }

    /**
     * Handle submission save.
     */
    public function handle_submission_save($post_id, $post, $update) {
        // Skip revisions and autosaves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Check if this is a new submission
        if (!$update) {
            // Get the contest ID from the relation field
            $contest_id = get_post_meta($post_id, 'voxel:relation:contest', true);
            
            if ($contest_id) {
                // Add submission count meta to contest
                $current_count = get_post_meta($contest_id, '_submission_count', true) ?: 0;
                update_post_meta($contest_id, '_submission_count', $current_count + 1);
                
                // Store the contest ID in a more accessible meta key
                update_post_meta($post_id, '_contest_id', $contest_id);
            }
        }
    }

    /**
     * Modify submission form to pre-select contest.
     */
    public function modify_submission_form($form_config, $post_type) {
        // Check if we have a contest ID in the URL
        if (isset($_GET['contest_id'])) {
            $contest_id = intval($_GET['contest_id']);
            
            // Check if contest exists
            if (get_post_type($contest_id) === 'photo_contest') {
                // Pre-fill the contest field and make it read-only
                foreach ($form_config['sections'] as $section_key => $section) {
                    foreach ($section['fields'] as $field_key => $field) {
                        if ($field['key'] === 'contest') {
                            $form_config['sections'][$section_key]['fields'][$field_key]['value'] = $contest_id;
                            $form_config['sections'][$section_key]['fields'][$field_key]['readonly'] = true;
                        }
                    }
                }
            }
        }
        
        return $form_config;
    }
}

// Initialize contests
new Contests();