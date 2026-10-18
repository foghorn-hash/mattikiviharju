<?php
/**
 * Plugin Name: CV PDF & DOCX to ACF Filler
 * Description: Extract CV data from PDF or Word (.docx) files using OpenAI, import standalone projects and work experience from PDF or Word (.docx), auto-translate to Fi/En/Sv, populate custom post types (cv_badge, cv_experience, cv_education, cv_course, cv_project, cv_skill, cv_reference) with language-specific content, extract embedded project PNG images and YouTube video links, and export CV as PDF document
 * Version: 2.7.0
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
function cv_pdf_acf_register_settings()
{
    register_setting('cv_pdf_acf_settings', 'cv_pdf_acf_api_key');
    register_setting('cv_pdf_acf_settings', 'cv_pdf_acf_model');
}
add_action('admin_init', 'cv_pdf_acf_register_settings');

// Add settings page to admin menu
function cv_pdf_acf_add_settings_page()
{
    add_options_page(
        'CV PDF & DOCX to ACF Filler',
        'CV PDF to ACF',
        'manage_options',
        'cv-pdf-to-acf-filler',
        'cv_pdf_acf_render_settings_page'
    );
}
add_action('admin_menu', 'cv_pdf_acf_add_settings_page');

// Render settings and upload page
function cv_pdf_acf_render_settings_page()
{
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

    // Display imported project data if available
    $project_import_results = get_transient('cv_pdf_acf_last_project_import');
    if ($project_import_results && is_array($project_import_results)) {
        delete_transient('cv_pdf_acf_last_project_import');
        cv_pdf_acf_display_project_import_results($project_import_results);
    }

    // Display imported experience data if available
    $experience_import_results = get_transient('cv_pdf_acf_last_experience_import');
    if ($experience_import_results && is_array($experience_import_results)) {
        delete_transient('cv_pdf_acf_last_experience_import');
        cv_pdf_acf_display_experience_import_results($experience_import_results);
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
        echo '<div class="notice notice-warning"><p><strong>Warning:</strong> No PDF parsing library found (PDF uploads won\'t work). You can still upload Word (.docx) files.<br>';
        echo 'To enable PDF: Run <code>composer require smalot/pdfparser</code> in the plugin directory or install <code>pdftotext</code>.</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>PDF Parser:</strong> ' . esc_html($pdf_method) . ' is available ✓</p></div>';
    }

    // Check DOCX parsing capability
    $docx_method = cv_pdf_acf_check_docx_capability();
    if (!$docx_method) {
        echo '<div class="notice notice-error"><p><strong>Error:</strong> PHP ZipArchive extension is not available. Please enable php-zip to process Word (.docx) files.</p></div>';
        if (!$pdf_method) {
            $dependencies_ok = false;
        }
    } else {
        echo '<div class="notice notice-success"><p><strong>Word DOCX Parser:</strong> ' . esc_html($docx_method) . ' is available ✓ (Extracts text, links, and embedded PNG images)</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>CV PDF & Word (.docx) to ACF Filler</h1>

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
                <th scope="row">Word (.docx) Parser</th>
                <td><?php echo $docx_method ? '✓ ' . esc_html($docx_method) : '✗ Not available'; ?></td>
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
                    <td><input type="password" id="cv_pdf_acf_api_key" name="cv_pdf_acf_api_key"
                            value="<?php echo esc_attr($api_key); ?>" class="regular-text" autocomplete="off" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cv_pdf_acf_model">Model</label></th>
                    <td>
                        <input type="text" id="cv_pdf_acf_model" name="cv_pdf_acf_model"
                            value="<?php echo esc_attr($model); ?>" class="regular-text" />
                        <p class="description">Recommended: gpt-4o, gpt-4o-mini, or gpt-4-turbo</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>

        <hr />

        <h2>Upload CV (PDF or Word .docx)</h2>
        <?php if (!$dependencies_ok): ?>
            <div class="notice notice-warning">
                <p><strong>Warning:</strong> Please resolve the errors above before uploading a CV file.</p>
            </div>
        <?php else: ?>
            <p>Upload a PDF or Word (.docx) CV file to extract structured CV data, images, YouTube video links, and create
                language-specific custom posts (cv_badge, cv_experience, cv_education, cv_course, cv_project, cv_skill).
                <strong>Existing posts will be deleted and replaced.</strong></p>
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
                        <th scope="row"><label for="cv_pdf_file">CV File (.pdf or .docx)</label></th>
                        <td>
                            <input type="file" id="cv_pdf_file" name="cv_pdf_file"
                                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                required />
                            <p class="description">Select a PDF or Word (.docx) file containing CV information. If you upload a
                                .docx file with embedded PNG/JPEG screenshots and YouTube links, they will be automatically
                                processed into project media!</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Source Language</th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_source_language" value="fi" checked />
                                Finnish (populate Fi, translate to En & Sv)
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_source_language" value="en" />
                                English (populate En, translate to Fi & Sv)
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="cv_source_language" value="sv" />
                                Swedish (populate Sv, translate to Fi & En)
                            </label>
                            <p class="description">Select the original language of your uploaded CV file</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Upload and Process CV'); ?>
            </form>

            <hr style="margin: 30px 0 20px;" />

            <h2>Lisää projekteja Word-tiedostosta (.docx) / Add Projects from Word (.docx)</h2>
            <p>
                Tällä toiminnolla voit tuoda ja lisätä <strong>pelkät projektit</strong> Word (.docx) -tiedostosta suoraan <code>cv_project</code>-artikkeleiksi
                koskematta muihin CV-osioihin (kuten työkokemukseen, koulutukseen tai taitoihin).
            </p>

            <div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 12px 16px; margin-bottom: 20px; border-radius: 2px;">
                <p style="margin: 0; font-weight: 600; color: #1d2327;">Toiminnon ominaisuudet:</p>
                <ul style="margin: 8px 0 0 20px; list-style-type: disc; color: #3c434a;">
                    <li>Purkaa .docx-tiedostosta projektien <strong>otsikot, teknologiat (tech stack), kuvaukset, verkkolinkit ja useat YouTube-videot</strong>.</li>
                    <li>Tuo automaattisesti kaikki dokumenttiin upotetut <strong>PNG-, JPEG- ja WebP-kuvat</strong> WordPressin mediakirjastoon ja asettaa ne projektin kuvagalleriaksi sekä artikkelikuvaksi.</li>
                    <li>Kääntää projektit automaattisesti <strong>suomeksi, englanniksi ja ruotsiksi</strong> ja linkittää kieliversiot toisiinsa Polylangilla.</li>
                    <li>Voit valita, <strong>lisätäänkö uudet projektit</strong> aiempien jatkoksi (suositus) vai <strong>korvataanko</strong> vain aiemmat projektit.</li>
                </ul>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('cv_pdf_acf_upload_projects'); ?>
                <input type="hidden" name="action" value="cv_pdf_acf_upload_projects" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="cv_projects_docx_file">Word-tiedosto (.docx)</label></th>
                        <td>
                            <input type="file" id="cv_projects_docx_file" name="cv_projects_docx_file"
                                accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                required />
                            <p class="description">Valitse .docx-tiedosto, joka sisältää projektikuvauksia, linkkejä, YouTube-videoita tai kuvakaappauksia.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lähdekieli / Source Language</th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_projects_source_language" value="fi" checked />
                                Suomi / Finnish (luo Fi ja kääntää En & Sv)
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_projects_source_language" value="en" />
                                Englanti / English (luo En ja kääntää Fi & Sv)
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="cv_projects_source_language" value="sv" />
                                Ruotsi / Swedish (luo Sv ja kääntää Fi & En)
                            </label>
                            <p class="description">Valitse ladatun projektitiedoston alkuperäinen kieli</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tuontitapa / Import Mode</th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_projects_mode" value="append" checked />
                                <strong>Lisää olemassa olevien jatkoksi</strong> (Säilyttää kaikki aiemmat projektit ja asettaa uudet näkyviin alkuun)
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="cv_projects_mode" value="replace" />
                                <strong>Korvaa aiemmat projektit</strong> (Poistaa vain aiemmat cv_project-postaukset; ei koske kokemusta, koulutusta tai taitoja)
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Tuo projektit (.docx) / Import Projects', 'primary', 'cv_projects_submit'); ?>
            </form>

            <hr style="margin: 30px 0 20px;" />

            <h2>Lisää työkokemuksia PDF- tai Word-tiedostosta (.pdf / .docx) / Add Work Experience from PDF or Word (.pdf / .docx)</h2>
            <p>
                Tällä toiminnolla voit tuoda ja lisätä <strong>pelkät työpaikat ja työkokemukset</strong> PDF- tai Word (.docx) -tiedostosta suoraan <code>cv_experience</code>-artikkeleiksi
                koskematta muihin CV-osioihin (kuten koulutukseen, kursseihin, projekteihin tai taitoihin).
            </p>

            <div style="background: #f0fdf4; border-left: 4px solid #16a34a; padding: 12px 16px; margin-bottom: 20px; border-radius: 2px;">
                <p style="margin: 0; font-weight: 600; color: #166534;">Toiminnon ominaisuudet:</p>
                <ul style="margin: 8px 0 0 20px; list-style-type: disc; color: #1e293b;">
                    <li>Tukee sekä <strong>PDF- että Word (.docx)</strong> -tiedostoja.</li>
                    <li>Tunnistaa ja purkaa dokumentista työkokemusten <strong>yrityksen / työnantajan nimen, työtehtävän (titteli/rooli), ajankohdan (päivämäärät/vuodet) ja työtehtävien kuvauksen</strong>.</li>
                    <li>Kääntää työpaikat automaattisesti <strong>suomeksi, englanniksi ja ruotsiksi</strong> ja linkittää kieliversiot toisiinsa Polylangilla.</li>
                    <li>Voit valita, <strong>lisätäänkö uudet työkokemukset</strong> aiempien jatkoksi (suositus) vai <strong>korvataanko</strong> vain aiemmat työkokemukset.</li>
                </ul>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('cv_pdf_acf_upload_experience'); ?>
                <input type="hidden" name="action" value="cv_pdf_acf_upload_experience" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="cv_experience_file">CV-tiedosto (.pdf tai .docx)</label></th>
                        <td>
                            <input type="file" id="cv_experience_file" name="cv_experience_file"
                                accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                required />
                            <p class="description">Valitse PDF- tai Word (.docx) -tiedosto, joka sisältää työkokemustietoja.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Lähdekieli / Source Language</th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_experience_source_language" value="fi" checked />
                                Suomi / Finnish (luo Fi ja kääntää En & Sv)
                            </label>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_experience_source_language" value="en" />
                                Englanti / English (luo En ja kääntää Fi & Sv)
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="cv_experience_source_language" value="sv" />
                                Ruotsi / Swedish (luo Sv ja kääntää Fi & En)
                            </label>
                            <p class="description">Valitse ladatun tiedoston alkuperäinen kieli</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tuontitapa / Import Mode</th>
                        <td>
                            <label style="display: block; margin-bottom: 8px;">
                                <input type="radio" name="cv_experience_mode" value="append" checked />
                                <strong>Lisää olemassa olevien jatkoksi</strong> (Säilyttää kaikki aiemmat työkokemukset ja asettaa uudet näkyviin alkuun)
                            </label>
                            <label style="display: block;">
                                <input type="radio" name="cv_experience_mode" value="replace" />
                                <strong>Korvaa aiemmat työkokemukset</strong> (Poistaa vain aiemmat cv_experience-postaukset; ei koske projekteja, koulutusta, kursseja tai taitoja)
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Tuo työkokemukset (.pdf / .docx) / Import Work Experience', 'primary', 'cv_experience_submit'); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

// Check PDF parsing capability
function cv_pdf_acf_check_pdf_capability()
{
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

// Check Word docx parsing capability
function cv_pdf_acf_check_docx_capability()
{
    if (class_exists('ZipArchive') && class_exists('DOMDocument')) {
        return 'PHP ZipArchive & DOMDocument (Native)';
    }
    return false;
}

// Call OpenAI API
function cv_pdf_acf_call_openai($payload, $api_key)
{
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
function cv_pdf_acf_sanitize_preserve_dashes($text)
{
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
function cv_pdf_acf_parse_json($content)
{
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
function cv_pdf_acf_extract_pdf_text($file_path)
{
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

// Extract text, hyperlinks (YouTube URLs), and embedded images from Word DOCX
function cv_pdf_acf_extract_docx_data($file_path)
{
    if (!class_exists('ZipArchive')) {
        return new WP_Error('docx_zip_missing', 'PHP ZipArchive extension is not enabled on this server.');
    }
    if (!class_exists('DOMDocument')) {
        return new WP_Error('docx_dom_missing', 'PHP DOM extension (DOMDocument) is not enabled on this server.');
    }

    $zip = new ZipArchive();
    if ($zip->open($file_path) !== true) {
        return new WP_Error('docx_open_failed', 'Failed to open the Word DOCX file.');
    }

    // 1. Read relationships to get hyperlinks (including YouTube links) and media references
    $rels_xml = $zip->getFromName('word/_rels/document.xml.rels');
    $hyperlinks = array();
    $media_rels = array();
    if ($rels_xml) {
        $dom_rels = new DOMDocument();
        @$dom_rels->loadXML($rels_xml);
        $rels = $dom_rels->getElementsByTagName('Relationship');
        foreach ($rels as $rel) {
            $r_id = $rel->getAttribute('Id');
            $type = $rel->getAttribute('Type');
            $target = $rel->getAttribute('Target');
            if (strpos($type, 'relationships/hyperlink') !== false) {
                $hyperlinks[$r_id] = $target;
            } elseif (strpos($type, 'relationships/image') !== false) {
                $media_rels[$r_id] = $target;
            }
        }
    }

    // 2. Read document XML for text content
    $doc_xml = $zip->getFromName('word/document.xml');
    if (!$doc_xml) {
        $zip->close();
        return new WP_Error('docx_no_content', 'Could not find word/document.xml inside the Word file.');
    }

    $dom = new DOMDocument();
    @$dom->loadXML($doc_xml);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
    $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

    $paragraphs = $xpath->query('//w:p');
    $extracted_lines = array();

    foreach ($paragraphs as $p) {
        $p_text = '';
        $nodes = $xpath->query('.//w:t | .//w:hyperlink | .//a:blip', $p);
        foreach ($nodes as $node) {
            if ($node->nodeName === 'w:hyperlink') {
                $r_id = $node->getAttribute('r:id') ?: $node->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
                $target_url = isset($hyperlinks[$r_id]) ? $hyperlinks[$r_id] : '';
                $link_text = $node->textContent;
                $p_text .= ' ' . $link_text . ($target_url ? ' (' . $target_url . ') ' : '');
            } elseif ($node->nodeName === 'a:blip') {
                $r_embed = $node->getAttribute('r:embed') ?: $node->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
                $target_media = isset($media_rels[$r_embed]) ? basename($media_rels[$r_embed]) : '';
                if (!empty($target_media)) {
                    $p_text .= ' [Embedded Image: ' . $target_media . '] ';
                }
            } else {
                // If w:t is inside a w:hyperlink, skip it to avoid duplicate link text
                $parent = $node->parentNode;
                $inside_link = false;
                while ($parent && $parent !== $p) {
                    if ($parent->nodeName === 'w:hyperlink') {
                        $inside_link = true;
                        break;
                    }
                    $parent = $parent->parentNode;
                }
                if (!$inside_link) {
                    $p_text .= $node->textContent;
                }
            }
        }
        $p_text = trim($p_text);
        if ($p_text !== '') {
            $extracted_lines[] = $p_text;
        }
    }

    $full_text = implode("\n", $extracted_lines);

    // 3. Extract embedded images (PNG, JPEG, WebP, GIF) from word/media/
    $uploaded_image_ids = array();
    $image_map = array();

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $filename = $stat['name'];

        if (preg_match('#^word/media/(.+\.(png|jpe?g|webp|gif))$#i', $filename, $matches)) {
            $image_basename = basename($matches[1]);
            $image_content = $zip->getFromIndex($i);

            if ($image_content) {
                $upload = wp_upload_bits($image_basename, null, $image_content);
                if (empty($upload['error'])) {
                    $attachment = array(
                        'post_mime_type' => $upload['type'],
                        'post_title' => preg_replace('/\.[^.]+$/', '', $image_basename),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attach_id = wp_insert_attachment($attachment, $upload['file']);
                    if ($attach_id && !is_wp_error($attach_id)) {
                        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        $uploaded_image_ids[] = $attach_id;
                        $image_map[$image_basename] = $attach_id;
                    }
                }
            }
        }
    }

    $zip->close();

    return array(
        'text' => $full_text,
        'uploaded_image_ids' => $uploaded_image_ids,
        'image_map' => $image_map,
    );
}

// Extract CV data from PDF or Word DOCX using OpenAI
function cv_pdf_acf_extract_cv_from_file($file_path, $file_extension, $api_key, $model)
{
    $uploaded_image_ids = array();
    $image_map = array();
    $extracted_text = '';

    if (strtolower($file_extension) === 'docx') {
        $docx_res = cv_pdf_acf_extract_docx_data($file_path);
        if (is_wp_error($docx_res)) {
            return $docx_res;
        }
        $extracted_text = $docx_res['text'];
        $uploaded_image_ids = $docx_res['uploaded_image_ids'];
        $image_map = $docx_res['image_map'] ?? array();
    } else {
        $pdf_text = cv_pdf_acf_extract_pdf_text($file_path);
        if (is_wp_error($pdf_text)) {
            return $pdf_text;
        }
        $extracted_text = $pdf_text;
    }

    if (empty(trim($extracted_text))) {
        return new WP_Error('empty_content', 'Uploaded document contains no readable text.');
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
    {"title": "Course Name", "provider": "Course Provider / School / Organization", "dates": "Year - Year", "description": "Course details"}
  ],
  "projects_title": "Projects" or appropriate section title,
  "projects": [
    {
      "title": "Project Name",
      "meta": "Technology/Tech Stack / Role",
      "description": "Project description",
      "url": "https://...",
      "url_title": "Project link label/title",
      "youtube_urls": ["https://www.youtube.com/watch?v=...", "https://youtu.be/..."],
      "image_filenames": ["image1.png"]
    }
  ],
  "skills_title": "Skills" or appropriate section title,
  "skills": ["Category 1: skill1, skill2, skill3, ...", "Category 2: skill4, skill5, skill6, ..."],
  "references_title": "References" or "Suosittelijat" or appropriate section title,
  "references": [
    {"name": "Recommender Name", "title": "Job Title", "company": "Company Name", "email": "Email", "phone": "Phone"}
  ]
}

Extract all relevant information from the CV. If a section is not present, use an empty array or null. Profile badges could include certifications, titles, or key qualifications mentioned at the top of the CV. For experience items, extract the company/employer name separately into "company" and the role/job title into "role". For education, extract the institution separately into "institution" and the degree/program name into "degree". For courses/additional training, extract the course name into "title", the provider or organizing school/institution into "provider", and the completion year/dates into "dates". For projects, extract project URLs (e.g. GitHub or live websites) into "url", link label into "url_title", all YouTube video URLs associated with each project into the "youtube_urls" array (projects can have multiple YouTube video links; also check if a video URL is in the project description or notes), and if there are tags like [Embedded Image: filename.png] in or near the project, include those filenames in "image_filenames". For skills, group them by category (e.g., "Full Stack Development", "Scrum Master Expertise", "DevOps", etc.) and format each category as a single line starting with the category name followed by a colon and all related skills separated by commas. For references, extract full name into "name", job title into "title", company into "company", email into "email", and phone into "phone".';

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
                'content' => "Please extract all CV information from this text and return it as structured JSON:\n\n" . $extracted_text
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

    return array(
        'cv_data' => $data,
        'uploaded_image_ids' => $uploaded_image_ids,
        'image_map' => $image_map,
    );
}

// Backward compatibility wrapper
function cv_pdf_acf_extract_cv_from_pdf($file_path, $api_key, $model)
{
    $result = cv_pdf_acf_extract_cv_from_file($file_path, 'pdf', $api_key, $model);
    if (is_wp_error($result)) {
        return $result;
    }
    return $result['cv_data'];
}

// Translate CV data between languages
function cv_pdf_acf_translate_cv($cv_data, $target_lang, $api_key, $model)
{
    $lang_names = array(
        'fi' => 'Finnish',
        'en' => 'English',
        'sv' => 'Swedish'
    );

    $target_name = $lang_names[$target_lang] ?? $target_lang;

    // Create prompt for translation
    $system_prompt = "You are a professional translator specializing in CV/resume translation. Translate all the provided CV content to {$target_name}. Maintain professional tone and accuracy. Keep all URLs, youtube_urls arrays, image_filenames arrays, link URLs, tech stack names, and JSON structure exactly intact. Translate section titles, job titles, summaries, degrees, descriptions, and skill category headings to {$target_name}. Return the same JSON structure with all text translated to {$target_name}.";

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

    // Ensure image_filenames, youtube_urls, and project url are preserved even if AI omitted them
    if (!empty($cv_data['projects']) && is_array($cv_data['projects']) && !empty($translated_data['projects']) && is_array($translated_data['projects'])) {
        foreach ($cv_data['projects'] as $p_idx => $orig_proj) {
            if (isset($translated_data['projects'][$p_idx])) {
                if (!empty($orig_proj['image_filenames']) && empty($translated_data['projects'][$p_idx]['image_filenames'])) {
                    $translated_data['projects'][$p_idx]['image_filenames'] = $orig_proj['image_filenames'];
                }
                if (!empty($orig_proj['youtube_urls']) && empty($translated_data['projects'][$p_idx]['youtube_urls'])) {
                    $translated_data['projects'][$p_idx]['youtube_urls'] = $orig_proj['youtube_urls'];
                }
                if (!empty($orig_proj['url']) && empty($translated_data['projects'][$p_idx]['url'])) {
                    $translated_data['projects'][$p_idx]['url'] = $orig_proj['url'];
                }
            }
        }
    }

    return $translated_data;
}

/**
 * Extract structured projects from document text using OpenAI.
 */
