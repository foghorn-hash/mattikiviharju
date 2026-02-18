<?php
/**
 * Plugin Name: Word to Blog AI
 * Description: Import Word documents and create Finnish blog posts using OpenAI
 * Version: 1.0.0
 * Author: Matti Kiviharju
 * Text Domain: word-to-blog-ai
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WTBAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WTBAI_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader if available
if (file_exists(WTBAI_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WTBAI_PLUGIN_DIR . 'vendor/autoload.php';
}

class Word_To_Blog_AI {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_wtbai_upload_word', array($this, 'handle_word_upload'));
        add_action('wp_ajax_wtbai_process_with_ai', array($this, 'process_with_ai'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Word to Blog AI',
            'Word to Blog AI',
            'manage_options',
            'word-to-blog-ai',
            array($this, 'render_admin_page'),
            'dashicons-edit-page',
            30
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_word-to-blog-ai' !== $hook) {
            return;
        }
        
        wp_enqueue_script(
            'wtbai-admin',
            WTBAI_PLUGIN_URL . 'js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('wtbai-admin', 'wtbaiData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wtbai_nonce')
        ));
        
        wp_enqueue_style(
            'wtbai-admin',
            WTBAI_PLUGIN_URL . 'css/admin.css',
            array(),
            '1.0.0'
        );
    }
    
    public function render_admin_page() {
        include WTBAI_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    public function handle_word_upload() {
        check_ajax_referer('wtbai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if (!isset($_FILES['word_file'])) {
            wp_send_json_error('No file uploaded');
        }
        
        $file = $_FILES['word_file'];
        
        // Validate file type
        $allowed_types = array('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Invalid file type. Please upload a .docx or .doc file.');
        }
        
        // Process the Word file
        try {
            $content = $this->extract_word_content($file['tmp_name']);
            wp_send_json_success($content);
        } catch (Exception $e) {
            wp_send_json_error('Error processing file: ' . $e->getMessage());
        }
    }
    
    private function extract_word_content($file_path) {
        if (!class_exists('PhpOffice\PhpWord\IOFactory')) {
            throw new Exception('PHPWord library not found. Please run: composer install');
        }
        
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file_path);
        $content = array(
            'text' => '',
            'images' => array()
        );
        
        // Extract text and images
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $element_class = get_class($element);
                
                if ($element_class === 'PhpOffice\PhpWord\Element\TextRun') {
                    foreach ($element->getElements() as $textElement) {
                        if (method_exists($textElement, 'getText')) {
                            $content['text'] .= $textElement->getText() . ' ';
                        }
                    }
                } elseif ($element_class === 'PhpOffice\PhpWord\Element\Text') {
                    $content['text'] .= $element->getText() . ' ';
                } elseif ($element_class === 'PhpOffice\PhpWord\Element\Image') {
                    // Get image source
                    $imageSource = $element->getSource();
                    if (file_exists($imageSource)) {
                        $content['images'][] = array(
                            'path' => $imageSource,
                            'data' => base64_encode(file_get_contents($imageSource))
                        );
                    }
                }
            }
        }
        
        return $content;
    }
    
    public function process_with_ai() {
        check_ajax_referer('wtbai_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
        $api_key = get_option('wtbai_openai_api_key', '');
        
        if (empty($api_key)) {
            wp_send_json_error('OpenAI API key not configured. Please add it in settings.');
        }
        
        if (empty($content)) {
            wp_send_json_error('No content provided');
        }
        
        try {
            $blog_post = $this->generate_blog_post($content, $api_key);
            
            // Create WordPress post
            $post_id = wp_insert_post(array(
                'post_title' => $blog_post['title'],
                'post_content' => $blog_post['content'],
                'post_status' => 'draft',
                'post_type' => 'post'
            ));
            
            if (is_wp_error($post_id)) {
                wp_send_json_error('Error creating post: ' . $post_id->get_error_message());
            }
            
            // Handle images if provided
            if (!empty($_POST['images'])) {
                $this->import_images($post_id, json_decode(stripslashes($_POST['images']), true));
            }
            
            wp_send_json_success(array(
                'post_id' => $post_id,
                'edit_url' => admin_url('post.php?post=' . $post_id . '&action=edit'),
                'title' => $blog_post['title']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error: ' . $e->getMessage());
        }
    }
    
    private function generate_blog_post($content, $api_key) {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $prompt = "Luo suomenkielinen blogiartikkeli seuraavasta sisällöstä. Anna vastaus JSON-muodossa kentillä 'title' (otsikko) ja 'content' (sisältö HTML-muodossa).\n\nSisältö:\n" . $content;
        
        $data = array(
            'model' => 'gpt-4o',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Olet ammattimainen suomenkielinen sisällöntuottaja. Luo laadukkaita blogiartikkeleita HTML-muodossa.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.7,
            'response_format' => array('type' => 'json_object')
        );
        
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode($data),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response');
        }
        
        $result = json_decode($body['choices'][0]['message']['content'], true);
        
        return array(
            'title' => $result['title'] ?? 'Untitled',
            'content' => $result['content'] ?? ''
        );
    }
    
    private function import_images($post_id, $images) {
        foreach ($images as $image_data) {
            if (empty($image_data['data'])) {
                continue;
            }
            
            $upload_dir = wp_upload_dir();
            $filename = 'blog-image-' . uniqid() . '.jpg';
            $file_path = $upload_dir['path'] . '/' . $filename;
            
            // Decode and save image
            $image_content = base64_decode($image_data['data']);
            file_put_contents($file_path, $image_content);
            
            // Create attachment
            $attachment = array(
                'post_mime_type' => wp_check_filetype($filename)['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);
            
            if (!is_wp_error($attachment_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $attachment_data);
            }
        }
    }
}

// Initialize plugin
add_action('plugins_loaded', array('Word_To_Blog_AI', 'get_instance'));

// Add settings page
add_action('admin_init', 'wtbai_register_settings');
function wtbai_register_settings() {
    register_setting('wtbai_settings', 'wtbai_openai_api_key');
    
    add_settings_section(
        'wtbai_settings_section',
        'OpenAI API Settings',
        '__return_empty_string',
        'word-to-blog-ai'
    );
    
    add_settings_field(
        'wtbai_openai_api_key',
        'OpenAI API Key',
        'wtbai_api_key_field',
        'word-to-blog-ai',
        'wtbai_settings_section'
    );
}

function wtbai_api_key_field() {
    $value = get_option('wtbai_openai_api_key', '');
    echo '<input type="password" name="wtbai_openai_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
    echo '<p class="description">Enter your OpenAI API key. Get it from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a></p>';
}
