<?php
require_once 'config/Database.php';

class SurveyQuestionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Save a new survey question
    public function saveQuestion($data) {
        $sql = "INSERT INTO survey_questions 
                (title, type, media_path, rating_scale, rating_symbol, tags, created_by, created_at, updated_by, updated_at, is_deleted)
                VALUES 
                (:title, :type, :media_path, :rating_scale, :rating_symbol, :tags, :created_by, NOW(), :created_by, NOW(), 0)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'type' => $data['type'],
            'media_path' => $data['media_path'],
            'rating_scale' => $data['rating_scale'],
            'rating_symbol' => $data['rating_symbol'],
            'tags' => $data['tags'],
            'created_by' => $data['created_by'],
        ]);
    
        return $this->conn->lastInsertId();
    }
    
    // Save options for a given question (with media uploads)
    public function saveOptions($questionId, $options, $optionMedias, $createdBy) {
        $count = count($options);
        for ($i = 0; $i < $count; $i++) {
            $text = trim($options[$i]);
            if ($text === '') continue;

            $mediaName = null;

            if (!empty($optionMedias['name'][$i])) {
                $file = [
                    'name' => $optionMedias['name'][$i],
                    'type' => $optionMedias['type'][$i],
                    'tmp_name' => $optionMedias['tmp_name'][$i],
                    'error' => $optionMedias['error'][$i],
                    'size' => $optionMedias['size'][$i],
                ];

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'application/pdf'];
                if (in_array($file['type'], $allowedTypes)) {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $randomName = bin2hex(random_bytes(10)) . '.' . $ext;
                    $uploadDir = "uploads/survey/";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                    $targetPath = $uploadDir . $randomName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $mediaName = $randomName; // store only filename
                    }
                }
            }

            $sql = "INSERT INTO survey_question_options 
                    (question_id, option_text, media_path, created_by, created_at, updated_by, updated_at, is_deleted)
                    VALUES 
                    (:question_id, :option_text, :media_path, :created_by, NOW(), :created_by, NOW(), 0)";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'question_id' => $questionId,
                'option_text' => $text,
                'media_path' => $mediaName,
                'created_by' => $createdBy,
            ]);
        }
    }

    // Fetch paginated questions with optional search and type filter
    // NOTE: adjusted parameters order to match controller: ($search, $type, $limit, $offset)
    public function getQuestions($search = '', $type = '', $limit = 10, $offset = 0) {
        $params = [];
        $sql = "SELECT id, title, type, tags 
                FROM survey_questions 
                WHERE is_deleted = 0 ";

        if ($search !== '') {
            $sql .= " AND title LIKE ? ";
            $params[] = '%' . $search . '%';
        }

        if ($type !== '') {
            $sql .= " AND type = ? ";
            $params[] = $type;
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $index => $param) {
            $typeParam = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($index + 1, $param, $typeParam);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total number of questions (for pagination), with optional search and type filter
    public function getTotalQuestionCount($search = '', $type = '') {
        $params = [];
        $sql = "SELECT COUNT(*) FROM survey_questions WHERE is_deleted = 0 ";

        if ($search !== '') {
            $sql .= " AND title LIKE ? ";
            $params[] = '%' . $search . '%';
        }

        if ($type !== '') {
            $sql .= " AND type = ? ";
            $params[] = $type;
        }

        $stmt = $this->conn->prepare($sql);

        foreach ($params as $index => $param) {
            $typeParam = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($index + 1, $param, $typeParam);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Get distinct question types for filters
    public function getDistinctTypes() {
        $sql = "SELECT DISTINCT type FROM survey_questions WHERE is_deleted = 0 ORDER BY type ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get question details by IDs (for loading selected questions back)
    public function getSelectedQuestions(array $ids) {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, title, type, tags FROM survey_questions WHERE id IN ($placeholders) AND is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        foreach ($ids as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Soft delete question by setting is_deleted = 1
    public function deleteQuestion($id) {
        $sql = "UPDATE survey_questions SET is_deleted = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
