/**
 * JavaScript Translation System
 * Provides client-side translation functionality
 */

// Global translations object - will be populated by PHP
window.translations = window.translations || {};

/**
 * Translate a key to the current language
 * @param {string} key - Translation key (e.g., 'js.validation.title_required')
 * @param {object} replacements - Optional replacements for placeholders
 * @returns {string} - Translated text or the key if not found
 */
function translate(key, replacements = {}) {
    let translation = window.translations[key] || key;
    
    // Replace placeholders like {field} with actual values
    Object.keys(replacements).forEach(placeholder => {
        const regex = new RegExp(`\\{${placeholder}\\}`, 'g');
        translation = translation.replace(regex, replacements[placeholder]);
    });
    
    return translation;
}

/**
 * Alias for translate function for shorter usage
 */
if (typeof window.t === 'undefined') {
    window.t = translate;
}

/**
 * Load translations from server
 * This function should be called when the page loads
 */
function loadTranslations() {
    // Translations will be injected by PHP in the HTML head
    console.log('Translations loaded:', Object.keys(window.translations).length, 'keys');
}

// Auto-load translations when DOM is ready
document.addEventListener('DOMContentLoaded', loadTranslations);
