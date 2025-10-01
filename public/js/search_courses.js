// Search Courses Page JS

// Global variables for pagination
let totalCourses = 0;
let totalPages = 0;
const perPage = 12;

// Fetch courses function
function fetchCourses() {
    const coursesList = document.getElementById('searchCoursesList');
    const pagination = document.getElementById('searchCoursesPagination');
    
    if (!coursesList) {
        console.error('coursesList element not found');
        return;
    }
    
    coursesList.innerHTML = '<div class="text-center py-5 w-100"><div class="spinner-border text-primary"></div></div>';
    if (pagination) {
        pagination.innerHTML = '';
    }
    
    // Get current search and page values from global variables
    const currentSearch = window.currentSearch || '';
    const currentPage = window.currentPage || 1;
    
    // Fetch both courses and total count in parallel
    Promise.all([
        fetch(`/Unlockyourskills/search-courses/list?search=${encodeURIComponent(currentSearch)}&page=${currentPage}&per_page=${perPage}`)
            .then(res => res.json()),
        fetch(`/Unlockyourskills/search-courses/count?search=${encodeURIComponent(currentSearch)}`)
            .then(res => res.json())
    ])
    .then(([coursesData, countData]) => {
        if (coursesData.success && Array.isArray(coursesData.courses)) {
            totalCourses = countData.success ? countData.total : 0;
            totalPages = Math.ceil(totalCourses / perPage);
            renderCourses(coursesData.courses);
            renderPagination();
        } else {
            coursesList.innerHTML = '<div class="text-danger">Failed to load courses.</div>';
        }
    })
    .catch(() => {
        coursesList.innerHTML = '<div class="text-danger">Failed to load courses.</div>';
    });
}

// Render courses function
function renderCourses(courses) {
    const coursesList = document.getElementById('searchCoursesList');
    const isGridView = window.isGridView !== false; // default to true
    
    if (!coursesList) {
        console.error('coursesList element not found');
        return;
    }
    
    if (!courses || courses.length === 0) {
        coursesList.innerHTML = `
            <div class="empty-state text-center py-5 w-100">
                <img src="/Unlockyourskills/public/images/UYSlogo.png" alt="No courses" style="max-width:120px;opacity:0.7;" class="mb-3"/>
                <h4 class="text-purple mb-2">No courses found</h4>
                <p class="text-muted">No courses match your search criteria.<br>Try different search terms or browse all available courses!</p>
            </div>
        `;
        return;
    }
    if (isGridView) {
        coursesList.className = 'row g-4';
        coursesList.innerHTML = courses.map(renderCourseCard).join('');
    } else {
        coursesList.className = '';
        coursesList.innerHTML = courses.map(renderCourseRow).join('');
    }
}

// Render course card function
function renderCourseCard(course) {
    const progress = Math.round(course.progress || 0);
    let statusIcon = '<i class="fas fa-circle text-secondary"></i>';
    if (course.user_course_status === 'completed') statusIcon = '<i class="fas fa-check-circle text-success"></i>';
    else if (course.user_course_status === 'in_progress') statusIcon = '<i class="fas fa-play-circle text-warning"></i>';
    else statusIcon = '<i class="fas fa-hourglass-start text-purple"></i>';
    // Instructor/avatar (placeholder)
    const avatar = `<div class='course-avatar bg-purple text-white d-flex align-items-center justify-content-center'><i class='fas fa-user-graduate'></i></div>`;
    // Date info (placeholder)
    const dateInfo = course.created_at ? `<span class='small text-muted'><i class='fas fa-calendar-alt me-1'></i>${new Date(course.created_at).toLocaleDateString()}</span>` : '';
    // Module info (placeholder)
    const moduleInfo = course.module_count ? `<span class='small text-muted ms-2'><i class='fas fa-layer-group me-1'></i>${course.module_count} Modules</span>` : '';
    const statusBadge = renderStatusBadge(course.user_course_status);
    const courseUrl = `/Unlockyourskills/my-courses/details/${course.encrypted_id}`;
    console.log(`[renderCourseCard] Creating link for course "${course.name}" with URL: ${courseUrl}`);
    console.log(`[renderCourseCard] Course ID: ${course.id}, Course data:`, course);
    return `<div class="col-md-6 col-lg-4 col-xl-3">
        <div class="card course-card h-100 shadow-lg border-0 animate-pop position-relative" data-course-id="${course.id}">
            <div class="card-img-top course-thumb position-relative">
                ${course.thumbnail_image ? `<img src="${course.thumbnail_image}" alt="${course.name}" class="w-100 h-100 object-fit-cover rounded-top">` : `<div class="course-thumb-placeholder d-flex align-items-center justify-content-center"><i class="fas fa-book fa-3x text-purple"></i></div>`}
                <div class="course-thumb-gradient"></div>
                <div class="course-avatar-wrap position-absolute top-0 end-0 m-2">${avatar}</div>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge badge-status me-2">${statusIcon} ${statusBadge}</span>
                    <span class="ms-auto small text-muted">${course.category_name || ''}${course.subcategory_name ? ' / ' + course.subcategory_name : ''}</span>
                </div>
                <h5 class="card-title text-purple fw-bold mb-1">${course.name}</h5>
                <div class="mb-2 text-muted small">${course.difficulty_level ? `<i class='fas fa-signal'></i> ${course.difficulty_level}` : ''}</div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    ${dateInfo}
                    ${moduleInfo}
                </div>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-purple animate-progress" role="progressbar" style="width: ${progress}%"></div>
                </div>
                <div class="mb-2 text-end small text-purple fw-bold">${progress}% Complete</div>
                <div class="d-flex justify-content-between align-items-center">
                    ${getEnrollmentButton(course)}
                    <i class="fas fa-arrow-right text-purple"></i>
                </div>
            </div>
        </div>
    </div>`;
}

