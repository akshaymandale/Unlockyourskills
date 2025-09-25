// Enrollment Approval Page JS

document.addEventListener('DOMContentLoaded', function() {
    try {
        const enrollmentsList = document.getElementById('enrollmentsList');
        const pagination = document.getElementById('enrollmentsPagination');
        const refreshBtn = document.getElementById('refreshBtn');
        
        // Statistics cards (now clickable)
        const pendingCard = document.getElementById('pendingCard');
        const approvedCard = document.getElementById('approvedCard');
        const rejectedCard = document.getElementById('rejectedCard');
        const totalCard = document.getElementById('totalCard');
        
        // Statistics elements
        const pendingCount = document.getElementById('pendingCount');
        const approvedCount = document.getElementById('approvedCount');
        const rejectedCount = document.getElementById('rejectedCount');
        const totalCount = document.getElementById('totalCount');
        
        // Current filter badge
        const currentFilterBadge = document.getElementById('currentFilterBadge');
        
        // Modal elements
        const rejectionModal = document.getElementById('rejectionModal');
        const rejectionReason = document.getElementById('rejectionReason');
        const confirmReject = document.getElementById('confirmReject');

        let currentFilter = 'pending';
        let currentPage = 1;
        let perPage = 10;
        let currentEnrollmentId = null;

        // Initialize page
        loadStatistics();
        setFilter('pending'); // Set default filter to pending
        loadEnrollments();

        // Card click events
        pendingCard.addEventListener('click', () => setFilter('pending'));
        approvedCard.addEventListener('click', () => setFilter('approved'));
        rejectedCard.addEventListener('click', () => setFilter('rejected'));
        totalCard.addEventListener('click', () => setFilter('all'));

        // Refresh button
        refreshBtn.addEventListener('click', () => {
            loadStatistics();
            loadEnrollments();
        });

        // Confirm reject button
        confirmReject.addEventListener('click', () => {
            if (!currentEnrollmentId) return;
            
            const reason = rejectionReason.value.trim();
            if (!reason) {
                showMessage('Please provide a reason for rejection.', 'error');
                return;
            }

            updateEnrollmentStatus(currentEnrollmentId, 'rejected', reason);
            bootstrap.Modal.getInstance(rejectionModal).hide();
        });

        function setFilter(filter) {
            currentFilter = filter;
            currentPage = 1;
            
            // Update card states
            document.querySelectorAll('.clickable-card').forEach(card => {
                card.classList.remove('active');
            });
            
            if (filter === 'pending') {
                pendingCard.classList.add('active');
                if (currentFilterBadge) {
                    currentFilterBadge.textContent = 'Pending';
                    currentFilterBadge.className = 'badge bg-warning text-dark';
                }
            } else if (filter === 'approved') {
                approvedCard.classList.add('active');
                if (currentFilterBadge) {
                    currentFilterBadge.textContent = 'Approved';
                    currentFilterBadge.className = 'badge bg-success';
                }
            } else if (filter === 'rejected') {
                rejectedCard.classList.add('active');
                if (currentFilterBadge) {
                    currentFilterBadge.textContent = 'Rejected';
                    currentFilterBadge.className = 'badge bg-danger';
                }
            } else if (filter === 'all') {
                totalCard.classList.add('active');
                if (currentFilterBadge) {
                    currentFilterBadge.textContent = 'All';
                    currentFilterBadge.className = 'badge bg-primary';
                }
            }
            
            loadEnrollments();
        }

        async function loadStatistics() {
            try {
                const response = await fetch('/Unlockyourskills/enrollment-approval/stats');
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.stats;
                    pendingCount.textContent = stats.pending;
                    approvedCount.textContent = stats.approved;
                    rejectedCount.textContent = stats.rejected;
                    totalCount.textContent = stats.total;
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        async function loadEnrollments() {
            try {
                enrollmentsList.innerHTML = `
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2 text-muted">Loading enrollment requests...</p>
                    </div>
                `;

                const statusParam = currentFilter === 'all' ? '' : `&status=${currentFilter}`;
                const response = await fetch(`/Unlockyourskills/enrollment-approval/list?page=${currentPage}&per_page=${perPage}${statusParam}`);
                const data = await response.json();
                
                if (data.success) {
                    renderEnrollments(data.enrollments);
                    renderPagination(data.total, data.page, data.per_page);
                } else {
                    enrollmentsList.innerHTML = '<div class="text-center py-5 text-danger">Failed to load enrollments.</div>';
                }
            } catch (error) {
                console.error('Error loading enrollments:', error);
                enrollmentsList.innerHTML = '<div class="text-center py-5 text-danger">Failed to load enrollments.</div>';
            }
        }

        function renderEnrollments(enrollments) {
            if (!enrollments || enrollments.length === 0) {
                const filterText = currentFilter === 'all' ? 'enrollment' : currentFilter;
                enrollmentsList.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No ${filterText} enrollments found</h5>
                        <p class="text-muted">There are currently no ${filterText} enrollment requests.</p>
                    </div>
                `;
                return;
            }

            const tableHTML = `
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Course</th>
                            <th>Enrollment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${enrollments.map(renderEnrollmentRow).join('')}
                    </tbody>
                </table>
            `;
            
            enrollmentsList.innerHTML = tableHTML;
        }

        function renderEnrollmentRow(enrollment) {
            const username = enrollment.username || 'Unknown User';
            const enrollmentDate = new Date(enrollment.enrollment_date).toLocaleDateString();
            
            let statusBadge = '';
            if (enrollment.status === 'pending') {
                statusBadge = '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pending</span>';
            } else if (enrollment.status === 'approved') {
                statusBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Approved</span>';
            } else if (enrollment.status === 'rejected') {
                statusBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Rejected</span>';
            }

            let actionsHTML = '';
            if (enrollment.status === 'pending') {
                actionsHTML = `
                    <div class="d-flex gap-2">
                        <button class="btn theme-btn-primary btn-sm" onclick="updateEnrollmentStatus(${enrollment.id}, 'approved')" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn theme-btn-danger btn-sm" onclick="showRejectionModal(${enrollment.id})" title="Reject">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            } else {
                actionsHTML = '<span class="text-muted">No actions</span>';
            }

            return `
                <tr>
                    <td>
                        <div>
                            <div class="fw-bold">${username}</div>
                            <small class="text-muted">User ID: ${enrollment.user_id}</small>
                        </div>
                    </td>
                    <td>
                        <div>
                            <div class="fw-bold">${enrollment.course_name || 'Unknown Course'}</div>
                            <small class="text-muted">${enrollment.category_name || ''}${enrollment.subcategory_name ? ' / ' + enrollment.subcategory_name : ''}</small>
                        </div>
                    </td>
                    <td>
                        <span class="text-muted">${enrollmentDate}</span>
                    </td>
                    <td>${statusBadge}</td>
                    <td>${actionsHTML}</td>
                </tr>
            `;
        }

        function renderPagination(total, page, perPage) {
            const totalPages = Math.ceil(total / perPage);
            
            if (totalPages <= 1) {
                pagination.innerHTML = '';
                return;
            }

            let paginationHTML = '<nav><ul class="pagination justify-content-center mb-0">';
            
            // Previous button
            if (page > 1) {
                paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${page - 1})">Previous</a></li>`;
            }
            
            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                paginationHTML += `
                    <li class="page-item ${i === page ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                    </li>
                `;
            }
            
            // Next button
            if (page < totalPages) {
                paginationHTML += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${page + 1})">Next</a></li>`;
            }
            
            paginationHTML += '</ul></nav>';
            pagination.innerHTML = paginationHTML;
        }

        // Global functions for onclick handlers
        window.changePage = function(page) {
            currentPage = page;
            loadEnrollments();
        };

        window.updateEnrollmentStatus = async function(enrollmentId, status, reason = null) {
            try {
                const formData = new FormData();
                formData.append('enrollment_id', enrollmentId);
                formData.append('status', status);
                if (reason) {
                    formData.append('rejection_reason', reason);
                }

                const response = await fetch('/Unlockyourskills/enrollment-approval/update', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showMessage(result.message, 'success');
                    loadStatistics();
                    loadEnrollments();
                } else {
                    showMessage(result.message, 'error');
                }

            } catch (error) {
                console.error('Error updating enrollment status:', error);
                showMessage('An error occurred while updating enrollment status.', 'error');
            }
        };

        window.showRejectionModal = function(enrollmentId) {
            currentEnrollmentId = enrollmentId;
            rejectionReason.value = '';
            const modal = new bootstrap.Modal(rejectionModal);
            modal.show();
        };

        function showMessage(message, type) {
            // Create or update message element
            let messageElement = document.getElementById('approvalMessage');
            if (!messageElement) {
                messageElement = document.createElement('div');
                messageElement.id = 'approvalMessage';
                messageElement.className = 'alert alert-dismissible fade show position-fixed';
                messageElement.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                
                const container = document.getElementById('enrollmentApprovalPage');
                container.appendChild(messageElement);
            }

            // Set message content and styling
            messageElement.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            messageElement.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (messageElement && messageElement.parentNode) {
                    messageElement.remove();
                }
            }, 5000);
        }

    } catch (error) {
        console.error('Error initializing enrollment approval page:', error);
    }
});
