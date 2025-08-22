<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>
<?php require_once 'core/IdEncryption.php'; ?>

<?php
/**
 * Get audio progress data for a specific content
 */
function getAudioProgressData($courseId, $contentId, $userId) {
    try {
        $database = new Database();
        $conn = $database->connect();
        
        $sql = "SELECT ap.*, 
                       ap.audio_status,
                       ap.playback_status,
                       ap.is_completed,
                       ap.listened_percentage,
                       ap.last_listened_at,
                       ap.play_count,
                       ap.updated_at
                FROM audio_progress ap
                WHERE ap.course_id = ? AND ap.content_id = ? AND ap.user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$courseId, $contentId, $userId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progress) {
            // Determine status based on completion and playback
            $status = [
                'status' => $progress['audio_status'],
                'playback_status' => $progress['playback_status'],
                'is_completed' => $progress['is_completed'],
                'progress' => $progress['listened_percentage'],
                'last_listened_at' => $progress['last_listened_at'],
                'play_count' => $progress['play_count']
            ];
        } else {
            $status = [
                'status' => 'not_started',
                'playback_status' => 'not_started',
                'is_completed' => false,
                'progress' => 0,
                'last_listened_at' => null,
                'play_count' => 0
            ];
        }
        
        return [
            'progress' => $progress ? $progress['listened_percentage'] : 0,
            'status' => $status
        ];
        
    } catch (Exception $e) {
        error_log("Error getting audio progress data: " . $e->getMessage());
        return [
            'progress' => 0,
            'status' => [
                'status' => 'not_started',
                'playback_status' => 'not_started',
                'is_completed' => false,
                'progress' => 0,
                'last_listened_at' => null,
                'play_count' => 0
            ]
        ];
    }
}

