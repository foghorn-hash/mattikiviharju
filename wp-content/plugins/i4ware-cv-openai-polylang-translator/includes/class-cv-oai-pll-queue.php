<?php
/**
 * Class CV_OAI_PLL_Queue
 *
 * Handles populating the translation queue with Posts, Terms, and Polylang Strings,
 * and processes them in batches (including batching multiple strings into a single OpenAI API call).
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Queue {

    /**
     * Scans the website for Finnish content and populates the translation queue.
     *
     * @param string $target_lang The target language code (e.g. 'en', 'sv').
     * @return int Number of items added to the queue.
     */
    public static function populate_queue($target_lang, $overwrite = false) {
        if (!function_exists('pll_get_post_language') || !function_exists('pll_languages_list')) {
            return 0;
        }

        $added_count = 0;

        // 1. Scan Posts, Pages, and CPTs
        $post_types = get_option('cv_oai_pll_post_types', ['page', 'cv_experience', 'cv_education', 'cv_course', 'cv_project', 'cv_skill', 'cv_badge']);
        if (!is_array($post_types)) {
            $post_types = ['page', 'cv_experience', 'cv_education', 'cv_course', 'cv_project', 'cv_skill', 'cv_badge'];
        }

        foreach ($post_types as $post_type) {
            $posts = get_posts([
                'post_type'      => $post_type,
                'posts_per_page' => -1,
                'post_status'    => ['publish', 'draft', 'private', 'pending'],
            ]);

            foreach ($posts as $post) {
                $lang = pll_get_post_language($post->ID);
                if ($lang === 'fi') {
                    // Check if translation already exists
                    $translations = pll_get_post_translations($post->ID);
                    $existing_id = isset($translations[$target_lang]) ? (int) $translations[$target_lang] : 0;
                    if ($existing_id && !$overwrite) {
                        continue; // Skip if translation already exists and overwrite is disabled
                    }

                    $queue_id = CV_OAI_PLL_DB::add_to_queue('post', $post->ID, $target_lang);
                    if ($queue_id) {
                        $added_count++;
                    }
                }
            }
        }

        // 2. Scan Taxonomies (Categories, Tags, etc.)
        $taxonomies = get_taxonomies(['public' => true]);
        foreach ($taxonomies as $taxonomy) {
            // Check if Polylang manages this taxonomy
            if (function_exists('pll_is_translated_taxonomy') && !pll_is_translated_taxonomy($taxonomy)) {
                continue;
            }

            $terms = get_terms([
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
            ]);

            if (is_array($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    if (function_exists('pll_get_term_language')) {
                        $lang = pll_get_term_language($term->term_id);
                        if ($lang === 'fi') {
                            if (function_exists('pll_get_term')) {
                                $existing_term_id = pll_get_term($term->term_id, $target_lang);
                                if ($existing_term_id && !$overwrite) {
                                    continue; // Skip if term translation already exists and overwrite is disabled
                                }
                            }

                            $queue_id = CV_OAI_PLL_DB::add_to_queue('term', $term->term_id, $target_lang);
                            if ($queue_id) {
                                $added_count++;
                            }
                        }
                    }
                }
            }
        }

        // 3. Scan registered Polylang strings
        $added_count += self::populate_missing_strings($target_lang, $overwrite);

        // 4. Scan Menus
        $menus = wp_get_nav_menus();
        if (is_array($menus)) {
            foreach ($menus as $menu) {
                if (function_exists('pll_get_term_language')) {
                    $lang = pll_get_term_language($menu->term_id);
                    if ($lang === 'fi') {
                        if (function_exists('pll_get_term')) {
                            $existing_menu_id = pll_get_term($menu->term_id, $target_lang);
                            if ($existing_menu_id && !$overwrite) {
                                continue; // Skip if menu translation already exists and overwrite is disabled
                            }
                        }

                        $queue_id = CV_OAI_PLL_DB::add_to_queue('menu', $menu->term_id, $target_lang);
                        if ($queue_id) {
                            $added_count++;
                        }
                    }
                }
            }
        }

        return $added_count;
    }

    /**
     * Helper to retrieve all registered Polylang strings.
     *
     * @return array
     */
    public static function get_registered_strings() {
        if (class_exists('PLL_Admin_Strings')) {
            return PLL_Admin_Strings::get_strings();
        }

        global $polylang;
        if (!isset($polylang)) {
            return [];
        }
        
        if (isset($polylang->strings) && method_exists($polylang->strings, 'get_strings')) {
            return $polylang->strings->get_strings();
        } elseif (isset($polylang->model->strings) && method_exists($polylang->model->strings, 'get_strings')) {
            return $polylang->model->strings->get_strings();
        } elseif (isset($polylang->model) && method_exists($polylang->model, 'get_strings')) {
            return $polylang->model->get_strings();
        }
        
        return [];
    }

    /**
     * Scans registered Polylang strings and adds untranslated strings to the queue.
     *
     * @param string $target_lang Target language code.
     * @param bool   $overwrite   Whether to overwrite existing translations.
     * @return int Number of strings added.
     */
    public static function populate_missing_strings($target_lang, $overwrite = false) {
        global $polylang;
        if (!isset($polylang) || !isset($polylang->model)) {
            return 0;
        }

        $added_count = 0;
        
        $registered_strings = self::get_registered_strings();

        if (empty($registered_strings)) {
            return 0;
        }

        // Load the translation MO file for the target language to check translations
        require_once dirname(__FILE__) . '/class-cv-oai-pll-string-translator.php';
        $mo = CV_OAI_PLL_String_Translator::get_mo_for_language($target_lang);
        if (is_wp_error($mo)) {
            return 0;
        }

        foreach ($registered_strings as $string) {
            $name    = $string['name'];
            $context = $string['context'];
            $text    = $string['string'];

            if (empty($text)) {
                continue;
            }

            // Check if translation exists in the target MO object
            $is_translated = false;
            if (!$overwrite) {
                if (method_exists($mo, 'translate_if_any')) {
                    $translation = $mo->translate_if_any($text);
                    if ($translation !== '') {
                        $is_translated = true;
                    }
                } else {
                    if (!class_exists('Translation_Entry')) {
                        require_once ABSPATH . 'wp-includes/pomo/entry.php';
                    }
                    $search_entry = new Translation_Entry(['singular' => $text]);
                    $entry = method_exists($mo, 'translate_entry') ? $mo->translate_entry($search_entry) : null;
                    if ($entry && !empty($entry->translations) && isset($entry->translations[0]) && $entry->translations[0] !== '') {
                        $is_translated = true;
                    } else {
                        // Fallback to translate comparison if entry check isn't available
                        $translation = $mo->translate($text);
                        if (!empty($translation) && $translation !== $text) {
                            $is_translated = true;
                        }
                    }
                }
            }

            if (!$is_translated) {
                // Unique item ID for strings: md5(context ||| name ||| string)
                $item_id = md5($context . '|||' . $name . '|||' . $text);
                $queue_id = CV_OAI_PLL_DB::add_to_queue('string', $item_id, $target_lang);
                if ($queue_id) {
                    $added_count++;
                }
            }
        }

        return $added_count;
    }

    /**
     * Processes a batch of up to 10 pending translation queue items.
     *
     * @param int $batch_size Batch size limit (default 10).
     * @return array Summary of processed items.
     */
    /**
     * Processes a batch of up to 10 pending translation queue items.
     *
     * @param int  $batch_size   Batch size limit (default 10).
     * @param bool $bypass_cache Whether to ignore cached translations.
     * @return array Summary of processed items.
     */
    public static function process_batch($batch_size = 10, $bypass_cache = false) {
        require_once dirname(__FILE__) . '/class-cv-oai-pll-translation-lock.php';
        if (CV_OAI_PLL_Translation_Lock::is_locked()) {
            return [
                'total'     => 0,
                'success'   => 0,
                'failed'    => 0,
                'details'   => [
                    [
                        'success' => false,
                        'error'   => __('Another translation job is currently running on this site. Please wait a few minutes.', 'cv-openai-polylang-translator')
                    ]
                ]
            ];
        }

        CV_OAI_PLL_Translation_Lock::acquire(999999);

        try {
            $pending_items = CV_OAI_PLL_DB::get_pending_batch($batch_size);

            if (empty($pending_items)) {
                CV_OAI_PLL_Translation_Lock::release();
                return [
                    'total'     => 0,
                    'success'   => 0,
                    'failed'    => 0,
                    'details'   => []
                ];
            }

            // Smart batch slicing:
            // If the first item is not a string, only process that one item.
            // If the first item is a string, process all consecutive strings in this batch.
            $first_item = $pending_items[0];
            if ($first_item->item_type !== 'string') {
                $pending_items = [$first_item];
            } else {
                $sliced_items = [];
                foreach ($pending_items as $item) {
                    if ($item->item_type === 'string') {
                        $sliced_items[] = $item;
                    } else {
                        break; // Stop at the first non-string item
                    }
                }
                $pending_items = $sliced_items;
            }

            $results = [
                'total'     => count($pending_items),
                'success'   => 0,
                'failed'    => 0,
                'details'   => []
            ];

            // We can group strings in the batch to translate them all in one OpenAI call
            $string_items = [];
            $other_items = [];

            foreach ($pending_items as $item) {
                if ($item->item_type === 'string') {
                    $string_items[] = $item;
                } else {
                    $other_items[] = $item;
                }
            }

            // Set status to processing for all items in batch
            foreach ($pending_items as $item) {
                CV_OAI_PLL_DB::update_item_status($item->id, 'processing', '', $item->attempts + 1);
            }

            // 1. Process String Items (Optimized Batching!)
            if (!empty($string_items)) {
                // Group by target language code (though usually they are all the same target language in a cron run)
                $strings_by_lang = [];
                foreach ($string_items as $item) {
                    $strings_by_lang[$item->target_language][] = $item;
                }

                foreach ($strings_by_lang as $lang => $items) {
                    $batch_results = self::process_string_batch($items, $lang, $bypass_cache);
                    $results['success'] += $batch_results['success'];
                    $results['failed']  += $batch_results['failed'];
                    $results['details'] = array_merge($results['details'], $batch_results['details']);
                }
            }

            // 2. Process Other Items (Posts, Terms) sequentially
            if (!empty($other_items)) {
                $options = [
                    'title'   => true,
                    'excerpt' => true,
                    'content' => true,
                    'acf'     => true,
                    'caption' => true,
                    'alt'     => true,
                ];

                foreach ($other_items as $item) {
                    $item_id = $item->item_id;
                    $target_lang = $item->target_language;

                    if ($item->item_type === 'post') {
                        $post_id = (int) $item_id;
                        $post_title = get_the_title($post_id);
                        $post_name = $post_title ? $post_title : '#' . $post_id;
                        
                        $translate_res = CV_OAI_PLL_Translator::translate_post($post_id, $target_lang, $options, true, true, $bypass_cache);

                        if (is_wp_error($translate_res)) {
                            CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $translate_res->get_error_message());
                            $results['failed']++;
                            $results['details'][] = [
                                'id'              => $item->id,
                                'type'            => 'post',
                                'item_id'         => $post_id,
                                'name'            => $post_name,
                                'target_language' => $target_lang,
                                'success'         => false,
                                'error'           => $translate_res->get_error_message()
                            ];
                        } else {
                            // Success! Retrieve token usage from last logs
                            $usage = self::get_last_logged_usage($post_id, $target_lang);
                            CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, $usage);
                            $results['success']++;
                            $results['details'][] = [
                                'id'              => $item->id,
                                'type'            => 'post',
                                'item_id'         => $post_id,
                                'name'            => $post_name,
                                'target_language' => $target_lang,
                                'success'         => true,
                                'draft_id'        => $translate_res
                            ];
                        }
                    } elseif ($item->item_type === 'term') {
                        $term_id = (int) $item_id;
                        $translate_res = CV_OAI_PLL_Translator::translate_term_by_id($term_id, $target_lang, $bypass_cache);

                        $term_obj = get_term($term_id);
                        $term_name = ($term_obj && !is_wp_error($term_obj)) ? $term_obj->name : '#' . $term_id;

                        if (is_wp_error($translate_res)) {
                            CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $translate_res->get_error_message());
                            $results['failed']++;
                            $results['details'][] = [
                                'id'              => $item->id,
                                'type'            => 'term',
                                'item_id'         => $term_id,
                                'name'            => $term_name,
                                'target_language' => $target_lang,
                                'success'         => false,
                                'error'           => $translate_res->get_error_message()
                            ];
                        } else {
                            CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, $translate_res['usage']);
                            $results['success']++;
                            $results['details'][] = [
                                'id'              => $item->id,
                                'type'            => 'term',
                                'item_id'         => $term_id,
                                'name'            => $term_name,
                                'target_language' => $target_lang,
                                'success'         => true
                            ];
                        }
                    } elseif ($item->item_type === 'menu') {
                        $menu_id = (int) $item_id;
                        $translate_res = CV_OAI_PLL_Translator::translate_menu($menu_id, $target_lang);

                        $menu_obj = wp_get_nav_menu_object($menu_id);
                        $menu_name = $menu_obj ? $menu_obj->name : '#' . $menu_id;

                        if (is_wp_error($translate_res)) {
                            CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $translate_res->get_error_message());
                            $results['failed']++;
                            $results['details'][] = [
                                'id'              => $item->id,
                                'type'            => 'menu',
                                'item_id'         => $menu_id,
                                'name'            => $menu_name,
                                'target_language' => $target_lang,
                                'success'         => false,
                                'error'           => $translate_res->get_error_message()
                            ];
                        } else {
                            CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, [
                                'model'             => get_option('cv_oai_pll_model', 'gpt-4o-mini'),
                                'prompt_tokens'     => 0,
                                'completion_tokens' => 0
                            ]);
                            $results['success']++;
                            $results['details'][] = [
                                'id'              => $item->id,
                                'type'            => 'menu',
                                'item_id'         => $menu_id,
                                'name'            => $menu_name,
                                'target_language' => $target_lang,
                                'success'         => true
                            ];
                        }
                    }
                }
            }

            CV_OAI_PLL_Translation_Lock::release();
            return $results;
        } catch (\Throwable $t) {
            CV_OAI_PLL_Translation_Lock::release();
            throw $t;
        }
    }

    /**
     * Processes a batch of Polylang strings.
     * Combines all strings into a single JSON object for translation.
     *
     * @param array  $items        List of queue string items.
     * @param string $target_lang  Target language code.
     * @param bool   $bypass_cache Whether to ignore cached translations.
     * @return array Progress stats.
     */
    private static function process_string_batch($items, $target_lang, $bypass_cache = false) {
        global $polylang;
        $results = [
            'success' => 0,
            'failed'  => 0,
            'details' => []
        ];

        if (empty($items) || !isset($polylang) || !isset($polylang->model)) {
            return $results;
        }

        require_once dirname(__FILE__) . '/class-cv-oai-pll-string-translator.php';

        // 1. Map registered strings by item_id (MD5 hash of context + name + string)
        $registered_strings = self::get_registered_strings();
        $string_map = [];
        foreach ($registered_strings as $string) {
            $hash = md5($string['context'] . '|||' . $string['name'] . '|||' . $string['string']);
            $string_map[$hash] = $string;
        }

        // 2. Prepare payload of strings that need translation
        $payload_to_translate = [];
        $item_mapping = [];

        foreach ($items as $item) {
            $hash = $item->item_id;
            if (isset($string_map[$hash])) {
                $string_info = $string_map[$hash];
                // Check Cache/Translation Memory first!
                $cached = !$bypass_cache ? CV_OAI_PLL_DB::get_cached_translation($string_info['string'], $target_lang) : false;

                if ($cached !== false) {
                    // Instant success from cache!
                    $saved = CV_OAI_PLL_String_Translator::save_single_translation(
                        $string_info['string'],
                        $cached,
                        $target_lang,
                        $string_info['context']
                    );

                    if ($saved) {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, [
                            'model'             => 'Translation Memory',
                            'prompt_tokens'     => 0,
                            'completion_tokens' => 0
                        ]);
                        $results['success']++;
                        $results['details'][] = [
                            'id'              => $item->id,
                            'type'            => 'string',
                            'item_id'         => $hash,
                            'name'            => strlen($string_info['string']) > 40 ? mb_substr($string_info['string'], 0, 40) . '...' : $string_info['string'],
                            'target_language' => $target_lang,
                            'success'         => true,
                            'cached'          => true
                        ];
                    } else {
                        CV_OAI_PLL_DB::update_item_status($item->id, 'failed', __('Failed to write translation memory to database.', 'cv-openai-polylang-translator'));
                        $results['failed']++;
                        $results['details'][] = [
                            'id'              => $item->id,
                            'type'            => 'string',
                            'item_id'         => $hash,
                            'name'            => strlen($string_info['string']) > 40 ? mb_substr($string_info['string'], 0, 40) . '...' : $string_info['string'],
                            'target_language' => $target_lang,
                            'success'         => false,
                            'error'           => __('Failed to write translation memory to database.', 'cv-openai-polylang-translator')
                        ];
                    }
                } else {
                    // Not cached, translate using OpenAI
                    $payload_to_translate[$hash] = $string_info['string'];
                    $item_mapping[$hash] = $item;
                }
            } else {
                // Registered string no longer exists in Polylang!
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', __('Registered Polylang string not found.', 'cv-openai-polylang-translator'));
                $results['failed']++;
                $results['details'][] = [
                    'id'              => $item->id,
                    'type'            => 'string',
                    'item_id'         => $hash,
                    'name'            => __('Unknown String', 'cv-openai-polylang-translator'),
                    'target_language' => $target_lang,
                    'success'         => false,
                    'error'           => __('Registered Polylang string not found.', 'cv-openai-polylang-translator')
                ];
            }
        }

        if (empty($payload_to_translate)) {
            return $results;
        }

        // 3. Request translation from OpenAI
        $api_key = get_option('cv_oai_pll_api_key', '');
        $model   = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        
        $target_language_name = CV_OAI_PLL_Translator::get_language_name_by_code($target_lang);

        $prompt = [
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
                    'content' => "Translate the following JSON object containing WordPress string names and their text values from Finnish into " . $target_language_name . ". IMPORTANT: Preserve trademark brand names (i4ware, Atlassian, Jira, WordPress, OpenAI, Microsoft, SAP, ERP, SaaS, Polylang, ACF, Freshworks, Freshchat, Microsoft Teams), sprintf placeholders (%s, %d, %1\$s...), HTML tags, shortcodes, and emojis exactly. Return only the translated JSON matching the input keys:\n\n" . wp_json_encode($payload_to_translate),
                ]
            ]
        ];

        $response = CV_OAI_PLL_OpenAI_Client::translate_payload_with_usage($prompt, $api_key);

        if (is_wp_error($response)) {
            // Entire batch failed API request
            $err_msg = $response->get_error_message();
            foreach ($item_mapping as $hash => $item) {
                $string_info = isset($string_map[$hash]) ? $string_map[$hash] : null;
                $string_text = $string_info ? $string_info['string'] : __('Unknown String', 'cv-openai-polylang-translator');
                
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $err_msg);
                $results['failed']++;
                $results['details'][] = [
                    'id'              => $item->id,
                    'type'            => 'string',
                    'item_id'         => $hash,
                    'name'            => strlen($string_text) > 40 ? mb_substr($string_text, 0, 40) . '...' : $string_text,
                    'target_language' => $target_lang,
                    'success'         => false,
                    'error'           => $err_msg
                ];
            }
            return $results;
        }

        // 4. Parse and validate JSON response
        $translated_data = json_decode($response['content'], true);
        if (!is_array($translated_data)) {
            $err_msg = __('Invalid JSON structure returned by OpenAI.', 'cv-openai-polylang-translator');
            foreach ($item_mapping as $hash => $item) {
                $string_info = isset($string_map[$hash]) ? $string_map[$hash] : null;
                $string_text = $string_info ? $string_info['string'] : __('Unknown String', 'cv-openai-polylang-translator');
                
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $err_msg);
                $results['failed']++;
                $results['details'][] = [
                    'id'              => $item->id,
                    'type'            => 'string',
                    'item_id'         => $hash,
                    'name'            => strlen($string_text) > 40 ? mb_substr($string_text, 0, 40) . '...' : $string_text,
                    'target_language' => $target_lang,
                    'success'         => false,
                    'error'           => $err_msg
                ];
            }
            return $results;
        }

        // Validate individual string translations
        $validation_errs = [];
        foreach ($payload_to_translate as $hash => $source_text) {
            if (!isset($translated_data[$hash])) {
                $validation_errs[$hash] = __('Missing translated key in OpenAI response.', 'cv-openai-polylang-translator');
                continue;
            }

            // Quick validation logic matching validator rules
            $trans_val = $translated_data[$hash];
            $validation_res = CV_OAI_PLL_Validator::validate([$hash => $source_text], [$hash => $trans_val], $target_lang);
            if (is_wp_error($validation_res)) {
                $validation_errs[$hash] = $validation_res->get_error_message();
            }
        }

        // Save translations and update statuses
        $token_usage = $response['usage'];
        $num_items = count($payload_to_translate);
        
        // Distribute token usage proportionally among translated items
        $avg_usage = [
            'model'             => $model,
            'prompt_tokens'     => isset($token_usage['prompt_tokens']) ? round($token_usage['prompt_tokens'] / $num_items) : 0,
            'completion_tokens' => isset($token_usage['completion_tokens']) ? round($token_usage['completion_tokens'] / $num_items) : 0
        ];

        foreach ($payload_to_translate as $hash => $source_text) {
            $item = $item_mapping[$hash];

            if (isset($validation_errs[$hash])) {
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $validation_errs[$hash]);
                $results['failed']++;
                $results['details'][] = [
                    'id'              => $item->id,
                    'type'            => 'string',
                    'item_id'         => $hash,
                    'name'            => strlen($source_text) > 40 ? mb_substr($source_text, 0, 40) . '...' : $source_text,
                    'target_language' => $target_lang,
                    'success'         => false,
                    'error'           => $validation_errs[$hash]
                ];
                continue;
            }

            $translated_text = $translated_data[$hash];
            $string_info = isset($string_map[$hash]) ? $string_map[$hash] : null;
            $context = $string_info ? $string_info['context'] : '';
            
            // Save string translation to Polylang
            $saved = CV_OAI_PLL_String_Translator::save_single_translation($source_text, $translated_text, $target_lang, $context);

            if ($saved) {
                // Save to Cache/Translation memory
                CV_OAI_PLL_DB::add_cached_translation($source_text, $target_lang, $translated_text);

                CV_OAI_PLL_DB::update_item_status($item->id, 'completed', '', null, $avg_usage);
                $results['success']++;
                $results['details'][] = [
                    'id'              => $item->id,
                    'type'            => 'string',
                    'item_id'         => $hash,
                    'name'            => strlen($source_text) > 40 ? mb_substr($source_text, 0, 40) . '...' : $source_text,
                    'target_language' => $target_lang,
                    'success'         => true
                ];
            } else {
                $err_msg = __('Failed to write translation to database.', 'cv-openai-polylang-translator');
                CV_OAI_PLL_DB::update_item_status($item->id, 'failed', $err_msg);
                $results['failed']++;
                $results['details'][] = [
                    'id'              => $item->id,
                    'type'            => 'string',
                    'item_id'         => $hash,
                    'name'            => strlen($source_text) > 40 ? mb_substr($source_text, 0, 40) . '...' : $source_text,
                    'target_language' => $target_lang,
                    'success'         => false,
                    'error'           => $err_msg
                ];
            }
        }

        return $results;
    }

    /**
     * Helper: Helper to query token usage from the logger history of a post.
     * Falls back to zero if not logged.
     */
    private static function get_last_logged_usage($post_id, $target_lang) {
        $history = CV_OAI_PLL_Logger::get_post_history($post_id);
        $model = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        
        // Check if there is usage logged in transient or logger (we'll implement this link shortly)
        $usage = get_transient('cv_oai_pll_last_tokens_usage');
        if (is_array($usage) && isset($usage['post_id']) && (int) $usage['post_id'] === (int) $post_id) {
            delete_transient('cv_oai_pll_last_tokens_usage');
            return [
                'model'             => $usage['model'],
                'prompt_tokens'     => $usage['prompt_tokens'],
                'completion_tokens' => $usage['completion_tokens'],
            ];
        }

        return [
            'model'             => $model,
            'prompt_tokens'     => 0,
            'completion_tokens' => 0
        ];
    }
}
