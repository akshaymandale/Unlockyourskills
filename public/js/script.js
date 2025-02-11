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

const countrySelect = document.getElementById("country");
const stateSelect = document.getElementById("state");
const citySelect = document.getElementById("city");

countrySelect.addEventListener("change", function () {
    const countryId = this.value;
    stateSelect.disabled = true;
    citySelect.disabled = true;
    citySelect.innerHTML = '<option value="">Select City</option>';

    if (countryId) {
        fetch("index.php?controller=LocationController&action=getStatesByCountry", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `country_id=${countryId}`,
        })
            .then((response) => response.json())
            .then((states) => {
                let options = '<option value="">Select State</option>';
                states.forEach((state) => {
                    options += `<option value="${state.id}">${state.name}</option>`;
                });
                stateSelect.innerHTML = options;
                stateSelect.disabled = false;
            });
    } else {
        stateSelect.innerHTML = '<option value="">Select State</option>';
        stateSelect.disabled = true;
    }
});

stateSelect.addEventListener("change", function () {
    const stateId = this.value;
    citySelect.disabled = true;

    if (stateId) {
        fetch("index.php?controller=LocationController&action=getCitiesByState", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `state_id=${stateId}`,
        })
            .then((response) => response.json())
            .then((cities) => {
                let options = '<option value="">Select City</option>';
                cities.forEach((city) => {
                    options += `<option value="${city.id}">${city.name}</option>`;
                });
                citySelect.innerHTML = options;
                citySelect.disabled = false;
            });
    } else {
        citySelect.innerHTML = '<option value="">Select City</option>';
        citySelect.disabled = true;
    }
});

});
