// Helper function to get project-relative URLs
function getProjectUrl(path) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;

    // Extract the project base path - for Unlockyourskills, it's always /Unlockyourskills
    let basePath = '';

    // Find the project root by looking for the first path segment
    const pathParts = pathname.split('/').filter(part => part !== '');
    if (pathParts.length > 0) {
        basePath = '/' + pathParts[0]; // /Unlockyourskills
    }

    // Clean up input path
    path = path.replace(/^\/+/, '');

    const fullUrl = `${protocol}//${host}${basePath}/${path}`;

    return fullUrl;
}

// Helper function to get API URLs
function getApiUrl(path) {
    return getProjectUrl('api/' + path.replace(/^\/+/, ''));
}

document.addEventListener("DOMContentLoaded", function () {
    let profileToggle = document.getElementById("profileToggle");
    const profileMenu = document.querySelector(".profile-menu");

    const languageToggle = document.getElementById("languageToggle");
    const languageMenu = document.querySelector(".language-menu");
    const languageDropdown = document.getElementById("languageDropdown");
    const languageSearch = document.getElementById("languageSearch");
    const languageItems = document.querySelectorAll(".language-item");

    let sidebar = document.getElementById("sidebar");
    let container = document.querySelector(".container");

    // ✅ Adjust container width on load
    adjustContainerWidth();

    function adjustContainerWidth() {
        if (container && sidebar.classList.contains("collapsed")) {
            container.style.marginLeft = "80px";
            container.style.maxWidth = "calc(100% - 80px)";
        } else if (container) {
            container.style.marginLeft = "250px";
            container.style.maxWidth = "calc(100% - 250px)";
        }
    }

    // ✅ Ensure correct container margin based on sidebar state
    if (container && sidebar.classList.contains("collapsed")) {
        container.style.marginLeft = "80px"; // Sidebar is collapsed
    } else if (container) {
        container.style.marginLeft = "250px"; // Sidebar is expanded
    }

    // ✅ Toggle Sidebar on Button Click
    let sidebarToggle = document.getElementById("sidebarToggle");
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("collapsed");

            if (container && sidebar.classList.contains("collapsed")) {
                container.style.marginLeft = "80px"; // Adjust for collapsed sidebar
            } else if (container) {
                container.style.marginLeft = "250px"; // Adjust for expanded sidebar
            }

            adjustContainerWidth();
        });
    }

    // ✅ Toggle Profile Dropdown
    if (profileToggle && profileMenu) {
        profileToggle.addEventListener("click", function (event) {
            event.stopPropagation();
            profileMenu.classList.toggle("active");
            if (languageMenu) languageMenu.classList.remove("active");
        });
    }

    // ✅ Toggle Language Dropdown
    if (languageToggle && languageMenu && languageDropdown && languageSearch) {
        languageToggle.addEventListener("click", function (event) {
            event.stopPropagation();
            languageMenu.classList.toggle("active");
            if (profileMenu) profileMenu.classList.remove("active");
            languageDropdown.classList.toggle("active");

            // ✅ Clear search box and show all languages again
            languageSearch.value = "";
            languageItems.forEach(item => item.style.display = "block");

            // ✅ Ensure the currently selected language is highlighted
            highlightSelectedLanguage();
        });
    }

    // ✅ Prevent dropdown from closing when clicking inside
    if (languageDropdown) {
        languageDropdown.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    }

    // ✅ Keep dropdown open when clicking inside the search box
    if (languageSearch) {
        languageSearch.addEventListener("click", function (event) {
            event.stopPropagation();
        });
    }

    // ✅ Close dropdown when clicking outside
    document.addEventListener("click", function () {
        if (profileMenu) profileMenu.classList.remove("active");
        if (languageMenu) languageMenu.classList.remove("active");
    });

    // ✅ Filter languages in real-time
    if (languageSearch && languageItems.length > 0) {
        languageSearch.addEventListener("input", function () {
            const searchValue = this.value.toLowerCase();

            languageItems.forEach(function (item) {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchValue) ? "block" : "none";
            });
        });
    }

    // ✅ Language selection without full-page reload
    if (languageItems.length > 0) {
        languageItems.forEach(item => {
            item.addEventListener("click", function (event) {
                event.preventDefault();
                let selectedLang = this.getAttribute("data-lang");

                // ✅ Call the language switching endpoint
                let langUrl = getProjectUrl(`lang/${selectedLang}`);

                fetch(langUrl, {
                    method: 'GET',
                    credentials: 'same-origin'
                })
                    .then(() => {
                        // ✅ Update Navbar Language
                        const selectedLanguageEl = document.getElementById("selectedLanguage");
                        const languageBtnIcon = document.querySelector(".language-btn i");

                        if (selectedLanguageEl) selectedLanguageEl.textContent = selectedLang.toUpperCase();
                        if (languageBtnIcon) languageBtnIcon.className = "fas fa-language";

                        // ✅ Mark selected language in the dropdown
                        highlightSelectedLanguage(selectedLang);

                        // ✅ Close the dropdown
                        if (languageDropdown) languageDropdown.classList.remove("active");
                        if (languageMenu) languageMenu.classList.remove("active");
                        // ✅ FORCE PAGE RELOAD TO APPLY LOCALIZATION
                        location.reload();
                    })
                    .catch(err => console.error("Language switch error:", err));
            });
        });
    }

    // ✅ Function to highlight the selected language in the dropdown
    function highlightSelectedLanguage(selectedLang = null) {
        const selectedLanguageEl = document.getElementById("selectedLanguage");
        if (!selectedLanguageEl || languageItems.length === 0) return;

        let currentLang = selectedLang || selectedLanguageEl.textContent.toLowerCase();

        languageItems.forEach(langItem => {
            langItem.classList.remove("selected");
            if (langItem.getAttribute("data-lang").toLowerCase() === currentLang) {
                langItem.classList.add("selected");
            }
        });
    }

    // ✅ Ensure selected language is highlighted on page load
    if (languageItems.length > 0) {
        highlightSelectedLanguage();
    }


    document.querySelectorAll(".tab-pane").forEach(pane => {
        if (pane.id === "user-details") {
            pane.style.display = "block"; // Force show
        } else {
            pane.style.display = "none";
        }
    });

    let activeTabPane = document.querySelector(".tab-pane.show.active");
    if (activeTabPane) {
        activeTabPane.style.display = "block";
    }

    // Manage Portal tab change hide and show
    let managePortalTabs = document.querySelectorAll("#managePortalTabs a");

    // Function to activate a specific tab
    function activateManagePortalTab(tabId) {
        // Hide all tab panes
        document.querySelectorAll(".tab-pane").forEach(pane => {
            pane.classList.remove("show", "active");
            pane.style.display = "none"; // Explicitly hide all
        });

        // Remove active class from all tabs
        document.querySelectorAll("#managePortalTabs a").forEach(tab => {
            tab.classList.remove("active");
        });

        // Show the selected tab pane
        let targetPane = document.getElementById(tabId);
        if (targetPane) {
            targetPane.classList.add("show", "active");
            targetPane.style.display = "block"; // Ensure it is visible
        }

        // Add active class to the corresponding tab
        let targetTab = document.querySelector(`#managePortalTabs a[href="#${tabId}"]`);
        if (targetTab) {
            targetTab.classList.add("active");
        }
    }

    // Check URL hash on page load and activate corresponding tab
    function checkUrlHashForTabs() {
        const hash = window.location.hash.substring(1); // Remove the # symbol
        if (hash && ['user-details', 'course-details', 'social', 'reports', 'settings'].includes(hash)) {
            activateManagePortalTab(hash);
        }
    }

    // Run on page load
    checkUrlHashForTabs();

    // Listen for hash changes
    window.addEventListener('hashchange', checkUrlHashForTabs);

    managePortalTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Update URL hash
            window.location.hash = targetId;

            // Activate the tab
            activateManagePortalTab(targetId);
        });
    });

    let userManagementBtn = document.querySelector(".user-box[onclick]");
    if (userManagementBtn) {
        userManagementBtn.addEventListener("click", function () {
            window.location.href = getProjectUrl("users");
        });
    }

    // Add user button functionality - check if it's a modal button or regular link
    let addUserBtn = document.querySelector(".add-user-btn");
    if (addUserBtn) {
        // Only add redirect behavior if it's NOT a modal button
        if (!addUserBtn.hasAttribute('data-bs-toggle')) {
            addUserBtn.addEventListener("click", function () {
                window.location.href = getProjectUrl("users/create");
            });
        }
        // If it has data-bs-toggle="modal", let Bootstrap handle it
    }




    // Add user page script for hide and show tab (Bootstrap 5 compatible)
    let addUserTabs = document.querySelectorAll("#addUserTabs button[data-bs-toggle='tab']");
    let editUserTabs = document.querySelectorAll("#editUserTabs button[data-bs-toggle='tab']");

    // Handle Add User tabs
    addUserTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("data-bs-target");

            // Hide all tab panes in the add user form
            document.querySelectorAll("#addUserTabsContent .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all add user tabs
            addUserTabs.forEach(tabEl => {
                tabEl.classList.remove("active");
                tabEl.setAttribute("aria-selected", "false");
            });

            // Show the selected tab pane
            let targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to the clicked tab
            this.classList.add("active");
            this.setAttribute("aria-selected", "true");
        });
    });

    // Handle Edit User tabs
    editUserTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("data-bs-target");

            // Hide all tab panes in the edit user form
            document.querySelectorAll("#editUserTabsContent .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all edit user tabs
            editUserTabs.forEach(tabEl => {
                tabEl.classList.remove("active");
                tabEl.setAttribute("aria-selected", "false");
            });

            // Show the selected tab pane
            let targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to the clicked tab
            this.classList.add("active");
            this.setAttribute("aria-selected", "true");
        });
    });


    //VLR - Simplified Tab Management

    let mainTabs = document.querySelectorAll("#vlrTabs a"); // Main tabs
    let scormTabs = document.querySelectorAll("#scormSubTabs a"); // SCORM sub-tabs
    let nonScormTabs = document.querySelectorAll("#nonScormSubTabs a"); // Non-SCORM sub-tabs
    let documentTabs = document.querySelectorAll("#documentSubTabs a"); // Document sub-tabs
    let externalTabs = document.querySelectorAll("#externalSubTabs a"); // External Content sub-tabs
    let interactiveTabs = document.querySelectorAll("#interactiveSubTabs a"); // Interactive & AI sub-tabs

    // ✅ MAIN TABS FUNCTIONALITY ✅
    mainTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Hide all main tab panes
            document.querySelectorAll(".tab-content > .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all main tabs
            mainTabs.forEach(tab => {
                tab.classList.remove("active");
            });

            // Show the selected main tab pane
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to clicked main tab
            this.classList.add("active");

            // ✅ IF RETURNING TO SCORM, ENSURE SUB-TAB CONTENT IS SHOWN ✅
            if (targetId === "scorm") {
                let activeSubTab = document.querySelector("#scormSubTabs a.active");
                if (activeSubTab) {
                    let activeSubTabId = activeSubTab.getAttribute("href").substring(1);
                    let activeSubPane = document.getElementById(activeSubTabId);
                    if (activeSubPane) {
                        activeSubPane.classList.add("show", "active");
                        activeSubPane.style.display = "block";
                    }
                }
            }

            // ✅ IF RETURNING TO NON-SCORM, ENSURE SUB-TAB CONTENT IS SHOWN ✅
            if (targetId === "non-scorm") {
                let activeNonScormSubTab = document.querySelector("#nonScormSubTabs a.active");
                if (activeNonScormSubTab) {
                    let activeNonScormSubTabId = activeNonScormSubTab.getAttribute("href").substring(1);
                    let activeNonScormSubPane = document.getElementById(activeNonScormSubTabId);
                    if (activeNonScormSubPane) {
                        activeNonScormSubPane.classList.add("show", "active");
                        activeNonScormSubPane.style.display = "block";
                    }
                }
            }

            // ✅ IF RETURNING TO DOCUMENTS, ENSURE SUB-TAB CONTENT IS SHOWN ✅
            if (targetId === "document") {
                let activeDocSubTab = document.querySelector("#documentSubTabs a.active");
                if (activeDocSubTab) {
                    let activeDocSubTabId = activeDocSubTab.getAttribute("href").substring(1);
                    let activeDocSubPane = document.getElementById(activeDocSubTabId);
                    if (activeDocSubPane) {
                        activeDocSubPane.classList.add("show", "active");
                        activeDocSubPane.style.display = "block";
                    }
                }
            }

            // ✅ IF RETURNING TO EXTERNAL CONTENT, ENSURE SUB-TAB CONTENT IS SHOWN ✅
            if (targetId === "external") {
                let activeExtSubTab = document.querySelector("#externalSubTabs a.active");
                if (activeExtSubTab) {
                    let activeExtSubTabId = activeExtSubTab.getAttribute("href").substring(1);
                    let activeExtSubPane = document.getElementById(activeExtSubTabId);
                    if (activeExtSubPane) {
                        activeExtSubPane.classList.add("show", "active");
                        activeExtSubPane.style.display = "block";
                    }
                }
            }

            // ✅ IF RETURNING TO INTERACTIVE & AI, ENSURE SUB-TAB CONTENT IS SHOWN ✅
            if (targetId === "interactive") {
                let activeIntSubTab = document.querySelector("#interactiveSubTabs a.active");
                if (activeIntSubTab) {
                    let activeIntSubTabId = activeIntSubTab.getAttribute("href").substring(1);
                    let activeIntSubPane = document.getElementById(activeIntSubTabId);
                    if (activeIntSubPane) {
                        activeIntSubPane.classList.add("show", "active");
                        activeIntSubPane.style.display = "block";
                    }
                }
            }

        });
    });

    // ✅ SCORM SUB-TABS FUNCTIONALITY ✅
    scormTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Hide all SCORM sub-tab panes
            document.querySelectorAll("#scorm .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all SCORM sub-tabs
            scormTabs.forEach(tab => {
                tab.classList.remove("active");
            });

            // Show the selected SCORM sub-tab pane
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to clicked SCORM sub-tab
            this.classList.add("active");
        });
    });

    // ✅ NON-SCORM SUB-TABS FUNCTIONALITY ✅
    nonScormTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Hide all Non-SCORM sub-tab panes
            document.querySelectorAll("#non-scorm .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all Non-SCORM sub-tabs
            nonScormTabs.forEach(tab => {
                tab.classList.remove("active");
            });

            // Show the selected Non-SCORM sub-tab pane
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to clicked Non-SCORM sub-tab
            this.classList.add("active");
        });
    });

    // ✅ DOCUMENTS SUB-TABS FUNCTIONALITY ✅
    documentTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Hide all DOCUMENT sub-tab panes
            document.querySelectorAll("#document .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all DOCUMENT sub-tabs
            documentTabs.forEach(tab => {
                tab.classList.remove("active");
            });

            // Show the selected DOCUMENT sub-tab pane
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to clicked DOCUMENT sub-tab
            this.classList.add("active");
        });
    });

    // ✅ EXTERNAL CONTENT SUB-TABS FUNCTIONALITY ✅
    externalTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Hide all External sub-tab panes
            document.querySelectorAll("#external .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all External sub-tabs
            externalTabs.forEach(tab => {
                tab.classList.remove("active");
            });

            // Show the selected External sub-tab pane
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to clicked External sub-tab
            this.classList.add("active");
        });
    });

    // ✅ INTERACTIVE & AI POWERED CONTENT SUB-TABS FUNCTIONALITY ✅
    interactiveTabs.forEach(tab => {
        tab.addEventListener("click", function (event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Hide all Interactive sub-tab panes
            document.querySelectorAll("#interactive .tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none";
            });

            // Remove active class from all Interactive sub-tabs
            interactiveTabs.forEach(tab => {
                tab.classList.remove("active");
            });

            // Show the selected Interactive sub-tab pane
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block";
            }

            // Add active class to clicked Interactive sub-tab
            this.classList.add("active");
        });
    });

    // ✅ ENSURE FIRST SUB-TAB IS VISIBLE ON PAGE LOAD ✅
    // Simple sub-tab initialization - let Bootstrap handle the rest
    if (scormTabs.length > 0) {
        scormTabs[0].classList.add("active");
        let firstTarget = scormTabs[0].getAttribute("href").substring(1);
        let firstPane = document.getElementById(firstTarget);
        if (firstPane) {
            firstPane.classList.add("show", "active");
            firstPane.style.display = "block";
        }
    }

    // ✅ ENSURE FIRST NON-SCORM SUB-TAB IS VISIBLE ON PAGE LOAD ✅
    if (nonScormTabs.length > 0) {
        nonScormTabs[0].classList.add("active");
        let firstNonScormTarget = nonScormTabs[0].getAttribute("href").substring(1);
        let firstNonScormPane = document.getElementById(firstNonScormTarget);
        if (firstNonScormPane) {
            firstNonScormPane.classList.add("show", "active");
            firstNonScormPane.style.display = "block";
        }
    }

    // Country - State - City script
    const countrySelect = document.getElementById("countrySelect");
    const stateSelect = document.getElementById("stateSelect");
    const citySelect = document.getElementById("citySelect");
    const timezoneSelect = document.getElementById("timezoneSelect");

    // ✅ Fetch States on Country Select (only if elements exist)
    if (countrySelect && stateSelect && citySelect && timezoneSelect) {
        countrySelect.addEventListener("change", function () {
        const countryId = this.value;
        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';
        timezoneSelect.innerHTML = '<option value="">Select Timezone</option>';
        citySelect.disabled = true;
        timezoneSelect.disabled = true;

        if (countryId) {
            // ✅ Fetch States
            fetch(getApiUrl('locations/states'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `country_id=${countryId}`,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        data.forEach(state => {
                            const option = document.createElement('option');
                            option.value = state.id;
                            option.textContent = state.name;
                            stateSelect.appendChild(option);
                        });
                        stateSelect.disabled = false;
                    } else {
                        stateSelect.disabled = true;
                    }
                })
                .catch(error => console.error('Error fetching states:', error));

            // ✅ Fetch Timezones
            fetch(`index.php?controller=LocationController&action=getTimezonesByCountry`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `country_id=${countryId}`,
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.timezones.length > 0) {
                        data.timezones.forEach(tz => {
                            const option = document.createElement('option');
                            option.value = tz.zoneName;
                            option.textContent = `${tz.zoneName} (${tz.gmtOffsetName}) - ${tz.abbreviation} - ${tz.tzName}`;
                            timezoneSelect.appendChild(option);
                        });
                        timezoneSelect.disabled = false;
                    } else {
                        timezoneSelect.disabled = true;
                    }
                })
                .catch(error => console.error('Error fetching timezones:', error));
        }
    });

        // ✅ Fetch Cities on State Select
        stateSelect.addEventListener("change", function () {
            const stateId = this.value;
            citySelect.innerHTML = '<option value="">Select City</option>';
            if (stateId) {
                fetch(getApiUrl('locations/cities'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `state_id=${stateId}`,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            data.forEach(city => {
                                const option = document.createElement('option');
                                option.value = city.id;
                                option.textContent = city.name;
                                citySelect.appendChild(option);
                            });
                            citySelect.disabled = false;
                        } else {
                            citySelect.disabled = true;
                        }
                    })
                    .catch(error => console.error('Error fetching cities:', error));
            }
        });
    } // End of country-state-city script
    
    // Disable date on datepicker
    const dobInput = document.getElementById("dob");
    const profileExpiryInput = document.getElementById("profile_expiry");

    // ✅ Disable future dates for DOB
    if (dobInput) {
        const today = new Date().toISOString().split("T")[0];
        dobInput.setAttribute("max", today);
    }

    // ✅ Restrict Profile Expiry Date to be today or later
    if (profileExpiryInput) {
        const today = new Date().toISOString().split("T")[0];
        profileExpiryInput.setAttribute("min", today);
    }

    let profileIdInput = document.getElementById("profile_id");

    // ✅ Fetch Client Name from Session (Injected via PHP)
    let clientNameElement = document.getElementById("clientName");

    if (clientNameElement && profileIdInput) {
        let clientName = clientNameElement.value; // Hidden input in add_user.php

        if (clientName) {
            // ✅ Extract First 3 Letters of Client Name
            let clientPrefix = clientName.substring(0, 3).toUpperCase();

            // ✅ Generate a Unique 7-Digit Number
            let randomNumber = Math.floor(1000000 + Math.random() * 9000000);

            // ✅ Final Profile ID (Example: "DEE1234567")
            let generatedProfileId = clientPrefix + randomNumber;

            // ✅ Auto-Fill Profile ID Field (Read-Only)
            profileIdInput.value = generatedProfileId;
        }
    }

});

// ✅ Professional Delete Confirmations (moved from vlr.php)
// NOTE: These handlers are now handled by the VLR confirmations module
// which properly sends DELETE requests via POST with method override
// Removing these to prevent conflicts with the confirmation system

// The following delete functionality is now handled by:
// - VLR confirmations module (vlr_confirmations.js)
// - Confirmation modal system (confirmation_modal.js)
// - Proper DELETE route handling in controllers

// If you need to add custom delete behavior, use the confirmation system:
// confirmDelete('Item name', function() { /* custom logic */ });

