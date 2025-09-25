<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 enrollment-approval" id="enrollmentApprovalPage">
        <h1 class="page-title text-purple mb-4">
            <i class="fas fa-user-check me-2"></i> <?= Localization::translate('enrollment_approval'); ?>
        </h1>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card stats-pending shadow-lg border-0 clickable-card" data-status="pending" id="pendingCard">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1 fw-bold" id="pendingCount">0</h3>
                                <p class="mb-0 text-muted">Pending Requests</p>
                                <small class="text-muted">Awaiting approval</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card stats-approved shadow-lg border-0 clickable-card" data-status="approved" id="approvedCard">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1 fw-bold" id="approvedCount">0</h3>
                                <p class="mb-0 text-muted">Approved</p>
                                <small class="text-muted">Successfully enrolled</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card stats-rejected shadow-lg border-0 clickable-card" data-status="rejected" id="rejectedCard">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1 fw-bold" id="rejectedCount">0</h3>
                                <p class="mb-0 text-muted">Rejected</p>
                                <small class="text-muted">Not approved</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card stats-total shadow-lg border-0 clickable-card" data-status="all" id="totalCard">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1 fw-bold" id="totalCount">0</h3>
                                <p class="mb-0 text-muted">Total Requests</p>
                                <small class="text-muted">All enrollments</small>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="row mb-4">
            <div class="col-12 text-end">
                <div class="action-buttons">
                    <button type="button" class="btn theme-btn-primary" id="refreshBtn">
                        <i class="fas fa-sync-alt me-2"></i> Refresh Data
                    </button>
                </div>
            </div>
        </div>

        <!-- Enrollment Requests Table -->
        <div class="card modern-card shadow-lg border-0">
            <div class="card-header modern-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white">
                        <i class="fas fa-list me-2"></i> Enrollment Requests
                    </h5>
                    <div class="header-actions">
                        <span class="badge bg-light text-dark" id="currentFilterBadge">Pending</span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="enrollmentsList" class="table-responsive">
                    <div class="text-center py-5">
                        <div class="spinner-border text-purple"></div>
                        <p class="mt-3 text-muted">Loading enrollment requests...</p>
                    </div>
                </div>
                <div id="enrollmentsPagination" class="p-4 bg-light"></div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-modal-header">
                <h5 class="modal-title text-white" id="rejectionModalLabel">
                    <i class="fas fa-times-circle me-2"></i> Reject Enrollment Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-warning d-flex align-items-center mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span>Please provide a reason for rejecting this enrollment request. This will help the user understand why their request was not approved.</span>
                </div>
                <div class="mb-3">
                    <label for="rejectionReason" class="form-label fw-bold">Rejection Reason:</label>
                    <textarea class="form-control modern-textarea" id="rejectionReason" rows="4" 
                              placeholder="Please provide a detailed reason for rejecting this enrollment request..."></textarea>
                    <div class="form-text">This reason will be visible to the user.</div>
                </div>
            </div>
            <div class="modal-footer modern-modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn theme-btn-danger" id="confirmReject">
                    <i class="fas fa-times me-1"></i> Reject Enrollment
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/Unlockyourskills/public/js/enrollment_approval.js"></script>
<?php include 'includes/footer.php'; ?>
