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
                (title, type, media_path, rating_scale, rating_symbol, created_by, created_at, updated_by, updated_at, is_deleted)
                VALUES 
                (:title, :type, :media_path, :rating_scale, :rating_symbol, :created_by, NOW(), :created_by, NOW(), 0)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'type' => $data['type'],
            'media_path' => $data['media_path'],
            'rating_scale' => $data['rating_scale'],
            'rating_symbol' => $data['rating_symbol'],
            'created_by' => $data['created_by'],
        ]);

        return $this->conn->lastInsertId();
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
                        $mediaName = $randomName; // ✅ Only store filename
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
}
