<?php
/**
 * Reusable component: full comments list with reaction stats.
 *
 * @package weal-profile
 *
 * Expected variables:
 *   $user_id              int
 *   $total_likes          int
 *   $total_dislikes       int
 *   $top_comments         array
 *   $comment_votes_enabled bool
 *   $user_comments        array|WP_Comment[]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( ! empty( $user_comments ) ) : ?>

	<?php if ( $comment_votes_enabled ) : ?>
		<div class="weal-comment-reactions">
			<h3><?php esc_html_e( 'Comment Reactions', 'weal-profile' ); ?></h3>
			<div class="weal-reactions-summary">
				<div class="weal-reaction-stat weal-likes">
					<span class="dashicons dashicons-thumbs-up"></span>
					<span class="weal-stat-value"><?php echo esc_html( $total_likes ); ?></span>
					<span class="weal-stat-label"><?php esc_html_e( 'Likes Received', 'weal-profile' ); ?></span>
				</div>
				<div class="weal-reaction-stat weal-dislikes">
					<span class="dashicons dashicons-thumbs-down"></span>
					<span class="weal-stat-value"><?php echo esc_html( $total_dislikes ); ?></span>
					<span class="weal-stat-label"><?php esc_html_e( 'Dislikes Received', 'weal-profile' ); ?></span>
				</div>
			</div>

			<?php if ( ! empty( $top_comments ) ) : ?>
				<div class="weal-top-comments">
					<h4><?php esc_html_e( 'Top Comments', 'weal-profile' ); ?></h4>
					<ul id="weal-comments-list">
						<?php foreach ( $user_comments as $top_comment ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $top_comment->comment_post_ID ) ); ?>#comment-<?php echo esc_attr( $top_comment->comment_ID ); ?>">
									<?php echo esc_html( wp_trim_words( $top_comment->comment_content, 10, '...' ) ); ?>
								</a>
								<span class="weal-comment-likes">
									<span class="dashicons dashicons-thumbs-up"></span>
									<?php echo esc_html( $top_comment->likes_count ); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

<?php else : ?>
	<p><?php esc_html_e( 'No comments', 'weal-profile' ); ?></p>
<?php endif; ?>
