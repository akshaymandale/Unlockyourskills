/**
 * Course Categories Management JavaScript
 * Handles all dynamic functionality for course categories including:
 * - Category listing and pagination
 * - Search and filtering
 * - Add/edit category functionality
 * - Modal management
 */

// Global variables
let currentCategoryId = null;
let currentPage = 1;
let currentSearch = '';
let currentFilters = {
    status: '',
    sort_order: '',
    subcategory_count: '',
    client_id: null
};

// Initialize client_id from data attribute
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.course-categories');
    
    if (container && container.dataset.clientId) {
        currentFilters.client_id = parseInt(container.dataset.clientId);
    }
    
    // Load initial categories
    loadCategories(1);
});

/**
 * Load categories with AJAX
 */
function loadCategories(page = currentPage) {
    currentPage = page;
    
    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const categoriesContainer = document.getElementById('categoriesContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (categoriesContainer) categoriesContainer.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('controller', 'CourseCategoryController');
    formData.append('action', 'ajaxSearch');
    formData.append('page', currentPage);
    formData.append('search', currentSearch);
    formData.append('status', currentFilters.status);
    formData.append('sort_order', currentFilters.sort_order);
    formData.append('subcategory_count', currentFilters.subcategory_count);

    const url = getProjectUrl('index.php');

    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCategoriesTable(data.categories);
            updatePagination(data.pagination);
            updateSearchInfo(data.pagination.totalCategories);
        } else {
            showToast.error(data.message || 'Error loading categories');
        }
    })
    .catch(error => {
        console.error('Error loading categories:', error);
        showToast.error('Error loading categories');
    })
    .finally(() => {
        // Hide loading indicator and show content
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (categoriesContainer) categoriesContainer.style.display = 'block';
        if (paginationContainer) paginationContainer.style.display = 'block';
    });
}

/**
 * Update categories table with data
 */
function updateCategoriesTable(categories) {
    const tbody = document.getElementById('categoriesTableBody');
    if (!tbody) return;

    if (!categories || categories.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="no-categories">
                        <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                        <h4>${getTranslation('course_categories.no_categories_found')}</h4>
                        <p>${getTranslation('course_categories.no_categories_message')}</p>
                        <button type="button" class="btn theme-btn-primary" id="addFirstCategoryBtn" data-action="add-category">
                            <i class="fas fa-plus me-2"></i>${getTranslation('course_categories.add_first_category')}
                        </button>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = categories.map((category, index) => createCategoryRow(category, index)).join('');
}

/**
 * Create a category table row
 */
function createCategoryRow(category, index) {
    const rowNumber = index + 1;
    const statusBadge = category.is_active ? 
        `<span class="badge bg-success">${getTranslation('course_categories.active')}</span>` : 
        `<span class="badge bg-secondary">${getTranslation('course_categories.inactive')}</span>`;
    
    const description = category.description ? 
        (category.description.length > 100 ? 
            escapeHtml(category.description.substring(0, 100)) + '...' : 
            escapeHtml(category.description)) : 
        `<span class="text-muted">${getTranslation('course_categories.no_description')}</span>`;
    
    const deleteButton = category.subcategory_count == 0 ? 
        `<button type="button" class="btn btn-sm btn-outline-danger delete-category-btn" 
                data-category-id="${category.id}" 
                title="${getTranslation('course_categories.delete_category')}">
            <i class="fas fa-trash"></i>
        </button>` : '';

    return `
        <tr>
            <td><strong>${escapeHtml(category.name)}</strong></td>
            <td>${description}</td>
            <td>${category.sort_order}</td>
            <td><span class="badge bg-info">${category.subcategory_count}</span></td>
            <td>${statusBadge}</td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary edit-category-btn" 
                            data-category-id="${category.id}" 
                            title="${getTranslation('course_categories.edit_category')}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm ${category.is_active ? 'btn-outline-success' : 'btn-outline-secondary'} toggle-status-btn" 
                            data-category-id="${category.id}" 
                            title="${category.is_active ? getTranslation('course_categories.deactivate') : getTranslation('course_categories.activate')}">
                        <i class="fas ${category.is_active ? 'fa-toggle-on' : 'fa-toggle-off'}"></i>
                    </button>
                    ${deleteButton}
                </div>
            </td>
        </tr>
    `;
}

/**
 * Update pagination
 */
function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container || !pagination) return;

    if (pagination.totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHTML = '<nav aria-label="Categories pagination"><ul class="pagination justify-content-center">';
    
    // Previous button
    if (pagination.currentPage > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">« Previous</a>
            </li>
        `;
    }
    
    // Page numbers
    const startPage = Math.max(1, pagination.currentPage - 2);
    const endPage = Math.min(pagination.totalPages, pagination.currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === pagination.currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }
    
    // Next button
    if (pagination.currentPage < pagination.totalPages) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Next »</a>
            </li>
        `;
    }
    
    paginationHTML += '</ul></nav>';
    container.innerHTML = paginationHTML;
}

