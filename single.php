<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $date = get_the_date();
        ?>
        <main class="post-detail">
            <section class="post-hero">
                <div class="container">
                    <p class="post-meta"><?php echo esc_html($date); ?></p>
                    <h1><?php the_title(); ?></h1>
                </div>
            </section>
            <section class="post-content">
                <div class="container">
                    <article class="card">
                        <?php the_content(); ?>
                    </article>
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
