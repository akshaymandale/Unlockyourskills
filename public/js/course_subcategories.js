/**
 * Course Subcategories Management JavaScript
 * Handles all AJAX operations, search, filtering, and CRUD operations for course subcategories
 */

// Global variables
let currentPage = 1;
let currentSearch = '';
let currentFilters = {
    status: '',
    category: '',
    sort_order: 'asc'
};

// Debounce function for search
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

// Get project URL helper
function getProjectUrl(path) {
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    return baseUrl + '/' + path.replace(/^\//, '');
}

// Get translation helper
function getTranslation(key, replacements = {}) {
    if (typeof window.translations !== 'undefined' && window.translations[key]) {
        let translation = window.translations[key];
        Object.keys(replacements).forEach(key => {
            translation = translation.replace(`{${key}}`, replacements[key]);
        });
        return translation;
    }
    return key;
}

// Escape HTML helper
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Load subcategories with AJAX
 */
function loadSubcategories(page = currentPage) {
    currentPage = page;
    
    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const subcategoriesContainer = document.getElementById('subcategoriesContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (subcategoriesContainer) subcategoriesContainer.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('page', currentPage);
    formData.append('search', currentSearch);
    formData.append('status', currentFilters.status);
    formData.append('category', currentFilters.category);
    formData.append('sort_order', currentFilters.sort_order);

    const url = window.courseSubcategoriesRoutes ? window.courseSubcategoriesRoutes.ajaxSearch : getProjectUrl('index.php');

    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderSubcategoriesTable(data.subcategories);
            renderPagination(data.pagination);
            updateSearchResultsInfo(data.pagination.total_records);
            
            // Show/hide no results message
            const noResultsMessage = document.getElementById('noResultsMessage');
            if (data.subcategories.length === 0) {
                if (noResultsMessage) noResultsMessage.style.display = 'block';
                if (subcategoriesContainer) subcategoriesContainer.style.display = 'none';
            } else {
                if (noResultsMessage) noResultsMessage.style.display = 'none';
                if (subcategoriesContainer) subcategoriesContainer.style.display = 'block';
            }
        } else {
            console.error('Error loading subcategories:', data.message);
            if (typeof showToast !== 'undefined' && showToast.error) {
                showToast.error('Error loading subcategories: ' + data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast('Error loading subcategories: ' + data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showToast !== 'undefined' && showToast.error) {
            showToast.error('An error occurred while loading subcategories');
        } else if (typeof showSimpleToast !== 'undefined') {
            showSimpleToast('An error occurred while loading subcategories', 'error');
        }
    })
    .finally(() => {
        if (loadingIndicator) loadingIndicator.style.display = 'none';
    });
}

/**
 * Render subcategories table
 */
function renderSubcategoriesTable(subcategories) {
    const tableBody = document.getElementById('subcategoriesTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = '';

    if (subcategories.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted">
                    ${getTranslation('course_subcategories.no_subcategories_found')}
                </td>
            </tr>
        `;
        return;
    }

    subcategories.forEach((subcategory, index) => {
        const row = createSubcategoryRow(subcategory, index);
        tableBody.insertAdjacentHTML('beforeend', row);
    });

    // Add event listeners to action buttons
    addActionEventListeners();
}

/**
 * Create a subcategory table row
 */
function createSubcategoryRow(subcategory, index) {
    const rowNumber = index + 1;
    const statusBadge = subcategory.is_active ? 
        `<span class="badge bg-success">${getTranslation('course_subcategories.active')}</span>` : 
        `<span class="badge bg-secondary">${getTranslation('course_subcategories.inactive')}</span>`;
    
    const description = subcategory.description ? 
        (subcategory.description.length > 100 ? 
            escapeHtml(subcategory.description.substring(0, 100)) + '...' : 
            escapeHtml(subcategory.description)) : 
        `<span class="text-muted">${getTranslation('course_subcategories.no_description')}</span>`;
    
    const deleteButton = `
        <button type="button" class="btn btn-sm btn-outline-danger delete-subcategory-btn" 
                data-subcategory-id="${subcategory.id}" 
                title="${getTranslation('course_subcategories.delete_subcategory')}">
            <i class="fas fa-trash"></i>
        </button>`;
    
    return `
        <tr>
            <td><strong>${escapeHtml(subcategory.name)}</strong></td>
            <td><span class="badge bg-info">${escapeHtml(subcategory.category_name)}</span></td>
            <td>${description}</td>
            <td>${subcategory.sort_order}</td>
            <td>${statusBadge}</td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary edit-subcategory-btn" 
                            data-subcategory-id="${subcategory.id}" 
                            title="${getTranslation('course_subcategories.edit_subcategory')}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm ${subcategory.is_active ? 'btn-outline-success' : 'btn-outline-secondary'} toggle-status-btn" 
                            data-subcategory-id="${subcategory.id}" 
                            data-current-status="${subcategory.is_active}"
                            title="${subcategory.is_active ? 
                                getTranslation('course_subcategories.deactivate') : 
                                getTranslation('course_subcategories.activate')}">
                        <i class="fas ${subcategory.is_active ? 'fa-toggle-on' : 'fa-toggle-off'}"></i>
                    </button>
                    ${deleteButton}
                </div>
            </td>
        </tr>
    `;
}

/**
 * Render pagination
 */
function renderPagination(pagination) {
    const paginationList = document.getElementById('paginationList');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (!paginationList || !paginationContainer) return;

    if (pagination.total_pages <= 1) {
        paginationContainer.style.display = 'none';
        return;
    }

    paginationContainer.style.display = 'block';
    paginationList.innerHTML = '';

    // Previous button
    if (pagination.has_prev) {
        paginationList.innerHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                    <i class="fas fa-chevron-left"></i> ${getTranslation('pagination_prev')}
                </a>
            </li>
        `;
    }

    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === pagination.current_page ? 'active' : '';
        paginationList.innerHTML += `
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Next button
    if (pagination.has_next) {
        paginationList.innerHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                    ${getTranslation('pagination_next')} <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
}

/**
 * Update search results info
 */
function updateSearchResultsInfo(totalRecords) {
    const searchResultsInfo = document.getElementById('searchResultsInfo');
    const resultsText = document.getElementById('resultsText');
    
    if (!searchResultsInfo || !resultsText) return;

    const hasFilters = currentSearch || currentFilters.status || currentFilters.category || currentFilters.sort_order;
    
    if (hasFilters) {
        let text = `${getTranslation('course_subcategories.showing')} ${totalRecords} ${getTranslation('course_subcategories.subcategories')}`;
        
        if (currentSearch) {
            text += ` ${getTranslation('course_subcategories.for_search')} "${currentSearch}"`;
        }
        
        if (currentFilters.status || currentFilters.category || currentFilters.sort_order) {
            text += ` ${getTranslation('course_subcategories.with_filters_applied')}`;
        }
        
        resultsText.textContent = text;
        searchResultsInfo.style.display = 'block';
    } else {
        searchResultsInfo.style.display = 'none';
    }
}

/**
 * Search subcategories
 */
function searchSubcategories() {
    console.log('searchSubcategories called');
    
    // Get all filter values
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortOrderFilter = document.getElementById('sortOrderFilter');
    
    // Update current filters
    currentSearch = searchInput ? searchInput.value.trim() : '';
    currentFilters.status = statusFilter ? statusFilter.value : '';
    currentFilters.category = categoryFilter ? categoryFilter.value : '';
    currentFilters.sort_order = sortOrderFilter ? sortOrderFilter.value : '';
    currentFilters.page = 1;
    
    console.log('Updated filters:', currentFilters);
    console.log('Current search:', currentSearch);
    
    loadSubcategories(1);
}

/**
 * Reset filters
 */
function resetFilters() {
    console.log('resetFilters called');
    
    // Reset form elements
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortOrderFilter = document.getElementById('sortOrderFilter');
    
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (categoryFilter) categoryFilter.value = '';
    if (sortOrderFilter) sortOrderFilter.value = '';
    
    // Reset current filters
    currentSearch = '';
    currentFilters = {
        status: '',
        category: '',
        sort_order: 'asc'
    };
    
    console.log('Filters reset');
    
    loadSubcategories(1);
}

/**
 * Add subcategory
 */
function addSubcategory() {
    const modal = new bootstrap.Modal(document.getElementById('subcategoryModal'));
    
    // Load modal content
    const formData = new FormData();
    formData.append('controller', 'CourseSubcategoryController');
    formData.append('action', 'loadAddModal');
    formData.append('X-Requested-With', 'XMLHttpRequest');

    fetch(window.courseSubcategoriesRoutes ? window.courseSubcategoriesRoutes.addModal : getProjectUrl('index.php'), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector('#subcategoryModal .modal-content').innerHTML = data.html;
            modal.show();
            setupModalEventListeners('add');
        } else {
            console.error('Error loading add form:', data.message);
            if (typeof showToast !== 'undefined' && showToast.error) {
                showToast.error('Error loading add form: ' + data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast('Error loading add form: ' + data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showToast !== 'undefined' && showToast.error) {
            showToast.error('An error occurred while loading the form');
        } else if (typeof showSimpleToast !== 'undefined') {
            showSimpleToast('An error occurred while loading the form', 'error');
        }
    });
}

/**
 * Edit subcategory
 */
function editSubcategory(subcategoryId) {
    const modal = new bootstrap.Modal(document.getElementById('subcategoryModal'));
    
    // Load modal content
    const formData = new FormData();
    formData.append('controller', 'CourseSubcategoryController');
    formData.append('action', 'loadEditModal');
    formData.append('subcategory_id', subcategoryId);
    formData.append('X-Requested-With', 'XMLHttpRequest');

    fetch(window.courseSubcategoriesRoutes ? window.courseSubcategoriesRoutes.editModal : getProjectUrl('index.php'), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelector('#subcategoryModal .modal-content').innerHTML = data.html;
            modal.show();
            setupModalEventListeners('edit');
        } else {
            console.error('Error loading edit form:', data.message);
            if (typeof showToast !== 'undefined' && showToast.error) {
                showToast.error('Error loading edit form: ' + data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast('Error loading edit form: ' + data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showToast !== 'undefined' && showToast.error) {
            showToast.error('An error occurred while loading the form');
        } else if (typeof showSimpleToast !== 'undefined') {
            showSimpleToast('An error occurred while loading the form', 'error');
        }
    });
}

/**
 * Setup modal event listeners
 */
function setupModalEventListeners(mode) {
    const form = document.getElementById(mode === 'add' ? 'addSubcategoryForm' : 'editSubcategoryForm');
    const descriptionField = document.getElementById(mode === 'add' ? 'subcategoryDescription' : 'editSubcategoryDescription');
    const charCount = document.getElementById(mode === 'add' ? 'charCount' : 'editCharCount');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            submitSubcategoryForm(mode);
        });
    }
    
    if (descriptionField && charCount) {
        descriptionField.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
    
    // Setup validation for focus out events
    if (typeof setupSubcategoryFormValidation === 'function') {
        setupSubcategoryFormValidation();
    }
}

/**
 * Submit subcategory form
 */
function submitSubcategoryForm(mode) {
    // Clear previous validation errors
    clearSubcategoryValidationErrors();
    
    // Validate form
    const validation = validateSubcategoryForm();
    
    if (!validation.isValid) {
        // Don't show toast for client-side validation errors
        // Just return and let the field errors show
        return;
    }
    
    const formData = new FormData();
    formData.append('controller', 'CourseSubcategoryController');
    formData.append('action', mode === 'add' ? 'submitAddModal' : 'submitEditModal');
    
    // Get form values
    const name = document.getElementById('subcategoryName') ? document.getElementById('subcategoryName').value : 
                 document.getElementById('editSubcategoryName') ? document.getElementById('editSubcategoryName').value : '';
    const categoryId = document.getElementById('subcategoryCategory') ? document.getElementById('subcategoryCategory').value : 
                      document.getElementById('editSubcategoryCategory') ? document.getElementById('editSubcategoryCategory').value : '';
    const description = document.getElementById('subcategoryDescription') ? document.getElementById('subcategoryDescription').value : 
                       document.getElementById('editSubcategoryDescription') ? document.getElementById('editSubcategoryDescription').value : '';
    const sortOrder = document.getElementById('subcategorySortOrder') ? document.getElementById('subcategorySortOrder').value : 
                     document.getElementById('editSubcategorySortOrder') ? document.getElementById('editSubcategorySortOrder').value : '';
    const isActive = document.getElementById('subcategoryActive') ? document.getElementById('subcategoryActive').checked : 
                    document.getElementById('editSubcategoryActive') ? document.getElementById('editSubcategoryActive').checked : true;
    
    formData.append('name', name);
    formData.append('category_id', categoryId);
    formData.append('description', description);
    formData.append('sort_order', sortOrder);
    formData.append('is_active', isActive ? '1' : '0');
    
    if (mode === 'edit') {
        const subcategoryId = document.querySelector('input[name="subcategory_id"]') ? 
                             document.querySelector('input[name="subcategory_id"]').value : '';
        formData.append('subcategory_id', subcategoryId);
    }
    
    const url = window.courseSubcategoriesRoutes ? 
                (mode === 'add' ? window.courseSubcategoriesRoutes.submitAdd : window.courseSubcategoriesRoutes.submitEdit) : 
                getProjectUrl('index.php');
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showToast !== 'undefined' && showToast.success) {
                showToast.success(data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast(data.message, 'success');
            }
            
            // Close modal and reload subcategories
            const modal = bootstrap.Modal.getInstance(document.getElementById('subcategoryModal'));
            if (modal) modal.hide();
            
            loadSubcategories(currentPage);
        } else {
            if (typeof showToast !== 'undefined' && showToast.error) {
                showToast.error(data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast(data.message, 'error');
            }
            
            // Display field errors if any
            if (data.errors) {
                displayFieldErrors(data.errors, mode);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showToast !== 'undefined' && showToast.error) {
            showToast.error('An error occurred while saving the subcategory');
        } else if (typeof showSimpleToast !== 'undefined') {
            showSimpleToast('An error occurred while saving the subcategory', 'error');
        }
    });
}

/**
 * Display field errors
 */
function displayFieldErrors(errors, mode) {
    // Clear previous errors
    const errorElements = document.querySelectorAll('.invalid-feedback');
    errorElements.forEach(el => el.textContent = '');
    
    // Display new errors
    Object.keys(errors).forEach(field => {
        const errorElement = document.getElementById(mode === 'add' ? field + 'Error' : 'edit' + field.charAt(0).toUpperCase() + field.slice(1) + 'Error');
        if (errorElement) {
            errorElement.textContent = errors[field];
        }
    });
}

/**
 * Toggle subcategory status
 */
function toggleSubcategoryStatus(subcategoryId) {
    // Get the subcategory name and current status for better confirmation message
    const subcategoryRow = document.querySelector(`[data-subcategory-id="${subcategoryId}"]`).closest('tr');
    const subcategoryName = subcategoryRow ? subcategoryRow.querySelector('td:nth-child(1) strong').textContent : 'subcategory';
    const currentStatus = subcategoryRow ? subcategoryRow.querySelector('td:nth-child(5) .badge').textContent.trim() : '';
    const newStatus = currentStatus === 'Active' ? 'inactive' : 'active';
    
    // Use the centralized confirmation system
    if (typeof window.confirmAction === 'function') {
        window.confirmAction('toggle', 'subcategory', () => {
            performToggleStatus(subcategoryId);
        }, `Are you sure you want to change the status of "${subcategoryName}" to ${newStatus}?`);
    } else if (typeof ConfirmationModal !== 'undefined' && ConfirmationModal.confirm) {
        ConfirmationModal.confirm({
            title: 'Toggle Subcategory Status',
            message: `Are you sure you want to change the status of "${subcategoryName}" to ${newStatus}?`,
            confirmText: 'Toggle Status',
            confirmClass: 'btn-primary',
            onConfirm: () => {
                performToggleStatus(subcategoryId);
            },
            onCancel: () => {
                // User cancelled
            }
        });
    } else {
        // Fallback to simple browser confirm
        const message = `Are you sure you want to change the status of "${subcategoryName}" to ${newStatus}?`;
        if (confirm(message)) {
            performToggleStatus(subcategoryId);
        }
    }
}

/**
 * Perform toggle status action
 */
function performToggleStatus(subcategoryId) {
    const formData = new FormData();
    formData.append('controller', 'CourseSubcategoryController');
    formData.append('action', 'toggleStatus');
    formData.append('subcategory_id', subcategoryId);
    formData.append('X-Requested-With', 'XMLHttpRequest');

    fetch(window.courseSubcategoriesRoutes ? window.courseSubcategoriesRoutes.toggleStatus : getProjectUrl('index.php'), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showToast !== 'undefined' && showToast.success) {
                showToast.success(data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast(data.message, 'success');
            }
            loadSubcategories(currentPage);
        } else {
            if (typeof showToast !== 'undefined' && showToast.error) {
                showToast.error(data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast(data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showToast !== 'undefined' && showToast.error) {
            showToast.error('An error occurred while updating status');
        } else if (typeof showSimpleToast !== 'undefined') {
            showSimpleToast('An error occurred while updating status', 'error');
        }
    });
}

/**
 * Delete subcategory
 */
function deleteSubcategory(subcategoryId) {
    // Get the subcategory name for better confirmation message
    const subcategoryRow = document.querySelector(`[data-subcategory-id="${subcategoryId}"]`).closest('tr');
    const subcategoryName = subcategoryRow ? subcategoryRow.querySelector('td:nth-child(2) strong').textContent : 'subcategory';
    
    // Use the centralized confirmation system
    if (typeof window.confirmAction === 'function') {
        window.confirmAction('delete', 'subcategory', () => {
            performDeleteSubcategory(subcategoryId);
        }, `Are you sure you want to delete the subcategory "${subcategoryName}"?`, 'This action cannot be undone.');
    } else {
        // Fallback to simple browser confirm
        const message = `Are you sure you want to delete the subcategory "${subcategoryName}"?\n\nThis action cannot be undone.`;
        if (confirm(message)) {
            performDeleteSubcategory(subcategoryId);
        }
    }
}

/**
 * Perform delete subcategory
 */
function performDeleteSubcategory(subcategoryId) {
    const formData = new FormData();
    formData.append('controller', 'CourseSubcategoryController');
    formData.append('action', 'delete');
    formData.append('id', subcategoryId);
    formData.append('X-Requested-With', 'XMLHttpRequest');

    fetch(window.courseSubcategoriesRoutes ? window.courseSubcategoriesRoutes.delete : getProjectUrl('index.php'), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showToast !== 'undefined' && showToast.success) {
                showToast.success(data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast(data.message, 'success');
            }
            loadSubcategories(currentPage);
        } else {
            if (typeof showToast !== 'undefined' && showToast.error) {
                showToast.error(data.message);
            } else if (typeof showSimpleToast !== 'undefined') {
                showSimpleToast(data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showToast !== 'undefined' && showToast.error) {
            showToast.error('An error occurred while deleting the subcategory');
        } else if (typeof showSimpleToast !== 'undefined') {
            showSimpleToast('An error occurred while deleting the subcategory', 'error');
        }
    });
}

/**
 * Add action event listeners
 */
function addActionEventListeners() {
    // Edit buttons
    document.querySelectorAll('.edit-subcategory-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const subcategoryId = this.getAttribute('data-subcategory-id');
            editSubcategory(subcategoryId);
        });
    });

    // Toggle status buttons
    document.querySelectorAll('.toggle-status-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const subcategoryId = this.getAttribute('data-subcategory-id');
            toggleSubcategoryStatus(subcategoryId);
        });
    });

    // Delete buttons
    document.querySelectorAll('.delete-subcategory-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const subcategoryId = this.getAttribute('data-subcategory-id');
            deleteSubcategory(subcategoryId);
        });
    });
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Search input with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(searchSubcategories, 500));
    }

    // Search button
    const searchButton = document.getElementById('searchButton');
    if (searchButton) {
        searchButton.addEventListener('click', searchSubcategories);
    }

    // Filter dropdowns
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const sortOrderFilter = document.getElementById('sortOrderFilter');

    if (statusFilter) {
        statusFilter.addEventListener('change', searchSubcategories);
    }
    if (categoryFilter) {
        categoryFilter.addEventListener('change', searchSubcategories);
    }
    if (sortOrderFilter) {
        sortOrderFilter.addEventListener('change', searchSubcategories);
    }

    // Clear filters button
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', resetFilters);
    }

    // Add subcategory button
    const addSubcategoryBtn = document.getElementById('addSubcategoryBtn');
    if (addSubcategoryBtn) {
        addSubcategoryBtn.addEventListener('click', addSubcategory);
    }

    // Add first subcategory button
    const addFirstSubcategoryBtn = document.getElementById('addFirstSubcategoryBtn');
    if (addFirstSubcategoryBtn) {
        addFirstSubcategoryBtn.addEventListener('click', addSubcategory);
    }

    // Pagination
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            if (page) {
                loadSubcategories(page);
            }
        }
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const sortOrderFilter = document.getElementById('sortOrderFilter');
    if (sortOrderFilter) {
        sortOrderFilter.value = 'asc';
    }
    currentFilters.sort_order = 'asc';
    loadSubcategories(1);
    setupEventListeners();
}); 