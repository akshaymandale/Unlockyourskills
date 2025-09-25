/**
 * External Content Progress Tracking System
 * Handles external content visit tracking, time spent, and completion status
 */

class ExternalProgressTracker {
    constructor(options = {}) {
        this.options = {
            courseId: options.courseId || null,
            contentId: options.contentId || null,
            externalPackageId: options.externalPackageId || null,
            clientId: options.clientId || null,
            userId: options.userId || null,
            contentType: options.contentType || 'external',
            autoMarkCompleted: options.autoMarkCompleted || false,
            completionThreshold: options.completionThreshold || 30, // 30 seconds for completion
            saveInterval: options.saveInterval || 10000, // 10 seconds
            ...options
        };

        // Progress tracking state
        this.currentProgress = {
            visit_count: 0,
            time_spent: 0,
            is_completed: false,
            start_time: null,
            last_activity: null,
            session_time: 0
        };

        // Tracking properties
        this.isActive = false;
        this.saveQueue = [];
        this.isSaving = false;
        this.lastSaveTime = 0;
        this.saveTimeout = null;
        this.activityTimeout = null;
        this.localStorageKey = `external_progress_${this.options.contentId}`;

        // Initialize
        this.initialize();
    }

    /**
     * Initialize the external progress tracker
     */
    initialize() {
        if (!this.options.courseId || !this.options.contentId) {
            console.error('ExternalProgressTracker: Missing required parameters');
            return;
        }

        // Set up activity tracking
        this.setupActivityTracking();
        
        // Set up periodic saves
        this.setupPeriodicSaves();
        
        // Set up tab close protection
        this.setupTabCloseProtection();
        
        // Load existing progress
        this.loadExistingProgress();
        
        console.log('ExternalProgressTracker initialized for content:', this.options.contentId);
    }

    /**
     * Record visit to external content
     */
    recordVisit() {
        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            external_package_id: this.options.externalPackageId
        };

