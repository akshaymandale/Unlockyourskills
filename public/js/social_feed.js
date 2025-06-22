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
        this.currentFilters = {
            post_type: '',
            visibility: '',
            status: '',
            date_from: '',
            date_to: ''
        };
        this.mediaFiles = [];
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
            this.handleCreatePost(e);
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

        // Edit modal close
        $('#editPostModal').on('hidden.bs.modal', () => {
            this.resetEditPostForm();
        });

        // Initialize filters and search functionality
        this.initializeFilters();

        // Create post form
        $('#createPostForm').on('submit', (e) => this.handleCreatePost(e));

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
        const dropZone = document.getElementById('mediaDropZone');
        const fileInput = document.getElementById('mediaFiles');

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

    handleFileSelection(files) {
        files.forEach(file => {
            if (this.validateFile(file)) {
                this.mediaFiles.push(file);
                this.displayMediaPreview(file);
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

    removeMediaFile(filename) {
        this.mediaFiles = this.mediaFiles.filter(file => file.name !== filename);
        const item = document.querySelector(`[data-filename="${filename}"]`);
        if (item) item.remove();
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
            post_type: $('#postTypeFilter').val(),
            visibility: $('#visibilityFilter').val(),
            status: $('#statusFilter').val(),
            date_from: $('#dateFilterFrom').val(),
            date_to: $('#dateFilterTo').val(),
            search: $('#searchPosts').val()
        };
        this.currentPage = 1;
        this.loadPosts();
    }

    performSearch() {
        this.currentFilters.search = $('#searchPosts').val();
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
            console.log('[SocialFeed] Raw AJAX response:', responseText); // DEBUG
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('[SocialFeed] Failed to parse JSON response:', parseError);
                console.error('[SocialFeed] Raw response:', responseText);
                this.showError('Server returned an invalid response. Please try again.');
                return;
            }
            
            console.log('[SocialFeed] Parsed data:', data); // DEBUG
            if (data.success) {
                console.log('[SocialFeed] Posts array length:', data.posts.length); // DEBUG
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

        const postsHTML = posts.map(post => {
            return this.renderPostCard(post);
        }).join('');
        grid.innerHTML = postsHTML;

        // Add event listeners to action buttons
        this.addActionListeners();
    }

    renderPostCard(post) {
        console.log('[SocialFeed] renderPostCard called for post:', post); // DEBUG
        const mediaHTML = this.renderPostMedia(post.media);
        const pollHTML = post.poll ? this.renderPoll(post.poll) : '';
        const badgesHTML = this.renderPostBadges(post);
        const tagsHTML = this.renderPostTags(post.tags);
        
        // Add reported class if post has been reported
        const reportedClass = post.report_count > 0 ? ' reported' : '';
        
        return `
            <div class="post-card${reportedClass}" data-post-id="${post.id}">
                <div class="post-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <img src="${post.author.avatar || 'public/images/UYSlogo.png'}" 
                                     alt="${post.author.name}" class="rounded-circle" width="40" height="40">
                            </div>
                            <div>
                                <h6 class="mb-0">${post.author.name}</h6>
                                <small class="text-muted">
                                    ${this.formatDate(post.created_at)} • ${post.post_type}
                                    ${post.visibility !== 'public' ? ` • ${post.visibility}` : ''}
                                </small>
                            </div>
                        </div>
                    </div>
                    ${badgesHTML}
                </div>
                
                <div class="post-content">
                    ${post.title ? `<h5 class="post-title mb-3">${this.escapeHtml(post.title)}</h5>` : ''}
                    <div class="post-text">
                        ${this.formatPostContent(post.body)}
                    </div>
                    ${mediaHTML}
                    ${pollHTML}
                    ${tagsHTML}
                </div>
                
                <div class="post-actions">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="socialFeed.toggleReaction(${post.id}, 'like')">
                                <i class="fas fa-thumbs-up me-1"></i>
                                <span class="reaction-count">${post.reactions.like || 0}</span>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="socialFeed.toggleReaction(${post.id}, 'love')">
                                <i class="fas fa-heart me-1"></i>
                                <span class="reaction-count">${post.reactions.love || 0}</span>
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="socialFeed.toggleComments(${post.id})">
                                <i class="fas fa-comment me-1"></i>
                                <span class="comment-count">${post.comment_count || 0}</span>
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-warning" onclick="socialFeed.sharePost(${post.id})">
                                <i class="fas fa-share me-1"></i>Share
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary view-details-btn me-1"
                                data-post-id="${post.id}"
                                onclick="socialFeed.viewPostDetails(${post.id})"
                                title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger report-post-btn me-1"
                                data-post-id="${post.id}"
                                onclick="socialFeed.reportPost(${post.id})"
                                title="Report Post">
                            <i class="fas fa-flag"></i>
                        </button>
                        ${this.getEditButton(post)}
                        <button type="button" class="btn btn-sm btn-outline-info view-statistics-btn me-1"
                                data-post-id="${post.id}"
                                onclick="socialFeed.viewPostStatistics(${post.id})"
                                title="View Statistics">
                            <i class="fas fa-chart-bar"></i>
                        </button>
                        ${this.getStatusActionButton(post)}
                        ${this.getDeleteButton(post)}
                    </div>
                </div>
            </div>
        `;
    }

    renderPostMedia(media) {
        if (!media || media.length === 0) return '';
        
        const mediaItems = media.map(item => {
            if (item.type.startsWith('image/')) {
                return `<img src="${item.url}" alt="Post media" class="img-fluid">`;
            } else if (item.type.startsWith('video/')) {
                return `<video src="${item.url}" controls class="img-fluid"></video>`;
            } else {
                return `<a href="${item.url}" class="btn btn-outline-primary" target="_blank">
                    <i class="fas fa-file-download me-1"></i>${item.filename}
                </a>`;
            }
        }).join('');
        
        return `<div class="post-media">${mediaItems}</div>`;
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
        if (post.is_pinned) {
            badges += '<span class="pinned-badge ms-2"><i class="fas fa-thumbtack me-1"></i>Pinned</span>';
        }
        if (post.report_count > 0) {
            badges += '<span class="reported-badge ms-2"><i class="fas fa-flag me-1"></i>Reported</span>';
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
        this.loadPosts();
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
        
        // Note: All validation (title, content, post_type, visibility, poll, schedule) is handled by social_feed_validation.js
        // Only validate complex fields that aren't handled by the validation script
        
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

        // Add poll options if poll is included
        if (includePoll) {
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
        this.mediaFiles.forEach(file => {
            formData.append('media[]', file);
        });

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
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to create post', 'error');
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
        document.getElementById('mediaPreview').innerHTML = '';
        this.mediaFiles = [];
        document.getElementById('pollSection').style.display = 'none';
        document.getElementById('scheduleDateTime').style.display = 'none';
        
        // Reset character counters
        document.getElementById('titleCharCounter').textContent = '0/150';
        document.getElementById('charCounter').textContent = '0/2000';
        
        // Reset tags
        if (window.tags) {
            window.tags = [];
            document.getElementById('tagDisplay').innerHTML = '';
            document.getElementById('tagList').value = '';
        }
    }

    async toggleReaction(postId, reactionType) {
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=reaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId,
                    reaction_type: reactionType
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update the reaction count in the UI
                const reactionButton = document.querySelector(`[data-post-id="${postId}"][data-reaction="${reactionType}"]`);
                if (reactionButton) {
                    const countElement = reactionButton.querySelector('.reaction-count');
                    if (countElement) {
                        countElement.textContent = data.reaction_count || 0;
                    }
                }
                
                // Reload posts to update all reaction counts
                this.loadPosts();
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to update reaction', 'error');
                }
            }
        } catch (error) {
            console.error('Error updating reaction:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to update reaction. Please try again.', 'error');
            }
        }
    }

    async toggleComments(postId) {
        const postCard = document.querySelector(`[data-post-id="${postId}"]`);
        let commentsSection = postCard.querySelector('.comments-section');
        
        // If comments section exists and is visible, hide it
        if (commentsSection && commentsSection.style.display !== 'none') {
            commentsSection.style.display = 'none';
            return;
        }
        
        // If comments section doesn't exist or is hidden, show it
        try {
            const response = await fetch(`index.php?controller=SocialFeedController&action=comments&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderComments(postId, data.comments);
                // Ensure the comments section is visible
                const newCommentsSection = postCard.querySelector('.comments-section');
                if (newCommentsSection) {
                    newCommentsSection.style.display = 'block';
                }
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to load comments', 'error');
                }
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to load comments. Please try again.', 'error');
            }
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
        const input = document.getElementById(`commentInput_${postId}`);
        const content = input.value.trim();
        
        if (!content) {
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Please enter a comment', 'warning');
            }
            return;
        }
        
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=comment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    post_id: postId,
                    content: content
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                input.value = '';
                // Reload comments to show the new comment
                const commentsResponse = await fetch(`index.php?controller=SocialFeedController&action=comments&post_id=${postId}`);
                const commentsData = await commentsResponse.json();
                if (commentsData.success) {
                    this.renderComments(postId, commentsData.comments);
                }
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Comment added successfully!', 'success');
                }
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to add comment', 'error');
                }
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to add comment. Please try again.', 'error');
            }
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
                // Reload the post to update poll results
                this.loadPosts();
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Vote recorded!', 'success');
                }
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to record vote', 'error');
                }
            }
        } catch (error) {
            console.error('Error voting:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to record vote. Please try again.', 'error');
            }
        }
    }

    async deletePost(postId) {
        console.log('🗑️ Delete post called for ID:', postId);
        
        // Get post title for confirmation message
        const postElement = document.querySelector(`[data-post-id="${postId}"]`);
        const postTitle = postElement ? 
            (postElement.querySelector('.post-title')?.textContent || 
             postElement.querySelector('.post-text')?.textContent?.substring(0, 50) + '...') : 
            'this post';
        
        console.log('📝 Post title for confirmation:', postTitle);
        console.log('🔍 confirmAction available:', typeof window.confirmAction === 'function');
        console.log('🔍 confirmDelete available:', typeof window.confirmDelete === 'function');
        
        // Use the confirmation modal system with proper delete styling
        if (typeof window.confirmAction === 'function') {
            console.log('✅ Using confirmAction for delete confirmation');
            window.confirmAction('delete', `post "${postTitle}"`, () => {
                console.log('✅ Delete confirmed, executing delete...');
                this.executeDeletePost(postId);
            });
        } else if (typeof window.confirmDelete === 'function') {
            console.log('✅ Using confirmDelete for delete confirmation');
            window.confirmDelete(`post "${postTitle}"`, () => {
                console.log('✅ Delete confirmed, executing delete...');
                this.executeDeletePost(postId);
            });
        } else {
            console.log('⚠️ Using fallback browser confirm');
            // Fallback to browser confirm
            if (confirm(`Are you sure you want to delete ${postTitle}? This action cannot be undone.`)) {
                console.log('✅ Delete confirmed, executing delete...');
                this.executeDeletePost(postId);
            }
        }
    }

    async executeDeletePost(postId) {
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
                this.loadPosts();
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

    reportPost(postId) {
        document.getElementById('reportPostId').value = postId;
        $('#reportModal').modal('show');
    }

    async handleReportSubmit(e) {
        e.preventDefault();
        
        // Check if validation is available and run it
        if (typeof window.validateReportForm === 'function') {
            const isValid = window.validateReportForm();
            if (!isValid) {
                console.log("❌ Report validation failed");
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(translate('js.validation.fix_errors_before_submit') || 'Please fix the errors before submitting.', 'error');
                }
                return;
            }
        }
        
        const formData = new FormData(e.target);
        
        try {
            const response = await fetch('index.php?controller=SocialFeedController&action=report', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Report submitted successfully!', 'success');
                }
                $('#reportModal').modal('hide');
                e.target.reset();
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to submit report', 'error');
                }
            }
        } catch (error) {
            console.error('Error submitting report:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to submit report. Please try again.', 'error');
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
        const modalContent = document.getElementById('postDetailContent');
        
        const mediaHTML = this.renderPostMedia(post.media);
        const pollHTML = post.poll ? this.renderPoll(post.poll) : '';
        const tagsHTML = this.renderPostTags(post.tags);
        
        // Calculate counts from actual data
        const reactionCount = post.reactions ? Object.values(post.reactions).reduce((sum, count) => sum + (count || 0), 0) : 0;
        const commentCount = post.comments ? post.comments.length : 0;
        const shareCount = post.share_count || 0; // Default to 0 if not provided
        
        // Format creation date
        const createdDate = new Date(post.created_at).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Get post status and visibility info
        const statusInfo = [];
        if (post.status) statusInfo.push(post.status);
        if (post.visibility && post.visibility !== 'global') statusInfo.push(post.visibility);
        if (post.is_pinned) statusInfo.push('Pinned');
        if (post.report_count > 0) statusInfo.push(`Reported (${post.report_count})`);
        
        // Generate reports section if post has reports
        const reportsHTML = post.reports && post.reports.length > 0 ? `
            <div class="reports-section mb-4">
                <h6 class="mb-3 text-danger">
                    <i class="fas fa-flag me-2"></i>Reports (${post.reports.length})
                </h6>
                <div class="reports-list">
                    ${post.reports.map(report => `
                        <div class="report-item mb-3 p-3 border border-danger rounded bg-light">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="${report.reporter?.avatar || 'public/images/UYSlogo.png'}" 
                                         alt="${report.reporter?.name || 'Unknown'}" 
                                         class="rounded-circle me-2" width="24" height="24">
                                    <strong class="text-danger">${report.reporter?.name || 'Unknown User'}</strong>
                                </div>
                                <div class="text-muted small">
                                    ${this.formatDate(report.created_at)}
                                </div>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-danger me-2">${this.formatReportReason(report.report_reason)}</span>
                                <span class="badge bg-secondary">${report.status}</span>
                            </div>
                            ${report.report_details ? `
                                <div class="report-details mt-2">
                                    <strong>Details:</strong>
                                    <p class="mb-0 mt-1">${this.escapeHtml(report.report_details)}</p>
                                </div>
                            ` : ''}
                            ${report.moderator_notes ? `
                                <div class="moderator-notes mt-2">
                                    <strong class="text-primary">Moderator Notes:</strong>
                                    <p class="mb-0 mt-1">${this.escapeHtml(report.moderator_notes)}</p>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : '';
        
        modalContent.innerHTML = `
            <div class="post-detail">
                <div class="post-header mb-4">
                    <div class="d-flex align-items-center">
                        <div class="avatar me-3">
                            <img src="${post.author.avatar || 'public/images/UYSlogo.png'}" 
                                 alt="${post.author.name}" class="rounded-circle" width="60" height="60">
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">${post.author.name || 'Unknown User'}</h5>
                            <p class="text-muted mb-1">
                                <i class="fas fa-calendar-alt me-1"></i>${createdDate}
                            </p>
                            <p class="text-muted mb-0">
                                <i class="fas fa-tag me-1"></i>${post.post_type || 'General'}
                                ${statusInfo.length > 0 ? ` • ${statusInfo.join(', ')}` : ''}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="post-content mb-4">
                    ${post.title ? `<h4 class="mb-3 text-primary">${this.escapeHtml(post.title)}</h4>` : ''}
                    <div class="post-body mb-3">
                        <p class="mb-0">${this.formatPostContent(post.body || post.content || '')}</p>
                    </div>
                    ${mediaHTML}
                    ${pollHTML}
                    ${tagsHTML}
                </div>
                
                ${reportsHTML}
                
                <div class="post-stats mb-4">
                    <div class="row text-center">
                        <div class="col">
                            <div class="stat-item">
                                <i class="fas fa-heart text-danger"></i>
                                <span class="ms-1 fw-bold">${reactionCount}</span>
                                <div class="text-muted small">Reactions</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-item">
                                <i class="fas fa-comment text-primary"></i>
                                <span class="ms-1 fw-bold">${commentCount}</span>
                                <div class="text-muted small">Comments</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="stat-item">
                                <i class="fas fa-share text-success"></i>
                                <span class="ms-1 fw-bold">${shareCount}</span>
                                <div class="text-muted small">Shares</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${post.comments && post.comments.length > 0 ? `
                    <div class="comments-section">
                        <h6 class="mb-3">
                            <i class="fas fa-comments me-2"></i>Comments (${commentCount})
                        </h6>
                        <div class="comments-list">
                            ${post.comments.map(comment => `
                                <div class="comment-item mb-3 p-3 border rounded">
                                    <div class="d-flex">
                                        <img src="${comment.author.avatar || 'public/images/UYSlogo.png'}" 
                                             alt="${comment.author.name}" class="rounded-circle me-2" width="32" height="32">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <strong>${comment.author.name || 'Unknown User'}</strong>
                                                <small class="text-muted">${this.formatDate(comment.created_at)}</small>
                                            </div>
                                            <p class="mb-0 mt-1">${this.escapeHtml(comment.body || comment.content || '')}</p>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-comments fa-2x mb-2"></i>
                        <p>No comments yet</p>
                    </div>
                `}
            </div>
        `;
    }

    formatReportReason(reason) {
        const reasonMap = {
            'spam': 'Spam',
            'inappropriate': 'Inappropriate Content',
            'harassment': 'Harassment',
            'fake_news': 'Fake News/Misinformation',
            'other': 'Other'
        };
        return reasonMap[reason] || reason;
    }

    sharePost(postId) {
        const postUrl = `${window.location.origin}${window.location.pathname}?controller=SocialFeedController&action=show&post_id=${postId}`;
        
        if (navigator.share) {
            navigator.share({
                title: 'Check out this post',
                url: postUrl
            });
        } else {
            navigator.clipboard.writeText(postUrl).then(() => {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast('Post URL copied to clipboard!', 'success');
                }
            });
        }
    }

    searchByTag(tag) {
        // Set the search input and trigger search
        const searchInput = document.getElementById('searchPosts');
        if (searchInput) {
            searchInput.value = `#${tag}`;
            this.performSearch();
        }
    }

    showLoading(show) {
        const loadingSpinner = document.getElementById('loadingSpinner');
        const socialFeedGrid = document.getElementById('socialFeedGrid');
        
        if (loadingSpinner) {
            loadingSpinner.style.display = show ? 'block' : 'none';
        }
        
        if (socialFeedGrid) {
            socialFeedGrid.style.display = show ? 'none' : 'block';
        }
        
        // Don't hide noResults here - let renderPosts handle it
    }

    hideLoading() {
        // Loading state is handled by renderPosts
    }

    updateResultsInfo(pagination) {
        const resultsInfo = document.getElementById('resultsInfo');
        if (resultsInfo && pagination) {
            const totalPosts = pagination.total_posts || 0;
            const currentPage = pagination.current_page || 1;
            const perPage = pagination.per_page || this.postsPerPage;
            const start = (currentPage - 1) * perPage + 1;
            const end = Math.min(currentPage * perPage, totalPosts);
            
            if (totalPosts === 0) {
                resultsInfo.textContent = 'No posts found';
            } else {
                resultsInfo.textContent = `Showing ${start}-${end} of ${totalPosts} posts`;
            }
        }
    }

    addActionListeners() {
        // This function can be used to add specific event listeners to dynamically created buttons
        // For now, we're using event delegation in the main initialization
    }

    // Edit post functionality
    editPost(postId) {
        // Load post data for editing
        this.loadPostForEdit(postId);
    }

    async loadPostForEdit(postId) {
        try {
            const response = await fetch(`index.php?controller=SocialFeedController&action=show&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateEditForm(data.post);
                $('#editPostModal').modal('show');
            } else {
                if (typeof showSimpleToast === 'function') {
                    showSimpleToast(data.message || 'Failed to load post for editing', 'error');
                }
            }
        } catch (error) {
            console.error('Error loading post for editing:', error);
            if (typeof showSimpleToast === 'function') {
                showSimpleToast('Failed to load post for editing. Please try again.', 'error');
            }
        }
    }

    populateEditForm(post) {
        // Set post ID
        document.getElementById('editPostId').value = post.id;
        
        // Set title
        document.getElementById('editPostTitle').value = post.title || '';
        this.updateCharacterCount(document.getElementById('editPostTitle'), 'editTitleCharCounter');
        
        // Set content
        document.getElementById('editPostContent').value = post.body;
        this.updateCharacterCount(document.getElementById('editPostContent'), 'editCharCounter');
        
        // Set category
        document.getElementById('editPostType').value = post.post_type;
        
        // Set visibility
        document.getElementById('editVisibility').value = post.visibility;
        
        // Set tags
        if (post.tags) {
            const tagArray = post.tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
            window.editTags = tagArray;
            this.updateEditTagDisplay();
            document.getElementById('editTagList').value = post.tags;
        } else {
            window.editTags = [];
            this.updateEditTagDisplay();
            document.getElementById('editTagList').value = '';
        }
        
        // Set pin status
        document.getElementById('editPinPost').checked = post.is_pinned == 1;
        
        // Set poll data if exists
        if (post.poll) {
            document.getElementById('editIncludePoll').checked = true;
            this.toggleEditPollSection(true);
            this.populateEditPollData(post.poll);
        } else {
            document.getElementById('editIncludePoll').checked = false;
            this.toggleEditPollSection(false);
        }
    }

    updateEditTagDisplay() {
        const tagDisplay = document.getElementById('editTagDisplay');
        tagDisplay.innerHTML = window.editTags.map(tag => 
            `<span class="tag">${tag}<button type="button" class="remove-tag" data-tag="${tag}">×</button></span>`
        ).join('');
    }

    toggleEditPollSection(show) {
        const pollSection = document.getElementById('editPollSection');
        pollSection.style.display = show ? 'block' : 'none';
    }

    populateEditPollData(poll) {
        const pollOptions = document.getElementById('editPollOptions');
        
        pollOptions.innerHTML = '';
        
        poll.options.forEach((option, index) => {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'input-group mb-2';
            optionDiv.innerHTML = `
                <input type="text" class="form-control" name="poll_options[]" value="${option.text}" placeholder="Option ${index + 1}">
                ${index > 1 ? '<button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>' : ''}
            `;
            pollOptions.appendChild(optionDiv);
        });
    }

    async handleEditPost(e) {
        e.preventDefault();
        
        // Clear previous validation errors
        this.clearValidationErrors();
        
        // Get form values
        const title = document.getElementById('editPostTitle').value.trim();
        const content = document.getElementById('editPostContent').value.trim();
        const postType = document.getElementById('editPostType').value;
        const visibility = document.getElementById('editVisibility').value;
        const includePoll = document.getElementById('editIncludePoll').checked;
        
        // Note: All validation (title, content, post_type, visibility, poll) is handled by social_feed_validation.js
        // Only validate complex fields that aren't handled by the validation script
        
        // Validate tags
        const tags = document.getElementById('editTagList').value;
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
        const form = e.target;
        
        // Add form fields
        formData.append('post_id', form.post_id.value);
        formData.append('title', title);
        formData.append('content', content);
        formData.append('post_type', postType);
        formData.append('visibility', visibility);
        formData.append('tags', tags);
        formData.append('pin_post', form.pin_post.checked ? '1' : '0');
        formData.append('include_poll', includePoll ? '1' : '0');

        // Add poll options if poll is included
        if (includePoll) {
            const pollOptions = document.querySelectorAll('#editPollSection input[name="poll_options[]"]');
            pollOptions.forEach(input => {
                if (input.value.trim() !== '') {
                    formData.append('poll_options[]', input.value.trim());
                }
            });
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
                this.loadPosts(); // Reload posts to show updated data
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
        document.getElementById('editMediaPreview').innerHTML = '';
        document.getElementById('editPollSection').style.display = 'none';
        
        // Reset character counters
        document.getElementById('editTitleCharCounter').textContent = '0/150';
        document.getElementById('editCharCounter').textContent = '0/2000';
        
        // Reset tags
        window.editTags = [];
        document.getElementById('editTagDisplay').innerHTML = '';
        document.getElementById('editTagList').value = '';
        
        // Reset character counter
        document.getElementById('editCharCounter').textContent = '0/2000';
    }

    // Edit helper methods
    addEditTag(tag) {
        if (tag.trim() && !window.editTags.includes(tag.trim())) {
            window.editTags.push(tag.trim());
            this.updateEditTagDisplay();
            document.getElementById('editTagList').value = window.editTags.join(',');
        }
    }

    removeEditTag(tag) {
        window.editTags = window.editTags.filter(t => t !== tag);
        this.updateEditTagDisplay();
        document.getElementById('editTagList').value = window.editTags.join(',');
    }

    addEditPollOption() {
        const pollOptions = document.getElementById('editPollOptions');
        const optionCount = pollOptions.children.length;
        
        if (optionCount < 10) {
            const optionDiv = document.createElement('div');
            optionDiv.className = 'input-group mb-2';
            optionDiv.innerHTML = `
                <input type="text" class="form-control" name="poll_options[]" placeholder="Option ${optionCount + 1}">
                <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            `;
            pollOptions.appendChild(optionDiv);
        }
    }

    handleEditPostTypeChange(postType) {
        const pollSection = document.getElementById('editPollSection');
        const includePollCheckbox = document.getElementById('editIncludePoll');
        
        if (postType === 'poll') {
            includePollCheckbox.checked = true;
            this.toggleEditPollSection(true);
        } else {
            includePollCheckbox.checked = false;
            this.toggleEditPollSection(false);
        }
    }

    // Helper method to format post content with mentions and hashtags
    formatPostContent(content) {
        if (!content) return '';
        
        // Convert mentions (@username)
        content = content.replace(/@(\w+)/g, '<span class="mention">@$1</span>');
        
        // Convert hashtags (#tag)
        content = content.replace(/#(\w+)/g, '<span class="hashtag">#$1</span>');
        
        // Convert line breaks
        content = content.replace(/\n/g, '<br>');
        
        return content;
    }

    // Helper method to render post media
    renderPostMedia(media) {
        if (!media || media.length === 0) return '';
        
        let mediaHTML = '<div class="post-media mt-3">';
        
        media.forEach(item => {
            if (item.type === 'image') {
                mediaHTML += `<img src="${item.url}" alt="Post media" class="img-fluid rounded mb-2">`;
            } else if (item.type === 'video') {
                mediaHTML += `
                    <video controls class="img-fluid rounded mb-2">
                        <source src="${item.url}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                `;
            } else {
                mediaHTML += `
                    <div class="document-preview p-3 border rounded mb-2">
                        <i class="fas fa-file-alt me-2"></i>
                        <a href="${item.url}" target="_blank">${item.filename || 'Document'}</a>
                    </div>
                `;
            }
        });
        
        mediaHTML += '</div>';
        return mediaHTML;
    }

    // Helper method to render poll
    renderPoll(poll) {
        if (!poll || !poll.options) return '';
        
        let pollHTML = '<div class="poll-section mt-3 p-3 border rounded">';
        pollHTML += '<h6 class="mb-3">Poll</h6>';
        
        poll.options.forEach(option => {
            const percentage = poll.total_votes > 0 ? Math.round((option.votes / poll.total_votes) * 100) : 0;
            pollHTML += `
                <div class="poll-option mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>${option.text}</span>
                        <span class="text-muted">${percentage}% (${option.votes} votes)</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        });
        
        pollHTML += `<small class="text-muted">Total votes: ${poll.total_votes}</small>`;
        pollHTML += '</div>';
        
        return pollHTML;
    }

    // Helper method to render tags
    renderPostTags(tags) {
        if (!tags) return '';
        
        const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
        if (tagArray.length === 0) return '';
        
        const tagsHTML = tagArray.map(tag => 
            `<span class="badge bg-light text-dark me-1">#${tag}</span>`
        ).join('');
        
        return `<div class="post-tags mt-3">${tagsHTML}</div>`;
    }

    // Validation helper methods
    showFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.classList.add('is-invalid');
            
            // Find or create error message element
            let errorElement = field.parentElement.querySelector('.invalid-feedback');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'invalid-feedback';
                field.parentElement.appendChild(errorElement);
            }
            errorElement.textContent = message;
        }
    }

    clearValidationErrors() {
        const invalidFields = document.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => {
            field.classList.remove('is-invalid');
            const errorElement = field.parentElement.querySelector('.invalid-feedback');
            if (errorElement) {
                errorElement.textContent = '';
            }
        });
    }

    // Real-time validation for better UX
    initializeRealTimeValidation() {
        // Post content validation
        document.getElementById('postContent').addEventListener('input', (e) => {
            const content = e.target.value.trim();
            if (content.length > 2000) {
                this.showFieldError('postContent', 'Post content cannot exceed 2000 characters.');
            } else {
                e.target.classList.remove('is-invalid');
                const errorElement = e.target.parentElement.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            }
        });

        // Category validation
        document.getElementById('post_type').addEventListener('change', (e) => {
            if (e.target.value) {
                e.target.classList.remove('is-invalid');
                const errorElement = e.target.parentElement.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            }
        });

        // Visibility validation
        document.getElementById('visibility').addEventListener('change', (e) => {
            if (e.target.value) {
                e.target.classList.remove('is-invalid');
                const errorElement = e.target.parentElement.querySelector('.invalid-feedback');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            }
        });

        // Schedule date validation
        document.getElementById('scheduleDateTime').addEventListener('change', (e) => {
            if (e.target.value) {
                const scheduledDate = new Date(e.target.value);
                const now = new Date();
                
                if (scheduledDate <= now) {
                    this.showFieldError('scheduleDateTime', 'Scheduled date must be in the future.');
                } else {
                    e.target.classList.remove('is-invalid');
                    const errorElement = e.target.parentElement.querySelector('.invalid-feedback');
                    if (errorElement) {
                        errorElement.textContent = '';
                    }
                }
            }
        });
    }

    // Helper methods for error handling and display
    showError(message) {
        if (typeof showSimpleToast === 'function') {
            showSimpleToast(message, 'error');
        } else {
            console.error(message);
        }
    }

    // Generate edit button with restrictions
    getEditButton(post) {
        if (post.can_edit) {
            return `
                <button type="button" class="btn btn-sm btn-outline-secondary edit-post-btn me-1"
                        data-post-id="${post.id}"
                        onclick="socialFeed.editPost(${post.id})"
                        title="Edit Post">
                    <i class="fas fa-edit"></i>
                </button>
            `;
        } else {
            return `
                <button type="button" class="btn btn-sm btn-secondary me-1"
                        disabled
                        title="You cannot edit this post">
                    <i class="fas fa-edit"></i>
                </button>
            `;
        }
    }

    // Generate delete button with restrictions
    getDeleteButton(post) {
        if (post.can_delete) {
            return `
                <button type="button" class="btn btn-sm btn-outline-danger delete-post-btn me-1"
                        data-post-id="${post.id}"
                        data-post-title="${this.escapeHtml(post.body.substring(0, 50))}${post.body.length > 50 ? '...' : ''}"
                        onclick="socialFeed.deletePost(${post.id})"
                        title="Delete Post">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
        } else {
            return `
                <button type="button" class="btn btn-sm btn-secondary me-1"
                        disabled
                        title="You cannot delete this post">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
        }
    }

    // Generate status action button based on post status
    getStatusActionButton(post) {
        // For social feed posts, we only need edit, delete, pin/unpin, and archive actions
        // Pause/resume doesn't make sense for social media posts
        let buttons = '';
        
        // Pin/Unpin button
        buttons += `<button type="button" class="btn btn-sm ${post.is_pinned ? 'btn-warning' : 'btn-outline-warning'} pin-post-btn me-1"
                        data-post-id="${post.id}"
                        onclick="socialFeed.togglePinPost(${post.id}, ${post.is_pinned ? 'false' : 'true'})"
                        title="${post.is_pinned ? 'Unpin' : 'Pin'} Post">
                    <i class="fas fa-thumbtack"></i>
                </button>`;
        
        // Archive/Unarchive button
        if (post.status === 'active') {
            buttons += `<button type="button" class="btn btn-sm btn-outline-secondary archive-post-btn me-1"
                            data-post-id="${post.id}"
                            onclick="socialFeed.archivePost(${post.id})"
                            title="Archive Post">
                        <i class="fas fa-archive"></i>
                    </button>`;
        } else if (post.status === 'archived') {
            buttons += `<button type="button" class="btn btn-sm btn-outline-secondary unarchive-post-btn me-1"
                            data-post-id="${post.id}"
                            onclick="socialFeed.unarchivePost(${post.id})"
                            title="Unarchive Post">
                        <i class="fas fa-undo"></i>
                    </button>`;
        }
        
        return buttons;
    }

    // Helper function to escape HTML
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // View post statistics
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

    // Show statistics modal
    showStatisticsModal(statistics, postId) {
        const modalHTML = `
            <div class="modal fade" id="statisticsModal" tabindex="-1" aria-labelledby="statisticsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="statisticsModalLabel">
                                <i class="fas fa-chart-bar me-2"></i>Post Statistics
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h3 class="text-primary">${statistics.total_reactions || 0}</h3>
                                            <p class="text-muted mb-0">Total Reactions</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h3 class="text-info">${statistics.total_comments || 0}</h3>
                                            <p class="text-muted mb-0">Total Comments</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h3 class="text-success">${statistics.total_views || 0}</h3>
                                            <p class="text-muted mb-0">Total Views</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h3 class="text-warning">${statistics.total_shares || 0}</h3>
                                            <p class="text-muted mb-0">Total Shares</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ${statistics.reaction_breakdown ? `
                                <div class="mt-3">
                                    <h6>Reaction Breakdown</h6>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-primary">👍 ${statistics.reaction_breakdown.like || 0}</span>
                                        <span class="badge bg-danger">❤️ ${statistics.reaction_breakdown.love || 0}</span>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('statisticsModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('statisticsModal'));
        modal.show();
        
        // Clean up modal when hidden
        document.getElementById('statisticsModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
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
}

// Initialize Social Feed when document is ready
document.addEventListener('DOMContentLoaded', () => {
    window.socialFeed = new SocialFeed();
});