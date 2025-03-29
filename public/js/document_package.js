document.addEventListener("DOMContentLoaded", function () {
    const documentModal = new bootstrap.Modal(document.getElementById("documentModal"));
    const documentForm = document.getElementById("documentForm");
    const documentCategory = document.getElementById("documentCategory");
    const cancelButton = document.getElementById("cancelForm");

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

        // Clear validation messages and invalid fields
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));

        toggleCategoryFields();
        document.getElementById("documentModalLabel").textContent = translations["document.modal.add"] || "Add Document";

        document.querySelector('input[name="mobileSupport"][value="No"]').checked = true;

        documentModal.show();
    });

    // ✅ Open Modal for Editing Document
    const editButtons = document.querySelectorAll(".edit-document");

    editButtons.forEach(editButton => {
        editButton.addEventListener("click", function () {
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

                // ✅ Handle Mobile Support Selection
                document.querySelector(`input[name="mobile_support"][value="${documentData.mobile_support}"]`).checked = true;

                // ✅ Display existing file names
                document.getElementById("documentFileWordExcelPpt").textContent = documentData.word_excel_ppt_file || "No file selected";
                document.getElementById("documentFileEbookManual").textContent = documentData.ebook_manual_file || "No file selected";
                document.getElementById("documentFileResearch").textContent = documentData.research_file || "No file selected";

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
