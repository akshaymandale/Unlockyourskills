<?php
class Localization {
    private static $langData = [];
    private static $currentLang = 'en';

    public static function loadLanguage($lang = 'en') {
        // ✅ Sanitize language code and provide fallback
        $lang = trim($lang);
        if (empty($lang) || !preg_match('/^[a-z]{2}$/', $lang)) {
            $lang = 'en'; // Fallback to English
        }

        self::$currentLang = $lang;
        $filePath = __DIR__ . "/../locales/{$lang}.json";

        if (file_exists($filePath)) {
            self::$langData = json_decode(file_get_contents($filePath), true);
        } else {
            // ✅ Try fallback to English if requested language file doesn't exist
            if ($lang !== 'en') {
                $fallbackPath = __DIR__ . "/../locales/en.json";
                if (file_exists($fallbackPath)) {
                    self::$langData = json_decode(file_get_contents($fallbackPath), true);
                    self::$currentLang = 'en';
                } else {
                    die("Localization file not found: $filePath and fallback en.json also missing");
                }
            } else {
                die("Localization file not found: $filePath");
            }
        }
    }

    public static function translate($key, $replacements = []) {
        // Handle nested keys with dot notation (e.g., 'course_categories.title')
        $keys = explode('.', $key);
        $text = self::$langData;
        
        // Navigate through nested structure
        foreach ($keys as $k) {
            if (is_array($text) && isset($text[$k])) {
                $text = $text[$k];
            } else {
                // Key not found, return the original key
                $text = $key;
                break;
            }
        }
        
        // Apply replacements if text is a string
        if (is_string($text)) {
            foreach ($replacements as $placeholder => $value) {
                $text = str_replace("{" . $placeholder . "}", $value, $text);
            }
        } else {
            // If text is not a string (e.g., still an array), return the key
            $text = $key;
        }
        
        return $text;
    }

    public static function getCurrentLanguage() {
        return self::$currentLang;
    }
}
?>
