<?php

require_once __DIR__ . '/../config/autoload.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/VLRModel.php';
require_once __DIR__ . '/../models/ProgressTrackingModel.php';

/**
 * SCORM Controller
 * Handles SCORM content launching and management
 */
class SCORMController extends BaseController
{
    private $vlrModel;
    private $progressTrackingModel;
    private $conn;

    public function __construct()
    {
        $this->vlrModel = new VLRModel();
        $this->progressTrackingModel = new ProgressTrackingModel();
        
        // Initialize database connection
        require_once 'config/Database.php';
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Launch SCORM content
     * GET /scorm/launch
     */
    public function launch()
    {
        try {
            // Debug session information
            error_log("[SCORM] Launch method called");
            error_log("[SCORM] Session data: " . print_r($_SESSION, true));
            error_log("[SCORM] Session ID: " . session_id());
            error_log("[SCORM] Session status: " . session_status());
            
            // Get parameters
            $courseId = $_GET['course_id'] ?? null;
            $moduleId = $_GET['module_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;
            $scormId = $_GET['scorm_id'] ?? null;
            $prerequisiteId = $_GET['prerequisite_id'] ?? null; // This is now the prerequisite record ID
            $prerequisiteScormPackageId = null; // This will be the SCORM package ID

            // Validate required parameters (module_id is now optional)
            if (!$courseId || !$contentId) {
                $this->toastError('Missing required parameters for SCORM content.', '/unlockyourskills/my-courses');
                return;
            }

            // For prerequisites, module_id might not be provided - use a fallback
            if (!$moduleId) {
                $moduleId = 'prerequisite_' . ($prerequisiteId ?? $contentId);
                error_log("[SCORM] No module_id provided, using fallback: {$moduleId}");
            }
            
            // If this is a prerequisite SCORM launch, look up the SCORM package ID from the prerequisite record
            if ($prerequisiteId) {
                try {
                    require_once 'config/Database.php';
                    $database = new Database();
                    $conn = $database->connect();
                    
                    $stmt = $conn->prepare("
                        SELECT prerequisite_id FROM course_prerequisites 
                        WHERE id = ? AND course_id = ? AND prerequisite_type = 'scorm'
                        LIMIT 1
                    ");
                    $stmt->execute([$prerequisiteId, $courseId]);
                    $prerequisiteScormPackageId = $stmt->fetchColumn();
                    
                    if ($prerequisiteScormPackageId) {
                        error_log("[SCORM] Found SCORM package ID: {$prerequisiteScormPackageId} for prerequisite record ID: {$prerequisiteId}");
                    } else {
                        error_log("[SCORM] No SCORM package found for prerequisite record ID: {$prerequisiteId}");
                    }
                } catch (Exception $e) {
                    error_log("[SCORM] Error looking up SCORM package: " . $e->getMessage());
                }
            }

            // Get user information
            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            
            error_log("[SCORM] User ID: " . ($userId ?? 'null'));
            error_log("[SCORM] Client ID: " . ($clientId ?? 'null'));

            if (!$userId || !$clientId) {
                error_log("[SCORM] Authentication failed - redirecting to login");
                $this->toastError('User not authenticated.', '/unlockyourskills/login');
                return;
            }
            
            error_log("[SCORM] Authentication successful");

            // Get SCORM package information from the database
            $scormPackage = null;
            
            // First try to get by scormId if provided
            if ($scormId) {
                $scormPackage = $this->vlrModel->getScormPackageById($scormId, $clientId);
            }
            
            // If no specific SCORM package, try to get from content
            if (!$scormPackage) {
                // Determine if this is a module or prerequisite flow
                // Module flow: has module_id parameter and no prerequisite_id
                // Prerequisite flow: has prerequisite_id parameter
                if ($moduleId && !$prerequisiteId) {
                    // Module flow - use module content ID method
                    try {
                        $scormPackage = $this->vlrModel->getScormPackageByModuleContentId($contentId, $clientId);
                        
                        if ($scormPackage) {
                            error_log("[SCORM] Found SCORM package for module content {$contentId}: " . json_encode($scormPackage));
                        } else {
                            error_log("[SCORM] No SCORM package found for module content {$contentId}");
                        }
                    } catch (Exception $e) {
                        error_log("[SCORM] Error finding SCORM package for module content {$contentId}: " . $e->getMessage());
                    }
                } else if ($prerequisiteId && $prerequisiteScormPackageId) {
                    // Prerequisite flow - use SCORM package ID directly
                    try {
                        $scormPackage = $this->vlrModel->getScormPackageById($prerequisiteScormPackageId, $clientId);
                        
                        if ($scormPackage) {
                            error_log("[SCORM] Found SCORM package for prerequisite SCORM package ID {$prerequisiteScormPackageId}: " . json_encode($scormPackage));
                        } else {
                            error_log("[SCORM] No SCORM package found for prerequisite SCORM package ID {$prerequisiteScormPackageId}");
                        }
                    } catch (Exception $e) {
                        error_log("[SCORM] Error finding SCORM package for prerequisite SCORM package ID {$prerequisiteScormPackageId}: " . $e->getMessage());
                    }
                } else {
                    // Fallback - try to get by content ID
                    try {
                        $scormPackage = $this->vlrModel->getScormPackageByContentId($contentId, $clientId);
                        
                        if ($scormPackage) {
                            error_log("[SCORM] Found SCORM package for content ID {$contentId}: " . json_encode($scormPackage));
                        } else {
                            error_log("[SCORM] No SCORM package found for content ID {$contentId}");
                        }
                    } catch (Exception $e) {
                        error_log("[SCORM] Error finding SCORM package for content ID {$contentId}: " . $e->getMessage());
                    }
                }
            }
            
            // If still no SCORM package, create a fallback
            if (!$scormPackage) {
                error_log("[SCORM] Using fallback SCORM package for content {$contentId}");
                $scormPackage = [
                    'title' => 'SCORM Content',
                    'content_id' => $contentId,
                    'launch_path' => "index.html"
                ];
            }

            if (!$scormPackage) {
                $this->toastError('SCORM package not found.', '/unlockyourskills/my-courses');
                return;
            }

            // Check if user has access to this course
            $hasAccess = $this->progressTrackingModel->hasCourseAccess($userId, $courseId, $clientId);
            if (!$hasAccess) {
                $this->toastError('Access denied to this course.', '/unlockyourskills/my-courses');
                return;
            }

            // Determine content type first
            $contentType = $this->determineContentType($moduleId, $prerequisiteId, $courseId, $contentId);
            error_log("[SCORM] Determined content type: {$contentType}");
            
            // Initialize course progress if needed (but not for prerequisites to avoid duplicates)
            if ($contentType !== 'prerequisite') {
                $this->progressTrackingModel->initializeCourseProgress($userId, $courseId, $clientId);
            }
            
            // Initialize SCORM progress based on content type
            if ($contentType === 'prerequisite') {
                // Check if prerequisite SCORM progress already exists
                $existingProgress = $this->progressTrackingModel->getPrerequisiteScormProgress($userId, $courseId, $prerequisiteScormPackageId, $prerequisiteId, $clientId);
                
                if (!$existingProgress) {
                    $this->progressTrackingModel->initializePrerequisiteScormProgress($userId, $courseId, $prerequisiteScormPackageId, $clientId, $prerequisiteId);
                    error_log("[SCORM] Initialized SCORM progress for prerequisite SCORM package {$prerequisiteScormPackageId}");
                } else {
                    error_log("[SCORM] Prerequisite SCORM progress already exists for package {$prerequisiteScormPackageId}, skipping initialization");
                }
                
                // For prerequisites, we need to find the course_module_content.id that corresponds to this SCORM package
                // This is needed because the launcher will use this content_id for progress updates
                $launcherContentId = $this->progressTrackingModel->getModuleContentIdForScormPackage($prerequisiteScormPackageId);
                
                if (!$launcherContentId) {
                    error_log("[SCORM] Warning: No module content found for SCORM package {$prerequisiteScormPackageId}, using package ID as fallback");
                    $launcherContentId = $prerequisiteScormPackageId;
                } else {
                    error_log("[SCORM] Found module content ID {$launcherContentId} for SCORM package {$prerequisiteScormPackageId}");
                }
            } elseif ($contentType === 'postrequisite') {
                // Check if post-requisite SCORM progress already exists
                $existingProgress = $this->progressTrackingModel->getScormProgress($userId, $courseId, $contentId, $clientId);
                
                if (!$existingProgress) {
                    $this->progressTrackingModel->initializeScormProgress($userId, $courseId, $contentId, $clientId, null, null, $contentId);
                    error_log("[SCORM] Initialized SCORM progress for post-requisite content {$contentId}");
                } else {
                    error_log("[SCORM] Post-requisite SCORM progress already exists for content {$contentId}, skipping initialization");
                }
                $launcherContentId = $contentId; // For post-requisites, contentId is already course_module_content.id
            } else {
                // Check if module SCORM progress already exists
                $existingProgress = $this->progressTrackingModel->getScormProgress($userId, $courseId, $contentId, $clientId);
                
                if (!$existingProgress) {
                    $this->progressTrackingModel->initializeScormProgress($userId, $courseId, $contentId, $clientId, null, $contentId, null);
                    error_log("[SCORM] Initialized SCORM progress for module content {$contentId}");
                } else {
                    error_log("[SCORM] Module SCORM progress already exists for content {$contentId}, skipping initialization");
                }
                $launcherContentId = $contentId; // For modules, contentId is already course_module_content.id
            }

            // Get resume data using the launcher content ID
            $resumeData = $this->progressTrackingModel->getContentResumePosition($userId, $courseId, $launcherContentId, $clientId);

                                // Prepare data for the launcher
                    $launcherData = [
                        'course_id' => $courseId,
                        'module_id' => $moduleId,
                        'content_id' => $launcherContentId, // Use the correct content_id for the launcher
                        'scorm_url' => $this->buildScormUrl($scormPackage),
                        'title' => $scormPackage['title'],
                        'resume_data' => $resumeData,
                        'content_type' => $contentType, // Add content type for context
                        'prerequisite_id' => $prerequisiteId, // Add prerequisite record ID for context
                        'scorm_package_id' => $scormPackage['id'] ?? $prerequisiteScormPackageId ?? $contentId // Add SCORM package ID for context
                    ];

            // Log SCORM launch
            error_log("[SCORM] User {$userId} launching SCORM content: " . json_encode($launcherData));

            // Include the SCORM launcher view
            include __DIR__ . '/../views/scorm_launcher.php';

        } catch (Exception $e) {
            error_log("[SCORM] Error launching SCORM content: " . $e->getMessage());
            $this->toastError('Failed to launch SCORM content: ' . $e->getMessage(), '/unlockyourskills/my-courses');
        }
    }

    

    /**
     * Build the full SCORM URL from package data
     */
    private function buildScormUrl($scormPackage)
    {
        // Check if zip_file exists and is not empty
        if (isset($scormPackage['zip_file']) && !empty($scormPackage['zip_file'])) {
            // Extract the package name from zip_file (remove .zip extension)
            $packageName = str_replace('.zip', '', $scormPackage['zip_file']);
            // Build the full path to the extracted SCORM content
            $basePath = "/Unlockyourskills/uploads/scorm/{$packageName}";
        } else {
            // Fallback: use content_id for path construction
            $contentId = isset($scormPackage['content_id']) ? $scormPackage['content_id'] : 'unknown';
            $basePath = "/Unlockyourskills/uploads/scorm/content_{$contentId}";
        }
        
        // Check if launch_path exists and is not empty
        $launchPath = isset($scormPackage['launch_path']) && !empty($scormPackage['launch_path']) 
            ? $scormPackage['launch_path'] 
            : 'index.html';
        
        // If launch_path is relative, combine with base path
        if (!preg_match('/^https?:\/\//', $launchPath)) {
            $fullUrl = $basePath . '/' . ltrim($launchPath, '/');
        } else {
            $fullUrl = $launchPath;
        }
        
        error_log("[SCORM] Built SCORM URL: {$fullUrl} from package: " . json_encode($scormPackage));
        
        return $fullUrl;
    }

    /**
     * Get SCORM progress data
     * POST /scorm/progress
     */
    public function getProgress()
    {
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $contentId = $_POST['content_id'] ?? null;

            if (!$userId || !$clientId || !$courseId || !$contentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Get progress data
            $progress = $this->progressTrackingModel->getContentProgress($userId, $courseId, $contentId, 'scorm', $clientId);

            echo json_encode([
                'success' => true,
                'data' => $progress
            ]);

        } catch (Exception $e) {
            error_log("[SCORM] Error getting progress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Update SCORM progress
     * POST /scorm/update
     */
    public function updateProgress()
    {
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $contentId = $_POST['content_id'] ?? null;
            $progressData = $_POST['progress_data'] ?? null;

            if (!$userId || !$clientId || !$courseId || !$contentId || !$progressData) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Parse progress data
            if (is_string($progressData)) {
                $progressData = json_decode($progressData, true);
            }

            if (!$progressData) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid progress data']);
                return;
            }

            // Check if this is a prerequisite by looking for prerequisite context in the request
            $prerequisiteId = $_POST['prerequisite_id'] ?? null;
            $scormPackageId = $_POST['scorm_package_id'] ?? null;
            $contentType = $_POST['content_type'] ?? null;
            
            // Update progress using the appropriate method based on content type
            if ($contentType === 'prerequisite' && $prerequisiteId && $scormPackageId) {
                error_log("[SCORM] Updating prerequisite SCORM progress - prerequisiteId: $prerequisiteId, scormPackageId: $scormPackageId");
                $result = $this->progressTrackingModel->updatePrerequisiteScormProgress(
                    $userId,
                    $courseId,
                    $scormPackageId,
                    $prerequisiteId,
                    $clientId,
                    $progressData
                );
            } else {
                error_log("[SCORM] Updating regular SCORM progress - contentId: $contentId");
                $result = $this->progressTrackingModel->updateScormProgress(
                    $userId,
                    $courseId,
                    $contentId,
                    $clientId,
                    $progressData
                );
            }

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Progress updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update progress'
                ]);
            }

        } catch (Exception $e) {
            error_log("[SCORM] Error updating progress: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Complete SCORM content
     * POST /scorm/complete
     */
    public function complete()
    {
        try {
            error_log("[SCORM] Complete endpoint called");
            error_log("[SCORM] Complete endpoint - POST data: " . json_encode($_POST));
            
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $contentId = $_POST['content_id'] ?? null;
            $courseId = $_POST['course_id'] ?? null;
            $moduleId = $_POST['module_id'] ?? null;
            
            // Get prerequisite context information
            $prerequisiteId = $_POST['prerequisite_id'] ?? null;
            $scormPackageId = $_POST['scorm_package_id'] ?? null;
            $contentType = $_POST['content_type'] ?? null;
            
            error_log("[SCORM] Complete endpoint - Parameters: userId=$userId, clientId=$clientId, contentId=$contentId, courseId=$courseId, moduleId=$moduleId");
            error_log("[SCORM] Complete endpoint - Context: prerequisiteId=$prerequisiteId, scormPackageId=$scormPackageId, contentType=$contentType");

            if (!$userId || !$clientId || !$contentId || !$courseId) {
                error_log("[SCORM] Complete endpoint - Missing required parameters");
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Note: Completion tracking is now handled only when content is actually completed

            // Mark content as complete
            $updateData = [
                'lesson_status' => 'completed'
            ];
            
            // Use context-aware update method
            if ($contentType === 'prerequisite' && $prerequisiteId && $scormPackageId) {
                error_log("[SCORM] Complete endpoint - Updating prerequisite SCORM progress - prerequisiteId: $prerequisiteId, scormPackageId: $scormPackageId");
                $result = $this->progressTrackingModel->updatePrerequisiteScormProgress(
                    $userId,
                    $courseId,
                    $scormPackageId,
                    $prerequisiteId,
                    $clientId,
                    $updateData
                );
            } else {
                error_log("[SCORM] Complete endpoint - Updating regular SCORM progress - contentId: $contentId");
                $result = $this->progressTrackingModel->updateScormProgress(
                    $userId,
                    $courseId,
                    $contentId,
                    $clientId,
                    $updateData
                );
            }
            
            error_log("[SCORM] Complete endpoint - updateScormProgress result: " . ($result ? 'SUCCESS' : 'FAILED'));

            if ($result) {
                // Handle shared SCORM completion (auto-complete if same SCORM exists in modules/prerequisites)
                $this->handleSharedScormCompletion($userId, $courseId, $contentId, $clientId, $contentType, $prerequisiteId, $scormPackageId);
                

                echo json_encode([
                    'success' => true,
                    'message' => 'Content completed successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to complete content'
                ]);
            }

        } catch (Exception $e) {
            error_log("[SCORM] Error completing content: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * Get SCORM resume data
     * GET /scorm/resume
     */
    public function getResume()
    {
        try {
            // Validate request
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            $userId = $_SESSION['user']['id'] ?? null;
            $clientId = $_SESSION['user']['client_id'] ?? null;
            $courseId = $_GET['course_id'] ?? null;
            $contentId = $_GET['content_id'] ?? null;

            if (!$userId || !$clientId || !$courseId || !$contentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required parameters']);
                return;
            }

            // Get resume data
            $resumeData = $this->progressTrackingModel->getContentResumePosition($userId, $courseId, $contentId, $clientId);

            echo json_encode([
                'success' => true,
                'data' => $resumeData
            ]);

        } catch (Exception $e) {
            error_log("[SCORM] Error getting resume data: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }


    /**
     * Determine content type based on parameters and database lookup
     */
    private function determineContentType($moduleId, $prerequisiteId, $courseId, $contentId) {
        try {
            // If prerequisite_id parameter is provided, it's a prerequisite
            if ($prerequisiteId) {
                return 'prerequisite';
            }
            
            // If no module_id but has content_id, check if it's a post-requisite
            if (!$moduleId && $contentId) {
                // Check if this content is in a post-requisite section
                if ($this->isContentPostrequisite($courseId, $contentId)) {
                    return 'postrequisite';
                }
            }
            
            // If module_id is provided, it's a module
            if ($moduleId) {
                return 'module';
            }
            
            // Default to module if we can't determine
            return 'module';
            
        } catch (Exception $e) {
            error_log("Error determining content type: " . $e->getMessage());
            return 'module'; // Default fallback
        }
    }

    /**
     * Check if content is a post-requisite
     */
    private function isContentPostrequisite($courseId, $contentId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if this content is in a post-requisite section
            // For now, we'll use a simple heuristic: if it's not a prerequisite and has module_id, it could be post-requisite
            // You may need to adjust this based on your actual post-requisite implementation
            
            // Since there's no course_post_requisites table, we'll return false for now
            // This can be implemented when post-requisites are properly set up
            return false;
        } catch (Exception $e) {
            error_log("Error checking if content is post-requisite: " . $e->getMessage());
            return false;
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
    
    /**
     * Handle shared SCORM completion - auto-complete if same SCORM exists in modules/prerequisites
     */
    private function handleSharedScormCompletion($userId, $courseId, $contentId, $clientId, $contentType, $prerequisiteId = null, $scormPackageId = null) {
        try {
            // Determine the actual SCORM package ID that was completed
            $completedScormId = null;
            
            if ($contentType === 'prerequisite' && $scormPackageId) {
                $completedScormId = $scormPackageId;
            } else {
                $completedScormId = $contentId;
            }
            
            if (!$completedScormId) {
                error_log("[SCORM] handleSharedScormCompletion - No SCORM ID to work with");
                return;
            }
            
            error_log("[SCORM] handleSharedScormCompletion - Processing SCORM ID: $completedScormId for user: $userId, course: $courseId");
            
            // Get the latest SCORM progress for this SCORM package
            $scormProgress = $this->getLatestScormProgress($completedScormId, $userId, $courseId, $clientId);
            
            if (!$scormProgress) {
                error_log("[SCORM] handleSharedScormCompletion - No SCORM progress found");
                return;
            }
            
            // Check if this SCORM also exists in modules (if completed as prerequisite)
            if ($contentType === 'prerequisite') {
                $moduleContents = $this->getModuleContentsForScorm($courseId, $completedScormId);
                
                if (!empty($moduleContents)) {
                    // Create duplicate entries for each module content
                    foreach ($moduleContents as $moduleContent) {
                        $this->createModuleScormProgress($scormProgress, $moduleContent, $userId, $courseId, $clientId);
                    }
                    
                    error_log("[SCORM] Created module SCORM progress for shared SCORM $completedScormId in course $courseId for user $userId");
                }
            }
            
            // Check if this SCORM also exists as a prerequisite (if completed as module)
            if ($contentType !== 'prerequisite') {
                $prerequisiteIds = $this->getPrerequisiteIdsForScorm($courseId, $completedScormId);
                
                if (!empty($prerequisiteIds)) {
                    // Create duplicate entries for each prerequisite
                    foreach ($prerequisiteIds as $prereqId) {
                        $this->createPrerequisiteScormProgress($scormProgress, $prereqId, $userId, $courseId, $clientId);
                    }
                    
                    error_log("[SCORM] Created prerequisite SCORM progress for shared SCORM $completedScormId in course $courseId for user $userId");
                }
            }
            
        } catch (Exception $e) {
            error_log("[SCORM] Error handling shared SCORM completion: " . $e->getMessage());
        }
    }
    
    /**
     * Get module contents that contain this SCORM
     */
    private function getModuleContentsForScorm($courseId, $scormId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT cmc.id as content_id, cmc.module_id, cmc.content_id as scorm_id
                    FROM course_module_content cmc
                    JOIN course_modules cm ON cmc.module_id = cm.id
                    WHERE cm.course_id = ? AND cmc.content_id = ? AND cmc.content_type = 'scorm' 
                    AND cmc.deleted_at IS NULL AND cm.deleted_at IS NULL";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $scormId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[SCORM] Error getting module contents for SCORM: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get prerequisite IDs that contain this SCORM
     */
    private function getPrerequisiteIdsForScorm($courseId, $scormId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT id FROM course_prerequisites 
                    WHERE course_id = ? AND prerequisite_id = ? AND prerequisite_type = 'scorm' 
                    AND deleted_at IS NULL";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$courseId, $scormId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            error_log("[SCORM] Error getting prerequisite IDs for SCORM: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the latest SCORM progress for this SCORM package
     */
    private function getLatestScormProgress($scormId, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            $sql = "SELECT * FROM scorm_progress 
                    WHERE scorm_package_id = ? AND user_id = ? AND course_id = ? AND client_id = ? 
                    ORDER BY completed_at DESC LIMIT 1";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$scormId, $userId, $courseId, $clientId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("[SCORM] Error getting latest SCORM progress: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create SCORM progress entry for module content
     */
    private function createModuleScormProgress($scormProgress, $moduleContent, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if progress already exists for this module content
            $checkSql = "SELECT id FROM scorm_progress 
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$userId, $courseId, $moduleContent['content_id'], $clientId]);
            
            if ($checkStmt->fetch()) {
                error_log("[SCORM] Module SCORM progress already exists for content {$moduleContent['content_id']}");
                return;
            }
            
            // Create new progress entry for module content
            $sql = "INSERT INTO scorm_progress (
                        user_id, course_id, content_id, scorm_package_id, client_id,
                        lesson_status, lesson_location, score_raw, score_min, score_max,
                        total_time, session_time, suspend_data, launch_data, interactions, objectives,
                        completed_at, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        NOW(), NOW(), NOW()
                    )";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $userId, $courseId, $moduleContent['content_id'], $scormProgress['scorm_package_id'], $clientId,
                $scormProgress['lesson_status'], $scormProgress['lesson_location'], 
                $scormProgress['score_raw'], $scormProgress['score_min'], $scormProgress['score_max'],
                $scormProgress['total_time'], $scormProgress['session_time'], 
                $scormProgress['suspend_data'], $scormProgress['launch_data'], 
                $scormProgress['interactions'], $scormProgress['objectives']
            ]);
            
            if ($result) {
                error_log("[SCORM] Created module SCORM progress for content {$moduleContent['content_id']}");
            } else {
                error_log("[SCORM] Failed to create module SCORM progress for content {$moduleContent['content_id']}");
            }
            
        } catch (Exception $e) {
            error_log("[SCORM] Error creating module SCORM progress: " . $e->getMessage());
        }
    }
    
    /**
     * Create SCORM progress entry for prerequisite
     */
    private function createPrerequisiteScormProgress($scormProgress, $prerequisiteId, $userId, $courseId, $clientId) {
        try {
            require_once 'config/Database.php';
            $database = new Database();
            $conn = $database->connect();
            
            // Check if progress already exists for this prerequisite
            $checkSql = "SELECT id FROM scorm_progress 
                        WHERE user_id = ? AND course_id = ? AND prerequisite_id = ? AND client_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([$userId, $courseId, $prerequisiteId, $clientId]);
            
            if ($checkStmt->fetch()) {
                error_log("[SCORM] Prerequisite SCORM progress already exists for prerequisite {$prerequisiteId}");
                return;
            }
            
            // Create new progress entry for prerequisite
            $sql = "INSERT INTO scorm_progress (
                        user_id, course_id, prerequisite_id, scorm_package_id, client_id,
                        lesson_status, lesson_location, score_raw, score_min, score_max,
                        total_time, session_time, suspend_data, launch_data, interactions, objectives,
                        completed_at, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        NOW(), NOW(), NOW()
                    )";
            
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([
                $userId, $courseId, $prerequisiteId, $scormProgress['scorm_package_id'], $clientId,
                $scormProgress['lesson_status'], $scormProgress['lesson_location'], 
                $scormProgress['score_raw'], $scormProgress['score_min'], $scormProgress['score_max'],
                $scormProgress['total_time'], $scormProgress['session_time'], 
                $scormProgress['suspend_data'], $scormProgress['launch_data'], 
                $scormProgress['interactions'], $scormProgress['objectives']
            ]);
            
            if ($result) {
                error_log("[SCORM] Created prerequisite SCORM progress for prerequisite {$prerequisiteId}");
            } else {
                error_log("[SCORM] Failed to create prerequisite SCORM progress for prerequisite {$prerequisiteId}");
            }
            
        } catch (Exception $e) {
            error_log("[SCORM] Error creating prerequisite SCORM progress: " . $e->getMessage());
        }
    }
}