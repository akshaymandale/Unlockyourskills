/**
 * Confirmation System Loader
 * Automatically loads appropriate confirmation modules based on page context
 * 
 * This file should be included in the main layout/header to ensure
 * confirmation functionality is available across the entire application
 */

class ConfirmationLoader {
    constructor() {
        this.loadedModules = new Set();
        this.init();
    }

    init() {
        // Load core confirmation system first
        this.loadCoreConfirmations();
        
        // Load page-specific modules based on page context
        this.loadPageSpecificModules();
        
        // Set up dynamic module loading for SPA-like behavior
        this.setupDynamicLoading();
    }

    loadCoreConfirmations() {
        // Core confirmation modal is already loaded via confirmation_modal.js
        // Core handlers are loaded via confirmation_handlers.js
        console.log('✅ Core confirmation system loaded');
    }

    loadPageSpecificModules() {
        const pageContext = this.detectPageContext();

        pageContext.forEach(context => {
            this.loadModuleForContext(context);
        });
    }

    detectPageContext() {
        const contexts = [];
        
        // VLR Module Detection
        if (this.isVLRPage()) {
            contexts.push('vlr');
        }
        
        // Assessment Module Detection
        if (this.isAssessmentPage()) {
            contexts.push('assessment');
        }
        
        // User Management Detection
        if (this.isUserManagementPage()) {
            contexts.push('user');
        }
        
        // Survey/Feedback Detection
        if (this.isSurveyFeedbackPage()) {
            contexts.push('survey');
        }
        
        return contexts;
    }

    isVLRPage() {
        return !!(
            document.querySelector('#vlrTabs') ||
            document.querySelector('.vlr-container') ||
            document.querySelector('[data-vlr-page]') ||
            window.location.href.includes('VLRController')
        );
    }

    isAssessmentPage() {
        return !!(
            document.querySelector('#questionsTableBody') ||
            document.querySelector('.assessment-container') ||
            document.querySelector('[data-assessment-page]') ||
            window.location.href.includes('QuestionController')
        );
    }

    isUserManagementPage() {
        return !!(
            document.querySelector('.user-management') ||
            document.querySelector('.users-table') ||
            document.querySelector('[data-user-page]') ||
            window.location.href.includes('UserManagementController')
        );
    }

    isSurveyFeedbackPage() {
        return !!(
            document.querySelector('.survey-container') ||
            document.querySelector('.feedback-container') ||
            document.querySelector('[data-survey-page]') ||
            document.querySelector('[data-feedback-page]') ||
            window.location.href.includes('SurveyQuestionController') ||
            window.location.href.includes('FeedbackQuestionController')
        );
    }

    loadModuleForContext(context) {
        if (this.loadedModules.has(context)) {
            return; // Already loaded
        }

        const moduleMap = {
            'vlr': getProjectUrl('public/js/modules/vlr_confirmations.js'),
            'assessment': getProjectUrl('public/js/modules/assessment_confirmations.js'),
            'user': getProjectUrl('public/js/modules/user_confirmations.js'),
            'survey': getProjectUrl('public/js/modules/survey_confirmations.js')
        };

        const modulePath = moduleMap[context];
        if (modulePath) {
            this.loadScript(modulePath, context);
        }
    }

    loadScript(src, context) {
        return new Promise((resolve, reject) => {
            // Check if script is already loaded
            if (document.querySelector(`script[src="${src}"]`)) {
                this.loadedModules.add(context);
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            
            script.onload = () => {
                this.loadedModules.add(context);
                console.log(`✅ Loaded confirmation module: ${context}`);
                resolve();
            };
            
            script.onerror = () => {
                console.warn(`⚠️ Failed to load confirmation module: ${context}`);
                reject(new Error(`Failed to load ${src}`));
            };
            
            document.head.appendChild(script);
        });
    }

    setupDynamicLoading() {
        // For SPA-like applications, re-detect context when DOM changes
        if (typeof MutationObserver !== 'undefined') {
            const observer = new MutationObserver((mutations) => {
                let shouldRecheck = false;
                
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        // Check if significant DOM changes occurred
                        for (const node of mutation.addedNodes) {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                if (node.querySelector && (
                                    node.querySelector('#vlrTabs') ||
                                    node.querySelector('#questionsTableBody') ||
                                    node.querySelector('.user-management') ||
                                    node.querySelector('.survey-container')
                                )) {
                                    shouldRecheck = true;
                                    break;
                                }
                            }
                        }
                    }
                });
                
                if (shouldRecheck) {
                    // Debounce the recheck
                    clearTimeout(this.recheckTimeout);
                    this.recheckTimeout = setTimeout(() => {
                        this.loadPageSpecificModules();
                    }, 500);
                }
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    // Public method to manually load a specific module
    static loadModule(context) {
        if (window.confirmationLoaderInstance) {
            window.confirmationLoaderInstance.loadModuleForContext(context);
        }
    }

    // Public method to check if a module is loaded
    static isModuleLoaded(context) {
        return window.confirmationLoaderInstance ? 
            window.confirmationLoaderInstance.loadedModules.has(context) : false;
    }
}

// Initialize the confirmation loader
document.addEventListener('DOMContentLoaded', function() {
    window.confirmationLoaderInstance = new ConfirmationLoader();
});

// Global helper functions
window.loadConfirmationModule = function(context) {
    ConfirmationLoader.loadModule(context);
};

window.isConfirmationModuleLoaded = function(context) {
    return ConfirmationLoader.isModuleLoaded(context);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ConfirmationLoader;
}
