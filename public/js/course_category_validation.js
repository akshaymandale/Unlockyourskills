/**
 * Course Categories Validation JavaScript
 * Handles all client-side validation for course categories forms
 */

/**
 * Validate category name
 */
function validateCategoryName(name) {
    if (!name || name.trim() === '') {
        return getTranslation('course_categories.category_name_required');
    }
    
    if (name.trim().length > 100) {
        return getTranslation('course_categories.category_name_too_long');
    }
    
    // Check for valid characters (letters, numbers, spaces, hyphens, underscores)
    const validPattern = /^[a-zA-Z0-9\s\-_]+$/;
    if (!validPattern.test(name.trim())) {
        return 'Category name can only contain letters, numbers, spaces, hyphens, and underscores';
    }
    
    return null; // No error
}

/**
 * Validate category description
 */
function validateCategoryDescription(description) {
    if (description && description.length > 500) {
        return getTranslation('course_categories.description_too_long');
    }
    
    return null; // No error
}

/**
 * Validate sort order
 */
function validateSortOrder(sortOrder) {
    if (sortOrder && (isNaN(sortOrder) || parseInt(sortOrder) < 0)) {
        return getTranslation('course_categories.sort_order_invalid');
    }
    
    return null; // No error
}

/**
 * Validate form fields
 */
function validateCategoryForm() {
    const name = document.getElementById('categoryName').value;
    const description = document.getElementById('categoryDescription').value;
    const sortOrder = document.getElementById('categorySortOrder').value;
    
    let isValid = true;
    let errors = {};
    
    // Validate name
    const nameError = validateCategoryName(name);
    if (nameError) {
        errors.name = nameError;
        isValid = false;
        showCategoryFieldError('categoryName', nameError);
    } else {
        clearCategoryFieldError('categoryName');
    }
    
    // Validate description
    const descriptionError = validateCategoryDescription(description);
    if (descriptionError) {
        errors.description = descriptionError;
        isValid = false;
        showCategoryFieldError('categoryDescription', descriptionError);
    } else {
        clearCategoryFieldError('categoryDescription');
    }
    
    // Validate sort order
    const sortOrderError = validateSortOrder(sortOrder);
    if (sortOrderError) {
        errors.sort_order = sortOrderError;
        isValid = false;
        showCategoryFieldError('categorySortOrder', sortOrderError);
    } else {
        clearCategoryFieldError('categorySortOrder');
    }
    
    return { isValid, errors };
}

/**
 * Show field error
 */
function showCategoryFieldError(fieldId, errorMessage) {
    const field = document.getElementById(fieldId);
    let errorDiv = null;
    
    // Map field IDs to error div IDs
    switch (fieldId) {
        case 'categoryName':
            errorDiv = document.getElementById('nameError');
            break;
        case 'categoryDescription':
            errorDiv = document.getElementById('descriptionError');
            break;
        case 'categorySortOrder':
            errorDiv = document.getElementById('sortOrderError');
            break;
        default:
            errorDiv = document.getElementById(fieldId + 'Error');
    }
    
    if (field) {
        field.classList.add('is-invalid');
    }
    
    if (errorDiv) {
        errorDiv.textContent = errorMessage;
    }
}

/**
 * Clear field error
 */
function clearCategoryFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    let errorDiv = null;
    
    // Map field IDs to error div IDs
    switch (fieldId) {
        case 'categoryName':
            errorDiv = document.getElementById('nameError');
            break;
        case 'categoryDescription':
            errorDiv = document.getElementById('descriptionError');
            break;
        case 'categorySortOrder':
            errorDiv = document.getElementById('sortOrderError');
            break;
        default:
            errorDiv = document.getElementById(fieldId + 'Error');
    }
    
    if (field) {
        field.classList.remove('is-invalid');
    }
    
    if (errorDiv) {
        errorDiv.textContent = '';
    }
}

/**
 * Validate field on focus out
 */
function validateCategoryFieldOnFocusOut(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const value = field.value;
    let error = null;
    
    switch (fieldId) {
        case 'categoryName':
            error = validateCategoryName(value);
            break;
        case 'categoryDescription':
            error = validateCategoryDescription(value);
            break;
        case 'categorySortOrder':
            error = validateSortOrder(value);
            break;
    }
    
    if (error) {
        showCategoryFieldError(fieldId, error);
    } else {
        clearCategoryFieldError(fieldId);
    }
}

/**
 * Clear all validation errors
 */
function clearCategoryValidationErrors() {
    const inputs = document.querySelectorAll('#categoryModal .is-invalid');
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
    });
    
    const errorDivs = document.querySelectorAll('#categoryModal .invalid-feedback');
    errorDivs.forEach(div => {
        div.textContent = '';
    });
}

/**
 * Setup form validation with focus out events
 */
function setupCategoryFormValidation() {
    // Category name validation
    const categoryName = document.getElementById('categoryName');
    if (categoryName) {
        categoryName.addEventListener('blur', function() {
            validateCategoryFieldOnFocusOut('categoryName');
        });
        
        // Clear error on input
        categoryName.addEventListener('input', function() {
            clearCategoryFieldError('categoryName');
        });
    }
    
    // Category description validation
    const categoryDescription = document.getElementById('categoryDescription');
    if (categoryDescription) {
        categoryDescription.addEventListener('blur', function() {
            validateCategoryFieldOnFocusOut('categoryDescription');
        });
        
        // Clear error on input
        categoryDescription.addEventListener('input', function() {
            clearCategoryFieldError('categoryDescription');
        });
    }
    
    // Sort order validation
    const categorySortOrder = document.getElementById('categorySortOrder');
    if (categorySortOrder) {
        categorySortOrder.addEventListener('blur', function() {
            validateCategoryFieldOnFocusOut('categorySortOrder');
        });
        
        // Clear error on input
        categorySortOrder.addEventListener('input', function() {
            clearCategoryFieldError('categorySortOrder');
        });
    }
} 