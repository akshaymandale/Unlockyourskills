<?php
// ✅ Fix session issue: Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id'])) {
    header('Location: index.php');
    exit();
}

include 'core/UrlHelper.php';
include 'views/includes/header.php';
include 'views/includes/navbar.php';
include 'views/includes/sidebar.php';

// Get user info - these variables are already set by the controller
$user = $_SESSION['user'] ?? null;
$clientId = $_SESSION['user']['client_id'] ?? null;

// $courses and $categories are already provided by the controller
// No need to instantiate models here

// Check if user is logged in
if (!isset($_SESSION['user']['client_id'])) {
    header('Location: index.php?controller=LoginController');
    exit;
}

require_once 'core/UrlHelper.php';
require_once 'core/IdEncryption.php';
require_once 'config/Localization.php';

$systemRole = $_SESSION['user']['system_role'] ?? '';
$canManageAll = in_array($systemRole, ['super_admin', 'admin']);

// Set default client name from session (current user's client)
$clientName = $_SESSION['user']['client_name'] ?? 'DEFAULT';

// If in client management mode, override with the managed client's name
if (isset($_GET['client_id'])) {
    $clientName = 'Unknown Client';
    if (isset($client) && $client) {
        $clientName = $client['client_name'];
    } elseif (isset($clients) && !empty($clients)) {
        foreach ($clients as $clientItem) {
            if ($clientItem['id'] == $_GET['client_id']) {
                $clientName = $clientItem['client_name'];
                break;
            }
        }
    }
}
?>

