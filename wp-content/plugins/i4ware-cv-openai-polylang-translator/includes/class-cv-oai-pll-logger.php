<?php
/**
 * Class CV_OAI_PLL_Logger
 *
 * Handles safe logging of translation runs and stores history without exposing
 * confidential information like OpenAI API keys.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Logger {
    /**
     * Option key used for storing translation history in the database.
     */
    private static $history_option_key = 'cv_oai_pll_translation_history';

    /**
     * Maximum number of logs to retain in the database history.
     */
    private static $max_history_entries = 100;

    /**
     * Logs a translation event.
     *
     * @param int    $source_post_id Source post ID.
     * @param string $target_lang    Target language code (e.g. 'sv', 'en').
     * @param string $model          OpenAI model used.
     * @param bool   $success        Whether the translation succeeded.
     * @param string $error_msg      Error message if failed.
     * @param int    $num_fields     Number of text fields translated.
     * @param int    $draft_id       Newly created translation draft post ID (if success).
     * @param int    $start_time     Microtime timestamp when starting.
     * @return void
     */
    public static function log($source_post_id, $target_lang, $model, $success, $error_msg = '', $num_fields = 0, $draft_id = 0, $start_time = 0) {
        $end_time = microtime(true);
        $duration = $start_time ? round($end_time - $start_time, 2) : 0;

        $new_entry = [
            'source_post_id' => (int) $source_post_id,
            'draft_id'       => (int) $draft_id,
            'target_lang'    => sanitize_text_field($target_lang),
            'model'          => sanitize_text_field($model),
            'success'        => $success ? 1 : 0,
            'error_message'  => sanitize_text_field($error_msg),
            'num_fields'     => (int) $num_fields,
            'duration'       => $duration,
            'user_id'        => get_current_user_id(),
            'date'           => current_time('mysql'),
        ];

        // 1. Log to site-wide history option
        $history = get_option(self::$history_option_key, []);
        if (!is_array($history)) {
            $history = [];
        }

        array_unshift($history, $new_entry);
        if (count($history) > self::$max_history_entries) {
            $history = array_slice($history, 0, self::$max_history_entries);
        }
        update_option(self::$history_option_key, $history);

        // 2. Also save specific translation history on the source post meta
        $post_history = get_post_meta($source_post_id, '_cv_oai_translation_history', true);
        if (!is_array($post_history)) {
            $post_history = [];
        }
        array_unshift($post_history, $new_entry);
        update_post_meta($source_post_id, '_cv_oai_translation_history', $post_history);

        // 3. Fallback/Optional: Log to WordPress debug log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = sprintf(
                '[CV OpenAI Polylang Translator] ID: %d | Lang: %s | Model: %s | Success: %s | Fields: %d | Duration: %ss | Error: %s',
                $source_post_id,
                $target_lang,
                $model,
                $success ? 'YES' : 'NO',
                $num_fields,
                $duration,
                $error_msg ? $error_msg : 'None'
            );
            error_log($log_message);
        }
    }

    /**
     * Retrieves the global translation history.
     *
     * @return array
     */
    public static function get_global_history() {
        return get_option(self::$history_option_key, []);
    }

    /**
     * Retrieves translation history for a specific post.
     *
     * @param int $post_id Post ID.
     * @return array
     */
    public static function get_post_history($post_id) {
        $history = get_post_meta($post_id, '_cv_oai_translation_history', true);
        return is_array($history) ? $history : [];
    }
}
