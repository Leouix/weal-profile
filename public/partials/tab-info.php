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

<h2>User Info</h2>

<form id="user-data-form" enctype="multipart/form-data">


	<?php if ( isset( $user_data_obj->display_name ) ) { ?>
		<div class="input-area">
			<label for="display-name">Display Name:</label>
			<input
					id="display-name"
					name="display_name"
					oninput="editingUserData(this)"
					data-orig="<?php echo esc_html( $user_data_obj->display_name ); ?>"
					value="<?php echo esc_html( $user_data_obj->display_name ); ?>"
			>
		</div>
	<?php } ?>



	<?php if ( isset( $user_data_obj->nickname ) ) { ?>
		<div class="input-area">
			<label for="nickname">Nickname:</label>
			<input
					id="nickname"
					name="nickname"
					oninput="editingUserData(this)"
					data-orig="<?php echo esc_html( $user_data_obj->nickname ); ?>"
					value="<?php echo esc_html( $user_data_obj->nickname ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->first_name ) ) { ?>
		<div class="input-area">
			<label for="first-name">First Name:</label>
			<input
					id="first-name"
					name="first_name"
					oninput="editingUserData(this)"
					data-orig="<?php echo esc_html( $user_data_obj->first_name ); ?>"
					value="<?php echo esc_html( $user_data_obj->first_name ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->last_name ) ) { ?>
		<div class="input-area">
			<label for="last-name">Last Name:</label>
			<input
					id="last-name"
					name="last_name"
					oninput="editingUserData(this)"
					data-orig="<?php echo esc_html( $user_data_obj->last_name ); ?>"
					value="<?php echo esc_html( $user_data_obj->last_name ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->user_url ) ) { ?>
		<div class="input-area">
			<label for="user-url">Web-Site:</label>
			<input
					id="user-url"
					name="user_url"
					oninput="editingUserData(this)"
					data-orig="<?php echo esc_html( $user_data_obj->user_url ); ?>"
					value="<?php echo esc_html( $user_data_obj->user_url ); ?>"
			>
		</div>
	<?php } ?>

	<?php if ( isset( $user_data_obj->description ) ) { ?>
		<div class="input-area">
			<label for="description">Description:</label>
			<input
					id="description"
					name="description"
					oninput="editingUserData(this)"
					data-orig="<?php echo esc_html( $user_data_obj->description ); ?>"
					value="<?php echo esc_html( $user_data_obj->description ); ?>"
			>
		</div>
	<?php } ?>

	<input id="form-user-button" class="button  primary-button" type="submit" value="Save">

</form>