<div class="main-content">
    <div class="container mt-4 course-management" data-course-page="true">
        <!-- Back Arrow and Title -->
        <div class="back-arrow-container">
            <a href="<?= UrlHelper::url('manage-portal') ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="divider-line"></span>
            <h1 class="page-title text-purple">
                <i class="fas fa-graduation-cap me-2"></i>
                Course Management
            </h1>
        </div>

        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('dashboard') ?>"><?= Localization::translate('dashboard'); ?></a>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?= UrlHelper::url('manage-portal') ?>"><?= Localization::translate('manage_portal'); ?></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Course Management</li>
            </ol>
        </nav>

        <?php if (isset($_GET['client_id'])): ?>
            <div class="alert alert-info mb-3">
                <i class="fas fa-building"></i>
                <strong>Client Management Mode:</strong> Managing courses for client <strong><?= htmlspecialchars($clientName); ?></strong>
                <a href="<?= UrlHelper::url('courses') ?>" class="btn btn-sm btn-outline-secondary ms-2">
                    <i class="fas fa-arrow-left"></i> Back to All Courses
                </a>
            </div>
        <?php endif; ?>

        <!-- Page Description -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-0">Manage course content, modules, and learning resources</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <button type="button" class="btn btn-outline-primary" id="importCourseBtn" title="Import Courses">
                            <i class="fas fa-upload me-2"></i>Import Courses
                        </button>
                        <a href="<?= UrlHelper::url('course-categories') ?>" class="btn btn-outline-secondary" title="Manage Course Categories">
                            <i class="fas fa-tags me-2"></i>Manage Categories
                        </a>
                        <?php
                        // Preserve client_id parameter if present (for super admin client management)
                        $addCourseUrl = UrlHelper::url('course-creation');
                        if (isset($_GET['client_id'])) {
                            $addCourseUrl .= '?client_id=' . urlencode($_GET['client_id']);
                        }
                        ?>
                        <button type="button" class="btn theme-btn-primary" title="Add New Course" id="addCourseBtn">
                            <i class="fas fa-plus me-2"></i>Add Course
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-3">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" id="searchInput" class="form-control"
                                        placeholder="Search courses..."
                                        title="Search courses">
                                </div>
                            </div>

                            <!-- Course Status Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="courseStatusFilter">
                                    <option value="">Course Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <!-- Category Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="categoryFilter">
                                    <option value="">All Categories</option>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= htmlspecialchars($category['name']); ?>">
                                                <?= htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Subcategory Filter -->
                            <div class="col-md-2">
                                <select class="form-select" id="subcategoryFilter">
                                    <option value="">All Subcategories</option>
                                    <?php if (!empty($subcategories)): ?>
                                        <?php foreach ($subcategories as $subcategory): ?>
                                            <option value="<?= htmlspecialchars($subcategory['name']); ?>">
                                                <?= htmlspecialchars($subcategory['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <!-- Clear All Filters -->
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger w-100" id="clearFiltersBtn" title="Clear all filters">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Results Info -->
        <div class="row mb-3">
            <div class="col-12">
                <div id="searchResultsInfo" class="search-results-info" style="display: none;">
                    <i class="fas fa-info-circle"></i>
                    <span id="resultsText"></span>
                </div>
            </div>
        </div>

        <!-- Courses Grid -->
        <div id="coursesContainer">
            <table class="table table-bordered" id="courseGrid">
                <thead class="question-grid">
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th>Status</th>
                        <th>Enrollment Count</th>
                        <th>Created Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="coursesTableBody">
                    <!-- Courses will be loaded here via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="row">
            <div class="col-12">
                <div id="paginationContainer">
                    <!-- Pagination will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Loading Spinner -->
        <div class="row" id="loadingIndicator" style="display: none;">
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading courses...</p>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCourseModalLabel">
                    <i class="fas fa-edit me-2"></i>Edit Course
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="editCourseModalContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Delete Course Confirmation Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCourseModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this course? This action cannot be undone.</p>
                <p class="text-muted"><strong>Course:</strong> <span id="deleteCourseName"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteCourse">Delete Course</button>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCourseModalLabel">
                    <i class="fas fa-plus me-2"></i>Add Course
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addCourseModalContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading form...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Preview Course Modal -->
<div class="modal fade" id="previewCourseModal" tabindex="-1" aria-labelledby="previewCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewCourseModalLabel">
                    <i class="fas fa-eye me-2"></i>Course Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="previewCourseModalContent">
                    <!-- Content will be loaded dynamically -->
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading course preview...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Modal Initialization Script -->
<script>
// Pass backend data to JavaScript
const currentUserRole = '<?= $_SESSION['user']['system_role'] ?? 'guest'; ?>';

document.addEventListener('DOMContentLoaded', function() {
    console.log('🔥 Course Management: DOM loaded');
    
    // Initialize search and filter functionality
    initializeCourseManagement();

    // Load initial courses
    if (document.getElementById('coursesTableBody')) {
        console.log('🔥 Course Management: Loading initial courses');
        loadCourses(1);
    } else {
        console.error('🔥 Course Management: coursesTableBody not found');
    }

    // Add Course Modal: Open and load form
    const addCourseBtn = document.getElementById('addCourseBtn');
    if (addCourseBtn) {
        addCourseBtn.addEventListener('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('addCourseModal'));
            document.getElementById('addCourseModalContent').innerHTML = `<div class='text-center py-4'><div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div><p class='mt-2'>Loading form...</p></div>`;
            fetch('/Unlockyourskills/course-creation/modal/add', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                console.log('[DEBUG] Response status:', response.status);
                console.log('[DEBUG] Response headers:', response.headers);
                console.log('[DEBUG] Response ok:', response.ok);
                return response.text();
            })
            .then(html => {
                console.log('[DEBUG] Response content length:', html.length);
                console.log('[DEBUG] Response content preview:', html.substring(0, 200));
                
                // Check if response is JSON error
                if (html.trim().startsWith('{')) {
                    try {
                        const jsonResponse = JSON.parse(html);
                        console.error('[DEBUG] JSON error response:', jsonResponse);
                        if (jsonResponse.redirect) {
                            console.error('[DEBUG] Redirecting to:', jsonResponse.redirect);
                            window.location.href = jsonResponse.redirect;
                            return;
                        }
                    } catch (e) {
                        console.log('[DEBUG] Not a JSON response, proceeding with HTML');
                    }
                }
                
                document.getElementById('addCourseModalContent').innerHTML = html;
                // Extract VLR content from the data attribute
                const vlrDiv = document.getElementById('vlrContentData');
                if (vlrDiv) {
                    window.vlrContent = JSON.parse(vlrDiv.getAttribute('data-vlr-content'));
                    console.log('[DEBUG] window.vlrContent set from data attribute:', window.vlrContent);
                } else {
                    console.error('[DEBUG] vlrContentData div not found in modal!');
                }
                
                        // Initialize course creation functionality after modal content is loaded
        console.log('[DEBUG] About to initialize course creation functionality');
        if (typeof initializeCourseCreation === 'function') {
            console.log('[DEBUG] initializeCourseCreation function found, calling it');
            initializeCourseCreation();
            console.log('[DEBUG] initializeCourseCreation completed');
        } else {
            console.error('[DEBUG] initializeCourseCreation function not found!');
        }
                
                const modal = new bootstrap.Modal(document.getElementById('addCourseModal'));
                modal.show();
            })
            .catch(error => {
                console.error('[DEBUG] Fetch error:', error);
            });
        });
    }
});

function initializeCourseManagement() {
    // Search functionality with debounce
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        const debouncedSearch = debounce(() => {
            searchCourses();
        }, 500);
        
        searchInput.addEventListener('input', debouncedSearch);
    }

    // Filter functionality
    const courseStatusFilter = document.getElementById('courseStatusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const subcategoryFilter = document.getElementById('subcategoryFilter');

    if (courseStatusFilter) {
        courseStatusFilter.addEventListener('change', searchCourses);
    }

    if (categoryFilter) {
        categoryFilter.addEventListener('change', searchCourses);
    }

    if (subcategoryFilter) {
        subcategoryFilter.addEventListener('change', searchCourses);
    }

    // Clear filters functionality
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', resetFilters);
    }

    // Pagination functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link[data-page]')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            loadCourses(page);
        }
    });
}

