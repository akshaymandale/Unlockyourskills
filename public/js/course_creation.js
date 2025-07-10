/**
 * Course Creation JavaScript - Function-based approach (like user management)
 * Enhanced with drag-and-drop, advanced validation, and custom modals
 */

console.log('[DEBUG] course_creation.js file loaded successfully');

// Global variables to track current state
let courseManagerState = {
    modules: [],
    prerequisites: [],
    post_requisites: [],
    learningObjectives: [],
    tags: [],
    currentTab: 0,
    isEditMode: false,
    courseId: null,
    searchTimeout: null,
    validationInterval: null
};
window.courseManagerState = courseManagerState;

// Initialize course creation functionality
function initializeCourseCreation() {
    console.log('[DEBUG] initializeCourseCreation() called');
    
    try {
        // Add custom styles for validation and tab highlighting
        addCustomStyles();
        console.log('[DEBUG] addCustomStyles() completed');
        
        bindCourseEvents();
        console.log('[DEBUG] bindCourseEvents() completed');
        initializeDragAndDrop();
        console.log('[DEBUG] initializeDragAndDrop() completed');
        setupValidation();
        console.log('[DEBUG] setupValidation() completed');
        initializeTabs();
        console.log('[DEBUG] initializeTabs() completed');
        loadInitialData();
        console.log('[DEBUG] loadInitialData() completed');
        loadExistingCourseData(); // Load existing data for edit mode
        console.log('[DEBUG] loadExistingCourseData() completed');
        initializeTagSystems();
        console.log('[DEBUG] initializeTagSystems() completed');
        setupImagePreviewHandlers();
        console.log('[DEBUG] setupImagePreviewHandlers() completed');
        console.log('[DEBUG] initializeCourseCreation() completed');
    } catch (error) {
        console.error('[ERROR] initializeCourseCreation() failed:', error);
        console.error('[ERROR] Error stack:', error.stack);
    }
}

function addCustomStyles() {
    console.log('[DEBUG] addCustomStyles() called');
    
    // Check if styles already exist
    if (document.getElementById('course-creation-styles')) {
        return;
    }
    
    const style = document.createElement('style');
    style.id = 'course-creation-styles';
    style.textContent = `
        /* Validation styles */
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-control.is-valid {
            border-color: #198754;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
        }
        
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        
        /* Tab highlighting styles */
        .nav-link.text-danger {
            color: #dc3545 !important;
            border-color: #dc3545 !important;
        }
        
        .nav-link.border-danger {
            border-color: #dc3545 !important;
        }
        
        /* Tab active state */
        .nav-link.active {
            background-color: #007bff !important;
            color: white !important;
            border-color: #007bff !important;
        }
        
        /* Tab hover effects */
        .nav-link:hover:not(.active) {
            background-color: #f8f9fa;
            border-color: #dee2e6;
        }
    `;
    
    document.head.appendChild(style);
    console.log('[DEBUG] Custom styles added');
}

function bindCourseEvents() {
    console.log('[DEBUG] bindCourseEvents() started');
    
    // Check if buttons exist
    const addPrerequisiteBtn = document.getElementById('add_prerequisite');
    const addAssessmentBtn = document.getElementById('selectPostAssessmentBtn');
    const addFeedbackBtn = document.getElementById('selectPostFeedbackBtn');
    const addSurveyBtn = document.getElementById('selectPostSurveyBtn');
    const addAssignmentBtn = document.getElementById('selectPostAssignmentBtn');
    
    console.log('[DEBUG] Button elements found:');
    console.log('[DEBUG] - add_prerequisite:', addPrerequisiteBtn);
    console.log('[DEBUG] - selectPostAssessmentBtn:', addAssessmentBtn);
    console.log('[DEBUG] - selectPostFeedbackBtn:', addFeedbackBtn);
    console.log('[DEBUG] - selectPostSurveyBtn:', addSurveyBtn);
    console.log('[DEBUG] - selectPostAssignmentBtn:', addAssignmentBtn);
    
    try {
        // Tab navigation
        const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
        console.log('[DEBUG] Found tab buttons:', tabButtons.length);
        
        tabButtons.forEach((button, index) => {
            console.log('[DEBUG] Tab button', index, ':', button.id, 'target:', button.getAttribute('data-bs-target'));
            button.addEventListener('click', handleTabClick);
        });
        console.log('[DEBUG] Tab event listeners bound successfully');
    } catch (error) {
        console.error('[ERROR] bindCourseEvents() failed:', error);
        console.error('[ERROR] Error stack:', error.stack);
    }

    // Form submission
    const form = document.getElementById('courseCreationForm');
    console.log('[DEBUG] bindCourseEvents: courseCreationForm:', form);
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
        console.log('[DEBUG] bindCourseEvents: submit event bound to courseCreationForm');
    } else {
        console.warn('[DEBUG] bindCourseEvents: courseCreationForm not found');
    }

    // Create course button (direct submission)
    const createCourseBtn = document.getElementById('create_course');
    if (createCourseBtn) {
        console.log('[DEBUG] Create course button found, binding click event');
        createCourseBtn.addEventListener('click', handleFormSubmit);
    } else {
        console.warn('[DEBUG] Create course button NOT found');
    }

    // Add module button
    const addModuleBtn = document.getElementById('addModuleBtn');
    if (addModuleBtn) {
        console.log('[DEBUG] Add Module button found, binding click event');
        addModuleBtn.addEventListener('click', handleAddModule);
    } else {
        console.warn('[DEBUG] Add Module button NOT found');
    }

    // Add prerequisite button
    if (addPrerequisiteBtn) {
        console.log('[DEBUG] Add Prerequisite button found, binding click event');
        addPrerequisiteBtn.addEventListener('click', handleAddPrerequisite);
    } else {
        console.warn('[DEBUG] Add Prerequisite button NOT found');
    }

    // Add post-requisite buttons
    if (addAssessmentBtn) {
        console.log('[DEBUG] Found selectPostAssessmentBtn, binding click event');
        addAssessmentBtn.addEventListener('click', (e) => {
            console.log('[DEBUG] selectPostAssessmentBtn clicked');
            try {
                showPostRequisiteVLRModal('assessment');
            } catch (error) {
                console.error('[ERROR] showPostRequisiteVLRModal failed:', error);
            }
        });
    } else {
        console.error('[DEBUG] ERROR: selectPostAssessmentBtn not found');
    }

    if (addFeedbackBtn) {
        console.log('[DEBUG] Found selectPostFeedbackBtn, binding click event');
        addFeedbackBtn.addEventListener('click', (e) => {
            console.log('[DEBUG] selectPostFeedbackBtn clicked');
            try {
                showPostRequisiteVLRModal('feedback');
            } catch (error) {
                console.error('[ERROR] showPostRequisiteVLRModal failed:', error);
            }
        });
    } else {
        console.error('[DEBUG] ERROR: selectPostFeedbackBtn not found');
    }

    if (addSurveyBtn) {
        console.log('[DEBUG] Found selectPostSurveyBtn, binding click event');
        addSurveyBtn.addEventListener('click', (e) => {
            console.log('[DEBUG] selectPostSurveyBtn clicked');
            try {
                showPostRequisiteVLRModal('survey');
            } catch (error) {
                console.error('[ERROR] showPostRequisiteVLRModal failed:', error);
            }
        });
    } else {
        console.error('[DEBUG] ERROR: selectPostSurveyBtn not found');
    }

    if (addAssignmentBtn) {
        console.log('[DEBUG] Found selectPostAssignmentBtn, binding click event');
        addAssignmentBtn.addEventListener('click', (e) => {
            console.log('[DEBUG] selectPostAssignmentBtn clicked');
            try {
                showPostRequisiteVLRModal('assignment');
            } catch (error) {
                console.error('[ERROR] showPostRequisiteVLRModal failed:', error);
            }
        });
    } else {
        console.error('[DEBUG] ERROR: selectPostAssignmentBtn not found');
    }

    // Category change handlers
    const categorySelect = document.getElementById('category_id');
    console.log('[DEBUG] categorySelect element:', categorySelect);
    if (categorySelect) {
        console.log('[DEBUG] Adding change event listener to category select');
        categorySelect.addEventListener('change', handleCategoryChange);
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
}

