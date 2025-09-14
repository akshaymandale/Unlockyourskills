<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'models/DocumentProgressModel.php';
require_once 'core/UrlHelper.php';

class DocumentProgressController {
    private $documentProgressModel;
    private $conn;

    public function __construct() {
        $this->documentProgressModel = new DocumentProgressModel();
        
        // Get database connection
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $this->conn = $database->connect();
        } catch (Exception $e) {
            error_log("Error connecting to database: " . $e->getMessage());
            $this->conn = null;
        }
    }

    /**
     * Start document tracking when user opens a document
     */
    public function startTracking() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        // Get request data
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $moduleId = $input['module_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $documentPackageId = $input['document_package_id'] ?? null;
        $totalPages = $input['total_pages'] ?? 0;

        // If document_package_id is not provided, try to get it from the content_id
        if (!$documentPackageId && $contentId) {
            try {
                require_once 'models/VLRModel.php';
                $vlrModel = new VLRModel();
                $documentContent = $vlrModel->getDocumentById($contentId);
                if ($documentContent) {
                    $documentPackageId = $documentContent['id'];
                }
            } catch (Exception $e) {
                // If we can't get the document package ID, use content_id as fallback
                $documentPackageId = $contentId;
            }
        }

        if (!$courseId || !$contentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters: course_id and content_id']);
            return;
        }

        try {
            $result = $this->documentProgressModel->startTracking(
                $userId,
                $courseId,
                $contentId,
                $documentPackageId,
                $clientId,
                $totalPages
            );

            // Note: Completion tracking is now handled only when content is actually completed

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Update document progress (page view, time spent, etc.)
     */
    public function updateProgress() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        // Get request data
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $currentPage = $input['current_page'] ?? 1;
        $pagesViewed = $input['pages_viewed'] ?? [];
        $timeSpent = $input['time_spent'] ?? 0;
        $viewedPercentage = $input['viewed_percentage'] ?? 0;

        if (!$courseId || !$contentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            $result = $this->documentProgressModel->updateProgress(
                $userId,
                $courseId,
                $contentId,
                $clientId,
                $currentPage,
                $pagesViewed,
                $timeSpent,
                $viewedPercentage
            );

            // If document is completed, update completion tracking
            if ($result && isset($result['is_completed']) && $result['is_completed']) {
                require_once 'models/CompletionTrackingService.php';
                $completionService = new CompletionTrackingService();
                $completionService->handleContentCompletion($userId, $courseId, $contentId, 'document', $clientId);
            }

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Mark document as completed
     */
    public function markComplete() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        // Get request data
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;

        if (!$courseId || !$contentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            $result = $this->documentProgressModel->markComplete(
                $userId,
                $courseId,
                $contentId,
                $clientId
            );

            // Handle completion tracking when content is actually completed
            if ($result) {
                require_once 'models/CompletionTrackingService.php';
                $completionService = new CompletionTrackingService();
                $completionService->handleContentCompletion($userId, $courseId, $contentId, 'document', $clientId);
            }

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Get document progress for a user
     */
    public function getProgress() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        $courseId = $_GET['course_id'] ?? null;
        $contentId = $_GET['content_id'] ?? null;

        if (!$courseId || !$contentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            $progress = $this->documentProgressModel->getProgress(
                $userId,
                $courseId,
                $contentId,
                $clientId
            );
            
            if ($progress) {
                // Add completion status
                $completionStatus = $this->documentProgressModel->getCompletionStatus($userId, $courseId, $contentId, $clientId);
                $progress['completion_status'] = $completionStatus;
                
                // Add status text for display
                $progress['status_text'] = $this->getStatusText($completionStatus['status']);
            }

            echo json_encode(['success' => true, 'data' => $progress]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Save user bookmark in document
     */
    public function saveBookmark() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        // Get request data
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $page = $input['page'] ?? 1;
        $title = $input['title'] ?? '';
        $note = $input['note'] ?? '';

        if (!$courseId || !$contentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            $result = $this->documentProgressModel->saveBookmark(
                $userId,
                $courseId,
                $contentId,
                $clientId,
                $page,
                $title,
                $note
            );

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Save user notes for document
     */
    public function saveNotes() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }

        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;

        // Get request data
        $input = json_decode(file_get_contents('php://input'), true);
        $courseId = $input['course_id'] ?? null;
        $contentId = $input['content_id'] ?? null;
        $notes = $input['notes'] ?? '';

        if (!$courseId || !$contentId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
            return;
        }

        try {
            $result = $this->documentProgressModel->saveNotes(
                $userId,
                $courseId,
                $contentId,
                $clientId,
                $notes
            );

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Debug endpoint to test document progress tracking
     */
    public function debug() {
        if (!isset($_SESSION['user']['id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'User not authenticated']);
            return;
        }
        
        $userId = $_SESSION['user']['id'];
        $clientId = $_SESSION['user']['client_id'] ?? null;
        
        try {
            // Get all document progress for this user
            $sql = "SELECT * FROM document_progress WHERE user_id = ?";
            if ($clientId) {
                $sql .= " AND client_id = ?";
            }
            $sql .= " ORDER BY created_at DESC LIMIT 10";
            
            $stmt = $this->conn->prepare($sql);
            if ($clientId) {
                $stmt->execute([$userId, $clientId]);
            } else {
                $stmt->execute([$userId]);
            }
            
            $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'user_id' => $userId,
                    'client_id' => $clientId,
                    'session_data' => $_SESSION,
                    'progress_records' => $progress,
                    'total_records' => count($progress)
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Get human-readable status text
     */
    private function getStatusText($status) {
        switch ($status) {
            case 'completed':
                return 'Completed';
            case 'in_progress':
                return 'In Progress';
            case 'not_started':
                return 'Not Started';
            case 'incomplete':
                return 'Incomplete';
            default:
                return 'Unknown';
        }
    }

    /**
     * Check if content is a prerequisite and start tracking
     */
    private function startPrerequisiteTrackingIfApplicable($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this content is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, $contentType);
            
            if ($isPrerequisite) {
                $completionService->startPrerequisiteTracking($userId, $courseId, $contentId, $contentType, $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in startPrerequisiteTrackingIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Start module tracking if content belongs to a module
     */
    private function startModuleTrackingIfApplicable($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Start module tracking if this content belongs to a module
            $completionService->startModuleTrackingIfApplicable($userId, $courseId, $contentId, $contentType, $clientId);
        } catch (Exception $e) {
            error_log("Error in startModuleTrackingIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Check if content is a prerequisite and mark as complete
     */
    private function markPrerequisiteCompleteIfApplicable($userId, $courseId, $contentId, $contentType, $clientId) {
        try {
            require_once 'models/CompletionTrackingService.php';
            $completionService = new CompletionTrackingService();
            
            // Check if this content is a prerequisite
            $isPrerequisite = $this->isContentPrerequisite($courseId, $contentId, $contentType);
            
            if ($isPrerequisite) {
                $completionService->markPrerequisiteComplete($userId, $courseId, $contentId, $contentType, $clientId);
            }
        } catch (Exception $e) {
            error_log("Error in markPrerequisiteCompleteIfApplicable: " . $e->getMessage());
        }
    }

    /**
     * Check if content is a prerequisite
     */
    private function isContentPrerequisite($courseId, $contentId, $contentType) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT COUNT(*) FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $contentId, $contentType]);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Error checking if content is prerequisite: " . $e->getMessage());
            return false;
        }
    }
}
