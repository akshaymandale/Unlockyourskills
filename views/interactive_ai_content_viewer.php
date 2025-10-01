<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive AI Content: <?= htmlspecialchars($GLOBALS['content']['title'] ?? 'Untitled') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .interactive-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .content-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        .sidebar {
            width: 350px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            overflow-y: auto;
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .main-content {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .content-frame-container {
            flex: 1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }

        .content-frame {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 15px;
        }

        .metadata-section {
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
        }

        .metadata-section h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .metadata-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .metadata-item:last-child {
            border-bottom: none;
        }

        .metadata-label {
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
        }

        .metadata-value {
            color: var(--secondary-color);
            font-size: 0.85rem;
            text-align: right;
            max-width: 60%;
        }

        .badge-custom {
            font-size: 0.7rem;
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
        }

        .warning-banner {
            background: linear-gradient(45deg, var(--warning-color), #ffeb3b);
            color: var(--dark-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #ff9800;
        }

        .error-banner {
            background: linear-gradient(45deg, var(--danger-color), #f44336);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .time-limit-indicator {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            z-index: 100;
        }

        .time-expired {
            background: var(--danger-color) !important;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }


        .ai-personality-indicator {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .requirement-item {
            background: rgba(255, 255, 255, 0.6);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid var(--info-color);
        }

        .requirement-item.warning {
            border-left-color: var(--warning-color);
            background: rgba(255, 193, 7, 0.1);
        }

        .requirement-item.error {
            border-left-color: var(--danger-color);
            background: rgba(220, 53, 69, 0.1);
        }

        .vr-ar-indicator {
            background: linear-gradient(45deg, #9c27b0, #e91e63);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .collapsible-section {
            cursor: pointer;
            user-select: none;
        }

        .collapsible-section:hover {
            background: rgba(0, 0, 0, 0.05);
            border-radius: 5px;
        }

        .collapsible-content {
            display: none;
            padding-top: 0.5rem;
        }

        .collapsible-content.show {
            display: block;
        }

        @media (max-width: 768px) {
            .content-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="interactive-header">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fas fa-robot me-2"></i>
                        <?= htmlspecialchars($GLOBALS['content']['title'] ?? 'Interactive AI Content') ?>
                    </h4>
                    <small class="text-muted">
                        <?= htmlspecialchars($GLOBALS['metadata']['basic_info']['content_type'] ?? '') ?> • 
                        Version <?= htmlspecialchars($GLOBALS['metadata']['basic_info']['version'] ?? '') ?>
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="toggleSidebar()">
                        <i class="fas fa-info-circle"></i> Toggle Info
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="window.close()">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="content-container">
        <!-- Sidebar with Metadata -->
        <div class="sidebar" id="sidebar">
            <!-- Validation Messages -->
            <?php if (!$GLOBALS['validation']['valid']): ?>
                <div class="error-banner">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Cannot Launch:</strong> <?= htmlspecialchars($GLOBALS['validation']['error'] ?? 'Unknown error') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($GLOBALS['validation']['warnings'])): ?>
                <div class="warning-banner">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warnings:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($GLOBALS['validation']['warnings'] as $warning): ?>
                            <li><?= htmlspecialchars($warning) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- AI Personality Indicator -->
            <?php if (!empty($GLOBALS['metadata']['ai_configuration']['tutor_personality'])): ?>
                <div class="ai-personality-indicator">
                    <i class="fas fa-user-tie me-2"></i>
                    <strong>AI Tutor:</strong> <?= htmlspecialchars($GLOBALS['metadata']['ai_configuration']['tutor_personality']) ?>
                </div>
            <?php endif; ?>

            <!-- VR/AR Support Indicator -->
            <?php if ($GLOBALS['metadata']['computed']['has_vr_support'] || $GLOBALS['metadata']['computed']['has_ar_support']): ?>
                <div class="vr-ar-indicator">
                    <i class="fas fa-vr-cardboard me-2"></i>
                    <?php if ($GLOBALS['metadata']['computed']['has_vr_support']): ?>
                        <strong>VR:</strong> <?= htmlspecialchars($GLOBALS['metadata']['technical_requirements']['vr_platform']) ?>
                    <?php endif; ?>
                    <?php if ($GLOBALS['metadata']['computed']['has_ar_support']): ?>
                        <?php if ($GLOBALS['metadata']['computed']['has_vr_support']): ?> • <?php endif; ?>
                        <strong>AR:</strong> <?= htmlspecialchars($GLOBALS['metadata']['technical_requirements']['ar_platform']) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Basic Information -->
            <div class="metadata-section collapsible-section" onclick="toggleSection('basic-info')">
                <h6><i class="fas fa-info-circle me-2"></i>Basic Information <i class="fas fa-chevron-down float-end"></i></h6>
            </div>
            <div class="collapsible-content show" id="basic-info">
                <div class="metadata-item">
                    <span class="metadata-label">Title</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['basic_info']['title'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Version</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['basic_info']['version'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Language</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['basic_info']['language'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Content Type</span>
                    <span class="metadata-value">
                        <span class="badge badge-custom bg-primary"><?= htmlspecialchars($GLOBALS['metadata']['learning_design']['content_type'] ?? 'N/A') ?></span>
                    </span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Difficulty</span>
                    <span class="metadata-value">
                        <span class="badge badge-custom bg-info"><?= htmlspecialchars($GLOBALS['metadata']['learning_design']['difficulty_level'] ?? 'N/A') ?></span>
                    </span>
                </div>
            </div>

            <!-- AI Configuration -->
            <div class="metadata-section collapsible-section" onclick="toggleSection('ai-config')">
                <h6><i class="fas fa-brain me-2"></i>AI Configuration <i class="fas fa-chevron-down float-end"></i></h6>
            </div>
            <div class="collapsible-content" id="ai-config">
                <div class="metadata-item">
                    <span class="metadata-label">AI Model</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['ai_configuration']['ai_model'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Interaction Type</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['ai_configuration']['interaction_type'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Tutor Personality</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['ai_configuration']['tutor_personality'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Response Style</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['ai_configuration']['response_style'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Knowledge Domain</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['ai_configuration']['knowledge_domain'] ?? 'N/A') ?></span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Adaptation Algorithm</span>
                    <span class="metadata-value"><?= htmlspecialchars($GLOBALS['metadata']['ai_configuration']['adaptation_algorithm'] ?? 'N/A') ?></span>
                </div>
            </div>

            <!-- Learning Design -->
            <div class="metadata-section collapsible-section" onclick="toggleSection('learning-design')">
                <h6><i class="fas fa-graduation-cap me-2"></i>Learning Design <i class="fas fa-chevron-down float-end"></i></h6>
            </div>
            <div class="collapsible-content" id="learning-design">
                <?php if (!empty($GLOBALS['metadata']['learning_design']['learning_objectives'])): ?>
                    <div class="metadata-item">
                        <span class="metadata-label">Learning Objectives</span>
                        <span class="metadata-value">
                            <?php if (is_array($GLOBALS['metadata']['learning_design']['learning_objectives'])): ?>
                                <?php foreach ($GLOBALS['metadata']['learning_design']['learning_objectives'] as $objective): ?>
                                    <div class="small">• <?= htmlspecialchars($objective) ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?= htmlspecialchars($GLOBALS['metadata']['learning_design']['learning_objectives']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($GLOBALS['metadata']['learning_design']['prerequisites'])): ?>
                    <div class="metadata-item">
                        <span class="metadata-label">Prerequisites</span>
                        <span class="metadata-value">
                            <?php if (is_array($GLOBALS['metadata']['learning_design']['prerequisites'])): ?>
                                <?php foreach ($GLOBALS['metadata']['learning_design']['prerequisites'] as $prereq): ?>
                                    <div class="small">• <?= htmlspecialchars($prereq) ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?= htmlspecialchars($GLOBALS['metadata']['learning_design']['prerequisites']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="metadata-item">
                    <span class="metadata-label">Time Limit</span>
                    <span class="metadata-value">
                        <?php if ($GLOBALS['metadata']['computed']['has_time_limit']): ?>
                            <span class="badge badge-custom bg-warning"><?= htmlspecialchars($GLOBALS['metadata']['learning_design']['time_limit']) ?> minutes</span>
                        <?php else: ?>
                            No limit
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <!-- Technical Requirements -->
            <div class="metadata-section collapsible-section" onclick="toggleSection('tech-requirements')">
                <h6><i class="fas fa-cogs me-2"></i>Technical Requirements <i class="fas fa-chevron-down float-end"></i></h6>
            </div>
            <div class="collapsible-content" id="tech-requirements">
                <div class="metadata-item">
                    <span class="metadata-label">Mobile Support</span>
                    <span class="metadata-value">
                        <span class="badge badge-custom <?= $GLOBALS['metadata']['computed']['is_mobile_compatible'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $GLOBALS['metadata']['computed']['is_mobile_compatible'] ? 'Yes' : 'No' ?>
                        </span>
                    </span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Assessment Integration</span>
                    <span class="metadata-value">
                        <span class="badge badge-custom <?= $GLOBALS['metadata']['computed']['has_assessment'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $GLOBALS['metadata']['computed']['has_assessment'] ? 'Yes' : 'No' ?>
                        </span>
                    </span>
                </div>
                <div class="metadata-item">
                    <span class="metadata-label">Progress Tracking</span>
                    <span class="metadata-value">
                        <span class="badge badge-custom bg-success">Enabled</span>
                    </span>
                </div>
                
                <?php if (!empty($GLOBALS['validation']['requirements'])): ?>
                    <div class="metadata-item">
                        <span class="metadata-label">Device Requirements</span>
                        <span class="metadata-value">
                            <?php foreach ($GLOBALS['validation']['requirements'] as $requirement): ?>
                                <div class="small">• <?= htmlspecialchars($requirement) ?></div>
                            <?php endforeach; ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Requirements Validation -->
            <?php if (!empty($GLOBALS['validation']['requirements'])): ?>
                <div class="metadata-section">
                    <h6><i class="fas fa-check-circle me-2"></i>Requirements Status</h6>
                    <?php foreach ($GLOBALS['validation']['requirements'] as $requirement): ?>
                        <div class="requirement-item">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <?= htmlspecialchars($requirement) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <div class="content-frame-container">
                <!-- Time Limit Indicator -->
                <?php if ($GLOBALS['metadata']['computed']['has_time_limit']): ?>
                    <div class="time-limit-indicator" id="timeLimitIndicator">
                        <i class="fas fa-clock me-2"></i>
                        <span id="timeDisplay">Loading...</span>
                    </div>
                <?php endif; ?>


                <!-- Content Frame -->
                <?php if ($GLOBALS['validation']['valid'] && !empty($GLOBALS['launch_config']['launch_url'])): ?>
                    <iframe 
                        class="content-frame" 
                        src="<?= htmlspecialchars($GLOBALS['launch_config']['launch_url']) ?>"
                        allow="fullscreen; microphone; camera; geolocation; autoplay"
                        referrerpolicy="no-referrer-when-downgrade"
                        onload="handleContentLoad()"
                        onerror="handleContentError()">
                    </iframe>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h4>Content Cannot Be Loaded</h4>
                            <p class="text-muted"><?= htmlspecialchars($GLOBALS['validation']['error'] ?? 'Unknown error occurred') ?></p>
                            <button class="btn btn-primary" onclick="window.close()">Close</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        const contentId = <?= json_encode($GLOBALS['content_id']) ?>;
        const courseId = <?= json_encode($GLOBALS['course_id']) ?>;
        const moduleId = <?= json_encode($GLOBALS['module_id']) ?>;
        const hasTimeLimit = <?= json_encode($GLOBALS['metadata']['computed']['has_time_limit']) ?>;
        const timeLimit = <?= json_encode($GLOBALS['metadata']['learning_design']['time_limit']) ?>;
        const tutorPersonality = <?= json_encode($GLOBALS['metadata']['ai_configuration']['tutor_personality']) ?>;
        const adaptationAlgorithm = <?= json_encode($GLOBALS['metadata']['ai_configuration']['adaptation_algorithm']) ?>;

        // Enhanced progress tracking variables
        let currentStep = 1;
        let totalSteps = 10; // Default, will be updated based on content
        let completionPercentage = 0;
        let isCompleted = false;
        let startTime = Date.now();
        let lastInteractionTime = Date.now();
        let interactionHistory = [];
        let userResponses = [];
        let aiFeedback = [];
        let currentProgress = {
            current_step: currentStep,
            total_steps: totalSteps,
            completion_percentage: completionPercentage,
            is_completed: isCompleted,
            time_spent: 0,
            status: 'in_progress',
            last_interaction_at: new Date().toISOString()
        };

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeInteractiveContent();
        });

        function initializeInteractiveContent() {
            console.log('🤖 Initializing Interactive AI Content:', {
                contentId: contentId,
                courseId: courseId,
                moduleId: moduleId,
                hasTimeLimit: hasTimeLimit,
                timeLimit: timeLimit,
                tutorPersonality: tutorPersonality,
                adaptationAlgorithm: adaptationAlgorithm
            });

            // Start time limit tracking if applicable
            if (hasTimeLimit && timeLimit) {
                startTimeLimitTracking();
            }

            // Start progress tracking
            startProgressTracking();

            // Apply AI personality customization
            applyAIPersonalityCustomization();

            // Start adaptation algorithm monitoring
            startAdaptationAlgorithmMonitoring();
        }

        function startTimeLimitTracking() {
            let startTime = sessionStorage.getItem(`interactive_start_time_${contentId}`);
            if (!startTime) {
                startTime = Date.now();
                sessionStorage.setItem(`interactive_start_time_${contentId}`, startTime);
            }

            const timeLimitMs = timeLimit * 60 * 1000;
            
            function updateTimeDisplay() {
                const elapsed = Date.now() - parseInt(startTime);
                const remaining = Math.max(0, timeLimitMs - elapsed);
                
                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                
                const timeDisplay = document.getElementById('timeDisplay');
                if (timeDisplay) {
                    timeDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')} remaining`;
                    
                    if (remaining <= 0) {
                        timeDisplay.textContent = 'Time Expired!';
                        document.getElementById('timeLimitIndicator').classList.add('time-expired');
                        handleTimeExpired();
                    }
                }
            }

            updateTimeDisplay();
            setInterval(updateTimeDisplay, 1000);
        }

        function startProgressTracking() {
            // Initialize start time
            startTime = Date.now();
            sessionStorage.setItem(`interactive_start_time_${contentId}`, startTime);
            
            // Enhanced progress tracking with detailed interaction data
            setInterval(() => {
                updateProgress();
            }, 5000); // Update every 5 seconds
            
            // Track user interactions for detailed analytics
            trackUserInteractions();
        }

        function updateProgress() {
            // Calculate actual time spent
            const timeSpent = Math.floor((Date.now() - startTime) / 1000); // in seconds
            
            // Update current progress object
            currentProgress.time_spent = timeSpent;
            currentProgress.last_interaction_at = new Date().toISOString();
            currentProgress.current_step = currentStep;
            currentProgress.total_steps = totalSteps;
            currentProgress.completion_percentage = completionPercentage;
            currentProgress.is_completed = isCompleted;
            currentProgress.status = isCompleted ? 'completed' : (completionPercentage > 0 ? 'in_progress' : 'started');


            // Prepare comprehensive progress data
            const progressData = {
                current_step: currentStep,
                total_steps: totalSteps,
                completion_percentage: completionPercentage,
                is_completed: isCompleted,
                time_spent: timeSpent,
                status: currentProgress.status,
                last_interaction_at: currentProgress.last_interaction_at
            };

            // Prepare detailed interaction data
            const interactionData = {
                interaction_history: interactionHistory,
                user_responses: userResponses,
                ai_feedback: aiFeedback,
                tutor_personality: tutorPersonality,
                adaptation_algorithm: adaptationAlgorithm,
                total_interactions: interactionHistory.length,
                avg_interaction_time: calculateAverageInteractionTime(),
                learning_pattern: analyzeLearningPattern(),
                timestamp: Date.now()
            };

            // Send comprehensive progress update to server
            fetch('/Unlockyourskills/api/interactive-ai/progress', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    content_id: contentId,
                    course_id: courseId,
                    module_id: moduleId,
                    progress_data: progressData,
                    interaction_data: interactionData
                })
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('✅ Progress updated successfully:', data);
                } else {
                    console.warn('⚠️ Progress update failed:', data.message);
                }
            }).catch(error => {
                console.warn('❌ Progress update failed:', error);
            });
        }

        function trackUserInteractions() {
            // Track clicks
            document.addEventListener('click', (event) => {
                recordInteraction('click', event);
            });

            // Track keyboard input
            document.addEventListener('keypress', (event) => {
                recordInteraction('keypress', event);
            });

            // Track form submissions
            document.addEventListener('submit', (event) => {
                recordInteraction('submit', event);
                if (event.target.tagName === 'FORM') {
                    captureUserResponse(event.target);
                }
            });

            // Track scroll events
            let scrollTimeout;
            document.addEventListener('scroll', (event) => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    recordInteraction('scroll', event);
                }, 500); // Debounce scroll events
            });

            // Track focus events
            document.addEventListener('focusin', (event) => {
                recordInteraction('focus', event);
            });

            // Track iframe interactions (for embedded content)
            const iframe = document.querySelector('iframe');
            if (iframe) {
                iframe.addEventListener('load', () => {
                    recordInteraction('iframe_loaded', { target: 'iframe' });
                });
            }
        }

        function recordInteraction(type, event) {
            const interaction = {
                type: type,
                timestamp: Date.now(),
                target: event.target ? event.target.tagName : 'unknown',
                target_id: event.target ? event.target.id : null,
                target_class: event.target ? event.target.className : null,
                x: event.clientX || 0,
                y: event.clientY || 0,
                value: event.target ? event.target.value : null,
                step: currentStep,
                completion_percentage: completionPercentage
            };

            interactionHistory.push(interaction);
            lastInteractionTime = Date.now();
            
            // Limit interaction history to last 100 interactions
            if (interactionHistory.length > 100) {
                interactionHistory = interactionHistory.slice(-100);
            }

            console.log('🔄 Interaction recorded:', interaction);
        }

        function captureUserResponse(formElement) {
            const formData = new FormData(formElement);
            const response = {
                timestamp: Date.now(),
                step: currentStep,
                form_data: Object.fromEntries(formData),
                response_type: 'form_submission'
            };

            userResponses.push(response);
            console.log('📝 User response captured:', response);
        }

        function addUserResponse(question, answer, responseType = 'manual') {
            const response = {
                timestamp: Date.now(),
                step: currentStep,
                question: question,
                answer: answer,
                response_type: responseType
            };

            userResponses.push(response);
            console.log('📝 User response added:', response);
        }

        function addAIFeedback(feedback, feedbackType = 'general') {
            const aiResponse = {
                timestamp: Date.now(),
                step: currentStep,
                feedback: feedback,
                feedback_type: feedbackType,
                tutor_personality: tutorPersonality
            };

            aiFeedback.push(aiResponse);
            console.log('🤖 AI feedback added:', aiResponse);
        }

        function updateStep(step, total = null) {
            currentStep = step;
            if (total) {
                totalSteps = total;
            }
            completionPercentage = Math.floor((currentStep / totalSteps) * 100);
            
            // Record step change interaction
            recordInteraction('step_change', {
                target: { tagName: 'STEP_CHANGE' },
                step: currentStep,
                total_steps: totalSteps
            });
            
            console.log(`📊 Step updated: ${currentStep}/${totalSteps} (${completionPercentage}%)`);
        }

        function markCompleted() {
            isCompleted = true;
            completionPercentage = 100;
            currentStep = totalSteps;
            
            // Record completion interaction
            recordInteraction('completion', {
                target: { tagName: 'COMPLETION' }
            });
            
            console.log('🎉 Content marked as completed!');
        }

        function calculateAverageInteractionTime() {
            if (interactionHistory.length < 2) return 0;
            
            const intervals = [];
            for (let i = 1; i < interactionHistory.length; i++) {
                intervals.push(interactionHistory[i].timestamp - interactionHistory[i-1].timestamp);
            }
            
            return intervals.reduce((sum, interval) => sum + interval, 0) / intervals.length;
        }

        function analyzeLearningPattern() {
            const pattern = {
                total_interactions: interactionHistory.length,
                click_ratio: interactionHistory.filter(i => i.type === 'click').length / interactionHistory.length,
                scroll_frequency: interactionHistory.filter(i => i.type === 'scroll').length,
                form_interactions: interactionHistory.filter(i => i.type === 'submit').length,
                engagement_level: 'medium' // Default
            };

            // Determine engagement level
            if (pattern.total_interactions > 50) {
                pattern.engagement_level = 'high';
            } else if (pattern.total_interactions < 10) {
                pattern.engagement_level = 'low';
            }

            return pattern;
        }

        function applyAIPersonalityCustomization() {
            if (!tutorPersonality) return;

            // Apply personality-based UI customization
            const personality = tutorPersonality.toLowerCase();
            
            if (personality.includes('encouraging') || personality.includes('mentor')) {
                document.body.style.setProperty('--primary-color', '#28a745');
                console.log('🎯 Applied encouraging mentor personality styling');
            } else if (personality.includes('patient') || personality.includes('teacher')) {
                document.body.style.setProperty('--primary-color', '#17a2b8');
                console.log('🎯 Applied patient teacher personality styling');
            } else if (personality.includes('scientific')) {
                document.body.style.setProperty('--primary-color', '#6f42c1');
                console.log('🎯 Applied scientific mentor personality styling');
            } else if (personality.includes('native') || personality.includes('guide')) {
                document.body.style.setProperty('--primary-color', '#fd7e14');
                console.log('🎯 Applied native speaker guide personality styling');
            }
        }

        function startAdaptationAlgorithmMonitoring() {
            if (!adaptationAlgorithm) return;

            console.log('🧠 Starting adaptation algorithm monitoring:', adaptationAlgorithm);
            
            // Monitor user interactions for adaptation
            document.addEventListener('click', handleUserInteraction);
            document.addEventListener('keypress', handleUserInteraction);
            document.addEventListener('scroll', handleUserInteraction);
        }

        function handleUserInteraction(event) {
            // Record interaction using the enhanced tracking system
            recordInteraction(event.type, event);
            
            // Send interaction data for adaptation algorithm
            const interactionData = {
                type: event.type,
                timestamp: Date.now(),
                target: event.target.tagName,
                adaptation_algorithm: adaptationAlgorithm,
                current_step: currentStep,
                completion_percentage: completionPercentage
            };

            console.log('🔄 User interaction for adaptation:', interactionData);
            
            // Trigger immediate progress update for critical interactions
            if (['submit', 'step_change', 'completion'].includes(event.type)) {
                setTimeout(() => {
                    updateProgress();
                }, 1000);
            }
        }

        function handleContentLoad() {
            console.log('✅ Interactive AI content loaded successfully');
            
            // Notify server that content has loaded
            fetch('/Unlockyourskills/api/interactive-ai/loaded', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    content_id: contentId,
                    course_id: courseId,
                    module_id: moduleId,
                    loaded_at: Date.now()
                })
            }).catch(error => {
                console.warn('Content loaded notification failed:', error);
            });
        }

        function handleContentError() {
            console.error('❌ Interactive AI content failed to load');
            
            // Show error message
            const container = document.querySelector('.content-frame-container');
            container.innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                        <h4>Content Load Error</h4>
                        <p class="text-muted">The interactive AI content failed to load. Please try again.</p>
                        <button class="btn btn-primary" onclick="window.location.reload()">Retry</button>
                    </div>
                </div>
            `;
        }

        function handleTimeExpired() {
            console.log('⏰ Time limit expired for interactive AI content');
            
            // Notify server about time expiration
            fetch('/Unlockyourskills/api/interactive-ai/time-expired', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    content_id: contentId,
                    course_id: courseId,
                    module_id: moduleId,
                    expired_at: Date.now()
                })
            }).catch(error => {
                console.warn('Time expired notification failed:', error);
            });

            // Show time expired message
            alert('Time limit has been reached for this interactive AI content. Your progress has been saved.');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
        }

        function toggleSection(sectionId) {
            const content = document.getElementById(sectionId);
            const icon = content.previousElementSibling.querySelector('.fa-chevron-down');
            
            if (content.classList.contains('show')) {
                content.classList.remove('show');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                content.classList.add('show');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }

        // Global functions that can be called from embedded content
        window.interactiveAIProgress = {
            // Update current step
            updateStep: function(step, total = null) {
                updateStep(step, total);
            },
            
            // Mark content as completed
            markCompleted: function() {
                markCompleted();
            },
            
            // Add user response
            addUserResponse: function(question, answer, responseType = 'manual') {
                addUserResponse(question, answer, responseType);
            },
            
            // Add AI feedback
            addAIFeedback: function(feedback, feedbackType = 'general') {
                addAIFeedback(feedback, feedbackType);
            },
            
            // Get current progress
            getCurrentProgress: function() {
                return currentProgress;
            },
            
            // Get interaction history
            getInteractionHistory: function() {
                return interactionHistory;
            },
            
            // Get user responses
            getUserResponses: function() {
                return userResponses;
            },
            
            // Get AI feedback
            getAIFeedback: function() {
                return aiFeedback;
            },
            
            // Force progress update
            forceUpdate: function() {
                updateProgress();
            }
        };

        // Expose progress tracking functions globally for embedded content
        window.updateInteractiveAIStep = updateStep;
        window.markInteractiveAICompleted = markCompleted;
        window.addInteractiveAIUserResponse = addUserResponse;
        window.addInteractiveAIFeedback = addAIFeedback;
        window.getInteractiveAIProgress = function() { return currentProgress; };
        window.forceInteractiveAIUpdate = updateProgress;

        console.log('🚀 Interactive AI Progress Tracking System Initialized');
        console.log('📋 Available functions:', Object.keys(window.interactiveAIProgress));

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            // Save final progress
            updateProgress();
        });
    </script>
</body>
</html>
