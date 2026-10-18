<?php
/**
 * Public Front-End Controller for Gemini AI Avatar Assistant
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Public {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Only enqueue and render if widget is enabled
		if ( Gemini_Avatar_Settings::get( 'enable_widget', 1 ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_footer', array( $this, 'render_widget' ) );
		}
	}

	public static function get_current_language() {
		$settings = Gemini_Avatar_Settings::get_all();
		$lang     = ! empty( $settings['default_language'] ) ? $settings['default_language'] : 'fi';

		// 1. Check current queried post/page language in Polylang
		$queried_id = get_queried_object_id();
		if ( ! empty( $queried_id ) && function_exists( 'pll_get_post_language' ) ) {
			$post_lang = pll_get_post_language( $queried_id );
			if ( in_array( $post_lang, array( 'fi', 'en', 'sv' ), true ) ) {
				return $post_lang;
			}
		}

		// 2. Check Polylang global current language
		if ( function_exists( 'pll_current_language' ) ) {
			$pll_lang = pll_current_language();
			if ( in_array( $pll_lang, array( 'fi', 'en', 'sv' ), true ) ) {
				$lang = $pll_lang;
			}
		}

		// 3. Fallback: inspect request URI path for /(sv|en)/
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			if ( preg_match( '#/(sv|en)(/|$)#i', $uri, $matches ) ) {
				$matched = strtolower( $matches[1] );
				if ( in_array( $matched, array( 'fi', 'en', 'sv' ), true ) ) {
					return $matched;
				}
			}
		}

		return $lang;
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'gemini-avatar-widget-css',
			GEMINI_AVATAR_PLUGIN_URL . 'public/css/avatar-widget.css',
			array(),
			GEMINI_AVATAR_VERSION
		);

		wp_enqueue_script(
			'gemini-avatar-widget-js',
			GEMINI_AVATAR_PLUGIN_URL . 'public/js/avatar-widget.js',
			array(),
			GEMINI_AVATAR_VERSION,
			true
		);

		$settings = Gemini_Avatar_Settings::get_all();

		// Detect current language (Polylang post, global, URI, or default)
		$current_lang = self::get_current_language();

		$greeting_sv = ! empty( $settings['greeting_sv'] ) ? $settings['greeting_sv'] : 'Hej! Jag är Matti Kivihargus AI-assistent. Hur kan jag hjälpa dig idag?';
		if ( 'sv' === $current_lang ) {
			$greeting = $greeting_sv;
		} elseif ( 'en' === $current_lang ) {
			$greeting = $settings['greeting_en'];
		} else {
			$greeting = $settings['greeting_fi'];
		}

		$i18n_languages = array(
			'fi' => array(
				'assistantName'   => $settings['assistant_name'],
				'greeting'        => $settings['greeting_fi'],
				'ready'           => 'Valmiina',
				'thinking'        => 'Muodostaa vastausta...',
				'speaking'        => 'Puhuu...',
				'error'           => 'Virhe',
				'soundOn'         => 'Puhe päällä',
				'soundOff'        => 'Puhe pois',
				'stopSpeech'      => 'Pysäytä puhe',
				'replay'          => 'Kuuntele vastaus',
				'newChat'         => 'Aloita alusta',
				'minimize'        => 'Pienennä',
				'close'           => 'Sulje',
				'inputPlaceholder'=> 'Kysy jotain Matista tai osaamisesta...',
				'send'            => 'Lähetä',
				'listening'       => 'Kuunnellaan... Puhu mikrofoniin',
				'micPermission'   => 'Mikrofonin käyttö estetty selaimessa.',
				'sources'         => 'Lähteet:',
				'speechLang'      => 'fi-FI',
				'starters'        => array(
					'Kerro Matin ohjelmointiosaamisesta',
					'Mitä ERP- ja Atlassian-kokemusta Matilla on?',
					'Minkälaisiin projekteihin Matti on käytettävissä?',
					'Miten Mattiin saa yhteyden?',
				),
			),
			'en' => array(
				'assistantName'   => $settings['assistant_name'],
				'greeting'        => $settings['greeting_en'],
				'ready'           => 'Ready',
				'thinking'        => 'Formulating answer...',
				'speaking'        => 'Speaking...',
				'error'           => 'Error',
				'soundOn'         => 'Sound ON',
				'soundOff'        => 'Sound OFF',
				'stopSpeech'      => 'Stop speech',
				'replay'          => 'Listen to answer',
				'newChat'         => 'New conversation',
				'minimize'        => 'Minimize',
				'close'           => 'Close',
				'inputPlaceholder'=> 'Ask something about Matti...',
				'send'            => 'Send',
				'listening'       => 'Listening... Speak into microphone',
				'micPermission'   => 'Microphone permission denied in browser.',
				'sources'         => 'Sources:',
				'speechLang'      => 'en-US',
				'starters'        => array(
					'Tell me about Matti’s programming skills',
					'What ERP & Atlassian experience does Matti have?',
					'What kind of projects is Matti available for?',
					'How can I get in touch with Matti?',
				),
			),
			'sv' => array(
				'assistantName'   => $settings['assistant_name'],
				'greeting'        => $greeting_sv,
				'ready'           => 'Redo',
				'thinking'        => 'Formulerar svar...',
				'speaking'        => 'Talar...',
				'error'           => 'Fel',
				'soundOn'         => 'Tal PÅ',
				'soundOff'        => 'Tal AV',
				'stopSpeech'      => 'Stoppa tal',
				'replay'          => 'Lyssna på svar',
				'newChat'         => 'Börja om',
				'minimize'        => 'Minimera',
				'close'           => 'Stäng',
				'inputPlaceholder'=> 'Fråga något om Matti eller hans kompetens...',
				'send'            => 'Skicka',
				'listening'       => 'Lyssnar... Tala i mikrofonen',
				'micPermission'   => 'Mikrofonåtkomst nekad i webbläsaren.',
				'sources'         => 'Källor:',
				'speechLang'      => 'sv-SE',
				'starters'        => array(
					'Berätta om Mattis programmeringskunskaper',
					'Vad har Matti för erfarenhet av ERP och Atlassian?',
					'Vilka typer av projekt är Matti tillgänglig för?',
					'Hur kommer jag i kontakt med Matti?',
				),
			),
		);

		$active_i18n = $i18n_languages[ $current_lang ] ?? $i18n_languages['fi'];

		wp_localize_script( 'gemini-avatar-widget-js', 'geminiAvatarConfig', array(
			'apiUrl'         => esc_url_raw( rest_url( 'gemini-avatar/v1/chat' ) ),
			'ttsUrl'         => esc_url_raw( rest_url( 'gemini-avatar/v1/tts' ) ),
			'nonce'          => wp_create_nonce( 'wp_rest' ),
			'assistantName'  => esc_html( $settings['assistant_name'] ),
			'greeting'       => esc_html( $greeting ),
			'currentLang'    => esc_attr( $current_lang ),
			'languages'      => $i18n_languages,
			'avatarType'     => esc_attr( $settings['avatar_type'] ),
			'avatarImageUrl' => esc_url( $settings['avatar_image_url'] ),
			'enableSpeech'   => (bool) $settings['enable_speech'],
			'enableVoiceIn'  => (bool) $settings['enable_voice_input'],
			'maxChars'       => absint( $settings['max_input_chars'] ),
			'privacyNotice'  => esc_html( $settings['privacy_notice'] ),
			'privacyUrl'     => esc_url( $settings['privacy_policy_url'] ),
			'homeUrl'        => esc_url( home_url( '/' ) ),
			'i18n'           => $active_i18n,
		) );
	}

	public function render_widget() {
		$settings = Gemini_Avatar_Settings::get_all();
		include GEMINI_AVATAR_PLUGIN_DIR . 'public/views/widget-template.php';
	}
}
