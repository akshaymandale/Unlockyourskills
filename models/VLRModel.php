<?php
require_once 'config/Database.php';

class VLRModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // ✅ Insert SCORM Package
    public function insertScormPackage($data)
    {
        // Backend Validation: Ensure required fields are filled
        if (empty($data['title']) || empty($data['zip_file']) || empty($data['version']) || empty($data['scorm_category']) || empty($data['mobile_support']) || empty($data['assessment']) || empty($data['client_id'])) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO scorm_packages
        (client_id, title, zip_file, description, tags, version, language, scorm_category, time_limit, mobile_support, assessment, launch_path, created_by, is_deleted, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");

        return $stmt->execute([
            $data['client_id'],
            $data['title'],
            $data['zip_file'],
            $data['description'],
            $data['tags'],
            $data['version'],
            $data['language'],
            $data['scorm_category'],
            $data['time_limit'],
            $data['mobile_support'],
            $data['assessment'],
            $data['launch_path'] ?? null,
            $data['created_by']
        ]);
    }

    // ✅ Update SCORM Package
    public function updateScormPackage($id, $data, $clientId = null)
    {
        // Ensure SCORM ID exists
        if (empty($id)) {
            return false;
        }

        $sql = "UPDATE scorm_packages
        SET title = ?, zip_file = ?, description = ?, tags = ?, version = ?, language = ?, scorm_category = ?, time_limit = ?, mobile_support = ?, assessment = ?, launch_path = ?, updated_at = NOW()
        WHERE id = ?";

        $params = [
            $data['title'],
            $data['zip_file'],
            $data['description'],
            $data['tags'],
            $data['version'],
            $data['language'],
            $data['scorm_category'],
            $data['time_limit'],
            $data['mobile_support'],
            $data['assessment'],
            $data['launch_path'] ?? null,
            $id
        ];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Get data for display on VLR
    public function getScormPackages($clientId = null)
    {
        $sql = "SELECT * FROM scorm_packages WHERE is_deleted = 0";
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs

        // Add can_edit and can_delete flags
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            foreach ($data as &$package) {
                $package['can_edit'] = 0;
                $package['can_delete'] = 0;
            }
            unset($package);
            return $data;
        }
        foreach ($data as &$package) {
            $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        }
        unset($package);

        return $data;
    }

    // Delete respective SCROM
    public function deleteScormPackage($id, $clientId = null)
    {
        $sql = "UPDATE scorm_packages SET is_deleted = 1 WHERE id = ?";
        $params = [$id];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Add external content package

    public function insertExternalContent($data)
    {
        try {
            // Debugging: Print data before inserting
            // echo "<pre>";
            // print_r($data);
            // echo "</pre>";
            // exit;

            // Ensure audio_file exists in data
            $audioFile = isset($data['audio_file']) ? $data['audio_file'] : null;

            $sql = "INSERT INTO external_content (
                client_id, title, content_type, version_number, mobile_support, language_support, time_limit,
                description, tags, video_url, thumbnail, course_url, platform_name, article_url,
                author, audio_source, audio_url, audio_file, speaker, created_by
            ) VALUES (
                :client_id, :title, :content_type, :version_number, :mobile_support, :language_support, :time_limit,
                :description, :tags, :video_url, :thumbnail, :course_url, :platform_name, :article_url,
                :author, :audio_source, :audio_url, :audio_file, :speaker, :created_by
            )";

            $stmt = $this->conn->prepare($sql);

            // Bind parameters
            $stmt->execute([
                ':client_id' => $data['client_id'],
                ':title' => $data['title'],
                ':content_type' => $data['content_type'],
                ':version_number' => $data['version_number'],
                ':mobile_support' => $data['mobile_support'],
                ':language_support' => $data['language_support'],
                ':time_limit' => $data['time_limit'],
                ':description' => $data['description'],
                ':tags' => $data['tags'],
                ':video_url' => $data['video_url'],
                ':thumbnail' => $data['thumbnail'],
                ':course_url' => $data['course_url'],
                ':platform_name' => $data['platform_name'],
                ':article_url' => $data['article_url'],
                ':author' => $data['author'],
                ':audio_source' => $data['audio_source'],
                ':audio_url' => $data['audio_url'],
                ':audio_file' => $audioFile, // Now ensuring this is always set
                ':speaker' => $data['speaker'],
                ':created_by' => $data['created_by']
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Insert Error: " . $e->getMessage());
            return false;
        }
    }


    // Update for External Content

    public function updateExternalContent($id, $data, $clientId = null)
    {
        try {
            // Ensure audio_file exists in data
            $audioFile = isset($data['audio_file']) ? $data['audio_file'] : null;

            $sql = "UPDATE external_content SET
                title = :title,
                content_type = :content_type,
                version_number = :version_number,
                mobile_support = :mobile_support,
                language_support = :language_support,
                time_limit = :time_limit,
                description = :description,
                tags = :tags,
                video_url = :video_url,
                thumbnail = :thumbnail,
                course_url = :course_url,
                platform_name = :platform_name,
                article_url = :article_url,
                author = :author,
                audio_source = :audio_source,
                audio_url = :audio_url,
                audio_file = :audio_file,
                speaker = :speaker,
                updated_at = NOW()
            WHERE id = :id";

            $params = [
                ':id' => $id,
                ':title' => $data['title'],
                ':content_type' => $data['content_type'],
                ':version_number' => $data['version_number'],
                ':mobile_support' => $data['mobile_support'],
                ':language_support' => $data['language_support'],
                ':time_limit' => $data['time_limit'],
                ':description' => $data['description'],
                ':tags' => $data['tags'],
                ':video_url' => $data['video_url'],
                ':thumbnail' => $data['thumbnail'],
                ':course_url' => $data['course_url'],
                ':platform_name' => $data['platform_name'],
                ':article_url' => $data['article_url'],
                ':author' => $data['author'],
                ':audio_source' => $data['audio_source'],
                ':audio_url' => $data['audio_url'],
                ':audio_file' => $audioFile, // Ensuring it is always set
                ':speaker' => $data['speaker']
            ];

            if ($clientId !== null) {
                $sql .= " AND client_id = :client_id";
                $params[':client_id'] = $clientId;
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            return true;
        } catch (PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            return false;
        }
    }

    // Get data for External Content with Language Names
    public function getExternalContent($clientId = null)
    {
        $sql = "
            SELECT e.*, l.language_name
            FROM external_content e
            LEFT JOIN languages l ON e.language_support = l.id
            WHERE e.is_deleted = 0
        ";
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND e.client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs

        // Add can_edit and can_delete flags
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            foreach ($data as &$package) {
                $package['can_edit'] = 0;
                $package['can_delete'] = 0;
            }
            unset($package);
            return $data;
        }
        foreach ($data as &$package) {
            $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        }
        unset($package);

        return $data;
    }

    // Delete respective External Content
    public function deleteExternalContent($id, $clientId = null)
    {
        $sql = "UPDATE external_content SET is_deleted = 1 WHERE id = ?";
        $params = [$id];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }


    // Documents package
    // Fetch all documents with language names

    // Get data for display on VLR
    public function getAllDocuments($clientId = null)
    {
        $sql = "SELECT d.*, l.language_name FROM documents d
                  LEFT JOIN languages l ON d.language_id = l.id
                  WHERE d.is_deleted = 0";
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND d.client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs

        // Add can_edit and can_delete flags
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            foreach ($data as &$package) {
                $package['can_edit'] = 0;
                $package['can_delete'] = 0;
            }
            unset($package);
            return $data;
        }
        foreach ($data as &$package) {
            $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        }
        unset($package);

        return $data;
    }


    // Fetch a single document by ID
    public function getDocumentById($id, $clientId = null)
    {
        $sql = "SELECT * FROM documents WHERE id = ? AND is_deleted = 0";
        $params = [$id];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Add document
    // Server-side validation
    private function validateDocument($data, $isUpdate = false)
    {
        $errors = [];

        if (empty($data['document_title'])) {
            $errors['document_title'] = "Title is required.";
        }

        if (empty($data['documentCategory'])) {
            $errors['documentCategory'] = "Category is required.";
        }

        if (empty($data['documentTagList'])) {
            $errors['documentTagList'] = "At least one tag is required.";
        }

        if (empty($data['doc_version'])) {
            $errors['doc_version'] = "Version number is required.";
        }

        if (!$isUpdate) {
            if ($data['documentCategory'] == "Word/Excel/PPT Files" && empty($data['word_excel_ppt_file'])) {
                $errors['word_excel_ppt_file'] = "File upload is required.";
            }

            if ($data['documentCategory'] == "E-Book & Manual" && empty($data['ebook_manual_file'])) {
                $errors['ebook_manual_file'] = "File upload is required.";
            }

            if ($data['documentCategory'] == "Research Paper & Case Studies" && empty($data['research_file'])) {
                $errors['research_file'] = "File upload is required.";
            }
        }

        return $errors;
    }

    // Insert document into database
    public function insertDocument($data)
    {
        $errors = $this->validateDocument($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $query = "INSERT INTO documents (client_id, title, category, description, tags, language_id, mobile_support, version_number, time_limit,
              authors, publication_date, reference_links, created_by, created_at, is_deleted, word_excel_ppt_file, ebook_manual_file, research_file)
              VALUES (:client_id, :title, :category, :description, :tags, :language_id, :mobile_support, :version_number, :time_limit,
              :authors, :publication_date, :reference_links, :created_by, NOW(), 0, :word_excel_ppt_file, :ebook_manual_file, :research_file)";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':client_id' => $data['client_id'],
            ':title' => $data['document_title'],
            ':category' => $data['documentCategory'],
            ':description' => $data['description'],
            ':tags' => $data['documentTagList'],
            ':language_id' => !empty($data['language']) ? (int) $data['language'] : null,
            ':mobile_support' => $data['mobile_support'],
            ':version_number' => $data['doc_version'],
            ':time_limit' => !empty($data['doc_time_limit']) ? (int) $data['doc_time_limit'] : null,
            ':authors' => $data['research_authors'] ?? null,
            ':publication_date' => $data['research_publication_date'] ?? null,
            ':reference_links' => $data['research_references'] ?? null,
            ':created_by' => $data['created_by'],
            ':word_excel_ppt_file' => $data['word_excel_ppt_file'] ?? null,
            ':ebook_manual_file' => $data['ebook_manual_file'] ?? null,
            ':research_file' => $data['research_file'] ?? null
        ]);

        return ['success' => true, 'message' => "Document added successfully."];
    }

    // Update document
    public function updateDocument($data, $id, $clientId = null)
    {
        $errors = $this->validateDocument($data, true);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $query = "UPDATE documents SET title = :title, category = :category, description = :description, tags = :tags,
              language_id = :language_id, mobile_support = :mobile_support, version_number = :version_number, time_limit = :time_limit,
              authors = :authors, publication_date = :publication_date, reference_links = :reference_links, updated_at = NOW(),
              word_excel_ppt_file = COALESCE(:word_excel_ppt_file, word_excel_ppt_file),
              ebook_manual_file = COALESCE(:ebook_manual_file, ebook_manual_file),
              research_file = COALESCE(:research_file, research_file)
              WHERE id = :id";

        $params = [
            ':title' => $data['document_title'],
            ':category' => $data['documentCategory'],
            ':description' => $data['description'],
            ':tags' => $data['documentTagList'],
            ':language_id' => !empty($data['language']) ? (int) $data['language'] : null,
            ':mobile_support' => $data['mobile_support'],
            ':version_number' => $data['doc_version'],
            ':time_limit' => !empty($data['doc_time_limit']) ? (int) $data['doc_time_limit'] : null,
            ':authors' => $data['research_authors'] ?? null,
            ':publication_date' => $data['research_publication_date'] ?? null,
            ':reference_links' => $data['research_references'] ?? null,
            ':word_excel_ppt_file' => $data['word_excel_ppt_file'] ?? null,
            ':ebook_manual_file' => $data['ebook_manual_file'] ?? null,
            ':research_file' => $data['research_file'] ?? null,
            ':id' => $id
        ];

        if ($clientId !== null) {
            $query .= " AND client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);

        return ['success' => true, 'message' => "Document updated successfully."];
    }

    // Soft delete a document
    public function deleteDocument($id, $clientId = null)
    {
        $sql = "UPDATE documents SET is_deleted = 1 WHERE id = ?";
        $params = [$id];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Fetch all languages
    public function getLanguages()
    {
        $sql = "SELECT id, language_name, language_code FROM languages"; // Ensure 'id' is selected
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative array
        // print_r($stmt);die;
    }

    // Assessment add and update

    public function saveAssessmentWithQuestions($data)
    {
        try {
            $this->conn->beginTransaction();

            // Insert into assessment_package
            $sql = "INSERT INTO assessment_package (
            client_id, title, tags, num_attempts, passing_percentage, time_limit,
            negative_marking, negative_marking_percentage,
            assessment_type, num_questions_to_display,
            selected_question_count, created_by, created_at
        ) VALUES (
            :client_id, :title, :tags, :num_attempts, :passing_percentage, :time_limit,
            :negative_marking, :negative_marking_percentage,
            :assessment_type, :num_questions_to_display,
            :selected_question_count, :created_by, NOW()
        )";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':client_id' => $data['client_id'],
                ':title' => $data['title'],
                ':tags' => $data['tags'],
                ':num_attempts' => $data['num_attempts'],
                ':passing_percentage' => $data['passing_percentage'],
                ':time_limit' => $data['time_limit'],
                ':negative_marking' => $data['negative_marking'],
                ':negative_marking_percentage' => $data['negative_marking_percentage'],
                ':assessment_type' => $data['assessment_type'],
                ':num_questions_to_display' => $data['num_questions_to_display'],
                ':selected_question_count' => count($data['question_ids']),
                ':created_by' => $data['created_by'],
            ]);

            $assessmentPackageId = $this->conn->lastInsertId();
            if (!$assessmentPackageId) {
                throw new Exception("Failed to retrieve last insert ID.");
            }

            // Insert into mapping table using correct column: assessment_package_id
            $mapSql = "INSERT INTO assessment_question_mapping (
            assessment_package_id, question_id, created_by, created_at
        ) VALUES (
            :assessment_package_id, :question_id, :created_by, NOW()
        )";

            $mapStmt = $this->conn->prepare($mapSql);
            if (!is_array($data['question_ids'])) {
                throw new Exception("question_ids is not an array.");
            }

            foreach ($data['question_ids'] as $qid) {
                $success = $mapStmt->execute([
                    ':assessment_package_id' => $assessmentPackageId,
                    ':question_id' => $qid,
                    ':created_by' => $data['created_by']
                ]);

                if (!$success) {
                    $error = $mapStmt->errorInfo();
                    throw new Exception("Mapping insert failed for question_id $qid: " . $error[2]);
                }
            }

            $this->conn->commit();
            return $assessmentPackageId;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Assessment Save Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }


    public function updateAssessmentWithQuestions($data, $assessmentId)
    {
        try {
            $this->conn->beginTransaction();

            // Basic server-side validation (if needed beyond controller)
            if (empty($assessmentId) || !is_numeric($assessmentId)) {
                throw new Exception("Invalid assessment ID.");
            }

            if (!is_array($data['question_ids']) || count($data['question_ids']) === 0) {
                throw new Exception("At least one question must be selected.");
            }

            // Update assessment_package
            $sql = "UPDATE assessment_package SET
            title = :title,
            tags = :tags,
            num_attempts = :num_attempts,
            passing_percentage = :passing_percentage,
            time_limit = :time_limit,
            negative_marking = :negative_marking,
            negative_marking_percentage = :negative_marking_percentage,
            assessment_type = :assessment_type,
            num_questions_to_display = :num_questions_to_display,
            selected_question_count = :selected_question_count,
            updated_at = NOW()
        WHERE id = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':tags' => $data['tags'],
                ':num_attempts' => $data['num_attempts'],
                ':passing_percentage' => $data['passing_percentage'],
                ':time_limit' => $data['time_limit'],
                ':negative_marking' => $data['negative_marking'],
                ':negative_marking_percentage' => $data['negative_marking_percentage'],
                ':assessment_type' => $data['assessment_type'],
                ':num_questions_to_display' => $data['num_questions_to_display'],
                ':selected_question_count' => count($data['question_ids']),
                ':id' => $assessmentId
            ]);

            // Delete old mappings
            $deleteSql = "DELETE FROM assessment_question_mapping WHERE assessment_package_id = :assessment_id";
            $deleteStmt = $this->conn->prepare($deleteSql);
            $deleteStmt->execute([':assessment_id' => $assessmentId]);

            // Insert new mappings
            $mapSql = "INSERT INTO assessment_question_mapping (
            assessment_package_id, question_id, created_by, created_at
        ) VALUES (
            :assessment_package_id, :question_id, :created_by, NOW()
        )";

            $mapStmt = $this->conn->prepare($mapSql);

            foreach ($data['question_ids'] as $qid) {
                $success = $mapStmt->execute([
                    ':assessment_package_id' => $assessmentId,
                    ':question_id' => $qid,
                    ':created_by' => $data['created_by']
                ]);

                if (!$success) {
                    $error = $mapStmt->errorInfo();
                    throw new Exception("Mapping insert failed for question_id $qid: " . $error[2]);
                }
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Assessment Update Error: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }


    // Assessment get data for display

    public function getAllAssessments($clientId = null)
    {
        $sql = "SELECT * FROM assessment_package WHERE is_deleted = 0";
        $params = [];
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add can_edit and can_delete flags
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            foreach ($data as &$package) {
                $package['can_edit'] = 0;
                $package['can_delete'] = 0;
            }
            unset($package);
            return $data;
        }
        foreach ($data as &$package) {
            $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        }
        unset($package);

        return $data;
    }

    public function getAssessmentByIdWithQuestions($assessmentId)
    {
        $db = $this->conn;

        // Fetch the assessment data
        $stmt = $db->prepare("
            SELECT *
            FROM assessment_package
            WHERE id = :id AND is_deleted = 0
        ");
        $stmt->execute([':id' => $assessmentId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assessment) {
            return null;
        }

        // Fetch mapped questions
        $stmt = $db->prepare("
            SELECT q.id, q.question_text AS title, q.tags, q.marks, q.question_type AS type
            FROM assessment_question_mapping m
            JOIN assessment_questions q ON m.question_id = q.id
            WHERE m.assessment_package_id = :id AND q.is_deleted = 0
        ");
        $stmt->execute([':id' => $assessmentId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add questions to assessment array
        $assessment['selected_questions'] = $questions;

        return $assessment;
    }

    public function deleteAssessment($id)
    {
        try {
            $this->conn->beginTransaction();

            // Hard delete from mapping table
            $stmt1 = $this->conn->prepare("DELETE FROM assessment_question_mapping WHERE assessment_package_id = ?");
            $stmt1->execute([$id]);

            // Soft delete in assessment_package table
            $stmt2 = $this->conn->prepare("UPDATE assessment_package SET is_deleted = 1 WHERE id = ?");
            $stmt2->execute([$id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Assessment Deletion Error: " . $e->getMessage());
            return false;
        }
    }


// ✅ Insert Audio Package
public function insertAudioPackage($data)
{
    // Validate required fields
    $requiredFields = ['client_id', 'title', 'audio_file', 'version', 'mobile_support', 'tags', 'created_by'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }

    $stmt = $this->conn->prepare("
        INSERT INTO audio_package
        (client_id, title, audio_file, version, language, time_limit, description, tags, mobile_support, created_by, is_deleted, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");

    return $stmt->execute([
        $data['client_id'],
        $data['title'],
        $data['audio_file'],
        $data['version'],
        $data['language'] ?? null,
        $data['time_limit'] ?? null,
        $data['description'] ?? null,
        $data['tags'],
        $data['mobile_support'],
        $data['created_by']
    ]);
}

// ✅ Update Audio Package
public function updateAudioPackage($id, $data)
{
    if (empty($id)) {
        return false;
    }

    $stmt = $this->conn->prepare("
        UPDATE audio_package SET
            title = ?,
            audio_file = ?,
            version = ?,
            language = ?,
            time_limit = ?,
            description = ?,
            tags = ?,
            mobile_support = ?,
            updated_by = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    return $stmt->execute([
        $data['title'],
        $data['audio_file'],
        $data['version'],
        $data['language'] ?? null,
        $data['time_limit'] ?? null,
        $data['description'] ?? null,
        $data['tags'],
        $data['mobile_support'],
        $data['updated_by'] ?? $data['created_by'], // fallback to creator if editor not set
        $id
    ]);
}

// ✅ Get Audio Packages (non-deleted) with Language Names
public function getAudioPackages()
{
    $stmt = $this->conn->prepare("
        SELECT a.*, l.language_name
        FROM audio_package a
        LEFT JOIN languages l ON a.language = l.id
        WHERE a.is_deleted = 0
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add can_edit and can_delete flags
    $currentUser = $_SESSION['user'] ?? null;
    if (!$currentUser) {
        foreach ($data as &$package) {
            $package['can_edit'] = 0;
            $package['can_delete'] = 0;
        }
        unset($package);
        return $data;
    }
    foreach ($data as &$package) {
        $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
    }
    unset($package);

    return $data;
}

// ✅ Soft Delete Audio Package
public function deleteAudioPackage($id)
{
    $stmt = $this->conn->prepare("UPDATE audio_package SET is_deleted = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

// ✅ Insert Video Package
public function insertVideoPackage($data)
{
    // Validate required fields
    $requiredFields = ['client_id', 'title', 'video_file', 'version', 'mobile_support', 'tags', 'created_by'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }

    $stmt = $this->conn->prepare("
        INSERT INTO video_package
        (client_id, title, video_file, version, language, time_limit, description, tags, mobile_support, created_by, is_deleted, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");

    return $stmt->execute([
        $data['client_id'],
        $data['title'],
        $data['video_file'],
        $data['version'],
        $data['language'] ?? null,
        $data['time_limit'] ?? null,
        $data['description'] ?? null,
        $data['tags'],
        $data['mobile_support'],
        $data['created_by']
    ]);
}

// ✅ Update Video Package
public function updateVideoPackage($id, $data)
{
    if (empty($id)) {
        return false;
    }

    $stmt = $this->conn->prepare("
        UPDATE video_package SET
            title = ?,
            video_file = ?,
            version = ?,
            language = ?,
            time_limit = ?,
            description = ?,
            tags = ?,
            mobile_support = ?,
            updated_by = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    return $stmt->execute([
        $data['title'],
        $data['video_file'],
        $data['version'],
        $data['language'] ?? null,
        $data['time_limit'] ?? null,
        $data['description'] ?? null,
        $data['tags'],
        $data['mobile_support'],
        $data['updated_by'] ?? $data['created_by'],
        $id
    ]);
}

// ✅ Get Video Packages (non-deleted) with Language Names
public function getVideoPackages()
{
    $stmt = $this->conn->prepare("
        SELECT v.*, l.language_name
        FROM video_package v
        LEFT JOIN languages l ON v.language = l.id
        WHERE v.is_deleted = 0
        ORDER BY v.created_at DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add can_edit and can_delete flags
    $currentUser = $_SESSION['user'] ?? null;
    if (!$currentUser) {
        foreach ($data as &$package) {
            $package['can_edit'] = 0;
            $package['can_delete'] = 0;
        }
        unset($package);
        return $data;
    }
    foreach ($data as &$package) {
        $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
    }
    unset($package);

    return $data;
}

// ✅ Soft Delete Video Package
public function deleteVideoPackage($id)
{
    $stmt = $this->conn->prepare("UPDATE video_package SET is_deleted = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

// ✅ Insert Image Package
public function insertImagePackage($data)
{
    // Validate required fields
    $requiredFields = ['client_id', 'title', 'image_file', 'version', 'mobile_support', 'tags', 'created_by'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }

    $stmt = $this->conn->prepare("
        INSERT INTO image_package
        (client_id, title, image_file, version, language, description, tags, mobile_support, created_by, is_deleted, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
    ");

    return $stmt->execute([
        $data['client_id'],
        $data['title'],
        $data['image_file'],
        $data['version'],
        $data['language'] ?? null,
        $data['description'] ?? null,
        $data['tags'],
        $data['mobile_support'],
        $data['created_by']
    ]);
}

// ✅ Update Image Package
public function updateImagePackage($id, $data)
{
    if (empty($id)) {
        return false;
    }

    $stmt = $this->conn->prepare("
        UPDATE image_package SET
            title = ?,
            image_file = ?,
            version = ?,
            language = ?,
            description = ?,
            tags = ?,
            mobile_support = ?,
            updated_by = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    return $stmt->execute([
        $data['title'],
        $data['image_file'],
        $data['version'],
        $data['language'] ?? null,
        $data['description'] ?? null,
        $data['tags'],
        $data['mobile_support'],
        $data['updated_by'] ?? $data['created_by'],
        $id
    ]);
}

// ✅ Get Image Packages (non-deleted) with Language Names
public function getImagePackages()
{
    $stmt = $this->conn->prepare("
        SELECT i.*, l.language_name
        FROM image_package i
        LEFT JOIN languages l ON i.language = l.id
        WHERE i.is_deleted = 0
        ORDER BY i.created_at DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add can_edit and can_delete flags
    $currentUser = $_SESSION['user'] ?? null;
    if (!$currentUser) {
        foreach ($data as &$package) {
            $package['can_edit'] = 0;
            $package['can_delete'] = 0;
        }
        unset($package);
        return $data;
    }
    foreach ($data as &$package) {
        $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
    }
    unset($package);

    return $data;
}

// ✅ Soft Delete Image Package
public function deleteImagePackage($id)
{
    $stmt = $this->conn->prepare("UPDATE image_package SET is_deleted = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

    // Save Survey with Questions
    public function saveSurveyWithQuestions($data)
    {
        try {
            $this->conn->beginTransaction();

            // Insert into survey_package
            $stmt = $this->conn->prepare("INSERT INTO survey_package (client_id, title, tags, created_by, created_at, is_deleted) VALUES (?, ?, ?, ?, NOW(), 0)");
            $stmt->execute([
                $data['client_id'],
                $data['title'],
                $data['tags'],
                $data['created_by']
            ]);

            $surveyId = $this->conn->lastInsertId();

            // Insert survey-question mappings
            if (!empty($data['question_ids'])) {
                $stmt = $this->conn->prepare("INSERT INTO survey_question_mapping (survey_package_id, survey_question_id, created_by, created_at) VALUES (?, ?, ?, NOW())");
                foreach ($data['question_ids'] as $questionId) {
                    $stmt->execute([$surveyId, $questionId, $data['created_by']]);
                }
            }

            $this->conn->commit();
            return $surveyId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }


    //Update Survey with Questions
    public function updateSurveyWithQuestions($data, $surveyId)
    {
        try {
            $this->conn->beginTransaction();

            // Update survey_package table
            $stmt = $this->conn->prepare("UPDATE survey_package SET client_id = ?, title = ?, tags = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $data['client_id'],
                $data['title'],
                $data['tags'],
                $data['created_by'],
                $surveyId
            ]);

            // Remove old question mappings
            $this->conn->prepare("DELETE FROM survey_question_mapping WHERE survey_package_id = ?")->execute([$surveyId]);

            // Insert new question mappings
            if (!empty($data['question_ids'])) {
                $stmt = $this->conn->prepare("INSERT INTO survey_question_mapping (survey_package_id, survey_question_id, created_by, created_at) VALUES (?, ?, ?, NOW())");
                foreach ($data['question_ids'] as $questionId) {
                    $stmt->execute([$surveyId, $questionId, $data['created_by']]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }


    //Get All Surveys (excluding deleted)
    public function getAllSurvey($clientId = null)
    {
        if ($clientId) {
            $stmt = $this->conn->prepare("SELECT * FROM survey_package WHERE client_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
            $stmt->execute([$clientId]);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM survey_package WHERE is_deleted = 0 ORDER BY created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add can_edit and can_delete flags
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            foreach ($data as &$package) {
                $package['can_edit'] = 0;
                $package['can_delete'] = 0;
            }
            unset($package);
            return $data;
        }
        foreach ($data as &$package) {
            $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        }
        unset($package);

        return $data;
    }

    //Get Survey by ID with Questions
    public function getSurveyByIdWithQuestions($surveyId, $clientId = null)
    {
        // Get survey basic info
        if ($clientId) {
            $stmt = $this->conn->prepare("SELECT * FROM survey_package WHERE id = ? AND client_id = ? AND is_deleted = 0");
            $stmt->execute([$surveyId, $clientId]);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM survey_package WHERE id = ? AND is_deleted = 0");
            $stmt->execute([$surveyId]);
        }
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$survey) {
            return false;
        }

        // Get mapped questions with full details (similar to assessment)
        $stmt = $this->conn->prepare("
            SELECT q.id, q.title, q.tags, q.type
            FROM survey_question_mapping m
            JOIN survey_questions q ON m.survey_question_id = q.id
            WHERE m.survey_package_id = ? AND q.is_deleted = 0
        ");
        $stmt->execute([$surveyId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add questions to survey array (like assessment does)
        $survey['selected_questions'] = $questions;

        return $survey;
    }

    //Delete Survey (soft delete)
    public function deleteSurvey($id)
    {
        $stmt = $this->conn->prepare("UPDATE survey_package SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Feedback Package Methods (following survey pattern)

    //Get All Feedback Packages (excluding deleted)
    public function getAllFeedback($clientId = null)
    {
        if ($clientId) {
            $stmt = $this->conn->prepare("SELECT * FROM feedback_package WHERE client_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
            $stmt->execute([$clientId]);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM feedback_package WHERE is_deleted = 0 ORDER BY created_at DESC");
            $stmt->execute();
        }
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add can_edit and can_delete flags
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            foreach ($data as &$package) {
                $package['can_edit'] = 0;
                $package['can_delete'] = 0;
            }
            unset($package);
            return $data;
        }
        foreach ($data as &$package) {
            $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        }
        unset($package);

        return $data;
    }

    //Get Feedback by ID with Questions
    public function getFeedbackByIdWithQuestions($feedbackId)
    {
        // Get feedback basic info
        $stmt = $this->conn->prepare("SELECT * FROM feedback_package WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$feedbackId]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$feedback) {
            return false;
        }

        // Get mapped questions with full details (similar to survey)
        $stmt = $this->conn->prepare("
            SELECT q.id, q.title, q.tags, q.type
            FROM feedback_question_mapping m
            JOIN feedback_questions q ON m.feedback_question_id = q.id
            WHERE m.feedback_package_id = ? AND q.is_deleted = 0
        ");
        $stmt->execute([$feedbackId]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add questions to feedback array (like survey does)
        $feedback['selected_questions'] = $questions;

        return $feedback;
    }

    // Save Feedback with Questions
    public function saveFeedbackWithQuestions($data)
    {
        try {
            $this->conn->beginTransaction();

            // Insert into feedback_package
            $stmt = $this->conn->prepare("INSERT INTO feedback_package (client_id, title, tags, created_by, created_at, is_deleted) VALUES (?, ?, ?, ?, NOW(), 0)");
            $stmt->execute([
                $data['client_id'],
                $data['title'],
                $data['tags'],
                $data['created_by']
            ]);

            $feedbackId = $this->conn->lastInsertId();

            // Insert feedback-question mappings
            if (!empty($data['question_ids'])) {
                $stmt = $this->conn->prepare("INSERT INTO feedback_question_mapping (feedback_package_id, feedback_question_id, created_by, created_at) VALUES (?, ?, ?, NOW())");
                foreach ($data['question_ids'] as $questionId) {
                    $stmt->execute([$feedbackId, $questionId, $data['created_by']]);
                }
            }

            $this->conn->commit();
            return $feedbackId;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    //Update Feedback with Questions
    public function updateFeedbackWithQuestions($data, $feedbackId)
    {
        try {
            $this->conn->beginTransaction();

            // Update feedback_package table
            $stmt = $this->conn->prepare("UPDATE feedback_package SET client_id = ?, title = ?, tags = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $data['client_id'],
                $data['title'],
                $data['tags'],
                $data['created_by'],
                $feedbackId
            ]);

            // Remove old question mappings
            $this->conn->prepare("DELETE FROM feedback_question_mapping WHERE feedback_package_id = ?")->execute([$feedbackId]);

            // Insert new question mappings
            if (!empty($data['question_ids'])) {
                $stmt = $this->conn->prepare("INSERT INTO feedback_question_mapping (feedback_package_id, feedback_question_id, created_by, created_at) VALUES (?, ?, ?, NOW())");
                foreach ($data['question_ids'] as $questionId) {
                    $stmt->execute([$feedbackId, $questionId, $data['created_by']]);
                }
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    //Delete Feedback (soft delete)
    public function deleteFeedback($id)
    {
        $stmt = $this->conn->prepare("UPDATE feedback_package SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ✅ Interactive & AI Powered Content Methods

    // Insert Interactive & AI Powered Content Package
    public function insertInteractiveContent($data)
    {
        error_log("VLRModel: insertInteractiveContent called with data: " . print_r($data, true));
        
        // Validate required fields
        $requiredFields = ['title', 'content_type', 'version', 'mobile_support', 'tags', 'created_by', 'client_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                error_log("VLRModel: Required field missing: " . $field);
                return false;
            }
        }
        
        error_log("VLRModel: All required fields present");

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO interactive_ai_content_package
                (client_id, title, content_type, description, tags, version, language, time_limit, mobile_support,
                 content_url, embed_code, ai_model, interaction_type, difficulty_level, learning_objectives,
                 prerequisites, content_file, thumbnail_image, metadata_file, vr_platform, ar_platform,
                 device_requirements, tutor_personality, response_style, knowledge_domain, adaptation_algorithm,
                 assessment_integration, progress_tracking, created_by, is_deleted, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ");

            $result = $stmt->execute([
                $data['client_id'],
                $data['title'],
                $data['content_type'],
                $data['description'] ?? null,
                $data['tags'],
                $data['version'],
                $data['language'] ?? null,
                $data['time_limit'] ?? null,
                $data['mobile_support'],
                $data['content_url'] ?? null,
                $data['embed_code'] ?? null,
                $data['ai_model'] ?? null,
                $data['interaction_type'] ?? null,
                $data['difficulty_level'] ?? null,
                $data['learning_objectives'] ?? null,
                $data['prerequisites'] ?? null,
                $data['content_file'] ?? null,
                $data['thumbnail_image'] ?? null,
                $data['metadata_file'] ?? null,
                $data['vr_platform'] ?? null,
                $data['ar_platform'] ?? null,
                $data['device_requirements'] ?? null,
                $data['tutor_personality'] ?? null,
                $data['response_style'] ?? null,
                $data['knowledge_domain'] ?? null,
                $data['adaptation_algorithm'] ?? null,
                $data['assessment_integration'] ?? null,
                $data['progress_tracking'] ?? null,
                $data['created_by']
            ]);
            
            if ($result) {
                error_log("VLRModel: Insert successful, last insert ID: " . $this->conn->lastInsertId());
                return $this->conn->lastInsertId();
            } else {
                error_log("VLRModel: Insert failed, error info: " . print_r($stmt->errorInfo(), true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("VLRModel: PDO Exception in insertInteractiveContent: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("VLRModel: General Exception in insertInteractiveContent: " . $e->getMessage());
            return false;
        }
    }

    // Update Interactive & AI Powered Content Package
    public function updateInteractiveContent($id, $data)
    {
        if (empty($id)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE interactive_ai_content_package SET
                title = ?,
                content_type = ?,
                description = ?,
                tags = ?,
                version = ?,
                language = ?,
                time_limit = ?,
                mobile_support = ?,
                content_url = ?,
                embed_code = ?,
                ai_model = ?,
                interaction_type = ?,
                difficulty_level = ?,
                learning_objectives = ?,
                prerequisites = ?,
                content_file = ?,
                thumbnail_image = ?,
                metadata_file = ?,
                vr_platform = ?,
                ar_platform = ?,
                device_requirements = ?,
                tutor_personality = ?,
                response_style = ?,
                knowledge_domain = ?,
                adaptation_algorithm = ?,
                assessment_integration = ?,
                progress_tracking = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['title'],
            $data['content_type'],
            $data['description'] ?? null,
            $data['tags'],
            $data['version'],
            $data['language'] ?? null,
            $data['time_limit'] ?? null,
            $data['mobile_support'],
            $data['content_url'] ?? null,
            $data['embed_code'] ?? null,
            $data['ai_model'] ?? null,
            $data['interaction_type'] ?? null,
            $data['difficulty_level'] ?? null,
            $data['learning_objectives'] ?? null,
            $data['prerequisites'] ?? null,
            $data['content_file'] ?? null,
            $data['thumbnail_image'] ?? null,
            $data['metadata_file'] ?? null,
            $data['vr_platform'] ?? null,
            $data['ar_platform'] ?? null,
            $data['device_requirements'] ?? null,
            $data['tutor_personality'] ?? null,
            $data['response_style'] ?? null,
            $data['knowledge_domain'] ?? null,
            $data['adaptation_algorithm'] ?? null,
            $data['assessment_integration'] ?? null,
            $data['progress_tracking'] ?? null,
            $data['created_by'],
            $id
        ]);
    }

    // Get Interactive & AI Powered Content Packages (non-deleted)
    public function getInteractiveContent()
    {
        $stmt = $this->conn->prepare("SELECT * FROM interactive_ai_content_package WHERE is_deleted = 0 ORDER BY created_at DESC");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add can_edit and can_delete flags
        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            foreach ($data as &$package) {
                $package['can_edit'] = 0;
                $package['can_delete'] = 0;
            }
            unset($package);
            return $data;
        }
        foreach ($data as &$package) {
            $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
        }
        unset($package);

        return $data;
    }

    // Soft Delete Interactive & AI Powered Content Package
    public function deleteInteractiveContent($id, $clientId = null)
    {
        $sql = "UPDATE interactive_ai_content_package SET is_deleted = 1 WHERE id = ?";
        $params = [$id];

        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // ✅ Non-SCORM Package Methods

    // Insert Non-SCORM Package
    public function insertNonScormPackage($data)
    {
        // Validate required fields
        $requiredFields = ['title', 'content_type', 'version', 'mobile_support', 'tags', 'created_by', 'client_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        $stmt = $this->conn->prepare("
            INSERT INTO non_scorm_package
            (client_id, title, content_type, description, tags, version, language, time_limit, mobile_support,
             content_url, launch_file, content_package, thumbnail_image, manifest_file,
             html5_framework, responsive_design, offline_support, flash_version, flash_security,
             unity_version, unity_platform, unity_compression, web_technologies, browser_requirements,
             external_dependencies, mobile_platform, app_store_url, minimum_os_version,
             progress_tracking, assessment_integration, completion_criteria, scoring_method,
             file_size, bandwidth_requirement, screen_resolution, created_by, is_deleted, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");

        return $stmt->execute([
            $data['client_id'],
            $data['title'],
            $data['content_type'],
            $data['description'] ?? null,
            $data['tags'],
            $data['version'],
            $data['language'] ?? null,
            $data['time_limit'] ?? null,
            $data['mobile_support'],
            $data['content_url'] ?? null,
            $data['launch_file'] ?? null,
            $data['content_package'] ?? null,
            $data['thumbnail_image'] ?? null,
            $data['manifest_file'] ?? null,
            $data['html5_framework'] ?? null,
            $data['responsive_design'] ?? null,
            $data['offline_support'] ?? null,
            $data['flash_version'] ?? null,
            $data['flash_security'] ?? null,
            $data['unity_version'] ?? null,
            $data['unity_platform'] ?? null,
            $data['unity_compression'] ?? null,
            $data['web_technologies'] ?? null,
            $data['browser_requirements'] ?? null,
            $data['external_dependencies'] ?? null,
            $data['mobile_platform'] ?? null,
            $data['app_store_url'] ?? null,
            $data['minimum_os_version'] ?? null,
            $data['progress_tracking'] ?? null,
            $data['assessment_integration'] ?? null,
            $data['completion_criteria'] ?? null,
            $data['scoring_method'] ?? null,
            $data['file_size'] ?? null,
            $data['bandwidth_requirement'] ?? null,
            $data['screen_resolution'] ?? null,
            $data['created_by']
        ]);
    }

    // Update Non-SCORM Package
    public function updateNonScormPackage($id, $data)
    {
        if (empty($id)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE non_scorm_package SET
                title = ?,
                content_type = ?,
                description = ?,
                tags = ?,
                version = ?,
                language = ?,
                time_limit = ?,
                mobile_support = ?,
                content_url = ?,
                launch_file = ?,
                content_package = ?,
                thumbnail_image = ?,
                manifest_file = ?,
                html5_framework = ?,
                responsive_design = ?,
                offline_support = ?,
                flash_version = ?,
                flash_security = ?,
                unity_version = ?,
                unity_platform = ?,
                unity_compression = ?,
                web_technologies = ?,
                browser_requirements = ?,
                external_dependencies = ?,
                mobile_platform = ?,
                app_store_url = ?,
                minimum_os_version = ?,
                progress_tracking = ?,
                assessment_integration = ?,
                completion_criteria = ?,
                scoring_method = ?,
                file_size = ?,
                bandwidth_requirement = ?,
                screen_resolution = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['title'],
            $data['content_type'],
            $data['description'] ?? null,
            $data['tags'],
            $data['version'],
            $data['language'] ?? null,
            $data['time_limit'] ?? null,
            $data['mobile_support'],
            $data['content_url'] ?? null,
            $data['launch_file'] ?? null,
            $data['content_package'] ?? null,
            $data['thumbnail_image'] ?? null,
            $data['manifest_file'] ?? null,
            $data['html5_framework'] ?? null,
            $data['responsive_design'] ?? null,
            $data['offline_support'] ?? null,
            $data['flash_version'] ?? null,
            $data['flash_security'] ?? null,
            $data['unity_version'] ?? null,
            $data['unity_platform'] ?? null,
            $data['unity_compression'] ?? null,
            $data['web_technologies'] ?? null,
            $data['browser_requirements'] ?? null,
            $data['external_dependencies'] ?? null,
            $data['mobile_platform'] ?? null,
            $data['app_store_url'] ?? null,
            $data['minimum_os_version'] ?? null,
            $data['progress_tracking'] ?? null,
            $data['assessment_integration'] ?? null,
            $data['completion_criteria'] ?? null,
            $data['scoring_method'] ?? null,
            $data['file_size'] ?? null,
            $data['bandwidth_requirement'] ?? null,
            $data['screen_resolution'] ?? null,
            $data['created_by'],
            $id
        ]);
    }

    // Get Non-SCORM Packages (non-deleted)
    public function getNonScormPackages()
    {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM non_scorm_package WHERE is_deleted = 0 ORDER BY created_at DESC");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add can_edit and can_delete flags
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                foreach ($data as &$package) {
                    $package['can_edit'] = 0;
                    $package['can_delete'] = 0;
                }
                unset($package);
                return $data;
            }
            foreach ($data as &$package) {
                $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
                $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            }
            unset($package);

            return $data;
        } catch (Exception $e) {
            error_log("Error fetching Non-SCORM packages: " . $e->getMessage());
            return [];
        }
    }

    // Soft Delete Non-SCORM Package
    public function deleteNonScormPackage($id)
    {
        $stmt = $this->conn->prepare("UPDATE non_scorm_package SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ✅ Insert Assignment Package
    public function insertAssignmentPackage($data)
    {
        // Validate required fields
        $requiredFields = ['client_id', 'title', 'assignment_file', 'version', 'mobile_support', 'tags', 'created_by'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }

        $stmt = $this->conn->prepare("
            INSERT INTO assignment_package
            (client_id, title, assignment_file, version, language, time_limit, description, tags, mobile_support, 
             assignment_type, difficulty_level, estimated_duration, max_attempts, passing_score, submission_format,
             allow_late_submission, late_submission_penalty, instructions, requirements, rubric, learning_objectives,
             prerequisites, thumbnail_image, sample_solution, supporting_materials, created_by, is_deleted, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");

        return $stmt->execute([
            $data['client_id'],
            $data['title'],
            $data['assignment_file'],
            $data['version'],
            $data['language'] ?? null,
            $data['time_limit'] ?? null,
            $data['description'] ?? null,
            $data['tags'],
            $data['mobile_support'],
            $data['assignment_type'] ?? 'individual',
            $data['difficulty_level'] ?? 'Beginner',
            $data['estimated_duration'] ?? null,
            $data['max_attempts'] ?? 1,
            $data['passing_score'] ?? null,
            $data['submission_format'] ?? 'file_upload',
            $data['allow_late_submission'] ?? 'No',
            $data['late_submission_penalty'] ?? 0,
            $data['instructions'] ?? null,
            $data['requirements'] ?? null,
            $data['rubric'] ?? null,
            $data['learning_objectives'] ?? null,
            $data['prerequisites'] ?? null,
            $data['thumbnail_image'] ?? null,
            $data['sample_solution'] ?? null,
            $data['supporting_materials'] ?? null,
            $data['created_by']
        ]);
    }

    // ✅ Update Assignment Package
    public function updateAssignmentPackage($id, $data)
    {
        if (empty($id)) {
            return false;
        }

        $stmt = $this->conn->prepare("
            UPDATE assignment_package SET
                title = ?,
                assignment_file = ?,
                version = ?,
                language = ?,
                time_limit = ?,
                description = ?,
                tags = ?,
                mobile_support = ?,
                assignment_type = ?,
                difficulty_level = ?,
                estimated_duration = ?,
                max_attempts = ?,
                passing_score = ?,
                submission_format = ?,
                allow_late_submission = ?,
                late_submission_penalty = ?,
                instructions = ?,
                requirements = ?,
                rubric = ?,
                learning_objectives = ?,
                prerequisites = ?,
                thumbnail_image = ?,
                sample_solution = ?,
                supporting_materials = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['title'],
            $data['assignment_file'],
            $data['version'],
            $data['language'] ?? null,
            $data['time_limit'] ?? null,
            $data['description'] ?? null,
            $data['tags'],
            $data['mobile_support'],
            $data['assignment_type'] ?? 'individual',
            $data['difficulty_level'] ?? 'Beginner',
            $data['estimated_duration'] ?? null,
            $data['max_attempts'] ?? 1,
            $data['passing_score'] ?? null,
            $data['submission_format'] ?? 'file_upload',
            $data['allow_late_submission'] ?? 'No',
            $data['late_submission_penalty'] ?? 0,
            $data['instructions'] ?? null,
            $data['requirements'] ?? null,
            $data['rubric'] ?? null,
            $data['learning_objectives'] ?? null,
            $data['prerequisites'] ?? null,
            $data['thumbnail_image'] ?? null,
            $data['sample_solution'] ?? null,
            $data['supporting_materials'] ?? null,
            $data['updated_by'] ?? $data['created_by'], // fallback to creator if editor not set
            $id
        ]);
    }

    // ✅ Get Assignment Packages (non-deleted) with Language Names
    public function getAssignmentPackages($clientId = null)
    {
        try {
            if ($clientId) {
                $stmt = $this->conn->prepare("
                    SELECT a.*, l.language_name
                    FROM assignment_package a
                    LEFT JOIN languages l ON a.language = l.id
                    WHERE a.client_id = ? AND a.is_deleted = 0
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute([$clientId]);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT a.*, l.language_name
                    FROM assignment_package a
                    LEFT JOIN languages l ON a.language = l.id
                    WHERE a.is_deleted = 0
                    ORDER BY a.created_at DESC
                ");
                $stmt->execute();
            }
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add can_edit and can_delete flags
            $currentUser = $_SESSION['user'] ?? null;
            if (!$currentUser) {
                foreach ($data as &$package) {
                    $package['can_edit'] = 0;
                    $package['can_delete'] = 0;
                }
                unset($package);
                return $data;
            }
            foreach ($data as &$package) {
                $package['can_edit'] = (canEdit('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
                $package['can_delete'] = (canDelete('vlr') && ($currentUser['system_role'] === 'super_admin' || $package['created_by'] == $currentUser['id'])) ? 1 : 0;
            }
            unset($package);

            error_log("VLRModel getAssignmentPackages - Client ID: " . ($clientId ?? 'null') . ", Count: " . count($data));
            return $data;
        } catch (Exception $e) {
            error_log("Error fetching Assignment packages: " . $e->getMessage());
            return [];
        }
    }

    // ✅ Soft Delete Assignment Package
    public function deleteAssignmentPackage($id)
    {
        $stmt = $this->conn->prepare("UPDATE assignment_package SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

}