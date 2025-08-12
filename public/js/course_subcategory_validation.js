/**
 * Course Subcategories Validation JavaScript
 * Handles all client-side validation for course subcategories forms
 */

/**
 * Validate subcategory name
 */
function validateSubcategoryName(name) {
    if (!name || name.trim() === '') {
        return getTranslation('course_subcategories.subcategory_name_required');
    }
    
    if (name.trim().length > 100) {
        return getTranslation('course_subcategories.subcategory_name_too_long');
    }
    
    // Check for valid characters (letters, numbers, spaces, hyphens, underscores)
    const validPattern = /^[a-zA-Z0-9\s\-_]+$/;
    if (!validPattern.test(name.trim())) {
        return 'Subcategory name can only contain letters, numbers, spaces, hyphens, and underscores';
    }
    
    return null; // No error
}

/**
 * Validate subcategory category selection
 */
function validateSubcategoryCategory(categoryId) {
    if (!categoryId || categoryId === '') {
        return getTranslation('course_subcategories.category_required');
    }
    
    return null; // No error
}

/**
 * Validate subcategory description
 */
function validateSubcategoryDescription(description) {
    if (description && description.length > 500) {
        return getTranslation('course_subcategories.description_too_long');
    }
    
    return null; // No error
}

/**
 * Validate sort order
 */
function validateSubcategorySortOrder(sortOrder) {
    if (sortOrder && (isNaN(sortOrder) || parseInt(sortOrder) < 0)) {
        return getTranslation('course_subcategories.sort_order_invalid');
    }
    
    return null; // No error
}

/**
 * Validate form fields
 */
function validateSubcategoryForm() {
    const name = document.getElementById('subcategoryName') ? document.getElementById('subcategoryName').value : 
                 document.getElementById('editSubcategoryName') ? document.getElementById('editSubcategoryName').value : '';
    const categoryId = document.getElementById('subcategoryCategory') ? document.getElementById('subcategoryCategory').value : 
                      document.getElementById('editSubcategoryCategory') ? document.getElementById('editSubcategoryCategory').value : '';
    const description = document.getElementById('subcategoryDescription') ? document.getElementById('subcategoryDescription').value : 
                       document.getElementById('editSubcategoryDescription') ? document.getElementById('editSubcategoryDescription').value : '';
    const sortOrder = document.getElementById('subcategorySortOrder') ? document.getElementById('subcategorySortOrder').value : 
                     document.getElementById('editSubcategorySortOrder') ? document.getElementById('editSubcategorySortOrder').value : '';
    
    let isValid = true;
    let errors = {};
    
    // Validate name
    const nameError = validateSubcategoryName(name);
    if (nameError) {
        errors.name = nameError;
        isValid = false;
        showSubcategoryFieldError('subcategoryName', nameError);
        showSubcategoryFieldError('editSubcategoryName', nameError);
    } else {
        clearSubcategoryFieldError('subcategoryName');
        clearSubcategoryFieldError('editSubcategoryName');
    }
    
    // Validate category
    const categoryError = validateSubcategoryCategory(categoryId);
    if (categoryError) {
        errors.category_id = categoryError;
        isValid = false;
        showSubcategoryFieldError('subcategoryCategory', categoryError);
        showSubcategoryFieldError('editSubcategoryCategory', categoryError);
    } else {
        clearSubcategoryFieldError('subcategoryCategory');
        clearSubcategoryFieldError('editSubcategoryCategory');
    }
    
    // Validate description
    const descriptionError = validateSubcategoryDescription(description);
    if (descriptionError) {
        errors.description = descriptionError;
        isValid = false;
        showSubcategoryFieldError('subcategoryDescription', descriptionError);
        showSubcategoryFieldError('editSubcategoryDescription', descriptionError);
    } else {
        clearSubcategoryFieldError('subcategoryDescription');
        clearSubcategoryFieldError('editSubcategoryDescription');
    }
    
    // Validate sort order
    const sortOrderError = validateSubcategorySortOrder(sortOrder);
    if (sortOrderError) {
        errors.sort_order = sortOrderError;
        isValid = false;
        showSubcategoryFieldError('subcategorySortOrder', sortOrderError);
        showSubcategoryFieldError('editSubcategorySortOrder', sortOrderError);
    } else {
        clearSubcategoryFieldError('subcategorySortOrder');
        clearSubcategoryFieldError('editSubcategorySortOrder');
    }
    
    return { isValid, errors };
}

/**
 * Show field error
 */
