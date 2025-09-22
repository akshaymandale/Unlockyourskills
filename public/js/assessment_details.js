/**
 * Assessment Details Page JavaScript
 * Handles course selection, context loading, user management, and attempt increases
 */

// Global variables
let selectedCourseId = null;
let selectedContext = null;
let selectedUsers = [];
let allUsers = [];
let allCourses = [];

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Store all courses data for filtering
    const courseItems = document.querySelectorAll('.course-item');
    allCourses = Array.from(courseItems).map(item => ({
        id: item.dataset.courseId,
        name: item.querySelector('.course-name').textContent,
        failedUsers: item.dataset.failedUsers,
        failedAssessments: item.dataset.failedAssessments,
        element: item
    }));
    
    // Load history on page load
    loadHistory();
});

/**
 * Filter courses based on search input
 */
function filterCourses() {
    const searchInput = document.getElementById('courseSearch');
    const searchTerm = searchInput.value.toLowerCase();
    const courseList = document.getElementById('courseList');
    
    if (searchTerm.length === 0) {
        // Show all courses
        allCourses.forEach(course => {
            course.element.style.display = 'block';
        });
    } else {
        // Filter courses
        allCourses.forEach(course => {
            const courseName = course.name.toLowerCase();
            if (courseName.includes(searchTerm)) {
                course.element.style.display = 'block';
            } else {
                course.element.style.display = 'none';
            }
        });
    }
    
    // Show dropdown if there are visible courses
    const visibleCourses = allCourses.filter(course => course.element.style.display !== 'none');
    if (visibleCourses.length > 0) {
        showCourseDropdown();
    } else {
        hideCourseDropdown();
    }
}

/**
 * Show course dropdown
 */
function showCourseDropdown() {
    const dropdown = document.getElementById('courseDropdown');
    dropdown.style.display = 'block';
}

/**
 * Hide course dropdown
 */
function hideCourseDropdown() {
    // Add a small delay to allow click events to fire
    setTimeout(() => {
        const dropdown = document.getElementById('courseDropdown');
        dropdown.style.display = 'none';
    }, 150);
}

/**
 * Select a course from the dropdown
 */
function selectCourse(courseId, courseName, failedUsers, failedAssessments) {
    selectedCourseId = courseId;
    
    // Update the search input
    const searchInput = document.getElementById('courseSearch');
    searchInput.value = courseName;
    
    // Hide the dropdown
    hideCourseDropdown();
    
    // Show selected course info
    showSelectedCourse(courseId, courseName, failedUsers, failedAssessments);
    
    // Load assessment contexts
    loadAssessmentContexts();
}

/**
 * Show selected course information
 */
function showSelectedCourse(courseId, courseName, failedUsers, failedAssessments) {
    const container = document.querySelector('.autocomplete-container');
    
    // Remove existing selected course display
    const existingSelected = container.querySelector('.selected-course');
    if (existingSelected) {
        existingSelected.remove();
    }
    
    // Create selected course display
    const selectedDiv = document.createElement('div');
    selectedDiv.className = 'selected-course';
    selectedDiv.innerHTML = `
        <div class="course-info">
            <div class="course-name">${courseName}</div>
            <div class="course-stats">
                <span class="badge bg-danger">${failedUsers} failed users</span>
                <span class="badge bg-warning">${failedAssessments} assessments</span>
            </div>
        </div>
        <button type="button" class="remove-btn" onclick="clearCourseSelection()" title="Remove selection">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(selectedDiv);
}

/**
 * Clear course selection
 */
function clearCourseSelection() {
    selectedCourseId = null;
    selectedContext = null;
    selectedUsers = [];
    
    // Clear search input
    document.getElementById('courseSearch').value = '';
    
    // Remove selected course display
    const selectedCourse = document.querySelector('.selected-course');
    if (selectedCourse) {
        selectedCourse.remove();
    }
    
    // Hide all cards
    document.getElementById('contextCard').style.display = 'none';
    document.getElementById('userCard').style.display = 'none';
    document.getElementById('increaseCard').style.display = 'none';
    
    // Reset all course items visibility
    allCourses.forEach(course => {
        course.element.style.display = 'block';
    });
}

/**
 * Load assessment contexts when course is selected
 */
function loadAssessmentContexts() {
    console.log('loadAssessmentContexts called');
    console.log('Selected course ID:', selectedCourseId);
    
    if (!selectedCourseId) {
        document.getElementById('contextCard').style.display = 'none';
        return;
    }
    
    document.getElementById('contextCard').style.display = 'block';
    
    const url = `assessment-details/contexts?course_id=${selectedCourseId}`;
    console.log('Fetching URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('Response received:', response);
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.success) {
                displayContexts(data.contexts);
            } else {
                console.error('API Error:', data.message);
                showAlert('Error loading assessment contexts: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showAlert('Error loading assessment contexts', 'danger');
        });
}

/**
 * Display assessment contexts
 */
function displayContexts(contexts) {
    console.log('displayContexts called with:', contexts);
    const container = document.getElementById('contextsContainer');
    
    if (!container) {
        console.error('contextsContainer element not found!');
        return;
    }
    
    if (contexts.length === 0) {
        console.log('No contexts found, showing warning');
        container.innerHTML = '<div class="alert alert-warning">No failed assessments found for this course.</div>';
        return;
    }
    
    console.log('Displaying', contexts.length, 'contexts');
    
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += '<thead><tr><th>Assessment Title</th><th>Context Type</th><th>Module</th><th>Max Attempts</th><th>Failed Users</th><th>Action</th></tr></thead>';
    html += '<tbody>';
    
    contexts.forEach(context => {
        const contextTypeLabel = context.context_type.charAt(0).toUpperCase() + context.context_type.slice(1);
        const moduleInfo = context.module_title || 'N/A';
        
        html += `
            <tr class="context-item" onclick="selectContext(${context.context_id}, '${context.context_type}', ${context.assessment_id}, '${context.assessment_title}', ${context.max_attempts})" style="cursor: pointer;">
                <td><strong>${context.assessment_title}</strong></td>
                <td><span class="badge bg-${getContextBadgeColor(context.context_type)}">${contextTypeLabel}</span></td>
                <td>${moduleInfo}</td>
                <td>${context.max_attempts}</td>
                <td><span class="text-danger">${context.failed_users_count}</span></td>
                <td><i class="fas fa-arrow-right text-muted"></i></td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
    console.log('Contexts displayed successfully');
}

