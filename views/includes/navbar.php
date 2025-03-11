<?php
// ✅ Load Navbar Data from Controller
require_once 'controllers/NavbarController.php';
// ✅ Load Navbar Data from Controller
$navbarController = new NavbarController();
$navbarData = $navbarController->getNavbarData(); // ✅ Correct method

$languages = $navbarData['languages'];
$userLanguage = $navbarData['userLanguage'];


// ✅ Default selected language
$selectedLanguage = "EN";
$selectedIcon = "fas fa-globe";

if ($userLanguage) {
   $selectedLanguage = strtoupper($userLanguage['language_code']);
    $selectedIcon = "fas fa-language"; // Custom icon for languages
}

// ✅ Check if user selected a language
if (isset($_GET['lang'])) {
    $selectedCode = htmlspecialchars($_GET['lang']);

    foreach ($languages as $lang) {
        if ($lang['language_code'] === $selectedCode) {
            $selectedLanguage = strtoupper($lang['language_code']);
            break;
        }
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
            <input class="search-input" type="search" placeholder="Search..." aria-label="Search">
            <button class="search-btn" type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    
    <div class="navbar-right">
        <!-- ✅ Language Menu -->
        <div class="language-menu">
            <button class="language-btn" id="languageToggle">
                <i class="<?= $selectedIcon; ?>"></i>
                <?= htmlspecialchars($selectedLanguage); ?>
            </button>
            
            <div class="dropdown-menu" id="languageDropdown">
                <!-- ✅ Search Box -->
                <input type="text" id="languageSearch" class="language-search" placeholder="Search language...">
                
                <!-- ✅ Language List (Scrollable) -->
                <div class="language-list">
                    <?php foreach ($languages as $lang): ?>
                        <a href="?lang=<?= htmlspecialchars($lang['language_code']); ?>" class="language-item">
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
