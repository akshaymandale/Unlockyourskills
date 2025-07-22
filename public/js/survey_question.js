document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('surveyQuestionType');
    const optionsWrapper = document.getElementById('surveyOptionsWrapper');
    const ratingWrapper = document.getElementById('ratingWrapper');
    const ratingScale = document.getElementById('ratingScale');
    const ratingSymbol = document.getElementById('ratingSymbol');
    const questionMediaInput = document.getElementById('surveyQuestionMedia');
    const questionPreview = document.getElementById('surveyQuestionPreview');
    const tagInput = document.getElementById('tagInput');
    const tagDisplay = document.getElementById('tagDisplay');
    const tagListInput = document.getElementById('tagList');
    let tags = [];

    function showPreview(input, container, type) {
        const file = input.files[0];
        if (!file) return;

        container.innerHTML = '';
        const fileType = file.type;
        let element;

        if (fileType.startsWith('image')) {
            element = document.createElement('img');
        } else if (fileType.startsWith('video')) {
            element = document.createElement('video');
            element.controls = true;
        } else if (fileType === 'application/pdf') {
            element = document.createElement('iframe');
        }

        if (element) {
            element.src = URL.createObjectURL(file);
            const wrapper = document.createElement('div');
            wrapper.classList.add('preview-wrapper', type);
            const crossBtn = document.createElement('button');
            crossBtn.innerHTML = '&times;';
            crossBtn.className = 'remove-preview';
            crossBtn.addEventListener('click', () => {
                container.innerHTML = '';
                input.value = '';
                // If this is the question media input, clear hidden input as well
                if (input === questionMediaInput) {
                    $('#existingSurveyQuestionMedia').val('');
                }
            });
            wrapper.appendChild(element);
            wrapper.appendChild(crossBtn);
            container.appendChild(wrapper);
        }
    }

    // Returns the created option block element
    function addOptionField(isRadio, isDropdown, value = '', mediaUrl = '') {
        const optionBlock = document.createElement('div');
        optionBlock.classList.add('mb-2', 'option-block');
        optionBlock.innerHTML = `
            <div class="d-flex align-items-center gap-2 mb-2">
                <input type="${isRadio ? 'radio' : 'checkbox'}" disabled>
                <input type="text" class="form-control option-text" name="optionText[]" placeholder="Option label" value="${value}">
                ${!isDropdown ? `
                    <label class="btn btn-outline-secondary mb-0">
                        <i class="fas fa-upload"></i>
                        <input type="file" class="d-none option-file" name="optionMedia[]" accept="image/*,video/*,.pdf">
                    </label>` : ''}
                <button type="button" class="btn btn-outline-danger btn-sm remove-option">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="preview-container option-preview mt-1"></div>
        `;

        const addBtn = document.getElementById('addMoreOptionsBtn');
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

        const removeBtn = optionBlock.querySelector('.remove-option');
        removeBtn.addEventListener('click', () => optionBlock.remove());

        if (!document.getElementById('addMoreOptionsBtn')) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.id = 'addMoreOptionsBtn';
            btn.className = 'btn btn-sm btn-primary mt-3';
            btn.textContent = 'Add Another Option';
            btn.addEventListener('click', () => addOptionField(isRadio, isDropdown));
            optionsWrapper.appendChild(btn);
        }

        if (mediaUrl && preview) {
            const ext = mediaUrl.split('.').pop().toLowerCase();
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
                element.src = mediaUrl;
                const wrapper = document.createElement('div');
                wrapper.classList.add('preview-wrapper', 'option-preview');
                const crossBtn = document.createElement('button');
                crossBtn.innerHTML = '&times;';
                crossBtn.className = 'remove-preview';

                crossBtn.addEventListener('click', () => {
                    preview.innerHTML = '';
                    if (fileInput) fileInput.value = '';

                    // Remove hidden input named existingOptionMedia[] inside this optionBlock
                    const hiddenInput = optionBlock.querySelector('input[name="existingOptionMedia[]"]');
                    if (hiddenInput) hiddenInput.value = '0';
                });

                wrapper.appendChild(element);
                wrapper.appendChild(crossBtn);
                preview.appendChild(wrapper);
            }
        }

        return optionBlock;
    }

    function updateOptionsUI() {
        const type = typeSelect.value;
        optionsWrapper.innerHTML = '';
        ratingWrapper.classList.toggle('d-none', type !== 'rating');

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

    typeSelect.addEventListener('change', updateOptionsUI);
    questionMediaInput.addEventListener('change', () => showPreview(questionMediaInput, questionPreview, 'question-preview'));

    const surveyModal = document.getElementById('addSurveyQuestionModal');
    surveyModal.addEventListener('show.bs.modal', () => {
        document.getElementById('addSurveyQuestionForm').reset();
        questionPreview.innerHTML = '';
        optionsWrapper.innerHTML = '';
        tags = [];
        renderTags();
        updateTagListInput();
        questionMediaInput.value = '';
        typeSelect.value = 'multi_choice';
        updateOptionsUI();
        ratingWrapper.classList.add('d-none');
    });

    // Add event listener for when modal is hidden to clean up backdrop
    surveyModal.addEventListener('hidden.bs.modal', () => {
        // Remove backdrop manually if it persists
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
        
        // Remove modal-open class from body
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    });

    document.querySelectorAll('.edit-question-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const questionData = btn.getAttribute('data-question');
            if (questionData) {
                setTimeout(() => {
                    window.editSurveyQuestion(questionData);
                }, 300);
            }
        });
    });

    window.editSurveyQuestion = function (data) {
        let parsedData = typeof data === 'string' ? JSON.parse(data) : data;

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

        $('#existingSurveyQuestionMedia').val(media_path || '');

        const form = document.getElementById('addSurveyQuestionForm');
        const surveyQuestionIdField = form.querySelector('[name="surveyQuestionId"]');
        surveyQuestionIdField.value = id || '';
        
        form.querySelector('[name="surveyQuestionTitle"]').value = title || '';
        typeSelect.value = type;
        updateOptionsUI();

        // Show media preview for question
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
                element.src = 'uploads/survey/' + media_path;
                const wrapper = document.createElement('div');
                wrapper.classList.add('preview-wrapper', 'question-preview');
                const crossBtn = document.createElement('button');
                crossBtn.innerHTML = '&times;';
                crossBtn.className = 'remove-preview';
                crossBtn.addEventListener('click', () => {
                    questionPreview.innerHTML = '';
                    questionMediaInput.value = '';
                    $('#existingSurveyQuestionMedia').val('');
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

        // Options
        optionsWrapper.innerHTML = '';
        if (['multi_choice', 'checkbox', 'dropdown'].includes(type) && Array.isArray(options)) {
            const isRadio = type === 'multi_choice';
            const isDropdown = type === 'dropdown';
            options.forEach(opt => {
                const optText = opt.option_text || '';
                const mediaPath = opt.media_path || '';

                // Add option field
                const optionField = addOptionField(isRadio, isDropdown, optText, mediaPath ? 'uploads/survey/' + mediaPath : '');

                // Add hidden input to track existing media
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

        // Rating logic
        if (type === 'rating') {
            ratingWrapper.classList.remove('d-none');
            ratingScale.value = rating_scale || 5;
            ratingSymbol.value = rating_symbol || 'star';
        } else {
            ratingWrapper.classList.add('d-none');
        }
    }

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
    // Check if we're on the survey questions page
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
        formData.append('controller', 'SurveyQuestionController');
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
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
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
                    <button type="button" class="btn theme-btn-primary edit-question-btn" data-bs-toggle="modal"
                      data-bs-target="#addSurveyQuestionModal" data-mode="edit"
                      data-question='${escapeHtml(JSON.stringify(question))}'
                      data-options='${escapeHtml(JSON.stringify(question.options || []))}'
                      title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <a href="#" class="btn theme-btn-danger delete-survey-question"
                      data-id="${question.id}"
                      data-title="${escapeHtml(question.title)}"
                      title="Delete">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(row);

            // Re-attach event listeners for edit buttons
            const editBtn = row.querySelector('.edit-question-btn');
            if (editBtn) {
                editBtn.addEventListener('click', () => {
                    const questionData = editBtn.getAttribute('data-question');
                    if (questionData) {
                        setTimeout(() => {
                            window.editSurveyQuestion(questionData);
                        }, 300);
                    }
                });
            }
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

    // ✅ Survey Question Delete Confirmations now handled by survey_confirmations.js module

    // Form submission
    document.getElementById('addSurveyQuestionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Disable submit button to prevent double submission
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

        const formData = new FormData(this);

        fetch('/unlockyourskills/surveys', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                // Show success message
                if (typeof showToast === 'function') {
                    showToast.success(data.message || 'Survey question saved successfully!');
                } else {
                    // Fallback alert if showToast is not available
                    alert(data.message || 'Survey question saved successfully!');
                }
                
                // Close modal and remove backdrop
                const modal = bootstrap.Modal.getInstance(document.getElementById('addSurveyQuestionModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Remove backdrop manually if it persists
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                
                // Remove modal-open class from body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Refresh the questions table
                if (typeof loadQuestions === 'function') {
                    loadQuestions(1);
                }
            } else {
                // Show error message
                if (typeof showToast === 'function') {
                    showToast.error(data.message || 'Failed to save survey question. Please try again.');
                } else {
                    // Fallback alert if showToast is not available
                    alert(data.message || 'Failed to save survey question. Please try again.');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast.error('An error occurred while saving the survey question.');
            } else {
                // Fallback alert if showToast is not available
                alert('An error occurred while saving the survey question.');
            }
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
