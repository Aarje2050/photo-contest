<?php
/**
 * Plugin Name: Voxel Photo Contests
 * Description: A photo contest system integrated with Voxel theme
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: voxel-photo-contests
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Create namespace for our plugin classes
namespace VPC {
    // Base placeholder classes to avoid errors before we create the actual files
    class Voting_System {
        private static $instance = null;
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        public function create_tables() {}
    }
    
    class Contest_Integration {
        private static $instance = null;
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
    
    class Submission_Integration {
        private static $instance = null;
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
    }
    
    class Contest_Templates {
        private static $instance = null;
        public static function instance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        public function render_contest($atts) { return ''; }
        public function render_submission_form($atts) { return ''; }
        public function render_gallery($atts) { return ''; }
    }
}

// Initialize the plugin
function vpc_init() {
    return Voxel_Photo_Contests::instance();
}

// Start the plugin
vpc_init();

class Voxel_Photo_Contests {
    
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
        // Define constants
        $this->define_constants();
        
        // Include required files
        $this->includes();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        define('VPC_VERSION', '1.0.0');
        define('VPC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('VPC_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('VPC_INCLUDES_DIR', VPC_PLUGIN_DIR . 'includes/');
        define('VPC_TEMPLATES_DIR', VPC_PLUGIN_DIR . 'templates/');
        define('VPC_ASSETS_URL', VPC_PLUGIN_URL . 'assets/');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once VPC_INCLUDES_DIR . 'class-voting-system.php';
        require_once VPC_INCLUDES_DIR . 'class-contest-integration.php';
        require_once VPC_INCLUDES_DIR . 'class-contest-templates.php';
        require_once VPC_INCLUDES_DIR . 'class-submission-integration.php';
        
        // Admin
        if (is_admin()) {
            require_once VPC_INCLUDES_DIR . 'admin/class-admin.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Add shortcodes
        add_shortcode('photo_contest', [$this, 'contest_shortcode']);
        add_shortcode('photo_contest_submission_form', [$this, 'submission_form_shortcode']);
        add_shortcode('photo_contest_gallery', [$this, 'gallery_shortcode']);
        add_shortcode('photo_contest_votes', [$this, 'votes_shortcode']);
        
        // Initialize Voxel integration classes
        add_action('init', [$this, 'init_voxel_integration'], 20);
        
        // Fix for Voxel post type indexing issues with hyphens
        add_filter('voxel/post-types/index-table-name', [$this, 'fix_index_table_name'], 10, 2);
    }
    
    /**
     * Fix index table name for post types with hyphens
     */
    public function fix_index_table_name($table_name, $post_type) {
        // For safety, make sure we only modify our own post types
        if (in_array($post_type, ['photo-contest', 'photo-submission'])) {
            // Replace hyphens with underscores for database table names
            $table_name = str_replace('-', '_', $table_name);
        }
        return $table_name;
    }
    
    /**
     * Initialize Voxel integration
     */
    public function init_voxel_integration() {
        // Make sure Voxel is active before initializing integrations
        if (!class_exists('\Voxel\Post_Type')) {
            return;
        }
        
        // Initialize integration classes
        \VPC\Contest_Integration::instance();
        \VPC\Submission_Integration::instance();
        \VPC\Contest_Templates::instance();
        \VPC\Voting_System::instance();
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        \VPC\Voting_System::instance()->create_tables();
        
        // Set up default options
        $this->setup_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set up default options
     */
    private function setup_default_options() {
        // Only set options if they don't exist
        if (get_option('vpc_require_login_for_submissions') === false) {
            update_option('vpc_require_login_for_submissions', 'yes');
        }
        
        if (get_option('vpc_allow_guest_voting') === false) {
            update_option('vpc_allow_guest_voting', 'no');
        }
        
        if (get_option('vpc_email_notifications') === false) {
            update_option('vpc_email_notifications', 'yes');
        }
        
        if (get_option('vpc_default_columns') === false) {
            update_option('vpc_default_columns', 3);
        }
        
        if (get_option('vpc_show_vote_count') === false) {
            update_option('vpc_show_vote_count', 'yes');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
        flush_rewrite_rules();
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_assets() {
        // Styles
        wp_enqueue_style(
            'vpc-styles',
            VPC_ASSETS_URL . 'css/vpc-styles.css',
            [],
            VPC_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'vpc-script',
            VPC_ASSETS_URL . 'js/vpc-script.js',
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
                'login_required' => __('Please log in to vote', 'voxel-photo-contests'),
                'votes_left' => __('You have %d vote left', 'voxel-photo-contests'),
                'no_votes_left' => __('You have used all your votes', 'voxel-photo-contests')
            ]
        ]);
    }
    
    /**
     * Contest shortcode
     */
    public function contest_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'template' => 'default',
        ], $atts);
        
        return \VPC\Contest_Templates::instance()->render_contest($atts);
    }
    
    /**
     * Submission form shortcode
     */
    public function submission_form_shortcode($atts) {
        $atts = shortcode_atts([
            'contest_id' => 0,
            'template' => 'default',
        ], $atts);
        
        return \VPC\Contest_Templates::instance()->render_submission_form($atts);
    }
    
    /**
     * Gallery shortcode
     */
    public function gallery_shortcode($atts) {
        $atts = shortcode_atts([
            'contest_id' => 0,
            'template' => 'grid',
            'columns' => 3,
            'order' => 'votes', // votes, date
        ], $atts);
        
        return \VPC\Contest_Templates::instance()->render_gallery($atts);
    }
    
    /**
     * Votes shortcode
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
        
        if (!$post_id) {
            return '<p class="vpc-error">' . __('No post ID specified', 'voxel-photo-contests') . '</p>';
        }
        
        // Get contest ID
        $contest_id = (int) $atts['contest_id'];
        if (!$contest_id) {
            // Try to get contest ID from post meta
            $contest_id = get_post_meta($post_id, 'vpc_contest_id', true);
            
            // If still no contest ID, try to get from Voxel post relation
            if (!$contest_id && class_exists('\Voxel\Post')) {
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
        }
        
        // If no contest found, show error
        if (!$contest_id) {
            return '<p class="vpc-error">' . __('No contest found for this submission', 'voxel-photo-contests') . '</p>';
        }
        
        // Return vote button HTML
        return \VPC\Voting_System::instance()->get_vote_button_html($post_id);
    }
}