        fetch('/Unlockyourskills/external-progress/record-visit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('External content visit recorded');
                this.currentProgress.visit_count++;
                // Don't start session here - it will be started separately
            } else {
                console.error('Failed to record visit:', result.message);
            }
        })
        .catch(error => {
            console.error('Error recording visit:', error);
        });
    }

    /**
     * Start tracking session
     */
    startSession() {
        // Record visit when session starts
        this.recordVisit();
        
        this.currentProgress.start_time = Date.now();
        this.currentProgress.last_activity = Date.now();
        this.isActive = true;

        // Start time tracking
        this.startTimeTracking();
        
        console.log('External content session started');
    }

    /**
     * Start time tracking
     */
    startTimeTracking() {
        if (this.timeTrackingInterval) {
            clearInterval(this.timeTrackingInterval);
        }

        this.timeTrackingInterval = setInterval(() => {
            if (this.isActive) {
                this.currentProgress.session_time += 1;
                this.currentProgress.time_spent += 1;
                this.currentProgress.last_activity = Date.now();

                // Auto-complete if threshold reached
                if (this.options.autoMarkCompleted && 
                    !this.currentProgress.is_completed && 
                    this.currentProgress.time_spent >= this.options.completionThreshold) {
                    this.markCompleted('Auto-completed based on time threshold');
                }

                // Save progress periodically
                if (this.currentProgress.session_time % 10 === 0) { // Every 10 seconds
                    this.debouncedSave();
                }
            }
        }, 1000); // Every second
    }

    /**
     * Set up activity tracking
     */
    setupActivityTracking() {
        // Track user activity
        const activityEvents = ['click', 'scroll', 'keypress', 'mousemove', 'touchstart'];
        
        activityEvents.forEach(event => {
            document.addEventListener(event, () => this.onActivity(), { passive: true });
        });

        // Track focus/blur
        window.addEventListener('focus', () => this.onFocus());
        window.addEventListener('blur', () => this.onBlur());

        // Track visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.onBlur();
            } else {
                this.onFocus();
            }
        });
    }

    /**
     * Handle user activity
     */
    onActivity() {
        this.currentProgress.last_activity = Date.now();
        
        if (!this.isActive) {
            this.isActive = true;
            console.log('External content activity resumed');
        }

        // Reset inactivity timeout
        if (this.activityTimeout) {
            clearTimeout(this.activityTimeout);
        }

        // Set inactivity timeout (5 minutes)
        this.activityTimeout = setTimeout(() => {
            this.isActive = false;
            console.log('External content marked as inactive due to no activity');
        }, 300000); // 5 minutes
    }

    /**
     * Handle window focus
     */
    onFocus() {
        this.isActive = true;
        this.currentProgress.last_activity = Date.now();
        console.log('External content window focused');
    }

    /**
     * Handle window blur
     */
    onBlur() {
        this.isActive = false;
        this.debouncedSave();
        console.log('External content window blurred');
    }

    /**
     * Set up periodic saves
     */
    setupPeriodicSaves() {
        setInterval(() => {
            if (this.currentProgress.time_spent > 0) {
                this.debouncedSave();
            }
        }, this.options.saveInterval);
    }

    /**
     * Debounced save for frequent updates
     */
    debouncedSave() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        this.saveTimeout = setTimeout(() => {
            this.saveProgress();
        }, 2000); // 2 second debounce
    }

    /**
     * Save progress to server
     */
    saveProgress() {
        if (this.isSaving) {
            return;
        }

        this.isSaving = true;

        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            time_spent: this.currentProgress.time_spent,
            visit_count: this.currentProgress.visit_count,
            is_completed: this.currentProgress.is_completed ? 1 : 0
        };

        // Save to localStorage as backup
        this.saveToLocalStorage(data);

        fetch('/Unlockyourskills/external-progress/update-time-spent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            body: new URLSearchParams(data)
        })
        .then(response => {
            // First get the response as text to see what we're actually getting
            return response.text().then(text => {
                console.log('ExternalProgressTracker: Raw server response:', text.substring(0, 200) + (text.length > 200 ? '...' : ''));
                
                // Try to parse as JSON
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('ExternalProgressTracker: Server returned non-JSON response:', text);
                    throw new Error('Server returned non-JSON response: ' + text.substring(0, 100));
                }
            });
        })
        .then(result => {
            if (result.success) {
                console.log('External progress saved - Time spent:', this.formatTime(this.currentProgress.time_spent));
                this.lastSaveTime = Date.now();
            } else {
                console.error('Failed to save external progress:', result.message);
            }
        })
        .catch(error => {
            console.error('Error saving external progress:', error);
        })
        .finally(() => {
            this.isSaving = false;
        });
    }

    /**
     * Mark external content as completed
     */
    markCompleted(completionNotes = null) {
        if (this.currentProgress.is_completed) {
            return; // Already completed
        }

        this.currentProgress.is_completed = true;

        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            completion_notes: completionNotes
        };

        fetch('/Unlockyourskills/external-progress/mark-completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('External content marked as completed');
                this.showCompletionNotification();
                this.saveProgress(); // Save final progress
                // Set close flag when content is completed
                this.setExternalCloseFlag();
            } else {
                console.error('Failed to mark as completed:', result.message);
                this.currentProgress.is_completed = false; // Revert on failure
            }
        })
        .catch(error => {
            console.error('Error marking as completed:', error);
            this.currentProgress.is_completed = false; // Revert on failure
        });
    }

    /**
     * Show completion notification
     */
    showCompletionNotification() {
        const notification = document.createElement('div');
        notification.className = 'external-completion-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="notification-text">
                    <div class="notification-title">Content Completed!</div>
                    <div class="notification-message">You have successfully completed this external content.</div>
                    <div class="notification-time">Time spent: ${this.formatTime(this.currentProgress.time_spent)}</div>
                </div>
                <button type="button" class="notification-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        // Insert at the top of the page, after the header
        const header = document.querySelector('.viewer-header');
        if (header && header.parentNode) {
            header.parentNode.insertBefore(notification, header.nextSibling);
        } else {
            document.body.insertBefore(notification, document.body.firstChild);
        }
        
        // Add show class for animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // Auto-dismiss after 8 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 8000);
    }

    /**
     * Load existing progress from server
     */
    async loadExistingProgress() {
        try {
            const response = await fetch(`/Unlockyourskills/external-progress/statistics?course_id=${this.options.courseId}&content_id=${this.options.contentId}`);
            const result = await response.json();

            if (result.success) {
                this.currentProgress.visit_count = result.visit_count || 0;
                this.currentProgress.time_spent = result.time_spent || 0;
                this.currentProgress.is_completed = result.is_completed || false;

                console.log('External progress loaded - Time spent:', this.formatTime(this.currentProgress.time_spent));
                
                if (this.currentProgress.is_completed) {
                    this.showResumeNotification();
                }
            }
        } catch (error) {
            console.warn('Could not load existing external progress:', error);
        }
    }

    /**
     * Show resume notification
     */
    showResumeNotification() {
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            You have previously spent ${this.formatTime(this.currentProgress.time_spent)} on this content.
            ${this.currentProgress.is_completed ? '<strong>Status: Completed</strong>' : ''}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at the top of the page
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(notification, container.firstChild);
        
        // Auto-dismiss after 8 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 8000);
    }

    /**
     * Set up tab close protection
     */
    setupTabCloseProtection() {
        // Before unload event
        window.addEventListener('beforeunload', (e) => {
            if (this.currentProgress.time_spent > 0 && !this.currentProgress.is_completed) {
                this.beaconSave();
            }
            // Set close flag to notify parent page
            this.setExternalCloseFlag();
        });

        // Page hide event
        window.addEventListener('pagehide', (e) => {
            if (this.currentProgress.time_spent > 0 && !this.currentProgress.is_completed) {
                this.beaconSave();
            }
            // Set close flag to notify parent page
            this.setExternalCloseFlag();
        });

        // Unload event
        window.addEventListener('unload', (e) => {
            if (this.currentProgress.time_spent > 0 && !this.currentProgress.is_completed) {
                this.beaconSave();
            }
            // Set close flag to notify parent page
            this.setExternalCloseFlag();
        });
    }

    /**
     * Save progress using beacon API (for reliable unload transmission)
     */
    beaconSave() {
        // Don't save if already completed - this prevents overwriting completion status
        if (this.currentProgress.is_completed) {
            console.log('Skipping beacon save - content already completed');
            return;
        }

        // Only save if we have meaningful progress to save
        if (this.currentProgress.time_spent === 0) {
            return;
        }

        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            time_spent: this.currentProgress.time_spent,
            visit_count: this.currentProgress.visit_count,
            is_completed: 0 // Always 0 for beacon saves to avoid overwriting completion
        };

        // Convert data to FormData for beacon
        const formData = new FormData();
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        // Use beacon API if available
        if (navigator.sendBeacon) {
            const success = navigator.sendBeacon('/Unlockyourskills/external-progress/update-time-spent', formData);
            if (success) {
                console.log('External progress sent via beacon API');
                this.lastSaveTime = Date.now();
            } else {
                console.warn('Beacon API failed for external progress');
            }
        }

        // Save to localStorage as backup
        this.saveToLocalStorage(data);
    }

    /**
     * Set external content close flag to notify parent page
     */
    setExternalCloseFlag() {
        try {
            if (this.options.contentId) {
                // Check if flag is already set to prevent duplicates
                const existingFlag = localStorage.getItem('external_closed_' + this.options.contentId);
                if (!existingFlag) {
                    localStorage.setItem('external_closed_' + this.options.contentId, Date.now().toString());
                    console.log('ExternalProgressTracker: External close flag set for:', this.options.contentId);
                } else {
                    console.log('ExternalProgressTracker: External close flag already exists for:', this.options.contentId);
                }
            }
        } catch (error) {
            console.error('ExternalProgressTracker: Error setting external close flag:', error);
        }
    }

    /**
     * Save to localStorage as backup
     */
    saveToLocalStorage(data) {
        try {
            localStorage.setItem(this.localStorageKey, JSON.stringify({
                ...data,
                timestamp: Date.now(),
                session_time: this.currentProgress.session_time
            }));
        } catch (error) {
            console.warn('Failed to save external progress to localStorage:', error);
        }
    }

    /**
     * Clear localStorage data
     */
    clearLocalStorage() {
        try {
            localStorage.removeItem(this.localStorageKey);
        } catch (error) {
            console.warn('Failed to clear external progress localStorage:', error);
        }
    }

    /**
     * Format time in human readable format
     */
    formatTime(seconds) {
        if (seconds < 60) {
            return `${seconds} second${seconds !== 1 ? 's' : ''}`;
        } else if (seconds < 3600) {
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            return `${minutes} minute${minutes !== 1 ? 's' : ''}${remainingSeconds > 0 ? ` ${remainingSeconds} second${remainingSeconds !== 1 ? 's' : ''}` : ''}`;
        } else {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return `${hours} hour${hours !== 1 ? 's' : ''}${minutes > 0 ? ` ${minutes} minute${minutes !== 1 ? 's' : ''}` : ''}`;
        }
    }

    /**
     * Get current progress data
     */
    getProgressData() {
        return { ...this.currentProgress };
    }

    /**
     * Get statistics from server
     */
    async getStatistics() {
        try {
            const response = await fetch(`/Unlockyourskills/external-progress/statistics?course_id=${this.options.courseId}&content_id=${this.options.contentId}`);
            const result = await response.json();
            
            if (result.success) {
                return result;
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Error getting external progress statistics:', error);
            return null;
        }
    }

    /**
     * Manual completion trigger
     */
    completeManually(notes = null) {
        this.markCompleted(notes || 'Manually marked as completed');
    }

    /**
     * Handle external content opened in new tab
     * This method can be called when external content is actually opened
     */
    handleExternalContentOpened() {
        // Record that the content was actually opened
        this.recordVisit();
        
        // Start the session for time tracking
        this.startSession();
        
        console.log('External content opened and tracking started');
    }

    /**
     * Handle external content closed/returned from
     * This method can be called when user returns from external content
     */
    handleExternalContentReturned() {
        // Save current progress
        this.saveProgress();
        
        // Mark session as inactive
        this.isActive = false;
        
        console.log('External content session ended');
    }

    /**
     * Mark external content as viewed/completed
     * This method can be called when user indicates they've finished viewing the content
     */
    markAsViewed() {
        // Mark as completed
        this.markCompleted('User marked as viewed/completed');
        
        // Save progress
        this.saveProgress();
        
        console.log('External content marked as viewed/completed');
    }

    /**
     * Clean up resources
     */
    destroy() {
        // Save final progress
        this.beaconSave();
        
        // Clear intervals and timeouts
        if (this.timeTrackingInterval) {
            clearInterval(this.timeTrackingInterval);
        }
        
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        if (this.activityTimeout) {
            clearTimeout(this.activityTimeout);
        }
        
        // Clear localStorage
        this.clearLocalStorage();
        
        console.log('ExternalProgressTracker destroyed');
    }
}

