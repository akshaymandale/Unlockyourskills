/**
 * Organization Delete Confirmations Module
 * Handles delete confirmations for organizations with internationalization support
 */

class OrganizationConfirmations {
    constructor() {
        this.init();
    }

    init() {
        // Organization delete confirmations
        document.addEventListener('click', (e) => {
            const target = e.target.closest('.delete-organization');
            if (target) {
                e.preventDefault();
                this.handleOrganizationDelete(target);
            }
        });
    }

    // Helper function to get translation with fallback
    getTranslation(key, replacements = {}) {
        if (typeof translate === 'function') {
            return translate(key, replacements);
        } else if (typeof window.translations === 'object' && window.translations[key]) {
            let text = window.translations[key];
            // Replace placeholders
            Object.keys(replacements).forEach(placeholder => {
                const regex = new RegExp(`\\{${placeholder}\\}`, 'g');
                text = text.replace(regex, replacements[placeholder]);
            });
            return text;
        }
        return key; // Fallback to key if no translation found
    }

    // Get translated item name for organizations
    getTranslatedItemName(data) {
        const replacements = { name: data.name };
        return this.getTranslation('item.organization', replacements) || `organization "${data.name}"`;
    }

    handleOrganizationDelete(element) {
        const id = element.dataset.id;
        const name = element.dataset.name;

        if (!id || !name) {
            console.error('Missing organization data for deletion');
            return;
        }

        const data = {
            id: id,
            name: name,
            action: `index.php?controller=OrganizationController&action=delete&id=${id}`
        };

        this.showOrganizationConfirmation(data);
    }

    showOrganizationConfirmation(data) {
        const itemName = this.getTranslatedItemName(data);
        
        // Use window.confirmDelete if available, otherwise fallback to browser confirm
        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                window.location.href = data.action;
            });
        } else {
            const fallbackMessage = this.getTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                window.location.href = data.action;
            }
        }
    }

    // Static helper method
    static deleteOrganization(id, name) {
        const url = `index.php?controller=OrganizationController&action=delete&id=${id}`;
        const data = { name: name };
        const itemName = OrganizationConfirmations.getStaticTranslatedItemName(data);

        if (typeof window.confirmDelete === 'function') {
            window.confirmDelete(itemName, () => {
                window.location.href = url;
            });
        } else {
            const fallbackMessage = OrganizationConfirmations.getStaticTranslation('confirmation.delete.message', {item: itemName}) || `Are you sure you want to delete ${itemName}?`;
            if (confirm(fallbackMessage)) {
                window.location.href = url;
            }
        }
    }

    // Static helper methods for translations
    static getStaticTranslation(key, replacements = {}) {
        if (typeof translate === 'function') {
            return translate(key, replacements);
        } else if (typeof window.translations === 'object' && window.translations[key]) {
            let text = window.translations[key];
            // Replace placeholders
            Object.keys(replacements).forEach(placeholder => {
                const regex = new RegExp(`\\{${placeholder}\\}`, 'g');
                text = text.replace(regex, replacements[placeholder]);
            });
            return text;
        }
        return key; // Fallback to key if no translation found
    }

    static getStaticTranslatedItemName(data) {
        const replacements = { name: data.name };
        return OrganizationConfirmations.getStaticTranslation('item.organization', replacements) || `organization "${data.name}"`;
    }
}

// Initialize Organization confirmations
document.addEventListener('DOMContentLoaded', function() {
    // Check for organization management page elements
    const orgPageElement = document.querySelector(
        '.organization-card, .delete-organization, [data-organization-page]'
    );
    
    if (orgPageElement) {
        window.organizationConfirmationsInstance = new OrganizationConfirmations();
    }
});

// Also initialize immediately if DOM is already loaded
if (document.readyState !== 'loading') {
    const orgPageElement = document.querySelector(
        '.organization-card, .delete-organization, [data-organization-page]'
    );
    
    if (orgPageElement) {
        window.organizationConfirmationsInstance = new OrganizationConfirmations();
    }
}

// Global helper functions
window.deleteOrganization = function(id, name) {
    OrganizationConfirmations.deleteOrganization(id, name);
};
