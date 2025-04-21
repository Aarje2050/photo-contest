<?php
namespace Voxel_Photo_Contests;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Post_Types {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action('init', [$this, 'register_post_types']);
        
        // Hook into Voxel's post type registration
        add_action('voxel/post-types/register', [$this, 'register_voxel_post_types']);
    }

    /**
     * Register custom post types.
     */
    public function register_post_types() {
        // Contest post type
        register_post_type('photo_contest', [
            'labels' => [
                'name'                  => _x('Photo Contests', 'Post Type General Name', 'voxel-photo-contests'),
                'singular_name'         => _x('Photo Contest', 'Post Type Singular Name', 'voxel-photo-contests'),
                'menu_name'             => __('Photo Contests', 'voxel-photo-contests'),
                'name_admin_bar'        => __('Photo Contest', 'voxel-photo-contests'),
                'archives'              => __('Contest Archives', 'voxel-photo-contests'),
                'attributes'            => __('Contest Attributes', 'voxel-photo-contests'),
                'all_items'             => __('All Contests', 'voxel-photo-contests'),
                'add_new_item'          => __('Add New Contest', 'voxel-photo-contests'),
                'add_new'               => __('Add New', 'voxel-photo-contests'),
                'new_item'              => __('New Contest', 'voxel-photo-contests'),
                'edit_item'             => __('Edit Contest', 'voxel-photo-contests'),
                'update_item'           => __('Update Contest', 'voxel-photo-contests'),
                'view_item'             => __('View Contest', 'voxel-photo-contests'),
                'view_items'            => __('View Contests', 'voxel-photo-contests'),
                'search_items'          => __('Search Contest', 'voxel-photo-contests'),
            ],
            'supports'              => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-camera',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
            'rest_base'             => 'photo-contests',
        ]);
    }

    /**
     * Register Voxel post types for photo contest submissions.
     */
    public function register_voxel_post_types($post_types) {
        // Add the photo submission post type to Voxel
        $post_types->create([
            'settings' => [
                'key' => 'photo_submission',
                'label' => 'Photo Submission',
                'singular' => 'Photo',
                'plural' => 'Photos',
                'icon' => 'la-camera',
                'timeline' => [
                    'enabled' => true,
                    'wall_visibility' => 'public',
                ],
                'submissions' => [
                    'enabled' => true,
                    'update_status' => ['publish', 'pending'],
                    'deletable' => true,
                ],
            ],
            'fields' => [
                'title' => [
                    'type' => 'title',
                    'label' => 'Title',
                    'placeholder' => 'Enter photo title',
                ],
                'description' => [
                    'type' => 'description',
                    'label' => 'Description',
                    'placeholder' => 'Describe your photo',
                ],
                'photo' => [
                    'type' => 'image',
                    'label' => 'Photo',
                    'max-count' => 1,
                    'required' => true,
                ],
                'contest' => [
                    'type' => 'post-relation',
                    'label' => 'Contest',
                    'post-type' => 'photo_contest',
                    'required' => true,
                ],
                'submission_date' => [
                    'type' => 'date',
                    'label' => 'Submission Date',
                    'default' => '@now',
                ],
            ],
            'filters' => [
                'search' => [
                    'type' => 'keywords',
                    'label' => 'Search',
                    'placeholder' => 'Search photos',
                ],
                'contest' => [
                    'type' => 'relations',
                    'label' => 'Contest',
                    'post-type' => 'photo_contest',
                ],
                'sort' => [
                    'type' => 'order-by',
                    'label' => 'Sort by',
                    'choices' => [
                        'latest' => 'Latest',
                        'most_votes' => 'Most votes',
                    ],
                ],
            ],
        ]);
    }
}

// Initialize post types
new Post_Types();