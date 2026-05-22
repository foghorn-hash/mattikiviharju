<?php

class AI_CV_Tailor_Router {

	public function init() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_include', array( $this, 'intercept_request' ) );
	}

	public function add_rewrite_rules() {
		// Rule: ^ai-cv/([^/]+)/([^/]+)/([^/]+)/?$
		// $matches[1] = application (post_id)
		// $matches[2] = audience (hr, cto, etc.)
		// $matches[3] = token
		add_rewrite_rule(
			'^ai-cv/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?ai_cv_application=$matches[1]&ai_cv_audience=$matches[2]&ai_cv_token=$matches[3]',
			'top'
		);
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'ai_cv_application';
		$vars[] = 'ai_cv_audience';
		$vars[] = 'ai_cv_token';
		return $vars;
	}

	public function intercept_request( $template ) {
		$application = get_query_var( 'ai_cv_application' );
		$audience    = get_query_var( 'ai_cv_audience' );
		$token       = get_query_var( 'ai_cv_token' );

		if ( ! empty( $application ) && ! empty( $audience ) && ! empty( $token ) ) {
			$post_id = absint( $application );

			if ( ! $post_id ) {
				// Also support slug later if needed.
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				return get_404_template();
			}

			$expected_token = get_post_meta( $post_id, 'ai_cv_' . str_replace( '-', '_', $audience ) . '_token', true );

			if ( ! $expected_token || ! hash_equals( $expected_token, $token ) ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				return get_404_template();
			}

			$post = get_post( $post_id );
			if ( ! $post || $post->post_status !== 'publish' || $post->post_type !== 'ai_cv_application' ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				return get_404_template();
			}

			// Validate expiration
			$settings = get_option( 'ai_cv_settings', array() );
			$expiration_days = intval( $settings['link_expiration'] ?? 30 );
			$post_date = strtotime( $post->post_date );
			$expiry_date = $post_date + ( $expiration_days * DAY_IN_SECONDS );
			
			if ( current_time( 'timestamp' ) > $expiry_date ) {
				wp_die( 'Tämä linkki on vanhentunut.', 'Linkki vanhentunut', array( 'response' => 403 ) );
			}
			
			// Track view
			do_action( 'ai_cv_tailor_track_view', $post->ID, $audience, $token );

			// Set global data for the renderer
			global $ai_cv_tailor_data;
			$ai_cv_tailor_data = array(
				'post'     => $post,
				'audience' => $audience,
				'settings' => $settings
			);

			// Return the custom template instead of a theme template
			return AI_CV_TAILOR_DIR . 'templates/public-cv.php';
		}

		return $template;
	}
}
