<?php
require_once 'config/Database.php';

class UserEventModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }

    /**
     * Get events for user with filters and pagination
     */
    public function getUserEvents($userId, $clientId, $limit = 10, $offset = 0, $search = '', $filters = []) {
        $sql = "SELECT e.*,
                       up.full_name as created_by_name,
                       er.response as user_rsvp,
                       er.created_at as rsvp_date,
                       COUNT(DISTINCT er2.id) as total_rsvp_count,
                       COUNT(DISTINCT CASE WHEN er2.response = 'yes' THEN er2.id END) as yes_count,
                       COUNT(DISTINCT CASE WHEN er2.response = 'no' THEN er2.id END) as no_count,
                       COUNT(DISTINCT CASE WHEN er2.response = 'maybe' THEN er2.id END) as maybe_count
                FROM events e
                LEFT JOIN user_profiles up ON e.created_by = up.id
                LEFT JOIN event_rsvps er ON e.id = er.event_id AND er.user_id = ? AND er.is_deleted = 0
                LEFT JOIN event_rsvps er2 ON e.id = er2.event_id AND er2.is_deleted = 0
                WHERE e.client_id = ? AND e.is_deleted = 0 
                AND e.status = 'active'
                AND (e.audience_type = 'global' 
                     OR (e.audience_type = 'group_specific' AND e.custom_field_id IS NULL))";
        
        $params = [$userId, $clientId];
        
        if (!empty($search)) {
            $sql .= " AND (e.title LIKE ? OR e.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($filters['event_type'])) {
            $sql .= " AND e.event_type = ?";
            $params[] = $filters['event_type'];
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'upcoming') {
                $sql .= " AND e.start_datetime > NOW()";
            } elseif ($filters['status'] === 'past') {
                $sql .= " AND e.start_datetime <= NOW()";
            } elseif ($filters['status'] === 'today') {
                $sql .= " AND DATE(e.start_datetime) = CURDATE()";
            }
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

        $sql .= " GROUP BY e.id
                  ORDER BY e.start_datetime ASC
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Process events to add additional info
        foreach ($events as &$event) {
            $event['is_upcoming'] = strtotime($event['start_datetime']) > time();
            $event['is_past'] = strtotime($event['start_datetime']) <= time();
            $event['is_today'] = date('Y-m-d', strtotime($event['start_datetime'])) === date('Y-m-d');
            $event['formatted_start_date'] = date('M j, Y', strtotime($event['start_datetime']));
            $event['formatted_start_time'] = date('g:i A', strtotime($event['start_datetime']));
            $event['formatted_end_time'] = $event['end_datetime'] ? date('g:i A', strtotime($event['end_datetime'])) : null;
        }

        return $events;
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
                AND (e.audience_type = 'global' 
                     OR (e.audience_type = 'group_specific' AND e.custom_field_id IS NULL))
                ORDER BY e.start_datetime ASC
                LIMIT " . (int)$limit;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $clientId]);
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
     * Get event details for user
     */
    public function getEventDetails($eventId, $userId, $clientId) {
        $sql = "SELECT e.*,
                       up.full_name as created_by_name,
                       er.response as user_rsvp,
                       er.created_at as rsvp_date,
                       COUNT(DISTINCT er2.id) as total_rsvp_count,
                       COUNT(DISTINCT CASE WHEN er2.response = 'yes' THEN er2.id END) as yes_count,
                       COUNT(DISTINCT CASE WHEN er2.response = 'no' THEN er2.id END) as no_count,
                       COUNT(DISTINCT CASE WHEN er2.response = 'maybe' THEN er2.id END) as maybe_count
                FROM events e
                LEFT JOIN user_profiles up ON e.created_by = up.id
                LEFT JOIN event_rsvps er ON e.id = er.event_id AND er.user_id = ? AND er.is_deleted = 0
                LEFT JOIN event_rsvps er2 ON e.id = er2.event_id AND er2.is_deleted = 0
                WHERE e.id = ? AND e.client_id = ? AND e.is_deleted = 0 
                AND e.status = 'active'
                AND (e.audience_type = 'global' OR e.audience_type = 'group_specific')
                GROUP BY e.id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $eventId, $clientId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            $event['is_upcoming'] = strtotime($event['start_datetime']) > time();
            $event['is_past'] = strtotime($event['start_datetime']) <= time();
            $event['is_today'] = date('Y-m-d', strtotime($event['start_datetime'])) === date('Y-m-d');
            $event['formatted_start_date'] = date('M j, Y', strtotime($event['start_datetime']));
            $event['formatted_start_time'] = date('g:i A', strtotime($event['start_datetime']));
            $event['formatted_end_time'] = $event['end_datetime'] ? date('g:i A', strtotime($event['end_datetime'])) : null;
        }

        return $event;
    }
}