// Event handler functions
function handleTabClick(e) {
    console.log('[DEBUG] Tab click event triggered');
    console.log('[DEBUG] Clicked element:', e.target);
    console.log('[DEBUG] Clicked element ID:', e.target.id);
    const target = e.target.getAttribute('data-bs-target');
    console.log('[DEBUG] Tab clicked - target:', target);
    if (target) {
        const tabId = target.replace('#', '');
        const tabIndex = getTabIndex(tabId);
        console.log('[DEBUG] Tab ID:', tabId, 'Tab Index:', tabIndex);
        switchTab(tabIndex);
    }
}

function handleAddModule() {
    console.log('[DEBUG] Add Module button clicked');
    addModule();
}

function handleAddPrerequisite(e) {
    console.log('[DEBUG] Add Prerequisite button clicked');
    console.log('[DEBUG] Event target:', e.target);
    console.log('[DEBUG] Event target ID:', e.target.id);
    console.log('[DEBUG] showPrerequisiteModal exists:', typeof showPrerequisiteModal);
    try {
        showPrerequisiteModal();
    } catch (error) {
        console.error('[ERROR] showPrerequisiteModal failed:', error);
        console.error('[ERROR] Error stack:', error.stack);
    }
}

function handleCategoryChange() {
    console.log('[DEBUG] Category changed, calling loadSubcategories');
    loadSubcategories();
}

function handleFormSubmit(e) {
    console.log('[DEBUG] Form submit event triggered');
    // Before submitting, update hidden inputs for non-JS fallback
    if (window.courseManagerState && Array.isArray(window.courseManagerState.modules)) {
        window.courseManagerState.modules.forEach((module, idx) => {
            module.sort_order = idx; // 0-based order for DB
        });
    }
    if (document.getElementById('modulesInput')) {
        document.getElementById('modulesInput').value = JSON.stringify(window.courseManagerState.modules || []);
    }
    if (document.getElementById('prerequisitesInput')) {
        document.getElementById('prerequisitesInput').value = JSON.stringify(window.courseManagerState.prerequisites || []);
    }
    if (document.getElementById('postRequisitesInput')) {
        document.getElementById('postRequisitesInput').value = JSON.stringify(window.courseManagerState.post_requisites || []);
    }
    // Let the validation system handle the submission
    // The form submit event will be handled by the validation system
}

// Implementation functions
function initializeDragAndDrop() {
    console.log('[DEBUG] initializeDragAndDrop() called');
    // Basic drag and drop implementation
    const moduleContainers = document.querySelectorAll('.module-content-container');
    moduleContainers.forEach(container => {
        container.addEventListener('dragover', (e) => {
            e.preventDefault();
            container.classList.add('drag-over');
        });
        container.addEventListener('dragleave', () => {
            container.classList.remove('drag-over');
        });
        container.addEventListener('drop', (e) => {
            e.preventDefault();
            container.classList.remove('drag-over');
            // Handle drop logic here
        });
    });
}

function setupValidation() {
    console.log('[DEBUG] setupValidation() called');
    
    // Initialize course creation validation if the function exists
    if (typeof initializeCourseCreationValidation === 'function') {
        initializeCourseCreationValidation();
    } else {
        console.warn('[DEBUG] initializeCourseCreationValidation function not found');
    }
}

function initializeTabs() {
    console.log('[DEBUG] initializeTabs() called');
    
    // Get all tab buttons
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    console.log('[DEBUG] Found tab buttons:', tabButtons.length);
    
    // Add event listeners for tab switching
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', (e) => {
            const target = e.target.getAttribute('data-bs-target');
            const tabId = e.target.id;
            console.log('[DEBUG] Tab shown:', target, 'Tab ID:', tabId);
            
            // Trigger rendering for specific tabs
            if (target === '#modules') {
                console.log('[DEBUG] Rendering modules tab');
                renderModules();
            } else if (target === '#prerequisites') {
                console.log('[DEBUG] Rendering prerequisites tab');
                renderPrerequisites();
            } else if (target === '#post-requisite') {
                console.log('[DEBUG] Rendering post-requisites tab');
                renderPostRequisites();
            }
            
            // Update tab highlighting
            updateTabHighlighting(tabId);
        });
        
        // Add click event for validation
        button.addEventListener('click', (e) => {
            const currentTab = document.querySelector('.tab-pane.active');
            if (currentTab && currentTab.id === 'basic-info') {
                // Validate basic info tab before allowing navigation
                if (!validateBasicInfoTab()) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        });
    });
    
    // Initialize first tab
    const firstTab = document.querySelector('[data-bs-toggle="tab"]');
    if (firstTab) {
        console.log('[DEBUG] Initializing first tab:', firstTab.id);
        updateTabHighlighting(firstTab.id);
    }
}

function updateTabHighlighting(activeTabId) {
    console.log('[DEBUG] updateTabHighlighting() called for tab:', activeTabId);
    
    // Remove active class from all tabs
    document.querySelectorAll('.nav-link').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Add active class to the current tab
    const activeTab = document.getElementById(activeTabId);
    if (activeTab) {
        activeTab.classList.add('active');
        console.log('[DEBUG] Set active tab:', activeTabId);
    }
}

function validateBasicInfoTab() {
    console.log('[DEBUG] validateBasicInfoTab() called');
    
    const requiredFields = ['name', 'category_id', 'subcategory_id', 'course_type', 'difficulty_level'];
    let isValid = true;
    
    requiredFields.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field && !field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else if (field) {
            field.classList.remove('is-invalid');
        }
    });
    
    if (!isValid) {
        showToast('error', 'Please fill in all required fields in Basic Info tab');
        highlightTabWithError('basic-info-tab');
    }
    
    return isValid;
}

