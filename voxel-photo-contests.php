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
        
        // Initialize Voxel integration classes
        add_action('init', [$this, 'init_voxel_integration'], 20);
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
        
        // Flush rewrite rules
        flush_rewrite_rules();
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
                'login_required' => __('Please log in to vote', 'voxel-photo-contests')
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