// Course Applicability JS

document.addEventListener('DOMContentLoaded', function() {
    const applicabilityRadios = document.querySelectorAll('input[name="applicability_type"]');
    const customFieldSection = document.getElementById('customFieldSection');
    const userSection = document.getElementById('userSection');
    const customFieldSelect = document.getElementById('customFieldSelect');
    const customFieldValueSelect = document.getElementById('customFieldValueSelect');
    const courseSelect = document.getElementById('courseSelect');
    const form = document.getElementById('courseApplicabilityForm');
    const rulesList = document.getElementById('applicabilityRulesList');

    function toggleSections() {
        const type = document.querySelector('input[name="applicability_type"]:checked').value;
        customFieldSection.classList.toggle('d-none', type !== 'custom_field');
        userSection.classList.toggle('d-none', type !== 'user');
    }

    applicabilityRadios.forEach(radio => {
        radio.addEventListener('change', toggleSections);
    });

    // Fetch custom field values when a field is selected
    if (customFieldSelect) {
        customFieldSelect.addEventListener('change', function() {
            const fieldId = this.value;
            customFieldValueSelect.innerHTML = `<option value="">Loading...</option>`;
            if (!fieldId) {
                customFieldValueSelect.innerHTML = `<option value="">Select a value...</option>`;
                customFieldValueSelect.style.display = 'none'; // Hide if no field selected
                console.log('[DEBUG] No custom field selected. Hiding value dropdown.');
                return;
            }
            customFieldValueSelect.style.display = 'block'; // Show when field is selected
            // Find the field options from the DOM (populated server-side)
            const fieldOption = this.options[this.selectedIndex];
            // We'll fetch values via AJAX for robustness
            console.log(`[DEBUG] Fetching options for custom field ID: ${fieldId}`);
            fetch(`/Unlockyourskills/api/custom-fields/check-label.php?field_id=${fieldId}`)
                .then(res => res.json())
                .then(data => {
                    console.log('[DEBUG] AJAX response for custom field values:', data);
                    if (data.success && Array.isArray(data.options) && data.options.length > 0) {
                        customFieldValueSelect.innerHTML = `<option value="">Select a value...</option>` +
                            data.options.map(opt => `<option value="${opt}">${opt}</option>`).join('');
                        customFieldValueSelect.style.display = 'block';
                        console.log('[DEBUG] Options loaded and dropdown shown.');
                    } else {
                        customFieldValueSelect.innerHTML = `<option value="">No values found</option>`;
                        customFieldValueSelect.style.display = 'none';
                        console.log('[DEBUG] No options found. Hiding value dropdown.');
                    }
                })
                .catch((err) => {
                    customFieldValueSelect.innerHTML = `<option value="">Error loading values</option>`;
                    customFieldValueSelect.style.display = 'none';
                    console.error('[DEBUG] Error fetching custom field values:', err);
                });
        });
    }

    // Load applicability rules for selected course
    function loadRules() {
        const courseId = courseSelect.value;
        if (!courseId) {
            rulesList.innerHTML = `<div class='text-muted'>Select a course to view applicability rules.</div>`;
            return;
        }
        fetch(`/Unlockyourskills/course-applicability/getApplicability?course_id=${courseId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.rules)) {
                    if (data.rules.length === 0) {
                        rulesList.innerHTML = `<div class='text-muted'>No applicability rules assigned.</div>`;
                        return;
                    }
                    rulesList.innerHTML = data.rules.map(rule => {
                        let desc = '';
                        if (rule.applicability_type === 'all') {
                            desc = 'All Users';
                        } else if (rule.applicability_type === 'custom_field') {
                            desc = `Custom Field: ${rule.custom_field_id} = ${rule.custom_field_value}`;
                        } else if (rule.applicability_type === 'user') {
                            desc = `User ID: ${rule.user_id}`;
                        }
                        return `<div class='d-flex justify-content-between align-items-center border-bottom py-2'>
                            <span>${desc}</span>
                            <button class='btn btn-sm btn-danger remove-applicability' data-id='${rule.id}'>Remove</button>
                        </div>`;
                    }).join('');
                } else {
                    rulesList.innerHTML = `<div class='text-danger'>Error loading rules.</div>`;
                }
            });
    }

    courseSelect.addEventListener('change', loadRules);

    // Assign applicability
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        fetch('/Unlockyourskills/course-applicability/assign', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadRules();
                form.reset();
                toggleSections();
            } else {
                alert(data.message || 'Failed to assign applicability.');
            }
        });
    });

    // Remove applicability
    rulesList.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-applicability')) {
            const id = e.target.getAttribute('data-id');
            if (confirm('Are you sure you want to remove this applicability rule?')) {
                fetch('/Unlockyourskills/course-applicability/remove', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id=${encodeURIComponent(id)}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        loadRules();
                    } else {
                        alert('Failed to remove applicability rule.');
                    }
                });
            }
        }
    });

    // Initial state
    toggleSections();
    loadRules();
}); 