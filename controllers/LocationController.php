<?php
// controllers/LocationController.php

require_once 'models/LocationModel.php';

class LocationController {
    private $locationModel;

    public function __construct() {
        $this->locationModel = new LocationModel();
    }

    public function getStatesByCountry() {
        if (isset($_POST['country_id'])) {
            $country_id = $_POST['country_id'];
            $states = $this->locationModel->fetchStatesByCountry($country_id);
            echo json_encode($states);
        }
    }

    public function getCitiesByState() {
        if (isset($_POST['state_id'])) {
            $state_id = $_POST['state_id'];
            $cities = $this->locationModel->fetchCitiesByState($state_id);
            echo json_encode($cities);
        }
    }
}
?>
