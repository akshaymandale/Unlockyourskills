document.addEventListener("DOMContentLoaded", function () {
    const addUserForm = document.getElementById("addUserForm");

    if (addUserForm) {
        // ✅ Validate on Form Submit
        addUserForm.addEventListener("submit", function (event) {
            event.preventDefault();
            let isValid = validateForm();
            if (isValid) {
                addUserForm.submit();
            }
        });

        // ✅ Validate on Focus Out (Blur Event)
        document.querySelectorAll("#addUserForm input, #addUserForm select").forEach(field => {
            field.addEventListener("blur", function () {
                validateField(field);
            });
        });
    }

    // ✅ Function to Validate Entire Form
    function validateForm() {
        let isValid = true;
        document.querySelectorAll("#addUserForm input, #addUserForm select").forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        return isValid;
    }

    // ✅ Function to Validate a Single Field
    function validateField(field) {
        let isValid = true;
        let value = field.value.trim();
        let fieldName = field.getAttribute("name");

        switch (fieldName) {
            case "full_name":
                if (value === "") {
                    showError(field, "validation.full_name_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "email":
                let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value === "") {
                    showError(field, "validation.email_required");
                    isValid = false;
                } else if (!emailPattern.test(value)) {
                    showError(field, "validation.email_invalid");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "contact_number":
                let contactPattern = /^[0-9]{10}$/;
                if (value === "") {
                    showError(field, "validation.contact_required");
                    isValid = false;
                } else if (!contactPattern.test(value)) {
                    showError(field, "validation.contact_invalid");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "dob":
                let today = new Date().toISOString().split("T")[0];
                if (value === "") {
                    showError(field, "validation.dob_required");
                    isValid = false;
                } else if (value > today) {
                    showError(field, "validation.dob_future");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "user_role":
                if (value === "") {
                    showError(field, "validation.user_role_required");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "profile_expiry":
                let expiryDate = new Date(value);
                let todayDate = new Date();
                if (value !== "" && expiryDate < todayDate) {
                    showError(field, "validation.profile_expiry_invalid");
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "profile_picture":
                if (field.files.length > 0) {
                    let file = field.files[0];
                    let allowedExtensions = ["image/jpeg", "image/png"];
                    let maxSize = 5 * 1024 * 1024; // 5MB

                    if (!allowedExtensions.includes(file.type)) {
                        showError(field, "validation.image_format");
                        isValid = false;
                    } else if (file.size > maxSize) {
                        showError(field, "validation.image_size");
                        isValid = false;
                    } else {
                        hideError(field);
                    }
                }
                break;
        }

        return isValid;
    }

    // ✅ Function to Show Error Beside Label & Add Red Border
    function showError(input, key) {
        let message = translations[key] || key; // Use translation or fallback to key

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

        // Add red border on error
        input.classList.add("input-error");
    }

    // ✅ Function to Hide Error & Remove Red Border
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }

        // Remove red border when valid
        input.classList.remove("input-error");
    }
});
