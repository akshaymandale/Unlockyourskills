// public/js/reports/course_completion_report.js
// JavaScript file loaded successfully
// Simple direct function for button click - make it global and define immediately
window.applyFiltersDirect = function(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Call the main applyFilters function
    if (typeof window.applyFilters === 'function') {
        window.applyFilters(event);
    } else {
        }
    
    return false;
};

document.addEventListener('DOMContentLoaded', function() {
    // Test if JavaScript is working
    
    if (window.reportData) {
        }
    
    // Initialize the report
    initializeReport();
    
    // Set up event listeners
    setTimeout(function() {
        setupEventListeners();
        setupDirectEventListeners();
    }, 100);
    
    // Store original data for client-side filtering
    if (window.reportData && window.reportData.reportData) {
        window.reportData.originalData = [...window.reportData.reportData];
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
    
    });

function initializeReport() {
    // Update summary cards
    updateSummaryCards(window.reportData.summary);
    
    // Populate table
    populateTable(window.reportData.reportData);
}

function setupEventListeners() {
    // No form submission prevention needed since we're using div instead of form
    const reportFiltersDiv = document.getElementById('reportFilters');
    if (reportFiltersDiv) {
        }
    
    // Apply Filters button - using direct onclick approach instead
    const applyFiltersBtn = document.getElementById('applyFilters');
    if (applyFiltersBtn) {
        // Use data attribute approach instead of onclick
        applyFiltersBtn.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            const action = this.getAttribute('data-action');
            if (action === 'apply-filters') {
                applyFiltersDirect(event);
            }
            
            return false;
        });
    } else {
        }
    
    // Clear Filters button
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearFilters);
        }
    
    // Export buttons
    const exportPdfBtn = document.getElementById('exportPdf');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', exportPdf);
    }
    
    const exportExcelBtn = document.getElementById('exportExcel');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', exportExcel);
    }
    
    // Course filter search
    const courseSearchInput = document.getElementById('courseSearchInput');
    if (courseSearchInput) {
        courseSearchInput.addEventListener('input', function(e) {
            filterDropdownOptions('course', e.target.value);
        });
        courseSearchInput.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Custom field search
    const customFieldSearchInput = document.getElementById('customFieldSearchInput');
    if (customFieldSearchInput) {
        customFieldSearchInput.addEventListener('input', function(e) {
            filterDropdownOptions('customField', e.target.value);
        });
        customFieldSearchInput.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Custom field value search
    const customFieldValueSearchInput = document.getElementById('customFieldValueSearchInput');
    if (customFieldValueSearchInput) {
        customFieldValueSearchInput.addEventListener('input', function(e) {
            filterDropdownOptions('customFieldValue', e.target.value);
        });
        customFieldValueSearchInput.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // "All" checkboxes
    const courseFilterAll = document.getElementById('courseFilterAll');
    if (courseFilterAll) {
        courseFilterAll.addEventListener('change', function() {
            toggleAllCheckboxes('course', this.checked);
        });
    }
    
    const customFieldValueAll = document.getElementById('customFieldValueAll');
    if (customFieldValueAll) {
        customFieldValueAll.addEventListener('change', function() {
            toggleAllCheckboxes('customFieldValue', this.checked);
        });
    }
    
    // Custom field selection
    const customFieldOptions = document.querySelectorAll('.custom-field-option label');
    customFieldOptions.forEach(label => {
        label.addEventListener('click', function(e) {
            e.preventDefault();
            const fieldId = this.getAttribute('data-field-id');
            selectCustomField(fieldId, this.textContent.trim());
        });
    });
    
    // Custom field value checkboxes (will be added dynamically)
    // We'll set up a delegate event listener for dynamically created checkboxes
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('custom-field-value-checkbox')) {
            updateDropdownButtonText();
        }
    });
}

function setupDirectEventListeners() {
    // Set up individual checkbox listeners
    const courseCheckboxes = document.querySelectorAll('.course-filter-checkbox');
    courseCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateDropdownButtonText);
    });
}

