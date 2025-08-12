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
        $clientCode = trim($_POST['client_code'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        // Basic validation
        if (empty($clientCode) || empty($username) || empty($password)) {
            if ($isAjax) {
                $this->returnJsonError('Please fill in all fields.');
            } else {
                $this->returnLoginView('Please fill in all fields.');
            }
            return;
        }
        $result = $this->authModel->authenticateUser($clientCode, $username, $password);
        if ($result['valid']) {
            // Set full user session and last_activity
            $this->setUserSession($result['user']);
            // Remember last client code for convenience on timeouts
            if (!headers_sent() && !empty($clientCode)) {
                setcookie('last_client_code', $clientCode, time() + (86400 * 30), '/');
            }
            if ($isAjax) {
                $this->returnJsonSuccess('Login successful', [
                    'redirect' => UrlHelper::url('dashboard')
                ]);
            } else {
                UrlHelper::redirect('dashboard');
            }
        } else {
            if ($isAjax) {
                $this->returnJsonError($result['message']);
            } else {
                $this->returnLoginView($result['message']);
            }
        }
    }

    private function showLoginForm() {
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
        // Log logout event
        if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
        }
        
        session_destroy();
        UrlHelper::redirect('login');
    }
}
?>
