// public/js/reports/user_progress_report.js
console.log('=== USER PROGRESS REPORT: JAVASCRIPT FILE LOADED ===');

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== USER PROGRESS REPORT: JAVASCRIPT LOADED AND DOM READY ==='); 
    
    // Initialize the report
    initializeReport();
    
    // Set up event listeners with a delay to ensure they're added after other systems
    setTimeout(function() {
        setupEventListeners();
        setupDirectEventListeners();
    }, 100);
    
    // Store original data for client-side filtering
    if (window.reportData && window.reportData.reportData) {
        window.reportData.originalData = [...window.reportData.reportData];
        console.log('Original data preserved for client-side filtering:', window.reportData.originalData.length, 'records');
    }
    
    // Initialize pagination variables
    window.reportData.currentPage = window.reportData.currentPage || 1;
    window.reportData.perPage = window.reportData.perPage || 20;
    
    // Initialize charts with data passed from server
    initializeCharts();
    
    // Initialize pagination controls if pagination data is available
    if (window.reportData && window.reportData.pagination) {
        updatePaginationControls(window.reportData.pagination);
    }
    
    // Set initial button state
    updateApplyButtonState();
    
    console.log('=== USER PROGRESS REPORT INITIALIZATION COMPLETE ===');
    
});

function initializeReport() {
    console.log('Initializing User Progress Report...');
    
    // Hide loading state since data is loaded from server
    const reportContent = document.getElementById('reportContent');
    if (reportContent) {
        reportContent.style.display = 'block';
    }
}

function initializeCharts() {
    console.log('Initializing charts with server data...');
    
    if (window.reportData) {
        // Update summary cards
        updateSummaryCards(window.reportData.summary);
        
        // Render charts
        renderCharts(window.reportData.charts);
        
        // Render data table
        renderDataTable(window.reportData.reportData);
        
        console.log('Charts initialized successfully');
    } else {
        console.error('No report data available');
        showError('No report data available');
    }
}

