<?php

class AI_CV_Tailor_Router {

	public function init() {
		add_action( 'init', array( $this, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'template_include', array( $this, 'intercept_request' ) );
	}

	public function add_rewrite_rules() {
		// Rule: ^ai-cv/([^/]+)/([^/]+)/([^/]+)/?$
		// $matches[1] = slug (application name)
		// $matches[2] = audience (hr, cto, etc.)
		// $matches[3] = token
		add_rewrite_rule(
			'^ai-cv/([^/]+)/([^/]+)/([^/]+)/?$',
			'index.php?ai_cv_slug=$matches[1]&ai_cv_audience=$matches[2]&ai_cv_token=$matches[3]',
			'top'
		);
	}

	public function add_query_vars( $vars ) {
		$vars[] = 'ai_cv_slug';
		$vars[] = 'ai_cv_audience';
		$vars[] = 'ai_cv_token';
		return $vars;
	}

	public function intercept_request( $template ) {
		$slug = get_query_var( 'ai_cv_slug' );
		$audience = get_query_var( 'ai_cv_audience' );
		$token = get_query_var( 'ai_cv_token' );

		if ( ! empty( $slug ) && ! empty( $audience ) && ! empty( $token ) ) {
			// Find the application by slug
			$args = array(
				'name'        => $slug,
				'post_type'   => 'ai_cv_application',
				'post_status' => 'publish',
				'numberposts' => 1
			);
			$posts = get_posts( $args );

			if ( empty( $posts ) ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				return get_404_template();
			}

			$post = $posts[0];
			$tokens = get_post_meta( $post->ID, '_tokens', true );
			
			// Validate token
			if ( ! is_array( $tokens ) || ! isset( $tokens[ $audience ] ) || $tokens[ $audience ] !== $token ) {
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
