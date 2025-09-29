/**
 * Social Feed Management JavaScript
 * Handles all social feed functionality including posts, comments, reactions, and interactions
 */

class SocialFeed {
    constructor() {
        this.currentPage = 1;
        this.postsPerPage = 10;
        this.isLoading = false;
        this.currentSearch = '';
        this.currentFilters = {};
        this.mediaFiles = [];
        this.editMediaFiles = [];
        this.filesToDelete = [];
        this.init();
    }

    init() {
        // Initialize character counters
        this.initializeCharacterCounters();
        
        // Initialize drop zone
        this.initializeDropZone();
        
        // Initialize poll toggle
        this.initializePollToggle();
        
        // Initialize schedule toggle
        this.initializeScheduleToggle();
        
        // Initialize filters
        this.initializeFilters();
        
        // Bind events
        this.bindEvents();
        
        // Load initial posts
        this.loadPosts();
        
        // Initialize real-time validation
        this.initializeRealTimeValidation();
    }

    initializeCharacterCounters() {
        // Title character counter for create form
        const titleInput = document.getElementById('postTitle');
        const titleCharCounter = document.getElementById('titleCharCounter');
        
        titleInput.addEventListener('input', function() {
            const currentLength = this.value.length;
            titleCharCounter.textContent = `${currentLength}/150`;
            
            if (currentLength > 150) {
                titleCharCounter.style.color = '#dc3545';
            } else {
                titleCharCounter.style.color = '#6c757d';
            }
        });

        // Title character counter for edit form
        const editTitleInput = document.getElementById('editPostTitle');
        const editTitleCharCounter = document.getElementById('editTitleCharCounter');
        
        editTitleInput.addEventListener('input', function() {
            const currentLength = this.value.length;
            editTitleCharCounter.textContent = `${currentLength}/150`;
            
            if (currentLength > 150) {
                editTitleCharCounter.style.color = '#dc3545';
            } else {
                editTitleCharCounter.style.color = '#6c757d';
            }
        });

        // Content character counter for create form
        const contentInput = document.getElementById('postContent');
        const contentCounter = document.getElementById('charCounter');
        if (contentInput && contentCounter) {
            contentInput.addEventListener('input', () => {
                this.updateCharacterCount(contentInput, 'charCounter');
            });
        }

        // Content character counter for edit form
        const editContentInput = document.getElementById('editPostContent');
        const editContentCounter = document.getElementById('editCharCounter');
        if (editContentInput && editContentCounter) {
            editContentInput.addEventListener('input', () => {
                this.updateCharacterCount(editContentInput, 'editCharCounter');
            });
        }
    }

    bindEvents() {
        // Create post form submission
        document.getElementById('createPostForm').addEventListener('submit', (e) => {
            // Check if validation passed before processing
            if (typeof validateCreatePostForm === 'function') {
                const isValid = validateCreatePostForm();
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            }
            this.handleCreatePost(e);
        });

        // Create post type change
        document.getElementById('post_type').addEventListener('change', (e) => {
            this.handleCreatePostTypeChange(e.target.value);
        });

        // Tag functionality
        const tagInput = document.getElementById('tagInput');
        const tagDisplay = document.getElementById('tagDisplay');
        const tagList = document.getElementById('tagList');
        window.tags = [];

        // Function to add a tag
        function addTag(tagText) {
            if (tagText && !window.tags.includes(tagText)) {
                window.tags.push(tagText);
                updateTagDisplay();
                updateHiddenInput();
            }
        }

        // Function to remove a tag
        function removeTag(tagText) {
            window.tags = window.tags.filter(tag => tag !== tagText);
            updateTagDisplay();
            updateHiddenInput();
        }

        // Function to update tag display
        function updateTagDisplay() {
            tagDisplay.innerHTML = window.tags.map(tag => 
                `<span class="tag">${tag}<button type="button" class="remove-tag" data-tag="${tag}">×</button></span>`
            ).join('');
        }

        // Function to update hidden input with tags
        function updateHiddenInput() {
            tagList.value = window.tags.join(",");
        }

        // Listen for Enter Key in the Input Field
        if (tagInput) {
            tagInput.addEventListener("keypress", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    addTag(tagInput.value.trim());
                    tagInput.value = ""; // Clear input after adding
                }
            });

            // Listen for Clicks on Remove Buttons
            tagDisplay.addEventListener("click", function (event) {
                if (event.target.classList.contains("remove-tag")) {
                    const tagText = event.target.getAttribute("data-tag");
                    removeTag(tagText);
                }
            });

