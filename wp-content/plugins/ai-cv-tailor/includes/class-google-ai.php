<?php
/**
 * Google AI Pro (Gemini API) Integration for Job Search and Recruitment Page Analysis
 *
 * @package AI_CV_Tailor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_CV_Tailor_Google_AI {

	/**
	 * Default Gemini API endpoint
	 */
	const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

	/**
	 * Get Google AI / Gemini API Key
	 *
	 * Checks settings, wp-config constants, and environment variables.
	 *
	 * @return string
	 */
	public function get_api_key() {
		if ( defined( 'GOOGLE_AI_API_KEY' ) && ! empty( GOOGLE_AI_API_KEY ) ) {
			return GOOGLE_AI_API_KEY;
		}
		if ( defined( 'GEMINI_API_KEY' ) && ! empty( GEMINI_API_KEY ) ) {
			return GEMINI_API_KEY;
		}

		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		if ( ! empty( $autopilot_settings['google_ai_api_key'] ) ) {
			return trim( $autopilot_settings['google_ai_api_key'] );
		}

		$base_settings = get_option( 'ai_cv_tailor_settings', array() );
		if ( ! empty( $base_settings['google_ai_api_key'] ) ) {
			return trim( $base_settings['google_ai_api_key'] );
		}

		$env_key = getenv( 'GOOGLE_AI_API_KEY' ) ?: getenv( 'GEMINI_API_KEY' );
		if ( ! empty( $env_key ) ) {
			return trim( $env_key );
		}

		return '';
	}

	/**
	 * Get selected model name
	 *
	 * @return string
	 */
	public function get_model() {
		$model = '';
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		if ( ! empty( $autopilot_settings['google_ai_model'] ) ) {
			$model = sanitize_text_field( $autopilot_settings['google_ai_model'] );
		}

		if ( empty( $model ) ) {
			$base_settings = get_option( 'ai_cv_tailor_settings', array() );
			if ( ! empty( $base_settings['google_ai_model'] ) ) {
				$model = sanitize_text_field( $base_settings['google_ai_model'] );
			}
		}

		if ( empty( $model ) ) {
			$model = 'gemini-3.5-flash';
		}

		// Automatically map retired / deprecated models to active gemini-3.5-flash
		$deprecated = array(
			'gemini-1.5-pro',
			'gemini-2.5-pro',
			'gemini-1.5-flash',
			'gemini-2.5-flash',
		);
		if ( in_array( $model, $deprecated, true ) ) {
			$model = 'gemini-3.5-flash';
		}

		return $model;
	}

	/**
	 * Check if Google Search Grounding is enabled
	 *
	 * @return bool
	 */
	public function is_search_grounding_enabled() {
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		return ! empty( $autopilot_settings['google_search_grounding'] );
	}

	/**
	 * Check if API key is configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		$key = $this->get_api_key();
		return ! empty( $key );
	}

	/**
	 * Test connection to Google AI API
	 *
	 * @return array|WP_Error
	 */
	public function test_connection() {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'no_api_key', 'Google AI (Gemini) API-avain puuttuu. Määritä se asetuksissa.' );
		}

		$prompt = 'Vastaa lyhyesti: "Google AI Pro yhteys muodostettu onnistuneesti."';
		$response = $this->generate_content( $prompt, array( 'temperature' => 0.1 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'success' => true,
			'message' => $response,
			'model'   => $this->get_model(),
		);
	}

	/**
	 * Call Gemini generateContent API
	 *
	 * @param string $prompt
	 * @param array  $options
	 * @return string|WP_Error
	 */
	public function generate_content( $prompt, $options = array() ) {
		$api_key = $this->get_api_key();
		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', 'Google AI API avainta ei ole asetettu.' );
		}

		$model = ! empty( $options['model'] ) ? $options['model'] : $this->get_model();
		$url = self::API_BASE_URL . '/models/' . $model . ':generateContent?key=' . $api_key;

		$body = array(
			'contents' => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array( 'text' => $prompt ),
					),
				),
			),
			'generationConfig' => array(
				'temperature'     => isset( $options['temperature'] ) ? (float) $options['temperature'] : 0.4,
				'maxOutputTokens' => 8192,
			),
		);

		if ( ! empty( $options['enable_google_search'] ) ) {
			// Enable Google Search Grounding tool
			$body['tools'] = array(
				array(
					'googleSearch' => (object) array(),
				),
			);
		}

		if ( ! empty( $options['response_schema'] ) || ! empty( $options['json'] ) ) {
			$body['generationConfig']['responseMimeType'] = 'application/json';
		}

		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 90,
		);

		AI_CV_Tailor_Autopilot_Logger::info( "Google AI Pro request: Model={$model}, Grounding=" . ( ! empty( $options['enable_google_search'] ) ? 'yes' : 'no' ) );

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( 'Google AI API HTTP virhe: ' . $response->get_error_message() );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data = json_decode( $response_body, true );

		if ( 200 !== $status_code ) {
			$error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : "HTTP status {$status_code}";
			AI_CV_Tailor_Autopilot_Logger::error( "Google AI API Error [{$status_code}]: {$error_msg}" );

			// Automatic fallback if model was deprecated (404), quota exceeded (429), or temporary high demand (503)
			if ( ( 404 === $status_code || 429 === $status_code || 503 === $status_code ) && empty( $options['is_retry'] ) ) {
				$fallback_model = ( 'gemini-flash-lite-latest' !== $model ) ? 'gemini-flash-lite-latest' : 'gemini-3.5-flash';
				AI_CV_Tailor_Autopilot_Logger::info( "Retrying [{$status_code}] with fallback model: {$fallback_model}" );
				if ( 503 === $status_code ) {
					sleep( 1 );
				}
				$retry_options = $options;
				$retry_options['is_retry'] = true;
				$retry_options['model']    = $fallback_model;
				if ( 429 === $status_code ) {
					$retry_options['enable_google_search'] = false;
				}
				return $this->generate_content( $prompt, $retry_options );
			}

			return new WP_Error( 'google_ai_error', $error_msg, $data );
		}

		$text = '';
		if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$text = $data['candidates'][0]['content']['parts'][0]['text'];
		}

		// Also attach grounding metadata if available
		if ( isset( $data['candidates'][0]['groundingMetadata'] ) ) {
			$options['groundingMetadata'] = $data['candidates'][0]['groundingMetadata'];
		}

		return trim( $text );
	}

	/**
	 * Search Google for jobs and recruitment pages matching keywords
	 *
	 * Uses Google Search Grounding or Custom Search API to locate company career pages,
	 * listed jobs, and open application ("avoin hakemus") landing pages.
	 *
	 * @param string $keywords
	 * @param int    $max_results
	 * @return array|WP_Error
	 */
	public function search_google_jobs( $keywords, $max_results = 8 ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'no_api_key', 'Google AI API avainta ei ole määritetty.' );
		}

		AI_CV_Tailor_Autopilot_Logger::info( "Searching Google for jobs with keywords: '{$keywords}' (Max: {$max_results})" );

		$today = date( 'Y-m-d' );

		$prompt = <<<EOT