function showLoadingState() {
    const loadingHtml = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading report data...</p>
        </div>
    `;
    
    const reportContent = document.getElementById('reportContent');
    if (reportContent) {
        reportContent.innerHTML = loadingHtml;
    }
}

// Filter options are already populated from server data

function populateFilters(data) {
    // Populate users dropdown
    const userSelect = document.getElementById('userFilter');
    if (userSelect && data.users) {
        userSelect.innerHTML = '<option value="">All Users</option>';
        data.users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.full_name + ' (' + user.email + ')';
            userSelect.appendChild(option);
        });
    }
    
    // Populate departments dropdown
    const deptSelect = document.getElementById('departmentFilter');
    if (deptSelect && data.departments) {
        deptSelect.innerHTML = '<option value="">All Departments</option>';
        data.departments.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept.department;
            option.textContent = dept.department;
            deptSelect.appendChild(option);
        });
    }
    
    // Populate courses dropdown
    const courseSelect = document.getElementById('courseFilter');
    if (courseSelect && data.courses) {
        courseSelect.innerHTML = '<option value="">All Courses</option>';
        data.courses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name;
            courseSelect.appendChild(option);
        });
    }
    
    console.log('Filters populated successfully');
}

function loadReportData() {
    console.log('Filtering report data...');
    
    if (!window.reportData) {
        console.error('No report data available');
        showError('No report data available');
        return;
    }
    
    // Reset pagination when applying new filters
    resetPagination();
    
    const filters = getCurrentFilters();
    console.log('Applied filters:', filters);
    console.log('Original data count:', window.reportData.reportData.length);
    
    // If any complex filtering is applied, use server-side filtering
    if (filters.custom_field_id || filters.user_ids || filters.course_ids || filters.start_date || filters.end_date) {
        console.log('Using server-side filtering for complex filters or date filtering');
        loadReportDataFromServer(filters);
        return;
    }
    
    // Use original data for client-side filtering, not the potentially filtered data
    const originalData = window.reportData.originalData || window.reportData.reportData;
    let filteredData = originalData.filter(row => {
        // Date range filter
        if (filters.start_date && row.last_accessed_at) {
            const rowDate = new Date(row.last_accessed_at);
            const startDate = new Date(filters.start_date);
            if (rowDate < startDate) return false;
        }
        
        if (filters.end_date && row.last_accessed_at) {
            const rowDate = new Date(row.last_accessed_at);
            const endDate = new Date(filters.end_date);
            if (rowDate > endDate) return false;
        }
        
        // User filter
        if (filters.user_ids && filters.user_ids.length > 0) {
            if (!filters.user_ids.includes(row.user_id.toString())) return false;
        }
        
        // Department filter
        if (filters.departments && filters.departments.length > 0) {
            if (!filters.departments.includes(row.department)) return false;
        }
        
        // Custom field filter - only apply if both values are set
        if (filters.custom_field_id && filters.custom_field_value) {
            // This case is handled by server-side filtering, so skip client-side filtering
            console.log('Custom field filtering requires server-side filtering - skipping client-side filter');
            return false;
        }
        
        // Course filter
        if (filters.course_ids && filters.course_ids.length > 0) {
            if (!filters.course_ids.includes(row.course_id.toString())) return false;
        }
        
        // Status filter
        if (filters.status && filters.status.length > 0) {
            if (!filters.status.includes(row.progress_status)) return false;
        }
        
        return true;
    });
    
    console.log('Filtered data count:', filteredData.length);
    console.log('Filtered data sample:', filteredData.slice(0, 3));
    
    // Calculate filtered summary
    const filteredSummary = calculateFilteredSummary(filteredData);
    
    // Calculate filtered charts
    const filteredCharts = calculateFilteredCharts(filteredData);
    
    // Render the filtered data
    const filteredReportData = {
        success: true,
        data: filteredData,
        summary: filteredSummary,
        charts: filteredCharts
    };
    
    console.log('Filtered data:', filteredReportData);
    renderReport(filteredReportData);
}

function calculateFilteredSummary(data) {
    const summary = {
        total_progress_records: data.length,
        unique_users: new Set(data.map(row => row.user_id)).size,
        unique_courses: new Set(data.map(row => row.course_id)).size,
        avg_completion: 0,
        completed_courses: 0,
        in_progress_courses: 0,
        not_started_courses: 0
    };
    
    if (data.length > 0) {
        const totalCompletion = data.reduce((sum, row) => sum + parseFloat(row.completion_percentage || 0), 0);
        summary.avg_completion = (totalCompletion / data.length).toFixed(2);
        
        data.forEach(row => {
            switch (row.progress_status) {
                case 'completed':
                    summary.completed_courses++;
                    break;
                case 'in_progress':
                    summary.in_progress_courses++;
                    break;
                case 'not_started':
                    summary.not_started_courses++;
                    break;
            }
        });
    }
    
    return summary;
}

function calculateFilteredCharts(data) {
    const charts = {
        completion_status: {
            completed: 0,
            in_progress: 0,
            not_started: 0
        },
        department_progress: {
            labels: [],
            data: []
        }
    };
    
    // Process completion status
    data.forEach(row => {
        switch (row.progress_status) {
            case 'completed':
                charts.completion_status.completed++;
                break;
            case 'in_progress':
                charts.completion_status.in_progress++;
                break;
            case 'not_started':
                charts.completion_status.not_started++;
                break;
        }
    });
    
    // Process department progress
    const deptStats = {};
    data.forEach(row => {
        const dept = row.department || 'No Department';
        if (!deptStats[dept]) {
            deptStats[dept] = { total: 0, sum: 0 };
        }
        deptStats[dept].total++;
        deptStats[dept].sum += parseFloat(row.completion_percentage || 0);
    });
    
    Object.keys(deptStats).forEach(dept => {
        charts.department_progress.labels.push(dept);
        charts.department_progress.data.push(
            Math.round((deptStats[dept].sum / deptStats[dept].total) * 10) / 10
        );
    });
    
    return charts;
}

function getCurrentFilters() {
    const filters = {};
    
    // Get form values
    const startDate = document.getElementById('startDate')?.value;
    const endDate = document.getElementById('endDate')?.value;
    const customFieldValue = document.getElementById('customFieldValueSelect')?.value;
    
    // Get custom field ID from dropdown selection
    let customFieldId = null;
    const customFieldText = document.getElementById('customFieldText');
    if (customFieldText && customFieldText.textContent !== 'Select Custom Field') {
        // Find the custom field option that matches the selected text
        const customFieldOptions = document.querySelectorAll('.custom-field-option label');
        customFieldOptions.forEach(option => {
            const optionText = option.textContent.trim();
            if (optionText === customFieldText.textContent) {
                customFieldId = option.getAttribute('data-field-id');
            }
        });
    }
    
    // Get multi-select checkbox values
    const selectedUserIds = Array.from(document.querySelectorAll('.user-filter-checkbox:checked')).map(cb => cb.value);
    const selectedCourseIds = Array.from(document.querySelectorAll('.course-filter-checkbox:checked')).map(cb => cb.value);
    const selectedCustomFieldValues = Array.from(document.querySelectorAll('.custom-field-value-checkbox:checked')).map(cb => cb.value);
    // Get status filter values from checkboxes
    const selectedStatuses = Array.from(document.querySelectorAll('input[name="status[]"]:checked')).map(cb => cb.value);
    
    // Check if "All" options are selected
    const userFilterAll = document.getElementById('userFilterAll');
    const courseFilterAll = document.getElementById('courseFilterAll');
    const customFieldValueAll = document.getElementById('customFieldValueAll');
    
    const hasAllUsers = userFilterAll && userFilterAll.checked;
    const hasAllCourses = courseFilterAll && courseFilterAll.checked;
    const hasAllCustomFieldValues = customFieldValueAll && customFieldValueAll.checked;
    
    // Add non-empty filters
    if (startDate) filters.start_date = startDate;
    if (endDate) filters.end_date = endDate;
    if (selectedStatuses.length > 0) filters.status = selectedStatuses;
    
    // Only add user filter if specific users are selected (not "All")
    if (selectedUserIds.length > 0 && !hasAllUsers) {
        filters.user_ids = selectedUserIds;
    }
    
    // Only add course filter if specific courses are selected (not "All")
    if (selectedCourseIds.length > 0 && !hasAllCourses) {
        filters.course_ids = selectedCourseIds;
    }
    
    // Only add custom field filter if specific values are selected (not "All")
    if (customFieldId && selectedCustomFieldValues.length > 0 && !hasAllCustomFieldValues) {
        filters.custom_field_id = customFieldId;
        filters.custom_field_value = selectedCustomFieldValues;
    } else if (customFieldId && customFieldValue && !hasAllCustomFieldValues) {
        // Fallback to single select for backward compatibility
        filters.custom_field_id = customFieldId;
        filters.custom_field_value = customFieldValue;
    }
    
    // Add status filters
    const statusFilters = [];
    const notStarted = document.getElementById('statusNotStarted')?.checked;
    const inProgress = document.getElementById('statusInProgress')?.checked;
    const completed = document.getElementById('statusCompleted')?.checked;
    
    if (notStarted) statusFilters.push('not_started');
    if (inProgress) statusFilters.push('in_progress');
    if (completed) statusFilters.push('completed');
    
    if (statusFilters.length > 0) {
        filters.status = statusFilters;
    }
    
    return filters;
}

function renderReport(data) {
    console.log('Rendering report with data:', data);
    
    // Update summary cards
    updateSummaryCards(data.summary);
    
    // Render charts
    renderCharts(data.charts);
    
    // Render data table
    renderDataTable(data.data);
    
    // Hide loading state
    const reportContent = document.getElementById('reportContent');
    if (reportContent) {
        reportContent.style.display = 'block';
    }
}

function updateSummaryCards(summary) {
    // Update not started courses
    const notStartedCoursesEl = document.getElementById('notStartedCourses');
    if (notStartedCoursesEl) {
        notStartedCoursesEl.textContent = summary.not_started_courses || 0;
    }
    
    // Update in progress courses
    const inProgressCoursesEl = document.getElementById('inProgressCourses');
    if (inProgressCoursesEl) {
        inProgressCoursesEl.textContent = summary.in_progress_courses || 0;
    }
    
    // Update completed courses
    const completedCoursesEl = document.getElementById('completedCourses');
    if (completedCoursesEl) {
        completedCoursesEl.textContent = summary.completed_courses || 0;
    }
    
    // Update average completion
    const avgCompletionEl = document.getElementById('avgCompletion');
    if (avgCompletionEl) {
        avgCompletionEl.textContent = (summary.avg_completion || 0) + '%';
    }
}

function renderCharts(charts) {
    // Render completion status chart
    renderCompletionStatusChart(charts.completion_status);
    
    // Render department progress chart
    renderDepartmentProgressChart(charts.department_progress);
}

function renderCompletionStatusChart(data) {
    const ctx = document.getElementById('completionStatusChart');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (window.completionStatusChart && typeof window.completionStatusChart.destroy === 'function') {
        window.completionStatusChart.destroy();
    }
    
    window.completionStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'In Progress', 'Not Started'],
            datasets: [{
                data: [data.completed || 0, data.in_progress || 0, data.not_started || 0],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function renderDepartmentProgressChart(data) {
    const ctx = document.getElementById('departmentProgressChart');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (window.departmentProgressChart && typeof window.departmentProgressChart.destroy === 'function') {
        window.departmentProgressChart.destroy();
    }
    
    window.departmentProgressChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Average Completion %',
                data: data.data || [],
                backgroundColor: '#6a0dad',
                borderColor: '#5a0a9d',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function renderDataTable(data) {
    const tbody = document.querySelector('#progressTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No data available</td></tr>';
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${row.user_name || 'N/A'}</td>
            <td>${row.user_email || 'N/A'}</td>
            <td>${row.course_name || 'N/A'}</td>
            <td>${row.completion_percentage || 0}%</td>
            <td><span class="badge bg-${getStatusColor(row.progress_status)}">${formatStatusText(row.progress_status)}</span></td>
            <td>${row.last_accessed_at ? new Date(row.last_accessed_at).toLocaleDateString() : 'N/A'}</td>
            <td>${formatTimeSpent(row.total_time_spent || 0)}</td>
        `;
        tbody.appendChild(tr);
    });
}