// Render course row function
function renderCourseRow(course) {
    const progress = Math.round(course.progress || 0);
    let statusIcon = '<i class="fas fa-circle text-secondary"></i>';
    if (course.user_course_status === 'completed') statusIcon = '<i class="fas fa-check-circle text-success"></i>';
    else if (course.user_course_status === 'in_progress') statusIcon = '<i class="fas fa-play-circle text-warning"></i>';
    else statusIcon = '<i class="fas fa-hourglass-start text-purple"></i>';
    const avatar = `<div class='course-avatar bg-purple text-white d-flex align-items-center justify-content-center me-3'><i class='fas fa-user-graduate'></i></div>`;
    const statusBadge = renderStatusBadge(course.user_course_status);
    const courseUrl = `/Unlockyourskills/my-courses/details/${course.encrypted_id}`;
    console.log(`[renderCourseRow] Creating link for course "${course.name}" with URL: ${courseUrl}`); // DEBUG LOG
    console.log(`[renderCourseRow] Course ID: ${course.id}, Course data:`, course);
    return `<div class="list-group-item d-flex align-items-center course-list-row animate-pop" data-course-id="${course.id}">
        ${avatar}
        <div class="flex-grow-1">
            <div class="d-flex align-items-center mb-1">
                <span class="fw-bold text-purple me-2">${course.name}</span>
                <span class="badge badge-status ms-2">${statusIcon} ${statusBadge}</span>
            </div>
            <div class="small text-muted mb-1">${course.category_name || ''}${course.subcategory_name ? ' / ' + course.subcategory_name : ''}</div>
            <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height: 8px; max-width: 120px;">
                    <div class="progress-bar bg-purple animate-progress" role="progressbar" style="width: ${progress}%"></div>
                </div>
                <span class="small text-purple fw-bold ms-2">${progress}%</span>
            </div>
        </div>
        <div class="course-actions">
            ${getEnrollmentButton(course)}
        </div>
    </div>`;
}

// Render status badge function
function renderStatusBadge(status) {
    if (status === 'completed') return '<span class="badge bg-success">Completed</span>';
    if (status === 'in_progress') return '<span class="badge theme-btn-warning text-dark">In Progress</span>';
    // Not Started: use theme purple class
    return '<span class="badge badge-purple">Not Started</span>';
}

