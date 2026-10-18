<?php

if (!isset($content_width)) {
    $content_width = 1200;
}

function cv_one_pager_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
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

    register_post_type('cv_reference', array(
        'labels' => array(
            'name' => cv_one_pager_t('References'),
            'singular_name' => cv_one_pager_t('Reference'),
            'add_new_item' => cv_one_pager_t('Add Reference'),
            'edit_item' => cv_one_pager_t('Edit Reference'),
        ),
        'public' => true,
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-testimonial',
        'supports' => array('title', 'editor', 'page-attributes'),
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
    wp_enqueue_style('cv-one-pager-style', get_stylesheet_uri(), array('cv-one-pager-fonts', 'bootstrap-icons'), '1.0.2');

    wp_enqueue_script(
        'cv-one-pager-nav',
        get_template_directory_uri() . '/js/nav.js',
        array(),
        '1.0.0',
        true
    );

    if (is_front_page() || is_page_template('template-cv.php')) {
        wp_enqueue_script(
            'cv-one-pager-gallery',
            get_template_directory_uri() . '/js/gallery.js',
            array(),
            '1.0.3',
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
        $current_lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
        wp_localize_script('cv-one-pager-blog', 'cvBlog', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cv_load_more_posts'),
            'perPage' => (int) get_option('posts_per_page', 6),
            'lang' => $current_lang,
        ));
    }
}
add_action('wp_enqueue_scripts', 'cv_one_pager_assets');

function cv_one_pager_get_translations_map() {
    return array(
        'References' => array(
            'fi' => 'Suosittelijat',
            'en' => 'References',
            'sv' => 'Referenser',
        ),
        'Suosittelijat' => array(
            'fi' => 'Suosittelijat',
            'en' => 'References',
            'sv' => 'Referenser',
        ),
        'Referenser' => array(
            'fi' => 'Suosittelijat',
            'en' => 'References',
            'sv' => 'Referenser',
        ),
        'Reference' => array(
            'fi' => 'Suosittelija',
            'en' => 'Reference',
            'sv' => 'Referens',
        ),
        'Suosittelija' => array(
            'fi' => 'Suosittelija',
            'en' => 'Reference',
            'sv' => 'Referens',
        ),
        'Referens' => array(
            'fi' => 'Suosittelija',
            'en' => 'Reference',
            'sv' => 'Referens',
        ),
        'Add Reference' => array(
            'fi' => 'Lisää suosittelija',
            'en' => 'Add Reference',
            'sv' => 'Lägg till referens',
        ),
        'Lisää suosittelija' => array(
            'fi' => 'Lisää suosittelija',
            'en' => 'Add Reference',
            'sv' => 'Lägg till referens',
        ),
        'Lägg till referens' => array(
            'fi' => 'Lisää suosittelija',
            'en' => 'Add Reference',
            'sv' => 'Lägg till referens',
        ),
        'Edit Reference' => array(
            'fi' => 'Muokkaa suosittelijaa',
            'en' => 'Edit Reference',
            'sv' => 'Redigera referens',
        ),
        'Muokkaa suosittelijaa' => array(
            'fi' => 'Muokkaa suosittelijaa',
            'en' => 'Edit Reference',
            'sv' => 'Redigera referens',
        ),
        'Redigera referens' => array(
            'fi' => 'Muokkaa suosittelijaa',
            'en' => 'Edit Reference',
            'sv' => 'Redigera referens',
        ),
        'Job Title' => array(
            'fi' => 'Titteli',
            'en' => 'Job Title',
            'sv' => 'Titel',
        ),
        'Titteli' => array(
            'fi' => 'Titteli',
            'en' => 'Job Title',
            'sv' => 'Titel',
        ),
        'Titel' => array(
            'fi' => 'Titteli',
            'en' => 'Job Title',
            'sv' => 'Titel',
        ),
        'Company' => array(
            'fi' => 'Yritys',
            'en' => 'Company',
            'sv' => 'Företag',
        ),
        'Yritys' => array(
            'fi' => 'Yritys',
            'en' => 'Company',
            'sv' => 'Företag',
        ),
        'Företag' => array(
            'fi' => 'Yritys',
            'en' => 'Company',
            'sv' => 'Företag',
        ),
        'Email' => array(
            'fi' => 'Sähköposti',
            'en' => 'Email',
            'sv' => 'E-post',
        ),
        'Sähköposti' => array(
            'fi' => 'Sähköposti',
            'en' => 'Email',
            'sv' => 'E-post',
        ),
        'E-post' => array(
            'fi' => 'Sähköposti',
            'en' => 'Email',
            'sv' => 'E-post',
        ),
        'Phone' => array(
            'fi' => 'Puhelin',
            'en' => 'Phone',
            'sv' => 'Telefon',
        ),
        'Puhelin' => array(
            'fi' => 'Puhelin',
            'en' => 'Phone',
            'sv' => 'Telefon',
        ),
        'Telefon' => array(
            'fi' => 'Puhelin',
            'en' => 'Phone',
            'sv' => 'Telefon',
        ),
        'Ei suosittelijoita vielä lisättynä.' => array(
            'fi' => 'Ei suosittelijoita vielä lisättynä.',
            'en' => 'No references added yet.',
            'sv' => 'Inga referenser tillagda ännu.',
        ),
        'No references added yet.' => array(
            'fi' => 'Ei suosittelijoita vielä lisättynä.',
            'en' => 'No references added yet.',
            'sv' => 'Inga referenser tillagda ännu.',
        ),
        'Inga referenser tillagda ännu.' => array(
            'fi' => 'Ei suosittelijoita vielä lisättynä.',
            'en' => 'No references added yet.',
            'sv' => 'Inga referenser tillagda ännu.',
        ),
        'Suosittelijat pyydettäessä' => array(
            'fi' => 'Suosittelijat pyydettäessä',
            'en' => 'References available upon request',
            'sv' => 'Referenser lämnas på begäran',
        ),
        'References available upon request' => array(
            'fi' => 'Suosittelijat pyydettäessä',
            'en' => 'References available upon request',
            'sv' => 'Referenser lämnas på begäran',
        ),
        'About' => array('fi' => 'Tietoa minusta', 'en' => 'About', 'sv' => 'Om mig'),
        'Experience' => array('fi' => 'Työkokemus', 'en' => 'Experience', 'sv' => 'Arbetslivserfarenhet'),
        'Education' => array('fi' => 'Koulutus', 'en' => 'Education', 'sv' => 'Utbildning'),
        'Additional training' => array('fi' => 'Lisäkoulutus', 'en' => 'Additional training', 'sv' => 'Vidareutbildning'),
        'Courses' => array('fi' => 'Kurssit', 'en' => 'Courses', 'sv' => 'Kurser'),
        'Projects' => array('fi' => 'Projektit', 'en' => 'Projects', 'sv' => 'Projekt'),
        'Skills' => array('fi' => 'Osaaminen', 'en' => 'Skills', 'sv' => 'Kompetens'),
        'Contact' => array('fi' => 'Yhteystiedot', 'en' => 'Contact', 'sv' => 'Kontakt'),
        'Blogi' => array('fi' => 'Blogi', 'en' => 'Blog', 'sv' => 'Blogg'),
        'Blog' => array('fi' => 'Blogi', 'en' => 'Blog', 'sv' => 'Blogg'),
        'Read more' => array('fi' => 'Lue lisää', 'en' => 'Read more', 'sv' => 'Läs mer'),
        'Takaisin blogiin' => array('fi' => 'Takaisin blogiin', 'en' => 'Back to blog', 'sv' => 'Tillbaka till bloggen'),
        'Käännökset:' => array('fi' => 'Käännökset:', 'en' => 'Translations:', 'sv' => 'Översättningar:'),
        'Julkaistu:' => array('fi' => 'Julkaistu:', 'en' => 'Published:', 'sv' => 'Publicerad:'),
        'Kategoriat:' => array('fi' => 'Kategoriat:', 'en' => 'Categories:', 'sv' => 'Kategorier:'),
        'Avainsanat:' => array('fi' => 'Avainsanat:', 'en' => 'Tags:', 'sv' => 'Taggar:'),
        'Edellinen artikkeli' => array('fi' => 'Edellinen artikkeli', 'en' => 'Previous post', 'sv' => 'Föregående artikel'),
        'Seuraava artikkeli' => array('fi' => 'Seuraava artikkeli', 'en' => 'Next post', 'sv' => 'Nästa artikel'),
    );
}

function cv_one_pager_t($string, $lang = null) {
    if (empty($lang)) {
        $lang = function_exists('pll_current_language') ? pll_current_language('slug') : 'fi';
    }
    if (empty($lang)) {
        $lang = 'fi';
    }

    if (function_exists('pll__')) {
        $translated = pll__($string);
        if ($translated !== $string && !empty($translated)) {
            return $translated;
        }
    }

    $map = cv_one_pager_get_translations_map();
    if (isset($map[$string][$lang])) {
        return $map[$string][$lang];
    }

    return $string;
}

function cv_one_pager_get_default_references($lang = null) {
    if (empty($lang)) {
        $lang = function_exists('pll_current_language') ? pll_current_language('slug') : 'fi';
    }
    if (empty($lang)) {
        $lang = 'fi';
    }

    $defaults = array(
        'fi' => array(
            array(
                'name' => 'Mikko Laaksonen',
                'title' => 'Toimitusjohtaja',
                'company' => 'DigiNordic Solutions Oy',
                'email' => 'mikko.laaksonen@diginordic.fi',
                'phone' => '+358 40 555 1234',
            ),
            array(
                'name' => 'Sanna Hämäläinen',
                'title' => 'Kehitysjohtaja (Head of Development)',
                'company' => 'CloudScale Technologies Oy',
                'email' => 'sanna.hamalainen@cloudscale.fi',
                'phone' => '+358 50 555 5678',
            ),
        ),
        'en' => array(
            array(
                'name' => 'Mikko Laaksonen',
                'title' => 'Chief Executive Officer (CEO)',
                'company' => 'DigiNordic Solutions Oy',
                'email' => 'mikko.laaksonen@diginordic.fi',
                'phone' => '+358 40 555 1234',
            ),
            array(
                'name' => 'Sanna Hämäläinen',
                'title' => 'Head of Development',
                'company' => 'CloudScale Technologies Oy',
                'email' => 'sanna.hamalainen@cloudscale.fi',
                'phone' => '+358 50 555 5678',
            ),
        ),
        'sv' => array(
            array(
                'name' => 'Mikko Laaksonen',
                'title' => 'Verkställande direktör (VD)',
                'company' => 'DigiNordic Solutions Ab',
                'email' => 'mikko.laaksonen@diginordic.fi',
                'phone' => '+358 40 555 1234',
            ),
            array(
                'name' => 'Sanna Hämäläinen',
                'title' => 'Utvecklingschef (Head of Development)',
                'company' => 'CloudScale Technologies Ab',
                'email' => 'sanna.hamalainen@cloudscale.fi',
                'phone' => '+358 50 555 5678',
            ),
        ),
    );

    return isset($defaults[$lang]) ? $defaults[$lang] : $defaults['fi'];
}

function cv_one_pager_seed_default_references($force = false) {
    if (!function_exists('pll_set_post_language')) {
        return false;
    }

    if (!$force && get_option('cv_references_seeded')) {
        return false;
    }

    $existing = get_posts(array(
        'post_type' => 'cv_reference',
        'numberposts' => 1,
        'post_status' => 'any',
        'suppress_filters' => true,
    ));
    if (!empty($existing) && !$force) {
        update_option('cv_references_seeded', 1);
        return false;
    }

    $langs = array('fi', 'en', 'sv');
    $created_by_lang = array();

    foreach ($langs as $lang) {
        $refs = cv_one_pager_get_default_references($lang);
        $created_by_lang[$lang] = array();
        $order = count($refs);

        foreach ($refs as $idx => $ref_data) {
            $post_id = wp_insert_post(array(
                'post_title' => $ref_data['name'],
                'post_type' => 'cv_reference',
                'post_status' => 'publish',
                'menu_order' => $order--,
            ));

            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_cv_reference_title', $ref_data['title']);
                update_post_meta($post_id, 'cv_reference_title', $ref_data['title']);
                update_post_meta($post_id, '_cv_reference_company', $ref_data['company']);
                update_post_meta($post_id, 'cv_reference_company', $ref_data['company']);
                update_post_meta($post_id, '_cv_reference_email', $ref_data['email']);
                update_post_meta($post_id, 'cv_reference_email', $ref_data['email']);
                update_post_meta($post_id, '_cv_reference_phone', $ref_data['phone']);
                update_post_meta($post_id, 'cv_reference_phone', $ref_data['phone']);

                pll_set_post_language($post_id, $lang);
                $created_by_lang[$lang][$idx] = $post_id;
            }
        }
    }

    if (function_exists('pll_save_post_translations')) {
        $count = count(cv_one_pager_get_default_references('fi'));
        for ($i = 0; $i < $count; $i++) {
            $group = array();
            foreach ($langs as $lang) {
                if (!empty($created_by_lang[$lang][$i])) {
                    $group[$lang] = $created_by_lang[$lang][$i];
                }
            }
            if (count($group) > 1) {
                pll_save_post_translations($group);
            }
        }
    }

    update_option('cv_references_seeded', 1);
    return true;
}
add_action('admin_init', 'cv_one_pager_seed_default_references');

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
        'References',
        'Reference',
        'Add Reference',
        'Edit Reference',
        'Suosittelijat',
        'Suosittelija',
        'Lisää suosittelija',
        'Muokkaa suosittelijaa',
        'Referenser',
        'Referens',
        'Lägg till referens',
        'Redigera referens',
        'Titteli',
        'Yritys',
        'Sähköposti',
        'Puhelin',
        'Job Title',
        'Company',
        'Email',
        'Phone',
        'Titel',
        'Företag',
        'E-post',
        'Telefon',
        'Ei suosittelijoita vielä lisättynä.',
        'No references added yet.',
        'Inga referenser tillagda ännu.',
        'Suosittelijat pyydettäessä',
        'References available upon request',
        'Takaisin blogiin',
        'Käännökset:',
        'Julkaistu:',
        'Kategoriat:',
        'Avainsanat:',
        'Edellinen artikkeli',
        'Seuraava artikkeli',
    );

    foreach ($strings as $string) {
        pll_register_string('cv-one-pager', $string, 'CV One Pager');
    }
}
add_action('init', 'cv_one_pager_register_polylang_strings', 20);

