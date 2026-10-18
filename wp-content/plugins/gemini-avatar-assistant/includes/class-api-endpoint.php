<?php
/**
 * REST API Endpoints for Chat and TTS
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_API_Endpoint {

	const NAMESPACE = 'gemini-avatar/v1';

	/**
	 * Register REST routes
	 */
	public static function register_routes() {
		// Chat endpoint
		register_rest_route( self::NAMESPACE, '/chat', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'handle_chat' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'args'                => array(
				'question'       => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
				'generate_audio' => array(
					'required'          => false,
					'type'              => 'boolean',
					'default'           => false,
				),
				'history'        => array(
					'required'          => false,
					'type'              => 'array',
					'default'           => array(),
				),
				'lang'           => array(
					'required'          => false,
					'type'              => 'string',
					'default'           => 'fi',
					'sanitize_callback' => 'sanitize_key',
				),
			),
		) );

		// Replay / Generate TTS endpoint
		register_rest_route( self::NAMESPACE, '/tts', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'handle_tts' ),
			'permission_callback' => array( __CLASS__, 'check_permissions' ),
			'args'                => array(
				'text' => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				),
			),
		) );
	}

	/**
	 * Permission and rate limiting callback
	 *
	 * @param WP_REST_Request $request
	 * @return bool|WP_Error
	 */
	public static function check_permissions( $request ) {
		// Verify WordPress REST Nonce
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden_nonce',
				'Virheellinen tai vanhentunut suojaustunniste (nonce). Lataa sivu uudelleen.',
				array( 'status' => 403 )
			);
		}

		// Server-side rate limiting per anonymized IP hash
		$ip = ! empty( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '127.0.0.1';
		$salt = wp_salt( 'auth' );
		$ip_hash = hash( 'sha256', $ip . $salt );

		$limit_min  = absint( Gemini_Avatar_Settings::get( 'rate_limit_per_minute', 5 ) );
		$limit_hour = absint( Gemini_Avatar_Settings::get( 'rate_limit_per_hour', 30 ) );

		// Minute check
		$min_transient_key = 'gemini_av_rpm_' . substr( $ip_hash, 0, 24 );
		$count_min = (int) get_transient( $min_transient_key );
		if ( $count_min >= $limit_min ) {
			return new WP_Error(
				'rate_limit_minute',
				'Olet lähettänyt liian monta viestiä minuutin sisällä. Odota hetki ennen seuraavaa kysymystä.',
				array( 'status' => 429 )
			);
		}

		// Hour check
		$hour_transient_key = 'gemini_av_rph_' . substr( $ip_hash, 0, 24 );
		$count_hour = (int) get_transient( $hour_transient_key );
		if ( $count_hour >= $limit_hour ) {
			return new WP_Error(
				'rate_limit_hour',
				'Käyttörajasi tälle tunnille on täyttynyt. Yritä myöhemmin uudelleen.',
				array( 'status' => 429 )
			);
		}

		// Increment rate limit counters
		set_transient( $min_transient_key, $count_min + 1, 60 );
		set_transient( $hour_transient_key, $count_hour + 1, 3600 );

		return true;
	}

	/**
	 * Handle incoming chat request
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_chat( $request ) {
		$question       = trim( (string) $request->get_param( 'question' ) );
		$generate_audio = (bool) $request->get_param( 'generate_audio' );
		$history        = (array) $request->get_param( 'history' );
		$lang           = sanitize_key( (string) $request->get_param( 'lang' ) );

		if ( ! in_array( $lang, array( 'fi', 'en', 'sv' ), true ) ) {
			$lang = 'fi';
		}

		$max_chars = absint( Gemini_Avatar_Settings::get( 'max_input_chars', 400 ) );
		if ( empty( $question ) ) {
			$err = ( 'sv' === $lang ) ? 'Frågan kan inte vara tom.' : ( ( 'en' === $lang ) ? 'Question cannot be empty.' : 'Kysymys ei voi olla tyhjä.' );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $err,
			), 400 );
		}

		if ( mb_strlen( $question, 'UTF-8' ) > $max_chars ) {
			$err = ( 'sv' === $lang ) ? sprintf( 'Maximal frågelängd är %d tecken.', $max_chars ) : ( ( 'en' === $lang ) ? sprintf( 'Maximum question length is %d characters.', $max_chars ) : sprintf( 'Kysymyksen enimmäispituus on %d merkkiä.', $max_chars ) );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $err,
			), 400 );
		}

		// Check if master widget is enabled
		if ( ! Gemini_Avatar_Settings::get( 'enable_widget', 1 ) ) {
			$err = ( 'sv' === $lang ) ? 'AI-assistenten är tillfälligt avstängd.' : ( ( 'en' === $lang ) ? 'AI assistant is temporarily disabled.' : 'AI-avustaja on tilapäisesti pois käytöstä.' );
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $err,
			), 503 );
		}

		// 1. Retrieve relevant knowledge excerpts in the requested language
		$context = Gemini_Avatar_Knowledge_Base::get_prompt_context( $question, 6, $lang );

		// 2. Generate text answer via Gemini in the requested language
		$answer_result = Gemini_Avatar_Gemini_Client::generate_answer(
			$question,
			$history,
			$context['context_text'],
			$lang
		);

		if ( empty( $answer_result['success'] ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $answer_result['error'] ?? 'Vastauksen luonti epäonnistui.',
			), 500 );
		}

		$answer_text = $answer_result['text'];
		$audio_data  = null;
		$audio_mime  = null;
		$audio_error = null;

		// 3. Generate speech if requested and enabled
		$speech_enabled = (bool) Gemini_Avatar_Settings::get( 'enable_speech', 1 );
		if ( $generate_audio && $speech_enabled ) {
			$tts_result = Gemini_Avatar_Gemini_Client::generate_tts( $answer_text );
			if ( ! empty( $tts_result['success'] ) ) {
				$audio_data = $tts_result['audio_base64'];
				$audio_mime = $tts_result['mime_type'];
			} else {
				$audio_error = $tts_result['error'] ?? 'Puhesynteesi epäonnistui';
			}
		}

		return new WP_REST_Response( array(
			'success'      => true,
			'text'         => $answer_text,
			'sources'      => $context['sources'],
			'audio_base64' => $audio_data,
			'mime_type'    => $audio_mime,
			'audio_error'  => $audio_error,
		), 200 );
	}

	/**
	 * Handle direct TTS generation / replay
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public static function handle_tts( $request ) {
		$text = trim( (string) $request->get_param( 'text' ) );
		if ( empty( $text ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Teksti puuttuu.',
			), 400 );
		}

		$tts_result = Gemini_Avatar_Gemini_Client::generate_tts( $text );
		if ( ! empty( $tts_result['success'] ) ) {
			return new WP_REST_Response( array(
				'success'      => true,
				'audio_base64' => $tts_result['audio_base64'],
				'mime_type'    => $tts_result['mime_type'],
			), 200 );
		}

		return new WP_REST_Response( array(
			'success' => false,
			'error'   => $tts_result['error'] ?? 'Puhesynteesi epäonnistui.',
		), 500 );
	}
}
