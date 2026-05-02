<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

use WealProfile\Includes\Comment_Votes\Profile_Votes_Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<h2><?php echo esc_html__( 'My comments', 'weal-profile' ); ?></h2>

<?php
/**
 * User comments variable.
 *
 * @var $user_comments
 */

if ( ! empty( $user_comments ) ) {

	echo wp_kses_post( Profile_Votes_Page::render( get_current_user_id() ) );

} else {
	echo esc_html__( 'No comments', 'weal-profile' );
}


?>
