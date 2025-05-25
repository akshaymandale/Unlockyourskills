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
        container.innerHTML = '';
        const file = input.files[0];
        if (!file) return;

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
        document.getElementById('surveyQuestionForm').reset();
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

    document.querySelectorAll('.edit-question-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const questionData = btn.getAttribute('data-question');
            if (questionData) {
                setTimeout(() => {
                    window.editSurveyQuestion(questionData);
                }, 300);
            } else {
                console.error('Question data not found on button');
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

        const form = document.getElementById('surveyQuestionForm');
        form.querySelector('[name="surveyQuestionId"]').value = id || '';
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
});
