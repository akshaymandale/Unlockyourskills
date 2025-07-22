document.addEventListener("DOMContentLoaded", function () {
    const documentModal = new bootstrap.Modal(document.getElementById("documentModal"));
    const documentForm = document.getElementById("documentForm");
    const documentCategory = document.getElementById("documentCategory");
    const cancelButton = document.getElementById("cancelForm");
    const wordExcelPptDisplay = document.getElementById("existingDocumentWordExcelPptDisplay");
    const ebookManualDisplay = document.getElementById("existingDocumentEbookManualDisplay");
    const researchDisplay = document.getElementById("existingDocumentResearchDisplay");
    const existingWordExcelPpt = document.getElementById("existingDocumentWordExcelPpt");
    const existingEbookManual = document.getElementById("existingDocumentEbookManual");
    const existingResearch = document.getElementById("existingDocumentResearch");

    // ✅ Tag Elements
    const tagInput = document.getElementById("documentTagInput");
    const tagContainer = document.getElementById("documentTagDisplay");
    const hiddenTagList = document.getElementById("documentTagList");
    let tags = [];

    // ✅ Function to Show or Hide Fields Based on Category Selection
    function toggleCategoryFields() {
        const selectedCategory = documentCategory.value;
        document.getElementById("wordExcelPptFields").style.display = (selectedCategory === "Word/Excel/PPT Files") ? "block" : "none";
        document.getElementById("ebookManualFields").style.display = (selectedCategory === "E-Book & Manual") ? "block" : "none";
        document.getElementById("researchFields").style.display = (selectedCategory === "Research Paper & Case Studies") ? "block" : "none";
        document.getElementById("researchDetails").style.display = (selectedCategory === "Research Paper & Case Studies") ? "flex" : "none";
    }

 // ✅ Open Modal for Adding New Document
 document.getElementById("addDocumentBtn").addEventListener("click", function () {
    documentForm.reset();
    tags = [];
    tagContainer.innerHTML = "";
    hiddenTagList.value = "";

    // ✅ Clear validation messages and ensure they are visible when needed
    document.querySelectorAll(".error-message").forEach(el => {
        el.textContent = "";
        el.style.display = "block"; // Ensure error messages show up on next validation
    });

    document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));

    // ✅ Clear previous file display from edit modal
    [wordExcelPptDisplay, ebookManualDisplay, researchDisplay].forEach(display => {
        display.innerHTML = "";
        display.style.display = "none"; // Hide the empty file display
    });

    // ✅ Clear hidden input values (existing file names)
    ["existingDocumentWordExcelPpt", "existingDocumentEbookManual", "existingDocumentResearch"].forEach(id => {
        let hiddenInput = document.getElementById(id);
        if (hiddenInput) hiddenInput.value = "";
    });

    // ✅ Clear file input fields
    ["documentFileWordExcelPpt", "documentFileEbookManual", "documentFileResearch"].forEach(id => {
        let fileInput = document.getElementById(id);
        if (fileInput) fileInput.value = "";
    });

    toggleCategoryFields();
    document.getElementById("documentModalLabel").textContent = translations["document.modal.add"] || "Add Document";

    document.querySelector('input[name="mobileSupport"][value="No"]').checked = true;

    // ✅ Add file change listeners for new uploads with preview
    function addFilePreviewListener(fieldId, hiddenInput, displayElement) {
        document.getElementById(fieldId).addEventListener("change", function () {
            hiddenInput.value = "";
            if (this.files && this.files[0]) {
                const file = this.files[0];
                showNewDocumentFilePreview(file, displayElement);
            } else {
                displayElement.innerHTML = "";
                displayElement.style.display = "none";
            }
        });
    }

    addFilePreviewListener("documentFileWordExcelPpt", existingWordExcelPpt, wordExcelPptDisplay);
    addFilePreviewListener("documentFileEbookManual", existingEbookManual, ebookManualDisplay);
    addFilePreviewListener("documentFileResearch", existingResearch, researchDisplay);

    documentModal.show();
});



    // ✅ Open Modal for Editing Document
    const editButtons = document.querySelectorAll(".edit-document");

    editButtons.forEach(editButton => {
        editButton.addEventListener("click", function () {
            documentForm.reset(); // Clear previous data
            tags = [];
            tagContainer.innerHTML = "";
            hiddenTagList.value = "";
    
            // ✅ Clear previous validation errors
            document.querySelectorAll(".error-message").forEach(el => {
                el.textContent = "";
                el.style.display = "none"; // Hide validation message container
            });
    
            document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    
            // ✅ Clear previous file selection
            document.querySelectorAll("#documentForm input[type='file']").forEach(fileInput => {
                fileInput.value = "";
            });
    
            // ✅ Clear file display and HIDE sections to remove blank gaps
            [wordExcelPptDisplay, ebookManualDisplay, researchDisplay].forEach(display => {
                display.innerHTML = "";
                display.style.display = "none"; // Hide the section
            });
    
            const documentDataStr = this.getAttribute("data-document");
    
            try {
                const documentData = JSON.parse(documentDataStr);
    
                // ✅ Populate Form Fields
                document.getElementById("documentId").value = documentData.id;
                document.getElementById("document_title").value = documentData.title;
                document.getElementById("document_description").value = documentData.description;
                document.getElementById("documentCategory").value = documentData.category;
                document.getElementById("document_language").value = documentData.language_id;
                document.getElementById("research_authors").value = documentData.authors;
    
                const publicationDateField = document.getElementById("research_publication_date");
                if (publicationDateField) {
                    publicationDateField.value = documentData.publication_date || "";
                }
    
                document.getElementById("research_references").value = documentData.reference_links;
                document.getElementById("doc_version").value = documentData.version_number;
                document.getElementById("doc_time_limit").value = documentData.time_limit;
    
                // ✅ Display Existing Files with Remove Buttons (following Non-SCORM pattern)
                if (documentData.word_excel_ppt_file) {
                    createExistingFilePreview(documentData.word_excel_ppt_file, wordExcelPptDisplay, 'uploads/documents/', 'wordExcelPptDisplay');
                    wordExcelPptDisplay.style.display = "block"; // Show only if file exists
                }

                if (documentData.ebook_manual_file) {
                    createExistingFilePreview(documentData.ebook_manual_file, ebookManualDisplay, 'uploads/documents/', 'ebookManualDisplay');
                    ebookManualDisplay.style.display = "block"; // Show only if file exists
                }

                if (documentData.research_file) {
                    createExistingFilePreview(documentData.research_file, researchDisplay, 'uploads/documents/', 'researchDisplay');
                    researchDisplay.style.display = "block"; // Show only if file exists
                }
    
                existingWordExcelPpt.value = documentData.word_excel_ppt_file || "";
                existingEbookManual.value = documentData.ebook_manual_file || "";
                existingResearch.value = documentData.research_file || "";
    
                // ✅ Handle Mobile Support Selection
                document.querySelector(`input[name="mobile_support"][value="${documentData.mobile_support}"]`).checked = true;
    
                // ✅ Load Tags
                tagContainer.innerHTML = ""; // Clear previous tags
                tags = [];
    
                if (documentData.tags && documentData.tags.trim() !== "") {
                    documentData.tags.split(",").forEach(tag => addTag(tag.trim()));
                }
    
                toggleCategoryFields();
                document.getElementById("documentModalLabel").textContent = translations["document.modal.edit"] || "Edit Document";
                documentModal.show();
            } catch (error) {
                console.error("❌ Error parsing JSON data:", error);
            }
        });
    });
    
    

    // ✅ Listen for Category Change
    documentCategory.addEventListener("change", toggleCategoryFields);

    // ✅ Function to Add Tags
    function addTag(tagName) {
        if (tagName.trim() === "" || tags.includes(tagName)) return; // Prevent empty/duplicate tags

        // ✅ Create a new tag element
        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagName} <button type="button" class="remove-tag" data-tag="${tagName}">&times;</button>`;

        tagContainer.appendChild(tagElement);
        tags.push(tagName);
        updateHiddenInput();
    }

    // ✅ Function to Remove Tags
    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateHiddenInput();

        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    // ✅ Update Hidden Input with Tags
    function updateHiddenInput() {
        hiddenTagList.value = tags.join(",");
    }

    // ✅ Add Tag on Enter
    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });

    // ✅ Remove Last Tag on Backspace
    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    // ✅ Listen for Clicks on Remove Buttons
    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    // ✅ Handle Cancel Button
    cancelButton.addEventListener("click", function () {
        document.querySelectorAll("#documentForm input, #documentForm textarea, #documentForm select").forEach(field => {
            if (field.type !== "radio" && field.type !== "checkbox") {
                field.value = "";
            }
        });

        document.querySelectorAll("#documentForm input[type='file']").forEach(fileInput => {
            fileInput.value = "";
        });

        tags = [];
        tagContainer.innerHTML = "";
        hiddenTagList.value = "";

        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));

        toggleCategoryFields();
    });

    // ✅ Show preview for new document file uploads
    function showNewDocumentFilePreview(file, displayElement) {
        const fileName = file.name;
        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        if (['pdf'].includes(fileExtension)) {
            // PDF preview with remove button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #e8f5e8;">
                        <i class="fas fa-file-pdf" style="font-size: 24px; color: #dc3545;"></i>
                        <button type="button" class="remove-preview" onclick="clearNewDocumentFileInput('${displayElement.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">New file: ${fileName} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                </div>
            `;
        } else if (['doc', 'docx'].includes(fileExtension)) {
            // Word document preview
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #e8f5e8;">
                        <i class="fas fa-file-word" style="font-size: 24px; color: #2b579a;"></i>
                        <button type="button" class="remove-preview" onclick="clearNewDocumentFileInput('${displayElement.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">New file: ${fileName} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                </div>
            `;
        } else if (['xls', 'xlsx'].includes(fileExtension)) {
            // Excel document preview
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #e8f5e8;">
                        <i class="fas fa-file-excel" style="font-size: 24px; color: #217346;"></i>
                        <button type="button" class="remove-preview" onclick="clearNewDocumentFileInput('${displayElement.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">New file: ${fileName} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                </div>
            `;
        } else if (['ppt', 'pptx'].includes(fileExtension)) {
            // PowerPoint document preview
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #e8f5e8;">
                        <i class="fas fa-file-powerpoint" style="font-size: 24px; color: #d24726;"></i>
                        <button type="button" class="remove-preview" onclick="clearNewDocumentFileInput('${displayElement.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">New file: ${fileName} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                </div>
            `;
        } else {
            // Generic file preview
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #e8f5e8;">
                        <i class="fas fa-file" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="clearNewDocumentFileInput('${displayElement.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">New file: ${fileName} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                </div>
            `;
        }

        displayElement.innerHTML = previewHTML;
        displayElement.style.display = 'block';
    }

    // ✅ Global function to clear new document file input
    window.clearNewDocumentFileInput = function(displayElementId) {
        const displayElement = document.getElementById(displayElementId);
        if (displayElement) {
            displayElement.innerHTML = '';
            displayElement.style.display = 'none';
        }

        // Clear the corresponding file input
        if (displayElementId === 'wordExcelPptDisplay') {
            const fileInput = document.getElementById('documentFileWordExcelPpt');
            if (fileInput) fileInput.value = '';
        } else if (displayElementId === 'ebookManualDisplay') {
            const fileInput = document.getElementById('documentFileEbookManual');
            if (fileInput) fileInput.value = '';
        } else if (displayElementId === 'researchDisplay') {
            const fileInput = document.getElementById('documentFileResearch');
            if (fileInput) fileInput.value = '';
        }
    };

    // ✅ File Preview Functions (following Non-SCORM pattern)
    function createExistingFilePreview(fileName, previewContainer, uploadPath = 'uploads/documents/', containerId) {
        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        if (['pdf'].includes(fileExtension)) {
            // PDF preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-file-pdf" style="font-size: 24px; color: #dc3545;"></i>
                        <button type="button" class="remove-preview" onclick="removeDocumentFilePreview('${containerId}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        } else if (['doc', 'docx'].includes(fileExtension)) {
            // Word document preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-file-word" style="font-size: 24px; color: #2b579a;"></i>
                        <button type="button" class="remove-preview" onclick="removeDocumentFilePreview('${containerId}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        } else if (['xls', 'xlsx'].includes(fileExtension)) {
            // Excel document preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-file-excel" style="font-size: 24px; color: #217346;"></i>
                        <button type="button" class="remove-preview" onclick="removeDocumentFilePreview('${containerId}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        } else if (['ppt', 'pptx'].includes(fileExtension)) {
            // PowerPoint document preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-file-powerpoint" style="font-size: 24px; color: #d24726;"></i>
                        <button type="button" class="remove-preview" onclick="removeDocumentFilePreview('${containerId}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        } else {
            // Generic file preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-file" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeDocumentFilePreview('${containerId}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        }

        previewContainer.innerHTML = previewHTML;
    }

    // Global function to remove file preview
    window.removeDocumentFilePreview = function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '';
            container.style.display = 'none';
        }

        // Clear the corresponding file input and hidden field
        if (containerId === 'wordExcelPptDisplay') {
            const fileInput = document.getElementById('documentFileWordExcelPpt');
            if (fileInput) fileInput.value = '';
            const hiddenField = document.getElementById('existingDocumentWordExcelPpt');
            if (hiddenField) hiddenField.value = '';
        } else if (containerId === 'ebookManualDisplay') {
            const fileInput = document.getElementById('documentFileEbookManual');
            if (fileInput) fileInput.value = '';
            const hiddenField = document.getElementById('existingDocumentEbookManual');
            if (hiddenField) hiddenField.value = '';
        } else if (containerId === 'researchDisplay') {
            const fileInput = document.getElementById('documentFileResearch');
            if (fileInput) fileInput.value = '';
            const hiddenField = document.getElementById('existingDocumentResearch');
            if (hiddenField) hiddenField.value = '';
        }
    };

});
