/**
 * Document Progress Tracker
 * Tracks user progress when viewing documents in the content viewer
 */

class DocumentProgressTracker {
    constructor() {
        this.isInitialized = false;
        this.courseId = null;
        this.moduleId = null;
        this.contentId = null;
        this.documentPackageId = null;
        this.userId = null;
        this.clientId = null;
        
        // Progress tracking variables
        this.currentPage = 1;
        this.totalPages = 0;
        this.pagesViewed = new Set([1]); // Initialize with page 1 as viewed
        this.startTime = null;
        this.lastActivityTime = null;
        this.timeSpent = 0;
        this.isTracking = false;
        this.completionThreshold = 80; // 80% of pages viewed to mark as complete
        
        console.log('🔧 DocumentProgressTracker constructor - pagesViewed initialized:', Array.from(this.pagesViewed));
        
        // Auto-save settings
        this.autoSaveInterval = 10000; // Auto-save every 10 seconds
        this.autoSaveTimer = null;
        
        // UI elements
        this.progressBar = null;
        this.progressText = null;
        this.pageCounter = null;
        this.timeCounter = null;
        
        this.init();
    }

    init() {
        try {
            console.log('🔄 Initializing Document Progress Tracker...');
            console.log('📍 Current URL:', window.location.href);
            
            // Get user data from session storage or window
            this.getUserData();
            
            // Get URL parameters
            this.getUrlParameters();
            
            console.log('📋 Initialization parameters:', {
                courseId: this.courseId,
                moduleId: this.moduleId,
                contentId: this.contentId,
                documentPackageId: this.documentPackageId,
                userId: this.userId,
                clientId: this.clientId
            });
            
            // Initialize UI elements
            this.initializeUI();
            
            // Start tracking if we have all required data
            if (this.courseId && (this.contentId || this.prerequisiteId)) {
                console.log('✅ Required parameters found, starting tracking...');
                this.startTracking();
            } else {
                console.warn('⚠️ Missing required parameters for document tracking');
                console.warn('Missing:', {
                    courseId: !this.courseId,
                    contentId: !this.contentId,
                    prerequisiteId: !this.prerequisiteId
                });
            }
            
            this.isInitialized = true;
            console.log('✅ Document Progress Tracker initialized');
            
        } catch (error) {
            console.error('❌ Error initializing Document Progress Tracker:', error);
            console.error('Error stack:', error.stack);
        }
    }

    getUserData() {
        // Try to get user data from various sources
        if (window.userData && window.userData.id) {
            this.userId = window.userData.id;
            this.clientId = window.userData.client_id;
        } else {
            // Try session storage
            const storedUserData = sessionStorage.getItem('user_data');
            if (storedUserData) {
                const userData = JSON.parse(storedUserData);
                this.userId = userData.id;
                this.clientId = userData.client_id;
            }
        }
        
        console.log('👤 User data retrieved:', { userId: this.userId, clientId: this.clientId });
    }

    getUrlParameters() {
        const urlParams = new URLSearchParams(window.location.search);
        this.courseId = urlParams.get('course_id');
        this.moduleId = urlParams.get('module_id');
        this.contentId = urlParams.get('content_id');
        this.prerequisiteId = urlParams.get('prerequisite_id');
        this.documentPackageId = urlParams.get('document_package_id');
        
        console.log('📋 URL parameters:', {
            courseId: this.courseId,
            moduleId: this.moduleId,
            contentId: this.contentId,
            prerequisiteId: this.prerequisiteId,
            documentPackageId: this.documentPackageId
        });
    }

    initializeUI() {
        // Create progress tracking UI
        this.createProgressUI();
        
        // Set up document event listeners
        this.setupDocumentListeners();
        
        // Set up page visibility listeners
        this.setupVisibilityListeners();
        
        console.log('🎨 UI elements initialized');
    }

