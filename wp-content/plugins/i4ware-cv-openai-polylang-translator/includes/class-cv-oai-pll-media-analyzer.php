<?php
/**
 * Class CV_OAI_PLL_Media_Analyzer
 *
 * Handles scanning, resizing, encoding, and submitting images to the OpenAI Vision API.
 * Also handles creating and updating Polylang translations for attachments.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Media_Analyzer {

    /**
     * Set up hooks.
     */
    public static function init() {
        // Media Library column hooks
        add_filter('manage_media_columns', [__CLASS__, 'add_media_column']);
        add_action('manage_media_custom_column', [__CLASS__, 'render_media_column'], 10, 2);

        // AJAX handlers
        add_action('wp_ajax_cv_oai_pll_get_media_list', [__CLASS__, 'ajax_get_media_list']);
        add_action('wp_ajax_cv_oai_pll_analyze_media', [__CLASS__, 'ajax_analyze_media']);
    }

    /**
     * Adds a column for AI Metadata status to the Media Library list view.
     */
    public static function add_media_column($columns) {
        $columns['cv_oai_pll_ai_meta'] = esc_html__('AI Alt & Meta (Fi/En/Sv)', 'cv-openai-polylang-translator');
        return $columns;
    }

    /**
     * Renders status dots and quick analyze button in the media library list table.
     */
    public static function render_media_column($column_name, $attachment_id) {
        if ($column_name !== 'cv_oai_pll_ai_meta') {
            return;
        }

        $mime_type = get_post_mime_type($attachment_id);
        if (strpos($mime_type, 'image/') !== 0) {
            echo '<span class="description">' . esc_html__('Not an image', 'cv-openai-polylang-translator') . '</span>';
            return;
        }

        $status = self::get_translation_status($attachment_id);

        echo '<div class="cv-oai-media-status-container" style="display:flex; align-items:center; gap:10px;">';
        
        // Status dots
        echo '<div style="display:flex; gap:6px;">';
        foreach (['fi', 'en', 'sv'] as $lang) {
            $has_meta = !empty($status[$lang]['alt']) && !empty($status[$lang]['title']);
            $color = $has_meta ? '#46b450' : '#dc3232';
            $title_attr = sprintf(
                esc_html__('%s: %s', 'cv-openai-polylang-translator'),
                strtoupper($lang),
                $has_meta ? esc_html__('Alt and Title present', 'cv-openai-polylang-translator') : esc_html__('Missing Alt or Title', 'cv-openai-polylang-translator')
            );
            echo '<span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:' . esc_attr($color) . ';" title="' . esc_attr($title_attr) . '"></span>';
        }
        echo '</div>';

        // Action button
        $nonce = wp_create_nonce('cv_oai_pll_media_nonce');
        echo '<button type="button" class="button button-small cv-oai-pll-quick-analyze-btn" ';
        echo 'data-id="' . (int) $attachment_id . '" ';
        echo 'data-nonce="' . esc_attr($nonce) . '">';
        echo esc_html__('AI Analyze', 'cv-openai-polylang-translator');
        echo '</button>';
        echo '<span class="spinner" style="float:none; margin:0; vertical-align:middle;"></span>';
        echo '</div>';
    }

    /**
     * Returns meta presence status for fi, en, and ar translations.
     */
    public static function get_translation_status($attachment_id) {
        $langs = ['fi', 'en', 'sv'];
        $result = [];

        // Check if Polylang is active and translates attachments
        if (function_exists('pll_get_post_translations')) {
            $translations = pll_get_post_translations($attachment_id);
            foreach ($langs as $lang) {
                $tr_id = isset($translations[$lang]) ? $translations[$lang] : 0;
                if ($tr_id) {
                    $post = get_post($tr_id);
                    $alt = get_post_meta($tr_id, '_wp_attachment_image_alt', true);
                    $result[$lang] = [
                        'id'          => $tr_id,
                        'alt'         => !empty($alt),
                        'title'       => $post ? !empty($post->post_title) : false,
                        'caption'     => $post ? !empty($post->post_excerpt) : false,
                        'description' => $post ? !empty($post->post_content) : false,
                    ];
                } else {
                    $result[$lang] = [
                        'id'          => 0,
                        'alt'         => false,
                        'title'       => false,
                        'caption'     => false,
                        'description' => false,
                    ];
                }
            }
        } else {
            // Polylang is not translating attachments. Fall back to local language only
            $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            $post = get_post($attachment_id);
            $lang = 'fi'; // Default
            if (function_exists('pll_get_post_language')) {
                $lang = pll_get_post_language($attachment_id) ?: 'fi';
            }
            foreach ($langs as $l) {
                if ($l === $lang) {
                    $result[$l] = [
                        'id'          => $attachment_id,
                        'alt'         => !empty($alt),
                        'title'       => $post ? !empty($post->post_title) : false,
                        'caption'     => $post ? !empty($post->post_excerpt) : false,
                        'description' => $post ? !empty($post->post_content) : false,
                    ];
                } else {
                    $result[$l] = [
                        'id'          => 0,
                        'alt'         => false,
                        'title'       => false,
                        'caption'     => false,
                        'description' => false,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * AJAX handler to query list of attachments.
     */
    public static function ajax_get_media_list() {
        check_ajax_referer('cv_oai_pll_queue_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'cv-openai-polylang-translator')]);
        }

        $paged = isset($_POST['paged']) ? max(1, (int) $_POST['paged']) : 1;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $filter_status = isset($_POST['filter_status']) ? sanitize_key($_POST['filter_status']) : 'all';

        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => 15,
            'paged'          => $paged,
            's'              => $search,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // If Polylang is active, let's only scan the primary language (fi) to avoid listing duplicates.
        if (function_exists('pll_get_post_translations')) {
            $args['lang'] = 'fi';
        }

        $query = new WP_Query($args);
        $items = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $id = get_the_ID();
                $status = self::get_translation_status($id);

                // Check filter criteria
                $has_all = !empty($status['fi']['alt']) && !empty($status['en']['alt']) && !empty($status['sv']['alt']) &&
                           !empty($status['fi']['title']) && !empty($status['en']['title']) && !empty($status['sv']['title']);
                
                if ($filter_status === 'missing' && $has_all) {
                    continue;
                }
                if ($filter_status === 'completed' && !$has_all) {
                    continue;
                }

                $thumb_url = wp_get_attachment_image_url($id, 'thumbnail');
                $filename = basename(get_attached_file($id));

                $items[] = [
                    'id'        => $id,
                    'title'     => get_the_title(),
                    'filename'  => $filename,
                    'thumb'     => $thumb_url ? $thumb_url : '',
                    'status'    => $status,
                ];
            }
            wp_reset_postdata();
        }

        wp_send_json_success([
            'items'       => $items,
            'total_pages' => $query->max_num_pages,
            'current'     => $paged,
        ]);
    }

    /**
     * AJAX handler to analyze a single attachment.
     */
    public static function ajax_analyze_media() {
        if (!isset($_POST['id'])) {
            wp_send_json_error(['message' => __('Missing media ID.', 'cv-openai-polylang-translator')]);
        }

        $attachment_id = (int) $_POST['id'];
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';

        // Verification
        if (!wp_verify_nonce($nonce, 'cv_oai_pll_media_nonce') && !wp_verify_nonce($nonce, 'cv_oai_pll_queue_nonce')) {
            wp_send_json_error(['message' => __('Nonce validation failed.', 'cv-openai-polylang-translator')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'cv-openai-polylang-translator')]);
        }

        $result = self::analyze_and_save_metadata($attachment_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success($result);
    }

    /**
     * Triggers visual analysis and saves translation attachments and metadata.
     */
    public static function analyze_and_save_metadata($attachment_id) {
        $api_key = get_option('cv_oai_pll_api_key', '');
        if (empty($api_key)) {
            return new WP_Error('missing_key', __('OpenAI API Key is not configured.', 'cv-openai-polylang-translator'));
        }

        $file_path = get_attached_file($attachment_id);
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Attachment file does not exist on disk.', 'cv-openai-polylang-translator'));
        }

        $mime_type = get_post_mime_type($attachment_id);
        if (strpos($mime_type, 'image/') !== 0) {
            return new WP_Error('invalid_mime', __('Selected file is not an image.', 'cv-openai-polylang-translator'));
        }

        // Downscale and read base64 image data
        $image_data = self::downscale_image_to_buffer($file_path);
        if (is_wp_error($image_data)) {
            return $image_data;
        }

        $base64 = base64_encode($image_data);
        $image_url_payload = 'data:' . $mime_type . ';base64,' . $base64;

        // Build OpenAI Chat Completions payload
        $system_prompt = 
            "You are a professional WordPress localization, SEO and image accessibility expert.\n\n" .
            "Analyze the provided image and generate metadata for three languages: Finnish (fi), English (en), and Swedish (sv).\n\n" .
            "For each language, generate exactly these fields:\n" .
            "1. \"title\": A short, descriptive, SEO-optimized title for the image (3-6 words).\n" .
            "2. \"alt\": A descriptive alternative text focusing on accessibility and screen readers (10-15 words).\n" .
            "3. \"caption\": A brief user-facing caption summarizing the image context (6-12 words).\n" .
            "4. \"description\": A detailed description explaining the visual details, style, and context of the image (20-30 words).\n\n" .
            "Language Guidelines:\n" .
            "- For 'fi', write in natural and standard Finnish.\n" .
            "- For 'en', write in professional English.\n" .
            "- For 'sv', write in professional Modern Standard Swedish suitable for B2B.\n\n" .
            "Important Constraints:\n" .
            "- Brand names and trademarks (such as i4ware, i4ware Software, Timesheet for Jira, Jira, Atlassian, Microsoft, WordPress, Polylang) must remain in Latin/English characters and not be translated or transliterated.\n" .
            "- Return ONLY a valid JSON object matching this schema:\n" .
            "{\n" .
            "  \"fi\": {\n" .
            "    \"title\": \"...\",\n" .
            "    \"alt\": \"...\",\n" .
            "    \"caption\": \"...\",\n" .
            "    \"description\": \"...\"\n" .
            "  },\n" .
            "  \"en\": {\n" .
            "    \"title\": \"...\",\n" .
            "    \"alt\": \"...\",\n" .
            "    \"caption\": \"...\",\n" .
            "    \"description\": \"...\"\n" .
            "  },\n" .
            "  \"ar\": {\n" .
            "    \"title\": \"...\",\n" .
            "    \"alt\": \"...\",\n" .
            "    \"caption\": \"...\",\n" .
            "    \"description\": \"...\"\n" .
            "  }\n" .
            "}";

        $payload = [
            'model' => 'gpt-4o-mini', // Vision-capable cost-effective model
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $system_prompt,
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analyze this image and output the requested JSON object.'
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => $image_url_payload,
                            ],
                        ],
                    ],
                ],
            ],
            'response_format' => [
                'type' => 'json_object',
            ],
            'temperature' => 0.2,
        ];

        // Call the API client
        $response = CV_OAI_PLL_OpenAI_Client::translate_payload_with_usage($payload, $api_key);
        if (is_wp_error($response)) {
            return $response;
        }

        $decoded_json = json_decode($response['content'], true);
        if (!is_array($decoded_json) || empty($decoded_json['fi']) || empty($decoded_json['en']) || empty($decoded_json['sv'])) {
            return new WP_Error('openai_vision_invalid', __('OpenAI response was not structured with fi, en, and sv keys.', 'cv-openai-polylang-translator'));
        }

        // Apply metadata to target attachments
        $translations = self::get_or_create_translations_mapping($attachment_id);

        foreach (['fi', 'en', 'sv'] as $lang) {
            $lang_data = $decoded_json[$lang];
            $target_attachment_id = isset($translations[$lang]) ? $translations[$lang] : 0;

            if ($target_attachment_id) {
                // Update attachment main post details
                wp_update_post([
                    'ID'           => $target_attachment_id,
                    'post_title'   => sanitize_text_field($lang_data['title']),
                    'post_excerpt' => sanitize_text_field($lang_data['caption']),
                    'post_content' => sanitize_textarea_field($lang_data['description']),
                ]);

                // Update alt text
                update_post_meta($target_attachment_id, '_wp_attachment_image_alt', sanitize_text_field($lang_data['alt']));

                // Flag as analyzed
                update_post_meta($target_attachment_id, '_cv_oai_pll_ai_analyzed', current_time('mysql'));
            }
        }

        return [
            'success' => true,
            'tokens'  => $response['usage']['total_tokens'],
            'cost'    => self::calculate_cost($response['usage']),
            'data'    => $decoded_json
        ];
    }

    /**
     * Resolves the list of translation attachments or creates them if missing.
     */
    private static function get_or_create_translations_mapping($attachment_id) {
        $langs = ['fi', 'en', 'sv'];

        if (!function_exists('pll_get_post_translations') || !function_exists('pll_save_post_translations')) {
            // Polylang media translation not active, just return original ID
            $lang = 'fi';
            if (function_exists('pll_get_post_language')) {
                $lang = pll_get_post_language($attachment_id) ?: 'fi';
            }
            return [$lang => $attachment_id];
        }

        // Retrieve existing translations mapping
        $translations = pll_get_post_translations($attachment_id);
        
        // If empty, initialize mapping with current attachment language
        if (empty($translations)) {
            $curr_lang = pll_get_post_language($attachment_id) ?: 'fi';
            $translations = [$curr_lang => $attachment_id];
        }

        $orig_lang = pll_get_post_language($attachment_id) ?: 'fi';
        $orig_id = isset($translations[$orig_lang]) ? $translations[$orig_lang] : $attachment_id;

        $orig_post = get_post($orig_id);
        if (!$orig_post) {
            return $translations;
        }

        $mapping_changed = false;

        foreach ($langs as $lang) {
            if (!isset($translations[$lang]) || !$translations[$lang]) {
                // Duplicate attachment for this target language
                $new_post_data = [
                    'post_title'     => $orig_post->post_title,
                    'post_name'      => $orig_post->post_name . '-' . $lang,
                    'post_mime_type' => $orig_post->post_mime_type,
                    'guid'           => $orig_post->guid,
                    'post_status'    => 'inherit',
                    'post_type'      => 'attachment',
                    'post_content'   => '',
                    'post_excerpt'   => '',
                ];

                $new_id = wp_insert_post($new_post_data);
                if (!is_wp_error($new_id) && $new_id) {
                    // Copy metadata
                    $attached_file = get_post_meta($orig_id, '_wp_attached_file', true);
                    if ($attached_file) {
                        update_post_meta($new_id, '_wp_attached_file', $attached_file);
                    }
                    $attachment_metadata = get_post_meta($orig_id, '_wp_attachment_metadata', true);
                    if ($attachment_metadata) {
                        update_post_meta($new_id, '_wp_attachment_metadata', $attachment_metadata);
                    }

                    // Set language
                    pll_set_post_language($new_id, $lang);

                    // Add to mapping
                    $translations[$lang] = $new_id;
                    $mapping_changed = true;
                }
            }
        }

        if ($mapping_changed) {
            pll_save_post_translations($translations);
        }

        return $translations;
    }

    /**
     * Downscales the image file to max 800px width/height and returns the file buffer.
     */
    private static function downscale_image_to_buffer($file_path) {
        $editor = wp_get_image_editor($file_path);
        if (is_wp_error($editor)) {
            // Fallback: if WordPress editor fails, read the original file.
            return file_get_contents($file_path);
        }

        // Resize image to max 800px on either axis, keeping aspect ratio
        $editor->resize(800, 800, false);

        $temp_dir = get_temp_dir();
        $ext = pathinfo($file_path, PATHINFO_EXTENSION);
        $temp_filepath = $temp_dir . 'cv_oai_vision_' . uniqid() . '.' . $ext;

        $save_result = $editor->save($temp_filepath);
        if (is_wp_error($save_result)) {
            return file_get_contents($file_path);
        }

        $image_data = file_get_contents($temp_filepath);
        unlink($temp_filepath);

        return $image_data;
    }

    /**
     * Calculates cost based on GPT-4o-mini rates.
     */
    private static function calculate_cost($usage) {
        // GPT-4o-mini costs as of recent pricing (Input: $0.150 / 1M tokens, Output: $0.600 / 1M tokens)
        $input_cost = ($usage['prompt_tokens'] / 1000000) * 0.150;
        $output_cost = ($usage['completion_tokens'] / 1000000) * 0.600;
        return round($input_cost + $output_cost, 4);
    }
}

