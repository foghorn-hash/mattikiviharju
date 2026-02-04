<?php
get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        ?>
        <main class="post-detail">
            <section class="post-hero">
                <div class="container">
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
        </main>
        <?php
    endwhile;
endif;

get_footer();
