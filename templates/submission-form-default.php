<?php
/**
 * Default template for submission form
 *
 * @package Voxel_Photo_Contests
 */

defined('ABSPATH') || exit;

// Check if required data is available
if (!isset($submission_data) || !$submission_data) {
    return;
}

// Extract data
$contest_id = $submission_data['contest_id'];
$contest_data = $submission_data['contest_data'];

// Check if Voxel has a submission form already
$has_voxel_form = false;
if (class_exists('\Voxel\Post_Type')) {
    $submission_post_type = \Voxel\Post_Type::get('submission');
    if ($submission_post_type) {
        $form_page_id = $submission_post_type->get_templates()['form'] ?? null;
        if ($form_page_id) {
            $has_voxel_form = true;
            $form_url = add_query_arg(['contest_id' => $contest_id], get_permalink($form_page_id));
        }
    }
}

// If Voxel form exists, redirect to it
if ($has_voxel_form):
?>
    <div class="vpc-container">
        <div class="vpc-submission-redirect">
            <h3><?php echo esc_html__('Submit Your Photo', 'voxel-photo-contests'); ?></h3>
            <p><?php echo esc_html__('You will be redirected to the submission form where you can upload your photo.', 'voxel-photo-contests'); ?></p>
            <p><a href="<?php echo esc_url($form_url); ?>" class="vpc-form-submit"><?php echo esc_html__('Go to Submission Form', 'voxel-photo-contests'); ?></a></p>
        </div>
    </div>
<?php
    return;
endif;

// Show our custom form if no Voxel form exists
?>

<div class="vpc-container">
    <div class="vpc-submission-form">
        <h3><?php echo esc_html__('Submit Your Photo', 'voxel-photo-contests'); ?></h3>
        
        <?php if (isset($_GET['submission_error'])): ?>
            <div class="vpc-error">
                <?php echo esc_html(urldecode($_GET['submission_error'])); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['submission_success'])): ?>
            <div class="vpc-success">
                <?php echo esc_html__('Your photo has been submitted successfully! It will be reviewed before appearing in the contest.', 'voxel-photo-contests'); ?>
            </div>
        <?php else: ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="vpc-form">
                <?php wp_nonce_field('vpc_submit_photo', 'vpc_submission_nonce'); ?>
                <input type="hidden" name="action" value="vpc_submit_photo">
                <input type="hidden" name="contest_id" value="<?php echo esc_attr($contest_id); ?>">
                
                <div class="vpc-form-field">
                    <label for="vpc-submission-title"><?php echo esc_html__('Title', 'voxel-photo-contests'); ?></label>
                    <input type="text" name="submission_title" id="vpc-submission-title" required>
                </div>
                
                <div class="vpc-form-field">
                    <label for="vpc-submission-description"><?php echo esc_html__('Description (optional)', 'voxel-photo-contests'); ?></label>
                    <textarea name="submission_description" id="vpc-submission-description" rows="4"></textarea>
                </div>
                
                <div class="vpc-form-field">
                    <label for="vpc-submission-file"><?php echo esc_html__('Photo', 'voxel-photo-contests'); ?></label>
                    <input type="file" name="submission_file" id="vpc-submission-file" class="vpc-submission-file-input" accept="image/*" required>
                    <div id="vpc-image-preview" style="display:none; margin-top:10px;"></div>
                    <p class="vpc-form-hint"><?php echo esc_html__('Allowed formats: JPG, PNG, GIF. Maximum file size: 5MB.', 'voxel-photo-contests'); ?></p>
                </div>
                
                <?php if (is_user_logged_in()): ?>
                    <button type="submit" class="vpc-form-submit"><?php echo esc_html__('Submit Photo', 'voxel-photo-contests'); ?></button>
                <?php else: ?>
                    <div class="vpc-login-required">
                        <?php echo esc_html__('You must be logged in to submit a photo.', 'voxel-photo-contests'); ?>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>"><?php echo esc_html__('Log in', 'voxel-photo-contests'); ?></a>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Image preview
    $('#vpc-submission-file').on('change', function() {
        const input = this;
        const preview = $('#vpc-image-preview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.html('<img src="' + e.target.result + '" style="max-width:100%; max-height:300px;">');
                preview.show();
            };
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.hide();
        }
    });
});
</script>