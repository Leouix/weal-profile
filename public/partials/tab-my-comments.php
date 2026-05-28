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
 *   $page                 int
 *   $total_pages          int
 *   $has_more             bool
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php echo esc_html__( 'My comments', 'weal-profile' ); ?></h2>

<?php
$user_id = get_current_user_id();
require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/user-comments-list.php';
?>

<?php if ( $has_more ) : ?>
	<div class="weal-load-more-wrap">
		<button class="weal-load-more button" data-page="<?php echo esc_attr( $page ); ?>" data-total-pages="<?php echo esc_attr( $total_pages ); ?>">
			<?php esc_html_e( 'Load More', 'weal-profile' ); ?>
		</button>
	</div>
<?php endif; ?>
