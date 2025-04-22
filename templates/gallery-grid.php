<?php
/**
 * Grid gallery template for displaying contest submissions
 *
 * @package Voxel_Photo_Contests
 */

defined('ABSPATH') || exit;

// Check if required data is available
if (!isset($gallery_data) || !$gallery_data) {
    return;
}

// Extract data
$contest_id = $gallery_data['contest_id'];
$contest_data = $gallery_data['contest_data'];
$submissions = $gallery_data['submissions'];
$args = $gallery_data['args'];

// Set columns
$columns = isset($args['columns']) ? intval($args['columns']) : 3;
$columns_class = 'vpc-gallery-columns-' . $columns;
?>

<div class="vpc-gallery <?php echo esc_attr($columns_class); ?>">
    <?php foreach ($submissions as $submission): ?>
        <?php
        $submission_id = $submission->ID;
        $thumbnail_id = get_post_thumbnail_id($submission_id);
        $thumbnail = $thumbnail_id ? wp_get_attachment_image_src($thumbnail_id, 'large') : null;
        $full_image = $thumbnail_id ? wp_get_attachment_image_src($thumbnail_id, 'full') : null;
        $author_id = $submission->post_author;
        $author = get_user_by('id', $author_id);
        $vote_count = get_post_meta($submission_id, 'vpc_vote_count', true) ?: 0;
        
        // Skip if no image
        if (!$thumbnail) {
            continue;
        }
        ?>
        <div class="vpc-submission-card">
            <div class="vpc-submission-image">
                <a href="<?php echo esc_url(get_permalink($submission_id)); ?>">
                    <img src="<?php echo esc_url($thumbnail[0]); ?>" alt="<?php echo esc_attr(get_the_title($submission_id)); ?>">
                </a>
            </div>
            <div class="vpc-submission-content">
                <h3 class="vpc-submission-title">
                    <a href="<?php echo esc_url(get_permalink($submission_id)); ?>">
                        <?php echo esc_html(get_the_title($submission_id)); ?>
                    </a>
                </h3>
                
                <?php if ($author): ?>
                    <div class="vpc-submission-author">
                        <?php printf(__('by %s', 'voxel-photo-contests'), esc_html($author->display_name)); ?>
                    </div>
                <?php endif; ?>
                
                <?php
                // Display vote button/count
                echo \VPC\Voting_System::instance()->get_vote_button_html($submission_id);
                ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (empty($submissions)): ?>
    <p class="vpc-notice"><?php echo esc_html__('No submissions found for this contest.', 'voxel-photo-contests'); ?></p>
<?php endif; ?>