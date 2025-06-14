document.addEventListener("DOMContentLoaded", function () {
    const scormModal = new bootstrap.Modal(document.getElementById("scormModal")); // Bootstrap Modal
    const scormForm = document.getElementById("scormForm");
    const scormTitle = document.getElementById("scorm_title");
    const zipFile = document.getElementById("zipFile");
    const version = document.getElementById("version");
    const language = document.getElementById("language");
    const scormCategory = document.getElementById("scormCategory");
    const description = document.getElementById("description");
    const timeLimit = document.getElementById("timeLimit");
    const scormId = document.getElementById("scorm_id"); // Hidden input
    const existingZip = document.getElementById("existing_zip");
    const zipPreview = document.getElementById("scormZipPreview");
    const mobileSupport = document.getElementsByName("mobileSupport");
    const assessment = document.getElementsByName("assessment");

    // Tag Elements
    const tagInput = document.getElementById("tagInput");
    const tagContainer = document.getElementById("tagDisplay");
    const hiddenTagList = document.getElementById("tagList");

    let tags = []; // Store tags

    // Function to Create and Display Tags
    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return; // Prevent empty/duplicate tags

        tags.push(tagText); // Add tag to array

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;

        tagContainer.appendChild(tagElement);
        updateHiddenInput();
    }

    // Function to Remove a Tag
    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText); // Remove only the clicked tag
        updateHiddenInput();

        // Remove tag from display
        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    // Function to Update Hidden Input with Tags
    function updateHiddenInput() {
        hiddenTagList.value = tags.join(",");
    }

    // Listen for Enter Key in the Input Field
    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = ""; // Clear input after adding
        }
    });

    // Listen for Clicks on Remove Buttons
    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    // Remove Last Tag when Pressing Backspace in an Empty Input
    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]); // Remove last tag
        }
    });

    // Open Modal for Editing SCORM
    document.querySelectorAll(".edit-scorm").forEach(button => {
        button.addEventListener("click", function () {
            const scormData = JSON.parse(this.dataset.scorm); // Get data from button attribute

            scormId.value = scormData.id; // Set ID for edit
            scormTitle.value = scormData.title;
            version.value = scormData.version;
            language.value = scormData.language;
            scormCategory.value = scormData.scorm_category;
            description.value = scormData.description;
            timeLimit.value = scormData.time_limit;
            existingZip.value = scormData.zip_file;

            // Debug logging
            console.log('🔍 SCORM Edit Mode - Setting existing ZIP file:', scormData.zip_file);
            console.log('🔍 existingZip field value:', existingZip.value);

            // Display Existing ZIP File Preview
            if (scormData.zip_file) {
                showExistingScormFilePreview(scormData.zip_file);
            } else {
                zipPreview.innerHTML = "";
            }

            // Pre-select Mobile Support
            mobileSupport.forEach(radio => {
                if (radio.value === scormData.mobile_support) {
                    radio.checked = true;
                }
            });

            // Pre-select Assessment
            assessment.forEach(radio => {
                if (radio.value === scormData.assessment) {
                    radio.checked = true;
                }
            });

            // Pre-fill Tags
            tagContainer.innerHTML = ""; // Clear existing tags
            tags = []; // Reset tag array
            if (scormData.tags) {
                scormData.tags.split(",").forEach(tag => addTag(tag.trim()));
            }

            document.getElementById("scormModalLabel").textContent = "Edit SCORM Package"; // Change modal title

            // Show that ZIP file is optional when editing
            const zipRequired = document.getElementById("zipRequired");
            const zipOptional = document.getElementById("zipOptional");
            if (zipRequired) zipRequired.style.display = "none";
            if (zipOptional) zipOptional.style.display = "inline";

            scormModal.show(); // Open modal
        });
    });

    // Open Modal for Adding New SCORM
    const addScormBtn = document.getElementById("addScormBtn");
    if (addScormBtn) {
        addScormBtn.addEventListener("click", function () {
            // Reset Form Fields
            scormForm.reset();
            scormId.value = "";

            // Clear Tags
            tags = [];
            tagContainer.innerHTML = "";
            hiddenTagList.value = "";

            // Clear Existing ZIP File Preview
            existingZip.value = "";
            zipPreview.innerHTML = "";

            // 🚀 **Do NOT Reset Radio Buttons - Keep Default "No" Selected**
            document.querySelector('input[name="mobileSupport"][value="No"]').checked = true;
            document.querySelector('input[name="assessment"][value="No"]').checked = true;

            // Set Modal Title to "Add SCORM Package"
            document.getElementById("scormModalLabel").textContent = "Add SCORM Package";

            // Show that ZIP file is required when adding new
            const zipRequired = document.getElementById("zipRequired");
            const zipOptional = document.getElementById("zipOptional");
            if (zipRequired) zipRequired.style.display = "inline";
            if (zipOptional) zipOptional.style.display = "none";

            scormModal.show();
        });
    }

    // ✅ File Preview Functions - Consistent with Other Modules

    // Add file change listener for new uploads
    zipFile.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            showNewScormFilePreview(file);
        }
    });

    // Show preview for existing files (when editing)
    function showExistingScormFilePreview(fileName) {
        if (!fileName) return;

        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        // File preview with download link and remove button
        previewHTML = `
            <div class="preview-wrapper">
                <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa; position: relative;">
                    <i class="fas fa-file-archive" style="font-size: 24px; color: #6a0dad;"></i>
                    <button type="button" class="remove-preview" onclick="removeScormFilePreview()">×</button>
                </div>
                <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">
                    Current file: <a href="uploads/scorm/${fileName}" target="_blank">${fileName}</a>
                </p>
            </div>
        `;

        zipPreview.innerHTML = previewHTML;
    }

    // Show preview for new file uploads
    function showNewScormFilePreview(file) {
        const fileExtension = file.name.split('.').pop().toLowerCase();
        let previewHTML = '';

        // File preview for new upload
        previewHTML = `
            <div class="preview-wrapper">
                <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #e8f5e8; position: relative;">
                    <i class="fas fa-file-archive" style="font-size: 24px; color: #6a0dad;"></i>
                    <button type="button" class="remove-preview" onclick="clearScormFileInput()">×</button>
                </div>
                <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">
                    New file: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                </p>
            </div>
        `;

        zipPreview.innerHTML = previewHTML;
    }

    // Global function to remove existing file preview
    window.removeScormFilePreview = function() {
        zipPreview.innerHTML = '';
        existingZip.value = '';
    };

    // Global function to clear new file input
    window.clearScormFileInput = function() {
        zipFile.value = '';
        zipPreview.innerHTML = '';
    };

    // Add form submission logging
    scormForm.addEventListener('submit', function(e) {
        console.log('🔍 SCORM Form Submission Debug:');
        console.log('- SCORM ID (edit mode):', scormId.value);
        console.log('- Existing ZIP file:', existingZip.value);
        console.log('- New ZIP file selected:', zipFile.files.length > 0 ? zipFile.files[0].name : 'None');
        console.log('- Form action:', this.action);

        // Check if we're in edit mode and no new file is selected
        if (scormId.value && zipFile.files.length === 0) {
            console.log('✅ Edit mode with no new file - existing ZIP should be preserved:', existingZip.value);
        } else if (scormId.value && zipFile.files.length > 0) {
            console.log('✅ Edit mode with new file - ZIP will be replaced');
        } else if (!scormId.value && zipFile.files.length > 0) {
            console.log('✅ Add mode with new file');
        } else if (!scormId.value && zipFile.files.length === 0) {
            console.log('❌ Add mode with no file - this should show validation error');
        }
    });
});