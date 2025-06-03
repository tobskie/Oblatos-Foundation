<?php
class Notification {
    // Database connection and table name
    private $db;
    private $table_name = "notifications";
    
    // Object properties
    public $id;
    public $user_id;
    public $title;
    public $message;
    public $type;
    public $is_read;
    public $created_at;
    
    // Constructor
    public function __construct($db) {
        $this->db = $db;
    }
    
    // Create notification
    public function create() {
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (user_id, title, message, type)
                    VALUES (:user_id, :title, :message, :type)";
            
            $stmt = $this->db->prepare($query);
            
            // Sanitize and bind values
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":title", $this->title);
            $stmt->bindParam(":message", $this->message);
            $stmt->bindParam(":type", $this->type);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user's notifications
    public function get_user_notifications($user_id, $limit = 10, $offset = 0) {
        try {
            $query = "SELECT * FROM " . $this->table_name . "
                     WHERE user_id = :user_id
                     ORDER BY created_at DESC
                     LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return false;
        }
    }
    
    // Get unread notification count
    public function get_unread_count($user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                     WHERE user_id = :user_id AND is_read = FALSE";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['count'];
        } catch (PDOException $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    // Mark notification as read
    public function mark_as_read($notification_id, $user_id) {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET is_read = TRUE
                     WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $notification_id);
            $stmt->bindParam(":user_id", $user_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    // Mark all notifications as read
    public function mark_all_as_read($user_id) {
        try {
            $query = "UPDATE " . $this->table_name . "
                     SET is_read = TRUE
                     WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    // Delete old notifications (e.g., older than 30 days)
    public function delete_old_notifications($days = 30) {
        try {
            $query = "DELETE FROM " . $this->table_name . "
                     WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":days", $days, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting old notifications: " . $e->getMessage());
            return false;
        }
    }
} 