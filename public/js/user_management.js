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
                 data-id="${user.profile_id}"
                 data-name="${escapeHtml(user.full_name)}"
                 title="Unlock User">
                 <i class="fas fa-lock-open"></i>
               </a>`
            : `<a href="#" class="btn theme-btn-danger lock-user"
                 data-id="${user.profile_id}"
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
                <a href="index.php?controller=UserManagementController&action=editUser&id=${user.profile_id}"
                  class="btn theme-btn-primary"
                  title="Edit User">
                  <i class="fas fa-edit"></i>
                </a>
                ${lockButton}
                <a href="#" class="btn theme-btn-danger delete-user"
                  data-id="${user.profile_id}"
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
