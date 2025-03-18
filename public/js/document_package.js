document.addEventListener("DOMContentLoaded", function () {
    const documentModal = new bootstrap.Modal(document.getElementById("documentModal"));

    const documentForm = document.getElementById("documentForm");
    const documentCategory = document.getElementById("documentCategory");

    // Tag Elements
    const tagInput = document.getElementById("documentTagInput");
    const tagContainer = document.getElementById("documentTagDisplay");
    const hiddenTagList = document.getElementById("documentTagList");
    let tags = [];

    // Function to Show or Hide Fields Based on Category Selection (Updated)
    function toggleCategoryFields() {
        const selectedCategory = documentCategory.value;
        document.getElementById("wordExcelPptFields").style.display = (selectedCategory === "Word/Excel/PPT Files") ? "block" : "none";
        document.getElementById("ebookManualFields").style.display = (selectedCategory === "E-Book & Manual") ? "block" : "none";
        document.getElementById("researchFields").style.display = (selectedCategory === "Research Paper & Case Studies") ? "block" : "none";
        document.getElementById("researchDetails").style.display = (selectedCategory === "Research Paper & Case Studies") ? "flex" : "none";
    }

    // Open Modal for Adding New Document
    document.getElementById("addDocumentBtn").addEventListener("click", function () {
        documentForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        hiddenTagList.value = "";

        // Clear validation messages and invalid fields
        document.querySelectorAll(".error-message").forEach(el => el.textContent = "");
        document.querySelectorAll(".is-invalid").forEach(el => el.classList.remove("is-invalid"));

        // Reset category-based sections
        toggleCategoryFields();

        document.getElementById("documentModalLabel").textContent = translations["document.modal.add"] || "Add Document";

        document.querySelector('input[name="mobileSupport"][value="No"]').checked = true;

        documentModal.show();
    });

    // Listen for Category Change
    documentCategory.addEventListener("change", toggleCategoryFields);

    // Function to Add Tags
    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;

        tags.push(tagText);

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag" data-tag="${tagText}">&times;</button>`;

        tagContainer.appendChild(tagElement);
        updateHiddenInput();
    }

    // Function to Remove Tags
    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateHiddenInput();

        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    // Update Hidden Input with Tags
    function updateHiddenInput() {
        hiddenTagList.value = tags.join(",");
    }

    // Add Tag on Enter
    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });

    // Remove Tag on Click
    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            removeTag(event.target.getAttribute("data-tag"));
        }
    });

    // Remove Last Tag on Backspace
    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });
});
