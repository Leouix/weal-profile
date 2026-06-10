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

$achievements_data = Weal_Profile_Achievements::get_admin_achievements_data();
?>
<div class="au-container achievement-container">
	<?php foreach ( $achievements_data as $achievement_id => $settings ) : ?>
		<form class="achievement-form">
			<?php wp_nonce_field( 'weal_profile_achievements_save', 'weal_profile_achievements_nonce' ); ?>
			<input type="hidden" name="achievement_id" value="<?php echo esc_attr( $achievement_id ); ?>">

			<div class="achievement-block">
				<h3><?php echo Weal_Profile_Achievements::render_achievement_icon( $settings['icon'], 'admin-achievement-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $settings['label'] ); ?></h3>

				<div class="label-area">
					<input type="hidden" name="achievements[<?php echo esc_attr( $achievement_id ); ?>][enabled]" value="0">
					<label for="achievement-<?php echo esc_attr( $achievement_id ); ?>-enabled">
						<?php esc_html_e( 'Enable achievement', 'weal-profile' ); ?>
					</label>
                    <input type="checkbox"
                           id="achievement-<?php echo esc_attr( $achievement_id ); ?>-enabled"
                           name="achievements[<?php echo esc_attr( $achievement_id ); ?>][enabled]"
                           value="1"
                             <?php checked( ! empty( $settings['enabled'] ) ); ?>>
				</div>

				<div class="label-area">
					<label for="achievement-<?php echo esc_attr( $achievement_id ); ?>-target">
						<?php esc_html_e( 'Target comments count:', 'weal-profile' ); ?>
					</label>
					<input type="number"
						id="achievement-<?php echo esc_attr( $achievement_id ); ?>-target"
						name="achievements[<?php echo esc_attr( $achievement_id ); ?>][target]"
						value="<?php echo esc_attr( $settings['target'] ); ?>"
						min="1">
				</div>

				<div class="label-area">
					<label for="achievement-<?php echo esc_attr( $achievement_id ); ?>-label">
						<?php esc_html_e( 'Label:', 'weal-profile' ); ?>
					</label>
					<input type="text"
						id="achievement-<?php echo esc_attr( $achievement_id ); ?>-label"
						name="achievements[<?php echo esc_attr( $achievement_id ); ?>][label]"
						value="<?php echo esc_attr( $settings['label'] ); ?>">
				</div>

				<div class="button-area">
					<input type="submit" class="save-achievement-button" value="<?php esc_attr_e( 'Save', 'weal-profile' ); ?>">
					<span class="achievement-success-notice"><?php esc_html_e( 'Success!', 'weal-profile' ); ?></span>
					<span class="achievement-error-notice"></span>
				</div>
			</div>
		</form>
	<?php endforeach; ?>
</div>
