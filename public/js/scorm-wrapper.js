/**
 * SCORM Wrapper for JavaScript
 * Full SCORM 1.2 and 2004 compliance with progress tracking integration
 * 
 * This wrapper provides a complete SCORM API implementation that integrates
 * with the existing Unlock Your Skills progress tracking system.
 * 
 * Features:
 * - Automatic SCORM version detection (1.2 or 2004)
 * - Complete CMI data model support
 * - Proper API finding and connection management
 * - Error handling and diagnostic information
 * - Seamless integration with progress tracking
 * - Resume functionality support
 */

class SCORMWrapper {
    constructor(options = {}) {
        // Configuration options
        this.version = options.version || null; // '1.2', '2004', or null for auto-detect
        this.handleCompletionStatus = options.handleCompletionStatus !== false; // Default true
        this.handleExitMode = options.handleExitMode !== false; // Default true
        this.debugMode = options.debugMode || false;
        this.progressTrackingEnabled = options.progressTrackingEnabled !== false; // Default true
        
        // SCORM state
        this.API = {
            handle: null,
            isFound: false,
            version: null
        };
        
        this.connection = {
            isActive: false,
            initialized: false
        };
        
        this.data = {
            completionStatus: null,
            exitStatus: null,
            lessonLocation: null,
            suspendData: null,
            score: {
                raw: null,
                min: null,
                max: null
            }
        };
        
        // Progress tracking integration
        this.progressTracker = window.progressTracker || null;
        this.currentCourseId = null;
        this.currentContentId = null;
        this.currentModuleId = null;
        this.userId = null;
        this.clientId = null;
        
        // Auto-initialize if progress tracker is available
        this.waitForProgressTracker();
    }

    /**
     * Wait for progress tracker to be available
     */
    waitForProgressTracker() {
        if (window.progressTracker && window.progressTracker.isInitialized) {
            this.progressTracker = window.progressTracker;
            this.setupProgressTracking();
        } else {
            setTimeout(() => this.waitForProgressTracker(), 100);
        }
    }

    /**
     * Setup progress tracking integration
     */
    setupProgressTracking() {
        if (this.progressTracker) {
            this.currentCourseId = this.progressTracker.currentCourseId;
            this.currentContentId = this.progressTracker.currentContentId;
            this.currentModuleId = this.progressTracker.currentModuleId;
            this.userId = this.progressTracker.userId;
            this.clientId = this.progressTracker.clientId;
            
            this.log('Progress tracking integration setup complete', {
                courseId: this.currentCourseId,
                contentId: this.currentContentId,
                moduleId: this.currentModuleId,
                userId: this.userId,
                clientId: this.clientId
            });
        }
    }

    /**
     * Log messages when debug mode is enabled
     */
    log(message, data = null) {
        if (this.debugMode) {
            if (data) {
                console.log(`[SCORM Wrapper] ${message}`, data);
            } else {
                console.log(`[SCORM Wrapper] ${message}`);
            }
        }
    }

    /**
     * Check if SCORM wrapper is available
     */
    isAvailable() {
        return true;
    }

    // =====================================================
    // SCORM API FINDING AND CONNECTION
    // =====================================================

