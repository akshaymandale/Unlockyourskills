<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'Content') ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="/Unlockyourskills/public/css/document-progress.css">
  <link rel="stylesheet" href="/Unlockyourskills/public/css/audio-progress.css">
  <style>
    :root { --purple: #6a0dad; --light: #f8f9fa; --border: #e5d6ff; }
    html, body { height: 100%; margin: 0; background: #fff; }
    .viewer-header {
      height: 48px; display: flex; align-items: center; justify-content: space-between;
      padding: 0 12px; background: #f3eaff; border-bottom: 1px solid var(--border);
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
    }
    .viewer-title { color: var(--purple); font-weight: 600; font-size: 14px; margin-left: 8px; }
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; font-size: 12px; 
      border: 1px solid #c9c9c9; background: #fff; color: #333; border-radius: 6px; text-decoration: none; cursor: pointer; }
    .btn:hover { background: #f2f2f2; }
    .btn-primary { border-color: var(--purple); color: var(--purple); }
    .btn-primary:hover { background: #efe6f7; }
    .btn-success { border-color: #28a745; color: #28a745; }
    .btn-success:hover { background: #f8fff9; }
    .viewer-frame { width: 100%; height: calc(100vh - 48px); border: 0; }
    
    /* Resume modal styles */
    .resume-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 10000;
      font-family: Arial, sans-serif;
    }
    
    .resume-modal-content {
      background: white;
      border-radius: 10px;
      padding: 30px;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      text-align: center;
    }
    
    .resume-modal-header h3 {
      margin: 0 0 20px 0;
      color: #333;
      font-size: 24px;
    }
    
    .resume-modal-body {
      margin-bottom: 25px;
      line-height: 1.6;
      color: #555;
    }
    
    .resume-info {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin: 15px 0;
      text-align: left;
      font-family: monospace;
      font-size: 14px;
    }
    
    .resume-modal-footer {
      display: flex;
      gap: 15px;
      justify-content: center;
    }
    
    .btn {
      padding: 12px 24px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      min-width: 140px;
    }
    
    .btn-primary {
      background: #007bff;
      color: white;
    }
    
    .btn-primary:hover {
      background: #0056b3;
      transform: translateY(-2px);
    }
    
    .btn-secondary {
      background: #6c757d;
      color: white;
    }
    
    .btn-secondary:hover {
      background: #545b62;
      transform: translateY(-2px);
    }
    
    /* Resume notification styles */
    .resume-notification {
      position: fixed;
      top: 20px;
      right: 20px;
      max-width: 400px;
      padding: 15px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 10001;
      font-family: Arial, sans-serif;
      animation: slideInRight 0.3s ease-out;
    }
    
    .resume-notification.success {
      background: #d4edda;
      border: 1px solid #c3e6cb;
      color: #155724;
    }
    
    .resume-notification.error {
      background: #f8d7da;
      border: 1px solid #f5c6cb;
      color: #721c24;
    }
    
    .notification-content {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .notification-icon {
      font-size: 20px;
      flex-shrink: 0;
    }
    
    .notification-text {
      font-size: 14px;
      line-height: 1.4;
    }
    
    .notification-text small {
      opacity: 0.8;
      font-size: 12px;
    }
    
    @keyframes slideInRight {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }
  </style>
</head>
<body>
  <div class="viewer-header">
    <div style="display:flex; align-items:center;">
      <button class="btn" onclick="closeTab()"><i class="fas fa-times"></i> Close</button>
      <div class="viewer-title"><?= htmlspecialchars($title ?? 'Content') ?></div>
    </div>
    <div style="display:flex; align-items:center; gap: 10px;">
          <?php if (($type ?? '') === 'scorm'): ?>
        <!-- SCORM Player button removed - SCORM content is now launched from course details page -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            <strong>SCORM Content:</strong> SCORM content should be launched from the course details page using the "Launch SCORM" button.
        </div>
        <button class="btn btn-info" onclick="captureSCORMDataFromIframe()"><i class="fas fa-sync"></i> Capture Data</button>
        <button class="btn btn-warning" onclick="forceCaptureAllSCORMData()"><i class="fas fa-exclamation-triangle"></i> Force Capture</button>
        <button class="btn btn-success" onclick="saveSCORMProgress()"><i class="fas fa-save"></i> Save Progress</button>
    <?php endif; ?>
    </div>
    <?php if (!empty($src ?? '')): ?>
      <?php if (($type ?? '') !== 'video' && ($type ?? '') !== 'audio'): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars($src) ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Open source</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  
  <?php if (!empty($src ?? '')): ?>
    <?php if (($type ?? '') === 'video'): ?>
      <video class="viewer-frame" controls autoplay playsinline>
        <source src="<?= htmlspecialchars($src) ?>" type="video/mp4">
        Your browser does not support the video tag.
      </video>
    <?php elseif (($type ?? '') === 'audio'): ?>
      <div class="audio-player-container" style="height: calc(100vh - 48px); display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--light, #f8f9fa) 0%, var(--border, #e5d6ff) 100%); padding: 20px;">
        <div class="audio-player-card" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; max-width: 600px; width: 100%;">
          <div class="audio-icon" style="font-size: 80px; color: var(--purple, #6a0dad); margin-bottom: 20px;">
            <i class="fas fa-headphones"></i>
          </div>
          <h2 class="audio-title" style="color: var(--purple, #6a0dad); margin-bottom: 30px; font-size: 24px; font-weight: 600; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
            <?= htmlspecialchars($title ?? 'Audio Content') ?>
          </h2>
          
          <!-- Audio Progress Tracking Container -->
          <div class="audio-progress-container">
            <div class="audio-progress-header">
              <h3 class="audio-progress-title">Progress Tracking</h3>
              <div class="audio-progress-status">
                <span class="audio-completion-badge" style="display: none;">Completed</span>
              </div>
            </div>
            
            <div class="audio-progress-bar-container">
              <div class="audio-progress-bar">
                <div class="audio-progress-bar-fill" style="width: 0%"></div>
              </div>
            </div>
            
            <div class="audio-progress-text">0% Complete</div>
            
            <div class="audio-progress-details">
              <div class="audio-progress-detail">
                <div class="audio-progress-detail-label">Current Time</div>
                <div class="audio-progress-detail-value" id="current-time">0:00</div>
              </div>
              <div class="audio-progress-detail">
                <div class="audio-progress-detail-label">Duration</div>
                <div class="audio-progress-detail-value" id="duration">0:00</div>
              </div>
              <div class="audio-progress-detail">
                <div class="audio-progress-detail-label">Play Count</div>
                <div class="audio-progress-detail-value" id="play-count">0</div>
              </div>
            </div>
            
            <div class="audio-progress-stats">
              <div class="audio-stat">
                <span class="audio-stat-value" id="listened-percentage">0</span>
                <div class="audio-stat-label">% Listened</div>
              </div>
              <div class="audio-stat">
                <span class="audio-stat-value" id="completion-status">Not Started</span>
                <div class="audio-stat-label">Status</div>
              </div>
              <div class="audio-stat">
                <span class="audio-stat-value" id="audio-playback-status">Not Started</span>
                <div class="audio-stat-label">Playback</div>
              </div>
            </div>
          </div>
          
          <div class="audio-player-wrapper" style="width: 100%; margin-bottom: 30px;">
            <audio controls preload="metadata" style="width: 100%; height: 60px;" id="audio-player">
              <source src="<?= htmlspecialchars($src) ?>" type="audio/mpeg">
              <source src="<?= htmlspecialchars($src) ?>" type="audio/wav">
              <source src="<?= htmlspecialchars($src) ?>" type="audio/ogg">
              Your browser does not support the audio element.
            </audio>
          </div>
          

          
          <div class="audio-info" style="color: var(--purple, #6a0dad); font-size: 14px; line-height: 1.6; margin-top: 20px; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">
            <p><i class="fas fa-info-circle me-2"></i>Click play to start listening to the audio content.</p>
            <p><i class="fas fa-clock me-2"></i>You can pause, rewind, or adjust the volume as needed.</p>
            <p><i class="fas fa-chart-line me-2"></i>Your progress is automatically tracked and saved.</p>
          </div>
        </div>
      </div>
    <?php else: ?>
      <?php if (($type ?? '') === 'scorm'): ?>
        <iframe class="viewer-frame" src="<?= htmlspecialchars($src) ?>" allow="fullscreen *; geolocation *; microphone *; camera *" referrerpolicy="no-referrer-when-downgrade"></iframe>
      <?php else: ?>
        <iframe class="viewer-frame" src="<?= htmlspecialchars($src) ?>" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
      <?php endif; ?>
    <?php endif; ?>
  <?php else: ?>
    <div style="height: calc(100vh - 48px); display:flex; align-items:center; justify-content:center; color:#777; font-family: system-ui;">No content to display.</div>
  <?php endif; ?>
  
  <script src="/Unlockyourskills/public/js/audio-progress.js"></script>
  <script>
  // Global variables for SCORM functionality
  window.scormData = {};
  window.scormIframe = null;
  window.scormAPIReady = false;
  window.scormResumeData = null;
  window.scormResumeApplied = false;
  window.resumeModalShown = false;
  window.userChoseResume = false;
  window.userChoseStartOver = false;
  
  // Debug function
  function debugLog(message, data = null) {
    const timestamp = new Date().toLocaleTimeString();
    console.log(`[${timestamp}] ${message}`, data || '');
  }
  
  // Function to log current SCORM data state
  function logScormDataState() {
    debugLog('📊 Current SCORM data state:', {
      totalKeys: Object.keys(window.scormData).length,
      data: window.scormData
    });
  }
  
  // Function to close the current tab
  function closeTab() {
    // Set a flag in localStorage to indicate document was closed
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const courseId = urlParams.get('course_id');
      const moduleId = urlParams.get('module_id');
      const contentId = urlParams.get('content_id');
      
      if (courseId && moduleId && contentId) {
        localStorage.setItem('document_closed_' + contentId, Date.now().toString());
        console.log('Document close flag set for:', contentId);
      }
    } catch (error) {
      console.error('Error setting document close flag:', error);
    }
    
    // Original close functionality
    try { window.close(); } catch(e) {}
    setTimeout(function(){
      try { window.open('', '_self'); window.close(); } catch(e) {}
    }, 50);
    setTimeout(function(){
      if (document.visibilityState === 'visible') {
        history.length > 1 ? history.back() : (window.location.href = '<?= addslashes(UrlHelper::url("my-courses")) ?>');
      }
    }, 150);
  }

  // SCORM Player launch function removed - now handled from course details page
  // SCORM content should be launched using the "Launch SCORM" button on the course details page
  
  // Function to manually save SCORM progress
  function saveSCORMProgress() {
    saveSCORMProgressInternal();
  }

  // Function to save SCORM progress internally
  function saveSCORMProgressInternal() {
    try {
      debugLog('💾 Attempting to save SCORM progress internally');
      debugLog('Current scormData:', window.scormData);
      debugLog('Object keys in scormData:', Object.keys(window.scormData));
      
      if (!window.scormData || Object.keys(window.scormData).length === 0) {
        debugLog('❌ No SCORM data available for saving');
        showNotification('No SCORM progress data available. Please navigate through the SCORM content first.', 'error');
        return false;
      }
      
      // Extract SCORM data
      const scormProgress = {
        lesson_location: window.scormData['cmi.location'] || '',
        lesson_status: window.scormData['cmi.lesson_status'] || 'not attempted',
        score_raw: window.scormData['cmi.score.raw'] || null,
        session_time: window.scormData['cmi.session_time'] || null,
        total_time: window.scormData['cmi.total_time'] || null,
        suspend_data: window.scormData['cmi.suspend_data'] || '{}'
      };
      
      debugLog('📊 Extracted SCORM progress data:', scormProgress);
      
      // Get URL parameters for the request
      const urlParams = new URLSearchParams(window.location.search);
      const courseId = urlParams.get('course_id');
      const moduleId = urlParams.get('module_id');
      const contentId = urlParams.get('content_id');
      
      debugLog('📋 Request parameters:', { courseId, moduleId, contentId });
      
      // Save progress using the correct SCORM update endpoint
      if (window.progressTracker && window.progressTracker.updateContentProgress) {
        debugLog('✅ Progress tracker available, calling updateContentProgress');
        const result = window.progressTracker.updateContentProgress(
          contentId, 
          'scorm',
          courseId,
          scormProgress
        );
        debugLog('📝 updateContentProgress result:', result);
        
        // Handle the promise result
        result.then(success => {
          if (success) {
            debugLog('✅ SCORM progress saved successfully');
            showNotification('SCORM progress saved successfully!', 'success');
          } else {
            debugLog('❌ Failed to save SCORM progress');
            showNotification('Failed to save SCORM progress.', 'error');
          }
        }).catch(error => {
          debugLog('❌ Error saving SCORM progress:', error);
          showNotification('Error saving SCORM progress: ' + error.message, 'error');
        });
        
        return true;
      } else {
        debugLog('❌ Progress tracker not available or missing updateContentProgress method');
        showNotification('Progress tracker not available.', 'error');
        return false;
      }
      
    } catch (error) {
      debugLog('❌ Error saving SCORM progress:', error);
      showNotification('Error saving SCORM progress: ' + error.message, 'error');
      return false;
    }
  }

  // Function to show notification
  function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `resume-notification ${type}`;
    notification.innerHTML = `
      <div class="notification-content">
        <div class="notification-icon">
          <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        </div>
        <div class="notification-text">
          ${message}
          <small>${new Date().toLocaleTimeString()}</small>
        </div>
      </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.remove();
    }, 3000);
  }

  // Function to inject SCORM API wrapper into iframe
  function injectScormAPIWrapper(scormIframe) {
    try {
      debugLog('🔧 Injecting SCORM API wrapper into iframe');
      
      const script = document.createElement('script');
      script.textContent = `
        console.log('SCORM API wrapper script starting...');
        
        // Store original API if it exists
        window.originalAPI = window.API || window.SCORM?.API;
        
        // Create comprehensive SCORM 2004 API
        window.API = {
          // SCORM 2004 Core Methods
          LMSInitialize: function(param) {
            console.log('SCORM API: LMSInitialize called with param:', param);
            try {
              // Notify parent of initialization
              if (window.parent) {
                window.parent.postMessage({
                  type: 'SCORM_INITIALIZE',
                  param: param,
                  timestamp: Date.now()
                }, '*');
              }
            } catch (e) {
              console.log('Error notifying parent of initialization:', e);
            }
            return 'true';
          },
          
          LMSFinish: function(param) {
            console.log('SCORM API: LMSFinish called with param:', param);
            try {
              // Notify parent of finish
              if (window.parent) {
                window.parent.postMessage({
                  type: 'SCORM_FINISH',
                  param: param,
                  timestamp: Date.now()
                }, '*');
              }
            } catch (e) {
              console.log('Error notifying parent of finish:', e);
            }
            return 'true';
          },
          
          LMSGetValue: function(element) {
            console.log('SCORM API: LMSGetValue called for:', element);
            
            // Try to get value from parent's scormData first
            try {
              if (window.parent && window.parent.window && window.parent.window.scormData) {
                const parentData = window.parent.window.scormData;
                if (parentData[element] !== undefined) {
                  console.log('Retrieved value from parent:', element, '=', parentData[element]);
                  return parentData[element];
                }
              }
            } catch (e) {
              console.log('Error accessing parent data:', e);
            }
            
            // Try to get resume data from parent
            try {
              if (window.parent && window.parent.window && window.parent.window.scormResumeData) {
                const resumeData = window.parent.window.scormResumeData;
                console.log('Found resume data:', resumeData);
                
                switch(element) {
                  case 'cmi.location':
                    return resumeData.lesson_location || '';
                  case 'cmi.lesson_status':
                    return resumeData.lesson_status || 'not attempted';
                  case 'cmi.suspend_data':
                    return resumeData.suspend_data || '';
                  case 'cmi.score.raw':
                    return resumeData.score_raw || '';
                  case 'cmi.session_time':
                    return resumeData.session_time || '';
                  case 'cmi.total_time':
                    return resumeData.total_time || '';
                  case 'cmi.completion_status':
                    return resumeData.lesson_status || 'not attempted';
                  case 'cmi.progress_measure':
                    return resumeData.progress_measure || '0';
                  default:
                    return '';
                }
              }
            } catch (e) {
              console.log('Error accessing parent resume data:', e);
            }
            
            // Return empty string for unknown elements
            return '';
          },
          
          LMSSetValue: function(element, value) {
            console.log('SCORM API: LMSSetValue called for:', element, 'with value:', value);
            
            // Send data back to parent immediately
            try {
              if (window.parent) {
                window.parent.postMessage({
                  type: 'SCORM_DATA',
                  element: element,
                  value: value,
                  timestamp: Date.now()
                }, '*');
                
                // Also send as scormData object for compatibility
                window.parent.postMessage({
                  scormData: {
                    [element]: value
                  }
                }, '*');
                
                // Also send raw string format for compatibility
                window.parent.postMessage(element + '=' + value, '*');
                
                console.log('✅ SCORM data sent to parent:', element, '=', value);
              }
            } catch (e) {
              console.log('Error sending data to parent:', e);
            }
            
            return 'true';
          },
          
          LMSCommit: function(param) {
            console.log('SCORM API: LMSCommit called with param:', param);
            try {
              // Notify parent of commit
              if (window.parent) {
                window.parent.postMessage({
                  type: 'SCORM_COMMIT',
                  param: param,
                  timestamp: Date.now()
                }, '*');
              }
            } catch (e) {
              console.log('Error notifying parent of commit:', e);
            }
            return 'true';
          },
          
          LMSGetLastError: function() {
            return '0';
          },
          
          LMSGetErrorString: function(errorCode) {
            return 'No error';
          },
          
          LMSGetDiagnostic: function(errorCode) {
            return 'No diagnostic information';
          },
          
          // SCORM 2004 Additional Methods
          LMSGetVersion: function() {
            return '1.3';
          },
          
          LMSIsInitialized: function() {
            return 'true';
          },
          
          LMSIsTerminated: function() {
            return 'false';
          },
          
          LMSIsSuspended: function() {
            return 'false';
          }
        };
        
        // Create Debug_api for SCORM 2004 compatibility
        window.Debug_api = window.API;
        
        // Create SCORM object for compatibility
        window.SCORM = {
          API: window.API,
          connection: {
            initialize: function(param) { return window.API.LMSInitialize(param); },
            terminate: function(param) { return window.API.LMSFinish(param); },
            get: function(element) { return window.API.LMSGetValue(element); },
            set: function(element, value) { return window.API.LMSSetValue(element, value); },
            commit: function() { return window.API.LMSCommit(''); }
          },
          data: {
            get: function(element) { return window.API.LMSGetValue(element); },
            set: function(element, value) { return window.API.LMSSetValue(element, value); },
            save: function() { return window.API.LMSCommit(''); }
          }
        };
        
        // Aggressively override the package's internal SCORM implementation
        function overrideScormImplementation() {
          console.log('Attempting to override SCORM implementation...');
          
          // Override window.SCORM if it exists
          if (window.SCORM) {
            console.log('Overriding window.SCORM');
            if (window.SCORM.API) {
              window.SCORM.API = window.API;
            }
            if (window.SCORM.connection) {
              window.SCORM.connection.initialize = function(param) {
                return window.API.LMSInitialize(param);
              };
            }
            if (window.SCORM.data) {
              window.SCORM.data.get = function(element) {
                return window.API.LMSGetValue(element);
              };
              window.SCORM.data.set = function(element, value) {
                return window.API.LMSSetValue(element, value);
              };
              window.SCORM.data.save = function() {
                return window.API.LMSCommit('');
              };
            }
          }
          
          // Override window.connection if it exists
          if (window.connection) {
            console.log('Overriding window.connection');
            if (window.connection.initialize) {
              window.connection.initialize = function(param) {
                return window.API.LMSInitialize(param);
              };
            }
          }
          
          // Override any existing SCORM objects
          if (window.elucidat && window.elucidat.SCORM) {
            console.log('Overriding elucidat.SCORM');
            window.elucidat.SCORM.API = window.API;
          }
          
          // Override any other SCORM-related objects
          if (window.LMS) {
            console.log('Overriding window.LMS');
            window.LMS = window.API;
          }
          
          console.log('SCORM implementation override completed');
        }
        
        // Try to override immediately
        overrideScormImplementation();
        
        // Also try after delays to catch late initialization
        setTimeout(overrideScormImplementation, 1000);
        setTimeout(overrideScormImplementation, 2000);
        setTimeout(overrideScormImplementation, 3000);
        setTimeout(overrideScormImplementation, 5000);
        
        // Notify parent that API is ready
        try {
          if (window.parent) {
            window.parent.postMessage({
              type: 'SCORM_API_READY',
              timestamp: Date.now()
            }, '*');
          }
        } catch (e) {
          console.log('Error notifying parent:', e);
        }
        
        console.log('SCORM API wrapper injected successfully');
      `;
      
      // Add script to iframe
      if (scormIframe.contentDocument && scormIframe.contentDocument.head) {
        scormIframe.contentDocument.head.appendChild(script);
        debugLog('✅ SCORM API wrapper script element added to iframe head');
      } else {
        debugLog('❌ Cannot access iframe contentDocument, trying alternative method');
        // Alternative method: inject via iframe load event
        scormIframe.addEventListener('load', function() {
          if (scormIframe.contentDocument && scormIframe.contentDocument.head) {
            scormIframe.contentDocument.head.appendChild(script);
            debugLog('✅ SCORM API wrapper script element added to iframe head (delayed)');
          }
        });
      }
      
    } catch (error) {
      debugLog('❌ Error injecting SCORM API wrapper:', error);
    }
  }

  // Function to find SCORM iframe with multiple strategies
  function findSCORMIframe() {
    debugLog('🔍 Starting comprehensive SCORM iframe search...');
    
    let scormIframe = null;
    const strategies = [
      {
        name: 'Direct SCORM selectors',
        selectors: [
          'iframe[src*="launch.html"]',
          'iframe[src*="launchpage.html"]',
          'iframe[src*="scorm"]',
          'iframe[src*="index.html"]',
          'iframe.viewer-frame',
          'iframe[class*="scorm"]',
          'iframe[class*="viewer"]',
          'iframe[class*="content"]'
        ]
      },
      {
        name: 'Generic iframe search',
        selectors: ['iframe']
      },
      {
        name: 'Dynamic iframe detection',
        method: () => {
          // Look for iframes that might be dynamically added
          const allIframes = document.querySelectorAll('iframe');
          debugLog(`📺 Found ${allIframes.length} total iframes on page`);
          
          allIframes.forEach((iframe, index) => {
            debugLog(`🔍 Iframe ${index + 1}:`, {
              src: iframe.src,
              className: iframe.className,
              id: iframe.id,
              name: iframe.name,
              width: iframe.width,
              height: iframe.height
            });
          });
          
          // Return the first iframe that looks like it has content
          return allIframes.length > 0 ? allIframes[0] : null;
        }
      }
    ];
    
    // Try each strategy
    for (const strategy of strategies) {
      debugLog(`🔍 Trying strategy: ${strategy.name}`);
      
      if (strategy.method) {
        // Custom method strategy
        scormIframe = strategy.method();
        if (scormIframe) {
          debugLog(`✅ Found iframe using ${strategy.name}:`, scormIframe);
          break;
        }
      } else {
        // Selector strategy
        for (const selector of strategy.selectors) {
          const iframe = document.querySelector(selector);
          if (iframe) {
            debugLog(`✅ Found iframe using selector '${selector}':`, iframe);
            scormIframe = iframe;
            break;
          }
        }
        if (scormIframe) break;
      }
    }
    
    if (scormIframe) {
      debugLog('🎯 SCORM iframe found successfully:', {
        src: scormIframe.src,
        className: scormIframe.className,
        id: scormIframe.id,
        name: scormIframe.name
      });
      
      // Store the iframe reference
      window.scormIframe = scormIframe;
      debugLog('✅ SCORM iframe stored in window.scormIframe');
      
      return scormIframe;
    } else {
      debugLog('❌ No SCORM iframe found with any strategy');
      return null;
    }
  }
  
  // Function to wait for iframe with timeout and retry
  function waitForIframeWithRetry(maxAttempts = 60, interval = 1000) {
    debugLog(`⏳ Waiting for iframe with retry (max ${maxAttempts} attempts, ${interval}ms interval)...`);
    
    return new Promise((resolve, reject) => {
      let attempts = 0;
      
      const checkIframe = () => {
        attempts++;
        debugLog(`🔍 Iframe check attempt ${attempts}/${maxAttempts}`);
        
        const iframe = findSCORMIframe();
        if (iframe) {
          debugLog('✅ Iframe found after waiting, resolving promise');
          resolve(iframe);
          return;
        }
        
        if (attempts >= maxAttempts) {
          debugLog('❌ Maximum attempts reached, rejecting promise');
          reject(new Error('Iframe not found after maximum attempts'));
          return;
        }
        
        // Continue checking
        setTimeout(checkIframe, interval);
      };
      
      // Start checking
      checkIframe();
    });
  }

  // Function to setup SCORM functionality
  function setupScormFunctionality() {
    debugLog('🎯 Setting up SCORM functionality');
    
    // Use the new robust iframe detection
    const scormIframe = findSCORMIframe();
    
    if (scormIframe) {
      debugLog('✅ SCORM iframe found and ready');
      
      // Store the iframe reference
      window.scormIframe = scormIframe;
      debugLog('✅ SCORM iframe stored in window.scormIframe');
      
      // Inject SCORM API wrapper immediately
      debugLog('🔧 Injecting SCORM API wrapper...');
      injectScormAPIWrapper(scormIframe);
      
      // Add load event listener to iframe
      scormIframe.addEventListener('load', function() {
        debugLog('Iframe load event fired');
        // Inject SCORM API wrapper again after iframe content loads
        injectScormAPIWrapper(scormIframe);
      });
      
      // Inject SCORM API wrapper immediately if iframe is already loaded
      if (scormIframe.contentWindow && scormIframe.contentWindow.document.readyState === 'complete') {
        debugLog('Iframe already loaded, injecting SCORM API wrapper immediately');
        injectScormAPIWrapper(scormIframe);
      } else {
        debugLog('Iframe not loaded yet, adding load event listener');
      }
      
      // Start periodic SCORM data capture
      startPeriodicDataCapture();
      
      // Setup SCORM API detection
      setupScormAPIDetection();
      
    } else {
      debugLog('❌ No SCORM iframe found, starting enhanced waiting...');
      
      // Use the new enhanced waiting mechanism
      waitForIframeWithRetry(60, 1000) // Wait up to 60 seconds
        .then((iframe) => {
          debugLog('✅ Iframe found after waiting, setting up SCORM functionality');
          setupScormFunctionality(); // Recursive call with found iframe
        })
        .catch((error) => {
          debugLog('❌ Iframe not found after maximum attempts:', error.message);
          debugLog('🔄 Starting fallback mode...');
          startFallbackMode();
        });
    }
  }
  
  // Function to start periodic SCORM data capture
  function startPeriodicDataCapture() {
    debugLog('🔄 Starting periodic SCORM data capture...');
    
    // Capture data every 2 seconds (more frequent)
    setInterval(() => {
      if (window.scormIframe && window.scormIframe.contentWindow) {
        try {
          const iframeWindow = window.scormIframe.contentWindow;
          
          if (iframeWindow.API) {
            // Capture key SCORM elements
            const keyElements = [
              'cmi.location', 
              'cmi.lesson_status', 
              'cmi.suspend_data', 
              'cmi.progress_measure',
              'cmi.score.raw',
              'cmi.session_time',
              'cmi.total_time'
            ];
            
            let dataChanged = false;
            
            keyElements.forEach(element => {
              try {
                const value = iframeWindow.API.LMSGetValue(element);
                if (value && value !== '' && value !== window.scormData[element]) {
                  window.scormData[element] = value;
                  debugLog('📊 Auto-captured SCORM data:', element, '=', value);
                  dataChanged = true;
                }
              } catch (error) {
                // Ignore individual element errors
              }
            });
            
            // If data changed and we have meaningful data, auto-save
            if (dataChanged && (window.scormData['cmi.location'] || window.scormData['cmi.progress_measure'])) {
              debugLog('🔄 SCORM data changed, auto-saving progress...');
              setTimeout(() => {
                saveSCORMProgressInternal();
              }, 1000); // Save after 1 second delay
            }
            
            // Check if we should show resume modal
            if (Object.keys(window.scormData).length > 0 && !window.scormResumeApplied && !window.resumeModalShown) {
              debugLog('🔄 Periodic check: SCORM data available, checking resume conditions...');
              checkResumeModalConditions();
            }
          }
        } catch (error) {
          // Ignore periodic capture errors
        }
      }
    }, 2000);
    
    // Also check for resume data every 5 seconds
    setInterval(() => {
      if (window.scormAPIReady && !window.scormResumeApplied && !window.resumeModalShown) {
        debugLog('🔄 Periodic resume check...');
        checkForExistingProgress();
      }
    }, 5000);
    
    debugLog('✅ Periodic SCORM data capture started (every 2 seconds) + resume checks (every 5 seconds)');
  }

  // Function to check if we should show resume modal
  function checkResumeModalConditions() {
    debugLog('🔍 Checking resume modal conditions...');
    debugLog('Current state:', {
      hasResumeData: !!window.scormResumeData,
      scormAPIReady: window.scormAPIReady,
      scormResumeApplied: window.scormResumeApplied,
      resumeModalShown: window.resumeModalShown
    });
    
    if (window.scormResumeData && window.scormAPIReady && !window.scormResumeApplied && !window.resumeModalShown) {
      debugLog('✅ All conditions met, showing resume modal in 1 second');
      setTimeout(() => showResumeChoiceModal(), 1000);
      return true;
    } else {
      debugLog('❌ Cannot show resume modal:', {
        hasResumeData: !!window.scormResumeData,
        scormAPIReady: window.scormAPIReady,
        scormResumeApplied: window.scormResumeApplied,
        resumeModalShown: window.resumeModalShown
      });
      return false;
    }
  }
  
  // Function to setup SCORM API detection
  function setupScormAPIDetection() {
    debugLog('Setting up SCORM API detection');
    
    let attempts = 0;
    const maxAttempts = 50;
    
    const checkAPI = setInterval(() => {
      attempts++;
      debugLog(`SCORM API detection attempt ${attempts}/${maxAttempts}`);
      
      try {
        if (window.scormIframe && window.scormIframe.contentWindow.API) {
          debugLog('✅ SCORM API found!');
          clearInterval(checkAPI);
          window.scormAPIReady = true;
          
          // Check if we have resume data and show modal
          debugLog('Checking for resume data to show modal...');
          debugLog('Resume data available:', window.scormResumeData);
          debugLog('Resume applied:', window.scormResumeApplied);
          debugLog('Modal shown:', window.resumeModalShown);
          debugLog('All conditions check:', {
            hasResumeData: !!window.scormResumeData,
            scormResumeApplied: window.scormResumeApplied,
            resumeModalShown: window.resumeModalShown,
            canShowModal: !!(window.scormResumeData && !window.scormResumeApplied && !window.resumeModalShown)
          });
          
          // Use the helper function to check conditions
          checkResumeModalConditions();
        } else {
          debugLog(`SCORM API not found yet (attempt ${attempts})`);
        }
      } catch (e) {
        debugLog(`Error checking SCORM API (attempt ${attempts}):`, e.message);
      }
      
      if (attempts >= maxAttempts) {
        debugLog('❌ Failed to detect SCORM API after maximum attempts');
        clearInterval(checkAPI);
      }
    }, 100);
  }

  // Function to show resume choice modal
  function showResumeChoiceModal() {
    try {
      debugLog('🎭 Attempting to show resume choice modal');
      
      if (!window.scormResumeData || window.resumeModalShown) {
        debugLog('❌ Cannot show modal:', {
          hasResumeData: !!window.scormResumeData,
          resumeModalShown: window.resumeModalShown
        });
        return;
      }
      
      debugLog('✅ Showing resume modal with data:', window.scormResumeData);
      window.resumeModalShown = true;
      
      // Create modal HTML
      const modalHTML = `
        <div id="resumeChoiceModal" class="resume-modal-overlay">
          <div class="resume-modal-content">
            <div class="resume-modal-header">
              <h3>🔄 Resume Course?</h3>
            </div>
            <div class="resume-modal-body">
              <p>We found your previous progress in this course.</p>
              <div class="resume-info">
                <strong>Last Location:</strong> ${window.scormResumeData.lesson_location || 'Unknown'}<br>
                <strong>Progress:</strong> ${window.scormResumeData.lesson_status || 'Incomplete'}<br>
                <strong>Last Accessed:</strong> ${new Date().toLocaleDateString()}
              </div>
              <p>Would you like to resume from where you left off, or start from the beginning?</p>
            </div>
            <div class="resume-modal-footer">
              <button id="resumeCourseBtn" class="btn btn-primary">
                🔄 Resume Course
              </button>
              <button id="startOverBtn" class="btn btn-secondary">
                🚀 Start Over
              </button>
            </div>
          </div>
        </div>
      `;
      
      // Add modal to page
      document.body.insertAdjacentHTML('beforeend', modalHTML);
      debugLog('✅ Modal HTML added to page');
      
      // Add event listeners
      document.getElementById('resumeCourseBtn').addEventListener('click', function() {
        debugLog('🔄 User clicked Resume Course');
        hideResumeChoiceModal();
        applyResumeData();
      });
      
      document.getElementById('startOverBtn').addEventListener('click', function() {
        debugLog('🚀 User clicked Start Over');
        hideResumeChoiceModal();
        startCourseFromBeginning();
      });
      
      debugLog('✅ Modal event listeners added');
      
    } catch (error) {
      debugLog('❌ Error showing resume modal:', error);
    }

  }
  
  // Function to hide resume choice modal
  function hideResumeChoiceModal() {
    try {
      const modal = document.getElementById('resumeChoiceModal');
      if (modal) {
        modal.remove();
      }
    } catch (error) {
      // Handle errors silently
    }
  }
  
  // Function to apply resume data
  function applyResumeData() {
    try {
      window.userChoseResume = true;
      window.scormResumeApplied = true;
      
      if (window.scormIframe && window.scormIframe.contentWindow.API) {
        const api = window.scormIframe.contentWindow.API;
        
        if (window.scormResumeData.lesson_location) {
          api.LMSSetValue('cmi.location', window.scormResumeData.lesson_location);
        }
        if (window.scormResumeData.suspend_data) {
          api.LMSSetValue('cmi.suspend_data', window.scormResumeData.suspend_data);
        }
        if (window.scormResumeData.lesson_status) {
          api.LMSSetValue('cmi.lesson_status', window.scormResumeData.lesson_status);
        }
        if (window.scormResumeData.score_raw) {
          api.LMSSetValue('cmi.score.raw', window.scormResumeData.score_raw);
        }
        if (window.scormResumeData.session_time) {
          api.LMSSetValue('cmi.session_time', window.scormResumeData.session_time);
        }
        if (window.scormResumeData.total_time) {
          api.LMSSetValue('cmi.total_time', window.scormResumeData.total_time);
        }
        
        api.LMSCommit('');
        
        showNotification('Course resumed successfully!', 'success');
      }
      
    } catch (e) {
      console.error('Error applying resume data:', e);
      showNotification('Error resuming course', 'error');
    }
  }
  
  // Function to start course from beginning
  function startCourseFromBeginning() {
    try {
      window.userChoseStartOver = true;
      window.scormResumeApplied = true;
      
      // Clear any resume data
      window.scormResumeData = null;
      
      showNotification('Starting course from beginning', 'success');
      
    } catch (error) {
      console.error('Error starting course from beginning:', error);
    }
  }

  // Message listener for SCORM communication
  window.addEventListener('message', function(event) {
    debugLog('📨 Message received from iframe:', event.data);
    
    // Handle SCORM API ready message
    if (event.data && event.data.type === 'SCORM_API_READY') {
      debugLog('🎯 SCORM API ready message received from iframe');
      window.scormAPIReady = true;
      
      // Check if we have resume data and show modal
      debugLog('Checking for resume data after API ready...', {
        hasResumeData: !!window.scormResumeData,
        scormResumeApplied: window.scormResumeApplied,
        resumeModalShown: window.resumeModalShown
      });
      
      // Use the helper function to check conditions
      checkResumeModalConditions();
      return;
    }
    
    // Handle SCORM data messages with type
    if (event.data && event.data.type === 'SCORM_DATA') {
      const { element, value } = event.data;
      debugLog('📊 SCORM data message received:', { element, value });
      
      // Store the SCORM data
      window.scormData[element] = value;
      debugLog('💾 SCORM data stored:', window.scormData);
      logScormDataState();
    }
    
    // Handle SCORM data from the injected wrapper
    if (event.data && typeof event.data === 'object' && event.data.scormData) {
      debugLog('📊 SCORM data object received:', event.data.scormData);
      
      // Store the SCORM data
      Object.assign(window.scormData, event.data.scormData);
      debugLog('💾 SCORM data merged:', window.scormData);
      logScormDataState();
    }
    
    // Handle raw SCORM data strings (like "cmi.location=6242f2c025ba0")
    if (typeof event.data === 'string' && event.data.includes('=')) {
      const parts = event.data.split('=');
      if (parts.length === 2) {
        const element = parts[0];
        const value = parts[1];
        
        // Only process SCORM data model elements
        if (element.startsWith('cmi.')) {
          debugLog('📊 Raw SCORM data received:', { element, value });
          
          // Store the SCORM data
          window.scormData[element] = value;
          debugLog('💾 SCORM data stored from raw string:', window.scormData);
          logScormDataState();
        }
      }
    }
  });

  // Listen for resume events from progress tracker
  window.addEventListener('progressResume', function(event) {
    debugLog('🎯 ProgressResume event received!', event.detail);
    
    // Check if we have resume data
    if (event.detail) {
      const resumeData = event.detail;
      debugLog('📊 Resume data structure:', resumeData);
      
      // Check for SCORM data in the resume data
      if (resumeData.scorm_data) {
        debugLog('✅ SCORM data found:', resumeData.scorm_data);
        window.scormResumeData = resumeData.scorm_data;
        
        // Show resume modal if API is ready
        if (window.scormAPIReady && !window.scormResumeApplied && !window.resumeModalShown) {
          debugLog('🎉 All conditions met, showing resume modal in 1 second');
          setTimeout(() => showResumeChoiceModal(), 1000);
        } else {
          debugLog('❌ Cannot show resume modal:', {
            scormAPIReady: window.scormAPIReady,
            scormResumeApplied: window.scormResumeApplied,
            resumeModalShown: window.resumeModalShown
          });
        }
      } else {
        debugLog('❌ No SCORM data found in resume data');
        debugLog('Available keys:', Object.keys(resumeData));
      }
    } else {
      debugLog('❌ No event detail received');
    }
  });

  // Expose user data to JavaScript for progress tracking
  window.userData = {
    id: <?php echo json_encode($_SESSION['user']['id'] ?? null); ?>,
    client_id: <?php echo json_encode($_SESSION['user']['client_id'] ?? null); ?>
  };

  // Store user data in sessionStorage for cross-tab access
  if (window.userData.id && window.userData.client_id) {
    sessionStorage.setItem('user_data', JSON.stringify(window.userData));
  }
  
  // Load progress tracker (only for non-audio content)
  const urlParams = new URLSearchParams(window.location.search);
  const contentType = urlParams.get('type');
  
  if (contentType !== 'audio') {
    const progressScript = document.createElement('script');
    progressScript.src = '/unlockyourskills/public/js/progress-tracking.js';
    document.head.appendChild(progressScript);
  }
  
  // Load document progress tracker for document content
  if (contentType === 'document') {
    console.log('📄 Document content detected, loading progress tracker...');
                    const documentProgressScript = document.createElement('script');
                // Add cache-busting parameter
                documentProgressScript.src = '/Unlockyourskills/public/js/document-progress.js?v=' + Date.now();
                documentProgressScript.onload = function() {
                    console.log('✅ Document progress script loaded');
                    
                    // Start tracking immediately when script loads
                    if (window.documentProgressTracker) {
                        console.log('🚀 Starting document tracking immediately...');
                        window.documentProgressTracker.startTracking();
                    } else {
                        console.log('⏳ Document progress tracker not ready yet, will start on DOM ready');
                    }
                };
                documentProgressScript.onerror = function() {
                    console.error('❌ Failed to load document progress script');
                };
                document.head.appendChild(documentProgressScript);
  }
  
  // Initialize when content loads
  document.addEventListener('DOMContentLoaded', function() {
    debugLog('🚀 DOM Content Loaded - Starting initialization');
    
    const urlParams = new URLSearchParams(window.location.search);
    const contentType = urlParams.get('type');
    const courseId = urlParams.get('course_id');
    const moduleId = urlParams.get('module_id');
    const contentId = urlParams.get('content_id');
    const documentPackageId = urlParams.get('document_package_id');
    
    debugLog('📋 URL Parameters:', {
      contentType,
      courseId,
      moduleId,
      contentId,
      documentPackageId
    });
    
    // Initialize document progress tracker for document content
    if (contentType === 'document' && courseId && contentId) {
      debugLog('📄 Document content detected, initializing progress tracker');
      
      // Wait for document progress tracker to be available
      const waitForDocumentTracker = () => {
        if (window.documentProgressTracker) {
          debugLog('✅ Document progress tracker ready');
          
          // Set the document package ID if available
          if (documentPackageId) {
            window.documentProgressTracker.documentPackageId = documentPackageId;
            debugLog('📦 Document package ID set:', documentPackageId);
          }
          
          // Start tracking
          window.documentProgressTracker.startTracking();
        } else {
          debugLog('⏳ Document progress tracker not ready yet, retrying in 500ms');
          setTimeout(waitForDocumentTracker, 500);
        }
      };
      
      waitForDocumentTracker();
    }
    
    // Wait for progress tracker to be available (only for non-audio content)
    if (contentType !== 'audio') {
      const waitForProgressTracker = () => {
        debugLog('⏳ Waiting for progress tracker...', {
          hasProgressTracker: !!window.progressTracker,
          isInitialized: window.progressTracker?.isInitialized,
          hasAllParams: !!(courseId && moduleId && contentId && contentType)
        });
        
        if (window.progressTracker && window.progressTracker.isInitialized && courseId && moduleId && contentId && contentType) {
          debugLog('✅ Progress tracker ready, setting course context');
          window.progressTracker.setCourseContext(courseId, moduleId, contentId, contentType);
          
          // Setup SCORM functionality
          if (contentType === 'scorm') {
            debugLog('🎯 Setting up SCORM functionality');
            setupScormFunctionality();
            
            // Add periodic SCORM data state logging for debugging
            setInterval(() => {
              if (Object.keys(window.scormData).length > 0) {
                debugLog('🔄 Periodic SCORM data check:', window.scormData);
              }
            }, 5000); // Check every 5 seconds
            
            // Add periodic resume modal condition check
            setInterval(() => {
              if (window.scormAPIReady && !window.resumeModalShown) {
                debugLog('🔄 Periodic resume modal check');
                checkResumeModalConditions();
              }
            }, 2000); // Check every 2 seconds
          }
        } else {
          debugLog('⏳ Progress tracker not ready yet, retrying in 500ms');
          setTimeout(waitForProgressTracker, 500);
        }
      };
      
      waitForProgressTracker();
    } else {
      debugLog('🎵 Audio content detected, skipping general progress tracker initialization');
    }
  });
  
  // Define document notification functions
  function notifyDocumentOpened() {
    console.log('Document opened notification');
    // This function can be extended to notify parent window or track document opening
  }

  function notifyDocumentViewed() {
    console.log('Document viewed notification');
    // This function can be extended to track document viewing
  }

  // Send notification when document is first loaded
  document.addEventListener('DOMContentLoaded', function() {
    console.log('Document viewer loaded, notifying parent page');
    notifyDocumentOpened();
    
    // Also notify when the document content is actually viewed
    setTimeout(() => {
      notifyDocumentViewed();
    }, 1000); // Wait 1 second for content to load
  });

  // Handle page focus to track when user returns to document
  window.addEventListener('focus', function() {
    console.log('Document window gained focus - user returned to document');
    notifyDocumentViewed();
  });

  // Set close flags when page is unloaded (additional safety)
  window.addEventListener('beforeunload', function(event) {
    console.log('Page is being unloaded - setting close flags');
    setDocumentCloseFlag();
    setAudioCloseFlag();
  });

  // Set close flags when page is hidden
  document.addEventListener('pagehide', function(event) {
    console.log('Page is being hidden - setting close flags');
    setDocumentCloseFlag();
    setAudioCloseFlag();
  });

  // Set close flags when window is unloaded
  window.addEventListener('unload', function(event) {
    console.log('Window is being unloaded - setting close flags');
    setDocumentCloseFlag();
    setAudioCloseFlag();
  });

  // Helper function to set document close flag
  function setDocumentCloseFlag() {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const courseId = urlParams.get('course_id');
      const moduleId = urlParams.get('module_id');
      const contentId = urlParams.get('content_id');
      
      if (courseId && moduleId && contentId) {
        localStorage.setItem('document_closed_' + contentId, Date.now().toString());
        console.log('Document close flag set via event listener for:', contentId);
      }
    } catch (error) {
      console.error('Error setting document close flag via event listener:', error);
    }
  }

  // Helper function to set audio close flag
  function setAudioCloseFlag() {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const courseId = urlParams.get('course_id');
      const moduleId = urlParams.get('module_id');
      const contentId = urlParams.get('content_id');
      
      if (courseId && moduleId && contentId) {
        localStorage.setItem('audio_closed_' + contentId, Date.now().toString());
        console.log('Audio close flag set via event listener for:', contentId);
      }
    } catch (error) {
      console.error('Error setting audio close flag via event listener:', error);
    }
  }

  // Save progress on page unload
  window.addEventListener('beforeunload', function(e) {
    if (window.progressTracker && window.progressTracker.isInitialized && Object.keys(window.scormData).length > 0) {
      const urlParams = new URLSearchParams(window.location.search);
      const courseId = urlParams.get('course_id');
      const moduleId = urlParams.get('module_id');
      const contentId = urlParams.get('content_id');
      
      if (courseId && moduleId && contentId) {
        const scormData = {
          lesson_location: window.scormData['cmi.location'] || '',
          suspend_data: window.scormData['cmi.suspend_data'] || '{}',
          lesson_status: window.scormData['cmi.lesson_status'] || '',
          score_raw: window.scormData['cmi.score.raw'] || '',
          session_time: window.scormData['cmi.session_time'] || '',
          total_time: window.scormData['cmi.total_time'] || '',
          timestamp: new Date().toISOString()
        };
        
        window.progressTracker.setResumePosition(moduleId, contentId, scormData);
      }
    }
    
    // Set audio close flag for audio content
    const contentType = new URLSearchParams(window.location.search).get('type');
    if (contentType === 'audio') {
      setAudioCloseFlag();
    }
  });

  // Function to handle SCORM API ready message
  function handleScormAPIReady(data) {
    debugLog('🎯 SCORM API ready message received from iframe');
    window.scormAPIReady = true;
    
    // Check if we have resume data and show modal
    debugLog('Checking for resume data to show modal...');
    debugLog('Resume data available:', window.scormResumeData);
    debugLog('Resume applied:', window.scormResumeApplied);
    debugLog('Modal shown:', window.resumeModalShown);
    debugLog('All conditions check:', {
      hasResumeData: !!window.scormResumeData,
      scormResumeApplied: window.scormResumeApplied,
      resumeModalShown: window.resumeModalShown,
      canShowModal: !!(window.scormResumeData && !window.scormResumeApplied && !window.resumeModalShown)
    });
    
    // Use the helper function to check conditions
    checkResumeModalConditions();
    
    // Show success notification
    showNotification('SCORM API connection established successfully!', 'success');
  }
  
  // Function to handle SCORM initialization
  function handleScormInitialize(data) {
    debugLog('🎯 SCORM initialization received:', data);
    showNotification('SCORM content initialized successfully!', 'success');
  }
  
  // Function to handle SCORM finish
  function handleScormFinish(data) {
    debugLog('🎯 SCORM finish received:', data);
    showNotification('SCORM content completed!', 'success');
  }
  
  // Function to handle SCORM commit
  function handleScormCommit(data) {
    debugLog('🎯 SCORM commit received:', data);
    showNotification('SCORM data committed successfully!', 'success');
  }
  
  // Function to debug SCORM connection issues
  function debugScormConnection() {
    debugLog('🔍 === SCORM CONNECTION DEBUG ===');
    
    // Check iframe status
    if (window.scormIframe) {
      debugLog('✅ Iframe found:', {
        src: window.scormIframe.src,
        className: window.scormIframe.className,
        contentWindow: !!window.scormIframe.contentWindow,
        contentDocument: !!window.scormIframe.contentDocument
      });
      
      // Check iframe content
      try {
        if (window.scormIframe.contentWindow) {
          const iframeWindow = window.scormIframe.contentWindow;
          debugLog('Iframe window status:', {
            hasAPI: !!iframeWindow.API,
            hasSCORM: !!iframeWindow.SCORM,
            hasElucidat: !!iframeWindow.elucidat,
            hasConnection: !!iframeWindow.connection
          });
          
          if (iframeWindow.API) {
            debugLog('SCORM API methods available:', Object.keys(iframeWindow.API));
          }
        }
      } catch (error) {
        debugLog('❌ Error accessing iframe content:', error.message);
      }
    } else {
      debugLog('❌ No iframe found');
    }
    
    // Check SCORM data
    debugLog('SCORM data status:', {
      hasScormData: !!window.scormData,
      scormDataKeys: window.scormData ? Object.keys(window.scormData) : [],
      hasResumeData: !!window.scormResumeData,
      scormAPIReady: window.scormAPIReady
    });
    
    // Check message handling
    debugLog('Message handling status:', {
      hasMessageListener: true,
      lastMessageReceived: window.lastMessageReceived || 'None'
    });
    
    debugLog('=== END SCORM CONNECTION DEBUG ===');
  }
  
  // Expose debug function globally
  window.debugScormConnection = debugScormConnection;

  // Function to capture SCORM data from iframe
  function captureSCORMDataFromIframe() {
    try {
      debugLog('🔍 Attempting to capture SCORM data from iframe...');
      
      if (window.scormIframe && window.scormIframe.contentWindow) {
        const iframeWindow = window.scormIframe.contentWindow;
        debugLog('✅ Iframe found, checking for SCORM API...');
        
        // Try to get SCORM data directly from iframe
        if (iframeWindow.API) {
          debugLog('✅ SCORM API found in iframe');
          
          const scormElements = [
            'cmi.location',
            'cmi.lesson_status', 
            'cmi.score.raw',
            'cmi.session_time',
            'cmi.total_time',
            'cmi.suspend_data',
            'cmi.completion_status',
            'cmi.exit',
            'cmi.progress_measure',
            'cmi.scaled_passing_score',
            'cmi.completion_threshold'
          ];
          
          let capturedCount = 0;
          
          scormElements.forEach(element => {
            try {
              const value = iframeWindow.API.LMSGetValue(element);
              if (value && value !== '') {
                window.scormData[element] = value;
                debugLog('📊 Captured SCORM data:', element, '=', value);
                capturedCount++;
              }
            } catch (error) {
              debugLog('⚠️ Error capturing element:', element, error.message);
            }
          });
          
          debugLog(`✅ Manual SCORM data capture completed. Captured ${capturedCount} elements.`);
          logScormDataState();
          
          if (capturedCount > 0) {
            showNotification(`Successfully captured ${capturedCount} SCORM data elements!`, 'success');
            return true;
          } else {
            showNotification('No SCORM data available to capture. Please navigate through the content first.', 'warning');
            return false;
          }
        } else {
          debugLog('❌ SCORM API not found in iframe');
          showNotification('SCORM API not available in iframe. Please wait for content to load.', 'warning');
          return false;
        }
      } else {
        debugLog('❌ SCORM iframe not found');
        showNotification('SCORM iframe not available. Please wait for content to load.', 'warning');
        return false;
      }
    } catch (error) {
      debugLog('❌ Error during manual SCORM data capture:', error);
      showNotification('Error capturing SCORM data: ' + error.message, 'error');
      return false;
    }
  }

  // Function to force capture all available SCORM data
  function forceCaptureAllSCORMData() {
    try {
      debugLog('🚀 Force capturing all available SCORM data...');
      
      if (window.scormIframe && window.scormIframe.contentWindow) {
        const iframeWindow = window.scormIframe.contentWindow;
        
        if (iframeWindow.API) {
          debugLog('✅ SCORM API found, attempting force capture...');
          
          const scormElements = [
            'cmi.location',
            'cmi.lesson_status', 
            'cmi.score.raw',
            'cmi.session_time',
            'cmi.total_time',
            'cmi.suspend_data',
            'cmi.completion_status',
            'cmi.exit',
            'cmi.progress_measure',
            'cmi.scaled_passing_score',
            'cmi.completion_threshold',
            'cmi.learner_id',
            'cmi.learner_name'
          ];
          
          let capturedCount = 0;
          
          scormElements.forEach(element => {
            try {
              const value = iframeWindow.API.LMSGetValue(element);
              if (value && value !== '') {
                window.scormData[element] = value;
                debugLog('📊 Force captured SCORM data:', element, '=', value);
                capturedCount++;
              }
            } catch (error) {
              debugLog('⚠️ Error force capturing element:', element, error.message);
            }
          });
          
          debugLog(`✅ Force capture completed. Captured ${capturedCount} elements.`);
          logScormDataState();
          
          if (capturedCount > 0) {
            showNotification(`Force capture successful! Captured ${capturedCount} data elements.`, 'success');
            return true;
          } else {
            showNotification('Force capture completed but no data found. Content may not be fully loaded.', 'warning');
            return false;
          }
        } else {
          debugLog('❌ SCORM API not found in iframe');
          showNotification('SCORM API not available in iframe. Please wait for content to load.', 'warning');
          return false;
        }
      } else {
        debugLog('❌ SCORM iframe not available');
        showNotification('SCORM iframe not available. Please wait for content to load.', 'warning');
        return false;
      }
    } catch (error) {
      debugLog('❌ Error during force capture:', error);
      showNotification('Error during force capture: ' + error.message, 'error');
      return false;
    }
  }

  // Function to manually enter SCORM data
  function manuallyEnterSCORMData() {
    try {
      debugLog('📝 Manual SCORM data entry...');
      
      // Create a simple form to enter SCORM data
      const formHTML = `
        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                    background: white; padding: 20px; border: 2px solid #007bff; border-radius: 10px; 
                    z-index: 10000; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
          <h3>Enter SCORM Data Manually</h3>
          <div style="margin: 10px 0;">
            <label>Location ID:</label><br>
            <input type="text" id="manualLocation" placeholder="e.g., 6242f2c025ba0" style="width: 200px; padding: 5px;">
          </div>
          <div style="margin: 10px 0;">
            <label>Progress Measure:</label><br>
            <input type="text" id="manualProgress" placeholder="e.g., 0.1" style="width: 200px; padding: 5px;">
          </div>
          <div style="margin: 10px 0;">
            <label>Lesson Status:</label><br>
            <input type="text" id="manualStatus" placeholder="e.g., incomplete" style="width: 200px; padding: 5px;">
          </div>
          <div style="margin: 20px 0;">
            <button onclick="saveManualSCORMData()" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">Save Data</button>
            <button onclick="closeManualDataForm()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Cancel</button>
          </div>
        </div>
      `;
      
      // Add form to page
      document.body.insertAdjacentHTML('beforeend', formHTML);
      
    } catch (error) {
      debugLog('❌ Error creating manual data form:', error);
    }
  }
  
  // Function to save manually entered SCORM data
  function saveManualSCORMData() {
    try {
      const location = document.getElementById('manualLocation').value;
      const progress = document.getElementById('manualProgress').value;
      const status = document.getElementById('manualStatus').value;
      
      if (location) {
        window.scormData['cmi.location'] = location;
        debugLog('📊 Manual data saved: cmi.location =', location);
      }
      if (progress) {
        window.scormData['cmi.progress_measure'] = progress;
        debugLog('📊 Manual data saved: cmi.progress_measure =', progress);
      }
      if (status) {
        window.scormData['cmi.lesson_status'] = status;
        debugLog('📊 Manual data saved: cmi.lesson_status =', status);
      }
      
      // Close the form
      closeManualDataForm();
      
      // Show success message
      showNotification('Manual SCORM data saved successfully!', 'success');
      
      // Log current state
      logScormDataState();
      
    } catch (error) {
      debugLog('❌ Error saving manual data:', error);
    }
  }
  
  // Function to close manual data form
  function closeManualDataForm() {
    const form = document.querySelector('div[style*="position: fixed"]');
    if (form) {
      form.remove();
    }
  }

  // Function to display detailed iframe status
  function showIframeStatus() {
    debugLog('📺 === IFRAME STATUS REPORT ===');
    
    const allIframes = document.querySelectorAll('iframe');
    debugLog(`📊 Total iframes found: ${allIframes.length}`);
    
    if (allIframes.length === 0) {
      debugLog('❌ No iframes found on the page');
      return;
    }
    
    allIframes.forEach((iframe, index) => {
      debugLog(`\n🔍 Iframe ${index + 1}:`);
      debugLog('  - src:', iframe.src);
      debugLog('  - className:', iframe.className);
      debugLog('  - id:', iframe.id);
      debugLog('  - name:', iframe.name);
      debugLog('  - width:', iframe.width);
      debugLog('  - height:', iframe.height);
      debugLog('  - style.display:', iframe.style.display);
      debugLog('  - style.visibility:', iframe.style.visibility);
      debugLog('  - offsetWidth:', iframe.offsetWidth);
      debugLog('  - offsetHeight:', iframe.offsetHeight);
      
      // Check iframe is visible
      const rect = iframe.getBoundingClientRect();
      debugLog('  - getBoundingClientRect:', {
        top: rect.top,
        left: rect.left,
        width: rect.width,
        height: rect.height
      });
      
      // Check iframe content
      try {
        if (iframe.contentDocument) {
          debugLog('  - contentDocument available: true');
          debugLog('  - contentDocument.readyState:', iframe.contentDocument.readyState);
          debugLog('  - contentDocument.title:', iframe.contentDocument.title);
          
          if (iframe.contentWindow) {
            debugLog('  - contentWindow available: true');
            debugLog('  - contentWindow.API available:', !!iframe.contentWindow.API);
            debugLog('  - contentWindow.elucidat available:', !!iframe.contentWindow.elucidat);
          }
        } else {
          debugLog('  - contentDocument available: false');
        }
      } catch (error) {
        debugLog('  - contentDocument access error:', error.message);
      }
    });
    
    // Check window.scormIframe
    debugLog('\n🔍 Window.scormIframe status:');
    if (window.scormIframe) {
      debugLog('  - window.scormIframe is set');
      debugLog('  - src:', window.scormIframe.src);
      debugLog('  - className:', window.scormIframe.className);
    } else {
      debugLog('  - window.scormIframe is NOT set');
    }
    
    debugLog('=== END IFRAME STATUS ===');
  }

  // Function to force save progress with aggressive data capture
  function forceSaveProgress() {
    debugLog('🚀 Force saving progress with aggressive data capture...');
    
    // Step 1: Try all capture methods
    let captureSuccess = false;
    
    // Method 1: Try iframe capture
    if (captureSCORMDataFromIframe()) {
      captureSuccess = true;
      debugLog('✅ Iframe capture successful');
    }
    
    // Method 2: Try console capture
    if (captureSCORMDataFromConsole()) {
      captureSuccess = true;
      debugLog('✅ Console capture successful');
    }
    
    // Method 3: Try multi-source capture
    if (captureSCORMDataFromAnySource()) {
      captureSuccess = true;
      debugLog('✅ Multi-source capture successful');
    }
    
    // Method 4: Try manual entry if still no data
    if (!captureSuccess || Object.keys(window.scormData).length === 0) {
      debugLog('🔄 No data captured, trying manual entry...');
      manuallyEnterSCORMData();
      return;
    }
    
    // Step 2: Validate we have lesson_location
    if (!window.scormData['cmi.location'] || window.scormData['cmi.location'] === '') {
      debugLog('❌ No lesson_location captured, this is critical for resume functionality');
      showNotification('Warning: No lesson location captured. Resume may not work properly.', 'warning');
      
      // Try to get location from any available source
      if (window.scormIframe && window.scormIframe.contentWindow && window.scormIframe.contentWindow.API) {
        try {
          const location = window.scormIframe.contentWindow.API.LMSGetValue('cmi.location');
          if (location && location !== '') {
            window.scormData['cmi.location'] = location;
            debugLog('✅ Retrieved lesson_location from iframe API:', location);
          }
        } catch (error) {
          debugLog('❌ Could not retrieve lesson_location from iframe:', error.message);
        }
      }
    }
    
    // Step 3: Force save the progress
    debugLog('💾 Force saving progress with data:', window.scormData);
    const saveResult = saveSCORMProgressInternal();
    
    if (saveResult) {
      debugLog('✅ Force save completed successfully');
      showNotification('Progress force saved successfully!', 'success');
      
      // Check if we can now show resume modal
      setTimeout(() => {
        if (window.scormData['cmi.location']) {
          debugLog('🎯 Lesson location available, checking resume conditions...');
          checkResumeModalConditions();
        }
      }, 1000);
    } else {
      debugLog('❌ Force save failed');
      showNotification('Force save failed. Check console for details.', 'error');
    }
  }

  // ===================================
  // AUDIO PROGRESS TRACKING FUNCTIONS
  // ===================================

  // Initialize audio progress tracking when page loads
  document.addEventListener('DOMContentLoaded', function() {
    if (window.location.search.includes('type=audio')) {
      console.log('Audio content detected, initializing progress tracking...');
      initializeAudioProgressTracking();
    }
  });

  function initializeAudioProgressTracking() {
    const audioPlayer = document.getElementById('audio-player');
    if (!audioPlayer) return;

    let lastUpdateTime = 0;
    const updateThrottle = 500; // Only update every 500ms

    // Throttled update function to prevent excessive updates
    function throttledUpdateProgress() {
      const now = Date.now();
      if (now - lastUpdateTime >= updateThrottle) {
        updateProgressDisplay();
        lastUpdateTime = now;
      }
    }

    // Update progress display elements with throttling
    audioPlayer.addEventListener('timeupdate', throttledUpdateProgress);
    audioPlayer.addEventListener('loadedmetadata', updateProgressDisplay);
    audioPlayer.addEventListener('play', updateProgressDisplay);
    audioPlayer.addEventListener('pause', updateProgressDisplay);
    audioPlayer.addEventListener('seeked', updateProgressDisplay);

    // Update immediately when function is called
    updateProgressDisplay();

    // Also update after a short delay to ensure audio is loaded
    setTimeout(() => {
      updateProgressDisplay();
    }, 100);

    // Set up periodic updates with longer interval
    setInterval(() => {
      updateProgressDisplay();
    }, 2000); // Update every 2 seconds instead of 1 second

    // Also check if progress elements still exist in DOM
    setInterval(() => {
      const progressContainer = document.querySelector('.audio-progress-container');
      if (!progressContainer) {
        console.warn('⚠️ Audio progress container not found in DOM!');
      } else {
        const progressBar = document.querySelector('.audio-progress-bar-fill');
        const progressText = document.querySelector('.audio-progress-text');
        if (!progressBar || !progressText) {
          console.warn('⚠️ Some progress elements missing from DOM!');
        }
      }
    }, 5000); // Check every 5 seconds instead of 2 seconds

    // Load existing progress if available
    loadExistingProgress();
  }

  function updateProgressDisplay() {
    const audioPlayer = document.getElementById('audio-player');
    if (!audioPlayer) return;

    const currentTime = audioPlayer.currentTime || 0;
    const duration = audioPlayer.duration || 0;
    
    // Only log occasionally to prevent spam
    if (Math.random() < 0.1) { // 10% chance to log
      console.log('Audio progress update:', { currentTime, duration, readyState: audioPlayer.readyState });
    }
    
    // Always update time displays, even if duration is 0
    const currentTimeEl = document.getElementById('current-time');
    const durationEl = document.getElementById('duration');
    
    if (currentTimeEl) {
      currentTimeEl.textContent = formatTime(currentTime);
    }
    
    if (durationEl) {
      durationEl.textContent = formatTime(duration);
    }
    
    // Update progress elements only if duration is available
    if (duration && duration > 0) {
      const percentage = Math.round((currentTime / duration) * 100);
      
      // Update progress bar
      const progressBar = document.querySelector('.audio-progress-bar-fill');
      if (progressBar) {
        progressBar.style.width = percentage + '%';
      }
      
      // Update progress text
      const progressText = document.querySelector('.audio-progress-text');
      if (progressText) {
        progressText.textContent = percentage + '% Complete';
      }
      
      // Update percentage display
      const percentageEl = document.getElementById('listened-percentage');
      if (percentageEl) {
        percentageEl.textContent = percentage;
      }
      
      // Update status
      const statusEl = document.getElementById('completion-status');
      if (statusEl) {
        if (percentage >= 80) {
          statusEl.textContent = 'Completed';
          statusEl.style.color = '#28a745';
        } else if (percentage > 0) {
          statusEl.textContent = 'In Progress';
          statusEl.style.color = '#fd7e14';
        } else {
          statusEl.textContent = 'Not Started';
          statusEl.style.color = '#666';
        }
      }

      // Update playback status based on audio element state
      const playbackStatusEl = document.getElementById('audio-playback-status');
      if (playbackStatusEl) {
        if (audioPlayer.paused) {
          if (audioPlayer.currentTime === 0) {
            playbackStatusEl.textContent = 'Not Started';
            playbackStatusEl.style.color = '#666';
          } else {
            playbackStatusEl.textContent = 'Paused';
            playbackStatusEl.style.color = '#fd7e14';
          }
        } else {
          playbackStatusEl.textContent = 'Playing';
          playbackStatusEl.style.color = '#28a745';
        }
      }
    } else {
      // Duration not available yet, show initial state
      const progressBar = document.querySelector('.audio-progress-bar-fill');
      if (progressBar) {
        progressBar.style.width = '0%';
      }
      
      const progressText = document.querySelector('.audio-progress-text');
      if (progressText) {
        progressText.textContent = '0% Complete';
      }
      
      const percentageEl = document.getElementById('listened-percentage');
      if (percentageEl) {
        percentageEl.textContent = '0';
      }
      
      const statusEl = document.getElementById('completion-status');
      if (statusEl) {
        statusEl.textContent = 'Not Started';
        statusEl.style.color = '#666';
      }

      const playbackStatusEl = document.getElementById('audio-playback-status');
      if (playbackStatusEl) {
        playbackStatusEl.textContent = 'Not Started';
        playbackStatusEl.style.color = '#666';
      }
    }
  }

  function formatTime(seconds) {
    if (isNaN(seconds)) return '0:00';
    
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
  }

  function loadExistingProgress() {
    // This function would load existing progress from the database
    // For now, we'll just initialize with default values
    console.log('Loading existing audio progress...');
  }



  // Show notification function (if not already defined)
  function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `audio-completion-notification ${type}`;
    notification.innerHTML = `
      <div class="notification-content">
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <span>${message}</span>
      </div>
    `;
    
    // Add to the page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
      notification.classList.add('show');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 3000);
  }
  </script>
</body>
</html>