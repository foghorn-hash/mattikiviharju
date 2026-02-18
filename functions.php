<?php

function cv_one_pager_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height' => 80,
        'width' => 240,
        'flex-height' => true,
        'flex-width' => true,
    ));
}
add_action('after_setup_theme', 'cv_one_pager_setup');

function cv_one_pager_register_post_types() {
    register_post_type('cv_experience', array(
        'labels' => array(
            'name' => cv_one_pager_t('Experience'),
            'singular_name' => cv_one_pager_t('Experience'),
            'add_new_item' => cv_one_pager_t('Add Experience'),
            'edit_item' => cv_one_pager_t('Edit Experience'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => array('title', 'editor', 'excerpt', 'page-attributes'),
        'has_archive' => false,
    ));

    register_post_type('cv_project', array(
        'labels' => array(
            'name' => cv_one_pager_t('Projects'),
            'singular_name' => cv_one_pager_t('Project'),
            'add_new_item' => cv_one_pager_t('Add Project'),
            'edit_item' => cv_one_pager_t('Edit Project'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-hammer',
        'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'),
        'has_archive' => false,
    ));

    register_post_type('cv_education', array(
        'labels' => array(
            'name' => cv_one_pager_t('Education'),
            'singular_name' => cv_one_pager_t('Education'),
            'add_new_item' => cv_one_pager_t('Add Education'),
            'edit_item' => cv_one_pager_t('Edit Education'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array('title', 'editor', 'excerpt', 'page-attributes'),
        'has_archive' => false,
    ));

    register_post_type('cv_course', array(
        'labels' => array(
            'name' => cv_one_pager_t('Courses'),
            'singular_name' => cv_one_pager_t('Course'),
            'add_new_item' => cv_one_pager_t('Add Course'),
            'edit_item' => cv_one_pager_t('Edit Course'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-welcome-learn-more',
        'supports' => array('title', 'editor', 'excerpt', 'page-attributes'),
        'has_archive' => false,
    ));

    register_post_type('cv_skill', array(
        'labels' => array(
            'name' => cv_one_pager_t('Skills'),
            'singular_name' => cv_one_pager_t('Skill'),
            'add_new_item' => cv_one_pager_t('Add Skill'),
            'edit_item' => cv_one_pager_t('Edit Skill'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-awards',
        'supports' => array('title', 'page-attributes'),
        'has_archive' => false,
    ));

    register_post_type('cv_badge', array(
        'labels' => array(
            'name' => cv_one_pager_t('Profile Badges'),
            'singular_name' => cv_one_pager_t('Profile Badge'),
            'add_new_item' => cv_one_pager_t('Add Profile Badge'),
            'edit_item' => cv_one_pager_t('Edit Profile Badge'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-tag',
        'supports' => array('title', 'page-attributes'),
        'has_archive' => false,
    ));
}
add_action('init', 'cv_one_pager_register_post_types');

function cv_one_pager_assets() {
    wp_enqueue_style(
        'cv-one-pager-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );
    wp_enqueue_style(
        'bootstrap-icons',
        'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
        array(),
        '1.11.3'
    );
    wp_enqueue_style('cv-one-pager-style', get_stylesheet_uri(), array('cv-one-pager-fonts', 'bootstrap-icons'), '1.0.0');

    wp_enqueue_script(
        'cv-one-pager-nav',
        get_template_directory_uri() . '/js/nav.js',
        array(),
        '1.0.0',
        true
    );

    if (is_front_page()) {
        wp_enqueue_script(
            'cv-one-pager-gallery',
            get_template_directory_uri() . '/js/gallery.js',
            array(),
            '1.0.0',
            true
        );
    }

    if (is_page_template('page-blog.php') || is_home()) {
        wp_enqueue_script(
            'cv-one-pager-blog',
            get_template_directory_uri() . '/js/blog.js',
            array('jquery'),
            '1.0.0',
            true
        );
        wp_localize_script('cv-one-pager-blog', 'cvBlog', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cv_load_more_posts'),
            'perPage' => (int) get_option('posts_per_page', 6),
        ));
    }
}
add_action('wp_enqueue_scripts', 'cv_one_pager_assets');

function cv_one_pager_t($string) {
    return function_exists('pll__') ? pll__($string) : $string;
}

function cv_one_pager_register_polylang_strings() {
    if (!function_exists('pll_register_string')) {
        return;
    }

    $strings = array(
        'About',
        'Experience',
        'Education',
        'Additional training',
        'Projects',
        'Skills',
        'Profile Badges',
        'Profile Badge',
        'Add Profile Badge',
        'Edit Profile Badge',
        'Add Experience',
        'Edit Experience',
        'Add Project',
        'Edit Project',
        'Add Education',
        'Edit Education',
        'Courses',
        'Course',
        'Add Course',
        'Edit Course',
        'Add Skill',
        'Edit Skill',
        'Contact',
        'Blogi',
        'Blog',
        'Schedule a call',
        'View work',
        'Profile Snapshot',
        'Selected Projects',
        'Let’s connect. Share your project goals and I’ll respond within 24 hours.',
        'Read more',
        'Ei julkaisuja vielä.',
        'Lataa lisää postauksia',
        'Uusimmat päivitykset, oivallukset ja projektimuistiinpanot.',
        'LinkedIn',
        'GitHub',
        'YouTube',
        'Tech Stack:',
        'Y-tunnus:',
        'ALV-rek. nro:',
    );

    foreach ($strings as $string) {
        pll_register_string('cv-one-pager', $string, 'CV One Pager');
    }
}
add_action('init', 'cv_one_pager_register_polylang_strings', 20);

function cv_one_pager_polylang_post_types($post_types, $is_settings) {
    if ($is_settings) {
        $post_types['cv_experience'] = 'cv_experience';
        $post_types['cv_education'] = 'cv_education';
        $post_types['cv_course'] = 'cv_course';
        $post_types['cv_project'] = 'cv_project';
        $post_types['cv_skill'] = 'cv_skill';
        $post_types['cv_badge'] = 'cv_badge';
        return $post_types;
    }

    $post_types[] = 'cv_experience';
    $post_types[] = 'cv_education';
    $post_types[] = 'cv_course';
    $post_types[] = 'cv_project';
    $post_types[] = 'cv_skill';
    $post_types[] = 'cv_badge';
    return array_unique($post_types);
}
add_filter('pll_get_post_types', 'cv_one_pager_polylang_post_types', 10, 2);

function cv_one_pager_cookie_consent_flash_guard() {
    echo '<style id="cv-cookie-consent-guard">#cookie-law-info-again{opacity:0;visibility:hidden;display:none;transition:opacity .2s ease}</style>';
}
add_action('wp_head', 'cv_one_pager_cookie_consent_flash_guard', 1);

function cv_one_pager_cookie_consent_flash_guard_script() {
    echo '<script id="cv-cookie-consent-guard-script">window.addEventListener("load",function(){var el=document.getElementById("cookie-law-info-again");if(el){el.style.opacity="1";el.style.visibility="visible";}});</script>';
}
add_action('wp_footer', 'cv_one_pager_cookie_consent_flash_guard_script', 1);

function cv_one_pager_add_meta_boxes() {
    add_meta_box(
        'cv_experience_meta',
        'Experience Details',
        'cv_one_pager_render_experience_meta_box',
        'cv_experience',
        'normal',
        'default'
    );

    add_meta_box(
        'cv_education_meta',
        'Education Details',
        'cv_one_pager_render_education_meta_box',
        'cv_education',
        'normal',
        'default'
    );

    add_meta_box(
        'cv_course_meta',
        'Course Details',
        'cv_one_pager_render_course_meta_box',
        'cv_course',
        'normal',
        'default'
    );

    add_meta_box(
        'cv_project_meta',
        'Project Details',
        'cv_one_pager_render_project_meta_box',
        'cv_project',
        'normal',
        'default'
    );

    add_meta_box(
        'cv_project_screenshots_meta',
        'Project Screenshots',
        'cv_one_pager_render_project_screenshots_meta_box',
        'cv_project',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'cv_one_pager_add_meta_boxes', 10);

function cv_one_pager_render_experience_meta_box($post) {
    wp_nonce_field('cv_experience_meta_save', 'cv_experience_meta_nonce');
    $dates = get_post_meta($post->ID, '_cv_experience_dates', true);
    echo '<p><label for="cv_experience_dates"><strong>Dates</strong></label></p>';
    echo '<input type="text" id="cv_experience_dates" name="cv_experience_dates" value="' . esc_attr($dates) . '" class="widefat" />';
}

function cv_one_pager_render_education_meta_box($post) {
    wp_nonce_field('cv_education_meta_save', 'cv_education_meta_nonce');
    $dates = get_post_meta($post->ID, '_cv_education_dates', true);
    echo '<p><label for="cv_education_dates"><strong>Dates</strong></label></p>';
    echo '<input type="text" id="cv_education_dates" name="cv_education_dates" value="' . esc_attr($dates) . '" class="widefat" />';
}

function cv_one_pager_render_course_meta_box($post) {
    wp_nonce_field('cv_course_meta_save', 'cv_course_meta_nonce');
    $dates = get_post_meta($post->ID, '_cv_course_dates', true);
    $provider = $post->post_excerpt;
    ?>
    <div style="padding: 10px 0;">
        <p><label for="cv_course_provider"><strong>Provider / Organizer (School/Institution)</strong></label></p>
        <input type="text" id="cv_course_provider" name="cv_course_provider" value="<?php echo esc_attr($provider); ?>" class="widefat" placeholder="e.g., University of Helsinki, Coursera, etc." />
        <p class="description">Enter the name of the organization or institution providing this course.</p>
        
        <hr style="margin: 15px 0;" />
        
        <p><label for="cv_course_dates"><strong>Dates / Training Period</strong></label></p>
        <input type="text" id="cv_course_dates" name="cv_course_dates" value="<?php echo esc_attr($dates); ?>" class="widefat" placeholder="e.g., 2023-2024, March 2024, etc." />
        <p class="description">Enter the dates or time period when this training/course took place.</p>
        
        <hr style="margin: 15px 0;" />
        
        <p class="description" style="margin-top: 10px;">
            <strong>How to edit:</strong><br>
            • <strong>Course Title:</strong> Use the title field at the top of this page<br>
            • <strong>Provider/School:</strong> Use the field above<br>
            • <strong>Description:</strong> Use the main content editor below<br>
            • <strong>Dates:</strong> Use the field above
        </p>
    </div>
    <?php
}

function cv_one_pager_render_project_meta_box($post) {
    wp_nonce_field('cv_project_meta_save', 'cv_project_meta_nonce');
    $description = $post->post_excerpt;
    $meta = get_post_meta($post->ID, '_cv_project_meta', true);
    $link = get_post_meta($post->ID, '_cv_project_link', true);
    $link_label = get_post_meta($post->ID, '_cv_project_link_label', true);
    
    echo '<div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #2271b1;">';
    echo '<p style="margin: 0; font-size: 13px; color: #646970;"><strong>Instructions:</strong><br>';
    echo 'Project Title: Use title field above<br>';
    echo 'Description: Use field below<br>';
    echo 'Tech Stack: Use Tech Stack field below<br>';
    echo 'Full Details: Use main editor below</p>';
    echo '</div>';
    
    echo '<p><label for="cv_project_description"><strong>Description (Summary)</strong></label></p>';
    echo '<textarea id="cv_project_description" name="cv_project_description" rows="3" class="widefat" placeholder="Short project description...">' . esc_textarea($description) . '</textarea>';
    echo '<p style="margin-top: 5px; font-size: 12px; color: #646970;">Brief summary shown on CV page</p>';
    
    echo '<p><label for="cv_project_meta"><strong>Tech Stack (e.g. React, PHP, MySQL)</strong></label></p>';
    echo '<input type="text" id="cv_project_meta" name="cv_project_meta" value="' . esc_attr($meta) . '" class="widefat" placeholder="React, PHP, MySQL" />';
    echo '<p><label for="cv_project_link"><strong>Project URL</strong></label></p>';
    echo '<input type="url" id="cv_project_link" name="cv_project_link" value="' . esc_attr($link) . '" class="widefat" />';
    echo '<p><label for="cv_project_link_label"><strong>Project Link Title</strong></label></p>';
    echo '<input type="text" id="cv_project_link_label" name="cv_project_link_label" value="' . esc_attr($link_label) . '" class="widefat" placeholder="View project" />';
    
    $youtube_url = get_post_meta($post->ID, '_cv_project_youtube', true);
    echo '<p><label for="cv_project_youtube"><strong>YouTube Video URL</strong></label></p>';
    echo '<input type="url" id="cv_project_youtube" name="cv_project_youtube" value="' . esc_attr($youtube_url) . '" class="widefat" placeholder="https://www.youtube.com/watch?v=..." />';
    echo '<p style="margin-top: 5px; font-size: 12px; color: #646970;">Video will be shown with screenshots in gallery</p>';
}

function cv_one_pager_render_project_screenshots_meta_box($post) {
    wp_nonce_field('cv_project_screenshots_save', 'cv_project_screenshots_nonce');
    $value = get_post_meta($post->ID, '_cv_project_screenshots', true);
    $ids = array();

    if (is_array($value)) {
        $ids = array_filter(array_map('absint', $value));
    } elseif (is_string($value) && $value !== '') {
        $ids = array_filter(array_map('absint', explode(',', $value)));
    }

    echo '<p><button type="button" class="button" id="cv-project-screenshots-add">Add Screenshots</button></p>';
    echo '<input type="hidden" id="cv_project_screenshots" name="cv_project_screenshots" value="' . esc_attr(implode(',', $ids)) . '" />';
    echo '<div id="cv-project-screenshots-list" class="cv-project-screenshots-list">';
    foreach ($ids as $attachment_id) {
        $thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        if (!$thumb) {
            continue;
        }
        $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        echo '<div class="cv-project-screenshot" data-id="' . esc_attr($attachment_id) . '">';
        echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($alt) . '" />';
        echo '<button type="button" class="button-link cv-project-screenshot-remove">Remove</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<style>.cv-project-screenshots-list{display:flex;flex-wrap:wrap;gap:12px}.cv-project-screenshot{display:flex;flex-direction:column;align-items:flex-start;gap:6px}.cv-project-screenshot img{width:120px;height:auto;border:1px solid #ccd0d4;border-radius:4px}</style>';
}

function cv_one_pager_admin_assets($hook) {
    if (!is_admin()) {
        return;
    }

    if (!function_exists('get_current_screen')) {
        return;
    }

    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'cv_project') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'cv-one-pager-project-screenshots',
        get_template_directory_uri() . '/js/admin-project-gallery.js',
        array('jquery'),
        '1.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'cv_one_pager_admin_assets');

function cv_one_pager_sanitize_preserve_dashes($text) {
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

function cv_one_pager_save_meta_boxes($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (isset($_POST['cv_experience_meta_nonce']) && wp_verify_nonce($_POST['cv_experience_meta_nonce'], 'cv_experience_meta_save')) {
        if (isset($_POST['cv_experience_dates'])) {
            update_post_meta($post_id, '_cv_experience_dates', cv_one_pager_sanitize_preserve_dashes($_POST['cv_experience_dates']));
        }
    }
    if (isset($_POST['cv_education_meta_nonce']) && wp_verify_nonce($_POST['cv_education_meta_nonce'], 'cv_education_meta_save')) {
        if (isset($_POST['cv_education_dates'])) {
            update_post_meta($post_id, '_cv_education_dates', cv_one_pager_sanitize_preserve_dashes($_POST['cv_education_dates']));
        }
    }
    if (isset($_POST['cv_course_meta_nonce']) && wp_verify_nonce($_POST['cv_course_meta_nonce'], 'cv_course_meta_save')) {
        if (isset($_POST['cv_course_provider'])) {
            // Unhook to prevent infinite loop
            remove_action('save_post', 'cv_one_pager_save_meta_boxes');
            
            // Update provider in post_excerpt
            wp_update_post(array(
                'ID' => $post_id,
                'post_excerpt' => sanitize_text_field($_POST['cv_course_provider'])
            ));
            
            // Re-hook
            add_action('save_post', 'cv_one_pager_save_meta_boxes');
        }
        if (isset($_POST['cv_course_dates'])) {
            update_post_meta($post_id, '_cv_course_dates', cv_one_pager_sanitize_preserve_dashes($_POST['cv_course_dates']));
        }
    }
    if (isset($_POST['cv_project_meta_nonce']) && wp_verify_nonce($_POST['cv_project_meta_nonce'], 'cv_project_meta_save')) {
        if (isset($_POST['cv_project_description'])) {
            // Unhook to prevent infinite loop
            remove_action('save_post', 'cv_one_pager_save_meta_boxes');
            
            // Update description in post_excerpt
            wp_update_post(array(
                'ID' => $post_id,
                'post_excerpt' => sanitize_textarea_field($_POST['cv_project_description'])
            ));
            
            // Re-hook
            add_action('save_post', 'cv_one_pager_save_meta_boxes');
        }
        if (isset($_POST['cv_project_meta'])) {
            update_post_meta($post_id, '_cv_project_meta', sanitize_text_field($_POST['cv_project_meta']));
        }
        if (isset($_POST['cv_project_link'])) {
            update_post_meta($post_id, '_cv_project_link', esc_url_raw($_POST['cv_project_link']));
        }
        if (isset($_POST['cv_project_link_label'])) {
            update_post_meta($post_id, '_cv_project_link_label', sanitize_text_field($_POST['cv_project_link_label']));
        }
        if (isset($_POST['cv_project_youtube'])) {
            update_post_meta($post_id, '_cv_project_youtube', esc_url_raw($_POST['cv_project_youtube']));
        }
    }

    if (isset($_POST['cv_project_screenshots_nonce']) && wp_verify_nonce($_POST['cv_project_screenshots_nonce'], 'cv_project_screenshots_save')) {
        if (isset($_POST['cv_project_screenshots'])) {
            $raw_ids = sanitize_text_field($_POST['cv_project_screenshots']);
            $ids = array_filter(array_map('absint', explode(',', $raw_ids)));
            update_post_meta($post_id, '_cv_project_screenshots', $ids);
        }
    }
}
add_action('save_post', 'cv_one_pager_save_meta_boxes');

function cv_one_pager_render_post_card($post) {
    $title = get_the_title($post);
    $excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words($post->post_content, 28);
    $permalink = get_permalink($post);
    $date = get_the_date('', $post);
    ob_start();
    ?>
    <article class="post-card">
        <div class="post-card-body">
            <p class="post-meta"><?php echo esc_html($date); ?></p>
            <h3 class="post-title"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
            <p class="post-excerpt"><?php echo esc_html($excerpt); ?></p>
        </div>
        <a class="post-link" href="<?php echo esc_url($permalink); ?>">Lue lisää</a>
    </article>
    <?php
    return ob_get_clean();
}

function cv_one_pager_load_more_posts() {
    check_ajax_referer('cv_load_more_posts', 'nonce');

    $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
    $per_page = isset($_POST['perPage']) ? max(1, (int) $_POST['perPage']) : 6;

    $query = new WP_Query(array(
        'post_type' => 'post',
        'posts_per_page' => $per_page,
        'offset' => $offset,
        'post_status' => 'publish',
        'no_found_rows' => false,
    ));

    $html = '';
    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            $html .= cv_one_pager_render_post_card($post);
        }
    }

    $total = (int) $query->found_posts;
    $loaded = $offset + $query->post_count;

    wp_send_json_success(array(
        'html' => $html,
        'hasMore' => $loaded < $total,
        'loaded' => $loaded,
    ));
}

add_action('wp_ajax_cv_load_more_posts', 'cv_one_pager_load_more_posts');
add_action('wp_ajax_nopriv_cv_load_more_posts', 'cv_one_pager_load_more_posts');

function cv_one_pager_front_page_id() {
    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id > 0) {
        if (function_exists('pll_get_post')) {
            $translated_id = pll_get_post($front_page_id);
            if ($translated_id) {
                return (int) $translated_id;
            }
        }
        return $front_page_id;
    }

    $queried_id = get_queried_object_id();
    if ($queried_id && function_exists('pll_get_post')) {
        $translated_id = pll_get_post($queried_id);
        if ($translated_id) {
            return (int) $translated_id;
        }
    }
    return $queried_id ? (int) $queried_id : 0;
}

function cv_one_pager_register_acf_fields() {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_cv_one_pager',
        'title' => 'CV One Pager',
        'fields' => array(
            array(
                'key' => 'field_cv_nav_about_label',
                'label' => 'Nav Label: About',
                'name' => 'cv_nav_about_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_nav_experience_label',
                'label' => 'Nav Label: Experience',
                'name' => 'cv_nav_experience_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_nav_education_label',
                'label' => 'Nav Label: Education',
                'name' => 'cv_nav_education_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_nav_courses_label',
                'label' => 'Nav Label: Additional Training',
                'name' => 'cv_nav_courses_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_nav_projects_label',
                'label' => 'Nav Label: Projects',
                'name' => 'cv_nav_projects_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_nav_skills_label',
                'label' => 'Nav Label: Skills',
                'name' => 'cv_nav_skills_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_nav_contact_label',
                'label' => 'Nav Label: Contact',
                'name' => 'cv_nav_contact_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_hero_headline',
                'label' => 'Hero Headline',
                'name' => 'cv_hero_headline',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_hero_intro',
                'label' => 'Hero Intro',
                'name' => 'cv_hero_intro',
                'type' => 'textarea',
                'rows' => 3,
            ),
            array(
                'key' => 'field_cv_about_photo',
                'label' => 'About Photo',
                'name' => 'cv_about_photo',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ),
            array(
                'key' => 'field_cv_primary_cta_label',
                'label' => 'Primary CTA Label',
                'name' => 'cv_primary_cta_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_primary_cta_url',
                'label' => 'Primary CTA URL',
                'name' => 'cv_primary_cta_url',
                'type' => 'url',
            ),
            array(
                'key' => 'field_cv_secondary_cta_label',
                'label' => 'Secondary CTA Label',
                'name' => 'cv_secondary_cta_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_secondary_cta_url',
                'label' => 'Secondary CTA URL',
                'name' => 'cv_secondary_cta_url',
                'type' => 'url',
            ),
            array(
                'key' => 'field_cv_profile_title',
                'label' => 'Profile Title',
                'name' => 'cv_profile_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_profile_body',
                'label' => 'Profile Body',
                'name' => 'cv_profile_body',
                'type' => 'textarea',
                'rows' => 4,
            ),
            array(
                'key' => 'field_cv_profile_github_url',
                'label' => 'Profile GitHub URL',
                'name' => 'cv_profile_github_url',
                'type' => 'url',
            ),
            array(
                'key' => 'field_cv_profile_youtube_url',
                'label' => 'Profile YouTube URL',
                'name' => 'cv_profile_youtube_url',
                'type' => 'url',
            ),
            array(
                'key' => 'field_cv_profile_badges',
                'label' => 'Profile Badges',
                'name' => 'cv_profile_badges',
                'type' => 'repeater',
                'layout' => 'table',
                'min' => 0,
                'button_label' => 'Add Badge',
                'sub_fields' => array(
                    array(
                        'key' => 'field_cv_profile_badge_label',
                        'label' => 'Badge',
                        'name' => 'label',
                        'type' => 'text',
                    ),
                ),
            ),
            array(
                'key' => 'field_cv_experience_title',
                'label' => 'Experience Section Title',
                'name' => 'cv_experience_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_education_title',
                'label' => 'Education Section Title',
                'name' => 'cv_education_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_courses_title',
                'label' => 'Additional Training Section Title',
                'name' => 'cv_courses_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_experience_items',
                'label' => 'Experience Items',
                'name' => 'cv_experience_items',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Add Experience',
                'sub_fields' => array(
                    array(
                        'key' => 'field_cv_experience_role',
                        'label' => 'Role',
                        'name' => 'role',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_cv_experience_dates',
                        'label' => 'Dates',
                        'name' => 'dates',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_cv_experience_summary',
                        'label' => 'Summary',
                        'name' => 'summary',
                        'type' => 'textarea',
                        'rows' => 3,
                    ),
                ),
            ),
            array(
                'key' => 'field_cv_projects_title',
                'label' => 'Projects Section Title',
                'name' => 'cv_projects_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_projects',
                'label' => 'Projects',
                'name' => 'cv_projects',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Add Project',
                'sub_fields' => array(
                    array(
                        'key' => 'field_cv_project_title',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_cv_project_meta',
                        'label' => 'Meta',
                        'name' => 'meta',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_cv_project_description',
                        'label' => 'Description',
                        'name' => 'description',
                        'type' => 'textarea',
                        'rows' => 3,
                    ),
                ),
            ),
            array(
                'key' => 'field_cv_skills_title',
                'label' => 'Skills Section Title',
                'name' => 'cv_skills_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_skills',
                'label' => 'Skills',
                'name' => 'cv_skills',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Add Skill',
                'sub_fields' => array(
                    array(
                        'key' => 'field_cv_skill_label',
                        'label' => 'Skill',
                        'name' => 'label',
                        'type' => 'text',
                    ),
                ),
            ),
            array(
                'key' => 'field_cv_contact_title',
                'label' => 'Contact Section Title',
                'name' => 'cv_contact_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_contact_body',
                'label' => 'Contact Body',
                'name' => 'cv_contact_body',
                'type' => 'textarea',
                'rows' => 3,
            ),
            array(
                'key' => 'field_cv_contact_email',
                'label' => 'Contact Email',
                'name' => 'cv_contact_email',
                'type' => 'email',
            ),
            array(
                'key' => 'field_cv_contact_phone',
                'label' => 'Contact Phone',
                'name' => 'cv_contact_phone',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_contact_business_id',
                'label' => 'Business ID (Y-tunnus)',
                'name' => 'cv_contact_business_id',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_contact_vat_id',
                'label' => 'VAT Registration ID (ALV)',
                'name' => 'cv_contact_vat_id',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_contact_linkedin',
                'label' => 'LinkedIn URL',
                'name' => 'cv_contact_linkedin',
                'type' => 'url',
            ),
            array(
                'key' => 'field_cv_contact_linkedin_label',
                'label' => 'LinkedIn Label',
                'name' => 'cv_contact_linkedin_label',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_footer_text',
                'label' => 'Footer Text',
                'name' => 'cv_footer_text',
                'type' => 'textarea',
                'rows' => 2,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_type',
                    'operator' => '==',
                    'value' => 'front_page',
                ),
            ),
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ),
                array(
                    'param' => 'page_type',
                    'operator' => '!=',
                    'value' => 'posts_page',
                ),
            ),
        ),
        'position' => 'acf_after_title',
        'style' => 'seamless',
    ));

    acf_add_local_field_group(array(
        'key' => 'group_cv_blog_page',
        'title' => 'Blog Page',
        'fields' => array(
            array(
                'key' => 'field_cv_blog_title',
                'label' => 'Blog Title',
                'name' => 'cv_blog_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_blog_intro',
                'label' => 'Blog Intro',
                'name' => 'cv_blog_intro',
                'type' => 'textarea',
                'rows' => 3,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_type',
                    'operator' => '==',
                    'value' => 'posts_page',
                ),
            ),
        ),
        'position' => 'acf_after_title',
        'style' => 'seamless',
    ));

    if (function_exists('acf_get_field_type') && acf_get_field_type('gallery')) {
        acf_add_local_field_group(array(
            'key' => 'group_cv_project_fields',
            'title' => 'Project Screenshots',
            'fields' => array(
                array(
                    'key' => 'field_cv_project_screenshots',
                    'label' => 'Screenshots',
                    'name' => 'cv_project_screenshots',
                    'type' => 'gallery',
                    'return_format' => 'array',
                    'preview_size' => 'thumbnail',
                    'library' => 'all',
                    'min' => 0,
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'cv_project',
                    ),
                ),
            ),
            'position' => 'acf_after_title',
            'style' => 'seamless',
        ));
    }
}
add_action('acf/init', 'cv_one_pager_register_acf_fields');