    /**
     * Find SCORM API in window hierarchy
     */
    findAPI(win) {
        let API = null;
        let findAttempts = 0;
        const findAttemptLimit = 500;
        const traceMsgPrefix = "SCORM.API.find";

        this.log(`${traceMsgPrefix}: Starting API search`);

        // Search through parent windows
        while ((!win.API && !win.API_1484_11) &&
            (win.parent) &&
            (win.parent != win) &&
            (findAttempts <= findAttemptLimit)) {

            findAttempts++;
            win = win.parent;
        }

        // If SCORM version is specified, look for specific API
        if (this.version) {
            switch (this.version) {
                case "2004":
                    if (win.API_1484_11) {
                        API = win.API_1484_11;
                        this.API.version = "2004";
                    } else {
                        this.log(`${traceMsgPrefix}: SCORM 2004 specified but API_1484_11 not found`);
                    }
                    break;

                case "1.2":
                    if (win.API) {
                        API = win.API;
                        this.API.version = "1.2";
                    } else {
                        this.log(`${traceMsgPrefix}: SCORM 1.2 specified but API not found`);
                    }
                    break;
            }
        } else {
            // Auto-detect SCORM version
            if (win.API_1484_11) {
                this.API.version = "2004";
                API = win.API_1484_11;
            } else if (win.API) {
                this.API.version = "1.2";
                API = win.API;
            }
        }

        if (API) {
            this.log(`${traceMsgPrefix}: API found. Version: ${this.API.version}`);
            this.log(`API: ${API}`);
        } else {
            this.log(`${traceMsgPrefix}: Error finding API. Find attempts: ${findAttempts}, Limit: ${findAttemptLimit}`);
        }

        return API;
    }

    /**
     * Get SCORM API handle
     */
    getAPI() {
        let API = null;
        let win = window;

        this.log("SCORM.API.get: Starting API search");

        // Search in current window hierarchy
        API = this.findAPI(win);

        // Search in parent window hierarchy
        if (!API && win.parent && win.parent != win) {
            API = this.findAPI(win.parent);
        }

        // Search in top opener window
        if (!API && win.top && win.top.opener) {
            API = this.findAPI(win.top.opener);
        }

        // Special handling for Plateau LMS
        if (!API && win.top && win.top.opener && win.top.opener.document) {
            API = this.findAPI(win.top.opener.document);
        }

        if (API) {
            this.API.isFound = true;
            this.API.handle = API;
            this.log("API.get: API found successfully");
        } else {
            this.log("API.get failed: Can't find the API!");
        }

        return API;
    }

    /**
     * Get API handle (cached or find new)
     */
    getHandle() {
        if (!this.API.handle && !this.API.isFound) {
            this.API.handle = this.getAPI();
        }
        return this.API.handle;
    }

    // =====================================================
    // SCORM CONNECTION MANAGEMENT
    // =====================================================

    /**
     * Initialize SCORM connection
     */
    initialize() {
        let success = false;
        const traceMsgPrefix = "SCORM.connection.initialize";

        this.log(`${traceMsgPrefix}: Called`);

        if (this.connection.isActive) {
            this.log(`${traceMsgPrefix}: Aborted - Connection already active`);
            return true;
        }

        const API = this.getHandle();
        if (!API) {
            this.log(`${traceMsgPrefix}: Failed - API is null`);
            return false;
        }

        try {
            // Initialize based on SCORM version
            switch (this.API.version) {
                case "1.2":
                    success = this.makeBoolean(API.LMSInitialize(""));
                    break;
                case "2004":
                    success = this.makeBoolean(API.Initialize(""));
                    break;
                default:
                    this.log(`${traceMsgPrefix}: Failed - Unknown SCORM version`);
                    return false;
            }

            if (success) {
                // Verify connection is working
                const errorCode = this.getLastError();
                if (errorCode === 0) {
                    this.connection.isActive = true;
                    this.connection.initialized = true;

                    this.log(`${traceMsgPrefix}: Success - Connection established`);

                    // Handle completion status if enabled
                    if (this.handleCompletionStatus) {
                        this.handleInitialCompletionStatus();
                    }

                    // Update progress tracking
                    if (this.progressTrackingEnabled && this.progressTracker) {
                        this.updateProgressTracking();
                    }

                    return true;
                } else {
                    this.log(`${traceMsgPrefix}: Failed - Error code: ${errorCode}, Info: ${this.getErrorString(errorCode)}`);
                    return false;
                }
            } else {
                const errorCode = this.getLastError();
                if (errorCode !== 0) {
                    this.log(`${traceMsgPrefix}: Failed - Error code: ${errorCode}, Info: ${this.getErrorString(errorCode)}`);
                } else {
                    this.log(`${traceMsgPrefix}: Failed - No response from server`);
                }
                return false;
            }
        } catch (error) {
            this.log(`${traceMsgPrefix}: Exception - ${error.message}`);
            return false;
        }
    }

