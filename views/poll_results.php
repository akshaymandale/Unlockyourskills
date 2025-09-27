<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="page-title text-purple mb-0">
                <i class="fas fa-chart-bar me-2"></i> Poll Results
            </h1>
            <a href="<?= UrlHelper::url('polls') ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Polls
            </a>
        </div>
        
        <?php if ($poll): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-purple"><?= htmlspecialchars($poll['title']) ?></h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-<?= $poll['status'] === 'active' ? 'success' : 'secondary' ?>">
                                <?= ucfirst($poll['status']) ?>
                            </span>
                            <span class="badge bg-info">
                                <i class="fas fa-users me-1"></i>
                                <?= $poll['unique_voters'] ?? 0 ?> voters
                            </span>
                        </div>
                    </div>
                    <?php if (!empty($poll['description'])): ?>
                        <p class="text-muted mb-0 mt-2"><?= htmlspecialchars($poll['description']) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <?php if (empty($questions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar text-muted mb-3" style="font-size: 3rem;"></i>
                            <h4 class="text-muted">No Questions Found</h4>
                            <p class="text-muted">This poll doesn't have any questions yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($questions as $question): ?>
                            <div class="poll-question mb-4">
                                <h6 class="text-purple mb-3"><?= htmlspecialchars($question['question_text']) ?></h6>
                                
                                <div class="row">
                                    <?php foreach ($question['options'] as $option): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="fw-medium"><?= htmlspecialchars($option['option_text']) ?></span>
                                                        <span class="badge bg-primary"><?= $option['vote_count'] ?> votes</span>
                                                    </div>
                                                    
                                                    <?php 
                                                    $totalVotes = $question['total_votes'];
                                                    $percentage = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100, 1) : 0;
                                                    ?>
                                                    
                                                    <div class="progress" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" 
                                                             role="progressbar" 
                                                             style="width: <?= $percentage ?>%"
                                                             aria-valuenow="<?= $percentage ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    
                                                    <small class="text-muted"><?= $percentage ?>%</small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Total votes: <?= $question['total_votes'] ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="card-footer">
                    <div class="row text-muted small">
                        <div class="col-md-6">
                            <i class="fas fa-user me-1"></i>
                            Created by <?= htmlspecialchars($poll['created_by_name'] ?? 'Unknown') ?>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <i class="fas fa-calendar me-1"></i>
                            Created <?= date('M j, Y g:i A', strtotime($poll['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                    <h4 class="text-muted">Poll Not Found</h4>
                    <p class="text-muted">The requested poll could not be found or you don't have permission to view it.</p>
                    <a href="<?= UrlHelper::url('polls') ?>" class="btn theme-btn-primary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Polls
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
