document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('surveyQuestionType');
    const optionsWrapper = document.getElementById('surveyOptionsWrapper');
    const ratingWrapper = document.getElementById('ratingWrapper');
    const questionMediaInput = document.getElementById('surveyQuestionMedia');
    const questionPreview = document.getElementById('surveyQuestionPreview');

    typeSelect.addEventListener('change', updateOptionsUI);
    questionMediaInput.addEventListener('change', () => showPreview(questionMediaInput, questionPreview, 'question-preview'));

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
        // Create the individual option block
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
    
        // Insert the new option before the "Add Another Option" button
        const addBtn = document.getElementById('addMoreOptionsBtn');
        if (addBtn) {
            optionsWrapper.insertBefore(optionBlock, addBtn);
        } else {
            optionsWrapper.appendChild(optionBlock);
        }
    
        // Bind file input preview
        const fileInput = optionBlock.querySelector('.option-file');
        const preview = optionBlock.querySelector('.option-preview');
        if (fileInput) {
            fileInput.addEventListener('change', () => showPreview(fileInput, preview, 'option-preview'));
        }
    
        // Remove option logic
        const removeBtn = optionBlock.querySelector('.remove-option');
        removeBtn.addEventListener('click', () => {
            optionBlock.remove();
        });
    
        // Create and append the "Add Another Option" button only once
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

    updateOptionsUI(); // Initial call

    // Reset modal fields and previews on open
const surveyModal = document.getElementById('addSurveyQuestionModal');
surveyModal.addEventListener('show.bs.modal', () => {
    // Reset form
    document.getElementById('surveyQuestionForm').reset();

    // Clear preview
    questionPreview.innerHTML = '';
    questionMediaInput.value = '';

    // Reset type dropdown to default and trigger UI update
    typeSelect.value = 'multi_choice';
    updateOptionsUI();

    // Reset rating fields
    ratingWrapper.classList.add('d-none');
});
});
