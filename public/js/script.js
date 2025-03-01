// public/js/script.js

document.addEventListener("DOMContentLoaded", function() {

    let profileToggle = document.getElementById("profileToggle");
    let profileDropdown = document.getElementById("profileDropdown");
    
    let sidebar = document.getElementById("sidebar");
    let container = document.querySelector(".container");




    // ✅ Adjust on page load
    adjustContainerWidth();

    function adjustContainerWidth() {
        if (sidebar.classList.contains("collapsed")) {
            container.style.marginLeft = "80px";
            container.style.maxWidth = "calc(100% - 80px)";
        } else {
            container.style.marginLeft = "250px";
            container.style.maxWidth = "calc(100% - 250px)";
        }
    }

    // ✅ Adjust container margin based on sidebar state on page load
    if (sidebar.classList.contains("collapsed")) {
        container.style.marginLeft = "80px"; // Sidebar is collapsed
    } else {
        container.style.marginLeft = "250px"; // Sidebar is expanded
    }

    // ✅ Toggle Sidebar on Button Click
    let sidebarToggle = document.getElementById("sidebarToggle");
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("collapsed");

            if (sidebar.classList.contains("collapsed")) {
                container.style.marginLeft = "80px"; // Adjusted for collapsed sidebar
            } else {
                container.style.marginLeft = "250px"; // Adjusted for expanded sidebar
            }

            adjustContainerWidth();
        });
    }

    if (profileToggle) {
        profileToggle.addEventListener("click", function(event) {
            event.stopPropagation();
            profileDropdown.style.display = profileDropdown.style.display === "block" ? "none" : "block";
        });
    }

    document.addEventListener("click", function(event) {
        if (profileDropdown && profileDropdown.style.display === "block") {
            if (!profileToggle.contains(event.target) && !profileDropdown.contains(event.target)) {
                profileDropdown.style.display = "none";
            }
        }
    });

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

    managePortalTabs.forEach(tab => {
        tab.addEventListener("click", function(event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

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
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block"; // Ensure it is visible
            }

            // Add active class to the clicked tab
            this.classList.add("active");
        });
    });



    let userManagementBtn = document.querySelector(".user-box[onclick]");
    if (userManagementBtn) {
        userManagementBtn.addEventListener("click", function() {
            window.location.href = "index.php?controller=UserManagementController";
        });
    }

    let addUserBtn = document.querySelector(".add-user-btn");
    if (addUserBtn) {
        addUserBtn.addEventListener("click", function() {
            window.location.href = "index.php?controller=UserManagementController&action=addUser";
        });
    }




// Add user page script for hide and show tab
    let addUserTabs = document.querySelectorAll("#addUserTabs a");

    addUserTabs.forEach(tab => {
        tab.addEventListener("click", function(event) {
            event.preventDefault();
            const targetId = this.getAttribute("href").substring(1);

            // Hide all tab panes
            document.querySelectorAll(".tab-pane").forEach(pane => {
                pane.classList.remove("show", "active");
                pane.style.display = "none"; // Explicitly hide all
            });

            // Remove active class from all tabs
            document.querySelectorAll("#addUserTabs a").forEach(tab => {
                tab.classList.remove("active");
            });

            // Show the selected tab pane
            let targetPane = document.getElementById(targetId);
            if (targetPane) {
                targetPane.classList.add("show", "active");
                targetPane.style.display = "block"; // Ensure it is visible
            }

            // Add active class to the clicked tab
            this.classList.add("active");
        });
    });


    //VLR 
    
    let mainTabs = document.querySelectorAll("#vlrTabs a"); // Main tabs
    let scormTabs = document.querySelectorAll("#scormSubTabs a"); // SCORM sub-tabs
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
    if (scormTabs.length > 0) {
        scormTabs[0].classList.add("active");
        let firstTarget = scormTabs[0].getAttribute("href").substring(1);
        let firstPane = document.getElementById(firstTarget);
        if (firstPane) {
            firstPane.classList.add("show", "active");
            firstPane.style.display = "block";
        }
    }

    
  
    
// Country - State - City script

const countrySelect = document.getElementById("countrySelect");
const stateSelect = document.getElementById("stateSelect");
const citySelect = document.getElementById("citySelect");
const timezoneSelect = document.getElementById("timezoneSelect");

// ✅ Fetch States on Country Select
countrySelect.addEventListener("change", function() {
    const countryId = this.value;
    stateSelect.innerHTML = '<option value="">Select State</option>';
    citySelect.innerHTML = '<option value="">Select City</option>';
    timezoneSelect.innerHTML = '<option value="">Select Timezone</option>';
    citySelect.disabled = true;
    timezoneSelect.disabled = true;

    if (countryId) {
        // ✅ Fetch States
        fetch(`index.php?controller=LocationController&action=getStatesByCountry`, {
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
stateSelect.addEventListener("change", function() {
    const stateId = this.value;
    citySelect.innerHTML = '<option value="">Select City</option>';
    if (stateId) {
        fetch(`index.php?controller=LocationController&action=getCitiesByState`, {
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
            let clientName = document.getElementById("clientName").value; // Hidden input in add_user.php
        
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

          
            


});





