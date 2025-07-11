// user_roles_validations.js
// Validation logic for User Roles & Permissions forms

// Example: Add Role form validation
function validateAddRoleForm() {
    const roleName = document.getElementById('role_name');
    const systemRole = document.getElementById('system_role');
    let valid = true;

    // Validate Role Name
    if (!roleName.value.trim()) {
        valid = false;
        roleName.classList.add('is-invalid');
        if (roleName.nextElementSibling && roleName.nextElementSibling.classList.contains('invalid-feedback')) {
            roleName.nextElementSibling.textContent = 'Role Name is required.';
        }
    } else {
        roleName.classList.remove('is-invalid');
        if (roleName.nextElementSibling && roleName.nextElementSibling.classList.contains('invalid-feedback')) {
            roleName.nextElementSibling.textContent = '';
        }
    }

    // Validate System Role
    if (!systemRole.value.trim()) {
        valid = false;
        systemRole.classList.add('is-invalid');
        if (systemRole.nextElementSibling && systemRole.nextElementSibling.classList.contains('invalid-feedback')) {
            systemRole.nextElementSibling.textContent = 'System Role is required.';
        }
    } else {
        systemRole.classList.remove('is-invalid');
        if (systemRole.nextElementSibling && systemRole.nextElementSibling.classList.contains('invalid-feedback')) {
            systemRole.nextElementSibling.textContent = '';
        }
    }

    if (!valid && typeof showSimpleToast === 'function') {
        showSimpleToast('Please fix the errors in the form.', 'error');
    }
    return valid;
}

// Example: Edit Role form validation
function validateEditRoleForm() {
    const roleName = document.getElementById('edit_role_name');
    const systemRole = document.getElementById('edit_system_role');
    let valid = true;

    // Validate Role Name
    if (!roleName.value.trim()) {
        valid = false;
        roleName.classList.add('is-invalid');
        if (roleName.nextElementSibling && roleName.nextElementSibling.classList.contains('invalid-feedback')) {
            roleName.nextElementSibling.textContent = 'Role Name is required.';
        }
    } else {
        roleName.classList.remove('is-invalid');
        if (roleName.nextElementSibling && roleName.nextElementSibling.classList.contains('invalid-feedback')) {
            roleName.nextElementSibling.textContent = '';
        }
    }

    // Validate System Role
    if (!systemRole.value.trim()) {
        valid = false;
        systemRole.classList.add('is-invalid');
        if (systemRole.nextElementSibling && systemRole.nextElementSibling.classList.contains('invalid-feedback')) {
            systemRole.nextElementSibling.textContent = 'System Role is required.';
        }
    } else {
        systemRole.classList.remove('is-invalid');
        if (systemRole.nextElementSibling && systemRole.nextElementSibling.classList.contains('invalid-feedback')) {
            systemRole.nextElementSibling.textContent = '';
        }
    }

    if (!valid && typeof showSimpleToast === 'function') {
        showSimpleToast('Please fix the errors in the form.', 'error');
    }
    return valid;
}

// Attach validation to forms if present
document.addEventListener('DOMContentLoaded', function() {
    const addRoleForm = document.getElementById('addRoleForm');
    if (addRoleForm) {
        addRoleForm.addEventListener('submit', function(e) {
            if (!validateAddRoleForm()) {
                e.preventDefault();
            }
        });
    }
    const editRoleForm = document.getElementById('editRoleForm');
    if (editRoleForm) {
        editRoleForm.addEventListener('submit', function(e) {
            if (!validateEditRoleForm()) {
                e.preventDefault();
            }
        });
    }
}); 