<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_CV_Tailor_Autopilot_Sources {

	public function fetch_all_sources() {
		$new_count = 0;
		$sources = get_option( 'ai_cv_autopilot_sources_list', array() );

		if ( is_string( $sources ) ) {
			$decoded = json_decode( $sources, true );
			$sources = is_array( $decoded ) ? $decoded : array();
		}

		if ( empty( $sources ) || ! is_array( $sources ) ) {
			AI_CV_Tailor_Autopilot_Logger::info( 'No autopilot sources configured.' );
			return 0;
		}

		foreach ( $sources as $source ) {
			if ( isset( $source['enabled'] ) && ! $source['enabled'] ) {
				continue;
			}

			$type = strtolower( sanitize_text_field( $source['type'] ?? '' ) );
			$raw_url = $source['url'] ?? '';
			if ( in_array( $type, array( 'rss', 'url' ) ) || filter_var( $raw_url, FILTER_VALIDATE_URL ) ) {
				$url = esc_url_raw( $raw_url );
			} else {
				$url = sanitize_text_field( $raw_url );
			}
			$name = sanitize_text_field( $source['name'] ?? 'Unnamed Source' );

			if ( empty( $url ) ) {
				continue;
			}

			if ( 'rss' === $type ) {
				$new_count += $this->fetch_rss( $url, $name );
			} elseif ( 'url' === $type ) {
				$new_count += $this->fetch_url( $url, $name );
			} elseif ( 'tyomarkkinatori' === $type ) {
				$new_count += $this->fetch_tyomarkkinatori( $url, $name );
			} elseif ( 'finitec' === $type ) {
				$new_count += $this->fetch_finitec( $url, $name );
			}
		}

		return $new_count;
	}

	public function fetch_rss( $url, $source_name ) {
		AI_CV_Tailor_Autopilot_Logger::info( "Fetching RSS source: {$source_name} ({$url})" );

		include_once ABSPATH . WPINC . '/feed.php';
		$rss = fetch_feed( $url );

		if ( is_wp_error( $rss ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "RSS Error for {$url}: " . $rss->get_error_message() );
			return 0;
		}

		$items = $rss->get_items( 0, $rss->get_item_quantity( 10 ) );
		$added = 0;
		$skipped = 0;

		foreach ( $items as $item ) {
			$title = html_entity_decode( wp_strip_all_tags( $item->get_title() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$link = esc_url_raw( $item->get_permalink() );
			$description = html_entity_decode( wp_strip_all_tags( $item->get_description() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			if ( empty( $link ) || $this->source_url_exists( $link ) ) {
				$skipped++;
				continue;
			}

			$post_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => 'freelance_job',
				'post_status' => 'publish',
			) );

			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, 'source', $source_name );
				update_post_meta( $post_id, 'source_url', $link );
				update_post_meta( $post_id, 'role_title', $title );
				update_post_meta( $post_id, 'description', $description );
				update_post_meta( $post_id, 'status', 'New' );
				update_post_meta( $post_id, 'autopilot_processed', '0' );
				$added++;
			}
		}

		AI_CV_Tailor_Autopilot_Logger::info( "RSS {$source_name} finished. Added: {$added}, Skipped: {$skipped}" );
		return $added;
	}

	public function fetch_url( $url, $source_name ) {
		AI_CV_Tailor_Autopilot_Logger::info( "Fetching URL source: {$source_name} ({$url})" );

		if ( $this->source_url_exists( $url ) ) {
			AI_CV_Tailor_Autopilot_Logger::info( "Duplicate URL skipped: {$url}" );
			return 0;
		}

		$response = wp_remote_get( $url, array(
			'timeout' => 15,
			'redirection' => 5,
			'user-agent' => 'AI CV Tailor Autopilot/1.0; ' . home_url(),
		) );

		if ( is_wp_error( $response ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( $response->get_error_message() );
			return 0;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "HTTP error for {$url}" );
			return 0;
		}

		$parsed = $this->parse_html_job_data( wp_remote_retrieve_body( $response ), $source_name );

		$post_id = wp_insert_post( array(
			'post_title' => $parsed['title'],
			'post_type' => 'freelance_job',
			'post_status' => 'publish',
		) );

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'source', $source_name );
			update_post_meta( $post_id, 'source_url', esc_url_raw( $url ) );
			update_post_meta( $post_id, 'role_title', $parsed['title'] );
			update_post_meta( $post_id, 'description', $parsed['description'] );
			update_post_meta( $post_id, 'status', 'New' );
			update_post_meta( $post_id, 'autopilot_processed', '0' );
			return 1;
		}

		return 0;
	}

	private function parse_html_job_data( $body, $source_name ) {
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$body = mb_convert_encoding( $body, 'HTML-ENTITIES', 'UTF-8' );
		}

		$dom->loadHTML( $body );
		libxml_clear_errors();

		$title = '';
		$title_nodes = $dom->getElementsByTagName( 'title' );
		if ( $title_nodes->length > 0 ) {
			$title = $title_nodes->item( 0 )->nodeValue;
		}

		$h1 = '';
		$h1_nodes = $dom->getElementsByTagName( 'h1' );
		if ( $h1_nodes->length > 0 ) {
			$h1 = $h1_nodes->item( 0 )->nodeValue;
		}

		$meta_desc = '';
		foreach ( $dom->getElementsByTagName( 'meta' ) as $meta ) {
			$name = strtolower( $meta->getAttribute( 'name' ) );
			$property = strtolower( $meta->getAttribute( 'property' ) );
			if ( 'description' === $name || 'og:description' === $property ) {
				$meta_desc = $meta->getAttribute( 'content' );
				break;
			}
		}

		$final_title = trim( wp_strip_all_tags( $h1 ? $h1 : $title ) );
		$final_desc = trim( wp_strip_all_tags( $meta_desc ) );

		$final_title = html_entity_decode( $final_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$final_desc = html_entity_decode( $final_desc, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( empty( $final_title ) ) {
			$final_title = 'Parsed Job @ ' . $source_name;
		}

		return array(
			'title' => sanitize_text_field( $final_title ),
			'description' => sanitize_textarea_field( $final_desc ),
		);
	}

	private function source_url_exists( $url ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'source_url' AND meta_value = %s LIMIT 1",
			$url
		) );
	}

	public function save_manual_source( $data ) {
		$role_title = sanitize_text_field( $data['role_title'] ?? '' );
		$company_name = sanitize_text_field( $data['company_name'] ?? '' );

		$post_id = wp_insert_post( array(
			'post_title' => $role_title . ' @ ' . $company_name,
			'post_type' => 'freelance_job',
			'post_status' => 'publish',
		) );

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'source', 'Manual' );
			update_post_meta( $post_id, 'source_url', esc_url_raw( $data['source_url'] ?? '' ) );
			update_post_meta( $post_id, 'company_name', $company_name );
			update_post_meta( $post_id, 'role_title', $role_title );
			update_post_meta( $post_id, 'description', sanitize_textarea_field( $data['description'] ?? '' ) );
			update_post_meta( $post_id, 'status', 'New' );
			update_post_meta( $post_id, 'autopilot_processed', '0' );
			return $post_id;
		}

		return false;
	}

	public function fetch_tyomarkkinatori( $url_or_query, $source_name ) {
		AI_CV_Tailor_Autopilot_Logger::info( "Fetching Työmarkkinatori source: {$source_name} ({$url_or_query})" );

		$query = $url_or_query;
		if ( filter_var( $url_or_query, FILTER_VALIDATE_URL ) ) {
			$parsed_url = parse_url( $url_or_query );
			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $query_params );
				if ( isset( $query_params['q'] ) ) {
					$query = $query_params['q'];
				}
			}
		}

		$response = wp_remote_post( 'https://tyomarkkinatori.fi/api/jobpostingfulltext/search/v2/search', array(
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			),
			'body' => wp_json_encode( array(
				'query'   => $query,
				'filters' => (object) array(),
				'paging'  => array( 'pageNumber' => 0, 'pageSize' => 20 ),
				'sorting' => 'LATEST',
			) ),
			'timeout' => 20,
		) );

		if ( is_wp_error( $response ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "Työmarkkinatori Search API Error: " . $response->get_error_message() );
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data['content'] ) || ! is_array( $data['content'] ) ) {
			AI_CV_Tailor_Autopilot_Logger::info( "Työmarkkinatori search returned no items." );
			return 0;
		}

		$added = 0;
		$skipped = 0;

		foreach ( $data['content'] as $item ) {
			$id = $item['id'] ?? '';
			if ( empty( $id ) ) {
				continue;
			}

			$link = 'https://tyomarkkinatori.fi/henkiloasiakkaat/avoimet-tyopaikat/' . $id;

			if ( $this->source_url_exists( $link ) ) {
				$skipped++;
				continue;
			}

			// Fetch job details
			$details_response = wp_remote_get( 'https://tyomarkkinatori.fi/api/jobposting-new/v1/public/jobpostings/' . $id, array(
				'timeout' => 15,
			) );

			if ( is_wp_error( $details_response ) ) {
				AI_CV_Tailor_Autopilot_Logger::error( "Työmarkkinatori Detail API Error for {$id}: " . $details_response->get_error_message() );
				continue;
			}

			$details_body = wp_remote_retrieve_body( $details_response );
			$details = json_decode( $details_body, true );

			if ( empty( $details ) ) {
				continue;
			}

			// Extract title
			$title = $details['position']['title']['fi'] ?? ( is_array( $details['position']['title'] ?? null ) ? reset( $details['position']['title'] ) : 'Unnamed Job' );

			// Extract description
			$desc = $details['position']['jobDescription']['fi'] ?? ( is_array( $details['position']['jobDescription'] ?? null ) ? reset( $details['position']['jobDescription'] ) : '' );
			
			// Append help text (apply instructions) if available
			$help_text = $details['application']['helpText']['fi'] ?? ( is_array( $details['application']['helpText'] ?? null ) ? reset( $details['application']['helpText'] ) : '' );
			if ( ! empty( $help_text ) ) {
				$desc .= "\n\nHakuohjeet / Lisätiedot:\n" . $help_text;
			}

			// Extract company
			$company_name = $details['owner']['company']['fi'] ?? $details['owner']['officeName'] ?? '';

			// Extract contact details
			$agent_email = $details['recruiter']['contacts'][0]['email'] ?? '';
			$agent_name = '';
			if ( ! empty( $details['recruiter']['contacts'][0] ) ) {
				$first = $details['recruiter']['contacts'][0]['firstName'] ?? '';
				$last = $details['recruiter']['contacts'][0]['lastName'] ?? '';
				$agent_name = trim( $first . ' ' . $last );
			}

			$post_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => 'freelance_job',
				'post_status' => 'publish',
			) );

			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, 'source', $source_name );
				update_post_meta( $post_id, 'source_url', $link );
				update_post_meta( $post_id, 'role_title', $title );
				update_post_meta( $post_id, 'company_name', $company_name );
				update_post_meta( $post_id, 'description', $desc );
				update_post_meta( $post_id, 'status', 'New' );
				update_post_meta( $post_id, 'autopilot_processed', '0' );
				if ( ! empty( $agent_email ) ) {
					update_post_meta( $post_id, 'contact_email', $agent_email );
				}
				if ( ! empty( $agent_name ) ) {
					update_post_meta( $post_id, 'contact_person', $agent_name );
				}
				$added++;
			}
		}

		AI_CV_Tailor_Autopilot_Logger::info( "Työmarkkinatori finish. Added: {$added}, Skipped: {$skipped}" );
		return $added;
	}

	public function fetch_finitec( $url_or_query, $source_name ) {
		AI_CV_Tailor_Autopilot_Logger::info( "Fetching Finitec Oy source: {$source_name} ({$url_or_query})" );

		$query = '';
		if ( ! empty( $url_or_query ) && ! filter_var( $url_or_query, FILTER_VALIDATE_URL ) ) {
			$query = $url_or_query;
		}

		$script_path = AI_CV_TAILOR_DIR . 'includes/scrape_finitec.js';
		$json_path = AI_CV_TAILOR_DIR . 'includes/finitec_gigs.json';

		// Execute Playwright scraper
		$cmd = "node " . escapeshellarg( $script_path ) . " " . escapeshellarg( $json_path ) . " 2>&1";
		$output = shell_exec( $cmd );
		AI_CV_Tailor_Autopilot_Logger::info( "Finitec scraper execution output: " . $output );

		if ( ! file_exists( $json_path ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "Finitec scraper failed to output json file." );
			return 0;
		}

		$json_content = file_get_contents( $json_path );
		$gigs = json_decode( $json_content, true );

		if ( empty( $gigs ) || ! is_array( $gigs ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "Failed to decode Finitec gigs JSON or empty result." );
			return 0;
		}

		$added = 0;
		$skipped = 0;

		foreach ( $gigs as $gig ) {
			if ( empty( $gig['active'] ) ) {
				continue;
			}

			$title = $gig['title_fi'] ?? $gig['title_en'] ?? '';
			if ( empty( $title ) ) {
				$title = $gig['description_fi']['title'] ?? $gig['description_en']['title'] ?? 'Unnamed Gig';
			}

			$byline = $gig['description_fi']['byline'] ?? $gig['description_en']['byline'] ?? '';
			$body = $gig['description_fi']['public_body'] ?? $gig['description_en']['public_body'] ?? '';
			
			$full_desc_html = $byline . "\n\n" . $body;
			$desc = html_entity_decode( wp_strip_all_tags( $full_desc_html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

			// Apply keyword filter if query is set
			if ( ! empty( $query ) ) {
				$title_match = stripos( $title, $query ) !== false;
				$desc_match = stripos( $desc, $query ) !== false;
				if ( ! $title_match && ! $desc_match ) {
					continue;
				}
			}

			$gig_link = 'https://www.finitec.fi/gigs/' . $gig['id'];

			if ( $this->source_url_exists( $gig_link ) ) {
				$skipped++;
				continue;
			}

			$agent_name = $gig['agent']['full_name'] ?? '';
			$agent_email = $gig['agent']['email'] ?? '';

			$post_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => 'freelance_job',
				'post_status' => 'publish',
			) );

			if ( ! is_wp_error( $post_id ) ) {
				update_post_meta( $post_id, 'source', $source_name );
				update_post_meta( $post_id, 'source_url', $gig_link );
				update_post_meta( $post_id, 'role_title', $title );
				update_post_meta( $post_id, 'company_name', 'Finitec Oy' );
				update_post_meta( $post_id, 'description', $desc );
				update_post_meta( $post_id, 'status', 'New' );
				update_post_meta( $post_id, 'autopilot_processed', '0' );
				if ( ! empty( $agent_email ) ) {
					update_post_meta( $post_id, 'contact_email', $agent_email );
				}
				if ( ! empty( $agent_name ) ) {
					update_post_meta( $post_id, 'contact_person', $agent_name );
				}
				$added++;
			}
		}

		// Clean up temporary json file
		if ( file_exists( $json_path ) ) {
			@unlink( $json_path );
		}

		AI_CV_Tailor_Autopilot_Logger::info( "Finitec finish. Added: {$added}, Skipped: {$skipped}" );
		return $added;
	}
}