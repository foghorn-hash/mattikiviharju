<?php
/**
 * Plugin Name: i4ware AI SEO Agent
 * Description: An AI-powered SEO agent that analyzes post content and generates optimized SEO title, meta description, keyphrases, and actionable recommendations.
 * Version: 1.0.0
 * Author: Antigravity AI
 * Text Domain: i4ware-ai-seo-agent
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register settings and admin hooks
add_action('admin_init', 'i4ware_ai_seo_register_settings');
add_action('admin_menu', 'i4ware_ai_seo_add_menu');
add_action('add_meta_boxes', 'i4ware_ai_seo_add_meta_box');
add_action('admin_enqueue_scripts', 'i4ware_ai_seo_enqueue_assets');

// AJAX handlers
add_action('wp_ajax_i4ware_ai_seo_analyze', 'i4ware_ai_seo_ajax_analyze');
add_action('wp_ajax_i4ware_ai_seo_save', 'i4ware_ai_seo_ajax_save');
add_action('wp_ajax_i4ware_ai_seo_bulk_list', 'i4ware_ai_seo_ajax_bulk_list');

// Frontend Meta output filters
add_action('wp_head', 'i4ware_ai_seo_render_meta_tags');
add_filter('document_title_parts', 'i4ware_ai_seo_filter_title', 999);

/**
 * Register settings
 */
function i4ware_ai_seo_register_settings() {
    register_setting('i4ware_ai_seo_group', 'i4ware_ai_seo_api_key');
    register_setting('i4ware_ai_seo_group', 'i4ware_ai_seo_model');
    register_setting('i4ware_ai_seo_group', 'i4ware_ai_seo_tone');
}

/**
 * Register options menu
 */
function i4ware_ai_seo_add_menu() {
    add_options_page(
        __('AI SEO Agent Dashboard', 'i4ware-ai-seo-agent'),
        __('AI SEO Agent', 'i4ware-ai-seo-agent'),
        'manage_options',
        'i4ware-ai-seo-agent',
        'i4ware_ai_seo_render_dashboard'
    );
}

/**
 * Render admin settings page with Tabs
 */
