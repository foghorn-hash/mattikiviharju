<?php
/**
 * Plugin Name: Gemini AI Avatar Assistant
 * Plugin URI: https://mattikiviharju.i4ware.fi/
 * Description: Google Gemini -pohjainen puhuva AI-avatar ja avustaja Matti Kiviharjun verkkosivustolle.
 * Version: 1.0.0
 * Author: Matti Kiviharju / i4ware Software
 * Author URI: https://www.i4ware.fi/
 * Text Domain: gemini-avatar-assistant
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GEMINI_AVATAR_VERSION', '1.0.0' );
define( 'GEMINI_AVATAR_PLUGIN_FILE', __FILE__ );
define( 'GEMINI_AVATAR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GEMINI_AVATAR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoload / Include required classes
require_once GEMINI_AVATAR_PLUGIN_DIR . 'includes/class-settings.php';
require_once GEMINI_AVATAR_PLUGIN_DIR . 'includes/class-knowledge-base.php';
require_once GEMINI_AVATAR_PLUGIN_DIR . 'includes/class-gemini-client.php';
require_once GEMINI_AVATAR_PLUGIN_DIR . 'includes/class-api-endpoint.php';
require_once GEMINI_AVATAR_PLUGIN_DIR . 'admin/class-admin.php';
require_once GEMINI_AVATAR_PLUGIN_DIR . 'public/class-public.php';
require_once GEMINI_AVATAR_PLUGIN_DIR . 'includes/class-gemini-avatar-assistant.php';

/**
 * Plugin activation hook
 */
register_activation_hook( __FILE__, array( 'Gemini_Avatar_Assistant', 'activate' ) );

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( __FILE__, array( 'Gemini_Avatar_Assistant', 'deactivate' ) );

/**
 * Initialize main plugin instance
 */
add_action( 'plugins_loaded', array( 'Gemini_Avatar_Assistant', 'get_instance' ) );
