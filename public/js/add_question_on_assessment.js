document.addEventListener("DOMContentLoaded", function () {
    const addQuestionBtn = document.getElementById("assessment_addQuestionBtn");
    const questionModalEl = document.getElementById("assessment_questionModal");
    let questionModal;
    try {
        questionModal = new bootstrap.Modal(questionModalEl);
    } catch (e) { }

    const questionTableBody = document.getElementById("assessment_questionTableBody");
    const loopSelectedBtn = document.getElementById("assessment_loopQuestionsBtn");
    const paginationContainer = document.getElementById("assessment_pagination");
    const selectedWrapper = document.getElementById("assessment_selectedQuestionsWrapper");
    const gridBody = document.getElementById("assessment_selectedQuestionsBody");

    let questionsData = [];
    let persistentSelectedQuestions = new Set(); // Only persisted on loop
    let temporarySelections = new Set(); // For checkbox state during modal open
    let currentPage = 1;

    function normalizeId(id) {
        return String(id);
    }

    async function fetchQuestions(page = 1) {
        const search = document.getElementById("assessment_questionSearch").value;
        const marks = document.getElementById("assessment_filterMarks").value;
        const type = document.getElementById("assessment_filterType").value;
        const limit = document.getElementById("assessment_showEntries").value;

        currentPage = page;

        const params = new URLSearchParams({ search, marks, type, limit, page });
        const response = await fetch(`index.php?controller=AssessmentController&action=getQuestions&${params.toString()}`);
        const data = await response.json();

        questionsData = data.questions;
        renderQuestionsTable();
        renderPagination(data.totalPages);
    }

    function renderQuestionsTable() {
        questionTableBody.innerHTML = "";

        questionsData.forEach((q) => {
            const qid = normalizeId(q.id);
            const checked = temporarySelections.has(qid) ? "checked" : "";
            const row = `
                <tr>
                    <td><input type="checkbox" class="question-checkbox" value="${qid}" ${checked}></td>
                    <td>${q.question_text}</td>
                    <td>${q.tags}</td>
                    <td>${q.marks}</td>
                    <td>${q.question_type}</td>
                </tr>
            `;
            questionTableBody.insertAdjacentHTML("beforeend", row);
        });
    }

    function renderPagination(totalPages) {
        paginationContainer.innerHTML = "";
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = document.createElement("button");
            pageBtn.textContent = i;
            pageBtn.className = `btn btn-sm ${i === currentPage ? 'btn-primary' : 'btn-outline-primary'} mx-1`;
            pageBtn.addEventListener("click", () => fetchQuestions(i));
            paginationContainer.appendChild(pageBtn);
        }
    }

    async function loadFilterOptions() {
        const response = await fetch(`index.php?controller=AssessmentController&action=getFilterOptions`);
        const data = await response.json();

        const marksSelect = document.getElementById("assessment_filterMarks");
        marksSelect.innerHTML = `<option value="">All Marks</option>`;
        data.marks.forEach(mark => {
            marksSelect.insertAdjacentHTML("beforeend", `<option value="${mark}">${mark} Mark${mark > 1 ? 's' : ''}</option>`);
        });

        const typeSelect = document.getElementById("assessment_filterType");
        typeSelect.innerHTML = `<option value="">All Types</option>`;
        data.types.forEach(type => {
            typeSelect.insertAdjacentHTML("beforeend", `<option value="${type}">${type}</option>`);
        });
    }

    loopSelectedBtn.addEventListener("click", async function () {
        if (temporarySelections.size === 0) {
            alert("Please select at least one question.");
            return;
        }

        try {
            const response = await fetch(`index.php?controller=AssessmentController&action=getSelectedQuestions`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ ids: Array.from(temporarySelections) })
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                alert("Invalid response from server.");
                return;
            }

            if (!data.questions) {
                alert("Failed to fetch questions.");
                return;
            }

            gridBody.innerHTML = "";
            data.questions.forEach(q => {
                gridBody.insertAdjacentHTML("beforeend", `
                    <tr>
                        <td>${q.question_text}</td>
                        <td>${q.tags}</td>
                        <td>${q.marks}</td>
                        <td>${q.question_type}</td>
                    </tr>
                `);
            });

            selectedWrapper.style.display = "block";

            // Persist only after looping
            persistentSelectedQuestions = new Set(temporarySelections);

            // ✅ Add this line to update hidden input for validation
            document.getElementById("assessment_selectedQuestionCount").value = temporarySelections.size;
            
            if (questionModal && typeof questionModal.hide === "function") {
                questionModal.hide();
            } else if (window.jQuery) {
                $('#assessment_questionModal').modal('hide');
            }

        } catch (error) {
            alert("Error looping questions.");
            console.error(error);
        }
    });

    addQuestionBtn.addEventListener("click", function () {
        temporarySelections = new Set(persistentSelectedQuestions);

        try {
            if (questionModal && typeof questionModal.show === "function") {
                questionModal.show();
            } else if (window.jQuery) {
                $('#assessment_questionModal').modal('show');
            }
        } catch (e) {
            console.error("Error showing modal:", e);
        }

        loadFilterOptions();
        fetchQuestions();
    });

    questionTableBody.addEventListener("change", function (e) {
        if (e.target.classList.contains("question-checkbox")) {
            const id = normalizeId(e.target.value);
            if (e.target.checked) temporarySelections.add(id);
            else temporarySelections.delete(id);
        }
    });

    document.getElementById("assessment_refreshBtn").addEventListener("click", () => fetchQuestions(currentPage));
    document.getElementById("assessment_questionSearch").addEventListener("input", () => fetchQuestions(1));
    document.getElementById("assessment_filterMarks").addEventListener("change", () => fetchQuestions(1));
    document.getElementById("assessment_filterType").addEventListener("change", () => fetchQuestions(1));
    document.getElementById("assessment_showEntries").addEventListener("change", () => fetchQuestions(1));

    document.getElementById("assessment_clearFiltersBtn").addEventListener("click", () => {
        document.getElementById("assessment_questionSearch").value = "";
        document.getElementById("assessment_filterMarks").value = "";
        document.getElementById("assessment_filterType").value = "";
        document.getElementById("assessment_showEntries").value = "10";
        temporarySelections.clear();
        fetchQuestions(1);
    });

    document.getElementById("assessment_modalCloseBtn").addEventListener("click", () => {
        // Do not persist temporary selections
        temporarySelections = new Set(persistentSelectedQuestions);

        if (questionModal && typeof questionModal.hide === "function") {
            questionModal.hide();
        } else if (window.jQuery) {
            $('#assessment_questionModal').modal('hide');
        }
    });
});
