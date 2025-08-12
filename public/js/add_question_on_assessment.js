document.addEventListener("DOMContentLoaded", function () {
    const addQuestionBtn = document.getElementById("assessment_addQuestionBtn");
    const questionModalEl = document.getElementById("assessment_questionModal");
    const parentModalEl = document.getElementById("assessment_assessmentModal");

    let questionModal;
    try {
        questionModal = new bootstrap.Modal(questionModalEl, { 
            backdrop: 'static',
            keyboard: false,
            focus: false
        });
    } catch (e) {
        console.error("Failed to create question modal:", e);
    }

    const questionTableBody = document.getElementById("assessment_questionTableBody");
    const loopSelectedBtn = document.getElementById("assessment_loopQuestionsBtn");
    const paginationContainer = document.getElementById("assessment_pagination");
    const selectedWrapper = document.getElementById("assessment_selectedQuestionsWrapper");
    const gridBody = document.getElementById("assessment_selectedQuestionsBody");

    let questionsData = [];
    let persistentSelectedQuestions = new Set(); // Persisted after loop
    let temporarySelections = new Set(); // Reset always
    let currentPage = 1;

    function normalizeId(id) {
        return String(id);
    }

    // Function to update the select all checkbox state
    function updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById("assessment_selectAllQuestions");
        if (!selectAllCheckbox) return;

        // If any question is selected, keep the header checkbox checked
        if (temporarySelections.size > 0) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }

    // Function to handle select all checkbox
    function handleSelectAll(checked) {
        const currentPageQuestionIds = questionsData.map(q => normalizeId(q.id));

        if (checked) {
            // Add all current page questions to selection
            currentPageQuestionIds.forEach(id => temporarySelections.add(id));
        } else {
            // Remove all current page questions from selection
            currentPageQuestionIds.forEach(id => temporarySelections.delete(id));
        }

        // Re-render the table to update checkboxes
        renderQuestionsTable();
    }

    // Function to reset all filters
    function resetFilters() {
        document.getElementById("assessment_questionSearch").value = "";
        document.getElementById("assessment_filterMarks").value = "";
        document.getElementById("assessment_filterType").value = "";
        document.getElementById("assessment_showEntries").value = "10";

        // Reset select all checkbox
        const selectAllCheckbox = document.getElementById("assessment_selectAllQuestions");
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }

        // Reset to first page
        currentPage = 1;
    }

    async function fetchQuestions(page = 1) {
        currentPage = page;
        const search = document.getElementById("assessment_questionSearch").value;
        const marks = document.getElementById("assessment_filterMarks").value;
        const type = document.getElementById("assessment_filterType").value;
        const limit = document.getElementById("assessment_showEntries").value;

        const params = new URLSearchParams({
            search: search,
            marks: marks,
            type: type,
            limit: limit,
            page: page
        });

        try {
            const response = await fetch(`/unlockyourskills/vlr/assessment-packages/questions?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await response.json();
            renderQuestionsTable(data.questions);
            renderPagination(data.totalPages);
        } catch (error) {
            console.error("Error fetching questions:", error);
            questionTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading questions</td></tr>';
        }
    }

    function renderQuestionsTable(questions) {
        if (!questionTableBody) {
            console.error("questionTableBody element not found!");
            return;
        }

        questionTableBody.innerHTML = "";

        if (questions.length === 0) {
            questionTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No questions found</td></tr>';
            updateSelectAllCheckbox();
            return;
        }

        questions.forEach((q) => {
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

        // Update the select all checkbox state after rendering
        updateSelectAllCheckbox();
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
        const response = await fetch(`/unlockyourskills/vlr/assessment-packages/filter-options`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
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
            const response = await fetch(`/unlockyourskills/vlr/assessment-packages/selected-questions`, {
                method: "POST",
                headers: { 
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                },
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
            persistentSelectedQuestions = new Set(temporarySelections);

            document.getElementById("assessment_selectedQuestionCount").value = temporarySelections.size;

            // ✅ Set the hidden input with selected question IDs
            document.getElementById("assessment_selectedQuestionIds").value = Array.from(temporarySelections).join(',');

            // Clear any validation error for selected questions
            if (typeof window.hideSelectedQuestionsError === 'function') {
                window.hideSelectedQuestionsError();
            }

            questionModal.hide();

        } catch (error) {
            alert("Error looping questions.");
            console.error(error);
        }
    });

    // Fix: Prevent infinite recursion by ensuring only one modal is open
    if (addQuestionBtn && questionModal && parentModalEl) {
        addQuestionBtn.addEventListener('click', function() {
            // Don't hide parent modal - just show question modal with different configuration
            questionModal.show();
            
            // Reset filters when opening modal
            resetFilters();

            // Load existing selected questions from the assessment form (for edit mode)
            const existingSelectedIds = document.getElementById("assessment_selectedQuestionIds").value;
            if (existingSelectedIds && existingSelectedIds.trim() !== "") {
                const selectedIds = existingSelectedIds.split(',').map(id => normalizeId(id.trim())).filter(id => id);
                persistentSelectedQuestions = new Set(selectedIds);
                console.log("Loaded existing selected questions for edit:", selectedIds);
            }

            // Load persistent selections (previously looped questions + existing assessment questions)
            temporarySelections = new Set(persistentSelectedQuestions);
            
            // Reset filters and load data after modal is shown
            loadFilterOptions();
            fetchQuestions(1);
        });

        // Remove the hidden.bs.modal event listener that was re-showing parent modal
    }

    // Handle individual question checkbox changes
    questionTableBody.addEventListener("change", function (e) {
        if (e.target.classList.contains("question-checkbox")) {
            const id = normalizeId(e.target.value);
            if (e.target.checked) {
                temporarySelections.add(id);
            } else {
                temporarySelections.delete(id);
            }
            // Update select all checkbox state
            updateSelectAllCheckbox();
        }
    });

    // Handle select all checkbox
    document.getElementById("assessment_selectAllQuestions").addEventListener("change", function (e) {
        handleSelectAll(e.target.checked);
    });

    document.getElementById("assessment_refreshBtn").addEventListener("click", () => fetchQuestions(currentPage));
    document.getElementById("assessment_questionSearch").addEventListener("input", () => fetchQuestions(1));
    document.getElementById("assessment_filterMarks").addEventListener("change", () => fetchQuestions(1));
    document.getElementById("assessment_filterType").addEventListener("change", () => fetchQuestions(1));
    document.getElementById("assessment_showEntries").addEventListener("change", () => fetchQuestions(1));

    document.getElementById("assessment_clearFiltersBtn").addEventListener("click", () => {
        resetFilters();
        temporarySelections.clear();
        fetchQuestions(1);
    });

    questionModalEl.addEventListener("hidden.bs.modal", () => {
        // Reset filters when modal is closed
        resetFilters();

        // Reset temporary selections to persistent ones (discard unsaved changes)
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
        document.getElementById("assessment_selectedQuestionCount").value = 0;

        // ✅ Reset hidden input when modal is closed
        document.getElementById("assessment_selectedQuestionIds").value = "";
    });
});
