<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<?php
$isEdit = isset($question);
?>

<div class="main-content">
    <div class="container add-user-container">
        <div class="back-arrow-container">
            <a href="index.php?controller=QuestionController&action=index" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title">
                <?= $isEdit ? Localization::translate('edit_assessment_question_title') : Localization::translate('add_assessment_question_title'); ?>
            </h1>
        </div>

        <form id="addQuestionForm" method="POST" enctype="multipart/form-data"
            action="index.php?controller=QuestionController&action=<?= $isEdit ? 'edit&id=' . $question['id'] : 'add' ?>">

            <?php if (isset($isEdit) && $isEdit && isset($question['id'])): ?>
                <input type="hidden" name="question_id" value="<?= htmlspecialchars($question['id']) ?>">
            <?php endif; ?>
            <div class="container">

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="questionText" class="form-label"><?= Localization::translate('question_label'); ?>
                            <span class="text-danger">*</span></label>
                        <textarea id="questionText" name="questionText" class="form-control"
                            rows="3"><?= $isEdit ? htmlspecialchars($question['question_text'] ?? '') : '' ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label for="tagsInput" class="form-label"><?= Localization::translate('tags_keywords'); ?> <span
                                class="text-danger">*</span></label>
                        <div id="tagsContainer" class="tag-container"></div>
                        <input type="text" id="tagsInput" class="form-control"
                            placeholder="<?= Localization::translate('type_and_press_enter'); ?>">
                        <input type="hidden" name="tags" id="tagsHidden"
                            value="<?= $isEdit ? htmlspecialchars($question['tags'] ?? '') : '' ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="skillsInput"
                            class="form-label"><?= Localization::translate('competency_skills'); ?></label>
                        <div id="skillsContainer" class="tag-container"></div>
                        <input type="text" id="skillsInput" class="form-control"
                            placeholder="<?= Localization::translate('type_and_press_enter'); ?>">
                        <input type="hidden" name="skills" id="skillsHidden"
                            value="<?= $isEdit ? htmlspecialchars($question['skills'] ?? '') : '' ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="level" class="form-label"><?= Localization::translate('question_level'); ?></label>
                        <select id="level" name="level" class="form-select">
                            <?php foreach (['Low', 'Medium', 'Hard'] as $level): ?>
                                <option value="<?= $level ?>" <?= $isEdit && $question['level'] == $level ? 'selected' : '' ?>>
                                    <?= Localization::translate(strtolower($level)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="marks"
                            class="form-label"><?= Localization::translate('marks_per_question'); ?></label>
                        <select id="marks" name="marks" class="form-select">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>" <?= $isEdit && $question['marks'] == $i ? 'selected' : (!$isEdit && $i == 1 ? 'selected' : '') ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label d-block"><?= Localization::translate('status'); ?></label>
                        <?php $status = $isEdit ? $question['status'] : 'Active'; ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" id="active" value="Active"
                                <?= $status == 'Active' ? 'checked' : '' ?>>
                            <label class="form-check-label"
                                for="active"><?= Localization::translate('active'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" id="inactive" value="Inactive"
                                <?= $status == 'Inactive' ? 'checked' : '' ?>>
                            <label class="form-check-label"
                                for="inactive"><?= Localization::translate('inactive'); ?></label>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label d-block"><?= Localization::translate('question_type'); ?></label>
                        <?php $type = $isEdit ? $question['question_type'] : 'Objective'; ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="questionFormType" id="objective"
                                value="Objective" <?= $type == 'Objective' ? 'checked' : '' ?>>
                            <label class="form-check-label"
                                for="objective"><?= Localization::translate('objective'); ?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="questionFormType" id="subjective"
                                value="Subjective" <?= $type == 'Subjective' ? 'checked' : '' ?>>
                            <label class="form-check-label"
                                for="subjective"><?= Localization::translate('subjective'); ?></label>
                        </div>
                    </div>
                    <div class="col-md-4 objective-only">
                        <label for="answerCount"
                            class="form-label"><?= Localization::translate('how_many_answer_options'); ?></label>
                        <select id="answerCount" name="answerCount" class="form-select">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>" <?= $isEdit && count($options) == $i ? 'selected' : '' ?>><?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3 objective-only">
                    <div class="col-md-6">
                        <label for="questionMediaType"
                            class="form-label"><?= Localization::translate('question_media_type'); ?></label>
                        <?php $mediaType = $isEdit ? $question['media_type'] : 'text'; ?>
                        <select id="questionMediaType" name="questionMediaType" class="form-select">
                            <?php foreach (['text', 'image', 'audio', 'video'] as $type): ?>
                                <option value="<?= $type ?>" <?= $mediaType == $type ? 'selected' : '' ?>>
                                    <?= Localization::translate($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php
                    $mediaType = $isEdit && !empty($question['media_type']) ? $question['media_type'] : 'text';
                    ?>

                    <div class="col-md-6 <?= $mediaType == 'text' ? 'd-none' : '' ?>" id="mediaUploadContainer">
                        <label for="mediaFile" class="form-label"><?= Localization::translate('upload_media'); ?> <span
                                class="text-danger">*</span></label>
                        <input type="file" id="mediaFile" name="mediaFile" class="form-control">
                        <div id="mediaPreview" class="mt-2">
                            <?php if ($isEdit && !empty($question['media_file'])): ?>
                                <?php $mediaPath = '' . $question['media_file']; ?>
                                <?php if ($mediaType == 'image'): ?>
                                    <img src="<?= $mediaPath ?>" class="img-thumbnail" width="200">
                                <?php elseif ($mediaType == 'audio'): ?>
                                    <audio controls src="<?= $mediaPath ?>"></audio>
                                <?php elseif ($mediaType == 'video'): ?>
                                    <video controls width="200" src="<?= $mediaPath ?>"></video>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div id="answerOptionsContainer" class="row g-3 objective-only mt-3">
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <?php
                        $isVisible = $isEdit && isset($options[$i - 1]);
                        $optionText = $isVisible ? htmlspecialchars($options[$i - 1]['option_text'] ?? '') : '';
                        $isCorrect = $isVisible && $options[$i - 1]['is_correct'];
                        ?>
                        <div class="col-md-12 option-block <?= $isVisible ? '' : 'd-none' ?>" data-index="<?= $i ?>">
                            <label for="option_<?= $i ?>" class="form-label"><?= Localization::translate('option'); ?>
                                <?= $i ?> <span class="text-danger">*</span></label>
                            <textarea id="option_<?= $i ?>" name="options[<?= $i ?>][text]" rows="2" maxlength="500"
                                class="form-control option-textarea"><?= $optionText ?></textarea>
                            <small class="text-muted"><span
                                    id="charCount_<?= $i ?>"><?= strlen($optionText) ?></span>/500</small>

                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="options[<?= $i ?>][correct]"
                                    id="correct_<?= $i ?>" <?= $isCorrect ? 'checked' : '' ?>>
                                <label class="form-check-label"
                                    for="correct_<?= $i ?>"><?= Localization::translate('correct_answer'); ?></label>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12 form-actions">
                        <button type="submit" class="btn btn-primary"><?= Localization::translate('submit'); ?></button>
                        <button type="reset" class="btn btn-danger"><?= Localization::translate('cancel'); ?></button>
                    </div>
                </div>

            </div>
        </form>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const answerCountSelect = document.getElementById("answerCount");
        const questionTypeSelect = document.getElementById("questionMediaType");
        const mediaUploadContainer = document.getElementById("mediaUploadContainer");
        const mediaFile = document.getElementById("mediaFile");
        const mediaPreview = document.getElementById("mediaPreview");
        const questionFormTypeRadios = document.querySelectorAll('input[name="questionFormType"]');

        function updateAnswerOptions() {
            const selected = parseInt(answerCountSelect.value);
            document.querySelectorAll(".option-block").forEach(block => {
                const index = parseInt(block.dataset.index);
                block.classList.toggle("d-none", index > selected);
            });
        }

        function updateMediaUpload() {
            const selectedType = questionTypeSelect.value;
            mediaUploadContainer.classList.toggle("d-none", selectedType === "text");
        }

        function toggleObjectiveFields() {
            const isObjective = document.getElementById("objective").checked;
            document.querySelectorAll(".objective-only").forEach(el => {
                el.classList.toggle("d-none", !isObjective);
            });
        }

        // Char count logic
        document.querySelectorAll(".option-textarea").forEach(textarea => {
            textarea.addEventListener("input", function () {
                const id = this.id.split("_")[1];
                document.getElementById("charCount_" + id).innerText = this.value.length;
            });
        });

        answerCountSelect.addEventListener("change", updateAnswerOptions);
        questionTypeSelect.addEventListener("change", updateMediaUpload);
        questionFormTypeRadios.forEach(radio => {
            radio.addEventListener("change", toggleObjectiveFields);
        });

        toggleObjectiveFields();
        updateAnswerOptions();
        updateMediaUpload();

        // Media preview
        mediaFile.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) return;

            const url = URL.createObjectURL(file);
            let preview = '';

            if (file.type.startsWith("image/")) {
                preview = `<img src="${url}" class="img-thumbnail" width="200">`;
            } else if (file.type.startsWith("audio/")) {
                preview = `<audio controls src="${url}"></audio>`;
            } else if (file.type.startsWith("video/")) {
                preview = `<video controls width="200" src="${url}"></video>`;
            }

            mediaPreview.innerHTML = preview;
        });

        // ----------------------------------------
        // Tag and Competency Skills functionality
        // ----------------------------------------
        function initTagInput(containerId, inputId, hiddenId, initialTags = []) {
            const container = document.getElementById(containerId);
            const input = document.getElementById(inputId);
            const hidden = document.getElementById(hiddenId);
            const tags = initialTags.length ? [...initialTags] : [];

            function renderTags() {
                container.innerHTML = '';
                tags.forEach((tag, index) => {
                    const tagEl = document.createElement('div');
                    tagEl.className = 'tag';
                    tagEl.innerHTML = `${tag} <span class="remove-tag" data-index="${index}">&times;</span>`;
                    container.appendChild(tagEl);
                });
                hidden.value = tags.join(',');
            }

            input.addEventListener('keydown', function (e) {
                if ((e.key === 'Enter' || e.key === ',') && input.value.trim() !== '') {
                    e.preventDefault();
                    const newTag = input.value.trim();
                    if (!tags.includes(newTag)) {
                        tags.push(newTag);
                        renderTags();
                    }
                    input.value = '';
                }
            });

            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-tag')) {
                    const index = parseInt(e.target.getAttribute('data-index'));
                    tags.splice(index, 1);
                    renderTags();
                }
            });

            renderTags(); // initial rendering for edit mode
        }

        // Initialize tag inputs with pre-filled values
        const initialTags = document.getElementById("tagsHidden").value.split(',').filter(Boolean);
        const initialSkills = document.getElementById("skillsHidden").value.split(',').filter(Boolean);
        initTagInput('tagsContainer', 'tagsInput', 'tagsHidden', initialTags);
        initTagInput('skillsContainer', 'skillsInput', 'skillsHidden', initialSkills);
    });
