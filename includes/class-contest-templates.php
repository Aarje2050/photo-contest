<?php
namespace VPC;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Contest Templates
 * 
 * Handles templates for displaying contests and submissions
 */
class Contest_Templates {

    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add Voxel template parts
        add_filter('voxel/template-parts', [$this, 'add_template_parts']);
        
        // Add CSS classes to submission and contest posts
        add_filter('post_class', [$this, 'add_post_classes'], 10, 3);
    }
    
    /**
     * Add template parts for Voxel
     */
    public function add_template_parts($template_parts) {
        // Add template parts for contest and submission views
        $template_parts['contest-grid'] = [
            'label' => __('Contest Grid', 'voxel-photo-contests'),
            'path' => VPC_TEMPLATES_DIR . 'parts/contest-grid.php',
        ];
        
        $template_parts['contest-submission-form'] = [
            'label' => __('Contest Submission Form', 'voxel-photo-contests'),
            'path' => VPC_TEMPLATES_DIR . 'parts/submission-form.php',
        ];
        
        $template_parts['contest-gallery'] = [
            'label' => __('Contest Gallery', 'voxel-photo-contests'),
            'path' => VPC_TEMPLATES_DIR . 'parts/gallery.php',
        ];
        
        $template_parts['contest-leaderboard'] = [
            'label' => __('Contest Leaderboard', 'voxel-photo-contests'),
            'path' => VPC_TEMPLATES_DIR . 'parts/leaderboard.php',
        ];
        
        return $template_parts;
    }
    
    /**
     * Add CSS classes to post
     */
    public function add_post_classes($classes, $class, $post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return $classes;
        }
        
        // Add classes to submission posts
        if ($post->post_type === 'submission') {
            $classes[] = 'vpc-submission';
            
            // Add vote count class
            $vote_count = get_post_meta($post_id, 'vpc_vote_count', true) ?: 0;
            $classes[] = 'vpc-votes-' . $vote_count;
            
            // Add contest classes
            $contest_id = get_post_meta($post_id, 'vpc_contest_id', true);
            if ($contest_id) {
                $classes[] = 'vpc-contest-' . $contest_id;
            }
        }
        
        // Add classes to contest posts
        if ($post->post_type === 'contest') {
            $classes[] = 'vpc-contest';
            
            // Add status class
            $voting_system = Voting_System::instance();
            $dates = $voting_system->get_contest_dates($post_id);
            $now = time();
            
            if ($dates['start'] && $now < $dates['start']) {
                $classes[] = 'vpc-contest-upcoming';
            } else if ($dates['end'] && $now > $dates['end']) {
                $classes[] = 'vpc-contest-ended';
            } else {
                $classes[] = 'vpc-contest-active';
            }
        }
        
        return $classes;
    }
    
    /**
     * Render a contest
     */
    public function render_contest($atts) {
        $contest_id = isset($atts['id']) ? intval($atts['id']) : 0;
        
        if (!$contest_id) {
            return '<p class="vpc-error">' . __('Contest ID is required.', 'voxel-photo-contests') . '</p>';
        }
        
        $contest = get_post($contest_id);
        if (!$contest || $contest->post_type !== 'contest') {
            return '<p class="vpc-error">' . __('Invalid contest ID.', 'voxel-photo-contests') . '</p>';
        }
        
        $template = isset($atts['template']) ? sanitize_text_field($atts['template']) : 'default';
        $template_file = VPC_TEMPLATES_DIR . 'contest-' . $template . '.php';
        
        // Fallback to default template if specified template doesn't exist
        if (!file_exists($template_file)) {
            $template_file = VPC_TEMPLATES_DIR . 'contest-default.php';
        }
        
        // Get contest data
        $contest_data = Contest_Integration::instance()->get_contest_data($contest_id);
        
        ob_start();
        include $template_file;
        return ob_get_clean();
    }
    
    /**
     * Render submission form
     */
    public function render_submission_form($atts) {
        $contest_id = isset($atts['contest_id']) ? intval($atts['contest_id']) : 0;
        
        if (!$contest_id) {
            return '<p class="vpc-error">' . __('Contest ID is required.', 'voxel-photo-contests') . '</p>';
        }
        
        $contest = get_post($contest_id);
        if (!$contest || $contest->post_type !== 'contest') {
            return '<p class="vpc-error">' . __('Invalid contest ID.', 'voxel-photo-contests') . '</p>';
        }
        
        $template = isset($atts['template']) ? sanitize_text_field($atts['template']) : 'default';
        $template_file = VPC_TEMPLATES_DIR . 'submission-form-' . $template . '.php';
        
        // Fallback to default template if specified template doesn't exist
        if (!file_exists($template_file)) {
            $template_file = VPC_TEMPLATES_DIR . 'submission-form-default.php';
        }
        
        // Check if contest is active for submissions
        $contest_data = Contest_Integration::instance()->get_contest_data($contest_id);
        
        // Verify user is logged in (if required)
        $require_login = get_option('vpc_require_login_for_submissions', 'yes');
        if ($require_login === 'yes' && !is_user_logged_in()) {
            return $this->get_login_required_message('submission');
        }
        
        // Check if submissions are open
        if ($contest_data['status'] === 'ended') {
            return '<p class="vpc-notice">' . __('This contest has ended. Submissions are no longer accepted.', 'voxel-photo-contests') . '</p>';
        }
        
        // Check if submissions are open yet
        if ($contest_data['status'] === 'upcoming') {
            return sprintf(
                '<p class="vpc-notice">' . __('This contest has not started yet. Submissions will open on %s.', 'voxel-photo-contests') . '</p>',
                $contest_data['start_date']
            );
        }
        
        // Pass data to template
        $submission_data = [
            'contest_id' => $contest_id,
            'contest_data' => $contest_data,
        ];
        
        ob_start();
        include $template_file;
        return ob_get_clean();
    }
    
    /**
     * Render gallery of submissions
     */
    public function render_gallery($atts) {
        $contest_id = isset($atts['contest_id']) ? intval($atts['contest_id']) : 0;
        
        if (!$contest_id) {
            return '<p class="vpc-error">' . __('Contest ID is required.', 'voxel-photo-contests') . '</p>';
        }
        
        $contest = get_post($contest_id);
        if (!$contest || $contest->post_type !== 'contest') {
            return '<p class="vpc-error">' . __('Invalid contest ID.', 'voxel-photo-contests') . '</p>';
        }
        
        $template = isset($atts['template']) ? sanitize_text_field($atts['template']) : 'grid';
        $template_file = VPC_TEMPLATES_DIR . 'gallery-' . $template . '.php';
        
        // Fallback to default template if specified template doesn't exist
        if (!file_exists($template_file)) {
            $template_file = VPC_TEMPLATES_DIR . 'gallery-grid.php';
        }
        
        // Get contest data
        $contest_data = Contest_Integration::instance()->get_contest_data($contest_id);
        
        // Parse additional args
        $args = [
            'columns' => isset($atts['columns']) ? intval($atts['columns']) : 3,
            'order' => isset($atts['order']) ? sanitize_text_field($atts['order']) : 'votes',
        ];
        
        // Get submissions
        $order_args = [];
        if ($args['order'] === 'votes') {
            $order_args['meta_key'] = 'vpc_vote_count';
            $order_args['orderby'] = 'meta_value_num';
            $order_args['order'] = 'DESC';
        } elseif ($args['order'] === 'date') {
            $order_args['orderby'] = 'date';
            $order_args['order'] = 'DESC';
        } elseif ($args['order'] === 'random') {
            $order_args['orderby'] = 'rand';
        }
        
        $submissions = Contest_Integration::instance()->get_contest_submissions($contest_id, $order_args);
        
        // Check if there are submissions
        if (empty($submissions)) {
            return '<p class="vpc-notice">' . __('No submissions found for this contest.', 'voxel-photo-contests') . '</p>';
        }
        
        // Pass data to template
        $gallery_data = [
            'contest_id' => $contest_id,
            'contest_data' => $contest_data,
            'submissions' => $submissions,
            'args' => $args,
        ];
        
        ob_start();
        include $template_file;
        return ob_get_clean();
    }
    
    /**
     * Get login required message
     */
    private function get_login_required_message($action_type = 'vote') {
        $message = '';
        
        switch ($action_type) {
            case 'vote':
                $message = __('You must be logged in to vote.', 'voxel-photo-contests');
                break;
            case 'submission':
                $message = __('You must be logged in to submit an entry.', 'voxel-photo-contests');
                break;
            default:
                $message = __('You must be logged in to continue.', 'voxel-photo-contests');
        }
        
        $login_url = wp_login_url(get_permalink());
        
        return sprintf(
            '<p class="vpc-login-required">%s <a href="%s">%s</a></p>',
            $message,
            esc_url($login_url),
            __('Log in', 'voxel-photo-contests')
        );
    }
    
    /**
     * Render a template file with data
     */
    public function render_template($template_file, $data = []) {
        if (!file_exists($template_file)) {
            return '';
        }
        
        // Extract data to make it available in template
        extract($data);
        
        ob_start();
        include $template_file;
        return ob_get_clean();
    }
}