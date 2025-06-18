// User Management JavaScript
// Global variables to track current state
let currentPage = 1;
let currentSearch = '';
let currentFilters = {
    user_status: '',
    locked_status: '',
    user_role: '',
    gender: ''
};

// Helper function to generate project URLs
function getProjectUrl(path) {
    // Get the base URL from the current location
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    return baseUrl + '/' + path.replace(/^\//, '');
}

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

// Initialize event listeners when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');

    if (searchInput && searchButton) {
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Add debounced search on input
        const debouncedSearch = debounce(performSearch, 500);
        searchInput.addEventListener('input', debouncedSearch);
    }

    // Filter functionality
    const userStatusFilter = document.getElementById('userStatusFilter');
    const lockedStatusFilter = document.getElementById('lockedStatusFilter');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const genderFilter = document.getElementById('genderFilter');

    if (userStatusFilter) userStatusFilter.addEventListener('change', applyFilters);
    if (lockedStatusFilter) lockedStatusFilter.addEventListener('change', applyFilters);
    if (userRoleFilter) userRoleFilter.addEventListener('change', applyFilters);
    if (genderFilter) genderFilter.addEventListener('change', applyFilters);

    // Clear filters functionality
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }

    // Pagination functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link[data-page]')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            loadUsers(page);
        }
    });

    // Modal functionality is handled in the view file
});

function performSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        currentSearch = searchInput.value.trim();
        currentPage = 1; // Reset to first page
        loadUsers();
    }
}

function applyFilters() {
    const userStatusFilter = document.getElementById('userStatusFilter');
    const lockedStatusFilter = document.getElementById('lockedStatusFilter');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const genderFilter = document.getElementById('genderFilter');
    
    currentFilters.user_status = userStatusFilter ? userStatusFilter.value : '';
    currentFilters.locked_status = lockedStatusFilter ? lockedStatusFilter.value : '';
    currentFilters.user_role = userRoleFilter ? userRoleFilter.value : '';
    currentFilters.gender = genderFilter ? genderFilter.value : '';
    currentPage = 1; // Reset to first page
    loadUsers();
}

function clearAllFilters() {
    // Clear search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        currentSearch = '';
    }

    // Clear filters
    const userStatusFilter = document.getElementById('userStatusFilter');
    const lockedStatusFilter = document.getElementById('lockedStatusFilter');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const genderFilter = document.getElementById('genderFilter');
    
    if (userStatusFilter) userStatusFilter.value = '';
    if (lockedStatusFilter) lockedStatusFilter.value = '';
    if (userRoleFilter) userRoleFilter.value = '';
    if (genderFilter) genderFilter.value = '';
    
    currentFilters = {
        user_status: '',
        locked_status: '',
        user_role: '',
        gender: ''
    };

    currentPage = 1;
    loadUsers();
}

function loadUsers(page = currentPage) {
    currentPage = page;
    
    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const usersContainer = document.getElementById('usersContainer');
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (usersContainer) usersContainer.style.display = 'none';
    if (paginationContainer) paginationContainer.style.display = 'none';

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append('controller', 'UserManagementController');
    formData.append('action', 'ajaxSearch');
    formData.append('page', currentPage);
    formData.append('search', currentSearch);
    formData.append('user_status', currentFilters.user_status);
    formData.append('locked_status', currentFilters.locked_status);
    formData.append('user_role', currentFilters.user_role);
    formData.append('gender', currentFilters.gender);

    // Make AJAX request
    fetch('index.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUsersTable(data.users);
            updatePagination(data.pagination);
            updateSearchInfo(data.totalUsers);
        } else {
            console.error('Error loading users:', data.message);
            alert('Error loading users: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('AJAX Error:', error);
        alert('Network error. Please try again.');
    })
    .finally(() => {
        // Hide loading indicator
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (usersContainer) usersContainer.style.display = 'block';
        if (paginationContainer) paginationContainer.style.display = 'block';
    });
}

