<?php
require_once 'views/includes/header.php';
require_once 'config/Localization.php';

Localization::loadLanguage($_SESSION['lang'] ?? 'en');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="/dashboard"><?= Localization::translate('dashboard.title') ?></a></li>
                        <li class="breadcrumb-item active"><?= Localization::translate('course_creation.title') ?></li>
                    </ol>
                </div>
                <h4 class="page-title"><?= Localization::translate('course_creation.title') ?></h4>
                <p class="text-muted"><?= Localization::translate('course_creation.subtitle') ?></p>
            </div>
        </div>
    </div>

    <form id="courseCreationForm" method="POST" action="/course-creation" enctype="multipart/form-data">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Navigation Tabs -->
                        <ul class="nav nav-tabs nav-bordered" id="courseCreationTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-info-tab" data-bs-toggle="tab" data-bs-target="#basic-info" type="button" role="tab">
                                    <i class="mdi mdi-information-outline me-1"></i>
                                    <?= Localization::translate('course_creation.basic_info') ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="modules-tab" data-bs-toggle="tab" data-bs-target="#modules" type="button" role="tab">
                                    <i class="mdi mdi-view-module me-1"></i>
                                    <?= Localization::translate('course_creation.modules_sections') ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="prerequisites-tab" data-bs-toggle="tab" data-bs-target="#prerequisites" type="button" role="tab">
                                    <i class="mdi mdi-arrow-up-bold-circle me-1"></i>
                                    <?= Localization::translate('course_creation.prerequisites') ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="assessments-tab" data-bs-toggle="tab" data-bs-target="#assessments" type="button" role="tab">
                                    <i class="mdi mdi-clipboard-check me-1"></i>
                                    <?= Localization::translate('course_creation.assessments') ?>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="feedback-surveys-tab" data-bs-toggle="tab" data-bs-target="#feedback-surveys" type="button" role="tab">
                                    <i class="mdi mdi-comment-multiple me-1"></i>
                                    <?= Localization::translate('course_creation.feedback_surveys') ?>
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="courseCreationTabContent">
                            <!-- Basic Information Tab -->
                            <div class="tab-pane fade show active" id="basic-info" role="tabpanel">
                                <div class="row mt-3">
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label for="course_title" class="form-label"><?= Localization::translate('course_creation.course_title') ?> <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="course_title" name="title" placeholder="<?= Localization::translate('course_creation.course_title_placeholder') ?>" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="short_description" class="form-label"><?= Localization::translate('course_creation.short_description') ?></label>
                                            <textarea class="form-control" id="short_description" name="short_description" rows="2" placeholder="<?= Localization::translate('course_creation.short_description_placeholder') ?>"></textarea>
                                            <div class="form-text"><?= Localization::translate('course_creation.short_description_placeholder') ?></div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="description" class="form-label"><?= Localization::translate('course_creation.description') ?></label>
                                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="<?= Localization::translate('course_creation.description_placeholder') ?>"></textarea>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label"><?= Localization::translate('course_creation.category') ?> <span class="text-danger">*</span></label>
                                            <select class="form-select" id="category_id" name="category_id" required>
                                                <option value=""><?= Localization::translate('course_creation.select_category') ?></option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="subcategory_id" class="form-label"><?= Localization::translate('course_creation.subcategory') ?> <span class="text-danger">*</span></label>
                                            <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                                                <option value=""><?= Localization::translate('course_creation.select_subcategory') ?></option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="course_type" class="form-label"><?= Localization::translate('course_creation.course_type') ?> <span class="text-danger">*</span></label>
                                            <select class="form-select" id="course_type" name="course_type" required>
                                                <option value=""><?= Localization::translate('course_creation.select_category') ?></option>
                                                <option value="e-learning"><?= Localization::translate('course_creation.e_learning') ?></option>
                                                <option value="classroom"><?= Localization::translate('course_creation.classroom') ?></option>
                                                <option value="blended"><?= Localization::translate('course_creation.blended') ?></option>
                                                <option value="assessment"><?= Localization::translate('course_creation.assessment') ?></option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="difficulty_level" class="form-label"><?= Localization::translate('course_creation.difficulty_level') ?> <span class="text-danger">*</span></label>
                                            <select class="form-select" id="difficulty_level" name="difficulty_level" required>
                                                <option value=""><?= Localization::translate('course_creation.select_category') ?></option>
                                                <option value="beginner"><?= Localization::translate('course_creation.beginner') ?></option>
                                                <option value="intermediate"><?= Localization::translate('course_creation.intermediate') ?></option>
                                                <option value="advanced"><?= Localization::translate('course_creation.advanced') ?></option>
                                                <option value="expert"><?= Localization::translate('course_creation.expert') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="target_audience" class="form-label"><?= Localization::translate('course_creation.target_audience') ?></label>
                                            <textarea class="form-control" id="target_audience" name="target_audience" rows="3" placeholder="<?= Localization::translate('course_creation.target_audience_placeholder') ?>"></textarea>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label"><?= Localization::translate('course_creation.learning_objectives') ?></label>
                                            <div id="learning_objectives_container">
                                                <div class="input-group mb-2">
                                                    <input type="text" class="form-control" name="learning_objectives[]" placeholder="<?= Localization::translate('course_creation.learning_objective_placeholder') ?>">
                                                    <button type="button" class="btn btn-outline-secondary add-learning-objective">
                                                        <i class="mdi mdi-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="add_learning_objective">
                                                <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_learning_objective') ?>
                                            </button>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label"><?= Localization::translate('course_creation.tags') ?></label>
                                            <div id="tags_container">
                                                <div class="input-group mb-2">
                                                    <input type="text" class="form-control" name="tags[]" placeholder="<?= Localization::translate('course_creation.tag_placeholder') ?>">
                                                    <button type="button" class="btn btn-outline-secondary add-tag">
                                                        <i class="mdi mdi-plus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="add_tag">
                                                <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_tag') ?>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="duration_hours" class="form-label"><?= Localization::translate('course_creation.duration') ?> (<?= Localization::translate('course_creation.hours') ?>)</label>
                                                    <input type="number" class="form-control" id="duration_hours" name="duration_hours" min="0" max="999.99" step="0.01" value="0">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="duration_minutes" class="form-label"><?= Localization::translate('course_creation.duration') ?> (<?= Localization::translate('course_creation.minutes') ?>)</label>
                                                    <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" min="0" max="59" value="0">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="max_attempts" class="form-label"><?= Localization::translate('course_creation.max_attempts') ?></label>
                                                    <input type="number" class="form-control" id="max_attempts" name="max_attempts" min="1" max="999" value="1">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="passing_score" class="form-label"><?= Localization::translate('course_creation.passing_score') ?></label>
                                                    <input type="number" class="form-control" id="passing_score" name="passing_score" min="0" max="100" step="0.01" value="70">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label"><?= Localization::translate('course_creation.course_settings') ?></label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_self_paced" name="is_self_paced" value="1">
                                                <label class="form-check-label" for="is_self_paced">
                                                    <?= Localization::translate('course_creation.self_paced') ?>
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1">
                                                <label class="form-check-label" for="is_featured">
                                                    <?= Localization::translate('course_creation.featured_course') ?>
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1">
                                                <label class="form-check-label" for="is_published">
                                                    <?= Localization::translate('course_creation.published') ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="thumbnail" class="form-label"><?= Localization::translate('course_creation.thumbnail_image') ?></label>
                                            <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="banner" class="form-label"><?= Localization::translate('course_creation.banner_image') ?></label>
                                            <input type="file" class="form-control" id="banner" name="banner" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Modules Tab -->
                            <div class="tab-pane fade" id="modules" role="tabpanel">
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5><?= Localization::translate('course_creation.modules') ?></h5>
                                        <button type="button" class="btn btn-primary" id="add_module">
                                            <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_module') ?>
                                        </button>
                                    </div>

                                    <div id="modules_container">
                                        <!-- Modules will be added here dynamically -->
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

                            <!-- Assessments Tab -->
                            <div class="tab-pane fade" id="assessments" role="tabpanel">
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5><?= Localization::translate('course_creation.assessments') ?></h5>
                                        <button type="button" class="btn btn-primary" id="add_assessment">
                                            <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_assessment') ?>
                                        </button>
                                    </div>

                                    <div id="assessments_container">
                                        <!-- Assessments will be added here dynamically -->
                                    </div>
                                </div>
                            </div>

                            <!-- Feedback & Surveys Tab -->
                            <div class="tab-pane fade" id="feedback-surveys" role="tabpanel">
                                <div class="mt-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5><?= Localization::translate('course_creation.feedback') ?></h5>
                                                <button type="button" class="btn btn-primary" id="add_feedback">
                                                    <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_feedback') ?>
                                                </button>
                                            </div>
                                            <div id="feedback_container">
                                                <!-- Feedback will be added here dynamically -->
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5><?= Localization::translate('course_creation.surveys') ?></h5>
                                                <button type="button" class="btn btn-primary" id="add_survey">
                                                    <i class="mdi mdi-plus me-1"></i><?= Localization::translate('course_creation.add_survey') ?>
                                                </button>
                                            </div>
                                            <div id="surveys_container">
                                                <!-- Surveys will be added here dynamically -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" id="save_draft">
                                <i class="mdi mdi-content-save me-1"></i><?= Localization::translate('course_creation.save_draft') ?>
                            </button>
                            <button type="button" class="btn btn-info" id="preview_course">
                                <i class="mdi mdi-eye me-1"></i><?= Localization::translate('course_creation.preview_course') ?>
                            </button>
                            <button type="submit" class="btn btn-primary" id="create_course">
                                <i class="mdi mdi-plus-circle me-1"></i><?= Localization::translate('course_creation.create_course') ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Module Template -->
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

