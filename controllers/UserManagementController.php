<?php
// controllers/UserManagementController.php

class UserManagementController {
    
    public function index() {
        include 'views/user_management.php';
    }
    
    public function addUser() {
        include 'views/add_user.php';
    }
    
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            include 'models/UserModel.php';
    
            $userModel = new UserModel();
            $userModel->addUser($_POST, $_FILES, 'user_profiles'); // Specify table name
    
            header('Location: index.php?controller=UserManagementController&action=index');
            exit();
        }
    }
}
?>
