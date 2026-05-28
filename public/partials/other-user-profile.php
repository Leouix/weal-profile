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

$is_author  = count_user_posts( $profile_user_id ) > 0;
$user_posts = array();

if ( $is_author ) {
	$user_posts = get_posts(
		array(
			'author'         => $profile_user_id,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

$settings              = ( new Settings_Manager() )->get_settings();
$comment_votes_enabled = $settings['comment_votes_enabled'] ?? true;

$likes_service  = new Likes_Vote_Service();
$vote_data      = $likes_service->get_user_vote_data( $profile_user_id );
$total_likes    = $vote_data['total_likes'] ?? 0;
$total_dislikes = $vote_data['total_dislikes'] ?? 0;

$comments_service = new Comments_Service();
$top_comments     = $comments_service->get_user_comments_data( $profile_user_id );

$user_comments = get_comments(
	array(
		'user_id' => $profile_user_id,
		'status'  => 'approve',
	)
);

$user_id = $profile_user_id;
?>

<div class="weal-profile-top-area">
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
	<div class="other-user-tabs">
		<div class="other-user-tab active" data-tab="posts" onclick="switchOtherUserTab(this)">
			<?php esc_html_e( 'Posts', 'weal-profile' ); ?>
		</div>
		<div class="other-user-tab" data-tab="comments" onclick="switchOtherUserTab(this)">
			<?php esc_html_e( 'Comments', 'weal-profile' ); ?>
		</div>
	</div>

	<div id="other-user-posts" class="other-user-posts">
		<?php if ( ! empty( $user_posts ) ) : ?>
			<?php foreach ( $user_posts as $post_item ) : ?>
				<?php setup_postdata( $post_item ); ?>
				<div class="other-user-post-item">
					<?php if ( has_post_thumbnail( $post_item->ID ) ) : ?>
						<div class="post-thumbnail">
							<?php echo get_the_post_thumbnail( $post_item->ID, 'thumbnail' ); ?>
						</div>
					<?php endif; ?>
					<div class="post-content">
						<h3>
							<a href="<?php echo esc_url( get_permalink( $post_item->ID ) ); ?>">
								<?php echo esc_html( get_the_title( $post_item->ID ) ); ?>
							</a>
						</h3>
					</div>
				</div>
			<?php endforeach; ?>
			<?php wp_reset_postdata(); ?>
		<?php endif; ?>
	</div>

	<div id="other-user-comments" class="other-user-comments" style="display:none;">
<?php else : ?>
	<div id="other-user-comments" class="other-user-comments">
<?php endif; ?>

	<?php require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/user-comments-list.php'; ?>

</div>

<?php
require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/profile-page-footer.php';