    /**
     * Terminate SCORM connection
     */
    terminate() {
        let success = false;
        const traceMsgPrefix = "SCORM.connection.terminate";

        if (!this.connection.isActive) {
            this.log(`${traceMsgPrefix}: Aborted - Connection already terminated`);
            return true;
        }

        const API = this.getHandle();
        if (!API) {
            this.log(`${traceMsgPrefix}: Failed - API is null`);
            return false;
        }

        try {
            // Handle exit mode if enabled
            if (this.handleExitMode && !this.data.exitStatus) {
                if (this.data.completionStatus !== "completed" && this.data.completionStatus !== "passed") {
                    switch (this.API.version) {
                        case "1.2":
                            success = this.set("cmi.core.exit", "suspend");
                            break;
                        case "2004":
                            success = this.set("cmi.exit", "suspend");
                            break;
                    }
                } else {
                    switch (this.API.version) {
                        case "1.2":
                            success = this.set("cmi.core.exit", "logout");
                            break;
                        case "2004":
                            success = this.set("cmi.exit", "normal");
                            break;
                    }
                }
            }

            // Save data for SCORM 1.2 (not required for 2004)
            if (this.API.version === "1.2") {
                success = this.save();
            }

            if (success) {
                // Terminate connection
                switch (this.API.version) {
                    case "1.2":
                        success = this.makeBoolean(API.LMSFinish(""));
                        break;
                    case "2004":
                        success = this.makeBoolean(API.Terminate(""));
                        break;
                }

                if (success) {
                    this.connection.isActive = false;
                    this.connection.initialized = false;
                    this.log(`${traceMsgPrefix}: Success - Connection terminated`);
                    
                    // Final progress update
                    if (this.progressTrackingEnabled && this.progressTracker) {
                        this.updateProgressTracking();
                    }
                    
                    return true;
                } else {
                    const errorCode = this.getLastError();
                    this.log(`${traceMsgPrefix}: Failed - Error code: ${errorCode}, Info: ${this.getErrorString(errorCode)}`);
                    return false;
                }
            }
        } catch (error) {
            this.log(`${traceMsgPrefix}: Exception - ${error.message}`);
            return false;
        }

        return false;
    }

    // =====================================================
    // SCORM DATA MODEL OPERATIONS
    // =====================================================

    /**
     * Get value from SCORM data model
     */
    get(parameter) {
        let value = null;
        const traceMsgPrefix = `SCORM.data.get('${parameter}')`;

        if (!this.connection.isActive) {
            this.log(`${traceMsgPrefix}: Failed - API connection is inactive`);
            return "";
        }

        const API = this.getHandle();
        if (!API) {
            this.log(`${traceMsgPrefix}: Failed - API is null`);
            return "";
        }

        try {
            // Get value based on SCORM version
            switch (this.API.version) {
                case "1.2":
                    value = API.LMSGetValue(parameter);
                    break;
                case "2004":
                    value = API.GetValue(parameter);
                    break;
            }

            const errorCode = this.getLastError();

            // Check for errors
            if (value !== "" || errorCode === 0) {
                // Cache important values
                this.cacheDataModelValue(parameter, value);
                this.log(`${traceMsgPrefix}: Success - Value: ${value}`);
            } else {
                this.log(`${traceMsgPrefix}: Failed - Error code: ${errorCode}, Info: ${this.getErrorString(errorCode)}`);
            }
        } catch (error) {
            this.log(`${traceMsgPrefix}: Exception - ${error.message}`);
            return "";
        }

        return String(value);
    }

