<?php
// Prevent browser caching of protected pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load UrlHelper for generating correct asset paths
require_once __DIR__ . '/../../core/UrlHelper.php';

// Load Localization class for translations
require_once __DIR__ . '/../../config/Localization.php';

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unlock Your Skills</title>
    
    <!-- Session timeout meta tag -->
    <meta name="session-start" content="<?= (isset($_SESSION['last_activity']) ? $_SESSION['last_activity'] * 1000 : time() * 1000) ?>">

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
            // Initialize Localization with current language from session
            $currentLang = $_SESSION['lang'] ?? 'en';
            Localization::loadLanguage($currentLang);
            
            $translationsFile = __DIR__ . "/../../locales/{$currentLang}.json";
            if (file_exists($translationsFile)) {
                $translations = json_decode(file_get_contents($translationsFile), true);
                if ($translations) {
                    // Function to flatten nested arrays with dot notation
                    function flattenTranslations($array, $prefix = '') {
                        $result = [];
                        foreach ($array as $key => $value) {
                            $newKey = $prefix ? $prefix . '.' . $key : $key;
                            if (is_array($value)) {
                                $result = array_merge($result, flattenTranslations($value, $newKey));
                            } else {
                                $result[$newKey] = $value;
                            }
                        }
                        return $result;
                    }
                    
                    // Flatten the translations
                    $flattenedTranslations = flattenTranslations($translations);
                    
                    // Filter JavaScript translations including confirmations
                    $jsTranslations = array_filter($flattenedTranslations, function($key) {
                        return strpos($key, 'js.') === 0 ||
                               strpos($key, 'validation.') === 0 ||
                               strpos($key, 'assessment.validation.') === 0 ||
                               strpos($key, 'assignment.validation.') === 0 ||
                               strpos($key, 'scorm.modal.') === 0 ||
                               strpos($key, 'document.modal.') === 0 ||
                               strpos($key, 'document.category.') === 0 ||
                               strpos($key, 'error.') === 0 ||
                               strpos($key, 'confirmation.') === 0 ||
                               strpos($key, 'item.') === 0 ||
                               strpos($key, 'course_categories.') === 0 ||
                               strpos($key, 'course_subcategories.') === 0 ||
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
        <!-- Session Timeout Management -->
        <script src="<?= UrlHelper::url('public/js/session-timeout.js') ?>"></script>
</head>
<body>

<script>
window.currentUserId = <?= json_encode($_SESSION['id'] ?? null) ?>;
</script>

<!-- Global Edit User Modal (for profile editing from navbar) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">
                    <i class="fas fa-user-edit me-2"></i><?= Localization::translate('edit_user_title'); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editUserModalContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- GLOBAL SCRIPTS -->
<script src="<?= UrlHelper::url('public/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>