function cv_pdf_acf_extract_projects_from_docx_text($extracted_text, $api_key, $model)
{
    $system_prompt = 'You are an expert software project and portfolio data extractor.
Your task is to extract ONLY the projects from the provided text into structured JSON.
The text comes from a Word (.docx) document and contains descriptions of one or more projects, apps, case studies, or portfolio items.
It may include embedded image tags such as "[Embedded Image: image1.png]" and hyperlinks or plain URLs, including YouTube links.

Return ONLY a JSON object with this exact structure:
{
  "projects_title": "Projects" (or "Projektit" if original language is Finnish),
  "projects": [
    {
      "title": "Project Name",
      "meta": "Technologies, frameworks, tech stack, or role (e.g. React, TypeScript, Node.js, AWS)",
      "description": "Comprehensive project description, goals, achievements, features, architecture details",
      "url": "https://... (Website or GitHub repository link, NOT YouTube)",
      "url_title": "Link label/title (e.g. GitHub, Live Demo, Verkkosivusto)",
      "youtube_urls": ["https://www.youtube.com/watch?v=..."],
      "image_filenames": ["image1.png"]
    }
  ]
}

Extraction guidelines:
1. Extract ALL projects found in the document. If the document describes a single project in detail, return an array with that single project. If it describes several projects, extract all of them.
2. For each project, extract any technology stack, programming languages, libraries, tools, or role mentioned into "meta".
3. For project description, provide a thorough and well-written text. Do not omit important details.
4. Extract primary project URLs (live sites, staging, repositories, app stores) into "url", and a concise label for it into "url_title".
5. For YouTube videos: Look for any YouTube URLs (youtube.com or youtu.be) in links, text, or footnotes related to the project and put them in "youtube_urls". A project can have multiple YouTube videos!
6. For image filenames: If there are markers like "[Embedded Image: filename.ext]" near or within the project section, include those filenames in the "image_filenames" array so they can be matched with the uploaded project screenshots.';

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
                'content' => "Please extract all project information from this text and return it as structured JSON:\n\n" . $extracted_text
            ),
        ),
    );

    $content = cv_pdf_acf_call_openai($payload, $api_key);
    if (is_wp_error($content)) {
        return $content;
    }

    $data = cv_pdf_acf_parse_json($content);
    if (!$data || !isset($data['projects']) || !is_array($data['projects'])) {
        return new WP_Error('openai_parse', 'Failed to parse projects JSON response from OpenAI.');
    }

    return $data;
}