function cv_one_pager_limit_acf_group_to_front_page($match, $rule, $screen, $field_group) {
    if (empty($field_group['key']) || $field_group['key'] !== 'group_cv_one_pager') {
        return $match;
    }

    if (!is_array($screen) || empty($screen['post_type']) || $screen['post_type'] !== 'page') {
        return false;
    }

    $front_page_id = (int) get_option('page_on_front');
    if ($front_page_id <= 0) {
        return false;
    }

    $post_id = 0;
    if (!empty($screen['post_id'])) {
        $post_id = (int) $screen['post_id'];
    } elseif (!empty($_GET['post'])) {
        $post_id = (int) $_GET['post'];
    } elseif (!empty($_POST['post_ID'])) {
        $post_id = (int) $_POST['post_ID'];
    }

    return $post_id === $front_page_id;
}
add_filter('acf/location/rule_match', 'cv_one_pager_limit_acf_group_to_front_page', 20, 4);

function cv_one_pager_polylang_post_types($post_types, $is_settings) {
    if ($is_settings) {
        $post_types['post'] = 'post';
        $post_types['cv_experience'] = 'cv_experience';
        $post_types['cv_education'] = 'cv_education';
        $post_types['cv_course'] = 'cv_course';
        $post_types['cv_project'] = 'cv_project';
        $post_types['cv_skill'] = 'cv_skill';
        $post_types['cv_badge'] = 'cv_badge';
        $post_types['cv_reference'] = 'cv_reference';
        return $post_types;
    }

    $post_types[] = 'post';
    $post_types[] = 'cv_experience';
    $post_types[] = 'cv_education';
    $post_types[] = 'cv_course';
    $post_types[] = 'cv_project';
    $post_types[] = 'cv_skill';
    $post_types[] = 'cv_badge';
    $post_types[] = 'cv_reference';
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

function cv_one_pager_get_default_og_image_data() {
    $image_path = get_template_directory() . '/assets/mattikiviharju5-600-600.png';
    $image_url = get_template_directory_uri() . '/assets/mattikiviharju5-600-600.png';

    $mime_type = 'image/png';
    $width = 0;
    $height = 0;

    if (file_exists($image_path)) {
        $size = @getimagesize($image_path);
        if (is_array($size)) {
            $width = !empty($size[0]) ? (int) $size[0] : 0;
            $height = !empty($size[1]) ? (int) $size[1] : 0;
            if (!empty($size['mime'])) {
                $mime_type = $size['mime'];
            }
        }
    }

    return array(
        'url' => $image_url,
        'mime' => $mime_type,
        'width' => $width,
        'height' => $height,
    );
}

function cv_one_pager_output_og_meta_tags() {
    if (is_admin() || is_feed() || is_robots() || is_trackback()) {
        return;
    }

    $title = wp_get_document_title();
    $description = '';

    if (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            $excerpt = has_excerpt($post) ? get_the_excerpt($post) : '';
            if ($excerpt) {
                $description = wp_strip_all_tags($excerpt);
            } else {
                $description = wp_trim_words(wp_strip_all_tags((string) $post->post_content), 30, '…');
            }
        }
    }

    if ($description === '') {
        $description = get_bloginfo('description', 'display');
    }

    $url = is_singular() ? get_permalink() : home_url(add_query_arg(array(), $GLOBALS['wp']->request ?? ''));
    $og_type = is_singular() ? 'article' : 'website';
    $site_name = get_bloginfo('name', 'display');
    $image = cv_one_pager_get_default_og_image_data();

    echo "\n";
    echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url($image['url']) . '">' . "\n";
    echo '<meta property="og:image:secure_url" content="' . esc_url($image['url']) . '">' . "\n";
    echo '<meta property="og:image:type" content="' . esc_attr($image['mime']) . '">' . "\n";

    if (!empty($image['width']) && !empty($image['height'])) {
        echo '<meta property="og:image:width" content="' . esc_attr((string) $image['width']) . '">' . "\n";
        echo '<meta property="og:image:height" content="' . esc_attr((string) $image['height']) . '">' . "\n";
    }

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url($image['url']) . '">' . "\n";
}
add_action('wp_head', 'cv_one_pager_output_og_meta_tags', 6);

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

    add_meta_box(
        'cv_reference_meta',
        'Reference Details',
        'cv_one_pager_render_reference_meta_box',
        'cv_reference',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'cv_one_pager_add_meta_boxes', 10);

