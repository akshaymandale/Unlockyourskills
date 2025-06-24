<?php
// ✅ Load Navbar Data
require_once 'controllers/NavbarController.php';
require_once 'core/UrlHelper.php';
$navbarController = new NavbarController();
$navbarData = $navbarController->getNavbarData();

$languages = $navbarData['languages'];
$userLanguage = $navbarData['userLanguage'];

// ✅ Get current user data from session
$currentUser = $_SESSION['user'] ?? null;
$userFullName = $currentUser['full_name'] ?? 'User';
$userEmail = $currentUser['email'] ?? '';
$userRole = $currentUser['user_role'] ?? '';
$systemRole = $currentUser['system_role'] ?? 'user';
$profilePicture = $currentUser['profile_picture'] ?? null;
$profileId = $currentUser['profile_id'] ?? '';

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
            <img src="public/images/UYSlogo.png" alt="<?= Localization::translate('client_logo'); ?>">
        </a>
    </div>
    
    <div class="navbar-center">
        <form class="d-flex">
            <input class="search-input" type="search" placeholder="<?= Localization::translate('search'); ?>" aria-label="<?= Localization::translate('search'); ?>">
            <button class="search-btn" type="submit" title="<?= Localization::translate('search'); ?>">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
    
    <div class="navbar-right">
        <!-- ✅ Language Menu -->
        <div class="language-menu">
            <button class="language-btn" id="languageToggle" title="<?= Localization::translate('change_language'); ?>">
                <i class="<?= $selectedIcon; ?>"></i>
                <span id="selectedLanguage"><?= htmlspecialchars($selectedLanguage); ?></span>
            </button>
            
            <div class="dropdown-menu" id="languageDropdown">
                <!-- ✅ Search Box -->
                <input type="text" id="languageSearch" class="language-search" placeholder="<?= Localization::translate('search_language'); ?>" aria-label="<?= Localization::translate('search_language'); ?>">
                
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
            <button class="profile-btn" id="profileToggle" title="<?= Localization::translate('profile'); ?>">
                <?php 
                $profilePictureUrl = null;
                $profilePictureExists = false;
                
                if ($profilePicture) {
                    // Handle different path formats
                    if (strpos($profilePicture, 'http') === 0) {
                        // External URL
                        $profilePictureUrl = $profilePicture;
                        $profilePictureExists = true;
                    } elseif (strpos($profilePicture, '/') === 0) {
                        // Absolute path
                        $profilePictureUrl = $profilePicture;
                        $profilePictureExists = file_exists($_SERVER['DOCUMENT_ROOT'] . $profilePicture);
                    } else {
                        // Relative path - assume it's in uploads directory
                        $profilePictureUrl = UrlHelper::url('uploads/' . ltrim($profilePicture, '/'));
                        $profilePictureExists = file_exists(__DIR__ . '/../../uploads/' . ltrim($profilePicture, '/'));
                    }
                }
                ?>
                <?php if ($profilePictureExists): ?>
                    <img src="<?= htmlspecialchars($profilePictureUrl); ?>" alt="Profile" class="profile-avatar">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
                <span class="profile-name"><?= htmlspecialchars($userFullName); ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu" id="profileDropdown">
                <!-- User Info Header -->
                <div class="profile-header">
                    <div class="profile-info">
                        <?php if ($profilePictureExists): ?>
                            <img src="<?= htmlspecialchars($profilePictureUrl); ?>" alt="Profile" class="profile-avatar-large">
                        <?php else: ?>
                            <div class="profile-avatar-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div class="profile-details">
                            <div class="profile-name-large"><?= htmlspecialchars($userFullName); ?></div>
                            <div class="profile-email"><?= htmlspecialchars($userEmail); ?></div>
                            <div class="profile-role"><?= htmlspecialchars($userRole); ?></div>
                            <div class="profile-id">ID: <?= htmlspecialchars($profileId); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown-divider"></div>
                
                <!-- Profile Actions -->
                <?php if ($systemRole !== 'super_admin'): ?>
                <a class="dropdown-item" href="<?= UrlHelper::url('users/' . $_SESSION['id'] . '/edit') ?>">
                    <i class="fas fa-user-edit"></i> <?= Localization::translate('edit_profile'); ?>
                </a>
                <?php endif; ?>
                <a class="dropdown-item" href="<?= UrlHelper::url('settings') ?>">
                    <i class="fas fa-cog"></i> <?= Localization::translate('settings'); ?>
                </a>
                
                <div class="dropdown-divider"></div>
                
                <!-- Logout -->
                <a class="dropdown-item" href="<?= UrlHelper::url('logout') ?>">
                    <i class="fas fa-sign-out-alt"></i> <?= Localization::translate('logout'); ?>
                </a>
            </div>
        </div>
    </div>
</nav>

