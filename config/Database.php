<?php
// Only enable error reporting if not in API context
if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

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
                    // Try different connection methods for XAMPP
                    $dsn_options = [
                        "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                        "mysql:host=" . $this->host . ";port=3306;dbname=" . $this->db_name,
                        "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=" . $this->db_name
                    ];

                    $connected = false;
                    foreach ($dsn_options as $dsn) {
                        try {
                            $this->conn = new PDO($dsn, $this->username, $this->password);
                            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                            $connected = true;
                            break;
                        } catch (PDOException $e) {
                            // Try next option
                            continue;
                        }
                    }

                    if (!$connected) {
                        throw new PDOException("Could not connect to database with any method");
                    }
                } catch (PDOException $exception) {
                    die("Database Connection Error: " . $exception->getMessage());
                }
            }
            return $this->conn;
        }
    }
}
?>
