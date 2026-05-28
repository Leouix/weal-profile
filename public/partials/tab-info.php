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
 * User data object.
 *
 * @var stdClass $user_data_obj
 */
?>

<h2><?php echo esc_html__( 'User Info', 'weal-profile' ); ?></h2>

<form id="user-data-form" enctype="multipart/form-data">


	<?php if ( isset( $user_data_obj->display_name ) ) { ?>
		<div class="input-area">
			<label for="display-name"><?php echo esc_html__( 'Display Name:', 'weal-profile' ); ?></label>
			<input
					id="display-name"
					name="display_name"
					data-wp-action="edit-user-data"
					data-orig="<?php echo esc_html( $user_data_obj->display_name ); ?>"
					value="<?php echo esc_html( $user_data_obj->display_name ); ?>"
			>
		</div>
	<?php } ?>



	<?php if ( isset( $user_data_obj->nickname ) ) { ?>
		<div class="input-area">
			<label for="nickname"><?php echo esc_html__( 'Nickname:', 'weal-profile' ); ?></label>
			<input
					id="nickname"
					name="nickname"
					data-wp-action="edit-user-data"
					data-orig="<?php echo esc_html( $user_data_obj->nickname ); ?>"
					value="<?php echo esc_html( $user_data_obj->nickname ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->first_name ) ) { ?>
		<div class="input-area">
			<label for="first-name"><?php echo esc_html__( 'First Name:', 'weal-profile' ); ?></label>
			<input
					id="first-name"
					name="first_name"
					data-wp-action="edit-user-data"
					data-orig="<?php echo esc_html( $user_data_obj->first_name ); ?>"
					value="<?php echo esc_html( $user_data_obj->first_name ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->last_name ) ) { ?>
		<div class="input-area">
			<label for="last-name"><?php echo esc_html__( 'Last Name:', 'weal-profile' ); ?></label>
			<input
					id="last-name"
					name="last_name"
					data-wp-action="edit-user-data"
					data-orig="<?php echo esc_html( $user_data_obj->last_name ); ?>"
					value="<?php echo esc_html( $user_data_obj->last_name ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->user_url ) ) { ?>
		<div class="input-area">
			<label for="user-url"><?php echo esc_html__( 'Web-Site:', 'weal-profile' ); ?></label>
			<input
					id="user-url"
					name="user_url"
					data-wp-action="edit-user-data"
					data-orig="<?php echo esc_html( $user_data_obj->user_url ); ?>"
					value="<?php echo esc_html( $user_data_obj->user_url ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->description ) ) { ?>
		<div class="input-area">
			<label for="description"><?php echo esc_html__( 'Description:', 'weal-profile' ); ?></label>
			<input
					id="description"
					name="description"
					data-wp-action="edit-user-data"
					data-orig="<?php echo esc_html( $user_data_obj->description ); ?>"
					value="<?php echo esc_html( $user_data_obj->description ); ?>"
			>
		</div>
	<?php } ?>

	<input id="form-user-button" class="button  primary-button" type="submit" value="<?php echo esc_attr__( 'Save', 'weal-profile' ); ?>">

</form>