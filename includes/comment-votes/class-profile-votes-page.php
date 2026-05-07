<?php
/**
 * Profile Page - Comment Reactions Block
 *
 * @package weal-profile
 */

namespace WealProfile\Includes\Comment_Votes;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Profile_Votes_Page
 */
class Profile_Votes_Page {

	/**
	 * Render the Comment Reactions block on the profile page.
	 *
	 * @param int   $user_id       User ID.
	 * @param int   $total_likes    Total likes count.
	 * @param int   $total_dislikes Total dislikes count.
	 * @param array $top_comments   Top comments data.
	 * @param bool  $liking_allowed   Is module allowed to like comments.
	 * @return string HTML output.
	 */
	public static function render(
		$user_id,
		$total_likes = 0,
		$total_dislikes = 0,
		$top_comments = array(),
		$liking_allowed = true
	) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return '';
		}

		ob_start();
		?>
		<div class="weal-comment-reactions">

			<?php if ( true === $liking_allowed ) : ?>

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
			<?php endif; ?>

			<?php if ( ! empty( $top_comments ) ) : ?>
				<div class="weal-top-comments">
					<h4><?php esc_html_e( 'Top Comments', 'weal-profile' ); ?></h4>
					<ul>
						<?php foreach ( $top_comments as $comment ) : ?>
							<li>
								<a href="<?php echo esc_url( get_permalink( $comment->comment_post_ID ) ); ?>#comment-<?php echo esc_attr( $comment->comment_ID ); ?>">
									<?php echo esc_html( wp_trim_words( $comment->comment_content, 10, '...' ) ); ?>
								</a>
								<span class="weal-comment-likes">
									<span class="dashicons dashicons-thumbs-up"></span>
									<?php echo esc_html( $comment->likes_count ); ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
