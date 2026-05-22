<?php

class AI_CV_Tailor_Plugin {

	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'ai-cv-tailor';
		$this->version = AI_CV_TAILOR_VERSION;
		$this->load_dependencies();
	}

	private function load_dependencies() {
		require_once AI_CV_TAILOR_DIR . 'includes/class-settings.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-admin.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-cpt.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-openai.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-router.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-renderer.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-statistics.php';
	}

	public function run() {
		$settings = new AI_CV_Tailor_Settings();
		$settings->init();

		$admin = new AI_CV_Tailor_Admin();
		$admin->init();

		$cpt = new AI_CV_Tailor_CPT();
		$cpt->init();

		$router = new AI_CV_Tailor_Router();
		$router->init();

		$renderer = new AI_CV_Tailor_Renderer();
		$renderer->init();
		
		$statistics = new AI_CV_Tailor_Statistics();
		$statistics->init();
	}
}
