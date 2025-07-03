<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'models/UserModel.php';
require_once 'models/AuthenticationModel.php';
require_once 'models/SSOModel.php';
require_once 'controllers/BaseController.php';
require_once 'core/UrlHelper.php';

class LoginController extends BaseController {
    private $authModel;
    private $ssoModel;

    public function __construct() {
        $this->authModel = new AuthenticationModel();
        $this->ssoModel = new SSOModel();
    }

    // ✅ Ensure 'index' method is present
    public function index() {
        error_log('[LOGIN CONTROLLER] index() called');
        $this->login(); // Redirect to login page
    }

    public function login() {
        error_log('[LOGIN CONTROLLER] login() called, REQUEST_METHOD: ' . (
            $_SERVER['REQUEST_METHOD'] ?? ''));
            //echo "index".$_SERVER["REQUEST_METHOD"];die;
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            error_log('[LOGIN CONTROLLER] Detected POST, calling handleLoginPost');
            $this->handleLoginPost();
        } else {
            error_log('[LOGIN CONTROLLER] Detected GET, calling showLoginForm');
            $this->showLoginForm();
        }
    }

    private function handleLoginPost() {
        error_log('[LOGIN CONTROLLER] handleLoginPost() called, POST: ' . json_encode($_POST));
        error_log('[LOGIN DEBUG] REQUEST_METHOD: ' . ($_SERVER['REQUEST_METHOD'] ?? ''));
        error_log('[LOGIN DEBUG] HTTP_X_REQUESTED_WITH: ' . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));
        error_log('[LOGIN DEBUG] POST: ' . json_encode($_POST));
        $clientCode = trim($_POST['client_code'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        error_log('[LOGIN CONTROLLER] isAjax: ' . ($isAjax ? 'true' : 'false'));
        // Basic validation
        if (empty($clientCode) || empty($username) || empty($password)) {
            error_log('[LOGIN CONTROLLER] Validation failed');
            if ($isAjax) {
                $this->returnJsonError('Please fill in all fields.');
            } else {
                $this->returnLoginView('Please fill in all fields.');
            }
            return;
        }
        $result = $this->authModel->authenticateUser($clientCode, $username, $password);
        error_log('[LOGIN CONTROLLER] Auth result: ' . print_r($result, true));
        if ($result['valid']) {
            error_log('[LOGIN CONTROLLER] Login valid, setting session');
            $_SESSION['id'] = $result['user']['id'];
            $_SESSION['user'] = $result['user'];
            error_log('[LOGIN CONTROLLER] After setting session: session_id=' . session_id() . ', $_SESSION=' . print_r($_SESSION, true));
            if ($isAjax) {
                error_log('[LOGIN CONTROLLER] Returning JSON success with redirect: ' . UrlHelper::url('dashboard'));
                $this->returnJsonSuccess('Login successful', [
                    'redirect' => UrlHelper::url('dashboard')
                ]);
            } else {
                error_log('[LOGIN CONTROLLER] Redirecting to dashboard (non-AJAX)');
                UrlHelper::redirect('dashboard');
            }
        } else {
            error_log('[LOGIN CONTROLLER] Login failed: ' . $result['message']);
            if ($isAjax) {
                $this->returnJsonError($result['message']);
            } else {
                $this->returnLoginView($result['message']);
            }
        }
    }

    private function showLoginForm() {
        error_log('[LOGIN CONTROLLER] showLoginForm() called, GET: ' . json_encode($_GET));
        // Check if client code is provided for SSO check
        $clientCode = $_GET['client_code'] ?? '';
        $ssoEnabled = false;
        $ssoProviders = [];
        
        // Check for timeout message
        $timeoutMessage = '';
        if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
            $timeoutMessage = 'Your session has expired due to inactivity. Please log in again.';
        }

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
        error_log('[LOGIN CONTROLLER] login.php included');
    }

    private function setUserSession($user) {
        // $_SESSION['loggedin'] = true; // No longer needed
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
            'client_name' => $user['client_name'] ?? 'Unknown Client',
            'profile_picture' => $user['profile_picture'] ?? '',
        ];
        
        // Set initial session activity timestamp
        $_SESSION['last_activity'] = time();
        error_log('[LOGIN CONTROLLER] After setting session: session_id=' . session_id() . ', $_SESSION=' . print_r($_SESSION, true));
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
        error_log('[LOGIN CONTROLLER] logout() called');
        // Log logout event
        if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
            error_log("User logout: " . json_encode([
                'user_id' => $_SESSION['id'],
                'client_id' => $_SESSION['user']['client_id'] ?? null,
                'logout_time' => time()
            ]));
        }
        
        session_destroy();
        UrlHelper::redirect('login');
    }
}
?>