    createProgressUI() {
        // Check if progress UI already exists
        if (document.getElementById('document-progress-container')) {
            return;
        }

        // Create progress container
        const progressContainer = document.createElement('div');
        progressContainer.id = 'document-progress-container';
        progressContainer.className = 'document-progress-container';
        
        progressContainer.innerHTML = `
            <div class="document-progress-header">
                <div class="progress-info">
                    <span class="progress-label">Document Progress:</span>
                    <span id="document-progress-text" class="progress-text">0%</span>
                    <span id="document-page-counter" class="page-counter">Page 1 of 1</span>
                    <span id="document-status" class="status-badge status-not-started">Not Started</span>
                </div>
                <div class="progress-actions">
                    <button id="mark-complete-btn" class="btn btn-sm btn-success" style="display: none;">
                        <i class="fas fa-check"></i> Mark Complete
                    </button>
                    <button id="save-progress-btn" class="btn btn-sm btn-primary">
                        <i class="fas fa-save"></i> Save Progress
                    </button>
                </div>
            </div>
            <div class="progress-bar-container">
                <div id="document-progress-bar" class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>
            <div class="document-stats">
                <span id="document-time-counter" class="time-counter">Time: 0:00</span>
                <span class="completion-threshold">Complete at ${this.completionThreshold}%</span>
            </div>

        `;
        
        // Insert at the top of the content viewer
        const viewerHeader = document.querySelector('.viewer-header');
        if (viewerHeader) {
            viewerHeader.parentNode.insertBefore(progressContainer, viewerHeader.nextSibling);
            console.log('✅ Progress UI inserted after viewer header');
        } else {
            document.body.insertBefore(progressContainer, document.body.firstChild);
            console.log('✅ Progress UI inserted at body start');
        }
        
        // Force the UI to be visible
        progressContainer.style.display = 'block';
        progressContainer.style.visibility = 'visible';
        progressContainer.style.opacity = '1';
        
        console.log('🔍 Progress UI element:', progressContainer);
        console.log('🔍 Progress UI HTML:', progressContainer.outerHTML);
        
        // Get references to UI elements
        this.progressBar = document.getElementById('document-progress-bar');
        this.progressText = document.getElementById('document-progress-text');
        this.pageCounter = document.getElementById('document-page-counter');
        this.timeCounter = document.getElementById('document-time-counter');
        this.statusBadge = document.getElementById('document-status');
        
        // Set up button event listeners
        document.getElementById('save-progress-btn').addEventListener('click', () => this.saveProgress());
        document.getElementById('mark-complete-btn').addEventListener('click', () => this.markComplete());
        
        console.log('✅ Progress UI created');
        
        // Verify UI is actually visible
        setTimeout(() => {
            if (!document.getElementById('document-progress-container') || 
                document.getElementById('document-progress-container').style.display === 'none') {
                console.warn('⚠️ Progress UI not visible, creating fallback...');
                this.createFallbackUI();
            }
        }, 1000);
    }

    createFallbackUI() {
        console.log('🔄 Creating fallback progress UI...');
        
        // Create a simple floating progress bar
        const fallbackUI = document.createElement('div');
        fallbackUI.id = 'fallback-progress-ui';
        fallbackUI.style.cssText = `
            position: fixed;
            top: 10px;
            right: 10px;
            background: white;
            border: 2px solid #6a0dad;
            border-radius: 8px;
            padding: 15px;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-family: Arial, sans-serif;
        `;
        
        fallbackUI.innerHTML = `
            <h4 style="margin: 0 0 10px 0; color: #6a0dad;">Document Progress</h4>
            <div style="margin-bottom: 10px;">
                <strong>Current Page:</strong> <span id="fallback-current-page">1</span> / <span id="fallback-total-pages">${this.totalPages}</span>
            </div>
            <div style="margin-bottom: 10px;">
                <strong>Pages Viewed:</strong> <span id="fallback-pages-viewed">0</span>
            </div>

            <button onclick="this.parentElement.remove()" style="position: absolute; top: 5px; right: 5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px;">×</button>
        `;
        
        document.body.appendChild(fallbackUI);
        console.log('✅ Fallback UI created');
    }

