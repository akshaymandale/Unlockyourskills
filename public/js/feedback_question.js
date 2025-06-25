document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('feedbackQuestionType');
    const optionsWrapper = document.getElementById('feedbackOptionsWrapper');
    const ratingWrapper = document.getElementById('feedbackRatingWrapper');
    const questionMediaInput = document.getElementById('feedbackQuestionMedia');
    const questionPreview = document.getElementById('feedbackQuestionPreview');

    const tagInput = document.getElementById('feedbackTagInput');
    const tagDisplay = document.getElementById('feedbackTagDisplay');
    const tagListInput = document.getElementById('feedbackTagList');
    let tags = [];

    typeSelect.addEventListener('change', updateOptionsUI);
    questionMediaInput.addEventListener('change', () =>
        showPreview(questionMediaInput, questionPreview, 'question-preview')
    );

    function updateOptionsUI() {
        const type = typeSelect.value;
        optionsWrapper.innerHTML = '';
        ratingWrapper.classList.toggle('d-none', type !== 'rating');

        // Remove the "Add Another Option" button if it exists
        const existingBtn = document.getElementById('addMoreFeedbackOptionsBtn');
        if (existingBtn) {
            existingBtn.remove();
        }

        if (['multi_choice', 'checkbox', 'dropdown'].includes(type)) {
            const isRadio = type === 'multi_choice';
            const isDropdown = type === 'dropdown';
            addOptionField(isRadio, isDropdown);
        } else if (type === 'short_answer') {
            optionsWrapper.innerHTML = `<input type="text" class="form-control" placeholder="User will enter short answer here" disabled>`;
        } else if (type === 'long_answer') {
            optionsWrapper.innerHTML = `<textarea class="form-control" placeholder="User will enter long answer here" disabled></textarea>`;
        }
    }

    function addOptionField(isRadio, isDropdown, optionText = '', mediaPath = '', optionId = null) {
        const optionBlock = document.createElement('div');
        optionBlock.classList.add('mb-2', 'option-block');

        optionBlock.innerHTML = `
        <div class="d-flex align-items-center gap-2 mb-2">
            <input type="${isRadio ? 'radio' : 'checkbox'}" disabled>
            <input type="text" class="form-control option-text" name="optionText[]" placeholder="Option label" value="${optionText}">
            ${optionId ? `<input type="hidden" name="optionId[]" value="${optionId}">` : '<input type="hidden" name="optionId[]" value="">'}
            ${!isDropdown ? `
                <label class="btn btn-outline-secondary mb-0">
                    <i class="fas fa-upload"></i>
                    <input type="file" class="d-none option-file" name="optionMedia[]" accept="image/*,video/*,.pdf">
                </label>
            ` : ''}
            <button type="button" class="btn btn-outline-danger btn-sm remove-option">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="preview-container option-preview mt-1"></div>
        `;

        const addBtn = document.getElementById('addMoreFeedbackOptionsBtn');
        if (addBtn) {
            optionsWrapper.insertBefore(optionBlock, addBtn);
        } else {
            optionsWrapper.appendChild(optionBlock);
        }

        const fileInput = optionBlock.querySelector('.option-file');
        const preview = optionBlock.querySelector('.option-preview');
        if (fileInput) {
            fileInput.addEventListener('change', () => showPreview(fileInput, preview, 'option-preview'));
        }

        // Show existing media preview if mediaPath is provided
        if (mediaPath && !isDropdown) {
            showExistingMediaPreview(mediaPath, preview);
        }

        const removeBtn = optionBlock.querySelector('.remove-option');
        removeBtn.addEventListener('click', () => optionBlock.remove());

        // Only add the "Add Another Option" button if we're dealing with option-based question types
        const currentType = document.getElementById('feedbackQuestionType').value;
        if (['multi_choice', 'checkbox', 'dropdown'].includes(currentType)) {
            if (!document.getElementById('addMoreFeedbackOptionsBtn')) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.id = 'addMoreFeedbackOptionsBtn';
                btn.className = 'btn btn-sm btn-primary mt-3';
                btn.innerHTML = 'Add Another Option';
                btn.addEventListener('click', () => addOptionField(isRadio, isDropdown, '', '', null));
                optionsWrapper.appendChild(btn);
            } else {
                // Ensure button stays last
                const existingBtn = document.getElementById('addMoreFeedbackOptionsBtn');
                if (existingBtn) {
                    optionsWrapper.appendChild(existingBtn);
                }
            }
        }

        return optionBlock; // Return for usage in edit
    }

    function showPreview(input, container, type) {
        container.innerHTML = '';
        const file = input.files[0];
        if (!file) return;

        let element;
        const fileType = file.type;

        if (fileType.startsWith('image')) {
            element = document.createElement('img');
            element.src = URL.createObjectURL(file);
        } else if (fileType.startsWith('video')) {
            element = document.createElement('video');
            element.src = URL.createObjectURL(file);
            element.controls = true;
        } else if (fileType === 'application/pdf') {
            element = document.createElement('iframe');
            element.src = URL.createObjectURL(file);
        }

        if (!element) return;

        const containerDiv = document.createElement('div');
        containerDiv.classList.add('preview-wrapper', type);

        const crossBtn = document.createElement('button');
        crossBtn.innerHTML = '&times;';
        crossBtn.className = 'remove-preview';
        crossBtn.style.borderRadius = '4px'; // square cross
        crossBtn.addEventListener('click', () => {
            container.innerHTML = '';
            input.value = '';
        });

        containerDiv.appendChild(element);
        containerDiv.appendChild(crossBtn);
        container.appendChild(containerDiv);
    }

    function showExistingMediaPreview(mediaPath, container) {
        container.innerHTML = '';

        const ext = mediaPath.split('.').pop().toLowerCase();
        let element;

        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
            element = document.createElement('img');
        } else if (['mp4', 'webm'].includes(ext)) {
            element = document.createElement('video');
            element.controls = true;
        } else if (ext === 'pdf') {
            element = document.createElement('iframe');
        }

        if (!element) return;

        element.src = 'uploads/feedback/' + mediaPath;

        const containerDiv = document.createElement('div');
        containerDiv.classList.add('preview-wrapper', 'option-preview');

        const crossBtn = document.createElement('button');
        crossBtn.innerHTML = '&times;';
        crossBtn.className = 'remove-preview';
        crossBtn.style.borderRadius = '4px';
        crossBtn.addEventListener('click', () => {
            container.innerHTML = '';
            // Also clear any associated file input if needed
        });

        containerDiv.appendChild(element);
        containerDiv.appendChild(crossBtn);
        container.appendChild(containerDiv);
    }

    function updateTagListInput() {
        tagListInput.value = tags.join(',');
    }

    function addTag(tag) {
        const trimmed = tag.trim();
        if (trimmed && !tags.includes(trimmed)) {
            tags.push(trimmed);
            renderTags();
            updateTagListInput();
        }
    }

    function removeTag(index) {
        tags.splice(index, 1);
        renderTags();
        updateTagListInput();
    }

    function renderTags() {
        tagDisplay.innerHTML = '';
        tags.forEach((tag, i) => {
            const span = document.createElement('span');
            span.className = 'tag';
            span.innerHTML = `${tag} <button type="button" class="remove-tag" data-tag="${tag}">&times;</button>`;
            span.querySelector('button').addEventListener('click', () => removeTag(i));
            tagDisplay.appendChild(span);
        });
    }

    tagInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addTag(tagInput.value);
            tagInput.value = '';
        }
    });

    // Flag to track if we're in edit mode
    let isEditMode = false;

    // Reset modal fields and previews on open
    const feedbackModal = document.getElementById('addFeedbackQuestionModal');
    feedbackModal.addEventListener('show.bs.modal', () => {
        // Only reset if not in edit mode
        if (!isEditMode) {
            document.getElementById('feedbackQuestionForm').reset();
            questionPreview.innerHTML = '';
            questionMediaInput.value = '';
            $('#existingFeedbackQuestionMedia').val('');
            typeSelect.value = 'multi_choice';
            updateOptionsUI();
            ratingWrapper.classList.add('d-none');
            feedbackModal.querySelector('.modal-body').scrollTop = 0;

            // Reset tags
            tags = [];
            renderTags();
            updateTagListInput();

            // Reset modal title
            const modalTitle = document.getElementById('addFeedbackQuestionModalLabel');
            if (modalTitle) {
                modalTitle.textContent = 'Add Feedback Question';
            }
        }
        // Reset the edit mode flag after modal is shown
        isEditMode = false;
    });



    // Attach edit button events dynamically (in case buttons are dynamically loaded)
    document.body.addEventListener('click', function (e) {
        if (e.target.closest('.edit-question-btn')) {
            const btn = e.target.closest('.edit-question-btn');
            const questionId = btn.getAttribute('data-question-id');
            if (questionId) {
                // Set edit mode flag
                isEditMode = true;

                // Show modal first, then fetch and populate data
                const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addFeedbackQuestionModal'));
                modal.show();

                // Fetch complete question data via AJAX after modal is shown
                setTimeout(() => {
                    fetchAndEditQuestion(questionId);
                }, 100); // Small delay to ensure modal is fully shown
            } else {
                console.error('Question ID not found on button');
            }
        }
    });

    // Function to fetch question data and populate edit form
    function fetchAndEditQuestion(questionId) {
        fetch(`/unlockyourskills/feedback/${questionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error fetching question:', data.error);
                    alert('Error loading question data: ' + data.error);
                    return;
                }

                // Use the question data directly (options are already included)
                const questionData = data.question;



                // Call the edit function with complete data
                window.editFeedbackQuestion(questionData);
            })
            .catch(error => {
                console.error('Error fetching question:', error);
                alert('Error loading question data. Please try again.');
            });
    }

    window.editFeedbackQuestion = function (data) {
        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;

        const {
            id,
            title,
            type,
            media_path,
            tags: existingTags,
            options,
            rating_scale,
            rating_symbol
        } = parsedData;

        // Set existing media hidden input
        $('#existingFeedbackQuestionMedia').val(media_path || '');

        const form = document.getElementById('feedbackQuestionForm');
        form.querySelector('[name="feedbackQuestionId"]').value = id || '';
        form.querySelector('[name="feedbackQuestionTitle"]').value = title || '';
        form.querySelector('[name="feedbackQuestionType"]').value = type || '';

        // Update modal title
        const modalTitle = document.getElementById('addFeedbackQuestionModalLabel');
        if (modalTitle) {
            modalTitle.textContent = 'Edit Feedback Question';
        }

        // Show media preview
        const questionPreview = document.getElementById('feedbackQuestionPreview');
        const questionMediaInput = document.getElementById('feedbackQuestionMedia');
        questionPreview.innerHTML = '';

        if (media_path) {
            const ext = media_path.split('.').pop().toLowerCase();
            let element;

            if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                element = document.createElement('img');
            } else if (['mp4', 'webm'].includes(ext)) {
                element = document.createElement('video');
                element.controls = true;
            } else if (ext === 'pdf') {
                element = document.createElement('iframe');
            }

            if (element) {
                element.src = 'uploads/feedback/' + media_path;
                const wrapper = document.createElement('div');
                wrapper.classList.add('preview-wrapper', 'question-preview');

                const crossBtn = document.createElement('button');
                crossBtn.innerHTML = '&times;';
                crossBtn.className = 'remove-preview';
                crossBtn.addEventListener('click', () => {
                    questionPreview.innerHTML = '';
                    questionMediaInput.value = '';
                    $('#existingFeedbackQuestionMedia').val('');
                });

                wrapper.appendChild(element);
                wrapper.appendChild(crossBtn);
                questionPreview.appendChild(wrapper);
            }
        }

        // Tags
        tags = existingTags ? existingTags.split(',').map(tag => tag.trim()) : [];
        renderTags();
        updateTagListInput();

        // Update the type select to trigger options UI update
        typeSelect.value = type || 'multi_choice';

        // Options - Clear and populate from database for edit
        const optionsWrapper = document.getElementById('feedbackOptionsWrapper');

        // Clear existing options first
        optionsWrapper.innerHTML = '';

        if (['multi_choice', 'checkbox', 'dropdown'].includes(type) && Array.isArray(options)) {
            const isRadio = type === 'multi_choice';
            const isDropdown = type === 'dropdown';

            options.forEach(opt => {
                const optText = opt.option_text || '';
                const mediaPath = opt.media_path || '';
                const optionId = opt.id || null;

                const optionField = addOptionField(isRadio, isDropdown, optText, mediaPath, optionId);

                if (mediaPath) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'existingOptionMedia[]';
                    hiddenInput.value = mediaPath;
                    hiddenInput.setAttribute('data-media', mediaPath);
                    optionField.appendChild(hiddenInput);
                }
            });
        }

        // Rating scale/symbol
        const ratingWrapper = document.getElementById('feedbackRatingWrapper');
        const ratingScale = document.getElementById('feedbackRatingScale');
        const ratingSymbol = document.getElementById('feedbackRatingSymbol');

        if (type === 'rating') {
            ratingWrapper.classList.remove('d-none');
            ratingScale.value = rating_scale || 5;
            ratingSymbol.value = rating_symbol || 'star';
        } else {
            ratingWrapper.classList.add('d-none');
        }
    };



    // Initialize on load
    updateOptionsUI();

    // ✅ Search and Filter Functionality
    // Global variables to track current state
    let currentPage = 1;
    let currentSearch = '';
    let currentFilters = {
        question_type: '',
        tags: ''
    };

    // Debounce function for search input
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

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');

    if (searchInput && searchButton) {
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Add debounced search on input
        const debouncedSearch = debounce(performSearch, 500);
        searchInput.addEventListener('input', debouncedSearch);
    }

    // Filter functionality
    const questionTypeFilter = document.getElementById('questionTypeFilter');
    if (questionTypeFilter) {
        questionTypeFilter.addEventListener('change', applyFilters);
    }

    // Tags filter with debounced input
    const tagsFilter = document.getElementById('tagsFilter');
    if (tagsFilter) {
        const debouncedTagsFilter = debounce(applyFilters, 500);
        tagsFilter.addEventListener('input', debouncedTagsFilter);
        tagsFilter.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    }

    // Clear filters functionality
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }

    // Pagination functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link[data-page]')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            loadQuestions(page);
        }
    });

    // ✅ Load initial questions on page load
    // Check if we're on the feedback questions page
    if (document.getElementById('questionsTableBody')) {
        // Load questions immediately when page loads
        loadQuestions(1);
    }

    function performSearch() {
        if (searchInput) {
            currentSearch = searchInput.value.trim();
            currentPage = 1; // Reset to first page
            loadQuestions();
        }
    }

    function applyFilters() {
        currentFilters.question_type = questionTypeFilter ? questionTypeFilter.value : '';
        currentFilters.tags = tagsFilter ? tagsFilter.value : '';
        currentPage = 1; // Reset to first page
        loadQuestions();
    }

    function clearAllFilters() {
        // Clear search
        if (searchInput) {
            searchInput.value = '';
            currentSearch = '';
        }

        // Clear filters
        if (questionTypeFilter) questionTypeFilter.value = '';
        if (tagsFilter) tagsFilter.value = '';
        currentFilters = {
            question_type: '',
            tags: ''
        };

        currentPage = 1;
        loadQuestions();
    }

    function loadQuestions(page = currentPage) {
        currentPage = page;

        // Show loading indicator
        const loadingIndicator = document.getElementById('loadingIndicator');
        const questionsContainer = document.getElementById('questionsContainer');
        const paginationContainer = document.getElementById('paginationContainer');

        if (loadingIndicator) loadingIndicator.style.display = 'block';
        if (questionsContainer) questionsContainer.style.display = 'none';
        if (paginationContainer) paginationContainer.style.display = 'none';

        // Prepare data for AJAX request
        const formData = new FormData();
        formData.append('controller', 'FeedbackQuestionController');
        formData.append('action', 'ajaxSearch');
        formData.append('page', currentPage);
        formData.append('search', currentSearch);
        formData.append('type', currentFilters.question_type);
        formData.append('tags', currentFilters.tags);

        // Make AJAX request
        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateQuestionsTable(data.questions);
                updatePagination(data.pagination);
                updateSearchInfo(data.totalQuestions);
            } else {
                console.error('Error loading questions:', data.message);
                alert('Error loading questions: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            alert('Network error. Please try again.');
        })
        .finally(() => {
            // Hide loading indicator
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            if (questionsContainer) questionsContainer.style.display = 'block';
            if (paginationContainer) paginationContainer.style.display = 'block';
        });
    }

    function updateQuestionsTable(questions) {
        const tbody = document.getElementById('questionsTableBody');
        if (!tbody) return;

        tbody.innerHTML = '';

        if (questions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="no-results-message">
                        <i class="fas fa-search"></i>
                        <div>
                            <h5>No questions found</h5>
                            <p>Try adjusting your search terms or filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        questions.forEach(question => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(question.title)}</td>
                <td>${escapeHtml(question.type)}</td>
                <td>${escapeHtml(question.tags)}</td>
                <td>
                    <button type="button" class="btn theme-btn-primary edit-question-btn"
                        data-question-id="${question.id}"
                        title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="#" class="btn theme-btn-danger delete-feedback-question"
                        data-id="${question.id}"
                        data-title="${escapeHtml(question.title)}"
                        title="Delete">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function updatePagination(pagination) {
        const container = document.getElementById('paginationContainer');
        if (!container) return;

        // Only show pagination if there are more than 10 total questions
        if (pagination.totalQuestions <= 10) {
            if (pagination.totalQuestions > 0) {
                // Show total count when no pagination needed
                const plural = pagination.totalQuestions !== 1 ? 's' : '';
                container.innerHTML = `
                    <div class="text-center text-muted small">
                        Showing all ${pagination.totalQuestions} question${plural}
                    </div>
                `;
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
            return;
        }

        container.style.display = 'block';

        // Create pagination navigation
        let paginationHTML = '<nav><ul class="pagination justify-content-center" id="paginationList">';

        // Previous button
        if (pagination.currentPage > 1) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">« Previous</a>
                </li>
            `;
        }

        // Page numbers
        for (let i = 1; i <= pagination.totalPages; i++) {
            const activeClass = i === pagination.currentPage ? 'active' : '';
            paginationHTML += `
                <li class="page-item ${activeClass}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next button
        if (pagination.currentPage < pagination.totalPages) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Next »</a>
                </li>
            `;
        }

        paginationHTML += '</ul></nav>';
        container.innerHTML = paginationHTML;
    }

    function updateSearchInfo(totalQuestions) {
        const searchInfo = document.getElementById('searchResultsInfo');
        const resultsText = document.getElementById('resultsText');

        if (!searchInfo || !resultsText) return;

        if (currentSearch || currentFilters.question_type || currentFilters.tags) {
            let infoText = `Showing ${totalQuestions} result(s)`;

            if (currentSearch) {
                infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
            }

            if (currentFilters.question_type || currentFilters.tags) {
                infoText += ' with filters applied';
            }

            resultsText.innerHTML = infoText;
            searchInfo.style.display = 'block';
        } else {
            searchInfo.style.display = 'none';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

});