function getStatusColor(status) {
    switch (status) {
        case 'completed': return 'success';
        case 'in_progress': return 'warning';
        case 'not_started': return 'secondary';
        default: return 'secondary';
    }
}

function formatStatusText(status) {
    switch (status) {
        case 'completed': return 'Completed';
        case 'in_progress': return 'In Progress';
        case 'not_started': return 'Not Started';
        default: return 'N/A';
    }
}

function formatTimeSpent(seconds) {
    if (!seconds || seconds === 0) return '0m';
    
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    } else {
        return `${minutes}m`;
    }
}

function setupEventListeners() {
    console.log('=== SETTING UP EVENT LISTENERS ===');
    
    // Prevent form submission
    const form = document.getElementById('reportFilters');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });
        
        // Also prevent any button from submitting the form
        form.addEventListener('click', function(e) {
            if (e.target.type === 'submit') {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    }
    
    // Apply filters button
    const applyBtn = document.getElementById('applyFilters');
    if (applyBtn) {
        applyBtn.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent any default behavior
            console.log('Apply filters clicked - preventing form submission');
            console.log('Button disabled state:', applyBtn.disabled);
            console.log('Button classes:', applyBtn.className);
            loadReportData();
        });
    } else {
        console.error('Apply filters button not found');
    }
    
    // Clear filters button
    const clearBtn = document.getElementById('clearFilters');
    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            console.log('Clear filters button clicked');
            clearFilters();
            // Reset to original data that was loaded on page load
            resetToOriginalData();
        });
    }
    
        // Custom field selection change (now using dropdown options)
        setupCustomFieldDropdown();
    
    // Multi-select checkbox functionality
    setupMultiSelectFilters();
    
    // Setup search functionality
    setupSearchFilters();
    
    // Setup date range and status filter listeners
    setupDateAndStatusListeners();
    
    // Custom field value selection change (for dropdown)
    const customFieldValueSelect = document.getElementById('customFieldValueSelect');
    if (customFieldValueSelect) {
        customFieldValueSelect.addEventListener('change', function(e) {
            // Just log the selection, wait for user to click Apply Filters button
            if (e.target.value) {
                console.log('Custom field value selected:', e.target.value, '- waiting for Apply Filters button');
                // Add visual indicator that filters are ready
                updateApplyButtonState();
            } else {
                console.log('Custom field value cleared - waiting for Apply Filters button');
                updateApplyButtonState();
            }
        });
    }
    
    // Export buttons
    const exportPdfBtn = document.getElementById('exportPdf');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportReport('pdf');
        });
    }
    
    const exportExcelBtn = document.getElementById('exportExcel');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportReport('excel');
        });
    }
}

