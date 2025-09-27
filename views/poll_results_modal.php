<?php if ($poll): ?>
    <div class="modern-poll-results">
        <!-- Modern Poll Header -->
        <div class="results-header">
            <div class="header-content">
                <div class="poll-title-section">
                    <h4 class="poll-title"><?= htmlspecialchars($poll['title']) ?></h4>
                    <?php if (!empty($poll['description'])): ?>
                        <p class="poll-description"><?= htmlspecialchars($poll['description']) ?></p>
                    <?php endif; ?>
                </div>
                <div class="poll-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= $poll['unique_voters'] ?? 0 ?></div>
                            <div class="stat-label">Total Voters</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number"><?= count($questions) ?></div>
                            <div class="stat-label">Questions</div>
                        </div>
                    </div>
                    <div class="status-badge status-<?= $poll['status'] ?>">
                        <i class="fas fa-circle me-1"></i>
                        <?= ucfirst($poll['status']) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Poll Questions with Charts -->
        <?php if (empty($questions)): ?>
            <div class="empty-results">
                <div class="empty-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h5 class="empty-title">No Questions Found</h5>
                <p class="empty-text">This poll doesn't have any questions yet.</p>
            </div>
        <?php else: ?>
            <div class="questions-container">
                <?php foreach ($questions as $questionIndex => $question): ?>
                    <div class="question-card" data-question-id="<?= $question['id'] ?>">
                        <div class="question-header">
                            <h5 class="question-title">
                                <span class="question-number"><?= $questionIndex + 1 ?></span>
                                <?= htmlspecialchars($question['question_text']) ?>
                            </h5>
                            <div class="question-meta">
                                <span class="total-votes">
                                    <i class="fas fa-vote-yea me-1"></i>
                                    <?= $question['total_votes'] ?> total votes
                                </span>
                            </div>
                        </div>
                        
                        <div class="results-visualization">
                            <!-- Chart Container -->
                            <div class="chart-container">
                                <canvas id="chart-<?= $question['id'] ?>" class="results-chart"></canvas>
                            </div>
                            
                            <!-- Detailed Results -->
                            <div class="detailed-results">
                                <?php foreach ($question['options'] as $optionIndex => $option): ?>
                                    <?php 
                                    $totalVotes = $question['total_votes'];
                                    $percentage = $totalVotes > 0 ? round(($option['vote_count'] / $totalVotes) * 100, 1) : 0;
                                    $colorIndex = $optionIndex % 6; // Cycle through colors
                                    ?>
                                    <div class="result-item" data-option-id="<?= $option['id'] ?>">
                                        <div class="result-header">
                                            <div class="option-info">
                                                <div class="option-color" style="background-color: var(--chart-color-<?= $colorIndex ?>)"></div>
                                                <span class="option-text"><?= htmlspecialchars($option['option_text']) ?></span>
                                            </div>
                                            <div class="vote-stats">
                                                <span class="vote-count"><?= $option['vote_count'] ?> votes</span>
                                                <span class="vote-percentage"><?= $percentage ?>%</span>
                                            </div>
                                        </div>
                                        <div class="progress-container">
                                            <div class="progress-bar-custom" style="width: <?= $percentage ?>%; background-color: var(--chart-color-<?= $colorIndex ?>)"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Modern Poll Footer -->
        <div class="results-footer">
            <div class="footer-content">
                <div class="creator-info">
                    <div class="creator-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="creator-details">
                        <div class="creator-name"><?= htmlspecialchars($poll['created_by_name'] ?? 'Unknown') ?></div>
                        <div class="creator-role">Poll Creator</div>
                    </div>
                </div>
                <div class="creation-date">
                    <i class="fas fa-calendar me-1"></i>
                    Created <?= date('M j, Y g:i A', strtotime($poll['created_at'])) ?>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Poll not found or you don't have permission to view it.
    </div>
<?php endif; ?>