    setupDocumentListeners() {
        // Listen for iframe load events to detect page changes
        const iframe = document.querySelector('iframe.viewer-frame');
        if (iframe) {
            iframe.addEventListener('load', () => {
                this.handleDocumentLoad(iframe);
            });
            
            // Try to set up content document listeners
            this.setupIframeListeners(iframe);
        }
        
        // Listen for PDF.js events if available
        this.setupPDFListeners();
        
        // Set up scroll listeners for progress estimation
        this.setupScrollListeners();
        
        console.log('👂 Document listeners set up');
    }

    setupIframeListeners(iframe) {
        try {
            if (iframe.contentDocument) {
                // Listen for scroll events in iframe
                iframe.contentWindow.addEventListener('scroll', () => {
                    this.handleScroll(iframe.contentWindow);
                });
                
                // Listen for page navigation events
                iframe.contentWindow.addEventListener('hashchange', () => {
                    this.handlePageChange();
                });
                
                // For PDFs, also check for URL changes that indicate page changes
                let lastUrl = iframe.contentWindow.location.href;
                setInterval(() => {
                    try {
                        if (iframe.contentWindow.location.href !== lastUrl) {
                            lastUrl = iframe.contentWindow.location.href;
                            console.log('📄 PDF URL changed:', lastUrl);
                            this.handlePageChange();
                        }
                    } catch (e) {
                        // Cross-origin restriction
                    }
                }, 1000);
                
            }
        } catch (error) {
            // Cross-origin restrictions may prevent access
            console.log('⚠️ Cannot access iframe content (cross-origin)');
            
            // Fallback: try to detect page changes by monitoring iframe src changes
            let lastSrc = iframe.src;
            setInterval(() => {
                if (iframe.src !== lastSrc) {
                    lastSrc = iframe.src;
                    console.log('📄 Iframe src changed:', lastSrc);
                    this.handlePageChange();
                }
            }, 1000);
        }
        
        // PDF-specific page detection
        if (iframe.src && iframe.src.includes('.pdf')) {
            console.log('📄 PDF detected, setting up PDF-specific listeners');
            this.setupPDFPageDetection(iframe);
        }
    }
    
    setupPDFPageDetection(iframe) {
        // Try multiple methods to detect PDF page changes
        
        // Method 1: Monitor iframe content for PDF.js events
        try {
            iframe.addEventListener('load', () => {
                console.log('📄 PDF iframe loaded, attempting to detect pages');
                
                // Try to access PDF.js if available
                setTimeout(() => {
                    try {
                        if (iframe.contentWindow && iframe.contentWindow.PDFViewerApplication) {
                            console.log('✅ PDF.js detected, setting up page change listener');
                            this.setupPDFJSPageDetection(iframe);
                        }
                    } catch (e) {
                        console.log('⚠️ Cannot access PDF.js:', e.message);
                    }
                }, 2000);
            });
        } catch (error) {
            console.log('⚠️ Cannot add PDF load listener:', error.message);
        }
        
        // Method 2: Simulate page changes based on time spent (fallback)
        console.log('📄 Setting up fallback PDF page detection');
        this.startFallbackPDFPageDetection();
    }
    
    setupPDFJSPageDetection(iframe) {
        try {
            const pdfApp = iframe.contentWindow.PDFViewerApplication;
            
            // Listen for page changes
            pdfApp.eventBus.on('pagechanging', (evt) => {
                const pageNumber = evt.pageNumber;
                console.log('📄 PDF.js page changing to:', pageNumber);
                this.handlePageChange(pageNumber);
            });
            
            // Listen for page changes
            pdfApp.eventBus.on('pagechanged', (evt) => {
                const pageNumber = evt.pageNumber;
                console.log('📄 PDF.js page changed to:', pageNumber);
                this.handlePageChange(pageNumber);
            });
            
            console.log('✅ PDF.js page change listeners set up');
        } catch (error) {
            console.log('❌ Error setting up PDF.js listeners:', error.message);
        }
    }
    