function setupDirectEventListeners() {
    // Use event delegation to catch clicks on the clear filters button
    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'clearFilters') {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            clearFilters();
            resetToOriginalData();
        }
    }, true); // Use capture phase to catch before other listeners
}

function clearFilters() {
    console.log('Clearing filters...');
    
    // Clear date inputs
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    if (startDate) startDate.value = '';
    if (endDate) endDate.value = '';
    
    // Clear status filter checkboxes
    const statusCheckboxes = document.querySelectorAll('input[name="status[]"]');
    statusCheckboxes.forEach(checkbox => checkbox.checked = false);
    
    // Reset dropdowns and checkboxes
    const customFieldValueSelect = document.getElementById('customFieldValueSelect');
    
    // Clear custom field selection
    const customFieldText = document.getElementById('customFieldText');
    if (customFieldText) {
        customFieldText.textContent = 'Select Custom Field';
    }
    handleCustomFieldChange('');
    
    // Clear user filter checkboxes
    const userFilterAll = document.getElementById('userFilterAll');
    const userFilterCheckboxes = document.querySelectorAll('.user-filter-checkbox');
    if (userFilterAll) userFilterAll.checked = false;
    userFilterCheckboxes.forEach(checkbox => checkbox.checked = false);
    updateUserFilterText();
    
    // Clear course filter checkboxes
    const courseFilterAll = document.getElementById('courseFilterAll');
    const courseFilterCheckboxes = document.querySelectorAll('.course-filter-checkbox');
    if (courseFilterAll) courseFilterAll.checked = false;
    courseFilterCheckboxes.forEach(checkbox => checkbox.checked = false);
    updateCourseFilterText();
    
    // Clear custom field checkboxes
    const customFieldValueAll = document.getElementById('customFieldValueAll');
    const customFieldValueCheckboxes = document.querySelectorAll('.custom-field-value-checkbox');
    if (customFieldValueAll) customFieldValueAll.checked = false;
    customFieldValueCheckboxes.forEach(checkbox => checkbox.checked = false);
    updateCustomFieldValueText();
    
    // Reset custom field dropdowns
    if (customFieldSelect) customFieldSelect.value = '';
    if (customFieldValueSelect) {
        customFieldValueSelect.value = '';
        customFieldValueSelect.disabled = true;
        customFieldValueSelect.innerHTML = '<option value="">Select Field Value</option>';
    }
    
    // Disable custom field value dropdown
    const customFieldValueDropdown = document.getElementById('customFieldValueDropdown');
    if (customFieldValueDropdown) {
        customFieldValueDropdown.disabled = true;
        customFieldValueDropdown.textContent = 'Select Field Value';
    }
    
    // Uncheck all status checkboxes
    const statusNotStarted = document.getElementById('statusNotStarted');
    const statusInProgress = document.getElementById('statusInProgress');
    const statusCompleted = document.getElementById('statusCompleted');
    if (statusNotStarted) statusNotStarted.checked = false;
    if (statusInProgress) statusInProgress.checked = false;
    if (statusCompleted) statusCompleted.checked = false;
    
    // Update button state after clearing
    updateApplyButtonState();
    
    console.log('All filters cleared');
}

function setupCustomFieldDropdown() {
    // Setup dropdown item click listeners
    const customFieldOptions = document.querySelectorAll('.custom-field-option label');
    customFieldOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const fieldId = this.getAttribute('data-field-id');
            const fieldLabel = this.textContent.trim();
            
            // Update the dropdown button text
            const customFieldText = document.getElementById('customFieldText');
            if (customFieldText) {
                customFieldText.textContent = fieldLabel;
            }
            
            // Handle the custom field change
            handleCustomFieldChange(fieldId);
            
            // Close the dropdown
            const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('customFieldDropdown'));
            if (dropdown) {
                dropdown.hide();
            }
        });
    });
}