function cv_one_pager_render_reference_meta_box($post) {
    wp_nonce_field('cv_reference_meta_save', 'cv_reference_meta_nonce');
    $title = get_post_meta($post->ID, '_cv_reference_title', true);
    if (empty($title)) {
        $title = get_post_meta($post->ID, 'cv_reference_title', true);
    }
    $company = get_post_meta($post->ID, '_cv_reference_company', true);
    if (empty($company)) {
        $company = get_post_meta($post->ID, 'cv_reference_company', true);
    }
    $email = get_post_meta($post->ID, '_cv_reference_email', true);
    if (empty($email)) {
        $email = get_post_meta($post->ID, 'cv_reference_email', true);
    }
    $phone = get_post_meta($post->ID, '_cv_reference_phone', true);
    if (empty($phone)) {
        $phone = get_post_meta($post->ID, 'cv_reference_phone', true);
    }

    echo '<div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #2271b1;">';
    echo '<p style="margin: 0; font-size: 13px; color: #646970;"><strong>Instructions:</strong><br>';
    echo 'Full Name / Henkilönimi: Use title field above<br>';
    echo 'Job Title / Titteli: Use field below<br>';
    echo 'Company / Yritys: Use field below<br>';
    echo 'Email / Sähköposti: Use field below<br>';
    echo 'Phone / Puhelin: Use field below</p>';
    echo '</div>';

    echo '<p><label for="cv_reference_title"><strong>Job Title / Titteli</strong></label></p>';
    echo '<input type="text" id="cv_reference_title" name="cv_reference_title" value="' . esc_attr($title) . '" class="widefat" />';

    echo '<p><label for="cv_reference_company"><strong>Company / Yritys</strong></label></p>';
    echo '<input type="text" id="cv_reference_company" name="cv_reference_company" value="' . esc_attr($company) . '" class="widefat" />';

    echo '<p><label for="cv_reference_email"><strong>Email / Sähköposti</strong></label></p>';
    echo '<input type="email" id="cv_reference_email" name="cv_reference_email" value="' . esc_attr($email) . '" class="widefat" />';

    echo '<p><label for="cv_reference_phone"><strong>Phone / Puhelin</strong></label></p>';
    echo '<input type="text" id="cv_reference_phone" name="cv_reference_phone" value="' . esc_attr($phone) . '" class="widefat" />';
}

function cv_one_pager_render_experience_meta_box($post) {
    wp_nonce_field('cv_experience_meta_save', 'cv_experience_meta_nonce');
    $company = get_post_meta($post->ID, '_cv_experience_company', true);
    if (empty($company)) {
        $company = get_post_meta($post->ID, 'cv_experience_company', true);
    }
    $dates = get_post_meta($post->ID, '_cv_experience_dates', true);
    if (empty($dates)) {
        $dates = get_post_meta($post->ID, 'cv_experience_dates', true);
    }
    $summary = $post->post_excerpt;

    echo '<div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #2271b1;">';
    echo '<p style="margin: 0; font-size: 13px; color: #646970;"><strong>Instructions:</strong><br>';
    echo 'Role: Use title field above<br>';
    echo 'Employer / Company: Use field below<br>';
    echo 'Dates: Use field below<br>';
    echo 'Description (Summary): Use field below<br>';
    echo 'Full details: Use main editor below</p>';
    echo '</div>';

    echo '<p><label for="cv_experience_company"><strong>Employer / Company</strong></label></p>';
    echo '<input type="text" id="cv_experience_company" name="cv_experience_company" value="' . esc_attr($company) . '" class="widefat" />';

    echo '<p><label for="cv_experience_dates"><strong>Dates</strong></label></p>';
    echo '<input type="text" id="cv_experience_dates" name="cv_experience_dates" value="' . esc_attr($dates) . '" class="widefat" />';

    echo '<p><label for="cv_experience_summary"><strong>Description (Summary)</strong></label></p>';
    echo '<textarea id="cv_experience_summary" name="cv_experience_summary" rows="3" class="widefat" placeholder="Short description shown on CV page...">' . esc_textarea($summary) . '</textarea>';
}

function cv_one_pager_render_education_meta_box($post) {
    wp_nonce_field('cv_education_meta_save', 'cv_education_meta_nonce');
    $school_name = $post->post_excerpt;
    $dates = get_post_meta($post->ID, '_cv_education_dates', true);
    if (empty($dates)) {
        $dates = get_post_meta($post->ID, 'cv_education_dates', true);
    }

    echo '<div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #2271b1;">';
    echo '<p style="margin: 0; font-size: 13px; color: #646970;"><strong>Instructions:</strong><br>';
    echo 'Degree / Qualification: Use title field above<br>';
    echo 'School / Institution (stored in excerpt): Use field below<br>';
    echo 'Dates: Use field below<br>';
    echo '</p>';
    echo '</div>';

    echo '<p><label for="cv_education_school"><strong>School / Institution</strong></label></p>';
    echo '<input type="text" id="cv_education_school" name="cv_education_school" value="' . esc_attr($school_name) . '" class="widefat" />';

    echo '<p><label for="cv_education_dates"><strong>Dates</strong></label></p>';
    echo '<input type="text" id="cv_education_dates" name="cv_education_dates" value="' . esc_attr($dates) . '" class="widefat" />';
}

