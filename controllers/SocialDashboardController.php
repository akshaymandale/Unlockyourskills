<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';
require_once 'models/SocialDashboardModel.php';

class SocialDashboardController {
    private $socialDashboardModel;
    
    public function __construct() {
        $this->socialDashboardModel = new SocialDashboardModel();
    }

    /**
     * Display Social Dashboard page
     */
    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }

        // Get user data
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        if (!$clientId) {
            UrlHelper::redirect('dashboard');
        }

        // Get social dashboard data
        $socialData = $this->socialDashboardModel->getSocialDashboardData($userId, $clientId);

        // Prepare data for the view
        $data = [
            'polls' => $socialData['polls'],
            'announcements' => $socialData['announcements'],
            'events' => $socialData['events'],
            'social_feed' => $socialData['social_feed'],
            'counts' => $socialData['counts']
        ];

        // Set page title and include view
        $pageTitle = 'Social Dashboard';
        require 'views/social_dashboard.php';
    }
}