/**
 * Get context badge color
 */
function getContextBadgeColor(contextType) {
    switch(contextType) {
        case 'prerequisite': return 'warning';
        case 'module': return 'primary';
        case 'post_requisite': return 'info';
        default: return 'secondary';
    }
}

/**
 * Select assessment context
 */
function selectContext(contextId, contextType, assessmentId, assessmentTitle, maxAttempts) {
    selectedContext = {
        contextId: contextId,
        contextType: contextType,
        assessmentId: assessmentId,
        assessmentTitle: assessmentTitle,
        maxAttempts: maxAttempts
    };
    
    // Update UI
    document.querySelectorAll('.context-item').forEach(item => {
        item.classList.remove('selected', 'table-active');
    });
    event.currentTarget.classList.add('selected', 'table-active');
    
    // Show user selection
    document.getElementById('userCard').style.display = 'block';
    loadUsers();
}

/**
 * Load users for selected context
 */
function loadUsers() {
    if (!selectedContext) return;
    
    const url = `assessment-details/users?course_id=${selectedCourseId}&assessment_id=${selectedContext.assessmentId}&context_type=${selectedContext.contextType}&context_id=${selectedContext.contextId}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allUsers = data.users;
                displayUsers(data.users);
            } else {
                showAlert('Error loading users: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading users', 'danger');
        });
}

/**
 * Search users
 */
function searchUsers() {
    const searchTerm = document.getElementById('userSearch').value;
    loadUsers(searchTerm);
}

/**
 * Display users
 */
function displayUsers(users) {
    const container = document.getElementById('usersContainer');
    
    if (users.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">No users found matching the criteria.</div>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-hover">';
    html += '<thead><tr><th>Select</th><th>Name</th><th>Email</th><th>Employee ID</th><th>Attempts Used</th><th>Last Score</th><th>Last Attempt</th></tr></thead>';
    html += '<tbody>';
    
    users.forEach(user => {
        const isSelected = selectedUsers.some(u => u.user_id === user.user_id);
        html += `
            <tr class="user-item ${isSelected ? 'table-active' : ''}" onclick="toggleUser(${user.user_id}, event)" style="cursor: pointer;">
                <td>
                    <div class="form-check">
                        <input class="form-check-input user-checkbox" type="checkbox" ${isSelected ? 'checked' : ''} 
                               data-user-id="${user.user_id}"
                               onchange="toggleUser(${user.user_id}, event)">
                    </div>
                </td>
                <td><strong>${user.full_name}</strong></td>
                <td>${user.email}</td>
                <td>${user.employee_id || 'N/A'}</td>
                <td><span class="text-danger">${user.attempts_used}/${user.max_attempts}</span></td>
                <td>${user.last_score}%</td>
                <td>${new Date(user.last_attempt_date).toLocaleDateString()}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
    updateSelectedUsersCount();
}

/**
 * Toggle user selection
 */
function toggleUser(userId, event) {
    const user = allUsers.find(u => u.user_id === userId);
    if (!user) return;
    
    const index = selectedUsers.findIndex(u => u.user_id === userId);
    if (index > -1) {
        selectedUsers.splice(index, 1);
    } else {
        selectedUsers.push(user);
    }
    
    // Update UI
    if (event && event.currentTarget) {
        const userRow = event.currentTarget;
        userRow.classList.toggle('selected', 'table-active');
        const checkbox = userRow.querySelector('.user-checkbox');
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
        }
    } else {
        // Fallback: find the user row by user ID
        const userRows = document.querySelectorAll('.user-item');
        userRows.forEach(row => {
            const checkbox = row.querySelector('.user-checkbox');
            if (checkbox && parseInt(checkbox.getAttribute('data-user-id')) === userId) {
                row.classList.toggle('selected', 'table-active');
                checkbox.checked = !checkbox.checked;
            }
        });
    }
    
    updateSelectedUsersCount();
    
    // Show increase attempts section if users are selected
    if (selectedUsers.length > 0) {
        document.getElementById('increaseCard').style.display = 'block';
    } else {
        document.getElementById('increaseCard').style.display = 'none';
    }
}

