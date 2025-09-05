/**
 * SCORM Integration Example for Unlock Your Skills
 * 
 * This file demonstrates how to integrate the SCORM wrapper and player
 * with the existing progress tracking system.
 * 
 * Features demonstrated:
 * - Automatic SCORM wrapper initialization
 * - SCORM player setup and configuration
 * - Progress tracking integration
 * - Resume functionality
 * - Error handling and debugging
 */

// =====================================================
// SCORM INTEGRATION MANAGER
// =====================================================

class SCORMIntegrationManager {
    constructor() {
        this.scormWrapper = null;
        this.scormPlayer = null;
        this.progressTracker = null;
        this.isInitialized = false;
        
        // Configuration
        this.config = {
            autoInitialize: true,
            debugMode: true,
            progressTrackingEnabled: true,
            autoSaveInterval: 30000, // 30 seconds
            resumeOnLoad: true
        };
        
        // Auto-initialize if enabled
        if (this.config.autoInitialize) {
            this.init();
        }
    }

    /**
     * Initialize the SCORM integration manager
     */
    async init() {
        try {
            console.log('[SCORM Integration] Initializing...');
            
            // Wait for dependencies
            await this.waitForDependencies();
            
            // Initialize SCORM wrapper
            await this.initSCORMWrapper();
            
            // Setup progress tracking
            this.setupProgressTracking();
            
            // Setup auto-save
            this.setupAutoSave();
            
            // Setup page event handlers
            this.setupPageEventHandlers();
            
            this.isInitialized = true;
            console.log('[SCORM Integration] Initialized successfully');
            
            // Trigger ready event
            this.triggerEvent('integrationReady', { manager: this });
            
        } catch (error) {
            console.error('[SCORM Integration] Initialization failed:', error);
        }
    }

    /**
     * Wait for required dependencies
     */
    async waitForDependencies() {
        return new Promise((resolve) => {
            const checkDependencies = () => {
                // Check for SCORM wrapper
                if (!window.SCORMWrapper) {
                    console.log('[SCORM Integration] Waiting for SCORM wrapper...');
                    setTimeout(checkDependencies, 100);
                    return;
                }
                
                // Check for progress tracker
                if (!window.progressTracker || !window.progressTracker.isInitialized) {
                    console.log('[SCORM Integration] Waiting for progress tracker...');
                    setTimeout(checkDependencies, 100);
                    return;
                }
                
                resolve();
            };
            
            checkDependencies();
        });
    }

    /**
     * Initialize SCORM wrapper
     */
    async initSCORMWrapper() {
        try {
            // Create SCORM wrapper instance
            this.scormWrapper = new window.SCORMWrapper({
                version: null, // Auto-detect
                handleCompletionStatus: true,
                handleExitMode: true,
                debugMode: this.config.debugMode,
                progressTrackingEnabled: this.config.progressTrackingEnabled
            });
            
            // Wait for wrapper to be ready
            await this.waitForSCORMWrapperReady();
            
            console.log('[SCORM Integration] SCORM wrapper initialized');
            
        } catch (error) {
            console.error('[SCORM Integration] Failed to initialize SCORM wrapper:', error);
            throw error;
        }
    }

    /**
     * Wait for SCORM wrapper to be ready
     */
    async waitForSCORMWrapperReady() {
        return new Promise((resolve) => {
            const checkReady = () => {
                if (this.scormWrapper && this.scormWrapper.progressTracker) {
                    resolve();
                } else {
                    setTimeout(checkReady, 100);
                }
            };
            
            checkReady();
        });
    }

    /**
     * Setup progress tracking
     */
    setupProgressTracking() {
        this.progressTracker = window.progressTracker;
        
        // Listen for progress tracker events using window/document event listeners
        if (this.progressTracker) {
            window.addEventListener('progressResume', (event) => {
                this.handleProgressResume(event);
            });
            
            document.addEventListener('progressUpdate', (event) => {
                this.handleProgressUpdate(event);
            });
        }
    }

