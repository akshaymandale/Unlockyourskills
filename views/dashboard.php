<?php
// ✅ Fix session issue: Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_log('[DASHBOARD VIEW] SESSION: ' . print_r($_SESSION, true));
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

include 'views/includes/header.php';
include 'views/includes/navbar.php';
include 'views/includes/sidebar.php';
?>

<div class="container add-user-container">
    <h2><?= Localization::translate('dashboard_welcome'); ?></h2>
    <p><?= Localization::translate('dashboard_description'); ?></p>
</div>

</body>
</html>
<?php include 'includes/footer.php'; ?>