// Add this function globally for tab error highlighting
function highlightTabWithError(tabId) {
    // Try by id first, then by data-bs-target
    let tabButton = document.getElementById(tabId);
    if (!tabButton) {
        tabButton = document.querySelector(`#courseCreationTabs button[data-bs-target="#basic-info"]`);
    }
    if (tabButton) {
        tabButton.classList.add('tab-error');
        tabButton.style.borderColor = '#dc3545';
        tabButton.style.borderWidth = '2px';
        tabButton.style.borderStyle = 'solid';
        tabButton.style.color = '#dc3545';
    }
}

function loadInitialData() {
    console.log('[DEBUG] loadInitialData() called');
    // Load initial data like categories, VLR content, etc.
    loadCategories();
    loadVLRContent();
}

function loadCategories() {
    console.log('[DEBUG] loadCategories() called');
    // Load course categories using legacy routing
    const url = 'index.php?controller=CourseCategoryController&action=getCategoriesForDropdown';
    console.log('[DEBUG] Categories URL:', url);
    
    fetch(url)
        .then(response => {
            console.log('[DEBUG] Categories response status:', response.status);
            console.log('[DEBUG] Categories response headers:', response.headers);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text(); // Get response as text first to debug
        })
        .then(text => {
            console.log('[DEBUG] Categories response text:', text.substring(0, 500));
            try {
                const data = JSON.parse(text);
                console.log('[DEBUG] Categories data received:', data);
                if (data.success) {
                    populateCategorySelect(data.categories);
                } else {
                    console.error('[ERROR] Categories API returned success: false:', data.message);
                }
            } catch (parseError) {
                console.error('[ERROR] Failed to parse JSON response:', parseError);
                console.error('[ERROR] Response text:', text);
            }
        })
        .catch(error => {
            console.error('[ERROR] Failed to load categories:', error);
            console.error('[ERROR] Error details:', error.message);
        });
}

function loadVLRContent() {
    console.log('[DEBUG] loadVLRContent() called');
    // VLR content should already be loaded from the modal content
    if (window.vlrContent) {
        console.log('[DEBUG] VLR content already available:', window.vlrContent);
    } else {
        console.warn('[DEBUG] VLR content not available');
    }
}

function loadExistingCourseData() {
    console.log('[DEBUG] loadExistingCourseData() called');
    // Check if we're in edit mode
    const courseIdInput = document.getElementById('course_id');
    if (courseIdInput && courseIdInput.value) {
        courseManagerState.isEditMode = true;
        courseManagerState.courseId = courseIdInput.value;
        console.log('[DEBUG] Edit mode detected, course ID:', courseManagerState.courseId);
        
        // Load existing data from hidden inputs
        loadExistingModules();
        loadExistingPrerequisites();
        loadExistingPostRequisites();
        loadExistingTags();
        loadExistingLearningObjectives();
    } else {
        console.log('[DEBUG] Add mode detected');
    }
}

function loadExistingModules() {
    console.log('[DEBUG] loadExistingModules() called');
    const modulesInput = document.getElementById('existing_modules');
    if (modulesInput && modulesInput.value) {
        try {
            const modules = JSON.parse(modulesInput.value);
            courseManagerState.modules = modules;
            console.log('[DEBUG] Loaded existing modules:', modules);
            renderModules();
        } catch (error) {
            console.error('[ERROR] Failed to parse existing modules:', error);
        }
    }
}

function loadExistingPrerequisites() {
    console.log('[DEBUG] loadExistingPrerequisites() called');
    const prerequisitesInput = document.getElementById('existing_prerequisites');
    if (prerequisitesInput && prerequisitesInput.value) {
        try {
            const prerequisites = JSON.parse(prerequisitesInput.value);
            courseManagerState.prerequisites = prerequisites;
            console.log('[DEBUG] Loaded existing prerequisites:', prerequisites);
            renderPrerequisites();
        } catch (error) {
            console.error('[ERROR] Failed to parse existing prerequisites:', error);
        }
    }
}

function loadExistingPostRequisites() {
    console.log('[DEBUG] loadExistingPostRequisites() called');
    const postRequisitesInput = document.getElementById('existing_post_requisites');
    if (postRequisitesInput && postRequisitesInput.value) {
        try {
            const postRequisites = JSON.parse(postRequisitesInput.value);
            courseManagerState.post_requisites = postRequisites;
            console.log('[DEBUG] Loaded existing post-requisites:', postRequisites);
            renderPostRequisites();
        } catch (error) {
            console.error('[ERROR] Failed to parse existing post-requisites:', error);
        }
    }
}

function loadExistingTags() {
    console.log('[DEBUG] loadExistingTags() called');
    const hiddenInput = document.getElementById('tagsList');
    if (hiddenInput) {
        let tags = hiddenInput.value;
        try {
            if (!tags || tags === 'null') {
                tags = [];
            } else if (typeof tags === 'string') {
                tags = JSON.parse(tags);
            }
            if (!Array.isArray(tags)) {
                tags = [];
            }
            courseManagerState.tags = tags;
            console.log('[DEBUG] Loaded existing tags:', tags, typeof tags);
            renderTags();
        } catch (error) {
            console.error('[ERROR] Failed to parse existing tags:', error, tags);
            courseManagerState.tags = [];
        }
    }
}

function loadExistingLearningObjectives() {
    console.log('[DEBUG] loadExistingLearningObjectives() called');
    const hiddenInput = document.getElementById('learningObjectivesList');
    if (hiddenInput) {
        let learningObjectives = hiddenInput.value;
        try {
            if (!learningObjectives || learningObjectives === 'null') {
                learningObjectives = [];
            } else if (typeof learningObjectives === 'string') {
                learningObjectives = JSON.parse(learningObjectives);
            }
            if (!Array.isArray(learningObjectives)) {
                learningObjectives = [];
            }
            courseManagerState.learningObjectives = learningObjectives;
            console.log('[DEBUG] Loaded existing learning objectives:', learningObjectives, typeof learningObjectives);
            renderLearningObjectives();
        } catch (error) {
            console.error('[ERROR] Failed to parse existing learning objectives:', error, learningObjectives);
            courseManagerState.learningObjectives = [];
        }
    }
}

function initializeTagSystems() {
    console.log('[DEBUG] initializeTagSystems() called');
    
    // Bind events for tags input
    const tagsInput = document.getElementById('tagsInput');
    if (tagsInput) {
        tagsInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = this.value.trim();
                if (value) {
                    addTag(value, 'tags');
                    this.value = '';
                }
            }
        });
        console.log('[DEBUG] Tags input event bound');
    } else {
        console.error('[ERROR] Tags input not found');
    }
    
    // Bind events for learning objectives input
    const objectivesInput = document.getElementById('learningObjectivesInput');
    if (objectivesInput) {
        objectivesInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = this.value.trim();
                if (value) {
                    addTag(value, 'learning_objectives');
                    this.value = '';
                }
            }
        });
        console.log('[DEBUG] Learning objectives input event bound');
    } else {
        console.error('[ERROR] Learning objectives input not found');
    }
}

