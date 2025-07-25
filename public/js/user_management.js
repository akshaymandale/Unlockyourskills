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
    const baseUrl = window.location.origin + '/Unlockyourskills/';
    return baseUrl + path.replace(/^\//, '');
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
    // Only run user management logic if the user management table is present
    if (document.getElementById('usersTableBody')) {
        // Initialize modals (setting up the 'show' event listeners)
        initializeModals();

        // Initial load of users
        loadUsers(1);

        // Setup event listeners for filters and search
        // Using debounce to prevent excessive AJAX calls on every keystroke
        const debouncedSearch = debounce(performSearch, 300);
        document.getElementById('searchInput').addEventListener('keyup', debouncedSearch);

        document.getElementById('userStatusFilter').addEventListener('change', applyFilters);
        document.getElementById('lockedStatusFilter').addEventListener('change', applyFilters);
        document.getElementById('userRoleFilter').addEventListener('change', applyFilters);
        document.getElementById('genderFilter').addEventListener('change', applyFilters);
        document.getElementById('clearFiltersBtn').addEventListener('click', clearAllFilters);
    }

    // Navbar Edit Profile button (global)
    const editProfileBtn = document.getElementById('editProfileBtn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Use the current user's ID from a global JS variable or data attribute
            let userId = null;
            if (window.currentUserId) {
                userId = window.currentUserId;
            } else if (typeof CURRENT_USER_ID !== 'undefined') {
                userId = CURRENT_USER_ID;
            } else if (editProfileBtn.dataset.userId) {
                userId = editProfileBtn.dataset.userId;
            } else {
                // fallback: try to get from session injected into JS
                userId = editProfileBtn.getAttribute('data-user-id');
            }
            if (!userId) {
                // Try to get from meta tag or global
                const metaUserId = document.querySelector('meta[name="current-user-id"]');
                if (metaUserId) userId = metaUserId.getAttribute('content');
            }
            if (!userId) {
                alert('Could not determine current user ID for profile editing.');
                return;
            }
            // Show the modal and load content
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
            loadEditUserModalContent(userId);
        });
    }
});

function performSearch() {
    currentPage = 1;
    loadUsers(currentPage);
}

function applyFilters() {
    currentPage = 1;
    loadUsers(currentPage);
}

function clearAllFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('userStatusFilter').value = '';
    document.getElementById('lockedStatusFilter').value = '';
    document.getElementById('userRoleFilter').value = '';
    document.getElementById('genderFilter').value = '';
    currentPage = 1;
    loadUsers(currentPage);
}

function loadUsers(page = 1) {
    currentPage = page;
    const searchInput = document.getElementById('searchInput').value;
    const userStatus = document.getElementById('userStatusFilter').value;
    const lockedStatus = document.getElementById('lockedStatusFilter').value;
    const userRole = document.getElementById('userRoleFilter').value;
    const gender = document.getElementById('genderFilter').value;
    
    // Update current search and filters
    currentSearch = searchInput;
    currentFilters.user_status = userStatus;
    currentFilters.locked_status = lockedStatus;
    currentFilters.user_role = userRole;
    currentFilters.gender = gender;
    
    const loadingIndicator = document.getElementById('loadingIndicator');
    const usersTableBody = document.getElementById('usersTableBody');
    const paginationContainer = document.getElementById('paginationContainer');

    loadingIndicator.style.display = 'block';
    usersTableBody.innerHTML = '';

    // Determine if we are in client management mode
    let clientId = null;
    
    // First try to get from URL search parameters (for backward compatibility)
    const urlParams = new URLSearchParams(window.location.search);
    clientId = urlParams.get('client_id');
    
    // If not found in search params, try to extract from URL path /clients/{id}/users
    if (!clientId) {
        const pathParts = window.location.pathname.split('/');
        const clientsIndex = pathParts.indexOf('clients');
        if (clientsIndex !== -1 && clientsIndex + 1 < pathParts.length && pathParts[clientsIndex + 2] === 'users') {
            clientId = pathParts[clientsIndex + 1];
            console.log('🔥 External JS: Extracted client_id from URL path:', clientId);
        }
    }
    
    // Determine the correct AJAX URL based on client context
    let ajaxUrl;
    if (clientId && !isNaN(clientId)) {
        // Use client-specific endpoint for better session handling
        ajaxUrl = getProjectUrl('clients/' + clientId + '/users/ajax/search');
        console.log('🔥 External JS: Using client-specific AJAX URL:', ajaxUrl);
    } else {
        ajaxUrl = getProjectUrl('users/ajax/search');
        console.log('🔥 External JS: Using general AJAX URL:', ajaxUrl);
    }
    
    const params = new URLSearchParams({
        page,
        search: searchInput,
        user_status: userStatus,
        locked_status: lockedStatus,
        user_role: userRole,
        gender
    });

    if (clientId && !isNaN(clientId)) {
        params.append('client_id', clientId);
        console.log('🔥 External JS: Adding client_id to AJAX request:', clientId);
    }
    
    fetch(ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: params.toString()
    })
    .then(response => response.json())
    .then(data => {
        updateUsersTable(data.users);
        updatePagination(data.pagination);
        updateSearchInfo(data.pagination.totalUsers);
    })
    .catch(error => {
        console.error('Error fetching users:', error);
    })
    .finally(() => {
        loadingIndicator.style.display = 'none';
    });
}

