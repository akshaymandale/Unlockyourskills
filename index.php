<?php
require_once 'config/autoload.php';
require_once 'config/Localization.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Preserve user session (Avoid logging out on language change)
$previousLang = $_SESSION['lang'] ?? 'en';

// ✅ Handle language selection
if (isset($_GET['lang'])) {
    $selectedLang = htmlspecialchars($_GET['lang']);
    
    // Only update language if it's different
    if ($selectedLang !== $previousLang) {
        $_SESSION['lang'] = $selectedLang;
        setcookie('lang', $selectedLang, time() + (86400 * 30), "/"); // Save language for 30 days
    }

    error_log("New Selected Language: " . $_SESSION['lang']);
    // ✅ Prevent redirect to login page
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // Remove ?lang= from URL
    exit();
}

// ✅ Set default language or use saved one
$lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';


error_log("Language file loaded for: " . $lang);
// ✅ Load language file
Localization::loadLanguage($lang);


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

