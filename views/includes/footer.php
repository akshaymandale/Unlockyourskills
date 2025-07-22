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

// Load UrlHelper for generating correct asset paths
require_once __DIR__ . '/../../core/UrlHelper.php';
?>

    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="site-info">
            &copy; 2025 <?= Localization::translate('company_name'); ?>. <?= Localization::translate('all_rights_reserved'); ?>
        </div><!-- .site-info -->
    </footer><!-- #colophon -->

</div><!-- #page -->


<script src="<?= UrlHelper::url('public/bootstrap/js/jquery.min.js') ?>"></script>
    <!-- Removed duplicate Bootstrap bundle - already loaded in header -->
    <!-- <script src="<?= UrlHelper::url('public/bootstrap/js/bootstrap.bundle.min.js') ?>"></script> -->

    <script src="<?= UrlHelper::url('public/js/script.js') ?>"></script>


</body>
</html>
