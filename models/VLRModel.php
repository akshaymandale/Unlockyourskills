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
        if (empty($data['title']) || empty($data['zip_file']) || empty($data['version']) || empty($data['scorm_category']) || empty($data['mobile_support']) || empty($data['assessment'])) {
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO scorm_packages 
        (title, zip_file, description, tags, version, language, scorm_category, time_limit, mobile_support, assessment, created_by, is_deleted, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");

        return $stmt->execute([
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
            $data['created_by']
        ]);
    }

    // ✅ Update SCORM Package
    public function updateScormPackage($id, $data)
    {
        // Ensure SCORM ID exists
        if (empty($id)) {
            return false;
        }

        $stmt = $this->conn->prepare("UPDATE scorm_packages 
        SET title = ?, zip_file = ?, description = ?, tags = ?, version = ?, language = ?, scorm_category = ?, time_limit = ?, mobile_support = ?, assessment = ?, updated_at = NOW()
        WHERE id = ?");

        return $stmt->execute([
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
            $id
        ]);
    }

    // Get data for display on VLR 
    public function getScormPackages()
    {
        $stmt = $this->conn->prepare("SELECT * FROM scorm_packages WHERE is_deleted = 0");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs

        return $data;
    }

    // Delete respective SCROM 
    public function deleteScormPackage($id)
    {
        $stmt = $this->conn->prepare("UPDATE scorm_packages SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
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
                title, content_type, version_number, mobile_support, language_support, time_limit, 
                description, tags, video_url, thumbnail, course_url, platform_name, article_url, 
                author, audio_source, audio_url, audio_file, speaker, created_by
            ) VALUES (
                :title, :content_type, :version_number, :mobile_support, :language_support, :time_limit, 
                :description, :tags, :video_url, :thumbnail, :course_url, :platform_name, :article_url, 
                :author, :audio_source, :audio_url, :audio_file, :speaker, :created_by
            )";

            $stmt = $this->conn->prepare($sql);

            // Bind parameters
            $stmt->execute([
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

    public function updateExternalContent($id, $data)
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

            $stmt = $this->conn->prepare($sql);

            // Bind parameters
            $stmt->execute([
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
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            return false;
        }
    }

    // Get data for External Content
    public function getExternalContent()
    {
        $stmt = $this->conn->prepare("SELECT * FROM external_content WHERE is_deleted = 0");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs

        return $data;
    }

    // Delete respective External Content 
    public function deleteExternalContent($id)
    {
        $stmt = $this->conn->prepare("UPDATE external_content SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }


    // Documents package
    // Fetch all documents with language names

    // Get data for display on VLR 
    public function getAllDocuments()
    {
        $stmt = $this->conn->prepare("SELECT d.*, l.language_name FROM documents d 
                  LEFT JOIN languages l ON d.language_id = l.id 
                  WHERE d.is_deleted = 0");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs

        return $data;
    }


    // Fetch a single document by ID
    public function getDocumentById($id)
    {
        $query = "SELECT * FROM documents WHERE id = ? AND is_deleted = 0";
        return $this->conn->fetchOne($query, [$id]);
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

        $query = "INSERT INTO documents (title, category, description, tags, language_id, mobile_support, version_number, time_limit, 
              authors, publication_date, reference_links, created_by, created_at, is_deleted, word_excel_ppt_file, ebook_manual_file, research_file)
              VALUES (:title, :category, :description, :tags, :language_id, :mobile_support, :version_number, :time_limit, 
              :authors, :publication_date, :reference_links, :created_by, NOW(), 0, :word_excel_ppt_file, :ebook_manual_file, :research_file)";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':title' => $data['document_title'],
            ':category' => $data['documentCategory'],
            ':description' => $data['description'],
            ':tags' => $data['documentTagList'],
            ':language_id' => !empty($data['language']) ? (int) $data['language'] : null,
            ':mobile_support' => $data['mobile_support'],
            ':version_number' => $data['doc_version'],
            ':time_limit' => $data['doc_time_limit'],
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
    public function updateDocument($data, $id)
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

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':title' => $data['document_title'],
            ':category' => $data['documentCategory'],
            ':description' => $data['description'],
            ':tags' => $data['documentTagList'],
            ':language_id' => !empty($data['language']) ? (int) $data['language'] : null,
            ':mobile_support' => $data['mobile_support'],
            ':version_number' => $data['doc_version'],
            ':time_limit' => $data['doc_time_limit'],
            ':authors' => $data['research_authors'] ?? null,
            ':publication_date' => $data['research_publication_date'] ?? null,
            ':reference_links' => $data['research_references'] ?? null,
            ':word_excel_ppt_file' => $data['word_excel_ppt_file'] ?? null,
            ':ebook_manual_file' => $data['ebook_manual_file'] ?? null,
            ':research_file' => $data['research_file'] ?? null,
            ':id' => $id
        ]);

        return ['success' => true, 'message' => "Document updated successfully."];
    }

    // Soft delete a document
    public function deleteDocument($id)
    {
        $stmt = $this->conn->prepare("UPDATE documents SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
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
            title, tags, num_attempts, passing_percentage, time_limit,
            negative_marking, negative_marking_percentage,
            assessment_type, num_questions_to_display,
            selected_question_count, created_by, created_at
        ) VALUES (
            :title, :tags, :num_attempts, :passing_percentage, :time_limit,
            :negative_marking, :negative_marking_percentage,
            :assessment_type, :num_questions_to_display,
            :selected_question_count, :created_by, NOW()
        )";

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
            return true;

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

    public function getAllAssessments()
    {
        $stmt = $this->conn->prepare("SELECT * FROM assessment_package WHERE is_deleted = 0 ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    $requiredFields = ['title', 'audio_file', 'version', 'mobile_support', 'tags', 'created_by'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }

    $stmt = $this->conn->prepare("
        INSERT INTO audio_package 
        (title, audio_file, version, language, time_limit, description, tags, mobile_support, created_by, is_deleted, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
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

// ✅ Get Audio Packages (non-deleted)
public function getAudioPackages()
{
    $stmt = $this->conn->prepare("SELECT * FROM audio_package WHERE is_deleted = 0 ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ✅ Soft Delete Audio Package
public function deleteAudioPackage($id)
{
    $stmt = $this->conn->prepare("UPDATE audio_package SET is_deleted = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}



}
?>