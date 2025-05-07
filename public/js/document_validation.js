document.addEventListener("DOMContentLoaded", function () {
    console.log("Document Validation Script Loaded!");

    const documentModal = document.getElementById("documentModal");
    const documentForm = document.getElementById("documentForm");
    const tagInput = document.getElementById("documentTagInput");
    const hiddenTagList = document.getElementById("documentTagList");

    // Show validation setup when modal is shown
    $('#documentModal').on('shown.bs.modal', function () {
        attachDocumentValidation();
    });

    // Cleanup when modal is closed
    $('#documentModal').on('hidden.bs.modal', function () {
        resetDocumentForm();
        $(this).modal('hide');

        // Remove lingering modal-open class and restore scroll
        $('body').removeClass('modal-open');
        document.body.style.overflow = 'auto';

        // Remove backdrop if exists
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    });

    // Attach validation to form
    function attachDocumentValidation() {
        if (!documentForm) return;

        documentForm.removeEventListener("submit", documentFormSubmitHandler);
        documentForm.addEventListener("submit", documentFormSubmitHandler);

        document.querySelectorAll("#documentForm input, #documentForm select, #documentForm textarea").forEach(field => {
            field.removeEventListener("blur", documentFieldBlurHandler);
            field.addEventListener("blur", documentFieldBlurHandler);
        });

        if (tagInput) {
            tagInput.removeEventListener("blur", validateTagInput);
            tagInput.addEventListener("blur", validateTagInput);
        }
    }

    function documentFormSubmitHandler(event) {
        event.preventDefault();
        if (validateDocumentForm()) {
            console.log("Document form valid. Submitting...");
            documentForm.submit();
        }
    }

    function documentFieldBlurHandler(event) {
        validateDocumentField(event.target);
    }

    function validateDocumentForm() {
        let isValid = true;
        const selectedCategory = document.getElementById("documentCategory").value.trim();

        if (selectedCategory === "") {
            showError(document.getElementById("documentCategory"), "validation.document_category_required");
            isValid = false;
        } else {
            hideError(document.getElementById("documentCategory"));
        }

        document.querySelectorAll("#documentForm input, #documentForm select, #documentForm textarea").forEach(field => {
            if (!validateDocumentField(field)) {
                isValid = false;
            }
        });

        if (!validateTagInput()) {
            isValid = false;
        }

        return isValid;
    }

    function validateDocumentField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;
        const selectedCategory = document.getElementById("documentCategory").value.trim();

        switch (name) {
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

            case "documentFileWordExcelPpt":
            case "documentFileEbookManual":
            case "documentFileResearch":
                let isFileRequired =
                    (name === "documentFileWordExcelPpt" && (selectedCategory === translations["document.category.word_excel_ppt"] || selectedCategory === "Word/Excel/PPT Files")) ||
                    (name === "documentFileEbookManual" && (selectedCategory === translations["document.category.ebook_manual"] || selectedCategory === "E-Book & Manual")) ||
                    (name === "documentFileResearch" && (selectedCategory === translations["document.category.research_paper"] || selectedCategory === "Research Paper & Case Studies"));

                if (isFileRequired) {
                    if (field.files.length === 0) {
                        showError(field, "validation.document_file_required");
                        isValid = false;
                    } else {
                        const file = field.files[0];
                        const fileName = file.name.toLowerCase();
                        const fileSize = file.size;
                        const maxSize = 10 * 1024 * 1024;
                        const allowedExtensions = ["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "epub", "mobi"];
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
                    hideError(field);
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
                if (!validateTagInput()) {
                    isValid = false;
                }
                break;
        }

        return isValid;
    }

    function validateTagInput() {
        const tags = hiddenTagList.value.split(",").filter(Boolean);
        if (tags.length === 0) {
            showError(tagInput, "validation.tags_required");
            return false;
        } else {
            hideError(tagInput);
            return true;
        }
    }

    function showError(input, messageKey) {
        const message = translations[messageKey] || messageKey;
        let errorElement = input.parentNode.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.classList.add("error-message");
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.color = "red";
        errorElement.style.fontSize = "12px";
        input.classList.add("is-invalid");
    }

    function hideError(input) {
        const errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) errorElement.textContent = "";
        input.classList.remove("is-invalid");
    }

    function resetDocumentForm() {
        documentForm.reset();
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));
        hiddenTagList.value = "";

        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
});
