<?php
/**
 * Template for the My Comments (Activity) tab.
 *
 * @package weal-profile
 *
 * Expected variables:
 *   $user_comments        array
 *   $total_likes          int
 *   $total_dislikes       int
 *   $top_comments         array
 *   $comment_votes_enabled bool
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php echo esc_html__( 'My comments', 'weal-profile' ); ?></h2>

<?php
$user_id = get_current_user_id();
require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/user-comments-list.php';
