/**
 * Language Switching and Translation Refresh System
 * Handles dynamic language switching without full page reload
 */

class LanguageManager {
    constructor() {
        this.currentLang = this.getCurrentLanguage();
        this.translations = window.translations || {};
        this.init();
    }

    /**
     * Get current language from session or default to 'en'
     */
    getCurrentLanguage() {
        // Try to get from session or cookie
        return document.documentElement.lang || 'en';
    }

    /**
     * Initialize language manager
     */
    init() {
        this.setupLanguageSwitcher();
        this.refreshTranslations();
    }

    /**
     * Setup language switcher event listeners
     */
    setupLanguageSwitcher() {
        const languageItems = document.querySelectorAll('.language-item');
        
        if (languageItems.length > 0) {
            languageItems.forEach(item => {
                item.addEventListener('click', (event) => {
                    event.preventDefault();
                    const selectedLang = item.getAttribute('data-lang');
                    this.switchLanguage(selectedLang);
                });
            });
        }
    }

    /**
     * Switch language and refresh translations
     */
    async switchLanguage(newLang) {
        try {
            console.log(`🔄 Switching language to: ${newLang}`);
            
            // Call language switch endpoint
            const response = await fetch(`/unlockyourskills/lang/${newLang}`, {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (response.ok) {
                // Update current language
                this.currentLang = newLang;
                document.documentElement.lang = newLang;
                
                // Refresh translations
                await this.refreshTranslations();
                
                // Update UI
                this.updateLanguageUI(newLang);
                
                console.log(`✅ Language switched to: ${newLang}`);
                
                // Show success message
                this.showLanguageChangeMessage(newLang);
                
                // Force page reload to ensure all translations are updated
                setTimeout(() => {
                    location.reload();
                }, 1000);
                
            } else {
                throw new Error(`Language switch failed: ${response.status}`);
            }
        } catch (error) {
            console.error('❌ Language switch error:', error);
            this.showLanguageChangeError(error.message);
        }
    }

    /**
     * Refresh JavaScript translations
     */
    async refreshTranslations() {
        try {
            console.log('🔄 Refreshing JavaScript translations...');
            
            // Fetch new translations from server
            const response = await fetch(`/unlockyourskills/api/translations/${this.currentLang}`, {
                method: 'GET',
                credentials: 'same-origin'
            });

            if (response.ok) {
                const newTranslations = await response.json();
                
                // Update global translations
                window.translations = newTranslations;
                this.translations = newTranslations;
                
                // Update translate function if it exists
                if (typeof window.translate === 'function') {
                    // Force refresh of translate function
                    this.refreshTranslateFunction();
                }
                
                console.log(`✅ Translations refreshed for language: ${this.currentLang}`);
                console.log(`📊 Loaded ${Object.keys(newTranslations).length} translation keys`);
                
            } else {
                console.warn('⚠️ Could not fetch new translations, using fallback');
                // Fallback: reload page to get fresh translations
                this.fallbackRefresh();
            }
        } catch (error) {
            console.error('❌ Translation refresh error:', error);
            this.fallbackRefresh();
        }
    }

    /**
     * Fallback method to refresh translations
     */
    fallbackRefresh() {
        console.log('🔄 Using fallback: reloading page for fresh translations');
        // This will be handled by the main language switch which forces page reload
    }

    /**
     * Refresh the translate function to use new translations
     */
    refreshTranslateFunction() {
        // The translate function should automatically use the updated window.translations
        // But we can force a refresh by updating any cached references
        if (window.ImageProgressTracker && typeof window.ImageProgressTracker.refreshTranslations === 'function') {
            window.ImageProgressTracker.refreshTranslations();
        }
        
        // Dispatch custom event for other components to refresh
        window.dispatchEvent(new CustomEvent('translationsRefreshed', {
            detail: { language: this.currentLang, translations: this.translations }
        }));
    }

    /**
     * Update language UI elements
     */
    updateLanguageUI(newLang) {
        // Update selected language display
        const selectedLanguageEl = document.getElementById('selectedLanguage');
        if (selectedLanguageEl) {
            selectedLanguageEl.textContent = newLang.toUpperCase();
        }

        // Update language button icon
        const languageBtnIcon = document.querySelector('.language-btn i');
        if (languageBtnIcon) {
            languageBtnIcon.className = 'fas fa-language';
        }

        // Highlight selected language in dropdown
        this.highlightSelectedLanguage(newLang);

        // Close dropdown
        const languageDropdown = document.getElementById('languageDropdown');
        const languageMenu = document.querySelector('.language-menu');
        if (languageDropdown) languageDropdown.classList.remove('active');
        if (languageMenu) languageMenu.classList.remove('active');
    }

    /**
     * Highlight selected language in dropdown
     */
    highlightSelectedLanguage(selectedLang = null) {
        const selectedLanguageEl = document.getElementById('selectedLanguage');
        if (!selectedLanguageEl) return;

        let currentLang = selectedLang || selectedLanguageEl.textContent.toLowerCase();
        const languageItems = document.querySelectorAll('.language-item');

        languageItems.forEach(langItem => {
            const langCode = langItem.getAttribute('data-lang');
            if (langCode === currentLang) {
                langItem.classList.add('selected');
            } else {
                langItem.classList.remove('selected');
            }
        });
    }

    /**
     * Show language change success message
     */
    showLanguageChangeMessage(newLang) {
        const langNames = {
            'en': 'English',
            'hi': 'Hindi'
        };
        
        const message = `Language changed to ${langNames[newLang] || newLang}. Refreshing page...`;
        
        // Use toast notification if available
        if (typeof showToast === 'function') {
            showToast('success', message);
        } else {
            // Fallback alert
            alert(message);
        }
    }

    /**
     * Show language change error message
     */
    showLanguageChangeError(errorMessage) {
        const message = `Failed to change language: ${errorMessage}`;
        
        // Use toast notification if available
        if (typeof showToast === 'function') {
            showToast('error', message);
        } else {
            // Fallback alert
            alert(message);
        }
    }

    /**
     * Get current translations
     */
    getTranslations() {
        return this.translations;
    }

    /**
     * Check if a translation key exists
     */
    hasTranslation(key) {
        return key in this.translations;
    }
}

// Initialize language manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.languageManager = new LanguageManager();
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LanguageManager;
}

