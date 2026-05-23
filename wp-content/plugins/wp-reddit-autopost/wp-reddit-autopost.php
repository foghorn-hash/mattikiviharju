<?php
/**
 * Plugin Name: WP Reddit Autopost
 * Description: Automatically publish new WordPress posts to a specified Reddit subreddit.
 * Version: 1.0.0
 * Author: i4ware ModPilot AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WP_REDDIT_AUTOPOST_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_REDDIT_AUTOPOST_URL', plugin_dir_url( __FILE__ ) );

// Load classes
require_once WP_REDDIT_AUTOPOST_DIR . 'includes/class-settings.php';
require_once WP_REDDIT_AUTOPOST_DIR . 'includes/class-reddit-api.php';
require_once WP_REDDIT_AUTOPOST_DIR . 'includes/class-post-handler.php';

// Initialize
function wp_reddit_autopost_init() {
	new WP_Reddit_Autopost_Settings();
	new WP_Reddit_Autopost_Handler();
}
add_action( 'plugins_loaded', 'wp_reddit_autopost_init' );
