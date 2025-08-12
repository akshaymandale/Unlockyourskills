<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>
<?php require_once 'core/IdEncryption.php'; ?>

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
<<<<<<< HEAD
                    <?php if (!empty($course['category_name'])): ?>
                        <span class="badge badge-category"><i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($course['category_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($course['subcategory_name'])): ?>
                        <span class="badge badge-subcategory"><i class="fas fa-tags me-1"></i><?= htmlspecialchars($course['subcategory_name']) ?></span>
                    <?php endif; ?>
=======
                    <?php if (!empty($course['difficulty_level'])): ?>
                        <span class="badge badge-difficulty"><i class="fas fa-signal me-1"></i><?= htmlspecialchars(ucfirst($course['difficulty_level'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($course['category_name'])): ?>
                        <span class="badge badge-category"><i class="fas fa-folder-open me-1"></i><?= htmlspecialchars($course['category_name']) ?></span>
                    <?php endif; ?>
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
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
            <div class="prerequisites-grid">
                <?php foreach ($course['prerequisites'] as $pre): ?>
<<<<<<< HEAD
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
                                    <h6 class="prerequisite-title mb-1"><?= htmlspecialchars($pre['title']) ?></h6>
                                    <span class="prerequisite-type"><?= htmlspecialchars(ucfirst($type)) ?></span>
                                </div>
                            </div>
                            <?php if (!empty($pre['prerequisite_type']) && in_array($pre['prerequisite_type'], ['assessment','survey','feedback','assignment'])): ?>
                                <?php 
                                $assessmentId = $pre['prerequisite_id'];
                                $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                                $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                                
                                if ($pre['prerequisite_type'] === 'assessment') {
                                    // Check if user has exceeded maximum attempts
                                    $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                    $attemptCount = count($assessmentAttempts);
                                    $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                    
                                    if ($hasExceededAttempts) {
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
                                    // Non-assessment content types
                                    $encryptedPrereqId = IdEncryption::encrypt($pre['prerequisite_id']);
                                    echo "<a class='prerequisite-action-btn' target='_blank' href='" . UrlHelper::url('my-courses/start') . '?type=' . urlencode($pre['prerequisite_type']) . '&id=' . urlencode($encryptedPrereqId) . "'>";
                                    echo "<i class='fas fa-play me-1'></i>Start";
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
=======
                    <div class="prerequisite-card">
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
                                <h6 class="prerequisite-title mb-1"><?= htmlspecialchars($pre['title']) ?></h6>
                                <span class="prerequisite-type"><?= htmlspecialchars(ucfirst($type)) ?></span>
                            </div>
                        </div>
                        <?php if (!empty($pre['prerequisite_type']) && in_array($pre['prerequisite_type'], ['assessment','survey','feedback','assignment'])): ?>
                            <?php $encryptedPrereqId = IdEncryption::encrypt($pre['prerequisite_id']); ?>
                            <a class="prerequisite-action-btn" target="_blank" href="<?= UrlHelper::url('my-courses/start') . '?type=' . urlencode($pre['prerequisite_type']) . '&id=' . urlencode($encryptedPrereqId) ?>">
                                <i class="fas fa-play me-1"></i>Start
                            </a>
                        <?php else: ?>
                            <span class="prerequisite-status">
                                <i class="fas fa-check-circle me-1"></i>Required
                            </span>
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Course Content -->
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
                                            <h6 class="module-title mb-1"><?= htmlspecialchars($module['title']) ?></h6>
                                        </div>
                                    </div>
                                    <div class="module-progress-section">
                                        <div class="progress-info">
                                            <span class="progress-text"><?= intval($module['module_progress'] ?? 0) ?>% Complete</span>
                                            <div class="progress" role="progressbar" aria-label="Module progress" aria-valuenow="<?= intval($module['module_progress'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100">
                                                <div class="progress-bar" style="width: <?= intval($module['module_progress'] ?? 0) ?>%"></div>
                                            </div>
                                        </div>
<<<<<<< HEAD
                                    </div>
                                    <div class="module-toggle">
                                        <i class="fas fa-chevron-down"></i>
=======
                                        <div class="module-toggle">
                                            <i class="fas fa-chevron-down"></i>
                                        </div>
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
                                    </div>
                                </div>
                            </div>
                            
                            <div id="collapse<?= $idx ?>" class="module-content collapse">
                                <?php if (!empty($module['content'])): ?>
                                    <div class="content-grid">
                                        <?php foreach ($module['content'] as $content): ?>
<<<<<<< HEAD
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
=======
                                            <?php
                                                $type = $content['type'];
                                                $iconMap = [
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
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
<<<<<<< HEAD
                                            <div class="content-item-card<?= ($content['type'] === 'assessment' && !empty($GLOBALS['assessmentAttempts'][$content['content_id']])) ? ' has-attempts' : '' ?>">
=======
                                            <div class="content-item-card">
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
                                                <div class="content-item-left">
                                                    <div class="content-item-header">
                                                        <div class="content-icon-wrapper me-3" style="background: <?= $color ?>;">
                                                            <i class="fas <?= $icon ?> text-white"></i>
                                                        </div>
                                                        <div class="content-details">
                                                            <h6 class="content-title mb-1"><?= htmlspecialchars($content['title']) ?></h6>
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
                                                                        $resolved = resolveContentUrl($launchUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=scorm&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved);
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='content-action-btn content-action-primary'>";
                                                                        echo "<i class='fas fa-rocket me-1'></i>Launch";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No SCORM launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'non_scorm':
                                                                case 'interactive':
                                                                    $launchUrl = $content['non_scorm_launch_path'] ?? ($content['interactive_launch_url'] ?? '');
                                                                    if ($launchUrl) {
                                                                        $resolved = resolveContentUrl($launchUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=iframe&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved);
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='content-action-btn content-action-primary'>";
                                                                        echo "<i class='fas fa-rocket me-1'></i>Launch";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No launch path</span>";
                                                                    }
                                                                    break;
                                                                case 'video':
                                                                    $videoUrl = $content['video_file_path'] ?? '';
                                                                    if ($videoUrl) {
                                                                        $resolved = resolveContentUrl($videoUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=video&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved);
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='content-action-btn content-action-primary'>";
                                                                        echo "<i class='fas fa-play me-1'></i>Play";
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
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=document&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved);
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='content-action-btn content-action-secondary'>";
                                                                        echo "<i class='fas fa-eye me-1'></i>View";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No document file</span>";
                                                                    }
                                                                    break;
                                                                case 'image':
                                                                    $imgUrl = $content['image_file_path'] ?? '';
                                                                    if ($imgUrl) {
                                                                        $resolved = resolveContentUrl($imgUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=image&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved);
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='content-action-btn content-action-secondary'>";
                                                                        echo "<i class='fas fa-eye me-1'></i>View";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No image file</span>";
                                                                    }
                                                                    break;
                                                                case 'external':
                                                                    $extUrl = $content['external_content_url'] ?? '';
                                                                    if ($extUrl) {
                                                                        $resolved = resolveContentUrl($extUrl);
                                                                        $viewer = UrlHelper::url('my-courses/view-content') . '?type=external&title=' . urlencode($content['title']) . '&src=' . urlencode($resolved);
                                                                        echo "<a href='" . htmlspecialchars($viewer) . "' target='_blank' class='content-action-btn content-action-info'>";
                                                                        echo "<i class='fas fa-external-link-alt me-1'></i>Open Link";
                                                                        echo "</a>";
                                                                    } else {
                                                                        echo "<span class='content-error'><i class='fas fa-exclamation-triangle me-1'></i>No external link</span>";
                                                                    }
                                                                    break;
                                                                case 'assessment':
<<<<<<< HEAD
                                                                    // Check if user has exceeded maximum attempts
                                                                    $assessmentId = $content['content_id'];
                                                                    $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                                                                    $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                                                                    $hasExceededAttempts = false;
                                                                    
                                                                    // Get max attempts from assessment details
                                                                    $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                                                    $attemptCount = count($assessmentAttempts);
                                                                    $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                                                    
                                                                    if ($hasExceededAttempts) {
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
=======
                                                                case 'survey':
                                                                case 'feedback':
                                                                case 'assignment':
                                                                    $encryptedId = IdEncryption::encrypt($content['id']);
                                                                    $startUrl = UrlHelper::url('my-courses/start') . '?type=' . urlencode($type) . '&id=' . urlencode($encryptedId);
                                                                    echo "<a href='" . htmlspecialchars($startUrl) . "' target='_blank' class='content-action-btn content-action-success'>";
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
                                                                    echo "<i class='fas fa-play me-1'></i>Start";
                                                                    echo "</a>";
                                                                    break;
                                                                default:
                                                                    echo "<span class='content-action-btn content-action-disabled' disabled>";
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
<<<<<<< HEAD
                                                    
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
                                            
=======
                                                </div>
                                            </div>
                                            
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
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

        <!-- Post-requisites -->
        <?php if (!empty($course['post_requisites'])): ?>
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
<<<<<<< HEAD
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
                                    <h6 class="postrequisite-title mb-1"><?= htmlspecialchars($post['content_title'] ?? $post['title'] ?? 'N/A') ?></h6>
                                    <span class="postrequisite-type"><?= htmlspecialchars(ucfirst($type)) ?></span>
                                </div>
                            </div>
                            <?php 
                            $assessmentId = $post['content_id'];
                            $assessmentAttempts = $GLOBALS['assessmentAttempts'][$assessmentId] ?? [];
                            $assessmentDetails = $GLOBALS['assessmentDetails'][$assessmentId] ?? [];
                            
                            if ($post['content_type'] === 'assessment') {
                                // Check if user has exceeded maximum attempts
                                $maxAttempts = $assessmentDetails['num_attempts'] ?? 3;
                                $attemptCount = count($assessmentAttempts);
                                $hasExceededAttempts = $attemptCount >= $maxAttempts;
                                
                                if ($hasExceededAttempts) {
                                    // Disable start button and show attempts exceeded message
                                    echo "<button class='postrequisite-action-btn postrequisite-action-disabled' disabled title='Maximum attempts reached'>";
                                    echo "<i class='fas fa-ban me-1'></i>Attempts Exceeded";
                                    echo "</button>";
                                } else {
                                    // Show start button with attempt count
                                    $encryptedPostreqId = IdEncryption::encrypt($post['content_id']);
                                    echo "<a class='postrequisite-action-btn' target='_blank' href='" . UrlHelper::url('my-courses/start') . '?type=' . urlencode($post['content_type']) . '&id=' . urlencode($encryptedPostreqId) . "'>";
                                    echo "<i class='fas fa-rocket me-1'></i>Launch";
                                    echo "</a>";
                                }
                            } else {
                                // Non-assessment content types
                                $encryptedPostreqId = IdEncryption::encrypt($post['content_id']);
                                echo "<a class='postrequisite-action-btn' target='_blank' href='" . UrlHelper::url('my-courses/start') . '?type=' . urlencode($post['content_type']) . '&id=' . urlencode($encryptedPostreqId) . "'>";
                                echo "<i class='fas fa-rocket me-1'></i>Launch";
                                echo "</a>";
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
=======
                    <div class="postrequisite-card">
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
                                <h6 class="postrequisite-title mb-1"><?= htmlspecialchars($post['content_title'] ?? $post['title'] ?? 'N/A') ?></h6>
                                <span class="postrequisite-type"><?= htmlspecialchars(ucfirst($type)) ?></span>
                            </div>
                        </div>
                        <?php $encryptedPostreqId = IdEncryption::encrypt($post['content_id']); ?>
                        <a class="postrequisite-action-btn" target="_blank" href="<?= UrlHelper::url('my-courses/start') . '?type=' . urlencode($post['content_type']) . '&id=' . urlencode($encryptedPostreqId) ?>">
                            <i class="fas fa-rocket me-1"></i>Launch
                        </a>
>>>>>>> af75b4fbe579979a6b31bc9dbf713ea5cddebe83
                    </div>
                <?php endforeach; ?>
            </div>
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

<?php include 'includes/footer.php'; ?> 