    startFallbackPDFPageDetection() {
        // Fallback: estimate page changes based on time spent
        let lastPage = 1;
        let timeOnCurrentPage = 0;
        
        setInterval(() => {
            if (this.isTracking) {
                timeOnCurrentPage += 1;
                
                // Estimate page change based on time (every 15 seconds = new page for faster detection)
                const estimatedPage = Math.min(this.totalPages, Math.floor(timeOnCurrentPage / 15) + 1);
                
                if (estimatedPage > lastPage && estimatedPage <= this.totalPages) {
                    console.log('📄 Fallback: Estimated page change to:', estimatedPage, 'based on time spent');
                    this.handlePageChange(estimatedPage);
                    lastPage = estimatedPage;
                    timeOnCurrentPage = 0;
                }
            }
        }, 1000);
        
        console.log('📄 Fallback PDF page detection started');
    }

    setupPDFListeners() {
        // Listen for PDF.js specific events if available
        window.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'pdf-page-change') {
                this.handlePageChange(event.data.pageNumber, event.data.totalPages);
            }
        });
    }

    setupScrollListeners() {
        // Throttle scroll events to improve performance
        let scrollTimeout;
        const throttledScrollHandler = () => {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(() => {
                this.handleScroll(window);
            }, 100); // Throttle to every 100ms
        };
        
        // Listen for scroll events on the main document
        window.addEventListener('scroll', throttledScrollHandler, { passive: true });
        
        // Also listen for scroll events on the iframe if available
        const iframe = document.querySelector('iframe.viewer-frame');
        if (iframe) {
            try {
                const iframeScrollHandler = () => {
                    if (scrollTimeout) {
                        clearTimeout(scrollTimeout);
                    }
                    scrollTimeout = setTimeout(() => {
                        this.handleScroll(iframe.contentWindow);
                    }, 100);
                };
                
                iframe.addEventListener('scroll', iframeScrollHandler, { passive: true });
                console.log('✅ Iframe scroll listener added');
            } catch (error) {
                console.log('⚠️ Cannot add iframe scroll listener (cross-origin):', error.message);
            }
        }
        
        // Add wheel event listener for better scroll detection
        window.addEventListener('wheel', throttledScrollHandler, { passive: true });
        
        // Add touch events for mobile devices
        window.addEventListener('touchmove', throttledScrollHandler, { passive: true });
        
        console.log('✅ Scroll listeners set up');
    }

    setupVisibilityListeners() {
        // Track when user switches tabs or minimizes window
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseTracking();
            } else {
                this.resumeTracking();
            }
        });
        
        // Save progress when user leaves the page
        window.addEventListener('beforeunload', () => {
            this.saveProgress();
        });
        
        // Handle focus/blur events
        window.addEventListener('focus', () => this.resumeTracking());
        window.addEventListener('blur', () => this.pauseTracking());
    }

    async startTracking() {
        if (this.isTracking) {
            console.log('⚠️ Document tracking already started');
            return;
        }

        try {
            console.log('🚀 Starting document tracking...');
            console.log('📋 Tracking parameters:', {
                courseId: this.courseId,
                moduleId: this.moduleId,
                contentId: this.contentId,
                prerequisiteId: this.prerequisiteId,
                documentPackageId: this.documentPackageId,
                totalPages: this.totalPages
            });
            
            // Estimate total pages from document
            this.estimateTotalPages();
            
            // Start tracking on server
            const response = await fetch('/Unlockyourskills/api/document-progress/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    course_id: this.courseId,
                    module_id: this.moduleId,
                    content_id: this.contentId,
                    prerequisite_id: this.prerequisiteId,
                    document_package_id: this.documentPackageId,
                    total_pages: this.totalPages
                })
            });
            
            const result = await response.json();
            console.log('📡 Server response:', result);
            
            if (result.success) {
                console.log('✅ Document tracking started on server');
                
                // Load existing progress if any
                this.loadExistingProgress(result.data);
                
                // Start client-side tracking
                this.isTracking = true;
                this.startTime = Date.now();
                this.lastActivityTime = Date.now();
                
                // Start auto-save timer
                this.startAutoSave();
                
                // Update UI
                this.updateUI();
                
                console.log('✅ Document tracking started successfully');
            } else {
                throw new Error(result.error || 'Failed to start document tracking');
            }
            
        } catch (error) {
            console.error('❌ Error starting document tracking:', error);
            this.showNotification('Failed to start document tracking', 'error');
        }
    }

    loadExistingProgress(progressData) {
        if (progressData) {
            this.currentPage = parseInt(progressData.current_page) || 1;
            this.totalPages = parseInt(progressData.total_pages) || this.totalPages;
            this.timeSpent = parseInt(progressData.time_spent) || 0;
            
            // Load pages viewed
            if (progressData.pages_viewed) {
                const pagesViewed = Array.isArray(progressData.pages_viewed) 
                    ? progressData.pages_viewed 
                    : JSON.parse(progressData.pages_viewed || '[]');
                this.pagesViewed = new Set(pagesViewed);
            }
            
            // Ensure page 1 is always marked as viewed
            this.pagesViewed.add(1);
            
            console.log('📚 Loaded existing progress - pagesViewed after ensuring page 1:', Array.from(this.pagesViewed));
            
            console.log('📚 Loaded existing progress:', {
                currentPage: this.currentPage,
                totalPages: this.totalPages,
                pagesViewed: this.pagesViewed.size,
                timeSpent: this.timeSpent
            });
        }
    }

    estimateTotalPages() {
        // Try to get total pages from various sources
        const iframe = document.querySelector('iframe.viewer-frame');
        
        if (iframe && iframe.src.includes('.pdf')) {
            // For PDF files, we'll try to estimate or get from PDF.js
            this.totalPages = this.estimatePDFPages(iframe);
        } else {
            // For other document types, use a default estimation
            this.totalPages = 1;
        }
        
        console.log('📄 Estimated total pages:', this.totalPages);
    }

    estimatePDFPages(iframe) {
        // This is a basic estimation - in a real implementation,
        // you might want to use PDF.js to get the actual page count
        try {
            // Try to get page count from PDF.js if available
            if (iframe.contentWindow && iframe.contentWindow.PDFViewerApplication) {
                return iframe.contentWindow.PDFViewerApplication.pagesCount || 1;
            }
        } catch (error) {
            console.log('Cannot access PDF page count');
        }
        
        // Default estimation
        return 10; // Default to 10 pages for PDFs
    }

    handleDocumentLoad(iframe) {
        console.log('📄 Document loaded in iframe');
        
        // Update activity time
        this.lastActivityTime = Date.now();
        
        // Try to get updated page information
        this.estimateTotalPages();
        
        // Update UI
        this.updateUI();
    }

    handlePageChange(pageNumber = null, totalPages = null) {
        if (pageNumber) {
            this.currentPage = pageNumber;
        } else {
            // Estimate current page based on scroll or other factors
            this.currentPage = Math.max(1, this.currentPage);
        }
        
        if (totalPages) {
            this.totalPages = totalPages;
        }
        
        // Mark current page as viewed
        this.pagesViewed.add(this.currentPage);
        
        // Update activity time
        this.lastActivityTime = Date.now();
        
        console.log('📖 Page changed to:', this.currentPage, 'Pages viewed:', Array.from(this.pagesViewed));
        
        // Update UI
        this.updateUI();
        
        // Auto-save progress
        this.scheduleAutoSave();
    }

    handleScroll(windowOrFrame) {
        // Update activity time
        this.lastActivityTime = Date.now();
        
        // Estimate page progress based on scroll position
        try {
            const scrollTop = windowOrFrame.scrollY || windowOrFrame.pageYOffset || 0;
            const scrollHeight = windowOrFrame.document.documentElement.scrollHeight || 1;
            const clientHeight = windowOrFrame.innerHeight || windowOrFrame.document.documentElement.clientHeight || 1;
            
            const scrollPercentage = Math.min(100, (scrollTop + clientHeight) / scrollHeight * 100);
            
            // Estimate current page based on scroll with more aggressive detection
            const estimatedPage = Math.max(1, Math.min(this.totalPages, Math.ceil((scrollPercentage / 100) * this.totalPages)));
            
            // Only log if there's a significant change to avoid spam
            if (Math.abs(estimatedPage - this.currentPage) >= 1) {
                console.log('📖 Scroll tracking:', {
                    scrollTop,
                    scrollHeight,
                    clientHeight,
                    scrollPercentage: scrollPercentage.toFixed(1),
                    estimatedPage,
                    currentPage: this.currentPage,
                    totalPages: this.totalPages
                });
            }
            
            // Mark all pages up to the estimated page as viewed
            if (estimatedPage > 0 && estimatedPage <= this.totalPages) {
                let pagesAdded = false;
                
                for (let page = 1; page <= estimatedPage; page++) {
                    if (!this.pagesViewed.has(page)) {
                        this.pagesViewed.add(page);
                        pagesAdded = true;
                    }
                }
                
                if (estimatedPage !== this.currentPage || pagesAdded) {
                    this.currentPage = estimatedPage;
                    console.log('📄 Page changed to:', this.currentPage, 'Pages viewed:', Array.from(this.pagesViewed));
                    
                    this.updateUI();
                    this.scheduleAutoSave();
                }
            }
            
        } catch (error) {
            console.error('❌ Scroll handling error:', error);
        }
    }

    startAutoSave() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
        }
        
        this.autoSaveTimer = setInterval(() => {
            if (this.isTracking) {
                this.saveProgress(false); // Silent save
            }
        }, this.autoSaveInterval);
        
        console.log('⏰ Auto-save timer started');
    }

    scheduleAutoSave() {
        // Debounced auto-save for frequent events
        clearTimeout(this.scheduleAutoSaveTimeout);
        this.scheduleAutoSaveTimeout = setTimeout(() => {
            this.saveProgress(false);
        }, 2000);
    }

    async saveProgress(showNotification = true) {
        if (!this.isTracking) {
            return;
        }

        try {
            // Calculate current time spent
            const currentTime = Date.now();
            const sessionTime = this.lastActivityTime ? Math.floor((currentTime - this.lastActivityTime) / 1000) : 0;
            this.timeSpent += sessionTime;
            this.lastActivityTime = currentTime;
            
            // Calculate viewed percentage
            const viewedPercentage = this.totalPages > 0 ? (this.pagesViewed.size / this.totalPages) * 100 : 0;
            
            console.log('📊 Saving progress:', {
                currentPage: this.currentPage,
                pagesViewed: Array.from(this.pagesViewed),
                totalPages: this.totalPages,
                viewedPercentage: viewedPercentage,
                timeSpent: this.timeSpent,
                pagesViewedSize: this.pagesViewed.size
            });
            
            // Debug: Check if pagesViewed is actually being populated
            if (this.pagesViewed.size === 0) {
                console.warn('⚠️ Warning: pagesViewed is empty! This suggests page tracking is not working.');
                console.warn('Current state:', {
                    currentPage: this.currentPage,
                    totalPages: this.totalPages,
                    isTracking: this.isTracking
                });
            }
            
            const progressData = {
                course_id: this.courseId,
                content_id: this.contentId,
                prerequisite_id: this.prerequisiteId,
                current_page: this.currentPage,
                pages_viewed: Array.from(this.pagesViewed),
                time_spent: this.timeSpent, // Send total time spent, not just session time
                viewed_percentage: viewedPercentage
            };
            
            const response = await fetch('/Unlockyourskills/api/document-progress/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify(progressData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (showNotification) {
                    this.showNotification('Progress saved successfully', 'success');
                }
                console.log('💾 Progress saved:', progressData);
                
                // Update UI with latest data
                this.updateUI();
                
                // Check if document should be marked as complete
                if (viewedPercentage >= this.completionThreshold && !result.data.is_completed) {
                    this.showMarkCompleteButton();
                }
            } else {
                throw new Error(result.error || 'Failed to save progress');
            }
            
        } catch (error) {
            console.error('❌ Error saving progress:', error);
            if (showNotification) {
                this.showNotification('Failed to save progress', 'error');
            }
        }
    }

    async markComplete() {
        try {
            const response = await fetch('/Unlockyourskills/api/document-progress/complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'include',
                body: JSON.stringify({
                    course_id: this.courseId,
                    content_id: this.contentId,
                    prerequisite_id: this.prerequisiteId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('Document marked as complete!', 'success');
                console.log('✅ Document marked as complete');
                
                // Update UI
                this.updateUI();
                this.hideMarkCompleteButton();
                
                // Notify parent window if in iframe
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'document_completed',
                        courseId: this.courseId,
                        contentId: this.contentId
                    }, '*');
                }
            } else {
                throw new Error(result.error || 'Failed to mark document as complete');
            }
            
        } catch (error) {
            console.error('❌ Error marking document as complete:', error);
            this.showNotification('Failed to mark document as complete', 'error');
        }
    }



    /**
     * Get current progress summary
     */
    getProgressSummary() {
        return {
            currentPage: this.currentPage,
            totalPages: this.totalPages,
            pagesViewed: Array.from(this.pagesViewed),
            viewedPercentage: this.totalPages > 0 ? (this.pagesViewed.size / this.totalPages) * 100 : 0,
            timeSpent: this.timeSpent,
            isCompleted: this.totalPages > 0 ? (this.pagesViewed.size / this.totalPages) * 100 >= this.completionThreshold : false
        };
    }

    /**
     * Simulate page change for testing PDF page detection
     */
    simulatePageChange(pageNumber) {
        console.log('🎭 Simulating page change to:', pageNumber);
        
        if (pageNumber > 0 && pageNumber <= this.totalPages) {
            this.handlePageChange(pageNumber);
            return true;
        }
        return false;
    }
    
    /**
     * Force update UI with current progress
     */
    forceUpdateUI() {
        console.log('🔄 Force updating UI...');
        this.updateUI();
        
        // Show current state
        console.log('Current state:', {
            currentPage: this.currentPage,
            totalPages: this.totalPages,
            pagesViewed: Array.from(this.pagesViewed),
            viewedPercentage: this.totalPages > 0 ? (this.pagesViewed.size / this.totalPages) * 100 : 0
        });
    }

    updateUI() {
        if (!this.progressText || !this.pageCounter || !this.timeCounter) {
            return;
        }
        
        // Calculate viewed percentage
        const viewedPercentage = this.totalPages > 0 ? (this.pagesViewed.size / this.totalPages) * 100 : 0;
        
        // Update progress text and bar
        this.progressText.textContent = `${Math.round(viewedPercentage)}%`;
        
        if (this.progressBar) {
            const progressFill = this.progressBar.querySelector('.progress-fill');
            if (progressFill) {
                progressFill.style.width = `${viewedPercentage}%`;
            }
        }
        
        // Update page counter
        this.pageCounter.textContent = `Page ${this.currentPage} of ${this.totalPages}`;
        
        // Update time counter
        const hours = Math.floor(this.timeSpent / 3600);
        const minutes = Math.floor((this.timeSpent % 3600) / 60);
        const seconds = this.timeSpent % 60;
        
        if (hours > 0) {
            this.timeCounter.textContent = `Time: ${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        } else {
            this.timeCounter.textContent = `Time: ${minutes}:${seconds.toString().padStart(2, '0')}`;
        }
        
        // Update completion status
        this.updateCompletionStatus(viewedPercentage);
    }
    
    updateCompletionStatus(viewedPercentage) {
        if (!this.statusBadge) return;
        
        let status = 'not_started';
        let statusText = 'Not Started';
        let statusClass = 'status-not-started';
        
        if (viewedPercentage >= this.completionThreshold) {
            status = 'completed';
            statusText = 'Completed';
            statusClass = 'status-completed';
        } else if (viewedPercentage > 0) {
            status = 'in_progress';
            statusText = 'In Progress';
            statusClass = 'status-in-progress';
        }
        
        // Update status badge
        this.statusBadge.textContent = statusText;
        this.statusBadge.className = `status-badge ${statusClass}`;
        
        // Show/hide mark complete button
        if (status === 'completed') {
            this.showMarkCompleteButton();
        } else {
            this.hideMarkCompleteButton();
        }
        
        console.log('📊 Status updated:', { status, statusText, viewedPercentage });
    }

    showMarkCompleteButton() {
        const button = document.getElementById('mark-complete-btn');
        if (button) {
            button.style.display = 'inline-block';
        }
    }

    hideMarkCompleteButton() {
        const button = document.getElementById('mark-complete-btn');
        if (button) {
            button.style.display = 'none';
        }
    }

    pauseTracking() {
        if (this.isTracking && this.lastActivityTime) {
            // Add time spent before pausing
            const currentTime = Date.now();
            this.timeSpent += Math.floor((currentTime - this.lastActivityTime) / 1000);
            this.lastActivityTime = null;
            
            console.log('⏸️ Tracking paused');
        }
    }

    resumeTracking() {
        if (this.isTracking && !this.lastActivityTime) {
            this.lastActivityTime = Date.now();
            console.log('▶️ Tracking resumed');
        }
    }

    stopTracking() {
        this.isTracking = false;
        
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
            this.autoSaveTimer = null;
        }
        
        // Final save
        this.saveProgress(false);
        
        console.log('⏹️ Document tracking stopped');
    }

    showNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `document-notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Public methods for external use
    getCurrentProgress() {
        return {
            currentPage: this.currentPage,
            totalPages: this.totalPages,
            pagesViewed: Array.from(this.pagesViewed),
            viewedPercentage: this.totalPages > 0 ? (this.pagesViewed.size / this.totalPages) * 100 : 0,
            timeSpent: this.timeSpent,
            isTracking: this.isTracking
        };
    }

    setPageInfo(currentPage, totalPages) {
        this.currentPage = currentPage;
        this.totalPages = totalPages;
        this.pagesViewed.add(currentPage);
        this.updateUI();
        this.scheduleAutoSave();
    }
}

// Initialize document progress tracker immediately when script loads
(function() {
    console.log('📄 Document progress script loaded, checking content type...');
    
    // Check if we're on a document content page
    const urlParams = new URLSearchParams(window.location.search);
    const contentType = urlParams.get('type');
    
    if (contentType === 'document') {
        console.log('📄 Document content detected, initializing progress tracker immediately');
        window.documentProgressTracker = new DocumentProgressTracker();
    } else {
        console.log('📄 Not document content, waiting for DOM ready...');
        // Fallback to DOM ready if not document content
        document.addEventListener('DOMContentLoaded', function() {
            if (contentType === 'document') {
                console.log('📄 Document content detected on DOM ready, initializing progress tracker');
                window.documentProgressTracker = new DocumentProgressTracker();
            }
        });
    }
})();

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DocumentProgressTracker;
}
