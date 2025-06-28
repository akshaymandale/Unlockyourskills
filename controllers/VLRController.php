<?php
// controllers/VLRController.php
require_once 'models/VLRModel.php';
require_once 'controllers/BaseController.php';
require_once 'core/UrlHelper.php';

class VLRController extends BaseController
{
    private $VLRModel;

    public function __construct()
    {
        $this->VLRModel = new VLRModel();
    }

    public function index()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        // Determine client filtering based on user role
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            // Client admin can only see their client's content
            $filterClientId = $clientId;
        }
        // Super admin can see all content (no client filtering)

        // Debug output
        error_log("VLR Index - Client ID: " . $clientId . ", Filter Client ID: " . ($filterClientId ?? 'null') . ", User Role: " . ($currentUser['system_role'] ?? 'unknown'));

        $scormPackages = $this->VLRModel->getScormPackages($filterClientId);
        $nonScormPackages = $this->VLRModel->getNonScormPackages($filterClientId);
        $externalContent = $this->VLRModel->getExternalContent($filterClientId);
        $documents = $this->VLRModel->getAllDocuments($filterClientId);
        $assessmentPackages = $this->VLRModel->getAllAssessments($filterClientId);
        $surveyPackages = $this->VLRModel->getAllSurvey($filterClientId);
        $feedbackPackages = $this->VLRModel->getAllFeedback($filterClientId);
        $audioPackages = $this->VLRModel->getAudioPackages($filterClientId);
        $videoPackages = $this->VLRModel->getVideoPackages($filterClientId);
        $imagePackages = $this->VLRModel->getImagePackages($filterClientId);
        $assignmentPackages = $this->VLRModel->getAssignmentPackages($filterClientId);
        
        // Debug assignment packages
        error_log("VLR Index - Assignment Packages Count: " . count($assignmentPackages));
        if (!empty($assignmentPackages)) {
            error_log("VLR Index - First Assignment: " . json_encode($assignmentPackages[0]));
        }
        
        $interactiveContent = $this->VLRModel->getInteractiveContent($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        require 'views/vlr.php';
    }

    public function getAssessmentById($id = null) {
        // Use the route parameter $id, fallback to $_GET['id']
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Assessment ID is required']);
            return;
        }

        $id = intval($id);
        $assessment = $this->VLRModel->getAssessmentByIdWithQuestions($id);

        if (!$assessment) {
            http_response_code(404);
            echo json_encode(['error' => 'Assessment not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($assessment);
    }

    public function getSurveyById($id = null)
    {
        // Use the route parameter $id, fallback to $_GET['id']
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Survey ID is required']);
            return;
        }

        $id = intval($id);
        
        // Get client ID for filtering
        $clientId = $_SESSION['user']['client_id'] ?? null;
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;
        
        $survey = $this->VLRModel->getSurveyByIdWithQuestions($id, $filterClientId);

        if (!$survey) {
            http_response_code(404);
            echo json_encode(['error' => 'Survey not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($survey);
    }

    public function addOrEditScormPackage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate session (ensure user is logged in)
            if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
                $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
                return;
            }

            $clientId = $_SESSION['user']['client_id'];

            $scormId = $_POST['scorm_id'] ?? null; // Hidden ID field for edit mode

            // Handle file upload (only required for new SCORM or if replacing)
            $zipFileName = $_POST['existing_zip'] ?? null;
            if (!empty($_FILES['zipFile']['name'])) {
                $uploadDir = "uploads/scorm/";

                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                    chmod($uploadDir, 0777); // Ensure proper permissions
                }

                // Validate file upload
                if ($_FILES['zipFile']['error'] !== UPLOAD_ERR_OK) {
                    $uploadErrors = [
                        UPLOAD_ERR_INI_SIZE => 'File size exceeds server limit.',
                        UPLOAD_ERR_FORM_SIZE => 'File size exceeds form limit.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
                        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
                    ];
                    $errorMessage = $uploadErrors[$_FILES['zipFile']['error']] ?? 'Unknown upload error.';
                    $this->toastError("File upload failed: $errorMessage", '/unlockyourskills/vlr?tab=scorm');
                    return;
                }

                // Validate file type
                $fileExtension = strtolower(pathinfo($_FILES['zipFile']['name'], PATHINFO_EXTENSION));
                $allowedExtensions = ['zip', 'rar', '7z'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $this->toastError('Invalid file type. Only ZIP, RAR, and 7Z files are allowed.', '/unlockyourskills/vlr?tab=scorm');
                    return;
                }

                // Validate file size (50MB limit)
                $maxSize = 50 * 1024 * 1024; // 50MB
                if ($_FILES['zipFile']['size'] > $maxSize) {
                    $this->toastError('File size too large. Maximum size is 50MB.', '/unlockyourskills/vlr?tab=scorm');
                    return;
                }

                $uniqueFileName = uniqid('scorm_') . '.' . $fileExtension;
                $uploadFilePath = $uploadDir . $uniqueFileName;

                if (move_uploaded_file($_FILES['zipFile']['tmp_name'], $uploadFilePath)) {
                    $zipFileName = $uniqueFileName;
                } else {
                    $this->toastError('File upload failed. Please check directory permissions.', '/unlockyourskills/vlr?tab=scorm');
                    return;
                }
            }

            // Validate required fields
            $errors = [];

            if (empty($_POST['scorm_title'])) {
                $errors[] = 'SCORM title is required.';
            }
            if (empty($_POST['version'])) {
                $errors[] = 'Version is required.';
            }
            if (empty($_POST['scormCategory'])) {
                $errors[] = 'SCORM category is required.';
            }
            if (empty($_POST['mobileSupport'])) {
                $errors[] = 'Mobile support selection is required.';
            }
            if (empty($_POST['assessment'])) {
                $errors[] = 'Assessment selection is required.';
            }

            // For new SCORM packages, zip file is required
            if (!$scormId && empty($zipFileName)) {
                $errors[] = 'ZIP file is required for new SCORM packages.';
            }

            if (!empty($errors)) {
                $errorMessage = implode(', ', $errors);
                $this->toastError("Validation errors: $errorMessage", '/unlockyourskills/vlr?tab=scorm');
                return;
            }

            // Prepare data
            $data = [
                'client_id' => $clientId,
                'title' => trim($_POST['scorm_title']),
                'zip_file' => $zipFileName,  // Use new or existing file
                'description' => trim($_POST['description'] ?? ''),
                'tags' => trim($_POST['tagList'] ?? ''),
                'version' => trim($_POST['version']),
                'language' => trim($_POST['language'] ?? ''),
                'scorm_category' => trim($_POST['scormCategory']),
                'time_limit' => !empty($_POST['timeLimit']) ? intval($_POST['timeLimit']) : null,
                'mobile_support' => trim($_POST['mobileSupport']),
                'assessment' => trim($_POST['assessment']),
                'created_by' => $_SESSION['id']  // Store logged-in user
            ];

            if ($scormId) {
                // Update existing SCORM package (with client validation)
                $currentUser = $_SESSION['user'] ?? null;
                $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

                $result = $this->VLRModel->updateScormPackage($scormId, $data, $filterClientId);
                if ($result) {
                    $this->toastSuccess('SCORM package updated successfully!', '/unlockyourskills/vlr?tab=scorm');
                } else {
                    $this->toastError('Failed to update SCORM package or access denied.', '/unlockyourskills/vlr?tab=scorm');
                }
            } else {
                // Insert new SCORM package
                $result = $this->VLRModel->insertScormPackage($data);
                if ($result) {
                    $this->toastSuccess('SCORM package added successfully!', '/unlockyourskills/vlr?tab=scorm');
                } else {
                    $this->toastError('Failed to insert SCORM package.', '/unlockyourskills/vlr?tab=scorm');
                }
            }
        } else {
            $this->toastError('Invalid request parameters.', '/unlockyourskills/vlr?tab=scorm');
        }
    }

    // Delete SCROM Package
    public function delete()
    {
        // Validate session (ensure user is logged in)
        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            error_log("📦 Deleting SCORM package with ID: " . $id);

            // Determine client filtering based on user role
            $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

            $result = $this->VLRModel->deleteScormPackage($id, $filterClientId);
            error_log("✅ Delete result: " . ($result ? 'SUCCESS' : 'FAILED'));

            if ($result) {
                error_log("🎉 SCORM package deleted successfully!");
                $this->toastSuccess('SCORM package deleted successfully!', '/unlockyourskills/vlr?tab=scorm');
            } else {
                error_log("❌ Failed to delete SCORM package or access denied");
                $this->toastError('Failed to delete SCORM package or access denied.', '/unlockyourskills/vlr?tab=scorm');
            }
        } else {
            error_log("❌ No ID provided in request");
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=scorm');
        }
    }

    // Add External content data

    public function addOrEditExternalContent()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Validate session (ensure user is logged in)
            if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
                $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
                return;
            }

            $clientId = $_SESSION['user']['client_id'];

            // Check if it's an update (edit) operation
            $isEdit = isset($_POST['id']) && !empty($_POST['id']);
            $id = $isEdit ? intval($_POST['id']) : null;

            // ✅ Sanitize and fetch input values with null coalescing
            $title = trim($_POST['title'] ?? '');
            $contentType = trim($_POST['content_type'] ?? '');
            $versionNumber = trim($_POST['version_number'] ?? '');
            $mobileSupport = trim($_POST['mobile_support'] ?? '');
            $languageSupport = trim($_POST['language_support'] ?? '');
            $timeLimit = isset($_POST['time_limit']) ? intval($_POST['time_limit']) : null;
            $description = trim($_POST['description'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $modifiedBy = $_SESSION['id']; // Session-based user

            // Content type specific fields
            $videoUrl = !empty($_POST['video_url']) ? filter_var($_POST['video_url'], FILTER_VALIDATE_URL) : null;
            $thumbnail = null; // Default null, will be updated if a new file is uploaded
            $courseUrl = !empty($_POST['course_url']) ? filter_var($_POST['course_url'], FILTER_VALIDATE_URL) : null;
            $platformName = !empty($_POST['platform_name']) ? trim($_POST['platform_name']) : null;
            $articleUrl = !empty($_POST['article_url']) ? filter_var($_POST['article_url'], FILTER_VALIDATE_URL) : null;
            $author = !empty($_POST['author']) ? trim($_POST['author']) : null;
            $audioSource = !empty($_POST['audio_source']) ? trim($_POST['audio_source']) : null;
            $audioUrl = !empty($_POST['audio_url']) ? filter_var($_POST['audio_url'], FILTER_VALIDATE_URL) : null;
            $speaker = !empty($_POST['speaker']) ? trim($_POST['speaker']) : null;

            // Backend validation
            $errors = [];

            if (empty($title))
                $errors[] = "Title is required.";
            if (!in_array($contentType, ['youtube-vimeo', 'linkedin-udemy', 'web-links-blogs', 'podcasts-audio'])) {
                $errors[] = "Invalid content type.";
            }
            if (empty($versionNumber))
                $errors[] = "Version number is required.";
            if (!in_array($mobileSupport, ['Yes', 'No']))
                $errors[] = "Invalid mobile support value.";
            // ✅ Language support is optional - removed required validation
            if (!empty($timeLimit) && !is_numeric($timeLimit))
                $errors[] = "Time limit must be a number.";
            if (empty($tags))
                $errors[] = "At least one tag is required.";

            // Validate content type-specific fields
            if ($contentType === "youtube-vimeo" && !$videoUrl)
                $errors[] = "Invalid video URL.";
            if ($contentType === "linkedin-udemy" && (!$courseUrl || empty($platformName)))
                $errors[] = "Course URL and Platform Name are required.";
            if ($contentType === "web-links-blogs" && (!$articleUrl || empty($author)))
                $errors[] = "Article URL and Author/Publisher are required.";
            if ($contentType === "podcasts-audio") {
                if ($audioSource === "url" && !$audioUrl)
                    $errors[] = "Valid audio URL is required.";
                if ($audioSource === "upload" && empty($_FILES['audio_file']['name']) && !$isEdit) {
                    $errors[] = "Audio file is required.";
                }
            }

            // ✅ Thumbnail Upload Handling (NEW)
            if (isset($_FILES['thumbnail']) && !empty($_FILES['thumbnail']['name'])) {
                $allowedImageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $thumbnailName = $_FILES['thumbnail']['name'];
                $thumbnailTmp = $_FILES['thumbnail']['tmp_name'];
                $thumbnailSize = $_FILES['thumbnail']['size'];

                // Get file extension
                $thumbnailExt = strtolower(pathinfo($thumbnailName, PATHINFO_EXTENSION));

                // Validate extension and size
                if (!in_array($thumbnailExt, $allowedImageExtensions)) {
                    $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed for thumbnails.";
                } elseif ($thumbnailSize > 5 * 1024 * 1024) { // 5MB limit
                    $errors[] = "Thumbnail size should not exceed 5MB.";
                } else {
                    // ✅ Ensure upload directory exists
                    $uploadDir = "uploads/external/thumbnails/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                        chmod($uploadDir, 0777); // Ensure proper permissions
                    }

                    // Save file
                    $newThumbnailName = time() . "_" . basename($thumbnailName);
                    $thumbnailUploadPath = $uploadDir . $newThumbnailName;

                    if (move_uploaded_file($thumbnailTmp, $thumbnailUploadPath)) {
                        $thumbnail = $newThumbnailName;
                    } else {
                        $errors[] = "Failed to upload thumbnail.";
                    }
                }
            } elseif ($isEdit && !empty($_POST['existing_thumbnail'])) {
                // ✅ Preserve existing thumbnail if no new one is uploaded during edit
                $thumbnail = $_POST['existing_thumbnail'];
            }

            // ✅ Audio File Upload Handling (EXISTING)
            $audioFile = null;
            if ($audioSource === "upload" && isset($_FILES['audio_file']) && !empty($_FILES['audio_file']['name'])) {
                $allowedExtensions = ['mp3', 'wav'];
                $fileName = $_FILES['audio_file']['name'];
                $fileTmp = $_FILES['audio_file']['tmp_name'];
                $fileSize = $_FILES['audio_file']['size'];

                // Get file extension
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Check extension and size
                if (!in_array($fileExt, $allowedExtensions)) {
                    $errors[] = "Only MP3 and WAV files are allowed.";
                } elseif ($fileSize > 5 * 1024 * 1024) {
                    $errors[] = "Audio file size should not exceed 5MB.";
                } else {
                    // ✅ Ensure upload directory exists
                    $audioUploadDir = "uploads/external/audio/";
                    if (!is_dir($audioUploadDir)) {
                        mkdir($audioUploadDir, 0777, true);
                        chmod($audioUploadDir, 0777); // Ensure proper permissions
                    }

                    // Save file
                    $newFileName = time() . "_" . basename($fileName);
                    $uploadPath = $audioUploadDir . $newFileName;

                    if (move_uploaded_file($fileTmp, $uploadPath)) {
                        $audioFile = $newFileName;
                    } else {
                        $errors[] = "Failed to upload audio file.";
                    }
                }
            }

            // ✅ If errors exist, redirect with toast notification
            if (!empty($errors)) {
                $errorMessage = implode(', ', $errors);
                $this->toastError($errorMessage, '/unlockyourskills/vlr?tab=external');
                return;
            }

            // Prepare data for insert/update
            $data = [
                'client_id' => $clientId,
                'title' => $title,
                'content_type' => $contentType,
                'version_number' => $versionNumber,
                'mobile_support' => $mobileSupport,
                'language_support' => $languageSupport,
                'time_limit' => $timeLimit,
                'description' => $description,
                'tags' => $tags,
                'video_url' => $videoUrl,
                'thumbnail' => $thumbnail,
                'course_url' => $courseUrl,
                'platform_name' => $platformName,
                'article_url' => $articleUrl,
                'author' => $author,
                'audio_source' => $audioSource,
                'audio_url' => $audioUrl,
                'speaker' => $speaker,
                'updated_by' => $modifiedBy,
            ];

            // ✅ Include uploaded files if present (thumbnail already set in main array)
            if ($audioFile)
                $data['audio_file'] = $audioFile;

            // Insert or update the database
            if ($isEdit) {
                $currentUser = $_SESSION['user'] ?? null;
                $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

                $result = $this->VLRModel->updateExternalContent($id, $data, $filterClientId);
                if ($result) {
                    $this->toastSuccess('External Content package updated successfully!', '/unlockyourskills/vlr?tab=external');
                } else {
                    $this->toastError('Failed to update External Content package or access denied.', '/unlockyourskills/vlr?tab=external');
                }
            } else {
                $data['created_by'] = $modifiedBy;
                $result = $this->VLRModel->insertExternalContent($data);
                if ($result) {
                    $this->toastSuccess('External Content package added successfully!', '/unlockyourskills/vlr?tab=external');
                } else {
                    $this->toastError('Failed to insert External Content package.', '/unlockyourskills/vlr?tab=external');
                }
            }
        }
    }

    // Delete External content data
    public function deleteExternal($id = null)
    {
        // Validate session (ensure user is logged in)
        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        // Accept ID from route parameter or fallback to GET for backward compatibility
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=external');
            return;
        }

        // Determine client filtering based on user role
        $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

        $result = $this->VLRModel->deleteExternalContent($id, $filterClientId);

        if ($result) {
            $this->toastSuccess('External Content deleted successfully!', '/unlockyourskills/vlr?tab=external');
        } else {
            $this->toastError('Failed to delete External Content package or access denied.', '/unlockyourskills/vlr?tab=external');
        }
    }

    // ===================== Document Management =====================




    /**
     * Add/Edit a new document
     */
    public function addOrEditDocument()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validate session (ensure user is logged in)
            if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
                $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
                return;
            }

            $clientId = $_SESSION['user']['client_id'];
            $errors = [];

            // Server-side validation
            $documentTitle = trim($_POST['document_title'] ?? '');
            $documentCategory = trim($_POST['documentCategory'] ?? '');
            $docVersion = trim($_POST['doc_version'] ?? '');
            $documentTagList = trim($_POST['documentTagList'] ?? '');

            if ($documentTitle === '') {
                $errors['document_title'] = "Document title is required.";
            }
            if ($documentCategory === '') {
                $errors['documentCategory'] = "Document category is required.";
            }
            if ($docVersion === '') {
                $errors['doc_version'] = "Document version is required.";
            }
            if ($documentTagList === '') {
                $errors['documentTagList'] = "At least one tag is required.";
            }

            // File Upload Handling
            $allowedExtensions = ["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "epub", "mobi"];
            $maxSize = 10 * 1024 * 1024; // 10MB
            $uploadDir = "uploads/documents/";

            // ✅ Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                chmod($uploadDir, 0777); // Ensure proper permissions
            }

            function processFileUpload($file, $expectedCategory, $selectedCategory, $allowedExtensions, $maxSize, $uploadDir, $existingFile)
            {
                global $errors;

                // If no new file is uploaded, return the existing file
                if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
                    return $existingFile;
                }

                $fileName = $file['name'];
                $fileSize = $file['size'];
                $fileTmpPath = $file['tmp_name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (!in_array($fileExtension, $allowedExtensions, true)) {
                    $errors[$expectedCategory] = "Invalid file format. Allowed formats: " . implode(", ", $allowedExtensions);
                    return null;
                }
                if ($fileSize > $maxSize) {
                    $errors[$expectedCategory] = "File size should not exceed 10MB.";
                    return null;
                }

                // Generate a unique file name
                $newFileName = "document_" . time() . "_" . uniqid() . "." . $fileExtension;
                $destinationPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                    return $newFileName;
                } else {
                    $errors[$expectedCategory] = "File upload failed.";
                    return null;
                }
            }

            // Get existing file names from hidden fields
            $existingWordExcelPptFile = $_POST['existing_word_excel_ppt_file'] ?? null;
            $existingEbookManualFile = $_POST['existing_ebook_manual_file'] ?? null;
            $existingResearchFile = $_POST['existing_research_file'] ?? null;

            // Upload new files or retain existing ones
            $wordExcelPptFile = processFileUpload($_FILES['documentFileWordExcelPpt'] ?? null, 'Word/Excel/PPT Files', $documentCategory, $allowedExtensions, $maxSize, $uploadDir, $existingWordExcelPptFile);
            $ebookManualFile = processFileUpload($_FILES['documentFileEbookManual'] ?? null, 'E-Book & Manual', $documentCategory, $allowedExtensions, $maxSize, $uploadDir, $existingEbookManualFile);
            $researchFile = processFileUpload($_FILES['documentFileResearch'] ?? null, 'Research Paper & Case Studies', $documentCategory, $allowedExtensions, $maxSize, $uploadDir, $existingResearchFile);

            // If there are validation errors, return them as JSON
            if (!empty($errors)) {
                echo json_encode(["success" => false, "errors" => $errors]);
                exit;
            }

            // Prepare data for insertion/updating
            $data = [
                'client_id' => $clientId,
                'document_title' => $documentTitle,
                'documentCategory' => $documentCategory,
                'description' => $_POST['description'] ?? '',
                'documentTagList' => $documentTagList,
                'language' => !empty($_POST['language']) ? (int) $_POST['language'] : null,
                'mobile_support' => $_POST['mobile_support'] ?? 'No',
                'doc_version' => $docVersion,
                'doc_time_limit' => $_POST['doc_time_limit'] ?? '',
                'research_authors' => $_POST['research_authors'] ?? '',
                'research_publication_date' => !empty($_POST['research_publication_date']) ? $_POST['research_publication_date'] : NULL,
                'research_references' => $_POST['research_references'] ?? '',
                'created_by' => $_SESSION['id'],
                'word_excel_ppt_file' => $wordExcelPptFile,
                'ebook_manual_file' => $ebookManualFile,
                'research_file' => $researchFile
            ];

            // Insert or update document
            if (!empty($_POST['documentId'])) {
                $currentUser = $_SESSION['user'] ?? null;
                $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

                $result = $this->VLRModel->updateDocument($data, $_POST['documentId'], $filterClientId);
                $message = $result['success'] ? "Document updated successfully." : "Failed to update document or access denied.";
            } else {
                $result = $this->VLRModel->insertDocument($data);
                $message = $result['success'] ? "Document added successfully." : "Failed to add document.";
            }

            if ($result) {
                $this->toastSuccess($isEdit ? 'Document updated successfully!' : 'Document added successfully!', '/unlockyourskills/vlr?tab=document');
            } else {
                $this->toastError($isEdit ? 'Failed to update document.' : 'Failed to add document.', '/unlockyourskills/vlr?tab=document');
            }
        }
    }



    /**
     * Delete a document
     */
    public function deleteDocument($id = null)
    {
        // Validate session (ensure user is logged in)
        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        // Accept ID from route parameter or fallback to GET for backward compatibility
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=document');
            return;
        }

        // Determine client filtering based on user role
        $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

        $result = $this->VLRModel->deleteDocument($id, $filterClientId);

        if ($result) {
            $this->toastSuccess('Document deleted successfully!', '/unlockyourskills/vlr?tab=document');
        } else {
            $this->toastError('Failed to delete document or access denied.', '/unlockyourskills/vlr?tab=document');
        }
    }

    /**
     * Fetch all languages for dropdowns
     */
    public function getLanguages()
    {
        return $this->VLRModel->getLanguages();
    }


    // Assessment Package Add and Edit

    public function addOrEditAssessment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];

        // Server-side validation
        $title = trim($_POST['title'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $numAttempts = (int) ($_POST['num_attempts'] ?? 0);
        $passingPercentage = (float) ($_POST['passing_percentage'] ?? 0);
        $timeLimit = (int) ($_POST['time_limit'] ?? 0);
        $negativeMarking = $_POST['assessment_negativeMarking'] ?? 'No';
        $negativeMarkingPercentage = $_POST['negative_marking_percentage'] ?? null;
        $assessmentType = $_POST['assessment_assessmentType'] ?? 'Fixed';
        $numQuestionsToDisplay = $_POST['num_questions_to_display'] ?? null;
        $selectedQuestions = $_POST['selected_question_ids'] ?? ''; // Comma-separated string
        $assessmentId = $_POST['assessmentId'] ?? null;
        $createdBy = $_SESSION['id'];

        $errors = [];

        if (empty($title))
            $errors[] = "Assessment title is required.";
        if (empty($tags))
            $errors[] = "Tags/keywords are required.";
        if ($numAttempts <= 0)
            $errors[] = "Number of attempts must be greater than 0.";
        if ($passingPercentage < 0 || $passingPercentage > 100)
            $errors[] = "Passing percentage must be between 0 and 100.";
        if ($negativeMarking === 'Yes' && empty($negativeMarkingPercentage))
            $errors[] = "Negative marking percentage required.";
        if ($assessmentType === 'Dynamic') {
            if (empty($numQuestionsToDisplay) || !is_numeric($numQuestionsToDisplay)) {
                $errors[] = "Number of questions to display is required for dynamic assessments.";
            }
        }
        if (empty($selectedQuestions)) {
            $errors[] = "At least one question must be selected.";
        }

        if (!empty($errors)) {
            $errorMsg = implode(', ', $errors);
            $this->toastError($errorMsg, '/unlockyourskills/vlr?tab=assessment');
            return;
        }

        // Prepare data
        $questionIds = explode(',', $selectedQuestions);
        $data = [
            'client_id' => $clientId,
            'title' => $title,
            'tags' => $tags,
            'num_attempts' => $numAttempts,
            'passing_percentage' => $passingPercentage,
            'time_limit' => $timeLimit,
            'negative_marking' => $negativeMarking, // Send 'Yes' or 'No' directly to match enum
            'negative_marking_percentage' => $negativeMarking === 'Yes' ? (int) $negativeMarkingPercentage : 0,
            'assessment_type' => $assessmentType,
            'num_questions_to_display' => $assessmentType === 'Dynamic' ? (int) $numQuestionsToDisplay : null,
            'created_by' => $createdBy,
            'question_ids' => $questionIds
        ];

        // Insert or update logic
        if (!empty($assessmentId)) {
            $result = $this->VLRModel->updateAssessmentWithQuestions($data, $assessmentId);
            if ($result) {
                $this->toastSuccess('Assessment updated successfully!', '/unlockyourskills/vlr?tab=assessment');
            } else {
                $this->toastError('Failed to update assessment.', '/unlockyourskills/vlr?tab=assessment');
            }
        } else {
            $result = $this->VLRModel->saveAssessmentWithQuestions($data);
            if ($result) {
                $this->toastSuccess('Assessment saved successfully!', '/unlockyourskills/vlr?tab=assessment');
            } else {
                $this->toastError('Failed to save assessment.', '/unlockyourskills/vlr?tab=assessment');
            }
        }
    }



    public function deleteAssessment($id = null)
    {
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=assessment');
            return;
        }

        $result = $this->VLRModel->deleteAssessment($id);

        if ($result) {
            $this->toastSuccess('Assessment deleted successfully!', '/unlockyourskills/vlr?tab=assessment');
        } else {
            $this->toastError('Failed to delete assessment.', '/unlockyourskills/vlr?tab=assessment');
        }
    }

