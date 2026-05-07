<?php
/**
 * Contains the relevant methods and functions for the plugin
 *
 * @package weal-profile
 */

use WealProfile\Includes\Comment_Votes\Likes_Vote_Service;
use WealProfile\Includes\Comment_Votes\Profile_Votes_Page;
use WealProfile\Includes\Manager\Settings_Manager;

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

	$weal_profile_likes_service  = new Likes_Vote_Service();
	$weal_profile_vote_data      = $weal_profile_likes_service->get_user_vote_data( $this->current_user );
	$weal_profile_total_likes    = $weal_profile_vote_data['total_likes'] ?? 0;
	$weal_profile_total_dislikes = $weal_profile_vote_data['total_dislikes'] ?? 0;
	$weal_profile_top_comments   = $weal_profile_vote_data['top_comments'] ?? array();

	$weal_profile_settings       = ( new Settings_Manager() )->get_settings();
	$weal_profile_liking_allowed = $weal_profile_settings['comment_votes_enabled'];

	echo wp_kses_post( Profile_Votes_Page::render( get_current_user_id(), $weal_profile_total_likes, $weal_profile_total_dislikes, $weal_profile_top_comments, $weal_profile_liking_allowed ) );

} else {
	echo esc_html__( 'No comments', 'weal-profile' );
}


?>
