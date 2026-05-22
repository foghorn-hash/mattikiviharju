<?php

class AI_CV_Tailor_Autopilot_Admin {

	public function init() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widgets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		add_action( 'wp_ajax_ai_cv_tailor_autopilot_analyze', array( $this, 'ajax_analyze' ) );
		add_action( 'wp_ajax_ai_cv_tailor_autopilot_reject', array( $this, 'ajax_reject' ) );
		add_action( 'wp_ajax_ai_cv_tailor_autopilot_generate', array( $this, 'ajax_generate_application' ) );
	}

	public function add_admin_menu() {
		// Parent menu is 'ai-cv-tailor'
		add_submenu_page(
			'ai-cv-tailor',
			'Freelance Autopilot',
			'Freelance Autopilot',
			'manage_options',
			'ai-cv-tailor-autopilot',
			array( $this, 'display_dashboard_page' )
		);
		add_submenu_page(
			'ai-cv-tailor',
			'Toimeksiantolähteet',
			'Toimeksiantolähteet',
			'manage_options',
			'ai-cv-tailor-autopilot-sources',
			array( $this, 'display_sources_page' )
		);
		add_submenu_page(
			'ai-cv-tailor',
			'Löydetyt toimeksiannot',
			'Löydetyt toimeksiannot',
			'manage_options',
			'ai-cv-tailor-autopilot-opportunities',
			array( $this, 'display_opportunities_page' )
		);
		add_submenu_page(
			'ai-cv-tailor',
			'Hakujonot',
			'Hakujonot',
			'manage_options',
			'ai-cv-tailor-autopilot-queues',
			array( $this, 'display_queues_page' )
		);
		add_submenu_page(
			'ai-cv-tailor',
			'Autopilot Asetukset',
			'Autopilot Asetukset',
			'manage_options',
			'ai-cv-tailor-autopilot-settings',
			array( $this, 'display_settings_page' )
		);
		add_submenu_page(
			'ai-cv-tailor',
			'Debug Log',
			'Debug Log',
			'manage_options',
			'ai-cv-tailor-autopilot-debug-log',
			array( $this, 'display_debug_log_page' )
		);
	}

	public function add_dashboard_widgets() {
		wp_add_dashboard_widget(
			'ai_cv_tailor_autopilot_widget',
			'Freelance Autopilot AI',
			array( $this, 'render_dashboard_widget' )
		);
	}

	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'ai-cv-tailor-autopilot' ) === false ) {
			return;
		}
		
		wp_enqueue_script( 'ai-cv-tailor-autopilot-js', AI_CV_TAILOR_URL . 'assets/autopilot-admin.js', array( 'jquery' ), AI_CV_TAILOR_VERSION, true );
		wp_localize_script( 'ai-cv-tailor-autopilot-js', 'aiCvTailorAutopilotObj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ai_cv_tailor_autopilot_nonce' )
		) );
	}

	public function display_dashboard_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/autopilot-dashboard.php';
	}

	public function display_sources_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/autopilot-sources.php';
	}

	public function display_opportunities_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/autopilot-opportunities.php';
	}

	public function display_queues_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/autopilot-queues.php';
	}

	public function display_settings_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/autopilot-settings.php';
	}

	public function display_debug_log_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/autopilot-debug-log.php';
	}

	public function render_dashboard_widget() {
		$today = date('Y-m-d');
		
		$args_new = array(
			'post_type' => 'freelance_job',
			'post_status' => 'any',
			'date_query' => array(
				array(
					'year'  => date('Y'),
					'month' => date('m'),
					'day'   => date('d'),
				),
			),
		);
		$query_new = new WP_Query( $args_new );
		$new_count = $query_new->found_posts;

		$args_good = array(
			'post_type' => 'freelance_job',
			'meta_key' => 'status',
			'meta_value' => 'Good Match',
		);
		$query_good = new WP_Query( $args_good );
		$good_count = $query_good->found_posts;
		
		$args_waiting = array(
			'post_type' => 'freelance_job',
			'meta_query' => array(
				array(
					'key' => 'status',
					'value' => array('Good Match', 'New', 'Analyzed'),
					'compare' => 'IN'
				)
			)
		);
		$query_waiting = new WP_Query( $args_waiting );
		$waiting_count = $query_waiting->found_posts;

		$args_followup = array(
			'post_type' => 'freelance_job',
			'meta_key' => 'follow_up_date',
			'meta_value' => $today,
			'compare' => '<='
		);
		$query_followup = new WP_Query( $args_followup );
		$followup_count = $query_followup->found_posts;

		echo '<div class="autopilot-dashboard-widget">';
		echo '<p><strong>Uudet tänään:</strong> ' . esc_html( $new_count ) . '</p>';
		echo '<p><strong>Hyvät matchit:</strong> ' . esc_html( $good_count ) . '</p>';
		echo '<p><strong>Hakemusta odottavat:</strong> ' . esc_html( $waiting_count ) . '</p>';
		echo '<p><strong>Follow-upit tänään:</strong> ' . esc_html( $followup_count ) . '</p>';
		echo '<a href="' . admin_url('admin.php?page=ai-cv-tailor-autopilot') . '" class="button button-primary">Avaa Autopilot</a>';
		echo '</div>';
	}
	
	public function ajax_analyze() {
		check_ajax_referer( 'ai_cv_tailor_autopilot_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Ei oikeuksia' );
		}
		
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( 'Post ID puuttuu' );
		}
		
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-openai.php';
		$openai = new AI_CV_Tailor_Autopilot_OpenAI();
		$result = $openai->analyze_opportunity( $post_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		
		wp_send_json_success( $result );
	}
	
	public function ajax_reject() {
		check_ajax_referer( 'ai_cv_tailor_autopilot_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Ei oikeuksia' );
		}
		
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( 'Post ID puuttuu' );
		}
		
		update_post_meta( $post_id, 'status', 'Rejected' );
		wp_send_json_success();
	}
	
	public function ajax_generate_application() {
		check_ajax_referer( 'ai_cv_tailor_autopilot_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Ei oikeuksia' );
		}
		
		$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( 'Post ID puuttuu' );
		}
		
		// Will use the existing OpenAI logic or new Autopilot logic to generate
		// an ai_cv_application post and link it.
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-openai.php';
		$openai = new AI_CV_Tailor_Autopilot_OpenAI();
		$result = $openai->generate_application_from_opportunity( $post_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		
		wp_send_json_success( $result );
	}
}
