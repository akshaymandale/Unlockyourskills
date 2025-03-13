<?php
// ✅ Load Navbar Data
require_once 'controllers/NavbarController.php';
$navbarController = new NavbarController();
$navbarData = $navbarController->getNavbarData();

$languages = $navbarData['languages'];
$userLanguage = $navbarData['userLanguage'];

// ✅ Default values
$selectedLanguage = "EN";
$selectedIcon = "fas fa-globe";

// ✅ Set the selected language based on session
$langCode = $_SESSION['lang'] ?? 'en';

foreach ($languages as $lang) {
    if ($lang['language_code'] === $langCode) {
        $selectedLanguage = strtoupper($lang['language_code']);
        $selectedIcon = "fas fa-language"; // Customize icon per language if needed
        break;
    }
}
?>

<nav class="navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <a class="navbar-brand" href="#">
            <img src="public/images/logo.png" alt="Client Logo">
        </a>
    </div>
    
    <div class="navbar-center">
        <form class="d-flex">
            <input class="search-input" type="search" placeholder="<?= Localization::translate('search'); ?>" aria-label="Search">
            <button class="search-btn" type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <div class="navbar-right">
        <!-- ✅ Language Menu -->
        <div class="language-menu">
            <button class="language-btn" id="languageToggle">
                <i class="<?= $selectedIcon; ?>"></i>
                <span id="selectedLanguage"><?= htmlspecialchars($selectedLanguage); ?></span>
            </button>
            
            <div class="dropdown-menu" id="languageDropdown">
                <!-- ✅ Search Box -->
                <input type="text" id="languageSearch" class="language-search" placeholder="<?= Localization::translate('search_language'); ?>">
                
                <!-- ✅ Language List (Scrollable) -->
                <div class="language-list">
                    <?php foreach ($languages as $lang): ?>
                        <a href="#" class="language-item" data-lang="<?= htmlspecialchars($lang['language_code']); ?>">
                            <i class="fas fa-language"></i> <?= htmlspecialchars($lang['language_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ✅ Separator -->
        <div class="navbar-separator"></div>

        <!-- ✅ Profile Menu -->
        <div class="profile-menu">
            <button class="profile-btn" id="profileToggle"><i class="fas fa-user"></i></button>
            <div class="dropdown-menu" id="profileDropdown">
                <a class="dropdown-item" href="#"><i class="fas fa-user-circle"></i> <?= Localization::translate('profile'); ?></a>
                <a class="dropdown-item" href="index.php?controller=LoginController&action=logout"><i class="fas fa-sign-out-alt"></i> <?= Localization::translate('logout'); ?></a>
            </div>
        </div>
    </div>
</nav>
