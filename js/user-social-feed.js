class UserSocialFeedManager {
    constructor() {
        this.currentPage = 1;
        this.totalPages = 1;
        this.postsPerPage = 10;
        this.currentFilters = {
            search: '',
            post_type: '',
            visibility: '',
            pinned: '',
            date_from: '',
            date_to: ''
        };
        this.currentPostId = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPosts();
    }

    bindEvents() {
        // Search input with debounce
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', this.debounce(() => {
                this.currentFilters.search = searchInput.value;
                this.currentPage = 1;
                this.loadPosts();
            }, 500));
        }

        // Filter dropdowns
        const filters = ['postTypeFilter', 'visibilityFilter', 'pinnedFilter'];
        filters.forEach(filterId => {
            const filter = document.getElementById(filterId);
            if (filter) {
                filter.addEventListener('change', () => {
                    const filterKey = filterId.replace('Filter', '').replace(/([A-Z])/g, '_$1').toLowerCase().substring(1);
                    this.currentFilters[filterKey] = filter.value;
                    this.currentPage = 1;
                    this.loadPosts();
                });
            }
        });

        // Date range filter
        const dateRangeBtn = document.getElementById('dateRangeBtn');
        const dateRangeInputs = document.getElementById('dateRangeInputs');
        const applyDateFilter = document.getElementById('applyDateFilter');
        const clearDateFilter = document.getElementById('clearDateFilter');

        if (dateRangeBtn && dateRangeInputs) {
            dateRangeBtn.addEventListener('click', () => {
                dateRangeInputs.style.display = dateRangeInputs.style.display === 'none' ? 'block' : 'none';
            });
        }

        if (applyDateFilter) {
            applyDateFilter.addEventListener('click', () => {
                this.currentFilters.date_from = document.getElementById('dateFrom').value;
                this.currentFilters.date_to = document.getElementById('dateTo').value;
                this.currentPage = 1;
                this.loadPosts();
            });
        }

        if (clearDateFilter) {
            clearDateFilter.addEventListener('click', () => {
                document.getElementById('dateFrom').value = '';
                document.getElementById('dateTo').value = '';
                this.currentFilters.date_from = '';
                this.currentFilters.date_to = '';
                this.currentPage = 1;
                this.loadPosts();
            });
        }

        // Clear all filters
        const clearAllFilters = document.getElementById('clearAllFilters');
        if (clearAllFilters) {
            clearAllFilters.addEventListener('click', () => {
                this.clearAllFilters();
            });
        }

        // Refresh posts
        const refreshPosts = document.getElementById('refreshPosts');
        if (refreshPosts) {
            refreshPosts.addEventListener('click', () => {
                this.loadPosts();
            });
        }

        // Pagination
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('page-link')) {
                e.preventDefault();
                const page = parseInt(e.target.dataset.page);
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadPosts();
                }
            }
        });

        // Post details modal
        document.addEventListener('click', (e) => {
            if (e.target.closest('.view-details-btn')) {
                const postId = e.target.closest('.view-details-btn').dataset.postId;
                this.showPostDetailsModal(postId);
            }
        });

        // Reaction buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.reaction-btn')) {
                const postId = e.target.closest('.reaction-btn').dataset.postId;
                const reactionType = e.target.closest('.reaction-btn').dataset.reactionType;
                this.toggleReaction(postId, reactionType);
            }
        });

        // Comments button
        document.addEventListener('click', (e) => {
            if (e.target.closest('.comments-btn')) {
                const postId = e.target.closest('.comments-btn').dataset.postId;
                this.showCommentsModal(postId);
            }
        });

        // Add comment
        const addCommentBtn = document.getElementById('addCommentBtn');
        const commentInput = document.getElementById('commentInput');
        if (addCommentBtn && commentInput) {
            addCommentBtn.addEventListener('click', () => {
                this.addComment();
            });
            commentInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.addComment();
                }
            });
        }
    }

    async loadPosts() {
        this.showLoading(true);
        
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.postsPerPage,
                ...this.currentFilters
            });

            const response = await fetch(`index.php?controller=UserSocialFeedController&action=getUserPosts&${params}`);
            const data = await response.json();

            if (data.success) {
                this.displayPosts(data.posts);
                this.updatePagination(data.pagination);
                this.updateResultsInfo(data.pagination);
            } else {
                this.showError(data.message || 'Failed to load posts');
            }
        } catch (error) {
            console.error('Error loading posts:', error);
            this.showError('Network error. Please check your connection and try again.');
        } finally {
            this.showLoading(false);
        }
    }

    displayPosts(posts) {
        const container = document.getElementById('postsContainer');
        const noResults = document.getElementById('noResults');

        if (posts.length === 0) {
            container.innerHTML = '';
            noResults.style.display = 'block';
            return;
        }

        noResults.style.display = 'none';
        container.innerHTML = posts.map(post => this.createPostCard(post)).join('');
    }

    createPostCard(post) {
        const mediaHtml = this.createMediaHtml(post.media);
        const linkHtml = this.createLinkHtml(post.link_preview);
        const pollHtml = this.createPollHtml(post.poll, post.id);
        const tagsHtml = this.createTagsHtml(post.tags);
        const reactionsHtml = this.createReactionsHtml(post);

        return `
            <div class="modern-announcement-card mb-4" data-post-id="${post.id}">
                <div class="announcement-card-header">
                    <div class="announcement-title-section">
                        <h5 class="announcement-title">${this.escapeHtml(post.title || 'Untitled Post')}</h5>
                        <div class="announcement-meta">
                            <span class="announcement-author">
                                <img src="${post.author.avatar || 'assets/images/default-avatar.png'}" 
                                     alt="${this.escapeHtml(post.author.name)}" class="author-avatar">
                                <span class="author-name">${this.escapeHtml(post.author.name)}</span>
                            </span>
                            <span class="announcement-time">${post.time_ago}</span>
                        </div>
                    </div>
                    <div class="announcement-badges">
                        ${post.is_pinned ? '<span class="badge bg-warning">Pinned</span>' : ''}
                        <span class="badge bg-info">${this.getPostTypeLabel(post.post_type)}</span>
                        <span class="badge bg-secondary">${this.getVisibilityLabel(post.visibility)}</span>
                    </div>
                </div>
                
                <div class="announcement-card-body">
                    <div class="announcement-description">
                        <div class="description-text">${this.formatPostContent(this.truncateText(post.body, 100))}</div>
                    </div>
                    
                    ${mediaHtml}
                    ${linkHtml}
                    ${pollHtml}
                    ${tagsHtml}
                </div>
                
                <div class="announcement-card-footer">
                    <div class="post-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary reaction-btn" 
                                data-post-id="${post.id}" data-reaction-type="like">
                            <i class="fas fa-thumbs-up me-1"></i>
                            <span class="reaction-count">${post.like_count || 0}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger reaction-btn" 
                                data-post-id="${post.id}" data-reaction-type="love">
                            <i class="fas fa-heart me-1"></i>
                            <span class="reaction-count">${post.love_count || 0}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning reaction-btn" 
                                data-post-id="${post.id}" data-reaction-type="laugh">
                            <i class="fas fa-laugh me-1"></i>
                            <span class="reaction-count">${post.laugh_count || 0}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary reaction-btn" 
                                data-post-id="${post.id}" data-reaction-type="angry">
                            <i class="fas fa-angry me-1"></i>
                            <span class="reaction-count">${post.angry_count || 0}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info comments-btn" 
                                data-post-id="${post.id}">
                            <i class="fas fa-comments me-1"></i>
                            <span class="comment-count">${post.comment_count || 0}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-modern-outline view-details-btn" 
                                data-post-id="${post.id}">
                            <i class="fas fa-eye me-1"></i>View Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    createMediaHtml(media) {
        if (!media || media.length === 0) return '';
        
        return `
            <div class="post-media mt-3">
                <div class="row g-2">
                    ${media.map(file => {
                        // Defensive programming: handle cases where file properties might be undefined
                        const fileType = file.type || file.filetype || '';
                        const fileUrl = file.url || file.filepath || '';
                        const fileName = file.name || file.filename || 'Unknown file';
                        
                        return `
                            <div class="col-md-4">
                                <div class="media-item">
                                    ${fileType.startsWith('image/') ? 
                                        `<img src="${fileUrl}" alt="${fileName}" class="img-fluid rounded">` :
                                        `<div class="media-file d-flex align-items-center p-3 bg-light rounded">
                                            <i class="fas fa-file me-2"></i>
                                            <span>${fileName}</span>
                                        </div>`
                                    }
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    createLinkHtml(linkPreview) {
        if (!linkPreview) return '';
        
        return `
            <div class="post-link mt-3">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">${this.escapeHtml(linkPreview.title || 'Link')}</h6>
                        <p class="card-text text-muted">${this.escapeHtml(linkPreview.description || '')}</p>
                        <a href="${linkPreview.url}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i>Visit Link
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    createPollHtml(poll, postId) {
        if (!poll) return '';
        
        // Check if user has already voted
        const hasVoted = poll.user_selected_options && poll.user_selected_options.length > 0;
        
        return `
            <div class="post-poll mt-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">📊 Poll ${hasVoted ? '<span class="badge bg-success ms-2">Voted</span>' : ''}</h6>
                    </div>
                    <div class="card-body">
                        ${poll.options ? poll.options.map((option, index) => {
                            const isSelected = hasVoted && poll.user_selected_options.includes(index);
                            return `
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="${poll.allow_multiple_votes ? 'checkbox' : 'radio'}" 
                                           name="poll_${postId}" id="poll_${postId}_${index}" value="${index}"
                                           ${isSelected ? 'checked' : ''} ${hasVoted ? 'disabled' : ''}>
                                    <label class="form-check-label ${isSelected ? 'fw-bold text-success' : ''}" for="poll_${postId}_${index}">
                                        ${this.escapeHtml(option)} ${isSelected ? '<i class="fas fa-check-circle ms-1"></i>' : ''}
                                    </label>
                                </div>
                            `;
                        }).join('') : ''}
                        ${!hasVoted ? `
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary btn-sm" onclick="userSocialFeedManager.votePoll(${postId})">
                                    <i class="fas fa-vote-yea me-1"></i>Submit Vote
                                </button>
                            </div>
                        ` : `
                            <div class="mt-3">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>You have already voted on this poll.
                                </div>
                            </div>
                        `}
                    </div>
                </div>
            </div>
        `;
    }

    createTagsHtml(tags) {
        if (!tags || tags.length === 0) return '';
        
        return `
            <div class="post-tags mt-3">
                ${tags.map(tag => `<span class="badge bg-light text-dark me-1">#${this.escapeHtml(tag)}</span>`).join('')}
            </div>
        `;
    }

    createReactionsHtml(post) {
        return ''; // Reactions are handled in the footer
    }

    async showPostDetailsModal(postId) {
        this.currentPostId = postId;
        const modal = new bootstrap.Modal(document.getElementById('postDetailsModal'));
        modal.show();
        
        try {
            const response = await fetch(`index.php?controller=UserSocialFeedController&action=getPostDetails&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayPostDetails(data.post);
            } else {
                document.getElementById('postDetailsContent').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <p class="text-muted">${data.message || 'Failed to load post details'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading post details:', error);
            document.getElementById('postDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                    <p class="text-muted">Error loading post details</p>
                </div>
            `;
        }
    }

    displayPostDetails(post) {
        const content = document.getElementById('postDetailsContent');
        content.innerHTML = `
            <div class="modern-announcement-detail">
                <div class="detail-header">
                    <div class="header-badges">
                        ${post.is_pinned ? '<span class="badge bg-warning">Pinned</span>' : ''}
                        <span class="badge bg-info">${this.getPostTypeLabel(post.post_type)}</span>
                        <span class="badge bg-secondary">${this.getVisibilityLabel(post.visibility)}</span>
                        ${post.status !== 'active' ? `<span class="badge bg-warning">${post.status.charAt(0).toUpperCase() + post.status.slice(1)}</span>` : ''}
                    </div>
                    <h4 class="detail-title">${this.escapeHtml(post.title || 'Untitled Post')}</h4>
                    <div class="detail-meta">
                        <div class="meta-item">
                            <i class="fas fa-user me-1"></i>
                            <span>Created by ${this.escapeHtml(post.author.name)}</span>
                        </div>
                        <div class="meta-divider">•</div>
                        <div class="meta-item">
                            <i class="fas fa-calendar me-1"></i>
                            <span>${post.formatted_date} at ${post.formatted_time}</span>
                        </div>
                        <div class="meta-divider">•</div>
                        <div class="meta-item">
                            <i class="fas fa-eye me-1"></i>
                            <span>${post.visibility === 'global' ? 'Public' : 'Group Specific'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-body">
                    <div class="detail-content">
                        <!-- Main Post Content - Full Width -->
                        <div class="post-content-section">
                            <h6 class="content-title">
                                <i class="fas fa-comment-alt me-2"></i>Post Content
                            </h6>
                            <div class="content-body">
                                ${this.formatPostContent(post.body)}
                            </div>
                        </div>
                        
                        <!-- Media Files - Full Width if present -->
                        ${post.media && post.media.length > 0 ? `
                        <div class="media-section">
                            <h6 class="content-title">
                                <i class="fas fa-images me-2"></i>Media Files (${post.media.length})
                            </h6>
                            <div class="media-preview">
                                ${post.media.map(file => `
                                    <div class="media-item">
                                        ${file.type.startsWith('image/') ? 
                                            `<img src="${file.url}" alt="${file.name}" class="img-thumbnail" style="max-width: 150px; max-height: 150px; cursor: pointer;" onclick="window.open('${file.url}', '_blank')">` :
                                            `<div class="media-file">
                                                <i class="fas fa-file me-2"></i>
                                                <span>${this.escapeHtml(file.name)}</span>
                                                <a href="${file.url}" target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>`
                                        }
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Link Preview - Full Width if present -->
                        ${post.link_preview ? `
                        <div class="link-section">
                            <h6 class="content-title">
                                <i class="fas fa-link me-2"></i>Link Preview
                            </h6>
                            <div class="link-preview">
                                <h6>${this.escapeHtml(post.link_preview.title || 'Link')}</h6>
                                <p class="text-muted">${this.escapeHtml(post.link_preview.description || '')}</p>
                                <a href="${this.escapeHtml(post.link_preview.url)}" target="_blank" class="btn btn-primary">
                                    <i class="fas fa-external-link-alt me-1"></i>Visit Link
                                </a>
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Poll Data - Full Width if present -->
                        ${post.poll ? `
                        <div class="poll-section">
                            <h6 class="content-title">
                                <i class="fas fa-poll me-2"></i>Poll
                                ${post.poll.user_selected_options && post.poll.user_selected_options.length > 0 ? 
                                    '<span class="badge bg-success ms-2">Voted</span>' : ''}
                            </h6>
                            <div class="poll-preview">
                                <ul class="list-unstyled">
                                    ${post.poll.options ? post.poll.options.map((option, index) => {
                                        const isSelected = post.poll.user_selected_options && post.poll.user_selected_options.includes(index);
                                        return `
                                            <li class="poll-option ${isSelected ? 'fw-bold text-success' : ''}">
                                                <i class="fas ${isSelected ? 'fa-check-circle' : 'fa-circle'} me-2" style="font-size: 0.5rem;"></i>
                                                ${this.escapeHtml(option)}
                                                ${isSelected ? '<i class="fas fa-check-circle ms-2 text-success"></i>' : ''}
                                            </li>
                                        `;
                                    }).join('') : ''}
                                </ul>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    ${post.poll.allow_multiple_votes ? 'Multiple votes allowed' : 'Single vote only'}
                                    ${post.poll.user_selected_options && post.poll.user_selected_options.length > 0 ? 
                                        ` • You voted for ${post.poll.user_selected_options.length} option(s)` : ''}
                                </small>
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Tags - Full Width if present -->
                        ${post.tags && post.tags.length > 0 ? `
                        <div class="tags-section">
                            <h6 class="content-title">
                                <i class="fas fa-hashtag me-2"></i>Tags
                            </h6>
                            <div class="tags-container">
                                ${post.tags.map(tag => `<span class="badge bg-light text-dark me-1">#${this.escapeHtml(tag)}</span>`).join('')}
                            </div>
                        </div>
                        ` : ''}
                        
                        <!-- Engagement Section - Full Width at Bottom -->
                        <div class="engagement-section">
                            <h6 class="content-title">
                                <i class="fas fa-heart me-2"></i>Engagement
                            </h6>
                            <div class="engagement-stats-full">
                                <div class="engagement-item like">
                                    <div class="engagement-icon">
                                        <i class="fas fa-thumbs-up"></i>
                                    </div>
                                    <div class="engagement-content">
                                        <div class="engagement-count">${post.like_count || 0}</div>
                                        <div class="engagement-label">Likes</div>
                                    </div>
                                </div>
                                
                                <div class="engagement-item love">
                                    <div class="engagement-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="engagement-content">
                                        <div class="engagement-count">${post.love_count || 0}</div>
                                        <div class="engagement-label">Love</div>
                                    </div>
                                </div>
                                
                                <div class="engagement-item laugh">
                                    <div class="engagement-icon">
                                        <i class="fas fa-laugh"></i>
                                    </div>
                                    <div class="engagement-content">
                                        <div class="engagement-count">${post.laugh_count || 0}</div>
                                        <div class="engagement-label">Laugh</div>
                                    </div>
                                </div>
                                
                                <div class="engagement-item angry">
                                    <div class="engagement-icon">
                                        <i class="fas fa-angry"></i>
                                    </div>
                                    <div class="engagement-content">
                                        <div class="engagement-count">${post.angry_count || 0}</div>
                                        <div class="engagement-label">Angry</div>
                                    </div>
                                </div>
                                
                                <div class="engagement-item comment">
                                    <div class="engagement-icon">
                                        <i class="fas fa-comment"></i>
                                    </div>
                                    <div class="engagement-content">
                                        <div class="engagement-count">${post.comment_count || 0}</div>
                                        <div class="engagement-label">Comments</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Grid Layout for Small Data Items -->
                        <div class="event-info-grid">
                            <!-- Post Type -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-tag"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Post Type</div>
                                    <div class="info-value">${this.getPostTypeLabel(post.post_type)}</div>
                                </div>
                            </div>
                            
                            <!-- Visibility -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-eye"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Visibility</div>
                                    <div class="info-value">${this.getVisibilityLabel(post.visibility)}</div>
                                </div>
                            </div>
                            
                            <!-- Target Audience (if group specific) -->
                            ${post.custom_field_id && post.custom_field_value ? `
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Target Audience</div>
                                    <div class="info-value">${this.escapeHtml(post.custom_field_value)}</div>
                                </div>
                            </div>
                            ` : ''}
                            
                            <!-- Post Settings -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-cog"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Settings</div>
                                    <div class="info-value">
                                        <div class="settings-info">
                                            <span class="me-2"><i class="fas fa-thumbtack me-1"></i>${post.is_pinned ? 'Pinned' : 'Not Pinned'}</span>
                                            <span class="me-2"><i class="fas fa-comments me-1"></i>${post.comments_locked ? 'Locked' : 'Open'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Timestamps -->
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Created</div>
                                    <div class="info-value">${post.formatted_date}</div>
                                </div>
                            </div>
                            
                            ${post.updated_at !== post.created_at ? `
                            <div class="info-item">
                                <div class="info-icon">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="info-content">
                                    <div class="info-label">Updated</div>
                                    <div class="info-value">${this.formatDate(post.updated_at)}</div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async showCommentsModal(postId) {
        this.currentPostId = postId;
        const modal = new bootstrap.Modal(document.getElementById('commentsModal'));
        modal.show();
        
        try {
            const response = await fetch(`index.php?controller=UserSocialFeedController&action=getComments&post_id=${postId}`);
            const data = await response.json();
            
            if (data.success) {
                this.displayComments(data.comments);
            } else {
                document.getElementById('commentsContainer').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                        <p class="text-muted">${data.message || 'Failed to load comments'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            document.getElementById('commentsContainer').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-3"></i>
                    <p class="text-muted">Error loading comments</p>
                </div>
            `;
        }
    }

    displayComments(comments) {
        const container = document.getElementById('commentsContainer');
        
        if (comments.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No comments yet. Be the first to comment!</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = comments.map(comment => `
            <div class="comment-item mb-3 p-3 border rounded">
                <div class="d-flex align-items-start">
                    <img src="${comment.author.avatar || 'assets/images/default-avatar.png'}" 
                         alt="${this.escapeHtml(comment.author.name)}" 
                         class="comment-avatar me-3">
                    <div class="flex-grow-1">
                        <div class="comment-header">
                            <strong>${this.escapeHtml(comment.author.name)}</strong>
                            <small class="text-muted ms-2">${comment.time_ago}</small>
                        </div>
                        <div class="comment-content mt-1">
                            ${this.escapeHtml(comment.content)}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    async addComment() {
        const input = document.getElementById('commentInput');
        const content = input.value.trim();
        
        if (!content || !this.currentPostId) return;
        
        try {
            const formData = new FormData();
            formData.append('post_id', this.currentPostId);
            formData.append('content', content);
            
            const response = await fetch('index.php?controller=UserSocialFeedController&action=addComment', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                input.value = '';
                this.showCommentsModal(this.currentPostId); // Reload comments
                this.loadPosts(); // Reload posts to update comment count
            } else {
                this.showError(data.message || 'Failed to add comment');
            }
        } catch (error) {
            console.error('Error adding comment:', error);
            this.showError('Error adding comment');
        }
    }

    async votePoll(pollId) {
        // Find the poll container - look for the specific poll
        const pollContainer = document.querySelector(`.post-poll`);
        if (!pollContainer) {
            this.showError('Poll not found');
            return;
        }

        // Get selected options
        const selectedOptions = [];
        const checkboxes = pollContainer.querySelectorAll(`input[name="poll_${pollId}"]:checked`);
        
        if (checkboxes.length === 0) {
            this.showError('Please select at least one option');
            return;
        }

        checkboxes.forEach(checkbox => {
            selectedOptions.push(parseInt(checkbox.value));
        });

        try {
            const formData = new FormData();
            formData.append('poll_id', pollId);
            formData.append('options', JSON.stringify(selectedOptions));
            
            console.log('Submitting poll vote:', {
                pollId: pollId,
                selectedOptions: selectedOptions
            });
            
            const response = await fetch('index.php?controller=UserSocialFeedController&action=votePoll', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const responseText = await response.text();
            console.log('Response text:', responseText);
            
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                throw new Error('Invalid JSON response from server');
            }
            
            console.log('Parsed data:', data);
            
            if (data.success) {
                this.showSuccess('Vote submitted successfully!');
                this.loadPosts(); // Reload posts to update poll results
            } else {
                this.showError(data.message || 'Failed to submit vote');
            }
        } catch (error) {
            console.error('Error voting on poll:', error);
            this.showError('Error submitting vote: ' + error.message);
        }
    }

    async toggleReaction(postId, reactionType) {
        try {
            const formData = new FormData();
            formData.append('post_id', postId);
            formData.append('reaction_type', reactionType);
            
            const response = await fetch('index.php?controller=UserSocialFeedController&action=toggleReaction', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.loadPosts(); // Reload posts to update reaction counts
            } else {
                this.showError(data.message || 'Failed to process reaction');
            }
        } catch (error) {
            console.error('Error toggling reaction:', error);
            this.showError('Error processing reaction');
        }
    }

    updatePagination(pagination) {
        const container = document.getElementById('pagination');
        if (!container) return;
        
        if (pagination.total_pages <= 1) {
            container.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        if (pagination.has_prev) {
            html += `<li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Previous</a>
            </li>`;
        }
        
        // Page numbers
        for (let i = 1; i <= pagination.total_pages; i++) {
            if (i === pagination.current_page) {
                html += `<li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>`;
            } else {
                html += `<li class="page-item">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            }
        }
        
        // Next button
        if (pagination.has_next) {
            html += `<li class="page-item">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Next</a>
            </li>`;
        }
        
        container.innerHTML = html;
    }

    updateResultsInfo(pagination) {
        const info = document.getElementById('resultsInfo');
        if (!info) return;
        
        const start = (pagination.current_page - 1) * pagination.per_page + 1;
        const end = Math.min(pagination.current_page * pagination.per_page, pagination.total_posts);
        
        info.innerHTML = `Showing ${start}-${end} of ${pagination.total_posts} posts`;
    }

    clearAllFilters() {
        // Reset all filter inputs
        document.getElementById('searchInput').value = '';
        document.getElementById('postTypeFilter').value = '';
        document.getElementById('visibilityFilter').value = '';
        document.getElementById('pinnedFilter').value = '';
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value = '';
        document.getElementById('dateRangeInputs').style.display = 'none';
        
        // Reset filter state
        this.currentFilters = {
            search: '',
            post_type: '',
            visibility: '',
            pinned: '',
            date_from: '',
            date_to: ''
        };
        
        this.currentPage = 1;
        this.loadPosts();
    }

    showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        const container = document.getElementById('postsContainer');
        
        if (show) {
            spinner.style.display = 'block';
            container.innerHTML = '';
        } else {
            spinner.style.display = 'none';
        }
    }

    showError(message) {
        const container = document.getElementById('postsContainer');
        container.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${this.escapeHtml(message)}
            </div>
        `;
    }

    showSuccess(message) {
        // Use toast notification if available, otherwise show in container
        if (typeof showSimpleToast === 'function') {
            showSimpleToast(message, 'success');
        } else {
            const container = document.getElementById('postsContainer');
            container.innerHTML = `
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    ${this.escapeHtml(message)}
                </div>
            `;
        }
    }

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatPostContent(content) {
        // Convert line breaks to HTML
        return this.escapeHtml(content).replace(/\n/g, '<br>');
    }

    truncateText(text, maxLength) {
        // First normalize the text by replacing multiple line breaks with single spaces
        const normalizedText = text.replace(/\s+/g, ' ').trim();
        
        if (normalizedText.length <= maxLength) {
            return normalizedText;
        }
        
        // Find the last space before the max length to avoid cutting words
        let truncateAt = maxLength;
        const lastSpace = normalizedText.lastIndexOf(' ', maxLength);
        if (lastSpace > maxLength * 0.8) { // Only use last space if it's not too far back
            truncateAt = lastSpace;
        }
        
        // Truncate the text
        const truncated = normalizedText.substring(0, truncateAt);
        return truncated + '...';
    }

    getPostTypeIcon(type) {
        const icons = {
            'text': '📝',
            'media': '🖼️',
            'poll': '📊',
            'link': '🔗'
        };
        return icons[type] || '📄';
    }

    getPostTypeLabel(type) {
        const labels = {
            'text': 'General',
            'media': 'Media',
            'poll': 'Poll',
            'link': 'Link'
        };
        return labels[type] || 'Post';
    }

    getVisibilityLabel(visibility) {
        const labels = {
            'global': 'Global',
            'group_specific': 'Group Specific'
        };
        return labels[visibility] || 'Unknown';
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.userSocialFeedManager = new UserSocialFeedManager();
});
