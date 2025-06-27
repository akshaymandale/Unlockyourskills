<?php
require_once 'config/Database.php';

class QuestionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Insert a new question
    public function insertQuestion($data) {
        try {
            $sql = "
                INSERT INTO assessment_questions
                (client_id, question_text, tags, competency_skills, level, marks, status, question_type, answer_count, media_type, media_file, created_by)
                VALUES
                (:client_id, :question_text, :tags, :competency_skills, :level, :marks, :status, :question_type, :answer_count, :media_type, :media_file, :created_by)
            ";
            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':question_text', $data['question_text']);
            $stmt->bindParam(':tags', $data['tags']);
            $stmt->bindParam(':competency_skills', $data['competency_skills']);
            $stmt->bindParam(':level', $data['level']);
            $stmt->bindParam(':marks', $data['marks']);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':question_type', $data['question_type']);
            $stmt->bindParam(':answer_count', $data['answer_count']);
            $stmt->bindParam(':media_type', $data['media_type']);
            $stmt->bindParam(':media_file', $data['media_file']);
            $stmt->bindParam(':created_by', $data['created_by']);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }

            // Log the error for debugging
            $errorInfo = $stmt->errorInfo();
            error_log("Question insert failed: " . print_r($errorInfo, true));
            error_log("Question data: " . print_r($data, true));
            return false;

        } catch (PDOException $e) {
            error_log("Question insert exception: " . $e->getMessage());
            error_log("Question data: " . print_r($data, true));
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    // Insert an option for a question
    public function insertOption($data) {
        try {
            $sql = "
                INSERT INTO assessment_options
                (client_id, question_id, option_index, option_text, is_correct)
                VALUES
                (:client_id, :question_id, :option_index, :option_text, :is_correct)
            ";
            $stmt = $this->conn->prepare($sql);

            $stmt->bindParam(':client_id', $data['client_id']);
            $stmt->bindParam(':question_id', $data['question_id']);
            $stmt->bindParam(':option_index', $data['option_index']);
            $stmt->bindParam(':option_text', $data['option_text']);
            $stmt->bindParam(':is_correct', $data['is_correct']);

            if ($stmt->execute()) {
                return true;
            }

            // Log the error for debugging
            $errorInfo = $stmt->errorInfo();
            error_log("Option insert failed: " . print_r($errorInfo, true));
            error_log("Option data: " . print_r($data, true));
            return false;

        } catch (PDOException $e) {
            error_log("Option insert exception: " . $e->getMessage());
            error_log("Option data: " . print_r($data, true));
            throw new Exception("Database error inserting option: " . $e->getMessage());
        }
    }

    // Update a question
    public function updateQuestion($id, $data) {
        // Ensure the question exists before updating options
        if (!$this->checkQuestionExists($id)) {
            throw new Exception("Question ID does not exist or is deleted.");
        }

        $sql = "UPDATE assessment_questions SET 
            question_text = :question_text,
            tags = :tags,
            competency_skills = :competency_skills,
            level = :level,
            marks = :marks,
            status = :status,
            question_type = :question_type,
            answer_count = :answer_count,
            media_type = :media_type,
            media_file = :media_file,
            updated_at = NOW()
            WHERE id = :id AND is_deleted = 0";

        $stmt = $this->conn->prepare($sql);

        return $stmt->execute([
            ':question_text' => $data['question_text'],
            ':tags' => $data['tags'],
            ':competency_skills' => $data['competency_skills'],
            ':level' => $data['level'],
            ':marks' => $data['marks'],
            ':status' => $data['status'],
            ':question_type' => $data['question_type'],
            ':answer_count' => $data['answer_count'],
            ':media_type' => $data['media_type'],
            ':media_file' => $data['media_file'],
            ':id' => $id
        ]);
    }

    // Check if a question exists in the database
    public function checkQuestionExists($id) {
        $sql = "SELECT COUNT(*) FROM assessment_questions WHERE id = :id AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetchColumn() > 0;
    }

    // Delete options for a question
    public function deleteOptionsByQuestionId($questionId) {
        $sql = "DELETE FROM assessment_options WHERE question_id = :question_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':question_id' => $questionId]);
    }

    // Update an option (reuse insert logic)
    public function updateOption($data) {
        return $this->insertOption($data); // Reuse insert logic
    }

    // Retrieve questions with pagination, search and filters
    public function getQuestions($limit, $offset, $search = '', $filters = [], $clientId = null) {
        $whereConditions = ["is_deleted = 0"];
        $params = [];

        // Add client filtering
        if ($clientId !== null) {
            $whereConditions[] = "client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        // Add search condition
        if (!empty($search)) {
            $whereConditions[] = "(question_text LIKE :search OR tags LIKE :search OR competency_skills LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        // Add filter conditions
        if (!empty($filters['question_type'])) {
            $whereConditions[] = "question_type = :question_type";
            $params[':question_type'] = $filters['question_type'];
        }

        if (!empty($filters['difficulty'])) {
            $whereConditions[] = "level = :difficulty";
            $params[':difficulty'] = $filters['difficulty'];
        }

        if (!empty($filters['tags'])) {
            $whereConditions[] = "tags LIKE :tags";
            $params[':tags'] = '%' . $filters['tags'] . '%';
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT id, question_text AS title, question_type AS type, level AS difficulty, tags
                FROM assessment_questions
                WHERE $whereClause
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);

        // Bind search and filter parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get the total count of questions with search and filters (for pagination)
    public function getTotalQuestionCount($search = '', $filters = [], $clientId = null) {
        $whereConditions = ["is_deleted = 0"];
        $params = [];

        // Add client filtering
        if ($clientId !== null) {
            $whereConditions[] = "client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        // Add search condition
        if (!empty($search)) {
            $whereConditions[] = "(question_text LIKE :search OR tags LIKE :search OR competency_skills LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        // Add filter conditions
        if (!empty($filters['question_type'])) {
            $whereConditions[] = "question_type = :question_type";
            $params[':question_type'] = $filters['question_type'];
        }

        if (!empty($filters['difficulty'])) {
            $whereConditions[] = "level = :difficulty";
            $params[':difficulty'] = $filters['difficulty'];
        }

        if (!empty($filters['tags'])) {
            $whereConditions[] = "tags LIKE :tags";
            $params[':tags'] = '%' . $filters['tags'] . '%';
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT COUNT(*) as total FROM assessment_questions WHERE $whereClause";
        $stmt = $this->conn->prepare($sql);

        // Bind search and filter parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // Soft delete a question (mark as deleted)
    public function softDeleteQuestion($id) {
        error_log("[QuestionModel] Attempting to soft delete question ID: $id");
        
        try {
            $stmt = $this->conn->prepare("UPDATE assessment_questions SET is_deleted = 1 WHERE id = :id");
            $result = $stmt->execute([':id' => $id]);
            
            error_log("[QuestionModel] Soft delete query executed. Result: " . var_export($result, true));
            error_log("[QuestionModel] Rows affected: " . $stmt->rowCount());
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("[QuestionModel] Soft delete failed. Error info: " . print_r($errorInfo, true));
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("[QuestionModel] Soft delete exception: " . $e->getMessage());
            return false;
        }
    }

    // Get a specific question by ID
    public function getQuestionById($id, $clientId = null) {
        $sql = "SELECT * FROM assessment_questions WHERE id = :id AND is_deleted = 0";
        $params = [':id' => $id];

        if ($clientId !== null) {
            $sql .= " AND client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get options for a specific question
    public function getOptionsByQuestionId($questionId, $clientId = null) {
        $sql = "
            SELECT option_index, option_text, is_correct
            FROM assessment_options
            WHERE question_id = :question_id
        ";
        $params = [':question_id' => $questionId];

        if ($clientId !== null) {
            $sql .= " AND client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $sql .= " ORDER BY option_index ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get unique question types for filter dropdown
    public function getUniqueQuestionTypes($clientId = null) {
        $sql = "
            SELECT DISTINCT question_type
            FROM assessment_questions
            WHERE is_deleted = 0 AND question_type IS NOT NULL
        ";
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $sql .= " ORDER BY question_type ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get unique difficulty levels for filter dropdown
    public function getUniqueDifficultyLevels($clientId = null) {
        $sql = "
            SELECT DISTINCT level
            FROM assessment_questions
            WHERE is_deleted = 0 AND level IS NOT NULL
        ";
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $sql .= "
            ORDER BY
                CASE level
                    WHEN 'Low' THEN 1
                    WHEN 'Medium' THEN 2
                    WHEN 'Hard' THEN 3
                    ELSE 4
                END
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get unique tags for filter suggestions
    public function getUniqueTags($clientId = null) {
        $sql = "
            SELECT DISTINCT tags
            FROM assessment_questions
            WHERE is_deleted = 0 AND tags IS NOT NULL AND tags != ''
        ";
        $params = [];

        if ($clientId !== null) {
            $sql .= " AND client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $sql .= " ORDER BY tags ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $allTags = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Split comma-separated tags and get unique values
        $uniqueTags = [];
        foreach ($allTags as $tagString) {
            $tags = array_map('trim', explode(',', $tagString));
            foreach ($tags as $tag) {
                if (!empty($tag) && !in_array($tag, $uniqueTags)) {
                    $uniqueTags[] = $tag;
                }
            }
        }
        sort($uniqueTags);
        return $uniqueTags;
    }
}
