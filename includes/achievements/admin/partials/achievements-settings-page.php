<?php
/**
 * Achievements settings page template.
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Variables passed from render_admin_page().
 *
 * @var array $achievements_settings
 */

?>
<div class="au-container">
	<form id="achievements-settings-form">
		<?php wp_nonce_field( 'weal_profile_achievements_save', 'weal_profile_achievements_nonce' ); ?>

		<p><?php esc_html_e( 'Achievements settings will be available in future updates.', 'weal-profile' ); ?></p>

		<div class="button-area">
			<input id="save-achievements-button" type="submit" value="<?php esc_attr_e( 'Save Settings', 'weal-profile' ); ?>">
			<div id="achievements-success-notice"><?php esc_html_e( 'Success!', 'weal-profile' ); ?></div>
			<div id="achievements-error-notice"></div>
		</div>
	</form>
</div>
