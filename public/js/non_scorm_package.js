document.addEventListener("DOMContentLoaded", function () {
    // ✅ Non-SCORM Package JavaScript

    // ✅ Initialize Bootstrap Tabs for Non-SCORM Sub-tabs
    const nonScormTabLinks = document.querySelectorAll('#nonScormSubTabs .nav-link');
    nonScormTabLinks.forEach(tabLink => {
        tabLink.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all Non-SCORM tabs
            nonScormTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active class from all Non-SCORM tab panes (only within the Non-SCORM section)
            const nonScormTabContent = document.querySelector('#non-scorm .tab-content');
            if (nonScormTabContent) {
                nonScormTabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                    tabPane.classList.remove('show', 'active');
                });
            }

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding tab pane
            const targetId = this.getAttribute('href');
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add('show', 'active');
            }
        });
    });

    const nonScormForm = document.getElementById("nonScormForm");
    const nonScormTitle = document.getElementById("non_scorm_title");
    const contentType = document.getElementById("nonscorm_content_type");
    const version = document.getElementById("nonscorm_version");
    const language = document.getElementById("nonscorm_language");
    const description = document.getElementById("nonscorm_description");
    const timeLimit = document.getElementById("nonscorm_timeLimit");
    const nonScormId = document.getElementById("non_scorm_id");

    // File input elements
    const contentPackageInput = document.getElementById("content_package");
    const launchFileInput = document.getElementById("launch_file");
    const thumbnailImageInput = document.getElementById("nonscorm_thumbnail_image");
    const manifestFileInput = document.getElementById("manifest_file");

    // Radio button elements
    const mobileSupport = document.querySelectorAll('input[name="nonscorm_mobileSupport"]');
    const responsiveDesign = document.querySelectorAll('input[name="nonscorm_responsive_design"]');
    const offlineSupport = document.querySelectorAll('input[name="nonscorm_offline_support"]');
    const progressTracking = document.querySelectorAll('input[name="nonscorm_progress_tracking"]');
    const assessmentIntegration = document.querySelectorAll('input[name="nonscorm_assessment_integration"]');

    // Tag management
    const nonScormTagInput = document.getElementById("nonscormTagInput");
    const nonScormTagDisplay = document.getElementById("nonscormTagDisplay");
    const nonScormTagContainer = nonScormTagDisplay;
    const nonScormHiddenTagList = document.getElementById("nonscormTagList");
    let nonScormTags = [];

    // ✅ Content Type Change Handler
    if (contentType) {
        contentType.addEventListener("change", function () {
            const selectedType = this.value;
            
            // Hide all content type specific fields
            document.querySelectorAll('.html5-fields, .flash-fields, .unity-fields, .custom-web-fields, .mobile-app-fields').forEach(field => {
                field.style.display = 'none';
            });

            // Show relevant fields based on content type
            switch (selectedType) {
                case 'html5':
                    document.querySelector('.html5-fields').style.display = 'block';
                    break;
                case 'flash':
                    document.querySelector('.flash-fields').style.display = 'block';
                    break;
                case 'unity':
                    document.querySelector('.unity-fields').style.display = 'block';
                    break;
                case 'custom_web':
                    document.querySelector('.custom-web-fields').style.display = 'block';
                    break;
                case 'mobile_app':
                    document.querySelector('.mobile-app-fields').style.display = 'block';
                    break;
            }
        });
    }

    // ✅ Tag Management Functions
    function addNonScormTag(tagText) {
        if (tagText && !nonScormTags.includes(tagText)) {
            nonScormTags.push(tagText);
            updateNonScormTagDisplay();
            updateNonScormHiddenTagList();
        }
    }

    function removeNonScormTag(tagText) {
        nonScormTags = nonScormTags.filter(tag => tag !== tagText);
        updateNonScormTagDisplay();
        updateNonScormHiddenTagList();
    }

    function updateNonScormTagDisplay() {
        if (nonScormTagContainer) {
            nonScormTagContainer.innerHTML = nonScormTags.map(tag =>
                `<span class="tag">${tag} <button type="button" class="remove-tag" onclick="removeNonScormTag('${tag}')">&times;</button></span>`
            ).join('');
        }
    }

    function updateNonScormHiddenTagList() {
        if (nonScormHiddenTagList) {
            nonScormHiddenTagList.value = nonScormTags.join(',');
        }
    }

    // ✅ Tag Input Event Listener
    if (nonScormTagInput) {
        nonScormTagInput.addEventListener("keypress", function (e) {
            if (e.key === "Enter" || e.key === ",") {
                e.preventDefault();
                const tagText = this.value.trim();
                if (tagText) {
                    addNonScormTag(tagText);
                    this.value = "";
                }
            }
        });
    }

    // ✅ File Preview Functions
    function createFilePreview(file, previewContainer, removeCallback) {
        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'preview-wrapper';
        previewWrapper.style.position = 'relative';
        previewWrapper.style.display = 'inline-block';
        previewWrapper.style.marginTop = '10px';

        if (file.type && file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.maxWidth = '150px';
            img.style.maxHeight = '150px';
            img.style.border = '1px solid #ddd';
            img.style.borderRadius = '4px';
            previewWrapper.appendChild(img);
        } else {
            const fileInfo = document.createElement('div');
            fileInfo.innerHTML = `<i class="fas fa-file"></i> ${file.name}`;
            fileInfo.style.padding = '10px';
            fileInfo.style.border = '1px solid #ddd';
            fileInfo.style.borderRadius = '4px';
            fileInfo.style.backgroundColor = '#f8f9fa';
            previewWrapper.appendChild(fileInfo);
        }

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'remove-preview';
        removeBtn.innerHTML = '×';
        removeBtn.onclick = removeCallback;
        previewWrapper.appendChild(removeBtn);

        previewContainer.innerHTML = '';
        previewContainer.appendChild(previewWrapper);
    }

    function createExistingFilePreview(fileName, previewContainer) {
        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
            // Image preview with cross button
            previewHTML = `
                <div class="preview-wrapper">
                    <img src="uploads/non_scorm/${fileName}" alt="Preview" style="max-width: 150px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px;">
                    <button type="button" class="remove-preview" onclick="removeNonScormFilePreview('${previewContainer.id}')">×</button>
                </div>
                <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: ${fileName}</p>
            `;
        } else {
            // File link preview with cross button
            previewHTML = `
                <div class="preview-wrapper">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa; position: relative;">
                        <i class="fas fa-file" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeNonScormFilePreview('${previewContainer.id}')">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="uploads/non_scorm/${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        }

        previewContainer.innerHTML = previewHTML;
    }

    // Global function to remove file preview
    window.removeNonScormFilePreview = function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '';
        }

        // Clear the corresponding hidden field
        if (containerId === 'contentPackagePreview') {
            document.getElementById('existing_content_package').value = '';
        } else if (containerId === 'launchFilePreview') {
            document.getElementById('existing_launch_file').value = '';
        } else if (containerId === 'thumbnailImagePreview') {
            document.getElementById('existing_thumbnail_image').value = '';
        } else if (containerId === 'manifestFilePreview') {
            document.getElementById('existing_manifest_file').value = '';
        }
    };

    // ✅ File Input Event Listeners
    if (contentPackageInput) {
        contentPackageInput.addEventListener('change', function() {
            const file = this.files[0];
            const previewContainer = document.getElementById('contentPackagePreview');
            if (file && previewContainer) {
                createFilePreview(file, previewContainer, () => {
                    this.value = '';
                    previewContainer.innerHTML = '';
                });
            }
        });
    }

    if (launchFileInput) {
        launchFileInput.addEventListener('change', function() {
            const file = this.files[0];
            const previewContainer = document.getElementById('launchFilePreview');
            if (file && previewContainer) {
                createFilePreview(file, previewContainer, () => {
                    this.value = '';
                    previewContainer.innerHTML = '';
                });
            }
        });
    }

    if (thumbnailImageInput) {
        thumbnailImageInput.addEventListener('change', function() {
            const file = this.files[0];
            const previewContainer = document.getElementById('thumbnailImagePreview');
            if (file && previewContainer) {
                createFilePreview(file, previewContainer, () => {
                    this.value = '';
                    previewContainer.innerHTML = '';
                });
            }
        });
    }

    if (manifestFileInput) {
        manifestFileInput.addEventListener('change', function() {
            const file = this.files[0];
            const previewContainer = document.getElementById('manifestFilePreview');
            if (file && previewContainer) {
                createFilePreview(file, previewContainer, () => {
                    this.value = '';
                    previewContainer.innerHTML = '';
                });
            }
        });
    }

    // ✅ Open Modal for Editing Non-SCORM Content
    document.querySelectorAll(".edit-non-scorm").forEach(button => {
        button.addEventListener("click", function () {
            const nonScormData = JSON.parse(this.dataset.package);

            // Clear all preview containers first to prevent showing old previews
            document.querySelectorAll('#contentPackagePreview, #launchFilePreview, #thumbnailImagePreview, #manifestFilePreview').forEach(container => {
                container.innerHTML = "";
            });

            // Clear all hidden fields
            document.getElementById("existing_content_package").value = "";
            document.getElementById("existing_launch_file").value = "";
            document.getElementById("existing_thumbnail_image").value = "";
            document.getElementById("existing_manifest_file").value = "";

            // Check if elements exist before setting values
            if (nonScormId) nonScormId.value = nonScormData.id || "";
            if (nonScormTitle) nonScormTitle.value = nonScormData.title || "";
            if (contentType) {
                contentType.value = nonScormData.content_type || "";
                // Trigger change event to show relevant fields
                contentType.dispatchEvent(new Event('change'));
            }
            if (version) version.value = nonScormData.version || "";
            if (language) language.value = nonScormData.language || "";
            if (description) description.value = nonScormData.description || "";
            if (timeLimit) timeLimit.value = nonScormData.time_limit || "";

            // Set content type specific fields with error checking
            const setFieldValue = (id, value) => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = value || "";
                } else {
                    console.warn(`Element with ID '${id}' not found`);
                }
            };

            setFieldValue("nonscorm_content_url", nonScormData.content_url);
            setFieldValue("html5_framework", nonScormData.html5_framework);
            setFieldValue("flash_version", nonScormData.flash_version);
            setFieldValue("flash_security", nonScormData.flash_security);
            setFieldValue("unity_version", nonScormData.unity_version);
            setFieldValue("unity_platform", nonScormData.unity_platform);
            setFieldValue("unity_compression", nonScormData.unity_compression);
            setFieldValue("web_technologies", nonScormData.web_technologies);
            setFieldValue("browser_requirements", nonScormData.browser_requirements);
            setFieldValue("external_dependencies", nonScormData.external_dependencies);
            setFieldValue("mobile_platform", nonScormData.mobile_platform);
            setFieldValue("app_store_url", nonScormData.app_store_url);
            setFieldValue("minimum_os_version", nonScormData.minimum_os_version);
            setFieldValue("completion_criteria", nonScormData.completion_criteria);
            setFieldValue("scoring_method", nonScormData.scoring_method);
            setFieldValue("bandwidth_requirement", nonScormData.bandwidth_requirement);
            setFieldValue("screen_resolution", nonScormData.screen_resolution);

            // Set existing file paths in hidden fields
            document.getElementById("existing_content_package").value = nonScormData.content_package || "";
            document.getElementById("existing_launch_file").value = nonScormData.launch_file || "";
            document.getElementById("existing_thumbnail_image").value = nonScormData.thumbnail_image || "";
            document.getElementById("existing_manifest_file").value = nonScormData.manifest_file || "";

            // Show existing file previews
            if (nonScormData.content_package) {
                createExistingFilePreview(nonScormData.content_package, document.getElementById('contentPackagePreview'));
            }

            if (nonScormData.launch_file) {
                createExistingFilePreview(nonScormData.launch_file, document.getElementById('launchFilePreview'));
            }

            if (nonScormData.thumbnail_image) {
                createExistingFilePreview(nonScormData.thumbnail_image, document.getElementById('thumbnailImagePreview'));
            }

            if (nonScormData.manifest_file) {
                createExistingFilePreview(nonScormData.manifest_file, document.getElementById('manifestFilePreview'));
            }

            // Pre-select radio buttons with error checking
            if (mobileSupport && mobileSupport.length > 0) {
                mobileSupport.forEach(radio => {
                    if (radio.value === nonScormData.mobile_support) {
                        radio.checked = true;
                    }
                });
            }

            if (responsiveDesign && responsiveDesign.length > 0) {
                responsiveDesign.forEach(radio => {
                    if (radio.value === nonScormData.responsive_design) {
                        radio.checked = true;
                    }
                });
            }

            if (offlineSupport && offlineSupport.length > 0) {
                offlineSupport.forEach(radio => {
                    if (radio.value === nonScormData.offline_support) {
                        radio.checked = true;
                    }
                });
            }

            if (progressTracking && progressTracking.length > 0) {
                progressTracking.forEach(radio => {
                    if (radio.value === nonScormData.progress_tracking) {
                        radio.checked = true;
                    }
                });
            }

            if (assessmentIntegration && assessmentIntegration.length > 0) {
                assessmentIntegration.forEach(radio => {
                    if (radio.value === nonScormData.assessment_integration) {
                        radio.checked = true;
                    }
                });
            }

            // Pre-fill Tags with error checking
            if (nonScormTagContainer) {
                nonScormTagContainer.innerHTML = "";
                nonScormTags = [];
                if (nonScormData.tags) {
                    nonScormData.tags.split(",").forEach(tag => addNonScormTag(tag.trim()));
                }
            }

            // Change modal title and show modal
            document.getElementById("nonScormModalLabel").textContent = "Edit Non-SCORM Package";
            const modal = new bootstrap.Modal(document.getElementById("nonScormModal"));
            modal.show();
        });
    });

    // ✅ Clear Form Function
    function clearNonScormForm() {
        if (nonScormForm) {
            nonScormForm.reset();

            // Clear tags
            nonScormTags = [];
            updateNonScormTagDisplay();
            updateNonScormHiddenTagList();

            // Clear file previews
            document.querySelectorAll('#contentPackagePreview, #launchFilePreview, #thumbnailImagePreview, #manifestFilePreview').forEach(container => {
                container.innerHTML = "";
            });

            // Clear hidden fields - use correct ID
            const nonScormIdField = document.getElementById("non_scorm_id") || document.getElementById("nonscorm_id");
            if (nonScormIdField) nonScormIdField.value = "";

            document.getElementById("existing_content_package").value = "";
            document.getElementById("existing_launch_file").value = "";
            document.getElementById("existing_thumbnail_image").value = "";
            document.getElementById("existing_manifest_file").value = "";
            
            // Hide content type specific fields
            document.querySelectorAll('.html5-fields, .flash-fields, .unity-fields, .custom-web-fields, .mobile-app-fields').forEach(field => {
                field.style.display = 'none';
            });
            
            // Reset Radio Buttons to Default
            document.querySelector('input[name="nonscorm_mobileSupport"][value="No"]').checked = true;
            document.querySelector('input[name="nonscorm_responsive_design"][value="Yes"]').checked = true;
            document.querySelector('input[name="nonscorm_offline_support"][value="No"]').checked = true;
            document.querySelector('input[name="nonscorm_progress_tracking"][value="Yes"]').checked = true;
            document.querySelector('input[name="nonscorm_assessment_integration"][value="No"]').checked = true;
            
            // Reset modal title
            document.getElementById("nonScormModalLabel").textContent = "Add Non-SCORM Package";
        }
    }

    // ✅ Clear Form Button Event Listener
    const clearNonScormFormBtn = document.getElementById("clearNonScormForm");
    if (clearNonScormFormBtn) {
        clearNonScormFormBtn.addEventListener("click", function () {
            clearNonScormForm();
            const modal = bootstrap.Modal.getInstance(document.getElementById("nonScormModal"));
            if (modal) {
                modal.hide();
            }
        });
    }

    // ✅ Add Button Event Listener
    const addNonScormBtn = document.getElementById("addNonScormBtn");
    if (addNonScormBtn) {
        addNonScormBtn.addEventListener("click", function () {
            clearNonScormForm();
        });
    }

    // Make removeNonScormTag globally accessible
    window.removeNonScormTag = removeNonScormTag;
});
