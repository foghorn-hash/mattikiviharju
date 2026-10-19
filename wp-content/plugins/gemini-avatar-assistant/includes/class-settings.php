<?php
/**
 * Settings and Configuration Manager
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Settings {

	const OPTION_KEY = 'gemini_avatar_settings';

	/**
	 * Default settings values
	 *
	 * @return array
	 */
	public static function get_defaults() {
		// Try to find default profile photo from front page or uploads
		$default_avatar = '';
		$default_avatar_id = 0;
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id > 0 && function_exists( 'get_field' ) ) {
			$photo = get_field( 'cv_about_photo', $front_id );
			if ( is_array( $photo ) && ! empty( $photo['url'] ) ) {
				$default_avatar = $photo['url'];
				$default_avatar_id = ! empty( $photo['ID'] ) ? (int) $photo['ID'] : 0;
			}
		}

		if ( empty( $default_avatar ) ) {
			$upload_dir = wp_upload_dir();
			$default_avatar = $upload_dir['baseurl'] . '/2026/02/mattikiviharju5-600-600.jpg';
		}

		// Privacy policy URL
		$privacy_url = '';
		if ( ! empty( $GLOBALS['wp_rewrite'] ) && function_exists( 'get_privacy_policy_url' ) ) {
			$privacy_url = get_privacy_policy_url();
		}
		if ( empty( $privacy_url ) ) {
			$privacy_url = home_url( '/privacy-policy/' );
		}

		return array(
			// API & Models
			'api_key'                 => '',
			'text_model'              => 'gemini-3.5-flash',
			'tts_model'               => 'gemini-3.8-flash-tts',
			'tts_voice'               => 'Puck',

			// Assistant Identity & Appearance
			'assistant_name'          => 'Matti Kiviharjun AI-avustaja',
			'greeting_fi'             => 'Hei! Olen Matti Kiviharjun tekoälyavustaja. Vastaan kysymyksiin Matin osaamisesta, työkokemuksesta, projekteista ja tarjoamista palveluista. Kuinka voin auttaa?',
			'greeting_en'             => 'Hello! I am Matti Kiviharju’s AI Assistant. I can answer questions about Matti’s expertise, background, projects, and services. How can I help you?',
			'greeting_sv'             => 'Hej! Jag är Matti Kiviharjus AI-assistent. Jag besvarar frågor om Mattis expertis, arbetserfarenhet, projekt och tjänster. Hur kan jag hjälpa dig?',
			'default_language'        => 'fi',
			'avatar_type'             => 'photo', // 'photo' or 'svg'
			'avatar_image_url'        => $default_avatar,
			'avatar_image_id'         => $default_avatar_id,

			// Knowledge Base
			'included_post_types'     => array(
				'cv_experience',
				'cv_project',
				'cv_education',
				'cv_course',
				'cv_skill',
				'cv_badge',
				'cv_reference',
				'page',
				'post',
			),
			'included_pages'          => array(),
			'approved_extra_knowledge'=> "i4ware Software on Matti Kiviharjun yritys (Y-tunnus: 2739594-6, ALV-numero: FI27395946, verkkosivusto: https://www.i4ware.fi/).\n"
				. "Matti tarjoaa ohjelmistoarkkitehtuurin, Full Stack -ohjelmoinnin, Atlassian-ratkaisujen (Jira, Confluence, Forge API) ja ERP-integraatioiden asiantuntijapalveluita.\n"
				. "Sähköposti: info@i4ware.fi tai matti.kiviharju@i4ware.fi, Puhelin: +358 40 820 0691.\n"
				. "Toimeksiannot sovitaan aina suoraan Matin kanssa kirjallisesti. Tekoälyavustaja ei voi tehdä sitovia sopimuksia tai antaa hintalupauksia.",

			// Limits & Guardrails
			'max_answer_tokens'       => 350,
			'temperature'             => 0.4,
			'rate_limit_per_minute'   => 5,
			'rate_limit_per_hour'     => 30,
			'max_input_chars'         => 400,

			// Toggles
			'enable_widget'           => 1,
			'enable_speech'           => 1,
			'enable_voice_input'      => 1,

			// Privacy
			'privacy_notice'          => 'Viestisi käsitellään Googlen Gemini -tekoälypalvelussa. Emme tallenna keskusteluhistoriaa palvelimelle.',
			'privacy_policy_url'      => $privacy_url,
		);
	}

	/**
	 * Get all settings merged with defaults
	 *
	 * @return array
	 */
	public static function get_all() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * Get single setting value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = self::get_all();
		if ( isset( $all[ $key ] ) ) {
			return $all[ $key ];
		}
		return $default;
	}

	/**
	 * Update settings
	 *
	 * @param array $settings
	 * @return bool
	 */
	public static function update_all( $settings ) {
		$current = self::get_all();
		$updated = wp_parse_args( $settings, $current );
		return update_option( self::OPTION_KEY, $updated );
	}

	/**
	 * Retrieve the active Gemini API key from constants, env, or options
	 *
	 * @return string
	 */
	public static function get_api_key() {
		// 1. wp-config constants
		if ( defined( 'GEMINI_API_KEY' ) && ! empty( GEMINI_API_KEY ) ) {
			return trim( (string) GEMINI_API_KEY );
		}
		if ( defined( 'GOOGLE_AI_API_KEY' ) && ! empty( GOOGLE_AI_API_KEY ) ) {
			return trim( (string) GOOGLE_AI_API_KEY );
		}

		// 2. Environment variables
		$env_key = getenv( 'GEMINI_API_KEY' ) ?: getenv( 'GOOGLE_AI_API_KEY' );
		if ( ! empty( $env_key ) ) {
			return trim( $env_key );
		}

		// 3. Stored setting
		$key = self::get( 'api_key', '' );
		if ( ! empty( $key ) ) {
			return trim( $key );
		}

		// 4. Fallback to existing autopilot setting if available
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		if ( ! empty( $autopilot_settings['google_ai_api_key'] ) && 'password' !== $autopilot_settings['google_ai_api_key'] ) {
			return trim( $autopilot_settings['google_ai_api_key'] );
		}

		return '';
	}

	/**
	 * Check if an API key is available
	 *
	 * @return bool
	 */
	public static function has_api_key() {
		$key = self::get_api_key();
		return ! empty( $key );
	}

	/**
	 * Source where the API key is retrieved from
	 *
	 * @return string
	 */
	public static function get_api_key_source() {
		if ( defined( 'GEMINI_API_KEY' ) && ! empty( GEMINI_API_KEY ) ) {
			return 'wp-config.php (GEMINI_API_KEY)';
		}
		if ( defined( 'GOOGLE_AI_API_KEY' ) && ! empty( GOOGLE_AI_API_KEY ) ) {
			return 'wp-config.php (GOOGLE_AI_API_KEY)';
		}
		if ( getenv( 'GEMINI_API_KEY' ) ) {
			return 'Ympäristömuuttuja (GEMINI_API_KEY)';
		}
		if ( getenv( 'GOOGLE_AI_API_KEY' ) ) {
			return 'Ympäristömuuttuja (GOOGLE_AI_API_KEY)';
		}
		$saved = self::get( 'api_key', '' );
		if ( ! empty( $saved ) ) {
			return 'Tietokanta-asetus';
		}
		return 'Ei määritetty';
	}

	/**
	 * Available text models
	 *
	 * @return array
	 */
	public static function get_text_models() {
		return array(
			'gemini-3.5-flash-lite'  => 'Gemini 3.5 Flash Lite (Matala viive, erillinen kiintiö - Suositus)',
			'gemini-3.1-flash-lite'  => 'Gemini 3.1 Flash Lite (Nopea & luotettava)',
			'gemini-3.5-flash'       => 'Gemini 3.5 Flash',
			'gemini-3.8-flash'       => 'Gemini 3.8 Flash',
			'gemini-flash-latest'    => 'Gemini Flash Latest',
		);
	}

	/**
	 * Available TTS models
	 *
	 * @return array
	 */
	public static function get_tts_models() {
		return array(
			'gemini-3.8-flash-tts'          => 'Gemini 3.8 Flash TTS (Suositus - 24kHz WAV)',
			'gemini-3.8-flash-lite-tts'     => 'Gemini 3.8 Flash Lite TTS (Matala viive)',
			'gemini-2.5-flash-preview-tts'  => 'Gemini 2.5 Flash Preview TTS',
			'gemini-3.1-flash-tts-preview'  => 'Gemini 3.1 Flash TTS Preview',
		);
	}

	/**
	 * Available prebuilt TTS voices
	 *
	 * @return array
	 */
	public static function get_tts_voices() {
		return array(
			'Puck'       => 'Puck (Selkeä, luonnollinen, suositus)',
			'Kore'       => 'Kore (Lämmin, vakaa)',
			'Fenrir'     => 'Fenrir (Matala, rauhallinen)',
			'Aoede'      => 'Aoede (Eloisa, kirkas)',
			'Zephyr'     => 'Zephyr (Kirkas, reipas)',
			'Orus'       => 'Orus (Vakaa, asiallinen)',
			'Autonoe'    => 'Autonoe (Ystävällinen)',
			'Umbriel'    => 'Umbriel (Rento)',
			'Erinome'    => 'Erinome (Kuulas)',
			'Laomedeia'  => 'Laomedeia (Innostava)',
			'Schedar'    => 'Schedar (Tasainen)',
		);
	}
}