function cv_one_pager_render_course_meta_box($post) {
    wp_nonce_field('cv_course_meta_save', 'cv_course_meta_nonce');
    $dates = get_post_meta($post->ID, '_cv_course_dates', true);
    if (empty($dates)) {
        $dates = get_post_meta($post->ID, 'cv_course_dates', true);
    }
    $provider = get_post_meta($post->ID, '_cv_course_provider', true);
    if (empty($provider)) {
        $provider = get_post_meta($post->ID, 'cv_course_provider', true);
    }
    if ($provider === '') {
        $provider = $post->post_excerpt;
    }
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
            • <strong>Dates:</strong> Use the field above
        </p>
    </div>
    <?php
}

function cv_one_pager_render_project_meta_box($post) {
    wp_nonce_field('cv_project_meta_save', 'cv_project_meta_nonce');
    $description = $post->post_excerpt;
    // Normalize any non-breaking spaces or invisible unicode characters so text wraps at spaces
    $description = str_replace(array("\xc2\xa0", "\u{00a0}", "\u{200b}", "\u{202f}", "\u{feff}"), ' ', $description);

    $meta = get_post_meta($post->ID, '_cv_project_meta', true);
    if (empty($meta)) {
        $meta = get_post_meta($post->ID, 'cv_project_meta', true);
    }
    $link = get_post_meta($post->ID, '_cv_project_link', true);
    if (empty($link)) {
        $link = get_post_meta($post->ID, 'cv_project_link', true);
    }
    $link_label = get_post_meta($post->ID, '_cv_project_link_label', true);
    if (empty($link_label)) {
        $link_label = get_post_meta($post->ID, 'cv_project_link_label', true);
    }

    $links = get_post_meta($post->ID, '_cv_project_links', true);
    if (empty($links)) {
        $links = get_post_meta($post->ID, 'cv_project_links', true);
    }
    if (empty($links) || !is_array($links)) {
        $links = array();
        if (!empty($link)) {
            $links[] = array(
                'url' => $link,
                'label' => $link_label,
            );
        }
    }
    if (empty($links)) {
        $links[] = array('url' => '', 'label' => '');
    }
    
    echo '<div style="margin-bottom: 20px; padding: 12px; background: #f9f9f9; border-left: 4px solid #2271b1;">';
    echo '<p style="margin: 0; font-size: 13px; color: #646970;"><strong>Instructions:</strong><br>';
    echo 'Project Title: Use title field above<br>';
    echo 'Description: Use field below (Summary wraps cleanly at spaces)<br>';
    echo 'Tech Stack: Use Tech Stack field below<br>';
    echo 'Project Links: Add one or more URLs with titles below (works in free ACF)<br>';
    echo 'Full Details: Use main editor below</p>';
    echo '</div>';
    
    echo '<p><label for="cv_project_description"><strong>Description (Summary)</strong></label></p>';
    echo '<textarea id="cv_project_description" name="cv_project_description" rows="3" class="widefat" placeholder="Short project description..." style="white-space: pre-wrap; word-break: normal; overflow-wrap: break-word;">' . esc_textarea($description) . '</textarea>';
    echo '<p style="margin-top: 5px; font-size: 12px; color: #646970;">Brief summary shown on CV page (rivittyy välilyöntien kohdalla)</p>';
    
    echo '<p><label for="cv_project_meta"><strong>Tech Stack (e.g. React, PHP, MySQL)</strong></label></p>';
    echo '<input type="text" id="cv_project_meta" name="cv_project_meta" value="' . esc_attr($meta) . '" class="widefat" placeholder="React, PHP, MySQL" />';

    echo '<div style="margin-top: 20px; margin-bottom: 5px;">';
    echo '<label><strong>Project Links / Projektilinkit (URL & Title)</strong></label>';
    echo '<p style="margin: 3px 0 10px 0; font-size: 12px; color: #646970;">Voit lisätä useamman projektilinkin ja linkin otsikon ilmaisessa ACF-versiossa. Ensimmäinen linkki tallennetaan myös päälinkiksi taaksepäinyhteensopivuutta varten.</p>';
    echo '</div>';

    echo '<div id="cv-project-links-container">';
    foreach ($links as $idx => $link_item) {
        $curr_url = isset($link_item['url']) ? $link_item['url'] : '';
        $curr_label = isset($link_item['label']) ? $link_item['label'] : (isset($link_item['title']) ? $link_item['title'] : '');
        $badge_num = $idx + 1;
        echo '<div class="cv-project-link-row" style="background: #fff; border: 1px solid #ccd0d4; padding: 12px; margin-bottom: 10px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">';
        echo '  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
        echo '    <strong class="cv-project-link-badge" style="color: #2271b1; font-size: 13px;">Linkki #' . $badge_num . '</strong>';
        echo '    <button type="button" class="button-link-delete cv-project-link-remove" style="color: #b32d2e; text-decoration: none; cursor: pointer; font-size: 12px;">Poista / Remove</button>';
        echo '  </div>';
        echo '  <div style="margin-bottom: 8px;">';
        echo '    <label style="display: block; font-size: 12px; margin-bottom: 3px; font-weight: 600;">Project URL</label>';
        echo '    <input type="url" name="cv_project_links[' . $idx . '][url]" value="' . esc_attr($curr_url) . '" class="widefat cv-project-link-url" placeholder="https://..." />';
        echo '  </div>';
        echo '  <div>';
        echo '    <label style="display: block; font-size: 12px; margin-bottom: 3px; font-weight: 600;">Project Link Title</label>';
        echo '    <input type="text" name="cv_project_links[' . $idx . '][label]" value="' . esc_attr($curr_label) . '" class="widefat cv-project-link-label" placeholder="View project" />';
        echo '  </div>';
        echo '</div>';
    }
    echo '</div>';

    echo '<p style="margin-top: 8px; margin-bottom: 18px;">';
    echo '  <button type="button" class="button button-secondary" id="cv-project-link-add">+ Lisää linkki / Add Project Link</button>';
    echo '</p>';
    
    $youtube_url = get_post_meta($post->ID, '_cv_project_youtube', true);
    if (empty($youtube_url)) {
        $youtube_url = get_post_meta($post->ID, 'cv_project_youtube', true);
    }
    $youtube_videos = get_post_meta($post->ID, '_cv_project_youtube_videos', true);
    if (empty($youtube_videos)) {
        $youtube_videos = get_post_meta($post->ID, 'cv_project_youtube_videos', true);
    }
    $youtube_text = '';
    if (!empty($youtube_videos) && is_array($youtube_videos)) {
        $youtube_text = implode("\n", $youtube_videos);
    } elseif (!empty($youtube_url)) {
        $youtube_text = $youtube_url;
    }
    echo '<p><label for="cv_project_youtube"><strong>YouTube Video URLs (one per line)</strong></label></p>';
    echo '<textarea id="cv_project_youtube" name="cv_project_youtube" rows="3" class="widefat" placeholder="https://www.youtube.com/watch?v=...&#10;https://youtu.be/...">' . esc_textarea($youtube_text) . '</textarea>';
    echo '<p style="margin-top: 5px; font-size: 12px; color: #646970;">Videos will be shown with screenshots in the gallery overlay. Add one YouTube URL per line to include multiple videos.</p>';
}

