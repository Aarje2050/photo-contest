<?php
/**
 * The template for displaying a single photo submission
 *
 * @package Voxel_Photo_Contests
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

while (have_posts()) :
    the_post();
    
    // Get contest data
    $contest_id = get_post_meta(get_the_ID(), 'voxel:relation:contest', true);
    $contest = get_post($contest_id);
    
    // Get photo
    $photo_field = get_post_meta(get_the_ID(), 'voxel:photo', true);
    $photo_url = '';
    
    if (is_array($photo_field) && !empty($photo_field[0])) {
        $photo_url = wp_get_attachment_image_url($photo_field[0], 'full');
    } elseif (has_post_thumbnail()) {
        $photo_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
    }
    ?>
    
    <div class="voxel-submission-single">
        <div class="voxel-submission-header">
            <?php if ($contest) : ?>
                <div class="voxel-submission-contest">
                    <span><?php _e('Entry in contest:', 'voxel-photo-contests'); ?></span>
                    <a href="<?php echo get_permalink($contest); ?>"><?php echo get_the_title($contest); ?></a>
                </div>
            <?php endif; ?>
            
            <h1 class="voxel-submission-title"><?php the_title(); ?></h1>
            
            <div class="voxel-submission-meta">
                <div class="voxel-submission-author">
                    <?php _e('By', 'voxel-photo-contests'); ?> <?php the_author(); ?>
                </div>
                
                <div class="voxel-submission-date">
                    <?php echo get_the_date(); ?>
                </div>
            </div>
            
            <?php if ($contest) : ?>
                <div class="voxel-submission-voting">
                    <?php echo \Voxel_Photo_Contests\Templates::get_vote_button(get_the_ID(), $contest_id); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="voxel-submission-content">
            <?php if ($photo_url) : ?>
                <div class="voxel-submission-photo">
                    <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                </div>
            <?php endif; ?>
            
            <div class="voxel-submission-description">
                <?php the_content(); ?>
            </div>
        </div>
        
        <?php if ($contest) : ?>
            <div class="voxel-submission-footer">
                <a href="<?php echo get_permalink($contest); ?>" class="voxel-btn voxel-btn-secondary">
                    <?php _e('Back to Contest', 'voxel-photo-contests'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
<?php endwhile;

get_footer();