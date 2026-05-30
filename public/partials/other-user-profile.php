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

$weal_profile_is_author = count_user_posts( $weal_profile_user_id ) > 0;

$weal_profile_items_per_page = 10;

// Posts pagination.
$weal_profile_posts_paged    = isset( $_GET['posts_page'] ) ? max( 1, intval( $_GET['posts_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$weal_profile_posts_query    = new WP_Query(
	array(
		'author'         => $weal_profile_user_id,
		'post_status'    => 'publish',
		'posts_per_page' => $weal_profile_items_per_page,
		'paged'          => $weal_profile_posts_paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
$weal_profile_user_posts     = $weal_profile_posts_query->posts;
$weal_profile_posts_max_page = $weal_profile_posts_query->max_num_pages;

$weal_profile_settings              = ( new Settings_Manager() )->get_settings();
$weal_profile_comment_votes_enabled = $weal_profile_settings['comment_votes_enabled'] ?? true;

$weal_profile_likes_service  = new Likes_Vote_Service();
$weal_profile_vote_data      = $weal_profile_likes_service->get_user_vote_data( $weal_profile_user_id );
$weal_profile_total_likes    = $weal_profile_vote_data['total_likes'] ?? 0;
$weal_profile_total_dislikes = $weal_profile_vote_data['total_dislikes'] ?? 0;

$weal_profile_comments_service = new Comments_Service();
$weal_profile_top_comments     = $weal_profile_comments_service->get_user_comments_data( $weal_profile_user_id );

// Comments pagination.
$weal_profile_comments_page       = isset( $_GET['comments_page'] ) ? max( 1, intval( $_GET['comments_page'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$weal_profile_comments_offset     = ( $weal_profile_comments_page - 1 ) * $weal_profile_items_per_page;
$weal_profile_user_comments       = get_comments(
	array(
		'user_id' => $weal_profile_user_id,
		'status'  => 'approve',
		'number'  => $weal_profile_items_per_page,
		'offset'  => $weal_profile_comments_offset,
	)
);
$weal_profile_comment_query       = new WP_Comment_Query();
$weal_profile_total_user_comments = $weal_profile_comment_query->query(
	array(
		'user_id' => $weal_profile_user_id,
		'status'  => 'approve',
		'count'   => true,
	)
);
$weal_profile_comments_max_page   = (int) ceil( $weal_profile_total_user_comments / $weal_profile_items_per_page );

$weal_profile_active_tab     = isset( $_GET['comments_page'] ) ? 'comments' : 'posts'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$weal_profile_posts_style    = 'comments' === $weal_profile_active_tab ? 'display:none;' : '';
$weal_profile_comments_style = 'posts' === $weal_profile_active_tab ? 'display:none;' : '';

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

<?php if ( $weal_profile_is_author ) : ?>
	<div class="activity-buttons">
		<div class="activity-button <?php echo 'posts' === $weal_profile_active_tab ? 'active' : ''; ?>" data-tab="posts" data-wp-action="switch-activity-button">
			<?php esc_html_e( 'Posts', 'weal-profile' ); ?>
		</div>
		<div class="activity-button <?php echo 'comments' === $weal_profile_active_tab ? 'active' : ''; ?>" data-tab="comments" data-wp-action="switch-activity-button">
			<?php esc_html_e( 'Comments', 'weal-profile' ); ?>
		</div>
	</div>

	<div id="other-user-posts" class="other-user-posts" style="<?php echo esc_attr( $weal_profile_posts_style ); ?>">
		<?php if ( ! empty( $weal_profile_user_posts ) ) : ?>
			<?php foreach ( $weal_profile_user_posts as $weal_profile_post_item ) : ?>
				<?php setup_postdata( $weal_profile_post_item ); ?>

			<a href="<?php echo esc_url( get_permalink( $weal_profile_post_item->ID ) ); ?>">

				<div class="weal-user-post-item">
					<?php if ( has_post_thumbnail( $weal_profile_post_item->ID ) ) : ?>
						<div class="post-thumbnail">
							<?php echo get_the_post_thumbnail( $weal_profile_post_item->ID, 'thumbnail' ); ?>
						</div>
					<?php endif; ?>
					<div class="post-content">
						<h3>

								<?php echo esc_html( get_the_title( $weal_profile_post_item->ID ) ); ?>

						</h3>
					</div>
				</div>

			</a>
			<?php endforeach; ?>
			<?php wp_reset_postdata(); ?>

			<?php if ( $weal_profile_posts_max_page > 1 ) : ?>
				<div class="weal-pagination">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'posts_page', '%#%' ),
								'format'  => '',
								'current' => $weal_profile_posts_paged,
								'total'   => $weal_profile_posts_max_page,
								'type'    => 'list',
							)
						)
					);
					?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div id="other-user-comments" class="other-user-comments" style="<?php echo esc_attr( $weal_profile_comments_style ); ?>">
<?php else : ?>
	<div id="other-user-comments" class="other-user-comments">
<?php endif; ?>

	<?php require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/user-comments-list.php'; ?>

	<?php if ( $weal_profile_comments_max_page > 1 ) : ?>
		<div class="weal-pagination">
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'    => add_query_arg( 'comments_page', '%#%' ),
						'format'  => '',
						'current' => $weal_profile_comments_page,
						'total'   => $weal_profile_comments_max_page,
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
