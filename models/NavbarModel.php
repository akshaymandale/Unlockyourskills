<?php
require_once 'config/Database.php';

class NavbarModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    public function getLanguages() {
        $stmt = $this->conn->prepare("SELECT language_name, language_code FROM languages ORDER BY language_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
