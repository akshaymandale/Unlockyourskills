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
        $externalContent = $this->VLRModel->getExternalContent();
        $documents = $this->VLRModel->getAllDocuments();
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
    
   // Delete SCROM Package
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

    // Add External content data
    
    public function addOrEditExternalContent()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Check if it's an update (edit) operation
            $isEdit = isset($_POST['id']) && !empty($_POST['id']);
            $id = $isEdit ? intval($_POST['id']) : null;
    
            // Sanitize and fetch input values
            $title = trim($_POST['title']);
            $contentType = trim($_POST['content_type']);
            $versionNumber = trim($_POST['version_number']);
            $mobileSupport = trim($_POST['mobile_support']);
            $languageSupport = trim($_POST['language_support']);
            $timeLimit = isset($_POST['time_limit']) ? intval($_POST['time_limit']) : null;
            $description = trim($_POST['description']);
            $tags = trim($_POST['tags']);
            $modifiedBy = $_SESSION['username']; // Session-based user
    
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
    
            if (empty($title)) $errors[] = "Title is required.";
            if (!in_array($contentType, ['youtube-vimeo', 'linkedin-udemy', 'web-links-blogs', 'podcasts-audio'])) {
                $errors[] = "Invalid content type.";
            }
            if (empty($versionNumber)) $errors[] = "Version number is required.";
            if (!in_array($mobileSupport, ['Yes', 'No'])) $errors[] = "Invalid mobile support value.";
            if (empty($languageSupport)) $errors[] = "Language support is required.";
            if (!empty($timeLimit) && !is_numeric($timeLimit)) $errors[] = "Time limit must be a number.";
            if (empty($tags)) $errors[] = "At least one tag is required.";
    
            // Validate content type-specific fields
            if ($contentType === "youtube-vimeo" && !$videoUrl) $errors[] = "Invalid video URL.";
            if ($contentType === "linkedin-udemy" && (!$courseUrl || empty($platformName))) $errors[] = "Course URL and Platform Name are required.";
            if ($contentType === "web-links-blogs" && (!$articleUrl || empty($author))) $errors[] = "Article URL and Author/Publisher are required.";
            if ($contentType === "podcasts-audio") {
                if ($audioSource === "url" && !$audioUrl) $errors[] = "Valid audio URL is required.";
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
                    // Save file
                    $newThumbnailName = time() . "_" . basename($thumbnailName);
                    $thumbnailUploadPath = "uploads/external/thumbnails/" . $newThumbnailName;
    
                    if (move_uploaded_file($thumbnailTmp, $thumbnailUploadPath)) {
                        $thumbnail = $newThumbnailName;
                    } else {
                        $errors[] = "Failed to upload thumbnail.";
                    }
                }
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
                    // Save file
                    $newFileName = time() . "_" . basename($fileName);
                    $uploadPath = "uploads/external/audio/" . $newFileName;
    
                    if (move_uploaded_file($fileTmp, $uploadPath)) {
                        $audioFile = $newFileName;
                    } else {
                        $errors[] = "Failed to upload audio file.";
                    }
                }
            }
    
            // If errors exist, return response
            if (!empty($errors)) {
                echo json_encode(["status" => "error", "messages" => $errors]);
                return;
            }
    
            // Prepare data for insert/update
            $data = [
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
    
            // Include uploaded files if present
            if ($audioFile) $data['audio_file'] = $audioFile;
            if ($thumbnail) $data['thumbnail'] = $thumbnail;
    
            // Insert or update the database
            if ($isEdit) {
                $result = $this->VLRModel->updateExternalContent($id, $data);
                $message = $result ? "External Content package updated successfully." : "Failed to update External Content package.";
            } else {
                $data['created_by'] = $modifiedBy;
                $result = $this->VLRModel->insertExternalContent($data);
                $message = $result ? "External Content package added successfully." : "Failed to insert External Content package.";
            }
    
            // Return JSON response
            echo json_encode(["status" => $result ? "success" : "error", "message" => $message]);
        }
    }
    
         // Delete External content data
        public function deleteExternal() {
            if (isset($_GET['id'])) {
                $id = $_GET['id'];
                $result = $this->VLRModel->deleteExternalContent($id);
        
                if ($result) {
                    echo "<script>alert('External Content deleted successfully.'); window.location.href='index.php?controller=VLRController';</script>";
                } else {
                    echo "<script>alert('Failed to delete External Content package.'); window.location.href='index.php?controller=VLRController';</script>";
                }
            }
            
        }

   // ===================== Document Management =====================

 
  

    /**
     * Add/Edit a new document
     */
    public function addOrEditDocument() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
            function processFileUpload($file, $expectedCategory, $selectedCategory, $allowedExtensions, $maxSize, $uploadDir, $existingFile) {
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
                'document_title' => $documentTitle,
                'documentCategory' => $documentCategory,
                'description' => $_POST['description'] ?? '',
                'documentTagList' => $documentTagList,
                'language' => $_POST['language'] ?? '',
                'mobile_support' => $_POST['mobile_support'] ?? 'No',
                'doc_version' => $docVersion,
                'doc_time_limit' => $_POST['doc_time_limit'] ?? '',
                'research_authors' => $_POST['research_authors'] ?? '',
                'research_publication_date' => !empty($_POST['research_publication_date']) ? $_POST['research_publication_date'] : NULL,
                'research_references' => $_POST['research_references'] ?? '',
                'created_by' => $_SESSION['username'],
                'word_excel_ppt_file' => $wordExcelPptFile,
                'ebook_manual_file' => $ebookManualFile,
                'research_file' => $researchFile
            ];
    
            // Insert or update document
            if (!empty($_POST['documentId'])) {
                $result = $this->VLRModel->updateDocument($data, $_POST['documentId']);
            } else {
                $result = $this->VLRModel->insertDocument($data);
            }
    
            echo json_encode($result);
            exit;
        }
    }
    
    

    /**
     * Delete a document
     */
  public function deleteDocument() {
    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $result = $this->VLRModel->deleteDocument($id);

        if ($result) {
            echo "<script>alert('Document deleted successfully.'); window.location.href='index.php?controller=VLRController';</script>";
        } else {
            echo "<script>alert('Failed to delete document.'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }
}

    /**
     * Fetch all languages for dropdowns
     */
    public function getLanguages() {
        return $this->VLRModel->getLanguages();
    }
   
}
?>
