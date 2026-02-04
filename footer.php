<?php
$front_page_id = function_exists('cv_one_pager_front_page_id') ? cv_one_pager_front_page_id() : 0;
$footer_text = function_exists('get_field') && $front_page_id ? get_field('cv_footer_text', $front_page_id) : '';
$footer_text = $footer_text ?: '© ' . date('Y') . ' ' . get_bloginfo('name') . '. All rights reserved.';
?>

<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <p><?php echo esc_html($footer_text); ?></p>
            <a class="footer-link" href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">
                <?php echo esc_html(function_exists('cv_one_pager_t') ? cv_one_pager_t('Privacy Policy') : 'Privacy Policy'); ?>
            </a>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