function i4ware_ai_seo_render_dashboard() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';

    $api_key = get_option('i4ware_ai_seo_api_key', '');
    $model   = get_option('i4ware_ai_seo_model', 'gpt-4o-mini');
    $tone    = get_option('i4ware_ai_seo_tone', 'professional');

    ?>
    <div class="wrap i4ware-ai-seo-wrap">
        <h1><?php esc_html_e('i4ware AI SEO Agent Dashboard', 'i4ware-ai-seo-agent'); ?></h1>

        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <a href="?page=i4ware-ai-seo-agent&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Settings', 'i4ware-ai-seo-agent'); ?>
            </a>
            <a href="?page=i4ware-ai-seo-agent&tab=bulk" class="nav-tab <?php echo $active_tab === 'bulk' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Bulk SEO Optimizer', 'i4ware-ai-seo-agent'); ?>
            </a>
        </h2>

        <div class="tab-content">
            <?php if ($active_tab === 'settings') : ?>
                <!-- Settings Tab -->
                <form method="post" action="options.php" style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                    <?php settings_fields('i4ware_ai_seo_group'); ?>
                    
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="i4ware_ai_seo_api_key"><?php esc_html_e('OpenAI API Key', 'i4ware-ai-seo-agent'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="i4ware_ai_seo_api_key" name="i4ware_ai_seo_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off" />
                                <p class="description"><?php esc_html_e('Your API key is used to evaluate post/page content and generate meta details.', 'i4ware-ai-seo-agent'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="i4ware_ai_seo_model"><?php esc_html_e('OpenAI Model', 'i4ware-ai-seo-agent'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="i4ware_ai_seo_model" name="i4ware_ai_seo_model" value="<?php echo esc_attr($model); ?>" class="regular-text" placeholder="gpt-4o-mini" />
                                <p class="description"><?php esc_html_e('Default: gpt-4o-mini (highly recommended, cost-effective).', 'i4ware-ai-seo-agent'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="i4ware_ai_seo_tone"><?php esc_html_e('SEO Tone of Voice', 'i4ware-ai-seo-agent'); ?></label>
                            </th>
                            <td>
                                <select id="i4ware_ai_seo_tone" name="i4ware_ai_seo_tone">
                                    <option value="professional" <?php selected($tone, 'professional'); ?>><?php esc_html_e('Professional & Authoritative', 'i4ware-ai-seo-agent'); ?></option>
                                    <option value="catchy" <?php selected($tone, 'catchy'); ?>><?php esc_html_e('Catchy & Click-Worthy', 'i4ware-ai-seo-agent'); ?></option>
                                    <option value="informative" <?php selected($tone, 'informative'); ?>><?php esc_html_e('Informative & Educational', 'i4ware-ai-seo-agent'); ?></option>
                                    <option value="conversational" <?php selected($tone, 'conversational'); ?>><?php esc_html_e('Friendly & Conversational', 'i4ware-ai-seo-agent'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('This dictates the tone of generated meta descriptions to match your brand style.', 'i4ware-ai-seo-agent'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Save settings', 'i4ware-ai-seo-agent')); ?>
                </form>
            <?php endif; ?>

            <?php if ($active_tab === 'bulk') : ?>
                <!-- Bulk Optimizer Tab -->
                <div id="i4ware-ai-seo-bulk-panel" style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                    <h3><?php esc_html_e('Bulk AI SEO Optimizer', 'i4ware-ai-seo-agent'); ?></h3>
                    <p><?php esc_html_e('Scan all post and pages to generate missing SEO metadata automatically in batches.', 'i4ware-ai-seo-agent'); ?></p>

                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
                        <div style="display:flex; gap:10px;">
                            <input type="text" id="i4ware-seo-search" placeholder="<?php esc_attr_e('Search content...', 'i4ware-ai-seo-agent'); ?>" style="margin:0; height:30px;" />
                            <button type="button" id="i4ware-seo-search-btn" class="button"><?php esc_html_e('Search', 'i4ware-ai-seo-agent'); ?></button>
                        </div>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <label for="i4ware-seo-filter"><strong><?php esc_html_e('Status:', 'i4ware-ai-seo-agent'); ?></strong></label>
                            <select id="i4ware-seo-filter" style="height:30px; margin:0;">
                                <option value="all"><?php esc_html_e('All Posts & Pages', 'i4ware-ai-seo-agent'); ?></option>
                                <option value="missing"><?php esc_html_e('Missing SEO Meta', 'i4ware-ai-seo-agent'); ?></option>
                                <option value="optimized"><?php esc_html_e('AI Optimized', 'i4ware-ai-seo-agent'); ?></option>
                            </select>
                            <button type="button" id="i4ware-seo-bulk-run" class="button button-primary" disabled>
                                <?php esc_html_e('Run Bulk Optimization', 'i4ware-ai-seo-agent'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar (Bulk) -->
                    <div id="i4ware-seo-progress-wrap" style="display:none; background:#f6f7f7; border: 1px solid #dcdcde; padding: 15px; border-radius: 3px; margin-bottom: 20px;">
                        <h4 style="margin:0 0 10px 0;"><?php esc_html_e('Bulk Optimization Progress', 'i4ware-ai-seo-agent'); ?></h4>
                        <div style="background:#f0f0f1; border-radius:4px; height:20px; width:100%; overflow:hidden; border: 1px solid #c3c4c7; position: relative; margin-bottom:10px;">
                            <div id="i4ware-seo-progress-bar" style="background:#2271b1; height:100%; width:0%; transition: width 0.2s ease;"></div>
                            <div id="i4ware-seo-progress-text" style="position: absolute; width: 100%; text-align: center; top: 0; line-height: 20px; font-weight: 600; color: #1d2327; font-size:11px;">0%</div>
                        </div>
                        <div id="i4ware-seo-tokens-cost" style="font-size:12px; color:#50575e;">
                            Tokens Used: <strong id="i4ware-seo-stat-tokens">0</strong> | Cost Estimate: <strong id="i4ware-seo-stat-cost" style="color:green;">$0.00</strong>
                        </div>
                    </div>

                    <!-- Live Logs Console -->
                    <div id="i4ware-seo-live-log" style="display:none; max-height: 120px; overflow-y: auto; background: #222; color: #00ff00; padding: 10px; font-family: monospace; font-size: 11px; border-radius: 3px; margin-bottom: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);"></div>

                    <!-- Posts list table -->
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width:30px;"><input type="checkbox" id="i4ware-seo-select-all" /></th>
                                <th><?php esc_html_e('Title', 'i4ware-ai-seo-agent'); ?></th>
                                <th style="width:120px;"><?php esc_html_e('Type', 'i4ware-ai-seo-agent'); ?></th>
                                <th style="width:140px;"><?php esc_html_e('SEO Status', 'i4ware-ai-seo-agent'); ?></th>
                                <th style="width:110px;"><?php esc_html_e('Actions', 'i4ware-ai-seo-agent'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="i4ware-seo-table-body">
                            <tr>
                                <td colspan="5" style="text-align:center; padding:20px;"><?php esc_html_e('Loading content...', 'i4ware-ai-seo-agent'); ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div style="margin-top: 15px; display:flex; justify-content:flex-end; align-items:center; gap:8px;">
                        <button type="button" id="i4ware-seo-prev-page" class="button" disabled>&laquo; <?php esc_html_e('Prev', 'i4ware-ai-seo-agent'); ?></button>
                        <span id="i4ware-seo-page-info" style="font-weight:600;">1 / 1</span>
                        <button type="button" id="i4ware-seo-next-page" class="button" disabled><?php esc_html_e('Next', 'i4ware-ai-seo-agent'); ?> &raquo;</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Register post editor Metabox
 */
function i4ware_ai_seo_add_meta_box() {
    $screens = ['post', 'page'];
    foreach ($screens as $screen) {
        add_meta_box(
            'i4ware_ai_seo_box',
            __('i4ware AI SEO Agent', 'i4ware-ai-seo-agent'),
            'i4ware_ai_seo_render_meta_box',
            $screen,
            'side',
            'high'
        );
    }
}

/**
 * Render post editor Metabox layout
 */
function i4ware_ai_seo_render_meta_box($post) {
    wp_nonce_field('i4ware_ai_seo_nonce_action', 'i4ware_ai_seo_nonce');

    // Retrieve active SEO values
    $saved_title = i4ware_ai_seo_get_meta_value($post->ID, 'title');
    $saved_desc  = i4ware_ai_seo_get_meta_value($post->ID, 'description');
    $saved_keys  = i4ware_ai_seo_get_meta_value($post->ID, 'keywords');

    $is_yoast = defined('WPSEO_VERSION');
    $is_rankmath = class_exists('RankMath');
    
    $seo_engine = 'Standalone';
    if ($is_yoast) {
        $seo_engine = 'Yoast SEO';
    } elseif ($is_rankmath) {
        $seo_engine = 'Rank Math';
    }

    ?>
    <div id="i4ware-ai-seo-metabox-root" data-post-id="<?php echo (int) $post->ID; ?>">
        <div style="background: #f6f7f7; border: 1px solid #ccd0d4; padding: 10px; border-radius: 4px; margin-bottom: 12px;">
            <p style="margin: 0; font-size: 11px; color: #50575e;">
                Active Engine: <strong><?php echo esc_html($seo_engine); ?></strong>
            </p>
        </div>

        <!-- Single Action Button -->
        <button type="button" id="i4ware-ai-seo-generate-btn" class="button button-primary button-large" style="width: 100%; text-align: center; margin-bottom: 15px;">
            <?php esc_html_e('Generate SEO with AI', 'i4ware-ai-seo-agent'); ?>
        </button>
        <div id="i4ware-ai-seo-spinner" class="spinner" style="float: none; display: none; margin: 0 auto 10px auto;"></div>

        <!-- Generated Metadata Output Cards (Hidden initially until generated, unless values already exist) -->
        <div id="i4ware-ai-seo-results" style="<?php echo ($saved_title || $saved_desc) ? '' : 'display:none;'; ?>">
            <!-- SEO Title -->
            <div class="i4ware-seo-field-wrap" style="margin-bottom: 12px;">
                <label for="i4ware-seo-output-title"><strong>SEO Title</strong></label>
                <input type="text" id="i4ware-seo-output-title" style="width:100%; margin-top:4px;" value="<?php echo esc_attr($saved_title); ?>" />
                <div class="i4ware-seo-counter" data-target="i4ware-seo-output-title" data-min="40" data-max="60" style="font-size:11px; margin-top:2px; text-align:right; color:#646970;">0 / 60</div>
            </div>

            <!-- SEO Meta Description -->
            <div class="i4ware-seo-field-wrap" style="margin-bottom: 12px;">
                <label for="i4ware-seo-output-desc"><strong>Meta Description</strong></label>
                <textarea id="i4ware-seo-output-desc" rows="3" style="width:100%; margin-top:4px; font-size:12px;"><?php echo esc_textarea($saved_desc); ?></textarea>
                <div class="i4ware-seo-counter" data-target="i4ware-seo-output-desc" data-min="120" data-max="160" style="font-size:11px; margin-top:2px; text-align:right; color:#646970;">0 / 160</div>
            </div>

            <!-- Focus Keywords -->
            <div class="i4ware-seo-field-wrap" style="margin-bottom: 15px;">
                <label for="i4ware-seo-output-keywords"><strong>Focus Keywords</strong></label>
                <input type="text" id="i4ware-seo-output-keywords" style="width:100%; margin-top:4px;" value="<?php echo esc_attr($saved_keys); ?>" />
                <p class="description" style="margin:2px 0 0 0; font-size:11px;">Comma separated keywords.</p>
            </div>

            <!-- AI Recommendations Card -->
            <div id="i4ware-seo-recommendations-wrap" style="background:#fff8e5; border-left:4px solid #f0b840; padding:10px; border-radius:3px; margin-bottom:15px; display:none;">
                <h4 style="margin:0 0 6px 0; font-size:12px;">AI SEO Recommendations:</h4>
                <ul id="i4ware-seo-recommendations-list" style="margin:0; padding-left:15px; font-size:11px; color:#2c3338; line-height:1.4;"></ul>
            </div>

            <!-- Apply Button -->
            <button type="button" id="i4ware-ai-seo-apply-btn" class="button button-secondary" style="width:100%; text-align:center;">
                <?php esc_html_e('Apply SEO Changes', 'i4ware-ai-seo-agent'); ?>
            </button>
        </div>

        <div id="i4ware-ai-seo-status-msg" style="font-size:12px; font-weight:600; text-align:center; margin-top:10px;"></div>
    </div>
    <?php
}

/**
 * Enqueue scripts and styles
 */
function i4ware_ai_seo_enqueue_assets($hook) {
    if (in_array($hook, ['post.php', 'post-new.php', 'settings_page_i4ware-ai-seo-agent'], true)) {
        wp_enqueue_style(
            'i4ware-ai-seo-admin-css',
            plugins_url('assets/css/admin.css', __FILE__),
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'i4ware-ai-seo-admin-js',
            plugins_url('assets/js/admin.js', __FILE__),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('i4ware-ai-seo-admin-js', 'i4wareAiSeoL10n', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('i4ware_ai_seo_nonce_action'),
            'analyzing'   => __('Analyzing content & generating tags...', 'i4ware-ai-seo-agent'),
            'applying'    => __('Applying modifications...', 'i4ware-ai-seo-agent'),
            'applied'     => __('SEO modifications applied successfully!', 'i4ware-ai-seo-agent'),
            'failed'      => __('Failed: ', 'i4ware-ai-seo-agent'),
            'placeholder' => __('Enter keyword', 'i4ware-ai-seo-agent'),
        ]);
    }
}

/**
 * Read current SEO values based on active engine
 */
function i4ware_ai_seo_get_meta_value($post_id, $type) {
    $is_yoast = defined('WPSEO_VERSION');
    $is_rankmath = class_exists('RankMath');

    if ($type === 'title') {
        if ($is_yoast) {
            return get_post_meta($post_id, '_yoast_wpseo_title', true);
        } elseif ($is_rankmath) {
            return get_post_meta($post_id, '_rank_math_title', true);
        }
        return get_post_meta($post_id, '_ai_seo_title', true);
    }
    
    if ($type === 'description') {
        if ($is_yoast) {
            return get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        } elseif ($is_rankmath) {
            return get_post_meta($post_id, '_rank_math_description', true);
        }
        return get_post_meta($post_id, '_ai_seo_description', true);
    }

    if ($type === 'keywords') {
        if ($is_yoast) {
            return get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        } elseif ($is_rankmath) {
            return get_post_meta($post_id, '_rank_math_focus_keyword', true);
        }
        return get_post_meta($post_id, '_ai_seo_keywords', true);
    }

    return '';
}

/**
 * Save SEO values to correct meta fields
 */
function i4ware_ai_seo_save_meta_value($post_id, $type, $val) {
    $is_yoast = defined('WPSEO_VERSION');
    $is_rankmath = class_exists('RankMath');

    $val = sanitize_text_field($val);

    if ($type === 'title') {
        if ($is_yoast) {
            update_post_meta($post_id, '_yoast_wpseo_title', $val);
        } elseif ($is_rankmath) {
            update_post_meta($post_id, '_rank_math_title', $val);
        }
        update_post_meta($post_id, '_ai_seo_title', $val);
    }

    if ($type === 'description') {
        if ($is_yoast) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $val);
        } elseif ($is_rankmath) {
            update_post_meta($post_id, '_rank_math_description', $val);
        }
        update_post_meta($post_id, '_ai_seo_description', $val);
    }

    if ($type === 'keywords') {
        if ($is_yoast) {
            update_post_meta($post_id, '_yoast_wpseo_focuskw', $val);
        } elseif ($is_rankmath) {
            update_post_meta($post_id, '_rank_math_focus_keyword', $val);
        }
        update_post_meta($post_id, '_ai_seo_keywords', $val);
    }
}

/**
 * AJAX: Single Post Analysis & Metadata Generation
 */
function i4ware_ai_seo_ajax_analyze() {
    check_ajax_referer('i4ware_ai_seo_nonce_action', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Insufficient permissions.', 'i4ware-ai-seo-agent')]);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error(['message' => __('Content post not found.', 'i4ware-ai-seo-agent')]);
    }

    $api_key = get_option('i4ware_ai_seo_api_key', '');
    if (empty($api_key)) {
        wp_send_json_error(['message' => __('OpenAI API Key is not configured.', 'i4ware-ai-seo-agent')]);
    }

    $model = get_option('i4ware_ai_seo_model', 'gpt-4o-mini');
    $tone  = get_option('i4ware_ai_seo_tone', 'professional');

    $post_content = strip_tags(strip_shortcodes($post->post_content));
    if (empty($post_content)) {
        wp_send_json_error(['message' => __('Post content is empty. Cannot perform analysis.', 'i4ware-ai-seo-agent')]);
    }

    // OpenAI Chat Completions API Prompt
    $system_prompt = 
        "You are an expert search engine optimization (SEO) agent.\n" .
        "Analyze the user's post content and generate highly optimized SEO meta details matching Google guidelines.\n\n" .
        "Instructions:\n" .
        "1. Generate an SEO Title (ideal character count 40-60 characters, highly click-worthy and descriptive).\n" .
        "2. Generate an SEO Meta Description (ideal character count 120-160 characters, compelling summarization, clear call to action).\n" .
        "3. Provide 3-5 focus keywords/keyphrases.\n" .
        "4. Offer a list of 3-5 structural recommendations (suggestions) to improve overall readability, keyword usage, headings, or content layout.\n\n" .
        "Format the output strictly as a JSON object matching this schema:\n" .
        "{\n" .
        "  \"seo_title\": \"...\",\n" .
        "  \"meta_description\": \"...\",\n" .
        "  \"keywords\": [\"keyword1\", \"keyword2\", \"...\"],\n" .
        "  \"recommendations\": [\n" .
        "    \"suggestion 1\",\n" .
        "    \"suggestion 2\",\n" .
        "    \"...\"\n" .
        "  ]\n" .
        "}\n\n" .
        "Guidelines:\n" .
        "- Maintain proper localization matching the language of the source text.\n" .
        "- Preserve brand names and trademarks in Latin characters.\n" .
        "- Retain tone of voice: " . esc_attr($tone) . ".";

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => "Post Title: " . $post->post_title . "\n\nPost Content:\n" . substr($post_content, 0, 8000)]
        ],
        'response_format' => ['type' => 'json_object'],
        'temperature' => 0.3,
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode($payload),
        'timeout' => 120,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => $response->get_error_message()]);
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);

    if ($status_code < 200 || $status_code >= 300) {
        $err_data = json_decode($body, true);
        $err_msg  = isset($err_data['error']['message']) ? $err_data['error']['message'] : __('Unknown OpenAI error.', 'i4ware-ai-seo-agent');
        wp_send_json_error(['message' => sprintf('OpenAI API Error (%d): %s', $status_code, $err_msg)]);
    }

    $decoded = json_decode($body, true);
    $content_string = isset($decoded['choices'][0]['message']['content']) ? trim($decoded['choices'][0]['message']['content']) : '';

    $seo_data = json_decode($content_string, true);
    if (!is_array($seo_data) || empty($seo_data['seo_title']) || empty($seo_data['meta_description'])) {
        wp_send_json_error(['message' => __('OpenAI returned invalid or empty JSON schema.', 'i4ware-ai-seo-agent')]);
    }

    // Add cost/tokens calculations
    $tokens = isset($decoded['usage']['total_tokens']) ? $decoded['usage']['total_tokens'] : 0;
    $cost = 0.0;
    if (isset($decoded['usage'])) {
        $cost = (($decoded['usage']['prompt_tokens'] / 1000000) * 0.150) + (($decoded['usage']['completion_tokens'] / 1000000) * 0.600);
    }

    wp_send_json_success([
        'seo_title'       => $seo_data['seo_title'],
        'meta_description'=> $seo_data['meta_description'],
        'keywords'        => is_array($seo_data['keywords']) ? implode(', ', $seo_data['keywords']) : '',
        'recommendations' => is_array($seo_data['recommendations']) ? $seo_data['recommendations'] : [],
        'tokens'          => $tokens,
        'cost'            => round($cost, 5),
    ]);
}

/**
 * AJAX: Save Generated Meta Details
 */
function i4ware_ai_seo_ajax_save() {
    check_ajax_referer('i4ware_ai_seo_nonce_action', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Insufficient permissions.', 'i4ware-ai-seo-agent')]);
    }

    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id) {
        wp_send_json_error(['message' => __('Missing post ID.', 'i4ware-ai-seo-agent')]);
    }

    $title = isset($_POST['seo_title']) ? sanitize_text_field($_POST['seo_title']) : '';
    $desc  = isset($_POST['meta_description']) ? sanitize_text_field($_POST['meta_description']) : '';
    $keys  = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';

    i4ware_ai_seo_save_meta_value($post_id, 'title', $title);
    i4ware_ai_seo_save_meta_value($post_id, 'description', $desc);
    i4ware_ai_seo_save_meta_value($post_id, 'keywords', $keys);
    
    // Set flag indicating optimized by AI
    update_post_meta($post_id, '_i4ware_ai_seo_optimized', current_time('mysql'));

    wp_send_json_success();
}