function handleCustomFieldChange(fieldId) {
    const customFieldValueSelect = document.getElementById('customFieldValueSelect');
    const customFieldValueDropdown = document.getElementById('customFieldValueDropdown');
    const customFieldValueOptions = document.getElementById('customFieldValueOptions');
    
    // Clear previous options
    if (customFieldValueSelect) {
        customFieldValueSelect.innerHTML = '<option value="">Select Field Value</option>';
    }
    
    if (customFieldValueOptions) {
        customFieldValueOptions.innerHTML = '';
    }
    
    if (!fieldId) {
        if (customFieldValueSelect) customFieldValueSelect.disabled = true;
        if (customFieldValueDropdown) {
            customFieldValueDropdown.disabled = true;
            customFieldValueDropdown.textContent = 'Select Field Value';
        }
        return;
    }
    
    // Find the selected custom field
    const customField = window.reportData.customFields.find(field => field.id == fieldId);
    if (!customField) {
        if (customFieldValueSelect) customFieldValueSelect.disabled = true;
        if (customFieldValueDropdown) {
            customFieldValueDropdown.disabled = true;
            customFieldValueDropdown.textContent = 'Select Field Value';
        }
        return;
    }
    
    // Populate options based on field type
    if (customField.field_type === 'select' || customField.field_type === 'radio' || customField.field_type === 'checkbox') {
        if (customField.field_options && customField.field_options.length > 0) {
            // Enable dropdowns
            if (customFieldValueSelect) customFieldValueSelect.disabled = false;
            if (customFieldValueDropdown) {
                customFieldValueDropdown.disabled = false;
                const searchInput = document.getElementById('customFieldValueSearchInput');
                if (searchInput) searchInput.disabled = false;
            }
            
            // Add options to both dropdown and checkbox list
            customField.field_options.forEach(option => {
                // Add to select dropdown (for backward compatibility)
                if (customFieldValueSelect) {
                    const optionElement = document.createElement('option');
                    optionElement.value = option;
                    optionElement.textContent = option;
                    customFieldValueSelect.appendChild(optionElement);
                }
                
                // Add to checkbox list
                if (customFieldValueOptions) {
                    const div = document.createElement('div');
                    div.className = 'custom-field-value-option';
                    div.setAttribute('data-search', option.toLowerCase());
                    div.innerHTML = `
                        <div class="form-check py-1">
                            <input class="form-check-input custom-field-value-checkbox" type="checkbox" id="custom_value_${option.replace(/\s+/g, '_')}" name="custom_field_values[]" value="${option}">
                            <label class="form-check-label" for="custom_value_${option.replace(/\s+/g, '_')}">
                                ${option}
                            </label>
                        </div>
                    `;
                    customFieldValueOptions.appendChild(div);
                }
            });
            
            // Setup event listeners for the new checkboxes
            setupCustomFieldValueCheckboxes();
            
            // Update the dropdown button text to show current selection
            updateCustomFieldValueText();
        } else {
            if (customFieldValueSelect) customFieldValueSelect.disabled = true;
            if (customFieldValueDropdown) {
                customFieldValueDropdown.disabled = true;
                customFieldValueDropdown.textContent = 'Select Field Value';
                const searchInput = document.getElementById('customFieldValueSearchInput');
                if (searchInput) searchInput.disabled = true;
            }
        }
    } else {
        if (customFieldValueSelect) customFieldValueSelect.disabled = true;
        if (customFieldValueDropdown) {
            customFieldValueDropdown.disabled = true;
            customFieldValueDropdown.textContent = 'Select Field Value';
            const searchInput = document.getElementById('customFieldValueSearchInput');
            if (searchInput) searchInput.disabled = true;
        }
    }
}

function loadReportDataFromServer(filters) {
    console.log('Loading report data from server with filters:', filters);
    
    // Add visual indicator for custom field filtering
    const applyBtn = document.getElementById('applyFilters');
    if (applyBtn && filters.custom_field_id && filters.custom_field_value) {
        applyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
        applyBtn.disabled = true;
    }
    
    // Show loading state
    showLoading();
    
    // Prepare form data
    const formData = new FormData();
    formData.append('action', 'get_report_data');
    
    // Add all filters to form data
    Object.keys(filters).forEach(key => {
        if (Array.isArray(filters[key])) {
            filters[key].forEach(value => {
                formData.append(key + '[]', value);
            });
        } else {
            formData.append(key, filters[key]);
        }
    });
    
    // Add pagination parameters
    const currentPage = window.reportData.currentPage || 1;
    const perPage = window.reportData.perPage || 20;
    formData.append('page', currentPage);
    formData.append('per_page', perPage);
    
    // Debug: Log form data contents
    console.log('FormData contents:');
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    // Make AJAX request
    fetch('/Unlockyourskills/reports/user-progress', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text(); // Get as text first to see what we're getting
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            
            if (data.success) {
                // Update the report data with server response
                window.reportData.reportData = data.reportData;
                window.reportData.summary = data.summary;
                window.reportData.charts = data.charts;
                window.reportData.pagination = data.pagination;
                
                // Preserve original data for client-side filtering
                if (!window.reportData.originalData) {
                    window.reportData.originalData = [...data.reportData];
                }
                
                // Debug logging
                console.log('DEBUG: Received data from server:', data);
                console.log('DEBUG: Summary stats:', data.summary);
                console.log('DEBUG: Charts data:', data.charts);
                console.log('DEBUG: Report data count:', data.reportData ? data.reportData.length : 0);
                
                // Render the filtered data
                updateSummaryCards(data.summary);
                renderCharts(data.charts);
                renderDataTable(data.reportData);
                updatePaginationControls(data.pagination);
                
                console.log('Server-side filtering completed successfully');
            } else {
                console.error('Server-side filtering failed:', data.message);
                showError('Failed to load filtered data: ' + data.message);
            }
        } catch (e) {
            console.error('Failed to parse JSON response:', e);
            console.error('Raw response text:', text);
            if (text.includes('login') || text.includes('Login')) {
                showError('Session expired. Please refresh the page and try again.');
            } else if (text.includes('<br />') || text.includes('<b>')) {
                showError('Server error occurred. Please check the console for details.');
            } else {
                showError('Invalid response from server');
            }
        }
    })
    .catch(error => {
        console.error('Error loading data from server:', error);
        showError('Error loading data from server: ' + error.message);
    })
    .finally(() => {
        hideLoading();
        
        // Restore button state
        const applyBtn = document.getElementById('applyFilters');
        if (applyBtn) {
            applyBtn.innerHTML = '<i class="fas fa-filter"></i> Apply Filters';
            applyBtn.disabled = false;
        }
    });
}

// Setup multi-select checkbox functionality
function setupMultiSelectFilters() {
    // User filter checkboxes
    setupUserFilterCheckboxes();
    
    // Course filter checkboxes
    setupCourseFilterCheckboxes();
    
    // Custom field value checkboxes (will be set up when custom field is selected)
    setupCustomFieldValueCheckboxes();
}