// Global functions for external access
window.ExternalProgressTracker = ExternalProgressTracker;

// Utility function to create tracker for external content
window.createExternalProgressTracker = function(options) {
    return new ExternalProgressTracker(options);
};

// Utility function to mark external content as completed
window.markExternalContentCompleted = function(contentId, courseId) {
    const containers = document.querySelectorAll(`[data-content-id="${contentId}"][data-course-id="${courseId}"]`);
    containers.forEach(container => {
        if (container.externalProgressTracker) {
            container.externalProgressTracker.markAsViewed();
        }
    });
};

// Utility function to get external content progress status
window.getExternalContentProgress = function(contentId, courseId) {
    const containers = document.querySelectorAll(`[data-content-id="${contentId}"][data-course-id="${courseId}"]`);
    containers.forEach(container => {
        if (container.externalProgressTracker) {
            return container.externalProgressTracker.getProgressData();
        }
    });
    return null;
};

// Recovery function for lost data
window.recoverExternalProgress = function() {
    const keys = Object.keys(localStorage).filter(key => key.startsWith('external_progress_'));
    const recoveredData = [];
    
    keys.forEach(key => {
        try {
            const data = JSON.parse(localStorage.getItem(key));
            recoveredData.push({
                key: key,
                data: data,
                age: Date.now() - data.timestamp
            });
        } catch (error) {
            console.warn('Failed to parse localStorage data for key:', key);
        }
    });
    
    console.log('Recovered external progress data:', recoveredData);
    return recoveredData;
};

