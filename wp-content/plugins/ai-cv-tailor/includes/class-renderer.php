<?php

class AI_CV_Tailor_Renderer {

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
	}

	public function enqueue_public_assets() {
		// Only enqueue on our custom CV page
		if ( get_query_var( 'ai_cv_token' ) ) {
			wp_enqueue_style( 'ai-cv-tailor-public-css', AI_CV_TAILOR_URL . 'assets/public.css', array(), AI_CV_TAILOR_VERSION, 'all' );
			
			// Optional: load google fonts for a modern look
			wp_enqueue_style( 'google-fonts-inter', 'https://fonts.googleapis.css2?family=Inter:wght@400;500;600;700&display=swap', false );
		}
	}
}
