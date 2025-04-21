<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get variables from calling context
// $post - Current contest post
// $rules - Contest rules (HTML)
// $prizes - Contest prizes (HTML)
?>

<div class="voxel-contest-footer">
    <?php if (!empty($rules)): ?>
        <div class="voxel-contest-rules">
            <h3><?php _e('Contest Rules', 'voxel-photo-contests'); ?></h3>
            <div class="voxel-contest-rules-content">
                <?php echo wp_kses_post($rules); ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($prizes)): ?>
        <div class="voxel-contest-prizes">
            <h3><?php _e('Prizes', 'voxel-photo-contests'); ?></h3>
            <div class="voxel-contest-prizes-content">
                <?php echo wp_kses_post($prizes); ?>
            </div>
        </div>
    <?php endif; ?>
</div>