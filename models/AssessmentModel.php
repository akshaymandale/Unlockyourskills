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

        $sql = "SELECT * FROM assessment_questions";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

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

        $sql = "SELECT COUNT(*) FROM assessment_questions";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn();
    }

    public function getQuestionsByIds($ids)
    {
        if (empty($ids))
            return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM assessment_questions WHERE id IN ($placeholders)";

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
    



}
