<?php
/**
 * Comment items for Load More pagination.
 *
 * @package weal-profile
 *
 * Expected variables (prefixed):
 *   $weal_profile_user_comments        array|WP_Comment[]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $user_comments as $weal_profile_comment_item ) :
	?>
	<li>
		<a href="<?php echo esc_url( get_permalink( $weal_profile_comment_item->comment_post_ID ) ); ?>#comment-<?php echo esc_attr( $weal_profile_comment_item->comment_ID ); ?>">
			<?php echo esc_html( wp_trim_words( $weal_profile_comment_item->comment_content, 10, '...' ) ); ?>
		</a>
		<span class="weal-comment-likes">
			<span class="dashicons dashicons-thumbs-up"></span>
			<?php echo esc_html( $weal_profile_comment_item->likes_count ?? 0 ); ?>
		</span>
	</li>
<?php endforeach; ?>
