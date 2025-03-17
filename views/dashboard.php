<?php
// ✅ Fix session issue: Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
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
