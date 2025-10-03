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
            
            // Get initial report data with pagination
            $filters = ['client_id' => $clientId];
            $page = 1;
            $perPage = 20;
            $result = $model->getUserProgressData($filters, $page, $perPage);
            $reportData = $result['data'];
            $pagination = $result['pagination'];
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
                'pagination' => $pagination,
                'summary' => $summary,
                'charts' => $charts
            ];
            
        } catch (Exception $e) {
            $data = [
                'filterOptions' => ['users' => [], 'departments' => [], 'courses' => []],
                'customFields' => [],
                'reportData' => [],
                'pagination' => ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'total_pages' => 0, 'has_next' => false, 'has_prev' => false],
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
                    
                    // Get pagination parameters
                    $page = (int)($_POST['page'] ?? 1);
                    $perPage = (int)($_POST['per_page'] ?? 20);

                    if (!empty($filters['user_ids'])) {
                        }
                    
                    $result = $model->getUserProgressData($filters, $page, $perPage);
                    $data = $result['data'];
                    $pagination = $result['pagination'];
                    $summary = $model->getSummaryStats($filters);

                    // Get all filtered data for chart calculation (no pagination)
                    $allDataResult = $model->getUserProgressData($filters, 1, PHP_INT_MAX);
                    $allData = $allDataResult['data'];
                    
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
                    
                    // Process completion status from all filtered data
                    foreach ($allData as $row) {
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
                    
                    // Process department progress from all filtered data
                    $deptStats = [];
                    foreach ($allData as $row) {
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
                        'pagination' => $pagination,
                        'summary' => $summary,
                        'charts' => $charts
                    ]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        } catch (Error $e) {
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
        
        // Handle AJAX requests for data
        if (isset($_POST['action'])) {
            $this->handleCourseCompletionAjaxRequest();
            return;
        }
        
        // Load data directly for the page
        try {
            require_once 'config/Database.php';
            require_once 'models/CourseCompletionReportModel.php';
            
            $model = new CourseCompletionReportModel();
            $clientId = $currentUser['client_id'];
            
            // Get filter options
            $filterOptions = $model->getFilterOptions($clientId);

            // Get custom fields for dynamic filtering
            $customFields = $model->getCustomFieldsForFiltering($clientId);
            
            // Get initial report data with pagination
            $filters = ['client_id' => $clientId];
            $page = 1;
            $perPage = 20;
            $result = $model->getCourseCompletionData($filters, $page, $perPage);
            $reportData = $result['data'];
            $pagination = $result['pagination'];
            $summary = $model->getSummaryStats($filters);
            
            // Prepare chart data
            $charts = [
                'completion_rate' => [
                    'labels' => [],
                    'data' => []
                ],
                'enrollment_status' => [
                    'completed' => 0,
                    'in_progress' => 0,
                    'not_started' => 0
                ]
            ];
            
            // Get all data for chart calculation (not just paginated data)
            $allDataResult = $model->getCourseCompletionData($filters, 1, PHP_INT_MAX);
            $allData = $allDataResult['data'];
            
            // Sort courses by completion rate (highest first) for chart
            usort($allData, function($a, $b) {
                return $b['completion_rate'] <=> $a['completion_rate'];
            });
            
            // Process course completion rates for bar chart (top 10 by completion rate)
            $chartCount = 0;
            foreach ($allData as $row) {
                if ($chartCount < 10) { // Top 10 courses by completion rate
                    $charts['completion_rate']['labels'][] = $row['course_name'];
                    $charts['completion_rate']['data'][] = $row['completion_rate'];
                    $chartCount++;
                }
                
                // Aggregate enrollment status from all data
                $charts['enrollment_status']['completed'] += $row['completed_count'];
                $charts['enrollment_status']['in_progress'] += $row['in_progress_count'];
                $charts['enrollment_status']['not_started'] += $row['not_started_count'];
            }
            
            // Pass data to view
            $data = [
                'filterOptions' => $filterOptions,
                'customFields' => $customFields,
                'reportData' => $reportData,
                'pagination' => $pagination,
                'summary' => $summary,
                'charts' => $charts
            ];

            } catch (Exception $e) {
            $data = [
                'filterOptions' => ['users' => [], 'courses' => []],
                'customFields' => [],
                'reportData' => [],
                'pagination' => ['total' => 0, 'per_page' => 20, 'current_page' => 1, 'total_pages' => 0, 'has_next' => false, 'has_prev' => false],
                'summary' => ['total_courses' => 0, 'total_enrollments' => 0, 'avg_completion_percentage' => 0, 'courses_with_completions' => 0, 'overall_completion_rate' => 0],
                'charts' => ['completion_rate' => ['labels' => [], 'data' => []], 'enrollment_status' => ['completed' => 0, 'in_progress' => 0, 'not_started' => 0]]
            ];
        }
        
        // Load the view
        require_once 'views/reports/course_completion_report.php';
    }
    
    private function handleCourseCompletionAjaxRequest() {
        header('Content-Type: application/json');
        
        $currentUser = $_SESSION['user'];
        $clientId = $currentUser['client_id'];
        
        try {
            require_once 'config/Database.php';
            require_once 'models/CourseCompletionReportModel.php';
            
            $model = new CourseCompletionReportModel();
            $action = $_POST['action'];
            
            switch ($action) {
                case 'get_custom_field_values':
                    $fieldId = $_POST['field_id'] ?? null;
                    if (!$fieldId) {
                        echo json_encode(['success' => false, 'message' => 'Field ID is required']);
                        exit;
                    }
                    
                    $values = $model->getCustomFieldValues($fieldId, $clientId);
                    echo json_encode(['success' => true, 'values' => $values]);
                    break;
                    
                case 'get_report_data':
                    $filters = [
                        'client_id' => $clientId,
                        'start_date' => $_POST['start_date'] ?? null,
                        'end_date' => $_POST['end_date'] ?? null,
                        'course_ids' => !empty($_POST['course_ids']) ? (is_array($_POST['course_ids']) ? $_POST['course_ids'] : explode(',', $_POST['course_ids'])) : null,
                        'status' => !empty($_POST['status']) ? (is_array($_POST['status']) ? $_POST['status'] : explode(',', $_POST['status'])) : null,
                        'custom_field_id' => $_POST['custom_field_id'] ?? null,
                        'custom_field_value' => !empty($_POST['custom_field_value']) ? (is_array($_POST['custom_field_value']) ? $_POST['custom_field_value'] : [$_POST['custom_field_value']]) : null
                    ];
                    
                    // Remove empty filters
                    $filters = array_filter($filters, function($value) {
                        return $value !== null && $value !== '';
                    });
                    
                    // Get pagination parameters
                    $page = (int)($_POST['page'] ?? 1);
                    $perPage = (int)($_POST['per_page'] ?? 20);
                    
                    $result = $model->getCourseCompletionData($filters, $page, $perPage);
                    $data = $result['data'];
                    $pagination = $result['pagination'];
                    $summary = $model->getSummaryStats($filters);
                    
                    // Get all filtered data for chart calculation (no pagination)
                    $allDataResult = $model->getCourseCompletionData($filters, 1, PHP_INT_MAX);
                    $allData = $allDataResult['data'];
                    
                    // Prepare chart data
                    $charts = [
                        'completion_rate' => [
                            'labels' => [],
                            'data' => []
                        ],
                        'enrollment_status' => [
                            'completed' => 0,
                            'in_progress' => 0,
                            'not_started' => 0
                        ]
                    ];
                    
                    // Sort courses by completion rate (highest first) for chart
                    usort($allData, function($a, $b) {
                        return $b['completion_rate'] <=> $a['completion_rate'];
                    });
                    
                    // Process course completion rates for bar chart (top 10 by completion rate)
                    $chartCount = 0;
                    foreach ($allData as $row) {
                        if ($chartCount < 10) { // Top 10 courses by completion rate
                            $charts['completion_rate']['labels'][] = $row['course_name'];
                            $charts['completion_rate']['data'][] = $row['completion_rate'];
                            $chartCount++;
                        }
                        
                        // Aggregate enrollment status from all data
                        $charts['enrollment_status']['completed'] += $row['completed_count'];
                        $charts['enrollment_status']['in_progress'] += $row['in_progress_count'];
                        $charts['enrollment_status']['not_started'] += $row['not_started_count'];
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $data,
                        'pagination' => $pagination,
                        'summary' => $summary,
                        'charts' => $charts
                    ]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Invalid action']);
                    break;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request']);
        }
        exit;
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