// Get enrollment button based on enrollment status
function getEnrollmentButton(course) {
    const courseUrl = `/Unlockyourskills/my-courses/details/${course.encrypted_id}`;
    
    // Check if course has enrollment status
    if (course.enrollment_status === 'pending') {
        return '<span class="btn btn-warning disabled"><i class="fas fa-clock me-1"></i>Pending for Approval</span>';
    } else if (course.enrollment_status === 'approved') {
        return '<a href="' + courseUrl + '" class="btn btn-success"><i class="fas fa-check me-1"></i>Enrolled</a>';
    } else if (course.enrollment_status === 'rejected') {
        return '<span class="btn btn-danger disabled"><i class="fas fa-times me-1"></i>Rejected</span>';
    } else {
        // No enrollment status - show regular enroll button
        return '<a href="' + courseUrl + '" class="btn theme-btn-primary animate-btn" onclick="enrollInCourse(' + course.id + ', this); return false;">Enroll</a>';
    }
}

// Get action text function
function getActionText(status) {
    if (status === 'completed') return 'View';
    if (status === 'in_progress') return 'Continue';
    return 'Enroll';
}

document.addEventListener('DOMContentLoaded', function() {
    try {
        const searchInput = document.getElementById('searchCoursesInput');
        const toggleViewBtn = document.getElementById('toggleViewBtn');
        const coursesList = document.getElementById('searchCoursesList');
        const pagination = document.getElementById('searchCoursesPagination');

        // Set global variables
        window.currentSearch = '';
        window.currentPage = 1;
        window.perPage = 12;
        window.isGridView = true;

        // Add a welcome message at the top of the page
        const container = document.getElementById('searchCoursesPage');
        if (container && !document.getElementById('welcomeMessage')) {
            const welcome = document.createElement('div');
            welcome.id = 'welcomeMessage';
            welcome.className = 'mb-4 p-4 rounded shadow-sm welcome-banner';
            welcome.innerHTML = `
                <h2 class="mb-2 text-purple fw-bold"><i class="fas fa-search me-2"></i>Discover New Courses!</h2>
                <p class="mb-0 text-muted">Search through all available courses and start your learning journey.</p>
            `;
            // Insert after the title (h1) and before the search controls
            const title = container.querySelector('h1');
            const searchControls = container.querySelector('.d-flex.justify-content-between');
            container.insertBefore(welcome, searchControls);
        }

        // Search functionality
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    window.currentSearch = e.target.value.trim();
                    window.currentPage = 1;
                    fetchCourses();
                }, 500);
            });
        }

        // Toggle view functionality
        if (toggleViewBtn) {
            toggleViewBtn.addEventListener('click', () => {
                window.isGridView = !window.isGridView;
                if (window.isGridView) {
                    toggleViewBtn.innerHTML = '<i class="fas fa-list"></i>';
                } else {
                    toggleViewBtn.innerHTML = '<i class="fas fa-th-large"></i>';
                }
                fetchCourses();
            });
        }

        // Course enroll button events
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn') && e.target.textContent.trim() === 'Enroll') {
                e.preventDefault();
                console.log('Enroll button clicked');
                const courseCard = e.target.closest('.course-card') || e.target.closest('.course-list-row');
                if (courseCard) {
                    const courseId = courseCard.dataset.courseId;
                    console.log('Course ID from data attribute:', courseId);
                    if (courseId) {
                        enrollInCourse(courseId, e.target);
                    } else {
                        console.error('No course ID found in data attribute');
                    }
                } else {
                    console.error('No course card or list row found');
                }
            }
        });

        // Initial load
        fetchCourses();

    } catch (error) {
        console.error('Error initializing search courses:', error);
    }
});

