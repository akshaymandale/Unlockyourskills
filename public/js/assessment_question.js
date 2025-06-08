document.addEventListener('DOMContentLoaded', function () {
    // ✅ Assessment Question Search and Filter Functionality
    // Global variables to track current state
    let currentPage = 1;
    let currentSearch = '';
    let currentFilters = {
        question_type: '',
        difficulty: '',
        tags: ''
    };

    // Debounce function for search input
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', performSearch);
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
        
        // Add debounced search on input
        const debouncedSearch = debounce(performSearch, 500);
        searchInput.addEventListener('input', debouncedSearch);
    }

    // Filter functionality
    const questionTypeFilter = document.getElementById('questionTypeFilter');
    const difficultyFilter = document.getElementById('difficultyFilter');
    
    if (questionTypeFilter) {
        questionTypeFilter.addEventListener('change', applyFilters);
    }
    
    if (difficultyFilter) {
        difficultyFilter.addEventListener('change', applyFilters);
    }

    // Tags filter with debounced input
    const tagsFilter = document.getElementById('tagsFilter');
    if (tagsFilter) {
        const debouncedTagsFilter = debounce(applyFilters, 500);
        tagsFilter.addEventListener('input', debouncedTagsFilter);
        tagsFilter.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    }

    // Clear filters functionality
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }

    // Pagination functionality
    document.addEventListener('click', function(e) {
        if (e.target.matches('.page-link[data-page]')) {
            e.preventDefault();
            const page = parseInt(e.target.getAttribute('data-page'));
            loadQuestions(page);
        }
    });

    // ✅ Load initial questions on page load
    // Check if we're on the assessment questions page
    if (document.getElementById('questionsTableBody')) {
        // Load questions immediately when page loads
        loadQuestions(1);
    }

    function performSearch() {
        if (searchInput) {
            currentSearch = searchInput.value.trim();
            currentPage = 1; // Reset to first page
            loadQuestions();
        }
    }

    function applyFilters() {
        currentFilters.question_type = questionTypeFilter ? questionTypeFilter.value : '';
        currentFilters.difficulty = difficultyFilter ? difficultyFilter.value : '';
        currentFilters.tags = tagsFilter ? tagsFilter.value : '';
        currentPage = 1; // Reset to first page
        loadQuestions();
    }

    function clearAllFilters() {
        // Clear search
        if (searchInput) {
            searchInput.value = '';
            currentSearch = '';
        }

        // Clear filters
        if (questionTypeFilter) questionTypeFilter.value = '';
        if (difficultyFilter) difficultyFilter.value = '';
        if (tagsFilter) tagsFilter.value = '';
        currentFilters = {
            question_type: '',
            difficulty: '',
            tags: ''
        };

        currentPage = 1;
        loadQuestions();
    }

    function loadQuestions(page = currentPage) {
        currentPage = page;
        
        // Show loading indicator
        const loadingIndicator = document.getElementById('loadingIndicator');
        const questionsContainer = document.getElementById('questionsContainer');
        const paginationContainer = document.getElementById('paginationContainer');
        
        if (loadingIndicator) loadingIndicator.style.display = 'block';
        if (questionsContainer) questionsContainer.style.display = 'none';
        if (paginationContainer) paginationContainer.style.display = 'none';

        // Prepare data for AJAX request
        const formData = new FormData();
        formData.append('controller', 'QuestionController');
        formData.append('action', 'ajaxSearch');
        formData.append('page', currentPage);
        formData.append('search', currentSearch);
        formData.append('question_type', currentFilters.question_type);
        formData.append('difficulty', currentFilters.difficulty);
        formData.append('tags', currentFilters.tags);

        // Make AJAX request
        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateQuestionsTable(data.questions);
                updatePagination(data.pagination);
                updateSearchInfo(data.totalQuestions);
            } else {
                console.error('Error loading questions:', data.message);
                alert('Error loading questions: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            alert('Network error. Please try again.');
        })
        .finally(() => {
            // Hide loading indicator
            if (loadingIndicator) loadingIndicator.style.display = 'none';
            if (questionsContainer) questionsContainer.style.display = 'block';
            if (paginationContainer) paginationContainer.style.display = 'block';
        });
    }

    function updateQuestionsTable(questions) {
        const tbody = document.getElementById('questionsTableBody');
        if (!tbody) return;
        
        tbody.innerHTML = '';

        if (questions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="no-results-message">
                        <i class="fas fa-search"></i>
                        <div>
                            <h5>No questions found</h5>
                            <p>Try adjusting your search terms or filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        questions.forEach(question => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHtml(question.title)}</td>
                <td>${escapeHtml(question.type)}</td>
                <td>${escapeHtml(question.difficulty)}</td>
                <td>${escapeHtml(question.tags)}</td>
                <td>
                    <a href="index.php?controller=QuestionController&action=edit&id=${question.id}"
                        class="btn theme-btn-primary"
                        title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    <a href="index.php?controller=QuestionController&action=delete&id=${question.id}"
                        class="btn theme-btn-danger"
                        title="Delete"
                        onclick="return confirm('Are you sure you want to delete this question?');">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function updatePagination(pagination) {
        const container = document.getElementById('paginationContainer');
        if (!container) return;
        
        // Only show pagination if there are more than 10 total questions
        if (pagination.totalQuestions <= 10) {
            if (pagination.totalQuestions > 0) {
                // Show total count when no pagination needed
                const plural = pagination.totalQuestions !== 1 ? 's' : '';
                container.innerHTML = `
                    <div class="text-center text-muted small">
                        Showing all ${pagination.totalQuestions} question${plural}
                    </div>
                `;
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
            return;
        }

        container.style.display = 'block';
        
        // Create pagination navigation
        let paginationHTML = '<nav><ul class="pagination justify-content-center" id="paginationList">';
        
        // Previous button
        if (pagination.currentPage > 1) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.currentPage - 1}">« Previous</a>
                </li>
            `;
        }

        // Page numbers
        for (let i = 1; i <= pagination.totalPages; i++) {
            const activeClass = i === pagination.currentPage ? 'active' : '';
            paginationHTML += `
                <li class="page-item ${activeClass}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next button
        if (pagination.currentPage < pagination.totalPages) {
            paginationHTML += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.currentPage + 1}">Next »</a>
                </li>
            `;
        }
        
        paginationHTML += '</ul></nav>';
        container.innerHTML = paginationHTML;
    }

    function updateSearchInfo(totalQuestions) {
        const searchInfo = document.getElementById('searchResultsInfo');
        const resultsText = document.getElementById('resultsText');
        
        if (!searchInfo || !resultsText) return;

        if (currentSearch || currentFilters.question_type || currentFilters.difficulty || currentFilters.tags) {
            let infoText = `Showing ${totalQuestions} result(s)`;
            
            if (currentSearch) {
                infoText += ` for search: "<strong>${escapeHtml(currentSearch)}</strong>"`;
            }
            
            if (currentFilters.question_type || currentFilters.difficulty || currentFilters.tags) {
                infoText += ' with filters applied';
            }
            
            resultsText.innerHTML = infoText;
            searchInfo.style.display = 'block';
        } else {
            searchInfo.style.display = 'none';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
