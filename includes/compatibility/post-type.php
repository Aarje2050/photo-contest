<?php
namespace Voxel;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Minimal implementation of Voxel\Post_Type for compatibility
class Post_Type {
    protected static $instances = [];
    
    public static function get($post_type) {
        if (!isset(static::$instances[$post_type])) {
            static::$instances[$post_type] = new self($post_type);
        }
        return static::$instances[$post_type];
    }
    
    protected $post_type_key;
    
    public function __construct($post_type_key) {
        $this->post_type_key = $post_type_key;
    }
    
    public function get_key() {
        return $this->post_type_key;
    }
    
    public function get_templates($create = false) {
        return ['form' => null];
    }
}

// Minimal Widgets\Base_Widget class for compatibility
if (!class_exists('\\Voxel\\Widgets\\Base_Widget')) {
    namespace Voxel\Widgets;
    
    class Base_Widget extends \Elementor\Widget_Base {
        // Basic implementation
    }
}