function updateUsersTable(users) {
    const tbody = document.getElementById('usersTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';

    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="no-results-message">
                    <i class="fas fa-search"></i>
                    <div>
                        <h5>No users found</h5>
                        <p>Try adjusting your search terms or filters</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    users.forEach(user => {
        const userStatusBadge = user.user_status == 1 
            ? '<span class="badge bg-success">Active</span>' 
            : '<span class="badge bg-danger">Inactive</span>';
        
        const lockedStatusBadge = user.locked_status == 1 
            ? '<span class="badge bg-warning">Locked</span>' 
            : '<span class="badge bg-primary">Unlocked</span>';
        
        const lockButton = user.locked_status == 1
            ? `<a href="#" class="btn theme-btn-warning unlock-user"
                 data-id="${user.encrypted_id}"
                 data-name="${escapeHtml(user.full_name)}"
                 title="Unlock User">
                 <i class="fas fa-lock-open"></i>
               </a>`
            : `<a href="#" class="btn theme-btn-danger lock-user"
                 data-id="${user.encrypted_id}"
                 data-name="${escapeHtml(user.full_name)}"
                 title="Lock User">
                 <i class="fas fa-lock"></i>
               </a>`;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(user.profile_id)}</td>
            <td>${escapeHtml(user.full_name)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>${escapeHtml(user.contact_number)}</td>
            <td>${userStatusBadge}</td>
            <td>${lockedStatusBadge}</td>
            <td>
                <button type="button" class="btn theme-btn-primary edit-user-btn"
                        data-user-id="${user.encrypted_id}"
                        title="Edit User">
                    <i class="fas fa-edit"></i>
                </button>
                ${lockButton}
                <a href="#" class="btn theme-btn-danger delete-user"
                  data-id="${user.encrypted_id}"
                  data-name="${escapeHtml(user.full_name)}"
                  title="Delete User">
                  <i class="fas fa-trash-alt"></i>
                </a>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container) return;

    // Only show pagination if there are more than 10 total users
    if (pagination.totalUsers <= 10) {
        if (pagination.totalUsers > 0) {
            // Show total count when no pagination needed
            const plural = pagination.totalUsers !== 1 ? 's' : '';
            container.innerHTML = `
                <div class="text-center text-muted small">
                    Showing all ${pagination.totalUsers} user${plural}
                </div>
            `;
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
        return;
    }

    container.style.display = 'block';

    // Create pagination navigation
    let paginationHTML = '<nav><ul class="pagination justify-content-center" id="paginationList">';

    // Previous button
    if (pagination.currentPage > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">«
                  Previous</a>
            </li>
        `;
    }

    // Page numbers
    for (let i = 1; i <= pagination.totalPages; i++) {
        const activeClass = i === pagination.currentPage ? 'active' : '';
        paginationHTML += `
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Next button
    if (pagination.currentPage < pagination.totalPages) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Next
                  »</a>
            </li>
        `;
    }

    paginationHTML += '</ul></nav>';
    container.innerHTML = paginationHTML;
}

function updateSearchInfo(totalUsers) {
    const searchInfo = document.getElementById('searchResultsInfo');
    const resultsText = document.getElementById('resultsText');

    if (!searchInfo || !resultsText) return;

    if (currentSearch || currentFilters.user_status || currentFilters.locked_status || currentFilters.user_role || currentFilters.gender) {
        let infoText = `Showing ${totalUsers} result(s)`;

        if (currentSearch) {
            infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
        }

        if (currentFilters.user_status || currentFilters.locked_status || currentFilters.user_role || currentFilters.gender) {
            infoText += ' with filters applied';
        }

        resultsText.innerHTML = infoText;
        searchInfo.style.display = 'block';
    } else {
        searchInfo.style.display = 'none';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===================================
// MODAL FUNCTIONALITY
// ===================================

// Modal initialization will be called from main DOMContentLoaded

function initializeModals() {
    // Add User Modal
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        addUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const clientId = button ? button.getAttribute('data-client-id') : '';
            loadAddUserModalContent(clientId);
        });

        addUserModal.addEventListener('hidden.bs.modal', function() {
            // Clear modal content when closed
            const modalContent = document.getElementById('addUserModalContent');
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                `;
            }
        });
    }

    // Edit User Modal
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button ? button.getAttribute('data-user-id') : '';
            loadEditUserModalContent(userId);
        });

        editUserModal.addEventListener('hidden.bs.modal', function() {
            // Clear modal content when closed
            const modalContent = document.getElementById('editUserModalContent');
            if (modalContent) {
                modalContent.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                `;
            }
        });
    }

    // Handle edit button clicks in AJAX-generated content
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-user-btn')) {
            e.preventDefault();
            const button = e.target.closest('.edit-user-btn');
            const userId = button.getAttribute('data-user-id');

            // Show modal and load content
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
            loadEditUserModalContent(userId);
        }
    });
}

function loadAddUserModalContent(clientId = '') {
    const modalContent = document.getElementById('addUserModalContent');
    if (!modalContent) return;

    // Show loading state
    modalContent.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading form...</p>
        </div>
    `;

    // Build URL with client_id if provided
    let url = getProjectUrl('users/modal/add');
    if (clientId) {
        url += '?client_id=' + encodeURIComponent(clientId);
    }

    fetch(url)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;

            // Initialize form functionality
            initializeAddUserForm();

            // Initialize location dropdowns
            initializeLocationDropdowns('modal_');

            // Initialize timezone dropdown
            initializeTimezoneDropdown('modal_');
        })
        .catch(error => {
            console.error('Error loading add user form:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading form. Please try again.
                </div>
            `;
        });
}

function loadEditUserModalContent(userId) {
    const modalContent = document.getElementById('editUserModalContent');
    if (!modalContent) return;

    // Show loading state
    modalContent.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading form...</p>
        </div>
    `;

    const url = getProjectUrl('users/modal/edit') + '?user_id=' + encodeURIComponent(userId);

    fetch(url)
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;

            // Initialize form functionality
            initializeEditUserForm();

            // Initialize location dropdowns
            initializeLocationDropdowns('edit_modal_');

            // Initialize timezone dropdown
            initializeTimezoneDropdown('edit_modal_');
        })
        .catch(error => {
            console.error('Error loading edit user form:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading form. Please try again.
                </div>
            `;
        });
}

function initializeAddUserForm() {
    const form = document.getElementById('addUserModalForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // ✅ Run client-side validation first
        if (typeof validateModalForm === 'function') {
            const isValid = validateModalForm();
            if (!isValid) {
                // Show validation error alert
                alert('Please fix all validation errors before submitting the form. Check tabs with red borders for errors.');

                // Focus on first tab with errors
                const firstErrorTab = document.querySelector('#addUserModalTabs .nav-link.tab-error');
                if (firstErrorTab) {
                    firstErrorTab.click();
                }
                return; // Stop form submission
            }
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Saving...';
        submitBtn.disabled = true;

        const formData = new FormData(form);

        // Debug: Log form data
        console.log('🔥 Form submission data:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }

        const submitUrl = getProjectUrl('users/modal/add');
        console.log('🔥 Submitting to URL:', submitUrl);

        fetch(submitUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('🔥 Response status:', response.status);
            console.log('🔥 Response headers:', response.headers);
            console.log('🔥 Response URL:', response.url);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.text(); // Get as text first to see what we're getting
        })
        .then(responseText => {
            console.log('🔥 Raw response:', responseText);

            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('❌ Failed to parse JSON:', e);
                console.error('❌ Response was:', responseText);
                throw new Error('Invalid JSON response from server');
            }

            console.log('🔥 Parsed response data:', data);
            if (data.success) {
                // Close modal properly
                closeModalProperly('addUserModal');

                // Show success message
                showToast('success', data.message || 'User added successfully!');

                // Reload users list
                loadUsers(1);
            } else {
                // Handle validation errors - show on form fields instead of toast
                if (data.field_errors && Object.keys(data.field_errors).length > 0) {
                    // Show field-specific errors on the modal form
                    if (typeof showServerValidationErrors === 'function') {
                        showServerValidationErrors(data.field_errors);
                    } else {
                        // Fallback: show individual field errors
                        Object.keys(data.field_errors).forEach(fieldName => {
                            const field = document.querySelector(`[name="${fieldName}"]`);
                            if (field) {
                                showFieldValidationError(field, data.field_errors[fieldName]);
                            }
                        });
                    }

                    // Focus on first tab with errors
                    const firstErrorTab = document.querySelector('#addUserModalTabs .nav-link.tab-error');
                    if (firstErrorTab) {
                        firstErrorTab.click();
                    }
                } else {
                    // General error - show as toast
                    showToast('error', data.message || 'Error adding user');
                }
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            showToast('error', 'Network error. Please try again.');
        })
        .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
}

function initializeEditUserForm() {
    // Wait for the form to be properly loaded with multiple retries
    let attempts = 0;
    const maxAttempts = 10;

    function tryInitialize() {
        attempts++;
        const form = document.getElementById('editUserModalForm');

        if (form) {
            // Set up basic validation
            setupEditModalValidation(form);

            // Initialize location dropdowns with proper prefix
            if (typeof initializeLocationDropdowns === 'function') {
                initializeLocationDropdowns('edit_modal_');
            }

            // Initialize timezone dropdown
            if (typeof initializeTimezoneDropdown === 'function') {
                initializeTimezoneDropdown('edit_modal_');
            }

            return; // Success, exit
        }

        if (attempts < maxAttempts) {
            setTimeout(tryInitialize, 300);
        }
    }

    // Start trying immediately
    tryInitialize();
}

function setupEditModalValidation(form) {
    // Remove any existing event listeners to prevent duplicates
    const fields = form.querySelectorAll('input, select, textarea');

    fields.forEach(field => {
        // Remove existing blur listeners
        field.removeEventListener('blur', handleEditModalFieldValidation);
        // Add new blur listener
        field.addEventListener('blur', handleEditModalFieldValidation);
    });

    // Handle form submission
    form.removeEventListener('submit', handleEditModalFormSubmit);
    form.addEventListener('submit', handleEditModalFormSubmit);

    // Validation setup complete
}

function handleEditModalFieldValidation(e) {
    const field = e.target;
    if (typeof validateEditModalField === 'function') {
        validateEditModalField(field);
    }
}

function handleEditModalFormSubmit(e) {
    e.preventDefault();
    console.log('🔥 Edit user form submitted');

    const form = e.target;

    // Validate the entire form
    let isValid = true;
    if (typeof validateEditModalForm === 'function') {
        isValid = validateEditModalForm();
    }

    if (!isValid) {
        console.log('❌ Edit user validation failed');
        alert('Please fix all validation errors before submitting the form.');
        return false;
    }

    console.log('✅ Edit user validation passed, submitting via AJAX');

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
    submitBtn.disabled = true;

    const formData = new FormData(form);

    // Debug: Log form data
    console.log('🔥 Edit user form submission data:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    fetch(getProjectUrl('users/modal/edit'), {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('🔥 Edit user response status:', response.status);
        console.log('🔥 Edit user response URL:', response.url);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.text(); // Get as text first to see what we're getting
    })
    .then(responseText => {
        console.log('🔥 Edit user raw response:', responseText);

        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('❌ Failed to parse JSON:', e);
            console.error('❌ Response was:', responseText);
            throw new Error('Invalid JSON response from server');
        }

        console.log('🔥 Edit user parsed response data:', data);

        if (data.success) {
            // Close modal properly
            closeModalProperly('editUserModal');

            // Show success message
            showToast('success', data.message || 'User updated successfully!');

            // Reload users list
            loadUsers(currentPage);
        } else {
            // Handle validation errors
            if (data.field_errors && Object.keys(data.field_errors).length > 0) {
                console.log('🔥 Showing field-specific errors:', data.field_errors);
                Object.keys(data.field_errors).forEach(fieldName => {
                    const field = document.querySelector(`#editUserModalForm [name="${fieldName}"]`);
                    if (field && typeof showEditModalError === 'function') {
                        showEditModalError(field, data.field_errors[fieldName]);
                    }
                });

                // Update tab highlighting
                if (typeof updateEditModalTabHighlighting === 'function') {
                    updateEditModalTabHighlighting();
                }

                // Focus on first tab with errors
                const firstErrorTab = document.querySelector('#editUserModalTabs .nav-link.tab-error');
                if (firstErrorTab) {
                    firstErrorTab.click();
                }
            } else {
                // General error - show as toast
                showToast('error', data.message || 'Error updating user');
            }
        }
    })
    .catch(error => {
        console.error('Error submitting form:', error);
        showToast('error', 'Network error. Please try again.');
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Helper function to show field validation errors (consistent with VLR packages)
function showFieldValidationError(input, message) {
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

    // Add Bootstrap error styling (consistent with VLR packages)
    input.classList.add("is-invalid");
}

// Helper function to hide field validation errors
function hideFieldValidationError(input) {
    let errorElement = input.parentNode.querySelector(".error-message");
    if (errorElement) {
        errorElement.textContent = "";
    }
    input.classList.remove("is-invalid");
}

// Helper function to properly close Bootstrap modals and remove backdrop
function closeModalProperly(modalId) {
    console.log('🔥 Closing modal:', modalId);

    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        console.log('❌ Modal element not found:', modalId);
        return;
    }

    let modal = bootstrap.Modal.getInstance(modalElement);

    if (!modal) {
        console.log('🔥 No modal instance found, creating new one');
        modal = new bootstrap.Modal(modalElement);
    }

    // Hide the modal
    modal.hide();

    // Force cleanup after animation completes
    setTimeout(() => {
        console.log('🔥 Forcing modal cleanup');

        // Remove any remaining backdrops
        const backdrops = document.querySelectorAll('.modal-backdrop');
        console.log('🔥 Found', backdrops.length, 'backdrops to remove');
        backdrops.forEach(backdrop => {
            console.log('🔥 Removing backdrop');
            backdrop.remove();
        });

        // Remove modal-open class from body
        document.body.classList.remove('modal-open');

        // Reset body styles
        document.body.style.paddingRight = '';
        document.body.style.overflow = '';

        // Ensure modal is hidden
        modalElement.classList.remove('show');
        modalElement.style.display = 'none';
        modalElement.setAttribute('aria-hidden', 'true');
        modalElement.removeAttribute('aria-modal');

        console.log('✅ Modal cleanup completed');
    }, 300);
}

// Helper function to show toast notifications
function showToast(type, message) {
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    // Add to toast container or create one
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    // Show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    toast.show();

    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', function() {
        toastElement.remove();
    });
}

