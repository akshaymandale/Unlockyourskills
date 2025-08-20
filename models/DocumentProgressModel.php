<?php
require_once 'config/Database.php';

class DocumentProgressModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Start tracking a document when user opens it
     */
    public function startTracking($userId, $courseId, $contentId, $documentPackageId, $clientId, $totalPages = 0) {
        try {
            error_log("DocumentProgressModel::startTracking called with: userId=$userId, courseId=$courseId, contentId=$contentId, documentPackageId=$documentPackageId, clientId=$clientId, totalPages=$totalPages");
            
            // Check if progress record already exists
            $existingProgress = $this->getProgress($userId, $courseId, $contentId, $clientId);
            
            if ($existingProgress) {
                // Update last_viewed_at and return existing progress
                $sql = "UPDATE document_progress 
                        SET last_viewed_at = CURRENT_TIMESTAMP,
                            total_pages = CASE WHEN total_pages = 0 THEN ? ELSE total_pages END
                        WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([$totalPages, $userId, $courseId, $contentId, $clientId]);
                
                return $this->getProgress($userId, $courseId, $contentId, $clientId);
            }

            // Create new progress record
            $sql = "INSERT INTO document_progress (
                        user_id, course_id, content_id, document_package_id, client_id,
                        current_page, total_pages, pages_viewed, viewed_percentage,
                        completion_threshold, is_completed, status, time_spent, last_viewed_at
                    ) VALUES (?, ?, ?, ?, ?, 1, ?, '[]', 0.00, 80.00, 0, 'not_started', 0, CURRENT_TIMESTAMP)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $userId, $courseId, $contentId, $documentPackageId, $clientId, $totalPages
            ]);

            return $this->getProgress($userId, $courseId, $contentId, $clientId);
        } catch (Exception $e) {
            error_log("Error starting document tracking: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update document progress
     */
    public function updateProgress($userId, $courseId, $contentId, $clientId, $currentPage, $pagesViewed, $timeSpent, $viewedPercentage) {
        try {
            // Ensure progress record exists
            $existingProgress = $this->getProgress($userId, $courseId, $contentId, $clientId);
            if (!$existingProgress) {
                throw new Exception('Document progress record not found. Please start tracking first.');
            }

            // Convert pages viewed to JSON if it's an array
            $pagesViewedJson = is_array($pagesViewed) ? json_encode($pagesViewed) : $pagesViewed;

            // Calculate completion and status based on viewed percentage (80% threshold)
            $isCompleted = ($viewedPercentage >= 80) ? 1 : 0;
            
            // Determine status
            $status = 'not_started';
            if ($isCompleted) {
                $status = 'completed';
            } elseif ($viewedPercentage > 0) {
                $status = 'in_progress';
            } elseif ($currentPage > 1) {
                $status = 'started';
            }

            $sql = "UPDATE document_progress 
                    SET current_page = ?,
                        pages_viewed = ?,
                        viewed_percentage = ?,
                        is_completed = ?,
                        status = ?,
                        time_spent = time_spent + ?,
                        last_viewed_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $currentPage, $pagesViewedJson, $viewedPercentage, $isCompleted, $status, $timeSpent,
                $userId, $courseId, $contentId, $clientId
            ]);

            return $this->getProgress($userId, $courseId, $contentId, $clientId);
        } catch (Exception $e) {
            error_log("Error updating document progress: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark document as completed
     */
    public function markComplete($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "UPDATE document_progress 
                    SET is_completed = 1,
                        viewed_percentage = 100.00,
                        status = 'completed',
                        last_viewed_at = CURRENT_TIMESTAMP
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Document progress record not found');
            }

            return $this->getProgress($userId, $courseId, $contentId, $clientId);
        } catch (Exception $e) {
            error_log("Error marking document as complete: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get document completion status (for integration with CourseModel)
     */
    public function getCompletionStatus($userId, $courseId, $contentId, $clientId) {
        try {
            $progress = $this->getProgress($userId, $courseId, $contentId, $clientId);
            
            if (!$progress) {
                return [
                    'status' => 'not_started',
                    'progress_percentage' => 0,
                    'is_completed' => false,
                    'current_page' => 0,
                    'total_pages' => 0
                ];
            }

            // Get status directly from database column
            $status = $progress['status'] ?? 'not_started';

            return [
                'status' => $status,
                'progress_percentage' => floatval($progress['viewed_percentage']),
                'is_completed' => (bool)$progress['is_completed'],
                'current_page' => intval($progress['current_page']),
                'total_pages' => intval($progress['total_pages'])
            ];
        } catch (Exception $e) {
            error_log("Error getting document completion status: " . $e->getMessage());
            return [
                'status' => 'error',
                'progress_percentage' => 0,
                'is_completed' => false,
                'current_page' => 0,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Get document progress for a user
     */
    public function getProgress($userId, $courseId, $contentId, $clientId) {
        try {
            error_log("DocumentProgressModel::getProgress called with: userId=$userId, courseId=$courseId, contentId=$contentId, clientId=$clientId");
            
            $sql = "SELECT dp.*, d.title as document_title, 
                           COALESCE(d.word_excel_ppt_file, d.ebook_manual_file, d.research_file) as document_path
                    FROM document_progress dp
                    LEFT JOIN documents d ON dp.document_package_id = d.id
                    WHERE dp.user_id = ? AND dp.course_id = ? AND dp.content_id = ? AND dp.client_id = ?";
            
            error_log("SQL Query: " . $sql);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);
            
            $progress = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progress) {
                // Decode JSON fields
                $progress['pages_viewed'] = json_decode($progress['pages_viewed'] ?? '[]', true);
                $progress['bookmarks'] = json_decode($progress['bookmarks'] ?? '[]', true);
                
                // Ensure status is set (fallback for existing records)
                if (!isset($progress['status'])) {
                    $progress['status'] = $this->calculateStatusFromProgress($progress);
                }
            }
            
            error_log("DocumentProgressModel::getProgress result: " . json_encode($progress));
            return $progress;
        } catch (Exception $e) {
            error_log("Error getting document progress: " . $e->getMessage());
            error_log("Error stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Calculate status from progress data (fallback for existing records)
     */
    private function calculateStatusFromProgress($progress) {
        if ($progress['is_completed']) {
            return 'completed';
        } elseif ($progress['viewed_percentage'] >= 80) {
            return 'completed';
        } elseif ($progress['viewed_percentage'] > 0) {
            return 'in_progress';
        } elseif ($progress['current_page'] > 1) {
            return 'started';
        }
        return 'not_started';
    }

    /**
     * Get document progress percentage (for integration with CourseModel)
     */
    public function getProgressPercentage($userId, $courseId, $contentId, $clientId) {
        try {
            $progress = $this->getProgress($userId, $courseId, $contentId, $clientId);
            
            if (!$progress) {
                return 0;
            }

            if ($progress['is_completed']) {
                return 100;
            }

            return floatval($progress['viewed_percentage']);
        } catch (Exception $e) {
            error_log("Error getting document progress percentage: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Save bookmark in document
     */
    public function saveBookmark($userId, $courseId, $contentId, $clientId, $page, $title, $note) {
        try {
            $progress = $this->getProgress($userId, $courseId, $contentId, $clientId);
            if (!$progress) {
                throw new Exception('Document progress record not found');
            }

            $bookmarks = $progress['bookmarks'] ?? [];
            
            // Add new bookmark
            $bookmark = [
                'id' => uniqid(),
                'page' => $page,
                'title' => $title,
                'note' => $note,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $bookmarks[] = $bookmark;

            $sql = "UPDATE document_progress 
                    SET bookmarks = ?
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                json_encode($bookmarks),
                $userId, $courseId, $contentId, $clientId
            ]);

            return $bookmark;
        } catch (Exception $e) {
            error_log("Error saving bookmark: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save notes for document
     */
    public function saveNotes($userId, $courseId, $contentId, $clientId, $notes) {
        try {
            $sql = "UPDATE document_progress 
                    SET notes = ?
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$notes, $userId, $courseId, $contentId, $clientId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Document progress record not found');
            }

            return ['notes' => $notes, 'updated_at' => date('Y-m-d H:i:s')];
        } catch (Exception $e) {
            error_log("Error saving notes: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all document progress for a user in a course (for reporting)
     */
    public function getUserCourseDocumentProgress($userId, $courseId, $clientId) {
        try {
            $sql = "SELECT dp.*, cmc.id as content_id, d.title as document_title
                    FROM document_progress dp
                    JOIN course_module_content cmc ON dp.content_id = cmc.id
                    LEFT JOIN documents d ON dp.document_package_id = d.id
                    WHERE dp.user_id = ? AND dp.course_id = ? AND dp.client_id = ?
                    ORDER BY dp.last_viewed_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $clientId]);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON fields for each result
            foreach ($results as &$result) {
                $result['pages_viewed'] = json_decode($result['pages_viewed'] ?? '[]', true);
                $result['bookmarks'] = json_decode($result['bookmarks'] ?? '[]', true);
            }
            
            return $results;
        } catch (Exception $e) {
            error_log("Error getting user course document progress: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete document progress (for cleanup)
     */
    public function deleteProgress($userId, $courseId, $contentId, $clientId) {
        try {
            $sql = "DELETE FROM document_progress 
                    WHERE user_id = ? AND course_id = ? AND content_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $courseId, $contentId, $clientId]);

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error deleting document progress: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get document statistics for analytics
     */
    public function getDocumentStats($documentPackageId, $clientId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_users,
                        COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_users,
                        AVG(viewed_percentage) as avg_viewed_percentage,
                        AVG(time_spent) as avg_time_spent,
                        MAX(time_spent) as max_time_spent,
                        MIN(time_spent) as min_time_spent
                    FROM document_progress 
                    WHERE document_package_id = ? AND client_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$documentPackageId, $clientId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting document stats: " . $e->getMessage());
            throw $e;
        }
    }
}
