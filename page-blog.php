<?php
/**
 * Template Name: Blog
 */
get_header();

$hero_title = get_the_title();
$hero_title = function_exists('get_field') ? get_field('cv_blog_title') : $hero_title;
$hero_intro = function_exists('get_field') ? get_field('cv_blog_intro') : '';
$hero_intro = $hero_intro ?: (function_exists('cv_one_pager_t') ? cv_one_pager_t('Uusimmat päivitykset, oivallukset ja projektimuistiinpanot.') : 'Uusimmat päivitykset, oivallukset ja projektimuistiinpanot.');

$per_page = (int) get_option('posts_per_page', 6);
$query = new WP_Query(array(
    'post_type' => 'post',
    'posts_per_page' => $per_page,
    'post_status' => 'publish',
    'no_found_rows' => false,
));
?>

<main class="blog-page">
    <section class="blog-hero">
        <div class="container">
            <p class="blog-kicker"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Blogi') : 'Blogi'); ?></p>
            <h1><?php echo esc_html($hero_title); ?></h1>
            <p class="blog-intro"><?php echo esc_html($hero_intro); ?></p>
        </div>
    </section>

    <section class="blog-section">
        <div class="container">
            <div class="post-list js-post-list" data-offset="<?php echo esc_attr($query->post_count); ?>">
                <?php
                if ($query->have_posts()) :
                    foreach ($query->posts as $post) :
                        echo cv_one_pager_render_post_card($post);
                    endforeach;
                else :
                    ?>
                    <p class="post-empty"><?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Ei julkaisuja vielä.') : 'Ei julkaisuja vielä.'); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($query->found_posts > $query->post_count) : ?>
                <div class="load-more-wrap">
                    <button class="load-more-button js-load-more" type="button">
                        <?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Lataa lisää postauksia') : 'Lataa lisää postauksia'); ?>
                    </button>
                    <span class="load-spinner js-load-spinner" aria-hidden="true"></span>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>