// Helper functions for location and timezone dropdowns
function initializeLocationDropdowns(prefix = '') {
    console.log('🔥 initializeLocationDropdowns called with prefix:', prefix);
    const countrySelect = document.getElementById(prefix + 'countrySelect');
    const stateSelect = document.getElementById(prefix + 'stateSelect');
    const citySelect = document.getElementById(prefix + 'citySelect');
    const timezoneSelect = document.getElementById(prefix + 'timezoneSelect');

    console.log('Found elements:', {
        country: !!countrySelect,
        state: !!stateSelect,
        city: !!citySelect,
        timezone: !!timezoneSelect
    });

    if (countrySelect && stateSelect && citySelect) {
        console.log('✅ Setting up location dropdown listeners');
        countrySelect.addEventListener('change', function() {
            console.log('🔥 Country changed to:', this.value);
            loadStates(this.value, stateSelect, citySelect);

            // Also load timezones for the selected country
            if (timezoneSelect) {
                loadTimezonesByCountry(this.value, timezoneSelect);
            }
        });

        stateSelect.addEventListener('change', function() {
            console.log('🔥 State changed to:', this.value);
            loadCities(this.value, citySelect);
        });
    } else {
        console.log('❌ Missing location dropdown elements');
    }
}

function initializeTimezoneDropdown(prefix = '') {
    const timezoneSelect = document.getElementById(prefix + 'timezoneSelect');
    if (timezoneSelect) {
        loadTimezones(timezoneSelect);
    }
}

