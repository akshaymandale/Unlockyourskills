<?php
// views/includes/navbar.php
?>

<nav class="navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">☰</button>
        <a class="navbar-brand" href="#">
            <img src="public/images/logo.png" alt="Client Logo">
        </a>
    </div>
    <div class="navbar-center">
        <form class="d-flex">
            <input class="search-input" type="search" placeholder="Search..." aria-label="Search">
            <button class="search-btn" type="submit">🔍</button>
        </form>
    </div>
    <div class="navbar-right">
        <div class="profile-menu">
            <button class="profile-btn" id="profileToggle">👤</button>
            <div class="dropdown-menu" id="profileDropdown">
                <a class="dropdown-item" href="#">Profile</a>
                <a class="dropdown-item" href="index.php?controller=LoginController&action=logout">Logout</a>
            </div>
        </div>
    </div>
</nav>