/**
 * AJAX: Query Posts/Pages for the bulk list
 */
function i4ware_ai_seo_ajax_bulk_list() {
    check_ajax_referer('i4ware_ai_seo_nonce_action', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Insufficient permissions.', 'i4ware-ai-seo-agent')]);
    }

    $paged = isset($_POST['paged']) ? max(1, (int) $_POST['paged']) : 1;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $filter_status = isset($_POST['filter_status']) ? sanitize_key($_POST['filter_status']) : 'all';

    $args = [
        'post_type'      => ['post', 'page'],
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => 15,
        'paged'          => $paged,
        's'              => $search,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $query = new WP_Query($args);
    $items = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();

            $title = i4ware_ai_seo_get_meta_value($id, 'title');
            $desc  = i4ware_ai_seo_get_meta_value($id, 'description');
            
            $optimized_at = get_post_meta($id, '_i4ware_ai_seo_optimized', true);
            $has_meta = !empty($title) && !empty($desc);

            if ($filter_status === 'missing' && $has_meta) {
                continue;
            }
            if ($filter_status === 'optimized' && !$optimized_at) {
                continue;
            }

            $items[] = [
                'id'           => $id,
                'title'        => get_the_title(),
                'type'         => get_post_type(),
                'status'       => get_post_status(),
                'optimized_at' => $optimized_at ? $optimized_at : '',
                'has_meta'     => $has_meta,
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
 * Output Standalone HTML Meta tags if Yoast/RankMath are not active
 */
function i4ware_ai_seo_render_meta_tags() {
    if (is_singular()) {
        $post_id = get_the_ID();
        
        // Skip outputting if Yoast or Rank Math is active
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return;
        }

        $desc = get_post_meta($post_id, '_ai_seo_description', true);
        if ($desc) {
            echo '<meta name="description" content="' . esc_attr($desc) . '" />' . "\n";
        }

        $keywords = get_post_meta($post_id, '_ai_seo_keywords', true);
        if ($keywords) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '" />' . "\n";
        }
    }
}

/**
 * Document Title Parts Filter - Override title tag for Standalone mode
 */
function i4ware_ai_seo_filter_title($title_parts) {
    if (is_singular()) {
        $post_id = get_the_ID();
        
        // Skip if Yoast or Rank Math is active
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return $title_parts;
        }

        $custom_title = get_post_meta($post_id, '_ai_seo_title', true);
        if ($custom_title) {
            $search = array(
                'Innovatiivinen Ohjelmistoyritys Suomessa &#8211; ',
                'Innovatiivinen Ohjelmistoyritys Suomessa &ndash; ',
                'Innovatiivinen Ohjelmistoyritys Suomessa – ',
                'Innovatiivinen Ohjelmistoyritys Suomessa - ',
                ' &#8211; Innovatiivinen Ohjelmistoyritys Suomessa',
                ' &ndash; Innovatiivinen Ohjelmistoyritys Suomessa',
                ' – Innovatiivinen Ohjelmistoyritys Suomessa',
                ' - Innovatiivinen Ohjelmistoyritys Suomessa',
                'Innovatiivinen Ohjelmistoyritys Suomessa',
            );
            $custom_title = trim(str_replace($search, '', $custom_title), " \t\n\r\0\x0B–-");
            if (!empty($custom_title)) {
                $title_parts['title'] = $custom_title;
            }
        }
    }
    return $title_parts;
}

