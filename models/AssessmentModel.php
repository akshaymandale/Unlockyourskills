<?php
require_once 'config/Database.php';

class AssessmentModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getFilteredQuestions($search, $marks, $type, $limit) {
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
    
        $sql = "SELECT * FROM assessment_questions";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
    
        // Append LIMIT safely — cast $limit to an int to prevent injection
        $limit = (int) $limit;
        $sql .= " LIMIT $limit";
    
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function getQuestionsByIds($ids) {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM assessment_questions WHERE id IN ($placeholders)";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