    /**
     * Set value in SCORM data model
     */
    set(parameter, value) {
        let success = false;
        const traceMsgPrefix = `SCORM.data.set('${parameter}')`;

        if (!this.connection.isActive) {
            this.log(`${traceMsgPrefix}: Failed - API connection is inactive`);
            return false;
        }

        const API = this.getHandle();
        if (!API) {
            this.log(`${traceMsgPrefix}: Failed - API is null`);
            return false;
        }

        try {
            // Set value based on SCORM version
            switch (this.API.version) {
                case "1.2":
                    success = this.makeBoolean(API.LMSSetValue(parameter, value));
                    break;
                case "2004":
                    success = this.makeBoolean(API.SetValue(parameter, value));
                    break;
            }

            if (success) {
                // Cache the value
                this.cacheDataModelValue(parameter, value);
                this.log(`${traceMsgPrefix}: Success - Value: ${value}`);
                
                // Update progress tracking if enabled
                if (this.progressTrackingEnabled && this.progressTracker) {
                    this.updateProgressTracking();
                }
            } else {
                const errorCode = this.getLastError();
                this.log(`${traceMsgPrefix}: Failed - Error code: ${errorCode}, Info: ${this.getErrorString(errorCode)}`);
            }
        } catch (error) {
            this.log(`${traceMsgPrefix}: Exception - ${error.message}`);
            return false;
        }

        return success;
    }

    /**
     * Save SCORM data
     */
    save() {
        let success = false;
        const traceMsgPrefix = "SCORM.data.save";

        if (!this.connection.isActive) {
            this.log(`${traceMsgPrefix}: Failed - API connection is inactive`);
            return false;
        }

        const API = this.getHandle();
        if (!API) {
            this.log(`${traceMsgPrefix}: Failed - API is null`);
            return false;
        }

        try {
            // Save data based on SCORM version
            switch (this.API.version) {
                case "1.2":
                    success = this.makeBoolean(API.LMSCommit(""));
                    break;
                case "2004":
                    success = this.makeBoolean(API.Commit(""));
                    break;
            }

            if (success) {
                this.log(`${traceMsgPrefix}: Success - Data saved`);
                
                // Update progress tracking after save
                if (this.progressTrackingEnabled && this.progressTracker) {
                    this.updateProgressTracking();
                }
            } else {
                this.log(`${traceMsgPrefix}: Failed - Save operation failed`);
            }
        } catch (error) {
            this.log(`${traceMsgPrefix}: Exception - ${error.message}`);
            return false;
        }

        return success;
    }

    // =====================================================
    // SCORM STATUS MANAGEMENT
    // =====================================================

    /**
     * Get or set completion status
     */
    status(action, status = null) {
        let success = false;
        const traceMsgPrefix = "SCORM.status";
        let cmi = "";

        if (!action) {
            this.log(`${traceMsgPrefix}: Failed - Action not specified`);
            return false;
        }

        // Determine CMI parameter based on SCORM version
        switch (this.API.version) {
            case "1.2":
                cmi = "cmi.core.lesson_status";
                break;
            case "2004":
                cmi = "cmi.completion_status";
                break;
            default:
                this.log(`${traceMsgPrefix}: Failed - Unknown SCORM version`);
                return false;
        }

        switch (action) {
            case "get":
                success = this.get(cmi);
                break;

            case "set":
                if (status) {
                    success = this.set(cmi, status);
                } else {
                    this.log(`${traceMsgPrefix}: Failed - Status not specified`);
                    return false;
                }
                break;

            default:
                this.log(`${traceMsgPrefix}: Failed - Invalid action: ${action}`);
                return false;
        }

        return success;
    }

    // =====================================================
    // SCORM ERROR HANDLING
    // =====================================================

    /**
     * Get last error code
     */
    getLastError() {
        const API = this.getHandle();
        if (!API) {
            this.log("SCORM.debug.getCode failed: API is null");
            return 0;
        }

        try {
            let code = 0;
            switch (this.API.version) {
                case "1.2":
                    code = parseInt(API.LMSGetLastError(), 10);
                    break;
                case "2004":
                    code = parseInt(API.GetLastError(), 10);
                    break;
            }
            return code;
        } catch (error) {
            this.log(`SCORM.debug.getCode exception: ${error.message}`);
            return 0;
        }
    }

