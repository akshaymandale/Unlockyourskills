<?php
require_once 'models/NavbarModel.php';

class NavbarController {
    private $navbarModel;

    public function __construct() {
        $this->navbarModel = new NavbarModel();
    }

    public function getNavbarData() {
        if (!isset($_SESSION)) {
            session_start();
        }

        $userId = $_SESSION['id'] ?? null;
        return [
            'languages' => $this->navbarModel->getLanguages(),
            'userLanguage' => $userId ? $this->navbarModel->getUserLanguage($userId) : null
        ];
    }
}
?>
