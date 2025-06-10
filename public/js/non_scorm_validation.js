// ✅ Non-SCORM Package Validation JavaScript

document.addEventListener("DOMContentLoaded", function () {
    const nonScormForm = document.getElementById("nonScormForm");

    if (nonScormForm) {
        nonScormForm.addEventListener("submit", function (e) {
            if (!validateNonScormForm()) {
                e.preventDefault();
            }
        });
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
    }

    function hideError(element) {
        if (!element) return;
        
        element.classList.remove('is-invalid');
        const existingError = element.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
    }

    function clearAllErrors() {
        document.querySelectorAll('.error-message').forEach(error => error.remove());
        document.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
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
});
