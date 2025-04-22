<?php
namespace VPC;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Voting System
 * 
 * Handles all voting-related functionality
 */
class Voting_System {
    
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
        // AJAX handlers
        add_action('wp_ajax_vpc_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_vpc_vote', [$this, 'handle_vote_nopriv']);
        
        // Display vote button on submission posts
        add_action('voxel/submission/after_content', [$this, 'display_vote_button']);
        
        // Add vote count to submission search results
        add_filter('voxel/post/prepared_data', [$this, 'add_vote_count_to_post_data'], 10, 2);
    }
    
    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vpc_votes';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            contest_id bigint(20) NOT NULL DEFAULT 0,
            user_id bigint(20) NOT NULL DEFAULT 0,
            vote_value int(11) NOT NULL DEFAULT 1,
            vote_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY vote_once (post_id, user_id),
            KEY contest_id (contest_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Handle vote AJAX request for logged-in users
     */
    public function handle_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vpc-vote-nonce')) {
            wp_send_json_error(['message' => __('Invalid security token', 'voxel-photo-contests')]);
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid submission ID', 'voxel-photo-contests')]);
        }
        
        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('You must be logged in to vote', 'voxel-photo-contests')]);
        }
        
        // Process vote
        $result = $this->process_vote($post_id, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Vote recorded successfully', 'voxel-photo-contests'),
            'count' => $result['count']
        ]);
    }
    
    /**
     * Handle vote AJAX request for non-logged-in users
     */
    public function handle_vote_nopriv() {
        // Check if guest voting is allowed in settings
        $allow_guest_voting = get_option('vpc_allow_guest_voting', 'no');
        
        if ($allow_guest_voting !== 'yes') {
            wp_send_json_error(['message' => __('You must be logged in to vote', 'voxel-photo-contests')]);
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vpc-vote-nonce')) {
            wp_send_json_error(['message' => __('Invalid security token', 'voxel-photo-contests')]);
        }
        
        // Get post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid submission ID', 'voxel-photo-contests')]);
        }
        
        // Use IP address as user identifier
        $user_id = 'ip_' . md5($_SERVER['REMOTE_ADDR']);
        
        // Process vote
        $result = $this->process_vote($post_id, $user_id, true);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Vote recorded successfully', 'voxel-photo-contests'),
            'count' => $result['count']
        ]);
    }
    
    /**
     * Process a vote
     *
     * @param int $post_id The submission post ID
     * @param mixed $user_id User ID or IP string for guests
     * @param bool $is_guest Whether this is a guest vote
     * @return array|WP_Error Result array or error
     */
    private function process_vote($post_id, $user_id, $is_guest = false) {
        // Get submission post
        $post = get_post($post_id);
        if (!$post) {
            return new \WP_Error('invalid_post', __('Invalid submission', 'voxel-photo-contests'));
        }
        
        // Get contest ID from submission
        $contest_id = 0;
        
        // Check if we're using Voxel's post relation field
        if (class_exists('\Voxel\Post')) {
            $submission = \Voxel\Post::get($post_id);
            if ($submission) {
                $field = $submission->get_field('contest');
                if ($field && $field->get_type() === 'post-relation') {
                    $contest_posts = $field->get_value();
                    if (!empty($contest_posts)) {
                        $contest_id = (int) $contest_posts[0];
                    }
                }
            }
        }
        
        // Fallback to regular post meta if needed
        if (!$contest_id) {
            $contest_id = get_post_meta($post_id, 'vpc_contest_id', true);
        }
        
        if (!$contest_id) {
            return new \WP_Error('no_contest', __('No associated contest found', 'voxel-photo-contests'));
        }
        
        // Check if voting is enabled for this contest
        $is_voting_enabled = $this->is_voting_enabled($contest_id);
        if (!$is_voting_enabled) {
            return new \WP_Error('voting_disabled', __('Voting is not enabled for this contest', 'voxel-photo-contests'));
        }
        
        // Check if contest has started
        $contest_dates = $this->get_contest_dates($contest_id);
        if ($contest_dates['start'] && time() < $contest_dates['start']) {
            return new \WP_Error('contest_not_started', __('This contest has not started yet', 'voxel-photo-contests'));
        }
        
        // Check if contest has ended
        if ($contest_dates['end'] && time() > $contest_dates['end']) {
            return new \WP_Error('contest_ended', __('This contest has ended', 'voxel-photo-contests'));
        }
        
        // Check max votes per user
        $max_votes = $this->get_max_votes_per_user($contest_id);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        
        // Convert user_id to string for compatibility with IP-based IDs
        $user_id_value = is_numeric($user_id) ? intval($user_id) : $user_id;
        
        // Check if user has already voted for this post
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND user_id = %s",
            $post_id, $user_id_value
        ));
        
        if ($existing_vote) {
            return new \WP_Error('already_voted', __('You have already voted for this submission', 'voxel-photo-contests'));
        }
        
        // Count how many votes this user has made in the contest
        $vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE contest_id = %d AND user_id = %s",
            $contest_id, $user_id_value
        ));
        
        // Check if user has reached max votes
        if ($vote_count >= $max_votes) {
            return new \WP_Error('max_votes_reached', sprintf(
                __('You have already used all %d of your allowed votes for this contest', 'voxel-photo-contests'), 
                $max_votes
            ));
        }
        
        // Record vote
        try {
            $result = $wpdb->insert(
                $table_name,
                [
                    'post_id' => $post_id,
                    'contest_id' => $contest_id,
                    'user_id' => $user_id_value,
                    'vote_value' => 1,
                    'vote_date' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%d', '%s']
            );
            
            if ($result === false) {
                return new \WP_Error('db_error', __('Error recording vote', 'voxel-photo-contests'));
            }
            
            // Get updated vote count for this post
            $post_vote_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
                $post_id
            ));
            
            // Update post meta for quick access to vote count
            update_post_meta($post_id, 'vpc_vote_count', $post_vote_count);
            
            return [
                'success' => true,
                'count' => $post_vote_count
            ];
            
        } catch (\Exception $e) {
            return new \WP_Error('exception', $e->getMessage());
        }
    }
    
    /**
     * Display vote button after submission content
     */
    public function display_vote_button($post) {
        // Make sure this is a submission post
        if (!$post || $post->post_type !== 'submission') {
            return;
        }
        
        echo $this->get_vote_button_html($post->ID);
    }
    
    /**
     * Get vote button HTML
     */
    public function get_vote_button_html($post_id) {
        // Get submission post
        $post = get_post($post_id);
        if (!$post) {
            return '';
        }
        
        // Get contest ID from submission
        $contest_id = 0;
        
        // Check if we're using Voxel's post relation field
        if (class_exists('\Voxel\Post')) {
            $submission = \Voxel\Post::get($post_id);
            if ($submission) {
                $field = $submission->get_field('contest');
                if ($field && $field->get_type() === 'post-relation') {
                    $contest_posts = $field->get_value();
                    if (!empty($contest_posts)) {
                        $contest_id = (int) $contest_posts[0];
                    }
                }
            }
        }
        
        // Fallback to regular post meta if needed
        if (!$contest_id) {
            $contest_id = get_post_meta($post_id, 'vpc_contest_id', true);
        }
        
        if (!$contest_id) {
            return '';
        }
        
        // Check if voting is enabled
        if (!$this->is_voting_enabled($contest_id)) {
            return '';
        }
        
        // Get contest dates
        $contest_dates = $this->get_contest_dates($contest_id);
        $contest_not_started = $contest_dates['start'] && time() < $contest_dates['start'];
        $contest_ended = $contest_dates['end'] && time() > $contest_dates['end'];
        
        // Get vote count
        $vote_count = get_post_meta($post_id, 'vpc_vote_count', true) ?: 0;
        
        // Get current user or IP
        $is_guest = !is_user_logged_in();
        $user_id = $is_guest ? 'ip_' . md5($_SERVER['REMOTE_ADDR']) : get_current_user_id();
        
        // Check if guest voting is allowed
        $allow_guest_voting = get_option('vpc_allow_guest_voting', 'no');
        if ($is_guest && $allow_guest_voting !== 'yes') {
            ob_start();
            ?>
            <div class="vpc-vote-container" data-post-id="<?php echo $post_id; ?>" data-contest-id="<?php echo $contest_id; ?>">
                <div class="vpc-vote-count"><?php echo $vote_count; ?> <?php echo _n('vote', 'votes', $vote_count, 'voxel-photo-contests'); ?></div>
                <div class="vpc-login-to-vote">
                    <?php _e('Please log in to vote', 'voxel-photo-contests'); ?>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Check if user has already voted
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        $has_voted = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE post_id = %d AND user_id = %s",
            $post_id, $user_id
        ));
        
        // Get max votes per user
        $max_votes = $this->get_max_votes_per_user($contest_id);
        
        // Count how many votes this user has made in the contest
        $user_vote_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE contest_id = %d AND user_id = %s",
            $contest_id, $user_id
        ));
        
        $votes_left = $max_votes - $user_vote_count;
        
        ob_start();
        ?>
        <div class="vpc-vote-container" data-post-id="<?php echo $post_id; ?>" data-contest-id="<?php echo $contest_id; ?>">
            <div class="vpc-vote-count"><?php echo $vote_count; ?> <?php echo _n('vote', 'votes', $vote_count, 'voxel-photo-contests'); ?></div>
            
            <?php if (!$contest_ended && !$contest_not_started && !$has_voted && $votes_left > 0): ?>
                <button class="vpc-vote-button">
                    <span class="vpc-heart">♡</span> <?php _e('Vote', 'voxel-photo-contests'); ?>
                </button>
                
                <?php if ($max_votes > 1): ?>
                    <div class="vpc-votes-left">
                        <?php printf(_n('You have %d vote left', 'You have %d votes left', $votes_left, 'voxel-photo-contests'), $votes_left); ?>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($has_voted): ?>
                <div class="vpc-voted">
                    <span class="vpc-heart vpc-voted-heart">❤</span> <?php _e('Voted', 'voxel-photo-contests'); ?>
                </div>
            <?php elseif ($votes_left <= 0): ?>
                <div class="vpc-votes-limit-reached">
                    <?php _e('You have used all your votes', 'voxel-photo-contests'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($contest_not_started): ?>
                <div class="vpc-contest-info">
                    <?php printf(__('Contest starts: %s', 'voxel-photo-contests'), date_i18n(get_option('date_format'), $contest_dates['start'])); ?>
                </div>
            <?php elseif ($contest_dates['end']): ?>
                <div class="vpc-contest-info">
                    <?php if ($contest_ended): ?>
                        <?php _e('Contest ended', 'voxel-photo-contests'); ?>
                    <?php else: ?>
                        <?php printf(__('Contest ends: %s', 'voxel-photo-contests'), date_i18n(get_option('date_format'), $contest_dates['end'])); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Check if voting is enabled for a contest
     */
    public function is_voting_enabled($contest_id) {
        // Default to enabled
        $enabled = true;
        
        // Check if we're using Voxel's fields
        if (class_exists('\Voxel\Post')) {
            $contest = \Voxel\Post::get($contest_id);
            if ($contest) {
                $field = $contest->get_field('enable_voting');
                if ($field && $field->get_type() === 'switcher') {
                    $enabled = $field->get_value() === '1';
                }
            }
        }
        
        // Fallback to regular post meta
        if (!isset($contest) || !$contest) {
            $enable_voting = get_post_meta($contest_id, 'vpc_enable_voting', true);
            $enabled = $enable_voting !== 'no'; // Default to yes if not set
        }
        
        return $enabled;
    }
    
    /**
     * Get contest start and end dates
     */
    public function get_contest_dates($contest_id) {
        $dates = [
            'start' => null,
            'end' => null
        ];
        
        // Check if we're using Voxel's fields
        if (class_exists('\Voxel\Post')) {
            $contest = \Voxel\Post::get($contest_id);
            if ($contest) {
                $start_field = $contest->get_field('contest_starts');
                $end_field = $contest->get_field('contest_ends');
                
                if ($start_field && $start_field->get_type() === 'date') {
                    $start_value = $start_field->get_value();
                    if ($start_value) {
                        $dates['start'] = strtotime($start_value);
                    }
                }
                
                if ($end_field && $end_field->get_type() === 'date') {
                    $end_value = $end_field->get_value();
                    if ($end_value) {
                        $dates['end'] = strtotime($end_value);
                    }
                }
            }
        }
        
        // Fallback to regular post meta
        if (!isset($contest) || !$contest) {
            $start_date = get_post_meta($contest_id, 'vpc_contest_starts', true);
            $end_date = get_post_meta($contest_id, 'vpc_contest_ends', true);
            
            if ($start_date) {
                $dates['start'] = strtotime($start_date);
            }
            
            if ($end_date) {
                $dates['end'] = strtotime($end_date);
            }
        }
        
        return $dates;
    }
    
    /**
     * Get max votes per user for a contest
     */
    public function get_max_votes_per_user($contest_id) {
        // Default to 1
        $max_votes = 1;
        
        // Check if we're using Voxel's fields
        if (class_exists('\Voxel\Post')) {
            $contest = \Voxel\Post::get($contest_id);
            if ($contest) {
                $field = $contest->get_field('max_votes_per_user');
                if ($field && $field->get_type() === 'number') {
                    $value = $field->get_value();
                    if ($value && is_numeric($value)) {
                        $max_votes = (int) $value;
                    }
                }
            }
        }
        
        // Fallback to regular post meta
        if (!isset($contest) || !$contest) {
            $votes = get_post_meta($contest_id, 'vpc_max_votes_per_user', true);
            if ($votes && is_numeric($votes)) {
                $max_votes = (int) $votes;
            }
        }
        
        return max(1, $max_votes); // Ensure minimum of 1
    }
    
    /**
     * Add vote count to post data in search results
     */
    public function add_vote_count_to_post_data($data, $post) {
        // Only process submission post types
        if ($post->post_type->get_key() !== 'submission') {
            return $data;
        }
        
        // Add vote count to post data
        $vote_count = get_post_meta($post->get_id(), 'vpc_vote_count', true) ?: 0;
        $data['vote_count'] = $vote_count;
        
        return $data;
    }
    
    /**
     * Get a list of top voted submissions for a contest
     */
    public function get_top_submissions($contest_id, $limit = 10) {
        $args = [
            'post_type' => 'submission',
            'posts_per_page' => $limit,
            'meta_query' => [
                [
                    'key' => 'vpc_vote_count',
                    'type' => 'NUMERIC',
                    'compare' => 'EXISTS',
                ],
                [
                    'key' => 'vpc_contest_id',
                    'value' => $contest_id,
                    'compare' => '=',
                ],
            ],
            'orderby' => [
                'meta_value_num' => 'DESC',
                'date' => 'DESC',
            ],
            'meta_key' => 'vpc_vote_count',
        ];
        
        // If using Voxel's post relation field
        if (class_exists('\Voxel\Post')) {
            // We need a different approach as Voxel stores post relations differently
            // This is a simplified version - actual implementation would depend on Voxel's database structure
            $args['meta_query'] = [
                [
                    'key' => 'vpc_vote_count',
                    'type' => 'NUMERIC',
                    'compare' => 'EXISTS',
                ]
            ];
            
            // We'll filter the results after the query to only include submissions related to this contest
            add_filter('posts_where', function($where) use ($contest_id) {
                global $wpdb;
                // This is a simplified example - you may need to adjust based on Voxel's actual database schema
                $where .= $wpdb->prepare(" AND ID IN (SELECT post_id FROM {$wpdb->prefix}voxel_relations WHERE related_id = %d)", $contest_id);
                return $where;
            });
        }
        
        $query = new \WP_Query($args);
        
        // Remove our filter if we added it
        if (class_exists('\Voxel\Post')) {
            remove_all_filters('posts_where');
        }
        
        return $query->posts;
    }
    
    /**
     * Get total votes for a contest
     */
    public function get_contest_vote_count($contest_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE contest_id = %d",
            $contest_id
        ));
    }
    
    /**
     * Get unique voters count for a contest
     */
    public function get_unique_voters_count($contest_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vpc_votes';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE contest_id = %d",
            $contest_id
        ));
    }
}