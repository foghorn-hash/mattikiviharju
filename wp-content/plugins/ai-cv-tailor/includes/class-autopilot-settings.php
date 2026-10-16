<?php

class AI_CV_Tailor_Autopilot_Settings {

	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		register_setting(
			'ai_cv_autopilot_settings_group',
			'ai_cv_autopilot_settings',
			array( $this, 'sanitize_settings' )
		);
	}

	public function sanitize_settings( $input ) {
		$sanitized = array();
		
		if ( isset( $input['min_score_cv_link'] ) ) {
			$sanitized['min_score_cv_link'] = absint( $input['min_score_cv_link'] );
		}
		if ( isset( $input['min_match_score'] ) ) {
			$sanitized['min_match_score'] = absint( $input['min_match_score'] );
		}
		if ( isset( $input['debug_mode'] ) ) {
			$sanitized['debug_mode'] = absint( $input['debug_mode'] );
		}
		if ( isset( $input['force_generate_test_mode'] ) ) {
			$sanitized['force_generate_test_mode'] = absint( $input['force_generate_test_mode'] );
		}
		if ( isset( $input['min_score_app_suggestion'] ) ) {
			$sanitized['min_score_app_suggestion'] = absint( $input['min_score_app_suggestion'] );
		}
		if ( isset( $input['allowed_tech'] ) ) {
			$sanitized['allowed_tech'] = sanitize_textarea_field( $input['allowed_tech'] );
		}
		if ( isset( $input['blocked_tech'] ) ) {
			$sanitized['blocked_tech'] = sanitize_textarea_field( $input['blocked_tech'] );
		}
		if ( isset( $input['min_hourly_rate'] ) ) {
			$sanitized['min_hourly_rate'] = absint( $input['min_hourly_rate'] );
		}
		if ( isset( $input['remote_only'] ) ) {
			$sanitized['remote_only'] = absint( $input['remote_only'] );
		}
		if ( isset( $input['b2b_only'] ) ) {
			$sanitized['b2b_only'] = absint( $input['b2b_only'] );
		}
		if ( isset( $input['locations'] ) ) {
			$sanitized['locations'] = sanitize_textarea_field( $input['locations'] );
		}
		if ( isset( $input['languages'] ) ) {
			$sanitized['languages'] = sanitize_text_field( $input['languages'] );
		}
		if ( isset( $input['blacklist_companies'] ) ) {
			$sanitized['blacklist_companies'] = sanitize_textarea_field( $input['blacklist_companies'] );
		}
		if ( isset( $input['preferred_keywords'] ) ) {
			$sanitized['preferred_keywords'] = sanitize_textarea_field( $input['preferred_keywords'] );
		}
		if ( isset( $input['rejected_keywords'] ) ) {
			$sanitized['rejected_keywords'] = sanitize_textarea_field( $input['rejected_keywords'] );
		}
		if ( isset( $input['google_ai_api_key'] ) ) {
			$sanitized['google_ai_api_key'] = sanitize_text_field( $input['google_ai_api_key'] );
		}
		if ( isset( $input['google_ai_model'] ) ) {
			$sanitized['google_ai_model'] = sanitize_text_field( $input['google_ai_model'] );
		}
		if ( isset( $input['google_search_grounding'] ) ) {
			$sanitized['google_search_grounding'] = absint( $input['google_search_grounding'] );
		}
		if ( isset( $input['google_cse_id'] ) ) {
			$sanitized['google_cse_id'] = sanitize_text_field( $input['google_cse_id'] );
		}
		if ( isset( $input['admin_sender_email'] ) ) {
			$sanitized['admin_sender_email'] = sanitize_email( $input['admin_sender_email'] );
		}
		if ( isset( $input['admin_sender_name'] ) ) {
			$sanitized['admin_sender_name'] = sanitize_text_field( $input['admin_sender_name'] );
		}
		if ( isset( $input['google_search_keywords'] ) ) {
			$sanitized['google_search_keywords'] = sanitize_textarea_field( $input['google_search_keywords'] );
		}
		
		return $sanitized;
	}
}
