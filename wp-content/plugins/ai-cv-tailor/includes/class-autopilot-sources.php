<?php

class AI_CV_Tailor_Autopilot_Sources {

	public function fetch_all_sources() {
		// In a real scenario, this would loop through configured sources (Option)
		// For now, we provide a structured way to fetch.
		$new_count = 0;
		$sources = get_option( 'ai_cv_autopilot_sources_list', array() );
		
		foreach ( $sources as $source ) {
			if ( $source['type'] === 'rss' ) {
				$new_count += $this->fetch_rss( $source['url'], $source['name'] );
			} elseif ( $source['type'] === 'url' ) {
				$new_count += $this->fetch_url( $source['url'], $source['name'] );
			}
			// Future API integrations (Upwork, LinkedIn, etc.)
		}
		
		return $new_count;
	}

		AI_CV_Tailor_Autopilot_Logger::info( "Fetching RSS source: {$source_name} ({$url})" );
		
		include_once( ABSPATH . WPINC . '/feed.php' );
		$rss = fetch_feed( $url );
		
		if ( is_wp_error( $rss ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "RSS Error for {$url}: " . $rss->get_error_message() );
			return 0;
		}

		$maxitems = $rss->get_item_quantity( 10 ); 
		$rss_items = $rss->get_items( 0, $maxitems );

		$added = 0;
		$skipped = 0;
		foreach ( $rss_items as $item ) {
			$title = html_entity_decode( $item->get_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$link = $item->get_permalink();
			$description = $item->get_description();
			
			// Check if already exists by URL
			global $wpdb;
			$exists = $wpdb->get_var( $wpdb->prepare( "
				SELECT post_id FROM $wpdb->postmeta 
				WHERE meta_key = 'source_url' AND meta_value = %s
				LIMIT 1
			", $link ) );

			if ( ! $exists ) {
				$post_id = wp_insert_post( array(
					'post_title'  => wp_strip_all_tags( $title ),
					'post_type'   => 'freelance_job',
					'post_status' => 'publish',
				) );

				if ( ! is_wp_error( $post_id ) ) {
					update_post_meta( $post_id, 'source', $source_name );
					update_post_meta( $post_id, 'source_url', $link );
					update_post_meta( $post_id, 'role_title', $title );
					update_post_meta( $post_id, 'description', wp_strip_all_tags( $description ) );
					update_post_meta( $post_id, 'status', 'New' );
					$added++;
				} else {
					AI_CV_Tailor_Autopilot_Logger::error( "Failed to create post for RSS item: {$link}" );
				}
			} else {
				$skipped++;
			}
		}
		
		AI_CV_Tailor_Autopilot_Logger::info( "RSS {$source_name} finished. Added: {$added}, Skipped duplicates: {$skipped}" );
		return $added;
	}

	public function fetch_url( $url, $source_name ) {
		AI_CV_Tailor_Autopilot_Logger::info( "Fetching URL source: {$source_name} ({$url})" );

		// Check if already exists by URL
		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare( "
			SELECT post_id FROM $wpdb->postmeta 
			WHERE meta_key = 'source_url' AND meta_value = %s
			LIMIT 1
		", $url ) );

		if ( $exists ) {
			AI_CV_Tailor_Autopilot_Logger::info( "URL {$url} already exists as post {$exists}. Skipping." );
			return 0;
		}

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );
		if ( is_wp_error( $response ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "URL Fetch Error for {$url}: " . $response->get_error_message() );
			return 0;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			AI_CV_Tailor_Autopilot_Logger::error( "URL Fetch Error for {$url}: HTTP {$response_code}" );
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			AI_CV_Tailor_Autopilot_Logger::error( "URL Fetch Error for {$url}: Empty body" );
			return 0;
		}

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( mb_convert_encoding( $body, 'HTML-ENTITIES', 'UTF-8' ) );
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
		$meta_nodes = $dom->getElementsByTagName( 'meta' );
		foreach ( $meta_nodes as $meta ) {
			if ( strtolower( $meta->getAttribute( 'name' ) ) === 'description' ) {
				$meta_desc = $meta->getAttribute( 'content' );
				break;
			}
		}

		$final_title = ! empty( $h1 ) ? $h1 : $title;
		$final_title = trim( wp_strip_all_tags( $final_title ) );
		$final_title = html_entity_decode( $final_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		
		$final_desc  = trim( wp_strip_all_tags( $meta_desc ) );
		$final_desc  = html_entity_decode( $final_desc, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		
		if ( empty( $final_title ) ) {
			$final_title = 'Parsed Job @ ' . $source_name;
		}

		$post_id = wp_insert_post( array(
			'post_title'  => $final_title,
			'post_type'   => 'freelance_job',
			'post_status' => 'publish',
		) );

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'source', $source_name );
			update_post_meta( $post_id, 'source_url', $url );
			update_post_meta( $post_id, 'role_title', $final_title );
			update_post_meta( $post_id, 'description', $final_desc );
			update_post_meta( $post_id, 'status', 'New' );
			AI_CV_Tailor_Autopilot_Logger::info( "URL {$source_name} finished. Created post {$post_id}." );
			return 1;
		} else {
			AI_CV_Tailor_Autopilot_Logger::error( "Failed to create post for URL: {$url}" );
		}

		return 0;
	}
	
	public function save_manual_source( $data ) {
		$post_id = wp_insert_post( array(
			'post_title'  => sanitize_text_field( $data['role_title'] ) . ' @ ' . sanitize_text_field( $data['company_name'] ),
			'post_type'   => 'freelance_job',
			'post_status' => 'publish',
		) );

		if ( ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'source', 'Manual' );
			update_post_meta( $post_id, 'source_url', esc_url_raw( $data['source_url'] ?? '' ) );
			update_post_meta( $post_id, 'company_name', sanitize_text_field( $data['company_name'] ?? '' ) );
			update_post_meta( $post_id, 'role_title', sanitize_text_field( $data['role_title'] ?? '' ) );
			update_post_meta( $post_id, 'description', sanitize_textarea_field( $data['description'] ?? '' ) );
			update_post_meta( $post_id, 'budget', sanitize_text_field( $data['budget'] ?? '' ) );
			update_post_meta( $post_id, 'remote', sanitize_text_field( $data['remote'] ?? '' ) );
			update_post_meta( $post_id, 'b2b_possible', sanitize_text_field( $data['b2b_possible'] ?? '' ) );
			update_post_meta( $post_id, 'status', 'New' );
			return $post_id;
		}
		
		return false;
	}
}