// Add or Edit Audio Package
public function addOrEditAudioPackage()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->toastError('Invalid request parameters.', '/unlockyourskills/vlr?tab=audio');
        return;
    }

    // ✅ Ensure session is valid
    if (!isset($_SESSION['id'])) {
        $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
        return;
    }

    // ✅ Get client ID from session
    $clientId = $_SESSION['user']['client_id'];

    // ✅ Extract POST and FILES data (with fallbacks for suffixed names)
    $audioId    = $_POST['audio_id'] ?? $_POST['audio_idaudio'] ?? null;
    $title      = trim($_POST['audio_title'] ?? $_POST['audio_titleaudio'] ?? '');
    $version    = $_POST['version'] ?? $_POST['versionaudio'] ?? '';
    $tags       = $_POST['tagList'] ?? $_POST['tagListaudio'] ?? '';
    $timeLimit  = trim($_POST['timeLimit'] ?? $_POST['timeLimitaudio'] ?? '');
    $audioFile  = $_FILES['audioFile'] ?? $_FILES['audioFileaudio'] ?? null;
    $existingAudio = $_POST['existing_audio'] ?? $_POST['existing_audioaudio'] ?? null;

    // ✅ Initialize error list
    $errors = [];

    // ✅ Validation
    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (!$audioId && empty($audioFile['name'])) {
        // Only required on "add"
        $errors[] = "Audio file is required.";
    } elseif (!empty($audioFile['name']) && $audioFile['size'] > 10 * 1024 * 1024) {
        $errors[] = "Audio file size cannot exceed 10MB.";
    }

    if (empty($version) || !is_numeric($version)) {
        $errors[] = "Version must be a valid number.";
    }

    if (empty($tags)) {
        $errors[] = "Tags are required.";
    }

    if ($timeLimit !== '' && !is_numeric($timeLimit)) {
        $errors[] = "Time limit must be numeric.";
    }

    // ✅ Handle validation failure
    if (!empty($errors)) {
        $errorMessage = implode(', ', $errors);
        $this->toastError($errorMessage, '/unlockyourskills/vlr?tab=audio');
        return;
    }

    // ✅ Handle audio file upload (only if a new file is provided)
    $audioFileName = $existingAudio;
    if (!empty($audioFile['name'])) {
        $uploadDir = "uploads/audio/";

        // ✅ Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $ext = pathinfo($audioFile['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("audio_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($audioFile['tmp_name'], $targetPath)) {
            $this->toastError('Audio upload failed.', '/unlockyourskills/vlr?tab=audio');
            return;
        }

        $audioFileName = $uniqueName;
    }

    // ✅ Prepare clean data for DB
    $data = [
        'client_id'      => $clientId,
        'title'          => $title,
        'audio_file'     => $audioFileName,
        'description'    => trim($_POST['description'] ?? $_POST['descriptionaudio'] ?? '') ?: null,
        'tags'           => $tags,
        'version'        => $version,
        'language'       => trim($_POST['language'] ?? $_POST['languageaudio'] ?? '') ?: null,
        'time_limit'     => $timeLimit !== '' ? $timeLimit : null,
        'mobile_support' => $_POST['mobileSupport'] ?? $_POST['mobileSupportaudio'] ?? 0,
        'created_by'     => $_SESSION['id']
    ];

    // ✅ Insert or update logic
    if ($audioId) {
        $success = $this->VLRModel->updateAudioPackage($audioId, $data);
        $message = $success ? "Audio package updated successfully." : "Failed to update Audio package.";
    } else {
        $success = $this->VLRModel->insertAudioPackage($data);
        $message = $success ? "Audio package added successfully." : "Failed to add Audio package.";
    }

    if ($success) {
        $this->toastSuccess($audioId ? 'Audio package updated successfully!' : 'Audio package added successfully!', '/unlockyourskills/vlr?tab=audio');
    } else {
        $this->toastError($audioId ? 'Failed to update audio package.' : 'Failed to add audio package.', '/unlockyourskills/vlr?tab=audio');
    }
}


