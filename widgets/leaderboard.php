<?php
namespace Voxel_Photo_Contests\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Leaderboard extends \Voxel\Widgets\Base_Widget {
    public function get_name() {
        return 'voxel-photo-contest-leaderboard';
    }

    public function get_title() {
        return __('Photo Contest Leaderboard (VX)', 'voxel-photo-contests');
    }

    public function get_icon() {
        return 'la la-trophy';
    }

    public function get_categories() {
        return ['voxel', 'basic'];
    }

    protected function register_controls() {
        // Content settings
        $this->start_controls_section(
            'voxel_leaderboard_content',
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
            'limit',
            [
                'label' => __('Number of entries to show', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 50,
            ]
        );

        $this->add_control(
            'show_thumbnail',
            [
                'label' => __('Show Thumbnail', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'voxel-photo-contests'),
                'label_off' => __('Hide', 'voxel-photo-contests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_author',
            [
                'label' => __('Show Author', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'voxel-photo-contests'),
                'label_off' => __('Hide', 'voxel-photo-contests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_vote_count',
            [
                'label' => __('Show Vote Count', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'voxel-photo-contests'),
                'label_off' => __('Hide', 'voxel-photo-contests'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style settings
        $this->start_controls_section(
            'voxel_leaderboard_style',
            [
                'label' => __('Leaderboard Style', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'board_background',
            [
                'label' => __('Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'board_border_radius',
            [
                'label' => __('Border Radius', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'board_shadow',
                'label' => __('Box Shadow', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-leaderboard',
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => __('Item Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-item' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'item_border_radius',
            [
                'label' => __('Item Border Radius', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_padding',
            [
                'label' => __('Item Padding', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_spacing',
            [
                'label' => __('Item Spacing', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-item + .voxel-leaderboard-item' => 'margin-top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Typography settings
        $this->start_controls_section(
            'voxel_leaderboard_typography',
            [
                'label' => __('Typography', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'rank_color',
            [
                'label' => __('Rank Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-rank' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'rank_typography',
                'label' => __('Rank Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-leaderboard-rank',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-leaderboard-title',
            ]
        );

        $this->add_control(
            'author_color',
            [
                'label' => __('Author Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-author' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'show_author' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'author_typography',
                'label' => __('Author Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-leaderboard-author',
                'condition' => [
                    'show_author' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'votes_color',
            [
                'label' => __('Votes Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-votes' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'show_vote_count' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'votes_typography',
                'label' => __('Votes Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-leaderboard-votes',
                'condition' => [
                    'show_vote_count' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Thumbnail Style
        $this->start_controls_section(
            'voxel_leaderboard_thumbnail',
            [
                'label' => __('Thumbnail', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_thumbnail' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'thumbnail_size',
            [
                'label' => __('Thumbnail Size', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 30,
                        'max' => 150,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 60,
                ],
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-thumbnail' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'thumbnail_radius',
            [
                'label' => __('Border Radius', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-leaderboard-thumbnail' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        if (empty($settings['contest_id'])) {
            echo '<div class="voxel-alert voxel-alert-warning">' . __('Please select a contest.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        $contest = get_post($settings['contest_id']);
        
        if (!$contest || $contest->post_type !== 'photo_contest') {
            echo '<div class="voxel-alert voxel-alert-error">' . __('Invalid contest selected.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        // Check if results should be shown
        $show_results = get_post_meta($contest->ID, '_show_results', true);
        $contest_end = get_post_meta($contest->ID, '_contest_end_date', true);
        $current_time = current_time('mysql');
        
        if ($show_results === 'never') {
            echo '<div class="voxel-alert voxel-alert-info">' . __('Results are not available for this contest.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        if ($show_results === 'after_contest' && $contest_end && $current_time < $contest_end) {
            echo '<div class="voxel-alert voxel-alert-info">' . __('Results will be available after the contest ends.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        // Get top voted posts
        $top_posts = \Voxel_Photo_Contests\Database::get_top_voted_posts($contest->ID, $settings['limit']);
        
        if (empty($top_posts)) {
            echo '<div class="voxel-alert voxel-alert-info">' . __('No votes have been cast in this contest yet.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        ?>
        <div class="voxel-leaderboard">
            <?php
            $rank = 1;
            foreach ($top_posts as $post_data) {
                $post_id = $post_data->post_id;
                $vote_count = $post_data->vote_count;
                
                // Basic post info
                $title = get_the_title($post_id);
                $permalink = get_permalink($post_id);
                $author_id = get_post_field('post_author', $post_id);
                $author_name = get_the_author_meta('display_name', $author_id);
                
                // Get photo
                $photo_field = get_post_meta($post_id, 'voxel:photo', true);
                $photo_url = '';
                
                if (is_array($photo_field) && !empty($photo_field[0])) {
                    $photo_url = wp_get_attachment_image_url($photo_field[0], 'thumbnail');
                } elseif (has_post_thumbnail($post_id)) {
                    $photo_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
                }
                
                ?>
                <div class="voxel-leaderboard-item">
                    <div class="voxel-leaderboard-rank">
                        #<?php echo esc_html($rank); ?>
                    </div>
                    
                    <?php if ($settings['show_thumbnail'] === 'yes' && $photo_url): ?>
                        <div class="voxel-leaderboard-thumbnail">
                            <a href="<?php echo esc_url($permalink); ?>">
                                <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($title); ?>">
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="voxel-leaderboard-info">
                        <div class="voxel-leaderboard-title">
                            <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                        </div>
                        
                        <?php if ($settings['show_author'] === 'yes'): ?>
                            <div class="voxel-leaderboard-author">
                                <?php _e('By', 'voxel-photo-contests'); ?> <?php echo esc_html($author_name); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($settings['show_vote_count'] === 'yes'): ?>
                        <div class="voxel-leaderboard-votes">
                            <?php printf(_n('%s vote', '%s votes', $vote_count, 'voxel-photo-contests'), number_format_i18n($vote_count)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php
                $rank++;
            }
            ?>
        </div>
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
}