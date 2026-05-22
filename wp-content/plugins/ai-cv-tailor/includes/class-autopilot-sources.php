<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_CV_Tailor_Autopilot_Sources {

	public function fetch_all_sources() {
		$new_count = 0;
		$sources = get_option( 'ai_cv_tailor_autopilot_sources', array() );

		if ( is_string( $sources ) ) {
			$decoded = json_decode( $sources, true );
			$sources = is_array( $decoded ) ? $decoded : array();
		}

		if ( empty( $sources ) || ! is_array( $sources ) ) {
			AI_CV_Tailor_Autopilot_Logger::info( 'No autopilot sources configured.' );
			return 0;
		}

		foreach ( $sources as $source ) {
			if ( empty( $source['enabled'] ) ) {
				continue;
			}

			$type = strtolower( sanitize_text_field( $source['type'] ?? '' ) );
			$url  = esc_url_raw( $source['url'] ?? '' );
			$name = sanitize_text_field( $source['name'] ?? 'Unnamed Source' );

			if ( empty( $url ) ) {
				continue;
			}

			if ( 'rss' === $type ) {
				$new_count += $this->fetch_rss( $url, $name );
			} elseif ( 'url' === $type ) {
				$new_count += $this->fetch_url( $url, $name );
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
}