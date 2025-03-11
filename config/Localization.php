<?php
class Localization {
    private static $langData = [];
    private static $currentLang = 'en';

    public static function loadLanguage($lang = 'en') {
        self::$currentLang = $lang;
        $filePath = __DIR__ . "/../locales/{$lang}.json";

        if (file_exists($filePath)) {
            self::$langData = json_decode(file_get_contents($filePath), true);
        } else {
            die("Localization file not found: $filePath");
        }
    }

    public static function translate($key, $replacements = []) {
        $text = self::$langData[$key] ?? $key;
        foreach ($replacements as $placeholder => $value) {
            $text = str_replace("{" . $placeholder . "}", $value, $text);
        }
        return $text;
    }

    public static function getCurrentLanguage() {
        return self::$currentLang;
    }
}
?>
