<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class DashboardController {
    public function index() {
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            header('Location: index.php');
            exit();
        }
        include 'views/dashboard.php';
    }
}
?>
