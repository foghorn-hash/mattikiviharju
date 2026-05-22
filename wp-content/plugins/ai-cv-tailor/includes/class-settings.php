<?php

class AI_CV_Tailor_Settings {

	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function register_settings() {
		// Register a single option array for all settings
		register_setting(
			'ai_cv_tailor_settings_group',
			'ai_cv_tailor_settings',
			array( $this, 'sanitize_settings' )
		);
		
		// Register CV data option as well
		register_setting(
			'ai_cv_tailor_data_group',
			'ai_cv_profile_data',
			array( $this, 'sanitize_cv_data' )
		);
	}

	public function sanitize_settings( $input ) {
		$sanitized = array();
		
		if ( isset( $input['openai_api_key'] ) ) {
			$sanitized['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] );
		}
		if ( isset( $input['model'] ) ) {
			$sanitized['model'] = sanitize_text_field( $input['model'] );
		}
		if ( isset( $input['default_language'] ) ) {
			$sanitized['default_language'] = sanitize_text_field( $input['default_language'] );
		}
		if ( isset( $input['link_expiration'] ) ) {
			$sanitized['link_expiration'] = absint( $input['link_expiration'] );
		}
		if ( isset( $input['company_name'] ) ) {
			$sanitized['company_name'] = sanitize_text_field( $input['company_name'] );
		}
		if ( isset( $input['my_name'] ) ) {
			$sanitized['my_name'] = sanitize_text_field( $input['my_name'] );
		}
		if ( isset( $input['my_email'] ) ) {
			$sanitized['my_email'] = sanitize_email( $input['my_email'] );
		}
		if ( isset( $input['my_phone'] ) ) {
			$sanitized['my_phone'] = sanitize_text_field( $input['my_phone'] );
		}
		if ( isset( $input['business_id'] ) ) {
			$sanitized['business_id'] = sanitize_text_field( $input['business_id'] );
		}
		if ( isset( $input['vat_id'] ) ) {
			$sanitized['vat_id'] = sanitize_text_field( $input['vat_id'] );
		}
		if ( isset( $input['website_url'] ) ) {
			$sanitized['website_url'] = esc_url_raw( $input['website_url'] );
		}
		if ( isset( $input['delivery_terms_url'] ) ) {
			$sanitized['delivery_terms_url'] = esc_url_raw( $input['delivery_terms_url'] );
		}
		if ( isset( $input['privacy_policy_url'] ) ) {
			$sanitized['privacy_policy_url'] = esc_url_raw( $input['privacy_policy_url'] );
		}
		if ( isset( $input['terms_url'] ) ) {
			$sanitized['terms_url'] = esc_url_raw( $input['terms_url'] );
		}
		if ( isset( $input['dpa_url'] ) ) {
			$sanitized['dpa_url'] = esc_url_raw( $input['dpa_url'] );
		}
		if ( isset( $input['sla_url'] ) ) {
			$sanitized['sla_url'] = esc_url_raw( $input['sla_url'] );
		}
		
		return $sanitized;
	}
	
	public function sanitize_cv_data( $input ) {
		// Just sanitize it as a JSON string or array, then serialize
		// The input will be coming from a textarea as JSON
		$decoded = json_decode( $input, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			// It's valid JSON, store it as array (WordPress will serialize it)
			return $decoded;
		}
		
		// If it's invalid, return the previous value and add error
		add_settings_error(
			'ai_cv_tailor_data_group',
			'invalid_json',
			'Tallennus epäonnistui: Virheellinen JSON-muotoilu.'
		);
		return get_option( 'ai_cv_profile_data' );
	}
}
