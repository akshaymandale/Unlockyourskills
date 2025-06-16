<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/UserModel.php';
require_once 'models/AuthenticationModel.php';
require_once 'models/SSOModel.php';
require_once 'controllers/BaseController.php';

class LoginController extends BaseController {
    private $authModel;
    private $ssoModel;

    public function __construct() {
        $this->authModel = new AuthenticationModel();
        $this->ssoModel = new SSOModel();
    }

    // ✅ Ensure 'index' method is present
    public function index() {
        $this->login(); // Redirect to login page
    }

    public function login() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->handleLoginPost();
        } else {
            $this->showLoginForm();
        }
    }

    private function handleLoginPost() {
        // Get client IP for logging
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        // Validate input
        $clientCode = trim($_POST['client_code'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($clientCode) || empty($username) || empty($password)) {
            $this->authModel->logAuthenticationAttempt($clientCode, $username, false, 'Missing required fields', $ipAddress);
            $this->returnJsonError('All fields are required');
            return;
        }

        // Authenticate user
        $authResult = $this->authModel->authenticateUser($clientCode, $username, $password);

        if (!$authResult['valid']) {
            $this->authModel->logAuthenticationAttempt($clientCode, $username, false, $authResult['message'], $ipAddress);
            $this->returnJsonError($authResult['message']);
            return;
        }

        $user = $authResult['user'];

        // Log successful authentication
        $this->authModel->logAuthenticationAttempt($clientCode, $username, true, 'Login successful', $ipAddress);

        // Set session data
        $this->setUserSession($user);

        $this->returnJsonSuccess('Login successful', [
            'redirect' => 'index.php?controller=DashboardController&action=index'
        ]);
    }

    private function showLoginForm() {
        // Check if client code is provided for SSO check
        $clientCode = $_GET['client_code'] ?? '';
        $ssoEnabled = false;
        $ssoProviders = [];

        if (!empty($clientCode)) {
            $ssoEnabled = $this->authModel->isSSOEnabled($clientCode);
            if ($ssoEnabled) {
                $clientValidation = $this->authModel->validateClientCode($clientCode);
                if ($clientValidation['valid']) {
                    $ssoProviders = $this->authModel->getClientSSOConfig($clientValidation['client']['id']);
                }
            }
        }

        include 'views/login.php';
    }

    private function setUserSession($user) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $user['email'];
        $_SESSION['client_code'] = $user['client_code'] ?? '';
        $_SESSION['id'] = $user['id'];
        $_SESSION['lang'] = !empty($user['language']) ? $user['language'] : 'en';

        $_SESSION['user'] = [
            'id' => $user['id'],
            'profile_id' => $user['profile_id'],
            'client_id' => $user['client_id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'user_role' => $user['user_role'],
            'system_role' => $user['system_role'] ?? 'user',
            'client_name' => $user['client_name'] ?? 'Unknown Client'
        ];
    }

    private function returnJsonError($message) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }

    private function returnJsonSuccess($message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit();
    }

    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }
}
?>