// Auto-initialize for external content pages
document.addEventListener('DOMContentLoaded', function() {
    // Look for external content containers with data attributes
    const externalContainers = document.querySelectorAll('[data-external-content]');
    
    externalContainers.forEach(container => {
        const options = {
            courseId: container.dataset.courseId,
            contentId: container.dataset.contentId,
            externalPackageId: container.dataset.externalPackageId,
            clientId: container.dataset.clientId,
            userId: container.dataset.userId,
            contentType: container.dataset.contentType || 'external',
            autoMarkCompleted: container.dataset.autoComplete === 'true'
        };

        if (options.courseId && options.contentId) {
            // Store options on the container but DON'T initialize tracker yet
            container.externalProgressOptions = options;
            
            // Add click event listener to initialize tracking only when clicked
            container.addEventListener('click', function(e) {
                // Only initialize if this is the external content launch button
                if (e.target.closest('.external-content-launch')) {
                    // Initialize tracker only when user actually clicks to open external content
                    if (!container.externalProgressTracker) {
                        container.externalProgressTracker = new ExternalProgressTracker(options);
                        console.log('ExternalProgressTracker initialized on click for container:', container);
                    }
                    
                    // Record the visit and start tracking when external content is opened
                    if (container.externalProgressTracker) {
                        container.externalProgressTracker.handleExternalContentOpened();
                    }
                }
            });

            // Add focus event listener to detect when user returns from external content
            window.addEventListener('focus', function() {
                if (container.externalProgressTracker && container.externalProgressTracker.isActive) {
                    // User returned from external content - end the session
                    container.externalProgressTracker.handleExternalContentReturned();
                }
            });
            
            console.log('External content container prepared for tracking:', container);
        }
    });
});
