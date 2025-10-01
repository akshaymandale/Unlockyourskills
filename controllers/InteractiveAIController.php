<?php
require_once 'models/InteractiveAIContentModel.php';
require_once 'config/Localization.php';
require_once 'config/Database.php';

class InteractiveAIController
{
    private $model;

    public function __construct()
    {
        $this->model = new InteractiveAIContentModel();
    }

    /**
     * Enhanced Interactive AI content viewer with full parameter support
     */
    public function viewInteractiveContent()
    {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
            UrlHelper::redirect('login');
        }

        $interactivePackageId = $_GET['content_id'] ?? null; // This is actually the Interactive AI package ID
        $courseId = $_GET['course_id'] ?? null;
        $moduleId = $_GET['module_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$interactivePackageId) {
            $this->showError('Content ID is required');
            return;
        }

        // Get comprehensive content data using the Interactive AI package ID
        $content = $this->model->getInteractiveContent($interactivePackageId, $clientId);
        if (!$content) {
            $this->showError("Interactive AI content not found for Package ID: {$interactivePackageId}, Client ID: {$clientId}");
            return;
        }

        // Find the course module content ID for this Interactive AI package
        $moduleContentId = $this->getModuleContentId($interactivePackageId, $courseId, $clientId);
        if (!$moduleContentId) {
            error_log("Failed to find module content ID for Interactive AI package: {$interactivePackageId}, Course: {$courseId}");
            $this->showError("Unable to initialize progress tracking for this content");
            return;
        }

        // Initialize progress tracking when user launches Interactive AI content
        $progressInitialized = $this->model->initializeInteractiveProgress($userId, $courseId, $moduleContentId, $clientId, $interactivePackageId);
        if (!$progressInitialized) {
            error_log("Failed to initialize Interactive AI progress tracking for User: {$userId}, Module Content: {$moduleContentId}, Package: {$interactivePackageId}");
        }

        // Validate user requirements
        $validation = $this->model->validateUserRequirements($interactivePackageId, $userId, $clientId);
        
        // Get content metadata for display
        $metadata = $this->model->getContentMetadata($interactivePackageId, $clientId);
        
        // Get personalized launch configuration
        $launchConfig = $this->model->getPersonalizedLaunchConfig($interactivePackageId, $userId, $clientId);

        // Expose data to view
        $GLOBALS['content'] = $content;
        $GLOBALS['validation'] = $validation;
        $GLOBALS['metadata'] = $metadata;
        $GLOBALS['launch_config'] = $launchConfig;
        $GLOBALS['course_id'] = $courseId;
        $GLOBALS['module_id'] = $moduleId;
        $GLOBALS['content_id'] = $interactivePackageId; // Keep as Interactive AI package ID for the viewer
        $GLOBALS['user_id'] = $userId;
        $GLOBALS['client_id'] = $clientId;

