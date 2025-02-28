<?php
require_once 'config/Database.php';

class VLRModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }


    // ✅ Insert SCORM Package
    public function insertScormPackage($data) {
        // Backend Validation
        if (empty($data['title']) || empty($data['zip_file']) || empty($data['version']) || empty($data['scorm_category']) || empty($data['mobile_support']) || empty($data['assessment'])) {
            return ['status' => 'error', 'message' => 'Required fields cannot be empty.'];
        }

        $stmt = $this->conn->prepare("INSERT INTO scorm_packages 
            (title, zip_file, description, tags, version, language, scorm_category, time_limit, mobile_support, assessment, created_by, is_deleted) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");

        $result = $stmt->execute([
            $data['title'], 
            $data['zip_file'], 
            $data['description'], 
            $data['tags'], 
            $data['version'], 
            $data['language'], 
            $data['scorm_category'], 
            $data['time_limit'], 
            $data['mobile_support'], 
            $data['assessment'], 
            $_SESSION['full_name']
        ]);

        if ($result) {
            return ['status' => 'success', 'message' => 'SCORM package added successfully.'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to insert SCORM package.'];
        }
    }
}
?>
