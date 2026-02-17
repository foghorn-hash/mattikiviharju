<?php
/**
 * Plugin Name: CV PDF to ACF Filler
 * Description: Extract CV data from PDF files using OpenAI, auto-translate to Fi/En/Sv, populate custom post types (cv_badge, cv_experience, cv_education, cv_course, cv_project, cv_skill) with language-specific content, and export CV as Word document
 * Version: 2.3.0
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

    // Display imported data if available
    $import_results = get_transient('cv_pdf_acf_last_import');
    if ($import_results && is_array($import_results)) {
        delete_transient('cv_pdf_acf_last_import');
        cv_pdf_acf_display_import_results($import_results);
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
        <p>Upload a PDF CV file to extract data and create language-specific custom posts (cv_badge, cv_experience, cv_education, cv_course, cv_project, cv_skill). <strong>Existing posts will be deleted and replaced.</strong></p>
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

// Sanitize text while preserving date formatting
function cv_pdf_acf_sanitize_preserve_dashes($text) {
    if (empty($text)) {
        return '';
    }
    
    // Normalize various dash/hyphen characters to standard ASCII hyphen (-)
    // en dash, em dash, minus sign
    $text = str_replace(
        ["–", "—", "−"],
        "-",
        $text
    );
    
    // Manual sanitization that preserves dashes
    $text = wp_check_invalid_utf8($text);
    $text = wp_strip_all_tags($text);
    // Remove line breaks but preserve dashes and normal whitespace
    $text = preg_replace('/[\r\n\t]+/', ' ', $text);
    // Normalize multiple spaces to single space
    $text = preg_replace('/\s{2,}/', ' ', $text);
    $text = trim($text);
    
    return $text;
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
    {"company": "Company Name", "role": "Job Title", "dates": "Year - Year", "summary": "Job description and achievements"}
  ],
  "education_title": "Education" or appropriate section title,
  "education_items": [
    {"institution": "School/University Name", "degree": "Degree/Certificate Name", "dates": "Year - Year", "description": "Details or achievements"}
  ],
  "courses_title": "Additional training" or "Courses" or appropriate section title,
  "courses": [
    {"title": "Course Name", "provider": "Course Provider", "dates": "Year - Year", "description": "Course details"}
  ],
  "projects_title": "Projects" or appropriate section title,
  "projects": [
    {"title": "Project Name", "meta": "Technology/Role", "description": "Project description"}
  ],
  "skills_title": "Skills" or appropriate section title,
  "skills": ["Category 1: skill1, skill2, skill3, ...", "Category 2: skill4, skill5, skill6, ..."]
}

Extract all relevant information from the CV. If a section is not present, use an empty array or null. Profile badges could include certifications, titles, or key qualifications mentioned at the top of the CV. For experience items, extract the company/employer name separately from the role/job title. For education, extract the institution separately from the degree/program name. For skills, group them by category (e.g., "Full Stack Development", "Scrum Master Expertise", "DevOps", etc.) and format each category as a single line starting with the category name followed by a colon and all related skills separated by commas. Each category should be a separate array item.';

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
function cv_pdf_acf_populate_fields($page_id, $cv_data, $lang = 'fi') {
    $updated = array();
    $created_ids = array(
        'badges' => array(),
        'experience' => array(),
        'education' => array(),
        'courses' => array(),
        'projects' => array(),
        'skills' => array()
    );

    // Update title fields
    if (!empty($cv_data['experience_title'])) {
        update_field('cv_experience_title', sanitize_text_field($cv_data['experience_title']), $page_id);
        $updated[] = 'Experience Title';
    }

    if (!empty($cv_data['projects_title'])) {
        update_field('cv_projects_title', sanitize_text_field($cv_data['projects_title']), $page_id);
        $updated[] = 'Projects Title';
    }

    if (!empty($cv_data['education_title'])) {
        update_field('cv_education_title', sanitize_text_field($cv_data['education_title']), $page_id);
        $updated[] = 'Education Title';
    }

    if (!empty($cv_data['courses_title'])) {
        update_field('cv_courses_title', sanitize_text_field($cv_data['courses_title']), $page_id);
        $updated[] = 'Courses Title';
    }

    if (!empty($cv_data['skills_title'])) {
        update_field('cv_skills_title', sanitize_text_field($cv_data['skills_title']), $page_id);
        $updated[] = 'Skills Title';
    }

    // Create/update custom post types (OVERWRITE existing)
    
    // Profile Badges
    if (!empty($cv_data['profile_badges']) && is_array($cv_data['profile_badges'])) {
        // Delete existing badges for this language
        $existing_badges = get_posts(array(
            'post_type' => 'cv_badge',
            'numberposts' => -1,
            'lang' => $lang,
            'suppress_filters' => false
        ));
        foreach ($existing_badges as $badge) {
            wp_delete_post($badge->ID, true);
        }
        
        // Create new badges
        $menu_order = count($cv_data['profile_badges']);
        foreach ($cv_data['profile_badges'] as $index => $badge) {
            $post_data = array(
                'post_title' => sanitize_text_field($badge),
                'post_type' => 'cv_badge',
                'post_status' => 'publish',
                'menu_order' => $menu_order--
            );
            $post_id = wp_insert_post($post_data);
            if ($post_id && function_exists('pll_set_post_language')) {
                pll_set_post_language($post_id, $lang);
                $created_ids['badges'][$index] = $post_id;
            }
        }
        $updated[] = 'Profile Badges (' . count($cv_data['profile_badges']) . ')';
    }

    // Experience Items
    if (!empty($cv_data['experience_items']) && is_array($cv_data['experience_items'])) {
        // Delete existing experience for this language
        $existing_experience = get_posts(array(
            'post_type' => 'cv_experience',
            'numberposts' => -1,
            'lang' => $lang,
            'suppress_filters' => false
        ));
        foreach ($existing_experience as $exp) {
            wp_delete_post($exp->ID, true);
        }
        
        // Create new experience items
        $menu_order = count($cv_data['experience_items']);
        foreach ($cv_data['experience_items'] as $index => $item) {
            $post_data = array(
                'post_title' => sanitize_text_field($item['role'] ?? ''),
                'post_excerpt' => sanitize_textarea_field($item['summary'] ?? ''),
                'post_type' => 'cv_experience',
                'post_status' => 'publish',
                'menu_order' => $menu_order--
            );
            $post_id = wp_insert_post($post_data);
            if ($post_id) {
                if (!empty($item['company'])) {
                    update_post_meta($post_id, '_cv_experience_company', sanitize_text_field($item['company']));
                }
                if (!empty($item['dates'])) {
                    update_post_meta($post_id, '_cv_experience_dates', cv_pdf_acf_sanitize_preserve_dashes($item['dates']));
                }
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                    $created_ids['experience'][$index] = $post_id;
                }
            }
        }
        $updated[] = 'Experience Items (' . count($cv_data['experience_items']) . ')';
    }

    // Education Items
    if (!empty($cv_data['education_items']) && is_array($cv_data['education_items'])) {
        // Delete existing education for this language
        $existing_education = get_posts(array(
            'post_type' => 'cv_education',
            'numberposts' => -1,
            'lang' => $lang,
            'suppress_filters' => false
        ));
        foreach ($existing_education as $edu) {
            wp_delete_post($edu->ID, true);
        }
        
        // Create new education items
        $menu_order = count($cv_data['education_items']);
        foreach ($cv_data['education_items'] as $index => $item) {
            $post_data = array(
                'post_title' => sanitize_text_field($item['degree'] ?? ''),
                'post_excerpt' => sanitize_text_field($item['institution'] ?? ''),
                'post_content' => sanitize_textarea_field($item['description'] ?? ''),
                'post_type' => 'cv_education',
                'post_status' => 'publish',
                'menu_order' => $menu_order--
            );
            $post_id = wp_insert_post($post_data);
            if ($post_id) {
                if (!empty($item['dates'])) {
                    update_post_meta($post_id, '_cv_education_dates', cv_pdf_acf_sanitize_preserve_dashes($item['dates']));
                }
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                    $created_ids['education'][$index] = $post_id;
                }
            }
        }
        $updated[] = 'Education Items (' . count($cv_data['education_items']) . ')';
    }

    // Courses
    if (!empty($cv_data['courses']) && is_array($cv_data['courses'])) {
        // Delete existing courses for this language
        $existing_courses = get_posts(array(
            'post_type' => 'cv_course',
            'numberposts' => -1,
            'lang' => $lang,
            'suppress_filters' => false
        ));
        foreach ($existing_courses as $course) {
            wp_delete_post($course->ID, true);
        }
        
        // Create new courses
        $menu_order = count($cv_data['courses']);
        foreach ($cv_data['courses'] as $index => $course) {
            $title = !empty($course['title']) ? $course['title'] : '';
            $provider = !empty($course['provider']) ? $course['provider'] : '';
            $dates = !empty($course['dates']) ? $course['dates'] : '';
            $description = !empty($course['description']) ? $course['description'] : '';
            
            $post_data = array(
                'post_title' => sanitize_text_field($title),
                'post_excerpt' => sanitize_text_field($provider),
                'post_content' => sanitize_textarea_field($description),
                'post_type' => 'cv_course',
                'post_status' => 'publish',
                'menu_order' => $menu_order--
            );
            $post_id = wp_insert_post($post_data);
            if ($post_id) {
                if (!empty($dates)) {
                    update_post_meta($post_id, '_cv_course_dates', sanitize_text_field($dates));
                }
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                }
                $created_ids['courses'][$index] = $post_id;
            }
        }
        $updated[] = 'Courses (' . count($cv_data['courses']) . ')';
    }

    // Projects
    if (!empty($cv_data['projects']) && is_array($cv_data['projects'])) {
        // Delete existing projects for this language
        $existing_projects = get_posts(array(
            'post_type' => 'cv_project',
            'numberposts' => -1,
            'lang' => $lang,
            'suppress_filters' => false
        ));
        foreach ($existing_projects as $proj) {
            wp_delete_post($proj->ID, true);
        }
        
        // Create new projects
        $menu_order = count($cv_data['projects']);
        foreach ($cv_data['projects'] as $index => $project) {
            $post_data = array(
                'post_title' => sanitize_text_field($project['title'] ?? ''),
                'post_excerpt' => sanitize_textarea_field($project['description'] ?? ''),
                'post_type' => 'cv_project',
                'post_status' => 'publish',
                'menu_order' => $menu_order--
            );
            $post_id = wp_insert_post($post_data);
            if ($post_id) {
                if (!empty($project['meta'])) {
                    update_post_meta($post_id, '_cv_project_meta', sanitize_text_field($project['meta']));
                }
                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                    $created_ids['projects'][$index] = $post_id;
                }
            }
        }
        $updated[] = 'Projects (' . count($cv_data['projects']) . ')';
    }

    // Skills
    if (!empty($cv_data['skills'])) {
        // Delete existing skills for this language
        $existing_skills = get_posts(array(
            'post_type' => 'cv_skill',
            'numberposts' => -1,
            'lang' => $lang,
            'suppress_filters' => false
        ));
        foreach ($existing_skills as $skill) {
            wp_delete_post($skill->ID, true);
        }
        
        // Create skill posts - one per category line
        if (is_array($cv_data['skills'])) {
            $menu_order = count($cv_data['skills']);
            foreach ($cv_data['skills'] as $index => $skill_line) {
                $post_data = array(
                    'post_title' => sanitize_text_field($skill_line),
                    'post_type' => 'cv_skill',
                    'post_status' => 'publish',
                    'menu_order' => $menu_order--
                );
                $post_id = wp_insert_post($post_data);
                if ($post_id && function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                    $created_ids['skills'][$index] = $post_id;
                }
            }
            $updated[] = 'Skills (' . count($cv_data['skills']) . ' categories)';
        } elseif (is_string($cv_data['skills'])) {
            // Fallback: single string (backward compatibility)
            $post_data = array(
                'post_title' => sanitize_text_field($cv_data['skills']),
                'post_type' => 'cv_skill',
                'post_status' => 'publish',
                'menu_order' => 1
            );
            $post_id = wp_insert_post($post_data);
            if ($post_id && function_exists('pll_set_post_language')) {
                pll_set_post_language($post_id, $lang);
                $created_ids['skills'][0] = $post_id;
            }
            $updated[] = 'Skills (1 line)';
        }
    }

    return array(
        'updated' => $updated,
        'created_ids' => $created_ids
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
    $result_source = cv_pdf_acf_populate_fields($source_page_id, $cv_data, $source_lang);
    
    $results = array();
    $results[strtoupper($source_lang)] = $result_source['updated'];
    
    // Store created post IDs for linking translations
    $all_created_ids = array(
        $source_lang => $result_source['created_ids']
    );
    
    // Store import results for display
    $import_data = array(
        'source_lang' => strtoupper($source_lang),
        'cv_data' => $cv_data,
        'translations' => array(),
        'results' => $results
    );
    
    // Translate and populate other languages
    $target_langs = array_diff($required_langs, array($source_lang));
    
    foreach ($target_langs as $target_lang) {
        $translated_data = cv_pdf_acf_translate_cv($cv_data, $target_lang, $api_key, $model);
        
        if (is_wp_error($translated_data)) {
            $results[strtoupper($target_lang)] = array('Translation failed: ' . $translated_data->get_error_message());
            continue;
        }
        
        // Store translated data for display
        $import_data['translations'][strtoupper($target_lang)] = $translated_data;
        
        $target_page_id = $translations[$target_lang];
        $result_target = cv_pdf_acf_populate_fields($target_page_id, $translated_data, $target_lang);
        $results[strtoupper($target_lang)] = $result_target['updated'];
        
        $all_created_ids[$target_lang] = $result_target['created_ids'];
    }
    
    // Link all language versions in Polylang
    if (function_exists('pll_save_post_translations')) {
        // Link badges
        if (!empty($all_created_ids[$source_lang]['badges'])) {
            foreach ($all_created_ids[$source_lang]['badges'] as $index => $source_id) {
                $translation_ids = array($source_lang => $source_id);
                foreach ($target_langs as $target_lang) {
                    if (!empty($all_created_ids[$target_lang]['badges'][$index])) {
                        $translation_ids[$target_lang] = $all_created_ids[$target_lang]['badges'][$index];
                    }
                }
                pll_save_post_translations($translation_ids);
            }
        }
        
        // Link experience items
        if (!empty($all_created_ids[$source_lang]['experience'])) {
            foreach ($all_created_ids[$source_lang]['experience'] as $index => $source_id) {
                $translation_ids = array($source_lang => $source_id);
                foreach ($target_langs as $target_lang) {
                    if (!empty($all_created_ids[$target_lang]['experience'][$index])) {
                        $translation_ids[$target_lang] = $all_created_ids[$target_lang]['experience'][$index];
                    }
                }
                pll_save_post_translations($translation_ids);
            }
        }
        
        // Link education items
        if (!empty($all_created_ids[$source_lang]['education'])) {
            foreach ($all_created_ids[$source_lang]['education'] as $index => $source_id) {
                $translation_ids = array($source_lang => $source_id);
                foreach ($target_langs as $target_lang) {
                    if (!empty($all_created_ids[$target_lang]['education'][$index])) {
                        $translation_ids[$target_lang] = $all_created_ids[$target_lang]['education'][$index];
                    }
                }
                pll_save_post_translations($translation_ids);
            }
        }
        
        // Link courses
        if (!empty($all_created_ids[$source_lang]['courses'])) {
            foreach ($all_created_ids[$source_lang]['courses'] as $index => $source_id) {
                $translation_ids = array($source_lang => $source_id);
                foreach ($target_langs as $target_lang) {
                    if (!empty($all_created_ids[$target_lang]['courses'][$index])) {
                        $translation_ids[$target_lang] = $all_created_ids[$target_lang]['courses'][$index];
                    }
                }
                pll_save_post_translations($translation_ids);
            }
        }
        
        // Link projects
        if (!empty($all_created_ids[$source_lang]['projects'])) {
            foreach ($all_created_ids[$source_lang]['projects'] as $index => $source_id) {
                $translation_ids = array($source_lang => $source_id);
                foreach ($target_langs as $target_lang) {
                    if (!empty($all_created_ids[$target_lang]['projects'][$index])) {
                        $translation_ids[$target_lang] = $all_created_ids[$target_lang]['projects'][$index];
                    }
                }
                pll_save_post_translations($translation_ids);
            }
        }
        
        // Link skills
        if (!empty($all_created_ids[$source_lang]['skills'])) {
            foreach ($all_created_ids[$source_lang]['skills'] as $index => $source_id) {
                $translation_ids = array($source_lang => $source_id);
                foreach ($target_langs as $target_lang) {
                    if (!empty($all_created_ids[$target_lang]['skills'][$index])) {
                        $translation_ids[$target_lang] = $all_created_ids[$target_lang]['skills'][$index];
                    }
                }
                pll_save_post_translations($translation_ids);
            }
        }
    }
    
    $import_data['results'] = $results;
    set_transient('cv_pdf_acf_last_import', $import_data, 300); // Store for 5 minutes
    
    // Build success message
    $message = 'PDF processed successfully! See imported items below.';
    cv_pdf_acf_redirect_with_message($message, 'success');
}
add_action('admin_post_cv_pdf_acf_upload', 'cv_pdf_acf_handle_upload');

// Display imported CV data
function cv_pdf_acf_display_import_results($import_data) {
    ?>
    <div class="notice notice-success" style="padding: 15px; margin-top: 20px;">
        <h2 style="margin-top: 0;">✓ Imported CV Data (Source: <?php echo esc_html($import_data['source_lang']); ?>)</h2>
        
        <?php
        $cv_data = $import_data['cv_data'];
        $translations = $import_data['translations'];
        
        // Display source language data
        echo '<div style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-left: 4px solid #2271b1;">';
        echo '<h3 style="margin-top: 0;">' . esc_html($import_data['source_lang']) . ' (Source)</h3>';
        cv_pdf_acf_display_cv_content($cv_data);
        echo '</div>';
        
        // Display translations
        foreach ($translations as $lang => $translated_data) {
            echo '<div style="background: #f9f9f9; padding: 15px; margin-bottom: 15px; border-left: 4px solid #2271b1;">';
            echo '<h3 style="margin-top: 0;">' . esc_html($lang) . ' (Translated)</h3>';
            cv_pdf_acf_display_cv_content($translated_data);
            echo '</div>';
        }
        ?>
    </div>
    <?php
}

// Display CV content in a formatted list
function cv_pdf_acf_display_cv_content($cv_data) {
    // Profile Badges
    if (!empty($cv_data['profile_badges']) && is_array($cv_data['profile_badges'])) {
        echo '<h4>Profile Badges</h4>';
        echo '<ul>';
        foreach ($cv_data['profile_badges'] as $badge) {
            echo '<li>' . esc_html($badge) . '</li>';
        }
        echo '</ul>';
    }
    
    // Experience
    if (!empty($cv_data['experience_title']) || !empty($cv_data['experience_items'])) {
        echo '<h4>' . esc_html($cv_data['experience_title'] ?? 'Experience') . '</h4>';
        if (!empty($cv_data['experience_items']) && is_array($cv_data['experience_items'])) {
            echo '<ul>';
            foreach ($cv_data['experience_items'] as $item) {
                echo '<li><strong>' . esc_html($item['role'] ?? '') . '</strong>';
                if (!empty($item['company'])) {
                    echo ' at ' . esc_html($item['company']);
                }
                if (!empty($item['dates'])) {
                    echo ' <em>(' . esc_html($item['dates']) . ')</em>';
                }
                if (!empty($item['summary'])) {
                    echo '<br>' . esc_html($item['summary']);
                }
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    
    // Education
    if (!empty($cv_data['education_title']) || !empty($cv_data['education_items'])) {
        echo '<h4>' . esc_html($cv_data['education_title'] ?? 'Education') . '</h4>';
        if (!empty($cv_data['education_items']) && is_array($cv_data['education_items'])) {
            echo '<ul>';
            foreach ($cv_data['education_items'] as $item) {
                echo '<li><strong>' . esc_html($item['degree'] ?? '') . '</strong>';
                if (!empty($item['institution'])) {
                    echo ' - ' . esc_html($item['institution']);
                }
                if (!empty($item['dates'])) {
                    echo ' <em>(' . esc_html($item['dates']) . ')</em>';
                }
                if (!empty($item['description'])) {
                    echo '<br>' . esc_html($item['description']);
                }
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    
    // Courses
    if (!empty($cv_data['courses_title']) || !empty($cv_data['courses'])) {
        echo '<h4>' . esc_html($cv_data['courses_title'] ?? 'Courses') . '</h4>';
        if (!empty($cv_data['courses']) && is_array($cv_data['courses'])) {
            echo '<ul>';
            foreach ($cv_data['courses'] as $course) {
                echo '<li><strong>' . esc_html($course['title'] ?? '') . '</strong>';
                if (!empty($course['provider'])) {
                    echo ' - ' . esc_html($course['provider']);
                }
                if (!empty($course['dates'])) {
                    echo ' <em>(' . esc_html($course['dates']) . ')</em>';
                }
                if (!empty($course['description'])) {
                    echo '<br>' . esc_html($course['description']);
                }
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    
    // Projects
    if (!empty($cv_data['projects_title']) || !empty($cv_data['projects'])) {
        echo '<h4>' . esc_html($cv_data['projects_title'] ?? 'Projects') . '</h4>';
        if (!empty($cv_data['projects']) && is_array($cv_data['projects'])) {
            echo '<ul>';
            foreach ($cv_data['projects'] as $project) {
                echo '<li><strong>' . esc_html($project['title'] ?? '') . '</strong>';
                if (!empty($project['meta'])) {
                    echo ' <em>(' . esc_html($project['meta']) . ')</em>';
                }
                if (!empty($project['description'])) {
                    echo '<br>' . esc_html($project['description']);
                }
                echo '</li>';
            }
            echo '</ul>';
        }
    }
    
    // Skills
    if (!empty($cv_data['skills_title']) || !empty($cv_data['skills'])) {
        echo '<h4>' . esc_html($cv_data['skills_title'] ?? 'Skills') . '</h4>';
        if (!empty($cv_data['skills'])) {
            if (is_array($cv_data['skills'])) {
                // Array format - each item is a category line
                echo '<ul>';
                foreach ($cv_data['skills'] as $skill_line) {
                    echo '<li>' . esc_html($skill_line) . '</li>';
                }
                echo '</ul>';
            } elseif (is_string($cv_data['skills'])) {
                // Single line format (backward compatibility)
                echo '<p>' . esc_html($cv_data['skills']) . '</p>';
            }
        }
    }
}

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

// Hook to handle PDF export
function cv_pdf_acf_handle_pdf_export() {
    if (!isset($_GET['cv_export_pdf'])) {
        return;
    }

    try {
        // Include FPDF library
        if (!class_exists('FPDF')) {
            require_once __DIR__ . '/fpdf.php';
        }
        
        if (!class_exists('FPDF')) {
            wp_die('FPDF library not found.', 'FPDF Missing', array('response' => 500));
        }

        if (!function_exists('cv_one_pager_front_page_id')) {
            wp_die('Front page ID function not found.', 'Function Missing', array('response' => 500));
        }

        $page_id = cv_one_pager_front_page_id();
        if (!$page_id) {
            wp_die('Front page not found. Please ensure you have a front page set.', 'No Front Page', array('response' => 404));
        }

        $lang = function_exists('pll_current_language') ? pll_current_language() : 'fi';
        
        // Collect CV data from ACF and custom posts
        $cv_data = cv_pdf_acf_collect_cv_data($page_id, $lang);
        
        // Generate PDF document
        $file_path = cv_pdf_acf_generate_pdf_document($cv_data, $lang);
        
        if (is_wp_error($file_path)) {
            wp_die($file_path->get_error_message(), 'Generation Error', array('response' => 500));
        }

        // Send file for download
        cv_pdf_acf_download_file($file_path, 'pdf');
        
    } catch (Exception $e) {
        error_log('CV PDF Export Error: ' . $e->getMessage());
        wp_die('Error generating PDF document: ' . esc_html($e->getMessage()), 'Export Error', array('response' => 500));
    }
}
add_action('template_redirect', 'cv_pdf_acf_handle_pdf_export');

// Collect CV data from ACF fields and custom post types
function cv_pdf_acf_collect_cv_data($page_id, $lang) {
    $data = array(
        'name' => get_bloginfo('name'),
        'hero_headline' => function_exists('get_field') ? get_field('cv_hero_headline', $page_id) : '',
        'hero_intro' => function_exists('get_field') ? get_field('cv_hero_intro', $page_id) : '',
        'profile_title' => function_exists('get_field') ? get_field('cv_profile_title', $page_id) : '',
        'profile_body' => function_exists('get_field') ? get_field('cv_profile_body', $page_id) : '',
        'contact_email' => function_exists('get_field') ? get_field('cv_contact_email', $page_id) : '',
        'contact_phone' => function_exists('get_field') ? get_field('cv_contact_phone', $page_id) : '',
        'contact_linkedin' => function_exists('get_field') ? get_field('cv_contact_linkedin', $page_id) : '',
        'badges' => array(),
        'experience' => array(),
        'education' => array(),
        'courses' => array(),
        'projects' => array(),
        'skills' => array(),
    );

    // Collect badges
    $badges_query = array(
        'post_type' => 'cv_badge',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'DESC',
        'lang' => $lang,
        'suppress_filters' => false
    );
    $badges = get_posts($badges_query);
    foreach ($badges as $badge) {
        $data['badges'][] = get_the_title($badge);
    }

    // Collect experience
    $experience_query = array(
        'post_type' => 'cv_experience',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'DESC',
        'lang' => $lang,
        'suppress_filters' => false
    );
    $experience_items = get_posts($experience_query);
    foreach ($experience_items as $item) {
        $data['experience'][] = array(
            'company' => get_post_meta($item->ID, '_cv_experience_company', true),
            'role' => get_the_title($item),
            'dates' => get_post_meta($item->ID, '_cv_experience_dates', true),
            'summary' => $item->post_excerpt ?: wp_trim_words($item->post_content, 50)
        );
    }

    // Collect education
    $education_query = array(
        'post_type' => 'cv_education',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'DESC',
        'lang' => $lang,
        'suppress_filters' => false
    );
    $education_items = get_posts($education_query);
    foreach ($education_items as $item) {
        $data['education'][] = array(
            'institution' => get_the_title($item),
            'dates' => get_post_meta($item->ID, '_cv_education_dates', true),
            'description' => $item->post_excerpt ?: wp_trim_words($item->post_content, 50)
        );
    }

    // Collect courses
    $courses_query = array(
        'post_type' => 'cv_course',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'DESC',
        'lang' => $lang,
        'suppress_filters' => false
    );
    $courses_items = get_posts($courses_query);
    foreach ($courses_items as $item) {
        $data['courses'][] = array(
            'title' => get_the_title($item),
            'provider' => $item->post_excerpt,
            'dates' => get_post_meta($item->ID, '_cv_course_dates', true),
            'description' => wp_trim_words($item->post_content, 50)
        );
    }

    // Collect projects
    $projects_query = array(
        'post_type' => 'cv_project',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'DESC',
        'lang' => $lang,
        'suppress_filters' => false
    );
    $projects_items = get_posts($projects_query);
    foreach ($projects_items as $item) {
        $data['projects'][] = array(
            'title' => get_the_title($item),
            'meta' => get_post_meta($item->ID, '_cv_project_meta', true),
            'description' => $item->post_excerpt ?: wp_trim_words($item->post_content, 50)
        );
    }

    // Collect skills
    $skills_query = array(
        'post_type' => 'cv_skill',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'DESC',
        'lang' => $lang,
        'suppress_filters' => false
    );
    $skills_items = get_posts($skills_query);
    foreach ($skills_items as $item) {
        $data['skills'][] = get_the_title($item);
    }

    return $data;
}

// Generate PDF document using FPDF
function cv_pdf_acf_generate_pdf_document($cv_data, $lang) {
    try {
        // Helper function to clean text for PDF
        $clean_text = function($text) {
            if (empty($text)) return '';
            $text = wp_strip_all_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            // Convert UTF-8 to ISO-8859-1 for FPDF
            $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
            return trim($text);
        };
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);
        
        // Set metadata
        $pdf->SetAuthor($clean_text($cv_data['name']));
        $pdf->SetTitle('CV - ' . $clean_text($cv_data['name']));
        $pdf->SetCreator('CV PDF Exporter');
        
        // Header - Name and title
        if (!empty($cv_data['hero_headline']) || !empty($cv_data['name'])) {
            $pdf->SetFont('Arial', 'B', 20);
            $pdf->SetTextColor(31, 73, 125); // Blue color
            $pdf->MultiCell(0, 12, $clean_text(!empty($cv_data['hero_headline']) ? $cv_data['hero_headline'] : $cv_data['name']), 0, 'L');
            $pdf->Ln(2);
        }
        
        if (!empty($cv_data['hero_intro'])) {
            $pdf->SetFont('Arial', 'I', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell(0, 6, $clean_text($cv_data['hero_intro']), 0, 'L');
            $pdf->Ln(5);
        }
        
        // Contact information
        if (!empty($cv_data['contact_email']) || !empty($cv_data['contact_phone'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 8, 'Contact', 0, 1);
            $pdf->Ln(1);
            
            $pdf->SetFont('Arial', '', 10);
            if (!empty($cv_data['contact_email'])) {
                $pdf->Cell(0, 5, 'Email: ' . $clean_text($cv_data['contact_email']), 0, 1);
            }
            if (!empty($cv_data['contact_phone'])) {
                $pdf->Cell(0, 5, 'Phone: ' . $clean_text($cv_data['contact_phone']), 0, 1);
            }
            if (!empty($cv_data['contact_linkedin'])) {
                $pdf->Cell(0, 5, 'LinkedIn: ' . $clean_text($cv_data['contact_linkedin']), 0, 1);
            }
            $pdf->Ln(3);
        }
        
        // Profile
        if (!empty($cv_data['profile_title']) || !empty($cv_data['profile_body'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, $clean_text(!empty($cv_data['profile_title']) ? $cv_data['profile_title'] : 'Profile'), 0, 1);
            $pdf->Ln(1);
            
            if (!empty($cv_data['profile_body'])) {
                $pdf->SetFont('Arial', '', 10);
                $pdf->MultiCell(0, 5, $clean_text($cv_data['profile_body']), 0, 'L');
                $pdf->Ln(3);
            }
        }
        
        // Badges
        if (!empty($cv_data['badges']) && is_array($cv_data['badges'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, 'Certifications & Badges', 0, 1);
            $pdf->Ln(1);
            
            $pdf->SetFont('Arial', '', 10);
            foreach ($cv_data['badges'] as $badge) {
                if (!empty($badge)) {
                    $pdf->Cell(10, 5, chr(149), 0, 0); // Bullet point
                    $pdf->MultiCell(0, 5, $clean_text($badge), 0, 'L');
                }
            }
            $pdf->Ln(3);
        }
        
        // Experience
        if (!empty($cv_data['experience']) && is_array($cv_data['experience'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, 'Experience', 0, 1);
            $pdf->Ln(1);
            
            foreach ($cv_data['experience'] as $exp) {
                if (!empty($exp['company'])) {
                    $pdf->SetFont('Arial', 'B', 11);
                    $pdf->Cell(0, 6, $clean_text($exp['company']), 0, 1);
                }
                if (!empty($exp['role'])) {
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->Cell(0, 5, $clean_text($exp['role']), 0, 1);
                }
                if (!empty($exp['dates'])) {
                    $pdf->SetFont('Arial', 'I', 9);
                    $pdf->Cell(0, 5, $clean_text($exp['dates']), 0, 1);
                }
                if (!empty($exp['summary'])) {
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->MultiCell(0, 5, $clean_text($exp['summary']), 0, 'L');
                }
                $pdf->Ln(2);
            }
            $pdf->Ln(1);
        }
        
        // Education
        if (!empty($cv_data['education']) && is_array($cv_data['education'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, 'Education', 0, 1);
            $pdf->Ln(1);
            
            foreach ($cv_data['education'] as $edu) {
                if (!empty($edu['institution'])) {
                    $pdf->SetFont('Arial', 'B', 11);
                    $pdf->Cell(0, 6, $clean_text($edu['institution']), 0, 1);
                }
                if (!empty($edu['dates'])) {
                    $pdf->SetFont('Arial', 'I', 9);
                    $pdf->Cell(0, 5, $clean_text($edu['dates']), 0, 1);
                }
                if (!empty($edu['description'])) {
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->MultiCell(0, 5, $clean_text($edu['description']), 0, 'L');
                }
                $pdf->Ln(2);
            }
            $pdf->Ln(1);
        }
        
        // Courses
        if (!empty($cv_data['courses']) && is_array($cv_data['courses'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, 'Additional Training', 0, 1);
            $pdf->Ln(1);
            
            $pdf->SetFont('Arial', '', 10);
            foreach ($cv_data['courses'] as $course) {
                $course_line = $clean_text($course['title']);
                if (!empty($course['provider'])) {
                    $course_line .= ' - ' . $clean_text($course['provider']);
                }
                if (!empty($course['dates'])) {
                    $course_line .= ' (' . $clean_text($course['dates']) . ')';
                }
                if (!empty($course_line)) {
                    $pdf->Cell(10, 5, chr(149), 0, 0); // Bullet point
                    $pdf->MultiCell(0, 5, $course_line, 0, 'L');
                }
                if (!empty($course['description'])) {
                    $pdf->SetFont('Arial', '', 9);
                    $pdf->SetX(20);
                    $pdf->MultiCell(0, 4, $clean_text($course['description']), 0, 'L');
                    $pdf->SetFont('Arial', '', 10);
                }
            }
            $pdf->Ln(3);
        }
        
        // Projects
        if (!empty($cv_data['projects']) && is_array($cv_data['projects'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, 'Projects', 0, 1);
            $pdf->Ln(1);
            
            foreach ($cv_data['projects'] as $project) {
                $project_title = $clean_text($project['title']);
                if (!empty($project['meta'])) {
                    $project_title .= ' (' . $clean_text($project['meta']) . ')';
                }
                if (!empty($project_title)) {
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->Cell(0, 6, $project_title, 0, 1);
                }
                if (!empty($project['description'])) {
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->MultiCell(0, 5, $clean_text($project['description']), 0, 'L');
                }
                $pdf->Ln(2);
            }
            $pdf->Ln(1);
        }
        
        // Skills
        if (!empty($cv_data['skills']) && is_array($cv_data['skills'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, 'Skills', 0, 1);
            $pdf->Ln(1);
            
            $pdf->SetFont('Arial', '', 10);
            foreach ($cv_data['skills'] as $skill_line) {
                if (!empty($skill_line)) {
                    $pdf->MultiCell(0, 5, $clean_text($skill_line), 0, 'L');
                }
            }
        }
        
        // Save to temporary file
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/cv-exports';
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        if (!is_writable($temp_dir)) {
            return new WP_Error('directory_not_writable', 'Export directory is not writable: ' . $temp_dir);
        }
        
        $filename = 'CV-' . sanitize_file_name($clean_text($cv_data['name'])) . '-' . $lang . '-' . time() . '.pdf';
        $file_path = $temp_dir . '/' . $filename;
        
        $pdf->Output('F', $file_path);
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_created', 'Failed to create PDF document');
        }
        
        return $file_path;
        
    } catch (Exception $e) {
        error_log('PDF document generation error: ' . $e->getMessage());
        return new WP_Error('generation_failed', 'Failed to generate document: ' . $e->getMessage());
    }
}

// Download file and clean up
function cv_pdf_acf_download_file($file_path, $file_type = 'pdf') {
    if (!file_exists($file_path)) {
        wp_die('File not found');
    }
    
    $filename = basename($file_path);
    
    // Clean any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set content type based on file type
    $content_type = $file_type === 'pdf' 
        ? 'application/pdf' 
        : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    
    // Set headers for download
    header('Content-Description: File Transfer');
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file_path));
    
    // Output file
    flush();
    readfile($file_path);
    
    // Clean up temporary file
    @unlink($file_path);
    exit;
}
