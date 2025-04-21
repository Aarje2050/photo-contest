<?php
/**
 * Plugin Name: Voxel Photo Contests
 * Description: Voting system for photo contests using Voxel post types
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: voxel-photo-contests
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Voxel_Photo_Contests {
    
    private $settings;
    
    public function __construct() {
        // Define constants
        define('VPC_VERSION', '1.0.0');
        define('VPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('VPC_PLUGIN_URL', plugin_dir_url(__FILE__));
        
        // Load settings
        $this->settings = get_option('vpc_settings', [
            'contest_post_type' => 'post',
            'submission_post_type' => 'submission',
            'enable_voting' => 'yes'
        ]);
        
        // Initialize the plugin
        $this->init();
    }
    
    public function init() {
        // Create database table on activation
        register_activation_hook(__FILE__, [$this, 'create_tables']);
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Admin settings
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Add shortcodes
        add_shortcode('photo_contest_votes', [$this, 'votes_shortcode']);
        
        // Add AJAX handlers
        add_action('wp_ajax_vpc_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_vpc_vote', [$this, 'handle_vote']);
        
        // Add meta box for contest settings
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_data']);
        
        // Add contest relationship field to submission post type
        add_action('add_meta_boxes', [$this, 'add_contest_relationship_meta_box']);
        add_action('save_post', [$this, 'save_contest_relationship']);
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vpc_votes';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            contest_id bigint(20) NOT NULL DEFAULT 0,
            user_id varchar(100) NOT NULL,
            vote_value int(11) NOT NULL DEFAULT 1,
            vote_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY vote_once (post_id, user_id),
            KEY contest_id (contest_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Photo Contests', 'voxel-photo-contests'),
            __('Photo Contests', 'voxel-photo-contests'),
            'manage_options',
            'vpc-settings',
            [$this, 'render_settings_page'],
            'dashicons-camera',
            30
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('vpc_settings_group', 'vpc_settings');
        
        add_settings_section(
            'vpc_general_section',
            __('General Settings', 'voxel-photo-contests'),
            [$this, 'vpc_general_section_callback'],
            'vpc_settings_page'
        );
        
        add_settings_field(
            'contest_post_type',
            __('Contest Post Type', 'voxel-photo-contests'),
            [$this, 'contest_post_type_callback'],
            'vpc_settings_page',
            'vpc_general_section'
        );
        
        add_settings_field(
            'submission_post_type',
            __('Submission Post Type', 'voxel-photo-contests'),
            [$this, 'submission_post_type_callback'],
            'vpc_settings_page',
            'vpc_general_section'
        );
        
        add_settings_field(
            'enable_voting',
            __('Enable Voting', 'voxel-photo-contests'),
            [$this, 'enable_voting_callback'],
            'vpc_settings_page',
            'vpc_general_section'
        );
    }
    
    /**
     * Settings section callback
     */
    public function vpc_general_section_callback() {
        echo '<p>' . __('Configure which post types to use for contests and submissions.', 'voxel-photo-contests') . '</p>';
    }
    
    /**
     * Contest post type field callback
     */
    public function contest_post_type_callback() {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected = $this->settings['contest_post_type'] ?? 'post';
        
        echo '<select name="vpc_settings[contest_post_type]">';
        foreach ($post_types as $post_type) {
            echo '<option value="' . esc_attr($post_type->name) . '" ' . selected($selected, $post_type->name, false) . '>' 
                . esc_html($post_type->labels->singular_name) . ' (' . esc_html($post_type->name) . ')</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('The post type that will be used for contests.', 'voxel-photo-contests') . '</p>';
    }
    
    /**
     * Submission post type field callback
     */
    public function submission_post_type_callback() {
        $post_types = get_post_types(['public' => true], 'objects');
        $selected = $this->settings['submission_post_type'] ?? 'submission';
        
        echo '<select name="vpc_settings[submission_post_type]">';
        foreach ($post_types as $post_type) {
            echo '<option value="' . esc_attr($post_type->name) . '" ' . selected($selected, $post_type->name, false) . '>' 
                . esc_html($post_type->labels->singular_name) . ' (' . esc_html($post_type->name) . ')</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('The post type that will be used for contest submissions.', 'voxel-photo-contests') . '</p>';
    }
    
    /**
     * Enable voting field callback
     */
    public function enable_voting_callback() {
        $enable_voting = $this->settings['enable_voting'] ?? 'yes';
        
        echo '<select name="vpc_settings[enable_voting]">';
        echo '<option value="yes" ' . selected($enable_voting, 'yes', false) . '>' . __('Yes', 'voxel-photo-contests') . '</option>';
        echo '<option value="no" ' . selected($enable_voting, 'no', false) . '>' . __('No', 'voxel-photo-contests') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Enable or disable voting functionality.', 'voxel-photo-contests') . '</p>';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('vpc_settings_group');
                do_settings_sections('vpc_settings_page');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'vpc-styles',
            VPC_PLUGIN_URL . 'assets/css/vpc-styles.css',
            [],
            VPC_VERSION
        );
        
        wp_enqueue_script(
            'vpc-script',
            VPC_PLUGIN_URL . 'assets/js/vpc-script.js',
            ['jquery'],
            VPC_VERSION,
            true
        );
        
        wp_localize_script('vpc-script', 'vpcData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vpc-vote-nonce'),
            'messages' => [
                'vote_success' => __('Your vote has been recorded!', 'voxel-photo-contests'),
                'vote_error' => __('Error recording your vote', 'voxel-photo-contests'),
                'login_required' => __('Please log in to vote', 'voxel-photo-contests')
            ]
        ]);
    }
    
    /**
     * Add meta boxes for contest settings
     */
    public function add_meta_boxes() {
        // Add meta box to contest post type
        add_meta_box(
            'vpc-contest-settings',
            __('Contest Settings', 'voxel-photo-contests'),
            [$this, 'render_contest_meta_box'],
            $this->settings['contest_post_type'],
            'normal',
            'high'
        );
    }
    
    /**
     * Add contest relationship meta box to submission post type
     */
    public function add_contest_relationship_meta_box() {
        add_meta_box(
            'vpc-contest-relationship',
            __('Contest', 'voxel-photo-contests'),
            [$this, 'render_contest_relationship_meta_box'],
            $this->settings['submission_post_type'],
            'side',
            'default'
        );
    }
    
    /**
     * Render meta box for contest settings
     */
    public function render_contest_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('vpc_meta_box', 'vpc_meta_box_nonce');
        
        // Get saved values
        $enable_voting = get_post_meta($post->ID, 'vpc_enable_voting', true) ?: 'yes';
        $contest_starts = get_post_meta($post->ID, 'vpc_contest_starts', true);
        $contest_ends = get_post_meta($post->ID, 'vpc_contest_ends', true);
        $max_votes_per_user = get_post_meta($post->ID, 'vpc_max_votes_per_user', true) ?: 1;
        
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
        </div>
        
        <div class="vpc-field-row">
            <div class="vpc-field-col">
                <div class="vpc-field">
                    <label><?php _e('Contest Starts', 'voxel-photo-contests'); ?></label>
                    <input type="date" name="vpc_contest_starts" value="<?php echo esc_attr($contest_starts); ?>">
                </div>
            </div>
            
            <div class="vpc-field-col">
                <div class="vpc-field">
                    <label><?php _e('Contest Ends', 'voxel-photo-contests'); ?></label>
                    <input type="date" name="vpc_contest_ends" value="<?php echo esc_attr($contest_ends); ?>">
                </div>
            </div>
        </div>
        
        <div class="vpc-field">
            <label><?php _e('Maximum Votes Per User', 'voxel-photo-contests'); ?></label>
            <input type="number" name="vpc_max_votes_per_user" value="<?php echo esc_attr($max_votes_per_user); ?>" min="1">
            <p class="description"><?php _e('How many different submissions a single user can vote for.', 'voxel-photo-contests'); ?></p>
        </div>
        
        <?php
    }
    
    /**
     * Render contest relationship meta box
     */
    public function render_contest_relationship_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('vpc_relationship_meta_box', 'vpc_relationship_meta_box_nonce');
        
        // Get selected contest
        $contest_id = get_post_meta($post->ID, 'vpc_contest_id', true);
        
        // Get available contests
        $contests = get_posts([
            'post_type' => $this->settings['contest_post_type'],
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        
        ?>
        <p>
            <select name="vpc_contest_id" style="width:100%;">
                <option value=""><?php _e('-- Select Contest --', 'voxel-photo-contests'); ?></option>
                <?php foreach ($contests as $contest): ?>
                    <option value="<?php echo esc_attr($contest->ID); ?>" <?php selected($contest_id, $contest->ID); ?>>
                        <?php echo esc_html($contest->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_data($post_id) {
        // Check if nonce is set
        if (!isset($_POST['vpc_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['vpc_meta_box_nonce'], 'vpc_meta_box')) {
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
    
    /**
     * Save contest relationship
     */
    public function save_contest_relationship($post_id) {
        // Check if nonce is set
        if (!isset($_POST['vpc_relationship_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['vpc_relationship_meta_box_nonce'], 'vpc_relationship_meta_box')) {
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
        
        // Save contest relationship
        if (isset($_POST['vpc_contest_id'])) {
            update_post_meta($post_id, 'vpc_contest_id', intval($_POST['vpc_contest_id']));
        }
    }
    
    /**
     * Handle voting via AJAX
     */
    public function handle_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vpc-vote-nonce')) {
            wp_send_json_error(['message' => 'Invalid security token']);
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(['message' => 'Invalid post ID']);
        }
        
        // Get contest ID (either from post meta or directly from AJAX)
        $contest_id = isset($_POST['contest_id']) ? intval($_POST['contest_id']) : get_post_meta($post_id, 'vpc_contest_id', true);
        
        // If no contest ID, try to get it from the post
        if (!$contest_id) {
            wp_send_json_error(['message' => 'No associated contest found']);
        }
        
        // Check if voting is enabled for this contest
        $enable_voting = get_post_meta($contest_id, 'vpc_enable_voting', true);
        if ($enable_voting !== 'yes') {
            wp_send_json_error(['message' => 'Voting is not enabled for this contest']);
        }
        
        // Check if contest has started
        $contest_starts = get_post_meta($contest_id, 'vpc_contest_starts', true);
        if ($contest_starts && strtotime($contest_starts) > time()) {
            wp_send_json_error(['message' => 'This contest has not started yet']);
        }
        
        // Check if contest has ended
        $contest_ends = get_post_meta($contest_id, 'vpc_contest_ends', true);
        if ($contest_ends && strtotime($contest_ends) < time()) {
            wp_send_json_error(['message' => 'This contest has ended']);
        }
        
        // Get user ID (use IP for non-logged in users)
        $user_id = is_user_logged_in() ? 'user_' . get_current_user_id() : 'ip_' . md5($_SERVER['REMOTE_ADDR']);
        
        // Check max votes per user
        $max_votes = get_post_meta($contest_id, 'vpc_max_votes_per_user', true) ?: 1;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        
        // Count how many votes this user has made in the contest
        $vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE contest_id = %d AND user_id = %s",
            $contest_id, $user_id
        ));
        
        // Check if user has already voted for this post
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND user_id = %s",
            $post_id, $user_id
        ));
        
        if ($existing_vote) {
            wp_send_json_error(['message' => 'You have already voted for this entry']);
        }
        
        // Check if user has reached max votes
        if ($vote_count >= $max_votes) {
            wp_send_json_error(['message' => sprintf('You have already used all %d of your allowed votes', $max_votes)]);
        }
        
        // Record vote
        try {
            $result = $wpdb->insert(
                $table_name,
                [
                    'post_id' => $post_id,
                    'contest_id' => $contest_id,
                    'user_id' => $user_id,
                    'vote_value' => 1,
                    'vote_date' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%d', '%s']
            );
            
            if ($result === false) {
                wp_send_json_error(['message' => 'Error recording vote']);
            }
            
            // Get updated vote count for this post
            $post_vote_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
                $post_id
            ));
            
            // Update post meta for quick access to vote count
            update_post_meta($post_id, 'vpc_vote_count', $post_vote_count);
            
            // Also update Voxel field if it exists
            update_post_meta($post_id, 'voxel:vote_count', $post_vote_count);
            
            wp_send_json_success([
                'message' => 'Vote recorded successfully',
                'count' => $post_vote_count
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error recording vote: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Shortcode to display votes
     */
    public function votes_shortcode($atts) {
        $atts = shortcode_atts([
            'post_id' => 0,
            'contest_id' => 0,
        ], $atts);
        
        $post_id = (int) $atts['post_id'];
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Get contest ID
        $contest_id = (int) $atts['contest_id'];
        if (!$contest_id) {
            $contest_id = get_post_meta($post_id, 'vpc_contest_id', true);
        }
        
        // If no contest, don't show voting
        if (!$contest_id) {
            return '';
        }
        
        // Check if voting is enabled
        $enable_voting = get_post_meta($contest_id, 'vpc_enable_voting', true);
        if ($enable_voting !== 'yes') {
            return '';
        }
        
        // Check if contest has started
        $contest_starts = get_post_meta($contest_id, 'vpc_contest_starts', true);
        $contest_not_started = $contest_starts && strtotime($contest_starts) > time();
        
        // Check if contest has ended
        $contest_ends = get_post_meta($contest_id, 'vpc_contest_ends', true);
        $contest_ended = $contest_ends && strtotime($contest_ends) < time();
        
        // Get vote count
        $vote_count = get_post_meta($post_id, 'vpc_vote_count', true) ?: 0;
        
        // Get user ID
        $user_id = is_user_logged_in() ? 'user_' . get_current_user_id() : 'ip_' . md5($_SERVER['REMOTE_ADDR']);
        
        // Check if user has already voted
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        $has_voted = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND user_id = %s",
            $post_id, $user_id
        ));
        
        // Get max votes per user
        $max_votes = get_post_meta($contest_id, 'vpc_max_votes_per_user', true) ?: 1;
        
        // Count how many votes this user has made in the contest
        $user_vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE contest_id = %d AND user_id = %s",
            $contest_id, $user_id
        ));
        
        $votes_left = $max_votes - $user_vote_count;
        
        ob_start();
        ?>
        <div class="vpc-vote-container" data-post-id="<?php echo $post_id; ?>" data-contest-id="<?php echo $contest_id; ?>">
            <div class="vpc-vote-count"><?php echo $vote_count; ?> <?php echo _n('vote', 'votes', $vote_count, 'voxel-photo-contests'); ?></div>
            
            <?php if (!$contest_ended && !$contest_not_started && !$has_voted && $votes_left > 0): ?>
                <button class="vpc-vote-button">
                    <span class="vpc-heart">♡</span> <?php _e('Vote', 'voxel-photo-contests'); ?>
                </button>
                
                <?php if ($max_votes > 1): ?>
                    <div class="vpc-votes-left">
                        <?php printf(_n('You have %d vote left', 'You have %d votes left', $votes_left, 'voxel-photo-contests'), $votes_left); ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($has_voted): ?>
                <div class="vpc-voted">
                    <span class="vpc-heart vpc-voted-heart">❤</span> <?php _e('Voted', 'voxel-photo-contests'); ?>
                </div>
            <?php elseif ($votes_left <= 0): ?>
                <div class="vpc-votes-limit-reached">
                    <?php _e('You have used all your votes', 'voxel-photo-contests'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($contest_not_started): ?>
                <div class="vpc-contest-info">
                    <?php printf(__('Contest starts: %s', 'voxel-photo-contests'), date_i18n(get_option('date_format'), strtotime($contest_starts))); ?>
                </div>
            <?php elseif ($contest_ends): ?>
                <div class="vpc-contest-info">
                    <?php if ($contest_ended): ?>
                        <?php _e('Contest ended', 'voxel-photo-contests'); ?>
                    <?php else: ?>
                        <?php printf(__('Contest ends: %s', 'voxel-photo-contests'), date_i18n(get_option('date_format'), strtotime($contest_ends))); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize the plugin
new Voxel_Photo_Contests();