<?php
/**
 * Plugin Name: Voxel Photo Contests
 * Description: Simple voting system for photo contests using Voxel post types
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: voxel-photo-contests
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Voxel_Photo_Contests {
    public function __construct() {
        // Define constants
        define('VPC_VERSION', '1.0.0');
        define('VPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('VPC_PLUGIN_URL', plugin_dir_url(__FILE__));
        
        // Initialize the plugin
        $this->init();
    }
    
    public function init() {
        // Create database table on activation
        register_activation_hook(__FILE__, [$this, 'create_tables']);
        
        // Register scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Add shortcodes
        add_shortcode('photo_contest_votes', [$this, 'votes_shortcode']);
        
        // Add AJAX handlers
        add_action('wp_ajax_vpc_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_vpc_vote', [$this, 'handle_vote']);
        
        // Add meta box for contest settings
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_data']);
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
            user_id varchar(100) NOT NULL,
            vote_value int(11) NOT NULL DEFAULT 1,
            vote_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY vote_once (post_id, user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
        // Get all Voxel post types
        $post_types = get_post_types(['public' => true]);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'vpc-contest-settings',
                __('Contest Settings', 'voxel-photo-contests'),
                [$this, 'render_contest_meta_box'],
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    /**
     * Render meta box for contest settings
     */
    public function render_contest_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('vpc_meta_box', 'vpc_meta_box_nonce');
        
        // Get saved values
        $enable_voting = get_post_meta($post->ID, 'vpc_enable_voting', true);
        $contest_ends = get_post_meta($post->ID, 'vpc_contest_ends', true);
        
        ?>
        <p>
            <label>
                <input type="checkbox" name="vpc_enable_voting" value="1" <?php checked($enable_voting, '1'); ?>>
                <?php _e('Enable voting for this post', 'voxel-photo-contests'); ?>
            </label>
        </p>
        <p>
            <label><?php _e('Contest ends:', 'voxel-photo-contests'); ?></label><br>
            <input type="date" name="vpc_contest_ends" value="<?php echo esc_attr($contest_ends); ?>">
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
        
        // Save data
        if (isset($_POST['vpc_enable_voting'])) {
            update_post_meta($post_id, 'vpc_enable_voting', '1');
        } else {
            delete_post_meta($post_id, 'vpc_enable_voting');
        }
        
        if (isset($_POST['vpc_contest_ends'])) {
            update_post_meta($post_id, 'vpc_contest_ends', sanitize_text_field($_POST['vpc_contest_ends']));
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
        
        // Check if voting is enabled for this post
        $enable_voting = get_post_meta($post_id, 'vpc_enable_voting', true);
        if ($enable_voting !== '1') {
            wp_send_json_error(['message' => 'Voting is not enabled for this post']);
        }
        
        // Check if contest has ended
        $contest_ends = get_post_meta($post_id, 'vpc_contest_ends', true);
        if ($contest_ends && strtotime($contest_ends) < time()) {
            wp_send_json_error(['message' => 'This contest has ended']);
        }
        
        // Get user ID (use IP for non-logged in users)
        $user_id = is_user_logged_in() ? get_current_user_id() : md5($_SERVER['REMOTE_ADDR']);
        
        // Record vote
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        
        try {
            $result = $wpdb->insert(
                $table_name,
                [
                    'post_id' => $post_id,
                    'user_id' => $user_id,
                    'vote_value' => 1,
                    'vote_date' => current_time('mysql')
                ],
                ['%d', '%s', '%d', '%s']
            );
            
            if ($result === false) {
                // Check if it's because user already voted
                $existing_vote = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE post_id = %d AND user_id = %s",
                    $post_id, $user_id
                ));
                
                if ($existing_vote) {
                    wp_send_json_error(['message' => 'You have already voted for this post']);
                } else {
                    wp_send_json_error(['message' => 'Error recording vote']);
                }
            }
            
            // Get updated vote count
            $vote_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
                $post_id
            ));
            
            // Update post meta for quick access to vote count
            update_post_meta($post_id, 'vpc_vote_count', $vote_count);
            
            wp_send_json_success([
                'message' => 'Vote recorded successfully',
                'count' => $vote_count
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
        ], $atts);
        
        $post_id = (int) $atts['post_id'];
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        // Check if voting is enabled
        $enable_voting = get_post_meta($post_id, 'vpc_enable_voting', true);
        if ($enable_voting !== '1') {
            return '';
        }
        
        // Check if contest has ended
        $contest_ends = get_post_meta($post_id, 'vpc_contest_ends', true);
        $contest_ended = $contest_ends && strtotime($contest_ends) < time();
        
        // Get vote count
        $vote_count = get_post_meta($post_id, 'vpc_vote_count', true) ?: 0;
        
        // Get user ID
        $user_id = is_user_logged_in() ? get_current_user_id() : md5($_SERVER['REMOTE_ADDR']);
        
        // Check if user has already voted
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        $has_voted = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND user_id = %s",
            $post_id, $user_id
        ));
        
        ob_start();
        ?>
        <div class="vpc-vote-container" data-post-id="<?php echo $post_id; ?>">
            <div class="vpc-vote-count"><?php echo $vote_count; ?> <?php echo _n('vote', 'votes', $vote_count, 'voxel-photo-contests'); ?></div>
            
            <?php if (!$contest_ended && !$has_voted): ?>
                <button class="vpc-vote-button">
                    <span class="vpc-heart">♡</span> <?php _e('Vote', 'voxel-photo-contests'); ?>
                </button>
            <?php elseif ($has_voted): ?>
                <div class="vpc-voted">
                    <span class="vpc-heart vpc-voted-heart">❤</span> <?php _e('Voted', 'voxel-photo-contests'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($contest_ends): ?>
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