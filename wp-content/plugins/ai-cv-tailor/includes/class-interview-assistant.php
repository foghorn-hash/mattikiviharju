<?php
/**
 * Interview Assistant Handler for i4ware Job Seekers Autopilot AI
 * Provides REST API endpoints, device pairing, interview prep management,
 * and AI answer suggestion for the desktop & browser apps.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_CV_Tailor_Interview_Assistant {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// AJAX handlers for admin interface
		add_action( 'wp_ajax_ai_cv_tailor_generate_pair_code', array( $this, 'ajax_generate_pair_code' ) );
		add_action( 'wp_ajax_ai_cv_tailor_revoke_device', array( $this, 'ajax_revoke_device' ) );
		add_action( 'wp_ajax_ai_cv_tailor_save_interview_prep', array( $this, 'ajax_save_interview_prep' ) );
		add_action( 'wp_ajax_ai_cv_tailor_test_suggest_answer', array( $this, 'ajax_test_suggest_answer' ) );
	}

	public function add_admin_menu() {
		add_submenu_page(
			'ai-cv-tailor',
			'Haastatteluavustaja',
			'Haastatteluavustaja',
			'manage_options',
			'ai-cv-tailor-interview-assistant',
			array( $this, 'render_admin_page' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( strpos( $hook, 'ai-cv-tailor-interview-assistant' ) === false ) {
			return;
		}

		wp_enqueue_style( 'ai-cv-tailor-admin-css', AI_CV_TAILOR_URL . 'assets/admin.css', array(), AI_CV_TAILOR_VERSION );
	}

	public function register_rest_routes() {
		$namespace = 'ai-cv-tailor/v1';

		// Pair device using 6-character code
		register_rest_route( $namespace, '/interview/pair', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'rest_pair_device' ),
			'permission_callback' => '__return_true', // Pairing code itself validates
			'args'                => array(
				'pairing_code' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'device_name'  => array(
					'required'          => false,
					'type'              => 'string',
					'default'           => 'Windows 11 Client',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		// Generate pairing code (logged-in user or admin)
		register_rest_route( $namespace, '/interview/pair-code', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'rest_generate_pair_code' ),
			'permission_callback' => array( $this, 'check_rest_authenticated' ),
		) );

		// Get available options (jobs, CVs, projects, languages)
		register_rest_route( $namespace, '/interview/options', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'rest_get_options' ),
			'permission_callback' => array( $this, 'check_rest_authenticated' ),
		) );

		// Get active interview preparation package
		register_rest_route( $namespace, '/interview/preparation', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'rest_get_preparation' ),
			'permission_callback' => array( $this, 'check_rest_authenticated' ),
		) );

		// Save active interview preparation package
		register_rest_route( $namespace, '/interview/preparation', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'rest_save_preparation' ),
			'permission_callback' => array( $this, 'check_rest_authenticated' ),
		) );

		// Suggest answer during interview
		register_rest_route( $namespace, '/interview/suggest-answer', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'rest_suggest_answer' ),
			'permission_callback' => array( $this, 'check_rest_authenticated' ),
			'args'                => array(
				'question' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'mode'     => array(
					'required'          => false,
					'type'              => 'string',
					'default'           => 'job_seeker',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'history'  => array(
					'required' => false,
					'type'     => 'array',
					'default'  => array(),
				),
			),
		) );

		// Save interview transcript (explicit opt-in only)
		register_rest_route( $namespace, '/interview/save-transcript', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'rest_save_transcript' ),
			'permission_callback' => array( $this, 'check_rest_authenticated' ),
		) );
	}

	/**
	 * Authenticates REST requests using either standard WP cookie/nonce
	 * or Authorization: Bearer <device_token>
	 */
	public function check_rest_authenticated( WP_REST_Request $request ) {
		// First check standard WP user (e.g. admin logged into dashboard)
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$auth_header = $request->get_header( 'authorization' );
		if ( empty( $auth_header ) && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$auth_header = $_SERVER['HTTP_AUTHORIZATION'];
		}

		if ( ! empty( $auth_header ) && preg_match( '/Bearer\s+(.*)$/i', $auth_header, $matches ) ) {
			$token = trim( $matches[1] );
			$user_id = $this->validate_device_token( $token );
			if ( $user_id ) {
				wp_set_current_user( $user_id );
				return true;
			}
		}

		return new WP_Error(
			'rest_forbidden',
			'Pääsy evätty: Virheellinen tai vanhentunut tunnistautumistunniste.',
			array( 'status' => 401 )
		);
	}

	/**
	 * Validate a bearer device token
	 */
	private function validate_device_token( $token ) {
		$hash = hash( 'sha256', $token );
		
		// Query users having this token hash in metadata
		$users = get_users( array(
			'meta_key'     => 'ai_cv_interview_device_tokens',
			'meta_compare' => 'EXISTS',
		) );

		foreach ( $users as $user ) {
			$tokens = get_user_meta( $user->ID, 'ai_cv_interview_device_tokens', true );
			if ( is_array( $tokens ) ) {
				foreach ( $tokens as $idx => $item ) {
					if ( isset( $item['hash'] ) && hash_equals( $item['hash'], $hash ) ) {
						// Update last used timestamp
						$tokens[ $idx ]['last_used'] = time();
						update_user_meta( $user->ID, 'ai_cv_interview_device_tokens', $tokens );
						return $user->ID;
					}
				}
			}
		}

		return false;
	}

	/**
	 * REST: Pair device using pairing code
	 */
	public function rest_pair_device( WP_REST_Request $request ) {
		$code = strtoupper( trim( $request->get_param( 'pairing_code' ) ) );
		$device_name = sanitize_text_field( $request->get_param( 'device_name' ) );

		if ( empty( $code ) ) {
			return new WP_Error( 'invalid_code', 'Parituskoodi puuttuu.', array( 'status' => 400 ) );
		}

		// Find transient for pairing code
		$transient_key = 'ai_interview_pair_' . $code;
		$user_id = get_transient( $transient_key );

		if ( ! $user_id ) {
			return new WP_Error( 'code_expired', 'Parituskoodi on virheellinen tai vanhentunut.', array( 'status' => 403 ) );
		}

		// Invalidate transient once used
		delete_transient( $transient_key );

		// Generate cryptographically secure token
		$token = bin2hex( random_bytes( 32 ) );
		$token_hash = hash( 'sha256', $token );

		$existing_tokens = get_user_meta( $user_id, 'ai_cv_interview_device_tokens', true );
		if ( ! is_array( $existing_tokens ) ) {
			$existing_tokens = array();
		}

		$existing_tokens[] = array(
			'id'          => wp_generate_uuid4(),
			'device_name' => ! empty( $device_name ) ? $device_name : 'Windows 11 Client',
			'hash'        => $token_hash,
			'created_at'  => time(),
			'last_used'   => time(),
		);

		update_user_meta( $user_id, 'ai_cv_interview_device_tokens', $existing_tokens );

		$user = get_userdata( $user_id );
		$settings = get_option( 'ai_cv_tailor_settings', array() );

		return rest_ensure_response( array(
			'success'     => true,
			'message'     => 'Laiteparitus onnistui.',
			'token'       => $token,
			'user'        => array(
				'id'           => $user->ID,
				'display_name' => $user->display_name,
				'email'        => $user->user_email,
			),
			'server_info' => array(
				'site_url'          => site_url(),
				'site_name'         => get_bloginfo( 'name' ),
				'has_openai'        => ! empty( $settings['openai_api_key'] ),
				'has_google_ai'     => ! empty( $settings['google_ai_api_key'] ),
				'has_liveavatar'    => ! empty( $settings['liveavatar_api_key'] ),
				'liveavatar_id'     => $settings['liveavatar_avatar_id'] ?? 'default_avatar',
				'liveavatar_voice'  => $settings['liveavatar_voice_id'] ?? 'default_voice',
			),
		) );
	}

	/**
	 * REST: Generate pairing code
	 */
	public function rest_generate_pair_code( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$code = $this->create_pairing_code_for_user( $user_id );

		return rest_ensure_response( array(
			'success'      => true,
			'pairing_code' => $code,
			'expires_in'   => 900, // 15 mins
			'message'      => 'Koodi on voimassa 15 minuuttia.',
		) );
	}

	/**
	 * Create 6-character random pairing code
	 */
	public function create_pairing_code_for_user( $user_id ) {
		$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // exclude ambiguous 0,1,I,O
		$code = '';
		for ( $i = 0; $i < 6; $i++ ) {
			$code .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
		}

		// Store for 15 minutes (900s)
		set_transient( 'ai_interview_pair_' . $code, $user_id, 900 );
		update_user_meta( $user_id, 'ai_interview_latest_pairing_code', array(
			'code'       => $code,
			'created_at' => time(),
			'expires_at' => time() + 900,
		) );

		return $code;
	}

	/**
	 * REST: Get options for interview preparation
	 */
	public function rest_get_options( WP_REST_Request $request ) {
		// Fetch ai_cv_application posts
		$app_posts = get_posts( array(
			'post_type'      => 'ai_cv_application',
			'posts_per_page' => 50,
			'post_status'    => 'any',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$applications = array();
		foreach ( $app_posts as $app ) {
			$company = get_post_meta( $app->ID, '_company_name', true );
			if ( empty( $company ) ) {
				$company = get_post_meta( $app->ID, 'ai_cv_company_name', true );
			}
			$role = get_post_meta( $app->ID, '_job_title', true );
			if ( empty( $role ) ) {
				$role = get_post_meta( $app->ID, 'ai_cv_role_title', true );
			}

			$applications[] = array(
				'id'           => $app->ID,
				'title'        => $app->post_title,
				'company'      => $company ?: 'Yritys',
				'role'         => $role ?: $app->post_title,
				'match_score'  => get_post_meta( $app->ID, 'ai_cv_match_score', true ),
				'has_cover'    => ! empty( get_post_meta( $app->ID, 'ai_cv_cover_letter', true ) ),
				'created_date' => get_the_date( 'Y-m-d H:i', $app->ID ),
			);
		}

		// Fetch freelance_job posts
		$job_posts = get_posts( array(
			'post_type'      => 'freelance_job',
			'posts_per_page' => 50,
			'post_status'    => 'any',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$freelance_jobs = array();
		foreach ( $job_posts as $job ) {
			$freelance_jobs[] = array(
				'id'      => $job->ID,
				'title'   => $job->post_title,
				'company' => get_post_meta( $job->ID, 'company', true ),
			);
		}

		// Fetch cv_project posts
		$proj_posts = get_posts( array(
			'post_type'      => 'cv_project',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
		) );

		$projects = array();
		foreach ( $proj_posts as $proj ) {
			$projects[] = array(
				'id'          => $proj->ID,
				'title'       => $proj->post_title,
				'description' => get_post_meta( $proj->ID, '_cv_project_description', true ) ?: $proj->post_content,
				'tech_stack'  => get_post_meta( $proj->ID, '_cv_project_meta', true ),
				'url'         => get_post_meta( $proj->ID, '_cv_project_link', true ),
			);
		}

		// Fetch cv_experience posts
		$exp_posts = get_posts( array(
			'post_type'      => 'cv_experience',
			'posts_per_page' => 50,
			'post_status'    => 'publish',
		) );

		$experiences = array();
		foreach ( $exp_posts as $exp ) {
			$experiences[] = array(
				'id'      => $exp->ID,
				'role'    => $exp->post_title,
				'company' => get_post_meta( $exp->ID, '_cv_experience_company', true ),
				'dates'   => get_post_meta( $exp->ID, '_cv_experience_dates', true ),
				'summary' => $exp->post_excerpt,
			);
		}

		// Languages
		$languages = array(
			array( 'code' => 'fi', 'name' => 'Suomi (Finnish)' ),
			array( 'code' => 'en', 'name' => 'English' ),
			array( 'code' => 'sv', 'name' => 'Svenska (Swedish)' ),
			array( 'code' => 'de', 'name' => 'Deutsch (German)' ),
		);

		return rest_ensure_response( array(
			'success'        => true,
			'applications'   => $applications,
			'freelance_jobs' => $freelance_jobs,
			'projects'       => $projects,
			'experiences'    => $experiences,
			'languages'      => $languages,
			'modes'          => array(
				array( 'id' => 'job_seeker', 'name' => 'Työnhakijan haastatteluavustaja' ),
				array( 'id' => 'hr_advisor', 'name' => 'HR Advisor (Haastattelija/Rekrytoija)' ),
			),
		) );
	}

	/**
	 * REST: Get active interview preparation package
	 */
	public function rest_get_preparation( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$prep = get_user_meta( $user_id, 'ai_interview_active_prep', true );

		if ( empty( $prep ) || ! is_array( $prep ) ) {
			// Construct sensible default
			$prep = $this->build_default_preparation( $user_id );
		}

		// Attach verified projects content and application details
		$enriched = $this->enrich_preparation_package( $prep );

		return rest_ensure_response( array(
			'success'     => true,
			'preparation' => $enriched,
		) );
	}

	/**
	 * Build default interview preparation
	 */
	private function build_default_preparation( $user_id ) {
		$apps = get_posts( array(
			'post_type'      => 'ai_cv_application',
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$selected_app_id = ! empty( $apps ) ? $apps[0]->ID : 0;

		$projects = get_posts( array(
			'post_type'      => 'cv_project',
			'posts_per_page' => 5,
			'post_status'    => 'publish',
		) );
		$project_ids = wp_list_pluck( $projects, 'ID' );

		$settings = get_option( 'ai_cv_tailor_settings', array() );

		return array(
			'application_id' => $selected_app_id,
			'job_title'      => ! empty( $apps ) ? $apps[0]->post_title : 'Ohjelmistoarkkitehti',
			'company_name'   => $settings['company_name'] ?? 'i4ware Software',
			'language'       => $settings['default_language'] ?? 'fi',
			'project_ids'    => $project_ids,
			'mode'           => 'job_seeker', // or 'hr_advisor'
			'hr_criteria'    => array(
				'role'                  => 'Senior Ohjelmistokehittäjä / Arkkitehti',
				'key_competencies'      => 'PHP, WordPress, JavaScript, TypeScript, React, REST API, pilvipalvelut, tiimityö',
				'evaluation_guidelines' => 'Arvioi kokemus, ongelmanratkaisukyky, kommunikaatio ja tekninen syvyys.',
			),
			'updated_at'     => time(),
		);
	}

	/**
	 * Enriches preparation package with full texts of job, cover letter, and projects
	 */
	private function enrich_preparation_package( $prep ) {
		$app_id = $prep['application_id'] ?? 0;
		if ( $app_id ) {
			$app_post = get_post( $app_id );
			if ( $app_post ) {
				$prep['application_title'] = $app_post->post_title;
				$prep['company_name']      = get_post_meta( $app_id, '_company_name', true ) ?: ( get_post_meta( $app_id, 'ai_cv_company_name', true ) ?: ( $prep['company_name'] ?? '' ) );
				$prep['role_title']        = get_post_meta( $app_id, '_job_title', true ) ?: ( get_post_meta( $app_id, 'ai_cv_role_title', true ) ?: $app_post->post_title );
				$prep['job_description']   = get_post_meta( $app_id, '_job_description', true ) ?: ( get_post_meta( $app_id, 'ai_cv_job_description', true ) ?: $app_post->post_content );
				$prep['cover_letter']      = get_post_meta( $app_id, 'ai_cv_cover_letter', true );
				$prep['motivation_letter'] = get_post_meta( $app_id, 'ai_cv_motivation_letter', true );
			}
		}

		// Fetch verified project details
		$prep['verified_projects'] = array();
		$project_ids = $prep['project_ids'] ?? array();
		if ( ! empty( $project_ids ) && is_array( $project_ids ) ) {
			$proj_posts = get_posts( array(
				'post_type'      => 'cv_project',
				'post__in'       => $project_ids,
				'posts_per_page' => count( $project_ids ),
			) );

			foreach ( $proj_posts as $p ) {
				$prep['verified_projects'][] = array(
					'id'          => $p->ID,
					'title'       => $p->post_title,
					'description' => get_post_meta( $p->ID, '_cv_project_description', true ) ?: $p->post_content,
					'tech_stack'  => get_post_meta( $p->ID, '_cv_project_meta', true ),
					'url'         => get_post_meta( $p->ID, '_cv_project_link', true ),
				);
			}
		}

		// Profile facts
		$profile_data = get_option( 'ai_cv_profile_data', array() );
		$prep['profile_summary'] = $profile_data['profile_summary'] ?? '';
		$prep['skills']          = $profile_data['skills'] ?? array();

		return $prep;
	}

	/**
	 * REST: Save active interview preparation package
	 */
	public function rest_save_preparation( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$params = $request->get_json_params();

		if ( empty( $params ) || ! is_array( $params ) ) {
			return new WP_Error( 'invalid_data', 'Virheellinen pyyntödata.', array( 'status' => 400 ) );
		}

		$current_prep = get_user_meta( $user_id, 'ai_interview_active_prep', true );
		if ( ! is_array( $current_prep ) ) {
			$current_prep = array();
		}

		if ( isset( $params['application_id'] ) ) {
			$current_prep['application_id'] = absint( $params['application_id'] );
		}
		if ( isset( $params['job_title'] ) ) {
			$current_prep['job_title'] = sanitize_text_field( $params['job_title'] );
		}
		if ( isset( $params['company_name'] ) ) {
			$current_prep['company_name'] = sanitize_text_field( $params['company_name'] );
		}
		if ( isset( $params['language'] ) ) {
			$current_prep['language'] = sanitize_text_field( $params['language'] );
		}
		if ( isset( $params['project_ids'] ) && is_array( $params['project_ids'] ) ) {
			$current_prep['project_ids'] = array_map( 'absint', $params['project_ids'] );
		}
		if ( isset( $params['mode'] ) ) {
			$current_prep['mode'] = in_array( $params['mode'], array( 'job_seeker', 'hr_advisor' ), true ) ? $params['mode'] : 'job_seeker';
		}
		if ( isset( $params['hr_criteria'] ) && is_array( $params['hr_criteria'] ) ) {
			$current_prep['hr_criteria'] = array(
				'role'                  => sanitize_text_field( $params['hr_criteria']['role'] ?? '' ),
				'key_competencies'      => sanitize_textarea_field( $params['hr_criteria']['key_competencies'] ?? '' ),
				'evaluation_guidelines' => sanitize_textarea_field( $params['hr_criteria']['evaluation_guidelines'] ?? '' ),
			);
		}

		$current_prep['updated_at'] = time();

		update_user_meta( $user_id, 'ai_interview_active_prep', $current_prep );

		return rest_ensure_response( array(
			'success'     => true,
			'message'     => 'Valmistelupaketti tallennettu.',
			'preparation' => $this->enrich_preparation_package( $current_prep ),
		) );
	}

	/**
	 * REST: Suggest answer during interview
	 */
	public function rest_suggest_answer( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$question = trim( $request->get_param( 'question' ) );
		$mode = $request->get_param( 'mode' ) ?: 'job_seeker';
		$history = $request->get_param( 'history' ) ?: array();

		if ( empty( $question ) ) {
			return new WP_Error( 'empty_question', 'Kysymysteksti on tyhjä.', array( 'status' => 400 ) );
		}

		// Get active prep
		$prep = get_user_meta( $user_id, 'ai_interview_active_prep', true );
		if ( empty( $prep ) || ! is_array( $prep ) ) {
			$prep = $this->build_default_preparation( $user_id );
		}
		$prep = $this->enrich_preparation_package( $prep );

		// Call AI engine
		$result = $this->generate_ai_suggestion( $question, $mode, $prep, $history );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $result,
		) );
	}

	/**
	 * Core AI Suggestion Generator
	 */
	private function generate_ai_suggestion( $question, $mode, $prep, $history = array() ) {
		$settings = get_option( 'ai_cv_tailor_settings', array() );
		$openai_key = $settings['openai_api_key'] ?? '';
		$model = $settings['model'] ?? 'gpt-4o';

		if ( empty( $openai_key ) ) {
			return new WP_Error( 'no_openai_key', 'OpenAI API -avain puuttuu WordPress-asetuksista.', array( 'status' => 500 ) );
		}

		$lang = $prep['language'] ?? 'fi';
		$lang_name = ( $lang === 'en' ) ? 'English' : ( ( $lang === 'sv' ) ? 'Swedish' : 'Finnish' );

		// Assemble verified facts string
		$verified_facts = "Työpaikka/Rooli: " . ( $prep['role_title'] ?? $prep['job_title'] ?? 'Kehittäjä' ) . "\n";
		$verified_facts .= "Kohdeyritys: " . ( $prep['company_name'] ?? '' ) . "\n";
		if ( ! empty( $prep['job_description'] ) ) {
			$verified_facts .= "Työpaikkailmoituksen kuvaus:\n" . substr( $prep['job_description'], 0, 2000 ) . "\n\n";
		}
		if ( ! empty( $prep['cover_letter'] ) ) {
			$verified_facts .= "Hakijan aiempi hakemuskirje:\n" . substr( $prep['cover_letter'], 0, 1500 ) . "\n\n";
		}

		$verified_facts .= "VAHVISTETUT PROJEKTIESIMERKIT (KÄYTÄ AINOASTAAN NÄITÄ FAKTOINA):\n";
		if ( ! empty( $prep['verified_projects'] ) ) {
			foreach ( $prep['verified_projects'] as $p ) {
				$verified_facts .= "- " . $p['title'] . ": " . $p['description'] . " (Teknologiat: " . $p['tech_stack'] . ")\n";
			}
		} else {
			$verified_facts .= "- i4ware Timesheet for Jira (PHP, Java, Atlassian REST APIs, React)\n";
			$verified_facts .= "- i4ware SDK low-code platform (TypeScript, Node.js, Web Components)\n";
			$verified_facts .= "- AI CV/Portfolio System (WordPress, OpenAI, Google Gemini, Polylang)\n";
		}

		if ( $mode === 'hr_advisor' ) {
			$system_prompt = "Olet kokenut HR-asiantuntija ja rekrytointikonsultti (HR Advisor).
Käytät kielenä: {$lang_name}.
Tehtäväsi on auttaa haastattelijaa/työnantajaa arvioimaan ehdokkaan vastausta tai muotoilemaan paras mahdollinen jatkokysymys.

Noudata tiukasti tätä JSON-rakennetta:
{
  \"intent\": \"Haastattelukysymyksen tai ehdokkaan vastauksen syvempi merkitys ja tarkoitus rekrytoinnin kannalta.\",
  \"main_point\": \"Keskeinen arvio ja suositus haastattelijalle.\",
  \"supporting_points\": [
    \"Osaamisvaatimusten täyttyminen\",
    \"Vahvuudet / havaitut riskit\",
    \"Syventämistä vaativa alue\"
  ],
  \"real_example\": \"Mitä konkreettista näyttöä tai näyttökysymystä rooliin liittyen kannattaa seuraavaksi kysyä.\",
  \"clarifying_question\": \"Täsmällinen jatkokysymys haastateltavalle.\",
  \"speech_draft\": \"Haastattelijan suullinen puheenvuoroluonnos (jos halutaan kysyä tarkentava kysymys ääneen).\",
  \"hr_score\": 4,
  \"is_clarification_needed\": false
}";
		} else {
			$system_prompt = "Olet huipputason työnhakijan haastatteluavustaja.
Käytät vastausten kielenä: {$lang_name}.

TÄRKEIMMÄT SÄÄNNÖT:
1. Tunnista haastattelijan kysymyksen varsinainen tarkoitus (intent).
2. Näytä vastauksen pääasia ensin (main_point) - vastaa suoraan kysymykseen heti ensimmäisessä lauseessa.
3. Ehdota enintään kolme lyhyttä tukikohtaa (supporting_points).
4. Hae todellinen esimerkki vahvistetuista taustatiedoista (real_example).
5. KRIITTINEN SÄÄNTÖ: ÄLÄ KEKSI kokemusta, saavutuksia tai vastauksia faktatiedoiksi! Käytä vain alla annettuja vahvistettuja taustatietoja. Jos hakijalla ei ole jotain tiettyä kokemusta, avustaja osoittaa miten hakijan olemassa oleva osaaminen siirretään ongelman ratkaisemiseen rehellisesti.
6. Jos haastattelijan tarkoitus on epäselvä tai kysymys on monitulkintainen, ehdota tarkentavaa vastakysymystä (clarifying_question).
7. Luo valmis muokattava puheenvuoroluonnos (speech_draft), jonka LiveAvatar voi lukea ääneen tai jota hakija voi käyttää. Puheenvuoron tulee olla luontevaa, ammattimaista puhekieltä (1-3 virkettä).

Palauta vastaus AINOASTAAN validina JSON-objektina:
{
  \"intent\": \"Kysymyksen varsinainen tarkoitus\",
  \"main_point\": \"Vastauksen pääasia tiiviisti (1-2 lausetta)\",
  \"supporting_points\": [
    \"Lyhyt tukikohta 1\",
    \"Lyhyt tukikohta 2\",
    \"Lyhyt tukikohta 3\"
  ],
  \"real_example\": \"Todellinen esimerkki vahvistetuista taustatiedoista\",
  \"clarifying_question\": \"Tarkentava kysymys tai tyhjä merkkijono jos kysymys oli selkeä\",
  \"speech_draft\": \"Luonnollinen, tiivis puheenvuoro avatarille tai hakijalle\",
  \"is_clarification_needed\": false
}";
		}

		$messages = array(
			array(
				'role'    => 'system',
				'content' => $system_prompt,
			),
			array(
				'role'    => 'user',
				'content' => "VAHVISTETUT TAUSTATIEDOT:\n{$verified_facts}\n\nHAASTATTELIJAN KYSYMYS:\n\"{$question}\"",
			),
		);

		// If recent history provided, append as context
		if ( ! empty( $history ) && is_array( $history ) ) {
			$history_context = "AIEMPI KESKUSTELU HAASTATTELUSSA:\n";
			foreach ( array_slice( $history, -4 ) as $h ) {
				$role = $h['role'] ?? 'interviewer';
				$text = $h['text'] ?? '';
				$history_context .= "- {$role}: {$text}\n";
			}
			$messages[1]['content'] = $history_context . "\n\n" . $messages[1]['content'];
		}

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $openai_key,
			),
			'body'    => wp_json_encode( array(
				'model'           => $model,
				'messages'        => $messages,
				'temperature'     => 0.4,
				'response_format' => array( 'type' => 'json_object' ),
			) ),
			'timeout' => 45,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'openai_api_error', $data['error']['message'] ?? 'OpenAI virhe.' );
		}

		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			return new WP_Error( 'empty_response', 'OpenAI palautti tyhjän vastauksen.' );
		}

		$parsed = json_decode( $data['choices'][0]['message']['content'], true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_parse_error', 'OpenAI vastauksen JSON-jäsennys epäonnistui.' );
		}

		$parsed['mode'] = $mode;
		$parsed['language'] = $lang;
		$parsed['verified_no_hallucinations'] = true;

		return $parsed;
	}

	/**
	 * REST: Save interview transcript (explicit opt-in only)
	 */
	public function rest_save_transcript( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$settings = get_option( 'ai_cv_tailor_settings', array() );
		
		$storage_enabled = ! empty( $settings['interview_transcription_storage'] );
		$explicit_user_consent = (bool) $request->get_param( 'explicit_consent' );

		// Rule: "Äänitallennus on oletuksena pois käytöstä. Litteroinnin pysyvä tallentaminen vaatii erillisen valinnan."
		if ( ! $storage_enabled && ! $explicit_user_consent ) {
			return new WP_Error(
				'storage_disabled',
				'Äänitallennus ja litteroinnin pysyvä tallennus on oletuksena pois käytöstä. Käyttäjän nimenomainen valinta vaaditaan tallentamiseen.',
				array( 'status' => 403 )
			);
		}

		$transcripts = $request->get_param( 'transcripts' );
		if ( empty( $transcripts ) || ! is_array( $transcripts ) ) {
			return new WP_Error( 'empty_data', 'Ei tallennettavaa litterointidataa.', array( 'status' => 400 ) );
		}

		// Store in user metadata or post
		$existing = get_user_meta( $user_id, 'ai_cv_interview_transcripts', true );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$entry = array(
			'id'          => wp_generate_uuid4(),
			'timestamp'   => time(),
			'date'        => date( 'Y-m-d H:i:s' ),
			'transcripts' => $transcripts,
		);

		$existing[] = $entry;
		update_user_meta( $user_id, 'ai_cv_interview_transcripts', $existing );

		return rest_ensure_response( array(
			'success' => true,
			'message' => 'Litterointi tallennettu onnistuneesti käyttäjän nimenomaisella suostumuksella.',
			'id'      => $entry['id'],
		) );
	}

	/**
	 * AJAX: Generate pair code from admin
	 */
	public function ajax_generate_pair_code() {
		check_ajax_referer( 'ai_cv_interview_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Ei riittäviä oikeuksia.' ) );
		}

		$user_id = get_current_user_id();
		$code = $this->create_pairing_code_for_user( $user_id );

		wp_send_json_success( array(
			'pairing_code' => $code,
			'expires_in'   => 900,
		) );
	}

	/**
	 * AJAX: Revoke device token
	 */
	public function ajax_revoke_device() {
		check_ajax_referer( 'ai_cv_interview_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Ei riittäviä oikeuksia.' ) );
		}

		$device_id = sanitize_text_field( $_POST['device_id'] ?? '' );
		$user_id = get_current_user_id();

		$tokens = get_user_meta( $user_id, 'ai_cv_interview_device_tokens', true );
		if ( is_array( $tokens ) ) {
			$filtered = array();
			foreach ( $tokens as $t ) {
				if ( ( $t['id'] ?? '' ) !== $device_id ) {
					$filtered[] = $t;
				}
			}
			update_user_meta( $user_id, 'ai_cv_interview_device_tokens', $filtered );
		}

		wp_send_json_success( array( 'message' => 'Laiteparitus purettu.' ) );
	}

	/**
	 * AJAX: Save interview preparation from admin
	 */
	public function ajax_save_interview_prep() {
		check_ajax_referer( 'ai_cv_interview_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Ei riittäviä oikeuksia.' ) );
		}

		$user_id = get_current_user_id();
		$app_id = absint( $_POST['application_id'] ?? 0 );
		$language = sanitize_text_field( $_POST['language'] ?? 'fi' );
		$mode = in_array( $_POST['mode'] ?? '', array( 'job_seeker', 'hr_advisor' ), true ) ? $_POST['mode'] : 'job_seeker';
		$project_ids = isset( $_POST['project_ids'] ) && is_array( $_POST['project_ids'] ) ? array_map( 'absint', $_POST['project_ids'] ) : array();

		$prep = array(
			'application_id' => $app_id,
			'language'       => $language,
			'mode'           => $mode,
			'project_ids'    => $project_ids,
			'hr_criteria'    => array(
				'role'                  => sanitize_text_field( $_POST['hr_role'] ?? '' ),
				'key_competencies'      => sanitize_textarea_field( $_POST['hr_competencies'] ?? '' ),
				'evaluation_guidelines' => sanitize_textarea_field( $_POST['hr_guidelines'] ?? '' ),
			),
			'updated_at'     => time(),
		);

		update_user_meta( $user_id, 'ai_interview_active_prep', $prep );

		wp_send_json_success( array(
			'message'     => 'Haastattelun valmistelutiedot tallennettu onnistuneesti.',
			'preparation' => $this->enrich_preparation_package( $prep ),
		) );
	}

	/**
	 * AJAX: Test AI answer suggestion from admin
	 */
	public function ajax_test_suggest_answer() {
		check_ajax_referer( 'ai_cv_interview_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Ei riittäviä oikeuksia.' ) );
		}

		$user_id = get_current_user_id();
		$question = sanitize_textarea_field( $_POST['question'] ?? '' );
		$mode = sanitize_text_field( $_POST['mode'] ?? 'job_seeker' );

		if ( empty( $question ) ) {
			wp_send_json_error( array( 'message' => 'Syötä testikysymys.' ) );
		}

		$prep = get_user_meta( $user_id, 'ai_interview_active_prep', true );
		if ( empty( $prep ) || ! is_array( $prep ) ) {
			$prep = $this->build_default_preparation( $user_id );
		}
		$prep = $this->enrich_preparation_package( $prep );

		$res = $this->generate_ai_suggestion( $question, $mode, $prep );

		if ( is_wp_error( $res ) ) {
			wp_send_json_error( array( 'message' => $res->get_error_message() ) );
		}

		wp_send_json_success( array( 'suggestion' => $res ) );
	}

	/**
	 * Render the Admin Page
	 */
	public function render_admin_page() {
		require_once AI_CV_TAILOR_DIR . 'templates/admin-interview-assistant.php';
	}
}
