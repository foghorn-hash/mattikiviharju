<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $date = get_the_date();
        $current_post_lang = function_exists('pll_get_post_language') ? pll_get_post_language(get_the_ID()) : '';
        if (empty($current_post_lang) && function_exists('pll_current_language')) {
            $current_post_lang = pll_current_language('slug');
        }
        if (empty($current_post_lang)) {
            $current_post_lang = 'fi';
        }
        $blog_url = function_exists('cv_one_pager_get_blog_url') ? cv_one_pager_get_blog_url($current_post_lang) : home_url('/blogi/');
        $translations = function_exists('pll_get_post_translations') ? pll_get_post_translations(get_the_ID()) : array();
        $languages_list = function_exists('pll_languages_list') ? pll_languages_list(array('fields' => null)) : array();
        $categories = get_the_category();
        $tags = get_the_tags();
        $prev_post = get_previous_post();
        $next_post = get_next_post();
        ?>
        <main class="post-detail">
            <section class="post-hero">
                <div class="container">
                    <div class="post-hero-top">
                        <a href="<?php echo esc_url($blog_url); ?>" class="post-back-link">
                            <i class="bi bi-arrow-left"></i> <?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Takaisin blogiin', $current_post_lang) : 'Takaisin blogiin'); ?>
                        </a>

                        <?php if (count($translations) > 1 && !empty($languages_list)) : ?>
                            <div class="post-translations" aria-label="<?php echo esc_attr(function_exists('cv_one_pager_t') ? cv_one_pager_t('Käännökset:') : 'Käännökset:'); ?>">
                                <span class="post-translations-label"><i class="bi bi-translate"></i> <?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Käännökset:') : 'Käännökset:'); ?></span>
                                <div class="post-translations-links">
                                    <?php foreach ($languages_list as $lang_obj) :
                                        $lang_slug = $lang_obj->slug;
                                        if (!isset($translations[$lang_slug])) {
                                            continue;
                                        }
                                        $trans_post_id = (int) $translations[$lang_slug];
                                        $is_current = ($lang_slug === $current_post_lang);
                                        if ($is_current) : ?>
                                            <span class="post-translation-pill is-current"><?php echo esc_html($lang_obj->name); ?></span>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url(get_permalink($trans_post_id)); ?>" class="post-translation-pill" hreflang="<?php echo esc_attr($lang_slug); ?>">
                                                <?php echo esc_html($lang_obj->name); ?>
                                            </a>
                                        <?php endif;
                                    endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="post-meta-badges">
                        <span class="post-meta-badge"><i class="bi bi-calendar3"></i> <?php echo esc_html($date); ?></span>
                        <?php if (!empty($categories)) : ?>
                            <span class="post-meta-sep">&bull;</span>
                            <span class="post-meta-categories">
                                <?php foreach ($categories as $cat) : ?>
                                    <span class="post-cat-tag"><?php echo esc_html($cat->name); ?></span>
                                <?php endforeach; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h1 class="post-hero-title"><?php the_title(); ?></h1>
                </div>
            </section>

            <section class="post-content-wrap">
                <div class="container">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="post-featured-image">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                    <?php endif; ?>

                    <article class="card post-body-card">
                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>

                        <?php if (!empty($tags)) : ?>
                            <div class="post-tags-list">
                                <span class="post-tags-label"><i class="bi bi-tags"></i> <?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Avainsanat:') : 'Avainsanat:'); ?></span>
                                <?php foreach ($tags as $tag) : ?>
                                    <span class="post-tag-pill">#<?php echo esc_html($tag->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>

                    <?php if ($prev_post || $next_post) : ?>
                        <nav class="post-nav" aria-label="Post Navigation">
                            <?php if ($prev_post) : ?>
                                <a href="<?php echo esc_url(get_permalink($prev_post)); ?>" class="post-nav-item post-nav-prev">
                                    <span class="post-nav-sub"><i class="bi bi-arrow-left"></i> <?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Edellinen artikkeli') : 'Edellinen artikkeli'); ?></span>
                                    <span class="post-nav-title"><?php echo esc_html(get_the_title($prev_post)); ?></span>
                                </a>
                            <?php else : ?>
                                <span class="post-nav-empty"></span>
                            <?php endif; ?>

                            <?php if ($next_post) : ?>
                                <a href="<?php echo esc_url(get_permalink($next_post)); ?>" class="post-nav-item post-nav-next">
                                    <span class="post-nav-sub"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Seuraava artikkeli') : 'Seuraava artikkeli'); ?> <i class="bi bi-arrow-right"></i></span>
                                    <span class="post-nav-title"><?php echo esc_html(get_the_title($next_post)); ?></span>
                                </a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </section>

            <?php
            if (comments_open() || get_comments_number()) {
                comments_template();
            }
            ?>
        </main>
        <?php
    endwhile;
endif;

get_footer();
