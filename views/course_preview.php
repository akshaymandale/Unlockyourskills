<?php
// Course Preview Modal Content
// This file is included by CourseCreationController@previewCourse

// Helper function to get content type icons
function getContentIcon($type) {
    $icons = [
        'scorm' => 'cube',
        'video' => 'video',
        'audio' => 'volume-up',
        'document' => 'file-alt',
        'image' => 'image',
        'interactive' => 'gamepad',
        'external' => 'external-link-alt',
        'non_scorm' => 'archive'
    ];
    
    return $icons[$type] ?? 'file';
}

// Check if course data is available
if (!isset($course) || !$course) {
    echo '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Course not found.</div>';
    return;
}
?>

<div class="course-preview">
    <!-- Course Header -->
    <div class="course-header mb-4">
        <div class="row">
            <div class="col-md-8">
                <h3 class="course-title text-primary mb-2"><?= htmlspecialchars($course['name'] ?? 'Untitled Course') ?></h3>
                <p class="course-description text-muted mb-3"><?= htmlspecialchars($course['description'] ?? 'No description available') ?></p>
                
                <!-- Course Meta Information -->
                <div class="course-meta d-flex flex-wrap gap-3 mb-3">
                    <?php if (!empty($course['category_name'])): ?>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-tag me-1"></i><?= htmlspecialchars($course['category_name']) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($course['subcategory_name'])): ?>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-tags me-1"></i><?= htmlspecialchars($course['subcategory_name']) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($course['course_type'])): ?>
                        <span class="badge bg-info">
                            <i class="fas fa-graduation-cap me-1"></i><?= htmlspecialchars(ucfirst($course['course_type'])) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($course['difficulty_level'])): ?>
                        <span class="badge bg-warning">
                            <i class="fas fa-signal me-1"></i><?= htmlspecialchars(ucfirst($course['difficulty_level'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-md-4 text-end">
                <div class="course-status">
                    <?php 
                    $status = isset($course['course_status']) ? strtolower($course['course_status']) : 'active';
                    $statusLabel = ($status === 'inactive') ? 'Inactive' : 'Active';
                    $statusClass = ($status === 'inactive') ? 'secondary' : 'success';
                    ?>
                    <span class="badge bg-<?= $statusClass ?> fs-6">
                        <?= htmlspecialchars($statusLabel) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Details Tabs -->
    <ul class="nav nav-tabs" id="coursePreviewTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                <i class="fas fa-info-circle me-1"></i>Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="modules-tab" data-bs-toggle="tab" data-bs-target="#modules" type="button" role="tab">
                <i class="fas fa-list me-1"></i>Modules (<?= count($modules ?? []) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="prerequisites-tab" data-bs-toggle="tab" data-bs-target="#prerequisites" type="button" role="tab">
                <i class="fas fa-link me-1"></i>Prerequisites (<?= count($prerequisites ?? []) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="post-requisites-tab" data-bs-toggle="tab" data-bs-target="#post-requisites" type="button" role="tab">
                <i class="fas fa-arrow-right me-1"></i>Post-Requisites (<?= count($assessments ?? []) + count($feedback ?? []) + count($surveys ?? []) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="coursePreviewTabContent">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-cog me-2"></i>Course Settings</h5>
                    <ul class="list-unstyled">
                        <li><strong>Course Type:</strong> <?= htmlspecialchars(ucfirst($course['course_type'] ?? 'Not specified')) ?></li>
                        <li><strong>Difficulty Level:</strong> <?= htmlspecialchars(ucfirst($course['difficulty_level'] ?? 'Not specified')) ?></li>
                        <li><strong>Self-Paced:</strong> <?= htmlspecialchars(($course['is_self_paced'] ?? 0) ? 'Yes' : 'No') ?></li>
                        <li><strong>Featured:</strong> <?= htmlspecialchars(($course['is_featured'] ?? 0) ? 'Yes' : 'No') ?></li>
                        <li><strong>Duration:</strong> 
                            <?php 
                            $hours = $course['duration_hours'] ?? 0;
                            $minutes = $course['duration_minutes'] ?? 0;
                            if ($hours > 0 || $minutes > 0) {
                                echo ($hours > 0 ? $hours . 'h ' : '') . ($minutes > 0 ? $minutes . 'm' : '');
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h5><i class="fas fa-users me-2"></i>Target Audience</h5>
                    <p><?= htmlspecialchars($course['target_audience'] ?? 'Not specified') ?></p>
                    
                    <?php if (!empty($course['learning_objectives'])): ?>
                        <h5><i class="fas fa-bullseye me-2"></i>Learning Objectives</h5>
                        <ul>
                            <?php 
                            $objectives = $course['learning_objectives'];
                            if (is_string($objectives)) {
                                $objectives = json_decode($objectives, true);
                            }
                            if (!is_array($objectives)) {
                                $objectives = [];
                            }
                            foreach ($objectives as $objective): 
                            ?>
                                <li><?= htmlspecialchars($objective) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modules Tab -->
        <div class="tab-pane fade" id="modules" role="tabpanel">
            <?php if (!empty($modules)): ?>
                <div class="modules-list">
                    <?php foreach ($modules as $index => $module): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-layer-group me-2"></i>
                                    Module <?= $index + 1 ?>: <?= htmlspecialchars($module['title']) ?>
                                </h6>
                            </div>
                            <div class="card-body">
                                <p class="text-muted mb-3"><?= htmlspecialchars($module['description'] ?? 'No description') ?></p>
                                
                                <?php if (!empty($module['content'])): ?>
                                    <h6><i class="fas fa-play me-2"></i>Content (<?= count($module['content']) ?> items)</h6>
                                    <div class="content-list">
                                        <?php foreach ($module['content'] as $content): ?>
                                            <div class="content-item d-flex align-items-center p-2 border rounded mb-2">
                                                <i class="fas fa-<?= getContentIcon($content['content_type'] ?? 'file') ?> me-2 text-primary"></i>
                                                <span class="flex-grow-1"><?= htmlspecialchars($content['title'] ?? 'Untitled Content') ?></span>
                                                <span class="badge bg-light text-dark"><?= htmlspecialchars(ucfirst($content['content_type'] ?? 'unknown')) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted"><i class="fas fa-info-circle me-1"></i>No content added to this module.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-layer-group fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No modules have been created for this course yet.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prerequisites Tab -->
        <div class="tab-pane fade" id="prerequisites" role="tabpanel">
            <?php if (!empty($prerequisites)): ?>
                <div class="prerequisites-list">
                    <?php foreach ($prerequisites as $prerequisite): ?>
                        <div class="prerequisite-item d-flex align-items-center p-3 border rounded mb-2">
                            <i class="fas fa-link me-3 text-primary"></i>
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($prerequisite['prerequisite_course_title'] ?: 'Untitled Prerequisite') ?></strong>
                                <br>
                                <small class="text-muted"><?= htmlspecialchars($prerequisite['prerequisite_description'] ?? 'No description') ?></small>
                            </div>
                            <span class="badge bg-light text-dark"><?= htmlspecialchars(ucfirst($prerequisite['prerequisite_type'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-link fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No prerequisites have been set for this course.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Post-Requisites Tab -->
        <div class="tab-pane fade" id="post-requisites" role="tabpanel">
            <?php 
            $hasPostRequisites = false;
            if (!empty($assessments) || !empty($feedback) || !empty($surveys)): 
                $hasPostRequisites = true;
            ?>
                <div class="post-requisites-list">
                    <?php if (!empty($assessments)): ?>
                        <h6><i class="fas fa-clipboard-check me-2 text-success"></i>Assessments</h6>
                        <?php foreach ($assessments as $assessment): ?>
                            <div class="post-requisite-item d-flex align-items-center p-3 border rounded mb-2">
                                <i class="fas fa-clipboard-check me-3 text-success"></i>
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($assessment['content_title'] ?? $assessment['title'] ?? 'Untitled Assessment') ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($assessment['description'] ?? 'No description') ?></small>
                                </div>
                                <span class="badge bg-success">Assessment</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($feedback)): ?>
                        <h6><i class="fas fa-comment-multiple me-2 text-info"></i>Feedback</h6>
                        <?php foreach ($feedback as $feedbackItem): ?>
                            <div class="post-requisite-item d-flex align-items-center p-3 border rounded mb-2">
                                <i class="fas fa-comment-multiple me-3 text-info"></i>
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($feedbackItem['title'] ?? $feedbackItem['content_title'] ?? 'Untitled Feedback') ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($feedbackItem['description'] ?? 'No description') ?></small>
                                </div>
                                <span class="badge bg-info">Feedback</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($surveys)): ?>
                        <h6><i class="fas fa-clipboard-text-multiple me-2 text-warning"></i>Surveys</h6>
                        <?php foreach ($surveys as $survey): ?>
                            <div class="post-requisite-item d-flex align-items-center p-3 border rounded mb-2">
                                <i class="fas fa-clipboard-text-multiple me-3 text-warning"></i>
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($survey['title'] ?? $survey['content_title'] ?? 'Untitled Survey') ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($survey['description'] ?? 'No description') ?></small>
                                </div>
                                <span class="badge bg-warning">Survey</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-arrow-right fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No post-requisites have been set for this course.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<style>
.course-preview {
    max-height: 70vh;
    overflow-y: auto;
}

.course-meta .badge {
    font-size: 0.85em;
}

.content-item, .prerequisite-item, .post-requisite-item {
    background-color: #f8f9fa;
    transition: background-color 0.2s;
}

.content-item:hover, .prerequisite-item:hover, .post-requisite-item:hover {
    background-color: #e9ecef;
}

.modules-list .card {
    border-left: 4px solid #007bff;
}

.prerequisites-list .prerequisite-item {
    border-left: 4px solid #28a745;
}

.post-requisites-list .post-requisite-item {
    border-left: 4px solid #ffc107;
}

.nav-tabs .nav-link {
    font-size: 0.9em;
    padding: 0.5rem 1rem;
}

.tab-content {
    padding: 1rem 0;
}
</style> 