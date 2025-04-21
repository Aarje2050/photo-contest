<?php
namespace Voxel_Photo_Contests;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Admin {
    public function __construct() {
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
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
        $voting_enabled = get_post_meta($post->ID, '_voting_enabled', true) ?: 'yes';
        
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
                        type="date" 
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
                        type="date" 
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
    }
}

// Initialize Admin
new Admin();