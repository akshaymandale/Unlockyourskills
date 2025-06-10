<?php
// Simple test to check Non-SCORM functionality
require_once 'config/database.php';
require_once 'models/VLRModel.php';

echo "<h2>Testing Non-SCORM Package Functionality</h2>";

try {
    // Create database connection
    $database = new Database();
    $db = $database->connect();
    
    echo "<p>✅ Database connection successful</p>";
    
    // Test VLRModel
    $vlrModel = new VLRModel($db);
    echo "<p>✅ VLRModel created successfully</p>";
    
    // Test getNonScormPackages method
    $nonScormPackages = $vlrModel->getNonScormPackages();
    echo "<p><strong>Non-SCORM packages found:</strong> " . count($nonScormPackages) . "</p>";
    
    if (!empty($nonScormPackages)) {
        echo "<h3>Non-SCORM Packages:</h3>";
        echo "<pre>";
        print_r($nonScormPackages);
        echo "</pre>";
    } else {
        echo "<p>No Non-SCORM packages found. Let's check if the table exists...</p>";
        
        // Check if table exists
        $stmt = $db->prepare("SHOW TABLES LIKE 'non_scorm_package'");
        $stmt->execute();
        $tableExists = $stmt->fetch();
        
        if (!$tableExists) {
            echo "<p>❌ Table 'non_scorm_package' does not exist!</p>";
            echo "<p>Creating the table now...</p>";
            
            // Create the table
            $createTableSQL = "
            CREATE TABLE IF NOT EXISTS `non_scorm_package` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `title` varchar(255) NOT NULL,
              `content_type` enum('html5', 'flash', 'unity', 'custom_web', 'mobile_app') NOT NULL,
              `description` text,
              `tags` text,
              `version` varchar(50) NOT NULL,
              `language` varchar(100) DEFAULT NULL,
              `time_limit` int(11) DEFAULT NULL COMMENT 'Time limit in minutes',
              `mobile_support` enum('Yes', 'No') DEFAULT 'No',
              `content_url` varchar(500) DEFAULT NULL COMMENT 'URL for external content',
              `launch_file` varchar(255) DEFAULT NULL COMMENT 'Main launch file (index.html, etc.)',
              `content_package` varchar(255) DEFAULT NULL COMMENT 'Uploaded content package (ZIP, etc.)',
              `thumbnail_image` varchar(255) DEFAULT NULL COMMENT 'Thumbnail image for the content',
              `manifest_file` varchar(255) DEFAULT NULL COMMENT 'Manifest or configuration file',
              `html5_framework` varchar(100) DEFAULT NULL COMMENT 'HTML5 framework used (React, Angular, etc.)',
              `responsive_design` enum('Yes', 'No') DEFAULT 'Yes' COMMENT 'Whether content is responsive',
              `offline_support` enum('Yes', 'No') DEFAULT 'No' COMMENT 'Whether content works offline',
              `flash_version` varchar(50) DEFAULT NULL COMMENT 'Required Flash Player version',
              `flash_security` enum('Local', 'Network') DEFAULT 'Local' COMMENT 'Flash security settings',
              `unity_version` varchar(50) DEFAULT NULL COMMENT 'Unity engine version',
              `unity_platform` enum('WebGL', 'WebPlayer') DEFAULT 'WebGL' COMMENT 'Unity deployment platform',
              `unity_compression` enum('Gzip', 'Brotli', 'None') DEFAULT 'Gzip' COMMENT 'Unity compression method',
              `web_technologies` text DEFAULT NULL COMMENT 'Technologies used (JavaScript, CSS3, etc.)',
              `browser_requirements` text DEFAULT NULL COMMENT 'Minimum browser requirements',
              `external_dependencies` text DEFAULT NULL COMMENT 'External libraries or APIs required',
              `mobile_platform` enum('iOS', 'Android', 'Cross-Platform') DEFAULT 'Cross-Platform',
              `app_store_url` varchar(500) DEFAULT NULL COMMENT 'App store download URL',
              `minimum_os_version` varchar(50) DEFAULT NULL COMMENT 'Minimum OS version required',
              `progress_tracking` enum('Yes', 'No') DEFAULT 'Yes' COMMENT 'Whether it tracks learner progress',
              `assessment_integration` enum('Yes', 'No') DEFAULT 'No' COMMENT 'Whether it integrates with assessments',
              `completion_criteria` text DEFAULT NULL COMMENT 'Criteria for marking content as complete',
              `scoring_method` enum('Points', 'Percentage', 'Pass/Fail', 'None') DEFAULT 'None',
              `file_size` bigint DEFAULT NULL COMMENT 'Content file size in bytes',
              `bandwidth_requirement` varchar(100) DEFAULT NULL COMMENT 'Minimum bandwidth required',
              `screen_resolution` varchar(100) DEFAULT NULL COMMENT 'Recommended screen resolution',
              `created_by` int(11) NOT NULL,
              `updated_by` int(11) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`),
              KEY `idx_content_type` (`content_type`),
              KEY `idx_created_by` (`created_by`),
              KEY `idx_is_deleted` (`is_deleted`),
              KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Non-SCORM content packages'";
            
            $db->exec($createTableSQL);
            echo "<p>✅ Table 'non_scorm_package' created successfully!</p>";
            
            // Insert a test package
            $stmt = $db->prepare("INSERT INTO non_scorm_package (title, content_type, version, mobile_support, tags, created_by, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                'Test HTML5 Interactive Content',
                'html5',
                '1.0',
                'Yes',
                'test,html5,interactive',
                1,
                'This is a test HTML5 interactive content package for testing purposes.'
            ]);
            
            if ($result) {
                echo "<p>✅ Test Non-SCORM package added successfully!</p>";
                
                // Test again
                $nonScormPackages = $vlrModel->getNonScormPackages();
                echo "<p><strong>Non-SCORM packages found after creation:</strong> " . count($nonScormPackages) . "</p>";
                
                if (!empty($nonScormPackages)) {
                    echo "<h3>Non-SCORM Packages:</h3>";
                    echo "<pre>";
                    print_r($nonScormPackages);
                    echo "</pre>";
                }
            } else {
                echo "<p>❌ Failed to add test Non-SCORM package!</p>";
            }
        } else {
            echo "<p>✅ Table 'non_scorm_package' exists but no packages found.</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.php?controller=VLRController'>Go to VLR Page</a></p>";
?>
