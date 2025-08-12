<?php
require_once 'includes/header.php';
require_once 'config/Localization.php';

$locale = $_SESSION['locale'] ?? 'en';

$assessment = $GLOBALS['assessment'] ?? null;
$attempt = $GLOBALS['attempt'] ?? null;
$attemptId = $GLOBALS['attemptId'] ?? null;

if (!$assessment || !$attempt || !$attemptId) {
    header('Location: /unlockyourskills/my-courses');
    exit;
}

$questions = $assessment['selected_questions'] ?? [];
$totalQuestions = count($questions);
$timeLimit = $attempt['time_limit'] ?? 60;
$timeRemaining = $attempt['time_remaining'] ?? ($timeLimit * 60);
?>

<!DOCTYPE html>
<html lang="<?php echo $locale; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo Localization::translate('assessment_title') ?? 'Assessment'; ?> - <?php echo htmlspecialchars($assessment['title'] ?? ''); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="/unlockyourskills/public/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/unlockyourskills/public/css/style.css" rel="stylesheet">
    <!-- Assessment Player CSS -->
    <link href="/unlockyourskills/public/css/assessment_player.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="/unlockyourskills/public/bootstrap/js/jquery.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="/unlockyourskills/public/bootstrap/js/bootstrap.bundle.min.js"></script>
