<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Reddit_Autopost_Handler {

	public function __construct() {
		add_action( 'transition_post_status', array( $this, 'on_post_published' ), 10, 3 );
	}

	public function on_post_published( $new_status, $old_status, $post ) {
		// Only trigger when a post transitions to "publish"
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Only trigger for standard 'post' type
		if ( 'post' !== $post->post_type ) {
			return;
		}

		// Check if it's already been posted to Reddit
		$already_posted = get_post_meta( $post->ID, '_posted_to_reddit', true );
		if ( $already_posted ) {
			return;
		}

		// Get post title and URL
		$title = get_the_title( $post->ID );
		$url   = get_permalink( $post->ID );

		// Instantiate API and send
		$api = new WP_Reddit_Autopost_API();
		$success = $api->submit_link( $title, $url );

		if ( $success ) {
			// Mark as posted so it won't be sent again
			update_post_meta( $post->ID, '_posted_to_reddit', current_time( 'mysql' ) );
		}
	}
}
