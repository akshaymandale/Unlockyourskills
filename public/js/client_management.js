/**
 * Client Management JavaScript
 * Handles client management functionality including search, modals, and form interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize client management functionality
    initializeSearch();
    initializeModals();
    initializeClientCodeFormatting();
    initializeDeleteConfirmation();
    initializeToastMessages();
    
    // Search functionality
    function initializeSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchButton = document.getElementById('searchButton');
        const statusFilter = document.getElementById('statusFilter');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');

        if (!searchInput || !searchButton || !statusFilter || !clearFiltersBtn) {
            return; // Elements not found, skip initialization
        }

        // Search on button click
        searchButton.addEventListener('click', function() {
            performSearch();
        });

        // Search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Filter on status change
        statusFilter.addEventListener('change', function() {
            performSearch();
        });

        // Clear filters
        clearFiltersBtn.addEventListener('click', function() {
            searchInput.value = '';
            statusFilter.value = '';
            window.location.href = 'index.php?controller=ClientController';
        });
    }

    function performSearch() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        
        const search = searchInput.value.trim();
        const status = statusFilter.value;

        let url = 'index.php?controller=ClientController';
        const params = [];

        if (search) params.push('search=' + encodeURIComponent(search));
        if (status) params.push('status=' + encodeURIComponent(status));

        if (params.length > 0) {
            url += '&' + params.join('&');
        }

        window.location.href = url;
    }

    // Modal functionality
    function initializeModals() {
        // Reset add form when modal closes
        const addModal = document.getElementById('addClientModal');
        if (addModal) {
            $(addModal).on('hidden.bs.modal', function() {
                resetForm('addClientForm', window.translations?.['clients_create_client'] || 'Create Client');
            });
        }

        // Reset edit form when modal closes
        const editModal = document.getElementById('editClientModal');
        if (editModal) {
            $(editModal).on('hidden.bs.modal', function() {
                resetEditForm();
            });
        }

        // Edit Client Modal Functionality
        document.addEventListener('click', function(e) {
            if (e.target.closest('.edit-client-btn')) {
                handleEditClient(e.target.closest('.edit-client-btn'));
            }
        });
    }

    function resetForm(formId, submitButtonText) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.reset();

        // Remove validation errors
        form.querySelectorAll('.error-message').forEach(el => {
            el.style.display = 'none';
            el.textContent = '';
        });
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Reset submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = submitButtonText;
            submitBtn.disabled = false;
        }
    }

    function resetEditForm() {
        const form = document.getElementById('editClientForm');
        if (!form) return;

        form.reset();
        
        // Hide logo preview
        const logoPreview = document.getElementById('current_logo_preview');
        if (logoPreview) {
            logoPreview.style.display = 'none';
        }

        // Remove validation errors
        document.querySelectorAll('#editClientForm .error-message').forEach(el => {
            el.textContent = '';
            el.style.display = 'none';
        });
        document.querySelectorAll('#editClientForm .is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // Reset submit button
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.innerHTML = window.translations?.['clients_update_client'] || 'Update Client';
            submitBtn.disabled = false;
        }
    }

    function handleEditClient(button) {
        const clientId = button.getAttribute('data-client-id');

        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        // Fetch client data
        fetch(`index.php?controller=ClientController&action=edit&id=${clientId}&ajax=1`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateEditModal(data.client);
                    $('#editClientModal').modal('show');
                } else {
                    alert('Error: ' + (data.error || 'Failed to load client data'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load client data. Please try again.');
            })
            .finally(() => {
                // Reset button state
                button.innerHTML = '<i class="fas fa-edit"></i>';
                button.disabled = false;
            });
    }

    function populateEditModal(client) {
        // Populate basic information
        document.getElementById('edit_client_id').value = client.id;
        document.getElementById('edit_client_name').value = client.client_name || '';
        document.getElementById('edit_client_code').value = client.client_code || '';
        document.getElementById('edit_max_users').value = client.max_users || '';
        document.getElementById('edit_status').value = client.status || 'active';

        // Populate configuration settings - ensure proper type conversion
        const reportsEnabled = client.reports_enabled;
        const themeSettings = client.theme_settings;
        const ssoEnabled = client.sso_enabled;
        const customFieldCreation = client.custom_field_creation;

        // Set select values (convert to string for proper matching)
        document.getElementById('edit_reports_enabled').value = (reportsEnabled == 1 || reportsEnabled === '1' || reportsEnabled === true) ? '1' : '0';
        document.getElementById('edit_theme_settings').value = (themeSettings == 1 || themeSettings === '1' || themeSettings === true) ? '1' : '0';
        document.getElementById('edit_sso_enabled').value = (ssoEnabled == 1 || ssoEnabled === '1' || ssoEnabled === true) ? '1' : '0';
        document.getElementById('edit_admin_role_limit').value = client.admin_role_limit || '1';

        // Set checkbox value
        document.getElementById('edit_custom_field_creation').checked = (customFieldCreation == 1 || customFieldCreation === '1' || customFieldCreation === true);

        // Populate description
        document.getElementById('edit_description').value = client.description || '';

        // Show current logo if exists
        const logoPreview = document.getElementById('current_logo_preview');
        const logoImg = document.getElementById('current_logo_img');
        if (client.logo_path) {
            logoImg.src = client.logo_path;
            logoPreview.style.display = 'block';
        } else {
            logoPreview.style.display = 'none';
        }
    }

    // Client code formatting
    function initializeClientCodeFormatting() {
        const clientCodeField = document.getElementById('client_code');
        const editClientCodeField = document.getElementById('edit_client_code');

        if (clientCodeField) {
            clientCodeField.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
            });
        }

        if (editClientCodeField) {
            editClientCodeField.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
            });
        }
    }

    // Delete confirmation
    function initializeDeleteConfirmation() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-client')) {
                e.preventDefault();
                handleDeleteClient(e.target.closest('.delete-client'));
            }
        });
    }

    function handleDeleteClient(element) {
        const id = element.dataset.id;
        const name = element.dataset.name;

        // Show loading state
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        element.disabled = true;

        // First check if client can be deleted
        fetch(`index.php?controller=ClientController&action=canDelete&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showToastOrAlert(data.error, 'error');
                } else if (!data.canDelete) {
                    showToastOrAlert(data.message, 'warning');
                } else {
                    // Client can be deleted, show confirmation modal
                    if (typeof window.confirmDelete === 'function') {
                        window.confirmDelete(`client "${name}"`, function() {
                            window.location.href = `index.php?controller=ClientController&action=delete&id=${id}`;
                        });
                    } else {
                        // Fallback to browser confirm if modal system not available
                        if (confirm(`Are you sure you want to delete client "${name}"? This action is not reversible.`)) {
                            window.location.href = `index.php?controller=ClientController&action=delete&id=${id}`;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error checking client delete status:', error);
                showToastOrAlert('Failed to check client status. Please try again.', 'error');
            })
            .finally(() => {
                // Reset button state
                element.innerHTML = '<i class="fas fa-trash-alt"></i>';
                element.disabled = false;
            });
    }

    // Toast messages
    function initializeToastMessages() {
        const urlParams = new URLSearchParams(window.location.search);

        // Handle toast messages from URL parameters
        if (urlParams.has('message') && urlParams.has('type')) {
            const message = decodeURIComponent(urlParams.get('message'));
            const type = urlParams.get('type');

            showToastOrAlert(message, type);

            // Clean URL by removing message parameters
            const cleanUrl = new URL(window.location);
            cleanUrl.searchParams.delete('message');
            cleanUrl.searchParams.delete('type');
            window.history.replaceState({}, document.title, cleanUrl.toString());
        }

        // Legacy support for old URL parameters (backward compatibility)
        if (urlParams.has('success')) {
            const message = decodeURIComponent(urlParams.get('success'));
            showToastOrAlert(message, 'success');

            // Clean URL
            const cleanUrl = new URL(window.location);
            cleanUrl.searchParams.delete('success');
            window.history.replaceState({}, document.title, cleanUrl.toString());
        }

        if (urlParams.has('error')) {
            const message = decodeURIComponent(urlParams.get('error'));
            showToastOrAlert(message, 'error');

            // Clean URL
            const cleanUrl = new URL(window.location);
            cleanUrl.searchParams.delete('error');
            window.history.replaceState({}, document.title, cleanUrl.toString());
        }
    }

    function showToastOrAlert(message, type) {
        if (typeof window.showSimpleToast === 'function') {
            window.showSimpleToast(message, type);
        } else {
            alert(message);
        }
    }

    // Function to refresh client cards after update
    function refreshClientCards() {
        // Reload the page to refresh all client cards
        // This is the simplest approach that ensures all data is current
        window.location.reload();
    }

    // Function to show toast notifications (enhanced version)
    function showToast(type, message) {
        // Use the existing toast system
        showToastOrAlert(message, type);
    }

    // Make functions available globally for form validation
    window.refreshClientCards = refreshClientCards;
    window.showToast = showToast;
});
