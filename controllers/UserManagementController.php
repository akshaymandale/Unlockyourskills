<?php
// controllers/UserManagementController.php
require_once 'models/UserModel.php';

class UserManagementController {
    
    public function index() {
        include 'views/user_management.php';
    }
    
    public function addUser() {
        include 'views/add_user.php';
    }

    public function storeUser() {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $userModel = new UserModel();
            $result = $userModel->insertUser($_POST, $_FILES);

            if ($result) {
                echo "<script>alert('User added successfully!'); 
                window.location.href='index.php?controller=UserManagementController';</script>";
            } else {
                echo "<script>alert('Error adding user.'); 
                window.history.back();</script>";
            }
        }
    }
    
}
?>
