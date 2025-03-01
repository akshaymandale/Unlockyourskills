<?php
// controllers/VLRController.php
require_once 'models/VLRModel.php';

class VLRController {
    private $VLRModel;

    public function __construct() {
        $this->VLRModel = new VLRModel();
    }

    public function index() {
        $scormPackages = $this->VLRModel->getScormPackages();
        require 'views/vlr.php';
    }

    public function addOrEditScormPackage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate session (ensure user is logged in)
            if (!isset($_SESSION['username'])) {
                echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
                exit();
            }
    
            $scormId = $_POST['scorm_id'] ?? null; // Hidden ID field for edit mode
    
            // Handle file upload (only required for new SCORM or if replacing)
            $zipFileName = $_POST['existing_zip'] ?? null;
            if (!empty($_FILES['zipFile']['name'])) {
                $uploadDir = "uploads/scorm/";
                $fileExtension = pathinfo($_FILES['zipFile']['name'], PATHINFO_EXTENSION);
                $uniqueFileName = uniqid('scorm_') . '.' . $fileExtension; // Generate unique file name
                $uploadFilePath = $uploadDir . $uniqueFileName;
    
                if (move_uploaded_file($_FILES['zipFile']['tmp_name'], $uploadFilePath)) {
                    $zipFileName = $uniqueFileName;
                } else {
                    echo "<script>alert('File upload failed.'); window.location.href='index.php?controller=VLRController';</script>";
                    exit();
                }
            }
    
            // Prepare data
            $data = [
                'title' => $_POST['scorm_title'],
                'zip_file' => $zipFileName,  // Use new or existing file
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tagList'] ?? '',
                'version' => $_POST['version'],
                'language' => $_POST['language'] ?? '',
                'scorm_category' => $_POST['scormCategory'],
                'time_limit' => $_POST['timeLimit'] ?? null,
                'mobile_support' => $_POST['mobileSupport'],
                'assessment' => $_POST['assessment'],
                'created_by' => $_SESSION['username']  // Store logged-in user
            ];
    
            if ($scormId) {
                // Update existing SCORM package
                $result = $this->VLRModel->updateScormPackage($scormId, $data);
                $message = $result ? "SCORM package updated successfully." : "Failed to update SCORM package.";
            } else {
                // Insert new SCORM package
                $result = $this->VLRModel->insertScormPackage($data);
                $message = $result ? "SCORM package added successfully." : "Failed to insert SCORM package.";
            }
    
            echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }
    
    public function delete() {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $this->VLRModel->deleteScormPackage($id);
    
            if ($result) {
                echo "<script>alert('SCORM package deleted successfully.'); window.location.href='index.php?controller=VLRController';</script>";
            } else {
                echo "<script>alert('Failed to delete SCORM package.'); window.location.href='index.php?controller=VLRController';</script>";
            }
        }
    }

   
}
?>
