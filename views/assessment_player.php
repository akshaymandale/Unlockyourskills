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
                                <span class="info-label">Question:</span>
                                <span class="info-value" id="question-counter">1 / <?php echo $totalQuestions; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Time Remaining:</span>
                                <span class="info-value" id="time-remaining"><?php echo gmdate('H:i:s', $timeRemaining); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label"><?php echo Localization::translate('passing_score') ?? 'Passing Score'; ?>:</span>
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

        <!-- Main Content -->
        <div class="assessment-content">
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
                                        <i class="fas fa-arrow-left"></i> Previous
                                    </button>
                                </div>
                                <div class="col-6 text-end">
                                    <button type="button" class="btn btn-primary" id="next-btn">
                                        Next <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <button type="button" class="btn btn-success" id="submit-btn" style="display: none;">
                                        Submit Assessment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Question Navigator -->
                    <div class="col-lg-4">
                        <div class="question-navigator">
                            <h5>Question Navigator</h5>
                            <div class="question-grid" id="question-grid">
                                <!-- Question numbers will be generated here -->
                            </div>
                            
                            <div class="navigator-legend">
                                <div class="legend-item">
                                    <span class="legend-color answered"></span>
                                    <span>Answered</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color current"></span>
                                    <span>Current</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color unanswered"></span>
                                    <span>Unanswered</span>
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
                    <h5 class="modal-title">Confirm Submission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to submit your assessment? You cannot change your answers after submission.</p>
                    <div class="alert alert-info">
                        <strong>Questions Answered:</strong> 
                        <span id="modal-answered-count">0</span> / <?php echo $totalQuestions; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="confirm-submit">
                        Submit Assessment
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
                    <h5 class="modal-title">Assessment Results</h5>
                </div>
                <div class="modal-body">
                    <div class="results-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="result-item">
                                    <span class="result-label">Score:</span>
                                    <span class="result-value" id="result-score">0</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Percentage:</span>
                                    <span class="result-value" id="result-percentage">0%</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="result-item">
                                    <span class="result-label">Status:</span>
                                    <span class="result-value" id="result-status">-</span>
                                </div>
                                <div class="result-item">
                                    <span class="result-label">Correct Answers:</span>
                                    <span class="result-value" id="result-correct">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="back-to-courses">
                        Back to Courses
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
            timeRemaining: <?php echo $timeRemaining; ?>
        };
    </script>
</body>
</html> 