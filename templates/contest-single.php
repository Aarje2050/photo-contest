<?php
/**
 * The template for displaying a single photo contest
 *
 * @package Voxel_Photo_Contests
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header();

while (have_posts()) :
    the_post();
    
    // Call contest header action
    do_action('voxel_photo_contests_before_content');
    ?>
    
    <div class="voxel-contest-single">
        <div class="voxel-contest-content">
            <h1 class="voxel-contest-title"><?php the_title(); ?></h1>
            
            <div class="voxel-contest-description">
                <?php the_content(); ?>
            </div>
            
            <?php
            // Display submissions
            $args = [
                'post_type' => 'photo_submission',
                'posts_per_page' => 12,
                'meta_query' => [
                    [
                        'key' => 'voxel:relation:contest',
                        'value' => get_the_ID(),
                        'compare' => '=',
                    ]
                ]
            ];
            
            $submissions = new WP_Query($args);
            
            if ($submissions->have_posts()) :
                ?>
                <div class="voxel-contest-submissions">
                    <h2><?php _e('Contest Entries', 'voxel-photo-contests'); ?></h2>
                    
                    <div class="voxel-photo-grid voxel-grid-3">
                        <?php while ($submissions->have_posts()) : $submissions->the_post(); ?>
                            <div class="voxel-photo-item">
                                <div class="voxel-photo-image">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php
                                        // Get photo
                                        $photo_field = get_post_meta(get_the_ID(), 'voxel:photo', true);
                                        $photo_url = '';
                                        
                                        if (is_array($photo_field) && !empty($photo_field[0])) {
                                            $photo_url = wp_get_attachment_image_url($photo_field[0], 'large');
                                        } elseif (has_post_thumbnail()) {
                                            $photo_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
                                        }
                                        
                                        if ($photo_url) {
                                            echo '<img src="' . esc_url($photo_url) . '" alt="' . esc_attr(get_the_title()) . '">';
                                        } else {
                                            echo '<div class="voxel-no-image">' . __('No Image', 'voxel-photo-contests') . '</div>';
                                        }
                                        ?>
                                    </a>
                                </div>
                                
                                <div class="voxel-photo-content">
                                    <h3 class="voxel-photo-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="voxel-photo-author">
                                        <?php _e('By', 'voxel-photo-contests'); ?> <?php the_author(); ?>
                                    </div>
                                    
                                    <div class="voxel-photo-voting">
                                        <?php echo \Voxel_Photo_Contests\Templates::get_vote_button(get_the_ID(), get_queried_object_id()); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if ($submissions->max_num_pages > 1) : ?>
                        <div class="voxel-pagination">
                            <?php
                            echo paginate_links([
                                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                                'format' => '?paged=%#%',
                                'current' => max(1, get_query_var('paged')),
                                'total' => $submissions->max_num_pages,
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php
            else :
                echo '<div class="voxel-alert voxel-alert-info">' . __('No submissions yet. Be the first to enter this contest!', 'voxel-photo-contests') . '</div>';
            endif;
            
            wp_reset_postdata();
            ?>
        </div>
    </div>
    
    <?php
    // Call contest footer action
    do_action('voxel_photo_contests_after_content');

endwhile;

get_footer();