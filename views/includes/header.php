<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        <link rel="stylesheet" href="public/bootstrap/css/bootstrap.min.css">

        <!-- ✅ Custom CSS -->
        <link rel="stylesheet" href="public/css/style.css">

        <!-- ✅ JavaScript Translations -->
        <script>
        <?php
        // Load translations for JavaScript - use current language
        $currentLang = Localization::getCurrentLanguage();
        $translationsFile = "locales/{$currentLang}.json";
        if (file_exists($translationsFile)) {
            $translations = json_decode(file_get_contents($translationsFile), true);
            // Filter only JavaScript validation translations to reduce size
            $jsTranslations = array_filter($translations, function($key) {
                return strpos($key, 'js.') === 0 ||
                       strpos($key, 'validation.') === 0 ||
                       strpos($key, 'assessment.validation.') === 0 ||
                       strpos($key, 'scorm.modal.') === 0 ||
                       strpos($key, 'document.modal.') === 0 ||
                       strpos($key, 'document.category.') === 0 ||
                       strpos($key, 'error.') === 0 ||
                       in_array($key, [
                           'buttons_cancel', 'buttons_close', 'buttons_submit_feedback_question',
                           'buttons_submit_survey_question', 'add_tag', 'upload_image_video_pdf'
                       ]);
            }, ARRAY_FILTER_USE_KEY);
            echo 'window.translations = ' . json_encode($jsTranslations) . ';';
        } else {
            echo 'window.translations = {};';
        }
        ?>
        </script>
        <script src="public/js/translations.js"></script>
        <!-- Toast notifications enabled -->
        <script src="public/js/toast_notifications.js"></script>
        <!-- ✅ Centralized Confirmation System -->
        <script src="public/js/confirmation_modal.js"></script>
        <script src="public/js/confirmation_handlers.js"></script>
        <script src="public/js/confirmation_loader.js"></script>
</head>
<body>

