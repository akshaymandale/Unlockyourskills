document.addEventListener("DOMContentLoaded", function () {
    const addQuestionBtn = document.getElementById("assessment_addQuestionBtn");
    const questionModal = new bootstrap.Modal(document.getElementById("assessment_questionModal"));
    const questionTableBody = document.getElementById("assessment_questionTableBody");
    const loopSelectedBtn = document.getElementById("assessment_loopQuestionsBtn");
    const assessmentQuestionsGrid = document.getElementById("assessment_selectedQuestionsGrid");

    let questionsData = [];
    let selectedQuestions = new Set();

    const BASE_URL = "http://localhost/unlockyourskills";

    // Fetch questions from backend
    async function fetchQuestions() {
        const search = document.getElementById("assessment_questionSearch").value;
        const marks = document.getElementById("assessment_filterMarks").value;
        const type = document.getElementById("assessment_filterType").value;
        const limit = document.getElementById("assessment_showEntries").value;

        const params = new URLSearchParams({ search, marks, type, limit });
        //const response = await fetch(`index.php?controller=AssessmentController/getQuestions?${params.toString()}`);
        const response = await fetch(`index.php?controller=AssessmentController&action=getQuestions&search=&marks=&type=&limit=10`);
        const data = await response.json();

        questionsData = data.questions;
        renderQuestionsTable();
    }

    // Render the question grid
    function renderQuestionsTable() {
        questionTableBody.innerHTML = "";

        questionsData.forEach((q) => {
            const checked = selectedQuestions.has(q.id) ? "checked" : "";
            const row = `
                <tr>
                    <td><input type="checkbox" class="question-checkbox" value="${q.id}" ${checked}></td>
                    <td>${q.question_text}</td>
                    <td>${q.tags}</td>
                    <td>${q.marks}</td>
                    <td>${q.question_type}</td>
                </tr>
            `;
            questionTableBody.insertAdjacentHTML("beforeend", row);
        });
    }

    // Handle select all checkbox
    document.getElementById("assessment_selectAllQuestions").addEventListener("change", function () {
        const checkboxes = document.querySelectorAll(".question-checkbox");
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            if (cb.checked) selectedQuestions.add(cb.value);
            else selectedQuestions.delete(cb.value);
        });
    });

    // Handle individual checkbox change
    questionTableBody.addEventListener("change", function (e) {
        if (e.target.classList.contains("question-checkbox")) {
            const id = e.target.value;
            if (e.target.checked) selectedQuestions.add(id);
            else selectedQuestions.delete(id);
        }
    });

    // Refresh button
    document.getElementById("assessment_refreshBtn").addEventListener("click", fetchQuestions);

    // Search input
    document.getElementById("assessment_questionSearch").addEventListener("input", fetchQuestions);

    // Filters
    document.getElementById("assessment_filterMarks").addEventListener("change", fetchQuestions);
    document.getElementById("assessment_filterType").addEventListener("change", fetchQuestions);
    document.getElementById("assessment_showEntries").addEventListener("change", fetchQuestions);

    // Show question modal
    addQuestionBtn.addEventListener("click", function () {
        questionModal.show();
        fetchQuestions();
    });

    // Loop selected questions to main form
    loopSelectedBtn.addEventListener("click", async function () {
        if (selectedQuestions.size === 0) {
            alert("Please select at least one question.");
            return;
        }

        const response = await fetch(`${BASE_URL}/AssessmentController/getSelectedQuestions`, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ids: Array.from(selectedQuestions) })
        });
        const data = await response.json();

        assessmentQuestionsGrid.innerHTML = "";
        data.questions.forEach(q => {
            const row = `
                <tr>
                    <td><input type="checkbox" name="selectedQuestions[]" value="${q.id}" checked></td>
                    <td>${q.question_text}</td>
                    <td>${q.tags}</td>
                    <td>${q.marks}</td>
                    <td>${q.question_type}</td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-question-btn" data-id="${q.id}">Delete</button></td>
                </tr>
            `;
            assessmentQuestionsGrid.insertAdjacentHTML("beforeend", row);
        });

        questionModal.hide();
    });

    // Remove individual question from grid
    assessmentQuestionsGrid.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-question-btn")) {
            const id = e.target.getAttribute("data-id");
            selectedQuestions.delete(id);
            e.target.closest("tr").remove();
        }
    });
});
