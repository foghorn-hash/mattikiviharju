<?php

class AI_CV_Tailor_OpenAI {

	private $api_key;
	private $model;

	public function __construct() {
		$settings = get_option( 'ai_cv_tailor_settings', array() );
		$this->api_key = $settings['openai_api_key'] ?? '';
		$this->model = $settings['model'] ?? 'gpt-4o';
	}

	public function is_configured() {
		return ! empty( $this->api_key );
	}

	public function generate_analysis_and_content( $job_description, $language ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'no_api_key', 'OpenAI API avainta ei ole asetettu.' );
		}

		$settings = get_option( 'ai_cv_tailor_settings', array() );
		if ( empty( $settings['delivery_terms_url'] ) || empty( $settings['privacy_policy_url'] ) ) {
			return new WP_Error( 'missing_terms', 'Delivery Terms ja Privacy Policy pitää lisätä asetuksiin ennen julkaisua.' );
		}

		$cv_data = get_option( 'ai_cv_profile_data', array() );
		if ( ! is_array( $cv_data ) ) {
			$cv_data = array();
		}

		$experiences = get_posts( array(
			'post_type'      => 'cv_experience',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );
		
		if ( ! empty( $experiences ) ) {
			$cv_data['dynamic_experiences'] = array();
			foreach ( $experiences as $exp ) {
				$cv_data['dynamic_experiences'][] = array(
					'role'    => $exp->post_title,
					'company' => get_post_meta( $exp->ID, '_cv_experience_company', true ),
					'dates'   => get_post_meta( $exp->ID, '_cv_experience_dates', true ),
					'summary' => $exp->post_excerpt,
				);
			}
		}

		$projects = get_posts( array(
			'post_type'      => 'cv_project',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		) );

		if ( ! empty( $projects ) ) {
			$cv_data['dynamic_projects'] = array();
			foreach ( $projects as $proj ) {
				$cv_data['dynamic_projects'][] = array(
					'title'       => $proj->post_title,
					'description' => get_post_meta( $proj->ID, '_cv_project_description', true ),
					'tech_stack'  => get_post_meta( $proj->ID, '_cv_project_meta', true ),
					'url'         => get_post_meta( $proj->ID, '_cv_project_link', true ),
				);
			}
		}

		$cv_json_string = wp_json_encode( $cv_data, JSON_UNESCAPED_UNICODE );

		$system_prompt = $this->get_system_prompt( $language, $settings );

		$messages = array(
			array(
				'role'    => 'system',
				'content' => $system_prompt
			),
			array(
				'role'    => 'user',
				'content' => "Tässä on käyttäjän CV-data:\n" . $cv_json_string . "\n\nJa tässä on työpaikkailmoitus:\n" . $job_description
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
				'temperature' => 0.7,
				'response_format' => array( 'type' => 'json_object' ) // Important for valid JSON
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
			// Attempt to parse to verify it's valid JSON
			$parsed = json_decode( $content, true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $parsed;
			} else {
				return new WP_Error( 'json_parse_error', 'OpenAI ei palauttanut validia JSON-muotoa.' );
			}
		}

		return new WP_Error( 'unknown_error', 'Tuntematon virhe OpenAI-kutsussa.' );
	}

	private function get_system_prompt( $language, $settings = array() ) {
		$lang_instruction = ( $language === 'en' ) ? 'Respond entirely in English.' : 'Vastaa kokonaan suomeksi.';
		
		$legal_text = '';
		if ( ! empty( $settings['delivery_terms_url'] ) && ! empty( $settings['privacy_policy_url'] ) ) {
			$legal_text = "Lisää sähköposti-, LinkedIn- ja freelance-platform-viesteihin/hakemuskirjeisiin aina automaattisesti tämä lyhyt sopimusmaininta:\n";
			$legal_text .= "\"Työ tehdään i4ware Softwaren toimitusehtojen ja tietosuojaselosteen mukaisesti:\n";
			$legal_text .= "Delivery Terms: " . esc_url_raw( $settings['delivery_terms_url'] ) . "\n";
			$legal_text .= "Privacy Policy: " . esc_url_raw( $settings['privacy_policy_url'] ) . "\"\n";
			$legal_text .= "Älä keksi omia sopimuslinkkejä.";
		}

		return <<<EOT
Olet asiantunteva ura- ja rekrytointikonsultti. Tehtäväsi on analysoida annettu työpaikkailmoitus ja räätälöidä käyttäjän CV-data eri kohderyhmille (HR, CTO, CEO, Team Lead, Recruiter).

TÄRKEÄT SÄÄNNÖT:
- Älä keksi kokemusta, asiakkaita tai sertifikaatteja. Käytä VAIN annettua CV-dataa.
- Jos jokin vaatimus ei täyty, mainitse se rehellisesti mutta positiivisesti, tai jätä huomiotta.
- Räätälöi sisältö työpaikkailmoituksen mukaan.
- Luo jokaiselle vastaanottajalle oma painotettu versio.
- $lang_instruction
$legal_text
- Sinun ON palautettava vastaus TISMALLEEN alla olevassa JSON-muodossa. Vain validi JSON sallitaan.

HR-painotus: selkeä profiili, koulutus, työkokemus, sopivuus, saatavuus, laskutus Y-tunnuksella.
CTO-painotus: tekninen osaaminen, arkkitehtuuri, teknologiat, integraatiot, projektit ja toteutuskyky.
CEO-painotus: liiketoimintahyöty, kustannustehokkuus, nopea käyttöönotto, yrittäjäkokemus, ROI, riskien vähentäminen.
Team Lead -painotus: yhteistyö, tiimityö, käytännön tekeminen, ongelmanratkaisu, kommunikaatio.
Recruiter-painotus: tiivis yhteenveto, avaintaidot, saatavuus, rooliin sopivuus, helppo esittely asiakkaalle.

JSON-rakenne:
{
  "job_analysis": {
    "summary": "Lyhyt yhteenveto roolista",
    "match_score": 85,
    "required_skills": ["Taito 1", "Taito 2"],
    "nice_to_have_skills": ["Taito 3"],
    "keywords": ["avainsana1"],
    "risks_or_gaps": ["mahdolliset puutteet osaamisessa"]
  },
  "audiences": {
    "hr": {
      "cv_title": "Otsikko CV:lle",
      "profile_summary": "Räätälöity profiiliteksti",
      "selected_skills": ["Valitut taidot"],
      "selected_projects": [{"name": "Projekti 1", "description": "Kuvaus", "url": "Linkki tai URL jos CV-datassa on"}],
      "selected_experience": [{"title": "Titteli", "company": "Yritys", "period": "Aika", "description": "Kuvaus", "url": "Linkki yritykseen tai työhön jos CV-datassa on"}],
      "cover_letter": "Hakemuskirje teksti",
      "motivation_letter": "Motivaatiokirje teksti"
    },
    "cto": { ...samat kentät kuin yllä... },
    "ceo": { ...samat kentät... },
    "team_lead": { ...samat kentät... },
    "recruiter": { ...samat kentät... }
  },
  "call_script": "Puheluskripti soittamista varten (esim. Hei, tässä Matti...)"
}
EOT;
	}
}