/**
 * Select all users
 */
function selectAllUsers() {
    selectedUsers = [...allUsers];
    displayUsers(allUsers);
    document.getElementById('increaseCard').style.display = 'block';
}

/**
 * Clear user selection
 */
function clearUserSelection() {
    selectedUsers = [];
    displayUsers(allUsers);
    document.getElementById('increaseCard').style.display = 'none';
}

/**
 * Update selected users count
 */
function updateSelectedUsersCount() {
    document.getElementById('selectedUsersCount').textContent = selectedUsers.length;
}

/**
 * Increase attempts
 */
function increaseAttempts() {
    console.log('increaseAttempts called');
    console.log('selectedUsers:', selectedUsers);
    console.log('selectedCourseId:', selectedCourseId);
    console.log('selectedContext:', selectedContext);
    
    if (selectedUsers.length === 0) {
        showAlert('Please select at least one user', 'warning');
        return;
    }
    
    if (!selectedContext) {
        showAlert('Please select an assessment context first', 'warning');
        return;
    }
    
    const attemptsToAdd = parseInt(document.getElementById('attemptsToAdd').value);
    const reason = document.getElementById('reason').value;
    
    if (attemptsToAdd < 1 || attemptsToAdd > 10) {
        showAlert('Please enter a valid number of attempts (1-10)', 'warning');
        return;
    }
    
    const data = {
        course_id: selectedCourseId,
        assessment_id: selectedContext.assessmentId,
        context_type: selectedContext.contextType,
        context_id: selectedContext.contextId,
        user_ids: selectedUsers.map(u => u.user_id),
        attempts_to_add: attemptsToAdd,
        reason: reason
    };
    
    console.log('Sending data:', data);
    
    document.getElementById('increaseBtn').disabled = true;
    document.getElementById('increaseBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    
    fetch('assessment-details/increase-attempts', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showIncreaseResults(data.results);
            loadHistory();
        } else {
            showAlert('Error: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error increasing attempts: ' + error.message, 'danger');
    })
    .finally(() => {
        document.getElementById('increaseBtn').disabled = false;
        document.getElementById('increaseBtn').innerHTML = '<i class="fas fa-plus me-2"></i>Increase Attempts';
    });
}

/**
 * Show increase results
 */
function showIncreaseResults(results) {
    let html = '<h6>Attempts Increased Successfully:</h6><ul class="list-group">';
    results.forEach(result => {
        const user = selectedUsers.find(u => u.user_id === result.user_id);
        html += `
            <li class="list-group-item">
                <strong>${user.full_name}</strong> - 
                ${result.previous_max} → ${result.new_max} attempts (+${result.increased_by})
            </li>
        `;
    });
    html += '</ul>';
    
    document.getElementById('increaseResults').innerHTML = html;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('increaseModal'));
    modal.show();
}

/**
 * Load history
 */
function loadHistory() {
    fetch('assessment-details/history')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayHistory(data.history);
            } else {
                document.getElementById('historyContainer').innerHTML = 
                    '<div class="alert alert-warning">Error loading history</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('historyContainer').innerHTML = 
                '<div class="alert alert-warning">Error loading history</div>';
        });
}

/**
 * Display history
 */
function displayHistory(history) {
    const container = document.getElementById('historyContainer');
    
    if (history.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No attempt increases recorded yet.</div>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped"><thead><tr>';
    html += '<th>Date</th><th>Course</th><th>Assessment</th><th>User</th><th>Context</th><th>Attempts Added</th><th>Reason</th><th>Admin</th>';
    html += '</tr></thead><tbody>';
    
    history.forEach(record => {
        html += `
            <tr>
                <td>${new Date(record.increased_at).toLocaleString()}</td>
                <td>${record.course_name}</td>
                <td>${record.assessment_title}</td>
                <td>${record.user_name}</td>
                <td><span class="badge bg-${getContextBadgeColor(record.context_type)}">${record.context_type}</span></td>
                <td>+${record.attempts_increased}</td>
                <td>${record.reason || 'N/A'}</td>
                <td>${record.increased_by_name}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

/**
 * Reset process
 */
function resetProcess() {
    // Clear course selection
    clearCourseSelection();
    
    // Clear other form fields
    document.getElementById('userSearch').value = '';
    document.getElementById('attemptsToAdd').value = '1';
    document.getElementById('reason').value = '';
    
    // Close modal if open
    const modal = bootstrap.Modal.getInstance(document.getElementById('increaseModal'));
    if (modal) {
        modal.hide();
    }
}

/**
 * Refresh courses
 */
function refreshCourses() {
    // Clear current selection
    clearCourseSelection();
    
    // Reload the page to get fresh data
    location.reload();
}

/**
 * Show alert
 */
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.container').firstChild);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

