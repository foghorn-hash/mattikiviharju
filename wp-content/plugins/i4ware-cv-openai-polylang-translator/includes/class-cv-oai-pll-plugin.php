<?php
/**
 * Class CV_OAI_PLL_Plugin
 *
 * The main controller of the CV OpenAI Polylang Translator plugin.
 * Handles inclusion of other files and boots the admin module.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Plugin {

    /**
     * Instance of CV_OAI_PLL_Admin.
     */
    private $admin;

    /**
     * Constructor: Autoloads files and instantiates hooks.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->admin = new CV_OAI_PLL_Admin();
    }

    /**
     * Includes all class files needed for the plugin.
     */
    private function load_dependencies() {
        $dir = dirname(__FILE__);
        
        require_once $dir . '/class-cv-oai-pll-db.php';
        require_once $dir . '/class-cv-oai-pll-translation-lock.php';
        require_once $dir . '/class-cv-oai-pll-logger.php';
        require_once $dir . '/class-cv-oai-pll-openai-client.php';
        require_once $dir . '/class-cv-oai-pll-validator.php';
        require_once $dir . '/class-cv-oai-pll-content-extractor.php';
        require_once $dir . '/class-cv-oai-pll-translator.php';
        require_once $dir . '/class-cv-oai-pll-string-translator.php';
        require_once $dir . '/class-cv-oai-pll-queue.php';
        require_once $dir . '/class-cv-oai-pll-cron.php';
        require_once $dir . '/class-cv-oai-pll-media-analyzer.php';
        require_once $dir . '/class-cv-oai-pll-admin.php';
    }

    /**
     * Boots the plugin admin area.
     */
    public function run() {
        $this->admin->init();
        CV_OAI_PLL_Cron::init();
        CV_OAI_PLL_Media_Analyzer::init();

        // Self-healing database tables creation
        $db_version = get_option('cv_oai_pll_db_version', '0');
        if (version_compare($db_version, '2.0.0', '<')) {
            require_once dirname(__FILE__) . '/class-cv-oai-pll-db.php';
            CV_OAI_PLL_DB::create_tables();
            update_option('cv_oai_pll_db_version', '2.0.0');
        }
        
        // Register activation/deactivation hooks
        register_deactivation_hook(
            dirname(dirname(__FILE__)) . '/cv-openai-polylang-translator.php',
            [$this, 'deactivate']
        );
    }

    /**
     * Runs on plugin deactivation.
     * Cleans up transients and options.
     */
    public function deactivate() {
        // Ensure translation locks are freed
        CV_OAI_PLL_Translation_Lock::release();

        // Clear scheduled WP-Cron jobs
        CV_OAI_PLL_Cron::unschedule_cron_job();
    }
}
