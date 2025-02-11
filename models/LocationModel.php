<?php
// models/LocationModel.php

require_once 'config/Database.php';

class LocationModel {
    private $db;

    public function __construct() {
        $database = new Database(); // Create a Database object
        $this->db = $database->connect(); // Use `connect()` instead of `getConnection()`
    }

    public function fetchStatesByCountry($country_id) {
        $stmt = $this->db->prepare("SELECT id, name FROM states WHERE country_id = ?");
        $stmt->execute([$country_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchCitiesByState($state_id) {
        $stmt = $this->db->prepare("SELECT id, name FROM cities WHERE state_id = ?");
        $stmt->execute([$state_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