    /**
     * Setup auto-save functionality
     */
    setupAutoSave() {
        if (this.config.autoSaveInterval > 0) {
            setInterval(() => {
                this.autoSave();
            }, this.config.autoSaveInterval);
        }
    }

    /**
     * Setup page event handlers
     */
    setupPageEventHandlers() {
        // Page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.handlePageHidden();
            } else {
                this.handlePageVisible();
            }
        });
        
        // Page unload
        window.addEventListener('beforeunload', () => {
            this.handlePageUnload();
        });
        
        // Page focus/blur
        window.addEventListener('focus', () => {
            this.handlePageFocus();
        });
        
        window.addEventListener('blur', () => {
            this.handlePageBlur();
        });
    }

    /**
     * Handle progress resume
     */
    handleProgressResume(event) {
        console.log('[SCORM Integration] Progress resume:', event.detail);
        
        // Trigger custom event
        this.triggerEvent('progressResume', event.detail);
    }

    /**
     * Handle progress updates
     */
    handleProgressUpdate(event) {
        console.log('[SCORM Integration] Progress update:', event.detail);
        
        // Trigger custom event
        this.triggerEvent('progressUpdate', event.detail);
    }

    /**
     * Handle content completion
     */
    handleContentCompleted(event) {
        console.log('[SCORM Integration] Content completed:', event.detail);
        
        // Trigger custom event
        this.triggerEvent('contentCompleted', event.detail);
    }

    /**
     * Auto-save progress
     */
    async autoSave() {
        try {
            if (this.scormWrapper && this.scormWrapper.connection.isActive) {
                console.log('[SCORM Integration] Auto-saving progress...');
                
                // Save SCORM data
                const success = this.scormWrapper.save();
                
                if (success) {
                    console.log('[SCORM Integration] Auto-save successful');
                } else {
                    console.warn('[SCORM Integration] Auto-save failed');
                }
            }
        } catch (error) {
            console.error('[SCORM Integration] Auto-save error:', error);
        }
    }

    /**
     * Handle page hidden
     */
    handlePageHidden() {
        console.log('[SCORM Integration] Page hidden, saving progress...');
        this.autoSave();
    }

    /**
     * Handle page visible
     */
    handlePageVisible() {
        console.log('[SCORM Integration] Page visible');
        // Could implement resume functionality here
    }

    /**
     * Handle page unload
     */
    handlePageUnload() {
        try {
            console.log('[SCORM Integration] Page unloading, saving progress...');
            
            if (this.scormWrapper && this.scormWrapper.connection.isActive) {
                // Save progress
                this.scormWrapper.save();
                
                // Terminate connection
                this.scormWrapper.terminate();
            }
        } catch (error) {
            console.error('[SCORM Integration] Error during page unload:', error);
        }
    }

    /**
     * Handle page focus
     */
    handlePageFocus() {
        console.log('[SCORM Integration] Page focused');
    }

    /**
     * Handle page blur
     */
    handlePageBlur() {
        console.log('[SCORM Integration] Page blurred');
    }

    /**
     * Create SCORM player
     */
    createSCORMPlayer(container, options = {}) {
        try {
            if (!window.SCORMPlayer) {
                throw new Error('SCORM player not loaded');
            }
            
            // Merge options with defaults
            const playerOptions = {
                courseId: this.progressTracker?.currentCourseId,
                moduleId: this.progressTracker?.currentModuleId,
                contentId: this.progressTracker?.currentContentId,
                debugMode: this.config.debugMode,
                showControls: true,
                autoInitialize: true,
                ...options
            };
            
            // Create player
            this.scormPlayer = new window.SCORMPlayer(container, playerOptions);
            
            // Setup player event listeners
            this.setupPlayerEventListeners();
            
            console.log('[SCORM Integration] SCORM player created');
            
            return this.scormPlayer;
            
        } catch (error) {
            console.error('[SCORM Integration] Failed to create SCORM player:', error);
            throw error;
        }
    }

    /**
     * Setup player event listeners
     */
    setupPlayerEventListeners() {
        if (!this.scormPlayer) return;
        
        // Player ready
        this.scormPlayer.addEventListener('playerReady', (event) => {
            console.log('[SCORM Integration] Player ready:', event.detail);
            this.triggerEvent('playerReady', event.detail);
        });
        
        // SCORM connected
        this.scormPlayer.addEventListener('scormConnected', (event) => {
            console.log('[SCORM Integration] SCORM connected:', event.detail);
            this.triggerEvent('scormConnected', event.detail);
        });
        
        // Progress update
        this.scormPlayer.addEventListener('progressUpdate', (event) => {
            console.log('[SCORM Integration] Player progress update:', event.detail);
            this.triggerEvent('playerProgressUpdate', event.detail);
        });
        
        // Content completed
        this.scormPlayer.addEventListener('contentCompleted', (event) => {
            console.log('[SCORM Integration] Player content completed:', event.detail);
            this.triggerEvent('playerContentCompleted', event.detail);
        });
    }

    /**
     * Get SCORM wrapper instance
     */
    getSCORMWrapper() {
        return this.scormWrapper;
    }

    /**
     * Get SCORM player instance
     */
    getSCORMPlayer() {
        return this.scormPlayer;
    }

    /**
     * Get current state
     */
    getState() {
        return {
            isInitialized: this.isInitialized,
            scormWrapper: this.scormWrapper ? this.scormWrapper.getState() : null,
            scormPlayer: this.scormPlayer ? this.scormPlayer.getState() : null,
            progressTracker: this.progressTracker ? true : false
        };
    }

    /**
     * Event handling
     */
    eventListeners = new Map();

    addEventListener(event, callback) {
        if (!this.eventListeners.has(event)) {
            this.eventListeners.set(event, []);
        }
        this.eventListeners.get(event).push(callback);
    }

    removeEventListener(event, callback) {
        if (this.eventListeners.has(event)) {
            const callbacks = this.eventListeners.get(event);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
        }
    }

    triggerEvent(event, data = {}) {
        if (this.eventListeners.has(event)) {
            const callbacks = this.eventListeners.get(event);
            callbacks.forEach(callback => {
                try {
                    callback({ type: event, detail: data, target: this });
                } catch (error) {
                    console.error('[SCORM Integration] Event callback error:', error);
                }
            });
        }
    }

    /**
     * Destroy integration manager
     */
    destroy() {
        try {
            // Destroy player
            if (this.scormPlayer) {
                this.scormPlayer.destroy();
                this.scormPlayer = null;
            }
            
            // Reset wrapper
            if (this.scormWrapper) {
                this.scormWrapper.reset();
                this.scormWrapper = null;
            }
            
            // Clear event listeners
            this.eventListeners.clear();
            
            this.isInitialized = false;
            console.log('[SCORM Integration] Destroyed');
            
        } catch (error) {
            console.error('[SCORM Integration] Error during destruction:', error);
        }
    }
}

