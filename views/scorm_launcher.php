<?php
/**
 * SCORM Launcher View
 * Provides a professional SCORM content player with progress tracking integration
 */

// Start session to maintain authentication in new window
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required classes
require_once __DIR__ . '/../core/UrlHelper.php';

// Get content details from controller data or URL parameters as fallback
$courseId = $launcherData['course_id'] ?? $_GET['course_id'] ?? null;
$moduleId = $launcherData['module_id'] ?? $_GET['module_id'] ?? null;
$contentId = $launcherData['content_id'] ?? $_GET['content_id'] ?? null;
$scormUrl = $launcherData['scorm_url'] ?? $_GET['scorm_url'] ?? null;
$title = $launcherData['title'] ?? $_GET['title'] ?? 'SCORM Content';

// Validate required parameters (module_id is optional for prerequisites)
if (!$courseId || !$contentId || !$scormUrl) {
    die('Missing required parameters for SCORM content. Received: ' . json_encode([
        'course_id' => $courseId,
        'module_id' => $moduleId,
        'content_id' => $contentId,
        'scorm_url' => $scormUrl,
        'launcherData' => $launcherData ?? 'not set'
    ]));
}

// For prerequisites, module_id might not be provided - use a fallback
if (!$moduleId) {
    $moduleId = 'prerequisite_' . $contentId;
}

// Get user information from session (session already started by main application)
$userId = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
$clientId = $_SESSION['user']['client_id'] ?? $_SESSION['client_id'] ?? null;

