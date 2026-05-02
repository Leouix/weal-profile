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
			<h1 class="wp-block-post-title"><?php echo esc_html__( 'My Account', 'weal-profile' ); ?></h1>
			<div id="main-tabs">
				<div id="tab-button-1"
					class="main-tabs-item"
					onclick="switchTab(this)">
					<div class="text"><?php echo esc_html__( 'Comments', 'weal-profile' ); ?></div>
				</div>
				<div id="tab-button-3"
					class="main-tabs-item"
					onclick="switchTab(this)">
					<div class="text"><?php echo esc_html__( 'My Info', 'weal-profile' ); ?></div>
				</div>
			</div>

		<div id="container-results"></div>

		<?php
		$user_id = get_current_user_id();
		$avatar_html = Weal_Profile_Avatar::get_avatar_html( $user_id );

		if ( isset( $_GET['avatar_updated'] ) && '1' === $_GET['avatar_updated'] ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Profile picture updated.', 'weal-profile' ) . '</p></div>';
		}

		if ( isset( $_GET['avatar_removed'] ) && '1' === $_GET['avatar_removed'] ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Profile picture removed.', 'weal-profile' ) . '</p></div>';
		}
		?>

		<div class="weal-profile-avatar-wrapper">
			<?php echo wp_kses_post( $avatar_html ); ?>
		</div>

		<form method="post" action="" enctype="multipart/form-data" class="weal-profile-avatar-form">
			<?php wp_nonce_field( 'weal_profile_avatar_action', 'weal_profile_avatar_nonce' ); ?>
			<input type="hidden" name="weal_profile_avatar_action" value="upload" />
			<input type="file" name="profile_avatar" accept=".jpg,.jpeg,.png,.webp" />
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Upload Avatar', 'weal-profile' ); ?></button>
		</form>

		<form method="post" action="" class="weal-profile-avatar-form">
			<?php wp_nonce_field( 'weal_profile_avatar_action', 'weal_profile_avatar_nonce' ); ?>
			<input type="hidden" name="weal_profile_avatar_action" value="remove" />
			<button type="submit" class="button"><?php esc_html_e( 'Remove Avatar', 'weal-profile' ); ?></button>
		</form>
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