/**
 * Update search results info
 */
function updateSearchInfo(totalCategories) {
    const searchInfo = document.getElementById('searchResultsInfo');
    const resultsText = document.getElementById('resultsText');
    
    if (!searchInfo || !resultsText) return;

    if (currentSearch || currentFilters.status || currentFilters.sort_order || currentFilters.subcategory_count) {
        let infoText = `Showing ${totalCategories} result(s)`;
        
        if (currentSearch) {
            infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
        }
        
        if (currentFilters.status || currentFilters.sort_order || currentFilters.subcategory_count) {
            infoText += ' with filters applied';
        }
        
        resultsText.innerHTML = infoText;
        searchInfo.style.display = 'block';
    } else {
        searchInfo.style.display = 'none';
    }
}

/**
 * Search categories
 */
function searchCategories() {
    console.log('searchCategories called');
    
    // Get all filter values
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const sortOrderFilter = document.getElementById('sortOrderFilter');
    const subcategoryFilter = document.getElementById('subcategoryFilter');
    
    // Update current filters
    currentSearch = searchInput ? searchInput.value.trim() : '';
    currentFilters.status = statusFilter ? statusFilter.value : '';
    currentFilters.sort_order = sortOrderFilter ? sortOrderFilter.value : '';
    currentFilters.subcategory_count = subcategoryFilter ? subcategoryFilter.value : '';
    currentFilters.page = 1;
    
    console.log('Updated filters:', currentFilters);
    console.log('Current search:', currentSearch);
    
    loadCategories(1);
}

/**
 * Reset filters
 */
function resetFilters() {
    console.log('resetFilters called');
    
    // Reset all filter inputs
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const sortOrderFilter = document.getElementById('sortOrderFilter');
    const subcategoryFilter = document.getElementById('subcategoryFilter');
    
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (sortOrderFilter) sortOrderFilter.value = '';
    if (subcategoryFilter) subcategoryFilter.value = '';
    
    // Reset current filters
    currentSearch = '';
    currentFilters.status = '';
    currentFilters.sort_order = '';
    currentFilters.subcategory_count = '';
    currentFilters.page = 1;
    
    console.log('Filters reset:', currentFilters);
    
    loadCategories(1);
}

/**
 * Load add category modal
 */
function loadAddCategoryModal() {
    // Clear current category ID for new category
    currentCategoryId = null;
    
    // Reset form
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('modalTitle').textContent = getTranslation('course_categories.add_new_category');
    document.getElementById('submitBtnText').textContent = getTranslation('course_categories.create_category');
    document.getElementById('modalIcon').className = 'fas fa-plus me-2';
    
    // Clear validation errors
    clearValidationErrors();
    
    // Reset character count
    updateCharCount();
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

/**
 * Load edit category modal
 */
function loadEditCategoryModal(categoryId) {
    // Set current category ID for form submission
    currentCategoryId = categoryId;
    
    // Show loading state
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
    
    // Update modal title
    document.getElementById('modalTitle').textContent = getTranslation('course_categories.edit_category');
    document.getElementById('submitBtnText').textContent = getTranslation('course_categories.update_category');
    document.getElementById('modalIcon').className = 'fas fa-edit me-2';
    
    // Fetch category data using legacy controller-based routing
    fetch(getProjectUrl(`index.php?controller=CourseCategoryController&action=get&id=${categoryId}`), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateEditForm(data.category);
        } else {
            showToast.error('Error loading category: ' + (data.message || data.error || 'Unknown error'));
            modal.hide();
        }
    })
    .catch(error => {
        console.error('Error loading category:', error);
        showToast.error('Error loading category');
        modal.hide();
    });
}

