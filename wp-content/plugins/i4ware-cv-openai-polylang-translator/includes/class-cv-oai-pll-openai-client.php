<?php
/**
 * Class CV_OAI_PLL_OpenAI_Client
 *
 * Handles HTTP requests to the OpenAI API for translating content.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_OpenAI_Client {
    /**
     * Endpoint for OpenAI chat completions.
     */
    private static $chat_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Default connection/request timeout in seconds.
     */
    private static $request_timeout = 120;

    /**
     * Sends a translation payload to the OpenAI Chat Completions API.
     *
     * @param array  $payload Payload array (model, messages, response_format, temperature, etc.).
     * @param string $api_key OpenAI API key.
     * @return string|WP_Error Translated text block (JSON string) or WP_Error on failure.
     */
    public static function translate_payload($payload, $api_key) {
        $result = self::translate_payload_with_usage($payload, $api_key);
        if (is_wp_error($result)) {
            return $result;
        }
        return $result['content'];
    }

    /**
     * Sends a translation payload to the OpenAI Chat Completions API and returns content and token usage.
     *
     * @param array  $payload Payload array (model, messages, response_format, temperature, etc.).
     * @param string $api_key OpenAI API key.
     * @return array|WP_Error Array with 'content' and 'usage' keys or WP_Error on failure.
     */
    public static function translate_payload_with_usage($payload, $api_key) {
        if (empty($api_key)) {
            return new WP_Error('openai_missing_key', __('OpenAI API key is missing.', 'cv-openai-polylang-translator'));
        }

        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ];

        $response = wp_remote_post(self::$chat_endpoint, [
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
            'timeout' => self::$request_timeout,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);

        if ($status_code < 200 || $status_code >= 300) {
            $err_data = json_decode($body, true);
            $err_msg  = isset($err_data['error']['message']) ? $err_data['error']['message'] : __('Unknown OpenAI API error.', 'cv-openai-polylang-translator');
            return new WP_Error('openai_api_error', sprintf(__('OpenAI API Error (Status %d): %s', 'cv-openai-polylang-translator'), $status_code, $err_msg));
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded) || empty($decoded['choices'][0]['message']['content'])) {
            return new WP_Error('openai_invalid_response', __('OpenAI returned a response with an unexpected structure.', 'cv-openai-polylang-translator'));
        }

        return [
            'content' => trim($decoded['choices'][0]['message']['content']),
            'usage'   => isset($decoded['usage']) ? $decoded['usage'] : [
                'prompt_tokens'     => 0,
                'completion_tokens' => 0,
                'total_tokens'      => 0
            ]
        ];
    }

    /**
     * Compiles a strict system prompt tailored for Swedish and English translation.
     *
     * @param string $target_language_name Name of the target language (e.g. "English", "Swedish").
     * @return string The prompt template text.
     */
    public static function get_system_prompt($target_language_name) {
        return sprintf(
            "You are a professional WordPress localisation translator.\n\n" .
            "Translate the supplied Finnish text into %s.\n\n" .
            "For Swedish, use professional Swedish suitable for B2B communication.\n\n" .
            "Translate only the human-readable text that is explicitly marked as translatable.\n\n" .
            "Do not add, remove or invent facts.\n\n" .
            "Do not invent product features, customer references, prices, legal claims, security claims, compliance claims or business promises.\n\n" .
            "Preserve product names and trademarks (they must remain in Latin/English characters and not be translated or transliterated, e.g.: i4ware, i4ware Software, i4ware®, Timesheet for Jira, Jira, Atlassian, OpenAI, Microsoft, SAP, ERP, SaaS, WordPress, Polylang, Advanced Custom Fields, ACF, Freshworks, Freshchat, Microsoft Teams), URLs, email addresses, phone numbers, currencies, dates, numbers, version numbers and technical identifiers.\n\n" .
            "Preserve HTML tags, Gutenberg block structure, shortcodes, JSON metadata, CSS classes, attachment IDs, image URLs, video URLs, YouTube embeds and iframe sources.\n\n" .
            "Images, featured images, galleries, videos, audio files and embedded YouTube videos must remain original.\n\n" .
            "Do not translate code, JavaScript, CSS, PHP, JSON, shortcode attributes or URL parameters.\n\n" .
            "Return only valid JSON.\n\n" .
            "The JSON structure must match the input structure exactly.\n\n" .
            "If a sentence is ambiguous, translate conservatively and do not guess.",
            $target_language_name
        );
    }
}
