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
                    <article class="card post-body-card">
                        <div class="entry-content">
                            <?php the_content(); ?>
                        </div>
                    </article>
                </div>
            </section>
        </main>
        <?php
    endwhile;
endif;

get_footer();
