/**
 * Course Creation JavaScript
 * Enhanced with drag-and-drop, advanced validation, and custom modals
 */

class CourseCreationManager {
    constructor() {
        this.currentTab = 0;
        this.modules = [];
        this.prerequisites = [];
        this.assessments = [];
        this.feedback = [];
        this.surveys = [];
        this.draggedElement = null;
        this.dragOverElement = null;
        this.isSubmitting = false;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeDragAndDrop();
        this.setupValidation();
        this.initializeTabs();
        this.loadInitialData();
    }

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('.course-creation-tabs .nav-link').forEach((tab, index) => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(index);
            });
        });

        // Form submission
        const form = document.getElementById('courseCreationForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Add module button
        const addModuleBtn = document.getElementById('addModuleBtn');
        if (addModuleBtn) {
            addModuleBtn.addEventListener('click', () => this.addModule());
        }

        // Add prerequisite button
        const addPrerequisiteBtn = document.getElementById('addPrerequisiteBtn');
        if (addPrerequisiteBtn) {
            addPrerequisiteBtn.addEventListener('click', () => this.showPrerequisiteModal());
        }

        // Add assessment button
        const addAssessmentBtn = document.getElementById('addAssessmentBtn');
        if (addAssessmentBtn) {
            addAssessmentBtn.addEventListener('click', () => this.showAssessmentModal());
        }

        // Add feedback button
        const addFeedbackBtn = document.getElementById('addFeedbackBtn');
        if (addFeedbackBtn) {
            addFeedbackBtn.addEventListener('click', () => this.showFeedbackModal());
        }

        // Add survey button
        const addSurveyBtn = document.getElementById('addSurveyBtn');
        if (addSurveyBtn) {
            addSurveyBtn.addEventListener('click', () => this.showSurveyModal());
        }

        // Category change handlers
        const categorySelect = document.getElementById('courseCategory');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => this.loadSubcategories());
        }

        // Real-time validation
        this.setupRealTimeValidation();

        // Keyboard shortcuts
        this.setupKeyboardShortcuts();
    }

    initializeDragAndDrop() {
        // Module drag and drop
        this.setupModuleDragAndDrop();
        
        // Global drag and drop handlers
        document.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.handleGlobalDragOver(e);
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            this.handleGlobalDrop(e);
        });
    }

    setupModuleDragAndDrop() {
        const modulesContainer = document.getElementById('modulesContainer');
        if (!modulesContainer) return;

        modulesContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.handleModuleDragOver(e);
        });

        modulesContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            this.handleModuleDrop(e);
        });

        // Add drag event listeners to module items
        this.addModuleDragListeners();
    }

    handleModuleDragOver(e) {
        const afterElement = this.getDragAfterElement(e.currentTarget, e.clientY);
        const draggable = document.querySelector('.dragging');
        
        if (draggable) {
            if (afterElement == null) {
                e.currentTarget.appendChild(draggable);
            } else {
                e.currentTarget.insertBefore(draggable, afterElement);
            }
        }
    }

    handleModuleDrop(e) {
        const draggedModule = this.draggedElement;
        if (draggedModule) {
            draggedModule.classList.remove('dragging');
            this.updateModuleOrder();
            this.showToast('Module order updated successfully', 'success');
        }
        this.draggedElement = null;
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.module-item:not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    setupValidation() {
        this.validationRules = {
            courseName: {
                required: true,
                minLength: 3,
                maxLength: 100,
                pattern: /^[a-zA-Z0-9\s\-_()]+$/
            },
            courseDescription: {
                required: true,
                minLength: 10,
                maxLength: 500
            },
            courseCategory: {
                required: true
            },
            courseSubcategory: {
                required: true
            },
            modules: {
                required: true,
                minCount: 1
            }
        };

        this.errorMessages = {
            courseName: {
                required: 'Course name is required',
                minLength: 'Course name must be at least 3 characters',
                maxLength: 'Course name cannot exceed 100 characters',
                pattern: 'Course name contains invalid characters'
            },
            courseDescription: {
                required: 'Course description is required',
                minLength: 'Course description must be at least 10 characters',
                maxLength: 'Course description cannot exceed 500 characters'
            },
            courseCategory: {
                required: 'Please select a course category'
            },
            courseSubcategory: {
                required: 'Please select a course subcategory'
            },
            modules: {
                required: 'At least one module is required',
                minCount: 'At least one module is required'
            }
        };
    }

    setupRealTimeValidation() {
        const inputs = document.querySelectorAll('#courseCreationForm input, #courseCreationForm textarea, #courseCreationForm select');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }

    validateField(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        const rules = this.validationRules[fieldName];
        
        if (!rules) return true;

        // Clear previous errors
        this.clearFieldError(field);

        // Check required
        if (rules.required && !value) {
            this.showFieldError(field, this.errorMessages[fieldName].required);
            return false;
        }

        if (value) {
            // Check min length
            if (rules.minLength && value.length < rules.minLength) {
                this.showFieldError(field, this.errorMessages[fieldName].minLength);
                return false;
            }

            // Check max length
            if (rules.maxLength && value.length > rules.maxLength) {
                this.showFieldError(field, this.errorMessages[fieldName].maxLength);
                return false;
            }

            // Check pattern
            if (rules.pattern && !rules.pattern.test(value)) {
                this.showFieldError(field, this.errorMessages[fieldName].pattern);
                return false;
            }
        }

        return true;
    }

    showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.style.display = 'none';
        }
    }

    validateForm() {
        let isValid = true;
        const errors = [];

        // Validate basic fields
        const fields = ['courseName', 'courseDescription', 'courseCategory', 'courseSubcategory'];
        fields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field && !this.validateField(field)) {
                isValid = false;
                errors.push(this.errorMessages[fieldName].required || 'Invalid field');
            }
        });

        // Validate modules
        if (this.modules.length === 0) {
            isValid = false;
            errors.push('At least one module is required');
            this.markTabAsError(1); // Modules tab
        } else {
            this.clearTabError(1);
        }

        // Validate prerequisites (if any)
        if (this.prerequisites.length > 0) {
            const hasValidPrerequisites = this.prerequisites.every(pre => pre.id && pre.title);
            if (!hasValidPrerequisites) {
                isValid = false;
                errors.push('Some prerequisites are invalid');
                this.markTabAsError(2); // Prerequisites tab
            } else {
                this.clearTabError(2);
            }
        }

        // Show validation errors
        if (!isValid) {
            this.showValidationErrors(errors);
        }

        return isValid;
    }

    showValidationErrors(errors) {
        const errorContainer = document.getElementById('validationErrors');
        if (!errorContainer) return;

        errorContainer.innerHTML = `
            <div class="course-validation-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        ${errors.map(error => `<li>${error}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
        errorContainer.style.display = 'block';
    }

    markTabAsError(tabIndex) {
        const tabs = document.querySelectorAll('.course-creation-tabs .nav-link');
        if (tabs[tabIndex]) {
            tabs[tabIndex].classList.add('tab-error');
        }
    }

    clearTabError(tabIndex) {
        const tabs = document.querySelectorAll('.course-creation-tabs .nav-link');
        if (tabs[tabIndex]) {
            tabs[tabIndex].classList.remove('tab-error');
        }
    }

    initializeTabs() {
        this.showTab(0);
    }

    switchTab(tabIndex) {
        if (this.validateCurrentTab()) {
            this.showTab(tabIndex);
        } else {
            this.showToast('Please fix errors in the current tab before proceeding', 'warning');
        }
    }

    validateCurrentTab() {
        switch (this.currentTab) {
            case 0: // Basic Info
                return this.validateBasicInfo();
            case 1: // Modules
                return this.modules.length > 0;
            case 2: // Prerequisites
                return true; // Optional
            case 3: // Assessments
                return true; // Optional
            case 4: // Feedback
                return true; // Optional
            case 5: // Surveys
                return true; // Optional
            default:
                return true;
        }
    }

    validateBasicInfo() {
        const requiredFields = ['courseName', 'courseDescription', 'courseCategory', 'courseSubcategory'];
        return requiredFields.every(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            return field && field.value.trim() !== '';
        });
    }

    showTab(tabIndex) {
        // Hide all tab contents
        document.querySelectorAll('.course-tab-content').forEach(content => {
            content.style.display = 'none';
        });

        // Remove active class from all tabs
        document.querySelectorAll('.course-creation-tabs .nav-link').forEach(tab => {
            tab.classList.remove('active');
        });

        // Show selected tab content
        const tabContent = document.querySelectorAll('.course-tab-content')[tabIndex];
        if (tabContent) {
            tabContent.style.display = 'block';
        }

        // Add active class to selected tab
        const selectedTab = document.querySelectorAll('.course-creation-tabs .nav-link')[tabIndex];
        if (selectedTab) {
            selectedTab.classList.add('active');
        }

        this.currentTab = tabIndex;
    }

    loadInitialData() {
        this.loadCategories();
        this.loadSubcategories();
    }

    async loadCategories() {
        try {
            const response = await fetch('/api/course-categories');
            const data = await response.json();
            
            if (data.success) {
                const select = document.getElementById('courseCategory');
                if (select) {
                    select.innerHTML = '<option value="">Select Category</option>';
                    data.categories.forEach(category => {
                        select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
                    });
                }
            }
        } catch (error) {
            console.error('Error loading categories:', error);
            this.showToast('Error loading categories', 'error');
        }
    }

    async loadSubcategories() {
        const categoryId = document.getElementById('courseCategory')?.value;
        if (!categoryId) {
            const select = document.getElementById('courseSubcategory');
            if (select) {
                select.innerHTML = '<option value="">Select Subcategory</option>';
            }
            return;
        }

        try {
            const response = await fetch(`/api/course-subcategories/${categoryId}`);
            const data = await response.json();
            
            if (data.success) {
                const select = document.getElementById('courseSubcategory');
                if (select) {
                    select.innerHTML = '<option value="">Select Subcategory</option>';
                    data.subcategories.forEach(subcategory => {
                        select.innerHTML += `<option value="${subcategory.id}">${subcategory.name}</option>`;
                    });
                }
            }
        } catch (error) {
            console.error('Error loading subcategories:', error);
            this.showToast('Error loading subcategories', 'error');
        }
    }

    addModule() {
        const moduleId = Date.now();
        const module = {
            id: moduleId,
            title: '',
            description: '',
            content: []
        };

        this.modules.push(module);
        this.renderModules();
        this.showToast('Module added successfully', 'success');
    }

    removeModule(moduleId) {
        this.showConfirmationModal(
            'Remove Module',
            'Are you sure you want to remove this module? This action cannot be undone.',
            () => {
                this.modules = this.modules.filter(module => module.id !== moduleId);
                this.renderModules();
                this.showToast('Module removed successfully', 'success');
            }
        );
    }

    renderModules() {
        const container = document.getElementById('modulesContainer');
        if (!container) return;

        container.innerHTML = this.modules.map((module, index) => `
            <div class="module-item" draggable="true" data-module-id="${module.id}">
                <div class="module-header">
                    <h6 class="module-title">Module ${index + 1}</h6>
                    <div class="module-actions">
                        <i class="fas fa-grip-vertical module-drag-handle" title="Drag to reorder"></i>
                        <button type="button" class="module-remove-btn" onclick="courseManager.removeModule(${module.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="moduleTitle_${module.id}">Module Title *</label>
                            <input type="text" class="form-control" id="moduleTitle_${module.id}" 
                                   value="${module.title}" onchange="courseManager.updateModule(${module.id}, 'title', this.value)">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="moduleDescription_${module.id}">Module Description</label>
                            <textarea class="form-control" id="moduleDescription_${module.id}" rows="3"
                                      onchange="courseManager.updateModule(${module.id}, 'description', this.value)">${module.description}</textarea>
                        </div>
                    </div>
                </div>
                <div class="module-content-section">
                    <h6>Module Content</h6>
                    <div class="vlr-content-list" id="moduleContent_${module.id}">
                        ${this.renderModuleContent(module.content)}
                    </div>
                    <button type="button" class="add-item-btn" onclick="courseManager.showVLRModal(${module.id})">
                        <i class="fas fa-plus"></i>
                        Add VLR Content
                    </button>
                </div>
            </div>
        `).join('');

        // Reinitialize drag and drop for new modules
        this.initializeModuleDragAndDrop();
    }

    renderModuleContent(content) {
        if (!content || content.length === 0) {
            return '<p class="text-muted">No content added yet</p>';
        }

        return content.map(item => `
            <div class="vlr-content-item">
                <div class="vlr-content-info">
                    <i class="vlr-content-icon fas ${this.getVLRIcon(item.type)}"></i>
                    <span class="vlr-content-title">${item.title}</span>
                    <span class="vlr-content-type">${item.type}</span>
                </div>
                <button type="button" class="vlr-content-remove" onclick="courseManager.removeVLRContent(${item.moduleId}, ${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    getVLRIcon(type) {
        const icons = {
            'scorm': 'fa-cube',
            'video': 'fa-video',
            'audio': 'fa-volume-up',
            'document': 'fa-file-alt',
            'assessment': 'fa-question-circle',
            'survey': 'fa-clipboard-list',
            'feedback': 'fa-comments',
            'interactive': 'fa-mouse-pointer',
            'assignment': 'fa-tasks'
        };
        return icons[type] || 'fa-file';
    }

    updateModule(moduleId, field, value) {
        const module = this.modules.find(m => m.id === moduleId);
        if (module) {
            module[field] = value;
        }
    }

    updateModuleOrder() {
        const moduleElements = document.querySelectorAll('.module-item');
        const newOrder = [];
        
        moduleElements.forEach((element, index) => {
            const moduleId = parseInt(element.dataset.moduleId);
            const module = this.modules.find(m => m.id === moduleId);
            if (module) {
                newOrder.push(module);
            }
        });

        this.modules = newOrder;
    }

    showPrerequisiteModal() {
        this.showVLRSelectionModal('prerequisites', 'Select Prerequisite Courses');
    }

    showAssessmentModal() {
        this.showVLRSelectionModal('assessments', 'Select Assessments');
    }

    showFeedbackModal() {
        this.showVLRSelectionModal('feedback', 'Select Feedback Forms');
    }

    showSurveyModal() {
        this.showVLRSelectionModal('surveys', 'Select Surveys');
    }

    showVLRModal(moduleId) {
        this.showVLRSelectionModal('moduleContent', 'Select VLR Content', moduleId);
    }

    async showVLRSelectionModal(type, title, moduleId = null) {
        try {
            const response = await fetch('/api/vlr-content');
            const data = await response.json();
            
            if (data.success) {
                this.showVLRContentModal(title, data.content, type, moduleId);
            }
        } catch (error) {
            console.error('Error loading VLR content:', error);
            this.showToast('Error loading VLR content', 'error');
        }
    }

    showVLRContentModal(title, content, type, moduleId = null) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'vlrSelectionModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            ${content.map(item => `
                                <div class="col-md-6 mb-3">
                                    <div class="card vlr-selection-card" data-vlr-id="${item.id}" data-vlr-type="${item.type}">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <i class="fas ${this.getVLRIcon(item.type)} me-3" style="font-size: 1.5em; color: #4b0082;"></i>
                                                <div>
                                                    <h6 class="card-title mb-1">${item.title}</h6>
                                                    <small class="text-muted">${item.type}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="courseManager.addSelectedVLR('${type}', ${moduleId})">Add Selected</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Handle card selection
        modal.querySelectorAll('.vlr-selection-card').forEach(card => {
            card.addEventListener('click', () => {
                card.classList.toggle('selected');
            });
        });

        // Clean up modal after hiding
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }

    addSelectedVLR(type, moduleId = null) {
        const selectedCards = document.querySelectorAll('#vlrSelectionModal .vlr-selection-card.selected');
        const selectedItems = [];

        selectedCards.forEach(card => {
            selectedItems.push({
                id: parseInt(card.dataset.vlrId),
                type: card.dataset.vlrType,
                title: card.querySelector('.card-title').textContent
            });
        });

        if (selectedItems.length === 0) {
            this.showToast('Please select at least one item', 'warning');
            return;
        }

        switch (type) {
            case 'prerequisites':
                this.prerequisites.push(...selectedItems);
                this.renderPrerequisites();
                break;
            case 'assessments':
                this.assessments.push(...selectedItems);
                this.renderAssessments();
                break;
            case 'feedback':
                this.feedback.push(...selectedItems);
                this.renderFeedback();
                break;
            case 'surveys':
                this.surveys.push(...selectedItems);
                this.renderSurveys();
                break;
            case 'moduleContent':
                if (moduleId) {
                    const module = this.modules.find(m => m.id === moduleId);
                    if (module) {
                        module.content.push(...selectedItems);
                        this.renderModules();
                    }
                }
                break;
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('vlrSelectionModal'));
        if (modal) {
            modal.hide();
        }

        this.showToast(`${selectedItems.length} item(s) added successfully`, 'success');
    }

    renderPrerequisites() {
        const container = document.getElementById('prerequisitesContainer');
        if (!container) return;

        container.innerHTML = this.prerequisites.map(item => `
            <div class="prerequisite-item">
                <div class="prerequisite-info">
                    <h6 class="prerequisite-title">${item.title}</h6>
                    <p class="prerequisite-category">${item.type}</p>
                </div>
                <button type="button" class="prerequisite-remove" onclick="courseManager.removePrerequisite(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    renderAssessments() {
        const container = document.getElementById('assessmentsContainer');
        if (!container) return;

        container.innerHTML = this.assessments.map(item => `
            <div class="vlr-content-item">
                <div class="vlr-content-info">
                    <i class="vlr-content-icon fas ${this.getVLRIcon(item.type)}"></i>
                    <span class="vlr-content-title">${item.title}</span>
                    <span class="vlr-content-type">${item.type}</span>
                </div>
                <button type="button" class="vlr-content-remove" onclick="courseManager.removeAssessment(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    renderFeedback() {
        const container = document.getElementById('feedbackContainer');
        if (!container) return;

        container.innerHTML = this.feedback.map(item => `
            <div class="vlr-content-item">
                <div class="vlr-content-info">
                    <i class="vlr-content-icon fas ${this.getVLRIcon(item.type)}"></i>
                    <span class="vlr-content-title">${item.title}</span>
                    <span class="vlr-content-type">${item.type}</span>
                </div>
                <button type="button" class="vlr-content-remove" onclick="courseManager.removeFeedback(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    renderSurveys() {
        const container = document.getElementById('surveysContainer');
        if (!container) return;

        container.innerHTML = this.surveys.map(item => `
            <div class="vlr-content-item">
                <div class="vlr-content-info">
                    <i class="vlr-content-icon fas ${this.getVLRIcon(item.type)}"></i>
                    <span class="vlr-content-title">${item.title}</span>
                    <span class="vlr-content-type">${item.type}</span>
                </div>
                <button type="button" class="vlr-content-remove" onclick="courseManager.removeSurvey(${item.id})">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
    }

    removePrerequisite(id) {
        this.prerequisites = this.prerequisites.filter(item => item.id !== id);
        this.renderPrerequisites();
        this.showToast('Prerequisite removed', 'success');
    }

    removeAssessment(id) {
        this.assessments = this.assessments.filter(item => item.id !== id);
        this.renderAssessments();
        this.showToast('Assessment removed', 'success');
    }

    removeFeedback(id) {
        this.feedback = this.feedback.filter(item => item.id !== id);
        this.renderFeedback();
        this.showToast('Feedback form removed', 'success');
    }

    removeSurvey(id) {
        this.surveys = this.surveys.filter(item => item.id !== id);
        this.renderSurveys();
        this.showToast('Survey removed', 'success');
    }

    removeVLRContent(moduleId, contentId) {
        const module = this.modules.find(m => m.id === moduleId);
        if (module) {
            module.content = module.content.filter(item => item.id !== contentId);
            this.renderModules();
            this.showToast('Content removed from module', 'success');
        }
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.handleFormSubmit();
            }

            // Tab navigation with arrow keys
            if (e.key === 'ArrowRight' && e.ctrlKey) {
                e.preventDefault();
                this.switchTab(Math.min(this.currentTab + 1, 5));
            }

            if (e.key === 'ArrowLeft' && e.ctrlKey) {
                e.preventDefault();
                this.switchTab(Math.max(this.currentTab - 1, 0));
            }
        });
    }

    async handleFormSubmit(e) {
        if (e) e.preventDefault();

        if (this.isSubmitting) {
            this.showToast('Form is already being submitted', 'warning');
            return;
        }

        if (!this.validateForm()) {
            this.showToast('Please fix the validation errors', 'error');
            return;
        }

        this.isSubmitting = true;
        this.showLoadingOverlay('Creating course...');

        try {
            const formData = this.collectFormData();
            const response = await fetch('/course-creation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Course created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/course-management';
                }, 2000);
            } else {
                this.showToast(result.message || 'Error creating course', 'error');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            this.showToast('An error occurred while creating the course', 'error');
        } finally {
            this.isSubmitting = false;
            this.hideLoadingOverlay();
        }
    }

    collectFormData() {
        const form = document.getElementById('courseCreationForm');
        const formData = new FormData(form);
        
        return {
            courseName: formData.get('courseName'),
            courseDescription: formData.get('courseDescription'),
            courseCategory: formData.get('courseCategory'),
            courseSubcategory: formData.get('courseSubcategory'),
            modules: this.modules,
            prerequisites: this.prerequisites,
            assessments: this.assessments,
            feedback: this.feedback,
            surveys: this.surveys
        };
    }

    showLoadingOverlay(message = 'Loading...') {
        const overlay = document.createElement('div');
        overlay.className = 'course-loading-overlay';
        overlay.innerHTML = `
            <div class="course-loading-spinner">
                <div class="spinner-border" role="status"></div>
                <p class="course-loading-text">${message}</p>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    hideLoadingOverlay() {
        const overlay = document.querySelector('.course-loading-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    showConfirmationModal(title, message, onConfirm, onCancel = null) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${title}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="courseManager.confirmAction()">
                            <i class="fas fa-check me-1"></i>
                            Confirm
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Store the confirmation callback
        this.pendingConfirmation = onConfirm;
        this.pendingCancel = onCancel;

        // Clean up modal after hiding
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
            this.pendingConfirmation = null;
            this.pendingCancel = null;
        });

        // Handle cancel button
        modal.querySelector('.btn-secondary').addEventListener('click', () => {
            if (this.pendingCancel) {
                this.pendingCancel();
            }
        });
    }

    confirmAction() {
        if (this.pendingConfirmation) {
            this.pendingConfirmation();
        }
        
        const modal = bootstrap.Modal.getInstance(document.querySelector('.modal'));
        if (modal) {
            modal.hide();
        }
    }

    showToast(message, type = 'info', duration = 5000) {
        // Use existing toast system if available
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            // Enhanced fallback toast implementation
            const toast = document.createElement('div');
            toast.className = `toast text-bg-${type === 'error' ? 'danger' : type} show`;
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.style.minWidth = '300px';
            toast.style.maxWidth = '400px';
            toast.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
            toast.style.borderRadius = '8px';
            toast.style.border = 'none';
            
            const icon = this.getToastIcon(type);
            const title = this.getToastTitle(type);
            
            toast.innerHTML = `
                <div class="toast-body d-flex align-items-center">
                    <i class="fas ${icon} me-2" style="font-size: 1.1em;"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${title}</div>
                        <div class="small">${message}</div>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-2" 
                            onclick="this.parentElement.parentElement.remove()"></button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-remove after duration
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    toast.style.transition = 'all 0.3s ease';
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.remove();
                        }
                    }, 300);
                }
            }, duration);

            // Add click to dismiss
            toast.addEventListener('click', (e) => {
                if (!e.target.classList.contains('btn-close')) {
                    toast.remove();
                }
            });
        }
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

    getToastTitle(type) {
        const titles = {
            success: 'Success',
            error: 'Error',
            warning: 'Warning',
            info: 'Information'
        };
        return titles[type] || 'Information';
    }

    showSuccessToast(message) {
        this.showToast(message, 'success', 4000);
    }

    showErrorToast(message) {
        this.showToast(message, 'error', 6000);
    }

    showWarningToast(message) {
        this.showToast(message, 'warning', 5000);
    }

    showInfoToast(message) {
        this.showToast(message, 'info', 4000);
    }

    initializeModuleDragAndDrop() {
        const modulesContainer = document.getElementById('modulesContainer');
        if (!modulesContainer) return;

        modulesContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.handleModuleDragOver(e);
        });

        modulesContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            this.handleModuleDrop(e);
        });

        // Add drag event listeners to module items
        this.addModuleDragListeners();
    }

    addModuleDragListeners() {
        document.querySelectorAll('.module-item').forEach(item => {
            item.addEventListener('dragstart', (e) => {
                this.draggedElement = item;
                item.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('dragging');
                this.draggedElement = null;
            });

            // Drag handle functionality
            const dragHandle = item.querySelector('.module-drag-handle');
            if (dragHandle) {
                dragHandle.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    item.draggable = true;
                });

                dragHandle.addEventListener('mouseup', () => {
                    item.draggable = false;
                });
            }
        });
    }

    handleGlobalDragOver(e) {
        // Handle global drag over events if needed
        e.preventDefault();
    }

    handleGlobalDrop(e) {
        // Handle global drop events if needed
        e.preventDefault();
    }
}

// Initialize course creation manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.courseManager = new CourseCreationManager();
});

// Export for global access
window.CourseCreationManager = CourseCreationManager; 