Olet rekrytointiasiantuntija ja verkkotiedonhakija. Tänään on päivämäärä {$today}.
Etsi Googlesta suomalaisia tai etätyötä (Suomeen/EU-alueelle) tarjoavia yrityksiä, joilla on avoinna olevia työpaikkoja, rekry-sivuja tai avoimen hakemuksen mahdollisuus hakusanalla: "{$keywords}".

Etsi nimenomaan:
1. Suoria yritysten omia rekrysivuja (esim. yritys.fi/ura, yritys.fi/rekry, yritys.fi/careers tai yritys.fi/avoin-hakemus)
2. Listattuja työpaikkailmoituksia tai toimeksiantoja yrityksen verkkosivuilla
3. Mahdollisia rekrytointisähköposteja tai yhteystietoja sivulta (esim. rekry@yritys.fi, careers@, info@)

Palauta tulokset TARKASTI JSON-muodossa ilman markdown-muotoilua, muodossa:
[
  {
    "company_name": "Yrityksen nimi",
    "role_title": "Työpaikan nimike tai 'Avoin työhakemus'",
    "url": "https://yritys.fi/rekry tai ilmoituksen suora URL",
    "job_type": "listattu" tai "avoin_hakemus",
    "snippet": "Lyhyt kuvaus toimeksiannosta tai yrityksen rekrytoinnista",
    "contact_email_hint": "rekry@yritys.fi jos löydetty tai tyhjä"
  }
]
Palauta korkeintaan {$max_results} parasta ja varmistettua hakutulosta, joissa on toimiva yrityksen oma URL. Varmista että URLit ovat oikeita ja suoria.
EOT;

		$response_text = $this->generate_content( $prompt, array(
			'enable_google_search' => true,
			'temperature'          => 0.2,
		) );

		if ( is_wp_error( $response_text ) ) {
			// If google_search tool fails (e.g. not allowed on free tier), try standard generation
			AI_CV_Tailor_Autopilot_Logger::info( 'Grounding call failed, falling back to standard prompt: ' . $response_text->get_error_message() );
			$response_text = $this->generate_content( $prompt, array(
				'enable_google_search' => false,
				'temperature'          => 0.3,
			) );
		}

		if ( is_wp_error( $response_text ) ) {
			return $response_text;
		}

		$cleaned_json = $this->extract_json( $response_text );
		$results = json_decode( $cleaned_json, true );

		if ( ! is_array( $results ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( 'Google AI did not return a valid JSON array: ' . substr( $response_text, 0, 200 ) );
			return new WP_Error( 'parse_error', 'Google AI vastausta ei voitu jäsentää JSON-muotoon.' );
		}

		// Filter and sanitize results
		$sanitized_results = array();
		foreach ( $results as $item ) {
			$url = esc_url_raw( $item['url'] ?? '' );
			if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
				continue;
			}

			$sanitized_results[] = array(
				'company_name'       => sanitize_text_field( $item['company_name'] ?? 'Tuntematon yritys' ),
				'role_title'         => sanitize_text_field( $item['role_title'] ?? $keywords ),
				'url'                => $url,
				'job_type'           => ( isset( $item['job_type'] ) && 'avoin_hakemus' === $item['job_type'] ) ? 'avoin_hakemus' : 'listattu',
				'snippet'            => sanitize_textarea_field( $item['snippet'] ?? '' ),
				'contact_email_hint' => sanitize_email( $item['contact_email_hint'] ?? '' ),
			);
		}

		AI_CV_Tailor_Autopilot_Logger::success( 'Google AI found ' . count( $sanitized_results ) . ' potential job targets for keyword: ' . $keywords );

		return $sanitized_results;
	}

	/**
	 * Fetch webpage content and extract clean text and links
	 *
	 * @param string $url
	 * @return array|WP_Error
	 */
	public function fetch_page_content( $url ) {
		$url = esc_url_raw( $url );
		if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return new WP_Error( 'invalid_url', 'Virheellinen URL.' );
		}

		$response = wp_remote_get( $url, array(
			'timeout'     => 20,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 (compatible; JobSeekerBot/1.0; +' . home_url() . ')',
			'headers'     => array(
				'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language' => 'fi,en-US,en;q=0.9',
			),
			'sslverify'   => false, // Avoid SSL failures in local XAMPP
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'http_error', "Sivun haku epäonnistui (HTTP {$code}) kohteesta: {$url}" );
		}

		$html = wp_remote_retrieve_body( $response );
		if ( empty( $html ) ) {
			return new WP_Error( 'empty_body', 'Sivun sisältö oli tyhjä.' );
		}

		// Convert charset if needed
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			if ( preg_match( '/charset=([a-zA-Z0-9-]+)/i', $content_type, $matches ) ) {
				$charset = strtolower( $matches[1] );
				if ( 'utf-8' !== $charset ) {
					$html = mb_convert_encoding( $html, 'UTF-8', $charset );
				}
			}
		}

		// Clean HTML to extract readable text
		$clean_text = $this->extract_clean_text( $html );

		return array(
			'url'        => $url,
			'html'       => $html,
			'clean_text' => $clean_text,
		);
	}

	/**
	 * Fish public emails from HTML content and check contact subpages
	 *
	 * Scans mailto links, regex email patterns, and prioritizes recruitment addresses
	 * (rekry@, careers@, jobs@, ura@, hr@, info@, contact@).
	 *
	 * @param string $html
	 * @param string $page_url
	 * @return array List of unique found emails with metadata
	 */
	public function fish_public_emails( $html, $page_url ) {
		$emails = array();

		// 1. Extract from mailto: links
		if ( preg_match_all( '/href=[\'"]mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})[\'"]/i', $html, $matches ) ) {
			foreach ( $matches[1] as $email ) {
				$email = strtolower( trim( $email ) );
				if ( $this->is_valid_email( $email ) ) {
					$emails[] = $email;
				}
			}
		}

		// 2. Extract general email pattern from text and HTML
		if ( preg_match_all( '/\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b/i', $html, $matches ) ) {
			foreach ( $matches[0] as $email ) {
				$email = strtolower( trim( $email ) );
				if ( $this->is_valid_email( $email ) ) {
					$emails[] = $email;
				}
			}
		}

		$emails = array_unique( $emails );

		// 3. If no email found on this page, look for internal contact / rekry link and crawl one
		if ( empty( $emails ) && ! empty( $page_url ) ) {
			$subpage_url = $this->find_contact_or_careers_subpage( $html, $page_url );
			if ( ! empty( $subpage_url ) && $subpage_url !== $page_url ) {
				AI_CV_Tailor_Autopilot_Logger::info( "No emails on main page, crawling contact subpage: {$subpage_url}" );
				$sub_res = wp_remote_get( $subpage_url, array(
					'timeout'   => 10,
					'sslverify' => false,
					'headers'   => array( 'User-Agent' => 'Mozilla/5.0 (JobSeekerBot/1.0)' ),
				) );
				if ( ! is_wp_error( $sub_res ) && 200 === wp_remote_retrieve_response_code( $sub_res ) ) {
					$sub_html = wp_remote_retrieve_body( $sub_res );
					if ( preg_match_all( '/\b[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}\b/i', $sub_html, $matches ) ) {
						foreach ( $matches[0] as $sub_email ) {
							$sub_email = strtolower( trim( $sub_email ) );
							if ( $this->is_valid_email( $sub_email ) ) {
								$emails[] = $sub_email;
							}
						}
					}
					$emails = array_unique( $emails );
				}
			}
		}

		// 4. Sort emails by recruitment priority
		usort( $emails, array( $this, 'sort_email_priority' ) );

		return $emails;
	}

	/**
	 * Validate if an extracted string is a genuine contact email
	 *
	 * @param string $email
	 * @return bool
	 */
	private function is_valid_email( $email ) {
		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return false;
		}

		// Filter out common false positives and image files masquerading as emails
		$invalid_patterns = array(
			'\.png$', '\.jpg$', '\.jpeg$', '\.webp$', '\.gif$', '\.svg$',
			'example\.com$', 'test\.com$', 'domain\.com$', 'company\.com$',
			'w3\.org$', 'schema\.org$', 'sentry\.io$', 'wordpress\.org$',
			'cloudflare\.com$', 'gravatar\.com$', 'placeholder', 'username@',
		);

		foreach ( $invalid_patterns as $pattern ) {
			if ( preg_match( '/' . $pattern . '/i', $email ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sort emails by priority: recruitment > general > personal
	 *
	 * @param string $a
	 * @param string $b
	 * @return int
	 */
	private function sort_email_priority( $a, $b ) {
		$score_a = $this->get_email_score( $a );
		$score_b = $this->get_email_score( $b );

		if ( $score_a === $score_b ) {
			return 0;
		}
		return ( $score_a > $score_b ) ? -1 : 1;
	}

	/**
	 * Score email address for recruitment relevance
	 *
	 * @param string $email
	 * @return int
	 */
	private function get_email_score( $email ) {
		$recruitment_prefixes = array( 'rekry@', 'rekrytointi@', 'recruitment@', 'careers@', 'jobs@', 'ura@', 'hr@', 'people@', 'talenthub@' );
		foreach ( $recruitment_prefixes as $p ) {
			if ( strpos( $email, $p ) === 0 ) {
				return 100;
			}
		}

		$general_prefixes = array( 'info@', 'contact@', 'hello@', 'office@', 'asiakaspalvelu@', 'tuki@' );
		foreach ( $general_prefixes as $p ) {
			if ( strpos( $email, $p ) === 0 ) {
				return 50;
			}
		}

		return 20;
	}

	/**
	 * Find internal link to contact or careers subpage in HTML
	 *
	 * @param string $html
	 * @param string $base_url
	 * @return string|null
	 */
	private function find_contact_or_careers_subpage( $html, $base_url ) {
		$parsed_base = parse_url( $base_url );
		$host = $parsed_base['host'] ?? '';
		$scheme = $parsed_base['scheme'] ?? 'https';

		if ( preg_match_all( '/href=[\'"]([^\'"]+)[\'"]/i', $html, $matches ) ) {
			$keywords = array( 'rekry', 'careers', 'yhteystiedot', 'contact', 'ota-yhteytta', 'meista', 'about', 'ura' );
			foreach ( $matches[1] as $href ) {
				foreach ( $keywords as $kw ) {
					if ( stripos( $href, $kw ) !== false ) {
						// Build absolute URL
						if ( strpos( $href, 'http' ) === 0 ) {
							$parsed_href = parse_url( $href );
							if ( isset( $parsed_href['host'] ) && $parsed_href['host'] === $host ) {
								return $href;
							}
						} elseif ( strpos( $href, '/' ) === 0 ) {
							return $scheme . '://' . $host . $href;
						} else {
							return rtrim( $base_url, '/' ) . '/' . ltrim( $href, '/' );
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * Clean HTML into plain readable text
	 *
	 * @param string $html
	 * @return string
	 */
	private function extract_clean_text( $html ) {
		// Remove scripts, styles, navigations, footers to reduce noise
		$html = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $html );
		$html = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $html );
		$html = preg_replace( '/<svg\b[^>]*>(.*?)<\/svg>/is', '', $html );
		$html = preg_replace( '/<noscript\b[^>]*>(.*?)<\/noscript>/is', '', $html );

		$text = wp_strip_all_tags( $html );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = preg_replace( '/\s+/', ' ', $text );

		// Cap at 15000 characters to stay within reasonable token limits
		if ( mb_strlen( $text ) > 15000 ) {
			$text = mb_substr( $text, 0, 15000 ) . '... [katkaistu]';
		}

		return trim( $text );
	}

	/**
	 * Analyze a recruitment webpage with Google AI Pro (Gemini Pro)
	 *
	 * Determines if it's an open application or listed job, extracts details,
	 * calculates match score against user profile, and fishes the contact email.
	 *
	 * @param string $url
	 * @param string $source_keyword
	 * @return array|WP_Error
	 */
	public function analyze_recruitment_page( $url, $source_keyword = '' ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'no_api_key', 'Google AI API avain puuttuu.' );
		}

		$fetch_result = $this->fetch_page_content( $url );
		if ( is_wp_error( $fetch_result ) ) {
			return $fetch_result;
		}

		$page_text = $fetch_result['clean_text'];
		$page_html = $fetch_result['html'];

		// Fish public emails from page
		$fished_emails = $this->fish_public_emails( $page_html, $url );
		$primary_email = ! empty( $fished_emails ) ? reset( $fished_emails ) : '';

		// Get user CV data and autopilot settings
		$cv_data = get_option( 'ai_cv_profile_data', array() );
		$cv_json = wp_json_encode( $cv_data, JSON_UNESCAPED_UNICODE );

		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		$settings_json = wp_json_encode( $autopilot_settings, JSON_UNESCAPED_UNICODE );

		$base_settings = get_option( 'ai_cv_tailor_settings', array() );
		$my_name = $base_settings['my_name'] ?? 'Matti Kiviharju';
		$company_name_sender = $base_settings['company_name'] ?? 'i4ware Software';

		$fished_emails_str = ! empty( $fished_emails ) ? implode( ', ', $fished_emails ) : 'Ei löydettyjä sähköposteja HTML-koodista';

		$analysis_date = current_time( 'Y-m-d H:i:s' );

		$prompt = <<<EOT
Olet huipputason rekrytointianalyytikko ja Google AI Pro -tekoälyagentti.
Tehtäväsi on analysoida yrityksen verkkosivun rekrysivu tai työpaikkailmoitus hakijan ({$my_name} / {$company_name_sender}) profiilia ja asetuksia vasten.

PÄIVÄMÄÄRÄ JA AIKA: {$analysis_date}
LÄHDE-URL: {$url}
SIVULTA KALASTETUT JULKISET SÄHKÖPOSTIT: {$fished_emails_str}

KÄYTTÄJÄN CV-DATA:
{$cv_json}

AUTOPILOT-ASETUKSET (Sallitut ja kielletyt teknologiat, tuntihinta, etätyö):
{$settings_json}

VERKKOSIVUN SISÄLTÖ:
{$page_text}

Määritä ja palauta analyysi TARKASTI alla olevassa JSON-muodossa:
{
  "company_name": "Yrityksen nimi",
  "role_title": "Roolin nimike (esim. 'Full-Stack Kehittäjä' tai 'Avoin työhakemus - Ohjelmistokehitys')",
  "job_type": "listattu" TAI "avoin_hakemus",
  "description": "Tiivis ja ammattimainen kuvaus toimeksiannosta tai avoimen haun tarpeista",
  "match_score": 85,
  "why_good_match": ["Syy 1 CV-kokemuksen perusteella", "Syy 2"],
  "risks": ["Mahdollinen riski tai puute"],
  "missing_requirements": ["Puuttuva taito tai teknologia"],
  "contact_email": "Valitse tähän paras julkinen sähköposti hakemuksen lähettämiseen (priorisoi sivulta kalastettuja sähköposteja kuten rekry@ tai contact@, tai sivun tekstistä löytyvää rekrytoijan osoitetta)",
  "contact_person": "Rekrytoijan tai yhteyshenkilön nimi jos mainittu, muuten tyhjä",
  "suggested_subject": "Työhakemus: [Rooli] - {$my_name} / {$company_name_sender}",
  "suggested_cover_letter": "Ammattimainen, vakuuttava ja suoraan kyseiselle yritykselle räätälöity suomenkielinen hakemuskirje (cover letter), jossa esitellään hakijan vahvuudet, soveltuvuus tähän rooliin/yritykseen ja yhteystiedot.",
  "recommended_cv_audiences": ["cto", "hr", "recruiter"]
}

TÄRKEÄT SÄÄNNÖT:
1. "job_type": Jos sivu on yleinen rekrysivu tai siinä kehotetaan jättämään avoin hakemus, aseta "job_type": "avoin_hakemus". Jos sivulla on tietty nimetty työpaikka, aseta "job_type": "listattu".
2. "contact_email": Jos sivulta kalastetuissa sähköposteissa on sopiva rekry- tai yleisosoite, käytä sitä ensisijaisesti.
3. Pisteytä match_score (0-100) rehellisesti vastaavuuden mukaan.
4. Älä lisää mitään ylimääräistä tekstiä tai markdownia, ainoastaan puhdas JSON-objekti.
EOT;

		$response_text = $this->generate_content( $prompt, array(
			'temperature' => 0.2,
			'json'        => true,
		) );

		if ( is_wp_error( $response_text ) ) {
			return $response_text;
		}

		$cleaned_json = $this->extract_json( $response_text );
		$parsed = json_decode( $cleaned_json, true );

		if ( ! is_array( $parsed ) || ! isset( $parsed['match_score'] ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( 'Google AI did not return expected analysis JSON: ' . substr( $response_text, 0, 300 ) );
			return new WP_Error( 'json_parse_error', 'Analyysin JSON-jäsennys epäonnistui Google AI Prolta.' );
		}

		// Ensure primary email fallback if AI returned empty but we fished one
		if ( empty( $parsed['contact_email'] ) && ! empty( $primary_email ) ) {
			$parsed['contact_email'] = $primary_email;
		}

		$parsed['analysis_date']  = $analysis_date;
		$parsed['source_url']     = $url;
		$parsed['source_keyword'] = $source_keyword;
		$parsed['all_emails']     = $fished_emails;

		AI_CV_Tailor_Autopilot_Logger::success( "Google AI analyzed: {$parsed['company_name']} - Score: {$parsed['match_score']}%, Type: {$parsed['job_type']}, Email: {$parsed['contact_email']}" );

		return $parsed;
	}

	/**
	 * Save analyzed job into freelance_job Custom Post Type
	 *
	 * @param array $data
	 * @return int|WP_Error Post ID
	 */
	public function save_analyzed_job( $data ) {
		$url = esc_url_raw( $data['source_url'] ?? '' );
		if ( empty( $url ) ) {
			return new WP_Error( 'missing_url', 'Lähde-URL puuttuu.' );
		}

		$existing_id = $this->find_job_by_url( $url );
		$company_name = sanitize_text_field( $data['company_name'] ?? 'Tuntematon' );
		$role_title   = sanitize_text_field( $data['role_title'] ?? 'Työpaikka' );
		$source_label = 'Google AI Pro' . ( ! empty( $data['source_keyword'] ) ? ': ' . sanitize_text_field( $data['source_keyword'] ) : '' );

		$title = $company_name . ' - ' . $role_title;

		if ( $existing_id ) {
			// Update existing job post
			$post_id = $existing_id;
			wp_update_post( array(
				'ID'         => $post_id,
				'post_title' => $title,
			) );
		} else {
			// Create new freelance_job post
			$post_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => 'freelance_job',
				'post_status' => 'publish',
			) );

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}
		}

		// Update metadata
		update_post_meta( $post_id, 'source', $source_label );
		update_post_meta( $post_id, 'source_url', $url );
		update_post_meta( $post_id, 'company_name', $company_name );
		update_post_meta( $post_id, 'role_title', $role_title );
		update_post_meta( $post_id, 'job_type', sanitize_text_field( $data['job_type'] ?? 'listattu' ) );
		update_post_meta( $post_id, 'analysis_date', sanitize_text_field( $data['analysis_date'] ?? current_time( 'Y-m-d H:i:s' ) ) );
		update_post_meta( $post_id, 'description', sanitize_textarea_field( $data['description'] ?? '' ) );
		update_post_meta( $post_id, 'match_score', intval( $data['match_score'] ?? 0 ) );
		update_post_meta( $post_id, 'ai_analysis_json', wp_json_encode( $data, JSON_UNESCAPED_UNICODE ) );

		if ( ! empty( $data['contact_email'] ) ) {
			update_post_meta( $post_id, 'contact_email', sanitize_email( $data['contact_email'] ) );
		}
		if ( ! empty( $data['contact_person'] ) ) {
			update_post_meta( $post_id, 'contact_person', sanitize_text_field( $data['contact_person'] ) );
		}
		if ( ! empty( $data['suggested_cover_letter'] ) ) {
			update_post_meta( $post_id, 'suggested_cover_letter', wp_kses_post( $data['suggested_cover_letter'] ) );
		}
		if ( ! empty( $data['suggested_subject'] ) ) {
			update_post_meta( $post_id, 'suggested_subject', sanitize_text_field( $data['suggested_subject'] ) );
		}

		// Status setting
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		$min_match = isset( $autopilot_settings['min_score_app_suggestion'] ) ? intval( $autopilot_settings['min_score_app_suggestion'] ) : 75;
		$current_status = get_post_meta( $post_id, 'status', true );

		if ( empty( $current_status ) || 'New' === $current_status || 'Analyzed' === $current_status ) {
			if ( intval( $data['match_score'] ?? 0 ) >= $min_match ) {
				update_post_meta( $post_id, 'status', 'Good Match' );
			} else {
				update_post_meta( $post_id, 'status', 'Analyzed' );
			}
		}

		update_post_meta( $post_id, 'autopilot_processed', '0' );

		return $post_id;
	}

	/**
	 * Find existing freelance_job post by source_url
	 *
	 * @param string $url
	 * @return int|null
	 */
	public function find_job_by_url( $url ) {
		global $wpdb;
		$post_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'source_url' AND meta_value = %s LIMIT 1",
			$url
		) );
		return $post_id ? (int) $post_id : null;
	}

	/**
	 * Extract clean JSON string from text that might contain code fences
	 *
	 * @param string $text
	 * @return string
	 */
	private function extract_json( $text ) {
		$text = trim( $text );

		// 1. Remove ```json ... ``` or ``` ... ```
		if ( preg_match( '/```(?:json)?\s*([\s\S]*?)\s*```/i', $text, $matches ) ) {
			$text = trim( $matches[1] );
		}

		// 2. Direct decode check
		$test = json_decode( $text, true );
		if ( is_array( $test ) ) {
			return $text;
		}

		// 3. Find outermost [ ... ] or { ... }
		$pos_brace   = strpos( $text, '{' );
		$pos_bracket = strpos( $text, '[' );

		if ( false !== $pos_bracket && ( false === $pos_brace || $pos_bracket < $pos_brace ) ) {
			$last_bracket = strrpos( $text, ']' );
			if ( false !== $last_bracket ) {
				$candidate = substr( $text, $pos_bracket, $last_bracket - $pos_bracket + 1 );
				if ( is_array( json_decode( $candidate, true ) ) ) {
					return $candidate;
				}
			}
		}

		if ( false !== $pos_brace ) {
			$last_brace = strrpos( $text, '}' );
			if ( false !== $last_brace ) {
				$candidate = substr( $text, $pos_brace, $last_brace - $pos_brace + 1 );
				if ( is_array( json_decode( $candidate, true ) ) ) {
					return $candidate;
				}
			}

			// Incomplete JSON: repair by closing open object
			$sub = substr( $text, $pos_brace );
			$sub = preg_replace( '/,\s*("[^"]*"?\s*:\s*)?[^,}]*$/s', '', $sub );
			$sub = rtrim( $sub, " \t\n\r\0\x0B," ) . "\n}";
			if ( is_array( json_decode( $sub, true ) ) ) {
				return $sub;
			}
		}

		return $text;
	}
}
