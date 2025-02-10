<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @package WordPress
 * @subpackage UnlockYourSkills
 * @since 1.0.0
 */
?>

    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="site-info">
            &copy; 2025 Deeplaxmi Communications. All Rights Reserved.
        </div><!-- .site-info -->
    </footer><!-- #colophon -->

</div><!-- #page -->

<!-- ✅ jQuery -->
<script src="<?php echo get_template_directory_uri(); ?>/public/js/jquery.min.js"></script>

<!-- ✅ Bootstrap JS -->
<script src="<?php echo get_template_directory_uri(); ?>/public/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- ✅ Custom Scripts -->
<script src="<?php echo get_template_directory_uri(); ?>/public/js/script.js"></script>

<?php wp_footer(); ?>

</body>
</html>
