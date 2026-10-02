<?php
/**
 * Class CV_OAI_PLL_String_Translator
 *
 * Interacts with Polylang's internal PLL_MO class and POMO library to load,
 * translate, and write back registered string translations to the database.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_String_Translator {

    /**
     * Gets a loaded PLL_MO translation object for a specific language.
     *
     * @param string $lang_code Target language code (e.g. 'en', 'sv').
     * @return PLL_MO|WP_Error
     */
    public static function get_mo_for_language($lang_code) {
        global $polylang;

        if (!class_exists('PLL_MO')) {
            return new WP_Error('pll_mo_missing', __('PLL_MO class not found. Polylang may not be active.', 'cv-openai-polylang-translator'));
        }

        if (!isset($polylang) || !isset($polylang->model)) {
            return new WP_Error('polylang_model_missing', __('Polylang global object or model is not initialized.', 'cv-openai-polylang-translator'));
        }

        $language = $polylang->model->get_language($lang_code);
        if (!$language) {
            return new WP_Error('invalid_language', sprintf(__('Language "%s" not found in Polylang settings.', 'cv-openai-polylang-translator'), $lang_code));
        }

        $mo = new PLL_MO();
        $mo->import_from_db($language);

        return $mo;
    }

    /**
     * Saves a single translation string into the Polylang database.
     *
     * @param string $source_text Original text string.
     * @param string $translated_text Translated text string.
     * @param string $target_lang Target language code.
     * @return bool True on success, false on failure.
     */
    public static function save_single_translation($source_text, $translated_text, $target_lang, $context = '') {
        global $polylang;

        if (!class_exists('PLL_MO') || !isset($polylang) || !isset($polylang->model)) {
            return false;
        }

        $language = $polylang->model->get_language($target_lang);
        if (!$language) {
            return false;
        }

        $mo = new PLL_MO();
        $mo->import_from_db($language);

        // Ensure POMO Translation Entry class is loaded
        if (!class_exists('Translation_Entry')) {
            require_once ABSPATH . 'wp-includes/pomo/entry.php';
        }

        // Add or update the translation entry
        $mo->add_entry(new Translation_Entry([
            'singular'     => $source_text,
            'context'      => '',
            'translations' => [$translated_text]
        ]));

        // Write back to DB
        $mo->export_to_db($language);

        // Clear WordPress caches for this translation post
        if (isset($language->mo_id) && $language->mo_id) {
            clean_post_cache((int) $language->mo_id);
        }

        // Also clean general Polylang cache
        wp_cache_delete('mo_' . $target_lang, 'polylang');

        return true;
    }

    /**
     * Translates a batch of strings and saves them.
     * Used for on-demand direct execution.
     *
     * @param array  $strings     List of source string texts.
     * @param string $target_lang Target language code.
     * @param string $api_key     OpenAI API Key.
     * @param string $model       OpenAI model.
     * @return true|WP_Error
     */
    public static function translate_and_save_strings($strings, $target_lang, $api_key, $model) {
        if (empty($strings)) {
            return true;
        }

        $target_language_name = CV_OAI_PLL_Translator::get_language_name_by_code($target_lang);

        // Map strings into a list for JSON payload
        $payload = [];
        foreach ($strings as $index => $str) {
            $payload['str_' . $index] = $str;
        }

        // Preprocess items: Replace URLs, Emails, and HTML tags with safe placeholders
        $url_map = [];
        $email_map = [];
        $html_tag_map = [];
        foreach ($payload as $key => &$str) {
            if (is_string($str)) {
                $str = CV_OAI_PLL_Translator::replace_urls_with_placeholders($str, $url_map);
                $str = CV_OAI_PLL_Translator::replace_emails_with_placeholders($str, $email_map);
                $str = CV_OAI_PLL_Translator::replace_html_tags_with_placeholders($str, $html_tag_map);
            }
        }
        unset($str); // Break references

        $chat_payload = [
            'model'       => $model,
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => CV_OAI_PLL_OpenAI_Client::get_system_prompt($target_language_name),
                ],
                [
                    'role'    => 'user',
                    'content' => "Translate the following JSON object texts from Finnish to " . $target_language_name . ". Return only the translated JSON:\n\n" . wp_json_encode($payload),
                ]
            ]
        ];

        $response = CV_OAI_PLL_OpenAI_Client::translate_payload_with_usage($chat_payload, $api_key);
        if (is_wp_error($response)) {
            return $response;
        }

        $translated = json_decode($response['content'], true);
        if (!is_array($translated)) {
            return new WP_Error('invalid_json', __('OpenAI response was not valid JSON.', 'cv-openai-polylang-translator'));
        }

        foreach ($payload as $key => $source_text) {
            if (isset($translated[$key])) {
                $translated_text = $translated[$key];

                // Restore HTML tags, URLs, and Emails
                $translated_text = CV_OAI_PLL_Translator::restore_html_tags_from_placeholders($translated_text, $html_tag_map);
                foreach ($url_map as $placeholder => $url) {
                    $translated_text = str_replace($placeholder, $url, $translated_text);
                }
                foreach ($email_map as $placeholder => $email) {
                    $translated_text = str_replace($placeholder, $email, $translated_text);
                }

                // Get original source text
                $orig_idx = str_replace('str_', '', $key);
                $orig_source = isset($strings[$orig_idx]) ? $strings[$orig_idx] : $source_text;
                
                // Validate individual translation integrity against original source
                $validation = CV_OAI_PLL_Validator::validate([$key => $orig_source], [$key => $translated_text], $target_lang);
                if (!is_wp_error($validation)) {
                    self::save_single_translation($orig_source, $translated_text, $target_lang);
                    CV_OAI_PLL_DB::add_cached_translation($orig_source, $target_lang, $translated_text);
                }
            }
        }

        return true;
    }
}
