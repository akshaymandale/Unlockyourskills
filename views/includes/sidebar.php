<?php
// views/includes/sidebar.php
require_once 'core/UrlHelper.php';
?>

<aside class="sidebar" id="sidebar">
    <ul class="list-group">
        <li class="list-group-item">
            <a href="<?= UrlHelper::url('dashboard') ?>">
                <i class="fas fa-tachometer-alt"></i> <span><?= Localization::translate('dashboard'); ?></span>
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?= UrlHelper::url('manage-portal') ?>">
                <i class="fas fa-cogs"></i> <span><?= Localization::translate('manage_portal'); ?></span>
            </a>
        </li>

        <?php if (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'super_admin'): ?>
        <li class="list-group-item">
            <a href="<?= UrlHelper::url('clients') ?>">
                <i class="fas fa-building"></i> <span>Client Management</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="list-group-item<?= strpos($_SERVER['REQUEST_URI'], 'my-courses') !== false ? ' active' : '' ?>">
            <a href="<?= UrlHelper::url('my-courses') ?>">
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
