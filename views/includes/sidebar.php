<?php
// views/includes/sidebar.php
require_once 'core/UrlHelper.php';
<<<<<<< HEAD

// Debug: Check what the current URI contains
echo "<!-- Debug: REQUEST_URI = " . $_SERVER['REQUEST_URI'] . " -->";
echo "<!-- Debug: Base Path = " . UrlHelper::getBasePath() . " -->";
echo "<!-- Debug: isCurrentPage('manage-portal') = " . (isCurrentPage('manage-portal') ? 'true' : 'false') . " -->";

// Helper function to check if current page matches a route
function isCurrentPage($route) {
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    $basePath = UrlHelper::getBasePath();
    
    // Remove base path from current URI for comparison
    if ($basePath && strpos($currentUri, $basePath) === 0) {
        $currentUri = substr($currentUri, strlen($basePath));
    }
    
    // Clean up the URI
    $currentUri = trim($currentUri, '/');
    $route = trim($route, '/');
    
    // Check if current URI starts with the route
    return $currentUri === $route || strpos($currentUri, $route . '/') === 0;
}
=======
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
?>

<aside class="sidebar" id="sidebar">
    <ul class="list-group">
<<<<<<< HEAD
        <li class="list-group-item<?= isCurrentPage('dashboard') ? ' active' : '' ?>">
=======
        <li class="list-group-item">
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
            <a href="<?= UrlHelper::url('dashboard') ?>">
                <i class="fas fa-tachometer-alt"></i> <span><?= Localization::translate('dashboard'); ?></span>
            </a>
        </li>
<<<<<<< HEAD
        <li class="list-group-item<?= isCurrentPage('manage-portal') ? ' active' : '' ?>">
=======
        <li class="list-group-item">
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
            <a href="<?= UrlHelper::url('manage-portal') ?>">
                <i class="fas fa-cogs"></i> <span><?= Localization::translate('manage_portal'); ?></span>
            </a>
        </li>

        <?php if (isset($_SESSION['user']) && $_SESSION['user']['system_role'] === 'super_admin'): ?>
<<<<<<< HEAD
        <li class="list-group-item<?= isCurrentPage('clients') ? ' active' : '' ?>">
=======
        <li class="list-group-item">
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
            <a href="<?= UrlHelper::url('clients') ?>">
                <i class="fas fa-building"></i> <span>Client Management</span>
            </a>
        </li>
        <?php endif; ?>
<<<<<<< HEAD
        <li class="list-group-item<?= isCurrentPage('my-courses') ? ' active' : '' ?>">
=======
        <li class="list-group-item<?= strpos($_SERVER['REQUEST_URI'], 'my-courses') !== false ? ' active' : '' ?>">
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
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
