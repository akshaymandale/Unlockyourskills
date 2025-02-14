<?php
include 'config/Database.php';

class UserModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getUser($client_code, $username) {
        $stmt = $this->conn->prepare("SELECT * FROM user_profiles WHERE client_id = ? AND email = ?");
        $stmt->execute([$client_code, $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
