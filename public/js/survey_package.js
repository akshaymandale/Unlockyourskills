document.addEventListener("DOMContentLoaded", function () {
    const surveyForm = document.getElementById("survey_surveyForm");
    const surveyTitle = document.getElementById("survey_surveyTitle");
    const tagInput = document.getElementById("survey_survey_tagInput");
    const tagContainer = document.getElementById("survey_tagDisplay");
    const hiddenTagList = document.getElementById("survey_tagList");

    const addSurveyBtn = document.getElementById("addSurveyBtn");
    const surveyModalLabel = document.getElementById("survey_surveyModalLabel");
    const surveyModal = new bootstrap.Modal(document.getElementById('survey_surveyModal'));

    const selectedQuestionsBody = document.getElementById("survey_selectedQuestionsBody");
    const selectedQuestionsWrapper = document.getElementById("survey_selectedQuestionsWrapper");
    const selectedQuestionIdsInput = document.getElementById("survey_selectedQuestionIds");
    const selectedQuestionCountInput = document.getElementById("survey_selectedQuestionCount");

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

    tagInput.addEventListener("keydown", function (event) {
        if (event.key === "Backspace" && tagInput.value === "" && tags.length > 0) {
            removeTag(tags[tags.length - 1]);
        }
    });

    tagContainer.addEventListener("click", function (event) {
        if (event.target.classList.contains("remove-tag")) {
            const tagText = event.target.getAttribute("data-tag");
            removeTag(tagText);
        }
    });

    addSurveyBtn.addEventListener("click", function () {
        surveyForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();

        selectedQuestionsBody.innerHTML = "";
        selectedQuestionIdsInput.value = "";
        selectedQuestionCountInput.value = "";
        selectedQuestionsWrapper.style.display = "none";

        surveyModalLabel.textContent = "Add Survey";
        surveyModal.show();
    });



    // Edit Survey Modal Logic (exactly like assessment)
    const editSurveyButtons = document.querySelectorAll(".edit-survey");
    console.log("Found", editSurveyButtons.length, "survey edit buttons");

    const survey_selectedSurveyQuestionsWrapper = document.getElementById("survey_selectedSurveyQuestionsWrapper");
    
    editSurveyButtons.forEach(button => {
        console.log("Attaching event listener to survey edit button");
        button.addEventListener("click", function () {
            console.log("Survey edit button clicked");
            const surveyData = JSON.parse(this.dataset.survey);
            console.log("Survey data:", surveyData);
            const surveyId = surveyData.id;
            console.log("Survey ID:", surveyId);

            // Add hidden input for survey ID
            let surveyIdInput = document.getElementById('surveyId');
            if (!surveyIdInput) {
                surveyIdInput = document.createElement('input');
                surveyIdInput.type = 'hidden';
                surveyIdInput.name = 'surveyId';
                surveyIdInput.id = 'surveyId';
                surveyForm.appendChild(surveyIdInput);
            }
            surveyIdInput.value = surveyId;

            fetch(`index.php?controller=VLRController&action=getSurveyById&id=${surveyId}`)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    surveyTitle.value = data.title;

                    tagContainer.innerHTML = "";
                    tags = [];
                    if (data.tags) {
                        data.tags.split(",").forEach(tag => addTag(tag.trim()));
                    }

                    // Load and display selected questions
                    selectedQuestionsBody.innerHTML = "";
                    selectedQuestionIdsInput.value = "";
                    if (data.selected_questions && data.selected_questions.length > 0) {
                        data.selected_questions.forEach(question => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${question.title}</td>
                                <td>${question.tags}</td>
                                <td>${question.type}</td>
                            `;
                            selectedQuestionsBody.appendChild(row);
                        });

                        const selectedIds = data.selected_questions.map(q => q.id);
                        selectedQuestionIdsInput.value = selectedIds.join(",");
                        selectedQuestionCountInput.value = selectedIds.length;
                        survey_selectedSurveyQuestionsWrapper.style.display = "block";
                    } else {
                        survey_selectedSurveyQuestionsWrapper.style.display = "none";
                    }

                    surveyModalLabel.textContent = "Edit Survey";
                    surveyModal.show();
                })
                .catch(error => {
                    console.error("Error fetching survey:", error);
                    alert("Failed to load survey data.");
                });
        });
    });

    document.querySelector('button[data-bs-dismiss="modal"]').addEventListener('click', function () {
        surveyForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();

        selectedQuestionsBody.innerHTML = "";
        selectedQuestionIdsInput.value = "";
        selectedQuestionCountInput.value = "";
        selectedQuestionsWrapper.style.display = "none";
    });

    surveyForm.addEventListener("submit", function (e) {
        const isValid = validateSurveyForm();
        if (!isValid) {
            e.preventDefault();
            console.log("Form validation failed.");
            return;
        }

        // Form is valid, let it submit normally
        // The form will submit to the action URL specified in the form
    });

    function showSelectedQuestionsGrid() {
        if (selectedQuestionsBody.children.length > 0) {
            selectedQuestionsWrapper.style.display = "block";
        } else {
            selectedQuestionsWrapper.style.display = "none";
        }
    }

    window.showSelectedQuestionsGrid = showSelectedQuestionsGrid;
});
