<?php
require_once 'config/Database.php';

class AnnouncementModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Create new announcement
     */
    public function createAnnouncement($data) {
        $sql = "INSERT INTO announcements (
                    client_id, title, body, audience_type, urgency, require_acknowledgment,
                    cta_label, cta_url, start_datetime, end_datetime, status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['client_id'],
            $data['title'],
            $data['body'],
            $data['audience_type'],
            $data['urgency'],
            $data['require_acknowledgment'] ? 1 : 0,
            $data['cta_label'],
            $data['cta_url'],
            $data['start_datetime'],
            $data['end_datetime'],
            $data['status'],
            $data['created_by']
        ]);

        return $result ? $this->conn->lastInsertId() : false;
    }

    /**
     * Update announcement
     */
    public function updateAnnouncement($id, $data) {
        $sql = "UPDATE announcements SET
                    title = ?,
                    body = ?,
                    audience_type = ?,
                    urgency = ?,
                    require_acknowledgment = ?,
                    cta_label = ?,
                    cta_url = ?,
                    start_datetime = ?,
                    end_datetime = ?,
                    status = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ? AND client_id = ? AND is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['body'],
            $data['audience_type'],
            $data['urgency'],
            $data['require_acknowledgment'] ? 1 : 0,
            $data['cta_label'],
            $data['cta_url'],
            $data['start_datetime'],
            $data['end_datetime'],
            $data['status'],
            $data['updated_by'],
            $id,
            $data['client_id']
        ]);
    }

    /**
     * Update expired announcements automatically
     * This method should be called before fetching announcements to ensure proper status
     */
    public function updateExpiredAnnouncements($clientId = null) {
        $sql = "UPDATE announcements 
                SET status = 'expired', 
                    updated_at = NOW() 
                WHERE status = 'active' 
                AND end_datetime IS NOT NULL 
                AND end_datetime < NOW()";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update expired announcements for all clients
     * Useful for admin maintenance tasks or cron jobs
     */
    public function updateAllExpiredAnnouncements() {
        return $this->updateExpiredAnnouncements(null);
    }

    /**
     * Get expired announcements statistics
     */
    public function getExpiredAnnouncementsStats($clientId = null) {
        $sql = "SELECT 
                    COUNT(*) as total_expired,
                    COUNT(CASE WHEN end_datetime < DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as expired_yesterday,
                    COUNT(CASE WHEN end_datetime < DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as expired_this_week,
                    COUNT(CASE WHEN end_datetime < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as expired_this_month
                FROM announcements 
                WHERE status = 'expired' AND is_deleted = 0";
        
        $params = [];
        
        if ($clientId) {
            $sql .= " AND client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get announcements with pagination and filters
     */
    public function getAnnouncements($clientId, $filters = [], $page = 1, $limit = 10) {
        // First, update any expired announcements
        $this->updateExpiredAnnouncements($clientId);
        
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT a.*,
                       up.full_name as creator_name,
                       up.email as creator_email,
                       (SELECT COUNT(*) FROM announcement_acknowledgments aa
                        WHERE aa.announcement_id = a.id AND aa.client_id = a.client_id) as acknowledgment_count,
                       (SELECT COUNT(*) FROM announcement_views av
                        WHERE av.announcement_id = a.id AND av.client_id = a.client_id) as view_count
                FROM announcements a
                LEFT JOIN user_profiles up ON a.created_by = up.id
                WHERE a.client_id = ? AND a.is_deleted = 0";

        $params = [$clientId];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['audience_type'])) {
            $sql .= " AND a.audience_type = ?";
            $params[] = $filters['audience_type'];
        }

        if (!empty($filters['urgency'])) {
            $sql .= " AND a.urgency = ?";
            $params[] = $filters['urgency'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND a.created_by = ?";
            $params[] = $filters['created_by'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (a.title LIKE ? OR a.body LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND a.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND a.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        // Order by urgency and creation date
        $sql .= " ORDER BY
                    CASE a.urgency
                        WHEN 'urgent' THEN 1
                        WHEN 'warning' THEN 2
                        WHEN 'info' THEN 3
                    END,
                    a.created_at DESC
                  LIMIT " . intval($limit) . " OFFSET " . intval($offset);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get total count for pagination
     */
    public function getAnnouncementsCount($clientId, $filters = []) {
        // First, update any expired announcements to ensure accurate count
        $this->updateExpiredAnnouncements($clientId);
        
        $sql = "SELECT COUNT(*) FROM announcements a WHERE a.client_id = ? AND a.is_deleted = 0";
        $params = [$clientId];

        // Apply same filters as getAnnouncements
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['audience_type'])) {
            $sql .= " AND a.audience_type = ?";
            $params[] = $filters['audience_type'];
        }

        if (!empty($filters['urgency'])) {
            $sql .= " AND a.urgency = ?";
            $params[] = $filters['urgency'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND a.created_by = ?";
            $params[] = $filters['created_by'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (a.title LIKE ? OR a.body LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND a.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND a.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Get announcement by ID
     */
    public function getAnnouncementById($id, $clientId) {
        // First, update any expired announcements to ensure accurate status
        $this->updateExpiredAnnouncements($clientId);
        
        $sql = "SELECT a.*,
                       up.full_name as creator_name,
                       up.email as creator_email
                FROM announcements a
                LEFT JOIN user_profiles up ON a.created_by = up.id
                WHERE a.id = ? AND a.client_id = ? AND a.is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id, $clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Delete announcement (soft delete)
     */
    public function deleteAnnouncement($id, $clientId) {
        $sql = "UPDATE announcements SET is_deleted = 1, updated_at = NOW() 
                WHERE id = ? AND client_id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id, $clientId]);
    }

    /**
     * Update announcement status
     */
    public function updateAnnouncementStatus($id, $status, $clientId, $updatedBy) {
        $sql = "UPDATE announcements SET 
                    status = ?, 
                    updated_by = ?, 
                    updated_at = NOW() 
                WHERE id = ? AND client_id = ? AND is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $updatedBy, $id, $clientId]);
    }

    /**
     * Add course targets for announcement
     */
    public function addAnnouncementCourses($announcementId, $courseIds, $clientId) {
        if (empty($courseIds)) return true;

        $sql = "INSERT INTO announcement_courses (announcement_id, course_id, client_id) VALUES ";
        $values = [];
        $params = [];

        foreach ($courseIds as $courseId) {
            $values[] = "(?, ?, ?)";
            $params[] = $announcementId;
            $params[] = $courseId;
            $params[] = $clientId;
        }

        $sql .= implode(', ', $values);
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Remove course targets for announcement
     */
    public function removeAnnouncementCourses($announcementId, $clientId) {
        $sql = "UPDATE announcement_courses SET is_deleted = 1 
                WHERE announcement_id = ? AND client_id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$announcementId, $clientId]);
    }

    /**
     * Get announcement courses
     */
    public function getAnnouncementCourses($announcementId, $clientId) {
        $sql = "SELECT ac.course_id, c.title as course_title
                FROM announcement_courses ac
                LEFT JOIN courses c ON ac.course_id = c.id
                WHERE ac.announcement_id = ? AND ac.client_id = ? AND ac.is_deleted = 0";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$announcementId, $clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Record user acknowledgment
     */
    public function acknowledgeAnnouncement($announcementId, $userId, $clientId, $ipAddress = null, $userAgent = null) {
        $sql = "INSERT INTO announcement_acknowledgments 
                (announcement_id, user_id, client_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE acknowledged_at = NOW()";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$announcementId, $userId, $clientId, $ipAddress, $userAgent]);
    }

    /**
     * Check if user has acknowledged announcement
     */
    public function hasUserAcknowledged($announcementId, $userId, $clientId) {
        $sql = "SELECT COUNT(*) FROM announcement_acknowledgments 
                WHERE announcement_id = ? AND user_id = ? AND client_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$announcementId, $userId, $clientId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get acknowledgment statistics
     */
    public function getAcknowledgmentStats($announcementId, $clientId) {
        $sql = "SELECT 
                    COUNT(aa.id) as acknowledged_count,
                    (SELECT COUNT(*) FROM user_profiles up 
                     WHERE up.client_id = ? AND up.is_deleted = 0) as total_users,
                    ROUND((COUNT(aa.id) * 100.0 / 
                          (SELECT COUNT(*) FROM user_profiles up 
                           WHERE up.client_id = ? AND up.is_deleted = 0)), 2) as acknowledgment_percentage
                FROM announcement_acknowledgments aa
                WHERE aa.announcement_id = ? AND aa.client_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId, $clientId, $announcementId, $clientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Record user view
     */
    public function recordView($announcementId, $userId, $clientId, $timeSpent = null, $ipAddress = null, $userAgent = null) {
        $sql = "INSERT INTO announcement_views 
                (announcement_id, user_id, client_id, time_spent_seconds, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    last_viewed_at = NOW(),
                    view_count = view_count + 1,
                    time_spent_seconds = COALESCE(?, time_spent_seconds)";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $announcementId, $userId, $clientId, $timeSpent, $ipAddress, $userAgent,
            $timeSpent
        ]);
    }
}
