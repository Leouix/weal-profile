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
	 * @param int $user_id User ID.
	 * @return string HTML output.
	 */
	public static function render( $user_id ) {
		global $wpdb;

		$table_name    = $wpdb->prefix . Comment_Votes::TABLE_NAME;
		$comments_table = $wpdb->prefix . 'comments';

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return '';
		}

		$user_email = $user->user_email;

		$total_likes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i v
                 INNER JOIN %i c ON v.comment_id = c.comment_ID
                 WHERE c.comment_author_email = %s AND v.is_liked = 1', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comments_table,
				$user_email
			)
		);

		$total_dislikes = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i v
                 INNER JOIN %i c ON v.comment_id = c.comment_ID
                 WHERE c.comment_author_email = %s AND v.is_liked = 0', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$table_name,
				$comments_table,
				$user_email
			)
		);

		$commentmeta_table = $wpdb->prefix . 'commentmeta';

		$top_comments = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				"SELECT c.comment_ID, c.comment_content, c.comment_post_ID,
                    COALESCE(m.meta_value, 0) as likes_count
                FROM %i c
                LEFT JOIN %i m ON c.comment_ID = m.comment_id AND m.meta_key = '_weal_likes_count'
                WHERE c.comment_author_email = %s AND c.comment_approved = 1
                ORDER BY COALESCE(m.meta_value, 0) DESC
                LIMIT 3", // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders
				$comments_table,
				$commentmeta_table,
				$user_email
			)
		);

		ob_start();
		?>
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