// =====================================================
// USAGE EXAMPLES
// =====================================================

// Example 1: Basic Integration
function setupBasicSCORMIntegration() {
    console.log('Setting up basic SCORM integration...');
    
    // Create integration manager
    const scormManager = new SCORMIntegrationManager();
    
    // Listen for ready event
    scormManager.addEventListener('integrationReady', (event) => {
        console.log('SCORM integration ready!', event.detail);
        
        // Create SCORM player
        const player = scormManager.createSCORMPlayer('#scorm-container', {
            scormUrl: '/uploads/scorm-package/index.html',
            showControls: true,
            debugMode: true
        });
    });
    
    return scormManager;
}

// Example 2: Advanced Integration with Custom Options
function setupAdvancedSCORMIntegration(options = {}) {
    console.log('Setting up advanced SCORM integration...');
    
    // Create integration manager with custom config
    const scormManager = new SCORMIntegrationManager();
    Object.assign(scormManager.config, options);
    
    // Listen for various events
    scormManager.addEventListener('integrationReady', (event) => {
        console.log('Advanced SCORM integration ready!', event.detail);
    });
    
    scormManager.addEventListener('scormConnected', (event) => {
        console.log('SCORM connected!', event.detail);
    });
    
    scormManager.addEventListener('progressUpdate', (event) => {
        console.log('Progress updated:', event.detail);
    });
    
    scormManager.addEventListener('contentCompleted', (event) => {
        console.log('Content completed:', event.detail);
    });
    
    return scormManager;
}

