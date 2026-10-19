<?php
/**
 * Class CV_OAI_PLL_Translator
 *
 * Coordinates the full WordPress single post translation flow: content extraction,
 * chunking, sequential OpenAI API requests, translation validation, draft saving,
 * Polylang association, and transient locking.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Translator {

    /**
     * Translates a single WordPress post or page from Finnish into a target language.
     *
     * @param int    $post_id          The source Finnish post ID.
     * @param string $target_lang      The target language code (e.g. 'en', 'sv').
     * @param array  $options          Translation options (title, excerpt, content, acf, caption, alt).
     * @param bool   $overwrite_draft  Whether the user explicitly confirmed overwriting an existing draft.
     * @return int|WP_Error New or updated translation post ID on success, WP_Error on failure.
     */
    public static function translate_post($post_id, $target_lang, $options, $overwrite_draft = false, $bypass_lock = false, $bypass_cache = false) {
        $start_time = microtime(true);
        $post       = get_post($post_id);

        if (!$post) {
            return new WP_Error('invalid_post', __('Source post not found.', 'cv-openai-polylang-translator'));
        }

        // 1. Dependency checks
        if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post_translations')) {
            return new WP_Error('polylang_missing', __('Polylang is not active.', 'cv-openai-polylang-translator'));
        }

        $api_key = get_option('cv_oai_pll_api_key', '');
        $model   = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        if (empty($api_key)) {
            return new WP_Error('openai_missing_key', __('OpenAI API key is missing. Please configure it in settings.', 'cv-openai-polylang-translator'));
        }

        // Check source post language
        $source_lang = pll_get_post_language($post_id);
        if (empty($source_lang)) {
            return new WP_Error('invalid_source_lang', __('Please set post language in Polylang before translating.', 'cv-openai-polylang-translator'));
        }
        if ($source_lang === $target_lang) {
            return new WP_Error('invalid_target_lang', __('Target language must be different from source language.', 'cv-openai-polylang-translator'));
        }

        // 2. Existing translation check
        $translations = pll_get_post_translations($post_id);
        $existing_id  = isset($translations[$target_lang]) ? (int) $translations[$target_lang] : 0;
        $is_update    = false;
        $existing_post = null;

        if ($existing_id) {
            $existing_post = get_post($existing_id);
            if ($existing_post) {
                if ($existing_post->post_status === 'publish') {
                    return new WP_Error('published_translation_exists', __('A published translation already exists for this language and cannot be automatically overwritten.', 'cv-openai-polylang-translator'));
                }

                if ($existing_post->post_status === 'draft') {
                    if (!$overwrite_draft) {
                        return new WP_Error('draft_exists_no_confirm', __('A draft translation already exists. Please confirm that you want to overwrite it.', 'cv-openai-polylang-translator'));
                    }
                    $is_update = true;
                }
            }
        }

        // 3. Acquire global translation lock
        if (!$bypass_lock && !CV_OAI_PLL_Translation_Lock::acquire($post_id)) {
            return new WP_Error('locked', __('Another translation job is currently running on this site. Please wait a few minutes.', 'cv-openai-polylang-translator'));
        }

        try {
            // Get ACF fields enabled for translation
            $enabled_acf_fields = get_option('cv_oai_pll_acf_fields', []);
            if (!is_array($enabled_acf_fields)) {
                $enabled_acf_fields = [];
            }

            // 4. Extract translatable payload
            $payload = CV_OAI_PLL_Content_Extractor::extract_translatable($post, $options, $enabled_acf_fields);
            if (empty($payload)) {
                throw new Exception(__('No translatable content found for the selected options.', 'cv-openai-polylang-translator'));
            }

            // 4.5. Check Cache / Translation Memory
            require_once dirname(__FILE__) . '/class-cv-oai-pll-db.php';
            $cached_results = [];
            $uncached_payload = [];

            foreach ($payload as $key => $item) {
                if (isset($item['content']) && is_string($item['content']) && trim($item['content']) !== '') {
                    $cached = !$bypass_cache ? CV_OAI_PLL_DB::get_cached_translation($item['content'], $target_lang) : false;
                    if ($cached !== false) {
                        $cached_results[$key] = $cached;
                    } else {
                        $uncached_payload[$key] = $item;
                    }
                } else {
                    $uncached_payload[$key] = $item;
                }
            }

            // Preprocess uncached items: Replace URLs, Emails, and HTML tags with safe placeholders
            $url_map = [];
            $email_map = [];
            $html_tag_map = [];
            foreach ($uncached_payload as $key => &$item) {
                if (isset($item['content']) && is_string($item['content'])) {
                    $item['content'] = self::replace_urls_with_placeholders($item['content'], $url_map);
                    $item['content'] = self::replace_emails_with_placeholders($item['content'], $email_map);
                    $item['content'] = self::replace_html_tags_with_placeholders($item['content'], $html_tag_map);
                }
            }

            $translated_results = [];
            $total_prompt_tokens = 0;
            $total_completion_tokens = 0;

            if (!empty($uncached_payload)) {
                // 5. Chunk the payload for resource-safety
                $chunks = CV_OAI_PLL_Content_Extractor::chunk_payload($uncached_payload);
                $cooldown = (int) get_option('cv_oai_pll_cooldown', 0);

                // Get target language label (e.g. "Swedish", "English")
                $target_language_name = self::get_language_name_by_code($target_lang);

                // 6. Sequential API calls and validations
                $chunk_index = 0;
                foreach ($chunks as $chunk) {
                    // Apply optional cooldown between chunks
                    if ($chunk_index > 0 && $cooldown > 0) {
                        sleep($cooldown);
                    }

                    $chat_payload = [
                        'model'       => $model,
                        'temperature' => 0.2,
                        'response_format' => ['type' => 'json_object'],
                        'messages'    => [
                            [
                                'role'    => 'system',
                                'content' => CV_OAI_PLL_OpenAI_Client::get_system_prompt($target_language_name),
                            ],
                            [
                                'role'    => 'user',
                                'content' => "Translate the following JSON object translatable fields into " . $target_language_name . ". IMPORTANT: Do not translate or transliterate trademark names, product names, or technology identifiers (such as i4ware, Atlassian, Jira, WordPress, OpenAI, Microsoft, SAP, ERP, SaaS, Polylang, ACF, Freshworks, Freshchat, Microsoft Teams). They must remain in Latin (English) characters exactly as written. Return only the translated JSON matching the structure:\n\n" . wp_json_encode($chunk),
                            ],
                        ],
                    ];

                    $response_data = CV_OAI_PLL_OpenAI_Client::translate_payload_with_usage($chat_payload, $api_key);
                    if (is_wp_error($response_data)) {
                        throw new Exception($response_data->get_error_message());
                    }

                    $response_content = $response_data['content'];
                    $total_prompt_tokens += isset($response_data['usage']['prompt_tokens']) ? (int) $response_data['usage']['prompt_tokens'] : 0;
                    $total_completion_tokens += isset($response_data['usage']['completion_tokens']) ? (int) $response_data['usage']['completion_tokens'] : 0;

                    $decoded_chunk = json_decode($response_content, true);
                    if (!is_array($decoded_chunk)) {
                        throw new Exception(__('Failed to parse translation chunk as JSON.', 'cv-openai-polylang-translator'));
                    }

                    // Validate chunk response for quality and integrity
                    $validation_res = CV_OAI_PLL_Validator::validate($chunk, $decoded_chunk);
                    if (is_wp_error($validation_res)) {
                        throw new Exception(sprintf(__('Validation failed on chunk %d: %s', 'cv-openai-polylang-translator'), $chunk_index + 1, $validation_res->get_error_message()));
                    }

                    // Merge successfully translated keys
                    $translated_results = array_merge($translated_results, $decoded_chunk);
                    $chunk_index++;
                }

                // Restore placeholders in translated_results
                foreach ($translated_results as $key => &$val) {
                    if (is_string($val)) {
                        // Restore HTML tags first
                        $val = self::restore_html_tags_from_placeholders($val, $html_tag_map);
                        // Restore URLs
                        foreach ($url_map as $placeholder => $url) {
                            $val = str_replace($placeholder, $url, $val);
                        }
                        // Restore Emails
                        foreach ($email_map as $placeholder => $email) {
                            $val = str_replace($placeholder, $email, $val);
                        }
                    }
                }

                // Reassemble for caching
                $reassembled_translated = [];
                $split_groups = [];
                foreach ($translated_results as $k => $v) {
                    if (preg_match('/^(.*)_split_(\d+)$/', $k, $matches)) {
                        $base_key = $matches[1];
                        $index    = (int) $matches[2];
                        $split_groups[$base_key][$index] = $v;
                    } else {
                        $reassembled_translated[$k] = $v;
                    }
                }
                foreach ($split_groups as $base_key => $parts) {
                    ksort($parts);
                    $reassembled_translated[$base_key] = implode('', $parts);
                }

                // Save new translations into translation memory (cache)
                foreach ($reassembled_translated as $key => $translated_text) {
                    if (isset($uncached_payload[$key]['content']) && is_string($translated_text)) {
                        $source_text = $payload[$key]['content'];
                        CV_OAI_PLL_DB::add_cached_translation($source_text, $target_lang, $translated_text);
                    }
                }
            }

            // Merge cached results with new translated results
            $translated_results = array_merge($translated_results, $cached_results);

            // 7. Compile translated elements
            $compiled = CV_OAI_PLL_Content_Extractor::compile_translated_post($post, $translated_results, $options, $enabled_acf_fields, $existing_post);

            // 8. Create or Update Translation Draft
            $post_data = [
                'post_type'    => $post->post_type,
                'post_status'  => $is_update ? get_post_status($existing_id) : 'draft',
                'post_title'   => $compiled['post_title'],
                'post_excerpt' => $compiled['post_excerpt'],
                'post_content' => $compiled['post_content'],
                'menu_order'   => (int) $post->menu_order,
            ];

            if ($is_update) {
                $post_data['ID'] = $existing_id;
                $new_post_id = wp_update_post($post_data, true);
            } else {
                $new_post_id = wp_insert_post($post_data, true);
            }

            if (is_wp_error($new_post_id) || !$new_post_id) {
                $err_msg = is_wp_error($new_post_id) ? $new_post_id->get_error_message() : __('Failed to insert/update post in DB.', 'cv-openai-polylang-translator');
                throw new Exception($err_msg);
            }

            // 9. Attach featured image (Original ID, do not duplicate/download)
            if (has_post_thumbnail($post_id)) {
                $thumbnail_id = get_post_thumbnail_id($post_id);
                if ($thumbnail_id) {
                    set_post_thumbnail($new_post_id, $thumbnail_id);
                }
            }

            // 10. Update ACF Field Values using update_field() to preserve references
            if (!empty($options['acf']) && !empty($compiled['acf_data']) && function_exists('update_field')) {
                foreach ($compiled['acf_data'] as $field_name => $field_val) {
                    update_field($field_name, $field_val, $new_post_id);
                }
            }

            // 10.5. Update Custom/SEO fields
            if (!empty($compiled['meta_data'])) {
                foreach ($compiled['meta_data'] as $meta_key => $meta_val) {
                    update_post_meta($new_post_id, $meta_key, $meta_val);
                }
            }

            // 11. Associate language and link Polylang translations
            if (function_exists('pll_set_post_language')) {
                pll_set_post_language($new_post_id, $target_lang);
            }

            $translations = pll_get_post_translations($post_id);
            if (!is_array($translations)) {
                $translations = [];
            }
            if (!isset($translations[$source_lang])) {
                $translations[$source_lang] = (int) $post_id;
            }
            $translations[$target_lang] = (int) $new_post_id;
            if (function_exists('pll_save_post_translations')) {
                pll_save_post_translations($translations);
            }

            // Synchronize and translate post categories and tags
            self::sync_post_taxonomies($post_id, $new_post_id, $target_lang, $api_key, $model);

            // 12. Save post meta details
            update_post_meta($new_post_id, '_cv_oai_source_post_id', (int) $post_id);
            update_post_meta($new_post_id, '_cv_oai_source_language', sanitize_text_field($source_lang));
            update_post_meta($new_post_id, '_cv_oai_target_language', sanitize_text_field($target_lang));
            update_post_meta($new_post_id, '_cv_oai_translation_date', current_time('mysql'));
            update_post_meta($new_post_id, '_cv_oai_model', sanitize_text_field($model));
            // Always set human review required to 1
            update_post_meta($new_post_id, '_cv_oai_review_required', '1');

            // Store token usage in transient for the queue processor or stats
            set_transient('cv_oai_pll_last_tokens_usage', [
                'post_id'           => $post_id,
                'model'             => $model,
                'prompt_tokens'     => $total_prompt_tokens,
                'completion_tokens' => $total_completion_tokens,
            ], 60);

            // 13. Log success and release lock
            CV_OAI_PLL_Logger::log($post_id, $target_lang, $model, true, '', count($payload), $new_post_id, $start_time);
            if (!$bypass_lock) {
                CV_OAI_PLL_Translation_Lock::release();
            }

            return $new_post_id;

        } catch (Exception $e) {
            // Log failure and release lock
            CV_OAI_PLL_Logger::log($post_id, $target_lang, $model, false, $e->getMessage(), 0, 0, $start_time);
            if (!$bypass_lock) {
                CV_OAI_PLL_Translation_Lock::release();
            }
            return new WP_Error('translation_processing_error', $e->getMessage());
        }
    }

    /**
     * Translates a Polylang language code to a readable name for the OpenAI prompt.
     */
    public static function get_language_name_by_code($code) {
        $langs = [
            'fi' => 'Finnish',
            'en' => 'English',
            'sv' => 'Swedish',
        ];
        if (isset($langs[$code])) {
            return $langs[$code];
        }

        if (function_exists('pll_languages_list')) {
            // Retrieve languages list from Polylang
            $pll_langs = pll_languages_list(['fields' => null]);
            if (is_array($pll_langs)) {
                foreach ($pll_langs as $l) {
                    if ($l->slug === $code) {
                        return $l->name;
                    }
                }
            }
        }

        return ucfirst($code);
    }

    /**
     * Replaces URLs in text with placeholders.
     */
    public static function replace_urls_with_placeholders($text, &$url_map) {
        $pattern = '/https?:\/\/[^\s\'"<>\(\)]+/i';
        return preg_replace_callback($pattern, function($matches) use (&$url_map) {
            $url = $matches[0];
            $clean_url = rtrim($url, '.,;:!?');
            $punctuation = substr($url, strlen($clean_url));
            
            $placeholder = array_search($clean_url, $url_map, true);
            if ($placeholder === false) {
                $index = count($url_map);
                $placeholder = 'URLPLACEHOLDER_' . $index;
                $url_map[$placeholder] = $clean_url;
            }
            return $placeholder . $punctuation;
        }, $text);
    }

    /**
     * Replaces emails in text with placeholders.
     */
    public static function replace_emails_with_placeholders($text, &$email_map) {
        $pattern = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i';
        return preg_replace_callback($pattern, function($matches) use (&$email_map) {
            $email = $matches[0];
            $placeholder = array_search($email, $email_map, true);
            if ($placeholder === false) {
                $index = count($email_map);
                $placeholder = 'EMAILPLACEHOLDER_' . $index;
                $email_map[$placeholder] = $email;
            }
            return $placeholder;
        }, $text);
    }

    /**
     * Replaces HTML tags that are not typography/basic formatting with placeholders.
     */
    public static function replace_html_tags_with_placeholders($text, &$tag_map) {
        $ignored_tags = [
            'p', 'br', 'hr', 'strong', 'em', 'span', 'b', 'i', 'ul', 'ol', 'li', 
            'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre'
        ];

        // Match any HTML tag: opening, closing, or self-closing
        $pattern = '/<\/?([a-zA-Z0-9]+)\b[^>]*>/';

        return preg_replace_callback($pattern, function($matches) use (&$tag_map, $ignored_tags) {
            $full_tag = $matches[0];
            $tag_name = strtolower($matches[1]);

            if (in_array($tag_name, $ignored_tags, true)) {
                return $full_tag;
            }

            $index = count($tag_map);
            $placeholder = 'HTMLTAGPLACEHOLDER_' . $index;
            $tag_map[$placeholder] = $full_tag;

            return $placeholder;
        }, $text);
    }

    /**
     * Restores original HTML tags from placeholders.
     */
    public static function restore_html_tags_from_placeholders($text, $tag_map) {
        if (empty($tag_map)) {
            return $text;
        }

        $pattern = '/HTMLTAGPLACEHOLDER\s*_\s*(\d+)/i';

        return preg_replace_callback($pattern, function($matches) use ($tag_map) {
            $index = $matches[1];
            $key = 'HTMLTAGPLACEHOLDER_' . $index;
            if (isset($tag_map[$key])) {
                return $tag_map[$key];
            }
            foreach ($tag_map as $k => $v) {
                if (strcasecmp($k, $key) === 0) {
                    return $v;
                }
            }
            return $matches[0];
        }, $text);
    }

    /**
     * Synchronizes and translates categories and tags from the source post to the translated post.
     */
    private static function sync_post_taxonomies($source_post_id, $translated_post_id, $target_lang, $api_key, $model) {
        $taxonomies = ['category', 'post_tag'];
        
        foreach ($taxonomies as $taxonomy) {
            $source_terms = wp_get_object_terms($source_post_id, $taxonomy);
            if (is_wp_error($source_terms) || empty($source_terms)) {
                continue;
            }
            
            $target_term_ids = [];
            
            foreach ($source_terms as $source_term) {
                $result = self::translate_term_by_id($source_term->term_id, $target_lang);
                if (!is_wp_error($result) && isset($result['term_id'])) {
                    $target_term_ids[] = (int) $result['term_id'];
                }
            }
            
            if (!empty($target_term_ids)) {
                wp_set_object_terms($translated_post_id, $target_term_ids, $taxonomy);
            }
        }
    }

    /**
     * Translates a single taxonomy term by ID and links it.
     *
     * @param int    $term_id     Source term ID.
     * @param string $target_lang Target language code.
     * @return array|WP_Error Array with 'term_id' and 'usage', or WP_Error on failure.
     */
    public static function translate_term_by_id($term_id, $target_lang, $bypass_cache = false) {
        $term = get_term($term_id);
        if (!$term || is_wp_error($term)) {
            return new WP_Error('invalid_term', __('Source term not found.', 'cv-openai-polylang-translator'));
        }

        $taxonomy = $term->taxonomy;
        
        // Check if translation already exists
        $translated_term_id = 0;
        if (function_exists('pll_get_term')) {
            $translated_term_id = pll_get_term($term_id, $target_lang);
        }

        if ($translated_term_id && !$bypass_cache) {
            return [
                'term_id' => (int) $translated_term_id,
                'usage'   => ['prompt_tokens' => 0, 'completion_tokens' => 0, 'model' => 'Cache']
            ];
        }

        $api_key = get_option('cv_oai_pll_api_key', '');
        $model   = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        if (empty($api_key)) {
            return new WP_Error('openai_missing_key', __('OpenAI API key is missing.', 'cv-openai-polylang-translator'));
        }

        $term_name = $term->name;
        $target_language_name = self::get_language_name_by_code($target_lang);

        $prompt = [
            'model'       => $model,
            'temperature' => 0.1,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => sprintf("You are a professional localization translator. Translate the following WordPress %s name from Finnish to %s. Return only the translated name, nothing else.", $taxonomy, $target_language_name),
                ],
                [
                    'role'    => 'user',
                    'content' => $term_name,
                ]
            ]
        ];

        $response = CV_OAI_PLL_OpenAI_Client::translate_payload_with_usage($prompt, $api_key);
        if (is_wp_error($response)) {
            return $response;
        }

        $translated_name = trim(strip_tags($response['content']));
        if (empty($translated_name)) {
            return new WP_Error('translation_failed', __('Translated term name is empty.', 'cv-openai-polylang-translator'));
        }

        if ($translated_term_id) {
            $updated = wp_update_term($translated_term_id, $taxonomy, ['name' => $translated_name]);
            if (is_wp_error($updated)) {
                return $updated;
            }
            $new_term_id = $translated_term_id;
        } else {
            // Create the new term in the target language
            $new_term = wp_insert_term($translated_name, $taxonomy);
            if (is_wp_error($new_term)) {
                if (isset($new_term->error_data['term_exists'])) {
                    $new_term_id = (int) $new_term->error_data['term_exists'];
                } else {
                    return $new_term;
                }
            } else {
                $new_term_id = (int) $new_term['term_id'];
            }
        }

        // Link in Polylang
        if (function_exists('pll_set_term_language')) {
            pll_set_term_language($new_term_id, $target_lang);
        }

        if (function_exists('pll_save_term_translations')) {
            $translations = [
                'fi'         => $term_id,
                $target_lang => $new_term_id,
            ];
            if (function_exists('pll_languages_list')) {
                $langs = pll_languages_list();
                foreach ($langs as $l) {
                    if ($l !== 'fi' && $l !== $target_lang) {
                        $existing = pll_get_term($term_id, $l);
                        if ($existing) {
                            $translations[$l] = $existing;
                        }
                    }
                }
            }
            pll_save_term_translations($translations);
        }

        return [
            'term_id' => $new_term_id,
            'usage'   => [
                'prompt_tokens'     => isset($response['usage']['prompt_tokens']) ? (int) $response['usage']['prompt_tokens'] : 0,
                'completion_tokens' => isset($response['usage']['completion_tokens']) ? (int) $response['usage']['completion_tokens'] : 0,
                'model'             => $model
            ]
        ];
    }

    /**
     * Translates a WordPress navigation menu and all its menu items.
     *
     * @param int    $menu_id     Source menu term ID.
     * @param string $target_lang Target language code.
     * @return int|WP_Error Target menu term ID on success, WP_Error on failure.
     */
    public static function translate_menu($menu_id, $target_lang) {
        $source_menu = wp_get_nav_menu_object($menu_id);
        if (!$source_menu) {
            return new WP_Error('invalid_menu', __('Source menu not found.', 'cv-openai-polylang-translator'));
        }

        // 1. Get/create translated menu term
        $translated_menu_id = 0;
        if (function_exists('pll_get_term')) {
            $translated_menu_id = pll_get_term($menu_id, $target_lang);
        }

        if (!$translated_menu_id) {
            // Translate menu name
            $translated_name = self::translate_text_inline($source_menu->name, $target_lang);
            if (empty($translated_name) || $translated_name === $source_menu->name) {
                $translated_name = $source_menu->name . ' (' . strtoupper($target_lang) . ')';
            }

            // Create target menu
            $new_menu = wp_create_nav_menu($translated_name);
            if (is_wp_error($new_menu)) {
                return $new_menu;
            }
            $translated_menu_id = (int) $new_menu;

            // Set language and link translation in Polylang
            if (function_exists('pll_set_term_language')) {
                pll_set_term_language($translated_menu_id, $target_lang);
            }
            if (function_exists('pll_save_term_translations')) {
                $translations = [
                    'fi'         => $menu_id,
                    $target_lang => $translated_menu_id
                ];
                if (function_exists('pll_languages_list')) {
                    $langs = pll_languages_list();
                    foreach ($langs as $l) {
                        if ($l !== 'fi' && $l !== $target_lang) {
                            $existing = pll_get_term($menu_id, $l);
                            if ($existing) {
                                $translations[$l] = $existing;
                            }
                        }
                    }
                }
                pll_save_term_translations($translations);
            }
        }

        // 2. Fetch source menu items
        $source_items = wp_get_nav_menu_items($menu_id, ['post_status' => 'any']);
        if (empty($source_items)) {
            return $translated_menu_id;
        }

        // Fetch target menu items (clear existing to prevent duplicates on re-translation)
        $target_items = wp_get_nav_menu_items($translated_menu_id, ['post_status' => 'any']);
        if (!empty($target_items)) {
            foreach ($target_items as $t_item) {
                wp_delete_post($t_item->ID, true);
            }
        }

        $item_id_map = [];

        // 3. Replicate items and translate their references
        foreach ($source_items as $item) {
            $target_object_id = $item->object_id;
            
            // Map linked posts/pages
            if ($item->type === 'post_type' && function_exists('pll_get_post')) {
                $mapped_post_id = pll_get_post($item->object_id, $target_lang);
                if ($mapped_post_id) {
                    $target_object_id = $mapped_post_id;
                }
            }
            // Map linked taxonomy terms
            elseif ($item->type === 'taxonomy' && function_exists('pll_get_term')) {
                $mapped_term_id = pll_get_term($item->object_id, $target_lang);
                if ($mapped_term_id) {
                    $target_object_id = $mapped_term_id;
                }
            }

            // Determine target parent item
            $target_parent = 0;
            if ($item->menu_item_parent && isset($item_id_map[$item->menu_item_parent])) {
                $target_parent = $item_id_map[$item->menu_item_parent];
            }

            // Translate title if custom
            $title = $item->title;
            $translated_title = self::translate_text_inline($title, $target_lang);

            // Rebuild item args
            $args = [
                'menu-item-object-id'   => $target_object_id,
                'menu-item-object'      => $item->object,
                'menu-item-parent-id'   => $target_parent,
                'menu-item-position'    => $item->menu_order,
                'menu-item-type'        => $item->type,
                'menu-item-title'       => $translated_title,
                'menu-item-url'         => $item->url,
                'menu-item-description' => $item->description,
                'menu-item-attr-title'  => $item->post_excerpt,
                'menu-item-target'      => $item->target,
                'menu-item-classes'     => implode(' ', $item->classes),
                'menu-item-xfn'         => $item->xfn,
                'menu-item-status'      => 'publish',
            ];

            $new_item_id = wp_update_nav_menu_item($translated_menu_id, 0, $args);
            if ($new_item_id && !is_wp_error($new_item_id)) {
                $item_id_map[$item->ID] = $new_item_id;
            }
        }

        return $translated_menu_id;
    }

    /**
     * Translates a small block of text inline using OpenAI (checks cache first).
     *
     * @param string $text        Source text.
     * @param string $target_lang Target language code.
     * @return string Translated text.
     */
    public static function translate_text_inline($text, $target_lang) {
        if (empty($text) || !is_string($text)) {
            return $text;
        }

        require_once dirname(__FILE__) . '/class-cv-oai-pll-db.php';
        $cached = CV_OAI_PLL_DB::get_cached_translation($text, $target_lang);
        if ($cached !== false) {
            return $cached;
        }

        $api_key = get_option('cv_oai_pll_api_key', '');
        $model   = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        if (empty($api_key)) {
            return $text;
        }

        $target_language_name = self::get_language_name_by_code($target_lang);

        $prompt = [
            'model'       => $model,
            'temperature' => 0.1,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => sprintf("You are a professional localization translator. Translate the following text from Finnish to %s. Return only the translated text, nothing else.", $target_language_name),
                ],
                [
                    'role'    => 'user',
                    'content' => $text,
                ]
            ]
        ];

        $response = CV_OAI_PLL_OpenAI_Client::translate_payload_with_usage($prompt, $api_key);
        if (is_wp_error($response)) {
            return $text;
        }

        $translated = trim(strip_tags($response['content']));
        if (!empty($translated)) {
            CV_OAI_PLL_DB::add_cached_translation($text, $target_lang, $translated);
            return $translated;
        }

        return $text;
    }
}
