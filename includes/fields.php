<?php
namespace Voxel_Photo_Contests;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Fields {
    /**
     * Constructor.
     */
    public function __construct() {
        // Add meta boxes for contest post type
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        
        // Register custom fields for contest meta data
        add_action('cmb2_admin_init', [$this, 'register_contest_meta']);
    }

    /**
     * Add meta boxes for contest post type.
     */
    public function add_meta_boxes() {
        add_meta_box(
            'voxel_photo_contest_settings',
            __('Contest Settings', 'voxel-photo-contests'),
            [$this, 'render_contest_settings_meta_box'],
            'photo_contest',
            'normal',
            'high'
        );
    }

    /**
     * Render contest settings meta box.
     */
    public function render_contest_settings_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('voxel_photo_contest_save_meta', 'voxel_photo_contest_meta_nonce');

        // Get current values
        $start_date = get_post_meta($post->ID, '_contest_start_date', true);
        $end_date = get_post_meta($post->ID, '_contest_end_date', true);
        $voting_enabled = get_post_meta($post->ID, '_voting_enabled', true);
        $show_results = get_post_meta($post->ID, '_show_results', true);
        $max_submissions = get_post_meta($post->ID, '_max_submissions_per_user', true) ?: 1;
        
        ?>
        <style>
            .voxel-contest-field {
                margin-bottom: 15px;
            }
            .voxel-contest-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .voxel-contest-field input[type="text"],
            .voxel-contest-field input[type="number"],
            .voxel-contest-field select {
                width: 100%;
            }
            .voxel-contest-field-row {
                display: flex;
                gap: 20px;
            }
            .voxel-contest-field-col {
                flex: 1;
            }
        </style>
        
        <div class="voxel-contest-field-row">
            <div class="voxel-contest-field-col">
                <div class="voxel-contest-field">
                    <label for="contest_start_date"><?php _e('Start Date', 'voxel-photo-contests'); ?></label>
                    <input 
                        type="datetime-local" 
                        id="contest_start_date" 
                        name="contest_start_date" 
                        value="<?php echo esc_attr($start_date); ?>"
                    >
                </div>
            </div>
            
            <div class="voxel-contest-field-col">
                <div class="voxel-contest-field">
                    <label for="contest_end_date"><?php _e('End Date', 'voxel-photo-contests'); ?></label>
                    <input 
                        type="datetime-local" 
                        id="contest_end_date" 
                        name="contest_end_date" 
                        value="<?php echo esc_attr($end_date); ?>"
                    >
                </div>
            </div>
        </div>
        
        <div class="voxel-contest-field-row">
            <div class="voxel-contest-field-col">
                <div class="voxel-contest-field">
                    <label for="voting_enabled"><?php _e('Voting Enabled', 'voxel-photo-contests'); ?></label>
                    <select id="voting_enabled" name="voting_enabled">
                        <option value="yes" <?php selected($voting_enabled, 'yes'); ?>><?php _e('Yes', 'voxel-photo-contests'); ?></option>
                        <option value="no" <?php selected($voting_enabled, 'no'); ?>><?php _e('No', 'voxel-photo-contests'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="voxel-contest-field-col">
                <div class="voxel-contest-field">
                    <label for="show_results"><?php _e('Show Results', 'voxel-photo-contests'); ?></label>
                    <select id="show_results" name="show_results">
                        <option value="always" <?php selected($show_results, 'always'); ?>><?php _e('Always', 'voxel-photo-contests'); ?></option>
                        <option value="after_voting" <?php selected($show_results, 'after_voting'); ?>><?php _e('After Voting', 'voxel-photo-contests'); ?></option>
                        <option value="after_contest" <?php selected($show_results, 'after_contest'); ?>><?php _e('After Contest Ends', 'voxel-photo-contests'); ?></option>
                        <option value="never" <?php selected($show_results, 'never'); ?>><?php _e('Never', 'voxel-photo-contests'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="voxel-contest-field">
            <label for="max_submissions_per_user"><?php _e('Maximum Submissions Per User', 'voxel-photo-contests'); ?></label>
            <input 
                type="number" 
                id="max_submissions_per_user" 
                name="max_submissions_per_user" 
                value="<?php echo esc_attr($max_submissions); ?>"
                min="1"
            >
        </div>
        <?php
    }

    /**
     * Save meta box data.
     */
    public function save_meta_box_data($post_id) {
        // Check if nonce is set
        if (!isset($_POST['voxel_photo_contest_meta_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['voxel_photo_contest_meta_nonce'], 'voxel_photo_contest_save_meta')) {
            return;
        }

        // If this is an autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save contest settings
        if (isset($_POST['contest_start_date'])) {
            update_post_meta($post_id, '_contest_start_date', sanitize_text_field($_POST['contest_start_date']));
        }
        
        if (isset($_POST['contest_end_date'])) {
            update_post_meta($post_id, '_contest_end_date', sanitize_text_field($_POST['contest_end_date']));
        }
        
        if (isset($_POST['voting_enabled'])) {
            update_post_meta($post_id, '_voting_enabled', sanitize_text_field($_POST['voting_enabled']));
        }
        
        if (isset($_POST['show_results'])) {
            update_post_meta($post_id, '_show_results', sanitize_text_field($_POST['show_results']));
        }
        
        if (isset($_POST['max_submissions_per_user'])) {
            update_post_meta($post_id, '_max_submissions_per_user', intval($_POST['max_submissions_per_user']));
        }
    }

    /**
     * Register custom fields using CMB2 (if available).
     */
    public function register_contest_meta() {
        // Check if CMB2 is available
        if (!class_exists('CMB2')) {
            return;
        }

        $cmb = new_cmb2_box([
            'id'            => 'contest_additional_settings',
            'title'         => __('Additional Contest Settings', 'voxel-photo-contests'),
            'object_types'  => ['photo_contest'],
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true,
        ]);

        $cmb->add_field([
            'name'       => __('Voting Type', 'voxel-photo-contests'),
            'desc'       => __('Select the type of voting for this contest', 'voxel-photo-contests'),
            'id'         => '_voting_type',
            'type'       => 'select',
            'default'    => 'single',
            'options'    => [
                'single'    => __('Single Vote (Like)', 'voxel-photo-contests'),
                'multiple'  => __('Multiple Votes (User can vote for multiple entries)', 'voxel-photo-contests'),
                'rating'    => __('Star Rating', 'voxel-photo-contests'),
            ],
        ]);

        $cmb->add_field([
            'name'       => __('Who Can Vote', 'voxel-photo-contests'),
            'desc'       => __('Select who can vote in this contest', 'voxel-photo-contests'),
            'id'         => '_who_can_vote',
            'type'       => 'select',
            'default'    => 'logged_in',
            'options'    => [
                'logged_in'  => __('Logged in users only', 'voxel-photo-contests'),
                'anyone'     => __('Anyone (including guests)', 'voxel-photo-contests'),
            ],
        ]);

        $cmb->add_field([
            'name'       => __('Contest Rules', 'voxel-photo-contests'),
            'desc'       => __('Enter the rules for this contest', 'voxel-photo-contests'),
            'id'         => '_contest_rules',
            'type'       => 'wysiwyg',
            'options'    => [
                'textarea_rows' => 10,
            ],
        ]);

        $cmb->add_field([
            'name'       => __('Prizes', 'voxel-photo-contests'),
            'desc'       => __('Enter information about prizes for this contest', 'voxel-photo-contests'),
            'id'         => '_contest_prizes',
            'type'       => 'wysiwyg',
            'options'    => [
                'textarea_rows' => 5,
            ],
        ]);
    }
}

// Initialize fields
new Fields();