/**
 * Populate edit form with category data
 */
function populateEditForm(category) {
    // Set current category ID
    currentCategoryId = category.id;
    
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('categorySortOrder').value = category.sort_order || 0;
    document.getElementById('categoryActive').checked = category.is_active == 1;
    
    // Update character count
    updateCharCount();
}

/**
 * Submit category form
 */
function submitCategoryForm() {
    // Clear previous validation errors
    clearCategoryValidationErrors();
    
    // Validate form
    const validation = validateCategoryForm();
    
    if (!validation.isValid) {
        // Don't show toast for client-side validation errors
        // Just return and let the field errors show
        return;
    }
    
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    
    // Add category_id if editing
    if (currentCategoryId) {
        formData.append('category_id', currentCategoryId);
    }
    
    // Add client_id
    if (currentFilters.client_id) {
        formData.append('client_id', currentFilters.client_id);
    }

    fetch(getProjectUrl(`index.php?controller=CourseCategoryController&action=submitCategory`), {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast.success(data.message);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('categoryModal'));
            modal.hide();
            
            // Reload categories
            loadCategories(currentFilters.page);
        } else {
            showToast.error(data.message);
            
            // Show validation errors
            if (data.errors) {
                Object.keys(data.errors).forEach(field => {
                    const input = document.getElementById(field.replace('_', ''));
                    if (input) {
                        input.classList.add('is-invalid');
                        const errorDiv = document.getElementById(field.replace('_', '') + 'Error');
                        if (errorDiv) {
                            errorDiv.textContent = data.errors[field];
                        }
                    }
                });
            }
        }
    })
    .catch(error => {
        showToast.error('Error submitting category');
    });
}

/**
 * Toggle category status
 */
function toggleCategoryStatus(categoryId) {
    // Get the category name and current status for better confirmation message
    const categoryRow = document.querySelector(`[data-category-id="${categoryId}"]`).closest('tr');
    const categoryName = categoryRow ? categoryRow.querySelector('td:nth-child(2) strong').textContent : 'category';
    const currentStatus = categoryRow ? categoryRow.querySelector('td:nth-child(6) .badge').textContent.trim() : '';
    const newStatus = currentStatus === 'Active' ? 'inactive' : 'active';
    
    // Use the centralized confirmation system
    if (typeof window.confirmAction === 'function') {
        window.confirmAction('toggle', 'category', () => {
            performToggleStatus(categoryId);
        }, `Are you sure you want to change the status of "${categoryName}" to ${newStatus}?`);
    } else if (typeof ConfirmationModal !== 'undefined' && ConfirmationModal.confirm) {
        ConfirmationModal.confirm({
            title: 'Toggle Category Status',
            message: `Are you sure you want to change the status of "${categoryName}" to ${newStatus}?`,
            confirmText: 'Toggle Status',
            confirmClass: 'btn-primary',
            onConfirm: () => {
                performToggleStatus(categoryId);
            },
            onCancel: () => {
                // User cancelled
            }
        });
    } else {
        // Fallback to simple browser confirm
        const message = `Are you sure you want to change the status of "${categoryName}" to ${newStatus}?`;
        if (confirm(message)) {
            performToggleStatus(categoryId);
        }
    }
}

/**
 * Perform toggle status action
 */
