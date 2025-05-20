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

    function populateSelectedQuestions(questions) {
        selectedQuestionsBody.innerHTML = "";

        if (Array.isArray(questions) && questions.length > 0) {
            questions.forEach(q => {
                const row = document.createElement("tr");

                const titleCell = document.createElement("td");
                titleCell.textContent = q.title || "";
                row.appendChild(titleCell);

                const tagsCell = document.createElement("td");
                tagsCell.textContent = q.tags || "";
                row.appendChild(tagsCell);

                const typeCell = document.createElement("td");
                typeCell.textContent = q.type || "";
                row.appendChild(typeCell);

                selectedQuestionsBody.appendChild(row);
            });

            selectedQuestionsWrapper.style.display = "block";
            selectedQuestionIdsInput.value = questions.map(q => q.id).join(",");
            selectedQuestionCountInput.value = questions.length;
        } else {
            selectedQuestionsWrapper.style.display = "none";
            selectedQuestionIdsInput.value = "";
            selectedQuestionCountInput.value = "";
        }
    }

    document.querySelectorAll(".edit-survey").forEach(button => {
        button.addEventListener("click", function () {
            const surveyData = JSON.parse(this.dataset.survey);
            const surveyId = surveyData.id;

            fetch(`index.php?controller=SurveyController&action=getSurveyById&id=${surveyId}`)
                .then(response => response.json())
                .then(surveyData => {
                    surveyTitle.value = surveyData.title;

                    tagContainer.innerHTML = "";
                    tags = [];
                    if (surveyData.tags) {
                        surveyData.tags.split(",").forEach(tag => addTag(tag.trim()));
                    }

                    selectedQuestionsBody.innerHTML = "";
                    selectedQuestionIdsInput.value = "";
                    if (surveyData.selected_questions && surveyData.selected_questions.length > 0) {
                        surveyData.selected_questions.forEach(question => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${question.title}</td>
                                <td>${question.type}</td>
                                <td>${question.tags}</td>
                            `;
                            selectedQuestionsBody.appendChild(row);
                        });

                        const selectedIds = surveyData.selected_questions.map(q => q.id);
                        selectedQuestionIdsInput.value = selectedIds.join(",");
                        selectedQuestionCountInput.value = selectedIds.length;
                        selectedQuestionsWrapper.style.display = "block";
                    } else {
                        selectedQuestionsWrapper.style.display = "none";
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
        e.preventDefault();

        const isValid = validateSurveyForm();
        if (!isValid) {
            console.log("Form validation failed.");
            return;
        }

        alert("Survey form submitted successfully!");
        surveyModal.hide();
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
