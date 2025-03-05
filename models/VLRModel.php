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
    // Backend Validation: Ensure required fields are filled
    if (empty($data['title']) || empty($data['zip_file']) || empty($data['version']) || empty($data['scorm_category']) || empty($data['mobile_support']) || empty($data['assessment'])) {
        return false;
    }

    $stmt = $this->conn->prepare("INSERT INTO scorm_packages 
        (title, zip_file, description, tags, version, language, scorm_category, time_limit, mobile_support, assessment, created_by, is_deleted, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");

    return $stmt->execute([
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
        $data['created_by']
    ]);
}

// ✅ Update SCORM Package
public function updateScormPackage($id, $data) {
    // Ensure SCORM ID exists
    if (empty($id)) {
        return false;
    }

    $stmt = $this->conn->prepare("UPDATE scorm_packages 
        SET title = ?, zip_file = ?, description = ?, tags = ?, version = ?, language = ?, scorm_category = ?, time_limit = ?, mobile_support = ?, assessment = ?, updated_at = NOW()
        WHERE id = ?");

    return $stmt->execute([
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
        $id
    ]);
}

    // Get data for display on VLR 
    public function getScormPackages() {
        $stmt = $this->conn->prepare("SELECT * FROM scorm_packages WHERE is_deleted = 0");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs
    
        return $data;
    }

    // Delete respective SCROM 
    public function deleteScormPackage($id) {
        $stmt = $this->conn->prepare("UPDATE scorm_packages SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Add external content package 

    public function insertExternalContent($data)
    {
        try {
            // Debugging: Print data before inserting
        // echo "<pre>";
        // print_r($data);
        // echo "</pre>";
            // exit;

            // Ensure audio_file exists in data
            $audioFile = isset($data['audio_file']) ? $data['audio_file'] : null;

            $sql = "INSERT INTO external_content (
                title, content_type, version_number, mobile_support, language_support, time_limit, 
                description, tags, video_url, thumbnail, course_url, platform_name, article_url, 
                author, audio_source, audio_url, audio_file, speaker, created_by
            ) VALUES (
                :title, :content_type, :version_number, :mobile_support, :language_support, :time_limit, 
                :description, :tags, :video_url, :thumbnail, :course_url, :platform_name, :article_url, 
                :author, :audio_source, :audio_url, :audio_file, :speaker, :created_by
            )";

            $stmt = $this->conn->prepare($sql);

            // Bind parameters
            $stmt->execute([
                ':title'           => $data['title'],
                ':content_type'    => $data['content_type'],
                ':version_number'  => $data['version_number'],
                ':mobile_support'  => $data['mobile_support'],
                ':language_support'=> $data['language_support'],
                ':time_limit'      => $data['time_limit'],
                ':description'     => $data['description'],
                ':tags'            => $data['tags'],
                ':video_url'       => $data['video_url'],
                ':thumbnail'       => $data['thumbnail'],
                ':course_url'      => $data['course_url'],
                ':platform_name'   => $data['platform_name'],
                ':article_url'     => $data['article_url'],
                ':author'          => $data['author'],
                ':audio_source'    => $data['audio_source'],
                ':audio_url'       => $data['audio_url'],
                ':audio_file'      => $audioFile, // Now ensuring this is always set
                ':speaker'         => $data['speaker'],
                ':created_by'      => $data['created_by']
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Insert Error: " . $e->getMessage());
            return false;
        }
    }


    // Update for External Content 

    public function updateExternalContent($id, $data)
    {
        try {
            // Ensure audio_file exists in data
            $audioFile = isset($data['audio_file']) ? $data['audio_file'] : null;

            $sql = "UPDATE external_content SET
                title = :title,
                content_type = :content_type,
                version_number = :version_number,
                mobile_support = :mobile_support,
                language_support = :language_support,
                time_limit = :time_limit,
                description = :description,
                tags = :tags,
                video_url = :video_url,
                thumbnail = :thumbnail,
                course_url = :course_url,
                platform_name = :platform_name,
                article_url = :article_url,
                author = :author,
                audio_source = :audio_source,
                audio_url = :audio_url,
                audio_file = :audio_file,
                speaker = :speaker,
                updated_at = NOW()
            WHERE id = :id";

            $stmt = $this->db->prepare($sql);

            // Bind parameters
            $stmt->execute([
                ':id'              => $id,
                ':title'           => $data['title'],
                ':content_type'    => $data['content_type'],
                ':version_number'  => $data['version_number'],
                ':mobile_support'  => $data['mobile_support'],
                ':language_support'=> $data['language_support'],
                ':time_limit'      => $data['time_limit'],
                ':description'     => $data['description'],
                ':tags'            => $data['tags'],
                ':video_url'       => $data['video_url'],
                ':thumbnail'       => $data['thumbnail'],
                ':course_url'      => $data['course_url'],
                ':platform_name'   => $data['platform_name'],
                ':article_url'     => $data['article_url'],
                ':author'          => $data['author'],
                ':audio_source'    => $data['audio_source'],
                ':audio_url'       => $data['audio_url'],
                ':audio_file'      => $audioFile, // Ensuring it is always set
                ':speaker'         => $data['speaker']
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Update Error: " . $e->getMessage());
            return false;
        }
    }

    // Get data for External Content
    public function getExternalContent() {
        $stmt = $this->conn->prepare("SELECT * FROM external_content WHERE is_deleted = 0");
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Debugging - Check if data is fetched
        error_log(print_r($data, true)); // Logs to XAMPP/PHP logs

        return $data;
    }

    // Delete respective External Content 
    public function deleteExternalContent($id) {
        $stmt = $this->conn->prepare("UPDATE external_content SET is_deleted = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }



}
?>