// Setup search functionality for all filter dropdowns
function setupSearchFilters() {
    // User search
    setupSearchFilter('userSearchInput', 'user-option', 'userFilterOptions');
    
    // Course search
    setupSearchFilter('courseSearchInput', 'course-option', 'courseFilterOptions');
    
    // Custom field search
    setupSearchFilter('customFieldSearchInput', 'custom-field-option', 'customFieldOptions');
    
    // Custom field value search
    setupSearchFilter('customFieldValueSearchInput', 'custom-field-value-option', 'customFieldValueOptions');
}

// Generic search filter setup
function setupSearchFilter(searchInputId, optionClass, containerId) {
    const searchInput = document.getElementById(searchInputId);
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const options = container.querySelectorAll(`.${optionClass}`);
        let visibleCount = 0;
        
        options.forEach(option => {
            const searchData = option.getAttribute('data-search') || '';
            const isVisible = searchData.includes(searchTerm);
            
            if (isVisible) {
                option.style.display = 'block';
                option.classList.remove('hidden');
                visibleCount++;
            } else {
                option.style.display = 'none';
                option.classList.add('hidden');
            }
        });
        
        // Show/hide no results message
        let noResultsMsg = container.querySelector('.no-results');
        if (visibleCount === 0 && searchTerm.length > 0) {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('li');
                noResultsMsg.className = 'no-results';
                noResultsMsg.textContent = 'No results found';
                container.appendChild(noResultsMsg);
            }
            noResultsMsg.style.display = 'block';
        } else if (noResultsMsg) {
            noResultsMsg.style.display = 'none';
        }
    });
    
    // Clear search when dropdown closes
    const dropdown = searchInput.closest('.dropdown');
    if (dropdown) {
        const dropdownToggle = dropdown.querySelector('[data-bs-toggle="dropdown"]');
        if (dropdownToggle) {
            dropdownToggle.addEventListener('hidden.bs.dropdown', function() {
                searchInput.value = '';
                const container = document.getElementById(containerId);
                if (container) {
                    const options = container.querySelectorAll(`.${optionClass}`);
                    options.forEach(option => {
                        option.style.display = 'block';
                        option.classList.remove('hidden');
                    });
                    
                    const noResultsMsg = container.querySelector('.no-results');
                    if (noResultsMsg) {
                        noResultsMsg.style.display = 'none';
                    }
                }
            });
        }
    }
}

// Setup user filter checkboxes
function setupUserFilterCheckboxes() {
    const userFilterCheckboxes = document.querySelectorAll('.user-filter-checkbox');
    const userFilterAll = document.getElementById('userFilterAll');
    const userFilterText = document.getElementById('userFilterText');
    
    // Handle "All Users" checkbox
    if (userFilterAll) {
        userFilterAll.addEventListener('change', function() {
            if (this.checked) {
                // Uncheck all individual user checkboxes
                userFilterCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                userFilterText.textContent = 'All Users';
            }
            updateApplyButtonState();
        });
    }
    
    // Handle individual user checkboxes
    userFilterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Uncheck "All Users" if individual user is selected
                if (userFilterAll) {
                    userFilterAll.checked = false;
                }
            }
            updateUserFilterText();
            updateApplyButtonState();
        });
    });
}

// Setup course filter checkboxes
function setupCourseFilterCheckboxes() {
    const courseFilterCheckboxes = document.querySelectorAll('.course-filter-checkbox');
    const courseFilterAll = document.getElementById('courseFilterAll');
    const courseFilterText = document.getElementById('courseFilterText');
    
    // Handle "All Courses" checkbox
    if (courseFilterAll) {
        courseFilterAll.addEventListener('change', function() {
            if (this.checked) {
                // Uncheck all individual course checkboxes
                courseFilterCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                courseFilterText.textContent = 'All Courses';
            }
            updateApplyButtonState();
        });
    }
    
    // Handle individual course checkboxes
    courseFilterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                // Uncheck "All Courses" if individual course is selected
                if (courseFilterAll) {
                    courseFilterAll.checked = false;
                }
            }
            updateCourseFilterText();
            updateApplyButtonState();
        });
    });
}

// Setup custom field value checkboxes
function setupCustomFieldValueCheckboxes() {
    // This will be called when custom field values are populated
    const customFieldValueAll = document.getElementById('customFieldValueAll');
    const customFieldValueText = document.getElementById('customFieldValueText');
    
    if (customFieldValueAll) {
        // Remove existing event listeners to avoid duplicates
        customFieldValueAll.removeEventListener('change', handleCustomFieldValueAllChange);
        customFieldValueAll.addEventListener('change', handleCustomFieldValueAllChange);
    }
    
    // Setup individual checkbox listeners
    const valueCheckboxes = document.querySelectorAll('.custom-field-value-checkbox');
    valueCheckboxes.forEach(checkbox => {
        // Remove existing event listeners to avoid duplicates
        checkbox.removeEventListener('change', handleCustomFieldValueChange);
        checkbox.addEventListener('change', handleCustomFieldValueChange);
    });
}

// Handle "All Values" checkbox change
function handleCustomFieldValueAllChange() {
    if (this.checked) {
        // Uncheck all individual value checkboxes
        const valueCheckboxes = document.querySelectorAll('.custom-field-value-checkbox');
        valueCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
    }
    
    // Update the dropdown button text to show current selection
    updateCustomFieldValueText();
    updateApplyButtonState();
}

// Handle individual custom field value checkbox change
function handleCustomFieldValueChange() {
    const customFieldValueAll = document.getElementById('customFieldValueAll');
    const customFieldValueText = document.getElementById('customFieldValueText');
    
    if (this.checked) {
        // Uncheck "All Values" if individual value is selected
        if (customFieldValueAll) {
            customFieldValueAll.checked = false;
        }
    }
    
    // Update the dropdown button text to show current selection
    updateCustomFieldValueText();
    updateApplyButtonState();
}

