/**
 * Session Timeout Management
 * Handles client-side session timeout warnings and activity tracking
 */

// Helper function to generate project URLs (if not already defined)
function getProjectUrl(path) {
    // Get the base URL from the current location
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '');
    return baseUrl + '/' + path.replace(/^\//, '');
}

class SessionTimeoutManager {
    constructor() {
        this.timeoutMinutes = 60; // 1 hour (should match server-side)
        this.warningMinutes = 5; // Show warning 5 minutes before timeout
        this.checkInterval = 60000; // Check every minute
        this.warningShown = false;
        this.activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        this.init();
    }
    
    init() {
        // Start monitoring
        this.startMonitoring();
        
        // Set up activity tracking
        this.setupActivityTracking();
        
        // Set up periodic checks
        this.setupPeriodicChecks();
    }
    
    /**
     * Start monitoring for session timeout
     */
    startMonitoring() {
        // Check if user is logged in (has session)
        if (!this.isLoggedIn()) {
            return;
        }
        
        // Send initial activity ping
        this.sendActivityPing();
    }
    
    /**
     * Set up activity tracking
     */
    setupActivityTracking() {
        this.activityEvents.forEach(eventType => {
            document.addEventListener(eventType, () => {
                this.handleUserActivity();
            }, { passive: true });
        });
        
        // Also track when user becomes active after being away
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.handleUserActivity();
            }
        });
    }
    
    /**
     * Set up periodic checks for timeout
     */
    setupPeriodicChecks() {
        setInterval(() => {
            this.checkSessionTimeout();
        }, this.checkInterval);
    }
    
    /**
     * Handle user activity
     */
    handleUserActivity() {
        // Send activity ping to server
        this.sendActivityPing();
        
        // Reset warning flag if user is active
        if (this.warningShown) {
            this.warningShown = false;
            this.hideTimeoutWarning();
        }
    }
    
    /**
     * Send activity ping to server
     */
    sendActivityPing() {
        // Update last activity timestamp in session
        fetch(getProjectUrl('api/session/activity'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'ping',
                timestamp: Date.now()
            })
        }).catch(error => {
            // Silently fail if server is not available
            console.log('Activity ping failed:', error);
        });
    }
    
    /**
     * Check if session is about to timeout
     */
    checkSessionTimeout() {
        if (!this.isLoggedIn()) {
            return;
        }
        
        // Get session start time from meta tag or localStorage
        const sessionStart = this.getSessionStartTime();
        if (!sessionStart) {
            return;
        }
        
        const now = Date.now();
        const elapsedMinutes = (now - sessionStart) / (1000 * 60);
        const remainingMinutes = this.timeoutMinutes - elapsedMinutes;
        
        // Show warning if within warning period and not already shown
        if (remainingMinutes <= this.warningMinutes && remainingMinutes > 0 && !this.warningShown) {
            this.showTimeoutWarning(remainingMinutes);
        }
        
        // Auto logout if timeout reached
        if (remainingMinutes <= 0) {
            this.handleSessionTimeout();
        }
    }
    
    /**
     * Show timeout warning
     */
    showTimeoutWarning(remainingMinutes) {
        this.warningShown = true;
        
        // Create warning modal
        const warningModal = document.createElement('div');
        warningModal.id = 'sessionTimeoutWarning';
        warningModal.innerHTML = `
            <div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(3px);">
                <div class="modal-content" style="background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%); padding: 40px; border-radius: 16px; max-width: 450px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 1px solid rgba(255,193,7,0.2); animation: modalSlideIn 0.4s ease-out;">
                    <div class="warning-icon" style="width: 80px; height: 80px; background: linear-gradient(135deg, #ffc107, #ff8f00); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; box-shadow: 0 4px 15px rgba(255,193,7,0.4);">
                        <i class="fas fa-clock" style="font-size: 32px; color: white;"></i>
                    </div>
                    <h3 style="color: #333; margin-bottom: 15px; font-size: 24px; font-weight: 600;">Session Timeout Warning</h3>
                    <p style="color: #666; margin-bottom: 30px; line-height: 1.6; font-size: 16px;">
                        Your session will expire in <strong style="color: #ff8f00;">${Math.ceil(remainingMinutes)} minutes</strong> due to inactivity.
                    </p>
                    <div class="modal-buttons" style="display: flex; gap: 15px; justify-content: center;">
                        <button id="extendSession" class="btn btn-primary" style="padding: 12px 24px; background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0,123,255,0.3);">
                            <i class="fas fa-clock" style="margin-right: 8px;"></i>
                            Extend Session
                        </button>
                        <button id="logoutNow" class="btn btn-secondary" style="padding: 12px 24px; background: linear-gradient(135deg, #6c757d, #545b62); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(108,117,125,0.3);">
                            <i class="fas fa-sign-out-alt" style="margin-right: 8px;"></i>
                            Logout Now
                        </button>
                    </div>
                    <style>
                        @keyframes modalSlideIn {
                            from {
                                opacity: 0;
                                transform: translateY(-30px) scale(0.9);
                            }
                            to {
                                opacity: 1;
                                transform: translateY(0) scale(1);
                            }
                        }
                        #extendSession:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(0,123,255,0.4);
                        }
                        #logoutNow:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 4px 12px rgba(108,117,125,0.4);
                        }
                    </style>
                </div>
            </div>
        `;
        
        document.body.appendChild(warningModal);
        
        // Add event listeners
        document.getElementById('extendSession').addEventListener('click', () => {
            this.extendSession();
        });
        
        document.getElementById('logoutNow').addEventListener('click', () => {
            this.logoutNow();
        });
    }
    
    /**
     * Hide timeout warning
     */
    hideTimeoutWarning() {
        const warningModal = document.getElementById('sessionTimeoutWarning');
        if (warningModal) {
            warningModal.remove();
        }
    }
    
    /**
     * Extend session
     */
    extendSession() {
        this.hideTimeoutWarning();
        this.warningShown = false;
        this.handleUserActivity();
        
        // Show success message
        this.showMessage('Session extended successfully!', 'success');
    }
    
    /**
     * Logout now
     */
    logoutNow() {
        window.location.href = getProjectUrl('logout');
    }
    
    /**
     * Handle session timeout
     */
    handleSessionTimeout() {
        // Redirect to login with timeout message
        window.location.href = getProjectUrl('login?timeout=1');
    }
    
    /**
     * Check if user is logged in
     */
    isLoggedIn() {
        // Check if we're on a protected page (not login/logout)
        const currentPath = window.location.pathname;
        return !currentPath.includes('/login') && !currentPath.includes('/logout');
    }
    
    /**
     * Get session start time
     */
    getSessionStartTime() {
        // Try to get from meta tag first
        const metaTag = document.querySelector('meta[name="session-start"]');
        if (metaTag) {
            return parseInt(metaTag.getAttribute('content'));
        }
        
        // Fallback to localStorage
        const stored = localStorage.getItem('sessionStartTime');
        if (stored) {
            return parseInt(stored);
        }
        
        // Set current time as fallback
        const now = Date.now();
        localStorage.setItem('sessionStartTime', now.toString());
        return now;
    }
    
    /**
     * Show message
     */
    showMessage(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            animation: slideIn 0.3s ease-out;
        `;
        
        // Set background color based on type
        switch (type) {
            case 'success':
                toast.style.backgroundColor = '#28a745';
                break;
            case 'warning':
                toast.style.backgroundColor = '#ffc107';
                toast.style.color = '#333';
                break;
            case 'error':
                toast.style.backgroundColor = '#dc3545';
                break;
            default:
                toast.style.backgroundColor = '#17a2b8';
        }
        
        toast.textContent = message;
        document.body.appendChild(toast);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// Initialize session timeout manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SessionTimeoutManager();
});

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style); 