// Example 3: Manual SCORM Wrapper Usage
function useSCORMWrapperDirectly() {
    console.log('Using SCORM wrapper directly...');
    
    if (!window.SCORMWrapper) {
        console.error('SCORM wrapper not available');
        return null;
    }
    
    // Create wrapper instance
    const wrapper = new window.SCORMWrapper({
        version: null, // Auto-detect
        handleCompletionStatus: true,
        handleExitMode: true,
        debugMode: true,
        progressTrackingEnabled: true
    });
    
    // Initialize connection
    const success = wrapper.initialize();
    
    if (success) {
        console.log('SCORM wrapper initialized successfully');
        
        // Get some data
        const lessonStatus = wrapper.get('cmi.lesson_status');
        const lessonLocation = wrapper.get('cmi.location');
        
        console.log('Lesson status:', lessonStatus);
        console.log('Lesson location:', lessonLocation);
        
        return wrapper;
    } else {
        console.error('Failed to initialize SCORM wrapper');
        return null;
    }
}

// Example 4: SCORM Player with Custom UI
function createCustomSCORMPlayer() {
    console.log('Creating custom SCORM player...');
    
    if (!window.SCORMPlayer) {
        console.error('SCORM player not available');
        return null;
    }
    
    // Create player with custom options
    const player = new window.SCORMPlayer('#custom-scorm-container', {
        courseId: 1,
        contentId: 58,
        scormUrl: '/uploads/scorm-package/index.html',
        showControls: false, // Hide default controls
        autoInitialize: false, // Don't auto-initialize
        debugMode: true
    });
    
    // Add custom event listeners
    player.addEventListener('playerReady', (event) => {
        console.log('Custom player ready:', event.detail);
        
        // Initialize manually
        player.init();
    });
    
    player.addEventListener('scormConnected', (event) => {
        console.log('Custom player SCORM connected:', event.detail);
    });
    
    return player;
}

