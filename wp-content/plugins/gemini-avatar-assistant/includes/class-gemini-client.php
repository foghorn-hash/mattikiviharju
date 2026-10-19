<?php
/**
 * Google Gemini API Client (Text & TTS)
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Gemini_Client {

	const API_BASE = 'https://generativelanguage.googleapis.com/v1beta';

	/**
	 * Generate an answer using the configured text model
	 *
	 * @param string $user_question
	 * @param array $history
	 * @param string $context_text
	 * @param string $lang 'fi', 'en', or 'sv'
	 * @param string $mode 'interview' (1st person job interview) or 'assistant' (3rd person site assistant)
	 * @param string $style 'concise', 'conversational', or 'technical'
	 * @return array
	 */
	public static function generate_answer( $user_question, $history = array(), $context_text = '', $lang = 'fi', $mode = 'interview', $style = 'conversational' ) {
		$api_key = Gemini_Avatar_Settings::get_api_key();
		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'error'   => 'Gemini API -avainta ei ole määritetty. Ylläpitäjä voi lisätä avaimen wp-config.php -tiedostoon (GOOGLE_AI_API_KEY) tai lisäosan asetuksiin.',
			);
		}

		$primary_model = sanitize_text_field( Gemini_Avatar_Settings::get( 'text_model', 'gemini-3.5-flash' ) );
		$temperature   = (float) Gemini_Avatar_Settings::get( 'temperature', 0.4 );

		if ( ! in_array( $lang, array( 'fi', 'en', 'sv' ), true ) ) {
			$lang = 'fi';
		}
		if ( ! in_array( $mode, array( 'interview', 'assistant' ), true ) ) {
			$mode = 'interview';
		}
		if ( ! in_array( $style, array( 'concise', 'conversational', 'technical' ), true ) ) {
			$style = 'conversational';
		}

		// Length and tone directives based on style
		$style_guide_en = "Target approx 30-50 seconds when spoken aloud (about 60-100 words). Natural, conversational, and direct.";
		$style_guide_fi = "Puhu noin 30-50 sekuntia ääneen luettuna (noin 60-100 sanaa). Luonnollinen, asiantunteva ja keskusteleva.";
		$style_guide_sv = "Sikta på ca 30-50 sekunder uppläst (ca 60-100 ord). Naturligt, professionellt och samtalsinriktat.";

		if ( 'concise' === $style ) {
			$style_guide_en = "Be very concise. Target approx 20-30 seconds spoken (about 35-60 words). State the direct core answer and key facts immediately without fluff.";
			$style_guide_fi = "Ole erittäin tiivis ja ytimekäs. Puhu noin 20-30 sekuntia (noin 35-60 sanaa). Anna suora ydinpointti heti ilman turhaa pohjustusta.";
			$style_guide_sv = "Var mycket koncis och rak. Sikta på ca 20-30 sekunder (ca 35-60 ord). Leverera kärnan omedelbart utan onödig utfyllnad.";
		} elseif ( 'technical' === $style ) {
			$style_guide_en = "Provide technical architectural depth. Target approx 60-90 seconds (about 120-160 words). Mention specific architecture decisions, libraries, database/API integration techniques, and quality considerations supported by the CV.";
			$style_guide_fi = "Tarjoa teknistä ja arkkitehtonista syvyyttä. Puhu noin 60-90 sekuntia (noin 120-160 sanaa). Nosta esiin arkkitehtuurivalintoja, rajapintoja, kirjastoja ja laadunvarmistusta CV-faktapohjaan nojaten.";
			$style_guide_sv = "Ge tekniskt och arkitektoniskt djup. Sikta på ca 60-90 sekunder (ca 120-160 ord). Lyft fram specifika tekniska lösningar, API-integrationer och arkitekturmönster från kunskapsbasen.";
		}

		if ( 'interview' === $mode ) {
			// Mode A: Interview Assistant (Formulating authentic answers for Matti in 1st person)
			if ( 'sv' === $lang ) {
				$system_instruction = "Du är Matti Kiviharjus personliga AI-intervjuassistent för anställningsintervjuer (Teams/Google Meet).\n"
					. "Din uppgift är att hjälpa Matti formulera tydliga, äkta och professionella svar på intervjuarens frågor på svenska.\n\n"
					. "STRIKTA REGLER:\n"
					. "1. TALA I FÖRSTA PERSON SOM MATTI (t.ex. \"Under min karriär har jag...\", \"I mina projekt har jag fokuserat på...\", \"Min erfarenhet av WordPress och PHP sträcker sig...\"). Texten godkänns av Matti innan den läses upp.\n"
					. "2. {$style_guide_sv}\n"
					. "3. Grunda ALLA påståenden ENBART på den bifogade verifierade kunskapsbasen (CV & projekt nedan). Hitta ALDRIG på examina, kundprojekt eller teknologier som inte finns i kunskapsbasen.\n"
					. "4. Om en fråga gäller teknik eller erfarenhet som inte finns i kunskapsbasen, säg ärligt och direkt vad du faktiskt har gjort inom närliggande områden och att du snabbt tar till dig nya verktyg.\n"
					. "5. Skriv i rent, talbart språk. Undvik råa URL-adresser, komplexa tabeller eller symboler som låter onaturliga vid uppläsning.";
				$context_prompt = "MATTIS VERIFIERADE CV- OCH PROJEKTKUNSKAPSBAS:\n\n" . $context_text . "\n\nSvara på intervjuarens fråga i första person på svenska:";
				$user_message_text = "Intervjuarens fråga: " . $user_question . "\n\n" . $context_prompt;
			} elseif ( 'en' === $lang ) {
				$system_instruction = "You are Matti Kiviharju's AI interview assistant for online job interviews (Microsoft Teams and Google Meet).\n"
					. "Your mission is to help Matti communicate his genuine professional experience clearly, concisely, and accurately in English.\n\n"
					. "STRICT RULES AND GUIDELINES:\n"
					. "1. FORMULATE THE ANSWER IN THE FIRST PERSON AS MATTI (e.g. \"In my work as a software architect...\", \"I have specialized in...\", \"My background in PHP, React, and WordPress...\"). Matti will review and approve this answer before speaking.\n"
					. "2. {$style_guide_en}\n"
					. "3. Base all answers ONLY on the provided verified CV and portfolio knowledge base below. NEVER invent qualifications, past employers, certifications, or project metrics.\n"
					. "4. If asked about technologies or subjects not supported in the CV, answer transparently about related strengths and your proven track record of rapidly adopting new stacks.\n"
					. "5. Format the text for natural spoken delivery. Avoid markdown tables, raw URLs, and special symbols that sound awkward when spoken aloud.";
				$context_prompt = "MATTI KIVIHARJU'S VERIFIED CV AND PORTFOLIO KNOWLEDGE BASE:\n\n" . $context_text . "\n\nFormulate the interview answer in first-person English:";
				$user_message_text = "Interviewer's question: " . $user_question . "\n\n" . $context_prompt;
			} else {
				$system_instruction = "Olet Matti Kiviharjun AI-haastatteluavustaja työhaastatteluissa (Microsoft Teams ja Google Meet).\n"
					. "Tehtäväsi on auttaa Mattia viestimään aidosta ammatillisesta osaamisestaan selkeästi, vakuuttavasti ja täsmällisesti suomeksi.\n\n"
					. "EHDOTTOMAT SÄÄNNÖT JA OHJEET:\n"
					. "1. MUOTOILE VASTAUS ENSIMMÄISESSÄ PERSOONASSA MATIN NÄKÖKULMASTA (esim. \"Olen toiminut ohjelmistoarkkitehtina...\", \"Kokemukseni kattaa...\", \"Omassa työssäni olen toteuttanut...\"). Teksti näytetään Matille esikatselussa ja hän hyväksyy sen ennen puhetta.\n"
					. "2. {$style_guide_fi}\n"
					. "3. Perusta kaikki vastaukset AINOASTAAN annettuun verifioituun CV- ja portfoliotietopohjaan. ÄLÄ KOSKAAN keksi työsuhteita, tutkintoja tai teknologioita, joita tietopohjassa ei mainita.\n"
					. "4. Jos kysytään osaamisalueesta, jota ei löydy tietopohjasta, totea se rehellisesti ja kerro läheisestä soveltuvasta kokemuksesta sekä kyvystäsi ottaa uusia teknologioita nopeasti haltuun.\n"
					. "5. Käytä luontevaa puhekieltä ja sujuvia lauserakenteita. Vältä raakoja verkkolinkkejä, monimutkaisia merkkejä tai taulukoita, jotka kuulostavat huonolta puhesynteesissä.";
				$context_prompt = "MATTI KIVIHARJUN TARKISTETTU CV- JA PORTFOLIOTIETOPOHJA:\n\n" . $context_text . "\n\nMuotoile vastauspuheenvuoro ensimmäisessä persoonassa suomeksi:";
				$user_message_text = "Haastattelijan kysymys: " . $user_question . "\n\n" . $context_prompt;
			}
		} else {
			// Mode B: Site Assistant (Answering public visitors in 3rd person)
			if ( 'sv' === $lang ) {
				$system_instruction = "Du är Matti Kivihargus officiella AI-assistent. Din uppgift är att svara på besökarnas frågor om Mattis expertis, arbetslivserfarenhet, utbildning, projekt och tjänster på svenska.\n\n"
					. "1. Inled svaret med: \"Jag är Matti Kivihargus AI-assistent.\" Tala ALLTID om Matti i tredje person.\n"
					. "2. {$style_guide_sv}\n"
					. "3. Grunda dina svar ENBART på den bifogade bakgrundsinformationen. Hitta ALDRIG på information.\n"
					. "4. Om informationen saknas, hänvisa besökaren till Matti: info@i4ware.fi tai +358 40 820 0691.\n"
					. "5. Undvik råa länkar och onaturliga tecken.";
				$context_prompt = "KUNSKAPSBAS:\n\n" . $context_text;
				$user_message_text = "Kunskapsbas:\n" . $context_prompt . "\n\nBesökarens fråga: " . $user_question;
			} elseif ( 'en' === $lang ) {
				$system_instruction = "You are Matti Kiviharju's official AI assistant. Your task is to answer visitors' questions about Matti's expertise, work experience, education, projects, and services in English.\n\n"
					. "1. Always start your response with: \"I am Matti Kiviharju's AI assistant.\" ALWAYS refer to Matti in the third person.\n"
					. "2. {$style_guide_en}\n"
					. "3. Base your answers ONLY on the provided background information. NEVER invent qualifications or roles.\n"
					. "4. If information is not in the knowledge base, direct the visitor to contact Matti directly: info@i4ware.fi or +358 40 820 0691.\n"
					. "5. Keep answers natural, friendly, and clean of raw markdown tables or raw URLs.";
				$context_prompt = "KNOWLEDGE BASE:\n\n" . $context_text;
				$user_message_text = "Knowledge base:\n" . $context_prompt . "\n\nVisitor's question: " . $user_question;
			} else {
				$system_instruction = "Olet Matti Kiviharjun virallinen tekoälyavustaja. Tehtäväsi on vastata kävijöiden kysymyksiin Matin asiantuntemuksesta, työkokemuksesta, koulutuksesta, projekteista ja palveluista suomeksi.\n\n"
					. "1. Aloita vastaus aina kertomalla: \"Olen Matti Kiviharjun AI-avustaja.\" Puhu Matista AINA kolmannessa persoonassa.\n"
					. "2. {$style_guide_fi}\n"
					. "3. Perusta vastauksesi AINOASTAAN annettuun taustatietoon. ÄLÄ KOSKAAN keksi tietoja.\n"
					. "4. Jos kysyttyä tietoa ei löydy, ohjaa kysyjä ottamaan yhteyttä Mattiin: info@i4ware.fi tai +358 40 820 0691.\n"
					. "5. Vältä monimutkaisia taulukoita tai raakoja URL-osoitteita tekstissä.";
				$context_prompt = "TIETOPOHJA:\n\n" . $context_text;
				$user_message_text = "Tietopohja:\n" . $context_prompt . "\n\nKävijän kysymys: " . $user_question;
			}
		}

		// Format contents with conversation history (last 4 messages)
		$contents = array();
		if ( ! empty( $history ) && is_array( $history ) ) {
			$recent_history = array_slice( $history, -4 );
			foreach ( $recent_history as $msg ) {
				$role = ( isset( $msg['role'] ) && 'user' === $msg['role'] ) ? 'user' : 'model';
				$text = sanitize_text_field( $msg['text'] ?? '' );
				if ( ! empty( $text ) ) {
					$contents[] = array(
						'role'  => $role,
						'parts' => array( array( 'text' => $text ) ),
					);
				}
			}
		}

		$contents[] = array(
			'role'  => 'user',
			'parts' => array( array( 'text' => $user_message_text ) ),
		);

		$payload = array(
			'systemInstruction' => array(
				'parts' => array( array( 'text' => $system_instruction ) ),
			),
			'contents'         => $contents,
			'generationConfig' => array(
				'temperature'     => $temperature,
				'maxOutputTokens' => 2500, // Ample tokens to accommodate Gemini reasoning thoughts without truncating output
			),
		);

		// Models to try in order of preference for resilience
		$model_candidates = array( $primary_model, 'gemini-3.5-flash-lite', 'gemini-3.1-flash-lite', 'gemini-3.5-flash', 'gemini-3.8-flash' );
		$model_candidates = array_values( array_filter( array_unique( $model_candidates ) ) );

		$last_error = '';
		foreach ( $model_candidates as $model_name ) {
			$endpoint = self::API_BASE . '/models/' . rawurlencode( $model_name ) . ':generateContent?key=' . urlencode( $api_key );

			$response = wp_remote_post( $endpoint, array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 28,
			) );

			if ( is_wp_error( $response ) ) {
				$last_error = 'Yhteysvirhe Google Geminiin (' . $model_name . '): ' . $response->get_error_message();
				continue;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$body   = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $status ) {
				$err_msg = ! empty( $body['error']['message'] ) ? $body['error']['message'] : ( 'HTTP ' . $status );
				$last_error = $err_msg;
				// If 503 high demand or 429 quota, try next model candidate
				if ( 503 === $status || 429 === $status || 404 === $status || false !== stripos( $err_msg, 'demand' ) ) {
					continue;
				}
				break;
			}

			// Extract and concatenate all text parts (skipping thought parts)
			$candidates = $body['candidates'] ?? array();
			if ( empty( $candidates ) ) {
				$last_error = 'Vastausta ei löytynyt API-palautteesta.';
				continue;
			}

			$parts = $candidates[0]['content']['parts'] ?? array();
			$answer_text = '';
			foreach ( $parts as $p ) {
				if ( ! empty( $p['text'] ) ) {
					$answer_text .= $p['text'];
				}
			}

			$answer_text = trim( $answer_text );
			if ( ! empty( $answer_text ) ) {
				return array(
					'success'    => true,
					'text'       => $answer_text,
					'model_used' => $model_name,
					'mode'       => $mode,
					'style'      => $style,
				);
			}
		}

		return array(
			'success' => false,
			'error'   => ! empty( $last_error ) ? $last_error : 'Avustaja ei pystynyt muodostamaan vastausta tähän kysymykseen.',
		);
	}

	/**
	 * Generate speech (TTS) using Gemini TTS models
	 *
	 * @param string $text
	 * @param string $preferred_voice
	 * @return array
	 */
	public static function generate_tts( $text, $preferred_voice = '' ) {
		$api_key = Gemini_Avatar_Settings::get_api_key();
		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'error'   => 'API-avain puuttuu.',
			);
		}

		$primary_tts_model = sanitize_text_field( Gemini_Avatar_Settings::get( 'tts_model', 'gemini-3.8-flash-tts' ) );
		$tts_voice = ! empty( $preferred_voice ) ? sanitize_text_field( $preferred_voice ) : sanitize_text_field( Gemini_Avatar_Settings::get( 'tts_voice', 'Puck' ) );

		// Clean up text for speech synthesis:
		// Remove URLs, markdown formatting, bullet symbols, asterisks
		$speech_text = preg_replace( '/https?:\/\/\S+/i', 'sivustolla', $text );
		$speech_text = preg_replace( '/\[([^\]]+)\]\([^)]+\)/', '$1', $speech_text );
		$speech_text = str_replace( array( '*', '#', '`', '~', '_', '•', '>', '—' ), ' ', $speech_text );
		$speech_text = trim( preg_replace( '/\s+/', ' ', $speech_text ) );

		// Optimal length for speech latency (max ~800 characters per TTS segment)
		if ( mb_strlen( $speech_text, 'UTF-8' ) > 800 ) {
			$speech_text = mb_substr( $speech_text, 0, 795, 'UTF-8' ) . '...';
		}

		$payload = array(
			'contents' => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array( 'text' => $speech_text ),
					),
				),
			),
			'generationConfig' => array(
				'responseModalities' => array( 'AUDIO' ),
				'speechConfig'       => array(
					'voiceConfig' => array(
						'prebuiltVoiceConfig' => array(
							'voiceName' => $tts_voice,
						),
					),
				),
			),
		);

		// Fallback TTS candidates
		$tts_candidates = array( $primary_tts_model, 'gemini-3.8-flash-tts', 'gemini-3.8-flash-lite-tts', 'gemini-2.5-flash-preview-tts' );
		$tts_candidates = array_unique( $tts_candidates );

		$last_error = '';
		foreach ( $tts_candidates as $tts_model ) {
			$endpoint = self::API_BASE . '/models/' . rawurlencode( $tts_model ) . ':generateContent?key=' . urlencode( $api_key );

			$response = wp_remote_post( $endpoint, array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			) );

			if ( is_wp_error( $response ) ) {
				$last_error = 'TTS-yhteysvirhe: ' . $response->get_error_message();
				continue;
			}

			$status = wp_remote_retrieve_response_code( $response );
			$body   = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $status ) {
				$last_error = ! empty( $body['error']['message'] ) ? $body['error']['message'] : ( 'TTS-virhe (HTTP ' . $status . ')' );
				continue;
			}

			$parts = $body['candidates'][0]['content']['parts'] ?? array();
			foreach ( $parts as $p ) {
				if ( ! empty( $p['inlineData']['data'] ) ) {
					$mime = ! empty( $p['inlineData']['mimeType'] ) ? $p['inlineData']['mimeType'] : 'audio/wav';
					return array(
						'success'      => true,
						'audio_base64' => $p['inlineData']['data'],
						'mime_type'    => $mime,
						'voice'        => $tts_voice,
						'model'        => $tts_model,
					);
				}
			}
		}

		return array(
			'success' => false,
			'error'   => ! empty( $last_error ) ? $last_error : 'Gemini TTS ei palauttanut audiodataa.',
		);
	}
}
