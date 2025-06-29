<?php
require_once 'config/autoload.php';
require_once 'config/Localization.php';

// Load routing infrastructure
require_once 'core/Router.php';
require_once 'core/Route.php';
require_once 'core/Middleware.php';
require_once 'core/Request.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set session cookie path to match the application path
session_set_cookie_params([
    'path' => '/Unlockyourskills',
    'httponly' => true,
    'secure' => false, // Set to true if using HTTPS
    'samesite' => 'Lax'
]);

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

// ✅ Set default language or use saved one with validation
$lang = $_SESSION['lang'] ?? $_COOKIE['lang'] ?? 'en';

// ✅ Validate and sanitize language code
$lang = trim($lang);
if (empty($lang) || !preg_match('/^[a-z]{2}$/', $lang)) {
    $lang = 'en'; // Fallback to English
    $_SESSION['lang'] = $lang; // Update session with valid language
}

error_log("Language file loaded for: " . $lang);
// ✅ Load language file
Localization::loadLanguage($lang);


// ===================================
// ROUTING SYSTEM
// ===================================

// Check if this is a legacy URL (backward compatibility)
if (isset($_GET['controller']) || isset($_POST['controller'])) {
    // LEGACY ROUTING SYSTEM - Maintain backward compatibility
    $controller = $_POST['controller'] ?? $_GET['controller'] ?? 'LoginController';
    $action = $_POST['action'] ?? $_GET['action'] ?? 'index';
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
} else {
    // NEW ROUTING SYSTEM
    try {
        // Load routes
        require_once 'routes/web.php';

        // Initialize and dispatch router
        $router = new Router();
        $router->dispatch();

    } catch (Exception $e) {
        // Fallback to login page on error
        error_log("Routing error: " . $e->getMessage());

        // If it's an AJAX request, return JSON error
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Routing error occurred',
                'redirect' => '/login'
            ]);
            exit();
        }

        // Regular request - redirect to login
        require_once 'core/UrlHelper.php';
        UrlHelper::redirect('login');
    }
}


?>