/**
 * Translate projects data between languages using OpenAI.
 */
function cv_pdf_acf_translate_projects($projects_data, $target_lang, $api_key, $model)
{
    $lang_names = array(
        'fi' => 'Finnish',
        'en' => 'English',
        'sv' => 'Swedish'
    );

    $target_name = $lang_names[$target_lang] ?? $target_lang;

    $system_prompt = "You are a professional translator specializing in software portfolio and CV projects translation.
Translate the provided projects data to {$target_name}.
Maintain professional tone, accurate technical terminology, and high quality.
Keep all URLs, youtube_urls arrays, image_filenames arrays, and JSON keys exactly intact.
Translate \"title\", \"meta\" (keep standard technology names like React, Python, Docker as-is, translate descriptive roles/terms), \"description\", \"url_title\", and \"projects_title\" to {$target_name}.
Return the same JSON structure with the text translated to {$target_name}.";

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
                'content' => "Please translate these projects to {$target_name}, keeping the exact same JSON structure:\n\n" . wp_json_encode($projects_data)
            ),
        ),
    );

    $content = cv_pdf_acf_call_openai($payload, $api_key);
    if (is_wp_error($content)) {
        return $content;
    }

    $translated_data = cv_pdf_acf_parse_json($content);
    if (!$translated_data || empty($translated_data['projects']) || !is_array($translated_data['projects'])) {
        return new WP_Error('openai_translate_parse', 'Failed to parse translated projects response.');
    }

    // Ensure image_filenames, youtube_urls, and project url are preserved even if AI omitted them
    if (!empty($projects_data['projects']) && is_array($projects_data['projects'])) {
        foreach ($projects_data['projects'] as $p_idx => $orig_proj) {
            if (isset($translated_data['projects'][$p_idx])) {
                if (!empty($orig_proj['image_filenames']) && empty($translated_data['projects'][$p_idx]['image_filenames'])) {
                    $translated_data['projects'][$p_idx]['image_filenames'] = $orig_proj['image_filenames'];
                }
                if (!empty($orig_proj['youtube_urls']) && empty($translated_data['projects'][$p_idx]['youtube_urls'])) {
                    $translated_data['projects'][$p_idx]['youtube_urls'] = $orig_proj['youtube_urls'];
                }
                if (!empty($orig_proj['url']) && empty($translated_data['projects'][$p_idx]['url'])) {
                    $translated_data['projects'][$p_idx]['url'] = $orig_proj['url'];
                }
            }
        }
    }

    return $translated_data;
}

/**
 * Extract structured work experience from text using OpenAI.
 */
function cv_pdf_acf_extract_experience_from_text($extracted_text, $api_key, $model)
{
    $system_prompt = 'You are an expert CV and resume data extractor specializing in professional work experience.
Your task is to extract ONLY the work experience / job history items from the provided text into structured JSON.
Ignore education, courses, standalone portfolio projects, and skills sections unless they are directly described as part of an employment / job position.

Return ONLY a JSON object with this exact structure:
{
  "experience_title": "Työkokemus" (or "Experience" / "Arbetslivserfarenhet"),
  "experience_items": [
    {
      "company": "Company / Organization / Employer Name",
      "role": "Job Title / Role (e.g. Senior Full Stack Developer)",
      "dates": "Start - End (e.g. 2021 - 2024, or 01/2020 - Nykyhetki / Present)",
      "summary": "Detailed job summary, responsibilities, accomplishments, and technologies used"
    }
  ]
}

Extraction guidelines:
1. Extract ALL work experience / employment positions found in the document.
2. For each experience, extract the employer/company name into "company" and the role/job title into "role".
3. Extract duration / dates into "dates" (preserve date formats, month/year ranges, and dash separators).
4. Provide a thorough, professional description in "summary". Include responsibilities, achievements, and tech stack mentioned in connection with that job.
5. Set "experience_title" appropriately based on the language of the source text.';

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
                'content' => "Please extract all work experience items from this text and return it as structured JSON:\n\n" . $extracted_text
            ),
        ),
    );

    $content = cv_pdf_acf_call_openai($payload, $api_key);
    if (is_wp_error($content)) {
        return $content;
    }

    $data = cv_pdf_acf_parse_json($content);
    if (!$data || !isset($data['experience_items']) || !is_array($data['experience_items'])) {
        return new WP_Error('openai_parse', 'Failed to parse work experience JSON response from OpenAI.');
    }

    return $data;
}

/**
 * Translate work experience data between languages using OpenAI.
 */
function cv_pdf_acf_translate_experience($experience_data, $target_lang, $api_key, $model)
{
    $lang_names = array(
        'fi' => 'Finnish',
        'en' => 'English',
        'sv' => 'Swedish'
    );

    $target_name = $lang_names[$target_lang] ?? $target_lang;

    $system_prompt = "You are a professional CV translator specializing in work experience translation.
Translate the provided work experience data to {$target_name}.
Maintain professional tone, accurate technical terminology, and high quality.
Keep company names ('company') intact unless there is a standard recognized translated name.
Translate 'role', 'summary', and 'experience_title' to {$target_name}.
Ensure date terminology (such as 'Present', 'Nykyhetki', 'Nuvarande', month names) is translated appropriately to {$target_name}.
Return the exact same JSON structure with the text translated to {$target_name}.";

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
                'content' => "Please translate this work experience data to {$target_name}, keeping the exact same JSON structure:\n\n" . wp_json_encode($experience_data)
            ),
        ),
    );

    $content = cv_pdf_acf_call_openai($payload, $api_key);
    if (is_wp_error($content)) {
        return $content;
    }

    $translated_data = cv_pdf_acf_parse_json($content);
    if (!$translated_data || empty($translated_data['experience_items']) || !is_array($translated_data['experience_items'])) {
        return new WP_Error('openai_translate_parse', 'Failed to parse translated work experience response.');
    }

    return $translated_data;
}

/**
 * Insert a single cv_experience post with metadata and language.
 */
function cv_pdf_acf_save_single_experience($item, $lang, $menu_order)
{
    $post_data = array(
        'post_title' => sanitize_text_field($item['role'] ?? ''),
        'post_excerpt' => sanitize_textarea_field($item['summary'] ?? ''),
        'post_content' => sanitize_textarea_field($item['summary'] ?? ''),
        'post_type' => 'cv_experience',
        'post_status' => 'publish',
        'menu_order' => $menu_order
    );

    $post_id = wp_insert_post($post_data);
    if (!$post_id || is_wp_error($post_id)) {
        return false;
    }

    $company = sanitize_text_field($item['company'] ?? '');
    if (!empty($company)) {
        update_post_meta($post_id, '_cv_experience_company', $company);
        update_post_meta($post_id, 'cv_experience_company', $company);
    }

    $dates = cv_pdf_acf_sanitize_preserve_dashes($item['dates'] ?? '');
    if (!empty($dates)) {
        update_post_meta($post_id, '_cv_experience_dates', $dates);
        update_post_meta($post_id, 'cv_experience_dates', $dates);
    }

    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($post_id, $lang);
    }

    return $post_id;
}

/**
 * Insert or update a single cv_project post with metadata, screenshots, link, and YouTube videos.
 */