function cv_one_pager_render_project_screenshots_meta_box($post) {
    wp_nonce_field('cv_project_screenshots_save', 'cv_project_screenshots_nonce');
    $value = get_post_meta($post->ID, '_cv_project_screenshots', true);
    if (empty($value)) {
        $value = get_post_meta($post->ID, 'cv_project_screenshots', true);
    }
    $saved_alts = get_post_meta($post->ID, '_cv_project_screenshot_alts', true);
    if (empty($saved_alts)) {
        $saved_alts = get_post_meta($post->ID, 'cv_project_screenshot_alts', true);
    }
    $saved_alts = is_array($saved_alts) ? $saved_alts : array();
    $ids = array();

    if (is_array($value)) {
        $ids = array_filter(array_map('absint', $value));
    } elseif (is_string($value) && $value !== '') {
        $ids = array_filter(array_map('absint', explode(',', $value)));
    }
    if (empty($ids) && has_post_thumbnail($post->ID)) {
        $thumb_id = get_post_thumbnail_id($post->ID);
        if ($thumb_id) {
            $ids = array($thumb_id);
        }
    }

    echo '<p><button type="button" class="button" id="cv-project-screenshots-add">Add Screenshots</button></p>';
    echo '<input type="hidden" id="cv_project_screenshots" name="cv_project_screenshots" value="' . esc_attr(implode(',', $ids)) . '" />';
    echo '<div id="cv-project-screenshots-list" class="cv-project-screenshots-list">';
    foreach ($ids as $attachment_id) {
        $thumb = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        if (!$thumb) {
            continue;
        }
        $default_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        $alt = isset($saved_alts[$attachment_id]) ? $saved_alts[$attachment_id] : $default_alt;
        $full = wp_get_attachment_image_url($attachment_id, 'large');
        if (!$full) {
            $full = wp_get_attachment_image_url($attachment_id, 'full');
        }
        if (!$full) {
            $full = $thumb;
        }
        echo '<div class="cv-project-screenshot" data-id="' . esc_attr($attachment_id) . '">';
        echo '<img src="' . esc_url($thumb) . '" data-full="' . esc_url($full) . '" alt="' . esc_attr($alt) . '" />';
        echo '<input type="text" class="widefat cv-project-screenshot-alt" name="cv_project_screenshot_alts[' . esc_attr($attachment_id) . ']" value="' . esc_attr($alt) . '" placeholder="Image alt text" />';
        echo '<button type="button" class="button-link cv-project-screenshot-remove">Remove</button>';
        echo '</div>';
    }
    echo '</div>';
    echo '<div id="cv-project-preview-modal" class="cv-project-preview-modal" aria-hidden="true">';
    echo '<button type="button" class="cv-project-preview-close" aria-label="Close preview">&times;</button>';
    echo '<img src="" alt="" class="cv-project-preview-image" />';
    echo '</div>';
    echo '<style>.cv-project-screenshots-list{display:flex;flex-wrap:wrap;gap:12px}.cv-project-screenshot{display:flex;flex-direction:column;align-items:flex-start;gap:6px;min-width:180px}.cv-project-screenshot img{width:120px;height:auto;border:1px solid #ccd0d4;border-radius:4px;cursor:zoom-in}.cv-project-screenshot-alt{max-width:220px}.cv-project-preview-modal{position:fixed;inset:0;background:rgba(0,0,0,.82);display:none;align-items:center;justify-content:center;z-index:100000}.cv-project-preview-modal.is-visible{display:flex}.cv-project-preview-image{max-width:min(92vw,1400px);max-height:88vh;border-radius:6px;box-shadow:0 10px 40px rgba(0,0,0,.5)}.cv-project-preview-close{position:absolute;top:16px;right:20px;font-size:34px;line-height:1;border:0;background:transparent;color:#fff;cursor:pointer}</style>';
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
        if (isset($_POST['cv_experience_company'])) {
            update_post_meta($post_id, '_cv_experience_company', sanitize_text_field($_POST['cv_experience_company']));
        }
        if (isset($_POST['cv_experience_dates'])) {
            update_post_meta($post_id, '_cv_experience_dates', cv_one_pager_sanitize_preserve_dashes($_POST['cv_experience_dates']));
        }
        if (isset($_POST['cv_experience_summary'])) {
            remove_action('save_post', 'cv_one_pager_save_meta_boxes');

            $clean_exp_summary = sanitize_textarea_field($_POST['cv_experience_summary']);
            $clean_exp_summary = str_replace(array("\xc2\xa0", "\u{00a0}", "\u{200b}", "\u{202f}", "\u{feff}"), ' ', $clean_exp_summary);

            wp_update_post(array(
                'ID' => $post_id,
                'post_excerpt' => $clean_exp_summary
            ));

            add_action('save_post', 'cv_one_pager_save_meta_boxes');
        }

        // Re-sync menu_orders so newest to oldest order is maintained
        if (function_exists('cv_one_pager_sync_all_experience_menu_orders')) {
            cv_one_pager_sync_all_experience_menu_orders();
        }
    }
    if (isset($_POST['cv_education_meta_nonce']) && wp_verify_nonce($_POST['cv_education_meta_nonce'], 'cv_education_meta_save')) {
        if (isset($_POST['cv_education_school'])) {
            remove_action('save_post', 'cv_one_pager_save_meta_boxes');

            wp_update_post(array(
                'ID' => $post_id,
                'post_excerpt' => sanitize_text_field($_POST['cv_education_school'])
            ));

            add_action('save_post', 'cv_one_pager_save_meta_boxes');
        }
        if (isset($_POST['cv_education_dates'])) {
            update_post_meta($post_id, '_cv_education_dates', cv_one_pager_sanitize_preserve_dashes($_POST['cv_education_dates']));
        }
    }
    if (isset($_POST['cv_course_meta_nonce']) && wp_verify_nonce($_POST['cv_course_meta_nonce'], 'cv_course_meta_save')) {
        if (isset($_POST['cv_course_provider'])) {
            update_post_meta($post_id, '_cv_course_provider', sanitize_text_field($_POST['cv_course_provider']));
        }
        if (isset($_POST['cv_course_dates'])) {
            update_post_meta($post_id, '_cv_course_dates', cv_one_pager_sanitize_preserve_dashes($_POST['cv_course_dates']));
        }
    }
    if (isset($_POST['cv_project_meta_nonce']) && wp_verify_nonce($_POST['cv_project_meta_nonce'], 'cv_project_meta_save')) {
        if (isset($_POST['cv_project_description'])) {
            // Unhook to prevent infinite loop
            remove_action('save_post', 'cv_one_pager_save_meta_boxes');
            
            $clean_proj_desc = sanitize_textarea_field($_POST['cv_project_description']);
            // Normalize non-breaking spaces and invisible characters so text wraps at spaces
            $clean_proj_desc = str_replace(array("\xc2\xa0", "\u{00a0}", "\u{200b}", "\u{202f}", "\u{feff}"), ' ', $clean_proj_desc);

            // Update description in post_excerpt and post_content
            wp_update_post(array(
                'ID' => $post_id,
                'post_excerpt' => $clean_proj_desc,
                'post_content' => $clean_proj_desc,
            ));

            update_post_meta($post_id, '_cv_project_description', $clean_proj_desc);
            update_post_meta($post_id, 'cv_project_description', $clean_proj_desc);
            
            // Re-hook
            add_action('save_post', 'cv_one_pager_save_meta_boxes');
        }
        if (isset($_POST['cv_project_meta'])) {
            update_post_meta($post_id, '_cv_project_meta', sanitize_text_field($_POST['cv_project_meta']));
        }

        // Save multiple project links (supporting free ACF setup)
        if (isset($_POST['cv_project_links']) && is_array($_POST['cv_project_links'])) {
            $cleaned_links = array();
            foreach ($_POST['cv_project_links'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $u = isset($item['url']) ? esc_url_raw(trim($item['url'])) : '';
                $l = isset($item['label']) ? sanitize_text_field(trim($item['label'])) : '';
                if (!empty($u)) {
                    $cleaned_links[] = array(
                        'url' => $u,
                        'label' => $l,
                    );
                }
            }
            if (!empty($cleaned_links)) {
                update_post_meta($post_id, '_cv_project_links', $cleaned_links);
                update_post_meta($post_id, 'cv_project_links', $cleaned_links);
                update_post_meta($post_id, '_cv_project_link', $cleaned_links[0]['url']);
                update_post_meta($post_id, 'cv_project_link', $cleaned_links[0]['url']);
                update_post_meta($post_id, '_cv_project_link_label', $cleaned_links[0]['label']);
                update_post_meta($post_id, 'cv_project_link_label', $cleaned_links[0]['label']);
            } else {
                delete_post_meta($post_id, '_cv_project_links');
                delete_post_meta($post_id, 'cv_project_links');
                delete_post_meta($post_id, '_cv_project_link');
                delete_post_meta($post_id, 'cv_project_link');
                delete_post_meta($post_id, '_cv_project_link_label');
                delete_post_meta($post_id, 'cv_project_link_label');
            }
        } elseif (isset($_POST['cv_project_link'])) {
            $u = esc_url_raw(trim($_POST['cv_project_link']));
            $l = isset($_POST['cv_project_link_label']) ? sanitize_text_field(trim($_POST['cv_project_link_label'])) : '';
            if (!empty($u)) {
                $cleaned_links = array(array('url' => $u, 'label' => $l));
                update_post_meta($post_id, '_cv_project_links', $cleaned_links);
                update_post_meta($post_id, 'cv_project_links', $cleaned_links);
                update_post_meta($post_id, '_cv_project_link', $u);
                update_post_meta($post_id, 'cv_project_link', $u);
                update_post_meta($post_id, '_cv_project_link_label', $l);
                update_post_meta($post_id, 'cv_project_link_label', $l);
            } else {
                delete_post_meta($post_id, '_cv_project_links');
                delete_post_meta($post_id, 'cv_project_links');
                delete_post_meta($post_id, '_cv_project_link');
                delete_post_meta($post_id, 'cv_project_link');
                delete_post_meta($post_id, '_cv_project_link_label');
                delete_post_meta($post_id, 'cv_project_link_label');
            }
        }
        if (isset($_POST['cv_project_youtube'])) {
            $raw_yt = sanitize_textarea_field($_POST['cv_project_youtube']);
            $lines = array_filter(array_map('trim', explode("\n", $raw_yt)));
            $urls = array();
            foreach ($lines as $line) {
                $cleaned = esc_url_raw($line);
                if (!empty($cleaned)) {
                    $urls[] = $cleaned;
                }
            }
            if (!empty($urls)) {
                update_post_meta($post_id, '_cv_project_youtube', $urls[0]);
                update_post_meta($post_id, '_cv_project_youtube_videos', $urls);
            } else {
                delete_post_meta($post_id, '_cv_project_youtube');
                delete_post_meta($post_id, '_cv_project_youtube_videos');
            }
        }
    }

    if (isset($_POST['cv_project_screenshots_nonce']) && wp_verify_nonce($_POST['cv_project_screenshots_nonce'], 'cv_project_screenshots_save')) {
        if (isset($_POST['cv_project_screenshots'])) {
            $raw_ids = sanitize_text_field($_POST['cv_project_screenshots']);
            $ids = array_filter(array_map('absint', explode(',', $raw_ids)));
            update_post_meta($post_id, '_cv_project_screenshots', $ids);

            $alts = array();
            if (isset($_POST['cv_project_screenshot_alts']) && is_array($_POST['cv_project_screenshot_alts'])) {
                foreach ($_POST['cv_project_screenshot_alts'] as $attachment_id => $alt_text) {
                    $attachment_id = absint($attachment_id);
                    if ($attachment_id > 0 && in_array($attachment_id, $ids, true)) {
                        $alts[$attachment_id] = sanitize_text_field($alt_text);
                    }
                }
            }
            update_post_meta($post_id, '_cv_project_screenshot_alts', $alts);
        }
    }

    if (isset($_POST['cv_reference_meta_nonce']) && wp_verify_nonce($_POST['cv_reference_meta_nonce'], 'cv_reference_meta_save')) {
        if (isset($_POST['cv_reference_title'])) {
            update_post_meta($post_id, '_cv_reference_title', sanitize_text_field($_POST['cv_reference_title']));
            update_post_meta($post_id, 'cv_reference_title', sanitize_text_field($_POST['cv_reference_title']));
        }
        if (isset($_POST['cv_reference_company'])) {
            update_post_meta($post_id, '_cv_reference_company', sanitize_text_field($_POST['cv_reference_company']));
            update_post_meta($post_id, 'cv_reference_company', sanitize_text_field($_POST['cv_reference_company']));
        }
        if (isset($_POST['cv_reference_email'])) {
            update_post_meta($post_id, '_cv_reference_email', sanitize_email($_POST['cv_reference_email']));
            update_post_meta($post_id, 'cv_reference_email', sanitize_email($_POST['cv_reference_email']));
        }
        if (isset($_POST['cv_reference_phone'])) {
            update_post_meta($post_id, '_cv_reference_phone', sanitize_text_field($_POST['cv_reference_phone']));
            update_post_meta($post_id, 'cv_reference_phone', sanitize_text_field($_POST['cv_reference_phone']));
        }
    }
}
add_action('save_post', 'cv_one_pager_save_meta_boxes');

