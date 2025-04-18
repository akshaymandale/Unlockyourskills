<?php
// views/includes/sidebar.php
?>

<aside class="sidebar" id="sidebar">
    <ul class="list-group">
        <li class="list-group-item">
            <a href="index.php?controller=DashboardController">
                <i class="fas fa-tachometer-alt"></i> <span><?= Localization::translate('dashboard'); ?></span>
            </a>
        </li>
        <li class="list-group-item">
            <a href="index.php?controller=ManagePortalController&action=index">
                <i class="fas fa-cogs"></i> <span><?= Localization::translate('manage_portal'); ?></span>
            </a>
        </li>
        <li class="list-group-item">
            <a href="#">
                <i class="fas fa-book"></i> <span><?= Localization::translate('my_courses'); ?></span>
            </a>
        </li>
        <li class="list-group-item">
            <a href="#">
                <i class="fas fa-search"></i> <span><?= Localization::translate('search_courses'); ?></span>
            </a>
        </li>
    </ul>
</aside>


<!-- Ensure FontAwesome is included in header.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