// Helper to get video progress data
function getVideoProgressData($courseId, $contentId, $userId) {
    try {
        $database = new Database();
        $conn = $database->connect();
        
        $sql = "SELECT vp.*, 
                       vp.is_completed,
                       vp.video_status,
                       vp.watched_percentage,
                       vp.last_watched_at,
                       vp.play_count,
                       vp.updated_at
                FROM video_progress vp
                WHERE vp.course_id = ? AND vp.content_id = ? AND vp.user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$courseId, $contentId, $userId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progress) {
            return [
                'progress' => intval($progress['watched_percentage'] ?? 0),
                'is_completed' => (bool)$progress['is_completed'],
                'video_status' => $progress['video_status'] ?? 'not_started',
                'current_time' => intval($progress['current_time'] ?? 0),
                'duration' => intval($progress['duration'] ?? 0),
                'play_count' => intval($progress['play_count'] ?? 0),
                'last_watched_at' => $progress['last_watched_at']
            ];
        } else {
            return [
                'progress' => 0,
                'is_completed' => false,
                'video_status' => 'not_started',
                'current_time' => 0,
                'duration' => 0,
                'play_count' => 0,
                'last_watched_at' => null
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting video progress data: " . $e->getMessage());
        return [
            'progress' => 0,
            'is_completed' => false,
            'current_time' => 0,
            'duration' => 0,
            'play_count' => 0,
            'last_watched_at' => null
        ];
    }
}

// Helper to get image progress data
function getImageProgressData($courseId, $contentId, $userId) {
    try {
        $database = new Database();
        $conn = $database->connect();
        
        $sql = "SELECT ip.*, 
                       ip.image_status,
                       ip.is_completed,
                       ip.view_count,
                       ip.viewed_at,
                       ip.updated_at
                FROM image_progress ip
                WHERE ip.course_id = ? AND ip.content_id = ? AND ip.user_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$courseId, $contentId, $userId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progress) {
            return [
                'progress' => $progress['is_completed'] ? 100 : 0,
                'is_completed' => (bool)$progress['is_completed'],
                'image_status' => $progress['image_status'] ?? 'not_viewed',
                'view_count' => intval($progress['view_count'] ?? 0),
                'viewed_at' => $progress['viewed_at']
            ];
        } else {
            return [
                'progress' => 0,
                'is_completed' => false,
                'image_status' => 'not_viewed',
                'view_count' => 0,
                'viewed_at' => null
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting image progress data: " . $e->getMessage());
        return [
            'progress' => 0,
            'is_completed' => false,
            'image_status' => 'not_viewed',
            'view_count' => 0,
            'viewed_at' => null
        ];
    }
}
?>

<style>
    .prerequisites-completion-status,
    .modules-completion-status {
        background: rgba(255, 255, 255, 0.7);
        border-radius: 12px;
        padding: 16px;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .prerequisites-completion-status .progress,
    .modules-completion-status .progress {
        border-radius: 6px;
        background-color: rgba(0, 0, 0, 0.1);
    }
    
    .prerequisites-completion-status .progress-bar,
    .modules-completion-status .progress-bar {
        border-radius: 6px;
        transition: width 0.6s ease;
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
    }
    
    .alert {
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .alert-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #fff8e1 100%);
        color: #856404;
        border-left: 4px solid #ffc107;
    }
    
    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #e8f4f8 100%);
        color: #0c5460;
        border-left: 4px solid #17a2b8;
    }
    
    .progress-bar.animate {
        animation: progressPulse 1s ease-in-out;
    }
    
    /* Toast Notification Styles */
    .toast-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 16px 20px;
        z-index: 10000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        border-left: 4px solid #007bff;
        max-width: 350px;
    }
    
    .toast-notification.show {
        transform: translateX(0);
    }
    
    .toast-notification.toast-success {
        border-left-color: #28a745;
    }
    
    .toast-notification.toast-error {
        border-left-color: #dc3545;
    }
    
    .toast-notification.toast-warning {
        border-left-color: #ffc107;
    }
    
    .toast-notification.toast-info {
        border-left-color: #17a2b8;
    }
    
    .toast-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .toast-content i {
        font-size: 1.2rem;
    }
    
    .toast-content i.fa-check-circle {
        color: #28a745;
    }
    
    .toast-content i.fa-exclamation-circle {
        color: #dc3545;
    }
    
    .toast-content i.fa-info-circle {
        color: #17a2b8;
    }
    
    .toast-content span {
        font-weight: 500;
        color: #495057;
    }
    
    /* SCORM Progress Status Styles */
    .scorm-progress-status {
        padding: 8px 12px;
        border-radius: 6px;
        background: #f8f9fa;
        border-left: 3px solid #dee2e6;
    }
    
    .scorm-progress-status .text-success {
        color: #198754 !important;
        font-weight: 500;
    }
    
    .scorm-progress-status .text-warning {
        color: #fd7e14 !important;
        font-weight: 500;
    }
    
    .scorm-progress-status .text-muted {
        color: #6c757d !important;
    }
    
    .scorm-completed-badge {
        margin-top: 8px;
    }
    
    .scorm-completed-badge .badge {
        font-size: 0.875rem;
        padding: 6px 12px;
    }
    
    .scorm-progress-status small {
        font-size: 0.75rem;
    }
    

    
    /* Enhanced Progress Bar Styles */
    .module-progress-section {
        margin: 15px 0;
    }
    
    .progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    
    .progress-text {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }
    
    .refresh-progress-btn {
        padding: 4px 8px;
        font-size: 0.75rem;
        border-radius: 4px;
        transition: all 0.2s ease;
    }
    
    .refresh-progress-btn:hover {
        transform: scale(1.05);
    }
    
    .refresh-progress-btn:active {
        transform: scale(0.95);
    }
    
    .progress {
        height: 8px;
        border-radius: 4px;
        background-color: #e9ecef;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .progress-bar {
        transition: width 0.6s ease;
        border-radius: 4px;
    }
    
    .progress-bar.bg-success {
        background: linear-gradient(45deg, #28a745, #20c997);
    }
    
    .progress-bar.bg-primary {
        background: linear-gradient(45deg, #007bff, #0056b3);
    }
    
    .progress-bar.bg-secondary {
        background: linear-gradient(45deg, #6c757d, #495057);
    }
    
    /* Content Progress Styles */
    .content-progress {
        min-width: 120px;
    }
    
    .content-progress-info {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    
    .content-progress-text {
        font-size: 0.8rem;
        font-weight: 600;
        color: #495057;
    }
    
    .content-progress-bar {
        width: 100%;
        height: 6px;
        background-color: #e9ecef;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .content-progress-fill {
        height: 100%;
        background: linear-gradient(45deg, #007bff, #0056b3);
        border-radius: 3px;
        transition: width 0.4s ease;
    }
    
    /* Progress Animation */
    @keyframes progressPulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    

    
    /* Progress bar updating animation */
    .content-progress-fill.updating {
        animation: progressPulse 1s ease-in-out;
    }
</style>

<div class="main-content">
    <div class="container mt-4 course-details" id="courseDetailsPage">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container d-flex align-items-center gap-2">
            <a href="<?= UrlHelper::url('my-courses') ?>" class="back-link me-2 d-inline-flex align-items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line mx-2" style="display:inline-block"></span>
            <h1 class="page-title text-purple mb-0 d-flex align-items-center"><i class="fas fa-info-circle me-2"></i><?= Localization::translate('course_details'); ?></h1>
        </div>

        <!-- Banner with overlay -->
        <div class="course-banner position-relative mb-4" style="height: 260px; border-radius: 18px; overflow: hidden; background: #f8f9fa;">
            <?php if (!empty($course['banner_image'])): ?>
                <img src="/Unlockyourskills/<?= htmlspecialchars($course['banner_image']) ?>" alt="<?= htmlspecialchars($course['name']) ?>" class="w-100 h-100" style="object-fit: cover;">
            <?php elseif (!empty($course['thumbnail_image'])): ?>
                <img src="/Unlockyourskills/<?= htmlspecialchars($course['thumbnail_image']) ?>" alt="<?= htmlspecialchars($course['name']) ?>" class="w-100 h-100" style="object-fit: cover;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 w-100">
                    <i class="fas fa-book-open fa-5x text-secondary"></i>
                </div>
            <?php endif; ?>
            <div class="banner-overlay"></div>
            <div class="banner-content">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge badge-course-type"><i class="fas fa-layer-group me-1"></i><?= htmlspecialchars($course['course_type'] ?? 'e-learning') ?></span>
                    <?php if (!empty($course['category_name'])): ?>
                        <span class="badge badge-category"><i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($course['category_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($course['subcategory_name'])): ?>
                        <span class="badge badge-subcategory"><i class="fas fa-tags me-1"></i><?= htmlspecialchars($course['subcategory_name']) ?></span>
                    <?php endif; ?>
                </div>
                <h2 class="banner-title mt-2 mb-0"><?= htmlspecialchars($course['name']) ?></h2>
            </div>
        </div>

        <?php
        // Helper to resolve URLs for content paths
        if (!function_exists('resolveContentUrl')) {
            function resolveContentUrl($path) {
                if (empty($path)) {
                    return '';
                }
                if (preg_match('#^https?://#i', $path)) {
                    return $path;
                }
                if ($path[0] === '/') {
                    return $path;
                }
                return UrlHelper::url($path);
            }
        }
        
        // Helper to check if all prerequisites are completed
        if (!function_exists('arePrerequisitesCompleted')) {
            function arePrerequisitesCompleted($prerequisites, $assessmentResults) {
                if (empty($prerequisites)) {
                    return true; // No prerequisites means all are completed
                }
                
                foreach ($prerequisites as $pre) {
                    if ($pre['prerequisite_type'] === 'assessment') {
                        $assessmentId = $pre['prerequisite_id'];
                        $result = $assessmentResults[$assessmentId] ?? null;
                        
                        // Assessment prerequisite is only completed if passed
                        if (!$result || !$result['passed']) {
                            return false;
                        }
                    } else {
                        // For non-assessment prerequisites (courses, modules, etc.), 
                        // we assume they need to be completed manually or have completion tracking
                        // For now, we'll consider them as completed if they exist
                        // This can be enhanced later with actual completion tracking
                    }
                }
                
                return true;
            }
        }
        
        // Helper to calculate module real progress dynamically
        if (!function_exists('calculateModuleRealProgress')) {
            function calculateModuleRealProgress($moduleId, $courseId, $userId) {
                try {
                    // Get database connection
                    $db = new PDO(
                        'mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=unlockyourskills',
                        'root',
                        ''
                    );
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Get all content items in the module
                    $stmt = $db->prepare("
                        SELECT id, content_type 
                        FROM course_module_content 
                        WHERE module_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
                        ORDER BY content_order ASC
                    ");
                    $stmt->execute([$moduleId]);
                    $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (empty($contentItems)) {
                        return 0;
                    }
                    
                    $totalProgress = 0;
                    $totalItems = count($contentItems);
                    
                    foreach ($contentItems as $content) {
                        $contentId = $content['id'];
                        $contentType = $content['content_type'];
                        $progress = 0;
                        
                        switch ($contentType) {
                            case 'audio':
                                $audioProgressData = getAudioProgressData($courseId, $contentId, $userId);
                                $progress = $audioProgressData['progress'];
                                break;
                                
                            case 'video':
                                $videoProgressData = getVideoProgressData($courseId, $contentId, $userId);
                                $progress = $videoProgressData['progress'];
                                break;
                                
                            case 'image':
                                $imageProgressData = getImageProgressData($courseId, $contentId, $userId);
                                $progress = $imageProgressData['progress'];
                                break;
                                
                            case 'document':
                                $progress = getDocumentProgressStatus($courseId, $contentId, $userId);
                                break;
                                
                            case 'scorm':
                                $scormProgress = getScormProgressStatus($courseId, $contentId, $userId);
                                $progress = $scormProgress['progress_percentage'];
                                break;
                                
                            default:
                                // For other content types, check if they have any progress
                                $stmt = $db->prepare("
                                    SELECT cp.status, cp.completion_percentage 
                                    FROM content_progress cp
                                    JOIN course_enrollments ce ON cp.enrollment_id = ce.id
                                    WHERE cp.content_id = ? AND ce.user_id = ?
                                ");
                                $stmt->execute([$contentId, $userId]);
                                $genProgress = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($genProgress) {
                                    $progress = intval($genProgress['completion_percentage'] ?? 0);
                                }
                                break;
                        }
                        
                        $totalProgress += $progress;
                    }
                    
                    return $totalItems > 0 ? round($totalProgress / $totalItems) : 0;
                    
                } catch (Exception $e) {
                    error_log("Error calculating module real progress: " . $e->getMessage());
                    return 0;
                }
            }
        }
        
        // Helper to check if all modules are completed
        if (!function_exists('areModulesCompleted')) {
            function areModulesCompleted($modules) {
                if (empty($modules)) {
                    return true; // No modules means all are completed
                }
                
                foreach ($modules as $module) {
                    // Check if module has content and if all content is completed
                    if (!empty($module['content'])) {
                        // Use real_progress if available, fallback to module_progress
                        $progress = intval($module['real_progress'] ?? $module['module_progress'] ?? 0);
                        if ($progress < 100) {
                            return false;
                        }
                    }
                }
                
                return true;
            }
        }
        
        // Helper to check if all modules are completed
        if (!function_exists('areModulesCompleted')) {
            function areModulesCompleted($modules) {
                if (empty($modules)) {
                    return true; // No modules means all are completed
                }
                
                foreach ($modules as $module) {
                    // Check if module has content and if all content is completed
                    if (!empty($module['content'])) {
                        // Calculate real progress including audio progress
                        $realProgress = calculateModuleRealProgress($module['id'], $GLOBALS['course']['id'], $_SESSION['user']['id']);
                        $progress = intval($realProgress ?? $module['real_progress'] ?? $module['module_progress'] ?? 0);
                        if ($progress < 100) {
                            return false;
                        }
                    }
                }
                
                return true;
            }
        }
        
        // Helper to get SCORM progress status
        if (!function_exists('getScormProgressStatus')) {
            function getScormProgressStatus($courseId, $contentId, $userId) {
                try {
                    // Validate inputs
                    if (empty($courseId) || empty($contentId) || empty($userId)) {
                        return [
                            'status' => 'unknown',
                            'location' => '',
                            'score' => 0,
                            'max_score' => 100,
                            'session_time' => '',
                            'last_updated' => '',
                            'has_progress' => false,
                            'progress_percentage' => 0
                        ];
                    }
                    
                    // Get database connection
                    $db = new PDO(
                        'mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=unlockyourskills',
                        'root',
                        ''
                    );
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Query SCORM progress
                    $stmt = $db->prepare("
                        SELECT lesson_status, lesson_location, score_raw, score_max, session_time, updated_at, suspend_data 
                        FROM scorm_progress 
                        WHERE course_id = ? AND content_id = ? AND user_id = ?
                    ");
                    $stmt->execute([$courseId, $contentId, $userId]);
                    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($progress) {
                        // Calculate progress percentage based on SCORM data
                        $progressPercentage = 0;
                        $status = $progress['lesson_status'] ?? 'incomplete';
                        
                        if ($status === 'completed') {
                            $progressPercentage = 100;
                        } elseif ($status === 'incomplete') {
                            // Calculate progress based on lesson_location and suspend_data
                            if (!empty($progress['lesson_location'])) {
                                $progressPercentage = 75; // Has location data - user has navigated
                            } elseif (!empty($progress['suspend_data'])) {
                                $progressPercentage = 50; // Has suspend data - user has interacted
                            } else {
                                $progressPercentage = 25; // Just started
                            }
                        }
                        
                        return [
                            'status' => $status,
                            'location' => $progress['lesson_location'] ?? '',
                            'score' => $progress['score_raw'] ?? 0,
                            'max_score' => $progress['score_max'] ?? 100,
                            'session_time' => $progress['session_time'] ?? '',
                            'last_updated' => $progress['updated_at'] ?? '',
                            'has_progress' => true,
                            'progress_percentage' => $progressPercentage
                        ];
                    } else {
                        return [
                            'status' => 'not_started',
                            'location' => '',
                            'score' => 0,
                            'max_score' => 100,
                            'session_time' => '',
                            'last_updated' => '',
                            'has_progress' => false,
                            'progress_percentage' => 0
                        ];
                    }
                } catch (Exception $e) {
                    // Log error for debugging
                    error_log("Error getting SCORM progress: " . $e->getMessage());
                    
                    // Return default status if database error
                    return [
                        'status' => 'unknown',
                        'location' => '',
                        'score' => 0,
                        'max_score' => 100,
                        'session_time' => '',
                        'last_updated' => '',
                        'has_progress' => false,
                        'progress_percentage' => 0
                    ];
                }
            }
        }
        
        // Helper to get document progress status
        if (!function_exists('getDocumentProgressStatus')) {
            function getDocumentProgressStatus($courseId, $contentId, $userId) {
                try {
                    // Validate inputs
                    if (empty($courseId) || empty($contentId) || empty($userId)) {
                        return 0;
                    }
                    
                    // Get database connection
                    $db = new PDO(
                        'mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=unlockyourskills',
                        'root',
                        ''
                    );
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Get client_id from session
                    $clientId = $_SESSION['user']['client_id'] ?? null;
                    
                    // Query document progress
                    $sql = "SELECT viewed_percentage, is_completed, time_spent, last_viewed_at 
                            FROM document_progress 
                            WHERE content_id = :content_id AND user_id = :user_id AND course_id = :course_id";
                    
                    if ($clientId) {
                        $sql .= " AND client_id = :client_id";
                    }
                    
                    $stmt = $db->prepare($sql);
                    $params = [
                        ':content_id' => $contentId,
                        ':user_id' => $userId,
                        ':course_id' => $courseId
                    ];
                    
                    if ($clientId) {
                        $params[':client_id'] = $clientId;
                    }
                    
                    $stmt->execute($params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        if ($result['is_completed']) {
                            return 100;
                        } else {
                            return floatval($result['viewed_percentage']);
                        }
                    }
                    
                    return 0;
                } catch (Exception $e) {
                    error_log("Error getting document progress: " . $e->getMessage());
                    return 0;
                }
            }
        }
        
        // Helper to get document progress data (similar to SCORM)
        if (!function_exists('getDocumentProgressData')) {
            function getDocumentProgressData($courseId, $contentId, $userId) {
                try {
                    // Validate inputs
                    if (empty($courseId) || empty($contentId) || empty($userId)) {
                        return [
                            'progress' => 0,
                            'status' => ['status' => 'not_started', 'text' => 'Not Started'],
                            'last_viewed_at' => null
                        ];
                    }
                    
                    // Get database connection
                    $db = new PDO(
                        'mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=unlockyourskills',
                        'root',
                        ''
                    );
                    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Get client_id from session
                    $clientId = $_SESSION['user']['client_id'] ?? null;
                    
                    // Query document progress with all needed data
                    $sql = "SELECT status, viewed_percentage, is_completed, current_page, last_viewed_at 
                            FROM document_progress 
                            WHERE content_id = :content_id AND user_id = :user_id AND course_id = :course_id";
                    
                    if ($clientId) {
                        $sql .= " AND client_id = :client_id";
                    }
                    
                    $stmt = $db->prepare($sql);
                    $params = [
                        ':content_id' => $contentId,
                        ':user_id' => $userId,
                        ':course_id' => $courseId
                    ];
                    
                    if ($clientId) {
                        $params[':client_id'] = $clientId;
                    }
                    
                    $stmt->execute($params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        // Calculate progress
                        $progress = 0;
                        if ($result['is_completed']) {
                            $progress = 100;
                        } elseif ($result['viewed_percentage'] >= 80) {
                            $progress = 100;
                        } elseif ($result['viewed_percentage'] > 0) {
                            $progress = floatval($result['viewed_percentage']);
                        } elseif ($result['current_page'] > 1) {
                            $progress = 25;
                        }
                        
                        // Get status
                        $status = getDocumentCompletionStatus($courseId, $contentId, $userId);
                        
                        return [
                            'progress' => $progress,
                            'status' => $status,
                            'last_viewed_at' => $result['last_viewed_at']
                        ];
                    }
                    
                    return [
                        'progress' => 0,
                        'status' => ['status' => 'not_started', 'text' => 'Not Started'],
                        'last_viewed_at' => null
                    ];
                    
                } catch (Exception $e) {
                    error_log("Error getting document progress data: " . $e->getMessage());
                    return [
                        'progress' => 0,
                        'status' => ['status' => 'error', 'text' => 'Error'],
                        'last_viewed_at' => null
                    ];
                }
            }
        }
        
        // Helper to get document completion status
        if (!function_exists('getDocumentCompletionStatus')) {
            function getDocumentCompletionStatus($courseId, $contentId, $userId) {
                try {
                    // Validate inputs
                    if (empty($courseId) || empty($contentId) || empty($userId)) {
                        return ['status' => 'not_started', 'text' => 'Not Started'];
                    }
                    
                    // Check if PDO is available
                    if (!class_exists('PDO')) {
                        error_log("PDO class not available");
                        return ['status' => 'error', 'text' => 'Database Error'];
                    }
                    
                    // Get database connection
                    $db = new PDO(
                        'mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=unlockyourskills',
                        'root',
                        ''
                    );
                    
                    // Set error mode with fallback
                    if (defined('PDO::ERRMODE_EXCEPTION')) {
                        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    }
                    
                    // Get client_id from session
                    $clientId = $_SESSION['user']['client_id'] ?? null;
                    
                    // Simple query using status column directly
                    $sql = "SELECT status, viewed_percentage, is_completed, current_page 
                            FROM document_progress 
                            WHERE content_id = :content_id AND user_id = :user_id AND course_id = :course_id";
                    
                    if ($clientId) {
                        $sql .= " AND client_id = :client_id";
                    }
                    
                    $stmt = $db->prepare($sql);
                    $params = [
                        ':content_id' => $contentId,
                        ':user_id' => $userId,
                        ':course_id' => $courseId
                    ];
                    
                    if ($clientId) {
                        $params[':client_id'] = $clientId;
                    }
                    
                    $stmt->execute($params);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Get status directly from database column
                    if ($result && isset($result['status'])) {
                        $status = $result['status'];
                        $text = match($status) {
                            'completed' => 'Completed',
                            'in_progress' => 'In Progress',
                            'started' => 'Started',
                            'not_started' => 'Not Started',
                            default => 'Unknown'
                        };
                        return ['status' => $status, 'text' => $text];
                    }
                    
                    // Fallback for existing records without status column
                    if ($result) {
                        if ($result['is_completed']) {
                            return ['status' => 'completed', 'text' => 'Completed'];
                        } elseif ($result['viewed_percentage'] >= 80) {
                            return ['status' => 'completed', 'text' => 'Completed'];
                        } elseif ($result['viewed_percentage'] > 0) {
                            return ['status' => 'in_progress', 'text' => 'In Progress'];
                        } elseif ($result['current_page'] > 1) {
                            return ['status' => 'started', 'text' => 'Started'];
                        }
                    }
                    
                    return ['status' => 'not_started', 'text' => 'Not Started'];
                } catch (Exception $e) {
                    error_log("Error getting document completion status: " . $e->getMessage());
                    return ['status' => 'error', 'text' => 'Error'];
                }
            }
        }
        
        // Helper to pretty duration
        $durationParts = [];
        if (!empty($course['duration_hours'])) { $durationParts[] = intval($course['duration_hours']) . 'h'; }
        if (!empty($course['duration_minutes'])) { $durationParts[] = intval($course['duration_minutes']) . 'm'; }
        $prettyDuration = !empty($durationParts) ? implode(' ', $durationParts) : null;
        $moduleCount = !empty($course['modules']) && is_array($course['modules']) ? count($course['modules']) : 0;
        ?>

        <!-- Meta + Description -->
        <div class="card mb-4 p-4 shadow-sm">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-8">
                    <h5 class="text-purple fw-bold mb-2"><i class="fas fa-align-left me-2"></i>About this course</h5>
                    <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($course['description'] ?? 'No description available.')) ?></p>
                </div>
                <div class="col-lg-4">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-stream"></i></div>
                            <div class="meta-text">
                                <div class="meta-label">Modules</div>
                                <div class="meta-value"><?= $moduleCount ?></div>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-clock"></i></div>
                            <div class="meta-text">
                                <div class="meta-label">Duration</div>
                                <div class="meta-value"><?= htmlspecialchars($prettyDuration ?? 'N/A') ?></div>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-signal"></i></div>
                            <div class="meta-text">
                                <div class="meta-label">Difficulty</div>
                                <div class="meta-value"><?= htmlspecialchars(ucfirst($course['difficulty_level'] ?? 'beginner')) ?></div>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-eye"></i></div>
                            <div class="meta-text">
                                <div class="meta-label">Visibility</div>
                                <div class="meta-value"><?= htmlspecialchars(ucfirst($course['course_status'] ?? 'active')) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Prerequisites -->
        <?php if (!empty($course['prerequisites'])): ?>
        <div class="card mb-4 p-4 shadow-sm border-0" style="background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%); border-left: 4px solid #6a0dad !important;">
            <div class="d-flex align-items-center mb-3">
                <div class="prerequisite-icon-wrapper me-3">
                    <i class="fas fa-list-check text-white"></i>
                </div>
                <div>
                    <h5 class="text-purple fw-bold mb-1">Prerequisites</h5>
                    <p class="text-muted small mb-0">Complete these requirements to access this course</p>
                </div>
            </div>
            
            <?php 
            // Show completion status
            $completedCount = 0;
            $totalCount = count($course['prerequisites']);
            foreach ($course['prerequisites'] as $pre) {
                if ($pre['prerequisite_type'] === 'assessment') {
                    $assessmentId = $pre['prerequisite_id'];
                    $result = $GLOBALS['assessmentResults'][$assessmentId] ?? null;
                    if ($result && $result['passed']) {
                        $completedCount++;
                    }
                } else {
                    // For non-assessment prerequisites, consider them as completed for now
                    $completedCount++;
                }
            }
            $completionPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
            ?>
            
            <div class="prerequisites-completion-status mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted">Prerequisites Progress</span>
                    <span class="badge <?= $completionPercentage === 100 ? 'bg-success' : 'bg-warning' ?>">
                        <?= $completedCount ?>/<?= $totalCount ?> Completed
                    </span>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar <?= $completionPercentage === 100 ? 'bg-success' : 'bg-warning' ?>" 
                         style="width: <?= $completionPercentage ?>%"></div>
                </div>
                <?php if ($completionPercentage === 100): ?>
                    <div class="text-success small mt-1">
                        <i class="fas fa-check-circle me-1"></i>All prerequisites completed! You can now access the course content.
                    </div>
                <?php else: ?>
                    <div class="text-muted small mt-1">
                        <i class="fas fa-info-circle me-1"></i>Complete all prerequisites to unlock course modules.
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="prerequisites-grid">
                <?php foreach ($course['prerequisites'] as $pre): ?>
                    <div class="prerequisite-item">
                        <div class="prerequisite-card<?= ($pre['prerequisite_type'] === 'assessment' && !empty($GLOBALS['assessmentAttempts'][$pre['prerequisite_id']])) ? ' has-attempts' : '' ?>">
                            <div class="prerequisite-content">
                                <div class="prerequisite-icon">
                                    <?php
                                        $type = $pre['prerequisite_type'] ?? 'course';
                                        $iconMap = [
                                            'assessment' => 'fa-clipboard-check',
                                            'survey' => 'fa-square-poll-vertical',
                                            'feedback' => 'fa-comments',
                                            'assignment' => 'fa-file-signature',
                                            'course' => 'fa-book-open',
                                            'module' => 'fa-layer-group',
                                            'scorm' => 'fa-cubes',
                                            'video' => 'fa-video',
                                            'audio' => 'fa-headphones',
                                            'document' => 'fa-file-lines',
                                            'image' => 'fa-image',
                                            'interactive' => 'fa-wand-magic-sparkles'
                                        ];
                                        $icon = $iconMap[$type] ?? 'fa-book-open';
                                        $colorMap = [
                                            'assessment' => '#28a745',
                                            'survey' => '#17a2b8',
                                            'feedback' => '#ffc107',
                                            'assignment' => '#fd7e14',
                                            'course' => '#6a0dad',
                                            'module' => '#6f42c1',
                                            'scorm' => '#e83e8c',
                                            'video' => '#dc3545',
                                            'audio' => '#fd7e14',
                                            'document' => '#6c757d',
                                            'image' => '#20c997',
                                            'interactive' => '#6f42c1'
                                        ];
                                        $color = $colorMap[$type] ?? '#6a0dad';
                                    ?>
                                    <i class="fas <?= $icon ?>" style="color: <?= $color ?>;"></i>
                                </div>
                                <div class="prerequisite-details">
                                    <h6 class="prerequisite-title mb-1" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="<?= htmlspecialchars($pre['title']) ?>">
                                        <?= htmlspecialchars($pre['title']) ?>
                                    </h6>
                                    <span class="prerequisite-type"><?= htmlspecialchars(ucfirst($type)) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($pre['prerequisite_type']) && in_array($pre['prerequisite_type'], ['assessment','survey','feedback','assignment'])): ?>
                                <?php 
                                $assessmentId = $pre['prerequisite_id'];
                                $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                                $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                                
                                if ($pre['prerequisite_type'] === 'assessment') {
                                    // Get assessment results for pass/fail status
                                    $assessmentResults = $GLOBALS['assessmentResults'][$assessmentId] ?? null;
                                    $hasPassed = $assessmentResults && $assessmentResults['passed'];
                                    
                                    // Check if user has exceeded maximum attempts
                                    $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                    $attemptCount = count($assessmentAttempts);
                                    $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                    
                                    // Show pass/fail status
                                    if ($assessmentResults) {
                                        $statusClass = $hasPassed ? 'text-success' : 'text-danger';
                                        $statusIcon = $hasPassed ? 'fa-check-circle' : 'fa-times-circle';
                                        $statusText = $hasPassed ? 'Passed' : 'Failed';
                                        echo "<div class='assessment-status mb-2'>";
                                        echo "<div class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>{$statusText}</div>";
                                        if ($hasPassed) {
                                            echo "<div class='text-muted mt-1'>(Score: {$assessmentResults['score']}/{$assessmentResults['max_score']} - {$assessmentResults['percentage']}%)</div>";
                                        }
                                        echo "</div>";
                                    }
                                    
                                    if ($hasPassed) {
                                        // Assessment passed - disable start button
                                        echo "<button class='prerequisite-action-btn prerequisite-action-disabled' disabled title='Assessment completed successfully'>";
                                        echo "<i class='fas fa-check me-1'></i>Completed";
                                        echo "</button>";
                                    } elseif ($hasExceededAttempts) {
                                        // Disable start button and show attempts exceeded message
                                        echo "<button class='prerequisite-action-btn prerequisite-action-disabled' disabled title='Maximum attempts reached'>";
                                        echo "<i class='fas fa-ban me-1'></i>Attempts Exceeded";
                                        echo "</button>";
                                    } else {
                                        // Show start button with attempt count
                                        $encryptedPrereqId = IdEncryption::encrypt($pre['prerequisite_id']);
                                        echo "<a class='prerequisite-action-btn' target='_blank' href='" . UrlHelper::url('my-courses/start') . '?type=' . urlencode($pre['prerequisite_type']) . '&id=' . urlencode($encryptedPrereqId) . "'>";
                                        echo "<i class='fas fa-play me-1'></i>Start";
                                        echo "</a>";
                                    }
                                } else {
                                    // Non-assessment content types with appropriate labels
                                    $encryptedPrereqId = IdEncryption::encrypt($pre['prerequisite_id']);
                                    
                                    // Define button labels and icons based on content type
                                    $buttonConfig = [
                                        'survey' => [
                                            'label' => 'Take Survey',
                                            'icon' => 'fa-clipboard-list',
                                            'class' => 'btn-info'
                                        ],
                                        'feedback' => [
                                            'label' => 'Give Feedback',
                                            'icon' => 'fa-comment-dots',
                                            'class' => 'btn-warning'
                                        ],
                                        'assignment' => [
                                            'label' => 'Start Assignment',
                                            'icon' => 'fa-file-pen',
                                            'class' => 'btn-primary'
                                        ],
                                        'course' => [
                                            'label' => 'Start Course',
                                            'icon' => 'fa-play',
                                            'class' => 'btn-success'
                                        ],
                                        'module' => [
                                            'label' => 'Start Module',
                                            'icon' => 'fa-play',
                                            'class' => 'btn-success'
                                        ],
                                        'scorm' => [
                                            'label' => 'Launch SCORM',
                                            'icon' => 'fa-cube',
                                            'class' => 'btn-secondary'
                                        ],
                                        'video' => [
                                            'label' => 'Watch Video',
                                            'icon' => 'fa-play',
                                            'class' => 'btn-danger'
                                        ],
                                        'audio' => [
                                            'label' => 'Listen Audio',
                                            'icon' => 'fa-play',
                                            'class' => 'btn-warning'
                                        ],
                                        'document' => [
                                            'label' => 'View Document',
                                            'icon' => 'fa-file-lines',
                                            'class' => 'btn-secondary'
                                        ],
                                        'image' => [
                                            'label' => 'View Image',
                                            'icon' => 'fa-image',
                                            'class' => 'btn-info'
                                        ],
                                        'interactive' => [
                                            'label' => 'Launch Interactive',
                                            'icon' => 'fa-wand-magic-sparkles',
                                            'class' => 'btn-primary'
                                        ]
                                    ];
                                    
                                    $config = $buttonConfig[$pre['prerequisite_type']] ?? [
                                        'label' => 'Start',
                                        'icon' => 'fa-play',
                                        'class' => 'btn-secondary'
                                    ];
                                    
                                    echo "<a class='prerequisite-action-btn {$config['class']}' target='_blank' href='" . UrlHelper::url('my-courses/start') . '?type=' . urlencode($pre['prerequisite_type']) . '&id=' . urlencode($encryptedPrereqId) . "'>";
                                    echo "<i class='fas {$config['icon']} me-1'></i>{$config['label']}";
                                    echo "</a>";
                                }
                                ?>
                            <?php else: ?>
                                <span class="prerequisite-status">
                                    <i class="fas fa-check-circle me-1"></i>Required
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($pre['prerequisite_type']) && $pre['prerequisite_type'] === 'assessment'): ?>
                            <?php 
                            $assessmentId = $pre['prerequisite_id'];
                            $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                            $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                            
                            if (!empty($assessmentDetails)) {
                                $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                $attemptCount = count($assessmentAttempts);
                                $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                
                                if ($hasExceededAttempts) {
                                    // Show attempts summary for exceeded attempts
                                    echo "<div class='attempts-summary mt-2'>";
                                    echo "<small class='text-muted'>";
                                    echo "<i class='fas fa-info-circle me-1'></i>";
                                    echo "You have used all " . $attemptCount . " attempts for this assessment.";
                                    echo "</small>";
                                    echo "</div>";
                                } else {
                                    if (!empty($assessmentAttempts)) {
                                        // Show attempts remaining
                                        $attemptsRemaining = $maxAttempts - $attemptCount;
                                        echo "<div class='attempts-summary mt-2'>";
                                        echo "<small class='text-muted'>";
                                        echo "<i class='fas fa-clock me-1'></i>";
                                        echo "Attempts remaining: " . $attemptsRemaining;
                                        echo "</small>";
                                        echo "</div>";
                                    } else {
                                        // Show total attempts available for first attempt
                                        echo "<div class='attempts-summary mt-2'>";
                                        echo "<small class='text-muted'>";
                                        echo "<i class='fas fa-info-circle me-1'></i>";
                                        echo "Total attempts available: " . $maxAttempts;
                                        echo "</small>";
                                        echo "</div>";
                                    }
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        // Check if prerequisites are completed
        $prerequisitesCompleted = arePrerequisitesCompleted($course['prerequisites'] ?? [], $GLOBALS['assessmentResults'] ?? []);
        ?>
        
        <!-- Course Content -->
        <?php if ($prerequisitesCompleted): ?>
        <div class="card mb-4 p-4 shadow-sm border-0" style="background: linear-gradient(135deg, #f0f8ff 0%, #ffffff 100%); border-left: 4px solid #007bff !important;">
            <div class="d-flex align-items-center mb-4">
                <div class="course-content-icon-wrapper me-3">
                    <i class="fas fa-layer-group text-white"></i>
                </div>
                <div>
                    <h5 class="text-purple fw-bold mb-1">Course Content</h5>
                    <p class="text-muted small mb-0">Explore modules and learning materials</p>
                </div>
            </div>
            
            <?php 
            // Show modules completion status
            $modulesCompletedCount = 0;
            $modulesTotalCount = count($course['modules']);
            $overallModuleProgress = 0;
            foreach ($course['modules'] as $module) {
                // Calculate real progress dynamically
                $realProgress = calculateModuleRealProgress($module['id'], $course['id'], $_SESSION['user']['id']);
                $progress = intval($realProgress ?? $module['real_progress'] ?? $module['module_progress'] ?? 0);
                $overallModuleProgress += $progress;
                if ($progress >= 100) {
                    $modulesCompletedCount++;
                }
            }
            $overallModuleProgress = $modulesTotalCount > 0 ? round($overallModuleProgress / $modulesTotalCount) : 0;
            ?>
            
            <div class="modules-completion-status mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted">Course Progress</span>
                    <span class="badge <?= $overallModuleProgress === 100 ? 'bg-success' : 'bg-primary' ?>">
                        <?= $overallModuleProgress ?>% Complete
                    </span>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar <?= $overallModuleProgress === 100 ? 'bg-success' : 'bg-primary' ?>" 
                         style="width: <?= $overallModuleProgress ?>%"></div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <span class="text-muted small">
                        <i class="fas fa-layer-group me-1"></i><?= $modulesCompletedCount ?>/<?= $modulesTotalCount ?> Modules Completed
                    </span>
                    <?php if ($overallModuleProgress === 100): ?>
                        <span class="text-success small">
                            <i class="fas fa-check-circle me-1"></i>Course completed! Post-requisites are now available.
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($course['modules'])): ?>
                <div class="course-modules-container" data-course-id="<?= $course['id'] ?>">
                    <?php foreach ($course['modules'] as $idx => $module): ?>
                        <div class="module-item" data-module-id="<?= $module['id'] ?>">
                            <div class="module-header" data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>" aria-expanded="false" aria-controls="collapse<?= $idx ?>">
                                <div class="module-header-content">
                                    <div class="module-header-left">
                                        <div class="module-info">
                                            <div class="module-icon-wrapper me-3">
                                                <i class="fas fa-folder text-white"></i>
                                            </div>
                                            <div class="module-details">
                                                <div class="module-number">Module <?= ($idx + 1) ?></div>
                                                <h6 class="module-title mb-1" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
                                                    title="<?= htmlspecialchars($module['title']) ?>">
                                                    <?= htmlspecialchars($module['title']) ?>
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="module-header-center">
                                        <div class="module-progress-section">
                                            <div class="progress-info">
                                                <div class="progress-text">
                                                    <?php 
                                                    // Calculate real progress dynamically
                                                    $realProgress = calculateModuleRealProgress($module['id'], $course['id'], $_SESSION['user']['id']);
                                                    $realProgress = intval($realProgress ?? $module['real_progress'] ?? $module['module_progress'] ?? 0);
                                                    $completedItems = intval($module['completed_items'] ?? 0);
                                                    $totalItems = intval($module['total_items'] ?? 0);
                                                    echo $realProgress . '% Complete';
                                                    if ($totalItems > 0) {
                                                        echo ' (' . $completedItems . '/' . $totalItems . ')';
                                                    }
                                                    ?>
                                                </div>
                                                <div class="progress" role="progressbar" aria-label="Module progress" aria-valuenow="<?= $realProgress ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <div class="progress-bar <?= $realProgress >= 100 ? 'bg-success' : ($realProgress > 0 ? 'bg-primary' : 'bg-secondary') ?>" 
                                                         style="width: <?= $realProgress ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="module-header-actions">
                                        <button class="btn btn-sm btn-outline-primary refresh-progress-btn" 
                                                onclick="refreshModuleProgressSimple(<?= $module['id'] ?>, <?= $GLOBALS['course']['id'] ?>)"
                                                title="Refresh progress">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <div class="module-toggle">
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="collapse<?= $idx ?>" class="module-content collapse">
                                <?php if (!empty($module['content'])): ?>
                                    <div class="content-grid">
                                        <?php foreach ($module['content'] as $content): ?>
                                                                                                    <?php
                                                            $type = $content['type'];
                                                            
                                                            // Initialize assessment variables for attempts summary
                                                            $assessmentId = null;
                                                            $assessmentAttempts = [];
                                                            $assessmentDetails = [];
                                                            $hasExceededAttempts = false;
                                                            $maxAttempts = 0;
                                                            $attemptCount = 0;
                                                            
                                                            $iconMap = [
                                                    'scorm' => 'fa-cubes',
                                                    'non_scorm' => 'fa-cube',
                                                    'interactive' => 'fa-wand-magic-sparkles',
                                                    'video' => 'fa-video',
                                                    'audio' => 'fa-headphones',
                                                    'document' => 'fa-file-lines',
                                                    'image' => 'fa-image',
                                                    'external' => 'fa-link',
                                                    'assessment' => 'fa-clipboard-check',
                                                    'survey' => 'fa-square-poll-vertical',
                                                    'feedback' => 'fa-comments',
                                                    'assignment' => 'fa-file-signature',
                                                ];
                                                $icon = $iconMap[$type] ?? 'fa-file';
                                                $colorMap = [
                                                    'scorm' => '#e83e8c',
                                                    'non_scorm' => '#6f42c1',
                                                    'interactive' => '#6f42c1',
                                                    'video' => '#dc3545',
                                                    'audio' => '#fd7e14',
                                                    'document' => '#6c757d',
                                                    'image' => '#20c997',
                                                    'external' => '#17a2b8',
                                                    'assessment' => '#28a745',
                                                    'survey' => '#17a2b8',
                                                    'feedback' => '#ffc107',
                                                    'assignment' => '#fd7e14',
                                                ];
                                                $color = $colorMap[$type] ?? '#6c757d';
                                            ?>
                                            <div class="content-item-card<?= ($content['type'] === 'assessment' && !empty($GLOBALS['assessmentAttempts'][$content['content_id']])) ? ' has-attempts' : '' ?>">
                                                <div class="content-item-left">
                                                    <div class="content-item-header">
                                                        <div class="content-icon-wrapper me-3" style="background: <?= $color ?>;">
                                                            <i class="fas <?= $icon ?> text-white"></i>
                                                        </div>
                                                        <div class="content-details">
                                                            <h6 class="content-title mb-1" 
                                                                data-bs-toggle="tooltip" 
                                                                data-bs-placement="top" 
                                                                title="<?= htmlspecialchars($content['title']) ?>">
                                                                <?= htmlspecialchars($content['title']) ?>
                                                            </h6>
                                                            <span class="content-type-badge"><?= htmlspecialchars(ucfirst($type)) ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="content-item-center">
                                                    <div class="content-progress">
                                                        <div class="content-progress-info">
                                                            <?php
                                                            // Calculate progress for different content types
                                                            $progressPercentage = 0;
                                                            if ($content['type'] === 'scorm') {
                                                                $scormProgress = getScormProgressStatus($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                $progressPercentage = $scormProgress['progress_percentage'];
                                                            } elseif ($content['type'] === 'document') {
                                                                // Get document progress from document_progress table
                                                                $progressPercentage = getDocumentProgressStatus($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                
                                                                // Get document completion status
                                                                $documentStatus = getDocumentCompletionStatus($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                            } elseif ($content['type'] === 'audio') {
                                                                // Get audio progress from audio_progress table
                                                                $audioProgressData = getAudioProgressData($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                $progressPercentage = intval($audioProgressData['progress']);
                                                            } elseif ($content['type'] === 'video') {
                                                                // Get video progress from video_progress table
                                                                $videoProgressData = getVideoProgressData($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                $progressPercentage = $videoProgressData['progress'];
                                                            } elseif ($content['type'] === 'image') {
                                                                // Get image progress from image_progress table
                                                                $imageProgressData = getImageProgressData($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                $progressPercentage = $imageProgressData['progress'];
                                                            } else {
                                                                $progressPercentage = intval($content['progress'] ?? 0);
                                                            }
                                                            ?>
                                                            <span class="content-progress-text"><?= $progressPercentage ?>%</span>
                                                            <div class="content-progress-bar" role="progressbar" aria-label="Content progress" aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="content-progress-fill" style="width: <?= $progressPercentage ?>%"></div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="content-item-right">
                                                    <div class="content-controls">
                                                        <?php
                                                            // Normalize output into status and actions for alignment
                                                            $statusHtml = '';
                                                            $actionsHtml = '';
                                                            switch ($content['type']) {
                                                                case 'scorm':
                                                                    $launchUrl = $content['scorm_launch_path'] ?? '';
                                                                    if ($launchUrl) {
                                                                        $scormProgress = getScormProgressStatus($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                        // Build status
                                                                        $statusHtml .= "<div class='scorm-progress-status mb-2'>";
                                                                        if ($scormProgress['has_progress']) {
                                                                            $statusClass = $scormProgress['status'] === 'completed' ? 'text-success' : 'text-warning';
                                                                            $statusIcon = $scormProgress['status'] === 'completed' ? 'fa-check-circle' : 'fa-clock';
                                                                            $statusHtml .= "<div class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>" . ucfirst($scormProgress['status']);
                                                                            if (!empty($scormProgress['score'])) {
                                                                                $statusHtml .= " - Score: {$scormProgress['score']}/{$scormProgress['max_score']}";
                                                                            }
                                                                            if (!empty($scormProgress['last_updated'])) {
                                                                                $statusHtml .= " <small class='text-muted'>(Last: " . date('M j, Y', strtotime($scormProgress['last_updated'])) . ")</small>";
                                                                            }
                                                                            $statusHtml .= "</div>";
                                                                        } else {
                                                                            $statusHtml .= "<div class='text-muted'><i class='fas fa-circle me-1'></i>Not started</div>";
                                                                        }
                                                                        $statusHtml .= "</div>";
                                                                        // Actions
                                                                        if ($scormProgress['status'] !== 'completed') {
                                                                            $scormLauncherUrl = UrlHelper::url('scorm/launch') . '?course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'] . '&title=' . urlencode($content['title']);
                                                                            $actionsHtml .= "<a href='" . htmlspecialchars($scormLauncherUrl) . "' target='_blank' class='postrequisite-action-btn btn-secondary launch-content-btn' data-module-id='" . $module['id'] . "' data-content-id='" . $content['id'] . "' data-type='scorm' onclick='launchNewSCORMPlayer(event, " . $GLOBALS['course']['id'] . ", " . $module['id'] . ", " . $content['id'] . ", \"" . addslashes($content['title']) . "\")'><i class='fas fa-cube me-1'></i>Launch</a>";
                                                                        } else {
                                                                            $statusHtml .= "<div class='scorm-completed-badge'><span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>SCORM Completed</span></div>";
                                                                        }
                                                                    } else {
                                                                        $statusHtml .= "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No SCORM launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'non_scorm':
                                                                case 'interactive':
                                                                    $launchUrl = $content['non_scorm_launch_path'] ?? ($content['interactive_launch_url'] ?? '');
                                                                    if ($launchUrl) {
                                                                        $resolved = resolveContentUrl($launchUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=iframe&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        $actionsHtml .= "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-primary launch-content-btn' data-module-id='" . $module['id'] . "' data-content-id='" . $content['id'] . "' data-type='" . $type . "'><i class='fas fa-wand-magic-sparkles me-1'></i>Launch Interactive</a>";
                                                                    } else {
                                                                        $statusHtml .= "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'video':
                                                                    $videoUrl = $content['video_file_path'] ?? '';
                                                                    if ($videoUrl) {
                                                                        $resolved = resolveContentUrl($videoUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=video&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        
                                                                        // Get video progress status
                                                                        $videoProgressData = getVideoProgressData($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                        
                                                                                                                // Build status
                                        $statusHtml .= "<div class='video-progress-status mb-2'>";
                                        if ($videoProgressData['progress'] > 0) {
                                            $statusClass = $videoProgressData['is_completed'] ? 'text-success' : 'text-warning';
                                            $statusIcon = $videoProgressData['is_completed'] ? 'fa-check-circle' : 'fa-play-circle';
                                            $statusHtml .= "<div class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>";
                                            if ($videoProgressData['is_completed']) {
                                                $statusHtml .= "Completed";
                                            } else {
                                                $statusHtml .= "In Progress - " . $videoProgressData['progress'] . "%";
                                            }
                                            if (!empty($videoProgressData['last_watched_at'])) {
                                                $statusHtml .= " <small class='text-muted'>(Last: " . date('M j, Y', strtotime($videoProgressData['last_watched_at'])) . ")</small>";
                                            }
                                            $statusHtml .= "</div>";
                                        } else {
                                            $statusHtml .= "<div class='text-muted'><i class='fas fa-circle me-1'></i>Not started</div>";
                                        }
                                        $statusHtml .= "</div>";
                                                                        
                                                                        // Actions
                                                                        if (!$videoProgressData['is_completed']) {
                                                                            $actionsHtml .= "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-danger launch-content-btn' data-module-id='" . $module['id'] . "' data-content-id='" . $content['id'] . "' data-type='video' data-video-package-id='" . $content['content_id'] . "' onclick='launchVideoPlayer(event, " . $GLOBALS['course']['id'] . ", " . $module['id'] . ", " . $content['id'] . ", \"" . addslashes($content['title']) . "\")'><i class='fas fa-play me-1'></i>Watch Video</a>";
                                                                        } else {
                                                                            $statusHtml .= "<div class='video-completed-badge'><span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>Video Completed</span></div>";
                                                                        }
                                                                    } else {
                                                                        $statusHtml .= "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No video file</span>";
                                                                    }
                                                                    break;
                                                                case 'audio':
                                                                    $audioUrl = $content['audio_file_path'] ?? '';
                                                                    if ($audioUrl) {
                                                                        $resolved = resolveContentUrl($audioUrl);
                                                                        
                                                                        // Get audio progress and status with additional data
                                                                        $audioProgressData = getAudioProgressData($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                        $audioProgress = intval($audioProgressData['progress']);
                                                                        $audioStatus = $audioProgressData['status'];
                                                                        $lastListenedAt = $audioStatus['last_listened_at'];
                                                                        
                                                                        // Build status exactly like documents
                                                                        $statusHtml .= "<div class='document-progress-status mb-2'>";
                                                                        if ($audioProgress > 0) {
                                                                            $statusClass = $audioStatus['status'] === 'completed' ? 'text-success' : 'text-warning';
                                                                            $statusIcon = $audioStatus['status'] === 'completed' ? 'fa-check-circle' : 'fa-clock';
                                                                            $statusHtml .= "<div class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>" . ucfirst($audioStatus['status']);
                                                                            if ($audioProgress > 0) {
                                                                                $statusHtml .= " - Progress: {$audioProgress}%";
                                                                            }
                                                                            // Add last listened date like documents
                                                                            if ($lastListenedAt) {
                                                                                $lastListened = date('M j, Y', strtotime($lastListenedAt));
                                                                                $statusHtml .= " <small class='text-muted'>(Last: {$lastListened})</small>";
                                                                            }
                                                                            $statusHtml .= "</div>";
                                                                        } else {
                                                                            $statusHtml .= "<div class='text-muted'><i class='fas fa-circle me-1'></i>Not started</div>";
                                                                        }
                                                                        $statusHtml .= "</div>";
                                                                        
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=audio&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        
                                                                        // Show completed badge if audio is completed
                                                                        if ($audioStatus['status'] === 'completed') {
                                                                            $statusHtml .= "<div class='document-completed-badge'><span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>Audio Completed</span></div>";
                                                                        } else {
                                                                            $actionsHtml .= "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-warning launch-content-btn' data-module-id='" . $module['id'] . "' data-content-id='" . $content['id'] . "' data-type='audio'><i class='fas fa-headphones me-1'></i>Listen Audio</a>";
                                                                        }
                                                                    } else {
                                                                        $statusHtml .= "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No audio file</span>";
                                                                    }
                                                                    break;
                                                                case 'document':
                                                                    $docUrl = $content['document_file_path'] ?? '';
                                                                    if ($docUrl) {
                                                                        $resolved = resolveContentUrl($docUrl);
                                                                        // Get the actual document package ID from the content
                                                                        $documentPackageId = $content['content_id'] ?? null;
                                                                        
                                                                                                                // Get document progress and status with additional data
                                        $documentProgressData = getDocumentProgressData($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                        $documentProgress = $documentProgressData['progress'];
                                        $documentStatus = $documentProgressData['status'];
                                        $lastViewedAt = $documentProgressData['last_viewed_at'];
                                                                        
                                                                                                                // Build status exactly like SCORM
                                        $statusHtml .= "<div class='document-progress-status mb-2'>";
                                        if ($documentProgress > 0) {
                                            $statusClass = $documentStatus['status'] === 'completed' ? 'text-success' : 'text-warning';
                                            $statusIcon = $documentStatus['status'] === 'completed' ? 'fa-check-circle' : 'fa-clock';
                                            $statusHtml .= "<div class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>" . ucfirst($documentStatus['status']);
                                            if ($documentProgress > 0) {
                                                $statusHtml .= " - Progress: {$documentProgress}%";
                                            }
                                            // Add last viewed date like SCORM
                                            if ($lastViewedAt) {
                                                $lastViewed = date('M j, Y', strtotime($lastViewedAt));
                                                $statusHtml .= " <small class='text-muted'>(Last: {$lastViewed})</small>";
                                            }
                                            $statusHtml .= "</div>";
                                        } else {
                                            $statusHtml .= "<div class='text-muted'><i class='fas fa-circle me-1'></i>Not started</div>";
                                        }
                                        $statusHtml .= "</div>";
                                                                        
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=document&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'] . '&document_package_id=' . $documentPackageId;
                                                                        
                                                                        // Show completed badge if document is completed
                                                                        if ($documentStatus['status'] === 'completed') {
                                                                            $statusHtml .= "<div class='document-completed-badge'><span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>Document Completed</span></div>";
                                                                        } else {
                                                                            $actionsHtml .= "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-secondary launch-content-btn' data-module-id='" . $module['id'] . "' data-content-id='" . $content['id'] . "' data-type='document'><i class='fas fa-file-lines me-1'></i>View Document</a>";
                                                                        }
                                                                    } else {
                                                                        $statusHtml .= "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No document file</span>";
                                                                    }
                                                                    break;
                                                                case 'image':
                                                                    $imgUrl = $content['image_file_path'] ?? '';
                                                                    if ($imgUrl) {
                                                                        $resolved = resolveContentUrl($imgUrl);
                                                                        
                                                                        // Get image progress and status
                                                                        $imageProgressData = getImageProgressData($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                        
                                                                        // Build status exactly like documents
                                                                        $statusHtml .= "<div class='image-progress-status mb-2'>";
                                                                        if ($imageProgressData['is_completed']) {
                                                                            $statusHtml .= "<div class='text-success'><i class='fas fa-check-circle me-1'></i>Viewed";
                                                                            if ($imageProgressData['view_count'] > 0) {
                                                                                $statusHtml .= " - View Count: {$imageProgressData['view_count']}";
                                                                            }
                                                                            if ($imageProgressData['viewed_at']) {
                                                                                $lastViewed = date('M j, Y', strtotime($imageProgressData['viewed_at']));
                                                                                $statusHtml .= " <small class='text-muted'>(Last: {$lastViewed})</small>";
                                                                            }
                                                                            $statusHtml .= "</div>";
                                                                        } else {
                                                                            $statusHtml .= "<div class='text-muted'><i class='fas fa-circle me-1'></i>Not viewed</div>";
                                                                        }
                                                                        $statusHtml .= "</div>";
                                                                        
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=image&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'] . '&image_package_id=' . $content['content_id'] . '&client_id=' . $_SESSION['user']['client_id'];
                                                                        
                                                                        // Show completed badge if image is viewed
                                                                        if ($imageProgressData['is_completed']) {
                                                                            $statusHtml .= "<div class='image-completed-badge'><span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>Image Viewed</span></div>";
                                                                        } else {
                                                                            $actionsHtml .= "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-info launch-content-btn' data-module-id='" . $module['id'] . "' data-content-id='" . $content['id'] . "' data-type='image' data-image-package-id='" . $content['content_id'] . "' onclick='launchImagePlayer(event, " . $GLOBALS['course']['id'] . ", " . $module['id'] . ", " . $content['id'] . ", \"" . addslashes($content['title']) . "\")'><i class='fas fa-image me-1'></i>View Image</a>";
                                                                        }
                                                                    } else {
                                                                        $statusHtml .= "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No image file</span>";
                                                                    }
                                                                    break;
                                                                case 'external':
                                                                    $extUrl = $content['external_content_url'] ?? '';
                                                                    if ($extUrl) {
                                                                        $resolved = resolveContentUrl($extUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=external&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        $actionsHtml .= "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-info launch-content-btn' data-module-id='" . $module['id'] . "' data-content-id='" . $content['id'] . "' data-type='external'><i class='fas fa-external-link-alt me-1'></i>Open Link</a>";
                                                                    } else {
                                                                        $statusHtml .= "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No external link</span>";
                                                                    }
                                                                    break;
                                                                case 'assessment':
                                                                    $assessmentId = $content['content_id'];
                                                                    $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                                                                    $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                                                                    $assessmentResults = $GLOBALS['assessmentResults'][$assessmentId] ?? null;
                                                                    $hasPassed = $assessmentResults && $assessmentResults['passed'];
                                                                    $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                                                    $attemptCount = count($assessmentAttempts);
                                                                    $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                                                    // Status
                                                                    if ($assessmentResults) {
                                                                        $statusClass = $hasPassed ? 'text-success' : 'text-danger';
                                                                        $statusIcon = $hasPassed ? 'fa-check-circle' : 'fa-times-circle';
                                                                        $statusText = $hasPassed ? 'Passed' : 'Failed';
                                                                        $statusHtml .= "<div class='assessment-status mb-2'><div class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>{$statusText}</div>";
                                                                        if ($hasPassed) {
                                                                            $statusHtml .= "<div class='text-muted mt-1'>(Score: {$assessmentResults['score']}/{$assessmentResults['max_score']} - {$assessmentResults['percentage']}%)</div>";
                                                                        }
                                                                        $statusHtml .= "</div>";
                                                                    }
                                                                    // Actions
                                                                    if ($hasPassed) {
                                                                        $actionsHtml .= "<button class='prerequisite-action-btn prerequisite-action-disabled' disabled title='Assessment completed successfully'><i class='fas fa-check me-1'></i>Completed</button>";
                                                                    } elseif ($hasExceededAttempts) {
                                                                        $actionsHtml .= "<button class='prerequisite-action-btn prerequisite-action-disabled' disabled title='Maximum attempts reached'><i class='fas fa-ban me-1'></i>Attempts Exceeded</button>";
                                                                    } else {
                                                                        $encryptedId = IdEncryption::encrypt($content['content_id']);
                                                                        $startUrl = UrlHelper::url('my-courses/start') . '?type=' . urlencode($type) . '&id=' . urlencode($encryptedId) . '&course_id=' . $GLOBALS['course']['id'];
                                                                        $actionsHtml .= "<a href='" . htmlspecialchars($startUrl) . "' target='_blank' class='prerequisite-action-btn'><i class='fas fa-play me-1'></i>Start</a>";
                                                                    }
                                                                    break;
                                                                case 'survey':
                                                                case 'feedback':
                                                                case 'assignment':
                                                                    $encryptedId = IdEncryption::encrypt($content['content_id']);
                                                                    $startUrl = UrlHelper::url('my-courses/start') . '?type=' . urlencode($type) . '&id=' . urlencode($encryptedId);
                                                                    $actionsHtml .= "<a href='" . htmlspecialchars($startUrl) . "' target='_blank' class='prerequisite-action-btn'><i class='fas fa-play me-1'></i>Start</a>";
                                                                    break;
                                                                default:
                                                                    $actionsHtml .= "<span class='postrequisite-action-btn btn-secondary' style='opacity: 0.6; cursor: not-allowed;' disabled><i class='fas fa-ban me-1'></i>Not available</span>";
                                                                    break;
                                                            }
                                                            echo "<div class='content-status'>" . $statusHtml . "</div>";
                                                            echo "<div class='content-actions'>" . $actionsHtml . "</div>";
                                                        ?>
                                                        <button type="button" class="content-toggle" data-bs-toggle="collapse" data-bs-target="#contentDetails<?= $idx ?>_<?= $content['id'] ?? $loop->index ?>" aria-expanded="false">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($type === 'assessment'): ?>
                                                <?php if ($hasExceededAttempts): ?>
                                                    <!-- Show attempts summary for exceeded attempts -->
                                                    <div class="attempts-summary mt-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-info-circle me-1"></i>
                                                            You have used all <?= $attemptCount ?> attempts for this assessment.
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <?php if (!empty($assessmentAttempts)): ?>
                                                        <!-- Show attempts remaining -->
                                                        <?php $attemptsRemaining = $maxAttempts - $attemptCount; ?>
                                                        <div class="attempts-summary mt-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                Attempts remaining: <?= $attemptsRemaining ?>
                                                            </small>
                                                        </div>
                                                    <?php else: ?>
                                                        <!-- Show total attempts available for first attempt -->
                                                        <div class="attempts-summary mt-2">
                                                            <small class="text-muted">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                Total attempts available: <?= $maxAttempts ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <!-- Content Details Collapsible Section -->
                                            <div id="contentDetails<?= $idx ?>_<?= $content['id'] ?? $loop->index ?>" class="content-details-collapse collapse">
                                                <div class="content-details-body">
                                                    <div class="content-metadata">
                                                        <div class="metadata-item">
                                                            <span class="metadata-label">Type:</span>
                                                            <span class="metadata-value"><?= htmlspecialchars(ucfirst($type)) ?></span>
                                                        </div>
                                                        <?php if (!empty($content['description'])): ?>
                                                        <div class="metadata-item">
                                                            <span class="metadata-label">Description:</span>
                                                            <span class="metadata-value"><?= htmlspecialchars($content['description']) ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($content['duration'])): ?>
                                                        <div class="metadata-item">
                                                            <span class="metadata-label">Duration:</span>
                                                            <span class="metadata-value"><?= htmlspecialchars($content['duration']) ?></span>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-content-message">
                                        <i class="fas fa-inbox text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No content in this module.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-modules-message">
                    <i class="fas fa-folder-open text-muted mb-3"></i>
                    <p class="text-muted mb-0">No modules available for this course.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                You must complete the prerequisites for this course to access the course content.
            </div>
        <?php endif; ?>

        <!-- Post-requisites -->
        <?php 
        // Check if both prerequisites and modules are completed
        $modulesCompleted = areModulesCompleted($course['modules'] ?? []);
        $canAccessPostRequisites = $prerequisitesCompleted && $modulesCompleted;
        
        // Debug information to help troubleshoot
        if (isset($_GET['debug']) && $_GET['debug'] === 'true'): ?>
            <div class="alert alert-info mb-4">
                <h6><i class="fas fa-bug me-2"></i>Debug Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <strong>Prerequisites:</strong><br>
                        <small class="text-muted">
                            Status: <?= $prerequisitesCompleted ? 'Completed' : 'Not Completed' ?><br>
                            Count: <?= count($course['prerequisites'] ?? []) ?><br>
                            <?php if (!empty($course['prerequisites'])): ?>
                                <?php foreach ($course['prerequisites'] as $pre): ?>
                                    - <?= $pre['prerequisite_type'] ?>: <?= $pre['prerequisite_id'] ?><br>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <div class="col-md-6">
                        <strong>Modules:</strong><br>
                        <small class="text-muted">
                            Status: <?= $modulesCompleted ? 'Completed' : 'Not Completed' ?><br>
                            Count: <?= count($course['modules'] ?? []) ?><br>
                            <?php if (!empty($course['modules'])): ?>
                                <?php foreach ($course['modules'] as $module): ?>
                                                                         - Module <?= $module['id'] ?>: <?= calculateModuleRealProgress($module['id'], $course['id'], $_SESSION['user']['id']) ?>%<br>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <div class="mt-2">
                    <strong>Post-requisites Access:</strong> <?= $canAccessPostRequisites ? 'Yes' : 'No' ?><br>
                    <strong>Post-requisites Count:</strong> <?= count($course['post_requisites'] ?? []) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($course['post_requisites']) && $canAccessPostRequisites): ?>
        <div class="card mb-4 p-4 shadow-sm border-0" style="background: linear-gradient(135deg, #fff8f9 0%, #ffffff 100%); border-left: 4px solid #20c997 !important;">
            <div class="d-flex align-items-center mb-3">
                <div class="postrequisite-icon-wrapper me-3">
                    <i class="fas fa-diagram-next text-white"></i>
                </div>
                <div>
                    <h5 class="text-purple fw-bold mb-1">Post-requisites</h5>
                    <p class="text-muted small mb-0">Available after completing this course</p>
                </div>
            </div>
            <div class="postrequisites-grid">
                <?php foreach ($course['post_requisites'] as $post): ?>
                    <div class="postrequisite-item">
                        <div class="postrequisite-card<?= ($post['content_type'] === 'assessment' && !empty($GLOBALS['assessmentAttempts'][$post['content_id']])) ? ' has-attempts' : '' ?>">
                            <div class="postrequisite-content">
                                <div class="postrequisite-icon">
                                    <?php
                                        $type = $post['content_type'] ?? 'assessment';
                                        $iconMap = [
                                            'assessment' => 'fa-clipboard-check',
                                            'survey' => 'fa-square-poll-vertical',
                                            'feedback' => 'fa-comments',
                                            'assignment' => 'fa-file-signature',
                                            'course' => 'fa-book-open',
                                            'module' => 'fa-layer-group',
                                            'scorm' => 'fa-cubes',
                                            'video' => 'fa-video',
                                            'audio' => 'fa-headphones',
                                            'document' => 'fa-file-lines',
                                            'image' => 'fa-image',
                                            'interactive' => 'fa-wand-magic-sparkles'
                                        ];
                                        $icon = $iconMap[$type] ?? 'fa-clipboard-check';
                                        $colorMap = [
                                            'assessment' => '#28a745',
                                            'survey' => '#17a2b8',
                                            'feedback' => '#ffc107',
                                            'assignment' => '#fd7e14',
                                            'course' => '#6a0dad',
                                            'module' => '#6f42c1',
                                            'scorm' => '#e83e8c',
                                            'video' => '#dc3545',
                                            'audio' => '#fd7e14',
                                            'document' => '#6c757d',
                                            'image' => '#20c997',
                                            'interactive' => '#6f42c1'
                                        ];
                                        $color = $colorMap[$type] ?? '#28a745';
                                    ?>
                                    <i class="fas <?= $icon ?>" style="color: <?= $color ?>;"></i>
                                </div>
                                <div class="postrequisite-details">
                                    <h6 class="postrequisite-title mb-1" 
                                        data-bs-toggle="tooltip" 
                                        data-bs-placement="top" 
                                        title="<?= htmlspecialchars($post['content_title'] ?? 'N/A') ?>">
                                        <?= htmlspecialchars($post['content_title'] ?? 'N/A') ?>
                                    </h6>
                                    <span class="postrequisite-type"><?= htmlspecialchars(ucfirst($type)) ?></span>
                                </div>
                            </div>
                            <?php 
                            $assessmentId = $post['content_id'];
                            $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                            $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                            
                            if ($post['content_type'] === 'assessment') {
                                // Get assessment results for pass/fail status
                                $assessmentResults = $GLOBALS['assessmentResults'][$assessmentId] ?? null;
                                $hasPassed = $assessmentResults && $assessmentResults['passed'];
                                
                                // Check if user has exceeded maximum attempts
                                $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                $attemptCount = count($assessmentAttempts);
                                $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                
                                // Show pass/fail status
                                if ($assessmentResults) {
                                    $statusClass = $hasPassed ? 'text-success' : 'text-danger';
                                    $statusIcon = $hasPassed ? 'fa-check-circle' : 'fa-times-circle';
                                    $statusText = $hasPassed ? 'Passed' : 'Failed';
                                    echo "<div class='assessment-status mb-2'>";
                                    echo "<div class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>{$statusText}</div>";
                                    if ($hasPassed) {
                                        echo "<div class='text-muted mt-1'>(Score: {$assessmentResults['score']}/{$assessmentResults['max_score']} - {$assessmentResults['percentage']}%)</div>";
                                    }
                                    echo "</div>";
                                }
                                
                                if ($hasPassed) {
                                    // Assessment passed - disable start button
                                    echo "<button class='postrequisite-action-btn postrequisite-action-disabled' disabled title='Assessment completed successfully'>";
                                    echo "<i class='fas fa-check me-1'></i>Completed";
                                    echo "</button>";
                                } elseif ($hasExceededAttempts) {
                                    // Disable start button and show attempts exceeded message
                                    echo "<button class='postrequisite-action-btn postrequisite-action-disabled' disabled title='Maximum attempts reached'>";
                                    echo "<i class='fas fa-ban me-1'></i>Attempts Exceeded";
                                    echo "</button>";
                                } else {
                                    // Show start button with attempt count
                                    $encryptedPostreqId = IdEncryption::encrypt($post['content_id']);
                                    echo "<a class='postrequisite-action-btn' target='_blank' href='" . UrlHelper::url('my-courses/start') . '?type=' . urlencode($post['content_type']) . '&id=' . urlencode($encryptedPostreqId) . "'>";
                                    echo "<i class='fas fa-play me-1'></i>Start";
                                    echo "</a>";
                                }
                            } else {
                                // Non-assessment content types with appropriate labels
                                $encryptedPostreqId = IdEncryption::encrypt($post['content_id']);
                                
                                // Define button labels and icons based on content type
                                $buttonConfig = [
                                    'survey' => [
                                        'label' => 'Take Survey',
                                        'icon' => 'fa-clipboard-list',
                                        'class' => 'btn-info'
                                    ],
                                    'feedback' => [
                                        'label' => 'Give Feedback',
                                        'icon' => 'fa-comment-dots',
                                        'class' => 'btn-warning'
                                    ],
                                    'assignment' => [
                                        'label' => 'Start Assignment',
                                        'icon' => 'fa-file-pen',
                                        'class' => 'btn-primary'
                                    ],
                                    'course' => [
                                        'label' => 'Start Course',
                                        'icon' => 'fa-play',
                                        'class' => 'btn-success'
                                    ],
                                    'module' => [
                                        'label' => 'Start Module',
                                        'icon' => 'fa-play',
                                        'class' => 'btn-success'
                                    ],
                                    'scorm' => [
                                        'label' => 'Launch SCORM',
                                        'icon' => 'fa-cube',
                                        'class' => 'btn-secondary'
                                    ],
                                    'video' => [
                                        'label' => 'Watch Video',
                                        'icon' => 'fa-play',
                                        'class' => 'btn-danger'
                                    ],
                                    'audio' => [
                                        'label' => 'Listen Audio',
                                        'icon' => 'fa-play',
                                        'class' => 'btn-warning'
                                    ],
                                    'document' => [
                                        'label' => 'View Document',
                                        'icon' => 'fa-file-lines',
                                        'class' => 'btn-secondary'
                                    ],
                                    'image' => [
                                        'label' => 'View Image',
                                        'icon' => 'fa-image',
                                        'class' => 'btn-info'
                                    ],
                                    'interactive' => [
                                        'label' => 'Launch Interactive',
                                        'icon' => 'fa-wand-magic-sparkles',
                                        'class' => 'btn-primary'
                                    ]
                                ];
                                
                                $config = $buttonConfig[$post['content_type']] ?? [
                                    'label' => 'Launch',
                                    'icon' => 'fa-rocket',
                                    'class' => 'btn-secondary'
                                ];
                                
                                // Handle SCORM content differently - use the new SCORM launcher
                                if ($post['content_type'] === 'scorm') {
                                    // For SCORM content, we need to get the actual content details to launch properly
                                    // Since this is postrequisite content, we'll need to construct the launch URL differently
                                    echo "<a class='postrequisite-action-btn {$config['class']}' href='#' onclick='launchPostrequisiteSCORM(event, \"" . addslashes($encryptedPostreqId) . "\", \"" . addslashes($post['content_type']) . "\")'>";
                                    echo "<i class='fas {$config['icon']} me-1'></i>{$config['label']}";
                                    echo "</a>";
                                } else {
                                    // For non-SCORM content, use the existing start method
                                    echo "<a class='postrequisite-action-btn {$config['class']}' target='_blank' href='" . UrlHelper::url('my-courses/start') . '?type=' . urlencode($post['content_type']) . '&id=' . urlencode($encryptedPostreqId) . "'>";
                                    echo "<i class='fas {$config['icon']} me-1'></i>{$config['label']}";
                                    echo "</a>";
                                }
                            }
                            ?>
                        </div>
                        
                        <?php if ($post['content_type'] === 'assessment'): ?>
                            <?php 
                            $assessmentId = $post['content_id'];
                            $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                            $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                            
                            if (!empty($assessmentDetails)) {
                                $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                $attemptCount = count($assessmentAttempts);
                                $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                
                                if ($hasExceededAttempts) {
                                    // Show attempts summary for exceeded attempts
                                    echo "<div class='attempts-summary mt-2'>";
                                    echo "<small class='text-muted'>";
                                    echo "<i class='fas fa-info-circle me-1'></i>";
                                    echo "You have used all " . $attemptCount . " attempts for this assessment.";
                                    echo "</small>";
                                    echo "</div>";
                                } else {
                                    if (!empty($assessmentAttempts)) {
                                        // Show attempts remaining
                                        $attemptsRemaining = $maxAttempts - $attemptCount;
                                        echo "<div class='attempts-summary mt-2'>";
                                        echo "<small class='text-muted'>";
                                        echo "<i class='fas fa-clock me-1'></i>";
                                        echo "Attempts remaining: " . $attemptsRemaining;
                                        echo "</small>";
                                        echo "</div>";
                                    } else {
                                        // Show total attempts available for first attempt
                                        echo "<div class='attempts-summary mt-2'>";
                                        echo "<small class='text-muted'>";
                                        echo "<i class='fas fa-info-circle me-1'></i>";
                                        echo "Total attempts available: " . $maxAttempts;
                                        echo "</small>";
                                        echo "</div>";
                                    }
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif (!empty($course['post_requisites']) && !$canAccessPostRequisites): ?>
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Post-requisites will be available after you complete all prerequisites and course modules.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Content Player Modal (kept for legacy triggers) -->
<div class="modal fade" id="contentPlayerModal" tabindex="-1" aria-labelledby="contentPlayerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="contentPlayerModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <div id="content-player-container" class="w-100 h-100"></div>
      </div>
    </div>
  </div>
</div>

<script src="/Unlockyourskills/public/js/course_player.js"></script>
<script src="/unlockyourskills/public/js/progress-tracking.js"></script>

<script>
// Expose user data to JavaScript for progress tracking
window.userData = {
    id: <?php echo json_encode($_SESSION['user']['id'] ?? null); ?>,
    client_id: <?php echo json_encode($_SESSION['user']['client_id'] ?? null); ?>
};

// Store user data in session storage for cross-tab access
if (window.userData.id && window.userData.client_id) {
    sessionStorage.setItem('user_data', JSON.stringify(window.userData));
}

// Debug: Check if course data is available
console.log('Debug: Course data available:', {
    course: <?php echo json_encode($GLOBALS['course'] ?? null); ?>,
    courseId: <?php echo json_encode($GLOBALS['course']['id'] ?? null); ?>,
    courseName: <?php echo json_encode($GLOBALS['course']['name'] ?? null); ?>
});

        // Function to launch image player
        function launchImagePlayer(event, courseId, moduleId, contentId, contentTitle) {
            event.preventDefault();
            const contentCard = event.target.closest('.content-item-card');
            if (!contentCard) { console.error('Content card not found'); return; }
            const imagePackageId = contentCard.querySelector('[data-image-package-id]')?.dataset.imagePackageId;
            if (!imagePackageId) { console.error('Image package ID not found'); return; }
            const clientId = <?= $_SESSION['user']['client_id'] ?? 'null' ?>;
            if (!clientId) { console.error('Client ID not found'); return; }
            console.log('Launching image player:', { courseId, moduleId, contentId, imagePackageId, clientId, contentTitle });
            const viewerUrl = `/Unlockyourskills/my-courses/view-content?type=image&course_id=${courseId}&module_id=${moduleId}&content_id=${contentId}&image_package_id=${imagePackageId}&client_id=${clientId}&title=${encodeURIComponent(contentTitle)}`;
            const imageWindow = window.open(viewerUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
            if (imageWindow) {
                console.log('Image player launched successfully');
                localStorage.setItem('image_closed_' + contentId, 'false');
                const checkClosed = setInterval(() => {
                    if (imageWindow.closed) {
                        clearInterval(checkClosed);
                        localStorage.setItem('image_closed_' + contentId, 'true');
                        console.log('Image window closed for content:', contentId);
                    }
                }, 1000);
            } else { console.error('Failed to open image player window'); }
        }

        // Function to launch video player
        function launchVideoPlayer(event, courseId, moduleId, contentId, contentTitle) {
    event.preventDefault();
    
    // Get the content card to extract video package ID
    const contentCard = event.target.closest('.content-item-card');
    if (!contentCard) {
        console.error('Content card not found');
        return;
    }
    
    // Get video package ID from data attribute
    const videoPackageId = contentCard.querySelector('[data-video-package-id]')?.dataset.videoPackageId;
    if (!videoPackageId) {
        console.error('Video package ID not found');
        return;
    }
    
    // Get user's client ID from the page
    const clientId = <?= $_SESSION['user']['client_id'] ?? 'null' ?>;
    if (!clientId) {
        console.error('Client ID not found');
        return;
    }
    
    console.log('Launching video player:', {
        courseId,
        moduleId,
        contentId,
        videoPackageId,
        clientId,
        contentTitle
    });
    
    // Create video viewer URL with correct course context
    const viewerUrl = `/Unlockyourskills/my-courses/view-content?type=video&course_id=${courseId}&module_id=${moduleId}&content_id=${contentId}&video_package_id=${videoPackageId}&client_id=${clientId}&title=${encodeURIComponent(contentTitle)}`;
    
    // Open video in new tab
    const videoWindow = window.open(viewerUrl, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    
    if (videoWindow) {
        console.log('Video player launched successfully');
        
        // Set close flag for monitoring
        localStorage.setItem('video_closed_' + contentId, 'false');
        
        // Monitor video window close
        const checkClosed = setInterval(() => {
            if (videoWindow.closed) {
                clearInterval(checkClosed);
                localStorage.setItem('video_closed_' + contentId, 'true');
                console.log('Video window closed for content:', contentId);
            }
        }, 1000);
    } else {
        console.error('Failed to open video player window');
    }
}
// Initialize Bootstrap tooltips for all title elements
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips for all elements with data-bs-toggle="tooltip"
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            trigger: 'hover',
            placement: 'top',
            html: true,
            customClass: 'custom-tooltip'
        });
    });
    
    // Re-initialize tooltips after dynamic content is loaded (for collapsible modules)
    document.addEventListener('shown.bs.collapse', function (event) {
        var newTooltipElements = event.target.querySelectorAll('[data-bs-toggle="tooltip"]');
        newTooltipElements.forEach(function(element) {
            if (!element.hasAttribute('data-bs-original-title')) {
                new bootstrap.Tooltip(element, {
                    trigger: 'hover',
                    placement: 'top',
                    html: true,
                    customClass: 'custom-tooltip'
                });
            }
        });
    });

    // Assessment completion detection and page refresh
    function checkForAssessmentCompletion() {
        // Get current course ID from URL or page data
        const currentCourseId = <?php echo json_encode($GLOBALS['course']['id'] ?? null); ?>;
        if (!currentCourseId) return;

        // Check localStorage for any assessment completion flags
        const keys = Object.keys(localStorage);
        const assessmentKeys = keys.filter(key => key.startsWith('assessment_completed_'));
        
        assessmentKeys.forEach(key => {
            try {
                const completionData = JSON.parse(localStorage.getItem(key));
                
                // Check if this completion is for the current course
                if (completionData.courseId == currentCourseId) {
                    console.log('Assessment completed for current course:', completionData);
                    
                    // Remove the flag from localStorage
                    localStorage.removeItem(key);
                    
                    // Show a notification to the user
                    showAssessmentCompletionNotification(completionData);
                    
                    // Refresh the page after a short delay to show updated status
                    setTimeout(() => {
                        console.log('Refreshing page to show updated assessment status...');
                        window.location.reload();
                    }, 2000);
                }
            } catch (error) {
                console.error('Error parsing assessment completion data:', error);
                // Remove invalid data
                localStorage.removeItem(key);
            }
        });
    }

    // Show notification about assessment completion
    function showAssessmentCompletionNotification(completionData) {
        const statusClass = completionData.passed ? 'success' : 'warning';
        const statusIcon = completionData.passed ? 'fa-check-circle' : 'fa-exclamation-triangle';
        const statusText = completionData.passed ? 'Passed' : 'Failed';
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${statusClass} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${statusIcon} me-2"></i>
                <div>
                    <strong>Assessment Completed!</strong><br>
                    <small>${completionData.assessmentId}: ${statusText} (${completionData.score}/${completionData.maxScore} - ${completionData.percentage}%)</small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Check for assessment completion every 2 seconds
    setInterval(checkForAssessmentCompletion, 2000);
    
    // Also check when the page becomes visible (user returns from assessment tab)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkForAssessmentCompletion();
        }
    });
    
    // Check immediately when page loads
    checkForAssessmentCompletion();
    
    // Initialize progress tracking for the current course
    initializeProgressTracking();
});

// Progress tracking initialization
function initializeProgressTracking() {
    const currentCourseId = <?php echo json_encode($GLOBALS['course']['id'] ?? null); ?>;
    console.log('Initializing progress tracking for course:', currentCourseId);
    console.log('Debug: Course object:', <?php echo json_encode($GLOBALS['course'] ?? null); ?>);
    
    if (!currentCourseId) {
        console.warn('No course ID available for progress tracking');
        console.warn('Course object:', <?php echo json_encode($GLOBALS['course'] ?? null); ?>);
        return;
    }
    
    // Wait for progress tracker to be available
    if (window.progressTracker) {
        window.progressTracker.setCourseContext(currentCourseId);
        console.log('Progress tracking initialized for course:', currentCourseId);
    } else {
        console.warn('Progress tracker not available, retrying...');
        setTimeout(initializeProgressTracking, 1000);
    }
}

// Add progress tracking to module content buttons
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('launch-content-btn')) {
        const courseId = <?php echo json_encode($GLOBALS['course']['id'] ?? null); ?>;
        const moduleId = event.target.dataset.moduleId;
        const contentId = event.target.dataset.contentId;
        const contentType = event.target.dataset.type;
        
        console.log('Content button clicked:', {
            courseId: courseId,
            moduleId: moduleId,
            contentId: contentId,
            contentType: contentType,
            progressTracker: !!window.progressTracker
        });
        
        if (window.progressTracker && courseId && moduleId && contentId && contentType) {
            // Set content context for progress tracking
            window.progressTracker.setCourseContext(courseId, moduleId, contentId, contentType);
            
            // Start progress tracking for this content (EXCLUDE SCORM - handled manually)
            if (contentType !== 'scorm') {
                window.progressTracker.startProgressTracking(contentId, contentType, 5000); // Update every 5 seconds
                console.log('Progress tracking started for:', {
                    courseId: courseId,
                    moduleId: moduleId,
                    contentId: contentId,
                    contentType: contentType
                });
            } else {
                console.log('SCORM content detected - automatic progress tracking DISABLED (handled manually)');
            }
        } else {
            console.warn('Progress tracking not available:', {
                hasProgressTracker: !!window.progressTracker,
                hasCourseId: !!courseId,
                hasModuleId: !!moduleId,
                hasContentId: !!contentId,
                hasContentType: !!contentType
            });
        }
    }
});

// New SCORM Launcher Function
function launchNewSCORMPlayer(event, courseId, moduleId, contentId, title) {
    event.preventDefault();
    
    console.log('Launching new SCORM player:', {
        courseId: courseId,
        moduleId: moduleId,
        contentId: contentId,
        title: title
    });
    
    // Build the SCORM launcher URL - use absolute path to avoid relative path issues
    const scormLauncherUrl = `${window.location.origin}/Unlockyourskills/scorm/launch?course_id=${courseId}&module_id=${moduleId}&content_id=${contentId}&title=${encodeURIComponent(title)}`;
    
    console.log('Constructed SCORM URL:', scormLauncherUrl);
    console.log('Current location:', window.location.href);
    console.log('Origin:', window.location.origin);
    
    // Open in new window/tab with session preservation
    const scormWindow = window.open(scormLauncherUrl, 'scorm_player', 'width=1200,height=800,scrollbars=yes,resizable=yes,menubar=yes,toolbar=yes');
    
    if (scormWindow) {
        console.log('SCORM player window opened successfully');
        
        // Focus the new window
        scormWindow.focus();
        
        // Optional: Add event listener for when the window is closed
        let hasRefreshed = false; // Prevent multiple refreshes
        let checkInterval = null;
        
        const checkClosed = setInterval(() => {
            if (scormWindow.closed && !hasRefreshed) {
                console.log('SCORM player window closed');
                clearInterval(checkClosed);
                hasRefreshed = true;
                
                // Add return parameter and refresh to show updated SCORM progress
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('scorm_return', 'true');
                window.history.replaceState({}, '', currentUrl);
                
                // Refresh after a short delay to show updated progress
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }
        }, 1000);
        
        // Store the interval reference for cleanup
        checkInterval = checkClosed;
        
        // Cleanup function to prevent memory leaks
        window.addEventListener('beforeunload', () => {
            if (checkInterval) {
                clearInterval(checkInterval);
            }
        });
        
    } else {
        console.error('Failed to open SCORM player window');
        alert('Failed to open SCORM player. Please check your popup blocker settings.');
    }
    
    return false;
}

// Function to handle SCORM content in postrequisites
function launchPostrequisiteSCORM(event, encryptedId, contentType) {
    event.preventDefault();
    
    console.log('Launching postrequisite SCORM:', {
        encryptedId: encryptedId,
        contentType: contentType
    });
    
    // For postrequisite SCORM content, we need to get the actual content details
    // This would require an API call to get the content information
    // For now, we'll show a message and redirect to the course details page
    
    alert('SCORM content launching... Please wait while we prepare the content.');
    
    // TODO: Implement proper postrequisite SCORM launching
    // This would require:
    // 1. Decrypting the postrequisite ID
    // 2. Getting the content details from the database
    // 3. Constructing the proper SCORM launcher URL
    // 4. Opening the SCORM player
    
    console.log('Postrequisite SCORM launch not yet implemented for:', encryptedId);
    
    return false;
}

// Function to refresh module progress
function refreshModuleProgress(moduleId, courseId) {
    const refreshBtn = event.target.closest('.refresh-progress-btn');
    const progressSection = refreshBtn.closest('.module-progress-section');
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    refreshBtn.disabled = true;
    
    // Make AJAX request to get updated progress
    fetch(`/Unlockyourskills/module-progress?module_id=${moduleId}&course_id=${courseId}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.text(); // Get response as text first to debug
    })
    .then(text => {
        console.log('Raw response:', text);
        
        // Try to parse as JSON
        try {
            const data = JSON.parse(text);
            return data;
        } catch (e) {
            console.error('Failed to parse JSON response:', e);
            console.error('Response was:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .then(data => {
        if (data.success) {
            // Update progress display
            updateModuleProgressDisplay(progressSection, data.data);
            
            // Show success animation
            const progressBar = progressSection.querySelector('.progress-bar');
            progressBar.classList.add('animate');
            setTimeout(() => progressBar.classList.remove('animate'), 1000);
            
            console.log('Module progress updated successfully:', data.data);
        } else {
            console.error('Failed to update module progress:', data.error);
            showToast('Failed to refresh progress', 'error');
        }
    })
    .catch(error => {
        console.error('Error refreshing module progress:', error);
        showToast('Error refreshing progress', 'error');
    })
    .finally(() => {
        // Restore button state
        refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
        refreshBtn.disabled = false;
    });
}

// Simple refresh function that reloads the page
function refreshModuleProgressSimple(moduleId, courseId) {
    const refreshBtn = event.target.closest('.refresh-progress-btn');
    
    // Show loading state
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    refreshBtn.disabled = true;
    
    // Show toast notification
    showToast('Refreshing progress...', 'info');
    
    // Reload the page after a short delay to show updated progress
    setTimeout(() => {
        window.location.reload();
    }, 1000);
}

// Function to update module progress display
function updateModuleProgressDisplay(progressSection, progressData) {
    const progressText = progressSection.querySelector('.progress-text');
    const progressBar = progressSection.querySelector('.progress-bar');
    const progressValue = progressSection.querySelector('[aria-valuenow]');
    
    // Update progress text
    const completedItems = progressData.completed_items || 0;
    const totalItems = progressData.total_items || 0;
    const progressPercentage = progressData.progress_percentage || 0;
    
    progressText.innerHTML = `${progressPercentage}% Complete${totalItems > 0 ? ` (${completedItems}/${totalItems})` : ''}`;
    
    // Update progress bar
    progressBar.style.width = `${progressPercentage}%`;
    progressValue.setAttribute('aria-valuenow', progressPercentage);
    
    // Update progress bar color class
    progressBar.className = 'progress-bar';
    if (progressPercentage >= 100) {
        progressBar.classList.add('bg-success');
    } else if (progressPercentage > 0) {
        progressBar.classList.add('bg-primary');
    } else {
        progressBar.classList.add('bg-secondary');
    }
}

// Function to show toast notifications
function showToast(message, type = 'info') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

// Auto-refresh progress every 30 seconds for active modules
setInterval(() => {
    const activeModules = document.querySelectorAll('.module-progress-section');
    activeModules.forEach(moduleSection => {
        const progressBar = moduleSection.querySelector('.progress-bar');
        const progressPercentage = parseInt(moduleSection.querySelector('[aria-valuenow]').getAttribute('aria-valuenow'));
        
        // Only auto-refresh if progress is not 100% (not completed)
        if (progressPercentage < 100) {
            // For auto-refresh, we'll just log that it's time to refresh
            // The user can manually refresh if needed
            console.log('Auto-refresh: Module progress is', progressPercentage + '%, user can manually refresh if needed');
        }
    });
}, 30000); // 30 seconds

// Check if we're returning from SCORM launcher (URL parameter)
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('scorm_return') === 'true') {
        // Check if we've already refreshed to prevent infinite loops
        const currentTime = Date.now();
        const lastRefreshTime = sessionStorage.getItem('scorm_refresh_time');
        const minRefreshInterval = 5000; // Minimum 5 seconds between refreshes
        
        console.log('SCORM return detected:', {
            lastRefreshTime: lastRefreshTime,
            currentTime: currentTime,
            timeSinceLastRefresh: lastRefreshTime ? currentTime - parseInt(lastRefreshTime) : 'never',
            currentUrl: window.location.href
        });
        
        // Check if enough time has passed since last refresh
        if (!lastRefreshTime || (currentTime - parseInt(lastRefreshTime)) > minRefreshInterval) {
            console.log('Returning from SCORM launcher - refreshing to show updated progress');
            
            // Mark that we've refreshed with timestamp
            sessionStorage.setItem('scorm_refresh_time', currentTime.toString());
            
            // Remove the scorm_return parameter to prevent infinite refresh
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.delete('scorm_return');
            window.history.replaceState({}, '', currentUrl);
            
            // Refresh once to show updated progress
            setTimeout(() => {
                console.log('Refreshing page to show updated SCORM progress...');
                window.location.reload();
            }, 1000);
        } else {
            console.log('Refreshed too recently - skipping refresh (minimum interval: 5s)');
            // Clean up the parameter without refreshing
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.delete('scorm_return');
            window.history.replaceState({}, '', currentUrl);
        }
        
        // Additional safety: Clear any old refresh flags that might cause issues
        sessionStorage.removeItem('scorm_refresh');
    } else {
        // Normal page load - clear any SCORM refresh flags
        const oldRefreshTime = sessionStorage.getItem('scorm_refresh_time');
        if (oldRefreshTime) {
            console.log('Clearing old SCORM refresh timestamp:', oldRefreshTime);
            sessionStorage.removeItem('scorm_refresh_time');
        }
    }
    
    // Start document close monitoring (only once)
    if (!window.documentCloseMonitoringStarted) {
        startDocumentCloseMonitoring();
        window.documentCloseMonitoringStarted = true;
    }
});

// Simple document and audio close monitoring using localStorage polling
function startDocumentCloseMonitoring() {
    // Prevent multiple monitoring instances
    if (window.documentCloseMonitoringActive) {
        console.log('Document close monitoring already active, skipping...');
        return;
    }
    
    // Get all document content IDs from the page
    const documentButtons = document.querySelectorAll('.launch-content-btn[data-type="document"]');
    const documentIds = Array.from(documentButtons).map(btn => btn.dataset.contentId);
    
    // Get all audio content IDs from the page
    const audioButtons = document.querySelectorAll('.launch-content-btn[data-type="audio"]');
    const audioIds = Array.from(audioButtons).map(btn => btn.dataset.contentId);
    
    // Get all video content IDs from the page
    const videoButtons = document.querySelectorAll('.launch-content-btn[data-type="video"]');
    const videoIds = Array.from(videoButtons).map(btn => btn.dataset.contentId);
    
    // Get all image content IDs from the page
    const imageButtons = document.querySelectorAll('.launch-content-btn[data-type="image"]');
    const imageIds = Array.from(imageButtons).map(btn => btn.dataset.contentId);
    
    if (documentIds.length === 0 && audioIds.length === 0 && videoIds.length === 0 && imageIds.length === 0) {
        return; // No content to monitor
    }
    
    console.log('Monitoring document close events for:', documentIds);
    console.log('Monitoring audio close events for:', audioIds);
    console.log('Monitoring video close events for:', videoIds);
    console.log('Monitoring image close events for:', imageIds);
    
    // Mark monitoring as active
    window.documentCloseMonitoringActive = true;
    
    // Check every 2 seconds for content close flags
    const checkInterval = setInterval(() => {
        // Prevent multiple refresh attempts
        if (window.pageRefreshInProgress) {
            console.log('Page refresh already in progress, skipping...');
            return;
        }
        
        // Prevent monitoring if page is about to refresh
        if (window.pageRefreshScheduled) {
            console.log('Page refresh already scheduled, stopping monitoring...');
            clearInterval(checkInterval);
            return;
        }
        
        let shouldRefresh = false;
        let closedContentType = '';
        let closedContentId = '';
        
        // Check document close flags
        for (let i = 0; i < documentIds.length && !shouldRefresh; i++) {
            const contentId = documentIds[i];
            if (contentId) {
                const closeFlag = localStorage.getItem('document_closed_' + contentId);
                if (closeFlag) {
                    console.log('Document close detected for:', contentId);
                    
                    // Check if this content was already processed recently
                    const processedKey = `processed_document_${contentId}`;
                    const lastProcessed = sessionStorage.getItem(processedKey);
                    const now = Date.now();
                    
                    if (lastProcessed && (now - parseInt(lastProcessed)) < 5000) {
                        console.log('Document content already processed recently, skipping...');
                        localStorage.removeItem('document_closed_' + contentId);
                        continue;
                    }
                    
                    // Mark as processed
                    sessionStorage.setItem(processedKey, now.toString());
                    
                    localStorage.removeItem('document_closed_' + contentId);
                    shouldRefresh = true;
                    closedContentType = 'Document';
                    closedContentId = contentId;
                    // Break out of the loop once we find a flag
                    break;
                }
            }
        }
        
        // Check audio close flags
        for (let i = 0; i < audioIds.length && !shouldRefresh; i++) {
            const contentId = audioIds[i];
            if (contentId) {
                const closeFlag = localStorage.getItem('audio_closed_' + contentId);
                if (closeFlag) {
                    console.log('Audio close detected for:', contentId, 'at timestamp:', closeFlag);
                    
                    // Check if this content was already processed recently
                    const processedKey = `processed_audio_${contentId}`;
                    const lastProcessed = sessionStorage.getItem(processedKey);
                    const now = Date.now();
                    
                    if (lastProcessed && (now - parseInt(lastProcessed)) < 5000) {
                        console.log('Audio content already processed recently, skipping...');
                        localStorage.removeItem('audio_closed_' + contentId);
                        continue;
                    }
                    
                    // Mark as processed
                    sessionStorage.setItem(processedKey, now.toString());
                    
                    // Remove the flag immediately to prevent duplicate detection
                    localStorage.removeItem('audio_closed_' + contentId);
                    shouldRefresh = true;
                    closedContentType = 'Audio';
                    closedContentId = contentId;
                    // Break out of the loop once we find a flag
                    break;
                }
            }
        }

        // Check video close flags
        for (let i = 0; i < videoIds.length && !shouldRefresh; i++) {
            const contentId = videoIds[i];
            if (contentId) {
                const closeFlag = localStorage.getItem('video_closed_' + contentId);
                if (closeFlag) {
                    console.log('Video close detected for:', contentId, 'at timestamp:', closeFlag);
                    
                    // Check if this content was already processed recently
                    const processedKey = `processed_video_${contentId}`;
                    const lastProcessed = sessionStorage.getItem(processedKey);
                    const now = Date.now();
                    
                    if (lastProcessed && (now - parseInt(lastProcessed)) < 5000) {
                        console.log('Video content already processed recently, skipping...');
                        localStorage.removeItem('video_closed_' + contentId);
                        continue;
                    }
                    
                    // Mark as processed
                    sessionStorage.setItem(processedKey, now.toString());
                    
                    // Remove the flag immediately to prevent duplicate detection
                    localStorage.removeItem('video_closed_' + contentId);
                    shouldRefresh = true;
                    closedContentType = 'Video';
                    closedContentId = contentId;
                    // Break out of the loop once we find a flag
                    break;
                }
            }
        }
        
        // Check image close flags
        for (let i = 0; i < imageIds.length && !shouldRefresh; i++) {
            const contentId = imageIds[i];
            if (contentId) {
                const closeFlag = localStorage.getItem('image_closed_' + contentId);
                if (closeFlag) {
                    console.log('Image close detected for:', contentId, 'at timestamp:', closeFlag);
                    
                    // Check if this content was already processed recently
                    const processedKey = `processed_image_${contentId}`;
                    const lastProcessed = sessionStorage.getItem(processedKey);
                    const now = Date.now();
                    
                    if (lastProcessed && (now - parseInt(lastProcessed)) < 5000) {
                        console.log('Image content already processed recently, skipping...');
                        localStorage.removeItem('image_closed_' + contentId);
                        continue;
                    }
                    
                    // Mark as processed
                    sessionStorage.setItem(processedKey, now.toString());
                    
                    // Remove the flag immediately to prevent duplicate detection
                    localStorage.removeItem('image_closed_' + contentId);
                    shouldRefresh = true;
                    closedContentType = 'Image';
                    closedContentId = contentId;
                    // Break out of the loop once we find a flag
                    break;
                }
            }
        }
        
        if (shouldRefresh) {
            // Mark refresh as in progress to prevent duplicates
            window.pageRefreshInProgress = true;
            
            console.log(`${closedContentType} was closed - refreshing page to show updated progress`);
            console.log('Content type:', closedContentType, 'Content ID:', closedContentId);
            
            // Check if we already have a notification to prevent duplicates
            if (document.querySelector('.alert-info[data-content-refresh]')) {
                console.log('Notification already exists, skipping duplicate...');
                return;
            }
            
            // Show a simple notification
            const notification = document.createElement('div');
            notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
            notification.setAttribute('data-content-refresh', 'true');
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-sync-alt me-2"></i>
                <strong>${closedContentType} Closed</strong><br>
                Refreshing page to show updated progress...
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            console.log('Notification created and added to page');
            
            // Auto-dismiss after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
            
            // Mark refresh as scheduled to prevent further monitoring
            window.pageRefreshScheduled = true;
            
            // For audio content, update progress immediately before refresh
            if (closedContentType === 'Audio') {
                console.log('Audio was closed - updating progress before refresh');
                console.log('Calling refreshAudioProgress for content ID:', closedContentId);
                
                // Update progress and then refresh after it completes
                refreshAudioProgress(closedContentId).then((result) => {
                    console.log('Audio progress update completed with result:', result);
                    // Wait a bit more for the progress update to complete, then refresh
                    setTimeout(() => {
                        console.log('Audio progress updated, refreshing page...');
                        window.location.reload();
                    }, 1000);
                }).catch((error) => {
                    console.error('Audio progress update failed with error:', error);
                    // If progress update fails, still refresh after delay
                    setTimeout(() => {
                        console.log('Audio progress update failed, refreshing page anyway...');
                        window.location.reload();
                    }, 2000);
                });
            } else if (closedContentType === 'Video') {
                // For video content, update progress immediately before refresh
                console.log('Video was closed - updating progress before refresh');
                console.log('Calling refreshVideoProgress for content ID:', closedContentId);
                
                // Update progress and then refresh after it completes
                refreshVideoProgress(closedContentId).then((result) => {
                    console.log('Video progress update completed with result:', result);
                    // Wait a bit more for the progress update to complete, then refresh
                    setTimeout(() => {
                        console.log('Video progress updated, refreshing page...');
                        window.location.reload();
                    }, 1000);
                }).catch((error) => {
                    console.error('Video progress update failed with error:', error);
                    // If progress update fails, still refresh after delay
                    setTimeout(() => {
                        console.log('Video progress update failed, refreshing page anyway...');
                        window.location.reload();
                    }, 2000);
                });
            } else {
                // For other content types, refresh the page after a short delay
                console.log('Non-audio/video content closed, refreshing page after delay...');
                setTimeout(() => {
                    console.log('Refreshing page for non-audio/video content...');
                    window.location.reload();
                }, 2000);
            }
            
            // Stop monitoring since we're refreshing
            clearInterval(checkInterval);
        }
    }, 2000);
    
    // Clean up interval when page is unloaded
    window.addEventListener('beforeunload', () => {
        clearInterval(checkInterval);
        window.documentCloseMonitoringActive = false;
        window.pageRefreshInProgress = false;
    });
    
    // Reset flags when page is refreshed
    window.addEventListener('pageshow', () => {
        if (window.performance.navigation.type === 1) { // Page refresh
            console.log('Page refreshed, resetting monitoring flags');
            window.documentCloseMonitoringActive = false;
            window.pageRefreshInProgress = false;
            window.documentCloseMonitoringStarted = false;
            
            // Clear processed content flags
            const keysToRemove = [];
            for (let i = 0; i < sessionStorage.length; i++) {
                const key = sessionStorage.key(i);
                if (key && (key.startsWith('processed_audio_') || key.startsWith('processed_document_') || key.startsWith('processed_video_') || key.startsWith('processed_image_'))) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(key => {
                sessionStorage.removeItem(key);
                console.log('Cleared processed flag:', key);
            });
            
            // Reset refresh flags
            window.pageRefreshScheduled = false;
        }
    });
}



// Function to refresh audio progress
function refreshAudioProgress(contentId) {
    if (!contentId) return Promise.resolve();
    
    console.log('Refreshing audio progress for:', contentId);
    
    // Find the content card
    const contentCard = document.querySelector(`[data-content-id="${contentId}"]`).closest('.content-item-card');
    if (!contentCard) return Promise.resolve();
    
    // Show loading state
    const progressText = contentCard.querySelector('.content-progress-text');
    const progressFill = contentCard.querySelector('.content-progress-fill');
    
    if (progressText) progressText.textContent = 'Loading...';
    if (progressFill) progressFill.classList.add('updating');
    
    // Fetch updated progress from the server
    const courseId = <?= $GLOBALS['course']['id'] ?? 'null' ?>;
    if (!courseId) return Promise.resolve();
    
    // Return a promise for proper async handling
    return fetch(`/Unlockyourskills/audio-progress/resume-position?course_id=${courseId}&content_id=${contentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Calculate percentage from resume position and duration
                let progressPercentage = 0;
                if (data.duration > 0 && data.resume_position > 0) {
                    progressPercentage = Math.round((data.resume_position / data.duration) * 100);
                }
                
                // Update progress bar
                if (progressText) progressText.textContent = `${progressPercentage}%`;
                if (progressFill) {
                    progressFill.style.width = `${progressPercentage}%`;
                    progressFill.setAttribute('aria-valuenow', progressPercentage);
                }
                
                console.log(`Audio progress refreshed: ${progressPercentage}%`);
                return true; // Success
            }
            return false; // No success
        })
        .catch(error => {
            console.error('Error refreshing audio progress:', error);
            if (progressText) progressText.textContent = 'Error';
            throw error; // Re-throw for catch handling
        })
        .finally(() => {
            if (progressFill) progressFill.classList.remove('updating');
        });
}

// Global function for external access
window.refreshAudioProgress = refreshAudioProgress;

// Function to refresh video progress
function refreshVideoProgress(contentId) {
    if (!contentId) return Promise.resolve();
    
    console.log('Refreshing video progress for:', contentId);
    
    // Find the content card
    const contentCard = document.querySelector(`[data-content-id="${contentId}"]`).closest('.content-item-card');
    if (!contentCard) return Promise.resolve();
    
    // Show loading state
    const progressText = contentCard.querySelector('.content-progress-text');
    const progressFill = contentCard.querySelector('.content-progress-fill');
    
    if (progressText) progressText.textContent = 'Loading...';
    if (progressFill) progressFill.classList.add('updating');
    
    // Fetch updated progress from the server
    const courseId = <?= $GLOBALS['course']['id'] ?? 'null' ?>;
    if (!courseId) return Promise.resolve();
    
    // Return a promise for proper async handling
    return fetch(`/Unlockyourskills/video-progress/resume-position?course_id=${courseId}&content_id=${contentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Calculate percentage from resume position and duration
                let progressPercentage = 0;
                if (data.duration > 0 && data.resume_position > 0) {
                    progressPercentage = Math.round((data.resume_position / data.duration) * 100);
                }
                
                // Update progress bar
                if (progressText) progressText.textContent = `${progressPercentage}%`;
                if (progressFill) {
                    progressFill.style.width = `${progressPercentage}%`;
                    progressFill.setAttribute('aria-valuenow', progressPercentage);
                }
                
                console.log(`Video progress refreshed: ${progressPercentage}%`);
                return true; // Success
            }
            return false; // No success
        })
        .catch(error => {
            console.error('Error refreshing video progress:', error);
            if (progressText) progressText.textContent = 'Error';
            throw error; // Re-throw for catch handling
        })
        .finally(() => {
            if (progressFill) progressFill.classList.remove('updating');
        });
}

// Global function for external access
window.refreshVideoProgress = refreshVideoProgress;
</script>

<?php include 'includes/footer.php'; ?> 