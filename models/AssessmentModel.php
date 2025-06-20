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

    public function getFilteredQuestions($search, $marks, $type, $limit, $offset)
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

        $sql = "SELECT * FROM assessment_questions WHERE " . implode(" AND ", $where);

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

    public function getFilteredQuestionCount($search, $marks, $type)
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

        $sql = "SELECT COUNT(*) FROM assessment_questions WHERE " . implode(" AND ", $where);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    public function getQuestionsByIds($ids)
    {
        if (empty($ids))
            return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM assessment_questions WHERE id IN ($placeholders) AND is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDistinctMarks()
    {
        $sql = "SELECT DISTINCT marks FROM assessment_questions ORDER BY marks ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getDistinctTypes()
    {
        $sql = "SELECT DISTINCT question_type FROM assessment_questions ORDER BY question_type ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

   
    
    
}
