// public/js/script.js

document.addEventListener("DOMContentLoaded", function() {

    let sidebarToggle = document.getElementById("sidebarToggle");
    let sidebar = document.getElementById("sidebar");
    let profileToggle = document.getElementById("profileToggle");
    let profileDropdown = document.getElementById("profileDropdown");

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function() {
            sidebar.classList.toggle("collapsed");
            document.body.classList.toggle("sidebar-collapsed");
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

    let managePortalTabs = document.querySelectorAll("#managePortalTabs a");
    if (managePortalTabs) {
        managePortalTabs.forEach(tab => {
            tab.addEventListener("click", function (e) {
                e.preventDefault();
                document.querySelectorAll(".tab-pane").forEach(pane => pane.style.display = "none");
                let targetPane = document.querySelector(this.getAttribute("href"));
                if (targetPane) {
                    targetPane.style.display = "block";
                    targetPane.classList.add("show", "active");
                }
            });
        });
    }

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
});
