/**
 * Prerequisite Progress Tracking JavaScript
 * 
 * Handles step-by-step prerequisite progress tracking
 */

class PrerequisiteProgressTracker {
    constructor() {
        this.baseUrl = '/Unlockyourskills/api/prerequisite-progress';
        this.init();
    }

    init() {
        // Add event listeners for prerequisite content
        this.addPrerequisiteEventListeners();
    }

    addPrerequisiteEventListeners() {
        // Listen for clicks on prerequisite content buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.prerequisite-content-btn, .launch-content-btn')) {
                const prerequisiteId = e.target.dataset.prerequisiteId || e.target.dataset.contentId;
                const prerequisiteType = e.target.dataset.type;
                const courseId = e.target.dataset.courseId;
                
                if (prerequisiteId && prerequisiteType && courseId) {
                    this.startTracking(prerequisiteId, prerequisiteType, courseId);
                }
            }
        });

        // Listen for prerequisite content completion events
        document.addEventListener('prerequisiteCompleted', (e) => {
            const { prerequisiteId, prerequisiteType, courseId } = e.detail;
            this.markComplete(prerequisiteId, prerequisiteType, courseId);
        });
    }

    /**
     * Start tracking prerequisite when user opens it
     */
    async startTracking(prerequisiteId, prerequisiteType, courseId) {
        try {
            const formData = new FormData();
            formData.append('prerequisite_id', prerequisiteId);
            formData.append('prerequisite_type', prerequisiteType);
            formData.append('course_id', courseId);

            const response = await fetch(`${this.baseUrl}/start`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                console.log('Prerequisite tracking started:', result.data);
                // Dispatch event for other components to listen
                document.dispatchEvent(new CustomEvent('prerequisiteStarted', {
                    detail: { prerequisiteId, prerequisiteType, courseId, data: result.data }
                }));
            } else {
                console.error('Failed to start prerequisite tracking:', result.error);
            }
        } catch (error) {
            console.error('Error starting prerequisite tracking:', error);
        }
    }

    /**
     * Mark prerequisite as completed
     */
    async markComplete(prerequisiteId, prerequisiteType, courseId) {
        try {
            const formData = new FormData();
            formData.append('prerequisite_id', prerequisiteId);
            formData.append('prerequisite_type', prerequisiteType);
            formData.append('course_id', courseId);

            const response = await fetch(`${this.baseUrl}/complete`, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                console.log('Prerequisite marked as completed:', result.data);
                // Dispatch event for other components to listen
                document.dispatchEvent(new CustomEvent('prerequisiteCompleted', {
                    detail: { prerequisiteId, prerequisiteType, courseId, data: result.data }
                }));
                
                // Refresh the course details page to update progress
                this.refreshCourseDetails();
            } else {
                console.error('Failed to mark prerequisite complete:', result.error);
            }
        } catch (error) {
            console.error('Error marking prerequisite complete:', error);
        }
    }

    /**
     * Get prerequisite progress
     */
    async getProgress(prerequisiteId, prerequisiteType, courseId) {
        try {
            const params = new URLSearchParams({
                prerequisite_id: prerequisiteId,
                prerequisite_type: prerequisiteType,
                course_id: courseId
            });

            const response = await fetch(`${this.baseUrl}/get?${params}`);
            const result = await response.json();
            
            if (result.success) {
                return result.data;
            } else {
                console.error('Failed to get prerequisite progress:', result.error);
                return null;
            }
        } catch (error) {
            console.error('Error getting prerequisite progress:', error);
            return null;
        }
    }

    /**
     * Refresh course details page
     */
    refreshCourseDetails() {
        // Check if we're on a course details page
        if (window.location.pathname.includes('/my-courses/details/')) {
            // Reload the page to show updated progress
            window.location.reload();
        }
    }
}

// Initialize the prerequisite progress tracker when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.prerequisiteProgressTracker = new PrerequisiteProgressTracker();
});

// Export for use in other scripts
window.PrerequisiteProgressTracker = PrerequisiteProgressTracker;
