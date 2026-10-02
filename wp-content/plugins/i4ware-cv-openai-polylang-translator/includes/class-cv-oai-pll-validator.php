<?php
/**
 * Class CV_OAI_PLL_Validator
 *
 * Validates translated strings against source Finnish text to prevent AI
 * hallucinations, structural breakages, and security risks.
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Validator {
    /**
     * Validates translated data array against the original source data array.
     *
     * @param array $source_data     Original flat or structured key/value array.
     * @param array $translated_data Translated key/value array returned by OpenAI.
     * @return true|WP_Error Returns true if valid, or a WP_Error describing the failure.
     */
    public static function validate($source_data, $translated_data, $target_lang = '') {
        if (!is_array($translated_data)) {
            return new WP_Error('validation_invalid_format', __('OpenAI response is not a valid JSON structure.', 'cv-openai-polylang-translator'));
        }

        foreach ($source_data as $key => $source_val) {
            // Verify the key exists in the translation response
            if (!array_key_exists($key, $translated_data)) {
                return new WP_Error(
                    'validation_missing_key',
                    sprintf(__('Validation failed: Missing key "%s" in translation response.', 'cv-openai-polylang-translator'), $key)
                );
            }

            $trans_val = $translated_data[$key];

            // If we have arrays (e.g. nested block content), recursively validate
            if (is_array($source_val)) {
                $sub_validation = self::validate($source_val, $trans_val, $target_lang);
                if (is_wp_error($sub_validation)) {
                    return $sub_validation;
                }
                continue;
            }

            // Perform checks on text content
            if (is_string($source_val) && is_string($trans_val)) {
                $source_val_clean = trim($source_val);
                $trans_val_clean  = trim($trans_val);

                // Prevent source content from being deleted without replacement
                if (!empty($source_val_clean) && empty($trans_val_clean)) {
                    return new WP_Error(
                        'validation_empty_translation',
                        sprintf(__('Validation failed: Text for "%s" was deleted without translation.', 'cv-openai-polylang-translator'), $key)
                    );
                }

                // UTF-8 correctness check
                if (!mb_check_encoding($trans_val_clean, 'UTF-8')) {
                    return new WP_Error('validation_utf8_error', __('Validation failed: Translation contains invalid UTF-8 encoding.', 'cv-openai-polylang-translator'));
                }

                // Truncation check
                if (mb_strlen($source_val_clean) > 80 && mb_strlen($trans_val_clean) < (mb_strlen($source_val_clean) * 0.15)) {
                    return new WP_Error('validation_truncated_text', __('Validation failed: Translated text appears to be severely truncated.', 'cv-openai-polylang-translator'));
                }

                // Security check: No script or iframe added unless present in source
                if (stripos($trans_val_clean, '<script') !== false && stripos($source_val_clean, '<script') === false) {
                    return new WP_Error('validation_security_script', __('Security check failed: The translation introduced an unauthorized <script> tag.', 'cv-openai-polylang-translator'));
                }
                if (stripos($trans_val_clean, '<iframe') !== false && stripos($source_val_clean, '<iframe') === false) {
                    return new WP_Error('validation_security_iframe', __('Security check failed: The translation introduced an unauthorized <iframe> tag.', 'cv-openai-polylang-translator'));
                }

                // HTML Tag Balance/Integrity check
                if (!self::check_html_integrity($source_val_clean, $trans_val_clean)) {
                    return new WP_Error(
                        'validation_html_imbalance',
                        sprintf(
                            __('Validation failed: Mismatched or unclosed HTML tags in translation. Source tags: %s. Translation tags: %s.', 'cv-openai-polylang-translator'),
                            implode(', ', self::extract_html_tags($source_val_clean)),
                            implode(', ', self::extract_html_tags($trans_val_clean))
                        )
                    );
                }

                // Extract and verify URL placeholders
                preg_match_all('/URLPLACEHOLDER_\d+/i', $source_val_clean, $source_url_pls);
                preg_match_all('/URLPLACEHOLDER_\d+/i', $trans_val_clean, $trans_url_pls);
                if (is_array($source_url_pls) && !empty($source_url_pls[0])) {
                    foreach ($source_url_pls[0] as $pl) {
                        $found = false;
                        foreach ($trans_url_pls[0] as $tpl) {
                            if (strcasecmp($tpl, $pl) === 0) {
                                $found = true;
                               break;
                            }
                        }
                        if (!$found) {
                            return new WP_Error(
                                'validation_missing_url',
                                sprintf(__('Validation failed: URL was missing or altered in translation.', 'cv-openai-polylang-translator'))
                            );
                        }
                    }
                }

                // Extract and verify Email placeholders
                preg_match_all('/EMAILPLACEHOLDER_\d+/i', $source_val_clean, $source_email_pls);
                preg_match_all('/EMAILPLACEHOLDER_\d+/i', $trans_val_clean, $trans_email_pls);
                if (is_array($source_email_pls) && !empty($source_email_pls[0])) {
                    foreach ($source_email_pls[0] as $pl) {
                        $found = false;
                        foreach ($trans_email_pls[0] as $tpl) {
                            if (strcasecmp($tpl, $pl) === 0) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            return new WP_Error(
                                'validation_missing_email',
                                sprintf(__('Validation failed: Email address was missing or altered in translation.', 'cv-openai-polylang-translator'))
                            );
                        }
                    }
                }

                // Sprintf placeholders check
                $source_sprintf = self::extract_sprintf_placeholders($source_val_clean);
                $trans_sprintf  = self::extract_sprintf_placeholders($trans_val_clean);
                sort($source_sprintf);
                sort($trans_sprintf);
                if ($source_sprintf !== $trans_sprintf) {
                    return new WP_Error(
                        'validation_sprintf_mismatch',
                        __('Validation failed: sprintf placeholders (e.g. %s, %d, %1$s) do not match the source text.', 'cv-openai-polylang-translator')
                    );
                }

                // Curly and Colon variables check (e.g. {{name}}, {count}, :attribute)
                $source_vars = self::extract_variables($source_val_clean);
                $trans_vars  = self::extract_variables($trans_val_clean);
                sort($source_vars);
                sort($trans_vars);
                if ($source_vars !== $trans_vars) {
                    return new WP_Error(
                        'validation_variables_mismatch',
                        __('Validation failed: dynamic variable placeholders (e.g. {{name}}, {count}, :attribute) do not match the source text.', 'cv-openai-polylang-translator')
                    );
                }

                // Extract and verify Phone numbers
                $source_phones = self::extract_phone_numbers($source_val_clean);
                $trans_phones  = self::extract_phone_numbers($trans_val_clean);
                foreach ($source_phones as $phone) {
                    if (!in_array($phone, $trans_phones, true)) {
                        return new WP_Error(
                            'validation_missing_phone',
                            sprintf(__('Validation failed: Phone number "%s" was missing or altered in translation.', 'cv-openai-polylang-translator'), $phone)
                        );
                    }
                }

                // Check critical B2B terms and product names (must remain in Latin)
                $product_names = [
                    'i4ware'                 => ['i4ware', 'i4ware Software', 'i4ware®'],
                    'i4ware Software'        => ['i4ware Software', 'i4ware', 'i4ware®'],
                    'i4ware®'                => ['i4ware®', 'i4ware', 'i4ware Software'],
                    'Timesheet for Jira'     => ['Timesheet for Jira', 'Timesheet'],
                    'Jira'                   => ['Jira'],
                    'Atlassian'              => ['Atlassian'],
                    'OpenAI'                 => ['OpenAI'],
                    'Microsoft'              => ['Microsoft'],
                    'SAP'                    => ['SAP'],
                    'WordPress'              => ['WordPress'],
                    'Polylang'               => ['Polylang'],
                    'Advanced Custom Fields' => ['Advanced Custom Fields', 'ACF'],
                    'ACF'                    => ['ACF', 'Advanced Custom Fields'],
                    'Freshworks'             => ['Freshworks'],
                    'Freshchat'              => ['Freshchat'],
                    'Microsoft Teams'        => ['Microsoft Teams', 'Teams']
                ];
                foreach ($product_names as $product => $synonyms) {
                    $pattern = '/(?<![a-zA-Z0-9])' . preg_quote($product, '/') . '(?![a-zA-Z0-9])/i';
                    if (preg_match($pattern, $source_val_clean)) {
                        $matched_translation = false;
                        foreach ($synonyms as $syn) {
                            $syn_pattern = '/(?<![a-zA-Z0-9])' . preg_quote($syn, '/') . '(?![a-zA-Z0-9])/i';
                            if (preg_match($syn_pattern, $trans_val_clean)) {
                                $matched_translation = true;
                                break;
                            }
                        }
                        if (!$matched_translation) {
                            return new WP_Error(
                                'validation_missing_product',
                                sprintf(__('Validation failed: Product name or trademark "%s" was missing or altered in translation.', 'cv-openai-polylang-translator'), $product)
                            );
                        }
                    }
                }

                // Extract and verify shortcodes
                $source_shortcodes = self::extract_shortcodes($source_val_clean);
                $trans_shortcodes  = self::extract_shortcodes($trans_val_clean);
                foreach ($trans_shortcodes as $sc) {
                    if (!in_array($sc, $source_shortcodes, true)) {
                        return new WP_Error(
                            'validation_new_shortcode',
                            sprintf(__('Validation failed: Unexpected shortcode "[%s]" was introduced in translation.', 'cv-openai-polylang-translator'), $sc)
                        );
                    }
                }            }
        }

        return true;
    }

    /**
     * Checks if HTML tags are balanced and correctly closed in a text string.
     *
     * @param string $text HTML input.
     * @return bool True if balanced/valid, false otherwise.
     */
    /**
     * Checks if HTML tag structure in translated text is balanced and has no unauthorized tags.
     *
     * @param string $source_text Original source HTML.
     * @param string $trans_text  Translated HTML.
     * @return bool True if tag structure is valid, false otherwise.
     */
    private static function check_html_integrity($source_text, $trans_text) {
        $source_tags = self::extract_html_tags($source_text);
        $trans_tags  = self::extract_html_tags($trans_text);

        // 1. Verify that the translation has balanced HTML tags.
        if (!self::is_tags_balanced($trans_tags)) {
            return false;
        }

        // 2. Prevent the translation from introducing any "new/unauthorized" HTML tags
        // that were not present in the source text.
        $source_tag_types = [];
        foreach ($source_tags as $tag) {
            $tag_name = ltrim($tag, '/');
            $source_tag_types[$tag_name] = true;
        }

        foreach ($trans_tags as $tag) {
            $tag_name = ltrim($tag, '/');
            if (!isset($source_tag_types[$tag_name])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if a sequence of extracted HTML tags is balanced.
     *
     * @param array $tags List of tags.
     * @return bool True if balanced, false otherwise.
     */
    private static function is_tags_balanced($tags) {
        $stack = [];
        $self_closing_tags = ['img', 'br', 'hr', 'input', 'link', 'meta', 'source', 'embed'];

        foreach ($tags as $tag) {
            if (in_array($tag, $self_closing_tags, true)) {
                continue;
            }
            if (strpos($tag, '/') === 0) {
                $tag_name = substr($tag, 1);
                if (empty($stack)) {
                    return false;
                }
                $top = array_pop($stack);
                if ($top !== $tag_name) {
                    return false;
                }
            } else {
                $stack[] = $tag;
            }
        }

        return empty($stack);
    }

    /**
     * Extracts a list of HTML tags in sequence to compare source and translation structure.
     *
     * @param string $text HTML input.
     * @return array List of tags.
     */
    private static function extract_html_tags($text) {
        preg_match_all('/<\/?([a-zA-Z0-9]+)\b[^>]*>/', $text, $matches);
        
        $tags = [];
        // Typography and basic formatting tags to ignore
        $ignored_tags = [
            'p', 'br', 'hr', 'strong', 'em', 'span', 'b', 'i', 'ul', 'ol', 'li', 
            'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre'
        ];
        
        if (is_array($matches) && !empty($matches[0])) {
            foreach ($matches[0] as $match) {
                $is_closing = (strpos($match, '</') === 0);
                
                preg_match('/<\/?([a-zA-Z0-9]+)/', $match, $name_match);
                $tag_name = isset($name_match[1]) ? strtolower($name_match[1]) : '';
                
                if ($tag_name && !in_array($tag_name, $ignored_tags, true)) {
                    $is_self_closing = (substr($match, -2) === '/>') || in_array($tag_name, ['img', 'br', 'hr', 'input', 'link', 'meta', 'source', 'embed'], true);
                    
                    if ($is_self_closing) {
                        $tags[] = $tag_name;
                    } else {
                        $tags[] = ($is_closing ? '/' : '') . $tag_name;
                    }
                }
            }
        }
        return $tags;
    }

    /**
     * Extracts sprintf placeholders from text.
     *
     * @param string $text Source text.
     * @return array
     */
    private static function extract_sprintf_placeholders($text) {
        preg_match_all('/%(?:\d+\$)?[-+0-9#\.]*[a-zA-Z%]/', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? $matches[0] : [];
    }

    /**
     * Extracts template variables ({{name}}, {count}, :attribute).
     *
     * @param string $text Source text.
     * @return array
     */
    private static function extract_variables($text) {
        $variables = [];
        // Match {{name}} and {count}
        preg_match_all('/\{\{[a-zA-Z0-9_\-]+\}\}|\{[a-zA-Z0-9_\-]+\}/', $text, $matches_curly);
        if (is_array($matches_curly) && !empty($matches_curly[0])) {
            $variables = array_merge($variables, $matches_curly[0]);
        }
        // Match :attribute
        preg_match_all('/(?<![a-zA-Z0-9_]):[a-zA-Z_][a-zA-Z0-9_\-]*/', $text, $matches_colon);
        if (is_array($matches_colon) && !empty($matches_colon[0])) {
            $variables = array_merge($variables, $matches_colon[0]);
        }
        return $variables;
    }

    /**
     * Helper: Extract URLs from text.
     */
    private static function extract_urls($text) {
        preg_match_all('/https?:\/\/[^\s\'"<>\(\)]+/i', $text, $matches);
        if (is_array($matches) && !empty($matches[0])) {
            $urls = [];
            foreach ($matches[0] as $url) {
                $urls[] = rtrim($url, '.,;:!?');
            }
            return array_unique($urls);
        }
        return [];
    }

    /**
     * Helper: Extract email addresses from text.
     */
    private static function extract_emails($text) {
        preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? array_unique($matches[0]) : [];
    }

    /**
     * Helper: Extract phone numbers from text.
     */
    private static function extract_phone_numbers($text) {
        // Matches typical phone formats starting with + (international) or 0 (local Finnish), e.g. +358 40 123 4567 or 040-1234567.
        // Prevents matching generic numbers with spaces like "300 000" or dates like "2026-07-26".
        preg_match_all('/(?:\+[0-9\s\-]{6,15}|0[0-9\s\-]{5,12})[0-9]/', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? array_unique(array_map('trim', $matches[0])) : [];
    }

    /**
     * Helper: Extract numbers (integers and decimals) from text.
     * Ignores single digits (0-9) to allow natural translations to words (like "one").
     */
    private static function extract_numbers($text) {
        preg_match_all('/\b(?:\d{2,}|\d+[\.,]\d+)\b/', $text, $matches);
        return is_array($matches) && !empty($matches[0]) ? array_unique($matches[0]) : [];
    }

    /**
     * Helper: Extract shortcode names (e.g. 'gallery', 'contact-form') from text.
     */
    private static function extract_shortcodes($text) {
        preg_match_all('/\[([a-zA-Z0-9_\-]+)/', $text, $matches);
        return is_array($matches) && !empty($matches[1]) ? array_unique($matches[1]) : [];
    }
}
