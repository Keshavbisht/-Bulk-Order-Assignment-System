<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Courier Service - Handles courier-related operations
 */
class CourierService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Fetch available couriers for a location
     */
    public function getAvailableCouriers($location, $limit = null) {
        $sql = "SELECT c.*, 
                       (c.daily_capacity - c.current_assigned_count) as available_capacity
                FROM couriers c
                WHERE c.is_active = 1
                AND JSON_CONTAINS(c.serviceable_locations, :location, '$')
                AND (c.daily_capacity - c.current_assigned_count) > 0
                ORDER BY available_capacity DESC, c.current_assigned_count ASC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':location', json_encode($location), PDO::PARAM_STR);
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get courier by ID
     */
    public function getCourierById($courierId) {
        $sql = "SELECT * FROM couriers WHERE id = :id AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $courierId]);
        return $stmt->fetch();
    }

    /**
     * Update courier assigned count (with lock)
     */
    public function updateCourierCapacity($courierId, $increment = 1) {
        $sql = "UPDATE couriers 
                SET current_assigned_count = current_assigned_count + :increment
                WHERE id = :id 
                AND (daily_capacity - current_assigned_count) >= :increment";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':increment' => $increment,
            ':id' => $courierId
        ]);
    }

    /**
     * Get courier capacity with lock
     */
    public function getCourierCapacityWithLock($courierId) {
        $sql = "SELECT id, daily_capacity, current_assigned_count,
                       (daily_capacity - current_assigned_count) as available_capacity
                FROM couriers 
                WHERE id = :id AND is_active = 1
                FOR UPDATE";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':id' => $courierId]);
        return $stmt->fetch();
    }
}


