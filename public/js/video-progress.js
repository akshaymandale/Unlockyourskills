/**
 * Video Progress Tracking System
 * Handles video playback progress, resume functionality, and data persistence
 */

class VideoProgressTracker {
    constructor(videoElement, options = {}) {
        this.video = videoElement;
        this.options = {
            courseId: options.courseId || null,
            contentId: options.contentId || null,
            videoPackageId: options.videoPackageId || null,
            clientId: options.clientId || null,
            userId: options.userId || null,
            completionThreshold: options.completionThreshold || 80,
            saveInterval: options.saveInterval || 5000, // 5 seconds
            ...options
        };

        // Progress tracking state
        this.currentProgress = {
            current_time: 0,
            duration: 0,
            watched_percentage: 0,
            is_completed: false,
            video_status: 'not_started',
            play_count: 0
        };

        // Hybrid approach properties
        this.saveQueue = [];
        this.isSaving = false;
        this.lastSaveTime = 0;
        this.saveTimeout = null;
        this.beaconQueue = [];
        this.localStorageKey = `video_progress_${this.options.contentId}`;

        // Initialize
        this.initialize();
    }

    /**
     * Initialize the video progress tracker
     */
    initialize() {
        if (!this.video || !this.options.courseId || !this.options.contentId) {
            console.error('VideoProgressTracker: Missing required parameters');
            return;
        }

        // Set up event listeners
        this.setupEventListeners();
        
        // Load existing progress
        this.loadExistingProgress();
        
        // Set up hybrid approach
        this.setupHybridApproach();
        
        // Set up tab close protection
        this.setupTabCloseProtection();
        
        console.log('VideoProgressTracker initialized for content:', this.options.contentId);
    }

    /**
     * Set up video event listeners
     */
    setupEventListeners() {
        // Play event
        this.video.addEventListener('play', () => this.onPlay());
        
        // Pause event
        this.video.addEventListener('pause', () => this.onPause());
        
        // Time update event
        this.video.addEventListener('timeupdate', () => this.onTimeUpdate());
        
        // Ended event
        this.video.addEventListener('ended', () => this.onEnded());
        
        // Loaded metadata event
        this.video.addEventListener('loadedmetadata', () => this.onLoadedMetadata());
        
        // Seeking event
        this.video.addEventListener('seeking', () => this.onSeeking());
        
        // Seeked event
        this.video.addEventListener('seeked', () => this.onSeeked());
        
        // Volume change event
        this.video.addEventListener('volumechange', () => this.onVolumeChange());
        
        // Rate change event
        this.video.addEventListener('ratechange', () => this.onRateChange());
    }

    /**
     * Handle play event
     */
    onPlay() {
        this.currentProgress.play_count++;
        this.currentProgress.current_time = this.video.currentTime;
        this.currentProgress.duration = this.video.duration;
        this.currentProgress.video_status = 'in_progress';
        this.updateWatchedPercentage();
        
        // Immediate save for play action
        this.immediateSave('play');
        
        console.log('Video started playing at:', this.formatTime(this.video.currentTime));
    }

    /**
     * Handle pause event
     */
    onPause() {
        this.currentProgress.current_time = this.video.currentTime;
        this.currentProgress.video_status = 'paused';
        this.updateWatchedPercentage();
        
        // Immediate save for pause action
        this.immediateSave('pause');
        
        console.log('Video paused at:', this.formatTime(this.video.currentTime));
    }

    /**
     * Handle time update event
     */
    onTimeUpdate() {
        this.currentProgress.current_time = this.video.currentTime;
        this.updateWatchedPercentage();
        
        // Debounced save for time updates
        this.debouncedSave();
    }

    /**
     * Handle video ended event
     */
    onEnded() {
        this.currentProgress.current_time = this.video.duration;
        this.currentProgress.watched_percentage = 100;
        this.currentProgress.is_completed = true;
        this.currentProgress.video_status = 'completed';
        
        // Immediate save for completion
        this.immediateSave('ended');
        
        console.log('Video completed - 100% watched');
    }

    /**
     * Handle loaded metadata event
     */
    onLoadedMetadata() {
        this.currentProgress.duration = this.video.duration;
        console.log('Video metadata loaded - Duration:', this.formatTime(this.video.duration));
    }

    /**
     * Handle seeking event
     */
    onSeeking() {
        console.log('Video seeking to:', this.formatTime(this.video.currentTime));
    }

    /**
     * Handle seeked event
     */
    onSeeked() {
        this.currentProgress.current_time = this.video.currentTime;
        this.currentProgress.video_status = 'in_progress';
        this.updateWatchedPercentage();
        
        // Save after seeking
        this.immediateSave('seeked');
        
        console.log('Video seeked to:', this.formatTime(this.video.currentTime));
    }

    /**
     * Handle volume change event
     */
    onVolumeChange() {
        console.log('Volume changed to:', this.video.volume);
    }

    /**
     * Handle rate change event
     */
    onRateChange() {
        console.log('Playback rate changed to:', this.video.playbackRate);
    }

