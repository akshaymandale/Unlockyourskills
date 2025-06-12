document.addEventListener("DOMContentLoaded", function () {
    const audioModal = new bootstrap.Modal(document.getElementById("audioModal"));
    const audioForm = document.getElementById("audioForm");

    // Form Fields
    const audioId = document.getElementById("audio_idaudio");
    const audioTitle = document.getElementById("audio_titleaudio");
    const audioFile = document.getElementById("audioFileaudio");
    const existingAudio = document.getElementById("existing_audioaudio");
    const audioDisplay = document.getElementById("existingAudioDisplayaudio");
    const versionAudio = document.getElementById("versionaudio");
    const languageAudio = document.getElementById("languageaudio");
    const timeLimitAudio = document.getElementById("timeLimitaudio");
    const mobileSupportAudio = document.getElementsByName("mobileSupportaudio");
    const descriptionAudio = document.getElementById("descriptionaudio");

    // Tag Elements
    const tagInput = document.getElementById("tagInputaudio");
    const tagContainer = document.getElementById("tagDisplayaudio");
    const hiddenTagList = document.getElementById("tagListaudio");

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

    // Event delegation for editing audio
    document.addEventListener("click", function (e) {
        const target = e.target.closest(".edit-audio");
        if (target) {
            e.preventDefault();
            const audioData = JSON.parse(target.dataset.audio);

            // Populate the modal fields with the audio data
            audioId.value = audioData.id;
            audioTitle.value = audioData.title;
            versionAudio.value = audioData.version;
            languageAudio.value = audioData.language;
            timeLimitAudio.value = audioData.time_limit;
            descriptionAudio.value = audioData.description;
            existingAudio.value = audioData.audio_file;

            // ✅ Show existing file preview with remove button (following Non-SCORM pattern)
            if (audioData.audio_file) {
                createExistingFilePreview(audioData.audio_file, audioDisplay, 'uploads/audio/');
            } else {
                audioDisplay.innerHTML = "No audio file uploaded.";
            }

            // Set Mobile Support
            mobileSupportAudio.forEach(radio => {
                if (radio.value === audioData.mobile_support) {
                    radio.checked = true;
                }
            });

            // Pre-fill Tags
            tagContainer.innerHTML = "";
            tags = [];
            if (audioData.tags) {
                audioData.tags.split(",").forEach(tag => addTag(tag.trim()));
            }

            // Open the modal
            document.getElementById("audioModalLabel").textContent = "Edit Audio Package";
            audioModal.show();
        }
    });

    // Open Modal for Adding New Audio
    const addAudioBtn = document.getElementById("addAudioBtn");
    if (addAudioBtn) {
        addAudioBtn.addEventListener("click", function () {
            audioForm.reset();
            audioId.value = "";

            tags = [];
            tagContainer.innerHTML = "";
            hiddenTagList.value = "";

            existingAudio.value = "";
            audioDisplay.innerHTML = "";

            document.querySelector('input[name="mobileSupportaudio"][value="No"]').checked = true;
            document.getElementById("audioModalLabel").textContent = "Add Audio Package";

            audioModal.show();
        });
    }

    // ✅ File Preview Functions (following Non-SCORM pattern)
    function createExistingFilePreview(fileName, previewContainer, uploadPath = 'uploads/audio/') {
        const fileExtension = fileName.split('.').pop().toLowerCase();
        let previewHTML = '';

        if (['mp3', 'wav', 'ogg', 'm4a'].includes(fileExtension)) {
            // Audio preview with cross button
            previewHTML = `
                <div class="preview-wrapper" style="position: relative; display: inline-block; margin-top: 10px;">
                    <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;">
                        <i class="fas fa-music" style="font-size: 24px; color: #6c757d;"></i>
                        <button type="button" class="remove-preview" onclick="removeAudioFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
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
                        <button type="button" class="remove-preview" onclick="removeAudioFilePreview('${previewContainer.id}')" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">×</button>
                    </div>
                    <p style="margin-top: 5px; font-size: 12px; color: #6c757d;">Current file: <a href="${uploadPath}${fileName}" target="_blank">${fileName}</a></p>
                </div>
            `;
        }

        previewContainer.innerHTML = previewHTML;
    }

    // Global function to remove file preview
    window.removeAudioFilePreview = function(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = '';
        }

        // Clear the corresponding file input and hidden field
        if (containerId === 'existingAudioDisplay') {
            const audioInput = document.getElementById('audioFileaudio');
            if (audioInput) audioInput.value = '';
            const existingAudioField = document.getElementById('existing_audio');
            if (existingAudioField) existingAudioField.value = '';
        }
    };

    // Optional: Add validation here
    audioForm.addEventListener("submit", function (event) {
        event.preventDefault();
        // Add form validation logic if needed
    });
});
