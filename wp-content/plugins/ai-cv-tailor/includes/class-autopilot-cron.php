<?php

class AI_CV_Tailor_Autopilot_Cron {

	public function init() {
		add_action( 'ai_cv_tailor_autopilot_daily_cron', array( $this, 'run_daily_tasks' ) );
		
		// CLI Hooks
		add_action( 'ai_cv_tailor_autopilot_fetch', array( $this, 'fetch_sources' ) );
		add_action( 'ai_cv_tailor_autopilot_analyze', array( $this, 'analyze_opportunities' ) );
		add_action( 'ai_cv_tailor_generate_applications', array( $this, 'generate_applications' ) );
		add_action( 'ai_cv_tailor_autopilot_digest', array( $this, 'generate_digest' ) );
		add_action( 'ai_cv_tailor_followups_due', array( $this, 'check_followups' ) );
		add_action( 'ai_cv_tailor_links_expire', array( $this, 'expire_links' ) );
		add_action( 'ai_cv_tailor_stats_cleanup', array( $this, 'cleanup_stats' ) );
		
		if ( ! wp_next_scheduled( 'ai_cv_tailor_autopilot_daily_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'ai_cv_tailor_autopilot_daily_cron' );
		}
	}

	public function run_daily_tasks() {
		$this->fetch_sources();
		$this->analyze_opportunities();
	}

	public function fetch_sources() {
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-sources.php';
		$sources = new AI_CV_Tailor_Autopilot_Sources();
		$new_posts = $sources->fetch_all_sources();
		if ( defined('WP_CLI') && WP_CLI ) {
			WP_CLI::log("Fetched $new_posts new opportunities.");
		}
	}

	public function analyze_opportunities() {
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-openai.php';
		$openai = new AI_CV_Tailor_Autopilot_OpenAI();

		if ( $openai->is_configured() ) {
			$args = array(
				'post_type'      => 'freelance_job',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'autopilot_processed',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key'     => 'autopilot_processed',
						'value'   => array( '1', 'rejected' ),
						'compare' => 'NOT IN'
					)
				)
			);
			
			$query = new WP_Query( $args );
			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post ) {
					// Check if already analyzed (has match_score) to avoid re-analyzing if generate step failed
					$existing_score = get_post_meta( $post->ID, 'match_score', true );
					if ( $existing_score === '' ) {
						AI_CV_Tailor_Autopilot_Logger::info( "Analyzing freelance job {$post->ID}" );
						$openai->analyze_opportunity( $post->ID );
						sleep(1);
					}
				}
			}
		}
	}

	public function generate_applications( $args = array() ) {
		$autopilot_settings = get_option( 'ai_cv_autopilot_settings', array() );
		$default_min = isset( $autopilot_settings['min_match_score'] ) ? intval( $autopilot_settings['min_match_score'] ) : 50;
		$min_score = isset($args['min-score']) ? intval($args['min-score']) : $default_min;
		
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-openai.php';
		$openai = new AI_CV_Tailor_Autopilot_OpenAI();
		
		$query_args = array(
			'post_type'      => 'freelance_job',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'match_score',
					'compare' => 'EXISTS'
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => 'autopilot_processed',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key'     => 'autopilot_processed',
						'value'   => array( '1', 'rejected' ),
						'compare' => 'NOT IN'
					)
				)
			)
		);
		$query = new WP_Query( $query_args );
		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post ) {
				$score = intval( get_post_meta( $post->ID, 'match_score', true ) );
				AI_CV_Tailor_Autopilot_Logger::info( "Match score for {$post->ID}: {$score} (Required: {$min_score})" );
				
				if ( $score >= $min_score ) {
					AI_CV_Tailor_Autopilot_Logger::info( "Creating ai_cv_application for job {$post->ID}" );
					$result = $openai->generate_application_from_opportunity( $post->ID );
					
					if ( ! is_wp_error( $result ) && isset( $result['app_id'] ) ) {
						update_post_meta( $post->ID, 'autopilot_processed', '1' );
						AI_CV_Tailor_Autopilot_Logger::success( "Application created: {$result['app_id']} for job {$post->ID}" );
					} else {
						AI_CV_Tailor_Autopilot_Logger::error( "Failed to create application for job {$post->ID}" );
					}
				} else {
					// Score is too low
					update_post_meta( $post->ID, 'autopilot_processed', 'rejected' );
					AI_CV_Tailor_Autopilot_Logger::info( "Job {$post->ID} rejected due to low score." );
				}
				sleep(1);
			}
		}
	}

	public function generate_digest() {
		// To be implemented: Send email digest of today's opportunities
	}

	public function check_followups() {
		// To be implemented: Check followups due today
	}

	public function expire_links() {
		// To be implemented: Mark AI CV links as expired
	}

	public function cleanup_stats() {
		// To be implemented: Cleanup old statistics
	}
	
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'ai_cv_tailor_autopilot_daily_cron' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'ai_cv_tailor_autopilot_daily_cron' );
		}
	}
}
