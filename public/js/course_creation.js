/**
 * Course Creation JavaScript
 * Enhanced with drag-and-drop, advanced validation, and custom modals
 */

// Instantiate CourseCreationManager only after modal content is loaded.
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
            console.log('[DEBUG] Add Module button found, binding click event');
            addModuleBtn.addEventListener('click', () => {
                console.log('[DEBUG] Add Module button clicked');
                this.addModule();
            });
        } else {
            console.warn('[DEBUG] Add Module button NOT found');
        }

        // Add prerequisite button
        const addPrerequisiteBtn = document.getElementById('add_prerequisite');
        if (addPrerequisiteBtn) {
            console.debug('[DEBUG] Add Prerequisite button found, binding click event');
            addPrerequisiteBtn.addEventListener('click', () => {
                console.debug('[DEBUG] Add Prerequisite button clicked');
                this.showPrerequisiteModal();
            });
        } else {
            console.warn('[DEBUG] Add Prerequisite button NOT found');
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
        // Comment out or remove any global or immediate instantiation of CourseCreationManager
        // ... existing code ...
        // ... existing code ...
        // ... existing code ...
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
        console.log('[DEBUG] addModule() called');
        const moduleId = Date.now();
        const module = {
            id: moduleId,
            title: '',
            description: '',
            content: []
        };
        this.modules.push(module);
        console.log('[DEBUG] Module pushed. Modules array:', this.modules);
        this.renderModules();
        this.showToast('New module created. Now add VLR content and fill in module details.', 'info');
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
        console.log('[DEBUG] renderModules() called. Modules:', this.modules);
        const container = document.getElementById('modulesContainer');
        if (!container) {
            console.warn('[DEBUG] modulesContainer NOT found');
            return;
        }

        // Add Module button at the top
        let html = `
            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-outline-primary" id="addModuleBtnTop">
                    <i class="fas fa-plus me-1"></i> Add Module
                </button>
            </div>
        `;

        html += this.modules.map((module, index) => `
            <div class="module-card card mb-4 shadow-sm" draggable="true" data-module-id="${module.id}">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary me-3">${index + 1}</span>
                        <h6 class="mb-0 module-title">${module.title || `Module ${index + 1}`}</h6>
                    </div>
                    <div class="module-actions d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary module-move-up" title="Move Up" ${index === 0 ? 'disabled' : ''} onclick="courseManager.moveModuleUp(${index})">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary module-move-down" title="Move Down" ${index === this.modules.length - 1 ? 'disabled' : ''} onclick="courseManager.moveModuleDown(${index})">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <i class="fas fa-grip-vertical module-drag-handle ms-2" title="Drag to reorder"></i>
                        <button type="button" class="btn btn-sm btn-outline-danger module-remove-btn ms-2" onclick="courseManager.removeModule(${module.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
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
                                <textarea class="form-control" id="moduleDescription_${module.id}" rows="2"
                                          onchange="courseManager.updateModule(${module.id}, 'description', this.value)">${module.description}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="module-content-section">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Module Content</h6>
                            <span class="text-muted small">${module.content.length} VLR item${module.content.length !== 1 ? 's' : ''}</span>
                        </div>
                        <div class="vlr-content-list d-flex flex-wrap gap-2" id="moduleContent_${module.id}">
                            ${this.renderModuleContent(module.content, module.id)}
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm mt-2 px-2 py-1 add-item-btn" style="font-size:0.95em; min-width:unset;" title="Add VLR Content" onclick="courseManager.showVLRModal(${module.id})">
                            <i class="fas fa-plus me-1"></i> Add VLR Content
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Add Module button at the bottom
        html += `
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-outline-primary" id="addModuleBtnBottom">
                    <i class="fas fa-plus me-1"></i> Add Module
                </button>
            </div>
        `;

        container.innerHTML = html;

        // Bind Add Module buttons
        document.getElementById('addModuleBtnTop').onclick = () => this.addModule();
        document.getElementById('addModuleBtnBottom').onclick = () => this.addModule();

        // Reinitialize drag and drop for new modules
        this.initializeModuleDragAndDrop();
    }

    renderModuleContent(content, moduleId) {
        if (!content || content.length === 0) {
            return '<span class="text-muted">No content added yet</span>';
        }

        return content.map((item, idx) => `
            <div class="vlr-chip card px-2 py-1 d-flex flex-row align-items-center overflow-hidden" draggable="false" data-content-id="${item.id}" data-module-id="${moduleId}">
                <i class="vlr-content-icon fas ${this.getVLRIcon(item.type)} me-2"></i>
                <span class="vlr-content-title flex-grow-1 text-truncate" title="${item.title}">${item.title.length > 20 ? item.title.substring(0, 18) + '…' : item.title}</span>
                <span class="badge bg-light text-dark ms-2 text-truncate" style="max-width: 80px;" title="${item.type}">${item.type}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-2 flex-shrink-0 move-up-btn" title="Move Up" ${idx === 0 ? 'disabled' : ''} onclick="courseManager.moveVLRUp(${moduleId}, ${idx})">
                    <i class="fas fa-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1 flex-shrink-0 move-down-btn" title="Move Down" ${idx === content.length - 1 ? 'disabled' : ''} onclick="courseManager.moveVLRDown(${moduleId}, ${idx})">
                    <i class="fas fa-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2 flex-shrink-0" title="Remove" onclick="courseManager.removeVLRContent(${moduleId}, ${item.id})">
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
            this.renderModules();
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
        // Use the VLR content selection modal for prerequisites
        const preselectedIds = this.prerequisites.map(item => ({ id: item.id, type: item.prereqType || item.type }));
        this.showVLRSelectionModal('prerequisites', 'Select Prerequisite Content', null, preselectedIds);
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
        // Find the module and pass its current content IDs and types
        const module = this.modules.find(m => m.id === moduleId);
        const preselectedIds = module ? module.content.map(item => ({ id: item.id, type: item.type })) : [];
        this.showVLRSelectionModal('moduleContent', 'Select VLR Content', moduleId, preselectedIds);
    }

    showVLRSelectionModal(type, title, moduleId = null, preselectedIds = []) {
        console.log('[DEBUG] showVLRSelectionModal called with type:', type, 'title:', title, 'moduleId:', moduleId);
        console.log('[DEBUG] typeof window.vlrContent:', typeof window.vlrContent);
        console.log('[DEBUG] window.vlrContent:', window.vlrContent);
        if (window.vlrContent && Object.keys(window.vlrContent).length > 0) {
            console.log('[DEBUG] VLR content found, opening modal.');
            this.showVLRContentModal(title, window.vlrContent, type, moduleId, preselectedIds);
        } else {
            console.error('[DEBUG] VLR content not available or empty!');
            this.showToast('VLR content not available', 'error');
        }
    }

    showVLRContentModal(title, content, type, moduleId = null, preselectedIds = []) {
        // Group content by type
        const groupedContent = {};
        content.forEach(item => {
            if (!groupedContent[item.type]) {
                groupedContent[item.type] = [];
            }
            groupedContent[item.type].push(item);
        });

        // Fix: Declare isGridView before using it in modal.innerHTML
        let isGridView = localStorage.getItem('vlrViewMode') !== 'list';

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'vlrSelectionModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header align-items-center">
                        <h5 class="modal-title flex-grow-1">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- View Toggle and Search Bar -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <button type="button" class="btn btn-outline-primary btn-sm me-2 rounded-pill px-3 fw-bold" id="toggleVLRViewBtn" aria-pressed="${isGridView}">
                                    <i class="fas fa-th"></i> <span id="vlrViewLabel">Grid View</span>
                                </button>
                            </div>
                            <div class="flex-grow-1">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="vlrSearchInput" placeholder="Search VLR content...">
                                    <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">Clear</button>
                                </div>
                            </div>
                        </div>
                        <!-- Tabs for different VLR types -->
                        <ul class="nav nav-tabs" id="vlrTypeTabs" role="tablist">
                            ${Object.keys(groupedContent).map((contentType, index) => `
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link ${index === 0 ? 'active' : ''}" 
                                            id="tab-${contentType}" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#content-${contentType}" 
                                            type="button" 
                                            role="tab">
                                        <i class="fas ${this.getVLRIcon(contentType)} me-2"></i>
                                        ${this.getVLRTypeDisplayName(contentType)} 
                                        <span class="badge bg-secondary ms-2">${groupedContent[contentType].length}</span>
                                    </button>
                                </li>
                            `).join('')}
                        </ul>
                        <!-- Tab Content -->
                        <div class="tab-content mt-3" id="vlrTypeTabContent">
                            ${Object.keys(groupedContent).map((contentType, index) => `
                                <div class="tab-pane fade ${index === 0 ? 'show active' : ''}" 
                                     id="content-${contentType}" 
                                     role="tabpanel">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" class="form-check-input me-2" id="selectAllVLR-${contentType}" aria-label="Select all ${this.getVLRTypeDisplayName(contentType)}">
                                        <label for="selectAllVLR-${contentType}" class="form-check-label small">Select All</label>
                                    </div>
                                    <div class="row g-3" id="content-grid-${contentType}">
                                        ${groupedContent[contentType].map(item => `
                                            <div class="col-md-6 col-lg-4 vlr-content-item d-flex align-items-center gap-3 p-3 border rounded bg-white position-relative" 
                                                 tabindex="0" role="option" aria-label="${item.title}" 
                                                 data-vlr-id="${item.id}" 
                                                 data-vlr-type="${item.type}"
                                                 data-title="${item.title.toLowerCase()}"
                                                 data-description="${(item.description || '').toLowerCase()}">
                                                <i class="fas ${this.getVLRIcon(item.type)} mt-1" style="font-size: 2em; color: var(--bs-primary, #4b0082); filter: drop-shadow(0 2px 4px #b197fc33);" title="${this.getVLRTypeDisplayName(item.type)}" data-bs-toggle="tooltip"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold mb-1 text-truncate" title="${item.title}" data-bs-toggle="tooltip" style="font-size:1.1em;">${item.title.length > 30 ? item.title.substring(0, 27) + '...' : item.title}</div>
                                                    <small class="text-muted d-block mb-2 text-truncate" title="${item.description}" data-bs-toggle="tooltip">${item.description && item.description.length > 60 ? item.description.substring(0, 57) + '...' : item.description || ''}</small>
                                                    <div class="d-flex align-items-center gap-2 mt-1">
                                                        <span class="badge bg-primary-subtle text-primary small">${this.getVLRTypeDisplayName(item.type)}</span>
                                                        ${item.uploadDate ? `<span class="badge bg-secondary-subtle text-secondary small" title="Upload Date">${item.uploadDate}</span>` : ''}
                                                        ${item.fileSize ? `<span class="badge bg-info-subtle text-info small" title="File Size">${item.fileSize}</span>` : ''}
                                                    </div>
                                                </div>
                                                <div class="form-check ms-2 flex-shrink-0" style="min-width:2.5em;">
                                                    <input class="form-check-input vlr-checkbox" type="checkbox" value="${item.id}" id="vlr-${item.id}" aria-label="Select ${item.title}" style="accent-color: var(--bs-primary, #4b0082); width:1.3em; height:1.3em;">
                                                    <label class="form-check-label visually-hidden" for="vlr-${item.id}">Select</label>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                    ${groupedContent[contentType].length === 0 ? `
                                        <div class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No ${this.getVLRTypeDisplayName(contentType)} content available</p>
                                        </div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <div>
                                <span class="text-muted" id="selectedCount">0 items selected</span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="addSelectedVLRBtn" disabled>
                                    Add Selected (<span id="selectedCountBtn">0</span>)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        // Initialize search functionality
        this.initializeVLRSearch(modal);
        
        // Initialize checkbox functionality, pass preselectedIds
        this.initializeVLRCheckboxes(modal, type, moduleId, preselectedIds);

        // Clean up modal after hiding
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });

        // Add after modalInstance.show();
        const toggleBtn = modal.querySelector('#toggleVLRViewBtn');
        function updateVLRView() {
            const tabPanes = modal.querySelectorAll('.tab-pane');
            tabPanes.forEach(tabPane => {
                const grid = tabPane.querySelector('.row, .g-3, #content-grid-' + tabPane.id.replace('content-', ''));
                if (!grid) return;
                const items = grid.querySelectorAll('.vlr-content-item');
                // Reset all custom styles
                items.forEach(item => {
                    item.style.background = '';
                    item.style.borderColor = '';
                    item.style.borderRadius = '';
                    item.style.boxShadow = '';
                    item.style.transition = '';
                    item.style.color = '';
                    item.style.padding = '';
                });
                if (isGridView) {
                    grid.classList.add('row', 'g-3');
                    items.forEach(item => {
                        item.classList.remove('col-12', 'h-100', 'flex-column');
                        item.classList.add('col-md-6', 'col-lg-4', 'mb-3', 'p-2', 'd-flex');
                        item.style.background = 'var(--bs-primary-bg-subtle, #f3f0ff)';
                        item.style.borderColor = 'var(--bs-primary-border-subtle, #b197fc)';
                        item.style.borderRadius = '1rem';
                        item.style.boxShadow = '0 2px 8px 0 rgba(75,0,130,0.07)';
                        item.style.transition = 'box-shadow 0.2s, border-color 0.2s';
                        item.style.padding = '0.75rem';
                        item.style.color = 'var(--bs-body-color, #212529)';
                        item.style.height = '';
                        item.style.display = '';
                        item.style.flexDirection = '';
                        item.style.justifyContent = '';
                        // Icon style
                        const icon = item.querySelector('i');
                        if (icon) {
                            icon.style.fontSize = '1.5em';
                            icon.style.color = 'var(--bs-primary, #4b0082)';
                        }
                        // Title style
                        const title = item.querySelector('.fw-bold');
                        if (title) {
                            title.style.fontSize = '1em';
                            title.style.fontWeight = 'bold';
                            title.style.whiteSpace = 'nowrap';
                            title.style.overflow = 'hidden';
                            title.style.textOverflow = 'ellipsis';
                            title.setAttribute('title', title.textContent);
                        }
                        // Description style
                        const desc = item.querySelector('.text-muted');
                        if (desc) {
                            desc.style.fontSize = '0.95em';
                            desc.style.color = 'var(--bs-secondary-color, #6c757d)';
                            desc.style.whiteSpace = 'nowrap';
                            desc.style.overflow = 'hidden';
                            desc.style.textOverflow = 'ellipsis';
                            desc.setAttribute('title', desc.textContent);
                        }
                        // Hover effect
                        item.onmouseover = () => { item.style.boxShadow = '0 0 0 0.2rem #b197fc33'; item.style.borderColor = '#4b0082'; };
                        item.onmouseout = () => { item.style.boxShadow = '0 2px 8px 0 rgba(75,0,130,0.07)'; item.style.borderColor = 'var(--bs-primary-border-subtle, #b197fc)'; };
                    });
                    toggleBtn.innerHTML = '<i class="fas fa-th-list"></i> <span id="vlrViewLabel">List View</span>';
                } else {
                    grid.classList.remove('row', 'g-3');
                    items.forEach(item => {
                        item.classList.remove('col-md-6', 'col-lg-4');
                        item.classList.add('col-12', 'd-flex');
                        item.style.background = 'var(--bs-body-bg, #fff)';
                        item.style.borderColor = 'var(--bs-primary-border-subtle, #b197fc)';
                        item.style.borderRadius = '0.5rem';
                        item.style.boxShadow = '0 1px 4px 0 rgba(75,0,130,0.04)';
                        item.style.transition = 'box-shadow 0.2s, border-color 0.2s';
                        item.style.padding = '1.25rem';
                        item.style.color = 'var(--bs-body-color, #212529)';
                        // Icon style
                        const icon = item.querySelector('i');
                        if (icon) {
                            icon.style.fontSize = '2em';
                            icon.style.color = 'var(--bs-primary, #4b0082)';
                        }
                        // Title style
                        const title = item.querySelector('.fw-bold');
                        if (title) {
                            title.style.fontSize = '1.1em';
                            title.style.fontWeight = 'bold';
                        }
                        // Description style
                        const desc = item.querySelector('.text-muted');
                        if (desc) {
                            desc.style.fontSize = '0.95em';
                            desc.style.color = 'var(--bs-secondary-color, #6c757d)';
                        }
                        // Hover effect
                        item.onmouseover = () => { item.style.boxShadow = '0 0 0 0.2rem #b197fc33'; item.style.borderColor = '#4b0082'; };
                        item.onmouseout = () => { item.style.boxShadow = '0 1px 4px 0 rgba(75,0,130,0.04)'; item.style.borderColor = 'var(--bs-primary-border-subtle, #b197fc)'; };
                    });
                    toggleBtn.innerHTML = '<i class="fas fa-th"></i> <span id="vlrViewLabel">Grid View</span>';
                }
            });
            localStorage.setItem('vlrViewMode', isGridView ? 'grid' : 'list');
        }
        toggleBtn.addEventListener('click', () => {
            isGridView = !isGridView;
            updateVLRView();
        });
        updateVLRView();
    }

    getVLRTypeDisplayName(type) {
        const typeNames = {
            'scorm': 'SCORM Packages',
            'non_scorm': 'Non-SCORM Packages',
            'assessment': 'Assessments',
            'audio': 'Audio Packages',
            'video': 'Video Packages',
            'image': 'Image Packages',
            'document': 'Documents',
            'external': 'External Content',
            'interactive': 'Interactive Content',
            'assignment': 'Assignments',
            'survey': 'Surveys',
            'feedback': 'Feedback Forms'
        };
        return typeNames[type] || type;
    }

    initializeVLRSearch(modal) {
        const searchInput = modal.querySelector('#vlrSearchInput');
        const clearSearchBtn = modal.querySelector('#clearSearchBtn');
        const contentItems = modal.querySelectorAll('.vlr-content-item');

        const performSearch = () => {
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            contentItems.forEach(item => {
                const title = item.dataset.title;
                const description = item.dataset.description;
                const matches = title.includes(searchTerm) || description.includes(searchTerm);
                
                item.style.display = matches ? 'block' : 'none';
            });

            // Update tab badges with visible count
            const tabs = modal.querySelectorAll('#vlrTypeTabs .nav-link');
            tabs.forEach(tab => {
                const targetId = tab.getAttribute('data-bs-target');
                const targetPane = modal.querySelector(targetId);
                // Only count .vlr-content-item that are visible (not display: none)
                const visibleItems = Array.from(targetPane.querySelectorAll('.vlr-content-item')).filter(item => item.style.display !== 'none');
                const badge = tab.querySelector('.badge');
                if (badge) {
                    badge.textContent = visibleItems.length;
                }
            });
        };

        searchInput.addEventListener('input', performSearch);
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            performSearch();
        });
    }

    initializeVLRCheckboxes(modal, type, moduleId, preselectedIds = []) {
        const checkboxes = modal.querySelectorAll('.vlr-checkbox');
        const selectedCountSpan = modal.querySelector('#selectedCount');
        const selectedCountBtn = modal.querySelector('#selectedCountBtn');
        const addSelectedBtn = modal.querySelector('#addSelectedVLRBtn');

        // Pre-check checkboxes for already selected items
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
            const checkboxId = parseInt(checkbox.value);
            const checkboxType = checkbox.closest('.vlr-content-item').dataset.vlrType;
            if (preselectedIds.some(sel => sel.id === checkboxId && sel.type === checkboxType)) {
                checkbox.checked = true;
            }
        });

        const updateSelectedCount = () => {
            const selectedCheckboxes = modal.querySelectorAll('.vlr-checkbox:checked');
            const count = selectedCheckboxes.length;
            
            selectedCountSpan.textContent = `${count} item${count !== 1 ? 's' : ''} selected`;
            selectedCountBtn.textContent = count;
            addSelectedBtn.disabled = count === 0;
        };

        // Initialize Select All functionality for each tab
        const tabPanes = modal.querySelectorAll('.tab-pane');
        tabPanes.forEach(tabPane => {
            const selectAllCheckbox = tabPane.querySelector('input.form-check-input[id^="selectAllVLR-"]');
            if (!selectAllCheckbox) return;
            
            // When Select All is clicked
            selectAllCheckbox.addEventListener('change', function() {
                // Find all visible VLR content items in this tab
                const visibleItems = tabPane.querySelectorAll('.vlr-content-item:not([style*="display: none"])');
                visibleItems.forEach(item => {
                    const checkbox = item.querySelector('.vlr-checkbox');
                    if (checkbox && !checkbox.disabled) {
                        checkbox.checked = selectAllCheckbox.checked;
                    }
                });
                updateSelectedCount();
            });
        });

        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                updateSelectedCount();
                
                // Update Select All state for the current tab
                const tabPane = checkbox.closest('.tab-pane');
                if (tabPane) {
                    const selectAllCheckbox = tabPane.querySelector('input.form-check-input[id^="selectAllVLR-"]');
                    if (selectAllCheckbox) {
                        const visibleItems = tabPane.querySelectorAll('.vlr-content-item:not([style*="display: none"])');
                        const visibleCheckboxes = Array.from(visibleItems).map(item => item.querySelector('.vlr-checkbox')).filter(cb => cb && !cb.disabled);
                        const checkedCount = visibleCheckboxes.filter(cb => cb.checked).length;
                        
                        selectAllCheckbox.checked = checkedCount === visibleCheckboxes.length && checkedCount > 0;
                        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < visibleCheckboxes.length;
                    }
                }
            });
        });

        addSelectedBtn.addEventListener('click', () => {
            const selectedCheckboxes = modal.querySelectorAll('.vlr-checkbox:checked');
            const selectedItems = [];

            selectedCheckboxes.forEach(checkbox => {
                const itemId = parseInt(checkbox.value);
                const itemCard = checkbox.closest('.vlr-content-item');
                const itemTitle = itemCard.querySelector('.fw-bold').textContent.trim();
                const itemType = itemCard.dataset.vlrType;
                
                selectedItems.push({
                    id: itemId,
                    type: itemType,
                    title: itemTitle
                });
            });

            if (selectedItems.length > 0) {
                this.addSelectedVLR(type, moduleId, selectedItems);
                
                // Close modal
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                }
            }
        });

        // Initialize count
        updateSelectedCount();
    }

    addSelectedVLR(type, moduleId, selectedItems) {
        switch (type) {
            case 'prerequisites':
                this.prerequisites = [...selectedItems];
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
                        module.content = [...selectedItems];
                        this.renderModules();
                    }
                }
                break;
        }

        this.showToast(`${selectedItems.length} item(s) added successfully`, 'success');
    }

    renderPrerequisites() {
        const container = document.getElementById('prerequisites_container');
        if (!container) return;
        if (!this.prerequisites.length) {
            container.innerHTML = '<span class="text-muted">No prerequisites added yet</span>';
            return;
        }
        container.innerHTML = this.prerequisites.map(item => `
            <div class="prerequisite-chip card px-2 py-1 d-flex flex-row align-items-center mb-2" style="border-radius:1.2em; background:#f8f9fa; border:1px solid #e0e0e0;">
                <i class="fas fa-link me-2 text-secondary"></i>
                <span class="prerequisite-title flex-grow-1 text-truncate" title="${item.title}">${item.title}</span>
                <span class="badge bg-light text-dark ms-2 text-truncate" style="max-width: 80px;" title="${item.prereqType || item.type}">${item.prereqType || item.type}</span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2 flex-shrink-0" title="Remove" onclick="courseManager.removePrerequisite(${item.id})">
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
        if (typeof window.showSimpleToast === 'function') {
            window.showSimpleToast(message, type);
            return;
        }
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
        toast.addEventListener('click', (e) => {
            if (!e.target.classList.contains('btn-close')) {
                toast.remove();
            }
        });
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

    moveModuleUp(index) {
        if (index > 0) {
            [this.modules[index - 1], this.modules[index]] = [this.modules[index], this.modules[index - 1]];
            this.renderModules();
        }
    }

    moveModuleDown(index) {
        if (index < this.modules.length - 1) {
            [this.modules[index], this.modules[index + 1]] = [this.modules[index + 1], this.modules[index]];
            this.renderModules();
        }
    }

    // Move VLR content up in the module
    moveVLRUp(moduleId, idx) {
        const module = this.modules.find(m => m.id === moduleId);
        if (!module || idx === 0) return;
        [module.content[idx - 1], module.content[idx]] = [module.content[idx], module.content[idx - 1]];
        this.renderModules();
    }

    // Move VLR content down in the module
    moveVLRDown(moduleId, idx) {
        const module = this.modules.find(m => m.id === moduleId);
        if (!module || idx === module.content.length - 1) return;
        [module.content[idx + 1], module.content[idx]] = [module.content[idx], module.content[idx + 1]];
        this.renderModules();
    }
}

// Export for global access
window.CourseCreationManager = CourseCreationManager; 