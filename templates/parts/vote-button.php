<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get variables from context
// $post_id - ID of the submission
// $contest_id - ID of the contest
// $has_voted - Boolean indicating if current user has voted
// $vote_count - Number of votes for this submission
// $voting_type - Type of voting (single, multiple, rating)

// Check if voting is enabled for this contest
$voting_enabled = get_post_meta($contest_id, '_voting_enabled', true);
if ($voting_enabled !== 'yes') {
    return;
}

// Check if contest is active
$contest_start = get_post_meta($contest_id, '_contest_start_date', true);
$contest_end = get_post_meta($contest_id, '_contest_end_date', true);
$current_time = current_time('mysql');

if (($contest_start && $current_time < $contest_start) || 
    ($contest_end && $current_time > $contest_end)) {
    // If contest is not active, just show the vote count
    if ($vote_count > 0) {
        echo '<div class="voxel-vote-count">';
        echo sprintf(_n('%s vote', '%s votes', $vote_count, 'voxel-photo-contests'), number_format_i18n($vote_count));
        echo '</div>';
    }
    return;
}

// Check who can vote
$who_can_vote = get_post_meta($contest_id, '_who_can_vote', true) ?: 'logged_in';
$can_vote = true;

if ($who_can_vote === 'logged_in' && !is_user_logged_in()) {
    $can_vote = false;
}

// Get voting type
$voting_type = get_post_meta($contest_id, '_voting_type', true) ?: 'single';

?>

<div class="voxel-vote-wrapper" data-post-id="<?php echo esc_attr($post_id); ?>" data-contest-id="<?php echo esc_attr($contest_id); ?>">
    <?php if ($voting_type === 'rating'): ?>
        <div class="voxel-star-rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="voxel-star <?php echo ($has_voted && $i <= $vote_value) ? 'voted' : ''; ?>" data-value="<?php echo $i; ?>">★</span>
            <?php endfor; ?>
        </div>
    <?php else: ?>
        <button type="button" class="voxel-vote-button <?php echo $has_voted ? 'voted' : ''; ?>" <?php echo $can_vote ? '' : 'disabled'; ?> data-post-id="<?php echo esc_attr($post_id); ?>" data-contest-id="<?php echo esc_attr($contest_id); ?>">
            <?php if ($has_voted): ?>
                <span class="voxel-vote-icon-voted">❤</span>
            <?php else: ?>
                <span class="voxel-vote-icon">♡</span>
            <?php endif; ?>
            <span class="voxel-vote-text">
                <?php
                if ($has_voted) {
                    _e('Voted', 'voxel-photo-contests');
                } else {
                    _e('Vote', 'voxel-photo-contests');
                }
                ?>
            </span>
            <?php if ($vote_count > 0): ?>
                <span class="voxel-vote-count"><?php echo number_format_i18n($vote_count); ?></span>
            <?php endif; ?>
        </button>
    <?php endif; ?>
    
    <?php if (!$can_vote): ?>
        <div class="voxel-login-prompt">
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">
                <?php _e('Log in to vote', 'voxel-photo-contests'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>