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
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-cpt.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-settings.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-logger.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-admin.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-autopilot-cron.php';
		require_once AI_CV_TAILOR_DIR . 'includes/class-application-metaboxes.php';
	}

	public function run() {
		$settings = new AI_CV_Tailor_Settings();
		$settings->init();

		$admin = new AI_CV_Tailor_Admin();
		$admin->init();

		$cpt = new AI_CV_Tailor_CPT();
		$cpt->init();

		$metaboxes = new AI_CV_Tailor_Application_Metaboxes();
		$metaboxes->init();

		$router = new AI_CV_Tailor_Router();
		$router->init();

		$renderer = new AI_CV_Tailor_Renderer();
		$renderer->init();
		
		$statistics = new AI_CV_Tailor_Statistics();
		$statistics->init();
		
		$autopilot_cpt = new AI_CV_Tailor_Autopilot_CPT();
		$autopilot_cpt->init();

		$autopilot_settings = new AI_CV_Tailor_Autopilot_Settings();
		$autopilot_settings->init();

		$autopilot_admin = new AI_CV_Tailor_Autopilot_Admin();
		$autopilot_admin->init();

		$autopilot_cron = new AI_CV_Tailor_Autopilot_Cron();
		$autopilot_cron->init();
	}
}
