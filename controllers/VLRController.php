<?php
// controllers/VLRController.php
require_once 'models/VLRModel.php';

class VLRController
{
    private $VLRModel;

    public function __construct()
    {
        $this->VLRModel = new VLRModel();
    }

    public function index()
    {
        $scormPackages = $this->VLRModel->getScormPackages();
        $externalContent = $this->VLRModel->getExternalContent();
        $documents = $this->VLRModel->getAllDocuments();
        $assessmentPackages = $this->VLRModel->getAllAssessments();
        $surveyPackages = $this->VLRModel->getAllSurvey();
        $feedbackPackages = $this->VLRModel->getAllFeedback();
        $audioPackages = $this->VLRModel->getAudioPackages();
        $videoPackages = $this->VLRModel->getVideoPackages();
        $imagePackages = $this->VLRModel->getImagePackages();
        $interactiveContent = $this->VLRModel->getInteractiveContent();
        $languageList = $this->VLRModel->getLanguages();
        //echo '<pre>'; print_r($audioPackages); die;
        require 'views/vlr.php';
    }

    public function getAssessmentById()
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Assessment ID is required']);
            return;
        }

        $id = intval($_GET['id']);
        $assessment = $this->VLRModel->getAssessmentByIdWithQuestions($id);

        if (!$assessment) {
            http_response_code(404);
            echo json_encode(['error' => 'Assessment not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($assessment);
    }

    public function getSurveyById()
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Survey ID is required']);
            return;
        }

        $id = intval($_GET['id']);
        $survey = $this->VLRModel->getSurveyByIdWithQuestions($id);

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
            if (!isset($_SESSION['id'])) {
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
                'created_by' => $_SESSION['id']  // Store logged-in user
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
    public function delete()
    {
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
            if (empty($languageSupport))
                $errors[] = "Language support is required.";
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
            if ($audioFile)
                $data['audio_file'] = $audioFile;
            if ($thumbnail)
                $data['thumbnail'] = $thumbnail;

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
    public function deleteExternal()
    {
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
    public function addOrEditDocument()
    {
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
                $result = $this->VLRModel->updateDocument($data, $_POST['documentId']);
                $message = $result['success'] ? "Document updated successfully." : "Failed to update document.";
            } else {
                $result = $this->VLRModel->insertDocument($data);
                $message = $result['success'] ? "Document added successfully." : "Failed to add document.";
            }

            $this->redirectWithAlert($message);
        }
    }



    /**
     * Delete a document
     */
    public function deleteDocument()
    {
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

        if (!isset($_SESSION['id'])) {
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            exit();
        }

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
            echo json_encode(['status' => 'error', 'errors' => $errors]);
            return;
        }

        // Prepare data
        $questionIds = explode(',', $selectedQuestions);
        $data = [
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
        } else {
            $result = $this->VLRModel->saveAssessmentWithQuestions($data);
        }

        if ($result) {
            $message = "Assessment saved successfully!";
            echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
        } else {
            $message = "Failed to save assessment.";
            echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }



    public function deleteAssessment()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $this->VLRModel->deleteAssessment($id);

            if ($result) {
                echo "<script>alert('Assessment deleted successfully.'); window.location.href='index.php?controller=VLRController';</script>";
            } else {
                echo "<script>alert('Failed to delete assessment.'); window.location.href='index.php?controller=VLRController';</script>";
            }
        } else {
            echo "<script>alert('Invalid request.'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }

// Add or Edit Audio Package
public function addOrEditAudioPackage()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirectWithAlert("Invalid request parameters.");
        return;
    }

    // ✅ Ensure session is valid
    if (!isset($_SESSION['id'])) {
        $this->redirectWithAlert("Unauthorized access. Please log in.");
        return;
    }

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
        $this->redirectWithAlert(implode('\n', $errors));
        return;
    }

    // ✅ Handle audio file upload (only if a new file is provided)
    $audioFileName = $existingAudio;
    if (!empty($audioFile['name'])) {
        $uploadDir = "uploads/audio/";
        $ext = pathinfo($audioFile['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("audio_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($audioFile['tmp_name'], $targetPath)) {
            $this->redirectWithAlert("Audio upload failed.");
            return;
        }

        $audioFileName = $uniqueName;
    }

    // ✅ Prepare clean data for DB
    $data = [
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

    $this->redirectWithAlert($message);
}


// Delete Audio Package
public function deleteAudioPackage()
{
    if (!isset($_GET['id'])) {
        $this->redirectWithAlert("Invalid request.");
        return;
    }

    $id = $_GET['id'];
    $success = $this->VLRModel->deleteAudioPackage($id);

    $message = $success ? "Audio package deleted successfully." : "Failed to delete Audio package.";
    $this->redirectWithAlert($message);
}



// ✅ Add or Edit Video Package
public function addOrEditVideoPackage()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirectWithAlert("Invalid request parameters.");
        return;
    }

    // ✅ Ensure session is valid
    if (!isset($_SESSION['id'])) {
        $this->redirectWithAlert("Unauthorized access. Please log in.");
        return;
    }

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
        $this->redirectWithAlert(implode('\n', $errors));
        return;
    }

    // ✅ Handle video file upload (only if a new file is provided)
    $videoFileName = $existingVideo;
    if (!empty($videoFile['name'])) {
        $uploadDir = "uploads/video/";
        $ext = pathinfo($videoFile['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("video_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($videoFile['tmp_name'], $targetPath)) {
            $this->redirectWithAlert("Video upload failed.");
            return;
        }

        $videoFileName = $uniqueName;
    }

    // ✅ Prepare clean data for DB
    $data = [
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

    $this->redirectWithAlert($message);
}

// ✅ Delete Video Package
public function deleteVideoPackage()
{
    if (!isset($_GET['id'])) {
        $this->redirectWithAlert("Invalid request.");
        return;
    }

    $id = $_GET['id'];
    $success = $this->VLRModel->deleteVideoPackage($id);

    $message = $success ? "Video package deleted successfully." : "Failed to delete Video package.";
    $this->redirectWithAlert($message);
}


// ✅ Add or Edit Image Package
public function addOrEditImagePackage()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirectWithAlert("Invalid request parameters.");
        return;
    }

    // ✅ Ensure session is valid
    if (!isset($_SESSION['id'])) {
        $this->redirectWithAlert("Unauthorized access. Please log in.");
        return;
    }

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
        $this->redirectWithAlert(implode('\n', $errors));
        return;
    }

    // ✅ Handle image file upload (only if a new file is provided)
    $imageFileName = $existingImage;
    if (!empty($imageFile['name'])) {
        $uploadDir = "uploads/image/";
        $ext = pathinfo($imageFile['name'], PATHINFO_EXTENSION);
        $uniqueName = uniqid("image_") . "." . $ext;
        $targetPath = $uploadDir . $uniqueName;

        if (!move_uploaded_file($imageFile['tmp_name'], $targetPath)) {
            $this->redirectWithAlert("Image upload failed.");
            return;
        }

        $imageFileName = $uniqueName;
    }

    // ✅ Prepare clean data for DB
    $data = [
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

    $this->redirectWithAlert($message);
}

