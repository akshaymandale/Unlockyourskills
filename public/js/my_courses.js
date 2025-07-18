// My Courses Page JS

document.addEventListener('DOMContentLoaded', function() {
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

    function fetchCourses() {
        coursesList.innerHTML = '<div class="text-center py-5 w-100"><div class="spinner-border text-primary"></div></div>';
        pagination.innerHTML = '';
        fetch(`/Unlockyourskills/my-courses/list?status=${encodeURIComponent(currentStatus)}&search=${encodeURIComponent(currentSearch)}&page=${currentPage}&per_page=${perPage}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.courses)) {
                    renderCourses(data.courses);
                    // TODO: handle pagination if backend returns total/pages
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
            coursesList.innerHTML = '<div class="text-center text-muted py-5 w-100">No courses found.</div>';
            return;
        }
        if (isGridView) {
            coursesList.className = 'row g-3';
            coursesList.innerHTML = courses.map(renderCourseCard).join('');
        } else {
            coursesList.className = 'list-group';
            coursesList.innerHTML = courses.map(renderCourseRow).join('');
        }
    }

    function renderCourseCard(course) {
        return `<div class="col-md-4 col-lg-3">
            <div class="card h-100 shadow-sm">
                ${course.thumbnail_image ? `<img src="${course.thumbnail_image}" class="card-img-top" alt="${course.name}">` : ''}
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-purple">${course.name}</h5>
                    <div class="mb-2 small text-muted">${course.category_name || ''}${course.subcategory_name ? ' / ' + course.subcategory_name : ''}</div>
                    <div class="mb-2">
                        ${renderStatusBadge(course.user_course_status)}
                    </div>
                    <div class="progress mb-2" style="height: 6px;">
                        <div class="progress-bar bg-purple" role="progressbar" style="width: ${course.progress || 0}%"></div>
                    </div>
                    <a href="#" class="btn theme-btn-primary mt-auto">${getActionText(course.user_course_status)}</a>
                </div>
            </div>
        </div>`;
    }

    function renderCourseRow(course) {
        return `<div class="list-group-item d-flex align-items-center">
            <div class="flex-grow-1">
                <div class="fw-bold text-purple">${course.name}</div>
                <div class="small text-muted">${course.category_name || ''}${course.subcategory_name ? ' / ' + course.subcategory_name : ''}</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                    ${renderStatusBadge(course.user_course_status)}
                    <div class="progress flex-grow-1" style="height: 6px; max-width: 120px;">
                        <div class="progress-bar bg-purple" role="progressbar" style="width: ${course.progress || 0}%"></div>
                    </div>
                </div>
            </div>
            <a href="#" class="btn theme-btn-primary ms-3">${getActionText(course.user_course_status)}</a>
        </div>`;
    }

    function renderStatusBadge(status) {
        if (status === 'completed') return '<span class="badge bg-success">Completed</span>';
        if (status === 'in_progress') return '<span class="badge bg-warning text-dark">In Progress</span>';
        return '<span class="badge bg-secondary">Not Started</span>';
    }

    function getActionText(status) {
        if (status === 'completed') return 'View';
        if (status === 'in_progress') return 'Continue';
        return 'Start';
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
}); 