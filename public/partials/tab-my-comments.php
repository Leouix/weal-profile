<?php
/**
 * Template for the My Comments subtab content.
 *
 * @package weal-profile
 *
 * Expected variables:
 *   $user_comments        array
 *   $total_likes          int
 *   $total_dislikes       int
 *   $top_comments         array
 *   $comment_votes_enabled bool
 *   $total_pages          int
 *   $pagination_html      string
 *   $user_id              int
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/user-comments-list.php';
?>

<?php if ( $total_pages > 1 ) : ?>
	<div class="weal-pagination" data-subtab="comments">
		<?php echo wp_kses_post( $pagination_html ); ?>
	</div>
<?php endif; ?>
