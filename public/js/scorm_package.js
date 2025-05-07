document.addEventListener("DOMContentLoaded", function () {
    const scormModal = new bootstrap.Modal(document.getElementById("scormModal")); // Bootstrap Modal
    const scormForm = document.getElementById("scormForm");
    const scormTitle = document.getElementById("scorm_title");
    const zipFile = document.getElementById("zipFile");
    const version = document.getElementById("version");
    const language = document.getElementById("language");
    const scormCategory = document.getElementById("scormCategory");
    const description = document.getElementById("description");
    const timeLimit = document.getElementById("timeLimit");
    const scormId = document.getElementById("scorm_id"); // Hidden input
    const existingZip = document.getElementById("existing_zip");
    const zipDisplay = document.getElementById("existingZipDisplay");
    const mobileSupport = document.getElementsByName("mobileSupport");
    const assessment = document.getElementsByName("assessment");

    // Tag Elements
    const tagInput = document.getElementById("tagInput");
    const tagContainer = document.getElementById("tagDisplay");
    const hiddenTagList = document.getElementById("tagList");

    let tags = []; // Store tags

    // Function to Create and Display Tags
    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return; // Prevent empty/duplicate tags

        tags.push(tagText); // Add tag to array

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;

        tagContainer.appendChild(tagElement);
        updateHiddenInput();
    }

    // Function to Remove a Tag
    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText); // Remove only the clicked tag
        updateHiddenInput();

        // Remove tag from display
        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    // Function to Update Hidden Input with Tags
    function updateHiddenInput() {
        hiddenTagList.value = tags.join(",");
    }

    // Listen for Enter Key in the Input Field
    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = ""; // Clear input after adding
        }
    });

    // Listen for Clicks on Remove Buttons
    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    // Remove Last Tag when Pressing Backspace in an Empty Input
    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]); // Remove last tag
        }
    });

    // Open Modal for Editing SCORM
    document.querySelectorAll(".edit-scorm").forEach(button => {
        button.addEventListener("click", function () {
            const scormData = JSON.parse(this.dataset.scorm); // Get data from button attribute

            scormId.value = scormData.id; // Set ID for edit
            scormTitle.value = scormData.title;
            version.value = scormData.version;
            language.value = scormData.language;
            scormCategory.value = scormData.scorm_category;
            description.value = scormData.description;
            timeLimit.value = scormData.time_limit;
            existingZip.value = scormData.zip_file;

            // Display Existing ZIP File
            if (scormData.zip_file) {
                zipDisplay.innerHTML = `Current File: <a href="uploads/scorm/${scormData.zip_file}" target="_blank">${scormData.zip_file}</a>`;
            } else {
                zipDisplay.innerHTML = "No SCORM ZIP uploaded.";
            }

            // Pre-select Mobile Support
            mobileSupport.forEach(radio => {
                if (radio.value === scormData.mobile_support) {
                    radio.checked = true;
                }
            });

            // Pre-select Assessment
            assessment.forEach(radio => {
                if (radio.value === scormData.assessment) {
                    radio.checked = true;
                }
            });

            // Pre-fill Tags
            tagContainer.innerHTML = ""; // Clear existing tags
            tags = []; // Reset tag array
            if (scormData.tags) {
                scormData.tags.split(",").forEach(tag => addTag(tag.trim()));
            }

            document.getElementById("scormModalLabel").textContent = "Edit SCORM Package"; // Change modal title
            scormModal.show(); // Open modal
        });
    });

    // Open Modal for Adding New SCORM
    const addScormBtn = document.getElementById("addScormBtn");
    if (addScormBtn) {
        addScormBtn.addEventListener("click", function () {
            // Reset Form Fields
            scormForm.reset();
            scormId.value = "";

            // Clear Tags
            tags = [];
            tagContainer.innerHTML = "";
            hiddenTagList.value = "";

            // Clear Existing ZIP File Display
            existingZip.value = "";
            zipDisplay.innerHTML = "";

            // 🚀 **Do NOT Reset Radio Buttons - Keep Default "No" Selected**
            document.querySelector('input[name="mobileSupport"][value="No"]').checked = true;
            document.querySelector('input[name="assessment"][value="No"]').checked = true;

            // Set Modal Title to "Add SCORM Package"
            document.getElementById("scormModalLabel").textContent = "Add SCORM Package";

            scormModal.show();
        });
    }
});