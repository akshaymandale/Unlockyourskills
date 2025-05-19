document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('surveyQuestionType');
    const optionsWrapper = document.getElementById('surveyOptionsWrapper');
    const ratingWrapper = document.getElementById('ratingWrapper');
    const questionMediaInput = document.getElementById('surveyQuestionMedia');
    const questionPreview = document.getElementById('surveyQuestionPreview');

    const tagInput = document.getElementById('tagInput');
    const tagDisplay = document.getElementById('tagDisplay');
    const tagListInput = document.getElementById('tagList');
    let tags = [];

    typeSelect.addEventListener('change', updateOptionsUI);
    questionMediaInput.addEventListener('change', () =>
        showPreview(questionMediaInput, questionPreview, 'question-preview')
    );

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

    function addOptionField(isRadio, isDropdown) {
        const optionBlock = document.createElement('div');
        optionBlock.classList.add('mb-2', 'option-block');

        optionBlock.innerHTML = `
        <div class="d-flex align-items-center gap-2 mb-2">
            <input type="${isRadio ? 'radio' : 'checkbox'}" disabled>
            <input type="text" class="form-control option-text" name="optionText[]" placeholder="Option label">
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
            btn.innerHTML = 'Add Another Option';
            btn.addEventListener('click', () => addOptionField(isRadio, isDropdown));
            optionsWrapper.appendChild(btn);
        }
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

        const containerDiv = document.createElement('div');
        containerDiv.classList.add('preview-wrapper', type);

        const crossBtn = document.createElement('button');
        crossBtn.innerHTML = '&times;';
        crossBtn.className = 'remove-preview';
        crossBtn.addEventListener('click', () => {
            container.innerHTML = '';
            input.value = '';
        });

        containerDiv.appendChild(element);
        containerDiv.appendChild(crossBtn);
        container.appendChild(containerDiv);
    }

    // 🔁 Tag input logic
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

    // Reset modal fields and previews on open
    const surveyModal = document.getElementById('addSurveyQuestionModal');
    surveyModal.addEventListener('show.bs.modal', () => {
        document.getElementById('surveyQuestionForm').reset();
        questionPreview.innerHTML = '';
        questionMediaInput.value = '';
        typeSelect.value = 'multi_choice';
        updateOptionsUI();
        ratingWrapper.classList.add('d-none');

        // Reset tags
        tags = [];
        renderTags();
        updateTagListInput();
    });

    updateOptionsUI(); // Initial UI setup
});
