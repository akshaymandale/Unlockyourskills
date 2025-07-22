<?php
require_once 'config/database.php';

class LocationModel {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function getStatesByCountry($country_id) {
        $stmt = $this->db->prepare("SELECT id, name FROM states WHERE country_id = ?");
        $stmt->execute([$country_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCitiesByState($state_id) {
        $stmt = $this->db->prepare("SELECT id, name FROM cities WHERE state_id = ?");
        $stmt->execute([$state_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ✅ Fetch Timezones by Country
  
    public function getTimezonesByCountry($country_id) {
        $stmt = $this->db->prepare("SELECT timezones FROM countries WHERE id = ?");
        $stmt->execute([$country_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result && $result['timezones']) {
            $timezones = json_decode($result['timezones'], true); // Decode JSON string
    
            $formattedTimezones = [];
            foreach ($timezones as $tz) {
                $formattedTimezones[] = [
                    "zoneName" => $tz["zoneName"],
                    "gmtOffsetName" => $tz["gmtOffsetName"],
                    "abbreviation" => $tz["abbreviation"],
                    "tzName" => $tz["tzName"]
                ];
            }
    
            return $formattedTimezones;
        }
    
        return [];
    }

}
?>
