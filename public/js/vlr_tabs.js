/**
 * VLR Tab Management System
 * Handles main tabs and sub-tabs for VLR (Virtual Learning Resources) module
 * 
 * Features:
 * - Main tab management (SCORM, Audio, Video, etc.)
 * - Sub-tab management (Document, External, Interactive modules)
 * - URL parameter handling for direct tab access
 * - Bootstrap tab integration
 */

class VLRTabManager {
    constructor() {
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.initializeMainTabs();
            this.initializeSubTabs();
            this.setupTabEventHandlers();
        });
    }

    initializeMainTabs() {
        // Get the active tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab') || 'scorm';

        // Ensure the correct tab is active and shown
        const tabLink = document.querySelector(`a[href="#${activeTab}"]`);
        const tabPane = document.querySelector(`#${activeTab}`);

        if (tabLink && tabPane) {
            // Remove active classes from all tabs and panes
            document.querySelectorAll('#vlrTabs .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelectorAll('.tab-content > .tab-pane').forEach(pane => {
                pane.classList.remove('show', 'active');
            });

            // Add active classes to the correct tab and pane
            tabLink.classList.add('active');
            tabPane.classList.add('show', 'active');

            // Trigger Bootstrap tab shown event to ensure proper initialization
            const tabTrigger = new bootstrap.Tab(tabLink);
            tabTrigger.show();

            // Initialize sub-tabs after a delay
            setTimeout(() => {
                this.initializeSubTabsForActiveTab(activeTab);
            }, 300);
        }

        // Add event listeners to all tab links to remove tab parameter from URL when manually clicked
        document.querySelectorAll('#vlrTabs .nav-link').forEach(tabLink => {
            tabLink.addEventListener('shown.bs.tab', (e) => {
                // Remove tab parameter from URL when user manually clicks tabs
                const url = new URL(window.location);
                if (url.searchParams.has('tab')) {
                    url.searchParams.delete('tab');
                    window.history.replaceState({}, '', url);
                }
            });
        });
    }

    initializeSubTabs() {
        // Document Sub-tabs
        this.setupSubTabGroup('#documentSubTabs', '#document .tab-content');
        
        // External Content Sub-tabs
        this.setupSubTabGroup('#externalSubTabs', '#external .tab-content');
        
        // Interactive Content Sub-tabs
        this.setupSubTabGroup('#interactiveSubTabs', '#interactive .tab-content');
    }

    setupSubTabGroup(tabsSelector, contentSelector) {
        const tabLinks = document.querySelectorAll(`${tabsSelector} .nav-link`);
        
        tabLinks.forEach(tabLink => {
            tabLink.addEventListener('click', (e) => {
                e.preventDefault();

                // Remove active class from all sub-tabs in this group
                tabLinks.forEach(link => link.classList.remove('active'));

                // Remove active class from all sub-tab panes in this group
                const tabContent = document.querySelector(contentSelector);
                if (tabContent) {
                    tabContent.querySelectorAll('.tab-pane').forEach(tabPane => {
                        tabPane.classList.remove('show', 'active');
                    });
                }

                // Add active class to clicked tab
                tabLink.classList.add('active');

                // Show corresponding tab pane
                const targetId = tabLink.getAttribute('href');
                const targetPane = document.querySelector(targetId);
                if (targetPane) {
                    targetPane.classList.add('show', 'active');
                    targetPane.style.display = 'block';
                }
            });
        });
    }

    initializeSubTabsForActiveTab(activeTab) {
        // Only handle tabs that have sub-tabs
        if (!['document', 'external', 'interactive'].includes(activeTab)) {
            return;
        }

        let subTabsContainer, firstSubTabId;

        switch(activeTab) {
            case 'document':
                subTabsContainer = '#documentSubTabs';
                firstSubTabId = '#word-excel-ppt';
                break;
            case 'external':
                subTabsContainer = '#externalSubTabs';
                firstSubTabId = '#youtube-vimeo';
                break;
            case 'interactive':
                subTabsContainer = '#interactiveSubTabs';
                firstSubTabId = '#adaptive-learning';
                break;
        }

        // Get sub-tab elements
        const subTabLinks = document.querySelectorAll(`${subTabsContainer} .nav-link`);
        const firstSubTabLink = document.querySelector(`${subTabsContainer} .nav-link[href="${firstSubTabId}"]`);
        const firstSubTabPane = document.querySelector(firstSubTabId);

        if (subTabLinks.length > 0 && firstSubTabLink && firstSubTabPane) {
            // Remove active from all sub-tabs
            subTabLinks.forEach(link => link.classList.remove('active'));

            // Remove active from all sub-tab panes in this section
            const mainTabPane = document.querySelector(`#${activeTab}`);
            if (mainTabPane) {
                const subTabPanes = mainTabPane.querySelectorAll('.tab-content .tab-pane');
                subTabPanes.forEach(pane => pane.classList.remove('show', 'active'));
            }

            // Activate first sub-tab and its pane
            firstSubTabLink.classList.add('active');
            firstSubTabPane.classList.add('show', 'active');

            // Force display and trigger reflow to ensure content is visible
            firstSubTabPane.style.display = 'block';
            firstSubTabPane.offsetHeight; // Force reflow

            // Trigger Bootstrap tab to ensure proper initialization
            if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                const subTabTrigger = new bootstrap.Tab(firstSubTabLink);
                subTabTrigger.show();
            }

            // Additional check to ensure content is visible
            setTimeout(() => {
                if (firstSubTabPane && !firstSubTabPane.classList.contains('show')) {
                    firstSubTabPane.classList.add('show', 'active');
                }

                // Force visibility with inline styles as backup
                if (firstSubTabPane) {
                    firstSubTabPane.style.display = 'block';
                    firstSubTabPane.style.opacity = '1';
                    firstSubTabPane.style.visibility = 'visible';
                }
            }, 100);
        }
    }

    setupTabEventHandlers() {
        // Additional event handlers for tab management can be added here
        // This method is called after all tabs are initialized
    }

    // Public method to programmatically switch tabs
    static switchToTab(tabId) {
        const tabLink = document.querySelector(`a[href="#${tabId}"]`);
        if (tabLink) {
            const tabTrigger = new bootstrap.Tab(tabLink);
            tabTrigger.show();
        }
    }

    // Public method to switch sub-tabs
    static switchToSubTab(subTabId) {
        const subTabLink = document.querySelector(`a[href="#${subTabId}"]`);
        if (subTabLink) {
            subTabLink.click();
        }
    }
}

// Initialize VLR Tab Manager
const vlrTabManager = new VLRTabManager();

// Global helper functions
window.switchVLRTab = function(tabId) {
    VLRTabManager.switchToTab(tabId);
};

window.switchVLRSubTab = function(subTabId) {
    VLRTabManager.switchToSubTab(subTabId);
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = VLRTabManager;
}
