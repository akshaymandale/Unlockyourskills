<?php
require_once 'models/QuestionModel.php';
require_once 'controllers/BaseController.php';

class QuestionController extends BaseController {
    private $questionModel;

    public function __construct() {
        $this->questionModel = new QuestionModel();
    }

    public function index() {
        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];

        // ✅ Don't load initial data - let JavaScript handle it via AJAX
        // This prevents duplicate data rendering issues
        $questions = []; // Empty array for initial page load
        $totalQuestions = 0;
        $totalPages = 0;
        $page = 1;

        // Get unique values for filter dropdowns (client-specific)
        $uniqueQuestionTypes = $this->questionModel->getUniqueQuestionTypes($clientId);
        $uniqueDifficultyLevels = $this->questionModel->getUniqueDifficultyLevels($clientId);

        require 'views/add_assessment.php';
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
                $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
                return;
            }

            $clientId = $_SESSION['user']['client_id'];

            $errors = [];

            // Collect and sanitize input
            $questionText = trim($_POST['questionText'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $skills = trim($_POST['skills'] ?? '');
            $level = $_POST['level'] ?? 'Low';
            $marks = intval($_POST['marks'] ?? 1);
            $status = $_POST['status'] ?? 'Active';
            $questionType = $_POST['questionFormType'] ?? 'Objective';
            $answerCount = intval($_POST['answerCount'] ?? 0);
            $mediaType = $_POST['questionMediaType'] ?? 'text';
            $createdBy = $_SESSION['id'] ?? 'Unknown';

            // Validations
            if (empty($questionText)) $errors[] = "Question text is required.";
            if (empty($tags)) $errors[] = "Tags are required.";
            if (!in_array($level, ['Low', 'Medium', 'Hard'])) $errors[] = "Invalid level.";
            if (!in_array($status, ['Active', 'Inactive'])) $errors[] = "Invalid status.";
            if (!in_array($questionType, ['Objective', 'Subjective'])) $errors[] = "Invalid question type.";
            if (!in_array($mediaType, ['text', 'image', 'audio', 'video'])) $errors[] = "Invalid media type.";

            $mediaFilePath = null;

            // Handle file upload
            if ($mediaType !== 'text' && isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === 0) {
                $targetDir = __DIR__ . "/../uploads/media/";
                
                // Debug logging
                error_log("QuestionController: Uploading file for media type: " . $mediaType);
                error_log("QuestionController: Target directory: " . $targetDir);
                error_log("QuestionController: File info: " . print_r($_FILES['mediaFile'], true));
                
                if (!is_dir($targetDir)) {
                    error_log("QuestionController: Creating directory: " . $targetDir);
                    mkdir($targetDir, 0777, true);
                    chmod($targetDir, 0777); // Ensure proper permissions
                }
                
                $fileName = basename($_FILES["mediaFile"]["name"]);
                $mediaFilePath = $targetDir . time() . "_" . $fileName;
                
                error_log("QuestionController: Full file path: " . $mediaFilePath);

                if (!move_uploaded_file($_FILES["mediaFile"]["tmp_name"], $mediaFilePath)) {
                    $uploadError = error_get_last();
                    error_log("QuestionController: File upload failed. Error: " . print_r($uploadError, true));
                    $errors[] = "Media file upload failed. Error: " . ($uploadError['message'] ?? 'Unknown error');
                } else {
                    error_log("QuestionController: File uploaded successfully to: " . $mediaFilePath);
                    // Store relative path in database for web access
                    $mediaFilePath = "uploads/media/" . time() . "_" . $fileName;
                    error_log("QuestionController: Database path: " . $mediaFilePath);
                }
            } else {
                error_log("QuestionController: File upload conditions not met. Media type: " . $mediaType . ", Files: " . print_r($_FILES, true));
            }

            if ($questionType === 'Objective') {
                $options = $_POST['options'] ?? [];
                if (count($options) < 1) {
                    $errors[] = "At least one option is required.";
                }
            }

            if (empty($errors)) {
                $questionId = $this->questionModel->insertQuestion([
                    'client_id' => $clientId,
                    'question_text' => $questionText,
                    'tags' => $tags,
                    'competency_skills' => $skills,
                    'level' => $level,
                    'marks' => $marks,
                    'status' => $status,
                    'question_type' => $questionType,
                    'answer_count' => $answerCount,
                    'media_type' => $mediaType,
                    'media_file' => $mediaFilePath,
                    'created_by' => $createdBy
                ]);

                if ($questionType === 'Objective' && $questionId && isset($options)) {
                    foreach ($options as $index => $option) {
                        $optionText = trim($option['text']);
                        $isCorrect = isset($option['correct']) ? 1 : 0;

                        if (!empty($optionText)) {
                            $this->questionModel->insertOption([
                                'client_id' => $clientId,
                                'question_id' => $questionId,
                                'option_index' => $index,
                                'option_text' => $optionText,
                                'is_correct' => $isCorrect
                            ]);
                        }
                    }
                }

                if ($questionId) {
                    $this->toastSuccess('Question added successfully!', '/unlockyourskills/vlr/questions');
                } else {
                    $this->toastError('Failed to insert question.', '/unlockyourskills/vlr/questions');
                }
                return;
            } else {
                $errorMsg = implode(', ', $errors);
                $this->toastError("Error(s): $errorMsg", '/unlockyourskills/vlr/questions');
                return;
            }
        }

        require 'views/add_assessment.php';
    }

    public function edit() {
        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            $this->toastError('Unauthorized access. Please log in.', 'index.php?controller=LoginController');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];
    
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $questionId = intval($_POST['question_id'] ?? 0);
            $errors = [];
    
            $questionText = trim($_POST['questionText'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $skills = trim($_POST['skills'] ?? '');
            $level = $_POST['level'] ?? 'Low';
            $marks = intval($_POST['marks'] ?? 1);
            $status = $_POST['status'] ?? 'Active';
            $questionType = $_POST['questionFormType'] ?? 'Objective';
            $answerCount = intval($_POST['answerCount'] ?? 0);
            $mediaType = $_POST['questionMediaType'] ?? 'text';
    
            if (empty($questionText)) $errors[] = "Question text is required.";
            if (empty($tags)) $errors[] = "Tags are required.";
            if (!in_array($level, ['Low', 'Medium', 'Hard'])) $errors[] = "Invalid level.";
            if (!in_array($status, ['Active', 'Inactive'])) $errors[] = "Invalid status.";
            if (!in_array($questionType, ['Objective', 'Subjective'])) $errors[] = "Invalid question type.";
            if (!in_array($mediaType, ['text', 'image', 'audio', 'video'])) $errors[] = "Invalid media type.";
    
            $mediaFilePath = $_POST['existing_media_file'] ?? null;
    
            if ($mediaType !== 'text' && isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === 0) {
                $targetDir = __DIR__ . "/../uploads/media/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                    chmod($targetDir, 0777); // Ensure proper permissions
                }
                $fileName = basename($_FILES["mediaFile"]["name"]);
                $mediaFilePath = $targetDir . time() . "_" . $fileName;
    
                if (!move_uploaded_file($_FILES["mediaFile"]["tmp_name"], $mediaFilePath)) {
                    $errors[] = "Media file upload failed.";
                } else {
                    // Store relative path in database for web access
                    $mediaFilePath = "uploads/media/" . time() . "_" . $fileName;
                }
            } else if ($mediaType !== 'text' && !$mediaFilePath) {
                // If media type is not text but no file uploaded and no existing file, it's an error
                $errors[] = "Media file is required for non-text media types.";
            }
    
            if ($questionType === 'Objective') {
                $options = $_POST['options'] ?? [];
                if (count($options) < 1) {
                    $errors[] = "At least one option is required.";
                }
            }
    
            if (empty($errors)) {
                $updateResult = $this->questionModel->updateQuestion($questionId, [
                    'question_text' => $questionText,
                    'tags' => $tags,
                    'competency_skills' => $skills,
                    'level' => $level,
                    'marks' => $marks,
                    'status' => $status,
                    'question_type' => $questionType,
                    'answer_count' => $answerCount,
                    'media_type' => $mediaType,
                    'media_file' => $mediaFilePath,
                ]);
    
                if ($questionType === 'Objective') {
                    $this->questionModel->deleteOptionsByQuestionId($questionId);
                    foreach ($options as $index => $option) {
                        $optionText = trim($option['text']);
                        $isCorrect = isset($option['correct']) ? 1 : 0;
    
                        if (!empty($optionText)) {
                            $this->questionModel->updateOption([
                                'client_id' => $clientId,
                                'question_id' => $questionId,
                                'option_index' => $index,
                                'option_text' => $optionText,
                                'is_correct' => $isCorrect
                            ]);
                        }
                    }
                }
    
                if ($updateResult) {
                    $this->toastSuccess('Question updated successfully!', '/unlockyourskills/vlr/questions');
                } else {
                    $this->toastError('Failed to update question.', '/unlockyourskills/vlr/questions');
                }
                return;
            } else {
                $errorMsg = implode(', ', $errors);
                $this->toastError("Error(s): $errorMsg", '/unlockyourskills/vlr/questions');
                return;
            }
        }
        // Handle initial page load (GET)
        else if (isset($_GET['id'])) {
            $questionId = intval($_GET['id']);
            $question = $this->questionModel->getQuestionById($questionId, $clientId);
            $options = $this->questionModel->getOptionsByQuestionId($questionId, $clientId);
    
            if ($question) {
                // Reuse add_assessment.php for editing
                $isEdit = true;
                require 'views/add_assessment.php';
            } else {
                $this->toastError('Question not found.', '/unlockyourskills/vlr/questions');
                return;
            }
        } else {
            $this->toastError('Invalid request.', '/unlockyourskills/vlr/questions');
            return;
        }
    }
    
    public function delete($id = null) {
        error_log("[Assessment Delete] Route param id: " . var_export($id, true));
        error_log("[Assessment Delete] _GET id: " . var_export($_GET['id'] ?? null, true));
        $questionId = $id ?? ($_GET['id'] ?? null);
        error_log("[Assessment Delete] Final questionId: " . var_export($questionId, true));
        if ($questionId) {
            $questionId = intval($questionId);
            $success = $this->questionModel->softDeleteQuestion($questionId);
            error_log("[Assessment Delete] softDeleteQuestion result for ID $questionId: " . var_export($success, true));
            if ($success) {
                error_log("[Assessment Delete] Question deleted successfully: $questionId");
                $this->toastSuccess('Question deleted successfully!', '/unlockyourskills/vlr/questions');
            } else {
                error_log("[Assessment Delete] Failed to delete question: $questionId");
                $this->toastError('Failed to delete question.', '/unlockyourskills/vlr/questions');
            }
        } else {
            error_log("[Assessment Delete] Invalid request triggered.");
            $this->toastError('Invalid request.', '/unlockyourskills/vlr/questions');
        }
    }

    public function ajaxSearch() {
        header('Content-Type: application/json');

        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized access. Please log in.'
            ]);
            exit();
        }

        $clientId = $_SESSION['user']['client_id'];

        try {
            $limit = 10;
            $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
            $offset = ($page - 1) * $limit;

            // Get search and filter parameters
            $search = trim($_POST['search'] ?? '');
            $filters = [];

            if (!empty($_POST['question_type'])) {
                $filters['question_type'] = $_POST['question_type'];
            }

            if (!empty($_POST['difficulty'])) {
                $filters['difficulty'] = $_POST['difficulty'];
            }

            if (!empty($_POST['tags'])) {
                $filters['tags'] = $_POST['tags'];
            }

            // Get questions from database (client-specific)
            $questions = $this->questionModel->getQuestions($limit, $offset, $search, $filters, $clientId);
            $totalQuestions = $this->questionModel->getTotalQuestionCount($search, $filters, $clientId);
            $totalPages = ceil($totalQuestions / $limit);

            $response = [
                'success' => true,
                'questions' => $questions,
                'totalQuestions' => $totalQuestions,
                'pagination' => [
                    'currentPage' => $page,
                    'totalPages' => $totalPages,
                    'totalQuestions' => $totalQuestions
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error loading questions: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    public function save() {
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
                return;
            }
            $this->toastError('Invalid request method.', '/unlockyourskills/vlr/questions');
            return;
        }

        if (!isset($_SESSION['id']) || !isset($_SESSION['user']['client_id'])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please log in.']);
                return;
            }
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $clientId = $_SESSION['user']['client_id'];

        $errors = [];
        $questionId = $_POST['questionId'] ?? null;

        // Collect and sanitize input
        $questionText = trim($_POST['questionText'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $skills = trim($_POST['skills'] ?? '');
        $level = $_POST['level'] ?? 'Low';
        $marks = intval($_POST['marks'] ?? 1);
        $status = $_POST['status'] ?? 'Active';
        $questionType = $_POST['questionFormType'] ?? 'Objective';
        $answerCount = intval($_POST['answerCount'] ?? 0);
        $mediaType = $_POST['questionMediaType'] ?? 'text';
        $createdBy = $_SESSION['id'] ?? 'Unknown';

        // Validations
        if (empty($questionText)) $errors[] = "Question text is required.";
        if (empty($tags)) $errors[] = "Tags are required.";
        if (!in_array($level, ['Low', 'Medium', 'Hard'])) $errors[] = "Invalid level.";
        if (!in_array($status, ['Active', 'Inactive'])) $errors[] = "Invalid status.";
        if (!in_array($questionType, ['Objective', 'Subjective'])) $errors[] = "Invalid question type.";
        if (!in_array($mediaType, ['text', 'image', 'audio', 'video'])) $errors[] = "Invalid media type.";

        $mediaFilePath = null;

        // Handle file upload with validation
        if ($mediaType !== 'text' && isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === 0) {
            $file = $_FILES['mediaFile'];

            // File size validation (10MB = 10 * 1024 * 1024 bytes)
            $maxSize = 10 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $errors[] = "File size must be less than 10MB.";
            }

            // File type validation based on media type
            $allowedTypes = [
                'image' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
                'audio' => ['audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mpeg'],
                'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/avi']
            ];

            if (isset($allowedTypes[$mediaType])) {
                $fileMimeType = mime_content_type($file['tmp_name']);
                if (!in_array($fileMimeType, $allowedTypes[$mediaType])) {
                    $expectedFormats = [
                        'image' => 'JPEG, PNG, GIF, WebP',
                        'audio' => 'MP3, WAV, OGG',
                        'video' => 'MP4, WebM, OGG, AVI'
                    ];
                    $errors[] = "Invalid file type for {$mediaType}. Expected: {$expectedFormats[$mediaType]}";
                }
            }

            if (empty($errors)) {
                $targetDir = "uploads/media/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                    chmod($targetDir, 0777); // Ensure proper permissions
                }
                $fileName = basename($file["name"]);
                $mediaFilePath = $targetDir . time() . "_" . $fileName;

                if (!move_uploaded_file($file["tmp_name"], $mediaFilePath)) {
                    $errors[] = "Media file upload failed.";
                }
            }
        }

        if ($questionType === 'Objective') {
            $options = $_POST['options'] ?? [];
            if (count($options) < 1) {
                $errors[] = "At least one option is required.";
            }
        }

        if (empty($errors)) {
            $data = [
                'client_id' => $clientId,
                'question_text' => $questionText,
                'tags' => $tags,
                'competency_skills' => $skills,
                'level' => $level,
                'marks' => $marks,
                'status' => $status,
                'question_type' => $questionType,
                'answer_count' => $answerCount,
                'media_type' => $mediaType,
                'media_file' => $mediaFilePath,
                'created_by' => $createdBy
            ];

            if ($questionId) {
                // Update existing question
                $result = $this->questionModel->updateQuestion($questionId, $data);
                $finalQuestionId = $questionId;
            } else {
                // Insert new question
                $finalQuestionId = $this->questionModel->insertQuestion($data);
                $result = $finalQuestionId !== false;
            }

            if ($questionType === 'Objective' && $finalQuestionId && isset($options)) {
                if ($questionId) {
                    // Delete existing options for update
                    $this->questionModel->deleteOptionsByQuestionId($finalQuestionId);
                }

                foreach ($options as $index => $option) {
                    $optionText = trim($option['text']);
                    $isCorrect = isset($option['correct']) ? 1 : 0;

                    if (!empty($optionText)) {
                        $this->questionModel->insertOption([
                            'client_id' => $clientId,
                            'question_id' => $finalQuestionId,
                            'option_index' => $index,
                            'option_text' => $optionText,
                            'is_correct' => $isCorrect
                        ]);
                    }
                }
            }

            if ($result) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    $message = $questionId ? 'Question updated successfully!' : 'Question added successfully!';
                    echo json_encode(['success' => true, 'message' => $message]);
                    return;
                } else {
                    if ($questionId) {
                        $this->toastSuccess('Question updated successfully!', '/unlockyourskills/vlr/questions');
                    } else {
                        $this->toastSuccess('Question added successfully!', '/unlockyourskills/vlr/questions');
                    }
                }
            } else {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to save question.']);
                    return;
                } else {
                    $this->toastError('Failed to save question.', '/unlockyourskills/vlr/questions');
                }
            }
            return;
        } else {
            $errorMsg = implode(', ', $errors);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Error(s): $errorMsg"]);
                return;
            } else {
                $this->toastError("Error(s): $errorMsg", '/unlockyourskills/vlr/questions');
            }
            return;
        }
    }

    public function getQuestionById($id = null) {
        header('Content-Type: application/json');

        // Check if user is logged in and get client_id
        if (!isset($_SESSION['user']['client_id'])) {
            echo json_encode(['error' => 'Unauthorized access. Please log in.']);
            exit;
        }

        $clientId = $_SESSION['user']['client_id'];

        // Use the route parameter $id, fallback to $_GET['id']
        if (!$id && isset($_GET['id'])) {
            $id = $_GET['id'];
        }

        if (!$id || !is_numeric($id)) {
            echo json_encode(['error' => 'Invalid question ID']);
            exit;
        }

        $id = (int)$id;

        try {
            $question = $this->questionModel->getQuestionById($id, $clientId);
            $options = $this->questionModel->getOptionsByQuestionId($id, $clientId);

            if (!$question) {
                echo json_encode(['error' => 'Question not found']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'question' => $question,
                'options' => $options
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }

    public function downloadTemplate() {
        $type = $_GET['type'] ?? 'objective';
        $format = $_GET['format'] ?? 'csv'; // csv or excel

        if (!in_array($type, ['objective', 'subjective'])) {
            $this->toastError('Invalid template type.', '/unlockyourskills/vlr/questions');
            return;
        }

        if ($format === 'excel') {
            // Generate proper Excel file using HTML table format
            $filename = "assessment_questions_template_{$type}.xls";

            // Set proper headers for Excel - force download
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Generate proper Excel HTML format
            $this->generateExcelHTMLTemplate($type);
        } else {
            // Generate CSV file
            $filename = "assessment_questions_template_{$type}.csv";

            // Set proper CSV headers - force download, prevent Numbers from opening
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');

            if ($type === 'objective') {
                $this->generateObjectiveTemplate();
            } else {
                $this->generateSubjectiveTemplate();
            }
        }
    }

    private function generateObjectiveTemplate() {
        // CSV headers for objective questions
        $headers = [
            'Question Text*',
            'Tags/Keywords*',
            'Competency Skills',
            'Difficulty Level* (Low/Medium/Hard)',
            'Marks*',
            'Option 1*',
            'Option 2*',
            'Option 3',
            'Option 4',
            'Option 5',
            'Correct Answer* (1,2,3,4,5)',
            'Media Type (text/image/audio/video)',
            'Media File Path'
        ];

        // Sample data
        $sampleData = [
            [
                'What is the capital of France?',
                'geography, capitals, france',
                'General Knowledge',
                'Low',
                '1',
                'Paris',
                'London',
                'Berlin',
                'Madrid',
                '',
                '1',
                'text',
                ''
            ],
            [
                'Which programming language is used for web development?',
                'programming, web development, languages',
                'Technical Skills',
                'Medium',
                '2',
                'Python',
                'JavaScript',
                'C++',
                'Java',
                'PHP',
                '2',
                'text',
                ''
            ]
        ];

        $this->outputCSV($headers, $sampleData);
    }

    private function generateSubjectiveTemplate() {
        // CSV headers for subjective questions
        $headers = [
            'Question Text*',
            'Tags/Keywords*',
            'Competency Skills',
            'Difficulty Level* (Low/Medium/Hard)',
            'Marks*',
            'Media Type (text/image/audio/video)',
            'Media File Path'
        ];

        // Sample data
        $sampleData = [
            [
                'Explain the concept of object-oriented programming and its benefits.',
                'programming, oop, concepts',
                'Technical Skills',
                'Hard',
                '5',
                'text',
                ''
            ],
            [
                'Describe the importance of data security in modern applications.',
                'security, data protection, applications',
                'Security Awareness',
                'Medium',
                '3',
                'text',
                ''
            ]
        ];

        $this->outputCSV($headers, $sampleData);
    }

    private function generateExcelHTMLTemplate($type) {
        // Generate proper Excel-compatible HTML with XML namespace
        echo '<?xml version="1.0"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        echo ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        echo ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";

        // Add styles for better formatting
        echo '<Styles>' . "\n";
        echo '<Style ss:ID="Header">' . "\n";
        echo '<Font ss:Bold="1" ss:Color="#FFFFFF"/>' . "\n";
        echo '<Interior ss:Color="#6a0dad" ss:Pattern="Solid"/>' . "\n";
        echo '</Style>' . "\n";
        echo '</Styles>' . "\n";

        echo '<Worksheet ss:Name="Assessment Questions">' . "\n";
        echo '<Table>' . "\n";

        if ($type === 'objective') {
            $headers = [
                'Question Text*',
                'Tags/Keywords*',
                'Competency Skills',
                'Difficulty Level* (Low/Medium/Hard)',
                'Marks*',
                'Option 1*',
                'Option 2*',
                'Option 3',
                'Option 4',
                'Option 5',
                'Correct Answer* (1,2,3,4,5)',
                'Media Type (text/image/audio/video)',
                'Media File Path'
            ];

            $sampleData = [
                [
                    'What is the capital of France?',
                    'geography, capitals, france',
                    'General Knowledge',
                    'Low',
                    '1',
                    'Paris',
                    'London',
                    'Berlin',
                    'Madrid',
                    '',
                    '1',
                    'text',
                    ''
                ],
                [
                    'Which programming language is used for web development?',
                    'programming, web development, languages',
                    'Technical Skills',
                    'Medium',
                    '2',
                    'Python',
                    'JavaScript',
                    'C++',
                    'Java',
                    'PHP',
                    '2',
                    'text',
                    ''
                ]
            ];
        } else {
            $headers = [
                'Question Text*',
                'Tags/Keywords*',
                'Competency Skills',
                'Difficulty Level* (Low/Medium/Hard)',
                'Marks*',
                'Media Type (text/image/audio/video)',
                'Media File Path'
            ];

            $sampleData = [
                [
                    'Explain the concept of object-oriented programming and its benefits.',
                    'programming, oop, concepts',
                    'Technical Skills',
                    'Hard',
                    '5',
                    'text',
                    ''
                ],
                [
                    'Describe the importance of data security in modern applications.',
                    'security, data protection, applications',
                    'Security Awareness',
                    'Medium',
                    '3',
                    'text',
                    ''
                ]
            ];
        }

        // Output headers with proper Excel formatting
        echo '<Row ss:StyleID="Header">' . "\n";
        foreach ($headers as $header) {
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        echo '</Row>' . "\n";

        // Output sample data with proper Excel formatting
        foreach ($sampleData as $row) {
            echo '<Row>' . "\n";
            foreach ($row as $cell) {
                $cellType = is_numeric($cell) ? 'Number' : 'String';
                echo '<Cell><Data ss:Type="' . $cellType . '">' . htmlspecialchars($cell) . '</Data></Cell>' . "\n";
            }
            echo '</Row>' . "\n";
        }

        echo '</Table>' . "\n";
        echo '</Worksheet>' . "\n";
        echo '</Workbook>' . "\n";
        exit();
    }

    private function outputCSV($headers, $data) {
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8 to ensure proper encoding
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers with explicit comma separation
        fputcsv($output, $headers, ',', '"');

        // Write sample data with explicit comma separation
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"');
        }

        fclose($output);
        exit();
    }

    // New method for Excel-compatible CSV output
    private function outputExcelCSV($headers, $data) {
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8 to ensure proper encoding in Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers with tab separation for better Excel compatibility
        fputcsv($output, $headers, "\t");

        // Write sample data with tab separation
        foreach ($data as $row) {
            fputcsv($output, $row, "\t");
        }

        fclose($output);
        exit();
    }

    private function generateObjectiveTemplateForExcel() {
        // CSV headers for objective questions
        $headers = [
            'Question Text*',
            'Tags/Keywords*',
            'Competency Skills',
            'Difficulty Level* (Low/Medium/Hard)',
            'Marks*',
            'Option 1*',
            'Option 2*',
            'Option 3',
            'Option 4',
            'Option 5',
            'Correct Answer* (1,2,3,4,5)',
            'Media Type (text/image/audio/video)',
            'Media File Path'
        ];

        // Sample data
        $sampleData = [
            [
                'What is the capital of France?',
                'geography, capitals, france',
                'General Knowledge',
                'Low',
                '1',
                'Paris',
                'London',
                'Berlin',
                'Madrid',
                '',
                '1',
                'text',
                ''
            ],
            [
                'Which programming language is used for web development?',
                'programming, web development, languages',
                'Technical Skills',
                'Medium',
                '2',
                'Python',
                'JavaScript',
                'C++',
                'Java',
                'PHP',
                '2',
                'text',
                ''
            ]
        ];

        $this->outputExcelCSV($headers, $sampleData);
    }

    private function generateSubjectiveTemplateForExcel() {
        // CSV headers for subjective questions
        $headers = [
            'Question Text*',
            'Tags/Keywords*',
            'Competency Skills',
            'Difficulty Level* (Low/Medium/Hard)',
            'Marks*',
            'Media Type (text/image/audio/video)',
            'Media File Path'
        ];

        // Sample data
        $sampleData = [
            [
                'Explain the concept of object-oriented programming and its benefits.',
                'programming, oop, concepts',
                'Technical Skills',
                'Hard',
                '5',
                'text',
                ''
            ],
            [
                'Describe the importance of data security in modern applications.',
                'security, data protection, applications',
                'Security Awareness',
                'Medium',
                '3',
                'text',
                ''
            ]
        ];

        $this->outputExcelCSV($headers, $sampleData);
    }

    public function importQuestions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->toastError('Invalid request method.', '/unlockyourskills/vlr/questions');
            return;
        }

        if (!isset($_SESSION['id'])) {
            $this->toastError('Unauthorized access. Please log in.', '/unlockyourskills/login');
            return;
        }

        $questionType = $_POST['questionType'] ?? '';

        if (!in_array($questionType, ['objective', 'subjective'])) {
            $this->toastError('Invalid question type selected.', '/unlockyourskills/vlr/questions');
            return;
        }

        if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
            $this->toastError('Please select a valid Excel file.', '/unlockyourskills/vlr/questions');
            return;
        }

        $file = $_FILES['importFile'];
        $allowedTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain', // Some systems report CSV as text/plain
            'application/csv' // Alternative CSV MIME type
        ];

        // Also check file extension as backup
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['xls', 'xlsx', 'csv'];

        if (!in_array($file['type'], $allowedTypes) && !in_array($fileExtension, $allowedExtensions)) {
            $this->toastError('Invalid file type. Please upload Excel (.xlsx, .xls) or CSV (.csv) file.', '/unlockyourskills/vlr/questions');
            return;
        }

        // Process the file
        $result = $this->processImportFile($file, $questionType);

        if ($result['success']) {
            $message = "Successfully imported {$result['count']} questions.";
            if (!empty($result['errors'])) {
                $message .= " Note: " . count($result['errors']) . " rows had errors and were skipped.";
            }
            $this->toastSuccess($message, '/unlockyourskills/vlr/questions');
        } else {
            $this->toastError("Import failed: " . $result['message'], '/unlockyourskills/vlr/questions');
        }
    }

    private function processImportFile($file, $questionType) {
        $uploadDir = "uploads/temp/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            chmod($uploadDir, 0777); // Ensure proper permissions
        }

        $tempFile = $uploadDir . time() . '_' . $file['name'];

        // Handle both uploaded files and regular files (for testing)
        if (is_uploaded_file($file['tmp_name'])) {
            if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
                return ['success' => false, 'message' => 'Failed to upload file.'];
            }
        } else {
            // For testing purposes, copy the file
            if (!copy($file['tmp_name'], $tempFile)) {
                return ['success' => false, 'message' => 'Failed to copy file.'];
            }
        }

        $data = [];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        try {
            if ($fileExtension === 'csv' || $fileExtension === 'xls') {
                // Handle CSV and Excel files saved as CSV format
                if (($handle = fopen($tempFile, "r")) !== FALSE) {
                    // Try different delimiters for better compatibility
                    $delimiters = [',', ';', "\t"];
                    $bestDelimiter = ',';
                    $maxColumns = 0;

                    // Detect the best delimiter
                    foreach ($delimiters as $delimiter) {
                        rewind($handle);
                        $firstRow = fgetcsv($handle, 0, $delimiter);
                        if ($firstRow && count($firstRow) > $maxColumns) {
                            $maxColumns = count($firstRow);
                            $bestDelimiter = $delimiter;
                        }
                    }

                    // Read the file with the best delimiter
                    rewind($handle);
                    $headers = fgetcsv($handle, 0, $bestDelimiter); // Skip headers

                    while (($row = fgetcsv($handle, 0, $bestDelimiter)) !== FALSE) {
                        // Skip empty rows
                        if (!empty(array_filter($row))) {
                            $data[] = $row;
                        }
                    }
                    fclose($handle);
                }
            } else {
                throw new Exception("Unsupported file format: $fileExtension");
            }
        } catch (Exception $e) {
            unlink($tempFile); // Clean up temp file
            return ['success' => false, 'message' => 'Error reading file: ' . $e->getMessage()];
        }

        unlink($tempFile); // Clean up temp file

        if (empty($data)) {
            return ['success' => false, 'message' => 'No valid data found in the file.'];
        }

        return $this->importQuestionsFromData($data, $questionType);
    }

    private function importQuestionsFromData($data, $questionType) {
        $successCount = 0;
        $errors = [];

        // Better session handling
        if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
            throw new Exception("User session not found. Please login again.");
        }
        $createdBy = $_SESSION['id'];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header row

            try {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                if ($questionType === 'objective') {
                    $result = $this->importObjectiveQuestion($row, $createdBy);
                } else {
                    $result = $this->importSubjectiveQuestion($row, $createdBy);
                }

                if ($result) {
                    $successCount++;
                } else {
                    $errors[] = "Row $rowNumber: Failed to import question.";
                }
            } catch (Exception $e) {
                $errors[] = "Row $rowNumber: " . $e->getMessage();
                // Log the error for debugging
                error_log("CSV Import Error - Row $rowNumber: " . $e->getMessage());
            }
        }

        return [
            'success' => $successCount > 0,
            'count' => $successCount,
            'errors' => $errors,
            'message' => $successCount > 0 ? "Import completed." : "No questions were imported."
        ];
    }

    private function importObjectiveQuestion($row, $createdBy) {
        // Validate required fields
        if (empty($row[0]) || empty($row[1]) || empty($row[3]) || empty($row[4]) || empty($row[5]) || empty($row[6])) {
            throw new Exception("Missing required fields.");
        }

        $questionData = [
            'question_text' => trim($row[0]),
            'tags' => trim($row[1]),
            'competency_skills' => trim($row[2] ?? ''),
            'level' => $this->validateLevel(trim($row[3])),
            'marks' => intval($row[4]),
            'status' => 'Active',  // Fixed: Database expects 'Active' not 'active'
            'question_type' => 'Objective',  // Fixed: Database expects 'Objective' not 'multi_choice'
            'media_type' => trim($row[11] ?? 'text'),
            'media_file' => isset($row[12]) && $row[12] !== null ? trim($row[12]) : null,
            'created_by' => (string)$createdBy  // Ensure it's a string as expected by database
        ];

        // Count non-empty options
        $options = [];
        for ($i = 5; $i <= 9; $i++) {
            if (!empty(trim($row[$i] ?? ''))) {
                $options[] = trim($row[$i]);
            }
        }

        if (count($options) < 2) {
            throw new Exception("At least 2 options are required.");
        }

        $questionData['answer_count'] = count($options);

        // Insert question
        $questionId = $this->questionModel->insertQuestion($questionData);

        if (!$questionId) {
            return false;
        }

        // Insert options
        $correctAnswer = intval($row[10] ?? 1);
        foreach ($options as $index => $optionText) {
            $isCorrect = ($index + 1) === $correctAnswer ? 1 : 0;
            $this->questionModel->insertOption([
                'question_id' => $questionId,
                'option_index' => $index,
                'option_text' => $optionText,
                'is_correct' => $isCorrect
            ]);
        }

        return true;
    }

    private function importSubjectiveQuestion($row, $createdBy) {
        // Validate required fields
        if (empty($row[0]) || empty($row[1]) || empty($row[3]) || empty($row[4])) {
            throw new Exception("Missing required fields.");
        }

        $questionData = [
            'question_text' => trim($row[0]),
            'tags' => trim($row[1]),
            'competency_skills' => trim($row[2] ?? ''),
            'level' => $this->validateLevel(trim($row[3])),
            'marks' => intval($row[4]),
            'status' => 'Active',  // Fixed: Database expects 'Active' not 'active'
            'question_type' => 'Subjective',  // Fixed: Database expects 'Subjective' not 'long_answer'
            'answer_count' => 0,
            'media_type' => trim($row[5] ?? 'text'),
            'media_file' => isset($row[6]) && $row[6] !== null ? trim($row[6]) : null,
            'created_by' => (string)$createdBy  // Ensure it's a string as expected by database
        ];

        return $this->questionModel->insertQuestion($questionData);
    }

    private function validateLevel($level) {
        $validLevels = ['Low', 'Medium', 'Hard'];
        $level = ucfirst(strtolower($level));

        if (!in_array($level, $validLevels)) {
            throw new Exception("Invalid difficulty level. Must be Low, Medium, or Hard.");
        }

        return $level;
    }

}
