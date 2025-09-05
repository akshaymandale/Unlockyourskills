<?php
// Check SCORM Progress in Database
require_once 'config/autoload.php';

// Configure session to match main application
session_save_path('/Applications/XAMPP/xamppfiles/htdocs/Unlockyourskills/sessions');
session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'secure' => false,
    'samesite' => 'Lax'
]);

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user']['id'])) {
    echo "Please log in first\n";
    exit;
}

$userId = $_SESSION['user']['id'];
$clientId = $_SESSION['user']['client_id'];

echo "Checking SCORM Progress for User ID: $userId, Client ID: $clientId\n\n";

try {
    // Use the same database connection method as the main app
    $host = 'localhost';
    $dbname = 'unlockyourskills';
    $username = 'root';
    $password = '';
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check SCORM progress records
    echo "1. Checking SCORM progress records...\n";
    $stmt = $conn->prepare("
        SELECT sp.*, cmc.content_type, c.name as course_name
        FROM scorm_progress sp
        JOIN course_module_content cmc ON sp.content_id = cmc.id
        JOIN courses c ON sp.course_id = c.id
        WHERE sp.user_id = ?
        ORDER BY sp.updated_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $scormProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($scormProgress)) {
        echo "   ❌ No SCORM progress records found\n";
    } else {
        echo "   ✅ Found " . count($scormProgress) . " SCORM progress records:\n";
        foreach ($scormProgress as $progress) {
            echo "      - Course: {$progress['course_name']}, Content ID: {$progress['content_id']}, Status: {$progress['lesson_status']}, Updated: {$progress['updated_at']}\n";
        }
    }
    
    // Check course progress
    echo "\n2. Checking course progress...\n";
    $stmt = $conn->prepare("
        SELECT * FROM user_course_progress 
        WHERE user_id = ? AND client_id = ?
        ORDER BY updated_at DESC
        LIMIT 3
    ");
    $stmt->execute([$userId, $clientId]);
    $courseProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($courseProgress)) {
        echo "   ❌ No course progress records found\n";
    } else {
        echo "   ✅ Found " . count($courseProgress) . " course progress records:\n";
        foreach ($courseProgress as $progress) {
            echo "      - Course ID: {$progress['course_id']}, Status: {$progress['status']}, Completion: {$progress['completion_percentage']}%, Updated: {$progress['updated_at']}\n";
        }
    }
    
    // Check content progress (using new progress tracking tables)
    echo "\n3. Checking content progress...\n";
    
    // First check if content_progress table exists and has the right structure
    try {
        $stmt = $conn->prepare("DESCRIBE content_progress");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('user_id', $columns)) {
            // New structure with user_id
            $stmt = $conn->prepare("
                SELECT cp.*, cmc.content_type, c.name as course_name
                FROM content_progress cp
                JOIN course_module_content cmc ON cp.content_id = cmc.id
                JOIN courses c ON cp.course_id = c.id
                WHERE cp.user_id = ? AND cp.client_id = ?
                ORDER BY cp.updated_at DESC
                LIMIT 5
            ");
            $stmt->execute([$userId, $clientId]);
        } else {
            // Old structure with enrollment_id
            $stmt = $conn->prepare("
                SELECT cp.*, cmc.content_type, c.name as course_name
                FROM content_progress cp
                JOIN course_module_content cmc ON cp.content_id = cmc.id
                JOIN courses c ON cp.course_id = c.id
                WHERE cp.enrollment_id IN (
                    SELECT id FROM course_enrollments WHERE user_id = ? AND client_id = ?
                )
                ORDER BY cp.updated_at DESC
                LIMIT 5
            ");
            $stmt->execute([$userId, $clientId]);
        }
        
        $contentProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($contentProgress)) {
            echo "   ❌ No content progress records found\n";
        } else {
            echo "   ✅ Found " . count($contentProgress) . " content progress records:\n";
            foreach ($contentProgress as $progress) {
                echo "      - Course: {$progress['course_name']}, Content ID: {$progress['content_id']}, Type: {$progress['content_type']}, Status: {$progress['status']}, Updated: {$progress['updated_at']}\n";
            }
        }
    } catch (Exception $e) {
        echo "   ⚠️ Content progress table not accessible: " . $e->getMessage() . "\n";
        echo "   ℹ️ This is normal if using the new progress tracking system\n";
    }
    
    // Check new progress tracking tables
    echo "\n4. Checking new progress tracking tables...\n";
    
    // Check video progress
    try {
        $stmt = $conn->prepare("
            SELECT vp.*, cmc.content_type, c.name as course_name
            FROM video_progress vp
            JOIN course_module_content cmc ON vp.content_id = cmc.id
            JOIN courses c ON vp.course_id = c.id
            WHERE vp.user_id = ? AND vp.client_id = ?
            ORDER BY vp.updated_at DESC
            LIMIT 3
        ");
        $stmt->execute([$userId, $clientId]);
        $videoProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($videoProgress)) {
            echo "   📹 No video progress records found\n";
        } else {
            echo "   📹 Found " . count($videoProgress) . " video progress records\n";
        }
    } catch (Exception $e) {
        echo "   📹 Video progress table not accessible\n";
    }
    
    // Check audio progress
    try {
        $stmt = $conn->prepare("
            SELECT ap.*, cmc.content_type, c.name as course_name
            FROM audio_progress ap
            JOIN course_module_content cmc ON ap.content_id = cmc.id
            JOIN courses c ON ap.course_id = c.id
            WHERE ap.user_id = ? AND ap.client_id = ?
            ORDER BY ap.updated_at DESC
            LIMIT 3
        ");
        $stmt->execute([$userId, $clientId]);
        $audioProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($audioProgress)) {
            echo "   🎵 No audio progress records found\n";
        } else {
            echo "   🎵 Found " . count($audioProgress) . " audio progress records\n";
        }
    } catch (Exception $e) {
        echo "   🎵 Audio progress table not accessible\n";
    }
    
    // Check document progress
    try {
        $stmt = $conn->prepare("
            SELECT dp.*, cmc.content_type, c.name as course_name
            FROM document_progress dp
            JOIN course_module_content cmc ON dp.content_id = cmc.id
            JOIN courses c ON dp.course_id = c.id
            WHERE dp.user_id = ? AND dp.client_id = ?
            ORDER BY dp.updated_at DESC
            LIMIT 3
        ");
        $stmt->execute([$userId, $clientId]);
        $documentProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($documentProgress)) {
            echo "   📄 No document progress records found\n";
        } else {
            echo "   📄 Found " . count($documentProgress) . " document progress records\n";
        }
    } catch (Exception $e) {
        echo "   📄 Document progress table not accessible\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
