/**
 * Progress Tracking JavaScript Library
 * Handles progress tracking for all content types and resume functionality
 * Works with course_applicability instead of course_enrollments
 */

class ProgressTracker {
    constructor() {
        this.currentCourseId = null;
        this.currentModuleId = null;
        this.currentContentId = null;
        this.currentContentType = null;
        this.userId = null;
        this.clientId = null;
        this.isInitialized = false;
        
        // Progress tracking intervals
        this.progressIntervals = new Map();
        this.lastProgressUpdate = new Map();
        
        // Resume data
        this.resumeData = null;
        
        // Wait for user data to be available before initializing
        this.waitForUserData();
    }

    /**
     * Wait for user data to be available before initializing
     */
    waitForUserData() {
        if (window.userData && window.userData.id && window.userData.client_id) {
            this.init();
        } else {
            // Check session storage
            const sessionData = sessionStorage.getItem('user_data');
            if (sessionData) {
                try {
                    const userData = JSON.parse(sessionData);
                    if (userData.id && userData.client_id) {
                        this.init();
                        return;
                    }
                } catch (e) {
                    // Handle parsing errors silently
                }
            }
            
            // Wait a bit and try again
            setTimeout(() => this.waitForUserData(), 100);
        }
    }

    /**
     * Initialize the progress tracker
     */
    init() {
        // Get user info from session or page data
        this.userId = this.getUserId();
        this.clientId = this.getClientId();
        
        if (this.userId && this.clientId) {
            this.isInitialized = true;
        } else {
            // Retry after a short delay in case user data is loaded asynchronously
            setTimeout(() => this.init(), 1000);
        }
    }

    /**
     * Get user ID from session or page data
     */
    getUserId() {
        // Try to get from page data first
        if (window.userData && window.userData.id) {
            return window.userData.id;
        }
        
        // Try to get from session storage
        const sessionData = sessionStorage.getItem('user_data');
        if (sessionData) {
            try {
                const userData = JSON.parse(sessionData);
                return userData.id;
            } catch (e) {
                // Handle parsing errors silently
            }
        }
        
        return null;
    }

    /**
     * Get client ID from session or page data
     */
    getClientId() {
        // Try to get from page data first
        if (window.userData && window.userData.client_id) {
            return window.userData.client_id;
        }
        
        // Try to get from session storage
        const sessionData = sessionStorage.getItem('user_data');
        if (sessionData) {
            try {
                const userData = JSON.parse(sessionData);
                return userData.client_id;
            } catch (e) {
                // Handle parsing errors silently
            }
        }
        
        return null;
    }

    /**
     * Set course context and load resume data
     */
    async setCourseContext(courseId, moduleId, contentId, contentType) {
        console.log('Setting course context:', { courseId, moduleId, contentId, contentType });
        
        this.currentCourseId = courseId;
        this.currentModuleId = moduleId;
        this.currentContentId = contentId;
        this.currentContentType = contentType;
        
        // Load resume position if available
        if (this.isInitialized) {
            console.log('Progress tracker initialized, loading resume position...');
            await this.loadResumePosition();
        } else {
            console.log('Progress tracker not initialized yet');
        }
    }

