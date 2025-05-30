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
        fetch(`index.php?controller=FeedbackQuestionController&action=getQuestionById&id=${questionId}`)
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



});
