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

    $('.filter-multiselect').select2({
        placeholder: "Filter by...",
        allowClear: true
    });
    
    let userTabs = document.querySelectorAll(".add-user-tabs .tab");
    if (userTabs) {
        userTabs.forEach(tab => {
            tab.addEventListener("click", function () {
                document.querySelectorAll(".tab-content").forEach(content => content.classList.remove("active"));
                document.querySelectorAll(".add-user-tabs .tab").forEach(tab => tab.classList.remove("active"));
                
                this.classList.add("active");
                let targetContent = document.querySelector(this.getAttribute("data-target"));
                if (targetContent) {
                    targetContent.classList.add("active");
                }
            });
        });
    }
});
