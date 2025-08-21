/**
 * Audio Progress Tracking System
 * Handles tracking of audio playback progress and updates the database
 */

class AudioProgressTracker {
    constructor(audioElement, options = {}) {
        this.audio = audioElement;
        this.options = {
            courseId: options.courseId || null,
            contentId: options.contentId || null,
            audioPackageId: options.audioPackageId || null,
            updateInterval: options.updateInterval || 5000, // Update every 5 seconds
            completionThreshold: options.completionThreshold || 80, // 80% to mark as complete
            ...options
        };

        this.progressData = {
            currentTime: 0,
            duration: 0,
            listenedPercentage: 0,
            isCompleted: false,
            audioStatus: 'not_started',
            playbackStatus: 'not_started',
            playCount: 0,
            playbackSpeed: 1.0,
            notes: ''
        };

        this.updateTimer = null;
        this.isTracking = false;
        this.lastUpdateTime = 0;
        
        // Hybrid approach properties
        this.immediateSaveQueue = [];
        this.debouncedUpdateTimer = null;
        this.lastImmediateSave = 0;
        this.localStorageKey = `audio_progress_${this.options.contentId}`;
        this.isClosing = false;

        this.init();
    }

    init() {
        if (!this.audio || !this.options.courseId || !this.options.contentId || !this.options.audioPackageId) {
            console.warn('AudioProgressTracker: Missing required parameters');
            return;
        }

        this.setupEventListeners();
        this.startTracking();
    }

    setupEventListeners() {
        console.log('AudioProgressTracker: Setting up event listeners');
        console.log('AudioProgressTracker: Audio element:', this.audio);
        console.log('AudioProgressTracker: Audio readyState:', this.audio.readyState);
        console.log('AudioProgressTracker: Audio duration:', this.audio.duration);
        
        // Track play events
        this.audio.addEventListener('play', () => {
            this.onPlay();
        });

        // Track pause events
        this.audio.addEventListener('pause', () => {
            this.onPause();
        });

        // Track ended events
        this.audio.addEventListener('ended', () => {
            this.onEnded();
        });

        // Track stop events (when audio is stopped)
        this.audio.addEventListener('abort', () => {
            this.onStop();
        });

        // Track time updates
        this.audio.addEventListener('timeupdate', () => {
            this.onTimeUpdate();
        });

        // Track playback rate changes
        this.audio.addEventListener('ratechange', () => {
            this.onPlaybackRateChange();
        });

        // Track seek events
        this.audio.addEventListener('seeking', () => {
            this.onSeeking();
        });

        // Track when metadata is loaded
        this.audio.addEventListener('loadedmetadata', () => {
            console.log('AudioProgressTracker: loadedmetadata event fired');
            this.onMetadataLoaded();
        });

        // Track errors
        this.audio.addEventListener('error', (e) => {
            this.onError(e);
        });
        
        // Tab close protection using beacon API
        this.setupTabCloseProtection();
        
        console.log('AudioProgressTracker: Event listeners set up complete');
    }

    startTracking() {
        this.isTracking = true;
        this.startUpdateTimer();
        console.log('AudioProgressTracker: Started tracking');
        
        // Check if metadata is already loaded (fallback for cases where loadedmetadata event doesn't fire)
        if (this.audio.readyState >= 1) {
            console.log('AudioProgressTracker: Audio already has metadata, calling onMetadataLoaded directly');
            this.onMetadataLoaded();
        } else {
            console.log('AudioProgressTracker: Audio readyState:', this.audio.readyState, '- waiting for metadata');
            // Force check after a short delay in case metadata loads asynchronously
            setTimeout(() => {
                if (this.audio.readyState >= 1) {
                    console.log('AudioProgressTracker: Metadata loaded after delay, calling onMetadataLoaded');
                    this.onMetadataLoaded();
                } else {
                    console.log('AudioProgressTracker: Metadata still not loaded after delay, readyState:', this.audio.readyState);
                }
            }, 1000);
        }
    }

    stopTracking() {
        this.isTracking = false;
        this.stopUpdateTimer();
        console.log('AudioProgressTracker: Stopped tracking');
    }

