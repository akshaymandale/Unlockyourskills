<?php
require_once 'config/Database.php';

class NavbarModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();

        if (!$this->conn) {
            die("❌ Database connection failed in NavbarModel.");
        }
    }

    // ✅ Get All Languages
    public function getLanguages() {
        try {
            if (!$this->conn) {
                die("❌ Database connection is NULL in getLanguages().");
            }
            $stmt = $this->conn->prepare("SELECT language_name, language_code FROM languages ORDER BY language_name");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Database Query Error: " . $e->getMessage());
        }
    }

    // ✅ Get User's Preferred Language
    public function getUserLanguage($userId) {
        try {
            if (!$this->conn) {
                die("❌ Database connection is NULL in getUserLanguage().");
            }
            $stmt = $this->conn->prepare("SELECT language FROM user_profiles WHERE id = ?");
            $stmt->execute([$userId]);
            $language = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($language) {
                $stmt = $this->conn->prepare("SELECT language_name, language_code FROM languages WHERE language_code = ?");
                $stmt->execute([$language['language']]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
            return null;
        } catch (PDOException $e) {
            die("Database Query Error: " . $e->getMessage());
        }
    }
}
?>
