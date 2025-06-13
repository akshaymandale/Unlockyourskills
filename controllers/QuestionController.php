<?php
require_once 'models/QuestionModel.php';

class QuestionController {
    private $questionModel;

    public function __construct() {
        $this->questionModel = new QuestionModel();
    }

    public function index() {
        // ✅ Don't load initial data - let JavaScript handle it via AJAX
        // This prevents duplicate data rendering issues
        $questions = []; // Empty array for initial page load
        $totalQuestions = 0;
        $totalPages = 0;
        $page = 1;

        // Get unique values for filter dropdowns
        $uniqueQuestionTypes = $this->questionModel->getUniqueQuestionTypes();
        $uniqueDifficultyLevels = $this->questionModel->getUniqueDifficultyLevels();

        require 'views/add_assessment.php';
    }

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!isset($_SESSION['id'])) {
                echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
                exit();
            }

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
                $targetDir = "uploads/media/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = basename($_FILES["mediaFile"]["name"]);
                $mediaFilePath = $targetDir . time() . "_" . $fileName;

                if (!move_uploaded_file($_FILES["mediaFile"]["tmp_name"], $mediaFilePath)) {
                    $errors[] = "Media file upload failed.";
                }
            }

            if ($questionType === 'Objective') {
                $options = $_POST['options'] ?? [];
                if (count($options) < 1) {
                    $errors[] = "At least one option is required.";
                }
            }

            if (empty($errors)) {
                $questionId = $this->questionModel->insertQuestion([
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
                                'question_id' => $questionId,
                                'option_index' => $index,
                                'option_text' => $optionText,
                                'is_correct' => $isCorrect
                            ]);
                        }
                    }
                }

                $message = $questionId ? "Question added successfully." : "Failed to insert question.";
                echo "<script>alert('$message'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            } else {
                $errorMsg = implode("\\n", $errors);
                echo "<script>alert('Error(s):\\n$errorMsg'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            }
        }

        require 'views/add_assessment_question.php';
    }

    public function edit() {
        if (!isset($_SESSION['id'])) {
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            exit();
        }
    
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
                $targetDir = "uploads/media/";
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = basename($_FILES["mediaFile"]["name"]);
                $mediaFilePath = $targetDir . time() . "_" . $fileName;
    
                if (!move_uploaded_file($_FILES["mediaFile"]["tmp_name"], $mediaFilePath)) {
                    $errors[] = "Media file upload failed.";
                }
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
                                'question_id' => $questionId,
                                'option_index' => $index,
                                'option_text' => $optionText,
                                'is_correct' => $isCorrect
                            ]);
                        }
                    }
                }
    
                $msg = $updateResult ? "Question updated successfully." : "Failed to update question.";
                echo "<script>alert('$msg'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            } else {
                $errorMsg = implode("\\n", $errors);
                echo "<script>alert('Error(s):\\n$errorMsg'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            }
        }
        // Handle initial page load (GET)
        else if (isset($_GET['id'])) {
            $questionId = intval($_GET['id']);
            $question = $this->questionModel->getQuestionById($questionId);
            $options = $this->questionModel->getOptionsByQuestionId($questionId);
    
            if ($question) {
                // Reuse add_assessment_question.php for editing
                $isEdit = true;
                require 'views/add_assessment_question.php';
            } else {
                echo "<script>alert('Question not found.'); window.location.href='index.php?controller=QuestionController';</script>";
                exit();
            }
        } else {
            echo "<script>alert('Invalid request.'); window.location.href='index.php?controller=QuestionController';</script>";
            exit();
        }
    }
    
    
    
    public function delete() {
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $success = $this->questionModel->softDeleteQuestion($id);
            $message = $success ? "Question deleted successfully." : "Failed to delete question.";
            echo "<script>alert('$message'); window.location.href='index.php?controller=QuestionController';</script>";
        } else {
            echo "<script>alert('Invalid request.'); window.location.href='index.php?controller=QuestionController';</script>";
        }
    }

    public function ajaxSearch() {
        header('Content-Type: application/json');

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

            // Get questions from database
            $questions = $this->questionModel->getQuestions($limit, $offset, $search, $filters);
            $totalQuestions = $this->questionModel->getTotalQuestionCount($search, $filters);
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
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>alert('Invalid request method.'); window.location.href='index.php?controller=QuestionController';</script>";
            return;
        }

        if (!isset($_SESSION['id'])) {
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            return;
        }

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
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
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
                            'question_id' => $finalQuestionId,
                            'option_index' => $index,
                            'option_text' => $optionText,
                            'is_correct' => $isCorrect
                        ]);
                    }
                }
            }

            $message = $result ? ($questionId ? "Question updated successfully." : "Question added successfully.") : "Failed to save question.";
            echo "<script>alert('$message'); window.location.href='index.php?controller=QuestionController';</script>";
            exit();
        } else {
            $errorMsg = implode("\\n", $errors);
            echo "<script>alert('Error(s):\\n$errorMsg'); window.location.href='index.php?controller=QuestionController';</script>";
            exit();
        }
    }

    public function getQuestionById() {
        // Simple test first - just return a basic response
        header('Content-Type: application/json');

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            echo json_encode(['error' => 'Invalid question ID']);
            exit;
        }

        $id = (int)$_GET['id'];

        try {
            $question = $this->questionModel->getQuestionById($id);
            $options = $this->questionModel->getOptionsByQuestionId($id);

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
        $format = $_GET['format'] ?? 'excel'; // excel or csv

        if (!in_array($type, ['objective', 'subjective'])) {
            echo "<script>alert('Invalid template type.'); window.location.href='index.php?controller=QuestionController';</script>";
            return;
        }

        if ($format === 'excel') {
            // Generate Excel-compatible HTML table
            $filename = "assessment_questions_template_{$type}.xls";

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $this->generateExcelHTMLTemplate($type);
        } else {
            // Generate CSV file
            $filename = "assessment_questions_template_{$type}.csv";

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

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
        // Generate simple HTML table that Excel can open without warnings
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head>';
        echo '<meta charset="utf-8">';
        echo '<meta name="ProgId" content="Excel.Sheet">';
        echo '<meta name="Generator" content="Assessment Question Template Generator">';
        echo '</head>';
        echo '<body>';
        echo '<table border="1">';

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

        // Output headers with styling
        echo '<tr style="background-color: #6a0dad; color: white; font-weight: bold;">';
        foreach ($headers as $header) {
            echo '<td>' . htmlspecialchars($header) . '</td>';
        }
        echo '</tr>';

        // Output sample data
        foreach ($sampleData as $rowIndex => $row) {
            $bgColor = $rowIndex % 2 === 0 ? '#f8f9fa' : '#ffffff';
            echo '<tr style="background-color: ' . $bgColor . ';">';
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }

        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit();
    }

    private function outputCSV($headers, $data) {
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers
        fputcsv($output, $headers);

        // Write sample data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit();
    }

    public function importQuestions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo "<script>alert('Invalid request method.'); window.location.href='index.php?controller=QuestionController';</script>";
            return;
        }

        if (!isset($_SESSION['id'])) {
            echo "<script>alert('Unauthorized access. Please log in.'); window.location.href='index.php?controller=VLRController';</script>";
            return;
        }

        $questionType = $_POST['questionType'] ?? '';

        if (!in_array($questionType, ['objective', 'subjective'])) {
            echo "<script>alert('Invalid question type selected.'); window.location.href='index.php?controller=QuestionController';</script>";
            return;
        }

        if (!isset($_FILES['importFile']) || $_FILES['importFile']['error'] !== UPLOAD_ERR_OK) {
            echo "<script>alert('Please select a valid Excel file.'); window.location.href='index.php?controller=QuestionController';</script>";
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
            echo "<script>alert('Invalid file type. Please upload Excel (.xlsx, .xls) or CSV (.csv) file.'); window.location.href='index.php?controller=QuestionController';</script>";
            return;
        }

        // Process the file
        $result = $this->processImportFile($file, $questionType);

        if ($result['success']) {
            $message = "Successfully imported {$result['count']} questions.";
            if (!empty($result['errors'])) {
                $message .= " Note: " . count($result['errors']) . " rows had errors and were skipped.";
            }
        } else {
            $message = "Import failed: " . $result['message'];
        }

        echo "<script>alert('$message'); window.location.href='index.php?controller=QuestionController';</script>";
    }

    private function processImportFile($file, $questionType) {
        $uploadDir = "uploads/temp/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tempFile = $uploadDir . time() . '_' . $file['name'];

        if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }

        // Read CSV file (simple implementation)
        $data = [];
        if (($handle = fopen($tempFile, "r")) !== FALSE) {
            $headers = fgetcsv($handle); // Skip headers
            while (($row = fgetcsv($handle)) !== FALSE) {
                $data[] = $row;
            }
            fclose($handle);
        }

        unlink($tempFile); // Clean up temp file

        return $this->importQuestionsFromData($data, $questionType);
    }

    private function importQuestionsFromData($data, $questionType) {
        $successCount = 0;
        $errors = [];
        $createdBy = $_SESSION['id'];

        foreach ($data as $index => $row) {
            $rowNumber = $index + 2; // +2 because index starts at 0 and we skip header row

            try {
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
            'status' => 'active',
            'question_type' => 'multi_choice',
            'media_type' => trim($row[11] ?? 'text'),
            'media_file' => trim($row[12] ?? null),
            'created_by' => $createdBy
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
            'status' => 'active',
            'question_type' => 'long_answer',
            'answer_count' => 0,
            'media_type' => trim($row[5] ?? 'text'),
            'media_file' => trim($row[6] ?? null),
            'created_by' => $createdBy
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
