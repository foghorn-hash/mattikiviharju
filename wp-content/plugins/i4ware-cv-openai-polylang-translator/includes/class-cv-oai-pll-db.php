<?php
/**
 * Class CV_OAI_PLL_DB
 *
 * Manages custom database tables for the translation queue and translation memory (cache),
 * database operations for CRUD tasks, and statistics aggregation.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_DB {

    /**
     * Gets the queue table name.
     *
     * @return string
     */
    public static function get_queue_table() {
        global $wpdb;
        return $wpdb->prefix . 'cv_oai_pll_queue';
    }

    /**
     * Gets the translation memory (cache) table name.
     *
     * @return string
     */
    public static function get_memory_table() {
        global $wpdb;
        return $wpdb->prefix . 'cv_oai_pll_memory';
    }

    /**
     * Creates or updates the custom database tables on activation.
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $queue_table = self::get_queue_table();
        $memory_table = self::get_memory_table();

        // 1. Queue Table Schema
        $sql_queue = "CREATE TABLE $queue_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            item_type varchar(50) NOT NULL,
            item_id varchar(255) NOT NULL,
            source_language varchar(10) NOT NULL DEFAULT 'fi',
            target_language varchar(10) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            error_message text NULL,
            attempts int(11) NOT NULL DEFAULT 0,
            model varchar(50) NULL,
            prompt_tokens int(11) NOT NULL DEFAULT 0,
            completion_tokens int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            UNIQUE KEY type_item_lang (item_type, item_id, target_language)
        ) $charset_collate;";

        // 2. Translation Memory (Cache) Table Schema
        $sql_memory = "CREATE TABLE $memory_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_hash varchar(32) NOT NULL,
            source_text longtext NOT NULL,
            target_language varchar(10) NOT NULL,
            translated_text longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY hash_lang (source_hash, target_language)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_queue);
        dbDelta($sql_memory);
    }

    /**
     * Adds an item to the translation queue.
     *
     * @param string $item_type Type of item ('post', 'term', 'string').
     * @param string $item_id   ID of item (post/term ID or string identifier).
     * @param string $target_lang Target language code.
     * @param string $source_lang Source language code.
     * @return int|bool Inserted ID, true on existing, or false on failure.
     */
    public static function add_to_queue($item_type, $item_id, $target_lang, $source_lang = 'fi') {
        global $wpdb;
        $table = self::get_queue_table();

        // Check if already in queue to avoid duplicates
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status FROM $table WHERE item_type = %s AND item_id = %s AND target_language = %s",
            $item_type,
            $item_id,
            $target_lang
        ));

        $now = current_time('mysql');

        if ($existing) {
            // Re-queue if failed or completed if desired, but default is to reset to pending
            if (in_array($existing->status, ['failed', 'completed'], true)) {
                $wpdb->update(
                    $table,
                    [
                        'status'        => 'pending',
                        'error_message' => null,
                        'attempts'      => 0,
                        'updated_at'    => $now
                    ],
                    ['id' => $existing->id]
                );
            }
            return $existing->id;
        }

        $result = $wpdb->insert(
            $table,
            [
                'item_type'       => sanitize_text_field($item_type),
                'item_id'         => sanitize_text_field($item_id),
                'source_language' => sanitize_text_field($source_lang),
                'target_language' => sanitize_text_field($target_lang),
                'status'          => 'pending',
                'attempts'        => 0,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Retrieves a batch of pending queue items.
     *
     * @param int $batch_size Maximum items to retrieve.
     * @return array
     */
    public static function get_pending_batch($batch_size = 10) {
        global $wpdb;
        $table = self::get_queue_table();

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status IN ('pending', 'processing') AND attempts < 5 ORDER BY id ASC LIMIT %d",
            $batch_size
        ));
    }

    /**
     * Updates queue item status.
     *
     * @param int         $id            Queue item ID.
     * @param string      $status        Status to set.
     * @param string      $error_message Optional error message.
     * @param int|null    $attempts      Optional attempts count update.
     * @param array|null  $usage         Optional token usage array ('prompt_tokens', 'completion_tokens', 'model').
     * @return bool
     */
    public static function update_item_status($id, $status, $error_message = '', $attempts = null, $usage = null) {
        global $wpdb;
        $table = self::get_queue_table();

        $data = [
            'status'     => sanitize_text_field($status),
            'updated_at' => current_time('mysql'),
        ];

        if ($error_message !== '') {
            $data['error_message'] = sanitize_text_field($error_message);
        }

        if ($attempts !== null) {
            $data['attempts'] = (int) $attempts;
        }

        if (is_array($usage)) {
            if (isset($usage['model'])) {
                $data['model'] = sanitize_text_field($usage['model']);
            }
            if (isset($usage['prompt_tokens'])) {
                $data['prompt_tokens'] = (int) $usage['prompt_tokens'];
            }
            if (isset($usage['completion_tokens'])) {
                $data['completion_tokens'] = (int) $usage['completion_tokens'];
            }
        }

        $result = $wpdb->update($table, $data, ['id' => (int) $id]);
        return $result !== false;
    }

    /**
     * Retries failed items in the queue.
     *
     * @param int|null $id Specific item ID, or null to retry all failed items.
     * @return bool
     */
    public static function retry_failed_items($id = null) {
        global $wpdb;
        $table = self::get_queue_table();
        $now = current_time('mysql');

        if ($id !== null) {
            return $wpdb->update(
                $table,
                [
                    'status'        => 'pending',
                    'error_message' => null,
                    'attempts'      => 0,
                    'updated_at'    => $now
                ],
                ['id' => (int) $id, 'status' => 'failed']
            ) !== false;
        }

        return $wpdb->query(
            "UPDATE $table SET status = 'pending', error_message = NULL, attempts = 0, updated_at = '$now' WHERE status = 'failed'"
        ) !== false;
    }

    /**
     * Gets queue progress statistics.
     *
     * @return array
     */
    public static function get_queue_stats() {
        global $wpdb;
        $table = self::get_queue_table();

        $results = $wpdb->get_results("SELECT status, COUNT(*) as count FROM $table GROUP BY status");
        
        $stats = [
            'total'      => 0,
            'pending'    => 0,
            'processing' => 0,
            'completed'  => 0,
            'failed'     => 0,
        ];

        foreach ($results as $row) {
            if (array_key_exists($row->status, $stats)) {
                $stats[$row->status] = (int) $row->count;
            }
            $stats['total'] += (int) $row->count;
        }

        return $stats;
    }

    /**
     * Deletes all items from the queue.
     */
    public static function clear_queue() {
        global $wpdb;
        $table = self::get_queue_table();
        return $wpdb->query("TRUNCATE TABLE $table") !== false;
    }

    /**
     * Gets failed queue items.
     *
     * @param int $limit Max items to return.
     * @return array
     */
    public static function get_failed_items($limit = 50) {
        global $wpdb;
        $table = self::get_queue_table();
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'failed' ORDER BY updated_at DESC LIMIT %d",
            $limit
        ));
    }

    /**
     * Translation Memory (Cache): Looks up a translation.
     *
     * @param string $source_text Original text.
     * @param string $target_lang Target language code.
     * @return string|bool Translated text on hit, false on miss.
     */
    public static function get_cached_translation($source_text, $target_lang) {
        global $wpdb;
        $table = self::get_memory_table();
        
        $hash = md5(trim($source_text));

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT translated_text FROM $table WHERE source_hash = %s AND target_language = %s",
            $hash,
            $target_lang
        ));

        return $result ? $result : false;
    }

    /**
     * Translation Memory (Cache): Stores a translation.
     *
     * @param string $source_text Original text.
     * @param string $target_lang Target language code.
     * @param string $translated_text Translated text.
     * @return bool
     */
    public static function add_cached_translation($source_text, $target_lang, $translated_text) {
        global $wpdb;
        $table = self::get_memory_table();

        $hash = md5(trim($source_text));
        $now = current_time('mysql');

        $result = $wpdb->replace(
            $table,
            [
                'source_hash'     => $hash,
                'source_text'     => $source_text,
                'target_language' => sanitize_text_field($target_lang),
                'translated_text' => $translated_text,
                'created_at'      => $now
            ]
        );

        return $result !== false;
    }

    /**
     * Aggregates token usage and calculates estimated cost.
     *
     * Rates:
     * - gpt-4o-mini: Input $0.150 / 1M tokens ($0.00000015 / token), Output $0.600 / 1M tokens ($0.0000006 / token)
     * - gpt-4o:      Input $5.00 / 1M tokens ($0.000005 / token), Output $15.00 / 1M tokens ($0.000015 / token)
     * - default:     Same as gpt-4o-mini
     *
     * @return array Array with 'prompt_tokens', 'completion_tokens', 'cost_estimate'.
     */
    public static function get_api_usage_stats() {
        global $wpdb;
        $table = self::get_queue_table();

        $rows = $wpdb->get_results("SELECT model, SUM(prompt_tokens) as total_prompt, SUM(completion_tokens) as total_completion FROM $table GROUP BY model");

        $stats = [
            'prompt_tokens'     => 0,
            'completion_tokens' => 0,
            'cost_estimate'     => 0.0000
        ];

        foreach ($rows as $row) {
            $prompt = (int) $row->total_prompt;
            $completion = (int) $row->total_completion;
            
            $stats['prompt_tokens'] += $prompt;
            $stats['completion_tokens'] += $completion;

            $model = strtolower($row->model);

            // Compute pricing
            if (strpos($model, 'gpt-4o-mini') !== false) {
                $cost = ($prompt * 0.15 + $completion * 0.60) / 1000000;
            } elseif (strpos($model, 'gpt-4o') !== false) {
                $cost = ($prompt * 5.00 + $completion * 15.00) / 1000000;
            } else {
                // Fallback pricing (same as gpt-4o-mini)
                $cost = ($prompt * 0.15 + $completion * 0.60) / 1000000;
            }

            $stats['cost_estimate'] += $cost;
        }

        $stats['cost_estimate'] = round($stats['cost_estimate'], 5);
        return $stats;
    }
}