// ✅ Delete Image Package
public function deleteImagePackage()
{
    if (!isset($_GET['id'])) {
        $this->redirectWithAlert("Invalid request.");
        return;
    }

    $id = $_GET['id'];
    $success = $this->VLRModel->deleteImagePackage($id);

    $message = $success ? "Image package deleted successfully." : "Failed to delete Image package.";
    $this->redirectWithAlert($message);
}



// 🔁 Utility function for redirecting with alert
private function redirectWithAlert($message)
{
    echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
    exit();
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
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            exit();
        }

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
            $errorMsg = implode("\\n", $errors);
            echo "<script>alert('$errorMsg'); window.history.back();</script>";
            exit();
        }

        // Prepare data for saving
        $data = [
            'title' => $title,
            'tags' => $tags,
            'created_by' => $createdBy,
            'question_ids' => $questionIds
        ];

        // Insert or update logic
        if (!empty($surveyId)) {
            $result = $this->VLRModel->updateSurveyWithQuestions($data, $surveyId);
            $message = $result ? "Survey updated successfully!" : "Failed to update survey.";
        } else {
            $result = $this->VLRModel->saveSurveyWithQuestions($data);
            $message = $result ? "Survey saved successfully!" : "Failed to save survey.";
        }

        echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
    }




    // Survey Delete
    public function deleteSurvey()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $this->VLRModel->deleteSurvey($id);

            if ($result) {
                echo "<script>alert('Survey deleted successfully.'); window.location.href='index.php?controller=VLRController';</script>";
            } else {
                echo "<script>alert('Failed to delete survey.'); window.location.href='index.php?controller=VLRController';</script>";
            }
        } else {
            echo "<script>alert('Invalid request.'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }

    // Feedback Package Methods (following survey pattern)

    public function getFeedbackById()
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Feedback ID is required']);
            return;
        }

        $id = intval($_GET['id']);
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
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            exit();
        }

        // Server-side validation
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
            $errorMsg = implode("\\n", $errors);
            echo "<script>alert('$errorMsg'); window.history.back();</script>";
            exit();
        }

        // Prepare data for saving
        $data = [
            'title' => $title,
            'tags' => $tags,
            'created_by' => $createdBy,
            'question_ids' => $questionIds
        ];

        // Insert or update logic
        if (!empty($feedbackId)) {
            $result = $this->VLRModel->updateFeedbackWithQuestions($data, $feedbackId);
            $message = $result ? "Feedback updated successfully!" : "Failed to update feedback.";
        } else {
            $result = $this->VLRModel->saveFeedbackWithQuestions($data);
            $message = $result ? "Feedback saved successfully!" : "Failed to save feedback.";
        }

        echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
    }

    // Feedback Delete
    public function deleteFeedback()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $this->VLRModel->deleteFeedback($id);

            if ($result) {
                echo "<script>alert('Feedback deleted successfully.'); window.location.href='index.php?controller=VLRController';</script>";
            } else {
                echo "<script>alert('Failed to delete feedback.'); window.location.href='index.php?controller=VLRController';</script>";
            }
        } else {
            echo "<script>alert('Invalid request.'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }

    // ✅ Interactive & AI Powered Content Methods

    public function addOrEditInteractiveContent()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $interactiveId = $_POST['interactive_id'] ?? '';

            // Handle file uploads
            $contentFile = null;
            $thumbnailImage = null;
            $metadataFile = null;

            // Handle content file upload
            if (isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                $contentFile = $this->handleInteractiveFileUpload($_FILES['content_file'], 'content');
                if (!$contentFile) {
                    $this->redirectWithAlert("Content file upload failed.");
                    return;
                }
            } else if (!empty($_POST['existing_content_file'])) {
                $contentFile = $_POST['existing_content_file'];
            }

            // Handle thumbnail image upload
            if (isset($_FILES['thumbnail_image']) && $_FILES['thumbnail_image']['error'] === UPLOAD_ERR_OK) {
                $thumbnailImage = $this->handleInteractiveFileUpload($_FILES['thumbnail_image'], 'thumbnail');
                if (!$thumbnailImage) {
                    $this->redirectWithAlert("Thumbnail image upload failed.");
                    return;
                }
            } else if (!empty($_POST['existing_thumbnail_image'])) {
                $thumbnailImage = $_POST['existing_thumbnail_image'];
            }

            // Handle metadata file upload
            if (isset($_FILES['metadata_file']) && $_FILES['metadata_file']['error'] === UPLOAD_ERR_OK) {
                $metadataFile = $this->handleInteractiveFileUpload($_FILES['metadata_file'], 'metadata');
                if (!$metadataFile) {
                    $this->redirectWithAlert("Metadata file upload failed.");
                    return;
                }
            } else if (!empty($_POST['existing_metadata_file'])) {
                $metadataFile = $_POST['existing_metadata_file'];
            }

            // Prepare data
            $data = [
                'title' => $_POST['interactive_title'],
                'content_type' => $_POST['content_type'],
                'description' => $_POST['description'] ?? '',
                'tags' => $_POST['tagList'] ?? '',
                'version' => $_POST['version'],
                'language' => $_POST['language'] ?? '',
                'time_limit' => !empty($_POST['timeLimit']) ? (int)$_POST['timeLimit'] : null,
                'mobile_support' => $_POST['interactive_mobileSupport'],
                'content_url' => $_POST['content_url'] ?? '',
                'embed_code' => $_POST['embed_code'] ?? '',
                'ai_model' => $_POST['ai_model'] ?? '',
                'interaction_type' => $_POST['interaction_type'] ?? '',
                'difficulty_level' => $_POST['difficulty_level'] ?? '',
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

            if ($interactiveId) {
                // Update existing interactive content
                $result = $this->VLRModel->updateInteractiveContent($interactiveId, $data);
                $message = $result ? "Interactive content updated successfully." : "Failed to update interactive content.";
            } else {
                // Insert new interactive content
                $result = $this->VLRModel->insertInteractiveContent($data);
                $message = $result ? "Interactive content added successfully." : "Failed to insert interactive content.";
            }

            echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
        } else {
            echo "<script>alert('Invalid request parameters.'); window.location.href='index.php?controller=VLRController';</script>";
        }
    }

    private function handleInteractiveFileUpload($file, $type)
    {
        $uploadDir = "uploads/interactive/";

        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
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

    public function deleteInteractiveContent()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $result = $this->VLRModel->deleteInteractiveContent($id);
            $message = $result ? "Interactive content deleted successfully." : "Failed to delete interactive content.";
        } else {
            $message = "Invalid request.";
        }

        echo "<script>alert('$message'); window.location.href='index.php?controller=VLRController';</script>";
    }

}
?>