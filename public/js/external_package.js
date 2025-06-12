document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS Loaded Successfully!");

    // ✅ Selecting necessary DOM elements
    const contentGroups = document.querySelectorAll(".content-group");
    const contentType = document.getElementById("contentType");
    const tagInput = document.getElementById("externalTagInput");
    const tagContainer = document.getElementById("externalTagDisplay");
    const hiddenTagList = document.getElementById("externalTagList");
    const thumbnailPreview = document.getElementById("thumbnailPreview");
    const audioFile = document.getElementById("audioFile");
    const audioUrl = document.getElementById("audioUrl");
    const audioSource = document.getElementById("audioSource");
    const modalTitle = document.getElementById("externalModalLabel");
    const externalForm = document.getElementById("externalContentForm");
    let tags = [];

    // ✅ Mapping of content type values to section IDs
    const contentTypeMap = {
        "youtube-vimeo": "youtubeVimeoFields",
        "linkedin-udemy": "linkedinUdemyFields",
        "web-links-blogs": "webLinksBlogsFields",
        "podcasts-audio": "podcastsAudioFields",
    };

    // ✅ Hide all sections initially
    function hideAllSections() {
        document.querySelectorAll(".content-group").forEach(group => {
            group.style.display = "none";
        });
    }

    // ✅ Show the selected content type section
    function showSelectedSection() {
        hideAllSections(); // Hide all sections first

        const selectedType = contentType.value;
        const sectionId = contentTypeMap[selectedType];

        console.log(`🔍 Checking if section exists: ${sectionId}`);

        const selectedSection = document.getElementById(sectionId);

        if (selectedSection) {
            selectedSection.style.display = "block";
            console.log(`✅ Displaying section: ${sectionId}`);
        } else {
            console.warn(`⚠️ Missing section for content type: ${selectedType}`);
        }

        // ✅ Ensure audio fields toggle properly when Podcasts & Audio is selected
        if (selectedType === "podcasts-audio") {
            toggleAudioFields();
            // ✅ Update asterisks when showing podcasts-audio section
            updateAudioFieldAsterisks();
        }
    }

    // ✅ Attach event listener for content type selection
    contentType.addEventListener("change", showSelectedSection);

    // ✅ Function to toggle audio fields visibility based on selection
    function toggleAudioFields() {
        if (!audioSource || !audioFile || !audioUrl) {
            console.error("❌ Missing audio elements in DOM!");
            return;
        }

        // Set default value if empty
        if (!audioSource.value) {
            audioSource.value = "upload"; // Default to "Upload File"
        }

        if (audioSource.value === "upload") {
            audioFile.parentElement.style.display = "block";
            audioUrl.parentElement.style.display = "none";
        } else {
            audioFile.parentElement.style.display = "none";
            audioUrl.parentElement.style.display = "block";
        }

        // ✅ Update asterisk visibility based on audio source
        updateAudioFieldAsterisks();
    }

    // ✅ Function to update asterisk visibility for audio fields
    function updateAudioFieldAsterisks() {
        const audioFileLabel = document.querySelector('label[for="audioFile"]');
        const audioUrlLabel = document.querySelector('label[for="audioUrl"]');

        if (audioFileLabel && audioUrlLabel) {
            // Get existing asterisks
            const audioFileAsterisk = audioFileLabel.querySelector('.text-danger');
            const audioUrlAsterisk = audioUrlLabel.querySelector('.text-danger');

            if (audioSource.value === "upload") {
                // Show asterisk for audio file, hide for audio URL
                if (audioFileAsterisk) audioFileAsterisk.style.display = 'inline';
                if (audioUrlAsterisk) audioUrlAsterisk.style.display = 'none';
            } else {
                // Show asterisk for audio URL, hide for audio file
                if (audioFileAsterisk) audioFileAsterisk.style.display = 'none';
                if (audioUrlAsterisk) audioUrlAsterisk.style.display = 'inline';
            }
        }
    }

    // ✅ Attach event listener for audio source change
    if (audioSource) {
        audioSource.addEventListener("change", toggleAudioFields);
    }

    // ✅ Initially hide all sections on page load
    hideAllSections();

    // ✅ Functions to handle tag input & display
    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;
        tags.push(tagText);
        updateTagDisplay();
        validateTags();
    }

    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateTagDisplay();
        validateTags();
    }

    function updateTagDisplay() {
        tagContainer.innerHTML = "";
        tags.forEach(tag => {
            const tagElement = document.createElement("span");
            tagElement.classList.add("tag");
            tagElement.innerHTML = `${tag} <button type="button" class="remove-tag" data-tag="${tag}">&times;</button>`;
            tagContainer.appendChild(tagElement);
        });
        hiddenTagList.value = tags.join(",");
    }

    // ✅ Event listeners for handling tag inputs
    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });

    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    tagInput.addEventListener("blur", validateTags);

    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    // ✅ Validation functions
    function validateTags() {
        let tagField = document.getElementById("externalTagList");
        let tagContainer = document.getElementById("externalTagDisplay");
        let tagsValue = tagField.value.trim();

        if (tagsValue === "") {
            showError(tagContainer, "Tags/Keywords are required");
            return false;
        } else {
            hideError(tagContainer);
            return true;
        }
    }

    // ✅ Show error function
    function showError(input, message) {
        let formGroup = input.closest(".form-group");
        if (!formGroup) {
            console.error(`❌ .form-group NOT found for ${input.name}`);
            return;
        }

        let errorElement = formGroup.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("small");
            errorElement.className = "error-message text-danger";
            formGroup.appendChild(errorElement);
        }

        errorElement.textContent = message;
        input.classList.add("is-invalid");
    }

    // ✅ Form validation is handled by external_content_validation.js

    function hideError(input) {
        let formGroup = input.closest(".form-group");
        if (!formGroup) return;

        let errorElement = formGroup.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        input.classList.remove("is-invalid");
    }

    // ✅ Handle modal reset on close
    $("#externalContentModal").on("hidden.bs.modal", function () {
        document.getElementById("externalContentForm").reset();
        hideAllSections();
        tags = [];
        updateTagDisplay();

        // Clear all preview elements
        thumbnailPreview.style.display = "none";
        const thumbnailFileLink = document.getElementById("thumbnailFileLink");
        if (thumbnailFileLink) thumbnailFileLink.innerHTML = '';

        modalTitle.textContent = "Add External Content";
    });

    // ✅ Enhanced thumbnail preview with remove functionality
    document.getElementById("thumbnail").addEventListener("change", function (event) {
        const file = event.target.files[0];
        const previewContainer = document.getElementById("thumbnailFileLink");

        if (file) {
            createFilePreview(file, previewContainer, () => {
                this.value = '';
                previewContainer.innerHTML = '';
                thumbnailPreview.style.display = "none";
            });
        } else {
            previewContainer.innerHTML = '';
            thumbnailPreview.style.display = "none";
        }
    });

    // ✅ File Preview Functions (following Non-SCORM pattern)
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
            img.style.maxHeight = '100px';
            img.style.objectFit = 'cover';
            img.style.border = '1px solid #ddd';
            img.style.borderRadius = '5px';
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
        removeBtn.style.position = 'absolute';
        removeBtn.style.top = '-5px';
        removeBtn.style.right = '-5px';
        removeBtn.style.background = '#dc3545';
        removeBtn.style.color = 'white';
        removeBtn.style.border = 'none';
        removeBtn.style.borderRadius = '50%';
        removeBtn.style.width = '20px';
        removeBtn.style.height = '20px';
        removeBtn.style.fontSize = '12px';
        removeBtn.style.cursor = 'pointer';
        removeBtn.onclick = removeCallback;
        previewWrapper.appendChild(removeBtn);

        previewContainer.innerHTML = '';
        previewContainer.appendChild(previewWrapper);
    }

    function createExistingFilePreview(fileName, previewContainer, uploadPath = 'uploads/external_content/') {
        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExtension)) {
            // Image preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <img src="${uploadPath}${fileName}" alt="Preview" style="max-width: 150px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px;">
                    <button type="button" class="remove-preview" onclick="removeExternalFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                </div>
                <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: ${fileName}</p>
            `;
        } else {
            // File link preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-file" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeExternalFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        }

        previewContainer.innerHTML = previewHTML;
    }

    // Global function to remove file preview
    window.removeExternalFilePreview = function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '';
        }

        // Clear the corresponding file input and hidden field
        if (containerId === 'thumbnailFileLink') {
            const thumbnailInput = document.getElementById('thumbnail');
            if (thumbnailInput) thumbnailInput.value = '';
            const thumbnailPreview = document.getElementById('thumbnailPreview');
            if (thumbnailPreview) thumbnailPreview.style.display = 'none';
        }
    };

    // ✅ Ensure correct audio field visibility on edit
    audioSource.addEventListener("change", function() {
        toggleAudioFields();

        // ✅ Re-validate audio URL field when source changes
        const audioUrlField = document.getElementById("audioUrl");
        if (audioUrlField && typeof validateExternalContentField === 'function') {
            validateExternalContentField(audioUrlField);
        }
    });

    // ✅ Handle edit content modal
    document.querySelectorAll(".edit-content").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            const contentData = JSON.parse(this.getAttribute("data-content"));

            modalTitle.textContent = "Edit External Content";

            // Clear existing previews first
            const thumbnailFileLink = document.getElementById("thumbnailFileLink");
            if (thumbnailFileLink) thumbnailFileLink.innerHTML = '';
            const thumbnailPreview = document.getElementById("thumbnailPreview");
            if (thumbnailPreview) thumbnailPreview.style.display = 'none';

            document.getElementById("external_id").value = contentData.id || "";
            document.getElementById("title").value = contentData.title || "";
            document.getElementById("versionNumber").value = contentData.version_number || "";
            document.getElementById("languageSupport").value = contentData.language_support || "English";
            document.getElementById("external_timeLimit").value = contentData.time_limit || "";
            document.getElementById("external_description").value = contentData.description || "";
            document.getElementById("contentType").value = contentData.content_type || "";

            showSelectedSection(); // Ensure correct section is shown

            tags = contentData.tags ? contentData.tags.split(",") : [];
            updateTagDisplay();

            // ✅ Update asterisks after setting audio source value
            setTimeout(() => {
                updateAudioFieldAsterisks();
            }, 100);

            document.getElementById("videoUrl").value = contentData.video_url || "";
            document.getElementById("courseUrl").value = contentData.course_url || "";
            document.getElementById("platformName").value = contentData.platform_name || "";
            document.getElementById("articleUrl").value = contentData.article_url || "";
            document.getElementById("author").value = contentData.author || "";
            document.getElementById("audioSource").value = contentData.audio_source || "upload";
            document.getElementById("audioUrl").value = contentData.audio_url || "";

            // ✅ Show existing file previews (following Non-SCORM pattern)
            if (contentData.thumbnail && thumbnailFileLink) {
                createExistingFilePreview(contentData.thumbnail, thumbnailFileLink);
            }

            // Set mobile support radio buttons
            const mobileSupport = document.querySelectorAll('input[name="mobile_support"]');
            mobileSupport.forEach(radio => {
                if (radio.value === contentData.mobile_support) {
                    radio.checked = true;
                }
            });

            $("#externalContentModal").modal("show");
        });
    });
});
