<?php
/**
 * Reusable component: full comments list with reaction stats.
 *
 * @package weal-profile
 *
 * Expected variables (prefixed):
 *   $weal_profile_total_likes          int
 *   $weal_profile_total_dislikes       int
 *   $weal_profile_comment_votes_enabled bool
 *   $weal_profile_user_comments        array|WP_Comment[]
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php if ( $weal_profile_comment_votes_enabled ) : ?>
<div class="weal-comment-reactions">
	<h3><?php esc_html_e( 'Comment Reactions', 'weal-profile' ); ?></h3>
	<div class="weal-reactions-summary">
		<div class="weal-reaction-stat weal-likes">
			<span class="dashicons dashicons-thumbs-up"></span>
			<span class="weal-stat-value"><?php echo esc_html( $weal_profile_total_likes ); ?></span>
			<span class="weal-stat-label"><?php esc_html_e( 'Likes Received', 'weal-profile' ); ?></span>
		</div>
		<div class="weal-reaction-stat weal-dislikes">
			<span class="dashicons dashicons-thumbs-down"></span>
			<span class="weal-stat-value"><?php echo esc_html( $weal_profile_total_dislikes ); ?></span>
			<span class="weal-stat-label"><?php esc_html_e( 'Dislikes Received', 'weal-profile' ); ?></span>
		</div>
	</div>
</div>
<?php endif; ?>

<?php if ( ! empty( $weal_profile_user_comments ) ) : ?>

		<div class="weal-top-comments">
			<h4><?php esc_html_e( 'Comments', 'weal-profile' ); ?></h4>
			<ul id="weal-comments-list">
				<?php
				foreach ( $weal_profile_user_comments as $weal_profile_top_comment ) :
					$weal_profile_has_any_reaction = $weal_profile_comment_votes_enabled
						&& ( $weal_profile_top_comment->has_likes || $weal_profile_top_comment->has_dislikes );
					?>
					<li>
						<a href="<?php echo esc_url( get_permalink( $weal_profile_top_comment->comment_post_ID ) ); ?>#comment-<?php echo esc_attr( $weal_profile_top_comment->comment_ID ); ?>">
							<?php echo esc_html( wp_trim_words( $weal_profile_top_comment->comment_content, 10, '...' ) ); ?>
						</a>
						<?php if ( $weal_profile_has_any_reaction ) : ?>
						<span class="weal-comment-reactions">
							<?php if ( $weal_profile_top_comment->has_likes ) : ?>
							<span class="weal-reaction-icon weal-icon-likes">
								<span class="dashicons dashicons-thumbs-up"></span>
							</span>
							<?php endif; ?>
							<?php if ( $weal_profile_top_comment->has_dislikes ) : ?>
							<span class="weal-reaction-icon weal-icon-dislikes">
								<span class="dashicons dashicons-thumbs-down"></span>
							</span>
							<?php endif; ?>
						</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

<?php else : ?>
	<p><?php esc_html_e( 'No comments', 'weal-profile' ); ?></p>
<?php endif; ?>
