<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'models/UserModel.php';

class LoginController {
    
    // ✅ Ensure 'index' method is present
    public function index() {
        $this->login(); // Redirect to login page
    }

    public function login() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $client_code = $_POST['client_code'];
            $username = $_POST['username'];
            $password = $_POST['password'];

            $userModel = new UserModel();
            $user = $userModel->getUser($client_code, $username);
            //echo '<pre>'; print_r($user); die;

            if ($user) {
                // ✅ Compare plain text password directly
                if ($password === $user['current_password']) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['client_code'] = $client_code;
                    $_SESSION['id'] = $user['id'];
                    // ✅ Set language with fallback to 'en' if user language is empty
                    $_SESSION['lang'] = !empty($user['language']) ? $user['language'] : 'en';

                    // ✅ Set complete user data for multi-tenant access control
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'client_id' => $user['client_id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'user_role' => $user['user_role'],
                        'system_role' => $user['system_role'] ?? 'user', // Default to 'user' if not set
                        'client_name' => $user['client_name'] ?? 'Unknown Client'
                    ];

                    //print_r($_SESSION); die;

                    header('Location: index.php?controller=DashboardController&action=index');
                    exit();
                } else {
                    echo "<script>alert('Invalid credentials! Password incorrect.'); window.location.href='index.php';</script>";
                }
            } else {
                echo "<script>alert('Invalid credentials! User not found.'); window.location.href='index.php';</script>";
            }
        } else {
            include 'views/login.php';
        }
    }

    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }
}
?>
