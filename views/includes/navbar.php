<?php
// views/includes/navbar.php
// ✅ Load Navbar (Before other controllers)
require_once 'controllers/NavbarController.php'; // Include NavbarController
$navbarController = new NavbarController();
$languages = $navbarController->showNavbar(); // This will include navbar.php
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
       <!-- Language Menu -->
        <div class="language-menu">
            <button class="language-btn" id="languageToggle"><i class="fas fa-globe"></i></button>
            <div class="dropdown-menu" id="languageDropdown">
                <!-- Search Box -->
                <input type="text" id="languageSearch" class="language-search" placeholder="Search language...">
                
                <!-- Language List (Scrollable) -->
                <div class="language-list">
                    <?php foreach ($languages as $lang): ?>
                        <a href="?lang=<?= htmlspecialchars($lang['language_code']); ?>" class="language-item">
                            <i class="fas fa-language"></i> <?= htmlspecialchars($lang['language_name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Profile Menu -->
        <div class="profile-menu">
            <button class="profile-btn" id="profileToggle"><i class="fas fa-user"></i></button>
            <div class="dropdown-menu" id="profileDropdown">
                <a class="dropdown-item" href="#"><i class="fas fa-user-circle"></i> Profile</a>
                <a class="dropdown-item" href="index.php?controller=LoginController&action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>