// Delete Audio Package
public function deleteAudioPackage($id = null)
{
    // If no ID provided as parameter, check GET (for backward compatibility)
    if ($id === null && isset($_GET['id'])) {
        $id = $_GET['id'];
    }
    
    if (!$id) {
        $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=audio');
        return;
    }

    $success = $this->VLRModel->deleteAudioPackage($id);

    if ($success) {
        $this->toastSuccess('Audio package deleted successfully!', '/unlockyourskills/vlr?tab=audio');
    } else {
        $this->toastError('Failed to delete audio package.', '/unlockyourskills/vlr?tab=audio');
    }
}



// ✅ Add or Edit Video Package
public function addOrEditVideoPackage()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->toastError('Invalid request parameters.', '/unlockyourskills/vlr?tab=video');
        return;
    }

    // ✅ Ensure session is valid
    if (!isset($_SESSION['id'])) {
        $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
        return;
    }

    // ✅ Get client ID from session
    $clientId = $_SESSION['user']['client_id'];

    // ✅ Extract POST and FILES data (with fallbacks for suffixed names)
    $videoId     = $_POST['video_id'] ?? $_POST['video_idvideo'] ?? null;
    $title       = trim($_POST['video_title'] ?? $_POST['video_titlevideo'] ?? '');
    $version     = $_POST['version'] ?? $_POST['versionvideo'] ?? '';
    $tags        = $_POST['tagList'] ?? $_POST['tagListvideo'] ?? '';
    $timeLimit   = trim($_POST['timeLimit'] ?? $_POST['timeLimitvideo'] ?? '');
    $videoFile   = $_FILES['videoFile'] ?? $_FILES['videoFilevideo'] ?? null;
    $existingVideo = $_POST['existing_video'] ?? $_POST['existing_videovideo'] ?? null;

    // ✅ Initialize error list
    $errors = [];

    // ✅ Validation
    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (!$videoId && empty($videoFile['name'])) {
        // Only required on "add"
        $errors[] = "Video file is required.";
    } elseif (!empty($videoFile['name']) && $videoFile['size'] > 50 * 1024 * 1024) {
        $errors[] = "Video file size cannot exceed 50MB.";
    }

    if (empty($version) || !is_numeric($version)) {
        $errors[] = "Version must be a valid number.";
    }

    if (empty($tags)) {
        $errors[] = "Tags are required.";
    }

    if ($timeLimit !== '' && !is_numeric($timeLimit)) {
        $errors[] = "Time limit must be numeric.";
    }

    // ✅ Handle validation failure
    if (!empty($errors)) {
        $errorMessage = implode(', ', $errors);
        $this->toastError($errorMessage, '/unlockyourskills/vlr?tab=video');
        return;
    }

    // ✅ Handle video file upload (only if a new file is provided)
    $videoFileName = $existingVideo;
    if (!empty($videoFile['name'])) {
        $uploadDir = "uploads/video/";

        // ✅ Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $ext = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("video_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($videoFile['tmp_name'], $targetPath)) {
            $this->toastError('Video upload failed.', '/unlockyourskills/vlr?tab=video');
            return;
        }

        $videoFileName = $uniqueName;
    }

    // ✅ Prepare clean data for DB
    $data = [
        'client_id'      => $clientId,
        'title'          => $title,
        'video_file'     => $videoFileName,
        'description'    => trim($_POST['description'] ?? $_POST['descriptionvideo'] ?? '') ?: null,
        'tags'           => $tags,
        'version'        => $version,
        'language'       => trim($_POST['language'] ?? $_POST['languagevideo'] ?? '') ?: null,
        'time_limit'     => $timeLimit !== '' ? $timeLimit : null,
        'mobile_support' => $_POST['mobileSupport'] ?? $_POST['mobileSupportvideo'] ?? 0,
        'created_by'     => $_SESSION['id']
    ];

    // ✅ Insert or update logic
    if ($videoId) {
        $success = $this->VLRModel->updateVideoPackage($videoId, $data);
        $message = $success ? "Video package updated successfully." : "Failed to update Video package.";
    } else {
        $success = $this->VLRModel->insertVideoPackage($data);
        $message = $success ? "Video package added successfully." : "Failed to add Video package.";
    }

    if ($success) {
        $this->toastSuccess($videoId ? 'Video package updated successfully!' : 'Video package added successfully!', '/unlockyourskills/vlr?tab=video');
    } else {
        $this->toastError($videoId ? 'Failed to update video package.' : 'Failed to add video package.', '/unlockyourskills/vlr?tab=video');
    }
}

