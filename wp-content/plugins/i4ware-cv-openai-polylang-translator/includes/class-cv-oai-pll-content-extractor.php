<?php
/**
 * Class CV_OAI_PLL_Content_Extractor
 *
 * Handles extraction of translatable contents from WordPress posts, pages,
 * Gutenberg blocks, Classic editor content, and ACF fields. It also manages
 * chunking large contents and writing back translated values.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Content_Extractor {

    /**
     * Extracts all translatable text from a WordPress post.
     *
     * @param WP_Post $post             The source post object.
     * @param array   $options          Extraction settings (acf, blocks, caption, alt, title, excerpt).
     * @param array   $enabled_acf_fields Selected ACF field names to translate.
     * @return array Array of translatable items, where each item has keys: 'type' (text/html) and 'content' (source string).
     */
    public static function extract_translatable($post, $options, $enabled_acf_fields) {
        $translatable = [];

        // 1. Extract post title and excerpt
        if (!empty($options['title']) && !empty($post->post_title)) {
            $translatable['post_title'] = [
                'type'    => 'text',
                'content' => $post->post_title,
            ];
        }
        if (!empty($options['excerpt']) && !empty($post->post_excerpt)) {
            $translatable['post_excerpt'] = [
                'type'    => 'text',
                'content' => $post->post_excerpt,
            ];
        }

        // 2. Extract Gutenberg blocks or Classic editor content
        if (!empty($options['content']) && !empty($post->post_content)) {
            if (has_blocks($post->post_content)) {
                $blocks = parse_blocks($post->post_content);
                self::walk_blocks($blocks, $translatable, 'block', $options);
            } else {
                // Classic Editor raw HTML
                $translatable['classic_content'] = [
                    'type'    => 'classic',
                    'content' => $post->post_content,
                ];
            }
        }

        // 3. Extract ACF fields
        if (!empty($options['acf']) && function_exists('get_field_objects')) {
            $field_objects = get_field_objects($post->ID);
            if (is_array($field_objects)) {
                foreach ($field_objects as $field_key => $field_obj) {
                    self::extract_acf_fields_recursive($field_obj, $enabled_acf_fields, $translatable, 'acf', $options);
                }
            }
        }

        // 4. Extract Custom Fields / SEO fields
        $custom_fields = get_option('cv_oai_pll_custom_fields', [
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_rank_math_title',
            '_rank_math_description'
        ]);
        if (is_array($custom_fields) && !empty($custom_fields)) {
            foreach ($custom_fields as $meta_key) {
                $meta_val = get_post_meta($post->ID, $meta_key, true);
                if (is_string($meta_val) && trim($meta_val) !== '') {
                    $translatable['meta_' . $meta_key] = [
                        'type'    => 'text',
                        'content' => $meta_val,
                    ];
                }
            }
        }

        return $translatable;
    }

    /**
     * Helper: Recursively walk Gutenberg blocks to extract text attributes and innerHTML content.
     */
    private static function walk_blocks(&$blocks, &$translatable, $path, $options) {
        foreach ($blocks as $index => $block) {
            $block_path = $path . '_' . $index;
            $name       = $block['blockName'];

            // Recurse inner blocks first (columns, groups, covers, etc.)
            if (!empty($block['innerBlocks'])) {
                self::walk_blocks($block['innerBlocks'], $translatable, $block_path . '_inner', $options);
            }

            // Unnamed block usually contains Classic Editor markup or empty lines
            if (empty($name)) {
                if (!empty($block['innerHTML']) && trim($block['innerHTML']) !== '') {
                    $translatable[$block_path . '_classic'] = [
                        'type'    => 'classic',
                        'content' => $block['innerHTML'],
                    ];
                }
                continue;
            }

            switch ($name) {
                case 'core/paragraph':
                case 'core/heading':
                case 'core/quote':
                case 'core/pullquote':
                case 'core/preformatted':
                case 'core/verse':
                case 'core/list-item':
                case 'core/table':
                    if (!empty($block['innerHTML']) && trim($block['innerHTML']) !== '') {
                        $translatable[$block_path . '_content'] = [
                            'type'    => 'html',
                            'content' => $block['innerHTML'],
                        ];
                    }
                    break;

                case 'core/list':
                    // Older Gutenberg versions store list items directly in innerHTML if no innerBlocks
                    if (empty($block['innerBlocks']) && !empty($block['innerHTML']) && trim($block['innerHTML']) !== '') {
                        $translatable[$block_path . '_content'] = [
                            'type'    => 'html',
                            'content' => $block['innerHTML'],
                        ];
                    }
                    break;

                case 'core/button':
                    if (isset($block['attrs']['text']) && trim($block['attrs']['text']) !== '') {
                        $translatable[$block_path . '_attr_text'] = [
                            'type'    => 'text',
                            'content' => $block['attrs']['text'],
                        ];
                    }
                    if (!empty($block['innerHTML']) && trim($block['innerHTML']) !== '') {
                        $translatable[$block_path . '_content'] = [
                            'type'    => 'html',
                            'content' => $block['innerHTML'],
                        ];
                    }
                    break;

                case 'core/image':
                    $translate_caption = !empty($options['caption']);
                    $translate_alt     = !empty($options['alt']);

                    if ($translate_caption && isset($block['attrs']['caption']) && trim($block['attrs']['caption']) !== '') {
                        $translatable[$block_path . '_attr_caption'] = [
                            'type'    => 'text',
                            'content' => $block['attrs']['caption'],
                        ];
                    }
                    if ($translate_alt && isset($block['attrs']['alt']) && trim($block['attrs']['alt']) !== '') {
                        $translatable[$block_path . '_attr_alt'] = [
                            'type'    => 'text',
                            'content' => $block['attrs']['alt'],
                        ];
                    }
                    break;
            }
        }
    }

    /**
     * Helper: Recursively walk ACF fields to extract translatable text.
     */
    private static function extract_acf_fields_recursive($field_object, $enabled_acf_fields, &$translatable, $prefix, $options) {
        $name  = $field_object['name'];
        $type  = $field_object['type'];
        $value = isset($field_object['value']) ? $field_object['value'] : null;

        if (empty($value)) {
            return;
        }

        $is_enabled = in_array($name, $enabled_acf_fields, true);

        if ($type === 'repeater' && is_array($value)) {
            foreach ($value as $row_index => $row_value) {
                if (!is_array($row_value)) {
                    continue;
                }
                if (!empty($field_object['sub_fields'])) {
                    foreach ($field_object['sub_fields'] as $sub_field) {
                        $sub_name = $sub_field['name'];
                        if (isset($row_value[$sub_name])) {
                            $temp_sub          = $sub_field;
                            $temp_sub['value'] = $row_value[$sub_name];
                            self::extract_acf_fields_recursive(
                                $temp_sub,
                                $enabled_acf_fields,
                                $translatable,
                                $prefix . '_' . $name . '_' . $row_index,
                                $options
                            );
                        }
                    }
                }
            }
        } elseif ($type === 'group' && is_array($value)) {
            if (!empty($field_object['sub_fields'])) {
                foreach ($field_object['sub_fields'] as $sub_field) {
                    $sub_name = $sub_field['name'];
                    if (isset($value[$sub_name])) {
                        $temp_sub          = $sub_field;
                        $temp_sub['value'] = $value[$sub_name];
                        self::extract_acf_fields_recursive(
                            $temp_sub,
                            $enabled_acf_fields,
                            $translatable,
                            $prefix . '_' . $name,
                            $options
                        );
                    }
                }
            }
        } elseif ($type === 'flexible_content' && is_array($value)) {
            foreach ($value as $layout_index => $layout_value) {
                $layout_name = $layout_value['acf_fc_layout'];
                foreach ($field_object['layouts'] as $layout_def) {
                    if ($layout_def['name'] === $layout_name) {
                        foreach ($layout_def['sub_fields'] as $sub_field) {
                            $sub_name = $sub_field['name'];
                            if (isset($layout_value[$sub_name])) {
                                $temp_sub          = $sub_field;
                                $temp_sub['value'] = $layout_value[$sub_name];
                                self::extract_acf_fields_recursive(
                                    $temp_sub,
                                    $enabled_acf_fields,
                                    $translatable,
                                    $prefix . '_' . $name . '_' . $layout_index,
                                    $options
                                );
                            }
                        }
                    }
                }
            }
        } elseif ($type === 'clone') {
            if (is_array($value)) {
                if (!empty($field_object['sub_fields'])) {
                    foreach ($field_object['sub_fields'] as $sub_field) {
                        $sub_name = $sub_field['name'];
                        if (isset($value[$sub_name])) {
                            $temp_sub          = $sub_field;
                            $temp_sub['value'] = $value[$sub_name];
                            self::extract_acf_fields_recursive(
                                $temp_sub,
                                $enabled_acf_fields,
                                $translatable,
                                $prefix . '_' . $name,
                                $options
                            );
                        }
                    }
                }
            } else {
                if ($is_enabled && in_array($type, ['text', 'textarea', 'wysiwyg'], true)) {
                    $translatable[$prefix . '_' . $name] = [
                        'type'    => $type,
                        'content' => $value,
                    ];
                }
            }
        } elseif ($is_enabled) {
            if (in_array($type, ['text', 'textarea', 'wysiwyg'], true)) {
                $translatable[$prefix . '_' . $name] = [
                    'type'    => $type,
                    'content' => $value,
                ];
            } elseif ($type === 'link' && is_array($value) && !empty($value['title'])) {
                $translatable[$prefix . '_' . $name . '_title'] = [
                    'type'    => 'text',
                    'content' => $value['title'],
                ];
            } elseif ($type === 'select' && !empty($value)) {
                if (is_string($value)) {
                    $translatable[$prefix . '_' . $name] = [
                        'type'    => 'text',
                        'content' => $value,
                    ];
                }
            } elseif ($type === 'image' && !empty($options['caption']) && is_array($value) && !empty($value['caption'])) {
                $translatable[$prefix . '_' . $name . '_caption'] = [
                    'type'    => 'text',
                    'content' => $value['caption'],
                ];
            }
        }
    }

    /**
     * Splits and groups translatable items into sequential chunks.
     *
     * Items are combined into JSON-convertible chunks, where the total sum of
     * translatable text in each chunk remains below $max_char_size.
     * Large Classic editor or single HTML text fields are automatically split
     * at paragraph boundaries and assigned sub-keys.
     *
     * @param array $translatable  The extracted items.
     * @param int   $max_char_size Maximum character size of strings in one chunk request.
     * @return array List of chunks, where each chunk is an associative array of item_key => item_value.
     */
    public static function chunk_payload($translatable, $max_char_size = 2500) {
        $flat_items = [];

        // 1. Process and flatten the payload, split items that are too large
        foreach ($translatable as $key => $item) {
            $type    = $item['type'];
            $content = $item['content'];

            if (($type === 'classic' || $type === 'html') && mb_strlen($content) > $max_char_size) {
                // Split large HTML content safely after closing block tags
                $parts = preg_split('/(?<=<\/p>|<\/h[1-6]>|<\/li>|<\/blockquote>)/i', $content, -1, PREG_SPLIT_NO_EMPTY);
                if (is_array($parts) && count($parts) > 1) {
                    $current_part_index = 0;
                    $accumulated        = '';

                    foreach ($parts as $part) {
                        if (mb_strlen($accumulated . $part) > $max_char_size && $accumulated !== '') {
                            $flat_items[$key . '_split_' . $current_part_index] = $accumulated;
                            $current_part_index++;
                            $accumulated = $part;
                        } else {
                            $accumulated .= $part;
                        }
                    }
                    if ($accumulated !== '') {
                        $flat_items[$key . '_split_' . $current_part_index] = $accumulated;
                    }
                } else {
                    $flat_items[$key] = $content;
                }
            } else {
                $flat_items[$key] = $content;
            }
        }

        // 2. Distribute flat items into sequential chunks
        $chunks       = [];
        $current_chunk = [];
        $current_size  = 0;

        foreach ($flat_items as $key => $content) {
            $content_size = mb_strlen($content);

            // If the item itself exceeds $max_char_size, it goes in its own chunk
            if ($content_size > $max_char_size) {
                if (!empty($current_chunk)) {
                    $chunks[]      = $current_chunk;
                    $current_chunk = [];
                    $current_size  = 0;
                }
                $chunks[] = [$key => $content];
                continue;
            }

            if ($current_size + $content_size > $max_char_size) {
                $chunks[]      = $current_chunk;
                $current_chunk = [$key => $content];
                $current_size  = $content_size;
            } else {
                $current_chunk[$key] = $content;
                $current_size       += $content_size;
            }
        }

        if (!empty($current_chunk)) {
            $chunks[] = $current_chunk;
        }

        return $chunks;
    }

    /**
     * Converts a flat set of chunk-translated keys back into the structured WordPress content.
     *
     * It parses split keys back together, walks the Gutenberg blocks tree to inject content,
     * and compiles the final HTML and ACF structures.
     *
     * @param WP_Post $post             The source post object.
     * @param array   $translations     Flat key/value array of translated items.
     * @param array   $options          Settings used.
     * @param array   $enabled_acf_fields Enabled ACF fields.
     * @return array Elements: 'post_title', 'post_excerpt', 'post_content', and 'acf_data' (parent ACF field values).
     */
    public static function compile_translated_post($post, $translations, $options, $enabled_acf_fields, $existing_post = null) {
        // 1. Reassemble any split strings
        $reassembled = [];
        $split_groups = [];

        foreach ($translations as $key => $val) {
            if (preg_match('/^(.*)_split_(\d+)$/', $key, $matches)) {
                $base_key = $matches[1];
                $index    = (int) $matches[2];
                $split_groups[$base_key][$index] = $val;
            } else {
                $reassembled[$key] = $val;
            }
        }

        foreach ($split_groups as $base_key => $parts) {
            ksort($parts);
            $reassembled[$base_key] = implode('', $parts);
        }

        // 2. Build Title and Excerpt
        $post_title = ($existing_post) ? $existing_post->post_title : $post->post_title;
        if (!empty($options['title']) && isset($reassembled['post_title'])) {
            $post_title = wp_strip_all_tags($reassembled['post_title']);
        }

        $post_excerpt = ($existing_post) ? $existing_post->post_excerpt : $post->post_excerpt;
        if (!empty($options['excerpt']) && isset($reassembled['post_excerpt'])) {
            $post_excerpt = wp_kses_post($reassembled['post_excerpt']);
        }

        // 3. Build Content (Blocks or Classic)
        $post_content = ($existing_post) ? $existing_post->post_content : $post->post_content;
        if (!empty($options['content'])) {
            if (has_blocks($post->post_content)) {
                $blocks = parse_blocks($post->post_content);
                self::walk_blocks_write_back($blocks, $reassembled, 'block');
                $post_content = serialize_blocks($blocks);
            } else {
                if (isset($reassembled['classic_content'])) {
                    $post_content = wp_kses_post($reassembled['classic_content']);
                }
            }
        }

        // 4. Reconstruct ACF Fields
        $acf_data = [];
        if (!empty($options['acf']) && function_exists('get_field_objects')) {
            $field_objects = get_field_objects($post->ID);
            if (is_array($field_objects)) {
                foreach ($field_objects as $field_key => $field_obj) {
                    $field_name = $field_obj['name'];
                    // Load raw/original value
                    $original_value = get_field($field_name, $post->ID, false);
                    self::write_back_acf_fields_recursive($original_value, $field_obj, $reassembled, 'acf');
                    $acf_data[$field_name] = $original_value;
                }
            }
        }

        // 5. Reconstruct Custom/SEO Fields
        $meta_data = [];
        $custom_fields = get_option('cv_oai_pll_custom_fields', [
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_rank_math_title',
            '_rank_math_description'
        ]);
        if (is_array($custom_fields) && !empty($custom_fields)) {
            foreach ($custom_fields as $meta_key) {
                if (isset($reassembled['meta_' . $meta_key])) {
                    $meta_data[$meta_key] = $reassembled['meta_' . $meta_key];
                }
            }
        }

        return [
            'post_title'   => $post_title,
            'post_excerpt' => $post_excerpt,
            'post_content' => $post_content,
            'acf_data'     => $acf_data,
            'meta_data'    => $meta_data,
        ];
    }

    /**
     * Helper: Walk Gutenberg blocks and write back translations.
     */
    private static function walk_blocks_write_back(&$blocks, $translations, $path) {
        foreach ($blocks as $index => &$block) {
            $block_path = $path . '_' . $index;
            $name       = $block['blockName'];

            if (!empty($block['innerBlocks'])) {
                self::walk_blocks_write_back($block['innerBlocks'], $translations, $block_path . '_inner');
            }

            if (empty($name)) {
                if (isset($translations[$block_path . '_classic'])) {
                    $block['innerHTML'] = $translations[$block_path . '_classic'];
                    if (isset($block['innerContent'][0])) {
                        $block['innerContent'][0] = $translations[$block_path . '_classic'];
                    }
                }
                continue;
            }

            switch ($name) {
                case 'core/paragraph':
                case 'core/heading':
                case 'core/quote':
                case 'core/pullquote':
                case 'core/preformatted':
                case 'core/verse':
                case 'core/list-item':
                case 'core/list':
                case 'core/table':
                    if (isset($translations[$block_path . '_content'])) {
                        $block['innerHTML'] = $translations[$block_path . '_content'];
                        if (isset($block['innerContent'][0])) {
                            $block['innerContent'][0] = $translations[$block_path . '_content'];
                        }
                    }
                    break;

                case 'core/button':
                    if (isset($translations[$block_path . '_attr_text'])) {
                        $block['attrs']['text'] = $translations[$block_path . '_attr_text'];
                    }
                    if (isset($translations[$block_path . '_content'])) {
                        $block['innerHTML'] = $translations[$block_path . '_content'];
                        if (isset($block['innerContent'][0])) {
                            $block['innerContent'][0] = $translations[$block_path . '_content'];
                        }
                    }
                    break;

                case 'core/image':
                    if (isset($translations[$block_path . '_attr_caption'])) {
                        $block['attrs']['caption'] = $translations[$block_path . '_attr_caption'];
                    }
                    if (isset($translations[$block_path . '_attr_alt'])) {
                        $block['attrs']['alt'] = $translations[$block_path . '_attr_alt'];
                    }

                    // Update values inside innerHTML if they exist to keep frontend matched
                    if (!empty($block['innerHTML'])) {
                        if (isset($translations[$block_path . '_attr_alt']) && isset($block['attrs']['alt'])) {
                            $block['innerHTML'] = preg_replace(
                                '/alt="([^"]*)"/',
                                'alt="' . esc_attr($translations[$block_path . '_attr_alt']) . '"',
                                $block['innerHTML']
                            );
                        }
                        if (isset($translations[$block_path . '_attr_caption'])) {
                            // Find and replace the content inside <figcaption>
                            $block['innerHTML'] = preg_replace(
                                '/<figcaption[^>]*>(.*?)<\/figcaption>/is',
                                '<figcaption>' . esc_html($translations[$block_path . '_attr_caption']) . '</figcaption>',
                                $block['innerHTML']
                            );
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Helper: Walk ACF structure and write back translations.
     */
    private static function write_back_acf_fields_recursive(&$field_value, $field_object, $translations, $prefix) {
        $name = $field_object['name'];
        $type = $field_object['type'];

        if (empty($field_value)) {
            return;
        }

        if ($type === 'repeater' && is_array($field_value)) {
            foreach ($field_value as $row_index => &$row_value) {
                if (!is_array($row_value)) {
                    continue;
                }
                if (!empty($field_object['sub_fields'])) {
                    foreach ($field_object['sub_fields'] as $sub_field) {
                        $sub_name = $sub_field['name'];
                        if (isset($row_value[$sub_name])) {
                            self::write_back_acf_fields_recursive(
                                $row_value[$sub_name],
                                $sub_field,
                                $translations,
                                $prefix . '_' . $name . '_' . $row_index
                            );
                        }
                    }
                }
            }
        } elseif ($type === 'group' && is_array($field_value)) {
            if (!empty($field_object['sub_fields'])) {
                foreach ($field_object['sub_fields'] as $sub_field) {
                    $sub_name = $sub_field['name'];
                    if (isset($field_value[$sub_name])) {
                        self::write_back_acf_fields_recursive(
                            $field_value[$sub_name],
                            $sub_field,
                            $translations,
                            $prefix . '_' . $name
                        );
                    }
                }
            }
        } elseif ($type === 'flexible_content' && is_array($field_value)) {
            foreach ($field_value as $layout_index => &$layout_value) {
                $layout_name = $layout_value['acf_fc_layout'];
                foreach ($field_object['layouts'] as $layout_def) {
                    if ($layout_def['name'] === $layout_name) {
                        foreach ($layout_def['sub_fields'] as $sub_field) {
                            $sub_name = $sub_field['name'];
                            if (isset($layout_value[$sub_name])) {
                                self::write_back_acf_fields_recursive(
                                    $layout_value[$sub_name],
                                    $sub_field,
                                    $translations,
                                    $prefix . '_' . $name . '_' . $layout_index
                                );
                            }
                        }
                    }
                }
            }
        } elseif ($type === 'clone') {
            if (is_array($field_value)) {
                if (!empty($field_object['sub_fields'])) {
                    foreach ($field_object['sub_fields'] as $sub_field) {
                        $sub_name = $sub_field['name'];
                        if (isset($field_value[$sub_name])) {
                            self::write_back_acf_fields_recursive(
                                $field_value[$sub_name],
                                $sub_field,
                                $translations,
                                $prefix . '_' . $name
                            );
                        }
                    }
                }
            } else {
                if (isset($translations[$prefix . '_' . $name])) {
                    $field_value = $translations[$prefix . '_' . $name];
                }
            }
        } else {
            if (in_array($type, ['text', 'textarea', 'wysiwyg'], true)) {
                if (isset($translations[$prefix . '_' . $name])) {
                    $field_value = $translations[$prefix . '_' . $name];
                }
            } elseif ($type === 'link' && is_array($field_value)) {
                if (isset($translations[$prefix . '_' . $name . '_title'])) {
                    $field_value['title'] = $translations[$prefix . '_' . $name . '_title'];
                }
            } elseif ($type === 'select') {
                if (is_string($field_value) && isset($translations[$prefix . '_' . $name])) {
                    $field_value = $translations[$prefix . '_' . $name];
                }
            } elseif ($type === 'image' && is_array($field_value)) {
                if (isset($translations[$prefix . '_' . $name . '_caption'])) {
                    $field_value['caption'] = $translations[$prefix . '_' . $name . '_caption'];
                }
            }
        }
    }
}
