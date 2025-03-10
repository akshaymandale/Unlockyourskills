<?php
require_once 'config/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Load Navbar (Before other controllers)
require_once 'controllers/NavbarController.php'; // Include NavbarController
$navbarController = new NavbarController();
$languages = $navbarController->showNavbar(); // This will include navbar.php

//require 'views/includes/navbar.php';

// Get the controller and action from the request
$controller = isset($_GET['controller']) ? $_GET['controller'] : 'LoginController';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$controllerFile = "controllers/$controller.php";

// Load the controller file if it exists
if (file_exists($controllerFile)) {
    require_once $controllerFile;
} else {
    die("Controller file '$controllerFile' not found.");
}

// Check if the controller class exists
if (class_exists($controller)) {
    $controllerInstance = new $controller();

    // Handle LocationController actions (for dynamic country, state, city)
    if ($controller === 'LocationController') {
        if ($action === 'getStatesByCountry' || $action === 'getCitiesByState') {
            header('Content-Type: application/json');
            $controllerInstance->$action();
            exit;
        }
    }

    // Execute the requested action
    if (method_exists($controllerInstance, $action)) {
        $controllerInstance->$action();
    } else {
        die("Method '$action' not found in '$controller'.");
    }
} else {
    die("Controller class '$controller' not found.");
}


//user management grid table details 

if ($controller == "UserManagementController" && $action == "index") {
    require_once 'controllers/UserManagementController.php';
    $userController = new UserManagementController();
    $userController->index();
    exit;
}

?>

