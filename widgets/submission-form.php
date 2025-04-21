<?php
namespace Voxel_Photo_Contests\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Submission_Form extends \Voxel\Widgets\Base_Widget {
    public function get_name() {
        return 'voxel-photo-contest-submission-form';
    }

    public function get_title() {
        return __('Photo Submission Form (VX)', 'voxel-photo-contests');
    }

    public function get_icon() {
        return 'la la-upload';
    }

    public function get_categories() {
        return ['voxel', 'basic'];
    }

    protected function register_controls() {
        // Content settings
        $this->start_controls_section(
            'voxel_submission_content',
            [
                'label' => __('Content', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'contest_id',
            [
                'label' => __('Contest', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_contests(),
                'default' => '',
                'label_block' => true,
            ]
        );

        $this->add_control(
            'allow_contest_selection',
            [
                'label' => __('Allow Contest Selection', 'voxel-photo-contests'),
                'description' => __('If enabled, user can select from available contests', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'voxel-photo-contests'),
                'label_off' => __('No', 'voxel-photo-contests'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'contest_id' => '',
                ],
            ]
        );

        $this->add_control(
            'form_title',
            [
                'label' => __('Form Title', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => __('Submit Your Photo', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'form_description',
            [
                'label' => __('Form Description', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Please complete the form below to enter the contest.', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'success_message',
            [
                'label' => __('Success Message', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Your photo has been submitted successfully!', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'redirect_after_submit',
            [
                'label' => __('Redirect After Submit', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'voxel-photo-contests'),
                'label_off' => __('No', 'voxel-photo-contests'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'redirect_url',
            [
                'label' => __('Redirect URL', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-link.com', 'voxel-photo-contests'),
                'show_external' => true,
                'default' => [
                    'url' => '',
                    'is_external' => false,
                    'nofollow' => false,
                ],
                'condition' => [
                    'redirect_after_submit' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style settings - Form container
        $this->start_controls_section(
            'voxel_submission_form_style',
            [
                'label' => __('Form Container', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'form_background',
            [
                'label' => __('Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'form_border_radius',
            [
                'label' => __('Border Radius', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'form_shadow',
                'label' => __('Box Shadow', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submission-form',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'form_border',
                'label' => __('Border', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submission-form',
            ]
        );

        $this->add_control(
            'form_padding',
            [
                'label' => __('Padding', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style settings - Typography
        $this->start_controls_section(
            'voxel_submission_typography',
            [
                'label' => __('Typography', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'form_title_color',
            [
                'label' => __('Form Title Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'form_title_typography',
                'label' => __('Form Title Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submission-title',
            ]
        );

        $this->add_control(
            'form_desc_color',
            [
                'label' => __('Form Description Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-description' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'form_desc_typography',
                'label' => __('Form Description Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submission-description',
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => __('Label Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form label' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'label' => __('Label Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submission-form label',
            ]
        );

        $this->end_controls_section();

        // Style settings - Form fields
        $this->start_controls_section(
            'voxel_submission_fields',
            [
                'label' => __('Form Fields', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'field_background',
            [
                'label' => __('Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form input[type="text"], {{WRAPPER}} .voxel-submission-form input[type="email"], {{WRAPPER}} .voxel-submission-form textarea, {{WRAPPER}} .voxel-submission-form select' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'field_text_color',
            [
                'label' => __('Text Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form input[type="text"], {{WRAPPER}} .voxel-submission-form input[type="email"], {{WRAPPER}} .voxel-submission-form textarea, {{WRAPPER}} .voxel-submission-form select' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'field_typography',
                'label' => __('Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submission-form input[type="text"], {{WRAPPER}} .voxel-submission-form input[type="email"], {{WRAPPER}} .voxel-submission-form textarea, {{WRAPPER}} .voxel-submission-form select',
            ]
        );

        $this->add_control(
            'field_border_radius',
            [
                'label' => __('Border Radius', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form input[type="text"], {{WRAPPER}} .voxel-submission-form input[type="email"], {{WRAPPER}} .voxel-submission-form textarea, {{WRAPPER}} .voxel-submission-form select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'field_border',
                'label' => __('Border', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submission-form input[type="text"], {{WRAPPER}} .voxel-submission-form input[type="email"], {{WRAPPER}} .voxel-submission-form textarea, {{WRAPPER}} .voxel-submission-form select',
            ]
        );

        $this->add_control(
            'field_padding',
            [
                'label' => __('Padding', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-submission-form input[type="text"], {{WRAPPER}} .voxel-submission-form input[type="email"], {{WRAPPER}} .voxel-submission-form textarea, {{WRAPPER}} .voxel-submission-form select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style settings - Submit button
        $this->start_controls_section(
            'voxel_submission_button',
            [
                'label' => __('Submit Button', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('button_styles');

        // Normal state
        $this->start_controls_tab(
            'button_normal',
            [
                'label' => __('Normal', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __('Text Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submit-button' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label' => __('Background Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submit-button' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'label' => __('Border', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submit-button',
            ]
        );

        $this->end_controls_tab();

        // Hover state
        $this->start_controls_tab(
            'button_hover',
            [
                'label' => __('Hover', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'button_text_color_hover',
            [
                'label' => __('Text Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submit-button:hover' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover',
            [
                'label' => __('Background Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submit-button:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'button_border_hover',
            [
                'label' => __('Border Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-submit-button:hover' => 'border-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'label' => __('Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-submit-button',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __('Border Radius', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-submit-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'button_padding',
            [
                'label' => __('Padding', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-submit-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo '<div class="voxel-alert voxel-alert-info">' . __('You must be logged in to submit a photo.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        $contest_id = $settings['contest_id'];
        
        // Handle allow_contest_selection
        if (empty($contest_id) && $settings['allow_contest_selection'] !== 'yes') {
            echo '<div class="voxel-alert voxel-alert-warning">' . __('Please select a contest in the widget settings.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        // If a contest_id is provided, verify it
        if (!empty($contest_id)) {
            $contest = get_post($contest_id);
            
            if (!$contest || $contest->post_type !== 'photo_contest') {
                echo '<div class="voxel-alert voxel-alert-error">' . __('Invalid contest selected.', 'voxel-photo-contests') . '</div>';
                return;
            }
            
            // Check if contest is active
            $contest_start = get_post_meta($contest->ID, '_contest_start_date', true);
            $contest_end = get_post_meta($contest->ID, '_contest_end_date', true);
            $current_time = current_time('mysql');
            
            if (($contest_start && $current_time < $contest_start) || 
                ($contest_end && $current_time > $contest_end)) {
                echo '<div class="voxel-alert voxel-alert-warning">' . __('This contest is not currently accepting submissions.', 'voxel-photo-contests') . '</div>';
                return;
            }
            
            // Check user submission limit
            $user_id = get_current_user_id();
            $max_submissions = get_post_meta($contest->ID, '_max_submissions_per_user', true) ?: 1;
            
            $existing_submissions = get_posts([
                'post_type' => 'photo_submission',
                'author' => $user_id,
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'voxel:relation:contest',
                        'value' => $contest->ID,
                        'compare' => '=',
                    ]
                ]
            ]);
            
            if (count($existing_submissions) >= $max_submissions) {
                echo '<div class="voxel-alert voxel-alert-warning">' . sprintf(__('You have reached the maximum number of submissions (%d) for this contest.', 'voxel-photo-contests'), $max_submissions) . '</div>';
                return;
            }
        }
        
        // If using Voxel's built-in form
        $post_type = \Voxel\Post_Type::get('photo_submission');
        
        if ($post_type) {
            // Use Voxel's built-in form
            $atts = [];
            
            if (!empty($contest_id)) {
                $atts['contest_id'] = $contest_id;
            }
            
            echo do_shortcode('[voxel_create_post post_type="photo_submission" ' . $this->build_shortcode_atts($atts) . ']');
            return;
        }
        
        // Otherwise, use our custom form
        ?>
        <div class="voxel-submission-form-container">
            <form id="voxel-submission-form-<?php echo esc_attr($this->get_id()); ?>" class="voxel-submission-form" method="post" enctype="multipart/form-data">
                <?php if (!empty($settings['form_title'])): ?>
                    <h2 class="voxel-submission-title"><?php echo esc_html($settings['form_title']); ?></h2>
                <?php endif; ?>
                
                <?php if (!empty($settings['form_description'])): ?>
                    <div class="voxel-submission-description"><?php echo wp_kses_post($settings['form_description']); ?></div>
                <?php endif; ?>
                
                <div class="voxel-form-field">
                    <label for="photo_title"><?php _e('Photo Title', 'voxel-photo-contests'); ?> <span class="required">*</span></label>
                    <input type="text" name="photo_title" id="photo_title" required>
                </div>
                
                <div class="voxel-form-field">
                    <label for="photo_description"><?php _e('Description', 'voxel-photo-contests'); ?></label>
                    <textarea name="photo_description" id="photo_description" rows="4"></textarea>
                </div>

                <div class="voxel-form-field">
                    <label for="photo_upload"><?php _e('Upload Photo', 'voxel-photo-contests'); ?> <span class="required">*</span></label>
                    <input type="file" name="photo_upload" id="photo_upload" accept="image/*" required>
                    <p class="field-description"><?php _e('Maximum file size: 5MB. Accepted formats: JPG, PNG, GIF', 'voxel-photo-contests'); ?></p>
                </div>
                
                <?php if (empty($contest_id) && $settings['allow_contest_selection'] === 'yes'): ?>
                    <div class="voxel-form-field">
                        <label for="contest_id"><?php _e('Select Contest', 'voxel-photo-contests'); ?> <span class="required">*</span></label>
                        <select name="contest_id" id="contest_id" required>
                            <option value=""><?php _e('Select a contest', 'voxel-photo-contests'); ?></option>
                            <?php
                            $active_contests = get_posts([
                                'post_type' => 'photo_contest',
                                'posts_per_page' => -1,
                                'post_status' => 'publish',
                                'meta_query' => [
                                    'relation' => 'AND',
                                    [
                                        'relation' => 'OR',
                                        [
                                            'key' => '_contest_start_date',
                                            'value' => current_time('mysql'),
                                            'compare' => '<=',
                                        ],
                                        [
                                            'key' => '_contest_start_date',
                                            'compare' => 'NOT EXISTS',
                                        ],
                                    ],
                                    [
                                        'relation' => 'OR',
                                        [
                                            'key' => '_contest_end_date',
                                            'value' => current_time('mysql'),
                                            'compare' => '>=',
                                        ],
                                        [
                                            'key' => '_contest_end_date',
                                            'compare' => 'NOT EXISTS',
                                        ],
                                    ],
                                ],
                            ]);
                            
                            foreach ($active_contests as $contest) {
                                // Check user submission limit
                                $user_id = get_current_user_id();
                                $max_submissions = get_post_meta($contest->ID, '_max_submissions_per_user', true) ?: 1;
                                
                                $existing_submissions = get_posts([
                                    'post_type' => 'photo_submission',
                                    'author' => $user_id,
                                    'posts_per_page' => -1,
                                    'meta_query' => [
                                        [
                                            'key' => 'voxel:relation:contest',
                                            'value' => $contest->ID,
                                            'compare' => '=',
                                        ]
                                    ]
                                ]);
                                
                                if (count($existing_submissions) < $max_submissions) {
                                    echo '<option value="' . esc_attr($contest->ID) . '">' . esc_html($contest->post_title) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="contest_id" value="<?php echo esc_attr($contest_id); ?>">
                <?php endif; ?>
                
                <?php wp_nonce_field('voxel_photo_submission', 'voxel_photo_submission_nonce'); ?>
                
                <div class="voxel-form-actions">
                    <button type="submit" class="voxel-submit-button"><?php _e('Submit Photo', 'voxel-photo-contests'); ?></button>
                </div>
            </form>
            
            <div class="voxel-submission-response" style="display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#voxel-submission-form-<?php echo esc_attr($this->get_id()); ?>').on('submit', function(e) {
                e.preventDefault();
                
                var form = $(this);
                var responseContainer = form.siblings('.voxel-submission-response');
                
                // Create FormData object
                var formData = new FormData(this);
                formData.append('action', 'voxel_photo_contest_submit');
                
                // Disable submit button and show loading state
                form.find('button[type="submit"]').prop('disabled', true).text('<?php _e('Submitting...', 'voxel-photo-contests'); ?>');
                
                // Send AJAX request
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            responseContainer.removeClass('voxel-alert-error').addClass('voxel-alert-success').html(response.data.message).show();
                            
                            // Reset form
                            form.trigger('reset');
                            
                            // Redirect if needed
                            <?php if ($settings['redirect_after_submit'] === 'yes' && !empty($settings['redirect_url']['url'])): ?>
                            setTimeout(function() {
                                window.location.href = '<?php echo esc_url($settings['redirect_url']['url']); ?>';
                            }, 2000);
                            <?php endif; ?>
                        } else {
                            // Show error message
                            responseContainer.removeClass('voxel-alert-success').addClass('voxel-alert-error').html(response.data.message).show();
                        }
                    },
                    error: function() {
                        // Show general error message
                        responseContainer.removeClass('voxel-alert-success').addClass('voxel-alert-error').html('<?php _e('An error occurred. Please try again.', 'voxel-photo-contests'); ?>').show();
                    },
                    complete: function() {
                        // Re-enable submit button
                        form.find('button[type="submit"]').prop('disabled', false).text('<?php _e('Submit Photo', 'voxel-photo-contests'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    private function get_contests() {
        $contests = get_posts([
            'post_type' => 'photo_contest',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);
        
        $options = ['' => __('Select a contest', 'voxel-photo-contests')];
        
        foreach ($contests as $contest) {
            $options[$contest->ID] = $contest->post_title;
        }
        
        return $options;
    }

    private function build_shortcode_atts($atts) {
        $shortcode_atts = '';
        
        foreach ($atts as $key => $value) {
            $shortcode_atts .= $key . '="' . esc_attr($value) . '" ';
        }
        
        return trim($shortcode_atts);
    }
}