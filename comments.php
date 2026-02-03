<?php
if (post_password_required()) {
    return;
}

$comment_count = get_comments_number();
?>
<section class="post-comments">
    <div class="container">
        <div class="card">
            <h2 class="comments-title">
                <?php
                if ($comment_count === 1) {
                    echo esc_html__('1 Comment');
                } else {
                    printf(
                        esc_html__('%s Comments'),
                        esc_html(number_format_i18n($comment_count))
                    );
                }
                ?>
            </h2>

            <?php if (have_comments()) : ?>
                <ol class="comment-list">
                    <?php
                    wp_list_comments(array(
                        'style' => 'ol',
                        'short_ping' => true,
                        'avatar_size' => 48,
                    ));
                    ?>
                </ol>

                <?php the_comments_navigation(); ?>
            <?php endif; ?>

            <?php if (!comments_open() && $comment_count) : ?>
                <p class="no-comments"><?php echo esc_html__('Comments are closed.'); ?></p>
            <?php endif; ?>

            <?php
            comment_form(array(
                'class_form' => 'comment-form',
                'class_submit' => 'button',
                'title_reply' => esc_html__('Leave a comment'),
                'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
                'title_reply_after' => '</h3>',
            ));
            ?>
        </div>
    </div>
</section>