    /**
     * Initialize course progress
     */
    async initializeCourseProgress() {
        if (!this.isInitialized || !this.currentCourseId) {
            return false;
        }

        try {
            const response = await fetch('/unlockyourskills/progress/initialize', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    course_id: this.currentCourseId
                })
            });

            const data = await response.json();
            
            if (data.success) {
                // Load resume position if available (temporarily disabled for debugging)
                try {
                    await this.loadResumePosition();
                } catch (error) {
                    // Handle resume position loading errors silently
                }
                
                return true;
            } else {
                console.error('Failed to initialize course progress:', data.message);
                return false;
            }
        } catch (error) {
            console.error('Error initializing course progress:', error);
            return false;
        }
    }

    /**
     * Load resume position for current course
     */
    async loadResumePosition() {
        if (!this.isInitialized || !this.currentCourseId || !this.currentContentId) {
            console.log('Cannot load resume position:', {
                isInitialized: this.isInitialized,
                currentCourseId: this.currentCourseId,
                currentContentId: this.currentContentId
            });
            return false;
        }

        try {
            console.log('Loading resume position for course:', this.currentCourseId, 'content:', this.currentContentId);
            
            // Call the resume API with both course_id and content_id
            const response = await fetch(`/unlockyourskills/progress/resume/get?course_id=${this.currentCourseId}&content_id=${this.currentContentId}`);
            
            // Check if response is valid
            if (!response.ok) {
                console.log('Resume API response not OK:', response.status);
                return false;
            }
            
            const data = await response.json();
            console.log('Resume API response:', data);
            
            if (data.success && data.resume_data) {
                this.resumeData = data.resume_data;
                console.log('Resume data loaded:', this.resumeData);
                
                // Check for SCORM data specifically
                if (this.resumeData.scorm_data) {
                    console.log('SCORM data found - triggering resume event');
                    this.triggerResumeEvent();
                    return true;
                } else {
                    console.log('No SCORM data in resume data');
                    console.log('Resume data structure:', this.resumeData);
                    console.log('Available keys:', Object.keys(this.resumeData));
                }
            } else {
                console.log('No resume data in API response');
            }
            
        } catch (error) {
            console.error('Error loading resume position:', error);
        }
        
        return false;
    }

    /**
     * Set resume position
     */
    async setResumePosition(moduleId = null, contentId = null, resumePosition = null) {
        
        if (!this.isInitialized || !this.currentCourseId) {
            return false;
        }

        try {
            const requestBody = {
                course_id: this.currentCourseId,
                module_id: moduleId || this.currentModuleId,
                content_id: contentId || this.currentContentId,
                resume_position: resumePosition
            };
            
            const response = await fetch('/unlockyourskills/progress/resume/set', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody)
            });

            const data = await response.json();
            
            if (data.success) {
                return true;
            } else {
                console.error('Failed to set resume position:', data.message);
                return false;
            }
        } catch (error) {
            console.error('Error setting resume position:', error);
            return false;
        }
    }

    /**
     * Update module progress
     */
    async updateModuleProgress(moduleId, data) {
        if (!this.isInitialized || !this.currentCourseId) {
            return false;
        }

        try {
            const response = await fetch('/unlockyourskills/progress/module/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    course_id: this.currentCourseId,
                    module_id: moduleId,
                    data: data
                })
            });

            const result = await response.json();
            
            if (result.success) {
                return true;
            } else {
                console.error('Failed to update module progress:', result.message);
                return false;
            }
        } catch (error) {
            console.error('Error updating module progress:', error);
            return false;
        }
    }

    /**
     * Update content progress
     */
    async updateContentProgress(contentId, contentType, courseId, data) {
        if (!this.isInitialized || !courseId) {
            return false;
        }

        try {
            const requestBody = {
                course_id: courseId,
                content_id: contentId,
                content_type: contentType,
                data: data
            };
            
            const response = await fetch('/unlockyourskills/progress/content/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(requestBody)
            });

            const result = await response.json();
            
            if (result.success) {
                return true;
            } else {
                console.error('Failed to update content progress:', result.message);
                return false;
            }
        } catch (error) {
            console.error('Error updating content progress:', error);
            return false;
        }
    }

    /**
     * Start progress tracking for content
     */
    startProgressTracking(contentId, contentType, intervalMs = 5000) {
        
        if (!this.isInitialized || !this.currentCourseId) {
            return false;
        }

        const trackingKey = `${contentId}_${contentType}`;
        
        // Stop existing tracking if any
        this.stopProgressTracking(contentId, contentType);
        
        // Start new tracking interval
        const interval = setInterval(async () => {
            await this.trackProgress(contentId, contentType);
        }, intervalMs);
        
        this.progressIntervals.set(trackingKey, interval);
        
        return true;
    }

    /**
     * Stop progress tracking for content
     */
    stopProgressTracking(contentId, contentType) {
        const trackingKey = `${contentId}_${contentType}`;
        const interval = this.progressIntervals.get(trackingKey);
        
        if (interval) {
            clearInterval(interval);
            this.progressIntervals.delete(trackingKey);
        }
    }

    /**
     * Track progress for content (called by interval)
     */
    async trackProgress(contentId, contentType) {
        
        // For SCORM content, use the working approach from content_viewer.php
        if (contentType === 'scorm') {
            const scormProgressData = await this.getScormProgressDataDirect();
            if (scormProgressData) {
                await this.updateContentProgress(contentId, contentType, this.currentCourseId, scormProgressData);
                return;
            }
        }
        
        // Get current progress data based on content type
        const progressData = this.getCurrentProgressData(contentType);
        
        if (progressData) {
            await this.updateContentProgress(contentId, contentType, this.currentCourseId, progressData);
        }
    }

    /**
     * Get SCORM progress data using a working approach
     */
    async getScormProgressDataDirect() {
        try {
            
            // Try to get SCORM data directly from the iframe
            const scormIframe = document.querySelector('iframe');
            if (!scormIframe || !scormIframe.contentWindow) {
                return null;
            }

            
            // Try multiple approaches to access SCORM API
            let api = null;
            
            // Approach 1: Direct access
            try {
                api = scormIframe.contentWindow.API;
                if (api && api.GetValue) {
                }
            } catch (e) {
            }
            
            // Approach 2: Try different API object names
            if (!api || !api.GetValue) {
                try {
                    api = scormIframe.contentWindow.api;
                    if (api && api.GetValue) {
                    }
                } catch (e) {
                }
            }
            
            // Approach 3: Try to find API in global scope
            if (!api || !api.GetValue) {
                try {
                    // Execute a script in the iframe to find the API
                    const findApiScript = `
                        (function() {
                            let foundApi = null;
                            
                            // Check common API object names
                            if (window.API && window.API.GetValue) {
                                foundApi = window.API;
                            } else if (window.api && window.api.GetValue) {
                                foundApi = window.api;
                            } else if (window.API_1484_11 && window.API_1484_11.GetValue) {
                                foundApi = window.API_1484_11;
                            }
                            
                            // Return the API object name if found
                            if (foundApi) {
                                return 'API_FOUND';
                            } else {
                                return 'API_NOT_FOUND';
                            }
                        })();
                    `;
                    
                    const result = scormIframe.contentWindow.eval(findApiScript);
                    if (result === 'API_FOUND') {
                        // Try to access it again
                        api = scormIframe.contentWindow.API || scormIframe.contentWindow.api;
                    }
                } catch (e) {
                }
            }
            
            // If we found an API, try to get the data
            if (api && api.GetValue) {
                
                try {
                    // Get actual SCORM data
                    const lessonLocation = api.GetValue('cmi.location') || '';
                    const lessonStatus = api.GetValue('cmi.lesson_status') || 'incomplete';
                    const progressMeasure = api.GetValue('cmi.progress_measure') || '';
                    const suspendData = api.GetValue('cmi.suspend_data') || '{}';
                    const totalTime = api.GetValue('cmi.total_time') || 'PT0M0S';
                    const sessionTime = api.GetValue('cmi.session_time') || 'PT0M0S';
                    const scoreRaw = api.GetValue('cmi.score.raw') || '';
                    const scoreMin = api.GetValue('cmi.score.min') || '';
                    const scoreMax = api.GetValue('cmi.score.max') || '';

                    return {
                        lesson_status: lessonStatus,
                        lesson_location: lessonLocation,
                        progress_measure: progressMeasure,
                        suspend_data: suspendData,
                        total_time: totalTime,
                        session_time: sessionTime,
                        score_raw: scoreRaw || null,
                        score_min: scoreMin || null,
                        score_max: scoreMax || null
                    };
                } catch (getError) {
                }
            }
            
            return null;
            
        } catch (error) {
            console.error('Error:', error.message);
            console.error('Error stack:', error.stack);
            return null;
        }
    }

    /**
     * Get current progress data based on content type
     */
    getCurrentProgressData(contentType) {
        switch (contentType) {
            case 'video':
                return this.getVideoProgressData();
            case 'audio':
                return this.getAudioProgressData();
            case 'document':
                return this.getDocumentProgressData();
            case 'scorm':
                return this.getScormProgressData();
            case 'interactive':
                return this.getInteractiveProgressData();
            case 'external':
                return this.getExternalProgressData();
            default:
                return null;
        }
    }

    /**
     * Get video progress data
     */
    getVideoProgressData() {
        const videoElement = document.querySelector('video');
        if (!videoElement) return null;

        const currentTime = Math.floor(videoElement.currentTime);
        const duration = Math.floor(videoElement.duration);
        const watchedPercentage = duration > 0 ? Math.round((currentTime / duration) * 100) : 0;
        
        return {
            current_time: currentTime,
            duration: duration,
            watched_percentage: watchedPercentage,
            is_completed: watchedPercentage >= 80, // 80% threshold for completion
            play_count: 1,
            last_watched_at: new Date().toISOString()
        };
    }

    /**
     * Get audio progress data
     */
    getAudioProgressData() {
        const audioElement = document.querySelector('audio');
        if (!audioElement) return null;

        const currentTime = Math.floor(audioElement.currentTime);
        const duration = Math.floor(audioElement.duration);
        const listenedPercentage = duration > 0 ? Math.round((currentTime / duration) * 100) : 0;
        
        return {
            current_time: currentTime,
            duration: duration,
            listened_percentage: listenedPercentage,
            is_completed: listenedPercentage >= 80, // 80% threshold for completion
            play_count: 1,
            last_listened_at: new Date().toISOString(),
            playback_speed: audioElement.playbackRate || 1.0
        };
    }

    /**
     * Get document progress data
     */
    getDocumentProgressData() {
        // This would need to be implemented based on the document viewer being used
        // For now, return basic data
        return {
            current_page: 1,
            total_pages: 1,
            viewed_percentage: 100,
            is_completed: true,
            last_viewed_at: new Date().toISOString()
        };
    }

    /**
     * Get SCORM progress data
     */
    getScormProgressData() {
        try {
            
            // Try to get SCORM data from the iframe
            const scormIframe = document.querySelector('iframe');
            if (!scormIframe || !scormIframe.contentWindow) {
                return {
                    lesson_status: 'incomplete',
                    lesson_location: '0',
                    total_time: 'PT0M0S'
                };
            }

            
            const api = scormIframe.contentWindow.API;
            if (!api || !api.GetValue) {
                return {
                    lesson_status: 'incomplete',
                    lesson_location: '0',
                    total_time: 'PT0M0S'
                };
            }

            
            // Get actual SCORM data
            const lessonLocation = api.GetValue('cmi.location') || '';
            const lessonStatus = api.GetValue('cmi.lesson_status') || 'incomplete';
            const progressMeasure = api.GetValue('cmi.progress_measure') || '';
            const suspendData = api.GetValue('cmi.suspend_data') || '{}';
            const totalTime = api.GetValue('cmi.total_time') || 'PT0M0S';
            const sessionTime = api.GetValue('cmi.session_time') || 'PT0M0S';
            const scoreRaw = api.GetValue('cmi.score.raw') || '';
            const scoreMin = api.GetValue('cmi.score.min') || '';
            const scoreMax = api.GetValue('cmi.score.max') || '';

            return {
                lesson_status: lessonStatus,
                lesson_location: lessonLocation,
                progress_measure: progressMeasure,
                suspend_data: suspendData,
                total_time: totalTime,
                session_time: sessionTime,
                score_raw: scoreRaw || null,
                score_min: scoreMin || null,
                score_max: scoreMax || null
            };
        } catch (error) {
            console.error('Error:', error.message);
            console.error('Error stack:', error.stack);
            return {
                lesson_status: 'incomplete',
                lesson_location: '0',
                total_time: 'PT0M0S'
            };
        }
    }

    /**
     * Get interactive content progress data
     */
    getInteractiveProgressData() {
        // This would need to be implemented based on the interactive content being used
        // For now, return basic data
        return {
            current_step: 1,
            total_steps: 1,
            completion_percentage: 100,
            is_completed: true,
            last_interaction_at: new Date().toISOString()
        };
    }

    /**
     * Get external content progress data
     */
    getExternalProgressData() {
        return {
            visit_count: 1,
            last_visited_at: new Date().toISOString(),
            time_spent: 0,
            is_completed: true
        };
    }

    /**
     * Mark content as completed
     */
    async markContentCompleted(contentId, contentType, completionData = {}) {
        if (!this.isInitialized || !this.currentCourseId) {
            return false;
        }

        const data = {
            ...completionData,
            is_completed: true,
            completed_at: new Date().toISOString()
        };

        return await this.updateContentProgress(contentId, contentType, this.currentCourseId, data);
    }

    /**
     * Mark module as completed
     */
    async markModuleCompleted(moduleId, completionData = {}) {
        if (!this.isInitialized || !this.currentCourseId) {
            return false;
        }

        const data = {
            ...completionData,
            status: 'completed',
            completed_at: new Date().toISOString()
        };

        return await this.updateModuleProgress(moduleId, data);
    }

    /**
     * Calculate and update overall course progress
     */
    async calculateCourseProgress() {
        if (!this.isInitialized || !this.currentCourseId) {
            return false;
        }

        try {
            const response = await fetch(`/unlockyourskills/progress/calculate?course_id=${this.currentCourseId}`);
            const data = await response.json();
            
            if (data.success) {
                // Trigger progress update event
                this.triggerProgressUpdateEvent(data.progress);
                
                return data.progress;
            } else {
                console.error('Failed to calculate course progress:', data.message);
                return false;
            }
        } catch (error) {
            console.error('Error calculating course progress:', error);
            return false;
        }
    }

    /**
     * Get user progress summary
     */
    async getUserProgressSummary() {
        if (!this.isInitialized) {
            return false;
        }

        try {
            const response = await fetch('/unlockyourskills/progress/summary');
            const data = await response.json();
            
            if (data.success) {
                return data.summary;
            } else {
                console.error('Failed to get user progress summary:', data.message);
                return false;
            }
        } catch (error) {
            console.error('Error getting user progress summary:', error);
            return false;
        }
    }

    /**
     * Trigger resume event for content players
     */
    triggerResumeEvent() {
        if (this.resumeData) {
            console.log('Triggering resume event with data:', this.resumeData);
            const event = new CustomEvent('progressResume', {
                detail: this.resumeData
            });
            window.dispatchEvent(event);
            console.log('Resume event dispatched');
        } else {
            console.log('No resume data to trigger event');
        }
    }

    /**
     * Trigger progress update event
     */
    triggerProgressUpdateEvent(progress) {
        const event = new CustomEvent('progressUpdate', {
            detail: progress
        });
        document.dispatchEvent(event);
    }

    /**
     * Clean up resources
     */
    destroy() {
        // Stop all progress tracking intervals
        for (const [key, interval] of this.progressIntervals) {
            clearInterval(interval);
        }
        this.progressIntervals.clear();
        
        // Clear current context
        this.currentCourseId = null;
        this.currentModuleId = null;
        this.currentContentId = null;
        this.currentContentType = null;
        this.isInitialized = false;
        
    }
}

// Create global instance
window.progressTracker = new ProgressTracker();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProgressTracker;
}

