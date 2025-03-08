document.addEventListener("DOMContentLoaded", function () {
    console.log("✅ JS Loaded Successfully!");

    // ✅ Selecting necessary DOM elements
    const contentGroups = document.querySelectorAll(".content-group");
    const contentType = document.getElementById("contentType");
    const tagInput = document.getElementById("externalTagInput");
    const tagContainer = document.getElementById("externalTagDisplay");
    const hiddenTagList = document.getElementById("externalTagList");
    const thumbnailPreview = document.getElementById("thumbnailPreview");
    const audioFile = document.getElementById("audioFile");
    const audioUrl = document.getElementById("audioUrl");
    const audioSource = document.getElementById("audioSource");
    const modalTitle = document.getElementById("externalModalLabel");
    const externalForm = document.getElementById("externalContentForm");
    let tags = [];

    // ✅ Mapping of content type values to section IDs
    const contentTypeMap = {
        "youtube-vimeo": "youtubeVimeoFields",
        "linkedin-udemy": "linkedinUdemyFields",
        "web-links-blogs": "webLinksBlogsFields",
        "podcasts-audio": "podcastsAudioFields",
    };

    // ✅ Hide all sections initially
    function hideAllSections() {
        document.querySelectorAll(".content-group").forEach(group => {
            group.style.display = "none";
        });
    }

    // ✅ Show the selected content type section
    function showSelectedSection() {
        hideAllSections(); // Hide all sections first

        const selectedType = contentType.value;
        const sectionId = contentTypeMap[selectedType];

        console.log(`🔍 Checking if section exists: ${sectionId}`);

        const selectedSection = document.getElementById(sectionId);

        if (selectedSection) {
            selectedSection.style.display = "block";
            console.log(`✅ Displaying section: ${sectionId}`);
        } else {
            console.warn(`⚠️ Missing section for content type: ${selectedType}`);
        }

        // ✅ Ensure audio fields toggle properly when Podcasts & Audio is selected
        if (selectedType === "podcasts-audio") {
            toggleAudioFields();
        }
    }

    // ✅ Attach event listener for content type selection
    contentType.addEventListener("change", showSelectedSection);

    // ✅ Function to toggle audio fields visibility based on selection
    function toggleAudioFields() {
        if (!audioSource || !audioFile || !audioUrl) {
            console.error("❌ Missing audio elements in DOM!");
            return;
        }

        // Set default value if empty
        if (!audioSource.value) {
            audioSource.value = "upload"; // Default to "Upload File"
        }

        if (audioSource.value === "upload") {
            audioFile.parentElement.style.display = "block";
            audioUrl.parentElement.style.display = "none";
        } else {
            audioFile.parentElement.style.display = "none";
            audioUrl.parentElement.style.display = "block";
        }
    }

    // ✅ Attach event listener for audio source change
    if (audioSource) {
        audioSource.addEventListener("change", toggleAudioFields);
    }

    // ✅ Initially hide all sections on page load
    hideAllSections();

    // ✅ Functions to handle tag input & display
    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;
        tags.push(tagText);
        updateTagDisplay();
        validateTags();
    }

    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateTagDisplay();
        validateTags();
    }

    function updateTagDisplay() {
        tagContainer.innerHTML = "";
        tags.forEach(tag => {
            const tagElement = document.createElement("span");
            tagElement.classList.add("tag");
            tagElement.innerHTML = `${tag} <button type="button" class="remove-tag" data-tag="${tag}">&times;</button>`;
            tagContainer.appendChild(tagElement);
        });
        hiddenTagList.value = tags.join(",");
    }

    // ✅ Event listeners for handling tag inputs
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

    tagInput.addEventListener("blur", validateTags);

    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    // ✅ Validation functions
    function validateTags() {
        let tagField = document.getElementById("externalTagList");
        let tagContainer = document.getElementById("externalTagDisplay");
        let tagsValue = tagField.value.trim();

        if (tagsValue === "") {
            showError(tagContainer, "Tags/Keywords are required");
            return false;
        } else {
            hideError(tagContainer);
            return true;
        }
    }

    // ✅ Show error function
    function showError(input, message) {
        let formGroup = input.closest(".form-group");
        if (!formGroup) {
            console.error(`❌ .form-group NOT found for ${input.name}`);
            return;
        }

        let errorElement = formGroup.querySelector(".error-message");
        if (!errorElement) {
            errorElement = document.createElement("small");
            errorElement.className = "error-message text-danger";
            formGroup.appendChild(errorElement);
        }

        errorElement.textContent = message;
        input.classList.add("is-invalid");
    }

    // ✅ Prevent form submission if validation fails
    externalForm.addEventListener("submit", function (event) {
        if (!validateForm()) {
            console.error("❌ Form validation failed. Please fix errors before submitting.");
            event.preventDefault();
        }
    });

    function hideError(input) {
        let formGroup = input.closest(".form-group");
        if (!formGroup) return;

        let errorElement = formGroup.querySelector(".error-message");
        if (errorElement) {
            errorElement.textContent = "";
        }
        input.classList.remove("is-invalid");
    }

    // ✅ Handle modal reset on close
    $("#externalContentModal").on("hidden.bs.modal", function () {
        document.getElementById("externalContentForm").reset();
        hideAllSections();
        tags = [];
        updateTagDisplay();
        thumbnailPreview.style.display = "none";
        modalTitle.textContent = "Add External Content";
    });

    // ✅ Handle thumbnail preview
    document.getElementById("thumbnail").addEventListener("change", function (event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                thumbnailPreview.src = e.target.result;
                thumbnailPreview.style.display = "block";
            };
            reader.readAsDataURL(file);
        } else {
            thumbnailPreview.style.display = "none";
        }
    });

    // ✅ Ensure correct audio field visibility on edit
    audioSource.addEventListener("change", toggleAudioFields);

    // ✅ Handle edit content modal
    document.querySelectorAll(".edit-content").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            const contentData = JSON.parse(this.getAttribute("data-content"));

            modalTitle.textContent = "Edit External Content";

            document.getElementById("external_id").value = contentData.id || "";
            document.getElementById("title").value = contentData.title || "";
            document.getElementById("versionNumber").value = contentData.version_number || "";
            document.getElementById("languageSupport").value = contentData.language_support || "English";
            document.getElementById("external_timeLimit").value = contentData.time_limit || "";
            document.getElementById("external_description").value = contentData.description || "";
            document.getElementById("contentType").value = contentData.content_type || "";

            showSelectedSection(); // Ensure correct section is shown

            tags = contentData.tags ? contentData.tags.split(",") : [];
            updateTagDisplay();

            document.getElementById("videoUrl").value = contentData.video_url || "";
            document.getElementById("courseUrl").value = contentData.course_url || "";
            document.getElementById("platformName").value = contentData.platform_name || "";
            document.getElementById("articleUrl").value = contentData.article_url || "";
            document.getElementById("author").value = contentData.author || "";
            document.getElementById("audioSource").value = contentData.audio_source || "upload";
            document.getElementById("audioUrl").value = contentData.audio_url || "";

            $("#externalContentModal").modal("show");
        });
    });
});