function cv_one_pager_get_blog_url() {
    $posts_page_id = (int) get_option('page_for_posts');
    if ($posts_page_id > 0) {
        if (function_exists('pll_get_post')) {
            $translated_id = pll_get_post($posts_page_id);
            if ($translated_id) {
                return get_permalink($translated_id);
            }
        }
        return get_permalink($posts_page_id);
    }

    $pages = get_pages(array(
        'meta_key' => '_wp_page_template',
        'meta_value' => 'page-blog.php',
        'number' => 1,
    ));
    if (!empty($pages)) {
        $blog_page_id = (int) $pages[0]->ID;
        if (function_exists('pll_get_post')) {
            $translated_id = pll_get_post($blog_page_id);
            if ($translated_id) {
                return get_permalink($translated_id);
            }
        }
        return get_permalink($blog_page_id);
    }

    if (function_exists('pll_home_url')) {
        return trailingslashit(pll_home_url()) . 'blog/';
    }

    return home_url('/blog/');
}

function cv_one_pager_render_post_card($post) {
    $title = get_the_title($post);
    $excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words($post->post_content, 28);
    $permalink = get_permalink($post);
    $date = get_the_date('', $post);
    $thumbnail = has_post_thumbnail($post) ? get_the_post_thumbnail($post, 'medium_large', array('class' => 'post-card-thumb', 'alt' => esc_attr($title))) : '';
    ob_start();
    ?>
    <article class="post-card">
        <?php if ($thumbnail) : ?>
            <a href="<?php echo esc_url($permalink); ?>" class="post-card-thumb-link" aria-label="<?php echo esc_attr($title); ?>">
                <?php echo $thumbnail; ?>
            </a>
        <?php endif; ?>
        <div class="post-card-body">
            <p class="post-meta"><i class="bi bi-calendar3"></i> <?php echo esc_html($date); ?></p>
            <h3 class="post-title"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($title); ?></a></h3>
            <p class="post-excerpt"><?php echo esc_html($excerpt); ?></p>
        </div>
        <a class="post-link" href="<?php echo esc_url($permalink); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Read more') : 'Read more'); ?> &rarr;</a>
    </article>
    <?php
    return ob_get_clean();
}

