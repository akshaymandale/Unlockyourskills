<?php
require_once 'config/Database.php';

class AssessmentModel
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getFilteredQuestions($search, $marks, $type, $limit, $offset, $clientId = null)
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "question_text LIKE :search";
            $params[':search'] = "%$search%";
        }

        if (!empty($marks)) {
            $where[] = "marks = :marks";
            $params[':marks'] = $marks;
        }

        if (!empty($type)) {
            $where[] = "question_type = :type";
            $params[':type'] = $type;
        }

        // Always exclude deleted questions
        $where[] = "is_deleted = 0";
        
        // Add client filtering if client_id is provided
        if ($clientId !== null) {
            $where[] = "client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $sql = "SELECT id, question_text, tags, marks, question_type, competency_skills, level, status FROM assessment_questions WHERE " . implode(" AND ", $where);

        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFilteredQuestionCount($search, $marks, $type, $clientId = null)
    {
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "question_text LIKE :search";
            $params[':search'] = "%$search%";
        }

        if (!empty($marks)) {
            $where[] = "marks = :marks";
            $params[':marks'] = $marks;
        }

        if (!empty($type)) {
            $where[] = "question_type = :type";
            $params[':type'] = $type;
        }

        // Always exclude deleted questions
        $where[] = "is_deleted = 0";
        
        // Add client filtering if client_id is provided
        if ($clientId !== null) {
            $where[] = "client_id = :client_id";
            $params[':client_id'] = $clientId;
        }

        $sql = "SELECT COUNT(*) FROM assessment_questions WHERE " . implode(" AND ", $where);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    public function getQuestionsByIds($ids, $clientId = null)
    {
        if (empty($ids))
            return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, question_text, tags, marks, question_type, competency_skills, level, status FROM assessment_questions WHERE id IN ($placeholders) AND is_deleted = 0";
        
        // Add client filtering if client_id is provided
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $ids[] = $clientId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctMarks($clientId = null)
    {
        $sql = "SELECT DISTINCT marks FROM assessment_questions WHERE is_deleted = 0";
        $params = [];
        
        // Add client filtering if client_id is provided
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY marks ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getDistinctTypes($clientId = null)
    {
        $sql = "SELECT DISTINCT question_type FROM assessment_questions WHERE is_deleted = 0";
        $params = [];
        
        // Add client filtering if client_id is provided
        if ($clientId !== null) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY question_type ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

   
    
    
}
