<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4">
        <!-- Modern Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="modern-header-card">
                    <div class="d-flex align-items-center">
                        <div class="header-icon">
                            <i class="fas fa-poll"></i>
                        </div>
                        <div class="header-content">
                            <h1 class="page-title mb-1"><?= Localization::translate('opinion_polls'); ?></h1>
                            <p class="text-muted mb-0">Participate in polls and share your opinions with the community</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Polls List -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($polls)): ?>
                    <div class="modern-empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-poll"></i>
                        </div>
                        <h4 class="empty-state-title">No Active Polls</h4>
                        <p class="empty-state-text">There are currently no active polls available for you to participate in.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($polls as $poll): ?>
                        <div class="modern-poll-card mb-4">
                            <div class="poll-card-header">
                                <div class="poll-title-section">
                                    <h5 class="poll-title"><?= htmlspecialchars($poll['title']) ?></h5>
                                    <div class="poll-meta">
                                        <span class="poll-creator">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($poll['created_by_name']) ?>
                                        </span>
                                        <span class="poll-divider">•</span>
                                        <span class="poll-end-date">
                                            <i class="fas fa-clock me-1"></i>
                                            Ends <?= date('M j, Y g:i A', strtotime($poll['end_datetime'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="poll-badges">
                                    <span class="status-badge status-<?= $poll['status'] ?>">
                                        <i class="fas fa-circle me-1"></i>
                                        <?= ucfirst($poll['status']) ?>
                                    </span>
                                    <span class="voters-badge">
                                        <i class="fas fa-users me-1"></i>
                                        <?= $poll['unique_voters'] ?> voters
                                    </span>
                                </div>
                            </div>
                            
                            <div class="poll-card-body">
                                <?php if (!empty($poll['description'])): ?>
                                    <div class="poll-description">
                                        <p class="description-text"><?= htmlspecialchars($poll['description']) ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Check if user has voted on any question in this poll
                                $userVotedOnPoll = false;
                                $votedQuestions = [];
                                foreach ($userVotes as $vote) {
                                    if ($vote['poll_id'] == $poll['id']) {
                                        $userVotedOnPoll = true;
                                        $votedQuestions[] = $vote['question_id'];
                                    }
                                }

                                // Determine if results should be shown
                                $canViewResults = false;
                                $pollEnded = strtotime($poll['end_datetime']) < time();
                                
                                if ($poll['show_results'] === 'after_vote' && $userVotedOnPoll) {
                                    $canViewResults = true;
                                } elseif ($poll['show_results'] === 'after_end' && $pollEnded) {
                                    $canViewResults = true;
                                } elseif ($poll['show_results'] === 'admin_only') {
                                    // Check if user is admin
                                    $canViewResults = isset($_SESSION['user']['system_role']) && $_SESSION['user']['system_role'] === 'admin';
                                }
                                ?>

                                <?php if ($userVotedOnPoll): ?>
                                    <!-- Show user's vote status -->
                                    <div class="voted-status-card">
                                        <div class="voted-status-content">
                                            <div class="voted-icon">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                            <div class="voted-text">
                                                <h6 class="voted-title">Vote Submitted</h6>
                                                <p class="voted-message">You have already voted on this poll.</p>
                                            </div>
                                        </div>
                                        <?php if ($canViewResults): ?>
                                            <button type="button" class="btn btn-modern-primary view-results-btn" data-poll-id="<?= $poll['id'] ?>">
                                                <i class="fas fa-chart-bar me-1"></i>
                                                View Results
                                            </button>
                                        <?php else: ?>
                                            <div class="results-info">
                                                <?php if ($poll['show_results'] === 'after_end'): ?>
                                                    <span class="info-text">Results will be available after the poll ends.</span>
                                                <?php elseif ($poll['show_results'] === 'admin_only'): ?>
                                                    <span class="info-text">Results are only visible to administrators.</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Single voting form for entire poll -->
                                    <form class="poll-vote-form" data-poll-id="<?= $poll['id'] ?>" data-show-results="<?= $poll['show_results'] ?>">
                                        <!-- Poll Questions -->
                                        <?php foreach ($poll['questions'] as $question): ?>
                                            <div class="question-container">
                                                <h6 class="question-title"><?= htmlspecialchars($question['question_text']) ?></h6>
                                                
                                                <div class="options-container">
                                                    <?php foreach ($question['options'] as $option): ?>
                                                        <div class="modern-option">
                                                            <input class="option-input" 
                                                                   type="<?= $poll['type'] === 'single_choice' ? 'radio' : 'checkbox' ?>" 
                                                                   name="question_<?= $question['id'] ?>[]" 
                                                                   value="<?= $option['id'] ?>" 
                                                                   id="option_<?= $option['id'] ?>"
                                                                   data-question-id="<?= $question['id'] ?>">
                                                            <label class="option-label" for="option_<?= $option['id'] ?>">
                                                                <span class="option-text"><?= htmlspecialchars($option['option_text']) ?></span>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <!-- Single submit button for entire poll -->
                                        <div class="form-actions">
                                            <button type="submit" class="btn btn-modern-primary btn-lg">
                                                <i class="fas fa-vote-yea me-2"></i>
                                                Submit Vote
                                            </button>
                                            <?php if ($canViewResults): ?>
                                                <button type="button" class="btn btn-modern-secondary view-results-btn" data-poll-id="<?= $poll['id'] ?>">
                                                    <i class="fas fa-chart-bar me-1"></i>
                                                    View Results
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modern Polls Page Styles -->
<style>
/* Modern Header Card */
.modern-header-card {
    background: linear-gradient(135deg, #6f42c1 0%, #8e44ad 100%);
    border-radius: 16px;
    padding: 2rem;
    color: white;
    box-shadow: 0 8px 32px rgba(111, 66, 193, 0.3);
    margin-bottom: 2rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
    font-size: 1.5rem;
}

.header-content .page-title {
    color: white;
    font-weight: 600;
    font-size: 2rem;
}

.header-content .text-muted {
    color: rgba(255, 255, 255, 0.8) !important;
    font-size: 1.1rem;
}

/* Empty State */
.modern-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.empty-state-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: white;
    font-size: 2rem;
}

.empty-state-title {
    color: #333;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-state-text {
    color: #666;
    font-size: 1.1rem;
}

/* Modern Poll Cards */
.modern-poll-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: none;
    overflow: hidden;
    transition: all 0.3s ease;
}

.modern-poll-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}

.poll-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.poll-title-section {
    margin-bottom: 1rem;
}

.poll-title {
    color: #333;
    font-weight: 600;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.poll-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #666;
    font-size: 0.9rem;
}

.poll-divider {
    color: #ccc;
}

.poll-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.status-active {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.status-draft {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

.voters-badge {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
}

/* Poll Card Body */
.poll-card-body {
    padding: 1.5rem;
}

.poll-description {
    margin-bottom: 1.5rem;
}

.description-text {
    color: #666;
    font-size: 1rem;
    line-height: 1.6;
    margin: 0;
}

/* Voted Status Card */
.voted-status-card {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 1px solid #c3e6cb;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.voted-status-content {
    display: flex;
    align-items: center;
}

.voted-icon {
    width: 50px;
    height: 50px;
    background: #28a745;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    margin-right: 1rem;
}

.voted-title {
    color: #155724;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.voted-message {
    color: #155724;
    margin: 0;
    font-size: 0.9rem;
}

.results-info {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Modern Poll Form */
.poll-vote-form {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
}

.question-container {
    margin-bottom: 2rem;
}

.question-title {
    color: #333;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #6f42c1;
}

.options-container {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.modern-option {
    position: relative;
}

.option-input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.option-label {
    display: flex;
    align-items: center;
    padding: 1rem 1.25rem;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.option-label:hover {
    border-color: #6f42c1;
    background: #f8f9ff;
}

/* Visible checkbox/radio button */
.option-label::before {
    content: '';
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    border: 2px solid #6f42c1;
    background: white;
    transition: all 0.3s ease;
}

/* Radio button styling */
.option-input[type="radio"] + .option-label::before {
    border-radius: 50%;
}

/* Checkbox styling */
.option-input[type="checkbox"] + .option-label::before {
    border-radius: 4px;
}

/* Hover state for input */
.option-label:hover::before {
    border-color: #8e44ad;
    background: #f8f9ff;
}

/* Checked state */
.option-input:checked + .option-label {
    border-color: #6f42c1;
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
    color: white;
}

.option-input:checked + .option-label::before {
    border-color: white;
    background: white;
}

/* Radio button checked state */
.option-input[type="radio"]:checked + .option-label::before {
    background: #6f42c1;
}

/* Checkbox checked state */
.option-input[type="checkbox"]:checked + .option-label::before {
    background: #6f42c1;
    border-radius: 4px;
}

/* Checkmark for checkbox */
.option-input[type="checkbox"]:checked + .option-label::after {
    content: '✓';
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: white;
    font-weight: bold;
    font-size: 0.8rem;
}

.option-text {
    margin-left: 2.5rem;
    font-weight: 500;
}

/* Form Actions */
.form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid #e9ecef;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.btn-modern-primary {
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
    border: none;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(111, 66, 193, 0.3);
}

.btn-modern-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(111, 66, 193, 0.4);
    color: white;
}

.btn-modern-secondary {
    background: white;
    border: 2px solid #6f42c1;
    color: #6f42c1;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-modern-secondary:hover {
    background: #6f42c1;
    color: white;
    transform: translateY(-2px);
}

/* Responsive Design */
@media (max-width: 768px) {
    .modern-header-card {
        padding: 1.5rem;
    }
    
    .header-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
        margin-right: 1rem;
    }
    
    .header-content .page-title {
        font-size: 1.5rem;
    }
    
    .poll-card-header {
        padding: 1rem;
    }
    
    .poll-badges {
        margin-top: 1rem;
    }
    
    .voted-status-card {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .btn-modern-primary,
    .btn-modern-secondary {
        width: 100%;
    }
}

/* Animation for loading states */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modern-poll-card {
    animation: fadeInUp 0.6s ease-out;
}
</style>

<!-- Modern Poll Results Modal -->
<div class="modal fade" id="pollResultsModal" tabindex="-1" aria-labelledby="pollResultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content modern-modal">
            <div class="modal-header modern-modal-header">
                <div class="header-content">
                    <h5 class="modal-title" id="pollResultsModalLabel">
                        <i class="fas fa-chart-bar me-2"></i>Poll Results
                    </h5>
                    <p class="modal-subtitle">Interactive data visualization and analytics</p>
                </div>
                <button type="button" class="btn-close modern-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modern-modal-body" id="pollResultsContent">
                <div class="loading-state">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                    </div>
                    <p class="loading-text">Loading poll results...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Poll Results Modal Styles -->
<style>
/* Chart Color Variables */
:root {
    --chart-color-0: #6f42c1;
    --chart-color-1: #8e44ad;
    --chart-color-2: #28a745;
    --chart-color-3: #17a2b8;
    --chart-color-4: #ffc107;
    --chart-color-5: #dc3545;
}

/* Modern Modal Styling */
.modern-modal {
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.modern-modal-header {
    background: linear-gradient(135deg, #6f42c1 0%, #8e44ad 100%);
    color: white;
    border: none;
    padding: 2rem;
}

.modern-modal-header .header-content {
    flex: 1;
}

.modern-modal-header .modal-title {
    color: white;
    font-weight: 600;
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
}

.modern-modal-header .modal-subtitle {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
    margin: 0;
}

.modern-close {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    opacity: 1;
    transition: all 0.3s ease;
    border: none;
    position: relative;
}

.modern-close::before {
    content: '×';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
}

.modern-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modern-close:hover::before {
    color: white;
}

.modern-modal-body {
    padding: 0;
    max-height: 80vh;
    overflow-y: auto;
}

/* Loading State */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
}

.loading-spinner {
    margin-bottom: 1rem;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #6f42c1;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    color: #666;
    font-size: 1rem;
    margin: 0;
}

/* Modern Poll Results */
.modern-poll-results {
    background: #f8f9fa;
    min-height: 100%;
}

/* Results Header */
.results-header {
    background: white;
    padding: 2rem;
    border-bottom: 1px solid #e9ecef;
}

.results-header .header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.poll-title-section .poll-title {
    color: #333;
    font-weight: 600;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.poll-title-section .poll-description {
    color: #666;
    font-size: 1rem;
    margin: 0;
    line-height: 1.5;
}

.poll-stats {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.stat-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 120px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
    line-height: 1;
}

.stat-label {
    font-size: 0.8rem;
    color: #666;
    margin-top: 0.25rem;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
}

.status-active {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.status-draft {
    background: linear-gradient(135deg, #6c757d, #495057);
    color: white;
}

/* Questions Container */
.questions-container {
    padding: 2rem;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.question-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
}

.question-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}

.question-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.question-title {
    color: #333;
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.question-number {
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 600;
}

.question-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.total-votes {
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
}

/* Results Visualization */
.results-visualization {
    padding: 1.5rem;
}

.chart-container {
    margin-bottom: 2rem;
    background: white;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.results-chart {
    width: 100% !important;
    height: 300px !important;
}

/* Detailed Results */
.detailed-results {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.result-item {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.3s ease;
}

.result-item:hover {
    background: #e9ecef;
    transform: translateX(4px);
}

.result-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.option-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.option-color {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.option-text {
    font-weight: 500;
    color: #333;
}

.vote-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.vote-count {
    background: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #6f42c1;
}

.vote-percentage {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
}

.progress-container {
    height: 8px;
    background: white;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
}

.progress-bar-custom {
    height: 100%;
    border-radius: 4px;
    transition: width 0.8s ease;
    position: relative;
}

.progress-bar-custom::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Empty Results */
.empty-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    margin: 2rem;
    border-radius: 16px;
}

.empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    color: white;
    font-size: 2rem;
}

.empty-title {
    color: #333;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-text {
    color: #666;
    font-size: 1rem;
}

/* Results Footer */
.results-footer {
    background: white;
    padding: 1.5rem 2rem;
    border-top: 1px solid #e9ecef;
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.creator-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.creator-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #6f42c1, #8e44ad);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1rem;
}

.creator-details {
    flex: 1;
}

.creator-name {
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.creator-role {
    color: #666;
    font-size: 0.8rem;
}

.creation-date {
    color: #666;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modern-modal-header {
        padding: 1.5rem;
    }
    
    .results-header .header-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .poll-stats {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .stat-card {
        min-width: 100px;
    }
    
    .questions-container {
        padding: 1rem;
    }
    
    .question-header {
        padding: 1rem;
    }
    
    .results-visualization {
        padding: 1rem;
    }
    
    .footer-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Poll Voting JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle poll voting
    const voteForms = document.querySelectorAll('.poll-vote-form');
    
    voteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const pollId = this.dataset.pollId;
            const formData = new FormData(this);
            
            // Get all selected options grouped by question
            const questionVotes = {};
            const inputs = this.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked');
            
            inputs.forEach(input => {
                const questionId = input.dataset.questionId;
                if (!questionVotes[questionId]) {
                    questionVotes[questionId] = [];
                }
                questionVotes[questionId].push(input.value);
            });
            
            // Check if at least one question has been answered
            if (Object.keys(questionVotes).length === 0) {
                alert('Please select at least one option.');
                return;
            }
            
            // Prepare vote data for all questions
            const voteData = {
                poll_id: pollId,
                votes: questionVotes
            };
            
            // Submit vote
            fetch(`<?= UrlHelper::url('polls') ?>/${pollId}/vote`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(voteData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const showResults = this.dataset.showResults;
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    
                    let alertContent = `
                        <i class="fas fa-check-circle me-2"></i>
                        ${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    
                    // Add View Results button if show_results is 'after_vote'
                    if (showResults === 'after_vote') {
                        alertContent = `
                            <i class="fas fa-check-circle me-2"></i>
                            ${data.message}
                            <button type="button" class="btn btn-sm btn-outline-primary ms-2 view-results-btn" data-poll-id="${pollId}">
                                <i class="fas fa-chart-bar me-1"></i>
                                View Results
                            </button>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                    }
                    
                    alert.innerHTML = alertContent;
                    
                    // Insert before the form
                    this.parentNode.insertBefore(alert, this);
                    
                    // Hide the form and show success message
                    this.style.display = 'none';
                    
                    // Reload page after 2 seconds to show updated state
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred while submitting your vote. Please try again.');
            });
        });
    });
    
    // Handle View Results button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-results-btn') || e.target.closest('.view-results-btn')) {
            e.preventDefault();
            
            const button = e.target.classList.contains('view-results-btn') ? e.target : e.target.closest('.view-results-btn');
            const pollId = button.dataset.pollId;
            
            if (pollId) {
                showPollResults(pollId);
            }
        }
    });
    
    function showPollResults(pollId) {
        const modal = new bootstrap.Modal(document.getElementById('pollResultsModal'));
        const content = document.getElementById('pollResultsContent');
        
        // Show loading state
        content.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading poll results...</p>
            </div>
        `;
        
        // Show modal
        modal.show();
        
        // Fetch poll results
        fetch(`<?= UrlHelper::url('polls') ?>/${pollId}/results`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = data.html;
                // Initialize charts after content is loaded
                initializeCharts();
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message || 'Failed to load poll results'}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    An error occurred while loading poll results. Please try again.
                </div>
            `;
        });
    }

    // Chart creation function
    function createPollChart(questionId, options) {
        const canvas = document.getElementById(`chart-${questionId}`);
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        
        // Chart colors
        const colors = [
            '#6f42c1', '#8e44ad', '#28a745', '#17a2b8', '#ffc107', '#dc3545'
        ];

        // Prepare data
        const labels = options.map(option => option.option_text);
        const data = options.map(option => option.vote_count);
        const backgroundColors = options.map((_, index) => colors[index % colors.length]);

        // Create Power BI-style chart
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverBorderWidth: 4,
                    hoverBorderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#6f42c1',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return `${context.label}: ${context.parsed} votes (${percentage}%)`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 1500,
                    easing: 'easeOutQuart'
                },
                cutout: '60%',
                elements: {
                    arc: {
                        borderWidth: 3
                    }
                }
            }
        });
    }

    // Initialize charts when modal content is loaded
    function initializeCharts() {
        // Wait a bit for the DOM to be ready
        setTimeout(() => {
            const questionCards = document.querySelectorAll('.question-card');
            questionCards.forEach(card => {
                const questionId = card.dataset.questionId;
                const options = [];
                
                // Extract option data from the detailed results
                const resultItems = card.querySelectorAll('.result-item');
                resultItems.forEach(item => {
                    const optionText = item.querySelector('.option-text').textContent;
                    const voteCount = parseInt(item.querySelector('.vote-count').textContent.match(/\d+/)[0]);
                    options.push({
                        option_text: optionText,
                        vote_count: voteCount
                    });
                });
                
                if (options.length > 0) {
                    createPollChart(questionId, options);
                }
            });
        }, 100);
    }
});
</script>

<?php include 'includes/footer.php'; ?>

