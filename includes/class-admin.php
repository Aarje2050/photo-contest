<?php
namespace VPC\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Admin
 * 
 * Handles admin settings, menus and functionality
 */
class Admin {
    
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
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        
        // Save post meta
        add_action('save_post', [$this, 'save_contest_meta'], 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Photo Contests', 'voxel-photo-contests'),
            __('Photo Contests', 'voxel-photo-contests'),
            'manage_options',
            'vpc-contests',
            [$this, 'render_contests_page'],
            'dashicons-camera',
            30
        );
        
        add_submenu_page(
            'vpc-contests',
            __('All Contests', 'voxel-photo-contests'),
            __('All Contests', 'voxel-photo-contests'),
            'manage_options',
            'vpc-contests',
            [$this, 'render_contests_page']
        );
        
        add_submenu_page(
            'vpc-contests',
            __('Settings', 'voxel-photo-contests'),
            __('Settings', 'voxel-photo-contests'),
            'manage_options',
            'vpc-settings',
            [$this, 'render_settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting('vpc_settings', 'vpc_settings');
        
        // General settings section
        add_settings_section(
            'vpc_general_settings',
            __('General Settings', 'voxel-photo-contests'),
            [$this, 'render_general_settings_section'],
            'vpc-settings'
        );
        
        // Add settings fields
        add_settings_field(
            'vpc_require_login_for_submissions',
            __('Require Login for Submissions', 'voxel-photo-contests'),
            [$this, 'render_checkbox_field'],
            'vpc-settings',
            'vpc_general_settings',
            [
                'label_for' => 'vpc_require_login_for_submissions',
                'description' => __('If enabled, users must be logged in to submit entries.', 'voxel-photo-contests'),
                'default' => 'yes',
            ]
        );
        
        add_settings_field(
            'vpc_allow_guest_voting',
            __('Allow Guest Voting', 'voxel-photo-contests'),
            [$this, 'render_checkbox_field'],
            'vpc-settings',
            'vpc_general_settings',
            [
                'label_for' => 'vpc_allow_guest_voting',
                'description' => __('If enabled, non-logged in users can vote using their IP address.', 'voxel-photo-contests'),
                'default' => 'no',
            ]
        );
        
        add_settings_field(
            'vpc_email_notifications',
            __('Email Notifications', 'voxel-photo-contests'),
            [$this, 'render_checkbox_field'],
            'vpc-settings',
            'vpc_general_settings',
            [
                'label_for' => 'vpc_email_notifications',
                'description' => __('Send email notifications for new submissions.', 'voxel-photo-contests'),
                'default' => 'yes',
            ]
        );
        
        // Display settings section
        add_settings_section(
            'vpc_display_settings',
            __('Display Settings', 'voxel-photo-contests'),
            [$this, 'render_display_settings_section'],
            'vpc-settings'
        );
        
        add_settings_field(
            'vpc_default_columns',
            __('Default Gallery Columns', 'voxel-photo-contests'),
            [$this, 'render_number_field'],
            'vpc-settings',
            'vpc_display_settings',
            [
                'label_for' => 'vpc_default_columns',
                'description' => __('Default number of columns in gallery view.', 'voxel-photo-contests'),
                'default' => 3,
                'min' => 1,
                'max' => 6,
            ]
        );
        
        add_settings_field(
            'vpc_show_vote_count',
            __('Show Vote Count', 'voxel-photo-contests'),
            [$this, 'render_checkbox_field'],
            'vpc-settings',
            'vpc_display_settings',
            [
                'label_for' => 'vpc_show_vote_count',
                'description' => __('Display vote count on submissions.', 'voxel-photo-contests'),
                'default' => 'yes',
            ]
        );
    }
    
    /**
     * Render general settings section
     */
    public function render_general_settings_section() {
        echo '<p>' . __('Configure general settings for photo contests.', 'voxel-photo-contests') . '</p>';
    }
    
    /**
     * Render display settings section
     */
    public function render_display_settings_section() {
        echo '<p>' . __('Configure how contests and submissions are displayed.', 'voxel-photo-contests') . '</p>';
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $option_name = $args['label_for'];
        $description = $args['description'] ?? '';
        $default = $args['default'] ?? 'no';
        
        $value = get_option($option_name, $default);
        ?>
        <label for="<?php echo esc_attr($option_name); ?>">
            <input
                type="checkbox"
                id="<?php echo esc_attr($option_name); ?>"
                name="<?php echo esc_attr($option_name); ?>"
                value="yes"
                <?php checked($value, 'yes'); ?>
            >
            <?php echo esc_html($description); ?>
        </label>
        <?php
    }
    
    /**
     * Render number field
     */
    public function render_number_field($args) {
        $option_name = $args['label_for'];
        $description = $args['description'] ?? '';
        $default = $args['default'] ?? 0;
        $min = $args['min'] ?? 0;
        $max = $args['max'] ?? 100;
        
        $value = get_option($option_name, $default);
        ?>
        <input
            type="number"
            id="<?php echo esc_attr($option_name); ?>"
            name="<?php echo esc_attr($option_name); ?>"
            value="<?php echo esc_attr($value); ?>"
            min="<?php echo esc_attr($min); ?>"
            max="<?php echo esc_attr($max); ?>"
        >
        <p class="description"><?php echo esc_html($description); ?></p>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only enqueue on our pages
        if (strpos($hook, 'vpc-') === false) {
            return;
        }
        
        // Admin CSS
        wp_enqueue_style(
            'vpc-admin-styles',
            VPC_ASSETS_URL . 'css/admin.css',
            [],
            VPC_VERSION
        );
        
        // Admin JS
        wp_enqueue_script(
            'vpc-admin-script',
            VPC_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            VPC_VERSION,
            true
        );
        
        wp_localize_script('vpc-admin-script', 'vpcAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vpc-admin-nonce'),
        ]);
    }
    
    /**
     * Render contests admin page
     */
    public function render_contests_page() {
        // Check for action parameter
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        switch ($action) {
            case 'results':
                $this->render_contest_results();
                break;
                
            case 'list':
            default:
                $this->render_contests_list();
                break;
        }
    }
    
    /**
     * Render contests list
     */
    private function render_contests_list() {
        // Get all contests
        $contests = get_posts([
            'post_type' => 'contest',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Photo Contests', 'voxel-photo-contests'); ?></h1>
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=contest')); ?>" class="page-title-action"><?php echo esc_html__('Add New', 'voxel-photo-contests'); ?></a>
            <hr class="wp-header-end">
            
            <?php if (empty($contests)): ?>
                <div class="notice notice-info">
                    <p><?php echo esc_html__('No contests found. Create your first contest to get started.', 'voxel-photo-contests'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php echo esc_html__('Contest', 'voxel-photo-contests'); ?></th>
                            <th scope="col"><?php echo esc_html__('Status', 'voxel-photo-contests'); ?></th>
                            <th scope="col"><?php echo esc_html__('Submissions', 'voxel-photo-contests'); ?></th>
                            <th scope="col"><?php echo esc_html__('Votes', 'voxel-photo-contests'); ?></th>
                            <th scope="col"><?php echo esc_html__('Date Range', 'voxel-photo-contests'); ?></th>
                            <th scope="col"><?php echo esc_html__('Actions', 'voxel-photo-contests'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contests as $contest): ?>
                            <?php
                            $contest_data = \VPC\Contest_Integration::instance()->get_contest_data($contest->ID);
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($contest->ID)); ?>">
                                        <strong><?php echo esc_html($contest->post_title); ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'vpc-status-' . $contest_data['status'];
                                    $status_text = ucfirst($contest_data['status']);
                                    echo sprintf('<span class="vpc-status %s">%s</span>', esc_attr($status_class), esc_html($status_text));
                                    ?>
                                </td>
                                <td><?php echo esc_html($contest_data['submissions_count']); ?></td>
                                <td><?php echo esc_html($contest_data['votes_count']); ?></td>
                                <td>
                                    <?php
                                    if ($contest_data['start_date'] && $contest_data['end_date']) {
                                        echo esc_html($contest_data['start_date'] . ' - ' . $contest_data['end_date']);
                                    } elseif ($contest_data['start_date']) {
                                        echo esc_html($contest_data['start_date'] . ' - ' . __('Ongoing', 'voxel-photo-contests'));
                                    } elseif ($contest_data['end_date']) {
                                        echo esc_html(__('Until', 'voxel-photo-contests') . ' ' . $contest_data['end_date']);
                                    } else {
                                        echo esc_html__('No date limit', 'voxel-photo-contests');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=vpc-contests&action=results&contest_id=' . $contest->ID)); ?>" class="button button-small">
                                        <?php echo esc_html__('View Results', 'voxel-photo-contests'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(get_permalink($contest->ID)); ?>" class="button button-small">
                                        <?php echo esc_html__('View Contest', 'voxel-photo-contests'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render contest results
     */
    private function render_contest_results() {
        // Get contest ID
        $contest_id = isset($_GET['contest_id']) ? intval($_GET['contest_id']) : 0;
        
        if (!$contest_id) {
            wp_redirect(admin_url('admin.php?page=vpc-contests'));
            exit;
        }
        
        $contest = get_post($contest_id);
        if (!$contest || $contest->post_type !== 'contest') {
            wp_redirect(admin_url('admin.php?page=vpc-contests'));
            exit;
        }
        
        // Get contest data
        $contest_data = \VPC\Contest_Integration::instance()->get_contest_data($contest_id);
        
        // Get top submissions
        $top_submissions = \VPC\Voting_System::instance()->get_top_submissions($contest_id, 20);
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php printf(__('Results: %s', 'voxel-photo-contests'), esc_html($contest->post_title)); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=vpc-contests')); ?>" class="page-title-action"><?php echo esc_html__('Back to Contests', 'voxel-photo-contests'); ?></a>
            <hr class="wp-header-end">
            
            <div class="vpc-results-summary">
                <div class="vpc-summary-box">
                    <h2><?php echo esc_html__('Contest Summary', 'voxel-photo-contests'); ?></h2>
                    <ul>
                        <li><strong><?php echo esc_html__('Status:', 'voxel-photo-contests'); ?></strong> <?php echo esc_html(ucfirst($contest_data['status'])); ?></li>
                        <li><strong><?php echo esc_html__('Submissions:', 'voxel-photo-contests'); ?></strong> <?php echo esc_html($contest_data['submissions_count']); ?></li>
                        <li><strong><?php echo esc_html__('Total Votes:', 'voxel-photo-contests'); ?></strong> <?php echo esc_html($contest_data['votes_count']); ?></li>
                        <li><strong><?php echo esc_html__('Unique Voters:', 'voxel-photo-contests'); ?></strong> <?php echo esc_html($contest_data['unique_voters_count']); ?></li>
                        <?php if ($contest_data['start_date']): ?>
                            <li><strong><?php echo esc_html__('Start Date:', 'voxel-photo-contests'); ?></strong> <?php echo esc_html($contest_data['start_date']); ?></li>
                        <?php endif; ?>
                        <?php if ($contest_data['end_date']): ?>
                            <li><strong><?php echo esc_html__('End Date:', 'voxel-photo-contests'); ?></strong> <?php echo esc_html($contest_data['end_date']); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="vpc-top-submissions">
                    <h2><?php echo esc_html__('Top Submissions', 'voxel-photo-contests'); ?></h2>
                    
                    <?php if (empty($top_submissions)): ?>
                        <p><?php echo esc_html__('No submissions found for this contest.', 'voxel-photo-contests'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo esc_html__('Rank', 'voxel-photo-contests'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Submission', 'voxel-photo-contests'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Author', 'voxel-photo-contests'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Votes', 'voxel-photo-contests'); ?></th>
                                    <th scope="col"><?php echo esc_html__('Actions', 'voxel-photo-contests'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_submissions as $index => $submission): ?>
                                    <tr>
                                        <td><?php echo esc_html($index + 1); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(get_permalink($submission->ID)); ?>">
                                                <?php echo esc_html(get_the_title($submission->ID)); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php
                                            $author = get_user_by('id', $submission->post_author);
                                            if ($author) {
                                                echo esc_html($author->display_name);
                                            } else {
                                                echo esc_html__('Unknown', 'voxel-photo-contests');
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo esc_html(get_post_meta($submission->ID, 'vpc_vote_count', true) ?: 0); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(get_permalink($submission->ID)); ?>" class="button button-small">
                                                <?php echo esc_html__('View', 'voxel-photo-contests'); ?>
                                            </a>
                                            <a href="<?php echo esc_url(get_edit_post_link($submission->ID)); ?>" class="button button-small">
                                                <?php echo esc_html__('Edit', 'voxel-photo-contests'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Photo Contest Settings', 'voxel-photo-contests'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('vpc_settings');
                do_settings_sections('vpc-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'vpc-contest-settings',
            __('Contest Settings', 'voxel-photo-contests'),
            [$this, 'render_contest_meta_box'],
            'contest',
            'normal',
            'high'
        );
    }
    
    /**
     * Render contest meta box
     */
    public function render_contest_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('vpc_contest_meta_box', 'vpc_contest_meta_box_nonce');
        
        // Get saved values
        $enable_voting = get_post_meta($post->ID, 'vpc_enable_voting', true) ?: 'yes';
        $start_date = get_post_meta($post->ID, 'vpc_contest_starts', true);
        $end_date = get_post_meta($post->ID, 'vpc_contest_ends', true);
        $max_votes = get_post_meta($post->ID, 'vpc_max_votes_per_user', true) ?: 1;
        
        // Check if we're using Voxel fields instead
        $using_voxel_fields = false;
        if (class_exists('\Voxel\Post')) {
            $contest = \Voxel\Post::get($post->ID);
            if ($contest) {
                // Check if Voxel fields exist
                $voting_field = $contest->get_field('enable_voting');
                $start_field = $contest->get_field('contest_starts');
                $end_field = $contest->get_field('contest_ends');
                $max_votes_field = $contest->get_field('max_votes_per_user');
                
                if ($voting_field && $start_field && $end_field && $max_votes_field) {
                    $using_voxel_fields = true;
                }
            }
        }
        
        // If using Voxel fields, show a notice
        if ($using_voxel_fields) {
            echo '<div class="notice notice-info inline"><p>';
            echo esc_html__('This contest is using Voxel fields for configuration. Please use the Voxel form editor to modify these settings.', 'voxel-photo-contests');
            echo '</p></div>';
            return;
        }
        
        ?>
        <style>
            .vpc-field {
                margin-bottom: 15px;
            }
            .vpc-field label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .vpc-field input[type="text"],
            .vpc-field input[type="number"],
            .vpc-field input[type="date"],
            .vpc-field select {
                width: 100%;
                max-width: 300px;
            }
            .vpc-field-row {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }
            .vpc-field-col {
                flex: 1;
                min-width: 200px;
            }
        </style>
        
        <div class="vpc-field">
            <label><?php _e('Enable Voting', 'voxel-photo-contests'); ?></label>
            <select name="vpc_enable_voting">
                <option value="yes" <?php selected($enable_voting, 'yes'); ?>><?php _e('Yes', 'voxel-photo-contests'); ?></option>
                <option value="no" <?php selected($enable_voting, 'no'); ?>><?php _e('No', 'voxel-photo-contests'); ?></option>
            </select>
            <p class="description"><?php _e('Allow users to vote on submissions for this contest.', 'voxel-photo-contests'); ?></p>
        </div>
        
        <div class="vpc-field-row">
            <div class="vpc-field-col">
                <div class="vpc-field">
                    <label><?php _e('Contest Starts', 'voxel-photo-contests'); ?></label>
                    <input type="date" name="vpc_contest_starts" value="<?php echo esc_attr($start_date); ?>">
                    <p class="description"><?php _e('The date when the contest begins. Leave blank for no start date.', 'voxel-photo-contests'); ?></p>
                </div>
            </div>
            
            <div class="vpc-field-col">
                <div class="vpc-field">
                    <label><?php _e('Contest Ends', 'voxel-photo-contests'); ?></label>
                    <input type="date" name="vpc_contest_ends" value="<?php echo esc_attr($end_date); ?>">
                    <p class="description"><?php _e('The date when the contest ends. Leave blank for no end date.', 'voxel-photo-contests'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="vpc-field">
            <label><?php _e('Maximum Votes Per User', 'voxel-photo-contests'); ?></label>
            <input type="number" name="vpc_max_votes_per_user" value="<?php echo esc_attr($max_votes); ?>" min="1">
            <p class="description"><?php _e('How many different submissions a single user can vote for.', 'voxel-photo-contests'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Save contest meta
     */
    public function save_contest_meta($post_id, $post) {
        // Check if we're using Voxel fields instead
        $using_voxel_fields = false;
        if (class_exists('\Voxel\Post')) {
            $contest = \Voxel\Post::get($post_id);
            if ($contest) {
                // Check if Voxel fields exist
                $voting_field = $contest->get_field('enable_voting');
                $start_field = $contest->get_field('contest_starts');
                $end_field = $contest->get_field('contest_ends');
                $max_votes_field = $contest->get_field('max_votes_per_user');
                
                if ($voting_field && $start_field && $end_field && $max_votes_field) {
                    $using_voxel_fields = true;
                }
            }
        }
        
        // If using Voxel fields, don't save our custom meta
        if ($using_voxel_fields) {
            return;
        }
        
        // Check if nonce is set
        if (!isset($_POST['vpc_contest_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['vpc_contest_meta_box_nonce'], 'vpc_contest_meta_box')) {
            return;
        }
        
        // If this is an autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Only save for contest post type
        if ($post->post_type !== 'contest') {
            return;
        }
        
        // Save contest settings
        if (isset($_POST['vpc_enable_voting'])) {
            update_post_meta($post_id, 'vpc_enable_voting', sanitize_text_field($_POST['vpc_enable_voting']));
        }
        
        if (isset($_POST['vpc_contest_starts'])) {
            update_post_meta($post_id, 'vpc_contest_starts', sanitize_text_field($_POST['vpc_contest_starts']));
        }
        
        if (isset($_POST['vpc_contest_ends'])) {
            update_post_meta($post_id, 'vpc_contest_ends', sanitize_text_field($_POST['vpc_contest_ends']));
        }
        
        if (isset($_POST['vpc_max_votes_per_user'])) {
            update_post_meta($post_id, 'vpc_max_votes_per_user', intval($_POST['vpc_max_votes_per_user']));
        }
    }
}