window.applyFilters = function(event) {
    // Prevent any default behavior
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        }
    
    // Store current filter values before AJAX call
    const currentFilters = {
        start_date: document.getElementById('startDate')?.value || '',
        end_date: document.getElementById('endDate')?.value || '',
        course_ids: getSelectedValues('course'),
        status: getSelectedValues('status'),
        custom_field_id: window.reportData.selectedCustomFieldId || '',
        custom_field_value: getSelectedValues('customFieldValue')
    };
    
    // Collect filter values
    const filters = {
        action: 'get_report_data',
        start_date: currentFilters.start_date,
        end_date: currentFilters.end_date,
        course_ids: currentFilters.course_ids,
        status: currentFilters.status,
        custom_field_id: currentFilters.custom_field_id,
        custom_field_value: currentFilters.custom_field_value,
        page: 1,
        per_page: window.reportData.perPage || 20
    };
    
    // Send AJAX request
    fetch('/Unlockyourskills/reports/course-completion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(filters)
    })
    .then(response => {
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update report data
            window.reportData.reportData = data.data;
            window.reportData.pagination = data.pagination;
            window.reportData.summary = data.summary;
            window.reportData.charts = data.charts;
            window.reportData.currentPage = 1;
            
            // Update UI
            updateSummaryCards(data.summary);
            populateTable(data.data);
            updatePaginationControls(data.pagination);
            updateCharts(data.charts);
            
            // Restore filter values after a short delay to ensure DOM is updated
            setTimeout(() => {
                restoreFilterValues(currentFilters);
            }, 100);
        } else {
            }
    })
    .catch(error => {
        });
    
    return false;
}

function clearFilters() {
    // Clear date inputs
    document.getElementById('startDate').value = '';
    document.getElementById('endDate').value = '';
    
    // Uncheck all checkboxes
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    
    // Reset custom field selection
    window.reportData.selectedCustomFieldId = null;
    document.getElementById('customFieldText').textContent = window.translations?.select_custom_field || 'Select Custom Field';
    document.getElementById('customFieldValueDropdown').disabled = true;
    document.getElementById('customFieldValueText').textContent = window.translations?.select_field_value || 'Select Field Value';
    document.getElementById('customFieldValueOptions').innerHTML = '';
    
    // Reset dropdown button texts
    document.getElementById('courseFilterText').textContent = window.translations?.all_courses || 'All Courses';
    
    // Reload initial data
    applyFilters();
}

function getSelectedValues(filterType) {
    const values = [];
    
    if (filterType === 'course') {
        document.querySelectorAll('.course-filter-checkbox:checked').forEach(cb => {
            values.push(cb.value);
        });
    } else if (filterType === 'status') {
        document.querySelectorAll('input[name="status[]"]:checked').forEach(cb => {
            values.push(cb.value);
        });
    } else if (filterType === 'customFieldValue') {
        document.querySelectorAll('.custom-field-value-checkbox:checked').forEach(cb => {
            values.push(cb.value);
        });
        }
    
    return values;
}

function updateSummaryCards(summary) {
    const totalCoursesEl = document.getElementById('totalCourses');
    const totalEnrollmentsEl = document.getElementById('totalEnrollments');
    const overallCompletionRateEl = document.getElementById('overallCompletionRate');
    const avgCompletionPercentageEl = document.getElementById('avgCompletionPercentage');
    
    if (totalCoursesEl) {
        totalCoursesEl.textContent = summary.total_courses || 0;
        }
    
    if (totalEnrollmentsEl) {
        totalEnrollmentsEl.textContent = summary.total_enrollments || 0;
        }
    
    if (overallCompletionRateEl) {
        overallCompletionRateEl.textContent = (summary.overall_completion_rate || 0) + '%';
        }
    
    if (avgCompletionPercentageEl) {
        avgCompletionPercentageEl.textContent = (summary.avg_completion_percentage || 0) + '%';
        }
    
    }

function populateTable(data) {
    const tbody = document.getElementById('completionTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!data || data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No data available</td></tr>';
        return;
    }
    
    data.forEach(row => {
        const tr = document.createElement('tr');
        
        // Use the not_started_count calculated by the server
        const applicableUsers = row.total_applicable_users || 0;
        const enrolledUsers = row.total_enrollments || 0;
        const notStarted = row.not_started_count || 0;
        
        tr.innerHTML = `
            <td>${escapeHtml(row.course_name)}</td>
            <td><span class="badge bg-info">${applicableUsers}</span></td>
            <td>${enrolledUsers}</td>
            <td><span class="badge bg-success">${row.completed_count || 0}</span></td>
            <td><span class="badge bg-warning">${row.in_progress_count || 0}</span></td>
            <td><span class="badge bg-secondary">${notStarted}</span></td>
            <td>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar ${getProgressBarClass(row.completion_rate)}" 
                         role="progressbar" 
                         style="width: ${row.completion_rate || 0}%"
                         aria-valuenow="${row.completion_rate || 0}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${row.completion_rate || 0}%
                    </div>
                </div>
            </td>
            <td>${row.avg_completion || 0}%</td>
            <td>${row.last_activity ? formatDate(row.last_activity) : 'N/A'}</td>
        `;
        
        tbody.appendChild(tr);
    });
    
    // Update pagination info
    if (window.reportData.pagination) {
        const { per_page, current_page, total } = window.reportData.pagination;
        const start = Math.min((current_page - 1) * per_page + 1, total);
        const end = Math.min(current_page * per_page, total);
        
        const paginationInfo = document.getElementById('paginationInfo');
        if (paginationInfo) {
            paginationInfo.textContent = `Showing ${start} to ${end} of ${total} entries`;
        }
    }
}

