<?php
require_once 'config/Database.php';

echo "<h2>Checking Poll-Related Database Tables</h2>";

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "<h3>Checking existing tables:</h3>";
    
    // Check which tables exist
    $tables = ['polls', 'poll_questions', 'poll_options', 'poll_votes', 'user_profiles', 'course_applicability', 'user_groups'];
    
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        echo "<p><strong>$table:</strong> " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "</p>";
    }
    
    echo "<h3>Creating missing tables...</h3>";
    
    // Create polls table
    $createPollsTable = "CREATE TABLE IF NOT EXISTS `polls` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text,
        `type` enum('single_choice','multiple_choice') NOT NULL DEFAULT 'single_choice',
        `target_audience` enum('global','course_specific','group_specific') NOT NULL DEFAULT 'global',
        `course_id` int(11) DEFAULT NULL,
        `group_id` int(11) DEFAULT NULL,
        `start_datetime` datetime NOT NULL,
        `end_datetime` datetime NOT NULL,
        `show_results` enum('after_vote','after_end','admin_only') NOT NULL DEFAULT 'after_vote',
        `allow_anonymous` tinyint(1) NOT NULL DEFAULT 0,
        `allow_vote_change` tinyint(1) NOT NULL DEFAULT 0,
        `status` enum('draft','active','paused','ended','archived') NOT NULL DEFAULT 'draft',
        `created_by` int(11) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_client_id` (`client_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($createPollsTable);
    echo "<p>✅ polls table created/verified</p>";
    
    // Create poll_questions table
    $createPollQuestionsTable = "CREATE TABLE IF NOT EXISTS `poll_questions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `poll_id` int(11) NOT NULL,
        `client_id` int(11) NOT NULL,
        `question_text` text NOT NULL,
        `question_order` int(11) NOT NULL DEFAULT 1,
        `media_type` enum('none','image','video','audio') NOT NULL DEFAULT 'none',
        `media_path` varchar(500) DEFAULT NULL,
        `is_required` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_poll_id` (`poll_id`),
        KEY `idx_client_id` (`client_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($createPollQuestionsTable);
    echo "<p>✅ poll_questions table created/verified</p>";
    
    // Create poll_options table
    $createPollOptionsTable = "CREATE TABLE IF NOT EXISTS `poll_options` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `question_id` int(11) NOT NULL,
        `poll_id` int(11) NOT NULL,
        `client_id` int(11) NOT NULL,
        `option_text` varchar(500) NOT NULL,
        `option_order` int(11) NOT NULL DEFAULT 1,
        `media_type` enum('none','image','video','audio') NOT NULL DEFAULT 'none',
        `media_path` varchar(500) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_question_id` (`question_id`),
        KEY `idx_poll_id` (`poll_id`),
        KEY `idx_client_id` (`client_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($createPollOptionsTable);
    echo "<p>✅ poll_options table created/verified</p>";
    
    // Create poll_votes table
    $createPollVotesTable = "CREATE TABLE IF NOT EXISTS `poll_votes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `poll_id` int(11) NOT NULL,
        `question_id` int(11) NOT NULL,
        `option_id` int(11) NOT NULL,
        `client_id` int(11) NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `voter_ip` varchar(45) DEFAULT NULL,
        `voter_session` varchar(255) DEFAULT NULL,
        `comment` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `idx_poll_id` (`poll_id`),
        KEY `idx_question_id` (`question_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_client_id` (`client_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($createPollVotesTable);
    echo "<p>✅ poll_votes table created/verified</p>";
    
    // Check if created_at column exists in poll_votes, add if missing
    $stmt = $conn->query("SHOW COLUMNS FROM poll_votes LIKE 'created_at'");
    if (!$stmt->fetch()) {
        echo "<p>⚠️ created_at column missing in poll_votes - adding it...</p>";
        $conn->exec("ALTER TABLE poll_votes ADD COLUMN created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER client_id");
        echo "<p>✅ created_at column added to poll_votes</p>";
    } else {
        echo "<p>✅ created_at column exists in poll_votes</p>";
    }
    
    // Check if user_profiles table exists, if not create a simple one
    $stmt = $conn->query("SHOW TABLES LIKE 'user_profiles'");
    if (!$stmt->fetch()) {
        $createUserProfilesTable = "CREATE TABLE IF NOT EXISTS `user_profiles` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `full_name` varchar(255) NOT NULL,
            `profile_picture` varchar(500) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($createUserProfilesTable);
        echo "<p>✅ user_profiles table created</p>";
        
        // Insert a sample profile for testing
        $stmt = $conn->prepare("INSERT IGNORE INTO user_profiles (user_id, full_name) VALUES (1, 'Test User')");
        $stmt->execute();
    } else {
        echo "<p>✅ user_profiles table already exists</p>";
    }
    
    // Check if course_applicability table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'course_applicability'");
    if (!$stmt->fetch()) {
        echo "<p>⚠️ course_applicability table missing - this might cause issues with course-specific polls</p>";
    } else {
        echo "<p>✅ course_applicability table exists</p>";
    }
    
    // Check if user_groups table exists, create if missing
    $stmt = $conn->query("SHOW TABLES LIKE 'user_groups'");
    if (!$stmt->fetch()) {
        echo "<p>⚠️ user_groups table missing - creating it...</p>";
        
        $createUserGroupsTable = "CREATE TABLE IF NOT EXISTS `user_groups` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `group_id` int(11) NOT NULL,
            `client_id` int(11) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_group_id` (`group_id`),
            KEY `idx_client_id` (`client_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($createUserGroupsTable);
        echo "<p>✅ user_groups table created</p>";
    } else {
        echo "<p>✅ user_groups table exists</p>";
    }
    
    echo "<h3>Testing poll query...</h3>";
    
    // Test the poll query
    try {
        $testSql = "SELECT p.*, up.full_name as created_by_name
                    FROM polls p
                    LEFT JOIN user_profiles up ON p.created_by = up.id
                    WHERE p.is_deleted = 0 
                    AND p.client_id = 1 
                    LIMIT 1";
        
        $stmt = $conn->prepare($testSql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✅ Poll query test successful</p>";
        echo "<p>Found " . count($result) . " polls</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Poll query test failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Creating a test poll...</h3>";
    
    // Create a test poll
    $testPollSql = "INSERT IGNORE INTO polls (client_id, title, description, type, target_audience, start_datetime, end_datetime, status, created_by) 
                    VALUES (1, 'Test Poll', 'This is a test poll to verify functionality', 'single_choice', 'global', NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY), 'active', 1)";
    
    $conn->exec($testPollSql);
    $pollId = $conn->lastInsertId();
    
    if ($pollId) {
        echo "<p>✅ Test poll created with ID: $pollId</p>";
        
        // Create a test question
        $testQuestionSql = "INSERT INTO poll_questions (poll_id, client_id, question_text, question_order) 
                           VALUES ($pollId, 1, 'What is your favorite color?', 1)";
        $conn->exec($testQuestionSql);
        $questionId = $conn->lastInsertId();
        
        if ($questionId) {
            echo "<p>✅ Test question created with ID: $questionId</p>";
            
            // Create test options
            $options = ['Red', 'Blue', 'Green', 'Yellow'];
            foreach ($options as $index => $option) {
                $testOptionSql = "INSERT INTO poll_options (question_id, poll_id, client_id, option_text, option_order) 
                                 VALUES ($questionId, $pollId, 1, '$option', " . ($index + 1) . ")";
                $conn->exec($testOptionSql);
            }
            echo "<p>✅ Test options created</p>";
        }
    }
    
    echo "<h3>✅ Database setup complete!</h3>";
    echo "<p><a href='/Unlockyourskills/polls'>Test Polls Page</a></p>";
    echo "<p><a href='/Unlockyourskills/social-dashboard'>Test Social Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