        require 'views/interactive_ai_content_viewer.php';
    }

    /**
     * API endpoint for Interactive AI content metadata
     */
    public function getContentMetadata()
    {
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $contentId = $_GET['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$contentId) {
            $this->jsonResponse(['success' => false, 'message' => 'Content ID required']);
            return;
        }

        $metadata = $this->model->getContentMetadata($contentId, $clientId);
        
        if ($metadata) {
            $this->jsonResponse(['success' => true, 'data' => $metadata]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Content not found']);
        }
    }

    /**
     * API endpoint for Interactive AI content validation
     */
    public function validateContentRequirements()
    {
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $contentId = $_GET['content_id'] ?? null;
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$contentId) {
            $this->jsonResponse(['success' => false, 'message' => 'Content ID required']);
            return;
        }

        $validation = $this->model->validateUserRequirements($contentId, $userId, $clientId);
        $this->jsonResponse(['success' => true, 'data' => $validation]);
    }

    /**
     * API endpoint for Interactive AI content launch configuration
     */
    public function getLaunchConfiguration()
    {
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $contentId = $_GET['content_id'] ?? null;
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$contentId) {
            $this->jsonResponse(['success' => false, 'message' => 'Content ID required']);
            return;
        }

        $config = $this->model->getPersonalizedLaunchConfig($contentId, $userId, $clientId);
        
        if ($config) {
            $this->jsonResponse(['success' => true, 'data' => $config]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => 'Content not found']);
        }
    }

    /**
     * Enhanced progress tracking for Interactive AI content
     */
    public function updateInteractiveProgress()
    {
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $moduleId = $input['module_id'] ?? null;
        $progressData = $input['progress_data'] ?? [];
        $interactionData = $input['interaction_data'] ?? [];

        if (!$courseId || !$contentId) {
            $this->jsonResponse(['success' => false, 'message' => 'Course ID and Content ID required']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        // Get content configuration for personalized progress tracking
        $content = $this->model->getInteractiveContent($contentId, $clientId);
        if (!$content) {
            $this->jsonResponse(['success' => false, 'message' => 'Content not found']);
            return;
        }

        try {
            // Enhanced progress tracking with AI parameters
            $result = $this->updateEnhancedProgress($userId, $courseId, $contentId, $moduleId, $clientId, $progressData, $interactionData, $content);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Interactive AI progress updated successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            error_log("InteractiveAIController::updateInteractiveProgress error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Internal server error']);
        }
    }

    /**
     * Time limit enforcement for Interactive AI content
     */
    public function checkTimeLimit()
    {
        if (!isset($_SESSION['user']['id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $contentId = $_GET['content_id'] ?? null;
        $clientId = $_SESSION['user']['client_id'] ?? null;

        if (!$contentId) {
            $this->jsonResponse(['success' => false, 'message' => 'Content ID required']);
            return;
        }

        $content = $this->model->getInteractiveContent($contentId, $clientId);
        if (!$content || !$content['has_time_limit']) {
            $this->jsonResponse(['success' => true, 'data' => ['has_limit' => false]]);
            return;
        }

        // Check if time limit has been exceeded
        $startTime = $_SESSION['interactive_content_start_time'][$contentId] ?? time();
        $elapsedTime = time() - $startTime;
        $timeLimit = $content['time_limit'] * 60; // Convert to seconds
        $remainingTime = max(0, $timeLimit - $elapsedTime);

        $this->jsonResponse([
            'success' => true,
            'data' => [
                'has_limit' => true,
                'time_limit' => $content['time_limit'],
                'elapsed_time' => $elapsedTime,
                'remaining_time' => $remainingTime,
                'time_expired' => $remainingTime <= 0
            ]
        ]);
    }

    // Private helper methods

    private function updateEnhancedProgress($userId, $courseId, $contentId, $moduleId, $clientId, $progressData, $interactionData, $content)
    {
        // Prepare data for interactive_progress table
        $updateData = [];
        
        // Map progress data to database fields
        if (isset($progressData['completion_percentage'])) {
            $updateData['completion_percentage'] = $progressData['completion_percentage'];
        }
        
        if (isset($progressData['current_step'])) {
            $updateData['current_step'] = $progressData['current_step'];
        }
        
        if (isset($progressData['total_steps'])) {
            $updateData['total_steps'] = $progressData['total_steps'];
        }
        
        if (isset($progressData['is_completed'])) {
            $updateData['is_completed'] = $progressData['is_completed'];
        }
        
        if (isset($progressData['time_spent'])) {
            $updateData['time_spent'] = $progressData['time_spent'];
        }
        
        if (isset($progressData['status'])) {
            $updateData['status'] = $progressData['status'];
        }
        
        // Store interaction data as JSON
        if (!empty($interactionData)) {
            $updateData['interaction_data'] = json_encode($interactionData);
        }
        
        // Store user responses if provided
        if (isset($progressData['user_responses'])) {
            $updateData['user_responses'] = json_encode($progressData['user_responses']);
        }
        
        // Store AI feedback if provided
        if (isset($progressData['ai_feedback'])) {
            $updateData['ai_feedback'] = json_encode($progressData['ai_feedback']);
        }
        
        // Calculate time spent if not provided
        if (!isset($updateData['time_spent']) || empty($updateData['time_spent'])) {
            $currentProgress = $this->model->getInteractiveProgress($userId, $courseId, $contentId, $clientId);
            if ($currentProgress && $currentProgress['started_at']) {
                $startTime = new DateTime($currentProgress['started_at']);
                $currentTime = new DateTime();
                $timeSpent = $currentTime->getTimestamp() - $startTime->getTimestamp();
                $updateData['time_spent'] = round($timeSpent / 60); // Convert to minutes
            }
        }
        
        // Update progress in database
        $result = $this->model->updateInteractiveProgress($userId, $courseId, $contentId, $clientId, $updateData);
        
        // Enhanced tracking with AI parameters
        $enhancedData = [
            'basic_progress' => $progressData,
            'ai_interactions' => $interactionData,
            'ai_model' => $content['ai_model'],
            'adaptation_algorithm' => $content['adaptation_algorithm'],
            'tutor_personality' => $content['tutor_personality'],
            'interaction_type' => $content['interaction_type'],
            'difficulty_level' => $content['difficulty_level'],
            'knowledge_domain' => $content['knowledge_domain'],
            'response_style' => $content['response_style']
        ];

        return [
            'progress_updated' => $result,
            'ai_adaptation_applied' => !empty($content['adaptation_algorithm']),
            'tutor_personality_applied' => !empty($content['tutor_personality']),
            'interaction_tracked' => !empty($interactionData),
            'database_updated' => $result,
            'completion_status' => isset($updateData['is_completed']) ? $updateData['is_completed'] : 0
        ];
    }

    /**
     * Get the course module content ID for a given Interactive AI package ID
     */
    private function getModuleContentId($interactivePackageId, $courseId, $clientId)
    {
        try {
            $database = new Database();
            $conn = $database->connect();
            
            // First try to find with the exact course_id
            $stmt = $conn->prepare("
                SELECT cmc.id as module_content_id
                FROM course_module_content cmc
                INNER JOIN course_modules cm ON cmc.module_id = cm.id
                INNER JOIN interactive_ai_content_package ia ON cmc.content_id = ia.id
                WHERE cmc.content_id = ? 
                AND cmc.content_type = 'interactive'
                AND cm.course_id = ?
                AND ia.client_id = ?
                AND ia.is_deleted = 0
            ");
            $stmt->execute([$interactivePackageId, $courseId, $clientId]);
            $result = $stmt->fetchColumn();
            
            if ($result) {
                return $result;
            }
            
            // If not found with exact course_id, try to find any course that contains this Interactive AI content
            // This handles cases where the Interactive AI content might be in a different course
            $stmt = $conn->prepare("
                SELECT cmc.id as module_content_id
                FROM course_module_content cmc
                INNER JOIN course_modules cm ON cmc.module_id = cm.id
                INNER JOIN interactive_ai_content_package ia ON cmc.content_id = ia.id
                WHERE cmc.content_id = ? 
                AND cmc.content_type = 'interactive'
                AND ia.client_id = ?
                AND ia.is_deleted = 0
                LIMIT 1
            ");
            $stmt->execute([$interactivePackageId, $clientId]);
            $result = $stmt->fetchColumn();
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("InteractiveAIController::getModuleContentId error: " . $e->getMessage());
            return null;
        }
    }

    private function showError($message)
    {
        // Simple error display instead of requiring a non-existent error.php file
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Error - Interactive AI Content</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-6'>
                <div class='card border-danger'>
                    <div class='card-header bg-danger text-white'>
                        <h4 class='mb-0'><i class='fas fa-exclamation-triangle me-2'></i>Error</h4>
                    </div>
                    <div class='card-body'>
                        <p class='text-danger mb-3'>" . htmlspecialchars($message) . "</p>
                        <div class='d-grid gap-2'>
                            <button class='btn btn-primary' onclick='window.close()'>
                                <i class='fas fa-times me-2'></i>Close Window
                            </button>
                            <button class='btn btn-secondary' onclick='window.history.back()'>
                                <i class='fas fa-arrow-left me-2'></i>Go Back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
        exit;
    }

    /**
     * API endpoint for content loaded event
     */
    public function contentLoaded()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $contentId = $input['content_id'] ?? null;
        $courseId = $input['course_id'] ?? null;
        $moduleId = $input['module_id'] ?? null;
        $loadedAt = $input['loaded_at'] ?? time();
        
        if (!$contentId || !$courseId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Content ID and Course ID are required'
            ]);
        }
        
        // Log content loaded event
        error_log("Interactive AI content loaded: Content ID {$contentId}, Course ID {$courseId}, Module ID {$moduleId}, Time: {$loadedAt}");
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Content loaded event recorded',
            'data' => [
                'content_id' => $contentId,
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'loaded_at' => $loadedAt
            ]
        ]);
    }

    /**
     * API endpoint for time expired event
     */
    public function timeExpired()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $contentId = $input['content_id'] ?? null;
        $courseId = $input['course_id'] ?? null;
        $moduleId = $input['module_id'] ?? null;
        $expiredAt = $input['expired_at'] ?? time();
        
        if (!$contentId || !$courseId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Content ID and Course ID are required'
            ]);
        }
        
        // Log time expired event
        error_log("Interactive AI content time expired: Content ID {$contentId}, Course ID {$courseId}, Module ID {$moduleId}, Time: {$expiredAt}");
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Time expired event recorded',
            'data' => [
                'content_id' => $contentId,
                'course_id' => $courseId,
                'module_id' => $moduleId,
                'expired_at' => $expiredAt
            ]
        ]);
    }

    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
?>
