/**
 * SCORM Player for Unlock Your Skills
 * 
 * This player provides a complete SCORM content viewing experience with:
 * - SCORM wrapper integration
 * - Progress tracking
 * - Resume functionality
 * - Error handling
 * - User interface controls
 */

class SCORMPlayer {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.options = {
            courseId: options.courseId || null,
            moduleId: options.moduleId || null,
            contentId: options.contentId || null,
            scormUrl: options.scormUrl || '',
            autoInitialize: options.autoInitialize !== false,
            showControls: options.showControls !== false,
            debugMode: options.debugMode || false,
            ...options
        };
        
        // SCORM wrapper instance
        this.scormWrapper = null;
        
        // Player state
        this.isLoaded = false;
        this.isPlaying = false;
        this.currentTime = 0;
        this.totalTime = 0;
        
        // Progress tracking
        this.progressTracker = window.progressTracker || null;
        
        // UI elements
        this.iframe = null;
        this.controls = null;
        this.progressBar = null;
        this.statusDisplay = null;
        
        // Event listeners
        this.eventListeners = new Map();
        
        // Initialize if auto-initialize is enabled
        if (this.options.autoInitialize) {
            this.init();
        }
    }

    /**
     * Initialize the SCORM player
     */
    async init() {
        try {
            this.log('Initializing SCORM player');
            
            // Create UI elements
            this.createUI();
            
            // Initialize SCORM wrapper
            await this.initSCORMWrapper();
            
            // Load SCORM content
            await this.loadContent();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Setup progress tracking
            this.setupProgressTracking();
            
            this.isLoaded = true;
            this.log('SCORM player initialized successfully');
            
            // Trigger initialization event
            this.triggerEvent('playerReady', { player: this });
            
        } catch (error) {
            this.log('Failed to initialize SCORM player', error);
            this.showError('Failed to initialize SCORM player: ' + error.message);
        }
    }

    /**
     * Create the player UI
     */
    createUI() {
        // Clear container
        this.container.innerHTML = '';
        
        // Create main player structure
        const playerHTML = `
            <div class="scorm-player" style="width: 100%; height: 100%;">
                <div class="scorm-content" style="width: 100%; height: calc(100% - 60px);">
                    <iframe id="scorm-iframe" 
                            style="width: 100%; height: 100%; border: none;"
                            allowfullscreen>
                    </iframe>
                </div>
                
                ${this.options.showControls ? `
                <div class="scorm-controls" style="height: 60px; background: #f8f9fa; border-top: 1px solid #dee2e6; padding: 10px; display: flex; align-items: center; gap: 15px;">
                    <div class="scorm-status" style="flex: 1;">
                        <div class="status-text" style="font-size: 14px; color: #6c757d; margin-bottom: 5px;">
                            Status: <span class="status-value">Initializing...</span>
                        </div>
                        <div class="progress-container" style="width: 100%;">
                            <div class="progress-bar" style="width: 100%; height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
                                <div class="progress-fill" style="height: 100%; background: #007bff; width: 0%; transition: width 0.3s ease;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="scorm-actions" style="display: flex; gap: 10px;">
                        <button class="btn btn-sm btn-outline-primary scorm-save" style="padding: 5px 15px;">
                            <i class="fas fa-save"></i> Save Progress
                        </button>
                        <button class="btn btn-sm btn-outline-secondary scorm-resume" style="padding: 5px 15px;">
                            <i class="fas fa-play"></i> Resume
                        </button>
                        <button class="btn btn-sm btn-outline-success scorm-complete" style="padding: 5px 15px;">
                            <i class="fas fa-check"></i> Mark Complete
                        </button>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        
        this.container.innerHTML = playerHTML;
        
        // Get references to UI elements
        this.iframe = this.container.querySelector('#scorm-iframe');
        this.controls = this.container.querySelector('.scorm-controls');
        this.progressBar = this.container.querySelector('.progress-fill');
        this.statusDisplay = this.container.querySelector('.status-value');
        
        // Hide controls if not needed
        if (!this.options.showControls) {
            this.controls.style.display = 'none';
        }
    }

    /**
     * Initialize SCORM wrapper
     */
    async initSCORMWrapper() {
        try {
            // Wait for SCORM wrapper to be available
            if (!window.SCORMWrapper) {
                throw new Error('SCORM wrapper not loaded');
            }
            
            // Create SCORM wrapper instance
            this.scormWrapper = new window.SCORMWrapper({
                version: null, // Auto-detect
                handleCompletionStatus: true,
                handleExitMode: true,
                debugMode: this.options.debugMode,
                progressTrackingEnabled: true
            });
            
            // Wait for progress tracker integration
            if (this.progressTracker) {
                this.scormWrapper.currentCourseId = this.options.courseId;
                this.scormWrapper.currentContentId = this.options.contentId;
                this.scormWrapper.currentModuleId = this.options.moduleId;
                this.scormWrapper.userId = this.progressTracker.userId;
                this.scormWrapper.clientId = this.progressTracker.clientId;
            }
            
            this.log('SCORM wrapper initialized', this.scormWrapper);
            
        } catch (error) {
            this.log('Failed to initialize SCORM wrapper', error);
            throw error;
        }
    }

    /**
     * Load SCORM content
     */
    async loadContent() {
        try {
            if (!this.options.scormUrl) {
                throw new Error('SCORM URL not provided');
            }
            
            this.log('Loading SCORM content', this.options.scormUrl);
            
            // Set iframe source
            this.iframe.src = this.options.scormUrl;
            
            // Wait for iframe to load
            await this.waitForIframeLoad();
            
            // Initialize SCORM connection
            await this.initializeSCORMConnection();
            
            // Load resume data if available
            await this.loadResumeData();
            
            this.log('SCORM content loaded successfully');
            
        } catch (error) {
            this.log('Failed to load SCORM content', error);
            throw error;
        }
    }

    /**
     * Wait for iframe to load
     */
    waitForIframeLoad() {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('Iframe load timeout'));
            }, 30000); // 30 second timeout
            
            this.iframe.onload = () => {
                clearTimeout(timeout);
                resolve();
            };
            
            this.iframe.onerror = () => {
                clearTimeout(timeout);
                reject(new Error('Iframe load failed'));
            };
        });
    }

    /**
     * Initialize SCORM connection
     */
    async initializeSCORMConnection() {
        try {
            this.log('Initializing SCORM connection');
            
            // Wait for SCORM API to be available in iframe
            await this.waitForSCORMAPI();
            
            // Initialize connection
            const success = this.scormWrapper.initialize();
            
            if (success) {
                this.log('SCORM connection initialized successfully');
                this.updateStatus('Connected');
                this.triggerEvent('scormConnected', { wrapper: this.scormWrapper });
            } else {
                throw new Error('Failed to initialize SCORM connection');
            }
            
        } catch (error) {
            this.log('Failed to initialize SCORM connection', error);
            throw error;
        }
    }

    /**
     * Wait for SCORM API to be available
     */
    waitForSCORMAPI() {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error('SCORM API not found within timeout'));
            }, 10000); // 10 second timeout
            
            const checkAPI = () => {
                try {
                    if (this.iframe.contentWindow) {
                        const api = this.iframe.contentWindow.API || this.iframe.contentWindow.API_1484_11;
                        if (api) {
                            clearTimeout(timeout);
                            resolve();
                            return;
                        }
                    }
                } catch (e) {
                    // Cross-origin restrictions, continue checking
                }
                
                setTimeout(checkAPI, 100);
            };
            
            checkAPI();
        });
    }

    /**
     * Load resume data
     */
    async loadResumeData() {
        try {
            if (!this.progressTracker || !this.options.contentId) {
                return;
            }
            
            this.log('Loading resume data');
            
            // Get resume position from progress tracker
            const resumeData = await this.progressTracker.getContentResumePosition(
                this.options.contentId,
                'scorm'
            );
            
            if (resumeData && resumeData.scorm_data) {
                this.log('Resume data found', resumeData.scorm_data);
                
                // Apply resume data to SCORM
                this.applyResumeData(resumeData.scorm_data);
                
                // Trigger resume event
                this.triggerEvent('resumeDataLoaded', { resumeData: resumeData.scorm_data });
            } else {
                this.log('No resume data found');
            }
            
        } catch (error) {
            this.log('Failed to load resume data', error);
        }
    }

    /**
     * Apply resume data to SCORM
     */
    applyResumeData(resumeData) {
        try {
            if (!this.scormWrapper || !this.scormWrapper.connection.isActive) {
                return;
            }
            
            // Apply lesson location if available
            if (resumeData.lesson_location) {
                this.scormWrapper.set('cmi.location', resumeData.lesson_location);
                this.log('Applied lesson location', resumeData.lesson_location);
            }
            
            // Apply suspend data if available
            if (resumeData.suspend_data) {
                this.scormWrapper.set('cmi.suspend_data', resumeData.suspend_data);
                this.log('Applied suspend data', resumeData.suspend_data);
            }
            
            // Save changes
            this.scormWrapper.save();
            
        } catch (error) {
            this.log('Failed to apply resume data', error);
        }
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Save progress button
        const saveBtn = this.container.querySelector('.scorm-save');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveProgress());
        }
        
        // Resume button
        const resumeBtn = this.container.querySelector('.scorm-resume');
        if (resumeBtn) {
            resumeBtn.addEventListener('click', () => this.resumeContent());
        }
        
        // Complete button
        const completeBtn = this.container.querySelector('.scorm-complete');
        if (completeBtn) {
            completeBtn.addEventListener('click', () => this.markComplete());
        }
        
        // Window unload event
        window.addEventListener('beforeunload', () => this.handlePageUnload());
        
        // Visibility change event
        document.addEventListener('visibilitychange', () => this.handleVisibilityChange());
    }

    /**
     * Setup progress tracking
     */
    setupProgressTracking() {
        if (!this.progressTracker) {
            return;
        }
        
        // Start progress tracking interval
        this.startProgressTracking();
        
        // Listen for progress events
        this.addEventListener('progressUpdate', (event) => {
            this.updateProgressDisplay(event.detail);
        });
    }

    /**
     * Start progress tracking
     */
    startProgressTracking() {
        // Update progress every 5 seconds
        this.progressInterval = setInterval(async () => {
            try {
                if (this.scormWrapper && this.scormWrapper.connection.isActive) {
                    await this.updateProgress();
                }
            } catch (error) {
                this.log('Progress tracking error', error);
            }
        }, 5000);
    }

    /**
     * Update progress
     */
    async updateProgress() {
        try {
            if (!this.progressTracker || !this.options.contentId) {
                return;
            }
            
            // Get current SCORM data
            const scormData = this.scormWrapper.getScormProgressData();
            
            // Update progress tracking
            await this.progressTracker.updateContentProgress(
                this.options.contentId,
                'scorm',
                this.options.courseId,
                scormData
            );
            
            // Update UI
            this.updateProgressDisplay(scormData);
            
            // Trigger progress update event
            this.triggerEvent('progressUpdate', scormData);
            
        } catch (error) {
            this.log('Failed to update progress', error);
        }
    }

    /**
     * Update progress display
     */
    updateProgressDisplay(progressData) {
        try {
            // Update status text
            if (this.statusDisplay) {
                const status = progressData.lesson_status || 'incomplete';
                this.statusDisplay.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                
                // Add status-specific styling
                this.statusDisplay.className = 'status-value';
                if (status === 'completed' || status === 'passed') {
                    this.statusDisplay.classList.add('text-success');
                } else if (status === 'failed') {
                    this.statusDisplay.classList.add('text-danger');
                } else {
                    this.statusDisplay.classList.add('text-warning');
                }
            }
            
            // Update progress bar
            if (this.progressBar) {
                let progressPercentage = 0;
                
                if (progressData.progress_measure) {
                    progressPercentage = parseFloat(progressData.progress_measure) * 100;
                } else if (progressData.lesson_status === 'completed' || progressData.lesson_status === 'passed') {
                    progressPercentage = 100;
                } else if (progressData.lesson_status === 'incomplete') {
                    progressPercentage = 50; // Default incomplete progress
                }
                
                this.progressBar.style.width = Math.min(100, Math.max(0, progressPercentage)) + '%';
            }
            
        } catch (error) {
            this.log('Failed to update progress display', error);
        }
    }

    /**
     * Save progress
     */
    async saveProgress() {
        try {
            this.log('Saving progress');
            
            if (this.scormWrapper && this.scormWrapper.connection.isActive) {
                // Save SCORM data
                const success = this.scormWrapper.save();
                
                if (success) {
                    // Update progress tracking
                    await this.updateProgress();
                    
                    this.showMessage('Progress saved successfully', 'success');
                    this.triggerEvent('progressSaved', { success: true });
                } else {
                    throw new Error('Failed to save SCORM data');
                }
            } else {
                throw new Error('SCORM connection not active');
            }
            
        } catch (error) {
            this.log('Failed to save progress', error);
            this.showMessage('Failed to save progress: ' + error.message, 'error');
        }
    }

    /**
     * Resume content
     */
    async resumeContent() {
        try {
            this.log('Resuming content');
            
            // Load resume data
            await this.loadResumeData();
            
            this.showMessage('Content resumed', 'info');
            this.triggerEvent('contentResumed', { success: true });
            
        } catch (error) {
            this.log('Failed to resume content', error);
            this.showMessage('Failed to resume content: ' + error.message, 'error');
        }
    }

    /**
     * Mark content as complete
     */
    async markComplete() {
        try {
            this.log('Marking content as complete');
            
            if (this.scormWrapper && this.scormWrapper.connection.isActive) {
                // Set completion status
                const success = this.scormWrapper.status('set', 'completed');
                
                if (success) {
                    // Save changes
                    this.scormWrapper.save();
                    
                    // Update progress tracking
                    await this.updateProgress();
                    
                    this.showMessage('Content marked as complete', 'success');
                    this.triggerEvent('contentCompleted', { success: true });
                } else {
                    throw new Error('Failed to set completion status');
                }
            } else {
                throw new Error('SCORM connection not active');
            }
            
        } catch (error) {
            this.log('Failed to mark content as complete', error);
            this.showMessage('Failed to mark content as complete: ' + error.message, 'error');
        }
    }

    /**
     * Handle page unload
     */
    handlePageUnload() {
        try {
            if (this.scormWrapper && this.scormWrapper.connection.isActive) {
                // Save progress before leaving
                this.scormWrapper.save();
                
                // Terminate connection
                this.scormWrapper.terminate();
            }
        } catch (error) {
            this.log('Error during page unload', error);
        }
    }

    /**
     * Handle visibility change
     */
    handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden, save progress
            this.saveProgress();
        }
    }

    /**
     * Add event listener
     */
    addEventListener(event, callback) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push(callback);
    }

    /**
     * Remove event listener
     */
    removeEventListener(event, callback) {
        if (this.eventListeners.has(event)) {
            const callbacks = this.eventListeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    /**
     * Trigger event
     */
    triggerEvent(event, data = {}) {
        if (this.eventListeners.has(event)) {
            const callbacks = this.eventListeners.get(event);
            callbacks.forEach(callback => {
                try {
                    callback({ type: event, detail: data, target: this });
                } catch (error) {
                    this.log('Error in event callback', error);
                }
            });
        }
    }

    /**
     * Show message
     */
    showMessage(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

    /**
     * Show error
     */
    showError(message) {
        this.showMessage(message, 'error');
    }

    /**
     * Log message
     */
    log(message, data = null) {
        if (this.options.debugMode) {
            if (data) {
                console.log(`[SCORM Player] ${message}`, data);
            } else {
                console.log(`[SCORM Player] ${message}`);
            }
        }
    }

    /**
     * Get player state
     */
    getState() {
        return {
            isLoaded: this.isLoaded,
            isPlaying: this.isPlaying,
            scormWrapper: this.scormWrapper ? this.scormWrapper.getState() : null,
            options: this.options
        };
    }

    /**
     * Destroy player
     */
    destroy() {
        try {
            // Clear intervals
            if (this.progressInterval) {
                clearInterval(this.progressInterval);
            }
            
            // Terminate SCORM connection
            if (this.scormWrapper && this.scormWrapper.connection.isActive) {
                this.scormWrapper.terminate();
            }
            
            // Remove event listeners
            this.eventListeners.clear();
            
            // Clear container
            this.container.innerHTML = '';
            
            this.log('SCORM player destroyed');
            
        } catch (error) {
            this.log('Error destroying player', error);
        }
    }
}

// =====================================================
// GLOBAL EXPORT
// =====================================================

// Export for different module systems
if (typeof define === 'function' && define.amd) {
    // AMD
    define([], function() { return SCORMPlayer; });
} else if (typeof module === 'object' && module.exports) {
    // Node.js
    module.exports = SCORMPlayer;
} else {
    // Browser globals
    window.SCORMPlayer = SCORMPlayer;
}

// =====================================================
// USAGE EXAMPLES
// =====================================================

/*
// Basic usage
const player = new SCORMPlayer('#scorm-container', {
    courseId: 1,
    contentId: 58,
    scormUrl: '/uploads/scorm-package/index.html',
    showControls: true,
    debugMode: true
});

// Advanced usage with event listeners
player.addEventListener('playerReady', (event) => {
    console.log('Player is ready', event.detail);
});

player.addEventListener('scormConnected', (event) => {
    console.log('SCORM connected', event.detail);
});

player.addEventListener('progressUpdate', (event) => {
    console.log('Progress updated', event.detail);
});

player.addEventListener('contentCompleted', (event) => {
    console.log('Content completed', event.detail);
});
*/
