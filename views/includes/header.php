<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load UrlHelper for generating correct asset paths
require_once __DIR__ . '/../../core/UrlHelper.php';

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unlock Your Skills</title>


    <!-- ✅ FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


        <!-- ✅ Bootstrap CSS -->
        <link rel="stylesheet" href="<?= UrlHelper::url('public/bootstrap/css/bootstrap.min.css') ?>">

        <!-- ✅ Custom CSS -->
        <link rel="stylesheet" href="<?= UrlHelper::url('public/css/style.css') ?>">

        <!-- ✅ JavaScript Translations -->
        <script>
        <?php
        // Load translations for JavaScript - use current language
        try {
            $currentLang = Localization::getCurrentLanguage();
            $translationsFile = __DIR__ . "/../../locales/{$currentLang}.json";
            if (file_exists($translationsFile)) {
                $translations = json_decode(file_get_contents($translationsFile), true);
                if ($translations) {
                    // Filter JavaScript translations including confirmations
                    $jsTranslations = array_filter($translations, function($key) {
                        return strpos($key, 'js.') === 0 ||
                               strpos($key, 'validation.') === 0 ||
                               strpos($key, 'assessment.validation.') === 0 ||
                               strpos($key, 'scorm.modal.') === 0 ||
                               strpos($key, 'document.modal.') === 0 ||
                               strpos($key, 'document.category.') === 0 ||
                               strpos($key, 'error.') === 0 ||
                               strpos($key, 'confirmation.') === 0 ||
                               strpos($key, 'item.') === 0 ||
                               in_array($key, [
                                   'buttons_cancel', 'buttons_close', 'buttons_submit_feedback_question',
                                   'buttons_submit_survey_question', 'add_tag', 'upload_image_video_pdf'
                               ]);
                    }, ARRAY_FILTER_USE_KEY);
                    echo 'window.translations = ' . json_encode($jsTranslations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';';
                } else {
                    echo 'window.translations = {};';
                }
            } else {
                echo 'window.translations = {};';
            }
        } catch (Exception $e) {
            echo 'window.translations = {};';
        }
        ?>
        </script>
        <script src="<?= UrlHelper::url('public/js/translations.js') ?>"></script>
        <!-- Toast notifications enabled -->
        <script src="<?= UrlHelper::url('public/js/toast_notifications.js') ?>"></script>
        <!-- ✅ Centralized Confirmation System -->
        <script src="<?= UrlHelper::url('public/js/confirmation_modal.js') ?>"></script>
        <script src="<?= UrlHelper::url('public/js/confirmation_handlers.js') ?>"></script>
        <script src="<?= UrlHelper::url('public/js/confirmation_loader.js') ?>"></script>
</head>
<body>