function performToggleStatus(categoryId) {
    fetch(getProjectUrl('course-categories/ajax/toggle-status'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `id=${categoryId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast.success(data.message);
            loadCategories(currentFilters.page);
        } else {
            showToast.error(data.message || data.error || 'Error toggling status');
        }
    })
    .catch(error => {
        showToast.error('Error toggling status');
    });
}

/**
 * Delete category
 */
function deleteCategory(categoryId) {
    // Get the category name for better confirmation message
    const categoryRow = document.querySelector(`[data-category-id="${categoryId}"]`).closest('tr');
    const categoryName = categoryRow ? categoryRow.querySelector('td:nth-child(2) strong').textContent : 'category';
    
    // Use the centralized confirmation system
    if (typeof window.confirmAction === 'function') {
        window.confirmAction('delete', 'category', () => {
            performDeleteCategory(categoryId);
        }, `Are you sure you want to delete the category "${categoryName}"?`, 'This action cannot be undone.');
    } else if (typeof ConfirmationModal !== 'undefined' && ConfirmationModal.confirm) {
        ConfirmationModal.confirm({
            title: 'Delete Category',
            message: `Are you sure you want to delete the category "${categoryName}"?`,
            subtext: 'This action cannot be undone.',
            confirmText: 'Delete',
            confirmClass: 'btn-danger',
            onConfirm: () => {
                performDeleteCategory(categoryId);
            },
            onCancel: () => {
                // User cancelled
            }
        });
    } else {
        // Fallback to simple browser confirm
        const message = `Are you sure you want to delete the category "${categoryName}"?\n\nThis action cannot be undone.`;
        if (confirm(message)) {
            performDeleteCategory(categoryId);
        }
    }
}

/**
 * Perform delete category action
 */
function performDeleteCategory(categoryId) {
    // Use the getProjectUrl function to construct the URL properly
    const url = getProjectUrl(`index.php?controller=CourseCategoryController&action=delete&id=${categoryId}`);
    
    const formData = new FormData();
    formData.append('_method', 'DELETE');
    
    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast.success(data.message);
            loadCategories(currentFilters.page);
        } else {
            showToast.error(data.message || 'Failed to delete category');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast.error('An error occurred while deleting the category');
    });
}

/**
 * Show confirmation modal
 */
function showConfirmationModal(title, message, onConfirm) {
    const modalLabel = document.getElementById('confirmationModalLabel');
    const modalBody = document.getElementById('confirmationModalBody');
    const confirmBtn = document.getElementById('confirmActionBtn');
    const modalElement = document.getElementById('confirmationModal');
    
    // Check if all required elements exist
    if (!modalLabel || !modalBody || !confirmBtn || !modalElement) {
        // Fallback to simple confirm
        if (confirm(message)) {
            onConfirm();
        }
        return;
    }
    
    modalLabel.textContent = title;
    modalBody.textContent = message;
    
    confirmBtn.onclick = () => {
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();
        onConfirm();
    };
    
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

/**
 * Update character count for description
 */
function updateCharCount() {
    const textarea = document.getElementById('categoryDescription');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        const currentLength = textarea.value.length;
        const maxLength = 500;
        charCount.textContent = `${currentLength}/${maxLength}`;
        
        if (currentLength > maxLength) {
            charCount.classList.add('text-danger');
        } else {
            charCount.classList.remove('text-danger');
        }
    }
}

/**
 * Clear validation errors
 */
function clearValidationErrors() {
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
 * Utility function to escape HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Get project URL with correct case sensitivity
 */
function getProjectUrl(path) {
    // Get the current pathname and ensure it uses the correct case
    const currentPath = window.location.pathname;
    
    // Extract the base path (everything before the last slash)
    const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
    
    // Ensure we use the correct case for Unlockyourskills
    const correctedBasePath = basePath.replace(/\/unlockyourskills/i, '/Unlockyourskills');
    
    return window.location.origin + correctedBasePath + '/' + path;
}

/**
 * Get translation
 */
function getTranslation(key) {
    // Check if translations are available
    if (window.translations && window.translations[key]) {
        return window.translations[key];
    }
    
    // Return the key as fallback
    return key;
}

/**
 * Validate form fields
 */
function validateForm() {
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
        showFieldError('categoryName', nameError);
    } else {
        clearFieldError('categoryName');
    }
    
    // Validate description
    const descriptionError = validateCategoryDescription(description);
    if (descriptionError) {
        errors.description = descriptionError;
        isValid = false;
        showFieldError('categoryDescription', descriptionError);
    } else {
        clearFieldError('categoryDescription');
    }
    
    // Validate sort order
    const sortOrderError = validateSortOrder(sortOrder);
    if (sortOrderError) {
        errors.sort_order = sortOrderError;
        isValid = false;
        showFieldError('categorySortOrder', sortOrderError);
    } else {
        clearFieldError('categorySortOrder');
    }
    
    return { isValid, errors };
}

/**
 * Show field error
 */
function showFieldError(fieldId, errorMessage) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + 'Error');
    
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
function clearFieldError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorDiv = document.getElementById(fieldId + 'Error');
    
    if (field) {
        field.classList.remove('is-invalid');
    }
    
    if (errorDiv) {
        errorDiv.textContent = '';
    }
}

/**
 * Setup form validation
 */
function setupFormValidation() {
    // Use the validation functions from course_category_validation.js
    if (typeof setupCategoryFormValidation === 'function') {
        setupCategoryFormValidation();
    }
}

// Global event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Form submission
    const categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitCategoryForm();
        });
    }
    
    // Character count for description
    const categoryDescription = document.getElementById('categoryDescription');
    if (categoryDescription) {
        categoryDescription.addEventListener('input', updateCharCount);
    }
    
    // Add focus out validation for form fields
    setupFormValidation();
    
    // Clear filters button
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', resetFilters);
    }
    
    // Search input enter key and button
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCategories();
            }
        });
    }
    
    if (searchButton) {
        searchButton.addEventListener('click', searchCategories);
    }
    
    // Filter change events
    const statusFilter = document.getElementById('statusFilter');
    const sortOrderFilter = document.getElementById('sortOrderFilter');
    const subcategoryFilter = document.getElementById('subcategoryFilter');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', searchCategories);
    }
    
    if (sortOrderFilter) {
        sortOrderFilter.addEventListener('change', searchCategories);
    }
    
    if (subcategoryFilter) {
        subcategoryFilter.addEventListener('change', searchCategories);
    }
    
    // Add click event listener for add category buttons
    const addCategoryBtns = document.querySelectorAll('button[data-action="add-category"]');
    addCategoryBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof loadAddCategoryModal === 'function') {
                loadAddCategoryModal();
            } else {
                alert('Error: Function not loaded. Please refresh the page.');
            }
        });
    });
    
    // Add event listeners for action buttons using event delegation
    document.addEventListener('click', function(e) {
        // Edit category button
        if (e.target.closest('.edit-category-btn')) {
            const btn = e.target.closest('.edit-category-btn');
            const categoryId = btn.getAttribute('data-category-id');
            if (typeof loadEditCategoryModal === 'function') {
                loadEditCategoryModal(categoryId);
            }
        }
        
        // Toggle status button
        if (e.target.closest('.toggle-status-btn')) {
            const btn = e.target.closest('.toggle-status-btn');
            const categoryId = btn.getAttribute('data-category-id');
            if (typeof toggleCategoryStatus === 'function') {
                toggleCategoryStatus(categoryId);
            }
        }
        
        // Delete category button
        if (e.target.closest('.delete-category-btn')) {
            const btn = e.target.closest('.delete-category-btn');
            const categoryId = btn.getAttribute('data-category-id');
            if (typeof deleteCategory === 'function') {
                deleteCategory(categoryId);
            }
        }
        
        // Pagination links
        if (e.target.closest('.page-link')) {
            e.preventDefault();
            const link = e.target.closest('.page-link');
            const page = link.getAttribute('data-page');
            if (page) {
                loadCategories(parseInt(page));
            }
        }
    });
});

// Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
} 