    startUpdateTimer() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }

        this.updateTimer = setInterval(() => {
            this.updateProgress();
        }, this.options.updateInterval);
    }

    stopUpdateTimer() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }

    onPlay() {
        this.progressData.playCount++;
        this.progressData.playbackStatus = 'playing';
        this.progressData.audioStatus = 'in_progress';
        
        // Immediate save for critical action
        this.immediateSave('play');
        
        // Also update progress normally
        this.updateProgress();
        console.log('AudioProgressTracker: Play started, play count:', this.progressData.playCount);
    }

    onPause() {
        this.progressData.playbackStatus = 'paused';
        this.progressData.audioStatus = 'in_progress';
        
        // Immediate save for critical action
        this.immediateSave('pause');
        
        // Also update progress normally
        this.updateProgress();
        console.log('AudioProgressTracker: Audio paused');
        
        // Set audio close flag when paused (user might close tab)
        this.setAudioCloseFlag();
    }

    onEnded() {
        this.progressData.currentTime = this.audio.duration;
        this.progressData.listenedPercentage = 100;
        this.progressData.isCompleted = true;
        this.progressData.playbackStatus = 'stopped';
        this.progressData.audioStatus = 'completed';
        
        // Immediate save for critical action
        this.immediateSave('ended');
        
        // Also update progress normally
        this.updateProgress();
        this.onCompleted();
        console.log('AudioProgressTracker: Audio ended');
        
        // Set audio close flag when audio ends
        this.setAudioCloseFlag();
    }

    onStop() {
        this.progressData.playbackStatus = 'stopped';
        this.progressData.audioStatus = 'in_progress';
        
        // Immediate save for critical action
        this.immediateSave('stop');
        
        // Also update progress normally
        this.updateProgress();
        console.log('AudioProgressTracker: Audio stopped');
        
        // Set audio close flag when audio is stopped
        this.setAudioCloseFlag();
    }

    onTimeUpdate() {
        if (this.audio.duration && this.audio.duration > 0) {
            this.progressData.currentTime = this.audio.currentTime;
            this.progressData.duration = this.audio.duration;
            this.progressData.listenedPercentage = this.calculateListenedPercentage();
            
            // Set initial status if not set
            if (!this.progressData.playbackStatus && this.progressData.currentTime > 0) {
                this.progressData.playbackStatus = 'playing';
            }
            if (!this.progressData.audioStatus && this.progressData.currentTime > 0) {
                this.progressData.audioStatus = 'in_progress';
            }
            
            // Check if completed
            if (this.progressData.listenedPercentage >= this.options.completionThreshold && !this.progressData.isCompleted) {
                this.progressData.isCompleted = true;
                // Don't call onCompleted() here as it will be called by onEnded()
                // Just update the status
                this.progressData.audioStatus = 'completed';
            }
        }
    }



    onPlaybackRateChange() {
        this.progressData.playbackSpeed = this.audio.playbackRate;
        console.log('AudioProgressTracker: Playback speed changed to:', this.progressData.playbackSpeed);
    }

    onSeeking() {
        // Update progress immediately when user seeks
        this.updateProgress();
        console.log('AudioProgressTracker: User seeking to:', this.audio.currentTime);
    }

    onMetadataLoaded() {
        console.log('AudioProgressTracker: onMetadataLoaded called');
        if (this.audio.duration && this.audio.duration > 0) {
            this.progressData.duration = this.audio.duration;
            // Set initial status
            this.progressData.playbackStatus = 'not_started';
            this.progressData.audioStatus = 'not_started';
            console.log('AudioProgressTracker: Metadata loaded, duration:', this.progressData.duration);
            
            // Check for lost data in localStorage
            this.recoverLostData();
            
            // Load resume position after metadata is loaded
            console.log('AudioProgressTracker: Calling loadResumePosition...');
            this.loadResumePosition();
        } else {
            console.log('AudioProgressTracker: No duration available yet');
        }
    }

    onError(error) {
        console.error('AudioProgressTracker: Audio error:', error);
        this.stopTracking();
    }

    onCompleted() {
        console.log('AudioProgressTracker: Audio marked as completed');
        this.showCompletionNotification();
    }

    calculateListenedPercentage() {
        if (this.progressData.duration <= 0) return 0;
        return Math.round((this.progressData.currentTime / this.progressData.duration) * 100 * 100) / 100;
    }

    updateProgress() {
        if (!this.isTracking) return;

        const now = Date.now();
        // Only update if enough time has passed or if it's a significant event
        if (now - this.lastUpdateTime < this.options.updateInterval && !this.progressData.isCompleted) {
            return;
        }

        this.lastUpdateTime = now;

        const progressData = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            audio_package_id: this.options.audioPackageId,
            current_time: Math.round(this.progressData.currentTime),
            duration: Math.round(this.progressData.duration),
            playback_speed: this.progressData.playbackSpeed,
            notes: this.progressData.notes,
            audio_status: this.progressData.audioStatus || 'not_started',
            playback_status: this.progressData.playbackStatus || 'not_started'
        };

        this.sendProgressUpdate(progressData);
    }

    async sendProgressUpdate(progressData) {
        try {
            const response = await fetch('/Unlockyourskills/audio-progress/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin', // Include cookies for authentication
                body: new URLSearchParams(progressData)
            });

            const result = await response.json();

            if (result.success) {
                this.updateProgressDisplay(result.progress);
                // Store the progress ID for status updates
                if (result.progress && result.progress.id) {
                    this.progressData.id = result.progress.id;
                }
                console.log('AudioProgressTracker: Progress updated successfully');
            } else {
                console.error('AudioProgressTracker: Failed to update progress:', result.message);
            }
        } catch (error) {
            console.error('AudioProgressTracker: Error updating progress:', error);
        }
    }

    updateProgressDisplay(progress) {
        // Update progress bar if it exists
        const progressBar = document.querySelector('.audio-progress-bar');
        if (progressBar) {
            progressBar.style.width = progress.listened_percentage + '%';
            progressBar.setAttribute('aria-valuenow', progress.listened_percentage);
        }

        // Update progress text if it exists
        const progressText = document.querySelector('.audio-progress-text');
        if (progressText) {
            progressText.textContent = `${progress.listened_percentage}% Complete`;
        }

        // Update completion status if it exists
        if (progress.is_completed) {
            const completionBadge = document.querySelector('.audio-completion-badge');
            if (completionBadge) {
                completionBadge.style.display = 'block';
                completionBadge.textContent = 'Completed';
            }
        }
    }

    showCompletionNotification() {
        // Create a completion notification
        const notification = document.createElement('div');
        notification.className = 'audio-completion-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-check-circle"></i>
                <span>Audio content completed!</span>
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

    // Public methods for external control
    setNotes(notes) {
        this.progressData.notes = notes;
    }

    getProgress() {
        return { ...this.progressData };
    }

    async updateAudioStatus(status) {
           if (!this.progressData.id) {
               console.warn('AudioProgressTracker: No progress ID available for status update');
               return;
           }

           try {
               const response = await fetch('/Unlockyourskills/audio-progress/status', {
                   method: 'POST',
                   headers: {
                       'Content-Type': 'application/x-www-form-urlencoded',
                       'X-Requested-With': 'XMLHttpRequest'
                   },
                   credentials: 'same-origin',
                   body: new URLSearchParams({
                       progress_id: this.progressData.id,
                       status: status
                   })
               });

               const result = await response.json();
               if (result.success) {
                   console.log('AudioProgressTracker: Playback status updated to:', status);
               } else {
                   console.error('AudioProgressTracker: Failed to update audio status:', result.message);
               }
           } catch (error) {
               console.error('AudioProgressTracker: Error updating audio status:', error);
           }
       }

        async updatePlaybackStatus(status) {
        if (!this.progressData.id) {
            console.warn('AudioProgressTracker: No progress ID available for playback status update');
            return;
        }

        try {
            const response = await fetch('/Unlockyourskills/audio-progress/playback-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: new URLSearchParams({
                    progress_id: this.progressData.id,
                    playback_status: status
                })
            });

            const result = await response.json();
            if (result.success) {
                console.log('AudioProgressTracker: Playback status updated to:', status);
            } else {
                console.error('AudioProgressTracker: Failed to update playback status:', result.message);
            }
        } catch (error) {
            console.error('AudioProgressTracker: Error updating playback status:', error);
        }
    }

    /**
     * Load resume position from the server
     */
    async loadResumePosition() {
        console.log('AudioProgressTracker: loadResumePosition called');
        console.log('AudioProgressTracker: courseId:', this.options.courseId, 'contentId:', this.options.contentId);
        
        try {
            const url = `/Unlockyourskills/audio-progress/resume-position?course_id=${this.options.courseId}&content_id=${this.options.contentId}`;
            console.log('AudioProgressTracker: Fetching from URL:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            console.log('AudioProgressTracker: Response status:', response.status);
            const result = await response.json();
            console.log('AudioProgressTracker: Response data:', result);
            
            if (result.success && result.resume_position > 0) {
                // Set the audio to resume position
                this.audio.currentTime = result.resume_position;
                this.progressData.currentTime = result.resume_position;
                this.progressData.listenedPercentage = result.listened_percentage;
                this.progressData.playCount = result.play_count;
                
                // Update status based on progress
                if (result.listened_percentage >= 100) {
                    this.progressData.audioStatus = 'completed';
                    this.progressData.playbackStatus = 'stopped';
                } else if (result.listened_percentage > 0) {
                    this.progressData.audioStatus = 'in_progress';
                    this.progressData.playbackStatus = 'paused';
                }
                
                console.log(`AudioProgressTracker: Resumed from position ${result.resume_position}s (${result.listened_percentage}% complete)`);
                
                // Show resume notification
                this.showResumeNotification(result.resume_position, result.listened_percentage);
            } else {
                console.log('AudioProgressTracker: No resume position found, starting from beginning');
            }
        } catch (error) {
            console.error('AudioProgressTracker: Error loading resume position:', error);
        }
    }

    /**
     * Show resume notification to user
     */
    showResumeNotification(resumePosition, progressPercentage) {
        const minutes = Math.floor(resumePosition / 60);
        const seconds = Math.floor(resumePosition % 60);
        const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Create resume notification
        const notification = document.createElement('div');
        notification.className = 'audio-resume-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-play-circle"></i>
                <span>Resumed from ${timeString} (${progressPercentage}% complete)</span>
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

    /**
     * Manual trigger for resume functionality (for testing)
     */
    manualResume() {
        console.log('AudioProgressTracker: Manual resume triggered');
        this.loadResumePosition();
    }

    /**
     * Set audio close flag in localStorage
     */
    setAudioCloseFlag() {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const courseId = urlParams.get('course_id');
            const moduleId = urlParams.get('module_id');
            const contentId = urlParams.get('content_id');
            
            if (courseId && moduleId && contentId) {
                // Check if flag is already set to prevent duplicates
                const existingFlag = localStorage.getItem('audio_closed_' + contentId);
                if (!existingFlag) {
                    localStorage.setItem('audio_closed_' + contentId, Date.now().toString());
                    console.log('AudioProgressTracker: Audio close flag set for:', contentId);
                } else {
                    console.log('AudioProgressTracker: Audio close flag already exists for:', contentId);
                }
            }
        } catch (error) {
            console.error('AudioProgressTracker: Error setting audio close flag:', error);
        }
    }

    // ===================================
    // HYBRID APPROACH METHODS
    // ===================================
    
    /**
     * Immediate save for critical actions (play, pause, stop)
     */
    async immediateSave(action = 'unknown') {
        try {
            const data = {
                course_id: this.options.courseId,
                content_id: this.options.contentId,
                audio_package_id: this.options.audioPackageId,
                current_time: this.progressData.currentTime,
                duration: this.progressData.duration,
                listened_percentage: this.progressData.listenedPercentage,
                playback_status: this.progressData.playbackStatus,
                audio_status: this.progressData.audioStatus,
                action: action
            };
            
            console.log(`AudioProgressTracker: Immediate save for action: ${action}`, data);
            
            // Save to localStorage immediately as backup
            this.saveToLocalStorage(data);
            
            // Send to server immediately
            const response = await fetch('/Unlockyourskills/audio-progress/immediate-save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(data)
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    console.log(`AudioProgressTracker: Immediate save successful for ${action}`);
                    this.lastImmediateSave = Date.now();
                    return true;
                }
            }
            
            // If immediate save fails, queue for retry
            this.queueForRetry(data, 'immediate');
            return false;
            
        } catch (error) {
            console.error(`AudioProgressTracker: Immediate save error for ${action}:`, error);
            this.queueForRetry(data, 'immediate');
            return false;
        }
    }
    
    /**
     * Debounced save for progress updates
     */
    debouncedSave() {
        // Clear existing timer
        if (this.debouncedUpdateTimer) {
            clearTimeout(this.debouncedUpdateTimer);
        }
        
        // Set new timer for 3 seconds
        this.debouncedUpdateTimer = setTimeout(() => {
            this.saveProgress();
        }, 3000);
    }
    
    /**
     * Beacon save for reliable data transmission on tab close
     */
    beaconSave() {
        try {
            const data = {
                course_id: this.options.courseId,
                content_id: this.options.contentId,
                audio_package_id: this.options.audioPackageId,
                current_time: this.progressData.currentTime,
                duration: this.progressData.duration,
                listened_percentage: this.progressData.listenedPercentage,
                playback_status: this.progressData.playbackStatus,
                audio_status: this.progressData.audioStatus
            };
            
            console.log('AudioProgressTracker: Beacon save initiated', data);
            
            // Use sendBeacon if available
            if (navigator.sendBeacon) {
                const formData = new FormData();
                Object.keys(data).forEach(key => {
                    formData.append(key, data[key]);
                });
                
                const success = navigator.sendBeacon('/Unlockyourskills/audio-progress/beacon-save', formData);
                if (success) {
                    console.log('AudioProgressTracker: Beacon save successful');
                    this.clearLocalStorage();
                    return true;
                }
            }
            
            // Fallback to regular fetch if sendBeacon fails
            this.fallbackSave(data);
            return false;
            
        } catch (error) {
            console.error('AudioProgressTracker: Beacon save error:', error);
            return false;
        }
    }
    
    /**
     * Fallback save when beacon fails
     */
    async fallbackSave(data) {
        try {
            const response = await fetch('/Unlockyourskills/audio-progress/beacon-save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(data)
            });
            
            if (response.ok) {
                console.log('AudioProgressTracker: Fallback save successful');
                this.clearLocalStorage();
                return true;
            }
        } catch (error) {
            console.error('AudioProgressTracker: Fallback save error:', error);
        }
        return false;
    }
    
    /**
     * Save to localStorage as backup
     */
    saveToLocalStorage(data) {
        try {
            const backupData = {
                ...data,
                timestamp: Date.now(),
                type: 'audio_progress'
            };
            localStorage.setItem(this.localStorageKey, JSON.stringify(backupData));
            console.log('AudioProgressTracker: Progress saved to localStorage');
        } catch (error) {
            console.error('AudioProgressTracker: localStorage save error:', error);
        }
    }
    
    /**
     * Clear localStorage after successful save
     */
    clearLocalStorage() {
        try {
            localStorage.removeItem(this.localStorageKey);
            console.log('AudioProgressTracker: localStorage cleared');
        } catch (error) {
            console.error('AudioProgressTracker: localStorage clear error:', error);
        }
    }
    
    /**
     * Queue data for retry
     */
    queueForRetry(data, type) {
        const retryItem = {
            data: data,
            type: type,
            timestamp: Date.now(),
            attempts: 0
        };
        
        this.immediateSaveQueue.push(retryItem);
        console.log(`AudioProgressTracker: Queued ${type} save for retry`);
        
        // Try to retry after 5 seconds
        setTimeout(() => {
            this.processRetryQueue();
        }, 5000);
    }
    
    /**
     * Process retry queue
     */
    async processRetryQueue() {
        if (this.immediateSaveQueue.length === 0) return;
        
        console.log(`AudioProgressTracker: Processing retry queue (${this.immediateSaveQueue.length} items)`);
        
        const itemsToRetry = [...this.immediateSaveQueue];
        this.immediateSaveQueue = [];
        
        for (const item of itemsToRetry) {
            if (item.attempts < 3) {
                item.attempts++;
                
                try {
                    let success = false;
                    if (item.type === 'immediate') {
                        success = await this.immediateSave(item.data.action);
                    }
                    
                    if (success) {
                        console.log(`AudioProgressTracker: Retry successful for ${item.type}`);
                    } else {
                        // Re-queue if still failing
                        this.immediateSaveQueue.push(item);
                    }
                } catch (error) {
                    console.error(`AudioProgressTracker: Retry error for ${item.type}:`, error);
                    this.immediateSaveQueue.push(item);
                }
            } else {
                console.warn(`AudioProgressTracker: Max retries reached for ${item.type}`);
            }
        }
    }
    
    /**
     * Enhanced save progress with hybrid approach
     */
    async saveProgress() {
        // Use debounced save for regular progress updates
        this.debouncedSave();
        
        // Also save to localStorage as backup
        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            audio_package_id: this.options.audioPackageId,
            current_time: this.progressData.currentTime,
            duration: this.progressData.duration,
            listened_percentage: this.progressData.listenedPercentage,
            playback_status: this.progressData.playbackStatus,
            audio_status: this.progressData.audioStatus
        };
        
        this.saveToLocalStorage(data);
    }
    
    /**
     * Setup tab close protection using beacon API
     */
    setupTabCloseProtection() {
        // Use beacon API for reliable data transmission on tab close
        window.addEventListener('beforeunload', (event) => {
            if (this.isTracking && !this.isClosing) {
                console.log('AudioProgressTracker: Tab closing detected, using beacon save');
                this.isClosing = true;
                this.beaconSave();
            }
        });
        
        // Also handle pagehide event (more reliable than beforeunload)
        window.addEventListener('pagehide', (event) => {
            if (this.isTracking && !this.isClosing) {
                console.log('AudioProgressTracker: Page hiding detected, using beacon save');
                this.isClosing = true;
                this.beaconSave();
            }
        });
        
        // Handle visibility change (when tab becomes hidden)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && this.isTracking && !this.isClosing) {
                console.log('AudioProgressTracker: Tab hidden detected, using beacon save');
                this.isClosing = true;
                this.beaconSave();
            }
        });
        
        // Handle unload event as final fallback
        window.addEventListener('unload', (event) => {
            if (this.isTracking && !this.isClosing) {
                console.log('AudioProgressTracker: Window unloading detected, using beacon save');
                this.isClosing = true;
                this.beaconSave();
            }
        });
        
        console.log('AudioProgressTracker: Tab close protection setup complete');
    }
    
    /**
     * Recover lost data from localStorage
     */
    async recoverLostData() {
        try {
            const lostData = localStorage.getItem(this.localStorageKey);
            if (lostData) {
                const parsedData = JSON.parse(lostData);
                
                // Check if this is recent data (within last 24 hours)
                const dataAge = Date.now() - parsedData.timestamp;
                const maxAge = 24 * 60 * 60 * 1000; // 24 hours
                
                if (dataAge < maxAge && parsedData.type === 'audio_progress') {
                    console.log('AudioProgressTracker: Found lost data in localStorage:', parsedData);
                    
                    // Try to save this data to the server
                    const success = await this.fallbackSave(parsedData);
                    if (success) {
                        console.log('AudioProgressTracker: Lost data recovered and saved successfully');
                        this.clearLocalStorage();
                    } else {
                        console.warn('AudioProgressTracker: Failed to recover lost data');
                    }
                } else {
                    // Data is too old, clear it
                    console.log('AudioProgressTracker: Clearing old localStorage data');
                    this.clearLocalStorage();
                }
            }
        } catch (error) {
            console.error('AudioProgressTracker: Error recovering lost data:', error);
            // Clear corrupted data
            this.clearLocalStorage();
        }
    }
    
    destroy() {
        this.stopTracking();
        this.audio = null;
        console.log('AudioProgressTracker: Destroyed');
    }
}

