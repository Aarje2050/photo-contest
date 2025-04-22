<?php
/**
 * Default template for displaying a contest
 *
 * @package Voxel_Photo_Contests
 */

defined('ABSPATH') || exit;

// Get contest data
if (!isset($contest_data) || !$contest_data) {
    return;
}
?>

<div class="vpc-container">
    <div class="vpc-contest-header">
        <h2><?php echo esc_html($contest_data['title']); ?></h2>
        
        <?php if ($contest_data['start_date'] || $contest_data['end_date']): ?>
            <div class="vpc-contest-dates">
                <?php if ($contest_data['start_date'] && $contest_data['end_date']): ?>
                    <?php printf(__('Contest runs from %s to %s', 'voxel-photo-contests'), 
                        esc_html($contest_data['start_date']), 
                        esc_html($contest_data['end_date'])
                    ); ?>
                <?php elseif ($contest_data['start_date']): ?>
                    <?php printf(__('Contest starts on %s', 'voxel-photo-contests'), esc_html($contest_data['start_date'])); ?>
                <?php elseif ($contest_data['end_date']): ?>
                    <?php printf(__('Contest ends on %s', 'voxel-photo-contests'), esc_html($contest_data['end_date'])); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="vpc-contest-status vpc-status-<?php echo esc_attr($contest_data['status']); ?>">
            <?php
            switch ($contest_data['status']) {
                case 'upcoming':
                    echo esc_html__('Upcoming', 'voxel-photo-contests');
                    break;
                case 'active':
                    echo esc_html__('Active', 'voxel-photo-contests');
                    break;
                case 'ended':
                    echo esc_html__('Ended', 'voxel-photo-contests');
                    break;
            }
            ?>
        </div>
    </div>
    
    <div class="vpc-contest-description">
        <?php echo wp_kses_post($contest_data['content']); ?>
    </div>
    
    <div class="vpc-contest-stats">
        <div class="vpc-contest-stat">
            <div class="vpc-stat-value"><?php echo esc_html($contest_data['submissions_count']); ?></div>
            <div class="vpc-stat-label"><?php echo esc_html__('Submissions', 'voxel-photo-contests'); ?></div>
        </div>
        
        <div class="vpc-contest-stat">
            <div class="vpc-stat-value"><?php echo esc_html($contest_data['votes_count']); ?></div>
            <div class="vpc-stat-label"><?php echo esc_html__('Votes', 'voxel-photo-contests'); ?></div>
        </div>
        
        <div class="vpc-contest-stat">
            <div class="vpc-stat-value"><?php echo esc_html($contest_data['unique_voters_count']); ?></div>
            <div class="vpc-stat-label"><?php echo esc_html__('Voters', 'voxel-photo-contests'); ?></div>
        </div>
        
        <?php if ($contest_data['voting_enabled']): ?>
            <div class="vpc-contest-stat">
                <div class="vpc-stat-value"><?php echo esc_html($contest_data['max_votes_per_user']); ?></div>
                <div class="vpc-stat-label"><?php echo esc_html__('Votes Per User', 'voxel-photo-contests'); ?></div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($contest_data['status'] !== 'ended'): ?>
        <div class="vpc-submission-button-container">
            <?php if ($contest_data['status'] === 'active'): ?>
                <a href="<?php echo esc_url(add_query_arg(['contest_id' => $contest_data['id']], get_permalink(get_page_by_path('submit-photo')))); ?>" class="vpc-form-submit">
                    <?php echo esc_html__('Submit Your Photo', 'voxel-photo-contests'); ?>
                </a>
            <?php elseif ($contest_data['status'] === 'upcoming'): ?>
                <p class="vpc-notice">
                    <?php printf(__('Submissions will open on %s', 'voxel-photo-contests'), esc_html($contest_data['start_date'])); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <h3><?php echo esc_html__('Submissions', 'voxel-photo-contests'); ?></h3>
    
    <?php
    // Display submissions gallery using the gallery shortcode
    echo do_shortcode(sprintf(
        '[photo_contest_gallery contest_id="%d" template="grid" columns="3" order="votes"]',
        $contest_data['id']
    ));
    ?>
    
    <?php if ($contest_data['votes_count'] > 0): ?>
        <h3><?php echo esc_html__('Leaderboard', 'voxel-photo-contests'); ?></h3>
        
        <?php
        // Get top submissions (limit to 10)
        $top_submissions = \VPC\Voting_System::instance()->get_top_submissions($contest_data['id'], 10);
        if (!empty($top_submissions)):
        ?>
            <div class="vpc-leaderboard">
                <ol class="vpc-leaderboard-list">
                    <?php foreach ($top_submissions as $submission): ?>
                        <?php
                        $submission_id = $submission->ID;
                        $thumbnail_id = get_post_thumbnail_id($submission_id);
                        $thumbnail = $thumbnail_id ? wp_get_attachment_image_src($thumbnail_id, 'thumbnail') : null;
                        $author_id = $submission->post_author;
                        $author = get_user_by('id', $author_id);
                        $vote_count = get_post_meta($submission_id, 'vpc_vote_count', true) ?: 0;
                        ?>
                        <li class="vpc-leaderboard-item">
                            <div class="vpc-leaderboard-rank"><?php echo esc_html($loop->index + 1); ?></div>
                            <?php if ($thumbnail): ?>
                                <div class="vpc-leaderboard-image">
                                    <img src="<?php echo esc_url($thumbnail[0]); ?>" alt="<?php echo esc_attr(get_the_title($submission_id)); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="vpc-leaderboard-content">
                                <h4 class="vpc-leaderboard-title">
                                    <a href="<?php echo esc_url(get_permalink($submission_id)); ?>">
                                        <?php echo esc_html(get_the_title($submission_id)); ?>
                                    </a>
                                </h4>
                                <?php if ($author): ?>
                                    <div class="vpc-leaderboard-author">
                                        <?php printf(__('by %s', 'voxel-photo-contests'), esc_html($author->display_name)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="vpc-leaderboard-votes">
                                <?php printf(_n('%s vote', '%s votes', $vote_count, 'voxel-photo-contests'), number_format_i18n($vote_count)); ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>