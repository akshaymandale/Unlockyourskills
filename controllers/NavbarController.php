
<?php
require_once 'models/NavbarModel.php';


class NavbarController {
    private $navbarModel;

    public function __construct() {
        $this->navbarModel = new NavbarModel();
    }

    public function showNavbar() {
        return $languages = $this->navbarModel->getLanguages();
    }
}
?>
