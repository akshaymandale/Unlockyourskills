<?php

require_once 'core/UrlHelper.php';

class DashboardController {

    public function index() {
        // Check if user is logged in (handled by AuthMiddleware in routes)
        // But add extra validation for direct access
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }

        // Load dashboard view
        require_once 'views/dashboard.php';
    }
}