function loadStates(countryId, stateSelect, citySelect) {
    console.log('🔥 loadStates called with countryId:', countryId);

    if (!countryId) {
        console.log('❌ No country ID provided');
        stateSelect.innerHTML = '<option value="">Select State</option>';
        stateSelect.disabled = true;
        citySelect.innerHTML = '<option value="">Select City</option>';
        citySelect.disabled = true;
        return;
    }

    console.log('📡 Loading states for country:', countryId);
    stateSelect.innerHTML = '<option value="">Loading...</option>';
    stateSelect.disabled = true;
    citySelect.innerHTML = '<option value="">Select City</option>';
    citySelect.disabled = true;

    const apiUrl = getProjectUrl('api/locations/states');
    console.log('📡 API URL:', apiUrl);
    console.log('📡 Request body:', `country_id=${countryId}`);

    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `country_id=${countryId}`
    })
    .then(response => {
        console.log('📡 States API response status:', response.status);
        console.log('📡 States API response headers:', response.headers);
        return response.json();
    })
    .then(data => {
        console.log('📡 States API response data:', data);
        stateSelect.innerHTML = '<option value="">Select State</option>';

        // Check if data is an array (direct response) or has success property
        if (Array.isArray(data)) {
            console.log('✅ States data is array, length:', data.length);
            data.forEach(state => {
                stateSelect.innerHTML += `<option value="${state.id}">${state.name}</option>`;
            });
        } else if (data.success && data.states) {
            console.log('✅ States data has success property, states length:', data.states.length);
            data.states.forEach(state => {
                stateSelect.innerHTML += `<option value="${state.id}">${state.name}</option>`;
            });
        } else {
            console.log('❌ Unexpected states data format:', data);
        }
        stateSelect.disabled = false;
    })
    .catch(error => {
        console.error('❌ Error loading states:', error);
        stateSelect.innerHTML = '<option value="">Error loading states</option>';
    });
}

