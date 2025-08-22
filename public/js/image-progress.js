/**
 * Image Progress Tracker
 * Tracks when users view images and marks them as completed
 */
class ImageProgressTracker {
    constructor(options = {}) {
        this.options = {
            courseId: null,
            moduleId: null,
            contentId: null,
            imagePackageId: null,
            clientId: null,
            ...options
        };
        
        this.isInitialized = false;
        this.hasTracked = false;
        
        this.init();
    }

    init() {
        if (!this.options.courseId || !this.options.contentId || !this.options.clientId) {
            console.error('ImageProgressTracker: Missing required options');
            return;
        }

        this.isInitialized = true;
        this.trackImageView();
        
        console.log('ImageProgressTracker initialized for content:', this.options.contentId);
    }

    /**
     * Track image view and mark as completed
     */
    trackImageView() {
        if (this.hasTracked) {
            return; // Already tracked this view
        }

        this.hasTracked = true;
        
        // Mark image as viewed immediately
        this.markAsViewed();
        
        // Also track when user closes the tab
        this.setupCloseTracking();
    }

    /**
     * Mark image as viewed in the database
     */
    markAsViewed() {
        const data = {
            course_id: this.options.courseId,
            content_id: this.options.contentId,
            image_package_id: this.options.imagePackageId,
            client_id: this.options.clientId,
            image_status: 'viewed',
            is_completed: 1,
            view_count: 1,
            viewed_at: new Date().toISOString()
        };

        // Send to backend
        fetch('/Unlockyourskills/image-progress/mark-as-viewed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('✅ Image marked as viewed successfully');
                // Set flag in localStorage to indicate image was viewed
                localStorage.setItem('image_viewed_' + this.options.contentId, Date.now().toString());
            } else {
                console.error('❌ Failed to mark image as viewed:', result.message);
            }
        })
        .catch(error => {
            console.error('❌ Error marking image as viewed:', error);
        });
    }

    /**
     * Setup tracking for when user closes the tab
     */
    setupCloseTracking() {
        // Track page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                this.markAsViewed();
            }
        });

        // Track beforeunload (tab close)
        window.addEventListener('beforeunload', () => {
            this.markAsViewed();
        });

        // Track pagehide (more reliable for tab close)
        window.addEventListener('pagehide', () => {
            this.markAsViewed();
        });
    }

    /**
     * Get current progress data
     */
    getProgressData() {
        return {
            courseId: this.options.courseId,
            moduleId: this.options.moduleId,
            contentId: this.options.contentId,
            imagePackageId: this.options.imagePackageId,
            clientId: this.options.clientId,
            isInitialized: this.isInitialized,
            hasTracked: this.hasTracked
        };
    }

    /**
     * Destroy the tracker
     */
    destroy() {
        this.isInitialized = false;
        this.hasTracked = false;
    }
}

// Make ImageProgressTracker available globally
window.ImageProgressTracker = ImageProgressTracker;

// Global function to create image progress tracker
window.createImageProgressTracker = function(options) {
    return new ImageProgressTracker(options);
};

// Auto-initialize if options are available
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're in an image viewer
    const urlParams = new URLSearchParams(window.location.search);
    const contentType = urlParams.get('type');
    
            if (contentType === 'image') {
            const courseId = urlParams.get('course_id');
            const moduleId = urlParams.get('module_id');
            const contentId = urlParams.get('content_id');
            const imagePackageId = urlParams.get('image_package_id');
            const clientId = urlParams.get('client_id');
            
            if (courseId && contentId && imagePackageId && clientId) {
                console.log('🖼️ Image content detected, initializing progress tracker');
                console.log('📋 Image parameters:', { courseId, moduleId, contentId, imagePackageId, clientId });
                
                // Wait for the tracker class to be available
                const waitForTracker = () => {
                    if (window.ImageProgressTracker) {
                        window.imageProgressTracker = new window.ImageProgressTracker({
                            courseId: parseInt(courseId),
                            moduleId: parseInt(moduleId),
                            contentId: parseInt(contentId),
                            imagePackageId: parseInt(imagePackageId),
                            clientId: parseInt(clientId)
                        });
                        console.log('🚀 Image progress tracking started');
                    } else {
                        console.log('⏳ Image progress tracker not ready yet, retrying in 500ms');
                        setTimeout(waitForTracker, 500);
                    }
                };
                
                waitForTracker();
            } else {
                console.error('❌ Missing required parameters for image progress tracking:', { courseId, contentId, imagePackageId, clientId });
            }
        }
});
