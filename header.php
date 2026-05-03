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
$front_page_id = function_exists('cv_one_pager_front_page_id') ? cv_one_pager_front_page_id() : 0;
$nav_about = function_exists('get_field') && $front_page_id ? get_field('cv_nav_about_label', $front_page_id) : '';
$nav_experience = function_exists('get_field') && $front_page_id ? get_field('cv_nav_experience_label', $front_page_id) : '';
$nav_education = function_exists('get_field') && $front_page_id ? get_field('cv_nav_education_label', $front_page_id) : '';
$nav_courses = function_exists('get_field') && $front_page_id ? get_field('cv_nav_courses_label', $front_page_id) : '';
$nav_projects = function_exists('get_field') && $front_page_id ? get_field('cv_nav_projects_label', $front_page_id) : '';
$nav_skills = function_exists('get_field') && $front_page_id ? get_field('cv_nav_skills_label', $front_page_id) : '';
$nav_contact = function_exists('get_field') && $front_page_id ? get_field('cv_nav_contact_label', $front_page_id) : '';

$nav_about = $nav_about ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('About') : 'About');
$nav_experience = $nav_experience ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Experience') : 'Experience');
$nav_education = $nav_education ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Education') : 'Education');
$nav_courses = $nav_courses ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Additional training') : 'Additional training');
$nav_projects = $nav_projects ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Projects') : 'Projects');
$nav_skills = $nav_skills ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Skills') : 'Skills');
$nav_contact = $nav_contact ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Contact') : 'Contact');

$home_url = home_url('/');
$anchor_base = is_front_page() ? '' : $home_url;
?>

<header class="site-header">
    <div class="container nav">
        <div class="logo-wrap">
            <?php if (function_exists('the_custom_logo') && has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a class="logo" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php bloginfo('name'); ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="nav-right">
            <?php if (function_exists('pll_the_languages')) : ?>
                <div class="nav-lang-row">
                    <div class="lang-switcher" aria-label="Language selector">
                        <?php
                        $languages = pll_the_languages(array(
                            'show_flags' => 0,
                            'show_names' => 1,
                            'hide_current' => 0,
                            'raw' => 1,
                        ));
                        if (!empty($languages) && is_array($languages)) :
                            $current_lang = '';
                            foreach ($languages as $lang) {
                                if (!empty($lang['current_lang'])) {
                                    $current_lang = $lang['slug'];
                                    break;
                                }
                            }
                            ?>
                            <select class="lang-dropdown" onchange="window.location.href = this.value;">
                                <?php foreach ($languages as $lang) :
                                    $is_current = !empty($lang['current_lang']);
                                    $name = !empty($lang['name']) ? $lang['name'] : $lang['slug'];
                                    ?>
                                    <option value="<?php echo esc_url($lang['url']); ?>" <?php selected($is_current); ?>>
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
                <a href="<?php echo esc_url($anchor_base . '#experience'); ?>"><?php echo esc_html($nav_experience); ?></a>
                <a href="<?php echo esc_url($anchor_base . '#education'); ?>"><?php echo esc_html($nav_education); ?></a>
                <a href="<?php echo esc_url($anchor_base . '#courses'); ?>"><?php echo esc_html($nav_courses); ?></a>
                <a href="<?php echo esc_url($anchor_base . '#projects'); ?>"><?php echo esc_html($nav_projects); ?></a>
                <a href="<?php echo esc_url($anchor_base . '#skills'); ?>"><?php echo esc_html($nav_skills); ?></a>
                <a href="<?php echo esc_url($anchor_base . '#contact'); ?>"><?php echo esc_html($nav_contact); ?></a>
                <a href="<?php echo esc_url(home_url('/blog/')); ?>"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Blogi') : 'Blogi'); ?></a>
            </nav>
        </div>
    </div>
</header>