// ✅ Delete Video Package
public function deleteVideoPackage($id = null)
{
    // If no ID provided as parameter, check GET (for backward compatibility)
    if ($id === null && isset($_GET['id'])) {
        $id = $_GET['id'];
    }
    
    if (!$id) {
        $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=video');
        return;
    }

    $success = $this->VLRModel->deleteVideoPackage($id);

    if ($success) {
        $this->toastSuccess('Video package deleted successfully!', '/unlockyourskills/vlr?tab=video');
    } else {
        $this->toastError('Failed to delete video package.', '/unlockyourskills/vlr?tab=video');
    }
}


// ✅ Add or Edit Image Package
public function addOrEditImagePackage()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->toastError('Invalid request parameters.', '/unlockyourskills/vlr?tab=image');
        return;
    }

    // ✅ Ensure session is valid
    if (!isset($_SESSION['id'])) {
        $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
        return;
    }
    // ✅ Get client ID from session
    $clientId = $_SESSION['user']['client_id'];

    // ✅ Extract POST and FILES data (with fallbacks for suffixed names)
    $imageId     = $_POST['image_id'] ?? $_POST['image_idimage'] ?? null;
    $title       = trim($_POST['image_title'] ?? $_POST['image_titleimage'] ?? '');
    $version     = $_POST['version'] ?? $_POST['versionimage'] ?? '';
    $tags        = $_POST['tagList'] ?? $_POST['tagListimage'] ?? '';
    $imageFile   = $_FILES['imageFile'] ?? $_FILES['imageFileimage'] ?? null;
    $existingImage = $_POST['existing_image'] ?? $_POST['existing_imageimage'] ?? null;

    // ✅ Initialize error list
    $errors = [];

    // ✅ Validation
    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (!$imageId && empty($imageFile['name'])) {
        $errors[] = "Image file is required.";
    } elseif (!empty($imageFile['name']) && $imageFile['size'] > 10 * 1024 * 1024) {
        $errors[] = "Image file size cannot exceed 10MB.";
    }

    if (empty($version) || !is_numeric($version)) {
        $errors[] = "Version must be a valid number.";
    }

    if (empty($tags)) {
        $errors[] = "Tags are required.";
    }

    // ✅ Handle validation failure
    if (!empty($errors)) {
        $errorMessage = implode(', ', $errors);
        $this->toastError($errorMessage, '/unlockyourskills/vlr?tab=image');
        return;
    }

    // ✅ Handle image file upload (only if a new file is provided)
    $imageFileName = $existingImage;
    if (!empty($imageFile['name'])) {
        $uploadDir = "uploads/image/";

        // ✅ Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $ext = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("image_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
            $this->toastError('Image upload failed.', '/unlockyourskills/vlr?tab=image');
            return;
        }

        $imageFileName = $uniqueName;
    }

    // ✅ Prepare clean data for DB
    $data = [
        'client_id'      => $clientId,
        'title'          => $title,
        'image_file'     => $imageFileName,
        'description'    => trim($_POST['description'] ?? $_POST['descriptionimage'] ?? '') ?: null,
        'tags'           => $tags,
        'version'        => $version,
        'language'       => trim($_POST['language'] ?? $_POST['languageimage'] ?? '') ?: null,
        'mobile_support' => $_POST['mobileSupport'] ?? $_POST['mobileSupportimage'] ?? 0,
        'created_by'     => $_SESSION['id']
    ];

    // ✅ Insert or update logic
    if ($imageId) {
        $success = $this->VLRModel->updateImagePackage($imageId, $data);
        $message = $success ? "Image package updated successfully." : "Failed to update Image package.";
    } else {
        $success = $this->VLRModel->insertImagePackage($data);
        $message = $success ? "Image package added successfully." : "Failed to add Image package.";
    }

    if ($success) {
        $this->toastSuccess($imageId ? 'Image package updated successfully!' : 'Image package added successfully!', '/unlockyourskills/vlr?tab=image');
    } else {
        $this->toastError($imageId ? 'Failed to update image package.' : 'Failed to add image package.', '/unlockyourskills/vlr?tab=image');
    }
}

