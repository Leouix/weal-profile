<?php
/**
 * Comment items for Load More pagination.
 *
 * @package weal-profile
 *
 * Expected variables:
 *   $user_comments        array|WP_Comment[]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $user_comments as $comment ) :
	?>
	<li>
		<a href="<?php echo esc_url( get_permalink( $comment->comment_post_ID ) ); ?>#comment-<?php echo esc_attr( $comment->comment_ID ); ?>">
			<?php echo esc_html( wp_trim_words( $comment->comment_content, 10, '...' ) ); ?>
		</a>
		<span class="weal-comment-likes">
			<span class="dashicons dashicons-thumbs-up"></span>
			<?php echo esc_html( $comment->likes_count ?? 0 ); ?>
		</span>
	</li>
<?php endforeach; ?>
