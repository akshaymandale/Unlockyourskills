/**
 * Course Management JavaScript
 * Handles filtering, search, and course actions
 */

class CourseManagementManager {
    constructor() {
        this.courses = [];
        this.filteredCourses = [];
        this.currentFilters = {
            search: '',
            category: '',
            course_status: ''
        };
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCourses();
        this.setupSearch();
    }

    bindEvents() {
        // Filter events
        document.getElementById('searchInput')?.addEventListener('input', (e) => {
            this.currentFilters.search = e.target.value.toLowerCase();
            this.applyFilters();
        });

        document.getElementById('categoryFilter')?.addEventListener('change', (e) => {
            this.currentFilters.category = e.target.value.toLowerCase();
            this.applyFilters();
        });

        document.getElementById('courseStatusFilter')?.addEventListener('change', (e) => {
            this.currentFilters.course_status = e.target.value.toLowerCase();
            this.applyFilters();
        });

        // Clear filters
        document.getElementById('clearFilters')?.addEventListener('click', () => {
            this.clearFilters();
        });

        // Global course actions
        window.publishCourse = (courseId) => this.publishCourse(courseId);
        window.unpublishCourse = (courseId) => this.unpublishCourse(courseId);
        window.deleteCourse = (courseId) => this.deleteCourse(courseId);
    }

