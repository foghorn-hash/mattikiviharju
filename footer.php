<?php
$front_page_id = function_exists('cv_one_pager_front_page_id') ? cv_one_pager_front_page_id() : 0;
$footer_text = function_exists('get_field') && $front_page_id ? get_field('cv_footer_text', $front_page_id) : '';
$footer_text = $footer_text ?: '© ' . date('Y') . ' ' . get_bloginfo('name') . '. All rights reserved.';
?>

<footer class="footer">
    <div class="container">
        <p><?php echo esc_html($footer_text); ?></p>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
