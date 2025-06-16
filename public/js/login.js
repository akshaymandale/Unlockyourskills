/**
 * Login Page JavaScript
 * Handles form validation, AJAX submission, and user interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loginBtnText = document.getElementById('loginBtnText');
    const loginSpinner = document.getElementById('loginSpinner');

    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Clear previous errors
        clearErrors();
        
        // Validate client code
        const clientCode = document.getElementById('client_code').value.trim();
        if (!clientCode) {
            showError('client_code', 'Client code is required');
            isValid = false;
        } else if (!/^[A-Z0-9_]+$/.test(clientCode)) {
            showError('client_code', 'Client code must contain only uppercase letters, numbers, and underscores');
            isValid = false;
        }
        
        // Validate username
        const username = document.getElementById('username').value.trim();
        if (!username) {
            showError('username', 'Username is required');
            isValid = false;
        }
        
        // Validate password
        const password = document.getElementById('password').value;
        if (!password) {
            showError('password', 'Password is required');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.getElementById(fieldId + '_error');
        
        field.classList.add('error');
        errorDiv.textContent = message;
        errorDiv.classList.add('show');
    }
    
    function showGeneralError(message) {
        const errorDiv = document.getElementById('general_error');
        errorDiv.textContent = message;
        errorDiv.classList.add('show');
    }
    
    function clearErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        const inputFields = document.querySelectorAll('.login-input');
        
        errorMessages.forEach(error => {
            error.classList.remove('show');
            error.textContent = '';
        });
        
        inputFields.forEach(field => {
            field.classList.remove('error');
        });
    }
    
    function setLoading(loading) {
        if (loading) {
            loginBtn.disabled = true;
            loginBtnText.style.display = 'none';
            loginSpinner.style.display = 'inline';
        } else {
            loginBtn.disabled = false;
            loginBtnText.style.display = 'inline';
            loginSpinner.style.display = 'none';
        }
    }
    
    // Form submission
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            return;
        }
        
        setLoading(true);
        clearErrors();
        
        const formData = new FormData(loginForm);
        
        fetch(loginForm.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);
            
            if (data.success) {
                if (data.data && data.data.redirect) {
                    window.location.href = data.data.redirect;
                } else {
                    window.location.href = 'index.php?controller=DashboardController&action=index';
                }
            } else {
                showGeneralError(data.message || 'Login failed. Please try again.');
            }
        })
        .catch(error => {
            setLoading(false);
            console.error('Login error:', error);
            showGeneralError('An error occurred during login. Please try again.');
        });
    });
    
    // Real-time validation
    document.getElementById('client_code').addEventListener('input', function() {
        const value = this.value.trim();
        if (value && !/^[A-Z0-9_]+$/.test(value)) {
            showError('client_code', 'Client code must contain only uppercase letters, numbers, and underscores');
        } else {
            const errorDiv = document.getElementById('client_code_error');
            this.classList.remove('error');
            errorDiv.classList.remove('show');
        }
    });
    
    // Auto-uppercase client code input
    document.getElementById('client_code').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Focus management for better UX
    document.getElementById('client_code').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('username').focus();
        }
    });
    
    document.getElementById('username').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('password').focus();
        }
    });
    
    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });
});
