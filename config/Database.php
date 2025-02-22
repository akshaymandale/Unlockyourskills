<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Prevent multiple declarations using class_exists
if (!class_exists('Database')) {
    class Database {
        private $host = "localhost";
        private $db_name = "unlockyourskills";
        private $username = "root";  // Change if using different MySQL credentials
        private $password = "";  // Change if using a MySQL password
        public $conn;

        public function connect() {
            if ($this->conn === null) {
                try {
                    $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                } catch (PDOException $exception) {
                    die("Database Connection Error: " . $exception->getMessage());
                }
            }
            return $this->conn;
        }
    }
}
?>