// Example 5: Progress Tracking Integration
function integrateWithProgressTracking() {
    console.log('Integrating with progress tracking...');
    
    if (!window.progressTracker) {
        console.error('Progress tracker not available');
        return;
    }
    
    // Create SCORM integration manager
    const scormManager = new SCORMIntegrationManager();
    
    // Wait for integration to be ready
    scormManager.addEventListener('integrationReady', async (event) => {
        console.log('Progress tracking integration ready!');
        
        // Set course context
        window.progressTracker.setCourseContext(1, 1, 58, 'scorm');
        
        // Create SCORM player
        const player = scormManager.createSCORMPlayer('#scorm-container', {
            scormUrl: '/uploads/scorm-package/index.html',
            showControls: true,
            debugMode: true
        });
        
        // Start progress tracking
        window.progressTracker.startProgressTracking(58, 'scorm', 5000);
    });
    
    return scormManager;
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Check if SCORM integration is available
 */
function isSCORMIntegrationAvailable() {
    return !!(window.SCORMWrapper && window.SCORMPlayer);
}

/**
 * Get SCORM integration status
 */
function getSCORMIntegrationStatus() {
    const status = {
        scormWrapper: !!window.SCORMWrapper,
        scormPlayer: !!window.SCORMPlayer,
        progressTracker: !!window.progressTracker,
        integrationManager: !!window.scormIntegrationManager
    };
    
    return status;
}

/**
 * Initialize SCORM integration globally
 */
function initGlobalSCORMIntegration() {
    if (window.scormIntegrationManager) {
        console.log('SCORM integration already initialized');
        return window.scormIntegrationManager;
    }
    
    console.log('Initializing global SCORM integration...');
    
    // Create global integration manager
    window.scormIntegrationManager = new SCORMIntegrationManager();
    
    return window.scormIntegrationManager;
}

// =====================================================
// AUTO-INITIALIZATION
// =====================================================

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Check if we're in a SCORM context
        if (window.parent && window.parent !== window) {
            // Try to find SCORM API
            let api = null;
            try {
                api = window.parent.API || window.parent.API_1484_11;
            } catch (e) {
                // Cross-origin restrictions
            }
            
            if (api) {
                console.log('SCORM context detected, initializing integration...');
                initGlobalSCORMIntegration();
            }
        }
    });
} else {
    // DOM already ready
    if (window.parent && window.parent !== window) {
        let api = null;
        try {
            api = window.parent.API || window.parent.API_1484_11;
        } catch (e) {
            // Cross-origin restrictions
        }
        
        if (api) {
            console.log('SCORM context detected, initializing integration...');
            initGlobalSCORMIntegration();
        }
    }
}

// =====================================================
// GLOBAL EXPORT
// =====================================================

// Export for different module systems
if (typeof define === 'function' && define.amd) {
    // AMD
    define([], function() { 
        return {
            SCORMIntegrationManager: SCORMIntegrationManager,
            setupBasicSCORMIntegration: setupBasicSCORMIntegration,
            setupAdvancedSCORMIntegration: setupAdvancedSCORMIntegration,
            useSCORMWrapperDirectly: useSCORMWrapperDirectly,
            createCustomSCORMPlayer: createCustomSCORMPlayer,
            integrateWithProgressTracking: integrateWithProgressTracking,
            isSCORMIntegrationAvailable: isSCORMIntegrationAvailable,
            getSCORMIntegrationStatus: getSCORMIntegrationStatus,
            initGlobalSCORMIntegration: initGlobalSCORMIntegration
        };
    });
} else if (typeof module === 'object' && module.exports) {
    // Node.js
    module.exports = {
        SCORMIntegrationManager: SCORMIntegrationManager,
        setupBasicSCORMIntegration: setupBasicSCORMIntegration,
        setupAdvancedSCORMIntegration: setupAdvancedSCORMIntegration,
        useSCORMWrapperDirectly: useSCORMWrapperDirectly,
        createCustomSCORMPlayer: createCustomSCORMPlayer,
        integrateWithProgressTracking: integrateWithProgressTracking,
        isSCORMIntegrationAvailable: isSCORMIntegrationAvailable,
        getSCORMIntegrationStatus: getSCORMIntegrationStatus,
        initGlobalSCORMIntegration: initGlobalSCORMIntegration
    };
} else {
    // Browser globals
    window.SCORMIntegrationManager = SCORMIntegrationManager;
    window.setupBasicSCORMIntegration = setupBasicSCORMIntegration;
    window.setupAdvancedSCORMIntegration = setupAdvancedSCORMIntegration;
    window.useSCORMWrapperDirectly = useSCORMWrapperDirectly;
    window.createCustomSCORMPlayer = createCustomSCORMPlayer;
    window.integrateWithProgressTracking = integrateWithProgressTracking;
    window.isSCORMIntegrationAvailable = isSCORMIntegrationAvailable;
    window.getSCORMIntegrationStatus = getSCORMIntegrationStatus;
    window.initGlobalSCORMIntegration = initGlobalSCORMIntegration;
}