function updateUsersTable(users) {
    const usersTableBody = document.getElementById('usersTableBody');
    usersTableBody.innerHTML = ''; // Clear existing rows

    if (users.length === 0) {
        usersTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No users found.</td></tr>';
        return;
    }

    users.forEach(user => {
        const userStatusBadge = user.user_status === 'Active' ? `<span class="badge bg-success">${user.user_status}</span>` : `<span class="badge bg-danger">${user.user_status}</span>`;
        const lockedStatusBadge = user.locked_status === '1' ? `<span class="badge bg-warning">Locked</span>` : `<span class="badge bg-secondary">Unlocked</span>`;
        
        // Check if user is super admin (disable actions)
        const isSuperAdmin = (user.system_role === 'super_admin' || user.user_role === 'Super Admin');
        const disabledClass = isSuperAdmin ? 'disabled' : '';
        const disabledStyle = isSuperAdmin ? 'style="pointer-events: none; opacity: 0.5; cursor: not-allowed;"' : '';
        
        // Permission-aware action buttons
        let actionButtons = '';
        if (canEditUser && !isSuperAdmin) {
            actionButtons += `<button class="btn btn-sm btn-outline-primary edit-user-btn" data-user-id="${user.encrypted_id}" title="Edit User"><i class="fas fa-edit"></i></button>`;
        }
        if (canLockUser && !isSuperAdmin) {
            actionButtons += (user.locked_status == '1')
                ? `<button class="btn btn-sm btn-outline-warning unlock-user" data-id="${user.encrypted_id}" data-name="${escapeHtml(user.full_name || '')}" title="Unlock User"><i class="fas fa-lock-open"></i></button>`
                : `<button class="btn btn-sm btn-outline-danger lock-user" data-id="${user.encrypted_id}" data-name="${escapeHtml(user.full_name || '')}" title="Lock User"><i class="fas fa-lock"></i></button>`;
        }
        if (canDeleteUser && !isSuperAdmin) {
            actionButtons += `<button class="btn btn-sm btn-outline-danger delete-user" data-id="${user.encrypted_id}" data-name="${escapeHtml(user.full_name || '')}" title="Delete User"><i class="fas fa-trash"></i></button>`;
        }

        const row = `
            <tr>
                <td>${escapeHtml(user.profile_id || '')}</td>
                <td>${escapeHtml(user.full_name || '')}</td>
                <td>${escapeHtml(user.email || '')}</td>
                <td>${escapeHtml(user.contact_number || '')}</td>
            <td>${userStatusBadge}</td>
            <td>${lockedStatusBadge}</td>
            <td>
                ${actionButtons}
            </td>
            </tr>
        `;
        usersTableBody.insertAdjacentHTML('beforeend', row);
    });
}

function updatePagination(pagination) {
    const paginationContainer = document.getElementById('paginationContainer');
    paginationContainer.innerHTML = ''; // Clear existing pagination

    // Only show pagination if there are more than 10 records (more than 1 page with 10 per page)
    if (pagination.totalUsers <= 10) return;

    let paginationHtml = '<nav><ul class="pagination justify-content-center">';

    // Previous button
    paginationHtml += `
        <li class="page-item ${pagination.currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadUsers(${pagination.currentPage - 1});">Previous</a>
            </li>
        `;

    // Page numbers
    for (let i = 1; i <= pagination.totalPages; i++) {
        paginationHtml += `
            <li class="page-item ${pagination.currentPage === i ? 'active' : ''}">
                <a class="page-link" href="#" onclick="event.preventDefault(); loadUsers(${i});">${i}</a>
            </li>
        `;
    }

    // Next button
    paginationHtml += `
        <li class="page-item ${pagination.currentPage === pagination.totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="event.preventDefault(); loadUsers(${pagination.currentPage + 1});">Next</a>
            </li>
        `;

    paginationHtml += '</ul></nav>';
    paginationContainer.innerHTML = paginationHtml;
}

