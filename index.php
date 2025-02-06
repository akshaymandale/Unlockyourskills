<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$controller = isset($_GET['controller']) ? $_GET['controller'] : 'LoginController';
$action = isset($_GET['action']) ? $_GET['action'] : 'index';
$controllerFile = "controllers/$controller.php";

if (file_exists($controllerFile)) {
    require_once $controllerFile;
} else {
    die("Controller file '$controllerFile' not found.");
}

if (class_exists($controller)) {
    $controllerInstance = new $controller();
    
    if (method_exists($controllerInstance, $action)) {
        $controllerInstance->$action();
    } else {
        die("Method '$action' not found in '$controller'.");
    }
} else {
    die("Controller class '$controller' not found.");
}
?>
