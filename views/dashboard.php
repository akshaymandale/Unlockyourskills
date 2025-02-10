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
    <h2>Welcome to the Dashboard</h2>
    <p>This is your e-learning admin panel.</p>
</div>

</body>
</html>
<?php include 'includes/footer.php'; ?>
