<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link  https://weal.cloud
 * @since 1.0.0
 *
 * @package    Weal_Profile
 * @subpackage Weal_Profile/public/partials
 */

global $weal_profile_user_id;
$profile_user_id = $weal_profile_user_id;
$is_own_profile  = (int) $profile_user_id === get_current_user_id();

$weal_profile_avatar_html             = Weal_Profile_Avatar::get_avatar_html( $profile_user_id );
$weal_profile_is_avatar_field_allowed = Weal_Profile_Avatar::get_is_avatar_field_allowed();

if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
	?>
	<div class="wp-site-blocks">
		<header class="wp-block-template-part">
			<?php block_template_part( 'header' ); ?>
		</header>
	</div>
	<?php
	wp_head();
} else {
	get_header();
}

?>
	<div class="container au-container">
		<div class="entry-content alignfull wp-block-post-content has-global-padding is-layout-constrained wp-block-post-content-is-layout-constrained">

            <?php if ( $is_own_profile ) : ?>
			    <h1 class="wp-block-post-title"><?php echo esc_html__( 'My Account', 'weal-profile' ); ?></h1>
            <?php endif; ?>

			<div class="weal-profile-top-area">
                <?php if ( $is_own_profile ) : ?>
                    <div id="main-tabs">
                        <div id="tab-button-1"
                            class="main-tabs-item active"
                            onclick="switchTab(this)"
                        <div class="text"><?php echo esc_html__( 'Activity', 'weal-profile' ); ?></div>
                        </div>
                        <div id="tab-button-3"
                            class="main-tabs-item"
                            onclick="switchTab(this)">
                            <div class="text"><?php echo esc_html__( 'My Info', 'weal-profile' ); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>

				<?php if ( $weal_profile_is_avatar_field_allowed ) : ?>
					<div class="avatar-area">
						<div class="weal-profile-avatar-wrapper">
							<?php echo wp_kses_post( $weal_profile_avatar_html ); ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $weal_profile_is_avatar_field_allowed && $is_own_profile ) : ?>
				<div class="weal-profile-avatar-forms">
					<form method="post" action="" enctype="multipart/form-data" class="weal-profile-avatar-form">
						<?php wp_nonce_field( 'weal_profile_avatar_action', 'weal_profile_avatar_nonce' ); ?>
						<input type="hidden" name="weal_profile_avatar_action" value="upload" />
						<input type="file" name="weal_profile_avatar" accept=".jpg,.jpeg,.png,.webp" />
					</form>

					<form method="post" action="" class="weal-profile-avatar-form">
						<?php wp_nonce_field( 'weal_profile_avatar_action', 'weal_profile_avatar_nonce' ); ?>
						<input type="hidden" name="weal_profile_avatar_action" value="remove" />
						<button type="submit" title="Delete" class="button weal-button-delete"><?php esc_html_e( 'Del', 'weal-profile' ); ?></button>
					</form>
				</div>
			<?php endif; ?>

		<div id="container-results">
			<?php if ( ! $is_own_profile ) : ?>
				<?php
				$settings              = ( new \WealProfile\Includes\Manager\Settings_Manager() )->get_settings();
				$comment_votes_enabled = $settings['comment_votes_enabled'] ?? true;

				$likes_service = new \WealProfile\Includes\Comment_Votes\Likes_Vote_Service();
				$vote_data     = $likes_service->get_user_vote_data( $profile_user_id );

				$total_likes    = $vote_data['total_likes'] ?? 0;
				$total_dislikes = $vote_data['total_dislikes'] ?? 0;

				$comments_service = new \WealProfile\Includes\Comment_Votes\Comments_Service();
				$top_comments     = $comments_service->get_user_comments_data( $profile_user_id );

				$args          = array(
					'user_id' => $profile_user_id,
					'status'  => 'approve',
				);
				$user_comments = get_comments( $args );

				if ( ! empty( $user_comments ) ) {
					echo wp_kses_post( \WealProfile\Includes\Comment_Votes\Profile_Votes_Page::render( $profile_user_id, $total_likes, $total_dislikes, $top_comments, $comment_votes_enabled ) );
				} else {
					echo esc_html__( 'No comments', 'weal-profile' );
				}
				?>
			<?php endif; ?>
		</div>

	</div>

<?php
if ( function_exists( 'wp_is_block_theme' ) && wp_is_block_theme() ) {
	?>
	<div class="wp-site-blocks">
		<footer class="wp-block-template-part">
			<?php block_template_part( 'footer' ); ?>
		</footer>
	</div>

	<?php

	wp_footer();
} else {
	get_footer();
}