    /**
     * Update watched percentage
     */
    updateWatchedPercentage() {
        if (this.currentProgress.duration > 0) {
            this.currentProgress.watched_percentage = Math.round(
                (this.currentProgress.current_time / this.currentProgress.duration) * 100
            );
            
            // Check if video is completed based on threshold
            if (this.currentProgress.watched_percentage >= this.options.completionThreshold) {
                this.currentProgress.is_completed = true;
                this.currentProgress.video_status = 'completed';
            } else if (this.currentProgress.watched_percentage > 0) {
                this.currentProgress.video_status = 'in_progress';
            }
        }
    }

    /**
     * Immediate save for critical actions
     */
    immediateSave(action) {
        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            video_package_id: this.options.videoPackageId,
            current_time: this.currentProgress.current_time,
            duration: this.currentProgress.duration,
            watched_percentage: this.currentProgress.watched_percentage,
            is_completed: this.currentProgress.is_completed ? 1 : 0,
            video_status: this.currentProgress.video_status,
            play_count: this.currentProgress.play_count,
            action: action
        };

        // Save to localStorage as backup
        this.saveToLocalStorage(data);

        // Send to server immediately
        fetch('/Unlockyourskills/video-progress/immediate-save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('Video progress saved immediately:', action);
                this.lastSaveTime = Date.now();
            } else {
                console.error('Failed to save video progress:', result.message);
                this.queueForRetry(data, 'immediate');
            }
        })
        .catch(error => {
            console.error('Error saving video progress:', error);
            this.queueForRetry(data, 'immediate');
        });
    }

    /**
     * Debounced save for frequent updates
     */
    debouncedSave() {
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }

        this.saveTimeout = setTimeout(() => {
            const data = {
                course_id: this.options.courseId,
                content_id: this.options.contentId,
                video_package_id: this.options.videoPackageId,
                current_time: this.currentProgress.current_time,
                duration: this.currentProgress.duration,
                watched_percentage: this.currentProgress.watched_percentage,
                is_completed: this.currentProgress.is_completed ? 1 : 0,
                video_status: this.currentProgress.video_status,
                play_count: this.currentProgress.play_count
            };

            // Save to localStorage as backup
            this.saveToLocalStorage(data);

            // Add to save queue for batch processing
            this.saveQueue.push(data);
            
            // Process queue if it gets too large
            if (this.saveQueue.length >= 5) {
                this.processSaveQueue();
            }
        }, 2000); // 2 second debounce
    }

    /**
     * Process the save queue
     */
    processSaveQueue() {
        if (this.isSaving || this.saveQueue.length === 0) {
            return;
        }

        this.isSaving = true;
        const updates = [...this.saveQueue];
        this.saveQueue = [];

        fetch('/Unlockyourskills/video-progress/batch-update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                updates: JSON.stringify(updates)
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('Video progress batch update successful:', result.message);
                this.lastSaveTime = Date.now();
            } else {
                console.error('Failed to batch update video progress:', result.message);
                // Re-queue failed updates
                this.saveQueue.unshift(...updates);
            }
        })
        .catch(error => {
            console.error('Error in batch update:', error);
            // Re-queue failed updates
            this.saveQueue.unshift(...updates);
        })
        .finally(() => {
            this.isSaving = false;
        });
    }

    /**
     * Save progress using beacon API (for reliable unload transmission)
     */
    beaconSave() {
        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            video_package_id: this.options.videoPackageId,
            current_time: this.currentProgress.current_time,
            duration: this.currentProgress.duration,
            watched_percentage: this.currentProgress.watched_percentage,
            is_completed: this.currentProgress.is_completed ? 1 : 0,
            video_status: this.currentProgress.video_status,
            play_count: this.currentProgress.play_count
        };

        // Convert data to FormData for beacon
        const formData = new FormData();
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        // Use beacon API if available
        if (navigator.sendBeacon) {
            const success = navigator.sendBeacon('/Unlockyourskills/video-progress/beacon-save', formData);
            if (success) {
                console.log('Video progress sent via beacon API');
                this.lastSaveTime = Date.now();
            } else {
                console.warn('Beacon API failed, falling back to fetch');
                this.fallbackSave(data);
            }
        } else {
            this.fallbackSave(data);
        }
    }

    /**
     * Fallback save method
     */
    fallbackSave(data) {
        fetch('/Unlockyourskills/video-progress/immediate-save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('Video progress saved via fallback method');
                this.lastSaveTime = Date.now();
            } else {
                console.error('Fallback save failed:', result.message);
            }
        })
        .catch(error => {
            console.error('Error in fallback save:', error);
        });
    }

    /**
     * Save to localStorage as backup
     */
    saveToLocalStorage(data) {
        try {
            localStorage.setItem(this.localStorageKey, JSON.stringify({
                ...data,
                timestamp: Date.now()
            }));
        } catch (error) {
            console.warn('Failed to save to localStorage:', error);
        }
    }

    /**
     * Clear localStorage data
     */
    clearLocalStorage() {
        try {
            localStorage.removeItem(this.localStorageKey);
        } catch (error) {
            console.warn('Failed to clear localStorage:', error);
        }
    }

    /**
     * Queue data for retry
     */
    queueForRetry(data, type) {
        this.beaconQueue.push({
            data: data,
            type: type,
            timestamp: Date.now()
        });
        
        // Process retry queue after a delay
        setTimeout(() => this.processRetryQueue(), 5000);
    }

    /**
     * Process retry queue
     */
    processRetryQueue() {
        if (this.beaconQueue.length === 0) {
            return;
        }

        const retryItem = this.beaconQueue.shift();
        
        if (retryItem.type === 'immediate') {
            this.immediateSave('retry');
        } else {
            this.beaconSave();
        }
    }

    /**
     * Set up hybrid approach for data persistence
     */
    setupHybridApproach() {
        // Process save queue periodically
        setInterval(() => {
            if (this.saveQueue.length > 0) {
                this.processSaveQueue();
            }
        }, 10000); // Every 10 seconds

        // Process retry queue periodically
        setInterval(() => {
            if (this.beaconQueue.length > 0) {
                this.processRetryQueue();
            }
        }, 15000); // Every 15 seconds
    }

    /**
     * Set up tab close protection
     */
    setupTabCloseProtection() {
        // Before unload event
        window.addEventListener('beforeunload', (e) => {
            if (this.currentProgress.watched_percentage > 0) {
                this.beaconSave();
                // Don't show confirmation dialog, just save data
            }
        });

        // Page hide event
        window.addEventListener('pagehide', (e) => {
            if (this.currentProgress.watched_percentage > 0) {
                this.beaconSave();
            }
        });

        // Unload event
        window.addEventListener('unload', (e) => {
            if (this.currentProgress.watched_percentage > 0) {
                this.beaconSave();
            }
        });

        // Visibility change event
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && this.currentProgress.watched_percentage > 0) {
                this.beaconSave();
            }
        });
    }

    /**
     * Load existing progress from server
     */
    async loadExistingProgress() {
        try {
            const response = await fetch(`/Unlockyourskills/video-progress/resume-position?course_id=${this.options.courseId}&content_id=${this.options.contentId}`);
            const result = await response.json();

            if (result.success && result.resume_position > 0) {
                this.currentProgress.current_time = result.resume_position;
                this.currentProgress.duration = result.duration;
                this.currentProgress.watched_percentage = result.watched_percentage;
                this.currentProgress.is_completed = result.is_completed;
                this.currentProgress.video_status = result.video_status || 'not_started';

                // Resume from last position
                this.video.currentTime = result.resume_position;
                
                console.log('Video progress loaded - Resuming from:', this.formatTime(result.resume_position));
                
                // Update UI to show resume position
                this.showResumeNotification();
            }
        } catch (error) {
            console.warn('Could not load existing video progress:', error);
        }
    }

    /**
     * Show resume notification
     */
    showResumeNotification() {
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.innerHTML = `
            <i class="fas fa-play-circle me-2"></i>
            Video will resume from ${this.formatTime(this.currentProgress.current_time)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insert at the top of the page
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(notification, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * Format time in MM:SS format
     */
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    /**
     * Get current progress data
     */
    getProgressData() {
        return { ...this.currentProgress };
    }

    /**
     * Set video position
     */
    setPosition(seconds) {
        if (this.video && !isNaN(seconds)) {
            this.video.currentTime = Math.max(0, Math.min(seconds, this.video.duration));
            this.currentProgress.current_time = this.video.currentTime;
            this.updateWatchedPercentage();
        }
    }

    /**
     * Clean up resources
     */
    destroy() {
        // Save final progress
        this.beaconSave();
        
        // Clear intervals and timeouts
        if (this.saveTimeout) {
            clearTimeout(this.saveTimeout);
        }
        
        // Clear localStorage
        this.clearLocalStorage();
        
        // Remove event listeners
        this.video.removeEventListener('play', this.onPlay);
        this.video.removeEventListener('pause', this.onPause);
        this.video.removeEventListener('timeupdate', this.onTimeUpdate);
        this.video.removeEventListener('ended', this.onEnded);
        this.video.removeEventListener('loadedmetadata', this.onLoadedMetadata);
        this.video.removeEventListener('seeking', this.onSeeking);
        this.video.removeEventListener('seeked', this.onSeeked);
        this.video.removeEventListener('volumechange', this.onVolumeChange);
        this.video.removeEventListener('ratechange', this.onRateChange);
        
        console.log('VideoProgressTracker destroyed');
    }
}

// Global functions for external access
window.VideoProgressTracker = VideoProgressTracker;

// Test function for debugging
window.testVideoProgress = function(videoElement) {
    if (!videoElement) {
        console.error('No video element provided');
        return;
    }
    
    const tracker = new VideoProgressTracker(videoElement, {
        courseId: 1,
        contentId: 1,
        videoPackageId: 1,
        clientId: 1,
        userId: 1
    });
    
    console.log('Video progress tracker created:', tracker);
    return tracker;
};

// Recovery function for lost data
window.recoverVideoProgress = function() {
    const keys = Object.keys(localStorage).filter(key => key.startsWith('video_progress_'));
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
    
    console.log('Recovered video progress data:', recoveredData);
    return recoveredData;
};
