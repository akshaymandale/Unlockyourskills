<?php
// Full-featured course creation form for modal (no header/footer)
?>
<form id="courseCreationForm" method="POST" enctype="multipart/form-data" <?= isset($isEditMode) && $isEditMode ? 'data-edit-mode="true"' : '' ?>>
    <!-- Hidden fields for existing data in edit mode -->
    <?php if (isset($isEditMode) && $isEditMode): ?>
        <input type="hidden" id="existing_modules" value="<?= htmlspecialchars(json_encode($modules ?? [])) ?>">
        <input type="hidden" id="existing_prerequisites" value="<?= htmlspecialchars(json_encode($prerequisites ?? [])) ?>">
        <input type="hidden" id="existing_post_requisites" value="<?= htmlspecialchars(json_encode($postRequisites ?? [])) ?>">
        <?php error_log('[DEBUG] $subcategories in modal: ' . print_r($subcategories, true)); ?>
        <?php error_log('[DEBUG] $editCourseData[\'subcategory_id\'] in modal: ' . print_r($editCourseData['subcategory_id'] ?? null, true)); ?>
    <?php endif; ?>
    <?php if (isset($isEditMode) && $isEditMode && isset($editCourseData['category_id'])): ?>
        <input type="hidden" id="edit_category_id" value="<?= htmlspecialchars($editCourseData['category_id']) ?>">
    <?php endif; ?>
    <?php if (isset($isEditMode) && $isEditMode && isset($editCourseData['subcategory_id'])): ?>
        <input type="hidden" id="edit_subcategory_id" value="<?= htmlspecialchars($editCourseData['subcategory_id']) ?>">
    <?php endif; ?>
    <?php if (isset($isEditMode) && $isEditMode && isset($editCourseData['id'])): ?>
        <input type="hidden" id="course_id" value="<?= htmlspecialchars($editCourseData['id']) ?>">
    <?php endif; ?>
    <input type="hidden" name="modules" id="modulesInput">
    <input type="hidden" name="prerequisites" id="prerequisitesInput">
    <input type="hidden" name="post_requisites" id="postRequisitesInput">
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs nav-bordered" id="courseCreationTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab">
                <i class="mdi mdi-information-outline me-1"></i>
                Basic Info
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="modules-tab" data-bs-toggle="tab" data-bs-target="#modules" type="button" role="tab">
                <i class="mdi mdi-view-module me-1"></i>
                Modules & Sections
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="prerequisites-tab" data-bs-toggle="tab" data-bs-target="#prerequisites" type="button" role="tab">
                <i class="mdi mdi-arrow-up-bold-circle me-1"></i>
                Prerequisites
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="post-requisite-tab" data-bs-toggle="tab" data-bs-target="#post-requisite" type="button" role="tab">
                <i class="mdi mdi-arrow-down-bold-circle me-1"></i>
                Post Requisite
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="extra-details-tab" data-bs-toggle="tab" data-bs-target="#extra-details" type="button" role="tab">
                <i class="mdi mdi-cog me-1"></i>
                Extra Details
            </button>
        </li>
    </ul>

    <div class="tab-content" id="courseCreationTabContent">
        <!-- Basic Information Tab -->
        <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
            <div class="mt-3">
                <!-- Course Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="mdi mdi-information-outline me-2"></i>Course Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="course_name" class="form-label"><?= Localization::translate('course_creation.course_title') ?> <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="course_name" name="name" placeholder="<?= Localization::translate('course_creation.course_title_placeholder') ?>" value="<?= isset($isEditMode) && $isEditMode && isset($editCourseData['name']) ? htmlspecialchars($editCourseData['name']) : '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="short_description" class="form-label"><?= Localization::translate('course_creation.short_description') ?></label>
                                    <textarea class="form-control" id="short_description" name="short_description" rows="2" placeholder="<?= Localization::translate('course_creation.short_description_placeholder') ?>"><?php if(isset($isEditMode) && $isEditMode && isset($editCourseData['short_description'])) echo htmlspecialchars($editCourseData['short_description']); ?></textarea>
                                    <div class="form-text"><?= Localization::translate('course_creation.short_description_placeholder') ?></div>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label"><?= Localization::translate('course_creation.description') ?></label>
                                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="<?= Localization::translate('course_creation.description_placeholder') ?>"><?php if(isset($isEditMode) && $isEditMode && isset($editCourseData['description'])) echo htmlspecialchars($editCourseData['description']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label"><?= Localization::translate('course_creation.category') ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value=""><?= Localization::translate('course_creation.select_category') ?></option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['category_id']) && $editCourseData['category_id'] == $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="subcategory_id" class="form-label"><?= Localization::translate('course_creation.subcategory') ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="subcategory_id" name="subcategory_id">
                                        <option value=""><?= Localization::translate('course_creation.select_subcategory') ?></option>
                                        <?php if(isset($isEditMode) && $isEditMode && isset($subcategories) && is_array($subcategories)): ?>
                                            <?php foreach ($subcategories as $subcategory): ?>
                                                <option value="<?= $subcategory['id'] ?>" <?= (isset($editCourseData['subcategory_id']) && $editCourseData['subcategory_id'] == $subcategory['id']) ? 'selected' : '' ?>><?= htmlspecialchars($subcategory['name']) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="course_type" class="form-label"><?= Localization::translate('course_creation.course_type') ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="course_type" name="course_type">
                                        <option value=""><?= Localization::translate('course_creation.select_category') ?></option>
                                        <?php 
                                        // Map database values back to frontend values for display
                                        $courseTypeDisplayMap = [
                                            'self_paced' => 'e-learning',
                                            'instructor_led' => 'classroom', 
                                            'hybrid' => 'blended'
                                        ];
                                        $displayCourseType = isset($isEditMode) && $isEditMode && isset($editCourseData['course_type']) ? 
                                            ($courseTypeDisplayMap[$editCourseData['course_type']] ?? 'e-learning') : '';
                                        ?>
                                        <option value="e-learning" <?= ($displayCourseType == 'e-learning') ? 'selected' : '' ?>><?= Localization::translate('course_creation.e_learning') ?></option>
                                        <option value="classroom" <?= ($displayCourseType == 'classroom') ? 'selected' : '' ?>><?= Localization::translate('course_creation.classroom') ?></option>
                                        <option value="blended" <?= ($displayCourseType == 'blended') ? 'selected' : '' ?>><?= Localization::translate('course_creation.blended') ?></option>
                                        <option value="assessment" <?= ($displayCourseType == 'assessment') ? 'selected' : '' ?>><?= Localization::translate('course_creation.assessment') ?></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="difficulty_level" class="form-label"><?= Localization::translate('course_creation.difficulty_level') ?> <span class="text-danger">*</span></label>
                                    <select class="form-select" id="difficulty_level" name="difficulty_level">
                                        <option value=""><?= Localization::translate('course_creation.select_category') ?></option>
                                        <option value="beginner" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['difficulty_level']) && $editCourseData['difficulty_level'] == 'beginner') ? 'selected' : '' ?>><?= Localization::translate('course_creation.beginner') ?></option>
                                        <option value="intermediate" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['difficulty_level']) && $editCourseData['difficulty_level'] == 'intermediate') ? 'selected' : '' ?>><?= Localization::translate('course_creation.intermediate') ?></option>
                                        <option value="advanced" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['difficulty_level']) && $editCourseData['difficulty_level'] == 'advanced') ? 'selected' : '' ?>><?= Localization::translate('course_creation.advanced') ?></option>
                                        <option value="expert" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['difficulty_level']) && $editCourseData['difficulty_level'] == 'expert') ? 'selected' : '' ?>><?= Localization::translate('course_creation.expert') ?></option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="course_status" class="form-label">Course Status</label>
                                    <select class="form-select" id="course_status" name="course_status">
                                        <option value="active" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['course_status']) && $editCourseData['course_status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['course_status']) && $editCourseData['course_status'] == 'inactive') ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="mdi mdi-details me-2"></i>Course Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="target_audience" class="form-label"><?= Localization::translate('course_creation.target_audience') ?></label>
                                    <textarea class="form-control" id="target_audience" name="target_audience" rows="3" placeholder="<?= Localization::translate('course_creation.target_audience_placeholder') ?>"><?php if(isset($isEditMode) && $isEditMode && isset($editCourseData['target_audience'])) echo htmlspecialchars($editCourseData['target_audience']); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?= Localization::translate('course_creation.learning_objectives') ?></label>
                                    <div class="tag-input-container form-control">
                                        <span id="learningObjectivesDisplay"></span>
                                        <input type="text" id="learningObjectivesInput" 
                                            placeholder="Type and press Enter to add learning objective..." 
                                            class="form-control border-0">
                                    </div>
                                    <input type="hidden" name="learning_objectives" id="learningObjectivesList" 
                                        value="<?= isset($isEditMode) && $isEditMode && isset($editCourseData['learning_objectives']) ? htmlspecialchars($editCourseData['learning_objectives']) : '' ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?= Localization::translate('course_creation.tags') ?></label>
                                    <div class="tag-input-container form-control">
                                        <span id="tagsDisplay"></span>
                                        <input type="text" id="tagsInput" 
                                            placeholder="Type and press Enter to add tags..." 
                                            class="form-control border-0">
                                    </div>
                                    <input type="hidden" name="tags" id="tagsList" 
                                        value="<?= isset($isEditMode) && $isEditMode && isset($editCourseData['tags']) ? htmlspecialchars($editCourseData['tags']) : '' ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="duration_hours" class="form-label"><?= Localization::translate('course_creation.duration') ?> (<?= Localization::translate('course_creation.hours') ?>)</label>
                                            <input type="number" class="form-control" id="duration_hours" name="duration_hours" min="0" max="999.99" step="0.01" value="<?= isset($isEditMode) && $isEditMode && isset($editCourseData['duration_hours']) ? htmlspecialchars($editCourseData['duration_hours']) : '0' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="duration_minutes" class="form-label"><?= Localization::translate('course_creation.duration') ?> (<?= Localization::translate('course_creation.minutes') ?>)</label>
                                            <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" min="0" max="59" value="<?= isset($isEditMode) && $isEditMode && isset($editCourseData['duration_minutes']) ? htmlspecialchars($editCourseData['duration_minutes']) : '0' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?= Localization::translate('course_creation.course_settings') ?></label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_self_paced" name="is_self_paced" value="1" <?= (isset($isEditMode) && $isEditMode && !empty($editCourseData['is_self_paced'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_self_paced">
                                            <?= Localization::translate('course_creation.self_paced') ?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" <?= (isset($isEditMode) && $isEditMode && !empty($editCourseData['is_featured'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_featured">
                                            <?= Localization::translate('course_creation.featured_course') ?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" <?= (isset($isEditMode) && $isEditMode && !empty($editCourseData['is_published'])) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_published">
                                            <?= Localization::translate('course_creation.published') ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Media -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="mdi mdi-image me-2"></i>Course Media</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="thumbnail" class="form-label"><?= Localization::translate('course_creation.thumbnail_image') ?></label>
                                    <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                    <div id="thumbnailPreviewContainer"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="banner" class="form-label"><?= Localization::translate('course_creation.banner_image') ?></label>
                                    <input type="file" class="form-control" id="banner" name="banner" accept="image/*">
                                    <div id="bannerPreviewContainer"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modules Tab -->
        <div class="tab-pane fade" id="modules" role="tabpanel">
            <div class="mt-3">
                <!-- Section 1: Module Structure -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="mdi mdi-format-list-bulleted me-2"></i>Module Structure</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="module_structure" id="sequential" value="sequential" checked>
                                    <label class="form-check-label" for="sequential">
                                        <i class="mdi mdi-arrow-right-bold me-1"></i>Sequential
                                    </label>
                                    <small class="form-text text-muted d-block">Modules must be completed in order</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="module_structure" id="non_sequential" value="non_sequential">
                                    <label class="form-check-label" for="non_sequential">
                                        <i class="mdi mdi-arrow-all me-1"></i>Non-Sequential
                                    </label>
                                    <small class="form-text text-muted d-block">Modules can be completed in any order</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Modules -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="mdi mdi-view-module me-2"></i>Modules</h6>
                        <button type="button" class="btn btn-primary btn-sm" id="addModuleBtn">
                            <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_module') ?>
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="modulesContainer">
                            <!-- Modules will be added here dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Prerequisites Tab -->
        <div class="tab-pane fade" id="prerequisites" role="tabpanel">
            <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><?= Localization::translate('course_creation.prerequisites') ?></h5>
                    <button type="button" class="btn btn-primary" id="add_prerequisite">
                        <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_prerequisite') ?>
                    </button>
                </div>
                <div id="prerequisites_container">
                    <!-- Prerequisites will be added here dynamically -->
                </div>
            </div>
        </div>
        <!-- Post Requisite Tab -->
        <div class="tab-pane fade" id="post-requisite" role="tabpanel">
            <div class="mt-3">
                <!-- Container for displaying selected post-requisites -->
                <div class="mb-3">
                    <label class="form-label">Selected Post Requisites</label>
                    <div id="postRequisitesContainer" class="border rounded p-3 bg-light">
                        <span class="text-muted">No post-requisites added yet</span>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">Assessment</label>
                        <button type="button" class="btn btn-outline-primary w-100" id="selectPostAssessmentBtn">
                            <i class="mdi mdi-clipboard-check me-1"></i> Select Assessment
                        </button>
                        <div id="selectedPostAssessment" class="mt-2"></div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">Feedback</label>
                        <button type="button" class="btn btn-outline-primary w-100" id="selectPostFeedbackBtn">
                            <i class="mdi mdi-comment-multiple me-1"></i> Select Feedback
                        </button>
                        <div id="selectedPostFeedback" class="mt-2"></div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">Survey</label>
                        <button type="button" class="btn btn-outline-primary w-100" id="selectPostSurveyBtn">
                            <i class="mdi mdi-clipboard-text-multiple me-1"></i> Select Survey
                        </button>
                        <div id="selectedPostSurvey" class="mt-2"></div>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <label class="form-label">Assignment</label>
                        <button type="button" class="btn btn-outline-primary w-100" id="selectPostAssignmentBtn">
                            <i class="mdi mdi-file-document-edit me-1"></i> Select Assignment
                        </button>
                        <div id="selectedPostAssignment" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Extra Details Tab -->
        <div class="tab-pane fade" id="extra-details" role="tabpanel">
            <div class="mt-3">
                <!-- Course Pricing & Points -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="mdi mdi-currency-usd me-2"></i>Course Pricing & Points</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="course_points" class="form-label">Course Points</label>
                                    <input type="number" class="form-control" id="course_points" name="course_points" min="0" value="0" placeholder="Enter course points">
                                    <div class="form-text">Points earned upon course completion</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="course_cost" class="form-label">Course Cost</label>
                                    <div class="input-group">
                                        <select class="form-select" id="currency" name="currency" style="max-width: 120px;">
                                            <option value="">Select Currency</option>
                                            <?php foreach ($currencies as $currency): ?>
                                                <option value="<?= $currency['currency'] ?>" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['currency']) && $editCourseData['currency'] == $currency['currency']) ? 'selected' : '' ?>><?= $currency['currency'] ?> (<?= $currency['currency_symbol'] ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" class="form-control" id="course_cost" name="course_cost" min="0" step="0.01" value="<?= isset($isEditMode) && $isEditMode && isset($editCourseData['course_cost']) ? htmlspecialchars($editCourseData['course_cost']) : '0' ?>" placeholder="0.00">
                                    </div>
                                    <div class="form-text">Set to 0 for free courses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="mdi mdi-cog me-2"></i>Course Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Reassign Course</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="reassign_course" id="reassign_no" value="no" checked>
                                        <label class="form-check-label" for="reassign_no">No</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="reassign_course" id="reassign_yes" value="yes">
                                        <label class="form-check-label" for="reassign_yes">Yes</label>
                                    </div>
                                    <div id="reassign_days_container" class="mt-2" style="display: none;">
                                        <label for="reassign_days" class="form-label">After how many days?</label>
                                        <input type="number" class="form-control" id="reassign_days" name="reassign_days" min="1" placeholder="Enter number of days">
                                        <div class="form-text">Course will be reassigned after this period</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Show in Search Courses</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="show_in_search" id="show_in_search_no" value="no" checked>
                                        <label class="form-check-label" for="show_in_search_no">No</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="show_in_search" id="show_in_search_yes" value="yes">
                                        <label class="form-check-label" for="show_in_search_yes">Yes</label>
                                    </div>
                                    <div class="form-text">Control course visibility in search results</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificate Options -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="mdi mdi-certificate me-2"></i>Certificate Options</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="certificate_option" id="issue_certificate_after_rating" value="after_rating">
                                    <label class="form-check-label" for="issue_certificate_after_rating">
                                        Issue Certificate after rating
                                    </label>
                                    <div class="form-text">Certificate is issued only after course rating</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="certificate_option" id="issue_certificate_on_completion" value="on_completion">
                                    <label class="form-check-label" for="issue_certificate_on_completion">
                                        Issue Certificate on course completion before rating
                                    </label>
                                    <div class="form-text">Certificate is issued immediately upon completion</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="mdi mdi-close me-1"></i>Cancel
        </button>
        <button type="submit" class="btn btn-primary" id="create_course">
            <?php if (isset($isEditMode) && $isEditMode): ?>
                <i class="mdi mdi-content-save me-1"></i>Update Course
            <?php else: ?>
                <i class="mdi mdi-plus-circle me-1"></i>Create Course
            <?php endif; ?>
        </button>
    </div>
</form>

<!-- Templates for dynamic elements (copied from course_creation.php) -->
<template id="module_template">
    <div class="module-item card mb-3" data-module-index="">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0 module-title"><?= Localization::translate('course_creation.module_title') ?></h6>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary move-up">
                    <i class="mdi mdi-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary move-down">
                    <i class="mdi mdi-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger remove-module">
                    <i class="mdi mdi-delete"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><?= Localization::translate('course_creation.module_title') ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control module-title-input" name="modules[][title]" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label"><?= Localization::translate('course_creation.sort_order') ?></label>
                        <input type="number" class="form-control" name="modules[][sort_order]" min="1" value="1">
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label"><?= Localization::translate('course_creation.module_description') ?></label>
                <textarea class="form-control" name="modules[][description]" rows="2"></textarea>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label class="form-label"><?= Localization::translate('course_creation.estimated_duration') ?></label>
                        <input type="number" class="form-control" name="modules[][estimated_duration]" min="0" value="0">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" name="modules[][is_required]" value="1">
                            <label class="form-check-label"><?= Localization::translate('course_creation.required') ?></label>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary add-content-btn">
                            <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_content') ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="module-content-container">
                <!-- Module content will be added here -->
            </div>
        </div>
    </div>
</template>

<template id="content_template">
    <div class="content-item border rounded p-3 mb-2" data-content-index="">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0"><?= Localization::translate('course_creation.content_title') ?></h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-content">
                <i class="mdi mdi-delete"></i>
            </button>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.content_type') ?></label>
                    <select class="form-select content-type-select" name="modules[][content][][content_type]">
                        <option value=""><?= Localization::translate('course_creation.select_content') ?></option>
                        <option value="scorm">SCORM</option>
                        <option value="non_scorm">Non-SCORM</option>
                        <option value="assessment">Assessment</option>
                        <option value="audio">Audio</option>
                        <option value="video">Video</option>
                        <option value="document">Document</option>
                        <option value="image">Image</option>
                        <option value="external">External</option>
                        <option value="interactive">Interactive</option>
                        <option value="assignment">Assignment</option>
                    </select>
                </div>
            </div>
            <div class="col-md-8">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.select_content') ?></label>
                    <select class="form-select content-select" name="modules[][content][][content_id]">
                        <option value=""><?= Localization::translate('course_creation.no_content_available') ?></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.content_title') ?></label>
                    <input type="text" class="form-control" name="modules[][content][][title]">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.sort_order') ?></label>
                    <input type="number" class="form-control" name="modules[][content][][sort_order]" min="1" value="1">
                </div>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label"><?= Localization::translate('course_creation.content_description') ?></label>
            <textarea class="form-control" name="modules[][content][][description]" rows="2"></textarea>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.estimated_duration') ?></label>
                    <input type="number" class="form-control" name="modules[][content][][estimated_duration]" min="0" value="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="modules[][content][][is_required]" value="1">
                        <label class="form-check-label"><?= Localization::translate('course_creation.required') ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Prerequisite Template -->
<template id="prerequisite_template">
    <div class="prerequisite-item border rounded p-3 mb-2" data-prerequisite-index="">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0"><?= Localization::translate('course_creation.prerequisite_course') ?></h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-prerequisite">
                <i class="mdi mdi-delete"></i>
            </button>
        </div>
        <div class="row">
            <div class="col-md-8">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.prerequisite_course') ?></label>
                    <select class="form-select" name="prerequisite_courses[][prerequisite_course_id]">
                        <option value=""><?= Localization::translate('course_creation.select_prerequisite') ?></option>
                        <?php foreach ($existingCourses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= (isset($isEditMode) && $isEditMode && isset($editCourseData['prerequisite_courses']) && in_array($course['id'], $editCourseData['prerequisite_courses'])) ? 'selected' : '' ?>><?= htmlspecialchars($course['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.prerequisite_type') ?></label>
                    <select class="form-select" name="prerequisite_courses[][prerequisite_type]">
                        <option value="required"><?= Localization::translate('course_creation.required_prerequisite') ?></option>
                        <option value="recommended"><?= Localization::translate('course_creation.recommended_prerequisite') ?></option>
                    </select>
                </div>
            </div>

        </div>
    </div>
</template>

<?php
$flatVlrContent = [];
if (isset($vlrContent) && is_array($vlrContent)) {
    foreach ($vlrContent as $type => $items) {
        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $flatVlrContent[] = $item;
                }
            }
        }
    }
}
?>
<div id="vlrContentData" data-vlr-content='<?= htmlspecialchars(json_encode($vlrContent ?? []), ENT_QUOTES, "UTF-8") ?>'></div>

<script>
console.log('[DEBUG] PHP $vlrContent (raw):', <?= var_export(isset($vlrContent) ? $vlrContent : null, true) ?>);
console.log('[DEBUG] PHP $vlrContent (json):', <?= json_encode($vlrContent ?? []) ?>);
console.log('[DEBUG] data-vlr-content attribute:', document.getElementById('vlrContentData')?.getAttribute('data-vlr-content'));
try {
    window.vlrContent = JSON.parse(document.getElementById('vlrContentData')?.getAttribute('data-vlr-content'));
    console.log('[DEBUG] window.vlrContent set from data attribute:', window.vlrContent);
} catch (e) {
    console.error('[DEBUG] Error parsing VLR content from data attribute:', e);
    window.vlrContent = [];
}
</script> 