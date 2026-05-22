<?php

class AI_CV_Tailor_Admin {

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		
		add_action( 'wp_ajax_ai_cv_tailor_generate', array( $this, 'ajax_generate' ) );
		add_action( 'wp_ajax_ai_cv_tailor_save', array( $this, 'ajax_save' ) );
	}

	public function add_plugin_admin_menu() {
		// Main menu
		add_menu_page(
			'AI CV Tailor', 
			'AI CV Tailor', 
			'manage_options', 
			'ai-cv-tailor', 
			array( $this, 'display_main_page' ), 
			'dashicons-media-document', 
			25
		);

		// Submenus
		add_submenu_page(
			'ai-cv-tailor',
			'Työpaikkailmoitukset',
			'Työpaikkailmoitukset',
			'manage_options',
			'edit.php?post_type=ai_cv_application'
		);
		
		add_submenu_page(
			'ai-cv-tailor',
			'Luo uusi hakemus',
			'Luo uusi hakemus',
			'manage_options',
			'ai-cv-tailor-new',
			array( $this, 'display_new_application_page' )
		);

		add_submenu_page(
			'ai-cv-tailor',
			'CV-data',
			'CV-data',
			'manage_options',
			'ai-cv-tailor-data',
			array( $this, 'display_cv_data_page' )
		);
		
		add_submenu_page(
			'ai-cv-tailor',
			'Asetukset',
			'Asetukset',
			'manage_options',
			'ai-cv-tailor-settings',
			array( $this, 'display_settings_page' )
		);

		add_submenu_page(
			'ai-cv-tailor',
			'Tilastot',
			'Tilastot',
			'manage_options',
			'ai-cv-tailor-statistics',
			array( $this, 'display_statistics_page' )
		);
		
		// Remove the duplicate "AI CV Tailor" submenu that WP auto-creates
		remove_submenu_page( 'ai-cv-tailor', 'ai-cv-tailor' );
	}

	public function enqueue_styles_scripts( $hook ) {
		// Only enqueue on our plugin pages
		if ( strpos( $hook, 'ai-cv-tailor' ) === false ) {
			return;
		}
		
		wp_enqueue_style( 'ai-cv-tailor-admin-css', AI_CV_TAILOR_URL . 'assets/admin.css', array(), AI_CV_TAILOR_VERSION, 'all' );
		wp_enqueue_script( 'ai-cv-tailor-admin-js', AI_CV_TAILOR_URL . 'assets/admin.js', array( 'jquery' ), AI_CV_TAILOR_VERSION, true );
		
		wp_localize_script( 'ai-cv-tailor-admin-js', 'aiCvTailorObj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ai_cv_tailor_nonce' )
		) );
	}

	public function display_main_page() {
		// Redirect to Jobs list
		echo '<script>window.location.href="' . admin_url('edit.php?post_type=ai_cv_application') . '";</script>';
	}
	
	public function display_new_application_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/admin-application-edit.php';
	}
	
	public function display_cv_data_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/admin-cv-data.php';
	}
	
	public function display_settings_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/admin-settings.php';
	}
	
	public function display_statistics_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/admin-statistics.php';
	}

	public function ajax_generate() {
		check_ajax_referer( 'ai_cv_tailor_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Ei oikeuksia' );
		}

		$job_description = isset( $_POST['job_description'] ) ? sanitize_textarea_field( $_POST['job_description'] ) : '';
		$language        = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : 'fi';

		$settings = get_option( 'ai_cv_settings', array() );
		if ( empty( $settings['delivery_terms_url'] ) || empty( $settings['privacy_policy_url'] ) ) {
			wp_send_json_error( 'Delivery Terms ja Privacy Policy pitää lisätä asetuksiin ennen julkaisua.' );
		}

		if ( empty( $job_description ) ) {
			wp_send_json_error( 'Työpaikkailmoitus puuttuu' );
		}

		$openai = new AI_CV_Tailor_OpenAI();
		$result = $openai->generate_analysis_and_content( $job_description, $language );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	public function ajax_save() {
		check_ajax_referer( 'ai_cv_tailor_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Ei oikeuksia' );
		}

		$company_name = isset( $_POST['company_name'] ) ? sanitize_text_field( $_POST['company_name'] ) : '';
		$job_title    = isset( $_POST['job_title'] ) ? sanitize_text_field( $_POST['job_title'] ) : '';
		$job_url      = isset( $_POST['job_url'] ) ? esc_url_raw( $_POST['job_url'] ) : '';
		$job_desc     = isset( $_POST['job_description'] ) ? sanitize_textarea_field( $_POST['job_description'] ) : '';
		$language     = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : 'fi';
		$json_data    = isset( $_POST['json_data'] ) ? wp_unslash( $_POST['json_data'] ) : '';
		
		$settings = get_option( 'ai_cv_settings', array() );
		if ( empty( $settings['delivery_terms_url'] ) || empty( $settings['privacy_policy_url'] ) ) {
			wp_send_json_error( 'Delivery Terms ja Privacy Policy pitää lisätä asetuksiin ennen julkaisua.' );
		}

		if ( empty( $company_name ) || empty( $job_title ) || empty( $json_data ) ) {
			wp_send_json_error( 'Pakollisia tietoja puuttuu' );
		}

		$parsed_data = json_decode( $json_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( 'Virheellinen JSON-data' );
		}

		$post_id = wp_insert_post( array(
			'post_title'  => $company_name . ' - ' . $job_title,
			'post_type'   => 'ai_cv_application',
			'post_status' => 'publish',
		) );

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( 'Hakemuksen tallennus epäonnistui' );
		}

		// Save meta
		update_post_meta( $post_id, '_company_name', $company_name );
		update_post_meta( $post_id, '_job_title', $job_title );
		update_post_meta( $post_id, '_job_url', $job_url );
		update_post_meta( $post_id, '_job_description', $job_desc );
		update_post_meta( $post_id, '_language', $language );
		update_post_meta( $post_id, '_openai_analysis', $json_data );

		// Generate tokens
		$audiences = array( 'hr', 'cto', 'ceo', 'team_lead', 'recruiter' );
		$tokens = array();
		foreach ( $audiences as $audience ) {
			$tokens[ $audience ] = wp_generate_password( 32, false );
		}
		update_post_meta( $post_id, '_tokens', $tokens );

		// Generate URLs
		$post = get_post( $post_id );
		$slug = $post->post_name;
		$urls = array();
		foreach ( $tokens as $audience => $token ) {
			$urls[ $audience ] = home_url( "/ai-cv/{$slug}/{$audience}/{$token}" );
		}

		wp_send_json_success( array(
			'post_id' => $post_id,
			'urls'    => $urls
		) );
	}
}
