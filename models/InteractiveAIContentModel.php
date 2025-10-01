<?php
require_once 'config/Database.php';
require_once 'core/UrlHelper.php';

class InteractiveAIContentModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get comprehensive Interactive AI content data with all parameters
     */
    public function getInteractiveContent($contentId, $clientId = null)
    {
        $sql = "SELECT 
                    id, client_id, title, description, tags, version, language,
                    content_type, content_url, content_file, embed_code, 
                    thumbnail_image, metadata_file,
                    ai_model, interaction_type, tutor_personality, response_style,
                    knowledge_domain, adaptation_algorithm,
                    difficulty_level, learning_objectives, prerequisites, time_limit,
                    mobile_support, device_requirements, vr_platform, ar_platform,
                    assessment_integration, progress_tracking,
                    created_at, updated_at
                FROM interactive_ai_content_package 
                WHERE id = ? AND is_deleted = 0";
        
        $params = [$contentId];
        
        if ($clientId) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $content = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($content) {
            // Parse JSON fields if they exist
            $content['learning_objectives'] = $this->parseJSONField($content['learning_objectives']);
            $content['prerequisites'] = $this->parseJSONField($content['prerequisites']);
            $content['device_requirements'] = $this->parseJSONField($content['device_requirements']);
            $content['tags'] = $this->parseJSONField($content['tags']);
            
            // Add computed fields
            $content['has_vr_support'] = !empty($content['vr_platform']);
            $content['has_ar_support'] = !empty($content['ar_platform']);
            $content['has_assessment'] = $content['assessment_integration'] === 'Yes';
            $content['has_time_limit'] = !empty($content['time_limit']);
            $content['is_mobile_compatible'] = $content['mobile_support'] === 'Yes';
        }
        
        return $content;
    }

    /**
     * Validate user requirements for Interactive AI content
     */
    public function validateUserRequirements($contentId, $userId, $clientId = null)
    {
        $content = $this->getInteractiveContent($contentId, $clientId);
        if (!$content) {
            return ['valid' => false, 'error' => 'Content not found'];
        }

        $validation = [
            'valid' => true,
            'warnings' => [],
            'requirements' => [],
            'content' => $content
        ];

        // Check prerequisites
        if (!empty($content['prerequisites'])) {
            $prereqResult = $this->checkPrerequisites($content['prerequisites'], $userId, $clientId);
            if (!$prereqResult['met']) {
                $validation['valid'] = false;
                $validation['error'] = 'Prerequisites not met: ' . implode(', ', $prereqResult['missing']);
            }
        }

        // Check device requirements
        if (!empty($content['device_requirements'])) {
            $deviceResult = $this->checkDeviceRequirements($content['device_requirements']);
            if (!$deviceResult['compatible']) {
                $validation['warnings'][] = 'Device requirements may not be fully met: ' . implode(', ', $deviceResult['issues']);
            }
            if (is_array($deviceResult['requirements'])) {
                $validation['requirements'] = array_merge($validation['requirements'], $deviceResult['requirements']);
            } elseif (!empty($deviceResult['requirements'])) {
                $validation['requirements'][] = $deviceResult['requirements'];
            }
        }

        // Check VR/AR platform requirements
        if ($content['has_vr_support'] || $content['has_ar_support']) {
            $vrArResult = $this->checkVRARSupport($content['vr_platform'], $content['ar_platform']);
            if (!$vrArResult['supported']) {
                $validation['warnings'][] = 'VR/AR platforms may not be supported: ' . implode(', ', $vrArResult['issues']);
            }
            if (is_array($vrArResult['requirements'])) {
                $validation['requirements'] = array_merge($validation['requirements'], $vrArResult['requirements']);
            } elseif (!empty($vrArResult['requirements'])) {
                $validation['requirements'][] = $vrArResult['requirements'];
            }
        }

        // Check mobile compatibility
        if ($content['is_mobile_compatible']) {
            $mobileResult = $this->checkMobileCompatibility();
            if (!$mobileResult['compatible']) {
                $validation['warnings'][] = 'Mobile compatibility issues detected';
            }
        }

        return $validation;
    }

    /**
     * Get Interactive AI content metadata for display
     */
    public function getContentMetadata($contentId, $clientId = null)
    {
        $content = $this->getInteractiveContent($contentId, $clientId);
        if (!$content) {
            return null;
        }

        $metadata = [
            'basic_info' => [
                'title' => $content['title'],
                'description' => $content['description'],
                'version' => $content['version'],
                'language' => $content['language'],
                'tags' => $content['tags']
            ],
            'ai_configuration' => [
                'ai_model' => $content['ai_model'],
                'interaction_type' => $content['interaction_type'],
                'tutor_personality' => $content['tutor_personality'],
                'response_style' => $content['response_style'],
                'knowledge_domain' => $content['knowledge_domain'],
                'adaptation_algorithm' => $content['adaptation_algorithm']
            ],
            'learning_design' => [
                'content_type' => $content['content_type'],
                'difficulty_level' => $content['difficulty_level'],
                'learning_objectives' => $content['learning_objectives'],
                'prerequisites' => $content['prerequisites'],
                'time_limit' => $content['time_limit']
            ],
            'technical_requirements' => [
                'mobile_support' => $content['mobile_support'],
                'device_requirements' => $content['device_requirements'],
                'vr_platform' => $content['vr_platform'],
                'ar_platform' => $content['ar_platform']
            ],
            'assessment_progress' => [
                'assessment_integration' => $content['assessment_integration'],
                'progress_tracking' => $content['progress_tracking']
            ],
            'content_delivery' => [
                'content_url' => $content['content_url'],
                'content_file' => $content['content_file'],
                'embed_code' => $content['embed_code'],
                'thumbnail_image' => $content['thumbnail_image'],
                'metadata_file' => $content['metadata_file']
            ],
            'computed' => [
                'has_vr_support' => $content['has_vr_support'],
                'has_ar_support' => $content['has_ar_support'],
                'has_assessment' => $content['has_assessment'],
                'has_time_limit' => $content['has_time_limit'],
                'is_mobile_compatible' => $content['is_mobile_compatible']
            ]
        ];

        return $metadata;
    }

    /**
     * Get personalized launch configuration based on AI parameters
     */
    public function getPersonalizedLaunchConfig($contentId, $userId, $clientId = null)
    {
        $content = $this->getInteractiveContent($contentId, $clientId);
        if (!$content) {
            return null;
        }

        $config = [
            'launch_url' => $this->resolveContentUrl($content['content_url']),
            'launch_method' => $this->determineLaunchMethod($content),
            'ui_customization' => $this->getUICustomization($content),
            'progress_config' => $this->getProgressConfig($content),
            'assessment_config' => $this->getAssessmentConfig($content),
            'time_config' => $this->getTimeConfig($content),
            'device_config' => $this->getDeviceConfig($content)
        ];

        return $config;
    }

    // Private helper methods

    private function resolveContentUrl($path)
    {
        if (empty($path)) {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        if ($path[0] === '/') {
            // For absolute paths, prepend the project path
            return '/Unlockyourskills' . $path;
        }
        return UrlHelper::url($path);
    }

    private function parseJSONField($field)
    {
        if (empty($field)) {
            return null;
        }
        
        // If already an array, return as-is
        if (is_array($field)) {
            return $field;
        }
        
        // If it's a string, try to decode as JSON
        if (is_string($field)) {
            $decoded = json_decode($field, true);
            return $decoded !== null ? $decoded : $field;
        }
        
        // For other types, return as-is
        return $field;
    }

    private function checkPrerequisites($prerequisites, $userId, $clientId)
    {
        // Implementation would check user's completed prerequisites
        // For now, return mock data
        return [
            'met' => true,
            'missing' => []
        ];
    }

    private function checkDeviceRequirements($deviceRequirements)
    {
        $requirements = $this->parseJSONField($deviceRequirements);
        $issues = [];
        $compatible = true;

        // Basic device requirement checks
        if (is_array($requirements)) {
            foreach ($requirements as $requirement) {
                if (strpos(strtolower($requirement), 'gpu') !== false && !$this->hasGPU()) {
                    $issues[] = 'Dedicated GPU required';
                    $compatible = false;
                }
                if (strpos(strtolower($requirement), 'webcam') !== false && !$this->hasWebcam()) {
                    $issues[] = 'Webcam required';
                    $compatible = false;
                }
            }
        }

        return [
            'compatible' => $compatible,
            'issues' => $issues,
            'requirements' => $requirements
        ];
    }

    private function checkVRARSupport($vrPlatform, $arPlatform)
    {
        $issues = [];
        $supported = true;
        $requirements = [];

        if (!empty($vrPlatform)) {
            $requirements[] = "VR Platform: {$vrPlatform}";
            if (!$this->isVRSupported($vrPlatform)) {
                $issues[] = "VR platform {$vrPlatform} not detected";
                $supported = false;
            }
        }

        if (!empty($arPlatform)) {
            $requirements[] = "AR Platform: {$arPlatform}";
            if (!$this->isARSupported($arPlatform)) {
                $issues[] = "AR platform {$arPlatform} not detected";
                $supported = false;
            }
        }

        return [
            'supported' => $supported,
            'issues' => $issues,
            'requirements' => $requirements
        ];
    }

    private function checkMobileCompatibility()
    {
        // Basic mobile compatibility check
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);
        
        return [
            'compatible' => true, // Assume compatible for now
            'is_mobile' => $isMobile
        ];
    }

    private function determineLaunchMethod($content)
    {
        if ($content['has_vr_support']) {
            return 'vr';
        } elseif ($content['has_ar_support']) {
            return 'ar';
        } elseif (!empty($content['embed_code'])) {
            return 'embed';
        } else {
            return 'iframe';
        }
    }

    private function getUICustomization($content)
    {
        return [
            'tutor_personality' => $content['tutor_personality'],
            'response_style' => $content['response_style'],
            'difficulty_level' => $content['difficulty_level'],
            'knowledge_domain' => $content['knowledge_domain']
        ];
    }

    private function getProgressConfig($content)
    {
        return [
            'enabled' => $content['progress_tracking'] === 'Yes',
            'adaptation_algorithm' => $content['adaptation_algorithm'],
            'ai_model' => $content['ai_model']
        ];
    }

    private function getAssessmentConfig($content)
    {
        return [
            'enabled' => $content['assessment_integration'] === 'Yes',
            'integration_type' => $content['content_type']
        ];
    }

    private function getTimeConfig($content)
    {
        return [
            'limit' => $content['time_limit'],
            'has_limit' => !empty($content['time_limit'])
        ];
    }

    private function getDeviceConfig($content)
    {
        return [
            'mobile_support' => $content['mobile_support'] === 'Yes',
            'device_requirements' => $content['device_requirements'],
            'vr_platform' => $content['vr_platform'],
            'ar_platform' => $content['ar_platform']
        ];
    }

    // Mock device detection methods
    private function hasGPU() { return true; } // Mock
    private function hasWebcam() { return true; } // Mock
    private function isVRSupported($platform) { return false; } // Mock
    private function isARSupported($platform) { return false; } // Mock

    // =====================================================
    // INTERACTIVE PROGRESS TRACKING METHODS
    // =====================================================

    /**
     * Initialize Interactive AI content progress tracking
     */
    public function initializeInteractiveProgress($userId, $courseId, $contentId, $clientId, $interactivePackageId = null)
    {
        try {
            // If interactive package ID is not provided, get it from course module content
            if (!$interactivePackageId) {
                $stmt = $this->conn->prepare("
                    SELECT content_id FROM course_module_content 
                    WHERE id = ? AND content_type = 'interactive'
                ");
                $stmt->execute([$contentId]);
                $interactivePackageId = $stmt->fetchColumn();
            }

            if (!$interactivePackageId) {
                error_log("InteractiveAIContentModel::initializeInteractiveProgress - No interactive package ID found for content ID: {$contentId}");
                return false;
            }

            // Check if progress record already exists
            $stmt = $this->conn->prepare("
                SELECT id FROM interactive_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing record to mark as started
                $stmt = $this->conn->prepare("
                    UPDATE interactive_progress 
                    SET started_at = NOW(), 
                        status = 'in_progress',
                        last_interaction_at = NOW(),
                        updated_at = NOW()
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                ");
                return $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            } else {
                // Create new progress record
                $stmt = $this->conn->prepare("
                    INSERT INTO interactive_progress 
                    (user_id, course_id, content_id, interactive_package_id, client_id, 
                     started_at, status, last_interaction_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), 'in_progress', NOW(), NOW(), NOW())
                ");
                return $stmt->execute([$userId, $courseId, $contentId, $interactivePackageId, $clientId]);
            }
        } catch (PDOException $e) {
            error_log("InteractiveAIContentModel::initializeInteractiveProgress error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update Interactive AI content progress
     */
    public function updateInteractiveProgress($userId, $courseId, $contentId, $clientId, $data)
    {
        try {
            $fields = [];
            $values = [];
            
            // Define allowed fields for security
            $allowedFields = [
                'interaction_data', 'current_step', 'total_steps', 'completion_percentage',
                'is_completed', 'time_spent', 'last_interaction_at', 'user_responses', 'ai_feedback',
                'status'
            ];
            
            foreach ($data as $key => $value) {
                if (in_array($key, $allowedFields)) {
                    $fields[] = "`$key` = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($fields)) {
                error_log("InteractiveAIContentModel::updateInteractiveProgress - No valid fields to update");
                return false;
            }
            
            // Always update the last_interaction_at and updated_at timestamps
            $fields[] = "`last_interaction_at` = NOW()";
            $fields[] = "`updated_at` = NOW()";
            
            // If completion is being set to true, also set completed_at (if column exists)
            if (isset($data['is_completed']) && $data['is_completed'] == 1) {
                // Check if completed_at column exists
                $stmt = $this->conn->query("DESCRIBE interactive_progress");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('completed_at', $columns)) {
                    $fields[] = "`completed_at` = NOW()";
                }
                $fields[] = "`status` = 'completed'";
            }
            
            $values[] = $userId;
            $values[] = $courseId;
            $values[] = $contentId;
            $values[] = $clientId;
            
            $sql = "UPDATE interactive_progress SET " . implode(', ', $fields) . 
                   " WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($values);
            
            if ($result && isset($data['is_completed']) && $data['is_completed'] == 1) {
                // Log completion event
                error_log("Interactive AI content completed - User: {$userId}, Course: {$courseId}, Content: {$contentId}");
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("InteractiveAIContentModel::updateInteractiveProgress error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Interactive AI content progress for a user
     */
    public function getInteractiveProgress($userId, $courseId, $contentId, $clientId)
    {
        try {
            // First check what columns exist in the table
            $stmt = $this->conn->query("DESCRIBE interactive_progress");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $selectFields = [
                'id', 'user_id', 'course_id', 'content_id', 'interactive_package_id', 'client_id',
                'started_at', 'last_interaction_at', 'time_spent',
                'completion_percentage', 'is_completed', 'status',
                'interaction_data', 'current_step', 'total_steps',
                'user_responses', 'ai_feedback', 'created_at', 'updated_at'
            ];
            
            // Add completed_at if it exists
            if (in_array('completed_at', $columns)) {
                $selectFields[] = 'completed_at';
            }
            
            $sql = "SELECT " . implode(', ', $selectFields) . " FROM interactive_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                    ORDER BY updated_at DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                // Parse JSON fields
                $progress['interaction_data'] = $this->parseJSONField($progress['interaction_data']);
                $progress['user_responses'] = $this->parseJSONField($progress['user_responses']);
                $progress['ai_feedback'] = $this->parseJSONField($progress['ai_feedback']);
                
                // Ensure completed_at exists in the result
                if (!isset($progress['completed_at'])) {
                    $progress['completed_at'] = null;
                }
            }
            
            return $progress;
        } catch (PDOException $e) {
            error_log("InteractiveAIContentModel::getInteractiveProgress error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user has started Interactive AI content
     */
    public function hasStartedInteractiveContent($userId, $courseId, $contentId, $clientId)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT id FROM interactive_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                AND started_at IS NOT NULL
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("InteractiveAIContentModel::hasStartedInteractiveContent error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has completed Interactive AI content
     */
    public function hasCompletedInteractiveContent($userId, $courseId, $contentId, $clientId)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT id FROM interactive_progress 
                WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?
                AND is_completed = 1
            ");
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("InteractiveAIContentModel::hasCompletedInteractiveContent error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get progress statistics for Interactive AI content
     */
    public function getInteractiveProgressStats($contentId, $clientId)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN started_at IS NOT NULL THEN 1 ELSE 0 END) as started_count,
                    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_count,
                    AVG(CASE WHEN is_completed = 1 THEN completion_percentage ELSE NULL END) as avg_completion_percentage,
                    AVG(CASE WHEN is_completed = 1 THEN time_spent ELSE NULL END) as avg_time_spent
                FROM interactive_progress 
                WHERE content_id = ? AND client_id = ?
            ");
            $stmt->execute([$contentId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("InteractiveAIContentModel::getInteractiveProgressStats error: " . $e->getMessage());
            return null;
        }
    }
}
?>
