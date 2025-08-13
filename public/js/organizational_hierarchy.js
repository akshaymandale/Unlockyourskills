/**
 * Organizational Hierarchy Management
 * Handles the display and interaction with the organizational structure
 */

class OrganizationalHierarchy {
    constructor() {
        this.hierarchy = [];
        this.expandedNodes = new Set();
        this.init();
    }

    /**
     * Initialize the hierarchy
     */
    init() {
        this.loadHierarchy();
        this.bindEvents();
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Refresh button
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn-refresh')) {
                this.loadHierarchy();
            }
        });

        // Expand/collapse individual nodes
        document.addEventListener('click', (e) => {
            if (e.target.matches('.org-expand')) {
                this.toggleNode(e.target);
            }
        });


    }

    /**
     * Load organizational hierarchy from server
     */
    async loadHierarchy() {
        try {
            this.showLoading();
            
            const response = await fetch('organizational-hierarchy/data');
            const data = await response.json();
            
            if (data.success) {
                console.log('Hierarchy data received:', data);
                this.hierarchy = data.hierarchy;
                this.renderHierarchy();
                this.updateStatistics();
            } else {
                console.error('Failed to load hierarchy:', data);
                this.showError(data.message || 'Failed to load hierarchy');
            }
        } catch (error) {
            console.error('Error loading hierarchy:', error);
            this.showError('Failed to load organizational hierarchy');
        }
    }

    /**
     * Render the hierarchy tree
     */
    renderHierarchy() {
        const container = document.getElementById('hierarchyContent');
        
        if (!this.hierarchy || this.hierarchy.length === 0) {
            container.innerHTML = `
                <div class="no-data">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <p>No organizational data available</p>
                </div>
            `;
            return;
        }

        let html = '';
        this.hierarchy.forEach((node, index) => {
            html += this.renderNode(node, 0, index);
        });

        container.innerHTML = html;
    }

    /**
     * Render a single node
     */
    renderNode(node, level, index) {
        console.log('Rendering node:', node);
        const hasChildren = node.subordinates && node.subordinates.length > 0;
        const isExpanded = this.expandedNodes.has(node.id);
        const levelClass = `level-${Math.min(level, 5)}`;
        
        let html = `
            <div class="org-node ${levelClass}" data-node-id="${node.id}">
                ${hasChildren ? `
                    <button class="org-expand ${isExpanded ? 'expanded' : ''}" data-node-id="${node.id}">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                ` : '<div style="width: 20px;"></div>'}
                
                <div class="org-avatar default">
                    <i class="fas fa-user"></i>
                </div>
                
                <div class="org-info">
                    <div class="org-name">${this.escapeHtml(node.full_name)}</div>
                    <div class="org-role">${this.escapeHtml(node.user_role || 'No Role')}</div>
                    <div class="org-email">${this.escapeHtml(node.email)}</div>
                    ${node.warning ? `<div class="org-warning"><i class="fas fa-exclamation-triangle"></i> ${this.escapeHtml(node.warning)}</div>` : ''}
                </div>
                
                <div class="org-status">
                    ${this.renderStatusBadges(node)}
                </div>
                

            </div>
        `;

        // Render children if expanded
        if (hasChildren) {
            html += `
                <div class="org-children ${isExpanded ? '' : 'collapsed'}" data-parent-id="${node.id}">
            `;
            
            node.subordinates.forEach((child, childIndex) => {
                html += this.renderNode(child, level + 1, childIndex);
            });
            
            html += '</div>';
        }

        return html;
    }

    /**
     * Render status badges for a user
     */
    renderStatusBadges(user) {
        let badges = '';
        
        // User status
        if (user.user_status && user.user_status.toLowerCase() === 'active') {
            badges += '<span class="status-badge status-active">Active</span>';
        } else {
            badges += '<span class="status-badge status-inactive">Inactive</span>';
        }
        
        // Locked status
        if (user.locked_status) {
            badges += '<span class="status-badge status-locked">Locked</span>';
        }
        
        return badges;
    }

    /**
     * Toggle node expansion
     */
    toggleNode(button) {
        const nodeId = button.dataset.nodeId;
        const childrenContainer = document.querySelector(`[data-parent-id="${nodeId}"]`);
        
        if (childrenContainer) {
            const isExpanded = this.expandedNodes.has(nodeId);
            
            if (isExpanded) {
                this.expandedNodes.delete(nodeId);
                button.classList.remove('expanded');
                childrenContainer.classList.add('collapsed');
            } else {
                this.expandedNodes.add(nodeId);
                button.classList.add('expanded');
                childrenContainer.classList.remove('collapsed');
            }
        }
    }

    /**
     * Expand all nodes
     */
    expandAll() {
        const allNodes = document.querySelectorAll('.org-expand');
        allNodes.forEach(node => {
            const nodeId = node.dataset.nodeId;
            if (!this.expandedNodes.has(nodeId)) {
                this.expandedNodes.add(nodeId);
                node.classList.add('expanded');
                
                const childrenContainer = document.querySelector(`[data-parent-id="${nodeId}"]`);
                if (childrenContainer) {
                    childrenContainer.classList.remove('collapsed');
                }
            }
        });
    }

    /**
     * Collapse all nodes
     */
    collapseAll() {
        const allNodes = document.querySelectorAll('.org-expand');
        allNodes.forEach(node => {
            const nodeId = node.dataset.nodeId;
            this.expandedNodes.delete(nodeId);
            node.classList.remove('expanded');
            
            const childrenContainer = document.querySelector(`[data-parent-id="${nodeId}"]`);
            if (childrenContainer) {
                childrenContainer.classList.add('collapsed');
            }
        });
    }

    /**
     * Update statistics
     */
    updateStatistics() {
        const stats = this.calculateStatistics();
        
        document.getElementById('totalUsers').textContent = stats.totalUsers;
        document.getElementById('activeUsers').textContent = stats.activeUsers;
        document.getElementById('totalLevels').textContent = stats.totalLevels;
        document.getElementById('avgTeamSize').textContent = stats.avgTeamSize;
    }

    /**
     * Calculate statistics from hierarchy
     */
    calculateStatistics() {
        let totalUsers = 0;
        let activeUsers = 0;
        let maxLevel = 0;
        let teamSizes = [];

        const countUsers = (nodes, level) => {
            maxLevel = Math.max(maxLevel, level);
            
            nodes.forEach(node => {
                totalUsers++;
                if (node.user_status && node.user_status.toLowerCase() === 'active') {
                    activeUsers++;
                }
                
                if (node.subordinates && node.subordinates.length > 0) {
                    teamSizes.push(node.subordinates.length);
                    countUsers(node.subordinates, level + 1);
                }
            });
        };

        countUsers(this.hierarchy, 0);

        const avgTeamSize = teamSizes.length > 0 ? 
            Math.round(teamSizes.reduce((a, b) => a + b, 0) / teamSizes.length * 10) / 10 : 0;

        return {
            totalUsers,
            activeUsers,
            totalLevels: maxLevel + 1,
            avgTeamSize
        };
    }



    /**
     * Show loading state
     */
    showLoading() {
        const container = document.getElementById('hierarchyContent');
        container.innerHTML = `
            <div class="loading">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <p>Loading organizational hierarchy...</p>
            </div>
        `;
    }

    /**
     * Show error state
     */
    showError(message) {
        const container = document.getElementById('hierarchyContent');
        container.innerHTML = `
            <div class="no-data">
                <i class="fas fa-exclamation-triangle fa-2x mb-3 text-danger"></i>
                <p class="text-danger">${this.escapeHtml(message)}</p>
                <button class="btn btn-primary" onclick="window.location.reload()">Retry</button>
            </div>
        `;
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Refresh hierarchy
     */
    refresh() {
        this.loadHierarchy();
    }
}

// Global functions for button clicks
function expandAll() {
    if (window.orgHierarchy) {
        window.orgHierarchy.expandAll();
    }
}

function collapseAll() {
    if (window.orgHierarchy) {
        window.orgHierarchy.collapseAll();
    }
}

function refreshHierarchy() {
    if (window.orgHierarchy) {
        window.orgHierarchy.refresh();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.orgHierarchy = new OrganizationalHierarchy();
});
