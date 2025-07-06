/**
 * Course Creation JavaScript
 * Enhanced with drag-and-drop, advanced validation, and custom modals
 */

// Instantiate CourseCreationManager only after modal content is loaded.
class CourseCreationManager {
    constructor() {
        console.log('[DEBUG] CourseCreationManager instantiated');
        this.currentTab = 0;
        this.modules = [];
        this.prerequisites = [];
        this.post_requisites = [];
        this.learningObjectives = [];
        this.tags = [];
        this.draggedElement = null;
        this.dragOverElement = null;
        this.isSubmitting = false;
        
        this.logToFile('post_requisites initialized as:', this.post_requisites);
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeDragAndDrop();
        this.setupValidation();
        this.initializeTabs();
        this.loadInitialData();
        this.initializeTagSystems();
        this.setupImagePreviewHandlers();
    }

    bindEvents() {
        // Tab navigation
        const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const target = e.target.getAttribute('data-bs-target');
                if (target) {
                    this.switchTab(target.replace('#', ''));
                }
            });
        });

        // Form submission
        const form = document.getElementById('courseCreationForm');
        console.log('[DEBUG] bindEvents: courseCreationForm:', form);
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(e);
            });
            console.log('[DEBUG] bindEvents: submit event bound to courseCreationForm');
        } else {
            console.warn('[DEBUG] bindEvents: courseCreationForm not found');
        }

        // Create course button (for confirmation modal)
        const createCourseBtn = document.getElementById('create_course');
        if (createCourseBtn) {
            console.log('[DEBUG] Create course button found, binding click event');
            createCourseBtn.addEventListener('click', (e) => {
                console.log('[DEBUG] Create course button clicked');
                e.preventDefault();
                this.showConfirmationModal(
                    'Create Course',
                    'Are you sure you want to create this course?',
                    () => {
                        console.log('[DEBUG] Confirmation callback executed');
                        this.handleFormSubmit(e);
                    },
                    () => {
                        console.log('[DEBUG] Confirmation cancelled');
                    }
                );
            });
        } else {
            console.warn('[DEBUG] Create course button NOT found');
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

        // Add post-requisite buttons (unified)
        const addAssessmentBtn = document.getElementById('selectPostAssessmentBtn');
        if (addAssessmentBtn) {
            this.logToFile('Found selectPostAssessmentBtn, binding click event');
            addAssessmentBtn.addEventListener('click', () => this.showPostRequisiteVLRModal('assessment'));
        } else {
            this.logToFile('ERROR: selectPostAssessmentBtn not found');
        }

        const addFeedbackBtn = document.getElementById('selectPostFeedbackBtn');
        if (addFeedbackBtn) {
            this.logToFile('Found selectPostFeedbackBtn, binding click event');
            addFeedbackBtn.addEventListener('click', () => this.showPostRequisiteVLRModal('feedback'));
        } else {
            this.logToFile('ERROR: selectPostFeedbackBtn not found');
        }

        const addSurveyBtn = document.getElementById('selectPostSurveyBtn');
        if (addSurveyBtn) {
            this.logToFile('Found selectPostSurveyBtn, binding click event');
            addSurveyBtn.addEventListener('click', () => this.showPostRequisiteVLRModal('survey'));
        } else {
            this.logToFile('ERROR: selectPostSurveyBtn not found');
        }

        const addAssignmentBtn = document.getElementById('selectPostAssignmentBtn');
        if (addAssignmentBtn) {
            this.logToFile('Found selectPostAssignmentBtn, binding click event');
            addAssignmentBtn.addEventListener('click', () => this.showPostRequisiteVLRModal('assignment'));
        } else {
            this.logToFile('ERROR: selectPostAssignmentBtn not found');
        }

        // Category change handlers
        const categorySelect = document.getElementById('category_id');
        console.log('[DEBUG] categorySelect element:', categorySelect);
        if (categorySelect) {
            console.log('[DEBUG] Adding change event listener to category select');
            categorySelect.addEventListener('change', () => {
                console.log('[DEBUG] Category changed, calling loadSubcategories');
                this.loadSubcategories();
            });
        } else {
            console.error('[DEBUG] category_id select element not found');
        }

        // Reassign course radio button handlers
        const reassignNo = document.getElementById('reassign_no');
        const reassignYes = document.getElementById('reassign_yes');
        const reassignDaysContainer = document.getElementById('reassign_days_container');
        
        if (reassignNo && reassignYes && reassignDaysContainer) {
            reassignNo.addEventListener('change', () => {
                reassignDaysContainer.style.display = 'none';
            });
            reassignYes.addEventListener('change', () => {
                reassignDaysContainer.style.display = 'block';
            });
        }

        // Real-time validation
        this.setupRealTimeValidation();

        // Keyboard shortcuts
        this.setupKeyboardShortcuts();

        // Post Requisite VLR selection buttons (these are already correctly bound above)
        // The buttons are now handled by the unified post-requisite system above

        // Initialize tag systems
        this.initializeTagSystems();

        // Remove tab error highlight on focus out if tab is valid
        if (form) {
            form.addEventListener('blur', (e) => {
                setTimeout(() => { // Wait for value to update
                    this.checkAndClearTabErrorForField(e.target);
                }, 0);
            }, true);
        }
    }

    initializeTagSystems() {
        console.log('[DEBUG] initializeTagSystems called');
        console.log('[DEBUG] this.learningObjectives before init:', this.learningObjectives);
        console.log('[DEBUG] this.tags before init:', this.tags);
        
        // Learning Objectives Tag System
        this.initializeLearningObjectivesTags();
        
        // Tags Tag System
        this.initializeTagsSystem();
        
        console.log('[DEBUG] this.learningObjectives after init:', this.learningObjectives);
        console.log('[DEBUG] this.tags after init:', this.tags);
    }

    initializeLearningObjectivesTags() {
        console.log('[DEBUG] initializeLearningObjectivesTags called');
        const container = document.getElementById('learning_objectives_container');
        if (!container) {
            console.log('[DEBUG] learning_objectives_container not found');
            return;
        }
        console.log('[DEBUG] learning_objectives_container found');

        // Clear existing content and create proper structure (no plus button)
        container.innerHTML = `
            <div class="input-group mb-2">
                <input type="text" class="form-control" id="learning_objective_input" 
                       placeholder="Enter learning objective">
            </div>
            <div id="learning_objectives_display" class="mb-2"></div>
            <input type="hidden" id="learning_objectives_list" name="learning_objectives_list" value="">
        `;

        const input = document.getElementById('learning_objective_input');
        const display = document.getElementById('learning_objectives_display');
        const hiddenInput = document.getElementById('learning_objectives_list');

        const addLearningObjective = (text) => {
            console.log('[DEBUG] addLearningObjective called with:', text);
            if (text.trim() === "" || this.learningObjectives.includes(text.trim())) {
                console.log('[DEBUG] addLearningObjective: skipping empty or duplicate');
                return;
            }
            
            this.learningObjectives.push(text.trim());
            console.log('[DEBUG] addLearningObjective: added, current array:', this.learningObjectives);
            updateDisplay();
            input.value = '';
        };

        const removeLearningObjective = (text) => {
            this.learningObjectives = this.learningObjectives.filter(obj => obj !== text);
            updateDisplay();
        };

        const updateDisplay = () => {
            display.innerHTML = this.learningObjectives.map(obj => 
                `<span class="tag">${obj} <button type="button" class="remove-tag" onclick="courseManager.removeLearningObjective('${obj}')">&times;</button></span>`
            ).join('');
            hiddenInput.value = this.learningObjectives.join(',');
        };

        // Event listeners
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addLearningObjective(input.value);
                }
            });
        }

        // Make remove function globally accessible
        this.removeLearningObjective = removeLearningObjective;
    }

    initializeTagsSystem() {
        console.log('[DEBUG] initializeTagsSystem called');
        const container = document.getElementById('tags_container');
        if (!container) {
            console.log('[DEBUG] tags_container not found');
            return;
        }
        console.log('[DEBUG] tags_container found');

        // Clear existing content and create proper structure (no plus button)
        container.innerHTML = `
            <div class="input-group mb-2">
                <input type="text" class="form-control" id="tag_input" 
                       placeholder="Enter tag">
            </div>
            <div id="tags_display" class="mb-2"></div>
            <input type="hidden" id="tags_list" name="tags_list" value="">
        `;

        const input = document.getElementById('tag_input');
        const display = document.getElementById('tags_display');
        const hiddenInput = document.getElementById('tags_list');

        const addTag = (text) => {
            console.log('[DEBUG] addTag called with:', text);
            if (text.trim() === "" || this.tags.includes(text.trim())) {
                console.log('[DEBUG] addTag: skipping empty or duplicate');
                return;
            }
            
            this.tags.push(text.trim());
            console.log('[DEBUG] addTag: added, current array:', this.tags);
            updateDisplay();
            input.value = '';
        };

        const removeTag = (text) => {
            this.tags = this.tags.filter(tag => tag !== text);
            updateDisplay();
        };

        const updateDisplay = () => {
            display.innerHTML = this.tags.map(tag => 
                `<span class="tag">${tag} <button type="button" class="remove-tag" onclick="courseManager.removeTag('${tag}')">&times;</button></span>`
            ).join('');
            hiddenInput.value = this.tags.join(',');
        };

        // Event listeners
        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addTag(input.value);
                }
            });
        }

        // Make remove function globally accessible
        this.removeTag = removeTag;
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
            title: {
                required: true,
                minLength: 3,
                maxLength: 100,
                pattern: /^[a-zA-Z0-9\s\-_()]+$/
            },
            category_id: {
                required: true
            },
            subcategory_id: {
                required: true
            },
            course_type: {
                required: true
            },
            difficulty_level: {
                required: true
            },
            modules: {
                required: true,
                minCount: 1
            }
        };

        this.errorMessages = {
            title: {
                required: 'Course title is required',
                minLength: 'Course title must be at least 3 characters',
                maxLength: 'Course title cannot exceed 100 characters',
                pattern: 'Course title contains invalid characters'
            },
            category_id: {
                required: 'Please select a course category'
            },
            subcategory_id: {
                required: 'Please select a course subcategory'
            },
            course_type: {
                required: 'Please select a course type'
            },
            difficulty_level: {
                required: 'Please select a difficulty level'
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
        // Track which tabs have errors
        const tabErrors = new Set();

        // Validate basic fields (tab 0)
        const fields = ['title', 'category_id', 'subcategory_id', 'course_type', 'difficulty_level'];
        fields.forEach(fieldName => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field && !this.validateField(field)) {
                isValid = false;
                errors.push(this.errorMessages[fieldName].required || 'Invalid field');
                tabErrors.add(0); // Basic Info tab
            }
        });

        // Validate duration fields
        const durationHours = document.querySelector('[name="duration_hours"]');
        const durationMinutes = document.querySelector('[name="duration_minutes"]');
        
        if (durationHours && durationMinutes) {
            const hours = parseInt(durationHours.value) || 0;
            const minutes = parseInt(durationMinutes.value) || 0;
            
            if (hours < 0) {
                isValid = false;
                errors.push('Duration hours cannot be negative');
                tabErrors.add(0);
            }
            
            if (minutes < 0 || minutes > 59) {
                isValid = false;
                errors.push('Duration minutes must be between 0 and 59');
                tabErrors.add(0);
            }
        }

        // Validate modules (tab 1)
        if (this.modules.length === 0) {
            isValid = false;
            errors.push('At least one module is required');
            tabErrors.add(1); // Modules tab
        }

        // Validate prerequisites (tab 2)
        if (this.prerequisites.length > 0) {
            const hasValidPrerequisites = this.prerequisites.every(pre => pre.id && pre.title);
            if (!hasValidPrerequisites) {
                isValid = false;
                errors.push('Some prerequisites are invalid');
                tabErrors.add(2); // Prerequisites tab
            }
        }

        // Highlight tabs with errors
        this.clearAllTabErrors();
        tabErrors.forEach(idx => this.markTabAsError(idx));

        // Show validation errors
        if (!isValid) {
            this.showValidationErrors(errors);
        } else {
            // If valid, ensure all tab highlights are cleared
            this.clearAllTabErrors();
        }

        return isValid;
    }

    clearAllTabErrors() {
        const tabs = document.querySelectorAll('#courseCreationTabs .nav-link');
        tabs.forEach(tab => tab.classList.remove('tab-error'));
    }

    markTabAsError(tabIndex) {
        const tabs = document.querySelectorAll('#courseCreationTabs .nav-link');
        if (tabs[tabIndex]) {
            tabs[tabIndex].classList.add('tab-error');
        }
    }

    clearTabError(tabIndex) {
        const tabs = document.querySelectorAll('#courseCreationTabs .nav-link');
        if (tabs[tabIndex]) {
            tabs[tabIndex].classList.remove('tab-error');
        }
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

    initializeTabs() {
        this.showTab(0);
    }

    switchTab(tabIndex) {
        // On tab change, re-validate the previous tab and clear highlight if valid
        if (this.validateCurrentTab()) {
            this.clearTabError(this.currentTab);
            this.showTab(tabIndex);
        } else {
            this.markTabAsError(this.currentTab);
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
        const requiredFields = ['title', 'category_id', 'subcategory_id', 'course_type', 'difficulty_level'];
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
        console.log('[DEBUG] loadSubcategories() called');
        const categoryId = document.getElementById('category_id')?.value;
        console.log('[DEBUG] categoryId:', categoryId);
        
        if (!categoryId) {
            console.log('[DEBUG] No category selected, clearing subcategory dropdown');
            const select = document.getElementById('subcategory_id');
            if (select) {
                select.innerHTML = '<option value="">Select Subcategory</option>';
            }
            return;
        }

        try {
            console.log('[DEBUG] Fetching subcategories for category_id:', categoryId);
            // Use the correct endpoint for subcategory dropdown
            const response = await fetch(`/Unlockyourskills/api/course-subcategories/dropdown?category_id=${categoryId}`, {
                credentials: 'include'
            });
            console.log('[DEBUG] Response status:', response.status);
            const data = await response.json();
            console.log('[DEBUG] Response data:', data);
            
            if (data.success) {
                const select = document.getElementById('subcategory_id');
                if (select) {
                    select.innerHTML = '<option value="">Select Subcategory</option>';
                    data.subcategories.forEach(subcategory => {
                        select.innerHTML += `<option value="${subcategory.id}">${subcategory.name}</option>`;
                    });
                    console.log('[DEBUG] Loaded', data.subcategories.length, 'subcategories');
                } else {
                    console.error('[DEBUG] subcategory_id select element not found');
                }
            } else {
                console.error('[DEBUG] API returned error:', data.message);
                this.showToast('Error loading subcategories: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('[DEBUG] Error loading subcategories:', error);
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
            content: [],
            sort_order: this.modules.length // Set proper order
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
                                <input type="text" class="form-control module-title-input" id="moduleTitle_${module.id}" 
                                       value="${module.title}" data-module-id="${module.id}" autocomplete="off">
                                <div class="invalid-feedback d-block module-title-error" style="display:none;"></div>
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

        // Bind validation for module title fields
        this.bindModuleTitleValidation();

        // Reinitialize drag and drop for new modules
        this.initializeModuleDragAndDrop();
    }

    bindModuleTitleValidation() {
        // Add blur and input event listeners to all module title fields
        const inputs = document.querySelectorAll('.module-title-input');
        inputs.forEach(input => {
            input.addEventListener('blur', (e) => {
                this.validateModuleTitleField(e.target);
                this.updateModulesTabErrorHighlight();
            });
            input.addEventListener('input', (e) => {
                this.clearModuleTitleError(e.target);
                this.updateModulesTabErrorHighlight();
            });
            // Also update module title in data on change
            input.addEventListener('change', (e) => {
                const moduleId = parseInt(e.target.getAttribute('data-module-id'));
                this.updateModule(moduleId, 'title', e.target.value);
            });
        });
    }

    validateModuleTitleField(input) {
        const value = input.value.trim();
        const errorDiv = input.parentElement.querySelector('.module-title-error');
        if (!value) {
            errorDiv.textContent = 'Module title is required';
            errorDiv.style.display = 'block';
            input.classList.add('is-invalid');
            return false;
        } else {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
            input.classList.remove('is-invalid');
            return true;
        }
    }

    clearModuleTitleError(input) {
        const errorDiv = input.parentElement.querySelector('.module-title-error');
        errorDiv.textContent = '';
        errorDiv.style.display = 'none';
        input.classList.remove('is-invalid');
    }

    updateModulesTabErrorHighlight() {
        // Highlight the Modules tab if any module title is invalid
        const inputs = document.querySelectorAll('.module-title-input');
        let hasError = false;
        inputs.forEach(input => {
            if (!input.value.trim()) {
                hasError = true;
            }
        });
        if (hasError) {
            this.markTabAsError(1);
        } else {
            this.clearTabError(1);
        }
    }

    // On form submit, validate all module titles
    validateAllModuleTitles() {
        const inputs = document.querySelectorAll('.module-title-input');
        let allValid = true;
        inputs.forEach(input => {
            if (!this.validateModuleTitleField(input)) {
                allValid = false;
            }
        });
        this.updateModulesTabErrorHighlight();
        return allValid;
    }

    // In handleFormSubmit, call validateAllModuleTitles and prevent submit if invalid
    async handleFormSubmit(e) {
        console.log('[DEBUG] handleFormSubmit called');
        // No need to prevent default here since we're handling it in the click event

        if (this.isSubmitting) {
            console.log('[DEBUG] Form is already being submitted');
            this.showToast('Form is already being submitted', 'warning');
            return;
        }

        // Validate all module titles
        console.log('[DEBUG] Validating module titles');
        const modulesValid = this.validateAllModuleTitles();
        if (!modulesValid) {
            console.log('[DEBUG] Module titles validation failed');
            this.showToast('Please fix module title errors before submitting.', 'error');
            return;
        }

        // Validate the rest of the form
        console.log('[DEBUG] Validating form');
        if (!this.validateForm()) {
            console.log('[DEBUG] Form validation failed');
            this.showToast('Please fix the validation errors', 'error');
            return;
        }
        
        console.log('[DEBUG] All validations passed, proceeding with submission');

        this.isSubmitting = true;
        this.showLoadingOverlay('Creating course...');

        try {
            const formData = this.collectFormData();
            console.log('[DEBUG] Form data being sent:', formData);
            console.log('[DEBUG] Modules data:', formData.modules);
            const response = await fetch('/Unlockyourskills/course-creation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include',
                body: JSON.stringify(formData)
            });

            const result = await response.json();

            if (result.success) {
                this.showToast('Course created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '/Unlockyourskills/course-management';
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

    showPostRequisiteModal(contentType) {
        const typeMap = {
            'assessment': 'Assessment',
            'feedback': 'Feedback',
            'survey': 'Survey',
            'assignment': 'Assignment'
        };
        const title = `Select ${typeMap[contentType]}`;
        const preselectedIds = this.post_requisites
            .filter(item => item.content_type === contentType)
            .map(item => item.content_id);
        this.showVLRSelectionModal(contentType, title, null, preselectedIds);
    }

    showVLRModal(moduleId) {
        // Find the module and pass its current content IDs and types
        const module = this.modules.find(m => m.id === moduleId);
        const preselectedIds = module ? module.content.map(item => ({ id: item.id, type: item.type })) : [];
        this.showVLRSelectionModal('moduleContent', 'Select VLR Content', moduleId, preselectedIds);
    }

    showVLRSelectionModal(type, title, moduleId = null, preselectedIds = []) {
        if (window.vlrContent && Object.keys(window.vlrContent).length > 0) {
            this.showVLRContentModal(title, window.vlrContent, type, moduleId, preselectedIds);
        } else {
            this.showToast('VLR content not available', 'error');
        }
    }

    showVLRContentModal(title, content, type, moduleId = null, preselectedIds = []) {
        // Filter VLR types based on course type if adding to module
        let allowedTypes = null;
        if (type === 'moduleContent') {
            const courseTypeSelect = document.getElementById('course_type');
            const courseType = courseTypeSelect ? courseTypeSelect.value : '';
            if (courseType === 'e-learning') {
                allowedTypes = [
                    'scorm', 'nonscorm', 'external', 'interactive', 'audio', 'video', 'image'
                ];
            } else if (courseType === 'blended') {
                allowedTypes = [
                    'assignment', 'document', 'video', 'audio', 'interactive'
                ];
            } else if (courseType === 'classroom') {
                allowedTypes = [
                    'document', 'assignment', 'image', 'external'
                ];
            } else if (courseType === 'assessment') {
                allowedTypes = ['assessment'];
            }
        }

        // Group content by type if it's an array (for legacy support)
        let groupedContent = {};
        if (Array.isArray(content)) {
            content.forEach(item => {
                if (!groupedContent[item.type]) groupedContent[item.type] = [];
                groupedContent[item.type].push(item);
            });
        } else if (typeof content === 'object' && content !== null) {
            groupedContent = content;
        }

        // If filtering, remove disallowed types
        if (allowedTypes) {
            Object.keys(groupedContent).forEach(typeKey => {
                if (!allowedTypes.includes(typeKey)) {
                    delete groupedContent[typeKey];
                }
            });
        }

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

        const isPostRequisite = type.startsWith('postRequisite_');

        const updateSelectedCount = () => {
            let count = 0;
            if (isPostRequisite) {
                count = modal.querySelectorAll('.vlr-checkbox:checked').length;
                // Only enable Add button if exactly one is checked
                addSelectedBtn.disabled = count !== 1;
                selectedCountSpan.textContent = `${count} item${count !== 1 ? 's' : ''} selected`;
                selectedCountBtn.textContent = count;
            } else {
                const selectedCheckboxes = modal.querySelectorAll('.vlr-checkbox:checked');
                count = selectedCheckboxes.length;
                selectedCountSpan.textContent = `${count} item${count !== 1 ? 's' : ''} selected`;
                selectedCountBtn.textContent = count;
                addSelectedBtn.disabled = count === 0;
            }
        };

        if (isPostRequisite) {
            // Only allow one selection at a time (radio button behavior)
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        checkboxes.forEach(cb => { if (cb !== checkbox) cb.checked = false; });
                    }
                    updateSelectedCount();
                });
            });
        } else {
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });
        }

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
        console.log('[DEBUG] addSelectedVLR', type, selectedItems);
        if (type.startsWith('postRequisite_')) {
            // Only one item allowed
            const realType = type.replace('postRequisite_', '');
            this.logToFile('Processing postRequisite type:', realType);
            this.logToFile('Selected items:', selectedItems);
            
            // Add to unified post-requisites array instead of separate object
            if (selectedItems[0]) {
                const requisite = {
                    id: selectedItems[0].id,
                    title: selectedItems[0].title,
                    description: selectedItems[0].description || '',
                    content_type: realType,
                    content_id: selectedItems[0].id,
                    sort_order: this.post_requisites.length,
                    is_required: true
                };
                
                this.logToFile('Adding requisite to post_requisites array:', requisite);
                this.post_requisites.push(requisite);
                this.logToFile('post_requisites after adding:', this.post_requisites);
                
                // Update UI
                this.renderPostRequisites();
            }
            
            // Update display div
            const displayDiv = document.getElementById('selectedPost' + this.capitalizeFirstLetter(realType));
            if (displayDiv) {
                if (selectedItems[0]) {
                    displayDiv.innerHTML = `<div class='alert alert-success py-1 px-2 mb-0 d-flex align-items-center justify-content-between'><span><i class='mdi mdi-check-circle me-1'></i> ${selectedItems[0].title}</span> <button type='button' class='btn btn-sm btn-link text-danger p-0 ms-2' onclick='courseManager.clearPostRequisiteSelection("${realType}")'><i class='mdi mdi-close'></i></button></div>`;
                } else {
                    displayDiv.innerHTML = '';
                }
            }
            
            this.renderPostRequisitesSummary();
            return;
        }
        switch (type) {
            case 'prerequisites':
                // Add sort_order to each prerequisite item
                const prerequisitesWithOrder = selectedItems.map((item, index) => ({
                    ...item,
                    sort_order: index
                }));
                this.prerequisites = prerequisitesWithOrder;
                console.log('[DEBUG] Prerequisites added:', prerequisitesWithOrder);
                this.renderPrerequisites();
                break;
            case 'assessment':
            case 'feedback':
            case 'survey':
            case 'assignment':
                this.logToFile('Adding post-requisites for type: ' + type);
                this.logToFile('Selected items:', selectedItems);
                this.logToFile('Current post_requisites before:', this.post_requisites);
                
                // Add to unified post-requisites array
                const postRequisitesWithOrder = selectedItems.map((item, index) => {
                    const requisite = {
                        id: item.id, // Keep original id for reference
                        title: item.title,
                        description: item.description || '',
                        content_type: type,
                        content_id: item.id, // This is what the backend expects
                        sort_order: this.post_requisites.length + index,
                        is_required: true
                    };
                    this.logToFile('Created requisite object:', requisite);
                    return requisite;
                });
                
                this.post_requisites.push(...postRequisitesWithOrder);
                this.logToFile('Post-requisites after adding:', this.post_requisites);
                this.renderPostRequisites();
                break;
            case 'moduleContent':
                if (moduleId) {
                    const module = this.modules.find(m => m.id === moduleId);
                    if (module) {
                        // Add sort_order to each content item
                        const contentWithOrder = selectedItems.map((item, index) => ({
                            ...item,
                            sort_order: index
                        }));
                        module.content = contentWithOrder;
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

    renderPostRequisites() {
        this.logToFile('renderPostRequisites called');
        this.logToFile('Current post_requisites:', this.post_requisites);
        this.logToFile('post_requisites length:', this.post_requisites.length);
        
        const container = document.getElementById('postRequisitesContainer');
        if (!container) {
            this.logToFile('ERROR: Post-requisites container not found');
            return;
        }

        if (!this.post_requisites.length) {
            this.logToFile('No post-requisites to render');
            container.innerHTML = '<span class="text-muted">No post-requisites added yet</span>';
            return;
        }

        container.innerHTML = this.post_requisites.map(requisite => `
            <div class="vlr-content-item d-flex align-items-center justify-content-between p-2 mb-2 border rounded bg-white">
                <div class="vlr-content-info d-flex align-items-center">
                    <i class="vlr-content-icon fas ${this.getVLRIcon(requisite.content_type)} me-2 text-primary"></i>
                    <div>
                        <div class="vlr-content-title fw-bold">${requisite.title}</div>
                        <small class="text-muted">${this.capitalizeFirstLetter(requisite.content_type)}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="courseManager.removePostRequisite(${requisite.id})">
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

    removePostRequisite(id) {
        this.logToFile('removePostRequisite called with id:', id);
        this.logToFile('post_requisites before removal:', this.post_requisites);
        
        // Convert id to number for comparison since it might be passed as string
        const numericId = parseInt(id);
        this.logToFile('numericId:', numericId);
        
        this.post_requisites = this.post_requisites.filter(item => item.id !== numericId);
        this.logToFile('post_requisites after removal:', this.post_requisites);
        
        this.renderPostRequisites();
        this.showToast('Post-requisite removed', 'success');
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

    collectFormData() {
        const form = document.getElementById('courseCreationForm');
        const formData = new FormData(form);
        
        const data = {
            title: formData.get('title'),
            short_description: formData.get('short_description'),
            description: formData.get('description'),
            category_id: formData.get('category_id'),
            subcategory_id: formData.get('subcategory_id'),
            course_type: formData.get('course_type'),
            difficulty_level: formData.get('difficulty_level'),
            course_status: formData.get('course_status'),
            module_structure: formData.get('module_structure'),
            course_points: formData.get('course_points'),
            course_cost: formData.get('course_cost'),
            currency: formData.get('currency'),
            reassign_course: formData.get('reassign_course'),
            reassign_days: formData.get('reassign_days'),
            show_in_search: formData.get('show_in_search'),
            certificate_option: formData.get('certificate_option'),
            duration_hours: formData.get('duration_hours'),
            duration_minutes: formData.get('duration_minutes'),
            is_self_paced: formData.get('is_self_paced'),
            is_featured: formData.get('is_featured'),
            is_published: formData.get('is_published'),
            target_audience: formData.get('target_audience'),
            learning_objectives: this.learningObjectives,
            tags: this.tags,
            modules: this.modules,
            prerequisites: this.prerequisites,
            post_requisites: this.post_requisites
        };
        
        this.logToFile('Form data collected:', data);
        console.log('[DEBUG] collectFormData - Learning Objectives:', this.learningObjectives);
        console.log('[DEBUG] collectFormData - Learning Objectives length:', this.learningObjectives.length);
        console.log('[DEBUG] collectFormData - Tags:', this.tags);
        console.log('[DEBUG] collectFormData - Tags length:', this.tags.length);
        this.logToFile('Learning Objectives:', this.learningObjectives);
        this.logToFile('Learning Objectives length:', this.learningObjectives.length);
        this.logToFile('Tags:', this.tags);
        this.logToFile('Tags length:', this.tags.length);
        this.logToFile('Post-requisites:', this.post_requisites);
        this.logToFile('Post-requisites length:', this.post_requisites.length);
        this.logToFile('Post-requisites type:', typeof this.post_requisites);
        this.logToFile('Post-requisites in data:', data.post_requisites);
        
        return data;
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
        console.log('[DEBUG] showConfirmationModal called');
        console.log('[DEBUG] window.confirmationModalInstance:', window.confirmationModalInstance);
        
        // Use the global confirmation modal if available
        if (window.confirmationModalInstance && typeof window.confirmationModalInstance.show === 'function') {
            console.log('[DEBUG] Using global confirmation modal');
            window.confirmationModalInstance.show({
                title: title,
                message: message,
                onConfirm: onConfirm,
                onCancel: onCancel
            });
        } else {
            console.log('[DEBUG] Using fallback confirmation');
            // Fallback to simple confirmation
            if (confirm(message)) {
                if (onConfirm) onConfirm();
            } else {
                if (onCancel) onCancel();
            }
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
            // Update sort_order for all modules
            this.modules.forEach((module, idx) => {
                module.sort_order = idx;
            });
            this.renderModules();
        }
    }

    moveModuleDown(index) {
        if (index < this.modules.length - 1) {
            [this.modules[index], this.modules[index + 1]] = [this.modules[index + 1], this.modules[index]];
            // Update sort_order for all modules
            this.modules.forEach((module, idx) => {
                module.sort_order = idx;
            });
            this.renderModules();
        }
    }

    // Move VLR content up in the module
    moveVLRUp(moduleId, idx) {
        const module = this.modules.find(m => m.id === moduleId);
        if (!module || idx === 0) return;
        [module.content[idx - 1], module.content[idx]] = [module.content[idx], module.content[idx - 1]];
        // Update sort_order for all content items
        module.content.forEach((content, contentIdx) => {
            content.sort_order = contentIdx;
        });
        this.renderModules();
    }

    // Move VLR content down in the module
    moveVLRDown(moduleId, idx) {
        const module = this.modules.find(m => m.id === moduleId);
        if (!module || idx === module.content.length - 1) return;
        [module.content[idx + 1], module.content[idx]] = [module.content[idx], module.content[idx + 1]];
        // Update sort_order for all content items
        module.content.forEach((content, contentIdx) => {
            content.sort_order = contentIdx;
        });
        this.renderModules();
    }

    showPostRequisiteVLRModal(type) {
        let preselectedId = null;
        if (this.postRequisites && this.postRequisites[type]) {
            preselectedId = this.postRequisites[type].id;
        }
        let vlrContentForType = [];
        if (Array.isArray(window.vlrContent)) {
            // Flat array: filter by type
            let filterType = type;
            if (type === 'assignment') filterType = 'assignment';
            if (type === 'survey') filterType = 'survey';
            vlrContentForType = window.vlrContent.filter(item => item.type === filterType);
        } else {
            // Object: use correct key
            let vlrKey = type;
            if (type === 'assignment') vlrKey = 'assignments';
            if (type === 'survey') vlrKey = 'surveys';
            vlrContentForType = (window.vlrContent && window.vlrContent[vlrKey]) ? window.vlrContent[vlrKey] : [];
        }
        console.log('Post requisite VLR content for', type, vlrContentForType);
        this.showSingleTypeVLRModal('postRequisite_' + type, 'Select ' + this.getVLRTypeDisplayName(type), vlrContentForType, preselectedId ? [{id: preselectedId, type}] : [], type);
    }

    // Dedicated modal for single-type VLR content (post requisite)
    showSingleTypeVLRModal(type, title, contentArray, preselectedIds = [], filterType = null) {
        // Group as an object for modal rendering
        const groupedContent = {};
        groupedContent[filterType] = Array.isArray(contentArray) ? contentArray : [];
        // Pass 'postRequisite_' + filterType as the type to ensure correct handling in addSelectedVLR
        this.showVLRContentModal(title, groupedContent, 'postRequisite_' + filterType, null, preselectedIds);
    }

    clearPostRequisiteSelection(type) {
        this.logToFile('clearPostRequisiteSelection called for type:', type);
        this.logToFile('post_requisites before removal:', this.post_requisites);
        
        // Remove items with this content_type from the array
        this.post_requisites = this.post_requisites.filter(item => item.content_type !== type);
        
        this.logToFile('post_requisites after removal:', this.post_requisites);
        
        // Update display div
        const displayDiv = document.getElementById('selectedPost' + this.capitalizeFirstLetter(type));
        if (displayDiv) displayDiv.innerHTML = '';
        
        // Update UI
        this.renderPostRequisites();
        this.renderPostRequisitesSummary();
    }

    capitalizeFirstLetter(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    renderPostRequisitesSummary() {
        const container = document.getElementById('postRequisitesSummary');
        if (!container) return;
        
        this.logToFile('renderPostRequisitesSummary called');
        this.logToFile('post_requisites array:', this.post_requisites);
        
        if (!this.post_requisites || this.post_requisites.length === 0) {
            container.innerHTML = '<span class="text-muted">No post requisites added yet</span>';
            return;
        }
        
        const types = [
            { key: 'assessment', label: 'Assessment', icon: 'mdi mdi-clipboard-check' },
            { key: 'feedback', label: 'Feedback', icon: 'mdi mdi-comment-multiple' },
            { key: 'survey', label: 'Survey', icon: 'mdi mdi-clipboard-text-multiple' },
            { key: 'assignment', label: 'Assignment', icon: 'mdi mdi-file-document-edit' }
        ];
        
        let cards = types.map(t => {
            const items = this.post_requisites.filter(item => item.content_type === t.key);
            if (items.length === 0) return '';
            
            return items.map(item => `
                <div class="prerequisite-chip card px-2 py-1 d-flex flex-row align-items-center mb-2" style="border-radius:1.2em; background:#f8f9fa; border:1px solid #e0e0e0;">
                    <i class="${t.icon} me-2 text-secondary"></i>
                    <span class="prerequisite-title flex-grow-1" title="${item.title}">${item.title}</span>
                    <span class="badge bg-light text-dark ms-2" title="${t.label}">${t.label}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger ms-2 flex-shrink-0" title="Remove" onclick="courseManager.removePostRequisite(${item.id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `).join('');
        }).join('');
        
        container.innerHTML = cards || '<span class="text-muted">No post requisites added yet</span>';
        this.logToFile('renderPostRequisitesSummary completed');
    }

    setupImagePreviewHandlers() {
        // This will work for both main and modal forms since IDs are reused
        const thumbnailInputs = document.querySelectorAll('#thumbnail');
        const thumbnailPreviews = document.querySelectorAll('#thumbnailPreviewContainer');
        thumbnailInputs.forEach((thumbnailInput, idx) => {
            const thumbnailPreview = thumbnailPreviews[idx];
            if (thumbnailInput && thumbnailPreview) {
                thumbnailInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        showImagePreview(this.files[0], thumbnailPreview, () => {
                            thumbnailInput.value = '';
                            thumbnailPreview.innerHTML = '';
                        });
                    } else {
                        thumbnailPreview.innerHTML = '';
                    }
                });
            }
        });
        const bannerInputs = document.querySelectorAll('#banner');
        const bannerPreviews = document.querySelectorAll('#bannerPreviewContainer');
        bannerInputs.forEach((bannerInput, idx) => {
            const bannerPreview = bannerPreviews[idx];
            if (bannerInput && bannerPreview) {
                bannerInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        showImagePreview(this.files[0], bannerPreview, () => {
                            bannerInput.value = '';
                            bannerPreview.innerHTML = '';
                        });
                    } else {
                        bannerPreview.innerHTML = '';
                    }
                });
            }
        });
        // Helper function for preview
        function showImagePreview(file, container, removeCallback) {
            const fileName = file.name;
            const fileExtension = fileName.split('.').pop().toLowerCase();
            let previewHTML = '';
            if (["jpg","jpeg","png","gif","webp","bmp","svg"].includes(fileExtension)) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewHTML = `
                        <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                            <img src="${e.target.result}" alt="Preview" style="max-width: 150px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px;">
                            <button type="button" class="remove-preview" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;" tabindex="0">×</button>
                        </div>
                        <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">New file: ${fileName} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                    `;
                    container.innerHTML = previewHTML;
                    const removeBtn = container.querySelector('.remove-preview');
                    if (removeBtn) {
                        removeBtn.addEventListener('click', removeCallback);
                    }
                };
                reader.readAsDataURL(file);
            } else {
                previewHTML = `
                    <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                        <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #e8f5e8;">
                            <i class="fas fa-file-image" style="font-size: 24px; color: #6a0dad;"></i>
                            <button type="button" class="remove-preview" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;" tabindex="0">×</button>
                        </div>
                        <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">New file: ${fileName} (${(file.size / 1024 / 1024).toFixed(2)} MB)</p>
                    </div>
                `;
                container.innerHTML = previewHTML;
                const removeBtn = container.querySelector('.remove-preview');
                if (removeBtn) {
                    removeBtn.addEventListener('click', removeCallback);
                }
            }
        }
    }

    checkAndClearTabErrorForField(field) {
        if (!field || !field.form || field.form.id !== 'courseCreationForm') return;
        // Determine which tab this field belongs to
        const tabMap = {
            0: ['title', 'category_id', 'subcategory_id', 'course_type', 'difficulty_level'],
            1: [], // Modules handled by module logic
            2: [], // Prerequisites handled by prerequisite logic
        };
        // Check basic info tab
        if (tabMap[0].includes(field.name)) {
            const allValid = tabMap[0].every(name => {
                const f = document.querySelector(`[name="${name}"]`);
                return f && this.validateField(f);
            });
            if (allValid) this.clearTabError(0);
        }
        // You can add similar logic for modules/prerequisites if needed
    }

    // Add debugging function to log to file
    logToFile(message, data = null) {
        const logData = {
            timestamp: new Date().toISOString(),
            message: message,
            data: data
        };
        
        // Send to a simple logging endpoint
        fetch('/Unlockyourskills/debug-log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(logData)
        }).catch(err => console.error('Logging failed:', err));
        
        // Also log to console
        console.log('[DEBUG]', message, data);
    }
}

// Export for global access
window.CourseCreationManager = CourseCreationManager;

document.addEventListener('DOMContentLoaded', function() {
    // Wait for courseManager to be available
    setTimeout(function() {
        if (window.courseManager && typeof window.courseManager.renderPostRequisitesSummary === 'function') {
            window.courseManager.renderPostRequisitesSummary();
        }
    }, 200);

    // Listen for all tab switches
    const tabList = document.querySelectorAll('#courseCreationTabs .nav-link');
    tabList.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            if (window.courseManager && typeof window.courseManager.renderPostRequisitesSummary === 'function') {
                window.courseManager.renderPostRequisitesSummary();
            }
        });
    });

    document.addEventListener('hidden.bs.modal', function (e) {
        if (e.target && e.target.id === 'vlrSelectionModal') {
            if (window.courseManager && typeof window.courseManager.renderPostRequisitesSummary === 'function') {
                window.courseManager.renderPostRequisitesSummary();
            }
        }
    });
}); 