function loadCities(stateId, citySelect) {
    console.log('🔥 loadCities called with stateId:', stateId);

    if (!stateId) {
        console.log('❌ No state ID provided');
        citySelect.innerHTML = '<option value="">Select City</option>';
        citySelect.disabled = true;
        return;
    }

    console.log('📡 Loading cities for state:', stateId);
    citySelect.innerHTML = '<option value="">Loading...</option>';
    citySelect.disabled = true;

    const apiUrl = getProjectUrl('api/locations/cities');
    console.log('📡 Cities API URL:', apiUrl);
    console.log('📡 Cities request body:', `state_id=${stateId}`);

    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `state_id=${stateId}`
    })
    .then(response => {
        console.log('📡 Cities API response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('📡 Cities API response data:', data);
        citySelect.innerHTML = '<option value="">Select City</option>';

        // Check if data is an array (direct response) or has success property
        if (Array.isArray(data)) {
            console.log('✅ Cities data is array, length:', data.length);
            data.forEach(city => {
                citySelect.innerHTML += `<option value="${city.id}">${city.name}</option>`;
            });
        } else if (data.success && data.cities) {
            console.log('✅ Cities data has success property, cities length:', data.cities.length);
            data.cities.forEach(city => {
                citySelect.innerHTML += `<option value="${city.id}">${city.name}</option>`;
            });
        } else {
            console.log('❌ Unexpected cities data format:', data);
        }
        citySelect.disabled = false;
    })
    .catch(error => {
        console.error('❌ Error loading cities:', error);
        citySelect.innerHTML = '<option value="">Error loading cities</option>';
    });
}

