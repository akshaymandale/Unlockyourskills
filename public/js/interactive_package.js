document.addEventListener("DOMContentLoaded", function () {
    const interactiveModal = new bootstrap.Modal(document.getElementById("interactiveModal"));
    const interactiveForm = document.getElementById("interactiveForm");
    const interactiveTitle = document.getElementById("interactive_title");
    const contentType = document.getElementById("content_type");
    const version = document.getElementById("interactive_version");
    const language = document.getElementById("interactive_language");
    const description = document.getElementById("interactive_description");
    const timeLimit = document.getElementById("interactive_timeLimit");
    const interactiveId = document.getElementById("interactive_id");
    const existingContentFile = document.getElementById("existing_content_file");
    const existingThumbnailImage = document.getElementById("existing_thumbnail_image");
    const existingMetadataFile = document.getElementById("existing_metadata_file");
    const contentFileDisplay = document.getElementById("contentFilePreview");
    const thumbnailDisplay = document.getElementById("thumbnailImagePreview");
    const metadataDisplay = document.getElementById("metadataFilePreview");
    const mobileSupport = document.querySelectorAll('input[name="interactive_mobileSupport"]');
    const progressTracking = document.querySelectorAll('input[name="interactive_progress_tracking"]');
    const assessmentIntegration = document.querySelectorAll('input[name="interactive_assessment_integration"]');

    // ✅ Tag Management for Interactive Content
    let interactiveTags = [];
    const interactiveTagContainer = document.getElementById("interactiveTagDisplay");
    const interactiveTagInput = document.getElementById("interactiveTagInput");
    const interactiveHiddenTagList = document.getElementById("interactiveTagList");

    // ✅ Add Tag Function
    function addInteractiveTag(tagText) {
        if (tagText && !interactiveTags.includes(tagText)) {
            interactiveTags.push(tagText);
            updateInteractiveTagDisplay();
            updateInteractiveHiddenTagList();
        }
    }

    // ✅ Remove Tag Function
    function removeInteractiveTag(tagText) {
        interactiveTags = interactiveTags.filter(tag => tag !== tagText);
        updateInteractiveTagDisplay();
        updateInteractiveHiddenTagList();
    }

    // ✅ Update Tag Display - Match SCORM Structure
    function updateInteractiveTagDisplay() {
        interactiveTagContainer.innerHTML = "";
        interactiveTags.forEach(tag => {
            const tagElement = document.createElement("span");
            tagElement.className = "tag";
            tagElement.innerHTML = `${tag} <button type="button" class="remove-tag" onclick="removeInteractiveTag('${tag}')">&times;</button>`;
            interactiveTagContainer.appendChild(tagElement);
        });
    }

    // ✅ Update Hidden Tag List
    function updateInteractiveHiddenTagList() {
        interactiveHiddenTagList.value = interactiveTags.join(",");
    }

    // ✅ Tag Input Event Listener
    if (interactiveTagInput) {
        interactiveTagInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter" || e.key === ",") {
                e.preventDefault();
                const tagText = this.value.trim();
                if (tagText) {
                    addInteractiveTag(tagText);
                    this.value = "";
                }
            }
        });

        interactiveTagInput.addEventListener("blur", function () {
            const tagText = this.value.trim();
            if (tagText) {
                addInteractiveTag(tagText);
                this.value = "";
            }
        });
    }

    // ✅ Make removeInteractiveTag globally accessible
    window.removeInteractiveTag = removeInteractiveTag;

    // ✅ Open Modal for Editing Interactive Content
    document.querySelectorAll(".edit-interactive").forEach(button => {
        button.addEventListener("click", function () {
            const interactiveData = JSON.parse(this.dataset.interactive);

            // Check if elements exist before setting values
            if (interactiveId) interactiveId.value = interactiveData.id || "";
            if (interactiveTitle) interactiveTitle.value = interactiveData.title || "";
            if (contentType) contentType.value = interactiveData.content_type || "";
            if (version) version.value = interactiveData.version || "";
            if (language) language.value = interactiveData.language || "";
            if (description) description.value = interactiveData.description || "";
            if (timeLimit) timeLimit.value = interactiveData.time_limit || "";

            // Set content type specific fields with error checking
            const setFieldValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || "";
                } else {
                    console.warn(`Element with ID '${id}' not found`);
                }
            };

            setFieldValue("content_url", interactiveData.content_url);
            setFieldValue("embed_code", interactiveData.embed_code);
            setFieldValue("ai_model", interactiveData.ai_model);
            setFieldValue("interaction_type", interactiveData.interaction_type);
            setFieldValue("difficulty_level", interactiveData.difficulty_level);
            setFieldValue("learning_objectives", interactiveData.learning_objectives);
            setFieldValue("prerequisites", interactiveData.prerequisites);
            setFieldValue("vr_platform", interactiveData.vr_platform);
            setFieldValue("ar_platform", interactiveData.ar_platform);
            setFieldValue("device_requirements", interactiveData.device_requirements);
            setFieldValue("tutor_personality", interactiveData.tutor_personality);
            setFieldValue("response_style", interactiveData.response_style);
            setFieldValue("knowledge_domain", interactiveData.knowledge_domain);
            setFieldValue("adaptation_algorithm", interactiveData.adaptation_algorithm);

            // Display existing files with cross button
            if (interactiveData.content_file) {
                showFilePreview('contentFilePreview', interactiveData.content_file, 'content_file');
                existingContentFile.value = interactiveData.content_file;
            } else {
                document.getElementById('contentFilePreview').innerHTML = "";
            }

            if (interactiveData.thumbnail_image) {
                showFilePreview('thumbnailImagePreview', interactiveData.thumbnail_image, 'thumbnail_image');
                existingThumbnailImage.value = interactiveData.thumbnail_image;
            } else {
                document.getElementById('thumbnailImagePreview').innerHTML = "";
            }

            if (interactiveData.metadata_file) {
                showFilePreview('metadataFilePreview', interactiveData.metadata_file, 'metadata_file');
                existingMetadataFile.value = interactiveData.metadata_file;
            } else {
                document.getElementById('metadataFilePreview').innerHTML = "";
            }

            // Pre-select radio buttons with error checking
            if (mobileSupport && mobileSupport.length > 0) {
                mobileSupport.forEach(radio => {
                    if (radio.value === interactiveData.mobile_support) {
                        radio.checked = true;
                    }
                });
            } else {
                console.warn('Mobile support radio buttons not found');
            }

            if (progressTracking && progressTracking.length > 0) {
                progressTracking.forEach(radio => {
                    if (radio.value === interactiveData.progress_tracking) {
                        radio.checked = true;
                    }
                });
            } else {
                console.warn('Progress tracking radio buttons not found');
            }

            if (assessmentIntegration && assessmentIntegration.length > 0) {
                assessmentIntegration.forEach(radio => {
                    if (radio.value === interactiveData.assessment_integration) {
                        radio.checked = true;
                    }
                });
            } else {
                console.warn('Assessment integration radio buttons not found');
            }

            // Pre-fill Tags with error checking
            if (interactiveTagContainer) {
                interactiveTagContainer.innerHTML = "";
                interactiveTags = [];
                if (interactiveData.tags) {
                    interactiveData.tags.split(",").forEach(tag => addInteractiveTag(tag.trim()));
                }
            } else {
                console.warn('Interactive tag container not found');
            }

            // Show content type specific fields
            handleContentTypeChange(interactiveData.content_type);

            // Set Modal Title to "Edit Interactive Content"
            document.getElementById("interactiveModalLabel").textContent = "Edit Interactive Content";

            interactiveModal.show();
        });
    });

    // ✅ Open Modal for Adding New Interactive Content
    const addInteractiveBtn = document.getElementById("addInteractiveBtn");
    if (addInteractiveBtn) {
        addInteractiveBtn.addEventListener("click", function () {
            // Reset Form Fields
            interactiveForm.reset();
            interactiveId.value = "";

            // Clear Tags
            interactiveTags = [];
            interactiveTagContainer.innerHTML = "";
            interactiveHiddenTagList.value = "";

            // Clear Existing File Displays
            existingContentFile.value = "";
            existingThumbnailImage.value = "";
            existingMetadataFile.value = "";
            document.getElementById('contentFilePreview').innerHTML = "";
            document.getElementById('thumbnailImagePreview').innerHTML = "";
            document.getElementById('metadataFilePreview').innerHTML = "";

            // Reset Radio Buttons to Default
            document.querySelector('input[name="interactive_mobileSupport"][value="No"]').checked = true;
            document.querySelector('input[name="interactive_progress_tracking"][value="Yes"]').checked = true;
            document.querySelector('input[name="interactive_assessment_integration"][value="No"]').checked = true;

            // Hide all conditional fields
            document.querySelectorAll('.ai-tutoring-fields, .ar-vr-fields, .adaptive-learning-fields').forEach(section => {
                section.style.display = 'none';
            });

            // Set Modal Title to "Add Interactive Content"
            document.getElementById("interactiveModalLabel").textContent = "Add Interactive Content";

            interactiveModal.show();
        });
    }

    // ✅ Handle Content Type Change
    function handleContentTypeChange(contentType) {
        // Hide all conditional fields
        document.querySelectorAll('.ai-tutoring-fields, .ar-vr-fields, .adaptive-learning-fields').forEach(section => {
            section.style.display = 'none';
        });

        // Show relevant fields based on content type
        switch (contentType) {
            case 'ai_tutoring':
                document.querySelectorAll('.ai-tutoring-fields').forEach(section => {
                    section.style.display = 'block';
                });
                break;
            case 'ar_vr':
                document.querySelectorAll('.ar-vr-fields').forEach(section => {
                    section.style.display = 'block';
                });
                break;
            case 'adaptive_learning':
                document.querySelectorAll('.adaptive-learning-fields').forEach(section => {
                    section.style.display = 'block';
                });
                break;
        }
    }

    // ✅ Content Type Change Event Listener
    if (contentType) {
        contentType.addEventListener("change", function() {
            handleContentTypeChange(this.value);
        });
    }

    // ✅ File Upload Preview Functions
    function showFilePreview(containerId, fileName, fieldType) {
        const container = document.getElementById(containerId);
        const fileExtension = fileName.split('.').pop().toLowerCase();

        let previewHTML = '';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
            // Image preview
            previewHTML = `
                <div class="preview-wrapper">
                    <img src="uploads/interactive/${fileName}" alt="Preview" style="max-width: 150px; max-height: 100px; object-fit: cover;">
                    <button type="button" class="remove-preview" onclick="removeFilePreview('${containerId}', '${fieldType}')">×</button>
                </div>
                <p style="margin-top: 5px; font-size: 12px;">Current file: ${fileName}</p>
            `;
        } else {
            // File link preview
            previewHTML = `
                <div class="preview-wrapper">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa; position: relative;">
                        <i class="fas fa-file" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeFilePreview('${containerId}', '${fieldType}')">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px;">Current file: <a href="uploads/interactive/${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        }

        container.innerHTML = previewHTML;
    }

    // ✅ Remove File Preview Function
    window.removeFilePreview = function(containerId, fieldType) {
        document.getElementById(containerId).innerHTML = '';

        // Clear the hidden field
        if (fieldType === 'content_file') {
            document.getElementById('existing_content_file').value = '';
        } else if (fieldType === 'thumbnail_image') {
            document.getElementById('existing_thumbnail_image').value = '';
        } else if (fieldType === 'metadata_file') {
            document.getElementById('existing_metadata_file').value = '';
        }
    };

    // ✅ File Input Change Handlers
    document.getElementById('content_file').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            showNewFilePreview('contentFilePreview', file);
        }
    });

    document.getElementById('thumbnail_image').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            showNewFilePreview('thumbnailImagePreview', file);
        }
    });

    document.getElementById('metadata_file').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            showNewFilePreview('metadataFilePreview', file);
        }
    });

    // ✅ Show New File Preview Function
    function showNewFilePreview(containerId, file) {
        const container = document.getElementById(containerId);
        const fileExtension = file.name.split('.').pop().toLowerCase();

        let previewHTML = '';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
            // Image preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewHTML = `
                    <div class="preview-wrapper">
                        <img src="${e.target.result}" alt="Preview" style="max-width: 150px; max-height: 100px; object-fit: cover;">
                        <button type="button" class="remove-preview" onclick="clearFileInput('${containerId}')">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px;">New file: ${file.name}</p>
                `;
                container.innerHTML = previewHTML;
            };
            reader.readAsDataURL(file);
        } else {
            // File preview
            previewHTML = `
                <div class="preview-wrapper">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa; position: relative;">
                        <i class="fas fa-file" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="clearFileInput('${containerId}')">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px;">New file: ${file.name}</p>
                </div>
            `;
            container.innerHTML = previewHTML;
        }
    }

    // ✅ Clear File Input Function
    window.clearFileInput = function(containerId) {
        document.getElementById(containerId).innerHTML = '';

        // Clear the file input
        if (containerId === 'contentFilePreview') {
            document.getElementById('content_file').value = '';
        } else if (containerId === 'thumbnailImagePreview') {
            document.getElementById('thumbnail_image').value = '';
        } else if (containerId === 'metadataFilePreview') {
            document.getElementById('metadata_file').value = '';
        }
    };
});
