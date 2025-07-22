<?php

require_once 'core/UrlHelper.php';

class LanguageController {
    
    public function switch($language) {
        $_SESSION['lang'] = $language;
        setcookie('lang', $language, time() + (86400 * 30), "/");
        
        $referer = $_SERVER['HTTP_REFERER'] ?? UrlHelper::url('dashboard');
        header('Location: ' . $referer);
        exit();
    }
}
