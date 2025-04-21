<?php
/**
 * Plugin Name: Voxel Photo Contests
 * Description: Photo contest extension for the Voxel theme
 * Version: 1.0.0
 * Author: Rajesh Jat
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
    // Add a notice but don't prevent plugin from loading
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php esc_html_e('Voxel theme is not detected. Some Voxel-specific features may be limited.', 'voxel-photo-contests'); ?></p>
        </div>
        <?php
    });
    
    // Define a minimal Voxel Post_Type class if it doesn't exist
    if (!class_exists('\\Voxel\\Post_Type')) {
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/compatibility/post-type.php';
    }
}

        // Include core files
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/database.php';
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/post-types.php';
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/fields.php';
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/voting.php';
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/templates.php';
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/contests.php';

        // Include widgets if Elementor is active
        if (did_action('elementor/loaded')) {
            add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
        }
    }

    /**
     * Register Elementor widgets.
     */
    public function register_widgets() {
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'widgets/contest-feed.php';
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'widgets/submission-form.php';
        require_once VOXEL_PHOTOS_PLUGIN_DIR . 'widgets/leaderboard.php';

        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Voxel_Photo_Contests\Widgets\Contest_Feed());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Voxel_Photo_Contests\Widgets\Submission_Form());
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Voxel_Photo_Contests\Widgets\Leaderboard());
    }

    /**
     * Init hooks.
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
    }

    /**
     * Plugin activation.
     */
    public function activate() {
        // Make sure Database class is loaded
require_once VOXEL_PHOTOS_PLUGIN_DIR . 'includes/database.php';
// Create database tables
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
}

// Initialize the plugin
function voxel_photo_contests() {
    return Voxel_Photo_Contests::instance();
}

// Start the plugin
voxel_photo_contests();