// ✅ Delete Image Package
public function deleteImagePackage($id = null)
{
    // If no ID provided as parameter, check GET (for backward compatibility)
    if ($id === null && isset($_GET['id'])) {
        $id = $_GET['id'];
    }
    
    if (!$id) {
        $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=image');
        return;
    }

    $success = $this->VLRModel->deleteImagePackage($id);

    if ($success) {
        $this->toastSuccess('Image package deleted successfully!', '/unlockyourskills/vlr?tab=image');
    } else {
        $this->toastError('Failed to delete image package.', '/unlockyourskills/vlr?tab=image');
    }
}





    // Survey Add and Edit
    public function addOrEditSurvey()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        if (!isset($_SESSION['id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        // ✅ Get client ID from session
        $clientId = $_SESSION['user']['client_id'];

        // Server-side validation
        $title = trim($_POST['title'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $questionIdsRaw = $_POST['survey_selectedQuestionIds'] ?? '';
        $surveyId = $_POST['surveyId'] ?? null;
        $createdBy = $_SESSION['id'];

        $errors = [];

        if (empty($title)) {
            $errors[] = "Survey title is required.";
        }

        if (empty($tags)) {
            $errors[] = "Tags/keywords are required.";
        }

        $questionIds = array_filter(array_map('trim', explode(',', $questionIdsRaw)));
        if (empty($questionIds)) {
            $errors[] = "At least one question must be selected.";
        }

        if (!empty($errors)) {
            $errorMsg = implode(', ', $errors);
            $this->toastError($errorMsg, '/unlockyourskills/vlr?tab=survey');
            return;
        }

        // Prepare data for saving
        $data = [
            'client_id' => $clientId,
            'title' => $title,
            'tags' => $tags,
            'created_by' => $createdBy,
            'question_ids' => $questionIds
        ];

        // Insert or update logic
        if (!empty($surveyId)) {
            $result = $this->VLRModel->updateSurveyWithQuestions($data, $surveyId);
            if ($result) {
                $this->toastSuccess('Survey updated successfully!', '/unlockyourskills/vlr?tab=survey');
            } else {
                $this->toastError('Failed to update survey.', '/unlockyourskills/vlr?tab=survey');
            }
        } else {
            $result = $this->VLRModel->saveSurveyWithQuestions($data);
            if ($result) {
                $this->toastSuccess('Survey saved successfully!', '/unlockyourskills/vlr?tab=survey');
            } else {
                $this->toastError('Failed to save survey.', '/unlockyourskills/vlr?tab=survey');
            }
        }
    }




    // Survey Delete
    public function deleteSurvey($id = null)
    {
        // If no ID provided as parameter, check GET (for backward compatibility)
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if (!$id) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=survey');
            return;
        }

        $result = $this->VLRModel->deleteSurvey($id);

        if ($result) {
            $this->toastSuccess('Survey deleted successfully!', '/unlockyourskills/vlr?tab=survey');
        } else {
            $this->toastError('Failed to delete survey.', '/unlockyourskills/vlr?tab=survey');
        }
    }

    // Feedback Package Methods (following survey pattern)

    public function getFeedbackById($id = null)
    {
        // Use the route parameter $id, fallback to $_GET['id'] for backward compatibility
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if (!$id || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Feedback ID is required']);
            return;
        }

        $id = intval($id);
        $feedback = $this->VLRModel->getFeedbackByIdWithQuestions($id);

        if (!$feedback) {
            http_response_code(404);
            echo json_encode(['error' => 'Feedback not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($feedback);
    }

    // Feedback Add and Edit
    public function addOrEditFeedback()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        if (!isset($_SESSION['id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        // Server-side validation
        // ✅ Get client ID from session
        $clientId = $_SESSION['user']['client_id'];
        $title = trim($_POST['title'] ?? '');
        $tags = trim($_POST['feedbackTagList'] ?? '');
        $questionIdsRaw = $_POST['feedback_selectedQuestionIds'] ?? '';
        $feedbackId = $_POST['feedbackId'] ?? null;
        $createdBy = $_SESSION['id'];

        $errors = [];

        if (empty($title)) {
            $errors[] = "Feedback title is required.";
        }

        if (empty($tags)) {
            $errors[] = "Tags/keywords are required.";
        }

        $questionIds = array_filter(array_map('trim', explode(',', $questionIdsRaw)));
        if (empty($questionIds)) {
            $errors[] = "At least one question must be selected.";
        }

        if (!empty($errors)) {
            $errorMsg = implode(', ', $errors);
            $this->toastError($errorMsg, '/unlockyourskills/vlr?tab=feedback');
            return;
        }

        // Prepare data for saving
        $data = [
            'client_id' => $clientId,
            'title' => $title,
            'tags' => $tags,
            'created_by' => $createdBy,
            'question_ids' => $questionIds
        ];

        // Insert or update logic
        if (!empty($feedbackId)) {
            $result = $this->VLRModel->updateFeedbackWithQuestions($data, $feedbackId);
            if ($result) {
                $this->toastSuccess('Feedback updated successfully!', '/unlockyourskills/vlr?tab=feedback');
            } else {
                $this->toastError('Failed to update feedback.', '/unlockyourskills/vlr?tab=feedback');
            }
        } else {
            $result = $this->VLRModel->saveFeedbackWithQuestions($data);
            if ($result) {
                $this->toastSuccess('Feedback saved successfully!', '/unlockyourskills/vlr?tab=feedback');
            } else {
                $this->toastError('Failed to save feedback.', '/unlockyourskills/vlr?tab=feedback');
            }
        }
    }

    // Feedback Delete
    public function deleteFeedback($id = null)
    {
        // Accept ID from route parameter or fallback to GET for backward compatibility
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id || !is_numeric($id)) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=feedback');
            return;
        }

        $id = intval($id);
        $result = $this->VLRModel->deleteFeedback($id);

        if ($result) {
            $this->toastSuccess('Feedback deleted successfully!', '/unlockyourskills/vlr?tab=feedback');
        } else {
            $this->toastError('Failed to delete feedback.', '/unlockyourskills/vlr?tab=feedback');
        }
    }

    // ✅ Interactive & AI Powered Content Methods

    public function addOrEditInteractiveContent()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Debug logging
            error_log("Interactive Content: POST request received");
            error_log("Session data: " . print_r($_SESSION, true));
            
            // Validate session (ensure user is logged in)
            if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
                error_log("Interactive Content: Session validation failed");
                $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
                return;
            }
            
            error_log("Interactive Content: Session validation passed");
            $clientId = $_SESSION['user']['client_id'];
            error_log("Interactive Content: Client ID = " . $clientId);
            
            $interactiveId = $_POST['interactive_id'] ?? '';
            error_log("Interactive Content: Interactive ID = " . $interactiveId);
            
            // Handle file uploads
            $contentFile = null;
            $thumbnailImage = null;
            $metadataFile = null;

            // Handle content file upload
            if (isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                $contentFile = $this->handleInteractiveFileUpload($_FILES['content_file'], 'content');
                if (!$contentFile) {
                    error_log("Interactive Content: Content file upload failed");
                    $this->toastError('Content file upload failed.', '/unlockyourskills/vlr?tab=interactive');
                    return;
                }
            } else if (!empty($_POST['existing_content_file'])) {
                $contentFile = $_POST['existing_content_file'];
            }

            // Handle thumbnail image upload
            if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === UPLOAD_ERR_OK) {
                $thumbnailImage = $this->handleInteractiveFileUpload($_FILES['thumbnail_image'], 'thumbnail');
                if (!$thumbnailImage) {
                    error_log("Interactive Content: Thumbnail image upload failed");
                    $this->toastError('Thumbnail image upload failed.', '/unlockyourskills/vlr?tab=interactive');
                    return;
                }
            } else if (!empty($_POST['existing_thumbnail_image'])) {
                $thumbnailImage = $_POST['existing_thumbnail_image'];
            }

            // Handle metadata file upload
            if (isset($_FILES['metadata_file']) && $_FILES['metadata_file']['error'] === UPLOAD_ERR_OK) {
                $metadataFile = $this->handleInteractiveFileUpload($_FILES['metadata_file'], 'metadata');
                if (!$metadataFile) {
                    error_log("Interactive Content: Metadata file upload failed");
                    $this->toastError('Metadata file upload failed.', '/unlockyourskills/vlr?tab=interactive');
                    return;
                }
            } else if (!empty($_POST['existing_metadata_file'])) {
                $metadataFile = $_POST['existing_metadata_file'];
            }

            // Validate version field - must be numeric
            $version = trim($_POST['version'] ?? '');
            if (empty($version)) {
                $this->toastError('Version is required.', '/unlockyourskills/vlr?tab=interactive');
                return;
            }
            
            if (!is_numeric($version)) {
                $this->toastError('Version must be a number.', '/unlockyourskills/vlr?tab=interactive');
                return;
            }

            // Prepare data
            $data = [
                'client_id' => $clientId,
                'title' => $_POST['interactive_title'],
                'content_type' => $_POST['content_type'],
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tagList'] ?? '',
                'version' => $version,
                'language' => $_POST['language'] ?? '',
                'time_limit' => !empty($_POST['timeLimit']) ? (int)$_POST['timeLimit'] : null,
                'mobile_support' => $_POST['interactive_mobileSupport'],
                'content_url' => $_POST['content_url'] ?? '',
                'embed_code' => $_POST['embed_code'] ?? '',
                'ai_model' => $_POST['ai_model'] ?? '',
                'interaction_type' => $_POST['interaction_type'] ?? '',
                'difficulty_level' => !empty($_POST['difficulty_level']) ? $_POST['difficulty_level'] : null,
                'learning_objectives' => $_POST['learning_objectives'] ?? '',
                'prerequisites' => $_POST['prerequisites'] ?? '',
                'content_file' => $contentFile,
                'thumbnail_image' => $thumbnailImage,
                'metadata_file' => $metadataFile,
                'vr_platform' => $_POST['vr_platform'] ?? '',
                'ar_platform' => $_POST['ar_platform'] ?? '',
                'device_requirements' => $_POST['device_requirements'] ?? '',
                'tutor_personality' => $_POST['tutor_personality'] ?? '',
                'response_style' => $_POST['response_style'] ?? '',
                'knowledge_domain' => $_POST['knowledge_domain'] ?? '',
                'adaptation_algorithm' => $_POST['adaptation_algorithm'] ?? '',
                'assessment_integration' => $_POST['interactive_assessment_integration'] ?? 'No',
                'progress_tracking' => $_POST['interactive_progress_tracking'] ?? 'Yes',
                'created_by' => $_SESSION['id']
            ];
            
            error_log("Interactive Content: Data prepared: " . print_r($data, true));

            if ($interactiveId) {
                // Update existing interactive content
                error_log("Interactive Content: Updating existing content with ID: " . $interactiveId);
                $result = $this->VLRModel->updateInteractiveContent($interactiveId, $data);
                if ($result) {
                    error_log("Interactive Content: Update successful");
                    $this->toastSuccess('Interactive content updated successfully!', '/unlockyourskills/vlr?tab=interactive');
                } else {
                    error_log("Interactive Content: Update failed");
                    $this->toastError('Failed to update interactive content.', '/unlockyourskills/vlr?tab=interactive');
                }
            } else {
                // Insert new interactive content
                error_log("Interactive Content: Inserting new content");
                $result = $this->VLRModel->insertInteractiveContent($data);
                if ($result) {
                    error_log("Interactive Content: Insert successful");
                    $this->toastSuccess('Interactive content added successfully!', '/unlockyourskills/vlr?tab=interactive');
                } else {
                    error_log("Interactive Content: Insert failed");
                    $this->toastError('Failed to insert interactive content.', '/unlockyourskills/vlr?tab=interactive');
                }
            }
        } else {
            error_log("Interactive Content: Invalid request method");
            $this->toastError('Invalid request parameters.', '/unlockyourskills/vlr?tab=interactive');
        }
    }

    private function handleInteractiveFileUpload($file, $type)
    {
        $uploadDir = "uploads/interactive/";

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("interactive_{$type}_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        // Validate file size (50MB limit)
        if ($file['size'] > 50 * 1024 * 1024) {
            return false;
        }

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        return $uniqueName;
    }

    public function deleteInteractiveContent($id = null)
    {
        // Validate session (ensure user is logged in)
        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        // Accept ID from route parameter or fallback to GET for backward compatibility
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=interactive');
            return;
        }

        // Determine client filtering based on user role
        $filterClientId = ($currentUser && $currentUser['system_role'] === 'admin') ? $clientId : null;

        $result = $this->VLRModel->deleteInteractiveContent($id, $filterClientId);

        if ($result) {
            $this->toastSuccess('Interactive content deleted successfully!', '/unlockyourskills/vlr?tab=interactive');
        } else {
            $this->toastError('Failed to delete interactive content or access denied.', '/unlockyourskills/vlr?tab=interactive');
        }
    }

    // ✅ Non-SCORM Package Methods

    public function addOrEditNonScormPackage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $clientId = $_SESSION['user']['client_id'];
            $nonScormId = $_POST['non_scorm_id'] ?? null;

            // Handle file uploads
            $contentPackage = $this->handleNonScormFileUpload($_FILES['content_package'] ?? null, 'package', $_POST['existing_content_package'] ?? null);
            $launchFile = $this->handleNonScormFileUpload($_FILES['launch_file'] ?? null, 'launch', $_POST['existing_launch_file'] ?? null);
            $thumbnailImage = $this->handleNonScormFileUpload($_FILES['thumbnail_image'] ?? null, 'thumbnail', $_POST['existing_thumbnail_image'] ?? null);
            $manifestFile = $this->handleNonScormFileUpload($_FILES['manifest_file'] ?? null, 'manifest', $_POST['existing_manifest_file'] ?? null);

            $data = [
                'client_id' => $clientId,
                'title' => $_POST['non_scorm_title'],
                'content_type' => $_POST['content_type'],
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tagList'] ?? '',
                'version' => $_POST['version'],
                'language' => $_POST['language'] ?? '',
                'time_limit' => !empty($_POST['timeLimit']) ? (int)$_POST['timeLimit'] : null,
                'mobile_support' => $_POST['nonscorm_mobileSupport'],
                'content_url' => $_POST['content_url'] ?? '',
                'launch_file' => $launchFile,
                'content_package' => $contentPackage,
                'thumbnail_image' => $thumbnailImage,
                'manifest_file' => $manifestFile,
                'html5_framework' => $_POST['html5_framework'] ?? '',
                'responsive_design' => $_POST['nonscorm_responsive_design'] ?? 'Yes',
                'offline_support' => $_POST['nonscorm_offline_support'] ?? 'No',
                'flash_version' => $_POST['flash_version'] ?? '',
                'flash_security' => $_POST['flash_security'] ?? 'Local',
                'unity_version' => $_POST['unity_version'] ?? '',
                'unity_platform' => $_POST['unity_platform'] ?? 'WebGL',
                'unity_compression' => $_POST['unity_compression'] ?? 'Gzip',
                'web_technologies' => $_POST['web_technologies'] ?? '',
                'browser_requirements' => $_POST['browser_requirements'] ?? '',
                'external_dependencies' => $_POST['external_dependencies'] ?? '',
                'mobile_platform' => $_POST['mobile_platform'] ?? 'Cross-Platform',
                'app_store_url' => $_POST['app_store_url'] ?? '',
                'minimum_os_version' => $_POST['minimum_os_version'] ?? '',
                'progress_tracking' => $_POST['nonscorm_progress_tracking'] ?? 'Yes',
                'assessment_integration' => $_POST['nonscorm_assessment_integration'] ?? 'No',
                'completion_criteria' => $_POST['completion_criteria'] ?? '',
                'scoring_method' => $_POST['scoring_method'] ?? 'None',
                'bandwidth_requirement' => $_POST['bandwidth_requirement'] ?? '',
                'screen_resolution' => $_POST['screen_resolution'] ?? '',
                'created_by' => $_SESSION['id']
            ];

            if ($nonScormId) {
                // Update existing non-scorm content
                $result = $this->VLRModel->updateNonScormPackage($nonScormId, $data);
                if ($result) {
                    $this->toastSuccess('Non-SCORM content updated successfully!', '/unlockyourskills/vlr?tab=non-scorm');
                } else {
                    $this->toastError('Failed to update Non-SCORM content.', '/unlockyourskills/vlr?tab=non-scorm');
                }
            } else {
                // Insert new non-scorm content
                $result = $this->VLRModel->insertNonScormPackage($data);
                if ($result) {
                    $this->toastSuccess('Non-SCORM content added successfully!', '/unlockyourskills/vlr?tab=non-scorm');
                } else {
                    $this->toastError('Failed to insert Non-SCORM content.', '/unlockyourskills/vlr?tab=non-scorm');
                }
            }
        } else {
            $this->toastError('Invalid request parameters.', '/unlockyourskills/vlr?tab=non-scorm');
        }
    }

    private function handleNonScormFileUpload($file, $type, $existingFile = null)
    {
        // If no new file is uploaded, return the existing file
        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return $existingFile;
        }

        $uploadDir = "uploads/non_scorm/";

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("nonscorm_{$type}_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        // Validate file size (100MB limit for packages, 10MB for others)
        $maxSize = ($type === 'package') ? 100 * 1024 * 1024 : 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return false;
        }

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        return $uniqueName;
    }

    public function deleteNonScormPackage($id = null)
    {
        // If no ID provided as parameter, check GET (for backward compatibility)
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if (!$id) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=non-scorm');
            return;
        }

        $result = $this->VLRModel->deleteNonScormPackage($id);
        if ($result) {
            $this->toastSuccess('Non-SCORM content deleted successfully!', '/unlockyourskills/vlr?tab=non-scorm');
        } else {
            $this->toastError('Failed to delete Non-SCORM content.', '/unlockyourskills/vlr?tab=non-scorm');
        }
    }

    public function scormIndex()
    {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/vlr?tab=login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;

        // Determine client filtering based on user role
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }

        // Only fetch SCORM packages for this tab
        $scormPackages = $this->VLRModel->getScormPackages($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'scorm';
        require 'views/vlr.php';
    }

    public function audioIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $audioPackages = $this->VLRModel->getAudioPackages($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'audio';
        require 'views/vlr.php';
    }

    public function videoIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $videoPackages = $this->VLRModel->getVideoPackages($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'video';
        require 'views/vlr.php';
    }

    public function imageIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $imagePackages = $this->VLRModel->getImagePackages($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'image';
        require 'views/vlr.php';
    }

    public function externalIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $externalContent = $this->VLRModel->getExternalContent($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'external';
        require 'views/vlr.php';
    }

    public function documentsIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $documents = $this->VLRModel->getAllDocuments($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'documents';
        require 'views/vlr.php';
    }

    public function interactiveIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $interactiveContent = $this->VLRModel->getInteractiveContent($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'interactive';
        require 'views/vlr.php';
    }

    public function assessmentIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $assessmentPackages = $this->VLRModel->getAllAssessments($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'assessment';
        require 'views/vlr.php';
    }

    public function surveyIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $surveyPackages = $this->VLRModel->getAllSurvey($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'survey';
        require 'views/vlr.php';
    }

    public function feedbackIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $feedbackPackages = $this->VLRModel->getAllFeedback($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'feedback';
        require 'views/vlr.php';
    }

    public function nonScormIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $nonScormPackages = $this->VLRModel->getNonScormPackages($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'non-scorm';
        require 'views/vlr.php';
    }

    public function assignmentIndex()
    {
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }
        $clientId = $_SESSION['user']['client_id'];
        $currentUser = $_SESSION['user'] ?? null;
        $filterClientId = null;
        if ($currentUser && $currentUser['system_role'] === 'admin') {
            $filterClientId = $clientId;
        }
        $assignmentPackages = $this->VLRModel->getAssignmentPackages($filterClientId);
        $languageList = $this->VLRModel->getLanguages();
        $activeTab = 'assignment';
        require 'views/vlr.php';
    }

    // Add or Edit Assignment Package
    public function addOrEditAssignmentPackage()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request parameters.', '/unlockyourskills/vlr?tab=assignment');
            return;
        }

        // ✅ Ensure session is valid
        if (!isset($_SESSION['id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        // ✅ Get client ID from session
        $clientId = $_SESSION['user']['client_id'];

        // ✅ Extract POST and FILES data (with fallbacks for suffixed names)
        $assignmentId    = $_POST['assignment_id'] ?? $_POST['assignment_idassignment'] ?? null;
        $title          = trim($_POST['assignment_title'] ?? $_POST['assignment_titleassignment'] ?? '');
        $version        = $_POST['version'] ?? $_POST['versionassignment'] ?? '';
        $tags           = $_POST['tagList'] ?? $_POST['tagListassignment'] ?? '';
        $timeLimit      = trim($_POST['timeLimit'] ?? $_POST['timeLimitassignment'] ?? '');
        $assignmentFile = $_FILES['assignmentFile'] ?? $_FILES['assignmentFileassignment'] ?? null;
        $existingAssignment = $_POST['existing_assignment'] ?? $_POST['existing_assignmentassignment'] ?? null;

        // ✅ Initialize error list
        $errors = [];

        // ✅ Validation
        if (empty($title)) {
            $errors[] = "Title is required.";
        }

        if (!$assignmentId && empty($assignmentFile['name'])) {
            // Only required on "add"
            $errors[] = "Assignment file is required.";
        } elseif (!empty($assignmentFile['name']) && $assignmentFile['size'] > 50 * 1024 * 1024) {
            $errors[] = "Assignment file size cannot exceed 50MB.";
        }

        if (empty($version) || !is_numeric($version)) {
            $errors[] = "Version must be a valid number.";
        }

        if (empty($tags)) {
            $errors[] = "Tags are required.";
        }

        if ($timeLimit !== '' && !is_numeric($timeLimit)) {
            $errors[] = "Time limit must be numeric.";
        }

        // ✅ Handle validation failure
        if (!empty($errors)) {
            $errorMessage = implode(', ', $errors);
            $this->toastError($errorMessage, '/unlockyourskills/vlr?tab=assignment');
            return;
        }

        // ✅ Handle assignment file upload (only if a new file is provided)
        $assignmentFileName = $existingAssignment;
        if (!empty($assignmentFile['name'])) {
            $uploadDir = "uploads/assignments/";

            // ✅ Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
                chmod($uploadDir, 0777); // Ensure proper permissions
            }

            $ext = pathinfo($assignmentFile['name'], PATHINFO_EXTENSION);
            $uniqueName = uniqid("assignment_") . "." . $ext;
            $targetPath = $uploadDir . $uniqueName;

            if (!move_uploaded_file($assignmentFile['tmp_name'], $targetPath)) {
                $this->toastError('Assignment upload failed.', '/unlockyourskills/vlr?tab=assignment');
                return;
            }

            $assignmentFileName = $uniqueName;
        }

        // ✅ Prepare clean data for DB
        $data = [
            'client_id'      => $clientId,
            'title'          => $title,
            'assignment_file' => $assignmentFileName,
            'description'    => trim($_POST['description'] ?? $_POST['descriptionassignment'] ?? '') ?: null,
            'tags'           => $tags,
            'version'        => $version,
            'language'       => trim($_POST['language'] ?? $_POST['languageassignment'] ?? '') ?: null,
            'time_limit'     => $timeLimit !== '' ? $timeLimit : null,
            'mobile_support' => $_POST['mobileSupport'] ?? $_POST['mobileSupportassignment'] ?? 0,
            'assignment_type' => $_POST['assignmentType'] ?? $_POST['assignmentTypeassignment'] ?? 'individual',
            'difficulty_level' => $_POST['difficultyLevel'] ?? $_POST['difficultyLevelassignment'] ?? 'Beginner',
            'estimated_duration' => trim($_POST['estimatedDuration'] ?? $_POST['estimatedDurationassignment'] ?? '') ?: null,
            'max_attempts'   => trim($_POST['maxAttempts'] ?? $_POST['maxAttemptsassignment'] ?? '1') ?: 1,
            'passing_score'  => trim($_POST['passingScore'] ?? $_POST['passingScoreassignment'] ?? '') ?: null,
            'submission_format' => $_POST['submissionFormat'] ?? $_POST['submissionFormatassignment'] ?? 'file_upload',
            'allow_late_submission' => $_POST['allowLateSubmission'] ?? $_POST['allowLateSubmissionassignment'] ?? 'No',
            'late_submission_penalty' => trim($_POST['lateSubmissionPenalty'] ?? $_POST['lateSubmissionPenaltyassignment'] ?? '0') ?: 0,
            'instructions'   => trim($_POST['instructions'] ?? $_POST['instructionsassignment'] ?? '') ?: null,
            'requirements'   => trim($_POST['requirements'] ?? $_POST['requirementsassignment'] ?? '') ?: null,
            'rubric'         => trim($_POST['rubric'] ?? $_POST['rubricassignment'] ?? '') ?: null,
            'learning_objectives' => trim($_POST['learningObjectives'] ?? $_POST['learningObjectivesassignment'] ?? '') ?: null,
            'prerequisites'  => trim($_POST['prerequisites'] ?? $_POST['prerequisitesassignment'] ?? '') ?: null,
            'created_by'     => $_SESSION['id']
        ];

        // ✅ Insert or update logic
        if ($assignmentId) {
            $success = $this->VLRModel->updateAssignmentPackage($assignmentId, $data);
            $message = $success ? "Assignment package updated successfully." : "Failed to update Assignment package.";
        } else {
            $success = $this->VLRModel->insertAssignmentPackage($data);
            $message = $success ? "Assignment package added successfully." : "Failed to add Assignment package.";
        }

        if ($success) {
            $this->toastSuccess($assignmentId ? 'Assignment package updated successfully!' : 'Assignment package added successfully!', '/unlockyourskills/vlr?tab=assignment');
        } else {
            $this->toastError($assignmentId ? 'Failed to update assignment package.' : 'Failed to add assignment package.', '/unlockyourskills/vlr?tab=assignment');
        }
    }

    // Delete Assignment Package
    public function deleteAssignmentPackage($id = null)
    {
        // If no ID provided as parameter, check GET (for backward compatibility)
        if ($id === null && isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        
        if (!$id) {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr?tab=assignment');
            return;
        }

        $success = $this->VLRModel->deleteAssignmentPackage($id);

        if ($success) {
            $this->toastSuccess('Assignment package deleted successfully!', '/unlockyourskills/vlr?tab=assignment');
        } else {
            $this->toastError('Failed to delete assignment package.', '/unlockyourskills/vlr?tab=assignment');
        }
    }

}