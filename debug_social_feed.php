<?php
require_once 'config/autoload.php';
require_once 'config/Database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate a logged-in user for testing
$_SESSION['user'] = [
    'id' => 1,
    'client_id' => 1,
    'role' => 'admin'
];

echo "<h2>Social Feed Debug Test</h2>";

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "<h2>Social Feed Debug Information</h2>";
    
    // Check if user_profiles table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'user_profiles'");
    $userProfilesExists = $stmt->rowCount() > 0;
    echo "<p><strong>user_profiles table exists:</strong> " . ($userProfilesExists ? 'YES' : 'NO') . "</p>";
    
    // Check if feed_posts table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'feed_posts'");
    $feedPostsExists = $stmt->rowCount() > 0;
    echo "<p><strong>feed_posts table exists:</strong> " . ($feedPostsExists ? 'YES' : 'NO') . "</p>";
    
    if ($feedPostsExists) {
        // Count posts
        $stmt = $conn->query("SELECT COUNT(*) as count FROM feed_posts");
        $postCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p><strong>Total posts in feed_posts:</strong> " . $postCount . "</p>";
        
        // Show sample posts
        if ($postCount > 0) {
            $stmt = $conn->query("SELECT id, user_id, body, post_type, visibility, status, created_at FROM feed_posts LIMIT 5");
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Sample Posts:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Body</th><th>Type</th><th>Visibility</th><th>Status</th><th>Created</th></tr>";
            foreach ($posts as $post) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($post['id']) . "</td>";
                echo "<td>" . htmlspecialchars($post['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($post['body'], 0, 50)) . "...</td>";
                echo "<td>" . htmlspecialchars($post['post_type']) . "</td>";
                echo "<td>" . htmlspecialchars($post['visibility']) . "</td>";
                echo "<td>" . htmlspecialchars($post['status']) . "</td>";
                echo "<td>" . htmlspecialchars($post['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    if ($userProfilesExists) {
        // Count user profiles
        $stmt = $conn->query("SELECT COUNT(*) as count FROM user_profiles");
        $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p><strong>Total users in user_profiles:</strong> " . $userCount . "</p>";
        
        // Show sample users
        if ($userCount > 0) {
            $stmt = $conn->query("SELECT id, full_name, email, system_role FROM user_profiles LIMIT 5");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Sample Users:</h3>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['system_role']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Test the actual query that's failing
    if ($feedPostsExists && $userProfilesExists) {
        echo "<h3>Testing the main query:</h3>";
        
        $query = "
            SELECT 
                p.*,
                up.full_name as author_name,
                up.email as author_email,
                up.profile_picture as author_avatar,
                up.system_role as author_role
            FROM feed_posts p
            LEFT JOIN user_profiles up ON p.user_id = up.id
            WHERE p.status = 'active'
            ORDER BY p.is_pinned DESC, p.created_at DESC
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Query result count:</strong> " . count($result) . "</p>";
        
        if (count($result) > 0) {
            echo "<h4>Query Results:</h4>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Post ID</th><th>User ID</th><th>Author Name</th><th>Body</th><th>Status</th></tr>";
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['author_name'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['body'], 0, 30)) . "...</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 