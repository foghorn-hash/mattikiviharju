<?php
/**
 * Class CV_OAI_PLL_Admin
 *
 * Handles WordPress admin settings, post editing meta boxes, asset enqueuing,
 * settings tabs, and AJAX translation request handling (single post & queue system).
 *
 * @package CV_OpenAI_Polylang_Translator
 */

if (!defined('ABSPATH')) {
    exit;
}

class CV_OAI_PLL_Admin {

    /**
     * Initializes hooks.
     */
    public function init() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Single post AJAX handler
        add_action('wp_ajax_cv_oai_pll_translate', [$this, 'handle_ajax_translation']);

        // Queue AJAX handlers
        add_action('wp_ajax_cv_oai_pll_scan_content', [$this, 'handle_ajax_scan_content']);
        add_action('wp_ajax_cv_oai_pll_process_queue_batch', [$this, 'handle_ajax_process_queue_batch']);
        add_action('wp_ajax_cv_oai_pll_get_queue_stats', [$this, 'handle_ajax_get_queue_stats']);
        add_action('wp_ajax_cv_oai_pll_retry_failed', [$this, 'handle_ajax_retry_failed']);
        add_action('wp_ajax_cv_oai_pll_clear_queue', [$this, 'handle_ajax_clear_queue']);
    }

    /**
     * Registers settings in WordPress.
     */
    public function register_settings() {
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_api_key', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_model', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_post_types', [
            'sanitize_callback' => [$this, 'sanitize_array_of_slugs']
        ]);
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_acf_fields', [
            'sanitize_callback' => [$this, 'sanitize_array_of_slugs']
        ]);
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_cooldown', [
            'sanitize_callback' => 'absint'
        ]);
        register_setting('cv_oai_pll_settings_group', 'cv_oai_pll_custom_fields', [
            'sanitize_callback' => 'sanitize_textarea_field'
        ]);
    }

    /**
     * Sanitizes an array of keys/slugs.
     *
     * @param mixed $input
     * @return array
     */
    public function sanitize_array_of_slugs($input) {
        if (!is_array($input)) {
            return [];
        }
        return array_map('sanitize_key', $input);
    }

    /**
     * Registers the Options settings page.
     */
    public function add_settings_page() {
        add_options_page(
            __('OpenAI Polylang Translator', 'cv-openai-polylang-translator'),
            __('OpenAI Polylang', 'cv-openai-polylang-translator'),
            'manage_options',
            'cv-oai-polylang-translator',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Renders the options settings page with two tabs.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';

        $api_key       = get_option('cv_oai_pll_api_key', '');
        $model         = get_option('cv_oai_pll_model', 'gpt-4o-mini');
        $cooldown      = get_option('cv_oai_pll_cooldown', '0');
        $custom_fields = get_option('cv_oai_pll_custom_fields', "_yoast_wpseo_title\n_yoast_wpseo_metadesc\n_rank_math_title\n_rank_math_description");
        
        $post_types = get_option('cv_oai_pll_post_types', ['page', 'cv_experience', 'cv_education', 'cv_course', 'cv_project', 'cv_skill', 'cv_badge']);
        if (!is_array($post_types)) {
            $post_types = ['page', 'cv_experience', 'cv_education', 'cv_course', 'cv_project', 'cv_skill', 'cv_badge'];
        }

        $acf_fields = get_option('cv_oai_pll_acf_fields', []);
        if (!is_array($acf_fields)) {
            $acf_fields = [];
        }

        // Fetch registered public post types
        $all_post_types = get_post_types(['public' => true], 'objects');

        // Fetch ACF fields if active
        $all_acf_fields = [];
        if (function_exists('acf_get_field_groups')) {
            $groups = acf_get_field_groups();
            if (is_array($groups)) {
                foreach ($groups as $group) {
                    $fields = acf_get_fields($group);
                    if (is_array($fields)) {
                        foreach ($fields as $field) {
                            $all_acf_fields[] = [
                                'name'  => $field['name'],
                                'label' => $field['label'],
                                'type'  => $field['type'],
                            ];
                        }
                    }
                }
            }
        }

        // Polylang target languages list
        $target_languages = [];
        if (function_exists('pll_languages_list')) {
            $pll_languages = pll_languages_list(['fields' => null]);
            if (is_array($pll_languages)) {
                foreach ($pll_languages as $l) {
                    if ($l->slug !== 'fi') {
                        $target_languages[] = $l;
                    }
                }
            }
        }
        ?>
        <div class="wrap cv-oai-pll-settings-wrap">
            <h1><?php esc_html_e('OpenAI Polylang Translator Dashboard', 'cv-openai-polylang-translator'); ?></h1>
            
            <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
                <a href="?page=cv-oai-polylang-translator&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Settings', 'cv-openai-polylang-translator'); ?>
                </a>
                <a href="?page=cv-oai-polylang-translator&tab=queue" class="nav-tab <?php echo $active_tab === 'queue' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Translation Queue & Worker', 'cv-openai-polylang-translator'); ?>
                </a>
                <a href="?page=cv-oai-polylang-translator&tab=media" class="nav-tab <?php echo $active_tab === 'media' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Media AI Analyzer', 'cv-openai-polylang-translator'); ?>
                </a>
            </h2>

            <div class="tab-content">
                <?php if ($active_tab === 'settings') : ?>
                    <!-- Settings Tab Content -->
                    <form method="post" action="options.php">
                        <?php settings_fields('cv_oai_pll_settings_group'); ?>
                        
                        <table class="form-table" role="presentation">
                            <!-- OpenAI API Key -->
                            <tr>
                                <th scope="row">
                                    <label for="cv_oai_pll_api_key"><?php esc_html_e('OpenAI API Key', 'cv-openai-polylang-translator'); ?></label>
                                </th>
                                <td>
                                    <input type="password" id="cv_oai_pll_api_key" name="cv_oai_pll_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off" />
                                    <p class="description"><?php esc_html_e('Your confidential API key remains hidden on load and is never logged.', 'cv-openai-polylang-translator'); ?></p>
                                </td>
                            </tr>

                            <!-- OpenAI Model -->
                            <tr>
                                <th scope="row">
                                    <label for="cv_oai_pll_model"><?php esc_html_e('OpenAI Model', 'cv-openai-polylang-translator'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="cv_oai_pll_model" name="cv_oai_pll_model" value="<?php echo esc_attr($model); ?>" class="regular-text" placeholder="gpt-4o-mini" />
                                    <p class="description"><?php esc_html_e('Recommended: gpt-4o-mini or gpt-4o for best structural adherence and B2B translation quality.', 'cv-openai-polylang-translator'); ?></p>
                                </td>
                            </tr>

                            <!-- Cooldown -->
                            <tr>
                                <th scope="row">
                                    <label for="cv_oai_pll_cooldown"><?php esc_html_e('Translation Cooldown (seconds)', 'cv-openai-polylang-translator'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="cv_oai_pll_cooldown" name="cv_oai_pll_cooldown" value="<?php echo esc_attr($cooldown); ?>" class="small-text" min="0" max="60" />
                                    <p class="description"><?php esc_html_e('Optional delay (cooldown) between consecutive OpenAI API chunk requests (0 to disable). Helps avoid API rate limit triggers.', 'cv-openai-polylang-translator'); ?></p>
                                </td>
                            </tr>

                            <!-- Post Types -->
                            <tr>
                                <th scope="row"><?php esc_html_e('Supported Post Types', 'cv-openai-polylang-translator'); ?></th>
                                <td>
                                    <p style="margin-bottom: 10px;"><label><input type="checkbox" id="cv_oai_pll_post_types_select_all" /> <strong><?php esc_html_e('Select All', 'cv-openai-polylang-translator'); ?></strong></label></p>
                                    <fieldset id="cv_oai_pll_post_types_fieldset">
                                        <?php foreach ($all_post_types as $pt_slug => $pt_obj) : ?>
                                            <label style="display:block; margin-bottom: 5px;">
                                                <input type="checkbox" name="cv_oai_pll_post_types[]" value="<?php echo esc_attr($pt_slug); ?>" <?php checked(in_array($pt_slug, $post_types, true)); ?> />
                                                <?php echo esc_html($pt_obj->label); ?> (<code><?php echo esc_html($pt_slug); ?></code>)
                                            </label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                </td>
                            </tr>

                            <!-- Custom / SEO Fields -->
                            <tr>
                                <th scope="row">
                                    <label for="cv_oai_pll_custom_fields"><?php esc_html_e('Custom Fields to Translate', 'cv-openai-polylang-translator'); ?></label>
                                </th>
                                <td>
                                    <textarea id="cv_oai_pll_custom_fields" name="cv_oai_pll_custom_fields" rows="5" class="regular-text" style="font-family: monospace;"><?php echo esc_textarea($custom_fields); ?></textarea>
                                    <p class="description"><?php esc_html_e('Enter custom field meta keys (e.g. Yoast SEO or RankMath keys) to extract and translate from posts, one per line.', 'cv-openai-polylang-translator'); ?></p>
                                </td>
                            </tr>

                            <!-- Translate ACF Fields Option -->
                            <tr>
                                <th scope="row"><?php esc_html_e('Translate ACF fields', 'cv-openai-polylang-translator'); ?></th>
                                <td>
                                    <?php if (empty($all_acf_fields)) : ?>
                                        <p class="description">
                                            <?php if (!function_exists('acf_get_field_groups')) : ?>
                                                <?php esc_html_e('Advanced Custom Fields is not active.', 'cv-openai-polylang-translator'); ?>
                                            <?php else : ?>
                                                <?php esc_html_e('No Advanced Custom Fields registered.', 'cv-openai-polylang-translator'); ?>
                                            <?php endif; ?>
                                        </p>
                                    <?php else : ?>
                                        <p class="description" style="margin-bottom: 10px;"><?php esc_html_e('Choose which ACF field names are enabled for translation. Only textual sub-fields of repeaters, flexible layouts, groups, or text areas will be parsed.', 'cv-openai-polylang-translator'); ?></p>
                                        <p style="margin-bottom: 10px;"><label><input type="checkbox" id="cv_oai_pll_acf_fields_select_all" /> <strong><?php esc_html_e('Select All', 'cv-openai-polylang-translator'); ?></strong></label></p>
                                        <div id="cv_oai_pll_acf_fields_container" style="max-height: 250px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; background: #fff; max-width: 500px;">
                                            <?php foreach ($all_acf_fields as $field) : ?>
                                                <label style="display:block; margin-bottom: 5px;">
                                                    <input type="checkbox" name="cv_oai_pll_acf_fields[]" value="<?php echo esc_attr($field['name']); ?>" <?php checked(in_array($field['name'], $acf_fields, true)); ?> />
                                                    <strong><?php echo esc_html($field['label']); ?></strong> (<code><?php echo esc_html($field['name']); ?></code>) - <span class="description"><?php echo esc_html($field['type']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                        
                        <?php submit_button(__('Save settings', 'cv-openai-polylang-translator')); ?>
                    </form>

                    <hr style="margin: 30px 0;" />

                    <h2><?php esc_html_e('Translation Run Logs & History', 'cv-openai-polylang-translator'); ?></h2>
                    <?php
                    $history = CV_OAI_PLL_Logger::get_global_history();
                    if (empty($history)) :
                        echo '<p>' . esc_html__('No translation history logged yet.', 'cv-openai-polylang-translator') . '</p>';
                    else :
                        ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Source ID', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Draft ID', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Target Language', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Model', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Fields', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Duration', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Result', 'cv-openai-polylang-translator'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $log) : ?>
                                    <tr>
                                        <td><?php echo esc_html($log['date']); ?></td>
                                        <td>
                                            <?php
                                            $src_title = get_the_title($log['source_post_id']);
                                            $src_title = $src_title ? $src_title : '#' . $log['source_post_id'];
                                            echo '<a href="' . esc_url(get_edit_post_link($log['source_post_id'])) . '">' . esc_html($src_title) . '</a>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php
                                            if ($log['success'] && $log['draft_id']) {
                                                $draft_title = get_the_title($log['draft_id']);
                                                $draft_title = $draft_title ? $draft_title : '#' . $log['draft_id'];
                                                echo '<a href="' . esc_url(get_edit_post_link($log['draft_id'])) . '">' . esc_html($draft_title) . '</a>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td><code><?php echo esc_html($log['target_lang']); ?></code></td>
                                        <td><?php echo esc_html($log['model']); ?></td>
                                        <td><?php echo esc_html($log['num_fields']); ?></td>
                                        <td><?php echo esc_html($log['duration']); ?>s</td>
                                        <td>
                                            <?php if ($log['success']) : ?>
                                                <span style="color: green; font-weight: bold;"><?php esc_html_e('Success', 'cv-openai-polylang-translator'); ?></span>
                                            <?php else : ?>
                                                <span style="color: red; font-weight: bold;"><?php esc_html_e('Failed', 'cv-openai-polylang-translator'); ?></span>
                                                <div style="font-size: 11px; color: #666; margin-top: 3px; max-width: 250px; white-space: normal; word-break: break-all;">
                                                    <?php echo esc_html($log['error_message']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                <?php else : ?>
                    <!-- Queue Tab Content -->
                    <div id="cv-oai-pll-queue-panel">
                        <?php wp_nonce_field('cv_oai_pll_queue_nonce', 'cv_oai_pll_queue_token'); ?>
                        
                        <div style="display: flex; gap: 20px; margin-bottom: 25px;">
                            <!-- Queue Setup Controls Card -->
                            <div style="flex: 1; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                <h3><?php esc_html_e('Queue Setup & Scanner', 'cv-openai-polylang-translator'); ?></h3>
                                <p><?php esc_html_e('Choose a target language, then scan and add contents to the queue.', 'cv-openai-polylang-translator'); ?></p>
                                
                                <p>
                                    <label for="cv_oai_pll_queue_target_lang"><strong><?php esc_html_e('Target Language:', 'cv-openai-polylang-translator'); ?></strong></label><br />
                                    <select id="cv_oai_pll_queue_target_lang" style="width: 100%; margin-top: 5px; max-width: 300px;">
                                        <option value=""><?php esc_html_e('-- Select Target Language --', 'cv-openai-polylang-translator'); ?></option>
                                        <?php foreach ($target_languages as $l) : ?>
                                            <option value="<?php echo esc_attr($l->slug); ?>"><?php echo esc_html($l->name); ?> (<code><?php echo esc_html($l->slug); ?></code>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>

                                <p style="margin-top: 15px;">
                                    <label>
                                        <input type="checkbox" id="cv-oai-pll-overwrite-queue" value="1" />
                                        <strong><?php esc_html_e('Overwrite existing translations', 'cv-openai-polylang-translator'); ?></strong>
                                    </label>
                                    <span class="description" style="display: block; margin-top: 3px;">
                                        <?php esc_html_e('Check this to overwrite existing translations (posts, strings, terms) and bypass the translation cache.', 'cv-openai-polylang-translator'); ?>
                                    </span>
                                </p>

                                <div style="display: flex; gap: 10px; margin-top: 20px;">
                                    <button type="button" id="cv-oai-pll-scan-btn" class="button button-secondary" disabled>
                                        <?php esc_html_e('Scan & Add All Finnish Content', 'cv-openai-polylang-translator'); ?>
                                    </button>
                                    <button type="button" id="cv-oai-pll-scan-strings-btn" class="button button-secondary" disabled>
                                        <?php esc_html_e('Scan & Add Missing Strings Only', 'cv-openai-polylang-translator'); ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Live Dashboard Info Card -->
                            <div style="flex: 1; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                                <h3><?php esc_html_e('Queue Status & Worker', 'cv-openai-polylang-translator'); ?></h3>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span><strong><?php esc_html_e('Total Queue items:', 'cv-openai-polylang-translator'); ?></strong></span>
                                    <span id="cv-oai-pll-stat-total">0</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span><strong><?php esc_html_e('Pending:', 'cv-openai-polylang-translator'); ?></strong></span>
                                    <span id="cv-oai-pll-stat-pending" style="color: #007cba;">0</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span><strong><?php esc_html_e('Processing:', 'cv-openai-polylang-translator'); ?></strong></span>
                                    <span id="cv-oai-pll-stat-processing" style="color: #cca300;">0</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span><strong><?php esc_html_e('Completed:', 'cv-openai-polylang-translator'); ?></strong></span>
                                    <span id="cv-oai-pll-stat-completed" style="color: green;">0</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                    <span><strong><?php esc_html_e('Failed:', 'cv-openai-polylang-translator'); ?></strong></span>
                                    <span id="cv-oai-pll-stat-failed" style="color: red;">0</span>
                                </div>

                                <div style="display: flex; gap: 10px;">
                                    <button type="button" id="cv-oai-pll-worker-toggle-btn" class="button button-primary" style="flex: 1;" disabled>
                                        <?php esc_html_e('Resume Queue Worker', 'cv-openai-polylang-translator'); ?>
                                    </button>
                                    <button type="button" id="cv-oai-pll-clear-queue-btn" class="button button-link-delete" style="color: red;">
                                        <?php esc_html_e('Clear Queue', 'cv-openai-polylang-translator'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Live Progress Bar Card -->
                        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                            <h3 style="margin-top: 0;"><?php esc_html_e('Translation Progress', 'cv-openai-polylang-translator'); ?></h3>
                            
                            <div class="progress-bar-wrapper" style="background:#f0f0f1; border-radius:4px; height:24px; width:100%; overflow:hidden; border: 1px solid #c3c4c7; margin: 15px 0; position: relative;">
                                <div id="cv-oai-pll-progress-bar" style="background:#2271b1; height:100%; width:0%; transition: width 0.3s ease;"></div>
                                <div id="cv-oai-pll-progress-text" style="position: absolute; width: 100%; text-align: center; top: 0; line-height: 24px; font-weight: 600; color: #1d2327;">0% (0 / 0)</div>
                            </div>

                            <!-- Real-time Status and Log Containers -->
                            <div id="cv-oai-pll-current-action" style="margin: 10px 0; font-style: italic; font-weight: 500; color: #50575e;"></div>
                            <div id="cv-oai-pll-live-log" style="max-height: 150px; overflow-y: auto; background: #f6f7f7; border: 1px solid #dcdcde; padding: 10px; font-family: monospace; font-size: 12px; border-radius: 3px; margin: 10px 0; display: none;"></div>
                            
                            <div style="display: flex; gap: 40px; margin-top: 15px;">
                                <div>
                                    <span style="font-weight: 600; font-size: 13px; text-transform: uppercase; color: #646970; display: block;"><?php esc_html_e('API Tokens Used', 'cv-openai-polylang-translator'); ?></span>
                                    <span id="cv-oai-pll-stat-tokens" style="font-size: 20px; font-weight: bold;">0</span>
                                </div>
                                <div>
                                    <span style="font-weight: 600; font-size: 13px; text-transform: uppercase; color: #646970; display: block;"><?php esc_html_e('Cost Estimate', 'cv-openai-polylang-translator'); ?></span>
                                    <span id="cv-oai-pll-stat-cost" style="font-size: 20px; font-weight: bold; color: green;">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Error Logs and Retry Section -->
                        <div id="cv-oai-pll-error-logs-container" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h3 style="margin: 0;"><?php esc_html_e('Queue Error Logs', 'cv-openai-polylang-translator'); ?></h3>
                                <button type="button" id="cv-oai-pll-retry-all-btn" class="button button-secondary" style="display: none;">
                                    <?php esc_html_e('Retry All Failed Items', 'cv-openai-polylang-translator'); ?>
                                </button>
                            </div>
                            
                            <div id="cv-oai-pll-error-log-table-wrap">
                                <p class="description"><?php esc_html_e('No failed items logged in the queue.', 'cv-openai-polylang-translator'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($active_tab === 'media') : ?>
                    <!-- Media Analyzer Tab Content -->
                    <div id="cv-oai-pll-media-panel" style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <h3><?php esc_html_e('Media AI Alt & Metadata Generator', 'cv-openai-polylang-translator'); ?></h3>
                        <p><?php esc_html_e('Scan, translate, and automatically generate image title, alt, caption, and description in Finnish, English, and Swedish using OpenAI Vision.', 'cv-openai-polylang-translator'); ?></p>

                        <!-- Search and filter controls -->
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
                            <div style="display:flex; gap:10px;">
                                <input type="text" id="cv-oai-media-search-input" placeholder="<?php esc_attr_e('Search images...', 'cv-openai-polylang-translator'); ?>" class="regular-text" style="margin:0; height:30px; line-height:30px;" />
                                <button type="button" id="cv-oai-media-search-btn" class="button"><?php esc_html_e('Search', 'cv-openai-polylang-translator'); ?></button>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <label for="cv-oai-media-filter-status"><strong><?php esc_html_e('Status:', 'cv-openai-polylang-translator'); ?></strong></label>
                                <select id="cv-oai-media-filter-status" style="height:30px; margin:0;">
                                    <option value="all"><?php esc_html_e('All Images', 'cv-openai-polylang-translator'); ?></option>
                                    <option value="missing"><?php esc_html_e('Missing Metadata', 'cv-openai-polylang-translator'); ?></option>
                                    <option value="completed"><?php esc_html_e('Completed Metadata', 'cv-openai-polylang-translator'); ?></option>
                                </select>
                                <button type="button" id="cv-oai-media-bulk-run-btn" class="button button-primary" disabled>
                                    <?php esc_html_e('Run Bulk Analysis', 'cv-openai-polylang-translator'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Progress Bar (Bulk) -->
                        <div id="cv-oai-media-progress-wrap" style="display:none; background:#f6f7f7; border: 1px solid #dcdcde; padding: 15px; border-radius: 3px; margin-bottom: 20px;">
                            <h4 style="margin:0 0 10px 0;"><?php esc_html_e('Bulk Processing Progress', 'cv-openai-polylang-translator'); ?></h4>
                            <div class="progress-bar-wrapper" style="background:#f0f0f1; border-radius:4px; height:20px; width:100%; overflow:hidden; border: 1px solid #c3c4c7; position: relative; margin-bottom:10px;">
                                <div id="cv-oai-media-progress-bar" style="background:#2271b1; height:100%; width:0%; transition: width 0.2s ease;"></div>
                                <div id="cv-oai-media-progress-text" style="position: absolute; width: 100%; text-align: center; top: 0; line-height: 20px; font-weight: 600; color: #1d2327; font-size:11px;">0%</div>
                            </div>
                            <div style="display: flex; gap: 40px;">
                                <div>
                                    <span style="font-weight: 600; font-size: 11px; text-transform: uppercase; color: #646970; display: block;"><?php esc_html_e('API Tokens Used', 'cv-openai-polylang-translator'); ?></span>
                                    <span id="cv-oai-media-stat-tokens" style="font-size: 16px; font-weight: bold;">0</span>
                                </div>
                                <div>
                                    <span style="font-weight: 600; font-size: 11px; text-transform: uppercase; color: #646970; display: block;"><?php esc_html_e('Cost Estimate', 'cv-openai-polylang-translator'); ?></span>
                                    <span id="cv-oai-media-stat-cost" style="font-size: 16px; font-weight: bold; color: green;">$0.0000</span>
                                </div>
                            </div>
                        </div>

                        <!-- Live Console Logs -->
                        <div id="cv-oai-media-live-log" style="display:none; max-height: 120px; overflow-y: auto; background: #222; color: #00ff00; padding: 10px; font-family: monospace; font-size: 11px; border-radius: 3px; margin-bottom: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);"></div>

                        <!-- Media listing table -->
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 30px;"><input type="checkbox" id="cv-oai-media-select-all" /></th>
                                    <th style="width: 60px;"><?php esc_html_e('Thumbnail', 'cv-openai-polylang-translator'); ?></th>
                                    <th><?php esc_html_e('Filename', 'cv-openai-polylang-translator'); ?></th>
                                    <th style="width: 150px;"><?php esc_html_e('AI Meta (Fi/En/Sv)', 'cv-openai-polylang-translator'); ?></th>
                                    <th style="width: 110px;"><?php esc_html_e('Actions', 'cv-openai-polylang-translator'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="cv-oai-media-list-table-body">
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 20px;"><?php esc_html_e('Loading images...', 'cv-openai-polylang-translator'); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Table Pagination -->
                        <div style="margin-top: 15px; display:flex; justify-content:space-between; align-items:center;">
                            <span class="description"><?php esc_html_e('Finnish (fi) primary attachments are listed. Translations are synchronized automatically.', 'cv-openai-polylang-translator'); ?></span>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <button type="button" id="cv-oai-media-prev-page" class="button" disabled>&laquo; <?php esc_html_e('Prev', 'cv-openai-polylang-translator'); ?></button>
                                <span id="cv-oai-media-page-info" style="font-weight:600;">1 / 1</span>
                                <button type="button" id="cv-oai-media-next-page" class="button" disabled><?php esc_html_e('Next', 'cv-openai-polylang-translator'); ?> &raquo;</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Registers meta boxes for supported content types and translations.
     */
    public function add_meta_boxes($post_type) {
        $supported_post_types = get_option('cv_oai_pll_post_types', ['page', 'cv_experience', 'cv_education', 'cv_course', 'cv_project', 'cv_skill', 'cv_badge']);
        if (!is_array($supported_post_types)) {
            $supported_post_types = ['page', 'cv_experience', 'cv_education', 'cv_course', 'cv_project', 'cv_skill', 'cv_badge'];
        }

        // 1. Meta Box on source posts: OpenAI translation action interface
        if (in_array($post_type, $supported_post_types, true)) {
            add_meta_box(
                'cv_oai_pll_translator_box',
                __('OpenAI Polylang Translator', 'cv-openai-polylang-translator'),
                [$this, 'render_translation_meta_box'],
                $post_type,
                'side',
                'high'
            );
        }

        // 2. Meta Box on target posts: Human Review checkbox
        global $post;
        if ($post) {
            $source_post_id = get_post_meta($post->ID, '_cv_oai_source_post_id', true);
            if ($source_post_id) {
                add_meta_box(
                    'cv_oai_pll_review_box',
                    __('Translation Review Status', 'cv-openai-polylang-translator'),
                    [$this, 'render_review_meta_box'],
                    $post_type,
                    'side',
                    'high'
                );
            }
        }
    }

    /**
     * Renders the translation action meta box on source posts.
     */
    public function render_translation_meta_box($post) {
        if (!function_exists('pll_get_post_language') || !function_exists('pll_languages_list')) {
            echo '<p class="notice notice-warning" style="padding: 5px;">' . esc_html__('Polylang is not active.', 'cv-openai-polylang-translator') . '</p>';
            return;
        }

        $post_id     = $post->ID;
        $source_lang = pll_get_post_language($post_id);

        if (empty($source_lang)) {
            echo '<p class="description">' . esc_html__('Please set the post language in Polylang before translating.', 'cv-openai-polylang-translator') . '</p>';
            return;
        }

        $api_key = get_option('cv_oai_pll_api_key', '');
        $missing_api_key = empty($api_key);

        // Fetch other configured languages
        $pll_languages = pll_languages_list(['fields' => null]);
        $target_languages = [];
        if (is_array($pll_languages)) {
            foreach ($pll_languages as $l) {
                if ($l->slug !== $source_lang) {
                    $target_languages[] = $l;
                }
            }
        }

        // Ensure post is saved
        $is_saved = ($post->post_status !== 'auto-draft');

        // Check locks
        $is_locked = CV_OAI_PLL_Translation_Lock::is_locked();
        $locked_id = CV_OAI_PLL_Translation_Lock::get_locked_post_id();

        // Pass details to JS via HTML attributes
        $translations_data = [];
        $translations = pll_get_post_translations($post_id);
        if (is_array($translations)) {
            foreach ($translations as $lang => $tr_id) {
                if ($tr_id != $post_id) {
                    $tr_post = get_post($tr_id);
                    if ($tr_post) {
                        $translations_data[$lang] = [
                            'id'       => $tr_id,
                            'status'   => $tr_post->post_status,
                            'edit_url' => get_edit_post_link($tr_id, 'raw'),
                        ];
                    }
                }
            }
        }
        $translations_json = wp_json_encode($translations_data);
        ?>
        <div id="cv-oai-pll-meta-box-root" data-post-id="<?php echo (int) $post_id; ?>" data-existing-translations="<?php echo esc_attr($translations_json); ?>">
            
            <?php wp_nonce_field('cv_oai_pll_translate_nonce', 'cv_oai_pll_nonce'); ?>
            
            <?php if (!$is_saved) : ?>
                <p style="color: #d63638; font-weight: bold;">
                    <?php esc_html_e('Please save the post as a draft before attempting translation.', 'cv-openai-polylang-translator'); ?>
                </p>
            <?php endif; ?>

            <?php if ($missing_api_key) : ?>
                <p style="color: #d63638;">
                    <?php esc_html_e('OpenAI API key is missing. Configure it in plugin settings.', 'cv-openai-polylang-translator'); ?>
                </p>
            <?php endif; ?>

            <!-- Target Language Select -->
            <p>
                <label for="cv_oai_pll_target_lang"><strong><?php esc_html_e('Target Language:', 'cv-openai-polylang-translator'); ?></strong></label><br />
                <select id="cv_oai_pll_target_lang" style="width: 100%; margin-top: 5px;">
                    <option value=""><?php esc_html_e('-- Select Language --', 'cv-openai-polylang-translator'); ?></option>
                    <?php foreach ($target_languages as $l) : ?>
                        <option value="<?php echo esc_attr($l->slug); ?>"><?php echo esc_html($l->name); ?> (<code><?php echo esc_html($l->slug); ?></code>)</option>
                    <?php endforeach; ?>
                </select>
            </p>

            <!-- Translate sections checkboxes -->
            <p><strong><?php esc_html_e('Translate Sections:', 'cv-openai-polylang-translator'); ?></strong></p>
            <div style="margin-bottom: 15px; border-left: 2px solid #2271b1; padding-left: 10px;">
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_title" checked /> <?php esc_html_e('Post Title', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_excerpt" checked /> <?php esc_html_e('Excerpt', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_content" checked /> <?php esc_html_e('Main Body Content', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_acf" checked /> <?php esc_html_e('Selected ACF Fields', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_caption" /> <?php esc_html_e('Image Captions', 'cv-openai-polylang-translator'); ?>
                </label>
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" id="cv_oai_pll_opt_alt" /> <?php esc_html_e('Image Alt Text', 'cv-openai-polylang-translator'); ?>
                </label>
            </div>

            <!-- Overwrite verification interface, populated dynamically via JS -->
            <div id="cv-oai-pll-overwrite-container" style="display: none; background: #fff8e5; border-left: 4px solid #f0b840; padding: 10px; margin-bottom: 15px;">
                <p style="margin:0 0 8px 0; font-size:12px; font-weight:600;" id="cv-oai-pll-overwrite-msg"></p>
                <a href="#" id="cv-oai-pll-open-existing-link" class="button button-small" target="_blank" style="margin-bottom: 8px; display:inline-block;"><?php esc_html_e('Open Existing Draft', 'cv-openai-polylang-translator'); ?></a>
                <label style="display:block; font-size: 11px; margin-top:5px; color:#2c3338;">
                    <input type="checkbox" id="cv_oai_pll_confirm_overwrite" />
                    <?php esc_html_e('I confirm that I want to Retranslate and Overwrite the existing draft translation.', 'cv-openai-polylang-translator'); ?>
                </label>
            </div>

            <div id="cv-oai-pll-published-warning" style="display: none; background: #fcf0f1; border-left: 4px solid #d63638; padding: 10px; margin-bottom: 15px;">
                <p style="margin:0; font-size:12px; color:#d63638; font-weight:600;"><?php esc_html_e('A published translation already exists for this language and cannot be overwritten.', 'cv-openai-polylang-translator'); ?></p>
            </div>

            <!-- Translate Action button -->
            <div style="margin-top: 15px;">
                <?php
                $disabled = (!$is_saved || $missing_api_key || $is_locked) ? 'disabled' : '';
                ?>
                <button type="button" id="cv_oai_pll_submit_btn" class="button button-primary button-large" style="width: 100%; text-align: center;" <?php echo esc_attr($disabled); ?>>
                    <?php esc_html_e('Translate with OpenAI', 'cv-openai-polylang-translator'); ?>
                </button>
                <div id="cv-oai-pll-spinner" class="spinner" style="float: none; display: block; margin: 10px auto; text-align: center;"></div>
            </div>

            <!-- Lock warning -->
            <?php if ($is_locked) : ?>
                <p id="cv-oai-pll-lock-warning" style="color: #b58100; font-size: 11px; margin-top: 8px;">
                    <?php
                    if ($locked_id == $post_id) {
                        esc_html_e('This post is currently locked as a translation job is already active for it.', 'cv-openai-polylang-translator');
                    } else {
                        esc_html_e('Another translation job is running on the server. Buttons are locked.', 'cv-openai-polylang-translator');
                    }
                    ?>
                </p>
            <?php endif; ?>

            <div id="cv-oai-pll-status-message" style="margin-top:10px; font-size: 12px; font-weight: 600;"></div>

            <!-- History log for this post -->
            <hr style="margin: 15px 0 10px 0;" />
            <p style="margin:0; font-size:11px; text-transform:uppercase; color:#646970;"><strong><?php esc_html_e('Translation History:', 'cv-openai-polylang-translator'); ?></strong></p>
            <div style="max-height: 120px; overflow-y: auto; font-size: 11px; color: #50575e; margin-top: 5px;" id="cv-oai-pll-history-list">
                <?php
                $post_history = CV_OAI_PLL_Logger::get_post_history($post_id);
                if (empty($post_history)) :
                    echo '<p style="margin:0;">' . esc_html__('No history for this item.', 'cv-openai-polylang-translator') . '</p>';
                else :
                    foreach ($post_history as $h) :
                        $status_str = $h['success'] ? __('Success', 'cv-openai-polylang-translator') : __('Failed', 'cv-openai-polylang-translator');
                        $color = $h['success'] ? 'green' : 'red';
                        echo '<div style="margin-bottom:6px; border-bottom:1px solid #f0f0f1; padding-bottom:4px;">';
                        echo '<strong>' . esc_html($h['date']) . '</strong> to <code>' . esc_html($h['target_lang']) . '</code><br />';
                        echo sprintf(
                            __('Status: <span style="color:%s;">%s</span> | Fields: %d | Time: %ss', 'cv-openai-polylang-translator'),
                            esc_attr($color),
                            esc_html($status_str),
                            (int) $h['num_fields'],
                            esc_html($h['duration'])
                        );
                        if (!$h['success'] && !empty($h['error_message'])) {
                            echo '<br /><span style="color:red; font-style:italic;">' . esc_html($h['error_message']) . '</span>';
                        }
                        echo '</div>';
                    endforeach;
                endif;
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renders the review status checkbox and warning notice on translated posts/pages.
     */
    public function render_review_meta_box($post) {
        $review_required = get_post_meta($post->ID, '_cv_oai_review_required', true);
        $source_post_id  = get_post_meta($post->ID, '_cv_oai_source_post_id', true);
        $target_lang     = get_post_meta($post->ID, '_cv_oai_target_language', true);
        ?>
        <div class="cv-oai-pll-review-box-inner" data-is-translation-draft="1" data-review-required="<?php echo esc_attr($review_required); ?>">
            <?php wp_nonce_field('cv_oai_pll_save_review_status', 'cv_oai_pll_review_status_nonce'); ?>
            <p style="background: #fff8e5; border-left: 4px solid #f0b840; padding: 10px; color: #2c3338; font-size:12px; margin:0 0 12px 0;">
                <strong><?php esc_html_e('Warning:', 'cv-openai-polylang-translator'); ?></strong><br />
                <?php esc_html_e('This content was translated by OpenAI and may contain errors or hallucinations. Check all facts, terminology, links, numbers, customer claims and formatting before publishing.', 'cv-openai-polylang-translator'); ?>
            </p>

            <label style="font-weight: bold; display: block; margin-bottom: 8px;">
                <input type="checkbox" name="cv_oai_review_completed" value="1" <?php checked($review_required, '0'); ?> />
                <?php esc_html_e('Translation reviewed by a human', 'cv-openai-polylang-translator'); ?>
            </label>

            <p class="description" style="font-size:11px;">
                <?php
                echo sprintf(
                    __('Source Post: <a href="%s" target="_blank">#%d</a> | Language: <code>%s</code>', 'cv-openai-polylang-translator'),
                    esc_url(get_edit_post_link($source_post_id)),
                    (int) $source_post_id,
                    esc_html($target_lang)
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Saves the meta box data when a post/page is saved.
     */
    public function save_meta_box_data($post_id) {
        // Verify nonce
        if (!isset($_POST['cv_oai_pll_review_status_nonce']) || !wp_verify_nonce($_POST['cv_oai_pll_review_status_nonce'], 'cv_oai_pll_save_review_status')) {
            return;
        }

        // Check user capabilities
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['cv_oai_review_completed'])) {
            update_post_meta($post_id, '_cv_oai_review_required', '0');
        } else {
            $source_post_id = get_post_meta($post_id, '_cv_oai_source_post_id', true);
            if ($source_post_id) {
                update_post_meta($post_id, '_cv_oai_review_required', '1');
            }
        }
    }

    /**
     * Enqueues administration JS and CSS assets.
     */
    public function enqueue_assets($hook) {
        if (in_array($hook, ['post.php', 'post-new.php', 'settings_page_cv-oai-polylang-translator', 'upload.php'], true)) {
            // CSS
            wp_enqueue_style(
                'cv-oai-pll-admin-css',
                plugins_url('assets/css/admin.css', dirname(__FILE__)),
                [],
                '1.0.0'
            );

            // JS
            wp_enqueue_script(
                'cv-oai-pll-admin-js',
                plugins_url('assets/js/admin.js', dirname(__FILE__)),
                ['jquery'],
                '1.0.0',
                true
            );

            wp_localize_script('cv-oai-pll-admin-js', 'cvOaiPllL10n', [
                'ajax_url'                 => admin_url('admin-ajax.php'),
                'confirm_publish_warning'  => __('This translation has not been marked as human-reviewed. Are you sure you want to publish it?', 'cv-openai-polylang-translator'),
                'translating_msg'          => __('Translating with OpenAI... Please wait, processing content chunks sequentially.', 'cv-openai-polylang-translator'),
                'success_msg'              => __('Translation completed successfully! Draft created.', 'cv-openai-polylang-translator'),
                'open_draft_lbl'           => __('Open Draft Translation', 'cv-openai-polylang-translator'),
                'select_lang_error'        => __('Please select a target language first.', 'cv-openai-polylang-translator'),
                'confirm_overwrite_error'  => __('Please check the confirm box to overwrite the existing draft translation.', 'cv-openai-polylang-translator'),
                'translation_failed_lbl'   => __('Translation failed: ', 'cv-openai-polylang-translator'),
            ]);

            // Queue & Media JavaScript loaders
            if ($hook === 'settings_page_cv-oai-polylang-translator' || $hook === 'upload.php') {
                wp_enqueue_script(
                    'cv-oai-pll-admin-media-js',
                    plugins_url('assets/js/admin-media.js', dirname(__FILE__)),
                    ['jquery'],
                    '1.0.0',
                    true
                );

                wp_localize_script('cv-oai-pll-admin-media-js', 'cvOaiPllQueueL10n', [
                    'ajax_url'            => admin_url('admin-ajax.php'),
                    'nonce'               => wp_create_nonce('cv_oai_pll_queue_nonce'),
                    'scanning_msg'        => __('Scanning and populating queue...', 'cv-openai-polylang-translator'),
                    'processing_msg'      => __('Processing batch of 10 items...', 'cv-openai-polylang-translator'),
                    'clearing_msg'        => __('Clearing queue...', 'cv-openai-polylang-translator'),
                    'retrying_msg'        => __('Retrying failed items...', 'cv-openai-polylang-translator'),
                    'no_lang_error'       => __('Please select a target language.', 'cv-openai-polylang-translator'),
                    'confirm_clear_queue' => __('Are you sure you want to clear the entire translation queue?', 'cv-openai-polylang-translator'),
                    'worker_running_lbl'  => __('Pause Queue Worker', 'cv-openai-polylang-translator'),
                    'worker_paused_lbl'   => __('Resume Queue Worker', 'cv-openai-polylang-translator'),
                    'run_bulk_label'      => __('Run Bulk Analysis (%d)', 'cv-openai-polylang-translator'),
                ]);
            }

            if ($hook === 'settings_page_cv-oai-polylang-translator') {
                wp_enqueue_script(
                    'cv-oai-pll-admin-queue-js',
                    plugins_url('assets/js/admin-queue.js', dirname(__FILE__)),
                    ['jquery'],
                    '1.0.0',
                    true
                );
            }
        }
    }

    /**
     * AJAX handler for performing the single-post translation workflow.
     */
    public function handle_ajax_translation() {
        check_ajax_referer('cv_oai_pll_translate_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => __('Insufficient permissions to edit this post.', 'cv-openai-polylang-translator')]);
        }

        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : '';
        if (empty($target_lang)) {
            wp_send_json_error(['message' => __('Please specify a target language.', 'cv-openai-polylang-translator')]);
        }

        $options = [
            'title'   => !empty($_POST['opt_title']),
            'excerpt' => !empty($_POST['opt_excerpt']),
            'content' => !empty($_POST['opt_content']),
            'acf'     => !empty($_POST['opt_acf']),
            'caption' => !empty($_POST['opt_caption']),
            'alt'     => !empty($_POST['opt_alt']),
        ];

        $overwrite_draft = !empty($_POST['confirm_overwrite']);

        $result = CV_OAI_PLL_Translator::translate_post($post_id, $target_lang, $options, $overwrite_draft);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $draft_url = get_edit_post_link($result, 'raw');
        wp_send_json_success([
            'draft_id'  => $result,
            'draft_url' => $draft_url,
        ]);
    }

    // ==========================================
    // QUEUE DASHBOARD AJAX HANDLERS
    // ==========================================

    /**
     * AJAX: Scan website and populate the translation queue.
     */
    public function handle_ajax_scan_content() {
        try {
            check_ajax_referer('cv_oai_pll_queue_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions.', 'cv-openai-polylang-translator')]);
            }

            $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : '';
            if (empty($target_lang)) {
                wp_send_json_error(['message' => __('Please select a target language.', 'cv-openai-polylang-translator')]);
            }

            $overwrite = !empty($_POST['overwrite']);

            require_once dirname(__FILE__) . '/class-cv-oai-pll-queue.php';
            
            $scan_type = isset($_POST['scan_type']) ? sanitize_text_field($_POST['scan_type']) : 'all';
            
            if ($scan_type === 'strings') {
                $added = CV_OAI_PLL_Queue::populate_missing_strings($target_lang, $overwrite);
            } else {
                $added = CV_OAI_PLL_Queue::populate_queue($target_lang, $overwrite);
            }

            wp_send_json_success([
                'added'   => $added,
                'message' => sprintf(__('%d items successfully added to the queue.', 'cv-openai-polylang-translator'), $added)
            ]);
        } catch (\Throwable $t) {
            wp_send_json_error(['message' => 'PHP Fatal Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ' on line ' . $t->getLine()]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'PHP Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
        }
    }

    /**
     * AJAX: Processes a single batch of 10 items from the queue.
     */
    public function handle_ajax_process_queue_batch() {
        try {
            check_ajax_referer('cv_oai_pll_queue_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions.', 'cv-openai-polylang-translator')]);
            }

            $bypass_cache = !empty($_POST['bypass_cache']);

            require_once dirname(__FILE__) . '/class-cv-oai-pll-queue.php';
            
            $results = CV_OAI_PLL_Queue::process_batch(10, $bypass_cache);
            wp_send_json_success($results);
        } catch (\Throwable $t) {
            wp_send_json_error(['message' => 'PHP Fatal Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ' on line ' . $t->getLine()]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'PHP Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
        }
    }

    /**
     * AJAX: Fetches queue statistics and API cost estimation.
     */
    public function handle_ajax_get_queue_stats() {
        try {
            check_ajax_referer('cv_oai_pll_queue_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions.', 'cv-openai-polylang-translator')]);
            }

            require_once dirname(__FILE__) . '/class-cv-oai-pll-db.php';
            
            $stats = CV_OAI_PLL_DB::get_queue_stats();
            $usage = CV_OAI_PLL_DB::get_api_usage_stats();
            $failed_items = CV_OAI_PLL_DB::get_failed_items(50);

            // Build HTML table output for error log
            ob_start();
            if (empty($failed_items)) {
                echo '<p class="description">' . esc_html__('No failed items logged in the queue.', 'cv-openai-polylang-translator') . '</p>';
            } else {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><?php esc_html_e('Type', 'cv-openai-polylang-translator'); ?></th>
                            <th style="width: 120px;"><?php esc_html_e('Item ID / Name', 'cv-openai-polylang-translator'); ?></th>
                            <th style="width: 80px;"><?php esc_html_e('Language', 'cv-openai-polylang-translator'); ?></th>
                            <th><?php esc_html_e('Error Details', 'cv-openai-polylang-translator'); ?></th>
                            <th style="width: 80px;"><?php esc_html_e('Attempts', 'cv-openai-polylang-translator'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('Action', 'cv-openai-polylang-translator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failed_items as $item) : ?>
                            <tr>
                                <td><code><?php echo esc_html($item->item_type); ?></code></td>
                                <td>
                                    <?php
                                    if ($item->item_type === 'post') {
                                        $title = get_the_title($item->item_id);
                                        echo '<a href="' . esc_url(get_edit_post_link($item->item_id)) . '">' . esc_html($title ? $title : '#' . $item->item_id) . '</a>';
                                    } elseif ($item->item_type === 'term') {
                                        $term = get_term($item->item_id);
                                        echo esc_html($term && !is_wp_error($term) ? $term->name : '#' . $item->item_id);
                                    } else {
                                        // String ID (show truncated hash/value)
                                        echo '<span title="' . esc_attr($item->item_id) . '">' . esc_html(substr($item->item_id, 0, 10)) . '...</span>';
                                    }
                                    ?>
                                </td>
                                <td><code><?php echo esc_html($item->target_language); ?></code></td>
                                <td style="color: #d63638; font-size: 11px; line-height: 1.4; white-space: normal; word-break: break-all;"><?php echo esc_html($item->error_message); ?></td>
                                <td><?php echo (int) $item->attempts; ?></td>
                                <td>
                                    <button type="button" class="button button-small cv-oai-pll-retry-single-btn" data-id="<?php echo (int) $item->id; ?>">
                                        <?php esc_html_e('Retry', 'cv-openai-polylang-translator'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php
            }
            $error_log_html = ob_get_clean();

            // Fetch next pending item description
            $next_pending = CV_OAI_PLL_DB::get_pending_batch(1);
            $next_item_desc = '';
            if (!empty($next_pending)) {
                $item = $next_pending[0];
                $type = $item->item_type;
                $lang = strtoupper($item->target_language);
                
                if ($type === 'post') {
                    $name = get_the_title($item->item_id);
                } elseif ($type === 'term') {
                    $term = get_term($item->item_id);
                    $name = ($term && !is_wp_error($term)) ? $term->name : '#' . $item->item_id;
                } elseif ($type === 'menu') {
                    $menu = wp_get_nav_menu_object($item->item_id);
                    $name = $menu ? $menu->name : '#' . $item->item_id;
                } else {
                    // String - fetch from registered strings mapping
                    global $polylang;
                    $name = '';
                    if (isset($polylang->model)) {
                        require_once dirname(__FILE__) . '/class-cv-oai-pll-queue.php';
                        $registered_strings = CV_OAI_PLL_Queue::get_registered_strings();
                        foreach ($registered_strings as $string) {
                            $hash = md5($string['context'] . '|||' . $string['name'] . '|||' . $string['string']);
                            if ($hash === $item->item_id) {
                                $name = $string['string'];
                                break;
                            }
                        }
                    }
                    if (empty($name)) {
                        $name = __('String', 'cv-openai-polylang-translator');
                    }
                }
                
                $truncated_name = strlen($name) > 40 ? mb_substr($name, 0, 40) . '...' : $name;
                $next_item_desc = sprintf(__('Translating: "%s" [%s] to %s...', 'cv-openai-polylang-translator'), $truncated_name, strtoupper($type), $lang);
            }

            wp_send_json_success([
                'stats'          => $stats,
                'tokens_total'   => $usage['prompt_tokens'] + $usage['completion_tokens'],
                'cost_total'     => sprintf('$%s', number_format($usage['cost_estimate'], 4)),
                'error_log_html' => $error_log_html,
                'next_item_desc' => $next_item_desc
            ]);
        } catch (\Throwable $t) {
            wp_send_json_error(['message' => 'PHP Fatal Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ' on line ' . $t->getLine()]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'PHP Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
        }
    }

    /**
     * AJAX: Reset failed items status back to pending.
     */
    public function handle_ajax_retry_failed() {
        try {
            check_ajax_referer('cv_oai_pll_queue_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions.', 'cv-openai-polylang-translator')]);
            }

            require_once dirname(__FILE__) . '/class-cv-oai-pll-db.php';
            
            $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : null;
            
            if ($item_id) {
                $res = CV_OAI_PLL_DB::retry_failed_items($item_id);
            } else {
                $res = CV_OAI_PLL_DB::retry_failed_items(null);
            }

            if ($res) {
                wp_send_json_success(['message' => __('Items successfully reset to pending.', 'cv-openai-polylang-translator')]);
            } else {
                wp_send_json_error(['message' => __('No failed items found or reset failed.', 'cv-openai-polylang-translator')]);
            }
        } catch (\Throwable $t) {
            wp_send_json_error(['message' => 'PHP Fatal Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ' on line ' . $t->getLine()]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'PHP Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
        }
    }

    /**
     * AJAX: Clear entire queue table.
     */
    public function handle_ajax_clear_queue() {
        try {
            check_ajax_referer('cv_oai_pll_queue_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Insufficient permissions.', 'cv-openai-polylang-translator')]);
            }

            require_once dirname(__FILE__) . '/class-cv-oai-pll-db.php';
            
            $res = CV_OAI_PLL_DB::clear_queue();
            if ($res) {
                wp_send_json_success(['message' => __('Queue successfully cleared.', 'cv-openai-polylang-translator')]);
            } else {
                wp_send_json_error(['message' => __('Failed to clear queue.', 'cv-openai-polylang-translator')]);
            }
        } catch (\Throwable $t) {
            wp_send_json_error(['message' => 'PHP Fatal Error: ' . $t->getMessage() . ' in ' . $t->getFile() . ' on line ' . $t->getLine()]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'PHP Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
        }
    }
}
