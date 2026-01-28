<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Order Service - Handles order-related operations
 */
class OrderService {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    /**
     * Fetch unassigned orders with pagination
     */
    public function getUnassignedOrders($page = 1, $limit = 100, $location = null) {
        $offset = ($page - 1) * $limit;
        $params = [':limit' => $limit, ':offset' => $offset];
        
        $sql = "SELECT o.*, 
                       (SELECT COUNT(*) FROM order_assignments oa 
                        WHERE oa.order_id = o.order_id AND oa.status = 'CONFIRMED') as assignment_count
                FROM orders o 
                WHERE o.status = 'UNASSIGNED'";
        
        if ($location) {
            $sql .= " AND o.delivery_location = :location";
            $params[':location'] = $location;
        }
        
        $sql .= " ORDER BY o.order_date ASC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get total count of unassigned orders
     */
    public function getUnassignedOrdersCount($location = null) {
        $sql = "SELECT COUNT(*) as total FROM orders WHERE status = 'UNASSIGNED'";
        $params = [];
        
        if ($location) {
            $sql .= " AND delivery_location = :location";
            $params[':location'] = $location;
        }
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return $stmt->fetch()['total'];
    }

    /**
     * Update order status
     */
    public function updateOrderStatus($orderId, $status) {
        $sql = "UPDATE orders SET status = :status WHERE order_id = :order_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':status' => $status, ':order_id' => $orderId]);
    }
}


