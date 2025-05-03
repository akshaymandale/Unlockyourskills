document.addEventListener("DOMContentLoaded", function () {
    const addQuestionBtn = document.getElementById("assessment_addQuestionBtn");
    const questionModal = new bootstrap.Modal(document.getElementById("assessment_questionModal"));
    const questionTableBody = document.getElementById("assessment_questionTableBody");
    const loopSelectedBtn = document.getElementById("assessment_loopQuestionsBtn");
    const assessmentQuestionsGrid = document.getElementById("assessment_selectedQuestionsGrid");
    const paginationContainer = document.getElementById("assessment_pagination");

    let questionsData = [];
    let selectedQuestions = new Set();
    let currentPage = 1;

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

    document.getElementById("assessment_clearFiltersBtn").addEventListener("click", () => {
        document.getElementById("assessment_questionSearch").value = "";
        document.getElementById("assessment_filterMarks").value = "";
        document.getElementById("assessment_filterType").value = "";
        document.getElementById("assessment_showEntries").value = "10"; // default option
        document.getElementById("assessment_selectAllQuestions").checked = false;
        selectedQuestions.clear();
        fetchQuestions(1); // reload the first page with default filters

    });
    

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

    document.getElementById("assessment_selectAllQuestions").addEventListener("change", function () {
        const checkboxes = document.querySelectorAll(".question-checkbox");
        checkboxes.forEach(cb => {
            cb.checked = this.checked;
            if (cb.checked) selectedQuestions.add(cb.value);
            else selectedQuestions.delete(cb.value);
        });
    });

    questionTableBody.addEventListener("change", function (e) {
        if (e.target.classList.contains("question-checkbox")) {
            const id = e.target.value;
            if (e.target.checked) selectedQuestions.add(id);
            else selectedQuestions.delete(id);
        }
    });

    document.getElementById("assessment_refreshBtn").addEventListener("click", () => fetchQuestions(currentPage));
    document.getElementById("assessment_questionSearch").addEventListener("input", () => fetchQuestions(1));
    document.getElementById("assessment_filterMarks").addEventListener("change", () => fetchQuestions(1));
    document.getElementById("assessment_filterType").addEventListener("change", () => fetchQuestions(1));
    document.getElementById("assessment_showEntries").addEventListener("change", () => fetchQuestions(1));

    addQuestionBtn.addEventListener("click", function () {
        questionModal.show();
        loadFilterOptions();
        fetchQuestions();
    });

    async function loadFilterOptions() {
        const response = await fetch(`index.php?controller=AssessmentController&action=getFilterOptions`);

        const data = await response.json();
    
        // Marks dropdown
        const marksSelect = document.getElementById("assessment_filterMarks");
        marksSelect.innerHTML = `<option value="">All Marks</option>`;
        data.marks.forEach(mark => {
            marksSelect.insertAdjacentHTML("beforeend", `<option value="${mark}">${mark} Mark${mark > 1 ? 's' : ''}</option>`);
        });
    
        // Types dropdown
        const typeSelect = document.getElementById("assessment_filterType");
        typeSelect.innerHTML = `<option value="">All Types</option>`;
        data.types.forEach(type => {
            typeSelect.insertAdjacentHTML("beforeend", `<option value="${type}">${type}</option>`);
        });
    }
    
    

    loopSelectedBtn.addEventListener("click", async function () {
        if (selectedQuestions.size === 0) {
            alert("Please select at least one question.");
            return;
        }

        const response = await fetch(`index.php?controller=AssessmentController&action=getSelectedQuestions`, {
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

    assessmentQuestionsGrid.addEventListener("click", function (e) {
        if (e.target.classList.contains("remove-question-btn")) {
            const id = e.target.getAttribute("data-id");
            selectedQuestions.delete(id);
            e.target.closest("tr").remove();
        }
    });

});

/*selected questions code start */
const selectedQuestions = new Map(); // Key = question_id, Value = question object

document.getElementById('assessment_loopQuestionsBtn').addEventListener('click', () => {
    const checkboxes = document.querySelectorAll('.assessment_questionCheckbox:checked');

    checkboxes.forEach(cb => {
        const id = cb.dataset.id;
        const title = cb.dataset.title;
        const tags = cb.dataset.tags;
        const marks = cb.dataset.marks;
        const type = cb.dataset.type;

        if (!selectedQuestions.has(id)) {
            selectedQuestions.set(id, { id, title, tags, marks, type });
        }
    });

    renderSelectedQuestions();
    bootstrap.Modal.getInstance(document.getElementById('assessment_questionModal')).hide(); // close modal
});

function renderSelectedQuestions() {
    const tbody = document.getElementById('assessment_selectedQuestionsBody');
    tbody.innerHTML = "";

    if (selectedQuestions.size === 0) {
        document.getElementById('assessment_selectedQuestionsWrapper').style.display = 'none';
        return;
    }

    document.getElementById('assessment_selectedQuestionsWrapper').style.display = 'block';

    selectedQuestions.forEach((q, id) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="checkbox" class="assessment_loopedCheckbox" data-id="${id}"></td>
            <td>${q.title}</td>
            <td>${q.tags}</td>
            <td>${q.marks}</td>
            <td>${q.type}</td>
            <td><button class="btn btn-sm btn-danger" onclick="removeSelectedQuestion('${id}')">Delete</button></td>
        `;
        tbody.appendChild(row);
    });
}

function removeSelectedQuestion(id) {
    selectedQuestions.delete(id);
    renderSelectedQuestions();
}

    /*selected questions code end */
