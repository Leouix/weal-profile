<?php
/**
 * Template for viewing another user's profile.
 *
 * Loaded when visiting a profile with a valid ?u= token
 * that points to a different user.
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/profile-page-header.php';

use WealProfile\Includes\Comment_Votes\Comments_Service;
use WealProfile\Includes\Comment_Votes\Likes_Vote_Service;
use WealProfile\Includes\Manager\Settings_Manager;

$is_author = count_user_posts( $profile_user_id ) > 0;

$items_per_page = 10;

// Posts pagination.
$posts_paged    = isset( $_GET['posts_page'] ) ? max( 1, intval( $_GET['posts_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$posts_query    = new WP_Query(
	array(
		'author'         => $profile_user_id,
		'post_status'    => 'publish',
		'posts_per_page' => $items_per_page,
		'paged'          => $posts_paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
$user_posts     = $posts_query->posts;
$posts_max_page = $posts_query->max_num_pages;

$settings              = ( new Settings_Manager() )->get_settings();
$comment_votes_enabled = $settings['comment_votes_enabled'] ?? true;

$likes_service  = new Likes_Vote_Service();
$vote_data      = $likes_service->get_user_vote_data( $profile_user_id );
$total_likes    = $vote_data['total_likes'] ?? 0;
$total_dislikes = $vote_data['total_dislikes'] ?? 0;

$comments_service = new Comments_Service();
$top_comments     = $comments_service->get_user_comments_data( $profile_user_id );

// Comments pagination.
$comments_page       = isset( $_GET['comments_page'] ) ? max( 1, intval( $_GET['comments_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$comments_offset     = ( $comments_page - 1 ) * $items_per_page;
$user_comments       = get_comments(
	array(
		'user_id' => $profile_user_id,
		'status'  => 'approve',
		'number'  => $items_per_page,
		'offset'  => $comments_offset,
	)
);
$comment_query       = new WP_Comment_Query();
$total_user_comments = $comment_query->query(
	array(
		'user_id' => $profile_user_id,
		'status'  => 'approve',
		'count'   => true,
	)
);
$comments_max_page   = (int) ceil( $total_user_comments / $items_per_page );

$active_tab     = isset( $_GET['comments_page'] ) ? 'comments' : 'posts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$posts_style    = 'comments' === $active_tab ? 'display:none;' : '';
$comments_style = 'posts' === $active_tab ? 'display:none;' : '';

$user_id = $profile_user_id;
?>

<div class="weal-profile-top-area weal-other-top-area">
	<div></div>

	<?php if ( $weal_profile_is_avatar_field_allowed ) : ?>
		<div class="avatar-area">
			<div class="weal-profile-avatar-wrapper">
				<?php echo wp_kses_post( $weal_profile_avatar_html ); ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php if ( $is_author ) : ?>
	<div class="activity-buttons">
		<div class="activity-button <?php echo 'posts' === $active_tab ? 'active' : ''; ?>" data-tab="posts" data-wp-action="switch-activity-button">
			<?php esc_html_e( 'Posts', 'weal-profile' ); ?>
		</div>
		<div class="activity-button <?php echo 'comments' === $active_tab ? 'active' : ''; ?>" data-tab="comments" data-wp-action="switch-activity-button">
			<?php esc_html_e( 'Comments', 'weal-profile' ); ?>
		</div>
	</div>

	<div id="other-user-posts" class="other-user-posts" style="<?php echo esc_attr( $posts_style ); ?>">
		<?php if ( ! empty( $user_posts ) ) : ?>
			<?php foreach ( $user_posts as $post_item ) : ?>
				<?php setup_postdata( $post_item ); ?>

            <a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>">

				<div class="weal-user-post-item">
					<?php if ( has_post_thumbnail( $post_item->ID ) ) : ?>
						<div class="post-thumbnail">
							<?php echo get_the_post_thumbnail( $post_item->ID, 'thumbnail' ); ?>
						</div>
					<?php endif; ?>
					<div class="post-content">
						<h3>

								<?php echo esc_html( get_the_title( $post_item->ID ) ); ?>

						</h3>
					</div>
				</div>

            </a>
			<?php endforeach; ?>
			<?php wp_reset_postdata(); ?>

			<?php if ( $posts_max_page > 1 ) : ?>
				<div class="weal-pagination">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'posts_page', '%#%' ),
								'format'  => '',
								'current' => $posts_paged,
								'total'   => $posts_max_page,
								'type'    => 'list',
							)
						)
					);
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div id="other-user-comments" class="other-user-comments" style="<?php echo esc_attr( $comments_style ); ?>">
<?php else : ?>
	<div id="other-user-comments" class="other-user-comments">
<?php endif; ?>

	<?php require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/user-comments-list.php'; ?>

	<?php if ( $comments_max_page > 1 ) : ?>
		<div class="weal-pagination">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'comments_page', '%#%' ),
						'format'  => '',
						'current' => $comments_page,
						'total'   => $comments_max_page,
						'type'    => 'list',
					)
				)
			);
			?>
		</div>
	<?php endif; ?>

</div>

<?php
require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/profile-page-footer.php';