// Enrollment function (moved outside DOMContentLoaded)
async function enrollInCourse(courseId, buttonElement) {
        try {
            console.log('Starting enrollment for course ID:', courseId);
            
            // Disable button and show loading state
            const originalText = buttonElement.textContent;
            buttonElement.disabled = true;
            buttonElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enrolling...';

            const formData = new FormData();
            formData.append('course_id', courseId);
            console.log('Form data prepared:', formData.get('course_id'));

            console.log('Making request to:', '/Unlockyourskills/course-enrollment/enroll');
            const response = await fetch('/Unlockyourskills/course-enrollment/enroll', {
                method: 'POST',
                body: formData
            });

            console.log('Response status:', response.status);
            const result = await response.json();
            console.log('Response data:', result);

            if (result.success) {
                // Show success message
                showMessage(result.message, 'success');
                
                // Update button state
                buttonElement.innerHTML = '<i class="fas fa-check me-1"></i>Enrolled';
                buttonElement.classList.remove('theme-btn-primary');
                buttonElement.classList.add('btn-success');
                
                // Optionally remove the course from search results or mark it as enrolled
                setTimeout(() => {
                    fetchCourses(); // Refresh the course list
                }, 2000);
                
            } else {
                // Check if it's an "already enrolled" message - treat as info, not error
                if (result.message && result.message.includes('already enrolled')) {
                    showMessage(result.message, 'info');
                    
                    // Update button state to show enrolled
                    buttonElement.innerHTML = '<i class="fas fa-check me-1"></i>Enrolled';
                    buttonElement.classList.remove('theme-btn-primary');
                    buttonElement.classList.add('btn-success');
                    
                    // Refresh the course list
                    setTimeout(() => {
                        fetchCourses();
                    }, 2000);
                } else {
                    // Show error message for actual errors
                    showMessage(result.message, 'error');
                    
                    // Restore button state
                    buttonElement.disabled = false;
                    buttonElement.textContent = originalText;
                }
            }

        } catch (error) {
            console.error('Error enrolling in course:', error);
            showMessage('An error occurred while enrolling in the course.', 'error');
            
            // Restore button state
            buttonElement.disabled = false;
            buttonElement.textContent = originalText;
        }
    }

// Show message function (moved outside DOMContentLoaded)
function showMessage(message, type) {
        // Create or update message element
        let messageElement = document.getElementById('enrollmentMessage');
        if (!messageElement) {
            messageElement = document.createElement('div');
            messageElement.id = 'enrollmentMessage';
            messageElement.className = 'alert alert-dismissible fade show position-fixed';
            messageElement.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            
            const container = document.getElementById('searchCoursesPage');
            container.appendChild(messageElement);
        }

        // Set message content and styling
        let alertClass = 'danger'; // default
        if (type === 'success') alertClass = 'success';
        else if (type === 'info') alertClass = 'info';
        else if (type === 'warning') alertClass = 'warning';
        
        messageElement.className = `alert alert-${alertClass} alert-dismissible fade show position-fixed`;
        messageElement.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (messageElement && messageElement.parentNode) {
                messageElement.remove();
            }
        }, 5000);
    }

// Render pagination function
function renderPagination() {
    const pagination = document.getElementById('searchCoursesPagination');
    if (!pagination) return;

    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }

    let paginationHTML = '<nav aria-label="Courses pagination"><ul class="pagination justify-content-center">';
    
    // Previous button
    const prevDisabled = window.currentPage === 1 ? 'disabled' : '';
    paginationHTML += `<li class="page-item ${prevDisabled}">
        <a class="page-link" href="#" data-page="${window.currentPage - 1}" ${prevDisabled ? 'tabindex="-1" aria-disabled="true"' : ''}>
            <i class="fas fa-chevron-left"></i> Previous
        </a>
    </li>`;

    // Page numbers
    const startPage = Math.max(1, window.currentPage - 2);
    const endPage = Math.min(totalPages, window.currentPage + 2);

    // First page and ellipsis
    if (startPage > 1) {
        paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
        if (startPage > 2) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Page numbers around current page
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === window.currentPage ? 'active' : '';
        paginationHTML += `<li class="page-item ${activeClass}">
            <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>`;
    }

    // Last page and ellipsis
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
    }

    // Next button
    const nextDisabled = window.currentPage === totalPages ? 'disabled' : '';
    paginationHTML += `<li class="page-item ${nextDisabled}">
        <a class="page-link" href="#" data-page="${window.currentPage + 1}" ${nextDisabled ? 'tabindex="-1" aria-disabled="true"' : ''}>
            Next <i class="fas fa-chevron-right"></i>
        </a>
    </li>`;

    paginationHTML += '</ul></nav>';

    // Add course count info
    const startCourse = (window.currentPage - 1) * perPage + 1;
    const endCourse = Math.min(window.currentPage * perPage, totalCourses);
    paginationHTML += `<div class="text-center mt-3 text-muted small">
        Showing ${startCourse}-${endCourse} of ${totalCourses} courses
    </div>`;

    pagination.innerHTML = paginationHTML;

    // Add event listeners to pagination links
    pagination.querySelectorAll('.page-link[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.getAttribute('data-page'));
            if (page >= 1 && page <= totalPages && page !== window.currentPage) {
                window.currentPage = page;
                fetchCourses();
            }
        });
    });
}