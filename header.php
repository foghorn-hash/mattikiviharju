<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    <?php
    $current_lang = function_exists('pll_current_language') ? pll_current_language('slug') : '';
    if (empty($current_lang) && !empty($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
        if (preg_match('#/(en)(/|$|\?)#i', $uri)) {
            $current_lang = 'en';
        } elseif (preg_match('#/(sv)(/|$|\?)#i', $uri)) {
            $current_lang = 'sv';
        }
    }
    if (empty($current_lang)) {
        $current_lang = 'fi';
    }

    $front_page_id = function_exists('cv_one_pager_front_page_id') ? cv_one_pager_front_page_id() : 0;
    $nav_about = function_exists('get_field') && $front_page_id ? get_field('cv_nav_about_label', $front_page_id) : '';
    $nav_experience = function_exists('get_field') && $front_page_id ? get_field('cv_nav_experience_label', $front_page_id) : '';
    $nav_education = function_exists('get_field') && $front_page_id ? get_field('cv_nav_education_label', $front_page_id) : '';
    $nav_courses = function_exists('get_field') && $front_page_id ? get_field('cv_nav_courses_label', $front_page_id) : '';
    $nav_projects = function_exists('get_field') && $front_page_id ? get_field('cv_nav_projects_label', $front_page_id) : '';
    $nav_skills = function_exists('get_field') && $front_page_id ? get_field('cv_nav_skills_label', $front_page_id) : '';
    $nav_references = function_exists('get_field') && $front_page_id ? get_field('cv_nav_references_label', $front_page_id) : '';
    $nav_contact = function_exists('get_field') && $front_page_id ? get_field('cv_nav_contact_label', $front_page_id) : '';
    $nav_blog = function_exists('get_field') && $front_page_id ? get_field('cv_nav_blog_label', $front_page_id) : '';

    $nav_about = $nav_about ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('About', $current_lang) : 'About');
    $nav_experience = $nav_experience ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Experience', $current_lang) : 'Experience');
    $nav_education = $nav_education ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Education', $current_lang) : 'Education');
    $nav_courses = $nav_courses ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Additional training', $current_lang) : 'Additional training');
    $nav_projects = $nav_projects ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Projects', $current_lang) : 'Projects');
    $nav_skills = $nav_skills ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Skills', $current_lang) : 'Skills');
    $nav_references = $nav_references ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('References', $current_lang) : 'References');
    $nav_contact = $nav_contact ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Contact', $current_lang) : 'Contact');
    $nav_blog = $nav_blog ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Blogi', $current_lang) : 'Blogi');

    $home_url = function_exists('pll_home_url') ? pll_home_url($current_lang) : home_url('/');
    $anchor_base = is_front_page() ? '' : $home_url;

    $blog_urls = array(
        'fi' => function_exists('pll_home_url') ? trailingslashit(pll_home_url('fi')) . 'blogi/' : home_url('/blogi/'),
        'en' => function_exists('pll_home_url') ? trailingslashit(pll_home_url('en')) . 'blog/' : home_url('/en/blog/'),
        'sv' => function_exists('pll_home_url') ? trailingslashit(pll_home_url('sv')) . 'blogg/' : home_url('/sv/blogg/'),
    );
    $blog_url = isset($blog_urls[$current_lang]) ? $blog_urls[$current_lang] : home_url('/blogi/');

    $is_blog_page = (function_exists('is_home') && is_home()) || (function_exists('is_page_template') && is_page_template('page-blog.php'));
    if (!$is_blog_page && !empty($_SERVER['REQUEST_URI'])) {
        $is_blog_page = (bool) preg_match('#/(blogi|blog|blogg)(/|$|\?)#i', $_SERVER['REQUEST_URI']);
    }
    ?>

    <header class="site-header">
        <div class="container nav">
            <div class="logo-wrap">
                <?php if (function_exists('the_custom_logo') && has_custom_logo()): ?>
                    <?php the_custom_logo(); ?>
                <?php else: ?>
                    <a class="logo" href="<?php echo esc_url($home_url); ?>">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="nav-right">
                <?php if (function_exists('pll_the_languages')): ?>
                    <div class="nav-lang-row">
                        <div class="lang-switcher" aria-label="Language selector">
                            <?php
                            $languages = pll_the_languages(array(
                                'show_flags' => 0,
                                'show_names' => 1,
                                'hide_current' => 0,
                                'raw' => 1,
                            ));
                            if (!empty($languages) && is_array($languages)): ?>
                                <select class="lang-dropdown" onchange="window.location.href = this.value;">
                                    <?php foreach ($languages as $lang):
                                        $lang_slug = !empty($lang['slug']) ? $lang['slug'] : '';
                                        $is_current = !empty($lang['current_lang']) || ($lang_slug === $current_lang);
                                        $name = !empty($lang['name']) ? $lang['name'] : $lang_slug;
                                        $option_url = $lang['url'];
                                        if ($is_blog_page && !empty($lang_slug) && isset($blog_urls[$lang_slug])) {
                                            $option_url = $blog_urls[$lang_slug];
                                        }
                                        ?>
                                        <option value="<?php echo esc_url($option_url); ?>" <?php selected($is_current); ?>>
                                            <?php echo esc_html($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-bar" aria-hidden="true"></span>
                    <span class="nav-toggle-label">Menu</span>
                </button>
                <nav class="nav-links" id="primary-nav" aria-label="Primary">
                    <a href="<?php echo esc_url($anchor_base . '#about'); ?>"><?php echo esc_html($nav_about); ?></a>
                    <a
                        href="<?php echo esc_url($anchor_base . '#experience'); ?>"><?php echo esc_html($nav_experience); ?></a>
                    <a
                        href="<?php echo esc_url($anchor_base . '#education'); ?>"><?php echo esc_html($nav_education); ?></a>
                    <a
                        href="<?php echo esc_url($anchor_base . '#courses'); ?>"><?php echo esc_html($nav_courses); ?></a>
                    <a
                        href="<?php echo esc_url($anchor_base . '#projects'); ?>"><?php echo esc_html($nav_projects); ?></a>
                    <a href="<?php echo esc_url($anchor_base . '#skills'); ?>"><?php echo esc_html($nav_skills); ?></a>
                    <a
                        href="<?php echo esc_url($anchor_base . '#references'); ?>"><?php echo esc_html($nav_references); ?></a>
                    <a
                        href="<?php echo esc_url($anchor_base . '#contact'); ?>"><?php echo esc_html($nav_contact); ?></a>
                    <a href="<?php echo esc_url($blog_url); ?>"><?php echo esc_html($nav_blog); ?></a>
                </nav>
            </div>
        </div>
    </header>