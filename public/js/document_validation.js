document.addEventListener("DOMContentLoaded", function () {
    console.log("Document Validation Script Loaded!");

    const documentForm = document.getElementById("documentForm");

    if (!documentForm) {
        console.error("Document Form NOT found!");
        return;
    }

    // Attach validation on form submission
    documentForm.addEventListener("submit", function (event) {
        event.preventDefault();
        let isValid = validateDocumentForm();
        if (isValid) {
            console.log("Document Form is valid! Submitting...");
            documentForm.submit();
        }
    });

    // Attach blur validation to fields
    document.querySelectorAll("#documentForm input, #documentForm select, #documentForm textarea").forEach(field => {
        field.addEventListener("blur", function () {
            validateDocumentField(this);
        });
    });

    // Validate Entire Form
    function validateDocumentForm() {
        let isValid = true;
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

            case "documentFile":
                if (field.files.length === 0) {
                    showError(field, "validation.document_file_required");
                    isValid = false;
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


