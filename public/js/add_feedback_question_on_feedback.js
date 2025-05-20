document.addEventListener("DOMContentLoaded", function () {
    const addQuestionBtn = document.getElementById("feedback_addFeedbackQuestionBtn");
    const questionModalEl = document.getElementById("feedback_questionModal");
    const parentModalEl = document.getElementById("feedback_feedbackModal");

    const questionTableBody = document.getElementById("feedback_questionTableBody");
    const loopSelectedBtn = document.getElementById("feedback_loopQuestionsBtn");
    const paginationContainer = document.getElementById("feedback_pagination");
    const selectedWrapper = document.getElementById("feedback_selectedQuestionsWrapper");
    const gridBody = document.getElementById("feedback_selectedQuestionsBody");

    const searchInput = document.getElementById("feedback_questionSearch");
    const filterType = document.getElementById("feedback_filterType");
    const showEntries = document.getElementById("feedback_showEntries");
    const clearFiltersBtn = document.getElementById("feedback_clearFiltersBtn");
    const refreshBtn = document.getElementById("feedback_refreshBtn");

    let questionModal;
    try {
        questionModal = new bootstrap.Modal(questionModalEl, { backdrop: 'static' });
    } catch (e) {}

    let questionsData = [];
    let persistentSelectedQuestions = new Set();
    let temporarySelections = new Set();
    let currentPage = 1;

    function normalizeId(id) {
        return String(id);
    }

    async function fetchQuestions(page = 1) {
        const params = new URLSearchParams({
            search: searchInput.value,
            type: filterType.value,
            limit: showEntries.value,
            page
        });

        currentPage = page;

        try {
            const response = await fetch(`index.php?controller=FeedbackQuestionController&action=getQuestions&${params.toString()}`);
            const data = await response.json();
            questionsData = data.questions || [];
            renderQuestionsTable();
            renderPagination(data.totalPages || 1);
        } catch (error) {
            console.error("Error fetching questions:", error);
        }
    }

    function renderQuestionsTable() {
        questionTableBody.innerHTML = "";
        questionsData.forEach(q => {
            const qid = normalizeId(q.id);
            const checked = temporarySelections.has(qid) ? "checked" : "";
            const row = `
                <tr>
                    <td><input type="checkbox" class="question-checkbox" value="${qid}" ${checked}></td>
                    <td>${q.title}</td>
                    <td>${q.tags}</td>
                    <td>${q.type}</td>
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
        try {
            const response = await fetch(`index.php?controller=FeedbackQuestionController&action=getFilterOptions`);
            const data = await response.json();
            filterType.innerHTML = `<option value="">All Types</option>`;
            (data.types || []).forEach(type => {
                filterType.insertAdjacentHTML("beforeend", `<option value="${type}">${type}</option>`);
            });
        } catch (error) {
            console.error("Error loading filter options:", error);
        }
    }

    loopSelectedBtn.addEventListener("click", async function () {
        if (temporarySelections.size === 0) {
            alert("Please select at least one question.");
            return;
        }

        try {
            const response = await fetch(`index.php?controller=FeedbackQuestionController&action=getSelectedQuestions`, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ ids: Array.from(temporarySelections) })
            });

            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
                console.log("Fetched selected questions:", data);
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
                        <td>${q.title}</td>
                        <td>${q.tags}</td>
                        <td>${q.type}</td>
                    </tr>
                `);
            });

            selectedWrapper.style.display = "block";
            persistentSelectedQuestions = new Set(temporarySelections);

            document.getElementById("feedback_selectedQuestionCount").value = temporarySelections.size;
            document.getElementById("feedback_selectedQuestionIds").value = Array.from(temporarySelections).join(',');

            questionModal.hide();
        } catch (error) {
            alert("Error looping questions.");
            console.error(error);
        }
    });

    addQuestionBtn.addEventListener("click", () => {
        temporarySelections = new Set(persistentSelectedQuestions);
        loadFilterOptions();
        fetchQuestions();
        questionModal.show();
    });

    questionTableBody.addEventListener("change", function (e) {
        if (e.target.classList.contains("question-checkbox")) {
            const id = normalizeId(e.target.value);
            if (e.target.checked) {
                temporarySelections.add(id);
            } else {
                temporarySelections.delete(id);
            }
        }
    });

    refreshBtn.addEventListener("click", () => fetchQuestions(currentPage));
    searchInput.addEventListener("input", () => fetchQuestions(1));
    filterType.addEventListener("change", () => fetchQuestions(1));
    showEntries.addEventListener("change", () => fetchQuestions(1));

    clearFiltersBtn.addEventListener("click", () => {
        searchInput.value = "";
        filterType.value = "";
        showEntries.value = "10";
        temporarySelections.clear();
        fetchQuestions(1);
    });

    questionModalEl.addEventListener("hidden.bs.modal", () => {
        temporarySelections = new Set(persistentSelectedQuestions);

        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 1) {
            backdrops[backdrops.length - 1].remove();
        }

        if (parentModalEl) {
            parentModalEl.classList.add("show");
            parentModalEl.style.display = "block";
            parentModalEl.removeAttribute("aria-hidden");
            parentModalEl.setAttribute("aria-modal", "true");

            setTimeout(() => {
                const focusable = parentModalEl.querySelector("button, [tabindex]:not([tabindex='-1'])");
                if (focusable) focusable.focus();
            }, 200);
        }
    });

    parentModalEl.addEventListener("hidden.bs.modal", () => {
        temporarySelections.clear();
        persistentSelectedQuestions.clear();
        gridBody.innerHTML = "";
        selectedWrapper.style.display = "none";
        document.getElementById("feedback_selectedQuestionCount").value = 0;
        document.getElementById("feedback_selectedQuestionIds").value = "";
    });
});