function loadTimezones(timezoneSelect) {
    // Common timezones - you can expand this list
    const timezones = [
        'UTC',
        'America/New_York',
        'America/Chicago',
        'America/Denver',
        'America/Los_Angeles',
        'Europe/London',
        'Europe/Paris',
        'Europe/Berlin',
        'Asia/Tokyo',
        'Asia/Shanghai',
        'Asia/Kolkata',
        'Australia/Sydney'
    ];

    timezones.forEach(timezone => {
        timezoneSelect.innerHTML += `<option value="${timezone}">${timezone}</option>`;
    });
}

function loadTimezonesByCountry(countryId, timezoneSelect) {
    console.log('🔥 loadTimezonesByCountry called with countryId:', countryId);

    if (!countryId) {
        console.log('❌ No country ID provided for timezones');
        // Load default timezones
        timezoneSelect.innerHTML = '<option value="">Select Timezone</option>';
        loadTimezones(timezoneSelect);
        return;
    }

    console.log('📡 Loading timezones for country:', countryId);
    timezoneSelect.innerHTML = '<option value="">Loading timezones...</option>';

    const apiUrl = getProjectUrl('api/locations/timezones');
    console.log('📡 Timezones API URL:', apiUrl);
    console.log('📡 Timezones request body:', `country_id=${countryId}`);

    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `country_id=${countryId}`
    })
    .then(response => {
        console.log('📡 Timezones API response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('📡 Timezones API response data:', data);
        timezoneSelect.innerHTML = '<option value="">Select Timezone</option>';

        if (data.success && data.timezones && data.timezones.length > 0) {
            console.log('✅ Timezones data found, length:', data.timezones.length);
            data.timezones.forEach(timezone => {
                const displayName = timezone.tzName || timezone.zoneName || timezone.abbreviation;
                const value = timezone.zoneName || timezone.tzName;
                timezoneSelect.innerHTML += `<option value="${value}">${displayName} (${timezone.gmtOffsetName || ''})</option>`;
            });
        } else {
            console.log('❌ No timezones found for country, loading default timezones');
            // Fallback to default timezones
            loadTimezones(timezoneSelect);
        }
    })
    .catch(error => {
        console.error('❌ Error loading timezones:', error);
        timezoneSelect.innerHTML = '<option value="">Select Timezone</option>';
        // Fallback to default timezones
        loadTimezones(timezoneSelect);
    });
}
