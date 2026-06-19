<?php
/**
 * Achievements settings page template.
 *
 * @package weal-profile
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WealProfile\Includes\Achievements\Weal_Profile_Achievements;

$weal_profile_achievements_data = Weal_Profile_Achievements::get_admin_achievements_data();
?>
<div class="au-container achievement-container">
	<?php foreach ( $weal_profile_achievements_data as $weal_profile_achievement_id => $weal_profile_settings ) : ?>
		<?php
		$weal_source      = isset( $weal_profile_settings['source'] ) ? $weal_profile_settings['source'] : $weal_profile_achievement_id;
		$weal_description = Weal_Profile_Achievements::get_achievement_description( $weal_profile_achievement_id, $weal_profile_settings['target'], $weal_source );
		?>

	<div class="achievement-wrapper">
		<?php if ( Weal_Profile_Achievements::is_system_achievement( $weal_profile_achievement_id ) ) : ?>
			<div class="achievement-duplicate" title="<?php esc_attr_e( 'Duplicate achievement', 'weal-profile' ); ?>"></div>
		<?php else : ?>
			<div class="achievement-delete" title="<?php esc_attr_e( 'Delete achievement', 'weal-profile' ); ?>">
				<img src="<?php echo esc_url( WEAL_PROFILE_PLUGIN_URL . 'admin/icons/delete.png' ); ?>" alt="<?php esc_attr_e( 'Delete', 'weal-profile' ); ?>">
			</div>
		<?php endif; ?>

		<form class="achievement-form">
			<?php wp_nonce_field( 'weal_profile_achievements_save', 'weal_profile_achievements_nonce' ); ?>
			<input type="hidden" name="achievement_id" value="<?php echo esc_attr( $weal_profile_achievement_id ); ?>">

			<div class="achievement-block">
				<h3><?php echo Weal_Profile_Achievements::render_achievement_icon( $weal_profile_settings['icon'], 'admin-achievement-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $weal_profile_settings['label'] ); ?></h3>

				<div class="label-area">
					<input type="hidden" name="achievements[<?php echo esc_attr( $weal_profile_achievement_id ); ?>][enabled]" value="0">
					<label for="achievement-<?php echo esc_attr( $weal_profile_achievement_id ); ?>-enabled">
						<?php esc_html_e( 'Enable achievement', 'weal-profile' ); ?>
					</label>
					<input type="checkbox"
							id="achievement-<?php echo esc_attr( $weal_profile_achievement_id ); ?>-enabled"
							name="achievements[<?php echo esc_attr( $weal_profile_achievement_id ); ?>][enabled]"
							value="1"
							<?php checked( ! empty( $weal_profile_settings['enabled'] ) ); ?>>
				</div>

				<div class="label-area">
					<label for="achievement-<?php echo esc_attr( $weal_profile_achievement_id ); ?>-target">
						<?php esc_html_e( 'Target comments count:', 'weal-profile' ); ?>
					</label>
					<input type="number"
						id="achievement-<?php echo esc_attr( $weal_profile_achievement_id ); ?>-target"
						name="achievements[<?php echo esc_attr( $weal_profile_achievement_id ); ?>][target]"
						value="<?php echo esc_attr( $weal_profile_settings['target'] ); ?>"
						min="1">
					<p class="description">
						<?php echo esc_html( $weal_description ); ?>
					</p>
				</div>

				<div class="label-area">
					<label for="achievement-<?php echo esc_attr( $weal_profile_achievement_id ); ?>-label">
						<?php esc_html_e( 'Label:', 'weal-profile' ); ?>
					</label>
					<input type="text"
						id="achievement-<?php echo esc_attr( $weal_profile_achievement_id ); ?>-label"
						name="achievements[<?php echo esc_attr( $weal_profile_achievement_id ); ?>][label]"
						value="<?php echo esc_attr( $weal_profile_settings['label'] ); ?>">
				</div>

				<div class="label-area">
					<label><?php esc_html_e( 'Custom Icon:', 'weal-profile' ); ?></label>
					<div class="achievement-icon-preview">
						<?php echo Weal_Profile_Achievements::render_achievement_icon( $weal_profile_settings['icon'] ?? '', 'admin-achievement-icon-preview' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
					<input type="hidden"
						name="achievements[<?php echo esc_attr( $weal_profile_achievement_id ); ?>][icon]"
						value="<?php echo esc_attr( $weal_profile_settings['icon'] ?? '' ); ?>"
						class="achievement-icon-input">
					<input type="hidden"
						name="achievements[<?php echo esc_attr( $weal_profile_achievement_id ); ?>][remove_icon]"
						value="0"
						class="achievement-remove-icon-flag">
					<button type="button" class="button upload-achievement-icon-button">
						<?php esc_html_e( 'Choose Icon', 'weal-profile' ); ?>
					</button>
					<button type="button" class="button remove-achievement-icon-button">
						<?php esc_html_e( 'Remove Icon', 'weal-profile' ); ?>
					</button>
				</div>

				<div class="button-area">
					<input type="submit" class="save-achievement-button" value="<?php esc_attr_e( 'Save', 'weal-profile' ); ?>">
					<span class="achievement-success-notice"><?php esc_html_e( 'Success!', 'weal-profile' ); ?></span>
					<span class="achievement-error-notice"></span>
				</div>
			</div>
		</form>
	</div>
	<?php endforeach; ?>
</div>
