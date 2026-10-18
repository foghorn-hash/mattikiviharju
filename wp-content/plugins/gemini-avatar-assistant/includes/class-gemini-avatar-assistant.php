<?php
/**
 * Main Plugin Orchestrator Class
 *
 * @package Gemini_Avatar_Assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gemini_Avatar_Assistant {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		// REST API endpoints
		add_action( 'rest_api_init', array( 'Gemini_Avatar_API_Endpoint', 'register_routes' ) );

		// Admin interface
		if ( is_admin() ) {
			Gemini_Avatar_Admin::get_instance();
		}

		// Public widget
		Gemini_Avatar_Public::get_instance();

		// Auto-refresh knowledge index on post save if public
		add_action( 'save_post', array( $this, 'on_save_post' ), 10, 2 );
	}

	/**
	 * Invalidate/refresh index when a published post is updated
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public function on_save_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! is_object( $post ) || 'publish' !== $post->post_status ) {
			return;
		}

		$allowed_types = Gemini_Avatar_Settings::get( 'included_post_types', array() );
		if ( in_array( $post->post_type, (array) $allowed_types, true ) ) {
			Gemini_Avatar_Knowledge_Base::clear_index();
		}
	}

	/**
	 * Plugin activation
	 */
	public static function activate() {
		$defaults = Gemini_Avatar_Settings::get_defaults();
		$existing = get_option( Gemini_Avatar_Settings::OPTION_KEY, false );
		if ( false === $existing ) {
			update_option( Gemini_Avatar_Settings::OPTION_KEY, $defaults );
		}

		// Build initial knowledge index
		Gemini_Avatar_Knowledge_Base::build_index();
	}

	/**
	 * Plugin deactivation
	 */
	public static function deactivate() {
		Gemini_Avatar_Knowledge_Base::clear_index();
	}
}
