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

    // ✅ Clear Existing File Display on New Upload
    function clearExistingFile(fieldId, hiddenInput, displayElement) {
        document.getElementById(fieldId).addEventListener("change", function () {
            hiddenInput.value = "";
            displayElement.innerHTML = "";
            displayElement.style.display = "none"; // Hide empty file display
        });
    }

    clearExistingFile("documentFileWordExcelPpt", existingWordExcelPpt, wordExcelPptDisplay);
    clearExistingFile("documentFileEbookManual", existingEbookManual, ebookManualDisplay);
    clearExistingFile("documentFileResearch", existingResearch, researchDisplay);

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
    
                // ✅ Display Existing Files and Show Sections if Files Exist
                if (documentData.word_excel_ppt_file) {
                    wordExcelPptDisplay.innerHTML = `Current File: <a href="uploads/documents/${documentData.word_excel_ppt_file}" target="_blank">${documentData.word_excel_ppt_file}</a>`;
                    wordExcelPptDisplay.style.display = "block"; // Show only if file exists
                }
    
                if (documentData.ebook_manual_file) {
                    ebookManualDisplay.innerHTML = `Current File: <a href="uploads/documents/${documentData.ebook_manual_file}" target="_blank">${documentData.ebook_manual_file}</a>`;
                    ebookManualDisplay.style.display = "block"; // Show only if file exists
                }
    
                if (documentData.research_file) {
                    researchDisplay.innerHTML = `Current File: <a href="uploads/documents/${documentData.research_file}" target="_blank">${documentData.research_file}</a>`;
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

});