    /**
     * Get error string for error code
     */
    getErrorString(errorCode) {
        const API = this.getHandle();
        if (!API) {
            this.log("SCORM.debug.getInfo failed: API is null");
            return "";
        }

        try {
            let result = "";
            switch (this.API.version) {
                case "1.2":
                    result = API.LMSGetErrorString(errorCode.toString());
                    break;
                case "2004":
                    result = API.GetErrorString(errorCode.toString());
                    break;
            }
            return String(result);
        } catch (error) {
            this.log(`SCORM.debug.getInfo exception: ${error.message}`);
            return "";
        }
    }

    /**
     * Get diagnostic information for error code
     */
    getDiagnosticInfo(errorCode) {
        const API = this.getHandle();
        if (!API) {
            this.log("SCORM.debug.getDiagnosticInfo failed: API is null");
            return "";
        }

        try {
            let result = "";
            switch (this.API.version) {
                case "1.2":
                    result = API.LMSGetDiagnostic(errorCode);
                    break;
                case "2004":
                    result = API.GetDiagnostic(errorCode);
                    break;
            }
            return String(result);
        } catch (error) {
            this.log(`SCORM.debug.getDiagnosticInfo exception: ${error.message}`);
            return "";
        }
    }

    // =====================================================
    // DATA MODEL VALUE CACHING
    // =====================================================

    /**
     * Cache important data model values
     */
    cacheDataModelValue(parameter, value) {
        switch (parameter) {
            case "cmi.core.lesson_status":
            case "cmi.completion_status":
                this.data.completionStatus = value;
                break;

            case "cmi.core.exit":
            case "cmi.exit":
                this.data.exitStatus = value;
                break;

            case "cmi.core.lesson_location":
            case "cmi.location":
                this.data.lessonLocation = value;
                break;

            case "cmi.suspend_data":
                this.data.suspendData = value;
                break;

            case "cmi.score.raw":
                this.data.score.raw = value;
                break;

            case "cmi.score.min":
                this.data.score.min = value;
                break;

            case "cmi.score.max":
                this.data.score.max = value;
                break;
        }
    }

    /**
     * Handle initial completion status
     */
    handleInitialCompletionStatus() {
        const completionStatus = this.status("get");
        if (completionStatus) {
            switch (completionStatus) {
                case "not attempted":
                    this.status("set", "incomplete");
                    break;
                case "unknown":
                    this.status("set", "incomplete");
                    break;
            }
            // Commit changes
            this.save();
        }
    }

    // =====================================================
    // PROGRESS TRACKING INTEGRATION
    // =====================================================

    /**
     * Update progress tracking system
     */
    async updateProgressTracking() {
        if (!this.progressTracker || !this.currentContentId || !this.currentCourseId) {
            return;
        }

        try {
            // Get current SCORM data
            const scormData = this.getScormProgressData();
            
            // Update progress tracking
            await this.progressTracker.updateContentProgress(
                this.currentContentId,
                'scorm',
                this.currentCourseId,
                scormData
            );

            this.log('Progress tracking updated successfully', scormData);
        } catch (error) {
            this.log('Failed to update progress tracking', error);
        }
    }