function clearAllFilters() {
    // Clear search
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
        currentSearch = '';
    }

    // Clear all filters
    const courseStatusFilter = document.getElementById('courseStatusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const subcategoryFilter = document.getElementById('subcategoryFilter');

    if (courseStatusFilter) courseStatusFilter.value = '';
    if (categoryFilter) categoryFilter.value = '';
    if (subcategoryFilter) subcategoryFilter.value = '';

    currentFilters = {
        course_status: '',
        category: '',
        subcategory: ''
    };

    // Reload courses
    loadCourses(1);
}

// Debounce function
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

// Global variables for search and filters
let currentSearch = '';
let currentFilters = {
    course_status: '',
    category: '',
    subcategory: ''
};
let currentPage = 1;

function searchCourses() {
    const searchInput = document.getElementById('searchInput');
    currentSearch = searchInput ? searchInput.value.trim() : '';
    
    // Get filter values
    const courseStatusFilter = document.getElementById('courseStatusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const subcategoryFilter = document.getElementById('subcategoryFilter');

    currentFilters = {
        course_status: courseStatusFilter ? courseStatusFilter.value : '',
        category: categoryFilter ? categoryFilter.value : '',
        subcategory: subcategoryFilter ? subcategoryFilter.value : ''
    };

    // Reset to first page when searching
    currentPage = 1;
    loadCourses(currentPage);
}

function resetFilters() {
    clearAllFilters();
}

function loadCourses(page = 1) {
    currentPage = page;
    
    // Show loading indicator
    const loadingIndicator = document.getElementById('loadingIndicator');
    const coursesContainer = document.getElementById('coursesContainer');
    
    if (loadingIndicator) loadingIndicator.style.display = 'block';
    if (coursesContainer) coursesContainer.style.display = 'none';

    // Prepare search parameters
    const params = new URLSearchParams({
        page: page,
        search: currentSearch,
        ...currentFilters
    });

    // Add client_id if present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('client_id')) {
        params.append('client_id', urlParams.get('client_id'));
    }

    // Make AJAX request
    fetch(`index.php?controller=CourseCreationController&action=getCourses&${params.toString()}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCourses(data.courses);
            displayPagination(data.pagination);
            updateSearchResultsInfo(data.total, data.filtered);
        } else {
            console.error('Error loading courses:', data.message);
            showToast('error', 'Failed to load courses');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Failed to load courses');
    })
    .finally(() => {
        // Hide loading indicator
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        if (coursesContainer) coursesContainer.style.display = 'block';
    });
}

function displayCourses(courses) {
    const tbody = document.getElementById('coursesTableBody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (courses.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-4">
                    <i class="fas fa-graduation-cap fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No courses found</p>
                </td>
            </tr>
        `;
        return;
    }

    courses.forEach(course => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${course.id}</td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="course-icon me-2">
                        <i class="fas fa-graduation-cap text-primary"></i>
                    </div>
                    <div>
                        <strong>${course.name}</strong>
                        <br>
                        <small class="text-muted">${course.description || 'No description'}</small>
                    </div>
                </div>
            </td>
            <td>${course.category_name || 'Uncategorized'}</td>
            <td>${course.subcategory_name || 'None'}</td>
            <td>
                <span class="badge bg-${getStatusBadgeClass(course.course_status || 'active')}">
                    ${(course.course_status || 'active').charAt(0).toUpperCase() + (course.course_status || 'active').slice(1)}
                </span>
            </td>
            <td>${course.enrollment_count || 0}</td>
            <td>${formatDate(course.created_at)}</td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editCourse(${course.id})" title="Edit Course">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="previewCourse(${course.id})" title="Preview Course">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCourse(${course.id}, '${course.name}')" title="Delete Course">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'active': return 'success';
        case 'inactive': return 'warning';
        case 'published': return 'success';
        case 'draft': return 'warning';
        case 'archived': return 'secondary';
        default: return 'secondary';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function displayPagination(pagination) {
    const container = document.getElementById('paginationContainer');
    if (!container || !pagination) return;

    const { current_page, total_pages, total_records } = pagination;
    
    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHTML = `
        <nav aria-label="Course pagination">
            <ul class="pagination justify-content-center">
    `;

    // Previous button
    if (current_page > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${current_page - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;
    }

    // Page numbers
    const startPage = Math.max(1, current_page - 2);
    const endPage = Math.min(total_pages, current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    // Next button
    if (current_page < total_pages) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" data-page="${current_page + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }

    paginationHTML += `
            </ul>
        </nav>
        <div class="text-center text-muted mt-2">
            Showing page ${current_page} of ${total_pages} (${total_records} total courses)
        </div>
    `;

    container.innerHTML = paginationHTML;
}

function updateSearchResultsInfo(total, filtered) {
    const searchResultsInfo = document.getElementById('searchResultsInfo');
    const resultsText = document.getElementById('resultsText');
    
    if (!searchResultsInfo || !resultsText) return;

    if (currentSearch || Object.values(currentFilters).some(filter => filter !== '')) {
        searchResultsInfo.style.display = 'block';
        resultsText.textContent = `Showing ${filtered} of ${total} courses`;
    } else {
        searchResultsInfo.style.display = 'none';
    }
}

function editCourse(courseId) {
    console.log('[DEBUG] editCourse() called with courseId:', courseId);
    // Load edit course modal content
    fetch(`index.php?controller=CourseCreationController&action=editCourse&id=${courseId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.text())
    .then(html => {
        console.log('[DEBUG] Modal content loaded, setting innerHTML');
        document.getElementById('editCourseModalContent').innerHTML = html;
        
        // Extract and set VLR content from the loaded modal content
        console.log('[DEBUG] Extracting VLR content from modal');
        const vlrContentElement = document.getElementById('vlrContentData');
        if (vlrContentElement) {
            try {
                const vlrContentData = vlrContentElement.getAttribute('data-vlr-content');
                console.log('[DEBUG] VLR content data attribute found:', vlrContentData);
                window.vlrContent = JSON.parse(vlrContentData);
                console.log('[DEBUG] window.vlrContent set from edit modal:', window.vlrContent);
            } catch (e) {
                console.error('[DEBUG] Error parsing VLR content from edit modal:', e);
                window.vlrContent = [];
            }
        } else {
            console.error('[DEBUG] VLR content element not found in edit modal');
            window.vlrContent = [];
        }
        
        console.log('[DEBUG] About to show modal');
        const modal = new bootstrap.Modal(document.getElementById('editCourseModal'));
        modal.show();
        
        // Initialize course creation functionality AFTER modal content is loaded
        console.log('[DEBUG] About to initialize course creation functionality');
        if (typeof initializeCourseCreation === 'function') {
            console.log('[DEBUG] initializeCourseCreation function found, calling it');
            // Add a small delay to ensure form fields are populated
            setTimeout(() => {
                initializeCourseCreation();
                console.log('[DEBUG] initializeCourseCreation completed');
            }, 100);
        } else {
            console.error('[ERROR] initializeCourseCreation function not found!');
        }
    })
    .catch(error => {
        console.error('Error loading edit form:', error);
        showToast('error', 'Failed to load edit form');
    });
}

function previewCourse(courseId) {
    // Show loading in modal
    const modal = new bootstrap.Modal(document.getElementById('previewCourseModal'));
    document.getElementById('previewCourseModalContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading course preview...</p>
        </div>
    `;
    modal.show();
    
    // Load course preview content
    fetch(`/Unlockyourskills/course-preview/${courseId}`, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('previewCourseModalContent').innerHTML = data.html;
        } else {
            document.getElementById('previewCourseModalContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${data.message || 'Failed to load course preview'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading course preview:', error);
        document.getElementById('previewCourseModalContent').innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Failed to load course preview. Please try again.
            </div>
        `;
    });
}

function deleteCourse(courseId, courseName) {
    document.getElementById('deleteCourseName').textContent = courseName;
    const modal = new bootstrap.Modal(document.getElementById('deleteCourseModal'));
    modal.show();
    
    // Store course ID for deletion
    document.getElementById('confirmDeleteCourse').onclick = function() {
        performDeleteCourse(courseId);
    };
}

function performDeleteCourse(courseId) {
    fetch(`index.php?controller=CourseCreationController&action=deleteCourse`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            course_id: courseId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Course deleted successfully');
            loadCourses(currentPage);
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteCourseModal'));
            modal.hide();
        } else {
            showToast('error', data.message || 'Failed to delete course');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'Failed to delete course');
    });
}

// Toast notification function
function showToast(type, message) {
    if (typeof showToastNotification === 'function') {
        showToastNotification(type, message);
    } else {
        alert(message);
    }
}

function attachAddCourseFormHandler() {
    const form = document.getElementById('addCourseForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('course-creation', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'Course created successfully');
                const modal = bootstrap.Modal.getInstance(document.getElementById('addCourseModal'));
                modal.hide();
                loadCourses(1);
            } else {
                showToast('error', data.message || 'Failed to create course');
            }
        })
        .catch(() => {
            showToast('error', 'Failed to create course');
        });
    });
}
</script>

<?php include 'views/includes/footer.php'; ?>
<script src="/Unlockyourskills/public/js/course_creation_validation.js"></script>
<script src="/Unlockyourskills/public/js/course_creation.js"></script>
</body>
</html> 