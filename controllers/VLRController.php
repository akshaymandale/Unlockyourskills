<?php
// controllers/VLRController.php
require_once 'models/VLRModel.php';

class VLRController {
    private $VLRModel;

    public function __construct() {
        $this->VLRModel = new VLRModel();
    }

    public function index() {
        require 'views/vlr.php';
    }

    public function addScormPackage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => $_POST['scorm_title'],
                'zip_file' => $_FILES['zipFile']['name'],  // Handle file upload separately
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tagList'] ?? '',
                'version' => $_POST['version'],
                'language' => $_POST['language'] ?? '',
                'scorm_category' => $_POST['scormCategory'],
                'time_limit' => $_POST['timeLimit'] ?? null,
                'mobile_support' => $_POST['mobileSupport'],
                'assessment' => $_POST['assessment']
            ];

            // Handle file upload
            if (!empty($_FILES['zipFile']['name'])) {
                $uploadDir = "uploads/scorm/";
                $uploadFile = $uploadDir . basename($_FILES['zipFile']['name']);
                move_uploaded_file($_FILES['zipFile']['tmp_name'], $uploadFile);
            }

            $response = $this->VLRModel->insertScormPackage($data);
            echo json_encode($response);
        }
    }
}
?>