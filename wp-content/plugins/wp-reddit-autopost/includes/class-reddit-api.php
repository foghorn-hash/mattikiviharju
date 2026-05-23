<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Reddit_Autopost_API {

	public function submit_link( $title, $url ) {
		$client_id     = get_option( 'wp_reddit_autopost_client_id' );
		$client_secret = get_option( 'wp_reddit_autopost_client_secret' );
		$username      = get_option( 'wp_reddit_autopost_username' );
		$password      = get_option( 'wp_reddit_autopost_password' );
		$subreddit     = get_option( 'wp_reddit_autopost_subreddit' );

		if ( empty( $client_id ) || empty( $client_secret ) || empty( $username ) || empty( $password ) || empty( $subreddit ) ) {
			return false; // Missing settings
		}

		$access_token = $this->get_access_token( $client_id, $client_secret, $username, $password );
		if ( ! $access_token ) {
			return false;
		}

		$api_url = 'https://oauth.reddit.com/api/submit';
		$args    = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'User-Agent'    => 'WP_Reddit_Autopost/1.0.0 by ' . $username,
			),
			'body'    => array(
				'api_type' => 'json',
				'kind'     => 'link',
				'sr'       => $subreddit,
				'title'    => $title,
				'url'      => $url,
			),
			'timeout' => 15,
		);

		$response = wp_remote_post( $api_url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'Reddit API Submit Error: ' . $response->get_error_message() );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( isset( $body['json']['errors'] ) && ! empty( $body['json']['errors'] ) ) {
			error_log( 'Reddit API Submit Error Details: ' . print_r( $body['json']['errors'], true ) );
			return false;
		}

		return true;
	}

	private function get_access_token( $client_id, $client_secret, $username, $password ) {
		$auth_url = 'https://www.reddit.com/api/v1/access_token';
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
				'User-Agent'    => 'WP_Reddit_Autopost/1.0.0 by ' . $username,
			),
			'body'    => array(
				'grant_type' => 'password',
				'username'   => $username,
				'password'   => $password,
			),
			'timeout' => 15,
		);

		$response = wp_remote_post( $auth_url, $args );

		if ( is_wp_error( $response ) ) {
			error_log( 'Reddit Auth Error: ' . $response->get_error_message() );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['access_token'] ) ) {
			return $body['access_token'];
		}

		return false;
	}
}