            // Remove Last Tag when Pressing Backspace in an Empty Input
            tagInput.addEventListener("keydown", function (event) {
                if (event.key === "Backspace" && tagInput.value === "" && window.tags.length > 0) {
                    removeTag(window.tags[window.tags.length - 1]); // Remove last tag
                }
            });
        }

        // Edit post form submission
        document.getElementById('editPostForm').addEventListener('submit', (e) => {
            // Check if validation passed before processing
            if (typeof validateEditPostForm === 'function') {
                const isValid = validateEditPostForm();
                if (!isValid) {
                    e.preventDefault();
                    return false;
                }
            }
            this.handleEditPost(e);
        });

        // Edit post type change
        document.getElementById('editPostType').addEventListener('change', (e) => {
            this.handleEditPostTypeChange(e.target.value);
        });

        // Edit poll toggle
        document.getElementById('editIncludePoll').addEventListener('change', (e) => {
            this.toggleEditPollSection(e.target.checked);
        });

        // Edit add poll option
        document.getElementById('editAddPollOption').addEventListener('click', () => {
            this.addEditPollOption();
        });

        // Edit tag input
        document.getElementById('editTagInput').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.addEditTag(e.target.value);
                e.target.value = '';
            }
        });

        // Edit tag removal
        document.getElementById('editTagDisplay').addEventListener('click', (e) => {
            if (e.target.classList.contains('remove-tag')) {
                const tag = e.target.dataset.tag;
                this.removeEditTag(tag);
            }
        });

        // Create modal show - reset form when opened
        $('#createPostModal').on('show.bs.modal', () => {
            this.resetCreatePostForm();
        });

        // Create modal close
        $('#createPostModal').on('hidden.bs.modal', () => {
            this.resetCreatePostForm();
        });

        // Edit modal close
        $('#editPostModal').on('hidden.bs.modal', () => {
            this.resetEditPostForm();
        });

        // Initialize filters and search functionality
        this.initializeFilters();

        // Report form
        $('#reportForm').on('submit', (e) => this.handleReportSubmit(e));

        // Character counter for post content
        $('#postContent').on('input', (e) => this.updateCharacterCount(e.target));

        // Add poll option
        $('#addPollOption').on('click', () => this.addPollOption());

        // Poll toggle
        $('#includePoll').on('change', (e) => this.togglePollSection(e.target.checked));

        // Schedule toggle
        $('#schedulePost').on('change', (e) => this.toggleScheduleInput(e.target.checked));
    }

    // Initialize filters and search functionality
    initializeFilters() {
        const searchInput = document.getElementById('searchInput');
        const postTypeFilter = document.getElementById('postTypeFilter');
        const visibilityFilter = document.getElementById('visibilityFilter');
        const statusFilter = document.getElementById('statusFilter');
        const pinnedFilter = document.getElementById('pinnedFilter');
        const dateRangeBtn = document.getElementById('dateRangeBtn');
        const applyDateFilter = document.getElementById('applyDateFilter');
        const clearDateFilter = document.getElementById('clearDateFilter');
        const clearAllFiltersBtn = document.getElementById('clearAllFiltersBtn');

        // Search with debounce
        if (searchInput) {
            const debouncedSearch = this.debounce((searchValue) => {
                this.currentSearch = searchValue;
                this.loadPosts(1);
            }, 500);

            searchInput.addEventListener('input', function() {
                debouncedSearch(this.value.trim());
            });
        }

        // Filter dropdowns
        if (postTypeFilter) {
            postTypeFilter.addEventListener('change', () => {
                this.currentFilters.post_type = postTypeFilter.value;
                this.loadPosts(1);
            });
        }

        if (visibilityFilter) {
            visibilityFilter.addEventListener('change', () => {
                this.currentFilters.visibility = visibilityFilter.value;
                this.loadPosts(1);
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', () => {
                this.currentFilters.status = statusFilter.value;
                this.loadPosts(1);
            });
        }

        if (pinnedFilter) {
            pinnedFilter.addEventListener('change', () => {
                this.currentFilters.is_pinned = pinnedFilter.value;
                this.loadPosts(1);
            });
        }

        // Date range toggle
        if (dateRangeBtn) {
            dateRangeBtn.addEventListener('click', () => {
                const dateRangeInputs = document.getElementById('dateRangeInputs');
                dateRangeInputs.classList.toggle('d-none');
            });
        }

        // Apply date filter
        if (applyDateFilter) {
            applyDateFilter.addEventListener('click', () => {
                const dateFrom = document.getElementById('dateFrom').value;
                const dateTo = document.getElementById('dateTo').value;

                if (dateFrom) this.currentFilters.date_from = dateFrom;
                if (dateTo) this.currentFilters.date_to = dateTo;

                this.loadPosts(1);
            });
        }

        // Clear date filter
        if (clearDateFilter) {
            clearDateFilter.addEventListener('click', () => {
                document.getElementById('dateFrom').value = '';
                document.getElementById('dateTo').value = '';
                delete this.currentFilters.date_from;
                delete this.currentFilters.date_to;
                this.loadPosts(1);
            });
        }

        // Clear all filters
        if (clearAllFiltersBtn) {
            clearAllFiltersBtn.addEventListener('click', () => {
                // Clear search
                if (searchInput) {
                    searchInput.value = '';
                    this.currentSearch = '';
                }

                // Clear filter dropdowns
                if (postTypeFilter) postTypeFilter.value = '';
                if (visibilityFilter) visibilityFilter.value = '';
                if (statusFilter) statusFilter.value = '';
                if (pinnedFilter) pinnedFilter.value = '';

                // Clear date inputs
                const dateFromInput = document.getElementById('dateFrom');
                const dateToInput = document.getElementById('dateTo');
                if (dateFromInput) dateFromInput.value = '';
                if (dateToInput) dateToInput.value = '';

                // Hide date range inputs
                const dateRangeInputs = document.getElementById('dateRangeInputs');
                if (dateRangeInputs) dateRangeInputs.classList.add('d-none');

                // Reset filter object
                this.currentFilters = {
                    post_type: '',
                    visibility: '',
                    status: '',
                    is_pinned: '',
                    date_from: '',
                    date_to: ''
                };

                // Reload posts
                this.loadPosts(1);
            });
        }
    }

    // Debounce function for search
    debounce(func, wait) {
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

    initializeDropZone() {
        // Initialize create form drop zone
        const dropZone = document.getElementById('mediaDropZone');
        const fileInput = document.getElementById('mediaFiles');

        if (dropZone && fileInput) {
            // Click to browse
            dropZone.addEventListener('click', () => fileInput.click());

            // Drag and drop events
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('dragover');
            });

            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('dragover');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('dragover');
                const files = Array.from(e.dataTransfer.files);
                this.handleFileSelection(files);
            });

            // File input change
            fileInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                this.handleFileSelection(files);
            });
        }

        // Initialize edit form drop zone
        const editDropZone = document.getElementById('editMediaDropZone');
        const editFileInput = document.getElementById('editMediaFiles');

        if (editDropZone && editFileInput) {
            // Click to browse
            editDropZone.addEventListener('click', () => editFileInput.click());

            // Drag and drop events
            editDropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                editDropZone.classList.add('dragover');
            });

            editDropZone.addEventListener('dragleave', () => {
                editDropZone.classList.remove('dragover');
            });

            editDropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                editDropZone.classList.remove('dragover');
                const files = Array.from(e.dataTransfer.files);
                this.handleEditFileSelection(files);
            });

            // File input change
            editFileInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                this.handleEditFileSelection(files);
            });
        }
    }

    handleFileSelection(files) {
        files.forEach(file => {
            if (this.validateFile(file)) {
                this.mediaFiles.push(file);
                this.displayMediaPreview(file);
            }
        });
    }

    handleEditFileSelection(files) {
        files.forEach(file => {
            if (this.validateFile(file)) {
                this.editMediaFiles.push(file);
                this.displayEditMediaPreview(file);
            }
        });
    }

    validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['image/', 'video/', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        if (file.size > maxSize) {
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('File too large. Maximum size is 10MB.', 'error');
            }
            return false;
        }

        const isValidType = allowedTypes.some(type => file.type.startsWith(type) || file.type === type);
        if (!isValidType) {
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Invalid file type. Please upload images, videos, or documents.', 'error');
            }
            return false;
        }

        return true;
    }

    displayMediaPreview(file) {
        const preview = document.getElementById('mediaPreview');
        const item = document.createElement('div');
        item.className = 'media-preview-item';
        item.dataset.filename = file.name;

        const reader = new FileReader();
        reader.onload = (e) => {
            if (file.type.startsWith('image/')) {
                item.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeMediaFile('${file.name}')">&times;</button>
                `;
            } else if (file.type.startsWith('video/')) {
                item.innerHTML = `
                    <video src="${e.target.result}" controls style="max-width: 100px; max-height: 100px;"></video>
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeMediaFile('${file.name}')">&times;</button>
                `;
            } else {
                item.innerHTML = `
                    <div style="width: 100px; height: 100px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class="fas fa-file-alt fa-2x text-muted"></i>
                    </div>
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeMediaFile('${file.name}')">&times;</button>
                `;
            }
        };
        reader.readAsDataURL(file);
        preview.appendChild(item);
    }

    displayEditMediaPreview(file) {
        const preview = document.getElementById('editMediaPreview');
        const item = document.createElement('div');
        item.className = 'media-preview-item';
        item.dataset.filename = file.name;

        const reader = new FileReader();
        reader.onload = (e) => {
            if (file.type.startsWith('image/')) {
                item.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeEditMediaFile('${file.name}')">&times;</button>
                `;
            } else if (file.type.startsWith('video/')) {
                item.innerHTML = `
                    <video src="${e.target.result}" controls style="max-width: 100px; max-height: 100px;"></video>
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeEditMediaFile('${file.name}')">&times;</button>
                `;
            } else {
                item.innerHTML = `
                    <div style="width: 100px; height: 100px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class="fas fa-file-alt fa-2x text-muted"></i>
                    </div>
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeEditMediaFile('${file.name}')">&times;</button>
                `;
            }
        };
        reader.readAsDataURL(file);
        preview.appendChild(item);
    }

    removeMediaFile(filename) {
        this.mediaFiles = this.mediaFiles.filter(file => file.name !== filename);
        const item = document.querySelector(`[data-filename="${filename}"]`);
        if (item) item.remove();
    }

    removeEditMediaFile(filename) {
        this.editMediaFiles = this.editMediaFiles.filter(file => file.name !== filename);
        const item = document.querySelector(`#editMediaPreview [data-filename="${filename}"]`);
        if (item) item.remove();
    }

    displayExistingMedia(media) {
        const preview = document.getElementById('editMediaPreview');
        preview.innerHTML = '';
        
        if (!media || media.length === 0) return;
        
        media.forEach(item => {
            const mediaItem = document.createElement('div');
            mediaItem.className = 'media-preview-item';
            mediaItem.dataset.filename = item.filename;
            mediaItem.dataset.existing = 'true';
            
            if (item.type.startsWith('image/')) {
                mediaItem.innerHTML = `
                    <img src="${item.url}" alt="${item.filename}">
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeExistingMedia('${item.filename}')">&times;</button>
                `;
            } else if (item.type.startsWith('video/')) {
                mediaItem.innerHTML = `
                    <video src="${item.url}" controls style="max-width: 100px; max-height: 100px;"></video>
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeExistingMedia('${item.filename}')">&times;</button>
                `;
            } else {
                mediaItem.innerHTML = `
                    <div style="width: 100px; height: 100px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                        <i class="fas fa-file-alt fa-2x text-muted"></i>
                    </div>
                    <button type="button" class="media-preview-remove" onclick="socialFeed.removeExistingMedia('${item.filename}')">&times;</button>
                `;
            }
            preview.appendChild(mediaItem);
        });
    }

    removeExistingMedia(filename) {
        const item = document.querySelector(`#editMediaPreview [data-filename="${filename}"][data-existing="true"]`);
        if (item) {
            item.remove();
            // Add to a list of files to be deleted
            if (!this.filesToDelete) this.filesToDelete = [];
            this.filesToDelete.push(filename);
        }
    }

    initializePollToggle() {
        $('#includePoll').on('change', (e) => {
            this.togglePollSection(e.target.checked);
        });
    }

    togglePollSection(show) {
        const pollSection = document.getElementById('pollSection');
        pollSection.style.display = show ? 'block' : 'none';
        
        if (show) {
            // Reset poll options
            document.getElementById('pollOptions').innerHTML = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="poll_options[]" placeholder="Option 1">
                </div>
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="poll_options[]" placeholder="Option 2">
                </div>
            `;
            
            // Re-initialize poll options validation for new elements
            // Add a small delay to ensure DOM is fully rendered
            setTimeout(() => {
                this.initializePollOptionsValidation();
                // Trigger validation immediately to show errors for empty fields
                this.validatePollOptions();
                
                // Also validate poll question
                const pollQuestion = document.getElementById('pollQuestion');
                if (pollQuestion && !pollQuestion.value.trim()) {
                    this.showFieldError('pollQuestion', 'Poll question is required.');
                }
            }, 100);
        } else {
            // Clear validation errors when poll section is hidden
            const pollQuestion = document.getElementById('pollQuestion');
            if (pollQuestion) {
                pollQuestion.classList.remove('is-invalid');
                const errorMessage = pollQuestion.parentNode.querySelector('.invalid-feedback');
                if (errorMessage) {
                    errorMessage.remove();
                }
            }
            
            const pollOptions = document.querySelectorAll('#pollSection input[name="poll_options[]"]');
            pollOptions.forEach(input => {
                input.classList.remove('is-invalid');
                const errorMessage = input.parentNode.querySelector('.invalid-feedback');
                if (errorMessage) {
                    errorMessage.remove();
                }
            });
        }
    }

    addPollOption() {
        const pollOptions = document.getElementById('pollOptions');
        const optionCount = pollOptions.children.length;
        
        if (optionCount >= 5) {
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Maximum 5 poll options allowed.', 'warning');
            }
            return;
        }

        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        newOption.innerHTML = `
            <input type="text" class="form-control" name="poll_options[]" placeholder="Option ${optionCount + 1}">
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        pollOptions.appendChild(newOption);
        
        // Add validation to the new poll option
        const newInput = newOption.querySelector('input[name="poll_options[]"]');
        if (newInput) {
            newInput.addEventListener('input', () => {
                this.validatePollOptions();
            });
            
            newInput.addEventListener('blur', () => {
                this.validatePollOptions();
            });
        }
    }

    initializeScheduleToggle() {
        $('#schedulePost').on('change', (e) => {
            this.toggleScheduleInput(e.target.checked);
        });
    }

    toggleScheduleInput(show) {
        const scheduleInput = document.getElementById('scheduleDateTime');
        scheduleInput.style.display = show ? 'block' : 'none';
        
        if (show) {
            // Set minimum date to current date/time
            const now = new Date();
            const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            scheduleInput.min = localDateTime;
        }
    }

    updateCharacterCount(textarea, counterId) {
        const maxLength = 2000;
        const currentLength = textarea.value.length;
        const remaining = maxLength - currentLength;
        
        // Update character count display
        const counter = document.getElementById(counterId);
        if (counter) {
            counter.textContent = `${currentLength}/${maxLength}`;
            counter.style.color = remaining < 100 ? '#dc3545' : '#6c757d';
        }
    }

    applyFilters() {
        this.currentFilters = {
            post_type: document.getElementById('postTypeFilter')?.value || '',
            visibility: document.getElementById('visibilityFilter')?.value || '',
            status: document.getElementById('statusFilter')?.value || '',
            is_pinned: document.getElementById('pinnedFilter')?.value || '',
            date_from: document.getElementById('dateFrom')?.value || '',
            date_to: document.getElementById('dateTo')?.value || ''
        };
        this.currentSearch = document.getElementById('searchInput')?.value || '';
        this.currentPage = 1;
        this.loadPosts();
    }

    performSearch() {
        this.currentSearch = document.getElementById('searchInput')?.value || '';
        this.currentPage = 1;
        this.loadPosts();
    }

    toggleViewMode(mode) {
        const container = document.getElementById('feedContainer');
        if (mode === 'gridView') {
            container.classList.add('grid-view');
        } else {
            container.classList.remove('grid-view');
        }
    }

    async loadPosts(page = 1) {
        if (this.isLoading) return;

        this.isLoading = true;
        this.currentPage = page;

        // Show loading spinner
        this.showLoading(true);

        // Build query parameters
        const params = new URLSearchParams({
            page: page,
            limit: this.postsPerPage,
            search: this.currentSearch,
            ...this.currentFilters
        });

        try {
            const response = await fetch(`index.php?controller=SocialFeedController&action=list&${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('[SocialFeed] Failed to parse JSON response:', parseError);
                console.error('[SocialFeed] Raw response:', responseText);
                this.showError('Server returned an invalid response. Please try again.');
                return;
            }
            
            if (data.success) {
                this.renderPosts(data.posts);
                this.renderPagination(data.pagination);
                this.updateResultsInfo(data.pagination);
            } else {
                console.error('[SocialFeed] Error loading posts:', data.error || data.message || 'Unknown error');
                this.showError(data.error || data.message || 'Failed to load posts. Please try again.');
            }
        } catch (error) {
            console.error('[SocialFeed] Network error:', error);
            this.showError('Network error. Please check your connection and try again.');
        } finally {
            this.isLoading = false;
            this.showLoading(false);
        }
    }

    renderPosts(posts) {
        const grid = document.getElementById('socialFeedGrid');
        const noResults = document.getElementById('noResults');

        if (!posts || posts.length === 0) {
            grid.innerHTML = '';
            if (noResults) {
                noResults.style.display = 'block';
            }
            return;
        }

        if (noResults) {
            noResults.style.display = 'none';
        }

        // Wrap each card in a Bootstrap column for 3 cards per row
        const postsHTML = posts.map(post => {
            return `<div class="col-md-6 col-lg-4 mb-4">${this.renderPostCard(post)}</div>`;
        }).join('');
        grid.innerHTML = `<div class="row">${postsHTML}</div>`;

        // Add event listeners to action buttons
        this.addActionListeners();
    }

    renderPostCard(post) {
        // Media files are not displayed on the card - removed mediaHTML
        // Don't show poll details on main feed page - only show poll indicator
        const pollHTML = post.poll ? this.renderPollIndicator(post.poll) : '';
        const badgesHTML = this.renderPostBadges(post);
        const tagsHTML = this.renderPostTags(post.tags);
        const reportedClass = post.report_count > 0 ? ' reported' : '';
        
        // Get pinned badge
        const pinnedBadge = post.is_pinned 
            ? '<span class="badge bg-warning text-dark ms-2"><i class="fas fa-thumbtack me-1"></i>Pinned</span>'
            : '<span class="badge bg-secondary ms-2"><i class="fas fa-thumbtack me-1"></i>Unpinned</span>';
        
        return `
            <div class="card h-100 post-card${reportedClass}" data-post-id="${post.id}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-newspaper text-primary me-2"></i>
                        <span class="badge bg-primary">${post.post_type.toUpperCase()}</span>
                    </div>
                    <div class="d-flex gap-1">
                        ${this.getStatusBadge(post.status)}
                        ${this.getVisibilityBadge(post.visibility)}
                        ${pinnedBadge}
                    </div>
                </div>
                <div class="card-body">
                    ${post.title ? `<h6 class="card-title">${this.escapeHtml(post.title)}</h6>` : ''}
                    <p class="card-text text-muted small">
                        ${this.truncateText(this.stripHtml(post.body), 100)}
                    </p>
                    ${badgesHTML}
                    ${pollHTML}
                    ${tagsHTML}

                    <!-- Social Interaction Buttons (Read-only) -->
                    <div class="post-actions mt-3">
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-sm btn-outline-primary" disabled title="Reactions available on /my-social-feed">
                                <i class="fas fa-thumbs-up me-1"></i>
                                <span class="reaction-count">${post.reactions.like || 0}</span>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" disabled title="Reactions available on /my-social-feed">
                                <i class="fas fa-heart me-1"></i>
                                <span class="reaction-count">${post.reactions.love || 0}</span>
                            </button>
                            <button class="btn btn-sm btn-outline-info" disabled title="Comments available on /my-social-feed">
                                <i class="fas fa-comment me-1"></i>
                                <span class="comment-count">${post.comment_count || 0}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary view-details-btn"
                                    data-post-id="${post.id}"
                                    onclick="socialFeed.viewPostDetails(${post.id})"
                                    title="View Details">
                                <i class="fas fa-eye me-1"></i>Details
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="socialFeed.sharePost(${post.id})">
                                <i class="fas fa-share me-1"></i>Share
                            </button>
                        </div>
                    </div>

                    <div class="post-meta mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between text-muted small">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user me-2"></i>
                                <span>${this.escapeHtml(post.author_name || post.author?.name || 'Unknown')}</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar me-2"></i>
                                <span>${this.formatDate(post.created_at)}</span>
                            </div>
                        </div>

                        ${post.view_count ? `
                        <div class="text-muted small mt-2">
                            <i class="fas fa-eye me-1"></i>
                            ${post.view_count} views
                        </div>
                        ` : ''}
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        ${post.can_edit ? `
                        <button type="button" class="btn btn-sm theme-btn-secondary edit-post-btn"
                                data-post-id="${post.id}"
                                data-action-permission="social_feed:edit"
                                onclick="socialFeed.editPost(${post.id})"
                                title="Edit Post">
                            <i class="fas fa-edit"></i>
                        </button>
                        ` : ''}
                        <button type="button" class="btn btn-sm btn-outline-info view-stats-btn"
                                data-post-id="${post.id}"
                                onclick="socialFeed.viewPostStatistics(${post.id})"
                                title="View Statistics">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning pin-post-btn"
                                data-post-id="${post.id}"
                                onclick="socialFeed.togglePinPost(${post.id}, ${post.is_pinned ? 'false' : 'true'})"
                                title="${post.is_pinned ? 'Unpin' : 'Pin'} Post">
                            <i class="fas fa-thumbtack"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger report-post-btn"
                                data-post-id="${post.id}"
                                onclick="socialFeed.reportPost(${post.id})"
                                title="Report Post">
                            <i class="fas fa-flag"></i>
                        </button>
                        ${this.getStatusActionButton(post)}
                        ${post.can_delete ? `
                        <button type="button" class="btn btn-sm theme-btn-danger delete-post-btn"
                                data-post-id="${post.id}"
                                data-post-title="${this.escapeHtml(post.title || 'Post')}"
                                data-action-permission="social_feed:delete"
                                onclick="socialFeed.confirmDeletePost(${post.id})"
                                title="Delete Post">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    renderPostMedia(media) {
        if (!media || media.length === 0) return '';
        
        // If only one media file, display it normally
        if (media.length === 1) {
            const item = media[0];
            if (item.type.startsWith('image/')) {
                return `<div class="post-media"><img src="${item.url}" alt="Post media" class="img-fluid"></div>`;
            } else if (item.type.startsWith('video/')) {
                return `<div class="post-media"><video src="${item.url}" controls class="img-fluid"></video></div>`;
            } else {
                return `<div class="post-media"><a href="${item.url}" target="_blank">
                    <i class="fas fa-file-download"></i>${item.filename}
                </a></div>`;
            }
        }
        
        // If multiple media files, use grid layout
        const mediaItems = media.map(item => {
            if (item.type.startsWith('image/')) {
                return `<img src="${item.url}" alt="Post media" class="img-fluid">`;
            } else if (item.type.startsWith('video/')) {
                return `<video src="${item.url}" controls class="img-fluid"></video>`;
            } else {
                return `<a href="${item.url}" target="_blank">
                    <i class="fas fa-file-download"></i>${item.filename}
                </a>`;
            }
        }).join('');
        
        return `<div class="post-media"><div class="media-grid">${mediaItems}</div></div>`;
    }

    renderPoll(poll) {
        const totalVotes = poll.options.reduce((sum, option) => sum + option.votes, 0);
        const optionsHTML = poll.options.map(option => {
            const percentage = totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0;
            return `
                <div class="poll-option" onclick="socialFeed.votePoll(${poll.id}, ${option.id})">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>${option.text}</span>
                        <span class="text-muted">${option.votes} votes (${percentage}%)</span>
                    </div>
                    <div class="poll-progress mt-1">
                        <div class="poll-progress-bar" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        }).join('');
        
        return `
            <div class="poll-section mt-3">
                <h6>${poll.question}</h6>
                ${optionsHTML}
                <small class="text-muted">Total votes: ${totalVotes}</small>
            </div>
        `;
    }

    renderPostBadges(post) {
        let badges = '';
        if (post.report_count > 0) {
            badges += '<span class="badge bg-danger ms-2"><i class="fas fa-flag me-1"></i>Reported</span>';
        }
        return badges;
    }

    renderPostTags(tags) {
        if (!tags || tags.trim() === '') return '';
        
        const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
        if (tagArray.length === 0) return '';
        
        const tagsHTML = tagArray.map(tag => 
            `<span class="tag-display" onclick="socialFeed.searchByTag('${tag}')">#${tag}</span>`
        ).join('');
        
        return `<div class="post-tags mt-2">${tagsHTML}</div>`;
    }

    formatPostContent(content) {
        // Convert mentions and hashtags to styled spans
        return content
            .replace(/@(\w+)/g, '<span class="mention">@$1</span>')
            .replace(/#(\w+)/g, '<span class="hashtag">#$1</span>')
            .replace(/\n/g, '<br>');
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInHours = (now - date) / (1000 * 60 * 60);
        
        if (diffInHours < 1) {
            return 'Just now';
        } else if (diffInHours < 24) {
            return `${Math.floor(diffInHours)}h ago`;
        } else if (diffInHours < 168) {
            return `${Math.floor(diffInHours / 24)}d ago`;
        } else {
            return date.toLocaleDateString();
        }
    }

    renderPagination(pagination) {
        const container = document.getElementById('pagination');
        if (!pagination || pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHTML = '';
        
        // Previous button
        paginationHTML += `
            <li class="page-item ${pagination.current_page <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="socialFeed.goToPage(${pagination.current_page - 1})">Previous</a>
            </li>
        `;

        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === 1 || i === pagination.total_pages || 
                (i >= pagination.current_page - 2 && i <= pagination.current_page + 2)) {
                paginationHTML += `
                    <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="socialFeed.goToPage(${i})">${i}</a>
                    </li>
                `;
            } else if (i === pagination.current_page - 3 || i === pagination.current_page + 3) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        // Next button
        paginationHTML += `
            <li class="page-item ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="socialFeed.goToPage(${pagination.current_page + 1})">Next</a>
            </li>
        `;

        container.innerHTML = paginationHTML;
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadPosts(page);
    }

    async handleCreatePost(e) {
        e.preventDefault();
        
        // Clear previous validation errors
        this.clearValidationErrors();
        
        // Get form values
        const title = document.getElementById('postTitle').value.trim();
        const content = document.getElementById('postContent').value.trim();
        const postType = document.getElementById('post_type').value;
        const visibility = document.getElementById('visibility').value;
        const includePoll = document.getElementById('includePoll').checked;
        const schedulePost = document.getElementById('schedulePost').checked;
        const scheduleDateTime = document.getElementById('scheduleDateTime');
        
        // Custom validation for poll fields
        if (includePoll) {
            const pollQuestion = document.getElementById('pollQuestion').value.trim();
            if (!pollQuestion) {
                this.showFieldError('pollQuestion', 'Poll question is required.');
                return;
            }
            
            const pollOptions = document.querySelectorAll('#pollSection input[name="poll_options[]"]');
            const validOptions = Array.from(pollOptions).filter(input => input.value.trim() !== '');
            if (validOptions.length < 2) {
                // Highlight all poll option fields as invalid
                pollOptions.forEach(input => {
                    input.classList.add('is-invalid');
                    // Remove existing error message
                    const existingError = input.parentNode.querySelector('.invalid-feedback');
                    if (existingError) {
                        existingError.remove();
                    }
                    // Add error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'At least 2 poll options are required.';
                    input.parentNode.appendChild(errorDiv);
                });
                return;
            }
        }
        
        // Validate tags
        const tags = document.getElementById('tagList').value;
        if (tags) {
            const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
            if (tagArray.length > 10) {
                this.showFieldError('tagInput', 'Maximum 10 tags allowed.');
                return;
            }
            tagArray.forEach(tag => {
                if (tag.length > 50) {
                    this.showFieldError('tagInput', 'Individual tags cannot exceed 50 characters.');
                    return;
                }
            });
        }

        // --- MEDIA REQUIRED VALIDATION ---
        if (postType === 'media') {
            if (!this.mediaFiles || this.mediaFiles.length === 0) {
                this.showFieldError('mediaFiles', 'Please upload at least one media file.');
                return;
            }
        }
        // --- END MEDIA VALIDATION ---
        
        const formData = new FormData();
        const form = e.target;
        
        // Add form fields
        formData.append('title', title);
        formData.append('content', content);
        formData.append('post_type', postType);
        formData.append('visibility', visibility);
        formData.append('tags', tags);
        formData.append('pin_post', form.pin_post.checked ? '1' : '0');
        formData.append('include_poll', includePoll ? '1' : '0');
        
        // Add custom field data for group specific posts
        if (visibility === 'group_specific') {
            const customFieldId = document.getElementById('customFieldId').value;
            const customFieldValue = document.getElementById('customFieldValue').value;
            formData.append('custom_field_id', customFieldId);
            formData.append('custom_field_value', customFieldValue);
        }

        // Add poll data if poll is included
        if (includePoll) {
            const pollQuestion = document.getElementById('pollQuestion').value.trim();
            if (pollQuestion) {
                formData.append('poll_question', pollQuestion);
            }
            
            const pollOptions = document.querySelectorAll('#pollSection input[name="poll_options[]"]');
            pollOptions.forEach(input => {
                if (input.value.trim() !== '') {
                    formData.append('poll_options[]', input.value.trim());
                }
            });
        }

        // Add scheduled date if scheduling is enabled
        if (schedulePost && scheduleDateTime.value) {
            formData.append('scheduled_at', scheduleDateTime.value);
        }

        // Add media files
        this.mediaFiles.forEach((file, index) => {
            formData.append('media[]', file);
        });

        // Add link data if post type is 'link'
        if (postType === 'link') {
            const linkUrl = document.getElementById('linkUrl').value.trim();
            const linkTitle = document.getElementById('linkTitle').value.trim();
            const linkDescription = document.getElementById('linkDescription').value.trim();
            
            if (linkUrl) {
                formData.append('link_url', linkUrl);
                formData.append('link_title', linkTitle);
                formData.append('link_description', linkDescription);
            }
        }

        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=create', {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse JSON response:', parseError);
                console.error('Raw response:', responseText);
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Server returned an invalid response. Please try again.', 'error');
                }
                return;
            }
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post created successfully!', 'success');
                }
                $('#createPostModal').modal('hide');
                this.resetCreatePostForm();
                this.loadPosts(); // Reload posts to show new post
            } else {
                // Handle validation errors without showing toast messages
                // Server-side validation is kept but not displayed as toasts
                
                // Only show toast for non-validation errors (like server errors)
                if (data.message && !data.message.includes('required') && !data.message.includes('Invalid')) {
                    if (typeof showSimpleToast === 'function') {
                        showSimpleToast(data.message, 'error');
                    }
                }
            }
        } catch (error) {
            console.error('Error creating post:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to create post. Please try again.', 'error');
            }
        }
    }

    resetCreatePostForm() {
        document.getElementById('createPostForm').reset();
        document.getElementById('createMediaSection').style.display = 'none';
        document.getElementById('pollSection').style.display = 'none';
        document.getElementById('linkSection').style.display = 'none';
        document.getElementById('customFieldSelection').style.display = 'none';
        document.getElementById('mediaPreview').innerHTML = '';
        document.getElementById('tagDisplay').innerHTML = '';
        document.getElementById('tagList').value = '';
        document.getElementById('charCounter').textContent = '0/2000';
        document.getElementById('titleCharCounter').textContent = '0/150';
        this.mediaFiles = []; // Clear media files array
        this.clearValidationErrors('createPostForm');
    }

    async toggleReaction(postId, reactionType) {
        // Reaction functionality disabled on main social feed page
        // Users can react to posts on /my-social-feed page only
        if (typeof showSimpleToast === 'function') {
            showSimpleToast('Reactions are available on the My Social Feed page', 'info');
        }
    }

    async toggleComments(postId) {
        // Comment functionality disabled on main social feed page
        // Users can comment on posts on /my-social-feed page only
        if (typeof showSimpleToast === 'function') {
            showSimpleToast('Comments are available on the My Social Feed page', 'info');
        }
    }

    renderComments(postId, comments) {
        const postCard = document.querySelector(`[data-post-id="${postId}"]`);
        let commentsSection = postCard.querySelector('.comments-section');
        
        if (!commentsSection) {
            commentsSection = document.createElement('div');
            commentsSection.className = 'comments-section border-top p-3';
            postCard.appendChild(commentsSection);
        }
        
        const commentsHTML = comments.map(comment => `
            <div class="comment-item">
                <div class="d-flex">
                    <img src="${comment.author.avatar || 'public/images/UYSlogo.png'}" 
                         alt="${comment.author.name}" class="rounded-circle me-2" width="32" height="32">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <strong>${comment.author.name}</strong>
                            <small class="text-muted">${this.formatDate(comment.created_at)}</small>
                        </div>
                        <p class="mb-1">${this.formatPostContent(comment.content)}</p>
                    </div>
                </div>
            </div>
        `).join('');
        
        commentsSection.innerHTML = `
            <h6>Comments (${comments.length})</h6>
            <div class="comment-list mb-3">
                ${commentsHTML}
            </div>
            <div class="add-comment">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Add a comment..." 
                           id="commentInput_${postId}">
                    <button class="btn btn-outline-primary" type="button" 
                            onclick="socialFeed.addComment(${postId})">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        `;
    }

    async addComment(postId) {
        // Comment functionality disabled on main social feed page
        // Users can comment on posts on /my-social-feed page only
        if (typeof showSimpleToast === 'function') {
            showSimpleToast('Comments are available on the My Social Feed page', 'info');
        }
    }

    async votePoll(pollId, optionId) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=pollVote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    poll_id: pollId,
                    option_id: optionId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Vote recorded successfully!', 'success');
                }
                this.loadPosts(); // Reload posts to update poll results
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to record vote', 'error');
                }
            }
        } catch (error) {
            console.error('Error voting on poll:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to record vote. Please try again.', 'error');
            }
        }
    }

    async deletePost(postId) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post deleted successfully!', 'success');
                }
                this.loadPosts(); // Reload posts
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to delete post', 'error');
                }
            }
        } catch (error) {
            console.error('Error deleting post:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to delete post. Please try again.', 'error');
            }
        }
    }

    confirmDeletePost(postId) {
        const postTitle = document.querySelector(`[data-post-id="${postId}"] .delete-post-btn`).dataset.postTitle;
        if (confirm(`Are you sure you want to delete "${postTitle}"?`)) {
            this.deletePost(postId);
        }
    }

    reportPost(postId) {
        $('#reportModal').modal('show');
        document.getElementById('reportPostId').value = postId;
    }

    async handleReportSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=report', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post reported successfully!', 'success');
                }
                $('#reportModal').modal('hide');
                document.getElementById('reportForm').reset();
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to report post', 'error');
                }
            }
        } catch (error) {
            console.error('Error reporting post:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to report post. Please try again.', 'error');
            }
        }
    }

    async viewPostDetails(postId) {
        try {
            const response = await fetch(`index.php?controller=SocialFeedController&action=show&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populatePostDetailModal(data.post);
                $('#postDetailModal').modal('show');
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to load post details', 'error');
                }
            }
        } catch (error) {
            console.error('Error loading post details:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to load post details. Please try again.', 'error');
            }
        }
    }

    populatePostDetailModal(post) {
        const modal = document.getElementById('postDetailModal');
        
        if (!modal) {
            console.error('Post detail modal not found');
            return;
        }

        // Helper function to safely set text content
        const setTextContent = (selector, text) => {
            const element = modal.querySelector(selector);
            if (element) {
                element.textContent = text || '';
            }
        };

        // Helper function to safely set innerHTML
        const setInnerHTML = (selector, html) => {
            const element = modal.querySelector(selector);
            if (element) {
                element.innerHTML = html || '';
            }
        };

        // Set modal title
        setTextContent('.modal-title', post.title || 'Post Details');
        
        // Set author name (try multiple sources)
        const authorName = post.author_name || post.author?.name || 'Unknown';
        setTextContent('.post-author', authorName);
        
        // Set date
        setTextContent('.post-date', this.formatDate(post.created_at));
        
        // Set content
        setInnerHTML('.post-content', this.formatPostContent(post.body));
        
        // Status and visibility badges
        const statusBadge = this.getStatusBadge(post.status);
        const visibilityBadge = this.getVisibilityBadge(post.visibility);
        setInnerHTML('.post-status', `${statusBadge} ${visibilityBadge}`);
        
        // Post type
        setTextContent('.post-type', post.post_type ? post.post_type.toUpperCase() : 'TEXT');
        
        // Tags
        const tagsContainer = modal.querySelector('.post-tags');
        if (tagsContainer && post.tags) {
            const tags = post.tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
            tagsContainer.innerHTML = tags.map(tag => `<span class="badge bg-secondary me-1">#${tag}</span>`).join('');
        } else if (tagsContainer) {
            tagsContainer.innerHTML = '<span class="text-muted">No tags</span>';
        }
        
        // Statistics
        setTextContent('.post-views', post.view_count || 0);
        
        // Handle reactions count
        let reactionCount = 0;
        if (post.reactions && typeof post.reactions === 'object') {
            reactionCount = Object.values(post.reactions).reduce((sum, count) => sum + (count || 0), 0);
        }
        setTextContent('.post-reactions', reactionCount);
        
        setTextContent('.post-comments', post.comment_count || 0);
        
        // Media files
        const mediaContainer = modal.querySelector('.post-media');
        if (mediaContainer && post.media && post.media.length > 0) {
            const mediaHTML = post.media.map(item => {
                if (item.type && item.type.startsWith('image/')) {
                    return `<img src="${item.url}" alt="Post media" class="post-detail-media mb-2" style="max-width: 300px; max-height: 200px; width: auto; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">`;
                } else if (item.type && item.type.startsWith('video/')) {
                    return `<video src="${item.url}" controls class="mb-2" style="max-width: 300px; max-height: 200px; width: auto; height: auto; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"></video>`;
                } else {
                    return `<a href="${item.url}" class="btn btn-outline-primary mb-2" target="_blank">
                        <i class="fas fa-file-download me-1"></i>${item.filename || 'Download File'}
                    </a>`;
                }
            }).join('');
            mediaContainer.innerHTML = mediaHTML;
        } else if (mediaContainer) {
            mediaContainer.innerHTML = '<span class="text-muted">No media files</span>';
        }
        
        // Poll data
        const pollContainer = modal.querySelector('.post-poll');
        if (pollContainer && post.poll) {
            const totalVotes = post.poll.options.reduce((sum, option) => sum + (option.votes || 0), 0);
            const pollHTML = `
                <h6>${post.poll.question}</h6>
                ${post.poll.options.map(option => {
                    const percentage = totalVotes > 0 ? Math.round(((option.votes || 0) / totalVotes) * 100) : 0;
                    return `
                        <div class="poll-option mb-2">
                            <div class="d-flex justify-content-between">
                                <span>${option.text}</span>
                                <span class="text-muted">${option.votes || 0} votes (${percentage}%)</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    `;
                }).join('')}
                <small class="text-muted">Total votes: ${totalVotes}</small>
            `;
            pollContainer.innerHTML = pollHTML;
        } else if (pollContainer) {
            pollContainer.innerHTML = '<span class="text-muted">No poll</span>';
        }
        
        // Reports
        const reportsContainer = modal.querySelector('.post-reports');
        if (reportsContainer && post.report_count > 0) {
            reportsContainer.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-flag me-1"></i>
                    This post has been reported ${post.report_count} time(s)
                </div>
            `;
        } else if (reportsContainer) {
            reportsContainer.innerHTML = '<span class="text-muted">No reports</span>';
        }
    }

    formatReportReason(reason) {
        const reasons = {
            'spam': 'Spam',
            'inappropriate': 'Inappropriate Content',
            'harassment': 'Harassment',
            'fake_news': 'Fake News',
            'other': 'Other'
        };
        return reasons[$reason] || reason;
    }

    sharePost(postId) {
        // For now, just copy the post URL to clipboard
        const postUrl = `${window.location.origin}/feed/${postId}`;
        navigator.clipboard.writeText(postUrl).then(() => {
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Post URL copied to clipboard!', 'success');
            }
        }).catch(() => {
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to copy URL', 'error');
            }
        });
    }

    searchByTag(tag) {
        document.getElementById('searchInput').value = `#${tag}`;
        this.currentSearch = `#${tag}`;
        this.loadPosts(1);
    }

    showLoading(show) {
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner) {
            loadingSpinner.style.display = show ? 'block' : 'none';
        }
    }

    hideLoading() {
        this.showLoading(false);
    }

    updateResultsInfo(pagination) {
        const resultsInfo = document.getElementById('resultsInfo');
        if (resultsInfo && pagination) {
            const start = (pagination.current_page - 1) * pagination.per_page + 1;
            const end = Math.min(pagination.current_page * pagination.per_page, pagination.total_posts);
            resultsInfo.textContent = `Showing ${start}-${end} of ${pagination.total_posts} posts`;
        }
    }

    addActionListeners() {
        // Add any additional event listeners for dynamically created elements
    }

    editPost(postId) {
        this.loadPostForEdit(postId);
        $('#editPostModal').modal('show');
    }

    async loadPostForEdit(postId) {
        try {
            const response = await fetch(`index.php?controller=SocialFeedController&action=show&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateEditForm(data.post);
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to load post for editing', 'error');
                }
            }
        } catch (error) {
            console.error('Error loading post for edit:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to load post for editing. Please try again.', 'error');
            }
        }
    }

    populateEditForm(post) {
        document.getElementById('editPostId').value = post.id;
        document.getElementById('editPostTitle').value = post.title || '';
        document.getElementById('editPostContent').value = post.body;
        document.getElementById('editPostType').value = post.post_type;
        document.getElementById('editVisibility').value = post.visibility;
        document.getElementById('editPinPost').checked = post.is_pinned;
        
        // Handle custom fields for group specific posts
        if (post.visibility === 'group_specific') {
            // Show custom field selection
            const customFieldSelection = document.getElementById('editCustomFieldSelection');
            customFieldSelection.style.display = 'block';
            
            // Set custom field values if they exist
            if (post.custom_field_id) {
                document.getElementById('editCustomFieldId').value = post.custom_field_id;
                // Trigger change event to load custom field values
                const customFieldIdSelect = document.getElementById('editCustomFieldId');
                const event = new Event('change', { bubbles: true });
                customFieldIdSelect.dispatchEvent(event);
                
                // Set custom field value after a short delay to allow values to load
                setTimeout(() => {
                    if (post.custom_field_value) {
                        document.getElementById('editCustomFieldValue').value = post.custom_field_value;
                    }
                }, 100);
            }
        } else {
            // Hide custom field selection for non-group specific posts
            const customFieldSelection = document.getElementById('editCustomFieldSelection');
            customFieldSelection.style.display = 'none';
        }
        
        // Show/hide media section based on post type
        const mediaSection = document.getElementById('editMediaSection');
        if (post.post_type === 'media') {
            mediaSection.style.display = 'block';
        } else {
            mediaSection.style.display = 'none';
        }
        
        // Show/hide link section based on post type
        const linkSection = document.getElementById('editLinkSection');
        if (post.post_type === 'link') {
            linkSection.style.display = 'block';
            // Populate link data if available
            if (post.link_preview) {
                try {
                    const linkData = JSON.parse(post.link_preview);
                    document.getElementById('editLinkUrl').value = linkData.url || '';
                    document.getElementById('editLinkTitle').value = linkData.title || '';
                    document.getElementById('editLinkDescription').value = linkData.description || '';
                } catch (e) {
                    console.error('Error parsing link preview data:', e);
                }
            }
        } else {
            linkSection.style.display = 'none';
        }
        
        // Set tags
        if (post.tags) {
            window.editTags = post.tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
            this.updateEditTagDisplay();
        } else {
            window.editTags = [];
            this.updateEditTagDisplay();
        }
        
        // Set poll data if exists
        if (post.poll) {
            document.getElementById('editIncludePoll').checked = true;
            this.toggleEditPollSection(true);
            this.populateEditPollData(post.poll);
        } else {
            document.getElementById('editIncludePoll').checked = false;
            this.toggleEditPollSection(false);
        }
        
        // Show existing media files
        this.displayExistingMedia(post.media);
        
        // Update character counters
        this.updateCharacterCount(document.getElementById('editPostTitle'), 'editTitleCharCounter');
        this.updateCharacterCount(document.getElementById('editPostContent'), 'editCharCounter');
    }

    updateEditTagDisplay() {
        const tagDisplay = document.getElementById('editTagDisplay');
        tagDisplay.innerHTML = window.editTags.map(tag => 
            `<span class="tag">${tag}<button type="button" class="remove-tag" data-tag="${tag}">×</button></span>`
        ).join('');
    }

    toggleEditPollSection(show) {
        const editPollSection = document.getElementById('editPollSection');
        editPollSection.style.display = show ? 'block' : 'none';
    }

    populateEditPollData(poll) {
        // Set poll question
        if (poll.question) {
            document.getElementById('editPollQuestion').value = poll.question;
        }
        
        const pollContainer = document.getElementById('editPollOptions');
        
        // Handle both old format (array of strings) and new format (array of objects)
        const options = poll.options.map(option => {
            if (typeof option === 'string') {
                return option;
            } else if (option && option.text) {
                return option.text;
            }
            return '';
        });
        
        pollContainer.innerHTML = options.map((option, index) => `
            <div class="input-group mb-2">
                <input type="text" class="form-control" name="edit_poll_options[]" value="${this.escapeHtml(option)}" placeholder="Poll option ${index + 1}">
                <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        
        // Set the allow multiple votes checkbox
        if (poll.allow_multiple_votes !== undefined) {
            document.getElementById('editAllowMultipleVotes').checked = poll.allow_multiple_votes == 1;
        }
    }

    async handleEditPost(e) {
        e.preventDefault();
        
        // Clear previous validation errors
        this.clearValidationErrors();
        
        // Get form values
        const postId = document.getElementById('editPostId').value;
        const title = document.getElementById('editPostTitle').value.trim();
        const content = document.getElementById('editPostContent').value.trim();
        const postType = document.getElementById('editPostType').value;
        const visibility = document.getElementById('editVisibility').value;
        const includePoll = document.getElementById('editIncludePoll').checked;
        const pinPost = document.getElementById('editPinPost').checked;
        
        // Custom validation for poll fields
        if (includePoll) {
            const pollQuestion = document.getElementById('editPollQuestion').value.trim();
            if (!pollQuestion) {
                this.showFieldError('editPollQuestion', 'Poll question is required.');
                return;
            }
            
            const pollOptions = document.querySelectorAll('#editPollSection input[name="edit_poll_options[]"]');
            const validOptions = Array.from(pollOptions).filter(input => input.value.trim() !== '');
            if (validOptions.length < 2) {
                // Highlight all poll option fields as invalid
                pollOptions.forEach(input => {
                    input.classList.add('is-invalid');
                    // Remove existing error message
                    const existingError = input.parentNode.querySelector('.invalid-feedback');
                    if (existingError) {
                        existingError.remove();
                    }
                    // Add error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'At least 2 poll options are required.';
                    input.parentNode.appendChild(errorDiv);
                });
                return;
            }
        }
        
        // Validate tags
        const tags = window.editTags.join(',');
        if (tags) {
            const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
            if (tagArray.length > 10) {
                this.showFieldError('editTagInput', 'Maximum 10 tags allowed.');
                return;
            }
            
            tagArray.forEach(tag => {
                if (tag.length > 50) {
                    this.showFieldError('editTagInput', 'Individual tags cannot exceed 50 characters.');
                    return;
                }
            });
        }
        
        const formData = new FormData();
        formData.append('post_id', postId);
        formData.append('title', title);
        formData.append('content', content);
        formData.append('post_type', postType);
        formData.append('visibility', visibility);
        formData.append('tags', tags);
        formData.append('pin_post', pinPost ? '1' : '0');
        formData.append('include_poll', includePoll ? '1' : '0');
        
        // Add custom field data for group specific posts
        if (visibility === 'group_specific') {
            const customFieldId = document.getElementById('editCustomFieldId').value;
            const customFieldValue = document.getElementById('editCustomFieldValue').value;
            formData.append('custom_field_id', customFieldId);
            formData.append('custom_field_value', customFieldValue);
        }

        // Add poll data if poll is included
        if (includePoll) {
            const pollQuestion = document.getElementById('editPollQuestion').value.trim();
            if (pollQuestion) {
                formData.append('poll_question', pollQuestion);
            }
            
            const pollOptions = document.querySelectorAll('#editPollSection input[name="edit_poll_options[]"]');
            pollOptions.forEach(input => {
                if (input.value.trim() !== '') {
                    formData.append('poll_options[]', input.value.trim());
                }
            });
        }

        // Add edit media files
        this.editMediaFiles.forEach(file => {
            formData.append('media[]', file);
        });

        // Add files to delete
        if (this.filesToDelete && this.filesToDelete.length > 0) {
            this.filesToDelete.forEach(filename => {
                formData.append('files_to_delete[]', filename);
            });
        }

        // Add link data if post type is 'link'
        if (postType === 'link') {
            const linkUrl = document.getElementById('editLinkUrl').value.trim();
            const linkTitle = document.getElementById('editLinkTitle').value.trim();
            const linkDescription = document.getElementById('editLinkDescription').value.trim();
            
            if (linkUrl) {
                formData.append('link_url', linkUrl);
                formData.append('link_title', linkTitle);
                formData.append('link_description', linkDescription);
            }
        }

        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=edit', {
                method: 'POST',
                body: formData
            });
            
            const responseText = await response.text();
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('Failed to parse JSON response:', parseError);
                console.error('Raw response:', responseText);
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Server returned an invalid response. Please try again.', 'error');
                }
                return;
            }
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post updated successfully!', 'success');
                }
                $('#editPostModal').modal('hide');
                this.resetEditPostForm();
                this.loadPosts(); // Reload posts to show updated post
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to update post', 'error');
                }
            }
        } catch (error) {
            console.error('Error updating post:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to update post. Please try again.', 'error');
            }
        }
    }

    resetEditPostForm() {
        document.getElementById('editPostForm').reset();
        document.getElementById('editMediaSection').style.display = 'none';
        document.getElementById('editPollSection').style.display = 'none';
        document.getElementById('editLinkSection').style.display = 'none';
        document.getElementById('editCustomFieldSelection').style.display = 'none';
        document.getElementById('editMediaPreview').innerHTML = '';
        document.getElementById('editTagDisplay').innerHTML = '';
        document.getElementById('editTagList').value = '';
        document.getElementById('editCharCounter').textContent = '0/2000';
        document.getElementById('editTitleCharCounter').textContent = '0/150';
        this.editMediaFiles = []; // Clear edit media files array
        this.filesToDelete = []; // Clear files to delete array
        this.clearValidationErrors('editPostForm');
    }

    addEditTag(tag) {
        if (tag && !window.editTags.includes(tag)) {
            window.editTags.push(tag);
            this.updateEditTagDisplay();
        }
    }

    removeEditTag(tag) {
        window.editTags = window.editTags.filter(t => t !== tag);
        this.updateEditTagDisplay();
    }

    addEditPollOption() {
        const pollContainer = document.getElementById('editPollOptions');
        const optionCount = pollContainer.children.length;
        const newOption = document.createElement('div');
        newOption.className = 'input-group mb-2';
        newOption.innerHTML = `
            <input type="text" class="form-control" name="edit_poll_options[]" placeholder="Poll option ${optionCount + 1}">
            <button class="btn btn-outline-danger" type="button" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        pollContainer.appendChild(newOption);
    }

    handleEditPostTypeChange(postType) {
        // Handle post type change in edit form
        const mediaSection = document.getElementById('editMediaSection');
        const pollSection = document.getElementById('editPollSection');
        const linkSection = document.getElementById('editLinkSection');
        
        // Show/hide media section based on post type
        if (postType === 'media') {
            mediaSection.style.display = 'block';
        } else {
            mediaSection.style.display = 'none';
            // Clear media preview when hiding
            document.getElementById('editMediaPreview').innerHTML = '';
            document.getElementById('editMediaFiles').value = '';
        }
        
        // Handle poll section
        if (postType === 'poll') {
            document.getElementById('editIncludePoll').checked = true;
            this.toggleEditPollSection(true);
        } else {
            document.getElementById('editIncludePoll').checked = false;
            this.toggleEditPollSection(false);
        }
        
        // Show/hide link section based on post type
        if (postType === 'link') {
            linkSection.style.display = 'block';
        } else {
            linkSection.style.display = 'none';
            // Clear link fields when hiding
            document.getElementById('editLinkUrl').value = '';
            document.getElementById('editLinkTitle').value = '';
            document.getElementById('editLinkDescription').value = '';
            // Clear any validation errors for link fields
            this.clearEditLinkFieldErrors();
        }
        
        // Trigger validation for all fields to update their validation state
        this.triggerEditFormValidation();
    }

    handleCreatePostTypeChange(postType) {
        // Handle post type change in create form
        const mediaSection = document.getElementById('createMediaSection');
        const pollSection = document.getElementById('pollSection');
        const linkSection = document.getElementById('linkSection');
        
        // Show/hide media section based on post type
        if (postType === 'media') {
            mediaSection.style.display = 'block';
        } else {
            mediaSection.style.display = 'none';
            // Clear media preview when hiding
            document.getElementById('mediaPreview').innerHTML = '';
            document.getElementById('mediaFiles').value = '';
        }
        
        // Handle poll section
        if (postType === 'poll') {
            document.getElementById('includePoll').checked = true;
            this.togglePollSection(true);
        } else {
            document.getElementById('includePoll').checked = false;
            this.togglePollSection(false);
        }
        
        // Show/hide link section based on post type
        if (postType === 'link') {
            linkSection.style.display = 'block';
        } else {
            linkSection.style.display = 'none';
            // Clear link fields when hiding
            document.getElementById('linkUrl').value = '';
            document.getElementById('linkTitle').value = '';
            document.getElementById('linkDescription').value = '';
            // Clear any validation errors for link fields
            this.clearLinkFieldErrors();
        }
        
        // Trigger validation for all fields to update their validation state
        this.triggerFormValidation();
    }

    formatPostContent(content) {
        // Convert mentions and hashtags to styled spans
        return content
            .replace(/@(\w+)/g, '<span class="mention">@$1</span>')
            .replace(/#(\w+)/g, '<span class="hashtag">#$1</span>')
            .replace(/\n/g, '<br>');
    }

    renderPostMedia(media) {
        if (!media || media.length === 0) return '';
        
        // If only one media file, display it normally
        if (media.length === 1) {
            const item = media[0];
            if (item.type.startsWith('image/')) {
                return `<div class="post-media"><img src="${item.url}" alt="Post media" class="img-fluid"></div>`;
            } else if (item.type.startsWith('video/')) {
                return `<div class="post-media"><video src="${item.url}" controls class="img-fluid"></video></div>`;
            } else {
                return `<div class="post-media"><a href="${item.url}" target="_blank">
                    <i class="fas fa-file-download"></i>${item.filename}
                </a></div>`;
            }
        }
        
        // If multiple media files, use grid layout
        const mediaItems = media.map(item => {
            if (item.type.startsWith('image/')) {
                return `<img src="${item.url}" alt="Post media" class="img-fluid">`;
            } else if (item.type.startsWith('video/')) {
                return `<video src="${item.url}" controls class="img-fluid"></video>`;
            } else {
                return `<a href="${item.url}" target="_blank">
                    <i class="fas fa-file-download"></i>${item.filename}
                </a>`;
            }
        }).join('');
        
        return `<div class="post-media"><div class="media-grid">${mediaItems}</div></div>`;
    }

    renderPoll(poll) {
        const totalVotes = poll.options.reduce((sum, option) => sum + option.votes, 0);
        const optionsHTML = poll.options.map(option => {
            const percentage = totalVotes > 0 ? Math.round((option.votes / totalVotes) * 100) : 0;
            return `
                <div class="poll-option" onclick="socialFeed.votePoll(${poll.id}, ${option.id})">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>${option.text}</span>
                        <span class="text-muted">${option.votes} votes (${percentage}%)</span>
                    </div>
                    <div class="poll-progress mt-1">
                        <div class="poll-progress-bar" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        }).join('');
        
        return `
            <div class="poll-section mt-3">
                <h6>${poll.question}</h6>
                ${optionsHTML}
                <small class="text-muted">Total votes: ${totalVotes}</small>
            </div>
        `;
    }

    renderPostTags(tags) {
        if (!tags || tags.trim() === '') return '';
        
        const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
        if (tagArray.length === 0) return '';
        
        const tagsHTML = tagArray.map(tag => 
            `<span class="tag-display" onclick="socialFeed.searchByTag('${tag}')">#${tag}</span>`
        ).join('');
        
        return `<div class="post-tags mt-2">${tagsHTML}</div>`;
    }

    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('is-invalid');
            
            // Remove existing error message
            const existingError = field.parentNode.querySelector('.invalid-feedback');
            if (existingError) {
                existingError.remove();
            }
            
            // Add new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }
    }

    clearValidationErrors() {
        const invalidFields = document.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => {
            field.classList.remove('is-invalid');
            const errorMessage = field.parentNode.querySelector('.invalid-feedback');
            if (errorMessage) {
                errorMessage.remove();
            }
        });
    }

    initializeRealTimeValidation() {
        // Real-time validation for form fields
        const titleInput = document.getElementById('postTitle');
        const contentInput = document.getElementById('postContent');
        const pollQuestionInput = document.getElementById('pollQuestion');
        
        if (titleInput) {
            titleInput.addEventListener('input', () => {
                const value = titleInput.value.trim();
                if (value.length > 150) {
                    this.showFieldError('postTitle', 'Title cannot exceed 150 characters');
                } else {
                    titleInput.classList.remove('is-invalid');
                    const errorMessage = titleInput.parentNode.querySelector('.invalid-feedback');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
        }
        
        if (contentInput) {
            contentInput.addEventListener('input', () => {
                const value = contentInput.value.trim();
                if (value.length > 2000) {
                    this.showFieldError('postContent', 'Content cannot exceed 2000 characters');
                } else {
                    contentInput.classList.remove('is-invalid');
                    const errorMessage = contentInput.parentNode.querySelector('.invalid-feedback');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
        }
        
        // Real-time validation for poll question
        if (pollQuestionInput) {
            pollQuestionInput.addEventListener('input', () => {
                const value = pollQuestionInput.value.trim();
                if (value.length > 200) {
                    this.showFieldError('pollQuestion', 'Poll question cannot exceed 200 characters');
                } else {
                    pollQuestionInput.classList.remove('is-invalid');
                    const errorMessage = pollQuestionInput.parentNode.querySelector('.invalid-feedback');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
            
            // Add blur validation for poll question
            pollQuestionInput.addEventListener('blur', () => {
                const value = pollQuestionInput.value.trim();
                const includePoll = document.getElementById('includePoll').checked;
                
                if (includePoll && !value) {
                    this.showFieldError('pollQuestion', 'Poll question is required.');
                } else if (value.length > 200) {
                    this.showFieldError('pollQuestion', 'Poll question cannot exceed 200 characters');
                } else {
                    pollQuestionInput.classList.remove('is-invalid');
                    const errorMessage = pollQuestionInput.parentNode.querySelector('.invalid-feedback');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
        }
        
        // Real-time validation for poll options
        this.initializePollOptionsValidation();
        
        // Real-time validation for edit form poll fields
        this.initializeEditPollValidation();
    }
    
    initializePollOptionsValidation() {
        // Add validation for existing poll options
        const pollOptions = document.querySelectorAll('#pollSection input[name="poll_options[]"]');
        
        pollOptions.forEach((input, index) => {
            input.addEventListener('input', () => {
                this.validatePollOptions();
            });
            
            // Add blur validation for poll options
            input.addEventListener('blur', () => {
                this.validatePollOptions();
            });
        });
        
        // Add validation for poll options added dynamically
        const pollSection = document.getElementById('pollSection');
        if (pollSection) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                const newInputs = node.querySelectorAll('input[name="poll_options[]"]');
                                newInputs.forEach(input => {
                                    input.addEventListener('input', () => {
                                        this.validatePollOptions();
                                    });
                                    
                                    // Add blur validation for new poll options
                                    input.addEventListener('blur', () => {
                                        this.validatePollOptions();
                                    });
                                });
                            }
                        });
                    }
                });
            });
            observer.observe(pollSection, { childList: true, subtree: true });
        }
    }
    
    validatePollOptions() {
        const pollOptions = document.querySelectorAll('#pollSection input[name="poll_options[]"]');
        const validOptions = Array.from(pollOptions).filter(input => input.value.trim() !== '');
        const includePoll = document.getElementById('includePoll').checked;
        
        // Clear all poll option errors first
        pollOptions.forEach(input => {
            input.classList.remove('is-invalid');
            const errorMessage = input.parentNode.querySelector('.invalid-feedback');
            if (errorMessage) {
                errorMessage.remove();
            }
        });
        
        // If poll is included and we have less than 2 valid options, show error
        if (includePoll && validOptions.length < 2) {
            pollOptions.forEach(input => {
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = 'At least 2 poll options are required.';
                input.parentNode.appendChild(errorDiv);
            });
        }
    }
    
    initializeEditPollValidation() {
        // Real-time validation for edit poll question
        const editPollQuestionInput = document.getElementById('editPollQuestion');
        if (editPollQuestionInput) {
            editPollQuestionInput.addEventListener('input', () => {
                const value = editPollQuestionInput.value.trim();
                if (value.length > 200) {
                    this.showFieldError('editPollQuestion', 'Poll question cannot exceed 200 characters');
                } else {
                    editPollQuestionInput.classList.remove('is-invalid');
                    const errorMessage = editPollQuestionInput.parentNode.querySelector('.invalid-feedback');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
            
            // Add blur validation for edit poll question
            editPollQuestionInput.addEventListener('blur', () => {
                const value = editPollQuestionInput.value.trim();
                const includePoll = document.getElementById('editIncludePoll').checked;
                
                if (includePoll && !value) {
                    this.showFieldError('editPollQuestion', 'Poll question is required.');
                } else if (value.length > 200) {
                    this.showFieldError('editPollQuestion', 'Poll question cannot exceed 200 characters');
                } else {
                    editPollQuestionInput.classList.remove('is-invalid');
                    const errorMessage = editPollQuestionInput.parentNode.querySelector('.invalid-feedback');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
        }
        
        // Real-time validation for edit poll options
        this.initializeEditPollOptionsValidation();
    }
    
    initializeEditPollOptionsValidation() {
        // Add validation for existing edit poll options
        const editPollOptions = document.querySelectorAll('#editPollSection input[name="edit_poll_options[]"]');
        editPollOptions.forEach(input => {
            input.addEventListener('input', () => {
                this.validateEditPollOptions();
            });
            
            // Add blur validation for edit poll options
            input.addEventListener('blur', () => {
                this.validateEditPollOptions();
            });
        });
        
        // Add validation for edit poll options added dynamically
        const editPollSection = document.getElementById('editPollSection');
        if (editPollSection) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                const newInputs = node.querySelectorAll('input[name="edit_poll_options[]"]');
                                newInputs.forEach(input => {
                                    input.addEventListener('input', () => {
                                        this.validateEditPollOptions();
                                    });
                                    
                                    // Add blur validation for new edit poll options
                                    input.addEventListener('blur', () => {
                                        this.validateEditPollOptions();
                                    });
                                });
                            }
                        });
                    }
                });
            });
            observer.observe(editPollSection, { childList: true, subtree: true });
        }
    }
    
    validateEditPollOptions() {
        const pollOptions = document.querySelectorAll('#editPollSection input[name="edit_poll_options[]"]');
        const validOptions = Array.from(pollOptions).filter(input => input.value.trim() !== '');
        
        // Clear all poll option errors first
        pollOptions.forEach(input => {
            input.classList.remove('is-invalid');
            const errorMessage = input.parentNode.querySelector('.invalid-feedback');
            if (errorMessage) {
                errorMessage.remove();
            }
        });
        
        // If poll is included and we have less than 2 valid options, show error
        const includePoll = document.getElementById('editIncludePoll').checked;
        if (includePoll && validOptions.length < 2) {
            pollOptions.forEach(input => {
                input.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = 'At least 2 poll options are required.';
                input.parentNode.appendChild(errorDiv);
            });
        }
    }

    showError(message) {
        if (typeof showSimpleToast === 'function') {
            showSimpleToast(message, 'error');
        } else {
            alert(message);
        }
    }

    getEditButton(post) {
        if (post.can_edit) {
            return `
                <button type="button" class="btn btn-sm theme-btn-secondary edit-post-btn"
                        data-post-id="${post.id}"
                        onclick="socialFeed.editPost(${post.id})"
                        title="Edit Post">
                    <i class="fas fa-edit"></i>
                </button>
            `;
        }
        return '';
    }

    getDeleteButton(post) {
        if (post.can_delete) {
            return `
                <button type="button" class="btn btn-sm theme-btn-danger delete-post-btn"
                        data-post-id="${post.id}"
                        data-post-title="${this.escapeHtml(post.title || 'Post')}"
                        onclick="socialFeed.confirmDeletePost(${post.id})"
                        title="Delete Post">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
        }
        return '';
    }

    getStatusActionButton(post) {
        // More robust status checking
        const status = (post.status || '').toString().toLowerCase().trim();
        
        if (status === 'draft') {
            return `
                <button type="button" class="btn btn-sm theme-btn-success activate-post-btn"
                        data-post-id="${post.id}"
                        onclick="socialFeed.activatePost(${post.id})"
                        title="Activate Post">
                    <i class="fas fa-play"></i>
                </button>
            `;
        } else if (status === 'active') {
            return `
                <button type="button" class="btn btn-sm theme-btn-warning archive-post-btn"
                        data-post-id="${post.id}"
                        onclick="socialFeed.archivePost(${post.id})"
                        title="Archive Post">
                    <i class="fas fa-archive"></i>
                </button>
            `;
        } else if (status === 'scheduled') {
            return `
                <button type="button" class="btn btn-sm theme-btn-warning cancel-schedule-btn"
                        data-post-id="${post.id}"
                        onclick="socialFeed.cancelSchedule(${post.id})"
                        title="Cancel Schedule">
                    <i class="fas fa-times"></i>
                </button>
            `;
        } else if (status === 'archived') {
            return `
                <button type="button" class="btn btn-sm theme-btn-success unarchive-post-btn"
                        data-post-id="${post.id}"
                        onclick="socialFeed.unarchivePost(${post.id})"
                        title="Unarchive Post">
                    <i class="fas fa-undo"></i>
                </button>
            `;
        } else {
            return `
                <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                    <i class="fas fa-minus"></i>
                </button>
            `;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async viewPostStatistics(postId) {
        try {
            const response = await fetch(`index.php?controller=SocialFeedController&action=statistics&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.showStatisticsModal(data.statistics, postId);
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to load statistics', 'error');
                }
            }
        } catch (error) {
            console.error('Error loading statistics:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to load statistics. Please try again.', 'error');
            }
        }
    }

    showStatisticsModal(statistics, postId) {
        const modal = document.getElementById('statisticsModal');
        
        // Update modal content with statistics
        modal.querySelector('.modal-title').textContent = `Post Statistics - Post #${postId}`;
        
        // Update statistics values
        modal.querySelector('.total-reactions').textContent = statistics.total_reactions;
        modal.querySelector('.total-comments').textContent = statistics.total_comments;
        modal.querySelector('.total-views').textContent = statistics.total_views;
        modal.querySelector('.total-shares').textContent = statistics.total_shares;
        
        // Update reaction breakdown
        const reactionBreakdown = modal.querySelector('.reaction-breakdown');
        reactionBreakdown.innerHTML = Object.entries(statistics.reaction_breakdown)
            .map(([type, count]) => `
                <div class="d-flex justify-content-between">
                    <span class="text-capitalize">${type}</span>
                    <span class="badge bg-primary">${count}</span>
                </div>
            `).join('');
        
        // Show the modal
        $('#statisticsModal').modal('show');
    }

    // Unarchive post
    async unarchivePost(postId) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=updateStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId,
                    status: 'active'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post unarchived successfully!', 'success');
                }
                this.loadPosts(); // Reload posts to update status
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to unarchive post', 'error');
                }
            }
        } catch (error) {
            console.error('Error unarchiving post:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to unarchive post. Please try again.', 'error');
            }
        }
    }

    // Toggle pin post
    async togglePinPost(postId, isPinned) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=pin', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId,
                    is_pinned: isPinned
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(isPinned ? 'Post pinned successfully!' : 'Post unpinned successfully!', 'success');
                }
                this.loadPosts(); // Reload posts to update status
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to update pin status', 'error');
                }
            }
        } catch (error) {
            console.error('Error updating pin status:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to update pin status. Please try again.', 'error');
            }
        }
    }

    // Archive post
    async archivePost(postId) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=updateStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId,
                    status: 'archived'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post archived successfully!', 'success');
                }
                this.loadPosts(); // Reload posts to update status
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to archive post', 'error');
                }
            }
        } catch (error) {
            console.error('Error archiving post:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to archive post. Please try again.', 'error');
            }
        }
    }

    // Activate post
    async activatePost(postId) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=updateStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId,
                    status: 'active'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post activated successfully!', 'success');
                }
                this.loadPosts(); // Reload posts to update status
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to activate post', 'error');
                }
            }
        } catch (error) {
            console.error('Error activating post:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to activate post. Please try again.', 'error');
            }
        }
    }

    // Cancel schedule
    async cancelSchedule(postId) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=updateStatus', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId,
                    status: 'draft'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post schedule cancelled successfully!', 'success');
                }
                this.loadPosts(); // Reload posts to update status
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to cancel schedule', 'error');
                }
            }
        } catch (error) {
            console.error('Error cancelling schedule:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to cancel schedule. Please try again.', 'error');
            }
        }
    }

    // Get status badge
    getStatusBadge(status) {
        const badges = {
            'active': '<span class="badge bg-success">Active</span>',
            'scheduled': '<span class="badge bg-primary">Scheduled</span>',
            'draft': '<span class="badge bg-secondary">Draft</span>',
            'expired': '<span class="badge bg-warning">Expired</span>',
            'archived': '<span class="badge bg-dark">Archived</span>',
            'reported': '<span class="badge bg-danger">Reported</span>'
        };
        return badges[status] || '';
    }

    // Get visibility badge
    getVisibilityBadge(visibility) {
        const badges = {
            'global': '<span class="badge bg-info">Global</span>',
            'group_specific': '<span class="badge bg-warning">Group</span>'
        };
        return badges[visibility] || '';
    }

    // Utility functions
    truncateText(text, length) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    stripHtml(html) {
        const div = document.createElement('div');
        div.innerHTML = html;
        return div.textContent || div.innerText || '';
    }

    // Clear link field errors for create form
    clearLinkFieldErrors() {
        const linkFields = ['linkUrl', 'linkTitle', 'linkDescription'];
        linkFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                // Remove error styling
                field.classList.remove('is-invalid');
                // Remove error message
                const errorElement = field.parentNode.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });
    }

    // Clear link field errors for edit form
    clearEditLinkFieldErrors() {
        const linkFields = ['editLinkUrl', 'editLinkTitle', 'editLinkDescription'];
        linkFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                // Remove error styling
                field.classList.remove('is-invalid');
                // Remove error message
                const errorElement = field.parentNode.querySelector('.error-message');
                if (errorElement) {
                    errorElement.style.display = 'none';
                }
            }
        });
    }

    // Trigger validation for create form
    triggerFormValidation() {
        // Trigger blur events on all form fields to update validation state
        const formFields = document.querySelectorAll('#createPostForm input, #createPostForm select, #createPostForm textarea');
        formFields.forEach(field => {
            if (field.type !== 'file' && field.type !== 'checkbox') {
                field.dispatchEvent(new Event('blur'));
            }
        });
    }

    // Trigger validation for edit form
    triggerEditFormValidation() {
        // Trigger blur events on all form fields to update validation state
        const formFields = document.querySelectorAll('#editPostForm input, #editPostForm select, #editPostForm textarea');
        formFields.forEach(field => {
            if (field.type !== 'file' && field.type !== 'checkbox') {
                field.dispatchEvent(new Event('blur'));
            }
        });
    }

    renderPollIndicator(poll) {
        const totalVotes = poll.options.reduce((sum, option) => sum + option.votes, 0);
        return `
            <div class="post-poll-indicator">
                <div class="d-flex align-items-center text-muted">
                    <i class="fas fa-poll me-2"></i>
                    <span class="small">Poll: ${this.escapeHtml(poll.question)}</span>
                    <span class="badge bg-light text-dark ms-2">${totalVotes} votes</span>
                </div>
            </div>
        `;
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.socialFeed = new SocialFeed();
});