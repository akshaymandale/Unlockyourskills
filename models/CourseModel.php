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
    public function createCourse($data)
    {
        try {
            $this->conn->beginTransaction();

            // Insert course
            $sql = "INSERT INTO courses (
                client_id, title, description, short_description, category_id, subcategory_id,
                course_type, difficulty_level, duration_hours, duration_minutes, max_attempts,
                passing_score, is_self_paced, is_featured, is_published, thumbnail_image,
                banner_image, tags, learning_objectives, prerequisites, target_audience,
                certificate_template, completion_criteria, created_by
            ) VALUES (
                :client_id, :title, :description, :short_description, :category_id, :subcategory_id,
                :course_type, :difficulty_level, :duration_hours, :duration_minutes, :max_attempts,
                :passing_score, :is_self_paced, :is_featured, :is_published, :thumbnail_image,
                :banner_image, :tags, :learning_objectives, :prerequisites, :target_audience,
                :certificate_template, :completion_criteria, :created_by
            )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':client_id' => $data['client_id'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':short_description' => $data['short_description'],
                ':category_id' => $data['category_id'],
                ':subcategory_id' => $data['subcategory_id'],
                ':course_type' => $data['course_type'],
                ':difficulty_level' => $data['difficulty_level'],
                ':duration_hours' => $data['duration_hours'],
                ':duration_minutes' => $data['duration_minutes'],
                ':max_attempts' => $data['max_attempts'],
                ':passing_score' => $data['passing_score'],
                ':is_self_paced' => $data['is_self_paced'],
                ':is_featured' => $data['is_featured'],
                ':is_published' => $data['is_published'],
                ':thumbnail_image' => $data['thumbnail_image'],
                ':banner_image' => $data['banner_image'],
                ':tags' => $data['tags'],
                ':learning_objectives' => $data['learning_objectives'],
                ':prerequisites' => $data['prerequisites'],
                ':target_audience' => $data['target_audience'],
                ':certificate_template' => $data['certificate_template'],
                ':completion_criteria' => $data['completion_criteria'],
                ':created_by' => $data['created_by']
            ]);

            $courseId = $this->conn->lastInsertId();

            // Insert modules if provided
            if (!empty($data['modules'])) {
                foreach ($data['modules'] as $module) {
                    $this->createModule($courseId, $module);
                }
            }

            // Insert prerequisites if provided
            if (!empty($data['prerequisite_courses'])) {
                foreach ($data['prerequisite_courses'] as $prerequisite) {
                    $this->addPrerequisite($courseId, $prerequisite);
                }
            }

            // Insert assessments if provided
            if (!empty($data['assessments'])) {
                foreach ($data['assessments'] as $assessment) {
                    $this->addAssessment($courseId, $assessment);
                }
            }

            // Insert feedback if provided
            if (!empty($data['feedback'])) {
                foreach ($data['feedback'] as $feedback) {
                    $this->addFeedback($courseId, $feedback);
                }
            }

            // Insert surveys if provided
            if (!empty($data['surveys'])) {
                foreach ($data['surveys'] as $survey) {
                    $this->addSurvey($courseId, $survey);
                }
            }

            $this->conn->commit();
            return $courseId;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Course creation error: " . $e->getMessage());
            return false;
        }
    }

    // Create a module
    private function createModule($courseId, $moduleData)
    {
        $sql = "INSERT INTO course_modules (
            course_id, title, description, sort_order, is_required, 
            estimated_duration, learning_objectives, created_by
        ) VALUES (
            :course_id, :title, :description, :sort_order, :is_required,
            :estimated_duration, :learning_objectives, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':course_id' => $courseId,
            ':title' => $moduleData['title'],
            ':description' => $moduleData['description'],
            ':sort_order' => $moduleData['sort_order'],
            ':is_required' => $moduleData['is_required'],
            ':estimated_duration' => $moduleData['estimated_duration'],
            ':learning_objectives' => $moduleData['learning_objectives'],
            ':created_by' => $moduleData['created_by']
        ]);

        $moduleId = $this->conn->lastInsertId();

        // Add module content if provided
        if (!empty($moduleData['content'])) {
            foreach ($moduleData['content'] as $content) {
                $this->addModuleContent($moduleId, $content);
            }
        }

        return $moduleId;
    }

    // Add module content
    private function addModuleContent($moduleId, $contentData)
    {
        $sql = "INSERT INTO course_module_content (
            module_id, content_type, content_id, title, description,
            sort_order, is_required, estimated_duration, completion_criteria, created_by
        ) VALUES (
            :module_id, :content_type, :content_id, :title, :description,
            :sort_order, :is_required, :estimated_duration, :completion_criteria, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':module_id' => $moduleId,
            ':content_type' => $contentData['content_type'],
            ':content_id' => $contentData['content_id'],
            ':title' => $contentData['title'],
            ':description' => $contentData['description'],
            ':sort_order' => $contentData['sort_order'],
            ':is_required' => $contentData['is_required'],
            ':estimated_duration' => $contentData['estimated_duration'],
            ':completion_criteria' => $contentData['completion_criteria'],
            ':created_by' => $contentData['created_by']
        ]);
    }

    // Add prerequisite
    private function addPrerequisite($courseId, $prerequisiteData)
    {
        $sql = "INSERT INTO course_prerequisites (
            course_id, prerequisite_course_id, prerequisite_type, minimum_score, created_by
        ) VALUES (
            :course_id, :prerequisite_course_id, :prerequisite_type, :minimum_score, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':course_id' => $courseId,
            ':prerequisite_course_id' => $prerequisiteData['prerequisite_course_id'],
            ':prerequisite_type' => $prerequisiteData['prerequisite_type'],
            ':minimum_score' => $prerequisiteData['minimum_score'],
            ':created_by' => $prerequisiteData['created_by']
        ]);
    }

    // Add assessment (references existing VLR assessment package)
    private function addAssessment($courseId, $assessmentData)
    {
        $sql = "INSERT INTO course_assessments (
            course_id, assessment_id, assessment_type, module_id, title, description,
            is_required, passing_score, max_attempts, time_limit, sort_order, created_by
        ) VALUES (
            :course_id, :assessment_id, :assessment_type, :module_id, :title, :description,
            :is_required, :passing_score, :max_attempts, :time_limit, :sort_order, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':course_id' => $courseId,
            ':assessment_id' => $assessmentData['assessment_id'],
            ':assessment_type' => $assessmentData['assessment_type'],
            ':module_id' => $assessmentData['module_id'],
            ':title' => $assessmentData['title'],
            ':description' => $assessmentData['description'],
            ':is_required' => $assessmentData['is_required'],
            ':passing_score' => $assessmentData['passing_score'],
            ':max_attempts' => $assessmentData['max_attempts'],
            ':time_limit' => $assessmentData['time_limit'],
            ':sort_order' => $assessmentData['sort_order'],
            ':created_by' => $assessmentData['created_by']
        ]);
    }

    // Add feedback (references existing VLR feedback package)
    private function addFeedback($courseId, $feedbackData)
    {
        $sql = "INSERT INTO course_feedback (
            course_id, feedback_id, feedback_type, module_id, title, description,
            is_required, sort_order, created_by
        ) VALUES (
            :course_id, :feedback_id, :feedback_type, :module_id, :title, :description,
            :is_required, :sort_order, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':course_id' => $courseId,
            ':feedback_id' => $feedbackData['feedback_id'],
            ':feedback_type' => $feedbackData['feedback_type'],
            ':module_id' => $feedbackData['module_id'],
            ':title' => $feedbackData['title'],
            ':description' => $feedbackData['description'],
            ':is_required' => $feedbackData['is_required'],
            ':sort_order' => $feedbackData['sort_order'],
            ':created_by' => $feedbackData['created_by']
        ]);
    }

    // Add survey (references existing VLR survey package)
    private function addSurvey($courseId, $surveyData)
    {
        $sql = "INSERT INTO course_surveys (
            course_id, survey_id, survey_type, module_id, title, description,
            is_required, sort_order, created_by
        ) VALUES (
            :course_id, :survey_id, :survey_type, :module_id, :title, :description,
            :is_required, :sort_order, :created_by
        )";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':course_id' => $courseId,
            ':survey_id' => $surveyData['survey_id'],
            ':survey_type' => $surveyData['survey_type'],
            ':module_id' => $surveyData['module_id'],
            ':title' => $surveyData['title'],
            ':description' => $surveyData['description'],
            ':is_required' => $surveyData['is_required'],
            ':sort_order' => $surveyData['sort_order'],
            ':created_by' => $surveyData['created_by']
        ]);
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

        if (!empty($filters['search'])) {
            $sql .= " AND (c.title LIKE ? OR c.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get course by ID with all related data
    public function getCourseById($courseId, $clientId = null)
    {
        $sql = "SELECT c.*, 
                       cc.name as category_name, 
                       csc.name as subcategory_name,
                       up.full_name as created_by_name
                FROM courses c
                LEFT JOIN course_categories cc ON c.category_id = cc.id
                LEFT JOIN course_subcategories csc ON c.subcategory_id = csc.id
                LEFT JOIN user_profiles up ON c.created_by = up.id
                WHERE c.id = ? AND c.is_deleted = 0";
        
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
            
            // Get assessments
            $course['assessments'] = $this->getCourseAssessments($courseId);
            
            // Get feedback
            $course['feedback'] = $this->getCourseFeedback($courseId);
            
            // Get surveys
            $course['surveys'] = $this->getCourseSurveys($courseId);
        }

        return $course;
    }

    // Get course modules
    private function getCourseModules($courseId)
    {
        $sql = "SELECT * FROM course_modules 
                WHERE course_id = ? AND is_deleted = 0 
                ORDER BY sort_order ASC";
        
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
                WHERE module_id = ? AND is_deleted = 0 
                ORDER BY sort_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$moduleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get course prerequisites
    private function getCoursePrerequisites($courseId)
    {
        $sql = "SELECT cp.*, c.title as prerequisite_course_title
                FROM course_prerequisites cp
                LEFT JOIN courses c ON cp.prerequisite_course_id = c.id
                WHERE cp.course_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get course assessments
    private function getCourseAssessments($courseId)
    {
        $sql = "SELECT ca.*, ap.title as assessment_title
                FROM course_assessments ca
                LEFT JOIN assessment_package ap ON ca.assessment_id = ap.id
                WHERE ca.course_id = ? AND ca.is_deleted = 0
                ORDER BY ca.sort_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get course feedback
    private function getCourseFeedback($courseId)
    {
        $sql = "SELECT cf.*, fp.title as feedback_title
                FROM course_feedback cf
                LEFT JOIN feedback_package fp ON cf.feedback_id = fp.id
                WHERE cf.course_id = ? AND cf.is_deleted = 0
                ORDER BY cf.sort_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get course surveys
    private function getCourseSurveys($courseId)
    {
        $sql = "SELECT cs.*, sp.title as survey_title
                FROM course_surveys cs
                LEFT JOIN survey_package sp ON cs.survey_id = sp.id
                WHERE cs.course_id = ? AND cs.is_deleted = 0
                ORDER BY cs.sort_order ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$courseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update course
    public function updateCourse($courseId, $data, $clientId = null)
    {
        try {
            $this->conn->beginTransaction();

            $sql = "UPDATE courses SET
                title = :title, description = :description, short_description = :short_description,
                category_id = :category_id, subcategory_id = :subcategory_id, course_type = :course_type,
                difficulty_level = :difficulty_level, duration_hours = :duration_hours, duration_minutes = :duration_minutes,
                max_attempts = :max_attempts, passing_score = :passing_score, is_self_paced = :is_self_paced,
                is_featured = :is_featured, is_published = :is_published, thumbnail_image = :thumbnail_image,
                banner_image = :banner_image, tags = :tags, learning_objectives = :learning_objectives,
                prerequisites = :prerequisites, target_audience = :target_audience,
                certificate_template = :certificate_template, completion_criteria = :completion_criteria,
                updated_by = :updated_by, updated_at = NOW()
                WHERE id = :course_id";

            $params = [
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':short_description' => $data['short_description'],
                ':category_id' => $data['category_id'],
                ':subcategory_id' => $data['subcategory_id'],
                ':course_type' => $data['course_type'],
                ':difficulty_level' => $data['difficulty_level'],
                ':duration_hours' => $data['duration_hours'],
                ':duration_minutes' => $data['duration_minutes'],
                ':max_attempts' => $data['max_attempts'],
                ':passing_score' => $data['passing_score'],
                ':is_self_paced' => $data['is_self_paced'],
                ':is_featured' => $data['is_featured'],
                ':is_published' => $data['is_published'],
                ':thumbnail_image' => $data['thumbnail_image'],
                ':banner_image' => $data['banner_image'],
                ':tags' => $data['tags'],
                ':learning_objectives' => $data['learning_objectives'],
                ':prerequisites' => $data['prerequisites'],
                ':target_audience' => $data['target_audience'],
                ':certificate_template' => $data['certificate_template'],
                ':completion_criteria' => $data['completion_criteria'],
                ':updated_by' => $data['updated_by'],
                ':course_id' => $courseId
            ];

            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Course update error: " . $e->getMessage());
            return false;
        }
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
    public function getAvailableVLRContent($clientId = null)
    {
        $content = [];

        // Get SCORM packages
        $sql = "SELECT id, title, 'scorm' as content_type FROM scorm_packages WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['scorm'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get non-SCORM packages
        $sql = "SELECT id, title, 'non_scorm' as content_type FROM non_scorm_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['non_scorm'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get assessment packages
        $sql = "SELECT id, title, 'assessment' as content_type FROM assessment_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['assessment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get audio packages
        $sql = "SELECT id, title, 'audio' as content_type FROM audio_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['audio'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get video packages
        $sql = "SELECT id, title, 'video' as content_type FROM video_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['video'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get document packages
        $sql = "SELECT id, title, 'document' as content_type FROM documents WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['document'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get image packages
        $sql = "SELECT id, title, 'image' as content_type FROM image_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['image'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get external content
        $sql = "SELECT id, title, 'external' as content_type FROM external_content WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['external'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get survey packages
        $sql = "SELECT id, title, 'survey' as content_type FROM survey_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['survey'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get feedback packages
        $sql = "SELECT id, title, 'feedback' as content_type FROM feedback_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['feedback'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get interactive content
        $sql = "SELECT id, title, 'interactive' as content_type FROM interactive_ai_content_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['interactive'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get assignment packages
        $sql = "SELECT id, title, 'assignment' as content_type FROM assignment_package WHERE is_deleted = 0";
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($clientId !== null ? [$clientId] : []);
        $content['assignment'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $content;
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
            $sql = "UPDATE courses SET status = ?, updated_at = NOW() WHERE id = ? AND client_id = ? AND is_deleted = 0";
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
} 