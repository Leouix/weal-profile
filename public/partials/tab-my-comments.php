<?php
/**
 * Template for the My Comments subtab content.
 *
 * @package weal-profile
 *
 * Expected variables (prefixed):
 *   $weal_profile_user_comments        array
 *   $weal_profile_total_likes          int
 *   $weal_profile_total_dislikes       int
 *   $weal_profile_comment_votes_enabled bool
 *   $weal_profile_total_pages          int
 *   $weal_profile_pagination_html      string
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/user-comments-list.php';
?>

<?php if ( $weal_profile_total_pages > 1 ) : ?>
	<div class="weal-pagination" data-subtab="comments">
		<?php echo wp_kses_post( $weal_profile_pagination_html ); ?>
	</div>
<?php endif; ?>
