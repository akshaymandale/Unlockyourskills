<?php
require_once 'config/Database.php';

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "<h2>🔍 Checking User Profile for ID 42</h2>";
    
    // Check if user 42 exists
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE id = ?");
    $stmt->execute([42]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>✅ User 42 found:</p>";
        echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ User 42 NOT found in user_profiles table</p>";
    }
    
    // Check all users
    $stmt = $conn->query("SELECT id, full_name, email, client_id FROM user_profiles ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Users in user_profiles:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Client ID</th></tr>";
    foreach ($users as $u) {
        echo "<tr><td>{$u['id']}</td><td>{$u['full_name']}</td><td>{$u['email']}</td><td>{$u['client_id']}</td></tr>";
    }
    echo "</table>";
    
    // Test the exact query that's failing
    echo "<h3>🔍 Testing the exact query that's failing:</h3>";
    
    $query = "
        SELECT 
            p.*,
            up.full_name as author_name,
            up.email as author_email,
            up.profile_picture as author_avatar,
            up.system_role as author_role,
            CASE 
                WHEN p.user_id = ? THEN 1 
                ELSE 0 
            END as can_edit,
            CASE 
                WHEN p.user_id = ? OR ? = 'super_admin' THEN 1 
                ELSE 0 
            END as can_delete,
            (SELECT COUNT(*) FROM feed_comments WHERE post_id = p.id) as comment_count,
            (SELECT COUNT(*) FROM feed_reactions WHERE post_id = p.id) as reaction_count
        FROM feed_posts p
        LEFT JOIN user_profiles up ON p.user_id = up.id
        WHERE p.status = 'active' AND p.client_id = ?
        ORDER BY p.is_pinned DESC, p.created_at DESC
        LIMIT 10 OFFSET 0
    ";
    
    $params = [42, 42, 'super_admin', 1];
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Query result count:</strong> " . count($posts) . "</p>";
    if (count($posts) > 0) {
        echo "<p>✅ Query works! First post:</p>";
        echo "<pre>" . json_encode($posts[0], JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p>❌ Query returns no posts</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 