// ✅ Non-SCORM Package Validation JavaScript

document.addEventListener("DOMContentLoaded", function () {
    const nonScormForm = document.getElementById("nonScormForm");
    let isSubmitting = false;

    if (nonScormForm) {
        nonScormForm.addEventListener("submit", function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            
            if (!validateNonScormForm()) {
                e.preventDefault();
                return false;
            }
            
            // If validation passes, allow submission but prevent double-submit
            isSubmitting = true;
            this.querySelectorAll('button[type="submit"]').forEach(btn => btn.disabled = true);
            
            // Re-enable after a delay in case of errors
            setTimeout(() => {
                isSubmitting = false;
                this.querySelectorAll('button[type="submit"]').forEach(btn => btn.disabled = false);
            }, 5000); // 5 second timeout
        });

        // Add focus out validation for real-time feedback
        addBlurValidation();
        
        // Add dynamic field validation for content type specific fields
        addDynamicFieldValidation();
        
        // Initial check for form validity and submit button state
        setTimeout(() => {
            if (typeof checkFormValidityAndUpdateSubmitButton === 'function') {
                checkFormValidityAndUpdateSubmitButton();
            }
        }, 100);
    }

    function validateNonScormForm() {
        let isValid = true;

        // Get form elements
        const title = document.getElementById("non_scorm_title");
        const contentType = document.getElementById("nonscorm_content_type");
        const version = document.getElementById("nonscorm_version");
        const tagList = document.getElementById("nonscormTagList");
        const timeLimit = document.getElementById("nonscorm_timeLimit");
        const contentUrl = document.getElementById("nonscorm_content_url");
        const appStoreUrl = document.getElementById("app_store_url");

        // Clear previous errors
        clearAllErrors();

        // Validate Title
        if (!title || !title.value.trim()) {
            showError(title, translate('js.validation.title_required') || 'Title is required.');
            isValid = false;
        } else if (title.value.trim().length < 3) {
            showError(title, translate('js.validation.title_min_length') || 'Title must be at least 3 characters long.');
            isValid = false;
        } else {
            hideError(title);
        }

        // Validate Content Type
        if (!contentType || !contentType.value) {
            showError(contentType, translate('js.validation.content_type_required') || 'Content type is required.');
            isValid = false;
        } else {
            hideError(contentType);
        }

        // Validate Version
        if (!version || !version.value.trim()) {
            showError(version, translate('js.validation.version_required') || 'Version is required.');
            isValid = false;
        } else {
            hideError(version);
        }

        // Validate Tags
        if (!tagList || !tagList.value.trim()) {
            showError(document.getElementById("nonscormTagInput"), translate('js.validation.tags_required') || 'At least one tag is required.');
            isValid = false;
        } else {
            hideError(document.getElementById("nonscormTagInput"));
        }

        // Validate Time Limit (if provided)
        if (timeLimit && timeLimit.value !== "" && (!isNumeric(timeLimit.value) || parseInt(timeLimit.value) < 0)) {
            showError(timeLimit, translate('js.validation.time_limit_numeric') || 'Time limit must be a valid positive number.');
            isValid = false;
        } else if (timeLimit) {
            hideError(timeLimit);
        }

        // Validate Content URL (if provided)
        if (contentUrl && contentUrl.value.trim() && !isValidUrl(contentUrl.value.trim())) {
            showError(contentUrl, translate('js.validation.invalid_url') || 'Please enter a valid URL (e.g., https://example.com).');
            isValid = false;
        } else if (contentUrl) {
            hideError(contentUrl);
        }

        // Validate App Store URL (if provided)
        if (appStoreUrl && appStoreUrl.value.trim() && !isValidUrl(appStoreUrl.value.trim())) {
            showError(appStoreUrl, translate('js.validation.invalid_url') || 'Please enter a valid URL (e.g., https://apps.apple.com/...).');
            isValid = false;
        } else if (appStoreUrl) {
            hideError(appStoreUrl);
        }

        // Content Type Specific Validations
        if (contentType && contentType.value) {
            switch (contentType.value) {
                case 'html5':
                    isValid = validateHtml5Fields() && isValid;
                    break;
                case 'flash':
                    isValid = validateFlashFields() && isValid;
                    break;
                case 'unity':
                    isValid = validateUnityFields() && isValid;
                    break;
                case 'custom_web':
                    isValid = validateCustomWebFields() && isValid;
                    break;
                case 'mobile_app':
                    isValid = validateMobileAppFields() && isValid;
                    break;
            }
        }

        // File Upload Validations
        isValid = validateFileUploads() && isValid;

        return isValid;
    }

    function addBlurValidation() {
        // Get form elements for blur validation
        const title = document.getElementById("non_scorm_title");
        const contentType = document.getElementById("nonscorm_content_type");
        const version = document.getElementById("nonscorm_version");
        const tagList = document.getElementById("nonscormTagList");
        const timeLimit = document.getElementById("nonscorm_timeLimit");
        const contentUrl = document.getElementById("nonscorm_content_url");
        const appStoreUrl = document.getElementById("app_store_url");
        const flashVersion = document.getElementById("flash_version");
        const unityVersion = document.getElementById("unity_version");
        const minimumOsVersion = document.getElementById("minimum_os_version");

        // Add blur event listeners for real-time validation
        if (title) {
            title.addEventListener("blur", function() {
                validateTitle(title);
            });
            // Add input event for real-time validation
            title.addEventListener("input", function() {
                if (this.value.trim().length >= 3) {
                    hideError(this);
                }
            });
        }

        if (contentType) {
            contentType.addEventListener("blur", function() {
                validateContentType(contentType);
            });
            // Add change event for real-time validation
            contentType.addEventListener("change", function() {
                if (this.value) {
                    hideError(this);
                }
            });
        }

        if (version) {
            version.addEventListener("blur", function() {
                validateVersion(version);
            });
            // Add input event for real-time validation
            version.addEventListener("input", function() {
                if (this.value.trim()) {
                    hideError(this);
                }
            });
        }

        if (tagList) {
            tagList.addEventListener("blur", function() {
                validateTags();
            });
        }

        // Also add blur validation to the tag input field
        const tagInput = document.getElementById("nonscormTagInput");
        if (tagInput) {
            tagInput.addEventListener("blur", function() {
                validateTags();
            });
            // Add input event for real-time validation
            tagInput.addEventListener("input", function() {
                const hiddenTagList = document.getElementById("nonscormTagList");
                if (this.value.trim() || (hiddenTagList && hiddenTagList.value.trim())) {
                    hideError(this);
                }
            });
        }

        if (timeLimit) {
            timeLimit.addEventListener("blur", function() {
                validateTimeLimit(timeLimit);
            });
            // Add input event for real-time validation
            timeLimit.addEventListener("input", function() {
                if (this.value === "" || (isNumeric(this.value) && parseInt(this.value) >= 0)) {
                    hideError(this);
                }
            });
        }

        if (contentUrl) {
            contentUrl.addEventListener("blur", function() {
                validateContentUrl(contentUrl);
            });
            // Add input event for real-time validation
            contentUrl.addEventListener("input", function() {
                if (this.value.trim() === "" || isValidUrl(this.value.trim())) {
                    hideError(this);
                }
            });
        }

        if (appStoreUrl) {
            appStoreUrl.addEventListener("blur", function() {
                validateAppStoreUrl(appStoreUrl);
            });
            // Add input event for real-time validation
            appStoreUrl.addEventListener("input", function() {
                if (this.value.trim() === "" || isValidUrl(this.value.trim())) {
                    hideError(this);
                }
            });
        }

        if (flashVersion) {
            flashVersion.addEventListener("blur", function() {
                validateFlashVersion(flashVersion);
            });
            // Add input event for real-time validation
            flashVersion.addEventListener("input", function() {
                if (this.value.trim() === "" || /^\d+(\.\d+)*$/.test(this.value.trim())) {
                    hideError(this);
                }
            });
        }

        if (unityVersion) {
            unityVersion.addEventListener("blur", function() {
                validateUnityVersion(unityVersion);
            });
            // Add input event for real-time validation
            unityVersion.addEventListener("input", function() {
                if (this.value.trim() === "" || /^\d{4}\.\d+\.\d+[a-z]\d+$/i.test(this.value.trim())) {
                    hideError(this);
                }
            });
        }

        if (minimumOsVersion) {
            minimumOsVersion.addEventListener("blur", function() {
                validateMinimumOsVersion(minimumOsVersion);
            });
            // Add input event for real-time validation
            minimumOsVersion.addEventListener("input", function() {
                if (this.value.trim() === "" || /^(iOS|Android)\s+\d+(\.\d+)*$/i.test(this.value.trim())) {
                    hideError(this);
                }
            });
        }
    }

    function addDynamicFieldValidation() {
        // Add validation for content type specific fields that appear dynamically
        const contentType = document.getElementById("nonscorm_content_type");
        if (contentType) {
            contentType.addEventListener("change", function() {
                // Add validation for fields that appear based on content type
                setTimeout(() => {
                    const flashVersion = document.getElementById("flash_version");
                    const unityVersion = document.getElementById("unity_version");
                    const minimumOsVersion = document.getElementById("minimum_os_version");
                    
                    if (flashVersion) {
                        flashVersion.addEventListener("input", function() {
                            if (this.value.trim() === "" || /^\d+(\.\d+)*$/.test(this.value.trim())) {
                                hideError(this);
                            }
                        });
                    }
                    
                    if (unityVersion) {
                        unityVersion.addEventListener("input", function() {
                            if (this.value.trim() === "" || /^\d{4}\.\d+\.\d+[a-z]\d+$/i.test(this.value.trim())) {
                                hideError(this);
                            }
                        });
                    }
                    
                    if (minimumOsVersion) {
                        minimumOsVersion.addEventListener("input", function() {
                            if (this.value.trim() === "" || /^(iOS|Android)\s+\d+(\.\d+)*$/i.test(this.value.trim())) {
                                hideError(this);
                            }
                        });
                    }
                }, 100); // Small delay to ensure fields are rendered
            });
        }
    }

    // Individual validation functions for blur events
    function validateTitle(title) {
        if (!title || !title.value.trim()) {
            showError(title, translate('js.validation.title_required') || 'Title is required.');
            return false;
        } else if (title.value.trim().length < 3) {
            showError(title, translate('js.validation.title_min_length') || 'Title must be at least 3 characters long.');
            return false;
        } else {
            hideError(title);
            return true;
        }
    }

    function validateContentType(contentType) {
        if (!contentType || !contentType.value) {
            showError(contentType, translate('js.validation.content_type_required') || 'Content type is required.');
            return false;
        } else {
            hideError(contentType);
            return true;
        }
    }

    function validateVersion(version) {
        if (!version || !version.value.trim()) {
            showError(version, translate('js.validation.version_required') || 'Version is required.');
            return false;
        } else {
            hideError(version);
            return true;
        }
    }

    function validateTags() {
        const tagInput = document.getElementById("nonscormTagInput");
        const hiddenTagList = document.getElementById("nonscormTagList");
        
        // Check if either the input field has content OR the hidden tag list has tags
        if ((!tagInput || tagInput.value.trim() === "") && (!hiddenTagList || hiddenTagList.value.trim() === "")) {
            showError(tagInput, translate('js.validation.tags_required') || 'At least one tag is required.');
            return false;
        } else {
            hideError(tagInput);
            return true;
        }
    }

    function validateTimeLimit(timeLimit) {
        if (timeLimit && timeLimit.value !== "" && (!isNumeric(timeLimit.value) || parseInt(timeLimit.value) < 0)) {
            showError(timeLimit, translate('js.validation.time_limit_numeric') || 'Time limit must be a valid positive number.');
            return false;
        } else if (timeLimit) {
            hideError(timeLimit);
            return true;
        }
        return true;
    }

    function validateContentUrl(contentUrl) {
        if (contentUrl && contentUrl.value.trim() && !isValidUrl(contentUrl.value.trim())) {
            showError(contentUrl, translate('js.validation.invalid_url') || 'Please enter a valid URL (e.g., https://example.com).');
            return false;
        } else if (contentUrl) {
            hideError(contentUrl);
            return true;
        }
        return true;
    }

    function validateAppStoreUrl(appStoreUrl) {
        if (appStoreUrl && appStoreUrl.value.trim() && !isValidUrl(appStoreUrl.value.trim())) {
            showError(appStoreUrl, translate('js.validation.invalid_url') || 'Please enter a valid URL (e.g., https://apps.apple.com/...).');
            return false;
        } else if (appStoreUrl) {
            hideError(appStoreUrl);
            return true;
        }
        return true;
    }

    function validateFlashVersion(flashVersion) {
        if (flashVersion && flashVersion.value.trim()) {
            const versionPattern = /^\d+(\.\d+)*$/;
            if (!versionPattern.test(flashVersion.value.trim())) {
                showError(flashVersion, translate('js.validation.invalid_flash_version') || 'Please enter a valid Flash version (e.g., 11.2.0).');
                return false;
            } else {
                hideError(flashVersion);
                return true;
            }
        }
        return true;
    }

    function validateUnityVersion(unityVersion) {
        if (unityVersion && unityVersion.value.trim()) {
            const versionPattern = /^\d{4}\.\d+\.\d+[a-z]\d+$/i;
            if (!versionPattern.test(unityVersion.value.trim())) {
                showError(unityVersion, translate('js.validation.invalid_unity_version') || 'Please enter a valid Unity version (e.g., 2022.3.0f1).');
                return false;
            } else {
                hideError(unityVersion);
                return true;
            }
        }
        return true;
    }

    function validateMinimumOsVersion(minimumOsVersion) {
        if (minimumOsVersion && minimumOsVersion.value.trim()) {
            const osVersionPattern = /^(iOS|Android)\s+\d+(\.\d+)*$/i;
            if (!osVersionPattern.test(minimumOsVersion.value.trim())) {
                showError(minimumOsVersion, translate('js.validation.invalid_os_version') || 'Please enter a valid OS version (e.g., iOS 14.0, Android 8.0).');
                return false;
            } else {
                hideError(minimumOsVersion);
                return true;
            }
        }
        return true;
    }

    function validateHtml5Fields() {
        // HTML5 specific validation can be added here
        return true;
    }

    function validateFlashFields() {
        const flashVersion = document.getElementById("flash_version");
        
        if (flashVersion && flashVersion.value.trim()) {
            // Basic version format validation (e.g., 11.2.0)
            const versionPattern = /^\d+(\.\d+)*$/;
            if (!versionPattern.test(flashVersion.value.trim())) {
                showError(flashVersion, translate('js.validation.invalid_flash_version') || 'Please enter a valid Flash version (e.g., 11.2.0).');
                return false;
            } else {
                hideError(flashVersion);
            }
        }
        
        return true;
    }

    function validateUnityFields() {
        const unityVersion = document.getElementById("unity_version");
        
        if (unityVersion && unityVersion.value.trim()) {
            // Basic Unity version format validation (e.g., 2022.3.0f1)
            const versionPattern = /^\d{4}\.\d+\.\d+[a-z]\d+$/i;
            if (!versionPattern.test(unityVersion.value.trim())) {
                showError(unityVersion, translate('js.validation.invalid_unity_version') || 'Please enter a valid Unity version (e.g., 2022.3.0f1).');
                return false;
            } else {
                hideError(unityVersion);
            }
        }
        
        return true;
    }

    function validateCustomWebFields() {
        // Custom web app specific validation can be added here
        return true;
    }

    function validateMobileAppFields() {
        const minimumOsVersion = document.getElementById("minimum_os_version");
        
        if (minimumOsVersion && minimumOsVersion.value.trim()) {
            // Basic OS version validation
            const osVersionPattern = /^(iOS|Android)\s+\d+(\.\d+)*$/i;
            if (!osVersionPattern.test(minimumOsVersion.value.trim())) {
                showError(minimumOsVersion, translate('js.validation.invalid_os_version') || 'Please enter a valid OS version (e.g., iOS 14.0, Android 8.0).');
                return false;
            } else {
                hideError(minimumOsVersion);
            }
        }
        
        return true;
    }

    function validateFileUploads() {
        let isValid = true;
        
        // Validate file sizes and types
        const fileInputs = [
            { element: document.getElementById("content_package"), maxSize: 100 * 1024 * 1024, types: ['.zip', '.rar', '.7z'] },
            { element: document.getElementById("launch_file"), maxSize: 10 * 1024 * 1024, types: ['.html', '.htm', '.swf', '.unity3d'] },
            { element: document.getElementById("nonscorm_thumbnail_image"), maxSize: 10 * 1024 * 1024, types: ['image/'] },
            { element: document.getElementById("manifest_file"), maxSize: 10 * 1024 * 1024, types: ['.xml', '.json', '.txt'] }
        ];

        fileInputs.forEach(input => {
            if (input.element && input.element.files && input.element.files.length > 0) {
                const file = input.element.files[0];
                
                // Check file size
                if (file.size > input.maxSize) {
                    const maxSizeMB = input.maxSize / (1024 * 1024);
                    showError(input.element, `File size must be less than ${maxSizeMB}MB.`);
                    isValid = false;
                } else {
                    hideError(input.element);
                }
                
                // Check file type
                const fileName = file.name.toLowerCase();
                const fileType = file.type.toLowerCase();
                let validType = false;
                
                input.types.forEach(type => {
                    if (type.startsWith('.') && fileName.endsWith(type)) {
                        validType = true;
                    } else if (!type.startsWith('.') && fileType.startsWith(type)) {
                        validType = true;
                    }
                });
                
                if (!validType) {
                    showError(input.element, `Invalid file type. Allowed types: ${input.types.join(', ')}`);
                    isValid = false;
                }
            }
        });
        
        return isValid;
    }

    // Utility Functions
    function isNumeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    }

    function isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === "http:" || url.protocol === "https:";
        } catch (_) {
            return false;
        }
    }

    function showError(element, message) {
        if (!element) return;
        
        hideError(element);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message text-danger mt-1';
        errorDiv.textContent = message;
        errorDiv.style.fontSize = '0.875rem';
        
        element.classList.add('is-invalid');
        element.parentNode.appendChild(errorDiv);
        
        // Check form validity and update submit button state
        if (typeof checkFormValidityAndUpdateSubmitButton === 'function') {
            checkFormValidityAndUpdateSubmitButton();
        }
    }

    function hideError(element) {
        if (!element) return;
        
        element.classList.remove('is-invalid');
        const existingError = element.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Check form validity and update submit button state
        if (typeof checkFormValidityAndUpdateSubmitButton === 'function') {
            checkFormValidityAndUpdateSubmitButton();
        }
    }

    function clearAllErrors() {
        document.querySelectorAll('.error-message').forEach(error => error.remove());
        document.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
        
        // Check form validity and update submit button state
        if (typeof checkFormValidityAndUpdateSubmitButton === 'function') {
            checkFormValidityAndUpdateSubmitButton();
        }
    }

    // Translation function (fallback if not available)
    function translate(key) {
        // Use global translation function if available
        if (typeof window.translate === 'function') {
            return window.translate(key);
        }

        // Use global translations object if available
        if (typeof window.translations === 'object' && window.translations[key]) {
            return window.translations[key];
        }

        // Fallback messages for non-SCORM validation
        const fallbacks = {
            'js.validation.version_required': 'Version is required.',
            'js.validation.content_type_required': 'Content type is required.',
            'js.validation.nonscorm_title_required': 'Non-SCORM title is required.',
            'js.validation.nonscorm_tags_required': 'Tags are required.',
            'js.validation.nonscorm_file_required': 'File is required.',
            'js.validation.nonscorm_url_required': 'URL is required.',
            'js.validation.nonscorm_url_invalid': 'Please enter a valid URL (e.g., https://example.com).',
            'js.validation.flash_version_required': 'Flash version is required.',
            'js.validation.unity_version_required': 'Unity version is required.',
            'js.validation.mobile_platform_required': 'Mobile platform is required.',
            'js.validation.app_store_url_invalid': 'Please enter a valid App Store URL.',
            'js.validation.minimum_os_version_required': 'Minimum OS version is required.'
        };

        return fallbacks[key] || key;
    }

    // Function to check form validity and enable/disable submit buttons
    function checkFormValidityAndUpdateSubmitButton() {
        const nonScormForm = document.getElementById("nonScormForm");
        if (!nonScormForm) return;

        // Check if there are any validation errors
        const hasErrors = nonScormForm.querySelectorAll('.is-invalid').length > 0;
        const hasErrorMessages = nonScormForm.querySelectorAll('.error-message').length > 0;

        // Get submit buttons
        const submitButtons = nonScormForm.querySelectorAll('button[type="submit"]');
        
        if (hasErrors || hasErrorMessages) {
            // Disable submit buttons if there are errors
            submitButtons.forEach(btn => {
                btn.disabled = true;
            });
        } else {
            // Enable submit buttons if no errors
            submitButtons.forEach(btn => {
                btn.disabled = false;
            });
        }
    }

    // Make the function globally accessible
    window.checkFormValidityAndUpdateSubmitButton = checkFormValidityAndUpdateSubmitButton;
    
    // Function to reset submission state
    function resetNonScormSubmissionState() {
        isSubmitting = false;
        if (nonScormForm) {
            nonScormForm.querySelectorAll('button[type="submit"]').forEach(btn => {
                btn.disabled = false;
            });
        }
    }
    
    // Make the reset function globally accessible
    window.resetNonScormSubmissionState = resetNonScormSubmissionState;
});