if (!$userId || !$clientId) {
    die('User not authenticated. Session data: ' . json_encode($_SESSION));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - SCORM Player</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom SCORM Player CSS -->
    <link href="/Unlockyourskills/public/css/scorm-player.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6a0dad;
            --secondary-color: #f8f9fa;
            --border-color: #e5d6ff;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
        }
        
        .scorm-main-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
            width: 100%;
            overflow-y: auto;
        }
        
        .scorm-header {
            background: linear-gradient(135deg, var(--primary-color), #8a2be2);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1000;
        }
        
        .scorm-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .scorm-subtitle {
            font-size: 14px;
            opacity: 0.9;
            margin: 5px 0 0 0;
        }
        
        .scorm-controls {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .control-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .control-group label {
            font-weight: 500;
            color: #495057;
            margin: 0;
            font-size: 14px;
        }
        
        .btn-scorm {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 120px;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
            line-height: 1.2;
        }
        
        .btn-scorm:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a0b9d;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .btn-info {
            background: var(--info-color);
            color: white;
        }
        
        .btn-info:hover {
            background: #138496;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
        }
        
        .scorm-content {
            flex: 1;
            position: relative;
            background: white;
            border-radius: 8px;
            margin: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .scorm-iframe {
            width: 100%;
            height: 100%;
            border: none;
            background: white;
            flex: 1;
            min-height: 0;
            border-radius: 8px;
            display: block;
        }
        
        /* Ensure iframe takes full available space */
        .scorm-content iframe {
            width: 100% !important;
            height: 100% !important;
            min-height: 0 !important;
            flex: 1 !important;
        }
        
        .scorm-status {
            background: white;
            border-top: 1px solid var(--border-color);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .status-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .status-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .progress-container {
            flex: 1;
            max-width: 300px;
        }
        
        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #8a2be2);
            transition: width 0.3s ease;
            border-radius: 4px;
        }
        
        .scorm-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            padding: 20px;
            overflow-y: auto;
        }
        
        .scorm-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .overlay-content {
            background: white;
            border-radius: 12px;
            padding: 30px 30px 40px 30px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .overlay-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .overlay-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .overlay-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .overlay-note {
            font-size: 14px;
            color: #888;
            margin-bottom: 25px;
            line-height: 1.4;
            font-style: italic;
        }
        
        .overlay-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
        }
        
        .overlay-actions .btn-scorm {
            min-width: 140px;
            margin: 4px;
            flex-shrink: 0;
            text-align: center;
            word-wrap: normal;
            overflow-wrap: normal;
        }
        
        /* Mobile responsiveness for overlay */
        @media (max-width: 768px) {
            .overlay-content {
                max-width: 90vw;
                max-height: 85vh;
                padding: 20px;
                margin: 20px;
            }
            
            .overlay-actions {
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }
            
            .overlay-actions .btn-scorm {
                min-width: 200px;
                width: 100%;
                max-width: 300px;
            }
        }
        

        
        /* Resume Progress Summary Styling */
        .resume-progress-summary {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        
        .progress-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .progress-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            border-left: 3px solid #007bff;
        }
        
        .progress-item i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        
        .progress-item span {
            font-size: 14px;
            color: #495057;
        }
        
        .progress-item strong {
            color: #212529;
        }
        
        /* Ensure button text doesn't overflow */
        .btn-scorm i {
            flex-shrink: 0;
            margin-right: 4px;
        }
        
        .btn-scorm span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            max-width: 100%;
        }
        
        /* Button hover effects */
        .btn-scorm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* Button focus states */
        .btn-scorm:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }
        
        /* Toast container stacks notifications vertically */
        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 10000;
            pointer-events: none;
        }
        
        .notification {
            position: relative;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            pointer-events: auto;
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
            min-width: 260px;
            max-width: 360px;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification.success {
            background: var(--success-color);
        }
        
        .notification.error {
            background: var(--danger-color);
        }
        
        .notification.warning {
            background: var(--warning-color);
            color: #212529;
        }
        
        .notification.info {
            background: var(--info-color);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .debug-panel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
            display: none;
        }
        
        .debug-panel.show {
            display: block;
        }
        
        .debug-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .debug-toggle:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .scorm-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .control-group {
                justify-content: space-between;
            }
            
            .scorm-status {
                flex-direction: column;
                align-items: stretch;
            }
            
            .progress-container {
                max-width: none;
            }
            
            .overlay-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .overlay-actions .btn-scorm {
                min-width: 200px;
                width: 100%;
                max-width: 300px;
            }
            
            .btn-scorm {
                min-width: 100px;
                padding: 12px 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Main Container -->
    <div class="scorm-main-container">
        <!-- SCORM Header -->
        <div class="scorm-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="scorm-title"><?= htmlspecialchars($title) ?></h1>
                <p class="scorm-subtitle">Course ID: <?= htmlspecialchars($courseId) ?> | Module ID: <?= htmlspecialchars($moduleId) ?> | Content ID: <?= htmlspecialchars($contentId) ?></p>
            </div>
            <div>
                            <button class="btn-scorm btn-secondary" onclick="closeSCORM()">
                <i class="fas fa-times"></i> <span>Close</span>
            </button>
            </div>
        </div>
    </div>

    <!-- SCORM Controls -->
    <div class="scorm-controls">
        <div class="control-group">
            <label>Status:</label>
            <span id="status-display" class="status-value">Initializing...</span>
        </div>
        
        <div class="control-group">
            <button class="btn-scorm btn-primary" id="btn-save" onclick="saveProgress()" disabled>
                <i class="fas fa-save"></i> <span>Save Progress</span>
            </button>
            <button class="btn-scorm btn-success" id="btn-complete" onclick="markComplete()" disabled>
                <i class="fas fa-check"></i> <span>Mark Complete</span>
            </button>
        </div>
        
        <div class="control-group">
            <button class="btn-scorm btn-info" onclick="toggleDebug()">
                <i class="fas fa-bug"></i> <span>Debug</span>
            </button>
            <button class="btn-scorm btn-warning" onclick="showResumeOptions()">
                <i class="fas fa-play"></i> <span>Resume Options</span>
            </button>
        </div>
        
        <div class="control-group">
            <label>Auto-save:</label>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="auto-save-toggle" checked>
                <label class="form-check-label" for="auto-save-toggle">Enabled</label>
            </div>
        </div>
    </div>

    <!-- SCORM Content Container -->
    <div class="scorm-content">
        <iframe 
            id="scorm-iframe" 
            class="scorm-iframe" 
            src="<?= htmlspecialchars($scormUrl) ?>"
            allow="fullscreen *; geolocation *; microphone *; camera *"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
        
        <!-- Loading Overlay -->
        <div class="scorm-overlay" id="loading-overlay">
            <div class="overlay-content">
                <div class="overlay-icon">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <h3 class="overlay-title">Loading SCORM Content</h3>
                <p class="overlay-message">Please wait while the content loads...</p>
            </div>
        </div>
        
        <!-- Resume Overlay -->
        <div class="scorm-overlay" id="resume-overlay">
            <div class="overlay-content">
                <div class="overlay-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <h3 class="overlay-title">Resume Learning</h3>
                <p class="overlay-message">You have previous progress for this content. Would you like to resume from where you left off?</p>
                
                <!-- Resume Progress Summary -->
                <div class="resume-progress-summary" id="resume-progress-summary">
                    <div class="progress-info">
                        <div class="progress-item">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                            <span><strong>Current Position:</strong> <span id="current-position-display">Loading...</span></span>
                        </div>
                        <div class="progress-item">
                            <i class="fas fa-check-circle text-success"></i>
                            <span><strong>Completed Slides:</strong> <span id="completed-slides-display">Loading...</span></span>
                        </div>
                        <div class="progress-item">
                            <i class="fas fa-clock text-warning"></i>
                            <span><strong>Session Time:</strong> <span id="session-time-display">Loading...</span></span>
                        </div>
                    </div>
                </div>
                
                <p class="overlay-note"><small><i class="fas fa-info-circle"></i> Note: Some SCORM packages may not support automatic resume. If automatic resume fails, use "Quick Jump" or navigate manually.</small></p>
                <div class="overlay-actions">
                    <button class="btn-scorm btn-primary" onclick="resumeContent()">
                        <i class="fas fa-play"></i> <span>Resume</span>
                    </button>
                    <button class="btn-scorm btn-success" onclick="quickJumpToLocation()">
                        <i class="fas fa-rocket"></i> <span>Quick Jump</span>
                    </button>
                    <button class="btn-scorm btn-info" onclick="manualResume()">
                        <i class="fas fa-hand-point-up"></i> <span>Manual Resume</span>
                    </button>
                    <button class="btn-scorm btn-secondary" onclick="startOver()">
                        <i class="fas fa-redo"></i> <span>Start Over</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCORM Status Bar -->
    <div class="scorm-status">
        <div class="status-item">
            <i class="fas fa-clock"></i>
            <span>Session Time: <span id="session-time" class="status-value">00:00:00</span></span>
        </div>
        
        <div class="status-item">
            <i class="fas fa-trophy"></i>
            <span>Score: <span id="score-display" class="status-value">--</span></span>
        </div>
        
        <div class="status-item">
            <i class="fas fa-map-marker-alt"></i>
            <span>Location: <span id="location-display" class="status-value">--</span></span>
        </div>
        
        <div class="progress-container">
            <div class="status-item">
                <i class="fas fa-chart-line"></i>
                <span>Progress: <span id="progress-text" class="status-value">0%</span></span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
            </div>
        </div>
        
        <div class="status-item">
            <i class="fas fa-sync-alt"></i>
            <span>Last Save: <span id="last-save" class="status-value">Never</span></span>
        </div>
    </div>

    <!-- Debug Panel -->
    <div class="debug-panel" id="debug-panel">
        <h6>SCORM Debug Information</h6>
        <div id="debug-content"></div>
        <div class="debug-actions mt-2">
            <button class="btn btn-sm btn-info" onclick="debugCacheState()" title="Show Cache State">
                <i class="fas fa-database"></i> Cache State
            </button>
            <button class="btn btn-sm btn-warning" onclick="testSuspendDataCapture()" title="Test Suspend Data Capture">
                <i class="fas fa-flask"></i> Test Capture
            </button>
        </div>
    </div>

    <!-- Debug Toggle Button -->
    <button class="debug-toggle" onclick="toggleDebug()" title="Toggle Debug Panel">
        <i class="fas fa-bug"></i>
    </button>

    <!-- Notification Container -->
    <div id="notification-container"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Our new SCORM system is self-contained -->
    <!-- No external JavaScript dependencies needed -->

    <script>
        // SCORM Launcher Configuration
        const SCORM_CONFIG = {
            courseId: <?= json_encode($courseId) ?>,
            moduleId: <?= json_encode($moduleId) ?>,
            contentId: <?= json_encode($contentId) ?>,
            userId: <?= json_encode($userId) ?>,
            clientId: <?= json_encode($clientId) ?>,
            scormUrl: <?= json_encode($scormUrl) ?>,
            title: <?= json_encode($title) ?>,
            contentType: <?= json_encode($launcherData['content_type'] ?? 'module') ?>,
            prerequisiteId: <?= json_encode($launcherData['prerequisite_id'] ?? null) ?>,
            scormPackageId: <?= json_encode($launcherData['scorm_package_id'] ?? null) ?>
        };

        // Global variables
        // We don't need these old managers anymore
        // Our new system works directly with the iframe
        let scormWrapper = null;
        // We don't need the old progress tracker anymore
        let autoSaveInterval = null;
        let sessionStartTime = Date.now();
        let lastSaveTime = null;
        let isInitialized = false;
        let quickSaveTimer = null;
        // Track accumulated session time to prevent reset
        let accumulatedSessionTime = 0;
        let lastSessionTimeUpdate = Date.now();
        // Minimal SCORM API shim to satisfy packages expecting API/API_1484_11
        function installScormAPIShim() {
            try {
                // Avoid re-install
                if (window.__scormApiShimInstalled) return;
                const lmsState = {
                    initialized: false,
                    finished: false,
                    data: {}
                };

                function logShim(msg) { try { console.log('[SCORM API SHIM]', msg); } catch(e) {} }

                function updateCacheFromSetValue(element, value) {
                    try {
                        if (!window.scormDataCache) return;
                        if (element === 'cmi.core.lesson_location' || element === 'cmi.location') {
                            window.scormDataCache.lesson_location = value;
                            window.scormDataCache.lastUpdate = Date.now();
                            scheduleQuickSave(800);
                            // Update progress bar when location changes
                            updateProgressFromCache();
                        } else if (element === 'cmi.suspend_data') {
                            window.scormDataCache.suspend_data = value;
                            window.scormDataCache.lastUpdate = Date.now();
                            scheduleQuickSave(800);
                            // Update progress bar when suspend data changes
                            updateProgressFromCache();
                        } else if (element === 'cmi.core.session_time' || element === 'cmi.session_time') {
                            window.scormDataCache.session_time = value;
                            window.scormDataCache.lastUpdate = Date.now();
                            // Update session time display
                            updateSessionTimeDisplay(value);
                            // Accumulate session time
                            accumulateSessionTime(value);
                        } else if (element === 'cmi.core.lesson_status' || element === 'cmi.completion_status') {
                            // Mirror into our extraction result via quick save
                            scheduleQuickSave(1000);
                        }
                    } catch (e) { /* noop */ }
                }

                // SCORM 1.2 API
                const API = {
                    LMSInitialize: function() { lmsState.initialized = true; logShim('LMSInitialize'); return 'true'; },
                    LMSFinish: function() { lmsState.finished = true; logShim('LMSFinish'); return 'true'; },
                    LMSGetValue: function(element) { const v = lmsState.data[element] ?? ''; logShim('LMSGetValue ' + element + '=' + v); return v; },
                    LMSSetValue: function(element, value) { lmsState.data[element] = value; logShim('LMSSetValue ' + element + '=' + value); updateCacheFromSetValue(element, value); return 'true'; },
                    LMSCommit: function() { logShim('LMSCommit'); try { saveProgress(true); } catch(e) {} return 'true'; },
                    LMSGetLastError: function() { return '0'; },
                    LMSGetErrorString: function() { return 'No error'; },
                    LMSGetDiagnostic: function() { return ''; }
                };

                // SCORM 2004 API
                const API_1484_11 = {
                    Initialize: function() { lmsState.initialized = true; logShim('Initialize'); return 'true'; },
                    Terminate: function() { lmsState.finished = true; logShim('Terminate'); return 'true'; },
                    GetValue: function(element) { const v = lmsState.data[element] ?? ''; logShim('GetValue ' + element + '=' + v); return v; },
                    SetValue: function(element, value) { lmsState.data[element] = value; logShim('SetValue ' + element + '=' + value); updateCacheFromSetValue(element, value); return 'true'; },
                    Commit: function() { logShim('Commit'); try { saveProgress(true); } catch(e) {} return 'true'; },
                    GetLastError: function() { return '0'; },
                    GetErrorString: function() { return 'No error'; },
                    GetDiagnostic: function() { return ''; }
                };

                // Expose on parent and window for discovery
                window.API = window.API || API;
                window.API_1484_11 = window.API_1484_11 || API_1484_11;
                // Some content searches up parent chain
                try { if (window.parent && window.parent !== window) { window.parent.API = window.parent.API || API; window.parent.API_1484_11 = window.parent.API_1484_11 || API_1484_11; } } catch(e) {}

                window.__scormApiShimInstalled = true;
                console.log('SCORM API shim installed');
            } catch (e) {
                console.error('Failed to install SCORM API shim', e);
            }
        }

        function scheduleQuickSave(delayMs = 1000) {
            try {
                if (window.contentCompleted) return;
                if (quickSaveTimer) {
                    clearTimeout(quickSaveTimer);
                }
                quickSaveTimer = setTimeout(() => {
                    // Silent save to avoid UI noise
                    saveProgress(true);
                }, delayMs);
            } catch (e) {
                console.error('Error scheduling quick save:', e);
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('SCORM Launcher initializing...');
            // Install SCORM API shim early so content can find it during load
            installScormAPIShim();
            initializeSCORM();
        });

        // Initialize SCORM system
        async function initializeSCORM() {
            try {
                showLoadingOverlay();
                updateStatus('Loading SCORM content...');
                
                // Wait for dependencies to load
                await waitForDependencies();
                
                // Initialize progress tracker
                await initializeProgressTracker();
                
                // Initialize SCORM integration
                await initializeSCORMIntegration();
                
                // Wait for iframe to be ready and initialize SCORM wrapper
                await waitForIframeAndInitialize();
                
                // Check for resume data
                await checkResumeData();
                
                // Setup auto-save
                setupAutoSave();
                
                // Setup event listeners
                setupEventListeners();
                
                // Setup periodic time update
                setupTimeTracking();
                
                isInitialized = true;
                hideLoadingOverlay();
                
                updateStatus('Ready');
                showNotification('SCORM content loaded successfully!', 'success');
                
            } catch (error) {
                console.error('Failed to initialize SCORM:', error);
                updateStatus('Initialization Failed');
                showNotification('Failed to initialize SCORM: ' + error.message, 'error');
                hideLoadingOverlay();
            }
        }

        // Wait for required dependencies
        function waitForDependencies() {
            return new Promise((resolve) => {
                // Our new system doesn't need external dependencies
                // Just resolve immediately
                resolve();
            });
        }

        // Wait for iframe to be ready and initialize SCORM wrapper
        async function waitForIframeAndInitialize() {
            try {
                updateStatus('Waiting for SCORM content to load...');
                
                const iframe = document.getElementById('scorm-iframe');
                if (!iframe) {
                    throw new Error('SCORM iframe not found');
                }
                
                // Wait for iframe to be fully loaded and ready
                await waitForIframeReady(iframe);
                
                updateStatus('Initializing SCORM bridge...');
                
                // Create a bridge to capture SCORM data
                createSCORMBridge(iframe.contentWindow);
                // Also expose API inside iframe window for content discovery
                try {
                    if (iframe.contentWindow) {
                        iframe.contentWindow.API = iframe.contentWindow.API || window.API;
                        iframe.contentWindow.API_1484_11 = iframe.contentWindow.API_1484_11 || window.API_1484_11;
                    }
                } catch(e) { /* ignore */ }
                
                // Mark as initialized since we're capturing data directly
                console.log('SCORM bridge created, marking as ready...');
                updateStatus('SCORM Ready (Bridge Mode)');
                showNotification('SCORM bridge established', 'success');
                
                // Enable save and complete buttons
                document.getElementById('btn-save').disabled = false;
                document.getElementById('btn-complete').disabled = false;
                
                // Update isInitialized flag
                isInitialized = true;
                
                // Check completion status after initialization
                await checkCompletionStatus();
                
            } catch (error) {
                console.error('Error in waitForIframeAndInitialize:', error);
                updateStatus('SCORM Bridge Failed');
                throw error;
            }
        }

        // Initialize progress tracker
        async function initializeProgressTracker() {
            try {
                updateStatus('Initializing progress tracking...');
                // Our new system doesn't need the old progress tracker
                console.log('Progress tracker not needed - using new system');
            } catch (error) {
                console.error('Error initializing progress tracker:', error);
            }
        }

        // We don't need the old progress tracker anymore
        // Our new system handles everything directly

        // Old SCORM wrapper function removed - using new waitForIframeAndInitialize instead
        
        // Wait for iframe to be ready
        function waitForIframeReady(iframe) {
            return new Promise((resolve) => {
                const maxWaitTime = 15000; // 15 seconds max wait
                const startTime = Date.now();
                
                const checkReady = () => {
                    try {
                        // Check if iframe is loaded and accessible
                        if (iframe.contentWindow && 
                            iframe.contentWindow.document && 
                            iframe.contentWindow.document.readyState === 'complete' &&
                            iframe.contentWindow.document.body) {
                            
                            // Additional check: wait for SCORM content to be present
                            if (iframe.contentWindow.document.querySelector('body') && 
                                iframe.contentWindow.document.body.innerHTML.length > 100) {
                                console.log('Iframe is ready with content');
                                resolve();
                                return;
                            }
                        }
                        
                        if (Date.now() - startTime > maxWaitTime) {
                            console.warn('Iframe ready timeout - proceeding anyway');
                            resolve();
                        } else {
                            setTimeout(checkReady, 200);
                        }
                        
                    } catch (error) {
                        // Iframe not accessible yet, wait
                        if (Date.now() - startTime > maxWaitTime) {
                            console.warn('Iframe access timeout - proceeding anyway');
                            resolve();
                        } else {
                            setTimeout(checkReady, 200);
                        }
                    }
                };
                checkReady();
            });
        }
        
        // Create SCORM bridge to capture data from debug interface
        function createSCORMBridge(iframeWindow) {
            try {
                if (!iframeWindow) {
                    console.error('Iframe window is null or undefined');
                    return;
                }
                
                // Store reference to iframe window
                window.scormIframeWindow = iframeWindow;
                
                // Create a console interceptor to capture SCORM data
                createConsoleInterceptor(iframeWindow);
                
                console.log('SCORM bridge created successfully');
                
            } catch (error) {
                console.error('Error creating SCORM bridge:', error);
            }
        }
        
        // Create console interceptor to capture SCORM data
        function createConsoleInterceptor(iframeWindow) {
            try {
                // Store SCORM data as it comes in
                window.scormDataCache = {
                    lesson_location: null,
                    suspend_data: null,
                    session_time: null,
                    progress_percent: 0,
                    lastUpdate: Date.now()
                };
                
                // Override console.log in iframe to capture SCORM data
                const originalLog = iframeWindow.console.log;
                iframeWindow.console.log = function(...args) {
                    // Call original console.log
                    originalLog.apply(iframeWindow.console, args);
                    
                    // Parse the log message for SCORM data
                    const logMessage = args.join(' ');
                    console.log('🔍 Console interceptor called with:', logMessage.substring(0, 200) + '...');
                    
                    // Look for location data - SCORM debug interface uses cmi.location=
                    const locationMatch = logMessage.match(/cmi\.location=([a-f0-9]+)(?:\s*\?|$)/);
                    if (locationMatch) {
                        const newLocation = locationMatch[1];
                        // Check if the new data is different from current cache (handling null values)
                        if (window.scormDataCache.lesson_location === null || newLocation !== window.scormDataCache.lesson_location) {
                            window.scormDataCache.lesson_location = newLocation;
                            window.scormDataCache.lastUpdate = Date.now();
                            console.log('🔄 Updated location:', newLocation, '(was:', window.scormDataCache.lesson_location, ')');
                            scheduleQuickSave(1200);
                            // Update progress bar when location changes
                            updateProgressFromCache();
                        }
                    }
                    
                    // Look for suspend data - capture full JSON up to ? Echo: or end of line
                    const suspendMatch = logMessage.match(/cmi\.suspend_data=({[\s\S]*?})(?:\s*\?|$)/);
                    if (suspendMatch) {
                        const newSuspendData = suspendMatch[1];
                        console.log('🔍 Suspend data match found:', newSuspendData);
                        console.log('🔍 Current cache suspend_data:', window.scormDataCache.suspend_data);
                        // Check if the new data is different from current cache (handling null values)
                        if (window.scormDataCache.suspend_data === null || newSuspendData !== window.scormDataCache.suspend_data) {
                            window.scormDataCache.suspend_data = newSuspendData;
                            window.scormDataCache.lastUpdate = Date.now();
                            console.log('🔄 Updated suspend data:', newSuspendData.substring(0, 100) + '...');
                            console.log('🔍 Cache after update:', window.scormDataCache.suspend_data);
                            scheduleQuickSave(1500);
                            // Update progress bar when suspend data changes
                            updateProgressFromCache();
                        } else {
                            console.log('🔍 Suspend data unchanged, skipping update');
                        }
                    } else {
                        console.log('🔍 No suspend data match found in:', logMessage);
                    }
                    
                    // Look for session time - SCORM debug interface uses cmi.session_time=
                    const sessionMatch = logMessage.match(/cmi\.session_time=([^?\s]+)(?:\s*\?|$)/);
                    if (sessionMatch) {
                        const newSessionTime = sessionMatch[1];
                        // Check if the new data is different from current cache (handling null values)
                        if (window.scormDataCache.session_time === null || newSessionTime !== window.scormDataCache.session_time) {
                            window.scormDataCache.session_time = newSessionTime;
                            window.scormDataCache.lastUpdate = Date.now();
                            console.log('🔄 Updated session time:', newSessionTime);
                            scheduleQuickSave(2000);
                        }
                    }

                    // Look for explicit progress text in content output
                    const overallMatch = logMessage.match(/Overall Result: Progress: (\d+)%/);
                    if (overallMatch) {
                        const percent = parseInt(overallMatch[1]);
                        if (!isNaN(percent)) {
                            window.scormDataCache.progress_percent = percent;
                            console.log('🔄 Progress detected from content:', percent + '%');
                            updateProgressBar(percent);
                        }
                    }

                    // Look for SCORM progress_measure (0..1)
                    const progressMeasureMatch = logMessage.match(/cmi\.progress_measure=([0-9.]+)/);
                    if (progressMeasureMatch) {
                        const ratio = parseFloat(progressMeasureMatch[1]);
                        if (!isNaN(ratio)) {
                            const percent = Math.max(0, Math.min(100, Math.round(ratio * 100)));
                            window.scormDataCache.progress_percent = percent;
                            console.log('🔄 Progress detected from cmi.progress_measure:', percent + '%');
                            updateProgressBar(percent);
                        }
                    }
                };
                
                console.log('Console interceptor created for SCORM data capture');
                
            } catch (error) {
                console.error('Error creating console interceptor:', error);
            }
        }
        
        // Get SCORM data directly from iframe
        function getSCORMDataFromIframe() {
            try {
                const iframeWindow = window.scormIframeWindow;
                if (!iframeWindow) {
                    console.error('Iframe window not available - waiting for initialization');
                    return null;
                }
                
                // Check if iframe is fully loaded
                if (iframeWindow.document.readyState !== 'complete') {
                    console.log('Iframe document not fully loaded yet');
                    return null;
                }
                
                // Since the SCORM content is using a debug interface, we need to capture
                // the data that's being logged to the console. Let me create a better approach.
                
                // Look for SCORM data in the page
                let scormData = {
                    lesson_status: 'incomplete',
                    lesson_location: '',
                    suspend_data: '',
                    score_raw: 0,
                    score_min: 0,
                    score_max: 100,
                    session_time: 'PT0S'
                };
                
                // Try to get data from the iframe's console logs or page content
                const iframeDoc = iframeWindow.document;
                const pageText = iframeDoc.body ? iframeDoc.body.innerText : '';
                
                // Look for progress information
                const progressMatch = pageText.match(/Overall Result: Progress: (\d+)%/);
                if (progressMatch) {
                    const progressPercent = parseInt(progressMatch[1]);
                    if (progressPercent >= 100) {
                        scormData.lesson_status = 'completed';
                    } else if (progressPercent > 0) {
                        scormData.lesson_status = 'incomplete';
                    } else {
                        scormData.lesson_status = 'not attempted';
                    }
                }
                
                // Look for score information
                const scoreMatch = pageText.match(/Score: (\d+)%/);
                if (scoreMatch) {
                    scormData.score_raw = parseInt(scoreMatch[1]) || 0;
                    scormData.score_min = 0;
                    scormData.score_max = 100;
                }
                
                // Look for objectives score (from console logs)
                const objectivesScoreMatch = pageText.match(/objectives\.0\.score\.raw=(\d+)/);
                if (objectivesScoreMatch) {
                    scormData.score_raw = parseInt(objectivesScoreMatch[1]) || 0;
                }
                
                // Get data from our console interceptor cache FIRST (most reliable)
                if (window.scormDataCache) {
                    console.log('📊 Current SCORM cache state:', {
                        lesson_location: window.scormDataCache.lesson_location || 'null',
                        suspend_data: window.scormDataCache.suspend_data ? window.scormDataCache.suspend_data.substring(0, 100) + '...' : 'null',
                        session_time: window.scormDataCache.session_time || 'null',
                        lastUpdate: new Date(window.scormDataCache.lastUpdate).toLocaleTimeString()
                    });
                    
                    if (window.scormDataCache.lesson_location) {
                        scormData.lesson_location = window.scormDataCache.lesson_location;
                        console.log('📍 Using cached location:', window.scormDataCache.lesson_location);
                    }
                    if (window.scormDataCache.suspend_data) {
                        scormData.suspend_data = window.scormDataCache.suspend_data;
                        console.log('💾 Using cached suspend data:', window.scormDataCache.suspend_data.substring(0, 100) + '...');
                    }
                    if (window.scormDataCache.session_time) {
                        scormData.session_time = window.scormDataCache.session_time;
                        console.log('⏱️ Using cached session time:', window.scormDataCache.session_time);
                    }
                } else {
                    console.log('❌ No cached SCORM data available');
                }
                
                // Fallback: Try to get data from the page text
                if (!scormData.lesson_location) {
                    // Look for cmi.location= format first (SCORM debug interface)
                    const cmiLocationMatches = pageText.match(/cmi\.location=([a-f0-9]+)/g);
                    if (cmiLocationMatches && cmiLocationMatches.length > 0) {
                        const lastLocation = cmiLocationMatches[cmiLocationMatches.length - 1];
                        const locationId = lastLocation.match(/cmi\.location=([a-f0-9]+)/)[1];
                        scormData.lesson_location = locationId;
                        console.log('Extracted location from page text (cmi.location):', locationId);
                    } else {
                        // Fallback to regular location= format
                        const locationMatches = pageText.match(/location=([a-f0-9]+)/g);
                        if (locationMatches && locationMatches.length > 0) {
                            const lastLocation = locationMatches[locationMatches.length - 1];
                            const locationId = lastLocation.match(/location=([a-f0-9]+)/)[1];
                            scormData.lesson_location = locationId;
                            console.log('Extracted location from page text (location):', locationId);
                        }
                    }
                }
                
                if (!scormData.suspend_data) {
                    // Look for cmi.suspend_data= format first (SCORM debug interface), capture up to '?' or EOL
                    const cmiSuspendDataMatches = pageText.match(/cmi\.suspend_data=({[\s\S]*?})(?:\s*\?|$)/g);
                    if (cmiSuspendDataMatches && cmiSuspendDataMatches.length > 0) {
                        const lastSuspendData = cmiSuspendDataMatches[cmiSuspendDataMatches.length - 1];
                        const suspendData = lastSuspendData.match(/cmi\.suspend_data=({[\s\S]*?})(?:\s*\?|$)/)[1];
                        scormData.suspend_data = suspendData;
                        console.log('Extracted suspend data from page text (cmi.suspend_data):', suspendData.substring(0, 100) + '...');
                        // Calculate progress from suspend data
                        scormData.progress_percent = calculateProgressFromSuspendData(suspendData);
                    } else {
                        // Fallback to regular suspend_data= format
                        const suspendDataMatches = pageText.match(/suspend_data=({[\s\S]*?})(?:\s*\?|$)/g);
                        if (suspendDataMatches && suspendDataMatches.length > 0) {
                            const lastSuspendData = suspendDataMatches[suspendDataMatches.length - 1];
                            const suspendData = lastSuspendData.match(/suspend_data=({[\s\S]*?})(?:\s*\?|$)/)[1];
                            scormData.suspend_data = suspendData;
                            console.log('Extracted suspend data from page text (suspend_data):', suspendData.substring(0, 100) + '...');
                            // Calculate progress from suspend data
                            scormData.progress_percent = calculateProgressFromSuspendData(suspendData);
                        }
                    }
                }
                
                if (!scormData.session_time) {
                    // Look for cmi.session_time= format first (SCORM debug interface)
                    const cmiSessionTimeMatches = pageText.match(/cmi\.session_time=([^,\s]+)/g);
                    if (cmiSessionTimeMatches && cmiSessionTimeMatches.length > 0) {
                        const lastSessionTime = cmiSessionTimeMatches[cmiSessionTimeMatches.length - 1];
                        const sessionTime = lastSessionTime.match(/cmi\.session_time=([^,\s]+)/)[1];
                        scormData.session_time = lastSessionTime;
                        console.log('Extracted session time from page text (cmi.session_time):', lastSessionTime);
                    } else {
                        // Fallback to regular session_time= format
                        const sessionTimeMatches = pageText.match(/session_time=([^,\s]+)/g);
                        if (sessionTimeMatches && sessionTimeMatches.length > 0) {
                            const lastSessionTime = sessionTimeMatches[sessionTimeMatches.length - 1];
                            const sessionTime = lastSessionTime.match(/session_time=([^,\s]+)/)[1];
                            scormData.session_time = lastSessionTime;
                            console.log('Extracted session time from page text (session_time):', lastSessionTime);
                        }
                    }
                }
                
                // If we still don't have good data, try to get it from the iframe's global variables
                try {
                    // Look for any global SCORM data in the iframe
                    if (iframeWindow.SCORM && iframeWindow.SCORM.data) {
                        const scormDataObj = iframeWindow.SCORM.data;
                        if (scormDataObj.lesson_status) scormData.lesson_status = scormDataObj.lesson_status;
                        if (scormDataObj.lesson_location) scormData.lesson_location = scormDataObj.lesson_location;
                        if (scormDataObj.suspend_data) scormData.suspend_data = scormDataObj.suspend_data;
                        if (scormDataObj.session_time) scormData.session_time = scormDataObj.session_time;
                    }
                } catch (e) {
                    // Ignore errors accessing iframe globals
                }
                
                // Calculate progress if not already set
                if (!scormData.progress_percent) {
                    scormData.progress_percent = calculateProgressFromSuspendData(scormData.suspend_data);
                }
                
                // Use calculated time values instead of extracted ones
                scormData.session_time = getAccumulatedSessionTime();
                scormData.total_time = getTotalTime();
                
                console.log('Extracted SCORM data from iframe:', scormData);
                return scormData;
                
            } catch (error) {
                console.error('Error getting SCORM data from iframe:', error);
                return null;
            }
        }
        
        // Calculate progress percentage from suspend data
        function calculateProgressFromSuspendData(suspendData) {
            try {
                if (!suspendData) {
                    console.log('🔍 No suspend data available for progress calculation');
                    return 0;
                }
                
                console.log('🔍 Calculating progress from suspend data:', suspendData);
                
                let parsedData;
                try {
                    parsedData = JSON.parse(suspendData);
                    console.log('🔍 Parsed suspend data:', parsedData);
                } catch (e) {
                    console.log('🔍 Suspend data is not JSON, trying string extraction');
                    // If not JSON, try to extract progress from string
                    const progressMatch = suspendData.match(/progress[:_]?(\d+)/i);
                    if (progressMatch) {
                        const progress = Math.min(parseInt(progressMatch[1]), 100);
                        console.log('🔍 Extracted progress from string:', progress + '%');
                        return progress;
                    }
                    return 0;
                }
                
                if (parsedData && typeof parsedData === 'object') {
                    // Handle different suspend data formats
                    if (parsedData.h && typeof parsedData.h === 'object') {
                        // Count completed slides/objectives
                        const completedCount = Object.keys(parsedData.h).length;
                        console.log('🔍 Completed slides count:', completedCount);
                        
                        if (completedCount > 0) {
                            // Estimate total slides (common patterns: 10, 12, 15, 20, 25, 30)
                            const totalSlides = estimateTotalSlides(completedCount, parsedData);
                            const progress = Math.round((completedCount / totalSlides) * 100);
                            console.log('🔍 Calculated progress from completed slides:', completedCount + '/' + totalSlides + ' = ' + progress + '%');
                            return Math.min(progress, 100);
                        }
                    }
                    
                    // Look for explicit progress values
                    if (parsedData.progress !== undefined) {
                        const progress = Math.min(parseInt(parsedData.progress), 100);
                        console.log('🔍 Found explicit progress value:', progress + '%');
                        return progress;
                    }
                    if (parsedData.completed !== undefined) {
                        const progress = Math.min(parseInt(parsedData.completed), 100);
                        console.log('🔍 Found completed value:', progress + '%');
                        return progress;
                    }
                    if (parsedData.percent !== undefined) {
                        const progress = Math.min(parseInt(parsedData.percent), 100);
                        console.log('🔍 Found percent value:', progress + '%');
                        return progress;
                    }
                }
                
                console.log('🔍 No progress data found in suspend data, returning 0%');
                return 0;
            } catch (error) {
                console.error('Error calculating progress from suspend data:', error);
                return 0;
            }
        }
        
        // Estimate total slides based on completed count and data patterns
        function estimateTotalSlides(completedCount, parsedData) {
            try {
                // Common SCORM package slide counts
                const commonSlideCounts = [10, 12, 15, 16, 18, 20, 24, 25, 30, 32, 36, 40, 45, 50];
                
                // If we have a current slide indicator, use it
                if (parsedData.c && parsedData.c.length > 0) {
                    // Try to extract slide number from current slide ID
                    const currentSlideMatch = parsedData.c.match(/(\d+)/);
                    if (currentSlideMatch) {
                        const currentSlide = parseInt(currentSlideMatch[1]);
                        // Estimate total as current + some buffer
                        const estimatedTotal = Math.max(currentSlide + 5, completedCount + 3);
                        console.log('🔍 Estimated total slides from current slide:', currentSlide, '->', estimatedTotal);
                        return estimatedTotal;
                    }
                }
                
                // Estimate based on completed count
                let estimatedTotal;
                if (completedCount <= 5) estimatedTotal = 15; // Early in course
                else if (completedCount <= 10) estimatedTotal = 25; // Mid-course
                else if (completedCount <= 15) estimatedTotal = 30; // Later in course
                else if (completedCount <= 20) estimatedTotal = 40; // Near end
                else estimatedTotal = Math.max(completedCount + 10, 50); // Default estimate
                
                console.log('🔍 Estimated total slides from completed count:', completedCount, '->', estimatedTotal);
                return estimatedTotal;
                
            } catch (error) {
                console.error('Error estimating total slides:', error);
                return Math.max(completedCount + 5, 20);
            }
        }
        
        // Alternative progress calculation based on current location
        function calculateProgressFromLocation(location) {
            try {
                if (!location) return 0;
                
                // Many packages use hex-like keys for location; avoid treating those as numbers
                const looksHexLike = /^[a-f0-9]{8,}$/i.test(location);
                if (looksHexLike) {
                    console.log('🔍 Location looks like a hex key; skipping numeric progress fallback:', location);
                    return 0;
                }
                
                // Convert location to number if possible
                const locationNum = parseInt(location, 10);
                if (isNaN(locationNum)) return 0;
                
                // Ignore out-of-range values
                if (locationNum < 0 || locationNum > 500) {
                    console.log('🔍 Location numeric value out of expected range; skipping:', locationNum);
                    return 0;
                }
                
                // Estimate total slides based on location bucket
                let estimatedTotal;
                if (locationNum <= 9) estimatedTotal = 15;
                else if (locationNum <= 19) estimatedTotal = 25;
                else if (locationNum <= 29) estimatedTotal = 35;
                else if (locationNum <= 39) estimatedTotal = 45;
                else estimatedTotal = 50;
                
                const progress = Math.round((locationNum / estimatedTotal) * 100);
                console.log('🔍 Progress from numeric location:', locationNum + '/' + estimatedTotal + ' = ' + progress + '%');
                return Math.min(progress, 100);
            } catch (error) {
                console.error('Error calculating progress from location:', error);
                return 0;
            }
        }
        
        // Update progress bar and text
        function updateProgressBar(progressPercent) {
            try {
                const progressFill = document.getElementById('progress-fill');
                const progressText = document.getElementById('progress-text');
                
                if (progressFill && progressText) {
                    // Ensure progress is between 0 and 100
                    const clampedProgress = Math.max(0, Math.min(100, progressPercent));
                    
                    // Update progress bar width with smooth animation
                    progressFill.style.transition = 'width 0.5s ease-in-out';
                    progressFill.style.width = clampedProgress + '%';
                    
                    // Update progress text
                    progressText.textContent = clampedProgress + '%';
                    
                    // Update progress bar color based on completion
                    if (clampedProgress >= 100) {
                        progressFill.style.background = 'linear-gradient(90deg, var(--success-color), #20c997)';
                    } else if (clampedProgress >= 75) {
                        progressFill.style.background = 'linear-gradient(90deg, var(--primary-color), #8a2be2)';
                    } else if (clampedProgress >= 50) {
                        progressFill.style.background = 'linear-gradient(90deg, #ffc107, #fd7e14)';
                    } else {
                        progressFill.style.background = 'linear-gradient(90deg, var(--primary-color), #8a2be2)';
                    }
                    
                    console.log('📊 Progress bar updated to:', clampedProgress + '%');
                }
            } catch (error) {
                console.error('Error updating progress bar:', error);
            }
        }
        
        // Update progress bar from cached SCORM data
        function updateProgressFromCache() {
            try {
                if (!window.scormDataCache) {
                    console.log('No SCORM cache available for progress update');
                    return;
                }
                
                console.log('🔍 Updating progress from cache:', {
                    location: window.scormDataCache.lesson_location,
                    suspend_data: window.scormDataCache.suspend_data ? 'Available' : 'None',
                    session_time: window.scormDataCache.session_time,
                    progress_percent: window.scormDataCache.progress_percent
                });
                
                // 1) If we have explicit percent from content/API, use it first
                let progressPercent = 0;
                if (typeof window.scormDataCache.progress_percent === 'number' && window.scormDataCache.progress_percent > 0) {
                    progressPercent = window.scormDataCache.progress_percent;
                } else {
                    // 2) Calculate from suspend data
                    progressPercent = calculateProgressFromSuspendData(window.scormDataCache.suspend_data);
                    
                    // 3) Fallback to numeric location (ignore hex-like)
                    if (progressPercent === 0 && window.scormDataCache.lesson_location) {
                        console.log('🔍 Suspend data progress is 0%, trying location-based calculation');
                        progressPercent = calculateProgressFromLocation(window.scormDataCache.lesson_location);
                    }
                }
                
                // Update the progress bar
                updateProgressBar(progressPercent);
                
                // Also update other display elements if available
                updateLocationDisplay(window.scormDataCache.lesson_location);
                updateSessionTimeDisplay(window.scormDataCache.session_time);
                
            } catch (error) {
                console.error('Error updating progress from cache:', error);
            }
        }
        
        // Update location display
        function updateLocationDisplay(location) {
            try {
                const locationDisplay = document.getElementById('location-display');
                if (locationDisplay && location) {
                    // Try to make location more user-friendly
                    if (location.length > 4) {
                        locationDisplay.textContent = `Slide ${location.slice(-4)}`;
                    } else {
                        locationDisplay.textContent = location;
                    }
                }
            } catch (error) {
                console.error('Error updating location display:', error);
            }
        }
        
        // Update session time display
        function updateSessionTimeDisplay(sessionTime) {
            try {
                const sessionTimeDisplay = document.getElementById('session-time');
                if (sessionTimeDisplay) {
                    // Always use accumulated session time to prevent flickering
                    // Only fall back to provided time if we have no accumulated time at all
                    let timeToDisplay;
                    if (accumulatedSessionTime > 0) {
                        timeToDisplay = getAccumulatedSessionTime();
                        console.log('⏱️ Using accumulated session time:', timeToDisplay);
                    } else if (sessionTime && sessionTime !== 'PT0S') {
                        timeToDisplay = sessionTime;
                        console.log('⏱️ Using provided session time (no accumulation yet):', timeToDisplay);
                    } else {
                        // Don't update display if we detect a reset and have no accumulated time
                        console.log('⏱️ Session time reset detected, not updating display');
                        return;
                    }
                    
                    const readableTime = convertScormTimeToReadable(timeToDisplay);
                    if (readableTime) {
                        sessionTimeDisplay.textContent = readableTime;
                        console.log('⏱️ Session time display updated:', readableTime);
                    }
                }
            } catch (error) {
                console.error('Error updating session time display:', error);
            }
        }
        
        // Convert SCORM time format (PT1M30S) to readable format
        function convertScormTimeToReadable(scormTime) {
            try {
                if (!scormTime || scormTime === 'PT0S') return '00:00:00';
                
                // Parse SCORM duration format (PT1H2M30S = 1 hour 2 minutes 30 seconds)
                const match = scormTime.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);
                if (match) {
                    const hours = parseInt(match[1]) || 0;
                    const minutes = parseInt(match[2]) || 0;
                    const seconds = parseInt(match[3]) || 0;
                    
                    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                }
                
                return scormTime;
            } catch (error) {
                console.error('Error converting SCORM time:', error);
                return scormTime;
            }
        }
        
        // Accumulate session time to prevent resetting
        function accumulateSessionTime(newSessionTime) {
            try {
                if (!newSessionTime) {
                    console.log('⏱️ No session time provided');
                    return;
                }
                
                // If we detect a reset (PT0S), don't update anything
                if (newSessionTime === 'PT0S') {
                    console.log('⏱️ Session time reset detected (PT0S), maintaining accumulated time:', accumulatedSessionTime + 's');
                    return;
                }
                
                // Parse the new session time
                const match = newSessionTime.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);
                if (match) {
                    const hours = parseInt(match[1]) || 0;
                    const minutes = parseInt(match[2]) || 0;
                    const seconds = parseInt(match[3]) || 0;
                    
                    // Convert to total seconds
                    const newTimeInSeconds = (hours * 3600) + (minutes * 60) + seconds;
                    
                    if (newTimeInSeconds > 0) {
                        const now = Date.now();
                        const timeSinceLastUpdate = (now - lastSessionTimeUpdate) / 1000;
                        
                        // Check if this is incremental time or total time
                        // If the new time is very small (like 2-3 seconds), it's likely incremental
                        // If it's larger, it might be total time for the current slide
                        if (newTimeInSeconds <= 10) {
                            // Likely incremental time - add to accumulated
                            accumulatedSessionTime += newTimeInSeconds;
                            console.log('⏱️ Added incremental time:', newTimeInSeconds + 's, total accumulated:', accumulatedSessionTime + 's');
                        } else if (newTimeInSeconds <= timeSinceLastUpdate + 60) {
                            // Reasonable time increase - add to accumulated
                            accumulatedSessionTime += newTimeInSeconds;
                            console.log('⏱️ Added session time:', newTimeInSeconds + 's, total accumulated:', accumulatedSessionTime + 's');
                        } else {
                            console.log('⏱️ Large time jump detected, not accumulating:', newTimeInSeconds + 's');
                        }
                        
                        lastSessionTimeUpdate = now;
                    }
                }
            } catch (error) {
                console.error('Error accumulating session time:', error);
            }
        }
        
        // Get accumulated session time in SCORM format
        function getAccumulatedSessionTime() {
            try {
                if (accumulatedSessionTime === 0) return 'PT0S';
                
                const hours = Math.floor(accumulatedSessionTime / 3600);
                const minutes = Math.floor((accumulatedSessionTime % 3600) / 60);
                const seconds = accumulatedSessionTime % 60;
                
                let timeString = 'PT';
                if (hours > 0) timeString += hours + 'H';
                if (minutes > 0) timeString += minutes + 'M';
                if (seconds > 0 || (hours === 0 && minutes === 0)) timeString += seconds + 'S';
                
                return timeString;
            } catch (error) {
                console.error('Error formatting accumulated session time:', error);
                return 'PT0S';
            }
        }
        
        // Get total time (session time + any previous total time)
        function getTotalTime() {
            try {
                // Get current session time
                const sessionTime = getAccumulatedSessionTime();
                
                // For now, total time is the same as session time
                // In a more complex implementation, this would add previous session times
                return sessionTime;
            } catch (error) {
                console.error('Error calculating total time:', error);
                return 'PT0S';
            }
        }
        
        // Save SCORM progress using the SCORM API endpoint
        async function saveSCORMProgress(progressData) {
            try {
                // Don't overwrite completed status
                if (window.contentCompleted) {
                    console.log('Save SCORM progress skipped - content already completed');
                    return { success: true, message: 'Content already completed' };
                }
                
                // Clean up the data to ensure proper types
                const cleanData = {
                    lesson_status: progressData.lesson_status || 'incomplete',
                    lesson_location: progressData.lesson_location || '',
                    suspend_data: progressData.suspend_data || '',
                    score_raw: parseInt(progressData.score_raw) || 0,
                    score_min: parseInt(progressData.score_min) || 0,
                    score_max: parseInt(progressData.score_max) || 100,
                    session_time: progressData.session_time || 'PT0S',
                    total_time: progressData.total_time || 'PT0S'
                };
                
                const requestBody = {
                    course_id: SCORM_CONFIG.courseId,
                    content_id: SCORM_CONFIG.contentId,
                    progress_data: JSON.stringify(cleanData),
                    content_type: SCORM_CONFIG.contentType,
                    prerequisite_id: SCORM_CONFIG.prerequisiteId,
                    scorm_package_id: SCORM_CONFIG.scormPackageId
                };
                
                console.log('Sending SCORM progress data:', requestBody);
                
                const response = await fetch('/Unlockyourskills/scorm/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'include', // Include cookies/session
                    body: new URLSearchParams(requestBody)
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                console.log('SCORM progress save response:', result);
                
                return result;
                
            } catch (error) {
                console.error('Error saving SCORM progress:', error);
                return {
                    success: false,
                    error: error.message
                };
                }
        }
        


        // Initialize SCORM integration
        async function initializeSCORMIntegration() {
            try {
                updateStatus('Setting up SCORM integration...');
                
                // We don't need the old SCORM integration manager anymore
                // Our new system works directly with the iframe and console interceptor
                
                // Create a simple wrapper object for compatibility
                scormWrapper = {
                    currentCourseId: SCORM_CONFIG.courseId,
                    currentContentId: SCORM_CONFIG.contentId,
                    currentModuleId: SCORM_CONFIG.moduleId,
                    userId: SCORM_CONFIG.userId,
                    clientId: SCORM_CONFIG.clientId,
                    connection: {
                        isActive: true,
                        initialized: true
                    },
                    getScormProgressData: function() {
                        return getSCORMDataFromIframe();
                    }
                };
                
                // Pre-load resume data for better UX
                await fetchAndUpdateResumeData();
                
                console.log('SCORM integration initialized successfully');
                
            } catch (error) {
                console.error('Error initializing SCORM integration:', error);
                throw error;
            }
        }

        // Check if content is already completed
        async function checkCompletionStatus() {
            try {
                const response = await fetch('/Unlockyourskills/scorm/progress', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    credentials: 'include',
                    body: new URLSearchParams({
                        course_id: SCORM_CONFIG.courseId,
                        content_id: SCORM_CONFIG.contentId
                    })
                });
                
                const result = await response.json();
                if (result.success && result.data && result.data.lesson_status === 'completed') {
                    window.contentCompleted = true;
                    console.log('Content already completed - disabling auto-save');
                    stopAutoSave();
                    updateStatus('Completed');
                    disableControls();
                }
            } catch (error) {
                console.error('Error checking completion status:', error);
            }
        }
        
        // Check for resume data
        async function checkResumeData() {
            try {
                updateStatus('Checking for resume data...');
                console.log('Checking for resume data...');
                
                // Check if we have saved progress data
                const response = await fetch(`/Unlockyourskills/scorm/resume?course_id=${SCORM_CONFIG.courseId}&content_id=${SCORM_CONFIG.contentId}`, {
                    credentials: 'include' // Include cookies/session
                });
                const result = await response.json();
                
                if (result.success && result.data && result.data.scorm_data) {
                    const scormData = result.data.scorm_data;
                    console.log('Resume data found:', scormData);
                    
                    // Check if we have meaningful resume data
                    if (scormData.lesson_location || scormData.suspend_data) {
                        console.log('Showing resume overlay with data:', scormData);
                        showResumeOverlay();
                        
                        // Store the resume data for later use
                        window.resumeData = scormData;
                    } else {
                        console.log('No meaningful resume data found');
                    }
                } else {
                    console.log('No resume data available');
                }
                
            } catch (error) {
                console.error('Error checking resume data:', error);
            }
        }

        // Setup auto-save
        function setupAutoSave() {
            updateStatus('Setting up auto-save...');
            const autoSaveToggle = document.getElementById('auto-save-toggle');
            
            autoSaveToggle.addEventListener('change', function() {
                if (this.checked) {
                    startAutoSave();
                } else {
                    stopAutoSave();
                }
            });
            
            if (autoSaveToggle.checked) {
                startAutoSave();
            }
        }

        // Start auto-save
        function startAutoSave() {
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
            }
            
            autoSaveInterval = setInterval(() => {
                // Don't auto-save if content is already completed
                if (window.contentCompleted) {
                    console.log('Auto-save skipped - content already completed');
                    return;
                }
                
                // Don't auto-save if SCORM wrapper is not ready
                if (!isInitialized || !scormWrapper || !window.scormIframeWindow) {
                    console.log('Auto-save skipped - SCORM wrapper not ready');
                    return;
                }
                
                // Use our new save method instead of the old one
                saveProgress(true); // Silent save
                
                // Also update progress bar periodically
                updateProgressFromCache();
            }, 30000); // Every 30 seconds
            
            console.log('Auto-save started');
        }

        // Stop auto-save
        function stopAutoSave() {
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
                autoSaveInterval = null;
            }
            console.log('Auto-save stopped');
        }
        
        // Setup time tracking
        function setupTimeTracking() {
            // Update session time every 5 seconds
            setInterval(() => {
                if (isInitialized && !window.contentCompleted) {
                    // Update the accumulated session time based on page load time
                    const currentTime = Date.now();
                    const elapsed = (currentTime - sessionStartTime) / 1000; // Convert to seconds
                    
                    // Only update if we have meaningful elapsed time
                    if (elapsed > 0) {
                        accumulatedSessionTime = Math.floor(elapsed);
                        console.log('Updated session time:', accumulatedSessionTime + 's');
                    }
                }
            }, 5000);
        }

        // Setup event listeners
        function setupEventListeners() {
            updateStatus('Setting up event listeners...');
            // Page visibility change
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    saveProgress(true);
                }
            });

            // Page unload
            window.addEventListener('beforeunload', function() {
                saveProgress(true);
            });

            // Iframe load event
            const iframe = document.getElementById('scorm-iframe');
            iframe.addEventListener('load', function() {
                console.log('SCORM iframe loaded');
                updateStatus('Content Loaded');
                enableControls();
                
                // Initialize SCORM wrapper after iframe loads - removed since we use new system
                // setTimeout(() => {
                //     initializeSCORMWrapper();
                // }, 1000); // Wait 1 second for SCORM content to fully load
            });

            // Iframe error handling
            iframe.addEventListener('error', function() {
                console.error('SCORM iframe failed to load');
                showNotification('Failed to load SCORM content', 'error');
            });
        }

        // Save progress
        async function saveProgress(silent = false) {
            try {
                // Don't save if content is already completed
                if (window.contentCompleted) {
                    console.log('Save progress skipped - content already completed');
                    return true;
                }
                
                console.log('Save progress called:', {
                    isInitialized: isInitialized,
                    scormWrapper: !!scormWrapper,
                    wrapperActive: scormWrapper ? scormWrapper.connection.isActive : false,
                    wrapperMethods: 'New system - direct data extraction'
                });
                
                if (!scormWrapper || !isInitialized) {
                    if (!silent) showNotification('SCORM not ready', 'warning');
                    return false;
                }

                // Ensure cache reflects the latest console-captured values only if cache is empty
                if (!window.scormDataCache || (!window.scormDataCache.lesson_location && !window.scormDataCache.suspend_data)) {
                    refreshSCORMDataCache();
                }
                const progressData = scormWrapper.getScormProgressData();
                
                if (progressData && Object.keys(progressData).length > 0) {
                    // Use the SCORM API endpoint directly instead of progressTracker
                    const result = await saveSCORMProgress(progressData);

                    if (result && result.success) {
                        lastSaveTime = new Date();
                        updateLastSave();
                        if (!silent) showNotification('Progress saved successfully!', 'success');
                        return true;
                    } else {
                        if (!silent) showNotification('Failed to save progress', 'error');
                        return false;
                    }
                } else {
                    if (!silent) {
                        console.log('No progress data to save - SCORM wrapper may still be initializing');
                    }
                    return false;
                }
                
            } catch (error) {
                console.error('Error saving progress:', error);
                if (!silent) showNotification('Error saving progress: ' + error.message, 'error');
                return false;
            }
        }

        // Mark content as complete
        async function markComplete() {
            try {
                if (!scormWrapper) {
                    showNotification('SCORM not ready', 'warning');
                    return;
                }

                console.log('Marking content as complete...');
                
                // Refresh the SCORM data cache to get the latest data
                refreshSCORMDataCache();
                
                // Get current SCORM data
                const currentData = getSCORMDataFromIframe();
                if (currentData) {
                    console.log('Current SCORM data before marking complete:', currentData);
                    
                    // First, save the current progress data (without changing lesson_status)
                    const saveResult = await saveSCORMProgress(currentData);
                    
                    if (saveResult && saveResult.success) {
                        console.log('Progress saved successfully, now marking as complete...');
                        
                        // Now call the complete endpoint to set lesson_status to completed
                        const completeResult = await updateCourseProgress();
                        
                        if (completeResult) {
                            // Set the completion flag to prevent further auto-saves
                            window.contentCompleted = true;
                            
                            showNotification('Content marked as complete!', 'success');
                            updateStatus('Completed');
                            disableControls();
                            
                            // Stop auto-save since content is completed
                            stopAutoSave();
                        } else {
                            showNotification('Progress saved but completion status update failed', 'warning');
                        }
                        
                    } else {
                        showNotification('Failed to save progress: ' + (saveResult?.error || 'Unknown error'), 'error');
                    }
                } else {
                    showNotification('Could not get SCORM data for completion', 'error');
                }
                
            } catch (error) {
                console.error('Error marking complete:', error);
                showNotification('Error marking complete: ' + error.message, 'error');
            }
        }
        
        // Update course progress to completed
        async function updateCourseProgress() {
            try {
                const requestBody = {
                    course_id: SCORM_CONFIG.courseId,
                    content_id: SCORM_CONFIG.contentId,
                    module_id: SCORM_CONFIG.moduleId,
                    lesson_status: 'completed',
                    content_type: SCORM_CONFIG.contentType,
                    prerequisite_id: SCORM_CONFIG.prerequisiteId,
                    scorm_package_id: SCORM_CONFIG.scormPackageId
                };
                
                console.log('Sending complete request with data:', requestBody);
                
                const response = await fetch('/Unlockyourskills/scorm/complete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'include', // Include cookies/session
                    body: new URLSearchParams(requestBody)
                });
                
                console.log('Complete endpoint response status:', response.status);
                
                const result = await response.json();
                console.log('Complete endpoint response:', result);
                
                if (result.success) {
                    console.log('Course progress updated to completed');
                    return true;
                } else {
                    console.warn('Course progress update failed:', result.error);
                    return false;
                }
                
            } catch (error) {
                console.error('Error updating course progress:', error);
                return false;
            }
        }
        
        // Refresh SCORM data cache from current iframe state
        function refreshSCORMDataCache() {
            try {
                if (window.scormDataCache) {
                    console.log('🔄 Refreshing SCORM data cache...');
                    
                    // Get current data from iframe
                    const currentData = getSCORMDataFromIframe();
                    if (currentData) {
                        // Update cache with current data
                        if (currentData.lesson_location) {
                            window.scormDataCache.lesson_location = currentData.lesson_location;
                        }
                        if (currentData.suspend_data) {
                            window.scormDataCache.suspend_data = currentData.suspend_data;
                        }
                        if (currentData.session_time) {
                            window.scormDataCache.session_time = currentData.session_time;
                        }
                        window.scormDataCache.lastUpdate = Date.now();
                        
                        console.log('✅ SCORM data cache refreshed:', {
                            lesson_location: window.scormDataCache.lesson_location || 'null',
                            suspend_data: window.scormDataCache.suspend_data ? window.scormDataCache.suspend_data.substring(0, 100) + '...' : 'null',
                            session_time: window.scormDataCache.session_time || 'null',
                            lastUpdate: new Date(window.scormDataCache.lastUpdate).toLocaleTimeString()
                        });
                    }
                }
            } catch (error) {
                console.error('❌ Error refreshing SCORM data cache:', error);
            }
        }

        // Resume content
        async function resumeContent() {
            try {
                hideResumeOverlay();
                showNotification('Resuming content...', 'info');
                
                // Get resume data from our API
                const response = await fetch(`/Unlockyourskills/scorm/resume?course_id=${SCORM_CONFIG.courseId}&content_id=${SCORM_CONFIG.contentId}`, {
                    credentials: 'include' // Include cookies/session
                });
                const result = await response.json();
                
                if (result.success && result.data) {
                    const resumeData = result.data;
                    console.log('Resume data retrieved:', resumeData);
                    
                    // If we have SCORM data, we need to restore it to the iframe
                    if (resumeData.scorm_data) {
                        await restoreSCORMState(resumeData.scorm_data);
                        showNotification('Content resumed successfully!', 'success');
                    } else {
                        showNotification('No resume data available', 'info');
                    }
                } else {
                    showNotification('No resume data found', 'info');
                }
                
            } catch (error) {
                console.error('Error resuming content:', error);
                showNotification('Error resuming content: ' + error.message, 'error');
            }
        }
        
        // Manual resume - for when automatic resume fails
        function manualResume() {
            try {
                hideResumeOverlay();
                
                if (window.resumeData) {
                    const resumeData = window.resumeData;
                    console.log('Manual resume with data:', resumeData);
                    
                    // Translate the technical data into user-friendly information
                    const translation = translateScormKeys(resumeData);
                    
                    // Show user-friendly resume information
                    let message = '📚 Manual Resume Information:\n\n';
                    message += `📍 Current Position: ${translation.currentPosition}\n`;
                    message += `✅ Completed Slides: ${translation.completedSlides}\n`;
                    message += `⏱️ Session Time: ${translation.sessionTime}\n`;
                    
                    if (translation.lastAccessed) {
                        message += `🕒 Last Accessed: ${translation.lastAccessed}\n`;
                    }
                    
                    message += '\n💡 Manual Navigation Instructions:\n';
                    message += '• Look for navigation controls in the SCORM content\n';
                    message += '• Navigate to the slide/content section mentioned above\n';
                    message += '• Use the progress bar or menu if available\n';
                    message += '• Some content may have "Next/Previous" buttons\n';
                    
                    // Show in a more prominent way
                    alert(message);
                    
                    showNotification('Manual resume instructions displayed', 'info');
                } else {
                    showNotification('No resume data available for manual resume', 'warning');
                }
                
            } catch (error) {
                console.error('Error in manual resume:', error);
                showNotification('Error in manual resume: ' + error.message, 'error');
            }
        }
        
        // Quick jump to saved location (for SCORM packages that don't support resume)
        function quickJumpToLocation() {
            try {
                if (window.resumeData && window.resumeData.lesson_location) {
                    const location = window.resumeData.lesson_location;
                    console.log('Quick jumping to location:', location);
                    
                    // Get user-friendly location information
                    const translation = translateScormKeys(window.resumeData);
                    
                    // Try to navigate the SCORM content to the saved location
                    const iframeWindow = window.scormIframeWindow;
                    if (iframeWindow) {
                        // Method 1: Try to call a navigation function
                        if (typeof iframeWindow.navigateToLocation === 'function') {
                            iframeWindow.navigateToLocation(location);
                            showNotification(`🚀 Jumping to ${translation.currentPosition}`, 'success');
                            return;
                        }
                        
                        // Method 2: Try to set a global variable that the content might check
                        iframeWindow.targetLocation = location;
                        iframeWindow.shouldNavigate = true;
                        
                        // Method 3: Dispatch a custom event
                        const navigateEvent = new CustomEvent('scormNavigate', {
                            detail: { location: location }
                        });
                        iframeWindow.dispatchEvent(navigateEvent);
                        
                        showNotification(`🚀 Navigation command sent to ${translation.currentPosition}`, 'success');
                        
                        // Show instructions to the user
                        setTimeout(() => {
                            showNotification('💡 If the content doesn\'t navigate automatically, use the manual navigation instructions', 'info');
                        }, 2000);
                        
                    } else {
                        showNotification('Iframe not available for navigation', 'error');
                    }
                } else {
                    showNotification('No location data available for quick jump', 'warning');
                }
                
            } catch (error) {
                console.error('Error in quick jump:', error);
                showNotification('Error in quick jump: ' + error.message, 'error');
            }
        }
        
        // Restore SCORM state to iframe
        async function restoreSCORMState(scormData) {
            try {
                const iframeWindow = window.scormIframeWindow;
                if (!iframeWindow) {
                    console.error('Iframe window not available for resume');
                    return false;
                }
                
                console.log('Attempting to restore SCORM state:', scormData);
                
                // Try to communicate with the SCORM content to restore state
                try {
                    // Method 1: Try to call a resume function if it exists
                    if (typeof iframeWindow.resumeToLocation === 'function') {
                        console.log('Calling resumeToLocation function');
                        iframeWindow.resumeToLocation(scormData.lesson_location);
                        showNotification(`Resuming to location: ${scormData.lesson_location}`, 'success');
                        return true;
                    }
                    
                    // Method 2: Try to set SCORM data directly if available
                    if (iframeWindow.SCORM && iframeWindow.SCORM.data) {
                        console.log('Restoring SCORM data directly');
                        Object.assign(iframeWindow.SCORM.data, scormData);
                        showNotification(`SCORM state restored for location: ${scormData.lesson_location}`, 'success');
                        return true;
                    }
                    
                    // Method 3: Try to navigate using URL parameters
                    if (scormData.lesson_location) {
                        console.log('Attempting URL-based resume');
                        const currentUrl = iframeWindow.location.href;
                        let newUrl;
                        
                        if (currentUrl.includes('?')) {
                            newUrl = currentUrl + '&resume=' + encodeURIComponent(scormData.lesson_location);
                        } else {
                            newUrl = currentUrl + '?resume=' + encodeURIComponent(scormData.lesson_location);
                        }
                        
                        console.log('Navigating to:', newUrl);
                        iframeWindow.location.href = newUrl;
                        showNotification(`Navigating to resume location: ${scormData.lesson_location}`, 'success');
                        return true;
                    }
                    
                    // Method 4: Try to inject resume data into the iframe
                    if (scormData.suspend_data) {
                        console.log('Attempting to inject suspend data');
                        try {
                            // Try to set a global variable that the SCORM content might check
                            iframeWindow.resumeData = scormData;
                            iframeWindow.shouldResume = true;
                            
                            // Also try to dispatch a custom event
                            const resumeEvent = new CustomEvent('scormResume', {
                                detail: scormData
                            });
                            iframeWindow.dispatchEvent(resumeEvent);
                            
                            showNotification('Resume data injected, content should resume automatically', 'success');
                            return true;
                        } catch (injectError) {
                            console.warn('Data injection failed:', injectError);
                        }
                    }
                    
                } catch (restoreError) {
                    console.warn('Advanced resume methods failed:', restoreError);
                }
                
                // Fallback: Show the location info and let user navigate manually
                if (scormData.lesson_location) {
                    showNotification(`Resume location: ${scormData.lesson_location}. You may need to navigate manually.`, 'info');
                } else {
                    showNotification('Resume data available but location restoration failed', 'warning');
                }
                
                return false;
                
            } catch (error) {
                console.error('Error restoring SCORM state:', error);
                return false;
            }
        }

        // Start over
        function startOver() {
            hideResumeOverlay();
            
            // Reset completion flag and restart auto-save
            window.contentCompleted = false;
            startAutoSave();
            
            if (scormWrapper) {
                // Reset using our new system
                console.log('SCORM content reset');
                showNotification('Starting over...', 'info');
            }
        }

        // Show resume options
        function showResumeOptions() {
            showResumeOverlay();
        }

        // Toggle debug panel
        function toggleDebug() {
            const debugPanel = document.getElementById('debug-panel');
            debugPanel.classList.toggle('show');
            
            if (debugPanel.classList.contains('show')) {
                updateDebugInfo();
            }
        }

        // Update debug information
        function updateDebugInfo() {
            const debugContent = document.getElementById('debug-content');
            const debugInfo = {
                'SCORM Manager': 'New system - not needed',
                'SCORM Player': 'New system - not needed',
                'SCORM Wrapper': !!scormWrapper,
                'Progress Tracker': 'New system - not needed',
                'Initialized': isInitialized,
                'Course ID': SCORM_CONFIG.courseId,
                'Module ID': SCORM_CONFIG.moduleId,
                'Content ID': SCORM_CONFIG.contentId,
                'User ID': SCORM_CONFIG.userId,
                'Client ID': SCORM_CONFIG.clientId,
                'Resume Data': window.resumeData ? 'Available' : 'None',
                'SCORM Cache': window.scormDataCache ? 'Active' : 'None'
            };
            
            let html = '';
            Object.entries(debugInfo).forEach(([key, value]) => {
                html += `<div><strong>${key}:</strong> ${value}</div>`;
            });
            
            // Add resume data details if available
            if (window.resumeData) {
                html += '<hr><div><strong>Resume Details:</strong></div>';
                html += `<div>Location: ${window.resumeData.lesson_location || 'N/A'}</div>`;
                html += `<div>Suspend Data: ${window.resumeData.suspend_data ? 'Available' : 'N/A'}</div>`;
                html += `<div>Session Time: ${window.resumeData.session_time || 'N/A'}</div>`;
            }
            
            // Add SCORM cache details if available
            if (window.scormDataCache) {
                html += '<hr><div><strong>SCORM Cache:</strong></div>';
                html += `<div>Location: ${window.scormDataCache.lesson_location || 'N/A'}</div>`;
                html += `<div>Suspend Data: ${window.scormDataCache.suspend_data ? 'Available' : 'N/A'}</div>`;
                html += `<div>Session Time: ${window.scormDataCache.session_time || 'N/A'}</div>`;
                html += `<div>Last Update: ${new Date(window.scormDataCache.lastUpdate).toLocaleTimeString()}</div>`;
            }
            
            debugContent.innerHTML = html;
        }

        // Update status display
        function updateStatus(status) {
            document.getElementById('status-display').textContent = status;
        }

        // Enable controls
        function enableControls() {
            document.getElementById('btn-save').disabled = false;
            document.getElementById('btn-complete').disabled = false;
        }

        // Disable controls
        function disableControls() {
            document.getElementById('btn-save').disabled = true;
            document.getElementById('btn-complete').disabled = true;
        }

        // Update last save time
        function updateLastSave() {
            if (lastSaveTime) {
                const timeString = lastSaveTime.toLocaleTimeString();
                document.getElementById('last-save').textContent = timeString;
            }
        }

        // Show loading overlay
        function showLoadingOverlay() {
            document.getElementById('loading-overlay').classList.add('active');
        }

        // Hide loading overlay
        function hideLoadingOverlay() {
            document.getElementById('loading-overlay').classList.remove('active');
        }

        // Show resume overlay
        function showResumeOverlay() {
            document.getElementById('resume-overlay').classList.add('active');
            
            // Always fetch fresh resume data and update display
            fetchAndUpdateResumeData();
        }

        // Hide resume overlay
        function hideResumeOverlay() {
            document.getElementById('resume-overlay').classList.remove('active');
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notification-container');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${getNotificationIcon(type)}"></i>
                ${message}
            `;
            
            container.appendChild(notification);
            
            // Show notification
            setTimeout(() => notification.classList.add('show'), 50);

            // Auto-hide after timeout, stagger if multiple to avoid overlap animations
            const AUTO_HIDE_MS = 3200;
            const existingToasts = container.querySelectorAll('.notification');
            const delay = Math.min(existingToasts.length * 300, 1200); // stagger up to 1.2s
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, AUTO_HIDE_MS + delay);
        }

        // Get notification icon
        function getNotificationIcon(type) {
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };
            return icons[type] || 'info-circle';
        }

        // Close SCORM
        function closeSCORM() {
            if (scormWrapper) {
                // Terminate using our new system
                console.log('SCORM content terminated');
            }
            
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
            }
            
            // Save final progress
            saveProgress(true);
            
            // Close window or redirect
            try {
                window.close();
            } catch (e) {
                window.location.href = '<?= addslashes(UrlHelper::url("my-courses")) ?>';
            }
        }

        // Update session time
        function updateSessionTime() {
            try {
                // Always prioritize accumulated session time to prevent flickering
                let elapsed;
                if (accumulatedSessionTime > 0) {
                    elapsed = accumulatedSessionTime * 1000; // Convert seconds to milliseconds
                } else {
                    // Only use page load time if we have no accumulated time
                    elapsed = Date.now() - sessionStartTime;
                }
                
                const hours = Math.floor(elapsed / 3600000);
                const minutes = Math.floor((elapsed % 3600000) / 60000);
                const seconds = Math.floor((elapsed % 60000) / 1000);
                
                const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                
                const sessionTimeDisplay = document.getElementById('session-time');
                if (sessionTimeDisplay) {
                    // Only update if we're not in the middle of a SCORM update
                    // This prevents conflicts with the accumulated time display
                    if (accumulatedSessionTime > 0) {
                        // Use accumulated time format for consistency
                        const accumulatedTime = getAccumulatedSessionTime();
                        const readableTime = convertScormTimeToReadable(accumulatedTime);
                        if (readableTime && readableTime !== sessionTimeDisplay.textContent) {
                            sessionTimeDisplay.textContent = readableTime;
                        }
                    } else {
                        // Only update with page time if no accumulated time
                        if (timeString !== sessionTimeDisplay.textContent) {
                            sessionTimeDisplay.textContent = timeString;
                        }
                    }
                }
            } catch (error) {
                console.error('Error updating session time:', error);
            }
        }
        
        // Update session time every second
        setInterval(updateSessionTime, 1000);

        // Global error handler
        window.addEventListener('error', function(event) {
            console.error('Global error:', event.error);
            showNotification('An error occurred: ' + event.error.message, 'error');
        });

        // Unhandled promise rejection handler
        window.addEventListener('unhandledrejection', function(event) {
            console.error('Unhandled promise rejection:', event.reason);
            showNotification('An error occurred: ' + event.reason, 'error');
        });
        
        // Function to translate cryptic SCORM keys into user-friendly information
        function translateScormKeys(resumeData) {
            if (!resumeData) return {};
            
            const translation = {
                currentPosition: 'Unknown Position',
                completedSlides: '0 slides',
                sessionTime: '0 seconds',
                slideProgress: []
            };
            
            try {
                // Handle lesson location
                if (resumeData.lesson_location) {
                    // Try to extract meaningful information from the location
                    const location = resumeData.lesson_location;
                    if (location.length > 4) {
                        // Use last 4 characters as slide identifier
                        translation.currentPosition = `Slide ${location.slice(-4)}`;
                    } else {
                        translation.currentPosition = `Position ${location}`;
                    }
                }
                
                // Handle suspend data for slide progress
                if (resumeData.suspend_data) {
                    let suspendData;
                    try {
                        suspendData = JSON.parse(resumeData.suspend_data);
                    } catch (e) {
                        suspendData = resumeData.suspend_data;
                    }
                    
                    if (suspendData && typeof suspendData === 'object') {
                        // Count completed slides
                        if (suspendData.h && typeof suspendData.h === 'object') {
                            const completedSlides = Object.keys(suspendData.h).length;
                            if (completedSlides > 0) {
                                translation.completedSlides = `${completedSlides} slides completed`;
                            } else {
                                translation.completedSlides = 'No slides completed yet';
                            }
                            
                            // Create slide progress array
                            translation.slideProgress = Object.keys(suspendData.h).map((key, index) => ({
                                id: key,
                                number: index + 1,
                                displayName: `Slide ${index + 1}`,
                                completed: true
                            }));
                        }
                        
                        // Get current slide
                        if (suspendData.c) {
                            const currentSlideIndex = translation.slideProgress.findIndex(slide => slide.id === suspendData.c);
                            if (currentSlideIndex !== -1) {
                                translation.currentPosition = `Slide ${currentSlideIndex + 1}`;
                            }
                        }
                    }
                }
                
                // Handle session time
                if (resumeData.session_time) {
                    const sessionTime = resumeData.session_time;
                    if (sessionTime === 'PT1S' || sessionTime === 'PT2S') {
                        translation.sessionTime = 'Just started';
                    } else if (sessionTime.includes('PT')) {
                        // Parse SCORM duration format (PT1M30S = 1 minute 30 seconds)
                        const match = sessionTime.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/);
                        if (match) {
                            const hours = match[1] || 0;
                            const minutes = match[2] || 0;
                            const seconds = match[3] || 0;
                            
                            if (hours > 0) {
                                translation.sessionTime = `${hours}h ${minutes}m ${seconds}s`;
                            } else if (minutes > 0) {
                                translation.sessionTime = `${minutes}m ${seconds}s`;
                            } else {
                                translation.sessionTime = `${seconds}s`;
                            }
                        } else {
                            translation.sessionTime = sessionTime;
                        }
                    } else {
                        translation.sessionTime = sessionTime;
                    }
                }
                
                // Handle timestamp
                if (resumeData.timestamp) {
                    try {
                        const date = new Date(resumeData.timestamp);
                        if (!isNaN(date.getTime())) {
                            translation.lastAccessed = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                        }
                    } catch (e) {
                        translation.lastAccessed = 'Recently';
                    }
                }
                
            } catch (error) {
                console.error('Error translating SCORM keys:', error);
            }
            
            return translation;
        }
        
        // Function to update resume progress display
        function updateResumeProgressDisplay(resumeData) {
            const translation = translateScormKeys(resumeData);
            
            // Update the display elements
            const currentPositionEl = document.getElementById('current-position-display');
            const completedSlidesEl = document.getElementById('completed-slides-display');
            const sessionTimeEl = document.getElementById('session-time-display');
            
            if (currentPositionEl) {
                currentPositionEl.textContent = translation.currentPosition;
            }
            
            if (completedSlidesEl) {
                completedSlidesEl.textContent = translation.completedSlides;
            }
            
            if (sessionTimeEl) {
                sessionTimeEl.textContent = translation.sessionTime;
            }
            
            console.log('Resume progress translated:', translation);
            return translation;
        }
        
        // Debug function to show current cache state
        function debugCacheState() {
            if (window.scormDataCache) {
                console.log('🔍 DEBUG: Current SCORM cache state:', {
                    lesson_location: window.scormDataCache.lesson_location || 'null',
                    suspend_data: window.scormDataCache.suspend_data ? window.scormDataCache.suspend_data.substring(0, 100) + '...' : 'null',
                    session_time: window.scormDataCache.session_time || 'null',
                    lastUpdate: new Date(window.scormDataCache.lastUpdate).toLocaleTimeString(),
                    cacheAge: Date.now() - window.scormDataCache.lastUpdate + 'ms'
                });
            } else {
                console.log('🔍 DEBUG: No SCORM cache available');
            }
        }

        // Test function to manually test suspend data capture
        function testSuspendDataCapture() {
            console.log('🧪 Testing suspend data capture...');
            
            // Simulate a console log message that should trigger capture
            const testMessage = 'cmi.suspend_data={"h":{"test123":{"e":1}},"c":"test123"}? Echo:';
            console.log('🧪 Test message:', testMessage);
            
            // Manually call the console interceptor logic
            if (window.scormDataCache) {
                const suspendMatch = testMessage.match(/cmi\.suspend_data=({[\s\S]*?})(?:\s*\?|$)/);
                if (suspendMatch) {
                    const newSuspendData = suspendMatch[1];
                    console.log('🧪 Regex match found:', newSuspendData);
                    
                    if (window.scormDataCache.suspend_data === null || newSuspendData !== window.scormDataCache.suspend_data) {
                        window.scormDataCache.suspend_data = newSuspendData;
                        window.scormDataCache.lastUpdate = Date.now();
                        console.log('🧪 Test: Updated suspend data in cache:', newSuspendData);
                        console.log('🧪 Test: Cache state after update:', window.scormDataCache);
                    } else {
                        console.log('🧪 Test: Suspend data unchanged');
                    }
                } else {
                    console.log('🧪 Test: No regex match found');
                }
            } else {
                console.log('🧪 Test: No SCORM cache available');
            }
        }

        // Function to get resume data and update display
        async function fetchAndUpdateResumeData() {
            try {
                const url = `/Unlockyourskills/scorm/resume?course_id=${encodeURIComponent(SCORM_CONFIG.courseId)}&content_id=${encodeURIComponent(SCORM_CONFIG.contentId)}`;
                const response = await fetch(url, { credentials: 'include' });
                
                const data = await response.json();
                console.log('Resume API response:', data);
                
                if (data.success && data.data) {
                    window.resumeData = data.data;
                    updateResumeProgressDisplay(data.data);
                    
                    // Update progress bar from resume data
                    if (data.data.scorm_data && data.data.scorm_data.suspend_data) {
                        const progressPercent = calculateProgressFromSuspendData(data.data.scorm_data.suspend_data);
                        updateProgressBar(progressPercent);
                        console.log('📊 Progress bar updated from resume data:', progressPercent + '%');
                    }
                    
                    return data.data;
                } else {
                    console.error('Resume API error:', data.error || 'Unknown error');
                    // Set default values if no data
                    updateResumeProgressDisplay({
                        lesson_location: 'Unknown',
                        suspend_data: '{}',
                        session_time: '0 seconds'
                    });
                }
            } catch (error) {
                console.error('Error fetching resume data:', error);
                // Set default values on error
                updateResumeProgressDisplay({
                    lesson_location: 'Error loading',
                    suspend_data: '{}',
                    session_time: '0 seconds'
                });
            }
        }
    </script>
    </div> <!-- End of .scorm-main-container -->
</body>
</html>
