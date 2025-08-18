console.log('[assessment_question.js] loaded');
document.addEventListener('DOMContentLoaded', function () {
    // ✅ Assessment Question Search and Filter Functionality
    // Global variables to track current state
    let currentPage = 1;
    let currentSearch = '';
    let currentFilters = {
        question_type: '',
        difficulty: '',
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
    const difficultyFilter = document.getElementById('difficultyFilter');
    
    if (questionTypeFilter) {
        questionTypeFilter.addEventListener('change', applyFilters);
    }
    
    if (difficultyFilter) {
        difficultyFilter.addEventListener('change', applyFilters);
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
    // Check if we're on the assessment questions page
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
        currentFilters.difficulty = difficultyFilter ? difficultyFilter.value : '';
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
        if (difficultyFilter) difficultyFilter.value = '';
        if (tagsFilter) tagsFilter.value = '';
        currentFilters = {
            question_type: '',
            difficulty: '',
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
        formData.append('controller', 'QuestionController');
        formData.append('action', 'ajaxSearch');
        formData.append('page', currentPage);
        formData.append('search', currentSearch);
        formData.append('question_type', currentFilters.question_type);
        formData.append('difficulty', currentFilters.difficulty);
        formData.append('tags', currentFilters.tags);

        // Make AJAX request
        fetch('/unlockyourskills/vlr/questions', {
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
                    <td colspan="5" class="no-results-message">
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
                <td>${escapeHtml(question.difficulty)}</td>
                <td>${escapeHtml(question.tags)}</td>
                <td>
                    <button type="button" class="btn theme-btn-primary edit-question-btn"
                        data-question-id="${question.id}"
                        title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <a href="#" class="btn theme-btn-danger delete-assessment-question"
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

        if (currentSearch || currentFilters.question_type || currentFilters.difficulty || currentFilters.tags) {
            let infoText = `Showing ${totalQuestions} result(s)`;
            
            if (currentSearch) {
                infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
            }
            
            if (currentFilters.question_type || currentFilters.difficulty || currentFilters.tags) {
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

    // ✅ Assessment question delete confirmations now handled by centralized system

    // ✅ Modal functionality for Assessment Questions
    const assessmentModal = document.getElementById('addAssessmentQuestionModal');
    const assessmentForm = document.getElementById('assessmentQuestionForm');
    let isEditMode = false;

    // Handle "Add Question" button clicks specifically
    document.addEventListener('click', function(e) {
        const addButton = e.target.closest('[data-bs-target="#addAssessmentQuestionModal"]:not(.edit-question-btn)');
        if (addButton) {
            isEditMode = false;
            // Force reset immediately when Add button is clicked
            setTimeout(() => {
                resetAssessmentForm();
            }, 50);
        }
    });

    // Reset modal on open
    if (assessmentModal) {
        assessmentModal.addEventListener('show.bs.modal', function(event) {
            // Check if this is triggered by an edit button
            const triggerButton = event.relatedTarget;
            const isEditButton = triggerButton && triggerButton.classList.contains('edit-question-btn');

            if (!isEditButton) {
                // This is an "Add" action, always reset the form
                isEditMode = false;
                resetAssessmentForm();
            }
            // If it's an edit button, isEditMode will be set by the click handler
        });

        // Also reset when modal is completely hidden
        assessmentModal.addEventListener('hidden.bs.modal', function() {
            // Always reset when modal is closed to ensure clean state
            isEditMode = false;
            resetAssessmentForm();
        });
    }

    // Handle edit button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-question-btn')) {
            e.preventDefault(); // Prevent any default Bootstrap modal behavior
            e.stopPropagation(); // Stop event bubbling

            const btn = e.target.closest('.edit-question-btn');
            const questionId = btn.getAttribute('data-question-id');

            if (questionId) {
                // Set edit mode BEFORE the modal opens
                isEditMode = true;

                // Reset form first to clear any previous state
                resetAssessmentForm();

                // Show the modal first
                const modal = bootstrap.Modal.getOrCreateInstance(assessmentModal);
                modal.show();

                // Then load the question data after modal is shown
                setTimeout(() => {
                    loadQuestionForEdit(questionId);
                }, 200); // Increased delay to ensure modal is fully shown
            } else {
                console.error('No question ID found on edit button');
            }
        }
    });

    function resetAssessmentForm() {
        if (assessmentForm) {
            // Reset the entire form
            assessmentForm.reset();

            // Clear specific fields that might not be reset by form.reset()
            document.getElementById('questionId').value = '';
            document.getElementById('questionText').value = '';

            // Clear all validation errors
            const invalidInputs = assessmentForm.querySelectorAll('.is-invalid');
            invalidInputs.forEach(input => {
                input.classList.remove('is-invalid');
                const feedback = input.parentNode.querySelector('.invalid-feedback');
                if (feedback) feedback.remove();
            });

            // Reset tags and skills completely
            resetTagContainer('tagsContainer', 'tagsHidden');
            resetTagContainer('skillsContainer', 'skillsHidden');

            // Reset all option textareas
            for (let i = 1; i <= 10; i++) {
                const optionTextarea = document.getElementById(`option_${i}`);
                const correctCheckbox = document.getElementById(`correct_${i}`);
                const charCount = document.getElementById(`charCount_${i}`);

                if (optionTextarea) optionTextarea.value = '';
                if (correctCheckbox) correctCheckbox.checked = false;
                if (charCount) charCount.textContent = '0';
            }

            // Reset modal title
            const modalTitle = document.getElementById('addAssessmentQuestionModalLabel');
            if (modalTitle) {
                modalTitle.textContent = 'Add Assessment Question';
            }

            // Reset media preview
            const mediaPreview = document.getElementById('mediaPreview');
            if (mediaPreview) {
                mediaPreview.innerHTML = '';
            }

            // Reset media file input
            const mediaFile = document.getElementById('mediaFile');
            if (mediaFile) {
                mediaFile.value = '';
            }

            // Remove the removeExistingMedia flag if it exists
            const removeInput = document.getElementById('removeExistingMedia');
            if (removeInput) {
                removeInput.remove();
            }

            // Set default values
            const answerCountSelect = document.getElementById('answerCount');
            if (answerCountSelect) {
                answerCountSelect.value = '1';
            }

            // Set default radio button selections
            const objectiveRadio = document.getElementById('objective');
            const activeRadio = document.getElementById('active');
            if (objectiveRadio) objectiveRadio.checked = true;
            if (activeRadio) activeRadio.checked = true;

            // Set default select values
            const levelSelect = document.getElementById('level');
            const marksSelect = document.getElementById('marks');
            const mediaTypeSelect = document.getElementById('questionMediaType');

            if (levelSelect) levelSelect.value = 'Low';
            if (marksSelect) marksSelect.value = '1';
            if (mediaTypeSelect) mediaTypeSelect.value = 'text';

            // Reset form state
            if (typeof window.toggleObjectiveFields === 'function') window.toggleObjectiveFields();
            if (typeof window.updateAnswerOptions === 'function') window.updateAnswerOptions();
            if (typeof window.updateMediaUpload === 'function') window.updateMediaUpload();
        }
    }

    function resetTagContainer(containerId, hiddenId) {
        const container = document.getElementById(containerId);
        const hidden = document.getElementById(hiddenId);
        const input = containerId === 'tagsContainer' ? document.getElementById('tagsInput') : document.getElementById('skillsInput');

        // Reset the tag arrays
        if (window.tagArrays && window.tagArrays[containerId]) {
            window.tagArrays[containerId] = [];
        }

        // Use the container's reset function if available
        if (container && container.resetTags) {
            container.resetTags();
        } else {
            if (container) container.innerHTML = '';
        }

        if (hidden) hidden.value = '';
        if (input) input.value = '';

        // Clear any validation errors on the input
        if (input) {
            input.classList.remove('is-invalid');
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) feedback.remove();
        }
    }
    function loadQuestionForEdit(questionId) {
        const url = `/unlockyourskills/vlr/questions/${questionId}`;
        console.log('[Edit] Fetching question for edit:', url);
        fetch(url)
            .then(response => {
                console.log('[Edit] Response status:', response.status);
                if (!response.ok) {
                    throw new Error('HTTP error! status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('[Edit] Fetched question data:', data);
                if (data.success && data.question) {
                    populateEditForm(data.question, data.options);
                } else if (data.error) {
                    alert('Error loading question: ' + data.error);
                } else {
                    alert('Question not found or invalid response');
                }
            })
            .catch(error => {
                console.error('[Edit] Error loading question:', error);
            });
    }

    function populateEditForm(question, options) {
        // Set form values
        document.getElementById('questionId').value = question.id;
        document.getElementById('questionText').value = question.question_text || '';

        // Set tags
        if (question.tags) {
            const tags = question.tags.split(',').filter(Boolean);
            populateTagContainer('tagsContainer', 'tagsHidden', tags);
        }

        // Set skills
        if (question.competency_skills) {
            const skills = question.competency_skills.split(',').filter(Boolean);
            populateTagContainer('skillsContainer', 'skillsHidden', skills);
        }

        // Set other fields
        document.getElementById('level').value = question.level || 'Low';
        document.getElementById('marks').value = question.marks || 1;

        // Set status
        const statusRadio = document.querySelector(`input[name="status"][value="${question.status || 'Active'}"]`);
        if (statusRadio) statusRadio.checked = true;

        // Set question type
        const typeRadio = document.querySelector(`input[name="questionFormType"][value="${question.question_type || 'Objective'}"]`);
        if (typeRadio) typeRadio.checked = true;

        // Set media type
        document.getElementById('questionMediaType').value = question.media_type || 'text';

        // Handle existing media preview
        const mediaPreview = document.getElementById('mediaPreview');
        
        if (question.media_file && question.media_type !== 'text') {
            // Store the existing media file path
            const existingMediaFileInput = document.getElementById('existingMediaFile');
            if (existingMediaFileInput) {
                existingMediaFileInput.value = question.media_file;
            }
            
            // Show the preview
            if (mediaPreview) {
                showExistingMediaPreview(question.media_file, question.media_type, mediaPreview);
            }
        }

        // Set answer count and options
        if (options && options.length > 0) {
            document.getElementById('answerCount').value = options.length;

            // Populate options
            options.forEach((option, index) => {
                const optionTextarea = document.getElementById(`option_${index + 1}`);
                const correctCheckbox = document.getElementById(`correct_${index + 1}`);

                if (optionTextarea) {
                    optionTextarea.value = option.option_text || '';
                    // Update character count
                    const charCount = document.getElementById(`charCount_${index + 1}`);
                    if (charCount) charCount.textContent = optionTextarea.value.length;
                }

                if (correctCheckbox) {
                    correctCheckbox.checked = option.is_correct == 1;
                }
            });
        }

        // Update modal title
        const modalTitle = document.getElementById('addAssessmentQuestionModalLabel');
        if (modalTitle) {
            modalTitle.textContent = 'Edit Assessment Question';
        }

        // Update form state
        if (typeof window.toggleObjectiveFields === 'function') {
            window.toggleObjectiveFields();
        }
        if (typeof window.updateAnswerOptions === 'function') {
            window.updateAnswerOptions();
        }
        if (typeof window.updateMediaUpload === 'function') {
            window.updateMediaUpload();
        }
    }

    function showExistingMediaPreview(mediaFile, mediaType, previewContainer) {
        if (!mediaFile || !previewContainer) return;

        // Construct the correct web-accessible path
        // The database stores paths like "uploads/media/filename.jpg"
        // We need to make it web-accessible
        let mediaPath;
        if (mediaFile.startsWith('uploads/')) {
            // If it's a relative path, make it web-accessible
            mediaPath = `/unlockyourskills/${mediaFile}`;
        } else if (mediaFile.startsWith('/')) {
            // If it's already an absolute path, use as is
            mediaPath = mediaFile;
        } else {
            // Fallback: assume it's a relative path
            mediaPath = `/unlockyourskills/uploads/media/${mediaFile}`;
        }
        
        const fileName = mediaFile.split('/').pop(); // Extract only the filename

        // Create element based on media type
        let element;
        if (mediaType === 'image') {
            element = document.createElement('img');
            element.src = mediaPath;
        } else if (mediaType === 'audio') {
            element = document.createElement('audio');
            element.src = mediaPath;
            element.controls = true;
        } else if (mediaType === 'video') {
            element = document.createElement('video');
            element.src = mediaPath;
            element.controls = true;
        }

        if (!element) return;

        // Create wrapper div with preview-wrapper class (same as feedback/survey)
        const wrapper = document.createElement('div');
        wrapper.classList.add('preview-wrapper', 'question-preview');

        // Create remove button (same style as feedback/survey)
        const crossBtn = document.createElement('button');
        crossBtn.innerHTML = '&times;';
        crossBtn.className = 'remove-preview';
        crossBtn.addEventListener('click', () => {
            previewContainer.innerHTML = '';
            const mediaFile = document.getElementById('mediaFile');
            if (mediaFile) {
                mediaFile.value = '';
            }

            // Add a hidden input to indicate media should be removed
            let removeInput = document.getElementById('removeExistingMedia');
            if (!removeInput) {
                removeInput = document.createElement('input');
                removeInput.type = 'hidden';
                removeInput.id = 'removeExistingMedia';
                removeInput.name = 'removeExistingMedia';
                document.getElementById('assessmentQuestionForm').appendChild(removeInput);
            }
            removeInput.value = '1';
        });

        // Append element and button to wrapper
        wrapper.appendChild(element);
        wrapper.appendChild(crossBtn);

        // Create "Current file:" text
        const fileInfo = document.createElement('div');
        fileInfo.className = 'mt-1';
        fileInfo.innerHTML = `<small class="text-muted">Current file: ${fileName}</small>`;

        // Clear container and add wrapper and file info
        previewContainer.innerHTML = '';
        previewContainer.appendChild(wrapper);
        previewContainer.appendChild(fileInfo);
    }

    function populateTagContainer(containerId, hiddenId, tags) {
        const container = document.getElementById(containerId);
        const hidden = document.getElementById(hiddenId);

        if (!window.tagArrays) window.tagArrays = {};
        window.tagArrays[containerId] = tags.slice(); // <-- update the global tag array

        if (container && hidden) {
            container.innerHTML = '';
            tags.forEach((tag, index) => {
                const tagEl = document.createElement('div');
                tagEl.className = 'tag';
                tagEl.innerHTML = `${tag} <span class="remove-tag" data-index="${index}">&times;</span>`;
                container.appendChild(tagEl);
            });
            hidden.value = tags.join(',');
        }
    }

    // Initialize modal form functionality
    if (assessmentForm) {
        initializeAssessmentForm();

        // Handle form submission
        assessmentForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Check if form has validation errors
            const hasErrors = assessmentForm.querySelectorAll('.is-invalid').length > 0;
            if (hasErrors) {
                // Scroll to first error
                const firstError = assessmentForm.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                return; // Don't submit if there are validation errors
            }

            // Submit form via AJAX
            const formData = new FormData(assessmentForm);

            fetch('/unlockyourskills/vlr/questions', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(assessmentModal);
                    if (modal) modal.hide();

                    // Refresh questions list
                    loadQuestions(currentPage);

                    // Show success message
                    alert(data.message);
                } else {
                    // Show error message
                    alert(data.message || 'Error saving question. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving question. Please try again.');
            });
        });
    }

    function initializeAssessmentForm() {
        const answerCountSelect = document.getElementById("answerCount");
        const questionTypeSelect = document.getElementById("questionMediaType");
        const mediaUploadContainer = document.getElementById("mediaUploadContainer");
        const mediaFile = document.getElementById("mediaFile");
        const mediaPreview = document.getElementById("mediaPreview");
        const questionFormTypeRadios = document.querySelectorAll('input[name="questionFormType"]');

        function updateAnswerOptions() {
            const selected = parseInt(answerCountSelect.value);
            document.querySelectorAll(".option-block").forEach(block => {
                const index = parseInt(block.dataset.index);
                block.classList.toggle("d-none", index > selected);
            });
        }

        function updateMediaUpload() {
            const selectedType = questionTypeSelect.value;
            mediaUploadContainer.classList.toggle("d-none", selectedType === "text");

            // Update accept attribute based on media type
            if (mediaFile) {
                const acceptTypes = {
                    'image': 'image/jpeg,image/jpg,image/png,image/gif,image/webp',
                    'audio': 'audio/mp3,audio/wav,audio/ogg,audio/mpeg',
                    'video': 'video/mp4,video/webm,video/ogg,video/avi'
                };
                mediaFile.setAttribute('accept', acceptTypes[selectedType] || '');
            }
        }

        function toggleObjectiveFields() {
            const isObjective = document.getElementById("objective").checked;
            document.querySelectorAll(".objective-only").forEach(el => {
                el.classList.toggle("d-none", !isObjective);
            });
        }

        // Make functions globally accessible for edit mode
        window.updateAnswerOptions = updateAnswerOptions;
        window.updateMediaUpload = updateMediaUpload;
        window.toggleObjectiveFields = toggleObjectiveFields;

        // Character count for options
        document.querySelectorAll(".option-textarea").forEach(textarea => {
            textarea.addEventListener("input", function () {
                const id = this.id.split("_")[1];
                const charCount = document.getElementById("charCount_" + id);
                if (charCount) {
                    charCount.innerText = this.value.length;
                }
            });
        });

        // Event listeners
        if (answerCountSelect) answerCountSelect.addEventListener("change", updateAnswerOptions);
        if (questionTypeSelect) questionTypeSelect.addEventListener("change", updateMediaUpload);
        questionFormTypeRadios.forEach(radio => {
            radio.addEventListener("change", toggleObjectiveFields);
        });

        // Media preview (validation handled by assessment_question_validation.js)
        if (mediaFile) {
            mediaFile.addEventListener("change", function () {
                const file = this.files[0];
                if (!file) {
                    if (mediaPreview) mediaPreview.innerHTML = '';
                    return;
                }

                // Show preview only (validation is handled by assessment_question_validation.js)
                const url = URL.createObjectURL(file);
                let preview = '';

                if (file.type.startsWith("image/")) {
                    preview = `<img src="${url}" class="img-thumbnail" width="200">`;
                } else if (file.type.startsWith("audio/")) {
                    preview = `<audio controls src="${url}"></audio>`;
                } else if (file.type.startsWith("video/")) {
                    preview = `<video controls width="200" src="${url}"></video>`;
                }

                if (mediaPreview) {
                    mediaPreview.innerHTML = preview;
                }
            });
        }

        // Tag functionality
        initTagInput('tagsContainer', 'tagsInput', 'tagsHidden');
        initTagInput('skillsContainer', 'skillsInput', 'skillsHidden');

        // Initialize form state
        toggleObjectiveFields();
        updateAnswerOptions();
        updateMediaUpload();
    }

    function initTagInput(containerId, inputId, hiddenId) {
        const container = document.getElementById(containerId);
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);

        if (!container || !input || !hidden) return;

        // Store tags array in a way that can be reset
        if (!window.tagArrays) window.tagArrays = {};
        window.tagArrays[containerId] = [];

        function renderTags() {
            container.innerHTML = '';
            window.tagArrays[containerId].forEach((tag, index) => {
                const tagEl = document.createElement('div');
                tagEl.className = 'tag';
                tagEl.innerHTML = `${tag} <span class="remove-tag" data-index="${index}">&times;</span>`;
                container.appendChild(tagEl);
            });
            hidden.value = window.tagArrays[containerId].join(',');
        }

        input.addEventListener('keydown', function (e) {
            if ((e.key === 'Enter' || e.key === ',') && input.value.trim() !== '') {
                e.preventDefault();
                const newTag = input.value.trim();
                if (!window.tagArrays[containerId].includes(newTag)) {
                    window.tagArrays[containerId].push(newTag);
                    renderTags();
                }
                input.value = '';
            }
        });

        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-tag')) {
                const index = parseInt(e.target.getAttribute('data-index'));
                window.tagArrays[containerId].splice(index, 1);
                renderTags();
            }
        });

        // Add a reset function to the container
        container.resetTags = function() {
            window.tagArrays[containerId] = [];
            renderTags();
        };
    }

    // ✅ Import Assessment Questions Modal Validation
    const importModal = document.getElementById('importAssessmentQuestionModal');
    const importForm = document.getElementById('importAssessmentQuestionForm');

    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('importFile');
            const questionTypeSelect = document.getElementById('questionType');

            let isValid = true;
            let errorMessage = '';

            // Validate file selection
            if (!fileInput.files || fileInput.files.length === 0) {
                isValid = false;
                errorMessage += 'Please select an Excel file to import.\n';
            } else {
                const file = fileInput.files[0];
                const allowedTypes = [
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/csv'
                ];

                if (!allowedTypes.includes(file.type)) {
                    isValid = false;
                    errorMessage += 'Invalid file type. Please select an Excel (.xlsx, .xls) or CSV file.\n';
                }

                // Check file size (10MB limit)
                const maxSize = 10 * 1024 * 1024; // 10MB in bytes
                if (file.size > maxSize) {
                    isValid = false;
                    errorMessage += 'File size exceeds 10MB limit. Please select a smaller file.\n';
                }
            }

            // Validate question type selection
            if (!questionTypeSelect.value) {
                isValid = false;
                errorMessage += 'Please select a question type (Objective or Subjective).\n';
            }

            if (!isValid) {
                alert('Please fix the following errors:\n\n' + errorMessage);
                return false;
            }

            // Show confirmation dialog
            const confirmMessage = `Are you sure you want to import questions from this file?\n\nFile: ${fileInput.files[0].name}\nType: ${questionTypeSelect.options[questionTypeSelect.selectedIndex].text}\n\nThis action cannot be undone.`;

            if (confirm(confirmMessage)) {
                // Show loading state
                const submitButton = importForm.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Importing...';
                submitButton.disabled = true;

                // Maintain theme color during loading
                submitButton.style.backgroundColor = '#6a0dad';
                submitButton.style.borderColor = '#6a0dad';
                submitButton.style.color = 'white';

                // Submit the form
                importForm.submit();
            }
        });
    }

    // Reset import form when modal is closed
    if (importModal) {
        importModal.addEventListener('hidden.bs.modal', function() {
            if (importForm) {
                importForm.reset();

                // Reset submit button state with theme styling
                const submitButton = importForm.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.innerHTML = '<i class="fas fa-upload me-2"></i>Import Questions';
                    submitButton.disabled = false;

                    // Restore theme styling
                    submitButton.style.backgroundColor = '#6a0dad';
                    submitButton.style.borderColor = '#6a0dad';
                    submitButton.style.color = 'white';
                }
            }
        });
    }

    // Re-initialize modal JS when shown
    const addAssessmentModal = document.getElementById('addAssessmentQuestionModal');
    if (addAssessmentModal) {
        // Reset as soon as the modal is hidden
        addAssessmentModal.addEventListener('hidden.bs.modal', function () {
            resetAddAssessmentQuestionModal();
        });
        // Also reset and re-initialize when the modal is about to be shown
        addAssessmentModal.addEventListener('show.bs.modal', function () {
            resetAddAssessmentQuestionModal();
            if (typeof initializeAssessmentForm === 'function') {
                initializeAssessmentForm();
            }
        });
    }

    function resetAddAssessmentQuestionModal() {
        // Reset the form fields
        const form = document.getElementById('addAssessmentQuestionForm');
        if (form) form.reset();

        // Reset tags and skills containers
        if (window.tagArrays) {
            if (window.tagArrays['tagsContainer']) window.tagArrays['tagsContainer'] = [];
            if (window.tagArrays['skillsContainer']) window.tagArrays['skillsContainer'] = [];
        }
        if (document.getElementById('tagsContainer') && document.getElementById('tagsContainer').resetTags) {
            document.getElementById('tagsContainer').resetTags();
        }
        if (document.getElementById('skillsContainer') && document.getElementById('skillsContainer').resetTags) {
            document.getElementById('skillsContainer').resetTags();
        }

        // Reset character counts
        for (let i = 1; i <= 10; i++) {
            const charCount = document.getElementById('charCount_' + i);
            if (charCount) charCount.innerText = '0';
        }

        // Hide all validation errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        // Reset answer options to default (show only first, hide others)
        document.querySelectorAll('.option-block').forEach((block, idx) => {
            if (idx === 0) block.classList.remove('d-none');
            else block.classList.add('d-none');
            // Clear textarea and checkbox
            const textarea = block.querySelector('textarea');
            if (textarea) textarea.value = '';
            const checkbox = block.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = false;
        });

        // Reset media preview
        const mediaPreview = document.getElementById('mediaPreview');
        if (mediaPreview) mediaPreview.innerHTML = '';
    }

});
