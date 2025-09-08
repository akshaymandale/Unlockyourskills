<?php
// controllers/ReportsController.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'core/UrlHelper.php';
require_once 'config/Localization.php';

class ReportsController {
    
    public function userProgressReport() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        
        // Simple permission check - if user is logged in and has client_id, allow access
        $currentUser = $_SESSION['user'];
        if (!isset($currentUser['client_id'])) {
            UrlHelper::redirect('login');
        }
        
        // Handle AJAX requests for data
        if (isset($_POST['action'])) {
            $this->handleAjaxRequest();
            return;
        }
        
        // Load data directly for the page
        try {
            require_once 'config/Database.php';
            require_once 'models/UserProgressReportModel.php';
            
            $model = new UserProgressReportModel();
            $clientId = $currentUser['client_id'];
            
            // Get filter options
            $filterOptions = $model->getFilterOptions($clientId);
            
            // Get custom fields for dynamic filtering
            $customFields = $model->getCustomFieldsForFiltering($clientId);
            
            // Get initial report data
            $filters = ['client_id' => $clientId];
            $reportData = $model->getUserProgressData($filters);
            $summary = $model->getSummaryStats($filters);
            
            // Prepare chart data
            $charts = [
                'completion_status' => [
                    'completed' => 0,
                    'in_progress' => 0,
                    'not_started' => 0
                ],
                'department_progress' => [
                    'labels' => [],
                    'data' => []
                ]
            ];
            
            // Process completion status
            foreach ($reportData as $row) {
                switch ($row['progress_status']) {
                    case 'completed':
                        $charts['completion_status']['completed']++;
                        break;
                    case 'in_progress':
                        $charts['completion_status']['in_progress']++;
                        break;
                    case 'not_started':
                        $charts['completion_status']['not_started']++;
                        break;
                }
            }
            
            // Process department progress
            $deptStats = [];
            foreach ($reportData as $row) {
                $dept = $row['department'] ?: 'No Department';
                if (!isset($deptStats[$dept])) {
                    $deptStats[$dept] = ['total' => 0, 'sum' => 0];
                }
                $deptStats[$dept]['total']++;
                $deptStats[$dept]['sum'] += $row['completion_percentage'];
            }
            
            foreach ($deptStats as $dept => $stats) {
                $charts['department_progress']['labels'][] = $dept;
                $charts['department_progress']['data'][] = round($stats['sum'] / $stats['total'], 1);
            }
            
            // Pass data to view
            $data = [
                'filterOptions' => $filterOptions,
                'customFields' => $customFields,
                'reportData' => $reportData,
                'summary' => $summary,
                'charts' => $charts
            ];
            
        } catch (Exception $e) {
            error_log('Reports Page Error: ' . $e->getMessage());
            $data = [
                'filterOptions' => ['users' => [], 'departments' => [], 'courses' => []],
                'customFields' => [],
                'reportData' => [],
                'summary' => ['total_progress_records' => 0, 'unique_users' => 0, 'unique_courses' => 0, 'avg_completion' => 0, 'completed_courses' => 0, 'in_progress_courses' => 0, 'not_started_courses' => 0],
                'charts' => ['completion_status' => ['completed' => 0, 'in_progress' => 0, 'not_started' => 0], 'department_progress' => ['labels' => [], 'data' => []]]
            ];
        }
        