// Update user filter text based on selected checkboxes
function updateUserFilterText() {
    const userFilterCheckboxes = document.querySelectorAll('.user-filter-checkbox:checked');
    const userFilterText = document.getElementById('userFilterText');
    
    if (!userFilterText) {
        console.error('userFilterText element not found!');
        return;
    }
    
    if (userFilterCheckboxes.length === 0) {
        userFilterText.textContent = 'All Users';
    } else if (userFilterCheckboxes.length === 1) {
        const label = userFilterCheckboxes[0].nextElementSibling.textContent;
        userFilterText.textContent = label;
    } else {
        userFilterText.textContent = `${userFilterCheckboxes.length} Users Selected`;
    }
}

// Update course filter text based on selected checkboxes
function updateCourseFilterText() {
    const courseFilterCheckboxes = document.querySelectorAll('.course-filter-checkbox:checked');
    const courseFilterText = document.getElementById('courseFilterText');
    
    if (!courseFilterText) {
        console.error('courseFilterText element not found!');
        return;
    }
    
    if (courseFilterCheckboxes.length === 0) {
        courseFilterText.textContent = 'All Courses';
    } else if (courseFilterCheckboxes.length === 1) {
        const label = courseFilterCheckboxes[0].nextElementSibling.textContent;
        courseFilterText.textContent = label;
    } else {
        courseFilterText.textContent = `${courseFilterCheckboxes.length} Courses Selected`;
    }
}

// Update custom field value text based on selected checkboxes
function updateCustomFieldValueText() {
    const valueCheckboxes = document.querySelectorAll('.custom-field-value-checkbox:checked');
    const customFieldValueText = document.getElementById('customFieldValueText');
    
    if (!customFieldValueText) {
        console.error('customFieldValueText element not found');
        return;
    }
    
    updateCustomFieldValueTextContent(customFieldValueText, valueCheckboxes);
}

// Helper function to update the text content
function updateCustomFieldValueTextContent(customFieldValueText, valueCheckboxes) {
    if (valueCheckboxes.length === 0) {
        customFieldValueText.textContent = 'All Values';
    } else if (valueCheckboxes.length === 1) {
        const label = valueCheckboxes[0].nextElementSibling.textContent;
        customFieldValueText.textContent = label;
    } else {
        customFieldValueText.textContent = `${valueCheckboxes.length} Values Selected`;
    }
}

// Reset to original data that was loaded on page load
function resetToOriginalData() {
    console.log('Resetting to original data...');
    
    if (!window.reportData || !window.reportData.originalData) {
        console.error('No original data available, reloading page...');
        window.location.reload();
        return;
    }
    
    // Restore original data
    window.reportData.reportData = [...window.reportData.originalData];
    
    // Recalculate summary and charts from original data
    const originalSummary = calculateFilteredSummary(window.reportData.originalData);
    const originalCharts = calculateFilteredCharts(window.reportData.originalData);
    
    // Update the data
    window.reportData.summary = originalSummary;
    window.reportData.charts = originalCharts;
    
    // Render the original data
    updateSummaryCards(originalSummary);
    renderCharts(originalCharts);
    renderDataTable(window.reportData.originalData);
    
    console.log('Reset to original data successfully:', window.reportData.originalData.length, 'records');
}

// Update Apply Filters button state based on current filters
function updateApplyButtonState() {
    const applyBtn = document.getElementById('applyFilters');
    if (!applyBtn) return;
    
    // Check if any "All" options are selected
    const userFilterAll = document.getElementById('userFilterAll');
    const courseFilterAll = document.getElementById('courseFilterAll');
    const customFieldValueAll = document.getElementById('customFieldValueAll');
    
    const hasAllUsers = userFilterAll && userFilterAll.checked;
    const hasAllCourses = courseFilterAll && courseFilterAll.checked;
    const hasAllCustomFieldValues = customFieldValueAll && customFieldValueAll.checked;
    
    // Check if any specific filters are selected
    const filters = getCurrentFilters();
    const hasCustomFieldFilter = filters.custom_field_id && filters.custom_field_value;
    const hasOtherFilters = filters.user_ids || filters.course_ids || filters.start_date || filters.end_date || filters.status;
    
    // Enable button if any filters are applied OR if "All" options are selected
    if (hasCustomFieldFilter || hasOtherFilters || hasAllUsers || hasAllCourses || hasAllCustomFieldValues) {
        // Enable button and show it's ready to apply
        applyBtn.disabled = false;
        applyBtn.innerHTML = '<i class="fas fa-filter"></i> Apply Filters';
        applyBtn.classList.remove('btn-secondary');
        applyBtn.classList.add('btn-primary');
    } else {
        // Disable button only if no filters and no "All" options are selected
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fas fa-filter"></i> Apply Filters';
        applyBtn.classList.remove('btn-primary');
        applyBtn.classList.add('btn-secondary');
    }
}

// Test function that can be called from browser console
window.testClearFilters = function() {
    clearFilters();
    resetToOriginalData();
};

// Test function to check button state
window.testApplyButton = function() {
    const applyBtn = document.getElementById('applyFilters');
    if (applyBtn) {
        console.log('Apply button found:');
        console.log('- Disabled:', applyBtn.disabled);
        console.log('- Classes:', applyBtn.className);
        console.log('- InnerHTML:', applyBtn.innerHTML);
        console.log('- Clickable:', !applyBtn.disabled && applyBtn.offsetParent !== null);
        
        // Test click
        applyBtn.click();
    } else {
        console.error('Apply button not found');
    }
};

