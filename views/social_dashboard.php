<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <div class="container mt-4 social-dashboard">
        <h1 class="page-title text-purple mb-4">
            <i class="fas fa-globe me-2"></i> <?= Localization::translate('social'); ?>
        </h1>
        
        <p class="text-muted mb-4">Stay connected with your learning community through announcements, events, polls, and social updates.</p>

        <!-- Social Features Grid -->
        <div class="row g-4">
            <!-- Opinion Polls Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card social-feature-card h-100" onclick="window.location.href='<?= UrlHelper::url('polls') ?>'">
                    <div class="card-body text-center">
                        <div class="social-icon mb-3">
                            <i class="fas fa-poll text-warning"></i>
                        </div>
                        <h5 class="card-title"><?= Localization::translate('opinion_polls'); ?></h5>
                        <p class="card-text text-muted small">Participate in polls and share your opinions</p>
                        <div class="mt-3">
                            <?php if ($data['counts']['polls'] > 0): ?>
                                <span class="badge bg-warning"><?= $data['counts']['polls'] ?> Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcements Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card social-feature-card h-100" onclick="window.location.href='<?= UrlHelper::url('my-announcements') ?>'">
                    <div class="card-body text-center">
                        <div class="social-icon mb-3">
                            <i class="fas fa-bullhorn text-danger"></i>
                        </div>
                        <h5 class="card-title"><?= Localization::translate('announcements'); ?></h5>
                        <p class="card-text text-muted small">Stay updated with important announcements</p>
                        <div class="mt-3">
                            <?php if ($data['counts']['announcements'] > 0): ?>
                                <span class="badge bg-danger"><?= $data['counts']['announcements'] ?> New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card social-feature-card h-100" onclick="window.location.href='<?= UrlHelper::url('my-events') ?>'">
                    <div class="card-body text-center">
                        <div class="social-icon mb-3">
                            <i class="fas fa-calendar-alt text-info"></i>
                        </div>
                        <h5 class="card-title"><?= Localization::translate('events'); ?></h5>
                        <p class="card-text text-muted small">Join events, webinars, and live sessions</p>
                        <div class="mt-3">
                            <?php if ($data['counts']['events'] > 0): ?>
                                <span class="badge bg-info"><?= $data['counts']['events'] ?> Upcoming</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Feed Card -->
            <div class="col-md-6 col-lg-3">
                <div class="card social-feature-card h-100" onclick="window.location.href='<?= UrlHelper::url('feed') ?>'">
                    <div class="card-body text-center">
                        <div class="social-icon mb-3">
                            <i class="fas fa-rss text-success"></i>
                        </div>
                        <h5 class="card-title"><?= Localization::translate('social_feed'); ?></h5>
                        <p class="card-text text-muted small">Connect and share with the community</p>
                        <div class="mt-3">
                            <?php if ($data['counts']['social_feed'] > 0): ?>
                                <span class="badge bg-success"><?= $data['counts']['social_feed'] ?> New Posts</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-purple mb-4">Recent Activity</h3>
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($data['polls']) && empty($data['announcements']) && empty($data['events']) && empty($data['social_feed'])): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-info-circle text-muted mb-3" style="font-size: 2rem;"></i>
                                <p class="text-muted">No recent activity available. Check out the social features above to get started!</p>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <!-- Recent Polls -->
                                <?php if (!empty($data['polls'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h5 class="text-warning mb-3">
                                            <i class="fas fa-poll me-2"></i>Active Polls
                                        </h5>
                                        <?php foreach (array_slice($data['polls'], 0, 3) as $poll): ?>
                                            <div class="activity-item mb-3 p-3 border rounded">
                                                <h6 class="mb-1"><?= htmlspecialchars($poll['title']) ?></h6>
                                                <p class="text-muted small mb-2"><?= htmlspecialchars(substr($poll['description'], 0, 100)) ?><?= strlen($poll['description']) > 100 ? '...' : '' ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-vote-yea me-1"></i>
                                                        <?= $poll['total_votes'] ?> votes
                                                    </small>
                                                    <small class="text-muted">
                                                        Ends: <?= date('M j, Y', strtotime($poll['end_datetime'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Recent Announcements -->
                                <?php if (!empty($data['announcements'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h5 class="text-danger mb-3">
                                            <i class="fas fa-bullhorn me-2"></i>Recent Announcements
                                        </h5>
                                        <?php foreach (array_slice($data['announcements'], 0, 3) as $announcement): ?>
                                            <div class="activity-item mb-3 p-3 border rounded">
                                                <div class="d-flex align-items-start">
                                                    <span class="badge bg-<?= $announcement['urgency'] === 'urgent' ? 'danger' : ($announcement['urgency'] === 'warning' ? 'warning' : 'info') ?> me-2">
                                                        <?= ucfirst($announcement['urgency']) ?>
                                                    </span>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?= htmlspecialchars($announcement['title']) ?></h6>
                                                        <p class="text-muted small mb-2"><?= htmlspecialchars(substr($announcement['body'], 0, 100)) ?><?= strlen($announcement['body']) > 100 ? '...' : '' ?></p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-user me-1"></i>
                                                            <?= htmlspecialchars($announcement['created_by_name']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Upcoming Events -->
                                <?php if (!empty($data['events'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h5 class="text-info mb-3">
                                            <i class="fas fa-calendar-alt me-2"></i>Upcoming Events
                                        </h5>
                                        <?php foreach (array_slice($data['events'], 0, 3) as $event): ?>
                                            <div class="activity-item mb-3 p-3 border rounded">
                                                <h6 class="mb-1"><?= htmlspecialchars($event['title']) ?></h6>
                                                <p class="text-muted small mb-2"><?= htmlspecialchars(substr($event['description'], 0, 100)) ?><?= strlen($event['description']) > 100 ? '...' : '' ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('M j, Y g:i A', strtotime($event['start_datetime'])) ?>
                                                    </small>
                                                    <?php if ($event['rsvp_count'] > 0): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-users me-1"></i>
                                                            <?= $event['rsvp_count'] ?> attending
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Recent Social Posts -->
                                <?php if (!empty($data['social_feed'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h5 class="text-success mb-3">
                                            <i class="fas fa-rss me-2"></i>Recent Posts
                                        </h5>
                                        <?php foreach (array_slice($data['social_feed'], 0, 3) as $post): ?>
                                            <div class="activity-item mb-3 p-3 border rounded">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?= htmlspecialchars($post['title'] ?: 'Social Post') ?></h6>
                                                        <p class="text-muted small mb-2"><?= htmlspecialchars(substr($post['body'], 0, 100)) ?><?= strlen($post['body']) > 100 ? '...' : '' ?></p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                <i class="fas fa-user me-1"></i>
                                                                <?= htmlspecialchars($post['author_name']) ?>
                                                            </small>
                                                            <div class="d-flex gap-3">
                                                                <?php if ($post['comment_count'] > 0): ?>
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-comments me-1"></i>
                                                                        <?= $post['comment_count'] ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                                <?php if ($post['reaction_count'] > 0): ?>
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-heart me-1"></i>
                                                                        <?= $post['reaction_count'] ?>
                                                                    </small>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Social Dashboard JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click handlers for activity items
    addActivityItemClickHandlers();
});

function addActivityItemClickHandlers() {
    // Add click handlers to make activity items clickable
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach(item => {
        item.style.cursor = 'pointer';
        item.addEventListener('click', function() {
            // This would navigate to the specific item's detail page
            // For now, just show a message
            console.log('Activity item clicked');
        });
    });
}
</script>

<?php include 'includes/footer.php'; ?>
