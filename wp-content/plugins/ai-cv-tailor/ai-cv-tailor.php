<?php
/**
 * Plugin Name:       AI CV Tailor
 * Description:       Create tailored CVs and cover letters from job descriptions using OpenAI.
 * Version:           1.0.0
 * Author:            Matti Kiviharju
 * Text Domain:       ai-cv-tailor
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'AI_CV_TAILOR_VERSION', '1.0.0' );
define( 'AI_CV_TAILOR_DIR', plugin_dir_path( __FILE__ ) );
define( 'AI_CV_TAILOR_URL', plugin_dir_url( __FILE__ ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once AI_CV_TAILOR_DIR . 'includes/class-plugin.php';

/**
 * Begins execution of the plugin.
 */
function run_ai_cv_tailor() {
	$plugin = new AI_CV_Tailor_Plugin();
	$plugin->run();
}
run_ai_cv_tailor();

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, 'ai_cv_tailor_activate' );
function ai_cv_tailor_activate() {
	require_once AI_CV_TAILOR_DIR . 'includes/class-statistics.php';
	AI_CV_Tailor_Statistics::create_table();
	
	require_once AI_CV_TAILOR_DIR . 'includes/class-cpt.php';
	$cpt = new AI_CV_Tailor_CPT();
	$cpt->register_post_type();
	
	require_once AI_CV_TAILOR_DIR . 'includes/class-router.php';
	$router = new AI_CV_Tailor_Router();
	$router->add_rewrite_rules();
	
	flush_rewrite_rules();
}

/**
 * Deactivation Hook
 */
register_deactivation_hook( __FILE__, 'ai_cv_tailor_deactivate' );
function ai_cv_tailor_deactivate() {
	flush_rewrite_rules();
}
