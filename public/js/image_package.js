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

            if (imageData.image_file) {
                imageDisplay.innerHTML = `Current File: <a href="uploads/image/${imageData.image_file}" target="_blank">${imageData.image_file}</a>`;
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

    imageForm.addEventListener("submit", function (event) {
        event.preventDefault();
        // Optional: Add validation logic here
    });
});