function updateSearchInfo(totalUsers) {
    const searchInfo = document.getElementById('searchResultsInfo');
    const resultsText = document.getElementById('resultsText');
    
    if (!searchInfo || !resultsText) return;

    // Check if there are any active filters or search
    const hasFilters = currentSearch || currentFilters.user_status || currentFilters.locked_status || currentFilters.user_role || currentFilters.gender;
    
    if (hasFilters) {
        // Handle undefined/null values
        const count = totalUsers || 0;
        let infoText = `Showing ${count} result(s)`;
        
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
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

// ===================================
// MODAL FUNCTIONALITY
// ===================================

/**
 * Sets up the initial event listeners for the Add and Edit modals.
 * This function only runs once on page load.
 */
function initializeModals() {
    console.log('🔥 External JS: initializeModals called');
    const addUserModal = document.getElementById('addUserModal');
    console.log('🔥 External JS: Add user modal found:', !!addUserModal);
    
    if (addUserModal) {
        addUserModal.addEventListener('show.bs.modal', function(event) {
            console.log('🔥 External JS: Add user modal show event fired');
            const button = event.relatedTarget;
            const clientId = button ? button.getAttribute('data-client-id') : '';
            console.log('🔥 External JS: Client ID from button:', clientId);
            loadAddUserModalContent(clientId);
        });
        // Add a listener to clear content when the modal is hidden to ensure it's fresh next time.
        addUserModal.addEventListener('hidden.bs.modal', function() {
            console.log('🔥 External JS: Add user modal hidden event fired');
            const modalContent = document.getElementById('addUserModalContent');
            if(modalContent) modalContent.innerHTML = '';
        });
    }

    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        editUserModal.addEventListener('show.bs.modal', function(event) {
            // Try to get userId from data attribute if relatedTarget is missing
            let userId = '';
            if (event.relatedTarget) {
                userId = event.relatedTarget.getAttribute('data-user-id');
            }
            if (!userId) {
                userId = editUserModal.getAttribute('data-user-id');
            }
            console.log('[DEBUG] Edit User modal show.bs.modal event. userId:', userId);
            if (userId) {
                loadEditUserModalContent(userId);
            } else {
                console.warn('[DEBUG] No userId found on show.bs.modal event.');
            }
        });
        editUserModal.addEventListener('hidden.bs.modal', function() {
            const modalContent = document.getElementById('editUserModalContent');
            if(modalContent) modalContent.innerHTML = '';
            // Clean up the data attribute
            editUserModal.removeAttribute('data-user-id');
        });
    }
    
    // This handles clicks on edit buttons that are added to the page dynamically via AJAX.
    document.addEventListener('click', function (event) {
        const editButton = event.target.closest('.edit-user-btn');
        if (editButton) {
            const userId = editButton.getAttribute('data-user-id');
            console.log('[DEBUG] Edit User button clicked. userId:', userId);
            const modalEl = document.getElementById('editUserModal');
            // Set the userId as a data attribute on the modal
            modalEl.setAttribute('data-user-id', userId);
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            // The 'show.bs.modal' event will handle loading the content.
        }
    });
    }

/**
 * Attaches a robust, delegated event listener to a modal's content area.
 * This is the definitive validation handler for all modal interactions.
 * It listens for both 'click' (on submit) and 'focusout' (on fields).
 * @param {HTMLElement} modalContent - The element containing the modal's body.
 */
function attachValidationHandler(modalContent) {
    const masterEventHandler = (event) => {
        if (event.type === 'click') {
            const submitButton = event.target.closest('button[type="submit"]');
            if (submitButton) {
                console.log('DEBUG: Click event on submit button detected by masterEventHandler');
                event.preventDefault();
                event.stopPropagation();
                const form = submitButton.closest('form');
                if (!form) return;
                if (form.id === 'addUserModalForm' && typeof validateAddUserModal === 'function') {
                    console.log('DEBUG: Calling validateAddUserModal');
                    if (validateAddUserModal()) {
                        console.log('DEBUG: Validation passed, calling submitAddUserModal');
                        submitAddUserModal();
                    }
                } else if (form.id === 'editUserModalForm' && typeof validateEditUserModal === 'function') {
                    if (validateEditUserModal()) {
                        submitEditUserModal();
                    }
                }
            }
        }
        if (event.type === 'focusout') {
            const field = event.target.closest('input, select, textarea');
            if (field) {
                console.log('DEBUG: Focusout event on field:', field.id);
                const form = field.closest('form');
                if (!form) return;
                if (form.id === 'addUserModalForm' && typeof validateField === 'function') {
                    validateField(field);
                } else if (form.id === 'editUserModalForm' && typeof validateEditField === 'function') {
                    validateEditField(field); 
                }
            }
        }
    };
    modalContent.removeEventListener('click', masterEventHandler, { capture: true });
    modalContent.removeEventListener('focusout', masterEventHandler, { capture: true });
    modalContent.addEventListener('click', masterEventHandler, { capture: true });
    modalContent.addEventListener('focusout', masterEventHandler, { capture: true });
}

/**
 * Loads the content for the Add User modal and attaches the validation handler.
 */
function loadAddUserModalContent(clientId = '') {
    console.log('🔥 External JS: loadAddUserModalContent called with clientId:', clientId);
    const modalContent = document.getElementById('addUserModalContent');
    if (!modalContent) {
        console.error('🔥 External JS: Modal content element not found');
        return;
    }
    
    modalContent.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

    let url = getProjectUrl('users/modal/add');
    if (clientId) url += '?client_id=' + encodeURIComponent(clientId);
    
    console.log('🔥 External JS: Fetching URL:', url);

    fetch(url, {
        credentials: 'include'
    })
        .then(response => {
            console.log('🔥 External JS: Response status:', response.status);
            return response.text();
        })
        .then(html => {
            console.log('🔥 External JS: Received HTML length:', html.length);
            console.log('🔥 External JS: HTML preview:', html.substring(0, 200));
            modalContent.innerHTML = html;

            // Initialize validation for the Add User modal
            if (typeof initializeAddUserModalValidation === 'function') {
                console.log('🔥 External JS: Calling initializeAddUserModalValidation');
                initializeAddUserModalValidation();
            }

            if (typeof initializeLocationDropdowns === 'function') {
                console.log('🔥 External JS: Calling initializeLocationDropdowns');
                initializeLocationDropdowns('modal_');
            }
            if (typeof initializeTimezoneDropdown === 'function') {
                console.log('🔥 External JS: Calling initializeTimezoneDropdown');
                initializeTimezoneDropdown('modal_');
            }
        })
        .catch(error => {
            console.error('🔥 External JS: Error loading add user form:', error);
            modalContent.innerHTML = `<div class="alert alert-danger p-4">Error loading form. Please close this modal and try again.</div>`;
        });
}

/**
 * Loads the content for the Edit User modal and attaches the validation handler.
 */
function loadEditUserModalContent(userId) {
    const modalContent = document.getElementById('editUserModalContent');
    if (!modalContent) return;

    modalContent.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

    const url = getProjectUrl('users/modal/edit') + '?user_id=' + encodeURIComponent(userId);
    console.log('[DEBUG] Making AJAX call to:', url);

    fetch(url, {
        credentials: 'include'
    })
        .then(response => response.text())
        .then(html => {
            modalContent.innerHTML = html;
            if (typeof initializeEditModalValidation === 'function') {
                initializeEditModalValidation();
            }
            if (typeof initializeLocationDropdowns === 'function') initializeLocationDropdowns('edit_modal_');
            if (typeof initializeTimezoneDropdown === 'function') initializeTimezoneDropdown('edit_modal_');
        })
        .catch(error => {
            console.error('Error loading edit user form:', error);
            modalContent.innerHTML = `<div class="alert alert-danger p-4">Error loading form. Please close this modal and try again.</div>`;
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

/**
 * Search users
 */
function searchUsers() {
    console.log('searchUsers called');
    
    // Get all filter values
    const searchInput = document.getElementById('searchInput');
    const userStatusFilter = document.getElementById('userStatusFilter');
    const lockedStatusFilter = document.getElementById('lockedStatusFilter');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const genderFilter = document.getElementById('genderFilter');
    
    // Update current filters
    currentSearch = searchInput ? searchInput.value.trim() : '';
    currentFilters.user_status = userStatusFilter ? userStatusFilter.value : '';
    currentFilters.locked_status = lockedStatusFilter ? lockedStatusFilter.value : '';
    currentFilters.user_role = userRoleFilter ? userRoleFilter.value : '';
    currentFilters.gender = genderFilter ? genderFilter.value : '';
    currentFilters.page = 1;
    
    console.log('Updated filters:', currentFilters);
    console.log('Current search:', currentSearch);
    
    loadUsers(1);
}

/**
 * Reset filters
 */
function resetFilters() {
    console.log('resetFilters called');
    
    // Reset all filter inputs
    const searchInput = document.getElementById('searchInput');
    const userStatusFilter = document.getElementById('userStatusFilter');
    const lockedStatusFilter = document.getElementById('lockedStatusFilter');
    const userRoleFilter = document.getElementById('userRoleFilter');
    const genderFilter = document.getElementById('genderFilter');
    
    if (searchInput) searchInput.value = '';
    if (userStatusFilter) userStatusFilter.value = '';
    if (lockedStatusFilter) lockedStatusFilter.value = '';
    if (userRoleFilter) userRoleFilter.value = '';
    if (genderFilter) genderFilter.value = '';
    
    // Reset current filters
    currentSearch = '';
    currentFilters.user_status = '';
    currentFilters.locked_status = '';
    currentFilters.user_role = '';
    currentFilters.gender = '';
    currentFilters.page = 1;
    
    console.log('Filters reset:', currentFilters);
    
    loadUsers(1);
}