function getProgressBarClass(percentage) {
    if (percentage >= 80) return 'bg-success';
    if (percentage >= 50) return 'bg-info';
    if (percentage >= 30) return 'bg-warning';
    return 'bg-danger';
}

function initializeCharts() {
    if (!window.reportData || !window.reportData.charts) {
        console.warn('No chart data available');
        return;
    }
    
    initializeChartsWithData(window.reportData.charts);
}

function initializeChartsWithData(charts) {
    // Enrollment Status Pie Chart
    const enrollmentStatusCtx = document.getElementById('enrollmentStatusChart');
    if (enrollmentStatusCtx) {
        window.enrollmentStatusChart = new Chart(enrollmentStatusCtx, {
            type: 'pie',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started'],
                datasets: [{
                    data: [
                        charts.enrollment_status.completed || 0,
                        charts.enrollment_status.in_progress || 0,
                        charts.enrollment_status.not_started || 0
                    ],
                    backgroundColor: [
                        'rgba(25, 135, 84, 0.8)',
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(108, 117, 125, 0.8)'
                    ],
                    borderColor: [
                        'rgba(25, 135, 84, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(108, 117, 125, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Course Completion Rate Bar Chart
    const completionRateCtx = document.getElementById('completionRateChart');
    if (completionRateCtx) {
        window.completionRateChart = new Chart(completionRateCtx, {
            type: 'bar',
            data: {
                labels: charts.completion_rate.labels || [],
                datasets: [{
                    label: 'Completion Rate (%)',
                    data: charts.completion_rate.data || [],
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                }
            }
        });
    }
}

function updateCharts(charts) {
    // Store chart instances globally to update them instead of reloading
    if (window.enrollmentStatusChart) {
        window.enrollmentStatusChart.destroy();
    }
    if (window.completionRateChart) {
        window.completionRateChart.destroy();
    }
    
    // Reinitialize charts with new data
    initializeChartsWithData(charts);
}

function updatePaginationControls(pagination) {
    const paginationControls = document.getElementById('paginationControls');
    if (!paginationControls) return;
    
    paginationControls.innerHTML = '';
    
    const { current_page, total_pages, has_prev, has_next } = pagination;
    
    // Previous button
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${!has_prev ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current_page - 1}">Previous</a>`;
    if (has_prev) {
        prevLi.querySelector('a').addEventListener('click', function(e) {
            e.preventDefault();
            changePage(current_page - 1);
        });
    }
    paginationControls.appendChild(prevLi);
    
    // Page numbers
    const maxPagesToShow = 5;
    let startPage = Math.max(1, current_page - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(total_pages, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const pageLi = document.createElement('li');
        pageLi.className = `page-item ${i === current_page ? 'active' : ''}`;
        pageLi.innerHTML = `<a class="page-link" href="#" data-page="${i}">${i}</a>`;
        pageLi.querySelector('a').addEventListener('click', function(e) {
            e.preventDefault();
            changePage(i);
        });
        paginationControls.appendChild(pageLi);
    }
    
    // Next button
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${!has_next ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#" data-page="${current_page + 1}">Next</a>`;
    if (has_next) {
        nextLi.querySelector('a').addEventListener('click', function(e) {
            e.preventDefault();
            changePage(current_page + 1);
        });
    }
    paginationControls.appendChild(nextLi);
}

function changePage(page) {
    // Collect current filters
    const filters = {
        action: 'get_report_data',
        start_date: document.getElementById('startDate')?.value || '',
        end_date: document.getElementById('endDate')?.value || '',
        course_ids: getSelectedValues('course'),
        status: getSelectedValues('status'),
        custom_field_id: window.reportData.selectedCustomFieldId || '',
        custom_field_value: getSelectedValues('customFieldValue'),
        page: page,
        per_page: window.reportData.perPage || 20
    };
    
    // Send AJAX request
    fetch('/Unlockyourskills/reports/course-completion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(filters)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.reportData.reportData = data.data;
            window.reportData.pagination = data.pagination;
            window.reportData.currentPage = page;
            
            populateTable(data.data);
            updatePaginationControls(data.pagination);
            
            // Scroll to top of table
            document.getElementById('completionTable').scrollIntoView({ behavior: 'smooth' });
        }
    })
    .catch(error => {
        });
}

function filterDropdownOptions(type, searchTerm) {
    const term = searchTerm.toLowerCase();
    let options;
    
    if (type === 'course') {
        options = document.querySelectorAll('.course-option');
    } else if (type === 'customField') {
        options = document.querySelectorAll('.custom-field-option');
    } else if (type === 'customFieldValue') {
        options = document.querySelectorAll('.custom-field-value-option');
    }
    
    if (!options) return;
    
    options.forEach(option => {
        const searchText = option.getAttribute('data-search');
        if (searchText && searchText.includes(term)) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
}

function toggleAllCheckboxes(type, checked) {
    let checkboxes;
    
    if (type === 'course') {
        checkboxes = document.querySelectorAll('.course-filter-checkbox');
    } else if (type === 'customFieldValue') {
        checkboxes = document.querySelectorAll('.custom-field-value-checkbox');
    }
    
    if (!checkboxes) return;
    
    checkboxes.forEach(cb => cb.checked = checked);
    updateDropdownButtonText();
}

function updateDropdownButtonText() {
    // Update course filter button text
    const courseCheckboxes = document.querySelectorAll('.course-filter-checkbox:checked');
    const courseFilterText = document.getElementById('courseFilterText');
    if (courseFilterText) {
        if (courseCheckboxes.length === 0) {
            courseFilterText.textContent = window.translations?.all_courses || 'All Courses';
        } else if (courseCheckboxes.length === 1) {
            courseFilterText.textContent = courseCheckboxes[0].nextElementSibling.textContent.trim();
        } else {
            courseFilterText.textContent = `${courseCheckboxes.length} courses selected`;
        }
    }
    
    // Update custom field value dropdown button text
    const customFieldValueCheckboxes = document.querySelectorAll('.custom-field-value-checkbox:checked');
    const customFieldValueText = document.getElementById('customFieldValueText');
    if (customFieldValueText) {
        if (customFieldValueCheckboxes.length === 0) {
            customFieldValueText.textContent = window.translations?.select_field_value || 'Select Field Value';
        } else if (customFieldValueCheckboxes.length === 1) {
            customFieldValueText.textContent = customFieldValueCheckboxes[0].nextElementSibling.textContent.trim();
        } else {
            customFieldValueText.textContent = `${customFieldValueCheckboxes.length} values selected`;
        }
    }
}

function selectCustomField(fieldId, fieldLabel) {
    window.reportData.selectedCustomFieldId = fieldId;
    document.getElementById('customFieldText').textContent = fieldLabel;
    
    // Enable custom field value dropdown
    document.getElementById('customFieldValueDropdown').disabled = false;
    document.getElementById('customFieldValueSearchInput').disabled = false;
    
    // Clear any existing custom field value selections
    document.getElementById('customFieldValueText').textContent = window.translations?.select_field_value || 'Select Field Value';
    
    // Fetch custom field values
    fetch('/Unlockyourskills/reports/course-completion', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_custom_field_values',
            field_id: fieldId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateCustomFieldValues(data.values);
        } else {
            }
    })
    .catch(error => {
        });
}

function populateCustomFieldValues(values) {
    const container = document.getElementById('customFieldValueOptions');
    if (!container) return;
    
    container.innerHTML = '';
    
    values.forEach(value => {
        const div = document.createElement('div');
        div.className = 'custom-field-value-option';
        div.setAttribute('data-search', value.toLowerCase());
        
        div.innerHTML = `
            <div class="form-check py-1">
                <input class="form-check-input custom-field-value-checkbox" type="checkbox" 
                       id="cfv_${value.replace(/\s+/g, '_')}" value="${escapeHtml(value)}">
                <label class="form-check-label" for="cfv_${value.replace(/\s+/g, '_')}">
                    ${escapeHtml(value)}
                </label>
            </div>
        `;
        
        container.appendChild(div);
    });
    
    // Update dropdown button text after populating values
    updateDropdownButtonText();
}

function exportPdf() {
    // Collect current filters
    const filters = {
        format: 'pdf',
        start_date: document.getElementById('startDate')?.value || '',
        end_date: document.getElementById('endDate')?.value || '',
        course_ids: getSelectedValues('course').join(','),
        status: getSelectedValues('status').join(','),
        custom_field_id: window.reportData.selectedCustomFieldId || '',
        custom_field_value: getSelectedValues('customFieldValue').join(',')
    };
    
    // Build URL with query parameters
    const params = new URLSearchParams(filters);
    window.open(`/Unlockyourskills/api/reports/export-course-completion.php?${params.toString()}`, '_blank');
}

function exportExcel() {
    // Collect current filters
    const filters = {
        format: 'excel',
        start_date: document.getElementById('startDate')?.value || '',
        end_date: document.getElementById('endDate')?.value || '',
        course_ids: getSelectedValues('course').join(','),
        status: getSelectedValues('status').join(','),
        custom_field_id: window.reportData.selectedCustomFieldId || '',
        custom_field_value: getSelectedValues('customFieldValue').join(',')
    };
    
    // Build URL with query parameters
    const params = new URLSearchParams(filters);
    window.open(`/Unlockyourskills/api/reports/export-course-completion.php?${params.toString()}`, '_blank');
}

// Utility functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Restore filter values after successful filtering
 */
function restoreFilterValues(storedFilters) {
    if (!storedFilters) {
        return;
    }
    
    // Restore date values
    if (storedFilters.start_date) {
        const startDateEl = document.getElementById('startDate');
        if (startDateEl) {
            startDateEl.value = storedFilters.start_date;
            } else {
            }
    }
    if (storedFilters.end_date) {
        const endDateEl = document.getElementById('endDate');
        if (endDateEl) {
            endDateEl.value = storedFilters.end_date;
            } else {
            }
    }
    
    // Restore course filter selections
    if (storedFilters.course_ids && storedFilters.course_ids.length > 0) {
        storedFilters.course_ids.forEach(courseId => {
            const checkbox = document.getElementById('course_' + courseId);
            if (checkbox) {
                checkbox.checked = true;
                } else {
                }
        });
    }
    
    // Restore status filter selections
    if (storedFilters.status && storedFilters.status.length > 0) {
        storedFilters.status.forEach(status => {
            let checkboxId;
            switch(status) {
                case 'not_started':
                    checkboxId = 'statusNotStarted';
                    break;
                case 'in_progress':
                    checkboxId = 'statusInProgress';
                    break;
                case 'completed':
                    checkboxId = 'statusCompleted';
                    break;
            }
            if (checkboxId) {
                const checkbox = document.getElementById(checkboxId);
                if (checkbox) {
                    checkbox.checked = true;
                    } else {
                    }
            }
        });
    }
    
    // Restore custom field selection
    if (storedFilters.custom_field_id) {
        // Find the custom field label by field ID
        const customFieldLabels = document.querySelectorAll('.custom-field-option label');
        customFieldLabels.forEach(label => {
            const fieldId = label.getAttribute('data-field-id');
            if (fieldId === storedFilters.custom_field_id) {
                const fieldLabel = label.textContent.trim();
                document.getElementById('customFieldText').textContent = fieldLabel;
                window.reportData.selectedCustomFieldId = storedFilters.custom_field_id;
                
                // Enable custom field value dropdown
                document.getElementById('customFieldValueDropdown').disabled = false;
                document.getElementById('customFieldValueSearchInput').disabled = false;
                
                }
        });
    }
    
    // Restore custom field value selections
    if (storedFilters.custom_field_value && storedFilters.custom_field_value.length > 0) {
        storedFilters.custom_field_value.forEach(value => {
            const checkboxId = 'cfv_' + value.replace(/\s+/g, '_');
            const checkbox = document.getElementById(checkboxId);
            if (checkbox) {
                checkbox.checked = true;
                } else {
                }
        });
        
        // Update custom field value dropdown button text
        const customFieldValueCheckboxes = document.querySelectorAll('.custom-field-value-checkbox:checked');
        const customFieldValueText = document.getElementById('customFieldValueText');
        if (customFieldValueText) {
            if (customFieldValueCheckboxes.length === 0) {
                customFieldValueText.textContent = window.translations?.select_field_value || 'Select Field Value';
            } else if (customFieldValueCheckboxes.length === 1) {
                customFieldValueText.textContent = customFieldValueCheckboxes[0].nextElementSibling.textContent.trim();
            } else {
                customFieldValueText.textContent = `${customFieldValueCheckboxes.length} values selected`;
            }
        }
    }
    
    // Update dropdown button texts
    updateDropdownButtonText();
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

