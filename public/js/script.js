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

});
