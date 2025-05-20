document.addEventListener("DOMContentLoaded", function () {
    const feedbackForm = document.getElementById("feedback_feedbackForm");
    const feedbackTitle = document.getElementById("feedback_feedbackTitle");
    const tagInput = document.getElementById("feedback_feedback_tagInput");
    const tagContainer = document.getElementById("feedback_tagDisplay");
    const hiddenTagList = document.getElementById("feedback_tagList");

    const addFeedbackBtn = document.getElementById("addFeedbackBtn");
    const feedbackModalLabel = document.getElementById("feedback_feedbackModalLabel");
    const feedbackModal = new bootstrap.Modal(document.getElementById('feedback_feedbackModal'));

    const selectedQuestionsBody = document.getElementById("feedback_selectedQuestionsBody");
    const selectedQuestionsWrapper = document.getElementById("feedback_selectedQuestionsWrapper");
    const selectedQuestionIdsInput = document.getElementById("feedback_selectedQuestionIds");
    const selectedQuestionCountInput = document.getElementById("feedback_selectedQuestionCount");

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

    addFeedbackBtn.addEventListener("click", function () {
        feedbackForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();

        selectedQuestionsBody.innerHTML = "";
        selectedQuestionIdsInput.value = "";
        selectedQuestionCountInput.value = "";
        selectedQuestionsWrapper.style.display = "none";

        feedbackModalLabel.textContent = "Add Feedback";
        feedbackModal.show();
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

    document.querySelectorAll(".edit-feedback").forEach(button => {
        button.addEventListener("click", function () {
            const feedbackData = JSON.parse(this.dataset.feedback);
            const feedbackId = feedbackData.id;

            fetch(`index.php?controller=FeedbackController&action=getFeedbackById&id=${feedbackId}`)
                .then(response => response.json())
                .then(feedbackData => {
                    feedbackTitle.value = feedbackData.title;

                    tagContainer.innerHTML = "";
                    tags = [];
                    if (feedbackData.tags) {
                        feedbackData.tags.split(",").forEach(tag => addTag(tag.trim()));
                    }

                    selectedQuestionsBody.innerHTML = "";
                    selectedQuestionIdsInput.value = "";
                    if (feedbackData.selected_questions && feedbackData.selected_questions.length > 0) {
                        feedbackData.selected_questions.forEach(question => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${question.title}</td>
                                <td>${question.type}</td>
                                <td>${question.tags}</td>
                            `;
                            selectedQuestionsBody.appendChild(row);
                        });

                        const selectedIds = feedbackData.selected_questions.map(q => q.id);
                        selectedQuestionIdsInput.value = selectedIds.join(",");
                        selectedQuestionCountInput.value = selectedIds.length;
                        selectedQuestionsWrapper.style.display = "block";
                    } else {
                        selectedQuestionsWrapper.style.display = "none";
                    }

                    feedbackModalLabel.textContent = "Edit Feedback";
                    feedbackModal.show();
                })
                .catch(error => {
                    console.error("Error fetching feedback:", error);
                    alert("Failed to load feedback data.");
                });
        });
    });

    document.querySelector('button[data-bs-dismiss="modal"]').addEventListener('click', function () {
        feedbackForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();

        selectedQuestionsBody.innerHTML = "";
        selectedQuestionIdsInput.value = "";
        selectedQuestionCountInput.value = "";
        selectedQuestionsWrapper.style.display = "none";
    });

    feedbackForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const isValid = validateFeedbackForm();
        if (!isValid) {
            console.log("Form validation failed.");
            return;
        }

        alert("Feedback form submitted successfully!");
        feedbackModal.hide();
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
