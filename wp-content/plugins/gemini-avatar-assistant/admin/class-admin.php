<?php
/**
 * Admin Controller for Gemini AI Avatar Assistant
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers
		add_action( 'wp_ajax_gemini_avatar_rebuild_index', array( $this, 'ajax_rebuild_index' ) );
		add_action( 'wp_ajax_gemini_avatar_test_api', array( $this, 'ajax_test_api' ) );
	}

	public function add_menu_page() {
		add_options_page(
			'Gemini AI-avustaja',
			'Gemini AI-avustaja',
			'manage_options',
			'gemini-avatar-assistant',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting( 'gemini_avatar_settings_group', Gemini_Avatar_Settings::OPTION_KEY, array(
			'type'              => 'array',
			'sanitize_callback' => array( $this, 'sanitize_settings' ),
		) );
	}

	public function sanitize_settings( $input ) {
		$output = Gemini_Avatar_Settings::get_all();

		if ( isset( $input['api_key'] ) ) {
			$output['api_key'] = sanitize_text_field( trim( $input['api_key'] ) );
		}
		if ( isset( $input['text_model'] ) ) {
			$output['text_model'] = sanitize_text_field( $input['text_model'] );
		}
		if ( isset( $input['tts_model'] ) ) {
			$output['tts_model'] = sanitize_text_field( $input['tts_model'] );
		}
		if ( isset( $input['tts_voice'] ) ) {
			$output['tts_voice'] = sanitize_text_field( $input['tts_voice'] );
		}
		if ( isset( $input['assistant_name'] ) ) {
			$output['assistant_name'] = sanitize_text_field( $input['assistant_name'] );
		}
		if ( isset( $input['greeting_fi'] ) ) {
			$output['greeting_fi'] = sanitize_textarea_field( $input['greeting_fi'] );
		}
		if ( isset( $input['greeting_en'] ) ) {
			$output['greeting_en'] = sanitize_textarea_field( $input['greeting_en'] );
		}
		if ( isset( $input['greeting_sv'] ) ) {
			$output['greeting_sv'] = sanitize_textarea_field( $input['greeting_sv'] );
		}
		if ( isset( $input['default_language'] ) ) {
			$output['default_language'] = in_array( $input['default_language'], array( 'fi', 'en', 'sv' ), true ) ? $input['default_language'] : 'fi';
		}
		if ( isset( $input['avatar_type'] ) ) {
			$output['avatar_type'] = in_array( $input['avatar_type'], array( 'photo', 'svg' ), true ) ? $input['avatar_type'] : 'photo';
		}
		if ( isset( $input['avatar_image_url'] ) ) {
			$output['avatar_image_url'] = esc_url_raw( $input['avatar_image_url'] );
		}
		if ( isset( $input['avatar_image_id'] ) ) {
			$output['avatar_image_id'] = absint( $input['avatar_image_id'] );
		}

		// Knowledge base post types
		$output['included_post_types'] = ! empty( $input['included_post_types'] ) && is_array( $input['included_post_types'] )
			? array_map( 'sanitize_key', $input['included_post_types'] )
			: array();

		if ( isset( $input['approved_extra_knowledge'] ) ) {
			$output['approved_extra_knowledge'] = sanitize_textarea_field( $input['approved_extra_knowledge'] );
		}

		// Limits
		if ( isset( $input['max_answer_tokens'] ) ) {
			$output['max_answer_tokens'] = max( 100, min( 1000, absint( $input['max_answer_tokens'] ) ) );
		}
		if ( isset( $input['temperature'] ) ) {
			$output['temperature'] = max( 0.0, min( 1.0, (float) $input['temperature'] ) );
		}
		if ( isset( $input['rate_limit_per_minute'] ) ) {
			$output['rate_limit_per_minute'] = max( 1, min( 30, absint( $input['rate_limit_per_minute'] ) ) );
		}
		if ( isset( $input['rate_limit_per_hour'] ) ) {
			$output['rate_limit_per_hour'] = max( 5, min( 200, absint( $input['rate_limit_per_hour'] ) ) );
		}
		if ( isset( $input['max_input_chars'] ) ) {
			$output['max_input_chars'] = max( 50, min( 1000, absint( $input['max_input_chars'] ) ) );
		}

		// Toggles
		$output['enable_widget']      = ! empty( $input['enable_widget'] ) ? 1 : 0;
		$output['enable_speech']      = ! empty( $input['enable_speech'] ) ? 1 : 0;
		$output['enable_voice_input'] = ! empty( $input['enable_voice_input'] ) ? 1 : 0;

		// Privacy
		if ( isset( $input['privacy_notice'] ) ) {
			$output['privacy_notice'] = sanitize_textarea_field( $input['privacy_notice'] );
		}
		if ( isset( $input['privacy_policy_url'] ) ) {
			$output['privacy_policy_url'] = esc_url_raw( $input['privacy_policy_url'] );
		}

		return $output;
	}

	public function enqueue_assets( $hook ) {
		if ( 'settings_page_gemini-avatar-assistant' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style(
			'gemini-avatar-admin-css',
			GEMINI_AVATAR_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			GEMINI_AVATAR_VERSION
		);
		wp_enqueue_script(
			'gemini-avatar-admin-js',
			GEMINI_AVATAR_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			GEMINI_AVATAR_VERSION,
			true
		);

		wp_localize_script( 'gemini-avatar-admin-js', 'geminiAvatarAdmin', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'gemini_avatar_admin_nonce' ),
		) );
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		include GEMINI_AVATAR_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * AJAX Rebuild Index
	 */
	public function ajax_rebuild_index() {
		check_ajax_referer( 'gemini_avatar_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Ei oikeuksia.' ) );
		}

		$chunks = Gemini_Avatar_Knowledge_Base::build_index();
		$stats  = Gemini_Avatar_Knowledge_Base::get_stats();

		wp_send_json_success( array(
			'message' => sprintf( 'Tietopohja päivitetty onnistuneesti! Indeksoitiin %d sisältökohdetta.', count( $chunks ) ),
			'stats'   => $stats,
		) );
	}

	/**
	 * AJAX Test API
	 */
	public function ajax_test_api() {
		check_ajax_referer( 'gemini_avatar_admin_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Ei oikeuksia.' ) );
		}

		$start = microtime( true );
		$ans = Gemini_Avatar_Gemini_Client::generate_answer(
			'Kuka on Matti Kiviharju?',
			array(),
			'Matti Kiviharju on suomalainen ohjelmistoarkkitehti, Full Stack -kehittäjä ja i4ware Softwaren perustaja.'
		);
		$time_text = round( microtime( true ) - $start, 2 );

		if ( empty( $ans['success'] ) ) {
			wp_send_json_error( array(
				'message' => 'Tekstimallin testikutsu epäonnistui: ' . ( $ans['error'] ?? 'Tuntematon virhe' ),
			) );
		}

		// Test TTS
		$start_tts = microtime( true );
		$tts = Gemini_Avatar_Gemini_Client::generate_tts( 'Hei, puhesynteesi toimii loistavasti!' );
		$time_tts = round( microtime( true ) - $start_tts, 2 );

		$tts_ok = ! empty( $tts['success'] );

		wp_send_json_success( array(
			'message'   => 'Gemini API toimii onnistuneesti!',
			'text_time' => $time_text . ' s',
			'text_sample' => $ans['text'],
			'tts_ok'    => $tts_ok,
			'tts_time'  => $tts_ok ? ( $time_tts . ' s' ) : null,
			'tts_error' => $tts_ok ? null : ( $tts['error'] ?? 'TTS epäonnistui' ),
		) );
	}
}
