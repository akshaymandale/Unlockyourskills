<?php
// Debug script to check Non-SCORM package data
require_once 'config/database.php';
require_once 'models/VLRModel.php';

echo "<h2>Debug: Non-SCORM Package Display Issue</h2>";

try {
    // Create database connection
    $database = new Database();
    $conn = $database->connect();

    // Create VLR model instance
    $vlrModel = new VLRModel();
    
    echo "<h3>1. Testing getNonScormPackages() method:</h3>";
    $nonScormPackages = $vlrModel->getNonScormPackages();

    echo "<p><strong>Number of Non-SCORM packages found:</strong> " . count($nonScormPackages) . "</p>";

    echo "<h3>2. Direct Database Query for Non-SCORM packages:</h3>";
    $stmt = $conn->prepare("SELECT * FROM non_scorm_package WHERE is_deleted = 0 ORDER BY created_at DESC");
    $stmt->execute();
    $directNonScormResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p><strong>Direct Non-SCORM query result count:</strong> " . count($directNonScormResult) . "</p>";

    if (!empty($directNonScormResult)) {
        echo "<h4>Direct Non-SCORM Query Data:</h4>";
        echo "<pre>";
        print_r($directNonScormResult);
        echo "</pre>";
    } else {
        echo "<p><strong>No Non-SCORM packages found in database!</strong></p>";
        echo "<p>Let me add a test Non-SCORM package...</p>";

        // Add a test Non-SCORM package
        $stmt = $conn->prepare("INSERT INTO non_scorm_package (title, content_type, version, mobile_support, tags, created_by, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
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
            echo "<p><strong>✅ Test Non-SCORM package added successfully!</strong></p>";

            // Re-fetch to confirm
            $stmt = $conn->prepare("SELECT * FROM non_scorm_package WHERE is_deleted = 0 ORDER BY created_at DESC");
            $stmt->execute();
            $newResult = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<p><strong>New count after adding test package:</strong> " . count($newResult) . "</p>";

            if (!empty($newResult)) {
                echo "<h4>Updated Non-SCORM Data:</h4>";
                echo "<pre>";
                print_r($newResult);
                echo "</pre>";
            }
        } else {
            echo "<p><strong>❌ Failed to add test Non-SCORM package!</strong></p>";
        }
    }
    
    if (!empty($nonScormPackages)) {
        echo "<h4>Package Data:</h4>";
        echo "<pre>";
        print_r($nonScormPackages);
        echo "</pre>";
        
        echo "<h4>Content Type Analysis:</h4>";
        $contentTypes = [];
        foreach ($nonScormPackages as $package) {
            $contentType = $package['content_type'] ?? 'unknown';
            if (!isset($contentTypes[$contentType])) {
                $contentTypes[$contentType] = 0;
            }
            $contentTypes[$contentType]++;
        }
        
        echo "<ul>";
        foreach ($contentTypes as $type => $count) {
            echo "<li><strong>$type:</strong> $count packages</li>";
        }
        echo "</ul>";
        
        echo "<h4>Category Mapping Test:</h4>";
        $categories = [
            'html5' => 'html5-content',
            'flash' => 'flash-content',
            'unity' => 'unity-content',
            'custom_web' => 'custom-web',
            'mobile_app' => 'mobile-app'
        ];
        
        $groupedData = [
            'html5-content' => [],
            'flash-content' => [],
            'unity-content' => [],
            'custom-web' => [],
            'mobile-app' => []
        ];
        
        foreach ($nonScormPackages as $package) {
            $contentType = $package['content_type'];
            $categoryKey = $categories[$contentType] ?? null;
            
            echo "<p>Package '{$package['title']}' has content_type '{$contentType}' -> maps to category '{$categoryKey}'</p>";
            
            if ($categoryKey && isset($groupedData[$categoryKey])) {
                $groupedData[$categoryKey][] = $package;
            }
        }
        
        echo "<h4>Grouped Data Results:</h4>";
        foreach ($groupedData as $category => $packages) {
            echo "<p><strong>$category:</strong> " . count($packages) . " packages</p>";
        }
        
    } else {
        echo "<p><strong>No packages found!</strong></p>";
        
        echo "<h3>2. Direct Database Query Test:</h3>";
        $stmt = $conn->prepare("SELECT * FROM non_scorm_package WHERE is_deleted = 0 ORDER BY created_at DESC");
        $stmt->execute();
        $directResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Direct query result count:</strong> " . count($directResult) . "</p>";
        
        if (!empty($directResult)) {
            echo "<h4>Direct Query Data:</h4>";
            echo "<pre>";
            print_r($directResult);
            echo "</pre>";
        }
        
        echo "<h3>3. Table Structure Check:</h3>";
        $stmt = $conn->prepare("DESCRIBE non_scorm_package");
        $stmt->execute();
        $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Table Structure:</h4>";
        echo "<pre>";
        print_r($tableStructure);
        echo "</pre>";
        
        echo "<h3>4. All Records Check (including deleted):</h3>";
        $stmt = $conn->prepare("SELECT id, title, content_type, is_deleted FROM non_scorm_package ORDER BY created_at DESC");
        $stmt->execute();
        $allRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total records in table:</strong> " . count($allRecords) . "</p>";
        
        if (!empty($allRecords)) {
            echo "<h4>All Records:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Content Type</th><th>Is Deleted</th></tr>";
            foreach ($allRecords as $record) {
                echo "<tr>";
                echo "<td>{$record['id']}</td>";
                echo "<td>{$record['title']}</td>";
                echo "<td>{$record['content_type']}</td>";
                echo "<td>{$record['is_deleted']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php?controller=VLRController'>Back to VLR Page</a></p>";
?>
