<?php
require_once 'config/Database.php';

class FeedbackQuestionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    // Save a new feedback question
    public function saveQuestion($data) {
        $sql = "INSERT INTO feedback_questions 
                (title, type, media_path, rating_scale, rating_symbol, tags, created_by, created_at, updated_by, updated_at, is_deleted)
                VALUES 
                (:title, :type, :media_path, :rating_scale, :rating_symbol, :tags, :created_by, NOW(), :created_by, NOW(), 0)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'type' => $data['type'],
            'media_path' => $data['media_path'] ?? null,
            'rating_scale' => $data['rating_scale'] ?? null,
            'rating_symbol' => $data['rating_symbol'] ?? null,
            'tags' => $data['tags'] ?? null,
            'created_by' => $data['created_by'],
        ]);

        return $this->conn->lastInsertId();
    }

    // Update existing question by ID
    public function updateQuestion($id, $data) {
        $sql = "UPDATE feedback_questions SET 
                    title = :title,
                    type = :type,
                    media_path = :media_path,
                    rating_scale = :rating_scale,
                    rating_symbol = :rating_symbol,
                    tags = :tags,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id AND is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            'title' => $data['title'],
            'type' => $data['type'],
            'media_path' => $data['media_path'] ?? null,
            'rating_scale' => $data['rating_scale'] ?? null,
            'rating_symbol' => $data['rating_symbol'] ?? null,
            'tags' => $data['tags'] ?? null,
            'updated_by' => $data['updated_by'],
            'id' => $id,
        ]);
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

                $mediaName = $this->handleMediaUpload($file, 'feedback');
            }

            $sql = "INSERT INTO feedback_question_options 
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

    // Update options for a question (pass option id to update, else insert new)
    public function updateOptions($questionId, $optionsData, $updatedBy) {
        /*
        $optionsData = [
            ['id' => 1, 'text' => 'Option 1', 'media_path' => 'media1.jpg', 'media_file' => $_FILES file array or null],
            ['id' => null, 'text' => 'New Option', 'media_path' => null, 'media_file' => ...],
            ...
        ]
        */
        foreach ($optionsData as $opt) {
            $text = trim($opt['text']);
            if ($text === '') continue;

            $mediaName = $opt['media_path'] ?? null;

            // Handle new media upload if exists
            if (!empty($opt['media_file']['name'])) {
                $mediaName = $this->handleMediaUpload($opt['media_file'], 'feedback');
            }

            if (!empty($opt['id'])) {
                // Update existing option
                $sql = "UPDATE feedback_question_options SET
                            option_text = :option_text,
                            media_path = :media_path,
                            updated_by = :updated_by,
                            updated_at = NOW()
                        WHERE id = :id AND question_id = :question_id AND is_deleted = 0";

                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'option_text' => $text,
                    'media_path' => $mediaName,
                    'updated_by' => $updatedBy,
                    'id' => $opt['id'],
                    'question_id' => $questionId,
                ]);
            } else {
                // Insert new option
                $sql = "INSERT INTO feedback_question_options
                        (question_id, option_text, media_path, created_by, created_at, updated_by, updated_at, is_deleted)
                        VALUES 
                        (:question_id, :option_text, :media_path, :created_by, NOW(), :created_by, NOW(), 0)";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    'question_id' => $questionId,
                    'option_text' => $text,
                    'media_path' => $mediaName,
                    'created_by' => $updatedBy,
                ]);
            }
        }
    }

    // Soft delete options by ids for a question
    public function deleteOptions($optionIds) {
        if (empty($optionIds)) return;

        $placeholders = implode(',', array_fill(0, count($optionIds), '?'));
        $sql = "UPDATE feedback_question_options SET is_deleted = 1 WHERE id IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        foreach ($optionIds as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
    }

    // Get all options for a question
    public function getOptionsByQuestionId($questionId) {
        $sql = "SELECT id, option_text, media_path 
                FROM feedback_question_options 
                WHERE question_id = :question_id AND is_deleted = 0 
                ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['question_id' => $questionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get question details including options
    public function getQuestionById($id) {
        $sql = "SELECT * FROM feedback_questions WHERE id = :id AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['id' => $id]);
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) return null;

        $question['options'] = $this->getOptionsByQuestionId($id);

        
        return $question;
    }

    // Fetch paginated questions with optional search and type filter
    public function getQuestions($search = '', $type = '', $limit = 10, $offset = 0, $tags = '') {
        $params = [];
        $sql = "SELECT id, title, type, tags
                FROM feedback_questions
                WHERE is_deleted = 0 ";

        if ($search !== '') {
            $sql .= " AND (title LIKE ? OR tags LIKE ?) ";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($type !== '') {
            $sql .= " AND type = ? ";
            $params[] = $type;
        }

        if ($tags !== '') {
            $sql .= " AND tags LIKE ? ";
            $params[] = '%' . $tags . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total number of questions for pagination
    public function getTotalQuestionCount($search = '', $type = '', $tags = '') {
        $params = [];
        $sql = "SELECT COUNT(*) FROM feedback_questions WHERE is_deleted = 0 ";

        if ($search !== '') {
            $sql .= " AND (title LIKE ? OR tags LIKE ?) ";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($type !== '') {
            $sql .= " AND type = ? ";
            $params[] = $type;
        }

        if ($tags !== '') {
            $sql .= " AND tags LIKE ? ";
            $params[] = '%' . $tags . '%';
        }

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // Get distinct question types
    public function getDistinctTypes() {
        $sql = "SELECT DISTINCT type FROM feedback_questions WHERE is_deleted = 0 ORDER BY type ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get unique tags for filter suggestions
    public function getUniqueTags() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT tags
            FROM feedback_questions
            WHERE is_deleted = 0 AND tags IS NOT NULL AND tags != ''
            ORDER BY tags ASC
        ");
        $stmt->execute();
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

    // Get question details by IDs (minimal fields)
    public function getSelectedQuestions(array $ids) {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id, title, type, tags FROM feedback_questions WHERE id IN ($placeholders) AND is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        foreach ($ids as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Soft delete a question
    public function deleteQuestion($id) {
        $sql = "UPDATE feedback_questions SET is_deleted = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Also soft delete options
        $sqlOpt = "UPDATE feedback_question_options SET is_deleted = 1 WHERE question_id = :question_id";
        $stmtOpt = $this->conn->prepare($sqlOpt);
        $stmtOpt->execute(['question_id' => $id]);

        return true;
    }

    // Helper: handle media upload, returns stored filename or null
    private function handleMediaUpload($file, $folder) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'video/mp4', 'application/pdf'];

        if (in_array($file['type'], $allowedTypes) && $file['error'] === 0) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $randomName = bin2hex(random_bytes(10)) . '.' . $ext;
            $uploadDir = "uploads/$folder/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $targetPath = $uploadDir . $randomName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return $randomName;
            }
        }

        return null;
    }
}