<!-- Content Item Template -->
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
            <div class="col-md-6">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.prerequisite_course') ?></label>
                    <select class="form-select" name="prerequisite_courses[][prerequisite_course_id]">
                        <option value=""><?= Localization::translate('course_creation.select_prerequisite') ?></option>
                        <?php foreach ($existingCourses as $course): ?>
                            <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.prerequisite_type') ?></label>
                    <select class="form-select" name="prerequisite_courses[][prerequisite_type]">
                        <option value="required"><?= Localization::translate('course_creation.required_prerequisite') ?></option>
                        <option value="recommended"><?= Localization::translate('course_creation.recommended_prerequisite') ?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.minimum_score') ?></label>
                    <input type="number" class="form-control" name="prerequisite_courses[][minimum_score]" min="0" max="100" step="0.01" value="0">
                </div>
            </div>
        </div>
    </div>
</template>

<!-- Assessment Template -->
<template id="assessment_template">
    <div class="assessment-item border rounded p-3 mb-2" data-assessment-index="">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0"><?= Localization::translate('course_creation.assessment_title') ?></h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-assessment">
                <i class="mdi mdi-delete"></i>
            </button>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.assessment_type') ?></label>
                    <select class="form-select" name="assessments[][assessment_type]">
                        <option value="pre_course"><?= Localization::translate('course_creation.pre_course') ?></option>
                        <option value="post_course"><?= Localization::translate('course_creation.post_course') ?></option>
                        <option value="module_assessment"><?= Localization::translate('course_creation.module_assessment') ?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-8">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.select_assessment') ?></label>
                    <select class="form-select" name="assessments[][assessment_id]">
                        <option value=""><?= Localization::translate('course_creation.select_assessment') ?></option>
                        <?php if (isset($vlrContent['assessment'])): ?>
                            <?php foreach ($vlrContent['assessment'] as $assessment): ?>
                                <option value="<?= $assessment['id'] ?>"><?= htmlspecialchars($assessment['title']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.assessment_title') ?></label>
                    <input type="text" class="form-control" name="assessments[][title]">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.passing_score') ?></label>
                    <input type="number" class="form-control" name="assessments[][passing_score]" min="0" max="100" step="0.01" value="70">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.max_attempts') ?></label>
                    <input type="number" class="form-control" name="assessments[][max_attempts]" min="1" max="999" value="1">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.time_limit') ?></label>
                    <input type="number" class="form-control" name="assessments[][time_limit]" min="0" value="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="assessments[][is_required]" value="1">
                        <label class="form-check-label"><?= Localization::translate('course_creation.required') ?></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label"><?= Localization::translate('course_creation.assessment_description') ?></label>
            <textarea class="form-control" name="assessments[][description]" rows="2"></textarea>
        </div>
    </div>
</template>

<!-- Feedback Template -->
<template id="feedback_template">
    <div class="feedback-item border rounded p-3 mb-2" data-feedback-index="">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0"><?= Localization::translate('course_creation.feedback_title') ?></h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-feedback">
                <i class="mdi mdi-delete"></i>
            </button>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.feedback_type') ?></label>
                    <select class="form-select" name="feedback[][feedback_type]">
                        <option value="pre_course"><?= Localization::translate('course_creation.pre_course_feedback') ?></option>
                        <option value="post_course"><?= Localization::translate('course_creation.post_course_feedback') ?></option>
                        <option value="module_feedback"><?= Localization::translate('course_creation.module_feedback') ?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-8">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.select_feedback') ?></label>
                    <select class="form-select" name="feedback[][feedback_id]">
                        <option value=""><?= Localization::translate('course_creation.select_feedback') ?></option>
                        <?php if (isset($vlrContent['feedback'])): ?>
                            <?php foreach ($vlrContent['feedback'] as $feedback): ?>
                                <option value="<?= $feedback['id'] ?>"><?= htmlspecialchars($feedback['title']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.feedback_title') ?></label>
                    <input type="text" class="form-control" name="feedback[][title]">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="feedback[][is_required]" value="1">
                        <label class="form-check-label"><?= Localization::translate('course_creation.required') ?></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label"><?= Localization::translate('course_creation.feedback_description') ?></label>
            <textarea class="form-control" name="feedback[][description]" rows="2"></textarea>
        </div>
    </div>
</template>

<!-- Survey Template -->
<template id="survey_template">
    <div class="survey-item border rounded p-3 mb-2" data-survey-index="">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0"><?= Localization::translate('course_creation.survey_title') ?></h6>
            <button type="button" class="btn btn-sm btn-outline-danger remove-survey">
                <i class="mdi mdi-delete"></i>
            </button>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.survey_type') ?></label>
                    <select class="form-select" name="surveys[][survey_type]">
                        <option value="pre_course"><?= Localization::translate('course_creation.pre_course_survey') ?></option>
                        <option value="post_course"><?= Localization::translate('course_creation.post_course_survey') ?></option>
                        <option value="module_survey"><?= Localization::translate('course_creation.module_survey') ?></option>
                    </select>
                </div>
            </div>
            <div class="col-md-8">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.select_survey') ?></label>
                    <select class="form-select" name="surveys[][survey_id]">
                        <option value=""><?= Localization::translate('course_creation.select_survey') ?></option>
                        <?php if (isset($vlrContent['survey'])): ?>
                            <?php foreach ($vlrContent['survey'] as $survey): ?>
                                <option value="<?= $survey['id'] ?>"><?= htmlspecialchars($survey['title']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="mb-2">
                    <label class="form-label"><?= Localization::translate('course_creation.survey_title') ?></label>
                    <input type="text" class="form-control" name="surveys[][title]">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="surveys[][is_required]" value="1">
                        <label class="form-check-label"><?= Localization::translate('course_creation.required') ?></label>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label"><?= Localization::translate('course_creation.survey_description') ?></label>
            <textarea class="form-control" name="surveys[][description]" rows="2"></textarea>
        </div>
    </div>
</template>

<?php require_once 'views/includes/footer.php'; ?>

<script src="/public/js/course_creation.js"></script> 