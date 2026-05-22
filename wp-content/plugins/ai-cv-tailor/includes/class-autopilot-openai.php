<?php

class AI_CV_Tailor_Autopilot_OpenAI {

	private $api_key;
	private $model;

	public function __construct() {
		$settings = get_option( 'ai_cv_settings', array() );
		$this->api_key = $settings['openai_api_key'] ?? '';
		$this->model = $settings['model'] ?? 'gpt-4o';
	}

	public function is_configured() {
		return ! empty( $this->api_key );
	}

	public function analyze_opportunity( $post_id ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'no_api_key', 'OpenAI API avainta ei ole asetettu.' );
		}

		$job_description = get_post_meta( $post_id, 'description', true );
		if ( empty( $job_description ) ) {
			return new WP_Error( 'no_description', 'Työpaikkailmoituksen kuvaus puuttuu.' );
		}

		$cv_data = get_option( 'ai_cv_profile_data', array() );
		$cv_json_string = wp_json_encode( $cv_data );

		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		$settings_json_string = wp_json_encode( $autopilot_settings );
		
		$base_settings = get_option( 'ai_cv_settings', array() );

		$system_prompt = $this->get_analysis_system_prompt( $base_settings );

		$messages = array(
			array(
				'role'    => 'system',
				'content' => $system_prompt
			),
			array(
				'role'    => 'user',
				'content' => "Tässä on käyttäjän CV-data:\n" . $cv_json_string . "\n\nTässä on Autopilot-asetukset (esim. sallitut/kielletyt teknologiat):\n" . $settings_json_string . "\n\nJa tässä on työpaikkailmoitus:\n" . $job_description
			)
		);

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $this->api_key
			),
			'body'    => wp_json_encode( array(
				'model'       => $this->model,
				'messages'    => $messages,
				'temperature' => 0.5,
				'response_format' => array( 'type' => 'json_object' )
			) ),
			'timeout' => 60
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( isset( $data['error'] ) ) {
			return new WP_Error( 'openai_error', $data['error']['message'] );
		}

		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			$content = $data['choices'][0]['message']['content'];
			$parsed = json_decode( $content, true );
			
			if ( json_last_error() === JSON_ERROR_NONE ) {
				// Save results to post meta
				$match_score = isset($parsed['match_score']) ? intval($parsed['match_score']) : 0;
				update_post_meta( $post_id, 'match_score', $match_score );
				update_post_meta( $post_id, 'ai_analysis_json', wp_json_encode($parsed, JSON_UNESCAPED_UNICODE) );
				
				// Determine status based on settings
				$min_score_app = isset($autopilot_settings['min_score_app_suggestion']) ? intval($autopilot_settings['min_score_app_suggestion']) : 75;
				if ( $match_score >= $min_score_app ) {
					update_post_meta( $post_id, 'status', 'Good Match' );
				} else {
					update_post_meta( $post_id, 'status', 'Analyzed' );
				}
				
				return $parsed;
			} else {
				return new WP_Error( 'json_parse_error', 'OpenAI ei palauttanut validia JSON-muotoa.' );
			}
		}

		return new WP_Error( 'unknown_error', 'Tuntematon virhe OpenAI-kutsussa.' );
	}

	public function generate_application_from_opportunity( $post_id ) {
		// Get job details
		$company_name = get_post_meta( $post_id, 'company_name', true );
		$role_title   = get_post_meta( $post_id, 'role_title', true );
		$job_url      = get_post_meta( $post_id, 'source_url', true );
		$job_desc     = get_post_meta( $post_id, 'description', true );
		$analysis_json = get_post_meta( $post_id, 'ai_analysis_json', true );
		
		// Run standard generator
		require_once AI_CV_TAILOR_DIR . 'includes/class-openai.php';
		$standard_openai = new AI_CV_Tailor_OpenAI();
		$result = $standard_openai->generate_analysis_and_content( $job_desc, 'fi' );
		
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		
		$post_title = $company_name . ' - ' . $role_title . ' (Autopilot)';
		$post_title = html_entity_decode( $post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		
		// Create ai_cv_application
		$app_post_id = wp_insert_post( array(
			'post_title'  => $post_title,
			'post_type'   => 'ai_cv_application',
			'post_status' => 'publish',
		) );

		if ( is_wp_error( $app_post_id ) ) {
			return new WP_Error( 'insert_failed', 'Hakemuksen luonti epäonnistui' );
		}

		// Save standard metas
		update_post_meta( $app_post_id, '_company_name', $company_name );
		update_post_meta( $app_post_id, '_job_title', $role_title );
		update_post_meta( $app_post_id, '_job_url', $job_url );
		update_post_meta( $app_post_id, '_job_description', $job_desc );
		update_post_meta( $app_post_id, '_language', 'fi' );
		update_post_meta( $app_post_id, '_openai_analysis', wp_json_encode($result, JSON_UNESCAPED_UNICODE) );
		update_post_meta( $app_post_id, 'source_freelance_job_id', $post_id );

		// Generate tokens
		$audiences = array( 'hr', 'cto', 'ceo', 'team_lead', 'recruiter' );
		$tokens = array();
		foreach ( $audiences as $audience ) {
			$tokens[ $audience ] = wp_generate_password( 32, false );
		}
		update_post_meta( $app_post_id, '_tokens', $tokens );

		// Link back to opportunity
		update_post_meta( $post_id, 'generated_application_id', $app_post_id );
		update_post_meta( $post_id, 'status', 'Applied' ); // or 'Awaiting Application'
		
		// Follow up date (e.g. +7 days)
		update_post_meta( $post_id, 'follow_up_date', date('Y-m-d', strtotime('+7 days')) );

		return array( 'success' => true, 'app_id' => $app_post_id );
	}

	private function get_analysis_system_prompt( $settings = array() ) {
		$legal_text = '';
		if ( ! empty( $settings['delivery_terms_url'] ) && ! empty( $settings['privacy_policy_url'] ) ) {
			$legal_text = "Lisää 'follow_up_script' ja 'application_strategy' -kenttiin automaattisesti tämä lyhyt sopimusmaininta viestin loppuun:\n";
			$legal_text .= "\"Työ tehdään i4ware Softwaren toimitusehtojen ja tietosuojaselosteen mukaisesti:\n";
			$legal_text .= "Delivery Terms: " . esc_url_raw( $settings['delivery_terms_url'] ) . "\n";
			$legal_text .= "Privacy Policy: " . esc_url_raw( $settings['privacy_policy_url'] ) . "\"\n";
			$legal_text .= "Älä keksi omia sopimuslinkkejä.";
		}
		
		return <<<EOT
Olet analyyttinen rekrytointi-AI. Tehtäväsi on pisteyttää freelancer-toimeksiantoja käyttäjän CV-datan ja asetusten perusteella.
Vastaa kokonaan suomeksi.

$legal_text

Sinun ON palautettava vastaus TISMALLEEN alla olevassa JSON-muodossa.

Pisteytyskriteerit (0-100):
- Teknologioiden vastaavuus CV:hen ja asetusten sallittuihin teknologioihin (negatiivinen jos estetyissä).
- Toimiala, etätyömahdollisuus, B2B/Y-tunnus mahdollisuus.
- Tuntihinnan vastaavuus minimipyyntöön.
- Varoitusmerkit (esim. epämääräinen kuvaus, liian alhainen budjetti laskevat pisteitä).

TÄRKEÄT PISTEYTYSSÄÄNNÖT:
1. Nosta pisteitä selvästi, jos kuvauksessa tai tittelissä esiintyy seuraavia asioita: React, Laravel, PHP, WordPress, OpenAI, Jira, Atlassian, API, MySQL, Full-Stack, Senior, Remote, B2B.
2. Jos titteli sisältää jonkin näistä: "Full-Stack Developer", "Senior Fullstack Developer", "React Developer", "PHP Developer", "Laravel Developer", "WordPress Developer" tai "AI Developer", match_score EI SAA OLLA ALLE 60, ellei ilmoituksessa ole selkeitä "rejected keywords" -sanoja.

JSON-rakenne:
{
  "match_score": 85,
  "summary": "Lyhyt yhteenveto toimeksiannosta ja sen sopivuudesta",
  "why_good_match": ["Syy 1", "Syy 2"],
  "risks": ["Riski 1", "Riski 2"],
  "missing_requirements": ["Puuttuva taito 1"],
  "recommended_angle": "Millä kulmalla tätä kannattaa hakea",
  "suggested_hourly_rate": "Ehdotettu tuntihinta (esim. 85€/h)",
  "application_strategy": "Miten hakea (esim. lähetä viesti suoraan CTO:lle)",
  "follow_up_script": "Hei, laitoin teille hakemuksen...",
  "recommended_cv_audiences": ["cto", "ceo"]
}
EOT;
	}
}
