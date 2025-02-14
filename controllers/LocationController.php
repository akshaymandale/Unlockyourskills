<?php
require_once 'models/LocationModel.php';

class LocationController {
    
    public function getStatesByCountry() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['country_id'])) {
            $country_id = $_POST['country_id'];
            $locationModel = new LocationModel();
            $states = $locationModel->getStatesByCountry($country_id);

            header('Content-Type: application/json');
            echo json_encode($states);
        }
    }

    public function getCitiesByState() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['state_id'])) {
            $state_id = $_POST['state_id'];
            $locationModel = new LocationModel();
            $cities = $locationModel->getCitiesByState($state_id);

            header('Content-Type: application/json');
            echo json_encode($cities);
        }
    }

    // ✅ Fetch Timezones based on Country
    public function getTimezonesByCountry() {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['country_id'])) {
            $country_id = $_POST['country_id'];
    
            $locationModel = new LocationModel();
            $timezones = $locationModel->getTimezonesByCountry($country_id);
    
            header('Content-Type: application/json');
            echo json_encode(["success" => true, "timezones" => $timezones]);
        }
    }
}
?>