    setupSearch() {
        // Debounced search
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilters.search = e.target.value.toLowerCase();
                    this.applyFilters();
                }, 300);
            });
        }
    }

    async loadCourses() {
        try {
            this.showLoading(true);
            
            const response = await fetch('/api/courses');
            const data = await response.json();
            
            if (data.success) {
                this.courses = data.courses;
                this.filteredCourses = [...this.courses];
                this.updateStatistics();
                this.renderCourses();
            } else {
                this.showError('Failed to load courses');
            }
        } catch (error) {
            console.error('Error loading courses:', error);
            this.showError('Error loading courses');
        } finally {
            this.showLoading(false);
        }
    }

    applyFilters() {
        this.filteredCourses = this.courses.filter(course => {
            const matchesSearch = !this.currentFilters.search || 
                course.name.toLowerCase().includes(this.currentFilters.search) ||
                course.description.toLowerCase().includes(this.currentFilters.search) ||
                course.category_name.toLowerCase().includes(this.currentFilters.search);

            const matchesCategory = !this.currentFilters.category || 
                course.category_name.toLowerCase() === this.currentFilters.category;

            const matchesStatus = !this.currentFilters.course_status || 
                (course.course_status || 'active').toLowerCase() === this.currentFilters.course_status;

            return matchesSearch && matchesCategory && matchesStatus;
        });

        this.renderCourses();
        this.updateSearchResults();
    }

    clearFilters() {
        this.currentFilters = {
            search: '',
            category: '',
            course_status: ''
        };

        // Reset form elements
        document.getElementById('searchInput').value = '';
        document.getElementById('categoryFilter').value = '';
        document.getElementById('courseStatusFilter').value = '';

        this.applyFilters();
        this.showToast('Filters cleared', 'info');
    }

    renderCourses() {
        const container = document.getElementById('coursesContainer');
        if (!container) return;

        if (this.filteredCourses.length === 0) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No courses found</h4>
                        <p class="text-muted">Try adjusting your search criteria</p>
                    </div>
                </div>
            `;
            return;
        }

        container.innerHTML = this.filteredCourses.map(course => `
            <div class="col-lg-4 col-md-6 mb-4 course-card" 
                 data-name="${course.name.toLowerCase()}"
                 data-category="${course.category_name.toLowerCase()}"
                 data-status="${course.course_status || 'active'}">
                <div class="card h-100 course-card-inner">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-${this.getStatusBadgeColor(course.course_status || 'active')}">
                            ${this.capitalizeFirst(course.course_status || 'active')}
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/course-edit/${course.id}">
                                        <i class="fas fa-edit me-2"></i>
                                        Edit
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/course-preview/${course.id}">
                                        <i class="fas fa-eye me-2"></i>
                                        Preview
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/course-analytics/${course.id}">
                                        <i class="fas fa-chart-bar me-2"></i>
                                        Analytics
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                ${course.course_status === 'inactive' ? `
                                    <li>
                                        <a class="dropdown-item text-success" href="#" onclick="courseManager.publishCourse(${course.id})">
                                            <i class="fas fa-check me-2"></i>
                                            Activate
                                        </a>
                                    </li>
                                ` : course.course_status === 'active' ? `
                                    <li>
                                        <a class="dropdown-item text-warning" href="#" onclick="courseManager.unpublishCourse(${course.id})">
                                            <i class="fas fa-pause me-2"></i>
                                            Deactivate
                                        </a>
                                    </li>
                                ` : ''}
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="courseManager.deleteCourse(${course.id})">
                                        <i class="fas fa-trash me-2"></i>
                                        Delete
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">${this.escapeHtml(course.name)}</h5>
                        <p class="card-text text-muted">
                            ${this.escapeHtml(course.description.length > 100 ? 
                                course.description.substring(0, 100) + '...' : 
                                course.description)}
                        </p>
                        
                        <div class="course-meta mb-3">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="course-stat">
                                        <div class="stat-number">${course.module_count}</div>
                                        <div class="stat-label">Modules</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="course-stat">
                                        <div class="stat-number">${course.enrollment_count}</div>
                                        <div class="stat-label">Enrolled</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="course-stat">
                                        <div class="stat-number">${course.completion_rate}%</div>
                                        <div class="stat-label">Complete</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="course-tags">
                            <span class="badge bg-light text-dark me-1">
                                <i class="fas fa-folder me-1"></i>
                                ${this.escapeHtml(course.category_name)}
                            </span>
                            ${course.subcategory_name ? `
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-tag me-1"></i>
                                    ${this.escapeHtml(course.subcategory_name)}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Created: ${this.formatDate(course.created_at)}
                        </small>
                        ${course.updated_at !== course.created_at ? `
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-edit me-1"></i>
                                Updated: ${this.formatDate(course.updated_at)}
                            </small>
                        ` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    updateStatistics() {
        const totalCourses = this.courses.length;
        const publishedCourses = this.courses.filter(c => c.course_status === 'active').length;
        const draftCourses = this.courses.filter(c => c.course_status === 'inactive').length;
        const totalEnrollments = this.courses.reduce((sum, c) => sum + (c.enrollment_count || 0), 0);

        // Update statistics cards
        const statsCards = document.querySelectorAll('.card.bg-primary, .card.bg-success, .card.bg-warning, .card.bg-info');
        if (statsCards.length >= 4) {
            statsCards[0].querySelector('h4').textContent = totalCourses;
            statsCards[1].querySelector('h4').textContent = publishedCourses;
            statsCards[2].querySelector('h4').textContent = draftCourses;
            statsCards[3].querySelector('h4').textContent = totalEnrollments;
        }
    }

    updateSearchResults() {
        const resultsInfo = document.getElementById('searchResultsInfo');
        if (resultsInfo) {
            const total = this.courses.length;
            const filtered = this.filteredCourses.length;
            
            if (filtered === total) {
                resultsInfo.style.display = 'none';
            } else {
                resultsInfo.style.display = 'block';
                resultsInfo.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Showing ${filtered} of ${total} courses
                    </div>
                `;
            }
        }
    }

    async publishCourse(courseId) {
        this.showConfirmationModal(
            'Publish Course',
            'Are you sure you want to publish this course? It will be visible to all users.',
            async () => {
                try {
                    const response = await fetch(`/api/courses/${courseId}/publish`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showToast('Course published successfully', 'success');
                        this.loadCourses(); // Reload to update status
                    } else {
                        this.showToast(data.message || 'Failed to publish course', 'error');
                    }
                } catch (error) {
                    console.error('Error publishing course:', error);
                    this.showToast('Error publishing course', 'error');
                }
            }
        );
    }

    async unpublishCourse(courseId) {
        this.showConfirmationModal(
            'Unpublish Course',
            'Are you sure you want to unpublish this course? It will no longer be visible to users.',
            async () => {
                try {
                    const response = await fetch(`/api/courses/${courseId}/unpublish`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showToast('Course unpublished successfully', 'success');
                        this.loadCourses(); // Reload to update status
                    } else {
                        this.showToast(data.message || 'Failed to unpublish course', 'error');
                    }
                } catch (error) {
                    console.error('Error unpublishing course:', error);
                    this.showToast('Error unpublishing course', 'error');
                }
            }
        );
    }

    async deleteCourse(courseId) {
        this.showConfirmationModal(
            'Delete Course',
            'Are you sure you want to delete this course? This action cannot be undone and will remove all associated data.',
            async () => {
                try {
                    const response = await fetch(`/api/courses/${courseId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.showToast('Course deleted successfully', 'success');
                        this.loadCourses(); // Reload to remove from list
                    } else {
                        this.showToast(data.message || 'Failed to delete course', 'error');
                    }
                } catch (error) {
                    console.error('Error deleting course:', error);
                    this.showToast('Error deleting course', 'error');
                }
            }
        );
    }

    showConfirmationModal(title, message, onConfirm) {
        const modal = document.getElementById('confirmationModal');
        if (!modal) return;

        const titleElement = modal.querySelector('#confirmationModalTitle');
        const bodyElement = modal.querySelector('#confirmationModalBody');
        const confirmButton = modal.querySelector('#confirmationModalConfirm');

        titleElement.textContent = title;
        bodyElement.textContent = message;

        // Remove existing event listeners
        const newConfirmButton = confirmButton.cloneNode(true);
        confirmButton.parentNode.replaceChild(newConfirmButton, confirmButton);

        // Add new event listener
        newConfirmButton.addEventListener('click', () => {
            onConfirm();
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });

        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }

    showLoading(show) {
        const loadingIndicator = document.getElementById('loadingIndicator');
        const coursesGrid = document.getElementById('coursesGrid');
        
        if (loadingIndicator) {
            loadingIndicator.style.display = show ? 'block' : 'none';
        }
        
        if (coursesGrid) {
            coursesGrid.style.display = show ? 'none' : 'block';
        }
    }

    showToast(message, type = 'info') {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            // Fallback toast implementation
            const toast = document.createElement('div');
            toast.className = `toast text-bg-${type === 'error' ? 'danger' : type} show`;
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.style.minWidth = '300px';
            toast.style.maxWidth = '400px';
            
            toast.innerHTML = `
                <div class="toast-body d-flex align-items-center">
                    <i class="fas fa-${this.getToastIcon(type)} me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close btn-close-white ms-2" onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);
        }
    }

    showError(message) {
        this.showToast(message, 'error');
    }

    getStatusBadgeColor(status) {
        const colors = {
            'active': 'success',
            'inactive': 'warning',
            'archived': 'secondary'
        };
        return colors[status] || 'secondary';
    }

    getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-triangle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
}

// Initialize course management when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.courseManager = new CourseManagementManager();
});

// Export for global access
window.CourseManagementManager = CourseManagementManager; 