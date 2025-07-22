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
                        // Add course name to the grid row
                        return `<div class='d-flex justify-content-between align-items-center border-bottom py-2'>
                            <span><strong>${rule.course_name}</strong> &mdash; ${desc}</span>
                            <button class='btn btn-sm btn-danger remove-applicability' data-id='${rule.id}'>Remove</button>
                        </div>`;
                    }).join('');
                } else {
                    rulesList.innerHTML = `<div class='text-danger'>Error loading rules.</div>`;
                }
            });
    }

    // When course or applicability type changes, reload user checkboxes with pre-checked users
    function loadApplicableUsersForCourse(courseId) {
        if (!courseId) return;
        fetch(`/Unlockyourskills/course-applicability/getApplicableUsers?course_id=${encodeURIComponent(courseId)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.user_ids)) {
                    selectedUserIds = data.user_ids.map(String); // Pre-check these users
                    // Fix: Map full_name to name for summary
                    if (data.users) {
                        selectedUsersMap = {};
                        data.users.forEach(u => {
                            selectedUsersMap[String(u.id)] = {
                                ...u,
                                name: u.name || u.full_name || 'Unknown'
                            };
                        });
                    }
                    updateSelectedUsersSummary();
                    ajaxSearchUsers(userAutocomplete.value.trim(), userSearchPage);
                }
            });
    }

    // Listen for course selection and applicability type change
    courseSelect.addEventListener('change', function() {
        if (document.querySelector('input[name="applicability_type"]:checked').value === 'user') {
            loadApplicableUsersForCourse(this.value);
        }
    });
    applicabilityRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'user') {
                loadApplicableUsersForCourse(courseSelect.value);
            }
        });
    });

    // Assign applicability
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        // Remove any existing user_ids[] hidden inputs
        form.querySelectorAll('input[name="user_ids[]"]').forEach(el => el.remove());
        // Add one hidden input per selected user
        selectedUserIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = id;
            form.appendChild(input);
        });
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

    // --- Autocomplete for course selection ---
    const courseAutocomplete = document.getElementById('courseAutocomplete');
    let coursesList = [];

    // This array will be rendered server-side for all courses
    if (window.coursesListData) {
        coursesList = window.coursesListData;
    }

    // Create dropdown for autocomplete
    const autocompleteDropdown = document.createElement('div');
    autocompleteDropdown.className = 'autocomplete-dropdown list-group position-absolute';
    autocompleteDropdown.style.zIndex = 1000;
    autocompleteDropdown.style.display = 'none';
    autocompleteDropdown.style.background = '#fff';
    autocompleteDropdown.style.border = '1px solid #ced4da';
    autocompleteDropdown.style.maxHeight = '220px';
    autocompleteDropdown.style.overflowY = 'auto';
    autocompleteDropdown.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
    autocompleteDropdown.style.width = '100%';
    autocompleteDropdown.style.left = 0;
    autocompleteDropdown.style.top = '100%';
    autocompleteDropdown.style.marginTop = '2px';
    courseAutocomplete.parentNode.style.position = 'relative';
    courseAutocomplete.parentNode.appendChild(autocompleteDropdown);

    courseAutocomplete.addEventListener('input', function() {
        const value = this.value.trim().toLowerCase();
        autocompleteDropdown.innerHTML = '';
        if (!value) {
            autocompleteDropdown.style.display = 'none';
            document.getElementById('courseSelect').value = '';
            return;
        }
        const matches = coursesList.filter(c => c.name.toLowerCase().includes(value));
        if (matches.length === 0) {
            autocompleteDropdown.style.display = 'none';
            return;
        }
        matches.forEach(course => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'list-group-item list-group-item-action';
            item.textContent = course.name;
            item.style.textAlign = 'left';
            item.style.whiteSpace = 'nowrap';
            item.style.overflow = 'hidden';
            item.style.textOverflow = 'ellipsis';
            item.addEventListener('mouseover', function() {
                item.style.background = '#f0f0f0';
            });
            item.addEventListener('mouseout', function() {
                item.style.background = '#fff';
            });
            item.addEventListener('click', function() {
                courseAutocomplete.value = course.name;
                document.getElementById('courseSelect').value = course.id;
                autocompleteDropdown.style.display = 'none';
                loadRules();
            });
            autocompleteDropdown.appendChild(item);
        });
        autocompleteDropdown.style.display = 'block';
    });

    // Hide dropdown on blur
    courseAutocomplete.addEventListener('blur', function() {
        setTimeout(() => { autocompleteDropdown.style.display = 'none'; }, 200);
    });

    // Hide dropdown on form submit
    form.addEventListener('submit', function() {
        autocompleteDropdown.style.display = 'none';
    });

    // --- AJAX Autocomplete and checkbox list for user selection (scalable) ---
    const userAutocomplete = document.getElementById('userAutocomplete');
    const userDropdown = document.getElementById('userDropdown');
    const userCheckboxList = document.getElementById('userCheckboxList');
    const userSelectHidden = document.getElementById('userSelect');
    const selectedUsersSummary = document.getElementById('selectedUsersSummary');
    let selectedUserIds = [];
    let selectedUsersMap = {};
    let userSearchTimeout = null;
    let userSearchPage = 1;
    let userSearchHasMore = false;
    let userSearchQuery = '';

    function renderUserCheckboxList(users) {
        userCheckboxList.innerHTML = '';
        users.forEach(user => {
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check';
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input';
            checkbox.id = 'user_cb_' + user.id;
            checkbox.value = user.id;
            checkbox.checked = selectedUserIds.includes(user.id.toString());
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    if (!selectedUserIds.includes(user.id.toString())) {
                        selectedUserIds.push(user.id.toString());
                        selectedUsersMap[user.id] = user;
                    }
                } else {
                    selectedUserIds = selectedUserIds.filter(id => id !== user.id.toString());
                    delete selectedUsersMap[user.id];
                }
                updateSelectedUsersSummary();
                userSelectHidden.value = selectedUserIds.join(',');
            });
            const label = document.createElement('label');
            label.className = 'form-check-label';
            label.htmlFor = checkbox.id;
            label.textContent = user.name + ' (' + user.email + ')';
            wrapper.appendChild(checkbox);
            wrapper.appendChild(label);
            userCheckboxList.appendChild(wrapper);
        });
        // Pagination controls
        const paginationDiv = document.createElement('div');
        paginationDiv.className = 'd-flex justify-content-between align-items-center mt-2';
        if (userSearchPage > 1) {
            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = 'btn btn-sm btn-outline-secondary';
            prevBtn.textContent = 'Previous';
            prevBtn.onclick = function() {
                userSearchPage--;
                ajaxSearchUsers(userSearchQuery, userSearchPage);
            };
            paginationDiv.appendChild(prevBtn);
        }
        if (userSearchHasMore) {
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'btn btn-sm btn-outline-secondary ms-auto';
            nextBtn.textContent = 'Next';
            nextBtn.onclick = function() {
                userSearchPage++;
                ajaxSearchUsers(userSearchQuery, userSearchPage);
            };
            paginationDiv.appendChild(nextBtn);
        }
        if (paginationDiv.children.length > 0) {
            userCheckboxList.appendChild(paginationDiv);
        }
        userSelectHidden.value = selectedUserIds.join(',');
    }

    function updateSelectedUsersSummary() {
        selectedUsersSummary.innerHTML = '';
        if (selectedUserIds.length === 0) {
            selectedUsersSummary.innerHTML = '<span class="text-muted">No users selected.</span>';
            return;
        }
        selectedUserIds.forEach(id => {
            const user = selectedUsersMap[id];
            if (!user) return;
            const badge = document.createElement('span');
            badge.className = 'badge bg-primary me-1 mb-1';
            badge.textContent = user.name + ' (' + user.email + ')';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-close btn-close-white ms-2';
            removeBtn.style.fontSize = '0.7em';
            removeBtn.style.marginLeft = '6px';
            removeBtn.addEventListener('click', function() {
                selectedUserIds = selectedUserIds.filter(uid => uid !== id);
                delete selectedUsersMap[id];
                updateSelectedUsersSummary();
                userSelectHidden.value = selectedUserIds.join(',');
                // Uncheck in visible list if present
                const cb = document.getElementById('user_cb_' + id);
                if (cb) cb.checked = false;
            });
            badge.appendChild(removeBtn);
            selectedUsersSummary.appendChild(badge);
        });
    }

    function ajaxSearchUsers(query, page = 1) {
        userCheckboxList.innerHTML = '<div class="text-muted">Searching...</div>';
        userSearchQuery = query;
        userSearchPage = page;
        fetch(`/Unlockyourskills/course-applicability/search-users?query=${encodeURIComponent(query)}&page=${page}`)
            .then(res => res.json())
            .then(data => {
                userSearchHasMore = !!data.has_more;
                if (data.success && Array.isArray(data.users)) {
                    renderUserCheckboxList(data.users);
                } else {
                    userCheckboxList.innerHTML = '<div class="text-danger">Error loading users.</div>';
                }
            })
            .catch(() => {
                userCheckboxList.innerHTML = '<div class="text-danger">Error loading users.</div>';
            });
    }

    userAutocomplete.addEventListener('input', function() {
        const value = this.value.trim();
        if (userSearchTimeout) clearTimeout(userSearchTimeout);
        userSearchTimeout = setTimeout(() => {
            ajaxSearchUsers(value, 1);
        }, 300);
    });

    userAutocomplete.addEventListener('focus', function() {
        ajaxSearchUsers(userAutocomplete.value.trim(), userSearchPage);
    });

    // Initial render: show empty search (all users, first page)
    ajaxSearchUsers('', 1);
    updateSelectedUsersSummary();
}); 