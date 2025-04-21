<?php
/**
 * Plugin Name: Voxel Photo Contests
 * Description: Photo contest extension for the Voxel theme
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: voxel-photo-contests
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

final class Voxel_Photo_Contests {
    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * Plugin instance.
     *
     * @var Voxel_Photo_Contests
     */
    private static $_instance = null;

    /**
     * Get instance.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define constants.
     */
    private function define_constants() {
        define('VOXEL_PHOTOS_VERSION', $this->version);
        define('VOXEL_PHOTOS_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('VOXEL_PHOTOS_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Include required files.
     */
    private function includes() {
        // Check if Voxel theme is active
        if (!class_exists('\\Voxel\\')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-warning is-dismissible">
                    <p><?php esc_html_e('Voxel Photo Contests works best with the Voxel theme. Some features may be limited without it.', 'voxel-photo-contests'); ?></p>
                </div>
                <?php
            });
        }

        // Include core files
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/database.php';
        
        // Only include other files if admin or during AJAX requests
        if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/admin.php';
        }
    }

    /**
     * Init hooks.
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        
        // Register custom post types
        add_action('init', [$this, 'register_post_types']);
        
        // Register shortcodes
        add_shortcode('photo_contest', [$this, 'contest_shortcode']);
        add_shortcode('photo_contest_submission_form', [$this, 'submission_form_shortcode']);
        add_shortcode('photo_contest_leaderboard', [$this, 'leaderboard_shortcode']);
        
        // Register AJAX handlers
        add_action('wp_ajax_voxel_photo_contest_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_voxel_photo_contest_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_voxel_photo_contest_submit', [$this, 'handle_submission']);
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Create database tables
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/database.php';
        \Voxel_Photo_Contests\Database::create_tables();

        // Flush rewrite rules after registering custom post types
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation.
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Register post types
     */
    public function register_post_types() {
        // Register photo contest post type
        register_post_type('photo_contest', [
            'labels' => [
                'name'                  => _x('Photo Contests', 'Post Type General Name', 'voxel-photo-contests'),
                'singular_name'         => _x('Photo Contest', 'Post Type Singular Name', 'voxel-photo-contests'),
                'menu_name'             => __('Photo Contests', 'voxel-photo-contests'),
                'name_admin_bar'        => __('Photo Contest', 'voxel-photo-contests'),
            ],
            'supports'              => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-camera',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        ]);
        
        // If Voxel is active, integrate with its post type system
        if (class_exists('\\Voxel\\Post_Type')) {
            $this->register_voxel_post_types();
        } else {
            // Fallback if Voxel is not active
            register_post_type('photo_submission', [
                'labels' => [
                    'name'                  => _x('Photo Submissions', 'Post Type General Name', 'voxel-photo-contests'),
                    'singular_name'         => _x('Photo Submission', 'Post Type Singular Name', 'voxel-photo-contests'),
                    'menu_name'             => __('Photo Submissions', 'voxel-photo-contests'),
                ],
                'supports'              => ['title', 'editor', 'thumbnail', 'custom-fields', 'author'],
                'hierarchical'          => false,
                'public'                => true,
                'show_ui'               => true,
                'show_in_menu'          => true,
                'menu_position'         => 5,
                'menu_icon'             => 'dashicons-format-gallery',
                'show_in_admin_bar'     => true,
                'show_in_nav_menus'     => true,
                'can_export'            => true,
                'has_archive'           => true,
                'exclude_from_search'   => false,
                'publicly_queryable'    => true,
                'capability_type'       => 'post',
                'show_in_rest'          => true,
            ]);
        }
    }
    
    /**
     * Register Voxel post types
     */
    private function register_voxel_post_types() {
        // This is where we would integrate with Voxel's post type system
        // We'll implement this once we have the basic plugin working
    }

    /**
     * Enqueue frontend scripts and styles.
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'voxel-photo-contests',
            VOXEL_PHOTOS_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            VOXEL_PHOTOS_VERSION
        );

        wp_enqueue_script(
            'voxel-photo-contests-voting',
            VOXEL_PHOTOS_PLUGIN_URL . 'assets/js/voting.js',
            ['jquery'],
            VOXEL_PHOTOS_VERSION,
            true
        );

        wp_localize_script('voxel-photo-contests-voting', 'voxelPhotoContests', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('voxel_photo_contests_nonce'),
            'i18n' => [
                'vote_success' => __('Your vote has been recorded', 'voxel-photo-contests'),
                'vote_error' => __('Error recording your vote', 'voxel-photo-contests'),
                'already_voted' => __('You have already voted for this entry', 'voxel-photo-contests'),
            ]
        ]);
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_style(
            'voxel-photo-contests-admin',
            VOXEL_PHOTOS_PLUGIN_URL . 'assets/css/admin.css',
            [],
            VOXEL_PHOTOS_VERSION
        );

        wp_enqueue_script(
            'voxel-photo-contests-admin',
            VOXEL_PHOTOS_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            VOXEL_PHOTOS_VERSION,
            true
        );
    }
    
    /**
     * Handle vote submission
     */
    public function handle_vote() {
        // This will be implemented in a future update
        wp_send_json_error(['message' => 'Voting system is under development']);
    }
    
    /**
     * Handle photo submission
     */
    public function handle_submission() {
        // This will be implemented in a future update
        wp_send_json_error(['message' => 'Submission system is under development']);
    }
    
    /**
     * Contest shortcode
     */
    public function contest_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);
        
        if (!$atts['id']) {
            return '<div class="voxel-alert voxel-alert-error">Please specify a contest ID.</div>';
        }
        
        ob_start();
        echo '<div class="voxel-photo-contest">';
        echo '<h2>' . get_the_title($atts['id']) . '</h2>';
        echo '<div>' . get_post_field('post_content', $atts['id']) . '</div>';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Submission form shortcode
     */
    public function submission_form_shortcode($atts) {
        $atts = shortcode_atts([
            'contest_id' => 0,
        ], $atts);
        
        if (!$atts['contest_id']) {
            return '<div class="voxel-alert voxel-alert-error">Please specify a contest ID.</div>';
        }
        
        ob_start();
        echo '<div class="voxel-submission-form">';
        echo '<h3>' . __('Submit your photo', 'voxel-photo-contests') . '</h3>';
        echo '<p>' . __('Submission form will be available soon!', 'voxel-photo-contests') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }
    
    /**
     * Leaderboard shortcode
     */
    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts([
            'contest_id' => 0,
            'limit' => 10,
        ], $atts);
        
        if (!$atts['contest_id']) {
            return '<div class="voxel-alert voxel-alert-error">Please specify a contest ID.</div>';
        }
        
        ob_start();
        echo '<div class="voxel-leaderboard">';
        echo '<h3>' . __('Contest Leaderboard', 'voxel-photo-contests') . '</h3>';
        echo '<p>' . __('Leaderboard will be available soon!', 'voxel-photo-contests') . '</p>';
        echo '</div>';
        return ob_get_clean();
    }
}

// Initialize the plugin
function voxel_photo_contests() {
    return Voxel_Photo_Contests::instance();
}

// Start the plugin
voxel_photo_contests();