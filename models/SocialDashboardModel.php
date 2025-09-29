<?php
require_once 'config/Database.php';

class SocialDashboardModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get social dashboard data for a user
     */
    public function getSocialDashboardData($userId, $clientId) {
        try {
            $data = [
                'polls' => $this->getActivePolls($clientId),
                'announcements' => $this->getRecentAnnouncements($userId, $clientId),
                'events' => $this->getUpcomingEvents($userId, $clientId),
                'social_feed' => $this->getRecentSocialPosts($userId, $clientId),
                'counts' => $this->getSocialCounts($userId, $clientId)
            ];

            return $data;
        } catch (Exception $e) {
            error_log("SocialDashboardModel: Error getting social dashboard data: " . $e->getMessage());
            return [
                'polls' => [],
                'announcements' => [],
                'events' => [],
                'social_feed' => [],
                'counts' => [
                    'polls' => 0,
                    'announcements' => 0,
                    'events' => 0,
                    'social_feed' => 0
                ]
            ];
        }
    }

    /**
     * Get active polls for the user
     */
    private function getActivePolls($clientId) {
        $sql = "SELECT p.id, p.title, p.description, p.type, p.target_audience, 
                       p.start_datetime, p.end_datetime, p.status,
                       up.full_name as created_by_name,
                       (SELECT COUNT(*) FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.is_deleted = 0) as total_votes
                FROM polls p
                LEFT JOIN user_profiles up ON p.created_by = up.id
                WHERE p.is_deleted = 0 
                AND p.client_id = ? 
                AND p.status = 'active'
                AND p.start_datetime <= NOW() 
                AND p.end_datetime >= NOW()
                ORDER BY p.created_at DESC
                LIMIT 5";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent announcements for the user
     */
    private function getRecentAnnouncements($userId, $clientId) {
        // First update expired announcements
        $this->updateExpiredAnnouncements($clientId);

        $sql = "SELECT a.id, a.title, a.body, a.urgency, a.audience_type,
                       a.start_datetime, a.end_datetime, a.status,
                       up.full_name as created_by_name,
                       (SELECT COUNT(*) FROM announcement_acknowledgments aa 
                        WHERE aa.announcement_id = a.id AND aa.client_id = a.client_id) as acknowledgment_count
                FROM announcements a
                LEFT JOIN user_profiles up ON a.created_by = up.id
                WHERE a.is_deleted = 0 
                AND a.client_id = ?
                AND a.status = 'active'
                AND (a.audience_type = 'global' 
                     OR (a.audience_type = 'user_specific' AND a.id IN (
                         SELECT announcement_id FROM announcement_targets 
                         WHERE user_id = ? AND is_deleted = 0
                     )))
                ORDER BY 
                    CASE a.urgency
                        WHEN 'urgent' THEN 1
                        WHEN 'warning' THEN 2
                        WHEN 'info' THEN 3
                    END,
                    a.created_at DESC
                LIMIT 5";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get upcoming events for the user
     */
    private function getUpcomingEvents($userId, $clientId) {
        $sql = "SELECT e.id, e.title, e.description, e.event_type, e.audience_type,
                       e.start_datetime, e.end_datetime, e.location, e.status,
                       up.full_name as created_by_name,
                       COUNT(DISTINCT er.id) as rsvp_count,
                       COUNT(DISTINCT CASE WHEN er.response = 'yes' THEN er.id END) as yes_count
                FROM events e
                LEFT JOIN user_profiles up ON e.created_by = up.id
                LEFT JOIN event_rsvps er ON e.id = er.event_id AND er.is_deleted = 0
                WHERE e.is_deleted = 0 
                AND e.client_id = ?
                AND e.status = 'active'
                AND e.start_datetime >= NOW()
                AND (e.audience_type = 'global' 
                     OR (e.audience_type = 'user_specific' AND e.id IN (
                         SELECT event_id FROM event_targets 
                         WHERE user_id = ? AND is_deleted = 0
                     )))
                GROUP BY e.id
                ORDER BY e.start_datetime ASC
                LIMIT 5";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId, $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get recent social feed posts for the user
     */
    private function getRecentSocialPosts($userId, $clientId) {
        $sql = "SELECT p.id, p.title, p.body, p.post_type, p.visibility, p.is_pinned,
                       p.created_at, p.updated_at,
                       up.full_name as author_name, up.profile_picture,
                       COUNT(DISTINCT c.id) as comment_count,
                       COUNT(DISTINCT r.id) as reaction_count
                FROM feed_posts p
                LEFT JOIN user_profiles up ON p.user_id = up.id
                LEFT JOIN feed_comments c ON p.id = c.post_id AND c.status = 'active'
                LEFT JOIN feed_reactions r ON p.id = r.post_id
                WHERE p.deleted_at IS NULL 
                AND p.client_id = ?
                AND p.status = 'active'
                AND p.visibility = 'global'
                GROUP BY p.id
                ORDER BY p.is_pinned DESC, p.created_at DESC
                LIMIT 5";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get counts for social features
     */
    private function getSocialCounts($userId, $clientId) {
        $counts = [
            'polls' => $this->getActivePollsCount($clientId),
            'announcements' => $this->getUnreadAnnouncementsCount($userId, $clientId),
            'events' => $this->getUpcomingEventsCount($userId, $clientId),
            'social_feed' => $this->getNewSocialPostsCount($userId, $clientId)
        ];

        return $counts;
    }

    /**
     * Get count of active polls
     */
    private function getActivePollsCount($clientId) {
        $sql = "SELECT COUNT(*) as count
                FROM polls 
                WHERE is_deleted = 0 
                AND client_id = ? 
                AND status = 'active'
                AND start_datetime <= NOW() 
                AND end_datetime >= NOW()";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Get count of unread announcements
     */
    private function getUnreadAnnouncementsCount($userId, $clientId) {
        // First update expired announcements
        $this->updateExpiredAnnouncements($clientId);

        $sql = "SELECT COUNT(*) as count
                FROM announcements a
                WHERE a.is_deleted = 0 
                AND a.client_id = ?
                AND a.status = 'active'
                AND (a.audience_type = 'global' 
                     OR (a.audience_type = 'user_specific' AND a.id IN (
                         SELECT announcement_id FROM announcement_targets 
                         WHERE user_id = ? AND is_deleted = 0
                     )))
";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Get count of upcoming events
     */
    private function getUpcomingEventsCount($userId, $clientId) {
        $sql = "SELECT COUNT(*) as count
                FROM events 
                WHERE is_deleted = 0 
                AND client_id = ?
                AND status = 'active'
                AND start_datetime >= NOW()
                AND (audience_type = 'global' 
                     OR (audience_type = 'user_specific' AND id IN (
                         SELECT event_id FROM event_targets 
                         WHERE user_id = ? AND is_deleted = 0
                     )))";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Get count of new social posts (last 24 hours)
     */
    private function getNewSocialPostsCount($userId, $clientId) {
        $sql = "SELECT COUNT(*) as count
                FROM feed_posts 
                WHERE deleted_at IS NULL 
                AND client_id = ?
                AND status = 'active'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND visibility = 'global'";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }

    /**
     * Update expired announcements
     */
    private function updateExpiredAnnouncements($clientId) {
        $sql = "UPDATE announcements 
                SET status = 'expired', updated_at = NOW() 
                WHERE status = 'active' 
                AND client_id = ?
                AND end_datetime IS NOT NULL 
                AND end_datetime < NOW()";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$clientId]);
    }
}
