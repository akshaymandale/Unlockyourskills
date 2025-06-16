<?php
require_once 'models/ClientModel.php';
require_once 'models/UserModel.php';
require_once 'controllers/BaseController.php';

class ClientController extends BaseController {
    private $clientModel;
    private $userModel;

    public function __construct() {
        $this->clientModel = new ClientModel();
        $this->userModel = new UserModel();

        // Check if user is super admin
        if (!$this->isSuperAdmin()) {
            header('Location: index.php?error=access_denied');
            exit;
        }
    }

    /**
     * Display client management page
     */
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $search = $_GET['search'] ?? '';
        $filters = [
            'status' => $_GET['status'] ?? ''
        ];

        $clients = $this->clientModel->getAllClients($limit, $offset, $search, $filters);
        $totalClients = count($this->clientModel->getAllClients(999999, 0, $search, $filters));
        $totalPages = ceil($totalClients / $limit);

        // Make sure all variables are available to the view
        $currentPage = $page;

        include 'views/client_management.php';
    }

    /**
     * Show create client form (handled by modal in main view)
     */
    public function create() {
        // Redirect back to main page - creation handled by modal
        header('Location: index.php?controller=ClientController');
        exit;
    }

    /**
     * Store new client
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=ClientController&error=invalid_method');
            exit;
        }

        try {
            // ✅ Server-side validation following SCORM pattern
            $errors = [];

            // Validate client name
            if (empty($_POST['client_name']) || trim($_POST['client_name']) === '') {
                $errors[] = Localization::translate('validation.client_name_required');
            }

            // Validate max users
            if (empty($_POST['max_users']) || trim($_POST['max_users']) === '') {
                $errors[] = Localization::translate('validation.max_users_required');
            } elseif (!is_numeric($_POST['max_users'])) {
                $errors[] = Localization::translate('validation.max_users_numeric');
            } elseif ((int)$_POST['max_users'] < 1) {
                $errors[] = Localization::translate('validation.max_users_minimum');
            }

            // Validate admin role limit
            if (empty($_POST['admin_role_limit']) || trim($_POST['admin_role_limit']) === '') {
                $errors[] = Localization::translate('validation.admin_role_limit_required');
            } elseif (!is_numeric($_POST['admin_role_limit'])) {
                $errors[] = Localization::translate('validation.admin_role_limit_numeric');
            } elseif ((int)$_POST['admin_role_limit'] < 1) {
                $errors[] = Localization::translate('validation.admin_role_limit_minimum');
            }

            // Validate logo upload
            if (empty($_FILES['logo']['name']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
                $errors[] = 'Client logo is required.';
            } else {
                // Validate file type
                $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                $fileType = $_FILES['logo']['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = 'Logo must be PNG, JPG, or GIF format.';
                }

                // Validate file size (5MB)
                $maxSize = 5 * 1024 * 1024;
                if ($_FILES['logo']['size'] > $maxSize) {
                    $errors[] = 'Logo file size must be less than 5MB.';
                }

                // Check for upload errors
                if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Logo upload failed. Please try again.';
                }
            }

            // If there are validation errors, show toast error
            if (!empty($errors)) {
                $errorMessage = implode(' ', $errors);
                $this->toastError($errorMessage, 'index.php?controller=ClientController');
                return;
            }

            // Handle logo upload
            $logoPath = $this->handleLogoUpload($_FILES['logo']);
            if (!$logoPath) {
                $this->toastError(Localization::translate('validation.logo_upload_failed'), 'index.php?controller=ClientController');
                return;
            }

            // Validate client code uniqueness
            $clientCode = trim($_POST['client_code']);
            if (!$this->clientModel->isClientCodeUnique($clientCode)) {
                $this->toastError(Localization::translate('validation.client_code_unique'), 'index.php?controller=ClientController');
                return;
            }

            $data = [
                'client_name' => trim($_POST['client_name']),
                'client_code' => $clientCode,
                'logo_path' => $logoPath,
                'max_users' => (int)$_POST['max_users'],
                'status' => $_POST['status'] ?? 'active',
                'description' => trim($_POST['description'] ?? ''),
                'reports_enabled' => (int)($_POST['reports_enabled'] ?? 1),
                'theme_settings' => (int)($_POST['theme_settings'] ?? 1),
                'sso_enabled' => (int)($_POST['sso_enabled'] ?? 0),
                'admin_role_limit' => (int)$_POST['admin_role_limit']
            ];

            if ($this->clientModel->createClient($data)) {
                $this->toastSuccess(Localization::translate('success.client_created'), 'index.php?controller=ClientController');
            } else {
                $this->toastError('Failed to create client. Please try again.', 'index.php?controller=ClientController');
            }

        } catch (Exception $e) {
            error_log("Client creation error: " . $e->getMessage());
            $this->toastError('An unexpected error occurred. Please try again.', 'index.php?controller=ClientController');
        }
    }

    /**
     * Get client data for edit modal (AJAX request)
     */
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Client ID is required']);
                exit;
            }
            header('Location: index.php?controller=ClientController&error=client_id_required');
            exit;
        }

        $client = $this->clientModel->getClientById($id);
        if (!$client) {
            if (isset($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Client not found']);
                exit;
            }
            header('Location: index.php?controller=ClientController&error=client_not_found');
            exit;
        }

        // Return JSON data for AJAX request
        if (isset($_GET['ajax'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'client' => $client
            ]);
            exit;
        }

        // For non-AJAX requests, redirect to main page
        header('Location: index.php?controller=ClientController');
        exit;
    }

    /**
     * Update client
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=ClientController&error=invalid_method');
            exit;
        }

        try {
            $clientId = $_POST['client_id'] ?? null;
            if (!$clientId) {
                $this->toastError(Localization::translate('validation.client_id_required'), 'index.php?controller=ClientController');
                return;
            }

            // Get existing client
            $existingClient = $this->clientModel->getClientById($clientId);
            if (!$existingClient) {
                $this->toastError(Localization::translate('validation.client_not_found'), 'index.php?controller=ClientController');
                return;
            }

            // ✅ Server-side validation (similar to store method but for update)
            $errors = [];

            // Validate client name
            if (empty($_POST['client_name']) || trim($_POST['client_name']) === '') {
                $errors[] = Localization::translate('validation.client_name_required');
            }

            // Validate max users
            if (empty($_POST['max_users']) || trim($_POST['max_users']) === '') {
                $errors[] = Localization::translate('validation.max_users_required');
            } elseif (!is_numeric($_POST['max_users'])) {
                $errors[] = Localization::translate('validation.max_users_numeric');
            } elseif ((int)$_POST['max_users'] < 1) {
                $errors[] = Localization::translate('validation.max_users_minimum');
            }

            // Validate admin role limit
            if (empty($_POST['admin_role_limit']) || trim($_POST['admin_role_limit']) === '') {
                $errors[] = Localization::translate('validation.admin_role_limit_required');
            } elseif (!is_numeric($_POST['admin_role_limit'])) {
                $errors[] = Localization::translate('validation.admin_role_limit_numeric');
            } elseif ((int)$_POST['admin_role_limit'] < 1) {
                $errors[] = Localization::translate('validation.admin_role_limit_minimum');
            }

            // Handle logo upload (optional for update)
            $logoPath = $existingClient['logo_path']; // Keep existing logo by default
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Validate file type
                $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                $fileType = $_FILES['logo']['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = 'Logo must be PNG, JPG, or GIF format.';
                }

                // Validate file size (5MB)
                $maxSize = 5 * 1024 * 1024;
                if ($_FILES['logo']['size'] > $maxSize) {
                    $errors[] = 'Logo file size must be less than 5MB.';
                }

                // Check for upload errors
                if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Logo upload failed. Please try again.';
                }

                // If no errors, upload the new logo
                if (empty($errors)) {
                    $newLogoPath = $this->handleLogoUpload($_FILES['logo']);
                    if ($newLogoPath) {
                        // Delete old logo if exists
                        if ($logoPath && file_exists($logoPath)) {
                            unlink($logoPath);
                        }
                        $logoPath = $newLogoPath;
                    } else {
                        $errors[] = 'Failed to upload logo. Please try again.';
                    }
                }
            }

            // If there are validation errors, show toast error
            if (!empty($errors)) {
                $errorMessage = implode(' ', $errors);
                $this->toastError($errorMessage, 'index.php?controller=ClientController');
                return;
            }

            // Validate client code uniqueness (excluding current client)
            $clientCode = trim($_POST['client_code']);
            if (!$this->clientModel->isClientCodeUnique($clientCode, $clientId)) {
                $this->toastError(Localization::translate('validation.client_code_unique'), 'index.php?controller=ClientController');
                return;
            }

            $data = [
                'client_name' => trim($_POST['client_name']),
                'client_code' => $clientCode,
                'logo_path' => $logoPath,
                'max_users' => (int)$_POST['max_users'],
                'status' => $_POST['status'] ?? 'active',
                'description' => trim($_POST['description'] ?? ''),
                'reports_enabled' => (int)($_POST['reports_enabled'] ?? 1),
                'theme_settings' => (int)($_POST['theme_settings'] ?? 1),
                'sso_enabled' => (int)($_POST['sso_enabled'] ?? 0),
                'admin_role_limit' => (int)$_POST['admin_role_limit']
            ];

            if ($this->clientModel->updateClient($clientId, $data)) {
                $this->toastSuccess(Localization::translate('success.client_updated'), 'index.php?controller=ClientController');
            } else {
                $this->toastError('Failed to update client. Please try again.', 'index.php?controller=ClientController');
            }

        } catch (Exception $e) {
            error_log("Client update error: " . $e->getMessage());
            $this->toastError(Localization::translate('error.client_update_failed'), 'index.php?controller=ClientController');
        }
    }

    /**
     * Check if client can be deleted (AJAX endpoint)
     */
    public function canDelete() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Client ID is required']);
            exit;
        }

        try {
            $client = $this->clientModel->getClientById($id);
            if (!$client) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Client not found']);
                exit;
            }

            // Check if client has users
            $users = $this->userModel->getUsersByClient($id, 1, 0);
            if (!empty($users)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'canDelete' => false,
                    'message' => 'Cannot delete client with existing users. Please remove all users first.'
                ]);
                exit;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'canDelete' => true,
                'client' => $client
            ]);

        } catch (Exception $e) {
            error_log("Client delete check error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => 'An error occurred while checking client status']);
        }
        exit;
    }

    /**
     * Delete client
     */
    public function delete() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->toastError('Client ID is required.', 'index.php?controller=ClientController');
            return;
        }

        try {
            $client = $this->clientModel->getClientById($id);
            if (!$client) {
                $this->toastError('Client not found.', 'index.php?controller=ClientController');
                return;
            }

            // User check is done in canDelete() method before showing confirmation
            // This method is called only after user confirms deletion

            if ($this->clientModel->deleteClient($id)) {
                // Delete logo file if exists (optional for soft delete)
                // Note: For soft delete, we might want to keep the logo file
                // if ($client['logo_path'] && file_exists($client['logo_path'])) {
                //     unlink($client['logo_path']);
                // }
                $this->toastSuccess('Client deleted successfully!', 'index.php?controller=ClientController');
            } else {
                $this->toastError('Failed to delete client. Please try again.', 'index.php?controller=ClientController');
            }

        } catch (Exception $e) {
            error_log("Client deletion error: " . $e->getMessage());
            $this->toastError('An unexpected error occurred while deleting client.', 'index.php?controller=ClientController');
        }
    }

    /**
     * Handle logo upload
     */
    private function handleLogoUpload($file) {
        $uploadDir = 'uploads/logos/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            return false;
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            return false;
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'org_' . time() . '_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filepath;
        }

        return false;
    }

    /**
     * Check if current user is super admin
     */
    private function isSuperAdmin() {
        return isset($_SESSION['user']) && 
               isset($_SESSION['user']['system_role']) && 
               $_SESSION['user']['system_role'] === 'super_admin';
    }
}
?>
