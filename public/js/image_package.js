document.addEventListener("DOMContentLoaded", function () {
    const imageModal = new bootstrap.Modal(document.getElementById("imageModal"));
    const imageForm = document.getElementById("imageForm");

    // Form Fields
    const imageId = document.getElementById("image_idimage");
    const imageTitle = document.getElementById("image_titleimage");
    const imageFile = document.getElementById("imageFileimage");
    const existingImage = document.getElementById("existing_imageimage");
    const imageDisplay = document.getElementById("existingImageDisplayimage");
    const versionImage = document.getElementById("versionimage");
    const languageImage = document.getElementById("languageimage");
    const mobileSupportImage = document.getElementsByName("mobileSupportimage");
    const descriptionImage = document.getElementById("descriptionimage");

    // Tag Elements
    const tagInput = document.getElementById("tagInputimage");
    const tagContainer = document.getElementById("tagDisplayimage");
    const hiddenTagList = document.getElementById("tagListimage");

    let tags = [];

    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;
        tags.push(tagText);

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;
        tagContainer.appendChild(tagElement);
        updateHiddenInput();
    }

    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateHiddenInput();

        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    function updateHiddenInput() {
        hiddenTagList.value = tags.join(",");
    }

    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });

    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    // Event delegation for editing image
    document.addEventListener("click", function (e) {
        const target = e.target.closest(".edit-image");
        if (target) {
            e.preventDefault();
            const imageData = JSON.parse(target.dataset.image);

            imageId.value = imageData.id;
            imageTitle.value = imageData.title;
            versionImage.value = imageData.version;
            languageImage.value = imageData.language;
            descriptionImage.value = imageData.description;
            existingImage.value = imageData.image_file;

            // ✅ Show existing file preview with remove button (following Non-SCORM pattern)
            if (imageData.image_file) {
                createExistingFilePreview(imageData.image_file, imageDisplay, 'uploads/image/');
            } else {
                imageDisplay.innerHTML = "No image file uploaded.";
            }

            // Mobile Support
            mobileSupportImage.forEach(radio => {
                if (radio.value === imageData.mobile_support) {
                    radio.checked = true;
                }
            });

            tagContainer.innerHTML = "";
            tags = [];
            if (imageData.tags) {
                imageData.tags.split(",").forEach(tag => addTag(tag.trim()));
            }

            document.getElementById("imageModalLabel").textContent = "Edit Image Package";
            imageModal.show();
        }
    });

    // Open Modal for Adding New Image
    const addImageBtn = document.getElementById("addImageBtn");
    if (addImageBtn) {
        addImageBtn.addEventListener("click", function () {
            imageForm.reset();
            imageId.value = "";
            tags = [];
            tagContainer.innerHTML = "";
            hiddenTagList.value = "";
            existingImage.value = "";
            imageDisplay.innerHTML = "";
            document.querySelector('input[name="mobileSupportimage"][value="No"]').checked = true;
            document.getElementById("imageModalLabel").textContent = "Add Image Package";
            imageModal.show();
        });
    }

    // ✅ File Preview Functions (following Non-SCORM pattern)
    function createExistingFilePreview(fileName, previewContainer, uploadPath = 'uploads/image/') {
        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(fileExtension)) {
            // Image preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <img src="${uploadPath}${fileName}" alt="Preview" style="max-width: 150px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; border-radius: 5px;">
                    <button type="button" class="remove-preview" onclick="removeImageFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                </div>
                <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: ${fileName}</p>
            `;
        } else {
            // Generic file preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-image" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeImageFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        }

        previewContainer.innerHTML = previewHTML;
    }

    // Global function to remove file preview
    window.removeImageFilePreview = function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '';
        }

        // Clear the corresponding file input and hidden field
        if (containerId === 'existingImageDisplay') {
            const imageInput = document.getElementById('imageFileimage');
            if (imageInput) imageInput.value = '';
            const existingImageField = document.getElementById('existing_image');
            if (existingImageField) existingImageField.value = '';
        }
    };

    imageForm.addEventListener("submit", function (event) {
        event.preventDefault();
        // Optional: Add validation logic here
    });
});
