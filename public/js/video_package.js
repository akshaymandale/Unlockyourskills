document.addEventListener("DOMContentLoaded", function () {
    const videoModal = new bootstrap.Modal(document.getElementById("videoModal"));
    const videoForm = document.getElementById("videoForm");

    // Form Fields
    const videoId = document.getElementById("video_idvideo");
    const videoTitle = document.getElementById("video_titlevideo");
    const videoFile = document.getElementById("videoFilevideo");
    const existingVideo = document.getElementById("existing_videovideo");
    const videoDisplay = document.getElementById("existingVideoDisplayvideo");
    const versionVideo = document.getElementById("versionvideo");
    const languageVideo = document.getElementById("languagevideo");
    const timeLimitVideo = document.getElementById("timeLimitvideo");
    const mobileSupportVideo = document.getElementsByName("mobileSupportvideo");
    const descriptionVideo = document.getElementById("descriptionvideo");

    // Tag Elements
    const tagInput = document.getElementById("tagInputvideo");
    const tagContainer = document.getElementById("tagDisplayvideo");
    const hiddenTagList = document.getElementById("tagListvideo");

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

    // Event delegation for editing video
  document.addEventListener("click", function (e) {
    const target = e.target.closest(".edit-video");
    if (target) {
        e.preventDefault();
        const videoData = JSON.parse(target.dataset.video);

        // Populate the modal fields with the video data
        videoId.value = videoData.id;
        videoTitle.value = videoData.title;
        versionVideo.value = videoData.version;
        languageVideo.value = videoData.language;
        timeLimitVideo.value = videoData.time_limit;
        descriptionVideo.value = videoData.description;
        existingVideo.value = videoData.video_file;

        // ✅ Show existing file preview with remove button (following Non-SCORM pattern)
        if (videoData.video_file) {
            createExistingFilePreview(videoData.video_file, videoDisplay, 'uploads/video/');
        } else {
            videoDisplay.innerHTML = "No video file uploaded.";
        }

        // Set Mobile Support
        mobileSupportVideo.forEach(radio => {
            if (radio.value === videoData.mobile_support) {
                radio.checked = true;
            }
        });

        // Pre-fill Tags
        tagContainer.innerHTML = "";
        tags = [];
        if (videoData.tags) {
            videoData.tags.split(",").forEach(tag => addTag(tag.trim()));
        }

        // Open the modal
        document.getElementById("videoModalLabel").textContent = "Edit Video Package";
        videoModal.show();
    }
});


    // Open Modal for Adding New Video
    const addVideoBtn = document.getElementById("addVideoBtn");
    if (addVideoBtn) {
        addVideoBtn.addEventListener("click", function () {
            videoForm.reset();
            videoId.value = "";

            tags = [];
            tagContainer.innerHTML = "";
            hiddenTagList.value = "";

            existingVideo.value = "";
            videoDisplay.innerHTML = "";

            document.querySelector('input[name="mobileSupportvideo"][value="No"]').checked = true;
            document.getElementById("videoModalLabel").textContent = "Add Video Package";

            videoModal.show();
        });
    }

    // ✅ File Preview Functions (following Non-SCORM pattern)
    function createExistingFilePreview(fileName, previewContainer, uploadPath = 'uploads/video/') {
        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        if (['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'].includes(fileExtension)) {
            // Video preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-video" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeVideoFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        } else {
            // Generic file preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-file" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeVideoFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        }

        previewContainer.innerHTML = previewHTML;
    }

    // Global function to remove file preview
    window.removeVideoFilePreview = function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '';
        }

        // Clear the corresponding file input and hidden field
        if (containerId === 'existingVideoDisplay') {
            const videoInput = document.getElementById('videoFilevideo');
            if (videoInput) videoInput.value = '';
            const existingVideoField = document.getElementById('existing_video');
            if (existingVideoField) existingVideoField.value = '';
        }
    };

    // Optional: Add validation here
    videoForm.addEventListener("submit", function (event) {
        event.preventDefault();
        // Add form validation logic if needed
    });
});
