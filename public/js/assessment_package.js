document.addEventListener("DOMContentLoaded", function () {
    const assessmentForm = document.getElementById("assessment_assessmentForm");
    const assessmentTitle = document.getElementById("assessment_assessmentTitle");
    const tagInput = document.getElementById("assessment_assessment_tagInput");
    const tagContainer = document.getElementById("assessment_tagDisplay");
    const hiddenTagList = document.getElementById("assessment_tagList");

    const numAttempts = document.getElementById("assessment_numAttempts");
    const passingPercentage = document.getElementById("assessment_passingPercentage");
    const timeLimit = document.getElementById("assessment_timeLimit");
    const negativeMarkingRadios = document.getElementsByName("assessment_negativeMarking");
    const negativeMarkingPercentageWrapper = document.getElementById("assessment_negativeMarkingPercentageWrapper");
    const negativeMarkingPercentage = document.getElementById("assessment_negativeMarkingPercentage");
    const assessmentTypeRadios = document.getElementsByName("assessment_assessmentType");
    const numberOfQuestionsWrapper = document.getElementById("assessment_numberOfQuestionsWrapper");
    const numberOfQuestions = document.getElementById("assessment_numberOfQuestions");

    const addAssessmentBtn = document.getElementById("addAssessmentBtn");
    const assessmentModalLabel = document.getElementById("assessment_assessmentModalLabel");
    const assessmentModal = new bootstrap.Modal(document.getElementById('assessment_assessmentModal'));

    let tags = [];

    // Function to Create and Display Tags
    function addTag(tagText) {
        if (tagText.trim() === "" || tags.includes(tagText)) return;

        tags.push(tagText);

        const tagElement = document.createElement("span");
        tagElement.classList.add("tag", "badge", "bg-primary", "me-2");
        tagElement.innerHTML = `${tagText} <button type="button" class="remove-tag btn-close btn-close-white btn-sm ms-1" data-tag="${tagText}"></button>`;

        tagContainer.appendChild(tagElement);
        updateHiddenInput();
    }

    // Function to Remove a Tag
    function removeTag(tagText) {
        tags = tags.filter(tag => tag !== tagText);
        updateHiddenInput();

        document.querySelectorAll(".tag").forEach(tagEl => {
            if (tagEl.textContent.includes(tagText)) {
                tagEl.remove();
            }
        });
    }

    // Function to Update Hidden Input
    function updateHiddenInput() {
        hiddenTagList.value = tags.join(",");
    }

    // Input: Add tag on Enter
    tagInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            addTag(tagInput.value.trim());
            tagInput.value = "";
        }
    });

    // Input: Remove last tag on Backspace
    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    // Click: Remove tag
    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    // Toggle functions
    function toggleNegativeMarkingOptions() {
        const isYes = [...negativeMarkingRadios].find(r => r.checked)?.value === "Yes";
        negativeMarkingPercentageWrapper.style.display = isYes ? "block" : "none";
        
        // Clear the Negative Marking Percentage if switching to "No"
        if (!isYes) {
            negativeMarkingPercentage.value = "";  // Clear input when switching to "No"
        }
    }

    function toggleNumberOfQuestionsField() {
        const isDynamic = [...assessmentTypeRadios].find(r => r.checked)?.value === "Dynamic";
        numberOfQuestionsWrapper.style.display = isDynamic ? "block" : "none";
        
        // Clear the number of questions input if changing to/from Dynamic
        if (!isDynamic) {
            numberOfQuestions.value = "";  // Clear input when switching to Fixed
        }
    }

    negativeMarkingRadios.forEach(r => r.addEventListener("change", toggleNegativeMarkingOptions));
    assessmentTypeRadios.forEach(r => r.addEventListener("change", toggleNumberOfQuestionsField));

    // Reset modal and show
    addAssessmentBtn.addEventListener("click", function () {
        assessmentForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();

        document.querySelector('input[name="assessment_negativeMarking"][value="No"]').checked = true;
        document.querySelector('input[name="assessment_assessmentType"][value="Fixed"]').checked = true;

        toggleNegativeMarkingOptions();
        toggleNumberOfQuestionsField();

        assessmentModalLabel.textContent = "Add Assessment";
        assessmentModal.show();
    });

    // Cancel button click event
    document.querySelector('button[data-bs-dismiss="modal"]').addEventListener('click', function () {
        // Reset the form fields
        assessmentForm.reset();
        
        // Reset tags (as they are not part of the form)
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();
        
        // Reset the default selections for radio buttons
        document.querySelector('input[name="assessment_negativeMarking"][value="No"]').checked = true;
        document.querySelector('input[name="assessment_assessmentType"][value="Fixed"]').checked = true;
        
        // Reset other dynamic fields
        toggleNegativeMarkingOptions();
        toggleNumberOfQuestionsField();
    });

    // Form Submit 
    assessmentForm.addEventListener("submit", function (e) {
        e.preventDefault();
    
        const isValid = validateAssessmentForm();
        if (!isValid) {
            console.log("Form validation failed.");
            return;
        }
    
        alert("Form submitted successfully!");
        assessmentModal.hide();
    });
});
