<?php
namespace Voxel_Photo_Contests\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Contest_Feed extends \Voxel\Widgets\Base_Widget {
    public function get_name() {
        return 'voxel-photo-contest-feed';
    }

    public function get_title() {
        return __('Photo Contest Feed (VX)', 'voxel-photo-contests');
    }

    public function get_icon() {
        return 'la la-camera';
    }

    public function get_categories() {
        return ['voxel', 'basic'];
    }

    protected function register_controls() {
        // Content settings
        $this->start_controls_section(
            'voxel_contest_feed_content',
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
            'display_mode',
            [
                'label' => __('Display Mode', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'grid',
                'options' => [
                    'grid' => __('Grid', 'voxel-photo-contests'),
                    'masonry' => __('Masonry', 'voxel-photo-contests'),
                    'carousel' => __('Carousel', 'voxel-photo-contests'),
                ],
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Entries per page', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 50,
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                ],
                'condition' => [
                    'display_mode' => ['grid', 'masonry'],
                ],
            ]
        );

        $this->add_control(
            'order_by',
            [
                'label' => __('Order by', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => __('Date', 'voxel-photo-contests'),
                    'votes' => __('Vote count', 'voxel-photo-contests'),
                    'title' => __('Title', 'voxel-photo-contests'),
                    'random' => __('Random', 'voxel-photo-contests'),
                ],
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => __('Order', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'DESC',
                'options' => [
                    'DESC' => __('Descending', 'voxel-photo-contests'),
                    'ASC' => __('Ascending', 'voxel-photo-contests'),
                ],
                'condition' => [
                    'order_by!' => 'random',
                ],
            ]
        );

        $this->add_control(
            'show_voting',
            [
                'label' => __('Show Voting UI', 'voxel-photo-contests'),
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

        $this->end_controls_section();

        // Style settings
        $this->start_controls_section(
            'voxel_contest_feed_style',
            [
                'label' => __('Feed Style', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
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
                    'size' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .voxel-photo-feed' => 'grid-gap: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .voxel-photo-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .voxel-photo-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} 0 0;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'item_box_shadow',
                'label' => __('Item Box Shadow', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-photo-item',
            ]
        );

        $this->add_control(
            'item_background',
            [
                'label' => __('Item Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-photo-item' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();

        // Typography settings
        $this->start_controls_section(
            'voxel_contest_feed_typography',
            [
                'label' => __('Typography', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-photo-title',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-photo-title' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'author_typography',
                'label' => __('Author Typography', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-photo-author',
                'condition' => [
                    'show_author' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'author_color',
            [
                'label' => __('Author Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-photo-author' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'show_author' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Vote button style
        $this->start_controls_section(
            'voxel_contest_vote_button',
            [
                'label' => __('Vote Button', 'voxel-photo-contests'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_voting' => 'yes',
                ],
            ]
        );

        $this->start_controls_tabs('vote_button_tabs');

        // Normal state
        $this->start_controls_tab(
            'vote_button_normal',
            [
                'label' => __('Normal', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'vote_button_color',
            [
                'label' => __('Button Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'vote_button_background',
            [
                'label' => __('Button Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'vote_button_border',
                'label' => __('Border', 'voxel-photo-contests'),
                'selector' => '{{WRAPPER}} .voxel-vote-button',
            ]
        );

        $this->add_control(
            'vote_button_border_radius',
            [
                'label' => __('Border Radius', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover state
        $this->start_controls_tab(
            'vote_button_hover',
            [
                'label' => __('Hover', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'vote_button_color_hover',
            [
                'label' => __('Button Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button:hover' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'vote_button_background_hover',
            [
                'label' => __('Button Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button:hover' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'vote_button_border_hover',
            [
                'label' => __('Border Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button:hover' => 'border-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_tab();

        // Voted state
        $this->start_controls_tab(
            'vote_button_voted',
            [
                'label' => __('Voted', 'voxel-photo-contests'),
            ]
        );

        $this->add_control(
            'vote_button_color_voted',
            [
                'label' => __('Button Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button.voted' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'vote_button_background_voted',
            [
                'label' => __('Button Background', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button.voted' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'vote_button_border_voted',
            [
                'label' => __('Border Color', 'voxel-photo-contests'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .voxel-vote-button.voted' => 'border-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

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
        
        // Build query args
        $args = [
            'post_type' => 'photo_submission',
            'posts_per_page' => $settings['posts_per_page'],
            'meta_query' => [
                [
                    'key' => 'voxel:relation:contest',
                    'value' => $contest->ID,
                    'compare' => '=',
                ]
            ]
        ];
        
        // Handle ordering
        if ($settings['order_by'] === 'votes') {
            $args['meta_key'] = '_voxel_photo_contest_vote_count';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = $settings['order'];
        } elseif ($settings['order_by'] === 'random') {
            $args['orderby'] = 'rand';
        } else {
            $args['orderby'] = $settings['order_by'];
            $args['order'] = $settings['order'];
        }
        
        $submissions = new \WP_Query($args);
        
        if (!$submissions->have_posts()) {
            echo '<div class="voxel-alert voxel-alert-info">' . __('No submissions yet for this contest.', 'voxel-photo-contests') . '</div>';
            return;
        }
        
        // Classes for layout
        $container_class = 'voxel-photo-feed';
        
        if ($settings['display_mode'] === 'grid') {
            $container_class .= ' voxel-photo-grid';
            $container_class .= ' voxel-grid-' . $settings['columns'];
        } elseif ($settings['display_mode'] === 'masonry') {
            $container_class .= ' voxel-photo-masonry';
            $container_class .= ' voxel-masonry-' . $settings['columns'];
        } elseif ($settings['display_mode'] === 'carousel') {
            $container_class .= ' voxel-photo-carousel';
        }
        
        ?>
        <div class="voxel-photo-contest-feed">
            <?php if ($settings['display_mode'] === 'carousel'): ?>
                <div class="swiper-container">
                    <div class="swiper-wrapper <?php echo esc_attr($container_class); ?>">
                        <?php while ($submissions->have_posts()): $submissions->the_post(); ?>
                            <div class="swiper-slide">
                                <?php $this->render_photo_item(get_the_ID(), $contest->ID, $settings); ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            <?php else: ?>
                <div class="<?php echo esc_attr($container_class); ?>">
                    <?php while ($submissions->have_posts()): $submissions->the_post(); ?>
                        <?php $this->render_photo_item(get_the_ID(), $contest->ID, $settings); ?>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        wp_reset_postdata();
        
        if ($settings['display_mode'] === 'carousel') {
            ?>
            <script>
            jQuery(document).ready(function($) {
                new Swiper('.voxel-photo-contest-feed .swiper-container', {
                    slidesPerView: 3,
                    spaceBetween: 30,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    breakpoints: {
                        320: {
                            slidesPerView: 1,
                            spaceBetween: 10
                        },
                        640: {
                            slidesPerView: 2,
                            spaceBetween: 20
                        },
                        1024: {
                            slidesPerView: 3,
                            spaceBetween: 30
                        }
                    }
                });
            });
            </script>
            <?php
        }
    }

    protected function render_photo_item($post_id, $contest_id, $settings) {
        $title = get_the_title($post_id);
        $permalink = get_permalink($post_id);
        $author_id = get_post_field('post_author', $post_id);
        $author_name = get_the_author_meta('display_name', $author_id);
        
        // Get photo
        $photo_field = get_post_meta($post_id, 'voxel:photo', true);
        $photo_url = '';
        
        if (is_array($photo_field) && !empty($photo_field[0])) {
            $photo_url = wp_get_attachment_image_url($photo_field[0], 'large');
        } elseif (has_post_thumbnail($post_id)) {
            $photo_url = get_the_post_thumbnail_url($post_id, 'large');
        }
        
        ?>
        <div class="voxel-photo-item">
            <div class="voxel-photo-image">
                <a href="<?php echo esc_url($permalink); ?>">
                    <?php if ($photo_url): ?>
                        <img src="<?php echo esc_url($photo_url); ?>" alt="<?php echo esc_attr($title); ?>">
                    <?php else: ?>
                        <div class="voxel-no-image"><?php _e('No Image', 'voxel-photo-contests'); ?></div>
                    <?php endif; ?>
                </a>
            </div>
            
            <div class="voxel-photo-content">
                <h3 class="voxel-photo-title">
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a>
                </h3>
                
                <?php if ($settings['show_author'] === 'yes'): ?>
                    <div class="voxel-photo-author">
                        <?php _e('By', 'voxel-photo-contests'); ?> <?php echo esc_html($author_name); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($settings['show_voting'] === 'yes'): ?>
                    <div class="voxel-photo-voting">
                        <?php echo \Voxel_Photo_Contests\Templates::get_vote_button($post_id, $contest_id); ?>
                    </div>
                <?php endif; ?>
            </div>
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