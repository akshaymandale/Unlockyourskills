<?php
// views/includes/sidebar.php
?>

<aside class="sidebar" id="sidebar">
    <ul class="list-group">
        <li class="list-group-item">
            <a href="index.php?controller=DashboardController">
                <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
            </a>
        </li>
        <li class="list-group-item">
            <a href="index.php?controller=ManagePortalController&action=index">
                 <i class="fas fa-cogs"></i> <span>Manage Portal</span>
            </a>
        </li>
        
        <li class="list-group-item">
            <a href="#">
                <i class="fas fa-book"></i> <span>My Courses</span>
            </a>
        </li>
        <li class="list-group-item">
            <a href="#">
                <i class="fas fa-search"></i> <span>Search Courses</span>
            </a>
        </li>
    </ul>
</aside>

<!-- Ensure FontAwesome is included in header.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
