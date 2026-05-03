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

    $likes_service = new Likes_Vote_Service();
    $vote_data     = $likes_service->get_user_vote_data( $this->current_user );
    $total_likes    = $vote_data['total_likes'] ?? 0;
    $total_dislikes = $vote_data['total_dislikes'] ?? 0;
    $top_comments   = $vote_data['top_comments'] ?? array();

    $settings            = ( new Settings_Manager() )->get_settings();
    $liking_allowed = $settings['comment_votes_enabled'] ?? true;


    echo wp_kses_post( Profile_Votes_Page::render( get_current_user_id(), $total_likes, $total_dislikes, $top_comments, $liking_allowed ) );

} else {
	echo esc_html__( 'No comments', 'weal-profile' );
}


?>