function cv_pdf_acf_save_single_project($project, $lang, $menu_order, $uploaded_image_ids = array(), $image_map = array(), $index = 0, $total_projects = 1)
{
    $post_data = array(
        'post_title' => sanitize_text_field($project['title'] ?? ''),
        'post_excerpt' => str_replace(array("\xc2\xa0", "\u{00a0}", "\u{200b}", "\u{202f}", "\u{feff}"), ' ', sanitize_textarea_field($project['description'] ?? '')),
        'post_content' => str_replace(array("\xc2\xa0", "\u{00a0}", "\u{200b}", "\u{202f}", "\u{feff}"), ' ', sanitize_textarea_field($project['description'] ?? '')),
        'post_type' => 'cv_project',
        'post_status' => 'publish',
        'menu_order' => $menu_order
    );

    $post_id = wp_insert_post($post_data);
    if (!$post_id || is_wp_error($post_id)) {
        return false;
    }

    $proj_meta = sanitize_text_field($project['meta'] ?? '');
    if (!empty($proj_meta)) {
        update_post_meta($post_id, '_cv_project_meta', $proj_meta);
        update_post_meta($post_id, 'cv_project_meta', $proj_meta);
    }

    $clean_desc = $post_data['post_excerpt'];
    update_post_meta($post_id, '_cv_project_description', $clean_desc);
    update_post_meta($post_id, 'cv_project_description', $clean_desc);

    // Save project links (supporting multiple links)
    $links_to_save = array();
    if (!empty($project['links']) && is_array($project['links'])) {
        foreach ($project['links'] as $p_link) {
            $l_url = esc_url_raw(trim($p_link['url'] ?? ''));
            $l_label = sanitize_text_field(trim($p_link['label'] ?? ($p_link['title'] ?? '')));
            if (!empty($l_url) && !preg_match('/(?:youtube\.com|youtu\.be)/i', $l_url)) {
                $links_to_save[] = array('url' => $l_url, 'label' => $l_label);
            }
        }
    }

    $raw_url = trim($project['url'] ?? '');
    $proj_url = esc_url_raw($raw_url);
    $proj_url_title = sanitize_text_field($project['url_title'] ?? '');
    $is_yt_project_url = (bool) preg_match('/(?:youtube\.com|youtu\.be)/i', $raw_url);

    if (empty($links_to_save) && !empty($proj_url) && !$is_yt_project_url) {
        $links_to_save[] = array('url' => $proj_url, 'label' => $proj_url_title);
    }

    if (!empty($links_to_save)) {
        update_post_meta($post_id, '_cv_project_links', $links_to_save);
        update_post_meta($post_id, 'cv_project_links', $links_to_save);
        update_post_meta($post_id, '_cv_project_link', $links_to_save[0]['url']);
        update_post_meta($post_id, 'cv_project_link', $links_to_save[0]['url']);
        update_post_meta($post_id, '_cv_project_link_label', $links_to_save[0]['label']);
        update_post_meta($post_id, 'cv_project_link_label', $links_to_save[0]['label']);
    }

    // YouTube URLs (collect from explicit field, project URL if YouTube, and description)
    $candidate_yt = array();
    if (!empty($project['youtube_urls'])) {
        if (is_array($project['youtube_urls'])) {
            $candidate_yt = array_merge($candidate_yt, $project['youtube_urls']);
        } elseif (is_string($project['youtube_urls'])) {
            $candidate_yt = array_merge($candidate_yt, preg_split('/[\r\n,]+/', $project['youtube_urls']));
        }
    }
    if (!empty($project['youtube_url'])) {
        $candidate_yt[] = $project['youtube_url'];
    }
    if (!empty($project['youtube'])) {
        $candidate_yt[] = $project['youtube'];
    }
    if ($is_yt_project_url && !empty($raw_url)) {
        $candidate_yt[] = $raw_url;
    }
    if (!empty($project['description']) && preg_match_all('/https?:\/\/(?:www\.)?(?:youtube\.com|youtu\.be)\/[^\s\)\"\'>]+/i', $project['description'], $desc_yt)) {
        $candidate_yt = array_merge($candidate_yt, $desc_yt[0]);
    }

    $youtube_urls = array();
    foreach ($candidate_yt as $yt) {
        $cleaned_yt = trim((string) $yt, " \t\n\r\0\x0B),\"'");
        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $cleaned_yt, $m)) {
            $canonical_yt = 'https://www.youtube.com/watch?v=' . $m[1];
            if (!in_array($canonical_yt, $youtube_urls, true)) {
                $youtube_urls[] = $canonical_yt;
            }
        }
    }

    if (!empty($youtube_urls)) {
        update_post_meta($post_id, '_cv_project_youtube', $youtube_urls[0]);
        update_post_meta($post_id, 'cv_project_youtube', $youtube_urls[0]);
        update_post_meta($post_id, '_cv_project_youtube_videos', $youtube_urls);
        update_post_meta($post_id, 'cv_project_youtube_videos', $youtube_urls);
    }

    // Project Screenshots / Images
    $project_image_ids = array();
    $candidate_filenames = array();
    if (!empty($project['image_filenames']) && is_array($project['image_filenames'])) {
        $candidate_filenames = $project['image_filenames'];
    } elseif (!empty($project['image_filenames']) && is_string($project['image_filenames'])) {
        $candidate_filenames = preg_split('/[\r\n,]+/', $project['image_filenames']);
    } elseif (!empty($project['images']) && is_array($project['images'])) {
        $candidate_filenames = $project['images'];
    } elseif (!empty($project['images']) && is_string($project['images'])) {
        $candidate_filenames = preg_split('/[\r\n,]+/', $project['images']);
    }

    if (!empty($candidate_filenames) && !empty($image_map)) {
        foreach ($candidate_filenames as $fn) {
            $clean_fn = basename(trim((string) $fn));
            if (isset($image_map[$clean_fn])) {
                $project_image_ids[] = absint($image_map[$clean_fn]);
            } else {
                foreach ($image_map as $map_fn => $map_id) {
                    if (strcasecmp($map_fn, $clean_fn) === 0) {
                        $project_image_ids[] = absint($map_id);
                        break;
                    }
                }
            }
        }
    }

    // Intelligent fallback distribution if images were not explicitly mapped by filename
    if (empty($project_image_ids) && !empty($uploaded_image_ids)) {
        $total_images = count($uploaded_image_ids);
        if ($total_projects === $total_images && isset($uploaded_image_ids[$index])) {
            $project_image_ids = array($uploaded_image_ids[$index]);
        } elseif ($total_projects === 1) {
            $project_image_ids = $uploaded_image_ids;
        } elseif (isset($uploaded_image_ids[$index])) {
            $project_image_ids = array($uploaded_image_ids[$index]);
        }
    }

    $project_image_ids = array_unique(array_filter(array_map('absint', $project_image_ids)));

    if (!empty($project_image_ids)) {
        update_post_meta($post_id, '_cv_project_screenshots', $project_image_ids);
        update_post_meta($post_id, 'cv_project_screenshots', $project_image_ids);
        set_post_thumbnail($post_id, $project_image_ids[0]);

        $screenshot_alts = array();
        $proj_title = get_the_title($post_id);
        foreach ($project_image_ids as $p_img_id) {
            $existing_alt = get_post_meta($p_img_id, '_wp_attachment_image_alt', true);
            $alt_text = !empty($existing_alt) ? $existing_alt : $proj_title;
            $screenshot_alts[$p_img_id] = $alt_text;
            if (empty($existing_alt)) {
                update_post_meta($p_img_id, '_wp_attachment_image_alt', $proj_title);
            }
        }
        update_post_meta($post_id, '_cv_project_screenshot_alts', $screenshot_alts);
        update_post_meta($post_id, 'cv_project_screenshot_alts', $screenshot_alts);
    }

    if (function_exists('pll_set_post_language')) {
        pll_set_post_language($post_id, $lang);
    }

    return $post_id;
}

