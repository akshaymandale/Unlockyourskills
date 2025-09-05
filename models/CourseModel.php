<?php
require_once 'config/Database.php';

class CourseModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Create a new course
    public function createCourse($data, $files, $userId, $clientId)
    {
        error_log("=== COURSE CREATION STARTED ===");
        error_log("User ID: $userId, Client ID: $clientId");
        error_log("Data keys received: " . json_encode(array_keys($data)));
        error_log("Full data received: " . json_encode($data));
        // Decode JSON fields if they are strings
        foreach (['modules', 'prerequisites', 'post_requisites'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                } else {
                    error_log("[ERROR] Failed to decode $key: " . json_last_error_msg());
                    $data[$key] = [];
                }
            }
        }
        
        try {
            $this->conn->beginTransaction();

            // Server-side validation for required fields
            if (empty($data['name'])) {
                return ['success' => false, 'message' => 'Course title is required.'];
            }
            if (empty($data['category_id'])) {
                return ['success' => false, 'message' => 'Course category is required.'];
            }
            if (empty($data['subcategory_id'])) {
                return ['success' => false, 'message' => 'Course subcategory is required.'];
            }
            if (empty($data['course_type'])) {
                return ['success' => false, 'message' => 'Course type is required.'];
            }
            if (empty($data['difficulty_level'])) {
                return ['success' => false, 'message' => 'Difficulty level is required.'];
            }

            // Handle file uploads
            $thumbnailPath = null;
            $bannerPath = null;

            if (!empty($files['thumbnail']['name'])) {
                $thumbnailPath = $this->uploadFile($files['thumbnail'], 'uploads/logos/');
            }
            if (!empty($files['banner']['name'])) {
                $bannerPath = $this->uploadFile($files['banner'], 'uploads/logos/');
            }

            // Map course_type to database enum values for internal logic
            $courseTypeMap = [
                'e-learning' => 'self_paced',
                'blended' => 'hybrid',
                'classroom' => 'instructor_led',
                'assessment' => 'self_paced'
            ];
            $mappedCourseType = $courseTypeMap[$data['course_type']] ?? 'self_paced';

            // Prepare course data - using all available fields from the database
            $courseData = [
                'client_id' => $clientId,
                'name' => trim($data['name']), // Database column is 'name'
                'description' => trim($data['description'] ?? ''),
                'short_description' => trim($data['short_description'] ?? ''),
                'category_id' => $data['category_id'],
                'subcategory_id' => $data['subcategory_id'],
                'course_type' => $mappedCourseType,
                'course_delivery_type' => $data['course_type'], // Store the original frontend value
                'difficulty_level' => $data['difficulty_level'],
                'course_status' => $data['course_status'] ?? 'active',
                'module_structure' => $data['module_structure'] ?? 'sequential',
                'course_points' => intval($data['course_points'] ?? 0),
                'course_cost' => floatval($data['course_cost'] ?? 0.00),
                'currency' => $data['currency'] ?? null,
                'reassign_course' => $data['reassign_course'] ?? 'no',
                'reassign_days' => ($data['reassign_course'] === 'yes') ? intval($data['reassign_days'] ?? 0) : null,
                'show_in_search' => $data['show_in_search'] ?? 'no',
                'certificate_option' => !empty($data['certificate_option']) ? $data['certificate_option'] : null,
                'duration_hours' => intval($data['duration_hours'] ?? 0),
                'duration_minutes' => intval($data['duration_minutes'] ?? 0),
                'is_self_paced' => isset($data['is_self_paced']) ? 1 : 0,
                'is_featured' => isset($data['is_featured']) ? 1 : 0,
                'is_published' => isset($data['is_published']) ? 1 : 0,
                'thumbnail_image' => $thumbnailPath,
                'banner_image' => $bannerPath,
                'target_audience' => trim($data['target_audience'] ?? ''),
                'learning_objectives' => !empty($data['learning_objectives']) ? (is_array($data['learning_objectives']) ? implode(',', $data['learning_objectives']) : $data['learning_objectives']) : null,
                'tags' => !empty($data['tags']) ? (is_array($data['tags']) ? implode(',', $data['tags']) : $data['tags']) : null,
                'created_by' => $userId
            ];

            // Insert course
            $sql = "INSERT INTO courses (
                client_id, name, description, short_description, category_id, subcategory_id,
                course_type, course_delivery_type, difficulty_level, course_status, module_structure, course_points,
                course_cost, currency, reassign_course, reassign_days, show_in_search,
                certificate_option, duration_hours, duration_minutes,
                is_self_paced, is_featured, is_published, thumbnail_image, banner_image,
                target_audience, learning_objectives, tags, created_by
            ) VALUES (
                :client_id, :name, :description, :short_description, :category_id, :subcategory_id,
                :course_type, :course_delivery_type, :difficulty_level, :course_status, :module_structure, :course_points,
                :course_cost, :currency, :reassign_course, :reassign_days, :show_in_search,
                :certificate_option, :duration_hours, :duration_minutes,
                :is_self_paced, :is_featured, :is_published, :thumbnail_image, :banner_image,
                :target_audience, :learning_objectives, :tags, :created_by
            )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($courseData);
            $courseId = $this->conn->lastInsertId();

            // Create modules if provided
            if (!empty($data['modules']) && is_array($data['modules'])) {
                error_log("Processing " . count($data['modules']) . " modules");
                foreach ($data['modules'] as $moduleData) {
                    $moduleData['created_by'] = $userId;
                    $this->createModule($courseId, $moduleData);
                }
            } else {
                error_log("No modules provided or modules is not an array");
            }

            // Create prerequisites if provided
            if (!empty($data['prerequisites']) && is_array($data['prerequisites'])) {
                foreach ($data['prerequisites'] as $prerequisiteData) {
                    $prerequisiteData['created_by'] = $userId;
                    $this->addPrerequisite($courseId, $prerequisiteData);
                }
            }

            // Create post-requisite content if provided
            error_log("=== CHECKING POST-REQUISITES ===");
            error_log("post_requisites key exists: " . (isset($data['post_requisites']) ? 'YES' : 'NO'));
            if (isset($data['post_requisites'])) {
                error_log("post_requisites value: " . json_encode($data['post_requisites']));
                error_log("post_requisites type: " . gettype($data['post_requisites']));
                error_log("post_requisites is array: " . (is_array($data['post_requisites']) ? 'YES' : 'NO'));
                error_log("post_requisites empty: " . (empty($data['post_requisites']) ? 'YES' : 'NO'));
            }
            
            if (!empty($data['post_requisites']) && is_array($data['post_requisites'])) {
                error_log("Processing post-requisites: " . json_encode($data['post_requisites']));
                foreach ($data['post_requisites'] as $index => $requisiteData) {
                    error_log("Processing requisite $index: " . json_encode($requisiteData));
                    $requisiteData['created_by'] = $userId;
                    $result = $this->addPostRequisite($courseId, $requisiteData);
                    error_log("addPostRequisite result for index $index: " . ($result ? 'SUCCESS' : 'FAILED'));
                }
            } else {
                error_log("No post-requisites found in data or not an array");
                error_log("Data keys: " . json_encode(array_keys($data)));
            }

            $this->conn->commit();
            return ['success' => true, 'course_id' => $courseId];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Course creation error: " . $e->getMessage());
            error_log("Course creation error trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Failed to create course: ' . $e->getMessage()];
        } catch (Error $e) {
            $this->conn->rollBack();
            error_log("Course creation fatal error: " . $e->getMessage());
            error_log("Course creation fatal error trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Fatal error creating course: ' . $e->getMessage()];
        }
    }

    // Helper method to upload files
    public function uploadFile($file, $uploadDir)
    {
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = time() . '_' . basename($file['name']);
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $targetPath;
        }

        return null;
    }

    // Create a module
    private function createModule($courseId, $moduleData)
    {
        error_log("Creating module for courseId: $courseId");
        error_log("Module data: " . json_encode($moduleData));
        
        $sql = "INSERT INTO course_modules (
            course_id, title, description, module_order, is_required, 
            estimated_duration, created_by
        ) VALUES (
            :course_id, :title, :description, :module_order, :is_required,
            :estimated_duration, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => $moduleData['title'] ?? $moduleData['name'] ?? '',
            ':description' => $moduleData['description'],
            ':module_order' => $moduleData['sort_order'] ?? 0, // Map sort_order to module_order
            ':is_required' => isset($moduleData['is_required']) ? 1 : 0,
            ':estimated_duration' => intval($moduleData['estimated_duration'] ?? 0),
            ':created_by' => $moduleData['created_by']
        ]);

        $moduleId = $this->conn->lastInsertId();
        error_log("Created module with ID: $moduleId");

        // Add module content if provided
        if (!empty($moduleData['content'])) {
            foreach ($moduleData['content'] as $content) {
                // Ensure created_by is set for each content item
                $content['created_by'] = $content['created_by'] ?? $moduleData['created_by'];
                $this->addModuleContent($moduleId, $content);
            }
        }

        return $moduleId;
    }

    // Add module content
    private function addModuleContent($moduleId, $contentData)
    {
        // Map frontend fields to database fields
        $contentType = $contentData['type'] ?? $contentData['content_type'] ?? '';
        // Always prefer content_id if present, only use id if content_id is not set
        $contentId = $contentData['content_id'] ?? $contentData['id'] ?? 0;
        $createdBy = $contentData['created_by'] ?? null;
        
        // Skip if required fields are missing
        if (empty($contentType) || empty($contentId) || empty($createdBy)) {
            error_log("Skipping module content - missing required fields. contentType: '$contentType', contentId: '$contentId', createdBy: '$createdBy'");
            error_log("Content data received: " . json_encode($contentData));
            return false;
        }

        $sql = "INSERT INTO course_module_content (
            module_id, content_type, content_id, description,
            content_order, is_required, estimated_duration, created_by
        ) VALUES (
            :module_id, :content_type, :content_id, :description,
            :content_order, :is_required, :estimated_duration, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':module_id' => $moduleId,
            ':content_type' => $contentType,
            ':content_id' => $contentId,
            ':description' => $contentData['description'] ?? '',
            ':content_order' => $contentData['sort_order'] ?? 0, // Map sort_order to content_order
            ':is_required' => isset($contentData['is_required']) ? 1 : 0,
            ':estimated_duration' => intval($contentData['estimated_duration'] ?? 0),
            ':created_by' => $createdBy
        ]);
        
        if ($result) {
            error_log("Successfully inserted module content: moduleId=$moduleId, contentType=$contentType, contentId=$contentId");
        } else {
            error_log("Failed to insert module content: moduleId=$moduleId, contentType=$contentType, contentId=$contentId");
        }
        
        return $result;
    }

    // Add prerequisite
    private function addPrerequisite($courseId, $prerequisiteData)
    {
        // Skip if required fields are missing
        if (empty($prerequisiteData['created_by'])) {
            error_log("Skipping prerequisite - missing required field: created_by");
            return false;
        }

        // Map frontend type to database prerequisite_type
        $frontendType = $prerequisiteData['type'] ?? $prerequisiteData['prerequisite_type'] ?? '';
        $prerequisiteType = $this->mapPrerequisiteType($frontendType);
        // Always use content_id if present, then id, then prerequisite_id
        $prerequisiteId = $prerequisiteData['content_id'] ?? $prerequisiteData['id'] ?? $prerequisiteData['prerequisite_id'] ?? 0;
        
        error_log("Processing prerequisite - frontendType: '$frontendType', mappedType: '$prerequisiteType', id: '$prerequisiteId'");
        error_log("Full prerequisite data: " . json_encode($prerequisiteData));

        $sql = "INSERT INTO course_prerequisites (
            course_id, prerequisite_type, prerequisite_id, 
            prerequisite_description, sort_order, created_by
        ) VALUES (
            :course_id, :prerequisite_type, :prerequisite_id,
            :prerequisite_description, :sort_order, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':course_id' => $courseId,
            ':prerequisite_type' => $prerequisiteType,
            ':prerequisite_id' => $prerequisiteId,
            ':prerequisite_description' => $prerequisiteData['description'] ?? '',
            ':sort_order' => intval($prerequisiteData['sort_order'] ?? 0),
            ':created_by' => $prerequisiteData['created_by']
        ]);
        
        if ($result) {
            error_log("Successfully inserted prerequisite: courseId=$courseId, type=$prerequisiteType, id=$prerequisiteId");
        } else {
            error_log("Failed to insert prerequisite: courseId=$courseId, type=$prerequisiteType, id=$prerequisiteId");
        }
        
        return $result;
    }

    // Map frontend content types to database prerequisite types
    private function mapPrerequisiteType($frontendType)
    {
        // Map VLR content types to their corresponding package table types
        $typeMapping = [
            'assessment' => 'assessment', // References assessment_package.id
            'survey' => 'survey', // References survey_package.id
            'feedback' => 'feedback', // References feedback_package.id
            'course' => 'course', // References courses.id
            'skill' => 'skill',
            'certification' => 'certification',
            'scorm' => 'scorm', // References scorm_packages.id
            'video' => 'video', // References video_package.id
            'audio' => 'audio', // References audio_package.id
            'document' => 'document', // References document_package.id (if exists)
            'interactive' => 'interactive', // References interactive_ai_content_package.id
            'assignment' => 'assignment', // References assignment_package.id
            'external' => 'external', // References external_package.id (if exists)
            'image' => 'image' // References image_package.id
        ];
        
        return $typeMapping[$frontendType] ?? 'assessment'; // Default to assessment if type not found
    }

    // Add post-requisite content (unified method for all types)
    private function addPostRequisite($courseId, $requisiteData)
    {
        error_log("=== ADDING POST-REQUISITE ===");
        error_log("Course ID: $courseId");
        error_log("Requisite data: " . json_encode($requisiteData));
        error_log("Requisite data keys: " . json_encode(array_keys($requisiteData)));
        
        // Skip if required fields are missing
        if (empty($requisiteData['created_by'])) {
            error_log("Skipping post-requisite - missing required field: created_by");
            return false;
        }

        $contentType = $requisiteData['content_type'] ?? $requisiteData['type'] ?? '';
        $contentId = $requisiteData['content_id'] ?? $requisiteData['id'] ?? 0;
        
        error_log("Extracted content_type: '$contentType'");
        error_log("Extracted content_id: '$contentId'");
        
        // Skip if content type or ID is missing
        if (empty($contentType) || empty($contentId)) {
            error_log("Skipping post-requisite - missing content_type or content_id");
            error_log("Post-requisite data: " . json_encode($requisiteData));
            return false;
        }

        // Prepare settings JSON for content-specific data
        $settings = null;
        if ($contentType === 'assessment') {
            $settings = json_encode([
                'time_limit' => intval($requisiteData['time_limit'] ?? 0)
            ]);
        }

        $sql = "INSERT INTO course_post_requisites (
            course_id, content_type, content_id, requisite_type, module_id,
            description, is_required, sort_order, settings, created_by
        ) VALUES (
            :course_id, :content_type, :content_id, :requisite_type, :module_id,
            :description, :is_required, :sort_order, :settings, :created_by
        )";

        error_log("SQL Query: $sql");
        
        $params = [
            ':course_id' => $courseId,
            ':content_type' => $contentType,
            ':content_id' => $contentId,
            ':requisite_type' => $requisiteData['requisite_type'] ?? 'post_course',
            ':module_id' => $requisiteData['module_id'] ?? null,
            ':description' => $requisiteData['description'] ?? '',
            ':is_required' => isset($requisiteData['is_required']) ? 1 : 0,
            ':sort_order' => intval($requisiteData['sort_order'] ?? 0),
            ':settings' => $settings,
            ':created_by' => $requisiteData['created_by']
        ];
        
        error_log("Parameters to execute: " . json_encode($params));

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . json_encode($this->conn->errorInfo()));
            return false;
        }
        
        $result = $stmt->execute($params);
        
        if ($result) {
            error_log("Successfully inserted post-requisite: courseId=$courseId, type=$contentType, id=$contentId");
        } else {
            error_log("Failed to insert post-requisite: courseId=$courseId, type=$contentType, id=$contentId");
        }
        
        return $result;
    }

    // Get all courses for a client
    public function getCourses($clientId = null, $filters = [])
    {
        $sql = "SELECT c.*, 
                       cc.name as category_name, 
                       csc.name as subcategory_name,
                       up.full_name as created_by_name
                FROM courses c
                LEFT JOIN course_categories cc ON c.category_id = cc.id
                LEFT JOIN course_subcategories csc ON c.subcategory_id = csc.id
                LEFT JOIN user_profiles up ON c.created_by = up.id
                WHERE c.is_deleted = 0";
        
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND c.client_id = ?";
            $params[] = $clientId;
        }

        // Apply filters
        if (!empty($filters['category_id'])) {
            $sql .= " AND c.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['course_type'])) {
            $sql .= " AND c.course_type = ?";
            $params[] = $filters['course_type'];
        }

        if (!empty($filters['difficulty_level'])) {
            $sql .= " AND c.difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
        }

        if (!empty($filters['is_published'])) {
            $sql .= " AND c.is_published = ?";
            $params[] = $filters['is_published'];
        }

        // New filters for course management
        if (!empty($filters['course_status'])) {
            if ($filters['course_status'] === 'published') {
                $sql .= " AND c.is_published = 1";
            } elseif ($filters['course_status'] === 'draft') {
                $sql .= " AND c.is_published = 0";
            } elseif ($filters['course_status'] === 'archived') {
                $sql .= " AND c.is_deleted = 1";
            } elseif ($filters['course_status'] === 'inactive') {
                $sql .= " AND c.course_status = 'inactive'";
            } elseif ($filters['course_status'] === 'active') {
                $sql .= " AND c.course_status = 'active'";
            }
        }

        if (!empty($filters['category'])) {
            $sql .= " AND cc.name = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['subcategory'])) {
            $sql .= " AND csc.name = ?";
            $params[] = $filters['subcategory'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR c.description LIKE ? OR c.short_description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY c.created_at DESC";

        // Add pagination (inject as integer literals, not placeholders)
        if (isset($filters['limit']) && is_numeric($filters['limit'])) {
            $limit = (int)$filters['limit'];
            $offset = isset($filters['offset']) && is_numeric($filters['offset']) ? (int)$filters['offset'] : 0;
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add course applicability flag to each course
        foreach ($courses as &$course) {
            $course['has_applicability_rules'] = $this->hasCourseApplicabilityRules($course['id']);
        }
        unset($course);
        
        return $courses;
    }

    /**
     * Get total count of courses with filters (for pagination)
     */
    public function getCoursesCount($clientId = null, $filters = [])
    {
        $sql = "SELECT COUNT(*) as total
                FROM courses c
                LEFT JOIN course_categories cc ON c.category_id = cc.id
                LEFT JOIN course_subcategories csc ON c.subcategory_id = csc.id
                WHERE c.is_deleted = 0";
        
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND c.client_id = ?";
            $params[] = $clientId;
        }

        // Apply filters (same as getCourses but without pagination)
        if (!empty($filters['category_id'])) {
            $sql .= " AND c.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['course_type'])) {
            $sql .= " AND c.course_type = ?";
            $params[] = $filters['course_type'];
        }

        if (!empty($filters['difficulty_level'])) {
            $sql .= " AND c.difficulty_level = ?";
            $params[] = $filters['difficulty_level'];
        }

        if (!empty($filters['is_published'])) {
            $sql .= " AND c.is_published = ?";
            $params[] = $filters['is_published'];
        }

        // New filters for course management
        if (!empty($filters['course_status'])) {
            if ($filters['course_status'] === 'published') {
                $sql .= " AND c.is_published = 1";
            } elseif ($filters['course_status'] === 'draft') {
                $sql .= " AND c.is_published = 0";
            } elseif ($filters['course_status'] === 'archived') {
                $sql .= " AND c.is_deleted = 1";
            } elseif ($filters['course_status'] === 'inactive') {
                $sql .= " AND c.course_status = 'inactive'";
            } elseif ($filters['course_status'] === 'active') {
                $sql .= " AND c.course_status = 'active'";
            }
        }

        if (!empty($filters['category'])) {
            $sql .= " AND cc.name = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['subcategory'])) {
            $sql .= " AND csc.name = ?";
            $params[] = $filters['subcategory'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.name LIKE ? OR c.description LIKE ? OR c.short_description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // Get course by ID with all related data
    public function getCourseById($courseId, $clientId = null, $userId = null)
    {
        $sql = "SELECT c.*, 
                       cc.name as category_name, 
                       csc.name as subcategory_name,
                       up.full_name as created_by_name,
                       c.course_delivery_type as course_type
                FROM courses c
                LEFT JOIN course_categories cc ON c.category_id = cc.id
                LEFT JOIN course_subcategories csc ON c.subcategory_id = csc.id
                LEFT JOIN user_profiles up ON c.created_by = up.id
                WHERE c.id = ? AND (c.deleted_at IS NULL OR c.deleted_at = '0000-00-00 00:00:00')";
        
        $params = [$courseId];

        if ($clientId !== null) {
            $sql .= " AND c.client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($course) {
            // Get modules
            $course['modules'] = $this->getCourseModules($courseId, $userId);
            
            // Get prerequisites
            $course['prerequisites'] = $this->getCoursePrerequisites($courseId);
            
            // Get post-requisites (unified)
            $course['post_requisites'] = $this->getCoursePostRequisites($courseId);

            // Ensure learning_objectives and tags are arrays for frontend prefill
            if (isset($course['learning_objectives'])) {
                $decoded = json_decode($course['learning_objectives'], true);
                $course['learning_objectives'] = (is_array($decoded)) ? $decoded : [];
            } else {
                $course['learning_objectives'] = [];
            }
            if (isset($course['tags'])) {
                $decoded = json_decode($course['tags'], true);
                $course['tags'] = (is_array($decoded)) ? $decoded : [];
            } else {
                $course['tags'] = [];
            }
        }

        return $course;
    }

    // Get course modules
    public function getCourseModules($courseId, $userId = null)
    {
        $sql = "SELECT cm.*, 
                       0 as module_progress,
                       'not_started' as module_status
                FROM course_modules cm
                WHERE cm.course_id = :course_id AND (cm.deleted_at IS NULL OR cm.deleted_at = '0000-00-00 00:00:00') 
                ORDER BY cm.module_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':course_id' => $courseId]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get module content for each module
        foreach ($modules as &$module) {
            $module['content'] = $this->getModuleContent($module['id']);
        }

        return $modules;
    }

    // Get module content
    private function getModuleContent($moduleId)
    {
        $sql = "SELECT * FROM course_module_content 
                WHERE module_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') 
                ORDER BY content_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$moduleId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Enrich each content item with launch/file URLs and titles as per content type
        foreach ($rows as &$row) {
            // Normalize type for frontend
            if (!isset($row['type']) && isset($row['content_type'])) {
                $row['type'] = $row['content_type'];
            }

            $contentId = $row['content_id'] ?? null;
            $type = $row['content_type'] ?? '';
            if (!$contentId) {
                continue;
            }

            try {
                switch ($type) {
                    case 'scorm': {
                        $q = $this->conn->prepare("SELECT title, launch_path, zip_file FROM scorm_packages WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            if (!empty($data['launch_path'])) {
                                $launch = $data['launch_path'];
                                // Normalize to a web path that actually exists
                                $isAbsolute = preg_match('#^(https?://|/)#i', $launch);
                                if (!$isAbsolute) {
                                    // If only a file is stored, rebuild with extracted folder from zip_file
                                    $folder = !empty($data['zip_file']) ? pathinfo($data['zip_file'], PATHINFO_FILENAME) : '';
                                    if (!empty($folder)) {
                                        $candidate = 'uploads/scorm/' . $folder . '/' . ltrim($launch, '/');
                                    } else {
                                        $candidate = 'uploads/scorm/' . ltrim($launch, '/');
                                    }
                                    // If file exists, use it; else fallback to raw
                                    if (file_exists($candidate)) {
                                        $launch = $candidate;
                                    }
                                }
                                $row['scorm_launch_path'] = $launch;
                            }
                        }
                        break;
                    }
                    case 'non_scorm': {
                        $q = $this->conn->prepare("SELECT title, content_url, launch_file FROM non_scorm_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            $row['non_scorm_launch_path'] = $data['content_url'] ?: ($data['launch_file'] ?? null);
                        }
                        break;
                    }
                    case 'interactive': {
                        $q = $this->conn->prepare("SELECT title, content_url, content_file, embed_code FROM interactive_ai_content_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            $row['interactive_launch_url'] = $data['content_url'] ?: ($data['content_file'] ?? null);
                        }
                        break;
                    }
                    case 'video': {
                        $q = $this->conn->prepare("SELECT title, video_file FROM video_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            if (!empty($data['video_file'])) {
                                $file = $data['video_file'];
                                $row['video_file_path'] = (preg_match('#^(https?://|/)#i', $file) || strpos($file, 'uploads/') === 0)
                                    ? $file
                                    : ('uploads/video/' . $file);
                            }
                        }
                        break;
                    }
                    case 'audio': {
                        $q = $this->conn->prepare("SELECT title, audio_file FROM audio_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            if (!empty($data['audio_file'])) {
                                $file = $data['audio_file'];
                                $row['audio_file_path'] = (preg_match('#^(https?://|/)#i', $file) || strpos($file, 'uploads/') === 0)
                                    ? $file
                                    : ('uploads/audio/' . $file);
                            }
                        }
                        break;
                    }
                    case 'document': {
                        $q = $this->conn->prepare("SELECT title, word_excel_ppt_file, ebook_manual_file, research_file FROM documents WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            $file = $data['word_excel_ppt_file'] ?: ($data['ebook_manual_file'] ?: ($data['research_file'] ?? null));
                            if ($file) {
                                $row['document_file_path'] = (preg_match('#^(https?://|/)#i', $file) || strpos($file, 'uploads/') === 0)
                                    ? $file
                                    : ('uploads/documents/' . $file);
                            }
                        }
                        break;
                    }
                    case 'image': {
                        $q = $this->conn->prepare("SELECT title, image_file FROM image_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            if (!empty($data['image_file'])) {
                                $file = $data['image_file'];
                                $row['image_file_path'] = (preg_match('#^(https?://|/)#i', $file) || strpos($file, 'uploads/') === 0)
                                    ? $file
                                    : ('uploads/image/' . $file);
                            }
                        }
                        break;
                    }
                    case 'external': {
                        $q = $this->conn->prepare("SELECT title, course_url, video_url, article_url, audio_url, audio_source, audio_file FROM external_content WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            // Handle different content types and their URLs
                            $url = null;
                            
                            // For audio content, check if it's uploaded file or external URL
                            if ($data['audio_source'] === 'upload' && !empty($data['audio_file'])) {
                                // Audio file was uploaded - construct file path
                                $audioFile = $data['audio_file'];
                                if (preg_match('#^(https?://|/)#i', $audioFile) || strpos($audioFile, 'uploads/') === 0) {
                                    $url = $audioFile;
                                } else {
                                    $url = 'uploads/external/audio/' . $audioFile;
                                }
                            } else {
                                // Check other URL fields in order of priority
                                $url = $data['course_url'] ?: ($data['video_url'] ?: ($data['article_url'] ?: ($data['audio_url'] ?? null)));
                            }
                            
                            if ($url) {
                                $row['external_content_url'] = $url;
                            }
                        }
                        break;
                    }
                    case 'assessment': {
                        $q = $this->conn->prepare("SELECT title, num_attempts, time_limit, passing_percentage FROM assessment_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data) {
                            // Override title with actual package title
                            if (!empty($data['title'])) {
                                $row['title'] = $data['title'];
                            }
                            
                            $row['assessment_title'] = $data['title'];
                            $row['num_attempts'] = $data['num_attempts'];
                            $row['time_limit'] = $data['time_limit'];
                            $row['passing_percentage'] = $data['passing_percentage'];
                        }
                        break;
                    }
                    case 'survey': {
                        $q = $this->conn->prepare("SELECT title FROM survey_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data && !empty($data['title'])) {
                            // Override title with actual package title
                            $row['title'] = $data['title'];
                        }
                        break;
                    }
                    case 'feedback': {
                        $q = $this->conn->prepare("SELECT title FROM feedback_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data && !empty($data['title'])) {
                            // Override title with actual package title
                            $row['title'] = $data['title'];
                        }
                        break;
                    }
                    case 'assignment': {
                        $q = $this->conn->prepare("SELECT title FROM assignment_package WHERE id = ? AND (is_deleted = 0 OR is_deleted IS NULL)");
                        $q->execute([$contentId]);
                        $data = $q->fetch(PDO::FETCH_ASSOC);
                        if ($data && !empty($data['title'])) {
                            // Override title with actual package title
                            $row['title'] = $data['title'];
                        }
                        break;
                    }
                    default:
                        // No enrichment needed
                        break;
                }
            } catch (Throwable $t) {
                // ignore enrichment errors
            }
        }
        
        return $rows;
    }

    // Get course prerequisites
    public function getCoursePrerequisites($courseId)
    {
        $sql = "SELECT cp.*, 
            c.name as course_title,
            sp.title as scorm_title,
            ap.title as assessment_title,
            fp.title as feedback_title,
            ep.title as external_title,
            d.title as document_title,
            asg.title as assignment_title,
            aud.title as audio_title,
            vid.title as video_title,
            img.title as image_title,
            iac.title as interactive_title,
            nsp.title as non_scorm_title,
            cp.prerequisite_type
                FROM course_prerequisites cp
                LEFT JOIN courses c ON cp.prerequisite_id = c.id AND cp.prerequisite_type = 'course'
        LEFT JOIN scorm_packages sp ON cp.prerequisite_id = sp.id AND cp.prerequisite_type = 'scorm'
        LEFT JOIN assessment_package ap ON cp.prerequisite_id = ap.id AND cp.prerequisite_type = 'assessment'
        LEFT JOIN feedback_package fp ON cp.prerequisite_id = fp.id AND cp.prerequisite_type = 'feedback'
        LEFT JOIN external_content ep ON cp.prerequisite_id = ep.id AND cp.prerequisite_type = 'external'
        LEFT JOIN documents d ON cp.prerequisite_id = d.id AND cp.prerequisite_type = 'document'
        LEFT JOIN assignment_package asg ON cp.prerequisite_id = asg.id AND cp.prerequisite_type = 'assignment'
        LEFT JOIN audio_package aud ON cp.prerequisite_id = aud.id AND cp.prerequisite_type = 'audio'
        LEFT JOIN video_package vid ON cp.prerequisite_id = vid.id AND cp.prerequisite_type = 'video'
        LEFT JOIN image_package img ON cp.prerequisite_id = img.id AND cp.prerequisite_type = 'image'
        LEFT JOIN interactive_ai_content_package iac ON cp.prerequisite_id = iac.id AND cp.prerequisite_type = 'interactive'
        LEFT JOIN non_scorm_package nsp ON cp.prerequisite_id = nsp.id AND cp.prerequisite_type = 'non_scorm'
                WHERE cp.course_id = ? AND (cp.deleted_at IS NULL OR cp.deleted_at = '0000-00-00 00:00:00')
                ORDER BY cp.sort_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Add a generic 'title' and 'type' field for frontend rendering
        foreach ($results as &$row) {
            switch ($row['prerequisite_type']) {
                case 'course':
                    $row['title'] = $row['course_title'];
                    $row['type'] = 'course';
                    break;
                case 'scorm':
                    $row['title'] = $row['scorm_title'];
                    $row['type'] = 'scorm';
                    break;
                case 'assessment':
                    $row['title'] = $row['assessment_title'];
                    $row['type'] = 'assessment';
                    break;
                case 'feedback':
                    $row['title'] = $row['feedback_title'];
                    $row['type'] = 'feedback';
                    break;
                case 'external':
                    $row['title'] = $row['external_title'];
                    $row['type'] = 'external';
                    break;
                case 'document':
                    $row['title'] = $row['document_title'];
                    $row['type'] = 'document';
                    break;
                case 'assignment':
                    $row['title'] = $row['assignment_title'];
                    $row['type'] = 'assignment';
                    break;
                case 'audio':
                    $row['title'] = $row['audio_title'];
                    $row['type'] = 'audio';
                    break;
                case 'video':
                    $row['title'] = $row['video_title'];
                    $row['type'] = 'video';
                    break;
                case 'image':
                    $row['title'] = $row['image_title'];
                    $row['type'] = 'image';
                    break;
                case 'interactive':
                    $row['title'] = $row['interactive_title'];
                    $row['type'] = 'interactive';
                    break;
                case 'non_scorm':
                    $row['title'] = $row['non_scorm_title'];
                    $row['type'] = 'non_scorm';
                    break;
                default:
                    $row['title'] = null;
                    $row['type'] = $row['prerequisite_type'];
            }
            // Titles now only come from package tables, no fallback to prerequisite_name
            if (empty($row['title'])) {
                $row['title'] = 'Untitled';
            }
            if (empty($row['type'])) {
                $row['type'] = 'unknown';
            }
            
            // Set prerequisite_course_title for backward compatibility with existing views
            $row['prerequisite_course_title'] = $row['title'];
        }
        return $results;
    }

    // Get course post-requisites
    public function getCoursePostRequisites($courseId)
    {
        try {
            // First try the unified table
            $sql = "SELECT cpr.*, 
                           CASE 
                               WHEN cpr.content_type = 'assessment' THEN ap.title
                               WHEN cpr.content_type = 'feedback' THEN fp.title
                               WHEN cpr.content_type = 'survey' THEN sp.title
                               WHEN cpr.content_type = 'assignment' THEN ap2.title
                           END as content_title
                    FROM course_post_requisites cpr
                    LEFT JOIN assessment_package ap ON cpr.content_type = 'assessment' AND cpr.content_id = ap.id
                    LEFT JOIN feedback_package fp ON cpr.content_type = 'feedback' AND cpr.content_id = fp.id
                    LEFT JOIN survey_package sp ON cpr.content_type = 'survey' AND cpr.content_id = sp.id
                    LEFT JOIN assignment_package ap2 ON cpr.content_type = 'assignment' AND cpr.content_id = ap2.id
                    WHERE cpr.course_id = ? AND cpr.is_deleted = 0
                    ORDER BY cpr.sort_order ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // If unified table doesn't exist, fall back to old tables
            error_log("Unified post-requisites table not found, falling back to old tables: " . $e->getMessage());
            
            $postRequisites = [];
            
            // Get assessments
            try {
                $sql = "SELECT ca.*, ap.title as content_title, 'assessment' as content_type
                        FROM course_assessments ca
                        LEFT JOIN assessment_package ap ON ca.assessment_id = ap.id
                        WHERE ca.course_id = ? AND (ca.deleted_at IS NULL OR ca.deleted_at = '0000-00-00 00:00:00')
                        ORDER BY ca.assessment_order ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$courseId]);
                $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $postRequisites = array_merge($postRequisites, $assessments);
            } catch (PDOException $e) {
                error_log("Error fetching assessments: " . $e->getMessage());
            }
            
            // Get feedback
            try {
                $sql = "SELECT cf.*, fp.title as content_title, 'feedback' as content_type
                        FROM course_feedback cf
                        LEFT JOIN feedback_package fp ON cf.feedback_id = fp.id
                        WHERE cf.course_id = ? AND (cf.deleted_at IS NULL OR cf.deleted_at = '0000-00-00 00:00:00')
                        ORDER BY cf.feedback_order ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$courseId]);
                $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $postRequisites = array_merge($postRequisites, $feedback);
            } catch (PDOException $e) {
                error_log("Error fetching feedback: " . $e->getMessage());
            }
            
            // Get surveys
            try {
                $sql = "SELECT cs.*, sp.title as content_title, 'survey' as content_type
                        FROM course_surveys cs
                        LEFT JOIN survey_package sp ON cs.survey_id = sp.id
                        WHERE cs.course_id = ? AND (cs.deleted_at IS NULL OR cs.deleted_at = '0000-00-00 00:00:00')
                        ORDER BY cs.survey_order ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$courseId]);
                $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $postRequisites = array_merge($postRequisites, $surveys);
            } catch (PDOException $e) {
                error_log("Error fetching surveys: " . $e->getMessage());
            }
            
            return $postRequisites;
        }
    }

    // Update course
    public function updateCourse($courseId, $data, $clientId = null)
    {
        // Decode JSON fields if they are strings (same as createCourse)
        foreach (['modules', 'prerequisites', 'post_requisites'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                } else {
                    error_log("[ERROR] Failed to decode $key in updateCourse: " . json_last_error_msg());
                    $data[$key] = [];
                }
            }
        }
        try {
            $this->conn->beginTransaction();

            // Map course_type to database enum values for internal logic (same as createCourse)
            $courseTypeMap = [
                'e-learning' => 'self_paced',
                'blended' => 'hybrid',
                'classroom' => 'instructor_led',
                'assessment' => 'self_paced'
            ];
            $mappedCourseType = $courseTypeMap[$data['course_type']] ?? 'self_paced';

            $sql = "UPDATE courses SET
                name = :name, description = :description, short_description = :short_description,
                category_id = :category_id, subcategory_id = :subcategory_id, course_type = :course_type,
                course_delivery_type = :course_delivery_type, difficulty_level = :difficulty_level, course_status = :course_status, module_structure = :module_structure,
                course_points = :course_points, course_cost = :course_cost, currency = :currency,
                reassign_course = :reassign_course, reassign_days = :reassign_days, show_in_search = :show_in_search,
                certificate_option = :certificate_option, duration_hours = :duration_hours, duration_minutes = :duration_minutes,
                is_self_paced = :is_self_paced, is_featured = :is_featured, is_published = :is_published,
                target_audience = :target_audience, learning_objectives = :learning_objectives, tags = :tags,
                updated_by = :updated_by, updated_at = CURRENT_TIMESTAMP";

            // Add thumbnail and banner image if present
            if (array_key_exists('thumbnail_image', $data)) {
                $sql .= ", thumbnail_image = :thumbnail_image";
            }
            if (array_key_exists('banner_image', $data)) {
                $sql .= ", banner_image = :banner_image";
            }
            $sql .= " WHERE id = :id AND (client_id = :client_id OR :client_id IS NULL)";

            $params = [
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':short_description' => $data['short_description'],
                ':category_id' => $data['category_id'],
                ':subcategory_id' => $data['subcategory_id'],
                ':course_type' => $mappedCourseType,
                ':course_delivery_type' => $data['course_type'], // Store the original frontend value
                ':difficulty_level' => $data['difficulty_level'],
                ':course_status' => $data['course_status'] ?? 'active',
                ':module_structure' => $data['module_structure'] ?? 'sequential',
                ':course_points' => intval($data['course_points'] ?? 0),
                ':course_cost' => floatval($data['course_cost'] ?? 0.00),
                ':currency' => $data['currency'] ?? null,
                ':reassign_course' => $data['reassign_course'] ?? 'no',
                ':reassign_days' => ($data['reassign_course'] === 'yes') ? intval($data['reassign_days'] ?? 0) : null,
                ':show_in_search' => $data['show_in_search'] ?? 'no',
                ':certificate_option' => !empty($data['certificate_option']) ? $data['certificate_option'] : null,
                ':duration_hours' => intval($data['duration_hours'] ?? 0),
                ':duration_minutes' => intval($data['duration_minutes'] ?? 0),
                ':is_self_paced' => isset($data['is_self_paced']) ? 1 : 0,
                ':is_featured' => isset($data['is_featured']) ? 1 : 0,
                ':is_published' => isset($data['is_published']) ? 1 : 0,
                ':target_audience' => trim($data['target_audience'] ?? ''),
                ':learning_objectives' => !empty($data['learning_objectives']) ? (is_array($data['learning_objectives']) ? implode(',', $data['learning_objectives']) : $data['learning_objectives']) : null,
                ':tags' => !empty($data['tags']) ? (is_array($data['tags']) ? implode(',', $data['tags']) : $data['tags']) : null,
                ':updated_by' => $data['updated_by'],
                ':id' => $courseId,
                ':client_id' => $clientId
            ];
            if (array_key_exists('thumbnail_image', $data)) {
                $params[':thumbnail_image'] = $data['thumbnail_image'];
            }
            if (array_key_exists('banner_image', $data)) {
                $params[':banner_image'] = $data['banner_image'];
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            // --- FULL UPDATE OF RELATED TABLES ---
            // 1. Delete all existing modules, module content, prerequisites, post-requisites for this course
            $this->deleteCourseModules($courseId);
            $this->deleteCoursePrerequisites($courseId);
            $this->deleteCoursePostRequisites($courseId);

            // 2. Re-insert modules, module content, prerequisites, post-requisites from $data
            $userId = $data['updated_by'] ?? null;
            // Modules
            if (!empty($data['modules']) && is_array($data['modules'])) {
                foreach ($data['modules'] as $moduleData) {
                    $moduleData['created_by'] = $userId;
                    $this->createModule($courseId, $moduleData);
                }
            }
            // Prerequisites
            if (!empty($data['prerequisites']) && is_array($data['prerequisites'])) {
                foreach ($data['prerequisites'] as $prerequisiteData) {
                    $prerequisiteData['created_by'] = $userId;
                    $this->addPrerequisite($courseId, $prerequisiteData);
                }
            }
            // Post-requisites
            if (!empty($data['post_requisites']) && is_array($data['post_requisites'])) {
                foreach ($data['post_requisites'] as $requisiteData) {
                    $requisiteData['created_by'] = $userId;
                    $this->addPostRequisite($courseId, $requisiteData);
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Course update error: " . $e->getMessage());
            return false;
        }
    }

    // Helper to delete all modules and their content for a course
    private function deleteCourseModules($courseId) {
        // Delete module content first
        $sql = "DELETE FROM course_module_content WHERE module_id IN (SELECT id FROM course_modules WHERE course_id = ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
        // Delete modules
        $sql = "DELETE FROM course_modules WHERE course_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
    }
    // Helper to delete all prerequisites for a course
    private function deleteCoursePrerequisites($courseId) {
        $sql = "DELETE FROM course_prerequisites WHERE course_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
    }
    // Helper to delete all post-requisites for a course
    private function deleteCoursePostRequisites($courseId) {
        $sql = "DELETE FROM course_post_requisites WHERE course_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
    }

    // Delete course (soft delete)
    public function deleteCourse($courseId, $clientId = null)
    {
        $sql = "UPDATE courses SET is_deleted = 1 WHERE id = ?";
        $params = [$courseId];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Get available VLR content for course creation
    public function getAvailableVLRContent($clientId)
    {
        try {
            $vlrContent = [];

            // SCORM
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'scorm' as type FROM scorm_packages WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['scorm'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['scorm']) . " SCORM packages");
            } catch (PDOException $e) {
                error_log("Error fetching SCORM packages: " . $e->getMessage());
                $vlrContent['scorm'] = [];
            }

            // External
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'external' as type FROM external_content WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['external'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['external']) . " External content");
            } catch (PDOException $e) {
                error_log("Error fetching External content: " . $e->getMessage());
                $vlrContent['external'] = [];
            }

            // Document
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'document' as type FROM documents WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['documents']) . " Documents");
            } catch (PDOException $e) {
                error_log("Error fetching Documents: " . $e->getMessage());
                $vlrContent['documents'] = [];
            }

            // Assessment
            try {
                $sql = "SELECT id, title, '' as description, 'assessment' as type FROM assessment_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['assessment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['assessment']) . " Assessments");
            } catch (PDOException $e) {
                error_log("Error fetching Assessments: " . $e->getMessage());
                $vlrContent['assessment'] = [];
            }

            // Audio
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'audio' as type FROM audio_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['audio'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['audio']) . " Audio packages");
            } catch (PDOException $e) {
                error_log("Error fetching Audio packages: " . $e->getMessage());
                $vlrContent['audio'] = [];
            }

            // Video
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'video' as type FROM video_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['video'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['video']) . " Video packages");
            } catch (PDOException $e) {
                error_log("Error fetching Video packages: " . $e->getMessage());
                $vlrContent['video'] = [];
            }

            // Image
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'image' as type FROM image_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['images']) . " Image packages");
            } catch (PDOException $e) {
                error_log("Error fetching Image packages: " . $e->getMessage());
                $vlrContent['images'] = [];
            }

            // Interactive
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'interactive' as type FROM interactive_ai_content_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['interactive'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['interactive']) . " Interactive packages");
            } catch (PDOException $e) {
                error_log("Error fetching Interactive packages: " . $e->getMessage());
                $vlrContent['interactive'] = [];
            }

            // Non-SCORM
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'non_scorm' as type FROM non_scorm_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['non_scorm'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['non_scorm']) . " Non-SCORM packages");
            } catch (PDOException $e) {
                error_log("Error fetching Non-SCORM packages: " . $e->getMessage());
                $vlrContent['non_scorm'] = [];
            }

            // Assignment
            try {
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'assignment' as type FROM assignment_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['assignments']) . " Assignment packages");
            } catch (PDOException $e) {
                error_log("Error fetching Assignment packages: " . $e->getMessage());
                $vlrContent['assignments'] = [];
            }

            // Survey
            try {
                $sql = "SELECT id, title, '' as description, 'survey' as type FROM survey_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['surveys'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['surveys']) . " Survey packages");
            } catch (PDOException $e) {
                error_log("Error fetching Survey packages: " . $e->getMessage());
                $vlrContent['surveys'] = [];
            }

            // Feedback
            try {
                $sql = "SELECT id, title, '' as description, 'feedback' as type FROM feedback_package WHERE client_id = ? AND is_deleted = 0";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$clientId]);
                $vlrContent['feedback'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                error_log("Fetched " . count($vlrContent['feedback']) . " Feedback packages");
            } catch (PDOException $e) {
                error_log("Error fetching Feedback packages: " . $e->getMessage());
                $vlrContent['feedback'] = [];
            }

            error_log('[DEBUG] $vlrContent before return: ' . print_r($vlrContent, true));
            return $vlrContent;

        } catch (PDOException $e) {
            error_log("Error getting VLR content: " . $e->getMessage());
            return [];
        }
    }

    // Get course statistics
    public function getCourseStats($clientId = null)
    {
        $sql = "SELECT 
                    COUNT(*) as total_courses,
                    SUM(CASE WHEN is_published = 1 THEN 1 ELSE 0 END) as published_courses,
                    SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_courses,
                    SUM(CASE WHEN course_type = 'e-learning' THEN 1 ELSE 0 END) as elearning_courses,
                    SUM(CASE WHEN course_type = 'classroom' THEN 1 ELSE 0 END) as classroom_courses,
                    SUM(CASE WHEN course_type = 'blended' THEN 1 ELSE 0 END) as blended_courses,
                    SUM(CASE WHEN course_type = 'assessment' THEN 1 ELSE 0 END) as assessment_courses
                FROM courses 
                WHERE is_deleted = 0";
        
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all courses for a client (enhanced version)
     */
    public function getAllCourses($clientId) {
        try {
            $sql = "SELECT 
                        c.*,
                        cc.name as category_name,
                        csc.name as subcategory_name,
                        (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as module_count,
                        0 as enrollment_count,
                        0 as completion_rate
                    FROM courses c
                    LEFT JOIN course_categories cc ON c.category_id = cc.id
                    LEFT JOIN course_subcategories csc ON c.subcategory_id = csc.id
                    WHERE c.client_id = ? AND c.is_deleted = 0
                    ORDER BY c.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add course applicability flag to each course
            foreach ($courses as &$course) {
                $course['has_applicability_rules'] = $this->hasCourseApplicabilityRules($course['id']);
            }
            unset($course);
            
            return $courses;
        } catch (PDOException $e) {
            error_log("Error getting all courses: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update course status
     */
    public function updateCourseStatus($courseId, $status, $clientId) {
        try {
            $sql = "UPDATE courses SET course_status = ?, updated_at = NOW() WHERE id = ? AND client_id = ? AND is_deleted = 0";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$status, $courseId, $clientId]);
        } catch (PDOException $e) {
            error_log("Error updating course status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get course analytics
     */
    public function getCourseAnalytics($courseId) {
        try {
            $analytics = [];
            
            // Enrollment statistics
            $sql = "SELECT 
                        COUNT(*) as total_enrollments,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_enrollments,
                        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_enrollments,
                        ROUND(AVG(completion_percentage), 1) as avg_completion_rate,
                        ROUND(AVG(CASE WHEN status = 'completed' THEN completion_time ELSE NULL END), 1) as avg_completion_time
                    FROM (SELECT 1 as dummy) d
                    WHERE 1 = 0";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $analytics['enrollment_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Assessment statistics
            $sql = "SELECT 
                        COUNT(*) as total_assessments,
                        ROUND(AVG(score), 1) as avg_score,
                        COUNT(CASE WHEN score >= 70 THEN 1 END) as passed_assessments
                    FROM assessment_results ar
                    JOIN course_assessments ca ON ar.assessment_id = ca.assessment_id
                    WHERE ca.course_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $analytics['assessment_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Feedback statistics
            $sql = "SELECT 
                        COUNT(*) as total_responses,
                        ROUND(AVG(rating), 1) as avg_rating
                    FROM feedback_responses fr
                    JOIN course_feedback cf ON fr.feedback_id = cf.feedback_id
                    WHERE cf.course_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$courseId]);
            $analytics['feedback_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $analytics;
        } catch (PDOException $e) {
            error_log("Error getting course analytics: " . $e->getMessage());
            return [];
        }
    }

    // Get currencies from countries table
    public function getCurrencies()
    {
        $sql = "SELECT DISTINCT currency, currency_symbol 
                FROM countries 
                WHERE currency IS NOT NULL AND currency != '' 
                ORDER BY currency";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calculate real-time module progress
    public function getModuleProgress($moduleId, $userId)
    {
        $sql = "SELECT 
                    cm.id as module_id,
                    cm.title as module_title,
                    cm.estimated_duration,
                    0 as completion_percentage,
                    'not_started' as status,
                    0 as completion_time
                FROM course_modules cm
                WHERE cm.id = :module_id AND (cm.deleted_at IS NULL OR cm.deleted_at = '0000-00-00 00:00:00')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':module_id' => $moduleId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Calculate content completion progress for a module
    public function getModuleContentProgress($moduleId, $userId, $clientId = null)
    {
        // Get all content items in the module
        $contentItems = $this->getModuleContent($moduleId);
        $totalItems = count($contentItems);
        $completedItems = 0;
        $totalProgress = 0;
        
        if ($totalItems === 0) {
            return [
                'total_items' => 0,
                'completed_items' => 0,
                'progress_percentage' => 0,
                'content_progress' => []
            ];
        }
        
        foreach ($contentItems as &$content) {
            $contentId = $content['content_id'];
            $contentType = $content['content_type'];
            $progress = 0;
            $status = 'not_started';
            
            // Calculate progress based on content type
            switch ($contentType) {
                case 'assessment':
                    $progress = $this->getAssessmentProgress($contentId, $userId, $clientId);
                    break;
                case 'assignment':
                    $progress = $this->getAssignmentProgress($contentId, $userId, $clientId);
                    break;
                case 'scorm':
                    // For SCORM, use the course_module_content.id, not the content_id
                    $progress = $this->getScormProgress($content['id'], $userId, $clientId);
                    break;
                case 'document':
                    $progress = $this->getDocumentProgress($content['id'], $userId, $clientId);
                    break;
                case 'video':
                case 'audio':
                case 'image':
                case 'interactive':
                case 'non_scorm':
                case 'external':
                    $progress = $this->getContentProgress($contentId, $userId, $clientId);
                    break;
                default:
                    $progress = 0;
            }
            
            // Determine if content is completed (progress >= 100%)
            if ($progress >= 100) {
                $completedItems++;
                $status = 'completed';
            } elseif ($progress > 0) {
                $status = 'in_progress';
            }
            
            $content['progress'] = $progress;
            $content['status'] = $status;
            $totalProgress += $progress;
        }
        
        $overallProgress = $totalItems > 0 ? round($totalProgress / $totalItems) : 0;
        
        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'progress_percentage' => $overallProgress,
            'content_progress' => $contentItems
        ];
    }
    
    // Get assessment progress
    private function getAssessmentProgress($assessmentId, $userId, $clientId)
    {
        try {
            require_once 'models/AssessmentPlayerModel.php';
            $assessmentModel = new AssessmentPlayerModel();
            $results = $assessmentModel->getUserAssessmentResults($assessmentId, $userId, $clientId);
            
            if ($results && isset($results['passed'])) {
                return $results['passed'] ? 100 : 0;
            }
            
            // Check if user has attempted the assessment
            $attempts = $assessmentModel->getUserCompletedAssessmentAttempts($assessmentId, $userId, $clientId);
            return !empty($attempts) ? 50 : 0; // 50% if attempted but not completed
        } catch (Exception $e) {
            error_log("Error getting assessment progress: " . $e->getMessage());
            return 0;
        }
    }
    
    private function getAssignmentProgress($assignmentId, $userId, $clientId)
    {
        try {
            require_once 'models/AssignmentSubmissionModel.php';
            $assignmentModel = new AssignmentSubmissionModel();
            
            // Get the course ID from the course_module_content table
            $stmt = $this->conn->prepare("
                SELECT cm.course_id 
                FROM course_module_content cmc
                JOIN course_modules cm ON cmc.module_id = cm.id
                WHERE cmc.content_id = ? AND cmc.content_type = 'assignment'
            ");
            $stmt->execute([$assignmentId]);
            $courseId = $stmt->fetchColumn();
            
            if (!$courseId) {
                error_log("Error getting assignment progress: Course ID not found for assignment ID: $assignmentId");
                return 0;
            }
            
            // Check if user has submitted the assignment
            $hasSubmitted = $assignmentModel->hasUserSubmittedAssignment($courseId, $userId, $assignmentId);
            
            return $hasSubmitted ? 100 : 0; // 100% if submitted, 0% if not
        } catch (Exception $e) {
            error_log("Error getting assignment progress: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get SCORM progress
    private function getScormProgress($contentId, $userId, $clientId)
    {
        try {
            // The contentId parameter is the course_module_content.id
            // We need to find progress records that match this content_id
            $sql = "SELECT lesson_status, lesson_location, score_raw, score_max 
                    FROM scorm_progress 
                    WHERE content_id = ? AND user_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId, $userId, $clientId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                if ($progress['lesson_status'] === 'completed') {
                    return 100;
                } elseif ($progress['lesson_status'] === 'incomplete' && !empty($progress['lesson_location'])) {
                    return 75; // In progress
                } elseif (!empty($progress['lesson_location'])) {
                    return 50; // Started
                }
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error getting SCORM progress: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get document progress
    private function getDocumentProgress($contentId, $userId, $clientId)
    {
        try {
            // The contentId parameter is the course_module_content.id
            // We need to find progress records that match this content_id
            $sql = "SELECT viewed_percentage, is_completed, time_spent, current_page, total_pages 
                    FROM document_progress 
                    WHERE content_id = ? AND user_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId, $userId, $clientId]);
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                if ($progress['is_completed']) {
                    return 100; // Completed
                } elseif ($progress['viewed_percentage'] >= 80) { // 80% threshold
                    return 100; // Completed based on threshold
                } elseif ($progress['viewed_percentage'] > 0) {
                    return floatval($progress['viewed_percentage']); // In progress
                } elseif ($progress['current_page'] > 1) {
                    return 25; // Started but no percentage yet
                }
            }
            return 0; // Not started
        } catch (Exception $e) {
            error_log("Error getting document progress: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get general content progress
    private function getContentProgress($contentId, $userId, $clientId)
    {
        try {
            // Since content_progress table was removed, check user_content_activity instead
            // Check if user has started this content (for video, audio, etc.)
            $sql = "SELECT started_at, completed_at 
                    FROM user_content_activity 
                    WHERE content_id = ? AND user_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId, $userId, $clientId]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($activity) {
                if ($activity['completed_at']) {
                    return 100;
                } elseif ($activity['started_at']) {
                    return 25; // Started but not completed
                }
            }
            
            // Check for audio progress specifically
            $sql = "SELECT listened_percentage, is_completed 
                    FROM audio_progress 
                    WHERE content_id = ? AND user_id = ? AND client_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$contentId, $userId, $clientId]);
            $audioProgress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($audioProgress) {
                if ($audioProgress['is_completed']) {
                    return 100;
                } else {
                    return intval($audioProgress['listened_percentage'] ?? 0);
                }
            }
            
            return 0;
        } catch (Exception $e) {
            error_log("Error getting content progress: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if a course has applicability rules
     * @param int $courseId
     * @return bool
     */
    public function hasCourseApplicabilityRules($courseId)
    {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM course_applicability 
                WHERE course_id = ?
            ");
            $stmt->execute([$courseId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['count'];
            
            return $count > 0;
        } catch (Exception $e) {
            error_log("Error checking course applicability rules: " . $e->getMessage());
            return true; // Assume has applicability rules if error occurs (safer approach)
        }
    }
} 