// Setup date range and status filter event listeners
function setupDateAndStatusListeners() {
    // Date range listeners
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    
    if (startDate) {
        startDate.addEventListener('change', updateApplyButtonState);
    }
    
    if (endDate) {
        endDate.addEventListener('change', updateApplyButtonState);
    }
    
    // Status checkbox listeners
    const statusCheckboxes = document.querySelectorAll('input[name="status[]"]');
    statusCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateApplyButtonState);
    });
}

// Format time spent function (removed - no longer needed)

function exportReport(format) {
    console.log('Exporting report as:', format);
    
    // Show loading indicator
    const btn = format === 'pdf' ? document.getElementById('exportPdf') : document.getElementById('exportExcel');
    if (btn) {
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Generating...';
        
        // Restore button after 3 seconds
        setTimeout(() => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }, 3000);
    }
    
    const filters = getCurrentFilters();
    
    // Build form data for POST request
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/Unlockyourskills/api/reports/export-user-progress.php';
    form.target = '_blank';
    
    // Add format
    const formatInput = document.createElement('input');
    formatInput.type = 'hidden';
    formatInput.name = 'format';
    formatInput.value = format;
    form.appendChild(formatInput);
    
    // Add filters
    Object.keys(filters).forEach(key => {
        if (Array.isArray(filters[key])) {
            filters[key].forEach(value => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key + '[]';
                input.value = value;
                form.appendChild(input);
            });
        } else {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filters[key];
            form.appendChild(input);
        }
    });
    
    // Add current report data (for charts)
    if (window.reportData) {
        const summaryInput = document.createElement('input');
        summaryInput.type = 'hidden';
        summaryInput.name = 'summary';
        summaryInput.value = JSON.stringify(window.reportData.summary);
        form.appendChild(summaryInput);
        
        const chartsInput = document.createElement('input');
        chartsInput.type = 'hidden';
        chartsInput.name = 'charts';
        chartsInput.value = JSON.stringify(window.reportData.charts);
        form.appendChild(chartsInput);
    }
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function showLoading() {
    const reportContent = document.getElementById('reportContent');
    if (reportContent) {
        reportContent.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading report data...</p>
            </div>
        `;
    }
}

function hideLoading() {
    const reportContent = document.getElementById('reportContent');
    if (reportContent) {
        reportContent.style.display = 'block';
    }
}

function showError(message) {
    const errorHtml = `
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
    
    const reportContent = document.getElementById('reportContent');
    if (reportContent) {
        reportContent.innerHTML = errorHtml;
    }
}

// Pagination Functions
function updatePaginationControls(pagination) {
    const paginationControls = document.getElementById('paginationControls');
    const paginationInfo = document.getElementById('paginationInfo');
    
    if (!paginationControls || !paginationInfo) {
        return;
    }
    
    // Update pagination info
    const start = (pagination.current_page - 1) * pagination.per_page + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    paginationInfo.textContent = `Showing ${start} to ${end} of ${pagination.total} entries`;
    
    // Clear existing pagination controls
    paginationControls.innerHTML = '';
    
    // Don't show pagination if there's only one page or no data
    if (pagination.total_pages <= 1) {
        return;
    }
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${!pagination.has_prev ? 'disabled' : ''}`;
    prevLi.innerHTML = `
        <a class="page-link" href="#" data-page="${pagination.current_page - 1}" ${!pagination.has_prev ? 'tabindex="-1" aria-disabled="true"' : ''}>
            <span aria-hidden="true">&laquo;</span>
        </a>
    `;
    paginationControls.appendChild(prevLi);
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    // First page if not in range
    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        firstLi.innerHTML = `<a class="page-link" href="#" data-page="1">1</a>`;
        paginationControls.appendChild(firstLi);
        
        if (startPage > 2) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
            paginationControls.appendChild(ellipsisLi);
        }
    }
    
    // Page numbers in range
    for (let i = startPage; i <= endPage; i++) {
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${i === pagination.current_page ? 'active' : ''}`;
        pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        paginationControls.appendChild(pageLi);
    }
    
    // Last page if not in range
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            const ellipsisLi = document.createElement('li');
            ellipsisLi.className = 'page-item disabled';
            ellipsisLi.innerHTML = `<span class="page-link">...</span>`;
            paginationControls.appendChild(ellipsisLi);
        }
        
        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        lastLi.innerHTML = `<a class="page-link" href="#" data-page="${pagination.total_pages}">${pagination.total_pages}</a>`;
        paginationControls.appendChild(lastLi);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${!pagination.has_next ? 'disabled' : ''}`;
    nextLi.innerHTML = `
        <a class="page-link" href="#" data-page="${pagination.current_page + 1}" ${!pagination.has_next ? 'tabindex="-1" aria-disabled="true"' : ''}>
            <span aria-hidden="true">&raquo;</span>
        </a>
    `;
    paginationControls.appendChild(nextLi);
    
    // Add click event listeners to pagination links
    paginationControls.addEventListener('click', function(e) {
        e.preventDefault();
        
        const target = e.target.closest('a[data-page]');
        if (target && !target.closest('.disabled')) {
            const page = parseInt(target.getAttribute('data-page'));
            if (page && page !== window.reportData.currentPage) {
                goToPage(page);
            }
        }
    });
}

function goToPage(page) {
    window.reportData.currentPage = page;
    
    // Get current filters and reload data
    const filters = getCurrentFilters();
    loadReportDataFromServer(filters);
}

function resetPagination() {
    window.reportData.currentPage = 1;
}