</head>
<body class="assessment-player-body">
    <div class="assessment-container">
        <!-- Header -->
        <div class="assessment-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="assessment-title"><?php echo htmlspecialchars($assessment['title'] ?? ''); ?></h1>
                        <p class="assessment-subtitle"><?php echo htmlspecialchars($assessment['description'] ?? ''); ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="assessment-info">
                            <div class="info-item">
                                <span class="info-label"><?php echo Localization::translate('assessment_player.question'); ?>:</span>
                                <span class="info-value" id="question-counter">1 / <?php echo $totalQuestions; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?php echo Localization::translate('assessment_player.time_remaining'); ?>:</span>
                                <span class="info-value" id="time-remaining"><?php echo gmdate('H:i:s', $timeRemaining); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?php echo Localization::translate('assessment_player.passing_score'); ?>:</span>
                                <span class="info-value"><?php echo ($assessment['passing_percentage'] ?? 70); ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="assessment-progress">
            <div class="container-fluid">
                <div class="progress">
                    <div class="progress-bar" id="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Start Assessment Screen -->
        <div class="assessment-content" id="start-screen">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="start-assessment-container text-center">
                            <div class="start-icon mb-4">
                                <i class="fas fa-clipboard-check fa-5x text-primary"></i>
                            </div>
                            <h2 class="mb-4"><?php echo Localization::translate('assessment_player.ready_to_start'); ?></h2>
                            <p class="lead mb-4"><?php echo Localization::translate('assessment_player.about_to_begin'); ?> <strong><?php echo htmlspecialchars($assessment['title'] ?? ''); ?></strong></p>
                            
                            <div class="assessment-details mb-4">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="detail-card">
                                            <i class="fas fa-question-circle fa-2x text-info mb-2"></i>
                                            <h5><?php echo $totalQuestions; ?> <?php echo Localization::translate('assessment_player.questions'); ?></h5>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-card">
                                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                            <h5><?php echo gmdate('H:i:s', $timeLimit * 60); ?> <?php echo Localization::translate('assessment_player.time_limit'); ?></h5>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="detail-card">
                                            <i class="fas fa-percentage fa-2x text-success mb-2"></i>
                                            <h5><?php echo ($assessment['passing_percentage'] ?? 70); ?>% <?php echo Localization::translate('assessment_player.to_pass'); ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="safety-features mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-shield-alt me-2"></i><?php echo Localization::translate('assessment_player.your_progress_protected'); ?>
                                </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="safety-item">
                                            <i class="fas fa-wifi text-success"></i>
                                            <small><?php echo Localization::translate('assessment_player.auto_save_every_30'); ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="safety-item">
                                            <i class="fas fa-undo text-info"></i>
                                            <small><?php echo Localization::translate('assessment_player.session_recovery_24h'); ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="safety-item">
                                            <i class="fas fa-bolt text-warning"></i>
                                            <small><?php echo Localization::translate('assessment_player.offline_mode_protection'); ?></small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-primary btn-lg" id="start-assessment-btn">
                                <i class="fas fa-play me-2"></i><?php echo Localization::translate('assessment_player.start_assessment'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content (Hidden initially) -->
        <div class="assessment-content" id="assessment-content" style="display: none;">
            <div class="container-fluid">
                <div class="row">
                    <!-- Question Area -->
                    <div class="col-lg-8">
                        <div class="question-container" id="question-container">
                            <!-- Questions will be loaded here -->
                        </div>
                        
                        <!-- Navigation Buttons -->
                        <div class="question-navigation">
                            <div class="row">
                                <div class="col-6">
                                    <button type="button" class="btn btn-secondary" id="prev-btn" disabled>
                                        <i class="fas fa-arrow-left"></i> <?php echo Localization::translate('assessment_player.previous_question'); ?>
                                    </button>
                                </div>
                                <div class="col-6 text-end">
                                    <button type="button" class="btn btn-primary" id="next-btn">
                                        <?php echo Localization::translate('assessment_player.next_question'); ?> <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <button type="button" class="btn btn-success" id="submit-btn" style="display: none;">
                                        <?php echo Localization::translate('assessment_player.submit_assessment'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Question Navigator -->
                    <div class="col-lg-4">
                        <div class="question-navigator">
                            <h5><?php echo Localization::translate('assessment_player.question_navigator'); ?></h5>
                            <div class="question-grid" id="question-grid">
                                <!-- Question numbers will be generated here -->
                            </div>
                            
                            <div class="navigator-legend">
                                <div class="legend-item">
                                    <span class="legend-color answered"></span>
                                    <span><?php echo Localization::translate('assessment_player.answered_questions'); ?></span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color current"></span>
                                    <span><?php echo Localization::translate('assessment_player.current_question'); ?></span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color unanswered"></span>
                                    <span><?php echo Localization::translate('assessment_player.unanswered_questions'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Confirmation Modal -->
    <div class="modal fade" id="submitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo Localization::translate('assessment_player.confirm_submit'); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><?php echo Localization::translate('assessment_player.confirm_submit'); ?></p>
                    <div class="alert alert-info">
                        <strong><?php echo Localization::translate('assessment_player.answered_questions'); ?>:</strong> 
                        <span id="modal-answered-count">0</span> / <?php echo $totalQuestions; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?php echo Localization::translate('common.cancel'); ?>
                    </button>
                    <button type="button" class="btn btn-success" id="confirm-submit">
                        <?php echo Localization::translate('assessment_player.submit_assessment'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="modal fade" id="resultsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo Localization::translate('assessment_player.assessment_complete'); ?></h5>
                </div>
                <div class="modal-body">
                    <div class="results-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="result-item">
                                    <span class="result-label"><?php echo Localization::translate('assessment_player.your_score'); ?>:</span>
                                    <span class="result-value" id="result-score">0</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label"><?php echo Localization::translate('assessment_player.percentage'); ?>:</span>
                                    <span class="result-value" id="result-percentage">0%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="result-item">
                                    <span class="result-label"><?php echo Localization::translate('assessment_player.result'); ?>:</span>
                                    <span class="result-value" id="result-status">-</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label"><?php echo Localization::translate('assessment_player.max_score'); ?>:</span>
                                    <span class="result-value" id="result-correct">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="back-to-courses">
                        <?php echo Localization::translate('assessment_player.back_to_courses'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assessment Player JavaScript -->
    <script src="/unlockyourskills/public/js/assessment_player.js?v=<?php echo time(); ?>"></script>
    
    <script>
        // Pass PHP data to JavaScript
        window.assessmentData = {
            assessment: <?php echo json_encode($assessment); ?>,
            attempt: <?php echo json_encode($attempt); ?>,
            attemptId: <?php echo json_encode($attemptId); ?>,
            totalQuestions: <?php echo $totalQuestions; ?>,
            timeLimit: <?php echo $timeLimit; ?>,
            timeRemaining: <?php echo $timeRemaining; ?>,
            translations: {
                assessment_safety_features: "<?php echo Localization::translate('assessment_player.assessment_safety_features'); ?>",
                progress_automatically_protected: "<?php echo Localization::translate('assessment_player.progress_automatically_protected'); ?>",
                internet_connection_issues: "<?php echo Localization::translate('assessment_player.internet_connection_issues'); ?>",
                answers_saved_locally: "<?php echo Localization::translate('assessment_player.answers_saved_locally'); ?>",
                offline_mode_activates: "<?php echo Localization::translate('assessment_player.offline_mode_activates'); ?>",
                answers_sync_automatically: "<?php echo Localization::translate('assessment_player.answers_sync_automatically'); ?>",
                no_data_loss: "<?php echo Localization::translate('assessment_player.no_data_loss'); ?>",
                power_outages_system_crashes: "<?php echo Localization::translate('assessment_player.power_outages_system_crashes'); ?>",
                progress_saved_browser: "<?php echo Localization::translate('assessment_player.progress_saved_browser'); ?>",
                session_recovery_available: "<?php echo Localization::translate('assessment_player.session_recovery_available'); ?>",
                resume_exactly_where: "<?php echo Localization::translate('assessment_player.resume_exactly_where'); ?>",
                all_answers_preserved: "<?php echo Localization::translate('assessment_player.all_answers_preserved'); ?>",
                accidentally_closing_tabs: "<?php echo Localization::translate('assessment_player.accidentally_closing_tabs'); ?>",
                warning_popup_prevents: "<?php echo Localization::translate('assessment_player.warning_popup_prevents'); ?>",
                progress_auto_saves_tabs: "<?php echo Localization::translate('assessment_player.progress_auto_saves_tabs'); ?>",
                return_same_question: "<?php echo Localization::translate('assessment_player.return_same_question'); ?>",
                timer_continues: "<?php echo Localization::translate('assessment_player.timer_continues'); ?>",
                important_stable_connection: "<?php echo Localization::translate('assessment_player.important_stable_connection'); ?>",
                auto_save: "<?php echo Localization::translate('assessment_player.auto_save'); ?>",
                every_30_seconds: "<?php echo Localization::translate('assessment_player.every_30_seconds'); ?>",
                session_recovery: "<?php echo Localization::translate('assessment_player.session_recovery'); ?>",
                up_to_24_hours: "<?php echo Localization::translate('assessment_player.up_to_24_hours'); ?>",
                i_understand: "<?php echo Localization::translate('assessment_player.i_understand'); ?>",
                marks: "<?php echo Localization::translate('assessment_player.marks'); ?>",
                difficulty: "<?php echo Localization::translate('assessment_player.difficulty'); ?>",
                skills: "<?php echo Localization::translate('assessment_player.skills'); ?>",
                question: "<?php echo Localization::translate('assessment_player.question'); ?>",
                enter_answer_placeholder: "<?php echo Localization::translate('assessment_player.enter_answer_placeholder'); ?>",
                characters: "<?php echo Localization::translate('assessment_player.characters'); ?>"
            }
        };
    </script>
</body>
</html> 