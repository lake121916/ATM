<?php
include 'db.php';

class Notification {
    private $db;
    private $table = 'notifications';

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // Create a new notification
    public function create($user_id, $message, $type = 'info') {
        $query = "INSERT INTO " . $this->table . " (user_id, message, type, is_read) 
                  VALUES (?, ?, ?, 0)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("iss", $user_id, $message, $type);

        return $stmt->execute();
    }

    // Get notifications for a user
    public function getUserNotifications($user_id, $limit = 5) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();

        $result = $stmt->get_result();
        $notifications = [];

        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }

        return $notifications;
    }

    // Mark notification as read
    public function markAsRead($notification_id) {
        $query = "UPDATE " . $this->table . " SET is_read = 1 WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $notification_id);

        return $stmt->execute();
    }

    // Get unread notification count
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row['count'];
    }
}
?>