function addTag(value, type) {
    console.log('[DEBUG] Adding tag:', value, 'type:', type);
    if (type === 'tags') {
        // Check for duplicates (case-insensitive)
        const trimmedValue = value.trim();
        if (trimmedValue && !courseManagerState.tags.some(tag => tag.toLowerCase() === trimmedValue.toLowerCase())) {
            courseManagerState.tags.push(trimmedValue);
            renderTags();
        } else {
            console.log('[DEBUG] Duplicate tag ignored:', trimmedValue);
        }
    } else if (type === 'learning_objectives') {
        // Check for duplicates (case-insensitive)
        const trimmedValue = value.trim();
        if (trimmedValue && !courseManagerState.learningObjectives.some(objective => objective.toLowerCase() === trimmedValue.toLowerCase())) {
            courseManagerState.learningObjectives.push(trimmedValue);
            renderLearningObjectives();
        } else {
            console.log('[DEBUG] Duplicate learning objective ignored:', trimmedValue);
        }
    }
}

function setupImagePreviewHandlers() {
    console.log('[DEBUG] setupImagePreviewHandlers() called');
    // Setup image preview for course thumbnail
    const imageInput = document.getElementById('course_image');
    const previewContainer = document.getElementById('image_preview');
    
    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewContainer.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`;
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

function getTabIndex(tabId) {
    console.log('[DEBUG] getTabIndex() called with:', tabId);
    const tabMap = {
        'basic-info-tab': 0,
        'modules-tab': 1,
        'prerequisites-tab': 2,
        'post-requisites-tab': 3,
        'settings-tab': 4
    };
    return tabMap[tabId] || 0;
}

function switchTab(tabIndex) {
    console.log('[DEBUG] switchTab() called with:', tabIndex);
    courseManagerState.currentTab = tabIndex;
    
    // Trigger rendering for the current tab
    switch (tabIndex) {
        case 1: // Modules tab
            renderModules();
            break;
        case 2: // Prerequisites tab
            renderPrerequisites();
            break;
        case 3: // Post-requisites tab
            renderPostRequisites();
            break;
    }
}

function addModule() {
    console.log('[DEBUG] addModule() called');
    const module = {
        id: Date.now(),
        name: `Module ${courseManagerState.modules.length + 1}`,
        description: '',
        content: []
    };
    courseManagerState.modules.push(module);
    renderModules();
}

function showPrerequisiteModal() {
    console.log('[DEBUG] showPrerequisiteModal() called');
    // Show VLR content selection modal for prerequisites
    if (window.showVLRModal) {
        window.showVLRModal('prerequisite');
    } else {
        console.error('[ERROR] showVLRModal function not available');
    }
}

function loadSubcategories() {
    console.log('[DEBUG] loadSubcategories() called');
    const categoryId = document.getElementById('category_id').value;
    const subcategorySelect = document.getElementById('subcategory_id');
    
    if (categoryId && subcategorySelect) {
        const url = `index.php?controller=CourseSubcategoryController&action=getSubcategoriesForDropdown&category_id=${categoryId}`;
        console.log('[DEBUG] Subcategories URL:', url);
        
        fetch(url)
            .then(response => {
                console.log('[DEBUG] Subcategories response status:', response.status);
                console.log('[DEBUG] Subcategories response headers:', response.headers);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text(); // Get response as text first to debug
            })
            .then(text => {
                console.log('[DEBUG] Subcategories response text:', text.substring(0, 500));
                try {
                    const data = JSON.parse(text);
                    console.log('[DEBUG] Subcategories data received:', data);
                    if (data.success) {
                        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                        data.subcategories.forEach(subcategory => {
                            subcategorySelect.innerHTML += `<option value="${subcategory.id}">${subcategory.name}</option>`;
                        });
                        // Restore existing subcategory value if in edit mode
                        if (courseManagerState.isEditMode) {
                            const existingSubcategory = document.getElementById('existing_subcategory_id');
                            if (existingSubcategory && existingSubcategory.value) {
                                subcategorySelect.value = existingSubcategory.value;
                            }
                        }
                    } else {
                        console.error('[ERROR] Subcategories API returned success: false:', data.message);
                    }
                } catch (parseError) {
                    console.error('[ERROR] Failed to parse JSON response:', parseError);
                    console.error('[ERROR] Response text:', text);
                }
            })
            .catch(error => {
                console.error('[ERROR] Failed to load subcategories:', error);
                console.error('[ERROR] Error details:', error.message);
            });
    }
}

// Form submission is now handled by the validation system
// The submitCourseForm function has been moved to course_creation_validation.js

function showPostRequisiteVLRModal(type) {
    console.log('[DEBUG] showPostRequisiteVLRModal() called with:', type);
    // Show VLR content selection modal for post-requisites
    if (window.showVLRModal) {
        window.showVLRModal('post_requisite', type);
    } else {
        console.error('[ERROR] showVLRModal function not available');
    }
}

// Rendering functions
function renderModules() {
    console.log('[DEBUG] renderModules() called');
    const container = document.getElementById('modulesContainer');
    if (!container) {
        console.error('[ERROR] modulesContainer not found');
        return;
    }
    
    // Remove error highlight if modules exist
    const modulesTabButton = document.querySelector('#courseCreationTabs button[data-bs-target="#modules"]');
    if (courseManagerState.modules.length > 0) {
        if (container) {
            container.style.border = '';
            container.style.borderRadius = '';
            container.style.boxShadow = '';
        }
        if (modulesTabButton) {
            modulesTabButton.classList.remove('tab-error');
            modulesTabButton.style.borderColor = '';
            modulesTabButton.style.borderWidth = '';
            modulesTabButton.style.borderStyle = '';
            modulesTabButton.style.color = '';
        }
    }
    
    if (courseManagerState.modules.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-folder-open text-muted fa-3x mb-3"></i>
                <p class="text-muted">No modules added yet</p>
            </div>
        `;
    } else {
        let html = '';
        courseManagerState.modules.forEach((module, index) => {
            html += `
                <div class="module-item border rounded p-3 mb-3" data-module-id="${module.id}">
                    <div class="module-header">
                        <h6 class="mb-0 module-title">Module ${index + 1}</h6>
                        <div class="btn-group btn-group-sm module-actions">
                            <button type="button" class="btn btn-outline-secondary" onclick="moveModuleUp(${index})" ${index === 0 ? 'disabled' : ''} title="Move Up">
                                <i class="fas fa-arrow-up"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="moveModuleDown(${index})" ${index === courseManagerState.modules.length - 1 ? 'disabled' : ''} title="Move Down">
                                <i class="fas fa-arrow-down"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="removeModule(${module.id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Module Title -->
                    <div class="mb-3">
                        <label class="form-label">Module Title</label>
                        <input type="text" class="form-control module-title-input" 
                               value="${module.name || `Module ${index + 1}`}" 
                               placeholder="Enter module title"
                               onchange="updateModuleTitle(${module.id}, this.value)">
                    </div>
                    
                    <!-- Module Description -->
                    <div class="mb-3">
                        <label class="form-label">Module Description</label>
                        <textarea class="form-control module-description-input" 
                                  rows="3" 
                                  placeholder="Enter module description"
                                  onchange="updateModuleDescription(${module.id}, this.value)">${module.description || ''}</textarea>
                    </div>
                    
                    <!-- Module Content Section -->
                    <div class="mb-3">
                        <label class="form-label">Module Content</label>
                        <div class="module-content-container border rounded p-2 bg-light">
                            ${renderModuleContent(module.content, module.id)}
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-primary btn-sm" onclick="addModuleContent(${module.id})">
                                <i class="fas fa-plus me-1"></i>Add VLR Content
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        // Add Module button inside the container, after the last module card
        html += `
            <div class="d-flex justify-content-end mt-2">
                <button type="button" class="btn btn-primary add-module-btn">
                    <i class="fas fa-plus me-2"></i>Add Module
                </button>
            </div>
        `;
        container.innerHTML = html;
    }
    
    // Re-bind add module button(s) by class
    document.querySelectorAll('.add-module-btn').forEach(btn => {
        btn.removeEventListener('click', handleAddModule); // Remove previous to avoid double binding
        btn.addEventListener('click', handleAddModule);
    });
}

function renderModuleContent(content, moduleId) {
    console.log('[DEBUG] renderModuleContent() called with:', content);
    if (!content || content.length === 0) {
        return '<p class="text-muted small">No content added</p>';
    }
    return content.map((item, idx) => {
        const type = item.type || item.content_type || 'unknown';
        const title = item.title || item.name || 'Untitled';
        const icon = getContentIcon(type);
        // Capitalize type for badge
        const typeBadge = type.charAt(0).toUpperCase() + type.slice(1);
        return `
            <div class="content-item d-flex align-items-center p-2 border rounded mb-2">
                <i class="${icon} me-2"></i>
                <span class="flex-grow-1">
                    ${title}
                </span>
                <div class="badge bg-primary-subtle text-primary ms-2">${typeBadge}</div>
                <div class="btn-group btn-group-sm ms-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveModuleContentUp(${moduleId}, ${idx})" ${idx === 0 ? 'disabled' : ''} title="Move Up">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="moveModuleContentDown(${moduleId}, ${idx})" ${idx === content.length - 1 ? 'disabled' : ''} title="Move Down">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeModuleContent(${item.id})" title="Delete">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function renderPrerequisites() {
    console.log('[DEBUG] renderPrerequisites() called');
    const container = document.getElementById('prerequisites_container');
    if (!container) {
        console.error('[ERROR] prerequisites_container not found');
        return;
    }
    
    if (courseManagerState.prerequisites.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-list-check text-muted fa-3x mb-3"></i>
                <p class="text-muted">No prerequisites added</p>
            </div>
        `;
    } else {
        let html = '<div class="module-content-container border rounded p-2 bg-light">';
        courseManagerState.prerequisites.forEach((item, index) => {
            const type = item.type || item.content_type || 'unknown';
            const title = item.title || item.name || 'Untitled';
            const icon = getContentIcon(type);
            const typeBadge = type.charAt(0).toUpperCase() + type.slice(1);
            html += `
                <div class="content-item d-flex align-items-center p-2 border rounded mb-2">
                    <i class="${icon} me-2"></i>
                    <span class="flex-grow-1">${title}</span>
                    <div class="badge bg-primary-subtle text-primary ms-2">${typeBadge}</div>
                    <div class="btn-group btn-group-sm ms-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="movePrerequisiteUp(${index})" ${index === 0 ? 'disabled' : ''} title="Move Up">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="movePrerequisiteDown(${index})" ${index === courseManagerState.prerequisites.length - 1 ? 'disabled' : ''} title="Move Down">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePrerequisite(${index})" title="Delete">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }
}

function renderPostRequisites() {
    console.log('[DEBUG] renderPostRequisites() called');
    const container = document.getElementById('postRequisitesContainer');
    if (!container) {
        console.error('[ERROR] postRequisitesContainer not found');
        return;
    }
    
    let html = '';
    if (courseManagerState.post_requisites.length === 0) {
        html += `
            <div class="text-center py-4">
                <i class="fas fa-list-check text-muted fa-3x mb-3"></i>
                <p class="text-muted">No post-requisites added</p>
            </div>
        `;
    } else {
        html += '<div class="module-content-container border rounded p-2 bg-light">';
        courseManagerState.post_requisites.forEach((item, index) => {
            const type = item.type || item.content_type || 'unknown';
            const title = item.title || item.name || 'Untitled';
            const icon = getContentIcon(type);
            const typeBadge = type.charAt(0).toUpperCase() + type.slice(1);
            html += `
                <div class="content-item d-flex align-items-center p-2 border rounded mb-2">
                    <i class="${icon} me-2"></i>
                    <span class="flex-grow-1">${title}</span>
                    <div class="badge bg-primary-subtle text-primary ms-2">${typeBadge}</div>
                    <div class="btn-group btn-group-sm ms-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="movePostRequisiteUp(${index})" ${index === 0 ? 'disabled' : ''} title="Move Up">
                            <i class="fas fa-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="movePostRequisiteDown(${index})" ${index === courseManagerState.post_requisites.length - 1 ? 'disabled' : ''} title="Move Down">
                            <i class="fas fa-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePostRequisite(${index})" title="Delete">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
    }
    container.innerHTML = html;
    // No add button group rendered
    // Re-bind post-requisite buttons (move/remove)
    bindPostRequisiteButtons();
}

function renderTags() {
    console.log('[DEBUG] renderTags() called');
    const display = document.getElementById('tagsDisplay');
    const hiddenInput = document.getElementById('tagsList');
    if (!display || !hiddenInput) {
        console.error('[ERROR] Tags display or hidden input not found');
        return;
    }
    
    display.innerHTML = courseManagerState.tags.map(tag => 
        `<span class="tag">${tag}<button type="button" class="remove-tag" data-tag="${tag}">×</button></span>`
    ).join('');
    
    // Update hidden input
    hiddenInput.value = JSON.stringify(courseManagerState.tags);
    
    // Bind remove tag events
    display.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-tag')) {
            const tagText = event.target.getAttribute('data-tag');
            removeTag(tagText);
        }
    });
}

function renderLearningObjectives() {
    console.log('[DEBUG] renderLearningObjectives() called');
    const display = document.getElementById('learningObjectivesDisplay');
    const hiddenInput = document.getElementById('learningObjectivesList');
    if (!display || !hiddenInput) {
        console.error('[ERROR] Learning objectives display or hidden input not found');
        return;
    }
    
    display.innerHTML = courseManagerState.learningObjectives.map(objective => 
        `<span class="tag">${objective}<button type="button" class="remove-tag" data-objective="${objective}">×</button></span>`
    ).join('');
    
    // Update hidden input
    hiddenInput.value = JSON.stringify(courseManagerState.learningObjectives);
    
    // Bind remove objective events
    display.addEventListener('click', function(event) {
        if (event.target.classList.contains('remove-tag')) {
            const objectiveText = event.target.getAttribute('data-objective');
            removeLearningObjective(objectiveText);
        }
    });
}

// Utility functions
function getContentIcon(type) {
    const iconMap = {
        'scorm': 'fas fa-cube',
        'video': 'fas fa-video',
        'audio': 'fas fa-volume-up',
        'document': 'fas fa-file-alt',
        'image': 'fas fa-image',
        'assessment': 'fas fa-question-circle',
        'survey': 'fas fa-clipboard-list',
        'feedback': 'fas fa-comments',
        'assignment': 'fas fa-tasks',
        'interactive': 'fas fa-mouse-pointer',
        'external': 'fas fa-external-link-alt',
        'non_scorm': 'fas fa-file-archive',
        'unknown': 'fas fa-file'
    };
    return iconMap[type] || iconMap.unknown;
}

function populateCategorySelect(categories) {
    const select = document.getElementById('category_id');
    if (select) {
        select.innerHTML = '<option value="">Select Category</option>';
        categories.forEach(category => {
            select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
        });
        // Set selected value if in edit mode
        const editCategoryIdInput = document.getElementById('edit_category_id');
        if (editCategoryIdInput && editCategoryIdInput.value) {
            select.value = editCategoryIdInput.value;
        }
    }
}

function bindPostRequisiteButtons() {
    const btnIds = [
        'selectPostAssessmentBtn',
        'selectPostFeedbackBtn',
        'selectPostSurveyBtn',
        'selectPostAssignmentBtn'
    ];
    const types = ['assessment', 'feedback', 'survey', 'assignment'];
    btnIds.forEach((id, idx) => {
        const btn = document.getElementById(id);
        if (btn) {
            // Replace with clone to remove all previous listeners
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);
            newBtn.addEventListener('click', () => showPostRequisiteVLRModal(types[idx]));
        }
    });
}

// Global functions for external access
window.addModuleContent = function(moduleId) {
    console.log('[DEBUG] addModuleContent() called for module:', moduleId);
    if (window.showVLRModal) {
        window.showVLRModal('module_content', moduleId);
    }
};

window.removeModule = function(moduleId) {
    console.log('[DEBUG] removeModule() called for module:', moduleId);
    courseManagerState.modules = courseManagerState.modules.filter(m => m.id !== moduleId);
    renderModules();
};

window.removeModuleContent = function(contentId) {
    console.log('[DEBUG] removeModuleContent() called for content:', contentId);
    courseManagerState.modules.forEach(module => {
        module.content = module.content.filter(c => c.id !== contentId);
    });
    renderModules();
};

window.removePrerequisite = function(index) {
    console.log('[DEBUG] removePrerequisite() called for index:', index);
    courseManagerState.prerequisites.splice(index, 1);
    renderPrerequisites();
};

window.removePostRequisite = function(index) {
    console.log('[DEBUG] removePostRequisite() called for index:', index);
    courseManagerState.post_requisites.splice(index, 1);
    renderPostRequisites();
};

window.movePostRequisiteUp = function(index) {
    if (index > 0) {
        const temp = courseManagerState.post_requisites[index];
        courseManagerState.post_requisites[index] = courseManagerState.post_requisites[index - 1];
        courseManagerState.post_requisites[index - 1] = temp;
        renderPostRequisites();
    }
};

window.movePostRequisiteDown = function(index) {
    if (index < courseManagerState.post_requisites.length - 1) {
        const temp = courseManagerState.post_requisites[index];
        courseManagerState.post_requisites[index] = courseManagerState.post_requisites[index + 1];
        courseManagerState.post_requisites[index + 1] = temp;
        renderPostRequisites();
    }
};

window.movePrerequisiteUp = function(index) {
    if (index > 0) {
        const arr = courseManagerState.prerequisites;
        [arr[index - 1], arr[index]] = [arr[index], arr[index - 1]];
        renderPrerequisites();
    }
};
window.movePrerequisiteDown = function(index) {
    if (index < courseManagerState.prerequisites.length - 1) {
        const arr = courseManagerState.prerequisites;
        [arr[index], arr[index + 1]] = [arr[index + 1], arr[index]];
        renderPrerequisites();
    }
};

window.removeTag = function(tag) {
    courseManagerState.tags = courseManagerState.tags.filter(t => t !== tag);
    renderTags();
};

window.removeLearningObjective = function(objective) {
    courseManagerState.learningObjectives = courseManagerState.learningObjectives.filter(o => o !== objective);
    renderLearningObjectives();
};

window.updateModuleTitle = function(moduleId, title) {
    console.log('[DEBUG] updateModuleTitle() called for module:', moduleId, 'title:', title);
    const module = courseManagerState.modules.find(m => m.id === moduleId);
    if (module) {
        module.name = title;
        console.log('[DEBUG] Module title updated');
    }
};

window.updateModuleDescription = function(moduleId, description) {
    console.log('[DEBUG] updateModuleDescription() called for module:', moduleId, 'description:', description);
    const module = courseManagerState.modules.find(m => m.id === moduleId);
    if (module) {
        module.description = description;
        console.log('[DEBUG] Module description updated');
    }
};

// ================= VLR Content Selection Modal Logic =================
// This logic is adapted from the previous CourseCreationManager implementation
// and made compatible with the current function-based approach and courseManagerState.

/**
 * Show the VLR content selection modal for modules, prerequisites, or post-requisites.
 * @param {string} context - 'module_content', 'prerequisite', or 'post_requisite'
 * @param {number|string} [moduleIdOrType] - moduleId for module content, or type for post_requisite
 */
window.showVLRModal = function(context, moduleIdOrType) {
    // VLR content should be loaded globally as window.vlrContent
    if (!window.vlrContent || Object.keys(window.vlrContent).length === 0) {
        showToast('VLR content not available', 'error');
        return;
    }
    let title = 'Select VLR Content';
    let preselectedIds = [];
    let allowedTypes = null;
    let moduleId = null;
    let type = null;
    if (context === 'module_content') {
        moduleId = moduleIdOrType;
        title = 'Select VLR Content for Module';
        // Preselect current module content (robust: always use content_id if present)
        const module = courseManagerState.modules.find(m => m.id === moduleId);
        preselectedIds = module ? module.content.map(item => ({ id: Number(item.content_id || item.id), type: (item.type || '').toLowerCase() })) : [];
        console.log('[DEBUG] showVLRModal preselectedIds for module_content:', preselectedIds);
        // Filter allowed types by course type
        const courseType = document.getElementById('course_type')?.value;
        if (courseType === 'e-learning') {
            allowedTypes = ['scorm', 'non_scorm', 'document', 'external', 'interactive', 'audio', 'video', 'image'];
        } else if (courseType === 'blended') {
            allowedTypes = [
                'assignment', 'document', 'video', 'audio', 'interactive',
                'scorm', 'non_scorm', 'assessment', 'image', 'external'
            ];
        } else if (courseType === 'classroom') {
            allowedTypes = ['document', 'assignment', 'image', 'external'];
        } else if (courseType === 'assessment') {
            allowedTypes = ['assessment'];
        }
        type = 'module_content';
    } else if (context === 'prerequisite') {
        title = 'Select Prerequisite Content';
        // Preselect using prerequisite_id if present
        preselectedIds = courseManagerState.prerequisites.map(item => ({ id: Number(item.prerequisite_id || item.id), type: (item.type || item.content_type || '').toLowerCase() }));
        console.log('[DEBUG] showVLRModal preselectedIds for prerequisite:', preselectedIds);
        type = 'prerequisite';
    } else if (context === 'post_requisite') {
        type = moduleIdOrType; // e.g. 'assessment', 'feedback', etc.
        title = 'Select ' + getVLRTypeDisplayName(type);
        preselectedIds = courseManagerState.post_requisites.filter(item => item.content_type === type).map(item => ({ id: item.content_id, type: item.content_type }));
        allowedTypes = [type];
    }
    showVLRContentModal(title, window.vlrContent, type, moduleId, preselectedIds, allowedTypes);
};

function showVLRContentModal(title, content, type, moduleId = null, preselectedIds = [], allowedTypes = null) {
    // Group content by type
    let groupedContent = {};
    if (Array.isArray(content)) {
        content.forEach(item => {
            if (!groupedContent[item.type]) groupedContent[item.type] = [];
            groupedContent[item.type].push(item);
        });
    } else if (typeof content === 'object' && content !== null) {
        groupedContent = { ...content };
    }
    // Filter allowed types if specified
    if (allowedTypes) {
        Object.keys(groupedContent).forEach(typeKey => {
            if (!allowedTypes.includes(typeKey)) {
                delete groupedContent[typeKey];
            }
        });
    }
    // Modal HTML
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
                    <ul class="nav nav-tabs" id="vlrTypeTabs" role="tablist">
                        ${Object.keys(groupedContent).map((contentType, index) => `
                            <li class="nav-item" role="presentation">
                                <button class="nav-link ${index === 0 ? 'active' : ''}" 
                                        id="tab-${contentType}" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-${contentType}" 
                                        type="button" 
                                        role="tab">
                                    <i class="fas ${getContentIcon(contentType)} me-2"></i>
                                    ${getVLRTypeDisplayName(contentType)} 
                                    <span class="badge bg-secondary ms-2">${groupedContent[contentType].length}</span>
                                </button>
                            </li>
                        `).join('')}
                    </ul>
                    <div class="tab-content mt-3" id="vlrTypeTabContent">
                        ${Object.keys(groupedContent).map((contentType, index) => `
                            <div class="tab-pane fade ${index === 0 ? 'show active' : ''}" 
                                 id="content-${contentType}" 
                                 role="tabpanel">
                                <div class="d-flex align-items-center mb-2">
                                    <input type="checkbox" class="form-check-input me-2" id="selectAllVLR-${contentType}" aria-label="Select all ${getVLRTypeDisplayName(contentType)}">
                                    <label for="selectAllVLR-${contentType}" class="form-check-label small">Select All</label>
                                </div>
                                <div class="row g-3" id="content-grid-${contentType}">
                                    ${groupedContent[contentType].map(item => `
                                        <div class="col-md-6 col-lg-4 vlr-content-item d-flex align-items-center gap-3 p-3 border rounded bg-white position-relative" 
                                             tabindex="0" role="option" aria-label="${item.title}" 
                                             data-vlr-id="${item.id}" 
                                             data-vlr-type="${item.type}"
                                             data-title="${item.title.toLowerCase()}"
                                             data-description="${(item.description || '').toLowerCase()}"
                                             data-vlr-json='${JSON.stringify(item).replace(/'/g, "&#39;")}'>
                                            <i class="fas ${getContentIcon(item.type)} mt-1" style="font-size: 2em; color: var(--bs-primary, #4b0082); filter: drop-shadow(0 2px 4px #b197fc33);" title="${getVLRTypeDisplayName(item.type)}" data-bs-toggle="tooltip"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold mb-1 text-truncate" title="${item.title}" data-bs-toggle="tooltip" style="font-size:1.1em;">${item.title.length > 30 ? item.title.substring(0, 27) + '...' : item.title}</div>
                                                <small class="text-muted d-block mb-2 text-truncate" title="${item.description}" data-bs-toggle="tooltip">${item.description && item.description.length > 60 ? item.description.substring(0, 57) + '...' : item.description || ''}</small>
                                                <div class="d-flex align-items-center gap-2 mt-1">
                                                    <span class="badge bg-primary-subtle text-primary small">${getVLRTypeDisplayName(item.type)}</span>
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
                                        <p class="text-muted">No ${getVLRTypeDisplayName(contentType)} content available</p>
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
    // Search functionality
    initializeVLRSearch(modal);
    // Checkbox functionality
    initializeVLRCheckboxes(modal, type, moduleId, preselectedIds);
    // Clean up modal after hiding
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
    // View toggle
    const toggleBtn = modal.querySelector('#toggleVLRViewBtn');
    function updateVLRView() {
        // For each tab-pane/content grid
        const tabPanes = modal.querySelectorAll('.tab-pane');
        tabPanes.forEach(tabPane => {
            const grid = tabPane.querySelector('[id^="content-grid-"]');
            if (!grid) return;
            const items = grid.querySelectorAll('.vlr-content-item');
            if (isGridView) {
                // Grid view: add row/g-3/col classes
                grid.classList.add('row', 'g-3');
                items.forEach(item => {
                    item.classList.add('col-md-6', 'col-lg-4');
                    item.classList.remove('col-12');
                });
                toggleBtn.innerHTML = '<i class="fas fa-th-list"></i> <span id="vlrViewLabel">List View</span>';
            } else {
                // List view: remove grid classes, use col-12
                grid.classList.remove('row', 'g-3');
                items.forEach(item => {
                    item.classList.remove('col-md-6', 'col-lg-4');
                    item.classList.add('col-12');
                });
                toggleBtn.innerHTML = '<i class="fas fa-th"></i> <span id="vlrViewLabel">Grid View</span>';
            }
        });
        // Save preference
        localStorage.setItem('vlrViewMode', isGridView ? 'grid' : 'list');
    }
    toggleBtn.addEventListener('click', () => {
        isGridView = !isGridView;
        updateVLRView();
    });
    updateVLRView();
}

function getVLRTypeDisplayName(type) {
    const typeNames = {
        'scorm': 'SCORM Packages',
        'non_scorm': 'Non-SCORM Packages',
        'nonscorm': 'Non-SCORM Packages', // fallback for legacy
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

function initializeVLRSearch(modal) {
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

function initializeVLRCheckboxes(modal, type, moduleId, preselectedIds = []) {
    const checkboxes = modal.querySelectorAll('.vlr-checkbox');
    const selectedCountSpan = modal.querySelector('#selectedCount');
    const selectedCountBtn = modal.querySelector('#selectedCountBtn');
    const addSelectedBtn = modal.querySelector('#addSelectedVLRBtn');
    // Debug: print all checkbox id/type values
    checkboxes.forEach(checkbox => {
        const checkboxId = Number(checkbox.value);
        const checkboxType = (checkbox.closest('.vlr-content-item').dataset.vlrType || '').toLowerCase();
        console.log('[DEBUG] VLR Checkbox:', { id: checkboxId, type: checkboxType });
    });
    // Pre-check checkboxes for already selected items
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        const checkboxId = Number(checkbox.value);
        const checkboxType = (checkbox.closest('.vlr-content-item').dataset.vlrType || '').toLowerCase();
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
        // Update Select All checkboxes for each tab
        const tabPanes = modal.querySelectorAll('.tab-pane');
        tabPanes.forEach(tabPane => {
            const selectAll = tabPane.querySelector('[id^="selectAllVLR-"]');
            const itemCheckboxes = tabPane.querySelectorAll('.vlr-checkbox');
            const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
            if (selectAll) {
                if (itemCheckboxes.length > 0 && checkedCount === itemCheckboxes.length) {
                    selectAll.checked = true;
                    selectAll.indeterminate = false;
                } else if (checkedCount === 0) {
                    selectAll.checked = false;
                    selectAll.indeterminate = false;
                } else {
                    selectAll.checked = false;
                    selectAll.indeterminate = true;
                }
            }
        });
    };
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    // Select All logic for each tab
    const selectAllCheckboxes = modal.querySelectorAll('[id^="selectAllVLR-"]');
    selectAllCheckboxes.forEach(selectAll => {
        selectAll.addEventListener('change', function() {
            const tabPane = selectAll.closest('.tab-pane');
            if (!tabPane) return;
            const itemCheckboxes = tabPane.querySelectorAll('.vlr-checkbox');
            itemCheckboxes.forEach(cb => {
                // Only check visible items
                if (cb.closest('.vlr-content-item').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
            updateSelectedCount();
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
            const itemJson = itemCard.getAttribute('data-vlr-json');
            let itemObj = {};
            try { itemObj = JSON.parse(itemJson.replace(/&#39;/g, "'")); } catch (e) { itemObj = {}; }
            selectedItems.push(itemObj);
        });
        if (selectedItems.length > 0) {
            addSelectedVLR(type, moduleId, selectedItems);
            // Close modal
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        }
    });
    updateSelectedCount();
}

function addSelectedVLR(type, moduleId, selectedItems) {
    if (type === 'module_content') {
        // Assign to module content (robust: always store id and content_id as VLR content id)
        const module = courseManagerState.modules.find(m => m.id === moduleId);
        if (module) {
            module.content = selectedItems.map((item, idx) => {
                const t = item.type || item.content_type || '';
                const contentId = Number(item.id);
                return {
                    ...item,
                    id: contentId,
                    content_id: contentId,
                    type: t,
                    content_type: t,
                    sort_order: idx
                };
            });
            renderModules();
        }
    } else if (type === 'prerequisite') {
        // Assign to prerequisites (robust: always store id and prerequisite_id as VLR content id)
        courseManagerState.prerequisites = selectedItems.map((item, idx) => {
            const t = item.type || item.content_type || '';
            const prereqId = Number(item.id);
            return {
                ...item,
                id: prereqId,
                prerequisite_id: prereqId,
                type: t,
                content_type: t,
                sort_order: idx
            };
        });
        renderPrerequisites();
    } else {
        // Post-requisite (type is the content_type)
        // Allow multiple per type, prevent duplicates
        courseManagerState.post_requisites = courseManagerState.post_requisites.filter(item => item.content_type !== type);
        selectedItems.forEach((item, idx) => {
            // Prevent duplicate content_id for this type
            if (!courseManagerState.post_requisites.some(pr => pr.content_id === item.id && pr.content_type === type)) {
                courseManagerState.post_requisites.push({
                    id: item.id,
                    title: item.title,
                    content_type: type,
                    content_id: item.id,
                    sort_order: courseManagerState.post_requisites.length,
                    is_required: true
                });
            }
        });
        renderPostRequisites();
    }
    showToast(`${selectedItems.length} item(s) added successfully`, 'success');
}

// Module ordering functions
window.moveModuleUp = function(index) {
    if (index > 0) {
        const temp = courseManagerState.modules[index];
        courseManagerState.modules[index] = courseManagerState.modules[index - 1];
        courseManagerState.modules[index - 1] = temp;
        renderModules();
    }
};
window.moveModuleDown = function(index) {
    if (index < courseManagerState.modules.length - 1) {
        const temp = courseManagerState.modules[index];
        courseManagerState.modules[index] = courseManagerState.modules[index + 1];
        courseManagerState.modules[index + 1] = temp;
        renderModules();
    }
};
// Module content ordering functions
window.moveModuleContentUp = function(moduleId, idx) {
    const module = courseManagerState.modules.find(m => m.id === moduleId);
    if (module && idx > 0) {
        const temp = module.content[idx];
        module.content[idx] = module.content[idx - 1];
        module.content[idx - 1] = temp;
        renderModules();
    }
};
window.moveModuleContentDown = function(moduleId, idx) {
    const module = courseManagerState.modules.find(m => m.id === moduleId);
    if (module && idx < module.content.length - 1) {
        const temp = module.content[idx];
        module.content[idx] = module.content[idx + 1];
        module.content[idx + 1] = temp;
        renderModules();
    }
};

// Export for global access
window.initializeCourseCreation = initializeCourseCreation;
console.log('[DEBUG] initializeCourseCreation function exported to window:', typeof window.initializeCourseCreation);

// Helper to reset courseManagerState
function resetCourseManagerState() {
    courseManagerState.modules = [];
    courseManagerState.prerequisites = [];
    courseManagerState.post_requisites = [];
    courseManagerState.learningObjectives = [];
    courseManagerState.tags = [];
    courseManagerState.currentTab = 0;
    courseManagerState.isEditMode = false;
    courseManagerState.courseId = null;
}

// Attach modal close handler to reset state
// Replace 'addCourseModal' with the actual modal ID if different

document.addEventListener('DOMContentLoaded', function() {
    var addCourseModal = document.getElementById('addCourseModal');
    if (addCourseModal) {
        addCourseModal.addEventListener('hidden.bs.modal', function() {
            resetCourseManagerState();
            // Optionally, re-render modules/prereqs/postreqs to clear UI
            renderModules();
            renderPrerequisites();
            renderPostRequisites();
            renderTags();
            renderLearningObjectives();
        });
    }
}); 