<?php
require_once 'config/Database.php';

echo "<h1>🔍 THOROUGH SOCIAL FEED DEBUG ANALYSIS</h1>";

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "<h2>📊 DATABASE TABLES ANALYSIS</h2>";
    
    // Check all tables
    $tables = ['feed_posts', 'user_profiles', 'feed_comments', 'feed_reactions', 'feed_media_files', 'feed_poll_votes'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "<p><strong>$table:</strong> " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "</p>";
        
        if ($exists) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>&nbsp;&nbsp;&nbsp;&nbsp;Records: $count</p>";
        }
    }
    
    echo "<h2>📝 FEED_POSTS DETAILED ANALYSIS</h2>";
    
    // Check feed_posts structure
    $stmt = $conn->query("DESCRIBE feed_posts");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check all posts with details
    $stmt = $conn->query("SELECT * FROM feed_posts ORDER BY id");
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Posts in Database:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Client ID</th><th>Body</th><th>Type</th><th>Visibility</th><th>Status</th><th>Created</th></tr>";
    foreach ($posts as $post) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($post['id']) . "</td>";
        echo "<td>" . htmlspecialchars($post['user_id']) . "</td>";
        echo "<td>" . htmlspecialchars($post['client_id']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($post['body'], 0, 30)) . "...</td>";
        echo "<td>" . htmlspecialchars($post['post_type']) . "</td>";
        echo "<td>" . htmlspecialchars($post['visibility']) . "</td>";
        echo "<td>" . htmlspecialchars($post['status']) . "</td>";
        echo "<td>" . htmlspecialchars($post['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>👥 USER_PROFILES ANALYSIS</h2>";
    
    // Check user_profiles structure
    $stmt = $conn->query("DESCRIBE user_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check all users
    $stmt = $conn->query("SELECT id, full_name, email, system_role, client_id FROM user_profiles ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Users in Database:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Client ID</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . htmlspecialchars($user['system_role']) . "</td>";
        echo "<td>" . htmlspecialchars($user['client_id']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>🔗 JOIN ANALYSIS</h2>";
    
    // Test the exact query from the model
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
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Query without client_id filter (should return all active posts):</h3>";
    echo "<p><strong>Result count:</strong> " . count($result) . "</p>";
    
    if (count($result) > 0) {
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
    
    // Test with client_id filter
    $queryWithClient = "
        SELECT 
            p.*,
            up.full_name as author_name,
            up.email as author_email,
            up.profile_picture as author_avatar,
            up.system_role as author_role
        FROM feed_posts p
        LEFT JOIN user_profiles up ON p.user_id = up.id
        WHERE p.status = 'active' AND p.client_id = 1
        ORDER BY p.is_pinned DESC, p.created_at DESC
    ";
    
    $stmt = $conn->prepare($queryWithClient);
    $stmt->execute();
    $resultWithClient = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Query with client_id = 1 filter:</h3>";
    echo "<p><strong>Result count:</strong> " . count($resultWithClient) . "</p>";
    
    if (count($resultWithClient) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Post ID</th><th>User ID</th><th>Client ID</th><th>Author Name</th><th>Body</th><th>Status</th></tr>";
        foreach ($resultWithClient as $row) {
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
    
    echo "<h2>🎯 SESSION ANALYSIS</h2>";
    
    session_start();
    echo "<h3>Current Session Data:</h3>";
    if (empty($_SESSION)) {
        echo "<p style='color: red;'>❌ NO SESSION DATA FOUND</p>";
    } else {
        echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>";
    }
    
    echo "<h2>🔍 COMPARISON WITH EVENTS</h2>";
    
    // Check events table
    $stmt = $conn->query("SHOW TABLES LIKE 'events'");
    $eventsExists = $stmt->rowCount() > 0;
    echo "<p><strong>Events table exists:</strong> " . ($eventsExists ? '✅ YES' : '❌ NO') . "</p>";
    
    if ($eventsExists) {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM events");
        $eventCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p><strong>Events count:</strong> $eventCount</p>";
        
        // Check events structure
        $stmt = $conn->query("DESCRIBE events");
        $eventColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Events Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($eventColumns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?> 