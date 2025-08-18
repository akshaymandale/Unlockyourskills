<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>
<?php require_once 'core/IdEncryption.php'; ?>

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
        
        // Helper to check if all modules are completed
        if (!function_exists('areModulesCompleted')) {
            function areModulesCompleted($modules) {
                if (empty($modules)) {
                    return true; // No modules means all are completed
                }
                
                foreach ($modules as $module) {
                    // Check if module has content and if all content is completed
                    if (!empty($module['content'])) {
                        foreach ($module['content'] as $content) {
                            // For now, we'll check if the module has any progress
                            // This can be enhanced with actual completion tracking per content type
                            $progress = intval($module['module_progress'] ?? 0);
                            if ($progress < 100) {
                                return false;
                            }
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
                            'has_progress' => false
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
                        SELECT lesson_status, lesson_location, score_raw, score_max, session_time, updated_at 
                        FROM scorm_progress 
                        WHERE course_id = ? AND content_id = ? AND user_id = ?
                    ");
                    $stmt->execute([$courseId, $contentId, $userId]);
                    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($progress) {
                        return [
                            'status' => $progress['lesson_status'] ?? 'incomplete',
                            'location' => $progress['lesson_location'] ?? '',
                            'score' => $progress['score_raw'] ?? 0,
                            'max_score' => $progress['score_max'] ?? 100,
                            'session_time' => $progress['session_time'] ?? '',
                            'last_updated' => $progress['updated_at'] ?? '',
                            'has_progress' => true
                        ];
                    } else {
                        return [
                            'status' => 'not_started',
                            'location' => '',
                            'score' => 0,
                            'max_score' => 100,
                            'session_time' => '',
                            'last_updated' => '',
                            'has_progress' => false
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
                        'has_progress' => false
                    ];
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
                                        echo "<span class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>{$statusText}</span>";
                                        if ($hasPassed) {
                                            echo "<small class='text-muted ms-2'>(Score: {$assessmentResults['score']}/{$assessmentResults['max_score']} - {$assessmentResults['percentage']}%)</small>";
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
                $progress = intval($module['module_progress'] ?? 0);
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
                <div class="course-modules-container">
                    <?php foreach ($course['modules'] as $idx => $module): ?>
                        <div class="module-card mb-3">
                            <div class="module-header" data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>" aria-expanded="false" aria-controls="collapse<?= $idx ?>">
                                <div class="module-header-content">
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
                                    <div class="module-progress-section">
                                        <div class="progress-info">
                                            <span class="progress-text"><?= intval($module['module_progress'] ?? 0) ?>% Complete</span>
                                            <div class="progress" role="progressbar" aria-label="Module progress" aria-valuenow="<?= intval($module['module_progress'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar" style="width: <?= intval($module['module_progress'] ?? 0) ?>%"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="module-toggle">
                                        <i class="fas fa-chevron-down"></i>
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
                                                            <span class="content-progress-text"><?= intval($content['progress'] ?? 0) ?>%</span>
                                                            <div class="content-progress-bar" role="progressbar" aria-label="Content progress" aria-valuenow="<?= intval($content['progress'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100">
                                                                <div class="content-progress-fill" style="width: <?= intval($content['progress'] ?? 0) ?>%"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="content-item-right">
                                                    <div class="content-controls">
                                                        <div class="content-actions">
                                                        <?php
                                                            switch ($content['type']) {
                                                                case 'scorm':
                                                                    $launchUrl = $content['scorm_launch_path'] ?? '';
                                                                    if ($launchUrl) {
                                                                        // Get SCORM progress status
                                                                        $scormProgress = getScormProgressStatus($GLOBALS['course']['id'], $content['id'], $_SESSION['user']['id']);
                                                                        
                                                                        // Show progress status
                                                                        echo "<div class='scorm-progress-status mb-2'>";
                                                                        if ($scormProgress['has_progress']) {
                                                                            $statusClass = $scormProgress['status'] === 'completed' ? 'text-success' : 'text-warning';
                                                                            $statusIcon = $scormProgress['status'] === 'completed' ? 'fa-check-circle' : 'fa-clock';
                                                                            echo "<div class='{$statusClass}'>";
                                                                            echo "<i class='fas {$statusIcon} me-1'></i>";
                                                                            echo ucfirst($scormProgress['status']);
                                                                            if ($scormProgress['score'] > 0) {
                                                                                echo " - Score: {$scormProgress['score']}/{$scormProgress['max_score']}";
                                                                            }
                                                                            if ($scormProgress['last_updated']) {
                                                                                echo " <small class='text-muted'>(Last: " . date('M j, Y', strtotime($scormProgress['last_updated'])) . ")</small>";
                                                                            }
                                                                            echo "</div>";
                                                                        } else {
                                                                            echo "<div class='text-muted'><i class='fas fa-circle me-1'></i>Not started</div>";
                                                                        }
                                                                        echo "</div>";
                                                                        
                                                                        // Show launch button only if not completed
                                                                        if ($scormProgress['status'] !== 'completed') {
                                                                            $scormLauncherUrl = UrlHelper::url('scorm/launch') . '?course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'] . '&title=' . urlencode($content['title']);
                                                                            echo "<a href='" . htmlspecialchars($scormLauncherUrl) . "' target='_blank' class='postrequisite-action-btn btn-secondary launch-content-btn' 
                                                                                 data-module-id='" . $module['id'] . "' 
                                                                                 data-content-id='" . $content['id'] . "' 
                                                                                 data-type='scorm'
                                                                                 onclick='launchNewSCORMPlayer(event, " . $GLOBALS['course']['id'] . ", " . $module['id'] . ", " . $content['id'] . ", \"" . addslashes($content['title']) . "\")'>";
                                                                            echo "<i class='fas fa-cube me-1'></i>Launch SCORM";
                                                                            echo "</a>";
                                                                        } else {
                                                                            echo "<div class='scorm-completed-badge'>";
                                                                            echo "<span class='badge bg-success'><i class='fas fa-check-circle me-1'></i>SCORM Completed</span>";
                                                                            echo "</div>";
                                                                        }
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No SCORM launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'non_scorm':
                                                                case 'interactive':
                                                                    $launchUrl = $content['non_scorm_launch_path'] ?? ($content['interactive_launch_url'] ?? '');
                                                                    if ($launchUrl) {
                                                                        $resolved = resolveContentUrl($launchUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=iframe&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-primary launch-content-btn' 
                                                                             data-module-id='" . $module['id'] . "' 
                                                                             data-content-id='" . $content['id'] . "' 
                                                                             data-type='" . $type . "'>";
                                                                        echo "<i class='fas fa-wand-magic-sparkles me-1'></i>Launch Interactive";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'video':
                                                                    $videoUrl = $content['video_file_path'] ?? '';
                                                                    if ($videoUrl) {
                                                                        $resolved = resolveContentUrl($videoUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=video&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-danger launch-content-btn' 
                                                                             data-module-id='" . $module['id'] . "' 
                                                                             data-content-id='" . $content['id'] . "' 
                                                                             data-type='video'>";
                                                                        echo "<i class='fas fa-play me-1'></i>Watch Video";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No video file</span>";
                                                                    }
                                                                    break;
                                                                case 'audio':
                                                                    $audioUrl = $content['audio_file_path'] ?? '';
                                                                    if ($audioUrl) {
                                                                        $resolved = resolveContentUrl($audioUrl);
                                                                        echo "<div class='audio-player-wrapper'>";
                                                                        echo "<audio controls preload='none'><source src='" . htmlspecialchars($resolved) . "' type='audio/mpeg'></audio>";
                                                                        echo "</div>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No audio file</span>";
                                                                    }
                                                                    break;
                                                                case 'document':
                                                                    $docUrl = $content['document_file_path'] ?? '';
                                                                    if ($docUrl) {
                                                                        $resolved = resolveContentUrl($docUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=document&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-secondary launch-content-btn' 
                                                                             data-module-id='" . $module['id'] . "' 
                                                                             data-content-id='" . $content['id'] . "' 
                                                                             data-type='document'>";
                                                                        echo "<i class='fas fa-file-lines me-1'></i>View Document";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No document file</span>";
                                                                    }
                                                                    break;
                                                                case 'image':
                                                                    $imgUrl = $content['image_file_path'] ?? '';
                                                                    if ($imgUrl) {
                                                                        $resolved = resolveContentUrl($imgUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=image&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-info launch-content-btn' 
                                                                             data-module-id='" . $module['id'] . "' 
                                                                             data-content-id='" . $content['id'] . "' 
                                                                             data-type='image'>";
                                                                        echo "<i class='fas fa-image me-1'></i>View Image";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No image file</span>";
                                                                    }
                                                                    break;
                                                                case 'external':
                                                                    $extUrl = $content['external_content_url'] ?? '';
                                                                    if ($extUrl) {
                                                                        $resolved = resolveContentUrl($extUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=external&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved) . '&course_id=' . $GLOBALS['course']['id'] . '&module_id=' . $module['id'] . '&content_id=' . $content['id'];
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='postrequisite-action-btn btn-info launch-content-btn' 
                                                                             data-module-id='" . $module['id'] . "' 
                                                                             data-content-id='" . $content['id'] . "' 
                                                                             data-type='external'>";
                                                                        echo "<i class='fas fa-external-link-alt me-1'></i>Open Link";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No external link</span>";
                                                                    }
                                                                    break;
                                                                case 'assessment':
                                                                    // Get assessment data and results
                                                                    $assessmentId = $content['content_id'];
                                                                    $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                                                                    $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                                                                    $assessmentResults = $GLOBALS['assessmentResults'][$assessmentId] ?? null;
                                                                    $hasPassed = $assessmentResults && $assessmentResults['passed'];
                                                                    $hasExceededAttempts = false;
                                                                    
                                                                    // Get max attempts from assessment details
                                                                    $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                                                    $attemptCount = count($assessmentAttempts);
                                                                    $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                                                    
                                                                    // Show pass/fail status
                                                                    if ($assessmentResults) {
                                                                        $statusClass = $hasPassed ? 'text-success' : 'text-danger';
                                                                        $statusIcon = $hasPassed ? 'fa-check-circle' : 'fa-times-circle';
                                                                        $statusText = $hasPassed ? 'Passed' : 'Failed';
                                                                        echo "<div class='assessment-status mb-2'>";
                                                                        echo "<span class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>{$statusText}</span>";
                                                                        if ($hasPassed) {
                                                                            echo "<small class='text-muted ms-2'>(Score: {$assessmentResults['score']}/{$assessmentResults['max_score']} - {$assessmentResults['percentage']}%)</small>";
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
                                                                        $encryptedId = IdEncryption::encrypt($content['content_id']);
                                                                        $startUrl = UrlHelper::url('my-courses/start') . '?type=' . urlencode($type) . '&id=' . urlencode($encryptedId);
                                                                        echo "<a href='" . htmlspecialchars($startUrl) . "' target='_blank' class='prerequisite-action-btn'>";
                                                                        echo "<i class='fas fa-play me-1'></i>Start";
                                                                        echo "</a>";
                                                                    }
                                                                    break;
                                                                case 'survey':
                                                                case 'feedback':
                                                                case 'assignment':
                                                                    $encryptedId = IdEncryption::encrypt($content['content_id']);
                                                                    $startUrl = UrlHelper::url('my-courses/start') . '?type=' . urlencode($type) . '&id=' . urlencode($encryptedId);
                                                                    echo "<a href='" . htmlspecialchars($startUrl) . "' target='_blank' class='prerequisite-action-btn'>";
                                                                    echo "<i class='fas fa-play me-1'></i>Start";
                                                                    echo "</a>";
                                                                    break;
                                                                default:
                                                                    echo "<span class='postrequisite-action-btn btn-secondary' style='opacity: 0.6; cursor: not-allowed;' disabled>";
                                                                    echo "<i class='fas fa-ban me-1'></i>Not available";
                                                                    echo "</span>";
                                                                    break;
                                                            }
                                                        ?>
                                                        </div>
                                                        <div class="content-toggle" data-bs-toggle="collapse" data-bs-target="#contentDetails<?= $idx ?>_<?= $content['id'] ?? $loop->index ?>" aria-expanded="false">
                                                            <i class="fas fa-chevron-down"></i>
                                                        </div>
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
        ?>
        
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
                                        title="<?= htmlspecialchars($post['content_title'] ?? $post['title'] ?? 'N/A') ?>">
                                        <?= htmlspecialchars($post['content_title'] ?? $post['title'] ?? 'N/A') ?>
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
                                    echo "<span class='{$statusClass}'><i class='fas {$statusIcon} me-1'></i>{$statusText}</span>";
                                    if ($hasPassed) {
                                        echo "<small class='text-muted ms-2'>(Score: {$assessmentResults['score']}/{$assessmentResults['max_score']} - {$assessmentResults['percentage']}%)</small>";
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

// Add CSS for SCORM progress status
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
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
    `;
    document.head.appendChild(style);
    
    // Check if we're returning from SCORM launcher (URL parameter)
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
});
</script>

<?php include 'includes/footer.php'; ?> 