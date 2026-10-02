<?php
/**
 * Class CV_OAI_PLL_Cron
 *
 * Manages WP-Cron schedules and background execution hooks for the translation queue.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Cron {

    /**
     * Initializes WP-Cron hooks and filters.
     */
    public static function init() {
        add_filter('cron_schedules', [__CLASS__, 'add_cron_schedules']);
        add_action('cv_oai_pll_cron_hook', [__CLASS__, 'run_queue_worker']);

        // Schedule cron on init if not already scheduled
        add_action('admin_init', [__CLASS__, 'schedule_cron_job']);
    }

    /**
     * Adds custom recurring cron schedule (every minute).
     *
     * @param array $schedules Existing schedules.
     * @return array
     */
    public static function add_cron_schedules($schedules) {
        $schedules['cv_oai_pll_every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute', 'cv-openai-polylang-translator'),
        ];
        return $schedules;
    }

    /**
     * Schedules the background cron event if not already registered.
     */
    public static function schedule_cron_job() {
        if (!wp_next_scheduled('cv_oai_pll_cron_hook')) {
            wp_schedule_event(time(), 'cv_oai_pll_every_minute', 'cv_oai_pll_cron_hook');
        }
    }

    /**
     * Clears scheduled cron hook upon plugin deactivation.
     */
    public static function unschedule_cron_job() {
        wp_clear_scheduled_hook('cv_oai_pll_cron_hook');
    }

    /**
     * Execution worker called by WP-Cron background worker.
     */
    public static function run_queue_worker() {
        try {
            require_once dirname(__FILE__) . '/class-cv-oai-pll-queue.php';
            CV_OAI_PLL_Queue::process_batch(10);
        } catch (\Throwable $t) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CV OpenAI Polylang Translator] Cron worker error: ' . $t->getMessage());
            }
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[CV OpenAI Polylang Translator] Cron worker exception: ' . $e->getMessage());
            }
        }
    }
}