// Populate ACF fields with extracted CV data (OVERWRITE mode)
function cv_pdf_acf_populate_fields($page_id, $cv_data, $lang = 'fi', $uploaded_image_ids = array(), $image_map = array())
{
    $updated = array();
    $created_ids = array(
        'badges' => array(),
        'experience' => array(),
        'education' => array(),
        'courses' => array(),
        'projects' => array(),
        'skills' => array(),
        'references' => array()
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

    if (!empty($cv_data['references_title'])) {
        update_field('cv_references_title', sanitize_text_field($cv_data['references_title']), $page_id);
        $updated[] = 'References Title';
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

        // Create new experience items (sorted newest to oldest)
        if (function_exists('cv_one_pager_sort_experience_items')) {
            $cv_data['experience_items'] = cv_one_pager_sort_experience_items($cv_data['experience_items']);
        }
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
            if ($post_id && !is_wp_error($post_id)) {
                $company = sanitize_text_field($item['company'] ?? '');
                if (!empty($company)) {
                    update_post_meta($post_id, '_cv_experience_company', $company);
                    update_post_meta($post_id, 'cv_experience_company', $company);
                }
                $dates = cv_pdf_acf_sanitize_preserve_dashes($item['dates'] ?? '');
                if (!empty($dates)) {
                    update_post_meta($post_id, '_cv_experience_dates', $dates);
                    update_post_meta($post_id, 'cv_experience_dates', $dates);
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
            if ($post_id && !is_wp_error($post_id)) {
                $dates = cv_pdf_acf_sanitize_preserve_dashes($item['dates'] ?? '');
                if (!empty($dates)) {
                    update_post_meta($post_id, '_cv_education_dates', $dates);
                    update_post_meta($post_id, 'cv_education_dates', $dates);
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
            if ($post_id && !is_wp_error($post_id)) {
                if (!empty($provider)) {
                    update_post_meta($post_id, '_cv_course_provider', sanitize_text_field($provider));
                    update_post_meta($post_id, 'cv_course_provider', sanitize_text_field($provider));
                }
                if (!empty($dates)) {
                    $clean_dates = cv_pdf_acf_sanitize_preserve_dashes($dates);
                    update_post_meta($post_id, '_cv_course_dates', $clean_dates);
                    update_post_meta($post_id, 'cv_course_dates', $clean_dates);
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
        $total_projects = count($cv_data['projects']);
        $menu_order = $total_projects;
        foreach ($cv_data['projects'] as $index => $project) {
            $post_id = cv_pdf_acf_save_single_project($project, $lang, $menu_order--, $uploaded_image_ids, $image_map, $index, $total_projects);
            if ($post_id) {
                $created_ids['projects'][$index] = $post_id;
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

    // References Items
    if (!empty($cv_data['references']) && is_array($cv_data['references'])) {
        $existing_refs = get_posts(array(
            'post_type' => 'cv_reference',
            'numberposts' => -1,
            'lang' => $lang,
            'suppress_filters' => false
        ));
        foreach ($existing_refs as $ref_post) {
            wp_delete_post($ref_post->ID, true);
        }

        $menu_order = count($cv_data['references']);
        foreach ($cv_data['references'] as $index => $item) {
            $post_data = array(
                'post_title' => sanitize_text_field($item['name'] ?? ''),
                'post_type' => 'cv_reference',
                'post_status' => 'publish',
                'menu_order' => $menu_order--
            );
            $post_id = wp_insert_post($post_data);
            if ($post_id && !is_wp_error($post_id)) {
                $ref_title = sanitize_text_field($item['title'] ?? '');
                if (!empty($ref_title)) {
                    update_post_meta($post_id, '_cv_reference_title', $ref_title);
                    update_post_meta($post_id, 'cv_reference_title', $ref_title);
                }
                $ref_company = sanitize_text_field($item['company'] ?? '');
                if (!empty($ref_company)) {
                    update_post_meta($post_id, '_cv_reference_company', $ref_company);
                    update_post_meta($post_id, 'cv_reference_company', $ref_company);
                }
                $ref_email = sanitize_text_field($item['email'] ?? '');
                if (!empty($ref_email)) {
                    update_post_meta($post_id, '_cv_reference_email', $ref_email);
                    update_post_meta($post_id, 'cv_reference_email', $ref_email);
                }
                $ref_phone = sanitize_text_field($item['phone'] ?? '');
                if (!empty($ref_phone)) {
                    update_post_meta($post_id, '_cv_reference_phone', $ref_phone);
                    update_post_meta($post_id, 'cv_reference_phone', $ref_phone);
                }

                if (function_exists('pll_set_post_language')) {
                    pll_set_post_language($post_id, $lang);
                    $created_ids['references'][$index] = $post_id;
                }
            }
        }
        $updated[] = 'References (' . count($cv_data['references']) . ')';
    }

    return array(
        'updated' => $updated,
        'created_ids' => $created_ids
    );
}

// Handle PDF or Word DOCX upload and processing
function cv_pdf_acf_handle_upload()
{
    try {
        // Increase limits for processing large files and API calls
        set_time_limit(300);
        @ini_set('memory_limit', '256M');

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
        $file_field = !empty($_FILES['cv_pdf_file']) ? 'cv_pdf_file' : (!empty($_FILES['cv_file']) ? 'cv_file' : '');
        if (empty($file_field) || $_FILES[$file_field]['error'] !== UPLOAD_ERR_OK) {
            cv_pdf_acf_redirect_with_message('File upload failed. Please try again.', 'error');
            return;
        }

        $file = $_FILES[$file_field];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validate file type (PDF or DOCX)
        $valid_extensions = array('pdf', 'docx');
        if (!in_array($file_ext, $valid_extensions, true)) {
            cv_pdf_acf_redirect_with_message('Invalid file type. Please upload a PDF (.pdf) or Word (.docx) file.', 'error');
            return;
        }

        // Move uploaded file to temp location
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/cv_temp_' . time() . '.' . $file_ext;

        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            cv_pdf_acf_redirect_with_message('Failed to process uploaded file.', 'error');
            return;
        }

        // Extract CV data and images from uploaded file
        $extract_result = cv_pdf_acf_extract_cv_from_file($temp_file, $file_ext, $api_key, $model);

        // Clean up temp file
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }

        if (is_wp_error($extract_result)) {
            cv_pdf_acf_redirect_with_message('CV Processing failed: ' . $extract_result->get_error_message(), 'error');
            return;
        }

        $cv_data = $extract_result['cv_data'] ?? null;
        $uploaded_image_ids = $extract_result['uploaded_image_ids'] ?? array();
        $image_map = $extract_result['image_map'] ?? array();

        if (empty($cv_data) || !is_array($cv_data)) {
            cv_pdf_acf_redirect_with_message('Extracted data is empty or invalid. Please check if your file contains readable text.', 'error');
            return;
        }

        // Populate source language first
        $source_page_id = $translations[$source_lang];
        $result_source = cv_pdf_acf_populate_fields($source_page_id, $cv_data, $source_lang, $uploaded_image_ids, $image_map);

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
            'results' => $results,
            'image_count' => count($uploaded_image_ids),
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
            $result_target = cv_pdf_acf_populate_fields($target_page_id, $translated_data, $target_lang, $uploaded_image_ids, $image_map);
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

            // Link references
            if (!empty($all_created_ids[$source_lang]['references'])) {
                foreach ($all_created_ids[$source_lang]['references'] as $index => $source_id) {
                    $translation_ids = array($source_lang => $source_id);
                    foreach ($target_langs as $target_lang) {
                        if (!empty($all_created_ids[$target_lang]['references'][$index])) {
                            $translation_ids[$target_lang] = $all_created_ids[$target_lang]['references'][$index];
                        }
                    }
                    pll_save_post_translations($translation_ids);
                }
            }
        }

        $import_data['results'] = $results;
        set_transient('cv_pdf_acf_last_import', $import_data, 300); // Store for 5 minutes

        // Build success message
        $message = strtoupper($file_ext) . ' processed successfully! ' . (!empty($uploaded_image_ids) ? count($uploaded_image_ids) . ' image(s) extracted. ' : '') . 'See imported items below.';
        cv_pdf_acf_redirect_with_message($message, 'success');

    } catch (Throwable $e) {
        wp_die(
            '<strong>A critical error occurred during CV processing:</strong><br><br>' .
            esc_html($e->getMessage()) . '<br><br>' .
            '<strong>File:</strong> ' . esc_html($e->getFile()) . '<br>' .
            '<strong>Line:</strong> ' . esc_html($e->getLine()) . '<br><br>' .
            '<a href="' . esc_url(admin_url('options-general.php?page=cv-pdf-to-acf-filler')) . '">&laquo; Back to Settings</a>',
            'CV Processing Error',
            array('response' => 500, 'back_link' => true)
        );
    }
}
add_action('admin_post_cv_pdf_acf_upload', 'cv_pdf_acf_handle_upload');

/**
 * Handle standalone Projects upload (.docx only) and processing.
 */
function cv_pdf_acf_handle_projects_upload()
{
    try {
        set_time_limit(300);
        @ini_set('memory_limit', '256M');

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions.');
        }
        check_admin_referer('cv_pdf_acf_upload_projects');

        $api_key = get_option('cv_pdf_acf_api_key', '');
        if (empty($api_key)) {
            cv_pdf_acf_redirect_with_message('OpenAI API -avain puuttuu. Määritä API-avain ensin asetuksissa.', 'error');
            return;
        }

        $model = get_option('cv_pdf_acf_model', 'gpt-4o');
        $source_lang = isset($_POST['cv_projects_source_language']) ? sanitize_text_field($_POST['cv_projects_source_language']) : 'fi';
        $mode = isset($_POST['cv_projects_mode']) && $_POST['cv_projects_mode'] === 'replace' ? 'replace' : 'append';

        if (empty($_FILES['cv_projects_docx_file']) || $_FILES['cv_projects_docx_file']['error'] !== UPLOAD_ERR_OK) {
            cv_pdf_acf_redirect_with_message('Tiedoston lataus epäonnistui. Valitse kelvollinen .docx-tiedosto.', 'error');
            return;
        }

        $file = $_FILES['cv_projects_docx_file'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file_ext !== 'docx') {
            cv_pdf_acf_redirect_with_message('Virheellinen tiedostomuoto. Tämä toiminto hyväksyy ainoastaan Word (.docx) -tiedostoja.', 'error');
            return;
        }

        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['basedir'] . '/cv_proj_temp_' . time() . '.docx';

        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            cv_pdf_acf_redirect_with_message('Ladatun tiedoston siirtäminen väliaikaishakemistoon epäonnistui.', 'error');
            return;
        }

        // 1. Extract text, hyperlinks and media from docx
        $docx_res = cv_pdf_acf_extract_docx_data($temp_file);

        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }

        if (is_wp_error($docx_res)) {
            cv_pdf_acf_redirect_with_message('Word-tiedoston purku epäonnistui: ' . $docx_res->get_error_message(), 'error');
            return;
        }

        $extracted_text = $docx_res['text'] ?? '';
        $uploaded_image_ids = $docx_res['uploaded_image_ids'] ?? array();
        $image_map = $docx_res['image_map'] ?? array();

        if (empty(trim($extracted_text))) {
            cv_pdf_acf_redirect_with_message('Word-dokumentti ei sisältänyt luettavaa tekstiä.', 'error');
            return;
        }

        // 2. Extract projects using OpenAI
        $projects_result = cv_pdf_acf_extract_projects_from_docx_text($extracted_text, $api_key, $model);
        if (is_wp_error($projects_result)) {
            cv_pdf_acf_redirect_with_message('Projektien OpenAI-jäsennys epäonnistui: ' . $projects_result->get_error_message(), 'error');
            return;
        }

        $projects = $projects_result['projects'] ?? array();
        if (empty($projects) || !is_array($projects)) {
            cv_pdf_acf_redirect_with_message('Dokumentista ei löytynyt tunnistettavia projekteja. Varmista dokumentin muotoilu.', 'error');
            return;
        }

        // Polylang languages
        $required_langs = array('fi', 'en', 'sv');
        $has_polylang = function_exists('pll_get_post_translations') && function_exists('pll_set_post_language');

        // Check if replace mode was chosen:
        if ($mode === 'replace') {
            if ($has_polylang) {
                foreach ($required_langs as $l) {
                    $old_projects = get_posts(array(
                        'post_type' => 'cv_project',
                        'numberposts' => -1,
                        'lang' => $l,
                        'suppress_filters' => false
                    ));
                    foreach ($old_projects as $op) {
                        wp_delete_post($op->ID, true);
                    }
                }
            } else {
                $old_projects = get_posts(array(
                    'post_type' => 'cv_project',
                    'numberposts' => -1,
                    'suppress_filters' => false
                ));
                foreach ($old_projects as $op) {
                    wp_delete_post($op->ID, true);
                }
            }
        }

        // Determine starting menu order
        $calculate_base_order = function($l) {
            $existing = get_posts(array(
                'post_type' => 'cv_project',
                'numberposts' => 1,
                'orderby' => 'menu_order',
                'order' => 'DESC',
                'lang' => $l,
                'suppress_filters' => false
            ));
            return (!empty($existing) && isset($existing[0]->menu_order)) ? (int) $existing[0]->menu_order : 0;
        };

        // Populate source language
        $total_projects = count($projects);
        $base_order = $calculate_base_order($source_lang) + $total_projects;
        $order = $base_order;

        $created_ids = array();
        foreach ($projects as $index => $project) {
            $post_id = cv_pdf_acf_save_single_project($project, $source_lang, $order--, $uploaded_image_ids, $image_map, $index, $total_projects);
            if ($post_id) {
                $created_ids[$source_lang][$index] = $post_id;
            }
        }

        // Store results for admin display
        $import_data = array(
            'source_lang' => strtoupper($source_lang),
            'mode' => $mode,
            'projects_count' => count($projects),
            'image_count' => count($uploaded_image_ids),
            'projects' => $projects,
            'translations' => array()
        );

        // Translate to target languages if Polylang is active
        if ($has_polylang) {
            $target_langs = array_diff($required_langs, array($source_lang));
            foreach ($target_langs as $t_lang) {
                $trans_result = cv_pdf_acf_translate_projects($projects_result, $t_lang, $api_key, $model);
                if (is_wp_error($trans_result) || empty($trans_result['projects'])) {
                    continue;
                }

                $trans_projects = $trans_result['projects'];
                $import_data['translations'][strtoupper($t_lang)] = $trans_projects;

                $t_order = $calculate_base_order($t_lang) + count($trans_projects);
                foreach ($trans_projects as $index => $t_proj) {
                    $t_post_id = cv_pdf_acf_save_single_project($t_proj, $t_lang, $t_order--, $uploaded_image_ids, $image_map, $index, count($trans_projects));
                    if ($t_post_id) {
                        $created_ids[$t_lang][$index] = $t_post_id;
                    }
                }
            }

            // Link translations in Polylang
            if (function_exists('pll_save_post_translations') && !empty($created_ids[$source_lang])) {
                foreach ($created_ids[$source_lang] as $index => $source_id) {
                    $link_group = array($source_lang => $source_id);
                    foreach ($target_langs as $t_lang) {
                        if (!empty($created_ids[$t_lang][$index])) {
                            $link_group[$t_lang] = $created_ids[$t_lang][$index];
                        }
                    }
                    pll_save_post_translations($link_group);
                }
            }
        }

        // If projects_title was provided and front page exists, optionally update cv_projects_title
        if (!empty($projects_result['projects_title']) && function_exists('update_field')) {
            $front_page_id = get_option('page_on_front');
            if ($front_page_id) {
                if ($has_polylang && function_exists('pll_get_post_translations')) {
                    $trans = pll_get_post_translations($front_page_id);
                    if (!empty($trans[$source_lang])) {
                        update_field('cv_projects_title', sanitize_text_field($projects_result['projects_title']), $trans[$source_lang]);
                    }
                } else {
                    update_field('cv_projects_title', sanitize_text_field($projects_result['projects_title']), $front_page_id);
                }
            }
        }

        set_transient('cv_pdf_acf_last_project_import', $import_data, 300);

        $success_msg = sprintf(
            'Projektit tuotu onnistuneesti! %d projektia lisätty, %d kuvaa viety mediakirjastoon.',
            count($projects),
            count($uploaded_image_ids)
        );
        cv_pdf_acf_redirect_with_message($success_msg, 'success');

    } catch (Throwable $e) {
        wp_die(
            '<strong>Virhe projektien käsittelyssä:</strong><br><br>' .
            esc_html($e->getMessage()) . '<br><br>' .
            '<strong>Tiedosto:</strong> ' . esc_html($e->getFile()) . '<br>' .
            '<strong>Rivi:</strong> ' . esc_html($e->getLine()) . '<br><br>' .
            '<a href="' . esc_url(admin_url('options-general.php?page=cv-pdf-to-acf-filler')) . '">&laquo; Takaisin asetuksiin</a>',
            'Project Processing Error',
            array('response' => 500, 'back_link' => true)
        );
    }
}
add_action('admin_post_cv_pdf_acf_upload_projects', 'cv_pdf_acf_handle_projects_upload');

/**
 * Handle standalone work experience upload from PDF or DOCX.
 */
function cv_pdf_acf_handle_experience_upload()
{
    @set_time_limit(300);
    @ini_set('memory_limit', '256M');

    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions.');
    }
    check_admin_referer('cv_pdf_acf_upload_experience');

    $api_key = get_option('cv_pdf_acf_api_key', '');
    if (empty($api_key)) {
        cv_pdf_acf_redirect_with_message('OpenAI API -avain puuttuu. Määritä API-avain ensin asetuksissa.', 'error');
        return;
    }

    $model = get_option('cv_pdf_acf_model', 'gpt-4o');
    $source_lang = isset($_POST['cv_experience_source_language']) ? sanitize_text_field($_POST['cv_experience_source_language']) : 'fi';
    $mode = isset($_POST['cv_experience_mode']) && $_POST['cv_experience_mode'] === 'replace' ? 'replace' : 'append';

    if (empty($_FILES['cv_experience_file']) || $_FILES['cv_experience_file']['error'] !== UPLOAD_ERR_OK) {
        cv_pdf_acf_redirect_with_message('Tiedoston lataus epäonnistui. Valitse kelvollinen PDF- tai Word (.docx) -tiedosto.', 'error');
        return;
    }

    $file = $_FILES['cv_experience_file'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, array('pdf', 'docx'), true)) {
        cv_pdf_acf_redirect_with_message('Virheellinen tiedostomuoto. Tämä toiminto hyväksyy ainoastaan PDF- ja Word (.docx) -tiedostoja.', 'error');
        return;
    }

    $upload_dir = wp_upload_dir();
    $temp_file = $upload_dir['basedir'] . '/cv_exp_temp_' . time() . '.' . $file_ext;

    if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
        cv_pdf_acf_redirect_with_message('Ladatun tiedoston siirtäminen väliaikaishakemistoon epäonnistui.', 'error');
        return;
    }

    try {
        $extracted_text = '';
        if ($file_ext === 'docx') {
            $docx_res = cv_pdf_acf_extract_docx_data($temp_file);
            if (is_wp_error($docx_res)) {
                if (file_exists($temp_file)) {
                    @unlink($temp_file);
                }
                cv_pdf_acf_redirect_with_message('Word-tiedoston purku epäonnistui: ' . $docx_res->get_error_message(), 'error');
                return;
            }
            $extracted_text = $docx_res['text'] ?? '';
        } else {
            $pdf_text = cv_pdf_acf_extract_pdf_text($temp_file);
            if (is_wp_error($pdf_text)) {
                if (file_exists($temp_file)) {
                    @unlink($temp_file);
                }
                cv_pdf_acf_redirect_with_message('PDF-tiedoston purku epäonnistui: ' . $pdf_text->get_error_message(), 'error');
                return;
            }
            $extracted_text = $pdf_text;
        }

        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }

        if (empty(trim($extracted_text))) {
            cv_pdf_acf_redirect_with_message('Dokumentti ei sisältänyt luettavaa tekstiä.', 'error');
            return;
        }

        // Extract work experience using OpenAI
        $experience_result = cv_pdf_acf_extract_experience_from_text($extracted_text, $api_key, $model);
        if (is_wp_error($experience_result)) {
            cv_pdf_acf_redirect_with_message('Työkokemusten OpenAI-jäsennys epäonnistui: ' . $experience_result->get_error_message(), 'error');
            return;
        }

        $experience_items = $experience_result['experience_items'] ?? array();
        if (empty($experience_items) || !is_array($experience_items)) {
            cv_pdf_acf_redirect_with_message('Dokumentista ei löytynyt tunnistettavia työkokemuksia. Varmista dokumentin sisältö ja muotoilu.', 'error');
            return;
        }

        $required_langs = array('fi', 'en', 'sv');
        $has_polylang = function_exists('pll_get_post_translations') && function_exists('pll_set_post_language');

        // Check if replace mode was chosen
        if ($mode === 'replace') {
            if ($has_polylang) {
                foreach ($required_langs as $l) {
                    $old_items = get_posts(array(
                        'post_type' => 'cv_experience',
                        'numberposts' => -1,
                        'lang' => $l,
                        'suppress_filters' => false
                    ));
                    foreach ($old_items as $oi) {
                        wp_delete_post($oi->ID, true);
                    }
                }
            } else {
                $old_items = get_posts(array(
                    'post_type' => 'cv_experience',
                    'numberposts' => -1,
                    'suppress_filters' => false
                ));
                foreach ($old_items as $oi) {
                    wp_delete_post($oi->ID, true);
                }
            }
        }

        // Determine starting menu order
        $calculate_base_order = function($l) {
            $existing = get_posts(array(
                'post_type' => 'cv_experience',
                'numberposts' => 1,
                'orderby' => 'menu_order',
                'order' => 'DESC',
                'lang' => $l,
                'suppress_filters' => false
            ));
            return (!empty($existing) && isset($existing[0]->menu_order)) ? (int) $existing[0]->menu_order : 0;
        };

        if (function_exists('cv_one_pager_sort_experience_items')) {
            $experience_items = cv_one_pager_sort_experience_items($experience_items);
            if (isset($experience_result['experience_items'])) {
                $experience_result['experience_items'] = $experience_items;
            }
        }

        $total_items = count($experience_items);
        $base_order = $calculate_base_order($source_lang) + $total_items;
        $order = $base_order;

        $created_ids = array();
        foreach ($experience_items as $index => $item) {
            $post_id = cv_pdf_acf_save_single_experience($item, $source_lang, $order--);
            if ($post_id) {
                $created_ids[$source_lang][$index] = $post_id;
            }
        }

        $import_data = array(
            'source_lang' => strtoupper($source_lang),
            'mode' => $mode,
            'file_type' => strtoupper($file_ext),
            'items_count' => count($experience_items),
            'items' => $experience_items,
            'translations' => array()
        );

        // Translate to target languages if Polylang is active
        if ($has_polylang) {
            $target_langs = array_diff($required_langs, array($source_lang));
            foreach ($target_langs as $t_lang) {
                $trans_result = cv_pdf_acf_translate_experience($experience_result, $t_lang, $api_key, $model);
                if (is_wp_error($trans_result) || empty($trans_result['experience_items'])) {
                    continue;
                }

                $trans_items = $trans_result['experience_items'];
                $import_data['translations'][strtoupper($t_lang)] = $trans_items;

                $t_order = $calculate_base_order($t_lang) + count($trans_items);
                foreach ($trans_items as $index => $t_item) {
                    $t_post_id = cv_pdf_acf_save_single_experience($t_item, $t_lang, $t_order--);
                    if ($t_post_id) {
                        $created_ids[$t_lang][$index] = $t_post_id;
                    }
                }
            }

            // Link translations in Polylang
            if (function_exists('pll_save_post_translations') && !empty($created_ids[$source_lang])) {
                foreach ($created_ids[$source_lang] as $index => $source_id) {
                    $link_group = array($source_lang => $source_id);
                    foreach ($target_langs as $t_lang) {
                        if (!empty($created_ids[$t_lang][$index])) {
                            $link_group[$t_lang] = $created_ids[$t_lang][$index];
                        }
                    }
                    pll_save_post_translations($link_group);
                }
            }
        }

        // If experience_title was provided and front page exists, update cv_experience_title
        if (!empty($experience_result['experience_title']) && function_exists('update_field')) {
            $front_page_id = get_option('page_on_front');
            if ($front_page_id) {
                if ($has_polylang && function_exists('pll_get_post_translations')) {
                    $trans = pll_get_post_translations($front_page_id);
                    if (!empty($trans[$source_lang])) {
                        update_field('cv_experience_title', sanitize_text_field($experience_result['experience_title']), $trans[$source_lang]);
                    }
                } else {
                    update_field('cv_experience_title', sanitize_text_field($experience_result['experience_title']), $front_page_id);
                }
            }
        }

        set_transient('cv_pdf_acf_last_experience_import', $import_data, 300);

        $success_msg = sprintf(
            'Työkokemukset tuotu onnistuneesti! %d työpaikkaa lisätty järjestelmään (%s).',
            count($experience_items),
            strtoupper($file_ext)
        );
        cv_pdf_acf_redirect_with_message($success_msg, 'success');

    } catch (Throwable $e) {
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
        wp_die(
            '<strong>Virhe työkokemusten käsittelyssä:</strong><br><br>' .
            esc_html($e->getMessage()) . '<br><br>' .
            '<strong>Tiedosto:</strong> ' . esc_html($e->getFile()) . '<br>' .
            '<strong>Rivi:</strong> ' . esc_html($e->getLine()) . '<br><br>' .
            '<a href="' . esc_url(admin_url('options-general.php?page=cv-pdf-to-acf-filler')) . '">&laquo; Takaisin asetuksiin</a>',
            'Experience Processing Error',
            array('response' => 500, 'back_link' => true)
        );
    }
}
add_action('admin_post_cv_pdf_acf_upload_experience', 'cv_pdf_acf_handle_experience_upload');

