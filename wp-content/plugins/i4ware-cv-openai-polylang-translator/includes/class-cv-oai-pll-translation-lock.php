<?php
/**
 * Class CV_OAI_PLL_Translation_Lock
 *
 * Implements a transient-based locking mechanism to prevent concurrent
 * translation jobs from running on the same WordPress installation.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Translation_Lock {
    /**
     * Transient key used for the global translation lock.
     */
    private static $lock_key = 'cv_oai_pll_global_lock';

    /**
     * Lock duration in seconds (10 minutes).
     */
    private static $lock_duration = 600;

    /**
     * Acquires the translation lock.
     *
     * @param int $post_id The ID of the post being translated.
     * @return bool True if lock acquired successfully, false if already locked.
     */
    public static function acquire($post_id) {
        $existing = get_transient(self::$lock_key);
        if ($existing !== false) {
            return false;
        }
        return set_transient(self::$lock_key, (int) $post_id, self::$lock_duration);
    }

    /**
     * Releases the translation lock.
     *
     * @return bool True on success, false on failure.
     */
    public static function release() {
        return delete_transient(self::$lock_key);
    }

    /**
     * Checks if the translation lock is active.
     *
     * @return bool True if locked, false otherwise.
     */
    public static function is_locked() {
        return get_transient(self::$lock_key) !== false;
    }

    /**
     * Gets the ID of the post that currently holds the lock.
     *
     * @return int|bool Post ID if locked, false otherwise.
     */
    public static function get_locked_post_id() {
        return get_transient(self::$lock_key);
    }
}
