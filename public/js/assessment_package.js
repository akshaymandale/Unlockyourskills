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

    const selectedQuestionsBody = document.getElementById("assessment_selectedQuestionsBody");
    const selectedQuestionsWrapper = document.getElementById("assessment_selectedQuestionsWrapper");
    const selectedQuestionIdsInput = document.getElementById("assessment_selectedQuestionIds");
    const selectedQuestionCountInput = document.getElementById("assessment_selectedQuestionCount");

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

    function toggleNegativeMarkingOptions() {
        const isYes = [...negativeMarkingRadios].find(r => r.checked)?.value === "Yes";
        negativeMarkingPercentageWrapper.style.display = isYes ? "block" : "none";
        if (!isYes) {
            negativeMarkingPercentage.value = "";
        }
    }

    function toggleNumberOfQuestionsField() {
        const isDynamic = [...assessmentTypeRadios].find(r => r.checked)?.value === "Dynamic";
        numberOfQuestionsWrapper.style.display = isDynamic ? "block" : "none";
        if (!isDynamic) {
            numberOfQuestions.value = "";
        }
    }

    negativeMarkingRadios.forEach(r => r.addEventListener("change", toggleNegativeMarkingOptions));
    assessmentTypeRadios.forEach(r => r.addEventListener("change", toggleNumberOfQuestionsField));

    addAssessmentBtn.addEventListener("click", function () {
        assessmentForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();

        // Clear assessment ID for new assessment
        let assessmentIdInput = document.getElementById('assessmentId');
        if (assessmentIdInput) {
            assessmentIdInput.remove();
        }

        selectedQuestionsBody.innerHTML = "";
        selectedQuestionIdsInput.value = "";
        selectedQuestionCountInput.value = "";
        selectedQuestionsWrapper.style.display = "none";

        document.querySelector('input[name="assessment_negativeMarking"][value="No"]').checked = true;
        document.querySelector('input[name="assessment_assessmentType"][value="Fixed"]').checked = true;

        toggleNegativeMarkingOptions();
        toggleNumberOfQuestionsField();

        assessmentModalLabel.textContent = "Add Assessment";
        assessmentModal.show();
    });

    // Utility: Populate selected questions in the grid
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

                const marksCell = document.createElement("td");
                marksCell.textContent = q.marks || "";
                row.appendChild(marksCell);

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

    // Edit Assessment Modal Logic
    document.querySelectorAll(".edit-assessment").forEach(button => {
        button.addEventListener("click", function () {
            const assessmentData = JSON.parse(this.dataset.assessment);

            /*assessmentTitle.value = assessmentData.title;
            numAttempts.value = assessmentData.num_attempts;
            passingPercentage.value = assessmentData.passing_percentage;
            timeLimit.value = assessmentData.time_limit;

            document.querySelectorAll('input[name="assessment_negativeMarking"]').forEach(radio => {
                radio.checked = (radio.value === assessmentData.negative_marking);
            });

            if (assessmentData.negative_marking_percentage) {
                negativeMarkingPercentage.value = assessmentData.negative_marking_percentage;
                negativeMarkingPercentageWrapper.style.display = "block";
            } else {
                negativeMarkingPercentageWrapper.style.display = "none";
            }

            document.querySelectorAll('input[name="assessment_assessmentType"]').forEach(radio => {
                radio.checked = (radio.value === assessmentData.assessment_type);
            });

            if (assessmentData.num_questions_to_display) {
                numberOfQuestions.value = assessmentData.num_questions_to_display;
                numberOfQuestionsWrapper.style.display = "block";
            } else {
                numberOfQuestionsWrapper.style.display = "none";
            }

            tagContainer.innerHTML = "";
            tags = [];
            if (assessmentData.tags) {
                assessmentData.tags.split(",").forEach(tag => addTag(tag.trim()));
            }

            console.log(assessmentData.id);*/

        const assessmentId = assessmentData.id;
    
        fetch(`/unlockyourskills/vlr/assessment-packages/${assessmentId}`)
            .then(response => response.json())
            .then(assessmentData => {
                console.log('Assessment data received:', assessmentData);
                console.log('Selected questions:', assessmentData.selected_questions);
                console.log('Questions count:', assessmentData.selected_questions?.length || 0);

                // Add hidden input for assessment ID (crucial for update vs insert logic)
                let assessmentIdInput = document.getElementById('assessmentId');
                if (!assessmentIdInput) {
                    assessmentIdInput = document.createElement('input');
                    assessmentIdInput.type = 'hidden';
                    assessmentIdInput.name = 'assessmentId';
                    assessmentIdInput.id = 'assessmentId';
                    assessmentForm.appendChild(assessmentIdInput);
                }
                assessmentIdInput.value = assessmentData.id;

                assessmentTitle.value = assessmentData.title;
                numAttempts.value = assessmentData.num_attempts;
                passingPercentage.value = assessmentData.passing_percentage;
                timeLimit.value = assessmentData.time_limit;
    
                document.querySelectorAll('input[name="assessment_negativeMarking"]').forEach(radio => {
                    radio.checked = (radio.value === assessmentData.negative_marking);
                });
    
                if (assessmentData.negative_marking_percentage) {
                    negativeMarkingPercentage.value = assessmentData.negative_marking_percentage;
                    negativeMarkingPercentageWrapper.style.display = "block";
                } else {
                    negativeMarkingPercentageWrapper.style.display = "none";
                }
    
                document.querySelectorAll('input[name="assessment_assessmentType"]').forEach(radio => {
                    radio.checked = (radio.value === assessmentData.assessment_type);
                });
    
                if (assessmentData.num_questions_to_display) {
                    numberOfQuestions.value = assessmentData.num_questions_to_display;
                    numberOfQuestionsWrapper.style.display = "block";
                } else {
                    numberOfQuestionsWrapper.style.display = "none";
                }
    
                tagContainer.innerHTML = "";
                tags = [];
                if (assessmentData.tags) {
                    assessmentData.tags.split(",").forEach(tag => addTag(tag.trim()));
                }
    
                // Load and display selected questions
                console.log('About to load questions into table...');
                console.log('selectedQuestionsBody element:', selectedQuestionsBody);
                console.log('selectedQuestionsWrapper element:', selectedQuestionsWrapper);
                
                selectedQuestionsBody.innerHTML = "";
                selectedQuestionIdsInput.value = "";
                if (assessmentData.selected_questions && assessmentData.selected_questions.length > 0) {
                    console.log('Processing', assessmentData.selected_questions.length, 'questions');
                    assessmentData.selected_questions.forEach((question, index) => {
                        console.log('Processing question', index + 1, ':', question);
                        const row = document.createElement("tr");
                        row.innerHTML = `
                            <td>${question.title}</td>
                            <td>${question.type}</td>
                            <td>${question.tags}</td>
                            <td>${question.marks}</td>
                        `;
                        selectedQuestionsBody.appendChild(row);
                        console.log('Added question row:', row.innerHTML);
                    });
    
                    const selectedIds = assessmentData.selected_questions.map(q => q.id);
                    selectedQuestionIdsInput.value = selectedIds.join(",");
                    selectedQuestionCountInput.value = selectedIds.length;
                    selectedQuestionsWrapper.style.display = "block";
                    console.log('Questions loaded successfully. IDs:', selectedIds);
                } else {
                    console.log('No questions found, hiding wrapper');
                    selectedQuestionsWrapper.style.display = "none";
                }
    
                assessmentModalLabel.textContent = "Edit Assessment";
                assessmentModal.show();
            })
            .catch(error => {
                console.error("Error fetching assessment:", error);
                alert("Failed to load assessment data.");
            });
    


            // Load selected questions
            /*if (assessmentData.selected_questions) {
                //here
                populateSelectedQuestions(assessmentData.selected_questions);
            } else {
                selectedQuestionsBody.innerHTML = "";
                selectedQuestionsWrapper.style.display = "none";
                selectedQuestionIdsInput.value = "";
                selectedQuestionCountInput.value = "";
            }*/

            assessmentModalLabel.textContent = "Edit Assessment";
            assessmentModal.show();
        });
    });

    document.querySelector('button[data-bs-dismiss="modal"]').addEventListener('click', function () {
        assessmentForm.reset();
        tags = [];
        tagContainer.innerHTML = "";
        updateHiddenInput();

        // Clear assessment ID when modal is closed
        let assessmentIdInput = document.getElementById('assessmentId');
        if (assessmentIdInput) {
            assessmentIdInput.remove();
        }

        selectedQuestionsBody.innerHTML = "";
        selectedQuestionIdsInput.value = "";
        selectedQuestionCountInput.value = "";
        selectedQuestionsWrapper.style.display = "none";

        document.querySelector('input[name="assessment_negativeMarking"][value="No"]').checked = true;
        document.querySelector('input[name="assessment_assessmentType"][value="Fixed"]').checked = true;

        toggleNegativeMarkingOptions();
        toggleNumberOfQuestionsField();
    });

    assessmentForm.addEventListener("submit", function (e) {
        e.preventDefault();

        const isValid = validateAssessmentForm();
        if (!isValid) {
            console.log("Form validation failed.");
            return;
        }

        // If validation passes, submit the form
        console.log("Form validation passed. Submitting...");
        assessmentForm.submit();
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
