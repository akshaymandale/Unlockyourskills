<?php
require_once 'config/Database.php';

class EventModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get all events with filters and pagination
     */
    public function getAllEvents($limit = 10, $offset = 0, $search = '', $filters = [], $clientId = null) {
        $sql = "SELECT e.*,
                       up.full_name as created_by_name,
                       COUNT(DISTINCT er.id) as rsvp_count,
                       COUNT(DISTINCT CASE WHEN er.response = 'yes' THEN er.id END) as yes_count,
                       COUNT(DISTINCT CASE WHEN er.response = 'no' THEN er.id END) as no_count,
                       COUNT(DISTINCT CASE WHEN er.response = 'maybe' THEN er.id END) as maybe_count
                FROM events e
                LEFT JOIN user_profiles up ON e.created_by = up.id
                LEFT JOIN event_rsvps er ON e.id = er.event_id AND er.is_deleted = 0
                WHERE e.is_deleted = 0";
        
        $params = [];
        
        if ($clientId !== null) {
            $sql .= " AND e.client_id = ?";
            $params[] = $clientId;
        }
        
        if (!empty($search)) {
            $sql .= " AND (e.title LIKE ? OR e.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND e.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['event_type'])) {
            $sql .= " AND e.event_type = ?";
            $params[] = $filters['event_type'];
        }

        if (!empty($filters['audience_type'])) {
            $sql .= " AND e.audience_type = ?";
            $params[] = $filters['audience_type'];
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(e.start_datetime) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(e.start_datetime) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $sql .= " GROUP BY e.id ORDER BY e.start_datetime ASC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        $stmt = $this->conn->prepare($sql);
        foreach ($params as $index => $param) {
            $stmt->bindValue($index + 1, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Add RBAC flags if not set by controller
        if (!empty($_SESSION['user'])) {
            require_once 'includes/permission_helper.php';
            $currentUser = $_SESSION['user'];
            foreach ($events as &$event) {
                if (!isset($event['can_edit'])) {
                    $event['can_edit'] = (canEdit('events') && ($currentUser['system_role'] === 'super_admin' || $event['created_by'] == $currentUser['id'])) ? 1 : 0;
                }
                if (!isset($event['can_delete'])) {
                    $event['can_delete'] = (canDelete('events') && ($currentUser['system_role'] === 'super_admin' || $event['created_by'] == $currentUser['id'])) ? 1 : 0;
                }
            }
            unset($event);
        }
        return $events;
    }

    /**
     * Get event by ID
     */
    public function getEventById($id, $clientId = null) {
        $sql = "SELECT e.*,
                       up.full_name as created_by_name
                FROM events e
                LEFT JOIN user_profiles up ON e.created_by = up.id
                WHERE e.id = ? AND e.is_deleted = 0";
        
        $params = [$id];
        
        if ($clientId !== null) {
            $sql .= " AND e.client_id = ?";
            $params[] = $clientId;
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create new event
     */
    public function createEvent($data) {
        $sql = "INSERT INTO events (
                    client_id, title, description, event_type, event_link, start_datetime, 
                    end_datetime, audience_type, location, enable_rsvp, send_reminder_before,
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['client_id'],
            $data['title'],
            $data['description'],
            $data['event_type'],
            $data['event_link'],
            $data['start_datetime'],
            $data['end_datetime'],
            $data['audience_type'],
            $data['location'],
            $data['enable_rsvp'] ? 1 : 0,
            $data['send_reminder_before'],
            $data['status'],
            $data['created_by']
        ]);

        return $result ? $this->conn->lastInsertId() : false;
    }

    /**
     * Update event
     */
    public function updateEvent($id, $data) {
        $sql = "UPDATE events SET
                    title = ?,
                    description = ?,
                    event_type = ?,
                    event_link = ?,
                    start_datetime = ?,
                    end_datetime = ?,
                    audience_type = ?,
                    location = ?,
                    enable_rsvp = ?,
                    send_reminder_before = ?,
                    status = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ? AND client_id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['event_type'],
            $data['event_link'],
            $data['start_datetime'],
            $data['end_datetime'],
            $data['audience_type'],
            $data['location'],
            $data['enable_rsvp'] ? 1 : 0,
            $data['send_reminder_before'],
            $data['status'],
            $data['updated_by'],
            $id,
            $data['client_id']
        ]);
    }

    /**
     * Soft delete event
     */
    public function deleteEvent($id, $clientId) {
        $sql = "UPDATE events SET is_deleted = 1, updated_at = NOW() WHERE id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$id, $clientId]);
    }

    /**
     * Update event status only
     */
    public function updateEventStatus($id, $status, $clientId) {
        $sql = "UPDATE events SET status = ?, updated_at = NOW() WHERE id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $id, $clientId]);
    }

    /**
     * Get event RSVP responses
     */
    public function getEventRSVPs($eventId, $clientId = null) {
        $sql = "SELECT er.*, up.full_name, up.email
                FROM event_rsvps er
                LEFT JOIN user_profiles up ON er.user_id = up.id
                WHERE er.event_id = ? AND er.is_deleted = 0";
        
        $params = [$eventId];
        
        if ($clientId !== null) {
            $sql .= " AND er.client_id = ?";
            $params[] = $clientId;
        }
        
        $sql .= " ORDER BY er.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Submit RSVP
     */
    public function submitRSVP($data) {
        // Check if RSVP already exists
        $checkSql = "SELECT id FROM event_rsvps WHERE event_id = ? AND user_id = ? AND client_id = ? AND is_deleted = 0";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->execute([$data['event_id'], $data['user_id'], $data['client_id']]);
        
        if ($checkStmt->fetch()) {
            // Update existing RSVP
            $sql = "UPDATE event_rsvps SET response = ?, updated_at = NOW() 
                    WHERE event_id = ? AND user_id = ? AND client_id = ? AND is_deleted = 0";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$data['response'], $data['event_id'], $data['user_id'], $data['client_id']]);
        } else {
            // Create new RSVP
            $sql = "INSERT INTO event_rsvps (event_id, user_id, client_id, response) VALUES (?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$data['event_id'], $data['user_id'], $data['client_id'], $data['response']]);
        }
    }

    /**
     * Get upcoming events for user
     */
    public function getUpcomingEventsForUser($userId, $clientId, $limit = 10) {
        $sql = "SELECT e.*, up.full_name as created_by_name,
                       er.response as user_rsvp
                FROM events e
                LEFT JOIN user_profiles up ON e.created_by = up.id
                LEFT JOIN event_rsvps er ON e.id = er.event_id AND er.user_id = ? AND er.is_deleted = 0
                WHERE e.client_id = ? AND e.is_deleted = 0 
                AND e.status = 'active' 
                AND e.start_datetime > NOW()
                ORDER BY e.start_datetime ASC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $clientId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get event audiences (for course/group specific events)
     */
    public function getEventAudiences($eventId) {
        $sql = "SELECT * FROM event_audiences WHERE event_id = ? AND is_deleted = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add event audience
     */
    public function addEventAudience($eventId, $audienceType, $audienceId, $clientId) {
        $sql = "INSERT INTO event_audiences (event_id, audience_type, audience_id, client_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$eventId, $audienceType, $audienceId, $clientId]);
    }

    /**
     * Remove event audiences
     */
    public function removeEventAudiences($eventId, $clientId) {
        $sql = "UPDATE event_audiences SET is_deleted = 1 WHERE event_id = ? AND client_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$eventId, $clientId]);
    }
}
