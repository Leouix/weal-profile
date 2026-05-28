<?php
/**
 * Template for the current user's own profile page.
 *
 * Loaded only when viewing own profile (no ?u= token or token matches self).
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/profile-page-header.php';
?>

<h1 class="wp-block-post-title"><?php echo esc_html__( 'My Account', 'weal-profile' ); ?></h1>

<div class="weal-profile-top-area">
	<div id="main-tabs">
		<div id="tab-button-1"
			class="main-tabs-item active"
			data-wp-action="switch-tab">
			<div class="text"><?php echo esc_html__( 'Activity', 'weal-profile' ); ?></div>
		</div>
		<div id="tab-button-3"
			class="main-tabs-item"
			data-wp-action="switch-tab">
			<div class="text"><?php echo esc_html__( 'My Info', 'weal-profile' ); ?></div>
		</div>
	</div>

	<?php if ( $weal_profile_is_avatar_field_allowed ) : ?>
		<div class="avatar-area">
			<div class="weal-profile-avatar-wrapper">
				<?php echo wp_kses_post( $weal_profile_avatar_html ); ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php if ( $weal_profile_is_avatar_field_allowed ) : ?>
	<div class="weal-profile-avatar-forms">
		<form method="post" action="" enctype="multipart/form-data" class="weal-profile-avatar-form">
			<?php wp_nonce_field( 'weal_profile_avatar_action', 'weal_profile_avatar_nonce' ); ?>
			<input type="hidden" name="weal_profile_avatar_action" value="upload" />
			<input type="file" name="weal_profile_avatar" accept=".jpg,.jpeg,.png,.webp" />
		</form>

		<form method="post" action="" class="weal-profile-avatar-form" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete the image?', 'weal-profile' ) ); ?>')">
			<?php wp_nonce_field( 'weal_profile_avatar_action', 'weal_profile_avatar_nonce' ); ?>
			<input type="hidden" name="weal_profile_avatar_action" value="remove" />
			<button type="submit" title="Delete" class="button weal-button-delete"><?php esc_html_e( 'Del', 'weal-profile' ); ?></button>
		</form>
	</div>
<?php endif; ?>

<div id="container-results"></div>

<?php
require WEAL_PROFILE_PLUGIN_DIR . 'public/partials/profile-page-footer.php';