function showSubcategoryFieldError(fieldId, errorMessage) {
    const field = document.getElementById(fieldId);
    let errorDiv = null;
    
    // Map field IDs to error div IDs
    switch (fieldId) {
        case 'subcategoryName':
        case 'editSubcategoryName':
            errorDiv = document.getElementById('nameError') || document.getElementById('editNameError');
            break;
        case 'subcategoryCategory':
        case 'editSubcategoryCategory':
            errorDiv = document.getElementById('categoryError') || document.getElementById('editCategoryError');
            break;
        case 'subcategoryDescription':
        case 'editSubcategoryDescription':
            errorDiv = document.getElementById('descriptionError') || document.getElementById('editDescriptionError');
            break;
        case 'subcategorySortOrder':
        case 'editSubcategorySortOrder':
            errorDiv = document.getElementById('sortOrderError') || document.getElementById('editSortOrderError');
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
function clearSubcategoryFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    let errorDiv = null;
    
    // Map field IDs to error div IDs
    switch (fieldId) {
        case 'subcategoryName':
        case 'editSubcategoryName':
            errorDiv = document.getElementById('nameError') || document.getElementById('editNameError');
            break;
        case 'subcategoryCategory':
        case 'editSubcategoryCategory':
            errorDiv = document.getElementById('categoryError') || document.getElementById('editCategoryError');
            break;
        case 'subcategoryDescription':
        case 'editSubcategoryDescription':
            errorDiv = document.getElementById('descriptionError') || document.getElementById('editDescriptionError');
            break;
        case 'subcategorySortOrder':
        case 'editSubcategorySortOrder':
            errorDiv = document.getElementById('sortOrderError') || document.getElementById('editSortOrderError');
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
function validateSubcategoryFieldOnFocusOut(fieldId) {
    const field = document.getElementById(fieldId);
    if (!field) return;
    
    const value = field.value;
    let error = null;
    
    switch (fieldId) {
        case 'subcategoryName':
        case 'editSubcategoryName':
            error = validateSubcategoryName(value);
            break;
        case 'subcategoryCategory':
        case 'editSubcategoryCategory':
            error = validateSubcategoryCategory(value);
            break;
        case 'subcategoryDescription':
        case 'editSubcategoryDescription':
            error = validateSubcategoryDescription(value);
            break;
        case 'subcategorySortOrder':
        case 'editSubcategorySortOrder':
            error = validateSubcategorySortOrder(value);
            break;
    }
    
    if (error) {
        showSubcategoryFieldError(fieldId, error);
    } else {
        clearSubcategoryFieldError(fieldId);
    }
}

/**
 * Clear all validation errors
 */
function clearSubcategoryValidationErrors() {
    const inputs = document.querySelectorAll('#subcategoryModal .is-invalid');
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
    });
    
    const errorDivs = document.querySelectorAll('#subcategoryModal .invalid-feedback');
    errorDivs.forEach(div => {
        div.textContent = '';
    });
}

/**
 * Setup form validation with focus out events
 */
function setupSubcategoryFormValidation() {
    // Add focus out event listeners for real-time validation
    const fields = [
        'subcategoryName', 'editSubcategoryName',
        'subcategoryCategory', 'editSubcategoryCategory',
        'subcategoryDescription', 'editSubcategoryDescription',
        'subcategorySortOrder', 'editSubcategorySortOrder'
    ];
    
    fields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('blur', () => validateSubcategoryFieldOnFocusOut(fieldId));
        }
    });
    
    // Add character count for description fields
    const descriptionFields = ['subcategoryDescription', 'editSubcategoryDescription'];
    descriptionFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateSubcategoryCharCount);
        }
    });
}

/**
 * Update character count for description fields
 */
function updateSubcategoryCharCount() {
    const descriptionField = this;
    const charCountSpan = descriptionField.id === 'subcategoryDescription' ? 
        document.getElementById('charCount') : 
        document.getElementById('editCharCount');
    
    if (charCountSpan) {
        const currentLength = descriptionField.value.length;
        charCountSpan.textContent = currentLength;
        
        // Add warning class if approaching limit
        if (currentLength > 450) {
            charCountSpan.classList.add('text-warning');
        } else {
            charCountSpan.classList.remove('text-warning');
        }
        
        if (currentLength > 480) {
            charCountSpan.classList.add('text-danger');
        } else {
            charCountSpan.classList.remove('text-danger');
        }
    }
}

// Initialize validation when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    setupSubcategoryFormValidation();
}); 