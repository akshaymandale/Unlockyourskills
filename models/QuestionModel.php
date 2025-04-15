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
        $sql = "
            INSERT INTO assessment_questions 
            (question_text, tags, competency_skills, level, marks, status, question_type, answer_count, media_type, media_file, created_by)
            VALUES 
            (:question_text, :tags, :competency_skills, :level, :marks, :status, :question_type, :answer_count, :media_type, :media_file, :created_by)
        ";
        $stmt = $this->conn->prepare($sql);

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

        return false;
    }

    // Insert an option for a question
    public function insertOption($data) {
        $sql = "
            INSERT INTO assessment_options 
            (question_id, option_index, option_text, is_correct)
            VALUES 
            (:question_id, :option_index, :option_text, :is_correct)
        ";
        $stmt = $this->conn->prepare($sql);

        $stmt->bindParam(':question_id', $data['question_id']);
        $stmt->bindParam(':option_index', $data['option_index']);
        $stmt->bindParam(':option_text', $data['option_text']);
        $stmt->bindParam(':is_correct', $data['is_correct']);

        return $stmt->execute();
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

    // Retrieve questions with pagination
    public function getQuestions($limit, $offset) {
        $sql = "SELECT id, question_text AS title, question_type AS type, level AS difficulty, tags, created_by
                FROM assessment_questions 
                WHERE is_deleted = 0 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get the total count of questions (for pagination)
    public function getTotalQuestionCount() {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM assessment_questions WHERE is_deleted = 0");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    // Soft delete a question (mark as deleted)
    public function softDeleteQuestion($id) {
        $stmt = $this->conn->prepare("UPDATE assessment_questions SET is_deleted = 1 WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Get a specific question by ID
    public function getQuestionById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM assessment_questions WHERE id = :id AND is_deleted = 0");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get options for a specific question
    public function getOptionsByQuestionId($questionId) {
        $stmt = $this->conn->prepare("
            SELECT option_index, option_text, is_correct 
            FROM assessment_options 
            WHERE question_id = :question_id 
            ORDER BY option_index ASC
        ");
        $stmt->execute([':question_id' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
