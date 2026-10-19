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
		// Popout standalone page redirect
		add_action( 'template_redirect', array( $this, 'maybe_render_popout' ) );

		// Only enqueue and render if widget is enabled
		if ( Gemini_Avatar_Settings::get( 'enable_widget', 1 ) ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_footer', array( $this, 'render_widget' ) );
		}
	}

	/**
	 * Check if popout avatar view is requested
	 */
	public function maybe_render_popout() {
		if ( isset( $_GET['gemini_popout'] ) && '1' === (string) $_GET['gemini_popout'] ) {
			include GEMINI_AVATAR_PLUGIN_DIR . 'public/views/avatar-popout.php';
			exit;
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
				'assistantName'    => $settings['assistant_name'],
				'greeting'         => $settings['greeting_fi'],
				'interviewGreeting'=> 'Olen valmiina avustamaan haastattelussa (Teams / Meet). Kuuntelen haastattelijan kysymyksiä ja muotoilen ehdotukset CV-osaamisesi pohjalta hyväksyttäväksesi.',
				'ready'            => 'Valmiina',
				'thinking'         => 'Muotoillaan vastausta...',
				'speaking'         => 'Puhuu kokoukseen...',
				'error'            => 'Virhe',
				'soundOn'          => 'Puhe päällä',
				'soundOff'         => 'Puhe pois',
				'stopSpeech'       => 'Pysäytä puhe',
				'replay'           => 'Kuuntele vastaus',
				'newChat'          => 'Aloita alusta',
				'minimize'         => 'Pienennä',
				'close'            => 'Sulje',
				'inputPlaceholder' => 'Syötä haastattelijan kysymys tai puhu mikrofoniin...',
				'send'             => 'Muotoile vastaus',
				'listening'        => 'Kuunnellaan haastattelijaa... Puhu mikrofoniin',
				'micPermission'    => 'Mikrofonin käyttö estetty selaimessa.',
				'sources'          => 'Lähteet:',
				'approveSpeak'     => 'Hyväksy ja puhu kokoukseen',
				'pause'            => 'Tauko',
				'resume'           => 'Jatka',
				'dismiss'          => 'Hylkää',
				'proposedAnswer'   => 'Ehdotettu puheenvuoro (Muokkaa tarvittaessa)',
				'speechLang'       => 'fi-FI',
				'starters'         => array(
					'Mitä ohjelmistoarkkitehtuurin ja full stack -kehityksen kokemusta sinulla on?',
					'Kerro kokemuksestasi Atlassian-ratkaisuista, Forgesta ja API-integraatioista.',
					'Miten hyödynnät tekoälyä ja LLM-integraatioita käytännön projekteissa?',
					'Kerro SaaS-yrittäjyydestäsi ja ohjelmistojen IPR-kaupoista.',
				),
			),
			'en' => array(
				'assistantName'    => $settings['assistant_name'],
				'greeting'         => $settings['greeting_en'],
				'interviewGreeting'=> 'Ready to assist during your interview (Teams / Google Meet). I will listen to questions and formulate authentic answers grounded in your verified CV for your approval.',
				'ready'            => 'Ready',
				'thinking'         => 'Formulating answer...',
				'speaking'         => 'Speaking to meeting...',
				'error'            => 'Error',
				'soundOn'          => 'Sound ON',
				'soundOff'         => 'Sound OFF',
				'stopSpeech'       => 'Stop speech',
				'replay'           => 'Listen to answer',
				'newChat'          => 'New conversation',
				'minimize'         => 'Minimize',
				'close'            => 'Close',
				'inputPlaceholder' => 'Enter interviewer question or speak into mic...',
				'send'             => 'Formulate answer',
				'listening'        => 'Listening to interviewer... Speak into microphone',
				'micPermission'    => 'Microphone permission denied in browser.',
				'sources'          => 'Sources:',
				'approveSpeak'     => 'Approve & Speak to meeting',
				'pause'            => 'Pause',
				'resume'           => 'Resume',
				'dismiss'          => 'Dismiss',
				'proposedAnswer'   => 'Proposed Answer (Edit if needed)',
				'speechLang'       => 'en-US',
				'starters'         => array(
					'What is your software architecture and full-stack development experience?',
					'Tell me about your experience with Atlassian solutions, Forge, and APIs.',
					'How do you integrate generative AI and LLMs in practical applications?',
					'Can you share details on your SaaS entrepreneurship and software IPR transactions?',
				),
			),
			'sv' => array(
				'assistantName'    => $settings['assistant_name'],
				'greeting'         => $greeting_sv,
				'interviewGreeting'=> 'Redo att assistera vid anställningsintervjun (Teams / Google Meet). Jag lyssnar på frågor och formulerar autentiska svar baserade på din CV för ditt godkännande.',
				'ready'            => 'Redo',
				'thinking'         => 'Formulerar svar...',
				'speaking'         => 'Talar i mötet...',
				'error'            => 'Fel',
				'soundOn'          => 'Tal PÅ',
				'soundOff'         => 'Tal AV',
				'stopSpeech'       => 'Stoppa tal',
				'replay'           => 'Lyssna på svar',
				'newChat'          => 'Börja om',
				'minimize'         => 'Minimera',
				'close'            => 'Stäng',
				'inputPlaceholder' => 'Ange intervjuarens fråga eller tala i mikrofonen...',
				'send'             => 'Formulera svar',
				'listening'        => 'Lyssnar på intervjuaren... Tala i mikrofonen',
				'micPermission'    => 'Mikrofonåtkomst nekad i webbläsaren.',
				'sources'          => 'Källor:',
				'approveSpeak'     => 'Godkänn och tala i mötet',
				'pause'            => 'Pausa',
				'resume'           => 'Fortsätt',
				'dismiss'          => 'Avvisa',
				'proposedAnswer'   => 'Föreslaget svar (Redigera vid behov)',
				'speechLang'       => 'sv-SE',
				'starters'         => array(
					'Berätta om din erfarenhet av programvaruarkitektur och full-stack-utveckling.',
					'Vad har du för erfarenhet av Atlassian-ekosystemet, Forge och API-integrationer?',
					'Hur implementerar du AI- och LLM-lösningar i produktion?',
					'Berätta om ditt SaaS-företagande och försäljning av mjukvaru-IPR.',
				),
			),
		);

		$active_i18n = $i18n_languages[ $current_lang ] ?? $i18n_languages['fi'];

		wp_localize_script( 'gemini-avatar-widget-js', 'geminiAvatarConfig', array(
			'apiUrl'         => esc_url_raw( rest_url( 'gemini-avatar/v1/chat' ) ),
			'ttsUrl'         => esc_url_raw( rest_url( 'gemini-avatar/v1/tts' ) ),
			'popoutUrl'      => esc_url_raw( add_query_arg( 'gemini_popout', '1', home_url( '/' ) ) ),
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