// Initialize audio progress tracking when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on an audio content page
    const audioElement = document.querySelector('audio');
    if (audioElement) {
        // Get progress tracking parameters from the page
        const urlParams = new URLSearchParams(window.location.search);
        const courseId = urlParams.get('course_id');
        const moduleId = urlParams.get('module_id');
        const contentId = urlParams.get('content_id');
        
        // For audio content, we need to get the actual audio package ID
        // We'll need to make an AJAX call to get this information or pass it as a parameter
        if (courseId && contentId) {
            // First, try to get audio package ID from the server
            fetch(`/Unlockyourskills/api/audio-content-info?content_id=${contentId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.audio_package_id) {
                    initializeAudioTracker(audioElement, courseId, contentId, data.audio_package_id);
                } else {
                    // Fallback: use contentId as audioPackageId
                    console.warn('Could not get audio package ID, using content ID as fallback');
                    initializeAudioTracker(audioElement, courseId, contentId, contentId);
                }
            })
            .catch(error => {
                console.error('Error fetching audio content info:', error);
                // Fallback: use contentId as audioPackageId
                initializeAudioTracker(audioElement, courseId, contentId, contentId);
            });
        } else {
            console.warn('AudioProgressTracker: Missing required parameters for progress tracking');
        }
    }
});

// Global function for testing resume functionality
window.testAudioResume = function() {
    if (window.audioProgressTracker) {
        console.log('Testing audio resume functionality...');
        window.audioProgressTracker.manualResume();
    } else {
        console.log('Audio progress tracker not available');
    }
};

// Global function for testing audio close flag
window.testAudioCloseFlag = function() {
    if (window.audioProgressTracker) {
        console.log('Testing audio close flag...');
        window.audioProgressTracker.setAudioCloseFlag();
        console.log('Audio close flag set! Now close this tab and check the parent page.');
    } else {
        console.log('Audio progress tracker not available');
    }
};

// Global function for testing hybrid approach
window.testHybridAudio = function() {
    if (window.audioProgressTracker) {
        console.log('Testing hybrid audio approach...');
        
        // Test immediate save
        window.audioProgressTracker.immediateSave('test');
        
        // Test beacon save
        window.audioProgressTracker.beaconSave();
        
        // Test data recovery
        window.audioProgressTracker.recoverLostData();
        
        console.log('Hybrid audio tests completed!');
    } else {
        console.log('Audio progress tracker not available');
    }
};

// Global function to recover all lost audio data
window.recoverAllLostAudioData = function() {
    console.log('Scanning for lost audio data...');
    
    const lostDataKeys = [];
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith('audio_progress_')) {
            lostDataKeys.push(key);
        }
    }
    
    if (lostDataKeys.length === 0) {
        console.log('No lost audio data found');
        return;
    }
    
    console.log(`Found ${lostDataKeys.length} lost audio data entries:`, lostDataKeys);
    
    // Try to recover each entry
    lostDataKeys.forEach(key => {
        try {
            const data = JSON.parse(localStorage.getItem(key));
            console.log('Recovering data for key:', key, data);
            
            // Send to server
            fetch('/Unlockyourskills/audio-progress/beacon-save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log('Successfully recovered data for:', key);
                    localStorage.removeItem(key);
                } else {
                    console.warn('Failed to recover data for:', key);
                }
            })
            .catch(error => {
                console.error('Error recovering data for:', key, error);
            });
        } catch (error) {
            console.error('Error parsing data for key:', key, error);
            // Remove corrupted data
            localStorage.removeItem(key);
        }
    });
};

function initializeAudioTracker(audioElement, courseId, contentId, audioPackageId) {
    // Initialize progress tracking
    window.audioProgressTracker = new AudioProgressTracker(audioElement, {
        courseId: courseId,
        contentId: contentId,
        audioPackageId: audioPackageId,
        updateInterval: 5000, // Update every 5 seconds
        completionThreshold: 80 // 80% to mark as complete
    });

    console.log('AudioProgressTracker: Initialized for audio content', {
        courseId,
        contentId,
        audioPackageId
    });
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AudioProgressTracker;
}