// Display imported CV data
function cv_pdf_acf_display_import_results($import_data)
{
    ?>
    <div class="notice notice-success" style="padding: 15px; margin-top: 20px;">
        <h2 style="margin-top: 0;">✓ Imported CV Data (Source: <?php echo esc_html($import_data['source_lang']); ?>)</h2>
        <?php if (!empty($import_data['image_count'])): ?>
            <p><strong>Embedded images imported to Media Library:</strong> <?php echo (int) $import_data['image_count']; ?>
                image(s)</p>
        <?php endif; ?>

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
function cv_pdf_acf_display_cv_content($cv_data)
{
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
                if (!empty($project['url'])) {
                    echo ' | <a href="' . esc_url($project['url']) . '" target="_blank">' . esc_html($project['url_title'] ?? 'Project link') . '</a>';
                }
                if (!empty($project['youtube_urls']) && is_array($project['youtube_urls'])) {
                    echo '<br><strong>YouTube Videos (' . count($project['youtube_urls']) . '):</strong> ' . esc_html(implode(', ', $project['youtube_urls']));
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

/**
 * Display imported project results notice in admin
 */
function cv_pdf_acf_display_project_import_results($import_data)
{
    $projects = $import_data['projects'] ?? array();
    $translations = $import_data['translations'] ?? array();
    $source_lang = $import_data['source_lang'] ?? 'FI';
    $mode_text = ($import_data['mode'] ?? 'append') === 'replace' ? 'Korvattu aiemmat projektit' : 'Lisätty olemassa olevien jatkoksi';
    $img_count = (int) ($import_data['image_count'] ?? 0);
    ?>
    <div class="notice notice-success" style="padding: 15px; margin-top: 20px;">
        <h2 style="margin-top: 0;">✓ Projektit tuotu onnistuneesti (.docx) &mdash; Lähdekieli: <?php echo esc_html($source_lang); ?></h2>
        <p>
            <strong>Projektien määrä:</strong> <?php echo count($projects); ?> kpl &nbsp;|&nbsp; 
            <strong>Toimintatapa:</strong> <?php echo esc_html($mode_text); ?> &nbsp;|&nbsp; 
            <strong>Mediakirjastoon tuodut kuvat:</strong> <?php echo $img_count; ?> kpl
        </p>

        <div style="background: #ffffff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; border-left: 4px solid #46b450;">
            <h3 style="margin-top: 0; color: #1d2327;"><?php echo esc_html($source_lang); ?> (Lähdekieli)</h3>
            <?php cv_pdf_acf_render_projects_preview_list($projects); ?>
        </div>

        <?php foreach ($translations as $lang => $trans_projs): ?>
            <div style="background: #ffffff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; border-left: 4px solid #2271b1;">
                <h3 style="margin-top: 0; color: #1d2327;"><?php echo esc_html($lang); ?> (Käännetty automaattisesti)</h3>
                <?php cv_pdf_acf_render_projects_preview_list($trans_projs); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Render preview grid of projects in admin notice
 */
function cv_pdf_acf_render_projects_preview_list($projects)
{
    if (empty($projects)) {
        echo '<p><em>Ei projekteja.</em></p>';
        return;
    }
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;">';
    foreach ($projects as $proj) {
        $title = $proj['title'] ?? 'Nimetön projekti';
        $meta = $proj['meta'] ?? '';
        $desc = $proj['description'] ?? '';
        $url = $proj['url'] ?? '';
        $url_title = $proj['url_title'] ?? 'Verkkosivu / Repo';
        $youtube_urls = !empty($proj['youtube_urls']) && is_array($proj['youtube_urls']) ? $proj['youtube_urls'] : array();
        $images = !empty($proj['image_filenames']) && is_array($proj['image_filenames']) ? $proj['image_filenames'] : array();

        echo '<div style="background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 12px;">';
        echo '<h4 style="margin: 0 0 6px 0; font-size: 15px; color: #0073aa;">' . esc_html($title) . '</h4>';
        if (!empty($meta)) {
            echo '<div style="margin-bottom: 6px;"><span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 3px; font-size: 12px; font-weight: 600;">' . esc_html($meta) . '</span></div>';
        }
        if (!empty($desc)) {
            echo '<p style="margin: 6px 0; font-size: 13px; color: #3c434a; line-height: 1.4;">' . esc_html(wp_trim_words($desc, 30)) . '</p>';
        }
        if (!empty($url)) {
            echo '<p style="margin: 6px 0; font-size: 12px;"><strong>Linkki:</strong> <a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url_title) . ' &rarr;</a></p>';
        }
        if (!empty($youtube_urls)) {
            echo '<div style="margin-top: 6px; font-size: 12px;"><strong>YouTube-videot (' . count($youtube_urls) . '):</strong><ul style="margin: 4px 0 0 16px; padding: 0;">';
            foreach ($youtube_urls as $yt) {
                echo '<li><a href="' . esc_url($yt) . '" target="_blank" rel="noopener noreferrer" style="color: #b32d2e;">▶ ' . esc_html($yt) . '</a></li>';
            }
            echo '</ul></div>';
        }
        if (!empty($images)) {
            echo '<div style="margin-top: 6px; font-size: 12px; color: #646970;"><strong>Kuvatiedostot:</strong> ' . esc_html(implode(', ', $images)) . '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Display imported Work Experience data notice
 */
function cv_pdf_acf_display_experience_import_results($import_data)
{
    $items = $import_data['items'] ?? array();
    $translations = $import_data['translations'] ?? array();
    $source_lang = $import_data['source_lang'] ?? 'FI';
    $file_type = $import_data['file_type'] ?? 'DOCX';
    $mode_text = ($import_data['mode'] ?? 'append') === 'replace' ? 'Korvattu aiemmat työkokemukset' : 'Lisätty olemassa olevien jatkoksi';
    ?>
    <div class="notice notice-success" style="padding: 15px; margin-top: 20px;">
        <h2 style="margin-top: 0;">✓ Työkokemukset tuotu onnistuneesti (<?php echo esc_html($file_type); ?>) &mdash; Lähdekieli: <?php echo esc_html($source_lang); ?></h2>
        <p>
            <strong>Työpaikkojen määrä:</strong> <?php echo count($items); ?> kpl &nbsp;|&nbsp; 
            <strong>Toimintatapa:</strong> <?php echo esc_html($mode_text); ?> &nbsp;|&nbsp; 
            <strong>Tiedostomuoto:</strong> <?php echo esc_html($file_type); ?>
        </p>

        <div style="background: #ffffff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; border-left: 4px solid #46b450;">
            <h3 style="margin-top: 0; color: #1d2327;"><?php echo esc_html($source_lang); ?> (Lähdekieli)</h3>
            <?php cv_pdf_acf_render_experience_preview_list($items); ?>
        </div>

        <?php foreach ($translations as $lang => $trans_items): ?>
            <div style="background: #ffffff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; border-left: 4px solid #2271b1;">
                <h3 style="margin-top: 0; color: #1d2327;"><?php echo esc_html($lang); ?> (Käännetty automaattisesti)</h3>
                <?php cv_pdf_acf_render_experience_preview_list($trans_items); ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}

/**
 * Render preview grid of work experience items in admin notice
 */
function cv_pdf_acf_render_experience_preview_list($items)
{
    if (empty($items)) {
        echo '<p><em>Ei työkokemuksia.</em></p>';
        return;
    }
    echo '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 15px;">';
    foreach ($items as $item) {
        $role = $item['role'] ?? 'Tehtävänimike';
        $company = $item['company'] ?? '';
        $dates = $item['dates'] ?? '';
        $summary = $item['summary'] ?? '';

        echo '<div style="background: #f6f7f7; border: 1px solid #dcdcde; border-radius: 4px; padding: 12px;">';
        echo '<h4 style="margin: 0 0 4px 0; font-size: 15px; color: #0073aa;">' . esc_html($role) . '</h4>';
        if (!empty($company)) {
            echo '<div style="font-weight: 600; font-size: 13px; color: #1d2327; margin-bottom: 4px;">' . esc_html($company) . '</div>';
        }
        if (!empty($dates)) {
            echo '<div style="margin-bottom: 6px;"><span style="background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 3px; font-size: 12px;">' . esc_html($dates) . '</span></div>';
        }
        if (!empty($summary)) {
            echo '<p style="margin: 6px 0 0 0; font-size: 13px; color: #3c434a; line-height: 1.4;">' . esc_html(wp_trim_words($summary, 30)) . '</p>';
        }
        echo '</div>';
    }
    echo '</div>';
}

// Helper function to redirect with message
function cv_pdf_acf_redirect_with_message($message, $type = 'success')
{
    $referer = wp_get_referer();
    if (!$referer) {
        $referer = admin_url('options-general.php?page=cv-pdf-to-acf-filler');
    }

    $url = add_query_arg(array(
        'cv_pdf_acf_message' => rawurlencode($message),
        'cv_pdf_acf_type' => $type
    ), $referer);

    if (headers_sent()) {
        echo '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '" />';
        echo '<script>window.location.href="' . esc_url_raw($url) . '";</script>';
    } else {
        wp_redirect($url); // Use wp_redirect instead of wp_safe_redirect to avoid host mismatch silent failures
    }
    exit;
}

// Hook to handle PDF export
function cv_pdf_acf_handle_pdf_export()
{
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
function cv_pdf_acf_collect_cv_data($page_id, $lang)
{
    $data = array(
        'name' => get_bloginfo('name'),
        'hero_headline' => function_exists('get_field') ? get_field('cv_hero_headline', $page_id) : '',
        'hero_intro' => function_exists('get_field') ? get_field('cv_hero_intro', $page_id) : '',
        'about_photo' => function_exists('get_field') ? get_field('cv_about_photo', $page_id) : '',
        'profile_title' => function_exists('get_field') ? get_field('cv_profile_title', $page_id) : '',
        'profile_body' => function_exists('get_field') ? get_field('cv_profile_body', $page_id) : '',
        'contact_email' => function_exists('get_field') ? get_field('cv_contact_email', $page_id) : '',
        'contact_phone' => function_exists('get_field') ? get_field('cv_contact_phone', $page_id) : '',
        'contact_linkedin' => function_exists('get_field') ? get_field('cv_contact_linkedin', $page_id) : '',
        'contact_homepage_url' => get_theme_mod('cv_contact_homepage_url', home_url('/')),
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
    if (function_exists('cv_one_pager_sort_experience_items')) {
        $experience_items = cv_one_pager_sort_experience_items($experience_items);
    }
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
        $item_links = get_post_meta($item->ID, '_cv_project_links', true);
        if (empty($item_links)) {
            $item_links = get_post_meta($item->ID, 'cv_project_links', true);
        }
        if (empty($item_links) || !is_array($item_links)) {
            $single_url = get_post_meta($item->ID, '_cv_project_link', true) ?: get_post_meta($item->ID, 'cv_project_link', true);
            $single_title = get_post_meta($item->ID, '_cv_project_link_label', true) ?: get_post_meta($item->ID, 'cv_project_link_label', true);
            $item_links = array();
            if (!empty($single_url)) {
                $item_links[] = array('url' => $single_url, 'label' => $single_title);
            }
        }
        $data['projects'][] = array(
            'title' => get_the_title($item),
            'meta' => get_post_meta($item->ID, '_cv_project_meta', true),
            'url_title' => get_post_meta($item->ID, '_cv_project_link_label', true),
            'url' => get_post_meta($item->ID, '_cv_project_link', true),
            'links' => $item_links,
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

    // Collect references
    $data['references'] = array();
    $references_query = array(
        'post_type' => 'cv_reference',
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'DESC',
        'lang' => $lang,
        'suppress_filters' => false
    );
    $references_items = get_posts($references_query);
    foreach ($references_items as $item) {
        $r_title = get_post_meta($item->ID, '_cv_reference_title', true) ?: get_post_meta($item->ID, 'cv_reference_title', true);
        $r_company = get_post_meta($item->ID, '_cv_reference_company', true) ?: get_post_meta($item->ID, 'cv_reference_company', true);
        $r_email = get_post_meta($item->ID, '_cv_reference_email', true) ?: get_post_meta($item->ID, 'cv_reference_email', true);
        $r_phone = get_post_meta($item->ID, '_cv_reference_phone', true) ?: get_post_meta($item->ID, 'cv_reference_phone', true);
        $data['references'][] = array(
            'name' => get_the_title($item),
            'title' => $r_title,
            'company' => $r_company,
            'email' => $r_email,
            'phone' => $r_phone
        );
    }

    // Also check ACF repeater if CPT was empty
    if (empty($data['references']) && function_exists('get_field')) {
        $acf_refs = get_field('cv_references', $page_id);
        if (!empty($acf_refs) && is_array($acf_refs)) {
            foreach ($acf_refs as $acf_ref) {
                if (!empty($acf_ref['name'])) {
                    $data['references'][] = array(
                        'name' => $acf_ref['name'],
                        'title' => $acf_ref['title'] ?? '',
                        'company' => $acf_ref['company'] ?? '',
                        'email' => $acf_ref['email'] ?? '',
                        'phone' => $acf_ref['phone'] ?? ''
                    );
                }
            }
        }
    }

    // Also fall back to default references if none found
    if (empty($data['references']) && function_exists('cv_one_pager_get_default_references')) {
        $data['references'] = cv_one_pager_get_default_references($lang);
    }

    return $data;
}

// Get translated text for PDF export
function cv_pdf_acf_get_translation($key, $lang)
{
    $translations = array(
        'contact' => array(
            'fi' => 'Yhteystiedot',
            'en' => 'Contact',
            'sv' => 'Kontakt'
        ),
        'email' => array(
            'fi' => 'Sähköposti',
            'en' => 'Email',
            'sv' => 'E-post'
        ),
        'phone' => array(
            'fi' => 'Puhelin',
            'en' => 'Phone',
            'sv' => 'Telefon'
        ),
        'linkedin' => array(
            'fi' => 'LinkedIn',
            'en' => 'LinkedIn',
            'sv' => 'LinkedIn'
        ),
        'website' => array(
            'fi' => 'Kotisivut',
            'en' => 'Website',
            'sv' => 'Webbplats'
        ),
        'profile' => array(
            'fi' => 'Profiili',
            'en' => 'Profile',
            'sv' => 'Profil'
        ),
        'certifications' => array(
            'fi' => 'Sertifikaatit ja merkit',
            'en' => 'Certifications & Badges',
            'sv' => 'Certifieringar och märken'
        ),
        'experience' => array(
            'fi' => 'Työkokemus',
            'en' => 'Experience',
            'sv' => 'Arbetslivserfarenhet'
        ),
        'education' => array(
            'fi' => 'Koulutus',
            'en' => 'Education',
            'sv' => 'Utbildning'
        ),
        'training' => array(
            'fi' => 'Lisäkoulutus',
            'en' => 'Additional Training',
            'sv' => 'Ytterligare utbildning'
        ),
        'projects' => array(
            'fi' => 'Projektit',
            'en' => 'Projects',
            'sv' => 'Projekt'
        ),
        'tech_stack' => array(
            'fi' => 'Tekninen pino',
            'en' => 'Tech Stack',
            'sv' => 'Teknisk stack'
        ),
        'url_title' => array(
            'fi' => 'URL-otsikko',
            'en' => 'URL Title',
            'sv' => 'URL-titel'
        ),
        'url' => array(
            'fi' => 'URL',
            'en' => 'URL',
            'sv' => 'URL'
        ),
        'skills' => array(
            'fi' => 'Taidot',
            'en' => 'Skills',
            'sv' => 'Färdigheter'
        ),
        'references' => array(
            'fi' => 'Suosittelijat',
            'en' => 'References',
            'sv' => 'Referenser'
        )
    );

    if (isset($translations[$key][$lang])) {
        return $translations[$key][$lang];
    }

    // Fallback to English if translation not found
    return isset($translations[$key]['en']) ? $translations[$key]['en'] : $key;
}

// Generate PDF document using FPDF
function cv_pdf_acf_generate_pdf_document($cv_data, $lang)
{
    try {
        // Helper function to clean text for PDF and ensure line breaks wrap cleanly at spaces
        $clean_text = function ($text) {
            if (empty($text))
                return '';
            $text = wp_strip_all_tags($text);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            // Normalize all non-breaking spaces and invisible unicode whitespace to ASCII space 0x20
            $text = str_replace(array("\xc2\xa0", "\u{00a0}", "\u{200b}", "\u{202f}", "\u{feff}", "&nbsp;"), ' ', $text);
            // Convert UTF-8 to ISO-8859-1 for FPDF
            $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $text);
            // Ensure any ISO-8859-1 non-breaking space (0xA0) is also converted to 0x20 (' ') so FPDF MultiCell wraps at spaces
            $text = str_replace(chr(160), ' ', $text);
            return trim($text);
        };

        $resolve_photo_file = function ($photo) {
            if (empty($photo)) {
                return '';
            }

            $find_local_from_url = function ($url) {
                if (empty($url) || !is_string($url)) {
                    return '';
                }

                $uploads = wp_upload_dir();
                if (!empty($uploads['baseurl']) && !empty($uploads['basedir']) && strpos($url, $uploads['baseurl']) === 0) {
                    $candidate = str_replace($uploads['baseurl'], $uploads['basedir'], $url);
                    if (file_exists($candidate)) {
                        return $candidate;
                    }
                }

                return '';
            };

            if (is_array($photo)) {
                if (!empty($photo['ID'])) {
                    $candidate = get_attached_file((int) $photo['ID']);
                    if (!empty($candidate) && file_exists($candidate)) {
                        return $candidate;
                    }
                }

                if (!empty($photo['url'])) {
                    $candidate = $find_local_from_url($photo['url']);
                    if (!empty($candidate)) {
                        return $candidate;
                    }
                }
            }

            if (is_numeric($photo)) {
                $candidate = get_attached_file((int) $photo);
                if (!empty($candidate) && file_exists($candidate)) {
                    return $candidate;
                }
            }

            if (is_string($photo)) {
                if (file_exists($photo)) {
                    return $photo;
                }

                $candidate = $find_local_from_url($photo);
                if (!empty($candidate)) {
                    return $candidate;
                }
            }

            return '';
        };

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 10);

        $page_width = method_exists($pdf, 'GetPageWidth')
            ? $pdf->GetPageWidth()
            : (isset($pdf->w) ? (float) $pdf->w : 210.0);
        $left_margin = 10;
        $right_margin = 10;

        $header_top_y = $pdf->GetY();
        $photo_file = $resolve_photo_file($cv_data['about_photo'] ?? '');
        $photo_width = 0;
        $photo_height = 0;
        $photo_gap = 6;

        if (!empty($photo_file)) {
            $photo_width = 36;
            $img_size = @getimagesize($photo_file);
            if (!empty($img_size[0]) && !empty($img_size[1])) {
                $photo_height = $photo_width * ($img_size[1] / $img_size[0]);
            } else {
                $photo_height = $photo_width;
            }

            $photo_x = $page_width - $right_margin - $photo_width;
            $pdf->Image($photo_file, $photo_x, $header_top_y, $photo_width);
        }

        $header_text_width = 0;
        if ($photo_width > 0) {
            $header_text_width = $page_width - $left_margin - $right_margin - $photo_width - $photo_gap;
        }

        // Set metadata
        $pdf->SetAuthor($clean_text($cv_data['name']));
        $pdf->SetTitle('CV - ' . $clean_text($cv_data['name']));
        $pdf->SetCreator('CV PDF Exporter');

        // Header - Name and title
        if (!empty($cv_data['hero_headline']) || !empty($cv_data['name'])) {
            $pdf->SetFont('Arial', 'B', 20);
            $pdf->SetTextColor(31, 73, 125); // Blue color
            $pdf->MultiCell($header_text_width > 0 ? $header_text_width : 0, 12, $clean_text(!empty($cv_data['hero_headline']) ? $cv_data['hero_headline'] : $cv_data['name']), 0, 'L');
            $pdf->Ln(2);
        }

        if (!empty($cv_data['hero_intro'])) {
            $pdf->SetFont('Arial', 'I', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell($header_text_width > 0 ? $header_text_width : 0, 6, $clean_text($cv_data['hero_intro']), 0, 'L');
            $pdf->Ln(5);
        }

        if ($photo_height > 0) {
            $min_y_after_photo = $header_top_y + $photo_height + 4;
            if ($pdf->GetY() < $min_y_after_photo) {
                $pdf->SetY($min_y_after_photo);
            }
        }

        // Contact information
        if (!empty($cv_data['contact_email']) || !empty($cv_data['contact_phone']) || !empty($cv_data['contact_linkedin']) || !empty($cv_data['contact_homepage_url'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('contact', $lang)), 0, 1);
            $pdf->Ln(1);

            $pdf->SetFont('Arial', '', 10);
            if (!empty($cv_data['contact_email'])) {
                $pdf->Cell(0, 5, $clean_text(cv_pdf_acf_get_translation('email', $lang)) . ': ' . $clean_text($cv_data['contact_email']), 0, 1);
            }
            if (!empty($cv_data['contact_phone'])) {
                $pdf->Cell(0, 5, $clean_text(cv_pdf_acf_get_translation('phone', $lang)) . ': ' . $clean_text($cv_data['contact_phone']), 0, 1);
            }
            if (!empty($cv_data['contact_linkedin'])) {
                $pdf->Cell(0, 5, $clean_text(cv_pdf_acf_get_translation('linkedin', $lang)) . ': ' . $clean_text($cv_data['contact_linkedin']), 0, 1);
            }
            if (!empty($cv_data['contact_homepage_url'])) {
                $pdf->Cell(0, 5, $clean_text(cv_pdf_acf_get_translation('website', $lang)) . ': ' . $clean_text($cv_data['contact_homepage_url']), 0, 1);
            }
            $pdf->Ln(3);
        }

        // Profile
        if (!empty($cv_data['profile_title']) || !empty($cv_data['profile_body'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, $clean_text(!empty($cv_data['profile_title']) ? $cv_data['profile_title'] : cv_pdf_acf_get_translation('profile', $lang)), 0, 1);
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
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('certifications', $lang)), 0, 1);
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
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('experience', $lang)), 0, 1);
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
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('education', $lang)), 0, 1);
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
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('training', $lang)), 0, 1);
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
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('projects', $lang)), 0, 1);
            $pdf->Ln(1);

            foreach ($cv_data['projects'] as $project) {
                $project_title = $clean_text($project['title'] ?? '');
                if (!empty($project_title)) {
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->Cell(0, 6, $project_title, 0, 1);
                }
                if (!empty($project['meta'])) {
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->MultiCell(0, 5, $clean_text(cv_pdf_acf_get_translation('tech_stack', $lang) . ': ' . $project['meta']), 0, 'L');
                }
                if (!empty($project['links']) && is_array($project['links'])) {
                    foreach ($project['links'] as $p_link) {
                        $pl_url = $p_link['url'] ?? '';
                        $pl_label = $p_link['label'] ?? ($p_link['title'] ?? '');
                        if (!empty($pl_label)) {
                            $pdf->SetFont('Arial', '', 10);
                            $pdf->MultiCell(0, 5, $clean_text(cv_pdf_acf_get_translation('url_title', $lang) . ': ' . $pl_label), 0, 'L');
                        }
                        if (!empty($pl_url)) {
                            $pdf->SetFont('Arial', '', 10);
                            $pdf->MultiCell(0, 5, $clean_text(cv_pdf_acf_get_translation('url', $lang) . ': ' . $pl_url), 0, 'L');
                        }
                    }
                } else {
                    if (!empty($project['url_title'])) {
                        $pdf->SetFont('Arial', '', 10);
                        $pdf->MultiCell(0, 5, $clean_text(cv_pdf_acf_get_translation('url_title', $lang) . ': ' . $project['url_title']), 0, 'L');
                    }
                    if (!empty($project['url'])) {
                        $pdf->SetFont('Arial', '', 10);
                        $pdf->MultiCell(0, 5, $clean_text(cv_pdf_acf_get_translation('url', $lang) . ': ' . $project['url']), 0, 'L');
                    }
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
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('skills', $lang)), 0, 1);
            $pdf->Ln(1);

            $pdf->SetFont('Arial', '', 10);
            foreach ($cv_data['skills'] as $skill_line) {
                if (!empty($skill_line)) {
                    $pdf->MultiCell(0, 5, $clean_text($skill_line), 0, 'L');
                }
            }
            $pdf->Ln(2);
        }

        // References (Suosittelijat)
        if (!empty($cv_data['references']) && is_array($cv_data['references'])) {
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 8, $clean_text(cv_pdf_acf_get_translation('references', $lang)), 0, 1);
            $pdf->Ln(1);

            foreach ($cv_data['references'] as $ref) {
                $ref_name = $clean_text($ref['name'] ?? '');
                if (!empty($ref_name)) {
                    $pdf->SetFont('Arial', 'B', 10);
                    $pdf->Cell(0, 6, $ref_name, 0, 1);
                }
                $ref_sub = array();
                if (!empty($ref['title'])) {
                    $ref_sub[] = $ref['title'];
                }
                if (!empty($ref['company'])) {
                    $ref_sub[] = $ref['company'];
                }
                if (!empty($ref_sub)) {
                    $pdf->SetFont('Arial', 'I', 10);
                    $pdf->Cell(0, 5, $clean_text(implode(', ', $ref_sub)), 0, 1);
                }
                if (!empty($ref['email'])) {
                    $pdf->SetFont('Arial', '', 9);
                    $pdf->Cell(0, 5, $clean_text(cv_pdf_acf_get_translation('email', $lang) . ': ' . $ref['email']), 0, 1);
                }
                if (!empty($ref['phone'])) {
                    $pdf->SetFont('Arial', '', 9);
                    $pdf->Cell(0, 5, $clean_text(cv_pdf_acf_get_translation('phone', $lang) . ': ' . $ref['phone']), 0, 1);
                }
                $pdf->Ln(2);
            }
            $pdf->Ln(1);
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
function cv_pdf_acf_download_file($file_path, $file_type = 'pdf')
{
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
