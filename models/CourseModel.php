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
    private function uploadFile($file, $uploadDir)
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
            module_id, content_type, content_id, title, description,
            content_order, is_required, estimated_duration, created_by
        ) VALUES (
            :module_id, :content_type, :content_id, :title, :description,
            :content_order, :is_required, :estimated_duration, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':module_id' => $moduleId,
            ':content_type' => $contentType,
            ':content_id' => $contentId,
            ':title' => $contentData['title'] ?? '',
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
            course_id, prerequisite_type, prerequisite_id, prerequisite_name, 
            prerequisite_description, sort_order, created_by
        ) VALUES (
            :course_id, :prerequisite_type, :prerequisite_id, :prerequisite_name,
            :prerequisite_description, :sort_order, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            ':course_id' => $courseId,
            ':prerequisite_type' => $prerequisiteType,
            ':prerequisite_id' => $prerequisiteId,
            ':prerequisite_name' => $prerequisiteData['title'] ?? '',
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
            title, description, is_required, sort_order, settings, created_by
        ) VALUES (
            :course_id, :content_type, :content_id, :requisite_type, :module_id,
            :title, :description, :is_required, :sort_order, :settings, :created_by
        )";

        error_log("SQL Query: $sql");
        
        $params = [
            ':course_id' => $courseId,
            ':content_type' => $contentType,
            ':content_id' => $contentId,
            ':requisite_type' => $requisiteData['requisite_type'] ?? 'post_course',
            ':module_id' => $requisiteData['module_id'] ?? null,
            ':title' => $requisiteData['title'] ?? '',
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
                WHERE c.deleted_at IS NULL";
        
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
                $sql .= " AND c.deleted_at IS NOT NULL";
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.short_description LIKE ?)";
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
    public function getCourseById($courseId, $clientId = null)
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
            $course['modules'] = $this->getCourseModules($courseId);
            
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
    public function getCourseModules($courseId)
    {
        $sql = "SELECT * FROM course_modules 
                WHERE course_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') 
                ORDER BY module_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
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
        // Ensure each item has a 'type' field for frontend preselection
        foreach ($rows as &$row) {
            if (!isset($row['type']) && isset($row['content_type'])) {
                $row['type'] = $row['content_type'];
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
            // Fallback: use cp.prerequisite_name if join title is empty
            if (empty($row['title']) && !empty($row['prerequisite_name'])) {
                $row['title'] = $row['prerequisite_name'];
            }
            if (empty($row['title'])) {
                $row['title'] = 'Untitled';
            }
            if (empty($row['type'])) {
                $row['type'] = 'unknown';
            }
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
                updated_by = :updated_by, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (client_id = :client_id OR :client_id IS NULL)";

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
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'survey' as type FROM survey_package WHERE client_id = ? AND is_deleted = 0";
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
                $sql = "SELECT id, title, COALESCE(description, '') as description, 'feedback' as type FROM feedback_package WHERE client_id = ? AND is_deleted = 0";
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
                        (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id) as enrollment_count,
                        (SELECT ROUND(AVG(completion_percentage), 1) FROM course_enrollments WHERE course_id = c.id) as completion_rate
                    FROM courses c
                    LEFT JOIN course_categories cc ON c.category_id = cc.id
                    LEFT JOIN course_subcategories csc ON c.subcategory_id = csc.id
                    WHERE c.client_id = ? AND c.is_deleted = 0
                    ORDER BY c.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$clientId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    FROM course_enrollments 
                    WHERE course_id = ?";
            
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
} 