<?php
require_once 'config/Database.php';

class SurveyQuestionModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

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

    public function updateQuestion($questionId, $data) {
        $sql = "UPDATE survey_questions SET 
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
            'media_path' => $data['media_path'],
            'rating_scale' => $data['rating_scale'],
            'rating_symbol' => $data['rating_symbol'],
            'tags' => $data['tags'],
            'updated_by' => $_SESSION['id'],
            'id' => $questionId
        ]);
    }

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
                        $mediaName = $randomName;
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

    public function updateOptions($questionId, $options, $optionMedias, $createdBy, $existingOptionMedias = [])
    {
        // Soft delete existing options
        $sql = "UPDATE survey_question_options 
                SET is_deleted = 1, updated_by = :updated_by, updated_at = NOW() 
                WHERE question_id = :question_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'updated_by' => $createdBy,
            'question_id' => $questionId,
        ]);

        // Save new options
        foreach ($options as $index => $optionText) {
            $mediaFileName = null;

            // If a new file was uploaded for this option
            if (isset($optionMedias['name'][$index]) && !empty($optionMedias['name'][$index])) {
                $mediaFile = [
                    'name' => $optionMedias['name'][$index],
                    'type' => $optionMedias['type'][$index],
                    'tmp_name' => $optionMedias['tmp_name'][$index],
                    'error' => $optionMedias['error'][$index],
                    'size' => $optionMedias['size'][$index],
                ];
                $mediaFileName = $this->handleUpload($mediaFile, 'survey_option'); // your custom upload handler
            } elseif (isset($existingOptionMedias[$index]) && !empty($existingOptionMedias[$index])) {
                $mediaFileName = $existingOptionMedias[$index]; // use existing
            }

            $sql = "INSERT INTO survey_question_options (question_id, option_text, media_path, is_deleted, created_by, created_at) 
                    VALUES (:question_id, :option_text, :media_path, 0, :created_by, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                'question_id' => $questionId,
                'option_text' => $optionText,
                'media_path' => $mediaFileName,
                'created_by' => $createdBy,
            ]);
        }
    }

    /**
 * Handles file upload.
 *
 * @param array $file The $_FILES['inputName'] array for a single file.
 * @param string $folder Subfolder name under your uploads directory (e.g., 'survey', 'survey_option')
 * @return string|false Returns the new unique filename on success, or false on failure.
 */
protected function handleUpload(array $file, string $folder)
{
    // Allowed mime types/extensions (adjust as needed)
    $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'video/mp4',
        'application/pdf',
        // Add more as needed
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimeTypes)) {
        return false;
    }

    // Create uploads folder if not exists
    $uploadDir = "uploads/survey/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename with original extension
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $uniqueName = uniqid('upl_', true) . '.' . $ext;

    $destination = $uploadDir . '/' . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return false;
    }

    return $uniqueName;
}


    


    public function getQuestions($search = '', $type = '', $limit = 10, $offset = 0, $tags = '') {
        $params = [];
        $sql = "SELECT id, title, type, tags, media_path
                FROM survey_questions
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
            $typeParam = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($index + 1, $param, $typeParam);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalQuestionCount($search = '', $type = '', $tags = '') {
        $params = [];
        $sql = "SELECT COUNT(*) FROM survey_questions WHERE is_deleted = 0 ";

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
            $typeParam = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($index + 1, $param, $typeParam);
        }

        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getDistinctTypes() {
        $sql = "SELECT DISTINCT type FROM survey_questions WHERE is_deleted = 0 ORDER BY type ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Get unique tags for filter suggestions
    public function getUniqueTags() {
        $stmt = $this->conn->prepare("
            SELECT DISTINCT tags
            FROM survey_questions
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

    // Get all options for a given question
    public function getOptionsByQuestionId($questionId)
    {
        $sql = "SELECT id, option_text, media_path
                FROM survey_question_options
                WHERE question_id = :question_id AND is_deleted = 0
                ORDER BY id ASC";
    
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':question_id', $questionId, PDO::PARAM_INT);
        $stmt->execute();
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function deleteQuestion($id) {
        $sql = "UPDATE survey_questions SET is_deleted = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