        // Load the view
        require_once 'views/reports/user_progress_report.php';
    }
    
    private function handleAjaxRequest() {
        header('Content-Type: application/json');
        
        try {
            $action = $_POST['action'] ?? '';
            $clientId = $_SESSION['user']['client_id'];
            
            require_once 'config/Database.php';
            require_once 'models/UserProgressReportModel.php';
            $model = new UserProgressReportModel();
            
            switch ($action) {
                case 'get_filter_options':
                    $options = $model->getFilterOptions($clientId);
                    echo json_encode([
                        'success' => true,
                        'users' => $options['users'],
                        'departments' => $options['departments'],
                        'courses' => $options['courses']
                    ]);
                    break;
                    
                case 'get_report_data':
                    $filters = [
                        'client_id' => $clientId,
                        'start_date' => $_POST['start_date'] ?? null,
                        'end_date' => $_POST['end_date'] ?? null,
                        'user_ids' => !empty($_POST['user_ids']) ? (is_array($_POST['user_ids']) ? $_POST['user_ids'] : explode(',', $_POST['user_ids'])) : null,
                        'departments' => !empty($_POST['departments']) ? (is_array($_POST['departments']) ? $_POST['departments'] : explode(',', $_POST['departments'])) : null,
                        'course_ids' => !empty($_POST['course_ids']) ? (is_array($_POST['course_ids']) ? $_POST['course_ids'] : explode(',', $_POST['course_ids'])) : null,
                        'status' => !empty($_POST['status']) ? (is_array($_POST['status']) ? $_POST['status'] : explode(',', $_POST['status'])) : null,
                        'custom_field_id' => $_POST['custom_field_id'] ?? null,
                        'custom_field_value' => !empty($_POST['custom_field_value']) ? (is_array($_POST['custom_field_value']) ? $_POST['custom_field_value'] : [$_POST['custom_field_value']]) : null
                    ];
                    
                    // Remove empty filters
                    $filters = array_filter($filters, function($value) {
                        return $value !== null && $value !== '';
                    });
                    
                    $data = $model->getUserProgressData($filters);
                    $summary = $model->getSummaryStats($filters);
                    
                    // Prepare chart data
                    $charts = [
                        'completion_status' => [
                            'completed' => 0,
                            'in_progress' => 0,
                            'not_started' => 0
                        ],
                        'department_progress' => [
                            'labels' => [],
                            'data' => []
                        ]
                    ];
                    
                    // Process completion status
                    foreach ($data as $row) {
                        switch ($row['progress_status']) {
                            case 'completed':
                                $charts['completion_status']['completed']++;
                                break;
                            case 'in_progress':
                                $charts['completion_status']['in_progress']++;
                                break;
                            case 'not_started':
                                $charts['completion_status']['not_started']++;
                                break;
                        }
                    }
                    
                    // Process department progress
                    $deptStats = [];
                    foreach ($data as $row) {
                        $dept = $row['department'] ?: 'No Department';
                        if (!isset($deptStats[$dept])) {
                            $deptStats[$dept] = ['total' => 0, 'sum' => 0];
                        }
                        $deptStats[$dept]['total']++;
                        $deptStats[$dept]['sum'] += $row['completion_percentage'];
                    }
                    
                    foreach ($deptStats as $dept => $stats) {
                        $charts['department_progress']['labels'][] = $dept;
                        $charts['department_progress']['data'][] = round($stats['sum'] / $stats['total'], 1);
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'reportData' => $data,
                        'summary' => $summary,
                        'charts' => $charts
                    ]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            
        } catch (Exception $e) {
            error_log('Reports AJAX Error: ' . $e->getMessage());
            error_log('Reports AJAX Stack Trace: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        } catch (Error $e) {
            error_log('Reports AJAX Fatal Error: ' . $e->getMessage());
            error_log('Reports AJAX Stack Trace: ' . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => 'Fatal Error: ' . $e->getMessage()]);
        }
    }
    
    public function userActivityReport() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        
        // Simple permission check - if user is logged in and has client_id, allow access
        $currentUser = $_SESSION['user'];
        if (!isset($currentUser['client_id'])) {
            UrlHelper::redirect('login');
        }
        
        // Load the view
        require_once 'views/reports/user_activity_report.php';
    }
    
    public function courseCompletionReport() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        
        // Simple permission check - if user is logged in and has client_id, allow access
        $currentUser = $_SESSION['user'];
        if (!isset($currentUser['client_id'])) {
            UrlHelper::redirect('login');
        }
        
        // Load the view
        require_once 'views/reports/course_completion_report.php';
    }
    
    public function assessmentResultsReport() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        
        // Simple permission check - if user is logged in and has client_id, allow access
        $currentUser = $_SESSION['user'];
        if (!isset($currentUser['client_id'])) {
            UrlHelper::redirect('login');
        }
        
        // Load the view
        require_once 'views/reports/assessment_results_report.php';
    }
    
    public function learningAnalyticsReport() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        
        // Simple permission check - if user is logged in and has client_id, allow access
        $currentUser = $_SESSION['user'];
        if (!isset($currentUser['client_id'])) {
            UrlHelper::redirect('login');
        }
        
        // Load the view
        require_once 'views/reports/learning_analytics_report.php';
    }
    
    public function engagementMetricsReport() {
        // Check if user is logged in
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }
        
        // Simple permission check - if user is logged in and has client_id, allow access
        $currentUser = $_SESSION['user'];
        if (!isset($currentUser['client_id'])) {
            UrlHelper::redirect('login');
        }
        
        // Load the view
        require_once 'views/reports/engagement_metrics_report.php';
    }
}
