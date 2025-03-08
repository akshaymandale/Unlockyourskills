<?php
// views/includes/navbar.php
?>

<!-- Include Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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
                <a class="dropdown-item" href="?lang=en"><i class="fas fa-flag-usa"></i> English</a>
                <a class="dropdown-item" href="?lang=fr"><i class="fas fa-flag"></i> Français</a>
                <a class="dropdown-item" href="?lang=es"><i class="fas fa-flag"></i> Español</a>
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
