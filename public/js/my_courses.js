// My Courses Page JS

document.addEventListener('DOMContentLoaded', function() {
    try {
        const tabs = document.querySelectorAll('#myCoursesTabs .nav-link');
    const searchInput = document.getElementById('myCoursesSearch');
    const toggleViewBtn = document.getElementById('toggleViewBtn');
    const coursesList = document.getElementById('myCoursesList');
    const pagination = document.getElementById('myCoursesPagination');

    let currentStatus = 'not_started';
    let currentSearch = '';
    let isGridView = true;
    let currentPage = 1;
    let perPage = 12;
    let totalCourses = 0;
    let totalPages = 0;

    // Add a welcome message above the tabs
    const container = document.getElementById('myCoursesPage');
    if (container && !document.getElementById('welcomeMessage')) {
        const welcome = document.createElement('div');
        welcome.id = 'welcomeMessage';
        welcome.className = 'mb-4 p-4 rounded shadow-sm welcome-banner';
        welcome.innerHTML = `
            <h2 class="mb-2 text-purple fw-bold"><i class="fas fa-unlock-alt me-2"></i>Welcome back!</h2>
            <p class="mb-0 text-muted">Ready to unlock new skills? Your learning journey awaits below.</p>
        `;
        container.prepend(welcome);
    }

    function fetchCourses() {
        coursesList.innerHTML = '<div class="text-center py-5 w-100"><div class="spinner-border text-primary"></div></div>';
        pagination.innerHTML = '';
        
        // Fetch both courses and total count in parallel
        Promise.all([
            fetch(`/Unlockyourskills/my-courses/list?status=${encodeURIComponent(currentStatus)}&search=${encodeURIComponent(currentSearch)}&page=${currentPage}&per_page=${perPage}`)
                .then(res => res.json()),
            fetch(`/Unlockyourskills/my-courses/count?status=${encodeURIComponent(currentStatus)}&search=${encodeURIComponent(currentSearch)}`)
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

    function renderCourses(courses) {
        if (!courses || courses.length === 0) {
            coursesList.innerHTML = `
                <div class="empty-state text-center py-5 w-100">
                    <img src="/Unlockyourskills/public/images/UYSlogo.png" alt="No courses" style="max-width:120px;opacity:0.7;" class="mb-3"/>
                    <h4 class="text-purple mb-2">No courses found</h4>
                    <p class="text-muted">You have no courses assigned yet.<br>Check back soon or contact your admin!</p>
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
        return `<div class="col-md-6 col-lg-4 col-xl-3">
            <div class="card course-card h-100 shadow-lg border-0 animate-pop position-relative">
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
                        <a href="${courseUrl}" class="btn theme-btn-primary animate-btn">${getActionText(course.user_course_status)}</a>
                        <i class="fas fa-arrow-right text-purple"></i>
                    </div>
                </div>
            </div>
        </div>`;
    }

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
        return `<div class="list-group-item d-flex align-items-center course-list-row animate-pop">
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
                <a href="${courseUrl}" class="btn theme-btn-primary animate-btn">${getActionText(course.user_course_status)}</a>
            </div>
        </div>`;
    }

    function renderStatusBadge(status) {
        if (status === 'completed') return '<span class="badge bg-success">Completed</span>';
        if (status === 'in_progress') return '<span class="badge theme-btn-warning text-dark">In Progress</span>';
        // Not Started: use theme purple class
        return '<span class="badge badge-purple">Not Started</span>';
    }

    function getActionText(status) {
        if (status === 'completed') return 'View';
        if (status === 'in_progress') return 'Continue';
        return 'Start';
    }

    function renderPagination() {
        if (totalPages <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let paginationHTML = '<nav aria-label="Courses pagination"><ul class="pagination justify-content-center">';
        
        // Previous button
        const prevDisabled = currentPage === 1 ? 'disabled' : '';
        paginationHTML += `<li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" ${prevDisabled ? 'tabindex="-1" aria-disabled="true"' : ''}>
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        </li>`;

        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        // First page and ellipsis
        if (startPage > 1) {
            paginationHTML += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        // Page numbers around current page
        for (let i = startPage; i <= endPage; i++) {
            const activeClass = i === currentPage ? 'active' : '';
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
        const nextDisabled = currentPage === totalPages ? 'disabled' : '';
        paginationHTML += `<li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" ${nextDisabled ? 'tabindex="-1" aria-disabled="true"' : ''}>
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </li>`;

        paginationHTML += '</ul></nav>';

        // Add course count info
        const startCourse = (currentPage - 1) * perPage + 1;
        const endCourse = Math.min(currentPage * perPage, totalCourses);
        paginationHTML += `<div class="text-center mt-3 text-muted small">
            Showing ${startCourse}-${endCourse} of ${totalCourses} courses
        </div>`;

        pagination.innerHTML = paginationHTML;

        // Add event listeners to pagination links
        pagination.querySelectorAll('.page-link[data-page]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page >= 1 && page <= totalPages && page !== currentPage) {
                    currentPage = page;
                    fetchCourses();
                }
            });
        });
    }

    // Tab switching
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.getAttribute('data-status');
            currentPage = 1;
            fetchCourses();
        });
    });

    // Search
    searchInput.addEventListener('input', function() {
        currentSearch = this.value.trim();
        currentPage = 1;
        fetchCourses();
    });

    // Grid/List toggle
    toggleViewBtn.addEventListener('click', function() {
        isGridView = !isGridView;
        this.innerHTML = isGridView ? '<i class="fas fa-th-large"></i>' : '<i class="fas fa-list"></i>';
        renderCourses([]); // Clear before reload
        fetchCourses();
    });

    // Initial load
    fetchCourses();

    // Add simple debugging to see if clicks are being detected
    document.addEventListener('click', function(e) {
        if (e.target.closest('.animate-btn')) {
            console.log('Start button clicked!', e.target);
            console.log('Button href:', e.target.href);
            console.log('Button text:', e.target.textContent);
        }
    });
    } catch (error) {
        alert('JavaScript Error: ' + error.message);
        console.error('My Courses JavaScript Error:', error);
    }
}); 