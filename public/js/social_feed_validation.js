/**
 * Social Feed Validation JavaScript
 * Handles client-side validation for social feed forms
 */

document.addEventListener("DOMContentLoaded", function () {
    console.log("Social Feed Validation Script Loaded!");

    const createPostModal = document.getElementById("createPostModal");
    const createPostForm = document.getElementById("createPostForm");
    const editPostModal = document.getElementById("editPostModal");
    const editPostForm = document.getElementById("editPostForm");
    const reportModal = document.getElementById("reportModal");
    const reportForm = document.getElementById("reportForm");

    // Attach validation when create post modal opens
    $('#createPostModal').on('shown.bs.modal', function () {
        clearAllValidationErrors();
        attachCreatePostValidation();
    });

    // Attach validation when edit post modal opens
    $('#editPostModal').on('shown.bs.modal', function () {
        clearAllValidationErrors();
        attachEditPostValidation();
    });

    // Attach validation when report modal opens
    $('#reportModal').on('shown.bs.modal', function () {
        clearAllValidationErrors();
        attachReportValidation();
    });

    // Reset forms when modals are hidden
    $('#createPostModal').on('hidden.bs.modal', function () {
        resetCreatePostForm();
    });

    $('#editPostModal').on('hidden.bs.modal', function () {
        resetEditPostForm();
    });

    $('#reportModal').on('hidden.bs.modal', function () {
        resetReportForm();
    });

    // Also try to set up validation immediately if forms exist
    attachCreatePostValidation();
    attachEditPostValidation();
    attachReportValidation();

    /**
     * Attach validation to create post form
     */
    function attachCreatePostValidation() {
        if (!createPostForm) return;

        console.log("✅ Create post form found, setting up validation");

        // Remove existing event listeners to prevent duplicates
        createPostForm.removeEventListener("submit", createPostFormSubmitHandler);
        createPostForm.addEventListener("submit", createPostFormSubmitHandler);

        // Add blur validation to all form fields
        document.querySelectorAll("#createPostForm input, #createPostForm select, #createPostForm textarea").forEach(field => {
            field.removeEventListener("blur", createPostFieldBlurHandler);
            field.addEventListener("blur", createPostFieldBlurHandler);
        });

        // Add input validation for character counter
        const postContent = document.getElementById("postContent");
        if (postContent) {
            postContent.removeEventListener("input", validatePostContentLength);
            postContent.addEventListener("input", validatePostContentLength);
        }

        // Add input validation for title character counter
        const postTitle = document.getElementById("postTitle");
        if (postTitle) {
            postTitle.removeEventListener("input", validatePostTitleLength);
            postTitle.addEventListener("input", validatePostTitleLength);
        }

        // Add validation for poll options
        const pollSection = document.getElementById("pollSection");
        if (pollSection) {
            const observer = new MutationObserver(() => {
                attachPollOptionValidation();
            });
            observer.observe(pollSection, { childList: true, subtree: true });
        }

        // Add validation for media files
        const mediaFiles = document.getElementById("mediaFiles");
        if (mediaFiles) {
            mediaFiles.removeEventListener("change", validateMediaFiles);
            mediaFiles.addEventListener("change", validateMediaFiles);
        }

        // Add validation for link fields
        const linkUrl = document.getElementById("linkUrl");
        if (linkUrl) {
            linkUrl.removeEventListener("blur", validateLinkUrl);
            linkUrl.addEventListener("blur", validateLinkUrl);
        }

        const linkTitle = document.getElementById("linkTitle");
        if (linkTitle) {
            linkTitle.removeEventListener("blur", validateLinkTitle);
            linkTitle.addEventListener("blur", validateLinkTitle);
        }

        const linkDescription = document.getElementById("linkDescription");
        if (linkDescription) {
            linkDescription.removeEventListener("blur", validateLinkDescription);
            linkDescription.addEventListener("blur", validateLinkDescription);
        }
    }

    /**
     * Attach validation to edit post form
     */
    function attachEditPostValidation() {
        if (!editPostForm) return;

        console.log("✅ Edit post form found, setting up validation");

        editPostForm.removeEventListener("submit", editPostFormSubmitHandler);
        editPostForm.addEventListener("submit", editPostFormSubmitHandler);

        document.querySelectorAll("#editPostForm input, #editPostForm select, #editPostForm textarea").forEach(field => {
            field.removeEventListener("blur", editPostFieldBlurHandler);
            field.addEventListener("blur", editPostFieldBlurHandler);
        });

        // Add input validation for title character counter
        const editPostTitle = document.getElementById("editPostTitle");
        if (editPostTitle) {
            editPostTitle.removeEventListener("input", validateEditPostTitleLength);
            editPostTitle.addEventListener("input", validateEditPostTitleLength);
        }

        // Add input validation for content character counter
        const editPostContent = document.getElementById("editPostContent");
        if (editPostContent) {
            editPostContent.removeEventListener("input", validateEditPostContentLength);
            editPostContent.addEventListener("input", validateEditPostContentLength);
        }

        // Add validation for edit link fields
        const editLinkUrl = document.getElementById("editLinkUrl");
        if (editLinkUrl) {
            editLinkUrl.removeEventListener("blur", validateEditLinkUrl);
            editLinkUrl.addEventListener("blur", validateEditLinkUrl);
        }

        const editLinkTitle = document.getElementById("editLinkTitle");
        if (editLinkTitle) {
            editLinkTitle.removeEventListener("blur", validateEditLinkTitle);
            editLinkTitle.addEventListener("blur", validateEditLinkTitle);
        }

        const editLinkDescription = document.getElementById("editLinkDescription");
        if (editLinkDescription) {
            editLinkDescription.removeEventListener("blur", validateEditLinkDescription);
            editLinkDescription.addEventListener("blur", validateEditLinkDescription);
        }
    }

    /**
     * Attach validation to report form
     */
    function attachReportValidation() {
        if (!reportForm) return;

        console.log("✅ Report form found, setting up validation");

        reportForm.removeEventListener("submit", reportFormSubmitHandler);
        reportForm.addEventListener("submit", reportFormSubmitHandler);

        document.querySelectorAll("#reportForm input, #reportForm select, #reportForm textarea").forEach(field => {
            field.removeEventListener("blur", reportFieldBlurHandler);
            field.addEventListener("blur", reportFieldBlurHandler);
        });
    }

    /**
     * Create post form submit handler
     */
    function createPostFormSubmitHandler(e) {
        let isValid = validateCreatePostForm();
        if (isValid) {
            console.log("Create post form valid. Submitting...");
            // Let the existing social feed handler process the submission
            return true;
        } else {
            console.log("❌ Create post validation failed");
            e.preventDefault(); // Only prevent default if validation fails
            if (typeof window.showToast === 'function') {
                window.showToast.error(translate('js.validation.fix_errors_before_submit') || 'Please fix the errors before submitting.');
            }
            return false;
        }
    }

    /**
     * Edit post form submit handler
     */
    function editPostFormSubmitHandler(e) {
        let isValid = validateEditPostForm();
        if (isValid) {
            console.log("Edit post form valid. Submitting...");
            // Let the existing social feed handler process the submission
            return true;
        } else {
            console.log("❌ Edit post validation failed");
            e.preventDefault(); // Only prevent default if validation fails
            if (typeof window.showToast === 'function') {
                window.showToast.error(translate('js.validation.fix_errors_before_submit') || 'Please fix the errors before submitting.');
            }
            return false;
        }
    }

    /**
     * Report form submit handler
     */
    function reportFormSubmitHandler(e) {
        let isValid = validateReportForm();
        if (isValid) {
            console.log("Report form valid. Submitting...");
            // Let the existing social feed handler process the submission
            return true;
        } else {
            console.log("❌ Report validation failed");
            e.preventDefault(); // Only prevent default if validation fails
            if (typeof window.showToast === 'function') {
                window.showToast.error(translate('js.validation.fix_errors_before_submit') || 'Please fix the errors before submitting.');
            }
            return false;
        }
    }

    /**
     * Create post field blur handler
     */
    function createPostFieldBlurHandler(e) {
        validateCreatePostField(e.target);
    }

    /**
     * Edit post field blur handler
     */
    function editPostFieldBlurHandler(e) {
        validateEditPostField(e.target);
    }

    /**
     * Report field blur handler
     */
    function reportFieldBlurHandler(e) {
        validateReportField(e.target);
    }

    /**
     * Validate create post form
     */
    function validateCreatePostForm() {
        let isValid = true;

        const fields = [
            "postTitle",
            "postContent",
            "post_type",
            "visibility"
        ];

        fields.forEach(id => {
            const field = document.getElementById(id);
            if (field && !validateCreatePostField(field)) {
                isValid = false;
            }
        });

        // Validate tags
        if (!validateTags()) {
            isValid = false;
        }

        // Validate poll if included
        const includePoll = document.getElementById("includePoll");
        if (includePoll && includePoll.checked) {
            if (!validatePollOptions()) {
                isValid = false;
            }
        }

        // Validate schedule date
        if (!validateScheduleDate()) {
            isValid = false;
        }

        // Validate media files
        if (!validateMediaFiles()) {
            isValid = false;
        }

        // Validate link fields if post type is 'link'
        const postType = document.getElementById("post_type");
        if (postType && postType.value === 'link') {
            if (!validateLinkFields()) {
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Validate edit post form
     */
    function validateEditPostForm() {
        let isValid = true;

        const fields = [
            "editPostTitle",
            "editPostContent",
            "editPostType",
            "editVisibility"
        ];

        fields.forEach(id => {
            const field = document.getElementById(id);
            if (field && !validateEditPostField(field)) {
                isValid = false;
            }
        });

        // Validate tags
        if (!validateTags()) {
            isValid = false;
        }

        // Validate poll if included
        const includePoll = document.getElementById("editIncludePoll");
        if (includePoll && includePoll.checked) {
            if (!validatePollOptions()) {
                isValid = false;
            }
        }

        // Validate schedule date
        if (!validateScheduleDate()) {
            isValid = false;
        }

        // Validate media files
        if (!validateMediaFiles()) {
            isValid = false;
        }

        // Validate link fields if post type is 'link'
        const postType = document.getElementById("editPostType");
        if (postType && postType.value === 'link') {
            if (!validateEditLinkFields()) {
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Validate report form
     */
    function validateReportForm() {
        let isValid = true;

        // Only validate required fields
        const requiredFields = [
            "reportReason"
        ];

        requiredFields.forEach(id => {
            const field = document.getElementById(id);
            if (field && !validateReportField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Validate create post field
     */
    function validateCreatePostField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;

        switch (name) {
            case "postTitle":
                if (value === "") {
                    showError(field, translate('js.validation.post_title_required') || 'Post title is required.');
                    isValid = false;
                } else if (value.length > 150) {
                    showError(field, translate('js.validation.post_title_too_long') || 'Post title cannot exceed 150 characters.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "postContent":
                if (value === "") {
                    showError(field, translate('js.validation.post_content_required') || 'Post content is required.');
                    isValid = false;
                } else if (value.length > 2000) {
                    showError(field, translate('js.validation.post_content_too_long') || 'Post content cannot exceed 2000 characters.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "post_type":
                if (value === "") {
                    showError(field, translate('js.validation.category_required') || 'Category is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "visibility":
                if (value === "") {
                    showError(field, translate('js.validation.visibility_required') || 'Visibility is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            default:
                hideError(field);
        }

        return isValid;
    }

    /**
     * Validate edit post field
     */
    function validateEditPostField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;

        switch (name) {
            case "editPostTitle":
                if (value === "") {
                    showError(field, translate('js.validation.post_title_required') || 'Post title is required.');
                    isValid = false;
                } else if (value.length > 150) {
                    showError(field, translate('js.validation.post_title_too_long') || 'Post title cannot exceed 150 characters.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "editPostContent":
                if (value === "") {
                    showError(field, translate('js.validation.post_content_required') || 'Post content is required.');
                    isValid = false;
                } else if (value.length > 2000) {
                    showError(field, translate('js.validation.post_content_too_long') || 'Post content cannot exceed 2000 characters.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "editPostType":
                if (value === "") {
                    showError(field, translate('js.validation.category_required') || 'Category is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "editVisibility":
                if (value === "") {
                    showError(field, translate('js.validation.visibility_required') || 'Visibility is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            default:
                hideError(field);
        }

        return isValid;
    }

    /**
     * Validate report field
     */
    function validateReportField(field) {
        if (!field) return true;

        const name = field.getAttribute("id");
        const value = field.value.trim();
        let isValid = true;

        switch (name) {
            case "reportReason":
                if (value === "") {
                    showError(field, translate('js.validation.report_reason_required') || 'Report reason is required.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            case "reportDetails":
                // Report details is optional (Additional Details)
                if (value.length > 500) {
                    showError(field, translate('js.validation.report_details_too_long') || 'Report details cannot exceed 500 characters.');
                    isValid = false;
                } else {
                    hideError(field);
                }
                break;

            default:
                hideError(field);
        }

        return isValid;
    }

    /**
     * Validate post content length
     */
    function validatePostContentLength(e) {
        const content = e.target.value;
        const maxLength = 2000;
        const currentLength = content.length;
        
        // Update character counter
        const counter = document.getElementById("charCounter");
        if (counter) {
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.className = currentLength > maxLength ? "text-danger" : "text-muted";
        }

        // Validate length
        if (currentLength > maxLength) {
            showError(e.target, translate('js.validation.post_content_too_long') || 'Post content cannot exceed 2000 characters.');
            return false;
        } else {
            hideError(e.target);
            return true;
        }
    }

    /**
     * Validate post title length
     */
    function validatePostTitleLength(e) {
        const title = e.target.value;
        const maxLength = 150;
        const currentLength = title.length;
        
        // Update character counter
        const counter = document.getElementById("titleCharCounter");
        if (counter) {
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.className = currentLength > maxLength ? "text-danger" : "text-muted";
        }

        // Validate length
        if (currentLength > maxLength) {
            showError(e.target, translate('js.validation.post_title_too_long') || 'Post title cannot exceed 150 characters.');
            return false;
        } else {
            hideError(e.target);
            return true;
        }
    }

    /**
     * Validate edit post title length
     */
    function validateEditPostTitleLength(e) {
        const title = e.target.value;
        const maxLength = 150;
        const currentLength = title.length;
        
        // Update character counter
        const counter = document.getElementById("editTitleCharCounter");
        if (counter) {
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.className = currentLength > maxLength ? "text-danger" : "text-muted";
        }

        // Validate length
        if (currentLength > maxLength) {
            showError(e.target, translate('js.validation.post_title_too_long') || 'Post title cannot exceed 150 characters.');
            return false;
        } else {
            hideError(e.target);
            return true;
        }
    }

    /**
     * Validate edit post content length
     */
    function validateEditPostContentLength(e) {
        const content = e.target.value;
        const maxLength = 2000;
        const currentLength = content.length;
        
        // Update character counter
        const counter = document.getElementById("editCharCounter");
        if (counter) {
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.className = currentLength > maxLength ? "text-danger" : "text-muted";
        }

        // Validate length
        if (currentLength > maxLength) {
            showError(e.target, translate('js.validation.post_content_too_long') || 'Post content cannot exceed 2000 characters.');
            return false;
        } else {
            hideError(e.target);
            return true;
        }
    }

    /**
     * Validate poll options
     */
    function validatePollOptions() {
        const pollOptions = document.querySelectorAll('input[name="poll_options[]"]');
        let validOptions = 0;

        pollOptions.forEach(option => {
            if (option.value.trim() !== "") {
                validOptions++;
            }
        });

        if (validOptions < 2) {
            const pollSection = document.getElementById("pollSection");
            showPollError(translate('js.validation.poll_minimum_options') || 'Poll must have at least 2 options.');
            return false;
        } else {
            hidePollError();
            return true;
        }
    }

    /**
     * Validate schedule date
     */
    function validateScheduleDate() {
        const schedulePost = document.getElementById("schedulePost");
        const scheduleDateTime = document.getElementById("scheduleDateTime");
        
        if (!schedulePost || !scheduleDateTime) {
            return true; // Schedule fields not found, skip validation
        }

        if (schedulePost.checked) {
            if (!scheduleDateTime.value.trim()) {
                showScheduleError(translate('js.validation.schedule_date_required') || 'Please select a date and time for scheduling.');
                return false;
            } else {
                const scheduledDate = new Date(scheduleDateTime.value);
                const now = new Date();
                
                if (scheduledDate <= now) {
                    showScheduleError(translate('js.validation.schedule_date_future') || 'Scheduled date must be in the future.');
                    return false;
                }
            }
        }

        hideScheduleError();
        return true;
    }

    /**
     * Validate media files
     */
    function validateMediaFiles() {
        const mediaFiles = document.getElementById("mediaFiles");
        if (!mediaFiles || !mediaFiles.files.length) {
            return true; // No files selected is valid
        }

        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/', 'video/', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        for (let i = 0; i < mediaFiles.files.length; i++) {
            const file = mediaFiles.files[i];
            
            if (file.size > maxSize) {
                showMediaError(translate('js.validation.file_too_large') || 'File too large. Maximum size is 10MB.');
                return false;
            }

            const isValidType = allowedTypes.some(type => file.type.startsWith(type) || file.type === type);
            if (!isValidType) {
                showMediaError(translate('js.validation.invalid_file_type') || 'Invalid file type. Please upload images, videos, or documents.');
                return false;
            }
        }

        hideMediaError();
        return true;
    }

    /**
     * Validate tags
     */
    function validateTags() {
        const tagList = document.getElementById("tagList");
        const tagInput = document.getElementById("tagInput");
        
        if (!tagList || !tagInput) {
            return true; // Tags field not found, skip validation
        }

        const tags = tagList.value.split(',').filter(tag => tag.trim() !== '');
        
        // Check if any tag is too long
        for (let tag of tags) {
            if (tag.trim().length > 50) {
                showError(tagInput, translate('js.validation.tag_too_long') || 'Each tag cannot exceed 50 characters.');
                return false;
            }
        }

        // Check if too many tags
        if (tags.length > 10) {
            showError(tagInput, translate('js.validation.too_many_tags') || 'Maximum 10 tags allowed.');
            return false;
        }

        hideError(tagInput);
        return true;
    }

    /**
     * Validate link fields for create form
     */
    function validateLinkFields() {
        let isValid = true;
        
        const linkUrl = document.getElementById("linkUrl");
        const linkTitle = document.getElementById("linkTitle");
        const linkDescription = document.getElementById("linkDescription");
        
        // Validate URL (required)
        if (linkUrl) {
            const urlValue = linkUrl.value.trim();
            if (urlValue === "") {
                showError(linkUrl, translate('js.validation.link_url_required') || 'URL is required for link posts.');
                isValid = false;
            } else if (!isValidUrl(urlValue)) {
                showError(linkUrl, translate('js.validation.invalid_url') || 'Please enter a valid URL including http:// or https://');
                isValid = false;
            } else {
                hideError(linkUrl);
            }
        }
        
        // Validate title (optional but if provided, check length)
        if (linkTitle && linkTitle.value.trim() !== "") {
            const titleValue = linkTitle.value.trim();
            if (titleValue.length > 200) {
                showError(linkTitle, translate('js.validation.link_title_too_long') || 'Link title cannot exceed 200 characters.');
                isValid = false;
            } else {
                hideError(linkTitle);
            }
        }
        
        // Validate description (optional but if provided, check length)
        if (linkDescription && linkDescription.value.trim() !== "") {
            const descValue = linkDescription.value.trim();
            if (descValue.length > 500) {
                showError(linkDescription, translate('js.validation.link_description_too_long') || 'Link description cannot exceed 500 characters.');
                isValid = false;
            } else {
                hideError(linkDescription);
            }
        }
        
        return isValid;
    }

    /**
     * Validate link fields for edit form
     */
    function validateEditLinkFields() {
        let isValid = true;
        
        const linkUrl = document.getElementById("editLinkUrl");
        const linkTitle = document.getElementById("editLinkTitle");
        const linkDescription = document.getElementById("editLinkDescription");
        
        // Validate URL (required)
        if (linkUrl) {
            const urlValue = linkUrl.value.trim();
            if (urlValue === "") {
                showError(linkUrl, translate('js.validation.link_url_required') || 'URL is required for link posts.');
                isValid = false;
            } else if (!isValidUrl(urlValue)) {
                showError(linkUrl, translate('js.validation.invalid_url') || 'Please enter a valid URL including http:// or https://');
                isValid = false;
            } else {
                hideError(linkUrl);
            }
        }
        
        // Validate title (optional but if provided, check length)
        if (linkTitle && linkTitle.value.trim() !== "") {
            const titleValue = linkTitle.value.trim();
            if (titleValue.length > 200) {
                showError(linkTitle, translate('js.validation.link_title_too_long') || 'Link title cannot exceed 200 characters.');
                isValid = false;
            } else {
                hideError(linkTitle);
            }
        }
        
        // Validate description (optional but if provided, check length)
        if (linkDescription && linkDescription.value.trim() !== "") {
            const descValue = linkDescription.value.trim();
            if (descValue.length > 500) {
                showError(linkDescription, translate('js.validation.link_description_too_long') || 'Link description cannot exceed 500 characters.');
                isValid = false;
            } else {
                hideError(linkDescription);
            }
        }
        
        return isValid;
    }

    /**
     * Validate URL format
     */
    function isValidUrl(string) {
        try {
            const url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }

    /**
     * Validate link URL field
     */
    function validateLinkUrl(e) {
        const field = e.target;
        const value = field.value.trim();
        
        // Only validate if post type is 'link'
        const postType = document.getElementById("post_type");
        if (!postType || postType.value !== 'link') {
            hideError(field);
            return true;
        }
        
        if (value === "") {
            showError(field, translate('js.validation.link_url_required') || 'URL is required for link posts.');
            return false;
        } else if (!isValidUrl(value)) {
            showError(field, translate('js.validation.invalid_url') || 'Please enter a valid URL including http:// or https://');
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    /**
     * Validate link title field
     */
    function validateLinkTitle(e) {
        const field = e.target;
        const value = field.value.trim();
        
        // Only validate if post type is 'link' and value is provided
        const postType = document.getElementById("post_type");
        if (!postType || postType.value !== 'link' || value === "") {
            hideError(field);
            return true;
        }
        
        if (value.length > 200) {
            showError(field, translate('js.validation.link_title_too_long') || 'Link title cannot exceed 200 characters.');
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    /**
     * Validate link description field
     */
    function validateLinkDescription(e) {
        const field = e.target;
        const value = field.value.trim();
        
        // Only validate if post type is 'link' and value is provided
        const postType = document.getElementById("post_type");
        if (!postType || postType.value !== 'link' || value === "") {
            hideError(field);
            return true;
        }
        
        if (value.length > 500) {
            showError(field, translate('js.validation.link_description_too_long') || 'Link description cannot exceed 500 characters.');
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    /**
     * Validate edit link URL field
     */
    function validateEditLinkUrl(e) {
        const field = e.target;
        const value = field.value.trim();
        
        // Only validate if post type is 'link'
        const postType = document.getElementById("editPostType");
        if (!postType || postType.value !== 'link') {
            hideError(field);
            return true;
        }
        
        if (value === "") {
            showError(field, translate('js.validation.link_url_required') || 'URL is required for link posts.');
            return false;
        } else if (!isValidUrl(value)) {
            showError(field, translate('js.validation.invalid_url') || 'Please enter a valid URL including http:// or https://');
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    /**
     * Validate edit link title field
     */
    function validateEditLinkTitle(e) {
        const field = e.target;
        const value = field.value.trim();
        
        // Only validate if post type is 'link' and value is provided
        const postType = document.getElementById("editPostType");
        if (!postType || postType.value !== 'link' || value === "") {
            hideError(field);
            return true;
        }
        
        if (value.length > 200) {
            showError(field, translate('js.validation.link_title_too_long') || 'Link title cannot exceed 200 characters.');
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    /**
     * Validate edit link description field
     */
    function validateEditLinkDescription(e) {
        const field = e.target;
        const value = field.value.trim();
        
        // Only validate if post type is 'link' and value is provided
        const postType = document.getElementById("editPostType");
        if (!postType || postType.value !== 'link' || value === "") {
            hideError(field);
            return true;
        }
        
        if (value.length > 500) {
            showError(field, translate('js.validation.link_description_too_long') || 'Link description cannot exceed 500 characters.');
            return false;
        } else {
            hideError(field);
            return true;
        }
    }

    /**
     * Attach validation to poll options
     */
    function attachPollOptionValidation() {
        document.querySelectorAll('input[name="poll_options[]"]').forEach(option => {
            option.removeEventListener("blur", validatePollOption);
            option.addEventListener("blur", validatePollOption);
        });
    }

    /**
     * Validate individual poll option
     */
    function validatePollOption(e) {
        const value = e.target.value.trim();
        if (value === "") {
            showError(e.target, translate('js.validation.poll_option_required') || 'Poll option cannot be empty.');
            return false;
        } else {
            hideError(e.target);
            return true;
        }
    }

    /**
     * Show error message
     */
    function showError(input, message) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("span");
            errorElement.classList.add("error-message");
            input.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.color = "red";
        errorElement.style.fontSize = "12px";
        errorElement.style.display = "block";
        input.classList.add("is-invalid");
    }

    /**
     * Hide error message
     */
    function hideError(input) {
        let errorElement = input.parentNode.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
            errorElement.style.display = "none";
        }
        input.classList.remove("is-invalid");
    }

    /**
     * Show poll error
     */
    function showPollError(message) {
        const pollSection = document.getElementById("pollSection");
        if (!pollSection) return;

        let errorElement = pollSection.querySelector(".poll-error-message");
        if (!errorElement) {
            errorElement = document.createElement("div");
            errorElement.classList.add("poll-error-message", "alert", "alert-danger", "mt-2");
            pollSection.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.display = "block";
    }

    /**
     * Hide poll error
     */
    function hidePollError() {
        const pollSection = document.getElementById("pollSection");
        if (!pollSection) return;

        const errorElement = pollSection.querySelector(".poll-error-message");
        if (errorElement) {
            errorElement.style.display = "none";
        }
    }

    /**
     * Show media error
     */
    function showMediaError(message) {
        const mediaSection = document.getElementById("mediaDropZone");
        if (!mediaSection) return;

        let errorElement = mediaSection.parentNode.querySelector(".media-error-message");
        if (!errorElement) {
            errorElement = document.createElement("div");
            errorElement.classList.add("media-error-message", "alert", "alert-danger", "mt-2");
            mediaSection.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.display = "block";
    }

    /**
     * Hide media error
     */
    function hideMediaError() {
        const mediaSection = document.getElementById("mediaDropZone");
        if (!mediaSection) return;

        const errorElement = mediaSection.parentNode.querySelector(".media-error-message");
        if (errorElement) {
            errorElement.style.display = "none";
        }
    }

    /**
     * Show schedule error
     */
    function showScheduleError(message) {
        const scheduleSection = document.getElementById("scheduleDateTime");
        if (!scheduleSection) return;

        let errorElement = scheduleSection.parentNode.querySelector(".schedule-error-message");
        if (!errorElement) {
            errorElement = document.createElement("div");
            errorElement.classList.add("schedule-error-message", "alert", "alert-danger", "mt-2");
            scheduleSection.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
        errorElement.style.display = "block";
    }

    /**
     * Hide schedule error
     */
    function hideScheduleError() {
        const scheduleSection = document.getElementById("scheduleDateTime");
        if (!scheduleSection) return;

        const errorElement = scheduleSection.parentNode.querySelector(".schedule-error-message");
        if (errorElement) {
            errorElement.style.display = "none";
        }
    }

    /**
     * Reset create post form
     */
    function resetCreatePostForm() {
        if (createPostForm) {
            createPostForm.reset();
        }

        // Reset character counter
        const counter = document.getElementById("charCounter");
        if (counter) {
            counter.textContent = "0/2000";
            counter.className = "text-muted";
        }

        // Reset title character counter
        const titleCounter = document.getElementById("titleCharCounter");
        if (titleCounter) {
            titleCounter.textContent = "0/150";
            titleCounter.className = "text-muted";
        }

        // Reset poll section
        const pollSection = document.getElementById("pollSection");
        if (pollSection) {
            pollSection.style.display = "none";
        }

        // Reset schedule section
        const scheduleInput = document.getElementById("scheduleDateTime");
        if (scheduleInput) {
            scheduleInput.style.display = "none";
        }

        // Reset media preview
        const mediaPreview = document.getElementById("mediaPreview");
        if (mediaPreview) {
            mediaPreview.innerHTML = "";
        }

        // Reset tags
        if (window.tags) {
            window.tags = [];
            const tagDisplay = document.getElementById("tagDisplay");
            const tagList = document.getElementById("tagList");
            if (tagDisplay) tagDisplay.innerHTML = "";
            if (tagList) tagList.value = "";
        }

        // Clear all error messages
        document.querySelectorAll("#createPostForm .error-message").forEach(error => {
            error.remove();
        });
        document.querySelectorAll("#createPostForm .is-invalid").forEach(field => {
            field.classList.remove("is-invalid");
        });

        hidePollError();
        hideMediaError();
        hideScheduleError();
    }

    /**
     * Reset edit post form
     */
    function resetEditPostForm() {
        if (editPostForm) {
            editPostForm.reset();
        }

        // Reset character counter
        const counter = document.getElementById("charCounter");
        if (counter) {
            counter.textContent = "0/2000";
            counter.className = "text-muted";
        }

        // Reset title character counter
        const titleCounter = document.getElementById("editTitleCharCounter");
        if (titleCounter) {
            titleCounter.textContent = "0/150";
            titleCounter.className = "text-muted";
        }

        // Reset poll section
        const pollSection = document.getElementById("pollSection");
        if (pollSection) {
            pollSection.style.display = "none";
        }

        // Reset schedule section
        const scheduleInput = document.getElementById("scheduleDateTime");
        if (scheduleInput) {
            scheduleInput.style.display = "none";
        }

        // Reset media preview
        const mediaPreview = document.getElementById("mediaPreview");
        if (mediaPreview) {
            mediaPreview.innerHTML = "";
        }

        // Reset tags
        if (window.tags) {
            window.tags = [];
            const tagDisplay = document.getElementById("tagDisplay");
            const tagList = document.getElementById("tagList");
            if (tagDisplay) tagDisplay.innerHTML = "";
            if (tagList) tagList.value = "";
        }

        // Clear all error messages
        document.querySelectorAll("#editPostForm .error-message").forEach(error => {
            error.remove();
        });
        document.querySelectorAll("#editPostForm .is-invalid").forEach(field => {
            field.classList.remove("is-invalid");
        });

        hidePollError();
        hideMediaError();
        hideScheduleError();
    }

    /**
     * Reset report form
     */
    function resetReportForm() {
        if (reportForm) {
            reportForm.reset();
            document.querySelectorAll("#reportForm .error-message").forEach(el => el.remove());
            document.querySelectorAll("#reportForm .is-invalid").forEach(el => el.classList.remove("is-invalid"));
        }
    }

    /**
     * Clear all validation errors from all forms
     */
    function clearAllValidationErrors() {
        // Clear error messages from all forms
        document.querySelectorAll(".error-message").forEach(error => {
            error.remove();
        });
        
        // Remove invalid class from all fields
        document.querySelectorAll(".is-invalid").forEach(field => {
            field.classList.remove("is-invalid");
        });
        
        // Hide specific error sections
        hidePollError();
        hideMediaError();
        hideScheduleError();
    }

    // Make functions available globally for form validation
    window.attachCreatePostValidation = attachCreatePostValidation;
    window.attachEditPostValidation = attachEditPostValidation;
    window.attachReportValidation = attachReportValidation;
    window.validateCreatePostForm = validateCreatePostForm;
    window.validateEditPostForm = validateEditPostForm;
    window.validateReportForm = validateReportForm;
}); 