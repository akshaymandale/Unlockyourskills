document.addEventListener("DOMContentLoaded", function () {
    const documentModal = new bootstrap.Modal(document.getElementById("documentModal"));
    const documentForm = document.getElementById("documentForm");

    if (!documentForm) {
        console.error("Document Form NOT found!");
        return;
    }

    // Open Modal for Adding New Document
    document.getElementById("addDocumentBtn").addEventListener("click", function () {
        documentForm.reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));

        document.getElementById("documentModalLabel").textContent = translations["document.modal.add"] || "Add Document";
        documentModal.show();
    });

    // Attach validation on form submission
    documentForm.addEventListener("submit", function (event) {
        event.preventDefault();
        let isValid = validateDocumentForm();
        if (isValid) {
            documentForm.submit();
        }
    });

    // Attach blur validation to fields
    document.querySelectorAll("#documentForm input, #documentForm select, #documentForm textarea").forEach(field => {
        field.addEventListener("blur", function () {
            validateDocumentField(this);
        });
    });

    // clear previous slected category validation 
document.getElementById("documentCategory").addEventListener("change", function () {
    // Clear only file input fields and their validation errors
    ["documentFileWordExcelPpt", "documentFileEbookManual", "documentFileResearch"].forEach(fileFieldId => {
        let fileInput = document.getElementById(fileFieldId);
        if (fileInput) {
            fileInput.value = ""; // Clear file input
            hideError(fileInput); // Remove validation error only for file inputs
        }
    });
});

    // Validate Entire Form
    function validateDocumentForm() {
        let isValid = true;
        let selectedCategory = document.getElementById("documentCategory").value;

        // Ensure documentCategory is selected
        if (selectedCategory === "") {
            showError(document.getElementById("documentCategory"), "validation.document_category_required");
            isValid = false;
        } else {
            hideError(document.getElementById("documentCategory"));
        }

        // Validate all fields (to show errors on all missing fields)
        document.querySelectorAll("#documentForm input, #documentForm select, #documentForm textarea").forEach(field => {
            if (!validateDocumentField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    // Validate Single Field
    function validateDocumentField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("id");
        let selectedCategory = document.getElementById("documentCategory").value.trim();

        switch (fieldName) {
            case "document_title":
                if (value === "") {
                    showError(field, "validation.document_title_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "documentCategory":
                if (value === "") {
                    showError(field, "validation.document_category_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            // Validate File Upload for Different Categories
            case "documentFileWordExcelPpt":
                case "documentFileEbookManual":
                case "documentFileResearch":
                    const categoryField = document.getElementById("documentCategory");
                    const selectedCategory = categoryField ? categoryField.value.trim() : "";
                
                    // Determine if file upload is required for the selected category
                    let isFileRequired = 
                        (fieldName === "documentFileWordExcelPpt" && selectedCategory === "Word/Excel/PPT Files") ||
                        (fieldName === "documentFileEbookManual" && selectedCategory === "E-Book & Manual") ||
                        (fieldName === "documentFileResearch" && selectedCategory === "Research Paper & Case Studies");
                
                    if (isFileRequired) {
                        if (field.files.length === 0) {
                            showError(field, "validation.document_file_required");
                            isValid = false;
                        } else {
                            const file = field.files[0];
                            const maxSize = 5 * 1024 * 1024; // 10MB
                            const allowedExtensions = ["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "epub", "mobi"];
                            
                
                            const fileName = file.name.toLowerCase();
                            const fileSize = file.size;
                            const fileExtension = fileName.split('.').pop();
                
                            if (!allowedExtensions.includes(fileExtension)) {
                                showError(field, "validation.invalid_file_format");
                                isValid = false;
                            } else if (fileSize > maxSize) {
                                showError(field, "validation.file_size_exceeded");
                                isValid = false;
                            } else {
                                hideError(field);
                            }
                        }
                    } else {
                        hideError(field); // If file is not required, clear any existing error
                    }
                    break;
                

            case "doc_version":
                if (value === "") {
                    showError(field, "validation.version_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "documentTagInput":
                if (value === "" && document.getElementById("documentTagList").value === "") {
                    showError(field, "validation.tags_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;
        }

        return isValid;
    }

    // Show Error Messages with Translations
    function showError(input, key) {
        let message = translations[key] || key;

        let errorElement = input.parentNode.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.classList.add("error-message");
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.color = "red";
        errorElement.style.marginLeft = "10px";
        errorElement.style.fontSize = "12px";

        input.classList.add("is-invalid");
    }

    // Hide Error Messages
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        input.classList.remove("is-invalid");
    }

    // Reset Form on Modal Close or "Clear" Button Click
    document.getElementById("documentModal").addEventListener("hidden.bs.modal", function () {
        documentForm.reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    });

    document.getElementById("clearForm").addEventListener("click", function () {
        documentForm.reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
    });
});