</script>


<script src="public/js/add_question_validation.js"></script>
<!-- ✅ SCORM Validation Translations -->
<script>
   const translations = <?= json_encode([
    // Document modal labels
    "document.modal.add" => Localization::translate("document.modal.add"),
    "document.modal.edit" => Localization::translate("document.modal.edit"),

    // Document validation messages
    "validation.document_title_required" => Localization::translate("validation.document_title_required"),
    "validation.document_category_required" => Localization::translate("validation.document_category_required"),
    "validation.document_file_required" => Localization::translate("validation.document_file_required"),
    "validation.invalid_file_format" => Localization::translate("validation.invalid_file_format"),
    "validation.file_size_exceeded" => Localization::translate("validation.file_size_exceeded"),
    "validation.version_required" => Localization::translate("validation.version_required"),
    "validation.tags_required" => Localization::translate("validation.tags_required"),
    "error.document_form_not_found" => Localization::translate("error.document_form_not_found"),

    // Document categories
    "document.category.word_excel_ppt" => Localization::translate("document.category.word_excel_ppt"),
    "document.category.ebook_manual" => Localization::translate("document.category.ebook_manual"),
    "document.category.research_paper" => Localization::translate("document.category.research_paper"),

    // Assessment validation messages
    "assessment.validation.required_field" => Localization::translate("assessment.validation.required_field"),
    "assessment.validation.correct_answer_required" => Localization::translate("assessment.validation.correct_answer_required"),
    "assessment.validation.media_required" => Localization::translate("assessment.validation.media_required"),
    "assessment.validation.invalid_media_type" => Localization::translate("assessment.validation.invalid_media_type"),
    "assessment.validation.media_size_exceeded" => Localization::translate("assessment.validation.media_size_exceeded"),
    "assessment.validation.tags_required" => Localization::translate("assessment.validation.tags_required")
]); ?>;

</script>


<?php include 'includes/footer.php'; ?>