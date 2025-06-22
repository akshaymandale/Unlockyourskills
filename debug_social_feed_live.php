<?php
require_once 'config/Database.php';

echo "<h2>🔍 LIVE SOCIAL FEED DEBUG</h2>";

try {
    $database = new Database();
    $conn = $database->connect();
    
    // Check session
    session_start();
    echo "<h3>📋 Current Session:</h3>";
    if (empty($_SESSION)) {
        echo "<p style='color: red;'>❌ NO SESSION DATA</p>";
    } else {
        echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>";
    }
    
    // Check database state
    echo "<h3>🗄️ Database State:</h3>";
    
    // Count posts
    $stmt = $conn->query("SELECT COUNT(*) as total FROM feed_posts");
    $totalPosts = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p><strong>Total posts:</strong> $totalPosts</p>";
    
    // Count active posts
    $stmt = $conn->query("SELECT COUNT(*) as active FROM feed_posts WHERE status = 'active'");
    $activePosts = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
    echo "<p><strong>Active posts:</strong> $activePosts</p>";
    
    // Show recent posts
    $stmt = $conn->query("SELECT id, user_id, client_id, body, status, created_at FROM feed_posts ORDER BY created_at DESC LIMIT 5");
    $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Recent Posts:</h4>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Client ID</th><th>Body</th><th>Status</th><th>Created</th></tr>";
    foreach ($recentPosts as $post) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($post['id']) . "</td>";
        echo "<td>" . htmlspecialchars($post['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($post['client_id']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($post['body'], 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($post['status']) . "</td>";
        echo "<td>" . htmlspecialchars($post['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test the exact query that should work
    if (!empty($_SESSION['user']['client_id'])) {
        $clientId = $_SESSION['user']['client_id'];
        echo "<h3>🔍 Testing Query with client_id = $clientId:</h3>";
        
        $query = "
            SELECT 
                p.*,
                up.full_name as author_name,
                up.email as author_email,
                up.profile_picture as author_avatar,
                up.system_role as author_role
            FROM feed_posts p
            LEFT JOIN user_profiles up ON p.user_id = up.id
            WHERE p.status = 'active' AND p.client_id = ?
            ORDER BY p.is_pinned DESC, p.created_at DESC
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$clientId]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Query result count:</strong> " . count($result) . "</p>";
        
        if (count($result) > 0) {
            echo "<h4>Query Results:</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Post ID</th><th>User ID</th><th>Client ID</th><th>Author Name</th><th>Body</th><th>Status</th></tr>";
            foreach ($result as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['client_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['author_name'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars(substr($row['body'], 0, 30)) . "...</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No posts found for client_id = $clientId</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No client_id in session</p>";
    }
    
    // Test without client_id filter
    echo "<h3>🔍 Testing Query without client_id filter:</h3>";
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
    
    echo "<p><strong>Query result count (no client filter):</strong> " . count($result) . "</p>";
    
    if (count($result) > 0) {
        echo "<h4>Query Results (no client filter):</h4>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Post ID</th><th>User ID</th><th>Client ID</th><th>Author Name</th><th>Body</th><th>Status</th></tr>";
        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['client_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['author_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['body'], 0, 30)) . "...</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 