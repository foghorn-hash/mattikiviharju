<?php
/**
 * Knowledge Base Indexer and Search Engine
 *
 * Scans published, public WordPress content and provides relevant
 * excerpts to the Gemini assistant.
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Knowledge_Base {

	const INDEX_OPTION = 'gemini_avatar_knowledge_index';
	const INDEX_TIME_OPTION = 'gemini_avatar_knowledge_indexed_at';

	/**
	 * Build or rebuild the knowledge base index
	 *
	 * @return array
	 */
	public static function build_index() {
		$chunks = array();
		$settings = Gemini_Avatar_Settings::get_all();
		$allowed_types = ! empty( $settings['included_post_types'] ) ? (array) $settings['included_post_types'] : array();
		$home_url = home_url( '/' );

		// 1. General Profile & Contact Chunks for each language (fi, en, sv)
		$profile_chunks = self::build_profile_chunks( $home_url );
		foreach ( $profile_chunks as $pc ) {
			$chunks[] = $pc;
		}

		// 2. Extra Approved Knowledge from Settings
		$extra = trim( (string) $settings['approved_extra_knowledge'] );
		if ( ! empty( $extra ) ) {
			$chunks[] = array(
				'id'       => 'extra_approved',
				'lang'     => 'fi',
				'title'    => 'Ylläpitäjän hyväksymät lisätiedot ja yritystiedot',
				'category' => 'Yleistieto',
				'url'      => $home_url,
				'content'  => $extra,
				'tokens'   => self::tokenize( 'Ylläpitäjän hyväksymät lisätiedot i4ware Software yritystiedot palvelut ' . $extra ),
			);
		}

		// 3. Process each allowed post type (published only, all languages via Polylang)
		foreach ( $allowed_types as $post_type ) {
			$post_type = sanitize_key( $post_type );

			$query_args = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'lang'           => '', // Fetch all Polylang languages (fi, en, sv)
				'orderby'        => 'menu_order date',
				'order'          => 'DESC',
			);

			$posts = get_posts( $query_args );
			foreach ( $posts as $p ) {
				$chunk = self::process_post( $p, $home_url );
				if ( ! empty( $chunk ) ) {
					$chunks[] = $chunk;
				}
			}
		}

		// Save index
		update_option( self::INDEX_OPTION, $chunks, false );
		update_option( self::INDEX_TIME_OPTION, current_time( 'mysql' ), false );

		return $chunks;
	}

	/**
	 * Build multilingual profile and contact chunks (fi, en, sv)
	 *
	 * @param string $home_url
	 * @return array
	 */
	private static function build_profile_chunks( $home_url ) {
		$profile_chunks = array();
		$langs = array( 'fi', 'en', 'sv' );
		$front_id = (int) get_option( 'page_on_front' );

		foreach ( $langs as $lang ) {
			$page_id = $front_id;
			if ( 'fi' !== $lang && function_exists( 'pll_get_post' ) ) {
				$trans_id = pll_get_post( $front_id, $lang );
				if ( $trans_id ) {
					$page_id = $trans_id;
				}
			}

			$headline = function_exists( 'get_field' ) ? get_field( 'cv_hero_headline', $page_id ) : '';
			$intro    = function_exists( 'get_field' ) ? get_field( 'cv_hero_intro', $page_id ) : '';
			$title    = function_exists( 'get_field' ) ? get_field( 'cv_profile_title', $page_id ) : '';
			$body     = function_exists( 'get_field' ) ? get_field( 'cv_profile_body', $page_id ) : '';

			$email       = function_exists( 'get_field' ) ? get_field( 'cv_contact_email', $page_id ) : 'info@i4ware.fi';
			$phone       = function_exists( 'get_field' ) ? get_field( 'cv_contact_phone', $page_id ) : '+358 40 820 0691';
			$business_id = function_exists( 'get_field' ) ? get_field( 'cv_contact_business_id', $page_id ) : '2739594-6';
			$vat_id      = function_exists( 'get_field' ) ? get_field( 'cv_contact_vat_id', $page_id ) : 'FI27395946';
			$linkedin    = function_exists( 'get_field' ) ? get_field( 'cv_contact_linkedin', $page_id ) : '';

			$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
			$tech_stack = ! empty( $autopilot_settings['allowed_tech'] ) ? $autopilot_settings['allowed_tech'] : '';

			$lang_home_url = function_exists( 'pll_home_url' ) ? pll_home_url( $lang ) : $home_url;

			if ( 'sv' === $lang ) {
				$chunk_title = 'Matti Kiviharju - Profilöversikt och kontaktuppgifter';
				$category    = 'Allmän information';
				$content  = "Person: Matti Kiviharju\n";
				$content .= "Roll / Rubrik: " . ( $headline ?: 'Programvaruarkitekt, Full Stack -utvecklare, Entreprenör' ) . "\n";
				if ( ! empty( $intro ) ) {
					$content .= "Introduktion: " . wp_strip_all_tags( $intro ) . "\n";
				}
				if ( ! empty( $body ) ) {
					$content .= "Profilbeskrivning: " . wp_strip_all_tags( $body ) . "\n";
				}
				$content .= "Företag: i4ware Software\n";
				$content .= "Kontakt: E-post: {$email}, Telefon: {$phone}, FO-nummer: {$business_id}, Moms: {$vat_id}\n";
			} elseif ( 'en' === $lang ) {
				$chunk_title = 'Matti Kiviharju - Profile Overview and Contact Information';
				$category    = 'General Info';
				$content  = "Person: Matti Kiviharju\n";
				$content .= "Role / Title: " . ( $headline ?: 'Software Architect, Full Stack Developer, Entrepreneur' ) . "\n";
				if ( ! empty( $intro ) ) {
					$content .= "Introduction: " . wp_strip_all_tags( $intro ) . "\n";
				}
				if ( ! empty( $body ) ) {
					$content .= "Profile: " . wp_strip_all_tags( $body ) . "\n";
				}
				$content .= "Company: i4ware Software\n";
				$content .= "Contact: Email: {$email}, Phone: {$phone}, Business ID: {$business_id}, VAT: {$vat_id}\n";
			} else {
				$chunk_title = 'Matti Kiviharju - Yleiskuvaus ja yhteystiedot';
				$category    = 'Yleistieto';
				$content  = "Henkilö: Matti Kiviharju\n";
				$content .= "Rooli / Otsikko: " . ( $headline ?: 'Ohjelmistoarkkitehti, Full Stack -kehittäjä, Yrittäjä' ) . "\n";
				if ( ! empty( $intro ) ) {
					$content .= "Esittely: " . wp_strip_all_tags( $intro ) . "\n";
				}
				if ( ! empty( $body ) ) {
					$content .= "Profiilikuvaus: " . wp_strip_all_tags( $body ) . "\n";
				}
				$content .= "Yritys: i4ware Software\n";
				$content .= "Yhteystiedot: Sähköposti: {$email}, Puhelin: {$phone}, Y-tunnus: {$business_id}, ALV: {$vat_id}\n";
			}

			if ( ! empty( $linkedin ) ) {
				$content .= "LinkedIn: {$linkedin}\n";
			}
			if ( ! empty( $tech_stack ) ) {
				$content .= "Teknologiat / Tech Stack: {$tech_stack}\n";
			}

			$profile_chunks[] = array(
				'id'       => 'profile_overview_' . $lang,
				'lang'     => $lang,
				'title'    => $chunk_title,
				'category' => $category,
				'url'      => $lang_home_url . '#about',
				'content'  => $content,
				'tokens'   => self::tokenize( $chunk_title . ' ' . $content ),
			);
		}

		return $profile_chunks;
	}

	/**
	 * Process a single WP_Post into a knowledge chunk
	 *
	 * @param WP_Post $post
	 * @param string $home_url
	 * @return array|null
	 */
	private static function process_post( $post, $home_url ) {
		$post_id   = $post->ID;
		$post_type = $post->post_type;
		$title     = trim( $post->post_title );

		// Detect Polylang language
		$post_lang = 'fi';
		if ( function_exists( 'pll_get_post_language' ) ) {
			$detected_lang = pll_get_post_language( $post_id );
			if ( ! empty( $detected_lang ) ) {
				$post_lang = $detected_lang;
			}
		}

		if ( empty( $title ) && empty( $post->post_content ) ) {
			return null;
		}

		$category = 'Artikkeli';
		$url      = get_permalink( $post_id );
		$details  = array();

		switch ( $post_type ) {
			case 'cv_experience':
				$category = ( 'sv' === $post_lang ) ? 'Arbetslivserfarenhet' : ( ( 'en' === $post_lang ) ? 'Work Experience' : 'Työkokemus' );
				$url      = $home_url . '#experience';
				$company  = get_post_meta( $post_id, '_cv_experience_company', true ) ?: get_post_meta( $post_id, 'cv_experience_company', true );
				$dates    = get_post_meta( $post_id, '_cv_experience_dates', true ) ?: get_post_meta( $post_id, 'cv_experience_dates', true );

				if ( ! empty( $company ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Företag / Organisation: {$company}" : ( ( 'en' === $post_lang ) ? "Company / Organization: {$company}" : "Yritys / Organisaatio: {$company}" );
				}
				if ( ! empty( $dates ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Tidpunkt: {$dates}" : ( ( 'en' === $post_lang ) ? "Dates: {$dates}" : "Ajankohta: {$dates}" );
				}
				$desc = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
				if ( ! empty( $desc ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Beskrivning: " . wp_strip_all_tags( $desc ) : ( ( 'en' === $post_lang ) ? "Description: " . wp_strip_all_tags( $desc ) : "Kuvaus: " . wp_strip_all_tags( $desc ) );
				}
				break;

			case 'cv_project':
				$category = ( 'sv' === $post_lang ) ? 'Projekt' : ( ( 'en' === $post_lang ) ? 'Project' : 'Projekti' );
				$url      = $home_url . '#projects';
				$meta     = get_post_meta( $post_id, '_cv_project_meta', true ) ?: get_post_meta( $post_id, 'cv_project_meta', true );
				$link     = get_post_meta( $post_id, '_cv_project_link', true ) ?: get_post_meta( $post_id, 'cv_project_link', true );
				$all_links = get_post_meta( $post_id, '_cv_project_links', true ) ?: get_post_meta( $post_id, 'cv_project_links', true );

				if ( ! empty( $meta ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Teknologier / Detaljer: {$meta}" : ( ( 'en' === $post_lang ) ? "Technologies / Details: {$meta}" : "Teknologiat / Tiedot: {$meta}" );
				}
				if ( ! empty( $all_links ) && is_array( $all_links ) ) {
					foreach ( $all_links as $l_item ) {
						$l_u = isset( $l_item['url'] ) ? $l_item['url'] : '';
						$l_t = isset( $l_item['label'] ) ? $l_item['label'] : ( isset( $l_item['title'] ) ? $l_item['title'] : '' );
						if ( ! empty( $l_u ) ) {
							$link_str = $l_t ? "{$l_t} ({$l_u})" : $l_u;
							$details[] = ( 'sv' === $post_lang ) ? "Projektlänk: {$link_str}" : ( ( 'en' === $post_lang ) ? "Project Link: {$link_str}" : "Projektin linkki: {$link_str}" );
						}
					}
				} elseif ( ! empty( $link ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Projektlänk: {$link}" : ( ( 'en' === $post_lang ) ? "Project Link: {$link}" : "Projektin linkki: {$link}" );
				}
				$desc = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
				if ( ! empty( $desc ) ) {
					$desc = str_replace( array( "\xc2\xa0", "\u{00a0}", "\u{200b}", "\u{202f}", "\u{feff}" ), ' ', $desc );
					$details[] = ( 'sv' === $post_lang ) ? "Beskrivning: " . wp_strip_all_tags( $desc ) : ( ( 'en' === $post_lang ) ? "Description: " . wp_strip_all_tags( $desc ) : "Kuvaus: " . wp_strip_all_tags( $desc ) );
				}
				break;

			case 'cv_education':
				$category = ( 'sv' === $post_lang ) ? 'Utbildning' : ( ( 'en' === $post_lang ) ? 'Education' : 'Koulutus' );
				$url      = $home_url . '#education';
				$dates    = get_post_meta( $post_id, '_cv_education_dates', true ) ?: get_post_meta( $post_id, 'cv_education_dates', true );

				if ( ! empty( $dates ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Tidpunkt: {$dates}" : ( ( 'en' === $post_lang ) ? "Dates: {$dates}" : "Ajankohta: {$dates}" );
				}
				$desc = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
				if ( ! empty( $desc ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Uppgifter: " . wp_strip_all_tags( $desc ) : ( ( 'en' === $post_lang ) ? "Details: " . wp_strip_all_tags( $desc ) : "Tiedot: " . wp_strip_all_tags( $desc ) );
				}
				break;

			case 'cv_course':
				$category = ( 'sv' === $post_lang ) ? 'Utbildning och kurser' : ( ( 'en' === $post_lang ) ? 'Courses and Training' : 'Koulutus ja kurssit' );
				$url      = $home_url . '#courses';
				$provider = get_post_meta( $post_id, '_cv_course_provider', true ) ?: get_post_meta( $post_id, 'cv_course_provider', true );
				$dates    = get_post_meta( $post_id, '_cv_course_dates', true ) ?: get_post_meta( $post_id, 'cv_course_dates', true );

				if ( ! empty( $provider ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Arrangör: {$provider}" : ( ( 'en' === $post_lang ) ? "Provider: {$provider}" : "Järjestäjä: {$provider}" );
				}
				if ( ! empty( $dates ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Tidpunkt: {$dates}" : ( ( 'en' === $post_lang ) ? "Dates: {$dates}" : "Ajankohta: {$dates}" );
				}
				if ( ! empty( $post->post_content ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Beskrivning: " . wp_strip_all_tags( $post->post_content ) : ( ( 'en' === $post_lang ) ? "Description: " . wp_strip_all_tags( $post->post_content ) : "Kuvaus: " . wp_strip_all_tags( $post->post_content ) );
				}
				break;

			case 'cv_skill':
				$category  = ( 'sv' === $post_lang ) ? 'Kompetens' : ( ( 'en' === $post_lang ) ? 'Skill' : 'Osaaminen' );
				$url       = $home_url . '#skills';
				$details[] = ( 'sv' === $post_lang ) ? "Kompetensområde: " . wp_strip_all_tags( $title ) : ( ( 'en' === $post_lang ) ? "Skill Area: " . wp_strip_all_tags( $title ) : "Osaamisalue: " . wp_strip_all_tags( $title ) );
				if ( ! empty( $post->post_content ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Närmare beskrivning: " . wp_strip_all_tags( $post->post_content ) : ( ( 'en' === $post_lang ) ? "Detailed Description: " . wp_strip_all_tags( $post->post_content ) : "Tarkempi kuvaus: " . wp_strip_all_tags( $post->post_content ) );
				}
				break;

			case 'cv_badge':
				$category  = ( 'sv' === $post_lang ) ? 'Certifikat / Färdighet' : ( ( 'en' === $post_lang ) ? 'Badge / Certification' : 'Sertifikaatti / Taito' );
				$url       = $home_url . '#about';
				$details[] = ( 'sv' === $post_lang ) ? "Färdighet / Specialkompetens: " . wp_strip_all_tags( $title ) : ( ( 'en' === $post_lang ) ? "Skill / Certification: " . wp_strip_all_tags( $title ) : "Taito / Erityisosaaminen: " . wp_strip_all_tags( $title ) );
				break;

			case 'cv_reference':
				$category  = ( 'sv' === $post_lang ) ? 'Referens' : ( ( 'en' === $post_lang ) ? 'Reference' : 'Suosittelija' );
				$url       = $home_url . '#references';
				$r_title   = get_post_meta( $post_id, '_cv_reference_title', true ) ?: get_post_meta( $post_id, 'cv_reference_title', true );
				$r_company = get_post_meta( $post_id, '_cv_reference_company', true ) ?: get_post_meta( $post_id, 'cv_reference_company', true );

				if ( ! empty( $r_title ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Position: {$r_title}" : ( ( 'en' === $post_lang ) ? "Title: {$r_title}" : "Asema: {$r_title}" );
				}
				if ( ! empty( $r_company ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Företag: {$r_company}" : ( ( 'en' === $post_lang ) ? "Company: {$r_company}" : "Yritys: {$r_company}" );
				}
				if ( ! empty( $post->post_content ) ) {
					$details[] = ( 'sv' === $post_lang ) ? "Rekommendation: " . wp_strip_all_tags( $post->post_content ) : ( ( 'en' === $post_lang ) ? "Recommendation: " . wp_strip_all_tags( $post->post_content ) : "Suositus: " . wp_strip_all_tags( $post->post_content ) );
				}
				break;

			case 'page':
				$category = ( 'sv' === $post_lang ) ? 'Sida' : ( ( 'en' === $post_lang ) ? 'Page' : 'Sivu' );
				if ( $post_id === (int) get_option( 'page_on_front' ) ) {
					$url = $home_url;
				}
				$content = wp_strip_all_tags( $post->post_content );
				if ( ! empty( $content ) ) {
					$details[] = wp_trim_words( $content, 120, '...' );
				}
				break;

			default:
				$category = ( 'sv' === $post_lang ) ? 'Blogg' : ( ( 'en' === $post_lang ) ? 'Blog' : 'Blogi' );
				$content = ! empty( $post->post_excerpt ) ? $post->post_excerpt : wp_strip_all_tags( $post->post_content );
				if ( ! empty( $content ) ) {
					$details[] = wp_trim_words( $content, 100, '...' );
				}
				break;
		}

		$content_str = implode( "\n", $details );
		if ( empty( $content_str ) ) {
			$content_str = $title;
		}

		return array(
			'id'       => 'post_' . $post_id,
			'lang'     => $post_lang,
			'title'    => $title,
			'category' => $category,
			'url'      => $url,
			'content'  => $content_str,
			'tokens'   => self::tokenize( $title . ' ' . $category . ' ' . $content_str ),
		);
	}

	/**
	 * Tokenize text into lowercase keywords
	 *
	 * @param string $text
	 * @return array
	 */
	public static function tokenize( $text ) {
		$text = mb_strtolower( (string) $text, 'UTF-8' );
		// Replace non-alphanumeric (keep Scandinavian letters)
		$text = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $text );
		$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

		// Common Finnish, English & Swedish stop words to filter
		$stopwords = array(
			'ja', 'on', 'ei', 'se', 'tai', 'eli', 'että', 'joka', 'jotka', 'niin', 'kun',
			'kuin', 'mutta', 'vaan', 'myös', 'sekä', 'kanssa', 'ovat', 'ollut', 'oli',
			'olen', 'olet', 'me', 'te', 'he', 'hän', 'tämä', 'tuo', 'nuo', 'nämä',
			'mitä', 'mikä', 'kuka', 'missä', 'miksi', 'miten', 'milloin', 'paljonko',
			'onko', 'voiko', 'saako', 'kerro', 'the', 'and', 'is', 'in', 'at', 'of',
			'to', 'for', 'with', 'on', 'that', 'this', 'are', 'was', 'by', 'an', 'be',
			'och', 'att', 'det', 'den', 'av', 'för', 'med', 'till', 'har', 'hade', 'om',
			'men', 'ett', 'en', 'inte', 'hur', 'vad', 'vem', 'när', 'varför', 'vilken',
			'vilka', 'berätta', 'som', 'kan', 'ska', 'skulle', 'vara', 'varit', 'eller',
			'också', 'alla', 'dessa', 'detta', 'hans', 'hennes', 'deras', 'vår', 'ert'
		);
		$stop_map = array_flip( $stopwords );

		$tokens = array();
		foreach ( $words as $w ) {
			if ( mb_strlen( $w, 'UTF-8' ) >= 3 && ! isset( $stop_map[ $w ] ) ) {
				$tokens[ $w ] = true;
			}
		}

		return array_keys( $tokens );
	}

	/**
	 * Search indexed chunks by query relevance and requested language
	 *
	 * @param string $query
	 * @param int $limit
	 * @param string $lang
	 * @return array
	 */
	public static function search( $query, $limit = 6, $lang = 'fi' ) {
		$index = get_option( self::INDEX_OPTION, null );
		if ( ! is_array( $index ) || empty( $index ) ) {
			$index = self::build_index();
		}

		$query_tokens = self::tokenize( $query );
		$lower_query  = mb_strtolower( trim( $query ), 'UTF-8' );

		$scored = array();
		foreach ( $index as $idx => $chunk ) {
			$score = 0;
			$chunk_lang    = ! empty( $chunk['lang'] ) ? $chunk['lang'] : 'fi';
			$lower_title   = mb_strtolower( $chunk['title'], 'UTF-8' );
			$lower_cat     = mb_strtolower( $chunk['category'], 'UTF-8' );
			$lower_content = mb_strtolower( $chunk['content'], 'UTF-8' );

			// Exact full phrase in title or content
			if ( ! empty( $lower_query ) && mb_strpos( $lower_title, $lower_query ) !== false ) {
				$score += 15;
			}
			if ( ! empty( $lower_query ) && mb_strpos( $lower_content, $lower_query ) !== false ) {
				$score += 8;
			}

			// Token matches
			$chunk_tokens = array_flip( $chunk['tokens'] );
			foreach ( $query_tokens as $qt ) {
				// Exact token in chunk tokens
				if ( isset( $chunk_tokens[ $qt ] ) ) {
					$score += 4;
				}
				// Substring match in title
				if ( mb_strpos( $lower_title, $qt ) !== false ) {
					$score += 6;
				}
				// Substring match in category
				if ( mb_strpos( $lower_cat, $qt ) !== false ) {
					$score += 4;
				}
				// Substring match in content
				if ( mb_strpos( $lower_content, $qt ) !== false ) {
					$score += 2;
				}
			}

			// High-priority topic checks (Multilingual: FI, EN, SV)
			if ( preg_match( '/\b(atlassian|jira|confluence|forge)\b/i', $lower_query ) && preg_match( '/\b(atlassian|jira|confluence|forge)\b/i', $chunk['title'] . ' ' . $chunk['content'] ) ) {
				$score += 10;
			}
			if ( preg_match( '/\b(erp|integraatio|integration|affärssystem|sap|visma|procountor|lemonsoft)\b/i', $lower_query ) && preg_match( '/\b(erp|integraatio|integration|affärssystem)\b/i', $chunk['title'] . ' ' . $chunk['content'] ) ) {
				$score += 10;
			}
			if ( preg_match( '/\b(koulutus|opiskelu|tutkinto|degree|education|utbildning|studier|examen)\b/i', $lower_query ) && in_array( $chunk['category'], array( 'Koulutus', 'Education', 'Utbildning', 'Utbildning och kurser', 'Courses and Training', 'Koulutus ja kurssit' ), true ) ) {
				$score += 12;
			}
			if ( preg_match( '/\b(kokemus|työ|työpaikka|experience|career|erfarenhet|arbete|jobb|karriär)\b/i', $lower_query ) && in_array( $chunk['category'], array( 'Työkokemus', 'Work Experience', 'Arbetslivserfarenhet' ), true ) ) {
				$score += 12;
			}
			if ( preg_match( '/\b(yhteys|yhteystiedot|sähköposti|puhelin|contact|email|phone|kontakt|kontaktuppgifter|epost|telefon)\b/i', $lower_query ) && 'profile_overview_' . $lang === $chunk['id'] ) {
				$score += 25;
			}

			// Significant bonus for chunks matching the requested language
			if ( $score > 0 ) {
				if ( $chunk_lang === $lang ) {
					$score += 25;
				}
				$scored[] = array(
					'score' => $score,
					'chunk' => $chunk,
				);
			}
		}

		// Sort by score descending
		usort( $scored, function( $a, $b ) {
			return $b['score'] <=> $a['score'];
		} );

		$results = array();
		foreach ( array_slice( $scored, 0, $limit ) as $item ) {
			$results[] = $item['chunk'];
		}

		// If no matches (e.g. empty or generic greeting), include profile overview for requested language
		if ( empty( $results ) ) {
			foreach ( $index as $chunk ) {
				if ( 'profile_overview_' . $lang === $chunk['id'] ) {
					$results[] = $chunk;
					break;
				}
			}
			if ( empty( $results ) ) {
				foreach ( $index as $chunk ) {
					if ( ! empty( $chunk['lang'] ) && $chunk['lang'] === $lang ) {
						$results[] = $chunk;
						if ( count( $results ) >= 3 ) {
							break;
						}
					}
				}
			}
		}

		return $results;
	}

	/**
	 * Get formatted context string for the Gemini prompt and array of sources
	 *
	 * @param string $query
	 * @param int $limit
	 * @param string $lang
	 * @return array
	 */
	public static function get_prompt_context( $query, $limit = 6, $lang = 'fi' ) {
		$chunks = self::search( $query, $limit, $lang );

		$context_lines = array();
		$sources       = array();
		$seen_urls     = array();

		$source_label = 'LÄHDE';
		if ( 'sv' === $lang ) {
			$source_label = 'KÄLLA';
		} elseif ( 'en' === $lang ) {
			$source_label = 'SOURCE';
		}

		foreach ( $chunks as $i => $chunk ) {
			$num = $i + 1;
			$context_lines[] = "--- [{$source_label} {$num}: {$chunk['title']} ({$chunk['category']})] ---";
			$context_lines[] = $chunk['content'];
			$context_lines[] = "URL: " . $chunk['url'];
			$context_lines[] = "";

			if ( ! empty( $chunk['url'] ) && ! isset( $seen_urls[ $chunk['url'] ] ) ) {
				$seen_urls[ $chunk['url'] ] = true;
				$sources[] = array(
					'title'    => $chunk['title'],
					'category' => $chunk['category'],
					'url'      => $chunk['url'],
				);
			}
		}

		return array(
			'context_text' => implode( "\n", $context_lines ),
			'sources'      => $sources,
		);
	}

	/**
	 * Get statistics about the index
	 *
	 * @return array
	 */
	public static function get_stats() {
		$index = get_option( self::INDEX_OPTION, array() );
		$last_updated = get_option( self::INDEX_TIME_OPTION, '' );

		$categories = array();
		if ( is_array( $index ) ) {
			foreach ( $index as $chunk ) {
				$cat = ! empty( $chunk['category'] ) ? $chunk['category'] : 'Muu';
				$categories[ $cat ] = ( $categories[ $cat ] ?? 0 ) + 1;
			}
		}

		return array(
			'total_chunks'  => is_array( $index ) ? count( $index ) : 0,
			'last_updated'  => $last_updated,
			'by_category'   => $categories,
		);
	}

	/**
	 * Clear index
	 */
	public static function clear_index() {
		delete_option( self::INDEX_OPTION );
		delete_option( self::INDEX_TIME_OPTION );
	}
}
