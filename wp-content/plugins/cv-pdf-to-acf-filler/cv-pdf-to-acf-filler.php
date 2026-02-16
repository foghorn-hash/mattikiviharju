<?php
/**
 * Plugin Name: CV PDF to ACF Filler
 * Description: Extract CV data from PDF files using OpenAI, auto-translate to Fi/En/Sv, and populate ACF fields on home pages (overwrites existing content)
 * Version: 2.0.0
 * Author: GitHub Copilot
 * Requires Plugins: advanced-custom-fields, polylang
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load composer dependencies if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Register settings
function cv_pdf_acf_register_settings() {
    register_setting('cv_pdf_acf_settings', 'cv_pdf_acf_api_key');
    register_setting('cv_pdf_acf_settings', 'cv_pdf_acf_model');
}
add_action('admin_init', 'cv_pdf_acf_register_settings');

// Add settings page to admin menu
function cv_pdf_acf_add_settings_page() {
    add_options_page(
        'CV PDF to ACF Filler',
        'CV PDF to ACF',
        'manage_options',
        'cv-pdf-to-acf-filler',
        'cv_pdf_acf_render_settings_page'
    );
}
add_action('admin_menu', 'cv_pdf_acf_add_settings_page');

// Render settings and upload page
function cv_pdf_acf_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $api_key = get_option('cv_pdf_acf_api_key', '');
    $model = get_option('cv_pdf_acf_model', 'gpt-4o');

    if (!empty($_GET['cv_pdf_acf_message'])) {
        $message_type = !empty($_GET['cv_pdf_acf_type']) ? $_GET['cv_pdf_acf_type'] : 'success';
        echo '<div class="notice notice-' . esc_attr($message_type) . '"><p>' . esc_html(wp_unslash($_GET['cv_pdf_acf_message'])) . '</p></div>';
    }

    // Check dependencies
    $dependencies_ok = true;
    
    if (!function_exists('get_field')) {
        echo '<div class="notice notice-error"><p><strong>Error:</strong> Advanced Custom Fields (ACF) is not active. Please activate ACF to use this plugin.</p></div>';
        $dependencies_ok = false;
    }
    
    if (!function_exists('pll_get_post_translations')) {
        echo '<div class="notice notice-error"><p><strong>Error:</strong> Polylang is not active. Please activate Polylang for multi-language support.</p></div>';
        $dependencies_ok = false;
    }
    
    // Check PDF parsing capability
    $pdf_method = cv_pdf_acf_check_pdf_capability();
    if (!$pdf_method) {
        echo '<div class="notice notice-error"><p><strong>Error:</strong> No PDF parsing library found. Please install one of the following:<br>';
        echo '<strong>Option 1 (Recommended):</strong> Run <code>composer require smalot/pdfparser</code> in the plugin directory<br>';
        echo '<strong>Option 2:</strong> Install <code>pdftotext</code> command-line tool (poppler-utils)</p></div>';
        $dependencies_ok = false;
    } else {
        echo '<div class="notice notice-success"><p><strong>PDF Parser:</strong> ' . esc_html($pdf_method) . ' is available ✓</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>CV PDF to ACF Filler</h1>
        
        <h2>Status</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">ACF Plugin</th>
                <td><?php echo function_exists('get_field') ? '✓ Active' : '✗ Not installed'; ?></td>
            </tr>
            <tr>
                <th scope="row">Polylang Plugin</th>
                <td><?php echo function_exists('pll_get_post_translations') ? '✓ Active' : '✗ Not installed'; ?></td>
            </tr>
            <tr>
                <th scope="row">PDF Parser</th>
                <td><?php echo $pdf_method ? '✓ ' . esc_html($pdf_method) : '✗ Not available'; ?></td>
            </tr>
            <tr>
                <th scope="row">OpenAI API Key</th>
                <td><?php echo !empty($api_key) ? '✓ Configured' : '✗ Not configured'; ?></td>
            </tr>
        </table>
        
        <hr />
        
        <h2>Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('cv_pdf_acf_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="cv_pdf_acf_api_key">OpenAI API Key</label></th>
                    <td><input type="password" id="cv_pdf_acf_api_key" name="cv_pdf_acf_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cv_pdf_acf_model">Model</label></th>
                    <td>
                        <input type="text" id="cv_pdf_acf_model" name="cv_pdf_acf_model" value="<?php echo esc_attr($model); ?>" class="regular-text" />
                        <p class="description">Recommended: gpt-4o, gpt-4o-mini, or gpt-4-turbo</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <hr />

        <h2>Upload CV PDF</h2>
        <?php if (!$dependencies_ok) : ?>
            <div class="notice notice-warning"><p><strong>Warning:</strong> Please resolve the errors above before uploading a PDF.</p></div>
        <?php else : ?>
        <p>Upload a PDF CV file to extract data and populate ACF fields on Home pages (Fi, En, Sv). <strong>Existing content will be overwritten.</strong></p>
        <?php
        // Check for Polylang and get home page translations
        $front_page_id = get_option('page_on_front');
        $has_polylang = function_exists('pll_get_post_translations');
        $home_pages = array();
        
        if ($has_polylang && $front_page_id) {
            $translations = pll_get_post_translations($front_page_id);
            foreach (array('fi', 'en', 'sv') as $lang) {
                if (!empty($translations[$lang])) {
                    $page = get_post($translations[$lang]);
                    if ($page) {
                        $home_pages[$lang] = $page->post_title . ' (ID: ' . $page->ID . ')';
                    }
                }
            }
        }
        
        if (empty($home_pages)) {
            echo '<div class="notice notice-warning"><p>Warning: Could not find Home pages in all three languages (Fi, En, Sv). Please ensure Polylang is active and home page translations are set up.</p></div>';
        } else {
            echo '<div class="notice notice-info"><p><strong>Target pages:</strong><br>';
            foreach ($home_pages as $lang => $info) {
                echo '• ' . strtoupper($lang) . ': ' . esc_html($info) . '<br>';
            }
            echo '</p></div>';
        }
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('cv_pdf_acf_upload'); ?>
            <input type="hidden" name="action" value="cv_pdf_acf_upload" />
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="cv_pdf_file">CV PDF File</label></th>
                    <td>
                        <input type="file" id="cv_pdf_file" name="cv_pdf_file" accept=".pdf" required />
                        <p class="description">Select a PDF file containing CV information (English or Finnish)</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Source Language</th>
                    <td>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="radio" name="cv_source_language" value="fi" checked />
                            Finnish (populate Fi, translate to En & Sv)
                        </label>
                        <label style="display: block;">
                            <input type="radio" name="cv_source_language" value="en" />
                            English (populate En, translate to Fi & Sv)
                        </label>
                        <p class="description">Select the language of your PDF CV</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Upload and Process PDF'); ?>
        </form>
        <?php endif; ?>
    </div>
    <?php
}

// Check PDF parsing capability
function cv_pdf_acf_check_pdf_capability() {
    // Check for Smalot PDF Parser
    if (class_exists('\\Smalot\\PdfParser\\Parser')) {
        return 'Smalot PDF Parser (Composer)';
    }
    
    // Check for pdftotext command
    if (function_exists('shell_exec')) {
        $test = shell_exec('pdftotext -v 2>&1');
        if ($test && stripos($test, 'pdftotext') !== false) {
            return 'pdftotext (Command-line)';
        }
    }
    
    return false;
}

// Call OpenAI API
function cv_pdf_acf_call_openai($payload, $api_key) {
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'timeout' => 120,
        'body' => wp_json_encode($payload),
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300) {
        return new WP_Error('openai_error', 'OpenAI API error: ' . $body);
    }

    $json = json_decode($body, true);
    if (!is_array($json) || empty($json['choices'][0]['message']['content'])) {
        return new WP_Error('openai_invalid', 'Invalid OpenAI response.');
    }

    return $json['choices'][0]['message']['content'];
}

// Parse JSON response from OpenAI
function cv_pdf_acf_parse_json($content) {
    $content = trim($content);
    if (str_starts_with($content, '```')) {
        $content = preg_replace('/^```(json)?/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);
    }

    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    return null;
}

// Extract text from PDF
function cv_pdf_acf_extract_pdf_text($file_path) {
    $errors = array();
    
    // Try using Smalot PDF Parser if available
    if (class_exists('\\Smalot\\PdfParser\\Parser')) {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($file_path);
            $text = $pdf->getText();
            if (!empty(trim($text))) {
                return $text;
            }
            $errors[] = 'Smalot parser returned empty text';
        } catch (Exception $e) {
            $errors[] = 'Smalot parser error: ' . $e->getMessage();
        }
    } else {
        $errors[] = 'Smalot PDF Parser not installed (run: composer install)';
    }
    
    // Try using pdftotext command line tool
    if (function_exists('shell_exec')) {
        $output_file = $file_path . '.txt';
        $command = sprintf('pdftotext %s %s 2>&1', escapeshellarg($file_path), escapeshellarg($output_file));
        $result = shell_exec($command);
        
        if (file_exists($output_file)) {
            $text = file_get_contents($output_file);
            @unlink($output_file);
            if (!empty(trim($text))) {
                return $text;
            }
            $errors[] = 'pdftotext returned empty text';
        } else {
            $errors[] = 'pdftotext command failed or not installed';
        }
    } else {
        $errors[] = 'shell_exec is disabled';
    }
    
    $error_message = 'Unable to extract text from PDF. Tried methods: ' . implode(' | ', $errors);
    return new WP_Error('pdf_parse_error', $error_message);
}

// Extract CV data from PDF using OpenAI
function cv_pdf_acf_extract_cv_from_pdf($file_path, $api_key, $model) {
    // Extract text from PDF
    $pdf_text = cv_pdf_acf_extract_pdf_text($file_path);
    if (is_wp_error($pdf_text)) {
        return $pdf_text;
    }

    // Create prompt for OpenAI
    $system_prompt = 'You are a CV data extraction assistant. Extract structured CV information from the provided CV text. Return a JSON object with the following structure:
{
  "profile_badges": ["badge1", "badge2", ...],
  "experience_title": "Experience" or appropriate section title,
  "experience_items": [
    {"role": "Job Title", "dates": "Month Year - Month Year", "summary": "Job description and achievements"}
  ],
  "projects_title": "Projects" or appropriate section title,
  "projects": [
    {"title": "Project Name", "meta": "Technology/Role", "description": "Project description"}
  ],
  "skills_title": "Skills" or appropriate section title,
  "skills": ["Skill1", "Skill2", ...]
}

Extract all relevant information from the CV. If a section is not present, use an empty array or null. Profile badges could include certifications, titles, or key qualifications mentioned at the top of the CV.';

    $payload = array(
        'model' => $model,
        'temperature' => 0.1,
        'response_format' => array('type' => 'json_object'),
        'messages' => array(
            array(
                'role' => 'system',
                'content' => $system_prompt,
            ),
            array(
                'role' => 'user',
                'content' => 'Please extract all CV information from this text and return it as structured JSON:\n\n' . $pdf_text
            ),
        ),
    );

    $content = cv_pdf_acf_call_openai($payload, $api_key);
    if (is_wp_error($content)) {
        return $content;
    }

    $data = cv_pdf_acf_parse_json($content);
    if (!$data) {
        return new WP_Error('openai_parse', 'Failed to parse CV extraction response.');
    }

    return $data;
}

// Translate CV data between languages
function cv_pdf_acf_translate_cv($cv_data, $target_lang, $api_key, $model) {
    $lang_names = array(
        'fi' => 'Finnish',
        'en' => 'English',
        'sv' => 'Swedish'
    );
    
    $target_name = $lang_names[$target_lang] ?? $target_lang;
    
    // Create prompt for translation
    $system_prompt = "You are a professional translator specializing in CV/resume translation. Translate all the provided CV content to {$target_name}. Maintain professional tone and accuracy. Return the same JSON structure with all text translated to {$target_name}.";

    $payload = array(
        'model' => $model,
        'temperature' => 0.2,
        'response_format' => array('type' => 'json_object'),
        'messages' => array(
            array(
                'role' => 'system',
                'content' => $system_prompt,
            ),
            array(
                'role' => 'user',
                'content' => "Please translate this CV data to {$target_name}, keeping the same JSON structure:\n\n" . wp_json_encode($cv_data)
            ),
        ),
    );

    $content = cv_pdf_acf_call_openai($payload, $api_key);
    if (is_wp_error($content)) {
        return $content;
    }

    $translated_data = cv_pdf_acf_parse_json($content);
    if (!$translated_data) {
        return new WP_Error('openai_translate_parse', 'Failed to parse translation response.');
    }

    return $translated_data;
}

// Populate ACF fields with extracted CV data (OVERWRITE mode)
function cv_pdf_acf_populate_fields($page_id, $cv_data) {
    $updated = array();

    // Profile Badges
    if (!empty($cv_data['profile_badges']) && is_array($cv_data['profile_badges'])) {
        $badges = array();
        foreach ($cv_data['profile_badges'] as $badge) {
            $badges[] = array('label' => sanitize_text_field($badge));
        }
        update_field('cv_profile_badges', $badges, $page_id);
        $updated[] = 'Profile Badges';
    }

    // Experience
    if (!empty($cv_data['experience_title'])) {
        update_field('cv_experience_title', sanitize_text_field($cv_data['experience_title']), $page_id);
        $updated[] = 'Experience Title';
    }

    if (!empty($cv_data['experience_items']) && is_array($cv_data['experience_items'])) {
        $experience = array();
        foreach ($cv_data['experience_items'] as $item) {
            $experience[] = array(
                'role' => sanitize_text_field($item['role'] ?? ''),
                'dates' => sanitize_text_field($item['dates'] ?? ''),
                'summary' => sanitize_textarea_field($item['summary'] ?? '')
            );
        }
        update_field('cv_experience_items', $experience, $page_id);
        $updated[] = 'Experience Items';
    }

    // Projects
    if (!empty($cv_data['projects_title'])) {
        update_field('cv_projects_title', sanitize_text_field($cv_data['projects_title']), $page_id);
        $updated[] = 'Projects Title';
    }

    if (!empty($cv_data['projects']) && is_array($cv_data['projects'])) {
        $projects = array();
        foreach ($cv_data['projects'] as $project) {
            $projects[] = array(
                'title' => sanitize_text_field($project['title'] ?? ''),
                'meta' => sanitize_text_field($project['meta'] ?? ''),
                'description' => sanitize_textarea_field($project['description'] ?? '')
            );
        }
        update_field('cv_projects', $projects, $page_id);
        $updated[] = 'Projects';
    }

    // Skills
    if (!empty($cv_data['skills_title'])) {
        update_field('cv_skills_title', sanitize_text_field($cv_data['skills_title']), $page_id);
        $updated[] = 'Skills Title';
    }

    if (!empty($cv_data['skills']) && is_array($cv_data['skills'])) {
        $skills = array();
        foreach ($cv_data['skills'] as $skill) {
            $skills[] = array('label' => sanitize_text_field($skill));
        }
        update_field('cv_skills', $skills, $page_id);
        $updated[] = 'Skills';
    }

    return array(
        'updated' => $updated
    );
}

// Handle PDF upload and processing
function cv_pdf_acf_handle_upload() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }
    check_admin_referer('cv_pdf_acf_upload');

    if (!function_exists('update_field')) {
        cv_pdf_acf_redirect_with_message('ACF is not active.', 'error');
        return;
    }

    if (!function_exists('pll_get_post_translations')) {
        cv_pdf_acf_redirect_with_message('Polylang is not active. This plugin requires Polylang for multi-language support.', 'error');
        return;
    }

    $api_key = get_option('cv_pdf_acf_api_key', '');
    if (empty($api_key)) {
        cv_pdf_acf_redirect_with_message('Missing OpenAI API key. Please configure settings first.', 'error');
        return;
    }

    $model = get_option('cv_pdf_acf_model', 'gpt-4o');
    $source_lang = isset($_POST['cv_source_language']) ? sanitize_text_field($_POST['cv_source_language']) : 'fi';
    
    // Get front page and its translations
    $front_page_id = get_option('page_on_front');
    if (!$front_page_id) {
        cv_pdf_acf_redirect_with_message('No front page set. Please set a front page first.', 'error');
        return;
    }
    
    $translations = pll_get_post_translations($front_page_id);
    $required_langs = array('fi', 'en', 'sv');
    $missing_langs = array();
    
    foreach ($required_langs as $lang) {
        if (empty($translations[$lang])) {
            $missing_langs[] = strtoupper($lang);
        }
    }
    
    if (!empty($missing_langs)) {
        cv_pdf_acf_redirect_with_message('Missing home page translations for: ' . implode(', ', $missing_langs) . '. Please create all language versions.', 'error');
        return;
    }

    // Handle file upload
    if (empty($_FILES['cv_pdf_file']) || $_FILES['cv_pdf_file']['error'] !== UPLOAD_ERR_OK) {
        cv_pdf_acf_redirect_with_message('File upload failed. Please try again.', 'error');
        return;
    }

    $file = $_FILES['cv_pdf_file'];
    
    // Validate file type
    if ($file['type'] !== 'application/pdf' && !str_ends_with($file['name'], '.pdf')) {
        cv_pdf_acf_redirect_with_message('Invalid file type. Please upload a PDF file.', 'error');
        return;
    }

    // Move uploaded file to temp location
    $upload_dir = wp_upload_dir();
    $temp_file = $upload_dir['basedir'] . '/cv_temp_' . time() . '.pdf';
    
    if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
        cv_pdf_acf_redirect_with_message('Failed to process uploaded file.', 'error');
        return;
    }

    // Extract CV data from PDF
    $cv_data = cv_pdf_acf_extract_cv_from_pdf($temp_file, $api_key, $model);
    
    // Clean up temp file
    if (file_exists($temp_file)) {
        @unlink($temp_file);
    }

    if (is_wp_error($cv_data)) {
        cv_pdf_acf_redirect_with_message('PDF Processing failed: ' . $cv_data->get_error_message(), 'error');
        return;
    }
    
    if (empty($cv_data) || !is_array($cv_data)) {
        cv_pdf_acf_redirect_with_message('Extracted data is empty or invalid. Please check if your PDF contains readable text.', 'error');
        return;
    }

    // Populate source language first
    $source_page_id = $translations[$source_lang];
    $result_source = cv_pdf_acf_populate_fields($source_page_id, $cv_data);
    
    $results = array();
    $results[strtoupper($source_lang)] = $result_source['updated'];
    
    // Translate and populate other languages
    $target_langs = array_diff($required_langs, array($source_lang));
    
    foreach ($target_langs as $target_lang) {
        $translated_data = cv_pdf_acf_translate_cv($cv_data, $target_lang, $api_key, $model);
        
        if (is_wp_error($translated_data)) {
            $results[strtoupper($target_lang)] = array('Translation failed: ' . $translated_data->get_error_message());
            continue;
        }
        
        $target_page_id = $translations[$target_lang];
        $result_target = cv_pdf_acf_populate_fields($target_page_id, $translated_data);
        $results[strtoupper($target_lang)] = $result_target['updated'];
    }

    // Build success message
    $message_parts = array('PDF processed successfully!');
    foreach ($results as $lang => $updated) {
        $message_parts[] = $lang . ': ' . (!empty($updated) ? implode(', ', $updated) : 'No fields');
    }
    
    $message = implode(' | ', $message_parts);
    cv_pdf_acf_redirect_with_message($message, 'success');
}
add_action('admin_post_cv_pdf_acf_upload', 'cv_pdf_acf_handle_upload');

// Helper function to redirect with message
function cv_pdf_acf_redirect_with_message($message, $type = 'success') {
    $referer = wp_get_referer();
    if (!$referer) {
        $referer = admin_url('options-general.php?page=cv-pdf-to-acf-filler');
    }
    
    wp_safe_redirect(add_query_arg(array(
        'cv_pdf_acf_message' => rawurlencode($message),
        'cv_pdf_acf_type' => $type
    ), $referer));
    exit;
}