    /**
     * Get comprehensive SCORM progress data
     */
    getScormProgressData() {
        if (!this.connection.isActive) {
            return {
                lesson_status: 'incomplete',
                lesson_location: '0',
                total_time: 'PT0M0S'
            };
        }

        try {
            // Get all relevant SCORM data
            const data = {
                lesson_status: this.get('cmi.lesson_status') || this.get('cmi.core.lesson_status') || 'incomplete',
                lesson_location: this.get('cmi.location') || this.get('cmi.core.lesson_location') || '0',
                progress_measure: this.get('cmi.progress_measure') || '',
                suspend_data: this.get('cmi.suspend_data') || '{}',
                total_time: this.get('cmi.total_time') || 'PT0M0S',
                session_time: this.get('cmi.session_time') || 'PT0M0S',
                score_raw: this.get('cmi.score.raw') || null,
                score_min: this.get('cmi.score.min') || null,
                score_max: this.get('cmi.score.max') || null
            };

            // Use cached values if available
            if (this.data.completionStatus) {
                data.lesson_status = this.data.completionStatus;
            }
            if (this.data.lessonLocation) {
                data.lesson_location = this.data.lessonLocation;
            }
            if (this.data.suspendData) {
                data.suspend_data = this.data.suspendData;
            }
            if (this.data.score.raw !== null) {
                data.score_raw = this.data.score.raw;
            }

            return data;
        } catch (error) {
            this.log('Error getting SCORM progress data', error);
            return {
                lesson_status: 'incomplete',
                lesson_location: '0',
                total_time: 'PT0M0S'
            };
        }
    }

    // =====================================================
    // UTILITY METHODS
    // =====================================================

    /**
     * Convert string to boolean
     */
    makeBoolean(value) {
        const t = typeof value;
        switch (t) {
            case "object":
            case "string":
                return (/(true|1)/i).test(value);
            case "number":
                return !!value;
            case "boolean":
                return value;
            case "undefined":
                return null;
            default:
                return false;
        }
    }

    /**
     * Get current SCORM state
     */
    getState() {
        return {
            version: this.API.version,
            isConnected: this.connection.isActive,
            isInitialized: this.connection.initialized,
            apiFound: this.API.isFound,
            data: this.data
        };
    }

    /**
     * Reset SCORM wrapper state
     */
    reset() {
        this.connection.isActive = false;
        this.connection.initialized = false;
        this.API.handle = null;
        this.API.isFound = false;
        this.data = {
            completionStatus: null,
            exitStatus: null,
            lessonLocation: null,
            suspendData: null,
            score: { raw: null, min: null, max: null }
        };
        
        this.log('SCORM wrapper state reset');
    }
}

// =====================================================
// SHORTCUT METHODS
// =====================================================

// Add shortcut methods to SCORMWrapper prototype for convenience
SCORMWrapper.prototype.init = function() { return this.initialize(); };
SCORMWrapper.prototype.quit = function() { return this.terminate(); };

// =====================================================
// GLOBAL EXPORT
// =====================================================

// Export for different module systems
if (typeof define === 'function' && define.amd) {
    // AMD
    define([], function() { return SCORMWrapper; });
} else if (typeof module === 'object' && module.exports) {
    // Node.js
    module.exports = SCORMWrapper;
} else {
    // Browser globals
    window.SCORMWrapper = SCORMWrapper;
    
    // Create a default instance
    window.scormWrapper = new SCORMWrapper({
        debugMode: true,
        progressTrackingEnabled: true
    });
}

// Auto-initialize if in a SCORM context
if (typeof window !== 'undefined') {
    // Wait for DOM to be ready
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
                    // Auto-initialize SCORM wrapper
                    if (!window.scormWrapper) {
                        window.scormWrapper = new SCORMWrapper({
                            debugMode: true,
                            progressTrackingEnabled: true
                        });
                    }
                    
                    // Try to initialize connection
                    setTimeout(() => {
                        if (window.scormWrapper && !window.scormWrapper.connection.isActive) {
                            window.scormWrapper.init();
                        }
                    }, 1000);
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
            
            if (api && !window.scormWrapper) {
                window.scormWrapper = new SCORMWrapper({
                    debugMode: true,
                    progressTrackingEnabled: true
                });
                
                setTimeout(() => {
                    if (window.scormWrapper && !window.scormWrapper.connection.isActive) {
                        window.scormWrapper.init();
                    }
                }, 1000);
            }
        }
    }
}