function cv_one_pager_load_more_posts() {
    check_ajax_referer('cv_load_more_posts', 'nonce');

    $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
    $per_page = isset($_POST['perPage']) ? max(1, (int) $_POST['perPage']) : 6;
    $lang = isset($_POST['lang']) ? sanitize_key($_POST['lang']) : '';

    $args = array(
        'post_type' => 'post',
        'posts_per_page' => $per_page,
        'offset' => $offset,
        'post_status' => 'publish',
        'no_found_rows' => false,
    );

    if (!empty($lang)) {
        $args['lang'] = $lang;
    }

    $query = new WP_Query($args);

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
                'key' => 'field_cv_references_title',
                'label' => 'References Section Title',
                'name' => 'cv_references_title',
                'type' => 'text',
            ),
            array(
                'key' => 'field_cv_references',
                'label' => 'References',
                'name' => 'cv_references',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Add Reference',
                'sub_fields' => array(
                    array(
                        'key' => 'field_cv_reference_name',
                        'label' => 'Suosittelijan henkilönimi / Full Name',
                        'name' => 'name',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_cv_reference_title',
                        'label' => 'Titteli / Job Title',
                        'name' => 'title',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_cv_reference_company',
                        'label' => 'Yritys / Company',
                        'name' => 'company',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_cv_reference_email',
                        'label' => 'Sähköposti / Email',
                        'name' => 'email',
                        'type' => 'email',
                    ),
                    array(
                        'key' => 'field_cv_reference_phone',
                        'label' => 'Puhelin / Phone',
                        'name' => 'phone',
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
            array(
                array(
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'page-blog.php',
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

function cv_one_pager_customize_register($wp_customize) {
    $wp_customize->add_section('cv_one_pager_contact', array(
        'title' => __('CV Contact', 'cv-one-pager'),
        'priority' => 160,
    ));

    $wp_customize->add_setting('cv_contact_homepage_url', array(
        'default' => home_url('/'),
        'sanitize_callback' => 'esc_url_raw',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('cv_contact_homepage_url', array(
        'label' => __('Homepage URL (PDF Export)', 'cv-one-pager'),
        'section' => 'cv_one_pager_contact',
        'type' => 'url',
    ));
}
add_action('customize_register', 'cv_one_pager_customize_register');

/**
 * CV Experience Date-Based Smart Sorting & Auto-Ordering
 * Orders work experiences from newest to oldest (e.g. 2026 first, 1996 last).
 */

function cv_one_pager_split_date_range($str) {
    if (empty($str) || !is_string($str)) {
        return array('', null);
    }

    $str = str_replace(array("–", "—", "−"), "-", $str);
    $str = trim($str);

    // 1. Separator with spaces around hyphen or word "to" or "..."
    if (preg_match('/\s+(-|\bto\b|\.{2,})\s*/ui', $str)) {
        $parts = preg_split('/\s+(-|\bto\b|\.{2,})\s*/ui', $str, 2);
        return array(trim($parts[0]), isset($parts[1]) ? trim($parts[1]) : '');
    }

    // 2. Format "YYYY-YYYY" without spaces
    if (preg_match('/^(\d{4})-(\d{4})$/', $str, $m)) {
        return array($m[1], $m[2]);
    }

    // 3. Format "MM.YYYY-MM.YYYY" or "MM/YYYY-MM/YYYY" without spaces
    if (preg_match('/^(\d{1,2}[\.\/]\d{4})-(\d{1,2}[\.\/]\d{4})$/', $str, $m)) {
        return array($m[1], $m[2]);
    }

    // 4. Trailing dash e.g. "2024-" or "2024 -"
    if (preg_match('/^(.*?)-$/', $str, $m)) {
        return array(trim($m[1]), '');
    }

    // 5. Fallback split on single hyphen
    if (preg_match('/^(.*?)-(.*?)$/', $str, $m)) {
        return array(trim($m[1]), trim($m[2]));
    }

    return array($str, null);
}

function cv_one_pager_parse_cv_date_part($part, $is_end = false) {
    $part = trim($part);
    if ($part === '') {
        return null;
    }

    $lower = mb_strtolower($part, 'UTF-8');

    // Check ongoing indicators in Finnish, English, Swedish
    $ongoing_patterns = array(
        'nykyhetki', 'nykypäivä', 'nykyisin', 'nykyinen', 'nyt', 'jatkuu', 'edelleen',
        'present', 'current', 'currently', 'ongoing', 'now', 'today',
        'tillsvidare', 'pågående', 'nuvarande', 'nu'
    );
    foreach ($ongoing_patterns as $word) {
        if (strpos($lower, $word) !== false) {
            return array(
                'is_ongoing' => true,
                'year' => 9999,
                'month' => 12,
                'raw' => $part
            );
        }
    }

    // Month mappings (Fi / En / Sv)
    $months = array(
        'tammi' => 1, 'helmi' => 2, 'maalis' => 3, 'huhti' => 4,
        'touko' => 5, 'kesä' => 6, 'heinä' => 7, 'elo' => 8,
        'syys' => 9, 'loka' => 10, 'marras' => 11, 'joulu' => 12,
        'kevät' => 4, 'syksy' => 10, 'talvi' => 12,

        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
        'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        'spring' => 4, 'summer' => 7, 'autumn' => 10, 'fall' => 10, 'winter' => 12,

        'maj' => 5, 'okt' => 10, 'vår' => 4, 'sommar' => 7, 'höst' => 10, 'vinter' => 12
    );

    $found_month = null;
    foreach ($months as $key => $m) {
        if (preg_match('/\b' . preg_quote($key, '/') . '[a-zåäö]*/ui', $lower)) {
            $found_month = $m;
            break;
        }
    }

    // 4-digit year (19xx or 20xx)
    $year = null;
    if (preg_match('/\b(19\d\d|20\d\d)\b/', $part, $ym)) {
        $year = (int)$ym[1];
    }

    // Try finding numeric month e.g. 01/2026, 1.2026, 2026-01
    if ($found_month === null && $year !== null) {
        if (preg_match('/(\b\d{1,2})[\.\/\-]' . $year . '\b/', $part, $mm)) {
            $m_val = (int)$mm[1];
            if ($m_val >= 1 && $m_val <= 12) {
                $found_month = $m_val;
            }
        } elseif (preg_match('/\b' . $year . '[\.\/\-](\d{1,2})\b/', $part, $mm)) {
            $m_val = (int)$mm[1];
            if ($m_val >= 1 && $m_val <= 12) {
                $found_month = $m_val;
            }
        }
    }

    if ($year !== null) {
        if ($found_month === null) {
            $found_month = $is_end ? 12 : 1;
        }
        return array(
            'is_ongoing' => false,
            'year' => $year,
            'month' => $found_month,
            'raw' => $part
        );
    }

    return null;
}

function cv_one_pager_parse_cv_dates($dates_str) {
    if (empty($dates_str) || !is_string($dates_str)) {
        return array(
            'start' => null,
            'end' => null,
            'is_ongoing' => 0,
            'has_valid_date' => 0,
            'end_year' => 0,
            'end_month' => 0,
            'start_year' => 0,
            'start_month' => 0
        );
    }

    $parts = cv_one_pager_split_date_range($dates_str);

    if ($parts[1] !== null) {
        $start_info = cv_one_pager_parse_cv_date_part($parts[0], false);
        $end_info = cv_one_pager_parse_cv_date_part($parts[1], true);

        // Trailing dash e.g. "2024 -" indicates ongoing
        if ($end_info === null && trim($parts[1]) === '' && $start_info !== null) {
            $end_info = array(
                'is_ongoing' => true,
                'year' => 9999,
                'month' => 12,
                'raw' => ''
            );
        }
    } else {
        $single_info = cv_one_pager_parse_cv_date_part($parts[0], true);
        $start_info = $single_info;
        $end_info = $single_info;
    }

    $is_ongoing = ($end_info && !empty($end_info['is_ongoing'])) ? 1 : 0;
    $has_valid_date = ($end_info !== null || $start_info !== null) ? 1 : 0;

    $end_year = $end_info ? $end_info['year'] : ($start_info ? $start_info['year'] : 0);
    $end_month = $end_info ? $end_info['month'] : ($start_info ? $start_info['month'] : 0);
    $start_year = $start_info ? $start_info['year'] : ($end_info ? $end_info['year'] : 0);
    $start_month = $start_info ? $start_info['month'] : ($end_info ? $end_info['month'] : 0);

    return array(
        'start' => $start_info,
        'end' => $end_info,
        'is_ongoing' => $is_ongoing,
        'has_valid_date' => $has_valid_date,
        'end_year' => $end_year,
        'end_month' => $end_month,
        'start_year' => $start_year,
        'start_month' => $start_month
    );
}

function cv_one_pager_compare_cv_dates($dates_a, $dates_b) {
    $a = cv_one_pager_parse_cv_dates($dates_a);
    $b = cv_one_pager_parse_cv_dates($dates_b);

    // 1. Items with valid dates come before items without dates
    if ($a['has_valid_date'] !== $b['has_valid_date']) {
        return ($a['has_valid_date'] > $b['has_valid_date']) ? -1 : 1;
    }

    // 2. Ongoing positions come first
    if ($a['is_ongoing'] !== $b['is_ongoing']) {
        return ($a['is_ongoing'] > $b['is_ongoing']) ? -1 : 1;
    }

    // If both are ongoing, compare start year & month DESC
    if ($a['is_ongoing'] && $b['is_ongoing']) {
        if ($a['start_year'] !== $b['start_year']) {
            return ($a['start_year'] > $b['start_year']) ? -1 : 1;
        }
        if ($a['start_month'] !== $b['start_month']) {
            return ($a['start_month'] > $b['start_month']) ? -1 : 1;
        }
        return 0;
    }

    // 3. Compare end_year DESC (e.g. 2026 before 1996)
    if ($a['end_year'] !== $b['end_year']) {
        return ($a['end_year'] > $b['end_year']) ? -1 : 1;
    }

    // 4. Compare end_month DESC
    if ($a['end_month'] !== $b['end_month']) {
        return ($a['end_month'] > $b['end_month']) ? -1 : 1;
    }

    // 5. Compare start_year DESC (if completed same year, job started later is newer)
    if ($a['start_year'] !== $b['start_year']) {
        return ($a['start_year'] > $b['start_year']) ? -1 : 1;
    }

    // 6. Compare start_month DESC
    if ($a['start_month'] !== $b['start_month']) {
        return ($a['start_month'] > $b['start_month']) ? -1 : 1;
    }

    return 0;
}

function cv_one_pager_sort_experience_items($items) {
    if (empty($items) || !is_array($items)) {
        return $items;
    }

    usort($items, function($a, $b) {
        $dates_a = '';
        $dates_b = '';
        $order_a = 0;
        $order_b = 0;
        $id_a = 0;
        $id_b = 0;

        if (is_object($a)) {
            $id_a = isset($a->ID) ? (int)$a->ID : 0;
            $order_a = isset($a->menu_order) ? (int)$a->menu_order : 0;
            if (function_exists('get_post_meta') && $id_a > 0) {
                $dates_a = get_post_meta($id_a, '_cv_experience_dates', true);
                if (empty($dates_a)) {
                    $dates_a = get_post_meta($id_a, 'cv_experience_dates', true);
                }
            } elseif (isset($a->dates)) {
                $dates_a = $a->dates;
            }
        } elseif (is_array($a)) {
            $id_a = isset($a['ID']) ? (int)$a['ID'] : (isset($a['id']) ? (int)$a['id'] : 0);
            $order_a = isset($a['menu_order']) ? (int)$a['menu_order'] : 0;
            $dates_a = $a['dates'] ?? ($a['_cv_experience_dates'] ?? ($a['cv_experience_dates'] ?? ''));
        }

        if (is_object($b)) {
            $id_b = isset($b->ID) ? (int)$b->ID : 0;
            $order_b = isset($b->menu_order) ? (int)$b->menu_order : 0;
            if (function_exists('get_post_meta') && $id_b > 0) {
                $dates_b = get_post_meta($id_b, '_cv_experience_dates', true);
                if (empty($dates_b)) {
                    $dates_b = get_post_meta($id_b, 'cv_experience_dates', true);
                }
            } elseif (isset($b->dates)) {
                $dates_b = $b->dates;
            }
        } elseif (is_array($b)) {
            $id_b = isset($b['ID']) ? (int)$b['ID'] : (isset($b['id']) ? (int)$b['id'] : 0);
            $order_b = isset($b['menu_order']) ? (int)$b['menu_order'] : 0;
            $dates_b = $b['dates'] ?? ($b['_cv_experience_dates'] ?? ($b['cv_experience_dates'] ?? ''));
        }

        $cmp = cv_one_pager_compare_cv_dates($dates_a, $dates_b);
        if ($cmp !== 0) {
            return $cmp;
        }

        // Tie-breaker: menu_order DESC
        if ($order_a !== $order_b) {
            return ($order_a > $order_b) ? -1 : 1;
        }

        // Tie-breaker: ID DESC
        if ($id_a !== $id_b) {
            return ($id_a > $id_b) ? -1 : 1;
        }

        return 0;
    });

    return $items;
}

/**
 * Re-indexes menu_order for all cv_experience posts so that newest is highest.
 */
function cv_one_pager_sync_all_experience_menu_orders() {
    $languages = array('');
    if (function_exists('pll_languages_list')) {
        $pll_langs = pll_languages_list();
        if (!empty($pll_langs) && is_array($pll_langs)) {
            $languages = $pll_langs;
        }
    }

    global $wpdb;

    foreach ($languages as $lang) {
        $args = array(
            'post_type' => 'cv_experience',
            'numberposts' => -1,
            'post_status' => 'any',
            'suppress_filters' => false,
        );
        if (!empty($lang)) {
            $args['lang'] = $lang;
        }
        $posts = get_posts($args);
        if (empty($posts)) {
            continue;
        }

        $sorted = cv_one_pager_sort_experience_items($posts);
        $total = count($sorted);
        $order = $total * 10;

        foreach ($sorted as $p) {
            $current_order = (int)$p->menu_order;
            if ($current_order !== $order) {
                if (!empty($wpdb)) {
                    $wpdb->update(
                        $wpdb->posts,
                        array('menu_order' => $order),
                        array('ID' => $p->ID),
                        array('%d'),
                        array('%d')
                    );
                } else {
                    wp_update_post(array(
                        'ID' => $p->ID,
                        'menu_order' => $order
                    ));
                }
                clean_post_cache($p->ID);
            }
            $order -= 10;
        }
    }
}

/**
 * Automatically sync experience orders once in admin.
 */
function cv_one_pager_auto_sync_experience_orders_once() {
    if (!is_admin()) {
        return;
    }
    if (!get_option('cv_experience_orders_synced_v3')) {
        cv_one_pager_sync_all_experience_menu_orders();
        update_option('cv_experience_orders_synced_v3', 1);
    }
}
add_action('admin_init', 'cv_one_pager_auto_sync_experience_orders_once');

/**
 * Add Company & Dates columns to cv_experience post list in admin.
 */
function cv_one_pager_cv_experience_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key === 'title') {
            $new_columns['cv_company'] = __('Työnantaja / Company', 'cv-one-pager');
            $new_columns['cv_dates'] = __('Ajankohta / Dates', 'cv-one-pager');
        }
    }
    return $new_columns;
}
add_filter('manage_cv_experience_posts_columns', 'cv_one_pager_cv_experience_columns');

function cv_one_pager_cv_experience_column_content($column, $post_id) {
    if ($column === 'cv_company') {
        $company = get_post_meta($post_id, '_cv_experience_company', true);
        if (empty($company)) {
            $company = get_post_meta($post_id, 'cv_experience_company', true);
        }
        echo esc_html($company ?: '—');
    } elseif ($column === 'cv_dates') {
        $dates = get_post_meta($post_id, '_cv_experience_dates', true);
        if (empty($dates)) {
            $dates = get_post_meta($post_id, 'cv_experience_dates', true);
        }
        echo esc_html($dates ?: '—');
    }
}
add_action('manage_cv_experience_posts_custom_column', 'cv_one_pager_cv_experience_column_content', 10, 2);

/**
 * Ensure default admin order for cv_experience is menu_order DESC.
 */
function cv_one_pager_admin_experience_order($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'cv_experience') {
        if (!$query->get('orderby')) {
            $query->set('orderby', 'menu_order');
            $query->set('order', 'DESC');
        }
    }
}
add_action('pre_get_posts', 'cv_one_pager_admin_experience_order');

/**
 * Admin action and notice to manually re-sort experiences if desired.
 */
function cv_one_pager_experience_admin_notices() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && $screen->id === 'edit-cv_experience') {
        if (isset($_GET['cv_experience_resorted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Työkokemukset järjestetty onnistuneesti uusimmasta vanhimpaan päivämäärien mukaan (2026 → 1996)!', 'cv-one-pager') . '</p></div>';
        }
    }
}
add_action('admin_notices', 'cv_one_pager_experience_admin_notices');

function cv_one_pager_handle_resort_experience_action() {
    if (!is_admin() || !current_user_can('edit_posts')) {
        return;
    }
    if (isset($_GET['action']) && $_GET['action'] === 'cv_resort_experience' && check_admin_referer('cv_resort_experience_nonce')) {
        cv_one_pager_sync_all_experience_menu_orders();
        wp_safe_redirect(add_query_arg(array('post_type' => 'cv_experience', 'cv_experience_resorted' => '1'), admin_url('edit.php')));
        exit;
    }
}
add_action('admin_init', 'cv_one_pager_handle_resort_experience_action');

function cv_one_pager_add_resort_button() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && $screen->id === 'edit-cv_experience') {
        $resort_url = wp_nonce_url(admin_url('edit.php?post_type=cv_experience&action=cv_resort_experience'), 'cv_resort_experience_nonce');
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.page-title-action').after('<a href="<?php echo esc_url($resort_url); ?>" class="page-title-action" style="margin-left: 8px;">Järjestä päivämäärien mukaan (2026 &rarr; 1996)</a>');
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'cv_one_pager_add_resort_button');

