/**
 * Reports To Autocomplete Functionality
 * Provides autocomplete search for user emails in the reports_to field
 */

class ReportsToAutocomplete {
    constructor() {
        this.currentRequest = null;
        this.abortController = null;
        this.dropdown = null;
        this.input = null;
        this.isOpen = false;
        this.selectedIndex = -1;
        this.suggestions = [];
        this.debounceTimeout = null;
        this.minSearchLength = 1; // Minimum characters before searching (temporarily reduced for testing)
        this.maxResults = 20; // Maximum results to show
        this.searchCache = new Map(); // Cache for recent searches
        this.cacheExpiry = 5 * 60 * 1000; // 5 minutes cache expiry
    }

    /**
     * Initialize autocomplete for a reports_to input field
     */
    init(inputElement) {
        this.input = inputElement;
        this.createDropdown();
        this.bindEvents();
    }

    /**
     * Create the dropdown element
     */
    createDropdown() {
        // Remove existing dropdown if any
        if (this.dropdown) {
            this.dropdown.remove();
        }

        // Create dropdown container
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'reports-to-autocomplete-dropdown';
        this.dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1050;
            display: none;
        `;

        // Insert dropdown after input
        this.input.parentNode.style.position = 'relative';
        this.input.parentNode.appendChild(this.dropdown);
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Input events
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.input.addEventListener('focus', () => this.handleFocus());
        this.input.addEventListener('blur', () => this.handleBlur());

        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.dropdown.contains(e.target)) {
                this.closeDropdown();
            }
        });
    }

    /**
     * Handle input changes
     */
    handleInput(e) {
        const query = e.target.value.trim();
        
        // Clear previous request
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }

        // Always clear autocomplete data attributes when user types
        this.input.removeAttribute('data-selected-email');
        this.input.removeAttribute('data-selected-name');

        // If query is empty or too short, close dropdown immediately
        if (query.length === 0) {
            this.closeDropdown();
            return;
        }
        
        // Don't search for very short queries to improve performance
        if (query.length < this.minSearchLength) {
            this.closeDropdown();
            return;
        }

        // Reset autocomplete state for fresh search
        this.reset();

        // Implement smart debouncing for performance
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }
        
        // Progressive delay based on query length for better performance
        let delay = 0;
        if (query.length < this.minSearchLength) {
            return; // Don't search for very short queries
        } else if (query.length <= 3) {
            delay = 300; // Longer delay for short queries
        } else if (query.length <= 5) {
            delay = 200; // Medium delay for medium queries
        } else {
            delay = 100; // Short delay for long queries
        }
        
        this.debounceTimeout = setTimeout(() => {
            this.searchEmails(query);
        }, delay);
    }

    /**
     * Handle keyboard navigation
     */
    handleKeydown(e) {
        switch (e.key) {
            case 'ArrowDown':
                if (this.isOpen) {
                    e.preventDefault();
                    this.navigateDown();
                }
                break;
            case 'ArrowUp':
                if (this.isOpen) {
                    e.preventDefault();
                    this.navigateUp();
                }
                break;
            case 'Enter':
                if (this.isOpen) {
                    e.preventDefault();
                    this.selectCurrent();
                }
                break;
            case 'Escape':
                if (this.isOpen) {
                    this.closeDropdown();
                } else {
                    // If dropdown is closed, clear the field
                    this.clearField();
                }
                break;
            case 'Backspace':
                // Clear autocomplete state when backspacing to empty
                if (this.input.value.length === 0) {
                    this.closeDropdown();
                }
                break;
        }
    }

    /**
     * Navigate down in suggestions
     */
    navigateDown() {
        this.selectedIndex = Math.min(this.selectedIndex + 1, this.suggestions.length - 1);
        this.updateSelection();
    }

    /**
     * Navigate up in suggestions
     */
    navigateUp() {
        this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
        this.updateSelection();
    }

    /**
     * Update visual selection
     */
    updateSelection() {
        const items = this.dropdown.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });
    }

    /**
     * Select current suggestion
     */
    selectCurrent() {
        if (this.selectedIndex >= 0 && this.selectedIndex < this.suggestions.length) {
            const suggestion = this.suggestions[this.selectedIndex];
            this.selectSuggestion(suggestion);
        }
    }

    /**
     * Handle focus event
     */
    handleFocus() {
        const query = this.input.value.trim();
        // Show dropdown if there's any query
        if (query.length > 0) {
            this.searchEmails(query);
        }
    }

    /**
     * Handle blur event
     */
    handleBlur() {
        // Delay closing to allow click events on dropdown
        setTimeout(() => {
            if (!this.dropdown.contains(document.activeElement)) {
                this.closeDropdown();
            }
        }, 150);
    }

    /**
     * Search for emails
     */
    async searchEmails(query) {
        // Double-check query length (should already be checked in handleInput)
        if (query.length === 0 || query.length < this.minSearchLength) {
            this.closeDropdown();
            return;
        }

        // Check cache first for better performance
        const cacheKey = query.toLowerCase().trim();
        const cachedResult = this.searchCache.get(cacheKey);
        if (cachedResult && (Date.now() - cachedResult.timestamp) < this.cacheExpiry) {
            this.suggestions = cachedResult.data;
            if (this.suggestions.length > 0) {
                this.showSuggestions();
            } else {
                this.closeDropdown();
            }
            return;
        }

        // Get client_id from current user context
        let clientId = window.currentUserClientId || '';
        
        // If no client_id from window context, try to get from modal button
        if (!clientId) {
            const addUserButton = document.querySelector('[data-bs-target="#addUserModal"]');
            if (addUserButton) {
                clientId = addUserButton.getAttribute('data-client-id') || '';
            }
        }

        let url;
        // Check if getProjectUrl function is available
        if (typeof getProjectUrl !== 'function') {
            // Fallback URL construction
            const baseUrl = window.location.origin + '/Unlockyourskills/';
            url = baseUrl + `users/autocomplete?q=${encodeURIComponent(query)}`;
            if (clientId) {
                url += `&client_id=${encodeURIComponent(clientId)}`;
            }
        } else {
            // Use getProjectUrl function for proper URL construction
            url = getProjectUrl(`users/autocomplete?q=${encodeURIComponent(query)}`);
            if (clientId) {
                url += `&client_id=${encodeURIComponent(clientId)}`;
            }
        }
        
        try {
            // Create new AbortController for this request
            this.abortController = new AbortController();
            
            this.currentRequest = fetch(url, {
                signal: this.abortController.signal,
                credentials: 'include'
            });
            
            // Check if request was aborted before processing
            if (this.abortController.signal.aborted) {
                return;
            }
            
            const response = await this.currentRequest;
            
            if (response.ok) {
                const data = await response.json();
                
                if (data.success) {
                    this.suggestions = data.emails;
                    
                    // Cache the result for future use
                    this.searchCache.set(cacheKey, {
                        data: this.suggestions,
                        timestamp: Date.now()
                    });
                    
                    // Limit cache size to prevent memory issues
                    if (this.searchCache.size > 100) {
                        const firstKey = this.searchCache.keys().next().value;
                        this.searchCache.delete(firstKey);
                    }
                    
                    if (this.suggestions.length > 0) {
                        this.showSuggestions();
                    } else {
                        this.closeDropdown();
                    }
                } else {
                    this.closeDropdown();
                    this.suggestions = [];
                }
            } else {
                this.closeDropdown();
                this.suggestions = [];
            }
            
            // Clear the current request reference
            this.currentRequest = null;
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error searching emails:', error);
                this.closeDropdown();
                this.suggestions = [];
            }
            // Clear the current request reference
            this.currentRequest = null;
        }
    }

    /**
     * Show suggestions in dropdown
     */
    showSuggestions() {
        if (!this.suggestions || this.suggestions.length === 0) {
            this.closeDropdown();
            return;
        }

        this.dropdown.innerHTML = '';
        this.selectedIndex = -1;

        // Show result count
        const resultCount = document.createElement('div');
        resultCount.className = 'autocomplete-result-count';
        resultCount.style.cssText = `
            padding: 6px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        `;
        resultCount.textContent = `Showing ${this.suggestions.length} results`;
        this.dropdown.appendChild(resultCount);

        // Show suggestions
        this.suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.style.cssText = `
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            `;

            item.innerHTML = `
                <span class="email">${this.highlightMatch(suggestion.email)}</span>
                <small class="name text-muted">${suggestion.full_name}</small>
            `;

            item.addEventListener('click', () => this.selectSuggestion(suggestion));
            item.addEventListener('mouseenter', () => {
                this.selectedIndex = index;
                this.updateSelection();
            });

            this.dropdown.appendChild(item);
        });

        // Add "show more" option if we're at the limit
        if (this.suggestions.length >= this.maxResults) {
            const showMoreItem = document.createElement('div');
            showMoreItem.className = 'autocomplete-show-more';
            showMoreItem.style.cssText = `
                padding: 8px 12px;
                background: #e9ecef;
                border-top: 1px solid #dee2e6;
                text-align: center;
                font-size: 12px;
                color: #495057;
                cursor: pointer;
            `;
            showMoreItem.textContent = `Type more characters to refine search`;
            this.dropdown.appendChild(showMoreItem);
        }

        this.showDropdown();
    }

    /**
     * Highlight matching text
     */
    highlightMatch(text) {
        const query = this.input.value.trim();
        if (!query) return text;
        
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }

    /**
     * Select a suggestion
     */
    selectSuggestion(suggestion) {
        this.input.value = suggestion.email;
        this.input.setAttribute('data-selected-email', suggestion.email);
        this.input.setAttribute('data-selected-name', suggestion.full_name);
        
        // Trigger change event for validation
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
        this.input.dispatchEvent(new Event('blur', { bubbles: true }));
        
        this.closeDropdown();
    }

    /**
     * Clear field and reset autocomplete state
     */
    clearField() {
        this.input.value = '';
        this.input.removeAttribute('data-selected-email');
        this.input.removeAttribute('data-selected-name');
        this.closeDropdown();
    }

    /**
     * Reset autocomplete state
     */
    reset() {
        this.suggestions = [];
        this.selectedIndex = -1;
        this.isOpen = false;
        if (this.dropdown) {
            this.dropdown.innerHTML = '';
            this.dropdown.style.display = 'none';
        }
        // Don't abort request here as it will be aborted in handleInput
        // Clear abort controller
        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }
    }

    /**
     * Show dropdown
     */
    showDropdown() {
        if (this.dropdown && this.suggestions && this.suggestions.length > 0) {
            this.dropdown.style.display = 'block';
            this.isOpen = true;
        } else {
            this.closeDropdown();
        }
    }

    /**
     * Close dropdown
     */
    closeDropdown() {
        if (this.dropdown) {
            this.dropdown.style.display = 'none';
            this.dropdown.innerHTML = '';
        }
        this.isOpen = false;
        this.selectedIndex = -1;
        // Don't clear suggestions array here as it might be needed for display
    }

    /**
     * Destroy autocomplete
     */
    destroy() {
        if (this.dropdown) {
            this.dropdown.remove();
        }
        if (this.abortController) {
            this.abortController.abort();
        }
    }
}

// Initialize autocomplete for all reports_to fields
function initializeReportsToAutocomplete() {
    const reportsToFields = document.querySelectorAll('input[name="reports_to"]');
    
    reportsToFields.forEach((field, index) => {
        const autocomplete = new ReportsToAutocomplete();
        autocomplete.init(field);
        
        // Store reference for cleanup
        field._autocomplete = autocomplete;
    });
}

// Wait for both DOM and getProjectUrl function to be available
function waitForDependencies() {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForDependencies);
        return;
    }
    
    if (typeof getProjectUrl === 'function') {
        initializeReportsToAutocomplete();
    } else {
        setTimeout(waitForDependencies, 100);
    }
}

// Start the initialization process
waitForDependencies();

// Initialize autocomplete for dynamically loaded content
document.addEventListener('shown.bs.modal', function(e) {
    if (e.target.id === 'addUserModal' || e.target.id === 'editUserModal') {
        setTimeout(() => {
            const reportsToField = e.target.querySelector('input[name="reports_to"]');
            if (reportsToField && !reportsToField._autocomplete) {
                const autocomplete = new ReportsToAutocomplete();
                autocomplete.init(reportsToField);
                reportsToField._autocomplete = autocomplete;
            }
        }, 100);
    }
});

